<?php

App::uses('GeocodeLib', 'Tools.Lib');

# google maps
Configure::write('Google', array(
	'key' => 'ABQIAAAAk-aSeht5vBRyVc9CjdBKLRRnhS8GMCOqu88EXp1O-QqtMSdzHhQM4y1gkHFQdUvwiZgZ6jaKlW40kw',	//local
	'api' => '2.x',
	'zoom' => 16,
	'lat' => null,
	'lng' => null,
	'type' => 'G_NORMAL_MAP'
));

class GeocodeLibTest extends CakeTestCase {

	public function setUp() {
		$this->GeocodeLib = new GeocodeLib();
	}

	public function TearDown() {
		unset($this->GeocodeLib);
	}
	
	public function testObject() {
		$this->assertTrue(is_object($this->GeocodeLib));
		$this->assertTrue(is_a($this->GeocodeLib, 'GeocodeLib'));
	}


	public function testDistance() {
		$coords = array(
			array('name'=>'MUC/Pforzheim (269km road, 2:33h)', 'x'=>array('lat'=>48.1391, 'lng'=>11.5802), 'y'=>array('lat'=>48.8934, 'lng'=>8.70492), 'd'=>228),
			array('name'=>'MUC/London (1142km road, 11:20h)', 'x'=>array('lat'=>48.1391, 'lng'=>11.5802), 'y'=>array('lat'=>51.508, 'lng'=>-0.124688), 'd'=>919),
			array('name'=>'MUC/NewYork (--- road, ---h)', 'x'=>array('lat'=>48.1391, 'lng'=>11.5802), 'y'=>array('lat'=>40.700943, 'lng'=>-73.853531), 'd'=>6479)
		);

		foreach ($coords as $coord) {
			$is = $this->GeocodeLib->distance($coord['x'], $coord['y']);
			echo $coord['name'].':';
			pr('is: '.$is.' - expected: '.$coord['d']);
			$this->assertEquals($coord['d'], $is);
		}

	}

	public function testConvert() {
		$values = array(
			array(3, 'M', 'K', 4.828032),
			array(3, 'K', 'M', 1.86411358),
			array(100000, 'I', 'K', 2.54),
		);
		foreach ($values as $value) {
			$is = $this->GeocodeLib->convert($value[0], $value[1], $value[2]);
			echo $value[0].$value[1].' in '.$value[2].':';
			pr('is: '.returns($is).' - expected: '.$value[3]);
			$this->assertEquals($value[3], round($is, 8));
		}
	}


	public function testUrl() {
		$is = $this->GeocodeLib->url();
		pr($is);
		$this->assertTrue(!empty($is) && startsWith($is, 'http://maps.google.de/maps/api/geocode/xml?'));
	}


	// not possible with protected method
	public function _testFetch() {
		$url = 'http://maps.google.com/maps/api/geocode/xml?sensor=false&address=74523';
		$is = $this->GeocodeLib->_fetch($url);
		//echo returns($is);

		$this->assertTrue(!empty($is) && substr($is, 0, 38) == '<?xml version="1.0" encoding="UTF-8"?>');

		$url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address=74523';
		$is = $this->GeocodeLib->_fetch($url);
		//echo returns($is);
		$this->assertTrue(!empty($is) && substr($is, 0, 1) == '{');

	}

	public function testSetParams() {

	}


	public function testSetOptions() {
		$this->GeocodeLib->setOptions(array('host'=>'xx'));
		# should remain ".com"
		$res = $this->GeocodeLib->url();
		pr($res);

		$this->GeocodeLib->setOptions(array('host'=>'de'));
		# should now be ".de"
		$res = $this->GeocodeLib->url();
		pr($res);

		# now DE

	}


	public function testGeocode() {
		$address = '74523 Deutschland';
		echo '<h2>'.$address.'</h2>';
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertTrue($is);

		$is = $this->GeocodeLib->getResult();
		echo returns($is);
		$this->assertTrue(!empty($is));

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(empty($is));


		$address = 'Leopoldstraße 100, München';
		echo '<h2>'.$address.'</h2>';
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertTrue($is);

		pr($this->GeocodeLib->debug());

		$is = $this->GeocodeLib->getResult();
		echo returns($is);
		$this->assertTrue(!empty($is));

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(empty($is));


		$address = 'Oranienburger Straße 87, 10178 Berlin, Deutschland';
		echo '<h2>'.$address.'</h2>';
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertTrue($is);

		pr($this->GeocodeLib->debug());

		$is = $this->GeocodeLib->getResult();
		echo returns($is);
		$this->assertTrue(!empty($is));

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(empty($is));

	}

	public function testGeocodeInvalid() {
		$address = 'Hjfjosdfhosj, 78878 Mdfkufsdfk';
		echo '<h2>'.$address.'</h2>';
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertFalse($is);

		pr($this->GeocodeLib->debug());

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	public function testGeocodeMinAcc() {
		$address = 'Deutschland';
		echo '<h2>'.$address.'</h2>';
		$this->GeocodeLib->setOptions(array('min_accuracy'=>3));
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertFalse($is);

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(!empty($is));
	}


	public function testGeocodeInconclusive() {
		// seems like there is no inconclusive result anymore!!!


		$address = 'Neustadt';
		echo '<h2>'.$address.'</h2>';

		# allow_inconclusive = TRUE
		$this->GeocodeLib->setOptions(array('allow_inconclusive'=>true));
		$is = $this->GeocodeLib->geocode($address);
		echo 'debug:';
		pr($this->GeocodeLib->debug());
		echo 'debug end';
		$this->assertTrue($is);

		$res = $this->GeocodeLib->getResult();
		pr($res);
		$this->assertTrue(count($res) > 4);

		$is = $this->GeocodeLib->isInconclusive();
		$this->assertTrue($is);


		# allow_inconclusive = FALSE
		$this->GeocodeLib->setOptions(array('allow_inconclusive'=>false));
		$is = $this->GeocodeLib->geocode($address);
		echo returns($is);
		$this->assertFalse($is);

		$is = $this->GeocodeLib->error();
		echo returns($is);
		$this->assertTrue(!empty($is));

	}


	public function testReverseGeocode() {
		$coords = array(
			array(-34.594445, -58.37446, 'Florida 1134-1200, Buenos Aires, Capital Federal, Argentinien'),
			array(48.8934, 8.70492, 'B294, 75175 Pforzheim, Deutschland')
		);

		foreach ($coords as $coord) {
			$is = $this->GeocodeLib->reverseGeocode($coord[0], $coord[1]);
			echo returns($is);
			$this->assertTrue($is);

			$is = $this->GeocodeLib->getResult();
			$this->assertTrue(!empty($is));
			echo returns($is);
			$address = isset($is[0]) ? $is[0]['formatted_address'] : $is['formatted_address'];
			$this->assertEquals($coord[2], $address);
		}

	}




	public function testGetResult() {

	}

}