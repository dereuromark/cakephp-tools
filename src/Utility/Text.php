<?php

namespace Tools\Utility;

use Cake\Core\Configure;
use Cake\Utility\Text as CakeText;

/**
 * Extends CakeText.
 * //TODO: cleanup
 */
class Text extends CakeText {

	/**
	 * @var string
	 */
	public $text;

	/**
	 * @var int
	 */
	public $length;

	/**
	 * @var string
	 */
	public $char;

	/**
	 * @var string
	 */
	public $letter;

	/**
	 * @var string
	 */
	public $space;

	/**
	 * @var string
	 */
	public $word;

	/**
	 * @var string
	 */
	public $rWord;

	/**
	 * @var string
	 */
	public $sen;

	/**
	 * @var string
	 */
	public $rSen;

	/**
	 * @var string
	 */
	public $para;

	/**
	 * @var string
	 */
	public $rPara;

	/**
	 * @var string
	 */
	public $beautified;

	/**
	 * Read tab data (tab-separated data).
	 *
	 * @param string $text
	 * @return array
	 */
	public function readTab($text) {
		$pieces = explode("\n", $text);
		$result = [];
		foreach ($pieces as $piece) {
			$tmp = explode("\t", trim($piece, "\r\n"));
			$result[] = $tmp;
		}

		return $result;
	}

	/**
	 * Read with a specific pattern.
	 *
	 * E.g.: '%s,%s,%s'
	 *
	 * @param string $text
	 * @param string $pattern
	 * @return array
	 */
	public function readWithPattern($text, $pattern) {
		$pieces = explode("\n", $text);
		$result = [];
		foreach ($pieces as $piece) {
			$result[] = sscanf(trim($piece, "\r\n"), $pattern);
		}

		return $result;
	}

