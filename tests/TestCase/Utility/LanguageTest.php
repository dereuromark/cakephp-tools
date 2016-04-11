<?php

namespace Tools\TestCase\Utility;

use Tools\TestSuite\TestCase;
use Tools\Utility\Language;

class LanguageTest extends TestCase {

	public function setUp() {
		parent::setUp();

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4';
	}

	/**
	 * LanguageTest::testAll()
	 *
	 * @return void
	 */
	public function testParseLanguageListEmpty() {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
		$res = Language::parseLanguageList();
		$this->assertSame([], $res);
	}

	/**
	 * LanguageTest::testAll()
	 *
	 * @return void
	 */
	public function testParseLanguageList() {
		$res = Language::parseLanguageList();
		$expected = [
			'1.0' => [
				'de-de'
			],
			'0.8' => [
				'de'
			],
			'0.6' => [
				'en-us'
			],
			'0.4' => [
				'en'
			]
		];
		$this->assertSame($expected, $res);
	}

	/**
	 * LanguageTest::testFindMatches()
	 *
	 * @return void
	 */
	public function testFindMatches() {
		$res = Language::findMatches([]);
		$this->assertSame([], $res);

		$res = Language::findMatches(['de', 'en']);
		$expected = [
			'1.0' => [
				'de-de'
			],
			'0.8' => [
				'de'
			],
			'0.6' => [
				'en-us'
			],
			'0.4' => [
				'en'
			]
		];
		$this->assertSame($expected, $res);

		$res = Language::findMatches(['de']);
		$expected = [
			'1.0' => [
				'de-de'
			],
			'0.8' => [
				'de'
			]
		];
		$this->assertSame($expected, $res);

		$res = Language::findMatches(['cs', 'en']);
		$expected = [
			'0.6' => [
				'en-us'
			],
			'0.4' => [
				'en'
			]
		];
		$this->assertSame($expected, $res);
	}

}
