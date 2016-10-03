<?php namespace ProcessWire;

$info = array(
	'title'   => 'File Editor',
	'summary' => 'A file editor.',
	'version' => 1.2,
	'author'  => 'fbg',
	'icon'    => 'file-o',
	'href'    => 'https://github.com/f-b-g-m/ProcessFileEdit/',
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
