<?php
App::uses('AppHelper', 'View/Helper');

/**
 * CakePHP Gravatar Helper
 *
 * A CakePHP View Helper for the display of Gravatar images (http://www.gravatar.com)
 *
 * @copyright Copyright 2009-2010, Graham Weldon (http://grahamweldon.com)
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * hashtype now always md5
 */
class GravatarHelper extends AppHelper {

	/**
	 * Gravatar avatar image base URL
	 *
	 * @var string
	 */
	protected $_url = array(
		'http' => 'http://www.gravatar.com/avatar/',
		'https' => 'https://secure.gravatar.com/avatar/'
	);

	/**
	 * Collection of allowed ratings
	 *
	 * @var array
	 */
	protected $_allowedRatings = array('g', 'pg', 'r', 'x');

	/**
	 * Default Icon sets
	 *
	 * @var array
	 */
	protected $_defaultIcons = array('none', 'mm', 'identicon', 'monsterid', 'retro', 'wavatar', '404');

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_default = array(
		'default' => null,
		'size' => null,
		'rating' => null,
		'ext' => false);

	/**
	 * Helpers used by this helper
	 *
	 * @var array
	 */
	public $helpers = array('Html');

	/**
	 * Constructor
	 *
	 */
	public function __construct($View = null, $settings = array()) {
		if (!is_array($settings)) {
			$settings = array();
		}
		$this->_default = array_merge($this->_default, array_intersect_key($settings, $this->_default));

		// Default the secure option to match the current URL.
		$this->_default['secure'] = env('HTTPS');
		parent::__construct($View, $settings);
	}

	/**
	 * Show gravatar for the supplied email address
	 *
	 * @param string $email Email address
	 * @param array $options Array of options, keyed from default settings
	 * @return string Gravatar image string
	 */
	public function image($email, $options = array()) {
		$imageUrl = $this->imageUrl($email, $options);
		unset($options['default'], $options['size'], $options['rating'], $options['ext']);
		return $this->Html->image($imageUrl, $options);
	}

	/**
	 * GravatarHelper::url()
	 *
	 * @param mixed $email
	 * @param boolean $options
	 * @return void
	 * @deprecated Use imageUrl() instead.
	 */
	public function url($email = null, $options = false) {
		if ($options === false) {
			$options = array();
		}
		$this->imageUrl($email, $options);
	}

	/**
	 * Generate image URL
	 * TODO: rename to avoid E_STRICT errors here
	 *
	 * @param string $email Email address
	 * @param string $options Array of options, keyed from default settings
	 * @return string Gravatar Image URL
	 */
	public function imageUrl($email, $options = array()) {
		$options = $this->_cleanOptions(array_merge($this->_default, $options));
		$ext = $options['ext'];
		$secure = $options['secure'];
		unset($options['ext'], $options['secure']);
		$protocol = $secure === true ? 'https' : 'http';

		$imageUrl = $this->_url[$protocol] . md5($email);
		if ($ext === true) {
			// If 'ext' option is supplied and true, append an extension to the generated image URL.
			// This helps systems that don't display images unless they have a specific image extension on the URL.
			$imageUrl .= '.jpg';
		}
		$imageUrl .= $this->_buildOptions($options);
		return $imageUrl;
	}

	/**
	 * Generate an array of default images for preview purposes
	 *
	 * @param array $options Array of options, keyed from default settings
	 * @return array Default images array
	 */
	public function defaultImages($options = array()) {
		$options = $this->_cleanOptions(array_merge($this->_default, $options));
		$images = array();
		foreach ($this->_defaultIcons as $defaultIcon) {
			$options['default'] = $defaultIcon;
			$images[$defaultIcon] = $this->image(null, $options);
		}
		return $images;
	}

	/**
	 * Sanitize the options array
	 *
	 * @param array $options Array of options, keyed from default settings
	 * @return array Clean options array
	 */
	protected function _cleanOptions($options) {
		if (!isset($options['size']) || empty($options['size']) || !is_numeric($options['size'])) {
			unset($options['size']);
		} else {
			$options['size'] = min(max($options['size'], 1), 512);
		}

		if (!$options['rating'] || !in_array(mb_strtolower($options['rating']), $this->_allowedRatings)) {
			unset($options['rating']);
		}

		if (!$options['default']) {
			unset($options['default']);
		} else {
			App::uses('Validation', 'Utility');
			if (!in_array($options['default'], $this->_defaultIcons) && !Validation::url($options['default'])) {
				unset($options['default']);
			}
		}
		return $options;
	}

	/**
	 * Generate email address hash
	 *
	 * @param string $email Email address
	 * @param string $type Hash type to employ
	 * @return string Email address hash
	 */
	protected function _emailHash($email, $type) {
		return md5(mb_strtolower($email), $type);
	}

	/**
	 * Build Options URL string
	 *
	 * @param array $options Array of options, keyed from default settings
	 * @return string URL string of options
	 */
	protected function _buildOptions($options = array()) {
		$gravatarOptions = array_intersect(array_keys($options), array_keys($this->_default));
		if (!empty($gravatarOptions)) {
			$optionArray = array();
			foreach ($gravatarOptions as $key) {
				$value = $options[$key];
				$optionArray[] = $key . '=' . mb_strtolower($value);
			}
			return '?' . implode('&amp;', $optionArray);
		}
		return '';
	}
}
