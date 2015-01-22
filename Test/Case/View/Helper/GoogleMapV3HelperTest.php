<?php

App::uses('GoogleMapV3Helper', 'Tools.View/Helper');
App::uses('MyCakeTestCase', 'Tools.TestSuite');
App::uses('View', 'View');

class GoogleMapV3HelperTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		Configure::delete('Google');

		$this->GoogleMapV3 = new GoogleMapV3Helper(new View(null));
	}

	public function testObject() {
		$this->assertInstanceOf('GoogleMapV3Helper', $this->GoogleMapV3);
	}

	/**
	 * GoogleMapV3HelperTest::testConfigMerge()
	 *
	 * @return void
	 */
	public function testConfigMerge() {
		$config = [
			'map' => [
				'type' => 'foo',
			]
		];
		Configure::write('Google.zoom', 8);
		$this->GoogleMapV3 = new GoogleMapV3Helper(new View(null), $config);

		$result = $this->GoogleMapV3->settings;
		$this->assertEquals('foo', $result['map']['type']);
		$this->assertEquals(8, $result['map']['zoom']);
	}

	/**
	 * GoogleMapV3HelperTest::testMapUrl()
	 *
	 * @return void
	 */
	public function testMapUrl() {
		$url = $this->GoogleMapV3->mapUrl(['to' => 'Munich, Germany']);
		$this->assertEquals('http://maps.google.com/maps?daddr=Munich%2C+Germany', $url);

		$url = $this->GoogleMapV3->mapUrl(['to' => '<München>, Germany']);
		$this->assertEquals('http://maps.google.com/maps?daddr=%3CM%C3%BCnchen%3E%2C+Germany', $url);
	}

	/**
	 * GoogleMapV3HelperTest::testMapLink()
	 *
	 * @return void
	 */
	public function testMapLink() {
		$result = $this->GoogleMapV3->mapLink('<To Munich>!', ['to' => '<Munich>, Germany']);
		$expected = '<a href="http://maps.google.com/maps?daddr=%3CMunich%3E%2C+Germany">&lt;To Munich&gt;!</a>';
		//echo $result;
		$this->assertEquals($expected, $result);
	}

	/**
	 * GoogleMapV3HelperTest::testLinkWithMapUrl()
	 *
	 * @return void
	 */
	public function testLinkWithMapUrl() {
		$url = $this->GoogleMapV3->mapUrl(['to' => '<München>, Germany']);
		$result = $this->GoogleMapV3->Html->link('Some title', $url);
		$expected = '<a href="http://maps.google.com/maps?daddr=%3CM%C3%BCnchen%3E%2C+Germany">Some title</a>';
		//echo $result;
		$this->assertEquals($expected, $result);
	}

	/**
	 * GoogleMapV3HelperTest::testStaticPaths()
	 *
	 * @return void
	 */
	public function testStaticPaths() {
		$m = $this->pathElements = [
			[
				'path' => ['Berlin', 'Stuttgart'],
				'color' => 'green',
			],
			[
				'path' => ['44.2,11.1', '43.1,12.2', '44.3,11.3', '43.3,12.3'],
			],
			[
				'path' => [['lat' => '48.1', 'lng' => '11.1'], ['lat' => '48.4', 'lng' => '11.2']], //'Frankfurt'
				'color' => 'red',
				'weight' => 10
			]
		];

		$is = $this->GoogleMapV3->staticPaths($m);
		//echo pr(h($is));

		$options = [
			'paths' => $is
		];
		$is = $this->GoogleMapV3->staticMapLink('My Title', $options);
		//echo h($is).BR.BR;

		$is = $this->GoogleMapV3->staticMap($options);
		//echo $is;
	}

	/**
	 * GoogleMapV3HelperTest::testStaticMarkers()
	 *
	 * @return void
	 */
	public function testStaticMarkers() {
		$m = $this->markerElements = [
			[
				'address' => '44.3,11.2',
			],
			[
				'address' => '44.2,11.1',
			]
		];
		$is = $this->GoogleMapV3->staticMarkers($m, ['color' => 'red', 'char' => 'C', 'shadow' => 'false']);
		//debug($is);

		$options = [
			'markers' => $is
		];
		$is = $this->GoogleMapV3->staticMap($options);
		//debug($is);
		//echo $is;
	}

