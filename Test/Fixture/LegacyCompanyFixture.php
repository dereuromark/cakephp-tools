<?php

class LegacyCompanyFixture extends CakeTestFixture {

	public $fields = [
		'company_id'		=> ['type' => 'integer', 'key' => 'primary'],
		'company_name'		=> ['type' => 'string', 'length' => 255, 'null' => false],
	];

	public $records = [
		['company_id' => 1, 'company_name' => 'Vintage Stuff Manufactory'],
		['company_id' => 2, 'company_name' => 'Modern Steam Cars Inc.'],
		['company_id' => 3, 'company_name' => 'Joe & Co Crate Shipping Company']
	];
}
