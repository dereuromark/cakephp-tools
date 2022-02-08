<?php

namespace Tools\Test\App\Config;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks(DashedRoute::class);
});

Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Tools', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Tools', 'action' => 'index']);

		$routes->fallbacks();
	});
});
