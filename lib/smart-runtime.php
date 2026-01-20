<?php
// [Smart.Framework / App Runtime]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR (((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.app') AND ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task'))) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//====================================================== r.20260120
// Smart-Framework - App Runtime (this should be loaded only from app web root)
// DEPENDS: Smart.Framework + Smart.Framework/Components
// DO NOT MODIFY THIS FILE OR ANY OTHER FILE(S) UNDER lib/* or index.php or admin.php or task.php [They will be all overwritten on any future framework updates or upgrades] !!!
// YOU CAN ONLY CHANGE / CUSTOMIZE:
//	* Configurations: etc/*
//	* Modules: modules/*
//======================================================

// [REGEX-SAFE-OK]

//===== WARNING: =====
// DO NOT CHANGE the code below (it may lead to severe disrupts in the execution of this software), but of course you can do it on your own risk !!!
//====================

//--
if((!defined('SMART_ERROR_LOG_MANAGEMENT')) OR ((string)SMART_ERROR_LOG_MANAGEMENT != 'Smart.Error.Handler')) {
	@http_response_code(500);
	die('The Smart Error Handler was not initialized or is not compatible ... SMART_ERROR_LOG_MANAGEMENT: '.SMART_ERROR_LOG_MANAGEMENT);
} //end if
//--

//--
if(headers_sent()) { // safe
	@http_response_code(500);
	die('Headers already sent before the runtime ...');
} //end if
//--
header('X-Powered-By: '.'Smart.Framework PHP/Javascript :: '.SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION.' @ '.((SMART_FRAMEWORK_ADMIN_AREA === true) ? (((string)SMART_FRAMEWORK_RUNTIME_MODE == 'web.task') ? '[T]' : '[A]') : '[I]')); // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
//--

//--
array_map(function($const){
	if(!defined((string)$const)) {
		@http_response_code(500);
		die('A required constant has not been defined: '.$const);
	}
},
[
	'SMART_FRAMEWORK_HTACCESS_NOEXECUTION', 'SMART_FRAMEWORK_DOWNLOAD_FOLDERS',
	'SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES', 'SMART_FRAMEWORK_NETSERVER_MAXLOAD',
	'SMART_FRAMEWORK_INFO_DIR_LOG', 'SMART_FRAMEWORK_UUID_COOKIE_NAME',
	'SMART_SOFTWARE_URL_ALLOW_PATHINFO', 'SMART_SOFTWARE_FRONTEND_DISABLED', 'SMART_SOFTWARE_BACKEND_DISABLED', 'SMART_SOFTWARE_TASK_DISABLED',
	'SMART_FRAMEWORK_SEMANTIC_URL_DISABLE', 'SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT', 'SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE', 'SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE',
	'SMART_FRAMEWORK_SESSION_HANDLER', 'SMART_FRAMEWORK_SESSION_NAME',
	'SMART_FRAMEWORK_SESSION_LIFETIME', 'SMART_FRAMEWORK_SESSION_DOMAIN', 'SMART_FRAMEWORK_SESSION_ROBOTS',
	'SMART_SOFTWARE_DISABLE_STATUS_POWERED',
]);
//--

//--
if(!preg_match('/^[_a-z0-9A-Z]+$/', (string)SMART_FRAMEWORK_UUID_COOKIE_NAME)) { // {{{SYNC-REGEX-COOKIE-NAME}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_FRAMEWORK_UUID_COOKIE_NAME');
} //end if
if(!defined('SMART_FRAMEWORK_UUID_COOKIE_SKIP')) {
	define('SMART_FRAMEWORK_UUID_COOKIE_SKIP', false); // avoid change it later
} //end if
//--
if(!preg_match('/^[_a-z0-9A-Z]+$/', (string)SMART_FRAMEWORK_SESSION_NAME)) { // {{{SYNC-REGEX-COOKIE-NAME}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters: SMART_FRAMEWORK_SESSION_NAME');
} //end if
//--
if(defined('SMART_FRAMEWORK_SESSION_PREFIX')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_SESSION_PREFIX');
} //end if
//--
if(defined('SMART_FRAMEWORK_ADMIN_AREA') AND (SMART_FRAMEWORK_ADMIN_AREA === true)) {
	if(defined('SMART_FRAMEWORK_RUNTIME_MODE') AND ((string)SMART_FRAMEWORK_RUNTIME_MODE == 'web.task')) { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
		define('SMART_FRAMEWORK_SESSION_PREFIX', 'tsk-sess');
	} else {
		define('SMART_FRAMEWORK_SESSION_PREFIX', 'adm-sess');
	} //end if else
} else {
	define('SMART_FRAMEWORK_SESSION_PREFIX', 'idx-sess');
} //end if else
//--

