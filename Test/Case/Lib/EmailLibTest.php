<?php

App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('EmailLib', 'Tools.Lib');

//Configure::write('Config.adminEmail', '...');

class EmailLibTest extends MyCakeTestCase {

	public $Email;

	public $sendEmails = false;

	public function setUp() {
		parent::setUp();
		//$this->skipIf(!file_exists(APP . 'Config' . DS . 'email.php'), 'no email.php');

		$this->Email = new TestEmailLib();
	}

	/**
	 * EmailLibTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertTrue(is_object($this->Email));
		$this->assertInstanceOf('EmailLib', $this->Email);
	}

	/**
	 * EmailLibTest::testSendDefault()
	 *
	 * @return void
	 */
	public function testSendDefault() {
		// start
		$this->Email->to(Configure::read('Config.adminEmail'), Configure::read('Config.adminEmailname'));
		$this->Email->subject('Test Subject');

		$res = $this->Email->send('xyz xyz');
		// end
		if ($error = $this->Email->getError()) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);

		$this->Email->resetAndSet();
		// start
		$this->Email->to(Configure::read('Config.adminEmail'), Configure::read('Config.adminEmailname'));
		$this->Email->subject('Test Subject 2');
		$this->Email->template('default', 'default');
		$this->Email->viewVars(array('x' => 'y', 'xx' => 'yy', 'text' => ''));
		$this->Email->addAttachments(array(CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'edit.gif'));

