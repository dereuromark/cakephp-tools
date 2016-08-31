<?php
App::uses('MyModel', 'Tools.Model');

class BitmaskedComment extends MyModel {

	public $validate = [
		'status' => [
			'notBlank' => [
				'rule' => 'notBlank',
				'last' => true
			]
		]
	];

	public static function types($value = null) {
		$options = [
			static::TYPE_BUG => 'Bug',
			static::TYPE_COMPLAINT => 'Complaint',
			static::TYPE_DISCUSSION => 'Discussion',
			static::TYPE_RFC => 'Request for change',
		];
		return static::enum($value, $options);
	}

	public static function statuses($value = null) {
		$options = [
			static::STATUS_ACTIVE => __d('tools', 'Active'),
			static::STATUS_PUBLISHED => __d('tools', 'Published'),
			static::STATUS_APPROVED => __d('tools', 'Approved'),
			static::STATUS_FLAGGED => __d('tools', 'Flagged'),
		];

		return static::enum($value, $options);
	}

	const TYPE_BUG = 0;
	const TYPE_COMPLAINT = 1;
	const TYPE_DISCUSSION = 2;
	const TYPE_RFC = 4;

	const STATUS_NONE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_PUBLISHED = 2;
	const STATUS_APPROVED = 4;
	const STATUS_FLAGGED = 8;

}
