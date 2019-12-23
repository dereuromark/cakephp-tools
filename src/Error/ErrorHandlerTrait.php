<?php

namespace Tools\Error;

use Cake\Controller\Exception\AuthSecurityException;
use Cake\Controller\Exception\MissingActionException;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ConflictException;
use Cake\Http\Exception\GoneException;
use Cake\Http\Exception\InvalidCsrfTokenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\MissingControllerException;
use Cake\Http\Exception\NotAcceptableException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Exception\UnavailableForLegalReasonsException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\Exception\MissingViewException;

/**
 * @property array $_options
 */
trait ErrorHandlerTrait {

	/**
	 * List of exceptions that are actually be treated as external 404s.
	 * They should not go into the normal error log, but a separate 404 one.
	 *
	 * @var string[]
	 */
	protected static $blacklist = [
		InvalidPrimaryKeyException::class,
		NotFoundException::class,
		MethodNotAllowedException::class,
		NotAcceptableException::class,
		RecordNotFoundException::class,
		BadRequestException::class,
		GoneException::class,
		ConflictException::class,
		InvalidCsrfTokenException::class,
		UnauthorizedException::class,
		MissingControllerException::class,
		MissingActionException::class,
		MissingRouteException::class,
		MissingViewException::class,
		MissingTemplateException::class,
		UnavailableForLegalReasonsException::class,
		SecurityException::class,
		AuthSecurityException::class,
	];

	/**
	 * By design, these exceptions are also 404 with a valid internal referer.
	 *
	 * @var string[]
	 */
	protected static $evenWithReferer = [
		AuthSecurityException::class,
	];

	/**
	 * @param \Throwable $exception
	 * @param \Psr\Http\Message\ServerRequestInterface|null $request
	 * @return bool
	 */
	protected function is404($exception, $request = null): bool {
		$blacklist = static::$blacklist;
		if (isset($this->_options['log404'])) {
			$blacklist = (array)$this->_options['log404'];
		}
		if (!$blacklist) {
			return false;
		}

		$class = get_class($exception);
		if (!$this->isBlacklisted($class, $blacklist)) {
			return false;
		}

		if (!$request || $this->isBlacklistedEvenWithReferer($class)) {
			return true;
		}
		$referer = $request->getHeaderLine('Referer');
		$baseUrl = Configure::read('App.fullBaseUrl');
		if (strpos($referer, $baseUrl) === 0) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $class
	 * @param string[] $blacklist
	 * @return bool
	 */
	protected function isBlacklisted(string $class, array $blacklist): bool {
		// Quick string comparison first
		if (in_array($class, $blacklist, true)) {
			return true;
		}

		// Deep instance of checking
		foreach ($blacklist as $blacklistedClass) {
			if ($class instanceof $blacklistedClass) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is a 404 even with referer present.
	 *
	 * @param string $class
	 * @return bool
	 */
	protected function isBlacklistedEvenWithReferer(string $class): bool {
		return $this->isBlacklisted($class, static::$evenWithReferer);
	}

}
