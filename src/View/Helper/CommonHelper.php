<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * Common helper
 *
 * @author Mark Scherer
 */
class CommonHelper extends Helper {

	/**
	 * @var array
	 */
	public $helpers = ['Html', 'Url'];

	/**
	 * Auto-pluralizing a word using the Inflection class
	 * //TODO: move to lib or bootstrap
	 *
	 * @param string $singular The string to be pl.
	 * @param int $count
	 * @param bool $autoTranslate
	 * @return string "member" or "members" OR "Mitglied"/"Mitglieder" if autoTranslate TRUE
	 */
	public function asp($singular, $count, $autoTranslate = false) {
		if ((int)$count !== 1) {
			$pural = Inflector::pluralize($singular);
		} else {
			$pural = null; // No pluralization necessary
		}
		return $this->sp($singular, $pural, $count, $autoTranslate);
	}

	/**
	 * Manual pluralizing a word using the Inflection class
	 * //TODO: move to lib or bootstrap
	 *
	 * @param string $singular
	 * @param string $plural
	 * @param int $count
	 * @param bool $autoTranslate
	 * @return string result
	 */
	public function sp($singular, $plural, $count, $autoTranslate = false) {
		if ((int)$count !== 1) {
			$result = $plural;
		} else {
			$result = $singular;
		}

		if ($autoTranslate) {
			$result = __($result);
		}
		return $result;
	}

	/**
	 * Convenience method for clean ROBOTS allowance
	 *
	 * @param string|null $type - private/public
	 * @return string HTML
	 */
	public function metaRobots($type = null) {
		if ($type === null && ($meta = Configure::read('Config.robots')) !== null) {
			$type = $meta;
		}
		$content = [];
		if ($type === 'public') {
			$this->privatePage = false;
			$content['robots'] = ['index', 'follow', 'noarchive'];
		} else {
			$this->privatePage = true;
			$content['robots'] = ['noindex', 'nofollow', 'noarchive'];
		}

		$return = '<meta name="robots" content="' . implode(',', $content['robots']) . '" />';
		return $return;
	}

	/**
	 * Convenience method for clean meta name tags
	 *
	 * @param string|null $name Author, date, generator, revisit-after, language
	 * @param mixed|null $content If array, it will be separated by commas
	 * @return string HTML Markup
	 */
	public function metaName($name = null, $content = null) {
		if (empty($name) || empty($content)) {
			return '';
		}

		$content = (array)$content;
		$return = '<meta name="' . $name . '" content="' . implode(', ', $content) . '" />';
		return $return;
	}

	/**
	 * Convenience method for meta description
	 *
	 * @param string $content
	 * @param string|null $language (iso2: de, en-us, ...)
	 * @param array $options Additional options
	 * @return string HTML Markup
	 */
	public function metaDescription($content, $language = null, $options = []) {
		if (!empty($language)) {
			$options['lang'] = mb_strtolower($language);
		} elseif ($language !== false) {
			$options['lang'] = Configure::read('Config.locale');
		}
		return $this->Html->meta('description', $content, $options);
	}

	/**
	 * Convenience method to output meta keywords
	 *
	 * @param string|array|null $keywords
	 * @param string|null $language (iso2: de, en-us, ...)
	 * @param bool $escape
	 * @return string HTML Markup
	 */
	public function metaKeywords($keywords = null, $language = null, $escape = true) {
		if ($keywords === null) {
			$keywords = Configure::read('Config.keywords');
		}
		if (is_array($keywords)) {
			$keywords = implode(', ', $keywords);
		}
		if ($escape) {
			$keywords = h($keywords);
		}
		$options = [];
		if (!empty($language)) {
			$options['lang'] = mb_strtolower($language);
		} elseif ($language !== false) {
			$options['lang'] = Configure::read('Config.locale');
		}
		return $this->Html->meta('keywords', $keywords, $options);
	}

	/**
	 * Convenience function for "canonical" SEO links
	 *
	 * @param mixed $url
	 * @param bool $full
	 * @return string HTML Markup
	 */
	public function metaCanonical($url = null, $full = false) {
		$canonical = $this->Url->build($url, $full);
		$options = ['rel' => 'canonical', 'link' => $canonical];
		return $this->Html->meta($options);
	}

	/**
	 * Convenience method for "alternate" SEO links
	 *
	 * @param mixed $url
	 * @param mixed $lang (lang(iso2) or array of langs)
	 * lang: language (in ISO 6391-1 format) + optionally the region (in ISO 3166-1 Alpha 2 format)
	 * - de
	 * - de-ch
	 * etc
	 * @param bool $full
	 * @return string HTML Markup
	 */
	public function metaAlternate($url, $lang, $full = false) {
		//$canonical = $this->Url->build($url, $full);
		$url = $this->Url->build($url, $full);
		//return $this->Html->meta('canonical', $canonical, array('rel'=>'canonical', 'type'=>null, 'title'=>null));
		$lang = (array)$lang;
		$res = [];
		foreach ($lang as $language => $countries) {
			if (is_numeric($language)) {
				$language = '';
			} else {
				$language .= '-';
			}
			$countries = (array)$countries;
			foreach ($countries as $country) {
				$l = $language . $country;
				$options = ['rel' => 'alternate', 'hreflang' => $l, 'link' => $url];
				$res[] = $this->Html->meta($options) . PHP_EOL;
			}
		}
		return implode('', $res);
	}

	/**
	 * Convenience method for META Tags
	 *
	 * @param mixed $url
	 * @param string|null $title
	 * @return string HTML Markup
	 */
	public function metaRss($url, $title = null) {
		$tags = [
			'meta' => '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
		];
		if (empty($title)) {
			$title = __d('tools', 'Subscribe to this feed');
		} else {
			$title = h($title);
		}

		return sprintf($tags['meta'], $title, $this->Url->build($url));
	}

	/**
	 * Convenience method for META Tags
	 *
	 * @param string $type
	 * @param string $value Content
	 * @param bool $escape
	 * @return string HTML Markup
	 */
	public function metaEquiv($type, $value, $escape = true) {
		$tags = [
			'meta' => '<meta http-equiv="%s"%s />',
		];
		if ($value === null) {
			return '';
		}
		if ($escape) {
			$value = h($value);
		}
		return sprintf($tags['meta'], $type, ' content="' . $value . '"');
	}

}
