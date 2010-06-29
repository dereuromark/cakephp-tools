<?php

class GeocoderBehavior extends ModelBehavior {
	/**
	 * Contain settings indexed by model name.
	 *
	 * @var array
	 * @access private
	 */
	var $__settings = array();

	var $log = true; // log successfull results to geocode.log (errors will be logged to error.log in either case)

/*
accuracy:
0 	Unbekannter Ort. (Seit 2.59)
1 	Land. (Seit 2.59)
2 	Bundesland/Bundesstaat, Provinz, Präfektur usw. (Seit 2.59)
3 	Bezirk, Gemeinde usw. (Seit 2.59)
4 	Ortschaft (Stadt, Dorf). (Seit 2.59)
5 	Postleitzahl (PLZ). (Seit 2.59)
6 	Straße. (Seit 2.59)
7 	Kreuzung. (Seit 2.59)
8 	Adresse. (Seit 2.59)
*/
	/**
	 * Initiate behavior for the model using specified settings. Available settings:
	 *
	 * - label: 	(array | string, optional) set to the field name that contains the
	 * 				string from where to generate the slug, or a set of field names to
	 * 				concatenate for generating the slug. DEFAULTS TO: title
	 *
	 * - real:		(boolean, optional) if set to true then field names defined in
	 * 				label must exist in the database table. DEFAULTS TO: true
	 *
	 * - accuracy: see above
	 *
	 * @param object $Model Model using the behaviour
	 * @param array $settings Settings to override for model.
	 * @access public
	 */
	function setup(&$Model, $settings = array()) {
		$default = array('real' => true, 'address' => array('street','zip','city'), 'lat'=>'lat','lng'=>'lng','formatted_address' => null, 'min_accuracy' => 4, 'allow_inconclusive' => false, 'host' => 'us', 'language' => 'de', 'region'=> '', 'bounds' => '', 'overwrite' => false);

		if (!isset($this->__settings[$Model->alias])) {
			$this->__settings[$Model->alias] = $default;
		}

		$this->__settings[$Model->alias] = array_merge($this->__settings[$Model->alias], ife(is_array($settings), $settings, array()));
	}

	/**
	 * Run before a model is saved, used to set up slug for model.
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean true if save should proceed, false otherwise
	 * @access public
	 */
	function beforeSave(&$Model) {
		$return = parent::beforeSave($Model);

		// Make address fields an array

		if (!is_array($this->__settings[$Model->alias]['address'])) {
			$addressfields = array($this->__settings[$Model->alias]['address']);
		} else {
			$addressfields = $this->__settings[$Model->alias]['address'];
		}
		$addressfields = array_unique($addressfields);

		// Make sure all address fields are available

		if ($this->__settings[$Model->alias]['real']) {
			foreach($addressfields as $field) {
				if (!$Model->hasField($field)) {
					return $return;
				}
			}
		}

		$adressdata = array();
		foreach($addressfields as $field) {
			if (!empty($Model->data[$Model->alias][$field])) {
					$adressdata[] = ife(!empty($label), ' ', '') . $Model->data[$Model->alias][$field];
			}
		}

		// See if we should request a geocode
		if ((!$this->__settings[$Model->alias]['real'] || ($Model->hasField($this->__settings[$Model->alias]['lat']) && $Model->hasField($this->__settings[$Model->alias]['lng']))) && ($this->__settings[$Model->alias]['overwrite'] || (empty($Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']]) || ($Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']]==0 && $Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']]==0)))) {
			$geocode = $this->__geocode($adressdata, $this->__settings[$Model->alias]);
			// Now set the geocode as part of the model data to be saved, making sure that
			// we are on the white list of fields to be saved
			//pr ($Model->whitelist); die();
			if (!empty($Model->whitelist) && (!in_array($this->__settings[$Model->alias]['lat'], $Model->whitelist) || !in_array($this->__settings[$Model->alias]['lng'], $Model->whitelist))) {
				/** HACK to prevent 0 inserts if not wanted! just use whitelist now to narrow fields down - 2009-03-18 ms */
				//$Model->whitelist[] = $this->__settings[$Model->alias]['lat'];
				//$Model->whitelist[] = $this->__settings[$Model->alias]['lng'];
				return $return;
			}

			# if both are 0, thats not valid, otherwise continue
			if (!empty($geocode['lat']) || !empty($geocode['lng'])) { /** HACK to prevent 0 inserts of incorrect runs - 2009-04-07 ms */
				$Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']] = $geocode['lat'];
				$Model->data[$Model->alias][$this->__settings[$Model->alias]['lng']] = $geocode['lng'];
			} else {
				if (isset($Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']])) {
					unset($Model->data[$Model->alias][$this->__settings[$Model->alias]['lat']]);
				}
				if (isset($Model->data[$Model->alias][$this->__settings[$Model->alias]['lng']])) {
					unset($Model->data[$Model->alias][$this->__settings[$Model->alias]['lng']]);
				}
			}

			if(!empty($this->__settings[$Model->alias]['formatted_address'])){
				$Model->data[$Model->alias][$this->__settings[$Model->alias]['formatted_address']] = $geocode['formatted_address'];
			} else {
				if (isset($Model->data[$Model->alias][$this->__settings[$Model->alias]['formatted_address']])) {
					unset($Model->data[$Model->alias][$this->__settings[$Model->alias]['formatted_address']]);
				}
			}

			if (!empty($geocode['inconclusive'])) {
				$Model->data[$Model->alias]['inconclusive'] = $geocode['inconclusive'];
				$Model->data[$Model->alias]['results'] = $geocode['results'];
			}

			# correct country id if neccessary
			if (in_array('country_name', $this->__settings[$Model->alias]['address'])) {

				if (!empty($geocode['country']) && in_array($geocode['country'], ($countries = Country::addressList()))) {
					$countries = array_shift(array_keys($countries, $geocode['country']));
					$Model->data[$Model->alias]['country'] = $countries;
				} else {
					$Model->data[$Model->alias]['country'] = 0;
				}
			}
		}
		return $return;
	}


	function __geocode($addressfields, $options = array()) {

		$address = implode(' ', $addressfields);
		App::import('Lib', 'Tools.GeocodeLib');

		$this->Geocode = new GeocodeLib(array('min_accuracy'=>$options['min_accuracy'], 'allow_inconclusive'=>$options['allow_inconclusive'], 'host'=>$options['host']));

		$settings = array('language'=>$options['language']);
		if (!$this->Geocode->geocode($address, $settings)) {
			return array('lat'=>0,'lng'=>0,'official_address'=>'');
		}

		$res = $this->Geocode->getResult();

		if (isset($res[0])) {
			$res = $res[0];

		}
         //TODO: rename to formatted_address
		# hack
		$res['official_address'] = $res['formatted_address'];

		return $res;
	}

}
?>