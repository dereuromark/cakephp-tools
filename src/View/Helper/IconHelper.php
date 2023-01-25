<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\View;
use Tools\View\Icon\IconCollection;

/**
 * Font icon rendering.
 *
 * @author Mark Scherer
 * @license MIT
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class IconHelper extends Helper {

	/**
	 * Other helpers used by FormHelper
	 *
	 * @var array
	 */
	protected array $helpers = ['Html'];

	/**
	 * @var \Tools\View\Icon\IconCollection
	 */
	protected IconCollection $collection;

	/**
	 * @var array
	 */
	protected array $_defaults = [
		'sets' => [],
		'paths' => [],
		'autoPrefix' => true, // For primary set no prefix is required
		'separator' => ':',
	];

	/**
	 * @param \Cake\View\View $View
	 * @param array<string, mixed> $config
	 */
	public function __construct(View $View, array $config = []) {
		$defaults = (array)Configure::read('Icon') + $this->_defaults;
		$config += $defaults;

		$this->collection = new IconCollection($config);

		parent::__construct($View, $config);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function names(): array {
		return $this->collection->names();
	}

	/**
	 * Icons using the default namespace or an already prefixed one.
	 *
	 * @param string $icon (constant or filename)
	 * @param array<string, mixed> $options :
	 * - translate, title, ...
	 * @param array<string, mixed> $attributes :
	 * - class, ...
	 * @return string
	 */
	public function render(string $icon, array $options = [], array $attributes = []) {
		return $this->collection->render($icon, $options, $attributes);
	}

}
