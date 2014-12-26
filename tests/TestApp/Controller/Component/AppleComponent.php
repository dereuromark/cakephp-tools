<?php
namespace TestApp\Controller\Component;

use Tools\Controller\Component\Component;
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
	public $components = array('Banana');

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
