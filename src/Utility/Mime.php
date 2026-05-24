<?php

namespace Tools\Utility;

use Cake\Http\MimeType;
use Cake\Http\Response;
use ReflectionClass;

/**
 * Wrapper to be able to read cake core's mime types as well as fix for missing ones
 *
 * @version 1.0
 * @license MIT
 * @author Mark Scherer
 * @deprecated This is for use with CakePHP <=5.1. For 5.2+ use MimeType instead.
 */
class Mime extends Response {

	/**
	 * @var array<string, array>
	 */
	protected array $_mimeTypesExt = [
		'3dm' => 'x-world/x-3dmf',
		'3dmf' => 'x-world/x-3dmf',
		'a' => 'application/octet-stream',
		'aab' => 'application/x-authorware-bin',
		'aam' => 'application/x-authorware-map',
		'aas' => 'application/x-authorware-seg',
		'abc' => 'text/vnd.abc',
		'acgi' => 'text/html',
		'afl' => 'video/animaflex',
		'aim' => 'application/x-aim',
		'aip' => 'text/x-audiosoft-intra',
		'ani' => 'application/x-navi-animation',
		'aos' => 'application/x-nokia-9000-communicator-add-on-software',
		'aps' => 'application/mime',
		'arc' => 'application/octet-stream',
		'arj' => 'application/arj',
		'art' => 'image/x-jg',
		'asf' => 'video/x-ms-asf',
		'asm' => 'text/x-asm',
		'asp' => 'text/asp',
		'asx' => 'video/x-ms-asf-plugin',
		'avs' => 'video/avs-video',
		'bin' => 'application/x-macbinary',
		'bm' => 'image/bmp',
		'boo' => 'application/book',
		'book' => 'application/book',
		'boz' => 'application/x-bzip2',
		'bsh' => 'application/x-bsh',
		'bz' => 'application/x-bzip',
		'bz2' => 'application/x-bzip2',
		'c' => 'text/x-c',
		'c++' => 'text/plain',
		'cat' => 'application/vnd.ms-pki.seccat',
		'cc' => 'text/x-c',
		'ccad' => 'application/clariscad',
		'cco' => 'application/x-cocoa',
		'cdf' => 'application/x-netcdf',
		'cer' => 'application/x-x509-ca-cert',
		'cha' => 'application/x-chat',
		'chat' => 'application/x-chat',
		'class' => 'application/x-java-class',
		'com' => 'text/plain',
		'conf' => 'text/plain',
		'cpio' => 'application/x-cpio',
		'cpp' => 'text/x-c',
		'cpt' => 'application/x-cpt',
		'crl' => 'application/pkix-crl',
		'crt' => 'application/x-x509-user-cert',
		'csh' => 'text/x-script.csh',
		'cxx' => 'text/plain',
		'dcr' => 'application/x-director',
		'deepv' => 'application/x-deepv',
		'def' => 'text/plain',
		'der' => 'application/x-x509-ca-cert',
		'dif' => 'video/x-dv',
		'dir' => 'application/x-director',
		'dl' => 'video/dl',
		'dot' => 'application/msword',
		'dp' => 'application/commonground',
		'drw' => 'application/drafting',
		'dump' => 'application/octet-stream',
		'dv' => 'video/x-dv',
		'dvi' => 'application/x-dvi',
		'dwf' => 'model/vnd.dwf',
		'dwg' => 'image/vnd.dwg',
		'dxf' => 'image/vnd.dwg',
		'dxr' => 'application/x-director',
		'el' => 'text/x-script.elisp',
		'elc' => 'application/x-elc',
		'env' => 'application/x-envoy',
		'es' => 'application/x-esrehber',
		'etx' => 'text/x-setext',
		'evy' => 'application/x-envoy',
		'f' => 'text/x-fortran',
		'f77' => 'text/x-fortran',
		'f90' => 'text/x-fortran',
		'fdf' => 'application/vnd.fdf',
		'fif' => 'image/fif',
		'fli' => 'video/x-fli',
		'flo' => 'image/florian',
		'flx' => 'text/vnd.fmi.flexstor',
		'fmf' => 'video/x-atomic3d-feature',
		'for' => 'text/plain',
		'fpx' => 'image/vnd.fpx',
		'frl' => 'application/freeloader',
		'funk' => 'audio/make',
		'g' => 'text/plain',
		'g3' => 'image/g3fax',
		'gl' => 'video/gl',
		'gsd' => 'audio/x-gsm',
		'gsm' => 'audio/x-gsm',
		'gsp' => 'application/x-gsp',
		'gss' => 'application/x-gss',
		'gtar' => 'application/x-gtar',
		'gz' => 'application/x-compressed',
		'gzip' => 'application/x-gzip',
		'h' => 'text/plain',
		'hdf' => 'application/x-hdf',
		'help' => 'application/x-helpfile',
		'hgl' => 'application/vnd.hp-hpgl',
		'hh' => 'text/plain',
		'hlb' => 'text/x-script',
		'hlp' => 'application/hlp',
		'hpg' => 'application/vnd.hp-hpgl',
		'hpgl' => 'application/vnd.hp-hpgl',
		'hqx' => 'application/binhex',
		'hta' => 'application/hta',
		'htc' => 'text/x-component',
		'htmls' => 'text/html',
		'htt' => 'text/webviewhtml',
		'htx' => 'text/html',
		'ics' => 'application/ics', // important for ipad to properly display ics files
		'ical' => 'text/calendar',
		'idc' => 'text/plain',
		'ief' => 'image/ief',
		'iefs' => 'image/ief',
		'ifb' => 'text/calendar',
		'iges' => 'application/iges',
		'igs' => 'application/iges',
		'ima' => 'application/x-ima',
		'imap' => 'application/x-httpd-imap',
		'inf' => 'application/inf',
		'ins' => 'application/x-internett-signup',
		'ip' => 'application/x-ip2',
		'isu' => 'video/x-isvideo',
		'it' => 'audio/it',
		'iv' => 'application/x-inventor',
		'ivr' => 'i-world/i-vrml',
		'ivy' => 'application/x-livescreen',
		'jam' => 'audio/x-jam',
		'jav' => 'text/plain',
		'java' => 'text/plain',
		'jcm' => 'application/x-java-commerce',
		'jfif' => 'image/jpeg',
		'jfif-tbnl' => 'image/jpeg',
		'jps' => 'image/x-jps',
		'jut' => 'image/jutvision',
		'kar' => 'music/x-karaoke',
		'ksh' => 'text/x-script.ksh',
		'la' => 'audio/x-nspaudio',
		'lam' => 'audio/x-liveaudio',
		'latex' => 'application/x-latex',
		'lha' => 'application/x-lha',
		'lhx' => 'application/octet-stream',
		'list' => 'text/plain',
		'lma' => 'audio/x-nspaudio',
		'log' => 'text/plain',
		'lsp' => 'application/x-lisp',
		'lst' => 'text/plain',
		'lsx' => 'text/x-la-asf',
		'ltx' => 'application/x-latex',
		'lzh' => 'application/octet-stream',
		'lzx' => 'application/lzx',
		'm' => 'text/plain',
		'm1v' => 'video/mpeg',
		'm2a' => 'audio/mpeg',
		'm2v' => 'video/mpeg',
		'm3u' => 'audio/x-mpequrl',
		'man' => 'application/x-troff-man',
		'map' => 'application/x-navimap',
		'mar' => 'text/plain',
		'mbd' => 'application/mbedlet',
		'mc$' => 'application/x-magic-cap-package-1.0',
		'mcd' => 'application/mcad',
		'mcf' => 'image/vasa',
		'mcp' => 'application/netmc',
		'me' => 'application/x-troff-me',
		'mht' => 'message/rfc822',
		'mhtml' => 'message/rfc822',
		'mid' => 'audio/midi',
		'midi' => 'audio/midi',
		'mif' => 'application/x-frame',
		'mime' => 'message/rfc822',
		'mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
		'mjpg' => 'video/x-motion-jpeg',
		'mm' => 'application/x-meme',
		'mme' => 'application/base64',
		'mod' => 'audio/x-mod',
		'moov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie',
		'mp2' => 'video/x-mpeq2a',
		'mpa' => 'video/mpeg',
		'mpc' => 'application/x-project',
		'mpe' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpga' => 'audio/mpeg',
		'mpp' => 'application/vnd.ms-project',
		'mpt' => 'application/x-project',
		'mpv' => 'application/x-project',
		'mpx' => 'application/x-project',
		'mrc' => 'application/marc',
		'ms' => 'application/x-troff-ms',
		'mv' => 'video/x-sgi-movie',
		'my' => 'audio/make',
		'mzz' => 'application/x-vnd.audioexplosion.mzz',
		'nap' => 'image/naplps',
		'naplps' => 'image/naplps',
		'nc' => 'application/x-netcdf',
		'ncm' => 'application/vnd.nokia.configuration-message',
		'nif' => 'image/x-niff',
		'niff' => 'image/x-niff',
		'nix' => 'application/x-mix-transfer',
		'nsc' => 'application/x-conference',
		'nvd' => 'application/x-navidoc',
		'o' => 'application/octet-stream',
		'oda' => 'application/oda',
		'omc' => 'application/x-omc',
		'omcd' => 'application/x-omcdatamaker',
		'omcr' => 'application/x-omcregerator',
		'p' => 'text/x-pascal',
		'p10' => 'application/x-pkcs10',
		'p12' => 'application/x-pkcs12',
		'p7a' => 'application/x-pkcs7-signature',
		'p7c' => 'application/x-pkcs7-mime',
		'p7m' => 'application/x-pkcs7-mime',
		'p7r' => 'application/x-pkcs7-certreqresp',
		'p7s' => 'application/pkcs7-signature',
		'part' => 'application/pro_eng',
		'pas' => 'text/pascal',
		'pbm' => 'image/x-portable-bitmap',
		'pcl' => 'application/x-pcl',
		'pct' => 'image/x-pict',
		'pcx' => 'image/x-pcx',
		'pdb' => 'chemical/x-pdb',
		'pfunk' => 'audio/make.my.funk',
		'pgm' => 'image/x-portable-greymap',
		'pic' => 'image/pict',
		'pict' => 'image/pict',
		'pkg' => 'application/x-newton-compatible-pkg',
		'pko' => 'application/vnd.ms-pki.pko',
		'pl' => 'text/x-script.perl',
		'plx' => 'application/x-pixclscript',
		'pm' => 'text/x-script.perl-module',
		'pm4' => 'application/x-pagemaker',
		'pm5' => 'application/x-pagemaker',
		'pnm' => 'image/x-portable-anymap',
		'pot' => 'application/vnd.ms-powerpoint',
		'pov' => 'model/x-pov',
		'ppa' => 'application/vnd.ms-powerpoint',
		'ppm' => 'image/x-portable-pixmap',
		'pps' => 'application/vnd.ms-powerpoint',
		'ppz' => 'application/mspowerpoint',
		'pre' => 'application/x-freelance',
		'prt' => 'application/pro_eng',
		'pvu' => 'paleovu/x-pv',
		'pwz' => 'application/vnd.ms-powerpoint',
		'py' => 'text/x-script.phyton',
		'pyc' => 'applicaiton/x-bytecode.python',
		'qcp' => 'audio/vnd.qcelp',
		'qd3' => 'x-world/x-3dmf',
		'qd3d' => 'x-world/x-3dmf',
		'qif' => 'image/x-quicktime',
		'qtc' => 'video/x-qtc',
		'qti' => 'image/x-quicktime',
		'qtif' => 'image/x-quicktime',
		'ra' => 'audio/x-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'ras' => 'image/x-cmu-raster',
		'rast' => 'image/cmu-raster',
		'rexx' => 'text/x-script.rexx',
		'rf' => 'image/vnd.rn-realflash',
		'rgb' => 'image/x-rgb',
		'rm' => 'audio/x-pn-realaudio',
		'rmi' => 'audio/mid',
		'rmm' => 'audio/x-pn-realaudio',
		'rmp' => 'audio/x-pn-realaudio-plugin',
		'rng' => 'application/vnd.nokia.ringing-tone',
		'rnx' => 'application/vnd.rn-realplayer',
		'roff' => 'application/x-troff',
		'rp' => 'image/vnd.rn-realpix',
		'rpm' => 'audio/x-pn-realaudio-plugin',
		'rt' => 'text/vnd.rn-realtext',
		'rtx' => 'text/richtext',
		'rv' => 'video/vnd.rn-realvideo',
		's' => 'text/x-asm',
		's3m' => 'audio/s3m',
		'saveme' => 'application/octet-stream',
		'sbk' => 'application/x-tbook',
		'scm' => 'video/x-scm',
		'sdml' => 'text/plain',
		'sdp' => 'application/x-sdp',
		'sdr' => 'application/sounder',
		'sea' => 'application/x-sea',
		'set' => 'application/set',
		'sgm' => 'text/x-sgml',
		'sgml' => 'text/x-sgml',
		'sh' => 'text/x-script.sh',
		'shar' => 'application/x-shar',
		'shtml' => 'text/html',
		'sid' => 'audio/x-psid',
		'sit' => 'application/x-stuffit',
		'skd' => 'application/x-koan',
		'skm' => 'application/x-koan',
		'skp' => 'application/x-koan',
		'skt' => 'application/x-koan',
		'sl' => 'application/x-seelogo',
		'smi' => 'application/smil',
		'smil' => 'application/smil',
		'snd' => 'audio/x-adpcm',
		'sol' => 'application/solids',
		'spc' => 'text/x-speech',
		'spl' => 'application/futuresplash',
		'spr' => 'application/x-sprite',
		'sprite' => 'application/x-sprite',
		'src' => 'application/x-wais-source',
		'ssi' => 'text/x-server-parsed-html',
		'ssm' => 'application/streamingmedia',
		'sst' => 'application/vnd.ms-pki.certstore',
		'step' => 'application/step',
		'stl' => 'application/x-navistyle',
		'stp' => 'application/step',
		'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc',
		'svf' => 'image/x-dwg',
		'svr' => 'x-world/x-svr',
		't' => 'application/x-troff',
		'talk' => 'text/x-speech',
		'tar' => 'application/x-tar',
		'tbk' => 'application/x-tbook',
		'tcl' => 'text/x-script.tcl',
		'tcsh' => 'text/x-script.tcsh',
		'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo',
		'texinfo' => 'application/x-texinfo',
		'text' => 'text/plain',
		'tgz' => 'application/x-compressed',
		'tr' => 'application/x-troff',
		'tsi' => 'audio/tsp-audio',
		'tsp' => 'audio/tsplayer',
		'tsv' => 'text/tab-separated-values',
		'turbot' => 'image/florian',
		'uil' => 'text/x-uil',
		'uni' => 'text/uri-list',
		'unis' => 'text/uri-list',
		'unv' => 'application/i-deas',
		'uri' => 'text/uri-list',
		'uris' => 'text/uri-list',
		'ustar' => 'multipart/x-ustar',
		'uu' => 'text/x-uuencode',
		'uue' => 'text/x-uuencode',
		'vcd' => 'application/x-cdlink',
		'vcs' => 'text/x-vcalendar',
		'vda' => 'application/vda',
		'vdo' => 'video/vdo',
		'vew' => 'application/groupwise',
		'viv' => 'video/vnd.vivo',
		'vivo' => 'video/vnd.vivo',
		'vmd' => 'application/vocaltec-media-desc',
		'vmf' => 'application/vocaltec-media-file',
		'voc' => 'audio/x-voc',
		'vos' => 'video/vosaic',
		'vox' => 'audio/voxware',
		'vqe' => 'audio/x-twinvq-plugin',
		'vqf' => 'audio/x-twinvq',
		'vql' => 'audio/x-twinvq-plugin',
		'vrml' => 'x-world/x-vrml',
		'vrt' => 'x-world/x-vrt',
		'vsd' => 'application/x-visio',
		'vst' => 'application/x-visio',
		'vsw' => 'application/x-visio',
		'w60' => 'application/wordperfect6.0',
		'w61' => 'application/wordperfect6.1',
		'w6w' => 'application/msword',
		'wav' => 'audio/x-wav',
		'wb1' => 'application/x-qpro',
		'wbmp' => 'image/vnd.wap.wbmp',
		'web' => 'application/vnd.xara',
		'wiz' => 'application/msword',
		'wk1' => 'application/x-123',
		'wmf' => 'windows/metafile',
		'wml' => 'text/vnd.wap.wml',
		'wmlc' => 'application/vnd.wap.wmlc',
		'wmls' => 'text/vnd.wap.wmlscript',
		'wmlsc' => 'application/vnd.wap.wmlscriptc',
		'word' => 'application/msword',
		'wp' => 'application/wordperfect',
		'wp5' => 'application/wordperfect6.0',
		'wp6' => 'application/wordperfect',
		'wpd' => 'application/x-wpwin',
		'wq1' => 'application/x-lotus',
		'wri' => 'application/x-wri',
		'wrl' => 'x-world/x-vrml',
		'wrz' => 'x-world/x-vrml',
		'wsc' => 'text/scriplet',
		'wsrc' => 'application/x-wais-source',
		'wtk' => 'application/x-wintalk',
		'xbm' => 'image/xbm',
		'xdr' => 'video/x-amt-demorun',
		'xgz' => 'xgl/drawing',
		'xif' => 'image/vnd.xiff',
		'xl' => 'application/excel',
		'xla' => 'application/x-msexcel',
		'xlb' => 'application/x-excel',
		'xlc' => 'application/x-excel',
		'xld' => 'application/x-excel',
		'xlk' => 'application/x-excel',
		'xll' => 'application/x-excel',
		'xlm' => 'application/x-excel',
		'xlt' => 'application/x-excel',
		'xlv' => 'application/x-excel',
		'xlw' => 'application/x-msexcel',
		'xm' => 'audio/xm',
		'xmz' => 'xgl/movie',
		'xpix' => 'application/x-vnd.ls-xpix',
		'xpm' => 'image/xpm',
		'x-png' => 'image/png',
		'xsr' => 'video/x-amt-showrun',
		'xwd' => 'image/x-xwindowdump',
		'xyz' => 'chemical/x-pdb',
		'z' => ['application/x-compress', 'application/x-compressed'],
		'zoo' => 'application/octet-stream',
		'zsh' => 'text/x-script.zsh',
		'txt' => 'text/plain',
		'php' => 'application/x-httpd-php',
		'phps' => 'application/x-httpd-phps',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'flv' => 'video/x-flv',
		'asc' => 'text/plain',
		'atom' => 'application/atom+xml',
		'bcpio' => 'application/x-bcpio',
		'png' => 'image/png',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'bmp' => 'image/bmp',
		'ico' => 'image/vnd.microsoft.icon',
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'exe' => 'application/x-msdownload',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',
		'mp3' => 'audio/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'au' => 'audio/basic',
		'avi' => 'video/x-msvideo',
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'ps' => 'application/postscript',
		'aif' => 'audio/x-aiff',
		'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'swf' => ['application/x-shockwave-flash', 'application/x-shockwave-flash2-preview', 'application/futuresplash', 'image/vnd.rn-realflash'],
	];

