<?php

/**
 * File Editor settings
 *
 */
class ProcessFileEditConfig extends ModuleConfig {



	/**
	 * Recursive function to extend starting directory selection to include template subdirs
	 *
	 * @param string $path starting directory
	 * @param array $options
	 * @return array $options
	 *
	 */
	protected static function scanForSubdirs($path, &$options) {
		// Ignore ./, ../ and .<folder>/ paths...
		$files = array_diff(@scandir($path), array('.', '..'));
		foreach ($files as $f) {
			if (is_dir("$path$f") && (0 !== strpos($f, '.'))) {
				$options["$path$f"] = "$path$f";
				self::scanForSubdirs("$path$f" . DIRECTORY_SEPARATOR, $options);
			}
		}
	}

	/**
	 * Create a list of directories from which to get files.
	 *
	 * @param string $paths
	 * @return array $options
	 *
	 */
	protected static function getDirPathOptions($paths) {
		// predefined paths
		$options = array(
			$paths->root => $paths->root,
			$paths->site => $paths->site,
			$paths->siteModules => $paths->siteModules,
			$paths->templates => $paths->templates,
		);

		// add directories under /site/templates
		if (function_exists('scandir')) {
			self::scanForSubdirs($paths->templates, $options);
		}

		return $options;
	}

	public function __construct() {

		$this->add(array(

			array(
				'name'        => 'dirPath',
				'type'        => 'select',
				'label'       => $this->_('Directory Path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'columnWidth' => 50,
				'required'    => true,
				'options'     => self::getDirPathOptions($this->config->paths),
				'value'       => $this->config->paths->site,
			),

			array(
				'name'        => 'encoding',
				'type'        => 'select',
				'label'       => $this->_('File encoding'),
				'description' => $this->_('Encoding used when saving file name.'),
				//'notes' => $this->_('In case file names are garbled, try different encoding.'),
				'columnWidth' => 25,
				'required'    => true,
				'options'     => array(
					'auto'          => 'Auto detect',
					'Windows-1250'  => 'Windows-1250',
					'Windows-1252'  => 'Windows-1252',
					'ISO-8859-2'    => 'ISO-8859-2',
					'urldecode'     => 'PHP\'s urldecode',
					'none'          => 'none',
				),
				'value'       => 'auto',
			),

			array(
				'name'        => 'lineEndings',
				'type'        => 'select',
				'label'       => $this->_('Line Endings'),
				'description' => $this->_('Type of line ending to use when saving.'),
				//'notes'       => $this->_('Default is Auto.'),
				'columnWidth' => 25,
				'required'    => true,
				'options'     => array(
					'auto'          => 'Auto',
					'win'           => 'Windows (\r\n)',
					'mac'           => 'Mac (\r)',
					'nix'           => 'Linux (\n)',
					'none'          => 'none',
				),
				'value'       => 'auto',
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
					'0'             => $this->_('Include files with named extensions'),
					'1'             => $this->_('Exclude files with named extensions'),
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
