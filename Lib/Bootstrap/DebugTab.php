<?php

/**
 * Debug entries to a static class
 * Do not use App::uses() to include this file as it also needs a function included
 * (see below). Use App::import('Lib', 'Tools.Bootstrap/DebugTab');
 */
class DebugTab {

	public static $content = array();

	public static $groups = array();

	/**
	 * @return boolean Success
	 */
	public static function debug($var = false, $display = false, $key = null) {
		if (is_string($display)) {
			$key = $display;
			$display = true;
		}
		if (Configure::read('debug') > 0) {
			$calledFrom = debug_backtrace();
			if (is_string($key)) {
				if (!isset(DebugTab::$groups[$key])) {
					DebugTab::$groups[$key] = array();
				}
				DebugTab::$groups[$key][] = array(
					'debug' => print_r($var, true),
					'file' => substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1),
					'line' => $calledFrom[0]['line'],
					'display' => $display
				);
			} else {
				DebugTab::$content[] = array(
					'debug' => print_r($var, true),
					'file' => substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1),
					'line' => $calledFrom[0]['line'],
					'display' => $display
				);
			}
		}
		return true;
	}

	/**
	 * Display debugged information
	 *
	 * @return string HTML
	 */
	public static function get() {
		return '<pre class="debug-tab">' .
			print_r(DebugTab::$groups, true) .
			print_r(DebugTab::$content, true) .
			'</pre>';
	}
}

/**
 * Public, quick access function for class
 *
 * @return boolean Success
 */
function debugTab($var = false, $display = false, $key = null) {
	return DebugTab::debug($var, $display, $key);
}
