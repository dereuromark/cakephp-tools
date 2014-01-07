<?php
App::uses('HtmlHelper', 'View/Helper');

/**
 * HtmlExt Helper
 *
 * Provides additional functionality for HtmlHelper.
 * Use with aliasing to map it back to $this->Html attribute.
 *
 * @author Mark Scherer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class HtmlExtHelper extends HtmlHelper {

	/**
	 * For convenience functions Html::defaultLink() and defaultUrl().
	 *
	 * @var array
	 */
	protected $_linkDefaults = null;

	/**
	 * Display image tag from blob content.
	 * Enhancement for HtmlHelper. Defaults to png image
	 *
	 * Options:
	 * - type: png, gif, jpg, ...
	 *
	 * @param binary $content
	 * @param array $options
	 * @return string html imageTag
	 */
	public function imageFromBlob($content, $options = array()) {
		$options += array('type' => 'png');
		$mimeType = 'image/' . $options['type'];

		$text = 'data:' . $mimeType . ';base64,' . base64_encode($content);
		return sprintf($this->_tags['image'], $text, $this->_parseAttributes($options, null, '', ' '));
	}

	/**
	 * HTML Helper extension for HTML5 time
	 * The time element represents either a time on a 24 hour clock,
	 * or a precise date in the proleptic Gregorian calendar,
	 * optionally with a time and a time-zone offset.
	 *
	 * Options:
	 * - 'format' STRING: Use the specified TimeHelper method (or format()).
	 *   FALSE: Generate the datetime. NULL: Do nothing.
	 * - 'datetime' STRING: If 'format' is STRING use as the formatting string.
	 *   FALSE: Don't generate attribute
	 *
	 * @param $content string Time
	 * @param $options array Options
	 * @return string HTML time tag.
	 */
	public function time($content, $options = array()) {
		if (!isset($this->tags['time'])) {
			$this->tags['time'] = '<time%s>%s</time>';
		}
		$options = array_merge(array(
			'datetime' => '%Y-%m-%d %T',
			'pubdate' => false,
			'format' => '%Y-%m-%d %T',
		), $options);

		if ($options['format'] !== null) {
			if (!isset($this->Time)) {
				App::uses('TimeHelper', 'View/Helper');
				$this->Time = new TimeHelper($this->_View);
			}
		}
		if ($options['format']) {
			if (method_exists($this->Time, $options['format'])) {
				$content = $this->Time->$options['format']($content);
			} else {
				$content = $this->Time->i18nFormat($content, $options['format']);
			}
			$options['datetime'] = $this->Time->i18nFormat(strtotime($content), $options['datetime']);
		} elseif ($options['format'] === false && $options['datetime']) {
			$options['datetime'] = $this->Time->i18nFormat(strtotime($content), $options['datetime']);
		}

		if ($options['pubdate']) {
			$pubdate = true;
		}
		unset($options['format']);
		unset($options['pubdate']);
		$attributes = $this->_parseAttributes($options, array(0), ' ', '');

		if (isset($pubdate)) {
			$attributes .= ' pubdate';
		}
		return sprintf($this->tags['time'], $attributes, $content);
	}

	/**
	 * Keep named and query params for pagination/filter after edit etc.
	 *
	 * @params same as Html::link($title, $url, $options, $confirmMessage)
	 * @return string Link
	 */
	public function completeLink($title, $url = null, $options = array(), $confirmMessage = false) {
		// Named are deprecated
		if (is_array($url)) {
			$url += $this->params['named'];
		}
		if (is_array($url)) {
			if (!isset($url['?'])) {
				$url['?'] = array();
			}
			$url['?'] += $this->request->query;
		}
		return $this->link($title, $url, $options, $confirmMessage);
	}

	/**
	 * Keep named and query params for pagination/filter after edit etc.
	 *
	 * @params same as Html::url($url, $options, $escape)
	 * @return string Link
	 */
	public function completeUrl($url = null, $full = false, $escape = true) {
		// Named are deprecated
		if (is_array($url)) {
			$url += $this->params['named'];
		}
		if (is_array($url)) {
			if (!isset($url['?'])) {
				$url['?'] = array();
			}
			$url['?'] += $this->request->query;
		}
		return $this->url($url, $options, $escape);
	}

	/**
	 * Convenience function for normal links.
	 * Useful for layout links and links inside elements etc if you don't want to
	 * verbosely reset all parts of it (prefix, plugin, ...).
	 *
	 * @params same as Html::link($title, $url, $options, $confirmMessage)
	 * @return string HTML Link
	 */
	public function defaultLink($title, $url = null, $options = array(), $confirmMessage = false) {
		if ($this->_linkDefaults === null) {
			if (!class_exists('CommonComponent')) {
				App::uses('CommonComponent', 'Tools.Controller/Component');
			}
			$this->_linkDefaults = CommonComponent::defaultUrlParams();
		}
		if (!defined('PREFIX_ADMIN')) {
			define('PREFIX_ADMIN', 'admin');
		}
		if ($url !== null && is_array($url)) {
			$url = array_merge($this->_linkDefaults, $url);
			if (!empty($url[PREFIX_ADMIN])) {
				$options['rel'] = 'nofollow';
			}
		} elseif (is_array($title)) {
			$title = array_merge($this->_linkDefaults, $title);
			if (!empty($title[PREFIX_ADMIN])) {
				$options['rel'] = 'nofollow';
			}
		}
		//$this->log($url, '404');
		return $this->link($title, $url, $options, $confirmMessage);
	}

	/**
	 * Convenience function for normal urls.
	 * Useful for layout links and links inside elements etc if you don't want to
	 * verbosely reset all parts of it (prefix, plugin, ...).
	 *
	 * @params same as Html::url($url, $full)
	 * @return string URL
	 */
	public function defaultUrl($url = null, $full = false) {
		if ($this->_linkDefaults === null) {
			if (!class_exists('CommonComponent')) {
				App::uses('CommonComponent', 'Tools.Controller/Component');
			}
			$this->_linkDefaults = CommonComponent::defaultUrlParams();
		}
		if ($url !== null && is_array($url)) {
			$url = array_merge($this->_linkDefaults, $url);
		}
		return $this->url($url, $full);
	}

	/**
	 * Enhancement to htmlHelper which allows the crumbs protected array
	 * to be cleared so that more than one set of crumbs can be generated in the same view.
	 *
	 * @return void
	 */
	public function resetCrumbs() {
		$this->_crumbs = array();
	}

}
