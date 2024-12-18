<?php
// [LIB - Smart.Framework / Plugins / Session Manager]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Session Manager
// DEPENDS:
//	* Smart::
//	* SmartHashCrypto::
//	* SmartFileSystem::
//	* SmartUtils::
//	* SmartPersistentCache::
// DEPENDS-EXT: PHP Session Module
//======================================================
//#NOTICE: GC is controlled via
//ini_get('session.gc_maxlifetime');
//ini_get('session.gc_divisor');
//ini_get('session.gc_probability');
//======================================================

// [PHP8]


//--
if(!function_exists('session_start')) {
	@http_response_code(500);
	die('ERROR: PHP Session Module is required for the Smart.Framework');
} //end if
//--

// [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// NOTICE: sessions cleanup will use the Session GC
// INFO: if the SmartAbstractCustomSession is extended as SmartCustomSession it will detect and use it
// This session type have a very advanced mechanism based on IP and browser signature to protect against session forgery.


/**
 * Class: SmartSession - provides an Application Session Container.
 *
 * Depending on the Smart.Framework INIT settings, it can use [files] based session or [user] custom session (example: Redis based session).
 *
 * <code>
 * // ## DO NOT USE directly the $_SESSION because session may not be started automatically !!! It starts on first use only ... ##
 * // # SAMPLE USAGE #
 * //--
 * SmartSession::set('MyVariable', 'test'); // register a variable to session
 * echo SmartSession::get('MyVariable'); // will get and echo just the $_SESSION['MyVariable']
 * print_r(SmartSession::get()); // will get and prin_r all $_SESSION
 * SmartSession::set('MyVariable', null); // unregister (unset) a variable from session
 * //--
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	extensions: PHP Session Module ; classes: Smart, SmartUtils
 * @version 	v.20231103
 * @package 	Application:Plugins:Session
 *
 */
final class SmartSession {

	// ::

	private static $started = false; 	// semaphore that session start was initiated to avoid re-run of start() ; on start() the session can start (active) or not ; if successful started (active) will set the $active != 0
	private static $active = 0; 		// 0 if inactive or time() if session successful started and active


