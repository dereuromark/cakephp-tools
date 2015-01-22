<?php

App::uses('GeocodeLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('HttpSocketResponse', 'Network/Http');

# google maps
Configure::write('Google', [
	'key' => 'ABQIAAAAk-aSeht5vBRyVc9CjdBKLRRnhS8GMCOqu88EXp1O-QqtMSdzHhQM4y1gkHFQdUvwiZgZ6jaKlW40kw',	//local
	'api' => '2.x',
	'zoom' => 16,
	'lat' => null,
	'lng' => null,
	'type' => 'G_NORMAL_MAP'
]);

class GeocodeLibTest extends MyCakeTestCase {

	public $apiMockupReverseGeocode40206 = [
		'reverseGeocode' => [
			'lat' => '38.2643',
			'lng' => '-85.6999',
			'params' => [
				'address' => '40206',
				'latlng' => '',
				'region' => '',
				'language' => 'en',
				'bounds' => '',
				'sensor' => 'false',
				'key' => 'AIzaSyAcQWSeMp_RF9W2_g2vOfLlUNCieHtHfFA',
				'result_type' => 'sublocality'
			]
		],
		'_fetch' => 'https://maps.googleapis.com/maps/api/geocode/json?address=40206&latlng=38.2643%2C-85.6999&language=en&sensor=false',
		'raw' => '{
			"results" : [
				{
					"address_components" : [
						{ "long_name" : "40206", "short_name" : "40206", "types" : [ "postal_code" ] },
						{ "long_name" : "Louisville", "short_name" : "Louisville", "types" : [ "locality", "political" ] },
						{ "long_name" : "Kentucky", "short_name" : "KY", "types" : [ "administrative_area_level_1", "political" ] },
						{ "long_name" : "United States", "short_name" : "US", "types" : [ "country", "political" ] }
					],
					"formatted_address" : "Louisville, KY 40206, USA",
					"geometry" : {
						"bounds" : {
							"northeast" : { "lat" : 38.2852558, "lng" : -85.664309 },
							"southwest" : { "lat" : 38.2395658, "lng" : -85.744801 }
						},
						"location" : { "lat" : 38.26435780000001, "lng" : -85.69997889999999 },
						"location_type" : "APPROXIMATE",
						"viewport" : {
							"northeast" : { "lat" : 38.2852558, "lng" : -85.664309 },
							"southwest" : { "lat" : 38.2395658, "lng" : -85.744801 }
						}
					},
					"types" : [ "postal_code" ]
				}
			],
			"status" : "OK"
		}',
	];

	public function setUp() {
		parent::setUp();

		$this->Geocode = new GeocodeLib();

		$this->mockFilePath = CakePlugin::path('Tools') . 'Test' . DS . 'test_files' . DS . 'google' . DS;
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Geocode);
	}

	public function testObject() {
		$this->assertTrue(is_object($this->Geocode));
		$this->assertInstanceOf('GeocodeLib', $this->Geocode);
	}

	/**
	 * GeocodeLibTest::testReverseGeocode()
	 *
	 * @return void
	 */
	public function testReverseGeocode() {
		$coords = [
			[-34.594445, -58.37446, 'Florida 1134-1200, Buenos Aires'],
			[48.8934, 8.70492, 'B294, 75175 Pforzheim, Deutschland']
		];

		foreach ($coords as $k => $coord) {
			if (!$this->isDebug()) {
				$this->_getMock('reverse' . $k);
			}

			$is = $this->Geocode->reverseGeocode($coord[0], $coord[1]);
			$this->assertTrue($is);

			$is = $this->Geocode->getResult();
			$this->assertTrue(!empty($is));
			//debug($is);
			$address = isset($is[0]) ? $is[0]['formatted_address'] : $is['formatted_address'];
			$this->assertTextContains($coord[2], $address);
		}
	}

	/**
	 * Seems to return
	 * - 'Bibersfelder Besen Weinstube, Luckenbacher Straße 1, 74523 Schwäbisch Hall, Deutschland'
	 *	 - point_of_interest, school, establishment
	 * - 'Bibersfeld, 74523 Schwäbisch Hall, Deutschland'
	 *	 - sublocality, political
	 *
	 * @return void
	 */
	public function testGeocodeInconclusive() {
		$address = 'Bibersfeld';

		if (!$this->isDebug()) {
			$this->_getMock('inconclusive', 2);
		}

		$this->Geocode->setOptions(['allow_inconclusive' => true, 'min_accuracy' => GeocodeLib::ACC_POSTAL]);
		$is = $this->Geocode->geocode($address);
		$this->assertTrue($is);
		$res = $this->Geocode->getResult();
		$this->assertNotEmpty($res);

		$is = $this->Geocode->isInconclusive();
		$this->assertFalse($is);

		// Fake inconclusive here by adding an additional type
		$this->Geocode->accuracyTypes[99] = 'point_of_interest';
		$this->Geocode->setOptions(['allow_inconclusive' => false]);
		$is = $this->Geocode->geocode($address);
		$this->assertFalse($is);

		$is = $this->Geocode->isInconclusive();
		$this->assertTrue($is);

		$res = $this->Geocode->getResult();
		$this->assertSame(2, $res['valid_results']);
	}

	/**
	 * GeocodeLibTest::testInvalid()
	 *
	 * @return void
	 */
	public function testInvalid() {
		// Dont mock in debug mode (live query), otherwise mock it out
		if (!$this->isDebug()) {
			$this->_getMock('invalid');
		}

		$this->Geocode->setOptions(['allow_inconclusive' => false]);
		$result = $this->Geocode->geocode('204 HWY 287 SOUTH, CACTUS, TX, 79013');
		$this->assertFalse($result);
	}

	/**
	 * With lower min accuracy
	 *
	 * @return void
	 */
	public function testGeocodeInconclusiveMinAccuracy() {
		$address = 'Bibersfeld';
		if (!$this->isDebug()) {
			$this->_getMock('inconclusive');
		}

		$this->Geocode->setOptions(['allow_inconclusive' => true, 'min_accuracy' => GeocodeLib::ACC_STREET]);
		$is = $this->Geocode->geocode($address);
		$this->assertFalse($is);
	}

	/**
	 * Seems to return
	 * - 'Bibersfelder Besen Weinstube, Luckenbacher Straße 1, 74523 Schwäbisch Hall, Deutschland'
	 *	 - point_of_interest, school, establishment
	 * - 'Bibersfeld, 74523 Schwäbisch Hall, Deutschland'
	 *	 - sublocality, political
	 *
	 * @return void
	 */
	public function testGeocodeExpect() {
		$address = 'Bibersfeld';
		if (!$this->isDebug()) {
			$this->_getMock('inconclusive');
		}

		$this->Geocode->setOptions([
			'allow_inconclusive' => true,
			'expect' => [GeocodeLib::ACC_POSTAL, GeocodeLib::ACC_LOC, GeocodeLib::ACC_SUBLOC]]);
		$is = $this->Geocode->geocode($address);
		$this->assertTrue($is);

		$this->Geocode->setOptions([
			'allow_inconclusive' => true,
			'expect' => [GeocodeLib::ACC_POSTAL, GeocodeLib::ACC_LOC]]);
		$is = $this->Geocode->geocode($address);
		$this->assertFalse($is);
	}

	/**
	 * GeocodeLibTest::testDistance()
	 *
	 * @return void
	 */
	public function testDistance() {
		$coords = [
			['name' => 'MUC/Pforzheim (269km road, 2:33h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 48.8934, 'lng' => 8.70492], 'd' => 228],
			['name' => 'MUC/London (1142km road, 11:20h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 51.508, 'lng' => -0.124688], 'd' => 919],
			['name' => 'MUC/NewYork (--- road, ---h)', 'x' => ['lat' => 48.1391, 'lng' => 11.5802], 'y' => ['lat' => 40.700943, 'lng' => -73.853531], 'd' => 6479]
		];

		foreach ($coords as $coord) {
			$is = $this->Geocode->distance($coord['x'], $coord['y']);
			$this->assertEquals($coord['d'], $is);
		}

		$is = $this->Geocode->distance($coords[0]['x'], $coords[0]['y'], GeocodeLib::UNIT_MILES);
		$this->assertEquals(142, $is);

		// String directly
		$is = $this->Geocode->distance($coords[0]['x'], $coords[0]['y'], 'F');
		$this->assertEquals(747236, $is);
	}

	/**
	 * GeocodeLibTest::testBlur()
	 *
	 * @return void
	 */
	public function testBlur() {
		$coords = [
			[48.1391, 1, 0.002], //'y'=>array('lat'=>48.8934, 'lng'=>8.70492), 'd'=>228),
			[11.5802, 1, 0.002],
		];
		foreach ($coords as $coord) {
			$is = $this->Geocode->blur($coord[0], $coord[1]);
			//pr('is: '.$is.' - expected: '.$coord[0].' +- '.$coord[2]);
			$this->assertWithinMargin($is, $coord[0], $coord[2]);
			$this->assertNotWithinMargin($is, $coord[0], $coord[2] / 4);
		}
	}

	/**
	 * GeocodeLibTest::testConvert()
	 *
	 * @return void
	 */
	public function testConvert() {
		$values = [
			[3, 'M', 'K', 4.828032],
			[3, 'K', 'M', 1.86411358],
			[100000, 'I', 'K', 2.54],
		];
		foreach ($values as $value) {
			$is = $this->Geocode->convert($value[0], $value[1], $value[2]);
			$this->assertEquals($value[3], round($is, 8));
		}
	}

	/**
	 * GeocodeLibTest::testUrl()
	 *
	 * @return void
	 */
	public function testUrl() {
		$ReflectionClass = new ReflectionClass('GeocodeLib');
		$Method = $ReflectionClass->getMethod('_url');
		$Method->setAccessible(true);

		$is = $Method->invoke($this->Geocode);
		$this->assertPattern('#https://maps.googleapis.com/maps/api/geocode/json#', $is);
	}

	/**
	 * GeocodeLibTest::testSetParams()
	 *
	 * @return void
	 */
	public function testSetParams() {
	}

	/**
	 * GeocodeLibTest::testSetOptions()
	 *
	 * @return void
	 */
	public function testSetOptions() {
		$this->Geocode->setOptions(['host' => 'maps.google.it']);

		// should now be ".it"
		$ReflectionClass = new ReflectionClass('GeocodeLib');
		$Method = $ReflectionClass->getMethod('_url');
		$Method->setAccessible(true);

		$result = $Method->invoke($this->Geocode);
		$this->assertTextContains('maps.google.it', $result);
	}

	/**
	 * GeocodeLibTest::testGeocode()
	 *
	 * @return void
	 */
	public function testGeocode() {
		if (!$this->isDebug()) {
			$this->_getMock('geocode0');
		}

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

		if (!$this->isDebug()) {
			$this->_getMock('geocode1');
		}

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

		if (!$this->isDebug()) {
			$this->_getMock('geocode2');
		}

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

	/**
	 * GeocodeLibTest::testGeocodeReachedQueryLimit()
	 *
	 * @return void
	 */
	public function testGeocodeReachedQueryLimit() {
		$this->Geocode->reachedQueryLimit = true;
		$address = 'Oranienburger Straße 87, 10178 Berlin, Deutschland';
		$result = $this->Geocode->geocode($address);
		$this->assertFalse($result);

		$result = $this->Geocode->error();
		$this->assertEquals('Over Query Limit - abort', $result);
	}

	/**
	 * GeocodeLibTest::testGeocodeBadApiKey()
	 *
	 * @return void
	 */
	public function testGeocodeBadApiKey() {
		if (!$this->isDebug()) {
			$this->_getMock('apikey');
		}

		$address = 'Oranienburger Straße 87, 10178 Berlin, Deutschland';
		$result = $this->Geocode->geocode($address, ['sensor' => false, 'key' => 'testingBadApiKey']);
		$this->assertFalse($result);

		$result = $this->Geocode->error();
		$this->assertEquals('Error REQUEST_DENIED (The provided API key is invalid.)', $result);
	}

	/**
	 * GeocodeLibTest::testGeocodeInvalid()
	 *
	 * @return void
	 */
	public function testGeocodeInvalid() {
		if (!$this->isDebug()) {
			$this->_getMock('zero');
		}

		$address = 'Hjfjosdfhosj, 78878 Mdfkufsdfk';
		$result = $this->Geocode->geocode($address);
		$this->assertFalse($result);

		$result = $this->Geocode->error();
		$this->assertTrue(!empty($result));
	}

	/**
	 * GeocodeLibTest::testGetMaxAddress()
	 *
	 * @return void
	 */
	public function testGetMaxAddress() {
		$ReflectionClass = new ReflectionClass('GeocodeLib');
		$Method = $ReflectionClass->getMethod('_getMaxAccuracy');
		$Method->setAccessible(true);

		$result = $Method->invoke($this->Geocode, ['street_address' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_STREET, $result);

		$result = $Method->invoke($this->Geocode, ['intersection' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_INTERSEC, $result);

		$result = $Method->invoke($this->Geocode, ['route' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_ROUTE, $result);

		$result = $Method->invoke($this->Geocode, ['sublocality' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_SUBLOC, $result);

		$result = $Method->invoke($this->Geocode, ['locality' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_LOC, $result);

		$result = $Method->invoke($this->Geocode, ['postal_code' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_POSTAL, $result);

		$result = $Method->invoke($this->Geocode, ['country' => 'abc']);
		$this->assertSame(GeocodeLib::ACC_COUNTRY, $result);

		$result = $Method->invoke($this->Geocode, []);
		$this->assertSame(null, $result);

		// mixed
		$result = $Method->invoke($this->Geocode, [
			'country' => 'aa',
			'postal_code' => 'abc',
			'locality' => '',
			'street_address' => '',
		]);
		$this->assertSame(GeocodeLib::ACC_POSTAL, $result);
	}

	/**
	 * GeocodeLibTest::testGeocodeMinAcc()
	 *
	 * @return void
	 */
	public function testGeocodeMinAcc() {
		if (!$this->isDebug()) {
			$this->_getMock('minacc');
		}

		// address = postal_code, minimum = street level
		$address = 'Deutschland';

		$this->Geocode->setOptions(['min_accuracy' => GeocodeLib::ACC_STREET]);
		$is = $this->Geocode->geocode($address);
		$this->assertFalse($is);
		$is = $this->Geocode->error();
		$this->assertTrue(!empty($is));
	}

	/**
	 * GeocodeLibTest::testTransformData()
	 *
	 * @return void
	 */
	public function testTransformData() {
		$ReflectionClass = new ReflectionClass('GeocodeLib');
		$Method = $ReflectionClass->getMethod('_transformData');
		$Method->setAccessible(true);

		// non-full records
		$data = ['types' => []];
		$this->assertEquals($data, $Method->invoke($this->Geocode, $data));
		$data = [];
		$this->assertEquals($data, $Method->invoke($this->Geocode, $data));

		// Full record
		$ReflectionClass = new ReflectionClass('GeocodeLib');
		$Method = $ReflectionClass->getMethod('_transform');
		$Method->setAccessible(true);
		$data = json_decode($this->apiMockupReverseGeocode40206['raw'], true);
		$expected = [
			'results' => [
				[
					'formatted_address' => 'Louisville, KY 40206, USA',
					// organized location components
					'country' => 'United States',
					'country_code' => 'US',
					'country_province' => 'Kentucky',
					'country_province_code' => 'KY',
					'postal_code' => '40206',
					'locality' => 'Louisville',
					'sublocality' => '',
					'route' => '',
					// vetted "types"
					'types' => [
						'postal_code',
					],
					// simple lat/lng
					'lat' => 38.264357800000013,
					'lng' => -85.699978899999991,
					'location_type' => 'APPROXIMATE',
					'viewport' => [
						'sw' => [
							'lat' => 38.239565800000001,
							'lng' => -85.744800999999995,
						],
						'ne' => [
							'lat' => 38.285255800000002,
							'lng' => -85.664309000000003,
						],
					],
					'bounds' => [
						'sw' => [
							'lat' => 38.239565800000001,
							'lng' => -85.744800999999995,
						],
						'ne' => [
							'lat' => 38.285255800000002,
							'lng' => -85.664309000000003,
						],
					],
					'address_components' => [
							[
								'long_name' => '40206',
								'short_name' => '40206',
								'types' => [
									'postal_code',
								],
							],
							[
								'long_name' => 'Louisville',
								'short_name' => 'Louisville',
								'types' => [
									'locality',
									'political',
								],
							],
							[
								'long_name' => 'Kentucky',
								'short_name' => 'KY',
								'types' => [
									'administrative_area_level_1',
									'political',
								],
							],
							[
								'long_name' => 'United States',
								'short_name' => 'US',
								'types' => [
									'country',
									'political',
								],
							],
						],
						'valid_type' => true,
						'accuracy' => 4,
						'accuracy_name' => 'postal_code',
				],
			],
			'status' => 'OK',
		];
		$result = $Method->invoke($this->Geocode, $data);

		$this->assertEquals($expected, $result);
	}

	protected function _getMock($type, $count = 1) {
		$this->Geocode->HttpSocket = $this->getMock('HttpSocket', ['get']);
		$responseContent = file_get_contents($this->mockFilePath . $type . '.json');
		$response = new HttpSocketResponse();
		$response->body = $responseContent;
		$response->code = 200;
		$this->Geocode->HttpSocket->response = $response;
		for ($i = 0; $i < $count; $i++) {
			$this->Geocode->HttpSocket->expects($this->at($i))
				->method('get')
				->will($this->returnValue($response));
		}
	}

}
