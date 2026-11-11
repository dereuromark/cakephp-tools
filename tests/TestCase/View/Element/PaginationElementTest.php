<?php

namespace Tools\Test\TestCase\View\Element;

use ArrayIterator;
use Cake\Datasource\Paging\PaginatedResultSet;
use Cake\Http\ServerRequest;
use Cake\View\View;
use Shim\TestSuite\TestCase;

class PaginationElementTest extends TestCase {

	/**
	 * @return void
	 */
	public function testCspScriptNonce(): void {
		$result = $this->renderElement('cspScriptNonce', 'core-nonce');

		$this->assertStringContainsString('<script nonce="core-nonce">', $result);
		$this->assertDoesNotMatchRegularExpression('/<script(?![^>]*nonce=)/', $result);
	}

	/**
	 * @return void
	 */
	public function testCspNonceFallback(): void {
		$result = $this->renderElement('cspNonce', 'custom-nonce');

		$this->assertStringContainsString('<script nonce="custom-nonce">', $result);
		$this->assertDoesNotMatchRegularExpression('/<script(?![^>]*nonce=)/', $result);
	}

	/**
	 * @param string $attribute
	 * @param string $nonce
	 * @return string
	 */
	protected function renderElement(string $attribute, string $nonce): string {
		$request = (new ServerRequest(['url' => '/items']))->withAttribute($attribute, $nonce);
		$view = new View($request);
		$view->set('items', new PaginatedResultSet(new ArrayIterator([]), [
			'count' => 0,
			'totalCount' => 0,
			'perPage' => 20,
			'pageCount' => 1,
			'currentPage' => 1,
			'start' => 0,
			'end' => 0,
			'hasPrevPage' => false,
			'hasNextPage' => false,
			'alias' => 'Items',
			'scope' => null,
			'sort' => null,
			'direction' => null,
			'sortDefault' => false,
			'directionDefault' => false,
			'completeSort' => [],
			'limit' => null,
			'requestedPage' => 1,
		]));

		return $view->element('Tools.pagination');
	}

}
