<?php
// [LIB - Smart.Framework / Security]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Environment and Security Compliance
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: Smart Environment - provides the methods for the Smart.Framework environment.
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
 * @depends     optional-constants: SMART_FRAMEWORK_DEBUG_MODE, SMART_FRAMEWORK_INTERNAL_DEBUG, SMART_FRAMEWORK_ENV, SMART_FRAMEWORK_ADMIN_AREA, SMART_FRAMEWORK_RUNTIME_MODE
 * @version     v.20250103
 * @package     @Core
 *
 */
final class SmartEnvironment { //  This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.

	// ::

	/**
	 * Connections Registry
	 * @var array
	 * @ignore
	 */
	public static $Connections = [];

	private static $isAdminArea 		= null; 	// admin area flag
	private static $isTaskArea 			= null; 	// task area flag

	private static $isDevMode 			= null; 	// dev env flag
	private static $isDebugOn 			= null; 	// debug flag
	private static $isInternalDebugOn 	= null; 	// internal debug flag
	private static $DebugMessages = [ 				// debug messages registry
		'stats' 			=> [],
		'optimizations' 	=> [],
		'extra' 			=> [],
		'db' 				=> [],
		'mail' 				=> [],
		'modules' 			=> []
	];


	//================================================================
	/**
	 * Returns TRUE if ATK (Auth Tokens) are Enabled
	 * Will test for the current area only
	 *
	 * @return 	BOOL						:: TRUE if enabled ; FALSE if not
	 */
	public static function isATKEnabled() : bool {
		//--
		$is_atk_enabled = 0;
		if(defined('SMART_SOFTWARE_AUTH_TOKENS')) {
			$is_atk_enabled = (int) intval(SMART_SOFTWARE_AUTH_TOKENS);
		} //end if
		//--
		if((int)$is_atk_enabled < 0) {
			$is_atk_enabled = 0;
		} elseif((int)$is_atk_enabled > 3) {
			$is_atk_enabled = 3;
		} //end if
		//--
		if((int)$is_atk_enabled == 0) { // 0: no area
			return false;
		} //end if
		//--
		if((int)$is_atk_enabled == 2) { // 2: all areas
			return true;
		} //end if
		//--
		if((self::isAdminArea() === true) || (self::isTaskArea() === true)) {
			return (bool) ((int)$is_atk_enabled == 1); // 1: only for adm/tsk area
		} else {
			return (bool) ((int)$is_atk_enabled == 3); // 3: only for idx area
		} //end if else
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns TRUE if 2FA is Enabled
	 * Will test for the current area only
	 *
	 * @return 	BOOL						:: TRUE if enabled ; FALSE if not
	 */
	public static function is2FAEnabled() : bool {
		//--
		$is_2fa_enabled = 0;
		if(defined('SMART_SOFTWARE_AUTH_2FA')) {
			$is_2fa_enabled = (int) intval(SMART_SOFTWARE_AUTH_2FA);
		} //end if
		//--
		if((int)$is_2fa_enabled < 0) {
			$is_2fa_enabled = 0;
		} elseif((int)$is_2fa_enabled > 3) {
			$is_2fa_enabled = 3;
		} //end if
		//--
		if((int)$is_2fa_enabled == 0) { // 0: no area
			return false;
		} //end if
		//--
		if((int)$is_2fa_enabled == 2) { // 2: all areas
			return true;
		} //end if
		//--
		if((self::isAdminArea() === true) || (self::isTaskArea() === true)) {
			return (bool) ((int)$is_2fa_enabled == 1); // 1: only for adm/tsk area
		} else {
			return (bool) ((int)$is_2fa_enabled == 3); // 3: only for idx area
		} //end if else
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns TRUE if 2FA is Required
	 * Will test for the current area only
	 *
	 * @return 	BOOL						:: TRUE if required ; FALSE if not
	 */
	public static function is2FARequired() : bool {
		//--
		if(self::is2FAEnabled() !== true) {
			return false;
		} //end if
		//--
		$is_2fa_required = 0;
		if(defined('SMART_SOFTWARE_AUTH_REQUIRED_2FA')) {
			$is_2fa_required = (int) intval(SMART_SOFTWARE_AUTH_REQUIRED_2FA);
		} //end if
		//--
		if((int)$is_2fa_required < 0) {
			$is_2fa_required = 0;
		} elseif((int)$is_2fa_required > 3) {
			$is_2fa_required = 3;
		} //end if
		//--
		if((int)$is_2fa_required == 0) { // 0: no area
			return false;
		} //end if
		//--
		if((int)$is_2fa_required == 2) { // 2: all areas
			return true;
		} //end if
		//--
		if((self::isAdminArea() === true) || (self::isTaskArea() === true)) {
			return (bool) ((int)$is_2fa_required == 1); // 1: only for adm/tsk area
		} else {
			return (bool) ((int)$is_2fa_required == 3); // 3: only for idx area
		} //end if else
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the Environment Area
	 *
	 * @return 	STRING						:: 'idx' for Index Area ; 'adm' for Admin Area ; 'tsk' for Task Area
	 */
	public static function getArea() : string {
		//--
		return (string) (self::isAdminArea() === true) ? ((self::isTaskArea() === true) ? 'tsk' : 'adm') : 'idx';
		//--
	} //end function
	//================================================================


	//======================================================================
	/**
	 * Test if Admin (service) Area
	 * For services running over admin.php / task.php will return TRUE ; for services running over index.php will return FALSE
	 *
	 * @return 	BOOLEAN			:: if is ADMIN area will return TRUE else will return FALSE
	 */
	public static function isAdminArea() : bool {
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
	 * Test if Task (service) Area (implies also to be isAdminArea() as well)
	 * Task area is an Admin area with some unrestricted features ; it must be restricted by IP to avoid security issues ; it is designed to be used mostly in development but can be used in production by restricting the access to this area using the init setting: SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS
	 * For services running over task.php will return TRUE ; for services running over admin.php / index.php will return FALSE
	 *
	 * @return 	BOOLEAN			:: if is TASK area will return TRUE else will return FALSE
	 */
	public static function isTaskArea() : bool {
		//--
		if(self::isAdminArea() !== true) {
			return false; // task area is a special area derived from admin area ONLY, would be unsafe to use it from index area ...
		} //end if
		//--
		if(self::$isTaskArea !== null) {
			return (bool) self::$isTaskArea;
		} //end if
		//--
		self::$isTaskArea = false;
		if(defined('SMART_FRAMEWORK_RUNTIME_MODE') AND (SMART_FRAMEWORK_RUNTIME_MODE === 'web.task')) { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
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
	public static function ifDevMode() : bool {
		//--
		if(self::$isDevMode !== null) {
			return (bool) self::$isDevMode;
		} //end if
		//--
		self::$isDevMode = false;
		if(defined('SMART_FRAMEWORK_ENV')) {
			if(SMART_FRAMEWORK_ENV === 'dev') { // 'dev' | 'prod'
				self::$isDevMode = true;
			} //end if
		} //end if
		//--
		return (bool) self::$isDevMode;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Test if DEBUG is ON
	 * Debug can be enabled on both: Production or Development Environments, by setting; define('SMART_FRAMEWORK_DEBUG_MODE', true); // init.php
	 * Using Debug in Production Environment must be enabled per one IP only that can have access to that feature to avoid expose sensitive data to the world !!!
	 * Ex: if(myIPIsDetected()) { define('SMART_FRAMEWORK_DEBUG_MODE', true); } // in init.php, for a Production Environment
	 *
	 * @return 	BOOLEAN			:: if Debug is ON will return TRUE, else will return FALSE
	 */
	public static function ifDebug() : bool {
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
	 * Ex: if(myIPIsDetected()) { define('SMART_FRAMEWORK_INTERNAL_DEBUG', true); } // in init.php, for a Production Environment
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	BOOLEAN			:: if Internal Debug (Advanced Debug) is ON will return TRUE, else will return FALSE
	 */
	public static function ifInternalDebug() : bool {
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
	/**
	 * Set a Debug Message
	 * $dbgmsg is MIXED: scalar OR aray (object or resource is NOT supported)
	 *
	 * @access 		private
	 * @internal
	 */
	public static function setDebugMsg(?string $area, ?string $context, $dbgmsg, ?string $opmode='') : void {
		//--
		if(!self::ifDebug()) {
			return;
		} //end if
		//--
		if(!$dbgmsg) {
			return;
		} //end if
		if(is_object($dbgmsg) OR is_resource($dbgmsg)) {
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
				trigger_error(__METHOD__.'()'."\n".'INVALID DEBUG AREA: '.$area."\n".'Message Content: '.print_r($dbgmsg,1), E_USER_NOTICE);
		} //end switch
		//--
		return;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Get Debug Messages for an area
	 *
	 * @access 		private
	 * @internal
	 */
	public static function getDebugMsgs(?string $area) : array {
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
				trigger_error(__METHOD__.'()'."\n".'INVALID DEBUG AREA: '.$area, E_USER_NOTICE);
				return array();
		} //end switch
		//--
		return array();
		//--
	} //END FUNCTION
	//======================================================================


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
		if(self::ifInternalDebug()) {
			if(self::ifDebug()) {
				self::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'Smart (Base) // Internal Data',
					'data' => 'Dump of Connections:'."\n".print_r(self::$Connections,1)
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
 * @depends 	SmartUnicode
 *
 * @version 	v.20240118
 * @package 	Application
 *
 */
final class SmartFrameworkSecurity {

	// ::
	// This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.


	//======================================================================
	/**
	 * Validate an URL variable name
	 *
	 * @param 	STRING 		$var_name 		:: The value to be tested
	 *
	 * @return 	0/1							:: 1 if Valid, 0 if Invalid
	 */
	public static function ValidateUrlVariableName(?string $var_name) : int {

		// VALIDATE INPUT (REQUEST / GET / POST / COOKIE) VARIABLE NAMES

		//--
		$var_name = (string) $var_name; // force string
		//--

		//--
		$regex_var_name = '/^[a-zA-Z0-9_\-]+$/'; // {{{SYNC-REGEX-URL-VARNAME}}}
		//--

		//-- init
		$out = 0;
		//-- validate characters (variable must not be empty, must not start with an underscore or a number
		if(((string)$var_name != '') AND (preg_match((string)$regex_var_name, (string)$var_name))) {
			$out = 1;
		} //end if else
		//-- corrections (variable name must be between 1 char and 128 chars)
		if((int)strlen((string)$var_name) < 1) {
			$out = 0;
		} elseif((int)strlen((string)$var_name) > 128) {
			$out = 0;
		} //end if
		//--

		//--
		return (int) $out;
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Validate a PHP variable name
	 *
	 * @param 	STRING 		$y_varname 					:: The value to be tested
	 * @param 	BOOLEAN 	$y_allow_upper_letters 		:: Default is TRUE; If set to FALSE will dissalow upper case letters
	 *
	 * @return 	0/1										:: 1 if Valid, 0 if Invalid
	 */
	public static function ValidateVariableName(?string $y_varname, bool $y_allow_upper_letters=true) : int {

		// VALIDATE PHP VARIABLE NAMES v.20210413

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
	/**
	 * Return a filtered string value for untrusted string (or similar, scalar or null) variables.
	 * It may be used for filtering insecure / untrusted variables.
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param MIXED 						$str_val	the input variable value
	 * @return STRING/NULL								the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterUnsafeString($str_val) : ?string { // $str_val is MIXED !
		//--
		return SmartUnicode::filter_unsafe_string($str_val); // mixed: NULL or STRING
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered values for GET/POST/REQUEST variables, using the FilterUnsafeString method
	 * It may be used for filtering insecure / untrusted string or array variables
	 * For array variables it also filters the keys
	 * When using the raw values from $_GET, $_POST, $_REQUEST - all the values should be always filtered prior to be used in PHP to avoid insecure characters.
	 * Important: All the REQUEST=GET+POST variables from Smart Framework Registry are already filtered, no need to filter them again, but if you are using any raw value from $_GET, $_POST, $_REQUEST it must be filtered !
	 *
	 * @param MIXED 											$value	the input variable value
	 * @return MIXED											the filtered value (if OBJECT or RESOURCE will return null)
	 */
	public static function FilterRequestVar($value) { // $value is MIXED !
		//--
		if(!isset($value)) {
			return null; // fix for Illegal string offset
		} //end if
		//--
		if(is_object($value) OR is_resource($value)) { // {{{SYNC-CONDITION-REQUEST-VAR-TYPES}}} objects or resources are not allowed to com from GET/POST/REQUEST
			//--
			$value = null; // invalid !! it comes from request
			//--
		} elseif(is_array($value)) { // array
			//--
			$arr = [];
			foreach($value as $kk => $vv) {
				if(is_object($vv) OR is_resource($vv)) { // objects or resources are not allowed to com from GET/POST/REQUEST
					$vv = null;
				} elseif(is_array($vv)) { // array
					$vv = (array) self::FilterRequestVar((array)$vv);
				} else { // nScalar
					$vv = (string) self::FilterUnsafeString((string)$vv);
				} //end if else
				$arr[self::FilterUnsafeString((string)$kk)] = $vv; // mixed
			} //end foreach
			$value = (array) $arr;
			$arr = null;
			//--
		} else { // nScalar
			//--
			$value = (string) self::FilterUnsafeString((string)$value);
			//--
		} //end if
		//--
		return $value; // mixed
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered value for a cookie from a COOKIE variable, using the FilterUnsafeString method
	 * It may be used for filtering the insecure / untrusted raw values from $_COOKIE
	 *
	 * @param STRING/NULL 						$str_val	the input variable value
	 * @return STRING/NULL									the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterCookieVar(?string $str_val) : ?string {
		//--
		if($str_val === null) {
			return null;
		} //end if
		//--
		return (string) self::FilterUnsafeString((string)$str_val);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered values for PATH_INFO server variable, using the FilterUnsafeString method, and apply trim
	 * It may be used for filtering the insecure / untrusted raw value of $_SERVER['PATH_INFO']
	 *
	 * @param MIXED 						$value		the input variable value
	 * @return STRING/NULL								the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterRequestPath($value) : ?string { // $value is MIXED !
		//--
		if(!isset($value)) {
			return null; // fix for Illegal string offset
		} //end if
		//--
		return (string) trim((string)self::FilterUnsafeString($value));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the url decoded (+/- filtered) variable from RAWURLENCODE / URLENCODE
	 * It may be used ONLY when working with RAW PATH INFO / RAW QUERY URLS !!!
	 * IMPORTANT: the $_GET and $_REQUEST are already decoded. Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.
	 *
	 * @param STRING 				$url_encoded_str_val		the input variable
	 * @param BOOLEAN 				$filter 					*Optional* Default to TRUE ; if FALSE will only decode but not filter variable ; DO NOT DISABLE FILTERING EXCEPT WHEN YOU CALL IT LATER EXPLICIT !!!
	 * @return STRING											the decoded +/- filtered value
	 */
	public static function DecodeAndFilterUrlVarString(?string $url_encoded_str_val, bool $filter=true) : string {
		//--
		$url_encoded_str_val = (string) urldecode((string)$url_encoded_str_val); // use urldecode() which decodes all % but also the + ; instead of rawurldecode() which does not decodes + !
		//--
		if($filter !== false) {
			$url_encoded_str_val = (string) self::FilterUnsafeString((string)$url_encoded_str_val);
		} //end if
		//--
		return (string) $url_encoded_str_val;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Prepare a safe value to be used with the header() function
	 * It will filter out all dangerous characters and will replace some control characters with spaces
	 * It willalso trim the string thus it may return an empty string if the passed value contains only invalid characters
	 *
	 * @param STRING 				$value						the input value
	 * @return STRING											the prepared value
	 */
	public static function PrepareSafeHeaderValue(?string $value) : string {
		//--
		$value = (string) trim((string)$value);
		if((string)$value == '') {
			return '';
		} //end if
		//--
		$value = (string) self::FilterUnsafeString((string)$value); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered !
		$value = (string) str_replace(["\r\n", "\r", "\n", "\t", "\x0B", "\0", "\f", "\x7F"], ' ', (string)$value); // {{{SYNC-NORMALIZE-SPACES}}} + DEL char ; security fix: avoid newline in header
		$value = (string) SmartUnicode::deaccent_str((string)$value); // HTTP headers doesn't support UTF-8. They officially support ISO-8859-1 only # https://www.w3.org/Protocols/rfc2616/rfc2616-sec2.html
		$value = (string) trim((string)$value);
		//--
		return (string) $value;
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
