<?php

namespace Tools\Identifier;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;

/**
 * Token Identifier
 */
class LoginLinkIdentifier extends AbstractIdentifier {

	use ResolverAwareTrait;

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'idField' => 'id',
		'dataField' => 'id',
		'resolver' => 'Authentication.Orm',
		'preCallback' => null, // Use to set email to active if needed for resolver
	];

	/**
	 * @inheritDoc
	 */
	public function identify(array $credentials): ArrayAccess|array|null {
		$dataField = $this->getConfig('dataField');
		if (!isset($credentials[$dataField])) {
			return null;
		}

		/** @var callable|null $callback */
		$callback = $this->getConfig('preCallback');
		if ($callback !== null) {
			$callback($credentials[$dataField]);
		}

		$conditions = [
			$this->getConfig('idField') => $credentials[$dataField],
		];

		return $this->getResolver()->find($conditions);
	}

}
