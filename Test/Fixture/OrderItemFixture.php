<?php

class OrderItemFixture extends CakeTestFixture {

	public $fields = [
		'id'		=> ['type' => 'integer', 'key' => 'primary'],
		'active_shipment_id'	=> ['type' => 'integer'],
	];

	public $records = [
		['id' => 50, 'active_shipment_id' => 320]
	];
}
