<?php
App::import('Vendor', 'Tools.Soros/Soros');
if (!class_exists('Soros')) {
	throw new CakeException(__d('dev', 'Vendor class Soros cannot be found'));
}

/**
 * Wrapper class for Soros number parsing into text
 * based on the source code from http://numbertext.org/
 *
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence LGPL/BSD dual-license
 */
class NumberTextLib {

	protected static $_dir = null;

	public function __construct() {
	}

	/**
	 * Set language
	 *
	 * @param string $lang (defaults to en_US)
	 * @return language
	 * @throws CakeException
	 */
	public static function setLang($lang = null) {
		if (!$lang) {
			$lang = 'en_US';
		}
		if (!static::$_dir) {
			static::$_dir = CakePlugin::path('Tools') . 'Vendor' . DS . 'Soros' . DS;
		}
		if (!file_exists(static::$_dir . "$lang.sor")) {
			throw new CakeException(__d('dev', 'Language file %s.sor not found', $lang));
		}
		return $lang;
	}

	/**
	 * Number to text conversion
	 *
	 * @param Parser $parser
	 * @param string $input
	 * @param string $lang default 'en_US'
	 * @return string
	 */
	public static function numberText($input = '', $lang = '') {
		$s = static::getLangModule($lang);
		if ($s === null) {
			$s = static::load($lang);
		}
		if ($s === null) {
			return null;
		}
		return $s->run($input);
	}

	/**
	 * Money to text conversion
	 *
	 * @param Parser $parser
	 * @param string $input
	 * @param string $money
	 * @param string $lang default 'en_US'
	 * @return string
	 */
	public static function moneyText($input = '', $money = '', $lang = '') {
		return static::numbertext($money . " " . $input, $lang);
	}

	protected static function load($lang) {
		$lang = static::setLang($lang);

		$file = static::$_dir . "$lang.sor";
		$st = file_get_contents($file);
		if ($st === false) {
			return null;
		}
		$s = new Soros($st);
		if ($lang != null) {
			static::addModule(array($lang, $s));
		}
		return $s;
	}

	protected static function getModules($m = null) {
		static $modules = array();
		if (is_array($m)) {
			$modules[] = $m;
		}
		return $modules;
	}

	protected static function getLangModule($lang) {
		$modules = static::getModules();
		if (isset($modules[$lang])) {
			return $modules[$lang];
		}
		return null;
	}

	protected static function addModule($m) {
		static::getModules($m);
	}

}