	protected array $_mimeTypesCore;

	/**
	 * @var array<string, array>
	 */
	protected ?array $_mimeTypesTmp = null;

	/**
     * Override constructor
     */
	public function __construct(array $options = []) {
		parent::__construct($options);

		if (class_exists(MimeType::class)) {
			$mimeType = new MimeType();
			$coreMimeTypes = $this->invokeProperty($mimeType, 'mimeTypes');
		} else {
			$response = new Response();
			$coreMimeTypes = $this->invokeProperty($response, '_mimeTypes');
		}
		$this->_mimeTypesCore = $coreMimeTypes;
	}

	/**
	 * @param object $object
	 * @param string $name
	 * @throws \ReflectionException
	 * @return mixed
	 */
	protected function invokeProperty(object &$object, string $name): mixed {
		$reflection = new ReflectionClass(get_class($object));
		if (!$reflection->hasProperty($name)) {
			return null;
		}
		$property = $reflection->getProperty($name);

		return $property->getValue($object);
	}

	/**
	 * Get all mime types that are supported right now
	 *
	 * @param bool $coreHasPrecedence
	 * @return array
	 */
	public function mimeTypes($coreHasPrecedence = false) {
		if ($coreHasPrecedence) {
			return $this->_mimeTypesCore += $this->_mimeTypesExt;
		}

		return $this->_mimeTypesExt += $this->_mimeTypesCore;
	}

