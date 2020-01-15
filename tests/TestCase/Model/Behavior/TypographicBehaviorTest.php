<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Shim\TestSuite\TestCase;
use Tools\Model\Behavior\TypographicBehavior;

class TypographicBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'core.Articles',
	];

	/**
	 * @var \Cake\ORM\Table|\Tools\Model\Behavior\TypographicBehavior
	 */
	protected $Model;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Model = TableRegistry::getTableLocator()->get('Articles');
		$this->Model->addBehavior('Tools.Typographic', ['fields' => ['body'], 'before' => 'marshal']);
	}

	/**
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf(TypographicBehavior::class, $this->Model->behaviors()->Typographic);
	}

	/**
	 * @return void
	 */
	public function testBeforeMarshal() {
		$data = [
			'title' => 'some «cool» title',
			'body' => 'A title with normal "qotes" - should be left untouched',
		];
		$entity = $this->Model->newEntity($data);
		$this->assertEmpty($entity->getErrors());

		$result = $entity->toArray();
		$this->assertSame($data, $result);

		$strings = [
			'some string with ‹single angle quotes›' => 'some string with \'single angle quotes\'',
			'other string with „German‟ quotes' => 'other string with "German" quotes',
			'mixed single ‚one‛ and ‘two’.' => 'mixed single \'one\' and \'two\'.',
			'mixed double “one” and «two».' => 'mixed double "one" and "two".',
		];
		foreach ($strings as $was => $expected) {
			$data = [
				'title' => 'some «cool» title',
				'body' => $was,
			];
			$entity = $this->Model->newEntity($data);
			$this->assertEmpty($entity->getErrors());

			$result = $entity->toArray();
			$this->assertSame($data['title'], $result['title']);
			$this->assertSame($expected, $result['body']);
		}
	}

	/**
	 * @return void
	 */
	public function testMergeQuotes() {
		$this->Model->removeBehavior('Typographic');
		$this->Model->addBehavior('Tools.Typographic', ['before' => 'marshal', 'mergeQuotes' => true]);
		$strings = [
			'some string with ‹single angle quotes›' => 'some string with "single angle quotes"',
			'other string with „German‟ quotes' => 'other string with "German" quotes',
			'mixed single ‚one‛ and ‘two’.' => 'mixed single "one" and "two".',
			'mixed double “one” and «two».' => 'mixed double "one" and "two".',
		];
		foreach ($strings as $was => $expected) {
			$data = [
				'title' => 'some «cool» title',
				'body' => $was,
			];
			$entity = $this->Model->newEntity($data);
			$this->assertEmpty($entity->getErrors());

			$result = $entity->toArray();
			$this->assertSame('some "cool" title', $result['title']);
			$this->assertSame($expected, $result['body']);
		}
	}

	/**
	 * Test that not defining fields results in all textarea and text fields being processed
	 *
	 * @return void
	 */
	public function testAutoFields() {
		$this->Model->removeBehavior('Typographic');
		$this->Model->addBehavior('Tools.Typographic');
		$data = [
			'title' => '„German‟ quotes',
			'body' => 'mixed double “one” and «two»',
		];

		$entity = $this->Model->newEntity($data);
		$this->assertEmpty($entity->getErrors());
		$res = $this->Model->save($entity);
		$this->assertTrue((bool)$res);

		$expected = [
			'title' => '"German" quotes',
			'body' => 'mixed double "one" and "two"',
		];

		$this->assertSame($expected['title'], $res['title']);
		$this->assertSame($expected['body'], $res['body']);
	}

	/**
	 * @return void
	 */
	public function testUpdateTypography() {
		$this->Model->removeBehavior('Typographic');
		for ($i = 0; $i < 202; $i++) {
			$data = [
				'title' => 'title ' . $i,
				'body' => 'unclean `content` to «correct»',
			];
			$entity = $this->Model->newEntity($data);
			$result = $this->Model->save($entity);
			$this->assertTrue((bool)$result);
		}
		$this->Model->addBehavior('Tools.Typographic');
		$count = $this->Model->updateTypography();
		$this->assertTrue($count >= 190, 'Count is only ' . $count);

		$record = $this->Model->find()->orderDesc('id')->firstOrFail();
		$this->assertSame('unclean `content` to "correct"', $record['body']);
	}

}
