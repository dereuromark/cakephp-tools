<?php
App::uses('DataSource', 'Model/Datasource');
App::uses('Inflector', 'Utility');
App::uses('Set', 'Utility');

/**
 * Get emails in your app with cake like finds.
 *
 * TODO: implement https://github.com/kvz/cakephp-emails-plugin/commits
 * TODO: check for search stuff from https://github.com/barbushin/php-imap
 *
 * @copyright Copyright (c) 2010 Carl Sutton ( dogmatic69 )
 * @link http://www.infinitas-cms.org
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @author dogmatic69
 * @author kvz
 * @author Mark Scherer
 */
class ImapSource extends DataSource {

	protected $_isConnected = false;

	protected $_connectionString = null;

	protected $_connectionType = '';

	protected $_defaultConfigs = array(
		'global' => array(
			'username' => false,
			'password' => false,
			'email' => false,
			'server' => 'localhost',
			'type' => 'imap',
			'ssl' => false,
			'mailbox' => 'INBOX',
			'retry' => 3,
			'error_handler' => 'php',
			'auto_mark_as' => array('seen'),
			'auto_transform' => true // transform attachments back into the original file content
			),
		'imap' => array('port' => 143),
		'pop3' => array('port' => 110),
		);

	public $marks = array(
		'\Seen',
		'\Answered',
		'\Flagged',
		'\Deleted',
		'\Draft',
		);

	public $config = array();

	public $driver = null;

	/**
	 * Default array of field list for imap mailbox.
	 *
	 * @var array
	 */
	protected $_schema = array(
		'id' => array(
			'type' => 'integer',
			'default' => null,
			'length' => 15,
			'key' => 'primary',
			),
		'message_id' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'email_number' => array(
			'type' => 'integer',
			'default' => null,
			'length' => 15,
			),

		'to' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'to_name' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'from' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'from_name' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'reply_to' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'reply_to_name' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'sender' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'sender_name' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),

