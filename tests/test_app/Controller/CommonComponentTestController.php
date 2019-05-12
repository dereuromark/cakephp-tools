<?php
namespace App\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 *
 * @property \Tools\Controller\Component\CommonComponent $Common
 */
class CommonComponentTestController extends Controller {

	/**
	 * @var array
	 */
	public $autoRedirectActions = ['allowed'];

	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Tools.Common');
	}

}
