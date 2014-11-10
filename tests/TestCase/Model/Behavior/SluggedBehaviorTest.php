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
