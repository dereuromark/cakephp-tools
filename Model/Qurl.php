<?php
App::uses('ToolsAppModel', 'Tools.Model');

/**
 * Manage Quick Urls
 *
 * @author Mark Scherer
 * @cakephp 2.x
 * @license MIT
 * 2012-05-21 ms
 */
class Qurl extends ToolsAppModel {

	public $displayField = 'key';

	public $scaffoldSkipFields = array('note', 'key', 'content');

	public $order = array('Qurl.created' => 'DESC');

	protected $defaultLength = 22;
	protected $validity = YEAR;

	public $validate = array(
		'key' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true,
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'valErrQurlKeyExists',
			),
		),
		'url' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				'message' => 'valErrMandatoryField',
				'last' => true
			),
			'validateUrl' => array(
				'rule' => array('validateUrl', array('deep'=>false, 'sameDomain'=>true, 'autoComplete'=>true)),
				'message' => 'valErrInvalidQurlUrl',
				'last' => true
			)
		),
		'note' => array(
		),
		'comment' => array(
		),
		'used' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				'message' => 'valErrMandatoryField',
			),
		),
	);

	public function beforeValidate($options = array()) {
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

	public function beforeSave($options = array()) {
		parent::beforeSave($options);

		if (isset($this->data[$this->alias]['content'])) {
			 $this->data[$this->alias]['content'] = serialize($this->data[$this->alias]['content']);
		}

		return true;
	}


	public function translate($key) {
		$res = $this->find('first', array('conditions'=>array($this->alias.'.key'=>$key, $this->alias.'.active'=>1)));
		if (!$res) {
			return false;
		}
		//$res['CodeKey']['url'] = Router::url($content['url'], true);
		if ($res[$this->alias]['content']) {
			$res[$this->alias]['content'] = unserialize($res[$this->alias]['content']);
		} else {
			$res[$this->alias]['content'] = array();
		}
		return $res;
	}

	/**
	 * form the access url by key
	 * @param string $key
	 * @retur string $url (absolute)
	 */
	public static function urlByKey($key, $title = null, $slugTitle = true) {
		if ($title && $slugTitle) {
			$title = Inflector::slug($title, '-');
		}
		return Router::url(array('admin' => false, 'plugin'=>'tools', 'controller'=>'qurls', 'action'=>'go', $key, $title), true);
	}

	/**
	 * makes an absolute url string ready to input anywhere
	 * uses generate() internally to get the key
	 * @param mixed $url
	 * @return string $url (absolute)
	 */
	public function url($url, $data = array()) {
		$key = $this->generate($url, $data);
		if (!$key) {
			return false;
		}
	 	return $this->urlByKey($key);
	}

	/**
	 * generates a Qurl key
	 * @param mixed $url
	 * @param string $uid
	 * @return string $key
	 * 2011-07-12 ms
	 */
	public function generate($url, $data = array()) {
		$url = Router::url($url, true);
		$content = am($data, array('url'=>$url));
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
	 * sets Key to "used" (only once!) - directly by ID
	 * @param id of key to spend: necessary
	 * @return boolean true on success, false otherwise
	 * 2009-05-13 ms
	 */
	public function markAsUsed($id = null) {
		if (empty($id)) {
			return false;
		}
		//$this->id = $id;
		if ($this->updateAll(array($this->alias.'.used' => $this->alias.'.used + 1', $this->alias.'.last_used'=>'"'.date(FORMAT_DB_DATETIME).'"'), array($this->alias.'.id'=>$id))) {
			return true;
		}
		return false;
	}

	/**
	 * remove old/invalid keys
	 * does not remove recently used ones (for proper feedback)!
	 * @return boolean success
	 * 2010-06-17 ms
	 */
	public function garbigeCollector() {
		$conditions = array(
			$this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity),
		);
		return $this->deleteAll($conditions, false);
	}


	/**
	 * get admin stats
	 * 2010-10-22 ms
	 */
	public function stats() {
		$keys = array();
		$keys['unused_valid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>0, $this->alias.'.created >='=>date(FORMAT_DB_DATETIME, time()-$this->validity))));
		$keys['used_valid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>1, $this->alias.'.created >='=>date(FORMAT_DB_DATETIME, time()-$this->validity))));

		$keys['unused_invalid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>0, $this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity))));
		$keys['used_invalid'] = $this->find('count', array('conditions'=>array($this->alias.'.used'=>1, $this->alias.'.created <'=>date(FORMAT_DB_DATETIME, time()-$this->validity))));

		$urls = $this->find('all', array('conditions'=>array(), 'fields'=>array('DISTINCT url')));
		$keys['urls'] = !empty($urls) ? Set::extract('/'.$this->alias.'/url', $urls) : array();
		return $keys;
	}


	/**
	 * @param length (defaults to defaultLength)
	 * @return string codekey
	 * 2009-05-13 ms
	 */
	public function generateKey($length = null) {
		if (empty($length)) {
			$length = $this->defaultLength;
		}

		if ((class_exists('CommonComponent') || App::import('Component', 'Common')) && method_exists('CommonComponent', 'generatePassword')) {
			return CommonComponent::generatePassword($length);
		} else {
			return $this->_generateKey($length);
		}
	}

	/**
	 * backup method - only used if no custom function exists
	 * 2010-06-17 ms
	 */
	protected function _generateKey($length = null) {
		$chars = "234567890abcdefghijkmnopqrstuvwxyz"; // ABCDEFGHIJKLMNOPQRSTUVWXYZ
		$i = 0;
		$password = "";
		$max = strlen($chars) - 1;

		while ($i < $length) {
			$password .= $chars[mt_rand(0, $max)];
			$i++;
		}
		return $password;
	}

}
