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

	/**
	 * @param \Cake\Form\Schema $schema
	 * @return \Cake\Form\Schema
	 */
	protected function _buildSchema(Schema $schema): Schema {
		return $schema->addField('name', ['type' => 'string', 'length' => 40])
			->addField('email', ['type' => 'string', 'length' => 50])
			->addField('subject', ['type' => 'string', 'length' => 60])
			->addField('body', ['type' => 'text']);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		return $validator
			->requirePresence('name')
			->notEmptyString('name', __d('tools', 'This field cannot be left empty'))
			->requirePresence('email')
			->add('email', 'format', [
					'rule' => 'email',
					'message' => __d('tools', 'A valid email address is required'),
			])
			->requirePresence('subject')
			->notEmptyString('subject', __d('tools', 'This field cannot be left empty'))
			->requirePresence('body')
			->notEmptyString('body', __d('tools', 'This field cannot be left empty'));
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	protected function _execute(array $data): bool {
		// Overwrite in your extending class
		return true;
	}

}
