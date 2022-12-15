<?php

namespace Tools\View\Icon;

use Tools\View\Icon\Collector\BootstrapIconCollector;

class BootstrapIcon extends AbstractIcon {

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}"{{attributes}}></span>',
		];

		parent::__construct($config);
	}

	/**
	 * @return array<string>
	 */
	public function names(): array {
		$path = $this->path();

		return BootstrapIconCollector::collect($path);
	}

	/**
	 * @param string $icon
	 * @param array $options
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []): string {
		if (!empty($this->config['attributes'])) {
			$attributes += $this->config['attributes'];
		}

		$options['class'] = 'bi bi-' . $icon;
		if (!empty($attributes['class'])) {
			$options['class'] .= ' ' . $attributes['class'];
		}
		$options['attributes'] = $this->template->formatAttributes($attributes, ['class']);

		return $this->template->format('icon', $options);
	}

}
