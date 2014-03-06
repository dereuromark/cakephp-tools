<?php
App::uses('CakeEmail', 'Network/Email');
App::uses('CakeLog', 'Log');
App::uses('MimeLib', 'Tools.Lib');

if (!defined('BR')) {
	define('BR', '<br />');
}

// Support BC (snake case config)
if (!Configure::read('Config.systemEmail')) {
	Configure::write('Config.systemEmail', Configure::read('Config.system_email'));
}
if (!Configure::read('Config.systemName')) {
	Configure::write('Config.systemName', Configure::read('Config.system_name'));
}
if (!Configure::read('Config.adminEmail')) {
	Configure::write('Config.adminEmail', Configure::read('Config.admin_email'));
}
if (!Configure::read('Config.adminName')) {
	Configure::write('Config.adminName', Configure::read('Config.admin_name'));
}

/**
 * Convenience class for internal mailer.
 *
 * Adds some useful features and fixes some bugs:
 * - enable easier attachment adding (and also from blob)
 * - enable embedded images in html mails
 * - extensive logging and error tracing
 * - create mails with blob attachments (embedded or attached)
 * - allow wrapLength to be adjusted
 * - Configure::read('Config.xMailer') can modify the x-mailer
 * - basic validation supported
 * - allow priority to be set (1 to 5)
 *
 * Configs for auto-from can be set via Configure::read('Config.adminEmail').
 * For systemEmail() one also needs Configure value Config.systemEmail to be set.
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.x
 */
class EmailLib extends CakeEmail {

	protected $_log = null;

	protected $_debug = null;

	protected $_error = null;

	protected $_wrapLength = null;

	protected $_priority = null;

	public function __construct($config = null) {
		if ($config === null) {
			$config = 'default';
		}
		parent::__construct($config);

		$this->resetAndSet();
	}

	/**
	 * Quick way to send emails to admin.
	 * App::uses() + EmailLib::systemEmail()
	 *
	 * Note: always go out with default settings (e.g.: SMTP even if debug > 0)
	 *
	 * @param string $subject
	 * @param string $message
	 * @param string $transportConfig
	 * @return boolean Success
	 */
	public static function systemEmail($subject, $message = 'System Email', $transportConfig = null) {
		$class = __CLASS__;
		$instance = new $class($transportConfig);
		$instance->from(Configure::read('Config.systemEmail'), Configure::read('Config.systemName'));
		$instance->to(Configure::read('Config.adminEmail'), Configure::read('Config.adminName'));
		if ($subject !== null) {
			$instance->subject($subject);
		}
		if (is_array($message)) {
			$instance->viewVars($message);
			$message = null;
		} elseif ($message === null && array_key_exists('message', $config = $instance->config())) {
			$message = $config['message'];
		}
		return $instance->send($message);
	}

	/**
	 * Change the layout
	 *
	 * @param string $layout Layout to use (or false to use none)
	 * @return resource EmailLib
	 */
	public function layout($layout = false) {
		if ($layout !== false) {
			$this->_layout = $layout;
		}
		return $this;
	}

	/**
	 * Add an attachment from file
	 *
	 * @param string $file: absolute path
	 * @param string $filename
	 * @param array $fileInfo
	 * @return resource EmailLib
	 */
	public function addAttachment($file, $name = null, $fileInfo = array()) {
		$fileInfo['file'] = $file;
		if (!empty($name)) {
			$fileInfo = array($name => $fileInfo);
		} else {
			$fileInfo = array($fileInfo);
		}
		return $this->addAttachments($fileInfo);
	}

	/**
	 * Add an attachment as blob
	 *
	 * @param binary $content: blob data
	 * @param string $filename to attach it
	 * @param string $mimeType (leave it empty to get mimetype from $filename)
	 * @param array $fileInfo
	 * @return resource EmailLib
	 */
	public function addBlobAttachment($content, $name, $mimeType = null, $fileInfo = array()) {
		$fileInfo['content'] = $content;
		$fileInfo['mimetype'] = $mimeType;
		$file = array($name => $fileInfo);
		return $this->addAttachments($file);
	}

