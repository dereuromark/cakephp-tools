<?php

App::uses('GooglLib', 'Tools.Lib');

/**
 */
class GooglLibTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();

		//Configure::write('Googl.key', 'YOUR KEY');

		$this->Googl = new GooglLib();
	}

	public function tearDown() {
		parent::tearDown();

		unset($this->Googl);
	}

	//TODO

	public function testOAuth() {
	}

	public function testHistory() {
		$this->skipIf(true);

		$is = $this->Googl->getHistory();
		//pr($is);
		die();
	}

	public function testShortenAndUnshorten() {
		//echo '<h2>Shorten without key (publically)</h2>';
		Configure::write('Googl.key', '');

		$url = 'http://www.spiegel.de';
		$is = $this->Googl->getShort($url);
		//pr($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] == $url . '/');

		//echo '<h2>Unshorten</h2>';

		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl);
		//pr($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['status'] === 'OK' && $is['longUrl'] == $url . '/');
	}

	public function testApi() {
		$this->skipIf(!Configure::write('Googl.key'), 'No Api Key found');

		//echo '<h2>Shorten with key</h2>';

		$url = 'http://www.gmx.de';
		$is = $this->Googl->getShort($url);
		//pr($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] == $url . '/');

		//echo '<h2>Unshorten</h2>';

		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl);
		//pr($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['status'] === 'OK' && $is['longUrl'] == $url . '/');

		//echo '<h2>FULL INFOS</h2>';

		$url = 'http://www.web.de#123456';
		$is = $this->Googl->getShort($url);
		//debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['longUrl'] === 'http://www.web.de/#123456');

		$shortUrl = $is['id'];
		$is = $this->Googl->getLong($shortUrl, GooglLib::PROJECTION_CLICKS);

		//debug($is);
		$res = $this->assertTrue(!empty($is) && is_array($is) && !empty($is['id']) && $is['kind'] === 'urlshortener#url' && $is['status'] === 'OK' && $is['longUrl'] === 'http://www.web.de/#123456');
	}

}
