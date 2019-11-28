<?php

namespace App\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 *
 * @property \Tools\Controller\Component\UrlComponent $Url
 */
class UrlComponentTestController extends Controller {

	/**
	 * @var array
	 */
	public $components = ['Tools.Url'];

}
