<?php
App::uses('PhpReader', 'Configure');

/**
 * Provides an interface for registering and inserting
 * content into simple logic-less string templates.
 *
 * Used by several helpers to provide simple flexible templates
 * for generating HTML and other content.
 *
 * Backported from CakePHP3.0
 */
class StringTemplate {

	/**
	 * List of attributes that can be made compact.
	 *
	 * @var array
	 */
	protected $_compactAttributes = [
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize',
		'autoplay', 'controls', 'loop', 'muted', 'required', 'novalidate', 'formnovalidate'
	];

	/**
	 * The default templates this instance holds.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'attribute' => '{{name}}="{{value}}"',
		'compactAttribute' => '{{name}}="{{value}}"',
	];

	protected $_config;

	/**
	 * Contains the list of compiled templates
	 *
	 * @var array
	 */
	protected $_compiled = [];

	/**
	 * Constructor.
	 *
	 * @param array $config A set of templates to add.
	 */
	public function __construct(array $config = []) {
		$this->config($config);
	}

	/**
	 * StringTemplate::config()
	 *
	 * @param string|array|null $key The key to get/set, or a complete array of configs.
	 * @param mixed|null $value The value to set.
	 * @param bool $merge Whether to merge or overwrite existing config defaults to true.
	 * @return mixed Config value being read, or the whole array itself on write operations.
	 */
	public function config($key = null, $value = null, $merge = true) {
		if ($key === null) {
			return $this->_config;
		}
		if (is_array($key)) {
			if ($merge) {
				$this->_config = $key + $this->_defaultConfig;
			} else {
				$this->_config = $key;
			}
			return;
		}

		if (func_num_args() >= 2) {
			if ($value === null) {
				unset($this->_config[$key]);
			} else {
				$this->_config[$key] = $value;
			}
			return $this->_config;
		}
		if (!isset($this->_config[$key])) {
			return null;
		}
		return $this->_config[$key];
	}

	/**
	 * Registers a list of templates by name
	 *
	 * ### Example:
	 *
	 * {{{
	 * $templater->add([
	 *	'link' => '<a href="{{url}}">{{title}}</a>'
	 *	'button' => '<button>{{text}}</button>'
	 * ]);
	 * }}}
	 *
	 * @param array an associative list of named templates
	 * @return \Cake\View\StringTemplate same instance
	 */
	public function add(array $templates) {
		$this->config($templates);
		$this->_compiled = array_diff_key($this->_compiled, $templates);
		return $this;
	}

	/**
	 * Load a config file containing templates.
	 *
	 * Template files should define a `$config` variable containing
	 * all the templates to load. Loaded templates will be merged with existing
	 * templates.
	 *
	 * @param string $file The file to load
	 * @return void
	 */
	public function load($file) {
		$loader = new PhpReader();
		$templates = $loader->read($file);
		$this->add($templates);
	}

	/**
	 * Remove the named template.
	 *
	 * @param string $name The template to remove.
	 * @return void
	 */
	public function remove($name) {
		$this->config($name, null);
		unset($this->_compiled[$name]);
	}

	/**
	 * Returns an array containing the compiled template to be used with
	 * the sprintf function and a list of placeholder names that were found
	 * in the template in the order that they should be replaced.
	 *
	 * @param string $name The compiled template info
	 * @return array
	 */
	public function compile($name) {
		if (isset($this->_compiled[$name])) {
			return $this->_compiled[$name];
		}

		$template = $this->config($name);
		if ($template === null) {
			return $this->_compiled[$name] = [null, null];
		}

		preg_match_all('#\{\{(\w+)\}\}#', $template, $matches);
		return $this->_compiled[$name] = [
			str_replace($matches[0], '%s', $template),
			$matches[1]
		];
	}

	/**
	 * Format a template string with $data
	 *
	 * @param string $name The template name.
	 * @param array $data The data to insert.
	 * @return string
	 */
	public function format($name, array $data) {
		list($template, $placeholders) = $this->compile($name);
		if ($template === null) {
			return '';
		}
		$replace = [];
		foreach ($placeholders as $placeholder) {
			$replace[] = isset($data[$placeholder]) ? $data[$placeholder] : null;
		}
		return vsprintf($template, $replace);
	}

	/**
	 * Returns a space-delimited string with items of the $options array. If a key
	 * of $options array happens to be one of those listed
	 * in `StringTemplate::$_compactAttributes` and its value is one of:
	 *
	 * - '1' (string)
	 * - 1 (integer)
	 * - true (boolean)
	 * - 'true' (string)
	 *
	 * Then the value will be reset to be identical with key's name.
	 * If the value is not one of these 4, the parameter is not output.
	 *
	 * 'escape' is a special option in that it controls the conversion of
	 * attributes to their html-entity encoded equivalents. Set to false to disable html-encoding.
	 *
	 * If value for any option key is set to `null` or `false`, that option will be excluded from output.
	 *
	 * This method uses the 'attribute' and 'compactAttribute' templates. Each of
	 * these templates uses the `name` and `value` variables. You can modify these
	 * templates to change how attributes are formatted.
	 *
	 * @param array $options Array of options.
	 * @param null|array $exclude Array of options to be excluded, the options here will not be part of the return.
	 * @return string Composed attributes.
	 */
	public function formatAttributes($options, $exclude = null) {
		$insertBefore = ' ';
		$options = (array)$options + ['escape' => true];

		if (!is_array($exclude)) {
			$exclude = [];
		}

		$exclude = ['escape' => true, 'idPrefix' => true] + array_flip($exclude);
		$escape = $options['escape'];
		$attributes = [];

		foreach ($options as $key => $value) {
			if (!isset($exclude[$key]) && $value !== false && $value !== null) {
				$attributes[] = $this->_formatAttribute($key, $value, $escape);
			}
		}
		$out = trim(implode(' ', $attributes));
		return $out ? $insertBefore . $out : '';
	}

	/**
	 * Formats an individual attribute, and returns the string value of the composed attribute.
	 * Works with minimized attributes that have the same value as their name such as 'disabled' and 'checked'
	 *
	 * @param string $key The name of the attribute to create
	 * @param string|array $value The value of the attribute to create.
	 * @param bool $escape Define if the value must be escaped
	 * @return string The composed attribute.
	 */
	protected function _formatAttribute($key, $value, $escape = true) {
		if (is_array($value)) {
			$value = implode(' ', $value);
		}
		if (is_numeric($key)) {
			return $this->format('compactAttribute', [
				'name' => $value,
				'value' => $value
			]);
		}
		$truthy = [1, '1', true, 'true', $key];
		$isMinimized = in_array($key, $this->_compactAttributes);
		if ($isMinimized && in_array($value, $truthy, true)) {
			return $this->format('compactAttribute', [
				'name' => $key,
				'value' => $key
			]);
		}
		if ($isMinimized) {
			return '';
		}
		return $this->format('attribute', [
			'name' => $key,
			'value' => $escape ? h($value) : $value
		]);
	}

}
