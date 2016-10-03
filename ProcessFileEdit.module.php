<?php namespace ProcessWire;

/**
 * File Editor Module
 *
 * A module for editing files in the admin area.
 *
 * @author Florea Banus George
 *
 * ProcessWire 3.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2
 *
 * http://processwire.com
 *
 */

class ProcessFileEdit extends Process {

	public function ___execute() {
		$moduleRoot = $this->config->urls->siteModules . "ProcessFileEdit";
		$newfile    = $this->dirPath . $this->input->get->path;
		$file       = ($this->input->get->path) ? $newfile : $this->dirPath . $this->defaultFile;
		$fileHandle = fopen($file, "r");
		$pathinfo   = pathinfo($file);
		$ext        = $pathinfo["extension"];

		if($ext == 'php' || $ext == 'module') { $mode = "application/x-httpd-php"; }
		else if($ext == 'js')   { $mode = "text/javascript"; }
		else if($ext == 'html') { $mode = "text/html";  }
		else if($ext == 'css')  { $mode = "text/css";   }
		else                    { $mode = "text/plain"; }

		$editedFile = $this->input->get->path ? "<strong>Editing</strong> <em>{$this->input->get->path}</em>" : "<strong>Editing</strong> <em>{$this->defaultFile}</em>";
		$out  = "<div class='fe-editor'>{$editedFile}<hr>";
		$out .= "<link href='{$moduleRoot}/codemirror/lib/codemirror.css' rel='stylesheet' type='text/css'>";
		$out .= "<form method='post'>";
		$out .= "<textarea rows='10' cols='60' name='file_edit' class='cm' style='display: none;'>";
		$out .= htmlspecialchars(fread($fileHandle, filesize($file)));
		$out .= "</textarea><br>";
		$out .= "<input type='submit' name='saveFile' value='Save'>";
		$out .= "</form></div>";

		$out .= "<div class='fe-file-tree'>";
		$extensions = explode(',',$this->extensionsFilter);
		$out .= $this->php_file_tree($this->dirPath, "[link]", $extensions);
		$out .= "</div>";

		$out .= "
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
			 		mode: '{$mode}',
			 		indentUnit: 4,
			 		indentWithTabs: true,
			 	});
			 });
			</script>
			<style>
			.CodeMirror{
				height: {$this->editorHeight};
			}
			</style>
		";
		if ($this->input->post->saveFile)
		{
			$openFile = fopen($file, "w");
			$output   = $this->input->post->file_edit;
			fwrite($openFile, $output);
			fclose($openFile);

			$queryString = ($this->input->get->path) ? "?path=".$this->input->get->path : "";
			wire("session")->redirect($this->config->urls->httpRoot . "processwire/setup/" . $this->page->name . "/" . $queryString);
		}
		$out .= "<div style='clear:both;'></div>";
		return $out;
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
					if($this->extFilter == true)
					{
						if( in_array($ext, $extensions) ) unset($file[$key]);
					}
					else if ($this->extFilter == false)
					{
						if( !in_array($ext, $extensions) ) unset($file[$key]);
					}
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
