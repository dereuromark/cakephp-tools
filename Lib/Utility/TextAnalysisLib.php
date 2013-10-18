<?php
App::uses('TextLib', 'Tools.Utility');

/**
 * TODO: extend the core String class some day?
 *
 */
class TextAnalysisLib extends TextLib {

	public function __construct($text = null) {
		$this->text = $text;
	}

	/**
	 * @param string $stringToCheck
	 * @param tolerance (in %: 0 ... 1)
	 * @return boolean Success
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
	 * @return boolean
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

/* text object specific */

	/**
	 * @return array(char=>amount) for empty char or int amount for specific char
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
		}

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

	/**
	 * @return array(char=>amount) for empty char or int amount for specific char
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
		//echo returns($occ);
		return $occ;
	}

	public function getLength() {
		if (!$this->length) {
			$this->length = mb_strlen($this->text);
		}
		return $this->length;
	}

	public function getCharacter() {
		if (!$this->char) $this->char = mb_strlen(strtr($this->text, array("\n" => '', "\r" =>
				'')));
		return $this->char;
	}

	public function getLetter() {
		if (!$this->letter) {
			$lText = mb_strtolower($this->text);
			for ($i = 0; $i < $this->length; $i++)
				if (mb_strpos("abcdefghijklmnopqrstuvwxyzäöü", $lText[$i]) != false) $this->
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
		if (!$this->word && !$this->rWord) {
			@preg_match_all("/[A-Za-zäöüÄÖÜß\-'\\\"]+/", $this->text, $m);
			$this->word = count($m[0]);
			$this->rWord = $m[0];
		}
		return $parse ? $this->rWord : $this->word;
	}

	/**
	 * @param options
	 * - min_char, max_char, case_sensititive, sort ('asc', 'desc', 'length', 'alpha', false), limit...
	 */
	public function wordCount($options = array()) {
		if (true || !$this->rrWord) {
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
				if ($sort === 'asc') {
					asort($res);
				} elseif ($sort === 'desc') {
					arsort($res);
				} elseif ($sort === 'length') {
					//TODO:
					//uasort($res, $callback);

				} elseif ($sort === 'alpha') {
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
		if (!$this->sen && !$this->rSen) {
			@preg_match_all("/[^:|;|\!|\.]+(:|;|\!|\.| )+/", $this->text, $m);
			$this->sen = count($m[0]);
			foreach ($m[0] as $s) $this->rSen[] = strtr(trim($s), array("\n" => '', "\r" => ''));
		}
		return $parse ? $this->rSen : $this->sen;
	}

	public function getParagraph($parse = false) {
		if (!$this->para && !$this->rPara) {
			@preg_match_all("/[^\n]+?(:|;|\!|\.| )+\n/s", strtr($this->text, array("\r" =>
				'')) . "\n", $m);
			$this->para = count($m[0]);
			foreach ($m[0] as $p) $this->rPara[] = trim($p);
		}
		return $parse ? $this->rPara : $this->para;
	}

	public function beautify($wordwrap = false) {
		if (!$this->beautified) {
			$this->beautified = @preg_replace(array("/ {1,}/", "/\. {1,}\./", "/\. *(?!\.)/",
				"/(,|:|;|\!|\)) */", "/(,|:|;|\!|\)|\.) *\r\n/", "/(\r\n) {3,}/"), array(" ", ".",
				". ", "$1 ", "$1\r\n", "\r\n\r\n"), $this->text);
		}
		return $wordwrap ? wordwrap($this->beautified, $wordwrap) : $this->beautified;
	}

}