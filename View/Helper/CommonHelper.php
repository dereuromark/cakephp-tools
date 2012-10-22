<?php
App::uses('AppHelper', 'View/Helper');

/**
 * All site-wide necessary stuff for the view layer
 */
class CommonHelper extends AppHelper {

	public $helpers = array ('Session', 'Html');

	public $packages = array(
		'Tools.Jquery' //Used by showDebug
	);


/*** Layout Stuff ***/

	/**
	 * convinience function for clean ROBOTS allowance
	 * @param STRING private/public
	 * 2008-12-08 ms
	 */
	public function metaRobots($type = null) {
		if (($meta = Configure::read('Config.robots')) !== null) {
			$type = $meta;
		}
		$content = array();
		if ($type == 'public') {
			$this->privatePage = false;
			$content['robots']= array('index','follow','noarchive');

		} else {
			$this->privatePage = true;
			$content['robots']= array('noindex','nofollow','noarchive');
		}

		$return = '<meta name="robots" content="' . implode(',', $content['robots']) . '" />';
		return $return;
	}

	/**
	 * convinience function for clean meta name tags
	 * @param STRING $name: author, date, generator, revisit-after, language
	 * @param MIXED $content: if array, it will be seperated by commas
	 * @return string $htmlMarkup
	 * 2009-07-07 ms
	 */
	public function metaName($name = null, $content = null) {
		if (empty($name) || empty($content)) {
			return '';
		}

		if (!is_array($content)) {
			$content = (array)$content;
		}
		$return = '<meta name="' . $name . '" content="' . implode(', ', $content) . '" />';
		return $return;
	}

	/**
	 * @param string $content
	 * @param string $language (iso2: de, en-us, ...)
	 * @param array $additionalOptions
	 * @return string $htmlMarkup
	 */
	public function metaDescription($content, $language = null, $options = array()) {
		if (!empty($language)) {
			$options['lang'] = mb_strtolower($language);
		} elseif ($language !== false) {
			$options['lang'] = Configure::read('Config.locale'); // DEFAULT_LANGUAGE
		}
		return $this->Html->meta('description', $content, $options);
	}

	/**
	 * @param string|array $keywords
	 * @param string $language (iso2: de, en-us, ...)
	 * @param bool $escape
	 * @return string $htmlMarkup
	 */
	public function metaKeywords($keywords = null, $language = null, $escape = true) {
		if ($keywords === null) {
			$keywords = Configure::read('Config.keywords');
		}
		if (is_array($keywords)) {
			$keywords = implode(', ', $keywords);
		}
		if ($escape) {
			$keywords = h($keywords);
		}
		if (!empty($language)) {
			$options['lang'] = mb_strtolower($language);
		} elseif ($language !== false) {
			$options['lang'] = Configure::read('Config.locale'); // DEFAULT_LANGUAGE
		}
		return $this->Html->meta('keywords', $keywords, $options);
	}

	/**
	 * convinience function for "canonical" SEO links
	 *
	 * @return string $htmlMarkup
	 * 2010-03-03 ms
	 */
	public function metaCanonical($url = null, $full = false) {
		$canonical = $this->Html->url($url, $full);
		//return $this->Html->meta('canonical', $canonical, array('rel'=>'canonical', 'type'=>null, 'title'=>null));
		return '<link rel="canonical" href="' . $canonical . '" />';
	}

	/**
	 * convinience function for "alternate" SEO links
	 * @param mixed $url
	 * @param mixed $lang (lang(iso2) or array of langs)
	 * lang: language (in ISO 6391-1 format) + optionally the region (in ISO 3166-1 Alpha 2 format)
	 * - de
	 * - de-ch
	 * etc
	 * @return string $htmlMarkup
	 * 2011-12-12 ms
	 */
	public function metaAlternate($url, $lang, $full = false) {
		$canonical = $this->Html->url($url, $full);
		//return $this->Html->meta('canonical', $canonical, array('rel'=>'canonical', 'type'=>null, 'title'=>null));
		$lang = (array)$lang;
		$res = array();
		foreach ($lang as $language => $countries) {
			if (is_numeric($language)) {
		 		$language = '';
 			} else {
 				$language .= '-';
 			}
 			$countries = (array)$countries;
	 		foreach ($countries as $country) {
	 			$l = $language . $country;
	 			$options = array('rel' => 'alternate', 'hreflang' => $l, 'type' => null, 'title' => null);
	 			$res[] = $this->Html->meta('alternate', $url, $options).PHP_EOL;
	 		}
		}
		return implode('', $res);
	}

