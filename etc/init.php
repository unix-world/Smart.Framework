<?php
// [@[#[!NO-STRIP!]#]@]
// [Smart.Framework / INIT] v.20210314
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// ===== IMPORTANT =====
//	* NO VARIABLES SHOULD BE DEFINED IN THIS FILE BECAUSE IT IS LOADED BEFORE THE GET/POST VARS ARE REGISTERED AND CAN CAUSE SECURITY ISSUES !!!
//	* ONLY CONSTANTS SHOULD BE DEFINED HERE ; IF .htaccess PHP settings will be used, be sure to sync them with this file too
// ==================

//define('SMART_FRAMEWORK_PROFILING_HTML_PERF', 'yes'); 									// Uncomment this to enable the HTML Performance Profiler (it can be used also in production environments for HTML Metrics and Profiling purposes)
//define('SMART_FRAMEWORK_DEBUG_MODE', 'yes');												// Uncomment this to enable Debugging and the Web Profiler Toolbar (do not use in production environments but only for internal Debugging / Profiling purposes)

//--------------------------------------- APP NAMESPACE
define('SMART_SOFTWARE_NAMESPACE', 			'smartframework.default');						// APP Namespace ID :: [a-z.], length 10..25 :: This should be used as a unique ID identifier for the application (aka application unique ID)
//--------------------------------------- ERRORS MANAGEMENT
define('SMART_ERROR_HANDLER', 				'dev'); 										// Error Handler mode: 'log' | 'dev' :: for production is recommended to use 'log' as it will show a blank page with a HTTP 500 Internal Server Error message ; for development or debugging use 'dev' but this will display an error with a HTTP 200 OK instead of HTTP 500
//--------------------------------------- TIMEZONE
define('SMART_FRAMEWORK_TIMEZONE', 			'UTC'); 										// The timezone for PHP (Example: Europe/London) ; default is: UTC
define('SMART_FRAMEWORK_DEFAULT_LANG', 		'en');											// The default language for translations (as language ID) ; must be a valid language ID defined in config.php as regional.language-id
//--------------------------------------- SECURITY
define('SMART_FRAMEWORK_SECURITY_FILTER_INPUT', '/[\x00-\x08\x0B-\x0C\x0E-\x1F]/');			// !!! DO NOT MODIFY THIS UNLESS YOU KNOW WHAT YOU ARE DOING !!! This is a Safe Unicode Filter Input (GET/POST/COOKIE) Variables (Strings) as it will remove all lower dangerous characters: x00 - x1F except: \t = x09 \n = 0A \r = 0D
define('SMART_FRAMEWORK_SECURITY_KEY', 		'private-key#0987654321'); 						// *** YOU HAVE TO CHANGE IT *** ; This is the Security Key that will be used to generate secure hashes
//define('SMART_FRAMEWORK_SECURITY_OPENSSLBFCRYPTO', true); 								// *Optional: if defined and set to TRUE will use the OpenSSL cipher openssl/blowfish/CBC (faster) instead of internal one blowfish.cbc (more compatible across platforms)
//define('SMART_FRAMEWORK_SECURITY_CRYPTO', 'openssl/aes256/CBC'); 							// *Optional: the crypto algo for general purpose encryption to be used ; default is hash/sha256 ; other modes: hash/sha1, hash/sha384, hash/sha512, openssl/{algo}/{mode} where mode can be: CBC, CFB, OFB ; algo can be: blowfish, aes256, camellia256
//--------------------------------------- URLS
define('SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT', 'index.php');							// Semantic URL Rewriter Skip Script (just for index.php) ; This can be set to: `index.php` or `` empty (admin.php have no support for this)
define('SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE', 'samples');								// Default Module for Shortening the semantic URLs or the URL rewriter from module.controller.html to just controller.html ; just for index.php (admin.php have no support for this)
//define('SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE', 'standard');							// URL Rewrite Mode (requires Apache Rewrite): `standard` | `semantic` :: Apache like rewrite rules (must be enabled in .htaccess) and the SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT must be set to `index.php` ; semantic URLS must be not disabled ; just for index.php (admin.php have no support for this)
//define('SMART_FRAMEWORK_SEMANTIC_URL_DISABLE', 	true); 									// *Optional: if defined, this will DISABLE the semantic URLs for index.php and admin.php ; Example: http(s)://domain.ext/?/page/sample.action instead of http(s)://domain.ext/?page=sample.action
//--------------------------------------- COOKIES
define('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME', 'SmartFramework__UID');						// The UniqueID Cookie Name (it is recommended to be customized) ; (If the SMART_FRAMEWORK_UNIQUE_ID_COOKIE_SKIP is defined and set to true will not set the SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME, which will drop some functionalities that depend on it ...)
define('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_LIFETIME', intval(60 * 60 * 24));					// The UniqueID Cookie LifeTime in seconds ; set to 0 for expire on browser close
//define('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_DOMAIN', '*');									// The UniqueID Cookie domain: set it (empty) `` for the current subdomain as `sdom.domain.tld` ; set it as `*` or explicit `domain.tld` for all sub-domains of domain.tld ; default is `` (empty) if not defined at all ; this is for advanced use of cookies management in sensitive production environments where you need per sub-domain encapsulated cookies
define('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_SAMESITE', 'Lax'); 								// The UniqueID Cookie SameSite Policy ; if not defined will not use any policy (old compatibility) ; If set must be one of these values: Lax / Strict or None ; set to None works only with a https secured connection because new browsers require this !
//--------------------------------------- SESSION
define('SMART_FRAMEWORK_SESSION_HANDLER', 	'files');										// Session Handler: 'files' (default / file storage: lightweight but in high concurencies may have locking issues) ; this can be set as 'redis' (DB / in-memory, very fast) or as 'mongodb' (DB / big-data) or as 'dba' or 'sqlite' ; or use your own custom adapter for the session in Smart.Framework you have to build it by extending the SmartAbstractCustomSession abstract class and define here as (example): 'modules/app/session-custom-adapter.php'
define('SMART_FRAMEWORK_SESSION_NAME', 		'SmartFramework__SESSION'); 					// Session Name ; *** YOU HAVE TO CHANGE IT *** this must be static and must contain only Letters and _
//define('SMART_FRAMEWORK_SESSION_LIFETIME', 	intval(60 * 60 * 24));						// Session Lifetime in seconds (0 by default) ; set to 0 for expire on browser close ; must be not higher than SMART_FRAMEWORK_UNIQUE_ID_COOKIE_LIFETIME
//define('SMART_FRAMEWORK_SESSION_DOMAIN', 	'*');											// Session (cookie) Domain: set it (empty) `` for the current subdomain as `sdom.domain.tld` ; set it as `*` or explicit `domain.tld` for all sub-domains of domain.tld ; default is `` (empty) if not defined at all ; this is for advanced use of the PHP session cookie management in sensitive production environments where you need per sub-domain encapsulated cookies
//define('SMART_FRAMEWORK_SESSION_ROBOTS', 	true);											// Uncomment this to enable session also for robots (robot identified user agents)
//--------------------------------------- HANDLERS
define('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER', false); 									// Persistent Cache Handler ; If set to FALSE will use no handler ; If set otherwise can use Built-In: 'redis' or 'mongodb' or 'dba' or 'sqlite' ; or a Custom handler can be set as (example): 'modules/app/persistent-cache-custom-adapter.php'
//--------------------------------------- EXECUTION / RUNTIME LIMITS
define('SMART_FRAMEWORK_MEMORY_LIMIT', 		'256M');										// Memory Limit Per Script (via PHP.INI) ; sync this with the value in .htaccess if defined ; a good value for production is 256M or 384M ; for development, with Debugging turned ON be sure to set a big value like 512M or 1024M !!
define('SMART_FRAMEWORK_EXECUTION_TIMEOUT', 610);											// Script Max Execution Time (Sync with the web server Timeout and PHP.INI)
define('SMART_FRAMEWORK_NETSOCKET_TIMEOUT', 120); 											// Network Socket (Stream) TimeOut in Seconds
define('SMART_FRAMEWORK_NETSERVER_ID', 		'1'); 											// Load Balancing: Unique ID, integer+ (min=0 ; max=1295) ; this is used for the main purpose to be able to generate very unique UUIDS in a cluster of apps ; every server in the cluster running the same app must have a different ID
//define('SMART_FRAMEWORK_NETSERVER_MAXLOAD', 0);											// Load Balancing and DDOS Protection against High Loads :: if set to 0 will be ignored ; if set to a value > 0 if server load go over this value the server will enter in BUSY state (503 Too busy) ; by example a value of 90 means 90% load over 1 CPU core ; on multi cpus/cores value must be 90 * number of cpus/cores
//--------------------------------------- SSL CRYPTO OVERALL TUNNINGS
define('SMART_FRAMEWORK_SSL_MODE', 				'tls');										// SSL/TLS Mode: tls | sslv3
define('SMART_FRAMEWORK_SSL_CA_FILE',			'');										// SSL/TLS Context CA Path: cafile ; default: '' ; if non-empty, must point to something like 'etc/cacert.pem' or another path to a certification authority pem
define('SMART_FRAMEWORK_SSL_CIPHERS',			'HIGH');									// SSL/TLS Context Ciphers: ciphers ; default: 'HIGH' ; generally allow only high ciphers
define('SMART_FRAMEWORK_SSL_VFY_HOST',			true);										// SSL/TLS Context Verify Host: verify_host ; default: true
define('SMART_FRAMEWORK_SSL_VFY_PEER',			false);										// SSL/TLS Context Verify Peer: verify_peer ; default: false ; this fails with some CAs
define('SMART_FRAMEWORK_SSL_VFY_PEER_NAME',		false);										// SSL/TLS Context Verify Peer Name: verify_peer_name ; default: false ; allow also wildcard names *
define('SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED',	true);										// SSL/TLS Context Allow Self-Signed Certificates: allow_self_signed ; default: true ; generally must allow self-signed certificates but verified above
define('SMART_FRAMEWORK_SSL_DISABLE_COMPRESS',	true);										// SSL/TLS Context Allow Self-Signed Certificates: disable_compression ; default: true ; help mitigate the CRIME attack vector
//---------------------------------------- FILE SYSTEM SETTINGS
define('SMART_FRAMEWORK_CHMOD_DIRS', 		0770);											// Folder Permissions: 0770 | 0700
define('SMART_FRAMEWORK_CHMOD_FILES', 		0660);											// File Permissions: 0660 | 0600
//--------------------------------------- UPLOADS / DOWNLOADS / QUOTA
//define('SMART_FRAMEWORK_STORAGE_DISK_QUOTA',	0);											// App Disk Quota in MB (0 for unlimited) ; This may be used by the uploads feature but it needs extra development to calculate total sizes of all uploads and compare with this value
define('SMART_FRAMEWORK_DOWNLOAD_FOLDERS', 		'<wpub>');									// Allow downloads ONLY from these folders: <folder1>,<folder2> (relative to the app root)
define('SMART_FRAMEWORK_DOWNLOAD_EXPIRE',		1);											// Download expiration time in hours (between 1 and 24 hours)
//define('SMART_FRAMEWORK_DOWNLOAD_SKIP_LOG',	true);										// If defined will disable logging for Downloads
define('SMART_FRAMEWORK_UPLOAD_PICTS', 			'<svg>,<png>,<gif>,<jpg>,<jpeg>,<webp>'); 	// Uploads images type ALLOWED extensions list
define('SMART_FRAMEWORK_UPLOAD_MOVIES', 		'<webm>,<ogv>,<mp4>,<mov>'); 				// Uploads video type ALLOWED extensions list
define('SMART_FRAMEWORK_UPLOAD_DOCS', 			'<txt>,<md>,<pdf>,<odt>,<ods>,<odp>,<csv>,<doc>,<rtf>,<xls>,<ppt>'); // Uploads document type ALLOWED extensions list
define('SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS',	SMART_FRAMEWORK_UPLOAD_PICTS.','.SMART_FRAMEWORK_UPLOAD_MOVIES.','.SMART_FRAMEWORK_UPLOAD_DOCS.',<json>,<yaml>,<xml>,<eml>,<ics>,<vcf>,<7z>,<zip>,<rar>,<tar>,<tgz>,<tbz>,<gz>,<bz2>,<xz>,<ps>,<eps>,<tif>,<tiff>,<wmf>,<bmp>,<swf>,<webp>'); // *OPTIONAL* The List of Allowed file extensions for Uploads ; if set and empty, will dissalow any upload by default ; if set and non-empty will only allow files with these extensions to be uploaded (if this is set the SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS will not count at all)
define('SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS', 	'<htm>,<html>,<js>,<sass>,<scss>,<css>,<shtml>,<phtml>,<php>,<sql>,<inc>,<tpl>,<mtpl>,<twig>,<latte>,<t3fluid>,<pl>,<py>,<pyc>,<pyo>,<rb>,<go>,<asp>,<jsp>,<sh>,<bash>,<bat>,<cmd>,<cgi>,<fcgi>,<fastcgi>,<scgi>,<wsgi>,<exe>,<msi>,<dll>,<dylib>,<bin>,<so>'); // The List of DENIED file extensions for Uploads ; files with these extensions will not be allowed to be uploaded by default
define('SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES', '<php>,<html>,<shtml>,<phtml>,<stml>,<css>,<js>,<json>,<xml>,<rss>,<txt>,<md>,<csv>,<sql>,<png>,<gif>,<jpg>,<webp>,<svg>,<webm>,<pdf>,<zip>,<tar>,<bz2>,<gz>,<tgz>,<xz>,<7z>,<netarch>'); // OPTIONAL: The list with reserved controller names to avoid confusion between controller names and URL page reserved extensions that can lead to wrong browser behaviour if serving a page with such extension without an explicit mime type ; a controller name must avoid having any name from this list
//---------------------------------------- SPECIAL .htaccess FILES CONTENT :: These are set for Apache web server. If you are using another web server you may adjust them.
//-- .htaccess DENY EXECUTION OF SCRIPTS
define('SMART_FRAMEWORK_HTACCESS_NOEXECUTION', '
# Prevent Scripts or Executables
Options -ExecCGI
<FilesMatch "\.(html|htm|js|sass|scss|css|shtml|phtml|php|php*|sql|inc|tpl|mtpl|twig|latte|t3fluid|pl|py|pyc|pyo|rb|go|asp|jsp|sh|bash|bat|cmd|cgi|fcgi|fastcgi|scgi|wsgi|exe|dll|dylib|bin|so)$">
	SetHandler none
	ForceType text/plain
</FilesMatch>
'); // php_flag engine off
//-- .htaccess ACCESS FORBIDDEN
define('SMART_FRAMEWORK_HTACCESS_FORBIDDEN', '
# Deny Access: Apache 2.2
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
# Deny Access: Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
');
//-- .htaccess IGNORE INDEXING
define('SMART_FRAMEWORK_HTACCESS_NOINDEXING', '
# Disable Indexing
<IfModule mod_autoindex.c>
	IndexIgnore *
</IfModule>
Options -Indexes
');
//--
//---------------------------------------- ROBOTS IDENTIFY :: DON'T CHANGE IF YOU DON'T KNOW WHAT YOU ARE DOING :: Sample (spaces between <> counts): '<bot signature 1>,<bot signature 2 >,< another-bot >'
define('SMART_FRAMEWORK_IDENT_ROBOTS', 					'<robot>,<apache>,<httperf>,<benchmark>,<scanner>,<googlebot>,<google adsbot>,<google toolbar>,<google web preview>,<google feed fetcher>,<yahoo! slurp>,<webcrawler>,<domaincrawler>,<catchbot>,<webalta crawler>,<superbot>,<msnbot>,<ms url control>,<winhttp>,<roku dvp>,<linkwalker>,<aihitbot>,<ia_archiver>,<sanszbot>,<linguee bot>,<swish-e>,<tarantula>,<fast-webcrawler>,<jeeves>,<teoma>,<baiduspider>,<bing bot>,<yandex>,<exabot>,<everyfeed spider>,<gregarius>,<facebook scraper>,<email wolf>,<gaisbot>,<gulperbot>,<grub-client>,<peach >,<htmlparser>,<w3c css validator>,<w3c (x)html validator>,<w3c p3p validator>,<download demon>,<offline explorer>,<webcopier>,<web downloader>,<webzip>,<htmldoc>,<wget >,<curl/>,<php >,<libwww-perl>,<python-urllib>,<java >'); // robots identification by user agent portions of signature
//--------------------------------------- SPECIAL URL PARAMS
//define('SMART_FRAMEWORK_URL_PARAM_LANGUAGE', 			'lang');													// *Optional* (used only with multi languages) Language URL Param (if empty string, will not accept any language inputs by URL or Cookie) ; Default = 'lang' ; if not empty may contain only characters: [a-z]
//---------------------------------------- SPECIAL FEATURES
define('SMART_SOFTWARE_FRONTEND_ENABLED',				true);														// To Disable Frontend (index.php) set this to false
define('SMART_SOFTWARE_BACKEND_ENABLED',				true);														// To Disable Backend (admin.php) set this to false
define('SMART_SOFTWARE_URL_ALLOW_PATHINFO',				2);															// Set to: 0 = none ; 1 = only admin ; 2 = both index & admin ; 3 = only index :: To Disable/Enable PathInfo (index|admin.php/path/info/)
//define('SMART_SOFTWARE_SQLDB_FATAL_ERR',				false);														// If defined / set to false will throw an \EXCEPTION that can be catched instead of raise a fatal error on all SQL DB adapters such as PostgreSQL / SQLite / MySQL (NOSQL adapters, ex: MongoDB can be set per instance) ; disabling SQL Fatal Errors is not safe to use with transactions for example ... (DO NOT modify or use this parameter unless you know what you are doing !!!)
//define('SMART_SOFTWARE_DISABLE_STATUS_POWERED',		true);														// If set to TRUE will enable the status powered info accesible via ?/smartframeworkservice/status
//define('SMART_SOFTWARE_MKTPL_PCACHETIME',				86400);														// If set to a positive integer (>=0) will cache the marker template files to (memory) persistent cache to avoid repetitive reads to the FileSystem (on some systems this can boost the speed ...)
//define('SMART_SOFTWARE_MKTPL_DEBUG_LEN', 				65535);														// If set will use this TPL Debug Length (255..524280) ; If not set will use default: 512
//---------------------------------------- CHARSET AND REGIONAL SETTINGS [ NEVER CHANGE THESE MUST BE UNICODE UTF-8 ; CHANGING IT YOU CAN BREAK THE UNICODE SUPPORT ]
define('SMART_FRAMEWORK_CHARSET', 						'UTF-8');													// This must be `UTF-8` 	:: Default Character Set for PHP
define('SMART_FRAMEWORK_DBSQL_CHARSET', 				'UTF8');													// This must be `UTF8` 		:: Default Character Set for DB SQL Servers
define('SMART_FRAMEWORK_LANGUAGES_CACHE_DIR', 			'modules/app/translations/');								// Languages Cache Dir		:: Default Languages Cache Dir, should be created by runtime ...
//define('SMART_FRAMEWORK__DEBUG__TEXT_TRANSLATIONS'	true); 														// If this is set will register the Translation usage for every: Language, Area, Subarea, Key ; (only will operate in DEV mode (SMART_ERROR_HANDLER == 'dev')
//---------------------------------------- CUSTOM IP DETECTION: example, using apache/php behind haproxy or varnish !!! Be very careful when setting cutsom IP detection to avoid detecting wrong client IP address that may impact the overall security !!!
//define('SMART_FRAMEWORK_IPDETECT_CUSTOM', 			true); 														// only define this and also the SMART_FRAMEWORK_IPDETECT_CLIENT and SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT when using a server proxy like haproxy or varnish to serve the apache/php application or website ; in this case the REMOTE_ADDR will always be the haproxy's / varnish's IP address and the real client IP must come from another custom trusted header that haproxy / varnish will be rewriting and safe forwarding to apache by setting in the haproxy config this: `option forwardfor` / or varnish config these: `remove req.http.X-Forwarded-For;`, 'set req.http.X-Forwarded-For = req.http.rlnclientipaddr;'
//define('SMART_FRAMEWORK_IPDETECT_CLIENT', 			'HTTP_X_FORWARDED_FOR'); 									// when using a server proxy (ex: haproxy or varnish) here must be set the header key that returns the real client IP (ex: use a trusted header like 'HTTP_X_FORWARDED_FOR' that comes from haproxy / varnish instead of default 'REMOTE_ADDR') ; if no proxy server is set this must NOT be defined at all as the default TRUSTED key is always 'REMOTE_ADDR'
//define('SMART_FRAMEWORK_IPDETECT_PROXY_CLIENT', 		'<HTTP_FORWARDED_FOR>,<HTTP_FORWARDED>'); 					// when using a server proxy (ex: haproxy or varnish) here must be set the header keys (or empty string if n/a) that may return the real client proxy IP (ex: '<HTTP_FORWARDED_FOR>,<HTTP_FORWARDED>' instead of defaults '<HTTP_CLIENT_IP>,<HTTP_X_FORWARDED_FOR>') ; these may or may not be available when using a server proxy, but be careful to avoid colisions with the trusted IP defined above
//----------------------------------------

//============================================================

//===== WARNING: =====
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//====================

//---------------------------------------- Set TimeZone in Global Mode per Application
date_default_timezone_set((string)SMART_FRAMEWORK_TIMEZONE);
//----------------------------------------

//---------------------------------------- PHP RUNTIME CHECKS
// NOTE: this must be set before any other settings !!!
// DESCRIPTION: check safe mode PHP (off) :: this cannot be supported !!!
// WARNING : These will NOT be changed !!! The entire work is based on these settings
//-- check safe mode PHP (must be 0=off)
// no more necessary, it was removed since PHP 5.4
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
ini_set('default_charset', (string)SMART_FRAMEWORK_CHARSET); // default charset UTF-8
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
ini_set('memory_limit', (string)SMART_FRAMEWORK_MEMORY_LIMIT);				// set the memory limit
ini_set('default_socket_timeout', (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT);	// socket timeout (2 min.)
ini_set('max_execution_time', (int)SMART_FRAMEWORK_EXECUTION_TIMEOUT);		// execution timeout this value must be close to httpd.conf's timeout
ini_set('ignore_user_abort', '1');											// ignore user aborts (safe for closing sessions, pg-connections and data integrity)
ini_set('auto_detect_line_endings', '0');									// auto detect line endings
ini_set('y2k_compliance', '0');												// it is recommended to use this as disabled since POSIX systems keep time based on UNIX epoch
ini_set('precision', '14');													// decimal number precision
ini_set('pcre.backtrack_limit', '8000000');									// PCRE BackTrack Limit 8M (min req. is 1M = 1000000) ; PCRE String Limits
ini_set('pcre.recursion_limit', '800000');									// PCRE Recursion Limit 800K (min req. is 100K = 100000) ; PCRE Expression Limits
//-- pcre JIT (you can disable this if you have very complex PCRE expressions combined with PHP functions that overflow the PCRE-JIT Memory)
//ini_set('pcre.jit', '0');													// PCRE JIT
//if((int)ini_get('pcre.jit') > 0) { // this may fail badly with very complex regex expressions, so if needed can be disabled
//	@http_response_code(500);
//	die('Smart.Framework INI // The PCRE-JIT should be disabled when very complex regular expressions are used that can overflow the PCRE-JIT very limited memory !');
//} //end if
//-- session stuff
if((string)ini_get('session.auto_start') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Session AutoSTART must be DISABLED !');
} //end if
if((string)ini_get('session.use_trans_sid') != '0') {
	@http_response_code(500);
	die('Smart.Framework INI // The PHP.INI Session TransSID must be DISABLED !');
} //end if
ini_set('session.save_handler', 'files');									// store session in 'files' (default) ; file storage as default ; can be set as `memcached` with session.save_path = 'localhost:11211' or other direct handler that PHP supports ; since PHP 7.3 there is no more supports set 'user' mode on session.save_handler ; PHP will just need to detect a custom handler user passes to handle the session
ini_set('session.gc_maxlifetime', 3600);									// GC Max Life Time in seconds after each sessions that were modified longer than this will be cleaned ; min is 1440 ; max is 65535 seconds or 2592000 seconds (30 days) depend on platform
ini_set('session.gc_probability', '1');										// GC Probability, Must be > 0 to use GC
ini_set('session.gc_divisor', '100');										// GC Divisor ; The probability is calculated by using gc_probability/gc_divisor, e.g. 1/100 means there is a 1% chance that the GC process starts on each request.
ini_set('session.use_cookies', '1');										// Session use cookies
ini_set('session.use_only_cookies', '1');									// It is safe to use only cookies for sessions, not send it by URL
ini_set('session.hash_bits_per_character', '5'); 							// session mode using characters as: (0-9, a-v) :: (only available since PHP 5.3)
ini_set('session.hash_function', 'sha512');									// set session hash to sha512 :: (only available since PHP 5.3)
ini_set('session.serialize_handler', 'php');								// use php (default) ; wddx can be buggy
//-- other checks:
// magic quotes runtime must be disabled :: no more necessary since PHP 5.4, it was removed
// suhoshin must be not enabled :: no more necessary to check as since PHP 5.4 was no more includded by default on popular distros :: suhoshin patch breaks a lot of functionality in latest PHP version, thus is not supported ... use it on your own risk !! ; example: it may break this app when working with large data packets or even corrupt session data or unicode strings
//----------------------------------------

//---------------------------------------- security: avoid load this multiple times
if(defined('SMART_FRAMEWORK_INITS')) {
	@http_response_code(500);
	die('Smart.Framework INI / Inits already loaded ...');
} //end if
define('SMART_FRAMEWORK_INITS', 'SET'); // avoid reload inits again (if accidentaly you do)
//----------------------------------------

// end of php code
