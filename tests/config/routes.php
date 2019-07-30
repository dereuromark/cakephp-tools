<?php

namespace Tools\Test\App\Config;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks();
});

require ROOT . DS . 'config' . DS . 'routes.php';
