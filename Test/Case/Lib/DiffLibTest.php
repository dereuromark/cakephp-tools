<?php
App::uses('DiffLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class DiffLibTest extends MyCakeTestCase {

	public $Diff = null;

	public function setUp() {
		parent::setUp();

		$this->Diff = new DiffLib();

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
		$this->out($style, true);
	}

	public function testCompare() {
	}

	public function testReverse() {
		$this->out('String - autodetect', true);
		$text = <<<TEXT
***************
*** 1 ****
! 99999999777
--- 1 ----
! 9999944449977
TEXT;
		$res = $this->Diff->reverse($text);
		$this->out($res);

		$this->out('String - Context - render as Unified', true);
	}

	public function testParseDiff() {
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

}
