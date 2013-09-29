<?php
App::uses('Token', 'Tools.Model');

/**
 * @deprecated - use "Token" class
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 */
class CodeKey extends Token {

	public $order = array('CodeKey.created' => 'DESC');

}
