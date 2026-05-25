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
			if (!str_ends_with((string)$file, '.php')) {
				continue;
			}
			$page = substr((string)$file, 0, -4);
			$pages[$page] = [
				'label' => Inflector::humanize($page),
				'action' => Inflector::dasherize($page),
			];
		}

		$this->set(compact('pages'));
	}

}
