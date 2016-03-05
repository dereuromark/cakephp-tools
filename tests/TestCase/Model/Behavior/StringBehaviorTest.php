<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Tools\TestSuite\TestCase;

class StringBehaviorTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'plugin.tools.string_comments'
	];

	/**
	 * @var \Tools\Model\Table\Table
	 */
	public $Comments;

	public function setUp() {
		parent::setUp();

		$this->Comments = TableRegistry::get('StringComments');
		$this->Comments->addBehavior('Tools.String', ['fields' => ['title'], 'input' => ['ucfirst']]);
	}

	/**
	 * StringBehaviorTest::testBasic()
	 *
	 * @return void
	 */
	public function testBasic() {
		$data = [
			'comment' => 'blabla',
			'url' => 'www.dereuromark.de',
			'title' => 'some Name',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('Some Name', $res['title']);
	}

	/**
	 * StringBehaviorTest::testMultipleFieldsAndMultipleFilters()
	 *
	 * @return void
	 */
	public function testMultipleFieldsAndMultipleFilters() {
		$this->Comments->behaviors()->String->config(['fields' => ['title', 'comment'], 'input' => ['strtolower', 'ucwords']]);

		$data = [
			'comment' => 'blaBla',
			'url' => 'www.dereuromark.de',
			'title' => 'some nAme',
		];
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$this->assertSame('Some Name', $res['title']);
		$this->assertSame('Blabla', $res['comment']);
	}

}
