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

}
