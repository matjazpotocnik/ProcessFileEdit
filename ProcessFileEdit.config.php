<?php

/**
 * File Editor settings
 *
 */
class ProcessFileEditConfig extends ModuleConfig {

	public function __construct() {

		$this->add(array(

			array(
				'name'        => 'dirPath',
				'type'        => 'select',
				'label'       => $this->_('Directory Path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'columnWidth' => 50,
				'required'    => true,
				'options'     => array(
					$this->config->paths->root => $this->config->paths->root,
					$this->config->paths->site => $this->config->paths->site,
					$this->config->paths->templates => $this->config->paths->templates,
					$this->config->paths->siteModules => $this->config->paths->siteModules,
				),
				'value'       => $this->config->paths->site,
			),

			array(
				'name'        => 'encoding',
				'type'        => 'select',
				'label'       => $this->_('File encoding'),
				'description' => $this->_('In case file names are garbled, try different encoding.'),
				'columnWidth' => 30,
				'required'    => true,
				'options'     => array(
					'auto'         => 'Auto detect',
					'Windows-1250' => 'Windows-1250',
					'Windows-1252' => 'Windows-1252',
					'ISO-8859-2'   => 'ISO-8859-2',
					'urldecode'    => 'PHP\'s urldecode',
					'none'         => 'none',
				),
				'value'       => 'auto',
			),

			array(
				'name'        => 'lineEndings',
				'type'        => 'select',
				'label'       => $this->_('Line Endings'),
				'description' => $this->_('Type of line ending to use when saving.'),
				'notes'       => $this->_('Default is Linux (\n).'),
				'columnWidth' => 20,
				'required'    => true,
				'options'     => array(
					'win'           => 'Windows (\r\n)',
					'mac'           => 'Mac (\r)',
					'nix'           => 'Linux (\n)',
				),
				'value'       => 'nix',
			),

			array(
				'name'        => 'extensionsFilter',
				'type'        => 'text',
				'label'       => $this->_('Extensions Filter'),
				'description' => $this->_('Comma separated list of extensions to filter files by. Example: "php,module,js,css".'),
				'columnWidth' => 50,
				'required'    => true,
				'value'       => 'php,module,js,css',
			),

			array(
				'name'        => 'extFilter',
				//'type'        => 'radios',
				'type'        => 'select',
				'label'       => $this->_('Include or exclude extensions'),
				'description' => $this->_('Select to include or exclude files with the extensions defined above.'),
				'columnWidth' => 50,
				'required'    => true,
				'options'     => array(
					'0' 				=> $this->_('Include files with named extensions'),
					'1' 				=> $this->_('Exclude files with named extensions'),
				),
				'value'       => '0',
			),

			array(
				'name'        => 'editorHeight',
				'type'        => 'text',
				'label'       => $this->_('Editor Height'),
				'description' => $this->_('Set the height of the editor. Default is "auto", can be any height like "450px".'),
				'columnWidth' => 50,
				'required'    => false,
				'value'       => 'auto',
			),

			array(
				'name'        => 'theme',
				'type'        => 'select',
				'label'       => $this->_('Codemirror theme'),
				'description' => $this->_('Select the theme used for editor **[demo](https://codemirror.net/demo/theme.html)**.'),
				'columnWidth' => 50,
				'required'    => true,
				'options'     => array(
					'default'                 => 'default',
					'3024-day'                => '3024-day',
					'3024-night'              => '3024-night',
					'abcdef'                  => 'abcdef',
					'ambiance'                => 'ambiance',
					'base16-dark'             => 'base16-dark',
					'base16-light'            => 'base16-light',
					'bespin'                  => 'bespin',
					'blackboard'              => 'blackboard',
					'cobalt'                  => 'cobalt',
					'colorforth'              => 'colorforth',
					'dracula'                 => 'dracula',
					'duotone-dark'            => 'duotone-dark',
					'duotone-light'           => 'duotone-light',
					'eclipse'                 => 'eclipse',
					'elegant'                 => 'elegant',
					'erlang-dark'             => 'erlang-dark',
					'hopscotch'               => 'hopscotch',
					'icecoder'                => 'icecoder',
					'isotope'                 => 'isotope',
					'lesser-dark'             => 'lesser-dark',
					'liquibyte'               => 'liquibyte',
					'material'                => 'material',
					'mbo'                     => 'mbo',
					'mdn-like'                => 'mdn-like',
					'midnight'                => 'midnight',
					'monokai'                 => 'monokai',
					'neat'                    => 'neat',
					'neo'                     => 'neo',
					'night'                   => 'night',
					'panda-syntax'            => 'panda-syntax',
					'paraiso-dark'            => 'paraiso-dark',
					'paraiso-light'           => 'paraiso-light',
					'pastel-on-dark'          => 'pastel-on-dark',
					'railscasts'              => 'railscasts',
					'rubyblue'                => 'rubyblue',
					'seti'                    => 'seti',
					'solarized'               => 'solarized',
					'the-matrix'              => 'the-matrix',
					'tomorrow-night-bright'   => 'tomorrow-night-bright',
					'tomorrow-night-eighties' => 'tomorrow-night-eighties',
					'ttcn'                    => 'ttcn',
					'twilight'                => 'twilight',
					'vibrant-ink'             => 'vibrant-ink',
					'xq-dark'                 => 'xq-dark',
					'xq-light'                => 'xq-light',
					'yeti'                    => 'yeti',
					'zenburn'                 => 'zenburn',
				),
				'value'       => 'default',
			),

		));
	}

}
