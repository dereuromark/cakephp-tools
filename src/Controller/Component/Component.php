<?php
namespace Tools\Controller\Component;

use Cake\Controller\Component as CakeComponent;

/**
 */
class Component extends CakeComponent {

	public $Controller;

	/**
	 * Component::beforeFilter()
	 *
	 * @param Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$this->Controller = $event->subject();
	}

}
