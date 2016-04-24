<?php

namespace Tools\Mailer;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Mailer\Mailer as CakeMailer;

/**
 * Allows locale overwrite to send emails in a specific language
 */
class Mailer extends CakeMailer {

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @inheritDoc
	 */
	public function send($action, $args = [], $headers = []) {
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
		$this->locale = I18n::locale();

		$primaryLocale = $this->getPrimaryLocale();
		if ($primaryLocale && $primaryLocale !== $this->locale) {
			I18n::locale($primaryLocale);
		}
	}

	/**
	 * Restore to current locale if applicable.
	 *
	 * @return void
	 */
	protected function restoreLocale() {
		$primaryLocale = $this->getPrimaryLocale();
		if ($primaryLocale && $primaryLocale !== $this->locale) {
			I18n::locale($this->locale);
		}
	}

	/**
	 * Returns the configured default locale.
	 *
	 * Can be based on the primary language and the allowed languages (whitelist).
	 *
	 * @return string
	 */
	protected function getPrimaryLocale() {
		$primaryLanguage = Configure::read('Config.defaultLanguage');
		if (Configure::read('Config.defaultLocale')) {
			return Configure::read('Config.defaultLocale');
		}

		$primaryLocale = Configure::read('Config.allowedLanguages.' . $primaryLanguage . '.locale');
		return $primaryLocale;
	}

}
