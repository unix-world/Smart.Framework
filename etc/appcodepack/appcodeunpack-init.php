<?php
// [@[#[!NO-STRIP!]#]@]
// [AppCodeUnpack / INIT] v.20210812 s.20210812.1228
// (c) 2013-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7
// {{{SYNC-SMART-APP-INI-SETTINGS}}}

// ===== IMPORTANT =====
//	* NO VARIABLES SHOULD BE DEFINED IN THIS FILE ; ONLY CONSTANTS SHOULD BE DEFINED HERE TO AVOID LATER CHANGES !!!
//	* IF .htaccess PHP settings will be used, be sure to sync them with this file too for ini sets at the bottom of this file !
// ==================

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APP_CUSTOM_LOG_PATH')) { // for standalone apps this must be defined in the first line of the application # // const APP_CUSTOM_LOG_PATH = '#APPCODE-UNPACK#/'; // {{{SYNC-APPCODEUNPACK-FOLDER}}} ; security: do not define here but will be defined upon the (re)generation of appcodeunpack.php
	@http_response_code(500);
	die('Invalid App Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======= ####### Change the below values to match the specific case ! ####### =======

//--------------------------------------- APPCODEUNPACK AUTH VALUES
const APP_AUTH_ADMIN_ENFORCE_HTTPS = false;
const APP_AUTH_ADMIN_USERNAME = 'super.admin';
const APP_AUTH_ADMIN_PASSWORD = 'FCEB3D00DE28538B9B9311C10A6B003FBE491D64980F5CA63AD004CDCE47D900B22DD9B6D075C07D260306156A4EAB2D4767948AF722C5A924F6F3944DF5FF4013A6998FF2C9D142'; // should be the same as set in appcodepack.yaml ; the app unpack auth password ; default is: `The1pass!` ; use AppCode Deploy Password Encryption Utility to generate this pass ; If the deploy-secret changes, this pass have to be regenerated ...
//--------------------------------------- APPCODEUNPACK DEPLOY SETTINGS
const APPCODEPACK_DEPLOY_SECRET = 'Set-here-1-private-key-that-must-not-be-disclosed!'; // should be the same as set in appcodepack.yaml
const APPCODEPACK_DEPLOY_APPLIST = '<smart-framework.local>,<smart-framework.test>'; // the list of App-IDs to allow under this instance ; example: <app-id-1> ; example with multiple: <app-id-1>,<app-id-2>
//---------------------------------------

//============================================================ ALWAYS CHANGE !
//--------------------------------------- TIMEZONE
const SMART_FRAMEWORK_TIMEZONE =  						'UTC'; 										// The timezone for PHP (Example: Europe/London) ; default is: UTC
//--------------------------------------- TASK RUNTIME ALLOWED IP LIST
const SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS = 		'<127.0.0.1>,<::1>'; 						// APP Task service area allowed IPs ; can not be empty ; Tasks area is like Admin area but with some unrestricted features ; it is intended to be used mostly for development tasks ... ; the task.php can be excluded from a release or simply set below: const SMART_SOFTWARE_TASK_DISABLED = true;
//--------------------------------------- SECURITY
const SMART_FRAMEWORK_SECURITY_KEY =  					'private-key#0987654321'; 					// *** YOU HAVE TO CHANGE IT *** Sync this with the etc/init.php of the app that will be released ; This is the Security Key that will be used to generate secure hashes
//---------------------------------------
//============================================================

//#################################################
//#################################################
//#################################################
//################################################# BELOW THIS COMMENT VALUES SHOULD NOT BE CHANGED, BUT CAN BE CHANGED IF NEEDED SO
//#################################################
//#################################################
//#################################################

//======= ####### app task init values (standalone) ####### =======

//============================================================ REVIEW AND CHANGE IF PRODUCTION MODE
//--------------------------------------- APP NAMESPACE :: DO NOT CHANGE THIS FOR APPCODEUNPACK, IT SHOULD REMAIN LIKE THIS !!!
const SMART_SOFTWARE_NAMESPACE =  						'appcodeunpack.standalone';					// APP Namespace ID :: [a-z.], length 10..25 :: This should be used as a unique ID identifier for the application (aka application unique ID)
//--------------------------------------- RUNTIME ENVIRONMENT :: MUST BE 'prod' for appcodeunpack
const SMART_FRAMEWORK_ENV =  							'prod'; 									// APP Environment: can be set to 'dev' or 'prod' ; id set to 'prod' (production environment) will not log E_USER_NOTICE and E_DEPRECATED and will not display in-page error details but just log them ; for development mode set this to 'dev'
//--------------------------------------- COOKIES
const SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE = 	 	'Lax'; 										// The UniqueID Cookie SameSite Policy ; if not defined will not use any policy (old compatibility) ; If set must be one of these values: Lax / Strict or None ; set to None works only with a https secured connection because new browsers require this !
const SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME =  		0;											// The UniqueID Cookie LifeTime in seconds ; set to 0 for expire on browser close
const SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN =  		'';											// The UniqueID Cookie domain: set it (empty) `` for the current subdomain as `sdom.domain.tld` ; set it as `*` or explicit `domain.tld` for all sub-domains of domain.tld ; default is `` (empty) if not defined at all ; this is for advanced use of cookies management in sensitive production environments where you need per sub-domain encapsulated cookies
//--------------------------------------- PERSISTENT-CACHE HANDLER
const SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER =  		false; 										// Persistent Cache Handler ; If set to FALSE will use no handler ; If set otherwise can use Built-In: 'redis' or 'mongodb' or 'dba' or 'sqlite' ; or a Custom handler can be set as (example): 'modules/app/persistent-cache-custom-adapter.php'
//--------------------------------------- EXECUTION / RUNTIME LIMITS :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_MEMORY_LIMIT =  					'512M';										// Memory Limit Per Script (via PHP.INI) ; sync this with the value in .htaccess if defined ; a good value for production is 256M or 384M ; for development, with Debugging turned ON be sure to set a big value like 512M or 1024M !!
const SMART_FRAMEWORK_EXECUTION_TIMEOUT =  				610;										// Script Max Execution Time (Sync with the web server Timeout and PHP.INI)
const SMART_FRAMEWORK_NETSOCKET_TIMEOUT =  				120; 										// Network Socket (Stream) TimeOut in Seconds
const SMART_FRAMEWORK_NETSERVER_ID =  					0; 											// Load Balancing: Unique ID, integer+ (min=0 ; max=1295) ; this is used for the main purpose to be able to generate very unique UUIDS in a cluster of apps ; every server in the cluster running the same app must have a different ID
const SMART_FRAMEWORK_NETSERVER_MAXLOAD =  				false;										// Load Balancing and DDOS Protection against High Loads :: if set to FALSE will be ignored ; if set to a value > 0 if server load go over this value the server will enter in BUSY state (503 Too busy) ; by example a value of 90 means 90% load over 1 CPU core ; on multi cpus/cores value must be this value * number of cpus/cores ; a good and realistic setting is 100 * number of CPU/cores
//--------------------------------------- SSL CRYPTO OVERALL SETTINGS :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_SSL_MODE =  						'tls';										// SSL/TLS Mode: tls | sslv3
const SMART_FRAMEWORK_SSL_CIPHERS = 					'HIGH';										// SSL/TLS Context Ciphers: ciphers ; default: 'HIGH' ; generally allow only high ciphers
const SMART_FRAMEWORK_SSL_VFY_HOST = 					true;										// SSL/TLS Context Verify Host: verify_host ; default: true
const SMART_FRAMEWORK_SSL_VFY_PEER = 					false;										// SSL/TLS Context Verify Peer: verify_peer ; default: false ; this fails with some CAs
const SMART_FRAMEWORK_SSL_VFY_PEER_NAME = 				false;										// SSL/TLS Context Verify Peer Name: verify_peer_name ; default: false ; allow also wildcard names *
const SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED = 			true;										// SSL/TLS Context Allow Self-Signed Certificates: allow_self_signed ; default: true ; generally must allow self-signed certificates but verified above
const SMART_FRAMEWORK_SSL_DISABLE_COMPRESS = 			true;										// SSL/TLS Context Allow Self-Signed Certificates: disable_compression ; default: true ; help mitigate the CRIME attack vector
const SMART_FRAMEWORK_SSL_CA_FILE = 					'';											// SSL/TLS Context CA Path: cafile ; default: '' ; if non-empty, must point to something like 'etc/cacert.pem' or another path to a certification authority pem
//---------------------------------------- FILE SYSTEM SETTINGS :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_CHMOD_DIRS =  					0770;										// Folder Permissions: 	default is 0770 (can be used for both production or development) ; use: 0770 | 0750 | 0700 for production ; use: 0777 | 0775 | 0755 for development  	{{{SYNC-SMARTFRAMEWORK-DEFAULT-DIRS-CHMOD}}}
const SMART_FRAMEWORK_CHMOD_FILES =  					0660;										// File Permissions: 	default is 0660 (can be used for both production or development) ; use: 0660 | 0640 | 0600 for production ; use: 0666 | 0664 | 0644 for development		{{{SYNC-SMARTFRAMEWORK-DEFAULT-FILES-CHMOD}}}
//---------------------------------------- TPL DEBUGGING
const SMART_SOFTWARE_MKTPL_DEBUG_LEN =  				0;											// If set will use this TPL Debug Length (255..524280) ; If not set will use default: 512
//---------------------------------------- CUSTOM IP DETECTION FOR USING THE SMART FRAMEWORK BEHIND A PROXY OR A LOAD BALANCER :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING :: example, using apache/php/smart-framework behind haproxy or varnish or another proxy or load balancer !!! Be very careful when setting cutsom IP detection to avoid detecting wrong client IP address that may impact the overall security !!!
const SMART_FRAMEWORK_IPDETECT_CUSTOM =  				false; 										// only change this and also the SMART_FRAMEWORK_IPDETECT_CLIENT and SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT when using a server proxy like haproxy or varnish to serve the apache/php application or website ; in this case the REMOTE_ADDR will always be the haproxy's / varnish's IP address and the real client IP must come from another custom trusted header that haproxy / varnish will be rewriting and safe forwarding to apache by setting in the haproxy config this: `option forwardfor` / or varnish config these: `remove req.http.X-Forwarded-For;`, 'set req.http.X-Forwarded-For = req.http.rlnclientipaddr;'
//const SMART_FRAMEWORK_IPDETECT_CLIENT =  				'HTTP_X_FORWARDED_FOR'; 					// when using a load balancer or reverse proxy (ex: haproxy or varnish) here must be set the trusted header key that returns the real client IP (ex: use a trusted header like 'HTTP_X_FORWARDED_FOR' or 'HTTP_X_REAL_IP' that is considered the trusted real visitor's IP header instead of the default 'REMOTE_ADDR' which in this case becomes the proxy's IP address instead of clien's IP address) ; if no proxy server is set this must NOT be defined at all as the default TRUSTED key is always 'REMOTE_ADDR'
//const SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT =  		'<HTTP_X_REAL_IP>,<HTTP_X_FORWARDED_FOR>'; 	// when using a server proxy behind apache, php and smart framework (ex: haproxy or varnish) here must be set the header keys (or empty string if n/a) that may return the real client proxy IP ; these may or may not be available when using a server proxy, but be careful to avoid colisions with the trusted IP defined above
//---------------------------------------- ROBOTS IDENTIFY :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING :: Sample (spaces between <> counts): '<bot signature 1>,<bot signature 2 >,< another-bot >'
const SMART_FRAMEWORK_IDENT_ROBOTS =  					'<robot>,<apache>,<httperf>,<benchmark>,<scanner>,<googlebot>,<google adsbot>,<google toolbar>,<google web preview>,<google feed fetcher>,<yahoo! slurp>,<webcrawler>,<domaincrawler>,<catchbot>,<webalta crawler>,<superbot>,<msnbot>,<ms url control>,<winhttp>,<roku dvp>,<linkwalker>,<aihitbot>,<ia_archiver>,<sanszbot>,<linguee bot>,<swish-e>,<tarantula>,<fast-webcrawler>,<jeeves>,<teoma>,<baiduspider>,<bing bot>,<yandex>,<exabot>,<everyfeed spider>,<gregarius>,<facebook scraper>,<email wolf>,<gaisbot>,<gulperbot>,<grub-client>,<peach >,<htmlparser>,<w3c css validator>,<w3c (x)html validator>,<w3c p3p validator>,<download demon>,<offline explorer>,<webcopier>,<web downloader>,<webzip>,<htmldoc>,<wget >,<curl/>,<php >,<libwww-perl>,<python-urllib>,<java >'; // robots identification by user agent portions of signature
//--------------------------------------- UPLOADS SECURITY :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS = 		'<z-netarch>'; 								// *OPTIONAL* The List of Allowed file extensions for Uploads ; if set and empty, will dissalow any upload by default ; if set and non-empty will only allow files with these extensions to be uploaded (if this is set the SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS will not count at all)
const SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS =  		''; 										// The List of DENIED file extensions for Uploads ; files with these extensions will not be allowed to be uploaded by default
//---------------------------------------
//============================================================

//===== WARNING: =====
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//====================

//============================================================
//----------------------------------------
const SMART_FRAMEWORK_SECURITY_FILTER_INPUT =  		'/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/';	// !!! DO NOT MODIFY THIS UNLESS YOU KNOW WHAT YOU ARE DOING !!! This is a Safe Unicode Filter Input (GET/POST/COOKIE) Variables (Strings) as it will remove all lower dangerous characters: x00 - x1F and x7F except: \t = x09 \n = x0A \r = x0D
const SMART_FRAMEWORK_CHARSET =  					'UTF-8';								// This must be `UTF-8` 	:: Default Character Set for PHP
const SMART_FRAMEWORK_SQL_CHARSET =  				'UTF8';									// This must be `UTF8` 		:: Default Character Set for DB SQL Servers
//----------------------------------------
//============================================================

//---------------------------------------- Set TimeZone in Global Mode per Application
if(defined('SMART_FRAMEWORK_TIMEZONE')) {
	if(!date_default_timezone_set((string)SMART_FRAMEWORK_TIMEZONE)) {
		@http_response_code(500);
		die('Smart.Framework INI // A required INIT constant has a wrong value: SMART_FRAMEWORK_TIMEZONE');
	} //end if
} //end if
//----------------------------------------

//---------------------------------------- PHP RUNTIME CHECKS
// NOTE: this must be set before any other settings !!!
// DESCRIPTION: check safe mode PHP (off) :: this cannot be supported !!!
// WARNING : These will NOT be changed !!! The entire work is based on these settings
// check safe mode PHP (must be 0=off) ; no more necessary, it was removed since PHP 5.4
//-- set default mime type to HTML
ini_set('default_mimetype', 'text/html'); // this is required as default, this is a framework for web
//-- REQUEST VARIABLES CHECK
if(((string)strtoupper((string)ini_get('request_order')) != 'GP') AND (stripos((string)ini_get('variables_order'), 'GP') === false)) { // If request_order is not set, variables_order is used for $_REQUEST contents
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI `request_order` MUST BE SET TO: `GP` OR IF THIS IS NOT SET the `variables_order` MUST CONTAIN the `G` and `P` IN THIS ORDER: `GP`'); // must not contain 'C' for cookies or 'S' for server, due to security concerns ; GET (G) must be prior to POST (P)
} //end if
if(!ini_get('enable_post_data_reading')) {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI enable_post_data_reading must be ENABLED'); // Disabling this option in php.ini causes $_POST and $_FILES not to be populated
} //end if
//-- server output compression (optional)
//if(function_exists('apache_setenv')) {
//	@apache_setenv('no-gzip', 1); // turn off GZip Compression in Apache
//} //end if
ini_set('zlib.output_compression', '0'); // disable ZLib PHP Internal Output Compression as it will break sensitive control over headings and timeouts
if((string)ini_get('zlib.output_compression') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI ZLib Output Compression must be disabled !');
} //end if
//-- output handlers
if((string)ini_get('zlib.output_handler') != '') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Zlib Output Handler must be unset !');
} //end if
if((string)ini_get('output_handler') != '') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Output Handler must be unset !');
} //end if
//-- charset
if((string)ini_get('zend.multibyte') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // PHP.INI Zend-MultiByte must be disabled ! Unicode support is managed via MBString into Smart.Framework ...');
} //end if
if((string)SMART_FRAMEWORK_CHARSET != 'UTF-8') {
	@http_response_code(500);
	die('Smart.Framework INI // The SMART_FRAMEWORK_CHARSET must be set to `UTF-8` !');
} //end if
if((string)SMART_FRAMEWORK_SQL_CHARSET != 'UTF8') {
	@http_response_code(500);
	die('Smart.Framework INI // The SMART_FRAMEWORK_SQL_CHARSET must be set to `UTF8` !');
} //end if
ini_set('default_charset', (string)SMART_FRAMEWORK_CHARSET); // set the default charset
if(!function_exists('mb_internal_encoding')) { // *** MBString is required ***
	@http_response_code(500);
	die('Smart.Framework INI // The MBString PHP Module is required for Smart.Framework / Unicode support (SMART-INIT) !');
} //end if
if(mb_internal_encoding((string)SMART_FRAMEWORK_CHARSET) !== true) { // this setting is required for UTF-8 mode
	@http_response_code(500);
	die('Smart.Framework INI // Failed to set MBString Internal Encoding to: '.SMART_FRAMEWORK_CHARSET);
} //end if
if(mb_substitute_character(63) !== true) {
	@http_response_code(500);
	die('Smart.Framework INI // Failed to set the MBString Substitute Character to standard: 63(?) ...');
} //end if
//-- check input limits
if((int)ini_get('max_input_vars') < 1000) { // it should be at least 1000 ; cannot be set to zero as it will dissalow any input vars ; this limits the Request Input Vars (GET / POST / COOKIE) includding their nested levels ; recommended is 2500 ; minimum accepted is 1000 ; after changing this value you have to change the max_input_vars with a value like this or even higher in PHP.INI
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI MaxInputVars must be set to a higher value than 1000 ...');
} //end if
if((int)ini_get('max_input_nesting_level') < 5) { // it should be at least 5 ; the max_input_nesting_level cannot be set to zero as it will dissalow any arrays
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI MaxInputNestingLevel must be set to a higher value than 5 ...');
} //end if
if((int)ini_get('max_input_time') < 60) { // it should be at least 60 ; the max_input_time cannot be set to zero as it will have no time for parsing input vars
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI MaxInputTime must be set to a higher value than 60 ...');
} //end if
//-- misc settings and limits
if(defined('SMART_FRAMEWORK_MEMORY_LIMIT')) {
	ini_set('memory_limit', (string)SMART_FRAMEWORK_MEMORY_LIMIT); // set the memory limit
} //end if
if(defined('SMART_FRAMEWORK_EXECUTION_TIMEOUT')) {
	ini_set('max_execution_time', (int)SMART_FRAMEWORK_EXECUTION_TIMEOUT); // execution timeout this value must be close to httpd.conf's timeout
} //end if
if(defined('SMART_FRAMEWORK_NETSOCKET_TIMEOUT')) {
	ini_set('default_socket_timeout', (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT); // socket timeout (2 min.)
} //end if
ini_set('ignore_user_abort', '1');											// ignore user aborts (safe for closing sessions, pg-connections and data integrity)
ini_set('auto_detect_line_endings', '0');									// auto detect line endings
ini_set('y2k_compliance', '0');												// it is recommended to use this as disabled since POSIX systems keep time based on UNIX epoch
ini_set('precision', '14');													// decimal number precision
ini_set('pcre.backtrack_limit', '8000000');									// PCRE BackTrack Limit 8M (min req. is 1M = 1000000) ; PCRE String Limits
ini_set('pcre.recursion_limit', '800000');									// PCRE Recursion Limit 800K (min req. is 100K = 100000) ; PCRE Expression Limits
//-- pcre JIT (disable this if you have very complex PCRE expressions combined with very complex PHP functions otherwise the PCRE-JIT Memory may overflow)
//ini_set('pcre.jit', '0');													// PCRE JIT can be disabled if explicit needed so
//if((int)ini_get('pcre.jit') > 0) {
//	@http_response_code(500);
//	die('Smart.Framework INI // The PHP.INI PCRE JIT could not be DISABLED !');
//} //end if
//---------------------------------------- session checks
if((string)ini_get('session.auto_start') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Session AutoSTART must be DISABLED !');
} //end if
if((string)ini_get('session.use_trans_sid') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Session TransSID must be DISABLED !');
} //end if
//---------------------------------------- session settings
// N/A
//---------------------------------------- other checks:
// magic quotes runtime must be disabled :: no more necessary since PHP 5.4, it was removed
// suhoshin must be not enabled :: no more necessary to check as since PHP 5.4 was no more includded by default on popular distros :: suhoshin patch breaks a lot of functionality in latest PHP version, thus is not supported ... use it on your own risk !! ; example: it may break this app when working with large data packets or even corrupt session data or unicode strings
//----------------------------------------

//---------------------------------------- security: avoid load this multiple times
if(defined('SMART_FRAMEWORK_INITS')) {
	@http_response_code(500);
	die('Smart.Framework INI // Inits already loaded ...');
} //end if
const SMART_FRAMEWORK_INITS = 'SET'; // avoid reload inits again (if accidentaly you do)
//----------------------------------------

//======= [standalone app]

//== v.20210812
//--
ini_set('display_errors', '1'); 											// temporary enable this to display bootstrap errors if any ; will be managed later by Smart Error Handler
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED); 			// on bootstrap show real-time errors (sync with Smart Error Handler)
//--
const SMART_FRAMEWORK_LIB_PATH =  			false; 							// smart framework lib path
const SMART_FRAMEWORK_RUNTIME_MODE =  		'web.task'; 					// runtime mode: 'web.task'
const SMART_STANDALONE_APP =  				true; 							// must be set to false, except standalone scripts !
const SMART_FRAMEWORK_ADMIN_AREA =  		true; 							// run app in private/admin/task mode
//--
define('SMART_FRAMEWORK_RUNTIME_READY', 	microtime(true)); 				// semaphore, runtime can execute scripts
//--
//==

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//=======

// end of php code
