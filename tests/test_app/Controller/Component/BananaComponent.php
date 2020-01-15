<?php

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

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
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function startup(EventInterface $event) {
		$this->_registry->getController()->bar = 'fail';
	}

}