	/**
	 * convinience function for META Tags
	 * @param STRING type
	 * @param STRING content
	 * @return string $htmlMarkup
	 * 2008-12-08 ms
	 */
	public function metaRss($url = null, $title = null) {
		$tags = array(
			'meta' => '<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />',
		);
		$content = array();
		if (empty($url)) { return ''; }
		if (empty($title)) {
			$title = 'Diesen Feed abonnieren';
		}

		return sprintf($tags['meta'], $title, $this->url($url));
	}


	/**
	 * convinience function for META Tags
	 * @param STRING type
	 * @param STRING content
	 * @return string $htmlMarkup
	 * 2008-12-08 ms
	 */
	public function metaEquiv($type = null, $value = null, $escape = true) {
		$tags = array(
			'meta' => '<meta http-equiv="%s"%s />',
		);
		if (empty($value)) {
			return '';
		}
		if ($escape) {
			$value = h($value);
		}

		if ($type == 'language') {
			return sprintf($tags['meta'], 'language', ' content="'.$value.'"');
		} elseif ($type == 'pragma') {
			return sprintf($tags['meta'], 'pragma', ' content="'.$value.'"');
		} elseif ($type == 'expires') {
			return sprintf($tags['meta'], 'expires', ' content="'.$value.'"');
		} elseif ($type == 'cache-control') {
			return sprintf($tags['meta'], 'cache-control', ' content="'.$value.'"');
		} elseif ($type == 'refresh') {
			return sprintf($tags['meta'], 'refresh', ' content="'.$value.'"');
		}
		return '';
	}

	/**
	 * (example): array(x, Tools|y, Tools.Jquery|jquery/sub/z)
	 * => x is in webroot/
	 * => y is in plugins/tools/webroot/
	 * => z is in plugins/tools/packages/jquery/files/jquery/sub/
	 * @return string $htmlMarkup
	 * 2011-03-23 ms
	 */
	public function css($files = array(), $rel = null, $options = array()) {
		$files = (array)$files;
		$pieces = array();
		foreach ($files as $file) {
			$pieces[] = 'file='.$file;
		}
		if ($v = Configure::read('Config.layout_v')) {
			$pieces[] = 'v='.$v;
		}
		$string = implode('&', $pieces);
		return $this->Html->css('/css.php?'.$string, $rel, $options);
	}

	/**
	 * (example): array(x, Tools|y, Tools.Jquery|jquery/sub/z)
	 * => x is in webroot/
	 * => y is in plugins/tools/webroot/
	 * => z is in plugins/tools/packages/jquery/files/jquery/sub/
	 * @return string $htmlMarkup
	 * 2011-03-23 ms
	 */
	public function script($files = array(), $options = array()) {
		$files = (array)$files;
		foreach ($files as $file) {
			$pieces[] = 'file='.$file;
		}
		if ($v = Configure::read('Config.layout_v')) {
			$pieces[] = 'v='.$v;
		}
		$string = implode('&', $pieces);
		return $this->Html->script('/js.php?'.$string, $options);
	}


	/**
	 * special css tag generator with the option to add '?...' to the link (for caching prevention)
	 * IN USAGE
	 * needs manual adjustment, but still better than the core one!
	 * @example needs Asset.cssversion => xyz (going up with counter)
	 * @return string $htmlMarkup
	 * 2008-12-08 ms
	 */
	public function cssDyn($path, $rel = null, $htmlAttributes = array(), $return = true) {
		$v = (int)Configure::read('Asset.version');
		return $this->Html->css($path.'.css?'.$v, $rel, $htmlAttributes, $return);
	}

	/**
	 * NOT IN USAGE
	 * but better than the core one!
	 * @example needs Asset.timestamp => force
	 * @return string $htmlMarkup
	 * 2008-12-08 ms
	 */
	public function cssAuto($path, $rel = null, $htmlAttributes = array(), $return = true) {
		define('COMPRESS_CSS',true);

			$time = date('YmdHis', filemtime(APP . 'webroot' . DS . CSS_URL . $path . '.css'));
			$url = "{$this->request->webroot}" . (COMPRESS_CSS ? 'c' : '') . CSS_URL . $this->themeWeb . $path . ".css?" . $time;
	 	return $url;
	}


/*** Content Stuff ***/

