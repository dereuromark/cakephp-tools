<?php

class NewsArticleFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false]
	];

	public $records = [
		['id' => 1, 'title' => 'CakePHP the best framework'],
		['id' => 2, 'title' => 'Zend the oldest framework'],
		['id' => 3, 'title' => 'Symfony the engineers framwork'],
		['id' => 4, 'title' => 'CodeIgniter wassat?']
	];
}
