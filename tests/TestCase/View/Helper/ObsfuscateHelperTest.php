<?php

namespace Tools\TestCase\View\Helper;

use Cake\View\View;
use Tools\TestSuite\TestCase;
use Tools\View\Helper\ObfuscateHelper;

class ObfuscateHelperTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$this->Obfuscate = new ObfuscateHelper(new View());
	}

	public function tearDown() {
		unset($this->Table);

 		parent::tearDown();
	}

	public function testObject() {
		$this->assertInstanceOf('Tools\View\Helper\ObfuscateHelper', $this->Obfuscate);
	}


	/**
	 * ObfuscateHelperTest::testEncodeEmails()
	 *
	 * @return void
	 */
	public function testEncodeEmail() {
		$result = $this->Obfuscate->encodeEmail('foobar@somedomain.com');
		$expected = '<span>@</span>';
		$this->assertContains($expected, $result);
	}

	/**
	 * ObfuscateHelperTest::testEncodeEmailUrl()
	 *
	 * @return void
	 */
	public function testEncodeEmailUrl() {
		$result = $this->Obfuscate->encodeEmailUrl('foobar@somedomain.com');
		$expected = '<script language=javascript>';
		$this->assertContains($expected, $result);
	}

	/**
	 * ObfuscateHelperTest::testEncodeText()
	 *
	 * @return void
	 */
	public function testEncodeText() {
		$result = $this->Obfuscate->encodeText('foobar@somedomain.com');
		$expected = ';&#';
		$this->assertContains($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testHideEmail() {
		$mails = [
			'test@test.de' => 't..t@t..t.de',
			'xx@yy.de' => 'x..x@y..y.de',
			'erk-wf@ve-eeervdg.com' => 'e..f@v..g.com',
		];
		foreach ($mails as $mail => $expected) {
			$res = $this->Obfuscate->hideEmail($mail);

			//echo '\''.$mail.'\' becomes \''.$res.'\' - expected \''.$expected.'\'';
			$this->assertEquals($expected, $res);
		}
	}

	/**
	 * @return void
	 */
	public function testWordCensor() {
		$data = [
			'dfssdfsdj sdkfj sdkfj ksdfj bitch ksdfj' => 'dfssdfsdj sdkfj sdkfj ksdfj ##### ksdfj',
			'122 jsdf ficken Sjdkf sdfj sdf' => '122 jsdf ###### Sjdkf sdfj sdf',
			'122 jsdf FICKEN sjdkf sdfjs sdf' => '122 jsdf ###### sjdkf sdfjs sdf',
			'dddddddddd ARSCH ddddddddddddd' => 'dddddddddd ##### ddddddddddddd',
			//'\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//";alert(String.fromCharCode(88,83,83))//\";alert(String.fromCharCode(88,83,83))//--></SCRIPT>">\'><SCRIPT>alert(String.fromCharCode(88,83,83))</SCRIPT>' => null
		];
		foreach ($data as $value => $expected) {
			$res = $this->Obfuscate->wordCensor($value, ['Arsch', 'Ficken', 'Bitch']);

			//debug('\''.h($value).'\' becomes \''.h($res).'\'', null, false);
			$this->assertEquals($expected === null ? $value : $expected, $res);
		}
	}

}
