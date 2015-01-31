<?php
namespace TestApp\Controller\Component;

use Shim\Controller\Component\Component;
use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * BananaComponent class
 *
 */
class BananaComponent extends Component {

/**
 * testField property
 *
 * @var string
 */
	public $testField = 'BananaField';

/**
 * startup method
 *
 * @param Event $event
 * @param Controller $controller
 * @return string
 */
	public function startup(Event $event) {
		$this->_registry->getController()->bar = 'fail';
	}

}
