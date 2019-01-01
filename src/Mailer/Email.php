<?php

namespace Tools\Mailer;

use Cake\Core\Configure;
use Cake\Log\LogTrait;
use Cake\Mailer\Email as CakeEmail;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Tools\Utility\Mime;
use Tools\Utility\Text;
use Tools\Utility\Utility;

class Email extends CakeEmail {

	use LogTrait;

	/**
	 * @var int|null
	 */
	protected $_wrapLength = null;

	/**
	 * @var string|null
	 */
	protected $_error = null;

	/**
	 * @var array|null
	 */
	protected $_debug = null;

	/**
	 * @var array
	 */
	protected $_log = [];

	/**
	 * @var \Tools\Utility\Mime|null
	 */
	protected $_Mime;

	/**
	 * @param string|null $config
	 */
	public function __construct($config = null) {
		if ($config === null) {
			$config = 'default';
		}
		parent::__construct($config);
	}

	/**
	 * Sets wrap length.
	 *
	 * @param int $length Must not be more than CakeEmail::LINE_LENGTH_MUST
	 * @return $this
	 */
	public function setWrapLength($length) {
		$this->_wrapLength = $length;
		return $this;
	}

	/**
	 * Gets wrap length.
	 *
	 * @return int
	 */
	public function getWrapLength() {
		return $this->_wrapLength;
	}

	/**
	 * Set/Get wrapLength
	 *
	 * @deprecated Use setWrapLenght()/getWrapLength() instead.
	 *
	 * @param int|null $length Must not be more than CakeEmail::LINE_LENGTH_MUST
	 * @return int|$this
	 */
	public function wrapLength($length = null) {
		if ($length === null) {
			return $this->getWrapLength();
		}

		return $this->setWrapLength($length);
	}

	/**
	 * Set/Get priority
	 *
	 * @deprecated Since CakePHP 3.4.0-RC4 in core as setPriority()/getPriority()
	 *
	 * @param int|null $priority 1 (highest) to 5 (lowest)
	 * @return int|$this
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
	 * @override To wrap by must length by default.
	 *
	 * @param string $message Message to wrap
	 * @param int $wrapLength
	 * @return array Wrapped message
	 */
	protected function _wrap($message, $wrapLength = CakeEmail::LINE_LENGTH_MUST) {
		if ($this->_wrapLength !== null) {
			$wrapLength = $this->_wrapLength;
		}
		return parent::_wrap($message, $wrapLength);
	}

	/**
	 * EmailLib::resetAndSet()
	 *
	 * @return void
	 */
	public function reset() {
		parent::reset();
		$this->_priority = null;
		$this->_wrapLength = null;

		$this->_error = null;
		$this->_debug = null;
	}

	/**
	 * Ovewrite to allow custom enhancements
	 *
	 * @param array|string $config
	 * @return $this
	 */
	public function setProfile($config) {
		if (!is_array($config)) {
			$config = (string)$config;
		}
		$this->_applyConfig($config);

		$fromEmail = Configure::read('Config.systemEmail');
		if ($fromEmail) {
			$fromName = Configure::read('Config.systemName');
		} else {
			$fromEmail = Configure::read('Config.adminEmail');
			$fromName = Configure::read('Config.adminName');
		}
		if ($fromEmail) {
			$this->setFrom($fromEmail, $fromName);
		}

		$xMailer = Configure::read('Config.xMailer');
		if ($xMailer) {
			$this->addHeaders(['X-Mailer' => $xMailer]);
		}

		return $this;
	}

	/**
	 * @deprecated Since CakePHP 3.4.0 - use setProfile()/getProfile() instead.
	 *
	 * @param mixed $config
	 * @return string|null|$this
	 */
	public function profile($config = null) {
		if ($config === null) {
			return $this->getProfile();
		}

		return $this->setProfile($config);
	}