	//==================================================
	/**
	 * [PUBLIC] Check if Session is Active
	 *
	 * returns BOOLEAN TRUE if active, FALSE if not
	 */
	public static function active() {
		//--
		if(self::$active === 0) {
			return false;
		} else {
			return true;
		} //end if else
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	/**
	 * [PUBLIC] Session Get Variable
	 *
	 * @param MIXED $yvariable 		variable name to get a specific variable or NULL to get all session data
	 *
	 * @return MIXED $_SESSION (if $yvariable) or $_SESSION[$yvariable] or null if not found or the $yvariable is empty string
	 */
	public static function get($yvariable=null) {
		//--
		// if $yvariable is NULL will return the full $_SESSION
		if(!Smart::is_nscalar($yvariable)) {
			return null;
		} //end if
		//--
		self::start(); // start session if not already started
		//--
		if((!isset($_SESSION)) OR (!is_array($_SESSION))) { // fix for PHP8
			$_SESSION = []; //emulate session
		} //end if
		//--
		if($yvariable === null) {
			return (array) $_SESSION; // array, all the session variables at once
		} elseif((string)trim((string)$yvariable) == '') {
			return null; // variable must be explicit null or a non-empty string
		} elseif(array_key_exists((string)$yvariable, $_SESSION)) {
			return $_SESSION[(string)$yvariable]; // mixed
		} else {
			return null; // other cases: not found
		} //end if else
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	/**
	 * [PUBLIC] Session Set Variable
	 *
	 * @param STRING $yvariable 		variable name
	 * @param ANY VARIABLE $yvalue 		variable value
	 *
	 * @return BOOLEAN 					TRUE if successful, FALSE if not
	 */
	public static function set(string $yvariable, $yvalue) {
		//--
		if(!$yvariable) {
			return false;
		} //end if
		//--
		self::start(); // start session if not already started
		//--
		if((!isset($_SESSION)) OR (!is_array($_SESSION))) { // fix for PHP8
			$_SESSION = []; //emulate session
		} //end if
		//--
		if($yvalue === null) {
			if(array_key_exists((string)$yvariable, $_SESSION)) {
				unset($_SESSION[(string)$yvariable]);
			} //end if
		} else {
			$_SESSION[(string)$yvariable] = $yvalue; // mixed
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	/**
	 * [PUBLIC] Session Unset Variable
	 *
	 * @param STRING $yvariable 		variable name
	 *
	 * @return BOOLEAN 					TRUE if successful, FALSE if not
	 */
	public static function unsets(string $yvariable) {
		//--
		return (bool) self::set($yvariable, null);
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	/**
	 * Start the Session (if not already started).
	 * This function is called automatically when set() or get() is used for a session thus is not mandatory to be called.
	 * It should be called just on special circumstances (Ex: force start session without using set/get).
	 *
	 */
	public static function start() {
		//=====
		//--
		if(self::$started !== false) {
			return; // avoid start session if already started ...
		} //end if
		self::$started = true; // mark session as started at the begining (will be marked as active at the end of this function)
		//--
		//=====
		//--
		if(SmartUtils::is_ip_client_trusted() !== true) {
			Smart::log_notice(__METHOD__.' # Session support is disabled if using custom IP detection and the untrusted IP detection was defined by some reason ; Custom detected Client IP is ('.$browser_os_ip_identification['ip'].') ; To prevent the huge risk that someone with a fake IP can hijack another one`s session !');
			return;
		} //end if
		//--
		//=====
		//--
		$browser_os_ip_identification = SmartUtils::get_os_browser_ip(); // get browser and os identification
		//--
		if((string)$browser_os_ip_identification['bw'] == '@s#') { // class 'rb' is handled below
			return; // this must be before identify bot ; in this case start no session for the self browser (session is blocked before a request to finalize thus it cannot be used !!!)
		} elseif((string)$browser_os_ip_identification['bc'] == 'rb') { // CAN USE here the 'rb' class instead of 'bot' as the self-robot is in the same class but handled above ...
			if(!defined('SMART_FRAMEWORK_SESSION_ROBOTS') OR (SMART_FRAMEWORK_SESSION_ROBOTS !== true)) { // to enable session for robots define the SMART_FRAMEWORK_SESSION_ROBOTS constant and set to TRUE
				return; // in this case start no session for robots (as they do not need normally to share info between many visits and can generate a lot of traffic)
			} //end if
		} //end if
		//--
		//=====
		//-- no log as the cookies can be dissalowed by the browser
		if(!defined('SMART_APP_VISITOR_COOKIE') OR ((string)trim((string)SMART_APP_VISITOR_COOKIE) == '')) { // {{{SYNC-SMART-UNIQUE-VAL-COOKIE}}}
			return; // session need cookies
		} //end if
		//--
		//=====
		//--
		$sf_sess_mode = 'files';
		$sf_sess_area = 'default-sess';
		$sf_sess_ns = 'unknown';
		$sf_sess_dir = 'tmp/sess';
		//--
		//=====
		if(!defined('SMART_FRAMEWORK_SESSION_PREFIX')) {
			Smart::log_warning('FATAL ERROR: Invalid Session Prefix :: SMART_FRAMEWORK_SESSION_PREFIX');
			return;
		} //end if
		if((strlen(SMART_FRAMEWORK_SESSION_PREFIX) < 3) OR (strlen(SMART_FRAMEWORK_SESSION_PREFIX) > 9)) {
			Smart::log_warning('WARNING: Session Prefix must have a length between 3 and 9 characters :: SMART_FRAMEWORK_SESSION_PREFIX');
			return;
		} //end if
		if(!preg_match('/^[a-z\-]+$/', (string)SMART_FRAMEWORK_SESSION_PREFIX)) {
			Smart::log_warning('WARNING: Session Prefix contains invalid characters :: SMART_FRAMEWORK_SESSION_PREFIX');
			return;
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK_SESSION_NAME')) {
			Smart::log_warning('FATAL ERROR: Invalid Session Name :: SMART_FRAMEWORK_SESSION_NAME');
			return;
		} //end if
		if((strlen(SMART_FRAMEWORK_SESSION_NAME) < 7) OR (strlen(SMART_FRAMEWORK_SESSION_NAME) > 25)) { // {{{SYNC-SF-UID-SESS-COOKIE-NAME-LENGTH}}}
			Smart::log_warning('WARNING: Session Name must have a length between 7 and 25 characters :: SMART_FRAMEWORK_SESSION_NAME');
			return;
		} //end if
		if(!SmartFrameworkSecurity::ValidateVariableName((string)SMART_FRAMEWORK_SESSION_NAME)) { // {{{SYNC-VALIDATE-UID-COOKIE-NAME}}}
			Smart::log_warning('WARNING: Session Name have an invalid value :: SMART_FRAMEWORK_SESSION_NAME');
			return;
		} //end if
		//--
		if(!SmartFileSystem::is_type_dir('tmp/sessions/')) {
			Smart::log_warning('FATAL ERROR: The Folder \'tmp/sessions/\' does not exists for use with Session !');
			return;
		} //end if
		//--
		$ini_sess_mode = (string) ini_get('session.save_handler');
		if((string)SMART_FRAMEWORK_SESSION_HANDLER === 'files') {
			if((string)$ini_sess_mode !== 'files') {
				Smart::log_warning('FATAL ERROR: The value set for SMART_FRAMEWORK_SESSION_HANDLER is set to: files / but the value found in session.save_handler is: '.$ini_sess_mode);
				return;
			} //end if
			$detected_session_mode = 'files';
		} else { // redis, dba or custom
			if(((string)$ini_sess_mode !== 'files') AND ((string)$ini_sess_mode !== 'user')) {
				return; // can be a different handler directly supported by PHP like memcached or other, so let it handle ...
			} //end if
			$detected_session_mode = 'user'; // since PHP 7.3 there is no more supports set 'user' mode on session.save_handler ; PHP will just need to detect a custom handler user passes to handle the session
		} //end if
		//--
		//=====
		//--  generate a the client private key based on it's IP and Browser
		$the_sess_client_uuid = (string) SmartUtils::unique_client_private_key(); // SHA512 B64 key to protect session data agains forgers
		//-- a very secure approach based on a chain, derived with a secret salt from the framework security key:
		// (1) an almost unique client private key hash based on it's IP and Browser and the Unique Visitor Tracking Cookie
		// (2) an almost unique client public key hash based on it's IP and Browser (1) and Session Name
		// (3) a unique session id composed from (1) and (2)
		//-- thus the correlation between the above makes almost impossible to forge it as it locks to IP+Browser, using a public entropy cookie all encrypted with a secret key and derived and related, finally composed.
		$the_sess_hash_priv_key = (string) SmartHashCrypto::sh3a512($the_sess_client_uuid.'^'.SMART_APP_VISITOR_COOKIE.'^'.SMART_FRAMEWORK_SECURITY_KEY);
		$the_sess_hash_pub_key = (string) SmartHashCrypto::sh3a512('^'.SMART_FRAMEWORK_SESSION_NAME.'&'.$the_sess_client_uuid.'&'.$the_sess_hash_priv_key.'&'.SMART_FRAMEWORK_SECURITY_KEY.'$');
		$the_sess_id = (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sh3a512($the_sess_hash_pub_key.'^'.$the_sess_client_uuid.'&'.$the_sess_hash_priv_key), 62); // session ID combines the secret client key based on it's IP / Browser and the Client Entropy Cookie ; 64..86 characters long
		//--
		$sf_sess_area = (string) Smart::safe_filename((string)SMART_FRAMEWORK_SESSION_PREFIX);
		if(((string)$sf_sess_area == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$sf_sess_area))) {
			Smart::raise_error(
				'SESSION // FATAL ERROR: Invalid/Empty Session Area: '.$sf_sess_area
			);
			die('');
			return;
		} //end if
		$sf_sess_dpfx = (string) substr((string)$the_sess_hash_pub_key, 0, 1).'-'.substr((string)$the_sess_hash_priv_key, 0, 1); // this come from hexa so 3 chars are 16x16x16=4096 dirs
		//--
		if((string)$browser_os_ip_identification['bw'] == '@s#') { // class 'rb' is handled below
			$sf_sess_ns = '@sr-'.$sf_sess_dpfx;
		} elseif((string)$browser_os_ip_identification['bc'] == 'rb') { // CAN USE here the 'rb' class instead of 'bot' as the self-robot is in the same class but handled above ...
			$sf_sess_ns = 'r0-'.$sf_sess_dpfx; // we just need a short prefix for robots (on disk is costly for GC to keep separate folders, but of course, not so safe)
		} elseif((string)$browser_os_ip_identification['bw'] == (string)SmartUtils::GENERIC_VALUE_OS_BROWSER_IP) {
			$sf_sess_ns = 'c-'.'_x_'.'-'.$sf_sess_dpfx; // unidentified browser
		} else {
			$sf_sess_ns = 'c-'.substr((string)$browser_os_ip_identification['bw'],0,3).'-'.$sf_sess_dpfx; // we just need a short prefix for clients (on disk is costly for GC to keep separate folders, but of course, not so safe)
		} //end if else
		$sf_sess_ns = (string) Smart::safe_filename($sf_sess_ns);
		if(((string)$sf_sess_ns == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$sf_sess_ns))) {
			Smart::raise_error(
				'SESSION // FATAL ERROR: Invalid/Empty Session NameSpace: '.$sf_sess_ns
			);
			die('');
			return;
		} //end if
		//-- by default set for files
		$sf_sess_mode = 'files';
		//-- {{{SYNC-SESSION-FILE_BASED-PREFIX}}}
		$path_prefix = (string) SmartPersistentCache::cachePathPrefix(2, $sf_sess_ns); // this is a safe path
		$sf_sess_dir = 'tmp/sessions/'.SmartFileSysUtils::addPathTrailingSlash((string)$sf_sess_area).SmartFileSysUtils::addPathTrailingSlash((string)$path_prefix).SmartFileSysUtils::addPathTrailingSlash((string)$sf_sess_ns);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$sf_sess_dir);
		if((string)$detected_session_mode === 'user') {
			if(class_exists('SmartCustomSession', false)) { // explicit autoload is false
				if((string)get_parent_class('SmartCustomSession') == 'SmartAbstractCustomSession') {
					$sf_sess_mode = 'user-custom';
					$sf_sess_dir = 'tmp/sessions/'.$sf_sess_area.'/'; // here the NS is saved in DB so we do not need to complicate paths
				} else {
					Smart::log_warning('SESSION INIT ERROR: Invalid Custom Session Handler. The class SmartCustomSession must be extended from class SmartAbstractCustomSession ...');
					return;
				} //end if else
			} else {
				Smart::log_warning('SESSION INIT ERROR: Custom Session Handler requires the class SmartCustomSession ...');
				return;
			} //end if
		} //end if
		$sf_sess_dir = (string) Smart::safe_pathname((string)$sf_sess_dir);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$sf_sess_dir);
		//--
		if(!SmartFileSystem::is_type_dir((string)$sf_sess_dir)) {
			SmartFileSystem::dir_create((string)$sf_sess_dir, true); // recursive
		} //end if
		SmartFileSystem::write_if_not_exists('tmp/sessions/'.$sf_sess_area.'/'.'index.html', '');
		//=====
		//--
		@session_save_path((string)$sf_sess_dir);
		@session_cache_limiter('nocache');
		//--
		$the_name_of_session = (string) SMART_FRAMEWORK_SESSION_NAME.'__ID_'.Smart::base_from_hex_convert((string)$the_sess_hash_pub_key, 62); // protect session name data agains forgers
		//--
		@session_id((string)$the_sess_id);
		@session_name((string)$the_name_of_session);
		//--
		$tmp_exp_seconds = 0;
		if(defined('SMART_FRAMEWORK_SESSION_LIFETIME')) {
			$tmp_exp_seconds = (int) SMART_FRAMEWORK_SESSION_LIFETIME;
			if($tmp_exp_seconds < 0) {
				$tmp_exp_seconds = 0;
			} //end if
		} //end if
		$options = [ // {{{SYNC-PHP-COOKIE-OPTIONS}}}
			'lifetime' 	=> (int) $tmp_exp_seconds, // for the session cookie this is 'expires'
			'path' 		=> (string) '/'
		]; // by default set it without specific domain (will using current IP or subdomain)
		$cookie_domain = '';
		if(defined('SMART_FRAMEWORK_SESSION_DOMAIN') AND ((string)SMART_FRAMEWORK_SESSION_DOMAIN != '')) {
			if((string)SMART_FRAMEWORK_SESSION_DOMAIN == '*') {
				$cookie_domain = (string) SmartUtils::get_server_current_basedomain_name();
			} else {
				$cookie_domain = (string) SMART_FRAMEWORK_SESSION_DOMAIN;
			} //end if
		} // end if
		if((string)$cookie_domain != '') {
			$options['domain'] = (string) $cookie_domain; // set cookie using domain (if running on IP will be set on current IP)
		} //end if
		$cookie_samesite = (string) SmartUtils::cookie_default_samesite_policy();
		if((string)$cookie_samesite != '') {
			$options['samesite'] = (string) $cookie_samesite; // use the same site policy if valid
		} //end if
		// do not set httponly = TRUE as the ajax (javascript) must send the cookies and if TRUE this cookie will not be accessible by javascript !
		// a question: should be there an option to set the cookie attribute: secure = TRUE ? if so needs to detect if the protocol is HTTPS and not start session if not ! ... reflection moment ;-) ...
		@session_set_cookie_params((array)$options); // session cookie options ; req. PHP 7.3+
		//-- be sure that session_write_close() is executed at the end of script if script if die premature and before pgsql shutdown register in the case of DB sessions
		register_shutdown_function('session_write_close');
		//-- handle custom session handler
		if((string)$sf_sess_mode === 'user-custom') {
			//--
			$sess_obj = new SmartCustomSession();
			$sess_obj->sess_area = (string) $sf_sess_area;
			$sess_obj->sess_ns = (string) $sf_sess_ns;
			$sess_obj->sess_expire = (int) $tmp_exp_seconds;
			//--
			session_set_save_handler(
				array($sess_obj, 'open'),
				array($sess_obj, 'close'),
				array($sess_obj, 'read'),
				array($sess_obj, 'write'),
				array($sess_obj, 'destroy'),
				array($sess_obj, 'gc')
			);
			//--
		} //end if else
		//-- start session
		@session_start();
		//--
		if((int)$tmp_exp_seconds > 0) {
			$sess_max_expire = (int) $tmp_exp_seconds;
		} else {
			$sess_max_expire = (int) 3600 * 24; // {{{SYNC-SESS-MAX-HARDCODED-VAL}}} max 24 hour from the last access if browser session, there is a security risk if SMART_FRAMEWORK_SESSION_LIFETIME is zero
		} //end if
		$time_now = (int) time();
		//--
		if(
			(Smart::array_size($_SESSION) <= 0)   OR
			(!isset($_SESSION['session_ID']))     OR ((int)strlen($_SESSION['session_ID']) < 64)                             OR
			(!isset($_SESSION['visitor_UUID']))   OR ((string)$_SESSION['visitor_UUID'] != (string)SMART_APP_VISITOR_COOKIE) OR
			(!isset($_SESSION['uniqbrowser_ID'])) OR ((string)$_SESSION['uniqbrowser_ID'] != (string)$the_sess_client_uuid)  OR
			(!isset($_SESSION['session_AREA']))   OR ((string)$_SESSION['session_AREA'] != (string)$sf_sess_area)            OR
			(!isset($_SESSION['session_NS']))     OR ((string)$_SESSION['session_NS'] != (string)$sf_sess_ns)                OR
			(!isset($_SESSION['website_ID']))     OR ((string)$_SESSION['website_ID'] != (string)SMART_SOFTWARE_NAMESPACE)   OR
			((int)((int)(isset($_SESSION['visit_UPDATE']) ? $_SESSION['visit_UPDATE'] : 0) + (int)$sess_max_expire) < (int)$time_now)
		) {
			//--
			if(Smart::array_size($_SESSION) > 0) {
				//--
				if((!isset($_SESSION['session_ID'])) OR (strlen($_SESSION['session_ID']) < 64)) {
					Smart::log_warning('Session Reset: Session ID must be at least 64 characters ...');
				} //end if
				//--
				if((!isset($_SESSION['visitor_UUID'])) OR ((string)$_SESSION['visitor_UUID'] != (string)SMART_APP_VISITOR_COOKIE)) {
					Smart::log_warning('Session Reset: Unique Visitor UUID does not match ...');
				} //end if
				//--
				if((!isset($_SESSION['uniqbrowser_ID'])) OR  ((string)$_SESSION['uniqbrowser_ID'] != (string)$the_sess_client_uuid)) {
					Smart::log_warning('Session Reset: Unique Browser ID does not match ...');
				} //end if
				//--
				if((!isset($_SESSION['session_AREA'])) OR ((string)$_SESSION['session_AREA'] != (string)$sf_sess_area)) {
					Smart::log_warning('Session Reset: Session Area does not match ...');
				} //end if
				//--
				if((!isset($_SESSION['session_NS'])) OR ((string)$_SESSION['session_NS'] != (string)$sf_sess_ns)) {
					Smart::log_warning('Session Reset: Session NameSpace does not match ...');
				} //end if
				//--
				if((!isset($_SESSION['website_ID'])) OR ((string)$_SESSION['website_ID'] != (string)SMART_SOFTWARE_NAMESPACE)) {
					Smart::log_warning('Session Reset: Session Website ID does not match ...');
				} //end if
				//--
				if(!isset($_SESSION['visit_UPDATE'])) {
					Smart::log_warning('Session Reset: Session Expiration Time is not set ...');
				} //end if
				if(SmartEnvironment::ifDebug()) {
					if((int)((int)$_SESSION['visit_UPDATE'] + (int)$sess_max_expire) < (int)$time_now) {
						Smart::log_notice('Session Reset: Session Max HardCoded Expiration Time was Reach ; sessionUpdate='.(int)$_SESSION['visit_UPDATE'].' ; maxExpire='.(int)$sess_max_expire.' ; timeNow='.(int)$time_now);
					} //end if
				} //end if
				//--
			} //end if
			//--
			$_SESSION = array(); // reset it
			//--
			$_SESSION['SoftwareFramework_VERSION'] 		= (string) SMART_FRAMEWORK_VERSION; 							// software version
			$_SESSION['SoftwareFramework_SessionMode'] 	= (string) $sf_sess_mode.':'.SMART_FRAMEWORK_SESSION_HANDLER; 	// session mode
			$_SESSION['website_ID'] 					= (string) SMART_SOFTWARE_NAMESPACE; 							// the website ID
			$_SESSION['visitor_UUID'] 					= (string) SMART_APP_VISITOR_COOKIE; 							// the visitor UUID
			$_SESSION['session_AREA'] 					= (string) $sf_sess_area; 										// session area
			$_SESSION['session_NS'] 					= (string) $sf_sess_ns; 										// session namespace
			$_SESSION['session_ID'] 					= (string) @session_id(); 										// read current session ID
			$_SESSION['session_STARTED'] 				= (string) date('Y-m-d H:i:s O'); 								// read current session ID
			$_SESSION['visit_COUNTER'] 					= (int)    0; 													// the session visit counter
			//--
		} //end if
		//--
		if(!array_key_exists('visit_COUNTER', $_SESSION)) {
			$_SESSION['visit_COUNTER'] = 0;
		} //end if
		$_SESSION['visit_COUNTER'] += 1; // increment visit counter
		//--
		$_SESSION['visit_UPDATE'] = (int) $time_now;
		//--
		if(!isset($_SESSION['visitor_UUID'])) {
			$_SESSION['visitor_UUID'] = (string) SMART_APP_VISITOR_COOKIE; // set it only once
		} //end if
		//--
		if(!isset($_SESSION['uniqbrowser_ID'])) {
			$_SESSION['uniqbrowser_ID'] = (string) $the_sess_client_uuid; // set it only once
		} //end if
		//--
		$_SESSION['SmartFramework__Browser__Identification__Data'] = (array) $browser_os_ip_identification; // rewrite it each time
		//--
		self::$active = (int) $time_now; // successfuly started
		//--
	} //END FUNCTION
	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================



//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Abstract Class Smart Custom Session
 * This is the abstract for extending the class SmartCustomSession
 *
 * @version 	v.20231103
 * @package 	development:Application
 */
abstract class SmartAbstractCustomSession {

	// -> ABSTRACT

	// NOTICE: This object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE WILL NOT WORK !!!


	//-- PUBLIC VARS
	public $sess_area;
	public $sess_ns;
	public $sess_expire;
	//--


	//==================================================
	final public function __construct() {
		//--
		// constructor (will not use it, this is not safe to use because changes between PHP versions ..., so all logic must be implemented in open)
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function open() {
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function close() {
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function write($id, $data) {
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function read($id) {
		//--
		return (string) '';
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function destroy($id) {
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	// TO BE EXTENDED
	public function gc($lifetime) {
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
