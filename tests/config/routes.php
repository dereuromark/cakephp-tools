<?php

namespace Tools\Test\App\Config;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/**
 * @var \Cake\Routing\RouteBuilder $routes
 */
//$routes->def(DashedRoute::class);

$routes->scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks(DashedRoute::class);
});

$routes->prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Tools', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Tools', 'action' => 'index']);

		$routes->fallbacks();
	});
});
