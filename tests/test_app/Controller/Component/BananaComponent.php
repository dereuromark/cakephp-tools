<?php

namespace TestApp\Controller\Component;

use Cake\Event\Event;
use Shim\Controller\Component\Component;

/**
 * BananaComponent class
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
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function startup(Event $event) {
		$this->_registry->getController()->bar = 'fail';
	}

}
