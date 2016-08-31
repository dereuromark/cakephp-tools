<?php

App::uses('BitmaskedBehavior', 'Tools.Model/Behavior');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('MyModel', 'Tools.Model');

class BitmaskedBehaviorTest extends MyCakeTestCase {

	public $fixtures = [
		'plugin.tools.bitmasked_comment'
	];

	public $Comment;

	public function setUp() {
		parent::setUp();

		App::build([
			'Model' => [CakePlugin::path('Tools') . 'Test' . DS . 'test_app' . DS . 'Model' . DS],
		], App::RESET);

		$this->Comment = ClassRegistry::init('BitmaskedComment');
		$this->Comment->Behaviors->load('Tools.Bitmasked', ['mappedField' => 'statuses']);
	}

	public function testEncodeBitmask() {
		$res = $this->Comment->encodeBitmask([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED]);
		$expected = BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED;
		$this->assertSame($expected, $res);
	}

	public function testDecodeBitmask() {
		$res = $this->Comment->decodeBitmask(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED);
		$expected = [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED];
		$this->assertSame($expected, $res);
	}

	public function testFind() {
		$res = $this->Comment->find('all');
		$this->assertTrue(!empty($res) && is_array($res));

		$this->assertTrue(!empty($res[1]['BitmaskedComment']['statuses']) && is_array($res[1]['BitmaskedComment']['statuses']));

		//debug($res[count($res)-1]);
	}

	public function testSave() {
		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];
		$this->Comment->create();
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertTrue($res);

		$is = $this->Comment->data['BitmaskedComment']['status'];
		$this->assertSame('0', $is);

		$data = [
			'comment' => 'test save',
			'statuses' => [BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED],
		];
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

		$res = $this->Comment->find('first', ['conditions' => ['statuses' => $data['statuses']]]);
		$this->assertTrue(!empty($res));
		$expected = BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED; // 6
		$this->assertEquals($expected, $res['BitmaskedComment']['status']);
		$expected = $data['statuses'];

		$this->assertEquals($expected, $res['BitmaskedComment']['statuses']);

		// model.field syntax
		$res = $this->Comment->find('first', ['conditions' => ['BitmaskedComment.statuses' => $data['statuses']]]);
		$this->assertTrue(!empty($res));

		// explitit
		$activeApprovedAndPublished = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED;
		$data = [
			'comment' => 'another post comment',
			'status' => $activeApprovedAndPublished,
		];
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(!empty($res));

		$res = $this->Comment->find('first', ['conditions' => ['status' => $activeApprovedAndPublished]]);
		$this->assertTrue(!empty($res));
		$this->assertEquals($activeApprovedAndPublished, $res['BitmaskedComment']['status']);
		$expected = [BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED];

		$this->assertEquals($expected, $res['BitmaskedComment']['statuses']);
	}

	/**
	 * Assert that you can manually trigger "notEmpty" rule with null instead of 0 for "not null" db fields
	 */
	public function testSaveWithDefaultValue() {
		$this->Comment->Behaviors->unload('Bitmasked');
		$this->Comment->Behaviors->load('Tools.Bitmasked', ['mappedField' => 'statuses', 'defaultValue' => '']);
		$data = [
			'comment' => 'test save',
			'statuses' => [],
		];
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
		$expected = ['BitmaskedComment.status' => 2];
		$this->assertEquals($expected, $res);
	}

	public function testIsNot() {
		$res = $this->Comment->isNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['NOT' => ['BitmaskedComment.status' => 2]];
		$this->assertEquals($expected, $res);
	}

	public function testContains() {
		$res = $this->Comment->containsBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComment.status & ? = ?)' => [2, 2]];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', ['conditions' => $conditions]);
		$this->assertTrue(!empty($res) && count($res) === 3);

		// multiple (AND)
		$res = $this->Comment->containsBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);

		$expected = ['(BitmaskedComment.status & ? = ?)' => [3, 3]];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', ['conditions' => $conditions]);
		$this->assertTrue(!empty($res) && count($res) === 2);
	}

	public function testNotContains() {
		$res = $this->Comment->containsNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = ['(BitmaskedComment.status & ? != ?)' => [2, 2]];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', ['conditions' => $conditions]);
		$this->assertTrue(!empty($res) && count($res) === 4);

		// multiple (AND)
		$res = $this->Comment->containsNotBit([BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE]);

		$expected = ['(BitmaskedComment.status & ? != ?)' => [3, 3]];
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comment->find('all', ['conditions' => $conditions]);
		$this->assertTrue(!empty($res) && count($res) === 5);
	}

	public function testSaveMultiFields() {
		$this->Comment->Behaviors->unload('Bitmasked');
		$this->Comment->Behaviors->load('Tools.Bitmasked', [
			['mappedField' => 'types', 'field' => 'type'],
			['mappedField' => 'statuses', 'field' => 'status'],
		]);
		$data = [
			'comment' => 'test save',
			'types' => [
				BitmaskedComment::TYPE_COMPLAINT,
				BitmaskedComment::TYPE_RFC,
			],
			'statuses' => [
				BitmaskedComment::STATUS_ACTIVE,
				BitmaskedComment::STATUS_APPROVED,
			],
		];
		$this->Comment->create();
		$result = $this->Comment->save($data);
		$expectedStatus = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED;
		$this->assertEquals($expectedStatus, $result['BitmaskedComment']['status']);
		$expectedType = BitmaskedComment::TYPE_COMPLAINT | BitmaskedComment::TYPE_RFC;
		$this->assertEquals($expectedType, $result['BitmaskedComment']['type']);
	}
}
