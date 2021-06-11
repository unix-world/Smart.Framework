<?php
// [LIB - Smart.Framework / Registry]
// (c) 2006-2021 unix-world.org - all rights reserved
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
// 	* Resource Connections
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
 * Normally there is no need to use this class as the controllers can access methods of this class directly.
 *
 * @depends 	classes: SmartFrameworkSecurity
 *
 * @version 	v.20210611
 * @package 	Application
 *
 */
final class SmartFrameworkRegistry {

	// ::
	//  This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.

	/**
	 * The Registry Connections Container
	 * IMPORTANT: This can be used for very advanced development only to set or unset global connections ; this cannot be handled by set/get methods since some connections are objects thus must use this variable to store them ; if using get/set an object will register just a reference not store it directly !
	 * @var Array
	 */
	public static $Connections = []; // connections registry

	private static $DebugMessages = [ // debug messages registry
		'stats' 			=> [],
		'optimizations' 	=> [],
		'extra' 			=> [],
		'db' 				=> [],
		'mail' 				=> [],
		'modules' 			=> []
	];

	private static $RequestLock 		= false; 	// request locking flag
	private static $RequestPath 		= '';		// request path (from path-info)
	private static $RequestVars 		= []; 		// request registry
	private static $CookieVars  		= [];  		// cookie registry

	private static $isAdminArea 		= null; 	// admin area flag
	private static $isTaskArea 			= null; 	// task area flag
	private static $isProdEnv 			= null; 	// prod env flag
	private static $isDebugOn 			= null; 	// debug flag
	private static $isInternalDebugOn 	= null; 	// internal debug flag


	//===== Public Methods for Check various settings


