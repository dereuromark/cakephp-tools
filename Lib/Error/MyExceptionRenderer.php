<?php
App::uses('ExceptionRenderer', 'Error');

class MyExceptionRenderer extends ExceptionRenderer {

	/**
	 * A safer way to render error messages, replaces all helpers, with basics
	 * and doesn't call component methods.
	 *
	 * @param string $template The template to render
	 * @return void
	 */
	protected function _outputMessageSafe($template) {
		$this->controller->layoutPath = null;
		$this->controller->subDir = null;
		$this->controller->viewPath = 'Errors/';
		$this->controller->viewClass = 'View';
		$this->controller->layout = 'error';
		$this->controller->helpers = array('Form', 'Html', 'Session');

		$this->controller->render($template);
		$this->controller->response->type('html');
		
		$x = $this->controller->response->body(); $this->controller->response->body(h($x));
		
		$this->controller->response->send();
	}
	
}
