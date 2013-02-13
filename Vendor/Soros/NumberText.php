<?php
App::import('Vendor', 'Tools.Soros/Soros');
if (!class_exists('Soros')) {
	throw new CakeException(__d('dev', 'Vendor class Soros cannot be found'));
}
/**
 * Sample classes
 * Is based on the source code from http://numbertext.org/
 *
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @licence LGPL/BSD dual-license
 */
class Numbertext {

	public function __construct() {
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
		$s = self::getLangModule($lang);
		if (is_null($s))
			$s = self::load($lang);
		if (is_null($s))
			return null;
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
		return self::numbertext($money . " " . $input, $lang);
	}

	private static function load($lang) {
		$url = __dir__ . "/$lang.sor";
		$st = file_get_contents($url);
		if ($st === false)
			return null;
		$s = new Soros($st);
		if ($lang != null)
			self::addModule(array($lang, $s));
		return $s;
	}

	private static function getModules($m = null) {
		static $modules = array();
		if (is_array($m))
			$modules[] = $m;
		return $modules;
	}

	private static function getLangModule($lang) {
		$modules = self::getModules();
		if (isset($modules[$lang]))
			return $modules[$lang];
		return null;
	}

	private static function addModule($m) {
		self::getModules($m);
	}

}
