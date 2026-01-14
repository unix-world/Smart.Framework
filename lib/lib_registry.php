<?php
// [LIB - Smart.Framework / Registry]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Registry (Data Records):
// 	* Input data: REQUEST (GET / POST / PATH) / COOKIES
// 	* Debug Data
//======================================================

// [PHP8]

array_map(function($const){ if(!defined((string)$const)) { @http_response_code(500); die('A required RUNTIME constant has not been defined: '.$const); } }, ['SMART_ERROR_LOG_MANAGEMENT', 'SMART_FRAMEWORK_RELEASE_TAGVERSION', 'SMART_FRAMEWORK_RELEASE_VERSION', 'SMART_FRAMEWORK_RELEASE_URL', 'SMART_FRAMEWORK_RELEASE_NAME']);

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class Smart.Framework Registry
 * It may be used anywhere inside Smart.Framework or by Plugins and Application Modules.
 *
 * @depends 	classes: SmartFrameworkSecurity, SmartEnvironment
 *
 * @version 	v.20260114
 * @package 	Application
 *
 */
final class SmartFrameworkRegistry {

	// ::
	//  This class have to be able to run before loading the Smart.Framework and must not depend on as few as possible classes.


	private static $ServerLock 			= false; 	// server vars locking flag
	private static $ServerVars 			= [];		// server vars registry from $_SERVER
	private static $RequestLock 		= false; 	// request locking flag
	private static $RequestPath 		= '';		// request path (from path-info)
	private static $RequestVars 		= []; 		// request registry
	private static $CookieVars  		= [];  		// cookie registry


	//===== Public Methods for SERVER, REQUEST and COOKIES ; some of them are hidden as they are used only internally, should be not exposed in development


