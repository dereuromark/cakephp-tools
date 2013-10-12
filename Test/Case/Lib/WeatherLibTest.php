<?php
App::uses('WeatherLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class WeatherLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Weather = new WeatherLib();

		$this->skipIf(!Configure::read('Weather.key'));
	}

	public function testUrl() {
		$res = $this->Weather->_url('x.xml');
		$this->assertEquals('http://api.worldweatheronline.com/free/v1/x.xml', $res);

		$res = $this->Weather->_url('x.xml', array('y' => 'z'));
		$this->assertEquals('http://api.worldweatheronline.com/free/v1/x.xml?y=z', $res);
	}

	public function testWeatherConditions() {
		$res = $this->Weather->conditions();
		$this->debug($res);
		$this->assertTrue(empty($res));
	}

	public function testWeather() {
		$res = $this->Weather->get('48.2,11.1');
		$this->debug($res);
		$this->assertTrue(!empty($res));
	}

}
