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

	public function initialize(array $config) {
		$this->Controller = $this->_registry->getController();
	}

}
