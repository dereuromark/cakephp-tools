<?php
App::uses('AppController', 'Controller');

class ToolsAppController extends AppController {

	public $components = ['Tools.Common'];

	public $helpers = ['Tools.Common', 'Tools.Format', 'Tools.Datetime', 'Tools.Numeric'];

}
