<?php namespace ProcessWire;

/**
 * File Editor Module
 *
 * A module for editing files (in the admin area).
 *
 * @version 2.0.3
 * @author Florea Banus George
 * @author Matjaz Potocnik
 * @author Roland Toth
 * @link https://github.com/matjazpotocnik/ProcessFileEdit
 *
 *
 * @property string $dirPath
 * @property string $extensionsFilter
 * @property string $extFilter
 * @property string $lineEndings
 * @property int|string $dotFilesExclusion // FieldtypeCheckbox return int 1 if checked, string '' if not
 * @property string $backupExtension
 * @property string $editorHeight
 * @property int|string $lineWrapping // FieldtypeCheckbox return int 1 if checked, string '' if not
 * @property string $theme
 */
class ProcessFileEdit extends Process {

	/**
	 * Path to templates directory without trailing slash
	 * @var string
	 */
	private $templatesPath;

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->templatesPath = rtrim((string) $this->wire()->config->paths->templates, '/\\');
	}

	/**
	 * Try to detect line endings based on presence of \r\n characters in a string.
	 *
	 * @param string $content
	 * @return string "win", "nix", "mac" or ""
	 */
	private function detectLineEndingType($content) {
		// https://stackoverflow.com/questions/11066857/detect-eol-type-using-php
		$normalized = preg_replace(
			'/[^\r\n]*(\r\n|\n|\r)/',
			'$1 ',
			$content
		) ?? '';

		$counts = array_count_values(explode(' ', $normalized));
		arsort($counts);
		$lineEnding = key($counts);

		if ($lineEnding === "\r\n") return 'win';
		if ($lineEnding === "\n") return 'nix';
		if ($lineEnding === "\r") return 'mac';

		return '';
	}

	/**
	 * Handles changing line endings to the required style.
	 *
	 * @param string $content file content
	 * @param string $lineEnding original file's line ending
	 * @return string
	 */
	private function handleLineEndings($content, $lineEnding = "") {
		$target_ending = $this->lineEndings; // from module setup

		if ($target_ending === 'none') return $content;

		if ($target_ending === 'auto') {
			$target_ending = $lineEnding;
		}

		$currentLineEnding = $this->detectLineEndingType($content);

		if ($currentLineEnding === 'win') {
			if ($target_ending === 'mac') return str_replace("\n", '',     $content);
			if ($target_ending === 'nix') return str_replace("\r", '',     $content);
		} elseif ($currentLineEnding === 'mac') {
			if ($target_ending === 'win') return str_replace("\r", "\r\n", $content);
			if ($target_ending === 'nix') return str_replace("\r", "\n",   $content);
		} elseif ($currentLineEnding === 'nix'){
			if ($target_ending === 'win') return str_replace("\n", "\r\n", $content);
			if ($target_ending === 'mac') return str_replace("\n", "\r",   $content);
		}

		return $content;
	}

	/**
	 * Detect original line ending style from the first line of a file.
	 *
	 * @param string $file
	 * @return string
	 */
	private function detectFileLineEnding($file) {
		$fileHandle = @fopen($file, 'r');
		if (!$fileHandle) return '';

		$line = fgets($fileHandle);
		fclose($fileHandle);

		return $line !== false ? $this->detectLineEndingType($line) : '';
	}

	/**
	 * Normalize path separators for display on current OS.
	 *
	 * @param string $path
	 * @return string
	 */
	private function displayPath($path) {
		return DIRECTORY_SEPARATOR === '/' ? $path : str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Create a backup copy and return warning text on failure.
	 *
	 * @param string $file
	 * @param string $displayFile
	 * @return string warning text (empty string on success)
	 */
	private function createBackupWarning($file, $displayFile) {
		$backupExtension = ltrim(trim($this->backupExtension), '.');
		if ($backupExtension === '') return '';

		if (@copy($file, $file . '.' . $backupExtension)) return '';

		$backupError = sprintf($this->_('Warning: Could not create backup for file %s.'), $displayFile);
		$this->logMessage('WARNING', $backupError);
		$this->warning('WARNING: ' . $backupError);
		return $backupError;
	}

	/**
	 * Save posted file content, handle backup warnings, and manage modal/non-modal responses.
	 *
	 * In modal mode the return payload format is:
	 * - "" or "0#..." for success
	 * - "1#..." for warning
	 * - "2#..." for critical error
	 *
	 * In non-modal mode this method uses session redirects for both success and error paths.
	 *
	 * @param string $file Full filesystem path to edited file.
	 * @param string $filebase Path relative to module base directory (used for redirect URL).
	 * @return string Modal response payload.
	 */
	private function processSave($file, $filebase) {
		$lineEnding = $this->detectFileLineEnding($file);
		$displayFile = $this->displayPath($file);
		$backupError = $this->createBackupWarning($file, $displayFile);

		/** @var WireInput $input */
		$input = $this->wire()->input;
		$isModalSave = (bool) $input->get('s');
		$redirectUrl = $this->page->httpUrl . "?f=" . $filebase;

		$fileHandle = @fopen($file, "w+");
		if ($fileHandle) {
			// we can write to file
			/** @var string $raw */
			$raw = $input->post('editFile');
			$content = $this->handleLineEndings($raw, $lineEnding);
			$bytesWritten = fwrite($fileHandle, $content);
			fclose($fileHandle);

			if ($bytesWritten === false || $bytesWritten !== strlen($content)) {
				$msg = sprintf($this->_('File %s could not be saved.'), $displayFile);
				$this->logMessage('ERROR', $msg);
				if ($isModalSave) {
					if ($backupError !== '') $msg .= "\n\n" . $backupError;
					return '2#' . $msg;
				}
				$this->error('ERROR: ' . $msg);
				$this->session->redirect($redirectUrl);
			}

			// successful save
			$msg = sprintf($this->_('File %s saved.'), $displayFile);
			$this->logMessage('INFO', $msg);
			if ($isModalSave) {
				if ($backupError !== '') return '1#' . $backupError;
				return '';
			}
			$this->message('INFO: ' . $msg);
			$this->session->redirect($redirectUrl);
		} else {
			// Error opening file for writing
			$msg = sprintf($this->_('Error opening file %s for writing.'), $displayFile);
			$this->logMessage('ERROR', $msg);
			if ($isModalSave) {
				if ($backupError !== '') $msg .= "\n\n" . $backupError;
				return '2#' . $msg;
			}
			$this->error('ERROR: ' . $msg);
			$this->session->redirect($redirectUrl);
		}

		// Non-modal branches redirect; return for static analyzers.
		return '';
	}

	/**
	 * Handle file edit workflow for requested file path.
	 *
	 * Validates and resolves requested file path, optionally handles save requests,
	 * and otherwise renders the editor UI for the selected file.
	 *
	 * If save is requested, delegates to processSave().
	 *
	 * @return string HTML markup or modal response payload.
	 */
	private function processEdit() {
		/** @var WireInput $input */
		$input = $this->wire()->input;
		$filebase = $this->toString($input->get('f')); // /file.php or /directory/file.php
		$lineParam = $this->toString($input->get('l')); // line number
		$lineNum = (int) $lineParam - 1;
		$isModalSave = (bool) $input->get('s');

		$fileContent = "";
		$out = '';
		$msg = '';

		// sanitize file coming from get request, ensure just one forward slash
		$filebase = '/' . ltrim($this->sanitizePath($filebase), '/\\');
		$file = '';

		// prevent parent path in file just in case someone is playing around
		if (strpos($filebase, '..') !== false) {
			$msg = sprintf($this->_('File %s contains parent path (..) notation.'), $filebase);
		} else {
			// full file path in unix style
			$file = $this->dirPath . $filebase;
			if (!is_file($file)) {
				$msg = sprintf($this->_('File %s not found.'), $file);
			}
		}

		if ($msg !== '') {
			$this->logMessage('ERROR', $msg);
			return "<h3 class='err'>{$msg}</h3>"; // displays error in javascript alert()
		}

		if ($input->post('saveFile') || $isModalSave) {
			// post->saveFile is present when submit button is not "hijacked" in javascript,
			// while get->s is for save when editing in modal
			return $this->processSave($file, $filebase);
		}

		// show file editor interface

		$displayFile = $this->displayPath($file);

		// read file content
		$fileHandle = @fopen($file, "r");
		if ($fileHandle) {
			$fileSize = filesize($file);
			if ($fileSize !== false && $fileSize > 0) {
				$readResult = fread($fileHandle, $fileSize);
				$fileContent = ($readResult !== false) ? $readResult : '';
			} else {
				$fileContent = '';
			}
			fclose($fileHandle);
		}

		$ro = $fileHandle === false || !is_writable($file);
		$headerSuffix = $ro ? ' (readonly)' : "<span id='change'></span>";
		$out .= "<h3>{$displayFile}{$headerSuffix}</h3>";

		// initialize CodeMirror instance
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		$modes = [
			'php' => 'application/x-httpd-php',
			'module' => 'application/x-httpd-php',
			'inc' => 'application/x-httpd-php',
			'js' => 'text/javascript',
			'css' => 'text/css',
			'html' => 'text/html',
			'htmx' => 'text/html',
			'htm' => 'text/html',
			'latte' => 'text/html',
			'twig' => 'text/html',
			'smarty' => 'text/html',
			'sql' => 'text/x-mysql',
			'md' => 'text/x-markdown',
			'markdown' => 'text/x-markdown',
			'xml' => 'application/xml',
		];
		$mode = $modes[$ext] ?? 'text/plain';

		$config = $this->wire()->config;
		$cm = $config->urls->siteModules . $this . "/codemirror/";
		$config->scripts->add("{$cm}lib/codemirror.js");
		$config->scripts->add("{$cm}mode/clike/clike.js");
		$config->scripts->add("{$cm}mode/xml/xml.js");
		$config->scripts->add("{$cm}mode/javascript/javascript.js");
		$config->scripts->add("{$cm}mode/css/css.js");
		$config->scripts->add("{$cm}mode/htmlmixed/htmlmixed.js"); // depends on XML, JavaScript and CSS modes
		$config->scripts->add("{$cm}mode/php/php.js"); // depends on XML, JavaScript, CSS, HTMLMixed and C-like modes
		$config->scripts->add("{$cm}mode/sql/sql.js");
		$config->scripts->add("{$cm}mode/markdown/markdown.js");
		$config->scripts->add("{$cm}addon/search/search.js"); // https://codemirror.net/demo/search.html
		$config->scripts->add("{$cm}addon/search/searchcursor.js");
		$config->scripts->add("{$cm}addon/search/jump-to-line.js");
		$config->scripts->add("{$cm}addon/dialog/dialog.js");
		$config->scripts->add("{$cm}addon/selection/active-line.js");
		$config->scripts->add("{$cm}addon/edit/matchbrackets.js");
		$config->scripts->add("{$cm}addon/fold/foldcode.js");
		$config->scripts->add("{$cm}addon/fold/foldgutter.js");
		$config->scripts->add("{$cm}addon/fold/brace-fold.js");
		$config->scripts->add("{$cm}addon/fold/xml-fold.js");
		$config->scripts->add("{$cm}addon/fold/indent-fold.js");
		$config->scripts->add("{$cm}addon/fold/markdown-fold.js");
		$config->scripts->add("{$cm}addon/fold/comment-fold.js");
		$config->styles->add ("{$cm}lib/codemirror.css");
		$config->styles->add ("{$cm}addon/dialog/dialog.css");
		$config->styles->add ("{$cm}addon/fold/foldgutter.css");
		if ($this->theme !== "default") {
			$config->styles->add ("{$cm}theme/{$this->theme}.css");
		}

		$editorHeight = $this->editorHeight;
		$height = '';
		$heightOffset = $this->wire()->user->admin_theme === 'AdminThemeUikit' ? 160 : 125;
		if ($editorHeight === 'auto' || $editorHeight === '') {
			$height = "window.editor.setSize(null, ($(window).height() - {$heightOffset}) + 'px');";
		} else {
			$height = 'window.editor.setSize(null, ' . json_encode($editorHeight) . ');';
		}

		$out .= "
		<script>
		$(document).ready(function(){
			window.editor = CodeMirror.fromTextArea(document.getElementById('editFile'), {
				theme: '{$this->theme}',
				lineNumbers: true,
				mode: '{$mode}',
				indentUnit: 4,
				indentWithTabs: true,
				styleActiveLine: true,
				matchBrackets: true,
				lineWrapping: " . ($this->lineWrapping ? 'true' : 'false') . ",
				foldGutter: true,
				gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
				extraKeys: {
					'Ctrl-S': function(cm) {
						$('#saveFile').trigger('click');
					}
				}
			});
			{$height}
			window.editor.on('change', function() {
				document.getElementById('change').innerHTML = '*';
				$('#saveFile').removeClass('ui-state-disabled');
				$('#saveFile_copy').removeClass('ui-state-disabled');
			});
			var t = editor.charCoords({line: {$lineNum}, ch: 0}, 'local').top;
			var middleHeight = editor.getScrollerElement().offsetHeight / 2;
			editor.scrollTo(null, t - middleHeight - 5);
			window.editor.setCursor({line: {$lineNum}, ch: 0});
		});
		</script>
		<style>/*.pw-container{max-width:none!important}*/</style>
		";

		return $out . $this->buildForm($fileContent, $filebase, $ro);
	}

	/**
	 * Main module entry point.
	 *
	 * Validates module configuration and access permissions, then either:
	 * - delegates to processEdit() when a file is requested, or
	 * - renders the file tree view for browsing.
	 *
	 * @return string Rendered admin output markup.
	 * @throws WirePermissionException When current user lacks required permissions.
	 */
	public function ___execute() {

		if (!$this->wire()->user->isSuperuser() && !$this->wire()->user->hasPermission('file-editor')) {
			throw new WirePermissionException($this->_('Insufficient permissions.'));
		}

		$msg = "";
		$extensionsFilter = $this->toArray($this->extensionsFilter);

		// sanitize directory path - sanitizer->path is not ok, since path with : is valid on windows eg. C:\somedir
		$this->dirPath = $this->sanitizePath($this->dirPath);

		if (strpos($this->dirPath, '..') !== false) {
			$msg = sprintf($this->_('Directory %s contains parent path (..) notation.'), $this->dirPath);
		} elseif (!is_dir($this->dirPath)) {
			$msg = sprintf($this->_('Directory %s not found.'), $this->dirPath);
		} elseif ($extensionsFilter === []) {
			$msg = $this->_('Extensions filter is empty.');
		}

		if ($msg !== '') {
			$this->logMessage('ERROR', $msg);
			$this->error($msg);
			return '&nbsp;'; // alternative: return "<h3 class='err'>Error: $msg</h3>";
		}

		/** @var string|null $filebase */
		$filebase = $this->wire()->input->get('f');

		if ($filebase) {
			// process file editing
			return $this->processEdit();
		} else {
			// show folders
			$displayPath = $this->dirPath;
			if (DIRECTORY_SEPARATOR !== '/') {
				// replace slashes with backslashes on windows
				$displayPath = str_replace('/', DIRECTORY_SEPARATOR, $displayPath);
			}

			/** @var Breadcrumbs $breadcrumbs */
			$breadcrumbs = $this->fuel('breadcrumbs');
			$breadcrumbs->add(new Breadcrumb('./', $this->_('File Editor')));
			$this->wire('processHeadline', sprintf($this->_("Path: %s"), $displayPath));

			/** @var JqueryUI $jqueryUi */
			$jqueryUi = $this->wire()->modules->get('JqueryUI');
			$jqueryUi->use('modal');
			$this->wire()->modules->get('JqueryMagnific');

			return "<div class='fe-file-tree'>" . $this->php_file_tree($this->dirPath, $extensionsFilter, (bool) $this->extFilter) . "</div>";
		}
	}

	/**
	 * Generates a valid HTML list of all directories, sub-directories and files
	 *
	 * @param string $directory starting point, valid path, with or without trailing slash
	 * @param array<int,string> $extensions array of strings with extension types (without dot), default: empty array, show all files
	 * @param bool $extFilter to include (false) or exclude (true) files with that extension, default: false
	 * @return string html markup
	 */
	 public function php_file_tree($directory, $extensions = [], $extFilter = false) {

		//$timer = Debug::timer();
		if (!function_exists("scandir")) {
			$msg = $this->_('Error: scandir function does not exist.');
			$this->error($msg);
			return '';
		}

		$directory = rtrim($directory, '/\\'); // strip both slash and backslash at the end

		//echo "<!--timer=".Debug::timer($timer)."-->";
		return "<div class='php-file-tree'>" . $this->phpFileTreeDir($directory, $extensions, $extFilter) . "</div>";
	}

	/**
	 * Recursive function to generate the list of directories/files.
	 *
	 * @param string $directory starting point, full valid path, without trailing slash
	 * @param array<int,string> $extensions array of strings with extension types (without dot), default: empty array
	 * @param bool $extFilter to include (false) or exclude (true) files with that extension, default: false (include)
	 * @param string $parent relative directory path, for internal use only
	 * @return string html markup
	 */
	private function phpFileTreeDir($directory, $extensions = [], $extFilter = false, $parent = "") {

		// Get directories/files
		$filesArray = array_diff(@scandir($directory), ['.', '..']); // array_diff removes . and ..

		// Filter unwanted extensions
		// currently empty extensions array returns all files in folders
		// comment if statement if you want empty extensions array to return no files at all
		if (!empty($extensions)) {
			foreach(array_keys($filesArray) as $key) {

				// exclude dotfiles, but leave files in extensions filter
				// substr($filesArray[$key], 1) removes first char
				if ($this->dotFilesExclusion && $filesArray[$key][0] === '.' && !in_array(substr($filesArray[$key], 1), $extensions, true)) {
					unset($filesArray[$key]);
				}

				if (!@is_dir("{$directory}/{$filesArray[$key]}")) {
					$ext = substr($filesArray[$key], strrpos($filesArray[$key], ".") + 1);
					if ($extFilter === in_array($ext, $extensions, true)) {
						unset($filesArray[$key]);
					}
				}
			}
		}

		$tree = "";

		if ($filesArray !== []) {
			// Sort directories/files
			natcasesort($filesArray);

			// Make directories first, then files
			$fls = [];
			$dirs = [];
			foreach($filesArray as $f) {
				if (@is_dir("{$directory}/{$f}")) {
					$dirs[] = $f;
				 } else {
					$fls[] = $f;
				 }
			}

			$filesArray = array_merge($dirs, $fls);

			$tree .= "<ul>";

			foreach($filesArray as $file) {

				if (@is_dir("{$directory}/{$file}")) {
					$parentDir = "/" . str_replace($this->wire()->config->paths->root, "", $directory . "/"); // directory is without trailing slash
					$dirPath = str_replace("//", "/", "{$parentDir}/{$file}/");
					$tree .= "<li class='pft-d'><a data-p='{$dirPath}'>{$file}</a>"; //$fileName
					$tree .= $this->phpFileTreeDir("{$directory}/{$file}", $extensions, $extFilter, "{$parent}/{$file}"); // no need to urlencode parent/file
					$tree .= "</li>";
				} else {
					// file
					// $parent = str_replace($this->dirPath, "", $directory);
					$ext = strtolower(substr($file, strrpos($file, ".") + 1));
					$link = str_replace("%2F", "/", rawurlencode("{$parent}/{$file}")); // to overcome bug/feature on apache
					if (in_array($ext, ["jpg", "png", "gif", "bmp"], true)) {
						// images
						$rootUrl = $this->convertPathToUrl($this->dirPath);
						$link = rtrim($rootUrl, '/\\') . $link;
						$tree .= "<li class='pft-f ext-{$ext}'><a href='{$link}'>{$file}</a></li>"; //$fileName
					} elseif ($directory == $this->templatesPath && $ext == $this->wire()->config->templateExtension) {
						// template files
						$a = $this->isTemplateFile($file);
						if ($a !== false) {
							$tpl = "<span class='pw-modal pw-modal-large' data-href='{$a[1]}'>{$a[0]}</span>";
							$tree .= "<li class='pft-f ext-{$ext}'><a class='pw-modal pw-modal-large' href='?f={$link}'>{$file}</a>{$tpl}</li>"; //$fileName
						} else {
							$tree .= "<li class='pft-f ext-{$ext}'><a class='pw-modal pw-modal-large' href='?f={$link}'>{$file}</a></li>"; //$fileName
						}
					} else {
						// just plain file
						$tree .= "<li class='pft-f ext-{$ext}'><a class='pw-modal pw-modal-large' href='?f={$link}'>{$file}</a></li>";  //$fileName
					}
				}
			}

			$tree .= "</ul>";
		}

		return $tree;
	}

	/**
	 * Generates a form markup for editor, textarea and submit button
	 *
	 * @param string $fileContent
	 * @param string $filebase file path realtive to the base directory
	 * @param bool $ro true if file is readonly
	 * @return string form html markup
	 */
	private function buildForm($fileContent, $filebase, $ro) {
		// full file path in unix style is $this->dirPath . $filebase;
		/** @var InputfieldForm $form */
		$form = $this->wire()->modules->get('InputfieldForm');
		$form->method = 'post';
		$form->attr('id+name','editForm');
		$form->action = $this->page->httpUrl . "?f=" . urlencode($filebase);

		// InputfieldText->setAttributeValue() trims the value!
		$fc = htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8');
		$out = "<textarea style='display:none' id='editFile' name='editFile' rows='22'>\n{$fc}</textarea>";
		$form->prependMarkup = $out;

		if (!$ro) { // no button if file is readonly
			/** @var InputfieldButton $f */
			$f = $this->wire()->modules->get('InputfieldButton');
			$f->attr('type', 'submit');
			$f->attr('id+name','saveFile');
			$f->value = $this->_('Save file');
			$f->attr('data-url', $form->action . "&s=1");	// in ajax form submit $input->post->saveFile is not available
			$f->addClass('aos_hotkeySave head_button_clone ui-state-disabled'); // add support for AdminOnSteroids ctrl+s
			$form->add($f);
		}

		return $form->render();
	}

	/**
	 * Sanitize directory/file path:
	 *   - normalize directory separators to forward slashes
	 *   - collapse multiple slashes
	 *   - remove trailing slash
	 *
	 * @param string $path
	 * @return string
	 */
	private function sanitizePath($path) {
		// Normalize Windows separators
		$path = str_replace('\\', '/', $path);

		// Preserve UNC prefix while collapsing repeated slashes everywhere else
		$prefix = '';
		if (strpos($path, '//') === 0) {
			$prefix = '//';
			$path = substr($path, 2);
		}

		$path = preg_replace('#/{2,}#', '/', $path) ?? $path;
		$path = $prefix . $path;

		return $path === '/' ? $path : rtrim($path, '/');
	}

	/**
	 * Convert string with multiple possible delimiters into an array.
	 * Supports comma and any whitespace as delimiters.
	 * Removes empty and whitespace-only values.
	 *
	 * @param string|array<int,string> $extensions
	 * @return array<int,string>
	 */
	private function toArray(string|array $extensions): array {
		// Normalize array input
		if (is_array($extensions)) {
			return array_values(
				array_filter(
					array_map('trim', $extensions),
					static fn(string $value): bool => $value !== ''
				)
			);
		}

		// Normalize string input - split on commas or any whitespace (one or more)
		$parts = preg_split('/[,\s]+/', trim($extensions), -1, PREG_SPLIT_NO_EMPTY);

		if ($parts === false) return [];

		return $parts;
	}

	/**
	 * Convert $config->paths->key to $config->urls->key
	 *
	 * @param string $path eg. $config->paths->templates
	 * @param array<int,string> $pathTypes eg. ['site','templates')] if not specified, array is constructed from $config->paths
	 * @return string path converted to url, empty string if path not found
	 */
	private function convertPathToUrl($path, $pathTypes = []) {
		$normalizedPath = rtrim($this->toString($path), '/\\') . '/'; // normalize separators and trailing slash
		if ($normalizedPath === '/') return '';

		/** @var Config $config */
		$config = $this->wire()->config;

		if (!is_array($pathTypes) || $pathTypes === []) {
			$pathTypes = ['root'];
			foreach($config->paths as $pathType => $dummy) {
				$pathTypes[] = (string) $pathType;
			}
		}

		foreach($pathTypes as $pathType) {
			$pathType = $this->toString($pathType);
			if ($pathType === '') continue;

			$configPath = rtrim($this->toString($config->paths->{$pathType}), '/\\') . '/';
			if ($configPath === $normalizedPath) {
				return $this->toString($config->urls->{$pathType});
			}
		}

		return '';
	}

	/**
	 * Check if filename is used as a template file
	 *
	 * @param string $fileName with or without path
	 * @return array{0:string,1:string}|false array (templatename, adminediturl), false otherwise
	 */
	private function isTemplateFile($fileName) {
		$fileName = basename($fileName);
		/** @var Config $config */
		$config = $this->wire()->config;
		/** @var iterable<Template> $templates */
		$templates = $this->wire()->templates;
		foreach($templates as $tpl) {
			// skip system templates
			if ($tpl->flags !== 0) continue;

			if (basename((string) $tpl->filename) === $fileName) {
			return [$tpl->name, $config->urls->admin . 'setup/template/edit?id=' . $tpl->id];
			}
		}

		return false;
	}

	/**
	 * Convert mixed values to string in a predictable way.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function toString($value) {
		if (is_string($value)) return $value;
		if (is_int($value) || is_float($value) || is_bool($value)) return (string) $value;
		if ($value instanceof \Stringable) return (string) $value;
		return '';
	}

	/**
	 * Write a message to fileedit.txt using a unified format.
	 *
	 * @param string $level
	 * @param string $message
	 * @return void
	 */
	private function logMessage($level, $message) {
		$this->wire()->log->save('file-edit', strtoupper($level) . ': ' . $message);
	}

}
