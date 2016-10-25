<?php

/**
 * File Editor Module
 *
 * A module for editing files (in the admin area).
 *
 * @author Florea Banus George, Matjaz Potocnik
 *
 * ProcessWire 3.x
 * Copyright (C) 2016 by Ryan Cramer
 * Licensed under GNU/GPL v2
 *
 * https://processwire.com
 *
 */

class ProcessFileEdit extends Process {

	public function init() {
		parent::init();

		// When auto_detect_line_endings is turned on, PHP will examine the data read by fgets() and file() to see
		// if it is using Unix, MS-Dos or Macintosh line-ending conventions.
		ini_set('auto_detect_line_endings', true);
	}

	public function ___execute() {
		if(!$this->wire('user')->isSuperuser() && !$this->wire('user')->hasPermission('file-editor')) throw new WirePermissionException($this->_('Insufficient permissions.'));

		$msg = $out = $fileContent = "";
		$ro = false; // is file readonly?

		// sanitize directory path - sanitizer->path is not ok, since path with : is valid on windows eg. C:\somedir
		// maybe realpath($this->dirPath) would be enough?
		$this->dirPath = $this->sanitizePath($this->dirPath);

		// check if path is a directory
		if(!is_dir($this->dirPath)) $msg = sprintf($this->_('Directory %s not found.'), $this->dirPath);

		// prevent parent path notation ..
		if(strpos($this->dirPath, '..') !== false) $msg = sprintf($this->_('Directory %s contains parent path (..) notation.'), $this->dirPath);

		$extensions = $this->toArray($this->extensionsFilter);
		if(empty($extensions)) $msg = $this->_('Extensions filter is empty.');

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
			if (!is_file($file)) $msg = sprintf($this->_('File %s not found.'), $file);

			if($msg != "") {
				$this->error($msg); //this is not visible in modal edit
				return "<h3 class='err'>Error: $msg</h3>";
			}

			$displayFile = $file;
			if(DIRECTORY_SEPARATOR != '/') $displayFile = str_replace('/', DIRECTORY_SEPARATOR, $displayFile); // replace slashes with backslashes on windows

			if($this->wire('input')->post('saveFile') || $this->wire('input')->get('s')) {
				// post->saveFile is present when submit button is not "hijacked" in javascript, while get->s is for save when editing in modal
				if($fileHandle = @fopen($file, "w+")) {
					//we can write to file
					$content = fwrite($fileHandle, $this->wire('input')->post('editFile'));
					fclose($fileHandle);
					if($this->wire('input')->get('s')) {
						return ""; // empty string means there is no error
						exit(0); // just in case, not needed
					}
					$this->message($this->_('File saved'));
					$this->session->redirect($this->page->httpUrl . "?f=" . $filebase);
				} else {
					//error saving
					$msg = sprintf($this->_('Error saving file %s'), $file);
					if($this->wire('input')->get('s')) {
						return $msg;
						exit(0); // just in case, not needed
					}
	      	$this->message($msg);
					$this->session->redirect($this->page->httpUrl . "?f=" . $filebase);
				}
			}
			//continue with edit

			//in modal there are no breadcrumbs
			//$this->fuel('breadcrumbs')->add(new Breadcrumb('./', $this->_('File Editor')));
			//$this->setFuel('processHeadline', sprintf($this->_("Edit file: %s"), $file));

			//$fileUTF8 = htmlentities(iconv('Windows-1250', 'UTF-8', $file), ENT_QUOTES); //MP it works for me on windows
			$fileUTF8 = htmlentities($this->toUTF8($displayFile), ENT_QUOTES); //MP it works for me
			if($fileHandle = @fopen($file, "r+")) {
    	  $fileContent = ((filesize($file) > 0) ? fread($fileHandle, filesize($file)) : '');
      	fclose($fileHandle);
      	$out .= "<h3>" . $fileUTF8 . "<span id='change'></span></h3>";
     	} else {
     		// file is readonly
  	    //$msg = sprintf($this->_('File %s has readonly permissions.'), $file);
	      //$this->message($msg);
	      $ro = true;
      	$out .= "<h3>" . $fileUTF8 . " (readonly)</h3>";
    	}

			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if($ext == 'php' || $ext == 'module' || $ext == 'inc') { $mode = "application/x-httpd-php"; }
			else if($ext == 'js')   { $mode = "text/javascript"; }
			else if($ext == 'html') { $mode = "text/html";  }
			else if($ext == 'css')  { $mode = "text/css";   }
			else                    { $mode = "text/plain"; }

			$config = $this->wire('config');
			$moduleRoot = $config->urls->siteModules . "ProcessFileEdit/"; //__CLASS__
			$config->scripts->add("{$moduleRoot}codemirror/lib/codemirror.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/php/php.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/xml/xml.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/javascript/javascript.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/clike/clike.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/css/css.js");
			$config->scripts->add("{$moduleRoot}codemirror/mode/htmlmixed/htmlmixed.js");
			$config->scripts->add("{$moduleRoot}codemirror/addon/display/fullscreen.js");
			$config->styles->add ("{$moduleRoot}codemirror/lib/codemirror.css");
			$config->styles->add ("{$moduleRoot}codemirror/addon/display/fullscreen.css");

			$h = "";
			if($this->editorHeight) {
				if($this->editorHeight == "auto" || $this->editorHeight == "") $h = "window.editor.setSize(null, $(window).height() - 200 +'px');";
				else $h = "window.editor.setSize(null, '$this->editorHeight');";
			}

			$out .= "
			<script>
			$(document).ready(function(){
				//var code = $('#editFile')[0];
				window.editor = CodeMirror.fromTextArea(document.getElementById('editFile'), {
					lineNumbers: true,
					mode: '{$mode}',
					indentUnit: 4,
					indentWithTabs: true,
					//viewport: Infinity,
					extraKeys: {
						'F11': function(cm) {
							cm.setOption('fullScreen', !cm.getOption('fullScreen'));
						},
						'Esc': function(cm) {
							if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false);
						}
					}
				});
				$h
				window.editor.on('change', function() {
					document.getElementById('change').innerHTML = '*';
				});
			});
			</script>
			<style>.CodeMirror{}.CodeMirror-scroll{}</style>
			";

