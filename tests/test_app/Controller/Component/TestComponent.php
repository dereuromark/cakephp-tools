<?php

namespace TestApp\Controller\Component;

use Cake\Event\Event;
use Shim\Controller\Component\Component;

class TestComponent extends Component {

	/**
	 * @var bool
	 */
	public $isInit = false;

	/**
	 * @var bool
	 */
	public $isStartup = false;

	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->isInit = true;
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function startup(Event $event) {
		$this->isStartup = true;
	}

}
