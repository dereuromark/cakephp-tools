<?php

namespace Tools\View\Icon;

use Cake\View\StringTemplate;
use Tools\View\Icon\Collector\FontAwesome6IconCollector;

class FontAwesome6Icon implements IconInterface {

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $template;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}"{{attributes}}></span>',
		];

		$this->template = new StringTemplate(['icon' => $config['template']]);
		$this->namespace = $config['namespace'] ?? 'solid';
	}

	/**
	 * @param string $path
	 *
	 * @return array<string>
	 */
	public function names(string $path): array {
		return FontAwesome6IconCollector::collect($path);
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

		$namespace = 'fa-' . $this->namespace;

		$class = [
			$namespace,
		];
		if (!empty($options['extra'])) {
			foreach ($options['extra'] as $i) {
				$class[] = 'fa-' . $i;
			}
		}
		if (!empty($options['size'])) {
			$class[] = 'fa-' . ($options['size'] === 'large' ? 'large' : $options['size'] . 'x');
		}
		if (!empty($options['pull'])) {
			$class[] = 'pull-' . $options['pull'];
		}
		if (!empty($options['rotate'])) {
			$class[] = 'fa-rotate-' . (int)$options['rotate'];
		}
		if (!empty($options['spin'])) {
			$class[] = 'fa-spin';
		}

		$options['class'] = implode(' ', $class) . ' ' . 'fa-' . $icon;
		if (!empty($attributes['class'])) {
			$options['class'] .= ' ' . $attributes['class'];
		}
		$options['attributes'] = $this->template->formatAttributes($formatOptions, ['class']);

		return $this->template->format('icon', $options);
	}

}
