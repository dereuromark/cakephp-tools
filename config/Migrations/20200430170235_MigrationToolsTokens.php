<?php

use Migrations\AbstractMigration;

class MigrationToolsTokens extends AbstractMigration {

	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
	 *
	 * @return void
	 */
	public function change() {
		if ($this->table('tokens')->exists()) {
			$this->table('tokens')
				->renameColumn('key', 'token_key')
				->update();

			return;
		}

		$this->table('tokens')
			->addColumn('user_id', 'integer', [
				'limit' => null,
				'null' => true,
			])
			->addColumn('type', 'string', [
				'comment' => 'e.g.:activate,reactivate',
				'default' => null,
				'limit' => 20,
				'null' => false,
			])
			->addColumn('token_key', 'string', [
				'default' => null,
				'limit' => 60,
				'null' => false,
			])
			->addColumn('content', 'string', [
				'comment' => 'can transport some information',
				'default' => null,
				'limit' => 255,
				'null' => true,
			])
			->addColumn('used', 'integer', [
				'default' => 0,
				'limit' => null,
				'null' => false,
			])
			->addColumn('created', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addColumn('modified', 'datetime', [
				'default' => null,
				'limit' => null,
				'null' => true,
			])
			->addIndex(['user_id'])
			->addIndex(['token_key'], ['unique' => true])
			->create();
	}

}
