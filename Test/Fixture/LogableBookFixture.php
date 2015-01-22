<?php
class LogableBookFixture extends CakeTestFixture {

	public $fields = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'title' => ['type' => 'string', 'length' => 255, 'null' => false],
		'weight' => ['type' => 'integer', 'null' => false]
	];

	public $records = [
		['id' => 3, 'title' => 'Sixth Book', 'weight' => 6 ],
		['id' => 6, 'title' => 'Fifth Book', 'weight' => 5 ],
		['id' => 2, 'title' => 'First Book', 'weight' => 1 ],
		['id' => 1, 'title' => 'Second Book', 'weight' => 2 ],
		['id' => 4, 'title' => 'Third Book', 'weight' => 3 ],
		['id' => 5, 'title' => 'Fourth Book', 'weight' => 4 ]
	];
}
