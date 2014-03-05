<?php
App::uses('AppHelper', 'View/Helper');

/**
 * Helper for hcard, vcard and other microformats!
 *
 * TODO: import http://codeigniter.com/wiki/microformats/
 */
class HcardHelper extends AppHelper {

	protected $_defaults = array(
		'given_name' => 'Firstname',
		'middle_name' => 'Middlename',
		'family_name' => 'Lastname',
		'organization' => 'OrganizationName',
		'street' => '123 Street',
		'city' => 'City',
		'province' => 'Province/State',
		'postal_code' => 'Postal/Zip',
		'country' => 'Country',
		'phone' => 'phonenumber',
		'email' => 'email@yoursite.com',
		'url' => 'http://yoursite.com',
		'aim_screenname' => 'aimname',
		'yim_screenname' => 'yimname',
		'avatar' => '/images/your_photo.png',
		'title' => 'title',
	);

	/**
	 * @return string HTML
	 */
	public function addressFormatHtml($data = null, $format = 'General') {
		if ($data === null) {
			$data = $this->_defaults;
		}
		$text = '';
		$text .= '<div id="hcard-' . $data['given_name'] . '-' . $data['family_name'] . '" class="vcard">';
		$text .= '<span class="fn">' . $data['given_name'] . ' ' . $data['family_name'] . '</span>';
		$text .= $this->address($data, $format);
		$text .= '</div>';
		return $text;
	}

	/**
	 * @return string
	 */
	public function addressFormatRaw($data = null, $format = 'General') {
		if ($data === null) {
			$data = $this->_defaults;
		}
		$text = $data['given_name'] . ' ' . $data['family_name'] . "\n";
		$text .= $data['street'] . "\n";
		if (Configure::read('Localization.addressFormat') === 'US') {
			$text .= $data['city'] . ', ' . $data['province'] . ' ' . $data['postal_code'] . "\n";
		} else {
			$text .= $data['postal_code'] . ' ' . $data['city'] . "\n";
		}
		$text .= $data['country'];
		return $text;
	}

	/**
	 * @return string
	 */
	public function address($data) {
		if ($data === null) {
			$data = $this->_defaults;
		}
		$text = '<div class="adr">';
		$text .= '<div class="street-address">' . $data['street'] . '</div> ';
		$text .= '<span class="locality">' . $data['city'] . '</span>, ';
		if (!empty($data['province'])) {
			$text .= '<span class="region">' . $data['province'] . '</span> ';
		}
		$text .= '<span class="postal-code">' . $data['postal_code'] . '</span> ';
		$text .= '<span class="country-name">' . $data['country'] . '</span> ';
		$text .= '</div>';
		return $text;
	}

}
