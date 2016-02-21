<?php

namespace Tools\Test\App\Config;

use Cake\Routing\Router;

//Router::extensions(['rss']);

Router::scope('/', function($routes) {
	//$routes->extensions(['rss']);
	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'InflectedRoute']);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
});
