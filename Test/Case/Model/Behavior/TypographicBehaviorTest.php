<?php

App::import('Behavior', 'Tools.Typographic');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.Lib');


class TypographicBehaviorTest extends MyCakeTestCase {

	public function startTest() {
		//$this->Comment = ClassRegistry::init('Comment');
		$this->Comment = new TypographicTestModel();
		$this->Comment->Behaviors->attach('Tools.Typographic', array('fields'=>array('body'), 'before'=>'validate'));
	}

	public function setUp() {

	}

	public function tearDown() {

	}

	public function testObject() {
		$this->assertTrue(is_a($this->Comment->Behaviors->Typographic, 'TypographicBehavior'));
	}


	public function testBeforeValidate() {
		$this->out($this->_header(__FUNCTION__), false);
		// accuracy >= 5
		$data = array(
			'name' => 'some Name',
			'body' => 'A title with normal "qotes" - should be left untouched',
		);
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertTrue($res);

		$res = $this->Comment->data;
		$this->assertSame($data['body'], $res['TypographicTestModel']['body']);

		$strings = array(
			'some string with ‹single angle quotes›' => 'some string with "single angle quotes"',
			'other string with „German‟ quotes' => 'other string with "German" quotes',
			'mixed single ‚one‛ and ‘two’.' => 'mixed single "one" and "two".',
			'mixed double “one” and «two».' => 'mixed double "one" and "two".',
		);
		foreach ($strings as $was => $expected) {
			$data = array(
				'body' => $was
			);
			$this->Comment->set($data);
			$res = $this->Comment->validates();
			$this->assertTrue($res);

			$res = $this->Comment->data;
			//debug($expected);
			//debug($res['TestModel']['body']);
			//die();
			$this->assertSame($expected, $res['TypographicTestModel']['body']);
		}

	}


}

/** other files **/

class TypographicTestModel extends AppModel {

	public $useTable = false;
	public $displayField = 'name';


}
