<?php

namespace Tools\Form;

use Cake\Core\Configure;
use Tools\Form\ContactForm;
use Tools\TestSuite\TestCase;

class ContactFormTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'core.posts', 'core.authors',
		'plugin.tools.tools_users', 'plugin.tools.roles',
	];

	/**
	 * @var \Tools\Form\ContactForm
	 */
	public $Form;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Form = new ContactForm();
	}

	/**
	 * Test testValidate
	 *
	 * @return void
	 */
	public function testValidate() {
		$requestData = [
			'name' => 'Foo',
			'email' => 'foo',
			'subject' => '',
			'message' => 'Some message'
		];
		$result = $this->Form->validate($requestData);
		$this->assertFalse($result);

		$errors = $this->Form->errors();
		$this->assertSame(['email', 'subject'], array_keys($errors));

		$requestData = [
			'name' => 'Foo',
			'email' => 'foo@example.org',
			'subject' => 'Yeah',
			'message' => 'Some message'
		];
		$result = $this->Form->validate($requestData);
		$this->assertTrue($result);
	}

	/**
	 * Test testExecute
	 *
	 * @return void
	 */
	public function testExecute() {
		$requestData = [
			'name' => 'Foo',
			'email' => 'foo@example.org',
			'subject' => 'Yeah',
			'message' => 'Some message'
		];
		$result = $this->Form->execute($requestData);
		$this->assertTrue($result);
	}

}
