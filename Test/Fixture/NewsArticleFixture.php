<?php

class NewsArticleFixture extends CakeTestFixture {

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false)
	);

	public $records = array(
		array('id' => 1, 'title' => 'CakePHP the best framework'),
		array('id' => 2, 'title' => 'Zend the oldest framework'),
		array('id' => 3, 'title' => 'Symfony the engineers framwork'),
		array('id' => 4, 'title' => 'CodeIgniter wassat?')
	);
}
