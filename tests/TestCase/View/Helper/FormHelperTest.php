<?php
namespace Tools\TestCase\View\Helper;

use Tools\View\Helper\FormHelper;
use Tools\TestSuite\TestCase;
use Cake\View\View;
use Cake\Core\Configure;
use Cake\Routing\Router;

/**
 * FormHelper tests
 */
class FormHelperTest extends TestCase {

	public $Form;

	public function setUp() {
		parent::setUp();

		Configure::delete('FormConfig');

		$this->View = new View(null);
		$this->Form = new FormHelper($this->View);
	}

	/**
	 * test novalidate for create
	 *
	 * @return void
	 */
	public function testCreate() {
		$expected = 'novalidate="novalidate"';

		$result = $this->Form->create();
		$this->assertNotContains($expected, $result);

		Configure::write('FormConfig.novalidate', true);
		$this->Form = new FormHelper($this->View);

		$result = $this->Form->create();
		$this->assertContains($expected, $result);

		$result = $this->Form->create(null, ['novalidate' => false]);
		$this->assertNotContains($expected, $result);
	}

}
