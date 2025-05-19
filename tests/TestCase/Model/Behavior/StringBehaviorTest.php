<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Shim\TestSuite\TestCase;

class StringBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.StringComments',
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	protected $Comments;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Comments = $this->getTableLocator()->get('StringComments');
	}

	/**
	 * @return void
	 */
	public function testBasic() {
		$this->Comments->addBehavior('Tools.String', ['fields' => ['title'], 'input' => ['ucfirst']]);
		$data = [
			'title' => 'some Name',
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('Some Name', $res['title']);
	}

	/**
	 * @return void
	 */
	public function testClean() {
		$this->Comments->addBehavior('Tools.String', ['fields' => ['title', 'comment', 'url'], 'clean' => true]);
		$data = [
			'title' => "some\r\nname",
			'comment' => 'bla  bla',
			'url' => 'www.dereuromark.de ',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('some name', $res['title']);
		$this->assertSame('bla bla', $res['comment']);
		$this->assertSame('www.dereuromark.de', $res['url']);
	}

	/**
	 * @return void
	 */
	public function testMultipleFieldsAndMultipleFilters() {
		$this->Comments->addBehavior('Tools.String', ['fields' => ['title', 'comment'], 'input' => ['strtolower', 'ucwords']]);

		$data = [
			'title' => 'some nAme',
			'comment' => 'blaBla',
			'url' => 'www.dereuromark.de',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('Some Name', $res['title']);
		$this->assertSame('Blabla', $res['comment']);
	}

	/**
	 * @return void
	 */
	public function testFieldsWithCustomFilters() {
		$this->Comments->addBehavior('Tools.String', [
			'input' => [
				'title' => [
					function(string $e): string {
						return ucwords($e);
					}, function(string $e): string {
						return str_replace(' ', '', $e);
					},
				],
			],
		]);

		$data = [
			'title' => 'some name',
			'comment' => 'blaBla',
			'url' => 'www.dereuromark.de',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('SomeName', $res['title']);
	}

	/**
	 * @return void
	 */
	public function testBasicOutput() {
		$data = [
			'title' => 'some Name',
			'comment' => 'blabla',
			'url' => '',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->Comments->addBehavior('Tools.String', ['fields' => ['title'], 'output' => ['ucfirst']]);

		$res = $this->Comments->get($entity->id);
		$this->assertSame('Some Name', $res['title']);
	}

}
