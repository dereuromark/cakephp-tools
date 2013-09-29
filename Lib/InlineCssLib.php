<?php

/**
 * Wrapper for Inline CSS replacement.
 * Useful for sending HTML emails.
 *
 * Note: requires vendors CssToInline or emogrifier!
 * Default engine: CssToInline
 *
 * @author Mark Scherer
 * @copyright Mark Scherer
 * @license MIT
 */
class InlineCssLib {

	const ENGINE_CSS_TO_INLINE = 'cssToInline';

	const ENGINE_EMOGRIFIER = 'emogrifier';

	protected $_defaults = array(
		'engine' => self::ENGINE_CSS_TO_INLINE,
		'cleanup' => true,
		'useInlineStylesBlock' => true,
		'xhtmlOutput' => false,
		'removeCss' => true,
		'debug' => false,
	);

	public $settings = array();

	/**
	 * startup
	 */
	public function __construct($settings = array()) {
		$defaults = am($this->_defaults, (array) Configure::read('InlineCss'));
		$this->settings = array_merge($defaults, $settings);
		if (!method_exists($this, '_process' . ucfirst($this->settings['engine']))) {
			throw new InternalErrorException('Engine does not exist');
		}
	}

	/**
	 * @return string Result
	 */
	public function process($html, $css = null) {
		if (($html = trim($html)) === '') {
			return $html;
		}
		$method = '_process' . ucfirst($this->settings['engine']);
		return $this->{$method}($html, $css);
	}

	/**
	 * @return string Result
	 */
	protected function _processEmogrifier($html, $css) {
		$css .= $this->_extractAndRemoveCss($html);
		App::import('Vendor', 'Emogrifier', array('file' => 'emogrifier' . DS . 'emogrifier.php'));
		$Emogrifier = new Emogrifier($html, $css);

		return @$Emogrifier->emogrify();
	}

	/**
	 * Process css blocks to inline css
	 * Also works for html snippets (without <html>)
	 *
	 * @return string HTML output
	 */
	protected function _processCssToInline($html, $css) {
		App::import('Vendor', 'CssToInline', array('file' => 'css_to_inline_styles' . DS . 'css_to_inline_styles.php'));

		//fix issue with <html> being added
		$separator = '~~~~~~~~~~~~~~~~~~~~';
		if (strpos($html, '<html>') === false) {
			$incomplete = true;
			$html = $separator . $html . $separator;
		}

		$CssToInlineStyles = new CSSToInlineStyles($html, $css);
		if ($this->settings['cleanup']) {
			$CssToInlineStyles->setCleanup();
		}
		if ($this->settings['useInlineStylesBlock']) {
			$CssToInlineStyles->setUseInlineStylesBlock();
		}
		if ($this->settings['removeCss']) {
			$CssToInlineStyles->setStripOriginalStyleTags();
		}
		if ($this->settings['debug']) {
			CakeLog::write('css', $html);
		}
		$html = $CssToInlineStyles->convert($this->settings['xhtmlOutput']);
		if ($this->settings['removeCss']) {
			//$html = preg_replace('/\<style(.*)\>(.*)\<\/style\>/i', '', $html);
			$html = $this->stripOnly($html, array('style', 'script'), true);
			//CakeLog::write('css', $html);
		}

		if (!empty($incomplete)) {
			$html = substr($html, strpos($html, $separator) + 20);
			$html = substr($html, 0, strpos($html, $separator));
			$html = trim($html);
		}
		return $html;
	}

	/**
	 * Some reverse function of strip_tags with blacklisting instead of whitelisting
	 * //maybe move to Tools.Utility/String/Text?
	 *
	 * @return string cleanedStr
	 */
	public function stripOnly($str, $tags, $stripContent = false) {
		$content = '';
		if (!is_array($tags)) {
			$tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
			if (end($tags) === '') {
				array_pop($tags);
			}
		}
		foreach ($tags as $tag) {
			if ($stripContent) {
				 $content = '(.+</' . $tag . '[^>]*>|)';
			}
			$str = preg_replace('#</?' . $tag . '[^>]*>' . $content . '#is', '', $str);
		}
		return $str;
	}

