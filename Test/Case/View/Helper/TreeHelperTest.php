<?php

App::uses('TreeHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');
App::uses('Hash', 'Utility');

class TreeHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.after_tree');

	public $Model;

	public function setUp() {
		parent::setUp();

		$this->Tree = new TreeHelper(new View(null));
		$this->Model = ClassRegistry::init('AfterTree');
		$this->Model->Behaviors->attach('Tree');

		$this->Model->truncate();

		$data = array(
			array('name' => 'One'),
			array('name' => 'Two'),
			array('name' => 'Three'),
			array('name' => 'Four'),

			array('name' => 'One-SubA', 'parent_id' => 1),
			array('name' => 'Two-SubA', 'parent_id' => 2),
			array('name' => 'Four-SubA', 'parent_id' => 4),

			array('name' => 'Two-SubA-1', 'parent_id' => 6),

			array('name' => 'Two-SubA-1-1', 'parent_id' => 8),
		);
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testObject() {
		$this->assertTrue(is_a($this->Tree, 'TreeHelper'));
	}

	public function testGenerate() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree);

		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li>Two
	<ul>
		<li>Two-SubA
		<ul>
			<li>Two-SubA-1
			<ul>
				<li>Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
		$this->assertTrue(substr_count($output, '<ul>') === substr_count($output, '</ul>'));
		$this->assertTrue(substr_count($output, '<li>') === substr_count($output, '</li>'));
	}

	//TODO: fixme: 8,9 is "Two-SubA-1-1" and so this entry should also be active
	public function testGenerateWithAutoPath() {
		$tree = $this->Model->find('threaded');
		debug($tree);

		$output = $this->Tree->generate($tree, array('autoPath' => array(8, 9)));
		debug($output);
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li>Two
	<ul class="active">
		<li class="active">Two-SubA
		<ul class="active">
			<li class="active">Two-SubA-1
			<ul>
				<li>Two-SubA-1-1</li>
			</ul>
			</li>
		</ul>
		</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four
	<ul>
		<li>Four-SubA</li>
	</ul>
	</li>
</ul>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

}
