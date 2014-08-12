<?php
App::uses('Token', 'Tools.Model');

/**
 * @deprecated - use "Token" class
 *
 * @author Mark Scherer
 * @license MIT
 */
class CodeKey extends Token {

	public $order = array('CodeKey.created' => 'DESC');

}
