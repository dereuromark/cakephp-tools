<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;

Router::plugin('Tools', function ($routes) {
	$routes->fallbacks(DashedRoute::class);
});
