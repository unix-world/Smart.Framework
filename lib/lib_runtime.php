<?php
// [LIB - Smart.Framework / Lib Runtime]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart.Framework - Lib Runtime
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//==================================================================================
//================================================================================== CLASS START
//==================================================================================

/**
 * Class Smart.Framework Security
 * It may be used anywhere inside Smart.Framework or by Plugins and Application Modules.
 *
 * <code>
 * // Usage example:
 * SmartFrameworkSecurity::some_method_of_this_class(...);
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		This is generally intended for advanced usage !
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY !!!
 *
 * @depends 	-
 * @version 	v.20210401
 * @package 	Application
 *
 */
final class SmartFrameworkSecurity {

	// ::
	// This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.


	//======================================================================
	// Validate variable names (by default allow to register ONLY lowercase variables to avoid interfere with PHP reserved variables !! security fix !! ; allow camel case or upper is optional)
	public static function ValidateVariableName($y_varname, $y_allow_upper_letters=false) {

		// VALIDATE INPUT VARIABLE NAMES v.20200121

		//--
		$y_varname = (string) $y_varname; // force string
		//--

		//--
		$regex_only_number = '/^[0-9_]+$/'; 		// not allowed as first character, especially the _ because $_ have a very special purpose in PHP
		//--
		if($y_allow_upper_letters === true) {
			$regex_var_name = '/^[a-zA-Z0-9_]+$/'; 	// allowed characters in a variable name (only small letters, upper letters, numbers and _ ; in PHP upper letters for variables are reserved)
		} else {
			$regex_var_name = '/^[a-z0-9_]+$/'; 	// allowed characters in a variable name (only small letters, numbers and _ ; in PHP upper letters for variables are reserved)
		} //end if else
		//--

		//-- init
		$out = 0;
		//-- validate characters (variable must not be empty, must not start with an underscore or a number
		if(((string)$y_varname != '') AND ((string)$y_varname != '_') AND (preg_match((string)$regex_var_name, (string)$y_varname)) AND (!preg_match((string)$regex_only_number, (string)substr((string)$y_varname, 0, 1)))) {
			$out = 1;
		} //end if else
		//-- corrections (variable name must be between 1 char and 255 chars)
		if((int)strlen((string)$y_varname) < 1) {
			$out = 0;
		} elseif((int)strlen((string)$y_varname) > 255) {
			$out = 0;
		} //end if
		//--

		//--
		return (int) $out;
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Filter Input String
	public static function FilterUnsafeString($y_value) {
		//--
		if(is_array($y_value) OR is_object($y_value) OR is_resource($y_value)) {
			return null;
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_SECURITY_FILTER_INPUT')) {
			if((string)SMART_FRAMEWORK_SECURITY_FILTER_INPUT != '') {
				if((string)$y_value != '') {
					$y_value = preg_replace((string)SMART_FRAMEWORK_SECURITY_FILTER_INPUT, '', (string)$y_value);
				} //end if
			} //end if
		} //end if
		//--
		return (string) $y_value;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered values for GET/POST REQUEST variables (Max: 3+1 levels for arrays).
	 * It is used to prevent insecure variables.
	 * All the input vars should be always filtered to avoid extremely long arrays or insecure characters.
	 *
	 * @param STRING/ARRAY 		$y_var	the input variable
	 * @return STRING/ARRAY				[processed]
	 */
	public static function FilterGetPostCookieVars($y_var) {
		//-- v.150527 magic_quotes_gpc has been removed since PHP 5.4, no more check for it
		if(!isset($y_var)) {
			return $y_var; // fix for Illegal string offset
		} //end if
		//--
		if(is_array($y_var)) { // array
			//--
			$newvar = array();
			//--
			foreach($y_var as $key => $val) {
				//--
				if(is_array($val)) { // array
					//--
					foreach($val as $tmp_key => $tmp_val) {
						//--
						if(is_array($tmp_val)) { // array
							//--
							foreach($tmp_val as $tmpx_key => $tmpx_val) {
								//--
								$newvar[(string)$key][(string)$tmp_key][(string)$tmpx_key] = (string) self::FilterUnsafeString((string)$tmpx_val); // 1
								//--
							} //end while
							//--
						} else { // string
							//--
							$newvar[(string)$key][(string)$tmp_key] = (string) self::FilterUnsafeString((string)$tmp_val); // 2
							//--
						} //end if else
						//--
					} //end while
					//--
				} else { // string
					//--
					$newvar[(string)$key] = (string) self::FilterUnsafeString((string)$val); // 3
					//--
				} //end if else
				//--
			} //end while
			//--
		} else { // string
			//--
			$newvar = (string) self::FilterUnsafeString((string)$y_var); // 4
			//--
		} //end if
		//--
		return $newvar; // string or array
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the url decoded (+/- filtered) variable from RAWURLENCODE / URLENCODE
	 * It may be used ONLY when working with RAW PATH INFO / RAW QUERY URLS !!!
	 * IMPORTANT: the $_GET and $_REQUEST are already decoded. Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.
	 *
	 * @param STRING 				$y_var		the input variable
	 * @param BOOLEAN 				$y_filter 	*Optional* Default to TRUE ; if FALSE will only decode but not filter variable ; DO NOT DISABLE FILTERING EXCEPT WHEN YOU CALL IT LATER EXPLICIT !!!
	 * @return STRING				[processed]
	 */
	public static function urlVarDecodeStr($y_urlencoded_str_var, $y_filter=true) {
		//--
		$y_urlencoded_str_var = (string) urldecode((string)$y_urlencoded_str_var); // use urldecode() which decodes all % but also the + ; instead of rawurldecode() which does not decodes + !
		//--
		if($y_filter) {
			$y_urlencoded_str_var = (string) self::FilterUnsafeString($y_urlencoded_str_var);
		} //end if
		//--
		return (string) $y_urlencoded_str_var;
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS

//==================================================================================
//================================================================================== CLASS END
//==================================================================================



//==================================================================================
//================================================================================== CLASS START
//==================================================================================

/**
 * Class Smart.Framework Registry
 * It may be used anywhere inside Smart.Framework or by Plugins and Application Modules.
 * Normally there is no need to use this class as the controllers can access methods of this class directly.
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY !!!
 *
 * @depends 	-
 * @version 	v.20210401
 * @package 	Application
 *
 */
final class SmartFrameworkRegistry {

	// ::
	//  This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.

	/**
	 * The Registry Connections Container
	 * IMPORTANT: This can be used for very advanced development only to set a global connection here (ex: DB server)
	 * @var Array
	 */
	public static $Connections = array(); // connections registry

	private static $DebugMessages = array( // debug messages registry
		'stats' 			=> [],
		'optimizations' 	=> [],
		'extra' 			=> [],
		'db' 				=> [],
		'mail' 				=> [],
		'modules' 			=> []
	);

	private static $RequestLock = false; 	// request locking flag
	private static $RequestPath = '';		// request path (from path-info)
	private static $RequestVars = array(); 	// request registry
	private static $CookieVars  = array();  // cookie registry


	//===== Public Methods


	public static function setRequestPath($value) {
		//--
		if(self::$RequestLock !== false) {
			return false; // request registry is locked
		} //end if
		//--
		self::$RequestPath = (string) $value;
		//--
		return true; // OK
		//--
	} //END FUNCTION


	public static function getRequestPath() {
		//--
		return (string) self::$RequestPath;
		//--
	} //END FUNCTION


	public static function setRequestVar($key, $value) {
		//--
		if(self::$RequestLock !== false) {
			return false; // request registry is locked
		} //end if
		//--
		$key = (string) trim((string)$key);
		if(((string)$key == '') OR (!SmartFrameworkSecurity::ValidateVariableName((string)$key, true))) { // {{{SYNC-REQVARS-CAMELCASE-KEYS}}}
			return false;
		} //end if
		//--
		self::$RequestVars[(string)$key] = $value;
		//--
		return true; // OK
		//--
	} //END FUNCTION


	public static function issetRequestVar($key) {
		//--
		$key = (string) trim((string)$key);
		if(((string)$key == '') OR (!SmartFrameworkSecurity::ValidateVariableName((string)$key, true))) { // {{{SYNC-REQVARS-CAMELCASE-KEYS}}}
			return false;
		} //end if
		//--
		if((array_key_exists((string)$key, self::$RequestVars)) AND (isset(self::$RequestVars[(string)$key])) AND ((is_array(self::$RequestVars[(string)$key])) OR ((string)self::$RequestVars[(string)$key] != ''))) { // if is set and (array or non-empty string) ; numbers from request comes as string too
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION


	public static function getRequestVar($key, $defval=null, $type='') { // {{{SYNC-REQUEST-DEF-PARAMS}}}
		//--
		$key = (string) trim((string)$key);
		if(((string)$key == '') OR (!SmartFrameworkSecurity::ValidateVariableName((string)$key, true))) { // {{{SYNC-REQVARS-CAMELCASE-KEYS}}}
			return null;
		} //end if
		//--
		if(self::issetRequestVar((string)$key) === true) {
			$val = self::$RequestVars[(string)$key]; // use the value from request :: mixed
		} else {
			$val = $defval; // init with the default value :: mixed
		} //end if
		//--
		if(is_array($type)) { // # if $type is array, then cast is ENUM LIST, it must contain an array with all allowed values as strings #
			//--
			if(((string)$val != '') AND (in_array((string)$val, (array)$type))) {
				$val = (string) $val; // force string
			} else {
				$val = ''; // set as empty string
			} //end if else
			//--
		} else { // # else $type must be a string with one of the following cases #
			//--
			if(is_object($val) OR is_resource($val)) { // dissalow objects here !!!
				$val = null;
			} //end if
			//--
			if(!in_array((string)strtolower((string)$type), ['array', 'mixed', 'raw', ''])) {
				if((!is_scalar($val)) AND (!is_null($val))) {
					$val = null;
				} //end if
			} //end if
			//--
			switch((string)strtolower((string)$type)) {
				case 'array':
					if(!is_array($val)) {
						$val = array(); // set as empty array
					} else {
						$val = (array) $val; // force array
					} //end if else
					break;
				case 'string':
					$val = (string) $val;
					break;
				case 'boolean':
					$val = (string) strtolower((string)$val); // {{{SYNC-SMART-BOOL-GET-EXT}}}
					if(((string)$val == 'true') OR ((string)$val == 't')) {
						$val = true;
					} elseif(((string)$val == 'false') OR ((string)$val == 'f')) {
						$val = false;
					} else {
						$val = (bool) $val;
					} //end if else
					break;
				case 'integer':
					$val = (int) $val;
					break;
				case 'integer+':
					$val = (int) $val;
					if($val < 0) { // {{{SYNC-SMART-INT+}}}
						$val = 0;
					} //end if
					break;
				case 'integer-':
					$val = (int) $val;
					if($val > 0) { // {{{SYNC-SMART-INT-}}}
						$val = 0;
					} //end if
					break;
				case 'decimal1':
					$val = (string) number_format(((float)$val), 1, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal2':
					$val = (string) number_format(((float)$val), 2, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal3':
					$val = (string) number_format(((float)$val), 3, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal4':
					$val = (string) number_format(((float)$val), 4, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'numeric':
					$val = (float) $val;
					break;
				case 'mixed': // mixed variable types, can vary by context, leave as is
				case 'raw': // raw, alias for mixed, leave as is
				case '': // no explicit format (take as raw / mixed)
					// return as is ... (in this case extra validations have to be done explicit in the controller)
					break;
				default:
					@trigger_error(__CLASS__.'::'.__FUNCTION__.'() // Invalid Request Variable ['.$key.'] Type: '.$type, E_USER_WARNING);
					return null;
			} //end switch
			//--
		} //end if else
		//--
		return $val; // mixed
		//--
	} //END FUNCTION


	public static function getRequestVars() {
		//--
		return (array) self::$RequestVars; // array
		//--
	} //END FUNCTION


	public static function setCookieVar($key, $value) {
		//--
		if(self::$RequestLock !== false) {
			return false; // request registry is locked
		} //end if
		//--
		$key = (string) trim((string)$key); // {{{SYNC-COOKIEVARS-KEYS}}}
		if((string)$key == '') {
			return false;
		} //end if
		//--
		self::$CookieVars[(string)$key] = (string) $value; // cookie vars can be only string types (also numeric will be stored as string)
		//--
		return true; // OK
		//--
	} //END FUNCTION


	public static function issetCookieVar($key) {
		//--
		$key = (string) trim((string)$key); // {{{SYNC-COOKIEVARS-KEYS}}}
		if((string)$key == '') {
			return false;
		} //end if
		//--
		if((array_key_exists((string)$key, self::$CookieVars)) AND (isset(self::$CookieVars[(string)$key]))) { // if is set and not null ; cookies are considered to be set if they exist and non-null
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION


	public static function getCookieVar($key) {
		//--
		$key = (string) trim((string)$key); // {{{SYNC-COOKIEVARS-KEYS}}}
		if((string)$key == '') {
			return null;
		} //end if
		//--
		if(self::issetCookieVar((string)$key) === true) {
			$val = (string) self::$CookieVars[(string)$key]; // use the value from cookies :: string
		} else {
			$val = null; // init with the default value :: null
		} //end if
		//--
		return $val; // mixed
		//--
	} //END FUNCTION


	public static function getCookieVars() {
		//--
		return (array) self::$CookieVars; // array
		//--
	} //END FUNCTION


	//===== Internal Lock Control


	/**
	 * Locks the Request Object after parsing the $_REQUEST to avoid security injections in framework's runtime request after parsing
	 *
	 * @access 		private
	 * @internal
	 */
	public static function lockRequestVar() {
		//--
		return self::$RequestLock = true;
		//--
	} //END FUNCTION


	//===== Debugging


	/**
	 * Get Debug Message
	 *
	 * @access 		private
	 * @internal
	 */
	public static function getDebugMsgs($area) {
		//--
		switch((string)$area) {
			case 'stats':
				return (array) self::$DebugMessages['stats'];
				break;
			case 'optimizations':
				return (array) self::$DebugMessages['optimizations'];
				break;
			case 'extra':
				return (array) self::$DebugMessages['extra'];
				break;
			case 'db':
				return (array) self::$DebugMessages['db'];
				break;
			case 'mail':
				return (array) self::$DebugMessages['mail'];
				break;
			case 'modules':
				return (array) self::$DebugMessages['modules'];
				break;
			default:
				// invalid area - register a notice to log
				@trigger_error(__CLASS__.'::'.__FUNCTION__.'()'."\n".'INVALID DEBUG AREA: '.$area, E_USER_NOTICE);
				return array();
		} //end switch
		//--
	} //END FUNCTION


	/**
	 * Set Debug Message
	 *
	 * @access 		private
	 * @internal
	 */
	public static function setDebugMsg($area, $context, $dbgmsg, $opmode='') {
		//--
		if(!SmartFrameworkRuntime::ifDebug()) {
			return;
		} //end if
		//--
		if(!$dbgmsg) {
			return;
		} //end if
		//--
		$subcontext = '';
		if(strpos((string)$context, '|') !== false) {
			$arr 		= (array)  explode('|', (string)$context, 3); // separe 1st and 2nd from the rest
			$context 	= (string) trim((string)(isset($arr[0]) ? $arr[0] : ''));
			$subcontext = (string) trim((string)(isset($arr[1]) ? $arr[1] : ''));
			$arr = null;
		} //end if
		if((string)$context == '') {
			$context = '-UNDEFINED-CONTEXT-';
		} //end if
		//--
		switch((string)$area) {
			case 'stats':
				if((!array_key_exists('stats', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['stats']))) {
					self::$DebugMessages['stats'] = [];
				} //end if
				self::$DebugMessages['stats'][(string)$context] = $dbgmsg; // stats will be always rewrite (as assign: =) to avoid duplicates
				break;
			case 'optimizations':
				if((!array_key_exists('optimizations', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['optimizations']))) {
					self::$DebugMessages['optimizations'] = [];
				} //end if
				if((!array_key_exists((string)$context, self::$DebugMessages['optimizations'])) OR (!is_array(self::$DebugMessages['optimizations'][(string)$context]))) {
					self::$DebugMessages['optimizations'][(string)$context] = [];
				} //end if
				self::$DebugMessages['optimizations'][(string)$context][] = $dbgmsg;
				break;
			case 'extra':
				if((!array_key_exists('extra', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['extra']))) {
					self::$DebugMessages['extra'] = [];
				} //end if
				if((!array_key_exists((string)$context, self::$DebugMessages['extra'])) OR (!is_array(self::$DebugMessages['extra'][(string)$context]))) {
					self::$DebugMessages['extra'][(string)$context] = [];
				} //end if
				self::$DebugMessages['extra'][(string)$context][] = $dbgmsg;
				break;
			case 'db': // can have sub-context
				if((string)$subcontext == '') {
					$subcontext = '-UNDEFINED-SUBCONTEXT-'; // db must have always a sub-context
				} //end if
				if((!array_key_exists('db', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['db']))) {
					self::$DebugMessages['db'] = [];
				} //end if
				if((!array_key_exists((string)$context, self::$DebugMessages['db'])) OR (!is_array(self::$DebugMessages['db'][(string)$context]))) {
					self::$DebugMessages['db'][(string)$context] = [];
				} //end if
				switch((string)$opmode) {
					case '=': // assign
						self::$DebugMessages['db'][(string)$context][(string)$subcontext] = $dbgmsg;
						break;
					case '+': // increment
						if((!array_key_exists((string)$subcontext, self::$DebugMessages['db'][(string)$context])) OR (is_array(self::$DebugMessages['db'][(string)$context][(string)$subcontext]))) {
							self::$DebugMessages['db'][(string)$context][(string)$subcontext] = 0;
						} //end if
						self::$DebugMessages['db'][(string)$context][(string)$subcontext] += (float) $dbgmsg;
						break;
					default: // default, add new entry []
						if((!array_key_exists((string)$subcontext, self::$DebugMessages['db'][(string)$context])) OR (!is_array(self::$DebugMessages['db'][(string)$context][(string)$subcontext]))) {
							self::$DebugMessages['db'][(string)$context][(string)$subcontext] = [];
						} //end if
						self::$DebugMessages['db'][(string)$context][(string)$subcontext][] = $dbgmsg;
				} //end switch
				break;
			case 'mail':
				if((!array_key_exists('mail', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['mail']))) {
					self::$DebugMessages['mail'] = [];
				} //end if
				if((!array_key_exists((string)$context, self::$DebugMessages['mail'])) OR (!is_array(self::$DebugMessages['mail'][(string)$context]))) {
					self::$DebugMessages['mail'][(string)$context] = [];
				} //end if
				self::$DebugMessages['mail'][(string)$context][] = $dbgmsg;
				break;
			case 'modules':
				if((!array_key_exists('modules', self::$DebugMessages)) OR (!is_array(self::$DebugMessages['modules']))) {
					self::$DebugMessages['modules'] = [];
				} //end if
				if((!array_key_exists((string)$context, self::$DebugMessages['modules'])) OR (!is_array(self::$DebugMessages['modules'][(string)$context]))) {
					self::$DebugMessages['modules'][(string)$context] = [];
				} //end if
				self::$DebugMessages['modules'][(string)$context][] = $dbgmsg;
				break;
			default:
				// drop message and register a notice to log
				@trigger_error(__CLASS__.'::'.__FUNCTION__.'()'."\n".'INVALID DEBUG AREA: '.$area."\n".'Message Content: '.print_r($dbgmsg,1), E_USER_NOTICE);
		} //end switch
		//--
	} //END FUNCTION


	/**
	 * Register to Internal Cache
	 *
	 * @access 		private
	 * @internal
	 */
	public static function registerInternalCacheToDebugLog() {
		//--
		if(SmartFrameworkRuntime::ifInternalDebug()) {
			if(SmartFrameworkRuntime::ifDebug()) {
				self::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartFrameworkRegistry // Internal Data',
					'data' => 'Dump of Request Lock: ['.print_r(self::$RequestLock,1).']'."\n".'Dump of Request Vars Keys: '.print_r(array_keys((array)self::$RequestVars),1)."\n".'Dump of Connections:'."\n".print_r(self::$Connections,1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION


} //END CLASS


//==================================================================================
//================================================================================== CLASS END
//==================================================================================


//==================================================================================
//================================================================================== CLASS START
//==================================================================================

/**
 * Class Smart.Framework Runtime
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY !!!
 *
 * @depends 	classes: Smart, SmartUtils
 * @version		v.20210401
 * @package 	Application
 *
 */
final class SmartFrameworkRuntime {

	// ::
	// {{{SYNC-SMART-HTTP-STATUS-CODES}}}

	private static $AppReleaseHash = '';

	private static $isAdminArea = null;
	private static $isProdEnv = null;
	private static $isDebugOn = null;
	private static $isInternalDebugOn = null;

	private static $NoCacheHeadersSent = false;

	private static $HttpStatusCodesOK  = [200, 202, 203, 208, 304]; 						// list of framework available HTTP OK Status Codes (sync with middlewares)
	private static $HttpStatusCodesRDR = [301, 302]; 										// list of framework available HTTP Redirect Status Codes (sync with middlewares)
	private static $HttpStatusCodesERR = [400, 401, 403, 404, 429, 500, 502, 503, 504]; 	// list of framework available HTTP Error Status Codes (sync with middlewares)

	private static $RequestProcessed 			= false; 	// after all request variables are processed this will be set to true to avoid re-process request variables which can be a huge security issue if re-process is called by mistake !
	private static $RedirectionMonitorStarted 	= false; 	// after the redirection monitor have been started this will be set to true to avoid re-run it
	private static $HighLoadMonitorStats 		= null; 	// register the high load monitor caches


	//======================================================================
	public static function InstantFlush() {
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
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Include with Require a PHP Script (script must end with .php, be a safe relative path and cannot be includded more than once) ; $area must be a description in case of error
	public static function requirePhpScript($script, $area) {
		//--
		$script = (string) trim((string)$script);
		$area = (string) $area;
		//--
		$err = '';
		//--
		if(strlen((string)$script) < 5) {
			$err = 'path is too short';
		} elseif((string)substr((string)$script, -4, 4) !== '.php') {
			$err = 'path must end with .php file extension';
		} elseif(!SmartFileSysUtils::check_if_safe_path((string)$script)) {
			$err = 'path is not relative/safe';
		} elseif(!SmartFileSystem::is_type_file((string)$script)) {
			$err = 'was not found';
		} //end if
		//--
		if((string)$err != '') {
			Smart::raise_error('ERROR: Cannot Include a PHP Script for the area: `'.$area.'` ; script is: `'.$script.'` ; reason: the file '.$err);
			return;
		} //end if
		//--
		require((string)$script);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// if is ADMIN area will return TRUE else will return FALSE
	public static function isAdminArea() {
		//--
		if(self::$isAdminArea !== null) {
			return (bool) self::$isAdminArea;
		} //end if
		//--
		self::$isAdminArea = false;
		if(defined('SMART_FRAMEWORK_ADMIN_AREA')) {
			if(SMART_FRAMEWORK_ADMIN_AREA === true) {
				self::$isAdminArea = true;
			} //end if
		} //end if
		//--
		return (bool) self::$isAdminArea;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// if Prod Environment is ON will return TRUE, else will return FALSE
	public static function ifProdEnv() {
		//--
		if(self::$isProdEnv !== null) {
			return (bool) self::$isProdEnv;
		} //end if
		//--
		self::$isProdEnv = false;
		if(defined('SMART_FRAMEWORK_ENV')) {
			if((string)SMART_FRAMEWORK_ENV == 'prod') {
				self::$isProdEnv = true;
			} //end if
		} //end if
		//--
		return (bool) self::$isProdEnv;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// if Debug is ON will return TRUE, else will return FALSE
	public static function ifDebug() {
		//--
		if(self::$isDebugOn !== null) {
			return (bool) self::$isDebugOn;
		} //end if
		//--
		self::$isDebugOn = false;
		if(defined('SMART_FRAMEWORK_DEBUG_MODE')) {
			if(SMART_FRAMEWORK_DEBUG_MODE === true) {
				self::$isDebugOn = true;
			} //end if
		} //end if
		//--
		return (bool) self::$isDebugOn;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// if Internal Debug (Advanced Debug) is ON will return TRUE, else will return FALSE
	public static function ifInternalDebug() {
		//--
		if(self::$isInternalDebugOn !== null) {
			return (bool) self::$isInternalDebugOn;
		} //end if
		//--
		self::$isInternalDebugOn = false;
		if(defined('SMART_FRAMEWORK_INTERNAL_DEBUG')) {
			if(SMART_FRAMEWORK_INTERNAL_DEBUG === true) {
				self::$isInternalDebugOn = true;
			} //end if
		} //end if
		//--
		return (bool) self::$isInternalDebugOn;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// security: use this instead of header() which cannot contain \r \n \t \x0B \0 \f
	public static function outputHttpSafeHeader($value) {
		//--
		$value = (string) trim((string)$value);
		//--
		if((string)$value != '') {
			//--
			$value = (string) SmartFrameworkSecurity::FilterUnsafeString((string)$value); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered !
			$value = (string) Smart::normalize_spaces((string)$value); // security fix: avoid newline in header
			$value = (string) trim((string)$value);
			//--
			if((string)$value != '') {
				//--
				if(headers_sent()) {
					@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Trying to set header but Headers Already Sent with value of: '.$value, E_USER_WARNING);
					return;
				} //end if
				//--
				header((string)$value);
				//--
			} //end if
			//--
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// for Advanced use Only :: this function outputs !!! the HTTP NoCache / Expire Headers
	public static function outputHttpHeadersNoCache($expiration=-1, $modified=-1, $control='private') {
		//--
		if(self::$NoCacheHeadersSent !== false) {
			return; // this function can run more than once ...
		} //end if
		//--
		$expiration = (int) $expiration; // expire time, in seconds, since now
		$modified   = (int) $modified;
		switch((string)$control) {
			case 'public':
				$control = 'public';
				break;
			case 'private':
			default:
				$control = 'private';
		} //end switch
		//--
		if(!headers_sent()) {
			//--
			if(($expiration < 0) AND ($modified < 0)) { // default
				//--
				header('Cache-Control: no-cache'); // HTTP 1.1
				header('Pragma: no-cache'); // HTTP 1.0
				header('Expires: '.gmdate('D, d M Y', @strtotime('-1 year')).' '.date('H:i:s').' GMT'); // HTTP 1.0
				header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
				//--
			} else {
				//--
				if($expiration < 60) {
					$expiration = 60;
				} //end if
				$expires = (int) (time() + $expiration);
				//--
				$modified = (int) $modified; // last modification timestamp of the contents, in seconds, must be > 0 <= now
				if(($modified <= 0) OR ($modified > time())) {
					$modified = (int) time();
				} //end if
				//--
				header('Expires: '.gmdate('D, d M Y H:i:s', (int)$expires).' GMT'); // HTTP 1.0
				header('Pragma: cache'); // HTTP 1.0
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', (int)$modified).' GMT');
				header('Cache-Control: '.$control.', max-age='.(int)$expiration); // HTTP 1.1 (private will dissalow proxies to cache the content)
				//--
			} //end if else
			//--
		} else {
			//--
			@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Could not set No-Cache Headers (Expire='.$expiration.' ; Modified='.$modified.'), Headers Already Sent ...', E_USER_WARNING);
			//--
		} //end if else
		//--
		self::$NoCacheHeadersSent = true;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with OK HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesOK() {
		//--
		return (array) self::$HttpStatusCodesOK;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with REDIRECT HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesRDR() {
		//--
		return (array) self::$HttpStatusCodesRDR;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with ERROR HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesERR() {
		//--
		return (array) self::$HttpStatusCodesERR;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with ALL(OK,REDIRECT,ERROR) HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesALL() {
		//--
		return (array) array_merge(self::getHttpStatusCodesOK(), self::getHttpStatusCodesRDR(), self::getHttpStatusCodesERR());
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// This will run before loading the Smart.Framework and must not depend on it's classes
	// After all Request are processed this have to be called to lock and avoid re-processing the Request variables
	public static function Lock_Request_Processing() {
		//--
		self::$RequestProcessed = true; // this will lock the Request processing
		//--
		SmartFrameworkRegistry::lockRequestVar(); // this will lock the request registry
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// check if pathInfo is enabled (allowed)
	public static function PathInfo_Enabled() {
		//--
		$status = false;
		//--
		if(defined('SMART_SOFTWARE_URL_ALLOW_PATHINFO')) {
			//--
			switch((int)SMART_SOFTWARE_URL_ALLOW_PATHINFO) {
				case 3: // only index enabled
					if(!self::isAdminArea()) {
						$status = true;
					} //end if
					break;
				case 2: // both enabled: index & admin
					$status = true;
					break;
				case 1: // only admin enabled
					if(self::isAdminArea()) {
						$status = true;
					} //end if
					break;
				case 0: // none enabled
				default:
					// not enabled
			} //end switch
			//--
		} //end if
		//--
		return (bool) $status;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// This will run before loading the Smart.Framework and must not depend on it's classes
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Parse_Semantic_URL() {

		// PARSE SEMANTIC URL VIA GET v.180818
		// it limits the URL to 65535 and vars to 1000

		//-- check if can run
		if(self::$RequestProcessed !== false) {
			@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Cannot Re-Parse the Semantic URLs, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--

		//-- check overall
		if(defined('SMART_FRAMEWORK_SEMANTIC_URL_DISABLE') AND (SMART_FRAMEWORK_SEMANTIC_URL_DISABLE === true)) {
			return;
		} //end if
		//--

		//--
		if((self::PathInfo_Enabled() === true) AND (isset($_SERVER['PATH_INFO'])) AND ((string)$_SERVER['PATH_INFO'] != '')) {
			$semantic_url = '';
			$fix_pathinfo = (string) SmartFrameworkSecurity::FilterUnsafeString((string)trim((string)$_SERVER['PATH_INFO'])); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered !
			$sem_path_pos = strpos((string)$fix_pathinfo, '/~');
			if($sem_path_pos !== false) {
				$semantic_url = (string) '?'.substr((string)$fix_pathinfo, 0, $sem_path_pos);
				$path_url = (string) substr((string)$fix_pathinfo, ($sem_path_pos + 2));
				SmartFrameworkRegistry::setRequestPath(
					(string) ($path_url ? (string)$path_url : '/')
				) OR @trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Failed to register !path-info! variable', E_USER_WARNING);
			} //end if
		} else {
			$semantic_url = (string) (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
		} //end if
		//--
		if(strlen($semantic_url) > 65535) { // limit according with Firefox standard which is 65535 ; Apache standard is much lower as 8192
			$semantic_url = (string) substr($semantic_url, 0, 65535);
		} //end if
		//--
		if(strpos($semantic_url, '?/') === false) {
			return;
		} //end if
		//--

		//--
		$get_arr = (array) explode('?/', $semantic_url, 2); // separe 1st from 2nd by ?/ if set
		$location_str = (string) trim((string)(isset($get_arr[1]) ? $get_arr[1] : ''));
		$get_arr = (array) explode('&', $location_str, 2); // separe 1st from 2nd by & if set
		$location_str = (string) trim((string)(isset($get_arr[0]) ? $get_arr[0] : ''));
		$get_arr = array(); // cleanup
		//--

		//--
		if((string)$location_str != '') {
			//--
			$location_arx = (array) explode('/', (string)$location_str, 1001); // max is 1000, so separe them from the rest
			$cnt_arx = (int) count($location_arx);
			if($cnt_arx > 1000) {
				$cnt_arx = 1000;
			} //end if
			//--
			$location_arr = array();
			if(is_array($location_arx)) {
				for($i=0; $i<$cnt_arx; $i++) {
					if(((string)trim((string)$location_arx[$i]) != '') AND (array_key_exists($i+1, $location_arx)) AND ((string)trim((string)$location_arx[$i+1]) != '')) {
						$location_arx[$i+1] = (string) SmartFrameworkSecurity::urlVarDecodeStr((string)$location_arx[$i+1], false); // do not filter here, will filter later when exracting to avoid double filtering !
						$location_arx[$i+1] = (string) str_replace((string)rawurlencode('/'), '/', (string)$location_arx[$i+1]);
						$location_arr[(string)$location_arx[$i]] = (string) $location_arx[$i+1];
					} //end if
					$i += 1;
				} //end for
			} //end if
			//--
			//print_r($location_arr);
			if(is_array($location_arr)) {
				if(count($location_arr) > 0) {
					self::Extract_Filtered_Request_Get_Post_Vars($location_arr, 'SEMANTIC-URL');
				} //end if
			} //end if
			//--
		} //end if
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	// This will run before loading the Smart.Framework and must not depend on it's classes
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Extract_Filtered_Request_Get_Post_Vars($filter_____arr, $filter_____info) {

		// FILTER INPUT GET/POST VARIABLES v.20200121 (with collision fix and private space check)
		// This no more limits the input variables as it is handled via prior checks to PHP.INI: max_input_vars and max_input_nesting_level
		// If any of: GET / POST overflow the max_input_vars and max_input_nesting_level a PHP warning is issued !!
		// The max_input_vars applies separately to each of the input variables, includding array(s) keys
		// The max_input_nesting_level also must be at least 5

		//-- check if can run
		if(self::$RequestProcessed !== false) {
			@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Cannot Register Request/'.$filter_____info.' Vars, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--

		//--
		if(self::ifDebug()) {
			self::DebugRequestLog('========================= FILTER REQUEST:'."\n".date('Y-m-d H:i:s O')."\n".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n\n".'===== RAW REQUEST VARS:'."\n".'['.$filter_____info.']'."\n".print_r($filter_____arr, 1)."\n");
		} //end if
		//--

		//-- process
		if(is_array($filter_____arr)) {
			//--
			foreach($filter_____arr as $filter_____key => $filter_____val) {
				//--
				$filter_____key = (string) $filter_____key; // force string
				//--
				if(((string)trim((string)$filter_____key) != '') AND (SmartFrameworkSecurity::ValidateVariableName($filter_____key, true))) { // {{{SYNC-REQVARS-CAMELCASE-KEYS}}}
					//--
					if(is_array($filter_____val)) { // array
						//--
						if(self::ifDebug()) {
							self::DebugRequestLog('#EXTRACT-FILTER-REQUEST-VAR-ARRAY:'."\n".$filter_____key.'='.print_r($filter_____val,1)."\n");
						} //end if
						//--
						SmartFrameworkRegistry::setRequestVar(
							(string) $filter_____key,
							(array) SmartFrameworkSecurity::FilterGetPostCookieVars($filter_____val)
						) OR @trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Failed to register an array request variable: '.$filter_____key.' @ '.$filter_____info, E_USER_WARNING);
						//--
					} else { // string
						//--
						if(self::ifDebug()) {
							self::DebugRequestLog('#EXTRACT-FILTER-REQUEST-VAR-STRING:'."\n".$filter_____key.'='.$filter_____val."\n");
						} //end if
						//--
						SmartFrameworkRegistry::setRequestVar(
							(string) $filter_____key,
							(string) SmartFrameworkSecurity::FilterGetPostCookieVars($filter_____val)
						) OR @trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Failed to register a string request variable: '.$filter_____key.' @ '.$filter_____info, E_USER_WARNING);
						//--
					} //end if else
					//--
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--

		//--
		if(self::ifDebug()) {
			self::DebugRequestLog('========== #END REQUEST FILTER =========='."\n\n");
		} //end if
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	// This will run before loading the Smart.Framework and must not depend on it's classes
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Extract_Filtered_Cookie_Vars($filter_____arr) {

		// FILTER INPUT COOKIES VARIABLES v.181019 (with collision fix and private space check)

		//-- check if can run
		if(self::$RequestProcessed !== false) {
			@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Cannot Register Cookie Vars, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--

		//--
		$filter_____info = 'COOKIES';
		//--

		//--
		if(self::ifDebug()) {
			self::DebugRequestLog('========================= FILTER COOKIES:'."\n".date('Y-m-d H:i:s O')."\n".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n\n".'===== RAW COOKIE VARS:'."\n".'['.$filter_____info.']'."\n".print_r($filter_____arr, 1)."\n");
		} //end if
		//--

		//-- process
		if(is_array($filter_____arr)) {
			//--
			$num = 0;
			//--
			foreach($filter_____arr as $filter_____key => $filter_____val) {
				//--
				$num++;
				//--
				$filter_____key = (string) trim((string)$filter_____key); // force string + trim (for cookies use no validate var name ...)
				//--
				if(substr($filter_____key, 0, 11) != 'filter_____') { // avoid collisions with the variables in this function
					//--
					if((string)$filter_____key != '') {
						//--
						if(self::ifDebug()) {
							self::DebugRequestLog('#EXTRACT-FILTER-COOKIE-VAR:'."\n".$filter_____key.'='.$filter_____val."\n");
						} //end if
						SmartFrameworkRegistry::setCookieVar(
							(string) $filter_____key,
							(string) SmartFrameworkSecurity::FilterGetPostCookieVars($filter_____val)
						) OR @trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Failed to register a cookie variable: '.$filter_____key.' @ '.$filter_____info, E_USER_WARNING);
						//--
					} //end if
					//--
				} //end if
				//--
				if($num >= 1024) {
					@trigger_error(__CLASS__.'::'.__FUNCTION__.'() :: '.'Too many cookie variables detected. Stoped to register at: #'.$num, E_USER_WARNING);
					break;
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--

		//--
		if(self::ifDebug()) {
			self::DebugRequestLog('========== #END COOKIES FILTER =========='."\n\n");
		} //end if
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	// get the App Release Hash based on Framework Version.Release.ModulesRelease
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function getAppReleaseHash() {
		//--
		if((string)self::$AppReleaseHash == '') {
			$hash = (string) SmartHashCrypto::crc32b((string)SMART_FRAMEWORK_RELEASE_TAGVERSION.(string)SMART_FRAMEWORK_RELEASE_VERSION.(string)SMART_APP_MODULES_RELEASE, true); // get as b36
			self::$AppReleaseHash = (string) strtolower((string)$hash);
		} //end if
		//--
		return (string) self::$AppReleaseHash;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function SingleUser_Mode_Monitor() {
		//--
		if(is_file('.ht-sf-singleuser-mode')) { // here must be used the functions is_file() as the filesys lib is may yet initialized ...
			if(!headers_sent()) {
				http_response_code(503);
			} //end if
			die(SmartComponents::http_message_503_serviceunavailable('The Service is under Maintenance (SingleUser Mode), try again later ...'));
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function High_Load_Monitor() {
		//--
		if(is_array(self::$HighLoadMonitorStats)) {
			return (array) self::$HighLoadMonitorStats; // avoid re-run and serve from cache
		} //end if
		//--
		$tmp_sysload_avg = array();
		//--
		if(defined('SMART_FRAMEWORK_NETSERVER_MAXLOAD')) {
			$tmp_max_load = (int) SMART_FRAMEWORK_NETSERVER_MAXLOAD;
		} else {
			$tmp_max_load = 0;
		} //end if
		if($tmp_max_load > 0) { // run only if set to a value > 0
			if(function_exists('sys_getloadavg')) {
				$tmp_sysload_avg = (array) @sys_getloadavg();
				$tmp_sysload_avg[0] = (float) $tmp_sysload_avg[0];
				if($tmp_sysload_avg[0] > $tmp_max_load) { // protect against system overload over max
					if(!headers_sent()) {
						http_response_code(504); // gateway timeout
					} //end if
					Smart::log_warning('#SMART-FRAMEWORK-HIGH-LOAD-PROTECT#'."\n".'Smart.Framework // Web :: System Overload Protection: The System is Too Busy ... Try Again Later. The Load Averages reached the maximum allowed value by current settings ... ['.$tmp_sysload_avg[0].' of '.$tmp_max_load.']');
					die(SmartComponents::http_message_503_serviceunavailable('The Service is Too busy, try again later ...', SmartComponents::operation_warn('<b>Smart.Framework // Web :: System Overload Protection</b><br>The Load Averages reached the maximum allowed value by current settings ...', '100%')));
					return array();
				} //end if
			} //end if
		} //end if
		//--
		self::$HighLoadMonitorStats = (array) $tmp_sysload_avg;
		//--
		return (array) self::$HighLoadMonitorStats;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Redirection_Monitor() {
		//--
		if(!defined('SMART_FRAMEWORK_VERSION')) {
			if(!headers_sent()) {
				http_response_code(500);
			} //end if
			die('Smart Runtime // Redirection Monitor :: Requires Smart.Framework to be loaded ...');
			return;
		} //end if
		//--
		if(self::$RedirectionMonitorStarted !== false) {
			return; // avoid run after it was used by runtime
		} //end if
		self::$RedirectionMonitorStarted = true;
		//--
		$url_redirect = '';
		//--
		$the_current_url = SmartUtils::get_server_current_url();
		$the_current_script = SmartUtils::get_server_current_script();
		//--
		$is_disabled_frontent = (bool) ((defined('SMART_SOFTWARE_FRONTEND_DISABLED') && (SMART_SOFTWARE_FRONTEND_DISABLED === true)) ? true : false);
		$is_disabled_backend  = (bool) ((defined('SMART_SOFTWARE_BACKEND_DISABLED')  && (SMART_SOFTWARE_BACKEND_DISABLED === true))  ? true : false);
		//--
		if(
			($is_disabled_frontent === true) AND
			($is_disabled_backend  === true)
		) { // both frontend and backend are disabled, avoid circular redirect from below
			if(!headers_sent()) {
				http_response_code(500);
			} //end if
			die((string)SmartComponents::http_error_message('App Config ERROR', 'The FRONTEND and the BACKEND of this application are both DISABLED in the config/init ! ...'));
			return;
		} //end if
		if(($is_disabled_frontent === true) AND ((string)$the_current_script == 'index.php')) {
			$url_redirect = $the_current_url.'admin.php';
			if(isset($_SERVER['QUERY_STRING'])) {
				if((string)$_SERVER['QUERY_STRING'] != '') {
					$url_redirect .= (string) '?'.$_SERVER['QUERY_STRING'];
				} //end if
			} //end if
		} //end if
		if(($is_disabled_backend === true) AND ((string)$the_current_script == 'admin.php')) {
			$url_redirect = $the_current_url.'index.php';
			if(isset($_SERVER['QUERY_STRING'])) {
				if((string)$_SERVER['QUERY_STRING'] != '') {
					$url_redirect .= (string) '?'.$_SERVER['QUERY_STRING'];
				} //end if
			} //end if
		} //end if
		//--
		if(((string)$url_redirect == '') AND (isset($_SERVER['PATH_INFO']))) {
			//--
			if((string)$_SERVER['PATH_INFO'] != '') {
				//--
				if((string)$the_current_script == 'index.php') {
					if(self::PathInfo_Enabled() === true) {
						if(((string)$_SERVER['PATH_INFO'] != '/') AND (strpos((string)$_SERVER['PATH_INFO'], '/~') !== false)) { // avoid common mistake to use just a / after script.php + detect tilda path
							return;
						} //end if
					} //end if
				//	$the_current_script = ''; // reset index.php part of URL
				} elseif((string)$the_current_script == 'admin.php') {
					if(self::PathInfo_Enabled() === true) {
						if(((string)$_SERVER['PATH_INFO'] != '/') AND (strpos((string)$_SERVER['PATH_INFO'], '/~') !== false)) { // avoid common mistake to use just a / after script.php + detect tilda path
							return;
						} //end if
					} //end if
				} //end if
				//--
				$url_redirect = (string) $the_current_url.$the_current_script.'?'.$_SERVER['PATH_INFO'];
				//--
			} //end if
			//--
		} //end if
		//--
		if((string)$url_redirect != '') {
			//--
			$gopage = '<!DOCTYPE html>
			<!-- template :: RUNTIME REDIRECTION / PATH SUFFIX -->
			<html>
				<head>
					<meta charset="UTF-8">
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<meta http-equiv="refresh" content="3;URL='.Smart::escape_html($url_redirect).'">
					<title>301 Moved Permanently</title>
				</head>
				<body>
					<h1>301 Moved Permanently</h1>
					<h2>Redirecting to a valid URL ... wait ...</h2><br>
					<script type="text/javascript">setTimeout("self.location=\''.Smart::escape_js($url_redirect).'\'",1500);</script>
				</body>
			</html>';
			//--
			if(!headers_sent()) {
				http_response_code(301); // permanent redirect
				self::outputHttpSafeHeader('Location: '.$url_redirect); // force redirect
			} //end if
			die((string)$gopage);
			return;
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function SetVisitorEntropyIDCookie() {
		//--
		if(!defined('SMART_FRAMEWORK_VERSION')) {
			@http_response_code(500);
			die('Smart Runtime // Set Visitor Entropy ID Cookie :: Requires Smart.Framework to be loaded ...');
			return;
		} //end if
		//--
		if(defined('SMART_APP_VISITOR_COOKIE')) {
			@http_response_code(500);
			die('SetVisitorEntropyIDCookie :: SMART_APP_VISITOR_COOKIE must not be re-defined ...');
			return;
		} //end if
		//--
		$cookie = '';
		$wasset = false;
		//-- {{{SYNC-SMART-UNIQUE-COOKIE}}}
		$expire = 0;
		if(defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_LIFETIME')) {
			$expire = (int) SMART_FRAMEWORK_UNIQUE_ID_COOKIE_LIFETIME;
			if($expire <= 0) {
				$expire = 0;
			} //end if
		} //end if
		if((defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME')) AND (!defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_SKIP'))) {
			if((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME != '') {
				if(SmartFrameworkSecurity::ValidateVariableName((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME, true)) {
					$cookie = (string) trim((string)strtolower((string)SmartFrameworkRegistry::getCookieVar((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME)));
					if(((string)$cookie == '') OR (strlen((string)$cookie) != 40) OR (!preg_match('/^[a-f0-9]+$/', (string)$cookie))) {
						$entropy = (string) sha1((string)Smart::unique_entropy('uuid-cookie')); // generate a random unique key ; cookie was not yet set or is invalid
						SmartUtils::set_cookie((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME, (string)$entropy, (int)$expire);
						$cookie = (string) $entropy;
						$wasset = true;
					} //end if
				} else {
					Smart::raise_error(
						'#SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME#'."\n".'Invalid Value for constant: '.SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME,
						'App Init ERROR :: (See Error Log for More Details)'
					);
				} //end if
			} //end if
		} //end if
		//-- #end# sync
		if($wasset !== true) {
			SmartUtils::set_cookie((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME, (string)$cookie, (int)$expire);
		} //end if
		define('SMART_APP_VISITOR_COOKIE', (string)$cookie); // empty or cookie ID
		//--
	} //END FUNCTION
	//======================================================================


	//=====


	//======================================================================
	public static function DebugRequestLog($y_message) {
		//--
		if(!self::ifDebug()) {
			return;
		} //end if
		//--
		if(self::isAdminArea()) {
			$the_dir = 'tmp/logs/adm/';
			$the_log = $the_dir.date('Y-m-d@H').'-debug-requests.log';
		} else {
			$the_dir = 'tmp/logs/idx/';
			$the_log = $the_dir.date('Y-m-d@H').'-debug-requests.log';
		} //end if else
		//--
		if(is_dir((string)$the_dir)) { // here must be is_dir() and file_put_contents() as the smart framework libs are not yet initialized in this phase ...
			@file_put_contents((string)$the_log, $y_message."\n", FILE_APPEND | LOCK_EX); // init
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//==================================================================================
//================================================================================== CLASS END
//==================================================================================


//==================================================================================
//================================================================================== INTERFACE START
//==================================================================================


/**
 * Abstract Inteface Smart App Bootstrap
 * The extended object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE MAY NOT WORK as Expected !!!
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210401
 *
 */
interface SmartInterfaceAppBootstrap {

	// :: INTERFACE


	//=====
	/**
	 * App Bootstrap Init :: This function is automatically called when App bootstraps before Run(), by the smart runtime.
	 * By example it can be used to create the required dirs and files on local file system.
	 * THIS MUST BE EXTENDED TO HANDLE THE REQUIRED CODE EXECUTION AT THE BOOTSTRAP INIT SEQUENCE
	 * RETURN: -
	 */
	public static function Initialize();

	//=====
	/**
	 * App Bootstrap Run :: This function is automatically called when App bootstraps after Initialize(), by the smart runtime.
	 * By example it can be used to connect to a database, install monitor or other operations.
	 * THIS MUST BE EXTENDED TO HANDLE THE REQUIRED CODE EXECUTION AT THE BOOTSTRAP RUN SEQUENCE
	 * RETURN: -
	 */
	public static function Run();
	//=====


	//=====
	/**
	 * App Bootstrap Authenticate :: This function is automatically called when App bootstraps after Initialize() and Run(), by the middleware service only when the bootstrap sequence completed.
	 * By example it can be used to authenticate / login into any area: admin or index ... or as well not providing any authentication at all if not required so ...
	 * THIS MUST BE EXTENDED TO HANDLE THE REQUIRED CODE EXECUTION AT THE BOOTSTRAP AUTHENTICATE SEQUENCE
	 * RETURN: -
	 */
	public static function Authenticate(string $area);
	//=====


} //END INTERFACE


//==================================================================================
//================================================================================== INTERFACE END
//==================================================================================


//==================================================================================
//================================================================================== INTERFACE START
//==================================================================================


/**
 * Abstract Inteface Smart App Info
 * The extended object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE MAY NOT WORK as Expected !!!
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210401
 *
 */
interface SmartInterfaceAppInfo {

	// :: INTERFACE


	//=====
	/**
	 * Test if a specific App Template Exists
	 * RETURN: true or false
	 */
	public static function TestIfTemplateExists($y_template_name);
	//=====


	//=====
	/**
	 * Test if a specific App Module Exists
	 * RETURN: true or false
	 */
	public static function TestIfModuleExists($y_module_name);
	//=====


} //END INTERFACE

//==================================================================================
//================================================================================== INTERFACE END
//==================================================================================


// end of php code
