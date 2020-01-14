<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Routing\Router;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\QrCodeHelper;

/**
 * QrCodeHelper Test Case
 */
class QrCodeHelperTest extends TestCase {

	const QR_TEST_STRING = 'Some Text to Translate';
	const QR_TEST_STRING_UTF = 'Some äöü Test String with $ and @ etc';

	/**
	 * @var \Tools\View\Helper\QrCodeHelper
	 */
	protected $QrCode;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Router::reload();
		Router::connect('/:controller', ['action' => 'index']);
		Router::connect('/:controller/:action/*');

		$this->testEmail = 'foo@bar.local'; // For testing normal behavior

		$this->QrCode = new QrCodeHelper(new View(null));
	}

	/**
	 * TearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->QrCode);
	}

	/**
	 * @return void
	 */
	public function testImage() {
		$is = $this->QrCode->image('Foo Bar');

		$expected = '<img src="http://chart.apis.google.com/chart?chl=Foo%20Bar&amp;cht=qr&amp;choe=UTF-8&amp;chs=74x74&amp;chld=" alt=""/>';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testFormatText() {
		$is = $this->QrCode->formatText(['controller' => 'Foo', 'action' => 'bar'], 'url');
		$this->assertSame('/foo/bar', $is);
	}

	/**
	 * @return void
	 */
	public function testFormatCard() {
		$data = [
			'name' => 'My name',
			'nickname' => 'Nick',
			'note' => 'Note',
			'birthday' => '2015-01-03',
		];
		$is = $this->QrCode->formatCard($data);

		$expected = 'MECARD:N:My name;NICKNAME:Nick;NOTE:Note;BDAY:20151-;';
		$this->assertSame($expected, $is);
	}

	/**
	 * @return void
	 */
	public function testSetSize() {
		$is = $this->QrCode->setSize(1000);
		$this->assertFalse($is);

		$is = $this->QrCode->setSize(300);
		$this->assertTrue($is);
	}

	/**
	 * @return void
	 */
	public function testImagesModified() {
		$this->QrCode->reset();
		$this->QrCode->setLevel('H');
		$is = $this->QrCode->image(static::QR_TEST_STRING);
		$this->assertTrue(!empty($is));

		$this->QrCode->reset();
		$this->QrCode->setLevel('H', 20);
		$is = $this->QrCode->image(static::QR_TEST_STRING_UTF);
		$this->assertTrue(!empty($is));

		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('L', 1);
		$is = $this->QrCode->image(static::QR_TEST_STRING);
		$this->assertTrue(!empty($is));

		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('H', 1);
		$is = $this->QrCode->image(static::QR_TEST_STRING);
		$this->assertTrue(!empty($is));
	}

	/**
	 * @return void
	 */
	public function testSpecialImages() {
		$this->QrCode->reset();
		$this->QrCode->setSize(300);
		$this->QrCode->setLevel('H');
		//echo 'CARD'.BR;
		$string = $this->QrCode->formatCard([
			'name' => 'Maier,Susanne',
			'tel' => ['0111222123', '012224344'],
			'nickname' => 'sssnick',
			'birthday' => '1999-01-03',
			'address' => 'Bluetenweg 11, 85375, Neufahrn, Deutschland',
			'email' => 'test@test.de',
			'note' => 'someNote;someOtherNote :)',
			'url' => 'http://www.some_url.de',
		]);
		$is = $this->QrCode->image($string);
		$this->assertTrue(!empty($is));
	}

	/**
	 * @return void
	 */
	public function testBitcoin() {
		$this->QrCode->reset();
		$this->QrCode->setSize(100);
		$this->QrCode->setLevel('H');
		$string = $this->QrCode->format('bitcoin', '18pnDgDYFMAKsHTA3ZqyAi6t8q9ztaWWXt');
		$is = $this->QrCode->image($string);
		$this->assertTrue(!empty($is));
	}

}
