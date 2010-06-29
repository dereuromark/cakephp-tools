<?php

App::import('Lib', 'Tools.HttpSocketLib');

class HttpSocketLibTestCase extends CakeTestCase {

	function setUp() {
		$this->HttpSocketLib = new HttpSocketLib();
		$this->assertTrue(is_object($this->HttpSocketLib));
	}

	function TearDown() {
		unset($this->HttpSocketLib);
	}

	function testFetch() {

		$url = 'http://maps.google.de';
		$is = $this->HttpSocketLib->fetch($url);
		//echo returns($is);
		$this->assertTrue(!empty($is));

		$url = 'http://sscfmaps.sfdgoogle.eede';
		$is = $this->HttpSocketLib->fetch($url);
		echo returns($is);
		$this->assertFalse($is);

		$error = $this->HttpSocketLib->error();
		echo returns($error);
		$this->assertTrue(!empty($error));

		$this->assertEqual($this->HttpSocketLib->debug, 'curl');

	}


	function testFetchPhp() {
		$this->HttpSocketLib = new HttpSocketLib('php');

		$url = 'http://maps.google.ch';
		$is = $this->HttpSocketLib->fetch($url);
		//echo returns($is);
		$this->assertTrue(!empty($is));

		$this->assertEqual($this->HttpSocketLib->debug, 'php');

	}


	function testFetchCake() {
		$this->HttpSocketLib = new HttpSocketLib('cake');

		$url = 'http://maps.google.at';
		$is = $this->HttpSocketLib->fetch($url);
		//echo returns($is);
		$this->assertTrue(!empty($is));

		$this->assertEqual($this->HttpSocketLib->debug, 'cake');
	}





}
?>