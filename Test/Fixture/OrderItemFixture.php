<?php

class OrderItemFixture extends CakeTestFixture {


	public $fields = array(
		'id'		=> array('type' => 'integer', 'key' => 'primary'),
		'active_shipment_id'	=> array('type' => 'integer'),
	);

	public $records = array(
		array ('id' => 50, 'active_shipment_id' => 320)
	);
}
