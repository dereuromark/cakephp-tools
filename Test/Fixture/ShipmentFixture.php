<?php

class ShipmentFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'ship_date'	=> ['type' => 'date'],
		'order_item_id'	=> ['type' => 'integer']
	];

	public $records = [
		['id' => 320, 'ship_date' => '2011-01-07', 'order_item_id' => 50],
		['id' => 319, 'ship_date' => '2011-01-07', 'order_item_id' => 50],
		['id' => 310, 'ship_date' => '2011-01-07', 'order_item_id' => 50]
	];
}
