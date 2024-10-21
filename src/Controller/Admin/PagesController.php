<?php

namespace Tools\Controller\Admin;

use App\Controller\AppController;
use Cake\Utility\Inflector;
use Shim\Filesystem\Folder;

class PagesController extends AppController {

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$folder = ROOT . DS . 'templates' . DS . 'Pages' . DS;

		$Folder = new Folder($folder);
		$folders = $Folder->read();
		$files = $folders[1];

		$pages = [];
		foreach ($files as $file) {
			if (substr($file, -4) !== '.php') {
				continue;
			}
			$page = substr($file, 0, -4);
			$pages[$page] = [
				'label' => Inflector::humanize($page),
				'action' => Inflector::variable($page),
			];
		}

		$this->set(compact('pages'));
	}

}
