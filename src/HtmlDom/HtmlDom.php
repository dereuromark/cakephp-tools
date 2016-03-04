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
	 * @param string $url URL or path to file content
	 * @return object Dom
	 */
	public function domFromFile($url) {
		return parent::file_get_html($url);
	}

	/**
	 * @param string $str
	 * @param bool $lowercase
	 * @return object Dom
	 */
	public function domFromString($str, $lowercase = true) {
		return parent::str_get_html($str, $lowercase);
	}

}
