<?php

namespace Tools\View\Icon;

interface IconInterface {

	/**
	 * @param string $path
	 *
	 * @return array<string>
	 */
	public function names(string $path): array;

	/**
	 * Icon formatting using the specific engine.
	 *
	 * @param string $icon Icon name
	 * @param array<string, mixed> $options :
	 * - translate, title, ...
	 * @param array<string, mixed> $attributes :
	 * - class, ...
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []): string;

}
