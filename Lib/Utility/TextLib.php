<?php

/**
 * TODO: extend the core String class some day?
 *
 * 2010-08-31 ms
 */
class TextLib {

	protected $text, $lenght, $char, $letter, $space, $word, $r_word, $sen, $r_sen, $para,
		$r_para, $beautified;


	public function __construct($text) {
		$this->text = $text;
	}

	/**
	 * @param string $stringToCheck
	 * @param tolerance (in %: 0 ... 1)
	 * @return boolean $success
	 * 2011-10-13 ms
	 */
	public function isScreamFont($str = null, $tolerance = 0.4) {
		if ($str === null) {
			$str = $this->text;
		}
		if (empty($str)) {
			return false;
		}

		$res = preg_match_all('/[A-ZÄÖÜ]/u', $str, $uppercase);
		$uppercase = array_shift($uppercase);
		//echo returns($uppercase);

		$res = preg_match_all('/[a-zäöüß]/u', $str, $lowercase);
		$lowercase = array_shift($lowercase);
		//echo returns($lowercase);

		if (($countUpper = count($uppercase)) && $countUpper >= count($lowercase)) {
			return true;
		}
		//TODO: tolerance

		return false;
	}

	/**
	 * @param string
	 * @param string $additionalChars
	 * - e.g. `-()0123456789`
	 */
	public function isWord($str = null, $additionalChars = null) {
		return preg_match('/^\w+$/', $str);
	}


/* utf8 generell stuff */

	/**
	 * Tests whether a string contains only 7-bit ASCII bytes. This is used to
	 * determine when to use native functions or UTF-8 functions.
	 *
	 * $ascii = UTF8::is_ascii($str);
	 *
	 * @param string string to check
	 * @return bool
	 */
	public function isAscii($str = null) {
		if ($str === null) {
			$str = $this->text;
		}
		return !preg_match('/[^\x00-\x7F]/S', $str);
	}

	/**
	 * Strips out device control codes in the ASCII range.
	 *
	 * $str = UTF8::strip_ascii_ctrl($str);
	 *
	 * @param string string to clean
	 * @return string
	 */
	public function stripAsciiCtrl($str = null) {
		if ($str === null) {
			$str = $this->text;
		}
		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
	}

	/**
	 * Strips out all non-7bit ASCII bytes.
	 *
	 * $str = UTF8::strip_non_ascii($str);
	 *
	 * @param string string to clean
	 * @return string
	 */
	public function stripNonAscii($str = null) {
		if ($str === null) {
			$str = $this->text;
		}
		return preg_replace('/[^\x00-\x7F]+/S', '', $str);
	}


	public function convertToOrd($str = null, $separator = '-') {
		/*
		if (!class_exists('UnicodeLib')) {
			App::uses('UnicodeLib', 'Tools.Lib');
		}
		*/
		if ($str === null) {
			$str = $this->text;
		}
		$chars = preg_split('//', $str, -1);
		$res = array();
		foreach ($chars as $char) {
			//$res[] = UnicodeLib::ord($char);
			$res[] = ord($char);
		}
		return implode($separator, $res);
	}

	public static function convertToOrdTable($str, $maxCols = 20) {
		$res = '<table>';
		$r = array('chr'=>array(), 'ord'=>array());
		$chars = preg_split('//', $str, -1);
		$count = 0;
		foreach ($chars as $key => $char) {
			if ($maxCols && $maxCols < $count || $key === count($chars)-1) {
				$res .= '<tr><th>'.implode('</th><th>', $r['chr']).'</th>';
				$res .= '</tr>';
				$res .= '<tr>';
				$res .= '<td>'.implode('</th><th>', $r['ord']).'</td></tr>';
				$count = 0;
				$r = array('chr'=>array(), 'ord'=>array());
			}
			$count++;
			//$res[] = UnicodeLib::ord($char);
			$r['ord'][] = ord($char);
			$r['chr'][] = $char;
		}

		$res .= '</table>';
		return $res;
	}


/* other */