		'subject' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'slug' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'body_html' => array(
			'type' => 'text',
			'default' => null,
			),
		'body_text' => array(
			'type' => 'text',
			'default' => null,
			),
		'size' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),

		'recent' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'seen' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'flagged' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'answered' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'draft' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'deleted' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),

		'thread_count' => array(
			'type' => 'integer',
			'default' => null,
			'length' => 15,
			'key' => 'primary',
			),
		'attachments' => array(
			'type' => 'text',
			'default' => null,
			),
		'in_reply_to' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'reference' => array(
			'type' => 'string',
			'default' => null,
			'length' => 255,
			),
		'new' => array(
			'type' => 'boolean',
			'default' => null,
			'length' => 1,
			),
		'created' => array(
			'type' => 'datetime',
			'default' => null,
			),
		);

	public $columns = array(
		'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
		'integer' => array(
			'name' => 'int',
			'limit' => '11',
			'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array(
			'name' => 'datetime',
			'format' => 'Y-m-d H:i:s',
			'formatter' => 'date'),
		'timestamp' => array(
			'name' => 'timestamp',
			'format' => 'Y-m-d H:i:s',
			'formatter' => 'date'),
		'time' => array(
			'name' => 'time',
			'format' => 'H:i:s',
			'formatter' => 'date'),
		'date' => array(
			'name' => 'date',
			'format' => 'Y-m-d',
			'formatter' => 'date'),
		'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'limit' => '1'));

	public $dataTypes = array(
		0 => 'text',
		1 => 'multipart',
		2 => 'message',
		3 => 'application',
		4 => 'audio',
		5 => 'image',
		6 => 'video',
		7 => 'other',
		);

	public $encodingTypes = array(
		0 => '7bit',
		1 => '8bit',
		2 => 'binary',
		3 => 'base64',
		4 => 'quoted-printable',
		5 => 'other',
		);

	/**
	 * __construct()
	 *
	 * @param mixed $config
	 */
	public function __construct($config) {
		parent::__construct($config);

		if (!function_exists('imap_open')) {
			throw new InternalErrorException('imap_open not available. Please install extension/module.');
		}

		if (!isset($config['type'])) {
			$type = $this->_defaultConfigs['global']['type'];
		} else {
			$type = $config['type'];
		}
		$newConfig = array_merge($this->_defaultConfigs['global'], $this->_defaultConfigs[$type], $this->config);
		$newConfig['email'] = !empty($newConfig['email']) ? $newConfig['email'] : $newConfig['username'];

		$this->config = $newConfig;
	}

	/**
	 * Expunge messages marked for deletion
	 *
	 */
	public function __destruct() {
		if ($this->_isConnected && $this->Stream) {
			$this->_isConnected = false;
			// If set to CL_EXPUNGE, the function will silently expunge the
			// mailbox before closing, removing all messages marked for deletion.
			// You can achieve the same thing by using imap_expunge()
			imap_close($this->Stream, CL_EXPUNGE);
		}
	}

	/**
	 * Describe the data
	 *
	 * @param Model $Model
	 * @return array The schema of the model
	 */
	public function describe($Model) {
		return $this->_schema;
	}

	/**
	 * listSources
	 *
	 * @return array Sources
	 */
	public function listSources($data = null) {
		return array('listSources');
	}

	/**
	 * ImapSource::delete()
	 *
	 * @param Model $Model
	 * @param mixed $conditions
	 * @return boolean Success
	 */
	public function delete(Model $Model, $conditions = null) {
		$query = compact('conditions');
		$searchCriteria = $this->_makeSearch($Model, $query);
		$uids = $this->_uidsByCriteria($searchCriteria);
		if ($uids === false) {
			$uids = $Model->find('list', $query);
		}

		// Nothing was found
		if (empty($uids)) {
			return false;
		}

		$success = true;
		foreach ($uids as $uid) {
			if (!imap_delete($this->Stream, $uid, FT_UID)) {
				$this->err($Model, 'Unable to delete email with uid: %s', $uid);
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Read data
	 *
	 * this is the main method that reads data from the datasource and
	 * formats it according to the request from the model.
	 *
	 * @param Model $Model the model that is requesting data
	 * @param mixed $query the qurey that was sent
	 *
	 * @return the data requested by the model
	 */
	public function read(Model $Model, $queryData = array(), $recursive = null) {
		if (!$this->connect($Model, $queryData)) {
			//throw new RuntimeException('something is wrong');
			return $this->err($Model, 'Cannot connect to server');
		}

		$searchCriteria = $this->_makeSearch($Model, $queryData);
		$uids = $this->_uidsByCriteria($searchCriteria);
		if ($uids === false) {
			// Perform Search & Order. Returns list of ids
			list($orderReverse, $orderCriteria) = $this->_makeOrder($Model, $queryData);
			$uids = imap_sort($this->Stream, $orderCriteria, $orderReverse, SE_UID, join(' ', $searchCriteria));
		}

		// Nothing was found
		if (empty($uids)) {
			return array();
		}

		// Trim resulting ids based on pagination / limitation
		if (@$queryData['start'] && @$queryData['end']) {
			$uids = array_slice($uids, @$queryData['start'], @$queryData['end'] - @$queryData['start']);
		} elseif (@$queryData['limit']) {
			$uids = array_slice($uids, @$queryData['start'] ? @$queryData['start'] : 0, @$queryData['limit']);
		} elseif ($Model->findQueryType === 'first') {
			$uids = array_slice($uids, 0, 1);
		}

		// Format output depending on findQueryType
		if ($Model->findQueryType === 'list') {
			return $uids;
		}
		if ($Model->findQueryType === 'count') {
			return array(array($Model->alias => array('count' => count($uids))));
		}
		if ($Model->findQueryType === 'all' || $Model->findQueryType === 'first') {
			$recursive = isset($queryData['recursive']) ? $queryData['recursive'] : $Model->recursive;
			$fetchAttachments = $recursive > 0;
			$mails = array();
			foreach ($uids as $uid) {
				if (($mail = $this->_getFormattedMail($Model, $uid, $fetchAttachments))) {
					$mails[] = $mail;
				}
			}
			return $mails;
		}

		return $this->err($Model, 'Unknown find type %s for query %s', $Model->findQueryType, $queryData);
	}

	/**
	 * Calculate
	 *
	 * @param <type> $Model
	 * @param <type> $func
	 * @param <type> $params
	 * @return string
	 */
	public function calculate(Model $Model, $func, $params = array()) {
		$params = (array)$params;
		switch (strtolower($func)) {
			case 'count':
				return 'count';
		}
	}

	/**
	 * Update the email setting flags
	 *
	 * @return boolean Success
	 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		if (empty($model->id)) {
			return $this->err($model, 'Cannot update a record without id');
		}

		$flags = array(
			'recent',
			'seen',
			'flagged',
			'answered',
			'draft',
			'deleted');
		$data = array_combine($fields, $values);
		foreach ($data as $field => $value) {
			if (!in_array($field, $flags)) {
				continue;
			}
			$flag = '\\' . ucfirst($field);
			if ($value === true || $value === 1 || $value === '1') {
				if (!imap_setflag_full($this->Stream, $model->id, $flag, ST_UID)) {
					$this->err($model, 'Unable to mark email %s as %s', $model->id, $flag);
				}
			} else {
				if (!imap_clearflag_full($this->Stream, $model->id, $flag, ST_UID)) {
					$this->err($model, 'Unable to unmark email %s as %s', $model->id, $flag);
				}
			}
		}
		return true;
	}

	/**
	 * ImapSource::query()
	 *
	 * Allow Source methods to be called from the model
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed Result
	 */
	public function query($method, $params, Model $Model) {
		array_unshift($params, $Model);
		return call_user_func_array(array($this, $method), $params);
	}

	/**
	 * ImapSource::err()
	 *
	 * @param Model $Model
	 * @param mixed $format
	 * @param mixed $args (3...x arguments)
	 * @return boolean false if error handler is not set to `exception`
	 */
	public function err($Model, $format, $args = null) {
		$arguments = func_get_args();
		$Model = array_shift($arguments);
		$format = array_shift($arguments);

		$str = $format;
		if (!empty($arguments)) {
			foreach ($arguments as $k => $v) {
				$arguments[$k] = $this->_sensible($v);
			}
			$str = vsprintf($str, $arguments);
		}

		$this->error = $str;
		$Model->onError();

		if ($this->config['error_handler'] === 'php') {
			trigger_error($str, E_USER_ERROR);
		} elseif ($this->config['error_handler'] === 'exception') {
			throw new CakeException($str);
		}

		return false;
	}

	/**
	 * ImapSource::lastError()
	 *
	 * @return string Error or bool false if no error is available
	 */
	public function lastError() {
		if (($lastError = $this->error)) {
			return $lastError;
		}
		if (($lastError = imap_last_error())) {
			$this->error = $lastError;
			return $lastError;
		}
		return false;
	}

	/**
	 * ImapSource::listMailboxes()
	 *
	 * There are two special characters you can pass as part of the pattern :
	 * '*' and '%'. '*' means to return all mailboxes. If you pass pattern as '*',
	 * you will get a list of the entire mailbox hierarchy. '%' means to return the current level only.
	 *
	 * @param Model $Model
	 * @param boolean|string $current
	 * @return array Array containing the names of the mailboxes.
	 */
	public function listMailboxes(Model $Model, $current = true) {
		if (is_bool($current)) {
			if ($current) {
				$current = '%';
			} else {
				$current = '*';
			}
		}
		$this->connect($Model, array());
		return imap_list($this->Stream, $this->_connectionString, $current);
	}

	/**
	 * Connect to the mail server
	 */
	public function connect(Model $Model, $query) {
		if ($this->_isConnected) {
			return true;
		}
		$this->_connectionString = $this->_buildConnector();

		try {
			$retries = $this->config['retry'];
			$this->Stream = imap_open($this->_connectionString, $this->config['username'], $this->config['password'], NIL, $retries);
			//$this->thread = @imap_thread($this->Stream);
		} catch (exception $Exception) {
			return $this->err($Model, 'Unable to connect to IMAP server %s retries. %s', $this->_connectionString, $Exception->getMessage() . ' ' . imap_last_error());
		}

		return $this->_isConnected = true;
	}

	protected function _buildConnector() {
		$data = $this->config;

		$string = sprintf('{%s:%s%s%s}', $this->config['server'], $this->config['port'], @$this->config['ssl'] ? '/ssl' : '', @$this->
					config['connect'] ? '/' . @$this->config['connect'] : '/novalidate-cert');

		return $string;

		$string = '{';
		$string .= $data['server'];
		if (!empty($data['port'])) {
			$string .= ':' . $data['port'];
		}
		if (!empty($data['service'])) {
			$string .= '/service=' . $data['service'];
		}
		if (!empty($data['user'])) {
			$string .= '/user=' . $data['user'];
		} else {
			$string .= '/anonymous';
		}
		if (!empty($data['authuser'])) {
			$string .= '/authuser=' . $data['authuser'];
		}
		if (!empty($data['debug'])) {
			$string .= '/debug';
		}
		if (!empty($data['secure'])) {
			$string .= '/secure';
		}
		if (!empty($data['norsh'])) {
			$string .= '/norsh';
		}
		if (!empty($data['ssl'])) {
			$string .= '/ssl';
		}
		if (!empty($data['validate'])) {
			$string .= '/validate-cert';
		} else {
			$string .= '/novalidate-cert';
		}
		if (!empty($data['tls'])) {
			$string .= '/tls';
		}
		if (!empty($data['notls'])) {
			$string .= '/notls';
		}
		if (!empty($data['readonly'])) {
			$string .= '/readonly';
		}
		$string .= '}';
		if (!empty($data['mailbox'])) {
			$string .= $data['mailbox'];
		}
		return $string;

		// deprecated part
		switch ($this->config['type']) {
			case 'imap':
				$this->_connectionString = sprintf('{%s:%s%s%s}', $this->config['server'], $this->config['port'], @$this->config['ssl'] ? '/ssl' : '', @$this->
					config['connect'] ? '/' . @$this->config['connect'] : '');
				break;

			case 'pop3':
				$this->_connectionString = sprintf('{%s:%s/pop3%s%s}', $this->config['server'], $this->config['port'], @$this->config['ssl'] ? '/ssl' : '',
					@$this->config['connect'] ? '/' . @$this->config['connect'] : '');
				break;
		}
	}

	/**
	 * Tranform search criteria from CakePHP -> Imap
	 * Does AND, not OR
	 *
	 * Supported:
	 *  FROM "string" - match messages with "string" in the From: field
	 *
	 *  ANSWERED - match messages with the \\ANSWERED flag set
	 *  UNANSWERED - match messages that have not been answered
	 *
	 *  SEEN - match messages that have been read (the \\SEEN flag is set)
	 *  UNSEEN - match messages which have not been read yet
	 *
	 *  DELETED - match deleted messages
	 *  UNDELETED - match messages that are not deleted
	 *
	 *  FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
	 *  UNFLAGGED - match messages that are not flagged
	 *
	 *  RECENT - match messages with the \\RECENT flag set
	 *
	 * @todo:
	 *  A string, delimited by spaces, in which the following keywords are allowed. Any multi-word arguments (e.g. FROM "joey smith") must be quoted.
	 *  ALL - return all messages matching the rest of the criteria
	 *  BCC "string" - match messages with "string" in the Bcc: field
	 *  BEFORE "date" - match messages with Date: before "date"
	 *  BODY "string" - match messages with "string" in the body of the message
	 *  CC "string" - match messages with "string" in the Cc: field
	 *  KEYWORD "string" - match messages with "string" as a keyword
	 *  NEW - match new messages
	 *  OLD - match old messages
	 *  ON "date" - match messages with Date: matching "date"
	 *  SINCE "date" - match messages with Date: after "date"
	 *  SUBJECT "string" - match messages with "string" in the Subject:
	 *  TEXT "string" - match messages with text "string"
	 *  TO "string" - match messages with "string" in the To:
	 *  UNKEYWORD "string" - match messages that do not have the keyword "string"
	 *
	 * @param object $Model
	 * @param array  $query
	 *
	 * @return array
	 */
	protected function _makeSearch(Model $Model, $query) {
		$searchCriteria = array();

		if (empty($query['conditions'])) {
			$query['conditions'] = array();
		}

		// Special case. When somebody specifies primaryKey(s),
		// We don't have to do an actual search
		if (($id = $this->_cond($Model, $query, $Model->primaryKey))) {
			return $this->_toUid($id);
		}

		// Flag search parameters
		$flags = array(
			'recent',
			'seen',
			'flagged',
			'answered',
			'draft',
			'deleted',
			);

		foreach ($flags as $flag) {
			if (($val = $this->_cond($Model, $query, $flag)) === null) {
				continue;
			}
			$upper = strtoupper($flag);
			$unupper = 'UN' . $upper;

			if (!$val && ($flag === 'recent')) {
				// There is no UNRECENT :/
				// Just don't set the condition
				continue;
			}

			$searchCriteria[] = $val ? $upper : $unupper;
		}

		// String search parameters
		if (($val = $this->_cond($Model, $query, 'from'))) {
			$searchCriteria[] = 'FROM "' . $val . '"';
		}

		return $searchCriteria;
	}

	/**
	 * Tranform order criteria from CakePHP -> Imap
	 *
	 * For now always sorts on date descending.
	 * @todo: Support the following sort parameters:
	 *  SORTDATE - message Date
	 *  SORTARRIVAL - arrival date
	 *  SORTFROM - mailbox in first From address
	 *  SORTSUBJECT - message subject
	 *  SORTTO - mailbox in first To address
	 *  SORTCC - mailbox in first cc address
	 *  SORTSIZE - size of message in octets
	 *
	 * @param object $Model
	 * @param array  $query
	 *
	 * @return array
	 */
	protected function _makeOrder(Model $Model, $query) {
		$criterias = array(
			'date',
			'arrival',
			'from',
			'subject',
			'to',
			'cc',
			'size');

		$order = array(1, SORTDATE);
		if (empty($query['order']) || empty($query['order'][0])) {
			return $order;
		}

		foreach ($query['order'][0] as $key => $dir) {
			if (in_array($key, $criterias)) {
				return array((strtoupper($dir) === 'ASC') ? 0 : 1, constant('SORT' . strtoupper($key)));
			}
		}
		return $order;
	}

	/**
	 * Returns a query condition, or null if it wasn't found
	 *
	 * @param object $Model
	 * @param array  $query
	 * @param string $field
	 *
	 * @return mixed or null
	 */
	protected function _cond(Model $Model, $query, $field) {
		$keys = array(
			'`' . $Model->alias . '`.`' . $field . '`',
			$Model->alias . '.' . $field,
			$field,
		);
		if (empty($query['conditions'])) {
			return null;
		}
		foreach ($keys as $key) {
			if (array_key_exists($key, $query['conditions'])) {
				return $query['conditions'][$key];
			}
		}

		return null;
	}

	/**
	 * Returns ids from searchCriteria or false if there's other criteria involved
	 *
	 * @param array $searchCriteria
	 *
	 * @return false or array
	 */
	protected function _uidsByCriteria($searchCriteria) {
		if (is_numeric($searchCriteria) || Set::numeric($searchCriteria)) {
			// We already know the id, or list of ids
			$results = $searchCriteria;
			if (!is_array($results)) {
				$results = array($results);
			}
			return $results;
		}

		return false;
	}

	/**
	 * ImapSource::_sensible() for error output
	 *
	 * @param mixed $arguments
	 * @return string
	 */
	protected function _sensible($arguments) {
		if (is_object($arguments)) {
			return get_class($arguments);
		}
		if (!is_array($arguments)) {
			if (!is_numeric($arguments) && !is_bool($arguments)) {
				$arguments = "'" . $arguments . "'";
			}
			return $arguments;
		}
		$arr = array();
		foreach ($arguments as $key => $val) {
			if (is_array($val)) {
				$val = json_encode($val);
			} elseif (!is_numeric($val) && !is_bool($val)) {
				$val = "'" . $val . "'";
			}

			if (strlen($val) > 33) {
				$val = substr($val, 0, 30) . '...';
			}

			$arr[] = $key . ': ' . $val;
		}
		return implode(', ', $arr);
	}

	/**
	 * Tries to parse mail & name data from Mail object for to, from, etc.
	 * Gracefully degrades where needed
	 *
	 * Type: to, cc, bcc, from, sender, reply_to
	 * Need: box, name, host, address, full
	 *
	 * @param object $Mail
	 * @param string $type
	 * @param string $need
	 *
	 * @return mixed string or array
	 */
	protected function _personId($Mail, $type = 'to', $need = null) {
		if ($type === 'sender' && !isset($Mail->sender)) {
			$type = 'from';
		}
		if (!isset($Mail->{$type})) {
			return array();
		}

		$results = array();
		foreach ($Mail->{$type} as $person) {
			$info = array(
				'box' => '',
				'host' => '',
				'address' => '',
				);

			if (isset($person->mailbox)) {
				$info['box'] = $person->mailbox;
			}
			if (isset($person->host)) {
				$info['host'] = $person->host;
			}
			if ($info['box'] && $info['host']) {
				$info['address'] = $info['box'] . '@' . $info['host'];
			}

			$info['name'] = $info['box'];
			if (isset($person->personal)) {
				$info['name'] = $this->_decode($person->personal);
			}
			$info['full'] = $info['address'];
			if ($info['name']) {
				$info['full'] = sprintf('"%s" <%s>', $info['name'], $info['address']);
			}

			$results[] = $info;
		}

		if ($need !== null) {
			return $results[0][$need];
		}
		return $results;
	}

	/**
	 * Decode text to the application encoding
	 *
	 * @param string $text
	 * @return string text
	 */
	protected function _decode($text) {
		if (is_object($text)) {
			$decoded = $text;
			$text = $decoded->text;
		} else {
			$decoded = imap_mime_header_decode($text);
			$decoded = $decoded[0];
		}

		if (empty($decoded) || empty($decoded->text) || $decoded->charset === 'default') {
			return $text;
		}
		$text = imap_qprint($decoded->text);

		$appEncoding = Configure::read('App.encoding');
		$mailEncoding = $decoded->charset;
		$encodings = mb_list_encodings();
		$valid = true;
		if ($appEncoding !== $mailEncoding || !($valid = mb_check_encoding($text, $mailEncoding))) {
			if (!in_array($mailEncoding, $encodings) || !$valid) {
				$mailEncoding = mb_detect_encoding($text);
			}
			if (!in_array($appEncoding, $encodings)) {
				$appEncoding = 'UTF-8';
			}

			$text = mb_convert_encoding($text, $appEncoding, $mailEncoding);
		}

		return $text;
	}

	/**
	 * Get the basic details like sender and reciver with flags like attatchments etc
	 *
	 * @param integer $uid the number of the message
	 * @return array empty on error/nothing or array of formatted details
	 */
	protected function _getFormattedMail(Model $Model, $uid, $fetchAttachments = false) {
		// Translate uid to msg_no. Has no decent fail
		$msgNumber = imap_msgno($this->Stream, $uid);

		// A hack to detect if imap_msgno failed, and we're in fact looking at the wrong mail
		if ($uid != ($mailuid = imap_uid($this->Stream, $msgNumber))) {
			//pr(compact('Mail'));
			return $this->err($Model, 'Mail id mismatch. parameter id: %s vs mail id: %s', $uid, $mailuid);
		}

		// Get Mail with a property: 'date' or fail
		if (!($Mail = imap_headerinfo($this->Stream, $msgNumber)) || !property_exists($Mail, 'date')) {
			//pr(compact('Mail'));
			return $this->err($Model, 'Unable to find mail date property in Mail corresponding with uid: %s. Something must be wrong', $uid);
		}

		// Get Mail with a property: 'type' or fail
		if (!($flatStructure = $this->_flatStructure($Model, $uid))) {
			return $this->err($Model, 'Unable to find structure type property in Mail corresponding with uid: %s. Something must be wrong', $uid);
		}

		$text = $this->_fetchFirstByMime($flatStructure, 'text/plain');
		$html = $this->_fetchFirstByMime($flatStructure, 'text/html');

		$return[$Model->alias] = array(
			'id' => $this->_toId($uid),
			'message_id' => $Mail->messageId,
			'email_number' => $Mail->Msgno,

			'from' => $this->_personId($Mail, 'from', 'address'),
			'from_name' => $this->_personId($Mail, 'from', 'name'),
			'reply_to' => $this->_personId($Mail, 'reply_to', 'address'),
			'reply_to_name' => $this->_personId($Mail, 'reply_to', 'name'),
			'sender' => $this->_personId($Mail, 'sender', 'address'),
			'sender_name' => $this->_personId($Mail, 'sender', 'name'),

			'subject' => htmlspecialchars(@$Mail->subject),
			'slug' => Inflector::slug(@$Mail->subject, '-'),
			'header' => @imap_fetchheader($this->Stream, $uid, FT_UID),
			'body_html' => $html,
			'body_text' => $text,
			'size' => @$Mail->Size,

			'recent' => @$Mail->Recent === 'R' ? 1 : 0,
			'seen' => @$Mail->Unseen === 'U' ? 0 : 1,
			'flagged' => @$Mail->Flagged === 'F' ? 1 : 0,
			'answered' => @$Mail->Answered === 'A' ? 1 : 0,
			'draft' => @$Mail->Draft === 'X' ? 1 : 0,
			'deleted' => @$Mail->Deleted === 'D' ? 1 : 0,

			'thread_count' => $this->_getThreadCount($Mail),
			'in_reply_to' => @$Mail->inReplyTo,
			'reference' => @$Mail->references,
			'new' => (int)@$Mail->inReplyTo,
			'created' => date('Y-m-d H:i:s', strtotime($Mail->date)),
			);
		$return['Recipient'] = $this->_personId($Mail, 'to');
		$return['RecipientCopy'] = $this->_personId($Mail, 'cc');
		$return['RecipientBlindCopy'] = $this->_personId($Mail, 'bcc');

		if ($fetchAttachments) {
			$return['Attachment'] = $this->_fetchAttachments($flatStructure, $Model);
		}

		// Auto mark after read
		if (!empty($this->config['auto_mark_as'])) {
			$marks = '\\' . join(' \\', $this->config['auto_mark_as']);
			if (!imap_setflag_full($this->Stream, $uid, $marks, ST_UID)) {
				$this->err($Model, 'Unable to mark email %s as %s', $uid, $marks);
			}
		}

		return $return;
	}

	/**
	 * ImapSource::_decodePart()
	 *
	 * @param object $Part
	 * @param mixed $uid
	 * @return object Part
	 */
	protected function _decodePart($Part, $uid) {
		if (!($Part->format = @$this->encodingTypes[$Part->encoding])) {
			$Part->format = $this->encodingTypes[0];
		}

		if (!($Part->datatype = @$this->dataTypes[$Part->type])) {
			$Part->datatype = $this->dataTypes[0];
		}

		$Part->mimeType = strtolower($Part->datatype . '/' . $Part->subtype);
		$Part->filename = '';
		$Part->name = '';
		$Part->uid = $uid;

		if ($Part->ifdparameters) {
			foreach ($Part->dparameters as $Object) {
				if (strtolower($Object->attribute) === 'filename') {
					$Part->filename = $Object->value;
				}
			}
		}

		if ($Part->ifparameters) {
			foreach ($Part->parameters as $Object) {
				if (strtolower($Object->attribute) === 'name') {
					$Part->name = $Object->value;
				}
			}
		}

		$Part->isAttachment = (!empty($Part->disposition) && !empty($Part->filename) && in_array(strtolower($Part->disposition), array('attachment',
				'inline')));

		return $Part;
	}

	/**
	 *
	 * Contains parts of:
	 *  http://p2p.wrox.com/pro-php/8658-fyi-parsing-imap_fetchstructure.html
	 *  http://www.php.net/manual/en/function.imap-fetchstructure.php#86685
	 *
	 * @param <type> $uid
	 * @param <type> $mixed
	 * @param <type> $Structure
	 * @param <type> $partnr
	 *
	 * @return array
	 */
	protected function _flatStructure(Model $Model, $uid, $Structure = false, $partnr = 1) {
		$mainRun = false;
		if (!$Structure) {
			$mainRun = true;
			$Structure = imap_fetchstructure($this->Stream, $uid, FT_UID);
			if (!property_exists($Structure, 'type')) {
				return $this->err($Model, 'No type in structure');
			}
		}
		$flatParts = array();

		if (!empty($Structure->parts)) {
			$decimas = explode('.', $partnr);
			$decimas[count($decimas) - 1] -= 1;
			$Structure->path = join('.', $decimas);
		} else {
			$Structure->path = $partnr;
		}
		$flatParts[$Structure->path] = $this->_decodePart($Structure, $uid);

		if (!empty($Structure->parts)) {
			foreach ($Structure->parts as $n => $Part) {
				if ($n >= 1) {
					$arrDecimas = explode('.', $partnr);
					$arrDecimas[count($arrDecimas) - 1] += 1;
					$partnr = join('.', $arrDecimas);
				}
				$Part->path = $partnr;

				$flatParts[$Part->path] = $this->_decodePart($Part, $uid);

				if (!empty($Part->parts)) {
					if ($Part->type == 1) {
						$flatParts = Set::merge($flatParts, $this->_flatStructure($Model, $uid, $Part, $partnr . '.' . ($n + 1)));
					} else {
						foreach ($Part->parts as $idx => $Part2) {
							$flatParts = Set::merge($flatParts, $this->_flatStructure($Model, $uid, $Part2, $partnr . '.' . ($idx + 1)));
						}
					}
				}
			}
		}

		// Filter mixed
		if ($mainRun) {
			foreach ($flatParts as $path => $Part) {
				if ($Part->mimeType === 'multipart/mixed') {
					unset($flatParts[$path]);
				}
				if ($Part->mimeType === 'multipart/alternative') {
					unset($flatParts[$path]);
				}
				if ($Part->mimeType === 'multipart/related') {
					unset($flatParts[$path]);
				}
				if ($Part->mimeType === 'message/rfc822') {
					unset($flatParts[$path]);
				}
			}
		}

		// Flatten more (remove childs)
		if ($mainRun) {
			foreach ($flatParts as $path => $Part) {
				unset($Part->parts);
			}
		}

		return $flatParts;
	}

	/**
	 * ImapSource::_fetchAttachments()
	 *
	 * @param mixed $flatStructure
	 * @param Model $Model
	 * @return array
	 */
	protected function _fetchAttachments($flatStructure, Model $Model) {
		$attachments = array();
		foreach ($flatStructure as $path => $Part) {
			if (!$Part->isAttachment) {
				continue;
			}
			$attachments[] = array(
				strtolower(Inflector::singularize($Model->alias) . '_id') => $this->_toId($Part->uid),
				'message_id' => $Part->uid,
				'isAttachment' => $Part->isAttachment,
				'filename' => $Part->filename,
				'mime_type' => $Part->mimeType,
				'type' => strtolower($Part->subtype),
				'datatype' => $Part->datatype,
				'format' => $Part->format,
				'name' => $Part->name,
				'size' => $Part->bytes,
				'attachment' => $this->_fetchPart($Part),
			);
		}

		return $attachments;
	}

	protected function _fetchPart($Part) {
		$data = imap_fetchbody($this->Stream, $Part->uid, $Part->path, FT_UID | FT_PEEK);
		if ($Part->format === 'quoted-printable') {
			$data = quoted_printable_decode($data);
		} elseif ($this->config['auto_transform']) {
			$data = $this->_decodeString($data, $Part->encoding);
		}
		return $data;
	}

	/**
	 * @see http://www.nerdydork.com/download-pop3imap-email-attachments-with-php.html
	 */
	protected function _decodeString($message, $coding) {
		switch ($coding) {
			case 0:
			case 1:
				return imap_8bit($message);
			case 2:
				return imap_binary($message);
			case 3:
				return imap_base64($message);
			case 4:
				return imap_qprint($message);
			default:
				// plain
				return $message;
		}
	}

	/**
	 * ImapSource::_fetchFirstByMime()
	 *
	 * @param mixed $flatStructure
	 * @param mixed $mimeType
	 * @return string
	 */
	protected function _fetchFirstByMime($flatStructure, $mimeType) {
		foreach ($flatStructure as $path => $Part) {
			if ($mimeType === $Part->mimeType) {
				$text = $this->_fetchPart($Part);
				if ($Part->format === 'base64') {
					$text = base64_decode($text);
				}

				// No parameters, no charset to decode
				if (empty($Part->parameters)) {
					return $text;
				}

				// Try decode using the charset provided
				foreach ($Part->parameters as $param) {
					if ($param->attribute !== 'charset') {
						continue;
					}
					$params = (object)array(
						'charset' => $param->value,
						'text' => $text,
						);
					return $this->_decode($params);
				}

				// Fallback to original text
				return $text;
			}
		}
	}

	/**
	 * Get id for use in the mail protocol
	 *
	 * @param <type> $id
	 * @return string
	 */
	protected function _toUid($id) {
		if (is_array($id)) {
			return array_map(array($this, __FUNCTION__), $id);
		}

		$uid = $id;
		return $uid;
	}

	/**
	 * Get id for use in the code
	 *
	 * @param string $uid in the format <.*@.*> from the email
	 * @return mixed on imap its the unique id (int) and for others its a base64_encoded string
	 */
	protected function _toId($uid) {
		if (is_array($uid)) {
			return array_map(array($this, __function__ ), $uid);
		}

		$id = $uid;
		return $id;
	}

	/**
	 * Figure out how many emails there are in the thread for this mail.
	 *
	 * @param object $Mail the imap header of the mail
	 * @return integer the number of mails in the thred
	 */
	protected function _getThreadCount($Mail) {
		if (isset($Mail->reference) || isset($Mail->inReplyTo)) {
			return '?';
		}
		return 0;
	}

}
