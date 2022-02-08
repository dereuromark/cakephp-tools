<?php

namespace Tools\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Shim\TestSuite\TestCase;
use Tools\Mailer\Message as MailerMessage;

class MessageTest extends TestCase {

	/**
	 * @var \Tools\Mailer\Message
	 */
	protected $message;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->message = new MailerMessage();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		//Log::drop('email');
	}

	/**
	 * @return void
	 */
	public function testAddAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->message->addAttachment($file);
		$res = $this->message->getAttachments();
		$expected = [
			'hotel.png' => [
				'file' => $file,
				'mimetype' => 'image/png',
			],
		];
		$this->assertEquals($expected, $res);

		$this->message->addAttachment($file, 'my_image.jpg');

		$res = $this->message->getAttachments();
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

		$this->message->setTo(Configure::read('Config.adminEmail'));
		$this->message->addAttachment($file);
		$res = trim($this->message->getBodyString());
		$this->assertNotEmpty($res);
	}

	/**
	 * @return void
	 */
	public function testAddEmbeddedAttachmentByContentId() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		$this->message->addEmbeddedAttachmentByContentId('123', $file);

		$attachments = $this->message->getAttachments();
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

		$this->message->addEmbeddedBlobAttachmentByContentId('123', $content, $file);

		$attachments = $this->message->getAttachments();
		$attachment = array_shift($attachments);
		$this->assertNotEmpty($attachment['data']);
		$this->assertSame('image/png', $attachment['mimetype']);
		$this->assertSame('123', $attachment['contentId']);

		$this->message->addEmbeddedBlobAttachmentByContentId('123', $content, $file, 'png');

		$attachments = $this->message->getAttachments();
		$attachment = array_shift($attachments);
		$this->assertSame('png', $attachment['mimetype']);
	}

	/**
	 * @return void
	 */
	public function testAddBlobAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$content = file_get_contents($file);

		$this->message->addBlobAttachment($content, 'hotel.png');
		$res = $this->message->getAttachments();
		$this->assertTrue(!empty($res['hotel.png']['data']));
		unset($res['hotel.png']['data']);
		$expected = [
			'hotel.png' => [
				//'data' => $content,
				'mimetype' => 'image/png',
			],
		];
		$this->assertEquals($expected, $res);

		$this->message->addBlobAttachment($content, 'hotel.gif', 'image/jpeg');
		$res = $this->message->getAttachments();
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

		$this->message->setEmailFormat('both');

		$cid = $this->message->addEmbeddedAttachment($file);
		$cid2 = $this->message->addEmbeddedAttachment($file);
		$this->assertSame($cid, $cid2);
		$this->assertStringContainsString('@' . env('HTTP_HOST'), $cid);

		$res = $this->message->getAttachments();
		$this->assertSame(1, count($res));

		$image = array_shift($res);
		$expected = [
			'file' => $file,
			'mimetype' => 'image/png',
			'contentId' => $cid,
		];
		$this->assertSame($expected, $image);
	}

	/**
	 * @return void
	 */
	public function testAddEmbeddedBlobAttachment() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';
		$this->assertTrue(file_exists($file));

		$this->message->setEmailFormat('both');
		$cid = $this->message->addEmbeddedBlobAttachment(file_get_contents($file), 'my_hotel.png');

		$this->assertStringContainsString('@' . env('HTTP_HOST'), $cid);

		$res = $this->message->getAttachments();
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
		$this->message->addEmbeddedBlobAttachment(file_get_contents($file), 'my_other_hotel.png', 'image/jpeg', $cid, $options);

		$res = $this->message->getAttachments();
		$this->assertSame(2, count($res));

		$keys = array_keys($res);
		$keyLastRecord = $keys[count($keys) - 1];
		$this->assertSame('image/jpeg', $res[$keyLastRecord]['mimetype']);
		$this->assertTrue($res[$keyLastRecord]['contentDisposition']);

		$cid3 = $this->message->addEmbeddedBlobAttachment(file_get_contents($file) . 'xxx', 'my_hotel.png');
		$this->assertNotSame($cid3, $cid);

		$res = $this->message->getAttachments();
		$this->assertSame(3, count($res));
	}

}
