<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.9.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Tools\View\Helper;

use Cake\View\Helper\HtmlHelper as CoreHtmlHelper;

/**
 * Overwrite
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class HtmlHelper extends CoreHtmlHelper {

	/**
	 * Display image tag from blob content.
	 * Enhancement for HtmlHelper. Defaults to png image
	 *
	 * Options:
	 * - type: png, gif, jpg, ...
	 *
	 * @param string $content Data in binary form
	 * @param array $options Attributes
	 * @return string HTML image tag
	 */
	public function imageFromBlob($content, array $options = []) {
		$options += ['type' => 'png'];
		$mimeType = 'image/' . $options['type'];

		$text = 'data:' . $mimeType . ';base64,' . base64_encode($content);

		return $this->formatTemplate('image', [
			'url' => $text,
			'attrs' => $this->templater()->formatAttributes($options, ['block', 'link']),
		]);
	}

	/**
	 * Creates a reset HTML link.
	 * The prefix and plugin params are resetting to default false.
	 *
	 * ### Options
	 *
	 * - `escape` Set to false to disable escaping of title and attributes.
	 * - `escapeTitle` Set to false to disable escaping of title. Takes precedence
	 *   over value of `escape`)
	 * - `confirm` JavaScript confirmation message.
	 *
	 * @param string $title The content to be wrapped by <a> tags.
	 * @param string|array|null $url URL or array of URL parameters, or
	 *   external URL (starts with http://)
	 * @param array $options Array of options and HTML attributes.
	 * @return string An `<a />` element.
	 */
	public function linkReset($title, $url = null, array $options = []) {
		if (is_array($url)) {
			$url += ['prefix' => false, 'plugin' => false];
		}
		return parent::link($title, $url, $options);
	}

	/**
	 * Keep query string params for pagination/filter for this link,
	 * e.g. after edit action.
	 *
	 * ### Options
	 *
	 * - `escape` Set to false to disable escaping of title and attributes.
	 * - `escapeTitle` Set to false to disable escaping of title. Takes precedence
	 *   over value of `escape`)
	 * - `confirm` JavaScript confirmation message.
	 *
	 * @param string $title The content to be wrapped by <a> tags.
	 * @param string|array|null $url URL or array of URL parameters, or
	 *   external URL (starts with http://)
	 * @param array $options Array of options and HTML attributes.
	 * @return string An `<a />` element.
	 * @return string Link
	 */
	public function linkComplete($title, $url = null, array $options = []) {
		if (is_array($url)) {
			// Add query strings
			if (!isset($url['?'])) {
				$url['?'] = [];
			}
			$url['?'] += $this->_View->getRequest()->getQuery();
		}
		return parent::link($title, $url, $options);
	}

}
