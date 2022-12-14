<?php

namespace Tools\View\Icon;

use Cake\View\StringTemplate;
use Tools\View\Icon\Collector\FeatherIconCollector;

class FeatherIcon implements IconInterface {

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $template;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span data-feather="{{name}}"{{attributes}}></span>',
		];

		$this->template = new StringTemplate(['icon' => $config['template']]);
	}

	/**
	 * @param string $path
	 *
	 * @return array<string>
	 */
	public function names(string $path): array {
		return FeatherIconCollector::collect($path);
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

		$options['name'] = $icon;
		$options['attributes'] = $this->template->formatAttributes($formatOptions);

		return $this->template->format('icon', $options);
	}

}
