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
	public $components = ['Tools.Common'];

	/**
	 * @var array
	 */
	public $autoRedirectActions = ['allowed'];

}
