<?php

namespace Tools\Test\TestCase\Mailer;

use App\Mailer\TestEmail;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Mailer\TransportFactory;
use Tools\Mailer\Email;
use Tools\TestSuite\TestCase;

/**
 * EmailTest class
 */
class EmailTest extends TestCase {

	/**
	 * @var \App\Mailer\TestEmail
	 */
	protected $Email;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->Email = new TestEmail();

		TransportFactory::setConfig('debug', [
			'className' => 'Debug'
		]);

		Configure::delete('Config.xMailer');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		Log::drop('email');
		Email::drop('test');
		TransportFactory::drop('debug');
		TransportFactory::drop('test_smtp');

		Configure::delete('Config.xMailer');
	}

	/**
	 * @return void
	 */
	public function testSetProfile() {
		Configure::write('Config.xMailer', 'foobar');

		$this->Email->setProfile('default');

		$result = $this->Email->getProtected('headers');
		$this->assertSame(['X-Mailer' => 'foobar'], $result);
	}

	/**
	 * @return void
	 */
	public function testFrom() {
		$this->assertSame(['test@example.com' => 'Mark'], $this->Email->getFrom());

		$this->Email->setFrom('cake@cakephp.org');
		$expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
		$this->assertSame($expected, $this->Email->getFrom());

		$this->Email->setFrom(['cake@cakephp.org']);
		$this->assertSame($expected, $this->Email->getFrom());

		$this->Email->setFrom('cake@cakephp.org', 'CakePHP');
		$expected = ['cake@cakephp.org' => 'CakePHP'];
		$this->assertSame($expected, $this->Email->getFrom());

		$result = $this->Email->setFrom(['cake@cakephp.org' => 'CakePHP']);
		$this->assertSame($expected, $this->Email->getFrom());
		$this->assertSame($this->Email, $result);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @return void
	 */
	public function testFromExecption() {
		$this->Email->setFrom(['cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address']);
	}

	/**
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
	 * @return void
	 */
	public function testAddAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email->setTo(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file);
		$res = $this->Email->send('test_default');
		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);

		$this->Email->reset();
		$this->Email->setTo(Configure::read('Config.adminEmail'));
		$this->Email->addAttachment($file, 'x.jpg');
		$res = $this->Email->send('test_custom_filename');

		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
	public function testAddEmbeddedAttachmentByContentId() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		$this->Email->addEmbeddedAttachmentByContentId('123', $file);

		$attachments = $this->Email->getProtected('attachments');
		$attachment = array_shift($attachments);
		$this->assertSame('image/png', $attachment['mimetype']);
		$this->assertSame('123', $attachment['contentId']);
	}

	/**
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachmentByContentId() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$content = file_get_contents($file);

		$this->Email->addEmbeddedBlobAttachmentByContentId('123', $content, $file);

		$attachments = $this->Email->getProtected('attachments');
		$attachment = array_shift($attachments);
		$this->assertNotEmpty($attachment['data']);
		$this->assertSame('image/png', $attachment['mimetype']);
		$this->assertSame('123', $attachment['contentId']);

		$this->Email->addEmbeddedBlobAttachmentByContentId('123', $content, $file, 'png');

		$attachments = $this->Email->getProtected('attachments');
		$attachment = array_shift($attachments);
		$this->assertSame('png', $attachment['mimetype']);
	}

	/**
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
	 * @return void
	 */
	public function testAddEmbeddedAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmail();
		$this->Email->setEmailFormat('both');

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
		$this->Email->setEmailFormat('both');
		$this->Email->setTo(Configure::read('Config.adminEmail'));
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
		$this->Email->setViewVars(compact('text', 'html'));

		$res = $this->Email->send();
		Configure::write('debug', 2);

		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->Email = new TestEmail();
		$this->Email->setEmailFormat('both');
		$cid = $this->Email->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png');

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
	 * @return void
	 */
	public function testValidates() {
		$this->Email = new TestEmail();
		$this->Email->setTransport('debug');
		$res = $this->Email->validates();
		$this->assertFalse($res);

		$this->Email->setSubject('foo');
		$res = $this->Email->validates();
		$this->assertFalse($res);

		$this->Email->setTo('some@web.de');
		$res = $this->Email->validates();
		$this->assertTrue($res);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		$this->Email = new TestEmail();
		$this->Email->setEmailFormat('both');
		$this->Email->setTo(Configure::read('Config.adminEmail'));
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
		$this->Email->setViewVars(compact('text', 'html'));

		$res = $this->Email->send();

		$this->assertEquals('', $this->Email->getError());
		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
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
