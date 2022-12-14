<?php

namespace Tools\View\Icon;

use Cake\View\StringTemplate;
use Tools\View\Icon\Collector\BootstrapIconCollector;

class BootstrapIcon implements IconInterface {

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $template;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}"{{attributes}}></span>',
		];

		$this->template = new StringTemplate(['icon' => $config['template']]);
	}

	/**
	 * @param string $path
	 *
	 * @return array<string>
	 */
	public function names(string $path): array {
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
		$formatOptions = $attributes + [
		];

		$options['class'] = 'bi bi-' . $icon;
		if (!empty($attributes['class'])) {
			$options['class'] .= ' ' . $attributes['class'];
		}
		$options['attributes'] = $this->template->formatAttributes($formatOptions, ['class']);

		return $this->template->format('icon', $options);
	}

}
