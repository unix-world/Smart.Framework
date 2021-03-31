<?php
// [Smart.Framework / App Runtime]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - App Runtime (this should be loaded only from app web root)
// DEPENDS: Smart.Framework + Smart.Framework/Components
// DO NOT MODIFY THIS FILE OR ANY OTHER FILE(S) UNDER lib/* or index.php / admin.php [They will be all overwritten on any future framework updates or upgrades] !!!
// YOU CAN ONLY CHANGE / CUSTOMIZE:
//	* Configurations: etc/*
//	* Modules: modules/*
//======================================================

// [REGEX-SAFE-OK]

//===== WARNING: =====
// DO NOT CHANGE the code below (it may lead to severe disrupts in the execution of this software), but of course you can do it on your own risk !!!
//====================

//--
if(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') || defined('SMART_FRAMEWORK_RELEASE_VERSION') || defined('SMART_FRAMEWORK_RELEASE_URL') || defined('SMART_FRAMEWORK_RELEASE_MIDDLEWARE')) {
	@http_response_code(500);
	die('Reserved Constants names have been already defined: SMART_FRAMEWORK_RELEASE_* is reserved');
} //end if
//--
define('SMART_FRAMEWORK_RELEASE_TAGVERSION', 'v.7.2.1'); 	// tag version
define('SMART_FRAMEWORK_RELEASE_VERSION', 'r.2021.03.31'); 	// tag release-date
define('SMART_FRAMEWORK_RELEASE_URL', 'http://demo.unix-world.org/smart-framework/');
//--

//--
if(!defined('SMART_FRAMEWORK_ADMIN_AREA')) {
	@http_response_code(500);
	die('A required RUNTIME constant has not been defined: SMART_FRAMEWORK_ADMIN_AREA');
} //end if
//--
if(!defined('SMART_ERROR_LOG_MANAGEMENT')) {
	@http_response_code(500);
	die('The Smart Error Handler was not initialized ... SMART_ERROR_LOG_MANAGEMENT');
} //end if
//--
if(!headers_sent()) { // safe
	header('X-Powered-By: '.'Smart.Framework PHP/Javascript :: '.SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION.' @ '.((SMART_FRAMEWORK_ADMIN_AREA === true) ? '[A]' : '[I]'));
} //end if
//--

//--
if(defined('SMART_FRAMEWORK_IPDETECT_CUSTOM')) {
	if((!defined('SMART_FRAMEWORK_IPDETECT_CLIENT')) OR (!defined('SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT'))) {
		@http_response_code(500);
		die('The following constants must be defined when SMART_FRAMEWORK_IPDETECT_CUSTOM is set: SMART_FRAMEWORK_IPDETECT_CLIENT, SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT');
	} //end if
} else {
	if((defined('SMART_FRAMEWORK_IPDETECT_CLIENT')) OR (defined('SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT'))) {
		@http_response_code(500);
		die('The following constants must NOT be defined when SMART_FRAMEWORK_IPDETECT_CUSTOM is not set: SMART_FRAMEWORK_IPDETECT_CLIENT, SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT');
	} //end if
	define('SMART_FRAMEWORK_IPDETECT_CLIENT', 'REMOTE_ADDR');
	define('SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT', '<HTTP_CLIENT_IP>,<HTTP_X_FORWARDED_FOR>');
} //end if else
//--