	/**
	 * Returns the primary mime type definition for an alias/extension.
	 *
	 * e.g `getMimeType('pdf'); // returns 'application/pdf'`
	 *
	 * @param string $alias the content type alias to map
	 * @param bool $primaryOnly
	 * @param bool $coreHasPrecedence
	 * @return array|string|null Mapped mime type or null if $alias is not mapped
	 */
	public function getMimeTypeByAlias(
		string $alias,
		bool $primaryOnly = true,
		bool $coreHasPrecedence = false,
	) {
		if (!$this->_mimeTypesTmp) {
			$this->_mimeTypesTmp = $this->mimeTypes($coreHasPrecedence);
		}
		if (!isset($this->_mimeTypesTmp[$alias])) {
			return null;
		}
		$mimeType = $this->_mimeTypesTmp[$alias];
		if ($primaryOnly && is_array($mimeType)) {
			$mimeType = array_shift($mimeType);
		}

		return $mimeType;
	}

	/**
	 * Maps a content-type back to an alias
	 *
	 * e.g `mapType('application/pdf'); // returns 'pdf'`
	 *
	 * @param array|string $ctype Either a string content type to map, or an array of types.
	 * @return array|string|null Aliases for the types provided.
	 */
	public function mapType(array|string $ctype): array|string|null {
		return parent::mapType($ctype);
	}

