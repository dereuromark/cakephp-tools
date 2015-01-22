<?php

App::uses('TreeHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class TreeHelperTest extends MyCakeTestCase {

	public $fixtures = ['core.after_tree'];

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

		$data = [
			['name' => 'One'],
			['name' => 'Two'],
			['name' => 'Three'],
			['name' => 'Four'],

			['name' => 'One-SubA', 'parent_id' => 1],
			['name' => 'Two-SubA', 'parent_id' => 2],
			['name' => 'Four-SubA', 'parent_id' => 4],

			['name' => 'Two-SubA-1', 'parent_id' => 6],

			['name' => 'Two-SubA-1-1', 'parent_id' => 8],
		];
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * TreeHelperTest::testObject()
	 *
	 * @return void
	 */
	public function testObject() {
		$this->assertInstanceOf('TreeHelper', $this->Tree);
	}

	/**
	 * TreeHelperTest::testGenerate()
	 *
	 * @return void
	 */
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

	/**
	 * TreeHelperTest::testGenerateWithFindAll()
	 *
	 * @return void
	 */
	public function testGenerateWithFindAll() {
		$tree = $this->Model->find('all', ['order' => ['lft' => 'ASC']]);

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
		$output = str_replace(["\t", "\r", "\n"], '', $output);
		$expected = str_replace(["\t", "\r", "\n"], '', $expected);
		$this->assertTextEquals($expected, $output);
	}

	/**
	 * TreeHelperTest::testGenerateWithDepth()
	 *
	 * @return void
	 */
	public function testGenerateWithDepth() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, ['depth' => 1]);
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

	/**
	 * TreeHelperTest::testGenerateWithSettings()
	 *
	 * @return void
	 */
	public function testGenerateWithSettings() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, ['class' => 'navi', 'id' => 'main', 'type' => 'ol']);
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

	/**
	 * TreeHelperTest::testGenerateWithMaxDepth()
	 *
	 * @return void
	 */
	public function testGenerateWithMaxDepth() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, ['maxDepth' => 2]);
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

	/**
	 * TreeHelperTest::testGenerateWithAutoPath()
	 *
	 * @return void
	 */
	public function testGenerateWithAutoPath() {
		$tree = $this->Model->find('threaded');
		//debug($tree);

		$output = $this->Tree->generate($tree, ['autoPath' => [7, 10]]); // Two-SubA-1
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

		$output = $this->Tree->generate($tree, ['autoPath' => [8, 9]]); // Two-SubA-1-1
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
		$data = [
			['name' => 'Two-SubB', 'parent_id' => 2],
			['name' => 'Two-SubC', 'parent_id' => 2],
		];
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}

		$tree = $this->Model->find('threaded');
		$id = 6;
		$path = $this->Model->getPath($id);
		//$this->_hideUnrelated($tree, $path);

		$output = $this->Tree->generate($tree, ['autoPath' => [6, 11], 'hideUnrelated' => true, 'treePath' => $path, 'callback' => [$this, '_myCallback']]); // Two-SubA
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
		$output = str_replace(["\t", "\r", "\n"], '', $output);
		$expected = str_replace(["\t", "\r", "\n"], '', $expected);
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
		$data = [
			['name' => 'Two-SubB', 'parent_id' => 2],
			['name' => 'Two-SubC', 'parent_id' => 2],
		];
		foreach ($data as $row) {
			$this->Model->create();
			$this->Model->save($row);
		}

		$tree = $this->Model->find('threaded');
		$id = 6;
		$path = $this->Model->getPath($id);

		$output = $this->Tree->generate($tree, [
			'autoPath' => [6, 11], 'hideUnrelated' => true, 'treePath' => $path,
			'callback' => [$this, '_myCallbackSiblings']]); // Two-SubA
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
		$output = str_replace(["\t", "\r", "\n"], '', $output);
		$expected = str_replace(["\t", "\r", "\n"], '', $expected);
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

	/**
	 * TreeHelperTest::testGenerateProductive()
	 *
	 * @return void
	 */
	public function testGenerateProductive() {
		$tree = $this->Model->find('threaded');

		$output = $this->Tree->generate($tree, ['indent' => false]);
		$expected = '<ul><li>One<ul><li>One-SubA</li></ul></li><li>Two<ul><li>Two-SubA<ul><li>Two-SubA-1<ul><li>Two-SubA-1-1</li></ul></li></ul></li></ul></li><li>Three</li><li>Four<ul><li>Four-SubA</li></ul></li></ul>';

		$this->assertTextEquals($expected, $output);
	}

}
