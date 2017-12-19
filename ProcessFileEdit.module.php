<?php

/**
 * File Editor Module
 *
 * A module for editing files (in the admin area).
 *
 * @version 1.7.5
 * @author Florea Banus George
 * @author Matjaz Potocnik
 * @author Roland Toth
 * @link https://github.com/matjazpotocnik/ProcessFileEdit
 *
 * ProcessWire 2.x/3.x, Copyright 2017 by Ryan Cramer
 * Licensed under GNU/GPL v2
 *
 * https://processwire.com
 *
 */
class ProcessFileEdit extends Process {

	/**
	 * Path to templates directory without trailing slash
	 * @var string
	 */
	private $templatesPath;

	public function init() {
		parent::init();

		// When auto_detect_line_endings is turned on, PHP will examine the data read by fgets() and file() to see
		// if it is using Unix, MS-Dos or Macintosh line-ending conventions.
		ini_set('auto_detect_line_endings', '1');

		$this->templatesPath = rtrim($this->wire('config')->paths->templates, '/\\');
	}

	/**
	 * Try to detect line endings based on presence of \r\n cahracters.
	 *
	 * @param string $content
	 * @return string "win", "nix", "mac" or ""
	 *
	 */
	protected function detect_newline_type($content) {
		//https://stackoverflow.com/questions/11066857/detect-eol-type-using-php
		$arr = array_count_values(
							explode(
								' ',
								preg_replace(
									'/[^\r\n]*(\r\n|\n|\r)/',
									'\1 ',
									$content
								)
							)
					);
		arsort($arr);
		$k = key($arr);
		if($k == "\r\n") return "win";
		if($k == "\n") return "nix";
		if($k == "\r") return "mac";
		return "";
	}

	/**
	 * Handles changing line endings to the required style.
	 *
	 * @param string $content file content
	 * @param string $lineEnding original file's line ending
	 * @return string
	 *
	 */
	protected function handleLineEndings($content, $lineEnding = "") {
		$target_ending = $this->lineEndings; // from module setup

		if($target_ending == 'none') return $content;
		if($target_ending == 'auto') $target_ending = $lineEnding;

		$currentLineEnding = $this->detect_newline_type($content);
		if($currentLineEnding == 'win') {
			if($target_ending == 'mac') return str_replace("\n", '',     $content);
			if($target_ending == 'nix') return str_replace("\r", '',     $content);
		} else if($currentLineEnding == 'mac') {
			if($target_ending == 'win') return str_replace("\r", "\r\n", $content);
			if($target_ending == 'nix') return str_replace("\r", "\n",   $content);
		} else if($currentLineEnding == 'nix'){
			if($target_ending == 'win') return str_replace("\n", "\r\n", $content);
			if($target_ending == 'mac') return str_replace("\n", "\r",   $content);
		}

		return $content;
	}

