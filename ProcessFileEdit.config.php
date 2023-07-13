<?php namespace ProcessWire;

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
		$files = array_diff(@scandir($path), ['.', '..']);
		foreach($files as $file) {
			$f = "$path$file";
			if(is_dir($f) && (strpos($file, '.') !== 0)) {
				$options[$f] = $f;
				self::scanForSubdirs($f . '/', $options);
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
		$rootPath = rtrim($paths->root, '/');
		$sitePath = rtrim($paths->site, '/');
		$siteModulesPath = rtrim($paths->siteModules, '/');
		$templatesPath = rtrim($paths->templates, '/');
		$options = [
			$rootPath => $rootPath,
			$sitePath => $sitePath,
			$siteModulesPath => $siteModulesPath,
			$templatesPath => $templatesPath,
		];

		// add directories under /site/templates to the options
		self::scanForSubdirs($paths->templates, $options);

		return $options;
	}

	public function __construct() {

		$this->add([

			[
				'name'        => 'dirPath',
				'type'        => 'select',
				'label'       => $this->_('Directory path'),
				'description' => $this->_('Path to the directory from which to get files.'),
				'columnWidth' => 34,
				'required'    => true,
				'options'     => self::getDirPathOptions($this->config->paths),
				'value'       => rtrim($this->config->paths->site, '/'),
			],

			[
				'name'        => 'extensionsFilter',
				'type'        => 'text',
				'label'       => $this->_('Extensions filter'),
				'description' => $this->_('Comma separated list of extensions to filter files by. Example: "php,module,js,css".'),
				'columnWidth' => 33,
				'required'    => true,
				'value'       => 'php,module,js,css',
			],

			[
				'name'        => 'extFilter',
				'type'        => 'select',
				'label'       => $this->_('Include or exclude extensions'),
				'description' => $this->_('Select to include or exclude files with the defined extensions.'),
				'columnWidth' => 33,
				'required'    => true,
				'options'     => [
					'0'             => $this->_('Include files with named extensions'),
					'1'             => $this->_('Exclude files with named extensions'),
				],
				'value'       => '0',
			],

			[
				'name'        => 'lineEndings',
				'type'        => 'select',
				'label'       => $this->_('Line endings'),
				'description' => $this->_('Type of line ending to use when saving.'),
				'columnWidth' => 34,
				'required'    => true,
				'options'     => [
					'auto'          => 'Auto detect',
					'win'           => 'Windows (\r\n)',
					'mac'           => 'Mac (\r)',
					'nix'           => 'Linux (\n)',
					'none'          => 'none',
				],
				'value'       => 'auto',
			],

			[
				'name'        => 'dotFilesExclusion',
				'type'        => 'checkbox',
				'label'       => $this->_('Dotfiles exclusion'),
				'description' => $this->_('Check to exclude files and folders starting with dot.'),
				'columnWidth' => 33,
				'required'    => false,
				'value'       => '0',
			],

			[
				'name'        => 'backupExtension',
				'type'        => 'text',
				'label'       => $this->_('Backup extension'),
				'description' => $this->_('Extension to use when backing up edited file. Leave empty for no backup.'),
				'columnWidth' => 33,
				'required'    => false,
				'value'       => '',
			],

			[
				'name'        => 'editorHeight',
				'type'        => 'text',
				'label'       => $this->_('Editor height'),
				'description' => $this->_('Set the height of the editor. Default is "auto", can be any height like "450px".'),
				'columnWidth' => 34,
				'required'    => false,
				'value'       => 'auto',
			],

			[
				'name'        => 'lineWrapping',
				'type'        => 'checkbox',
				'label'       => $this->_('Editor Line Wrapping'),
				'description' => $this->_('Make long lines wrap. Default is on.'),
				'columnWidth' => 33,
				'required'    => false,
				'value'       => '1',
			],

			[
				'name'        => 'theme',
				'type'        => 'select',
				'label'       => $this->_('Codemirror theme'),
				'description' => $this->_('Select the theme used for editor, see **[demo](https://codemirror.net/demo/theme.html)**.'),
				'columnWidth' => 33,
				'required'    => true,
				'options'     => [
					'default' => 'default',
					'3024-day' => '3024-day',
					'3024-night' => '3024-night',
					'abbott' => 'abbott',
					'abcdef' => 'abcdef',
					'ambiance-mobile' => 'ambiance-mobile',
					'ambiance' => 'ambiance',
					'ayu-dark' => 'ayu-dark',
					'ayu-mirage' => 'ayu-mirage',
					'base16-dark' => 'base16-dark',
					'base16-light' => 'base16-light',
					'bespin' => 'bespin',
					'blackboard' => 'blackboard',
					'cobalt' => 'cobalt',
					'colorforth' => 'colorforth',
					'darcula' => 'darcula',
					'dracula' => 'dracula',
					'duotone-dark' => 'duotone-dark',
					'duotone-light' => 'duotone-light',
					'eclipse' => 'eclipse',
					'elegant' => 'elegant',
					'erlang-dark' => 'erlang-dark',
					'gruvbox-dark' => 'gruvbox-dark',
					'hopscotch' => 'hopscotch',
					'icecoder' => 'icecoder',
					'idea' => 'idea',
					'isotope' => 'isotope',
					'juejin' => 'juejin',
					'lesser-dark' => 'lesser-dark',
					'liquibyte' => 'liquibyte',
					'lucario' => 'lucario',
					'material-darker' => 'material-darker',
					'material-ocean' => 'material-ocean',
					'material-palenight' => 'material-palenight',
					'material' => 'material',
					'mbo' => 'mbo',
					'mdn-like' => 'mdn-like',
					'midnight' => 'midnight',
					'monokai' => 'monokai',
					'moxer' => 'moxer',
					'neat' => 'neat',
					'neo' => 'neo',
					'night' => 'night',
					'nord' => 'nord',
					'oceanic-next' => 'oceanic-next',
					'panda-syntax' => 'panda-syntax',
					'paraiso-dark' => 'paraiso-dark',
					'paraiso-light' => 'paraiso-light',
					'pastel-on-dark' => 'pastel-on-dark',
					'railscasts' => 'railscasts',
					'rubyblue' => 'rubyblue',
					'seti' => 'seti',
					'shadowfox' => 'shadowfox',
					'solarized' => 'solarized',
					'ssms' => 'ssms',
					'the-matrix' => 'the-matrix',
					'tomorrow-night-bright' => 'tomorrow-night-bright',
					'tomorrow-night-eighties' => 'tomorrow-night-eighties',
					'ttcn' => 'ttcn',
					'twilight' => 'twilight',
					'vibrant-ink' => 'vibrant-ink',
					'xq-dark' => 'xq-dark',
					'xq-light' => 'xq-light',
					'yeti' => 'yeti',
					'yonce' => 'yonce',
					'zenburn' => 'zenburn',
				],
				'value'       => 'default',
			],

		]);
	}

}
