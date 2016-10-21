<?php namespace ProcessWire;

/**
 *
 * File Editor settings
 *
 *
 */

class ProcessFileEditConfig extends ModuleConfig {

	public function __construct() {

		$this->add(array(

			// Text field: default directory
			array(
				'name'        => 'dirPath',
				'type'        => 'text',
				'label'       => $this->_('Directory Path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'notes'       => $this->_('WARNING: Be carefull! Do NOT set this to the root of file system.'),
				'required'    => true,
				'value'       => $this->config->paths->site,
			),
			// Text field: default file
			array(
				'name'        => 'defaultFile',
				'type'        => 'text',
				'label'       => $this->_('Default File'),
				'description' => $this->_('Path to the file you want the editor to show by default. Relative to the directory path above.'),
				'required'    => true,
				'value'       => "config.php",
			),
			// Text field: default extensions
			array(
				'name'        => 'extensionsFilter',
				'type'        => 'text',
				'label'       => $this->_('Extensions Filter'),
				'description' => $this->_('Comma separated list of extensions to filter files by.'),
				'required'    => true,
				'value'       => "js,css,php,module",
			),
			// Checkbox field: default editor height
			array(
				'name'        => 'extFilter',
				'type'        => 'checkbox',
				'label'       => $this->_('Include or exclude extensions'),
				'description' => $this->_('Check this to exclude the extensions defined above, leave unchecked to include them.'),
				'required'    => false,
			),
			// Text field: default editor height
			array(
				'name'        => 'editorHeight',
				'type'        => 'text',
				'label'       => $this->_('Editor Height'),
				'description' => $this->_('Set the height of the editor.'),
				'required'    => false, //MP changed to false
				'value'       => "500px",
			),
		));
	}
}
