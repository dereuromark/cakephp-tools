<?php
App::uses('AppController', 'Controller');

class ToolsAppController extends AppController {

	public $components = array('Session', 'Tools.Common');

	public $helpers = array('Html', 'Form', 'Session', 'Tools.Common', 'Tools.Format', 'Tools.Datetime', 'Tools.Numeric');

}
