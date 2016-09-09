<?php
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::plugin('Tools', function ($routes) {
	$routes->fallbacks(DashedRoute::class);
});
