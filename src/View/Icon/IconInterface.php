<?php

namespace Tools\View\Icon;

interface IconInterface {

	/**
	 * @return array<string>
	 */
	public function names(): array;

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