//	http://maps.google.com/staticmap?size=500x500&maptype=hybrid&markers=color:red|label:S|48.3,11.2&sensor=false
//	http://maps.google.com/maps/api/staticmap?size=512x512&maptype=roadmap&markers=color:blue|label:S|40.702147,-74.015794&markers=color:green|label:G|40.711614,-74.012318&markers=color:red|color:red|label:C|40.718217,-73.998284&sensor=false

	/**
	 * GoogleMapV3HelperTest::testStatic()
	 *
	 * @return void
	 */
	public function testStatic() {
		//echo '<h2>StaticMap</h2>';
		$m = [
			[
				'address' => 'Berlin',
				'color' => 'yellow',
				'char' => 'Z',
				'shadow' => 'true'
			],
			[
				'lat' => '44.2',
				'lng' => '11.1',
				'color' => '#0000FF',
				'char' => '1',
				'shadow' => 'false'
			]
		];

		$options = [
			'markers' => $this->GoogleMapV3->staticMarkers($m)
		];
		//debug($options['markers']).BR;

		$is = $this->GoogleMapV3->staticMapUrl($options);
		//echo h($is);
		//echo BR.BR;

		$is = $this->GoogleMapV3->staticMapLink('MyLink', $options);
		//echo h($is);
		//echo BR.BR;

		$is = $this->GoogleMapV3->staticMap($options);
		//echo h($is).BR;
		//echo $is;
		//echo BR.BR;

		$options = [
			'size' => '200x100',
			'center' => true
		];
		$is = $this->GoogleMapV3->staticMapLink('MyTitle', $options);
		//echo h($is);
		//echo BR.BR;
		$attr = [
			'title' => '<b>Yeah!</b>'
		];
		$is = $this->GoogleMapV3->staticMap($options, $attr);
		//echo h($is).BR;
		//echo $is;
		//echo BR.BR;

		$pos = [
			['lat' => 48.1, 'lng' => '11.1'],
			['lat' => 48.2, 'lng' => '11.2'],
		];
		$options = [
			'markers' => $this->GoogleMapV3->staticMarkers($pos)
		];

		$attr = ['url' => $this->GoogleMapV3->mapUrl(['to' => 'Munich, Germany'])];
		$is = $this->GoogleMapV3->staticMap($options, $attr);
		//echo h($is).BR;
		//echo $is;

		//echo BR.BR.BR;

		$url = $this->GoogleMapV3->mapUrl(['to' => 'Munich, Germany']);
		$attr = [
			'title' => 'Yeah'
		];
		$image = $this->GoogleMapV3->staticMap($options, $attr);
		$link = $this->GoogleMapV3->Html->link($image, $url, ['escape' => false, 'target' => '_blank']);
		//echo h($link).BR;
		//echo $link;
	}

	/**
	 * GoogleMapV3HelperTest::testStaticMapWithStaticMapLink()
	 *
	 * @return void
	 */
	public function testStaticMapWithStaticMapLink() {
		//echo '<h2>testStaticMapWithStaticMapLink</h2>';
		$markers = [];
		$markers[] = ['lat' => 48.2, 'lng' => 11.1, 'color' => 'red'];
		$mapMarkers = $this->GoogleMapV3->staticMarkers($markers);

		$staticMapUrl = $this->GoogleMapV3->staticMapUrl(['center' => 48 . ',' . 11, 'markers' => $mapMarkers, 'size' => '640x510', 'zoom' => 6]);
		//echo $this->GoogleMapV3->Html->link('Open Static Map', $staticMapUrl, array('class'=>'staticMap', 'title'=>__d('tools', 'click for full map'))); //, 'escape'=>false

	}

	/**
	 * GoogleMapV3HelperTest::testMarkerIcons()
	 *
	 * @return void
	 */
	public function testMarkerIcons() {
		$tests = [
			['green', null],
			['black', null],
			['purple', 'E'],
			['', 'Z'],
		];
		foreach ($tests as $test) {
			$is = $this->GoogleMapV3->iconSet($test[0], $test[1]);
			//echo $this->GoogleMapV3->Html->image($is['url']).BR;
		}
	}

	/**
	 * Test some basic map options
	 */
	public function testMap() {
		$options = [
			'autoScript' => true,
			'inline' => true,
		];

		$result = $this->GoogleMapV3->map($options);

		$result .= $this->GoogleMapV3->script();

		$expected = '<div id="map_canvas" class="map"';
		$this->assertTextContains($expected, $result);

		$expected = '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false';
		$this->assertTextContains($expected, $result);

		$expected = 'var map0 = new google.maps.Map(document.getElementById("map_canvas"), myOptions);';
		$this->assertTextContains($expected, $result);
	}

	/**
	 * GoogleMapV3HelperTest::testMarker() and draggable property
	 *
	 * @return void
	 */
	public function testMarker() {
		$this->GoogleMapV3->map();
		$this->GoogleMapV3->addMarker([
				'lat' => 48.69847, 'lng' => 10.9514,
				'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>',
				'draggable' => true]);

		$result = $this->GoogleMapV3->script();
		$this->assertContains('draggable: true,', $result);

		$this->GoogleMapV3->map(['marker' => ['draggable' => true]]);
		$this->GoogleMapV3->addMarker([
				'lat' => 48.69847, 'lng' => 10.9514,
				'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>']);

		$result = $this->GoogleMapV3->script();
		$this->assertContains('draggable: true,', $result);

		$this->GoogleMapV3->map(['marker' => ['draggable' => true]]);
		$this->GoogleMapV3->addMarker([
				'lat' => 48.69847, 'lng' => 10.9514,
				'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>',
				'draggable' => false]);

		$result = $this->GoogleMapV3->script();
		$this->assertNotContains('draggable: true,', $result);
	}

	/**
	 * With default options
	 */
	public function testDynamic() {
		//echo '<h2>Map 1</h2>';
		//echo '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>';
		//echo $this->GoogleMapV3->map($defaul, array('style'=>'width:100%; height: 800px'));
		//echo '<script type="text/javascript" src="'.$this->GoogleMapV3->apiUrl().'"></script>';
		//echo '<script type="text/javascript" src="'.$this->GoogleMapV3->gearsUrl().'"></script>';

		$options = [
			'zoom' => 6,
			'type' => 'R',
			'geolocate' => true,
			'div' => ['id' => 'someothers'],
			'map' => ['navOptions' => ['style' => 'SMALL'], 'typeOptions' => ['style' => 'HORIZONTAL_BAR', 'pos' => 'RIGHT_CENTER']]
		];
		$result = $this->GoogleMapV3->map($options);
		$this->GoogleMapV3->addMarker(['lat' => 48.69847, 'lng' => 10.9514, 'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>', 'icon' => $this->GoogleMapV3->iconSet('green', 'E')]);

		$this->GoogleMapV3->addMarker(['lat' => 47.69847, 'lng' => 11.9514, 'title' => 'Marker2', 'content' => 'Some more Html-<b>Content</b>']);

		$this->GoogleMapV3->addMarker(['lat' => 47.19847, 'lng' => 11.1514, 'title' => 'Marker3']);

		/*
		$options = array(
		'lat'=>48.15144,
		'lng'=>10.198,
		'content'=>'Thanks for using this'
	);
		$this->GoogleMapV3->addInfoWindow($options);
		//$this->GoogleMapV3->addEvent();
		*/

		$result .= $this->GoogleMapV3->script();

		//echo $result;
	}

	/**
	 * More than 100 markers and it gets reaaally slow...
	 */
	public function testDynamic2() {
		//echo '<h2>Map 2</h2>';
		$options = [
			'zoom' => 6, 'type' => 'H',
			'autoCenter' => true,
			'div' => ['id' => 'someother'], //'height'=>'111',
			'map' => ['typeOptions' => ['style' => 'DROPDOWN_MENU']]
		];
		//echo $this->GoogleMapV3->map($options);
		$this->GoogleMapV3->addMarker(['lat' => 47.69847, 'lng' => 11.9514, 'title' => 'MarkerMUC', 'content' => 'Some more Html-<b>Content</b>']);

		for ($i = 0; $i < 100; $i++) {
			$lat = mt_rand(46000, 54000) / 1000;
			$lng = mt_rand(2000, 20000) / 1000;
			$this->GoogleMapV3->addMarker(['id' => 'm' . ($i + 1), 'lat' => $lat, 'lng' => $lng, 'title' => 'Marker' . ($i + 1), 'content' => 'Lat: <b>' . $lat . '</b><br>Lng: <b>' . $lng . '</b>', 'icon' => 'http://google-maps-icons.googlecode.com/files/home.png']);
		}

		$js = "$('.mapAnchor').live('click', function() {
		var id = $(this).attr('rel');

		var match = matching[id];

		/*
		map.panTo(mapPoints[match]);
		mapMarkers[match].openInfoWindowHtml(mapWindows[match]);
		*/

		gInfoWindows1[0].setContent(gWindowContents1[match]);
		gInfoWindows1[0].open(map1, gMarkers1[match]);
	});";

		$this->GoogleMapV3->addCustom($js);

		//echo $this->GoogleMapV3->script();

		//echo '<a href="javascript:void(0)" class="mapAnchor" rel="m2">Marker2</a> ';
		//echo '<a href="javascript:void(0)" class="mapAnchor" rel="m3">Marker3</a>';
	}

	public function testDynamic3() {
		//echo '<h2>Map with Directions</h2>';
		$options = [
			'zoom' => 5,
			'type' => 'H',
			'map' => []
		];
		//echo $this->GoogleMapV3->map($options);

		$this->GoogleMapV3->addMarker(['lat' => 48.69847, 'lng' => 10.9514, 'content' => '<b>Bla</b>', 'title' => 'NoDirections']);

		$this->GoogleMapV3->addMarker(['lat' => 47.69847, 'lng' => 11.9514, 'title' => 'AutoToDirections', 'content' => '<b>Bla</b>', 'directions' => true]);

		$this->GoogleMapV3->addMarker(['lat' => 46.69847, 'lng' => 11.9514, 'title' => 'ManuelToDirections', 'content' => '<b>Bla</b>', 'directions' => ['to' => 'Munich, Germany']]);

		$this->GoogleMapV3->addMarker(['lat' => 45.69847, 'lng' => 11.9514, 'title' => 'ManuelFromDirections', 'content' => '<b>Bla</b>', 'directions' => ['from' => 'Munich, Germany']]);

		//echo $this->GoogleMapV3->script();
	}
}
