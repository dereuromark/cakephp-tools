<?php
/**
 * LICENSE: The MIT License
 * Copyright (c) 2010 Chris Nizzardini (http://www.cnizz.com)
 */

/**
 * Basic idea from
 * http://www.phpclasses.org/package/6256-PHP-Retrieve-messages-from-an-IMAP-server.html
 * added enhancements
 *
 * @modified 2011-11-13 Mark Scherer
 * @php 5
 * @cakephp 2.x
 *
 * ImapLib for accessing IMAP and POP email accounts
 */
class ImapLib {

	const S_MAILBOX = 'mailbox';
	const S_SERVER = 'server';
	const S_PORT = 'port';
	const S_SERVICE = 'service';
	const S_USER = 'user';
	const S_PASSWORD = 'password';
	const S_AUTHUSER = 'authuser';
	const S_DEBUG = 'debug';
	const S_SECURE = 'secure';
	const S_NORSH = 'norsa';
	const S_SSL = 'ssl';
	const S_VALIDATECERT = 'validatecert';
	const S_TLS = 'tls';
	const S_NOTLS = 'notls';
	const S_READONLY = 'readonly';

	public $stream;

	public $settings = array(
		self::S_MAILBOX => 'INBOX',
		self::S_SERVER => '',
		self::S_PORT => '',
		self::S_SERVICE => 'imap',
		self::S_USER => false,
		self::S_PASSWORD => '',
		self::S_AUTHUSER => false,
		self::S_DEBUG => false,
		self::S_SECURE => false,
		self::S_NORSH => false,
		self::S_SSL => false,
		self::S_VALIDATECERT => false,
		self::S_TLS => false,
		self::S_NOTLS => false,
		self::S_READONLY => false
	);

	public $currentSettings = array();

	public $currentRef = '';

	/**
	 * Check for basic dependencies in constructor.
	 */
	public function __construct() {
		$this->dependenciesMatch();
	}

	/**
	 * ImapLib::buildConnector()
	 *
	 * @param array $data
	 * @return string
	 */
	public function buildConnector($data = array()) {
		$data = array_merge($this->settings, $data);
		$string = '{';
		$string .= $data[self::S_SERVER];
		if ($data[self::S_PORT]) {
			$string .= ':' . $data[self::S_PORT];
		}
		if ($data[self::S_SERVICE]) {
			$string .= '/service=' . $data[self::S_SERVICE];
		}
		if ($data[self::S_USER]) {
			$string .= '/user=' . $data[self::S_USER];
		} else {
			$string .= '/anonymous';
		}
		if ($data[self::S_AUTHUSER]) {
			$string .= '/authuser=' . $data[self::S_AUTHUSER];
		}
		if ($data[self::S_DEBUG]) {
			$string .= '/debug';
		}
		if ($data[self::S_SECURE]) {
			$string .= '/secure';
		}
		if ($data[self::S_NORSH]) {
			$string .= '/norsh';
		}
		if ($data[self::S_SSL]) {
			$string .= '/ssl';
		}
		if ($data[self::S_VALIDATECERT]) {
			$string .= '/validate-cert';
		} else {
			$string .= '/novalidate-cert';
		}
		if ($data[self::S_TLS]) {
			$string .= '/tls';
		}
		if ($data[self::S_NOTLS]) {
			$string .= '/notls';
		}
		if ($data[self::S_READONLY]) {
			$string .= '/readonly';
		}
		$string .= '}';

		$string .= $data[self::S_MAILBOX];

		$this->currentRef = $string;
		$this->currentSettings = $data;
		return $string;
	}

	/**
	 * ImapLib::set()
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value) {
		$this->settings[$key] = $value;
	}

	/**
	 * ImapLib::lastError()
	 *
	 * @return string
	 */
	public function lastError() {
		return imap_last_error();
	}

