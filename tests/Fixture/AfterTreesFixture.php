<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AfterTreeFixture class
 */
class AfterTreesFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'parent_id' => ['type' => 'integer'],
		'lft' => ['type' => 'integer'],
		'rght' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public $records = [
		['parent_id' => null, 'lft' => 1, 'rght' => 2, 'name' => 'One'],
		['parent_id' => null, 'lft' => 3, 'rght' => 4, 'name' => 'Two'],
		['parent_id' => null, 'lft' => 5, 'rght' => 6, 'name' => 'Three'],
		['parent_id' => null, 'lft' => 7, 'rght' => 12, 'name' => 'Four'],
		['parent_id' => null, 'lft' => 8, 'rght' => 9, 'name' => 'Five'],
		['parent_id' => null, 'lft' => 10, 'rght' => 11, 'name' => 'Six'],
		['parent_id' => null, 'lft' => 13, 'rght' => 14, 'name' => 'Seven'],
	];

}
