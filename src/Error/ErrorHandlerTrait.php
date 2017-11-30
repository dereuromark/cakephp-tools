<?php

namespace Tools\Error;

use Cake\Controller\Exception\MissingActionException;
use Cake\Core\Configure;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\ConflictException;
use Cake\Network\Exception\GoneException;
use Cake\Network\Exception\InvalidCsrfTokenException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Network\Exception\NotAcceptableException;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\UnavailableForLegalReasonsException;
use Cake\Routing\Exception\MissingControllerException;
use Cake\Routing\Exception\MissingRouteException;
use Cake\View\Exception\MissingViewException;

/**
 * @property array $_options
 */
trait ErrorHandlerTrait {

	/**
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
		UnavailableForLegalReasonsException::class,
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

		$referer = $request->getHeaderLine('Referer');
		$baseUrl = Configure::read('App.fullBaseUrl');
		if (strpos($referer, $baseUrl) === 0) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $class
	 * @param array $blacklist
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

}
