<?php
namespace Tools\Controller\Component;

use Cake\Controller\Component as CakeComponent;
use Cake\Event\Event;

/**
 * Convenience class that automatically provides the component's methods with
 * the controller instance via `$this->Controller`.
 */
class Component extends CakeComponent {

	/**
	 * @var \Cake\Controller\Controller
	 */
	public $Controller;

	/**
	 * Component::beforeFilter()
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->Controller = $event->subject();
	}

}
