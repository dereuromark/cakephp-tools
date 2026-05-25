<?php

use Migrations\BaseMigration;

class AddValidityToToolsTokens extends BaseMigration {

	/**
	 * @return void
	 */
	public function change() {
		$table = $this->table('tokens');
		if (!$table->exists() || $table->hasColumn('validity')) {
			return;
		}

		$table
			->addColumn('validity', 'integer', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->update();
	}

}