		$res = $this->Email->send('xyz');
		// end
		if ($error = $this->Email->getError()) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);
	}

	/**
	 * EmailLibTest::testSendFast()
	 *
	 * @return void
	 */
	public function testSendFast() {
		//$this->Email->resetAndSet();
		//$this->Email->from(Configure::read('Config.adminEmail'), Configure::read('Config.adminEmailname'));
		$res = EmailLib::systemEmail('system-mail test', 'some fast email to admin test');
		//debug($res);
		$this->assertTrue($res);
	}

	/**
	 * EmailLibTest::testXMailer()
	 *
	 * @return void
	 */
	public function testXMailer() {
		$this->Email = new TestEmailLib();
		$this->Email->from('cake@cakephp.org');
		$this->Email->to('cake@cakephp.org');
		$this->Email->subject('My title');
		$this->Email->emailFormat('both');

		$result = $this->Email->send();
		$this->assertTrue($result);
		$result = $this->Email->getDebug();
		$this->assertTextContains('X-Mailer: CakePHP Email', $result['headers']);

		Configure::write('Config.xMailer', 'Tools Plugin');

		$this->Email = new TestEmailLib();
		$this->Email->from('cake@cakephp.org');
		$this->Email->to('cake@cakephp.org');
		$this->Email->subject('My title');
		$this->Email->emailFormat('both');

		$result = $this->Email->send();
		$this->assertTrue($result);
		$result = $this->Email->getDebug();
		$this->assertTextNotContains('X-Mailer: CakePHP Email', $result['headers']);
		$this->assertTextContains('X-Mailer: Tools Plugin', $result['headers']);
	}

	public function _testSendWithInlineAttachments() {
		$this->Email = new TestEmailLib();
		$this->Email->transport('debug');
		$this->Email->from('cake@cakephp.org');
		$this->Email->to('cake@cakephp.org');
		$this->Email->subject('My title');
		$this->Email->emailFormat('both');

		$result = $this->Email->send();
		//debug($result);

		$boundary = $this->Email->getBoundary();
		/*
		$this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
		$expected = "--$boundary\r\n" .
			"Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
			"\r\n" .
			"--rel-$boundary\r\n" .
			"Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/plain; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-$boundary\r\n" .
			"Content-Type: text/html; charset=UTF-8\r\n" .
			"Content-Transfer-Encoding: 8bit\r\n" .
			"\r\n" .
			"Hello" .
			"\r\n" .
			"\r\n" .
			"\r\n" .
			"--alt-{$boundary}--\r\n" .
			"\r\n" .
			"--rel-$boundary\r\n" .
			"Content-Type: application/octet-stream\r\n" .
			"Content-Transfer-Encoding: base64\r\n" .
			"Content-ID: <abc123>\r\n" .
			"Content-Disposition: inline; filename=\"cake.png\"\r\n\r\n";
		$this->assertContains($expected, $result['message']);
		$this->assertContains('--rel-' . $boundary . '--', $result['message']);
		$this->assertContains('--' . $boundary . '--', $result['message']);
		*/
		//debug($boundary);
		die();
	}

	/**
	 * EmailLibTest::testAddAttachment()
	 *
	 * @return void
	 */
	public function testAddAttachment() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email->addAttachment($file);

		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'hotel.png' => array(
				'file' => $file,
				'mimetype' => 'image/png',
			)
		);
		$this->assertEquals($expected, $res);

		$this->Email->addAttachment($file, 'my_image.jpg');

		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'file' => $file,
			'mimetype' => 'image/jpeg',
		);
		$this->assertEquals($expected, $res['my_image.jpg']);
	}

	/**
	 * EmailLibTest::testAddAttachment()
	 *
	 * @return void
	 */
	public function testAddAttachmentSend() {
		$this->skipIf(!$this->sendEmails);

		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));
		Configure::write('debug', 0);

		$this->Email->to(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file);
		$res = $this->Email->send('test_default', 'default');
		if ($error = $this->Email->getError()) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);

		$this->Email->resetAndSet();
		$this->Email->to(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file, 'x.jpg');
		$res = $this->Email->send('test_custom_filename');

		Configure::write('debug', 2);
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);
	}

	/**
	 * EmailLibTest::testAddBlobAttachment()
	 *
	 * @return void
	 */
	public function testAddBlobAttachment() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$content = file_get_contents($file);

		$this->Email->addBlobAttachment($content, 'hotel.png');
		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'hotel.png' => array(
				'content' => $content,
				'mimetype' => 'image/png',
			)
		);
		$this->assertEquals($expected, $res);

		$this->Email->addBlobAttachment($content, 'hotel.gif', 'image/jpeg');
		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'content' => $content,
			'mimetype' => 'image/jpeg',
		);
		$this->assertEquals($expected, $res['hotel.gif']);#
		$this->assertSame(2, count($res));
	}

	/**
	 * EmailLibTest::testAddEmbeddedAttachment()
	 *
	 * @return void
	 */
	public function testAddEmbeddedAttachment() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmailLib();
		$this->Email->emailFormat('both');

		$cid = $this->Email->addEmbeddedAttachment($file);
		$cid2 = $this->Email->addEmbeddedAttachment($file);
		$this->assertSame($cid, $cid2);
		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'hotel.png' => array(
				'file' => $file,
				'mimetype' => 'image/png; charset=binary',
				'contentId' => $cid
			)
		);
		$this->assertSame($expected, $res);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedAttachmentSend() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		Configure::write('debug', 0);
		$this->Email = new TestEmailLib();
		$this->Email->emailFormat('both');
		$this->Email->to(Configure::read('Config.adminEmail'));
		$cid = $this->Email->addEmbeddedAttachment($file);

		$cid2 = $this->Email->addEmbeddedAttachment($file);

		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$html = '<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="ohyeah" />

	<title>Untitled 6</title>
