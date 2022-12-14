<?php

namespace Tools\View\Icon;

use Cake\View\StringTemplate;
use RuntimeException;

abstract class AbstractIcon implements IconInterface {

	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected $template;

	/**
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config = []) {
		$this->template = new StringTemplate(['icon' => $config['template']]);
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	protected function path(): string {
		$path = $this->config['path'] ?? null;
		if (!$path) {
			throw new RuntimeException('You need to define a meta data file path for `' . static::class . '` in order to get icon names.');
		}
		if (!file_exists($path)) {
			throw new RuntimeException('Cannot find meta data file path `' . $path . '` for `' . static::class . '`.');
		}

		return $path;
	}

}
