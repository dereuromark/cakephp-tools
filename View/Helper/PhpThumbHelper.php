<?php
App::uses('AppHelper', 'View/Helper');

/**
 * @see http://code621.com/content/1/phpthumb-helper-for-cakephp
 * some mods?
 * //TODO: make to lib!?
 * //TODO: integrate ThumbnailHelper !!!
 */
class PhpThumbHelper extends AppHelper {

	protected $PhpThumb;
	protected $options;
	protected $file_extension;
	protected $cache_filename;
	protected $thumb_data;
	protected $error;
	protected $error_detail;

	protected function init($options = array()) {
		$this->options = $options;
		$this->set_file_extension();
		$this->thumb_data = array();
		$this->error = 0;
	}

	protected function set_file_extension() {
		$this->file_extension = mb_substr($this->options['src'], mb_strrpos($this->options['src'], '.'), mb_strlen($this->options['src']));
	}

	protected function set_cache_filename() {
		ksort($this->options);
		$filename_parts = array();
		$cacheable_properties = array('src', 'new', 'w', 'h', 'wp', 'hp', 'wl', 'hl', 'ws', 'hs', 'f', 'q', 'sx', 'sy', 'sw', 'sh', 'zc', 'bc', 'bg', 'fltr');

		foreach ($this->options as $key => $value) {
			if (in_array($key, $cacheable_properties)) {
				$filename_parts[$key] = $value;
			}
		}

		$this->cache_filename = '';

		foreach ($filename_parts as $key => $value) {
			$this->cache_filename .= $key . $value;
		}

		$last_modified = ''; //date("F d Y H:i:s.", filectime($this->options['src']));

		$this->cache_filename = $this->options['save_path'] . DS . md5($this->cache_filename . $last_modified) . $this->file_extension;
	}

	protected function image_is_cached() {
		if (is_file($this->cache_filename)) {
			return true;
		}
		return false;
	}

	protected function create_thumb() {
		if (!isset($this->PhpThumb) || !is_object($this->PhpThumb)) {
			App::import('Vendor', 'phpThumb', array('file' => 'phpThumb'.DS.'phpthumb.class.php'));
		}
		$this->PhpThumb = new PhpThumb();
		set_time_limit(30);

		//TODO: make it cleaner
		# addon
		$PHPTHUMB_CONFIG = array();
		$PHPTHUMB_CONFIG['allow_src_above_docroot'] = true;
		$PHPTHUMB_CONFIG['cache_disable_warning'] = true;
		$PHPTHUMB_CONFIG['max_source_pixels'] = 1920000;
		$PHPTHUMB_CONFIG['error_message_image_default'] = 'Image not found';
		$PHPTHUMB_CONFIG['error_die_on_source_failure'] = true;

		if ((int)Configure::read('debug') > 0) {
			$PHPTHUMB_CONFIG['cache_disable_warning'] = false;
			$PHPTHUMB_CONFIG['error_die_on_source_failure'] = false;
		}

			if (!empty($PHPTHUMB_CONFIG)) {
				foreach ($PHPTHUMB_CONFIG as $key => $value) {
					$keyname = 'config_'.$key;
					$this->PhpThumb->setParameter($keyname, $value);
				}
			}
			# addon end

		foreach ($this->PhpThumb as $var => $value) {
			if (isset($this->options[$var])) {
				$this->PhpThumb->setParameter($var, $this->options[$var]);
			}
		}

		if ($this->PhpThumb->GenerateThumbnail()) {
			$this->PhpThumb->RenderToFile($this->cache_filename);
		} else {
			$this->error = 1;
			$this->error_detail = ereg_replace("[^A-Za-z0-9\/: .]", "", $this->PhpThumb->fatalerror);
		}
	}

	protected function get_thumb_data() {
		$this->thumb_data['error'] = $this->error;

		if ($this->error) {
			$this->thumb_data['error_detail'] = $this->error_detail;
			$this->thumb_data['src'] = $this->options['error_image_path'];
		} else {
			$this->thumb_data['src'] = $this->options['display_path'] . '/' . mb_substr($this->cache_filename, mb_strrpos($this->cache_filename, DS) + 1, mb_strlen($this->cache_filename));
		}

		if (isset($this->options['w'])) {
			$this->thumb_data['w'] = $this->options['w'];
		}

		if (isset($this->options['h'])) {
			 $this->thumb_data['h'] = $this->options['h'];
		}

		return $this->thumb_data;
	}

	protected function validate() {
		if (!is_file($this->options['src'])) {
			$this->error = 1;
			$this->error_detail = 'File ' . $this->options['src'] . ' does not exist';
			return;
		}

		$valid_extensions = array('.gif', '.jpg', '.jpeg', '.png');

		if (!in_array($this->file_extension, $valid_extensions)) {
			$this->error = 1;
			$this->error_detail = 'File ' . $this->options['src'] . ' is not a supported image type';
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
		return (String)$this->error_detail;
	}

/** NOT IN USE YET **/

	/**
	 * Image tag
	 */
	public function show($options = array(), $tag_options = array()) {
		$this->init($options, $tag_options);
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
