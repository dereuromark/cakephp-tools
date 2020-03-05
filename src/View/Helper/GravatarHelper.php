<?php

namespace Tools\View\Helper;

use Cake\Validation\Validation;
use Cake\View\Helper;
use Cake\View\View;

/**
 * CakePHP Gravatar Helper
 *
 * A CakePHP View Helper for the display of Gravatar images (http://www.gravatar.com)
 *
 * @copyright Copyright 2009-2010, Graham Weldon (http://grahamweldon.com)
 * @license http://opensource.org/licenses/mit-license.php MIT
 * @author Mark Scherer
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class GravatarHelper extends Helper {

	/**
	 * Gravatar avatar image base URL
	 *
	 * @var array
	 */
	protected $_url = [
		'http' => 'http://www.gravatar.com/avatar/',
		'https' => 'https://secure.gravatar.com/avatar/',
	];

	/**
	 * Collection of allowed ratings
	 *
	 * @var array
	 */
	protected $_allowedRatings = [
		'g', 'pg', 'r', 'x'];

	/**
	 * Default Icon sets
	 *
	 * @var array
	 */
	protected $_defaultIcons = [
		'none', 'mm', 'identicon', 'monsterid', 'retro', 'wavatar', '404'];

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'default' => null,
		'size' => null,
		'rating' => null,
		'ext' => false];

	/**
	 * Helpers used by this helper
	 *
	 * @var array
	 */
	protected $helpers = ['Html'];

	/**
	 * @param \Cake\View\View $View
	 * @param array $config
	 */
	public function __construct(View $View, array $config = []) {
		// Default the secure option to match the current URL.
		$this->_defaultConfig['secure'] = (bool)env('HTTPS');

		parent::__construct($View, $config);
	}

	/**
	 * Show gravatar for the supplied email address
	 *
	 * @param string $email Email address
	 * @param array $options Array of options, keyed from default settings
	 * @return string Gravatar image string
	 */
	public function image($email, array $options = []) {
		$imageOptions = $options += [
			'escape' => false,
		];
		$imageUrl = $this->url($email, $imageOptions);

		unset($options['default'], $options['size'], $options['rating'], $options['ext']);

		return $this->Html->image($imageUrl, $options);
	}

	/**
	 * Generate image URL
	 * TODO: rename to avoid E_STRICT errors here
	 *
	 * @param string $email Email address
	 * @param array $options Array of options, keyed from default settings
	 * @return string Gravatar Image URL
	 */
	public function url($email, array $options = []) {
		$options = $this->_cleanOptions($options + $this->_config);
		$options += [
			'escape' => true,
		];

		$ext = $options['ext'];
		$secure = $options['secure'];
		unset($options['ext'], $options['secure']);
		$protocol = $secure === true ? 'https' : 'http';

		$imageUrl = $this->_url[$protocol] . $this->_emailHash($email);
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
	public function defaultImages($options = []) {
		$options = $this->_cleanOptions($options + $this->_config);
		$images = [];
		foreach ($this->_defaultIcons as $defaultIcon) {
			$options['default'] = $defaultIcon;
			$images[$defaultIcon] = $this->image('', $options);
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
	 * @return string Email address hash
	 */
	protected function _emailHash($email) {
		return md5(mb_strtolower($email));
	}

	/**
	 * Build Options URL string
	 *
	 * @param array $options Array of options, keyed from default settings
	 * @return string URL string of options
	 */
	protected function _buildOptions($options = []) {
		$gravatarOptions = array_intersect(array_keys($options), array_keys($this->_defaultConfig));
		if (!empty($gravatarOptions)) {
			$optionArray = [];
			foreach ($gravatarOptions as $key) {
				$value = $options[$key];
				$optionArray[] = $key . '=' . mb_strtolower($value);
			}
			return '?' . implode(!empty($options['escape']) ? '&amp;' : '&', $optionArray);
		}
		return '';
	}

}
