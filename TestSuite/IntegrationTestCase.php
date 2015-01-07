<?php
App::uses('MyControllerTestCase', 'Tools.TestSuite');
App::uses('Router', 'Routing');
App::uses('Dispatcher', 'Routing');
App::uses('EventManager', 'Event');

/**
 * A test case class intended to make integration tests of
 * your controllers easier.
 *
 * This class has been backported from 3.0.
 * Does not support cookies or non 2xx/3xx responses yet, though.
 *
 * This test class provides a number of helper methods and features
 * that make dispatching requests and checking their responses simpler.
 * It favours full integration tests over mock objects as you can test
 * more of your code easily and avoid some of the maintenance pitfalls
 * that mock objects create.
 */
abstract class IntegrationTestCase extends MyControllerTestCase {

	/**
	 * The data used to build the next request.
	 * Use the headers key to set specific $_ENV headers.
	 *
	 * @var array
	 */
	protected $_requestData = [];

	/**
	 * Session data to use in the next request.
	 *
	 * @var array
	 */
	protected $_sessionData = [];

/**
 * Configure the data for the *next* request.
 *
 * This data is cleared in the tearDown() method.
 *
 * You can call this method multiple times to append into
 * the current state.
 *
 * @param array $data The request data to use.
 * @return void
 */
	public function configRequest(array $data) {
		$this->_requestData = $data + $this->_requestData;
	}

/**
 * Set session data.
 *
 * This method lets you configure the session data
 * you want to be used for requests that follow. The session
 * state is reset in each tearDown().
 *
 * You can call this method multiple times to append into
 * the current state.
 *
 * @param array $data The session data to use.
 * @return void
 */
	public function session(array $data) {
		$this->_sessionData = $data + $this->_sessionData;
	}

/**
 * Perform a GET request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @return void
 */
	public function get($url) {
		return $this->_sendRequest($url, 'GET');
	}

/**
 * Perform a POST request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function post($url, $data = []) {
		return $this->_sendRequest($url, 'POST', $data);
	}

/**
 * Perform a PATCH request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function patch($url, $data = []) {
		return $this->_sendRequest($url, 'PATCH', $data);
	}

/**
 * Perform a PUT request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function put($url, $data = []) {
		return $this->_sendRequest($url, 'PUT', $data);
	}

/**
 * Perform a DELETE request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @return void
 */
	public function delete($url) {
		return $this->_sendRequest($url, 'DELETE');
	}

/**
 * Create and send the request into a Dispatcher instance.
 *
 * Receives and stores the response for future inspection.
 *
 * @param string $url The url
 * @param string $method The HTTP method
 * @param array|null $data The request data.
 * @return mixed
 * @throws \Exception
 */
	protected function _sendRequest($url, $method, $data = []) {
		$options = array(
			'data' => $data,
			'method' => $method,
			'return' => 'vars'
		);

		$env = array();
		if (isset($this->_requestData['headers'])) {
			foreach ($this->_requestData['headers'] as $k => $v) {
				$env['HTTP_' . str_replace('-', '_', strtoupper($k))] = $v;
			}
			unset($this->_requestData['headers']);
		}

		CakeSession::write($this->_sessionData);
		$envBackup = array();
		foreach ($env as $k => $v) {
			$envBackup[$k] = isset($_ENV[$k]) ? $_ENV[$k] : null;
			$_ENV[$k] = $v;
		}

		$result = $this->testAction($url, $options);

		foreach ($env as $k => $v) {
			$_ENV[$k] = $envBackup[$k];
		}

		$this->_response = $this->controller->response;
		$this->_request = $this->controller->request;
		$this->_requestSession = $this->controller->Session;

		return $result;
	}

/**
 * Fetch a view variable by name.
 *
 * If the view variable does not exist null will be returned.
 *
 * @param string $name The view variable to get.
 * @return mixed The view variable if set.
 */
	public function viewVariable($name) {
		if (empty($this->controller->viewVars)) {
			$this->fail('There are no view variables, perhaps you need to run a request?');
		}
		if (isset($this->controller->viewVars[$name])) {
			return $this->controller->viewVars[$name];
		}
		return null;
	}

/**
 * Assert that the response status code is in the 2xx range.
 *
 * @return void
 */
	public function assertResponseOk() {
		$this->_assertStatus(200, 204, 'Status code is not between 200 and 204');
	}

/**
 * Assert that the response status code is in the 4xx range.
 *
 * @return void
 */
	public function assertResponseError() {
		$this->_assertStatus(400, 417, 'Status code is not between 400 and 417');
	}

/**
 * Assert that the response status code is in the 5xx range.
 *
 * @return void
 */
	public function assertResponseFailure() {
		$this->_assertStatus(500, 505, 'Status code is not between 500 and 505');
	}

/**
 * Asserts a specific response status code.
 *
 * @param int $code Status code to assert.
 * @return void
 */
	public function assertResponseCode($code) {
		$actual = $this->_response->statusCode();
		$this->_assertStatus($code, $code, 'Status code is not ' . $code . ' but ' . $actual);
	}

/**
 * Helper method for status assertions.
 *
 * @param int $min Min status code.
 * @param int $max Max status code.
 * @param string $message The error message.
 * @return void
 */
	protected function _assertStatus($min, $max, $message) {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert status code.');
		}
		$status = $this->_response->statusCode();
		$this->assertGreaterThanOrEqual($min, $status, $message);
		$this->assertLessThanOrEqual($max, $status, $message);
	}

