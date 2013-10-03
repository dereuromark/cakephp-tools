<?php
/**
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('RssView', 'Tools.View');

/**
 * RssViewTest
 *
 */
class RssViewTest extends CakeTestCase {

	public $Rss;

	public function setUp() {
		parent::setUp();
		//Configure::write('debug', 0);

		$this->Rss = new RssView();

		$this->baseUrl = HTTP_BASE;
	}

	/**
	 * testTime method
	 *
	 * @return void
	 */
	public function testTime() {
		$now = time();
		$time = $this->Rss->time($now);
		$this->assertEquals(date('r', $now), $time);
	}

	/**
	 * RssViewTest::testSerialize()
	 *
	 * @return void
	 */
	public function testSerialize() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'channel' => array(
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'description' => 'Channel description'
			),
			'items' => array(
				array('title' => 'Title One', 'link' => 'http://example.org/one', 'author' => 'one@example.org', 'description' => 'Content one'),
				array('title' => 'Title Two', 'link' => 'http://example.org/two', 'author' => 'two@example.org', 'description' => 'Content two'),
			));
		$Controller->set(array('channel' => $data, '_serialize' => 'channel'));
		$View = new RssView($Controller);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description>Channel description</description>
    <item>
      <title>Title One</title>
      <link>http://example.org/one</link>
      <author>one@example.org</author>
      <description>Content one</description>
    </item>
    <item>
      <title>Title Two</title>
      <link>http://example.org/two</link>
      <author>two@example.org</author>
      <description>Content two</description>
    </item>
  </channel>
</rss>

RSS;
		//debug($output); ob_flush();
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerialize()
	 *
	 * @return void
	 */
	public function testSerializeWithPrefixes() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);

		$time = time();
		$data = array(
			'channel' => array(
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'description' => 'Channel description',
				'sy:updatePeriod' => 'hourly',
				'sy:updateFrequency' => 1
			),
			'items' => array(
				array('title' => 'Title One', 'link' => 'http://example.org/one', 'dc:creator' => 'Author One', 'pubDate' => $time),
				array('title' => 'Title Two', 'link' => 'http://example.org/two', 'dc:creator' => 'Author Two', 'pubDate' => $time),
			)
		);
		$Controller->set(array('channel' => $data, '_serialize' => 'channel'));
		$View = new RssView($Controller);
		$result = $View->render(false);

		$time = date('r', $time);
		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description>Channel description</description>
    <sy:updatePeriod>hourly</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <item>
      <title>Title One</title>
      <link>http://example.org/one</link>
      <dc:creator>Author One</dc:creator>
      <pubDate>$time</pubDate>
    </item>
    <item>
      <title>Title Two</title>
      <link>http://example.org/two</link>
      <dc:creator>Author Two</dc:creator>
      <pubDate>$time</pubDate>
    </item>
  </channel>
</rss>

RSS;
		//debug($result); ob_flush();
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithArrayLinks()
	 *
	 * `'atom:link' => array('@href' => array(...)` becomes
	 * '@rel' => 'self', '@type' => 'application/rss+xml' automatically set for atom:link
	 *
	 * @return void
	 */
	public function testSerializeWithArrayLinks() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'channel' => array(
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'atom:link' => array('@href' => array('controller' => 'foo', 'action' => 'bar')),
				'description' => 'Channel description',
			),
			'items' => array(
				array('title' => 'Title One', 'link' => array('controller' => 'foo', 'action' => 'bar'), 'description' => 'Content one'),
				array('title' => 'Title Two', 'link' => array('controller' => 'foo', 'action' => 'bar'), 'description' => 'Content two'),
			)
		);
		$Controller->set(array('channel' => $data, '_serialize' => 'channel'));
		$View = new RssView($Controller);
		$result = $View->render(false);

$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <atom:link href="$this->baseUrl/foo/bar" rel="self" type="application/rss+xml"/>
    <description>Channel description</description>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
    </item>
    <item>
      <title>Title Two</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content two</description>
    </item>
  </channel>
</rss>

RSS;
		//debug($result); ob_flush();
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithContent()
	 *
	 * @return void
	 */
	public function testSerializeWithContent() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'channel' => array(
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'guid' => array('url' => 'http://channel.example.org', '@isPermaLink' => 'true'),
				'atom:link' => array('@href' => array('controller' => 'foo', 'action' => 'bar')),
			),
			'items' => array(
				array('title' => 'Title One', 'link' => array('controller' => 'foo', 'action' => 'bar'), 'description' => 'Content one',
					'content:encoded' => 'HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> one'),
				array('title' => 'Title Two', 'link' => array('controller' => 'foo', 'action' => 'bar'), 'description' => 'Content two',
					'content:encoded' => 'HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> two'),
			)
		);
		$Controller->set(array('channel' => $data, '_serialize' => 'channel'));
		$View = new RssView($Controller);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <guid isPermaLink="true">http://channel.example.org</guid>
    <atom:link href="$this->baseUrl/foo/bar" rel="self" type="application/rss+xml"/>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
      <content:encoded><![CDATA[HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> one]]></content:encoded>
    </item>
    <item>
      <title>Title Two</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content two</description>
      <content:encoded><![CDATA[HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> two]]></content:encoded>
    </item>
  </channel>
</rss>

RSS;
		//debug($output); ob_flush();
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithCustomNamespace()
	 *
	 * @return void
	 */
	public function testSerializeWithCustomNamespace() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array(
			'document' => array(
				'namespace' => array(
					'admin' => 'http://webns.net/mvcb/',
					'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
				)
			),
			'channel' => array(
				'title' => 'Channel title',
				'admin:errorReportsTo' => array('@rdf:resource' => 'mailto:me@example.com')
			),
			'items' => array(
				array('title' => 'Title One', 'link' => array('controller' => 'foo', 'action' => 'bar')),
			)
		);
		$Controller->set(array('channel' => $data, '_serialize' => 'channel'));
		$View = new RssView($Controller);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" version="2.0">
  <channel>
    <title>Channel title</title>
    <admin:errorReportsTo rdf:resource="mailto:me@example.com"/>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
    </item>
  </channel>
</rss>

RSS;
		//debug($result); ob_flush();
		$this->assertTextEquals($expected, $result);
	}

}
