<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link http://cakephp.org CakePHP(tm) Project
 * @since 0.9.1
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Tools\View\Helper;

use Cake\View\Helper\HtmlHelper as CoreHtmlHelper;

/**
 * Overwrite
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class HtmlHelper extends CoreHtmlHelper {

	use HtmlTrait;

}
