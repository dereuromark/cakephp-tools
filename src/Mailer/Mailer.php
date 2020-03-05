<?php

namespace Tools\Mailer;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Mailer\Mailer as CakeMailer;

/**
 * Allows locale overwrite to send emails in a specific language
 *
 * @method string addEmbeddedAttachment(string $file, ?string $name = null, array $options = [])
 * @method string addEmbeddedBlobAttachment(string $file, ?string $name = null, array $options = [])
 * @method string addEmbeddedAttachmentByContentId(string $file, ?string $name = null, array $options = [])
 * @method string addEmbeddedBlobAttachmentByContentId(string $file, ?string $name = null, array $options = [])
 */
class Mailer extends CakeMailer {

	/**
	 * Message class name.
	 *
	 * @var string
	 */
	protected $messageClass = Message::class;

	/**
	 * @var string|null
	 */
	protected $locale;

	/**
	 * @var string
	 */
	protected $localeBefore;

	/**
	 * Magic method to forward method class to Message instance.
	 *
	 * @param string $method Method name.
	 * @param array $args Method arguments
	 * @return \Cake\Mailer\Mailer|mixed
	 */
	public function __call(string $method, array $args) {
		$result = $this->message->$method(...$args);
		if (strpos($method, 'get') === 0 || strpos($method, 'add') === 0) {
			return $result;
		}

		return $this;
	}

	/**
	 * @param string $locale
	 *
	 * @return $this
	 */
	public function setLocale(string $locale) {
		$this->locale = $locale;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getLocale(): ?string {
		return $this->locale;
	}

	/**
	 * Validate if the email has the required fields necessary to make send() work.
	 * Assumes layouting (does not check on content to be present or if view/layout files are missing).
	 *
	 * @return bool Success
	 */
	public function validates() {
		if ($this->getMessage()->getSubject() && $this->getMessage()->getTo()) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function send(?string $action = null, array $args = [], array $headers = []): array {
		$this->fixateLocale();

		$result = parent::send($action, $args, $headers);

		$this->restoreLocale();

		return $result;
	}

	/**
	 * Switch to primary locale if applicable.
	 *
	 * @return void
	 */
	protected function fixateLocale() {
		$this->localeBefore = I18n::getLocale();

		if ($this->locale) {
			I18n::setLocale($this->locale);
			return;
		}

		$primaryLocale = $this->detectPrimaryLocale();
		if ($primaryLocale) {
			I18n::setLocale($primaryLocale);
		}
	}

	/**
	 * Restore to current locale if applicable.
	 *
	 * @return void
	 */
	protected function restoreLocale() {
		I18n::setLocale($this->localeBefore);
	}

	/**
	 * Returns the configured default locale.
	 *
	 * Can be based on the primary language and the allowed languages (whitelist).
	 *
	 * @throws \RuntimeException
	 * @return string|null
	 */
	protected function detectPrimaryLocale(): ?string {
		if (Configure::read('Config.defaultLocale')) {
			return Configure::read('Config.defaultLocale');
		}

		$primaryLanguage = Configure::read('Config.defaultLanguage');
		$primaryLocale = Configure::read('Config.allowedLanguages.' . $primaryLanguage . '.locale');

		return $primaryLocale;
	}

	/**
	 * @return $this
	 */
	public function reset() {
		$this->locale = null;

		return parent::reset();
	}

}