	/**
	 * Overwrite to allow mimetype detection
	 *
	 * @param string|array $attachments String with the filename or array with filenames
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setAttachments($attachments) {
		$attach = [];
		foreach ((array)$attachments as $name => $fileInfo) {
			if (!is_array($fileInfo)) {
				$fileInfo = ['file' => $fileInfo];
			}
			if (!isset($fileInfo['file'])) {
				if (!isset($fileInfo['data'])) {
					throw new InvalidArgumentException('No file or data specified.');
				}
				if (is_int($name)) {
					throw new InvalidArgumentException('No filename specified.');
				}
			} else {
				$fileName = $fileInfo['file'];
				if (!preg_match('~^https?://~i', $fileInfo['file'])) {
					$fileInfo['file'] = realpath($fileInfo['file']);
				}
				if ($fileInfo['file'] === false || !Utility::fileExists($fileInfo['file'])) {
					throw new InvalidArgumentException(sprintf('File not found: "%s"', $fileName));
				}
				if (is_int($name)) {
					$name = basename($fileInfo['file']);
				}
			}
			if (!isset($fileInfo['mimetype'])) {
				$ext = pathinfo($name, PATHINFO_EXTENSION);
				$fileInfo['mimetype'] = $this->_getMimeByExtension($ext);
			}
			$attach[$name] = $fileInfo;
		}
		$this->_attachments = $attach;

		return $this;
	}

	/**
	 * @deprecated Since CakePHP 3.4.0 - use setAttachments()/getAttachments() instead.
	 *
	 * @param mixed|null $attachments
	 * @return array|$this
	 */
	public function attachments($attachments = null) {
		if ($attachments === null) {
			return $this->_attachments;
		}

		return $this->setAttachments($attachments);
	}

	/**
	 * Add an attachment from file
	 *
	 * @param string $file Absolute path
	 * @param string|null $name
	 * @param array $fileInfo
	 * @return $this
	 */
	public function addAttachment($file, $name = null, $fileInfo = []) {
		$fileInfo['file'] = $file;
		if (!empty($name)) {
			$fileInfo = [$name => $fileInfo];
		} else {
			$fileInfo = [$fileInfo];
		}
		return $this->addAttachments($fileInfo);
	}

