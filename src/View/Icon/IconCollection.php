<?php

namespace Tools\View\Icon;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Inflector;
use RuntimeException;

class IconCollection {

	use InstanceConfigTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected $_defaultConfig = [];

	/**
	 * @var string
	 */
	protected $defaultSet;

	/**
	 * @var array<string, \Tools\View\Icon\IconInterface>
	 */
	protected $iconSets = [];

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		/** @var array<class-string<\Tools\View\Icon\IconInterface>> $sets */
		$sets = $config['sets'] ?? [];
		unset($config['sets']);

		foreach ($sets as $set => $className) {
			$iconConfig = $config['config'][$set] ?? [];
			$iconConfig += $config;
			$this->iconSets[$set] = new $className($iconConfig);
		}

		$key = array_key_first($sets);
		if (!$key) {
			throw new RuntimeException('No set defined for icon collection, at least one is required.');
		}

		$this->defaultSet = $key;

		$this->setConfig($config);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function names(): array {
		$names = [];
		foreach ($this->iconSets as $name => $set) {
			$path = $this->_config['config'][$name]['path'] ?? null;
			if ($path === null) {
				continue;
			}
			if (!file_exists($path)) {
				throw new RuntimeException('Cannot find file path `' . $path . '` for icon set `' . $name . '`');
			}

			$iconNames = $set->names($path);
			$names[$name] = $iconNames;
		}

		ksort($names);

		return $names;
	}

	/**
	 * Icons using the default namespace or an already prefixed one.
	 *
	 * @param string $icon Icon name, prefixed for non default namespace
	 * @param array<string, mixed> $options :
	 * - translate, title, ...
	 * @param array<string, mixed> $attributes :
	 * - class, ...
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []): string {
		if (isset($this->_config['map'][$icon])) {
			$icon = $this->_config['map'][$icon];
		}

		$separator = $this->_config['separator'];
		$separatorPos = strpos($icon, $separator);
		if ($separatorPos !== false) {
			[$set, $icon] = explode($separator, $icon, 2);
		} else {
			$set = $this->defaultSet;
		}

		if (!isset($this->iconSets[$set])) {
			throw new RuntimeException('No such icon namespace: `' . $set . '`.');
		}

		$options += $this->_config;
		if (!isset($options['title']) || $options['title'] !== false) {
			$titleField = !isset($options['title']) || $options['title'] === true ? 'title' : $options['title'];
			if (!isset($attributes[$titleField])) {
				$attributes[$titleField] = ucwords(Inflector::humanize(Inflector::underscore($icon)));
			}
		}
		if (!isset($options['translate']) || $options['translate'] !== false) {
			$attributes['title'] = __($attributes['title']);
		}

		return $this->iconSets[$set]->render($icon, $options, $attributes);
	}

}
