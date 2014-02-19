<?php
App::uses('AppHelper', 'View/Helper');
/*
uid The Flattr User ID as found on the Flattr dashboard (in the example I used mine).
tle The title for the link to be submitted.
Since the helper supports the full range of options below are the other options:

dsc A description for the link.
cat The category for the link. This can be any of the following: text, images, video, audio, software, rest. The default if this option isn’t specified is text.
lng The language of the link. Any of the languages on this list and defaults to en_GB.
tags Any tags matching the link. This field must be an array!
url The URL of the link.
btn The badge to use. Currently the only option is compact but if not specified or set to something else it defaults to the standard larger badge
*/

/**
 * Flattr Donate Button
 * @link http://flattr.com/support/integrate/js
 */
class FlattrHelper extends AppHelper {

	public $helpers = array('Html');

	const API_URL = 'http://api.flattr.com/';

	/**
	 * Display the FlattrButton
	 *
	 * @param mixed $url (unique! necessary)
	 * @param array $options
	 * @return string
	 */
	public function button($url, $options = array(), $attr = array()) {
		if (empty($options['uid'])) {
			$options['uid'] = Configure::read('Flattr.uid');
		}
		$categories = array();

		$defaults = array(
			'mode' => 'auto',
			'language' => 'de_DE',
			'category' => 'text',
			'button' => 'default', # none or compact
			'tags' => array(),
			//'hidden' => '',
			//'description' => '',
		);
		$options = array_merge($defaults, $options);

		$mode = $options['mode'];
		unset($options['mode']);
		if (is_array($options['tags'])) {
			$options['tags'] = implode(',', $options['tags']);
		}

		$rev = array();
		foreach ($options as $key => $option) {
			$rev[] = $key . ':' . $option;
		}
		$linkOptions = array(
			'title' => $_SERVER['HTTP_HOST'],
			'class' => 'FlattrButton',
			'style' => 'display:none;',
			'rel' => 'flattr;' . implode(';', $rev)
		);
		$linkOptions = array_merge($linkOptions, $attr);

		$js = "(function() {
	var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
	s.type = 'text/javascript';
	s.async = true;
	s.src = '" . self::API_URL . "js/0.6/load.js?mode=" . $mode . "';
	t.parentNode.insertBefore(s, t);
})();";
		$code = $this->Html->link('', $this->Html->url($url, true), $linkOptions);

		//&uid=gargamel&language=sv_SE&category=text

		// compact: <a class="FlattrButton" style="display:none;" rev="flattr;button:compact;"href="X"></a>

		// static: <a href="http://flattr.com/thing/X" target="_blank"><img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>

		$code .= $this->Html->scriptBlock($js, array('inline' => true));
		return $code;
	}

	//TODO: http://api.flattr.com/odapi/categories/json - parse

	/**
	 * @deprecated!!!
	 * @param mixed $uid
	 * @param array $options:
	 * - tle, cat, lng, dsc, url, btn, tags, hidden (optional)
	 * @return string html with js script tag
	 */
	public function badge($uid = null, $options = array()) {
		if (!$uid) {
			$uid = Configure::read('Flattr.uid');
		}
		if (!isset($options['tle'])) {
			$options['tle'] = __('Donate');
		}

		$vars = '';
		$vars .= "var flattr_uid = '" . h($uid) . "';\r\n";
		$vars .= "var flattr_tle = '" . $options['tle'] . "';\r\n";
		if (!isset($options['dsc'])) {
			$options['dsc'] = '';
		}
		$vars .= "var flattr_dsc = '" . $options['dsc'] . "';\r\n";
		if (!isset($options['cat'])) {
			$options['cat'] = 'text';
		}
		$vars .= "var flattr_cat = '" . $options['cat'] . "';\r\n";
		if (!isset($options['lng'])) {
			$options['lng'] = 'en_GB';
		}
		$vars .= "var flattr_lng = '" . $options['lng'] . "';\r\n";
		if (isset($options['tags']) && count($options['tags']) > 0) {
			//array_walk($options['tags'], 'Sanitize::paranoid');
			$vars .= "var flattr_tag = '" . implode(', ', $options['tags']) . "';\r\n";
		}
		if (isset($options['url']) && ((version_compare(phpversion(), '5.2.0', '>=') && function_exists('filter_var')) ? filter_var($options['url'],
			FILTER_VALIDATE_URL) : true)) {
			$vars .= "var flattr_url = '" . $options['url'] . "';\r\n";
		}
		if (isset($options['btn']) && $options['btn'] === 'compact') {
			$vars .= "var flattr_btn = 'compact';\r\n";
		}
		$code = $this->Html->scriptBlock($vars, array('inline' => true));
		$code .= $this->Html->script(self::API_URL . 'button/load.js', array('inline' => true));
		return $code;
	}

}