	/**
	 * Add an attachment as blob
	 *
	 * @param string $content Blob data
	 * @param string $filename to attach it
	 * @param string|null $mimeType (leave it empty to get mimetype from $filename)
	 * @param array $fileInfo
	 * @return $this
	 */
	public function addBlobAttachment($content, $filename, $mimeType = null, $fileInfo = []) {
		if ($mimeType === null) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$mimeType = $this->_getMimeByExtension($ext);
		}
		$fileInfo['data'] = $content;
		$fileInfo['mimetype'] = $mimeType;
		$file = [$filename => $fileInfo];
		return $this->addAttachments($file);
	}

	/**
	 * Adds an inline attachment from file.
	 *
	 * Options:
	 * - mimetype
	 * - contentDisposition
	 *
	 * @param string $contentId
	 * @param string $file
	 * @param string|null $name
	 * @param array $options
	 * @return $this
	 */
	public function addEmbeddedAttachmentByContentId($contentId, $file, $name = null, array $options = []) {
		if (empty($name)) {
			$name = basename($file);
		}
		$name = pathinfo($name, PATHINFO_FILENAME) . '_' . md5($file) . '.' . pathinfo($name, PATHINFO_EXTENSION);

		$options['file'] = $file;
		if (empty($options['mimetype'])) {
			$options['mimetype'] = $this->_getMime($file);
		}
		$options['contentId'] = $contentId;
		$file = [$name => $options];
		return $this->addAttachments($file);
	}

	/**
	 * Adds an inline attachment from file.
	 *
	 * Options:
	 * - mimetype
	 * - contentDisposition
	 *
	 * @param string $file Absolute path
	 * @param string|null $name (optional)
	 * @param array|string|null $options Options - string CID is deprecated
	 * @param array $notUsed Former Options @deprecated 4th param is now 3rd since CakePHP 3.4.0 - Use addEmbeddedAttachmentByContentId() otherwise.
	 * @return string|null CID (null is deprecated)
	 */
	public function addEmbeddedAttachment($file, $name = null, $options = null, array $notUsed = []) {
		if (empty($name)) {
			$name = basename($file);
		}

		$contentId = null;
		// Deprecated $contentId here
		if (!is_array($options)) {
			$contentId = $options;
			$options = $notUsed;
		}

		$name = pathinfo($name, PATHINFO_FILENAME) . '_' . md5($file) . '.' . pathinfo($name, PATHINFO_EXTENSION);
		if ($contentId === null && ($cid = $this->_isEmbeddedAttachment($file, $name))) {
			return $cid;
		}

		$options['file'] = $file;
		if (empty($options['mimetype'])) {
			$options['mimetype'] = $this->_getMime($file);
		}
		$options['contentId'] = $contentId ?: str_replace('-', '', Text::uuid()) . '@' . $this->_domain;
		$file = [$name => $options];
		$this->addAttachments($file);
		if ($contentId === null) {
			return $options['contentId'];
		}

		// Deprecated
		return $contentId;
	}

	/**
	 * Adds an inline attachment from file.
	 *
	 * Options:
	 * - mimetype
	 * - contentDisposition
	 *
	 * @param string $contentId
	 * @param string $content Blob data
	 * @param string $file File File path to file
	 * @param string|null $mimeType (leave it empty to get mimetype from $filename)
	 * @param array $options
	 * @return $this
	 */
	public function addEmbeddedBlobAttachmentByContentId($contentId, $content, $file, $mimeType = null, array $options = []) {
		if ($mimeType === null) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$mimeType = $this->_getMimeByExtension($ext);
		}

		$filename = pathinfo($file, PATHINFO_FILENAME) . '_' . md5($content) . '.' . pathinfo($file, PATHINFO_EXTENSION);

		$options['data'] = $content;
		$options['mimetype'] = $mimeType;
		$options['contentId'] = $contentId;
		$file = [$filename => $options];
		$res = $this->addAttachments($file);

		return $res;
	}

	/**
	 * Add an inline attachment as blob
	 *
	 * Options:
	 * - contentDisposition
	 *
	 * @param string $content Blob data
	 * @param string $filename to attach it
	 * @param string|null $mimeType (leave it empty to get mimetype from $filename)
	 * @param array|string|null $options Options - string CID is deprecated
	 * @param array $notUsed
	 * @return string|null CID CcontentId (null is deprecated)
	 */
	public function addEmbeddedBlobAttachment($content, $filename, $mimeType = null, $options = null, array $notUsed = []) {
		if ($mimeType === null) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$mimeType = $this->_getMimeByExtension($ext);
		}

		$contentId = null;
		// Deprecated $contentId here
		if (!is_array($options)) {
			$contentId = $options;
			$options = $notUsed;
		}

		$filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . md5($content) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
		if ($contentId === null && ($cid = $this->_isEmbeddedBlobAttachment($content, $filename))) {
			return $cid;
		}

		$options['data'] = $content;
		$options['mimetype'] = $mimeType;
		$options['contentId'] = $contentId ? $contentId : str_replace('-', '', Text::uuid()) . '@' . $this->_domain;
		$file = [$filename => $options];
		$this->addAttachments($file);
		if ($contentId === null) {
			return $options['contentId'];
		}

		// Deprecated
		return $contentId;
	}

	/**
	 * Returns if this particular file has already been attached as embedded file with this exact name
	 * to prevent the same image to overwrite each other and also to only send this image once.
	 * Allows multiple usage of the same embedded image (using the same cid)
	 *
	 * @param string $file
	 * @param string $name
	 * @return bool|string CID of the found file or false if no such attachment can be found
	 */
	protected function _isEmbeddedAttachment($file, $name) {
		foreach ($this->_attachments as $filename => $fileInfo) {
			if ($filename !== $name) {
				continue;
			}
			return $fileInfo['contentId'];
		}
		return false;
	}

	/**
	 * Returns if this particular file has already been attached as embedded file with this exact name
	 * to prevent the same image to overwrite each other and also to only send this image once.
	 * Allows multiple usage of the same embedded image (using the same cid)
	 *
	 * @param string $content
	 * @param string $name
	 * @return bool|string CID of the found file or false if no such attachment can be found
	 */
	protected function _isEmbeddedBlobAttachment($content, $name) {
		foreach ($this->_attachments as $filename => $fileInfo) {
			if ($filename !== $name) {
				continue;
			}
			return $fileInfo['contentId'];
		}
		return false;
	}

	/**
	 * @param string $ext
	 * @param string $default
	 * @return mixed
	 */
	protected function _getMimeByExtension($ext, $default = 'application/octet-stream') {
		if (!isset($this->_Mime)) {
			$this->_Mime = new Mime();
		}
		$mime = $this->_Mime->getMimeTypeByAlias($ext);
		if (!$mime) {
			$mime = $default;
		}
		return $mime;
	}

	/**
	 * Try to find mimetype by file extension
	 *
	 * @param string $filename File name
	 * @param string $default default MimeType
	 * @return string Mimetype (falls back to `application/octet-stream`)
	 */
	protected function _getMime($filename, $default = 'application/octet-stream') {
		if (!isset($this->_Mime)) {
			$this->_Mime = new Mime();
		}
		$mime = $this->_Mime->detectMimeType($filename);
		// Some environments falsely return the default too fast, better fallback to extension here
		if (!$mime || $mime === $default) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$mime = $this->_Mime->getMimeTypeByAlias($ext);
		}
		return $mime;
	}

	/**
	 * Read the file contents and return a base64 version of the file contents.
	 * Overwrite parent to avoid File class and file_exists to false negative existent
	 * remove images.
	 * Also fixes file_get_contents (used via File class) to close the connection again
	 * after getting remote files. So far it would have kept the connection open in HTTP/1.1.
	 *
	 * @param string $path The absolute path to the file to read.
	 * @return string File contents in base64 encoding
	 */
	protected function _readFile($path) {
		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']]);
		$content = file_get_contents($path, 0, $context);
		if (!$content) {
			trigger_error('No content found for ' . $path);
		}
		return chunk_split(base64_encode($content));
	}

	/**
	 * Validate if the email has the required fields necessary to make send() work.
	 * Assumes layouting (does not check on content to be present or if view/layout files are missing).
	 *
	 * @return bool Success
	 */
	public function validates() {
		if (!empty($this->_subject) && !empty($this->_to)) {
			return true;
		}
		return false;
	}

	/**
	 * Set the body of the mail as we send it.
	 * Note: the text can be an array, each element will appear as a seperate line in the message body.
	 *
	 * Do NOT pass a message if you use $this->set() in combination with templates
	 *
	 * @override
	 * @param string|array|null $message Message
	 * @return bool Success
	 */
	public function send($message = null) {
		$this->_log = [
			'to' => $this->_to,
			'from' => $this->_from,
			'sender' => $this->_sender,
			'replyTo' => $this->_replyTo,
			'cc' => $this->_cc,
			'subject' => $this->_subject,
			'bcc' => $this->_bcc,
			'transport' => $this->_transport ? get_class($this->_transport) : null,
		];

		// if not live, just log but do not send any mails //TODO: remove and use Debug Transport!
		if (!Configure::read('Config.live')) {
			$this->_logEmail();
			return true;
		}

		// Security measure to not sent to the actual addressee in debug mode while email sending is live
		if (Configure::read('debug') && Configure::read('Config.live')) {
			$adminEmail = Configure::read('Config.adminEmail');

			if (!$adminEmail) {
				$adminEmail = Configure::read('Config.systemEmail');
			}

			foreach ($this->_to as $k => $v) {
				if ($k === $adminEmail) {
					continue;
				}
				unset($this->_to[$k]);
				$this->_to[$adminEmail] = $v;
			}
			foreach ($this->_cc as $k => $v) {
				if ($k === $adminEmail) {
					continue;
				}
				unset($this->_cc[$k]);
				$this->_cc[$adminEmail] = $v;
			}
			foreach ($this->_bcc as $k => $v) {
				if ($k === $adminEmail) {
					continue;
				}
				unset($this->_bcc[$k]);
				$this->_bcc[] = $v;
			}
		}

		try {
			$this->_debug = parent::send($message);
		} catch (\Exception $e) {
			$this->_error = $e->getMessage();
			$this->_error .= ' (line ' . $e->getLine() . ' in ' . $e->getFile() . ')' . PHP_EOL .
				$e->getTraceAsString();

			// always log report
			$this->_logEmail(LogLevel::ERROR);

			// log error
			$this->log($this->_error, LogLevel::ERROR);

			return false;
		}

		if (!empty($this->_profile['logReport'])) {
			$this->_logEmail();
		}

		return true;
	}

	/**
	 * @param string $level
	 * @return void
	 */
	protected function _logEmail($level = LogLevel::INFO) {
		$content =
			$this->_log['transport'] . (!Configure::read('Config.live') ? ' (simulated)' : '')
			. ' - ' . 'TO:' . implode(',', array_keys($this->_log['to']))
			. '||FROM:' . implode(',', array_keys($this->_log['from']))
			. '||REPLY:' . implode(',', array_keys($this->_log['replyTo']))
			. '||S:' . $this->_log['subject'];

		$this->log($content, $level);
	}

	/**
	 * Attach inline/embedded files to the message.
	 *
	 * CUSTOM FIX: blob data support
	 *
	 * @override
	 * @param string|null $boundary Boundary to use. If null, will default to $this->_boundary
	 * @return array An array of lines to add to the message
	 */
	protected function _attachInlineFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = [];
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (empty($fileInfo['contentId'])) {
				continue;
			}
			if (!empty($fileInfo['data'])) {
				$data = $fileInfo['data'];
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
	 * @param string|null $boundary Boundary to use. If null, will default to $this->_boundary
	 * @return array An array of lines to add to the message
	 */
	protected function _attachFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = [];
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (!empty($fileInfo['contentId'])) {
				continue;
			}
			if (!empty($fileInfo['data'])) {
				$data = $fileInfo['data'];
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
	 * Returns the error if existent
	 *
	 * @return string
	 */
	public function getError() {
		return $this->_error;
	}

}
