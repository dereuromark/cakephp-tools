<?php
App::uses('Helper', 'View');
App::uses('UrlCacheManager', 'Tools.Routing');

/**
 * Helper enhancements for Cake2
 *
 * @author Mark Scherer
 * @license MIT
 * 2012-02-27 ms
 */
class MyHelper extends Helper {

	public function __construct($View = null, $settings = array()) {
		if (class_exists('Packages')) {
			Packages::initialize($this, __CLASS__);
		}
		parent::__construct($View, $settings);
	}

/**
 * Sets the defaults for an input tag.  Will set the
 * name, value, and id attributes for an array of html attributes. Will also
 * add a 'form-error' class if the field contains validation errors.
 *
 * @param string $field The field name to initialize.
 * @param array $options Array of options to use while initializing an input field.
 * @return array Array options for the form input.
 */
	protected function _initInputField($field, $options = array()) {
		if ($field !== null) {
			$this->setEntity($field);
		}
		$options = (array)$options;
		$normalize = true;
		if (isset($options['normalize'])) {
			$normalize = $options['normalize'];
			unset($options['normalize']);
		}

		$options = $this->_name($options);
		$options = $this->value($options);
		if (!empty($options['value']) && $normalize) {
			$options['value'] = str_replace(array("\t", "\r\n", "\n"), ' ', $options['value']);
		}
		$options = $this->domId($options);
		return $options;
	}

	/**
	 * manually
	 */
	public function loadHelpers($helpers = array(), $callbacks = false) {
		foreach ((array)$helpers as $helper => $config) {
			if (is_int($helper)) {
				$helper = $config;
				$config = array();
			}
			list($plugin, $helperName) = pluginSplit($helper);
			if (isset($this->{$helperName})) {
				continue;
			}
			App::import('Helper', $helper);
			$helperFullName = $helperName.'Helper';
			$this->{$helperName} = new $helperFullName($this->_View, (array)$config);

			if ($callbacks) {
				if (method_exists($helper, 'beforeRender')) {
					$this->{$helperName}->beforeRender();
				}
			}
		}
	}

	//TODO
	/**
	 * problems: what if inside plugin webroot? not easy to do...
	 */
	public function imageIfExists($path, $options = array(), $default = '---') {
		if (startsWith($path, '/')) {
			/*
			$completePath = Router::url($path);
			//echo (returns(file_exists($completePath)));
			//die($completePath);
			# problem with plugin files!!! needs "webroot" added after plugin name
			if (!file_exists($completePath)) {
				return $default;
			}
			*/
		} else {
			$completePath = Router::url($path);
		}
		if (!empty($completePath) && !file_exists($completePath)) {
			return $default;
		}
		return $this->image($path, $options);
	}

	/**
	 * display image tag from blob content
	 * enhancement for HtmlHelper
	 * @param binary $content
	 * @param array $options
	 * @return string $html imageTag
	 * 2010-11-22 ms
	 */
	public function imageFromBlob($content, $options = array()) {
		$text = 'data:image/png;base64,' . base64_encode($content);
		$image = sprintf($this->_tags['image'], $text, $this->_parseAttributes($options, null, '', ' '));
		return $image;
	}

	/**
	 * HTML Helper extension for HTML5 time
	 * The time element represents either a time on a 24 hour clock,
	 * or a precise date in the proleptic Gregorian calendar,
	 * optionally with a time and a time-zone offset.
	 *
	 * @param $content string
	 * @param $options array
	 * - 'format' STRING: Use the specified TimeHelper method (or format()). FALSE: Generate the datetime. NULL: Do nothing.
	 * - 'datetime' STRING: If 'format' is STRING use as the formatting string. FALSE: Don't generate attribute
	 *
	 * //TODO: fixme
	 * 2011-07-17 ms
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
			App::uses('TimeHelper', 'View/Helper');
			$TimeHelper = new TimeHelper($this->_View);
		}
		if ($options['format']) {
			if (method_exists($t, $options['format'])) {
				$content = $TimeHelper->$options['format']($content);
			} else {
				$content = $TimeHelper->i18nFormat($content, $options['format']);
			}
			$options['datetime'] = $TimeHelper->i18nFormat(strtotime($content), $options['datetime']);
		} elseif ($options['format'] === false && $options['datetime']) {
			$options['datetime'] = $TimeHelper->i18nFormat(strtotime($content), $options['datetime']);
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

	# for convienience function Html::defaultLink()
	protected $linkDefaults = null;

	/*
	# only one prefix at a time
	public function url($url, $full = false) {
		if (is_array($url)) {
			$url['lang'] = 'deu';
		}
		return parent::url($url, $full);
	}
	*/

