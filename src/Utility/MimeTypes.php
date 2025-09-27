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
 */
class MimeTypes {

	/**
	 * @var array<string, array|string>
	 */
	protected array $_mimeTypes;

	/**
	 * @var array<string, array|string>
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
		'asx' => [
			'application/x-mplayer2',
			'video/x-ms-asf',
			'video/x-ms-asf-plugin',
		],
		'au' => [
			'audio/basic',
			'audio/x-au',
		],
		'avi' => [
			'application/x-troff-msvideo',
			'video/avi',
			'video/msvideo',
			'video/x-msvideo',
		],
		'avs' => 'video/avs-video',
		'bcpio' => 'application/x-bcpio',
		'bin' => [
			'application/mac-binary',
			'application/macbinary',
			'application/octet-stream',
			'application/x-binary',
			'application/x-macbinary',
		],
		'bm' => 'image/bmp',
		'bmp' => [
			'image/bmp',
			'image/x-windows-bmp',
		],
		'boo' => 'application/book',
		'book' => 'application/book',
		'boz' => 'application/x-bzip2',
		'bsh' => 'application/x-bsh',
		'bz' => 'application/x-bzip',
		'bz2' => 'application/x-bzip2',
		'c' => [
			'text/plain',
			'text/x-c',
		],
		'c++' => 'text/plain',
		'cat' => 'application/vnd.ms-pki.seccat',
		'cc' => [
			'text/plain',
			'text/x-c',
		],
		'ccad' => 'application/clariscad',
		'cco' => 'application/x-cocoa',
		'cdf' => [
			'application/cdf',
			'application/x-cdf',
			'application/x-netcdf',
		],
		'cer' => [
			'application/pkix-cert',
			'application/x-x509-ca-cert',
		],
		'cha' => 'application/x-chat',
		'chat' => 'application/x-chat',
		'class' => [
			'application/java',
			'application/java-byte-code',
			'application/x-java-class',
		],
		'com' => [
			'application/octet-stream',
			'text/plain',
		],
		'conf' => 'text/plain',
		'cpio' => 'application/x-cpio',
		'cpp' => 'text/x-c',
		'cpt' => [
			'application/mac-compactpro',
			'application/x-compactpro',
			'application/x-cpt',
		],
		'crl' => [
			'application/pkcs-crl',
			'application/pkix-crl',
		],
		'crt' => [
			'application/pkix-cert',
			'application/x-x509-ca-cert',
			'application/x-x509-user-cert',
		],
		'csh' => [
			'application/x-csh',
			'text/x-script.csh',
		],
		'css' => 'text/css',
		'cxx' => 'text/plain',
		'dcr' => 'application/x-director',
		'deepv' => 'application/x-deepv',
		'def' => 'text/plain',
		'der' => 'application/x-x509-ca-cert',
		'dif' => 'video/x-dv',
		'dir' => 'application/x-director',
		'dl' => 'video/dl',
		'doc' => 'application/msword',
		'dot' => 'application/msword',
		'dp' => 'application/commonground',
		'drw' => 'application/drafting',
		'dump' => 'application/octet-stream',
		'dv' => 'video/x-dv',
		'dvi' => 'application/x-dvi',
		'dwf' => 'model/vnd.dwf',
		'dwg' => [
			'application/acad',
			'image/vnd.dwg',
		],
		'dxf' => [
			'application/dxf',
			'image/vnd.dwg',
		],
		'dxr' => 'application/x-director',
		'el' => 'text/x-script.elisp',
		'elc' => [
			'application/x-bytecode.elisp',
			'application/x-elc',
		],
		'env' => 'application/x-envoy',
		'eps' => 'application/postscript',
		'es' => 'application/x-esrehber',
		'etx' => 'text/x-setext',
		'evy' => [
			'application/envoy',
			'application/x-envoy',
		],
		'exe' => [
			'application/octet-stream',
			'application/x-msdownload',
		],
		'f' => [
			'text/plain',
			'text/x-fortran',
		],
		'f77' => 'text/x-fortran',
		'f90' => [
			'text/plain',
			'text/x-fortran',
		],
		'fdf' => 'application/vnd.fdf',
		'fif' => [
			'application/fractals',
			'image/fif',
		],
		'fli' => [
			'video/fli',
			'video/x-fli',
		],
		'flo' => 'image/florian',
		'flx' => 'text/vnd.fmi.flexstor',
		'fmf' => 'video/x-atomic3d-feature',
		'for' => 'text/plain',
		'fpx' => 'image/vnd.fpx',
		'frl' => 'application/freeloader',
		'funk' => 'audio/make',
		'g' => 'text/plain',
		'g3' => 'image/g3fax',
		'gif' => 'image/gif',
		'gl' => 'video/gl',
		'gsd' => 'audio/x-gsm',
		'gsm' => 'audio/x-gsm',
		'gsp' => 'application/x-gsp',
		'gss' => 'application/x-gss',
		'gtar' => 'application/x-gtar',
		'gz' => [
			'application/x-compressed',
			'application/x-gzip',
		],
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
		'ico' => [
			'image/x-icon',
			'image/vnd.microsoft.icon',
		],
		'ics' => 'application/ics',
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
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jps' => 'image/x-jps',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'jut' => 'image/jutvision',
		'kar' => [
			'audio/midi',
			'music/x-karaoke',
		],
		'ksh' => [
			'application/x-ksh',
			'text/x-script.ksh',
		],
		'la' => [
			'audio/nspaudio',
			'audio/x-nspaudio',
		],
		'lam' => 'audio/x-liveaudio',
		'latex' => 'application/x-latex',
		'lha' => [
			'application/lha',
			'application/octet-stream',
			'application/x-lha',
		],
		'lhx' => 'application/octet-stream',
		'list' => 'text/plain',
		'lma' => [
			'audio/nspaudio',
			'audio/x-nspaudio',
		],
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
		'mm' => [
			'application/base64',
			'application/x-meme',
		],
		'mme' => 'application/base64',
		'mod' => [
			'audio/mod',
			'audio/x-mod',
		],
		'moov' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie',
		'mp2' => [
			'audio/mpeg',
			'audio/x-mpeg',
			'video/mpeg',
			'video/x-mpeg',
			'video/x-mpeq2a',
		],
		'mp3' => [
			'audio/mpeg',
			'audio/mpeg3',
			'audio/x-mpeg-3',
			'video/mpeg',
			'video/x-mpeg',
		],
		'mpa' => [
			'audio/mpeg',
			'video/mpeg',
		],
		'mpc' => 'application/x-project',
		'mpe' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'mpg' => [
			'audio/mpeg',
			'video/mpeg',
		],
		'mpga' => 'audio/mpeg',
		'mpp' => 'application/vnd.ms-project',
		'mpt' => 'application/x-project',
		'mpv' => 'application/x-project',
		'mpx' => 'application/x-project',
		'mrc' => 'application/marc',
		'ms' => 'application/x-troff-ms',
		'msi' => 'application/x-msdownload',
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
		'p10' => [
			'application/pkcs10',
			'application/x-pkcs10',
		],
		'p12' => [
			'application/pkcs-12',
			'application/x-pkcs12',
		],
		'p7a' => 'application/x-pkcs7-signature',
		'p7c' => [
			'application/pkcs7-mime',
			'application/x-pkcs7-mime',
		],
		'p7m' => [
			'application/pkcs7-mime',
			'application/x-pkcs7-mime',
		],
		'p7r' => 'application/x-pkcs7-certreqresp',
		'p7s' => 'application/pkcs7-signature',
		'part' => 'application/pro_eng',
		'pas' => 'text/pascal',
		'pbm' => 'image/x-portable-bitmap',
		'pcl' => [
			'application/vnd.hp-pcl',
			'application/x-pcl',
		],
		'pct' => 'image/x-pict',
		'pcx' => 'image/x-pcx',
		'pdb' => 'chemical/x-pdb',
		'pdf' => 'application/pdf',
		'pfunk' => [
			'audio/make',
			'audio/make.my.funk',
		],
		'pgm' => [
			'image/x-portable-graymap',
			'image/x-portable-greymap',
		],
		'php' => 'application/x-httpd-php',
		'phps' => 'application/x-httpd-phps',
		'pic' => 'image/pict',
		'pict' => 'image/pict',
		'pkg' => 'application/x-newton-compatible-pkg',
		'pko' => 'application/vnd.ms-pki.pko',
		'pl' => [
			'text/plain',
			'text/x-script.perl',
		],
		'plx' => 'application/x-pixclscript',
		'pm' => [
			'image/x-xpixmap',
			'text/x-script.perl-module',
		],
		'pm4' => 'application/x-pagemaker',
		'pm5' => 'application/x-pagemaker',
		'png' => 'image/png',
		'pnm' => [
			'application/x-portable-anymap',
			'image/x-portable-anymap',
		],
		'pot' => [
			'application/mspowerpoint',
			'application/vnd.ms-powerpoint',
		],
		'pov' => 'model/x-pov',
		'ppa' => 'application/vnd.ms-powerpoint',
		'ppm' => 'image/x-portable-pixmap',
		'pps' => [
			'application/mspowerpoint',
			'application/vnd.ms-powerpoint',
		],
		'ppt' => [
			'application/mspowerpoint',
			'application/powerpoint',
			'application/vnd.ms-powerpoint',
			'application/x-mspowerpoint',
		],
		'ppz' => 'application/mspowerpoint',
		'pre' => 'application/x-freelance',
		'prt' => 'application/pro_eng',
		'ps' => 'application/postscript',
		'psd' => [
			'application/octet-stream',
			'image/vnd.adobe.photoshop',
		],
		'pvu' => 'paleovu/x-pv',
		'pwz' => 'application/vnd.ms-powerpoint',
		'py' => 'text/x-script.phyton',
		'pyc' => 'applicaiton/x-bytecode.python',
		'qcp' => 'audio/vnd.qcelp',
		'qd3' => 'x-world/x-3dmf',
		'qd3d' => 'x-world/x-3dmf',
		'qif' => 'image/x-quicktime',
		'qt' => 'video/quicktime',
		'qtc' => 'video/x-qtc',
		'qti' => 'image/x-quicktime',
		'qtif' => 'image/x-quicktime',
		'ra' => [
			'audio/x-pn-realaudio',
			'audio/x-pn-realaudio-plugin',
			'audio/x-realaudio',
		],
		'ram' => 'audio/x-pn-realaudio',
		'rar' => 'application/x-rar-compressed',
		'ras' => [
			'application/x-cmu-raster',
			'image/cmu-raster',
			'image/x-cmu-raster',
		],
		'rast' => 'image/cmu-raster',
		'rexx' => 'text/x-script.rexx',
		'rf' => 'image/vnd.rn-realflash',
		'rgb' => 'image/x-rgb',
		'rm' => [
			'application/vnd.rn-realmedia',
			'audio/x-pn-realaudio',
		],
		'rmi' => 'audio/mid',
		'rmm' => 'audio/x-pn-realaudio',
		'rmp' => [
			'audio/x-pn-realaudio',
			'audio/x-pn-realaudio-plugin',
		],
		'rng' => [
			'application/ringing-tones',
			'application/vnd.nokia.ringing-tone',
		],
		'rnx' => 'application/vnd.rn-realplayer',
		'roff' => 'application/x-troff',
		'rp' => 'image/vnd.rn-realpix',
		'rpm' => 'audio/x-pn-realaudio-plugin',
		'rt' => [
			'text/richtext',
			'text/vnd.rn-realtext',
		],
		'rtf' => [
			'application/rtf',
			'application/x-rtf',
			'text/richtext',
		],
		'rtx' => [
			'application/rtf',
			'text/richtext',
		],
		'rv' => 'video/vnd.rn-realvideo',
		's' => 'text/x-asm',
		's3m' => 'audio/s3m',
		'saveme' => 'application/octet-stream',
		'sbk' => 'application/x-tbook',
		'scm' => [
			'application/x-lotusscreencam',
			'text/x-script.guile',
			'text/x-script.scheme',
			'video/x-scm',
		],
		'sdml' => 'text/plain',
		'sdp' => [
			'application/sdp',
			'application/x-sdp',
		],
		'sdr' => 'application/sounder',
		'sea' => [
			'application/sea',
			'application/x-sea',
		],
		'set' => 'application/set',
		'sgm' => [
			'text/sgml',
			'text/x-sgml',
		],
		'sgml' => [
			'text/sgml',
			'text/x-sgml',
		],
		'sh' => [
			'application/x-bsh',
			'application/x-sh',
			'application/x-shar',
			'text/x-script.sh',
		],
		'shar' => [
			'application/x-bsh',
			'application/x-shar',
		],
		'shtml' => 'text/html',
		'sid' => 'audio/x-psid',
		'sit' => [
			'application/x-sit',
			'application/x-stuffit',
		],
		'skd' => 'application/x-koan',
		'skm' => 'application/x-koan',
		'skp' => 'application/x-koan',
		'skt' => 'application/x-koan',
		'sl' => 'application/x-seelogo',
		'smi' => 'application/smil',
		'smil' => 'application/smil',
		'snd' => [
			'audio/basic',
			'audio/x-adpcm',
		],
		'sol' => 'application/solids',
		'spc' => [
			'application/x-pkcs7-certificates',
			'text/x-speech',
		],
		'spl' => 'application/futuresplash',
		'spr' => 'application/x-sprite',
		'sprite' => 'application/x-sprite',
		'src' => 'application/x-wais-source',
		'ssi' => 'text/x-server-parsed-html',
		'ssm' => 'application/streamingmedia',
		'sst' => 'application/vnd.ms-pki.certstore',
		'step' => 'application/step',
		'stl' => [
			'application/sla',
			'application/vnd.ms-pki.stl',
			'application/x-navistyle',
		],
		'stp' => 'application/step',
		'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc',
		'svf' => [
			'image/vnd.dwg',
			'image/x-dwg',
		],
		'svr' => [
			'application/x-world',
			'x-world/x-svr',
		],
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',
		'swf' => [
			'application/x-shockwave-flash',
			'application/x-shockwave-flash2-preview',
			'application/futuresplash',
			'image/vnd.rn-realflash',
		],
		't' => 'application/x-troff',
		'talk' => 'text/x-speech',
		'tar' => 'application/x-tar',
		'tbk' => [
			'application/toolbook',
			'application/x-tbook',
		],
		'tcl' => [
			'application/x-tcl',
			'text/x-script.tcl',
		],
		'tcsh' => 'text/x-script.tcsh',
		'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo',
		'texinfo' => 'application/x-texinfo',
		'text' => [
			'application/plain',
			'text/plain',
		],
		'tgz' => [
			'application/gnutar',
			'application/x-compressed',
		],
		'tif' => [
			'image/tiff',
			'image/x-tiff',
		],
		'tiff' => [
			'image/tiff',
			'image/x-tiff',
		],
		'tr' => 'application/x-troff',
		'tsi' => 'audio/tsp-audio',
		'tsp' => [
			'application/dsptype',
			'audio/tsplayer',
		],
		'tsv' => 'text/tab-separated-values',
		'turbot' => 'image/florian',
		'txt' => 'text/plain',
		'uil' => 'text/x-uil',
		'uni' => 'text/uri-list',
		'unis' => 'text/uri-list',
		'unv' => 'application/i-deas',
		'uri' => 'text/uri-list',
		'uris' => 'text/uri-list',
		'ustar' => [
			'application/x-ustar',
			'multipart/x-ustar',
		],
		'uu' => [
			'application/octet-stream',
			'text/x-uuencode',
		],
		'uue' => 'text/x-uuencode',
		'vcd' => 'application/x-cdlink',
		'vcs' => 'text/x-vcalendar',
		'vda' => 'application/vda',
		'vdo' => 'video/vdo',
		'vew' => 'application/groupwise',
		'viv' => [
			'video/vivo',
			'video/vnd.vivo',
		],
		'vivo' => [
			'video/vivo',
			'video/vnd.vivo',
		],
		'vmd' => 'application/vocaltec-media-desc',
		'vmf' => 'application/vocaltec-media-file',
		'voc' => [
			'audio/voc',
			'audio/x-voc',
		],
		'vos' => 'video/vosaic',
		'vox' => 'audio/voxware',
		'vqe' => 'audio/x-twinvq-plugin',
		'vqf' => 'audio/x-twinvq',
		'vql' => 'audio/x-twinvq-plugin',
		'vrml' => [
			'application/x-vrml',
			'model/vrml',
			'x-world/x-vrml',
		],
		'vrt' => 'x-world/x-vrt',
		'vsd' => 'application/x-visio',
		'vst' => 'application/x-visio',
		'vsw' => 'application/x-visio',
		'w60' => 'application/wordperfect6.0',
		'w61' => 'application/wordperfect6.1',
		'w6w' => 'application/msword',
		'wav' => [
			'audio/wav',
			'audio/x-wav',
		],
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
		'wp5' => [
			'application/wordperfect',
			'application/wordperfect6.0',
		],
		'wp6' => 'application/wordperfect',
		'wpd' => [
			'application/wordperfect',
			'application/x-wpwin',
		],
		'wq1' => 'application/x-lotus',
		'wri' => [
			'application/mswrite',
			'application/x-wri',
		],
		'wrl' => [
			'application/x-world',
			'model/vrml',
			'x-world/x-vrml',
		],
		'wrz' => [
			'model/vrml',
			'x-world/x-vrml',
		],
		'wsc' => 'text/scriplet',
		'wsrc' => 'application/x-wais-source',
		'wtk' => 'application/x-wintalk',
		'xbm' => [
			'image/x-xbitmap',
			'image/x-xbm',
			'image/xbm',
		],
		'xdr' => 'video/x-amt-demorun',
		'xgz' => 'xgl/drawing',
		'xif' => 'image/vnd.xiff',
		'xl' => 'application/excel',
		'xla' => [
			'application/excel',
			'application/x-excel',
			'application/x-msexcel',
		],
		'xlb' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
		],
		'xlc' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
		],
		'xld' => [
			'application/excel',
			'application/x-excel',
		],
		'xlk' => [
			'application/excel',
			'application/x-excel',
		],
		'xll' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
		],
		'xlm' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
		],
		'xls' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
			'application/x-msexcel',
		],
		'xlt' => [
			'application/excel',
			'application/x-excel',
		],
		'xlv' => [
			'application/excel',
			'application/x-excel',
		],
		'xlw' => [
			'application/excel',
			'application/vnd.ms-excel',
			'application/x-excel',
			'application/x-msexcel',
		],
		'xm' => 'audio/xm',
		'xml' => [
			'application/xml',
			'text/xml',
		],
		'xmz' => 'xgl/movie',
		'xpix' => 'application/x-vnd.ls-xpix',
		'xpm' => [
			'image/x-xpixmap',
			'image/xpm',
		],
		'x-png' => 'image/png',
		'xsr' => 'video/x-amt-showrun',
		'xwd' => [
			'image/x-xwd',
			'image/x-xwindowdump',
		],
		'xyz' => 'chemical/x-pdb',
		'z' => [
			'application/x-compress',
			'application/x-compressed',
		],
		'zip' => [
			'application/x-compressed',
			'application/x-zip-compressed',
			'application/zip',
			'multipart/x-zip',
		],
		'zoo' => 'application/octet-stream',
		'zsh' => 'text/x-script.zsh',
		'asc' => 'text/plain',
		'atom' => 'application/atom+xml',
		'aif' => 'audio/x-aiff',
		'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'flv' => 'video/x-flv',
		'cab' => 'application/vnd.ms-cab-compressed',
		'ai' => 'application/postscript',
	];

	/**
	 * @var array<string, array<string>|string>
	 */
	protected ?array $_mimeTypesTmp = null;

