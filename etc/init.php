<?php
// [@[#[!NO-STRIP!]#]@]
// [Smart.Framework / INIT] v.20210812
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7
// {{{SYNC-SMART-APP-INI-SETTINGS}}}

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// ===== IMPORTANT =====
//	* NO VARIABLES SHOULD BE DEFINED IN THIS FILE ; ONLY CONSTANTS SHOULD BE DEFINED HERE TO AVOID LATER CHANGES !!!
// 	* IF any constant from below must be used in conditional contexts, use define('CONSTANT_NAME', 'value'); // instead of use: const CONSTANT_NAME = 'value'; // compile time constants are significant faster than define constants, but cannot be used in conditional context and cannot have value from cast or methods !
//	* IF .htaccess PHP settings will be used, be sure to sync them with this file too for ini sets at the bottom of this file !
// ==================

//============================================================
//--------------------------------------- DEBUG AND PROFILING :: these must use define constants not using compile time const, to be able to use in conditional contexts
//define('SMART_FRAMEWORK_PROFILING_HTML_PERF', 		true); 										// Uncomment this to enable the HTML Performance Profiler for Browser (it can be used also in production environments for HTML Metrics and Profiling purposes)
//define('SMART_FRAMEWORK_DEBUG_MODE', 					true);										// Uncomment this to enable Debugging and the Web Profiler Toolbar ; This works on main requests only ; XHR Requests will be shown in the main request parent area (do not use in production environments but only for internal Debugging / Profiling purposes)
//---------------------------------------
//============================================================ ALWAYS CHANGE !
//--------------------------------------- TIMEZONE
const SMART_FRAMEWORK_TIMEZONE =  						'UTC'; 										// The timezone for PHP (Example: Europe/London) ; default is: UTC
//--------------------------------------- TASK RUNTIME ALLOWED IP LIST
const SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS = 		''; 										// APP Task service area allowed IPs ; ex: '<127.0.0.1>,<::1>' ; can not be empty ; Tasks area is like Admin area but with some unrestricted features ; it is intended to be used mostly for development tasks ... ; the task.php can be excluded from a release or simply set below: const SMART_SOFTWARE_TASK_DISABLED = true;
//--------------------------------------- SECURITY
const SMART_FRAMEWORK_SECURITY_KEY =  					'private-key#0987654321'; 					// *** YOU HAVE TO CHANGE IT *** ; This is the Security Key that will be used to generate secure hashes
//const SMART_FRAMEWORK_SECURITY_OPENSSLBFCRYPTO =  	true; 										// *Optional: if defined and set to TRUE will use the OpenSSL cipher openssl/blowfish/CBC (faster) instead of internal one blowfish.cbc (more compatible across platforms)
//const SMART_FRAMEWORK_SECURITY_CRYPTO = 	 			'openssl/aes256/CBC'; 						// *Optional: the crypto algo for general purpose encryption to be used ; default is hash/sha256 ; other modes: hash/sha512, hash/sha1, hash/md5, openssl/{algo}/{mode} where mode can be: CBC, CFB, OFB ; algo can be: blowfish, aes256, camellia256
//---------------------------------------
//============================================================ REVIEW AND CHANGE IF PRODUCTION MODE
//--------------------------------------- APP NAMESPACE
const SMART_SOFTWARE_NAMESPACE =  						'smart-framework.default';					// APP Namespace ID :: [ _ a-z 0-9 - . ], length 10..25 :: This should be used as a unique ID identifier for the application (aka application unique ID)
//--------------------------------------- RUNTIME ENVIRONMENT :: CHANGE IT with 'prod' for a production environment !
const SMART_FRAMEWORK_ENV =  							'dev'; 										// APP Environment: can be set to 'dev' or 'prod' ; id set to 'prod' (production environment) will not log E_USER_NOTICE and E_DEPRECATED and will not display in-page error details but just log them ; for development mode set this to 'dev'
//--------------------------------------- COOKIES
const SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE = 	 	'Lax'; 										// The UniqueID Cookie SameSite Policy ; if not defined will not use Any Policy (old, compatibility fallback behaviour) ; If set must be one of these values: Lax / Strict or None ; set to None works only with a https secured connection because new browsers require this !
const SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME =  		0;											// The UniqueID Cookie LifeTime in seconds ; set to 0 for expire on browser close
const SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN =  		'';											// The UniqueID Cookie domain: set it (empty) `` for the current subdomain as `sdom.domain.tld` ; set it as `*` or explicit `domain.tld` for all sub-domains of domain.tld ; default is `` (empty) if not defined at all ; this is for advanced use of cookies management in sensitive production environments where you need per sub-domain encapsulated cookies
//---------------------------------------
//============================================================
//--------------------------------------- PERSISTENT-CACHE HANDLER
const SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER =  		false; 										// Persistent Cache Handler ; If set to FALSE will use no handler ; If set otherwise can use Built-In: 'redis' or 'mongodb' or 'dba' or 'sqlite' ; or a Custom handler can be set as (example): 'modules/app/persistent-cache-custom-adapter.php'
//--------------------------------------- EXECUTION / RUNTIME LIMITS :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_MEMORY_LIMIT =  					'256M';										// Memory Limit Per Script (via PHP.INI) ; sync this with the value in .htaccess if defined ; a good value for production is 256M or 384M ; for development, with Debugging turned ON be sure to set a big value like 512M or 1024M !!
const SMART_FRAMEWORK_EXECUTION_TIMEOUT =  				610;										// Script Max Execution Time (Sync with the web server Timeout and PHP.INI)
const SMART_FRAMEWORK_NETSOCKET_TIMEOUT =  				120; 										// Network Socket (Stream) TimeOut in Seconds
const SMART_FRAMEWORK_NETSERVER_ID =  					1; 											// Load Balancing: Unique ID, integer+ (min=0 ; max=1295) ; this is used for the main purpose to be able to generate very unique UUIDS in a cluster of apps ; every server in the cluster running the same app must have a different ID
const SMART_FRAMEWORK_NETSERVER_MAXLOAD =  				false;										// Load Balancing and DDOS Protection against High Loads :: if set to FALSE will be ignored ; if set to a value > 0 if server load go over this value the server will enter in BUSY state (503 Too busy) ; by example a value of 90 means 90% load over 1 CPU core ; on multi cpus/cores value must be this value * number of cpus/cores ; a good and realistic setting is 100 * number of CPU/cores
//--------------------------------------- SSL CRYPTO OVERALL SETTINGS :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
const SMART_FRAMEWORK_SSL_MODE =  						'tls';										// SSL/TLS Mode: tls | sslv3
const SMART_FRAMEWORK_SSL_CIPHERS = 					'HIGH';										// SSL/TLS Context Ciphers: ciphers ; default: 'HIGH' ; generally allow only high ciphers
const SMART_FRAMEWORK_SSL_VFY_HOST = 					true;										// SSL/TLS Context Verify Host: verify_host ; default: true
const SMART_FRAMEWORK_SSL_VFY_PEER = 					false;										// SSL/TLS Context Verify Peer: verify_peer ; default: false ; this may fail with some CAs
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
const SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS = 		'<svg>,<png>,<gif>,<jpg>,<jpeg>,<webp>'.','.'<webm>,<ogv>,<ogg>,<mp4>,<mov>'.','.'<txt>,<md>,<pdf>,<odt>,<ods>,<odp>,<csv>,<doc>,<rtf>,<xls>,<ppt>'.','.'<json>,<yaml>,<xml>,<eml>,<ics>,<vcf>'.','.'<7z>,<zip>,<rar>,<tar>,<tgz>,<tbz>,<gz>,<bz2>,<xz>'.','.'<ps>,<eps>,<tif>,<tiff>,<wmf>,<bmp>,<swf>'; // *OPTIONAL* The List of Allowed file extensions for Uploads ; if set and empty, will dissalow any upload by default ; if set and non-empty will only allow files with these extensions to be uploaded (if this is set the SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS will not count at all)
const SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS =  		'<htm>,<html>,<js>,<sass>,<scss>,<css>,<shtml>,<phtml>,<php>,<sql>,<inc>,<tpl>,<mtpl>,<twig>,<dust>,<t3fluid>,<pl>,<py>,<pyc>,<pyo>,<rb>,<go>,<asp>,<jsp>,<sh>,<bash>,<bat>,<cmd>,<cgi>,<fcgi>,<fastcgi>,<scgi>,<wsgi>,<exe>,<msi>,<dll>,<dylib>,<bin>,<so>'; // The List of DENIED file extensions for Uploads ; files with these extensions will not be allowed to be uploaded by default
//---------------------------------------
//============================================================ # BELOW ARE APP SPECIFIC SETTINGS
//--------------------------------------- DOWNLOADS SECURITY :: CHANGE IT ONLY YOU KNOW WHAT YOU ARE DOING
//const SMART_FRAMEWORK_DOWNLOAD_SKIP_LOG = 			true;										// If defined will disable logging for Downloads
const SMART_FRAMEWORK_DOWNLOAD_FOLDERS =  				'<wpub>';									// Allow downloads ONLY from these folders: <folder1>,<folder2> (relative to the app root)
const SMART_FRAMEWORK_DOWNLOAD_EXPIRE = 				1;											// Download expiration time in hours (between 1 and 24 hours)
//--------------------------------------- URLS
const SMART_FRAMEWORK_SEMANTIC_URL_DISABLE =  			false; 										// if set to TRUE this will DISABLE the semantic URLs ; this must be set to TRUE for standalone scripts ; Example: http(s)://domain.ext/?/page/sample.action instead of http(s)://domain.ext/?page=sample.action
const SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT =  		true;										// Semantic URL Rewriter Skip Script for Shortening the semantic URLs ; just for index area ; if set to TRUE will skip the 'index.php' part of building semantic URLs
const SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE = 		false;										// Semantic URL Rewriter Skip Default Module for Shortening the semantic URLs ; just for index area ; if set to TRUE will skip the default module defined in configs as app.index-default-module
const SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE = 	 	'';											// Default is `` (do not use rewrite) ; URL Rewrite Mode (requires Apache Rewrite): `standard` | `semantic` :: Apache like rewrite rules (must be enabled in .htaccess) and the SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT must be set to TRUE ; semantic URLS must be not disabled ; this works just for index.php (declared as directory index under apache)
//---------------------------------------- SPECIAL FEATURES
const SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES =  		'<php>,<html>,<shtml>,<phtml>,<stml>,<css>,<js>,<json>,<xml>,<rss>,<txt>,<md>,<csv>,<sql>,<svg>,<png>,<gif>,<jpg>,<webp>,<webm>,<pdf>,<zip>,<tar>,<bz2>,<gz>,<tgz>,<xz>,<7z>,<netarch>,<z-netarch>'; // OPTIONAL: The list with reserved controller names to avoid confusion between controller names and URL page reserved extensions that can lead to wrong browser behaviour if serving a page with such extension without an explicit mime type ; a controller name must avoid having any name from this list
const SMART_SOFTWARE_URL_ALLOW_PATHINFO = 				1;											// Default is set to 1 ; Set to: 0 = no area ; 1 = only admin area ; 2 = both: index area & admin area ; 3 = only index area :: Sample PathInfo (index.php|task.php|admin.php/path/to/something/~)
const SMART_SOFTWARE_FRONTEND_DISABLED = 				false;										// To Disable Frontend service (index.php) set this to TRUE
const SMART_SOFTWARE_BACKEND_DISABLED = 				false;										// To Disable Backend service (admin.php) set this to TRUE
const SMART_SOFTWARE_TASK_DISABLED = 					false; 										// To Disable Task service (task.php) set this to TRUE
const SMART_SOFTWARE_DISABLE_STATUS_POWERED = 			false;										// If set to FALSE will disable the status powered info accesible via ?/smartframeworkservice/status
const SMART_SOFTWARE_SQLDB_FATAL_ERR = 					true;										// If set to false will throw \EXCEPTION which can be catched instead of raise a fatal error on all SQL DB adapters such as PostgreSQL / SQLite / MySQL (NOSQL adapters, ex: MongoDB or Redis can be set per instance and are not affected by this setting) ; WARNING: disabling SQL Fatal Errors is not safe, especially when using SQL transactions ... ; DO NOT modify this parameter unless you know what you are doing !!!
//--------------------------------------- SESSION AND CLIENT UUID ; SESSION REQUIRES THE: SMART_FRAMEWORK_UUID_COOKIE_NAME for UUID ENTROPY ; IF the SMART_FRAMEWORK_UUID_COOKIE_SKIP is set to TRUE, Session will not start at all (security check) !!
const SMART_FRAMEWORK_UUID_COOKIE_NAME = 	 			'SmartFramework__UUID';						// The UniqueID Cookie Name (it is recommended to be customized) ; (If the SMART_FRAMEWORK_UUID_COOKIE_SKIP is defined and set to true will not set the SMART_FRAMEWORK_UUID_COOKIE_NAME, which will drop some functionalities that depend on it ...)
const SMART_FRAMEWORK_SESSION_NAME =  					'SmartFramework__SESS'; 					// Session Name ; *** YOU HAVE TO CHANGE IT *** this must be static and must contain only Letters and _
const SMART_FRAMEWORK_SESSION_ROBOTS =  				false;										// Uncomment this to enable session also for robots (robot identified user agents)
const SMART_FRAMEWORK_SESSION_LIFETIME =  				0;											// Session Lifetime in seconds (0 by default) ; set to 0 for expire on browser close ; must be not higher than SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME
const SMART_FRAMEWORK_SESSION_DOMAIN =  				'';											// Session (cookie) Domain: set it (empty) `` for the current subdomain as `sdom.domain.tld` ; set it as `*` or explicit `domain.tld` for all sub-domains of domain.tld ; default is `` (empty) if not defined at all ; this is for advanced use of the PHP session cookie management in sensitive production environments where you need per sub-domain encapsulated cookies
const SMART_FRAMEWORK_SESSION_HANDLER =  				'files';									// Session Handler: 'files' (default / file storage: lightweight but in high concurencies may have locking issues) ; this can be set as 'redis' (DB / in-memory, very fast) or as 'mongodb' (DB / big-data) or as 'dba' or 'sqlite' ; or use your own custom adapter for the session in Smart.Framework you have to build it by extending the SmartAbstractCustomSession abstract class and define here as (example): 'modules/app/session-custom-adapter.php'
//---------------------------------------- CHARSET AND REGIONAL SETTINGS [ NEVER CHANGE THESE MUST BE UNICODE UTF-8 ; CHANGING IT YOU CAN BREAK THE UNICODE SUPPORT ]
//const SMART_FRAMEWORK_DEFAULT_LANG =  				'en';										// The default language for translations (as language ID) :: Default is 'en' :: {{{SYNC-APP-DEFAULT-LANG}}} :: must be a valid language ID defined in config.php as regional.language-id
//const SMART_FRAMEWORK_URL_PARAM_LANGUAGE =  			'lang';										// *Optional* (used only with multi languages) Language URL Param (if empty string, will not accept any language inputs by URL or Cookie) ; Default = 'lang' ; if not empty may contain only characters: [a-z] ; if defined and non empty must be between 1 and 10 characters long
//const SMART_FRAMEWORK_TRANSLATIONS_BUILTIN_CUSTOM = 	true; 										// This is used only with the built-in YAML Translations adapter ; The YAML @core translation files for EN language are located in `lib/app/translations/` which are hard to manage, if upgrading the lib folder will be rewritten ; the YAML @core translations for other languages are always located in `modules/app/translations/` ; to avoid modify the `lib/app/translations/` by defining this constant and set it to TRUE will look for the EN @core YAML translations in `modules/app/translations/` instead of `lib/app/translations/` which make them easier to maintain if need to modify them ; otherwise if not need to modify them make non-sense ; if this is enabled just make a copy of the YAML files from `lib/app/translations/` to `modules/app/translations/` and enable this constant by set it to TRUE
//const SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM =  	'modules/app/custom-transl-adapter.php'; 	// Custom Translations Adapter Handler ; if using this will not use the default YAML files based translations which is built-in
//---------------------------------------- SPECIAL .htaccess FILES CONTENT :: These are set for Apache web server. If you are using another web server you may adjust them.
//-- .htaccess DENY EXECUTION OF SCRIPTS
const SMART_FRAMEWORK_HTACCESS_NOEXECUTION = '
# Prevent Scripts or Executables
Options -ExecCGI
<FilesMatch "\.(html|htm|js|sass|scss|css|shtml|phtml|php|php*|sql|inc|tpl|mtpl|twig|latte|t3fluid|pl|py|pyc|pyo|rb|go|asp|jsp|sh|bash|bat|cmd|cgi|fcgi|fastcgi|scgi|wsgi|exe|dll|dylib|bin|so)$">
	SetHandler none
	ForceType text/plain
