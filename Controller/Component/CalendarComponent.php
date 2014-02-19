<?php

App::uses('Component', 'Controller');

/**
 * Calendar Component
 *
 * inspired by http://www.flipflops.org/2007/09/21/a-simple-php-calendar-function/
 *
 * @author Mark Scherer
 * @copyright 2012 Mark Scherer
 * @license MIT
 *
 */
class CalendarComponent extends Component {

	public $Controller = null;

	public $monthList = array(
		'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');

	public $dayList = array(
		'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'
	);

	public $year = null;

	public $month = null;

	public $day = null;

	/**
	 * Startup controller
	 *
	 * @param object $Controller Controller instance
	 * @return void
	 */
	public function startup(Controller $Controller) {
		$this->Controller = $Controller;
	}

	/**
	 * @return boolean Success
	 */
	public function ensureCalendarConsistency($year, $month, $span = 10) {
		if (!is_numeric($month)) {
			$monthKeys = array_keys($this->monthList, $month);
			$month = array_shift($monthKeys);
			if ($month === null) {
				$month = -1;
			}
		}

		if (!$year || !$month) {
			$year = date('Y');
			$month = date('n');
			$item = null;
		}
		$year = (int)$year;
		$month = (int)$month;

		$current = date('Y');

		if (empty($month) || $year < $current - $span || $year > $current + $span) {
			$this->Controller->Common->flashMessage(__('invalid date'), 'error');
			$this->Controller->redirect(array('action' => 'index'));
		}

		$this->year = $year;
		$this->month = $month;

		if (empty($this->Controller->request->params['pass'])) {
			return true;
		}

		if ($month < 1 || $month > 12) {
			$this->Controller->Common->flashMessage(__('invalid date'), 'error');
			$this->Controller->redirect(array('action' => 'index'));
		}
		return true;
	}

	public function year() {
		return $this->year;
	}

	public function month($asString = false) {
		return $this->month;
	}

	/**
	 * Month as integer value 1..12 or 0 on error
	 * february => 2
	 */
	public function retrieveMonth($string) {
		if (empty($string)) {
			return 0;
		}
		$string = mb_strtolower($string);
		if (in_array($string, $this->monthList)) {
			$keys = array_keys($this->monthList, $string);
			return $keys[0] + 1;
		}
		return 0;
	}

	/**
	 * Day as integer value 1..31 or 0 on error
	 * february => 2
	 */
	public function retrieveDay($string, $month = null) {
		if (empty($string)) {
			return 0;
		}
		$string = (int)$string;
		if ($string < 1 || $string > 31) {
			return 0;
		}

		// check on month days!
		return $string;

		return 0;
	}

	public function months() {
		return $this->monthList;
	}

	public function days() {
		return $this->dayList;
	}

	/**
	 * Converts integer to x-digit string
	 * 1 => 01, 12 => 12
	 */
	public function asString($number, $digits = 2) {
		$number = (string)$number;
		$count = mb_strlen($number);
		while ($count < $digits) {
			$number = '0' . $number;
			$count++;
		}
		return $number;
	}

}
