<?php
// [APP - Bootstrap / Smart.Framework]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======================================================
// Smart-Framework - App Bootstrap :: r.20240119
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

define('SMART_SOFTWARE_APP_NAME', 'smart.framework.app'); // REQUIRED BY SMART RUNTIME

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class Smart.Framework App.BootStrap
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
 *
 * @version 	v.20250620
 * @package 	Application
 *
 */
final class SmartAppBootstrap implements SmartInterfaceAppBootstrap {

	// ::

	public const SF_LANG_COOKIE_NAME 				= 'Sf_Lang';

	private static bool $initCompleted 				= false;	// flag to avoid re-create required dirs
	private static bool $authCompleted 				= false; 	// flag to avoid re-authenticate
	private static bool $isRunning 					= false; 	// flag to avoid re-run

	private static bool $isSetLanguageByCookie 		= false; 	// flag to avoid set again language by cookie
	private static bool $isSetLanguageBySubdomain 	= false; 	// flag to avoid set again language by subdomain

	//===== [PUBLIC:REQUIRED]


	//======================================================================
	public static function Initialize() : void {
		//--
		global $configs;
		//--
		if(self::$initCompleted !== false) {
			http_response_code(500);
			die((string)SmartComponents::http_message_500_internalerror('App Boostrap is already initialized ...'));
			return; // avoid run after it was used by runtime
		} //end if
		self::$initCompleted = true;
		//--
		if(!is_array($configs)) { // check here to avoid do this check in each private or in Run() or Authenticate() which are called after this Initialize()
			http_response_code(500);
			die((string)SmartComponents::http_message_500_internalerror('Configs is not an array ...'));
			return;
		} //end if
		//--
		self::createRequiredDirs(); 				// must be first
		//--
		self::setPersistentCacheAdapter(); 			// may depend on dirs if using file system like dba or sqlite
		self::setTextTranslationsAdapter(); 		// depends on persistent cache
		self::setCustomSessionHandlerAdapter(); 	// must be after persistent cache
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Run() : void {
		//--
		global $configs; // expose to app-bootstrap.inc.php
		//--
		if(self::$isRunning !== false) {
			http_response_code(500);
			die((string)SmartComponents::http_message_500_internalerror('App Boostrap is already running ...'));
			return; // avoid run after it was used by runtime
		} //end if
		self::$isRunning = true;
		//--
		require('modules/app/app-bootstrap.inc.php'); // custom boostrap code (this can permanently start session or connect to a DB server or ...)
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Authenticate(string $area) : void { // THIS SHOULD BE RUN IN MIDDLEWARE IDX/ADM|TSK ONLY
		//--
		global $configs; // expose to app-auth-*.inc.php
		//--
		if(self::$authCompleted !== false) {
			http_response_code(500);
			die((string)SmartComponents::http_message_500_internalerror('App Boostrap Auth already loaded ...'));
			return; // avoid run after it was used by runtime
		} //end if
		self::$authCompleted = true;
		//--
		switch((string)$area) {
			case 'index': // idx
				require('modules/app/app-auth-idx.inc.php');
				break;
			case 'admin': // adm + tsk
				require('modules/app/app-auth-adm-tsk.inc.php');
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


	//===== [PUBLIC:CUSTOM]


	//======================================================================
	// Sets the language cookie with the current language
	// this is useful in a cluster where master is using by domain language set and slaves by cookie
	// after master sets the language by domain can also call this method to keep the language cookie in sync with subdomain thus when visitor is redirected on different (slave) server to keep the same language
	public static function AppSetLanguageCookie() : bool {
		//--
		return (bool) SmartUtils::set_cookie((string)self::SF_LANG_COOKIE_NAME, (string)SmartTextTranslations::getLanguage());
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Handles the Language Detection by Cookie (SF_LANG_COOKIE_NAME)
	// This is an alternative for set language by subdomain
	public static function AppSetLanguageByCookie(?string $default_lang=null) : void {
		//--
		if(self::$isSetLanguageByCookie !== false) {
			return; // avoid run after it was used by runtime
		} //end if
		self::$isSetLanguageByCookie = true;
		//--
		if($default_lang === null) {
			$default_lang = (string) SmartTextTranslations::getDefaultLanguage();
		} elseif(!SmartTextTranslations::validateLanguage((string)$default_lang)) {
			return;
		} //end if
		//--
		$crrLang = (string) strtolower((string)trim((string)SmartFrameworkRegistry::getCookieVar((string)self::SF_LANG_COOKIE_NAME)));
		if(SmartTextTranslations::validateLanguage((string)$crrLang)) {
			SmartTextTranslations::setLanguage((string)$crrLang); // set default language or custom
			return;
		} //end if
		//--
		SmartTextTranslations::setLanguage((string)$default_lang); // set default language or custom
		//--
	} //END FUNCTION


	//======================================================================
	// Handles the Language Detection by SubDomain
	// if used this will set the app language by sub-domain
	// this will work only if more than one languages are defined in configs, otherwise will raise an error
	// NOTICE: by default the language can be set only by URL Parameter or Cookie
	// if this is used can set language by subdomain ; it must be called in modules/app/app-bootstrap.inc.php
	// it can be set by checking if admin area is set to true to handle only one of the index or admin areas ; or if no condition will handle both
	// 1st param: 'www' will be used for the default language ; must not contain dots
	// 2nd param: if TRUE will redirect the 'en' subdomain (because matches the default language as set in SMART_FRAMEWORK_DEFAULT_LANG) to the subdomain to 'www' (1st parameter)
	// 3rd param: if TRUE will redirect all other subdomains (except 'www' and the 'en' subdomains), to 'www' (1st parameter)
	// 4th param: if TRUE and 3rd param is FALSE will show 404 for all other subdomains (except 'www' and the 'en' subdomains)
	// 5th param: ARRAY of sub-domains to be excepted (valid languages must not be includded here, they are managed separately) or empty array if none ; ex: [ 'sdom1', 'sdom2' ]
	// 6th param ?STRING ; Default is NULL ; the default language override ; if NOT NULL set to: 'de' or other valid language ; custom default language for default subdomain
	public static function AppSetLanguageBySubdomain(string $default_subdomain='www', bool $redirect_default_language_to_default_subdomain=true, bool $redirect_other_subdomains=false, bool $notfound_other_subdomains=false, array $except_subdomains=[], ?string $default_lang=null) : void {
		//--
		if(self::$isSetLanguageBySubdomain !== false) {
			return; // avoid run after it was used by runtime
		} //end if
		self::$isSetLanguageBySubdomain = true;
		//--
		if($default_lang === null) {
			$default_lang = (string) SmartTextTranslations::getDefaultLanguage();
		} elseif(!SmartTextTranslations::validateLanguage((string)$default_lang)) {
			return;
		} //end if
		//--
		$arr_available_languages = (array) SmartTextTranslations::getAvailableLanguages(); // ex: ['en', 'ro']
		if(Smart::array_size($arr_available_languages) <= 1) {
			return; // for only one language that is also the default one, make non sense
		} //end if
		//--
		$default_subdomain = (string) trim((string)$default_subdomain);
		if(
			((string)$default_subdomain != '')
			AND
			(
				(strpos((string)$default_subdomain, '-') === 0) OR
				(substr((string)$default_subdomain, -1, 1) === '-') OR
				(!preg_match('/^[a-z0-9\-]{1,63}$/', (string)$default_subdomain))
			)
		) {
			return; // invalid default subdomain ; contain only standard ASCII alphanumeric characters a to z; numerals 0 to 9 and/or hyphens (-) and not underscore ; not begin or end with a hyphen (-)
		} //end if
		//--
		if($redirect_default_language_to_default_subdomain === true) {
			unset($arr_available_languages[(string)$default_lang]); // default language must be unset, it is mapped to the $default_subdomain
		} //end if
		//--
		$pdom = (string) trim((string)SmartUtils::get_server_current_subdomain_name());
		//--
		if((string)$pdom != (string)$default_subdomain) {
			//--
			$except_subdom = false;
			if((int)Smart::array_size($except_subdomains) > 0) {
				if(in_array((string)$pdom, (array)$except_subdomains)) {
					$except_subdom = true;
				} //end if
			} //end if
			//--
			if(((string)$pdom != (string)$default_lang) AND (SmartTextTranslations::validateLanguage((string)$pdom))) { // other languages
				SmartTextTranslations::setLanguage((string)$pdom); // set only other languages if valid: ro, de, ...
				return;
			} elseif($except_subdom !== true) {
				if(
					(($redirect_default_language_to_default_subdomain === true) AND ((string)$pdom == (string)$default_lang))
					OR
					(($redirect_other_subdomains === true) AND ((string)$pdom != (string)$default_lang) AND ((string)$pdom != (string)$default_subdomain) AND (!in_array((string)$pdom, (array)$arr_available_languages)))
				) {
					$port = (int) SmartUtils::get_server_current_port();
					if((int)$port <= 0) {
						$port = 80;
					} //end if
					if(((int)$port == 80) OR ((int)$port == 443)) {
						$port = '';
					} else {
						$port = ':'.$port;
					} //end if else
					$redir = (string) SmartUtils::get_server_current_protocol().(((string)$default_subdomain != '') ? $default_subdomain.'.' : '').SmartUtils::get_server_current_basedomain_name().$port.SmartUtils::get_server_current_request_uri();
					http_response_code(302); // temporary redirect if the language code is not valid
					SmartFrameworkRuntime::outputHttpSafeHeader('Location: '.$redir); // force redirect
					die((string)SmartComponents::http_status_message('Redirect', 'Language:'.$pdom, '<br><a href="'.Smart::escape_html((string)$redir).'">Click here if you are not redirected</a><br><br>', '3xx')); // stop here, mandatory
				} elseif(($redirect_other_subdomains !== true) AND ((string)$pdom != (string)$default_lang) AND ((string)$pdom != (string)$default_subdomain) AND (!in_array((string)$pdom, (array)$arr_available_languages))) {
					if($notfound_other_subdomains === true) {
						http_response_code(404);
						die((string)SmartComponents::http_message_404_notfound('Invalid Sub-Domain ...'));
					} //end if
				} //end if else
			} //end if else
			//--
		} //end if
		//--
		SmartTextTranslations::setLanguage((string)$default_lang); // set default language or custom
		//--
	} //END FUNCTION
	//======================================================================


	//===== [PRIVATES]


	//======================================================================
	private static function createRequiredDirs() : void {
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//-- tmp dir
		$dir = 'tmp/';
		if(!SmartFileSystem::is_type_dir($dir)) {
			SmartFileSystem::dir_create($dir);
		} //end if
		if((!SmartFileSystem::is_type_dir($dir)) OR (!SmartFileSystem::have_access_write($dir))) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
				'App Init ERROR :: TMP @ '.substr((string)sprintf('%o', (string)fileperms('/tmp')), -4) // this must be explicit if failed to write to TMP folder ... it means cannot log !
			);
			return;
		} //end if
		if(!SmartEnvironment::ifDebug()) {
			if(SmartFileSystem::is_type_file('tmp/SMART-FRAMEWORK__DEBUG-ON')) {
				if(SmartFileSystem::is_type_dir('tmp/logs/idx/')) {
					SmartFileSystem::dir_delete('tmp/logs/idx/', true);
				} //end if
				if(SmartFileSystem::is_type_dir('tmp/logs/adm/')) {
					SmartFileSystem::dir_delete('tmp/logs/adm/', true);
				} //end if
				SmartFileSystem::delete('tmp/SMART-FRAMEWORK__DEBUG-ON');
			} //end if
		} else {
			SmartFileSystem::write_if_not_exists('tmp/SMART-FRAMEWORK__DEBUG-ON', 'DEBUG:ON');
		} //end if else
		if(!SmartFileSystem::is_type_file($dir.'.htaccess')) {
			SmartFileSystem::write($dir.'.htaccess', trim((string)SMART_FRAMEWORK_HTACCESS_NOINDEXING)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_NOEXECUTION)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_FORBIDDEN)."\n"); // {{{SYNC-TMP-FOLDER-HTACCESS}}}
			if(!SmartFileSystem::is_type_file($dir.'.htaccess')) {
				Smart::raise_error(
					'#SMART-FRAMEWORK-CREATE-REQUIRED-FILES#'."\n".'A required file cannot be created in #TMP: `'.$dir.'.htaccess`',
					'App Init ERROR TMP#ACCESS' // this must be explicit if failed to write to TMP folder ... it means cannot log !
				);
				return;
			} //end if
		} //end if
		if(!SmartFileSystem::is_type_file($dir.'index.html')) {
			SmartFileSystem::write($dir.'index.html', '');
			if(!SmartFileSystem::is_type_file($dir.'index.html')) {
				Smart::raise_error(
					'#SMART-FRAMEWORK-CREATE-REQUIRED-FILES#'."\n".'A required file cannot be created in #TMP: `'.$dir.'index.html`',
					'App Init ERROR TMP#INDEX-HTML' // this must be explicit if failed to write to TMP folder ... it means cannot log !
				);
				return;
			} //end if
		} //end if
		//-- tmp logs dir
		$dir = 'tmp/logs/';
		if(!SmartFileSystem::is_type_dir($dir)) {
			SmartFileSystem::dir_create($dir);
			if(SmartFileSystem::is_type_dir($dir)) {
				SmartFileSystem::write($dir.'index.html', '');
			} //end if
		} // end if
		if(!SmartFileSystem::have_access_write($dir)) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
				'App Init ERROR TMP#LOGS' // this must be explicit if failed to write to TMP folder ... it means cannot log !
			);
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($dir.'.htaccess')) {
			SmartFileSystem::write($dir.'.htaccess', trim((string)SMART_FRAMEWORK_HTACCESS_NOINDEXING)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_NOEXECUTION)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_FORBIDDEN)."\n"); // {{{SYNC-TMP-FOLDER-HTACCESS}}}
		} //end if
		//--
		if(!SmartEnvironment::isAdminArea()) { // IDX
			//-- tmp logs/idx dir
			$dir = 'tmp/logs/idx/';
			if(!SmartFileSystem::is_type_dir($dir)) {
				SmartFileSystem::dir_create($dir);
				if(SmartFileSystem::is_type_dir($dir)) {
					SmartFileSystem::write($dir.'index.html', '');
				} //end if
			} // end if
			if(!SmartFileSystem::have_access_write($dir)) {
				Smart::raise_error(
					__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
					'App Init ERROR TMP#LOGS#IDX' // this must be explicit if failed to write to TMP folder ... it means cannot log !
				);
				return;
			} //end if
			//--
		} else { // ADM / TSK
			//--
			if(!SmartEnvironment::isTaskArea()) { // ADM
				//-- tmp logs/admin dir
				$dir = 'tmp/logs/adm/';
				if(!SmartFileSystem::is_type_dir($dir)) {
					SmartFileSystem::dir_create($dir);
					if(SmartFileSystem::is_type_dir($dir)) {
						SmartFileSystem::write($dir.'index.html', '');
					} //end if
				} // end if
				if(!SmartFileSystem::have_access_write($dir)) {
					Smart::raise_error(
						__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
						'App Init ERROR TMP#LOGS#ADM' // this must be explicit if failed to write to TMP folder ... it means cannot log !
					);
					return;
				} //end if
				//--
			} else { // TSK
				//-- tmp logs/task dir
				$dir = 'tmp/logs/tsk/';
				if(!SmartFileSystem::is_type_dir($dir)) {
					SmartFileSystem::dir_create($dir);
					if(SmartFileSystem::is_type_dir($dir)) {
						SmartFileSystem::write($dir.'index.html', '');
					} //end if
				} // end if
				if(!SmartFileSystem::have_access_write($dir)) {
					Smart::raise_error(
						__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
						'App Init ERROR TMP#LOGS#TSK' // this must be explicit if failed to write to TMP folder ... it means cannot log !
					);
					return;
				} //end if
				//--
			} //end if else
			//--
		} //end if else
		//-- tmp cache dir
		$dir = 'tmp/cache/';
		if(!SmartFileSystem::is_type_dir($dir)) {
			SmartFileSystem::dir_create($dir);
			if(SmartFileSystem::is_type_dir($dir)) {
				SmartFileSystem::write($dir.'index.html', '');
			} //end if
		} // end if
		if(!SmartFileSystem::have_access_write($dir)) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
				'App Init ERROR TMP#CACHE' // this must be explicit if failed to write to TMP folder ... it means cannot log !
			);
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($dir.'.htaccess')) {
			SmartFileSystem::write($dir.'.htaccess', trim((string)SMART_FRAMEWORK_HTACCESS_NOINDEXING)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_NOEXECUTION)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_FORBIDDEN)."\n"); // {{{SYNC-TMP-FOLDER-HTACCESS}}}
		} //end if
		//-- tmp sessions dir
		$dir = 'tmp/sessions/';
		if(!SmartFileSystem::is_type_dir($dir)) {
			SmartFileSystem::dir_create($dir);
			if(SmartFileSystem::is_type_dir($dir)) {
				SmartFileSystem::write($dir.'index.html', '');
			} //end if
		} // end if
		if(!SmartFileSystem::have_access_write($dir)) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: `'.$dir.'` is NOT writable !',
				'App Init ERROR TMP#SESS' // this must be explicit if failed to write to TMP folder ... it means cannot log !
			);
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($dir.'.htaccess')) {
			SmartFileSystem::write($dir.'.htaccess', trim((string)SMART_FRAMEWORK_HTACCESS_NOINDEXING)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_NOEXECUTION)."\n".trim((string)SMART_FRAMEWORK_HTACCESS_FORBIDDEN)."\n"); // {{{SYNC-TMP-FOLDER-HTACCESS}}}
		} //end if
		//-- wpub dir
		$dir = 'wpub/'; // {{{SYNC-WPUB-DIR}}}
		$ctrlfile = $dir.'#wpub';
		$htfile = $dir.'.htaccess';
		$robotsfile = $dir.'robots.txt';
		if(!SmartFileSystem::is_type_dir($dir)) {
			SmartFileSystem::dir_create($dir);
			if(SmartFileSystem::is_type_dir($dir)) {
				SmartFileSystem::write($dir.'index.html', '');
				SmartFileSystem::write($robotsfile, 'User-agent: *'."\n".'Disallow: *'); // by default avoid robots to index it ; this file can be edited manually
			} //end if
		} // end if
		if(!SmartFileSystem::is_type_dir($dir)) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: #WEB-PUBLIC Folder: `'.$dir.'` does NOT exists !',
				'App Init ERROR'
			);
			return;
		} //end if
		if(!SmartFileSystem::have_access_write($dir)) {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: #WEB-PUBLIC Folder: `'.$dir.'` is NOT writable !',
				'App Init ERROR'
			);
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($ctrlfile)) {
			SmartFileSystem::write($ctrlfile, 'FileName: #wpub (#WEB-PUBLIC)'."\n".'Created by: App-Runtime'."\n".date('Y-m-d H:i:s O'));
			if(!SmartFileSystem::is_type_file($ctrlfile)) {
				Smart::raise_error(
					__METHOD__."\n".'Cannot Connect to FileSystem #WEB-PUBLIC, the control file is missing `'.$ctrlfile.'`',
					'App Init ERROR'
				);
				return;
			} //end if
		} //end if
		if(!SmartFileSystem::is_type_file($htfile)) {
			SmartFileSystem::write($htfile, (string)trim((string)SMART_FRAMEWORK_HTACCESS_NOEXECUTION)."\n"); // trim((string)SMART_FRAMEWORK_HTACCESS_NOINDEXING)."\n".
			if(!SmartFileSystem::is_type_file($htfile)) {
				Smart::raise_error(
					__METHOD__."\n".'The `.htaccess` file is missing on FileSystem #WEB-PUBLIC: '.$htfile,
					'App Init ERROR'
				);
				return;
			} //end if
		} //end if
		//--
		$dir = (string) SmartFileSystem::APP_DB_FOLDER; // {{{SYNC-APP-DB-FOLDER}}}
		$err = (string) SmartFileSystem::create_protected_dir((string)$dir);
		if((string)$err != '') {
			Smart::raise_error(
				__METHOD__."\n".'General ERROR :: `'.$dir.'` create: '.$err,
				'App Init ERROR'
			);
			return;
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	private static function setPersistentCacheAdapter() : void { // Set Persistent-Cache Adapter (or use none/blackhole)
		//--
		global $configs;
		//--
		if(class_exists('SmartPersistentCache')) {
			return;
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER') AND is_string(SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER) AND ((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER != '')) {
			//--
			switch((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER) {
				case 'redis': // Redis is significant faster than DBA or SQLite but needs RAM memory which could not be available ...
					if((!isset($configs['redis'])) OR (!is_array($configs['redis']))) {
						Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the Redis config is not available');
						die('');
					} //end if
					require('lib/app/persistent-cache-redis.php'); // load the Redis based persistent cache
					break;
				case 'mongodb': // MongoDB is faster than DBA or SQLite and can scale in a big data cluster (slower than Redis) ...
					if((!isset($configs['mongodb'])) OR (!is_array($configs['mongodb']))) {
						Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the MongoDB config is not available');
						die('');
					} //end if
					require('lib/app/persistent-cache-mongodb.php'); // load the MongoDB based persistent cache
					break;
				case 'dba': // DBA is significant faster than SQLite
					if((!isset($configs['dba'])) OR (!is_array($configs['dba'])) OR (SmartDbaUtilDb::isDbaAndHandlerAvailable() !== true)) {
						Smart::raise_error('ERROR: The Custom Persistent Cache handler is set to: '.SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER.' but the DBA config is not available or wrong');
						die('');
					} //end if
					require('lib/app/persistent-cache-dba.php'); // load the DBA based persistent cache
					break;
				case 'sqlite': // this is designed to be used only if DBA is N/A or for small websites
					if((!isset($configs['sqlite'])) OR (!is_array($configs['sqlite'])) OR (!class_exists('SQLite3'))) {
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
			//--
		} else {
			//--
			// using Blackhole SmartPersistentCache (default, built-in Framework) / or using a 3rd party class
			//--
		} //end if else
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	private static function setTextTranslationsAdapter() : void { // Set Text-Translations Adapter (depends on Persistent-Cache)
		//--
		global $configs;
		//--
		if(class_exists('SmartAdapterTextTranslations')) {
			return;
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM') AND is_string(SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM) AND ((string)trim((string)SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM) != '')) {
			//--
			SmartFrameworkRuntime::requirePhpScript((string)SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM, 'Custom Translations Adapter');
			if(!class_exists('SmartAdapterTextTranslations', false)) { // explicit autoload is false
				Smart::raise_error('ERROR: The Custom Translations Adapter handler is set to: '.SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM.' but the php file is missing the `SmartAdapterTextTranslations` class');
				die('');
			} //end if
			//--
		} else {
			//--
			require('lib/app/translations-adapter-yaml.php'); // text translations (YAML based adapter)
			//--
		} //end if else
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	private static function setCustomSessionHandlerAdapter() : void { // Set Custom Session Handler Adapter if any (or fallback to files)
		//--
		global $configs;
		//--
		if(class_exists('SmartCustomSession')) {
			return;
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_SESSION_HANDLER') AND is_string(SMART_FRAMEWORK_SESSION_HANDLER) AND ((string)SMART_FRAMEWORK_SESSION_HANDLER !== 'files')) {
			//--
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
			//--
		} else {
			//--
			// using files based session (default, built-in PHP)
			//--
		} //end if else
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
 * @version 	v.20250107
 * @package 	Application
 *
 */
final class SmartAppInfo implements SmartInterfaceAppInfo {

	// ::

	private static $cache = [];


	//=====
	/**
	 * Test if Application Module Exists in modules/
	 *
	 * @param 	STRING 	$y_module_name 		:: The short module name (Ex: for 'modules/mod-something', this parameter would be: 'mod-something'
	 *
	 * @return 	BOOLEAN						:: TRUE if module exists, FALSE if not detected
	 */
	public static function TestIfModuleExists(string $y_module_name) : bool {
		//--
		$y_module_name = (string) Smart::safe_filename((string)$y_module_name);
		if((string)$y_module_name == '') {
			return false;
		} //end if
		//--
		$prefix = (string) __FUNCTION__;
		//--
		if(!array_key_exists((string)$prefix.':'.$y_module_name, (array)self::$cache)) {
			if(SmartFileSystem::is_type_dir('modules/'.$y_module_name.'/')) {
				self::$cache[(string)$prefix.':'.$y_module_name] = true;
			} else {
				self::$cache[(string)$prefix.':'.$y_module_name] = false;
			} //end if
		} //end if
		//--
		return (bool) self::$cache[(string)$prefix.':'.$y_module_name];
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if Application Template Exists in etc/templates/
	 *
	 * @param 	STRING 	$y_template_name 	:: The template dir name (Ex: for 'etc/templates/something', this parameter would be: 'something'
	 *
	 * @return 	BOOLEAN						:: TRUE if template exists, FALSE if not detected
	 */
	public static function TestIfTemplateExists(string $y_template_name) : bool {
		//--
		$y_template_name = (string) Smart::safe_filename((string)$y_template_name);
		if((string)$y_template_name == '') {
			return false;
		} //end if
		//--
		$prefix = (string) __FUNCTION__;
		//--
		if(!array_key_exists((string)$prefix.':'.$y_template_name, (array)self::$cache)) {
			if(SmartFileSystem::is_type_dir('etc/templates/'.$y_template_name.'/')) {
				self::$cache[(string)$prefix.':'.$y_template_name] = true;
			} else {
				self::$cache[(string)$prefix.':'.$y_template_name] = false;
			} //end if
		} //end if
		//--
		return (bool) self::$cache[(string)$prefix.':'.$y_template_name];
		//--
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