	/**
	 * still necessary?
	 * @param array $fields
	 * @return string $html
	 */
	public function displayErrors($fields = array()) {
		$res = '';
		if (!empty($this->validationErrors)) {
			if ($fields === null) { # catch ALL
				foreach ($this->validationErrors as $alias => $error) {
					list($alias, $fieldname) = explode('.', $error);
					$this->validationErrors[$alias][$fieldname];
				}
			} elseif (!empty($fields)) {
				foreach ($fields as $field) {
					list($alias, $fieldname) = explode('.', $field);

					if (!empty($this->validationErrors[$alias][$fieldname])) {
						$res .= $this->_renderError($this->validationErrors[$alias][$fieldname]);
					}
				}
			}

			/*
			if (!empty($catched)) {
				foreach ($catched as $c) {

				}
			}
			*/
		}
		return $res;
	}

	protected function _renderError($error, $escape = true) {
		if ($escape !== false) {
			$error = h($error);
		}
		return '<div class="error-message">'.$error.'</div>';
	}

	/**
	 * Alternates between two or more strings.
	 *
	 * echo CommonHelper::alternate('one', 'two'); // "one"
	 * echo CommonHelper::alternate('one', 'two'); // "two"
	 * echo CommonHelper::alternate('one', 'two'); // "one"
	 *
	 * Note that using multiple iterations of different strings may produce
	 * unexpected results.
	 * TODO: move to booststrap!!!
	 *
	 * @param string strings to alternate between
	 * @return string
	 */
	public static function alternate() {
		static $i;

		if (func_num_args() === 0) {
			$i = 0;
			return '';
		}

		$args = func_get_args();
		return $args[($i++ % count($args))];
	}


	/**
	 * check if session works due to allowed cookies
	 *
	 * 2009-06-29 ms
	 */
	public function sessionCheck() {
		return !CommonComponent::cookiesDisabled();
		/*
		if (!empty($_COOKIE) && !empty($_COOKIE[Configure::read('Session.cookie')])) {
			return true;
		}
		return false;
		*/
	}

	/**
	 * display warning if cookies are disallowed (and session won't work)
	 * 2009-06-29 ms
	 */
	public function sessionCheckAlert() {
		if (!$this->sessionCheck()) {
			return '<div class="cookieWarning">'.__('Please enable cookies').'</div>';
		}
		return '';
	}

	/**
	 * //TODO: move boostrap
	 * auto-pluralizing a word using the Inflection class
	 * @param string $s = the string to be pl.
	 * @param int $c = the count
	 * @return $string "member" or "members" OR "Mitglied"/"Mitglieder" if autoTranslate TRUE
	 * 2009-07-23 ms
	 */
	public function asp($s, $c, $autoTranslate = false) {
		if ((int)$c !== 1) {
			$p = Inflector::pluralize($s);
		} else {
		 	$p = null; # no pluralization necessary
		}
		return $this->sp($s, $p, $c, $autoTranslate);
	}

	/**
	 * //TODO: move boostrap
	 * manual pluralizing a word using the Inflection class
	 *
	 * @param string $singular
	 * @param string $plural
	 * @param int $count
	 * @return string $result
	 * 2009-07-23 ms
	 */
	public function sp($s, $p, $c, $autoTranslate = false) {
		if ((int)$c !== 1) {
			$ret = $p;
		} else {
		 		$ret = $s;
		}

		if ($autoTranslate) {
			$ret = __($ret);
		}
		return $ret;
	}

	/**
	 * Show FlashMessages
	 * @param boolean unsorted true/false [default:FALSE = sorted by priority]
	 * TODO: export div wrapping method (for static messaging on a page)
	 * TODO: sorting
	 * 2010-11-22 ms
	 */
	public function flash($unsorted = false, $backwardsComp = true) {
		// Get the messages from the session
		$messages = (array)$this->Session->read('messages');
		$cMessages = (array)Configure::read('messages');
		if (!empty($cMessages)) {
			$messages = (array)Set::merge($messages, $cMessages);
		}
		$html='';
		if (!empty($messages)) {
			$html = '<div class="flashMessages">';

			if ($unsorted !== true) {
				// Add a div for each message using the type as the class
				foreach ($messages as $type => $msgs) {
					foreach ((array)$msgs as $msg) {
						$html .= $this->_message($msg, $type);
					}
				}
			} else {
				foreach ($messages as $type) {
					//
				}
			}
			$html .= '</div>';
			if (method_exists($this->Session, 'delete')) {
				$this->Session->delete('messages');
			} else {
				CakeSession::delete('messages');
			}
		}

		return $html;
	}

