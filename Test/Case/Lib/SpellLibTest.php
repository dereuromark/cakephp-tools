<?php

App::uses('SpellLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SpellLibTest extends MyCakeTestCase {

	public $SpellLib;

	public function setUp() {
		parent::setUp();

		$this->skipIf(!function_exists('enchant_broker_init'), __('Module %s not installed', 'Enchant'));

		$this->SpellLib = new SpellLib();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->SpellLib);
	}

	public function testObject() {
		$this->assertInstanceOf('SpellLib', $this->SpellLib);
	}

	public function testList() {
		$res = $this->SpellLib->listBrokers();
		//debug($res);
		$this->assertTrue(is_array($res) && count($res) > 1);
		$this->assertTrue(in_array($res[0]['name'], array('ispell', 'myspell')));

		$res = $this->SpellLib->listDictionaries();
		//debug($res);
		$this->assertTrue(is_array($res) && count($res) > 1);
		$this->assertTrue(in_array($res[0]['lang_tag'], array('de_DE', 'en_GB')));
	}

	public function testDefaults() {
		$word = 'house';
		$res = $this->SpellLib->check($word);
		$this->assertTrue($res);

		$word = 'soong';
		$res = $this->SpellLib->check($word);
		$this->assertFalse($res);
		$suggestions = $this->SpellLib->suggestions($word);
		//debug($suggestions);
		$this->assertTrue(is_array($suggestions) && count($suggestions) > 1);
		$this->assertTrue(in_array('song', $suggestions));

		$word = 'bird';
		$res = $this->SpellLib->check($word);
		$this->assertTrue($res);
	}

	public function testGerman() {
		$this->SpellLib = new SpellLib(array('lang' => 'de_DE'));

		$word = 'Wand';
		$res = $this->SpellLib->check($word);
		$this->assertTrue($res);

		$word = 'Hauz';
		$res = $this->SpellLib->check($word);
		$this->assertFalse($res);
		$suggestions = $this->SpellLib->suggestions($word);
		//debug($suggestions);
		$this->assertTrue(is_array($suggestions) && count($suggestions) > 1);
		$this->assertTrue(in_array('Haus', $suggestions));

		$word = 'Mäuse';
		$res = $this->SpellLib->check($word);
		$this->assertTrue($res);
	}

	public function testConfigureConfiguration() {
		Configure::write('Spell.lang', 'de_DE');
		$this->SpellLib = new SpellLib();

		$word = 'Mäuse';
		$res = $this->SpellLib->check($word);
		$this->assertTrue($res);

		Configure::write('Spell.lang', 'en_GB');
		$this->SpellLib = new SpellLib();

		$word = 'Mäuse';
		$res = $this->SpellLib->check($word);
		$this->assertFalse($res);
		$suggestions = $this->SpellLib->suggestions($word);
		$this->assertTrue(is_array($suggestions) && count($suggestions) > 0);
		$this->assertTrue(in_array('Mouse', $suggestions));
	}

}
