<?php
App::uses('GeocoderBehavior', 'Tools.Model/Behavior');
App::uses('Set', 'Utility');
App::uses('AppModel', 'Model');
App::uses('AppController', 'Controller');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class GeocoderBehaviorTest extends MyCakeTestCase {

	public $fixtures = array(
		'core.comment', 'plugin.tools.address', 'core.cake_session'
	);

	public function setUp() {
		parent::setUp();

		$this->Comment = ClassRegistry::init('Comment');

		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false));
	}

	/**
	 * GeocoderBehaviorTest::testDistance()
	 *
	 * @return void
	 */
	public function testDistance() {
		$res = $this->Comment->distance(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comment.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comment.lat)) * SIN(PI()/2 - RADIANS(90 - 12)))';
		$this->assertEquals($expected, $res);

		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comment->distance(12.1, 14.2);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.x)) * COS(PI()/2 - RADIANS(90 - 12.1)) * COS(RADIANS(Comment.y) - RADIANS(14.2)) + SIN(PI()/2 - RADIANS(90 - Comment.x)) * SIN(PI()/2 - RADIANS(90 - 12.1)))';
		$this->assertEquals($expected, $res);

		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('lat' => 'x', 'lng' => 'y'));
		$res = $this->Comment->distance('User.lat', 'User.lng');
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.x)) * COS(PI()/2 - RADIANS(90 - User.lat)) * COS(RADIANS(Comment.y) - RADIANS(User.lng)) + SIN(PI()/2 - RADIANS(90 - Comment.x)) * SIN(PI()/2 - RADIANS(90 - User.lat)))';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testDistanceField()
	 *
	 * @return void
	 */
	public function testDistanceField() {
		$res = $this->Comment->distanceField(12, 14);
		$expected = '6371.04 * ACOS(COS(PI()/2 - RADIANS(90 - Comment.lat)) * COS(PI()/2 - RADIANS(90 - 12)) * COS(RADIANS(Comment.lng) - RADIANS(14)) + SIN(PI()/2 - RADIANS(90 - Comment.lat)) * SIN(PI()/2 - RADIANS(90 - 12))) AS Comment.distance';
		$this->assertEquals($expected, $res);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualField()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualField() {
		$this->Address = ClassRegistry::init('Address');
		$this->Address->Behaviors->load('Tools.Geocoder');
		$this->Address->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Address->find('all', $options);
		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
		$this->assertTrue($res[1]['Address']['distance'] < $res[2]['Address']['distance']);
		$this->assertTrue($res[0]['Address']['distance'] > 640 && $res[0]['Address']['distance'] < 650);
	}

	/**
	 * GeocoderBehaviorTest::testSetDistanceAsVirtualFieldInMiles()
	 *
	 * @return void
	 */
	public function testSetDistanceAsVirtualFieldInMiles() {
		$this->Address = ClassRegistry::init('Address');
		$this->Address->Behaviors->load('Tools.Geocoder', array('unit' => GeocodeLib::UNIT_MILES));
		$this->Address->setDistanceAsVirtualField(13.3, 19.2);
		$options = array('order' => array('Address.distance' => 'ASC'));
		$res = $this->Address->find('all', $options);
		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
		$this->assertTrue($res[1]['Address']['distance'] < $res[2]['Address']['distance']);
		$this->assertTrue($res[0]['Address']['distance'] > 390 && $res[0]['Address']['distance'] < 410);
	}

	/**
	 * GeocoderBehaviorTest::testPagination()
	 *
	 * @return void
	 */
	public function testPagination() {
		$this->Controller = new TestController(new CakeRequest(null, false), null);
		$this->Controller->constructClasses();
		$this->Controller->Address->Behaviors->load('Tools.Geocoder');
		$this->Controller->Address->setDistanceAsVirtualField(13.3, 19.2);
		$this->Controller->paginate = array(
			'conditions' => array('distance <' => 3000),
			'order' => array('distance' => 'ASC')
		);
		$res = $this->Controller->paginate();
		$this->assertEquals(2, count($res));
		$this->assertTrue($res[0]['Address']['distance'] < $res[1]['Address']['distance']);
	}

	/**
	 * GeocoderBehaviorTest::testValidate()
	 *
	 * @return void
	 */
	public function testValidate() {
		$is = $this->Comment->validateLatitude(44);
		$this->assertTrue($is);

		$is = $this->Comment->validateLatitude(110);
		$this->assertFalse($is);

		$is = $this->Comment->validateLongitude(150);
		$this->assertTrue($is);

		$is = $this->Comment->validateLongitude(-190);
		$this->assertFalse($is);

		$this->db = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->db instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$this->Comment->validator()->add('lat', 'validateLatitude', array('rule' => 'validateLatitude', 'message' => 'validateLatitudeError'));
		$this->Comment->validator()->add('lng', 'validateLongitude', array('rule' => 'validateLongitude', 'message' => 'validateLongitudeError'));
		$data = array(
			'lat' => 44,
			'lng' => 190,
		);
		$this->Comment->set($data);
		$res = $this->Comment->validates();
		$this->assertFalse($res);
		$expectedErrors = array(
			'lng' => array(__('validateLongitudeError'))
		);
		$this->assertEquals($expectedErrors, $this->Comment->validationErrors);
	}

	/**
	 * Geocoding tests using the google webservice
	 *
	 * @return void
	 */
	public function testBasic() {
		$this->db = ConnectionManager::getDataSource('test');
		$this->skipIf(!($this->db instanceof Mysql), 'The virtualFields test is only compatible with Mysql.');

		$data = array(
			'street' => 'Krebenweg 22',
			'zip' => '74523',
			'city' => 'Bibersfeld'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']) && round($res['Comment']['lat']) === 49.0 && round($res['Comment']['lng']) === 10.0);

		// inconclusive
		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'München'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertEquals('', $this->Comment->Behaviors->Geocoder->Geocode->error());

		//debug($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
		$this->assertEquals('München, Deutschland', $res['Comment']['geocoder_result']['formatted_address']);

		$data = array(
			'city' => 'Bibersfeld'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->debug($res);
		$this->assertTrue(!empty($res));
		$this->assertEquals('', $this->Comment->Behaviors->Geocoder->Geocode->error());
	}

	/**
	 * GeocoderBehaviorTest::testMinAccLow()
	 *
	 * @return void
	 */
	public function testMinAccLow() {
		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false, 'min_accuracy' => GeocodeLib::ACC_COUNTRY));
		$data = array(
			'city' => 'Deutschland'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue((int)$res['Comment']['lat'] && (int)$res['Comment']['lng']);
	}

	/**
	 * GeocoderBehaviorTest::testMinAccHigh()
	 *
	 * @return void
	 */
	public function testMinAccHigh() {
		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false, 'min_accuracy' => GeocodeLib::ACC_POSTAL));
		$data = array(
			'city' => 'Deutschland'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinInc()
	 *
	 * @return void
	 */
	public function testMinInc() {
		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false, 'min_accuracy' => GeocodeLib::ACC_SUBLOC));

		$this->assertEquals(GeocodeLib::ACC_SUBLOC, $this->Comment->Behaviors->Geocoder->settings['Comment']['min_accuracy']);

		$data = array(
			//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);

		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testMinIncAllowed()
	 *
	 * @return void
	 */
	public function testMinIncAllowed() {
		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false, 'allow_inconclusive' => true));

		$data = array(
			'city' => 'Neustadt'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);

		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
	}

	/**
	 * GeocoderBehaviorTest::testExpect()
	 *
	 * @return void
	 */
	public function testExpect() {
		$this->Comment->Behaviors->unload('Geocoder');
		$this->Comment->Behaviors->load('Tools.Geocoder', array('real' => false, 'expect' => array('postal_code')));

		$data = array(
			'city' => 'Bibersfeld'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(empty($res['Comment']['lat']) && empty($res['Comment']['lng']));

		$data = array(
			'city' => '74523'
		);
		$this->Comment->create();
		$res = $this->Comment->save($data);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
	}

}

class TestController extends AppController {

	public $uses = array('Address');

}
