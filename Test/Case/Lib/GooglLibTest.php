<?php

App::uses('GooglLib', 'Tools.Lib');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

/**
 */
class GooglLibTest extends MyCakeTestCase {

	public function setUp() {
		parent::setUp();

		//Configure::write('Googl.key', 'YOUR KEY');

		$this->Googl = new TestGooglLib();
		$this->Googl->setLive(!$this->isDebug());
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Googl);
	}

	//TODO

	public function testOAuth() {
	}

	public function testHistory() {
		$this->skipIf(true, 'Login required');

		$is = $this->Googl->getHistory();
		$this->debug($is);
	}

	/**
	 * GooglLibTest::testShortenAndUnshorten()
	 *
	 * @return void
	 */
	public function testShortenAndUnshorten() {
		// Shorten without key (publically)
		Configure::write('Googl.key', '');

		$url = 'http://www.spiegel.de';
		$is = $this->Googl->getShort($url);
		$this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] == $url . '/');

		// Unshorten
		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl);
		$this->assertTrue(!empty($is));
		$this->assertTrue(!empty($is['id']));
		$this->assertSame('urlshortener#url', $is['kind']);
		$this->assertSame('OK', $is['status']);
		$this->assertSame($url . '/', $is['longUrl']);
	}

	/**
	 * GooglLibTest::testApi()
	 *
	 * @return void
	 */
	public function testApi() {
		$this->skipIf(!Configure::read('Googl.key'), 'No Api Key found');

		// Shorten with key
		$url = 'http://www.blue.de';
		$is = $this->Googl->getShort($url);
		$this->debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] == $url . '/');

		// Unshorten
		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl);
		$this->debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['status'] === 'OK' && $is['longUrl'] == $url . '/');

		// FULL INFOS
		$url = 'http://www.web.de#123456';
		$is = $this->Googl->getShort($url);
		$this->debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] === 'http://www.web.de/#123456');

		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl, GooglLib::PROJECTION_CLICKS);
		$this->debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['status'] === 'OK' && $is['longUrl'] === 'http://www.web.de/#123456');
	}

}

/**
 * Wrapper to mock the API calls away
 */
class TestGooglLib extends GooglLib {

	protected $_debug = true;

	protected $_map = [
		'http://www.spiegel.de' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/nBBg",
 "longUrl": "http://www.spiegel.de/"
}',
		'http://goo.gl/nBBg' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/nBBg",
 "longUrl": "http://www.spiegel.de/",
 "status": "OK"
}',
		'http://www.blue.de' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/leVfu4",
 "longUrl": "http://www.blue.de/"
}',
		'http://goo.gl/leVfu4' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/leVfu4",
 "longUrl": "http://www.blue.de/",
 "status": "OK"
}',
		'http://www.web.de#123456' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/7937W",
 "longUrl": "http://www.web.de/#123456"
}',
		'http://goo.gl/7937W' => '{
 "kind": "urlshortener#url",
 "id": "http://goo.gl/7937W",
 "longUrl": "http://www.web.de/#123456",
 "status": "OK",
 "analytics": {
  "allTime": {
   "shortUrlClicks": "1",
   "longUrlClicks": "1"
  },
  "month": {
   "shortUrlClicks": "0",
   "longUrlClicks": "0"
  },
  "week": {
   "shortUrlClicks": "0",
   "longUrlClicks": "0"
  },
  "day": {
   "shortUrlClicks": "0",
   "longUrlClicks": "0"
  },
  "twoHours": {
   "shortUrlClicks": "0",
   "longUrlClicks": "0"
  }
 }
}'
	];

	public function setLive($live = true) {
		$this->_debug = !$live;
	}

	public function getShort($url) {
		if ($this->_debug) {
			return json_decode($this->_map[$url], true);
		}
		return parent::getShort($url);
	}

	public function getLong($url, $projection = null) {
		if ($this->_debug) {
			return json_decode($this->_map[$url], true);
		}
		return parent::getLong($url, $projection);
	}

}