</head>
<body>
test_embedded_default äöü <img src="cid:' . $cid . '" /> end
another image <img src="cid:' . $cid2 . '" /> end
html-part
</body>
</html>';
		$text = trim(strip_tags($html));
		$this->Email->viewVars(compact('text', 'html'));

		if (!$this->sendEmails) {
			Configure::write('debug', 2);
		}
		$this->skipIf(!$this->sendEmails);

		$res = $this->Email->send();
		Configure::write('debug', 2);
		if ($error = $this->Email->getError()) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);
	}

	/**
	 * EmailLibTest::testAddEmbeddedBlobAttachment()
	 *
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachment() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmailLib();
		$this->Email->emailFormat('both');
		$cid = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png');

		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'my_hotel.png' => array(
				'content' => file_get_contents($file),
				'mimetype' => 'image/png',
				'contentId' => $cid,
			)
		);
		$this->assertEquals($expected, $res);

		$options = array(
			'contentDisposition' => true,
		);
		$cid = 'abcdef';
		$this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_other_hotel.png', 'image/jpeg', $cid, $options);

		$res = $this->Email->getProtected('attachments');
		$expected = array(
			'contentDisposition' => true,
			'content' => file_get_contents($file),
			'mimetype' => 'image/jpeg',
			'contentId' => $cid,
		);
		$this->assertEquals($expected, $res['my_other_hotel.png']);
	}

	/**
	 * EmailLibTest::testValidates()
	 *
	 * @return void
	 */
	public function testValidates() {
		$this->skipIf(php_sapi_name() === 'cli', 'For now...');

		$this->Email = new TestEmailLib();
		$res = $this->Email->validates();
		$this->assertFalse($res);
		$res = $this->Email->send();
		$this->assertFalse($res);

		$this->Email->subject('foo');
		$res = $this->Email->validates();
		$this->assertFalse($res);
		$res = $this->Email->send();
		$this->assertFalse($res);

		$this->Email->to('some@web.de');
		$res = $this->Email->validates();
		$this->assertTrue($res);
		$res = $this->Email->send();
		$this->assertTrue($res);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachmentSend() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		Configure::write('debug', 0);
		$this->Email = new TestEmailLib();
		$this->Email->emailFormat('both');
		$this->Email->to(Configure::read('Config.adminEmail'));
		$cid = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png', 'image/png');

		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$html = '<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="ohyeah" />

	<title>Untitled 6</title>
</head>
<body>
test_embedded_blob_default äöü <img src="cid:' . $cid . '" /> end
html-part
</body>
</html>';
		$text = trim(strip_tags($html));
		$this->Email->viewVars(compact('text', 'html'));

		if (!$this->sendEmails) {
			Configure::write('debug', 2);
		}
		$this->skipIf(!$this->sendEmails);

		$res = $this->Email->send();
		Configure::write('debug', 2);
		if ($error = $this->Email->getError()) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue($res);
	}

	public function _testComplexeHtmlWithEmbeddedImages() {
		$file = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		//TODO
	}

	public function testWrapLongEmailContent() {
		$this->Email = new TestEmailLib();

		$html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head></head><body style="color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 16px; text-align: left; vertical-align: top; margin: 0;">
sjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsf
</body></html>
HTML;
		//$html = str_replace(array("\r\n", "\n", "\r"), "", $html);
		$is = $this->Email->wrap($html);

		foreach ($is as $line => $content) {
			$this->assertTrue(strlen($content) <= EmailLib::LINE_LENGTH_MUST);
		}
		$this->debug($is);
		$this->assertTrue(count($is) >= 5);
	}

	public function testWrapCustomized() {
		$this->Email = new TestEmailLib();

		$html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head></head><body style="color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 16px; text-align: left; vertical-align: top; margin: 0;">
sjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsf
</body></html>
HTML;
		//$html = str_replace(array("\r\n", "\n", "\r"), "", $html);
		$this->Email->wrapLength(100);
		$is = $this->Email->wrap($html);

		foreach ($is as $line => $content) {
			$this->assertTrue(strlen($content) <= EmailLib::LINE_LENGTH_MUST);
		}
		$this->debug($is);
		$this->assertTrue(count($is) >= 16);
	}

}

/**
 * Help to test EmailLib
 *
 */
class TestEmailLib extends EmailLib {

	/**
	 * Wrap to protected method
	 *
	 */
	public function formatAddress($address) {
		return parent::_formatAddress($address);
	}

	/**
	 * Wrap to protected method
	 *
	 */
	public function wrap($text) {
		return parent::_wrap($text);
	}

	/**
	 * Get the boundary attribute
	 *
	 * @return string
	 */
	public function getBoundary() {
		return $this->_boundary;
	}

	public function getProtected($attribute) {
		$attribute = '_' . $attribute;
		return $this->$attribute;
	}

	/**
	 * Encode to protected method
	 *
	 */
	public function encode($text) {
		return $this->_encode($text);
	}

}
