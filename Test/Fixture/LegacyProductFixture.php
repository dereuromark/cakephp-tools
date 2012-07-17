<?php

class LegacyProductFixture extends CakeTestFixture {


	public $fields = array(
		'product_id'	=> array('type' => 'integer', 'key' => 'primary'),
		'name'		=> array('type' => 'string', 'length' => 255, 'null' => false),
		'the_company_that_builds_it_id'		=> array('type' => 'integer'),
		'the_company_that_delivers_it_id'		=> array('type' => 'integer')
	);

	public $records = array(
		array('product_id' => 1, 'name' => 'Velocipede', 'the_company_that_builds_it_id' => 1, 'the_company_that_delivers_it_id' => 3),
		array('product_id' => 2, 'name' => 'Oruktor Amphibolos', 'the_company_that_builds_it_id' => 2, 'the_company_that_delivers_it_id' => 2),
	);
}
