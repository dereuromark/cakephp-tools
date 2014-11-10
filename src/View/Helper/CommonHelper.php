<?php
namespace Tools\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;

/**
 * Common helper
 *
 * @author Mark Scherer
 */
class CommonHelper extends Helper {

	public $helpers = array('Session', 'Html');

	/**
	 * Display all flash messages.
	 *
	 * TODO: export div wrapping method (for static messaging on a page)
	 *
	 * @param array $types Types to output. Defaults to all if none are specified.
	 * @return string HTML
	 */
	public function flash(array $types = array()) {
		// Get the messages from the session
		$messages = (array)$this->Session->read('messages');
		$cMessages = (array)Configure::read('messages');
		if (!empty($cMessages)) {
			$messages = (array)Hash::merge($messages, $cMessages);
		}
		$html = '';
		if (!empty($messages)) {
			$html = '<div class="flash-messages flashMessages">';

			if ($types) {
				foreach ($types as $type) {
					// Add a div for each message using the type as the class.
					foreach ($messages as $messageType => $msgs) {
						if ($messageType !== $type) {
							continue;
						}
						foreach ((array)$msgs as $msg) {
							$html .= $this->_message($msg, $messageType);
						}
					}
				}
			} else {
				foreach ($messages as $messageType => $msgs) {
					foreach ((array)$msgs as $msg) {
						$html .= $this->_message($msg, $messageType);
					}
				}
			}
			$html .= '</div>';
			if ($types) {
				foreach ($types as $type) {
					CakeSession::delete('messages.' . $type);
					Configure::delete('messages.' . $type);
				}
			} else {
				CakeSession::delete('messages');
				Configure::delete('messages');
			}
		}

		return $html;
	}

	/**
	 * Outputs a single flashMessage directly.
	 * Note that this does not use the Session.
	 *
	 * @param string $message String to output.
	 * @param string $type Type (success, warning, error, info)
	 * @param bool $escape Set to false to disable escaping.
	 * @return string HTML
	 */
	public function flashMessage($msg, $type = 'info', $escape = true) {
		$html = '<div class="flash-messages flashMessages">';
		if ($escape) {
			$msg = h($msg);
		}
		$html .= $this->_message($msg, $type);
		$html .= '</div>';
		return $html;
	}

	/**
	 * Formats a message
	 *
	 * @param string $msg Message to output.
	 * @param string $type Type that will be formatted to a class tag.
	 * @return string
	 */
	protected function _message($msg, $type) {
		if (!empty($msg)) {
			return '<div class="message' . (!empty($type) ? ' ' . $type : '') . '">' . $msg . '</div>';
		}
		return '';
	}

	/**
	 * Add a message on the fly
	 *
	 * @param string $msg
	 * @param string $class
	 * @return void
	 */
	public function addFlashMessage($msg, $class = null) {
		CommonComponent::transientFlashMessage($msg, $class);
	}

	/**
	 * CommonHelper::transientFlashMessage()
	 *
	 * @param mixed $msg
	 * @param mixed $class
	 * @return void
	 * @deprecated Use addFlashMessage() instead
	 */
	public function transientFlashMessage($msg, $class = null) {
		$this->addFlashMessage($msg, $class);
	}

	/**
	 * Auto-pluralizing a word using the Inflection class
	 * //TODO: move to lib or bootstrap
	 *
	 * @param string $singular The string to be pl.
	 * @param int $count
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
	 * @param string $type - private/public
	 * @return string HTML
	 */
	public function metaRobots($type = null) {
		if ($type === null && ($meta = Configure::read('Config.robots')) !== null) {
			$type = $meta;
		}
		$content = array();
		if ($type === 'public') {
			$this->privatePage = false;
			$content['robots'] = array('index', 'follow', 'noarchive');

		} else {
			$this->privatePage = true;
			$content['robots'] = array('noindex', 'nofollow', 'noarchive');
		}

		$return = '<meta name="robots" content="' . implode(',', $content['robots']) . '" />';
		return $return;
	}

	/**
	 * Convenience method for clean meta name tags
	 *
	 * @param string $name: author, date, generator, revisit-after, language
	 * @param mixed $content: if array, it will be seperated by commas
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
	 * @param string $language (iso2: de, en-us, ...)
	 * @param array $additionalOptions
	 * @return string HTML Markup
	 */
	public function metaDescription($content, $language = null, $options = array()) {
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
	 * @param string|array $keywords
	 * @param string $language (iso2: de, en-us, ...)
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
		$canonical = $this->Html->url($url, $full);
		$options = array('rel' => 'canonical', 'type' => null, 'title' => null);
		return $this->Html->meta('canonical', $canonical, $options);
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
	 * @return string HTML Markup
	 */
	public function metaAlternate($url, $lang, $full = false) {
		//$canonical = $this->Html->url($url, $full);
		$url = $this->Html->url($url, $full);
		//return $this->Html->meta('canonical', $canonical, array('rel'=>'canonical', 'type'=>null, 'title'=>null));
		$lang = (array)$lang;
		$res = array();
		foreach ($lang as $language => $countries) {
			if (is_numeric($language)) {
				$language = '';
			} else {
				$language .= '-';
			}
			$countries = (array)$countries;
			foreach ($countries as $country) {
				$l = $language . $country;
				$options = array('rel' => 'alternate', 'hreflang' => $l, 'type' => null, 'title' => null);
				$res[] = $this->Html->meta('alternate', $url, $options) . PHP_EOL;
			}
		}
		return implode('', $res);
	}

	/**
	 * Convenience method for META Tags
	 *
	 * @param mixed $url
	 * @param string $title
	 * @return string HTML Markup
	 */
	public function metaRss($url, $title = null) {
		$tags = array(
			'meta' => '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
		);
		if (empty($title)) {
			$title = __('Subscribe to this feed');
		} else {
			$title = h($title);
		}

		return sprintf($tags['meta'], $title, $this->url($url));
	}

	/**
	 * Convenience method for META Tags
	 *
	 * @param string $type
	 * @param string $content
	 * @return string HTML Markup
	 */
	public function metaEquiv($type, $value, $escape = true) {
		$tags = array(
			'meta' => '<meta http-equiv="%s"%s />',
		);
		if ($value === null) {
			return '';
		}
		if ($escape) {
			$value = h($value);
		}
		return sprintf($tags['meta'], $type, ' content="' . $value . '"');
	}

}
