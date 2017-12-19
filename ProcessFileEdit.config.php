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
	 *
	 */
	protected static function scanForSubdirs($path, &$options) {
		// Ignore ./, ../ and .<folder>/ paths...
		$files = array_diff(@scandir($path), array('.', '..'));
		foreach ($files as $f) {
			if (is_dir("$path$f") && (strpos($f, '.') !== 0)) {
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
				'label'       => $this->_('Directory path'),
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
				'label'       => $this->_('Line endings'),
				'description' => $this->_('Type of line ending to use when saving.'),
				'columnWidth' => 25,
				'required'    => true,
				'options'     => array(
					'auto'          => 'Auto detect',
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
				'label'       => $this->_('Extensions filter'),
				'description' => $this->_('Comma separated list of extensions to filter files by. Example: "php,module,js,css".'),
				'columnWidth' => 25,
				'required'    => true,
				'value'       => 'php,module,js,css',
			),

			array(
				'name'        => 'extFilter',
				'type'        => 'select',
				'label'       => $this->_('Include or exclude extensions'),
				'description' => $this->_('Select to include or exclude files with the extensions defined above.'),
				'columnWidth' => 25,
				'required'    => true,
				'options'     => array(
					'0'             => $this->_('Include files with named extensions'),
					'1'             => $this->_('Exclude files with named extensions'),
				),
				'value'       => '0',
			),

			array(
				'name'        => 'dotFilesExclusion',
				'type'        => 'checkbox',
				'label'       => $this->_('Dotfiles exclusion'),
				'description' => $this->_('Check to exclude files and folders starting with dot.'),
				'columnWidth' => 25,
				'required'    => false,
				'value'       => '0',
			),

			array(
				'name'        => 'backupExtension',
				'type'        => 'text',
				'label'       => $this->_('Backup extension'),
				'description' => $this->_('Extension to use when backing up edited file. Leave empty for no backup.'),
				'columnWidth' => 25,
				'required'    => false,
				'value'       => '',
			),

			array(
				'name'        => 'editorHeight',
				'type'        => 'text',
				'label'       => $this->_('Editor height'),
				'description' => $this->_('Set the height of the editor. Default is "auto", can be any height like "450px".'),
				'columnWidth' => 25,
				'required'    => false,
				'value'       => 'auto',
			),

			array(
				'name'        => 'lineWrapping',
				'type'        => 'checkbox',
				'label'       => $this->_('Editor Line Wrapping'),
				'description' => $this->_('Make long lines wrap. Default is on.'),
				'columnWidth' => 25,
				'required'    => false,
				'value'       => '1',
			),

			array(
				'name'        => 'theme',
				'type'        => 'select',
				'label'       => $this->_('Codemirror theme'),
				'description' => $this->_('Select the theme used for editor, see **[demo](https://codemirror.net/demo/theme.html)**.'),
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
