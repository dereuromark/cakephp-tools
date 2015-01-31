<?php
namespace TestApp\Controller\Component;

use Shim\Controller\Component\Component;
use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * AppleComponent class
 *
 */
class AppleComponent extends Component {

/**
 * components property
 *
 * @var array
 */
	public $components = ['Banana'];

/**
 * startup method
 *
 * @param Event $event
 * @param mixed $controller
 * @return void
 */
	public function startup(Event $event) {
	}

}
