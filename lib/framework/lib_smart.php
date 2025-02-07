<?php
// [LIB - Smart.Framework / Base]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Base
// DEPENDS-PHP: 7.4 or later
// DEPENDS-EXT: PHP XML, PHP JSON
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//================================================================
if((!function_exists('json_encode')) OR (!function_exists('json_decode')) OR (!defined('JSON_INVALID_UTF8_SUBSTITUTE'))) {
	@http_response_code(500);
	die('ERROR: The PHP JSON Extension with JSON_INVALID_UTF8_SUBSTITUTE support is required for the Smart.Framework / Base');
} //end if
if(!function_exists('hex2bin')) {
	@http_response_code(500);
	die('ERROR: The PHP hex2bin Function is required for Smart.Framework / Base');
} //end if
if(!function_exists('bin2hex')) {
	@http_response_code(500);
	die('ERROR: The PHP bin2hex Function is required for Smart.Framework / Base');
} //end if
//================================================================
if((!defined('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH')) OR (!is_string(SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH))) {
	@http_response_code(500);
	die('The SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH was not set or is invalid (must be string) ...');
} //end if
//================================================================
// TODO: remove this after dropping off PHP compatibility lower than PHP 8.1
define('SMART_FRAMEWORK_PHP_HAVE_ARRAY_LIST', (bool)((version_compare((string)phpversion(), '8.1.0') >= 0) && function_exists('array_is_list')));
//================================================================


/***** PHP and Dynamic Variable Basics :: Comparing different type of variables can be tricky in PHP

//##### NOTICE !!! The PHP comparison between string and number is tricky with equal sign #####
$var = 0;
//-- incorrect
if($var == 'some-string') {
	echo 'This comparison will give unexpected results !';
} //end if
//-- correct use
if((string)$var == 'some-string') {
	echo 'This will avoid comparison problems';
} //end if
//#####

// never use break; return ...; // return will never get executed !! :: # pcregrep -rM 'break;\s*return' .

*****/


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: Smart (Base Functions) - provides the base methods for an easy and secure development with Smart.Framework and PHP.
 *
 * <code>
 * // Usage example:
 * Smart::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 * @hints       It is recommended to use the methods in this class instead of PHP native methods whenever is possible because this class will offer Long Term Support and the methods will be supported even if the behind PHP methods can change over time, so the code would be easier to maintain.
 *
 * @access      PUBLIC
 * @depends     extensions: PHP JSON ; classes: SmartUnicode, SmartFrameworkSecurity, SmartEnvironment ; constants: SMART_FRAMEWORK_CHARSET ; optional-constants: SMART_FRAMEWORK_SECURITY_KEY, SMART_SOFTWARE_NAMESPACE, SMART_FRAMEWORK_NETSERVER_ID, SMART_FRAMEWORK_INFO_LOG
 * @version     v.20250207
 * @package     @Core
 *
 */
final class Smart {

	// ::

	public const REGEX_ASCII_NOSPACE_CHARACTERS 	= '/^[[:graph:]]+$/'; 			// match all ASCII safe printable characters except spaces ; [[:graph:]] is equivalent to [[:alnum:][:punct:]]
	public const REGEX_ASCII_ANDSPACE_CHARACTERS 	= '/^[[:graph:] \t\r\n]+$/'; 	// match all ASCII safe printable characters ; allow extra safe spaces only: space, tab, line feed, carriage return
	public const REGEX_ASCII_PRINTABLE_CHARACTERS 	= '/^[[:print:]]+$/'; 			// match all ASCII printable characters [[:print:]] is equivalent to [[:graph:][:whitespace:]] ; [:whitespace:] match a whitespace character such as space, tab, formfeed, and carriage return

	public const REGEX_SAFE_PATH_NAME 	= '/^[_a-zA-Z0-9\-\.@\#\/]+$/';
	public const REGEX_SAFE_FILE_NAME 	= '/^[_a-zA-Z0-9\-\.@\#]+$/';

	public const REGEX_SAFE_VAR_NAME 	= '/^[_a-zA-Z0-9]+$/';

	public const REGEX_SAFE_VALID_NAME 	= '/^[_a-z0-9\-\.@]+$/';
	public const REGEX_SAFE_USERNAME 	= '/^[a-z0-9\.]+$/';

	public const REGEX_SAFE_HEX_STR 	= '/^[0-9a-f]+$/'; // if need case insensitive check just append the `i` flag
	public const REGEX_SAFE_B64_STR 	= '/^[a-zA-Z0-9\+\/\=]+$/';
	public const REGEX_SAFE_B64S_STR 	= '/^[a-zA-Z0-9\-_\.]+$/';
	public const REGEX_SAFE_B64U_STR 	= '/^[a-zA-Z0-9\-_]+$/';

	public const DECIMAL_NUM_PRECISION 	= '9999999999999.9'; // DECIMAL I[13].D[1] ; if I + D > 14 looses some decimal precision ; by example: 99999999999999.9900 becomes 99999999999999.98 with 2 decimals and 100000000000000.0 with one decimal on number format ! ; no higher decimal numbers than this are safe using a precision like 14, the max in PHP

	public const CHARSET_BASE_16 = '0123456789abcdef';
	public const CHARSET_BASE_32 = '0123456789ABCDEFGHIJKLMNOPQRSTUV'; // cs09AV
	public const CHARSET_BASE_36 = '0123456789abcdefghijklmnopqrstuvwxyz';
	public const CHARSET_BASE_58 = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz'; // compatible with smartgo
	public const CHARSET_BASE_62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	public const CHDIFF_BASE_64s = [ '+' => '-', '/' => '_', '=' => '.' ]; // with padding
	public const CHDIFF_BASE_64u = [ '+' => '-', '/' => '_', '=' => '' ]; // with no padding
	public const CHARSET_BASE_85 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#'; // https://rfc.zeromq.org/spec:32/Z85/
	public const CHARSET_BASE_92 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#|;,_~`"'; // uxm, compatible with smartgo

	public const UNDEF_VAR_NAME = 'Undef____V_a_r';

	public const SIZE_BYTES_16M = 16777216; // Reference Unit

	//--

	private const STRIP_HTML_ENTITIES = [ // keep unique also in case insensitive ! the most usual HTML Entities list, used for normalize strip tags only ; no need to export as public
		'&NewLine;' 	=> "\n",
		'&Tab;' 		=> "\t",
		'&nbsp;' 		=> ' ',
		'&quot;' 		=> '"',
		'&apos;' 		=> "'",
		'&#039;' 		=> "'", // alternate for &apos; (HTML4)
		'&#39;' 		=> "'", // short version of &#039;
		'&lt;' 			=> '<',
		'&gt;' 			=> '>',
		'&sol;' 		=> '/',
		'&#047;' 		=> '/', // alternate for &sol; better supported by schema.org
		'&#47;' 		=> '/', // short version of &#047;
		'&bsol;' 		=> '\\',
		'&#092;' 		=> '\\', // alternate for &bsol; better supported by schema.org
		'&#92;' 		=> '\\', // short version of &#092;
		'&middot;' 		=> '.',
		'&centerdot;' 	=> '.',
		'&bull;' 		=> '.',
		'&sdot;' 		=> '.',
		'&copy;' 		=> '(c)',
		'&#169;' 		=> '(c)', // alternate for &copy;
		'&reg;' 		=> '(R)',
		'&#174;' 		=> '(R)', // alternate for &reg;
		'&circledR;' 	=> '(R)', // alternate for &reg;
		'&trade;' 		=> '(TM)',
		'&excl;' 		=> '!',
		'&quest;'		=> '?',
		'&num;' 		=> '#',
		'&commat;' 		=> '@',
		'&#064;' 		=> '@', // alternate for &commat;
		'&#64;' 		=> '@', // short version of &#064;
		'&curren;' 		=> '¤',
		'&euro;' 		=> '€',
		'&dollar;' 		=> '$',
		'&cent;' 		=> '¢',
		'&pound;' 		=> '£',
		'&yen;' 		=> '¥',
		'&lsaquo;' 		=> '‹',
		'&rsaquo;' 		=> '›',
		'&laquo;' 		=> '«',
		'&raquo;' 		=> '»',
		'&lsquo;' 		=> '‘',
		'&rsquo;' 		=> '’',
		'&ldquo;' 		=> '“',
		'&rdquo;' 		=> '”',
		'&bdquo;' 		=> '„',
		'&acute;' 		=> '`',
		'&prime;' 		=> '`',
		'&dash;' 		=> '-',
		'&ndash;' 		=> '-',
		'&mdash;' 		=> '-',
		'&horbar;' 		=> '--',
		'&minus;' 		=> '-',
		'&macr;' 		=> '-',
		'&strns;' 		=> '-',
		'&OverBar;' 	=> '-',
		'&uml;' 		=> '..',
		'&die;' 		=> '..',
		'&Dot;' 		=> '..',
		'&DoubleDot;' 	=> '..',
		'&lowbar;' 		=> '_',
		'&Hat;' 		=> '^',
		'&comma;' 		=> ',',
		'&nldr;' 		=> '..',
		'&hellip;' 		=> '...',
		'&tilde;' 		=> '~',
		'&sim;' 		=> '~',
		'&circ;' 		=> '^',
		'&spades;' 		=> '♠',
		'&clubs;' 		=> '♣',
		'&hearts;' 		=> '♥',
		'&diams;' 		=> '♦',
		'&sung;' 		=> '♪',
		'&flat;' 		=> '♭',
		'&natur;' 		=> '♮',
		'&natural;' 	=> '♮',
		'&sharp;' 		=> '♯',
		'&check;' 		=> '✓',
		'&cross;' 		=> '✗',
		'&sext;' 		=> '✶',
		'&infin;' 		=> '∞',
		'&percnt;' 		=> '%',
		'&lpar;' 		=> '(',
		'&rpar;' 		=> ')',
		'&equals;' 		=> '=',
		'&lowast;' 		=> '*',
		'&ast;' 		=> '*',
		'&midast;' 		=> '*',
		'&plus;' 		=> '+',
		'&divide;' 		=> '÷',
		'&times;' 		=> 'x',
		'&frac12;' 		=> '1/2',
		'&frac14;' 		=> '1/4',
		'&frac34;' 		=> '3/4',
		'&brvbar;' 		=> '¦',
		'&brkbar;' 		=> '¦',
		'&sect;' 		=> '§',
		'&para;' 		=> '¶',
		'&micro;' 		=> 'µ',
		'&iexcl;' 		=> '¡',
		'&iquest;' 		=> '¿',
		'&hibar;' 		=> '¯',
		'&deg;' 		=> '°',
		'&ordm;' 		=> 'º',
		'&plusmn;' 		=> '±',
		'&PlusMinus;' 	=> '±',
		'&sup1;' 		=> '¹',
		'&sup2;' 		=> '²',
		'&sup3;' 		=> '³',
		'&ordf;' 		=> 'ª',
		'&amp;' 		=> '&', // must be at the end because it is contained as prexif in any entity if double encoded ...
	];

	//--
	private static $Cfgs = []; // registry of cached config data

	private static $SemaphoreAreLogHandlersDisabled = false; // by default they are supposed to be not
	//--