	/**
	 * @return boolean Success
	 */
	public function connect($user, $pass, $server, $port = null) {
		$this->settings[self::S_SERVER] = $server;
		if ($port || !$port && $this->settings[self::S_SERVICE] === 'imap') {
			$this->settings[self::S_PORT] = $port;
		}
		$this->settings[self::S_USER] = $user;
		$this->settings[self::S_PASSWORD] = $pass;
		$connector = $this->buildConnector();

		//$options = OP_DEBUG;
		$this->stream = @imap_open($connector, $user, $pass, $options);
		if ($this->stream === false) {
			if ($error = $this->checkConnection()) {
				throw new ImapException($error);
			}
			return false;
		}
		return true;
	}

	/**
	 * ImapLib::checkConnection()
	 *
	 * @return mixed
	 */
	public function checkConnection() {
		if ($this->stream) {
			return $this->lastError();
		}
		return false;
	}

	/**
	 * ImapLib::msgCount()
	 *
	 * @return integer Count
	 */
	public function msgCount() {
		return imap_num_msg($this->stream);
	}

	public function listMailboxes($current = true) {
		if (is_bool($current)) {
			if ($current) {
				$current = '%';
			} else {
				$current = '*';
			}
		}
		return imap_list($this->stream, $this->currentRef, $current);
	}

	public function getFolder() {
		return new ImapFolderLib($this);
	}

	public function expunge() {
		return imap_expunge($this->stream);
	}

