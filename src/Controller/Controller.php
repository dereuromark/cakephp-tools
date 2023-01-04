<?php

namespace Tools\Controller;

use Cake\Core\Configure;
use Cake\Datasource\Paging\PaginatedInterface;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
use Shim\Controller\Controller as ShimController;

/**
 * @property \Tools\Controller\Component\CommonComponent $Common
 * @property \Tools\Controller\Component\UrlComponent $Url
 */
class Controller extends ShimController {

	/**
	 * @var array<string>
	 */
	protected array $autoRedirectActions = [];

	/**
	 * Handles automatic pagination of model records.
	 *
	 * @override To support defaults like limit etc.
	 *
	 * @param \Cake\Datasource\RepositoryInterface|\Cake\Datasource\QueryInterface|string|null $object Table to paginate
	 * (e.g: Table instance, 'TableName' or a Query object)
	 * @param array<string, mixed> $settings The settings/configuration used for pagination. See {@link \Cake\Controller\Controller::$paginate}.
	 * @return \Cake\Datasource\Paging\PaginatedInterface
	 */
	public function paginate(
		RepositoryInterface|QueryInterface|string|null $object = null,
		array $settings = [],
	): PaginatedInterface {
		$defaultSettings = (array)Configure::read('Paginator');
		if ($defaultSettings) {
			$this->paginate += $defaultSettings;
		}

		return parent::paginate($object, $settings);
	}

}
