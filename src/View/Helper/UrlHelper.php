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
 */
class UrlHelper extends CoreUrlHelper {

	/**
	 * @deprecated
	 * @param string|array|null $url URL.
	 * @param bool $full
	 * @return string
	 */
	public function defaultBuild($url = null, $full = false) {
		return $this->reset($url, $full);
	}

	/**
	 * Creates a reset URL.
	 * The prefix and plugin params are resetting to default false.
	 *
	 * @param string|array|null $url URL.
	 * @param bool $full If true, the full base URL will be prepended to the result
	 * @return string Full translated URL with base path.
	 */
	public function reset($url = null, $full = false) {
		if (is_array($url)) {
			$url += ['prefix' => false, 'plugin' => false];
		}
		return parent::build($url, $full);
	}

	/**
	 * Returns a URL based on provided parameters.
	 *
	 * @param string|array|null $url URL.
	 * @param bool $full If true, the full base URL will be prepended to the result
	 * @return string Full translated URL with base path.
	 */
	public function complete($url = null, $full = false) {
		if (is_array($url)) {
			// Add query strings
			if (!isset($url['?'])) {
				$url['?'] = [];
			}
			$url['?'] += $this->request->query;
		}
		return parent::build($url, $full);
	}

	/**
	 * Event listeners.
	 *
	 * @return array
	 */
	public function implementedEvents() {
		return [];
	}

}
