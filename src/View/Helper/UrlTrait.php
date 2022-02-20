<?php

namespace Tools\View\Helper;

trait UrlTrait {

	/**
	 * @param array $url
	 * @return array
	 */
	public function resetArray(array $url): array {
		$url += $this->defaults();

		return $url;
	}

	/**
	 * @param array $url
	 * @return array
	 */
	public function completeArray(array $url): array {
		$url = $this->addQueryStrings($url);
		$url = $this->addPassed($url);

		return $url;
	}

	/**
	 * Creates a reset URL.
	 * The prefix and plugin params are resetting to default false.
	 *
	 * Can only add defaults for array URLs.
	 *
	 * @param array|string|null $url URL.
	 * @param array<string, mixed> $options
	 * @return string Full translated URL with base path.
	 */
	public function buildReset($url, array $options = []): string {
		if (is_array($url)) {
			$url += $this->defaults();
		}

		return $this->build($url, $options);
	}

	/**
	 * Returns a URL based on provided parameters.
	 *
	 * Can only add query strings for array URLs.
	 *
	 * @param array|string|null $url URL.
	 * @param array<string, mixed> $options
	 * @return string Full translated URL with base path.
	 */
	public function buildComplete($url, array $options = []): string {
		if (is_array($url)) {
			$url = $this->addQueryStrings($url);
		}

		return $this->build($url, $options);
	}

	/**
	 * @return array
	 */
	public function defaults(): array {
		return [
			'prefix' => false,
			'plugin' => false,
		];
	}

	/**
	 * @param array $url
	 *
	 * @return array
	 */
	protected function addQueryStrings(array $url): array {
		if (!isset($url['?'])) {
			$url['?'] = [];
		}
		$url['?'] += $this->_View->getRequest()->getQuery();

		return $url;
	}

	/**
	 * @param array $url
	 *
	 * @return array
	 */
	protected function addPassed(array $url) {
		$pass = $this->_View->getRequest()->getParam('pass');
		$url = array_merge($url, $pass);

		return $url;
	}

}
