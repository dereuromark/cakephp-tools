<?php

namespace Tools\Utility;

/**
 * Parses Browser detected preferred language.
 */
class Language
{

	/**
	 * @param string|null $languageList
	 *
	 * @return array
	 */
	public static function parseLanguageList($languageList = null)
	{
		if ($languageList === null) {
			if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				return [];
			}
			$languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		$languages = [];
		$languageRanges = explode(',', trim($languageList));
		foreach ($languageRanges as $languageRange) {
			$pattern = '/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/';
			if (preg_match($pattern, trim($languageRange), $match)) {
				if (!isset($match[2])) {
					$match[2] = '1.0';
				} else {
					$match[2] = (string)(float)($match[2]);
				}
				if (!isset($languages[$match[2]])) {
					if ($match[2] === '1') {
						$match[2] = '1.0';
					}
					$languages[$match[2]] = [];
				}
				$languages[$match[2]][] = strtolower($match[1]);
			}
		}
		krsort($languages);

		return $languages;
	}

	/**
	 * Compares two parsed arrays of language tags and find the matches
	 *
	 * @param array $accepted
	 * @param array $available
	 * @return string|null
	 */
	public static function findFirstMatch(array $accepted, array $available = [])
	{
		$matches = static::findMatches($accepted, $available);
		if (!$matches) {
			return null;
		}

		$match = array_shift($matches);
		if (!$match) {
			return null;
		}

		return array_shift($match);
	}

	/**
	 * Compares two parsed arrays of language tags and find the matches
	 *
	 * @param array $accepted
	 * @param array $available
	 * @return array
	 */
	public static function findMatches(array $accepted, array $available = []) 
	{
		$matches = [];
		if (!$available) {
			$available = static::parseLanguageList();
		}
		foreach ($accepted as $acceptedValue) {
			foreach ($available as $availableQuality => $availableValues) {
				$availableQuality = (float)$availableQuality;
				if ($availableQuality === 0.0) {
					continue;
				}

				foreach ($availableValues as $availableValue) {
					$matchingGrade = static::_matchLanguage($acceptedValue, $availableValue);
					if ($matchingGrade > 0) {
						$q = (string)($availableQuality * $matchingGrade);
						if ($q === '1') {
							$q = '1.0';
						}
						if (!isset($matches[$q])) {
							$matches[$q] = [];
						}
						if (!in_array($availableValue, $matches[$q])) {
							$matches[$q][] = $availableValue;
						}
					}
				}
			}
		}
		krsort($matches);

		return $matches;
	}

	/**
	 * Compare two language tags and distinguish the degree of matching
	 *
	 * @param string $a
	 * @param string $b
	 * @return float
	 */
	protected static function _matchLanguage($a, $b)
	{
		$a = explode('-', $a);
		$b = explode('-', $b);

		for ($i = 0, $n = min(count($a), count($b)); $i < $n; $i++) {
			if ($a[$i] !== $b[$i]) {
				break;
			}
		}

		return $i === 0 ? 0 : (float)$i / count($a);
	}

}
