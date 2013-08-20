<?php
/**
 * Imap Datasource Test file
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * @author Mark Scherer
 */

App::uses('ImapSource', 'Tools.Model/Datasource');
App::uses('ConnectionManager', 'Model');
App::uses('AppModel', 'Model');
App::uses('MyCakeTestCase', 'Tools.TestSuite');

//class_exists('TestImapSource');

// Add new db config
//ConnectionManager::create('test_imap', array('datasource' => 'TestImapSource', 'type' => 'imap'));

/**
 * Imap Datasource Test
 *
 */
class ImapSourceTest extends MyCakeTestCase {

	public $Model = null;

	/**
	 * Imap Source Instance
	 *
	 * @var ImapSource
	 */
	public $Imap = null;

	public function setUp() {
		parent::setUp();

		$this->Model = ClassRegistry::init('TestImap');
		$config = array();
		$this->Imap = new TestImapSource($config);
	}

	/**
	 * testFindWithoutConfig
	 *
	 * @expectedException RuntimeException
	 */
	public function _testFindWithoutConfig() {
		$result = $this->Model->find('all');
		//$expected = ?
		//$this->assertEquals($expected, $result);
		$this->debug($result);
	}

	public function testMakeSearch() {
		$query = array(
			'answered' => 1,
			'seen' => true,
			'deleted' => null,
			'flagged' => false, // will be reversed
		);
		$res = $this->Imap->makeSearch($this->Model, array('conditions' => $query));
		$expected = array('SEEN', 'UNFLAGGED', 'ANSWERED');
		$this->assertEquals($expected, $res);
	}

}

/**
 * Testing Source
 *
 */
class TestImapSource extends ImapSource {

	public function makeSearch($Model, $query) {
		return $this->_makeSearch($Model, $query);
	}

}

/**
 * Testing Model
 *
 */
class TestImap extends AppModel {

	public $useTable = false;

	public $recursive = -1;

}