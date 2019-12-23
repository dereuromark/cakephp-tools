<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * For BitmaskedBehaviorTest
 */
class BitmaskedCommentsFixture extends TestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'integer'],
		'article_id' => ['type' => 'integer', 'null' => true],
		'user_id' => ['type' => 'integer', 'null' => true],
		'comment' => 'text',
		'status' => ['type' => 'integer', 'null' => false, 'length' => 2, 'default' => '0'],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = [
		['article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'status' => '0', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'],
		['article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'status' => '1', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'],
		['article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'status' => '2', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'],
		['article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'status' => '3', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'],
		['article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'status' => '4', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'],
		['article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'status' => '5', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'],
		['article_id' => 2, 'user_id' => 3, 'comment' => 'Comment With All Bits set', 'status' => '15', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'],
	];

}
