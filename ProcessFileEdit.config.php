<?php

/**
 * File Editor settings
 *
 */

class ProcessFileEditConfig extends ModuleConfig {

	public function __construct() {

		$this->add(array(

			/*
			array(
				'name'        => 'dirPath',
				'type'        => 'text',
				'label'       => $this->_('Directory Path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'notes'       => $this->_('WARNING: Do NOT set this to the root of file system.'),
				'required'    => true,
				'value'       => $this->config->paths->site,
			),
			*/

			array(
				'name'        => 'dirPath',
				'type'        => 'radios',
				'label'       => $this->_('Directory Path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'required'    => true,
				'options'     => array(
					$this->config->paths->root => $this->config->paths->root,
					$this->config->paths->site => $this->config->paths->site,
					$this->config->paths->templates => $this->config->paths->templates,
					$this->config->paths->siteModules => $this->config->paths->siteModules,
				),
				'value'       => $this->config->paths->site,
			),

			/*array(
				'name'        => 'defaultFile',
				'type'        => 'text',
				'label'       => $this->_('Default File'),
				'description' => $this->_('Path to the file you want the editor to show by default. Relative to the directory path above.'),
				'required'    => true,
				'value'       => "config.php",
			),*/

			array(
				'name'        => 'extensionsFilter',
				'type'        => 'text',
				'label'       => $this->_('Extensions Filter'),
				'description' => $this->_('Comma separated list of extensions to filter files by.'),
				'required'    => true,
				'value'       => "php,module,js,css",
				'notes'       => $this->_('Example: php,module,js,css.'),
			),

			array(
				'name'        => 'extFilter',
				'type'        => 'checkbox',
				'label'       => $this->_('Include or exclude extensions'),
				'description' => $this->_('Check this to exclude the extensions defined above, leave unchecked to include them.'),
				'required'    => false,
			),

			array(
				'name'        => 'editorHeight',
				'type'        => 'text',
				'label'       => $this->_('Editor Height'),
				'description' => $this->_('Set the height of the editor.'),
				'notes'       => $this->_('Default is "auto", can be any height like "450px".'),
				'required'    => false,
				'value'       => "auto",
			),

		));
	}
}
