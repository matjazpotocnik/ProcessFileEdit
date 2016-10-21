<?php namespace ProcessWire;
//MP I would remove namespace so that module would work on nonamespaced PW 2.8

/**
 * File Editor Module
 *
 * A module for editing files in the admin area.
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

    public function init() { //MP from nico, don't know if it's needed
        parent::init();
        ini_set('auto_detect_line_endings', true);
    }

	public function ___execute() {

		$msg = $out = $fileContent = "";
		$ro = false;

		if ($this->input->get->f) {
			//edit file
			$filebase = $this->sanitizer->url($this->input->get->f); //MP look sok
			$file = $this->dirPath . $filebase;
			$file = str_replace('..','',$file); //MP prevent updir
			$file = str_replace('//','/',$file); //MP remove double separators

			if (!is_file($file)) {
      	//throw new WireException($this->_('File not found')); //MP too agressive
      	$msg = sprintf($this->_('File %s not found.'), $file);
      	$this->error($msg);
      	return "<h3 class='err'>$msg</h3>";
      }

			if($this->input->post->saveFile || $this->input->get->s) {
				//MP post->saveFile is for "normal" save, while get->s is for save when editing in modal
				if($fileHandle = @fopen($file, "w+")) {
					//we can write to file
					$content = fwrite($fileHandle, $this->input->post->editFile);
					fclose($fileHandle);
					if($this->input->get->s) {
						//MP empty string means there is no error
						return "";
						exit(0); //MP just in case, not needed
					}
					$this->message($this->_('File saved'));
					$this->session->redirect($this->page->httpUrl . "?f=" . $filebase);
				} else {
					//error
					$msg = sprintf($this->_('Error saving file %s'), $file);
					if($this->input->get->s) {
						return $msg;
						exit(0); //MP just in case, not neede
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
			$fileUTF8 = htmlentities($this->toUTF8($file), ENT_QUOTES); //MP it works for me
			if($fileHandle = @fopen($file, "r+")) {
    	  $fileContent = ((filesize($file) > 0) ? fread($fileHandle, filesize($file)) : '');
      	fclose($fileHandle);
      	//$fileContent = htmlspecialchars($filecontent); //MP we need this?
      	$out .= "<h3>" . $fileUTF8 . "<span id='change'></span></h3>";
     	} else {
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

			$moduleRoot = $this->config->urls->siteModules . "ProcessFileEdit/"; //__CLASS__
			$this->config->scripts->add("{$moduleRoot}codemirror/lib/codemirror.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/php/php.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/xml/xml.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/javascript/javascript.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/clike/clike.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/css/css.js");
			$this->config->scripts->add("{$moduleRoot}codemirror/mode/htmlmixed/htmlmixed.js");
			$this->config->styles->add ("{$moduleRoot}codemirror/lib/codemirror.css");

			$out .= "
			<script>
			$(document).ready(function(){
				//var code = $('#editFile')[0];
				window.editor = CodeMirror.fromTextArea(document.getElementById('editFile'), {
					lineNumbers: true,
					mode: '{$mode}',
					indentUnit: 4,
					indentWithTabs: true
				});
				window.editor.on('change', function() {
					document.getElementById('change').innerHTML = '*';
				});
			});
			</script>
			<style>.CodeMirror{height:{$this->editorHeight};}</style>
			";

			return $out . $this->buildForm($fileContent, $file, $filebase, $ro);

		} else {
			// show folders
			$this->fuel('breadcrumbs')->add(new Breadcrumb('./', $this->_('File Editor')));
			$this->setFuel('processHeadline', sprintf($this->_("Path: %s"), $this->dirPath));

			$this->wire('modules')->get('JqueryUI')->use('modal');
			
			//MP this should be made more effective
			$extensions = str_replace(' ', ',', trim($this->extensionsFilter));
			$extensions = str_replace(',,', ',', $extensions);
			$extensions = explode(',', $extensions);

			$out .= "<div class='fe-file-tree'>";
			$out .= $this->php_file_tree($this->dirPath, "[link]", $extensions); //validate dirpath?
			$out .= "</div>";

			return $out;
		}
	}

	protected function buildForm($fileContent, $file, $filebase, $ro) {
  	$form = $this->modules->get("InputfieldForm");
  	$form->method = 'post';
		$form->attr('id+name','editForm');
  	$form->action = $this->page->httpUrl . "?f=" . urlencode($filebase);
		//$form->attr('action', $this->page->httpUrl . "?f=" . $filebase);

		$f = $this->modules->get('InputfieldTextarea');
		$f->attr('id+name','editFile');
		$f->label = $this->_('Content');
		//$f->label = $file;
		//$f->label = "&nbsp;";
		$f->collapsed = Inputfield::collapsedNo;
		$f->value = $fileContent;
		$f->rows = 22;
		$form->add($f);

		if(!$ro) { //MP no button if file is readonly
			$f = $this->modules->get("InputfieldButton");
			$f->type = 'submit';
			$f->attr('id+name','saveFile');
			$f->value = $this->_('Save file');
			$f->attr('data-url', $form->action . "&s=1");	//MP in ajax form submit, $input->post->saveFile is not available
			$f->addClass('aos_hotkeySave'); //MP add support for AdminOnSteroids ctrl+s
			$form->add($f);
		}

		return $form->render();
  }

	public function php_file_tree($directory, $return_link, $extensions = array()) {
		// Generates a valid XHTML list of all directories, sub-directories, and files in $directory
		// Remove trailing slash

		$timer = Debug::timer();

		if(!function_exists("scandir")) {
			$this->error('scandir function does not exist.');
			return "";
		}
		if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);
		$code = $this->php_file_tree_dir($directory, $return_link, $extensions);

		echo "<!--timer=".Debug::timer($timer)."-->";
		return $code;
	}

	public function php_file_tree_dir($directory, $return_link, $extensions = array(), $first_call = true, $parent = null) {
		// Recursive function called by php_file_tree() to list directories/files
		$php_file_tree = "";
		// Get and sort directories/files
		$file = @scandir($directory); //MP returns false on error
		if($file === false) {
			$file = array(); //MP to make foreach work
		} else {
			$file = array_diff($file, array('.', '..')); //MP array_diff removes . and ..
			natcasesort($file);
		}

		// Make directories first
		$files = $dirs = array();
		foreach($file as $this_file) {
			if(is_dir("$directory/$this_file" )) $dirs[] = $this_file; else $files[] = $this_file;
		}
		$file = array_merge($dirs, $files);

		// Filter unwanted extensions
		if( !empty($extensions) ) {
			foreach( array_keys($file) as $key ) {
				if( !is_dir("$directory/$file[$key]") ) {
					$ext = substr($file[$key], strrpos($file[$key], ".") + 1);
					if($this->extFilter == true) {
						if( in_array($ext, $extensions) ) unset($file[$key]);
					}
					else if ($this->extFilter == false) {
						if( !in_array($ext, $extensions) ) unset($file[$key]);
					}
				}
			}
		}

		if( count($file) > 0 ) {
			$php_file_tree .= "<ul";
			if( $first_call ) { $php_file_tree .= " class=\"php-file-tree\""; $first_call = false; }
			$php_file_tree .= ">";
			foreach( $file as $this_file ) {
				//$fileName = htmlentities(iconv('Windows-1250', 'UTF-8', $this_file), ENT_QUOTES); //MP it works for me on windows
				$fileName = htmlentities($this->toUTF8($this_file), ENT_QUOTES); //MP this works for me
				if( is_dir("$directory/$this_file") ) {
					// Directory
					//MP $php_file_tree .= "<li class=\"pft-dir\"><a href=\"#\">" . htmlspecialchars($this_file) . "</a>";
					$php_file_tree .= "<li class=\"pft-dir\"><a href=\"#\">" . $fileName . "</a>";
					$php_file_tree .= $this->php_file_tree_dir("$directory/$this_file", $return_link ,$extensions, false, $parent.'/'.$this_file);
					$php_file_tree .= "</li>";
				} else {
					// File
					// Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
					$ext = "ext-" . strtolower(substr($this_file, strrpos($this_file, ".") + 1));
					$link = str_replace("[link]", "$parent/" . urlencode($this_file), $return_link);
					//MP $php_file_tree .= "<li class=\"pft-file " . $ext . "\"><a class='pw-modal pw-modal-large' href=\"?f=$link\">" . htmlspecialchars($this_file) . "</a></li>";
					//MP $php_file_tree .= "<li class=\"pft-file " . $ext . "\"><a class='pw-modal pw-modal-large' href=\"?f=$link\">" . htmlentities($this_file) . "</a></li>";
					$php_file_tree .= "<li class=\"pft-file " . $ext . "\"><a class='pw-modal pw-modal-large' href=\"?f=$link\">" . $fileName . "</a></li>"; //to ni ok
				}
			}
			$php_file_tree .= "</ul>";
		}
		return $php_file_tree;
	}

	/**
	 * Try to convert string to UTF-8, not bulletproof
	 *
	 * @param string $str
	 * @return string
	 *
	 */
	public function toUTF8($str) { //MP
		// http://stackoverflow.com/questions/7979567/php-convert-any-string-to-utf-8-without-knowing-the-original-character-set-or
		if(extension_loaded('mbstring') && function_exists('iconv')) {
			return iconv(mb_detect_encoding($str, mb_detect_order(), true), 'UTF-8', $str);
		}
		return $str;
		
		//http://stackoverflow.com/questions/505562/detect-file-encoding-in-php
		//if(!mb_check_encoding($output, 'UTF-8')
    //OR !($output === mb_convert_encoding(mb_convert_encoding($output, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {
		//
    //$output = mb_convert_encoding($content, 'UTF-8', 'pass'); 
		//}
		
		//another one
		//https://github.com/neitanod/forceutf8
	}

}
