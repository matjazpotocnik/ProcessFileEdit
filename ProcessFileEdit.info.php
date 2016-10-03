<?php namespace ProcessWire;

$info = array(
	'title'   => 'File Editor',
	'summary' => 'A file editor.',
	'version' => 1.1,
	'author'  => 'fbg',
	'icon'    => 'file-o',
	'href'    => 'http://modules.processwire.com/',
	'permission'  => 'file-editor',
	'permissions' => array(
		'file-editor' => 'Edit Files'
	),
	'page' => array(
		'name'   => 'file-editor',
		'parent' => 'setup',
		'title'  => 'File Editor'
	),
);
