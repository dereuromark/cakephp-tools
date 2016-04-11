<?php

namespace Tools\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * A default ContactForm form fitting most apps.
 * Extend in your app to fill _execute() and to customize
 *
 * @author Mark Scherer
 * @license MIT
 */
class ContactForm extends Form {

	protected function _buildSchema(Schema $schema) {
		return $schema->addField('name', ['type' => 'string', 'length' => 40])
			->addField('email', ['type' => 'string', 'length' => 50])
			->addField('subject', ['type' => 'string', 'length' => 60])
			->addField('body', ['type' => 'text']);
	}

	protected function _buildValidator(Validator $validator) {
		return $validator
			->requirePresence('name')
			->notEmpty('name', __('This field cannot be left empty'))
			->requirePresence('email')
			->add('email', 'format', [
					'rule' => 'email',
					'message' => __('A valid email address is required'),
			])
			->requirePresence('subject')
			->notEmpty('subject', __('This field cannot be left empty'))
			->requirePresence('message')
			->notEmpty('message', __('This field cannot be left empty'));
	}

	protected function _execute(array $data) {
		// Overwrite in your extending class
		return true;
	}

}