	public function close($expunge = false) {
		if ($expunge) {
			return @imap_close($this->stream, CL_EXPUNGE);
		}
		return @imap_close($this->stream);
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * Main listing of messages
	 * - body, structure, attachments
	 *
	 * @return array
	 */
	public function msgList($msgList = array()) {
		$return = array();

		if (empty($msgList)) {
			$count = $this->msgCount();
			for ($i = 1; $i <= $count; $i++) {
				$header = imap_headerinfo($this->stream, $i);
				$msgNo = trim($header->Msgno);
				foreach ($header as $id => $value) {
					// Simple array
					if (!is_array($value)) {
						$return[$msgNo][$id] = imap_utf8($value);
					} else {
						foreach ($value as $newid => $arrayValue) {
							foreach ($arrayValue as $key => $aValue) {
								$return[$msgNo][$id][$key] = imap_utf8($aValue);
							}
						}
					}

				}

				//lets add attachments
				$return[$msgNo]['structure'] = (array)imap_fetchstructure($this->stream, $msgNo);
				$encodingValue = $return[$msgNo]['structure']['encoding'];
				if (!empty($return[$msgNo]['structure']['parts'])) {
					$part = $return[$msgNo]['structure']['parts'][0];
					$encodingValue = $part->encoding;
				}
				// Let's add the body
				$return[$msgNo]['body'] = $this->_getDecodedValue(imap_fetchbody($this->stream, $msgNo, 1), $encodingValue);

				//debug(imap_fetchstructure($this->stream, $header->Msgno, FT_UID));
				$return[$msgNo]['attachments'] = $this->attachments($header);
			}
		}
		// We want to search a specific array of messages
		else {
			foreach ($msgList as $i) {
				$header = imap_headerinfo($this->stream, $i);
				foreach ($header as $id => $value) {
					// Simple array
					if (!is_array($value)) {
						$return[$header->Msgno][$id] = $value;
					} else {
						foreach ($value as $newid => $arrayValue) {
							foreach ($value[0] as $key => $aValue) {
								$return[$header->Msgno][$id][$key] = quoted_printable_decode($aValue);
							}
						}
					}
					$return[$header->Msgno]['structure'] = imap_fetchstructure($this->stream, $header->Msgno);
					$encodingValue = $return[$header->Msgno]['structure']->encoding;
					if (!empty($return[$header->Msgno]['structure']->parts)) {
						$part = $return[$header->Msgno]['structure']->parts[0];
						$encodingValue = $part->encoding;
					}
					// Let's add the body too!
					$return[$header->Msgno]['body'] = $this->_getDecodedValue(imap_fetchbody($this->stream, $header->Msgno, 1), $encodingValue);
					$return[$header->Msgno]['attachments'] = $this->attachments($header);
				}
			}
		}
		return $return;
	}

	/**
	 * @see http://www.nerdydork.com/download-pop3imap-email-attachments-with-php.html
	 * @param object $header
	 * @return array
	 */
	public function attachments($header) {
		$structure = imap_fetchstructure($this->stream, $header->Msgno);
		if (!$structure || !isset($structure->parts)) {
			return array();
		}
		$parts = $structure->parts;
		$fpos = 2;
		$message = array();
		$message["attachment"]["type"][0] = 'text';
		$message["attachment"]["type"][1] = 'multipart';
		$message["attachment"]["type"][2] = 'message';
		$message["attachment"]["type"][3] = 'application';
		$message["attachment"]["type"][4] = 'audio';
		$message["attachment"]["type"][5] = 'image';
		$message["attachment"]["type"][6] = 'video';
		$message["attachment"]["type"][7] = 'other';

		$attachments = array();
		$count = count($parts);
		for ($i = 1; $i < $count; $i++) {
			$attachment = array();
			$part = $parts[$i];
			if (isset($part->disposition) && $part->disposition === 'ATTACHMENT') {
				$attachment["pid"] = $i;
				$attachment["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype);
				$attachment["subtype"][$i] = strtolower($part->subtype);
				$ext = $part->subtype;
				$params = $part->dparameters;

				$data = imap_fetchbody($this->stream, $header->Msgno, $fpos);
				$attachment['filename'] = $part->dparameters[0]->value;
				$attachment['data'] = $this->_getDecodedValue($data, $part->encoding);
				$attachment['filesize'] = strlen($attachment['data']);

				$fpos++;
				$attachments[] = $attachment;

			} elseif (isset($part->subtype) && $part->subtype === "OCTET-STREAM") {
				$attachment["pid"] = $i;
				$attachment["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype);
				$attachment["subtype"][$i] = strtolower($part->subtype);
				$ext = $part->subtype;
				$params = $part->parameters;

				$data = imap_fetchbody($this->stream, $header->Msgno, $fpos);
				$attachment['filename'] = $part->dparameters[0]->value;
				$attachment['data'] = $this->_getDecodedValue($data, $part->encoding);
				$attachment['filesize'] = strlen($attachment['data']);

				$fpos++;
				$attachments[] = $attachment;

			} else { // inline attachments etc
				$attachment["pid"] = $i;
				$type = '';
				if (!empty($message["attachment"]["type"][$part->type])) {
					$type = $message["attachment"]["type"][$part->type] . "/";
				}
				$attachment["type"][$i] = $type . strtolower($part->subtype);
				$attachment["subtype"][$i] = strtolower($part->subtype);
				$ext = $part->subtype;
				$params = $part->parameters;

				$data = imap_fetchbody($this->stream, $header->Msgno, $fpos);
				$attachment['filename'] = !is_object($part->parameters) ? $part->parameters[0]->value : '';
				$attachment['data'] = $this->_getDecodedValue($data, $part->encoding);
				$attachment['filesize'] = strlen($attachment['data']);

				$fpos++;
				$attachments[] = $attachment;
			}
		}
		return $attachments;
	}

	/**
	 * Decode message text.
	 *
	 * @param string $message
	 * @param int $encoding
	 * @return string Message
	 * @see http://www.nerdydork.com/download-pop3imap-email-attachments-with-php.html
	 */
	protected function _getDecodedValue($message, $encoding) {
		if ($encoding == 0) {
			$message = imap_8bit($message);
			$message = $this->_decode7Bit($message);
			$message = imap_utf8($message);
		} elseif ($encoding == 1) {
			$message = imap_8bit($message);
			$message = quoted_printable_decode($message);
			$message = imap_utf8($message);
		} elseif ($encoding == 2) {
			$message = imap_binary($message);
		} elseif ($encoding == 3) {
			$message = imap_base64($message);
		} elseif ($encoding == 4) {
			$message = imap_qprint($message);
			$message = mb_convert_encoding($message, "UTF-8", "ISO-8859-1");
		} elseif ($encoding == 5) {
			// plain
		}
		return $message;
	}

	/**
	 * Decodes 7-Bit text.
	 *
	 * @param string $text 7-Bit text to convert.
	 * @return string Decoded text.
	 */
	protected function _decode7Bit($text) {
		// Manually convert common encoded characters into their UTF-8 equivalents.
		$characters = array(
			'=20' => ' ', // space.
			'=E2=80=99' => "'", // single quote.
			'=0A' => "\r\n", // line break.
			'=A0' => ' ', // non-breaking space.
			'=C2=A0' => ' ', // non-breaking space.
			"=\r\n" => '', // joined line.
			'=E2=80=A6' => '…', // ellipsis.
			'=E2=80=A2' => '•', // bullet.
		);
		foreach ($characters as $key => $value) {
			$text = str_replace($key, $value, $text);
		}
		return $text;
	}

	/**
	 * ImapLib::search()
	 *
	 * @param mixed $params
	 * @return string
	 */
	public function search($params) {
		if ($this->stream) {
			if (is_array($params)) {
				$searchString = '';
				foreach ($params as $field => $value) {
					if (is_numeric($field)) {
						// Make sure the value is uppercase
						$searchString .= strtoupper($value) . ' ';
					} else {
						$searchString .= strtoupper($field) . ' "' . $value . '" ';
					}
				}

				// Perform the search
				#echo "'$searchString'";
				return imap_search($this->stream, $searchString);
			}
			return imap_last_error();
		}

		return imap_last_error();
	}

	/**
	 * ImapLib::flag()
	 *
	 * @param mixed $flag
	 * @return boolean
	 */
	public function flag($flag) {
		return imap_setflag_full($this->ImapFolder->Imap->stream, $this->uid, $flag, ST_UID);
	}

	/**
	 * ImapLib::delete()
	 *
	 * @param string|array $emails
	 * @param bool $delete
	 * @return boolean Success
	 */
	public function delete($emails, $delete = false) {
		$emails = (array)$emails;
		foreach ($emails as $email) {
			if ($delete) {
				imap_delete($this->stream, (int)$email);
			} else {
				imap_mail_move($this->stream, (int)$email, "Inbox/Trash");
			}
		}
		return imap_expunge($this->stream);
	}

	/**
	 * @deprecated
	 */
	public function delx($emails, $delete = false) {
		if (!$this->stream) {
			return false;
		}
		$emails = (array)$emails;
		foreach ($emails as $key => $val) {
			$emails[$key] = (int)$val;
		}

		// Let's delete multiple emails
		if (count($emails) > 0) {
			$deleteString = '';
			$emailError = array();
			foreach ($emails as $email) {
				if ($delete) {
					if (!imap_delete($this->stream, $email)) {
						$emailError[] = $email;
					}
				}
			}
			if (!$delete) {
				// Need to take the last comma out!
				$deleteString = implode(',', $emails);
				echo $deleteString;
				imap_mail_move($this->stream, $deleteString, "Inbox/Trash");
				//imap_expunge($this->stream);
			} else {
				// NONE of the emails were deleted
				//imap_expunge($this->stream);

				if (count($emailError) === count($emails)) {
					return imap_last_error();
				}
				$return['status'] = false;
				$return['not_deleted'] = $emailError;
				return $return;
			}
		}
		// Not connected
		return imap_last_error();
	}

	/**
	 * @deprecated
	 */
	public function switch_mailbox($mailbox = '') {
		if ($this->stream) {
			$this->mbox = '{' . $this->server;

			if ($this->port) {
				$this->mbox .= ':' . $this->port;
			}

			if ($this->flags) {
				$this->mbox .= $this->flags;
			}

			$this->mbox .= '/user="' . $this->user . '"';
			$this->mbox .= '}';
			$this->mbox .= $this->defaultMailbox;

			if ($mailbox) {
				$this->mbox .= '.' . $mailbox;
			}

			return @imap_reopen($this->stream, $this->mbox);
		}

		// Not connected
		return imap_last_error();
	}

	/**
	 * @deprecated
	 */
	public function current_mailbox() {
		if ($this->stream) {
			$info = imap_mailboxmsginfo($this->stream);
			if ($info) {
				return $info->Mailbox;
			}
			// There was an error
			return imap_last_error();
		}

		// Not connected
		return imap_last_error();
	}

	/**
	 * @deprecated
	 */
	public function mailbox_info($type = 'obj') {
		if ($this->stream) {
			$info = imap_mailboxmsginfo($this->stream);
			if ($info) {
				if ($type === 'array') {
					$infoArray = get_object_vars($info);
					return $infoArray;
				}
				return $info;
			}
			// There was an error
			return imap_last_error();
		}

		// Not connected
		return imap_last_error();
	}

	/**
	 * Makes sure imap_open is available etc
	 *
	 * @throws InternalErrorException
	 * @return boolean Success
	 */
	public function dependenciesMatch() {
		if (!function_exists('imap_open')) {
			throw new InternalErrorException('imap_open not available. Please install extension/module.');
		}
		return true;
	}

}

// Currently NOT IN USE: //

/**
 * IMAP Postf�cher mit CakePHP abfragen
 * @see http://www.interaktionsdesigner.de/2009/05/11/imap-postfacher-mit-cakephp-abfragen/
 *
 * $this->Imap->connect();
 * Der R�ckgabewert dieser Funktion ist negativ wenn es nicht funktioniert hat.
 *
 * Eine gute Hilfe gegen verr�ckte Sonderzeichen und Kodierungen ist die Kombination von utf8_encode und quoted_printable_decode. Damit werden die meisten Umlaute richtig dargestellt.
 * F�r den Text der Mail w�re das dann innerhalb der foreach-Schleife:
 * debug(utf8_encode(quoted_printable_decode($message['body'])));
 *
 * fixes: pop3 connect etc
 */
class ImapMessageInfoLib {

	const BS = "\\";

	public $ImapFolder;

	public $ImapMessage;

	public $subject;

	public $from;

	public $to;

	public $date;

	public $messageId;

	public $references;

	public $inReplyTo;

	public $size;

	public $uid;

	public $msgno;

	public $recent;

	public $flagged;

	public $answered;

	public $deleted;

	public $seen;

	public $draft;

	public function __construct($ImapFolder, $data) {
		if (!is_object($data)) {
			$list = new ImapMessagesListLib($ImapFolder, array($data));
			$list = $list->overview(false);
			$data = $list[0];
		}
		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
		$this->ImapFolder = $ImapFolder;
	}

	public function messageObject() {
		if (!isset($this->ImapMessage)) {
			return $this->ImapMessage = new ImapMessageLib($this->ImapFolder, $this->uid, $this);
		}
		return $this->ImapMessage;
	}

	public function flag($flag) {
		return imap_setflag_full($this->ImapFolder->Imap->stream, $this->uid, $flag, ST_UID);
	}

	public function unFlag($flag) {
		return imap_clearflag_full($this->ImapFolder->Imap->stream, $this->uid, $flag, ST_UID);
	}

	public function seen($set = null) {
		if ($set === null) {
			return $this->seen;
		}
		if ($set) {
			return $this->flag(self::BS . 'Seen');
		}
		return $this->unFlag(self::BS . 'Seen');
	}

	public function answered($set = null) {
		if ($set === null) {
			return $this->answered;
		}
		if ($set) {
			return $this->flag(self::BS . 'Answered');
		}
		return $this->unFlag(self::BS . 'Answered');
	}

	public function flagged($set = null) {
		if ($set === null) {
			return $this->flagged;
		}
		if ($set) {
			return $this->flag(self::BS . 'Flagged');
		}
		return $this->unFlag(self::BS . 'Flagged');
	}

	public function deleted($set = null) {
		if ($set === null) {
			return $this->deleted;
		}
		if ($set) {
			return $this->flag(self::BS . 'Deleted');
		}
		return $this->unFlag(self::BS . 'Deleted');
	}

	public function draft($set = null) {
		if ($set === null) {
			return $this->draft;
		}
		if ($set) {
			return $this->flag(self::BS . 'Draft');
		}
		return $this->unFlag(self::BS . 'Draft');
	}

}

class ImapMessageLib {

	public $ImapFolder;

	public $MessageInfo;

	public $uid;

	public function __construct($ImapFolder, $uid, $ImapMessageInfo = null) {
		$this->ImapFolder = $ImapFolder;
		if ($ImapMessageInfo === null) {
			$this->MessageInfo = new ImapMessageInfoLib($this->ImapFolder, $uid);
		} else {
			$this->MessageInfo = $ImapMessageInfo;
		}
		$this->MessageInfo->ImapMessage = $this;
		$this->uid = $uid;
	}

	public function move($folder) {
	}

	public function id() {
		//CHANGE DIR TO CURRENT
		return imap_msgno($this->ImapFolder->Imap->stream, $this->uid);
	}

	public function uid($ID) {
		return $this->uid;
	}

	public function fetchstructure() {
		return imap_fetchstructure($this->ImapFolder->Imap->stream, $this->uid, FT_UID);
	}

	public function fetchbody($section = 0) {
		return imap_fetchbody($this->ImapFolder->Imap->stream, $this->uid, $section, (FT_UID + FT_PEEK));
	}

}

class ImapMessagesListLib {

	public $ImapFolder;

	public $messageUIDs = array();

	public function __construct($ImapFolder, $messageUIDs) {
		$this->ImapFolder = $ImapFolder;
		$this->messageUIDs = $messageUIDs;
	}

	public function messgageObject($id) {
		if (isset($this->messageUIDs[$id])) {
			if (is_object($this->messageUIDs[$id])) {
				return $this->messageUIDs[$id];
			}
			return $this->messageUIDs[$id] = new ImapMessageLib($this->ImapFolder, $this->messageUIDs[$id]);
		}
		return false;
	}

	public function count() {
		return count($this->messageUIDs);
	}

	public function overview($returnInfo = true) {
		//CHANGE DIR TO CURRENT
		$overview = imap_fetch_overview($this->ImapFolder->Imap->stream, implode(',', $this->messageUIDs), FT_UID);
		if ($returnInfo) {
			$msgObjs = array();
			foreach ($overview as $info) {
				$msgObjs[] = new ImapMessageInfoLib($this->ImapFolder, $info);
			}
			return $msgObjs;
		}
		return $overview;
	}

}

class ImapFolderLib {

	const S_ALL = 'ALL';
	const S_ANSWERED = 'ANSWERED';
	const S_BCC = 'BCC';
	const S_BEFORE = 'BEFORE';
	const S_BODY = 'BODY';
	const S_CC = 'CC';
	const S_DELETED = 'DELETED';
	const S_FLAGGED = 'FLAGGED';
	const S_FROM = 'FROM';
	const S_KEYWORD = 'KEYWORD';
	const S_NEW = 'NEW';
	const S_OLD = 'OLD';
	const S_ON = 'ON';
	const S_RECENT = 'RECENT';
	const S_SEEN = 'SEEN';
	const S_SINCE = 'SINCE';
	const S_SUBJECT = 'SUBJECT';
	const S_TEXT = 'TEXT';
	const S_TO = 'TO';
	const S_UNANSWERED = 'UNANSWERED';
	const S_UNDELETED = 'UNDELETED';
	const S_UNFLAGGED = 'UNFLAGGED';
	const S_UNKEYWORD = 'UNKEYWORD';
	const S_UNSEEN = 'UNSEEN';

	public $Imap;

	public $currentRef = '';

	public function __construct($Imap) {
		$this->Imap = $Imap;
		$this->currentRef = $this->Imap->currentRef;
	}

	public function listFolder() {
	}

	public function searchMessages($options = array(self::ALL)) {
		$optionstring = '';
		foreach ($options as $key => $value) {
			if (is_int($key)) {
				$key = $value;
				$value = null;
			}
			switch ($key) {
				case self::S_FROM:
					$param = '"' . $value . '" ';
					break;
				default:
					$param = '';
					break;
			}
			$optionstring .= $key . ' ' . $param;
		}
		//CHANGE DIR TO CURRENT
		$msg = imap_search($this->Imap->stream, $optionstring, SE_UID);
		if ($msg !== false) {
			return new ImapMessagesListLib($this, $msg);
		}
		return false;
	}

	public function allMessages() {
		return $this->searchMessages();
	}

}

class ImapException extends CakeException {

}
