<?php
App::uses('CakeEmail', 'Network/Email');
App::uses('CakeLog', 'Log');
App::uses('MimeLib', 'Tools.Lib');

if (!defined('BR')) {
	define('BR', '<br />');
}

/**
 * Convenience class for internal mailer.
 * Adds some nice features and fixes some bugs:
 * - enbale embedded images in html mails
 * - allow setting domain for CLI environment (now in core)
 * - enable easier attachment adding
 * - extensive logging and error tracing
 * - create mails with blob attachments (embedded or attached)
 * TODO: cleanup and more tests
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.2
 * 2012-03-30 ms
 */
class EmailLib extends CakeEmail {

	protected $_log = null;

	protected $_debug = null;

	protected $_error = null;

	public function __construct($config = null) {
		if ($config === null) {
			$config = 'default';
		}
		parent::__construct($config);

		$this->resetAndSet();
	}

	/**
	 * quick way to send emails to admin
	 * App::uses() + EmailLib::systemEmail()
	 *
	 * Note: always go out with default settings (e.g.: SMTP even if debug > 0)
	 * @return bool $success
	 * 2011-10-31 ms
	 */
	public static function systemEmail($subject, $message = 'System Email', $transportConfig = null) {
		$class = __CLASS__;
		$instance = new $class($transportConfig);
		$instance->from(Configure::read('Config.system_email'), Configure::read('Config.system_name'));
		$instance->to(Configure::read('Config.admin_email'), Configure::read('Config.admin_name'));
		if ($subject !== null) {
			$instance->subject($subject);
		}
		if (is_array($message)) {
			$instance->viewVars($message);
			$message = null;
		} elseif ($message === null && array_key_exists('message', $config = $instance->config())) {
			$message = $config['message'];
		}
		if (true || $send === true) {
			return $instance->send($message);
		}
		return $instance;
	}

	/**
	 * @param string $layout Layout to use (or false to use none)
	 * @return resource EmailLib
	 * 2011-11-02 ms
	 */
	public function layout($layout = false) {
		if ($layout !== false) {
			$this->_layout = $layout;
		}
		return $this;
	}

	/**
	 * @param string $file: absolute path
	 * @param string $filename (optional)
	 * @return resource EmailLib
	 * 2011-11-02 ms
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
	 * @param binary $content: blob data
	 * @param string $filename to attach it
	 * @param string $mimeType (leave it empty to get mimetype from $filename)
	 * @param string $contentId (optional)
	 * @return mixed ressource EmailLib or string $contentId
	 * 2011-11-02 ms
	 */
	public function addBlobAttachment($content, $name, $mimeType = null, $fileInfo = array()) {
		$fileInfo['content'] = $content;
		$fileInfo['mimetype'] = $mimeType;
		$file = array($name=>$fileInfo);
		$res = $this->addAttachments($file);
		if ($contentId === null) {
			return $fileInfo['contentId'];
		}
		return $res;
	}

	/**
	 * @param binary $content: blob data
	 * @param string $filename to attach it
	 * @param string $mimeType (leave it empty to get mimetype from $filename)
	 * @param string $contentId (optional)
	 * @param array $options
	 * - contentDisposition
	 * @return mixed ressource $EmailLib or string $contentId
	 * 2011-11-02 ms
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
	 * @param string $file: absolute path
	 * @param string $filename (optional)
	 * @param string $contentId (optional)
	 * @param array $options
	 * - mimetype
	 * - contentDisposition
	 * @return mixed ressource $EmailLib or string $contentId
	 * 2011-11-02 ms
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
	 * Returns if this particular file has already been attached as embedded file with this exact name
	 * to prevent the same image to overwrite each other and also to only send this image once.
	 * Allows multiple usage of the same embedded image (using the same cid)
	 *
	 * @return string cid of the found file or false if no such attachment can be found
	 */
	protected function _isEmbeddedAttachment($file, $name) {
		foreach ($this->_attachments as $filename => $fileInfo) {
			if ($filename != $name) {
				continue;
			}
			if ($fileInfo['file'] == $file) {
				return $fileInfo['contentId'];
			}
		}
		return false;
	}

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
	 * try to find mimetype by file extension
	 * @param string $ext lowercase (jpg, png, pdf, ...)
	 * @param string $defaultMimeType
	 * @return string $mimeType (falls back to )
	 * 2012-04-17 ms
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

