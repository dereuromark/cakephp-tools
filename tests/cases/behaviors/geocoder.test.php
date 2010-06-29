<?php

App::import('Behavior', 'Tools.Geocoder');

class GeocoderTestCase extends CakeTestCase {

	var $fixtures = array(
		'core.comment'
	);


	function startTest() {
		$this->Comment =& ClassRegistry::init('Comment');


		$this->Comment->Behaviors->attach('Geocoder', array('real'=>false));
	}

	function testBasic() {
		// accuracy >= 5
		$data = array(
			'street' => 'Krebenweg 2',
			'zip' => '74523',
			'city' => 'Bibersfeld'
		);
		$res = $this->Comment->save($data);
		echo returns($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));
		// accuracy = 4
		$data = array(
	 		//'street' => 'Leopoldstraße',
			'city' => 'München'
		);
		$res = $this->Comment->save($data);
		echo returns($this->Comment->Behaviors->Geocoder->Geocode->error()).BR;

		echo returns($res);
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));

	}

	function testMinAccLow() {
		$this->Comment->Behaviors->detach('Geocoder');
		$this->Comment->Behaviors->attach('Geocoder', array('real'=>false, 'min_accuracy'=>0));
		// accuracy = 1
		$data = array(
	 		//'street' => 'Leopoldstraße',
			'city' => 'Deutschland'
		);
		$res = $this->Comment->save($data);
		echo returns($this->Comment->Behaviors->Geocoder->Geocode->error()).BR;

		echo returns($res);

		//echo returns($this->Comment->Behaviors->Geocoder->Geocode->debug());
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));

	}

	function testMinAccHigh() {
		$this->Comment->Behaviors->detach('Geocoder');
		$this->Comment->Behaviors->attach('Geocoder', array('real'=>false, 'min_accuracy'=>4));
		// accuracy = 1
		$data = array(
	 		//'street' => 'Leopoldstraße',
			'city' => 'Deutschland'
		);
		$res = $this->Comment->save($data);
		echo returns($this->Comment->Behaviors->Geocoder->Geocode->error()).BR;

		echo returns($res);

		//echo returns($this->Comment->Behaviors->Geocoder->Geocode->debug());
		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));

	}


	function testMinInc() {
		$this->Comment->Behaviors->detach('Geocoder');
		$this->Comment->Behaviors->attach('Geocoder', array('real'=>false, 'min_accuracy'=>4));
		// accuracy = 1
		$data = array(
	 		//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$res = $this->Comment->save($data);
		echo returns($this->Comment->Behaviors->Geocoder->Geocode->error()).BR;

		echo returns($this->Comment->Behaviors->Geocoder->Geocode->getResult()).BR;

		echo returns($res);

		//echo returns($this->Comment->Behaviors->Geocoder->Geocode->debug());
		$this->assertTrue(!isset($res['Comment']['lat']) && !isset($res['Comment']['lng']));

	}

	function testMinIncAllowed() {
		$this->Comment->Behaviors->detach('Geocoder');
		$this->Comment->Behaviors->attach('Geocoder', array('real'=>false, 'allow_inconclusive'=>true));
		// accuracy = 1
		$data = array(
	 		//'street' => 'Leopoldstraße',
			'city' => 'Neustadt'
		);
		$res = $this->Comment->save($data);
		echo returns($this->Comment->Behaviors->Geocoder->Geocode->error()).BR;

		echo returns($this->Comment->Behaviors->Geocoder->Geocode->url()).BR;

		echo returns($this->Comment->Behaviors->Geocoder->Geocode->getResult()).BR;

		echo returns($res);

		//echo returns($this->Comment->Behaviors->Geocoder->Geocode->debug());
		$this->assertTrue(!empty($res['Comment']['lat']) && !empty($res['Comment']['lng']));

	}


}



