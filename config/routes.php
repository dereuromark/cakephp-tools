<?php
use Cake\Routing\Router;

Router::plugin('Tools', function ($routes) {
	$routes->fallbacks('DashedRoute');
});
