<?php
/**
 * For BitmaskedBehaviorTest
 *
 */
class BitmaskedCommentFixture extends CakeTestFixture {

	/**
	 * Fields property
	 *
	 * @var array
	 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'article_id' => array('type' => 'integer', 'null' => false),
		'user_id' => array('type' => 'integer', 'null' => false),
		'comment' => 'text',
		'status' => array('type' => 'integer', 'null' => false, 'length' => 2, 'default' => '0'),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

	/**
	 * Records property
	 *
	 * @var array
	 */
	public $records = array(
		array('article_id' => 1, 'user_id' => 2, 'comment' => 'First Comment for First Article', 'status' => '0', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
		array('article_id' => 1, 'user_id' => 4, 'comment' => 'Second Comment for First Article', 'status' => '1', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
		array('article_id' => 1, 'user_id' => 1, 'comment' => 'Third Comment for First Article', 'status' => '2', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
		array('article_id' => 1, 'user_id' => 1, 'comment' => 'Fourth Comment for First Article', 'status' => '3', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
		array('article_id' => 2, 'user_id' => 1, 'comment' => 'First Comment for Second Article', 'status' => '4', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
		array('article_id' => 2, 'user_id' => 2, 'comment' => 'Second Comment for Second Article', 'status' => '5', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'),
		array('article_id' => 2, 'user_id' => 3, 'comment' => 'Comment With All Bits set', 'status' => '15', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31')
	);
}