//--
if(!defined('SMART_FRAMEWORK_URL_PARAM_LANGUAGE')) {
	define('SMART_FRAMEWORK_URL_PARAM_LANGUAGE', '');
} //end if
if(SMART_FRAMEWORK_URL_PARAM_LANGUAGE AND (!preg_match('/^[a-z]+$/', (string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE))) { // {{{SYNC-APP-URL-LANG-PARAM}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_FRAMEWORK_URL_PARAM_LANGUAGE');
} //end if

//--
if(defined('SMART_APP_LANG_COOKIE')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_APP_LANG_COOKIE');
} //end if
if(SMART_FRAMEWORK_URL_PARAM_LANGUAGE) {
	if(SMART_FRAMEWORK_ADMIN_AREA === true) {
		define('SMART_APP_LANG_COOKIE', 'SmartApp_ADM__SetLanguage__'.SMART_FRAMEWORK_URL_PARAM_LANGUAGE);
	} else {
		define('SMART_APP_LANG_COOKIE', 'SmartApp_IDX__SetLanguage__'.SMART_FRAMEWORK_URL_PARAM_LANGUAGE);
	} //end if else
} //end if
if(!defined('SMART_APP_LANG_COOKIE')) {
	define('SMART_APP_LANG_COOKIE', '');
} //end if
//--

//--
if(defined('SMART_FRAMEWORK_INFO_LOG')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_INFO_LOG');
} //end if
//--
if(SMART_FRAMEWORK_ADMIN_AREA === true) {
	if(!define('SMART_FRAMEWORK_INFO_LOG', 'tmp/logs/adm/'.'info-'.date('Y-m-d@H').'.log')) {
		die('Failed to define the SMART_FRAMEWORK_INFO_LOG (adm) ...');
	} //end if
} else {
	if(!define('SMART_FRAMEWORK_INFO_LOG', 'tmp/logs/idx/'.'info-'.date('Y-m-d@H').'.log')) {
		die('Failed to define the SMART_FRAMEWORK_INFO_LOG (idx) ...');
	} //end if
} //end if else
//--

//--
if(!defined('SMART_SOFTWARE_NAMESPACE')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_SOFTWARE_NAMESPACE');
} //end if
if((strlen(SMART_SOFTWARE_NAMESPACE) < 10) OR (strlen(SMART_SOFTWARE_NAMESPACE) > 25)) {
	@http_response_code(500);
	die('A required INIT constant must have a length between 10 and 25 characters: SMART_SOFTWARE_NAMESPACE');
} //end if
if(!preg_match('/^[_a-z0-9\-\.]+$/', (string)SMART_SOFTWARE_NAMESPACE)) { // regex namespace
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_SOFTWARE_NAMESPACE');
} //end if
//--
if(!defined('SMART_FRAMEWORK_TIMEZONE')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_TIMEZONE');
} //end if
//--
if(!defined('SMART_FRAMEWORK_DEFAULT_LANG')) {
	define('SMART_FRAMEWORK_DEFAULT_LANG', 'en');
} //end if
//--
if(!defined('SMART_FRAMEWORK_SECURITY_FILTER_INPUT')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SECURITY_FILTER_INPUT');
} //end if
//--
if(!defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME');
} //end if
if(!preg_match('/^[_a-z0-9A-Z]+$/', (string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME)) { // {{{SYNC-REGEX-COOKIE-NAME}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_SOFTWARE_NAMESPACE');
} //end if
//--
if(!defined('SMART_FRAMEWORK_MAX_BROWSER_COOKIE_SIZE')) {
	define('SMART_FRAMEWORK_MAX_BROWSER_COOKIE_SIZE', 3072); // max cookie size is 4096 but includding the name, time, domain, path and the rest ...
} //end if
//--
if(!defined('SMART_FRAMEWORK_SECURITY_KEY')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SECURITY_KEY');
} //end if
//--
if(!defined('SMART_FRAMEWORK_SESSION_NAME')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SESSION_NAME');
} //end if
if(!preg_match('/^[_a-z0-9A-Z]+$/', (string)SMART_FRAMEWORK_SESSION_NAME)) { // {{{SYNC-REGEX-COOKIE-NAME}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_FRAMEWORK_SESSION_NAME');
} //end if
if(!defined('SMART_FRAMEWORK_SESSION_HANDLER')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SESSION_HANDLER');
} //end if
//--
if(!defined('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER');
} //end if
//--
if(!defined('SMART_FRAMEWORK_MEMORY_LIMIT')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_MEMORY_LIMIT');
} //end if
if(!defined('SMART_FRAMEWORK_EXECUTION_TIMEOUT')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_EXECUTION_TIMEOUT');
} //end if
if(!defined('SMART_FRAMEWORK_NETSOCKET_TIMEOUT')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_NETSOCKET_TIMEOUT');
} //end if
if(!defined('SMART_FRAMEWORK_NETSERVER_ID')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_NETSERVER_ID');
} //end if
if(((int)SMART_FRAMEWORK_NETSERVER_ID < 0) OR ((int)SMART_FRAMEWORK_NETSERVER_ID > 1295)) { // {{{SYNC-MIN-MAX-NETSERVER-ID}}}
	@http_response_code(500);
	die('The required INIT constant SMART_FRAMEWORK_NETSERVER_ID can have values between 0 and 1295');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_MODE')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_MODE');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_CIPHERS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_CIPHERS');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_VFY_HOST')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_VFY_HOST');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_VFY_PEER')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_VFY_PEER');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_VFY_PEER_NAME')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_VFY_PEER_NAME');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED');
} //end if
if(!defined('SMART_FRAMEWORK_SSL_DISABLE_COMPRESS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_SSL_DISABLE_COMPRESS');
} //end if
//--
if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHMOD_DIRS');
} //end if
if(!is_int(SMART_FRAMEWORK_CHMOD_DIRS)) {
	@http_response_code(500);
	die('Invalid INIT constant value for SMART_FRAMEWORK_CHMOD_DIRS');
} //end if
if(!defined('SMART_FRAMEWORK_CHMOD_FILES')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHMOD_FILES');
} //end if
if(!is_int(SMART_FRAMEWORK_CHMOD_FILES)) {
	@http_response_code(500);
	die('Invalid INIT constant value for SMART_FRAMEWORK_CHMOD_FILES');
} //end if
//--
if(!defined('SMART_FRAMEWORK_DOWNLOAD_FOLDERS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_DOWNLOAD_FOLDERS');
} //end if
if(!defined('SMART_FRAMEWORK_UPLOAD_PICTS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_UPLOAD_PICTS');
} //end if
if(!defined('SMART_FRAMEWORK_UPLOAD_MOVIES')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_UPLOAD_MOVIES');
} //end if
if(!defined('SMART_FRAMEWORK_UPLOAD_DOCS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_UPLOAD_DOCS');
} //end if
if(!defined('SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS');
} //end if
if(!defined('SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES')) {
	define('SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES', '<php>,<netarch>');
} //end if
//--
if(!defined('SMART_FRAMEWORK_HTACCESS_NOEXECUTION')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_NOEXECUTION');
} //end if
if(!defined('SMART_FRAMEWORK_HTACCESS_FORBIDDEN')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_FORBIDDEN');
} //end if
if(!defined('SMART_FRAMEWORK_HTACCESS_NOINDEXING')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_NOINDEXING');
} //end if
if(!defined('SMART_FRAMEWORK_IDENT_ROBOTS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_IDENT_ROBOTS');
} //end if
//--
if(!defined('SMART_SOFTWARE_FRONTEND_ENABLED')) {
	define('SMART_SOFTWARE_FRONTEND_ENABLED', true); // if not explicit defined, set it here to avoid later modifications
} //end if
if(!defined('SMART_SOFTWARE_BACKEND_ENABLED')) {
	define('SMART_SOFTWARE_BACKEND_ENABLED', true); // if not explicit defined, set it here to avoid later modifications
} //end if
if(!defined('SMART_SOFTWARE_URL_ALLOW_PATHINFO')) {
	define('SMART_SOFTWARE_URL_ALLOW_PATHINFO', 0); // if not explicit defined, set it here to avoid later modifications
} //end if
//--
if(!defined('SMART_FRAMEWORK_CHARSET')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHARSET');
} //end if
if(!defined('SMART_FRAMEWORK_DBSQL_CHARSET')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_DBSQL_CHARSET');
} //end if
if(!defined('SMART_FRAMEWORK_LANGUAGES_CACHE_DIR')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_LANGUAGES_CACHE_DIR');
} //end if
if(!preg_match('/^[a-z\/]+$/', (string)SMART_FRAMEWORK_LANGUAGES_CACHE_DIR)) {
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_FRAMEWORK_LANGUAGES_CACHE_DIR');
} //end if
//--
//==