</FilesMatch>
'; // php_flag engine off
//-- .htaccess ACCESS FORBIDDEN
const SMART_FRAMEWORK_HTACCESS_FORBIDDEN = '
# Deny Access: Apache 2.2
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
# Deny Access: Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
'; // {{{SYNC-SMART-APP-INI-HTACCESS}}}
//-- .htaccess IGNORE INDEXING
const SMART_FRAMEWORK_HTACCESS_NOINDEXING = '
# Disable Indexing
<IfModule mod_autoindex.c>
	IndexIgnore *
</IfModule>
Options -Indexes
'; // {{{SYNC-SMART-APP-INI-HTACCESS}}}
//--

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

//============================================================
//============================================================ INIT PHP RUNTIME
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
ini_set('session.save_handler', 'files');									// store session in 'files' (default) ; file storage as default ; can be set as `memcached` with session.save_path = 'localhost:11211' or other direct handler that PHP supports ; since PHP 7.3 there is no more supports set 'user' mode on session.save_handler ; PHP will just need to detect a custom handler user passes to handle the session
ini_set('session.gc_maxlifetime', 3600);									// GC Max Life Time in seconds after each sessions that were modified longer than this will be cleaned ; min is 1440 ; max is 65535 seconds or 2592000 seconds (30 days) depend on platform
ini_set('session.gc_probability', '1');										// GC Probability, Must be > 0 to use GC
ini_set('session.gc_divisor', '100');										// GC Divisor ; The probability is calculated by using gc_probability/gc_divisor, e.g. 1/100 means there is a 1% chance that the GC process starts on each request.
ini_set('session.use_cookies', '1');										// Session use cookies
ini_set('session.use_only_cookies', '1');									// It is safe to use only cookies for sessions, not send it by URL
ini_set('session.hash_bits_per_character', '5'); 							// session mode using characters as: (0-9, a-v) :: (only available since PHP 5.3)
ini_set('session.hash_function', 'sha512');									// set session hash to sha512 :: (only available since PHP 5.3)
ini_set('session.serialize_handler', 'php');								// use php (default) ; wddx can be buggy
//---------------------------------------- other checks:
// magic quotes runtime must be disabled :: no more necessary since PHP 5.4, it was removed
// suhoshin must be not enabled :: no more necessary to check as since PHP 5.4 was no more includded by default on popular distros :: suhoshin patch breaks a lot of functionality in latest PHP version, thus is not supported ... use it on your own risk !! ; example: it may break this app when working with large data packets or even corrupt session data or unicode strings
//----------------------------------------

//============================================================
//============================================================ FREEZE
//============================================================

//---------------------------------------- security: avoid load this multiple times
if(defined('SMART_FRAMEWORK_INITS')) {
	@http_response_code(500);
	die('Smart.Framework INI // Inits already loaded ...');
} //end if
const SMART_FRAMEWORK_INITS = 'SET'; // avoid reload inits again (if accidentaly you do)
//----------------------------------------

//============================================================
//============================================================ #END
//============================================================

// end of php code