	/**
	 * output a single flashMessage
	 * 2010-11-22 ms
	 */
	public function flashMessage($msg, $type = 'info', $escape = true) {
		$html = '<div class="flashMessages">';
		if ($escape) {
			$msg = h($msg);
		}
		$html .= $this->_message($msg, $type);
		$html .= '</div>';
		return $html;
	}

	protected function _message($msg, $type) {
		if (!empty($msg)) {
			return '<div class="message'.(!empty($type) ? ' '.$type : '').'">'.$msg.'</div>';
		}
		return '';
	}

	/**
	 * add a message on the fly
	 *
	 * @param string $msg
	 * @param string $class
	 * @return bool $success
	 * 2011-05-25 ms
	 */
	public function transientFlashMessage($msg, $class = null) {
		return CommonComponent::transientFlashMessage($msg, $class);
	}

	/**
	 * TODO: move into TextExt?
	 * escape text with some more automagic
	 *
	 * @param string $text
	 * @param array $options
	 * @return string $processedText
	 * - nl2br: true/false (defaults to true)
	 * - escape: false prevents h() and space transformation (defaults to true)
	 * - tabsToSpaces: int (defaults to 4)
	 * 2010-11-20 ms
	 */
	public function esc($text, $options = array()) {
		if (!isset($options['escape']) || $options['escape'] !== false) {
			//$text = str_replace(' ', '&nbsp;', h($text));
			$text = h($text);
			# try to fix indends made out of spaces
			$text = explode(NL, $text);
			foreach ($text as $key => $t) {
				$i = 0;
				while (!empty($t[$i]) && $t[$i] === ' ') {
					$i++;
				}
				if ($i > 0) {
					$t = str_repeat('&nbsp;', $i) . substr($t, $i);
					$text[$key] = $t;
				}
			}
			$text = implode(NL, $text);
			$esc = true;
		}
		if (!isset($options['nl2br']) || $options['nl2br'] !== false) {
			$text = nl2br($text);
		}
		if (!isset($options['tabsToSpaces'])) {
			$options['tabsToSpaces'] = 4;
		}
		if (!empty($options['tabsToSpaces'])) {

			$text = str_replace(TB, str_repeat(!empty($esc) ? '&nbsp;' : ' ', $options['tabsToSpaces']), $text);
		}

		return $text;
	}



