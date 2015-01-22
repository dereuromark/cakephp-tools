<?php
App::uses('HazardableBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class HazardableBehaviorTest extends MyCakeTestCase {

	public $fixtures = ['core.comment'];

	public $Model;

	/**
	 * HazardableBehaviorTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('Comment');
		$this->Model->Behaviors->load('Tools.Hazardable');
	}

	public function testObject() {
		$this->assertInstanceOf('HazardableBehavior', $this->Model->Behaviors->Hazardable);
	}

	/**
	 * HazardableBehaviorTest::testSaveAndFind()
	 *
	 * @return void
	 */
	public function testSaveAndFind() {
		$data = [
			'comment' => 'foo',
		];
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', ['conditions' => ['id' => $this->Model->id]]);
		$this->assertTrue((bool)$res);

		$this->assertEquals('<', $res['Comment']['published']);
		$this->assertTrue(!empty($res['Comment']['comment']));
	}

	/**
	 * HazardableBehaviorTest::testFind()
	 *
	 * @return void
	 */
	public function testReplaceFind() {
		$this->Model->Behaviors->unload('Hazardable');
		$data = [
			'comment' => 'foo',
		];
		$this->Model->create();
		$res = $this->Model->save($data);
		$this->assertTrue((bool)$res);

		$res = $this->Model->find('first', ['conditions' => ['id' => $this->Model->id]]);
		$this->assertTrue((bool)$res);

		$this->assertEquals('foo', $res['Comment']['comment']);

		$this->Model->Behaviors->load('Tools.Hazardable', ['replaceFind' => true]);
		$res = $this->Model->find('first', ['conditions' => ['id' => $this->Model->id]]);
		$this->assertTrue((bool)$res);

		$this->assertEquals('<', $res['Comment']['published']);
		$this->assertTrue(!empty($res['Comment']['comment']));
		$this->assertNotEquals('foo', $res['Comment']['comment']);
	}

}
