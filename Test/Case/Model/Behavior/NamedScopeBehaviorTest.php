<?php

App::uses('NamedScopeBehavior', 'Tools.Model/Behavior');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class NamedScopeBehaviorTest extends MyCakeTestCase {

	public $NamedScopeBehavior;

	public $Comment;

	public $fixtures = ['core.comment', 'core.user'];

	public function setUp() {
		parent::setUp();

		$this->NamedScopeBehavior = new NamedScopeBehavior();

		$this->Comment = ClassRegistry::init('Comment');
		$this->Comment->bindModel(['belongsTo' => ['User']], false);
		$this->Comment->displayField = 'comment';
		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');
	}

	public function testObject() {
		$this->assertTrue(is_object($this->NamedScopeBehavior));
		$this->assertInstanceOf('NamedScopeBehavior', $this->NamedScopeBehavior);
	}

	/**
	 * NamedScopeBehaviorTest::testScope()
	 *
	 * @return void
	 */
	public function testScope() {
		$result = $this->Comment->scope('active');
		$this->assertNull($result);

		$this->Comment->scope('active', ['published' => 'Y']);
		$result = $this->Comment->scope('active');
		$this->assertEquals(['published' => 'Y'], $result);

		$this->Comment->scope('active', ['published' => 'Y', 'active' => 1]);
		$result = $this->Comment->scope('active');
		$this->assertEquals(['published' => 'Y', 'active' => 1], $result);

		$result = $this->Comment->scope();
		$this->assertEquals(['active' => ['published' => 'Y', 'active' => 1]], $result);
	}

	/**
	 * NamedScopeBehaviorTest::testBasic()
	 *
	 * @return void
	 */
	public function testBasic() {
		$before = $this->Comment->find('count');

		$this->Comment->scope('active', ['published' => 'Y']);
		$options = [
			'scope' => ['active']
		];
		$after = $this->Comment->find('count', $options);
		$this->assertTrue($before > $after);
		$this->assertSame(5, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testCrossModel()
	 *
	 * @return void
	 */
	public function testCrossModel() {
		$before = $this->Comment->find('count');

		$this->Comment->scope('active', ['Comment.published' => 'Y']);
		$this->Comment->User->scope('senior', ['User.id <' => '3']);

		$options = [
			'contain' => ['User'],
			'scope' => ['Comment.active', 'User.senior']
		];
		$after = $this->Comment->find('count', $options);
		$this->assertTrue($before > $after);
		$this->assertSame(4, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testCrossModelWithAttributeScope()
	 *
	 * @return void
	 */
	public function testCrossModelWithAttributeScope() {
		$this->Comment->scopes = ['active' => ['Comment.published' => 'Y']];
		$this->Comment->User->scopes = ['senior' => ['User.id <' => '2']];

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$options = [
			'contain' => ['User'],
			'scope' => ['Comment.active', 'User.senior']
		];
		$after = $this->Comment->find('count', $options);
		$this->assertSame(2, $after);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFind()
	 *
	 * @return void
	 */
	public function testScopedFind() {
		$this->Comment->scopes = ['active' => ['Comment.published' => 'Y']];
		$this->Comment->User->scopes = ['senior' => ['User.id <' => '2']];

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = [
			'activeAndSenior' => [
				'name' => 'Active and Senior',
				'find' => [
					'virtualFields' => [
						//'fullname' => "CONCAT(User.id, '-', User.user)"
					],
					'options' => [
						'scope' => ['Comment.active', 'User.senior'],
						'contain' => ['User'],
						'fields' => ['User.id', 'User.user'],
						'order' => ['User.user' => 'ASC'],
					],
				]
			]
		];
		$result = $this->Comment->scopedFind('activeAndSenior');
		$this->assertSame(2, count($result));

		$result = $this->Comment->scopedFind('activeAndSenior', ['type' => 'count']);
		$this->assertSame(2, $result);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFindWithVirtualFields()
	 *
	 * @return void
	 */
	public function testScopedFindWithVirtualFields() {
		$this->db = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->db instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Comment->scopes = ['active' => ['Comment.published' => 'Y']];
		$this->Comment->User->scopes = ['senior' => ['User.id <' => '2']];

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = [
			'activeAndSenior' => [
				'name' => 'Active and Senior',
				'find' => [
					'virtualFields' => [
						'fullname' => "CONCAT(User.id, '-', User.user)"
					],
					'options' => [
						'scope' => ['Comment.active', 'User.senior'],
						'contain' => ['User'],
						'fields' => ['User.id', 'fullname'],
						'order' => ['fullname' => 'ASC'],
					],
				]
			]
		];
		$result = $this->Comment->scopedFind('activeAndSenior');
		$this->assertSame(2, count($result));

		$scopedFinds = $this->Comment->scopedFinds();
		$this->assertSame(['activeAndSenior' => 'Active and Senior'], $scopedFinds);
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFindWithLimit()
	 *
	 * @return void
	 */
	public function testScopedFindWithLimit() {
		$this->Comment->scopes = ['active' => ['Comment.published' => 'Y']];
		$this->Comment->User->scopes = ['senior' => ['User.id <' => '2']];

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');

		$this->Comment->scopedFinds = [
			'activeAndSenior' => [
				'name' => 'Active and Senior',
				'find' => [
					'virtualFields' => [
						'fullname' => "CONCAT(User.id, '-', User.user)"
					],
					'options' => [
						'scope' => ['Comment.active', 'User.senior'],
						'contain' => ['User'],
						'fields' => ['User.id', 'fullname'],
						'order' => ['fullname' => 'ASC'],
					],
				]
			]
		];
		$result = $this->Comment->scopedFind('activeAndSenior', ['options' => ['limit' => 1]]);
		$this->assertSame(1, count($result));
	}

	/**
	 * NamedScopeBehaviorTest::testScopedFindOverwrite()
	 *
	 * @return void
	 */
	public function testScopedFindOverwrite() {
		$this->Comment->scopes = ['active' => ['Comment.published' => 'Y']];

		$this->Comment->Behaviors->load('Tools.NamedScope');
		$this->Comment->User->Behaviors->load('Tools.NamedScope');
		$this->Comment->scopedFinds = [
			'active' => [
				'name' => 'Active Comentators',
				'find' => [
					'type' => 'all',
					'virtualFields' => [
						'fullname' => "CONCAT(User.id, '-', User.user)"
					],
					'options' => [
						'scope' => ['Comment.active'],
						'contain' => ['User'],
						'fields' => ['User.id', 'fullname'],
						'order' => ['fullname' => 'ASC'],
						'limit' => 5
					]
				]
			]
		];
		$result = $this->Comment->scopedFind('active', ['options' => ['limit' => 2]]);
		$this->assertSame(2, count($result));

		$result = $this->Comment->scopedFind('active', ['type' => 'count']);
		$this->assertSame(5, $result);

		$result = $this->Comment->scopedFind('active', ['type' => 'first', 'options' => ['fields' => ['User.id', 'User.created'], 'order' => ['User.id' => 'DESC']]]);
		$expected = [
			'User' => [
				'id' => 4,
				'created' => '2007-03-17 01:22:23'
			]
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * NamedScopeBehaviorTest::testException()
	 *
	 * @expectedException RuntimeException
	 * @return void
	 */
	public function testException() {
		$this->Comment->scopedFind('foo');
	}

}