	/**
	 * Add an inline attachment from file
	 *
	 * @param string $file: absolute path
	 * @param string $filename (optional)
	 * @param string $contentId (optional)
	 * @param array $options
	 * - mimetype
	 * - contentDisposition
	 * @return mixed resource $EmailLib or string $contentId
	 */
	public function addEmbeddedAttachment($file, $name = null, $contentId = null, $options = array()) {
		$path = realpath($file);
		if (empty($name)) {
			$name = basename($file);
		}
		if ($contentId === null && ($cid = $this->_isEmbeddedAttachment($path, $name))) {
			return $cid;
		}

		$options['file'] = $path;
		if (empty($options['mimetype'])) {
			$options['mimetype'] = $this->_getMime($file);
		}
		$options['contentId'] = $contentId ? $contentId : str_replace('-', '', String::uuid()) . '@' . $this->_domain;
		$file = array($name => $options);
		$res = $this->addAttachments($file);
		if ($contentId === null) {
			return $options['contentId'];
		}
		return $res;
	}

	/**
	 * Add an inline attachment as blob
	 *
	 * @param binary $content: blob data
	 * @param string $filename to attach it
	 * @param string $mimeType (leave it empty to get mimetype from $filename)
	 * @param string $contentId (optional)
	 * @param array $options
	 * - contentDisposition
	 * @return mixed resource $EmailLib or string $contentId
	 */
	public function addEmbeddedBlobAttachment($content, $name, $mimeType = null, $contentId = null, $options = array()) {
		$options['content'] = $content;
		$options['mimetype'] = $mimeType;
		$options['contentId'] = $contentId ? $contentId : str_replace('-', '', String::uuid()) . '@' . $this->_domain;
		$file = array($name => $options);
		$res = $this->addAttachments($file);
		if ($contentId === null) {
			return $options['contentId'];
		}
		return $res;
	}

	/**
	 * Returns if this particular file has already been attached as embedded file with this exact name
	 * to prevent the same image to overwrite each other and also to only send this image once.
	 * Allows multiple usage of the same embedded image (using the same cid)
	 *
	 * @return string cid of the found file or false if no such attachment can be found
	 */
	protected function _isEmbeddedAttachment($file, $name) {
		foreach ($this->_attachments as $filename => $fileInfo) {
			if ($filename !== $name) {
				continue;
			}
			if ($fileInfo['file'] === $file) {
				return $fileInfo['contentId'];
			}
		}
		return false;
	}

