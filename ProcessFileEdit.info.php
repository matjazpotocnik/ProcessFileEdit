<?php namespace ProcessWire;

$info = array(
	'title' => 'Files Editor',
	'summary' => _('Edit files'),
	'version' => '2.0.1',
	'author' => 'Florea Banus George, Matja&#382; Poto&#269;nik, Roland Toth',
	'icon' => 'pencil-square-o',
	'href' => 'https://github.com/matjazpotocnik/ProcessFileEdit/',
	'requires' => 'ProcessWire>=3.0.0, PHP>=7.1.0',
	'permission' => 'file-editor',
	'permissions' => [
		'file-editor' => _('Edit Files (recommended for superuser only)')
	],
	'page' => [
		'name' => 'file-editor',
		'parent' => 'setup',
		'title' => 'Files Editor'
	],
);
