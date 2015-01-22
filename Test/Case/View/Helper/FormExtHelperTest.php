<?php

App::uses('FormExtHelper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class FormExtHelperTest extends MyCakeTestCase {

	public function setUp() {
		$this->Form = new FormExtHelper(new View(null));

		parent::setUp();
	}

	public function testObject() {
		$this->assertInstanceOf('FormExtHelper', $this->Form);
	}

	/**
	 * FormExtHelperTest::testPostLink()
	 *
	 * @return void
	 */
	public function testPostLink() {
		$result = $this->Form->postLink('Delete', '/posts/delete/1');
		$this->assertTags($result, [
			'form' => [
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			],
			'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'POST'],
			'/form',
			'a' => ['href' => '#', 'class' => 'post-link postLink', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
			'Delete',
			'/a'
		]);
	}

	/**
	 * FormExtHelperTest::testPostLinkShim()
	 *
	 * @return void
	 */
	public function testPostLinkShim() {
		$result = $this->Form->postLink('foo', '/bar', ['confirm' => 'Confirm me']);
		$this->assertTextContains('onclick="if (confirm(&quot;Confirm me&quot;)) {', $result);
	}

	/**
	 * FormExtHelperTest::testDeleteLink()
	 *
	 * @return void
	 */
	public function testDeleteLink() {
		$result = $this->Form->deleteLink('Delete', '/posts/delete/1');
		$this->assertTags($result, [
			'form' => [
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			],
			'input' => ['type' => 'hidden', 'name' => '_method', 'value' => 'DELETE'],
			'/form',
			'a' => ['href' => '#', 'class' => 'delete-link deleteLink', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'],
			'Delete',
			'/a'
		]);
	}

	//Not needed right now as autoRequire has been disabled

	public function _testAutoRequire() {
		$this->Form->request->data['ContactExt']['id'] = 1;
		$this->Form->create('ContactExt');

		Configure::write('Validation.autoRequire', false);

		$result = $this->Form->input('ContactExt.imrequiredonupdate');

		$this->assertTags($result, [
			'div' => [
				'class' => 'input text'
			],
			'label' => ['for' => 'ContactExtImrequiredonupdate'],
			'Imrequiredonupdate',
			'/label',
			'input' => ['name' => 'data[ContactExt][imrequiredonupdate]', 'type' => 'text', 'id' => 'ContactExtImrequiredonupdate'],
			'/div'
		]);

		Configure::write('Validation.autoRequire', true);

		$result = $this->Form->input('ContactExt.imrequiredonupdate');
		$this->assertTags($result, [
			'div' => [
				'class' => 'input text required'
			],
			'label' => ['for' => 'ContactExtImrequiredonupdate'],
			'Imrequiredonupdate',
			'/label',
			'input' => ['name' => 'data[ContactExt][imrequiredonupdate]', 'type' => 'text', 'id' => 'ContactExtImrequiredonupdate'],
			'/div'
		]);
	}

	/**
	 * Test that browserAutoRequire disables html5 frontend form validation
	 *
	 * @return void
	 */
	public function testBrowserAutoRequire() {
		$this->Form->request->data['ContactExt']['id'] = 1;

		Configure::write('Validation.browserAutoRequire', false);

		$result = $this->Form->create('ContactExt');
		$this->assertTags($result, [
			'form' => [
				'action' => '/',
				'novalidate' => 'novalidate',
				'id' => 'ContactExtForm',
				'method' => 'post',
				'accept-charset' => 'utf-8',
			],
			'div' => [
				'style' => 'display:none;'
			],
			'input' => [
				'type' => 'hidden',
				'name' => '_method',
				'value' => 'PUT'
			],
			'/div',
		]);

		Configure::write('Validation.browserAutoRequire', true);

		$result = $this->Form->create('ContactExt');
		$this->assertTags($result, [
			'form' => [
				'action' => '/',
				'id' => 'ContactExtForm',
				'method' => 'post',
				'accept-charset' => 'utf-8',
			],
			'div' => [
				'style' => 'display:none;'
			],
			'input' => [
				'type' => 'hidden',
				'name' => '_method',
				'value' => 'PUT'
			],
			'/div',
		]);
	}

	/**
	 * TestNormalize method
	 *
	 * test that whitespaces are normalized for all inputs except textareas (which also understand new line characters)
	 *
	 * @return void
	 */
	public function testNormalize() {
		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->text('Model.field');
		$this->assertTags($result, ['input' => ['type' => 'text', 'name' => 'data[Model][field]', 'value' => 'My value', 'id' => 'ModelField']]);

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->textarea('Model.field');
		$this->assertTags($result, [
			'textarea' => ['name' => 'data[Model][field]', 'id' => 'ModelField'],
			"My\nvalue",
			'/textarea'
		]);

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->input('Model.field', ['type' => 'text']);
		$this->assertTags($result, [
			'div' => ['class' => 'input text'],
			'label' => ['for' => 'ModelField'],
			'Field',
			'/label',
			'input' => ['name' => 'data[Model][field]', 'type' => 'text', 'value' => 'My value', 'id' => 'ModelField'],
			'/div'
		]);

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->input('Model.field', ['type' => 'textarea']);
		//debug($result);
		$this->assertTags($result, [
			'div' => ['class' => 'input textarea'],
			'label' => ['for' => 'ModelField'],
			'Field',
			'/label',
			'textarea' => ['name' => 'data[Model][field]', 'cols' => '30', 'rows' => 6, 'id' => 'ModelField'],
			"My\nvalue",
			'/textarea',
			'/div'
		]);
	}

}

/**
 * Contact class
 *
 */
class ContactExt extends CakeTestModel {

	/**
	 * UseTable property
	 *
	 * @var bool
	 */
	public $useTable = false;

	/**
	 * Default schema
	 *
	 * @var array
	 */
	protected $_schema = [
		'id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
		'name' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
		'email' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
		'phone' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
		'password' => ['type' => 'string', 'null' => '', 'default' => '', 'length' => '255'],
		'published' => ['type' => 'date', 'null' => true, 'default' => null, 'length' => null],
		'created' => ['type' => 'date', 'null' => '1', 'default' => '', 'length' => ''],
		'updated' => ['type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null],
		'age' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => null]
	];

	/**
	 * Validate property
	 *
	 * @var array
	 */
	public $validate = [
		'non_existing' => [],
		'idontexist' => [],
		'imrequired' => ['rule' => ['between', 5, 30], 'allowEmpty' => false],
		'imrequiredonupdate' => ['notEmpty' => ['rule' => 'alphaNumeric', 'on' => 'update']],
		'imrequiredoncreate' => ['required' => ['rule' => 'alphaNumeric', 'on' => 'create']],
		'imrequiredonboth' => [
			'required' => ['rule' => 'alphaNumeric'],
		],
		'string_required' => 'notEmpty',
		'imalsorequired' => ['rule' => 'alphaNumeric', 'allowEmpty' => false],
		'imrequiredtoo' => ['rule' => 'notEmpty'],
		'required_one' => ['required' => ['rule' => ['notEmpty']]],
		'imnotrequired' => ['required' => false, 'rule' => 'alphaNumeric', 'allowEmpty' => true],
		'imalsonotrequired' => [
			'alpha' => ['rule' => 'alphaNumeric', 'allowEmpty' => true],
			'between' => ['rule' => ['between', 5, 30]],
		],
		'imalsonotrequired2' => [
			'alpha' => ['rule' => 'alphaNumeric', 'allowEmpty' => true],
			'between' => ['rule' => ['between', 5, 30], 'allowEmpty' => true],
		],
		'imnotrequiredeither' => ['required' => true, 'rule' => ['between', 5, 30], 'allowEmpty' => true],
		'iamrequiredalways' => [
			'email' => ['rule' => 'email'],
			'rule_on_create' => ['rule' => ['maxLength', 50], 'on' => 'create'],
			'rule_on_update' => ['rule' => ['between', 1, 50], 'on' => 'update'],
		],
	];

	/**
	 * Schema method
	 *
	 * @return void
	 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

}
