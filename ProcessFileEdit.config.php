<?php namespace ProcessWire;

/**
 * Configure the Hello World module
 *
 * This type of configuration method requires ProcessWire 2.5.5 or newer.
 * For backwards compatibility with older versions of PW, you'll want to
 * instead want to look into the getModuleConfigInputfields() method, which
 * is specified with the .module file. So we are assuming you only need to
 * support PW 2.5.5 or newer here.
 *
 * For more about configuration methods, see here:
 * http://processwire.com/blog/posts/new-module-configuration-options/
 *
 *
 */

class ProcessFileEditConfig extends ModuleConfig {

	public function __construct() {

		$this->add(array(

			// Text field: default file
			array(
				'name'        => 'defaultFile', // name of field
				'type'        => 'text', // type of field (any Inputfield module name)
				'label'       => $this->_('Default File'), // field label
				'description' => $this->_('Path to the file you want the editor to show by default.'),
				'required'    => true,
				'value'       => $this->config->paths->site."config.php", // default value
			),
			// Text field: default directory
			array(
				'name'        => 'dirPath', // name of field
				'type'        => 'text', // type of field (any Inputfield module name)
				'label'       => $this->_('Directory Path'), // field label
				'description' => $this->_('Path to the directory from which to get files.'),
				'required'    => true,
				'value'       => $this->config->paths->site, // default value
			),
			// Text field: default extensions
			array(
				'name'        => 'extensionsFilter', // name of field
				'type'        => 'text', // type of field (any Inputfield module name)
				'label'       => $this->_('Extensions Filter'), // field label
				'description' => $this->_('Comma separated list of extensions to filter files by.'),
				'required'    => true,
				'value'       => "js,css,php", // default value
			),
		));
	}
}
