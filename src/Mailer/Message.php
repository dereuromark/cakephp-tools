<?php

namespace Tools\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\Message as CakeMessage;
use InvalidArgumentException;
use Tools\Utility\Mime;
use Tools\Utility\Text;
use Tools\Utility\Utility;

/**
 * Allows locale overwrite to send emails in a specific language
 */
class Message extends CakeMessage {

	/**
	 * @var \Tools\Utility\Mime|null
	 */
	protected $_Mime;

	/**
	 * @param array|null $config Array of configs, or string to load configs from app.php
	 */
	public function __construct(?array $config = null) {
		parent::__construct($config);

		$xMailer = Configure::read('Config.xMailer');
		if ($xMailer) {
			$this->addHeaders(['X-Mailer' => $xMailer]);
		}
	}

	/**
	 * Overwrite to allow custom enhancements
	 *
	 * @param array|string $config
	 * @return $this
	 */
	public function _setProfile($config) {
		if (!is_array($config)) {
			$config = (string)$config;
		}
		//$this->_applyConfig($config);

		$fromEmail = Configure::read('Config.systemEmail');
		if ($fromEmail) {
			$fromName = Configure::read('Config.systemName');
		} else {
			$fromEmail = Configure::read('Config.adminEmail');
			$fromName = Configure::read('Config.adminName');
		}
		if ($fromEmail) {
			//$this->setFrom($fromEmail, $fromName);
		}

		return $this;
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
		parent::setAttachments($attach);

		return $this;
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

		$this->addAttachments($fileInfo);

		return $this;
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

		$this->addAttachments($file);

		return $this;
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
		$this->addAttachments($file);

		return $this;
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
	 * @param array $options Options
	 * @return string
	 */
	public function addEmbeddedAttachment(string $file, ?string $name = null, array $options = []): string {
		if (empty($name)) {
			$name = basename($file);
		}

		$name = pathinfo($name, PATHINFO_FILENAME) . '_' . md5($file) . '.' . pathinfo($name, PATHINFO_EXTENSION);
		$cid = $this->_isEmbeddedAttachment($file, $name);
		if ($cid) {
			return $cid;
		}

		$options['file'] = $file;
		if (empty($options['mimetype'])) {
			$options['mimetype'] = $this->_getMime($file);
		}
		$options['contentId'] = str_replace('-', '', Text::uuid()) . '@' . $this->getDomain();
		$file = [$name => $options];
		$this->addAttachments($file);

		return $options['contentId'];
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

		$this->addAttachments($file);

		return $this;
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
		$options['contentId'] = $contentId ? $contentId : str_replace('-', '', Text::uuid()) . '@' . $this->getDomain();
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
		foreach ($this->getAttachments() as $filename => $fileInfo) {
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
		foreach ($this->getAttachments() as $filename => $fileInfo) {
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
		$content = file_get_contents($path, false, $context);
		if (!$content) {
			trigger_error('No content found for ' . $path);
		}
		return chunk_split(base64_encode($content));
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
	protected function attachInlineFiles(?string $boundary = null): array {
		if ($boundary === null) {
			/** @var string $boundary */
			$boundary = $this->boundary;
		}

		$msg = [];
		foreach ($this->getAttachments() as $filename => $fileInfo) {
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

}
