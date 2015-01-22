<?php

class LegacyProductFixture extends CakeTestFixture {

	public $fields = [
		'product_id'	=> ['type' => 'integer', 'key' => 'primary'],
		'name'		=> ['type' => 'string', 'length' => 255, 'null' => false],
		'the_company_that_builds_it_id'		=> ['type' => 'integer'],
		'the_company_that_delivers_it_id'		=> ['type' => 'integer']
	];

	public $records = [
		['product_id' => 1, 'name' => 'Velocipede', 'the_company_that_builds_it_id' => 1, 'the_company_that_delivers_it_id' => 3],
		['product_id' => 2, 'name' => 'Oruktor Amphibolos', 'the_company_that_builds_it_id' => 2, 'the_company_that_delivers_it_id' => 2],
	];
}
