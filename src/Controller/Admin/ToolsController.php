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
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$this->viewBuilder()->addHelper('Tools.Format');
	}

}
