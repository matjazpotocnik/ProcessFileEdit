<?php namespace ProcessWire;

/**
 * File Editor Module
 *
 * A module for editing file in the admin area.
 *
 * Copyright 2016 by fbg
 *
 * ProcessWire 3.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

class ProcessFileEdit extends Process {

	/**
	 * This is an optional initialization function called before any execute functions.
	 *
	 * If you don't need to do any initialization common to every execution of this module,
	 * you can simply remove this init() method.
	 *
	 */
	public function init() {
		parent::init(); // always remember to call the parent init
	}

	/**
	 * This function is executed when a page with your Process assigned is accessed.
 	 *
	 * This can be seen as your main or index function. You'll probably want to replace
	 * everything in this function.
	 *
	 */
	public function ___execute() {

		// greetingType and greeting are automatically populated to this module
		// and they were defined in ProcessHello.config.php

		$moduleRoot = $this->config->urls->siteModules . "ProcessFileEdit";
		$newfile    = $this->config->paths->site . $this->input->get->path;
		$file       = ($this->input->get->path) ? $newfile : $this->defaultFile;
		$fContent   = file_get_contents($file);
		$pathinfo   = pathinfo($file);
		$ext        = $pathinfo["extension"];

		if($ext == 'php')      { $mode = "application/x-httpd-php"; }
		else if($ext == 'css') { $mode = "text/css";                }
		else                   { $mode = "text/javascript";         }

		$out  = "";
		$out .= "<link href='{$moduleRoot}/codemirror/lib/codemirror.css' rel='stylesheet' type='text/css'>";
		$out .= "<link href='{$moduleRoot}/phpFileTree/styles/default/default.css' rel='stylesheet' type='text/css'>";
		$out .= "<div style='float:left;'><form method='post'>";
		$out .= "<textarea rows='10' cols='60' name='file_edit' class='cm' style='display: none;'>$fContent</textarea><br>";
		$out .= "<input type='submit' name='saveFile' value='Save'>";
		$out .= "</form></div>";
		$out .= "
			<script src='{$moduleRoot}/phpFileTree/php_file_tree_jquery.js'></script>
			<script src='{$moduleRoot}/codemirror/lib/codemirror.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/php/php.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/xml/xml.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/javascript/javascript.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/clike/clike.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/css/css.js'></script>
			<script src='{$moduleRoot}/codemirror/mode/htmlmixed/htmlmixed.js'></script>
			<script>
			$(document).ready(function(){
			 	var code = $('.cm')[0];
			 	var editor = CodeMirror.fromTextArea(code, {
			 		lineNumbers: true,
			 		mode: '$mode',
			 		indentUnit: 4,
			 		indentWithTabs: true,
			 	});
			 	editor.setSize(960, 500);
			 });
			</script>
		";

		$out .= "<div style='float:left;margin-left:40px;'>";
		$extensions = explode(',',$this->extensionsFilter);
		$out .= $this->php_file_tree($this->dirPath, "[link]", $extensions);
		$out .= "</div>";
		if (isset($_POST['saveFile']))
		{
			$openFile = fopen($file, "a");
			ftruncate($openFile, 0);
			$output = $this->input->post->file_edit;
			fwrite($openFile, $output);
			fclose($openFile);

			$queryString = ($this->input->get->path) ? "?path=".$this->input->get->path : "";
			wire("session")->redirect("http://localhost/pw/processwire/setup/file-editor/".$queryString);
		}
		$out .= "<div style='clear:both;'></div>";
		return $out;
	}

	/**
	 * Called only when your module is installed
	 *
	 * If you don't need anything here, you can simply remove this method.
	 *
	 */
	public function ___install() {
		parent::___install(); // always remember to call parent method
	}

	/**
	 * Called only when your module is uninstalled
	 *
	 * This should return the site to the same state it was in before the module was installed.
	 *
	 * If you don't need anything here, you can simply remove this method.
	 *
	 */
	public function ___uninstall() {
		parent::___uninstall(); // always remember to call parent method
	}




	public function php_file_tree($directory, $return_link, $extensions = array()) {
		// Generates a valid XHTML list of all directories, sub-directories, and files in $directory
		// Remove trailing slash
		if( substr($directory, -1) == "/" ) $directory = substr($directory, 0, strlen($directory) - 1);
		$code .= $this->php_file_tree_dir($directory, $return_link, $extensions);
		return $code;
	}

	public function php_file_tree_dir($directory, $return_link, $extensions = array(), $first_call = true, $parent = null) {
		// Recursive function called by php_file_tree() to list directories/files

		// Get and sort directories/files
		if( function_exists("scandir") ) $file = scandir($directory); else $file = $this->php4_scandir($directory);
		natcasesort($file);
		// Make directories first
		$files = $dirs = array();
		foreach($file as $this_file) {
			if( is_dir("$directory/$this_file" ) ) $dirs[] = $this_file; else $files[] = $this_file;
		}
		$file = array_merge($dirs, $files);

		// Filter unwanted extensions
		if( !empty($extensions) ) {
			foreach( array_keys($file) as $key ) {
				if( !is_dir("$directory/$file[$key]") ) {
					$ext = substr($file[$key], strrpos($file[$key], ".") + 1);
					if( !in_array($ext, $extensions) ) unset($file[$key]);
				}
			}
		}

		if( count($file) > 2 ) { // Use 2 instead of 0 to account for . and .. "directories"
			$php_file_tree = "<ul";
			if( $first_call ) { $php_file_tree .= " class=\"php-file-tree\""; $first_call = false; }
			$php_file_tree .= ">";
			foreach( $file as $this_file ) {
				if( $this_file != "." && $this_file != ".." ) {
					if( is_dir("$directory/$this_file") ) {
						// Directory
						$php_file_tree .= "<li class=\"pft-directory\"><a href=\"#\">" . htmlspecialchars($this_file) . "</a>";
						$php_file_tree .= $this->php_file_tree_dir("$directory/$this_file", $return_link ,$extensions, false, $parent.'/'.$this_file);
						$php_file_tree .= "</li>";
					} else {
						// File
						// Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
						$ext = "ext-" . substr($this_file, strrpos($this_file, ".") + 1);
						$link = str_replace("[link]", "$parent/" . urlencode($this_file), $return_link);
						$php_file_tree .= "<li class=\"pft-file " . strtolower($ext) . "\"><a href=\"?path=$link\">" . htmlspecialchars($this_file) . "</a></li>";
					}
				}
			}
			$php_file_tree .= "</ul>";
		}
		return $php_file_tree;
	}

	// For PHP4 compatibility
	public function php4_scandir($dir) {
		$dh  = opendir($dir);
		while( false !== ($filename = readdir($dh)) ) {
		    $files[] = $filename;
		}
		sort($files);
		return($files);
	}

}
