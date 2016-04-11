<?php

namespace Tools\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use RuntimeException;

/**
 * Language switching
 *
 * Needs Configure:
 * - allowedLanguages (language mapping)
 * - defaultLanguage (optional, uses first of allowedLanguages otherwise)
 *
 * Mapping
 *
 * de => ['locale' => 'de_DE', 'name' => 'Deutsch'], ...
 */
class ShuntRequestController extends AppController {

	/**
	 * Switch language as post link.
	 *
	 * @param string|null $language
	 * @return \Cake\Network\Response
	 */
	public function language($language = null) {
		$this->request->allowMethod(['post']);

		$allowedLanguages = (array)Configure::read('Config.allowedLanguages');
		if (!$language) {
			$language = Configure::read('Config.defaultLanguage');
		}
		if (!$language) {
			$keys = array_keys($allowedLanguages);
			$language = $allowedLanguages[array_shift($keys)];
		}

		if (!array_key_exists($language, $allowedLanguages)) {
			throw new RuntimeException('Invalid Language');
		}
		$language = $allowedLanguages[$language];

		$this->request->session()->write('Config.language', $language['locale']);
		I18n::locale($language['locale']);
		$this->Flash->success(__d('tools', 'Language switched to {0}', $language['name']));
		return $this->redirect($this->referer('/', true));
	}

}
