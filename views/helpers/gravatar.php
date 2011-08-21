<?php
App::import(array('Validation'));

/**
 * CakePHP Gravatar Helper
 *
 * A CakePHP View Helper for the display of Gravatar images (http://www.gravatar.com)
 *
 * @copyright Copyright 2009-2010, Graham Weldon (http://grahamweldon.com)
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package goodies
 * @subpackage goodies.views.helpers
 * 
 * hashtype now always md5
 * 2010-12-21 ms
 */
class GravatarHelper extends AppHelper {

/**
 * Gravatar avatar image base URL
 *
 * @var string
 */
	private $__url = array(
		'http' => 'http://www.gravatar.com/avatar/',
		'https' => 'https://secure.gravatar.com/avatar/'
	);

/**
 * Collection of allowed ratings
 *
 * @var array
 */
	private $__allowedRatings = array('g', 'pg', 'r', 'x');

/**
 * Default Icon sets
 *
 * @var array
 */
	private $__defaultIcons = array('none', 'mm', 'identicon', 'monsterid', 'retro', 'wavatar', '404');

/**
 * Default settings
 *
 * @var array
 */
	private $__default = array(
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
	public function __construct($settings = array()) {
		if (!is_array($settings)) {
			$settings = array();
		}
		$this->__default = array_merge($this->__default, array_intersect_key($settings, $this->__default));

		// Default the secure option to match the current URL.
		$this->__default['secure'] = env('HTTPS');
		parent::__construct();
	}

/**
 * Show gravatar for the supplied email address
 *
 * @param string $email Email address
 * @param array $options Array of options, keyed from default settings
 * @return string Gravatar image string
 */
	public function image($email, $options = array()) {
		$imageUrl = $this->url($email, $options);
		unset($options['default'], $options['size'], $options['rating'], $options['ext']);
		return $this->Html->image($imageUrl, $options);
	}

/**
 * Generate image URL
 *
 * @param string $email Email address
 * @param string $options Array of options, keyed from default settings
 * @return string Gravatar Image URL
 */
	public function url($email, $options = array()) {
		$options = $this->__cleanOptions(array_merge($this->__default, $options));
		$ext = $options['ext'];
		$secure = $options['secure'];
		unset($options['ext'], $options['secure']);
		$protocol = $secure === true ? 'https' : 'http';

		$imageUrl = $this->__url[$protocol] . md5($email);
		if ($ext === true) {
			// If 'ext' option is supplied and true, append an extension to the generated image URL.
			// This helps systems that don't display images unless they have a specific image extension on the URL.
			$imageUrl .= '.jpg';
		}
		$imageUrl .= $this->__buildOptions($options);
		return $imageUrl;
	}

/**
 * Generate an array of default images for preview purposes
 *
 * @param array $options Array of options, keyed from default settings
 * @return array Default images array
 * @access public
 */
	public function defaultImages($options = array()) {
		$options = $this->__cleanOptions(array_merge($this->__default, $options));
		$images = array();
		foreach ($this->__defaultIcons as $defaultIcon) {
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
	private function __cleanOptions($options) {
		if (!isset($options['size']) || empty($options['size']) || !is_numeric($options['size'])) {
			unset($options['size']);
		} else {
			$options['size'] = min(max($options['size'], 1), 512);
		}

		if (!$options['rating'] || !in_array(mb_strtolower($options['rating']), $this->__allowedRatings)) {
			unset($options['rating']);
		}

		if (!$options['default']) {
			unset($options['default']);
		} else {
			if (!in_array($options['default'], $this->__defaultIcons) && !Validation::url($options['default'])) {
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
	private function __emailHash($email, $type) {
		return md5(mb_strtolower($email), $type);
	}

/**
 * Build Options URL string
 *
 * @param array $options Array of options, keyed from default settings
 * @return string URL string of options
 */
	private function __buildOptions($options = array()) {
		$gravatarOptions = array_intersect(array_keys($options), array_keys($this->__default));
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