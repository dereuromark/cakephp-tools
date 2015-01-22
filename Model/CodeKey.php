<?php
App::uses('Token', 'Tools.Model');

/**
 * @deprecated - use "Token" class
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class CodeKey extends Token {

	public $order = ['CodeKey.created' => 'DESC'];

}
