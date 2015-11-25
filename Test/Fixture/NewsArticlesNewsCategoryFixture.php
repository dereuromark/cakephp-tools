<?php

class NewsArticlesNewsCategoryFixture extends CakeTestFixture {

	public $fields = [
		'article_id' => ['type' => 'integer', 'key' => 'primary'],
		'category_id' => ['type' => 'integer', 'key' => 'primary'],
	];

	public $records = [
		['article_id' => 1, 'category_id' => 1],
		['article_id' => 1, 'category_id' => 2],
		['article_id' => 2, 'category_id' => 2],
		['article_id' => 2, 'category_id' => 3],
		['article_id' => 4, 'category_id' => 3],
	];
}
