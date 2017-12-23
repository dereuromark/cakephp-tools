<?php
namespace App\Controller;

use Tools\Controller\Controller;

/**
 * @property \Tools\Controller\Component\MobileComponent $Mobile
 */
class MobileComponentTestController extends Controller {

	/**
	 * Components property
	 *
	 * @var array
	 */
	public $components = ['RequestHandler', 'Tools.Mobile'];

}