	//======================================================================
	/**
	 * Test if Admin (service) Area
	 * For services running over admin.php / task.php will return TRUE ; for services running over index.php will return FALSE
	 *
	 * @return 	BOOLEAN			:: if is ADMIN area will return TRUE else will return FALSE
	 */
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
	/**
	 * Test if Task (service) Area
	 * Task area is an Admin area with some unrestricted features ; it must be restricted by IP to avoid security issues ; it is designed to be used mostly in development but can be used in production by restricting the access to this area using the init setting: SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS
	 * For services running over task.php will return TRUE ; for services running over admin.php / index.php will return FALSE
	 *
	 * @return 	BOOLEAN			:: if is TASK area will return TRUE else will return FALSE
	 */
	public static function isTaskArea() {
		//--
		if(self::$isTaskArea !== null) {
			return (bool) self::$isTaskArea;
		} //end if
		//--
		self::$isTaskArea = false;
		if(defined('SMART_FRAMEWORK_RUNTIME_MODE') AND ((string)SMART_FRAMEWORK_RUNTIME_MODE == 'web.task')) { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
			self::$isTaskArea = true;
		} //end if
		//--
		return (bool) self::$isTaskArea;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Test if Production Environment as set in init.php by SMART_FRAMEWORK_ENV
	 * Production Environment (SMART_FRAMEWORK_ENV = 'prod') differ from Development Environment (SMART_FRAMEWORK_ENV = 'dev') especially by the fact that Production Environment avoids to display any error (will just log errors and warnings and will not log notices)
	 * Using this method to detect if Production or Development Environment the code can have a dual behaviour Production vs Development
	 *
	 * @return 	BOOLEAN			:: if is Production Environment will return TRUE else will return FALSE (if FALSE it is always considered the Development Environment)
	 */
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
	/**
	 * Test if DEBUG is ON
	 * Debug can be enabled on both: Production or Development Environments, by setting; define('SMART_FRAMEWORK_DEBUG_MODE', true); // init.php
	 * Using Debug in Production Environment must be enabled per one IP only that can have access to that feature to avoid expose sensitive data to the world !!!
	 * Ex: if(myIPDetected()) { define('SMART_FRAMEWORK_DEBUG_MODE', true); } // in init.php, for a Production Environment
	 *
	 * @return 	BOOLEAN			:: if Debug is ON will return TRUE, else will return FALSE
	 */
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
	/**
	 * Test if INTERNAL DEBUG (Advanced Debug) is ON
	 * INTERNAL DEBUG expects that also DEBUG is ON, otherwise may behave in a wrong way ...
	 * Internal Debug can be enabled on both: Production or Development Environments, by setting; define('SMART_FRAMEWORK_INTERNAL_DEBUG', true); // init.php
	 * Using Debug in Production Environment must be enabled per one IP only that can have access to that feature to avoid expose sensitive data to the world !!!
	 * Ex: if(myIPDetected()) { define('SMART_FRAMEWORK_INTERNAL_DEBUG', true); } // in init.php, for a Production Environment
	 *
	 * @return 	BOOLEAN			:: if Internal Debug (Advanced Debug) is ON will return TRUE, else will return FALSE
	 */
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


	//===== Public Methods for REQUEST and COOKIES ; some of them are hidden as they are used only internally, should be not exposed in development


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
	public static function setRequestPath($value) {
		//--
		if(self::$RequestLock !== false) { // check if can run
			@trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
		//--
		$value = (string) SmartFrameworkSecurity::FilterRequestPath($value);
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
	public static function getRequestPath() {
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
	public static function setRequestVar($key, $value) {
		//--
		if(self::$RequestLock !== false) { // check if can run
			@trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Vars, Registry is already locked !', E_USER_WARNING);
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
	 * No need to use this except for the cookie registration process which is automatically done at bootstrap time
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function issetRequestVar($key) {
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
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
	public static function getRequestVar(?string $key, $defval=null, $type='') { // {{{SYNC-REQUEST-DEF-PARAMS}}}
		//--
		$key = (string) trim((string)$key);
		if((string)$key == '') {
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
					@trigger_error(__METHOD__.'() // Invalid Request Variable ['.$key.'] Type: '.$type, E_USER_WARNING);
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
	public static function getRequestVars() {
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
	public static function setCookieVar($key, $value) {
		//--
		if(self::$RequestLock !== false) { // check if can run
			@trigger_error(__METHOD__.'() :: '.'Cannot Re-Register Vars, Registry is already locked !', E_USER_WARNING);
			return false; // avoid run after registry was already locked
		} //end if
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
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function issetCookieVar($key) {
		//--
		$key = (string) trim((string)$key);
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
	public static function getCookieVar($key) {
		//--
		$key = (string) trim((string)$key);
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
	public static function getCookieVars() {
		//--
		return (array) self::$CookieVars; // array
		//--
	} //END FUNCTION
	//======================================================================


	//===== Internal Only


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
	public static function registerFilteredRequestVars(?array $arr, ?string $info) {
		//-- check if can run
		if(self::$RequestLock !== false) {
			@trigger_error(__METHOD__.'() :: '.'Cannot Register Request/'.$info.' Vars, Registry is already locked !', E_USER_WARNING);
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
						) OR @trigger_error(__METHOD__.'() :: '.'Failed to register an array request variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
						//--
					} else { // string
						//--
						self::setRequestVar(
							(string) $key,
							(string) $val
						) OR @trigger_error(__METHOD__.'() :: '.'Failed to register a string request variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
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
	public static function registerFilteredCookieVars(?array $arr) {
		//-- check if can run
		if(self::$RequestLock !== false) {
			@trigger_error(__METHOD__.'() :: '.'Cannot Register Cookie Vars, Registry is already locked !', E_USER_WARNING);
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
					) OR @trigger_error(__METHOD__.'() :: '.'Failed to register a cookie variable: `'.$key.'` @ `'.$info.'`', E_USER_WARNING);
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
	 * Locks the Request Registry after parsing the GET / POST / COOKIE / PATH_INFO to avoid security issues after parsing those variables
	 * IMPORTANT: This is automatically used by the service registrations and never should be used otherwise
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	BOOLEAN					:: TRUE if SUCCESS, FALSE if FAIL
	 *
	 */
	public static function lockRequestRegistry() {
		//--
		if(self::$RequestLock !== false) { // check if can run
			@trigger_error(__METHOD__.'() :: '.'Registry is already locked !', E_USER_WARNING);
			return; // avoid run after registry was already locked
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
	 * Get Debug Messages for an area
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
				@trigger_error(__METHOD__.'()'."\n".'INVALID DEBUG AREA: '.$area, E_USER_NOTICE);
				return array();
		} //end switch
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Set a Debug Message
	 *
	 * @access 		private
	 * @internal
	 */
	public static function setDebugMsg($area, $context, $dbgmsg, $opmode='') {
		//--
		if(!self::ifDebug()) {
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
			$context 	= (string) trim((string)($arr[0] ?? ''));
			$subcontext = (string) trim((string)($arr[1] ?? ''));
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
				@trigger_error(__METHOD__.'()'."\n".'INVALID DEBUG AREA: '.$area."\n".'Message Content: '.print_r($dbgmsg,1), E_USER_NOTICE);
		} //end switch
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Register to Internal Cache
	 *
	 * @access 		private
	 * @internal
	 */
	public static function registerInternalCacheToDebugLog() {
		//--
		if(self::ifInternalDebug()) {
			if(self::ifDebug()) {
				self::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartFrameworkRegistry // Internal Data',
					'data' => 'Dump of Request Lock: ['.print_r(self::$RequestLock,1).']'."\n".'Dump of Request Vars Keys: '.print_r(array_keys((array)self::$RequestVars),1)."\n".'Dump of Connections:'."\n".print_r(self::$Connections,1)
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
