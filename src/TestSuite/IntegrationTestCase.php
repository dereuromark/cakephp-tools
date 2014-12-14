<?php
namespace Tools\TestSuite;

use Cake\TestSuite\IntegrationTestCase as CakeIntegrationTestCase;
use Cake\Routing\Router;
use Tools\TestSuite\ToolsTestTrait;

/**
 * Tools TestCase class
 *
 */
abstract class IntegrationTestCase extends CakeIntegrationTestCase {

	use ToolsTestTrait;

}