	/**
	 * Explode a string of given tags into an array.
	 */
	public function explodeTags($tags) {
		// This regexp allows the following types of user input:
		// this, "somecompany, llc", "and ""this"" w,o.rks", foo bar
		$regexp = '%(?:^|,\ *)("(?>[^"]*)(?>""[^"]* )*"|(?: [^",]*))%x';
		preg_match_all($regexp, $tags, $matches);
		$typed_tags = array_unique($matches[1]);

		$tags = array();
		foreach ($typed_tags as $tag) {
		// If a user has escaped a term (to demonstrate that it is a group,
		// or includes a comma or quote character), we remove the escape
		// formatting so to save the term into the database as the user intends.
		$tag = trim(str_replace('""', '"', preg_replace('/^"(.*)"$/', '\1', $tag)));
		if ($tag != "") {
			$tags[] = $tag;
		}
		}

		return $tags;
	}


	/**
	 * Implode an array of tags into a string.
	 */
	public function implodeTags($tags) {
		$encoded_tags = array();
		foreach ($tags as $tag) {
		// Commas and quotes in tag names are special cases, so encode them.
		if (strpos($tag, ',') !== FALSE || strpos($tag, '"') !== FALSE) {
			$tag = '"'. str_replace('"', '""', $tag) .'"';
		}

		$encoded_tags[] = $tag;
		}
		return implode(', ', $encoded_tags);
	}



		/**
	 * Prevents [widow words](http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin)
	 * by inserting a non-breaking space between the last two words.
	 *
	 * echo Text::widont($text);
	 *
	 * @param string text to remove widows from
	 * @return string
	 */
	public function widont($str = null) {
		if ($str === null) {
			$str = $this->text;
		}
		$str = rtrim($str);
		$space = strrpos($str, ' ');

		if ($space !== FALSE) {
			$str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
		}

		return $str;
	}


/* text object specific */

	/**
	 * @return array(char=>amount) for empty char or int amount for specific char
	 * 2010-08-31 ms
	 */
	public function occurrences($char = null, $caseSensitive = false) {

		if ($caseSensitive) {
			$str = $this->text;
		} else {
			if ($char !== null) {
				$char = strtolower($char);
			}
			$str = strtolower($this->text);
		}

		if ($char === null) {
			$occ = array();
			$str = str_split($str);
			foreach ($str as $value) {
				if (array_key_exists($value, $occ)) {
					$occ[$value] += 1;
				} else {
					$occ[$value] = 1;
				}
			}
			return $occ;

		} else {

			$occ = 0;
			$pos = 0;
			do {
				$pos = strpos($str, $char, $pos);
				if ($pos !== false) {
					$occ++;
					$pos++;
				} else {
					break;
				}
			} while (true);
			return $occ;
		}
	}


	/**
	 * @return array(char=>amount) for empty char or int amount for specific char
	 * 2010-08-31 ms
	 */
	public function maxOccurrences($caseSensitive = false) {

		$arr = $this->occurrences(null, $caseSensitive);
		$max = 0;
		$occ = array();

		foreach ($arr as $key => $value) {
			if ($value === $max) {
				$occ[$key] = $value;
			} elseif ($value > $max) {
				$max = $value;
				$occ = array($key => $value);
			}
		}
		echo returns($occ);
		return $occ;
	}


	public function getLength() {
		if (!$this->lenght) {
			$this->lenght = mb_strlen($this->text);
		}
		return $this->lenght;
	}

	public function getCharacter() {
		if (!$this->char) $this->char = mb_strlen(strtr($this->text, array("\n" => '', "\r" =>
				'')));
		return $this->char;
	}

	public function getLetter() {
		if (!$this->letter) {
			$l_text = mb_strtolower($this->text);
			for ($i = 0; $i < $this->lenght; $i++)
				if (mb_strpos("abcdefghijklmnopqrstuvwxyzäöü", $l_text[$i]) != false) $this->
						letter++;
		}
		return $this->letter;
	}

	public function getSpace() {
		if (!$this->space) $this->space = mb_substr_count($this->text, " ") +
				mb_substr_count($this->text, "\t");
		return $this->space;
	}

	public function getSymbol() {
		return $this->getCharacter() - $this->getLetter() - $this->getSpace();
	}

	//TODO: improve it to work with case insensitivity and utf8 chars like é or î
	public function getWord($parse = false) {
		if (!$this->word && !$this->r_word) {
			@preg_match_all("/[A-Za-zäöüÄÖÜß\-'\\\"]+/", $this->text, $m);
			$this->word = count($m[0]);
			$this->r_word = $m[0];
		}
		return $parse ? $this->r_word : $this->word;
	}

