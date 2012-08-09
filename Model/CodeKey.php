<?php
App::uses('Token', 'Tools.Model');
App::uses('CommonComponent', 'Tools.Controller/Component');

/**
 * @deprecated - use "Token" class
 *
 * @author Mark Scherer
 * @cakephp 2.0
 * @license MIT
 * 2011-11-17 ms
 */
class CodeKey extends Token {

	public $order = array('CodeKey.created' => 'DESC');

}

