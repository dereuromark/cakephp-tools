<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Entity;
use RuntimeException;
use Shim\TestSuite\TestCase;
use TestApp\Model\Entity\SluggedArticle;
use Tools\Utility\Text;

/**
 * SluggedBehaviorTest
 */
class SluggedBehaviorTest extends TestCase {

	/**
	 * Fixture
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.SluggedArticles',
	];

	/**
	 * @var \Cake\ORM\Table
	 */
	protected $articles;

	/**
	 * setup
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		//$this->connection = ConnectionManager::get('test');

		$options = ['alias' => 'Articles'];
		$this->articles = $this->getTableLocator()->get('SluggedArticles', $options);
		Configure::delete('Slugged');

		$this->articles->addBehavior('Tools.Slugged');
	}

	/**
	 * teardown
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->articles);

		$this->getTableLocator()->clear();
		parent::tearDown();
	}

	/**
	 * Testing simple slugging when adding a record
	 *
	 * @return void
	 */
	public function testAdd() {
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
		$this->articles->behaviors()->Slugged->setConfig(['unique' => true]);

		$entity = $this->_getEntity();
		$result = $this->articles->save($entity);
		$this->assertSame('test-123', $result->get('slug'));

		$entity = $this->_getEntity();
		$result = $this->articles->save($entity);
		$this->assertSame('test-123-1', $result->get('slug'));
	}

	/**
	 * @return void
	 */
	public function testAddUniqueMultipleLabels() {
		/** @var \Tools\Model\Behavior\SluggedBehavior $sluggedBehavior */
		$sluggedBehavior = $this->articles->behaviors()->Slugged;
		//$this->articles->behaviors()->Slugged->setConfig('label', ''); // Hack necessary right now to avoid title showing up twice
		$sluggedBehavior->configShallow(['mode' => 'ascii', 'unique' => true, 'label' => ['title', 'long_title']]);

		$entity = $this->_getEntity(null, null, ['long_title' => 'blae']);
		$result = $this->articles->save($entity);
		$this->assertEquals('test-123-blae', $result->get('slug'));

		$entity = $this->_getEntity(null, null, ['long_title' => 'blä']);
		$result = $this->articles->save($entity);
		$this->assertEquals('test-123-blae-1', $result->get('slug'));
	}

	/**
	 * @return void
	 */
	public function testCustomFinder() {
		$article = $this->articles->find()->find('slugged', slug: 'foo')->first();
		$this->assertEquals('Foo', $article->get('title'));
	}

	/**
	 * @return void
	 */
	public function testCustomFinderLegacy() {
		$article = $this->articles->find()->find('slugged', ...['slug' => 'foo'])->first();
		$this->assertEquals('Foo', $article->get('title'));
	}

	/**
	 * Tests that manual slugging works.
	 *
	 * @return void
	 */
	public function testSlugManualSave() {
		$article = $this->articles->newEntity(['title' => 'Some Cool String']);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Cool-String', $result['slug']);

		$article = $this->articles->newEntity(['title' => 'Some Other String']);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Other-String', $result['slug']);

		$this->articles->patchEntity($article, ['title' => 'Some Cool Other String', 'slug' => 'foo-bar']);
		$result = $this->articles->save($article);
		$this->assertEquals('foo-bar', $result['slug']);

		$this->articles->patchEntity($article, ['title' => 'Some Cool Other String', 'slug' => 'foo-bar-bat']);
		$result = $this->articles->save($article);
		$this->assertEquals('foo-bar-bat', $result['slug']);

		$this->articles->patchEntity($article, ['title' => 'Some Cool Other String', 'slug' => '']);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Cool-Other-String', $result['slug']);
	}

	/**
	 * Length based on manual config.
	 *
	 * @return void
	 */
	public function testLengthRestrictionManual() {
		$this->articles->behaviors()->Slugged->setConfig(['length' => 155]);
		$entity = $this->_getEntity(str_repeat('foo bar', 31));

		$result = $this->articles->save($entity);
		$this->assertEquals(155, strlen($result->get('slug')));

		$this->articles->behaviors()->Slugged->setConfig(['length' => 10, 'mode' => 'ascii']);
		$entity = $this->_getEntity('ä ö ü ä ö ü');
		$result = $this->articles->save($entity);
		$this->assertEquals('ae-oe-ue-a', $result->get('slug'));
	}

