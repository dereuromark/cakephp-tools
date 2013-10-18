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
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form',
			'a' => array('href' => '#', 'class' => 'postLink', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));
	}

	/**
	 * FormExtHelperTest::testDeleteLink()
	 *
	 * @return void
	 */
	public function testDeleteLink() {
		$result = $this->Form->deleteLink('Delete', '/posts/delete/1');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'DELETE'),
			'/form',
			'a' => array('href' => '#', 'class' => 'deleteLink', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));
	}

	//Not needed right now as autoRequire has been disabled

	public function _testAutoRequire() {
		$this->Form->request->data['ContactExt']['id'] = 1;
		$this->Form->create('ContactExt');

		Configure::write('Validation.autoRequire', false);

		$result = $this->Form->input('ContactExt.imrequiredonupdate');

		$this->assertTags($result, array(
			'div' => array(
				'class' => 'input text'
			),
			'label' => array('for' => 'ContactExtImrequiredonupdate'),
			'Imrequiredonupdate',
			'/label',
			'input' => array('name' => 'data[ContactExt][imrequiredonupdate]', 'type' => 'text', 'id' => 'ContactExtImrequiredonupdate'),
			'/div'
		));

		Configure::write('Validation.autoRequire', true);

		$result = $this->Form->input('ContactExt.imrequiredonupdate');
		$this->assertTags($result, array(
			'div' => array(
				'class' => 'input text required'
			),
			'label' => array('for' => 'ContactExtImrequiredonupdate'),
			'Imrequiredonupdate',
			'/label',
			'input' => array('name' => 'data[ContactExt][imrequiredonupdate]', 'type' => 'text', 'id' => 'ContactExtImrequiredonupdate'),
			'/div'
		));
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
		$this->assertTags($result, array(
			'form' => array(
				'action' => '/',
				'novalidate' => 'novalidate',
				'id' => 'ContactExtForm',
				'method' => 'post',
				'accept-charset' => 'utf-8',
			),
			'div' => array(
				'style' => 'display:none;'
			),
			'input' => array(
				'type' => 'hidden',
				'name' => '_method',
				'value' => 'PUT'
			),
			'/div',
		));

		Configure::write('Validation.browserAutoRequire', true);

		$result = $this->Form->create('ContactExt');
		$this->assertTags($result, array(
			'form' => array(
				'action' => '/',
				'id' => 'ContactExtForm',
				'method' => 'post',
				'accept-charset' => 'utf-8',
			),
			'div' => array(
				'style' => 'display:none;'
			),
			'input' => array(
				'type' => 'hidden',
				'name' => '_method',
				'value' => 'PUT'
			),
			'/div',
		));
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
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'data[Model][field]', 'value' => 'My value', 'id' => 'ModelField')));

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->textarea('Model.field');
		$this->assertTags($result, array(
			'textarea' => array('name' => 'data[Model][field]', 'id' => 'ModelField'),
			"My\nvalue",
			'/textarea'
		));

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->input('Model.field', array('type' => 'text'));
		$this->assertTags($result, array(
			'div' => array('class' => 'input text'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'input' => array('name' => 'data[Model][field]', 'type' => 'text', 'value' => 'My value', 'id' => 'ModelField'),
			'/div'
		));

		$this->Form->request->data['Model']['field'] = "My\nvalue";
		$result = $this->Form->input('Model.field', array('type' => 'textarea'));
		//debug($result);
		$this->assertTags($result, array(
			'div' => array('class' => 'input textarea'),
			'label' => array('for' => 'ModelField'),
			'Field',
			'/label',
			'textarea' => array('name' => 'data[Model][field]', 'cols' => '30', 'rows' => 6, 'id' => 'ModelField'),
			"My\nvalue",
			'/textarea',
			'/div'
		));
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
	 * @var boolean
	 */
	public $useTable = false;

	/**
	 * Default schema
	 *
	 * @var array
	 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null),
		'age' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => null)
	);

	/**
	 * Validate property
	 *
	 * @var array
	 */
	public $validate = array(
		'non_existing' => array(),
		'idontexist' => array(),
		'imrequired' => array('rule' => array('between', 5, 30), 'allowEmpty' => false),
		'imrequiredonupdate' => array('notEmpty' => array('rule' => 'alphaNumeric', 'on' => 'update')),
		'imrequiredoncreate' => array('required' => array('rule' => 'alphaNumeric', 'on' => 'create')),
		'imrequiredonboth' => array(
			'required' => array('rule' => 'alphaNumeric'),
		),
		'string_required' => 'notEmpty',
		'imalsorequired' => array('rule' => 'alphaNumeric', 'allowEmpty' => false),
		'imrequiredtoo' => array('rule' => 'notEmpty'),
		'required_one' => array('required' => array('rule' => array('notEmpty'))),
		'imnotrequired' => array('required' => false, 'rule' => 'alphaNumeric', 'allowEmpty' => true),
		'imalsonotrequired' => array(
			'alpha' => array('rule' => 'alphaNumeric', 'allowEmpty' => true),
			'between' => array('rule' => array('between', 5, 30)),
		),
		'imalsonotrequired2' => array(
			'alpha' => array('rule' => 'alphaNumeric', 'allowEmpty' => true),
			'between' => array('rule' => array('between', 5, 30), 'allowEmpty' => true),
		),
		'imnotrequiredeither' => array('required' => true, 'rule' => array('between', 5, 30), 'allowEmpty' => true),
		'iamrequiredalways' => array(
			'email' => array('rule' => 'email'),
			'rule_on_create' => array('rule' => array('maxLength', 50), 'on' => 'create'),
			'rule_on_update' => array('rule' => array('between', 1, 50), 'on' => 'update'),
		),
	);

	/**
	 * Schema method
	 *
	 * @return void
	 */
	public function setSchema($schema) {
		$this->_schema = $schema;
	}

}
