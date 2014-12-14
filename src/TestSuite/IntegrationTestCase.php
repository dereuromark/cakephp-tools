<?php
namespace Tools\TestSuite;

use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestCase as CakeIntegrationTestCase;

/**
 * Tools TestCase class
 *
 */
abstract class IntegrationTestCase extends CakeIntegrationTestCase {

	use ToolsTestTrait;

	/**
	 * Create a request object with the configured options and parameters.
	 *
	 * Overwrite to allow array URLs.
	 *
	 * @param string|array $url The URL
	 * @param string $method The HTTP method
	 * @param array|null $data The request data.
	 * @return \Cake\Network\Request The built request.
	 */
	protected function _buildRequest($url, $method, $data) {
		if (is_array($url)) {
			$url = Router::url($url);
		}
		return parent::_buildRequest($url, $method, $data);
	}

}
