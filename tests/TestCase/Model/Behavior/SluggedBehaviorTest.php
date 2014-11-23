<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Core\Configure;

/**
 * SluggedBehaviorTest
 */
class SluggedBehaviorTest extends TestCase {

/**
 * Fixture
 *
 * @var array
 */
	public $fixtures = [
		'plugin.tools.slugged_articles'
	];

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		//$this->connection = ConnectionManager::get('test');

		$options = ['alias' => 'Articles'];
		$this->articles = TableRegistry::get('SluggedArticles', $options);

		Configure::delete('Slugged');
	}

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->articles);

 		TableRegistry::clear();
 		parent::tearDown();
	}

/**
 * Testing simple slugging when adding a record
 *
 * @return void
 */
	public function testAdd() {
		$this->articles->addBehavior('Tools.Slugged');

		$entity = $this->_getEntity();
		$result = $this->articles->save($entity);

		$this->assertEquals('test-123', $result->get('slug'));
	}

/**
 * Testing simple slugging when adding a record
 *
 * @return void
 */
	public function testAddUnique() {
		$this->articles->addBehavior('Tools.Slugged', ['unique' => true]);

		$entity = $this->_getEntity();
		$result = $this->articles->save($entity);
		$this->assertEquals('test-123', $result->get('slug'));

		//$entity = $this->_getEntity();
		//$result = $this->articles->save($entity);
		//$this->assertEquals('test-123', $result->get('slug'));
		//debug($result);
	}

/**
 * SluggedBehaviorTest::testCustomFinder()
 *
 * @return void
 */
	public function testCustomFinder() {
		$this->articles->addBehavior('Tools.Slugged');
		$article = $this->articles->find()->find('slugged', ['slug' => 'foo'])->first();
		$this->assertEquals('Foo', $article->get('title'));
	}

/**
 * Length based on manual config.
 *
 * @return void
 */
	public function testLengthRestrictionManual() {
		$this->articles->addBehavior('Tools.Slugged', ['length' => 155]);
		$entity = $this->_getEntity(str_repeat('foo bar ', 31));

		$result = $this->articles->save($entity);
		$this->assertEquals(155, strlen($result->get('slug')));

		// For non ascii chars it might be longer, though...
		$this->articles->behaviors()->Slugged->config(['length' => 10, 'mode' => 'ascii']);
		$entity = $this->_getEntity('ä ö ü ä ö ü');
		$result = $this->articles->save($entity);
		$this->assertEquals('ae-oe-ue-ae-oe-ue', $result->get('slug'));
	}

/**
 * Length based on auto-detect of schema.
 *
 * @return void
 */
	public function testLengthRestrictionAutoDetect() {
		$this->articles->addBehavior('Tools.Slugged');
		$entity = $this->_getEntity(str_repeat('foo bar ', 31));

		$result = $this->articles->save($entity);
		$this->assertEquals(245, strlen($result->get('slug')));
	}

/**
 * Ensure that you can overwrite length.
 *
 * @return void
 */
	public function testLengthRestrictionNoLimit() {
		$this->articles->addBehavior('Tools.Slugged', ['length' => 0, 'label' => 'long_title', 'field' => 'long_slug']);
		$entity = $this->_getEntity(str_repeat('foo bar ', 100), 'long_title');

		$result = $this->articles->save($entity);
		$this->assertEquals(799, strlen($result->get('long_slug')));
	}

	/**
	 * SluggedBehaviorTest::testResetSlugs()
	 *
	 * @return void
	 */
	public function testResetSlugs() {
		$article = $this->articles->newEntity(array('title' => 'Andy Dawson', 'slug' => 'foo'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawsom', 'slug' => 'bar'));
		$this->articles->save($article);

		$result = $this->articles->find('all', array(
			'conditions' => array('title LIKE' => 'Andy Daw%'),
			'fields' => array('title', 'slug'),
			'order' => 'title'
		))->combine('title', 'slug')->toArray();
		$expected = array(
			'Andy Dawsom' => 'bar',
			'Andy Dawson' => 'foo'
		);
		$this->assertEquals($expected, $result);

		$this->articles->addBehavior('Tools.Slugged');
		$result = $this->articles->resetSlugs(['limit' => 1]);
		$this->assertTrue($result);

		$result = $this->articles->find('all', array(
			'conditions' => array('title LIKE' => 'Andy Daw%'),
			'fields' => array('title', 'slug'),
			'order' => 'title'
		))->combine('title', 'slug')->toArray();
		$expected = array(
			'Andy Dawsom' => 'Andy-Dawsom',
			'Andy Dawson' => 'Andy-Dawson'
		);
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestDuplicateWithLengthRestriction method
	 *
	 * If there's a length restriction - ensure it's respected by the unique slug routine
	 *
	 * @return void
	 */
	public function testDuplicateWithLengthRestriction() {
		return;

		$this->articles->addBehavior('Tools.Slugged', ['length' => 10, 'unique' => true]);

		$article = $this->articles->newEntity(array('title' => 'Andy Dawson'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawsom'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawsoo'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso3'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso4'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso5'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso6'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso7'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso8'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso9'));
		$this->articles->save($article);
		$article = $this->articles->newEntity(array('title' => 'Andy Dawso0'));
		$this->articles->save($article);

		$result = $this->articles->find('all', array(
			'conditions' => array('title LIKE' => 'Andy Daw%'),
			'fields' => array('title', 'slug'),
			'order' => 'title'
		))->combine('title', 'slug')->toArray();
		$expects = array(
			'Andy Dawson' => 'Andy-Dawso',
			'Andy Dawsom' => 'Andy-Daw-1',
			'Andy Dawsoo' => 'Andy-Daw-2',
			'Andy Dawso3' => 'Andy-Daw-3',
			'Andy Dawso4' => 'Andy-Daw-4',
			'Andy Dawso5' => 'Andy-Daw-5',
			'Andy Dawso6' => 'Andy-Daw-6',
			'Andy Dawso7' => 'Andy-Daw-7',
			'Andy Dawso8' => 'Andy-Daw-8',
			'Andy Dawso9' => 'Andy-Daw-9',
			'Andy Dawso0' => 'Andy-Da-10'
		);
		$this->assertEquals($expects, $result);
	}

/**
 * Get a new Entity
 *
 * @return Entity
 */
	protected function _getEntity($title = 'test 123', $field = 'title') {
		return new Entity([
			$field => $title
		]);
	}

}
