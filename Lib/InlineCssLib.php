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

	/**
	 * Default config
	 *
	 * @var array
	 */
	protected $_defaults = array(
		'engine' => self::ENGINE_EMOGRIFIER,
		'cleanup' => true,
		'responsive' => false, // If classes/ids should not be remove, only relevant for cleanup=>true
		'useInlineStylesBlock' => true,
		'debug' => false, // only cssToInline
		'xhtmlOutput' => false, // only cssToInline
		'removeCss' => true, // only cssToInline
		'correctUtf8' => false // only cssToInline
	);

	public $config = array();

	/**
	 * Inits with auto merged config.
	 */
	public function __construct($config = array()) {
		$defaults = (array)Configure::read('InlineCss') + $this->_defaults;
		$this->config = $config + $defaults;
		if (!method_exists($this, '_process' . ucfirst($this->config['engine']))) {
			throw new InternalErrorException('Engine does not exist: ' . $this->config['engine']);
		}
	}

	/**
	 * Processes HTML and CSS.
	 *
	 * @return string Result
	 */
	public function process($html, $css = null) {
		if (($html = trim($html)) === '') {
			return $html;
		}
		$method = '_process' . ucfirst($this->config['engine']);
		return $this->{$method}($html, $css);
	}

	/**
	 * @return string Result
	 */
	protected function _processEmogrifier($html, $css) {
		//$css .= $this->_extractAndRemoveCss($html);
		App::import('Vendor', 'Tools.Emogrifier', array('file' => 'Emogrifier/Emogrifier.php'));

		$Emogrifier = new Emogrifier($html, $css);
		//$Emogrifier->preserveEncoding = true;

		$result = $Emogrifier->emogrify();

		if ($this->config['cleanup']) {
			// Remove comments and whitespace
			$result = preg_replace( '/<!--(.|\s)*?-->/', '', $result);
			//$result = preg_replace( '/\s\s+/', '\s', $result);

			// Result classes and ids
			if (!$this->config['responsive']) {
				$result = preg_replace('/\bclass="[^"]*"/', '', $result);
				$result = preg_replace('/\bid="[^"]*"/', '', $result);
			}
		}
		return $result;
	}

	/**
	 * Process css blocks to inline css
	 * Also works for html snippets (without <html>)
	 *
	 * @return string HTML output
	 */
	protected function _processCssToInline($html, $css) {
		App::import('Vendor', 'Tools.CssToInlineStyles', array('file' => 'CssToInlineStyles' . DS . 'CssToInlineStyles.php'));

		//fix issue with <html> being added
		$separator = '~~~~~~~~~~~~~~~~~~~~';
		if (strpos($html, '<html') === false) {
			$incomplete = true;
			$html = $separator . $html . $separator;
		}

		$CssToInlineStyles = new CssToInlineStyles($html, $css);
		if ($this->config['cleanup']) {
			$CssToInlineStyles->setCleanup();
		}
		if ($this->config['useInlineStylesBlock']) {
			$CssToInlineStyles->setUseInlineStylesBlock();
		}
		if ($this->config['removeCss']) {
			$CssToInlineStyles->setStripOriginalStyleTags();
		}
		if ($this->config['correctUtf8']) {
			$CssToInlineStyles->setCorrectUtf8();
		}
		if ($this->config['debug']) {
			CakeLog::write('css', $html);
		}
		$html = $CssToInlineStyles->convert($this->config['xhtmlOutput']);
		if ($this->config['removeCss']) {
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
		$removeDoms = array();

		// catch <link> style sheet content
		$links = $DOM->getElementsByTagName('link');

		foreach ($links as $link) {
			if ($link->hasAttribute('href') && preg_match("/\.css$/i", $link->getAttribute('href'))) {

				// find the css file and load contents
				if ($link->hasAttribute('media')) {
					// FOR NOW
					continue;
					foreach ($this->mediaTypes as $cssLinkMedia) {
						if (strstr($link->getAttribute('media'), $cssLinkMedia)) {
							$css .= $this->_findAndLoadCssFile($link->getAttribute('href')) . "\n\n";
							$removeDoms[] = $link;
						}
					}
				} else {
					$css .= $this->_findAndLoadCssFile($link->getAttribute('href')) . "\n\n";
					$removeDoms[] = $link;
				}
			}
		}

		// Catch embeded <style> and @import CSS content
		$styles = $DOM->getElementsByTagName('style');

		// Style
		foreach ($styles as $style) {
			if ($style->hasAttribute('media')) {
				foreach ($this->mediaTypes as $cssLinkMedia) {
					if (strstr($style->getAttribute('media'), $cssLinkMedia)) {
						$css .= $this->_parseInlineCssAndLoadImports($style->nodeValue);
						$removeDoms[] = $style;
					}
				}
			} else {
				$css .= $this->_parseInlineCssAndLoadImports($style->nodeValue);
				$removeDoms[] = $style;
			}
		}

		// Remove
		if ($this->config['removeCss']) {
			foreach ($removeDoms as $removeDom) {
				try {
					$removeDom->parentNode->removeChild($removeDom);
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
		$cssFilenames = array_merge($this->_globRecursive(CSS . '*.Css'), $this->_globRecursive(CSS . '*.CSS'), $this->_globRecursive(CSS . '*.css'));

		// Build an array of the ever more path specific $cssHref location
		$cssHref = str_replace(array('\\', '/'), '/', $cssHref);

		$cssHrefs = explode(DS, $cssHref);
		$cssHrefPaths = array();
		for ($i = count($cssHrefs) - 1; $i > 0; $i--) {
			if (isset($cssHrefPaths[count($cssHrefPaths) - 1])) {
				$cssHrefPaths[] = $cssHrefs[$i] . DS . $cssHrefPaths[count($cssHrefPaths) - 1];
			} else {
				$cssHrefPaths[] = $cssHrefs[$i];
			}
		}

		// the longest string match will be the match we are looking for
		$bestCssFilename = null;
		$bestCssMatchLength = 0;
		foreach ($cssFilenames as $cssFilename) {
			foreach ($cssHrefPaths as $cssHrefPath) {
				$regex = '/' . str_replace('/', '\/', str_replace('.', '\.', $cssHrefPath)) . '/';
				if (preg_match($regex, $cssFilename, $match)) {
					if (strlen($match[0]) > $bestCssMatchLength) {
						$bestCssMatchLength = strlen($match[0]);
						$bestCssFilename = $cssFilename;
					}
				}
			}
		}

		$css = null;
		if (!empty($bestCssFilename) && is_file($bestCssFilename)) {
			$context = stream_context_create(
				array('http' => array('header' => 'Connection: close')));
			$css = file_get_contents($bestCssFilename, 0, $context);
		}

		return $css;
	}

	/**
	 * _globRecursive
	 *
	 * @param string $pattern
	 * @param int $flags
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
		// Load up the @import CSS if any exists
		preg_match_all("/\@import.*?url\((.*?)\)/i", $css, $matches);

		if (isset($matches[1]) && is_array($matches[1])) {
			// First remove the @imports
			$css = preg_replace("/\@import.*?url\(.*?\).*?;/i", '', $css);

			$context = stream_context_create(
				array('http' => array('header' => 'Connection: close')));
			foreach ($matches[1] as $url) {
				if (preg_match("/^http/i", $url)) {
					if ($this->importExternalCss) {
						$css .= file_get_contents($url, 0, $context);
					}
				} else {
					$css .= $this->_findAndLoadCssFile($url);
				}
			}
		}

		return $css;
	}

}
