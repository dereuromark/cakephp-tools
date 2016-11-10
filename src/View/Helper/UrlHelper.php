<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.9.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Tools\View\Helper;

use Cake\View\Helper\UrlHelper as CoreUrlHelper;

/**
 * Url Helper class.
 *
 * @author Mark Scherer
 * @license MIT
 */
class UrlHelper extends CoreUrlHelper {

	/**
	 * @param array $url
	 * @return array
	 */
	public function resetArray(array $url) {
		$url += $this->defaults();

		return $url;
	}

	/**
	 * @param array $url
	 * @return array
	 */
	public function completeArray(array $url) {
		$url = $this->addQueryStrings($url);

		return $url;
	}

	/**
	 * Creates a reset URL.
	 * The prefix and plugin params are resetting to default false.
	 *
	 * Can only add defaults for array URLs.
	 *
	 * @param string|array|null $url URL.
	 * @param bool $full If true, the full base URL will be prepended to the result
	 * @return string Full translated URL with base path.
	 */
	public function reset($url = null, $full = false) {
		if (is_array($url)) {
			$url += $this->defaults();
		}

		return parent::build($url, $full);
	}

	/**
	 * @return array
	 */
	public function defaults() {
		return [
			'prefix' => false,
			'plugin' => false
		];
	}

	/**
	 * Returns a URL based on provided parameters.
	 *
	 * Can only add query strings for array URLs.
	 *
	 * @param string|array|null $url URL.
	 * @param bool $full If true, the full base URL will be prepended to the result
	 * @return string Full translated URL with base path.
	 */
	public function complete($url = null, $full = false) {
		if (is_array($url)) {
			$url = $this->addQueryStrings($url);
		}

		return parent::build($url, $full);
	}

	/**
	 * @param array $url
	 *
	 * @return array
	 */
	protected function addQueryStrings(array $url) {
		if (!isset($url['?'])) {
			$url['?'] = [];
		}
		$url['?'] += $this->request->query;

		return $url;
	}

}