	public function ___execute() {

		if(!$this->wire('user')->isSuperuser() && !$this->wire('user')->hasPermission('file-editor')) {
			throw new WirePermissionException($this->_('Insufficient permissions.'));
		}

		$msg = $out = $fileContent = "";
		$ro = false; // is file readonly?

		// sanitize directory path - sanitizer->path is not ok, since path with : is valid on windows eg. C:\somedir
		// maybe realpath($this->dirPath) would be enough?
		$this->dirPath = $this->sanitizePath($this->dirPath);

		// check if path is a directory
		if(!@is_dir($this->dirPath)) $msg = sprintf($this->_('Directory %s not found.'), $this->dirPath);

		// prevent parent path notation ..
		if(strpos($this->dirPath, '..') !== false) $msg = sprintf($this->_('Directory %s contains parent path (..) notation.'), $this->dirPath);

		$this->extensionsFilter = $this->toArray($this->extensionsFilter);
		if(empty($this->extensionsFilter)) $msg = $this->_('Extensions filter is empty.');

		if($msg != "") {
			$this->error($msg);
			return; // alternative: return "<h3 class='err'>Error: $msg</h3>";
		}

		$filebase = $this->wire('input')->get('f');
		$lineNum = (int) $this->wire('input')->get('l') - 1;

		if($filebase) {
			// edit file

			// sanitize file coming from get request
			$filebase = $this->sanitizePath('/' . $filebase); // filebase should have starting slash, sanitizer will remove duplicates

			// prevent parent path in file just in case someone is playing around
			if(strpos($filebase, '..') !== false) $msg = sprintf($this->_('File %s contains parent path (..) notation.'), $filebase);

			$file = $this->dirPath . $filebase; // full file path in unix style

			// check if file exist
			if(!is_file($file)) $msg = sprintf($this->_('File %s not found.'), $file);

			if($msg != "") {
				$this->error($msg); //this is not visible in modal edit
				return "<h3 class='err'>Error: $msg</h3>";
			}

			$displayFile = $file;
			// replace slashes with backslashes on windows
			if(DIRECTORY_SEPARATOR != '/') $displayFile = str_replace('/', DIRECTORY_SEPARATOR, $displayFile);

			if($this->wire('input')->post('saveFile') || $this->wire('input')->get('s')) {
				// post->saveFile is present when submit button is not "hijacked" in javascript,
				// while get->s is for save when editing in modal

				// try to detect original line endings
				$lineEnding = "";
				if($fileHandle = @fopen($file, "r")) {
					$line = fgets($fileHandle);
					$lineEnding = $this->detect_newline_type($line);
					fclose($fileHandle);
				}

				if(trim($this->backupExtension) !== "") {
					// make a backup of the edited file
					$f = str_replace("/", "/.", $filebase);
					if(strpos($this->backupExtension, '.') !== 0) {
						$this->backupExtension = '.' . $this->backupExtension;
 					}
					$dest = $this->dirPath . $f . $this->backupExtension;
					@copy($file, $dest);
				}

				if($fileHandle = @fopen($file, "w+")) {
					// we can write to file
					$raw = $this->wire('input')->post('editFile');
					$content = $this->handleLineEndings($raw, $lineEnding);
					$content = fwrite($fileHandle, $content);
					fclose($fileHandle);
					if($this->wire('input')->get('s')) {
						return ""; // empty string means there is no error
						exit(0); // just in case, not needed
					}
					$this->message($this->_('File saved'));
					$this->session->redirect($this->page->httpUrl . "?f=" . $filebase);
				} else {
					//error saving
					$msg = sprintf($this->_('Error saving file %s'), $displayFile);
					if($this->wire('input')->get('s')) {
						return $msg;
						exit(0); // just in case, not needed
					}
					$this->message($msg);
					$this->session->redirect($this->page->httpUrl . "?f=" . $filebase);
				}
			}
			// continue with edit

			// in modal there are no breadcrumbs
			$this->fuel('breadcrumbs')->add(new Breadcrumb('./', $this->_('File Editor')));
			$this->setFuel('processHeadline', sprintf($this->_("Edit file: %s"), $file));

			$fileUTF8 = $this->toUTF8($displayFile, $this->encoding);
			if($fileHandle = @fopen($file, "r+")) {
				$fileContent = ((filesize($file) > 0) ? fread($fileHandle, filesize($file)) : '');
				fclose($fileHandle);
				$out .= "<h3>" . $fileUTF8 . "<span id='change'></span></h3>";
			} else {
				// file is readonly
				// $msg = sprintf($this->_('File %s has readonly permissions.'), $file);
				// $this->message($msg);
				$ro = true;
				$out .= "<h3>" . $fileUTF8 . " (readonly)</h3>";
			}

			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			switch($ext) {
				case 'php':
				case 'module':
				case 'inc':
					$mode = 'application/x-httpd-php';
					break;
				case 'js':
					$mode = 'text/javascript';
					break;
				case 'html':
				case 'htm':
				case 'latte':
				case 'smarty':
				case 'twig':
					$mode = 'text/html';
					break;
				case 'css':
					$mode = 'text/css';
					break;
				case 'sql':
					$mode = 'text/x-mysql';
					break;
				case 'md':
				case 'markdown':
					$mode = 'text/x-markdown';
					break;
				case 'xml':
					$mode = 'application/xml';
					break;
				default:
					$mode = 'text/plain';
			};

			$config = $this->wire('config');
			$codemirror = $config->urls->siteModules . __CLASS__ . "/codemirror/";
			$config->scripts->add("{$codemirror}lib/codemirror.js");
			$config->scripts->add("{$codemirror}mode/clike/clike.js");
			$config->scripts->add("{$codemirror}mode/xml/xml.js");
			$config->scripts->add("{$codemirror}mode/javascript/javascript.js");
			$config->scripts->add("{$codemirror}mode/css/css.js");
			$config->scripts->add("{$codemirror}mode/htmlmixed/htmlmixed.js"); // depends on XML, JavaScript and CSS modes
			$config->scripts->add("{$codemirror}mode/php/php.js"); // depends on XML, JavaScript, CSS, HTMLMixed and C-like modes
			$config->scripts->add("{$codemirror}mode/sql/sql.js");
			$config->scripts->add("{$codemirror}mode/markdown/markdown.js");
			$config->scripts->add("{$codemirror}addon/search/search.js"); // https://codemirror.net/demo/search.html
			$config->scripts->add("{$codemirror}addon/search/searchcursor.js");
			$config->scripts->add("{$codemirror}addon/search/jump-to-line.js");
			$config->scripts->add("{$codemirror}addon/dialog/dialog.js");
			$config->scripts->add("{$codemirror}addon/selection/active-line.js");
			$config->scripts->add("{$codemirror}addon/edit/matchbrackets.js");
			$config->scripts->add("{$codemirror}addon/fold/foldcode.js");
			$config->scripts->add("{$codemirror}addon/fold/foldgutter.js");
			$config->scripts->add("{$codemirror}addon/fold/brace-fold.js");
			$config->scripts->add("{$codemirror}addon/fold/xml-fold.js");
			$config->scripts->add("{$codemirror}addon/fold/indent-fold.js");
			$config->scripts->add("{$codemirror}addon/fold/markdown-fold.js");
			$config->scripts->add("{$codemirror}addon/fold/comment-fold.js");
			$config->styles->add ("{$codemirror}lib/codemirror.css");
			$config->styles->add ("{$codemirror}addon/dialog/dialog.css");
			$config->styles->add ("{$codemirror}addon/fold/foldgutter.css");
			if($this->theme != "default") $config->styles->add ("{$codemirror}theme/{$this->theme}.css");

			$height = "";
			if($this->editorHeight) {
				$h1 = ($this->wire('user')->admin_theme == "AdminThemeUikit") ? "160" : "125";
				if($this->editorHeight == "auto" || $this->editorHeight == "") $height = "window.editor.setSize(null, $(window).height() - " . $h1 . "+'px');";
				else $height = "window.editor.setSize(null, '$this->editorHeight');";
			}

			$out .= "
			<script>
			$(document).ready(function(){
				//var code = $('#editFile')[0];
				window.editor = CodeMirror.fromTextArea(document.getElementById('editFile'), {
					theme: '{$this->theme}',
					lineNumbers: true,
					mode: '{$mode}',
					indentUnit: 4,
					indentWithTabs: true,
					styleActiveLine: true,
					matchBrackets: true,
					lineWrapping: ".($this->lineWrapping ?: '0').",
					foldGutter: true,
					gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
					extraKeys: {
						'Ctrl-S': function(cm) {
							$('#saveFile').trigger('click');
						}
					}
				});
				$height
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
			<style></style>
			";

			return $out . $this->buildForm($fileContent, $filebase, $ro);

		} else {
			// show folders
			$displayPath = $this->dirPath;
			if(DIRECTORY_SEPARATOR != '/') $displayPath = str_replace('/', DIRECTORY_SEPARATOR, $displayPath); // replace slashes with backslashes on windows
			$this->fuel('breadcrumbs')->add(new Breadcrumb('./', $this->_('File Editor')));
			$this->setFuel('processHeadline', sprintf($this->_("Path: %s"), $displayPath));

			$this->wire('modules')->get('JqueryUI')->use('modal');
			$this->wire('modules')->get('JqueryMagnific');

			$out .= "<div class='fe-file-tree'>";
			$out .= $this->php_file_tree($this->dirPath, $this->extensionsFilter, $this->extFilter);
			$out .= "</div>";

			return $out;
		}
	}

	/**
	 * Generates a valid HTML list of all directories, sub-directories and files
	 *
	 * @param string $directory starting point, valid path, with or without trailing slash
	 * @param array $extensions array of strings with extension types (without dot), default: empty array, show all files
	 * @param bool $extFilter to include (false) or exclude (true) files with that extension, default: false
	 * @return string html markup
	 *
	 */
	 public function php_file_tree($directory, $extensions = array(), $extFilter = false) {

		//$timer = Debug::timer();
		if(!function_exists("scandir")) {
			$msg = $this->_('Error: scandir function does not exist.');
			$this->error($msg);
			return;
		}

		$directory = rtrim($directory, '/\\'); // strip both slash and backslash at the end

		$code  = "<div class='php-file-tree'>";
		$code .= $this->php_file_tree_dir($directory, $extensions, (bool) $extFilter);
		$code .= "</div>";

		//echo "<!--timer=".Debug::timer($timer)."-->";
		return $code;
	}

	/**
	 * Recursive function to generate the list of directories/files.
	 *
	 * @param string $directory starting point, full valid path, without trailing slash
	 * @param array $extensions array of strings with extension types (without dot), default: empty array
	 * @param bool $extFilter to include (false) or exclude (true) files with that extension, default: false (include)
	 * @param string $parent relative directory path, for internal use only
	 * @return string html markup
	 *
	 */
	private function php_file_tree_dir($directory, $extensions = array(), $extFilter = false, $parent = "") {

		// Get directories/files
		$filesArray = array_diff(@scandir($directory), array('.', '..')); // array_diff removes . and ..

		// Filter unwanted extensions
		// currently empty extensions array returns all files in folders
		// comment if statement if you want empty extensions array to return no files at all
		if(!empty($extensions)) {
			foreach(array_keys($filesArray) as $key) {

				// exclude dotfiles, but leave files in extensions filter
				// substr($filesArray[$key], 1) removes first char
				if($this->dotFilesExclusion && $filesArray[$key][0] == '.' && !in_array(substr($filesArray[$key], 1), $extensions) ) unset($filesArray[$key]);

				if(!@is_dir("$directory/$filesArray[$key]")) {
					$ext = substr($filesArray[$key], strrpos($filesArray[$key], ".") + 1);
					if($extFilter == in_array($ext, $extensions)) unset($filesArray[$key]);
				}
			}
		}

		$tree = "";

		if(count($filesArray) > 0) {
			// Sort directories/files
			natcasesort($filesArray);

			// Make directories first, then files
			$fls = $dirs = array();
			foreach($filesArray as $f) {
				if(@is_dir("$directory/$f")) $dirs[] = $f; else $fls[] = $f;
			}
			$filesArray = array_merge($dirs, $fls);

			$tree .= "<ul>";

			foreach($filesArray as $file) {
				$fileName = $this->toUTF8($file, $this->encoding);

				if(@is_dir("$directory/$file")) {
					$parentDir = "/" . str_replace($this->wire('config')->paths->root, "", $directory . "/"); // directory is without trailing slash
					$dirPath = $this->toUTF8("$parentDir/$file/", $this->encoding);
					$dirPath = str_replace("//", "/", $dirPath);
					$tree .= "<li class='pft-d'><a data-p='$dirPath'>$fileName</a>";
					$tree .= $this->php_file_tree_dir("$directory/$file", $extensions, $extFilter, "$parent/$file"); // no need to urlencode parent/file
					$tree .= "</li>";
				} else {
					// file
					// $parent = str_replace($this->dirPath, "", $directory);
					$ext = strtolower(substr($file, strrpos($file, ".") + 1));
					$link = str_replace("%2F", "/", rawurlencode("$parent/$file")); // to overcome bug/feature on apache
					if(in_array($ext, array("jpg", "png", "gif", "bmp"))) {
						// images
						$rootUrl = $this->convertPathToUrl($this->dirPath);
						$link = rtrim($rootUrl, '/\\') . $link;
						$tree .= "<li class='pft-f ext-$ext'><a href='$link'>$fileName</a></li>";
					} else if($directory == $this->templatesPath && $ext == $this->wire('config')->templateExtension) {
						// template files
						$a = $this->isTemplateFile($file);
						if($a !== false) {
							$tpl = "<span class='pw-modal pw-modal-large' data-href='$a[1]'>$a[0]</span>";
							$tree .= "<li class='pft-f ext-$ext'><a class='pw-modal pw-modal-large' href='?f=$link'>$fileName</a>$tpl</li>";
						} else {
							$tree .= "<li class='pft-f ext-$ext'><a class='pw-modal pw-modal-large' href='?f=$link'>$fileName</a></li>";
						}
					} else {
						// just plain file
						$tree .= "<li class='pft-f ext-$ext'><a class='pw-modal pw-modal-large' href='?f=$link'>$fileName</a></li>";
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
	 *
	 */
	protected function buildForm($fileContent, $filebase, $ro) {
		// full file path in unix style is $this->dirPath . $filebase;
		$form = $this->wire('modules')->get('InputfieldForm');
		$form->method = 'post';
		$form->attr('id+name','editForm');
		$form->action = $this->page->httpUrl . "?f=" . urlencode($filebase);

		/*
		InputfieldText->setAttributeValue() trims the value!
		$f = $this->wire('modules')->get('InputfieldTextarea');
		$f->attr('id+name','editFile');
		$f->skipLabel = true;
		$f->collapsed = Inputfield::collapsedNever;
		$f->value = $fileContent;
		$f->rows = 22;
		$form->add($f);
		*/
		$fc = htmlspecialchars($fileContent, ENT_QUOTES, 'UTF-8');
		$out = "<textarea style='display:none' id='editFile' name='editFile' rows='22'>\n$fc</textarea>";
		$form->prependMarkup = $out;

		if(!$ro) { // no button if file is readonly
			$f = $this->wire('modules')->get('InputfieldButton');
			$f->type = 'submit';
			$f->attr('id+name','saveFile');
			$f->value = $this->_('Save file');
			$f->attr('data-url', $form->action . "&s=1");	// in ajax form submit $input->post->saveFile is not available
			$f->addClass('aos_hotkeySave head_button_clone ui-state-disabled'); // add support for AdminOnSteroids ctrl+s
			$form->add($f);
		}

		return $form->render();
	}

	/**
	 * Check for mbstring and iconv support
	 *
	 */
	public function ___install() {
		parent::___install();
		if(!extension_loaded('mbstring') || !function_exists('iconv')) {
			$this->message("Support for mbstring and iconv is recommended.");
		}
	}

	/**
	 * Try to convert string to UTF-8, far from bulletproof, requires mbstring and iconv support
	 *
	 * @param string $str string to convert to UTF-8
	 * @param string $encoding auto|ISO-8859-2|Windows-1250|Windows-1252|urldecode
	 * @param boolean $c
	 * @return string
	 *
	 */
	private function toUTF8($str, $encoding = 'auto', $c = false) {
		// http://stackoverflow.com/questions/7979567/php-convert-any-string-to-utf-8-without-knowing-the-original-character-set-or
		if(extension_loaded('mbstring') && function_exists('iconv')) {
			if($encoding == 'auto') {
				if(DIRECTORY_SEPARATOR != '/') {
					// windows
					$str = @iconv(mb_detect_encoding($str, mb_detect_order(), true), 'UTF-8', $str);
				} else {
					// linux
					$str = @iconv('Windows-1250', 'UTF-8', $str); // wild guess!!! could be ISO-8859-2, UTF-8, ...
				}
			} else {
				if($encoding == 'urldecode') $str = @urldecode($str);
				else if($encoding == 'none') $str = $str;
				else if($encoding != 'UTF-8') $str = @iconv($encoding, 'UTF-8', $str);
			}
		}
		// replacement of % must be first!!!
		if($c) $str = str_replace(array("%", "#", " ", "{", "}", "^", "+"), array("%25", "%23", "%20", "%7B", "%7D", "%5E", "%2B"), $str);
		return $str;
	}

	/**
	 * Sanitize directory/file path:
	 *   - replace all backslashes with slashes
	 *   - replace all double slashes with single slashes
	 *   - replace all double backslashes with single backslash
	 *   - strip slash and backslash at the end
	 *
	 * @param string $path
	 * @return string
	 *
	 */
	private function sanitizePath($path) {
		if(DIRECTORY_SEPARATOR != '/') $path = str_replace(DIRECTORY_SEPARATOR, '/', $path); // first replace backslashes with slashes
		$path = preg_replace('#/+#', '/', $path); // replace double slashes with single slash
		$path = preg_replace('#\\\+#', '\\', $path); // replace double backslashes with single backslash
		$path = rtrim($path, '/\\'); // strip both slash and backslash at the end
		return $path;
	}

	/**
	 * Convert string delimited by delimiter into an array. Removes empty array keys.
	 *
	 * @param string $extensions string with delimiters
	 * @param string $delimiter, default is comma
	 * @return array
	 *
	 */
	private function toArray($extensions, $delimiter = ',') {
		$ext = preg_replace('# +#', '', $extensions); // remove all spaces
		$ext = array_filter(explode($delimiter, $ext), 'strlen'); // convert to array splitting by delimiter
		return $ext;
	}

	/**
	 * Convert $config->paths->key to $config->urls->key
	 *
	 * @param string $path eg. $config->paths->templates
	 * @param array $pathTypes eg. array('site','templates'), if not specified, array is constructed from $config->paths
	 * @return string path converted to url, empty string if path not found
	 *
	 */
	private function convertPathToUrl($path, $pathTypes = array()) {
		$path = rtrim($path, '/\\') . '/'; // strip both slash and backslash at the end and then re-add separator
		$url = '';

		if(!$pathTypes) {
			$pathTypes = array('root'); // root is missing
			foreach($this->wire('config')->paths as $pathType => $dummy) $pathTypes[] = $pathType;
		}

		foreach($pathTypes as $pathType) {
			if($this->wire('config')->paths->{$pathType} == $path) {
				$url = $this->wire('config')->urls->{$pathType};
				break;
			}
		}
		return $url;
	}

	/**
	 * Check if filename is used as a template file
	 *
	 * @param string $fileName with or without path
	 * @return array|boolean array (templatename, adminediturl), false otherwise
	 *
	 */
	private function isTemplateFile($fileName) {
		$fileName = basename($fileName);
		foreach($this->wire('templates') as $tpl) {
			if($tpl->flags !== 0) continue; // skip system templates
			if(basename($tpl->filename) == $fileName) {
				return array($tpl->name, $this->wire('config')->urls->httpAdmin . 'setup/template/edit?id=' . $tpl->id);
			}
		}
		return false;
	}

}
