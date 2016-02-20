<?php
namespace TestApp\Controller\Component;

use Cake\Event\Event;
use Shim\Controller\Component\Component;

class TestComponent extends Component {

	public $Controller;

	public $isInit = false;

	public $isStartup = false;

	public function beforeFilter(Event $event) {
		$this->isInit = true;
	}

	public function startup(Event $event) {
		$this->isStartup = true;
	}

}
