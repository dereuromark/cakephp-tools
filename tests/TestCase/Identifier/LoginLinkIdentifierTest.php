<?php

namespace Tools\Test\TestCase\Identifier;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Tools\Identifier\LoginLinkIdentifier;

class LoginLinkIdentifierTest extends TestCase {

	/**
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Tools.ToolsUsers',
	];

	/**
	 * @return void
	 */
	public function testIdentify() {
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();

		$identifier = new LoginLinkIdentifier([
			'resolver' => [
				'className' => 'Authentication.Orm',
				'userModel' => 'ToolsUsers',
			],
		]);
		$result = $identifier->identify(['id' => $user->id]);
		$this->assertInstanceOf(Entity::class, $result);
	}

	/**
	 * @return void
	 */
	public function testIdentifyPreCallback() {
		$user = $this->fetchTable('ToolsUsers')->find()->firstOrFail();
		$this->assertNotEmpty($user->password);

		$identifier = new LoginLinkIdentifier([
			'resolver' => [
				'className' => 'Authentication.Orm',
				'userModel' => 'ToolsUsers',
			],
			'preCallback' => function (int $id) {
				$fields = ['password' => null];
				$conditions = compact('id');
				TableRegistry::getTableLocator()->get('ToolsUsers')->updateAll($fields, $conditions);
			},
		]);
		$result = $identifier->identify(['id' => $user->id]);
		$this->assertInstanceOf(Entity::class, $result);

		$this->assertEmpty($result->password);
	}

}
