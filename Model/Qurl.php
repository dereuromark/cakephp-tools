<?php
App::uses('ToolsAppModel', 'Tools.Model');
App::uses('Hash', 'Utility');
App::uses('RandomLib', 'Tools.Lib');

/**
 * Manage Quick Urls
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class Qurl extends ToolsAppModel {

	public $displayField = 'key';

	public $scaffoldSkipFields = ['note', 'key', 'content'];

	public $order = ['Qurl.created' => 'DESC'];

	protected $defaultLength = 22;

	protected $validity = YEAR;

	public $validate = [
		'key' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true,
			],
			'isUnique' => [
				'rule' => ['isUnique'],
				'message' => 'valErrQurlKeyExists',
			],
		],
		'url' => [
			'notEmpty' => [
				'rule' => ['notEmpty'],
				'message' => 'valErrMandatoryField',
				'last' => true
			],
			'validateUrl' => [
				'rule' => ['validateUrl', ['deep' => false, 'sameDomain' => true, 'autoComplete' => true]],
				'message' => 'valErrInvalidQurlUrl',
				'last' => true
			]
		],
		'note' => [
		],
		'comment' => [
		],
		'used' => [
			'numeric' => [
				'rule' => ['numeric'],
				'message' => 'valErrMandatoryField',
			],
		],
	];

	public function beforeValidate($options = []) {
		parent::beforeValidate($options);

		if (isset($this->data[$this->alias]['key']) && empty($this->data[$this->alias]['key'])) {
			$length = null;
			if (!empty($this->data[$this->alias]['key_length'])) {
				$length = $this->data[$this->alias]['key_length'];
			}
			$this->data[$this->alias]['key'] = $this->generateKey($length);
		}

		return true;
	}

	public function beforeSave($options = []) {
		parent::beforeSave($options);

		if (isset($this->data[$this->alias]['content'])) {
			$this->data[$this->alias]['content'] = serialize($this->data[$this->alias]['content']);
		}

		return true;
	}

	/**
	 * Qurl::translate()
	 *
	 * @param mixed $key
	 * @return array
	 */
	public function translate($key) {
		$res = $this->find('first', ['conditions' => [$this->alias . '.key' => $key, $this->alias . '.active' => 1]]);
		if (!$res) {
			return false;
		}

		if ($res[$this->alias]['content']) {
			$res[$this->alias]['content'] = unserialize($res[$this->alias]['content']);
		} else {
			$res[$this->alias]['content'] = [];
		}
		return $res;
	}

	/**
	 * Form the access url by key
	 *
	 * @param string $key
	 * @return string Url (absolute)
	 */
	public static function urlByKey($key, $title = null, $slugTitle = true) {
		if ($title && $slugTitle) {
			$title = Inflector::slug($title, '-');
		}
		return Router::url(['admin' => false, 'plugin' => 'tools', 'controller' => 'qurls', 'action' => 'go', $key, $title], true);
	}

	/**
	 * Makes an absolute url string ready to input anywhere.
	 * Uses generate() internally to get the key.
	 *
	 * @param mixed $url
	 * @return string Url (absolute)
	 */
	public function url($url, $data = []) {
		$key = $this->generate($url, $data);
		if (!$key) {
			return false;
		}
		return $this->urlByKey($key);
	}

	/**
	 * Generates a Qurl key
	 *
	 * @param mixed $url
	 * @param string $uid
	 * @return string Key
	 */
	public function generate($url, $data = []) {
		$url = Router::url($url, true);
		$content = array_merge($data, ['url' => $url]);
		if (!isset($content['key'])) {
			$content['key'] = '';
		}
		$res = $this->save($content);
		if (!$res) {
			return false;
		}
		return $res[$this->alias]['key'];
	}

	/**
	 * Sets Key to "used" (only once!) - directly by ID
	 *
	 * @param id of key to spend: necessary
	 * @return bool Success
	 */
	public function markAsUsed($id = null) {
		if (empty($id)) {
			return false;
		}
		//$this->id = $id;
		if ($this->updateAll([$this->alias . '.used' => $this->alias . '.used + 1', $this->alias . '.last_used' => '"' . date(FORMAT_DB_DATETIME) . '"'], [$this->alias . '.id' => $id])) {
			return true;
		}
		return false;
	}

	/**
	 * Remove old/invalid keys
	 * does not remove recently used ones (for proper feedback)!
	 *
	 * @return bool success
	 */
	public function garbageCollector() {
		$conditions = [
			$this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity),
		];
		return $this->deleteAll($conditions, false);
	}

	/**
	 * Get admin stats
	 *
	 * @return array
	 */
	public function stats() {
		$keys = [];
		$keys['unused_valid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 0, $this->alias . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_valid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 1, $this->alias . '.created >=' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$keys['unused_invalid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 0, $this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);
		$keys['used_invalid'] = $this->find('count', ['conditions' => [$this->alias . '.used' => 1, $this->alias . '.created <' => date(FORMAT_DB_DATETIME, time() - $this->validity)]]);

		$urls = $this->find('all', ['conditions' => [], 'fields' => ['DISTINCT url']]);
		$keys['urls'] = !empty($urls) ? Hash::extract('{n}.' . $this->alias . '.url', $urls) : [];
		return $keys;
	}

	/**
	 * Generate key
	 *
	 * @param length (defaults to defaultLength)
	 * @return string codekey
	 */
	public function generateKey($length = null) {
		if (empty($length)) {
			$length = $this->defaultLength;
		}

		return RandomLib::generatePassword($length);
	}

}