	public function validates() {
		if (!empty($this->Email->subject)) {
			return true;
		}
		return false;
	}

	/**
	 * Attach inline/embedded files to the message.
	 * @override
	 * CUSTOM FIX: blob data support
	 *
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
	 * @override
	 * CUSTOM FIX: blob data support
	 *
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
	 * @override
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
	 * Get list of headers
	 * @override
	 * CUSTOM FIX: message id correctly set in CLI and can be passed in via domain()
	 *
	 * ### Includes:
	 *
	 * - `from`
	 * - `replyTo`
	 * - `readReceipt`
	 * - `returnPath`
	 * - `to`
	 * - `cc`
	 * - `bcc`
	 * - `subject`
	 *
	 * @param array $include
	 * @return array
	 */
	public function getHeaders($include = array()) {
		if ($include == array_values($include)) {
			$include = array_fill_keys($include, true);
		}
		$defaults = array_fill_keys(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject'), false);
		$include += $defaults;

		$headers = array();
		$relation = array(
			'from' => 'From',
			'replyTo' => 'Reply-To',
			'readReceipt' => 'Disposition-Notification-To',
			'returnPath' => 'Return-Path'
		);
		foreach ($relation as $var => $header) {
			if ($include[$var]) {
				$var = '_' . $var;
				$headers[$header] = current($this->_formatAddress($this->{$var}));
			}
		}
		if ($include['sender']) {
			if (key($this->_sender) === key($this->_from)) {
				$headers['Sender'] = '';
			} else {
				$headers['Sender'] = current($this->_formatAddress($this->_sender));
			}
		}

		foreach (array('to', 'cc', 'bcc') as $var) {
			if ($include[$var]) {
				$classVar = '_' . $var;
				$headers[ucfirst($var)] = implode(', ', $this->_formatAddress($this->{$classVar}));
			}
		}

		$headers += $this->_headers;
		if (!isset($headers['X-Mailer'])) {
			$headers['X-Mailer'] = self::EMAIL_CLIENT;
		}
		if (!isset($headers['Date'])) {
			$headers['Date'] = date(DATE_RFC2822);
		}
		if ($this->_messageId !== false) {
			if ($this->_messageId === true) {
				$headers['Message-ID'] = '<' . str_replace('-', '', String::UUID()) . '@' . $this->_domain . '>';
			} else {
				$headers['Message-ID'] = $this->_messageId;
			}
		}

		if ($include['subject']) {
			$headers['Subject'] = $this->_subject;
		}

		$headers['MIME-Version'] = '1.0';
		if (!empty($this->_attachments) || $this->_emailFormat === 'both') {
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
		} elseif ($this->_emailFormat === 'text') {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->charset;
		} elseif ($this->_emailFormat === 'html') {
			$headers['Content-Type'] = 'text/html; charset=' . $this->charset;
		}
		$headers['Content-Transfer-Encoding'] = $this->_getContentTransferEncoding();

		return $headers;
	}

	/**
	 * Apply the config to an instance
	 *
	 * @param CakeEmail $obj CakeEmail
	 * @param array $config
	 * @return void
	 * @throws ConfigureException When configuration file cannot be found, or is missing
	 * the named config.
	 */
	protected function _applyConfig($config) {
		if (is_string($config)) {
			if (!class_exists('EmailConfig') && !config('email')) {
				throw new ConfigureException(__d('cake_dev', '%s not found.', APP . 'Config' . DS . 'email.php'));
			}
			$configs = new EmailConfig();
			if (!isset($configs->{$config})) {
				throw new ConfigureException(__d('cake_dev', 'Unknown email configuration "%s".', $config));
			}
			$config = $configs->{$config};
		}
		$this->_config += $config;
		if (!empty($config['charset'])) {
			$this->charset = $config['charset'];
		}
		if (!empty($config['headerCharset'])) {
			$this->headerCharset = $config['headerCharset'];
		}
		if (empty($this->headerCharset)) {
			$this->headerCharset = $this->charset;
		}
		$simpleMethods = array(
			'from', 'sender', 'to', 'replyTo', 'readReceipt', 'returnPath', 'cc', 'bcc',
			'messageId', 'domain', 'subject', 'viewRender', 'viewVars', 'attachments',
			'transport', 'emailFormat'
		);
		foreach ($simpleMethods as $method) {
			if (isset($config[$method])) {
				$this->$method($config[$method]);
				unset($config[$method]);
			}
		}
		if (isset($config['headers'])) {
			$this->setHeaders($config['headers']);
			unset($config['headers']);
		}
		if (array_key_exists('template', $config)) {
			$layout = false;
			if (array_key_exists('layout', $config)) {
				$layout = $config['layout'];
				unset($config['layout']);
			}
			$this->template($config['template'], $layout);
			unset($config['template']);
		}
		$this->transportClass()->config($config);
	}

	/**
	 * Set the body of the mail as we send it.
	 * Note: the text can be an array, each element will appear as a seperate line in the message body.
	 *
	 * LEAVE empty if you use $this->set() in combination with templates
	 *
	 * @param string/array: message
	 * @return bool $success
	 */
	public function send($message = null) {
		$this->_log = array(
			'to' => $this->_to,
			'from' => $this->_from,
			'sender' => $this->_sender,
			'replyTo' => $this->_replyTo,
			'cc' => $this->_cc,
			'subject' => $this->_subject,
			'cc' => $this->_cc,
			'transport' => $this->_transportName
		);

		# prep images for inline
		/*
		if ($this->_emailFormat !== 'text') {
			if ($message !== null) {
				$message = $this->_prepMessage($message);
			} else {
				$this->_htmlMessage = $this->_prepMessage($this->_htmlMessage);
			}
		}
		*/
		try {
			$this->_debug = parent::send($message);
		} catch (Exception $e) {
			$this->_error = $e->getMessage();
			$this->_error .= ' (line '.$e->getLine().' in '.$e->getFile().')'.PHP_EOL.$e->getTraceAsString();

			if (!empty($this->_config['report'])) {
				$this->_logEmail();
			}
			return false;
		}

		if (!empty($this->_config['report'])) {
			$this->_logEmail();
		}
		return true;
	}

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
	 * Logs Email to type email
	 * @return void
	 */
	protected function _logEmail($append = null) {
 		$res = $this->_log['transport'].
			' - '.'TO:'.implode(',', array_keys($this->_log['to'])).
			'||FROM:'.implode(',', array_keys($this->_log['from'])).
			'||REPLY:'.implode(',', array_keys($this->_log['replyTo'])).
			'||S:'.$this->_log['subject'];
 		$type = 'email';
		 if (!empty($this->_error)) {
		 	$type = 'email_error';
 			$res .= '||ERROR:' . $this->_error;
 		}
		if ($append) {
			$res .= '||'.$append;
		}
		CakeLog::write($type, $res);
	}

	public function resetAndSet() {
		//$this->reset();

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

		if ($fromEmail = Configure::read('Config.system_email')) {
			$fromName =  Configure::read('Config.system_name');
		} else {
			$fromEmail = Configure::read('Config.admin_email');
			$fromName = Configure::read('Config.admin_name');
		}
		$this->from($fromEmail, $fromName);

		if ($xMailer = Configure::read('Config.x-mailer')) {
			$this->addHeaders(array('X-Mailer'=>$xMailer));
		}
		//$this->_errors = array();
		//$this->charset($this->charset);
		//$this->sendAs($this->sendAs);
		//$this->layout($this->_layout);
		//$this->delivery($this->deliveryMethod);
	}

}
