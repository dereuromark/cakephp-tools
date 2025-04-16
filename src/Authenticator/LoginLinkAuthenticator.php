<?php

namespace Tools\Authenticator;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ServerRequestInterface;

class LoginLinkAuthenticator extends AbstractAuthenticator {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'url' => null,
		'oneTime' => true,
		'queryString' => 'token',
	];

	/**
	 * @param \Cake\Http\ServerRequest $request The request that contains login information.
	 * @return \Authentication\Authenticator\ResultInterface
	 */
	public function authenticate(ServerRequestInterface $request): ResultInterface {
		$token = $this->getToken($request);
		if (!$token) {
			return new Result(null, ResultInterface::FAILURE_CREDENTIALS_MISSING);
		}

		$user = $this->getUserFromToken($token);

		if (!$user) {
			return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
		}

		return new Result($user, ResultInterface::SUCCESS);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request
	 * @return string|null The token or null.
	 */
	protected function getToken(ServerRequestInterface $request): ?string {
		/** @var array<string, mixed> $url */
		$url = $this->getConfig('url');
		if ($url) {
			$params = $request->getQuery('params');
			if ($params['controller'] !== $url['controller'] || $params['action'] !== $url['action']) {
				return null;
			}
		}

		return $request->getQuery('token');
	}

	/**
	 * @param string $token
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function getUserFromToken(string $token): ?EntityInterface {
		/** @var \Tools\Model\Table\TokensTable $tokensTable */
		$tokensTable = TableRegistry::getTableLocator()->get('Tools.Tokens');
		$tokenEntity = $tokensTable->useKey('login_link', $token, null, (bool)$this->getConfig('oneTime'));
		if (!$tokenEntity) {
			return null;
		}

		$sub = $tokenEntity->user_id;
		/** @var \Cake\ORM\Entity $identity */
		$identity = $this->_identifier->identify(compact('sub'));

		$email = $tokenEntity->content;
		if ($email && $identity->get('email') && $email !== $identity->get('email')) {
			return null;
		}

		return $identity;
	}

}
