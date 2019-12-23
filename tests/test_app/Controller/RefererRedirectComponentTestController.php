<?php

namespace App\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 *
 * @property \Tools\Controller\Component\CommonComponent $Common
 */
class RefererRedirectComponentTestController extends Controller {

	/**
	 * @var array
	 */
	public $components = ['Tools.RefererRedirect'];

}
