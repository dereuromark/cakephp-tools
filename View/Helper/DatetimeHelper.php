<?php

App::uses('DatetimeLib', 'Tools.Utility');
App::uses('TimeHelper', 'View/Helper');

/**
 * TODO: make extend TimeLib some day?
 * 2012-04-09 ms
 */
class DatetimeHelper extends TimeHelper {
	
	public $helpers = array('Html');

	public $Datetime;

	protected $userOffset = null;
	protected $daylightSavings = false;

	public function __construct($View = null, $settings = array()) {
		$settings = Set::merge(array('engine' => 'Tools.DatetimeLib'), $settings);
		parent::__construct($View, $settings);

		$i18n = Configure::read('Localization');
		if (!empty($i18n['time_offset'])) {
			$this->userOffset = (int)$i18n['time_offset'];
		}
		if (!empty($i18n['daylight_savings'])) {
			$this->daylightSavings = (bool)$i18n['daylight_savings'];
		}
		//$this->Datetime = new DatetimeLib();
	}

	/*
	public function __call($method, $params) {

		if (!method_exists($this, 'call__')) {
			//trigger_error(__('Magic method handler call__ not defined in %s', get_class($this)), E_USER_ERROR);
		}
		return call_user_func_array(array($this->Datetime, $method), $params);
	}
	*/


	/**
	 * EXPERIMENTAL!!!
	 * @param
	 * @param
	 * @return int offset
	 * 2009-03-19 ms
	 */
	public function tzOffset($gmtoffset, $is_dst) {
		//global $gmtoffset, $is_dst;

		extract(getdate());
		$serveroffset = gmmktime(0,0,0,$mon,$mday,$year) - mktime(0,0,0,$mon,$mday,$year);
		$offset = $gmtoffset - $serveroffset;

		return $offset + ($is_dst ? 3600 : 0);
	}


	/**
	 * @param string date (from db)
	 * @return int $age on success, mixed $default otherwise
	 * 2009-11-22 ms
	 */
	public function userAge($date = null, $default = '---') {
		if ((int)$date === 0) {
			return $default;
		}
		$age = $this->age($date, null);
		if ($age >= 1 && $age <= 99) {
			return $age;
			}
			return $default;
	}




	/**
	 * Like localDate(), only with additional markup <span> and class="today", if today, etc
	 * 2009-11-22 ms
	 */
	public function localDateMarkup($dateString = null, $format = null, $options = array()) {
		$date = $this->localDate($dateString, $format, $options);
		$date = '<span'.($this->isToday($dateString,(isset($options['userOffset'])?$options['userOffset']:null))?' class="today"':'').'>'.$date.'</span>';
		return $date;
	}



	/**
	 * Like niceDate(), only with additional markup <span> and class="today", if today, etc
	 * 2009-11-22 ms
	 */
	public function niceDateMarkup($dateString = null, $format = null, $options = array()) {
		$date = $this->niceDate($dateString, $format, $options);
		$date = '<span'.($this->isToday($dateString,(isset($options['userOffset'])?$options['userOffset']:null))?' class="today"':'').'>'.$date.'</span>';
		return $date;
	}



	/**
	 * returns red/specialGreen/green date depending on the current day
	 * @param date in DB Format (xxxx-xx-xx)
	 * ...
	 * @param array $options
	 * @param array $attr: html attributes
	 * @return nicely formatted date
	 * 2009-07-25 ms
	 * // TODO refactor!
	 */
	public function published($dateString = null, $userOffset = null, $options=array(), $attr=array()) {
		$date = $dateString ? $this->fromString($dateString, $userOffset) : null; // time() ?
		$niceDate = '';
		$when = null;
		$span = '';
		$spanEnd = '';
		$whenArray = array('-1'=>'already','0'=>'today','1'=>'notyet');
		$titles = array('-1'=>__('publishedAlready'),'0'=>__('publishedToday'),'1'=>__('publishedNotYet'));

		if (!empty($date)) {

			$y = $this->isThisYear($date) ? '' : ' Y';

			$format = (!empty($options['format'])?$options['format']:FORMAT_NICE_YMD);

			# Hack
			# //TODO: get this to work with datetime - somehow cleaner
			$timeAttachment = '';
			if (isset($options['niceDateTime'])) {
				$timeAttachment = ', '.$this->niceDate($date, $options['niceDateTime']);
				$whenOverride = true;
			}

			if ($this->isToday($date)) {
				$when = 0;
				$niceDate = __('Today').$timeAttachment;
			} elseif ($this->isTomorrow($date)) {
				$when = 1;
				$niceDate = __('Tomorrow').$timeAttachment;
			} elseif ($this->wasYesterday($date)) {
				$when = -1;
				$niceDate = __('Yesterday').$timeAttachment;
			} else {
				# before or after?
				if ($this->isNotTodayAndInTheFuture($date)) {
					$when = 1;
				} else {
					$when = -1;
				}
				$niceDate = $this->niceDate($date, $format).$timeAttachment; //date("M jS{$y}", $date);
			}

			if (!empty($whenOverride) && $when == 0) {
				if ($this->isInTheFuture($date)) {
					$when = 1;
				} else {
					$when = -1;
				}
			}

		}

		if (empty($niceDate) || $when === null) {
			$niceDate = '<i>n/a</i>';
		} else {
			if (!isset($attr['title'])) {
				$attr['title'] = $titles[$when];
			}
			$attr['class'] = 'published '.$whenArray[$when];
			//$span = '<span class="published '..'">';	// -1/-2 = ago | 1/2 = ahead | 0 = today
			//$spanEnd = '</span>';
		}
		if (isset($this->Html)) {
			return $this->Html->tag('span', $niceDate, $attr);
		}
		trigger_error('HtmlHelper not found');
		$a = array();
		foreach ($attr as $key => $val) {
			$a[] = $key.'="'.$val.'"';
		}
		$attr = '';
		if (!empty($a)) {
			$attr .= ' '.implode(' ', $a);
		}
		$span = '<span'.$attr.'>';
		$spanEnd = '</span>';
		return $span.$niceDate.$spanEnd;
	}



	/**
	 * @deprecated - use DatetimeLib::isInRange()
	 * for birthdays etc
	 * @param date
	 * @param string days with +-
	 * @param options
	 * 2010-08-26 ms
	 */
	public function isInRangeFromDays($dateString, $days, $options = array()) {
		$date = explode(' ',$dateString);
		list ($y, $m, $d) = explode('-', $date[0]);

		$then = mktime(1, 1, 1, $m, $d, $y);
		$now = mktime(1, 1, 1, date('n'), date('j'), $y);

		$abs = abs($now-$then);

		if ((int)($abs/DAY) <= $days) {
			return true;
		}
		return false;
	}

	/**
	 * takes time as hh:mm:ss
	 * returns hh:mm
	 * @param badTime
	 * returns niceTime
	 * TODO: move to lib, but more generic
	 * 2011-07-19 gh
	 */
	public function niceTime($badTime) {
		return substr($badTime, 0, 5);
	}

}