	/**
	 * Try to determine the mimetype by filename.
	 * Uses finfo_open() if availble, otherwise guesses it via file extension.
	 *
	 * @param string $filename
	 * @param string Mimetype
	 */
	protected function _getMime($filename) {
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
		} else {
			//TODO: improve
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$mimetype = $this->_getMimeByExtension($ext);
		}
		return $mimetype;
	}

	/**
	 * Try to find mimetype by file extension
	 *
	 * @param string $ext lowercase (jpg, png, pdf, ...)
	 * @param string $defaultMimeType
	 * @return string Mimetype (falls back to `application/octet-stream`)
	 */
	protected function _getMimeByExtension($ext, $default = 'application/octet-stream') {
		if (!isset($this->_Mime)) {
			$this->_Mime = new MimeLib();
		}
		$mime = $this->_Mime->getMimeType($ext);
		if (!$mime) {
			$mime = $default;
		}
		return $mime;
	}

	/**
	 * Validate if the email has the required fields necessary to make send() work.
	 * Assumes layouting (does not check on content to be present or if view/layout files are missing).
	 *
	 * @return boolean Success
	 */
	public function validates() {
		if (!empty($this->_subject) && !empty($this->_to)) {
			return true;
		}
		return false;
	}

	/**
	 * Attach inline/embedded files to the message.
	 *
	 * CUSTOM FIX: blob data support
	 *
	 * @override
	 * @param string $boundary Boundary to use. If null, will default to $this->_boundary
	 * @return array An array of lines to add to the message
	 */
	protected function _attachInlineFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = array();
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (empty($fileInfo['contentId'])) {
				continue;
			}
			if (!empty($fileInfo['content'])) {
				$data = $fileInfo['content'];
				$data = chunk_split(base64_encode($data));

			} elseif (!empty($fileInfo['file'])) {
				$data = $this->_readFile($fileInfo['file']);

			} else {
				continue;
			}

			$msg[] = '--' . $boundary;
			$msg[] = 'Content-Type: ' . $fileInfo['mimetype'];
			$msg[] = 'Content-Transfer-Encoding: base64';
			$msg[] = 'Content-ID: <' . $fileInfo['contentId'] . '>';
			$msg[] = 'Content-Disposition: inline; filename="' . $filename . '"';
			$msg[] = '';
			$msg[] = $data;
			$msg[] = '';
		}
		return $msg;
	}

	/**
	 * Attach non-embedded files by adding file contents inside boundaries.
	 *
	 * CUSTOM FIX: blob data support
	 *
	 * @override
	 * @param string $boundary Boundary to use. If null, will default to $this->_boundary
	 * @return array An array of lines to add to the message
	 */
	protected function _attachFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = array();
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (!empty($fileInfo['contentId'])) {
				continue;
			}
			if (!empty($fileInfo['content'])) {
				$data = $fileInfo['content'];
				$data = chunk_split(base64_encode($data));

			} elseif (!empty($fileInfo['file'])) {
				$data = $this->_readFile($fileInfo['file']);

			} else {
				continue;
			}

			$msg[] = '--' . $boundary;
			$msg[] = 'Content-Type: ' . $fileInfo['mimetype'];
			$msg[] = 'Content-Transfer-Encoding: base64';
			if (
				!isset($fileInfo['contentDisposition']) ||
				$fileInfo['contentDisposition']
			) {
				$msg[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
			}
			$msg[] = '';
			$msg[] = $data;
			$msg[] = '';
		}
		return $msg;
	}

	/**
	 * Add attachments to the email message
	 *
	 * CUSTOM FIX: blob data support
	 *
	 * Attachments can be defined in a few forms depending on how much control you need:
	 *
	 * Attach a single file:
	 *
	 * {{{
	 * $email->attachments('path/to/file');
	 * }}}
	 *
	 * Attach a file with a different filename:
	 *
	 * {{{
	 * $email->attachments(array('custom_name.txt' => 'path/to/file.txt'));
	 * }}}
	 *
	 * Attach a file and specify additional properties:
	 *
	 * {{{
	 * $email->attachments(array('custom_name.png' => array(
	 *		'file' => 'path/to/file',
	 *		'mimetype' => 'image/png',
	 *		'contentId' => 'abc123'
	 * ));
	 * }}}
	 *
	 * The `contentId` key allows you to specify an inline attachment. In your email text, you
	 * can use `<img src="cid:abc123" />` to display the image inline.
	 *
	 * @override
	 * @param mixed $attachments String with the filename or array with filenames
	 * @return mixed Either the array of attachments when getting or $this when setting.
	 * @throws SocketException
	 */
	public function attachments($attachments = null) {
		if ($attachments === null) {
			return $this->_attachments;
		}
		$attach = array();
		foreach ((array)$attachments as $name => $fileInfo) {
			if (!is_array($fileInfo)) {
				$fileInfo = array('file' => $fileInfo);
			}
			if (empty($fileInfo['content'])) {
				if (!isset($fileInfo['file'])) {
					throw new SocketException(__d('cake_dev', 'File not specified.'));
				}
				$fileInfo['file'] = realpath($fileInfo['file']);
				if ($fileInfo['file'] === false || !file_exists($fileInfo['file'])) {
					throw new SocketException(__d('cake_dev', 'File not found: "%s"', $fileInfo['file']));
				}
				if (is_int($name)) {
					$name = basename($fileInfo['file']);
				}
			}
			if (empty($fileInfo['mimetype'])) {
				$ext = pathinfo($name, PATHINFO_EXTENSION);
				$fileInfo['mimetype'] = $this->_getMimeByExtension($ext);
			}
			$attach[$name] = $fileInfo;
		}
		$this->_attachments = $attach;
		return $this;
	}

	/**
	 * Set the body of the mail as we send it.
	 * Note: the text can be an array, each element will appear as a seperate line in the message body.
	 *
	 * Do NOT pass a message if you use $this->set() in combination with templates
	 *
	 * @overwrite
	 * @param string/array: message
	 * @return boolean Success
	 */
	public function send($message = null) {
		$this->_log = array(
			'to' => $this->_to,
			'from' => $this->_from,
			'sender' => $this->_sender,
			'replyTo' => $this->_replyTo,
			'cc' => $this->_cc,
			'subject' => $this->_subject,
			'bcc' => $this->_bcc,
			'transport' => $this->_transportName
		);
		if ($this->_priority) {
			$this->_headers['X-Priority'] = $this->_priority;
			//$this->_headers['X-MSMail-Priority'] = 'High';
			//$this->_headers['Importance'] = 'High';
		}

		try {
			$this->_debug = parent::send($message);
		} catch (Exception $e) {
			$this->_error = $e->getMessage();
			$this->_error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL .
				$e->getTraceAsString();

			if (!empty($this->_config['logReport'])) {
				$this->_logEmail();
			} else {
				CakeLog::write('error', $this->_error);
			}
			return false;
		}

		if (!empty($this->_config['logReport'])) {
			$this->_logEmail();
		}
		return true;
	}

	/**
	 * Allow modifications of the message
	 *
	 * @param string $text
	 * @return string Text
	 */
	protected function _prepMessage($text) {
		return $text;
	}

	/**
	 * Returns the error if existent
	 *
	 * @return string
	 */
	public function getError() {
		return $this->_error;
	}

	/**
	 * Returns the debug content returned by send()
	 *
	 * @return string
	 */
	public function getDebug() {
		return $this->_debug;
	}

	/**
	 * Set/Get wrapLength
	 *
	 * @param integer $length Must not be more than CakeEmail::LINE_LENGTH_MUST
	 * @return integer|CakeEmail
	 */
	public function wrapLength($length = null) {
		if ($length === null) {
			return $this->_wrapLength;
		}
		$this->_wrapLength = $length;
		return $this;
	}

	/**
	 * Set/Get priority
	 *
	 * @param integer $priority 1 (highest) to 5 (lowest)
	 * @return integer|CakeEmail
	 */
	public function priority($priority = null) {
		if ($priority === null) {
			return $this->_priority;
		}
		$this->_priority = $priority;
		return $this;
	}

	/**
	 * Fix line length
	 *
	 * @overwrite
	 * @param string $message Message to wrap
	 * @return array Wrapped message
	 */
	protected function _wrap($message, $wrapLength = CakeEmail::LINE_LENGTH_MUST) {
		if ($this->_wrapLength !== null) {
			$wrapLength = $this->_wrapLength;
		}
		return parent::_wrap($message, $wrapLength);
	}

	/**
	 * Logs Email to type email
	 *
	 * @return void
	 */
	protected function _logEmail($append = null) {
		$res = $this->_log['transport'] .
			' - ' . 'TO:' . implode(',', array_keys($this->_log['to'])) .
			'||FROM:' . implode(',', array_keys($this->_log['from'])) .
			'||REPLY:' . implode(',', array_keys($this->_log['replyTo'])) .
			'||S:' . $this->_log['subject'];
		$type = 'email';
		if (!empty($this->_error)) {
			$type = 'email_error';
			$res .= '||ERROR:' . $this->_error;
		}
		if ($append) {
			$res .= '||' . $append;
		}
		CakeLog::write($type, $res);
	}

	/**
	 * EmailLib::resetAndSet()
	 *
	 * @return void
	 */
	public function resetAndSet() {
		$this->_to = array();
		$this->_cc = array();
		$this->_bcc = array();
		$this->_messageId = true;
		$this->_subject = '';
		$this->_headers = array();
		$this->_viewVars = array();
		$this->_textMessage = '';
		$this->_htmlMessage = '';
		$this->_message = '';
		$this->_attachments = array();

		$this->_error = null;
		$this->_debug = null;

		if ($fromEmail = Configure::read('Config.systemEmail')) {
			$fromName = Configure::read('Config.systemName');
		} else {
			$fromEmail = Configure::read('Config.adminEmail');
			$fromName = Configure::read('Config.adminName');
		}
		$this->from($fromEmail, $fromName);

		if ($xMailer = Configure::read('Config.xMailer')) {
			$this->addHeaders(array('X-Mailer' => $xMailer));
		}
	}

}