	/**
	 * Test that fields doesnt mess with slug storing.
	 *
	 * @return void
	 */
	public function testFields() {
		// field list is only relevant for newEntity(), not for what the behavior does
		$entity = $this->articles->newEntity(['title' => 'Some title'], ['fields' => ['title']]);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));
	}

	/**
	 * @return void
	 */
	public function testNeedsSlugUpdate() {
		// No title change
		$entity = $this->articles->newEntity(['title' => 'Some title'], ['fields' => []]);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertFalse($result);

		// Title change
		$entity = $this->articles->newEntity(['title' => 'Some title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertTrue($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));

		// No title change
		$entity = $this->articles->patchEntity($entity, ['description' => 'Foo bar']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertFalse($result);

		// Needs an update, but overwrite is still false: will not modify the slug
		$entity = $this->articles->patchEntity($entity, ['title' => 'Some other title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertTrue($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));

		$this->articles->behaviors()->Slugged->setConfig(['overwrite' => true]);
		// Now it can modify the slug
		$entity = $this->articles->patchEntity($entity, ['title' => 'Some really other title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertTrue($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-really-other-title', $result->get('slug'));

		$this->articles->behaviors()->Slugged->setConfig(['overwrite' => true]);
		// Without title present it should not modify the slug
		$entity = $this->articles->patchEntity($entity, ['foo' => 'bar']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertFalse($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-really-other-title', $result->get('slug'));
	}

	/**
	 * @return void
	 */
	public function testNeedsNoSlugUpdate() {
		$entity = $this->articles->newEntity(['title' => 'Some title']);
		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));

		$entity = $this->articles->get($entity->id, ...['fields' => ['id', 'slug']]);

		$this->articles->behaviors()->Slugged->setConfig(['overwrite' => true]);
		// Without title present it should not modify the slug
		$entity = $this->articles->patchEntity($entity, ['foo' => 'bar']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertFalse($result);

		$this->articles->saveOrFail($entity);
		$entity = $this->articles->get($entity->id);
		$this->assertEquals('Some-title', $entity->get('slug'));
	}

	/**
	 * Tests needSlugUpdate() with deep
	 *
	 * @return void
	 */
	public function testNeedsSlugUpdateDeep() {
		// No title change
		$entity = $this->articles->newEntity(['title' => 'Some title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertTrue($result);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity, true);
		$this->assertTrue($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));

		// Needs an update, but overwrite is still false: will not modify the slug
		$entity = $this->articles->patchEntity($entity, ['title' => 'Some other title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertTrue($result);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity, true);
		$this->assertTrue($result);

		$result = $this->articles->save($entity);
		$this->assertEquals('Some-title', $result->get('slug'));

		// Here deep would tell the truth
		$entity = $this->articles->patchEntity($entity, ['title' => 'Some other title']);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity);
		$this->assertFalse($result);
		$result = $this->articles->getBehavior('Slugged')->needsSlugUpdate($entity, true);
		$this->assertTrue($result);
	}

	/**
	 * Length based on auto-detect of schema.
	 *
	 * @return void
	 */
	public function testLengthRestrictionAutoDetect() {
		$entity = $this->_getEntity(str_repeat('foo bar', 36));

		$result = $this->articles->save($entity);
		$this->assertEquals(252, strlen($result->get('slug')));
	}

	/**
	 * Ensure that you can overwrite length.
	 *
	 * @return void
	 */
	public function testLengthRestrictionNoLimit() {
		$this->articles->behaviors()->Slugged->setConfig(['length' => 0, 'label' => 'long_title', 'field' => 'long_slug']);
		$entity = $this->_getEntity(str_repeat('foo bar', 35), 'long_title');

		$result = $this->articles->save($entity);
		$this->assertEquals(245, strlen($result->get('long_slug')));
	}

	/**
	 * @return void
	 */
	public function testResetSlugs() {
		$this->articles->removeBehavior('Slugged');

		$article = $this->articles->newEntity(['title' => 'Andy Dawson', 'slug' => 'foo']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawsom', 'slug' => 'bar']);
		$this->articles->save($article);

		$result = $this->articles->find('all', ...[
			'conditions' => ['title LIKE' => 'Andy Daw%'],
			'fields' => ['title', 'slug'],
			'order' => 'title',
		])->all()->combine('title', 'slug')->toArray();
		$expected = [
			'Andy Dawsom' => 'bar',
			'Andy Dawson' => 'foo',
		];
		$this->assertEquals($expected, $result);

		$this->articles->addBehavior('Tools.Slugged');
		$result = $this->articles->getBehavior('Slugged')->resetSlugs(['limit' => 1]);
		$this->assertTrue($result);

		$result = $this->articles->find('all', ...[
			'conditions' => ['title LIKE' => 'Andy Daw%'],
			'fields' => ['title', 'slug'],
			'order' => 'title',
		])->all()->combine('title', 'slug')->toArray();
		$expected = [
			'Andy Dawsom' => 'Andy-Dawsom',
			'Andy Dawson' => 'Andy-Dawson',
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * If there's a length restriction - ensure it's respected by the unique slug routine
	 *
	 * @return void
	 */
	public function testDuplicateWithLengthRestriction() {
		$this->skipIf(true);

		$this->articles->behaviors()->Slugged->setConfig(['length' => 10, 'unique' => true]);

		$article = $this->articles->newEntity(['title' => 'Andy Dawson']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawsom']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawsoo']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso3']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso4']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso5']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso6']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso7']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso8']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso9']);
		$this->articles->save($article);
		$article = $this->articles->newEntity(['title' => 'Andy Dawso0']);
		$this->articles->save($article);

		$result = $this->articles->find('all', ...[
			'conditions' => ['title LIKE' => 'Andy Daw%'],
			'fields' => ['title', 'slug'],
			'order' => 'title',
		])->all()->combine('title', 'slug')->toArray();
		$expected = [
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
			'Andy Dawso0' => 'Andy-Da-10',
		];
		$this->assertEquals($expected, $result);
	}

	/**
	 * TestTruncateMultibyte method
	 *
	 * Ensure that the first test doesn't cut a multibyte character The test string is:
	 *     17 chars
	 *     51 bytes UTF-8 encoded
	 *
	 * @return void
	 */
	public function testTruncateMultibyte() {
		$this->articles->behaviors()->Slugged->setConfig(['length' => 16]);

		$result = $this->articles->getBehavior('Slugged')->generateSlug('モデルのデータベースとデータソース');
		$expected = 'モデルのデータベースとデータソー';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testSlugManually() {
		$article = new Entity();
		$article->title = 'Foo Bar';
		$this->articles->getBehavior('Slugged')->slug($article);

		$this->assertSame('Foo-Bar', $article->slug);
	}

	/**
	 * Test Url method
	 *
	 * @return void
	 */
	public function testUrlMode() {
		$this->articles->behaviors()->Slugged->setConfig(['mode' => 'url', 'replace' => false]);

		$string = 'standard string';
		$expected = 'standard-string';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a \' in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a " in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a / in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a ? in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a < in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a > in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a . in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a $ in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a / in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a : in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a ; in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a ? in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a @ in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a = in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a + in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a & in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a % in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a \ in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a # in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);

		$string = 'something with a , in it';
		$expected = 'something-with-a-in-it';
		$result = $this->articles->getBehavior('Slugged')->generateSlug($string);
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test slug with ascii
	 *
	 * @return void
	 */
	public function testSlugGenerationModeAscii() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'mode' => 'ascii',
		]);

		$article = $this->articles->newEntity(['title' => 'Some Article 25271']);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);

		$this->assertEquals('Some-Article-25271', $result['slug']);
	}

	/**
	 * Test slug generation/update on beforeSave
	 *
	 * @return void
	 */
	public function testSlugGenerationBeforeSave() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'on' => 'beforeSave',
			'overwrite' => true,
		]);

		$article = $this->articles->newEntity(['title' => 'Some Article 25271']);
		$result = $this->articles->save($article);

		//$result['id'] = $result['id'];
		$this->assertEquals('Some-Article-25271', $result['slug']);
	}

	/**
	 * Test slug generation with i18n replacement pieces
	 *
	 * @return void
	 */
	public function testSlugGenerationI18nReplacementPieces() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'overwrite' => true,
		]);

		$article = $this->articles->newEntity(['title' => 'Some & More']);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-' . __d('tools', 'and') . '-More', $result['slug']);
	}

	/**
	 * Test dynamic slug overwrite
	 *
	 * @return void
	 */
	public function testSlugDynamicOverwrite() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'overwrite' => false,
			'overwriteField' => 'overwrite_my_slug',
		]);

		$article = $this->articles->newEntity(['title' => 'Some Cool String', 'overwrite_my_slug' => false]);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Cool-String', $result['slug']);

		$this->articles->patchEntity($article, ['title' => 'Some Cool Other String', 'overwrite_my_slug' => false]);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Cool-String', $result['slug']);

		$this->articles->patchEntity($article, ['title' => 'Some Cool Other String', 'overwrite_my_slug' => true]);
		$result = $this->articles->save($article);
		$this->assertEquals('Some-Cool-Other-String', $result['slug']);
	}

	/**
	 * Test slug generation/update based on scope
	 *
	 * @return void
	 */
	public function testSlugGenerationWithScope() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', ['unique' => true]);

		$data = ['title' => 'Some Article 12345', 'section' => 0];

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345', $result['slug']);

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345-1', $result['slug']);

		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', ['unique' => true, 'scope' => ['section' => 1]]);

		$data = ['title' => 'Some Article 12345', 'section' => 1];

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345', $result['slug']);
	}

	/**
	 * Test unique slug generation with custom callback.
	 *
	 * @return void
	 */
	public function testUniqueWithCallback() {
		$callbackInvocations = 0;

		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'unique' => true,
			'uniqueCallback' => function ($table, $conditions) use (&$callbackInvocations) {
				$callbackInvocations++;

				return $table->exists($conditions);
			},
		]);

		$article = $this->articles->newEntity(['title' => 'Callback Test']);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Callback-Test', $result['slug']);
		$this->assertSame(1, $callbackInvocations);

		// Second article with same title should trigger multiple callback invocations
		$article2 = $this->articles->newEntity(['title' => 'Callback Test']);
		$result2 = $this->articles->save($article2);
		$this->assertTrue((bool)$result2);
		$this->assertEquals('Callback-Test-1', $result2['slug']);
		$this->assertSame(3, $callbackInvocations); // 1 initial + 2 more (first check + suffix check)
	}

	/**
	 * Test unique callback that always returns false (no duplicates).
	 *
	 * @return void
	 */
	public function testUniqueWithCallbackAlwaysFalse() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'unique' => true,
			'uniqueCallback' => function ($table, $conditions) {
				// Always return false = no duplicates found
				return false;
			},
		]);

		$article = $this->articles->newEntity(['title' => 'Always Unique']);
		$result = $this->articles->save($article);
		$this->assertEquals('Always-Unique', $result['slug']);

		// Even with same title, callback says no duplicate exists
		$article2 = $this->articles->newEntity(['title' => 'Always Unique']);
		$result2 = $this->articles->save($article2);
		$this->assertEquals('Always-Unique', $result2['slug']); // No suffix added
	}

	/**
	 * Test dynamic scope with Closure.
	 *
	 * @return void
	 */
	public function testDynamicScope() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'unique' => true,
			'scope' => function ($entity) {
				return ['section' => $entity->get('section')];
			},
		]);

		// Create article in section 1
		$article1 = $this->articles->newEntity(['title' => 'Dynamic Scope Test', 'section' => 1]);
		$result1 = $this->articles->save($article1);
		$this->assertEquals('Dynamic-Scope-Test', $result1['slug']);

		// Same title in section 1 should get suffix
		$article2 = $this->articles->newEntity(['title' => 'Dynamic Scope Test', 'section' => 1]);
		$result2 = $this->articles->save($article2);
		$this->assertEquals('Dynamic-Scope-Test-1', $result2['slug']);

		// Same title in section 2 should NOT get suffix (different scope)
		$article3 = $this->articles->newEntity(['title' => 'Dynamic Scope Test', 'section' => 2]);
		$result3 = $this->articles->save($article3);
		$this->assertEquals('Dynamic-Scope-Test', $result3['slug']);
	}

	/**
	 * Test onDirty option - only regenerate slug when label is dirty.
	 *
	 * @return void
	 */
	public function testOnDirty() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'onDirty' => true,
		]);

		// Create initial article
		$article = $this->articles->newEntity(['title' => 'OnDirty Test']);
		$result = $this->articles->save($article);
		$this->assertEquals('OnDirty-Test', $result['slug']);

		// Update non-label field - slug should NOT change
		$article = $this->articles->patchEntity($article, ['description' => 'Some description']);
		$result = $this->articles->save($article);
		$this->assertEquals('OnDirty-Test', $result['slug']);

		// Update label field - slug SHOULD change
		$article = $this->articles->patchEntity($article, ['title' => 'OnDirty Changed']);
		$result = $this->articles->save($article);
		$this->assertEquals('OnDirty-Changed', $result['slug']);
	}

	/**
	 * Test onDirty with overwrite false - should still respect onDirty.
	 *
	 * @return void
	 */
	public function testOnDirtyWithOverwriteFalse() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'onDirty' => true,
			'overwrite' => false,
		]);

		// Create initial article
		$article = $this->articles->newEntity(['title' => 'Initial Title']);
		$result = $this->articles->save($article);
		$this->assertEquals('Initial-Title', $result['slug']);

		// Update title - with onDirty, slug should update even though overwrite is false
		$article = $this->articles->patchEntity($article, ['title' => 'Updated Title']);
		$result = $this->articles->save($article);
		$this->assertEquals('Updated-Title', $result['slug']);

		// Update description only - slug should NOT change
		$article = $this->articles->patchEntity($article, ['description' => 'New description']);
		$result = $this->articles->save($article);
		$this->assertEquals('Updated-Title', $result['slug']);
	}

	/**
	 * Test slug generation works with virtual fields.
	 *
	 * @return void
	 */
	public function testSlugGenerationWithVirtualField() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->setEntityClass(SluggedArticle::class);
		$this->articles->addBehavior('Tools.Slugged', [
			'label' => [
				'title',
				'special',
			],
		]);

		$data = ['title' => 'Some Article 12345', 'section' => 0];

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345-dereuromark', $result['slug']);
	}

	/**
	 * Tests slug generation fails with invalid entity config.
	 *
	 * @return void
	 */
	public function testSlugGenerationWithVirtualFieldInvalidField() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->setEntityClass(SluggedArticle::class);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('(SluggedBehavior::setup) model `SluggedArticles` is missing the field `specialNonExistent` (specified in the setup for entity `TestApp\Model\Entity\SluggedArticle`.');

		$this->articles->addBehavior('Tools.Slugged', [
			'label' => [
				'specialNonExistent',
			],
		]);
	}

	/**
	 * Test slug generation works with new slugger.
	 *
	 * @return void
	 */
	public function testSlugGenerationWithNewSlugger() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'mode' => [Text::class, 'slug'],
		]);

		$data = ['title' => 'Some Article 12345'];

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('Some-Article-12345', $result['slug']);
	}

	/**
	 * Test slug generation works with custom slugger.
	 *
	 * @return void
	 */
	public function testSlugGenerationWithCustomSlugger() {
		$this->articles->removeBehavior('Slugged');
		$this->articles->addBehavior('Tools.Slugged', [
			'mode' => [$this, '_customSluggerMethod'],
		]);

		$data = ['title' => 'Some Article 12345'];

		$article = $this->articles->newEntity($data);
		$result = $this->articles->save($article);
		$this->assertTrue((bool)$result);
		$this->assertEquals('some article 12345', $result['slug']);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function _customSluggerMethod($name) {
		return mb_strtolower($name);
	}

	/**
	 * Get a new Entity
	 *
	 * @param string|null $title
	 * @param string|null $field
	 * @param array $data
	 * @param array<string, mixed> $options
	 * @return \Cake\ORM\Entity
	 */
	protected function _getEntity($title = null, $field = null, array $data = [], array $options = []) {
		$options += ['validate' => false];
		if ($title === null) {
			$title = 'test 123';
		}
		if ($field === null) {
			$field = 'title';
		}

		$data = [
			$field => $title,
		] + $data;

		return new Entity($data, $options);
	}

}
