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
 * @since 3.1.0
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Tools\Mailer;

use Cake\Core\App;
use Cake\Mailer\Exception\MissingMailerException;

/**
 * Provides functionality for loading mailer classes
 * onto properties of the host object.
 *
 * Example users of this trait are Cake\Controller\Controller and
 * Cake\Console\Shell.
 */
trait MailerAwareTrait {

	/**
	 * Returns a mailer instance.
	 *
	 * @param string $name Mailer's name.
	 * @param array|null $config
	 * @throws \Cake\Mailer\Exception\MissingMailerException if undefined mailer class.
	 * @return \Cake\Mailer\Mailer
	 */
	public function getMailer($name, $config = null) {
		$className = App::className($name, 'Mailer', 'Mailer');

		if (!$className) {
			throw new MissingMailerException(compact('name'));
		}

		return new $className($config);
	}

}