	/**
	 * Count words in a text.
	 *
	 * //TODO use str_word_count() instead!!!
	 *
	 * @param string $text
	 * @return int
	 */
	public static function numberOfWords($text) {
		$count = 0;
		$words = explode(' ', $text);
		foreach ($words as $word) {
			$word = trim($word);
			if (!empty($word)) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Count chars in a text.
	 *
	 * Options:
	 * - 'whitespace': If whitespace should be counted, as well, defaults to false
	 *
	 * @param string $text
	 * @param array<string, mixed> $options
	 * @return int
	 */
	public static function numberOfChars($text, array $options = []) {
		$text = str_replace(["\r", "\n", "\t", ' '], '', $text);
		$count = mb_strlen($text);

		return $count;
	}

	/**
	 * Return an abbreviated string, with characters in the middle of the
	 * excessively long string replaced by $ending.
	 *
	 * @param string $text The original string.
	 * @param int $length The length at which to abbreviate.
	 * @param string $ending Defaults to ...
	 * @return string The abbreviated string, if longer than $length.
	 */
	public static function abbreviate($text, $length = 20, $ending = '...') {
		if (mb_strlen($text) <= $length) {
			return $text;
		}

		return rtrim(mb_substr($text, 0, (int)round(($length - 3) / 2))) . $ending . ltrim(mb_substr($text, (($length - 3) / 2) * -1));
	}

	/**
	 * TextLib::convertToOrd()
	 *
	 * @param string $str
	 * @param string $separator
	 * @return string
	 */
	public function convertToOrd(string $str, string $separator = '-'): string {
		$chars = preg_split('//', $str, -1) ?: [];
		$res = [];
		foreach ($chars as $char) {
			//$res[] = UnicodeLib::ord($char);
			$res[] = $char !== '' ? ord($char) : 0;
		}

		return implode($separator, $res);
	}

	/**
	 * Explode a string of given tags into an array.
	 *
	 * @param string $tags
	 * @return array<string>
	 */
	public function explodeTags($tags) {
		// This regexp allows the following types of user input:
		// this, "somecompany, llc", "and ""this"" w,o.rks", foo bar
		$regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
		preg_match_all($regexp, $tags, $matches);
		$typedTags = array_unique($matches[1]);

		$tags = [];
		foreach ($typedTags as $tag) {
			// If a user has escaped a term (to demonstrate that it is a group,
			// or includes a comma or quote character), we remove the escape
			// formatting so to save the term into the database as the user intends.
			/** @var string $replacedString */
			$replacedString = str_replace('""', '"', (string)preg_replace('/^"(.*)"$/', '\1', $tag));
			$tag = trim($replacedString);
			if ($tag) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Implode an array of tags into a string.
	 *
	 * @param array<string> $tags
	 * @return string
	 */
	public function implodeTags(array $tags) {
		$encodedTags = [];
		foreach ($tags as $tag) {
			// Commas and quotes in tag names are special cases, so encode them.
			if (strpos($tag, ',') !== false || strpos($tag, '"') !== false) {
				$tag = '"' . str_replace('"', '""', $tag) . '"';
			}

			$encodedTags[] = $tag;
		}

		return implode(', ', $encodedTags);
	}

	/**
	 * Prevents [widow words](http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin)
	 * by inserting a non-breaking space between the last two words.
	 *
	 * echo Text::widont($text);
	 *
	 * @param string $str Text to remove widows from
	 * @return string
	 */
	public function widont($str) {
		$str = rtrim($str);
		$space = strrpos($str, ' ');

		if ($space !== false) {
			$str = substr($str, 0, $space) . '&nbsp;' . substr($str, $space + 1);
		}

		return $str;
	}

	/**
	 * Extract words
	 *
	 * @param string $text
	 * @param array<string, mixed> $options
	 * - min_char, max_char, case_sensititive, ...
	 * @return array<string>
	 */
	public function words($text, array $options = []) {
		$text = str_replace([PHP_EOL, "\t"], ' ', $text);

		$pieces = explode(' ', $text);
		$pieces = array_unique($pieces);

		// strip chars like . or ,
		foreach ($pieces as $key => $piece) {
			if (empty($options['case_sensitive'])) {
				$piece = mb_strtolower($piece);
			}
			$search = [',', '.', ';', ':', '#', '', '(', ')', '{', '}', '[', ']', '$', '%', '"', '!', '?', '<', '>', '=', '/'];
			/** @var array<string> $search */
			$search = array_merge($search, [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]);
			$piece = str_replace($search, '', $piece);
			$piece = trim($piece);

			if (!$piece || !empty($options['min_char']) && mb_strlen($piece) < $options['min_char'] || !empty($options['max_char']) && mb_strlen($piece) > $options['max_char']) {
				unset($pieces[$key]);
			} else {
				$pieces[$key] = $piece;
			}
		}
		$pieces = array_unique($pieces);

		return $pieces;
	}

	/**
	 * Limit the number of words in a string.
	 *
	 * <code>
	 *    // Returns "This is a..."
	 *    echo TextExt::maxWords('This is a sentence.', 3);
	 *
	 *    // Limit the number of words and append a custom ending
	 *    echo Str::words('This is a sentence.', 3, '---');
	 * </code>
	 *
	 * @param string $value
	 * @param int $words
	 * @param array<string, mixed> $options
	 * - ellipsis
	 * - html
	 * @return string
	 */
	public static function maxWords($value, $words = 100, array $options = []) {
		$defaults = [
			'ellipsis' => '...',
		];
		if (!empty($options['html']) && Configure::read('App.encoding') === 'UTF-8') {
			$defaults['ellipsis'] = "\xe2\x80\xa6";
		}
		$options += $defaults;

		if (trim($value) === '') {
			return '';
		}
		preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

		$end = $options['ellipsis'];
		if (mb_strlen($value) === mb_strlen($matches[0])) {
			$end = '';
		}

		return rtrim($matches[0]) . $end;
	}

}
