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
		$Weather = new ReflectionMethod('WeatherLib', '_url');
		$Weather->setAccessible(true);

		$res = $Weather->invoke($this->Weather, 'x.xml');
		//$res = $this->Weather->_url('x.xml');
		$this->assertEquals(WeatherLib::API_URL_FREE . 'x.xml', $res);

		$res = $Weather->invoke($this->Weather, 'x.xml', ['y' => 'z']);
		//$res = $this->Weather->_url('x.xml', ['y' => 'z']);
		$this->assertEquals(WeatherLib::API_URL_FREE . 'x.xml?y=z', $res);
	}

	public function testWeatherConditions() {
		$res = $this->Weather->conditions();
		$this->debug($res);
		$this->assertTrue(empty($res));
	}

	public function testWeather() {
		$res = $this->Weather->get('Berlin');
		$this->debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame('City', $res['request']['type']);
	}

	public function testWeatherCoords() {
		$res = $this->Weather->get('48.2,11.1');
		$this->debug($res);
		$this->assertTrue(!empty($res));
		$this->assertSame('LatLon', $res['request']['type']);
	}

}
