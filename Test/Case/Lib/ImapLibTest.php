<?php

App::uses('ImapLib', 'Tools.Lib');
App::uses('EmailLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * Testing IMAP send/receive
 *
 * The following config is needed:
 *   Configure::write('Mailbox.DEVTEST.address', 'devtest@host');
 *   Configure::write('Mailbox.DEVTEST.password', 'devtest');
 *   Configure::write('Mailbox.DEVTEST.host', 'host');
 *
 * The following files are needed in /APP/Test/test_files/:
 *   Sample_Email_PDF.pdf
 *   Sample_Email_PDF_UTF8.pdf
 *
 */
class ImapLibTest extends MyCakeTestCase {

	public $Imap;

	public function setUp() {
		parent::setUp();

		$this->skipIf(!function_exists('imap_open'), 'No Imap class installed');
		$this->skipIf(!Configure::read('Mailbox.DEVTEST'), 'No test account `DEVTEST` available');

		$this->Imap = new ImapLib();

		$this->testFilePath = APP . 'Test' . DS . 'test_files' . DS;
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Imap);
	}

	public function testObject() {
		$this->assertInstanceOf('ImapLib', $this->Imap);
	}

	public function testCount() {
		$count = $this->_count();
		//debug($count);
		$this->assertSame(0, $count);
	}

	public function testReceive() {
		$file = $this->testFilePath . 'Sample_Email_PDF.pdf';
		$this->_send($file);
		sleep(2);

		$messages = $this->_read();
		//debug($messages);
		$this->assertTrue(!empty($messages));
		$message = array_shift($messages);
		$this->assertTrue(!empty($message['subject']));
	}

	public function testReceiveUtf8() {
		$file = $this->testFilePath . 'Sample_Email_PDF_UTF8.pdf';
		$this->_send($file);
		sleep(2);

		$messages = $this->_read();
		//debug($messages);
		$this->assertTrue(!empty($messages));
		$message = array_shift($messages);
		$this->assertTrue(!empty($message['subject']));
	}

	protected function _send($file, $contentDisposition = false) {
		Configure::write('debug', 0);

		$this->Email = new EmailLib();
		$this->Email->to(Configure::read('Mailbox.DEVTEST.address'));
		$this->Email->subject('UTF8 ÄÖÜ Test Mail ' . date(FORMAT_DB_DATETIME));
		$this->Email->layout('blank');
		$this->Email->template('simple_email');
		$this->Email->addAttachment($file, 'test.php', array('contentDisposition' => $contentDisposition));
		$text = '';
		$this->Email->viewVars(compact('text'));
		if ($this->Email->send()) {
			Configure::write('debug', 2);
			return true;
		}
		Configure::write('debug', 2);
		trigger_error($this->Email->getError());
		return false;
	}

	protected function _count($code = 'DEVTEST') {
		$account = Configure::read('Mailbox.' . $code);
		if (!isset($account['host'])) {
			$account['host'] = Configure::read('Mailbox.host');
		}

		$Imap = new ImapLib();
		$Imap->set(ImapLib::S_SERVICE, 'imap');
		$Imap->set(ImapLib::S_NORSH, true);
		$res = $Imap->connect($account['address'], $account['password'], $account['host']);
		if (!$res) {
			throw new InternalErrorException('Error connecting: ' . $account['address'] . ' - ' . $account['host'] . ' (' . $account['password'] . ')');
		}
		$count = $Imap->msgCount();
		$Imap->close();
		return $count;
	}

	protected function _read($code = 'DEVTEST', $delete = true) {
		$account = Configure::read('Mailbox.' . $code);
		if (!isset($account['host'])) {
			$account['host'] = Configure::read('Mailbox.host');
		}

		$Imap = new ImapLib();
		$Imap->set(ImapLib::S_SERVICE, 'imap');
		$Imap->set(ImapLib::S_NORSH, true);
		//$Imap->set(ImapLib::S_NOTLS, true);
		//$Imap->set(ImapLib::S_TLS, true);
		/*
		if (($pos = strpos($account['address'], '@')) !== false) {
			$account['address'] = substr($account['address'], 0, $pos);
		}
		*/
		$res = $Imap->connect($account['address'], $account['password'], $account['host']);
		if (!$res) {
			//trigger_error($account['address'].' - '.Configure::read('Mailbox.host').' ('.$account['password'].')');
			throw new InternalErrorException('Error connecting: ' . $account['address'] . ' - ' . $account['host'] . ' (' . $account['password'] . ')');
			//return array();
		}
		//$count = $Imap->msgCount();
		$messages = $Imap->msgList();
		if ($delete) {
			$messageNumbers = Set::extract('/Msgno', $messages);
			//TODO: FIX Delete
			$res = $Imap->delete($messageNumbers, true);
		}
		$Imap->close();
		if (!is_array($messages)) {
			return array();
		}
		return $messages;
	}

}
