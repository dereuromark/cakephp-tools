<?php
class Contact extends ToolsAppModel {

	var $name = 'Contact';
	var $useTable = false;
	
	var $validate = array(
		'name' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'valErrMandatoryField'
			)
				),
				'email' => array(
					'email' => array(
						'rule' => array('email', true),
						'message' => 'valErrInvalidEmail'
			),
		), 
		'subject' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'valErrMandatoryField',
						'required' => true
			)
				), 
		'message' => array(
				'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'valErrMandatoryField',
						'required' => true
			)
				),
	);



	function schema() {
				return array(
			'name' => array('type' => 'string' , 'null' => false, 'default' => '', 'length' => '30'),
						'email'    => array('type' => 'string' , 'null' => false, 'default' => '', 'length' => '60'),
						'subject'  => array('type' => 'string' , 'null' => false, 'default' => '', 'length' => '60'),
						'message'  => array('type' => 'text' , 'null' => false, 'default' => ''),
			);
		}



}
?>