//--
if(!defined('SMART_FRAMEWORK_DEFAULT_LANG')) {
	define('SMART_FRAMEWORK_DEFAULT_LANG', 'en'); // {{{SYNC-APP-DEFAULT-LANG}}}
} //end if
//--
if(!defined('SMART_FRAMEWORK_URL_PARAM_LANGUAGE')) {
	define('SMART_FRAMEWORK_URL_PARAM_LANGUAGE', '');
} //end if
if(((string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE != '') AND (!preg_match('/^[a-z]{1,10}$/', (string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE))) { // {{{SYNC-APP-URL-LANG-PARAM}}}
	@http_response_code(500);
	die('A required INIT constant contains invalid characters or is not between 1 and 10 characters: SMART_FRAMEWORK_URL_PARAM_LANGUAGE');
} //end if
//--
if(defined('SMART_APP_LANG_COOKIE')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_APP_LANG_COOKIE');
} //end if
if(defined('SMART_FRAMEWORK_URL_PARAM_LANGUAGE') AND ((string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE != '')) {
	if(defined('SMART_FRAMEWORK_ADMIN_AREA') AND (SMART_FRAMEWORK_ADMIN_AREA === true)) {
		if((string)SMART_FRAMEWORK_RUNTIME_MODE == 'web.task') { // {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}
			define('SMART_APP_LANG_COOKIE', 'SmartApp_TSK__SetLanguage__'.SMART_FRAMEWORK_URL_PARAM_LANGUAGE);
		} else {
			define('SMART_APP_LANG_COOKIE', 'SmartApp_ADM__SetLanguage__'.SMART_FRAMEWORK_URL_PARAM_LANGUAGE);
		} //end if else
	} else {
		define('SMART_APP_LANG_COOKIE', 'SmartApp_IDX__SetLanguage__'.SMART_FRAMEWORK_URL_PARAM_LANGUAGE);
	} //end if else
} //end if
if(!defined('SMART_APP_LANG_COOKIE')) {
	define('SMART_APP_LANG_COOKIE', '');
} //end if
//--
if(!defined('SMART_FRAMEWORK_TRANSLATIONS_BUILTIN_CUSTOM')) {
	define('SMART_FRAMEWORK_TRANSLATIONS_BUILTIN_CUSTOM', false);
} //end if
if(!defined('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM')) {
	define('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM', false);
} //end if
//--

//--
if(!defined('SMART_FRAMEWORK_PROFILING_HTML_PERF')) {
	define('SMART_FRAMEWORK_PROFILING_HTML_PERF', false); // if not explicit defined, this must be set here to avoid PHP 7.3+ warnings
} //end if
//--

//--
if(defined('SMART_FRAMEWORK_INFO_LOG')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_FRAMEWORK_INFO_LOG');
} //end if
if(!define('SMART_FRAMEWORK_INFO_LOG', SMART_FRAMEWORK_INFO_DIR_LOG.'info-'.date('Y-m-d@H').'.log')) {
	die('Failed to define the SMART_FRAMEWORK_INFO_LOG ...');
} //end if
//--

//=========================
//========================= ALL CODE BELOW: must be created, loaded or registered after the registration of the REQUEST variables (GET/POST/COOKIES) to avoid security leaks !!! Do not modify this order !!!
//=========================

//--------------------------------------- CONFIG INITS
$configs = [];
$languages = [];
//--------------------------------------- LOAD CONFIGS
require('etc/config.php'); // load the main configuration, after GET/POST registration
//--------------------------------------- LOAD SMART-FRAMEWORK
require('lib/framework/smart-framework.php');
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//--------------------------------------- LOAD SMART REGISTRY
require('lib/lib_registry.php');
//--------------------------------------- LOAD SMART FILE SYSTEM
require('lib/core/lib_filesys.php');
//--------------------------------------- LOAD SMART UTILS (after: registry, file system)
require('lib/core/lib_utils.php');
//--------------------------------------- LOAD SMART TRANSLATIONS SUPPORT ; before smart components, it depends on it
require('lib/core/lib_translations.php');
//--------------------------------------- LOAD SMART COMPONENTS ; before plugins, some plugins depend on it
require('lib/core/lib_smart_components.php');
//--------------------------------------- LOAD RUNTIME ; before plugins, after smart components
require('lib/lib_runtime.php');
//--------------------------------------- REGISTER AUTO-LOAD OF PLUGINS (by dependency injection)
require('lib/core/plugins/autoload.php');
//--------------------------------------- CONDITIONAL LOAD THE DEBUG (PROFILER) ; at the end, it depends at least on smart components
if(SmartEnvironment::ifDebug()) {
	//-- load debug profiler
	require('lib/core/lib_debug_profiler.php');
	//-- register extra logs from framework
	SmartDebugProfiler::register_extra_debug_log('SmartMarkersTemplating', 'registerOptimizationHintsToDebugLog');
	//-- register extra internal logs from framework
	if(SmartEnvironment::ifInternalDebug()) {
		SmartDebugProfiler::register_extra_debug_log('SmartFrameworkRegistry', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartEnvironment', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('Smart', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartFileSysUtils', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartHashCrypto', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartAuth', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartUtils', 'registerInternalCacheToDebugLog');
		SmartDebugProfiler::register_extra_debug_log('SmartMarkersTemplating', 'registerInternalCacheToDebugLog');
	} //end if
	//--
} //end if
//--------------------------------------- LOAD APP.REQUEST (HANDLER)
require('lib/run/app-request.php'); // REGISTER REQUEST INPUT VARIABLES (GET, POST, COOKIE, SERVER)
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
if(!class_exists('SmartAppBootstrap')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppBootstrap is missing ...');
} //end if
if(!is_subclass_of('SmartAppBootstrap', 'SmartInterfaceAppBootstrap', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppBootstrap must implement the SmartInterfaceAppBootstrap ...');
} //end if
if(!class_exists('SmartAppInfo')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppInfo is missing ...');
} //end if
if(!is_subclass_of('SmartAppInfo', 'SmartInterfaceAppInfo', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAppInfo must implement the SmartInterfaceAppInfo ...');
} //end if
//========================= APP.BOOTSTRAP: INIT
SmartAppBootstrap::Initialize(); // MUST create all required dirs by Smart.Framework ; MUST load all custom adapters if set as: Persistent Cache, Text Translations and Custom Session
//========================= APP.BOOTSTRAP: INIT Check: if bootstrap init loaded the custom adapters if set, otherwise fall back to default adapters
if(!class_exists('SmartPersistentCache')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartPersistentCache is missing ...');
} //end if
if(!is_subclass_of('SmartPersistentCache', 'SmartAbstractPersistentCache')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartPersistentCache must be extended from the Class SmartAbstractPersistentCache ...');
} //end if
if(!class_exists('SmartAdapterTextTranslations')) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAdapterTextTranslations is missing ...');
} //end if
if(!is_subclass_of('SmartAdapterTextTranslations', 'SmartInterfaceAdapterTextTranslations', true)) {
	@http_response_code(500);
	die('Smart.Framework // Runtime: the Class SmartAdapterTextTranslations must implement the SmartInterfaceAdapterTextTranslations ...');
} //end if
//========================= MONITORS
SmartFrameworkRuntime::SingleUser_Mode_Monitor(); // detect Maintenance SingleUser Mode ; if .ht-sf-singleuser-mode exists then Return 503
SmartFrameworkRuntime::High_Load_Monitor(); // detect and handle High Loads ; if detected Return 503 Too Busy
SmartFrameworkRuntime::Redirection_Monitor(); // controller redirection monitor: detect path info allowed, detect frontend or backend disabled
//========================= REGISTER UNIQUE ID COOKIE (required before run)
SmartFrameworkRuntime::SetVisitorEntropyIDCookie(); // will define the constant SMART_APP_VISITOR_COOKIE ; cookie will be set only if SMART_FRAMEWORK_UUID_COOKIE_NAME is non empty
SmartCache::setKey('smart-app-runtime', 'visitor-cookie', (string)SMART_APP_VISITOR_COOKIE);
//========================= APP.BOOTSTRAP: RUN
SmartAppBootstrap::Run(); // MUST load the modules/app/app-bootstrap.inc.php
//=========================
//--

//--
//=========================
//==
if(defined('SMART_FRAMEWORK_APP_RUNTIME')) {
	@http_response_code(500);
	die('Smart.Framework / App-Runtime already loaded ...');
} //end if
//==
define('SMART_FRAMEWORK_APP_RUNTIME', 'SET'); // stop here !
//==
//=========================


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
 * @version 	v.20221208
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
 * @version 	v.20221208
 *
 */
interface SmartInterfaceAppInfo {

	// :: INTERFACE


	//=====
	/**
	 * Test if a specific App Template Exists
	 * RETURN: true or false
	 */
	public static function TestIfTemplateExists(string $y_template_name) : bool;
	//=====


	//=====
	/**
	 * Test if a specific App Module Exists
	 * RETURN: true or false
	 */
	public static function TestIfModuleExists(string $y_module_name) : bool;
	//=====


} //END INTERFACE

//==================================================================================
//================================================================================== INTERFACE END
//==================================================================================


// end of php code
