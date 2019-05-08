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
use Cake\Http\Exception\NotAcceptableException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Exception\UnavailableForLegalReasonsException;
use Cake\Routing\Exception\MissingControllerException;
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
	 * @var array
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
		'Cake\Network\Exception\BadRequestException',
		'Cake\Network\Exception\ConflictException',
		'Cake\Network\Exception\GoneException',
		'Cake\Network\Exception\InvalidCsrfTokenException',
		'Cake\Network\Exception\MethodNotAllowedException',
		'Cake\Network\Exception\NotAcceptableException',
		'Cake\Network\Exception\NotFoundException',
		'Cake\Network\Exception\UnauthorizedException',
		'Cake\Network\Exception\UnavailableForLegalReasonsException',
	];

	/**
	 * By design, these exceptions are also 404 with a valid internal referer.
	 *
	 * @var array
	 */
	protected static $evenWithReferer = [
		AuthSecurityException::class,
	];

	/**
	 * @param \Exception $exception
	 * @param \Psr\Http\Message\ServerRequestInterface|null $request
	 * @return bool
	 */
	protected function is404($exception, $request = null) {
		$blacklist = static::$blacklist;
		if (isset($this->_options['log404'])) {
			$blacklist = $this->_options['log404'];
		}
		if (!$blacklist) {
			return false;
		}

		$class = get_class($exception);
		if (!$this->isBlacklisted($class, (array)$blacklist)) {
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
	protected function isBlacklisted($class, array $blacklist) {
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
	protected function isBlacklistedEvenWithReferer($class) {
		return $this->isBlacklisted($class, static::$evenWithReferer);
	}

}
