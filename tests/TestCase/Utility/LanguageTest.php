<?php

namespace Tools\Test\TestCase\Utility;

use Shim\TestSuite\TestCase;
use Tools\Utility\Language;

class LanguageTest extends TestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
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
	 * LanguageTest::testParseLanguageList()
	 *
	 * @return void
	 */
	public function testParseLanguageList() {
		$res = Language::parseLanguageList();
		$expected = [
			'1.0' => [
				'de-de',
			],
			'0.8' => [
				'de',
			],
			'0.6' => [
				'en-us',
			],
			'0.4' => [
				'en',
			],
		];
		$this->assertSame($expected, $res);

		$res = Language::parseLanguageList('FI-FI,de-DE', ['forceLowerCase' => true]);
		$expected = [
			'1.0' => [
				'fi-fi',
				'de-de',
			],
		];
		$this->assertSame($expected, $res);

		$res = Language::parseLanguageList('fi-fi,DE-DE', ['forceLowerCase' => false]);
		$expected = [
			'1.0' => [
				'fi-FI',
				'de-DE',
			],
		];
		$this->assertSame($expected, $res);

		$res = Language::parseLanguageList('fi-fi,DE-DE', false);
		$expected = [
			'1.0' => [
				'fi-FI',
				'de-DE',
			],
		];
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testParseLanguageListWithDupes() {
		$httpAcceptLanguages = 'en-US,en;q=0.1,de-AT;q=0.7,fr;q=0.5,de;q=0.3,DE-DE;q=0.3,en-US,en;q=0.8,de-AT;q=1.0,fr;q=0.5,de;q=0.3,de-DE;q=0.1,SE';

		$res = Language::parseLanguageList($httpAcceptLanguages);
		$expected = [
			'1.0' => [
				'de-at',
				'se',
			],
			'0.8' => [
				'en',
			],
			'0.7' => [
				'de-at',
			],
			'0.5' => [
				'fr',
			],
			'0.3' => [
				'de',
				'de-de',
			],
			'0.1' => [
				'en',
			],
		];
		$this->assertSame($expected, $res);
	}

	/**
	 * @return void
	 */
	public function testFindMatches() {
		$res = Language::findMatches([]);
		$this->assertSame([], $res);

		$res = Language::findMatches(['de', 'en']);
		$expected = [
			'1.0' => [
				'de-de',
			],
			'0.8' => [
				'de',
			],
			'0.6' => [
				'en-us',
			],
			'0.4' => [
				'en',
			],
		];
		$this->assertSame($expected, $res);

		$res = Language::findMatches(['DE']);
		$expected = [
			'1.0' => [
				'de-de',
			],
			'0.8' => [
				'de',
			],
		];
		$this->assertSame($expected, $res);

		$res = Language::findMatches(['cs', 'en']);
		$expected = [
			'0.6' => [
				'en-us',
			],
			'0.4' => [
				'en',
			],
		];
		$this->assertSame($expected, $res);
	}

}
