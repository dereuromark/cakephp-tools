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

use Cake\Core\Configure;
use Cake\Network\Response;
use Cake\View\Helper\UrlHelper as CoreUrlHelper;
use Cake\View\StringTemplateTrait;
use Cake\View\View;

/**
 * Url Helper class.
 */
class UrlHelper extends CoreUrlHelper {

	/**
	 * Returns a URL based on provided parameters.
	 *
	 * @param string|array $url Either a relative string url like `/products/view/23` or
	 *    an array of URL parameters. Using an array for URLs will allow you to leverage
	 *    the reverse routing features of CakePHP.
	 * @param bool $full If true, the full base URL will be prepended to the result
	 * @return string Full translated URL with base path.
	 */
	public function defaultBuild($url = null, $full = false) {
		if (is_array($url)) {
			$url += ['prefix' => false, 'plugin' => false];
		}
		return parent::build($url, $full);
	}

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
