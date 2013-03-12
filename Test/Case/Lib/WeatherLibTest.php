<?php
App::uses('WeatherLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class WeatherLibTest extends MyCakeTestCase {

	public function setUp() {
		Configure::write('Weather.key', '598dfbdaeb121715111208');

		App::uses('WeatherLib', 'Tools.Lib');
		$this->Weather = new WeatherLib();
	}

	public function testUrl() {
		$res = $this->Weather->_url('x.xml');
		$this->assertEquals('http://free.worldweatheronline.com/feed/x.xml', $res);

		$res = $this->Weather->_url('x.xml', array('y'=>'z'));
		$this->assertEquals('http://free.worldweatheronline.com/feed/x.xml?y=z', $res);
	}

	public function testWeatherConditions() {
		$res = $this->Weather->conditions();
		$this->out($res);
		$this->assertTrue(!empty($res));
	}

	public function testWeather() {
		$res = $this->Weather->get('48.2,11.1');
		$this->out($res);
		$this->assertTrue(!empty($res));
	}

}
