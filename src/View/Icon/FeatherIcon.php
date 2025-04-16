<?php

namespace Tools\View\Icon;

use Tools\View\Icon\Collector\FeatherIconCollector;

/**
 * @deprecated Use Templating plugin icon classes instead.
 */
class FeatherIcon extends AbstractIcon {

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span data-feather="{{name}}"{{attributes}}></span>',
		];

		parent::__construct($config);
	}

	/**
	 * @return array<string>
	 */
	public function names(): array {
		$path = $this->path();

		return FeatherIconCollector::collect($path);
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
		$options['attributes'] = $this->template->formatAttributes($attributes);

		return $this->template->format('icon', $options);
	}

}
