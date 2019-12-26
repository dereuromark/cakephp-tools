<?php

namespace Tools\Test\App\Config;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks(DashedRoute::class);
});

require ROOT . DS . 'config' . DS . 'routes.php';
