<?php
namespace Tools\HtmlDom;

use Yangqi\Htmldom\Htmldom as BaseHtmlDom;

/**
 * A wrapper class to parse HTML DOM including traversing, manipulation etc.
 *
 * An alternative would PhpQueryLib be for example.
 *
 * @link http://simplehtmldom.sourceforge.net/
 */
class HtmlDom extends BaseHtmlDom {

	/**
	 * @param url or path to file content
	 * @return object Dom
	 */
	public static function domFromFile($url) {
		return static::file_get_html($url);
	}

	/**
	 * @param string $content
	 * @return object Dom
	 */
	public static function domFromString($str, $lowercase = true) {
		return static::str_get_html($str, $lowercase);
	}

}
