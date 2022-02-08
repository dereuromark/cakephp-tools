<?php

namespace Tools\Controller\Admin;

use App\Controller\AppController;

/**
 * Display format helper specific debug info
 *
 * Needs Configure:
 * - Format.fontIcons
 */
class FormatController extends AppController {

	/**
	 * @var string|null
	 */
	protected $modelClass = '';

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function icons() {
	}

}
