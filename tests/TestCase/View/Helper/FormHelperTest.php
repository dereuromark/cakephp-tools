<?php

namespace Tools\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\View\View;
use Shim\TestSuite\TestCase;
use Tools\View\Helper\FormHelper;

/**
 * FormHelper tests
 */
class FormHelperTest extends TestCase {

	/**
	 * @var \Tools\View\Helper\FormHelper
	 */
	protected $Form;

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('App.fullBaseUrl', 'http://foo.bar');

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
		$this->assertStringNotContainsString($expected, $result);

		Configure::write('FormConfig.novalidate', true);
		$this->Form = new FormHelper($this->View);

		$result = $this->Form->create();
		$this->assertStringContainsString($expected, $result);

		$result = $this->Form->create(null, ['novalidate' => false]);
		$this->assertStringNotContainsString($expected, $result);
	}

}
