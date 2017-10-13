<?php

/**
 * File Editor Module
 *
 * A module for editing files (in the admin area).
 *
 * @author Florea Banus George
 * @author Matjaz Potocnik
 * @author Roland Toth
 * @link https://github.com/matjazpotocnik/ProcessFileEdit
 *
 * ProcessWire 2.x/3.x, Copyright 2016 by Ryan Cramer
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
	protected $templatesPath;

	/**
	 * Template extension
	 * @var string
	 */
	protected $templateExtension;

	/**
	 * Root path
	 * @var string
	 */
	protected $rootPath;

	public function init() {
		parent::init();

		// When auto_detect_line_endings is turned on, PHP will examine the data read by fgets() and file() to see
		// if it is using Unix, MS-Dos or Macintosh line-ending conventions.
		ini_set('auto_detect_line_endings', '1');

		$this->templatesPath = $directory = rtrim($this->wire('config')->paths->templates, '/\\');
		$this->templateExtension = $this->wire('config')->templateExtension;
		$this->rootPath = $this->wire('config')->paths->root;
	}

    /**
     * Handles changing line endings to the required style...
     */
    protected function handleLineEndings($content) {
        $has_cr = false !== strpos($content, "\r");
        $has_nl = false !== strpos($content, "\n");

        $is_win = $has_cr && $has_nl;
        $is_mac = $has_cr;
        $is_nix = $has_nl;

        $target_ending = $this->lineEndings;

        if ($is_win) {
            if ('mac' == $target_ending) $content = str_replace("\n", '',     $content);
            if ('nix' == $target_ending) $content = str_replace("\r", '',     $content);
        } else if ($is_mac) {
            if ('win' == $target_ending) $content = str_replace("\r", "\r\n", $content);
            if ('nix' == $target_ending) $content = str_replace("\r", "\n",   $content);
        } else {
            if ('win' == $target_ending) $content = str_replace("\n", "\r\n", $content);
            if ('mac' == $target_ending) $content = str_replace("\n", "\r",   $content);
        }

        return $content;
    }

	public function ___execute() {

		if(!$this->wire('user')->isSuperuser() && !$this->wire('user')->hasPermission('file-editor')) throw new WirePermissionException($this->_('Insufficient permissions.'));

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
				if($fileHandle = @fopen($file, "w+")) {
					//we can write to file
					$raw     = $this->wire('input')->post('editFile');
					$content = $this->handleLineEndings($raw);


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
				default:
					$mode = 'text/plain';
			};

			$config = $this->wire('config');
			$moduleRoot = $config->urls->siteModules . __CLASS__ . "/";
			$config->scripts->add("{$moduleRoot}codemirror/lib/codemirror.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/clike/clike.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/xml/xml.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/javascript/javascript.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/css/css.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/htmlmixed/htmlmixed.js"); // depends on XML, JavaScript and CSS modes
			$config->scripts->add("{$moduleRoot}codemirror/mode/php/php.js"); // depends on XML, JavaScript, CSS, HTMLMixed and C-like modes
			$config->scripts->add("{$moduleRoot}codemirror/mode/sql/sql.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/markdown/markdown.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/search/search.js"); // https://codemirror.net/demo/search.html
			$config->scripts->add("{$moduleRoot}codemirror/addon/search/searchcursor.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/search/jump-to-line.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/dialog/dialog.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/selection/active-line.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/edit/matchbrackets.js");
			$config->styles->add ("{$moduleRoot}codemirror/lib/codemirror.css");
			$config->styles->add ("{$moduleRoot}codemirror/addon/dialog/dialog.css");
			if($this->theme != "default") $config->styles->add ("{$moduleRoot}codemirror/theme/{$this->theme}.css");

			$h = "";
			if($this->editorHeight) {
				$h1 = ($this->wire('user')->admin_theme == "AdminThemeUikit") ? "150" : "125";
				if($this->editorHeight == "auto" || $this->editorHeight == "") $h = "window.editor.setSize(null, $(window).height() - " . $h1 . "+'px');";
				else $h = "window.editor.setSize(null, '$this->editorHeight');";
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
					lineWrapping: true,
					extraKeys: {
						'Ctrl-S': function(cm) {
							$('#saveFile').trigger('click');
						}
					}
				});
				$h
				window.editor.on('change', function() {
					document.getElementById('change').innerHTML = '*';
					$('#saveFile').removeClass('ui-state-disabled');
					$('#saveFile_copy').removeClass('ui-state-disabled');
				});
			});
			</script>
			<style></style>
			";

			return $out . $this->buildForm($fileContent, $file, $filebase, $ro);

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
					// directory
					$parentDir = "/" . str_replace($this->rootPath, "", $directory . "/"); // directory is without trailing slash
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
					} else if($directory == $this->templatesPath && $ext == $this->templateExtension) {
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
	 * @param string $file full file path, inluding base directory
	 * @param string $filebase file path realtive to the base directory
	 * @param bool $ro true if file is readonly
	 * @return string form html markup
	 *
	 */
	protected function buildForm($fileContent, $file, $filebase, $ro) {
		$form = $this->wire('modules')->get('InputfieldForm');
		$form->method = 'post';
		$form->attr('id+name','editForm');
		$form->action = $this->page->httpUrl . "?f=" . urlencode($filebase);

		$f = $this->wire('modules')->get('InputfieldTextarea');
		$f->attr('id+name','editFile');
		$f->skipLabel = true;
		$f->collapsed = Inputfield::collapsedNever;
		$f->value = $fileContent; //htmlspecialchars($fileContent);
		$f->rows = 22;
		$form->add($f);

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
			//MP todo: don't iconv form UTF-8 to UTF-8!!!!
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
				else $str = @iconv($encoding, 'UTF-8', $str);
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
	 * @param string $path eg. $config->paths->templates
	 * @param array $pathTypes eg. array('site','templates'), if not specified, array is constructed from $config->paths
	 * @return string path converted tor url, empty string if path not found
	 *
	 */
	private function convertPathToUrl($path, $pathTypes = array()) {
		$path = rtrim($path, '/\\') . '/'; // strip both slash and backslash at the end and then re-add separator
		$url = '';

		if(!$pathTypes) {
			$pathTypes = array('root'); // root is missing
			foreach(wire('config')->paths as $pathType => $dummy) $pathTypes[] = $pathType;
		}

		foreach($pathTypes as $pathType) {
			/*if($path == wire('config')->paths->assets . "sessions/" ) {
				$url = wire('config')->urls->assets . "sessions/";
				break;
			}*/
			if(wire('config')->paths->{$pathType} == $path) {
				$url = wire('config')->urls->{$pathType};
				break;
			}
		}
		return $url;
	}

	/**
	 * Check if filename is used as a template file
	 * @param string $filenam with or without path
	 * @return array|boolean array (templatename, adminediturl), false otherwise
	 *
	 */
	private function isTemplateFile ($filename) {
		$filename = basename($filename);
		foreach(wire('templates') as $tpl) {
			if($tpl->flags !== 0) continue; // skip system templates
			if(basename($tpl->filename) == $filename) {
				return array($tpl->name, wire('config')->urls->httpAdmin . 'setup/template/edit?id=' . $tpl->id);
			}
		}
		return false;
	}

}
