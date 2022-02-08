<?php

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

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
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event) {
		$this->isInit = true;
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function startup(EventInterface $event) {
		$this->isStartup = true;
	}

}
