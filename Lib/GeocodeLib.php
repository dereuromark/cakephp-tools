<?php
App::uses('String', 'Utility');
App::uses('HttpSocket', 'Network/Http');

/**
 * Geocode via google (UPDATE: api3)
 * @see https://developers.google.com/maps/documentation/geocoding/
 *
 * Used by Tools.GeocoderBehavior
 *
 * TODOS (since 1.2):
 * - Work with exceptions in 2.x
 *
 * @author Mark Scherer
 * @licence MIT
 */
class GeocodeLib {

	const BASE_URL = 'https://{host}/maps/api/geocode/json?';
	const DEFAULT_HOST = 'maps.googleapis.com';

	const ACC_COUNTRY = 0;
	const ACC_AAL1 = 1;
	const ACC_AAL2 = 2;
	const ACC_AAL3 = 3;
	const ACC_POSTAL = 4;
	const ACC_LOC = 5;
	const ACC_SUBLOC = 6;
	const ACC_ROUTE = 7;
	const ACC_INTERSEC = 8;
	const ACC_STREET = 9;

	const UNIT_KM = 'K';
	const UNIT_NAUTICAL = 'N';
	const UNIT_FEET = 'F';
	const UNIT_INCHES = 'I';
	const UNIT_MILES = 'M';

	// First tries with curl, then cake, then php
	public $use = [
		'curl' => true,
		'cake' => true,
		'php' => true
	];

	public $units = [
		self::UNIT_KM => 1.609344,
		self::UNIT_NAUTICAL => 0.868976242,
		self::UNIT_FEET => 5280,
		self::UNIT_INCHES => 63360,
		self::UNIT_MILES => 1
	];

	/**
	 * Validation and retrieval options
	 * - use:
	 * - log: false logs only real errors, true all activities
	 * - pause: timeout to prevent blocking
	 * - ...
	 *
	 */
	public $options = [
		'log' => false,
		'pause' => 2000000, # in microseconds (2000000 = 2 seconds = recommended by Google)
		'repeat' => 2, # if over limits, how many times to repeat
		'min_accuracy' => self::ACC_COUNTRY,
		'allow_inconclusive' => true,
		'expect' => [], # see accuracyTypes for details
		'host' => null, # results in maps.google.com - use if you wish to obtain the closest address
	];

	/**
	 * Url params
	 */
	public $params = [
		'address' => '', # either address or latlng required!
		'latlng' => '', # The textual latitude/longitude value for which you wish to obtain the closest, human-readable address
		'region' => '', # The region code, specified as a ccTLD ("top-level domain") two-character
		'language' => 'de',
		'bounds' => '',
		'sensor' => 'false', # device with gps module sensor
		//'key' => '' # not necessary anymore
	];

	public $reachedQueryLimit = false;
	protected $error = [];
	protected $debug = [];

	protected $result = null;

	public $statusCodes = [
		self::STATUS_SUCCESS => 'Success',
		self::STATUS_BAD_REQUEST => 'Sensor param missing',
		self::STATUS_MISSING_QUERY => 'Adress/LatLng missing',
		self::STATUS_UNKNOWN_ADDRESS => 'Success, but to address found',
		self::STATUS_TOO_MANY_QUERIES => 'Limit exceeded',
	];

	public $accuracyTypes = [
		self::ACC_COUNTRY => 'country',
		self::ACC_AAL1 => 'administrative_area_level_1', # provinces/states
		self::ACC_AAL2 => 'administrative_area_level_2 ',
		self::ACC_AAL3 => 'administrative_area_level_3',
		self::ACC_LOC => 'locality',
		self::ACC_POSTAL => 'postal_code',
		self::ACC_SUBLOC => 'sublocality',
		self::ACC_ROUTE => 'route',
		self::ACC_INTERSEC => 'intersection',
		self::ACC_STREET => 'street_address',
	];

