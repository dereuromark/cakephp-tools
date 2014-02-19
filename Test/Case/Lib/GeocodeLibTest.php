<?php

App::uses('GeocodeLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

# google maps
Configure::write('Google', array(
	'key' => 'ABQIAAAAk-aSeht5vBRyVc9CjdBKLRRnhS8GMCOqu88EXp1O-QqtMSdzHhQM4y1gkHFQdUvwiZgZ6jaKlW40kw',	//local
	'api' => '2.x',
	'zoom' => 16,
	'lat' => null,
	'lng' => null,
	'type' => 'G_NORMAL_MAP'
));

class GeocodeLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->Geocode = new GeocodeLib();
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Geocode);
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Geocode));
		$this->assertInstanceOf('GeocodeLib', $this->Geocode);
	}

	public function testDistance() {
		$coords = array(
			array('name' => 'MUC/Pforzheim (269km road, 2:33h)', 'x' => array('lat' => 48.1391, 'lng' => 11.5802), 'y' => array('lat' => 48.8934, 'lng' => 8.70492), 'd' => 228),
			array('name' => 'MUC/London (1142km road, 11:20h)', 'x' => array('lat' => 48.1391, 'lng' => 11.5802), 'y' => array('lat' => 51.508, 'lng' => -0.124688), 'd' => 919),
			array('name' => 'MUC/NewYork (--- road, ---h)', 'x' => array('lat' => 48.1391, 'lng' => 11.5802), 'y' => array('lat' => 40.700943, 'lng' => -73.853531), 'd' => 6479)
		);

		foreach ($coords as $coord) {
			$is = $this->Geocode->distance($coord['x'], $coord['y']);
			//echo $coord['name'].':';
			//pr('is: '.$is.' - expected: '.$coord['d']);
			$this->assertEquals($coord['d'], $is);
		}
	}

	public function testBlur() {
		$coords = array(
			array(48.1391, 1, 0.002), //'y'=>array('lat'=>48.8934, 'lng'=>8.70492), 'd'=>228),
			array(11.5802, 1, 0.002),
		);
		foreach ($coords as $coord) {
			$is = $this->Geocode->blur($coord[0], $coord[1]);
			//pr('is: '.$is.' - expected: '.$coord[0].' +- '.$coord[2]);
			$this->assertWithinMargin($is, $coord[0], $coord[2]);
			$this->assertNotWithinMargin($is, $coord[0], $coord[2] / 4);
		}
	}

	public function testConvert() {
		$values = array(
			array(3, 'M', 'K', 4.828032),
			array(3, 'K', 'M', 1.86411358),
			array(100000, 'I', 'K', 2.54),
		);
		foreach ($values as $value) {
			$is = $this->Geocode->convert($value[0], $value[1], $value[2]);
			//echo $value[0].$value[1].' in '.$value[2].':';
			//pr('is: '.returns($is).' - expected: '.$value[3]);
			$this->assertEquals($value[3], round($is, 8));
		}
	}

	public function testUrl() {
		$is = $this->Geocode->url();
		//debug($is);
		$this->assertTrue(!empty($is) && strpos($is, 'http://maps.googleapis.com/maps/api/geocode/xml?') === 0);
	}

	// not possible with protected method

	public function _testFetch() {
		$url = 'http://maps.google.com/maps/api/geocode/xml?sensor=false&address=74523';
		$is = $this->Geocode->_fetch($url);
		//debug($is);

		$this->assertTrue(!empty($is) && substr($is, 0, 38) === '<?xml version="1.0" encoding="UTF-8"?>');

		$url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address=74523';
		$is = $this->Geocode->_fetch($url);
		//debug($is);
		$this->assertTrue(!empty($is) && substr($is, 0, 1) === '{');
	}

	public function testSetParams() {
	}

	public function testWithJson() {
		$this->Geocode->setOptions(array('output' => 'json'));
		$address = '74523 Deutschland';
		//echo '<h2>'.$address.'</h2>';
		$is = $this->Geocode->geocode($address);
		$this->assertTrue($is);

		$is = $this->Geocode->getResult();
		//debug($is);
		$this->assertTrue(!empty($is));
	}

	public function testSetOptions() {
		// should be the default
		$res = $this->Geocode->url();
		$this->assertTextContains('maps.googleapis.com', $res);

		$this->Geocode->setOptions(array('host' => 'maps.google.it'));
		// should now be ".it"
		$res = $this->Geocode->url();
		$this->assertTextContains('maps.google.it', $res);
	}

	public function testGeocode() {
		$address = '74523 Deutschland';
		//echo '<h2>'.$address.'</h2>';
		$is = $this->Geocode->geocode($address);
		//debug($is);
		$this->assertTrue($is);

		$is = $this->Geocode->getResult();
		//debug($is);
		$this->assertTrue(!empty($is));

		$is = $this->Geocode->error();
		//debug($is);
		$this->assertTrue(empty($is));

		$address = 'Leopoldstraße 100, München';
		//echo '<h2>'.$address.'</h2>';
		$is = $this->Geocode->geocode($address);
		//debug($is);
		$this->assertTrue($is);

		//pr($this->Geocode->debug());

		$is = $this->Geocode->getResult();
		//debug($is);
		$this->assertTrue(!empty($is));

		$is = $this->Geocode->error();
		//debug($is);
		$this->assertTrue(empty($is));

		$address = 'Oranienburger Straße 87, 10178 Berlin, Deutschland';
		//echo '<h2>'.$address.'</h2>';
		$is = $this->Geocode->geocode($address);
		//debug($is);
		$this->assertTrue($is);

		//pr($this->Geocode->debug());

		$is = $this->Geocode->getResult();
		//debug($is);
		$this->assertTrue(!empty($is));

		$is = $this->Geocode->error();
		//debug($is);
		$this->assertTrue(empty($is));
	}

	public function testGeocodeInvalid() {
		$address = 'Hjfjosdfhosj, 78878 Mdfkufsdfk';
		//echo '<h2>'.$address.'</h2>';
		$is = $this->Geocode->geocode($address);
		//debug($is);
		$this->assertFalse($is);

		//pr($this->Geocode->debug());

		$is = $this->Geocode->error();
		//debug($is);
		$this->assertTrue(!empty($is));
	}

	public function testGeocodeMinAcc() {
		$address = 'Deutschland';
		//echo '<h2>'.$address.'</h2>';
		$this->Geocode->setOptions(array('min_accuracy' => 3));
		$is = $this->Geocode->geocode($address);
		//debug($is);
		$this->assertFalse($is);

		$is = $this->Geocode->error();
		//debug($is);
		$this->assertTrue(!empty($is));
	}

	public function testGeocodeInconclusive() {
		// seems like there is no inconclusive result anymore!!!

		$address = 'Neustadt';
		//echo '<h2>'.$address.'</h2>';

		// allow_inconclusive = TRUE
		$this->Geocode->setOptions(array('allow_inconclusive' => true, 'min_accuracy' => GeocodeLib::ACC_LOC));
		$is = $this->Geocode->geocode($address);
		//echo 'debug:';
		//pr($this->Geocode->debug());
		//echo 'debug end';
		$this->assertTrue($is);

		$res = $this->Geocode->getResult();
		//pr($res);
		$this->assertTrue(count($res) > 4);

		$is = $this->Geocode->isInconclusive();
		$this->assertTrue($is);

		$this->Geocode->setOptions(array('allow_inconclusive' => false));
		$is = $this->Geocode->geocode($address);
		$this->assertFalse($is);
	}

	public function testReverseGeocode() {
		$coords = array(
			array(-34.594445, -58.37446, 'Calle Florida 1134-1200, Buenos Aires'),
			array(48.8934, 8.70492, 'B294, 75175 Pforzheim, Deutschland')
		);

		foreach ($coords as $coord) {
			$is = $this->Geocode->reverseGeocode($coord[0], $coord[1]);
			$this->assertTrue($is);

			$is = $this->Geocode->getResult();
			$this->assertTrue(!empty($is));
			//debug($is);
			$address = isset($is[0]) ? $is[0]['formatted_address'] : $is['formatted_address'];
			$this->assertTextContains($coord[2], $address);
		}
	}

	public function testGetResult() {
	}

}
