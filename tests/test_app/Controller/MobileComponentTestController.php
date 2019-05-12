<?php
namespace App\Controller;

use Tools\Controller\Controller;

/**
 * @property \Tools\Controller\Component\MobileComponent $Mobile
 */
class MobileComponentTestController extends Controller {

	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('RequestHandler');
		$this->loadComponent('Tools.Mobile');
	}

}
