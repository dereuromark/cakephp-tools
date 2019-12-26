<?php

namespace Tools\Utility;

/**
 * Parses Browser detected preferred language.
 */
class Language {

	/**
	 * Parse languages from a browser language list.
	 *
	 * Options
	 * - forceLowerCase: defaults to true
	 *
	 * @param string|null $languageList List of locales/language codes.
	 * @param array|bool|null $options Flags to forceLowerCase or removeDuplicates locales/language codes
	 *        deprecated: Set to true/false to toggle lowercase
	 *
	 * @return array
	 */
	public static function parseLanguageList($languageList = null, $options = []) {
		$defaultOptions = [
			'forceLowerCase' => true,
		];
		if (!is_array($options)) {
			$options = ['forceLowerCase' => $options];
		}
		$options += $defaultOptions;

		if ($languageList === null) {
			if (!env('HTTP_ACCEPT_LANGUAGE')) {
				return [];
			}
			$languageList = env('HTTP_ACCEPT_LANGUAGE');
		}

		$languages = [];
		$languagesRanks = [];
		$languageRanges = explode(',', trim($languageList));

		foreach ($languageRanges as $languageRange) {
			$pattern = '/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/';
			if (preg_match($pattern, trim($languageRange), $match)) {
				if (!isset($match[2])) {
					$rank = '1.0';
				} else {
					$rank = (string)(float)($match[2]);
				}
				if (!isset($languages[$rank])) {
					if ($rank === '1') {
						$rank = '1.0';
					}
					$languages[$rank] = [];
				}

				$language = $match[1];
				if ($options['forceLowerCase']) {
					$language = strtolower($language);
				} else {
					$language = substr_replace($language, strtolower(substr($language, 0, 2)), 0, 2);
					if (strlen($language) === 5) {
						$language = substr_replace($language, strtoupper(substr($language, 3, 2)), 3, 2);
					}
				}

				if (array_key_exists($language, $languagesRanks) === false) {
					$languages[$rank][] = $language;
					$languagesRanks[$language] = $rank;
				} elseif ($rank > $languagesRanks[$language]) {
					foreach ($languages as $existRank => $existLangs) {
						$key = array_search($existLangs, $languages);
						if ($key !== false) {
							unset($languages[$existRank][$key]);
							if (empty($languages[$existRank])) {
								unset($languages[$existRank]);
							}
						}
					}
					$languages[$rank][] = $language;
					$languagesRanks[$language] = $rank;
				}
			}
		}
		krsort($languages);
		return $languages;
	}

	/**
	 * Compares two parsed arrays of language tags and find the matches
	 *
	 * @param string[] $accepted
	 * @param array $available
	 * @return string|null
	 */
	public static function findFirstMatch(array $accepted, array $available = []) {
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
	 * @param string[] $accepted
	 * @param array $available
	 * @return array
	 */
	public static function findMatches(array $accepted, array $available = []) {
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
	protected static function _matchLanguage($a, $b) {
		$a = explode('-', strtolower($a));
		$b = explode('-', strtolower($b));

		for ($i = 0, $n = min(count($a), count($b)); $i < $n; $i++) {
			if ($a[$i] !== $b[$i]) {
				break;
			}
		}
		return $i === 0 ? 0 : (float)$i / count($a);
	}

}
