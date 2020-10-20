<?php

namespace Tools\Controller\Admin;

use App\Controller\AppController;

/**
 * Display format helper specific debug info
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class ToolsController extends AppController {

	/**
	 * @var string|null
	 */
	protected $modelClass = '';

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$this->viewBuilder()->addHelper('Tools.Format');
	}

}
