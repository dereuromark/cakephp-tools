<?php

namespace Tools\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Tools\Mailer\Email;
use Tools\TestSuite\TestCase;

/**
 * Help to test Email
 */
class TestEmail extends Email {

	/**
	 * Wrap to protected method
	 *
	 * @param array $address
	 * @return array
	 */
	public function formatAddress($address) {
		return parent::_formatAddress($address);
	}

	/**
	 * Wrap to protected method
	 *
	 * @param string $text
	 * @param int $length
	 * @return array
	 */
	public function wrap($text, $length = Email::LINE_LENGTH_MUST) {
		return parent::_wrap($text, $length);
	}

	/**
	 * Get the boundary attribute
	 *
	 * @return string
	 */
	public function getBoundary() {
		return $this->_boundary;
	}

	/**
	 * Encode to protected method
	 *
	 * @param string $text
	 * @return string
	 */
	public function encode($text) {
		return $this->_encode($text);
	}

	/**
	 * Render to protected method
	 *
	 * @param string $content
	 * @return array
	 */
	public function render($content) {
		return $this->_render($content);
	}

	/**
	 * TestEmail::getProtected()
	 *
	 * @param string $attribute
	 * @return mixed
	 */
	public function getProtected($attribute) {
		$attribute = '_' . $attribute;
		return $this->$attribute;
	}

}

/**
 * EmailTest class
 */
class EmailTest extends TestCase {

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Email = new TestEmail();

