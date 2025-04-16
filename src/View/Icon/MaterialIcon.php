<?php

namespace Tools\View\Icon;

use Tools\View\Icon\Collector\MaterialIconCollector;

/**
 * @deprecated Use Templating plugin icon classes instead.
 */
class MaterialIcon extends AbstractIcon {

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}"{{attributes}}>{{name}}</span>',
			'namespace' => 'material-icons',
		];

		parent::__construct($config);
	}

	/**
	 * @return array<string>
	 */
	public function names(): array {
		$path = $this->path();

		return MaterialIconCollector::collect($path);
	}

	/**
	 * @param string $icon
	 * @param array<string, mixed> $options
	 * @param array<string, mixed> $attributes
	 *
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []): string {
		if (!empty($this->config['attributes'])) {
			$attributes += $this->config['attributes'];
		}

		$options['name'] = $icon;
		$options['class'] = $this->config['namespace'];
		if (!empty($attributes['class'])) {
			$options['class'] .= ' ' . $attributes['class'];
		}
		$options['attributes'] = $this->template->formatAttributes($attributes, ['class']);

		return $this->template->format('icon', $options);
	}

}