	/**
	 * prevents site being opened/included by others/websites inside frames
	 * 2009-01-08 ms
	 */
	public function framebuster() {
	 	return $this->Html->scriptBlock('
if (top!=self) top.location.ref=self.location.href;
');
	}

	/**
	 * currenctly only alerts on IE6/IE7
	 * options
	 * - engine (js, jquery)
	 * - escape
	 * needs the id element to be a present (div) container in the layout
	 * 2009-12-26 ms
	 **/
	public function browserAlert($id, $message, $options = array()) {
		$engine = 'js';

		if (!isset($options['escape']) || $options['escape'] !== false) {
				$message = h($message);
		}
	 	return $this->Html->scriptBlock('
// Returns the version of Internet Explorer or a -1
function getInternetExplorerVersion() {
	var rv = -1; // Return value assumes failure.
	if (navigator.appName == "Microsoft Internet Explorer") {
	var ua = navigator.userAgent;
	var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
	if (re.exec(ua) != null)
		rv = parseFloat( RegExp.$1 );
	}
	return rv;
}

if ((document.all) && (navigator.appVersion.indexOf("MSIE 7.") != -1) || typeof document.body.style.maxHeight === "undefined") {
	document.getElementById(\''.$id.'\').innerHTML = \''.$message.'\';
}
/*
jQuery(document).ready(function() {
	if ($.browser.msie && $.browser.version.substring(0,1) < 8) {
		document.getElementById(\''.$id.'\').innerHTML = \''.$message.'\';
	}
});
*/
');
/*
if ($.browser.msie) {
	var version = $.browser.version.substring(0,1);
}
*/
	}

	/**
	 * in noscript tags:
	 * - link which should not be followed by bots!
	 * - "pseudo"image which triggers log
	 * 2009-12-28 ms
	 */
	public function honeypot($noFollowUrl, $noscriptUrl = array()) {
		$res = '<div class="invisible" style="display:none"><noscript>';
		$res .= $this->Html->defaultLink('Email', $noFollowUrl, array('rel'=>'nofollow'));

		if (!empty($noscriptUrl)) {
			$res .= BR.$this->Html->image($this->Html->defaultUrl($noscriptUrl, true)); //$this->Html->link($noscriptUrl);
		}

		$res .= '</noscript></div>';
		return $res;
	}


/*** Stats ***/

	/**
	 * print js-visit-stats-link to layout
	 * uses Piwik open source statistics framework
	 * 2009-04-15 ms
	 */

	public function visitStats($viewPath = null) {
		$res = '';
		if (!defined('HTTP_HOST_LIVESERVER')) {
			return '';
		}
		if (HTTP_HOST == HTTP_HOST_LIVESERVER && (int)Configure::read('Config.tracking') === 1) {
			$trackingUrl = Configure::read('Config.tracking_url');
			if (empty($trackingUrl)) {
				$trackingUrl = 'visit_stats';
			}
			$error = false;
			if (!empty($viewPath) && $viewPath == 'errors') {
				$error = true;
			}
$res .= '
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://'.HTTP_HOST.'/'.$trackingUrl.'/" : "http://'.HTTP_HOST.'/'.$trackingUrl.'/");
document.write(unescape("%3Cscript src=\'" + pkBaseURL + "piwik.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
'.($error?'piwikTracker.setDocumentTitle(\'404/URL = \'+encodeURIComponent(document.location.pathname+document.location.search) + \'/From = \' + encodeURIComponent(document.referrer));':'').'
} catch( err ) {}
</script>
<noscript><p>'.$this->visitStatsImg().'</p></noscript>
';
		}
		return $res;
	}

	/**
	 * non js browsers
	 * 2009-09-07 ms
	 */
	public function visitStatsImg($trackingUrl = null) {
		if (empty($trackingUrl)) {
			$trackingUrl = Configure::read('Config.tracking_url');
		}
		if (empty($trackingUrl)) {
			$trackingUrl = 'visit_stats';
		}
		return '<img src="'.Router::url('/').$trackingUrl.'/piwik.php?idsite=1" style="border:0" alt=""/>';
	}


/*** deprecated ***/

	/**
	 * SINGLE ROLES function
	 * currently: isRole('admin'), isRole('user')
	 *
	 * @deprecated - use Auth class instead
	 * 2009-07-06 ms
	 */
	public function isRole($role) {
		$sessionRole = $this->Session->read('Auth.User.role_id');
		$roles = array(
			ROLE_USER => 'user',
			ROLE_ADMIN => 'admin',
			ROLE_SUPERADMIN => 'superadmin',
			ROLE_GUEST => 'guest',
		);
		if (!empty($roles[$sessionRole]) && $role = $roles[$sessionRole]) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if a role is in the current users session
	 *
	 * @param necessary right(s) as array - or a single one as string possible
	 * Note: all of them need to be in the user roles to return true by default
	 * @deprecated - use Auth class instead
	 */
	public function roleNames($sessionRoles = null) {
		$tmp = array();

		if ($sessionRoles === null) {
			$sessionRoles = $this->Session->read('Auth.User.Role');
		}

		$roles = Cache::read('User.Role');

		if (empty($roles) || !is_array($roles)) {
			$Role = ClassRegistry::init('Role');
			/*
			if ($Role->useDbConfig == 'test_suite') {
				return array();
			}
			*/
			$roles = $Role->getActive('list');
			Cache::write('User.Role', $roles);
		}
		//$roleKeys = Set::combine($roles, '/Role/id','/Role/name'); // on find(all)
		if (!empty($sessionRoles)) {
			if (is_array($sessionRoles)) {

				foreach ($sessionRoles as $sessionRole) {
					if (!$sessionRole) {
					continue;
					}
					if (array_key_exists((int)$sessionRole, $roles)) {
						$tmp[$sessionRole] = $roles[(int)$sessionRole];
					}
				}
			} else {
				if (array_key_exists($sessionRoles, $roles)) {
					$tmp[$sessionRoles] = $roles[$sessionRoles];
				}
			}
		}

		return $tmp;
	}

	/**
	 * Display Roles separated by Commas
	 * @deprecated - use Auth class instead
	 * 2009-07-17 ms
	 */
	public function displayRoles($sessionRoles = null, $placeHolder = '---') {
		$roles = $this->roleNames($sessionRoles);
		if (!empty($roles)) {
			return implode(', ', $roles);
		}
		return $placeHolder;
	}

	/**
	 * takes int / array(int) and finds the role name to it
	 * @return array roles
	 */
	public function roleNamesTranslated($value) {
		if (empty($value)) { return array(); }
		$ret = array();
		$translate = (array)Configure::read('Role');
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$ret[$v] = __($translate[$v]);
			}
		} else {
			$ret[$value] = __($translate[$value]);
		}
		return $ret;
	}

	/**
	 * @deprecated
	 */
	public function showDebug() {
		$output = '';
		$groupout = '';
		foreach (debugTab::$content as $group => $debug) {
			if (is_int($group)) {
				$output .= '<div class="common-debug">';
				$output .= "<span style=\"cursor:pointer\" onclick=\"$(this).parent().children('pre').slideToggle('fast');\"><strong>" . h($debug['file']) . '</strong>';
				$output .= ' (line <strong>' . $debug['line'] . '</strong>)</span>';
				if ($debug['display'])
					$debug['display'] = 'block';
				else
					$debug['display'] = 'none';
				$output .= "\n<pre style=\"display:" . $debug['display'] . "\" class=\"cake-debug\">\n";
				$output .= h($debug['debug']);
				$output .= "\n</pre>\n</div>";
			}
		}
		foreach (debugTab::$groups as $group => $data) {
			$groupout .= '<div class="common-debug">';
			$groupout .= "<span style=\"cursor:pointer\" onclick=\"$(this).parent().children('div').slideToggle('fast');\"><strong>" . h($group) . '</strong></span>';
			foreach ($data as $debug) {
				$groupout .= "<div style=\"display:none\"><br/><span style=\"cursor:pointer\" onclick=\"$(this).parent().children('pre').slideToggle('fast');\"><strong>" . h($debug['file']) . '</strong></span>';
				$groupout .= ' (line <strong>' . h($debug['line']) . '</strong>)</span>';
				if ($debug['display'])
					$debug['display'] = 'block';
				else
					$debug['display'] = 'none';
				$groupout .= "\n<pre style=\"display:" . $debug['display'] . "\" class=\"cake-debug\">\n";
				$groupout .= h($debug['debug']);
				$groupout .= "\n</pre>\n</div>";
			}
			$groupout .= "\n</div>";
		}
		return $groupout . $output;
	}

	/**
	 * Creates an HTML link.
	 *
	 * If $url starts with "http://" this is treated as an external link. Else,
	 * it is treated as a path to controller/action and parsed with the
	 * HtmlHelper::url() method.
	 *
	 * If the $url is empty, $title is used instead.
	 *
	 * @param string $title The content to be wrapped by <a> tags.
	 * @param mixed $url Cake-relative URL or array of URL parameters, or external URL (starts with http://)
	 * @param array $htmlAttributes Array of HTML attributes.
	 * @param string $confirmMessage JavaScript confirmation message.
	 * @param boolean $escapeTitle	Whether or not $title should be HTML escaped.
	 * @return string	An <a /> element.
	 * @deprecated?
	 * // core-hack! $rel = null | !!!!!!!!! Somehow causes trouble with routing functionality of this helper function... careful!
	 */
	public function link($title, $url = null, $htmlAttributes = array(), $confirmMessage = false, $escapeTitle = true, $rel = null) {
		if ($url !== null) {
			/** core-hack $rel (relative to current position/routing) **/
			if ($rel === true || !is_array($url)) {
				// leave it as it is
			} else {
				$defaultArray = array('admin'=>false, 'prefix'=>0);
				$url = array_merge($defaultArray, $url);
			}
			/** core-hack END **/
			return $this->Html->link($title, $url, $htmlAttributes, $confirmMessage, $escapeTitle);
		}
	}

}