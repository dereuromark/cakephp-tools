<?php
App::import('Model', 'App');
App::import('Lib', 'Tools.MyCakeTestCase');

class TestModel extends AppModel {
	var $useTable = false;
	
	public static function x() {
		return array('1'=>'x', '2'=>'y', '3'=>'z');
	}
	
}

/**
 * mainly MyModel (in tools plugin libs)
 * 2010-10-18 ms
 */
class AppModelTestCase extends MyCakeTestCase {
	
	var $App = null;
	
	var $model = null;
	var $modelName = null;

	function startTest() {
		$models = array('Auth.User', 'User', 'Setup.Configuration');
		
		$this->model = array_shift($models);
		while(!App::import('Model', $this->model) && !empty($models)) {
			$this->model = array_shift($models);
		}
		$this->App = ClassRegistry::init($this->model);
		
		list($plugin, $this->modelName) = pluginSplit($this->model);
	}

	function testAppInstance() {
		$this->out($this->modelName);
		$this->assertTrue(is_a($this->App, $this->modelName));
	}
	




	function testValidateIdentical() {
		$this->out($this->_header(__FUNCTION__));
		$this->App->data = array($this->App->alias=>array('y'=>'efg'));
		$is = $this->App->validateIdentical(array('x'=>'efg'), 'y');
		$this->assertTrue($is);
		
		$this->App->data = array($this->App->alias=>array('y'=>'2'));
		$is = $this->App->validateIdentical(array('x'=>2), 'y');
		$this->assertFalse($is);
		
		$this->App->data = array($this->App->alias=>array('y'=>'3'));
		$is = $this->App->validateIdentical(array('x'=>3), 'y', array('cast'=>'int'));
		$this->assertTrue($is);
		
		$this->App->data = array($this->App->alias=>array('y'=>'3'));
		$is = $this->App->validateIdentical(array('x'=>3), 'y', array('cast'=>'string'));
		$this->assertTrue($is);
	}
	
	
	function testValidateKey() {
		$this->out($this->_header(__FUNCTION__));
		//$this->App->data = array($this->App->alias=>array('y'=>'efg'));
		$testModel = new TestModel();
		$testModel->_schema = array(
			'id' => array (
				'type' => 'string',
				'null' => false,
				'default' => '',
				'length' => 36,
				'key' => 'primary',
				'collate' => 'utf8_unicode_ci',
				'charset' => 'utf8',
			),
			'foreign_id' => array (
				'type' => 'integer',
				'null' => false,
				'default' => '0',
				'length' => 10,
			),
		);
		
		$is = $testModel->validateKey(array('id'=>'2'));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('id'=>2));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('id'=>'4e6f-a2f2-19a4ab957338'));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('id'=>'4dff6725-f0e8-4e6f-a2f2-19a4ab957338'));
		$this->assertTrue($is);
		
		$is = $testModel->validateKey(array('id'=>''));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('id'=>''), array('allowEmpty'=>true));
		$this->assertTrue($is);
		
		
		$is = $testModel->validateKey(array('foreign_id'=>'2'));
		$this->assertTrue($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>2));
		$this->assertTrue($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>2.3));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>-2));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>'4dff6725-f0e8-4e6f-a2f2-19a4ab957338'));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>0));
		$this->assertFalse($is);
		
		$is = $testModel->validateKey(array('foreign_id'=>0), array('allowEmpty'=>true));
		$this->assertTrue($is);
	}
	
	
	function testValidateEnum() {
		$this->out($this->_header(__FUNCTION__));
		//$this->App->data = array($this->App->alias=>array('y'=>'efg'));
		$testModel = new TestModel();
		$is = $testModel->validateEnum(array('x'=>'1'), true);
		$this->assertTrue($is);
		
		$is = $testModel->validateEnum(array('x'=>'4'), true);
		$this->assertFalse($is);
		
		$is = $testModel->validateEnum(array('x'=>'5'), true, array('4', '5'));
		$this->assertTrue($is);
		
		$is = $testModel->validateEnum(array('some_key'=>'3'), 'x', array('4', '5'));
		$this->assertTrue($is);
	}
	


	function testAppInvalidate() {
		$this->out($this->_header(__FUNCTION__));
		$this->App->invalidate('fieldx', array('e %s f', 33), true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->App->invalidate('Model.fieldy', array('e %s f %s g', 33, 'xyz'), true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->App->invalidate('fieldy', array('e %s f %s g %s', true, 'xyz', 55), true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->App->invalidate('fieldy', array('valErrMandatoryField'), true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));

		$this->App->invalidate('fieldy', 'valErrMandatoryField', true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res));
		
		$this->App->invalidate('fieldy', array('a %s b %s c %s %s %s %s %s h %s', 1, 2, 3, 4, 5, 6, 7, 8), true);
		$res = $this->App->validationErrors;
		$this->out($res);
		$this->assertTrue(!empty($res) && $res['fieldy'] == 'a 1 b 2 c 3 4 5 6 7 h 8');

	}

	function testAppValidateDate() {
		$this->out($this->_header(__FUNCTION__));
		$data = array('field' => '2010-01-22');
		$res = $this->App->validateDate($data);
		$this->out(returns($res));
		$this->assertTrue($res);

		$data = array('field' => '2010-02-29');
		$res = $this->App->validateDate($data);
		$this->out(returns($res));
		$this->assertFalse($res);

		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-22'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDate($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-24 11:11:11'));
		$data = array('field' => '2010-02-23');
		$res = $this->App->validateDate($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertFalse($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-25'));
		$data = array('field' => '2010-02-25');
		$res = $this->App->validateDate($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertTrue($res);	
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-25'));
		$data = array('field' => '2010-02-25');
		$res = $this->App->validateDate($data, array('after'=>'after', 'min'=>1));
		$this->out(returns($res));
		$this->assertFalse($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->App->validateDate($data, array('after'=>'after', 'min'=>2));
		$this->out(returns($res));
		$this->assertFalse($res);	
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->App->validateDate($data, array('after'=>'after', 'min'=>1));
		$this->out(returns($res));
		$this->assertTrue($res);	

		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-24'));
		$data = array('field' => '2010-02-25');
		$res = $this->App->validateDate($data, array('after'=>'after', 'min'=>2));
		$this->out(returns($res));
		$this->assertFalse($res);	
		
		$this->App->data = array($this->App->alias=>array('before'=>'2010-02-24'));
		$data = array('field' => '2010-02-24');
		$res = $this->App->validateDate($data, array('before'=>'before', 'min'=>1));
		$this->out(returns($res));
		$this->assertFalse($res);	
		
		$this->App->data = array($this->App->alias=>array('before'=>'2010-02-25'));
		$data = array('field' => '2010-02-24');
		$res = $this->App->validateDate($data, array('before'=>'before', 'min'=>1));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('before'=>'2010-02-25'));
		$data = array('field' => '2010-02-24');
		$res = $this->App->validateDate($data, array('before'=>'before', 'min'=>2));
		$this->out(returns($res));
		$this->assertFalse($res);	
		
		$this->App->data = array($this->App->alias=>array('before'=>'2010-02-26'));
		$data = array('field' => '2010-02-24');
		$res = $this->App->validateDate($data, array('before'=>'before', 'min'=>2));
		$this->out(returns($res));
		$this->assertTrue($res);											
	}

	function testAppValidateDatetime() {
		$this->out($this->_header(__FUNCTION__));
		$data = array('field' => '2010-01-22 11:11:11');
		$res = $this->App->validateDatetime($data);
		$this->out(returns($res));
		$this->assertTrue($res);

		$data = array('field' => '2010-01-22 11:61:11');
		$res = $this->App->validateDatetime($data);
		$this->out(returns($res));
		$this->assertFalse($res);

		$data = array('field' => '2010-02-29 11:11:11');
		$res = $this->App->validateDatetime($data);
		$this->out(returns($res));
		$this->assertFalse($res);

		$data = array('field' => '');
		$res = $this->App->validateDatetime($data, array('allowEmpty'=>true));
		$this->out(returns($res));
		$this->assertTrue($res);

		$data = array('field' => '0000-00-00 00:00:00');
		$res = $this->App->validateDatetime($data, array('allowEmpty'=>true));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-22 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-24 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertFalse($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertFalse($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after', 'min'=>1));
		$this->out(returns($res));
		$this->assertFalse($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:11'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after', 'min'=>0));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:10'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateDatetime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertFalse($res);
		
	}

	function testAppValidateTime() {
		$this->out($this->_header(__FUNCTION__));
		$data = array('field' => '11:21:11');
		$res = $this->App->validateTime($data);
		$this->out(returns($res));
		$this->assertTrue($res);
		
		$data = array('field' => '11:71:11');
		$res = $this->App->validateTime($data);
		$this->out(returns($res));
		$this->assertFalse($res);

		$this->App->data = array($this->App->alias=>array('before'=>'2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateTime($data, array('before'=>'before'));
		$this->out(returns($res));
		$this->assertTrue($res);
				
		$this->App->data = array($this->App->alias=>array('after'=>'2010-02-23 11:11:12'));
		$data = array('field' => '2010-02-23 11:11:11');
		$res = $this->App->validateTime($data, array('after'=>'after'));
		$this->out(returns($res));
		$this->assertFalse($res);
	}

	function testAppValidateUrl() {
		$this->out($this->_header(__FUNCTION__));
		$data = array('field' => 'www.dereuromark.de');
		$res = $this->App->validateUrl($data, array('allowEmpty'=>true));
		$this->assertTrue($res);
		
		$data = array('field' => 'www.xxxde');
		$res = $this->App->validateUrl($data, array('allowEmpty'=>true));
		$this->assertFalse($res);
		
		$data = array('field' => 'www.dereuromark.de');
		$res = $this->App->validateUrl($data, array('allowEmpty'=>true, 'autoComplete'=>false));
		$this->assertFalse($res);
		
		$data = array('field' => 'http://www.dereuromark.de');
		$res = $this->App->validateUrl($data, array('allowEmpty'=>true, 'autoComplete'=>false));
		$this->assertTrue($res);
		
		$data = array('field' => 'www.dereuromark.de');
		$res = $this->App->validateUrl($data, array('strict'=>true));
		$this->assertTrue($res); # aha
		
		$data = array('field' => 'http://www.dereuromark.de');
		$res = $this->App->validateUrl($data, array('strict'=>false));
		$this->assertTrue($res);
		
		
		$this->skipIf(empty($_SERVER['HTTP_HOST']), 'No HTTP_HOST');

		$data = array('field' => 'http://xyz.de/some/link');
		$res = $this->App->validateUrl($data, array('deep'=>false, 'sameDomain'=>true));
		$this->assertFalse($res);
		
		$data = array('field' => '/some/link');
		$res = $this->App->validateUrl($data, array('deep'=>false, 'autoComplete'=>false));
		$this->assertFalse($res);
		
		
		$this->skipIf(strpos($_SERVER['HTTP_HOST'], '.') === false, 'No online HTTP_HOST');
		
		$data = array('field' => 'http://'.$_SERVER['HTTP_HOST'].'/some/link');
		$res = $this->App->validateUrl($data, array('deep'=>false));
		$this->assertTrue($res);
		
		$data = array('field' => '/some/link');
		$res = $this->App->validateUrl($data, array('deep'=>false, 'autoComplete'=>true));
		$this->assertTrue($res);
		
		$data = array('field' => '/some/link');
		$res = $this->App->validateUrl($data, array('deep'=>false, 'sameDomain'=>true));
		$this->assertTrue($res);
		
	}


}
