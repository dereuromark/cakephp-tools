<?php

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

/**
 * AppleComponent class
 */
class AppleComponent extends Component {

	/**
	 * components property
	 *
	 * @var array
	 */
	protected $components = ['Banana'];

	/**
	 * startup method
	 *
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function startup(EventInterface $event) {
	}

}
