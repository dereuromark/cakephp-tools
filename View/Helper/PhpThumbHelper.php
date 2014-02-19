<?php
App::uses('AppHelper', 'View/Helper');

/**
 * @see http://code621.com/content/1/phpthumb-helper-for-cakephp
 * some mods?
 * //TODO: make to lib!?
 * //TODO: integrate ThumbnailHelper !!!
 */
class PhpThumbHelper extends AppHelper {

	public $PhpThumb;

	public $options;

	public $fileExtension;

	public $cacheFilename;

	public $thumbData;

	public $error;

	public $errorDetail;

	public function init($options = array()) {
		$this->options = $options;
		$this->set_file_extension();
		$this->thumbData = array();
		$this->error = 0;
	}

	public function set_file_extension() {
		$this->fileExtension = mb_substr($this->options['src'], mb_strrpos($this->options['src'], '.'), mb_strlen($this->options['src']));
	}

	public function set_cache_filename() {
		ksort($this->options);
		$filenameParts = array();
		$cacheableProperties = array('src', 'new', 'w', 'h', 'wp', 'hp', 'wl', 'hl', 'ws', 'hs', 'f', 'q', 'sx', 'sy', 'sw', 'sh', 'zc', 'bc', 'bg', 'fltr');

		foreach ($this->options as $key => $value) {
			if (in_array($key, $cacheableProperties)) {
				$filenameParts[$key] = $value;
			}
		}

		$this->cacheFilename = '';

		foreach ($filenameParts as $key => $value) {
			$this->cacheFilename .= $key . $value;
		}

		$lastModified = ''; //date("F d Y H:i:s.", filectime($this->options['src']));

		$this->cacheFilename = $this->options['save_path'] . DS . md5($this->cacheFilename . $lastModified) . $this->fileExtension;
	}

	public function image_is_cached() {
		if (is_file($this->cacheFilename)) {
			return true;
		}
		return false;
	}

	public function create_thumb() {
		if (!isset($this->PhpThumb) || !is_object($this->PhpThumb)) {
			App::import('Vendor', 'phpThumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php'));
		}
		$this->PhpThumb = new PhpThumb();
		set_time_limit(30);

		//TODO: make it cleaner
		// addon
		$phpthumbConfig = array();
		$phpthumbConfig['allow_src_above_docroot'] = true;
		$phpthumbConfig['cache_disable_warning'] = true;
		$phpthumbConfig['max_source_pixels'] = 1920000;
		$phpthumbConfig['error_message_image_default'] = 'Image not found';
		$phpthumbConfig['error_die_on_source_failure'] = true;

		if ((int)Configure::read('debug') > 0) {
			$phpthumbConfig['cache_disable_warning'] = false;
			$phpthumbConfig['error_die_on_source_failure'] = false;
		}

			if (!empty($phpthumbConfig)) {
				foreach ($phpthumbConfig as $key => $value) {
					$keyname = 'config_' . $key;
					$this->PhpThumb->setParameter($keyname, $value);
				}
			}
			// addon end

		foreach ($this->PhpThumb as $var => $value) {
			if (isset($this->options[$var])) {
				$this->PhpThumb->setParameter($var, $this->options[$var]);
			}
		}

		if ($this->PhpThumb->GenerateThumbnail()) {
			$this->PhpThumb->RenderToFile($this->cacheFilename);
		} else {
			$this->error = 1;
			$this->errorDetail = ereg_replace("[^A-Za-z0-9\/: .]", "", $this->PhpThumb->fatalerror);
		}
	}

	public function get_thumb_data() {
		$this->thumbData['error'] = $this->error;

		if ($this->error) {
			$this->thumbData['error_detail'] = $this->errorDetail;
			$this->thumbData['src'] = $this->options['error_image_path'];
		} else {
			$this->thumbData['src'] = $this->options['display_path'] . '/' . mb_substr($this->cacheFilename, mb_strrpos($this->cacheFilename, DS) + 1, mb_strlen($this->cacheFilename));
		}

		if (isset($this->options['w'])) {
			$this->thumbData['w'] = $this->options['w'];
		}

		if (isset($this->options['h'])) {
			 $this->thumbData['h'] = $this->options['h'];
		}

		return $this->thumbData;
	}

	public function validate() {
		if (!is_file($this->options['src'])) {
			$this->error = 1;
			$this->errorDetail = 'File ' . $this->options['src'] . ' does not exist';
			return;
		}

		$validExtensions = array('.gif', '.jpg', '.jpeg', '.png');

		if (!in_array($this->fileExtension, $validExtensions)) {
			$this->error = 1;
			$this->errorDetail = 'File ' . $this->options['src'] . ' is not a supported image type';
			return;
		}
	}

	public function generate($options = array()) {
		$this->init($options);

		$this->validate();

		if (!$this->error) {
			$this->set_cache_filename();
			if (!$this->image_is_cached()) {
				$this->create_thumb();
			}
		}

		return $this->get_thumb_data();
	}

	/**
	 * @return string error
	 */
	public function error() {
		return (string)$this->errorDetail;
	}

/** NOT IN USE YET **/

	/**
	 * Image tag
	 */
	public function show($options = array(), $tagOptions = array()) {
		$this->init($options, $tagOptions);
		if ($this->image_is_cached()) {
			return $this->show_image_tag();
		} else {
			$this->create_thumb();
			return $this->show_image_tag();
		}
	}

	/**
	 * Image src only
	 */
	public function show_src($options = array()) {
		$this->init($options);
		if ($this->image_is_cached()) {
			return $this->get_image_src();
		} else {
			$this->create_thumb();
			return $this->get_image_src();
		}
	}

}
