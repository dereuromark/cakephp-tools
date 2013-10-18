<?php

App::uses('TreeHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class TreeHelperTest extends MyCakeTestCase {

	public $fixtures = array('core.after_tree');

	public $Model;

	/**
	 * Initial Tree
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * - Three
	 * - Four
	 * -- Four-SubA
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->Tree = new TreeHelper(new View(null));
		$this->Model = ClassRegistry::init('AfterTree');
		$this->Model->Behaviors->load('Tree');

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
		$this->assertInstanceOf('TreeHelper', $this->Tree);
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

	//TODO: beautify debug output

	public function testGenerateWithFindAll() {
		$tree = $this->Model->find('all', array('order' => array('lft' => 'ASC')));

		$output = $this->Tree->generate($tree);
		//debug($output); return;
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
		$output = str_replace(array("\t", "\r", "\n"), '', $output);
		$expected = str_replace(array("\t", "\r", "\n"), '', $expected);
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithDepth() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, array('depth' => 1));
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
	}

	public function testGenerateWithSettings() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, array('class' => 'navi', 'id' => 'main', 'type' => 'ol'));
		$expected = <<<TEXT

<ol class="navi" id="main">
	<li>One
	<ol>
		<li>One-SubA</li>
	</ol>
	</li>
	<li>Two
	<ol>
		<li>Two-SubA
		<ol>
			<li>Two-SubA-1
			<ol>
				<li>Two-SubA-1-1</li>
			</ol>
			</li>
		</ol>
		</li>
	</ol>
	</li>
	<li>Three</li>
	<li>Four
	<ol>
		<li>Four-SubA</li>
	</ol>
	</li>
</ol>

TEXT;
		$this->assertTextEquals($expected, $output);
	}

	public function testGenerateWithMaxDepth() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, array('maxDepth' => 2));
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
			<li>Two-SubA-1</li>
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

	public function testGenerateWithAutoPath() {
		$tree = $this->Model->find('threaded');
		//debug($tree);

		$output = $this->Tree->generate($tree, array('autoPath' => array(7, 10))); // Two-SubA-1
		//debug($output);
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li class="active">Two
	<ul>
		<li class="active">Two-SubA
		<ul>
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

		$output = $this->Tree->generate($tree, array('autoPath' => array(8, 9))); // Two-SubA-1-1
		//debug($output);
		$expected = <<<TEXT

<ul>
	<li>One
	<ul>
		<li>One-SubA</li>
	</ul>
	</li>
	<li class="active">Two
	<ul>
		<li class="active">Two-SubA
		<ul>
			<li class="active">Two-SubA-1
			<ul>
				<li class="active">Two-SubA-1-1</li>
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

	/**
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * -- Two-SubB
	 * -- Two-SubC
	 * - Three
	 * - Four
	 * -- Four-SubA
	 */
	public function testGenerateWithAutoPathAndHideUnrelated() {
		$data = array(
			array('name' => 'Two-SubB', 'parent_id' => 2),
			array('name' => 'Two-SubC', 'parent_id' => 2),
		);
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}

		$tree = $this->Model->find('threaded');
		$id = 6;
		$path = $this->Model->getPath($id);
		//$this->_hideUnrelated($tree, $path);

		$output = $this->Tree->generate($tree, array('autoPath' => array(6, 11), 'hideUnrelated' => true, 'treePath' => $path, 'callback' => array($this, '_myCallback'))); // Two-SubA
		//debug($output);

		$expected = <<<TEXT

<ul>
	<li>One</li>
	<li class="active">Two (active)
	<ul>
		<li class="active">Two-SubA (active)
		<ul>
			<li>Two-SubA-1</li>
		</ul>
		</li>
		<li>Two-SubB</li>
		<li>Two-SubC</li>
	</ul>
	</li>
	<li>Three</li>
	<li>Four</li>
</ul>

TEXT;
		$output = str_replace(array("\t", "\r", "\n"), '', $output);
		$expected = str_replace(array("\t", "\r", "\n"), '', $expected);
		//debug($output);
		//debug($expected);
		$this->assertTextEquals($expected, $output);
	}

	/**
	 *
	 * - One
	 * -- One-SubA
	 * - Two
	 * -- Two-SubA
	 * --- Two-SubA-1
	 * ---- Two-SubA-1-1
	 * -- Two-SubB
	 * -- Two-SubC
	 * - Three
	 * - Four
	 * -- Four-SubA
	 */
	public function testGenerateWithAutoPathAndHideUnrelatedAndSiblings() {
		$data = array(
			array('name' => 'Two-SubB', 'parent_id' => 2),
			array('name' => 'Two-SubC', 'parent_id' => 2),
		);
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}

		$tree = $this->Model->find('threaded');
		$id = 6;
		$path = $this->Model->getPath($id);

		$output = $this->Tree->generate($tree, array(
			'autoPath' => array(6, 11), 'hideUnrelated' => true, 'treePath' => $path,
			'callback' => array($this, '_myCallbackSiblings'))); // Two-SubA
		//debug($output);

		$expected = <<<TEXT

<ul>
	<li>One (sibling)</li>
	<li class="active">Two (active)
	<ul>
		<li class="active">Two-SubA (active)
		<ul>
			<li>Two-SubA-1</li>
		</ul>
		</li>
		<li>Two-SubB</li>
		<li>Two-SubC</li>
	</ul>
	</li>
	<li>Three (sibling)</li>
	<li>Four (sibling)</li>
</ul>

TEXT;
		$output = str_replace(array("\t", "\r", "\n"), '', $output);
		$expected = str_replace(array("\t", "\r", "\n"), '', $expected);
		//debug($output);
		//debug($expected);
		$this->assertTextEquals($expected, $output);
	}

	public function _myCallback($data) {
		if (!empty($data['data']['AfterTree']['hide'])) {
			return;
		}
		return $data['data']['AfterTree']['name'] . ($data['activePathElement'] ? ' (active)' : '');
	}

	public function _myCallbackSiblings($data) {
		if (!empty($data['data']['AfterTree']['hide'])) {
			return;
		}
		if ($data['depth'] == 0 && $data['isSibling']) {
			return $data['data']['AfterTree']['name'] . ' (sibling)';
		}
		return $data['data']['AfterTree']['name'] . ($data['activePathElement'] ? ' (active)' : '');
	}

	public function testGenerateProductive() {
		$tree = $this->Model->find('threaded');

		$expected = '<ul><li>One<ul><li>One-SubA</li></ul></li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1<ul><li>Two-SubA-1-1</li></ul></li></ul></li></ul></li><li>Three</li><li>Four<ul><li>Four-SubA</li></ul></li></ul>';

		$output = $this->Tree->generate($tree, array('indent' => false));

		$this->assertTextEquals($expected, $output);
	}

}
