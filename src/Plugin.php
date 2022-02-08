<?php

namespace Tools;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for Tools
 */
class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin('Tools', function (RouteBuilder $routes) {
			$routes->fallbacks();
		});

		$routes->prefix('Admin', function (RouteBuilder $routes) {
			$routes->plugin('Tools', function (RouteBuilder $routes) {
				$routes->connect('/', ['controller' => 'Tools', 'action' => 'index']);

				$routes->fallbacks();
			});
		});
	}

}