	/**
	 * convenience function for normal links
	 * useful for layout links and links inside elements etc
	 * @params same as Html::link($title, $url, $options, $confirmMessage)
	 * 2010-01-23 ms
	 */
	public function defaultLink($title, $url=null, $options=array(), $confirmMessage=false) {
		if ($this->linkDefaults === null) {
			if (!class_exists('CommonComponent')) {
				App::import('Component', 'Tools.Common');
			}
			$this->linkDefaults = CommonComponent::defaultUrlParams();
		}
		if (!defined('PREFIX_ADMIN')) {
			define('PREFIX_ADMIN', 'admin');
		}
		if ($url !== null && is_array($url)) {
			$url = array_merge($this->linkDefaults, $url);
			if (!empty($url[PREFIX_ADMIN])) {
				$options['rel'] = 'nofollow';
			}
		} elseif (is_array($title)) {
			$title = array_merge($this->linkDefaults, $title);
			if (!empty($title[PREFIX_ADMIN])) {
				$options['rel'] = 'nofollow';
			}
		}
		//$this->log($url, '404');
		return $this->link($title, $url, $options, $confirmMessage);
	}

	/**
	 * convenience function for normal urls
	 * useful for layout urls and urls inside elements etc
	 * @params same as Html::url($url, $full)
	 * 2010-01-23 ms
	 */
	public function defaultUrl($url = null, $full = false) {
		if ($this->linkDefaults === null) {
			if (!class_exists('CommonComponent')) {
				App::import('Component', 'Tools.Common');
			}
			$this->linkDefaults = CommonComponent::defaultUrlParams();
		}
		if ($url !== null && is_array($url)) {
			$url = array_merge($this->linkDefaults, $url);
		}
		return $this->url($url, $full);
	}


	public $urlHere = null;

	/**
	 * Small Helper Function to retrieve CORRECT $this->here (as it should be) - CAKE BUG !? -> this is a fix
	 * 2009-01-06 ms
	 */
	public function here() {
		if (empty($this->urlHere) && isset($_GET['url'])) {
			$this->urlHere = $_GET['url'];
			if (strpos($this->urlHere, '/') !== 0) {
				$this->urlHere = '/'.$this->urlHere;
			}
		}
		return $this->urlHere;
	}


	/**
	 * enhancement to htmlHelper which allows the crumbs protected array
	 * to be cleared so that more than one set of crumbs can be generated in the same view.
	 *
	 * @return void
	 * 2009-08-05 ms
	 */
	public function resetCrumbs() {
		$this->_crumbs = array();
	}



/** deprecated */

	/**
	 * @deprecated
	 */
	public function nl2p($text, $options = array(), $enforceMaxLength = true) {
		$pS = $this->Html->tag('p', null, $options);
		$pE = '</p>';
		if (!empty($text)) {
			// Max length auto line break, if enabled
			if ($enforceMaxLength) {
				$maxLength = null;
				if (isset($options['maxLength'])) {
					$maxLength = (int)$options['maxLength'];
				}
				$text = $this->maxLength($text, $maxLength);
			}
			// Replace double newlines with <p>
			$text = $pS . preg_replace('#(\r?\n) {2,}(\s+)?#u', $pE . $pS, $text) . $pE;
			// Replace single newlines with <br>
			$text = preg_replace('#\r?\n#u', BR, $text);
			// Add newlines to sourcecode for sourcode readability
			$text = preg_replace(
				array(
					'#' . $pE . '#u', // Matches $pE (like </p>)
					'#' . BR . '#u', // Matches $br (like <br />)
				),
				array(
					$pE . "\n",
					BR . "\n",
				),
				$text);
		}
		return $text;
	}

	/**
	 * This function is responsible for setting up the Url cache before the application starts generating urls in views
	 *
	 * @return void
	 */
	public function beforeRender($viewFile) {
		if (!Configure::read('UrlCache.active') || Configure::read('UrlCache.runtime.beforeRender')) {
			return;
		}

		# todo: maybe lazy load with HtmlHelper::url()?
		UrlCacheManager::init($this->_View);
		Configure::write('UrlCache.runtime.beforeRender', true);
	}

	/**
	 * This method will store the current generated urls into a persistent cache for next use
	 *
	 * @return void
	 */
	public function afterLayout($layoutFile = null) {
		if (!Configure::read('UrlCache.active') || Configure::read('UrlCache.runtime.afterLayout')) {
			return;
		}

		UrlCacheManager::finalize();
		Configure::write('UrlCache.runtime.afterLayout', true);
	}

	/**
	 * Intercepts the parent url function to first look if the cache was already generated for the same params
	 *
	 * @param mixed $url url to generate using cakephp array syntax
	 * @param boolean $full wheter to generate a full url or not (http scheme)
	 * @return string
	 * @see Helper::url()
	 */
	public function url($url = null, $full = false, $escape = true) {
		if (Configure::read('UrlCache.active')) {
			if ($cachedUrl = UrlCacheManager::get($url, $full)) {
				return $cachedUrl;
			}
		}

		$routerUrl = parent::url($url, $full, $escape);
		if (Configure::read('UrlCache.active')) {
			UrlCacheManager::set($routerUrl);
		}
		return $routerUrl;
	}

}
