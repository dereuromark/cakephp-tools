<?php
App::uses('AppHelper', 'View/Helper');

App::uses('Helper', 'View');

/**
 * uses ical lib
 * tipps see http://labs.iamkoa.net/2007/09/07/create-downloadable-ical-events-via-cake/
 *
 * needs ical layout
 * needs Router::parseExtensions('ics') in router.php
 *
 * 2011-10-10 ms
 */
class IcalHelper extends AppHelper {

	public $helpers = array(); //'Html'

	public $Ical;

	protected $_data = array();


	public function __construct($View = null, $settings = array()) {
		parent::__construct($View, $settings);

		App::uses('IcalLib', 'Tools.Lib');
		$this->Ical = new IcalLib();
	}


	public function reset() {
		$this->$_data = array();
	}

	/**
	 * add a new ical record
	 * @return boolean $success
	 */
	public function add($data = array()) {
		//TODO: validate!
		$this->_data[] = $data;

		return true;
	}

	/**
	 * returns complete ical calender file content to output
	 * 2011-10-10 ms
	 */
	public function generate($globalData = array(), $addStartAndEnd = true) {
		$res = array();
		foreach ($this->_data as $data) {
			$res[] = $this->Ical->build($data);
		}
		$res = implode(PHP_EOL.PHP_EOL, $res);
		if ($addStartAndEnd) {
			$res = $this->Ical->createStart($globalData).PHP_EOL.PHP_EOL.$res.PHP_EOL.PHP_EOL.$this->Ical->createEnd();
		}
		return $res;
	}

}