//--
if(defined('SMART_FRAMEWORK_SESSION_PREFIX')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_SESSION_PREFIX');
} //end if
//--
if(SMART_FRAMEWORK_ADMIN_AREA === true) {
	define('SMART_FRAMEWORK_SESSION_PREFIX', 'adm-sess');
} else {
	define('SMART_FRAMEWORK_SESSION_PREFIX', 'idx-sess');
} //end if else
//--

//=========================
//========================= ALL CODE BELOW: must be created, loaded or registered after the registration of the REQUEST variables (GET/POST/COOKIES) to avoid security leaks !!! Do not modify this order !!!
//=========================

//--------------------------------------- CONFIG INITS
$configs = array();
$languages = array();
//--------------------------------------- LOAD CONFIGS
require('etc/config.php'); // load the main configuration, after GET/POST registration
//--------------------------------------- LOAD SMART-FRAMEWORK
require('lib/framework/smart-framework.php');
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//--------------------------------------- LOAD SMART TRANSLATIONS SUPPORT ; before smart components, it depends on it
require('lib/app/lib_translations.php');
//--------------------------------------- LOAD SMART COMPONENTS ; before plugins, some plugins depend on it
require('lib/core/lib_smart_components.php');
//--------------------------------------- LOAD RUNTIME ; before plugins, after smart components
require('lib/lib_runtime.php');
//--------------------------------------- REGISTER AUTO-LOAD OF PLUGINS (by dependency injection)
require('lib/core/plugins/autoload.php');
//--------------------------------------- CONDITIONAL LOAD THE DEBUG (PROFILER) ; at the end, it depends at least on smart components
if(SmartFrameworkRuntime::ifDebug()) {
	//-- load debug profiler
	require('lib/core/lib_debug_profiler.php');
	//-- register extra logs from framework
	SmartDebugProfiler::register_extra_debug_log('SmartMarkersTemplating', 'registerOptimizationHintsToDebugLog');
	//-- register extra internal logs from framework
	if(SmartFrameworkRuntime::ifInternalDebug()) {
		SmartDebugProfiler::register_extra_debug_log('SmartFrameworkRegistry', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('Smart', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartHashCrypto', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartAuth', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartUtils', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartMarkersTemplating', 'registerInternalCacheToDebugLog');
	} //end if
	//--
} //end if
//--------------------------------------- LOAD APP.REQUEST (HANDLER)
require('lib/run/app-request.php'); // REGISTER REQUEST INPUT VARIABLES (GET, POST, COOKIE, SERVER)
//--------------------------------------- If .ht-sf-singleuser-mode exists then Return 503, Maintenance: SingleUser Mode
SmartFrameworkRuntime::SingleUser_Mode_Monitor();
//--------------------------------------- Monitor High Loads and if detected Return 503 Too Busy
SmartFrameworkRuntime::High_Load_Monitor();
//--------------------------------------- create temporary dir (required by Smart.Framework)
SmartFrameworkRuntime::Create_Required_Dirs();
//--------------------------------------- LOAD APP.BOOTSTRAP
if(defined('SMART_SOFTWARE_APP_NAME')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_SOFTWARE_APP_NAME. Only external apps can define this outside of App Bootstrap but not Smart Framework itself ...');
} //end if
require('lib/run/app-bootstrap.php');
if(!defined('SMART_SOFTWARE_APP_NAME')) {
	@http_response_code(500);
	die('A required BOOTSTRAP Constant has not been defined: SMART_SOFTWARE_APP_NAME');
} //end if
//--
if(!class_exists('SmartPersistentCache')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartPersistentCache is missing ...');
} //end if
if(!is_subclass_of('SmartPersistentCache', 'SmartAbstractPersistentCache')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartPersistentCache must be extended from the Class SmartAbstractPersistentCache ...');
} //end if
//--
if(!class_exists('SmartAdapterTextTranslations')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAdapterTextTranslations is missing ...');
} //end if
if(!is_subclass_of('SmartAdapterTextTranslations', 'SmartInterfaceAdapterTextTranslations', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAdapterTextTranslations must implement the SmartInterfaceAdapterTextTranslations ...');
} //end if
//--
if(!class_exists('SmartAppInfo')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppInfo is missing ...');
} //end if
if(!is_subclass_of('SmartAppInfo', 'SmartInterfaceAppInfo', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppInfo must implement the SmartInterfaceAppInfo ...');
} //end if
//--
if(!class_exists('SmartAppBootstrap')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppBootstrap is missing ...');
} //end if
if(!is_subclass_of('SmartAppBootstrap', 'SmartInterfaceAppBootstrap', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppBootstrap must implement the SmartInterfaceAppBootstrap ...');
} //end if
//---------------------------------------

//========================= MONITOR: REDIRECTION CONTROLLER
SmartFrameworkRuntime::Redirection_Monitor();
//========================= REGISTER UNIQUE ID COOKIE (required before run)
SmartFrameworkRuntime::SetVisitorEntropyIDCookie(); // will define the constant SMART_APP_VISITOR_COOKIE ; cookie will be set only if SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME is non empty
//========================= APP.BOOTSTRAP: RUN
SmartAppBootstrap::Run();
//=========================
SmartCache::setKey('smart-app-runtime', 'visitor-cookie', (string)SMART_APP_VISITOR_COOKIE);
//=========================

//=========================
//==
/**
 * Function AutoLoad Modules (Libs / Models) via Dependency Injection
 *
 * @access 		private
 * @internal
 * @version		v.20210330
 *
 */
function autoload__SmartFrameworkModClasses($classname) {
	//--
	$classname = (string) $classname;
	//--
	if((strpos($classname, '\\') === false) OR (!preg_match('/^[a-zA-Z0-9_\\\]+$/', $classname))) { // if have no namespace or not valid character set
		return;
	} //end if
	//--
	if((strpos($classname, 'SmartModExtLib\\') === false) AND (strpos($classname, 'SmartModDataModel\\') === false)) { // must start with this namespaces only
		return;
	} //end if
	//--
	$parts = (array) explode('\\', $classname);
	if(count($parts) != 3) { // need for [0], [1] and [2]
		return;
	} //end if
	//--
	$max = (int) count($parts) - 1; // the last is the class
	//--
	$dir = 'modules/mod';
	//--
	if((string)$parts[1] != '') {
		//--
		$dir .= (string) strtolower((string)implode('-', preg_split('/(?=[A-Z])/', (string)$parts[1])));
		//--
		if((string)$parts[0] == 'SmartModExtLib') {
			//--
			$dir .= '/libs/';
			//--
		} elseif((string)$parts[0] == 'SmartModDataModel') {
			//--
			$dir .= '/models/';
			//--
		} else {
			//--
			return; // other namespaces are not managed here
			//--
		} //end if else
		//--
		if((string)$parts[2] != '') {
			for($i=2; $i<$max; $i++) {
				$dir .= (string) $parts[$i].'/';
			} //end for
		} //end if
		//--
	} else {
		//--
		return; // no module detected
		//--
	} //end if
	//--
	$dir = (string) $dir;
	$file = (string) $parts[(int)$max];
	$path = (string) $dir.$file;
	$path = (string) trim((string)str_replace(array('\\', "\0"), array('', ''), $path)); // filter out null byte and backslash
	//--
	if(((string)$path == '') OR ((string)$path == '/') OR (!preg_match('/^[_a-zA-Z0-9\-\/]+$/', $path))) {
		return; // invalid path characters in file
	} //end if
	//--
	if(!is_file($path.'.php')) { // here must be used is_file() because is autoloader ...
		return; // file does not exists
	} //end if
	//--
	require_once($path.'.php');
	//--
} //END FUNCTION
//==
spl_autoload_register('autoload__SmartFrameworkModClasses', true, false); // throw / prepend
//==
//=========================

//=========================
//==
if(defined('SMART_FRAMEWORK_APP_RUNTIME')) {
	@http_response_code(500);
	die('Smart.Framework / App-Runtime already loaded ...');
} //end if
//==
define('SMART_FRAMEWORK_APP_RUNTIME', 'SET');
//==
//=========================


// end of php code