	/**
	 * Retrieve the corresponding MIME type, if one exists
	 *
	 * @param string|null $file File Name (relative location such as "image_test.jpg" or full "http://site.com/path/to/image_test.jpg")
	 *
	 * @return string MIMEType - The type of the file passed in the argument
	 */
	public function detectMimeType(?string $file = null): string {
		// Attempts to retrieve file info from FINFO
		// If FINFO functions are not available then try to retrieve MIME type from pre-defined MIMEs
		// If MIME type doesn't exist, then try (as a last resort) to use the (deprecated) mime_content_type function
		// If all else fails, just return application/octet-stream
		if (!function_exists('finfo_open')) {
			if (function_exists('mime_content_type')) {
				$type = mime_content_type($file);
				if (!empty($type)) {
					return $type;
				}
			}
			$extension = static::_getExtension($file);
			/** @var string|null $mimeType */
			$mimeType = $this->getMimeTypeByAlias($extension);
			if ($mimeType) {
				return $mimeType;
			}

			return 'application/octet-stream';
		}

		return static::_detectMimeType($file);
	}

	/**
	 * Utility::getMimeType()
	 *
	 * @param string $file File
	 * @return string Mime type
	 */
	public static function _detectMimeType($file) {
		if (!function_exists('finfo_open')) {
			//throw new InternalErrorException('finfo_open() required - please enable');
		}

		// Treat non local files differently
		$pattern = '~^https?://~i';
		if (preg_match($pattern, $file)) {
			$context = stream_context_create([
				'http' => [
					'timeout' => 5,
				],
			]);
			// phpcs:disable
			$headers = @get_headers($file, false, $context);
			// phpcs:enable
			if (!$headers || !preg_match("|\b200\b|", $headers[0])) {
				return '';
			}
			foreach ($headers as $header) {
				if (str_starts_with($header, 'Content-Type:')) {
					return trim(substr($header, 13));
				}
			}

			return '';
		}

		if (!is_file($file)) {
			return '';
		}

		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $file);
		$pos = strpos($mimetype, ';');
		if ($pos !== false) {
			$mimetype = substr($mimetype, 0, $pos);
		}
		if ($mimetype) {
			return $mimetype;
		}
		$extension = static::_getExtension($file);
		/** @var string|null $mimeType */
		$mimeType = static::getMimeTypeByAlias($extension);
		if ($mimeType) {
			return $mimeType;
		}

		return 'application/octet-stream';
	}

	/**
	 * Get encoding.
	 *
	 * @param string|null $file
	 * @param string $default
	 * @return string
	 */
	public static function getEncoding(?string $file = null, string $default = 'utf-8'): string {
		if (!function_exists('finfo_open')) {
			return $default;
		}
		$finfo = finfo_open(FILEINFO_MIME_ENCODING);
		$encoding = finfo_file($finfo, $file);
		@finfo_close($finfo);
		if ($encoding !== false) {
			return $encoding;
		}

		return $default;
	}

	/**
	 * Gets the file extension from a string
	 *
	 * @param string $file The full file name
	 * @return string The file extension
	 */
	protected static function _getExtension(string $file): string {
		$pieces = explode('.', $file);

		return strtolower(array_pop($pieces));
	}

}
