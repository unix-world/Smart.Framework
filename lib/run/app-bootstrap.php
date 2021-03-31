<?php
// [APP - Bootstrap / Smart.Framework]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======================================================
// Smart-Framework - App Bootstrap :: r.20210331
// DEPENDS: SmartFramework, SmartFrameworkRuntime
//======================================================
// This file can be customized per App ...
// DO NOT MODIFY ! IT IS CUSTOMIZED FOR: Smart.Framework
//======================================================

// [PHP8]

// [REGEX-SAFE-OK]

//##### WARNING: #####
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
// This code part is handling the bootstrap libs, that can be changed by setting the following constants in etc/init.php:
// * SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER
// * SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM
// * SMART_FRAMEWORK_SESSION_HANDLER
//####################

//== Set Persistent-Cache Adapter (or use none/blackhole)
if(defined('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER') AND ((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER != '')) {
	switch((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER) {
		case 'redis': // Redis is significant faster than DBA or SQLite but needs RAM memory which could not be available ...
			if(!is_array($configs['redis'])) {
				Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the Redis config is not available');
				die('');
			} //end if
			require('lib/app/persistent-cache-redis.php'); // load the Redis based persistent cache
			break;
		case 'mongodb': // MongoDB is faster than DBA or SQLite and can scale in a big data cluster (slower than Redis) ...
			if(!is_array($configs['mongodb'])) {
				Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the MongoDB config is not available');
				die('');
			} //end if
			require('lib/app/persistent-cache-mongodb.php'); // load the MongoDB based persistent cache
			break;
		case 'dba': // DBA is significant faster than SQLite
			if((!is_array($configs['dba'])) OR (SmartDbaUtilDb::isDbaAndHandlerAvailable() !== true)) {
				Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the DBA config is not available or wrong');
				die('');
			} //end if
			require('lib/app/persistent-cache-dba.php'); // load the DBA based persistent cache
			break;
		case 'sqlite': // this is designed to be used only if DBA is N/A or for small websites
			if((!is_array($configs['sqlite'])) OR (!class_exists('SQLite3'))) {
				Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the SQLite config is not available or wrong');
				die('');
			} //end if
			require('lib/app/persistent-cache-sqlite.php'); // load the SQLite3 based persistent cache (this uses fatal err)
			break;
		default:
			SmartFrameworkRuntime::requirePhpScript((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER, 'Custom Persistent Cache Handler');
			if(!class_exists('SmartPersistentCache', false)) { // explicit autoload is false
				Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the php file is missing the `SmartPersistentCache` class');
				die('');
			} //end if
	} //end switch
} else {
	require('lib/app/persistent-cache-x-blackhole.php'); // load the Blackhole (x-none) persistent cache which will implement only definitions and is required for compatibility but having no storage at all
} //end if else
//== Set Text-Translations Adapter (depends on Persistent-Cache)
if(defined('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM') AND ((string)SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM != '')) {
	SmartFrameworkRuntime::requirePhpScript((string)SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM, 'Custom Translations Adapter');
	if(!class_exists('SmartAdapterTextTranslations', false)) { // explicit autoload is false
		Smart::raise_error('ERROR: The Custom Translations Adapter handler is set to: '.SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM.' but the php file is missing the `SmartAdapterTextTranslations` class');
		die('');
	} //end if
} else {
	require('lib/app/translations-adapter-yaml.php'); // text translations (YAML based adapter)
} //end if else
//== Set Custom Session Handler Adapter if any (or fallback to files)
if(defined('SMART_FRAMEWORK_SESSION_HANDLER') AND ((string)SMART_FRAMEWORK_SESSION_HANDLER !== 'files')) {
	switch((string)SMART_FRAMEWORK_SESSION_HANDLER) {
		case 'redis': // Redis is significant faster than DBA or SQLite but needs RAM memory which could not be available ...
			if(!is_array($configs['redis'])) {
				Smart::raise_error('ERROR: The Custom Session Handler is set to: '.SMART_FRAMEWORK_SESSION_HANDLER.' but the Redis config is not available');
				die('');
			} //end if
			require('lib/app/custom-session-redis.php'); // use custom session based on Redis
			break;
		case 'mongodb': // MongoDB is faster than DBA or SQLite and can scale in a big data cluster (slower than Redis) ...
			if(!is_array($configs['mongodb'])) {
				Smart::raise_error('ERROR: The Custom Session Handler is set to: '.SMART_FRAMEWORK_SESSION_HANDLER.' but the MongoDB config is not available');
				die('');
			} //end if
			require('lib/app/custom-session-mongodb.php'); // use custom session based on MongoDB
			break;
		case 'dba': // DBA is significant faster than SQLite
			if((!is_array($configs['dba'])) OR (SmartDbaUtilDb::isDbaAndHandlerAvailable() !== true)) {
				Smart::raise_error('ERROR: The Custom Session Handler is set to: '.SMART_FRAMEWORK_SESSION_HANDLER.' but the DBA config is not available or wrong');
				die('');
			} //end if
			require('lib/app/custom-session-dba.php'); // use custom session based on DBA
			break;
		case 'sqlite': // this is designed to be used only if DBA is N/A or for small websites
			if((!is_array($configs['sqlite'])) OR (!class_exists('SQLite3'))) {
				Smart::raise_error('ERROR: The Custom Session Handler is set to: '.SMART_FRAMEWORK_SESSION_HANDLER.' but the SQLite config is not available or wrong');
				die('');
			} //end if
			require('lib/app/custom-session-sqlite.php'); // use custom session based on SQLite3 (this uses fatal err)
			break;
		default:
			SmartFrameworkRuntime::requirePhpScript((string)SMART_FRAMEWORK_SESSION_HANDLER, 'Custom Session Handler');
			if(!class_exists('SmartCustomSession', false)) { // explicit autoload is false
				Smart::raise_error('ERROR: The Custom Session Handler is set to: '.SMART_FRAMEWORK_SESSION_HANDLER.' but the php file is missing the `SmartCustomSession` class');
				die('');
			} //end if
	} //end switch
} else {
	// using files based session (default, built-in)
} //end if else
//==


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

define('SMART_SOFTWARE_APP_NAME', 'smart.framework.app'); // REQUIRED BY SMART RUNTIME

/**
 * Class Smart.Framework App.BootStrap
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
 *
 * @version 	v.20210303
 *
 */
final class SmartAppBootstrap implements SmartInterfaceAppBootstrap {

	// ::

	private static $isRunning 		= false;
	private static $authCompleted 	= false;


	//======================================================================
	// REQUIRED
	public static function Run() {
		//--
		global $configs; // expose to app-custom-bootstrap.inc.php
		//--
		if(self::$isRunning !== false) {
			http_response_code(500);
			die(SmartComponents::http_message_500_internalerror('App Boostrap is already running ...'));
			return;
		} //end if
		self::$isRunning = true;
		//--
		require('modules/app/app-custom-bootstrap.inc.php'); // custom boostrap code (this can permanently start session or connect to a DB server or ...)
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Authenticate($area) {
		//--
		global $configs; // expose to app-auth-*.inc.php
		//--
		if(self::$authCompleted !== false) {
			http_response_code(500);
			die(SmartComponents::http_message_500_internalerror('App Boostrap Auth already loaded ...'));
			return;
		} //end if
		self::$authCompleted = true;
		//--
		switch((string)$area) {
			case 'index':
				require('modules/app/app-auth-index.inc.php');
				break;
			case 'admin':
				require('modules/app/app-auth-admin.inc.php');
				break;
			default:
				$msg = 'Invalid Authentication Realm: '.$area;
				Smart::raise_error(
					'App Bootstrap / Authenticate: '.$msg,
					'App Bootstrap / Authenticate: '.$msg // msg to display
				);
				die('Invalid Auth Realm'); // just in case
		} //end switch
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartAppInfo
 *
 * Provides some methods for integration between the Smart.Framework App/Modules.
 *
 * <code>
 * // Usage example:
 * SmartAppInfo::some_method_of_this_class(...);
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	-
 * @version 	v.20210303
 * @package 	Application
 *
 */
final class SmartAppInfo implements SmartInterfaceAppInfo {

	// ::

	private static $cache = array();


	//=====
	/**
	 * Test if Application Template Exists in etc/templates/
	 *
	 * @param 	STRING 	$y_template_name 	:: The template dir name (Ex: for 'etc/templates/something', this parameter would be: 'something'
	 *
	 * @return 	BOOLEAN						:: TRUE if template exists, FALSE if not detected
	 */
	public static function TestIfTemplateExists($y_template_name) {
		//--
		$y_template_name = Smart::safe_filename((string)$y_template_name);
		if((string)$y_template_name == '') {
			return false;
		} //end if
		//--
		$test_cache = '';
		if(array_key_exists((string)'TestIfTemplateExists:'.$y_template_name, self::$cache)) {
			$test_cache = (string) self::$cache['TestIfTemplateExists:'.$y_template_name];
		} //end if
		//--
		if((string)$test_cache != '') { // get cached test
			//--
			if((string)$test_cache == 'YES') {
				$exists = true;
			} else {
				$exists = false;
			} //end if
			//--
		} else { // real test
			//--
			if(SmartFileSystem::is_type_dir('etc/templates/'.$y_template_name.'/')) {
				$exists = true;
				self::$cache['TestIfTemplateExists:'.$y_template_name] = 'YES';
			} else {
				$exists = false;
				self::$cache['TestIfTemplateExists:'.$y_template_name] = 'NO';
			} //end if
			//--
		} //end if else
		//--
		return (bool) $exists;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if Application Module Exists in modules/
	 *
	 * @param 	STRING 	$y_module_name 		:: The short module name (Ex: for 'modules/mod-something', this parameter would be: 'mod-something'
	 *
	 * @return 	BOOLEAN						:: TRUE if module exists, FALSE if not detected
	 */
	public static function TestIfModuleExists($y_module_name) {
		//--
		$y_module_name = Smart::safe_filename((string)$y_module_name);
		if((string)$y_module_name == '') {
			return false;
		} //end if
		//--
		$test_cache = '';
		if(array_key_exists((string)'TestIfModuleExists:'.$y_module_name, self::$cache)) {
			$test_cache = (string) self::$cache['TestIfModuleExists:'.$y_module_name];
		} //end if
		//--
		if((string)$test_cache != '') { // get cached test
			//--
			if((string)$test_cache == 'YES') {
				$exists = true;
			} else {
				$exists = false;
			} //end if
			//--
		} else { // real test
			//--
			if(SmartFileSystem::is_type_dir('modules/'.$y_module_name.'/')) {
				$exists = true;
				self::$cache['TestIfModuleExists:'.$y_module_name] = 'YES';
			} else {
				$exists = false;
				self::$cache['TestIfModuleExists:'.$y_module_name] = 'NO';
			} //end if
			//--
		} //end if else
		//--
		return (bool) $exists;
		//--
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
