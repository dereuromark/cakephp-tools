<?php

namespace Tools\View\Helper;

trait HtmlTrait {

	/**
	 * Display image tag from blob content.
	 * Enhancement for HtmlHelper. Defaults to png image
	 *
	 * Options:
	 * - type: png, gif, jpg, ...
	 *
	 * @param string $content Data in binary form
	 * @param array<string, mixed> $options Attributes
	 * @return string HTML image tag
	 */
	public function imageFromBlob(string $content, array $options = []): string {
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
	 * @param array|string $title The content to be wrapped by <a> tags.
	 * @param array|string|null $url URL or array of URL parameters, or
	 *   external URL (starts with http://)
	 * @param array<string, mixed> $options Array of options and HTML attributes.
	 * @return string An `a` HTML element.
	 */
	public function linkReset(array|string $title, array|string|null $url = null, array $options = []): string {
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
	 * @param array|string $title The content to be wrapped by <a> tags.
	 * @param array|string|null $url URL or array of URL parameters, or
	 *   external URL (starts with http://)
	 * @param array<string, mixed> $options Array of options and HTML attributes.
	 * @return string An `a` HTML element.
	 */
	public function linkComplete(array|string $title, array|string|null $url = null, array $options = []): string {
		if (is_array($url)) {
			// Add query strings
			if (!isset($url['?'])) {
				$url['?'] = [];
			}
			$url['?'] += $this->_View->getRequest()->getQuery();

			$pass = $this->_View->getRequest()->getParam('pass');
			$url = array_merge($url, $pass);
		}

		return parent::link($title, $url, $options);
	}

}