		Email::configTransport('debug', [
			'className' => 'Debug'
		]);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		Log::drop('email');
		Email::drop('test');
		Email::dropTransport('debug');
		Email::dropTransport('test_smtp');
	}

	/**
	 * testFrom method
	 *
	 * @return void
	 */
	public function testFrom() {
		$this->assertSame(['test@example.com' => 'Mark'], $this->Email->from());

		$this->Email->from('cake@cakephp.org');
		$expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
		$this->assertSame($expected, $this->Email->from());

		$this->Email->from(['cake@cakephp.org']);
		$this->assertSame($expected, $this->Email->from());

		$this->Email->from('cake@cakephp.org', 'CakePHP');
		$expected = ['cake@cakephp.org' => 'CakePHP'];
		$this->assertSame($expected, $this->Email->from());

		$result = $this->Email->from(['cake@cakephp.org' => 'CakePHP']);
		$this->assertSame($expected, $this->Email->from());
		$this->assertSame($this->Email, $result);

		$this->setExpectedException('InvalidArgumentException');
		$result = $this->Email->from(['cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address']);
	}

	/**
	 * EmailTest::testAddAttachment()
	 *
	 * @return void
	 */
	public function testAddAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email->addAttachment($file);
		$res = $this->Email->getProtected('attachments');
		$expected = [
			'hotel.png' => [
				'file' => $file,
				'mimetype' => 'image/png',
			]
		];
		$this->assertEquals($expected, $res);

		$this->Email->addAttachment($file, 'my_image.jpg');

		$res = $this->Email->getProtected('attachments');
		$expected = [
			'file' => $file,
			'mimetype' => 'image/jpeg',
		];
		$this->assertEquals($expected, $res['my_image.jpg']);
	}

	/**
	 * EmailTest::testAddAttachment()
	 *
	 * @return void
	 */
	public function testAddAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));
		//Configure::write('debug', 0);

		$this->Email->to(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file);
		$res = $this->Email->send('test_default', 'default');
		$error = $this->Email->getError();
		if ($error) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);

		$this->Email->reset();
		$this->Email->to(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file, 'x.jpg');
		$res = $this->Email->send('test_custom_filename');

		//Configure::write('debug', 2);
		//$this->assertEquals('', $this->Email->getError());
		//$this->assertTrue($res);
	}

	/**
	 * EmailTest::testAddBlobAttachment()
	 *
	 * @return void
	 */
	public function testAddBlobAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$content = file_get_contents($file);

		$this->Email->addBlobAttachment($content, 'hotel.png');
		$res = $this->Email->getProtected('attachments');
		$this->assertTrue(!empty($res['hotel.png']['data']));
		unset($res['hotel.png']['data']);
		$expected = [
			'hotel.png' => [
				//'data' => $content,
				'mimetype' => 'image/png',
			]
		];
		$this->assertEquals($expected, $res);

		$this->Email->addBlobAttachment($content, 'hotel.gif', 'image/jpeg');
		$res = $this->Email->getProtected('attachments');
		$this->assertTrue(!empty($res['hotel.gif']['data']));
		unset($res['hotel.gif']['data']);
		$expected = [
			//'data' => $content,
			'mimetype' => 'image/jpeg',
		];
		$this->assertEquals($expected, $res['hotel.gif']);
		$this->assertSame(2, count($res));
	}

	/**
	 * EmailTest::testAddEmbeddedAttachment()
	 *
	 * @return void
	 */
	public function testAddEmbeddedAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmail();
		$this->Email->emailFormat('both');

		$cid = $this->Email->addEmbeddedAttachment($file);
		$cid2 = $this->Email->addEmbeddedAttachment($file);
		$this->assertSame($cid, $cid2);
		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$res = $this->Email->getProtected('attachments');
		$this->assertSame(1, count($res));

		$image = array_shift($res);
		$expected = [
			'file' => $file,
			'mimetype' => 'image/png',
			'contentId' => $cid
		];
		$this->assertSame($expected, $image);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		Configure::write('debug', 0);
		$this->Email = new TestEmail();
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

		$res = $this->Email->send();
		Configure::write('debug', 2);
		$error = $this->Email->getError();
		if ($error) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);
	}

	/**
	 * EmailTest::testAddEmbeddedBlobAttachment()
	 *
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmail();
		$this->Email->emailFormat('both');
		$cid = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png');
		$cid2 = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png');

		$this->assertContains('@' . env('HTTP_HOST'), $cid);

		$res = $this->Email->getProtected('attachments');
		$this->assertSame(1, count($res));

		$images = $res;
		$image = array_shift($images);
		unset($image['data']);
		$expected = [
			'mimetype' => 'image/png',
			'contentId' => $cid,
		];
		$this->assertEquals($expected, $image);

		$options = [
			'contentDisposition' => true,
		];
		$cid = 'abcdef';
		$this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_other_hotel.png', 'image/jpeg', $cid, $options);

		$res = $this->Email->getProtected('attachments');
		$this->assertSame(2, count($res));

		$keys = array_keys($res);
		$keyLastRecord = $keys[count($keys) - 1];
		$this->assertSame('image/jpeg', $res[$keyLastRecord]['mimetype']);
		$this->assertTrue($res[$keyLastRecord]['contentDisposition']);

		$cid3 = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file) . 'xxx', 'my_hotel.png');
		$this->assertNotSame($cid3, $cid);

		$res = $this->Email->getProtected('attachments');
		$this->assertSame(3, count($res));
	}

	/**
	 * EmailTest::testValidates()
	 *
	 * @return void
	 */
	public function testValidates() {
		$this->Email = new TestEmail();
		$this->Email->transport('debug');
		$res = $this->Email->validates();
		$this->assertFalse($res);
		//$res = $this->Email->send();
		//$this->assertFalse($res);

		$this->Email->subject('foo');
		$res = $this->Email->validates();
		$this->assertFalse($res);
		//$res = $this->Email->send();
		//$this->assertFalse($res);

		$this->Email->to('some@web.de');
		$res = $this->Email->validates();
		$this->assertTrue($res);
		//$res = $this->Email->send();
		//$this->assertTrue($res);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		$this->Email = new TestEmail();
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

		$res = $this->Email->send();

		$error = $this->Email->getError();
		if ($error) {
			$this->out($error);
		}
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);
	}

	public function _testComplexeHtmlWithEmbeddedImages() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		//TODO
	}

	/**
	 * EmailTest::testWrapLongEmailContent()
	 *
	 * @return void
	 */
	public function testWrapLongEmailContent() {
		$this->Email = new TestEmail();

		$html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head></head><body style="color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 16px; text-align: left; vertical-align: top; margin: 0;">
sjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsfsjdf ojdshfdsf odsfh dsfhodsf hodshfhdsjdshfjdshfjdshfj dsjfh jdsfh ojds hfposjdf pohpojds fojds hfojds fpojds foijds fpodsij fojdsnhfojdshf dsufhpodsufds fuds foudshf ouds hfoudshf udsofhuds hfouds hfouds hfoudshf udsh fouhds fluds hflsdu hflsud hfuldsuhf dsf
</body></html>
HTML;
		//$html = str_replace(array("\r\n", "\n", "\r"), "", $html);
		$is = $this->Email->wrap($html);

		foreach ($is as $line => $content) {
			$this->assertTrue(strlen($content) <= Email::LINE_LENGTH_MUST);
		}
		$this->debug($is);
		$this->assertTrue(count($is) >= 5);
	}

	/**
	 * EmailTest::testWrapCustomized()
	 *
	 * @return void
	 */
	public function testWrapCustomized() {
		$this->Email = new TestEmail();

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
			$this->assertTrue(strlen($content) <= Email::LINE_LENGTH_MUST);
		}
		$this->debug($is);
		$this->assertTrue(count($is) >= 16);
	}

}
