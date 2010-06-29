<?php

/**
 * wrapper for curl,
 */
class HttpSocketLib {

	// First tries with curl, then cake, then php
	var $use = array('curl' => true, 'cake'=> true, 'php' => true);
	var $debug = null;

	function __construct($use = array()) {
		if (is_array($use)) {
			foreach ($use as $key => $value) {
				if (array_key_exists($key, $this->use)) {
					$this->use[$key] = $value;
				}
			}
		} elseif (array_key_exists($use, $this->use)) {
			$this->use[$use] = true;
			if ($use == 'cake') {
				$this->use['curl'] = false;
			} elseif ($use == 'php') {
				$this->use['curl'] = $this->use['cake'] = false;
			}
		}
	}

	function setError($error) {
		if (empty($error)) {
			return;
		}
		$this->error[] = $error;
	}

	function error($asString = true, $separator = ', ') {
		return implode(', ', $this->error);
	}


	/**
	 * fetches url with curl if available
	 * fallbacks: cake and php
	 * note: expects url with json encoded content
	 * @access private
	 **/
	public function fetch($url, $agent = 'cakephp http socket lib') {
		if ($this->use['curl'] && function_exists('curl_init')) {
			$this->debug = 'curl';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			$response = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
			if ($status != '200') {
				$this->setError('Error '.$status);
				return false;
			}
			return $response;

		} elseif($this->use['cake'] && App::import('Core', 'HttpSocket')) {
			$this->debug = 'cake';

			$HttpSocket = new HttpSocket(array('timeout' => 5));
			$response = $HttpSocket->get($url);
			if (empty($response)) { //TODO: status 200?
				return false;
			}
			return $response;

		} elseif($this->use['php'] || true) {
			$this->debug = 'php';

			$response = file_get_contents($url, 'r');
			//TODO: status 200?
			if (empty($response)) {
				return false;
			}
			return $response;
		}
	}


}

/*

Array
(
    [name] => 74523 Deutschland
    [Status] => Array
        (
            [code] => 200
            [request] => geocode
        )

    [Placemark] => Array
        (
            [id] => p1
            [address] => 74523, Deutschland
            [AddressDetails] => Array
                (
                    [Accuracy] => 5
                    [xmlns] => urn:oasis:names:tc:ciq:xsdschema:xAL:2.0
                    [Country] => Array
                        (
                            [CountryNameCode] => DE
                            [CountryName] => Deutschland
                            [AdministrativeArea] => Array
                                (
                                    [AdministrativeAreaName] => Baden-Württemberg
                                    [SubAdministrativeArea] => Array
                                        (
                                            [SubAdministrativeAreaName] => Schwäbisch Hall
                                            [PostalCode] => Array
                                                (
                                                    [PostalCodeNumber] => 74523
                                                )

                                        )

                                )

                        )

                )

            [ExtendedData] => Array
                (
                    [LatLonBox] => Array
                        (
                            [north] => 49.1670260
                            [south] => 49.0451477
                            [east] => 9.8756350
                            [west] => 9.6132550
                        )

                )

            [Point] => Array
                (
                    [coordinates] => 9.7544127,49.1257616,0
                )

        )

)

*/
?>