	public function __construct(array $options = []) {
		$this->defaultParams = $this->params;
		$this->defaultOptions = $this->options;
		if (Configure::read('debug') > 0) {
			$this->options['log'] = true;
		}

		$this->setOptions($options);
		if (empty($this->options['host'])) {
			$this->options['host'] = static::DEFAULT_HOST;
		}
	}

	/**
	 * @param array $params
	 * @return void
	 */
	public function setParams(array $params) {
		foreach ($params as $key => $value) {
			if ($key === 'sensor' && $value !== 'false' && $value !== 'true') {
				$value = !empty($value) ? 'true' : 'false';
			}
			$this->params[$key] = (string)$value;
		}
	}

	/**
	 * @param array $options
	 * @return void
	 */
	public function setOptions(array $options) {
		foreach ($options as $key => $value) {
			$this->options[$key] = $value;
		}
	}

	public function setError($error) {
		if (empty($error)) {
			return;
		}
		$this->_setDebug('setError', $error);
		$this->error[] = $error;
	}

	public function error($asString = true, $separator = ', ') {
		if (!$asString) {
			return $this->error;
		}
		return implode(', ', $this->error);
	}

	/**
	 * Reset - ready for the next request
	 *
	 * @param mixed boolean $full or string === 'params' to reset just params
	 * @return void
	 */
	public function reset($full = true) {
		$this->error = [];
		$this->result = null;
		$this->reachedQueryLimit = false;
		if (empty($full)) {
			return;
		}
		if ($full === 'params') {
			$this->params = $this->defaultParams;
			return;
		}
		$this->params = $this->defaultParams;
		$this->options = $this->defaultOptions;
	}

	/**
	 * Build url
	 *
	 * @return string url (full)
	 */
	protected function _url() {
		$params = [
			'host' => $this->options['host']
		];
		$url = String::insert(static::BASE_URL, $params, ['before' => '{', 'after' => '}', 'clean' => true]);
		return $url;
	}

	/**
	 * Seems like there are no inconclusive results anymore...
	 *
	 * @return bool isInconclusive (or null if no query has been run yet)
	 */
	public function isInconclusive() {
		if ($this->result === null) {
			return null;
		}
		return !empty($this->result['valid_results']) && $this->result['valid_results'] > 1;
	}

	/**
	 * Return the geocoder result or empty array on failure
	 *
	 * @return array result
	 */
	public function getResult() {
		if ($this->result === null) {
			return [];
		}
		return $this->result;
	}

	/**
	 * Trying to avoid "TOO_MANY_QUERIES" error
	 */
	public function pause() {
		usleep($this->options['pause']);
	}

