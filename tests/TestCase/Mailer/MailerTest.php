<?php

namespace Tools\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\TransportFactory;
use Shim\TestSuite\TestCase;
use TestApp\Mailer\TestMailer;
use Tools\Mailer\Mailer;

/**
 * EmailTest class
 */
class MailerTest extends TestCase {

	/**
	 * @var \TestApp\Mailer\TestMailer
	 */
	protected $mailer;

	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		I18n::setLocale(I18n::getDefaultLocale());
		Configure::write('Config.defaultLocale', 'deu');

		//Mailer::setConfig('test');
		TransportFactory::setConfig('debug', [
			'className' => 'Debug',
		]);

		$this->mailer = new TestMailer();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		Log::drop('email');
		Mailer::drop('test');
		TransportFactory::drop('debug');
		TransportFactory::drop('test_smtp');

		I18n::setLocale(I18n::getDefaultLocale());
	}

	/**
	 * @return void
	 */
	public function testSend(): void {
		$mailer = $this->mailer;

		$result = $mailer->send();
		$this->assertNotEmpty($result);
	}

	/**
	 * @return void
	 */
	public function testDeliver(): void {
		$mailer = $this->mailer;

		$result = $mailer->deliver('Foo Bar');
		$this->assertTextContains('Foo Bar', $result['message']);
	}

	/**
	 * @return void
	 */
	public function testDeliverLocaleDefault(): void {
		$mailer = $this->mailer;

		$mailer->setEmailFormat('both');
		$mailer->setSubject('Test me');
		$mailer->setViewVars(['value' => 123.45]);
		$mailer->viewBuilder()
			->setTemplate('welcome')
			->setLayout('fancy');

		$result = $mailer->deliver();
		$this->assertNotEmpty($result);
		$this->assertTextContains('**My price**: 123,45', $result['message']);
		$this->assertTextContains('<b>My price</b>: 123,45', $result['message']);
	}

	/**
	 * @return void
	 */
	public function testDeliverLocaleCustom(): void {
		I18n::setLocale('eng');

		$mailer = new TestMailer();

		$mailer->setLocale('deu');
		$mailer->setEmailFormat('both');
		$mailer->setSubject('Test me');
		$mailer->setViewVars(['value' => 123.45]);
		$mailer->viewBuilder()
			->setTemplate('welcome')
			->setLayout('fancy');

		$result = $mailer->deliver();
		$this->assertNotEmpty($result);
		$this->assertTextContains('**My price**: 123.45', $result['message']);
		$this->assertTextContains('<b>My price</b>: 123.45', $result['message']);
	}

	/**
	 * Html email
	 *
	 * @return void
	 */
	public function testAddEmbeddedAttachmentSend() {
		$file = Plugin::path('Tools') . 'tests' . DS . 'test_files' . DS . 'img' . DS . 'hotel.png';

		Configure::write('debug', 0);
		$this->mailer->setEmailFormat('both');
		$this->mailer->setTo(Configure::read('Config.adminEmail'));
		$cid = $this->mailer->addEmbeddedAttachment($file);
		$cid2 = $this->mailer->addEmbeddedAttachment($file);

		$this->assertStringContainsString('@' . env('HTTP_HOST'), $cid);

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
		$this->mailer->setViewVars(compact('text', 'html'));

		$res = $this->mailer->send();
		Configure::write('debug', 2);

		//$this->assertEquals('', $this->mailer->getError());
		$this->assertTrue((bool)$res);
	}

	/**
	 * @return void
	 */
	public function testValidates() {
		$res = $this->mailer->validates();
		$this->assertFalse($res);

		$this->mailer->setSubject('foo');
		$res = $this->mailer->validates();
		$this->assertFalse($res);

		$this->mailer->setTo('some@web.de');
		$res = $this->mailer->validates();
		$this->assertTrue($res);
	}

}
