<?php

class NewsArticlesNewsCategoryFixture extends CakeTestFixture {

	public $fields = array(
		'article_id' => array('type' => 'integer', 'key' => 'primary'),
		'category_id' => array('type' => 'integer', 'key' => 'primary'),
	);

	public $records = array(
		array('article_id' => 1, 'category_id' => 1),
		array('article_id' => 1, 'category_id' => 2),
		array('article_id' => 2, 'category_id' => 2),
		array('article_id' => 2, 'category_id' => 3),
		array('article_id' => 4, 'category_id' => 3),
	);
}