	/**
	 * Actual querying.
	 * The query will be flatted, and if multiple results are fetched, they will be found
	 * in $result['all'].
	 *
	 * @param string $address
	 * @param array $params
	 * @return bool Success
	 */
	public function geocode($address, array $params = []) {
		if ($this->reachedQueryLimit) {
			$this->setError('Over Query Limit - abort');
			return false;
		}
		$this->reset(false);
		$this->_setDebug('geocode', compact('address', 'params'));
		$params = ['address' => $address] + $params;
		$this->setParams($params);

		$count = 0;
		$requestUrl = $this->_url();

		while (true) {
			$result = $this->_fetch($requestUrl, $this->params);
			if ($result === false || $result === null) {
				$this->setError('Could not retrieve url');
				CakeLog::write('geocode', 'Geocoder could not retrieve url with \'' . $address . '\'');
				return false;
			}

			$this->_setDebug('raw', $result);
			$result = $this->_transform($result);

			if (!is_array($result)) {
				$this->setError('Result parsing failed');
				CakeLog::write('geocode', __d('tools', 'Failed geocode parsing of \'%s\'', $address));
				return false;
			}

			$status = $result['status'];

			if ($status == static::STATUS_SUCCESS) {
				if (!$this->_process($result)) {
					return false;
				}

				// save Result
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Address \'%s\' has been geocoded', $address));
				}
				break;
			} elseif ($status == static::STATUS_TOO_MANY_QUERIES) {
				// sent geocodes too fast, delay +0.1 seconds
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Delay necessary for address \'%s\'', $address));
				}
				$count++;
			} else {
				// something went wrong
				$errorMessage = (isset($result['error_message']) ? $result['error_message'] : '');
				if (empty($errorMessage)) {
					$errorMessage = $this->errorMessage($status);
				}
				if (empty($errorMessage)) {
					$errorMessage = 'unknown';
				}
				$this->setError('Error ' . $status . ' (' . $errorMessage . ')');

				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Could not geocode \'%s\'', $address));
				}
				return false; # for now...
			}

			if ($count > $this->options['repeat']) {
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Aborted after too many trials with \'%s\'', $address));
				}
				$this->setError('Too many trials - abort');
				$this->reachedQueryLimit = true;
				return false;
			}
			$this->pause();
		}

		return true;
	}

	/**
	 * Results usually from most accurate to least accurate result (street_address, ..., country)
	 *
	 * @param float $lat
	 * @param float $lng
	 * @param array $params
	 * @return bool Success
	 */
	public function reverseGeocode($lat, $lng, array $params = []) {
		if ($this->reachedQueryLimit) {
			$this->setError('Over Query Limit - abort');
			return false;
		}
		$this->reset(false);
		$this->_setDebug('reverseGeocode', compact('lat', 'lng', 'params'));
		$latlng = $lat . ',' . $lng;
		$params = ['latlng' => $latlng] + $params;
		$this->setParams($params);

		$count = 0;
		$requestUrl = $this->_url();
		while (true) {
			$result = $this->_fetch($requestUrl, $this->params);
			if ($result === false || $result === null) {
				$this->setError('Could not retrieve url');
				CakeLog::write('geocode', __d('tools', 'Could not retrieve url with \'%s\'', $latlng));
				return false;
			}

			$this->_setDebug('raw', $result);
			$result = $this->_transform($result);
			if (!is_array($result)) {
				$this->setError('Result parsing failed');
				CakeLog::write('geocode', __d('tools', 'Failed reverseGeocode parsing of \'%s\'', $latlng));
				return false;
			}

			$status = $result['status'];

			if ($status == static::STATUS_SUCCESS) {
				if (!$this->_process($result)) {
					return false;
				}

				// save Result
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Address \'%s\' has been geocoded', $latlng));
				}
				break;
			} elseif ($status == static::STATUS_TOO_MANY_QUERIES) {
				// sent geocodes too fast, delay +0.1 seconds
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Delay necessary for \'%s\'', $latlng));
				}
				$count++;
			} else {
				// something went wrong
				$this->setError('Error ' . $status . (isset($this->statusCodes[$status]) ? ' (' . $this->statusCodes[$status] . ')' : ''));

				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Could not geocode \'%s\'', $latlng));
				}
				return false; # for now...
			}
			if ($count > $this->options['repeat']) {
				if ($this->options['log']) {
					CakeLog::write('geocode', __d('tools', 'Aborted after too many trials with \'%s\'', $latlng));
				}
				$this->setError(__d('tools', 'Too many trials - abort'));
				$this->reachedQueryLimit = true;
				return false;
			}
			$this->pause();
		}

		return true;
	}

	/**
	 * GeocodeLib::_process()
	 *
	 * @param mixed $result
	 * @return bool Success
	 */
	protected function _process($result) {
		$this->result = null;
		$validResults = 0;

		foreach ($result['results'] as $res) {
			if (!$res['valid_type']) {
				continue;
			}
			$validResults++;
			if (isset($this->result)) {
				continue;
			}
			$this->result = $res;
		}
		// Only necessary for allow_inconclusive and no valid results found
		if (!$this->result && !empty($result['results'])) {
			$this->result = $result['results'][0];
		}

		$this->result['valid_results'] = $validResults;
		$this->result['all'] = $result['results'];

		// validate
		if (!$this->options['allow_inconclusive'] && $validResults === 0) {
			$this->setError(__d('tools', 'No results found'));
			return false;
		}
		if (!$this->options['allow_inconclusive'] && $validResults > 1) {
			$this->setError(__d('tools', 'Inconclusive result (total of %s)', $validResults));
			return false;
		}

		if ($this->_isNotAccurateEnough($this->result['accuracy'])) {
			$minAccuracy = $this->accuracyTypes[$this->options['min_accuracy']];
			$this->setError(__d('tools', 'Accuracy not good enough (%s instead of at least %s)', $this->result['accuracy_name'], $minAccuracy));

			return false;
		}

		if (!empty($this->options['expect'])) {
			$expected = (array)$this->options['expect'];
			foreach ($expected as $k => $v) {
				$accuracy = is_int($v) ? $this->accuracyTypes[$v] : $v;
				$expected[$k] = $accuracy;
			}
			$found = array_intersect($this->result['types'], $expected);
			$validExpectation = !empty($found);

			if (!$validExpectation) {
				$this->setError(__d('tools', 'Expectation not reached (we have %s instead of at least one of %s)',
					implode(', ', $found),
					implode(', ', $expected)
				));
				return false;
			}
		}
		return true;
	}

	/**
	 * GeocodeLib::accuracyTypes()
	 *
	 * @param mixed $value
	 * @return mixed Type or types
	 */
	public function accuracyTypes($value = null) {
		if ($value !== null) {
			if (isset($this->accuracyTypes[$value])) {
				return $this->accuracyTypes[$value];
			}
			return null;
		}
		return $this->accuracyTypes;
	}

	protected function _validate($result) {
		$validType = false;
		foreach ($result['types'] as $type) {
			if (in_array($type, $this->accuracyTypes, true)) {
				$validType = true;
				break;
			}
		}
		$result['valid_type'] = $validType;
		return $result;
	}

	protected function _accuracy($result) {
		$accuracyTypes = array_reverse($this->accuracyTypes, true);

		$accuracy = 0;
		foreach ($accuracyTypes as $key => $field) {
			if (array_key_exists($field, $result) && !empty($result[$field])) {
				$accuracy = $key;
				break;
			}
		}

		$result['accuracy'] = $accuracy;
		$result['accuracy_name'] = $this->accuracyTypes[$accuracy];
		return $result;
	}

	/**
	 * @param int $accuracy
	 * @return bool notAccurateEnough
	 */
	protected function _isNotAccurateEnough($accuracy) {
		if (!array_key_exists($accuracy, $this->accuracyTypes)) {
			$accuracy = 0;
		}
		// is our current accuracy < minimum?
		return $accuracy < $this->options['min_accuracy'];
	}

	/**
	 * GeocodeLib::_transform()
	 *
	 * @param string|array $record JSON string or array
	 * @return array
	 */
	protected function _transform($record) {
		if (!is_array($record)) {
			$record = json_decode($record, true);
		}
		if (empty($record['results'])) {
			$record['results'] = [];
			return $record;
		}
		$record['results'] = $this->_transformData($record['results']);
		return $record;
	}

	/**
	 * Try to find the max accuracy level
	 *  - look through all fields and
	 *    attempt to find the first record which matches an accuracyTypes field
	 *
	 * @param array $record
	 * @return int|null $maxAccuracy 9-0 as defined in $this->accuracyTypes
	 */
	protected function _getMaxAccuracy($record) {
		if (!is_array($record)) {
			return null;
		}
		$accuracyTypes = array_reverse($this->accuracyTypes, true);
		foreach ($accuracyTypes as $key => $field) {
			if (array_key_exists($field, $record) && !empty($record[$field])) {
				// found $field -- return it's $key
				return $key;
			}
		}

		// not found?  recurse into all possible children
		foreach (array_keys($record) as $key) {
			if (empty($record[$key]) || !is_array($record[$key])) {
				continue;
			}
			$accuracy = $this->_getMaxAccuracy($record[$key]);
			if ($accuracy !== null) {
				// found in nested value
				return $accuracy;
			}
		}

		return null;
	}

	/**
	 * Flattens result array and returns clean record
	 * keys:
	 * - formatted_address, type, country, country_code, country_province, country_province_code, locality, sublocality, postal_code, route, lat, lng, location_type, viewport, bounds
	 *
	 * @param mixed $record any level of input, whole raw array or records or single record
	 * @return array record organized & normalized
	 */
	protected function _transformData($record) {
		if (!is_array($record)) {
			return [];
		}
		if (!array_key_exists('address_components', $record)) {
			foreach (array_keys($record) as $key) {
				$record[$key] = $this->_transformData($record[$key]);
			}
			return $record;
		}

		$res = [];

		// handle and organize address_components
		$components = [];
		foreach ($record['address_components'] as $c) {
			$type = $c['types'][0];
			$types = $c['types'];

			if (array_key_exists($type, $components)) {
				$components[$type]['name'] .= ' ' . $c['long_name'];
				$components[$type]['abbr'] .= ' ' . $c['short_name'];
				$components[$type]['types'] += $types;
			} else {
				$components[$type] = ['name' => $c['long_name'], 'abbr' => $c['short_name'], 'types' => $types];
			}
		}

		$res['formatted_address'] = $record['formatted_address'];

		if (array_key_exists('country', $components)) {
			$res['country'] = $components['country']['name'];
			$res['country_code'] = $components['country']['abbr'];
		} else {
			$res['country'] = $res['country_code'] = '';
		}
		if (array_key_exists('administrative_area_level_1', $components)) {
			$res['country_province'] = $components['administrative_area_level_1']['name'];
			$res['country_province_code'] = $components['administrative_area_level_1']['abbr'];
		} else {
			$res['country_province'] = $res['country_province_code'] = '';
		}

		if (array_key_exists('postal_code', $components)) {
			$res['postal_code'] = $components['postal_code']['name'];
		} else {
			$res['postal_code'] = '';
		}

		if (array_key_exists('locality', $components)) {
			$res['locality'] = $components['locality']['name'];
		} else {
			$res['locality'] = '';
		}

		if (array_key_exists('sublocality', $components)) {
			$res['sublocality'] = $components['sublocality']['name'];
		} else {
			$res['sublocality'] = '';
		}

		if (array_key_exists('route', $components)) {
			$res['route'] = $components['route']['name'];
			if (array_key_exists('street_number', $components)) {
				$res['route'] .= ' ' . $components['street_number']['name'];
			}
		} else {
			$res['route'] = '';
		}

		// determine accuracy types
		if (array_key_exists('types', $record)) {
			$res['types'] = $record['types'];
		} else {
			$res['types'] = [];
		}

		//TODO: add more

		$res['lat'] = $record['geometry']['location']['lat'];
		$res['lng'] = $record['geometry']['location']['lng'];
		$res['location_type'] = $record['geometry']['location_type'];

		if (!empty($record['geometry']['viewport'])) {
			$res['viewport'] = ['sw' => $record['geometry']['viewport']['southwest'], 'ne' => $record['geometry']['viewport']['northeast']];
		}
		if (!empty($record['geometry']['bounds'])) {
			$res['bounds'] = ['sw' => $record['geometry']['bounds']['southwest'], 'ne' => $record['geometry']['bounds']['northeast']];
		}

		// manuell corrections
		$array = [
			'Berlin' => 'BE',
		];
		if (!empty($res['country_province_code']) && array_key_exists($res['country_province_code'], $array)) {
			$res['country_province_code'] = $array[$res['country_province_code']];
		}
		if (!empty($record['postcode_localities'])) {
			$res['postcode_localities'] = $record['postcode_localities'];
		}
		if (!empty($record['address_components'])) {
			$res['address_components'] = $record['address_components'];
		}

		$res = $this->_validate($res);
		$res = $this->_accuracy($res);

		return $res;
	}

	/**
	 * Fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 *
	 * @return mixed
	 */
	protected function _fetch($url, $query) {
		if (!isset($this->HttpSocket)) {
			$this->HttpSocket = new HttpSocket();
		}
		foreach ($query as $k => $v) {
			if ($v === '') {
				unset($query[$k]);
			}
		}
		if ($res = $this->HttpSocket->get($url, $query)) {
			return $res->body;
		}
		$errorCode = $this->HttpSocket->response->code;
		$this->setError('Error ' . $errorCode . ': ' . $this->errorMessage($errorCode));
		return false;
	}

	/**
	 * return debugging info
	 *
	 * @return array debug
	 */
	public function debug() {
		$this->debug['result'] = $this->result;
		return $this->debug;
	}

	/**
	 * set debugging info
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return void
	 */
	public function _setDebug($key, $data = null) {
		$this->debug[$key] = $data;
	}

	/**
	 * Calculates Distance between two points - each: array('lat'=>x,'lng'=>y)
	 * DB:
	 * '6371.04 * ACOS( COS( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
	 * 'COS( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] .')) * ' .
	 * 'COS( RADIANS(Retailer.lng) - RADIANS('. $data['Location']['lng'] .')) + ' .
	 * 'SIN( PI()/2 - RADIANS(90 - Retailer.lat)) * ' .
	 * 'SIN( PI()/2 - RADIANS(90 - '. $data['Location']['lat'] . '))) ' .
	 * 'AS distance'
	 *
	 * @param array pointX
	 * @param array pointY
	 * @param string $unit Unit char or constant (M=miles, K=kilometers, N=nautical miles, I=inches, F=feet)
	 * @return int Distance in km
	 */
	public function distance(array $pointX, array $pointY, $unit = null) {
		if (empty($unit)) {
			$unit = array_keys($this->units);
			$unit = $unit[0];
		}
		$unit = strtoupper($unit);
		if (!isset($this->units[$unit])) {
			throw new CakeException(sprintf('Invalid Unit: %s', $unit));
		}

		$res = $this->calculateDistance($pointX, $pointY);
		if (isset($this->units[$unit])) {
			$res *= $this->units[$unit];
		}
		return ceil($res);
	}

	/**
	 * GeocodeLib::calculateDistance()
	 *
	 * @param array $pointX
	 * @param array $pointY
	 * @return float
	 */
	public static function calculateDistance(array $pointX, array $pointY) {
		/*
		$res = 	6371.04 * ACOS( COS( PI()/2 - rad2deg(90 - $pointX['lat'])) *
				COS( PI()/2 - rad2deg(90 - $pointY['lat'])) *
				COS( rad2deg($pointX['lng']) - rad2deg($pointY['lng'])) +
				SIN( PI()/2 - rad2deg(90 - $pointX['lat'])) *
				SIN( PI()/2 - rad2deg(90 - $pointY['lat'])));

		$res = 6371.04 * acos(sin($pointY['lat'])*sin($pointX['lat'])+cos($pointY['lat'])*cos($pointX['lat'])*cos($pointY['lng'] - $pointX['lng']));
		*/

		// seems to be the only working one (although slightly incorrect...)
		$res = 69.09 * rad2deg(acos(sin(deg2rad($pointX['lat'])) * sin(deg2rad($pointY['lat'])) + cos(deg2rad($pointX['lat'])) * cos(deg2rad($pointY['lat'])) * cos(deg2rad($pointX['lng'] - $pointY['lng']))));
		return $res;
	}

	/**
	 * Convert between units
	 *
	 * @param float $value
	 * @param string $fromUnit (using class constants)
	 * @param string $toUnit (using class constants)
	 * @return float convertedValue
	 * @throws CakeException
	 */
	public function convert($value, $fromUnit, $toUnit) {
		if (!isset($this->units[($fromUnit = strtoupper($fromUnit))]) || !isset($this->units[($toUnit = strtoupper($toUnit))])) {
			throw new CakeException('Invalid Unit');
		}
		if ($fromUnit === 'M') {
			$value *= $this->units[$toUnit];
		} elseif ($toUnit === 'M') {
			$value /= $this->units[$fromUnit];
		} else {
			$value /= $this->units[$fromUnit];
			$value *= $this->units[$toUnit];
		}
		return $value;
	}

	/**
	 * Fuzziness filter for coordinates (lat or lng).
	 * Useful if you store other users' locations and want to grant some
	 * privacy protection. This way the coordinates will be slightly modified.
	 *
	 * @param float coord Coordinates
	 * @param int level The Level of blurness (0 = nothing to 5 = extrem)
	 * - 1:
	 * - 2:
	 * - 3:
	 * - 4:
	 * - 5:
	 * @return float Coordinates
	 * @throws CakeException
	 */
	public static function blur($coord, $level = 0) {
		if (!$level) {
			return $coord;
		}
		//TODO:
		switch ($level) {
			case 1:
				break;
			case 2:
				break;
			case 3:
				break;
			case 4:
				break;
			case 5:
				break;
			default:
				throw new CakeException(sprintf('Invalid level \'%s\'', $level));
		}
		$scrambleVal = 0.000001 * mt_rand(1000, 2000) * (mt_rand(0, 1) === 0 ? 1 : -1);

		return ($coord + $scrambleVal);

		//$scrambleVal *= (mt_rand(0,1) === 0 ? 1 : 2);
		//$scrambleVal *= (float)(2^$level);

		// TODO: + - by chance!!!
		return $coord + $scrambleVal;
	}

	const TYPE_ROOFTOP = 'ROOFTOP';
	const TYPE_RANGE_INTERPOLATED = 'RANGE_INTERPOLATED';
	const TYPE_GEOMETRIC_CENTER = 'GEOMETRIC_CENTER';
	const TYPE_APPROXIMATE = 'APPROXIMATE';

	/**
	 * Return human error message string for response code of Geocoder API.
	 *
	 * @param mixed $code
	 * @return string
	 */
	public function statusMessage($code) {
		if (isset($this->statusCodes[$code])) {
			return __d('tools', $this->statusCodes[$code]);
		}
		return '';
	}

	const STATUS_SUCCESS = 'OK'; //200;
	const STATUS_TOO_MANY_QUERIES = 'OVER_QUERY_LIMIT'; //620;
	const STATUS_BAD_REQUEST = 'REQUEST_DENIED'; //400;
	const STATUS_MISSING_QUERY = 'INVALID_REQUEST';//601;
	const STATUS_UNKNOWN_ADDRESS = 'ZERO_RESULTS'; //602;

	/**
	 * Return human error message string for error code of HttpSocket response.
	 *
	 * @param mixed $code
	 * @return string
	 */
	public function errorMessage($code) {
		$codes = [
			static::CODE_SUCCESS => 'Success',
			static::CODE_BAD_REQUEST => 'Bad Request',
			static::CODE_MISSING_ADDRESS => 'Bad Address',
			static::CODE_UNKNOWN_ADDRESS => 'Unknown Address',
			static::CODE_UNAVAILABLE_ADDRESS => 'Unavailable Address',
			static::CODE_BAD_KEY => 'Bad Key',
			static::CODE_TOO_MANY_QUERIES => 'Too Many Queries',
		];
		if (isset($codes[$code])) {
			return __d('tools', $codes[$code]);
		}
		return '';
	}

	const CODE_SUCCESS = 200;
	const CODE_BAD_REQUEST = 400;
	const CODE_SERVER_ERROR = 500;
	const CODE_MISSING_ADDRESS = 601;
	const CODE_UNKNOWN_ADDRESS = 602;
	const CODE_UNAVAILABLE_ADDRESS = 603;
	const CODE_UNKNOWN_DIRECTIONS = 604;
	const CODE_BAD_KEY = 610;
	const CODE_TOO_MANY_QUERIES = 620;

}

/*

TODO:
http://code.google.com/intl/de-DE/apis/maps/documentation/geocoding/
- whats the difference to "http://maps.google.com/maps/api/geocode/output?parameters"

*/