	public function __construct() {
		if (class_exists(MimeType::class)) {
			$mimeType = new MimeType();
			$coreMimeTypes = $this->invokeProperty($mimeType, 'mimeTypes');
		} else {
			$response = new Response();
			$coreMimeTypes = $this->invokeProperty($response, '_mimeTypes');
		}

		$this->_mimeTypes = $coreMimeTypes;
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
	 * @return array<string, array<string>|string>
	 */
	public function all(bool $coreHasPrecedence = false) {
		if ($coreHasPrecedence) {
			return $this->_mimeTypes += $this->_mimeTypesExt;
		}

		return $this->_mimeTypesExt += $this->_mimeTypes;
	}

	/**
	 * Returns the primary mime type definition for an alias/extension.
	 *
	 * e.g `getMimeType('pdf'); // returns 'application/pdf'`
	 *
	 * @param string $alias the content type alias to map
	 * @param bool $primaryOnly
	 * @param bool $coreHasPrecedence
	 * @return array<string>|string|null Mapped mime type or null if $alias is not mapped
	 */
	public function getMimeType(
		string $alias,
		bool $primaryOnly = true,
		bool $coreHasPrecedence = false,
	) {
		if (!$this->_mimeTypesTmp) {
			$this->_mimeTypesTmp = $this->all($coreHasPrecedence);
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
	 * @param array<string>|string $mimeTypes Either a string content type to map, or an array of types.
	 * @return string|null Alias for the types provided.
	 */
	public function mapType(array|string $mimeTypes): ?string {
		$all = $this->all();
		foreach ($all as $ext => $extMimeTypes) {
			foreach ((array)$extMimeTypes as $extMimeType) {
				if ($mimeTypes === $extMimeType) {
					return $ext;
				}
				if (is_array($mimeTypes)) {
					if (in_array($extMimeType, $mimeTypes, true)) {
						return $ext;
					}
				}
			}
		}

		return null;
	}

}