	/**
	 * _extractAndRemoveCss - extracts any CSS from the rendered view and
	 * removes it from the $html
	 *
	 * @return string
	 */
	protected function _extractAndRemoveCss($html) {
		$css = null;

		$DOM = new DOMDocument;
		$DOM->loadHTML($html);

		// DOM removal queue
		$remove_doms = array();

		// catch <link> style sheet content
		$links = $DOM->getElementsByTagName('link');

		foreach ($links as $link) {
			if ($link->hasAttribute('href') && preg_match("/\.css$/i", $link->getAttribute('href'))) {

				// find the css file and load contents
				if ($link->hasAttribute('media')) {
					foreach ($this->media_types as $css_link_media) {
						if (strstr($link->getAttribute('media'), $css_link_media)) {
							$css .= $this->_findAndLoadCssFile($link->getAttribute('href')) . "\n\n";
							$remove_doms[] = $link;
						}
					}
				} else {
					$css .= $this->_findAndLoadCssFile($link->getAttribute('href')) . "\n\n";
					$remove_doms[] = $link;
				}
			}
		}

		// Catch embeded <style> and @import CSS content
		$styles = $DOM->getElementsByTagName('style');

		// Style
		foreach ($styles as $style) {
			if ($style->hasAttribute('media')) {
				foreach ($this->media_types as $css_link_media) {
					if (strstr($style->getAttribute('media'), $css_link_media)) {
						$css .= $this->_parseInlineCssAndLoadImports($style->nodeValue);
						$remove_doms[] = $style;
					}
				}
			} else {
				$css .= $this->_parseInlineCssAndLoadImports($style->nodeValue);
				$remove_doms[] = $style;
			}
		}

		// Remove
		if ($this->settings['removeCss']) {
			foreach ($remove_doms as $remove_dom) {
				try {
					$remove_dom->parentNode->removeChild($remove_dom);
				} catch (DOMException $e) {}
			}
			$html = $DOM->saveHTML();
		}

		return $html;
	}

	/**
	 * _findAndLoadCssFile - finds the appropriate css file within the CSS path
	 *
	 * @param string $cssHref
	 * @return string Content
	 */
	protected function _findAndLoadCssFile($cssHref) {
		$css_filenames = array_merge($this->_globRecursive(CSS.'*.Css'), $this->_globRecursive(CSS.'*.CSS'), $this->_globRecursive(CSS.'*.css'));

		// Build an array of the ever more path specific $cssHref location
		$cssHrefs = split(DS, $cssHref);
		$cssHref_paths = array();
		for ($i = count($cssHrefs) - 1; $i > 0; $i--) {
			if (isset($cssHref_paths[count($cssHref_paths) - 1])) {
				$cssHref_paths[] = $cssHrefs[$i] . DS . $cssHref_paths[count($cssHref_paths) - 1];
			} else {
				$cssHref_paths[] = $cssHrefs[$i];
			}
		}

		// the longest string match will be the match we are looking for
		$best_css_filename = null;
		$best_css_match_length = 0;
		foreach ($css_filenames as $css_filename) {
			foreach ($cssHref_paths as $cssHref_path) {
				$regex = '/' . str_replace('/', '\/', str_replace('.', '\.', $cssHref_path)) . '/';
				if (preg_match($regex, $css_filename, $match)) {
					if (strlen($match[0]) > $best_css_match_length) {
						$best_css_match_length = strlen($match[0]);
						$best_css_filename = $css_filename;
					}
				}
			}
		}

		$css = null;
		if (!empty($best_css_filename) && is_file($best_css_filename)) {
			$css = file_get_contents($best_css_filename);
		}

		return $css;
	}

	/**
	 * _globRecursive
	 *
	 * @param string $pattern
	 * @param integer $flags
	 * @return array
	 */
	protected function _globRecursive($pattern, $flags = 0) {

		$files = glob($pattern, $flags);

		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
			$files = array_merge($files, $this->_globRecursive($dir . '/' . basename($pattern), $flags));
		}

		return $files;
	}

	/**
	 * _parseInlineCssAndLoadImports
	 *
	 * @param string Input
	 * @return string Result
	 */
	protected function _parseInlineCssAndLoadImports($css) {
		// Remove any <!-- --> comment tags - they are valid in HTML but we probably
		// don't want to be commenting out CSS
		$css = str_replace('-->', '', str_replace('<!--', '', $css)) . "\n\n";

		// Load up the @import CSS if any exists
		preg_match_all("/\@import.*?url\((.*?)\)/i", $css, $matches);

		if (isset($matches[1]) && is_array($matches[1])) {
			// First remove the @imports
			$css = preg_replace("/\@import.*?url\(.*?\).*?;/i", '', $css);

			foreach ($matches[1] as $url) {
				if (preg_match("/^http/i", $url)) {
					if ($this->import_external_css) {
						$css .= file_get_contents($url);
					}
				} else {
					$css .= $this->_findAndLoadCssFile($url);
				}
			}
		}

		return $css;
	}

}
