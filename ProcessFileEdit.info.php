<?php

$info = array(
	'title'   => 'Files Editor',
	'summary' => _('Edit files'),
	'version' => '1.7.5',
	'author'  => 'Florea Banus George, Matja&#382; Poto&#269;nik, Roland Toth',
	'icon'    => 'pencil-square-o',
	'href'    => 'https://github.com/matjazpotocnik/ProcessFileEdit/',
	'requires'  => 'ProcessWire>=2.6.1, PHP>=5.3.8',
	'permission'  => 'file-editor',
	'permissions' => array(
		'file-editor' => _('Edit Files (recommended for superuser only)')
	),
	'page' => array(
		'name'   => 'file-editor',
		'parent' => 'setup',
		'title'  => 'Files Editor'
	),
);
