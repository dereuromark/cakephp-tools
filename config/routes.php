<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('Tools', function (RouteBuilder $routes) {
	$routes->fallbacks();
});
