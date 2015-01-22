<?php

//$path = dirname(__FILE__);
//require_once($path . DS . 'Vendor' . DS . 'ical'.DS.'ical.php');
App::import('Vendor', 'Tools.ical', ['file' => 'ical/ical.php']);
App::import('Vendor', 'Tools.icalobject', ['file' => 'ical/i_cal_object.php']);

App::uses('TimeLib', 'Tools.Utility');

/**
 * A wrapper for the Ical/Ics calendar lib
 * @see http://www.dereuromark.de/2011/11/21/serving-views-as-files-in-cake2 for details
 *
 * @author Mark Scherer
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class IcalLib {

	public $Ical;

	public $Time;

	public function __construct() {
		$this->ICalObject = new ICalObject();
	}

	/**
	 *
	 * some automagic
	 * - array urls are transformed in (full) absolute urls
	 * - id => uid with @host
	 * - start/end/timestamp to atom
	 * - class to upper
	 *
	 * @param array $data
	 * @param bool $addStartAndEnd
	 * @return string icalContent (single vevent)
	 */
	public function build($data, $addStartAndEnd = true) {
		$replacements = ['-', ':'];
		if (isset($data['timezone'])) {
			$replacements[] = 'Z';
		}

		if (isset($data['start'])) {
			$data['dtstart'] = TimeLib::toAtom($data['start']);
			$data['dtstart'] = str_replace($replacements, '', $data['dtstart']);
			unset($data['start']);
		}
		if (isset($data['end'])) {
			$data['dtend'] = TimeLib::toAtom($data['end']);
			$data['dtend'] = str_replace($replacements, '', $data['dtend']);
			unset($data['end']);
		}
		if (isset($data['timestamp'])) {
			$data['dtstamp'] = TimeLib::toAtom($data['timestamp']);
			$data['dtstamp'] = str_replace(['-', ':'], '', $data['dtstamp']);
			unset($data['timestamp']);
		}
		if (isset($data['timezone'])) {
			$data['tzid'] = $data['timezone'];
			unset($data['timezone']);
		}
		if (isset($data['id'])) {
			$data['uid'] = $data['id'] . '@' . env('HTTP_HOST');
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
			$res = 'BEGIN:VEVENT' . PHP_EOL . trim($res) . PHP_EOL . 'END:VEVENT';
		}
		return $res;
	}

	/**
	 * Start the file
	 *
	 * @param array $data
	 * @return string
	 */
	public function createStart($data = []) {
		$defaults = [
			'version' => '2.0',
			'prodid' => '-//' . env('HTTP_HOST'),
			'method' => 'PUBLISH',
		];
		$data = array_merge($defaults, $data);

		$res = [];
		$res[] = 'BEGIN:VCALENDAR';
		foreach ($data as $key => $val) {
			$res[] = strtoupper($key) . ':' . $val;
		}
		return implode(PHP_EOL, $res);
	}

	/**
	 * End the file
	 *
	 * @return string
	 */
	public function createEnd() {
		return 'END:VCALENDAR';
	}

	public function parse($url) {
		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']]);
		if (!file_exists($url) || !($res = file_get_contents($url, 0, $context))) {
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
	 */
	public function getCalendarInfos() {
		return $this->Ical->get_calender_data();
	}

	/**
	 * Key => value with key as unixTimeStamp and value as summary
	 *
	 * @return array
	 */
	public function getEventsAsList() {
		$res = [];
		$events = $this->getEvents();
		foreach ($events as $event) {
			$res[$event['DTSTART']['unixtime']] = $event['SUMMARY'];
		}
		return $res;
	}

	/**
	 * @return array events or false on failure
	 */
	public function getEvents() {
		return $this->Ical->get_sort_event_list();
	}

	/**
	 * @return array todos or false on failure
	 */
	public function getTodos() {
		return $this->Ical->get_todo_list();
	}

}
