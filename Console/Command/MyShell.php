<?php

App::uses('Shell', 'Console');

/**
 */
class MyShell extends Shell {

	/**
	 * MyShell::name()
	 *
	 * @param bool $prependPlugin
	 * @return string
	 */
	public function name($prependPlugin = true) {
		$plugin = '';

		$name = $plugin . $this->name;
		return $name;
	}

}
