<?php
App::uses('LogableBehavior', 'Tools.Model/Behavior');
App::uses('Log', 'Tools.Model');

class LogableBehaviorTest extends CakeTestCase {

	public $Log = null;

	public $fixtures = array('core.cake_session', 'plugin.tools.logable_log', 'plugin.tools.logable_book', 'plugin.tools.logable_user', 'plugin.tools.logable_comment', 'core.user');

	public function setUp() {
		parent::setUp();

		Configure::write('Config.language', 'eng');

		$this->LogableBook = ClassRegistry::init('LogableBook');
		$this->Log = ClassRegistry::init('LogableLog');
		//die(debug($this->Log->find('all')));
		$this->LogableUser = ClassRegistry::init('LogableUser');
		$this->LogableComment = ClassRegistry::init('LogableComment');
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->LogableBook);
		unset($this->Log);
		unset($this->LogableUser);
		unset($this->LogableComment);
	}

	public function testFindLog() {
		// no params should give all log items of current model
		$result = $this->LogableBook->findLog(array('order' => 'LogableLog.id DESC'));
		$result = Set::combine($result, '/LogableLog/id', '/LogableLog/description');

		$expected = array(
			5 => 'LogableBook "New Book" (7) added by LogableUser "Steven" (301).',
			4 => 'LogableBook "Fifth Book" (6) deleted by LogableUser "Alexander" (66).',
			2 => 'LogableBook "Fifth Book" (6) updated by LogableUser "Alexander" (66).',
			1 => 'LogableBook "Fifth Book" (6) created by LogableUser "Alexander" (66).'
		);
		$this->assertEquals($expected, $result);

		// asking for user, but not model, so should just get users changes on current model
		$expected = array(
			5 => 'LogableBook "New Book" (7) added by LogableUser "Steven" (301).'
		);
		$result = $this->LogableBook->findLog(array('user_id' => 301, 'order' => 'id DESC'));
		$result = Set::combine($result, '/LogableLog/id', '/LogableLog/description');

		$this->assertEquals($expected, $result);

		$expected = array(
			5 => 'LogableBook "New Book" (7) added by LogableUser "Steven" (301).'
		);
		$result = $this->LogableBook->findLog(array('foreign_id' => 7, 'order' => 'id DESC'));
		$result = Set::combine($result, '/LogableLog/id', '/LogableLog/description');
		$this->assertEquals($expected, $result);

		$expected = array(
			0 => array('LogableLog' => array('id' => 4)),
			1 => array('LogableLog' => array('id' => 2)),
			2 => array('LogableLog' => array('id' => 1))
		);
		$result = $this->LogableBook->findLog(array('foreign_id' => 6, 'fields' => array('id'), 'order' => 'id DESC'));
		$this->assertEquals($expected, $result);

		$expected = array(0 => array('LogableLog' => array('id' => 4)));
		$result = $this->LogableBook->findLog(array('action' => 'delete', 'fields' => array('id'), 'order' => 'id DESC'));
		$this->assertEquals($expected, $result);

		$expected = array(
			0 => array('LogableLog' => array('id' => 5)),
			1 => array('LogableLog' => array('id' => 1))
		);
		$result = $this->LogableBook->findLog(array('action' => 'add', 'fields' => array('id'), 'order' => 'id DESC'));
		$this->assertEquals($expected, $result);

		$expected = array(0 => array('LogableLog' => array('id' => 2)));
		$result = $this->LogableBook->findLog(array('action' => 'edit', 'fields' => array('id'), 'order' => 'id DESC'));
		$this->assertEquals($expected, $result);

		$expected = array(
			0 => array('LogableLog' => array('id' => 5)),
			1 => array('LogableLog' => array('id' => 1))
		);
		$result = $this->LogableBook->findLog(array(
			'action' => 'add',
			'fields' => array('id'),
			'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);

		$expected = array(
			0 => array('LogableLog' => array('id' => 5)),
			1 => array('LogableLog' => array('id' => 1))
		);
		$result = $this->LogableBook->findLog(array(
			'action' => 'add',
			'fields' => array('id'),
			'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);

		$expected = array(0 => array('LogableLog' => array('id' => 4)));
		$result = $this->LogableBook->findLog(array(
			'fields' => array('id'),
			'conditions' => array('user_id' < 300, 'action' => 'delete'),
			'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);
	}

	public function testFindLogMoreModels() {
		// all actions of user Steven
		$expected = array(
			0 => array('LogableLog' => array('id' => 5)),
			1 => array('LogableLog' => array('id' => 3))
		);
		$result = $this->LogableBook->findLog(array(
			'fields' => array('id'),
			'user_id' => 301,
			'model' => false,
			'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);

		// all delete actions of user Alexander
		$expected = array(0 => array('LogableLog' => array('id' => 4)));
		$result = $this->LogableBook->findLog(array(
			'fields' => array('id'),
			'user_id' => 66,
			'action' => 'delete',
			'model' => false,
			 'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);

		// get a differnt models logs
		$expected = array(0 => array('LogableLog' => array('id' => 3)));
		$result = $this->LogableBook->findLog(array(
			'fields' => array('id'),
			'order' => 'id ASC',
			'model' => 'LogableUser',
			'order' => 'id DESC'
		));
		$this->assertEquals($expected, $result);
	}

	public function testFindUserActions() {
		$expected = array(
			array('LogableLog' => array('id' => 5)),
			array('LogableLog' => array('id' => 3)),
		);
		$result = $this->LogableBook->findUserActions(301, array('fields' => 'id'));
		$this->assertEquals($expected, $result);

		$expected = array(
			array('LogableLog' => array('id' => 4, 'event' => 'Alexander deleted the logablebook(id 6)')),
			array('LogableLog' => array('id' => 2, 'event' => 'Alexander edited title of logablebook(id 6)')),
			array('LogableLog' => array('id' => 1, 'event' => 'Alexander added a logablebook(id 6)')),
		);
		$result = $this->LogableBook->findUserActions(66, array('events' => true));
		$this->assertEquals($expected, $result);

		$expected = array(
			0 => array('LogableLog' => array('id' => 5))
		);
		$result = $this->LogableBook->findUserActions(301, array('fields' => 'id', 'model' => 'LogableBook'));
		$this->assertEquals($expected, $result);
	}

	public function testAddingModels() {
		$this->LogableBook->save(array('LogableBook' => array('title' => 'Denver')));
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));

		$this->assertEquals('Denver', $result['LogableLog']['title']);
		$this->assertEquals('add', $result['LogableLog']['action']);
		$this->assertEquals('title', $result['LogableLog']['change']);
		$this->assertEquals(7, $result['LogableLog']['foreign_id']);
		$result = Set::combine($result, '/LogableLog/id', '/LogableLog/description');

		$expected = array(
			6 => 'LogableBook "Denver" (7) added by System.'
		);
		// check with user
		$this->assertEquals($expected, $result);

		$this->LogableBook->create();
		$this->LogableBook->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableBook->save(array('LogableBook' => array('title' => 'New Orleans')));
		$this->LogableBook->clearUserData();
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));

		$expected = array('LogableLog' => array(
				'id' => 7,
				'title' => 'New Orleans',
				'description' => 'LogableBook "New Orleans" (8) added by LogableUser "Alexander" (66).',
				'model' => 'LogableBook',
				'foreign_id' => 8,
				'action' => 'add',
				'user_id' => 66,
				'change' => 'title',
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testEditingModels() {
		$data = array('LogableBook' => array('id' => 5, 'title' => 'Forth book'));
		$this->LogableBook->save($data, false);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => 6,
				'title' => 'Forth book',
				'description' => 'LogableBook "Forth book" (5) updated by System.',
				'model' => 'LogableBook',
				'foreign_id' => 5,
				'action' => 'edit',
				'user_id' => 0,
				'change' => 'title',
			));
		$this->assertEquals($expected, $result);
	}

	public function testDeletingModels() {
		$this->LogableBook->delete(5);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => 6,
				'title' => 'Fourth Book',
				'description' => 'LogableBook "Fourth Book" (5) deleted by System.',
				'model' => 'LogableBook',
				'foreign_id' => 5,
				'action' => 'delete',
				'user_id' => 0,
				'change' => '',
			));
		$this->assertEquals($expected, $result);
	}

	public function testUserLogging() {
		$this->LogableUser->save(array('LogableUser' => array('name' => 'Jonny')));
		$result = $this->Log->get(6, array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change'));
		$expected = array(
			'LogableLog' => array(
				'id' => 6,
				'title' => 'Jonny',
				'description' => 'LogableUser "Jonny" (302) added by System.',
				'model' => 'LogableUser',
				'foreign_id' => 302,
				'action' => 'add',
				'user_id' => 0,
				'change' => 'name',
			)
		);
		// check with LogableUser
		$this->assertEquals($expected, $result);
		$this->LogableUser->delete(302);
		$result = $this->Log->get(7, array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change'));
		$expected = array(
			'LogableLog' => array(
				'id' => 7,
				'title' => 'Jonny',
				'description' => 'LogableUser "Jonny" (302) deleted by System.',
				'model' => 'LogableUser',
				'foreign_id' => 302,
				'action' => 'delete',
				'user_id' => 0,
				'change' => '',
			)
		);
		// check with LogableUser
		$this->assertEquals($expected, $result);
	}

	public function testLoggingWithoutDisplayField() {
		$this->LogableComment->save(array('LogableComment' => array('content' => 'You too?')));
		$result = $this->Log->get(6, array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change'));
		$expected = array(
			'LogableLog' => array(
				'id' => 6,
				'title' => 'LogableComment (5)',
				'description' => 'LogableComment (5) added by System.',
				'model' => 'LogableComment',
				'foreign_id' => 5,
				'action' => 'add',
				'user_id' => 0,
				'change' => 'content',
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testConfigurationsWithoutDescription() {
		$description = $this->Log->schema('description');
		$this->Log->removeSchema('description');
		$this->LogableBook->create();
		$this->LogableBook->save(array('LogableBook' => array('title' => 'Denver XYZ', 'weight' => 1)));

		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => (string)6,
				'title' => 'Denver XYZ',
				//'description' => 'LogableBook "Denver" (7) added by System.',
				'description' => '',
				'model' => 'LogableBook',
				'foreign_id' => (string)7,
				'action' => 'add',
				'user_id' => (string)0,
				'change' => 'title, weight',
			)
		);

		$this->assertEquals($expected, $result);

		$data = array('LogableBook' => array('id' => 5, 'title' => 'Forth book'));
		$this->LogableBook->save($data, false);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => 7,
				'title' => 'Forth book',
				//'description' => 'LogableBook "Forth book" (5) updated by System.',
				'description' => '',
				'model' => 'LogableBook',
				'foreign_id' => 5,
				'action' => 'edit',
				'user_id' => 0,
				'change' => 'title',
			));

		$this->assertEquals($expected, $result);

		$this->LogableBook->delete(5);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => 8,
				'title' => 'Forth book',
				//'description' => 'LogableBook "Forth book" (5) deleted by System.',
				'description' => '',
				'model' => 'LogableBook',
				'foreign_id' => 5,
				'action' => 'delete',
				'user_id' => 0,
				'change' => '',
			));
		$this->assertEquals($expected, $result);

		$this->Log->setSchema('description', $description);
	}

	public function testConfigurationsWithoutModel() {
		$logSchema = $this->Log->schema();
		$this->Log->removeSchema('description');
		$this->Log->removeSchema('model');
		$this->Log->removeSchema('foreign_id');

		$this->LogableBook->create();
		$this->LogableBook->save(array('LogableBook' => array('title' => 'Denver')));
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => (string)6,
				'title' => 'Denver',
				//'description' => 'LogableBook "Denver" (7) added by System.',
				'description' => '',
				'model' => '',
				'foreign_id' => '0',
				'action' => 'add',
				'user_id' => (string)0,
				'change' => 'title',
			)
		);
		$this->assertEquals($expected, $result);

		$this->LogableBook->delete(5);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array(
			'LogableLog' => array(
				'id' => 7,
				'title' => 'Fourth Book',
				//'description' => 'LogableBook "Forth book" (5) deleted by System.',
				'description' => '',
				'model' => '',
				'foreign_id' => '0',
				'action' => 'delete',
				'user_id' => 0,
				'change' => '',
			));

		$this->assertEquals($expected, $result);

		$this->Log->setSchema(null, $logSchema);
	}

	public function testConfiguratiosWithoutUserId() {
		$this->Log->removeSchema('user_id');

		$this->LogableBook->create();
		$this->LogableBook->save(array('LogableBook' => array('title' => 'New Orleans')));

		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)6,
				'title' => 'New Orleans',
				'description' => 'LogableBook "New Orleans" (7) added by System.',
				'model' => 'LogableBook',
				'foreign_id' => (string)7,
				'action' => 'add',
				'user_id' => '0',
				'change' => 'title',
			)
		);

		$this->assertEquals($expected, $result);

		$this->LogableBook->create();
		$this->LogableBook->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableBook->save(array('LogableBook' => array('title' => 'New York')));
		$this->LogableBook->clearUserData();
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)7,
				'title' => 'New York',
				'description' => 'LogableBook "New York" (8) added by LogableUser "Alexander" (66).',
				'model' => 'LogableBook',
				'foreign_id' => (string)8,
				'action' => 'add',
				'user_id' => (string)66,
				'change' => 'title',
			)
		);

		$this->assertEquals($expected, $result);
	}

	public function testConfiguratiosWithoutAction() {
		$this->Log->removeSchema('user_id');

		$this->LogableBook->create();
		$this->LogableBook->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableBook->save(array('LogableBook' => array('title' => 'New Orleans')));
		$this->LogableBook->clearUserData();
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)6,
				'title' => 'New Orleans',
				'description' => 'LogableBook "New Orleans" (7) added by LogableUser "Alexander" (66).',
				'model' => 'LogableBook',
				'foreign_id' => (string)7,
				'action' => 'add',
				'user_id' => (string)66,
				'change' => 'title',
			)
		);
		$this->assertEquals($expected, $result);

		$this->LogableBook->delete(5);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)7,
				'title' => 'Fourth Book',
				'description' => 'LogableBook "Fourth Book" (5) deleted by System.',
				'model' => 'LogableBook',
				'foreign_id' => (string)5,
				'action' => 'delete',
				'user_id' => '0',
				'change' => '',
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testConfiguratiosDefaults() {
		$this->Log->removeSchema('user_id');
		$this->Log->removeSchema('model');
		$this->Log->removeSchema('foreign_id');
		$this->Log->removeSchema('action');
		$this->Log->removeSchema('change');

		$this->LogableBook->create();
		$this->LogableBook->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableBook->save(array('LogableBook' => array('title' => 'New Orleans')));
		$this->LogableBook->clearUserData();
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)6,
				'title' => 'New Orleans',
				'description' => 'LogableBook "New Orleans" (7) added by LogableUser "Alexander" (66).',
				'model' => 'LogableBook',
				'foreign_id' => '7',
				'action' => 'add',
				'user_id' => '66',
				'change' => 'title'
			)
		);
		$this->assertEquals($expected, $result);

		$this->LogableBook->delete(5);
		$result = $this->Log->find('last', array('fields' => array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change')));
		$expected = array('LogableLog' => array(
				'id' => (string)7,
				'title' => 'Fourth Book',
				'description' => 'LogableBook "Fourth Book" (5) deleted by System.',
				'model' => 'LogableBook',
				'foreign_id' => '5',
				'action' => 'delete',
				'user_id' => '0',
				'change' => ''
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testConfigurationWithoutMost() {
		$this->LogableComment->Behaviors->load('Logable', array('descriptionIds' => false, 'userModel' => 'LogableUser'));
		$this->LogableComment->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableComment->save(array('LogableComment' => array('id' => 1, 'content' => 'You too?')));
		$result = $this->Log->get(6, array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change'));
		$expected = array(
			'LogableLog' => array(
				'id' => (string)6,
				'title' => 'LogableComment (1)',
				'model' => 'LogableComment',
				'foreign_id' => (string)1,
				'action' => 'edit',
				'user_id' => (string)66,
				'change' => 'content',
				'description' => 'LogableComment updated by LogableUser "Alexander".',
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testIgnoreExtraFields() {
		$this->LogableComment->setUserData(array('LogableUser' => array('id' => 66, 'name' => 'Alexander')));
		$this->LogableComment->save(array('LogableComment' => array('id' => 1, 'content' => 'You too?', 'extra_field' => 'some data')));
		$result = $this->Log->get(6, array('id', 'title', 'description', 'model', 'foreign_id', 'action', 'user_id', 'change'));
		$expected = array(
			'LogableLog' => array(
				'id' => (string)6,
				'title' => 'LogableComment (1)',
				'description' => 'LogableComment (1) updated by LogableUser "Alexander" (66).',
				'model' => 'LogableComment',
				'foreign_id' => (string)1,
				'action' => 'edit',
				'user_id' => (string)66,
				'change' => 'content',
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testIgnoreSetup() {
		$logRowsBefore = $this->Log->find('count', array('conditions' => array('model' => 'LogableUser', 'foreign_id' => 301)));
		$this->LogableUser->save(array('id' => 301, 'counter' => 3));
		$logRowsAfter = $this->Log->find('count', array('conditions' => array('model' => 'LogableUser', 'foreign_id' => 301)));
		$this->assertEquals($logRowsAfter, $logRowsBefore);

		$this->LogableUser->save(array('id' => 301, 'name' => 'Steven Segal', 'counter' => 77));

		$result = $this->Log->find('first', array(
			'order' => 'LogableLog.id DESC',
			'conditions' => array('model' => 'LogableUser', 'foreign_id' => 301)));
		$this->assertEquals($result['LogableLog']['change'], 'name');
	}

	/**
	 * LogableBehaviorTest::testCustomLog()
	 *
	 * @return void
	 */
	public function testCustomLog() {
		$record = $this->LogableBook->find('first');
		$this->assertNotEmpty($record);
		$id = $record['LogableBook']['id'];

		// manually passing the id
		$result = $this->LogableBook->customLog('foo', $id);
		$this->assertTrue((bool)$result);

		// Model->id set, but no other data
		$this->LogableBook->id = $id;
		$result = $this->LogableBook->customLog('foo');
		$this->assertTrue((bool)$result);
	}

}

class LogableLog extends Log {

	public $recursive = -1;

	public $order = array('LogableLog.created' => 'DESC');

	public $belongsTo = array(
		'LogableUser' => array(
			'className' => 'LogableUser',
			'foreignKey' => 'user_id',
			'fields' => array('id', 'name'),
		),
	);

	public function removeSchema($field) {
		if (!isset($this->_schema[$field])) {
			return;
		}
		unset($this->_schema[$field]);
	}

	public function setSchema($field, $settings) {
		if ($field === null) {
			$this->_schema = $settings;
			return;
		}
		$this->_schema[$field] = $settings;
	}

}

class LogableTestModel extends CakeTestModel {

	public $recursive = -1;

}

class LogableBook extends LogableTestModel {

	public $actsAs = array(
		'Tools.Logable' => array('userModel' => 'LogableUser', 'logModel' => 'LogableLog'),
		//'Ordered' => array('foreign_key' => FALSE)
	);

	public $order = array('LogableBook.weight' => 'ASC');

}

class LogableUser extends LogableTestModel {

	public $actsAs = array('Tools.Logable' => array('userModel' => 'LogableUser', 'logModel' => 'LogableLog', 'ignore' => array('counter')));

}

class LogableComment extends LogableTestModel {

	public $actsAs = array('Tools.Logable' => array('userModel' => 'LogableUser', 'logModel' => 'LogableLog', ));

}
