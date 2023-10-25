<?php

namespace Tools\View\Helper;

use Cake\Core\App;
use Cake\Core\Exception\CakeException;
use Cake\View\Helper\NumberHelper as CakeNumberHelper;
use Cake\View\View;

/**
 * Overwrite to allow usage of own Number class.
 *
 * @mixin \Tools\I18n\Number
 */
class NumberHelper extends CakeNumberHelper {

	/**
	 * @var \Cake\I18n\Number
	 */
	protected $_engine;

	/**
	 * ### Settings:
	 *
	 * - `engine` Class name to use to replace Number functionality.
	 *            The class needs to be placed in the `Utility` directory.
	 *
	 * @param \Cake\View\View $view The View this helper is being attached to.
	 * @param array<string, mixed> $config Configuration settings for the helper
	 */
	public function __construct(View $view, array $config = []) {
		$config += ['engine' => 'Tools.Number'];

		parent::__construct($view, $config);

		/** @psalm-var class-string<\Cake\I18n\Number>|null $engineClass */
		$engineClass = App::className($config['engine'], 'I18n');
		if ($engineClass === null) {
			throw new CakeException(sprintf('Class for `%s` could not be found', $config['engine']));
		}

		$this->_engine = new $engineClass($config);
	}

	/**
	 * Call methods from Cake\I18n\Number utility class
	 *
	 * @param string $method Method to invoke
	 * @param array $params Array of params for the method.
	 * @return mixed Whatever is returned by called method, or false on failure
	 */
	public function __call(string $method, array $params): mixed {
		return $this->_engine->{$method}(...$params);
	}

}
