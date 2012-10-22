<?php

//$path = dirname(__FILE__);
//require_once($path . DS . 'Vendor' . DS . 'ical'.DS.'ical.php');
App::import('Vendor', 'Tools.ical', array('file'=>'ical/ical.php'));
App::import('Vendor', 'Tools.icalobject', array('file'=>'ical/i_cal_object.php'));

App::uses('CakeTime', 'Utility');

/**
 * A wrapper for the Ical/Ics calendar lib
 * @see http://www.dereuromark.de/2011/11/21/serving-views-as-files-in-cake2 for details
 *
 * @author Mark Scherer
 * @license MIT
 * @cakephp 2.0
 * 2010-09-14 ms
 */
class IcalLib {

	public $Ical;
	public $Time;

	public function __construct() {
		$this->ICalObject = new ICalObject();
	}

/** BUILDING **/

	/**
	 *
	 * some automagic
	 * - array urls are transformed in (full) absolute urls
	 * - id => uid with @host
	 * - start/end/timestamp to atom
	 * - class to upper
	 * @return string $icalContent (single vevent)
	 * 2011-10-10 ms
	 */
	public function build($data, $addStartAndEnd = true) {
		if (isset($data['start'])) {
			$data['dtstart'] = CakeTime::toAtom($data['start']);
			$data['dtstart'] = str_replace(array('-', ':'), '', $data['dtstart']);
			unset($data['start']);
		}
		if (isset($data['end'])) {
			$data['dtend'] = CakeTime::toAtom($data['end']);
			$data['dtend'] = str_replace(array('-', ':'), '', $data['dtend']);
			unset($data['end']);
		}
		if (isset($data['timestamp'])) {
			$data['dtstamp'] = CakeTime::toAtom($data['timestamp']);
			$data['dtstamp'] = str_replace(array('-', ':'), '', $data['dtstamp']);
			unset($data['timestamp']);
		}
		if (isset($data['id'])) {
			$data['uid'] = $data['id'].'@'.env('HTTP_HOST');
			unset($data['id']);
		}
		if (isset($data['class'])) {
			$data['class'] = strtoupper($data['class']);
		}
		if (isset($data['url']) && is_array($data['url'])) {
			$data['url'] = Router::url($data['url'], true);
		}
		$res = $this->ICalObject->create($data);
		if ($addStartAndEnd) {
			$res = 'BEGIN:VEVENT'.PHP_EOL.trim($res).PHP_EOL.'END:VEVENT';
		}
		return $res;
	}

	public function createStart($data = array()) {
		$defaults = array(
			'version' => '2.0',
			'prodid' => '-//'.env('HTTP_HOST'),
			'method' => 'PUBLISH',
		);
		$data = am($defaults, $data);

		$res = array();
		$res[] = 'BEGIN:VCALENDAR';
		foreach ($data as $key => $val) {
			$res[] = strtoupper($key).':'.$val;
		}
		return implode(PHP_EOL, $res);
	}

	public function createEnd() {
		return 'END:VCALENDAR';
	}


/** PARSING **/

	public function parse($url) {
		if (!file_exists($url) || !($res = file_get_contents($url))) {
			return false;
		}
		$this->Ical = new ical($url);
		if ($this->Ical->parse()) {
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 * 2010-09-14 ms
	 */
	public function getCalendarInfos() {
		return $this->Ical->get_calender_data();
	}

	/**
	 * key => value with key as unixTimeStamp and value as summary
	 * @return array
	 * 2010-09-14 ms
	 */
	public function getEventsAsList() {
		$res = array();
		$events = $this->getEvents();
		foreach ($events as $event) {
			$res[$event['DTSTART']['unixtime']] = $event['SUMMARY'];
		}
		return $res;
	}

	/**
	 * @return array $events or false on failure
	 * 2010-09-14 ms
	 */
	public function getEvents() {
		return $this->Ical->get_sort_event_list();
	}

	/**
	 * @return array $todos or false on failure
	 * 2010-09-14 ms
	 */
	public function getTodos() {
		return $this->Ical->get_todo_list();
	}


}