	//================================================================
	/**
	 * Test if a variable value is Scalar or Null
	 *
	 * @param 	MIXED 		$val 			:: The value to be tested
	 *
	 * @return 	BOOL						:: FALSE if array, object or resource ; TRUE for the rest
	 */
	public static function is_nscalar($val) : bool {
		//--
		if(is_scalar($val) OR is_null($val)) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if a variable value is Array or Scalar or Null
	 *
	 * @param 	MIXED 		$val 			:: The value to be tested
	 *
	 * @return 	BOOL						:: FALSE if object or resource ; TRUE for the rest
	 */
	public static function is_arr_or_nscalar($val) : bool {
		//--
		if(self::is_nscalar($val) OR is_array($val)) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the value for a Config parameter from the app $configs array.
	 *
	 * @param 	STRING 		$param 			:: The selected configuration parameter. Example: 'app.info-url' will get value (STRING) from $configs['app']['info-url'] ; 'app' will get the value (ARRAY) from $configs['app']
	 * @param 	ENUM 		$type 			:: The type to pre-format the value: 'array', 'string', 'boolean', 'integer', 'numeric' OR '' to leave the value as is (raw)
	 *
	 * @return 	MIXED						:: The value for the selected parameter. If the Config parameter does not exists, will return an empty string.
	 */
	public static function get_from_config(?string $param, ?string $type='') { // mixed
		//--
		global $configs;
		//--
		$param = (string) trim((string)$param);
		if((string)$param == '') {
			self::log_warning(__METHOD__.' # Empty Parameter ; Type ['.$type.']');
			return null;
		} //end if
		//--
		if(array_key_exists((string)$param, self::$Cfgs)) {
			return self::$Cfgs[(string)$param]; // mixed
		} //end if
		//--
		$value = self::array_get_by_key_path($configs, strtolower((string)$param), '.'); // mixed
		//--
		if(is_object($value) OR is_resource($value)) {
			$value = ''; // fix: dissalow objects in config ; allowed types: NULL, BOOL, NUMERIC, STRING, ARRAY
		} //end if
		//--
		self::$Cfgs[(string)$param] = $value; // mixed
		//--
		switch((string)$type) {
			case 'array':
				if(!is_array($value)) {
					$value = array();
				} //end if
				break;
			case 'string':
				if(!self::is_nscalar($value)) {
					$value = '';
				} //end if
				$value = (string) $value;
				break;
			case 'boolean':
				$value = (string) strtolower((string)$value); // {{{SYNC-SMART-BOOL-GET-EXT}}}
				if((!$value) OR ((string)$value == 'false')) {
					$value = false;
				} else {
					$value = true;
				} //end if
				break;
			case 'integer':
				if(!self::is_nscalar($value)) {
					$value = 0;
				} //end if
				$value = (int) $value;
				break;
			case 'numeric':
				if(!self::is_nscalar($value)) {
					$value = 0;
				} //end if
				$value = (float) $value;
				break;
			case '':
				// return as is (raw, unformatted) ...
				break;
			default:
				self::log_warning(__METHOD__.' # Invalid Type to get from Config for: Parameter ['.$param.'] ; Type ['.$type.']');
		} //end switch
		//--
		return $value; // mixed
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Fix for Directory Separator if on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path name to fix
	 *
	 * @return 	STRING						:: The fixed path name
	 */
	public static function fix_path_separator(string $y_path, bool $y_force=false) : string {
		//--
		if((string)$y_path != '') {
			if(((string)DIRECTORY_SEPARATOR == '\\') OR ($y_force === true)) { // if on Windows, Fix Path Separator !!!
				if(strpos((string)$y_path, '\\') !== false) {
					$y_path = (string) strtr((string)$y_path, [ '\\' => '/' ]); // convert \ to / on paths
				} //end if
			} //end if
		} //end if
		//--
		return (string) $y_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED realpath() as absolute path, also with fix on Windows
	 * This should be mostly used for existing paths. If path does not exists will return the same path as passed argument.
	 * It does not support passing empty paths to avoid security issues like rtrim(Smart::real_path(''),'/').'/' which will point to the root folder of the filesystem.
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract realpath()
	 *
	 * @return 	STRING						:: The real path
	 */
	public static function real_path(?string $y_path) : string {
		//--
		$y_path = (string) trim((string)$y_path);
		if((string)$y_path == '') {
			self::raise_error(__METHOD__.' # Empty Path passed as argument');
			return 'tmp/invalid-real-path/';
		} //end if
		//--
		$the_path = (string) trim((string)realpath((string)$y_path));
		if((string)$the_path == '') { // realpath will return false/empty-string if the path does not exists
			$the_path = (string) $y_path;
		} //end if
		//--
		return (string) self::fix_path_separator((string)$the_path); // FIX: on Windows, is possible to return a backslash \ instead of slash /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED dirname(), safe on Linux and Unix and with safety fix on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract dirname()
	 *
	 * @return 	STRING						:: The dirname or . or empty string
	 */
	public static function dir_name(?string $y_path) : string {
		//--
		$dir_name = (string) dirname((string)$y_path);
		//--
		return (string) self::fix_path_separator((string)$dir_name); // FIX: on Windows, is possible to return a backslash \ instead of slash /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED basename(), in a safe way
	 * The directory separator character is the forward slash (/), except Windows where both slash (/) and backslash (\) are considered
	 *
	 * @param 	STRING 	$y_path 			:: The path name from to extract basename()
	 * @param 	STRING 	$y_suffix 			:: If the name component ends in suffix this will also be cut off
	 *
	 * @return 	STRING						:: The basename
	 */
	public static function base_name(?string $y_path, ?string $y_suffix='') : string {
		//--
		if((string)$y_suffix != '') {
			$base_name = (string) basename((string)$y_path, (string)$y_suffix);
		} else {
			$base_name = (string) basename((string)$y_path);
		} //end if else
		//--
		return (string) $base_name;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the FIXED pathinfo(), also with fix on Windows
	 *
	 * @param 	STRING 	$y_path 			:: The path to process as pathinfo()
	 *
	 * @return 	ARRAY						:: The pathinfo array
	 */
	public static function path_info(?string $y_path) : array {
		//--
		$path_info = pathinfo((string)$y_path, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME); // mixed return ... do not cast to array !!
		//-- PHP8 fix
		$path_info = (array) self::array_init_keys(
			$path_info,
			[
				'dirname',
				'basename',
				'filename',
				'extension'
			]
		);
		//--
		$path_info['dirname'] = (string) self::fix_path_separator((string)$path_info['dirname']);
		//--
		return (array) $path_info;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Str Replace, Only First Occurence
	 *
	 * @param 	STRING 		$search 		:: The value being searched for, otherwise known as the needle
	 * @param 	STRING 		$replace 		:: The replacement value that replaces found search value
	 * @param 	STRING 		$str			:: The string being searched and replaced on, otherwise known as the haystack
	 *
	 * @return 	STRING						:: This function returns a string with the replaced value only on first occurence if search value is found
	 */
	public static function str_replace_first(?string $search, ?string $replace, ?string $str) : string {
		//--
		if((string)$str != '') {
			if((string)$search != '') {
				$pos = strpos((string)$str, (string)$search); // MIXED: FALSE or INT+
				if($pos !== false) {
					$str = (string) substr_replace((string)$str, (string)$replace, (int)$pos, (int)strlen((string)$search));
				} //end if
			} //end if
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Str Replace, Only Last Occurence
	 *
	 * @param 	STRING 		$search 		:: The value being searched for, otherwise known as the needle
	 * @param 	STRING 		$replace 		:: The replacement value that replaces found search value
	 * @param 	STRING 		$str			:: The string being searched and replaced on, otherwise known as the haystack
	 *
	 * @return 	STRING						:: This function returns a string with the replaced value only on last occurence if search value is found
	 */
	public static function str_replace_last(?string $search, ?string $replace, ?string $str) : string {
		//--
		if((string)$str != '') {
			if((string)$search != '') {
				$pos = strrpos((string)$str, (string)$search); // MIXED: FALSE or INT+
				if($pos !== false) {
					$str = (string) substr_replace((string)$str, (string)$replace, (int)$pos, (int)strlen((string)$search));
				} //end if
			} //end if
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Parse URL 					:: a better replacement for parse_url()
	 *
	 * @param STRING 	$y_url			:: The URL to be parsed
	 * @param BOOL 		$y_skiport 		:: If set to TRUE will skip the port if standard (empty: 80 or 443) ; Default is FALSE
	 *
	 * @return ARRAY 					:: The separed URL (associative array) as: protocol, server, port, path, scriptname
	 */
	public static function url_parse(?string $y_url, bool $y_skiport=false) : array {
		//--
		$y_url = (string) $y_url;
		//--
		$parts = array();
		$parts = parse_url((string)$y_url); // do not cast
		if(!is_array($parts)) {
			$parts = [];
		} //end if
		//--
		$scheme = '';
		if(array_key_exists('scheme', $parts)) {
			$scheme = (string) trim((string)$parts['scheme']);
		} //end if
		//--
		$protocol = (string) $scheme;
		if((string)$protocol != '') {
			$protocol .= ':';
		} //end if
		$protocol .= '//';
		//--
		$server = '';
		if(array_key_exists('host', $parts)) {
			$server = (string) trim((string)$parts['host']);
		} //end if
		//--
		$port = '';
		if(array_key_exists('port', $parts)) {
			$port = (string) trim((string)$parts['port']);
		} //end if
		if($y_skiport !== true) {
			if((string)$port == '') {
				if((string)$scheme == 'https') {
					$port = '443';
				} else {
					$port = '80';
				} //end if else
			} //end if
		} //end if
		//--
		$path = '';
		if(array_key_exists('path', $parts)) {
			$path = (string) trim((string)$parts['path']);
		} //end if
		//--
		$query = '';
		if(array_key_exists('query', $parts)) {
			$query = (string) trim((string)$parts['query']);
		} //end if
		//--
		$fragment = '';
		if(array_key_exists('fragment', $parts)) {
			$fragment = (string) trim((string)$parts['fragment']);
		} //end if
		//--
		$suffix = (string) $path;
		if((string)$query != '') {
			$suffix .= '?'.$query;
		} //end if
		if((string)$fragment != '') {
			$suffix .= '#'.$fragment;
		} //end if
		if((string)$suffix == '') {
			$suffix = '/'; // FIX: this is required as default http path for HTTP Cli requests !!
		} //end if
		//--
		return [ // must be compatible with: PHP's parse_url() but may have extra entries
			'protocol' 	=> (string) $protocol,
			'scheme' 	=> (string) $scheme,
			'host' 		=> (string) $server,
			'port' 		=> (string) $port,
			'path' 		=> (string) $path,
			'query' 	=> (string) $query,
			'fragment' 	=> (string) $fragment,
			'suffix' 	=> (string) $suffix,
		];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Parse URL Query 			:: a better replacement for parse_str()
	 *
	 * @param STRING 	$y_query_url	:: The URL Query to be parsed ; Ex: first=value&arr[]=foo+bar&arr[]=baz
	 *
	 * @return ARRAY 					:: The array with all parsed values
	 */
	public static function url_parse_query(?string $y_query_url) : array {
		//--
		$y_query_url = (string) trim((string)$y_query_url);
		$y_query_url = (string) ltrim((string)$y_query_url, '?');
		$y_query_url = (string) ltrim((string)$y_query_url, '&');
		$y_query_url = (string) ltrim((string)$y_query_url);
		//--
		if((string)$y_query_url == '') {
			return [];
		} //end if
		//--
		$arr = [];
		parse_str((string)$y_query_url, $arr); // this method does not output
		if(!is_array($arr)) {
			$arr = [];
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Build an URL Query (Build a standard RFC3986 URL from an array of parameters) as: a=b&param1=value1&param2=value2&c[0]=a&c[1]=x&d[a]=15&d[b]=z
	 *
	 * @param 	ARRAY		$y_params 						:: Associative array as [param1 => value1, Param2 => Value2]
	 * @param 	BOOLEAN 	$y_allow_late_binding_params	:: Allow late binding params ex: a={{{param}}}&b=true
	 *
	 * @return 	STRING										:: The prepared URL in the standard RFC3986 format (all values are escaped using rawurlencode() to be Unicode full compliant
	 */
	public static function url_build_query(?array $y_params, bool $y_allow_late_binding_params) : string {
		//--
		if(self::array_size($y_params) <= 0) {
			return '';
		} //end if
		//--
		$out = [];
		if(is_array($y_params)) {
			foreach($y_params as $key => $val) {
				if(((string)trim((string)$key) != '') AND (SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
					$out[] = (string) self::url_encode_params((string)$key, $val, (bool)$y_allow_late_binding_params);
				} //end if
			} //end foreach
		} //end if
		//--
		return (string) implode('&', (array)$out);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// [PRIVATE]
	private static function url_encode_value(?string $value, bool $y_allow_late_binding_params) : string {
		//--
		if(
			($y_allow_late_binding_params === true) AND
			((string)substr((string)$value, 0, 3) == '{{{') AND
			((string)substr((string)$value, -3, 3) == '}}}') AND
			((string)$value != '{{{}}}')
		) { // this is {{{param}}} ; protect: `{{{` and `}}}` if starts or ends with them and not {{{}}}
			$value = (string) substr((string)$value, 3);
			$value = (string) substr((string)$value, 0, (int)strlen((string)$value)-3);
			$value = (string) '{{{'.rawurlencode((string)$value).'}}}';
		} else {
			$value = (string) rawurlencode((string)$value);
		} //end if
		//--
		return (string) $value;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// [PRIVATE] on init this function $suffix must be set to ''
	private static function url_encode_params(?string $name, $value, bool $y_allow_late_binding_params, ?string $suffix='') : string {
		//--
		$ret = [];
		//--
		if(self::array_size($value) > 0) { // Non-Empty Array
			$arrtype = self::array_type_test($value); // 0: not an array ; 1: non-associative ; 2:associative
			foreach($value as $kk => $vv) {
				if($arrtype === 1) { // 1: non-associative
					$ek = (string) '[]';
				} else { // 2: associative
					$ek = (string) '['.rawurlencode((string)$kk).']';
				} //end if else
				if(is_array($vv)) {
					$ret[] = (string) self::url_encode_params((string)$name, (array)$vv, (bool)$y_allow_late_binding_params, (string)$suffix.$ek);
				} else {
					$ret[] = (string) rawurlencode((string)$name).$suffix.$ek.'='.self::url_encode_value((string)$vv, (bool)$y_allow_late_binding_params);
				} //end if else
			} //end foreach
		} elseif(self::is_nscalar($value) OR is_array($value)) { // nScalar or Empty Array
			if(is_array($value)) {
				$value = null; // keep empty arrays, normally they are discarded by the http_build_query()
			} elseif($value === true) {
				$value = 1;
			} elseif($value === false) {
				$value = 0;
			} //end if
			$ret[] = (string) rawurlencode((string)$name).$suffix.'='.self::url_encode_value((string)$value, (bool)$y_allow_late_binding_params);
		} //end if else
		//--
		return (string) implode('&', (array)$ret);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Remove URL Params (Build a standard RFC3986 URL from script and parameters) as: script.xyz?a=b&param1=value1&param2=value2&param3={{{late-binding}}}
	 * It may allow or not late binding params in url (when re-parsing url query) such as 'param3={{{late-binding}}}'
	 *
	 * @param 	STRING 		$y_url							:: The base URL like: script.php or script.php?a=b or empty
	 * @param 	ARRAY		$y_params 						:: Non-Associative array as [param1, Param2]
	 * @param 	BOOLEAN 	$y_allow_late_binding_params	:: Allow late binding params in URL query (when re-parsing) ex: a={{{param}}}&b=true
	 *
	 * @return 	STRING										:: The prepared URL in the standard RFC3986 format (all values are escaped using rawurlencode() to be Unicode full compliant
	 */
	public static function url_remove_params(?string $y_url, ?array $y_params, bool $y_allow_late_binding_params=true) : string {
		//--
		$y_url = (string) trim((string)$y_url);
		if((string)$y_url == '') {
			return ''; // url is empty
		} //end if
		//--
		if((int)self::array_size($y_params) <= 0) {
			self::log_notice(__METHOD__.' # Input parameters array is empty');
			return (string) $y_url; // err
		} //end if
		if((int)self::array_type_test($y_params) != 1) {
			self::log_warning(__METHOD__.' # Input parameters array must be non-associative');
			return (string) $y_url; // err
		} //end if
		//--
		if(strpos((string)$y_url, '?') === false) {
			return (string) $y_url; // no query URL, nothing to remove
		} //end if
		//--
		$arr = (array) explode('?', (string)$y_url, 2);
		$part_url = (string) trim((string)($arr[0] ?? null));
		$part_query = (string) trim((string)($arr[1] ?? null));
		$arr = null;
		//--
		if((string)$part_query == '') {
			return (string) $y_url; // nothing after ?, nothing to remove
		} //end if
		//--
		$part_hash = '';
		if(strpos((string)$part_query, '#') !== false) {
			$arr = (array) explode('#', (string)$part_query, 2);
			$part_query = (string) trim((string)($arr[0] ?? null));
			$part_hash = (string) trim((string)($arr[1] ?? null));
		} //end if
		//--
		if((string)$part_query == '') {
			return (string) $y_url; // nothing after ? before #, nothing to remove
		} //end if
		//--
		$arr = (array) self::url_parse_query((string)$part_query);
		if((int)self::array_size($arr) <= 0) {
			return (string) $y_url; // malformed url, nothing to remove
		} //end if
		//--
		for($i=0; $i<self::array_size($y_params); $i++) {
			$key = '';
			if(self::is_nscalar($y_params[$i])) {
				$key = (string) trim((string)$y_params[$i]);
				if((string)$key != '') {
					if(array_key_exists((string)$key, (array)$arr)) {
						unset($arr[(string)$key]);
					} //end if
				} //end if
			} else {
				self::log_warning(__METHOD__.' # Input parameters array contains a non-scalar value at index: #'.(int)$i);
			} //end if else
		} //end for
		$part_query = (string) trim((string)self::url_build_query((array)$arr, (bool)$y_allow_late_binding_params));
		if((string)$part_query != '') {
			$part_query = '?'.$part_query;
		} //end if
		//--
		if((string)$part_hash != '') {
			$part_hash = (string) '#'.$part_hash;
		} //end if
		//--
		return (string) $part_url.$part_query.$part_hash;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Add URL Params (Build a standard RFC3986 URL from script and parameters) as: script.xyz?a=b&param1=value1&param2=value2&param3={{{late-binding}}}
	 * It may allow or not late binding params such as 'param3' => '{{{late-binding}}}'
	 *
	 * @param 	STRING 		$y_url							:: The base URL like: script.php or script.php?a=b or empty
	 * @param 	ARRAY		$y_params 						:: Associative array as [param1 => value1, Param2 => Value2, param3 => {{{late-binding}}}]
	 * @param 	BOOLEAN 	$y_allow_late_binding_params	:: Allow late binding params ex: a={{{param}}}&b=true
	 *
	 * @return 	STRING										:: The prepared URL in the standard RFC3986 format (all values are escaped using rawurlencode() to be Unicode full compliant
	 */
	public static function url_add_params(?string $y_url, ?array $y_params, bool $y_allow_late_binding_params=true) : string {
		//--
		if(self::array_size($y_params) <= 0) {
			return (string) $y_url;
		} //end if
		//--
		$url = (string) trim((string)$y_url);
		//--
		foreach($y_params as $key => $val) {
			if(((string)trim((string)$key) != '') AND (SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
				$url = (string) self::url_add_suffix((string)$url, (string)self::url_build_query([ (string)$key => $val ], (bool)$y_allow_late_binding_params));
			} //end if
		} //end foreach
		//--
		return (string) $url;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Add URL Suffix (to a standard RFC3986 URL) as: script.php?a=b&C=D&e=%20d
	 *
	 * @param 	STRING 		$y_url				:: The base URL to use as prefix like: script.php or script.php?a=b&c=d or empty
	 * @param 	STRING		$y_suffix 			:: A RFC3986 URL segment like: a=b or E=%20d (without ? or not starting with & as they will be detected if need append ? or &; variable values must be encoded using rawurlencode() RFC3986)
	 *
	 * @return 	STRING							:: The prepared URL in the standard RFC3986 format (all values are escaped using rawurlencode() to be Unicode full compliant
	 */
	public static function url_add_suffix(?string $y_url, ?string $y_suffix) : string {
		//--
		$y_url = (string) trim((string)$y_url);
		$y_suffix = (string) trim((string)$y_suffix);
		//--
		if(((string)$y_suffix == '') OR ((string)$y_suffix == '?') OR ((string)$y_suffix == '&')) {
			if((string)$y_url != '') {
				return (string) $y_url.$y_suffix;
			} //end if
		} //end if
		//--
		if(((string)substr((string)$y_suffix, 0, 1) == '?') OR ((string)substr((string)$y_suffix, 0, 1) == '&')) {
			$y_suffix = (string) substr((string)$y_suffix, 1);
		} //end if
		//--
		if((strpos((string)$y_suffix, '?') !== false) OR (strpos((string)$y_suffix, '&') === 0)) {
			self::log_notice(__METHOD__.' # The URL Suffix should not contain `?` or start with `&` :: URL: `'.$y_url.'` ; Suffix: `'.$y_suffix.'`');
		} //end if
		//--
		if(((string)substr((string)$y_url, -1, 1) == '?') OR ((string)substr((string)$y_url, -1, 1) == '&')) {
			$url = (string) $y_url.$y_suffix;
		} elseif(strpos((string)$y_url, '?') === false) {
			$url = (string) $y_url.'?'.$y_suffix;
		} else {
			$url = (string) $y_url.'&'.$y_suffix;
		} //end if else
		//--
		return (string) $url;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Add URL Anchor (to a standard RFC3986 URL) as: script.php?a=b&C=D&e=%20d
	 *
	 * @param 	STRING 		$y_url				:: The base URL to use as prefix like: script.php or script.php?a=b&c=d or empty
	 * @param 	STRING		$y_anchor 			:: A RFC3986 URL anchor like: myAnchor
	 *
	 * @return 	STRING							:: The prepared URL as script.php?a=b&c=d&e=%20d#myAnchor
	 */
	public static function url_add_anchor(?string $y_url, ?string $y_anchor) : string {
		//--
		$y_url = (string) trim((string)$y_url);
		$y_anchor = (string) trim((string)$y_anchor);
		//--
		if(((string)$y_anchor == '') OR ((string)$y_anchor == '#')) {
			return (string) $y_url.$y_anchor;
		} //end if
		//--
		if((string)substr((string)$y_anchor, 0, 1) == '#') {
			$y_anchor = (string) substr((string)$y_anchor, 1);
		} //end if
		//--
		if(strpos((string)$y_suffix, '#') !== false) {
			self::log_notice(__METHOD__.' # The URL Anchor should not contain `#` :: URL: `'.$y_url.'` ; Suffix: `'.$y_anchor.'`');
		} //end if
		//--
		return (string) $y_url.'#'.self::escape_url((string)$y_anchor);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape URL Variable (using RFC3986 standards to be full Unicode compliant)
	 * This is a shortcut to the rawurlencode() to provide a standard into Smart.Framework
	 *
	 * @param 	STRING 		$y_string			:: The variable value to be escaped
	 *
	 * @return 	STRING							:: The escaped URL variable using the RFC3986 standard format (this variable can be appended to URL, by example: ?variable={escaped-value-returned-by-this-method}
	 */
	public static function escape_url(?string $y_string) : string {
		//--
		return (string) rawurlencode((string)$y_string);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape strings to be injected in HTML code
	 * This is a shortcut to the htmlspecialchars() for HTML mode, to avoid use long options each time and provide a standard into Smart.Framework
	 *
	 * @param 	STRING 		$y_string			:: The string to be escaped
	 *
	 * @return 	STRING							:: The escaped string using htmlspecialchars() HTML standards with Unicode-Safe control
	 */
	public static function escape_html(?string $y_string) : string {
		//-- v.20181203
		// Default is: ENT_HTML401 | ENT_COMPAT
		// keep the ENT_HTML401 instead of ENT_HTML5 to avoid troubles with misc. HTML Parsers (robots, htmldoc, ...)
		// keep the ENT_COMPAT (replace only < > ") and not replace '
		// add ENT_SUBSTITUTE to avoid discard the entire invalid string (with UTF-8 charset) but substitute dissalowed characters with ?
		// enforce 4th param as TRUE as default (double encode)
		//--
		if((string)$y_string == '') {
			return '';
		} //end if
		//--
		return (string) htmlspecialchars((string)$y_string, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true); // use charset from INIT (to prevent XSS attacks) ; the 4th parameter double_encode is set to TRUE as default
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape strings to be injected in XML code
	 * This is a shortcut to the htmlspecialchars() for XML mode, to avoid use long options each time and provide a standard into Smart.Framework
	 *
	 * @param 	STRING 		$y_string			:: The string to be escaped
	 * @param 	BOOL 		$y_extra_escapings 	:: If set to TRUE will replace special characters such as TAB, LF and CR with the corresponding entities
	 *
	 * @return 	STRING							:: The escaped string using htmlspecialchars() XML standards with Unicode-Safe control
	 */
	public static function escape_xml(?string $y_string, bool $y_extra_escapings) : string {
		//-- v.20241228
		if((string)$y_string == '') {
			return '';
		} //end if
		//--
		$y_string = (string) htmlspecialchars((string)$y_string, ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, (string)SMART_FRAMEWORK_CHARSET, true); // use charset from INIT (to prevent XSS attacks) ; the 4th parameter double_encode is set to TRUE as default
		//--
		if($y_extra_escapings === true) {
			$y_string = (string) strtr((string)$y_string, [ // golang compliant
				"\r" => '&#xD;', // '&#13;',
				"\n" => '&#xA;', // '&#10;',
				"\t" => '&#x9;', // '&#09;',
			]);
		} //end if
		//--
		return (string) $y_string;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape strings to be injected in CSS code
	 *
	 * @param 	STRING 		$y_string			:: The string to be escaped
	 *
	 * @return 	STRING							:: The escaped string compatible with Twig standard
	 */
	public static function escape_css(?string $y_string) : string {
		//--
		if((string)$y_string == '') {
			return '';
		} //end if
		//-- http://www.w3.org/TR/2006/WD-CSS21-20060411/syndata.html#q6
	//	return (string) addcslashes((string)$y_string, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~"); // inspired from Latte Templating ; escaped string using the WD-CSS21-20060411 standard
		//--
		// The following characters have a special meaning in CSS, in sensitive contexts they have to be escaped:
		// !, ", #, $, %, &, ', (, ), *, +, ,, -, ., /, :, ;, <, =, >, ?, @, [, \, ], ^, `, {, |, }, ~
		// Compatible with javascript: MDN: CSS.escape(str)
		//--
		if((string)$y_string == '') {
			return '';
		} //end if
		//-- provides a Twig-compatible CSS escaper
		$out = '';
		//--
		for($i=0; $i<SmartUnicode::str_len((string)$y_string); $i++) {
			$c = (string) SmartUnicode::sub_str((string)$y_string, $i, 1);
			$code = (int) SmartUnicode::ord($c);
			if((string)$c == SmartUnicode::chr((int)$code)) {
				if((((int)$code >= 65) && ((int)$code <= 90)) || (((int)$code >= 97) && ((int)$code <= 122)) || (((int)$code >= 48) && ((int)$code <= 57))) {
					$out .= (string) $c; // a-zA-Z0-9
				} else {
					$out .= (string) sprintf("\\%04X", (int)$code); // UTF-8
				} //end if else
			} else {
				self::log_notice(__METHOD__.' # Skip Invalid Character Code Point: `'.$code.'`');
			} //end if else
		} //end for
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe escape strings to be injected in Javascript code as strings
	 *
	 * @param 	STRING 		$str			:: The string to be escaped
	 *
	 * @return 	STRING						:: The escaped string using a json_encode() standard to be injected between single quotes '' or double quotes ""
	 */
	public static function escape_js(?string $str) : string {
		//-- v.20200605
		// Prepare a string to pass in JavaScript Single or Double Quotes
		// By The Situation:
		// * Using inside tags as '<a onClick="self.location = \''.Smart::escape_js($str).'\';"></a>'
		// * Using inside tags as '<script>self.location = \''.Smart::escape_js($str).'\';></script>
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//-- encode as json
		$encoded = (string) @json_encode((string)$str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE, 512); // encode the string includding unicode chars, with all possible: < > ' " &
		//-- the above will provide a json encoded string as: "mystring" ; we get just what's between double quotes as: mystring
		return (string) substr((string)trim((string)$encoded), 1, -1);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * JSON Encode PHP variables to a JSON string
	 *
	 * @param 	MIXED 		$data				:: The variable to be encoded (mixed): numeric, string, array
	 * @param 	BOOLEAN 	$prettyprint		:: *Optional* Default to FALSE ; If TRUE will format the json as pretty-print (takes much more space, but sometimes make sense ...)
	 * @param 	BOOLEAN 	$unescaped_unicode 	:: *Optional* Default to TRUE ; If FALSE will escape unicode characters
	 * @param 	BOOLEAN 	$htmlsafe 			:: *Optional* Default to TRUE ; If FALSE the JSON will not be HTML-Safe as it will not escape: < > ' " &
	 * @param   INTEGER+ 	$depth 				:: *Optional* Default to 512 ; the maximum depth ; must be greater than zero and no more than 1024 (to keep memory footprint low) !
	 *
	 * @return 	STRING							:: The JSON encoded string
	 */
	public static function json_encode($data, bool $prettyprint=false, bool $unescaped_unicode=true, bool $htmlsafe=true, int $depth=512) : string {
		//-- {{{SYNC-JSON-DEFAULT-AND-MAX-DEPTH}}}
		if((int)$depth <= 0) {
			$depth = 512; // default
		} elseif((int)$depth > 1024) {
			$depth = 1024; // max
		} //end if else
		//--
		$options = 0;
		if(!$unescaped_unicode) {
			if($prettyprint) {
				if($htmlsafe) {
					$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE;
				} else {
					$options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE;
				} //end if else
			} else {
				if($htmlsafe) {
					$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE;
				} else {
					$options = JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
				} //end if else
			} //end if else
		} else { // default
			if($prettyprint) {
				if($htmlsafe) {
					$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE;
				} else {
					$options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE;
				} //end if else
			} else {
				if($htmlsafe) {
					$options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
				} else {
					$options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
				} //end if else
			} //end if else
		} //end if else
		//--
		$json = (string) @json_encode($data, $options, (int)$depth); // Fix: must return a string ; mixed data ; depth was added since PHP 5.5
		if((string)$json == '') { // fix if json encode returns FALSE
			self::log_warning(__METHOD__.' # Invalid Encoded JSON for input: '.print_r($data,1));
			$json = 'null';
		} //end if
		//--
		return (string) $json;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Decode JSON strings to PHP native variable(s)
	 *
	 * @param 	STRING 		$json				:: The JSON string
	 * @param 	BOOLEAN		$return_array		:: *Optional* Default to FALSE ; When TRUE, returned objects will be converted into associative arrays (default to TRUE)
	 * @param   INTEGER+ 	$depth 				:: *Optional* Default to 512 ; the maximum depth ; must be greater than zero and no more than 1024 (to keep memory footprint low) !
	 *
	 * @return 	MIXED							:: The PHP native Variable: NULL ; INT ; NUMERIC ; STRING ; ARRAY
	 */
	public static function json_decode(?string $json, bool $return_array=true, int $depth=512) { // mixed
		//-- {{{SYNC-JSON-DEFAULT-AND-MAX-DEPTH}}}
		if((int)$depth <= 0) {
			$depth = 512; // default
		} elseif((int)$depth > 1024) {
			$depth = 1024; // max
		} //end if else
		//-- fix: json decode to decode the exact as encoded using the same depth actually needs the encodingDepth + 1 !
		return @json_decode((string)$json, (bool)$return_array, (int)((int)$depth+1), JSON_BIGINT_AS_STRING); // as json decode depth is added just in PHP 5.5 use the default depth = 512 by now ...
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Serialize PHP variables to a JSON string
	 * This is a safe replacement for PHP serialize() which can break the security if contain unsafe Objects
	 *
	 * @param 	MIXED 		$data			:: The variable to be encoded: numeric, string, array
	 *
	 * @return 	STRING						:: The JSON encoded string
	 */
	public static function seryalize($data) : string {
		//-- seryalize json v.170503
		return (string) self::json_encode($data, false, false, false); // no pretty print, escaped unicode is safer for Redis, no html safe, depth 512
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unserialize JSON data to PHP native variable(s)
	 * This is a safe replacement for PHP unserialize() which can break the security if contain unsafe Objects
	 *
	 * @param 	STRING 		$y_json			:: The JSON string
	 *
	 * @return 	MIXED						:: The PHP native Variable
	 */
	public static function unseryalize(?string $y_json) { // mixed
		//-- unseryalize json v.170503
		return self::json_decode((string)$y_json, true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check if an integer number overflows the minimum or maximum safe int
	 * All numbers over this must use special operators from BCMath to avoid floating point precision issues
	 * On 32-bit platforms the INTEGER is between 		   -2147483648 		to 				 2147483647
	 * On 64-bit platforms the INTEGER is between -9223372036854775808 		to		9223372036854775807
	 *
	 * @param INTEGER NUMBER AS STRING 		$y_number	:: The integer number to be checked
	 *
	 * @return BOOLEAN 									:: TRUE if overflows the max safe integer ; FALSE if is OK (not overflow maximum)
	 */
	public static function check_int_number_overflow($y_number) : bool { // do not enforce a type, will check to be nscalar and numeric !
		//--
		if(!self::is_nscalar($y_number)) {
			return false; // like a string which will be zero if cast to float
		} //end if
		//--
		$overflow = false;
		if( // IMPORTANT: do not cast or pre-format the number to anything before this comparison, by ex if format as float will loose some precision and comparing min float with min int is ... tricky as it will return FALSE ;-)
			((float)abs((float)$y_number) > (int)PHP_INT_MAX)
			OR
			((float)(-1 * abs((float)$y_number)) < (int)PHP_INT_MIN)
		) { // must do both comparisons as this as float min/max is non-symetric
			$overflow = true;
		} //end if
		//--
		return (bool) $overflow;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check if a decimal number overflows the minimum or maximum safe decimal as precision = 14 (PHP default)
	 * Thus decimal numbers must be between -999999999999.9900 and 999999999999.9900
	 * All numbers over this must use special operators from BCMath to avoid floating point precision issues
	 * This is more intended for decimal numbers like financial operations where the significant decimal digits are important
	 *
	 * @param DECIMAL NUMBER AS STRING 		$y_number	:: The decimal number to be checked
	 *
	 * @return BOOLEAN 									:: TRUE if overflows the max safe decimal ; FALSE if is OK (not overflow maximum)
	 */
	public static function check_dec_number_overflow($y_number) : bool { // do not enforce a type, will check to be nscalar and numeric !
		//--
		if(!self::is_nscalar($y_number)) {
			return false; // like a string which will be zero if cast to float
		} //end if
		//--
		$overflow = false;
		if((float)abs((float)$y_number) > (float)self::DECIMAL_NUM_PRECISION) {
			$overflow = true;
		} //end if else
		//--
		return (bool) $overflow;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format a number as FLOAT
	 *
	 * @param 	NUMERIC 	$y_number		:: A numeric value
	 * @param 	ENUM		$y_signed		:: Default to '' ; If set to '+' will return (enforce) an UNSIGNED/POSITIVE Number ; If set to '-' will return (enforce) an NEGATIVE Number ; Otherwise if set to NULL or '' will return just a regular SIGNED Number wich can be negative or positive
	 *
	 * @return 	FLOAT						:: An float number
	 */
	public static function format_number_float($y_number, ?string $y_signed=null) : float { // do not make strongtype on number, it may come as string
		//--
		// must be notice not warning ; in production environments cannot control values that come from request ...
		//--
		if(!self::is_nscalar($y_number)) { // array, object or resource
			self::log_notice(__METHOD__.' # The expected (numeric) input value is not nScalar ; fixed as ZERO [0] : '.print_r($y_number,1));
			$y_number = 0;
		} //end if
		if(is_null($y_number)) { // NULL
			$y_number = 0;
		} //end if
		if(is_bool($y_number)) { //  FALSE/TRUE
			$y_number = (int) $y_number; // cast to 0/1
		} //end if
		if((string)trim((string)$y_number) == '') { // empty string ; must check after bool and null !
			$y_number = 0; // cast to 0
		} //end if
		if(is_nan((float)$y_number)) { // must cast to float ; ex: 0/0
			self::log_notice(__METHOD__.' # The expected (numeric) input value is NAN ; fixed as ZERO [0] : '.$y_number);
			$y_number = 0; // NAN is considered overflow
		} //end if
		if(!is_numeric($y_number)) { // must check after check for null and nan ; it returns true also for number as strings ; ex: a non-numeric string
			$y_number = (float) floatval((string)trim((string)$y_number)); // don't log, extract what can, there are many cases when request variables comes malformed, out of control !
		} //end if
		if(is_infinite((float)$y_number)) { // must cast to float ; ex: log(0) = -INF ; -1 * log(0) = INF
			if((float)$y_number < 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE[-] ; fixed as ['.(float)PHP_FLOAT_MIN.'] : '.$y_number);
				$y_number = (float) PHP_FLOAT_MIN;
			} elseif((float)$y_number > 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE[+] ; fixed as ['.(float)PHP_FLOAT_MAX.'] : '.$y_number);
				$y_number = (float) PHP_FLOAT_MAX;
			} else {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE ; fixed as ZERO [0] : '.$y_number);
				$y_number = 0;
			} //end if else
		} //end if
		//--
		// there is no way to check in PHP if a float overflows ... the PHP_FLOAT_MIN and PHP_FLOAT_MAX are just informative ...
		//--
		if((string)$y_signed == '+') { // positive (unsigned) float
			if((float)$y_number < 0) { // {{{SYNC-SMART-FLOAT+}}}
				$y_number = 0; // it must be zero if negative for the all logic in this framework
			} //end if
		} elseif((string)$y_signed == '-') { // negative float
			if((float)$y_number > 0) { // {{{SYNC-SMART-FLOAT-}}}
				$y_number = 0; // it must be zero if positive for the all logic in this framework
			} //end if
		} //end if else
		//--
		return (float) $y_number;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format a number as INTEGER
	 * On 64-bit platforms the INTEGER is between -9223372036854775808 		to		9223372036854775807
	 *
	 * @param 	NUMERIC 	$y_number		:: A numeric value
	 * @param 	ENUM		$y_signed		:: Default to '' ; If set to '+' will return (enforce) an UNSIGNED/POSITIVE Number ; If set to '-' will return (enforce) an NEGATIVE Number ; Otherwise if set to NULL or '' will return just a regular SIGNED Number wich can be negative or positive
	 *
	 * @return 	INTEGER						:: An integer number
	 */
	public static function format_number_int($y_number, ?string $y_signed=null) : int { // do not make strongtype on number, it may come as string
		//--
		// must be notice not warning ; in production environments cannot control values that come from request ...
		//--
		if(!self::is_nscalar($y_number)) { // array, object or resource
			self::log_notice(__METHOD__.' # The expected (numeric) input value is not nScalar ; fixed as ZERO [0] : '.print_r($y_number,1));
			$y_number = 0;
		} //end if
		if(is_null($y_number)) { // NULL
			$y_number = 0;
		} //end if
		if(is_bool($y_number)) { //  FALSE/TRUE
			$y_number = (int) $y_number; // cast to 0/1
		} //end if
		if((string)trim((string)$y_number) == '') { // empty string ; must check after bool and null !
			$y_number = 0; // cast to 0
		} //end if
		if(is_nan((float)$y_number)) { // must cast to float ; ex 0/0 = NAN
			self::log_notice(__METHOD__.' # The expected (numeric) input value is NAN ; fixed as ZERO [0] : '.$y_number);
			$y_number = 0; // NAN is considered overflow
		} //end if
		if(!is_numeric($y_number)) { // must check after check for null and nan ; it returns true also for number as strings ; ex: a non-numeric string
			$y_number = (float) floatval((string)trim((string)$y_number)); // AS FLOAT !! ; don't log, extract what can, there are many cases when request variables comes malformed, out of control !
		} //end if
		if(is_infinite((float)$y_number)) { // must cast to float ; ex: log(0) = -INF ; -1 * log(0) = INF
			if((float)$y_number < 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE[-] ; fixed as ['.(int)PHP_INT_MIN.'] : '.$y_number);
				$y_number = (int) PHP_INT_MIN;
			} elseif((float)$y_number > 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE[+] ; fixed as ['.(int)PHP_INT_MAX.'] : '.$y_number);
				$y_number = (int) PHP_INT_MAX;
			} else {
				self::log_notice(__METHOD__.' # The expected (numeric) input value is INFINITE ; fixed as ZERO [0] : '.$y_number);
				$y_number = 0;
			} //end if else
		} //end if
		if(self::check_int_number_overflow($y_number) === true) { // must bind to be in limits
			if((float)$y_number < 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS[-] the limits ; fixed as ['.(int)PHP_INT_MIN.'] : '.$y_number);
				$y_number = (int) PHP_INT_MIN;
			} elseif((float)$y_number > 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS[+] the limits ; fixed as ['.(int)PHP_INT_MAX.'] : '.$y_number);
				$y_number = (int) PHP_INT_MAX;
			} else {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS the limits ; fixed as ZERO [0] : '.$y_number);
				$y_number = 0;
			} //end if else
		} //end if
		//--
		if((string)$y_signed == '+') { // positive (unsigned) integer
			if((int)$y_number < 0) { // {{{SYNC-SMART-INT+}}}
				$y_number = 0; // it must be zero if negative for the all logic in this framework
			} //end if
		} elseif((string)$y_signed == '-') { // negative integer
			if((int)$y_number > 0) { // {{{SYNC-SMART-INT-}}}
				$y_number = 0; // it must be zero if positive for the all logic in this framework
			} //end if
		} //end if
		//--
		return (int) $y_number;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Format a number as DECIMAL (NOTICE: The maximum PHP.INI precision is 14, includding decimals).
	 * Because of the precision, it supports values between -999999999999.9900 and 999999999999.9900 ; If a value overflows limits will return -limit / +limit depends if value is negative or positive
	 * This is a better replacement for the PHP's number_format() which throws a warning if first argument passed is a string since PHP 5.3
	 *
	 * @param 	NUMERIC 			$y_number			:: A numeric value
	 * @param 	INTEGER+			$y_decimals			:: The number of decimal to use (safe value is between 1..13, keeping in mind the 14 max precision) ; Default is 2
	 * @param 	STRING				$y_sep_decimals 	:: The decimal separator symbol as: 	. or , (default is .)
	 * @param 	STRING 				$y_sep_thousands	:: The thousand separator symbol as: 	, or . (default is [none])
	 *
	 * @return 	DECIMAL 								:: A decimal number as string
	 */
	public static function format_number_dec($y_number, $y_decimals=2, ?string $y_sep_decimals='.', ?string $y_sep_thousands='') : string { // do not make strongtype on number or decimals, it may come as string
		//--
		// must be notice not warning ; in production environments cannot control values that come from request ...
		//--
		if(!self::is_nscalar($y_decimals)) {
			self::log_notice(__METHOD__.' # The expected (decimals) input value is not nScalar ; fixed as ZERO [0] : '.print_r($y_decimals,1));
			$y_decimals = 0;
		} //end if
		$y_decimals = (int) self::format_number_int($y_decimals, '+'); // fix decimals
		if($y_decimals < 1) { // 9999999999999.9
			$y_decimals = 1;
		} elseif($y_decimals > 13) { // 0.0000000000001
			$y_decimals = 13;
		} //end if
		//-- fix float
		if(!self::is_nscalar($y_number)) {
			self::log_notice(__METHOD__.' # The expected (numeric) input value is not nScalar ; fixed as ZERO [0] : '.print_r($y_number,1));
			$y_number = 0;
		} //end if
		if(self::check_dec_number_overflow($y_number) === true) {
			if((float)$y_number < 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS[-] the limits ; fixed as [-'.self::DECIMAL_NUM_PRECISION.'] : '.$y_number); // must be notice not warning ; in production environments cannot control values that come from request ...
				$y_number = (float) (-1 * (float)self::DECIMAL_NUM_PRECISION);
			} elseif((float)$y_number > 0) {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS[+] the limits ; fixed as ['.self::DECIMAL_NUM_PRECISION.'] : '.$y_number); // must be notice not warning ; in production environments cannot control values that come from request ...
				$y_number = (float) self::DECIMAL_NUM_PRECISION;
			} else {
				self::log_notice(__METHOD__.' # The expected (numeric) input value OVERFLOWS the limits ; fixed as ZERO [0] : '.$y_number); // must be notice not warning ; in production environments cannot control values that come from request ...
				$y_number = 0;
			} //end if
		} //end if
		$y_number = (float) self::format_number_float($y_number);
		//-- by default number_format() returns string, so enforce string as output to keep decimals
		return (string) number_format((float)$y_number, (int)$y_decimals, (string)$y_sep_decimals, (string)$y_sep_thousands); // {{{SYNC-SMART-DECIMAL}}}
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe array count(), for safety, with array type check ; this should be used instead of count() because count(string) returns a non-zero value and can confuse if a string is passed to count instead of an array
	 *
	 * @param ARRAY/MIXED 		$y_arr			:: The array to count elements on ; can be a mixed variable ; if non-array will return zero
	 *
	 * @return INTEGER 							:: The array COUNT of elements, or zero if array is empty or non-array is provided
	 */
	public static function array_size($y_arr) : int { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO TEST ALSO NON-ARRAY VARS !!!
		//--
		if(is_array($y_arr)) {
			return (int) count($y_arr);
		} else {
			return 0;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Easy sort for NON-Associative arrays ...
	 *
	 * @param ARRAY 		$y_arr			:: The array to be sorted by a criteria (type, see below)
	 * @param ENUM 			$y_mode			:: The sort type: natsort, sort, rsort, asort, arsort, ksort, krsort
	 *
	 * @return ARRAY 						:: The sorted array
	 */
	public static function array_sort(?array $y_arr, ?string $y_mode) : array {
		//--
		if(self::array_size($y_arr) <= 0) {
			return array();
		} //end if
		//--
		switch((string)strtolower((string)$y_mode)) {
			//--
			case 'natsort': // natural sort
				@natsort($y_arr);
				break;
			case 'natcasesort': // natural case-sensitive sort
				@natcasesort($y_arr);
				break;
			case 'sort': // regular sort
				@sort($y_arr);
				break;
			case 'rsort': // regular reverse sort
				@rsort($y_arr);
				break;
			//--
			case 'asort': // associative sort
				@asort($y_arr);
				break;
			case 'arsort': // associative reverse sort
				@arsort($y_arr);
				break;
			case 'ksort': // associative key sort
				@ksort($y_arr);
				break;
			case 'krsort': // associative key reverse sort
				@krsort($y_arr);
				break;
			//--
			default:
				self::log_warning(__METHOD__.' # Invalid Sort Mode: `'.$y_mode.'`');
				return (array) $y_arr;
			//--
		} //end switch
		//--
		if((int)self::array_type_test((array)$y_arr) != 2) {
			$y_arr = (array) array_values((array)$y_arr); // only for non-associative arrays ; for associative arrays this will lost the keys, don't apply !
		} //end if
		//--
		return (array) $y_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Shuffle for NON-Associative arrays ...
	 *
	 * @param ARRAY 		$y_arr			:: The array to be sorted by a criteria (type, see below)
	 *
	 * @return ARRAY 						:: The sorted array
	 */
	public static function array_shuffle(?array $y_arr) : array {
		//--
		if(self::array_size($y_arr) <= 0) {
			return array();
		} //end if
		//--
		shuffle($y_arr);
		//--
		return (array) $y_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array Get (value) By Key Path (case sensitive)
	 *
	 * @param ARRAY 		$y_arr 					:: The input array
	 * @param STRING 		$y_key_path 			:: The composed key path by levels (Ex: key1.key2) :: case-sensitive
	 * @param STRING 		$y_path_separator 		:: The key path separator (Example: .)
	 *
	 * @return MIXED 		:: The value from the specified array by the specific key path or NULL if the value does not exists
	 */
	public static function array_get_by_key_path(?array $y_arr, ?string $y_key_path, ?string $y_path_separator) { // mixed
		//--
		if(self::array_size($y_arr) <= 0) {
			return null;
		} //end if
		//--
		$y_key_path = (string) trim((string)$y_key_path);
		$y_path_separator = (string) trim((string)$y_path_separator);
		//--
		if((string)$y_key_path == '') {
			return null; // dissalow empty key path
		} //end if
		//--
		if(strlen((string)$y_path_separator) != 1) {
			return null; // dissalow empty separator
		} //end if
		//--
		$arr = (array) explode((string)$y_path_separator, (string)$y_key_path);
		$max = (int) count($arr);
		for($i=0; $i<$max; $i++) {
			if((string)trim((string)$arr[$i]) != '') {
				if(is_array($y_arr)) {
					if(array_key_exists($arr[$i], $y_arr)) {
						$y_arr = $y_arr[$arr[$i]];
					} else {
						$y_arr = null;
						break;
					} //end if
				} else {
					$y_arr = null;
					break;
				} //end if
			} else {
				$y_arr = null;
				break;
			} //end if
		} //end for
		//--
		return $y_arr; // mixed
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array Test if Key Exist By (Key) Path (case sensitive)
	 *
	 * @param ARRAY 		$y_arr 					:: The input array
	 * @param STRING 		$y_key_path 			:: The composed key path by levels (Ex: key1.key2) :: case-sensitive
	 * @param STRING 		$y_path_separator 		:: The key path separator (Example: .)
	 *
	 * @return BOOL 								:: TRUE if Key Exist / FALSE if NOT
	 */
	public static function array_test_key_by_path_exists(?array $y_arr, ?string $y_key_path, ?string $y_path_separator) : bool {
		//--
		if(self::array_size($y_arr) <= 0) {
			return false;
		} //end if
		//--
		$y_key_path = (string) trim((string)$y_key_path);
		$y_path_separator = (string) trim((string)$y_path_separator);
		//--
		if((string)$y_key_path == '') {
			return false; // dissalow empty key path
		} //end if
		//--
		if(strlen($y_path_separator) != 1) {
			return false; // dissalow empty separator
		} //end if
		//--
		$arr = (array) explode((string)$y_path_separator, (string)$y_key_path);
		$max = (int) count($arr);
		$tarr = (array) $y_arr;
		for($i=0; $i<$max; $i++) {
			$arr[$i] = (string) trim((string)$arr[$i]);
			if((string)$arr[$i] != '') {
				if(!is_array($tarr)) {
					return false;
				} //end if
				if(!array_key_exists((string)$arr[$i], (array)$tarr)) {
					return false;
				} //end if
				$tarr = $tarr[(string)$arr[$i]];
			} //end if
		} //end for
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array Recursive Change Key Case
	 *
	 * @param ARRAY 		$y_arr 					:: The input array
	 * @param ENUM 			$y_mode 				:: Change Mode: LOWER | UPPER
	 *
	 * @return ARRAY 								:: The modified array
	 */
	public static function array_change_key_case_recursive(?array $y_arr, ?string $y_mode) : array {
		//--
		if(self::array_size($y_arr) <= 0) { // fix bug if empty array / max nested level
			return array();
		} //end if
		//--
		switch((string)strtoupper((string)$y_mode)) {
			case 'UPPER':
				$case = CASE_UPPER;
				break;
			case 'LOWER':
				$case = CASE_LOWER;
				break;
			default:
				return (array) $y_arr;
		} //end if
		//--
		return (array) array_map(
			function($y_newarr) use($y_mode) {
				if(is_array($y_newarr)) {
					$y_newarr = self::array_change_key_case_recursive($y_newarr, $y_mode);
				} //end if
				return $y_newarr; // mixed
			},
			array_change_key_case($y_arr, $case)
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array Initialize Keys
	 *
	 * @param ARRAY/MIXED 		$y_arr 					:: The input array
	 * @param LIST/ARRAY 		$y_keys 				:: The array keys to initialize: will add these keys to the array assigned with a NULL value only if the key does not exists ; the existing keys will be preserved with their existing values ; must be a non-associative array, as: [ 'key1', 'key2', '', ...] ; a key can be also an empty string
	 *
	 * @return ARRAY 									:: The modified array
	 */
	public static function array_init_keys($y_arr, $y_keys) : array { // do not enforce parameters type, it have a wide usage and detects !
		//--
		if(!is_array($y_arr)) {
			$y_arr = array();
		} //end if
		//--
		if(self::array_type_test($y_keys) == 1) {
			$num_keys = (int) self::array_size($y_keys);
			if((int)$num_keys > 0) {
				for($i=0; $i<$num_keys; $i++) {
					if(self::is_nscalar($y_keys[$i])) {
						if(!array_key_exists((string)$y_keys[$i], $y_arr)) {
							$y_arr[(string)$y_keys[$i]] = null;
						} //end if
					} else {
						self::log_warning(__METHOD__.' # Array init key is not nScalar: '.print_r($y_keys[$i],1));
					} //end if else
				} //end for
			} //end if
		} else {
			self::log_warning(__METHOD__.' # Array init keys are not compliant ... must be non-associative type: '.print_r($y_keys,1));
		} //end if
		//--
		return (array) $y_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test if the Array Type for a given variable
	 *
	 * @param ARRAY 		$y_arr			:: The variable to test
	 *
	 * @return INT/ENUM 					:: Will return the type: 0 = not an array ; 1 = non-associative array (list, sequential array or empty array) ; 2 = associative array (map, non-sequential key/val)
	 */
	public static function array_type_test($y_arr) : int { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER $y_arr AS IT HAVE TO TEST ALSO NON-ARRAY VARS !!!
		//--
		if(!is_array($y_arr)) {
			return 0; // not an array
		} //end if
		//--
		$is_list = false; // by default consider is non-list (associative array, aka: map)
		if(SMART_FRAMEWORK_PHP_HAVE_ARRAY_LIST === true) { // requires PHP >= 8.1.0
			$is_list = (bool) array_is_list((array)$y_arr);
		} else {
			$is_list = (bool) ((array)array_values((array)$y_arr) === (array)$y_arr); // speed-optimized, the fastest with PHP < 8.1 with non-associative large arrays, tested in all scenarios with large or small arrays
		} //end if
		//--
		if($is_list === true) {
			return 1; // non-associative array (list, sequential numeric index, 0..n)
		} else {
			return 2; // associative (map, aka: key/val, non-sequential)
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array recursive Diff (Dual-Way, from Left to Right and from Right to Left)
	 *
	 * @param ARRAY $array1
	 * @param ARRAY $array2
	 *
	 * @return ARRAY
	 */
	public static function array_diff_assoc_recursive(array $array1, array $array2) : array {
		//--
		$diff1 = (array) self::array_diff_assoc_oneway_recursive((array)$array1, (array)$array2);
		$diff2 = (array) self::array_diff_assoc_oneway_recursive((array)$array2, (array)$array1);
		//--
		return (array) array_merge_recursive((array)$diff1, (array)$diff2);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Array recursive Diff (One Way Only, from Left to Right)
	 *
	 * @param ARRAY $array1
	 * @param ARRAY $array2
	 *
	 * @return ARRAY
	 */
	public static function array_diff_assoc_oneway_recursive(array $array1, array $array2) : array {
		//--
		$difference = array();
		//--
		foreach($array1 as $key => $value) {
			if(is_array($value)) {
				if(!isset($array2[$key]) || !is_array($array2[$key])) {
					$difference[$key] = $value;
				} else {
					$new_diff = self::array_diff_assoc_oneway_recursive($value, $array2[$key]);
					if(!empty($new_diff)) {
						$difference[$key] = $new_diff;
					} //end if
				} //end if else
			} elseif(!array_key_exists($key, $array2) || ($array2[$key] != $value)) { // !==
				$difference[$key] = $value;
			} //end if else
		} //end foreach
		//--
		return (array) $difference;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Convert an Array to a List String
	 *
	 * @param ARRAY 	$y_arr			:: The Array to be converted: Array(elem1, elem2, ..., elemN)
	 *
	 * @return STRING 					:: The List String: '<elem1>, <elem2>, ..., <elemN>'
	 */
	public static function array_to_list($y_arr) : string { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO WORK ALSO NON-ARRAY VARS !!!
		//--
		$out = '';
		//--
		if(self::array_size($y_arr) > 0) { // this also check if it is array
			//--
			$arr = array();
			//--
			foreach($y_arr as $key => $val) {
				//--
				if(!is_array($val)) {
					//--
					$val = (string) trim((string)$val); // must not do strtolower as it is used to store both cases
					$val = (string) str_replace(['<', '>'], ['‹', '›'], (string)$val); // {{{SYNC-SMARTLIST-BRACKET-REPLACEMENTS}}}
					$val = (string) str_replace(',', ';', (string)$val); // fix just on value
					if((string)$val != '') {
						if(!in_array('<'.$val.'>', $arr)) {
							$arr[] = '<'.$val.'>';
						} //end if
					} //end if
					//--
				} //end if
				//--
			} //end foreach
			//--
			$out = (string) implode(', ', (array)$arr);
			//--
			$arr = array();
			//--
		} //end if else
		//--
		return (string) trim((string)$out);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Convert a List String to Array
	 *
	 * @param STRING 	$y_list			:: The List String to be converted: '<elem1>, <elem2>, ..., <elemN>'
	 * @param BOOLEAN 	$y_trim 		:: *Optional* default is TRUE ; If set to FALSE will not trim the values in the list, in some cases preserve spaces is required
	 *
	 * @return ARRAY 					:: The Array: Array(elem1, elem2, ..., elemN)
	 */
	public static function list_to_array(?string $y_list, bool $y_trim=true) : array {
		//--
		if((string)trim((string)$y_list) == '') {
			return array(); // empty list
		} //end if
		//--
		$y_list = (string) trim((string)$y_list);
		//--
		$arr = (array) explode(',', (string)$y_list);
		$new_arr = array();
		for($i=0; $i<self::array_size($arr); $i++) {
			$arr[$i] = (string) trim((string)$arr[$i]); // must trim outside <> entries because was exploded by ',' and list can be also ', '
			$arr[$i] = (string) str_replace(['<', '>'], ['', ''], (string)$arr[$i]);
			if($y_trim !== false) {
				$arr[$i] = (string) trim((string)$arr[$i]); // trim <> inside values only if explicit required (otherwise preserve inside spaces)
			} //end if
			if((string)trim((string)$arr[$i]) != '') {
				if(!in_array((string)$arr[$i], $new_arr)) {
					$new_arr[] = (string) $arr[$i];
				} //end if
			} //end if
		} //end for
		//--
		return (array) $new_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Cut a text by a fixed length, if longer than allowed length.
	 * If cut words option is FALSE it may make the string shorted by rolling back until the last space and cutting off the last partial word.
	 * The default cut suffix is (...) but can be disabled using the last parameter.
	 *
	 * @param 	STRING 	$ytxt 				:: The text string to be processed
	 * @param 	STRING 	$ylen 				:: The fixed length of the string
	 * @param 	BOOLEAN	$y_cut_words		:: *Optional* Default TRUE ; if TRUE, will CUT last word to provide a fixed length ; if FALSE will eliminate unterminate last word ; default is TRUE
	 * @param 	ENUM 	$y_suffix 			:: *Optional* Default '...' ; Can be '' or '[...a cutoff-message...]'
	 *
	 * @return 	STRING						:: The processed string (text)
	 */
	public static function text_cut_by_limit(?string $ytxt, ?int $ylen, bool $y_cut_words=true, ?string $y_suffix='...') : string {
		//--
		$ytxt = (string) trim((string)$ytxt);
		$ylen = (int) self::format_number_int($ylen, '+');
		//--
		if((string)$y_suffix == '') {
			$cutoff = (int) $ylen;
		} else {
			$cutoff = (int) self::format_number_int(((int)$ylen - (int)SmartUnicode::str_len((string)$y_suffix)), '+');
		} //end if else
		if($cutoff <= 0) {
			$cutoff = 1;
		} //end if
		//--
		if((int)SmartUnicode::str_len((string)$ytxt) > (int)$cutoff) {
			//--
			$ytxt = (string) SmartUnicode::sub_str((string)$ytxt, 0, (int)$cutoff);
			//--
			if($y_cut_words === false) {
				//-- {{{SYNC-REGEX-TEXT-CUTOFF}}}
				$ytxt = (string) preg_replace('/\s+?(\S+)?$/', '', (string)$ytxt); // cut off last word until first space (if no space, no cut)
				//--
			} //end if
			//--
			$ytxt .= (string) $y_suffix;
			//--
		} //end if
		//--
		return (string) $ytxt;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Easy HTML5 compliant nl2br() ; Will replace new lines (\n) with HTML5 <br> instead of XHTML <br />
	 *
	 * @param STRING 		$y_code			:: The string to apply nl2br()
	 *
	 * @return STRING 						:: The formatted string
	 */
	public static function nl_2_br(?string $y_code, bool $y_trim=true) : string {
		//--
		if($y_trim !== false) {
			$y_code = (string) trim((string)$y_code);
		} //end if
		//--
		return (string) nl2br((string)$y_code, false); // 2nd param is false for not xhtml tags, since PHP 5.3 !!
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Decode XML Entities
	 *
	 * @param STRING 		$str			:: The code to be processed
	 *
	 * @return STRING 						:: The processed Code
	 */
	public static function decode_xml_entities(?string $str) : string {
		//--
		return (string) html_entity_decode((string)$str, ENT_XML1 | ENT_QUOTES, (string)SMART_FRAMEWORK_CHARSET);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Decode HTML Entities
	 *
	 * @param STRING 		$str			:: The code to be processed
	 *
	 * @return STRING 						:: The processed Code
	 */
	public static function decode_html_entities(?string $str) : string {
		//--
		return (string) html_entity_decode((string)$str, ENT_HTML5 | ENT_QUOTES, (string)SMART_FRAMEWORK_CHARSET);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Enhanced strip_tags() :: will revert also special entities like nbsp; and more
	 *
	 * @param ARRAY 		$yhtmlcode		:: HTML Code to be stripped of tags
	 * @param BOOL 			$y_mode			:: Default is TRUE to convert <br> to new lines \n, otherwise (if FALSE) will convert <br> to spaces
	 * @param BOOL 			$ynormalize 	:: Default is TRUE to normalize the code as text, otherwise (if FALSE) will do not use extra normalize feature so the code may be re-used for re-encoding as html (ex: extract markdown) ...
	 *
	 * @return STRING 						:: The processed HTML Code
	 */
	public static function stripTags(?string $yhtmlcode, bool $ynewline=true, bool $ynormalize=true) : string { // {{{SYNC-SMART-STRIP-TAGS-LOGIC}}}
		//-- fix xhtml tag ends and add spaces between tags
		$yhtmlcode = (string) strtr((string)$yhtmlcode, [
			' />' 	=> '> ',
			'/>' 	=> '> ',
			'>' 	=> '> ',
		]);
		//-- remove special tags
		$html_regex_h = [
			'#<head[^>]*?'.'>.*?</head[^>]*?'.'>#si',				// head
			'#<style[^>]*?'.'>.*?</style[^>]*?'.'>#si',				// style
			'#<script[^>]*?'.'>.*?</script[^>]*?'.'>#si',			// script
			'#<noscript[^>]*?'.'>.*?</noscript[^>]*?'.'>#si',		// noscript
			'#<frameset[^>]*?'.'>.*?</frameset[^>]*?'.'>#si',		// frameset
			'#<frame[^>]*?'.'>.*?'.'</frame[^>]*?'.'>#si',			// frame
			'#<iframe[^>]*?'.'>.*?'.'</iframe[^>]*?'.'>#si',		// iframe
			'#<canvas[^>]*?'.'>.*?'.'</canvas[^>]*?'.'>#si',		// canvas
			'#<audio[^>]*?'.'>.*?'.'</audio[^>]*?'.'>#si',			// audio
			'#<video[^>]*?'.'>.*?'.'</video[^>]*?'.'>#si',			// video
			'#<applet[^>]*?'.'>.*?'.'</applet[^>]*?'.'>#si',		// applet
			'#<param[^>]*?'.'>.*?'.'</param[^>]*?'.'>#si',			// param
			'#<object[^>]*?'.'>.*?'.'</object[^>]*?'.'>#si',		// object
			'#<form[^>]*?'.'>.*?'.'</form[^>]*?'.'>#si',			// form
			'#<link[^>]*?'.'>#si',									// link
			'#<img[^>]*?'.'>#si'									// img
		];
		$yhtmlcode = (string) preg_replace((array)$html_regex_h, ' ', (string)$yhtmlcode);
		$yhtmlcode = (string) str_replace(["\r\n", "\r", "\t", "\f"], ["\n", "\n", ' ', ' '], (string)$yhtmlcode);
		//-- replace new line tags
		if($ynewline === true) {
			$replchr = "\n"; // newline
		} else {
			$replchr = ' '; // space
		} //end if else
		$yhtmlcode = (string) str_ireplace(['<br>', '</br>', '<br/>', '<br />'], (string)$replchr, (string)$yhtmlcode);
		//-- strip the tags
		$yhtmlcode = (string) strip_tags((string)$yhtmlcode);
		//-- restore some usual html entities
		if($ynormalize === true) {
			$yhtmlcode = (string) str_ireplace((array)array_keys((array)self::STRIP_HTML_ENTITIES), (array)array_values((array)self::STRIP_HTML_ENTITIES), (string)$yhtmlcode); // must be insensitive replace ... by example &Prime; can be also &prime; ... in this context, using STRIP_HTML_ENTITIES they willnot conflict with wrong entities if using case insensitice since they are unique also in case insensitive
		} //end if
		//-- if new tags may appear after strip tags that is natural as they were encoded already with entities ... ; Anyway, the following can't be used as IT BREAKS TEXT THAT COMES AFTER < which was previous encoded as &lt; !!!
		//$yhtmlcode = (string) strip_tags((string)$yhtmlcode); // [disabled, not needed] fix: after all fixes when reversing entities, new tags can appear that were encoded, so needs run again for safety ...
		//-- restore html unicode entities
		$yhtmlcode = (string) str_replace((array)array_values((array)SmartUnicode::ACCENTED_HTML_ENTITIES), (array)array_keys((array)SmartUnicode::ACCENTED_HTML_ENTITIES), (string)$yhtmlcode);
		//-- try to convert other remaining html entities
		$yhtmlcode = (string) self::decode_html_entities((string)$yhtmlcode);
		//-- clean any other remaining html entities
		if($ynormalize === true) {
			$yhtmlcode = (string) preg_replace('/&\#?([0-9a-z]+);/i', ' ', (string)$yhtmlcode);
		} //end if
		//-- cleanup multiple spaces with just one space
		$yhtmlcode = (string) preg_replace('/[ \\t]+/', ' ', (string)$yhtmlcode); // replace multiple tabs or spaces with one space
		//-- other fixes ; {{{SYNC-FIX-EMPTY-MULTI-LINES-WITH-ONE-LINE}}}
		$yhtmlcode = (string) preg_replace('/^\s*[\n]{2,}/m', '', (string)$yhtmlcode); // fix: replace multiple consecutive lines that may also contain before optional leading spaces
		$yhtmlcode = (string) preg_replace('/[^\S\r\n]+$/m', '', (string)$yhtmlcode); // remove trailing spaces on each line
		//--
		return (string) trim((string)$yhtmlcode);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Test and Fix if unsafe detected for: safe path / safe filename / safe valid name / safe username / safe varname
	 * This is intended to be used against the result of above functions to avoid generate an unsafe file system path (ex: . or .. or / or /. or /..)
	 * Because all the above functions may return an empty (string) result, if unsafe sequences are detected will just fix it by clear the result (enforce empty string is better than unsafe)
	 * It should allow also both: absolute and relative paths, thus if absolute path should be tested later
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 						:: The fixed (filesys) safe string
	 *
	 */
	public static function safe_fix_invalid_filesys_names(?string $y_fsname) : string {
		//-- v.20231022
		$y_fsname = (string) trim((string)$y_fsname);
		//-- {{{SYNC-SAFE-PATH-CHARS}}} {{{SYNC-CHK-SAFE-PATH}}}
		if(
			((string)$y_fsname == '.') OR
			((string)$y_fsname == '..') OR
			((string)$y_fsname == ':') OR
			((string)$y_fsname == '/') OR
			((string)$y_fsname == '/.') OR
			((string)$y_fsname == '/..') OR
			((string)$y_fsname == '/:') OR
			((string)ltrim((string)$y_fsname, '/') == '.') OR
			((string)ltrim((string)$y_fsname, '/') == '..') OR
			((string)ltrim((string)$y_fsname, '/') == ':') OR
			((string)trim((string)$y_fsname, '/') == '') OR
			((string)substr((string)$y_fsname, -2, 2) == '/.') OR
			((string)substr((string)$y_fsname, -3, 3) == '/..')
		) {
			$y_fsname = '';
		} //end if
		//--
		return (string) $y_fsname; // returns trimmed value or empty if non-safe
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a Safe Path Name to be used to process dynamic build paths to avoid weird path character injections
	 * This should be used for relative or absolute path to files or dirs
	 * It should allow also both: absolute and relative paths, thus if absolute path should be tested later
	 * NOTICE: It may return an empty string if all characters in the path are invalid or invalid path sequences detected, so if empty path name must be tested later
	 * ALLOWED CHARS: [a-zA-Z0-9] _ - . @ # /
	 *
	 * @param STRING 		$y_path			:: Path to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 *
	 * @return STRING 						:: The safe path ; if invalid will return empty value
	 */
	public static function safe_pathname(?string $y_path, ?string $ysupresschar='') : string {
		//-- v.20231119
		$y_path = (string) trim((string)$y_path); // force string and trim
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		if(preg_match((string)self::REGEX_SAFE_PATH_NAME, (string)$y_path)) { // {{{SYNC-CHK-SAFE-PATH}}}
			return (string) self::safe_fix_invalid_filesys_names((string)$y_path);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) { // strict control: must not contain any regex backreferences such as: $ as $1.. or \\1
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_path = (string) preg_replace((string)self::lower_unsafe_characters(), '', (string)$y_path); // remove dangerous characters
		$y_path = (string) SmartUnicode::utf8_to_iso((string)$y_path); // bring STRING to ISO-8859-1
		$y_path = (string) stripslashes((string)$y_path); // remove any possible back-slashes
		$y_path = (string) self::normalize_spaces((string)$y_path); // normalize spaces to catch null seq.
		//$y_path = (string) str_replace('?', $ysupresschar, $y_path); // replace questionmark (that may come from utf8 decode) ; this is already done below
		$y_path = (string) preg_replace('/[^_a-zA-Z0-9\-\.@\#\/]/', (string)$ysupresschar, (string)$y_path); // {{{SYNC-SAFE-PATH-CHARS}}} suppress any other characters than these, no unicode modifier
		$y_path = (string) preg_replace("/(\.)\\1+/", '.', (string)$y_path); // suppress multiple . dots and replace with single dot
		$y_path = (string) preg_replace("/(\/)\\1+/", '/', (string)$y_path); // suppress multiple // slashes and replace with single slash
		$y_path = (string) str_replace([ '../', './' ], [ '-', '-' ], (string)$y_path); // replace any unsafe path combinations (do not suppress but replace with a fixed character to avoid create security breaches)
		$y_path = (string) trim((string)$y_path); // finally trim it
		//--
		return (string) self::safe_fix_invalid_filesys_names((string)$y_path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a Safe File Name to be used to process dynamic build file names or dir names to avoid weird path character injections
	 * To should be used only for file or dir names (not paths)
	 * NOTICE: DO NOT USE for full paths or full dir paths because will break them, as the / character is supressed
	 * NOTICE: It may return an empty string if all characters in the file/dir name are invalid or invalid path sequences detected, so if empty file/dir name must be tested later
	 * ALLOWED CHARS: [a-zA-Z0-9] _ - . @ #
	 *
	 * @param STRING 		$y_fname		:: File Name or Dir Name to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 *
	 * @return STRING 						:: The safe file or dir name ; if invalid will return empty value
	 */
	public static function safe_filename(?string $y_fname, ?string $ysupresschar='') : string {
		//-- v.20231119
		$y_fname = (string) trim((string)$y_fname); // force string and trim
		if((string)$y_fname == '') {
			return '';
		} //end if
		//--
		if(preg_match((string)self::REGEX_SAFE_FILE_NAME, (string)$y_fname)) { // {{{SYNC-CHK-SAFE-FILENAME}}}
			return (string) self::safe_fix_invalid_filesys_names((string)$y_fname);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) { // DO NOT ALLOW DOT . AS IS SECURITY RISK, replaced below
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_fname = (string) self::safe_pathname((string)$y_fname, (string)$ysupresschar);
		$y_fname = (string) str_replace('/', '-', (string)$y_fname); // replace the path character with a fixed character (do not suppress to avoid create security breaches)
		$y_fname = (string) trim((string)$y_fname); // finally trim it
		//--
		return (string) self::safe_fix_invalid_filesys_names((string)$y_fname);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a (RFC, ISO) Safe compliant User Name, Domain Name or Email Address
	 * NOTICE: It may return an empty string if all characters in the given name are invalid or invalid path sequences detected, so if empty name must be tested later
	 * ALLOWED CHARS: [a-z0-9] _ - . @
	 *
	 * @param STRING 		$y_name			:: Name to be processed
	 * @param STRING 		$ysupresschar	:: The suppression character to replace weird characters ; optional ; default is ''
	 * @param BOOL 			$y_allow_upper 	:: Allow UpperCase ; *Optional* ; Default is FALSE
	 *
	 * @return STRING 						:: The safe name ; if invalid should return empty value
	 */
	public static function safe_validname(?string $y_name, ?string $ysupresschar='', bool $y_allow_upper=false) : string {
		//-- v.20231119
		$y_name = (string) trim((string)$y_name); // force string and trim
		if((string)$y_name == '') {
			return '';
		} //end if
		//--
		if(
			(
				($y_allow_upper !== true)
				AND
				(preg_match((string)self::REGEX_SAFE_VALID_NAME, (string)$y_name))
			)
			OR
			(
				($y_allow_upper === true)
				AND
				(preg_match((string)self::REGEX_SAFE_VALID_NAME, (string)strtolower((string)$y_name)))
			)
		) {
			return (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		} //end if
		//--
		$ysupresschar = (string) $ysupresschar; // force string and be sure is lower
		switch((string)$ysupresschar) {
			case '-':
			case '_':
				break;
			default:
				$ysupresschar = '';
		} //end if
		//--
		$y_name = (string) self::safe_filename((string)$y_name, (string)$ysupresschar);
		if($y_allow_upper !== true) {
			$y_name = (string) strtolower((string)$y_name); // make all lower chars
		} //end if
		$y_name = (string) str_replace('#', '', (string)$y_name); // replace also diez
		$y_name = (string) trim((string)$y_name);
		//--
		return (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create a Safe Valid Strict User Name
	 * NOTICE: It may return an empty string if all characters in the given name are invalid or invalid path sequences detected, so if empty name must be tested later
	 * ALLOWED CHARS: [a-z0-9] .
	 *
	 * @param STRING 		$y_name			:: Name to be processed
	 *
	 * @return STRING 						:: The safe name ; if invalid should return empty value
	 */
	public static function safe_username(?string $y_name) : string {
		//-- v.20231119
		$y_name = (string) trim((string)$y_name); // force string and trim
		if((string)$y_name == '') {
			return '';
		} //end if
		//--
		if(preg_match((string)self::REGEX_SAFE_USERNAME, (string)$y_name)) {
			return (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		} //end if
		//--
		$y_name = (string) self::safe_validname((string)$y_name); // cannot use dot as a suppress character because is unsupported by validname
		$y_name = (string) str_replace(['@', '-', '_'], '', (string)$y_name); // replace also @ - _
		$y_name = (string) trim((string)$y_name);
		//--
		return (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a Safe Valid Variable Name
	 * NOTICE: this have a special usage and must allow also 0..9 as prefix because is can be used for other purposes not just for real safe variable names, thus if real safe valid variable name must be tested later (real safe variable names cannot start with numbers ...)
	 * NOTICE: In case of empty string will return Undef____V_a_r
	 * ALLOWED CHARS: [a-zA-Z0-9] _
	 *
	 * @param STRING 		$y_name				:: Variable Name to be processed
	 * @param BOOL 			$y_allow_upper 		:: Allow UpperCase ; *Optional* ; Default is TRUE
	 *
	 * @return STRING 							:: The safe variable name ; if invalid instead of returning an empty value, will return an undef var name
	 */
	public static function safe_varname(?string $y_name, bool $y_allow_upper=true) : string {
		//-- v.20231119
		$y_name = (string) trim((string)$y_name); // force string and trim
		if((string)$y_name == '') {
			return (string) self::UNDEF_VAR_NAME;
		} //end if
		//--
		if($y_allow_upper === false) {
			$y_name = (string) strtolower((string)$y_name);
		} //end if
		//--
		if(preg_match((string)self::REGEX_SAFE_VAR_NAME, (string)$y_name)) {
			return (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		} //end if
		//--
		$y_name = (string) self::safe_filename((string)$y_name, '-');
		$y_name = (string) str_replace([ '-', '.', '@', '#' ], '', (string)$y_name); // replace the invalid - . @ #
		$y_name = (string) trim((string)$y_name);
		$y_name = (string) self::safe_fix_invalid_filesys_names((string)$y_name);
		if((string)$y_name == '') {
			return (string) self::UNDEF_VAR_NAME;
		} //end if
		//--
		return (string) $y_name; // this may not be empty, mandatory at least: UNDEF_VAR_NAME
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a Slug (URL safe slug) from a string
	 *
	 * @param STRING 		$y_str			:: The string to be processed
	 * @param BOOLEAN 		$y_lowercase 	:: *OPTIONAL* If TRUE will return the slug with enforced lowercase characters ; DEFAULT is FALSE
	 * @param INTEGER+ 		$y_maxlen 		:: *OPTIONAL* If a positive value greater than zero is supplied here the slug max length will be constrained to this value
	 *
	 * @return STRING 						:: The slug which will contain only: a-z 0-9 _ - (A-Z will be converted to a-z if lowercase is enforced)
	 */
	public static function create_slug(?string $y_str, bool $y_lowercase=false, ?int $y_maxlen=0) : string {
		//--
		$y_str = (string) SmartUnicode::deaccent_str((string)trim((string)$y_str));
		$y_str = (string) preg_replace('/[^a-zA-Z0-9_\-]/', '-', (string)$y_str);
		$y_str = (string) trim((string)preg_replace('/[\-]+/', '-', (string)$y_str), '-'); // suppress multiple -
		//--
		if($y_lowercase === true) {
			$y_str = (string) strtolower((string)$y_str);
		} //end if
		//--
		if((int)$y_maxlen > 0) {
			if((int)strlen((string)$y_str) > (int)$y_maxlen) {
				$y_str = (string) substr((string)$y_str, 0, (int)$y_maxlen);
				$y_str = (string) rtrim((string)$y_str, '-');
			} //end if
		} //end if
		//--
		return (string) $y_str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a compliant HTML-ID (HTML ID used for HTML elements) from a string
	 *
	 * @param STRING 		$y_str			:: The string to be processed
	 *
	 * @return STRING 						:: The HTML-ID which will contain only: a-z A-Z 0-9 _ -
	 */
	public static function create_htmid(?string $y_str) : string {
		//--
		return (string) trim((string)preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$y_str));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a compliant Js-Var (JavaScript Variable Name) from a string
	 *
	 * @param STRING 		$y_str			:: The string to be processed
	 *
	 * @return STRING 						:: The Js-Var which will contain only: a-z A-Z 0-9 _ $
	 */
	public static function create_jsvar(?string $y_str) : string {
		//--
		return (string) trim((string)preg_replace('/[^a-zA-Z0-9_\$]/', '', (string)$y_str));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Normalize Spaces
	 * This will replace: "\r", "\n", "\t", "\v" = "\x0B", "\0" = "\x00", "\f" = \x0C, "\b", "\a" with normal space ' '
	 *
	 * @param STRING 		$y_txt			:: Text to be normalized
	 *
	 * @return STRING 						:: The normalized text
	 */
	public static function normalize_spaces(?string $y_txt) : string {
		//-- {{{SYNC-NORMALIZE-SPACES}}}
	//	return (string) str_replace(["\r\n", "\r", "\n", "\t", "\v", "\0", "\f", "\b", "\a"], ' ', (string)$y_txt);
		return (string) str_replace(["\r\n", "\r", "\n", "\t", "\v", chr(0), "\f", chr(8), chr(7)], ' ', (string)$y_txt); // bug fix: \b \0 \a will not work in PHP as in other languages (ex: Golang) ...
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an integer random number between min and max using mt_rand() which is4x times faster than rand().
	 * It may use a random seed based on microtime or custom using mt_srand() which uses MT_RAND_MT19937 for PHP >= 7.1
	 * NOTICE: using a time based seed may result in most of the calls to a random number may return the same number which perhaps is not what is expected !!
	 * The min is zero. The max is limited to 2147483647 on most of the platforms.
	 *
	 * @return INTEGER 						:: An integer random number
	 */
	public static function random_number(?int $y_min=0, ?int $y_max=-1, bool $y_seed=false) : int {
		//-- PHP 8.1 Fix to avoid deprecation notice float to int conversion
		$y_min = (int) $y_min;
		$y_max = (int) $y_max;
		//-- seed the mt_rand() using mt_srand()
		if($y_seed !== false) {
			if($y_seed === true) {
				$y_seed = (int) (microtime(true) * 10000);
			} //end if
			mt_srand((int)$y_seed);
		} //end if
		//-- the mt_rand() is 4x times faster than rand() ; but the max is limited to 2147483647 on most of the platforms
		if((int)$y_min < 0) {
			$y_min = 0;
		} //end if
		if((int)$y_max < 0) {
			$y_max = mt_getrandmax();
		} //end if
		if($y_min > $y_max) {
			$y_min = $y_max;
		} //end if
		//--
		return (int) mt_rand((int)$y_min, (int)$y_max);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Converts from Base64 to Base64s / Base64u (Base64 Safe URL / Base64 URL) by replacing characters as follows:
	 * '+' with '-',
	 * '/' with '_',
	 * '=' with '.' (if padding) or '' (if no padding)
	 *
	 * @param STRING 	$str 				:: The Base64 string to be converted
	 * @param BOOL 		$pad 				:: Use padding ; Default is TRUE ; if set to FALSE will not use padding (`=`:`` instead of `=`:`.`)
	 * @return STRING 						:: The Base64s or Base64u (if no padding) encoded string
	 */
	public static function b64_to_b64s(?string $str, bool $pad=true) : string {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$repl = (array) self::CHDIFF_BASE_64s;
		if($pad === false) {
			$repl = (array) self::CHDIFF_BASE_64u;
		} //end if
		//--
		return (string) strtr((string)$str, (array)$repl);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Converts from Base64s / Base64u (Base64 Safe URL / Base64 URL) to Base64 by replacing characters as follows:
	 * '-' with '+',
	 * '_' with '/',
	 * '.' with '=' (optional, if padded, otherwise ignore)
	 *
	 * @param STRING 	$str 				:: The Base64s or Base64u (if no padding) string to be converted
	 * @return STRING 						:: The Base64 string
	 */
	public static function b64s_to_b64(?string $str) : string {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		return (string) strtr((string)$str, (array)array_flip((array)self::CHDIFF_BASE_64s));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Base64s / Base64u (Base64 Safe URL / Base64 URL) Modified Encoding from a string by replacing the standard base64 encoding as follows:
	 * '+' with '-',
	 * '/' with '_',
	 * '=' with '.' (if padding) or '' (if no padding)
	 *
	 * @param STRING 	$str 				:: The string to be encoded
	 * @param BOOL 		$pad 				:: Use padding ; Default is TRUE ; if set to FALSE will not use padding (`=`:`` instead of `=`:`.`)
	 * @return STRING 						:: The Base64s or Base64u (if no padding) encoded string
	 */
	public static function b64s_enc(?string $str, bool $pad=true) : string {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$repl = (array) self::CHDIFF_BASE_64s;
		if($pad === false) {
			$repl = (array) self::CHDIFF_BASE_64u;
		} //end if
		//--
		return (string) strtr((string)self::b64_enc((string)$str), (array)$repl);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Decoded string from Base64s / Base64u (Base64 Safe URL / Base64 URL) Encoding by replacing back as follows before applying the standard base64 decoding:
	 * '-' with '+',
	 * '_' with '/',
	 * '.' with '=' (optional, if padded, otherwise ignore)
	 *
	 * @param STRING 	$str 				:: The Base64s or Base64u (if no padding) encoded string
	 * @param BOOL 		$strict 			:: Strict mode: default is FALSE ; set to TRUE for strict decoding (may return null for invalid characters) ; FALSE means non-strict (if the input contains character from outside the base64 alphabet will be silently discarded)
	 * @return STRING 						:: The decoded string
	 */
	public static function b64s_dec(?string $str, bool $strict=false) : string {
		//--
		$str = (string) trim((string)$str);
		if((string)$str == '') {
			return '';
		} //end if
		//--
		return (string) self::b64_dec((string)strtr((string)$str, (array)array_flip((array)self::CHDIFF_BASE_64s)), (bool)$strict);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Safet Decoded string from Base64 ; Will Fix the missing padding
	 *
	 * @param STRING 	$str 				:: The Base64 encoded string
	 * @param BOOL 		$strict 			:: Strict mode: default is FALSE ; set to TRUE for strict decoding (may return null for invalid characters) ; FALSE means non-strict (if the input contains character from outside the base64 alphabet will be silently discarded)
	 * @return STRING | NULL 				:: The decoded string ; if base64_decode() will return FALSE, it will return NULL, otherwise will return STRING
	 */
	public static function b64_dec(?string $str, bool $strict=false) : ?string {
		//--
		$str = (string) trim((string)$str);
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$l = (int) ((int)strlen((string)$str) % 4);
		if((int)$l > 0) {
			if((int)$l == 1) { // {{{SYNC-B64-WRONG-PAD-3}}} ; it cannot end with 3 "=" signs ; every 4 characters of base64 encoded string represent exactly 3 bytes, because byte contains 8 bits (2^8), and 64 = 2^6 ; so 4 characters of base-64 encoding can hold up to 2^6 * 2^6 * 2^6 * 2^6 bits, which is exactly 2^8 * 2^8 * 2^8 = 3 bytes
				self::log_notice(__METHOD__.' # Invalid B64 Padding Length, more than 2, L = '.(int)$l);
				return null;
			} //end if
			$str .= (string) str_repeat('=', (int)(4 - (int)$l)); // fix missing padding
		} //end if
		//--
		$str = base64_decode((string)$str, (bool)$strict); // returns: string | false
		if($str === false) {
			self::log_notice(__METHOD__.' # Invalid B64 Content');
			return null;
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Base64 Encoded from a string
	 *
	 * @param STRING 	$str 				:: The string to be encoded
	 * @return STRING 						:: The Base64s or Base64u (if no padding) encoded string
	 */
	public static function b64_enc(?string $str) : string {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		return (string) base64_encode((string)$str);
		//--
	} //END FUNCTION
	//================================================================


	//==============================================================
	/**
	 * Perform the rot13 transform on a string
	 * ! This is not encryption ; It is just simply obfuscate !
	 * When combined with encryption, this can be very powerful ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param $str 				The string ; must be ISO-8859-1 character set only !
	 * @return STRING 			The modified string
	 */
	public static function dataRot13(string $str) {
		//--
		return (string) str_rot13((string)$str);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	/**
	 * Perform the rot13 + reverse transform on a string
	 * ! This is not encryption ; It is just simply obfuscate !
	 * When combined with encryption, this can be very powerful ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param $str 				The string ; must be ISO-8859-1 character set only !
	 * @return STRING 			The modified string
	 */
	public static function dataRRot13(string $str) {
		//--
		return (string) strrev((string)self::dataRot13((string)$str));
		//--
	} //END FUNCTION
	//==============================================================


	//================================================================
	/**
	 * Returns the Safe Decoded string from a HEX String
	 * It will validate the string to have an EVEN length not ODD, as required by hex2bin ; if string length is ODD or invalid HEX, a notice will be logged
	 *
	 * Hint: to avoid warnings from hex2bin use this method instead, on invalid input will log just notices
	 *
	 * @param STRING 	$hexstr 			:: The Bin2Hex (HEX) encoded string
	 * @param BOOL 		$ignore_case 		:: Default is TRUE (hex2bin default behaviour, accept both lower/upper case letters and numbers) ; if set to FALSE will accept just lowercase letters and numbers
	 * @param BOOL 		$log_if_invalid 	:: Default is TRUE (hex2bin default behaviour, except that will log notice not warning) ; if set to FALSE will not log at all (useful for input that comes from untrusted sources, ex: URL)
	 * @return STRING 						:: The decoded string or empty string on failure
	 */
	public static function safe_hex_2_bin(?string $hexstr, bool $ignore_case=true, bool $log_if_invalid=true) : string {
		//--
		$hexstr = (string) trim((string)$hexstr);
		if((string)$hexstr == '') {
			return '';
		} //end if
		//-- 1st check to be even length, not odd ; hex2bin requires this to be valid, otherwise will return empty string and exit with a warning (prevent warning)
		$len = (int) strlen((string)$hexstr);
		if((int)($len % 2) !== 0) {
			if($log_if_invalid !== false) {
				self::log_notice(__METHOD__.' # Invalid Input, IS ODD (Length=`'.(int)$len.'`), and must be EVEN: `'.$hexstr.'`');
			} //end if
			return '';
		} //end if
		//-- 2nd handle the case ignore option
		if($ignore_case !== false) {
			if(!\preg_match((string)self::REGEX_SAFE_HEX_STR, (string)$hexstr)) { // pre-check, case sensitive, to avoid make all lower if is already lower
				$hexstr = (string) strtolower((string)$hexstr); // default behaviour of hex2bin is to ignore the case
			} //end if
		} //end if
		//-- 3rd check if all characters matches B16 lower (ignore case option was handled above)
		if((int)strlen((string)$hexstr) !== (int)strspn((string)$hexstr, (string)self::CHARSET_BASE_16)) { // check, case sensitive ; if ignore case was set to TRUE the hex str must be previous converted to all lower characters
			if($log_if_invalid !== false) {
				self::log_notice(__METHOD__.' # Invalid Input, NOT HEX: `'.$hexstr.'`');
			} //end if
			return '';
		} //end if
		//--
		return (string) hex2bin((string)$hexstr);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// based on idea by: https://github.com/tuupola/base62 # License MIT
	private static function base_asciihex_convert(array $source, int $sourceBase, int $targetBase) : array {
		//--
		$result = [];
		//--
		while($count = count($source)) {
			$quotient = [];
			$remainder = 0;
			for($i = 0; $i !== $count; $i++) {
				$accumulator = $source[$i] + $remainder * $sourceBase;
				//--
				$digit = intdiv($accumulator, $targetBase); // PHP 7+
			//	$digit = ($accumulator - ($accumulator % $targetBase)) / $targetBase; // PHP 5.6-
				//--
				$remainder = $accumulator % $targetBase;
				if(count($quotient) || $digit) {
					$quotient[] = $digit;
				} //end if
			} //end for
			array_unshift($result, $remainder);
			$source = $quotient;
		} //end while
		$source = null;
		//--
		return (array) $result;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// based on idea by: https://github.com/tuupola/base62 # License MIT
	/**
	 * Safe convert to hex from any of the following bases: 32, 36, 58, 62, 85, 92
	 * In case of error will return an empty string.
	 *
	 * @param STRING 		$encoded			:: A string (baseXX encoded) that was previous encoded using Smart::base_from_hex_convert()
	 * @param INTEGER 		$currentBase		:: The base to convert ; Available source base: 32, 36, 58, 62, 85, 92
	 * @param STRING 		$charset 			:: *OPTIONAL* an alternate charset if non-empty ; if empty (default) the built-in charset will be used
	 * @return STRING 							:: The decoded string (as hex) from the selected base or empty string on error
	 */
	public static function base_to_hex_convert(string $encoded, int $currentBase, string $charset='') : string {
		//--
		$encoded = (string) trim((string)$encoded);
		if((string)$encoded == '') {
			self::log_notice(__METHOD__.' # Empty Input');
			return '';
		} //end if
		//--
		$currentBase = (int) $currentBase;
		$baseCharset = '';
		if((string)trim((string)$charset) != '') {
			$baseCharset = (string) $charset;
		} else {
			switch((int)$currentBase) { // {{{SYNC-SMART-BASE-CONV-INTERNAL-CHARSETS}}}
				case 32:
					$baseCharset = (string) self::CHARSET_BASE_32;
					break;
				case 36:
					$baseCharset = (string) self::CHARSET_BASE_36;
					break;
				case 58:
					$baseCharset = (string) self::CHARSET_BASE_58;
					break;
				case 62:
					$baseCharset = (string) self::CHARSET_BASE_62;
					break;
				case 85:
					$baseCharset = (string) self::CHARSET_BASE_85;
					break;
				case 92:
					$baseCharset = (string) self::CHARSET_BASE_92;
					break;
				default:
					break;
			} //end switch
		} //end if
		$baseCharset = (string) trim((string)$baseCharset);
		if((string)$baseCharset == '') {
			self::log_warning(__METHOD__.' # Invalid Current Base: `'.(int)$currentBase.'`');
			return '';
		} //end if
		//--
		if((int)strlen((string)$encoded) !== (int)strspn((string)$encoded, (string)$baseCharset)) {
			self::log_notice(__METHOD__.' # Invalid Input, NOT in Current Base ['.(int)$currentBase.']: `'.$encoded.'`');
			return '';
		} //end if
		//--
		$data = (array) str_split((string)$encoded, 1);
		$encoded = null;
		$data = (array) array_map(function($character) use($baseCharset) {
			return strpos($baseCharset, $character); // do not cast, may return FALSE instead of INT
		}, $data);
		//--
		$leadingZeroes = 0;
		while(!empty($data) && (0 === ($data[0] ?? null))) {
			$leadingZeroes++;
			array_shift($data);
		} //end while
		//--
		$converted = (array) self::base_asciihex_convert((array)$data, (int)$currentBase, 256);
		$data = null;
		//--
		if(0 < (int)$leadingZeroes) {
			$converted = (array) array_merge(
				(array) array_fill(0, $leadingZeroes, 0),
				(array) $converted
			);
		} //end if
		//--
		$converted = (string) implode('', (array)array_map('chr', (array)$converted));
		//--
		return (string) bin2hex((string)$converted);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// based on idea by: https://github.com/tuupola/base62 # License MIT
	/**
	 * Safe convert from hex to any of the following bases: 32, 36, 58, 62, 85, 92
	 * In case of error will return an empty string.
	 *
	 * @param STRING 		$hexstr				:: A hexadecimal string (base16) ; can be from bin2hex($string) or from dechex($integer) but in the case of using dechex must use also left padding with zeros to have an even length of the hex data
	 * @param INTEGER 		$targetBase			:: The base to convert ; Available target base: 32, 36, 58, 62, 85, 92
	 * @param STRING 		$charset 			:: *OPTIONAL* an alternate charset if non-empty ; if empty (default) the built-in charset will be used
	 * @return STRING 							:: The encoded string in the selected base or empty string on error
	 */
	public static function base_from_hex_convert(string $hexstr, int $targetBase, string $charset='') : string {
		//--
		$hexstr = (string) trim((string)$hexstr); // req. hex to allow converting also integer values not only strings as bin2hex($string) ; passing an integer can be done using dechex($integer) will use a different compression, making a shorter converted string ; Ex: bin2hex('2') = 3132 / dec2hex(2) = c !!
		if((string)$hexstr == '') {
			self::log_notice(__METHOD__.' # Empty Input');
			return '';
		} //end if
		//--
		$targetBase = (int) $targetBase;
		$baseCharset = '';
		if((string)trim((string)$charset) != '') {
			$baseCharset = (string) $charset;
		} else {
			switch((int)$targetBase) { // {{{SYNC-SMART-BASE-CONV-INTERNAL-CHARSETS}}}
				case 32:
					$baseCharset = (string) self::CHARSET_BASE_32;
					break;
				case 36:
					$baseCharset = (string) self::CHARSET_BASE_36;
					break;
				case 58:
					$baseCharset = (string) self::CHARSET_BASE_58;
					break;
				case 62:
					$baseCharset = (string) self::CHARSET_BASE_62;
					break;
				case 85:
					$baseCharset = (string) self::CHARSET_BASE_85;
					break;
				case 92:
					$baseCharset = (string) self::CHARSET_BASE_92;
					break;
				default:
					break;
			} //end switch
		} //end if
		$baseCharset = (string) trim((string)$baseCharset);
		if((string)$baseCharset == '') {
			self::log_warning(__METHOD__.' # Invalid Target Base: `'.(int)$targetBase.'`');
			return '';
		} //end if
		//--
		$source = (string) self::safe_hex_2_bin((string)$hexstr, false, false); // do not ignore case ; ignore logging ; if invalid will log below !
		if((string)$source == '') {
			self::log_notice(__METHOD__.' # Invalid HEX Input: `'.$hexstr.'`');
			return '';
		} //end if
		$hexstr = null; // free mem
		$source = (array) array_map('ord', (array)str_split((string)$source, 1)); // map hex (16) to ascii (256)
		//--
		$leadingZeroes = 0;
		while(!empty($source) && (0 === $source[0])) {
			$leadingZeroes++;
			array_shift($source); // trim off leading zeroes
		} //end while
		//--
		$result = (array) self::base_asciihex_convert((array)$source, 256, (int)$targetBase);
		$source = null;
		//--
		if(0 < (int)$leadingZeroes) {
			$result = (array) array_merge(
				(array) array_fill(0, (int)$leadingZeroes, 0),
				(array) $result
			);
		} //end if
		//--
		return (string) implode('', (array)array_map(function($val) use($baseCharset) {
			return (string) $baseCharset[$val];
		}, (array)$result));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/* Converts hex (string) to 64-bit integer number
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param INTEGER+ $num
	 * @return STRING
	 */
	 public static function hex_to_int10(?string $hex) : int {
		//--
		$hex = (string) strtolower((string)trim((string)$hex));
		//-- standardize for comparing
		if(strpos((string)$hex, '0x') === 0) {
			$hex = (string) substr((string)$hex, 2);
		} //end if
		//-- add prefix
		$hex = (string) '0x'.trim((string)ltrim((string)$hex, '0'));
		//-- compare with max supported hex on this platform to avoid overflows
		$maxhex = (string) '0x'.trim((string)ltrim((string)self::int10_to_hex((int)PHP_INT_MAX), '0'));
		if(((int)strlen((string)$hex) > (int)strlen((string)$maxhex)) OR ((string)$hex > (string)$maxhex)) {
			self::log_warning(__METHOD__.' # HEX `'.$hex.'` is too large for this platform. Max HEX is: `'.$maxhex.'` as :`'.PHP_INT_MAX.'`');
			$hex = (string) $maxhex;
		} //end if
		//--
		return (int) hexdec((string)$hex);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/* Converts a 64-bit integer number to hex (string)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param INTEGER+ $num
	 * @return STRING
	 */
	 public static function int10_to_hex(?int $num) : string {
		//--
		$num = (int) $num;
		if($num < 0) {
			$num = 0;
		} //end if
		//--
		$hex = (string) dechex((int)$num);
		$len = (int) strlen((string)$hex);
		if((int)$len <= 0) {
			$hex = '00'; // this should not happen but anyway, it have to be fixed just in the case
		} elseif(((int)$len % 2) != 0) {
			$hex = '0'.$hex; // even zeros padding
		} //end if
		//--
		return (string) $hex;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/* Converts a 64-bit integer number to base62 (string)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param INTEGER+ $num
	 * @return STRING
	 */
	public static function int10_to_base62_str(?int $num) : string {
		//--
		$num = (int) $num;
		if($num < 0) {
			$num = 0;
		} //end if
		//--
		// METHOD UPGRADED ON 2021.08.12 AS THIS IS SAFER ON ALL 64-bit PLATFORMS
		// THE OLD METHOD IS AVAILABLE ONLY FOR TESTING IN: modules/mod-samples/libs/TestUnitCrypto.php
		//--
		$hex = (string) self::int10_to_hex((int)$num);
		//--
		return (string) self::base_from_hex_convert((string)$hex, 62);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the valid Net Server ID (to be used in a cluster)
	 * Valid values are 0..1295 (or 00..ZZ if BASE36)
	 */
	public static function net_server_id(bool $base36=false) : string { // {{{SYNC-MIN-MAX-NETSERVER-ID}}}
		//--
		$netserverid = (int) 0;
		if(defined('SMART_FRAMEWORK_NETSERVER_ID')) {
			$netserverid = (int) SMART_FRAMEWORK_NETSERVER_ID;
		} //end if
		//--
		if($netserverid < 0) {
			$netserverid = 0;
		} elseif($netserverid > 1295) {
			$netserverid = 1295;
		} //end if else
		//--
		if($base36 === true) {
			$netserverid = (string) strtoupper((string)sprintf('%02s', (string)base_convert((string)$netserverid, 10, 36))); // 00..ZZ
		} //end if
		//--
		return (string) $netserverid; // return int as string
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates a time based entropy as replacement for uniqid() to ensure is unique in time and space.
	 * It is based on a full unique signature in space and time: server name, server unique id, a unique time sequence that repeats once in 1000 years and 2 extremely large (fixed length) random values .
	 * If a suffix is provided will append it.
	 *
	 * @return STRING 						:: variable length Unique Entropy string
	 */
	public static function unique_entropy(?string $y_suffix='', bool $y_use_net_server_id=true) : string {
		//--
		$netserverid = '';
		if($y_use_net_server_id !== false) {
			$netserverid = (string) self::net_server_id();
		} //end if
		//--
		$namespace = '';
		//--
		if(defined('SMART_SOFTWARE_NAMESPACE')) {
			$namespace .= (string) ' `'.trim((string)SMART_SOFTWARE_NAMESPACE).'` ';
		} //end if
		if(defined('SMART_FRAMEWORK_SECURITY_KEY')) {
			$namespace .= (string) ' `'.trim((string)SMART_FRAMEWORK_SECURITY_KEY).'` ';
		} //end if
		//--
		$namespace = (string) trim((string)$namespace);
		//--
		if((string)$namespace == '') {
			$namespace = (string) __METHOD__.'#'.microtime();
		} //end if
		//--
		return (string) 'Namespace:'.$namespace.'NetServer#'.$netserverid.'UUIDUSequence='.self::uuid_13_seq().';UUIDSequence='.self::uuid_10_seq().';UUIDRandStr='.self::uuid_10_str().';UUIDRandNum='.self::uuid_10_num().';'.$y_suffix;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Small scale.
	 * Generates a random, almost unique numeric UUID of 10 characters [0..9] ; Example: 5457229400 .
	 * For the same time moment, duplicate values can happen with a chance of 1 in a 9 million.
	 * Min is: 0000000001 ; Max id: 9999999999 .
	 * Values: 9999999998 .
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_10_num() : string {
		//--
		$uid = '';
		for($i=0; $i<9; $i++) {
			$rand = (string) self::random_number(0,9);
			$uid .= (string) $rand;
		} //end for
		$rand = (string) self::random_number(1,9);
		$uid .= (string) $rand;
		//--
		return (string) $uid;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Medium scale.
	 * Generates a random, almost unique string (base36) UUID of 10 characters [0..9A..Z] ; Example: Z4C9S6F1H1 .
	 * For the same time moment, duplicate values can occur with a chance of ~ 1 in a 3000 trillion.
	 * Min is: 0A0A0A0A0A (28232883707050) ; Max id: Z9Z9Z9Z9Z9 (3582752942424645) .
	 * Values YZYZYZYZYZ (3554520058717595) .
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_10_str() : string {
		//--
		$toggle = self::random_number(0,1);
		//--
		$uid = '';
		for($i=0; $i<10; $i++) {
			if(($i % 2) == $toggle) {
				$rand = (string) self::random_number(0,9);
			} else { // alternate nums with chars (this avoid to have ugly words)
				$rand = (string) self::random_number(10,35);
			} //end if else
			$uid .= (string) base_convert((string)$rand, 10, 36);
		} //end for
		//--
		return (string) strtoupper((string)$uid);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Large scale.
	 * Generates a random string (base36) UUID of 10 characters [0..9A..Z] ; Example: 0G1G74W362 .
	 * Intended usage: Medium scale / Sequential / Non-Repeating (never repeats in a period cycle of 1000 years).
	 * This is sequential, date and time based with miliseconds and a randomizer factor to ensure an ~ unique ID.
	 * Duplicate values can occur just in the same milisecond (1000 miliseconds = 1 second) with a chance of ~ 3%
	 * Values: 34 k / sec ; 200 k / min ; 120 mil / hour .
	 *
	 * Advantages: This is one of the most powerful UUID system for medium scale as the ID will never repeat in a large period of time.
	 * Compared with the classic autoincremental IDs this UUID is much better as on the next cycle can fill up unallocated
	 * values and more, because the next cycle occur after so many time there is no risk to re-use some IDs if they were
	 * previous deleted or deactivated in terms of generating confusions with previous records.
	 * The classic autoincremental systems can NOT do this and also, once the max ID is reached the DB table is blocked
	 * as autoincremental records reach the max ID !!!
	 *
	 * Disadvantages: The database connectors require more complexity and must be able to retry within a cycle with
	 * double check before alocating, such UUIDs and must use a pre-alocation table since the UUIDs are time based and if
	 * in the same milisecond more than 1 inserts is allocated they can conflict each other without such pre-alocation !
	 *
	 * Smart.Framework implements the retry + cycle + pre-alocating table as a standard feature
	 * in the bundled PostgreSQL library (connector/plugin) as PostgreSQL can do DDLs in transactions.
	 * Using such functionality with MySQL would be tricky as DDLs will break the transactions, but still usable ;-).
	 * And for SQLite it does not make sense since SQLite is designed for small DBs thus no need for such high scalability ...
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_10_seq() : string { // v7
		//-- 00 .. RR
		$b10_thousands_year = (int) substr((string)date('Y'), -3, 3); // get last 3 digits from year 000 .. 999
		$b36_thousands_year = (string) sprintf('%02s', (string)base_convert((string)$b10_thousands_year, 10, 36));
		//-- 00000 .. ITRPU
		$b10_day_of_year = (int) (date('z') + 1); // 1 .. 366
		$b10_second_of_day = (int) ((((int)date('H')) * 60 * 60) + ((int)date('i') * 60) + ((int)date('s'))); // 0 .. 86399
		$b10_second_of_year = (int) ($b10_day_of_year * $b10_second_of_day); // 0 .. 31622399
		$b36_second_of_year = (string) sprintf('%05s', (string)base_convert((string)$b10_second_of_year, 10, 36));
		//-- 00 .. RR
		$microtime = (array) explode('.', (string)microtime(true));
		$b10_microseconds = (int) substr((string)trim((string)($microtime[1] ?? null)), 0, 3); // 0 .. 999
		$b36_microseconds = (string) sprintf('%02s', (string)base_convert((string)$b10_microseconds, 10, 36));
		//-- 1 .. Z
		$rand = (string) self::random_number(1, 35); // trick: avoid 0000000000
		$b36_randomizer = (string) sprintf('%01s', (string)base_convert((string)$rand, 10, 36));
		//--
		$uid = (string) trim((string)$b36_thousands_year.$b36_second_of_year.$b36_microseconds.$b36_randomizer);
		//--
		return (string) strtoupper((string)$uid);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Large scale, in a cluster.
	 * Generates a random string (base62) UUID of 12 characters [0..9a..zA..Z] ; Example: 0K4M6V04JM01 .
	 * It is based on Smart::uuid_10_seq() but will append the last two characters in base36 00..ZZ using Smart::net_server_id(true) that represent the Net Server ID in a cluster.
	 * To set the Net Server ID as unique per each running instance of Smart.Framework under the same domain,
	 * set the constant SMART_FRAMEWORK_NETSERVER_ID in etc/init.php with a number between 0..1295 to have a unique number for each instance of Smart.Framework
	 * where supposed all this instances will run in a cluster.
	 * If there is only one instance running and no plans at all to implement a multi-server cluster, makes non-sense to use this function, use instead the Smart::uuid_10_seq()
	 * For how is implemented, read the documentation for the functions: Smart::uuid_10_seq() and Smart::net_server_id(true)
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_12_seq() : string { // v7
		//--
		return (string) self::uuid_10_seq().self::net_server_id(true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Very Large scale. Case sensitive.
	 * Generates a random string (base62) UUID of 13 characters [0..9a..zA..Z] ; Example: 00wA0whhw2e9L .
	 * Intended usage: Very Large scale / Sequential / Non-Repeating (never repeats in a period cycle of 9999999 years).
	 * This is sequential, date and time based with miliseconds and a randomizer factor to ensure an ~ unique ID.
	 * Duplicate values can occur just in the same milisecond (1000 miliseconds = 1 second) with a chance of ~ 0.3%
	 * Values: 340000 k / sec ; 2000000 k / min ; 1200000 mil / hour .
	 *
	 * Advantages: This is one of the most powerful UUID system for large scale as the ID will never repeat in a huge period of time.
	 * Compared with the classic autoincremental IDs this UUID is much better as on the next cycle can fill up unallocated
	 * values and more, because the next cycle occur after so many time there is no risk to re-use some IDs if they were
	 * previous deleted or deactivated in terms of generating confusions with previous records.
	 * The classic autoincremental systems can NOT do this and also, once the max ID is reached the DB table is blocked
	 * as autoincremental records reach the max ID !!!
	 *
	 * Disadvantages: The database connectors require more complexity and must be able to retry within a cycle with
	 * double check before alocating, such UUIDs and must use a pre-alocation table since the UUIDs are time based and if
	 * in the same milisecond more than 1 inserts is allocated they can conflict each other without such pre-alocation !
	 *
	 * Smart.Framework implements the retry + cycle + pre-alocating table as a standard feature
	 * in the bundled PostgreSQL library (connector/plugin) as PostgreSQL can do DDLs in transactions.
	 * Using such functionality with MySQL would be tricky as DDLs will break the transactions, but still usable ;-).
	 * And for SQLite it does not make sense since SQLite is designed for small DBs thus no need for such high scalability ...
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_13_seq() : string { // v1
		//-- YEAR: 0 .. 9999999 in base62 is 0000 .. FXsj
		$b10_10milion_year = (int) substr((string)date('Y'), -7, 7); // get last 7 digits of year
		$b62_10milion_year = (string) sprintf('%04s', self::int10_to_base62_str($b10_10milion_year));
		//-- SECOND OF YEAR: 0 .. 31622399 in base62 is 00000 .. 28GqH
		$b10_day_of_year = (int) (date('z') + 1); // 1 .. 366
		$b10_second_of_day = (int) ((((int)date('H')) * 60 * 60) + ((int)date('i') * 60) + ((int)date('s'))); // 0 .. 86399
		$b10_second_of_year = (int) ($b10_day_of_year * $b10_second_of_day); // 0 .. 31622399
		$b62_second_of_year = (string) sprintf('%05s', self::int10_to_base62_str($b10_second_of_year));
		//-- MICROSECOND: 0 .. 9999999 in base62 is 0000 .. FXsj
		$microtime = (array) explode('.', (string)microtime(true));
		$b10_microseconds = (string) sprintf('%04s', (int)substr((string)trim((string)($microtime[1] ?? null)), 0, 4)); // 0000 .. 9999
		$rand = self::random_number(1, 999); // trick: avoid 0000000000000
		$b10_randomizer = (string) sprintf('%03s', $rand);
		$b10_microseconds .= $b10_randomizer; // append 0000 .. 9999 with 3 more digits 000 .. 999
		$b62_microseconds = (string) sprintf('%04s', self::int10_to_base62_str((int)$b10_microseconds));
		//--
		return (string) $b62_10milion_year.$b62_second_of_year.$b62_microseconds;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Intended usage: Very Large scale, in a cluster. Case sensitive.
	 * Generates a random string (base62) UUID of 15 characters [0..9a..zA..Z] ; Example: 00wA0whhw2e9L01 .
	 * It is based on Smart::uuid_13_seq() but will append the last two characters in base36 00..ZZ using Smart::net_server_id(true) that represent the Net Server ID in a cluster.
	 * To set the Net Server ID as unique per each running instance of Smart.Framework under the same domain,
	 * set the constant SMART_FRAMEWORK_NETSERVER_ID in etc/init.php with a number between 0..1295 to have a unique number for each instance of Smart.Framework
	 * where supposed all this instances will run in a cluster.
	 * If there is only one instance running and no plans at all to implement a multi-server cluster, makes non-sense to use this function, use instead the Smart::uuid_13_seq()
	 * For how is implemented, read the documentation for the functions: Smart::uuid_13_seq() and Smart::net_server_id(true)
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_15_seq() : string { // v1
		//--
		return (string) self::uuid_13_seq().self::net_server_id(true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique BASE36 based UUID of 32 characters [0..9A..Z] ; Example: Y123AY7WK5-9187139702-Z98W7T091K .
	 * This compose as: Smart::uuid_10_seq().'-'.Smart::uuid_10_num().'-'.Smart::uuid_10_str()
	 * Intended usage: Very Large scale.
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_32() : string {
		//--
		return (string) self::uuid_10_seq().'-'.self::uuid_10_num().'-'.self::uuid_10_str();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique BASE36 based UUID of 34 characters [0..9A..Z] ; Example: Y123AY7WK501-9187139702-Z98W7T091K .
	 * This compose as: Smart::uuid_12_seq().'-'.Smart::uuid_10_num().'-'.Smart::uuid_10_str()
	 * Intended usage: Very Large scale, in a cluster.
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_34() : string {
		//--
		return (string) self::uuid_12_seq().'-'.self::uuid_10_num().'-'.self::uuid_10_str();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique BASE62 + BASE36 based UUID of 35 characters [0..9a..zA..Z] ; Example: 00wA0whhw2e9L-9187139702-Z98W7T091K .
	 * This compose as: Smart::uuid_13_seq().'-'.Smart::uuid_10_num().'-'.Smart::uuid_10_str()
	 * Intended usage: Extremely Large scale. Case sensitive.
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_35() : string {
		//--
		return (string) self::uuid_13_seq().'-'.self::uuid_10_num().'-'.self::uuid_10_str();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique BASE62 + BASE36 based UUID of 37 characters [0..9a..zA..Z] ; Example: 00wA0whhw2e9L01-9187139702-Z98W7T091K .
	 * This compose as: Smart::uuid_15_seq().'-'.Smart::uuid_10_num().'-'.Smart::uuid_10_str()
	 * Intended usage: Extremely Large scale, in a cluster. Case sensitive.
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_37() : string {
		//--
		return (string) self::uuid_15_seq().'-'.self::uuid_10_num().'-'.self::uuid_10_str();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique MD5 based UUID of 36 characters [0..9a..f] ; Example: cfcb6c2a-a6e0-f539-141d-083abee19a4e .
	 * The uniqueness of this is based on a full unique signature in space and time: 2 random UUIDS, server name, year/day/month hour:minute:seconds, time, microseconds, a random value 0...9999 .
	 * For the same time moment, duplicates values can occur with a chance of 1 in ~ a 340282366920938586008062602462446642046 .
	 * The Net Server ID can be passed via the $prefix parameter
	 * Intended usage: Large scale. Standard.
	 *
	 * @param STRING $prefix 				:: A prefix to use for more uniqueness entropy ; Ex: can use the Smart::net_server_id()
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_36(string $prefix='') : string {
		//--
		$hash = (string) md5((string)$prefix.self::unique_entropy('uid36', false)); // by default use no reference to net server id, which can be passed via prefix
		//--
		$uuid  = (string) substr($hash,0,8).'-';
		$uuid .= (string) substr($hash,8,4).'-';
		$uuid .= (string) substr($hash,12,4).'-';
		$uuid .= (string) substr($hash,16,4).'-';
		$uuid .= (string) substr($hash,20,12);
		//--
		return (string) strtolower((string)$uuid);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates an almost unique SHA1 based UUID of 45 characters [0..9a..f] ; Example: c02acc84-97f4-0807-b12c-ed6f28dd2078-400c1baf .
	 * The uniqueness of this is based on a full unique signature in space and time: 2 random UUIDS, server name, year/day/month hour:minute:seconds, time, microseconds, a random value 0...9999 .
	 * For the same time moment, duplicates values can occur with a chance of 1 in ~ a 1461501637330903466848266086008062602462446642046 .
	 * The Net Server ID can be passed via the $prefix parameter
	 * Intended usage: Large scale. Standard.
	 *
	 * @param STRING $prefix 				:: A prefix to use for more uniqueness entropy ; Ex: can use the Smart::net_server_id()
	 *
	 * @return STRING 						:: the UUID
	 */
	public static function uuid_45(string $prefix='') : string {
		//--
		$hash = (string) sha1((string)$prefix.self::unique_entropy('uid45', false)); // by default use no reference to net server id, which can be passed via prefix
		//--
		$uuid  = (string) substr($hash,0,8).'-';
		$uuid .= (string) substr($hash,8,4).'-';
		$uuid .= (string) substr($hash,12,4).'-';
		$uuid .= (string) substr($hash,16,4).'-';
		$uuid .= (string) substr($hash,20,12);
		$uuid .= (string) '-'.substr($hash,32,8);
		//--
		return (string) strtolower((string)$uuid);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Remove brackets [] from IPv6 domain for using it as IPv6 address
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 	$domipv4orv6 		:: The IPv6 domain as [::ffff:127.0.0.1]
	 * @return STRING 						:: The IPv6 address as ::ffff:127.0.0.1
	 */
	public static function ip_domain_to_ip_addr(?string $domipv4orv6) : string {
		//--
		return (string) strtr((string)$domipv4orv6, [ '[' => '', ']' => '' ]); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-BRACKETS}}}
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Compress an IPv6 address to shortest form, lowercase ; they are also validated
	 * The IPv4 will be returned as they are, they are only validated
	 *
	 * @param STRING 	$ipv4orv6 			:: The IP address ; IPv4 (ex: 127.0.0.1) or IPv6 (ex: ::ffff:127.0.0.1)
	 * @return STRING 						:: The validated (IPV6 also compressed) IP address ; will return an empty string if invalid IP
	 */
	public static function ip_addr_compress(?string $ipv4orv6) : string {
		//--
		// {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
		// For IPv4 it returns exact the same ; for IPv6 it returns as, ex: 2001::6dcd:8c74:0:0:0:0 as 2001:0:6dcd:8c74::
		// This should be used when comparing 2 ip addresses because the IPv6 may be in short or long form, so compressing them both before comparing is the best !
		//--
		$ipv4orv6 = (string) trim((string)$ipv4orv6);
		if((string)$ipv4orv6 == '') {
			return '';
		} //end if
		//--
		return (string) trim((string)inet_ntop((string)inet_pton((string)strtolower((string)$ipv4orv6))));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Compare 2 IP addresses if they are identical
	 * Works for IPv4 / IPv6 (any form, will standardize it using compression)
	 * Will compare them using numeric translation format IP to INTEGER
	 *
	 * @param STRING 	$ip1 				:: The first  IP address to compare
	 * @param STRING 	$ip2 				:: The second IP address to compare
	 * @return BOOL 						:: Will return TRUE if they are identical, valid, non-empty ; FALSE if not identical OR not valid OR empty
	 */
	public static function ip_addr_compare(?string $ip1, ?string $ip2) : bool {
		//--
		// {{{SYNC-IPV6-STORE-SHORTEST-POSSIBLE}}} ; IPV6 addresses may vary .. find a standard form, ex: shortest
		//--
		$ip1 = (string) self::ip_addr_compress((string)$ip1);
		$ip2 = (string) self::ip_addr_compress((string)$ip2);
		//--
		if(((string)$ip1 == '') OR ((string)$ip2 == '')) {
			return false;
		} //end if
		//--
		if((int)ip2long((string)$ip1) !== (int)ip2long((string)$ip2)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check if an IPv4 or IPv6 is in range ; doesn't matter what form IPv6 is, will do numeric validation
	 * Works for IPv4 / IPv6 (any form, will standardize it using compression)
	 * Will compare the range and IP using numeric translation format IP to INTEGER
	 *
	 * @param STRING 	$lower_range_ip_address 	:: The lower range IP
	 * @param STRING 	$upper_range_ip_address 	:: The upper range IP
	 * @return BOOL 								:: Will return TRUE if the IP is in range ; FALSE if not
	 */
	public static function ip_addr_in_range(?string $lower_range_ip_address, ?string $upper_range_ip_address, ?string $needle_ip_address) : bool {
			//-- get the numeric reprisentation of the IP Address with IP2long
			$min 	= ip2long((string)$lower_range_ip_address);
			$max 	= ip2long((string)$upper_range_ip_address);
			$needle = ip2long((string)$needle_ip_address);
			//-- then it is as simple as checking whether the needle falls between the lower and upper ranges
			return (bool) (($needle >= $min) AND ($needle <= $max));
			//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * The Info logger.
	 * This will add messages to the App Info Log. (depending if on admin or index, will output into 'tmp/logs/adm/' or 'tmp/logs/idx/')
	 *
	 * @param STRING 	$title			:: The title of the message to be logged
	 * @param STRING 	$message		:: The message to be logged
	 *
	 * @return -						:: This function does not return anything
	 */
	public static function log_info(?string $title, ?string $message) : void {
		//--
		if((defined('SMART_FRAMEWORK_INFO_LOG')) AND is_string(SMART_FRAMEWORK_INFO_LOG) AND ((string)trim((string)SMART_FRAMEWORK_INFO_LOG) != '') AND (is_dir((string)self::dir_name((string)trim((string)SMART_FRAMEWORK_INFO_LOG))))) { // must use is_dir here to avoid dependency with smart file system lib
			@file_put_contents((string)trim((string)SMART_FRAMEWORK_INFO_LOG), '[INF]'."\t".date('Y-m-d H:i:s O')."\t".self::normalize_spaces($title)."\t".self::normalize_spaces($message)."\n", FILE_APPEND | LOCK_EX);
		} else {
			self::log_notice('INFO-LOG NOT SET :: Logging to Notices ... # Message: '.self::normalize_spaces($title)."\t".self::normalize_spaces($message));
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * A quick replacement for trigger_error() / E_USER_NOTICE.
	 * This is intended to log APP Level Notices.
	 * This will log the message as NOTICE into the App Error Log.
	 * Notices are logged ONLY for Development Environment (and NOT for Production Environment)
	 *
	 * @param STRING 	$message		:: The message to be triggered
	 *
	 * @return -						:: This function does not return anything
	 */
	public static function log_notice(?string $message) : void {
		//--
		if(SmartEnvironment::ifDevMode() === false) {
			return; // use this only in DEV mode
		} //end if
		//--
		trigger_error('#SMART-FRAMEWORK.NOTICE# '.$message, E_USER_NOTICE);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * A quick replacement for trigger_error() / E_USER_WARNING.
	 * This is intended to log APP Level Warnings.
	 * This will log the message as WARNING into the App Error Log.
	 *
	 * @param STRING 	$message		:: The message to be triggered
	 *
	 * @return -						:: This function does not return anything
	 */
	public static function log_warning(?string $message) : void {
		//--
		trigger_error('#SMART-FRAMEWORK.WARNING# '.$message, E_USER_WARNING);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * A quick replacement for trigger_error() / E_USER_ERROR.
	 * Since PHP 8.4 E_USER_ERROR has dissapeared
	 * Fix: E_USER_ERROR was replaced by E_USER_WARNING + a message that must start with: '#SMART-FRAMEWORK.ERROR#' in order to preserve the past behaviour ...
	 * This is intended to log APP Level Errors.
	 * This will log the message as ERROR into the App Error Log and stop the execution (also in the Smart Error Handler will raise a HTTP 500 Code).
	 *
	 * @param STRING 	$message_to_log			:: The message to be triggered
	 * @param STRING 	$message_to_display 	:: *Optional* the message to be displayed (must be html special chars safe)
	 *
	 * @return -								:: This function does not return anything
	 */
	public static function raise_error(?string $message_to_log, ?string $message_to_display='', bool $is_html_message_to_display=false) : void {
		//--
		global $smart_____framework_____last__error;
		global $smart_____framework_____is_html_last__error;
		//--
		if((string)trim((string)$message_to_display) == '') {
			if((string)SMART_ERROR_HANDLER == 'prod') { // if prod mode
				$message_to_display = 'See Error Log for More Details'; // avoid empty message to display
				$is_html_message_to_display = false;
			} else {
				if((string)trim((string)$message_to_display) == '') {
					$message_to_display = (string) $message_to_log;
					$is_html_message_to_display = false;
				} //end if
			} //end if
		} //end if
		$smart_____framework_____last__error = (string) $message_to_display;
		$smart_____framework_____is_html_last__error = (bool) $is_html_message_to_display;
		trigger_error('#SMART-FRAMEWORK.ERROR# '.$message_to_log, E_USER_WARNING); // {{{SF-PHP-EMULATE-E_USER_ERROR}}} ; to emulate the old behaviour of E_USER_ERROR the message must start mandatory with '#SMART-FRAMEWORK.ERROR#'
		die('App Level Raise ERROR. Execution Halted. '.$message_to_display); // normally this line will never be executed because the E_USER_ERROR via Smart Error Handler will die() before ... but this is just in case, as this is a fatal error and the execution should be halted here !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Disable the current Error/Exception Handlers, for temporary use
	 *
	 * @access 		private
	 * @internal
	 */
	public static function disableErrLog() : void {
		//--
		if(SmartEnvironment::ifDebug()) {
			return;
		} //end if
		//--
		if(self::$SemaphoreAreLogHandlersDisabled !== false) {
			return; // AVOID call this method twice without calling restoreErrLog(), a semaphore is used
		} //end if
		//--
		set_exception_handler(function(){return true;});
		set_error_handler(function(){return true;});
		//--
		self::$SemaphoreAreLogHandlersDisabled = true; // semaphore ON
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Restore the previous Error/Exception Handlers, for temporary use
	 *
	 * @access 		private
	 * @internal
	 */
	public static function restoreErrLog() : void {
		//--
		if(SmartEnvironment::ifDebug()) {
			return;
		} //end if
		//--
		if(self::$SemaphoreAreLogHandlersDisabled !== true) {
			return; // AVOID call this method twice without calling disableErrLog(), a semaphore is used
		} //end if
		//--
		restore_error_handler();
		restore_exception_handler();
		//--
		self::$SemaphoreAreLogHandlersDisabled = false; // semaphore OFF
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function InstantFlush() : void {
		//--
		$output_buffering_status = @ob_get_status();
		//-- type: 0 = PHP_OUTPUT_HANDLER_INTERNAL ; 1 = PHP_OUTPUT_HANDLER_USER
		if(is_array($output_buffering_status) AND array_key_exists('type', $output_buffering_status) AND array_key_exists('chunk_size', $output_buffering_status)) {
			if(((string)$output_buffering_status['type'] == '0') AND ((int)$output_buffering_status['chunk_size'] > 0)) { // avoid to break user level output buffering(s), so enable this just for level zero (internal, if set in php.ini)
				@ob_flush();
			} //end if
		} //end if
		//--
		@flush();
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Comment out PHP Code from a string
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING
	 *
	 */
	public static function commentOutPhpCode(?string $y_code, array $y_repl=['tag-start' => '<!--? ', 'tag-end' => ' ?-->']) : string {
		//--
		$y_code = (string) $y_code;
		$y_repl = (array)  $y_repl;
		//--
		$tag_start 	= (string) ($y_repl['tag-start'] ?? '');
		$tag_end 	= (string) ($y_repl['tag-end']   ?? '');
		//--
		$tmp_regex_php = [
			'<'.'?php',
			'<'.'?',
			'?'.'>'
		];
		$tmp_regex_htm = [
			(string) $tag_start,
			(string) $tag_start,
			(string) $tag_end
		];
		//--
		return (string) str_ireplace((array)$tmp_regex_php, (array)$tmp_regex_htm, (string)$y_code);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Regex Expr. with the lower unsafe characters
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 						:: A regex expression
	 *
	 */
	public static function lower_unsafe_characters() : string {
		//--
		return '/[\x00-\x08\x0B-\x0C\x0E-\x1F]/'; // all lower dangerous characters: x00 - x1F except: \t = x09 \n = 0A \r = 0D
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the PHP Class Name from a Namespace Prefixed Class Name
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return STRING 						:: The Class Name
	 *
	 */
	public static function getClassNameWithoutNamespacePrefix(string $class) : string {
		//--
		$class = (string) trim((string)$class);
		//--
		if((string)$class != '') {
			if(strpos((string)$class, '\\') !== false) {
				$arr = (array) explode('\\', (string)$class);
				$class = (string) array_pop($arr);
				$arr = null;
			} //end if
		} //end if
		//--
		if((string)$class != '') {
			if(!SmartFrameworkSecurity::ValidateVariableName((string)$class)) {
				$class = '';
			} //end if
		} //end if
		//--
		if((string)$class != '') {
			return (string) $class;
		} //end if
		//--
		return '_UNDEFINED____CLASS_';
		//--
	} //END FUNCTION
	//================================================================


	//##### DEBUG ONLY


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'Smart (Base) // Internal Cache',
					'data' => 'Dump of Cfgs:'."\n".print_r(self::$Cfgs,1)
				]);
			} //end if
		} //end if
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================



//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartFileSysUtils - provides the File System Util functions.
 *
 * This class enforces the use of RELATIVE PATHS to force using correct path access in a web environment application.
 * Relative paths must be relative to the web application folder as folder: `some-folder/` or file: `some-folder/my-file.txt`.
 * Absolute paths are denied by internal checks as they are NOT SAFE in a Web Application from the security point of view ...
 * Also the backward path access like `../some-file-or-folder` is denied from the above exposed reasons.
 * Files and Folders must contain ONLY safe characters as: `[a-z] [A-Z] [0-9] _ - . @ #` ; folders can also contain slashes `/` (as path separators); no spaces are allowed in paths !!
 *
 * NOTICE: To use paths in a safe manner, never add manually a / at the end of a path variable, because if it is empty will result in accessing the root of the file system (/).
 * To handle this in an easy and safe manner, use the function SmartFileSysUtils::addPathTrailingSlash((string)$my_dir) so it will add the trailing slash ONLY if misses but NOT if the $my_dir is empty to avoid root access !
 *
 * <code>
 *
 * // Usage example:
 * SmartFileSysUtils::some_method_of_this_class(...);
 *
 *  //-----------------------------------------------------------------------------------------------------
 *  // SAFE REPLACEMENTS:
 *  // In order to supply a common framework for Unix / Linux but also on Windows,
 *  // because on Windows dir separator is \ instead of / the following functions must be used as replacements:
 *  //-----------------------------------------------------------------------------------------------------
 *  // Smart::real_path()        instead of:        realpath()
 *  // Smart::dir_name()         instead of:        dirname()
 *  // Smart::path_info()        instead of:        pathinfo()
 *  //-----------------------------------------------------------------------------------------------------
 *  // Also, when folders are get from external environments and are not certified if they have
 *  // been converted from \ to / on Windows, those paths have to be fixed using: Smart::fix_path_separator()
 * 	// To check compliancy may use: checkIfSafeFileOrDirName or checkIfSafePath
 *  //-----------------------------------------------------------------------------------------------------
 *
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartEnvironment
 * @version 	v.20250203
 * @package 	@Core:FileSystem
 *
 */
final class SmartFileSysUtils {

	// ::

	private static $cachedStaticFilePaths = [];


	//================================================================
	/**
	 * FAST CHECK IF A STATIC FILE EXISTS AND IS READABLE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It should be used just with static files which does not changes between executions ; it does not use safe lock checks
	 *
	 * @param 	STRING 		$file_relative_path 				:: The relative path of file to be read (can be a symlink to a file)
	 *
	 * @return 	BOOL											:: TRUE if file exists and path is safe ; FALSE otherwise
	 */
	public static function staticFileExists(?string $file_relative_path) : bool {
		//--
		if((string)trim((string)$file_relative_path) == '') {
			return false;
		} //end if
		//--
		if(!is_array(self::$cachedStaticFilePaths)) {
			self::$cachedStaticFilePaths = [];
		} //end if
		if(array_key_exists((string)$file_relative_path, (array)self::$cachedStaticFilePaths)) {
			self::$cachedStaticFilePaths[(string)$file_relative_path]++; // keep number ou accesses
			return (bool) self::$cachedStaticFilePaths[(string)$file_relative_path];
		} //end if
		//--
		$staticRootPath = (string) self::getStaticFilesRootPath();
		//--
		$ok = true;
		if(
			(self::checkIfSafePath((string)$file_relative_path) != 1)
			OR
			(self::checkIfSafePath((string)$staticRootPath.$file_relative_path) != 1)
		) {
			$ok = false;
		} elseif(!is_file((string)$staticRootPath.$file_relative_path)) { // do not use clearstatcache(), this is intended for STATIC FILES ONLY
			$ok = false;
		} elseif(!is_readable((string)$staticRootPath.$file_relative_path)) { // do not use clearstatcache(), this is intended for STATIC FILES ONLY
			$ok = false;
		} //end if
		//--
		self::$cachedStaticFilePaths[(string)$file_relative_path] = (int) $ok; // 0 or 1 (will increment above, if more than 1 access)
		//--
		return (bool) self::$cachedStaticFilePaths[(string)$file_relative_path];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * FAST READ A STATIC FILE CONTENTS ONLY. ALSO CHECKS IF THE FILE EXISTS AND IS READABLE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It should be used just with static files which does not changes between executions ; it does not use safe lock checks
	 *
	 * @param 	STRING 		$file_relative_path 				:: The relative path of file to be read (can be a symlink to a file)
	 * @param 	INTEGER+	$length 							:: DEFAULT is 0 (zero) ; If zero will read the entire file ; If > 0 (ex: 100) will read only the first 100 bytes fro the file or less if the file size is under 100 bytes
	 * @param 	BOOL 		$dont_read_if_overSized 			:: DEFAULT is FALSE ; if set to TRUE and a Max length is Set the file will not be read if size is more than max length
	 *
	 * @return 	STRING											:: The file contents or an empty string if file not found or cannot read file or other error cases
	 */
	public static function readStaticFile(?string $file_relative_path, ?int $length=null, bool $dont_read_if_overSized=false) : string {
		//--
		if(self::staticFileExists((string)$file_relative_path) !== true) {
			Smart::log_warning(__METHOD__.' # File Path is Invalid: Empty / Unsafe / Not Found / Not Readable: `'.$file_relative_path.'`');
			return '';
		} //end if
		//--
		$length = (int) $length;
		if((int)$length <= 0) {
			$length = null;
		} //end if
		//--
		// do not use clearstatcache(), this is intended for STATIC FILES ONLY
		//--
		$staticRootPath = (string) self::getStaticFilesRootPath();
		//--
		self::raiseErrorIfUnsafePath((string)$file_relative_path);
		self::raiseErrorIfUnsafePath((string)$staticRootPath.$file_relative_path);
		//--
		if($dont_read_if_overSized === true) { // {{{SYNC-DONT-READ-FILE-IF-SPECIFIC-LEN-AND-OVERSIZED}}}
			if((int)filesize((string)$staticRootPath.$file_relative_path) > (int)$length) { // if this param is set to TRUE even if the max length was not specified and that is zero stop here !
				return '';
			} //end if
		} //end if
		//--
		$fcontent = null;
		if((int)$length > 0) {
			$fcontent = file_get_contents(
				(string) $staticRootPath.$file_relative_path,
				false, // don't use include path
				null, // context resource
				0, // start from begining (negative offsets still don't work)
				(int) $length // max length to read ; if zero, read the entire file
			);
		} else {
			$fcontent = file_get_contents(
				(string) $staticRootPath.$file_relative_path,
				false, // don't use include path
				null, // context resource
				0 // start from begining (negative offsets still don't work)
				// max length to read ; don't use this parameter here ...
			);
		} //end if else
		//--
		if($fcontent === false) { // check
			Smart::log_warning(__METHOD__.' # Failed to Read a File: `'.$file_relative_path.'`');
			return '';
		} //end if
		//--
		return (string) $fcontent;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function getStaticFilesRootPath() : string {
		//--
		if(!defined('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH')) {
			Smart::raise_error(
				__METHOD__.' # The Static Root Path was not defined',
				'Smart.Framework / FileSystem Utils / [SECURITY]: UNDEFINED ROOT PATH !' // msg to display
			);
			return '';
		} //end if
		//--
		if((string)trim((string)SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH) == '') {
			return '';
		} //end if
		//--
		if((string)trim((string)SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH) == '/') {
			Smart::raise_error(
				__METHOD__.' # The Static Root Path is disallowed: `/`',
				'Smart.Framework / FileSystem Utils / [SECURITY]: DISSALOWED ROOT PATH !' // msg to display
			);
			return '';
		} //end if
		//--
		if((string)substr((string)trim((string)SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH), -1, 1) != '/') {
			Smart::raise_error(
				__METHOD__.' # The Static Root Path is invalid: `'.SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH.'`',
				'Smart.Framework / FileSystem Utils / [SECURITY]: INVALID ROOT PATH !' // msg to display
			);
			return '';
		} //end if
		//--
		return (string) trim((string)SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the MAXIMUM allowed Upload Size
	 *
	 * @return INTEGER								:: the Max Upload Size in Bytes
	 */
	public static function maxUploadFileSize() : int {
		//--
		$inival = (string) trim((string)ini_get('upload_max_filesize'));
		if((string)$inival == '') {
			return 0;
		} //end if
		//--
		$last = (string) strtoupper((string)substr((string)$inival, -1, 1));
		$value = (int) intval((string)$inival);
		//--
		if((string)$last === 'K') { // kilo
			$value *= 1000;
		} elseif((string)$last === 'M') { // mega
			$value *= 1000 * 1000;
		} elseif((string)$last === 'G') { // giga
			$value *= 1000 * 1000 * 1000;
		} elseif((string)$last === 'T') { // tera
			$value *= 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'P') { // peta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000;
		/* the below unit of measures may overflow the max 64-bit integer value with higher values set in php.ini ... anyway there is no case to upload such large files ...
		} elseif((string)$last === 'E') { // exa
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'Z') { // zetta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'Y') { // yotta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		*/
		} //end if else
		//--
		return (int) Smart::format_number_int((int)$value, '+');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a Name of a File or Directory (not a path containing /) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe filenames or dirnames are accessed.
	 *
	 * @param 	STRING 	$y_fname 								:: The dirname or filename, (not path containing /) to validate
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function checkIfSafeFileOrDirName(?string $y_fname) : int {
		//-- test empty filename
		if((string)trim((string)$y_fname) == '') {
			return 0;
		} //end if else
		//-- test valid characters in filename or dirname (must not contain / (slash), it is not a path)
		if(!preg_match((string)Smart::REGEX_SAFE_FILE_NAME, (string)$y_fname)) { // {{{SYNC-CHK-SAFE-FILENAME}}}
			return 0;
		} //end if
		//-- test valid path (should pass all tests from valid, especially: must not be equal with: / or . or .. (and they are includded in valid path)
		if(self::testIfValidPath((string)$y_fname) !== 1) {
			return 0;
		} //end if
		//--
		if((int)strlen((string)$y_fname) > 255) {
			return 0;
		} //end if
		//--
		// IMPORTANT: it should not test if filenames or dirnames start with a # (protected) as they are not paths !!!
		//--
		return 1;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a Path (to a Directory or to a File) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe paths are accessed.
	 * Absolute paths on windows if checked as intended must be previous be converted using slash instead of backslash using Smart::fix_path_separator()
	 *
	 * @param 	STRING 	$path 									:: The path (dir or file) to validate
	 * @param 	BOOL 	$deny_absolute_path 					:: *Optional* If TRUE will dissalow absolute paths
	 * @param 	BOOL 	$allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to TRUE except if you know what you are really doing ! If set to TRUE will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function checkIfSafePath(?string $path, bool $deny_absolute_path=true, bool $allow_protected_relative_paths=false) : int { // {{{SYNC-FS-PATHS-CHECK}}}
		//-- override
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_relative_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//-- dissalow empty paths
		if((string)trim((string)$path) == '') {
			return 0;
		} //end if else
		//-- test valid path
		$vpath = (string) $path; // linux/unix compatible operating systems, use as is
		if((string)DIRECTORY_SEPARATOR == '\\') { // Fix: just for Windows OS, remove the `c:` to avoid change rules ; this is not as safe as on other platforms, but Windows support is intended just for development purposes ...
			if($deny_absolute_path === false) { // only if absolute paths are not denied
				if(strpos((string)$vpath, ':') === 1) { // 2nd character must be `:` (colon)
					if(preg_match('/^[a-zA-Z]{1}/', (string)$vpath)) { // 1st character must be a-z A-Z (drive letter)
						$vpath = (string) substr((string)$vpath, 2); // important: do not fix path separator here, must be fixed prior to pass to this function to ensure correct usage
					} //end if
				} //end if
			} //end if
		} //end if
		if(self::testIfValidPath((string)$vpath) !== 1) {
			return 0;
		} //end if
		//-- test backward path
		if(self::testIfBackwardPath((string)$path) !== 1) {
			return 0;
		} //end if
		//-- test absolute path and protected path
		if($deny_absolute_path !== false) {
			if(self::testIfAbsolutePath((string)$path) !== 1) {
				return 0;
			} //end if
		} //end if
		//-- test protected path
		if($allow_protected_relative_paths !== true) {
			if(self::testIfProtectedPath((string)$path) !== 1) { // check protected path only if deny absolute path access, otherwise n/a
				return 0;
			} //end if
		} //end if
		//-- test max path length
		if(((int)strlen((string)$path) > 1024) OR ((int)strlen((string)$path) > (int)PHP_MAXPATHLEN)) { // IMPORTANT: this also protects against cycled loops that can occur when scanning linked folders
			return 0; // path is longer than the allowed path max length by PHP_MAXPATHLEN between 512 to 4096 (safe is 1024)
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CHECK ABSOLUTE PATH ACCESS
	/**
	 * Function: Raise Error if Unsafe Path.
	 * Security: implements protection if unsafe paths are accessed.
	 *
	 * @param 	STRING 	$path 									:: The path (dir or file) to validate
	 * @param 	BOOL 	$deny_absolute_path 					:: *Optional* If TRUE will dissalow absolute paths
	 * @param 	BOOL 	$allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to TRUE except if you know what you are really doing ! If set to TRUE will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function raiseErrorIfUnsafePath(?string $path, bool $deny_absolute_path=true, bool $allow_protected_relative_paths=false) : void { // {{{SYNC-FS-PATHS-CHECK}}}
		//--
		if(self::checkIfSafePath((string)$path, (bool)$deny_absolute_path, (bool)$allow_protected_relative_paths) !== 1) {
			Smart::raise_error(
				__METHOD__.' # Unsafe Path Usage Detected in code: `'.$path.'`',
				'Smart.Framework / FileSystem Utils / [SECURITY]: UNSAFE PATH USAGE DETECTED !' // msg to display
			);
			return;
		} //end if
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF PROTECTED (SPECIAL) PATH
	// special protected paths (only relative) start with '#'
	// returns 1 if OK
	private static function testIfProtectedPath(?string $y_path) : int {
		//--
		$y_path = (string) $y_path;
		//--
		if((string)substr((string)trim((string)$y_path), 0, 1) == '#') {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF VALID PATH
	// test if path is valid ; on windows paths must use the / instead of backslash (and without drive letter prefix c:) to comply
	// path should not contain SPACE, BACKSLASH, :, |, ...
	// the : is denied also on unix because can lead to unpredictable paths behaviours
	// the | is denied because old MacOS is not supported
	// path should not be EMPTY or equal with: / . .. ./ ../ ./.
	// path should contain just these characters _ a-z A-Z 0-9 - . @ # /
	// returns 1 if OK
	private static function testIfValidPath(?string $y_path) : int {
		//--
		$y_path = (string) $y_path;
		//--
		if(!preg_match((string)Smart::REGEX_SAFE_PATH_NAME, (string)$y_path)) { // {{{SYNC-SAFE-PATH-CHARS}}} {{{SYNC-CHK-SAFE-PATH}}} only ISO-8859-1 characters are allowed in paths (unicode paths are unsafe for the network environments !!!)
			return 0;
		} //end if
		//--
		if(
			((string)trim((string)$y_path) == '') OR 							// empty path: error
			((string)trim((string)$y_path) == '.') OR 							// special: protected
			((string)trim((string)$y_path) == '..') OR 							// special: protected
			((string)trim((string)$y_path) == '/') OR 							// root dir: security
			(strpos((string)$y_path, ' ') !== false) OR 						// no space allowed ; Windows paths must be re-converted using / instead of \
			(strpos((string)$y_path, '\\') !== false) OR 						// no backslash allowed
			(strpos((string)$y_path, '://') !== false) OR 						// no protocol access allowed
			(strpos((string)$y_path, ':') !== false) OR 						// no dos/win disk access allowed
			(strpos((string)$y_path, '|') !== false) OR 						// no macos disk access allowed
			((string)trim((string)$y_path) == './') OR 							// this must not be used - dissalow FS operations to the app root path, enforce use relative paths such as path/to/something
			((string)trim((string)$y_path) == '../') OR 						// backward path access denied: security
			((string)trim((string)$y_path) == './.') OR 						// this is a risk that can lead to unpredictable results
			(strpos((string)$y_path, '...') !== false) OR 						// this is a risk that can lead to unpredictable results
			((string)substr((string)trim((string)$y_path), -2, 2) == '/.') OR 	// special: protected ; this may lead to rewrite/delete the special protected . in a directory if refered as a filename or dirname that may break the filesystem
			((string)substr((string)trim((string)$y_path), -3, 3) == '/..')  	// special: protected ; this may lead to rewrite/delete the special protected .. in a directory if refered as a filename or dirname that may break the filesystem
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF BACKWARD PATH
	// test backpath or combinations against crafted paths to access backward paths on filesystem
	// will test only combinations allowed by testIfValidPath()
	// returns 1 if OK
	private static function testIfBackwardPath(?string $y_path) : int {
		//--
		$y_path = (string) $y_path;
		//--
		if(
			(strpos((string)$y_path, '/../') !== false) OR
			(strpos((string)$y_path, '/./') !== false) OR
			(strpos((string)$y_path, '/..') !== false) OR
			(strpos((string)$y_path, '../') !== false)
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF ABSOLUTE PATH
	// test against absolute path access
	// will test only combinations allowed by testIfValidPath() and testIfBackwardPath()
	// the first character should not be / ; path must not contain :, :/
	// returns 1 if OK
	private static function testIfAbsolutePath(?string $y_path) : int {
		//--
		$y_path = (string) trim((string)$y_path);
		//--
		$c1 = (string) substr((string)$y_path, 0, 1);
		$c2 = (string) substr((string)$y_path, 1, 1);
		//--
		if(
			((string)$c1 == '/') OR // unix/linux # /path/to/
			((string)$c1 == ':') OR // windows # :/path/to/
			((string)$c2 == ':')    // windows # c:/path/to
		) {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe add a trailing slash to a path if not already have it, with safe detection and avoid root access.
	 *
	 * Adding a trailing slash to a path is not a simple task as if path is empty, adding the trailing slash will result in accessing the root file system as will be: /.
	 * Otherwise it have to detect if the trailing slash exists already to avoid double slash.
	 *
	 * @param 	STRING 	$y_path 					:: The path to add the trailing slash to
	 *
	 * @return 	STRING								:: The fixed path with a trailing
	 */
	public static function addPathTrailingSlash(?string $y_path) : string {
		//--
		$y_path = (string) trim((string)Smart::fix_path_separator((string)trim((string)$y_path)));
		//--
		if(((string)$y_path == '') OR ((string)$y_path == '.') OR ((string)$y_path == './')) {
			return './'; // this is a mandatory security fix for the cases when used with dirname() which may return empty or just .
		} //end if
		//--
		if(((string)$y_path == '/') OR ((string)trim((string)str_replace(['/', '.'], ['', ''], (string)$y_path)) == '') OR (strpos((string)$y_path, '\\') !== false)) {
			Smart::log_warning(__METHOD__.' # Add Last Dir Slash: Invalid Path: ['.$y_path.'] ; Returned: tmp/invalid/');
			return 'tmp/invalid/'; // Security Fix: avoid make the path as root: / (if the path is empty, adding a trailing slash is a huge security risk)
		} //end if
		//--
		if((string)substr((string)$y_path, -1, 1) != '/') {
			$y_path = (string) $y_path.'/';
		} //end if
		//--
		self::raiseErrorIfUnsafePath((string)$y_path, true, true); // deny absolute paths ; allow #special paths
		//--
		return (string) $y_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Remove the versioning from a file name
	 * Ex: myfile.@1505240360@.ext will become: myfile.ext
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$file 						:: The file name (with version or not) to be processed
	 *
	 * @return 	STRING								:: The fixed file name without the version
	 */
	public static function fnameVersionClear(?string $file) : string {
		//--
		$file = (string) $file;
		//--
		if((strpos($file, '.@') !== false) AND (strpos($file, '@.') !== false)) {
			//--
			$arr = (array) explode('.@', (string)$file);
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			$arr2 = (array) explode('@.', (string)$arr[1]);
			if(!array_key_exists(0, $arr2)) {
				$arr2[0] = null;
			} //end if
			if(!array_key_exists(1, $arr2)) {
				$arr2[1] = null;
			} //end if
			//--
			if((string)trim((string)$arr[0]) == '') {
				$arr[0] = '_empty-filename_';
			} //end if
			if(((string)$arr2[1] === '_no-ext_') OR ((string)$arr2[1] === '')) { // {{{SYNC-FILE-VER-NOEXT}}}
				$file = (string) $arr[0];
			} else {
				$file = (string) $arr[0].'.'.$arr2[1];
			} //end if else
			//--
		} //end if
		//--
		return (string) $file;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Add the version to a file name. The file name MUST have a valid extension (do not use on file names without extension)
	 * Ex: myfile.ext will become: myfile.@1505240360@.ext OR myfile.@a1-32_zx@.ext
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$file 						:: The file name (with version or not) to be processed ; if version detected will be preserved
	 * @param 	STRING 	$version 					:: The version to be added ; if the version is empty, a stdmtime version will be used (microtime: 1517576620.6128 as 15175766206128) ; if version is invalid, will NOT add a version: allowed characters for version (as safe valid name): [a-z0-9] _ - (except the . @ which are allowed but will be removed)
	 *
	 * @return 	STRING								:: The fixed file name with a version
	 */
	public static function fnameVersionAdd(?string $file, ?string $version=null) : string {
		//--
		$file = (string) self::fnameVersionClear((string)trim((string)$file)); // clear any previous version ...
		if((string)$file == '') {
			return '';
		} //end if
		//--
		if((string)trim((string)$version) == '') {
			$version = (string) strtr((string)microtime(true), [ '.' => '' ]); // version stdmtime
		} //end if
		$version = (string) trim((string)strtolower((string)str_replace(['.', '@'], ['', ''], (string)Smart::safe_validname((string)$version))));
		if((string)$version == '') {
			return (string) $file; // just in case
		} //end if
		//--
		$file_no_ext = (string) self::extractPathFileNoExtName((string)$file); // fix: removed strtolower()
		$file_ext = (string) self::extractPathFileExtension((string)$file); // fix: removed strtolower()
		//--
		if((string)$file_ext == '') { // because file versioning expects a valid file extension, to avoid break when version remove will find no extension and would consider the version as extension, add something as extension
			$file_ext = '_no-ext_'; // {{{SYNC-FILE-VER-NOEXT}}}
		} //end if
		//--
		$fwithver = (string) $file_no_ext.'.@'.$version.'@.'.$file_ext;
		//--
		$test_ver = (string) self::fnameVersionGet((string)$fwithver);
		if((string)$test_ver == '') {
			Smart::log_warning(__METHOD__.' # Failed to get the FileName Version from: `'.$fwithver.'` [`'.$file.'`, `'.$version.'`]');
		} //end if
		//--
		return (string) $fwithver;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the version from a file name.
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$file 						:: The file name to be processed
	 *
	 * @return 	STRING								:: The version from filename or ''
	 */
	public static function fnameVersionGet(?string $file) : string {
		//--
		$file = (string) $file;
		//--
		$version = '';
		//--
		if((strpos($file, '.@') !== false) AND (strpos($file, '@.') !== false)) {
			//--
			$arr = (array) explode('.@', (string)$file);
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			$arr2 = (array) explode('@.', (string)$arr[1]);
			if(!array_key_exists(0, $arr2)) {
				$arr2[0] = null;
			} //end if
			if(!array_key_exists(1, $arr2)) {
				$arr2[1] = null;
			} //end if
			//--
			$version = (string) $arr2[0];
			//--
		} //end if
		//--
		return (string) $version;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a file name for a specific version.
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 	$file 						:: The file name to be checked
	 * @param 	STRING 	$version 					:: The version to be checked
	 *
	 * @return 	0/1									:: returns 1 if the version is detected ; otherwise returns 0 if version not detected
	 */
	public static function fnameVersionCheck(?string $file, ?string $version) : int {
		//--
		$file = (string) trim((string)$file);
		$version = (string) trim((string)strtolower((string)str_replace(['.', '@'], '', (string)Smart::safe_validname((string)$version))));
		//--
		if(stripos($file, '.@'.$version.'@.') !== false) {
			return 1;
		} //end if
		//--
		return 0;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the folder name from a path (except last trailing slash: /)
	 *
	 * @param STRING 	$y_path						:: the path (dir or file)
	 * @return STRING 								:: a directory path [FOLDER NAME]
	 */
	public static function extractPathDir(?string $y_path) : string {
		//--
		$y_path = (string) Smart::safe_pathname((string)$y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)Smart::safe_pathname((string)$arr['dirname'])); // this may contain /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file name (includding extension) from path
	 * WARNING: path_info('c:\\file.php') will not work correct on unix, but on windows will work correct both: path_info('c:\\file.php') and path_info('path/file.php'
	 * @param STRING 	$y_path			path or file
	 * @return STRING 					[FILE NAME]
	 */
	public static function extractPathFileName(?string $y_path) : string {
		//--
		$y_path = (string) Smart::safe_pathname((string)$y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)Smart::safe_filename((string)$arr['basename']));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file name (WITHOUT extension) from path
	 *
	 * @param STRING 	$y_path			path or file
	 * @return STRING 					[FILE NAME]
	 */
	public static function extractPathFileNoExtName(?string $y_path) : string {
		//--
		$y_path = (string) Smart::safe_pathname((string)$y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		$tmp_ext = (string) $arr['extension'];
		$tmp_file = (string) $arr['basename'];
		//--
		$str_len = (int) ((int)strlen($tmp_file) - (int)strlen($tmp_ext) - 1);
		//--
		if((int)strlen($tmp_ext) > 0) {
			// with .extension
			$tmp_xfile = (string) substr((string)$tmp_file, 0, (int)$str_len);
		} else {
			// no extension
			$tmp_xfile = (string) $tmp_file;
		} //end if else
		//--
		return (string) trim((string)Smart::safe_filename((string)$tmp_xfile));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file extension (without .) from path
	 *
	 * @param STRING 	$y_path			path or file
	 * @return STRING 					[FILE EXTENSION]
	 */
	public static function extractPathFileExtension(?string $y_path) : string {
		//--
		$y_path = (string) Smart::safe_pathname((string)$y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)strtolower((string)Smart::safe_filename((string)$arr['extension'])));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a prefixed dir from a base62 (also supports: base58 / base36 / base32 / base16 / base10) UID, 8..72 chars length : [a-zA-Z0-9].
	 * It does include also the UID as final folder segment.
	 * Example: for ID 8iAz0WtTuV72QZ72Re5X0PlIgB23M6 will return: 8/8iAz0WtTuV72QZ72Re5X0PlIgB23M6/ as the generated prefixed path.
	 * This have to be used for large folder storage structure to avoid limitations on some filesystems (ext3 / ntfs) where max sub-dirs per dir is 32k.
	 *
	 * The prefixed path will be grouped by each 1 character (max sub-folders per folder: 62).
	 * If a shorther length than 8 chars is provided will pad with 0 on the left.
	 * If a longer length than 72 or an invalid ID is provided will reset the ID to 00000000 (8 chars) for the given length, but also drop a warning.
	 *
	 * @param STRING 		$y_id			8..72 chars id (uid)
	 * @param INTEGER		$y_spectrum 	1..7 the expanding levels spectrum
	 * @return STRING 						Prefixed Path
	 */
	public static function prefixedUidB62Path(?string $y_id, int $y_spectrum) : string {
		//--
		$y_id = (string) trim((string)$y_id);
		if(
			((int)strlen((string)$y_id) < 8)
			OR
			((int)strlen((string)$y_id) > 72)
			OR
			(!preg_match('/^[a-zA-Z0-9]+$/', (string)$y_id))
		) {
			Smart::log_warning(__METHOD__.' # Invalid ID (min length is 8 ; max length is 72 ; may contain only [a-zA-Z0-9] characters) ['.$y_id.']');
			$y_id = '00000000'; // 8 chars long
		} //end if
		//--
		$y_spectrum = (int) $y_spectrum;
		if((int)$y_spectrum < 1) {
			Smart::log_warning(__METHOD__.' # Invalid Spectrum (min is 1): ['.(int)$y_spectrum.']');
			$y_spectrum = 1;
		} elseif((int)$y_spectrum > 7) {
			Smart::log_warning(__METHOD__.' # Invalid Spectrum (max is 7): ['.(int)$y_spectrum.']');
			$y_spectrum = 7;
		} //end if else
		//--
		$arr = [];
		for($i=0; $i<(int)$y_spectrum; $i++) {
			$arr[] = (string) substr((string)$y_id, (int)$i, 1);
		} //end for
		$arr[] = (string) $y_id; // this have to be the last entry
		//--
		$dir = (string) implode('/', (array)$arr).'/';
		//--
		if(!self::checkIfSafePath((string)$dir)) {
			Smart::log_warning(__METHOD__.' # Invalid Dir Path: ['.$dir.'] :: From ID: ['.$y_id.']');
			return 'tmp/invalid/pfx-uidb62-path/'; // this error should not happen ...
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a prefixed dir from a base36 UUID, 10 chars length : [A-Z0-9].
	 * It does include also the UUID as final folder segment.
	 * Example: for ID ABCDEFGHIJ09 will return: 9T/5B/0B/9M/9T5B0B9M8M/ as the generated prefixed path.
	 * This have to be used for large folder storage structure to avoid limitations on some filesystems (ext3 / ntfs) where max sub-dirs per dir is 32k.
	 *
	 * The prefixed path will be grouped by each 2 characters (max sub-folders per folder: 36 x 36 = 1296).
	 * If a shorther length than 10 chars is provided will pad with 0 on the left.
	 * If a longer length or an invalid ID is provided will reset the ID to 000000..00 (10 chars) for the given length, but also drop a warning.
	 *
	 * @param STRING 		$y_id			10 chars id (uuid10)
	 * @return STRING 						Prefixed Path
	 */
	public static function prefixedUuid10B36Path(?string $y_id) : string { // check len is default 10 as set in lib core uuid 10s
		//--
		$y_id = (string) strtoupper((string)trim((string)$y_id));
		//--
		if(((int)strlen((string)$y_id) != 10) OR (!preg_match('/^[A-Z0-9]+$/', (string)$y_id))) {
			Smart::log_warning(__METHOD__.' # Invalid ID ['.$y_id.']');
			$y_id = '0000000000'; // str-10.B36 (uuid10)
		} //end if
		//--
		$dir = (string) self::addPathTrailingSlash((string)self::addPathTrailingSlash((string)implode('/', (array)str_split((string)substr((string)$y_id, 0, 8), 2))).$y_id); // split by 2 grouping except last 2 chars
		//--
		if(!self::checkIfSafePath((string)$dir)) {
			Smart::log_warning(__METHOD__.' # Invalid Dir Path: ['.$dir.'] :: From ID: ['.$y_id.']');
			return 'tmp/invalid/pfx-uuid10b36-path/'; // this error should not happen ...
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a prefixed dir from a base16 UUID (sha1), 40 chars length : [a-f0-9].
	 * It does NOT include the ID final folder.
	 * Example: for ID df3a808b2bf20aaab4419c43d9f3a6143bd6b4bb will return: d/f3a/808/b2b/f20/aaa/b44/19c/43d/9f3/a61/43b/d6b/ as the generated prefixed path.
	 * This have to be used for large folder storage structure to avoid limitations on some filesystems (ext3 / ntfs) where max sub-dirs per dir is 32k.
	 *
	 * The prefixed folder will be grouped by each 3 characters (max sub-folders per folder: 16 x 16 x 16 = 4096).
	 * If a shorther length than 40 chars is provided will pad with 0 on the left.
	 * If a longer length than 40 chars or an invalid ID is provided will reset the ID to 000000..00 (40 chars) for the given length, but also drop a warning.
	 *
	 * @param STRING 		$y_id			40 chars id (sha1)
	 * @return STRING 						Prefixed Path
	 */
	public static function prefixedUuid40B16Path(?string $y_id) : string { // here the number of levels does not matter too much as at the end will be a cache file
		//--
		$y_id = (string) strtolower((string)trim((string)$y_id));
		//--
		if(((int)strlen((string)$y_id) != 40) OR (!preg_match('/^[a-f0-9]+$/', (string)$y_id))) {
			Smart::log_warning(__METHOD__.' # Invalid ID ['.$y_id.']');
			$y_id = '0000000000000000000000000000000000000000'; // str-40.hex (sha1)
		} //end if
		//--
		$dir = (string) self::addPathTrailingSlash((string)substr((string)$y_id, 0, 1).'/'.implode('/', (array)str_split((string)substr((string)$y_id, 1, 36), 3))); // split by 3 grouping
		//--
		if(!self::checkIfSafePath((string)$dir)) {
			Smart::log_warning(__METHOD__.' # Invalid Dir Path: ['.$dir.'] :: From ID: ['.$y_id.']');
			return 'tmp/invalid/pfx-uuid40b16-path/'; // this error should not happen ...
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the File MimeType
	 *
	 * @param STRING 		$file_name_or_path			the filename or path (includding file extension) ; Ex: file.ext or path/to/file.ext
	 * @return STRING 									the mime type by extension (will also detect some standard files without extension: ex: readme)
	 */
	public static function getMimeType(?string $file_name_or_path) : string {
		//--
		return (string) self::mimeEval((string)$file_name_or_path, false);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the File MimeType
	 *
	 * @param STRING 		$file_name_or_path			the filename or path (includding file extension) ; Ex: file.ext or path/to/file.ext
	 * @param MIXED 		$disposition 				EMPTY STRING (leave as is) ; ENUM: attachment | inline - to force a disposition type
	 * @return ARRAY 									Example: ARRAY [ 0 => 'text/plain' ; 1 => 'inline; filename="file.ext"' ; 2 => 'inline' ] OR [ 0 => 'application/octet-stream' ; 1 => 'attachment; filename="file.ext"' ; 2 => 'attachment' ]
	 */
	public static function getArrMimeType(?string $file_name_or_path, ?string $disposition='') : array {
		//--
		return (array) self::mimeEval((string)$file_name_or_path, (string)$disposition);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Evaluate and return the File MimeType (and *Optional* the Disposition by File Extension).
	 *
	 * @param STRING 		$yfile			the filename or path (includding file extension) ; Ex: file.ext or path/to/file.ext
	 * @param MIXED 		$ydisposition 	NULL or EMPTY STRING (leave as is) ; ENUM: attachment | inline - to force a disposition type ; BOOLEAN: FALSE to get just Mime Type (TRUE is currently not used)
	 * @return MIXED 						ARRAY [ 0 => mimeType ; 1 => inline/attachment; filename="file.ext" ; 2 => inline/attachment ] ; IF $ydisposition == FALSE will return STRING: mimeType
	 */
	private static function mimeEval(?string $yfile, $ydisposition=null) { // mixed type: STRING | ARRAY
		//--
		$yfile = (string) Smart::safe_pathname((string)$yfile);
		//--
		$file = (string) self::extractPathFileName((string)$yfile); // bug fixed: if a full path is sent, try to get just the file name to return
		$lfile = (string) strtolower((string)$file);
		//--
		$type = '';
		$disp = '';
		//--
		if(in_array((string)$lfile, [ // all lowercase as file is already strtolower
			'#release',
			'license',
			'license-bsd',
			'license-gplv3',
			'changelog',
			'changes',
			'readme',
			'makefile',
			'cmake',
			'meson.build',
			'go.mod', // go module
			'go.sum', // go checksum
			'mime.types', // apache
			'magic', // apache
			'manifest', // github
			'exports',
			'fstab',
			'group',
			'hosts',
			'machine-id',
			'.htaccess',
			'.htpasswd',
			'.gitignore',
			'.gitattributes',
			'.gitmodules',
			'.properties',
		])) {
			$extension = (string) $lfile;
		} else {
			$extension = (string) strtolower((string)self::extractPathFileExtension((string)$yfile)); // [OK]
		} //end if else
		//--
		switch((string)$extension) {
			//-------------- html / css
			case 'htm':
			case 'html':
			case 'mtpl': // marker tpl templating
			case 'tpl':  // tpl templating
			case 'twist': // tpl twist
			case 'twig': // twig templating
			case 't3fluid': // typo3 fluid templating
			case 'django': // django templating
				$type = 'text/html';
				$disp = 'attachment';
				break;
			case 'css':
			case 'less':
			case 'scss':
			case 'sass':
				$type = 'text/css';
				$disp = 'attachment';
				break;
			//-------------- php
			case 'php':
				$type = 'application/x-php';
				$disp = 'attachment';
				break;
			//-------------- javascript
			case 'js':
				$type = 'application/javascript';
				$disp = 'attachment';
				break;
			case 'json':
				$type = 'application/json';
				$disp = 'attachment';
				break;
			//-------------- xml
			case 'xhtml':
			case 'xml':
			case 'xsl':
			case 'dtd':
			case 'sgml': // Standard Generalized Markup Language
			case 'glade': // glade UI XML file
			case 'ui': // qt ui XML file
				$type = 'application/xml';
				$disp = 'attachment';
				break;
			//-------------- rss / atom / rdf
			case 'rdf':
				$type = 'application/rdf+xml';
				$disp = 'inline';
				break;
			case 'rss':
				$type = 'application/rss+xml';
				$disp = 'inline';
				break;
			case 'atom':
				$type = 'application/atom+xml';
				$disp = 'inline';
				break;
			//-------------- plain text and development
			case 'tex': // TeX
			case 'txt': // text
			case 'log': // log file
			case 'sql': // sql file
			case 'cf': // config file
			case 'cfg': // config file
			case 'conf': // config file
			case 'config': // config file
			case 'sh': // shell script
			case 'bash': // bash (shell) script
			case 'awk': // AWK script
			case 'll': // llvm IR assembler
			case 's': // llvm IR assembler
			case 'asm': // assembler (x86)
			case 'aasm': // assembler (arm)
			case 'masm': // assembler (mips)
			case 'cmd': // windows command file
			case 'bat': // windows batch file
			case 'ps1': // windows powershell
			case 'psm1': // windows powershell
			case 'psd1': // windows powershell
			case 'asp': // active server page
			case 'csharp': // C#
			case 'cs': // C#
			case 'm': // Objective C Method
			case 'c': // C
			case 'h': // C header
			case 'y': // Yacc source code file
			case 'f': // Fortran
			case 'fs': // Fortran Sharp
			case 'fsharp': // Fortran Sharp
			case 'r': // R language
			case 'd': // D language
			case 'diff': // Diff File
			case 'patch': // Diff Patch
			case 'pro': // QT project file
			case 'cpp': // C++
			case 'hpp': // C++ header
			case 'ypp': // Bison source code file
			case 'cxx': // C++
			case 'hxx': // C++ header
			case 'yxx': // Bison source code file
			case 'csh': // C-Shell script
			case 'tcl': // TCL
			case 'tk': // Tk
			case 'lua': // Lua
			case 'gjs': // gnome js
			case 'toml': // Tom's Obvious, Minimal Language (used with Cargo / Rust definitions)
			case 'rs': // Rust Language
			case 'go': // Go Lang
			case 'go.mod': // Go Module
			case 'go.sum': // Go Module Checksum
			case 'coffee': // Coffee Script
			case 'cson': // Coffee Script
			case 'ocaml': // Ocaml
			case 'ml': // Ocaml ML
			case 'mli': // Ocaml MLI (plain signature)
			case 'erl': // Erlang
			case 'hrl': // Erlang macro
			case 'pl': // perl
			case 'pm': // perl module
			case 'py': // python
			case 'phps': // php source, assign text/plain !
			case 'hh': // hip-hop (a kind of PHP for HipHop VM)
			case 'swift': // apple swift language
			case 'vala': // vala language
			case 'vapi': // vala vapi
			case 'deps': // vala deps
			case 'hx': // haxe
			case 'hxml': // haxe compiler arguments
			case 'hs': // haskell
			case 'lhs': // haskell literate
			case 'jsp': // java server page (html + syntax)
			case 'java': // java source code
			case 'groovy': // apache groovy language
			case 'gvy': // apache groovy language
			case 'gy': // apache groovy language
			case 'gsh': // apache groovy language, shell script
			case 'kotlin': // kotlin language
			case 'kt': // kotlin language
			case 'ktm': // kotlin language module
			case 'kts': // kotlin language script, shell script
			case 'scala': // Scala
			case 'sc': // scala
			case 'gradle': // automation tool for java like languages
			case 'pas': // Delphi / Pascal
			case 'as': // action script
			case 'ts': // type script
			case 'tsx': // type script
			case 'basic': // Basic
			case 'bas': // basic
			case 'vb': // visual basic - vbnet
			case 'vbs': // visual basic script - vbnet
			case 'openscad': // openscad
			case 'jscad': // openscad (js version)
			case 'scad': // openscad
			case 'stl': // openscad
			case 'obj': // openscad
			case 'inc': // include file
			case 'ins': // install config file
			case 'inf': // info file
			case 'ini': // ini file
			case 'yml': // yaml file
			case 'yaml': // yaml file
			case 'md': // markdown
			case 'markdown': // markdown
			case 'protobuf': // protocol buffers
			case 'pb': // protocol buffers
			case 'vhd': // vhdl
			case '#release': // release
			case 'license': // license
			case 'license-bsd': // license
			case 'license-gplv3': // license
			case 'changelog': // changelog
			case 'changes': // changes
			case 'readme': // license
			case 'makefile': // makefile
			case 'cmake': // cmake file
			case 'meson.build': // meson build file
			case 'mime.types': // apache
			case 'magic': // apache
			case 'manifest': // github
			case 'exports': // linux exports
			case 'fstab': // linux fstab
			case 'group': // linux group
			case 'hosts': // linux hosts
			case 'machine-id': // openbsd machine-id
			case '.htaccess': // .htaccess
			case '.htpasswd': // .htpasswd
			case '.gitignore': // git ignore
			case '.gitattributes': // git attributes
			case '.gitmodules': // git modules
			case '.properties': // properties file
			case 'pem': // PEM Certificate File
			case 'crl': // Certificate Revocation List
			case 'crt': // Certificate File
			case 'key': // Certificate Key File
			case 'keys': // Bind DNS keys
			case 'dns': // DNS Config
			case 'csp': // Content Security Policy
			case 'httph': // HTTP Header
			case 'dist': // .dist files are often configuration files which do not contain the real-world deploy-specific parameters
			case 'lock': // ex: yarn.lock
				$type = 'text/plain';
				$disp = 'attachment';
				break;
			//-------------- web images
			case 'svg':
				$type = 'image/svg+xml';
				$disp = 'inline';
				break;
			case 'png':
				$type = 'image/png';
				$disp = 'inline';
				break;
			case 'gif':
				$type = 'image/gif';
				$disp = 'inline';
				break;
			case 'jpg':
			case 'jpe':
			case 'jpeg':
				$type = 'image/jpeg';
				$disp = 'inline';
				break;
			case 'webp':
				$type = 'image/webp';
				$disp = 'inline';
				break;
			case 'ico':
				$type = 'image/vnd.microsoft.icon';
				$disp = 'inline';
				break;
			//-------------- other images
			case 'tif':
			case 'tiff':
				$type = 'image/tiff';
				$disp = 'attachment';
				break;
			case 'wmf':
				$type = 'application/x-msmetafile';
				$disp = 'attachment';
				break;
			case 'bmp':
				$type = 'image/bmp';
				$disp = 'attachment';
				break;
			//-------------- fonts
			case 'ttf':
				$type = 'application/x-font-ttf';
				$disp = 'attachment';
				break;
			case 'woff':
				$type = 'application/x-font-woff';
				$disp = 'attachment';
				break;
			case 'woff2':
				$type = 'application/x-font-woff2';
				$disp = 'attachment';
				break;
			//-------------- portable documents
			case 'pdf':
				$type = 'application/pdf';
				$disp = 'inline'; // 'attachment';
				break;
			case 'xfdf':
				$type = 'application/vnd.adobe.xfdf';
				$disp = 'attachment';
				break;
			case 'epub':
				$type = 'application/epub+zip';
				$disp = 'attachment';
				break;
			//-------------- email / calendar / addressbook
			case 'eml':
				$type = 'message/rfc822';
				$disp = 'attachment';
				break;
			case 'ics':
				$type = 'text/calendar';
				$disp = 'attachment';
				break;
			case 'vcf':
				$type = 'text/x-vcard';
				$disp = 'attachment';
				break;
			case 'vcs':
				$type = 'text/x-vcalendar';
				$disp = 'attachment';
				break;
			case 'ldif':
				$type = 'text/ldif';
				$disp = 'attachment';
				break;
			//-------------- data
			case 'csv': // csv comma
			case 'tab': // csv tab
				$type = 'text/csv';
				$disp = 'attachment';
				break;
			//-------------- specials
			case 'asc':
			case 'sig':
				$type = 'application/pgp-signature';
				$disp = 'attachment';
				break;
			case 'curl':
				$type = 'application/vnd.curl';
				$disp = 'attachment';
				break;
			//-------------- graphics
			case 'psd': // photoshop file
			case 'xcf': // gimp file
				$type = 'image/x-xcf';
				$disp = 'attachment';
				break;
			case 'ai': // illustrator file
			case 'eps':
			case 'ps':
				$type = 'application/postscript';
				$disp = 'attachment';
				break;
			//-------------- web video
			case 'ogg': // theora audio
			case 'oga':
				$type = 'audio/ogg';
				$disp = 'inline';
				break;
			case 'ogv': // theora video
				$type = 'video/ogg';
				$disp = 'inline';
				break;
			case 'webm': // google vp8
				$type = 'video/webm';
				$disp = 'inline';
				break;
			//-------------- other video
			case 'mpeg':
			case 'mpg':
			case 'mpe':
			case 'mpv':
			case 'mp4':
				$type = 'video/mpeg';
				$disp = 'attachment';
				break;
			case 'mpga':
			case 'mp2':
			case 'mp3':
			case 'mp4a':
				$type = 'audio/mpeg';
				$disp = 'attachment';
				break;
			case 'qt':
			case 'mov':
				$type = 'video/quicktime';
				$disp = 'attachment';
				break;
			case 'flv':
				$type = 'video/x-flv';
				$disp = 'attachment';
				break;
			case 'avi':
				$type = 'video/x-msvideo';
				$disp = 'attachment';
				break;
			case 'wm':
			case 'wmv':
			case 'wmx':
			case 'wvx':
				$type = 'video/x-ms-'.$extension;
				$disp = 'attachment';
				break;
			//-------------- flash
			case 'swf':
				$type = 'application/x-shockwave-flash';
				$disp = 'attachment';
				break;
			//-------------- rich text
			case 'rtf':
				$type = 'application/rtf';
				$disp = 'attachment';
				break;
			case 'abw': // Abi Word
				$type = 'application/x-abiword';
				$disp = 'attachment';
				break;
			//-------------- openoffice / libreoffice
			case 'odc':
				$type = 'application/vnd.oasis.opendocument.chart';
				$disp = 'attachment';
				break;
			case 'otc':
				$type = 'application/vnd.oasis.opendocument.chart-template';
				$disp = 'attachment';
				break;
			case 'odf':
			case 'sxm':
				$type = 'application/vnd.oasis.opendocument.formula';
				$disp = 'attachment';
				break;
			case 'otf':
				$type = 'application/vnd.oasis.opendocument.formula-template';
				$disp = 'attachment';
				break;
			case 'odg':
			case 'fodg':
			case 'sxd':
				$type = 'application/vnd.oasis.opendocument.graphics';
				$disp = 'attachment';
				break;
			case 'otg':
				$type = 'application/vnd.oasis.opendocument.graphics-template';
				$disp = 'attachment';
				break;
			case 'odi':
				$type = 'application/vnd.oasis.opendocument.image';
				$disp = 'attachment';
				break;
			case 'oti':
				$type = 'application/vnd.oasis.opendocument.image-template';
				$disp = 'attachment';
				break;
			case 'odp':
			case 'fodp':
			case 'sxi':
				$type = 'application/vnd.oasis.opendocument.presentation';
				$disp = 'attachment';
				break;
			case 'otp':
			case 'sti':
				$type = 'application/vnd.oasis.opendocument.presentation-template';
				$disp = 'attachment';
				break;
			case 'ods':
			case 'fods':
			case 'sxc':
				$type = 'application/vnd.oasis.opendocument.spreadsheet';
				$disp = 'attachment';
				break;
			case 'ots':
			case 'stc':
				$type = 'application/vnd.oasis.opendocument.spreadsheet-template';
				$disp = 'attachment';
				break;
			case 'odt':
			case 'fodt':
			case 'sxw':
				$type = 'application/vnd.oasis.opendocument.text';
				$disp = 'attachment';
				break;
			case 'ott':
			case 'stw':
				$type = 'application/vnd.oasis.opendocument.text-template';
				$disp = 'attachment';
				break;
			case 'otm':
				$type = 'application/vnd.oasis.opendocument.text-master';
				$disp = 'attachment';
				break;
			case 'oth':
				$type = 'application/vnd.oasis.opendocument.text-web';
				$disp = 'attachment';
				break;
			case 'odb':
				$type = 'application/vnd.oasis.opendocument.database';
				$disp = 'attachment';
				break;
			//-------------- ms office
			case 'doc':
			case 'dot':
				$type = 'application/msword';
				$disp = 'attachment';
				break;
			case 'docx':
			case 'dotx':
				$type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				$disp = 'attachment';
				break;
			case 'xla':
			case 'xlc':
			case 'xlm':
			case 'xls':
			case 'xlt':
			case 'xlw':
				$type = 'application/vnd.ms-excel';
				$disp = 'attachment';
				break;
			case 'xlsx':
			case 'xltx':
				$type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				$disp = 'attachment';
				break;
			case 'pot':
			case 'pps':
			case 'ppt':
				$type = 'application/vnd.ms-powerpoint';
				$disp = 'attachment';
				break;
			case 'potx':
			case 'ppsx':
			case 'pptx':
				$type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
				$disp = 'attachment';
				break;
			case 'mdb':
				$type = 'application/x-msaccess';
				$disp = 'attachment';
				break;
			//-------------- archives
			case '7z':
				$type = 'application/x-7z-compressed';
				$disp = 'attachment';
				break;
			case 'xz':
				$type = 'application/x-xz';
				$disp = 'attachment';
				break;
			case 'tar':
				$type = 'application/x-tar';
				$disp = 'attachment';
				break;
			case 'tgz':
			case 'tbz':
				$type = 'application/x-compressed';
				$disp = 'attachment';
				break;
			case 'gz':
				$type = 'application/x-gzip';
				$disp = 'attachment';
				break;
			case 'bz2':
				$type = 'application/x-bzip2';
				$disp = 'attachment';
				break;
			case 'z':
				$type = 'application/x-compress';
				$disp = 'attachment';
				break;
			case 'zip':
				$type = 'application/zip';
				$disp = 'attachment';
				break;
			case 'rar':
				$type = 'application/x-rar-compressed';
				$disp = 'attachment';
				break;
			case 'sit':
				$type = 'application/x-stuffit';
				$disp = 'attachment';
				break;
			//-------------- executables
			case 'exe':
			case 'msi':
			case 'dll':
			case 'com':
				$type = 'application/x-msdownload';
				$disp = 'attachment';
				break;
			//-------------- others, default
			default:
				$type = 'application/octet-stream';
				$disp = 'attachment';
			//--------------
		} //end switch
		//--
		if($ydisposition === false) {
			//--
			return (string) $type; // mime type
			//--
		} else {
			//--
			switch((string)$ydisposition) {
				case 'inline':
					$disp = 'inline'; // rewrite display mode
					break;
				case 'attachment':
					$disp = 'attachment'; // rewrite display mode
					break;
				default:
					// nothing
			} //end switch
			//--
			return [
				(string) $type, // mime type
				(string) $disp.'; filename="'.Smart::safe_filename((string)$file, '-').'"', // mime header disposition suffix
				(string) $disp // mime disposition
			];
			//--
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'Smart (FileSysUtils) // Required Settings',
					'data' => 'FileSysUtils RootPath : `'.(defined('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH') ? print_r(SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH,1) : '').'`'
				]);
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'Smart (FileSysUtils) // Internal Cache',
					'data' => 'Dump of Cached Static Paths:'."\n".print_r(self::$cachedStaticFilePaths,1)
				]);
			} //end if
		} //end if
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
