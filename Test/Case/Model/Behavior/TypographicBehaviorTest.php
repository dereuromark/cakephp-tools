<?php

App::import('Behavior', 'Tools.Typographic');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class TypographicBehaviorTest extends MyCakeTestCase {

	public $Model;

	public $fixtures = array('core.article');

	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('Article');
		$this->Model->Behaviors->load('Tools.Typographic', array('fields' => array('body'), 'before' => 'validate'));
	}

	public function testObject() {
		$this->assertInstanceOf('TypographicBehavior', $this->Model->Behaviors->Typographic);
	}

	public function testBeforeValidate() {
		$this->out($this->_header(__FUNCTION__), false);
		$data = array(
			'title' => 'some «cool» title',
			'body' => 'A title with normal "qotes" - should be left untouched',
		);
		$this->Model->set($data);
		$res = $this->Model->validates();
		$this->assertTrue($res);

		$res = $this->Model->data;
		$this->assertSame($data, $res['Article']);

		$strings = array(
			'some string with ‹single angle quotes›' => 'some string with \'single angle quotes\'',
			'other string with „German‟ quotes' => 'other string with "German" quotes',
			'mixed single ‚one‛ and ‘two’.' => 'mixed single \'one\' and \'two\'.',
			'mixed double “one” and «two».' => 'mixed double "one" and "two".',
		);
		foreach ($strings as $was => $expected) {
			$data = array(
				'title' => 'some «cool» title',
				'body' => $was
			);
			$this->Model->set($data);
			$res = $this->Model->validates();
			$this->assertTrue($res);

			$res = $this->Model->data;
			$this->assertSame($data['title'], $res['Article']['title']);
			$this->assertSame($expected, $res['Article']['body']);
		}
	}

	public function testMergeQuotes() {
		$this->Model->Behaviors->unload('Typographic');
		$this->Model->Behaviors->load('Tools.Typographic', array('before' => 'validate', 'mergeQuotes' => true));
		$strings = array(
			'some string with ‹single angle quotes›' => 'some string with "single angle quotes"',
			'other string with „German‟ quotes' => 'other string with "German" quotes',
			'mixed single ‚one‛ and ‘two’.' => 'mixed single "one" and "two".',
			'mixed double “one” and «two».' => 'mixed double "one" and "two".',
		);
		foreach ($strings as $was => $expected) {
			$data = array(
				'title' => 'some «cool» title',
				'body' => $was
			);
			$this->Model->set($data);
			$res = $this->Model->validates();
			$this->assertTrue($res);

			$res = $this->Model->data;
			$this->assertSame('some "cool" title', $res['Article']['title']);
			$this->assertSame($expected, $res['Article']['body']);
		}
	}

	/**
	 * Test that not defining fields results in all textarea and text fields being processed
	 */
	public function testAutoFields() {
		$this->Model->Behaviors->unload('Typographic');
		$this->Model->Behaviors->load('Tools.Typographic');
		$data = array(
			'title' => '„German‟ quotes',
			'body' => 'mixed double “one” and «two»',
		);

		$this->Model->set($data);
		$res = $this->Model->save();
		$this->assertTrue((bool)$res);

		$expected = array(
			'title' => '"German" quotes',
			'body' => 'mixed double "one" and "two"',
		);

		$this->assertSame($expected['title'], $res['Article']['title']);
		$this->assertSame($expected['body'], $res['Article']['body']);
	}

	public function testUpdateTypography() {
		$this->Model->Behaviors->unload('Typographic');
		for ($i = 0; $i < 202; $i++) {
			$data = array(
				'title' => 'title ' . $i,
				'body' => 'unclean `content` to «correct»',
			);
			$this->Model->create();
			$this->Model->save($data);
		}
		$this->Model->Behaviors->load('Tools.Typographic');
		$count = $this->Model->updateTypography();
		$this->assertTrue($count >= 200);

		$record = $this->Model->find('first', array('order' => array('id' => 'DESC')));
		$this->assertSame('unclean `content` to "correct"', $record['Article']['body']);
	}

}
