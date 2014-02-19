<?php

App::uses('BitmaskedBehavior', 'Tools.Model/Behavior');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('MyModel', 'Tools.Model');

class BitmaskedBehaviorTest extends MyCakeTestCase {

	public $fixtures = array(
		'plugin.tools.bitmasked_comment'
	);

	public $Comment;

	public function setUp() {
		parent::setUp();

		$this->Comment = new BitmaskedComment();
		$this->Comment->Behaviors->load('Tools.Bitmasked', array('mappedField' => 'statuses'));
	}

	public function testEncodeBitmask() {
		$res = $this->Comment->encodeBitmask(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED));
		$expected = BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED;
		$this->assertSame($expected, $res);
	}

	public function testDecodeBitmask() {
		$res = $this->Comment->decodeBitmask(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED);
		$expected = array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED);
		$this->assertSame($expected, $res);
	}

	public function testFind() {
		$res = $this->Comment->find('all');
		$this->assertTrue(!empty($res) && is_array($res));

		$this->assertTrue(!empty($res[1]['BitmaskedComment']['statuses']) && is_array($res[1]['BitmaskedComment']['statuses']));

		//debug($res[count($res)-1]);
	}

	public function testSave() {
		$data = array(
			'comment' => 'test save',
			'statuses' => array(),
		);
		$this->Comment->create();
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertTrue($res);

		$is = $this->Comment->data['BitmaskedComment']['status'];
		$this->assertSame('0', $is);

		$data = array(
			'comment' => 'test save',
			'statuses' => array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED),
		);
		$this->Comment->create();
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertTrue($res);

		$is = $this->Comment->data['BitmaskedComment']['status'];
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $is);

		// save + find

		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(!empty($res));

		$res = $this->Comment->find('first', array('conditions' => array('statuses' => $data['statuses'])));
		$this->assertTrue(!empty($res));
		$expected = BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED; // 6
		$this->assertEquals($expected, $res['BitmaskedComment']['status']);
		$expected = $data['statuses'];

		$this->assertEquals($expected, $res['BitmaskedComment']['statuses']);

		// model.field syntax
		$res = $this->Comment->find('first', array('conditions' => array('BitmaskedComment.statuses' => $data['statuses'])));
		$this->assertTrue(!empty($res));

		// explitit
		$activeApprovedAndPublished = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED;
		$data = array(
			'comment' => 'another post comment',
			'status' => $activeApprovedAndPublished,
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(!empty($res));

		$res = $this->Comment->find('first', array('conditions' => array('status' => $activeApprovedAndPublished)));
		$this->assertTrue(!empty($res));
		$this->assertEquals($activeApprovedAndPublished, $res['BitmaskedComment']['status']);
		$expected = array(BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED);

		$this->assertEquals($expected, $res['BitmaskedComment']['statuses']);
	}

	/**
	 * Assert that you can manually trigger "notEmpty" rule with null instead of 0 for "not null" db fields
	 */
	public function testSaveWithDefaultValue() {
		$this->Comment->Behaviors->load('Tools.Bitmasked', array('mappedField' => 'statuses', 'defaultValue' => ''));
		$data = array(
			'comment' => 'test save',
			'statuses' => array(),
		);
		$this->Comment->create();
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		//debug($this->Comment->data);
		$this->assertFalse($res);

		$is = $this->Comment->data['BitmaskedComment']['status'];
		$this->assertSame('', $is);
	}

	public function testIs() {
		$res = $this->Comment->isBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('BitmaskedComment.status' => 2);
		$this->assertEquals($expected, $res);
	}

	public function testIsNot() {
		$res = $this->Comment->isNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('NOT' => array('BitmaskedComment.status' => 2));
		$this->assertEquals($expected, $res);
	}

	public function testContains() {
		$res = $this->Comment->containsBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('(BitmaskedComment.status & ? = ?)' => array(2, 2));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', array('conditions' => $conditions));
		$this->assertTrue(!empty($res) && count($res) === 3);

		// multiple (AND)
		$res = $this->Comment->containsBit(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE));

		$expected = array('(BitmaskedComment.status & ? = ?)' => array(3, 3));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', array('conditions' => $conditions));
		$this->assertTrue(!empty($res) && count($res) === 2);
	}

	public function testNotContains() {
		$res = $this->Comment->containsNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('(BitmaskedComment.status & ? != ?)' => array(2, 2));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', array('conditions' => $conditions));
		$this->assertTrue(!empty($res) && count($res) === 4);

		// multiple (AND)
		$res = $this->Comment->containsNotBit(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE));

		$expected = array('(BitmaskedComment.status & ? != ?)' => array(3, 3));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', array('conditions' => $conditions));
		$this->assertTrue(!empty($res) && count($res) === 5);
	}

}

class BitmaskedComment extends CakeTestModel {

	public $validate = array(
		'status' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'last' => true
			)
		)
	);

	public static function statuses($value = null) {
		$options = array(
			self::STATUS_ACTIVE => __('Active'),
			self::STATUS_PUBLISHED => __('Published'),
			self::STATUS_APPROVED => __('Approved'),
			self::STATUS_FLAGGED => __('Flagged'),
		);

		return MyModel::enum($value, $options);
	}

	const STATUS_NONE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_PUBLISHED = 2;
	const STATUS_APPROVED = 4;
	const STATUS_FLAGGED = 8;
}
