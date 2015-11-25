<?php
App::uses('ShimIntegrationTestCase', 'Shim.TestSuite');
App::uses('Router', 'Routing');
App::uses('Dispatcher', 'Routing');
App::uses('EventManager', 'Event');
App::uses('CakeSession', 'Model/Datasource');

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
abstract class IntegrationTestCase extends ShimIntegrationTestCase {
}
