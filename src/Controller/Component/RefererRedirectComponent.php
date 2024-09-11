<?php

namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * Uses a referer key in query string to redirect to given referer.
 * Useful for passing to edit forms if you want a different target as redirect than the default.
 * The neat thing here is that it doesn't require changes to existing actions. This can just be
 * added on top, for one or all controllers.
 *
 * @method \App\Controller\AppController getController()
 */
class RefererRedirectComponent extends Component {

	/**
	 * @var string
	 */
	public const QUERY_REFERER = 'ref';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'actions' => [],
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param array|string $url A string or array containing the redirect location
	 * @param \Cake\Http\Response $response The response object.
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function beforeRedirect(EventInterface $event, $url, Response $response) {
		$actions = $this->getConfig('actions');
		$currentAction = $this->getController()->getRequest()->getParam('action');

		if ($currentAction && $actions && !in_array($currentAction, $actions, true)) {
			return null;
		}

		$referer = $this->referer();
		if (!$referer) {
			return null;
		}

		return $response->withLocation($referer);
	}

	/**
	 * Only accept relative URLs.
	 *
	 * @see \Cake\Http\ServerRequest::referer()
	 *
	 * @return string|null
	 */
	protected function referer(): ?string {
		$referer = $this->getController()->getRequest()->getQuery(static::QUERY_REFERER);
		if (!$referer) {
			return null;
		}

		if (is_array($referer)) {
			$referer = Router::url($referer);
		}

		if (!is_string($referer) || !str_starts_with($referer, '/')) {
			return null;
		}

		return $referer;
	}

}
