<?php

namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\View\Helper;

/**
 * Common helper
 *
 * @author Mark Scherer
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CommonHelper extends Helper {

	/**
	 * @var array
	 */
	protected $helpers = ['Html', 'Url'];

	/**
	 * Auto-pluralizing a word using the Inflection class
	 * //TODO: move to lib or bootstrap
	 *
	 * @param string $singular The string to be pl.
	 * @param int $count
	 * @param bool $autoTranslate
	 * @return string "member" or "members" OR "Mitglied"/"Mitglieder" if autoTranslate TRUE
	 * @deprecated Use explicit form directly via sp()
	 */
	public function asp(string $singular, int $count, bool $autoTranslate = false): string {
		if ($count !== 1) {
			$plural = Inflector::pluralize($singular);
		} else {
			$plural = ''; // No pluralization necessary
		}

		return $this->sp($singular, $plural, $count, $autoTranslate);
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
	public function sp(string $singular, string $plural, int $count, bool $autoTranslate = false): string {
		if ($count !== 1) {
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
	public function metaRobots(?string $type = null): string {
		if ($type === null && ($meta = Configure::read('Config.robots')) !== null) {
			$type = $meta;
		}
		$content = [];
		if ($type === 'public') {
			//$this->privatePage = false;
			$content['robots'] = ['index', 'follow', 'noarchive'];
		} else {
			//$this->privatePage = true;
			$content['robots'] = ['noindex', 'nofollow', 'noarchive'];
		}

		$return = '<meta name="robots" content="' . implode(',', $content['robots']) . '" />';
		return $return;
	}

	/**
	 * Convenience method for clean meta name tags
	 *
	 * @param string|null $name Author, date, generator, revisit-after, language
	 * @param string|array|null $content If array, it will be separated by commas
	 * @return string HTML Markup
	 */
	public function metaName(?string $name = null, $content = null): string {
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
	public function metaDescription(string $content, ?string $language = null, array $options = []): string {
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
	public function metaKeywords($keywords = null, ?string $language = null, bool $escape = true): string {
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
	 * @param string|array|null $url
	 * @param bool $full
	 * @return string HTML Markup
	 */
	public function metaCanonical($url = null, bool $full = false): string {
		$canonical = $this->Url->build($url, ['full' => $full]);
		$options = ['rel' => 'canonical', 'link' => $canonical];

		return $this->Html->meta($options);
	}

	/**
	 * Convenience method for "alternate" SEO links
	 *
	 * @param string|array $url
	 * @param string|array $lang (lang(iso2) or array of langs)
	 * lang: language (in ISO 6391-1 format) + optionally the region (in ISO 3166-1 Alpha 2 format)
	 * - de
	 * - de-ch
	 * etc
	 * @param bool $full
	 * @return string HTML Markup
	 */
	public function metaAlternate($url, $lang, bool $full = false): string {
		$url = $this->Url->build($url, ['full' => $full]);
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
	 * @param string|array $url
	 * @param string|null $title
	 * @return string HTML Markup
	 */
	public function metaRss($url, ?string $title = null): string {
		$tags = [
			'meta' => '<link rel="alternate" type="application/rss+xml" title="%s" href="%s"/>',
		];
		if (empty($title)) {
			$title = __d('tools', 'Subscribe to this feed');
		} else {
			$title = h($title);
		}

		return sprintf($tags['meta'], $title, $this->Url->build($url));
	}

	/**
	 * Convenience method for meta tags.
	 *
	 * @param string $type
	 * @param string $value Content
	 * @param bool $escape
	 * @return string HTML Markup
	 */
	public function metaEquiv(string $type, string $value, bool $escape = true): string {
		$tags = [
			'meta' => '<meta http-equiv="%s"%s/>',
		];
		if ($escape) {
			$value = h($value);
		}
		return sprintf($tags['meta'], $type, ' content="' . $value . '"');
	}

}