	/**
	 * @param options
	 * - min_char, max_char, case_sensititive, ...
	 * 2010-10-09 ms
	 */
	public function words($options = array()) {
		if (true || !$this->xr_word) {
			$text = str_replace(array(PHP_EOL, NL, TB), ' ', $this->text);

			$pieces = explode(' ', $text);
			$pieces = array_unique($pieces);

			# strip chars like . or ,
			foreach ($pieces as $key => $piece) {
				if (empty($options['case_sensitive'])) {
					$piece = mb_strtolower($piece);
				}
				$search = array(',', '.', ';', ':', '#', '', '(', ')', '{', '}', '[', ']', '$', '%', '"', '!', '?', '<', '>', '=', '/');
				$search = array_merge($search, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0));
				$piece = str_replace($search, '', $piece);
				$piece = trim($piece);

				if (empty($piece) || !empty($options['min_char']) && mb_strlen($piece) < $options['min_char'] || !empty($options['max_char']) && mb_strlen($piece) > $options['max_char']) {
					unset($pieces[$key]);
				} else {
					$pieces[$key] = $piece;
				}
			}
			$pieces = array_unique($pieces);
			//$this->xr_word = $pieces;
		}
		return $pieces;
	}

	/**
	 * @param options
	 * - min_char, max_char, case_sensititive, sort ('asc', 'desc', 'length', 'alpha', false), limit...
	 * 2010-10-09 ms
	 */
	public function wordCount($options = array()) {
		if (true || !$this->rr_word) {
			$text = str_replace(array(NL, CR, PHP_EOL, TB), ' ', $this->text);
			$res = array();
			$search = array('*', '+', '~', ',', '.', ';', ':', '#', '', '(', ')', '{', '}', '[', ']', '$', '%', '“', '”', '—', '"', '‘', '’', '!', '?', '<', '>', '=', '/');
			$search = array_merge($search, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0));
			$text = str_replace($search, ' ', $text);

			$pieces = explode(' ', $text);

			//TODO: use array_count_values()?
			foreach ($pieces as $key => $piece) {
				if (empty($options['case_sensitive'])) {
					$piece = mb_strtolower($piece);
				}
				$piece = trim($piece);

				if (empty($piece) || !empty($options['min_char']) && mb_strlen($piece) < $options['min_char'] || !empty($options['max_char']) && mb_strlen($piece) > $options['max_char']) {
					unset($pieces[$key]);
					continue;
				}

				if (!array_key_exists($piece, $res)) {
					$res[$piece] = 0;
				}
				$res[$piece]++;
			}
			if (!empty($options['sort'])) {
				$sort = strtolower($options['sort']);
				if ($sort == 'asc') {
					asort($res);
				} elseif ($sort == 'desc') {
					arsort($res);
				} elseif ($sort == 'length') {
					//TODO:
					//uasort($res, $callback);

				} elseif ($sort == 'alpha') {
					ksort($res);
				}
			}
			if (!empty($options['limit'])) {
				$res = array_slice($res, 0, (int)$options['limit'], true);
			}

			//$this->rr_word = $res;
		}
		return $res; // $this->rr_word;
	}


	public function getSentence($parse = false) {
		if (!$this->sen && !$this->r_sen) {
			@preg_match_all("/[^:|;|\!|\.]+(:|;|\!|\.| )+/", $this->text, $m);
			$this->sen = count($m[0]);
			foreach ($m[0] as $s) $this->r_sen[] = strtr(trim($s), array("\n" => '', "\r" =>
					''));
		}
		return $parse ? $this->r_sen : $this->sen;
	}

	public function getParagraph($parse = false) {
		if (!$this->para && !$this->r_para) {
			@preg_match_all("/[^\n]+?(:|;|\!|\.| )+\n/s", strtr($this->text, array("\r" =>
				'')) . "\n", $m);
			$this->para = count($m[0]);
			foreach ($m[0] as $p) $this->r_para[] = trim($p);
		}
		return $parse ? $this->r_para : $this->para;
	}

	public function beautify($wordwrap = false) {
		if (!$this->beautified) {
			$this->beautified = @preg_replace(array("/ {1,}/", "/\. {1,}\./", "/\. *(?!\.)/",
				"/(,|:|;|\!|\)) */", "/(,|:|;|\!|\)|\.) *\r\n/", "/(\r\n) {3,}/"), array(" ", ".",
				". ", "$1 ", "$1\r\n", "\r\n\r\n"), $this->text);
		}
		return $wordwrap ? wordwrap($this->beautified, $wordwrap) : $this->beautified;
	}


	/**
	 * High ASCII to Entities
	 *
	 * Converts High ascii text and MS Word special characters to character entities
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function ascii_to_entities($str) {
		$count = 1;
		$out = '';
		$temp = array();

		for ($i = 0, $s = strlen($str); $i < $s; $i++) {
			$ordinal = ord($str[$i]);

			if ($ordinal < 128) {
				/*
				If the $temp array has a value but we have moved on, then it seems only
				fair that we output that entity and restart $temp before continuing. -Paul
				*/
				if (count($temp) == 1) {
					$out .= '&#' . array_shift($temp) . ';';
					$count = 1;
				}

				$out .= $str[$i];
			} else {
				if (count($temp) == 0) {
					$count = ($ordinal < 224) ? 2 : 3;
				}

				$temp[] = $ordinal;

				if (count($temp) == $count) {
					$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] %
						64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);

					$out .= '&#' . $number . ';';
					$count = 1;
					$temp = array();
				}
			}
		}
		return $out;
	}

	// ------------------------------------------------------------------------

	/**
	 * Entities to ASCII
	 *
	 * Converts character entities back to ASCII
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public function entities_to_ascii($str, $all = true) {
		if (preg_match_all('/\&#(\d+)\;/', $str, $matches)) {
			for ($i = 0, $s = count($matches['0']); $i < $s; $i++) {
				$digits = $matches['1'][$i];

				$out = '';

				if ($digits < 128) {
					$out .= chr($digits);

				} elseif ($digits < 2048) {
					$out .= chr(192 + (($digits - ($digits % 64)) / 64));
					$out .= chr(128 + ($digits % 64));
				} else {
					$out .= chr(224 + (($digits - ($digits % 4096)) / 4096));
					$out .= chr(128 + ((($digits % 4096) - ($digits % 64)) / 64));
					$out .= chr(128 + ($digits % 64));
				}

				$str = str_replace($matches['0'][$i], $out, $str);
			}
		}

		if ($all) {
			$str = str_replace(array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
				array("&", "<", ">", "\"", "'", "-"), $str);
		}

		return $str;
	}


	/**
	 * Reduce Double Slashes
	 *
	 * Converts double slashes in a string to a single slash,
	 * except those found in http://
	 *
	 * http://www.some-site.com//index.php
	 *
	 * becomes:
	 *
	 * http://www.some-site.com/index.php
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function reduce_double_slashes($str) {
		return preg_replace("#([^:])//+#", "\\1/", $str);
	}

	// ------------------------------------------------------------------------

	/**
	 * Reduce Multiples
	 *
	 * Reduces multiple instances of a particular character. Example:
	 *
	 * Fred, Bill,, Joe, Jimmy
	 *
	 * becomes:
	 *
	 * Fred, Bill, Joe, Jimmy
	 *
	 * @access	public
	 * @param	string
	 * @param	string	the character you wish to reduce
	 * @param	bool	TRUE/FALSE - whether to trim the character from the beginning/end
	 * @return	string
	 */
	public function reduce_multiples($str, $character = ',', $trim = false) {
		$str = preg_replace('#' . preg_quote($character, '#') . '{2,}#', $character, $str);

		if ($trim === true) {
			$str = trim($str, $character);
		}

		return $str;
	}

}


/*

//explode string, return word and number of repeation
$r = explode('[spilit]', $value);

//regex
if ( preg_match('/([a-z]+)/', $r[0])) {

preg_match_all( '/'. $r[0] .'/', $this -> checkString[$arrays], $match);
} else {

preg_match_all( '/\\'. $r[0] .'/', $this -> checkString[$arrays], $match);
}

//count chars
if ( count($match[0]) <= $r[1]) {

$this -> _is_valid[$arrays][$valData] = true;
} else {

$this -> _is_valid[$arrays][$valData] = false;

//set errors array
$this -> error[$arrays][] = $r[0] . $this -> error_max_time_char;
}

*/


