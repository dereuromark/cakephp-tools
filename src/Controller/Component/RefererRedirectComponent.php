<?php

namespace Tools\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Uses a referer key in query string to redirect to given referer.
 * Useful for passing to edit forms if you want a different target as redirect than the default.
 * The neat thing here is that it doesn't require changes to existing actions. This can just be
 * added on top, for one or all controllers.
 */
class RefererRedirectComponent extends Component {

	const QUERY_REFERER = 'ref';

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'actions' => [],
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param string|array $url A string or array containing the redirect location
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
	protected function referer() {
		$referer = $this->getController()->getRequest()->getQuery(static::QUERY_REFERER);
		if (!$referer) {
			return null;
		}

		if (strpos($referer, '/') !== 0) {
			return null;
		}

		return $referer;
	}

}
