<?php

namespace Tools\Form;

use Tools\TestSuite\TestCase;
use Tools\Form\ContactForm;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Core\Configure;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Routing\Router;
use Cake\Network\Request;
use Cake\Auth\PasswordHasherFactory;
use Cake\I18n\Time;
use Cake\Datasource\ConnectionManager;

class ContactFormTest extends TestCase {

	public $fixtures = [
		'core.posts', 'core.authors',
		'plugin.tools.tools_users', 'plugin.tools.roles',
	];

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
