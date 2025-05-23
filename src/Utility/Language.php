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
	 * @param array<string, mixed> $options Flags to forceLowerCase or removeDuplicates locales/language codes
	 *
	 * @return array
	 */
	public static function parseLanguageList(?string $languageList = null, array $options = []): array {
		$defaultOptions = [
			'forceLowerCase' => true,
		];
		$options += $defaultOptions;

		if ($languageList === null) {
			if (!env('HTTP_ACCEPT_LANGUAGE')) {
				return [];
			}
			$languageList = (string)env('HTTP_ACCEPT_LANGUAGE');
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
					/** @var string $language */
					$language = substr_replace($language, strtolower(substr($language, 0, 2)), 0, 2);
					if (strlen($language) === 5) {
						/** @var string $language */
						$language = substr_replace($language, strtoupper(substr($language, 3, 2)), 3, 2);
					}
				}

				if (array_key_exists($language, $languagesRanks) === false) {
					$languages[$rank][] = $language;
					$languagesRanks[$language] = $rank;
				} elseif ($rank > $languagesRanks[$language]) {
					foreach ($languages as $existRank => $existLangs) {
						$key = array_search($existLangs, $languages, true);
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
	 * @param array<string> $accepted
	 * @param array $available
	 * @param bool $onlyTwoLetters
	 * @return string|null
	 */
	public static function findFirstMatch(array $accepted, array $available = [], bool $onlyTwoLetters = false) {
		$matches = static::findMatches($accepted, $available, $onlyTwoLetters);
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
	 * @param array<string> $accepted
	 * @param array $available
	 * @param bool $onlyTwoLetters
	 * @return array
	 */
	public static function findMatches(array $accepted, array $available = [], bool $onlyTwoLetters = false): array {
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
					$matchingGrade = static::_matchLanguage($acceptedValue, $availableValue, $onlyTwoLetters);
					if ($matchingGrade > 0) {
						$q = (string)($availableQuality * $matchingGrade);
						if ($q === '1') {
							$q = '1.0';
						}
						if (!isset($matches[$q])) {
							$matches[$q] = [];
						}
						if (!in_array($availableValue, $matches[$q])) {
							$matches[$q][] = $onlyTwoLetters ? $acceptedValue : $availableValue;
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
	 * @param bool $onlyTwoLetters
	 * @return float
	 */
	protected static function _matchLanguage(string $a, string $b, bool $onlyTwoLetters = false) {
		$a = explode('-', strtolower($a));
		$b = explode('-', strtolower($b));
		if ($onlyTwoLetters) {
			return $a[0] === $b[0] ? 1 : 0;
		}

		for ($i = 0, $n = min(count($a), count($b)); $i < $n; $i++) {
			if ($a[$i] !== $b[$i]) {
				break;
			}
		}

		return $i === 0 ? 0 : (float)$i / count($a);
	}

}
