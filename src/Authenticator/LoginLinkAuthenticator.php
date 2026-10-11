<?php

namespace Tools\Authenticator;

use Authentication\Authenticator\AbstractAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\UrlChecker\UrlCheckerInterface;
use Authentication\UrlChecker\UrlCheckerTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\ServerRequestInterface;

class LoginLinkAuthenticator extends AbstractAuthenticator {

	use UrlCheckerTrait {
		_getUrlChecker as protected createUrlChecker;
	}

	/**
	 * @var array<string, mixed>
	 */
	protected array $defaultConfig = [
		'urlChecker' => null, // e.g. 'Authentication.Default',
		'loginUrl' => null,
		'oneTime' => true,
		'queryString' => 'token',
		'identifierKey' => 'id',
	];

	/**
	 * @return \Authentication\UrlChecker\UrlCheckerInterface
	 */
	protected function _getUrlChecker(): UrlCheckerInterface {
		$checker = $this->getConfig('urlChecker');
		if ($checker === 'Authentication.CakeRouter') {
			$this->setConfig('urlChecker', 'Authentication.Default');
		} elseif (is_array($checker) && ($checker['className'] ?? null) === 'Authentication.CakeRouter') {
			$checker['className'] = 'Authentication.Default';
			$this->setConfig('urlChecker', $checker);
		}

		return $this->createUrlChecker();
	}

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
			return new Result(null, ResultInterface::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentifier()->getErrors());
		}

		return new Result($user, ResultInterface::SUCCESS);
	}

	/**
	 * @param \Cake\Http\ServerRequest $request
	 * @return string|null The token or null.
	 */
	protected function getToken(ServerRequestInterface $request): ?string {
		if ($this->getConfig('urlChecker') !== null && !$this->checkUrl($request)) {
			return null;
		}

		$url = $this->getConfig('loginUrl');
		if ($this->getConfig('urlChecker') === null && $url) {
			if (is_string($url) && $url !== $request->getUri()->getPath()) {
				return null;
			}
			if (is_array($url)) {
				if (empty($url['controller']) || empty($url['action'])) {
					return null;
				}
				$params = (array)$request->getAttribute('params');
				foreach (['plugin', 'prefix', 'controller', 'action'] as $key) {
					if (!array_key_exists($key, $url)) {
						continue;
					}
					$actual = $params[$key] ?? null;
					$expected = $url[$key];
					if (in_array($key, ['plugin', 'prefix'], true)) {
						$actual = $actual ?: null;
						$expected = $expected ?: null;
					}
					if ($actual !== $expected) {
						return null;
					}
				}
			}
		}

		$token = $request->getQuery($this->getConfig('queryString'));

		return is_string($token) ? $token : null;
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

		/** @var \Cake\ORM\Entity $identity */
		$identity = $this->getIdentifier()->identify([$this->getConfig('identifierKey') => $tokenEntity->user_id]);
		$email = $tokenEntity->content;
		if ($email && $identity && $identity->get('email') && $email !== $identity->get('email')) {
			return null;
		}

		return $identity;
	}

}
