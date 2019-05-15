<?php

namespace Tools\Test\App\Config;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks();
});
Router::plugin('Tools', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
