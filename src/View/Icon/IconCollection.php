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
	protected array $_defaultConfig = [];

	/**
	 * @var string
	 */
	protected $defaultSet;

	/**
	 * @var array<string, \Tools\View\Icon\IconInterface>
	 */
	protected array $iconSets = [];

	/**
	 * @var array|null
	 */
	protected $names;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		/** @var array<class-string<\Tools\View\Icon\IconInterface>|array<string, mixed>> $sets */
		$sets = $config['sets'] ?? [];
		unset($config['sets']);

		foreach ($sets as $set => $setConfig) {
			if (is_string($setConfig)) {
				$setConfig = [
					'class' => $setConfig,
				];
			} else {
				if (empty($setConfig['class'])) {
					throw new RuntimeException('You must define a `class` for each icon set.');
				}
			}

			/** @var class-string<\Tools\View\Icon\IconInterface> $className */
			$className = $setConfig['class'];
			if (isset($config['attributes']) && isset($setConfig['attributes'])) {
				$setConfig['attributes'] += $config['attributes'];
			}
			$setConfig += $config;
			$this->iconSets[$set] = new $className($setConfig);
		}

		/** @var string|null $key */
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
		if ($this->names !== null) {
			return $this->names;
		}

		$names = [];
		foreach ($this->iconSets as $name => $set) {
			$iconNames = $set->names();
			$names[$name] = $iconNames;
		}

		ksort($names);
		$this->names = $names;

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
		$iconName = null;
		if (isset($this->_config['map'][$icon])) {
			$iconName = $icon;
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
			/** @var string $titleField */
			$titleField = !isset($options['title']) || $options['title'] === true ? 'title' : $options['title'];
			if (!isset($attributes[$titleField])) {
				$attributes[$titleField] = ucwords(Inflector::humanize(Inflector::underscore($iconName ?? $icon)));
			}
			if (!isset($options['translate']) || $options['translate'] !== false && isset($attributes[$titleField])) {
				$attributes[$titleField] = __($attributes[$titleField]);
			}
		}

		unset($options['attributes']);
		if ($this->getConfig('checkExistence') && !$this->exists($icon, $set)) {
			trigger_error(sprintf('Icon `%s` does not exist', $set . ':' . $icon), E_USER_WARNING);
		}

		return $this->iconSets[$set]->render($icon, $options, $attributes);
	}

	/**
	 * @param string $icon
	 * @param string $set
	 *
	 * @return bool
	 */
	protected function exists(string $icon, string $set): bool {
		$names = $this->names();

		return !empty($names[$set]) && in_array($icon, $names[$set], true);
	}

}