	//======================================================================
	/**
	 * Set a Server Variable into Registry
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function setServerVar(?string $key, ?string $value) : bool {
		//--
		if(self::$ServerLock !== false) { // check if can run
			trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Server Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//--
		$key = (string) strtoupper((string)trim((string)$key)); // the keys in $_SERVER can contain other characters than valid variable names, do not validate as variable name !
		if((string)$key == '') {
			return false;
		} //end if
		//--
		if((string)$key == 'PHP_AUTH_PW') {
			self::$ServerVars[(string)$key] = (string) base64_encode((string)$value); // preserve as is, encode as base64 to prevent display in clear !!!
		} else {
			self::$ServerVars[(string)$key] = (string) trim((string)SmartFrameworkSecurity::FilterUnsafeString((string)$value)); // only strings are supported
		} //end if else
		//--
		return true; // OK
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Check if a Server Variable is set into Registry
	 *
	 * @return 	BOOL						:: TRUE if the variable exists, FALSE otherwise
	 */
	public static function issetServerVar(?string $key) : bool {
		//--
		$key = (string) strtoupper((string)trim((string)$key));
		if((string)$key == '') {
			return false;
		} //end if
		//--
		if(array_key_exists((string)$key, self::$ServerVars)) { // for server vars if key exists is considered TRUE
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get a Server Variable from the Registry for a key
	 * This will get a value from $_SERVER but but filtered with SmartFrameworkSecurity::FilterUnsafeString to be UTF-8 compliant and avoid dangerous characters ; $_SERVER is not filtered, is raw !
	 * @return 	STRING/NULL						:: the safe filtered value of $_SERVER[$key]
	 */
	public static function getServerVar(?string $key) : ?string {
		//--
		$key = (string) strtoupper((string)trim((string)$key));
		if((string)$key == '') {
			return null;
		} //end if
		//--
		$val = null; // init with the default value :: null
		if(self::issetServerVar((string)$key) === true) {
			$val = (string) self::$ServerVars[(string)$key]; // use the value from server vars :: string
		} //end if
		//--
		return $val; // mixed
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get all the Server Variables from the Registry
	 *
	 * @return 	ARRAY			:: an associative array with all the server vars from $_SERVER but filtered with SmartFrameworkSecurity::FilterUnsafeString to be UTF-8 compliant and avoid dangerous characters ; $_SERVER is not filtered, is raw !
	 */
	public static function getServerVars() : array {
		//--
		return (array) self::$ServerVars; // array
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Set the Special Request Path into Registry, handled by Smart.Framework in combination with Semantic URLS
	 * This is a special feature inside Smart.Framework and is not the real value of $_SERVER['PATH_INFO'] !!!
	 * This is intended for extra running services such as WebDAV, CalDAV or CardDAV ...
	 *
	 * This is different than the value of $_SERVER['PATH_INFO'] as it will get just the part after the first occurence of the `/~` in the path
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function setRequestPath(?string $value) : bool {
		//--
		if(self::$RequestLock !== false) { // check if can run
			trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//--
		$value = (string) SmartFrameworkSecurity::FilterRequestPath((string)$value);
		//--
		$path_url = '';
		if(((string)$value != '')) {
			$fix_pathinfo = (string) $value; // variables from PathInfo are already URL Decoded, so must be ONLY Filtered !
			$sem_path_pos = strpos((string)$fix_pathinfo, '/~');
			if(($sem_path_pos !== false) AND ((int)$sem_path_pos >= 0)) {
				$path_url = (string) substr((string)$fix_pathinfo, ((int)$sem_path_pos + 2));
			} //end if
		} //end if
		$path_url = (string) trim((string)$path_url);
		//--
		self::$RequestPath = (string) (((string)$path_url != '') ? $path_url : '/');
		//--
		return true; // OK
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get the Special Request Path into Registry, handled by Smart.Framework in combination with Semantic URLS
	 * This is a special feature inside Smart.Framework and is not the real value of $_SERVER['PATH_INFO'] !!!
	 * This is intended for extra running services such as WebDAV, CalDAV or CardDAV ...
	 *
	 * This is different than the value of $_SERVER['PATH_INFO'] as it will get just the part after the first occurence of the `/~` in the path
	 * If need to work directly with the full value returned by $_SERVER['PATH_INFO'] don't forget to use the value of it filtered as SmartFrameworkSecurity::FilterRequestPath($_SERVER['PATH_INFO']);
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	STRING										:: The request path
	 */
	public static function getRequestPath() : string {
		//--
		return (string) self::$RequestPath;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Set a Request Variable into Registry
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function setRequestVar(?string $key, $value) : bool {
		//--
		// input $value is MIXED: string|array ; but be flexible and allow all the scalar types and null
		//--
		if(is_object($value) OR is_resource($value)) { // {{{SYNC-CONDITION-REQUEST-VAR-TYPES}}}
			trigger_error(__METHOD__.'() :: '.'Invalid Variable Type to Register: Object or Resource !', E_USER_WARNING);
			return false; // see above what types are allowed
		} //end if
		//--
		if(self::$RequestLock !== false) { // check if can run
			trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//--
		$key = (string) trim((string)$key);
		if(((string)$key == '') OR (!SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
			return false;
		} //end if
		//--
		self::$RequestVars[(string)$key] = SmartFrameworkSecurity::FilterRequestVar($value); // mixed, supports only array and nScalar
		//--
		return true; // OK
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Check if a Request Variable is set into Registry
	 *
	 * @return 	BOOL						:: TRUE if the variable exists, FALSE otherwise
	 */
	public static function issetRequestVar(?string $key) : bool {
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return false;
		} //end if
		//--
		if((array_key_exists((string)$key, self::$RequestVars)) AND (isset(self::$RequestVars[(string)$key])) AND ((is_array(self::$RequestVars[(string)$key])) OR ((string)self::$RequestVars[(string)$key] != ''))) { // if is set and (array or non-empty string) ; numbers from request comes as string too
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get a Request Variable that come from GET or POST
	 * See the method description for SmartFrameworkRegistry::getRequestVars() to sse how the GET / POST variables are registered, in which order, which have precedence for more details
	 * Shortly, this is a union between $_GET and $_POST ; $_GET are registered first ; $_POST after ; $_POST will ot rewrite $_GET (security !!)
	 * Difference between this method and working directly with $_GET or $_POST is that this method returns safe filtered values of $_GET or $_POST variables ...
	 * If need to work directly with $_GET or $_POST or $_REQUEST don't forget to use the value of them filtered with SmartFrameworkSecurity::FilterRequestVar($_GET['something']); // or SmartFrameworkSecurity::FilterRequestVar($_POST['something_else']); // or SmartFrameworkSecurity::FilterRequestVar($_REQUEST['something_else']);
	 *
	 * @param 	STRING 		$key		:: The name (key) of the GET or POST variable (if the variable is set in both GET and POST, the GPC as set in PHP.INI sequence will overwrite the GET with POST, thus the POST value will be get).
	 * @param	MIXED		$defval		:: The default value (if a type is set must be the same type) of that variable in the case was not set in the Request (GET/POST). By default it is set to null.
	 * @param	ENUM		$type		:: The type of the variable ; Default is '' (no enforcing). This can be used to enforce a type for the variable as: ['enum', 'list', 'of', 'allowed', 'values'], 'array', 'string', 'boolean', 'integer', 'integer+', 'integer-', 'decimal1', 'decimal2', 'decimal3', 'decimal4', 'numeric'.
	 *
	 * @return 	MIXED					:: The value of the choosen Request (GET/POST) variable
	 */
	public static function getRequestVar(?string $key, $defval=null, $type='') { // : MIXED ! ; {{{SYNC-REQUEST-DEF-PARAMS}}}
		//--
		// $defval and $type are MIXED ; output is MIXED
		//--
		if(is_object($defval)) { // safety protection
			return null;
		} //end if
		if(is_object($type)) { // safety protection
			return null;
		} //end if
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return null;
		} //end if
		//--
		if(self::issetRequestVar((string)$key) === true) {
			$val = self::$RequestVars[(string)$key]; // use the value from request :: mixed
		} else {
			$val = $defval; // mixed: init with the default value
		} //end if
		//--
		if(is_array($type)) { // # if $type is array, then cast is ENUM LIST, it must contain an array with all allowed values as strings #
			//--
			if((int)Smart::array_type_test($type) != 1) { // must be list not associative
				$type = [];
			} //end if
			//--
			if(((string)$val != '') AND (in_array((string)$val, (array)$type))) {
				$val = (string) strval($val); // force string
			} else {
			//	$val = ''; // set as empty string
				$val = $defval;
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
						$val = []; // set as empty array
					} else {
						$val = (array) $val; // force array
					} //end if else
					break;
				case 'string':
					$val = (string) strval($val);
					break;
				case 'boolean':
					$val = (string) strtolower((string)strval($val)); // {{{SYNC-SMART-BOOL-GET-EXT}}}
					if(((string)$val == 'true') OR ((string)$val == 't')) {
						$val = true;
					} elseif(((string)$val == 'false') OR ((string)$val == 'f')) {
						$val = false;
					} else {
						$val = (bool) $val;
					} //end if else
					break;
				case 'integer':
					$val = (int) intval($val);
					break;
				case 'integer+':
					$val = (int) intval($val);
					if($val < 0) { // {{{SYNC-SMART-INT+}}}
						$val = 0;
					} //end if
					break;
				case 'integer-':
					$val = (int) intval($val);
					if($val > 0) { // {{{SYNC-SMART-INT-}}}
						$val = 0;
					} //end if
					break;
				case 'decimal1':
					$val = (string) number_format(((float)floatval($val)), 1, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal2':
					$val = (string) number_format(((float)floatval($val)), 2, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal3':
					$val = (string) number_format(((float)floatval($val)), 3, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'decimal4':
					$val = (string) number_format(((float)floatval($val)), 4, '.', ''); // {{{SYNC-SMART-DECIMAL}}}
					break;
				case 'numeric':
					$val = (float) floatval($val);
					break;
				case 'mixed': // mixed variable types, can vary by context, leave as is
				case 'raw': // raw, alias for mixed, leave as is
				case '': // no explicit format (take as raw / mixed)
					// return as is ... (in this case extra validations have to be done explicit in the controller)
					break;
				default:
					trigger_error(__METHOD__.'() // Invalid Request Variable ['.$key.'] Type: `'.print_r($type,1).'`', E_USER_WARNING);
					return null;
			} //end switch
			//--
		} //end if else
		//--
		return $val; // mixed
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get all the request Request Variables from the Registry
	 * It contains the filtered union between $_GET and $_POST variables registered into this order: $_GET first and $_POST after
	 * If $_POST contain a variable with the same name as in $_GET it will not rewrite the first one, will be dropped for security reasons
	 * Unifying GET + POST is an easy way to work with REQUEST input variables as is not important if the variables come from GET or POST (sometimes even with POST method some variables can come from GET others from POST)
	 * From the security point of view make no difference ... POST variables are not more secure than GET variables !
	 * But if you wish to work with specific $_GET or $_POST or $_REQUEST see the notes from method getRequestVar()
	 *
	 * @return 	ARRAY			:: an associative array with all the ($_GET + $_POST) request variables, except cookies ; this may differ from $_GET / $_POST or $_REQUEST or  as this is filtered to be UTF-8 compliant and avoid dangerous characters ; $_GET / $_POST or $_REQUEST are not filtered, they are raw
	 *
	 *
	 */
	public static function getRequestVars() : array {
		//--
		return (array) self::$RequestVars; // array
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Set a Cookie Variable into Registry
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function setCookieVar(?string $key, ?string $value) : bool {
		//--
		// do not check if request is locked ; cookies must be set if needed later into this registry by Smart.Framework internal methods only ...
		//--
		$key = (string) trim((string)$key);
		if(((string)$key == '') OR (!SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
			return false;
		} //end if
		//--
		self::$CookieVars[(string)$key] = (string) SmartFrameworkSecurity::FilterCookieVar($value); // cookie vars can be only string types (also numeric will be stored as string)
		//--
		return true; // OK
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Check if a Cookie Variable is set into Registry
	 * No need to use this except for the cookie registration process which is automatically done at bootstrap time
	 * To check if a cookie was set use instead: SmartUtils::isset_cookie()
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function issetCookieVar(?string $key) : bool {
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return false;
		} //end if
		//--
		if((array_key_exists((string)$key, self::$CookieVars)) AND (isset(self::$CookieVars[(string)$key]))) { // if is set and not null ; cookies are considered to be set if they exist and non-null
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get a Cookie Variable from the Registry for a cookie key
	 * It will include only the input cookie value (if was set) that come from request and if value changed meanwhile will not reflect here
	 * To get the realistic value for a cookie (that might have been changed from the request bootstrap to a specific moment) use SmartUtils::get_cookie()
	 * If need to work directly with $_COOKIE don't forget to use the value of it filtered with SmartFrameworkSecurity::FilterCookieVar($_COOKIE['myCookie']);
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function getCookieVar(?string $key) : ?string {
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
			return null;
		} //end if
		//--
		$val = null; // init with the default value :: null
		if(self::issetCookieVar((string)$key) === true) {
			$val = (string) self::$CookieVars[(string)$key]; // use the value from cookies :: string
		} //end if
		//--
		return $val; // mixed: string or null
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get all the Cookie Variables from the Registry
	 * It will include only the input cookies that come from request and with those values and not the cookies set after that or the new values if changed
	 * To get the realistic value for a cookie (that might have been changed from the request bootstrap to a specific moment) use SmartUtils::get_cookie()
	 * IMPORTANT: this method may be misleading and should be avoided to be used ... as does not contains the updated values of the cookies
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	ARRAY			:: an associative array with all the cookies ; this may differ from $_COOKIE as this is filtered to be UTF-8 compliant and avoid dangerous characters ; $_COOKIE is not filtered, is raw !
	 *
	 */
	public static function getCookieVars() : array {
		//--
		return (array) self::$CookieVars; // array
		//--
	} //END FUNCTION
	//======================================================================


	//===== Internal Only


	//======================================================================
	/**
	 * This is for extract the SERVER variables and register them into the registry
	 * IMPORTANT: THIS FUNCTION IS FOR INTERNAL USE ONLY
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	ARRAY 		$arr		:: The $_SERVER array
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function registerFilteredServerVars(?array $arr) : bool {
		//-- check if can run
		if(self::$ServerLock !== false) {
			trigger_error(__METHOD__.'() :: '.'Cannot Register Server Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//-- process
		$info = 'SERVER';
		//--
		if(is_array($arr)) {
			//--
			foreach($arr as $key => $val) {
				//--
				$key = (string) $key; // force string
				//--
				if((string)trim((string)$key) != '') { // {{{SYNC-REQVARS-VALIDATION}}}
					//--
					self::setServerVar(
						(string) $key,
						(string) $val
					) OR trigger_error(__METHOD__.'() :: '.'Failed to register a server variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
					//--
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * This is for extract the GET / POST variables and register them into the registry
	 * IMPORTANT: THIS FUNCTION IS FOR INTERNAL USE ONLY
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	ARRAY 		$arr		:: The $_GET or $_POST array
	 * @param	STRING		$info		:: The description of what variables are extracted ; Ex: 'GET' or 'POST'
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function registerFilteredRequestVars(?array $arr, ?string $info) : bool {
		//-- check if can run
		if(self::$RequestLock !== false) {
			trigger_error(__METHOD__.'() :: '.'Cannot Register Request/'.$info.' Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//-- process
		if(is_array($arr)) {
			//--
			foreach($arr as $key => $val) {
				//--
				$key = (string) $key; // force string
				//--
				if(((string)trim((string)$key) != '') AND (SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
					//--
					if(is_array($val)) { // array
						//--
						self::setRequestVar(
							(string) $key,
							(array)  $val
						) OR trigger_error(__METHOD__.'() :: '.'Failed to register an array request variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
						//--
					} else { // string
						//--
						self::setRequestVar(
							(string) $key,
							(string) $val
						) OR trigger_error(__METHOD__.'() :: '.'Failed to register a string request variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
						//--
					} //end if else
					//--
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * This is for extract the COOKIE variables and register them into the registry
	 * IMPORTANT: THIS FUNCTION IS FOR INTERNAL USE ONLY
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	ARRAY 		$arr		:: The $_COOKIE array
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function registerFilteredCookieVars(?array $arr) : bool {
		//-- check if can run
		if(self::$RequestLock !== false) {
			trigger_error(__METHOD__.'() :: '.'Cannot Register Cookie Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//-- process
		$info = 'COOKIES';
		//--
		if(is_array($arr)) {
			//--
			foreach($arr as $key => $val) {
				//--
				$key = (string) $key; // force string
				//--
				if(((string)trim((string)$key) != '') AND (SmartFrameworkSecurity::ValidateUrlVariableName((string)$key))) { // {{{SYNC-REQVARS-VALIDATION}}}
					//--
					self::setCookieVar(
						(string) $key,
						(string) $val
					) OR trigger_error(__METHOD__.'() :: '.'Failed to register a cookie variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
					//--
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Locks the Server vars Registry after parsing the SERVER vars to avoid security issues after parsing those variables
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function lockServerRegistry() : ?bool {
		//--
		if(self::$ServerLock !== false) { // check if can run
			trigger_error(__METHOD__.'() :: '.'Registry is already locked !', E_USER_WARNING);
			return null; // failed, already locked ; avoid run after registry was already locked
		} //end if
		//--
		self::$ServerLock = true;
		//--
		return (bool) self::$ServerLock;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Locks the Request Registry after parsing the GET / POST / COOKIE / PATH_INFO to avoid security issues after parsing those variables
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function lockRequestRegistry() : ?bool {
		//--
		if(self::$RequestLock !== false) { // check if can run
			trigger_error(__METHOD__.'() :: '.'Registry is already locked !', E_USER_WARNING);
			return null; // failed, already locked ; avoid run after registry was already locked
		} //end if
		//--
		self::$RequestLock = true;
		//--
		return (bool) self::$RequestLock;
		//--
	} //END FUNCTION
	//======================================================================


	//===== Debugging


	//======================================================================
	/**
	 * Register to Internal Cache
	 *
	 * @access 		private
	 * @internal
	 */
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartFrameworkRegistry // Internal Data',
					'data' => 'Dump of Request Lock: ['.print_r(self::$RequestLock,1).']'."\n".'Dump of Request Vars Keys: '.print_r(array_keys((array)self::$RequestVars),1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