			return $out . $this->buildForm($fileContent, $file, $filebase, $ro);

		} else {
			// show folders
			$displayPath = $this->dirPath;
			if(DIRECTORY_SEPARATOR != '/') $displayPath = str_replace('/', DIRECTORY_SEPARATOR, $displayPath); // replace slashes with backslashes on windows
			$this->fuel('breadcrumbs')->add(new Breadcrumb('./', $this->_('File Editor')));
			$this->setFuel('processHeadline', sprintf($this->_("Path: %s"), $displayPath));

			$this->wire('modules')->get('JqueryUI')->use('modal');

			$out .= "<div class='fe-file-tree'>";
			$out .= $this->php_file_tree($this->dirPath, $extensions, $this->extFilter);
			$out .= "</div>";

			return $out;
		}
	}

	/**
	 * Generates a valid HTML list of all directories, sub-directories and files
	 *
	 * @param string $directory starting point, valid path
	 * @param array $extensions array of strings with extension types (without dot), default: empty array
	 * @param bool $extFilter to include (true) or exclude (false) files with that extension, default: true
	 * @return string html markup
	 *
	 */
	 public function php_file_tree($directory, $extensions = array(), $extFilter = true) {

		$timer = Debug::timer();

		if(!function_exists("scandir")) {
			$msg = $this->_('Error: scandir function does not exist.');
			$this->error($msg);
			return;
		}

		$directory = rtrim($directory, '/\\'); // strip both slash and backslash at the end
		//if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);

		$code = $this->php_file_tree_dir($directory, "[link]", $extensions, $extFilter);

		echo "<!--timer=".Debug::timer($timer)."-->";
		return $code;
	}

	/**
	 * Recursive function to generate the list of directories/files
	 *
	 * @param string $directory starting point, valid path
	 * @param string $return_link
	 * @param array $extensions array of strings with extension types (without dot), default: empty array
	 * @param bool $extFilter to include (true) or exclude (false) files with that extension, default is true
	 * @param bool $first_call
	 * @param string $parent
	 * @return string html markup
	 *
	 */
	public function php_file_tree_dir($directory, $return_link, $extensions = array(), $extFilter = true, $first_call = true, $parent = null) {

		$tree = "";
		// Get and sort directories/files
		$file = @scandir($directory); // returns false on error
		if($file === false) {
			$file = array(); // to make foreach work
		} else {
			$file = array_diff($file, array('.', '..')); // array_diff removes . and ..
			natcasesort($file);
		}

		// Filter unwanted extensions
		if(!empty($extensions)) {
			foreach(array_keys($file) as $key) {
				if(!is_dir("$directory/$file[$key]")) {
					$ext = substr($file[$key], strrpos($file[$key], ".") + 1);
					if($extFilter == true) {
						if(in_array($ext, $extensions)) unset($file[$key]);
					}
					else if ($extFilter == false) {
						if(!in_array($ext, $extensions)) unset($file[$key]);
					}
				}
			}
		}

		// Make directories first
		$fls = $dirs = array();
		foreach($file as $this_file) {
			if(is_dir("$directory/$this_file" )) $dirs[] = $this_file; else $fls[] = $this_file;
		}
		$file = array_merge($dirs, $fls);

		if(count($file) > 0) {
			$tree .= "<ul";
			if($first_call) {
				$tree .= " class='php-file-tree'";
				$first_call = false;
			}
			$tree .= ">";

			foreach($file as $this_file) {
				//$fileName = htmlentities(iconv('Windows-1250', 'UTF-8', $this_file), ENT_QUOTES);
				$fileName = htmlentities($this->toUTF8($this_file), ENT_QUOTES); // this works for me
				if(is_dir("$directory/$this_file")) {
					// directory
					$tree .= "<li class='pft-d'><a href='#'>$fileName</a>";
					$tree .= $this->php_file_tree_dir("$directory/$this_file", $return_link ,$extensions, $extFilter, false, $parent.'/'.$this_file);
					$tree .= "</li>";
				} else {
					// file
					// get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
					$ext = "ext-" . strtolower(substr($this_file, strrpos($this_file, ".") + 1));
					//$ext = "";
					$link = str_replace("[link]", urlencode("$parent/$this_file"), $return_link); //zakaj to
					$tree .= "<li class='pft-f $ext'><a class='pw-modal pw-modal-large' href='?f=$link'>" . $fileName . "</a></li>";
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
		$f->label = $this->_('Content');
		//$f->label = $file;
		//$f->label = "&nbsp;";
		$f->collapsed = Inputfield::collapsedNo;
		$f->value = $fileContent;
		//$f->value = htmlspecialchars($fileContent);
		$f->rows = 22;
		$form->add($f);

		if(!$ro) { // no button if file is readonly
			$f = $this->wire('modules')->get('InputfieldButton');
			$f->type = 'submit';
			$f->attr('id+name','saveFile');
			$f->value = $this->_('Save file');
			$f->attr('data-url', $form->action . "&s=1");	// in ajax form submit $input->post->saveFile is not available
			$f->addClass('aos_hotkeySave head_button_clone'); // add support for AdminOnSteroids ctrl+s
			$form->add($f);
		}

		return $form->render();
	}

	/**
	 * Try to convert string to UTF-8, not bulletproof, requires mbstring and iconv support
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	private function toUTF8($str) {
		// http://stackoverflow.com/questions/7979567/php-convert-any-string-to-utf-8-without-knowing-the-original-character-set-or
		if(extension_loaded('mbstring') && function_exists('iconv')) {
			return iconv(mb_detect_encoding($str, mb_detect_order(), true), 'UTF-8', $str);
		}
		return $str;

		//http://stackoverflow.com/questions/505562/detect-file-encoding-in-php
		//if(!mb_check_encoding($output, 'UTF-8') OR
		// !($output === mb_convert_encoding(mb_convert_encoding($output, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {
		//	$output = mb_convert_encoding($content, 'UTF-8', 'pass');
		//}

		//another one
		//https://github.com/neitanod/forceutf8
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
	private function toArray($extensions, $delimiter =',') {
		$ext = preg_replace('# +#', '', $extensions); // remove all spaces
		$ext = array_filter(explode($delimiter, $ext), 'strlen'); // convert to array splitting by delimiter
		return $ext;
	}

}
