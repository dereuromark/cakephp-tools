<?php

namespace Tools\Test\TestCase\Form;

use Shim\TestSuite\TestCase;
use Tools\Form\ContactForm;

class ContactFormTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'core.Posts',
		'core.Authors',
		'plugin.Tools.ToolsUsers',
		'plugin.Tools.Roles',
	];

	/**
	 * @var \Tools\Form\ContactForm
	 */
	protected $Form;

	/**
	 * SetUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

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
			'body' => 'Some message',
		];
		$result = $this->Form->validate($requestData);
		$this->assertFalse($result);

		$errors = $this->Form->getErrors();
		$this->assertSame(['email', 'subject'], array_keys($errors));

		$requestData = [
			'name' => 'Foo',
			'email' => 'foo@example.org',
			'subject' => 'Yeah',
			'body' => 'Some message',
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
			'body' => 'Some message',
		];
		$result = $this->Form->execute($requestData);
		$this->assertTrue($result);
	}

}