/**
 * Assert that the Location header is correct.
 *
 * @param string|array $url The url you expected the client to go to. This
 *   can either be a string URL or an array compatible with Router::url()
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertRedirect($url, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert location header. ' . $message);
		}
		$result = $this->_response->header();
		if (empty($result['Location'])) {
			$this->fail('No location header set. ' . $message);
		}
		$this->assertEquals(Router::url($url, true), $result['Location'], $message);
	}

/**
 * Asserts that the Location header is correct.
 *
 * @param string|array $url The url you expected the client to go to. This
 *   can either be a string URL or an array compatible with Router::url()
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertNoRedirect($message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert location header. ' . $message);
		}
		$result = $this->_response->header();
		if (!$message) {
			$message = 'Redirect header set';
		}
		if (!empty($result['Location'])) {
			$message .= ': ' . $result['Location'];
		}
		$this->assertTrue(empty($result['Location']), $message);
	}

/**
 * Assert response headers
 *
 * @param string $header The header to check
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertHeader($header, $content, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert headers. ' . $message);
		}
		$headers = $this->_response->header();
		if (!isset($headers[$header])) {
			$this->fail("The '$header' header is not set. " . $message);
		}
		$this->assertEquals($headers[$header], $content, $message);
	}

/**
 * Assert content type
 *
 * @param string $type The content-type to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertContentType($type, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert content-type. ' . $message);
		}
		$alias = $this->_response->getMimeType($type);
		if ($alias !== false) {
			$type = $alias;
		}
		$result = $this->_response->type();
		$this->assertEquals($type, $result, $message);
	}

/**
 * Assert content exists in the response body.
 *
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertResponseContains($content, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert content. ' . $message);
		}
		$this->assertContains($content, $this->_response->body(), $message);
	}

/**
 * Assert content does not exist in the response body.
 *
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertResponseNotContains($content, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert content. ' . $message);
		}
		$this->assertNotContains($content, $this->_response->body(), $message);
	}

/**
 * Assert that the search string was in the template name.
 *
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertTemplate($content, $message = '') {
		if (!$this->_viewName) {
			$this->fail('No view name stored. ' . $message);
		}
		$this->assertContains($content, $this->_viewName, $message);
	}

/**
 * Assert that the search string was in the layout name.
 *
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertLayout($content, $message = '') {
		if (!$this->_layoutName) {
			$this->fail('No layout name stored. ' . $message);
		}
		$this->assertContains($content, $this->_layoutName, $message);
	}

/**
 * Assert session contents
 *
 * @param string $expected The expected contents.
 * @param string $path The session data path. Uses Hash::get() compatible notation
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertSession($expected, $path, $message = '') {
		if (empty($this->_requestSession)) {
			$this->fail('There is no stored session data. Perhaps you need to run a request?');
		}
		$result = $this->_requestSession->read($path);
		$this->assertEquals($expected, $result, 'Session content differs. ' . $message);
	}

/**
 * Assert cookie values
 *
 * @param string $expected The expected contents.
 * @param string $name The cookie name.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertCookie($expected, $name, $message = '') {
		if (empty($this->_response)) {
			$this->fail('Not response set, cannot assert cookies.');
		}
		$result = $this->_response->cookie($name);
		$this->assertEquals($expected, $result['value'], 'Cookie data differs. ' . $message);
	}

}
