<?php

namespace TestApp\Controller\Component;

use Cake\Event\Event;
use Shim\Controller\Component\Component;

/**
 * AppleComponent class
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
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function startup(Event $event) {
	}

}
