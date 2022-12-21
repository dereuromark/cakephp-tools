<?php

namespace Tools\Utility;

use Cake\Core\Configure;
use Cake\Http\Client;
use InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Wrapper class to log data into Gitter API.
 *
 * e.g simple post: curl -d message=hello your_url
 * e.g error levels: curl -d message=oops -d level=error your_url
 * e.g markdown: curl --data-urlencode "message=_markdown_ is fun" your_url
 *
 * Uses {@link \Cake\Http\Client} to make the API call.
 */
class GitterLog {

	/**
	 * @var string
	 */
	protected const URL = 'https://webhooks.gitter.im/e/%s';

	/**
	 * @param string $message
	 * @param string|null $level
	 *
	 * @return void
	 */
	public function write(string $message, ?string $level = null): void {
		$url = sprintf(static::URL, Configure::readOrFail('Gitter.key'));

		$data = [
			'message' => $message,
		];
		if ($level !== null) {
			$levelString = $this->levelString($level);
			$data['level'] = $levelString;
		}

		$options = [];
		$client = $this->getClient();
		$client->post($url, $data, $options);
	}

	/**
	 * @return \Cake\Http\Client
	 */
	protected function getClient(): Client {
		return new Client();
	}

	/**
	 * @param string $level
	 *
	 * @return string
	 */
	protected function levelString(string $level): string {
		if (!in_array($level, [LogLevel::ERROR, LogLevel::INFO], true)) {
			throw new InvalidArgumentException('Only levels `info` and `error`are allowed.');
		}

		return $level;
	}

}
