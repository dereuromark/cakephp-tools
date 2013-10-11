<?php

App::uses('DiffHelper', 'Tools.View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 * render: unified/inline
 * engine: native/shell (shell only on linux!)
 */
class DiffHelperTest extends MyCakeTestCase {

	/**
	 * SetUp method
	 */
	public function setUp() {
		parent::setUp();

		$this->Diff = new DiffHelper(new View(null));
		$this->Diff->Html = new HtmlHelper(new View(null));

		$style = <<<CSS
<style type="text/css">
del {
	color: red;
}
ins {
	color: green;
}

</style>
CSS;
		$this->out($style);
	}

	public function testAutoEngine() {
		$engine = extension_loaded('xdiff') ? 'Xdiff' : 'Native';
		$this->out('auto engine: ' . $engine);
	}

	/**
	 * String renderer
	 * source: 'context', 'unified', or 'autodetect'
	 * engine:
	 * - auto
	 * - context from unified
	 * - unified from context
	 */
	public function testReverse() {
		$this->out('String - autodetect', false);
		$text = <<<TEXT
***************
*** 1 ****
! 99999999777
--- 1 ----
! 9999944449977
TEXT;
		$res = $this->Diff->reverse($text);
		$this->out($res);

		$this->out('String - Context - render as Unified', false);
		$text = <<<TEXT
***************
*** 1 ****
! 99999999777
--- 1 ----
! 9999944449977
TEXT;
		$this->Diff->renderType('unified');
		$res = $this->Diff->reverse($text, array('mode' => 'context'));
		$this->out($res);
	}

	/**
	 * @expectedException HORDE_TEXT_DIFF_EXCEPTION
	 */
	public function testReverseUnifiedDiffNotDetectable() {
		$this->out('Unified - String', false);
		$text = <<<TEXT
@@ -1,3 +1,3 @@
 1dfdf
-jtzth6h6h6th6
+jtzh6h6th6
 xcsdfdf
TEXT;
		$this->Diff->reverse($text);
	}

	/**
	 * Auto engine + inline Render
	 * Fastest way
	 *
	 */
	public function testDiffDefault() {
		$t1 = array(
			'errgrshrth',
			'srhrthrt777 ssshsrjtz jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzth6h6h6th6' . PHP_EOL . 'xcsdfdf',
			'99999999777'
		);
		$t2 = array(
			'errgrsh3333rth',
			'srhrthrt777 hsrthsrjt888 jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzh6h6th6' . PHP_EOL . 'xcsdfdf',
			'9999944449977'
		);
		$this->out('Inline - auto', false);
		for ($i = 0; $i < 4; $i++) {
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);
		}
	}

	/**
	 * Inline render
	 * engine:
	 * - native
	 * - shell
	 * - xdiff (skip if not available)
	 */
	public function testDiffInline() {
		$t1 = array(
			'errgrshrth',
			'srhrthrt777 ssshsrjtz jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzth6h6h6th6' . PHP_EOL . 'xcsdfdf',
			'99999999777'
		);
		$t2 = array(
			'errgrsh3333rth',
			'srhrthrt777 hsrthsrjt888 jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzh6h6th6' . PHP_EOL . 'xcsdfdf',
			'9999944449977'
		);

		$this->out('Inline - Native', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('inline'));
			$this->assertTrue($this->Diff->engineType('native'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);
		}

		$this->out('Inline - Shell', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('inline'));
			$this->assertTrue($this->Diff->engineType('shell'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);
		}

		$this->skipIf(!extension_loaded('xdiff'), 'xdiff not available');

		$this->out('Inline - Xdiff', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('inline'));
			$this->assertTrue($this->Diff->engineType('xdiff'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);
		}
	}

	/**
	 * Unified renderer
	 */
	public function testDiffUnified() {
		$t1 = array(
			'errgrshrth',
			'srhrthrt777 ssshsrjtz jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzth6h6h6th6' . PHP_EOL . 'xcsdfdf',
			'99999999777'
		);
		$t2 = array(
			'errgrsh3333rth',
			'srhrthrt777 hsrthsrjt888 jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzh6h6th6' . PHP_EOL . 'xcsdfdf',
			'9999944449977'
		);

		$max = 4;

		$this->out('Unified - Native', false);
		for ($i = 0; $i < $max; $i++) {
			$this->assertTrue($this->Diff->renderType('unified'));
			$this->assertTrue($this->Diff->engineType('native'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);

		}

		$this->out('Unified - Shell', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('unified'));
			$this->assertTrue($this->Diff->engineType('shell'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);

		}

		$this->skipIf(!extension_loaded('xdiff'), 'xdiff not available');

		$this->out('Unified - Xdiff', false);
		for ($i = 0; $i < $max; $i++) {
			$this->assertTrue($this->Diff->renderType('unified'));
			$this->assertTrue($this->Diff->engineType('xdiff'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);

		}
	}

	/**
	 * Context renderer
	 */
	public function testDiffContext() {
		$t1 = array(
			'errgrshrth',
			'srhrthrt777 ssshsrjtz jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzth6h6h6th6' . PHP_EOL . 'xcsdfdf',
			'99999999777'
		);
		$t2 = array(
			'errgrsh3333rth',
			'srhrthrt777 hsrthsrjt888 jrjtjtjt',
			'1dfdf' . PHP_EOL . 'jtzh6h6th6' . PHP_EOL . 'xcsdfdf',
			'9999944449977'
		);

		$this->out('Context - Native', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('context'));
			$this->assertTrue($this->Diff->engineType('native'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);

		}

		$this->out('Context - Shell', false);
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue($this->Diff->renderType('context'));
			$this->assertTrue($this->Diff->engineType('shell'));
			$res = $this->Diff->compare($t1[$i], $t2[$i]);
			$this->out($res);

		}
	}

}
