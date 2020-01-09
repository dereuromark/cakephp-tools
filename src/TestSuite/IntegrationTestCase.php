<?php

namespace Tools\TestSuite;

use Shim\TestSuite\IntegrationTestCase as ShimIntegrationTestCase;

/**
 * Tools TestCase class
 */
abstract class IntegrationTestCase extends ShimIntegrationTestCase {

	use ToolsTestTrait;

	/**
	 * Globally disabling error handler middleware to see the actual errors instead of cloaking.
	 *
	 * You can enable this when you don't explicitly test exception handling for controllers.
	 *
	 * @var bool
	 */
	protected $disableErrorHandlerMiddleware = false;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if (!$this->disableErrorHandlerMiddleware) {
			return;
		}
		$this->disableErrorHandlerMiddleware();
	}

}
