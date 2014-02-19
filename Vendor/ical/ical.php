<?php
/*
	Copyright (c) 2010, Roman Ožana

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
 */
/**
 * This class Parse iCal standard. Is prepare to iCal feature version. Now is testing with apple iCal standard 2.0.
 *
 * @name iCal parser
 * @copyright Roman Ožana, 2011
 * @author Roman Ožana 2006-2011

 * @link www.nabito.net
 * @link www.omdesign.cz
 * @link https://github.com/OzzyCzech/icalparser/blob/master/ical.php
 * @version 2.0
 * @license MIT
 *
 * <code>
 * 	$ical = new ical('./calendar.ics');
 * 	$ical->parse();
 *  echo '<pre>'.print_r($ical->get_all_data(), true).'</pre>';
 * </code>
 *
 */

//namespace Helpers\Calendar;

class ical
{

	/** @var string content of file */
	private $plain_content = null;
	/** @var array save iCalendar parse data */
	private $cal = array ();
	/** @var string Help variable save last key (multiline string) */
	private $last_key = '';
	/** @var array buffer */
	private $buffer = array ();
	/** @var string nesting or open tag */
	private $nesting = 'VCALENDAR';


	/**
	 * Constructr iCal parser object
	 * @param string $filename
	 */
	public function __construct($filename = null)
	{
	if (!is_null($filename)) $this->read_file($filename);
	}


	/**
	 * Read iCal file
	 * @param string $filename
	 */
	public function read_file($filename)
	{
	// FIXME load file content and replace wrong way formated lines
	$this->plain_content = preg_replace("/[\r\n]{1,} ([:;])/", "\\1", file_get_contents($filename));

	// because Mozilla Calendar save values wrong, like this -->
	#SUMMARY
	// :Text of sumary
	// good way is, for example in SunnyBird. SunnyBird save iCal like this example -->
	#SUMMARY:Text of sumary
	}


	/**
	 * Prekladac kalendare
	 *
	 * @param string|url $filename
	 * @return unknown
	 */
	public function parse()
	{
	$this->plain_content = preg_split("/[\n]/", $this->plain_content); // split by lines
	// is this text vcalendar standart text ? on line 1 is BEGIN:VCALENDAR
	if (strpos($this->plain_content[0], 'BEGIN:VCALENDAR') === false)
	{
		throw new Exception('Not a VCALENDAR file');
	}

	foreach ($this->plain_content as $text)
	{
		$text = trim($text); // trim one line
		if (!empty($text))
		{
		// get Key and Value VCALENDAR:Begin --> Key = VCALENDAR, Value = begin
		list($key, $value) = $this->retun_key_value($text);


		if ($key === false)
		{
			$key = $this->last_key; // in case key is empty
		}

		// process simple dates
		if (($key == "DTSTAMP") || ($key == "LAST-MODIFIED") || ($key == "CREATED"))
		{
			$value = $this->ical_date_to_unix($value);
		}

		// process RRULE
		if ($key == "RRULE")
		{
			$value = $this->ical_rrule($value);
		}

		//
		// process ical date values like
		//
		// [DTSTART;VALUE=DATE] => 20121224
		// [DTEND;VALUE=DATE] => 20121225
		if (strpos($key, 'DTSTART') !== false || strpos($key, 'DTEND') !== false)
		{
			list($key, $value) = $this->ical_dt_date($key, $value);
		}

		switch ($text) // search special string
		{
			case "BEGIN:VCALENDAR":
			case "BEGIN:DAYLIGHT":
			case "BEGIN:VTIMEZONE":
			case "BEGIN:STANDARD":
			case "BEGIN:VTODO":
			case "BEGIN:VEVENT":
			$this->nesting = substr($text, 6);
			$this->buffer[$this->nesting] = array (); // null buffer
			break;


			case "END:VCALENDAR":
			$this->cal['VCALENDAR'] = $this->buffer['VCALENDAR']; // save buffer
			break;

			case "END:DAYLIGHT":
			case "END:VTIMEZONE":
			case "END:STANDARD":
			case "END:VEVENT":
			case "END:VTODO":
			$this->cal[substr($text, 4)][] = $this->buffer[$this->nesting]; // save buffer
			break;

			default: // no special string
			$this->buffer[$this->nesting][$key] = $value;
			$this->last_key = $key; // save last key
			break;
		}
		}
	}
	return $this->cal;
	}


