<?php
App::uses('AppHelper', 'View/Helper');

/**
 * Helper for hcard, vcard and other microformats!
 *
 * TODO: import http://codeigniter.com/wiki/microformats/
 */
class HcardHelper extends AppHelper {

	public $data = array(
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
	 * TODO
	 */
	public function addressFormatHtml($data, $prefix = false, $format = 'General') {
		$data = $this->filter($data, $prefix);
		$text = $this->style($data, $format);
		$text = '';
		$text .= '<div id="hcard-' . $data['firstname'] . '-' . $data['lastname'] . '" class="vcard">';
		$text .= '<span class="fn">' . $data['firstname'] . ' ' . $data['lastname'] . '</span>';
		$text .= $this->address($data, $format);
		$text .= '</div>';
		return $text;
	}

	/**
	 * TODO
	 */
	public function addressFormatRaw($data, $prefix = false, $format = 'General') {
		$data = $this->filter($data, $prefix);
		$text = $data['firstname'] . ' ' . $data['lastname'] . "\n";
		$text .= $data['address'] . "\n";
		if (Configure::read('Localization.address_format') === 'US') {
			$text .= $data['city'] . ', ' . $data['state'] . ' ' . $data['postcode'] . "\n";
		} else {
			$text .= $data['postcode'] . ' ' . $data['city'] . "\n";
		}
		$text .= $data['country'];
		return $text;
	}

	/**
	 * TODO
	 */
	public function style($data) {
	}

	/**
	 * TODO
	 */
	public function address($data) {
		$text = '<div class="adr">';
		$text .= '<div class="street-address">' . $data['address'] . '</div> ';
		$text .= '<span class="locality">' . $data['city'] . '</span>, ';
		if (!empty($data['state'])) {
			$text .= '<span class="region">' . $data['state'] . '</span> ';
		}
		$text .= '<span class="postal-code">' . $data['postcode'] . '</span> ';
		$text .= '<span class="country-name">' . $data['country'] . '</span> ';
		$text .= '</div>';
		return $text;
	}

	/**
	 * TODO
	 */
	public function filter($data, $prefix = '') {
		if ($prefix) {
			foreach ($data as $key => $row) {
				$data[$prefix . $key] = $data[$key];
			}
		}
		return $data;
	}

}
