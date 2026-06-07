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
	 * Gravatar avatar image base URL.
	 *
	 * Both `http` and `https` keys point at the canonical HTTPS endpoint. Modern browsers
	 * block mixed content, so a HTTP URL embedded in an HTTPS page would silently fail to
	 * load. The legacy `secure.gravatar.com` host has been a redirect to `www.gravatar.com`
	 * for years; we use the canonical host directly.
	 *
	 * @var array
	 */
	protected array $_url = [
		'http' => 'https://www.gravatar.com/avatar/',
		'https' => 'https://www.gravatar.com/avatar/',
	];

	/**
	 * Collection of allowed ratings
	 *
	 * @var array
	 */
	protected array $_allowedRatings = [
		'g', 'pg', 'r', 'x',
	];

	/**
	 * Default Icon sets
	 *
	 * @var array
	 */
	protected array $_defaultIcons = [
		'none', 'mm', 'identicon', 'monsterid', 'retro', 'wavatar', '404',
	];

	/**
	 * Default settings
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'default' => null,
		'size' => null,
		'rating' => null,
		'ext' => false,
		'hashAlgo' => 'sha256',
	];

	/**
	 * Allowed email hash algorithms.
	 *
	 * Gravatar transitioned from MD5 to SHA-256 in 2024. SHA-256 is the default; `md5` is
	 * offered for backwards compatibility with accounts that registered before the switch
	 * (their generated default avatar is derived from the MD5 hash).
	 *
	 * @var array<string>
	 */
	protected array $_allowedHashAlgos = [
		'md5', 'sha256',
	];

	/**
	 * Helpers used by this helper
	 *
	 * @var array
	 */
	protected array $helpers = ['Html'];

	/**
	 * @param \Cake\View\View $View
	 * @param array<string, mixed> $config
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
	 * @param array<string, mixed> $options Array of options, keyed from default settings
	 * @return string Gravatar image string
	 */
	public function image(string $email, array $options = []): string {
		$imageOptions = $options += [
			'escape' => false,
		];
		$imageUrl = $this->url($email, $imageOptions);

		unset($options['default'], $options['size'], $options['rating'], $options['ext'], $options['hashAlgo']);

		return $this->Html->image($imageUrl, $options);
	}

	/**
	 * Generate image URL
	 * TODO: rename to avoid E_STRICT errors here
	 *
	 * @param string $email Email address
	 * @param array<string, mixed> $options Array of options, keyed from default settings
	 * @return string Gravatar Image URL
	 */
	public function url(string $email, array $options = []): string {
		$options = $this->_cleanOptions($options + $this->_config);
		$options += [
			'escape' => true,
		];

		$ext = $options['ext'];
		$secure = $options['secure'];
		$hashAlgo = $options['hashAlgo'];
		unset($options['ext'], $options['secure'], $options['hashAlgo']);
		$protocol = $secure === true ? 'https' : 'http';

		$imageUrl = $this->_url[$protocol] . $this->_emailHash($email, $hashAlgo);
		if ($ext === true) {
			// If 'ext' option is supplied and true, append an extension to the generated image URL.
			// This helps systems that don't display images unless they have a specific image extension on the URL.
			$imageUrl .= '.jpg';
		}

		return $imageUrl . $this->_buildOptions($options);
	}

	/**
	 * Generate an array of default images for preview purposes
	 *
	 * @param array<string, mixed> $options Array of options, keyed from default settings
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
	 * @param array<string, mixed> $options Array of options, keyed from default settings
	 * @return array Clean options array
	 */
	protected function _cleanOptions($options) {
		if (!isset($options['size']) || empty($options['size']) || !is_numeric($options['size'])) {
			unset($options['size']);
		} else {
			$options['size'] = min(max($options['size'], 1), 512);
		}

		if (!$options['rating'] || !in_array(mb_strtolower((string)$options['rating']), $this->_allowedRatings)) {
			unset($options['rating']);
		}

		if (!$options['default']) {
			unset($options['default']);
		} elseif (!in_array($options['default'], $this->_defaultIcons) && !Validation::url($options['default'])) {
			unset($options['default']);
		}

		return $options;
	}

	/**
	 * Generate the email-address hash used as the avatar identifier.
	 *
	 * Gravatar transitioned away from MD5 to SHA-256 in 2024. Both hashes still resolve
	 * for legacy MD5-mapped accounts, but new accounts only register under SHA-256.
	 * Whitespace-trimmed, lowercased input matches Gravatar's normalization rules.
	 *
	 * @param string $email Email address
	 * @param string $algo Hash algorithm (`sha256` default, `md5` for legacy accounts)
	 * @return string Email address hash (lowercase hex)
	 */
	protected function _emailHash($email, $algo = 'sha256') {
		if (!in_array($algo, $this->_allowedHashAlgos, true)) {
			$algo = 'sha256';
		}

		return hash($algo, mb_strtolower(trim($email)));
	}

	/**
	 * Build Options URL string
	 *
	 * @param array<string, mixed> $options Array of options, keyed from default settings
	 * @return string URL string of options
	 */
	protected function _buildOptions(array $options = []): string {
		$gravatarOptions = array_intersect(array_keys($options), array_keys($this->_defaultConfig));
		if (!empty($gravatarOptions)) {
			$optionArray = [];
			foreach ($gravatarOptions as $key) {
				$value = $options[$key];
				$optionArray[] = $key . '=' . mb_strtolower((string)$value);
			}

			return '?' . implode(empty($options['escape']) ? '&' : '&amp;', $optionArray);
		}

		return '';
	}

}