	/* --------------------------------------------------------------------------
	 * Private parser functions
	 * -------------------------------------------------------------------------- */


	/**
	 * Parse text "XXXX:value text some with : " and return array($key = "XXXX", $value="value");
	 *
	 * @param unknown_type $text
	 * @return unknown
	 */
	private function retun_key_value($text)
	{
	preg_match("/([^:]+)[:]([\w\W]+)/", $text, $matches);

	if (empty($matches))
	{
		return array (false, $text);
	}
	else
	{
		$matches = array_splice($matches, 1, 2);
		return $matches;
	}
	}


	/**
	 * Parse RRULE  return array
	 *
	 * @param unknown_type $value
	 * @return unknown
	 */
	private function ical_rrule($value)
	{
	$rrule = explode(';', $value);
	foreach ($rrule as $line)
	{
		$rcontent = explode('=', $line);
		$result[$rcontent[0]] = $rcontent[1];
	}
	return $result;
	}


	/**
	 * Return Unix time from ical date time fomrat (YYYYMMDD[T]HHMMSS[Z] or YYYYMMDD[T]HHMMSS)
	 *
	 * @param unknown_type $ical_date
	 * @return unknown
	 */
	private function ical_date_to_unix($ical_date)
	{
	$ical_date = preg_replace(array ('/T/', '/Z/'), '', $ical_date); // remove T and Z from strig

	if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $ical_date, $date))
	{

		if ($date[1] <= 1970)
		{
		$date[1] = 1971; // FIXME UNIX timestamps can't deal with pre 1970 dates
		}

		return mktime((int) $date[4], (int) $date[5], (int) $date[6], (int) $date[2], (int) $date[3], (int) $date[1]);
	}
	else
	{
		return null;
	}
	}


	/**
	 * Return unix date from iCal date format
	 *
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	private function ical_dt_date($key, $value)
	{
	$value = $this->ical_date_to_unix($value);

	// zjisteni TZID
	$temp = explode(";", $key);

	if (empty($temp[1])) // neni TZID
	{
			// 2011-06-02 ms fix
		$value = str_replace('T', '', $value);
		return array ($key, $value);
	}
	// pridani $value a $tzid do pole
	$key = $temp[0];
	$temp = explode("=", $temp[1]);
	$return_value[$temp[0]] = $temp[1];
	$return_value['unixtime'] = $value;

	return array ($key, $return_value);
	}


	/* --------------------------------------------------------------------------
	 * List of public getters
	 * -------------------------------------------------------------------------- */


	/**
	 * Return sorted eventlist as array or false if calenar is empty
	 *
	 * @return array|boolean
	 */
	public function get_sort_event_list()
	{
	$temp = $this->get_event_list();
	if (!empty($temp))
	{
		usort($temp, array (&$this, "ical_dtstart_compare"));
		return $temp;
	}
	else
	{
		return false;
	}
	}


	/**
	 * Compare two unix timestamp
	 *
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	private function ical_dtstart_compare($a, $b)
	{
	return strnatcasecmp($a['DTSTART']['unixtime'], $b['DTSTART']['unixtime']);
	}


	/**
	 * Return eventlist array (not sort eventlist array)
	 *
	 * @return array
	 */
	public function get_event_list()
	{
	return $this->cal['VEVENT'];
	}


	/**
	 * Return todo arry (not sort todo array)
	 *
	 * @return array
	 */
	public function get_todo_list()
	{
	if (empty($this->cal['VTODO'])) {
		return array();
	}
	return $this->cal['VTODO'];
	}


	/**
	 * Return base calendar data
	 *
	 * @return array
	 */
	public function get_calender_data()
	{
	return $this->cal['VCALENDAR'];
	}


	/**
	 * Return array with all data
	 *
	 * @return array
	 */
	public function get_all_data()
	{
	return $this->cal;
	}


}