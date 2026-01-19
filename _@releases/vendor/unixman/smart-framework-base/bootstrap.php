<?php

// bootstrap/autoload Smart.Framework base classes for Vendor/Composer
// license: BSD
// (c) 2022-present unix-world.org
// r.20260118.2358

// ####### DO NOT MODIFY THIS FILE. IT WILL BE REWRITTEN ON ANY UPDATE OF vendor/unixman/smart-framework-base
// how to install: `php composer.phar require unixman/smart-framework-base @dev`

/*

// [ setup for Smart Configs ]
// create config/smart-cfg.php ; add the following ; comment out what is unused from below configs ...

define('SMART_FRAMEWORK_FILESYSUTILS_ROOTPATH', (string)realpath(__DIR__.'/../').'/');
define('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER', false); // false | 'redis' | 'mongodb'

global $configs;
$configs = [];

// redis
$configs['redis'] = [];
$configs['redis']['server-host']	= '127.0.0.1';							// redis host
$configs['redis']['server-port']	= 6379;									// redis port
$configs['redis']['dbnum']			= 8;									// redis db number 0..15
$configs['redis']['password']		= '';									// redis Base64-Encoded password ; by default is empty
$configs['redis']['timeout']		= 5;									// redis connect timeout in seconds
$configs['redis']['slowtime']		= 0.0005;								// redis slow query time (for debugging) 0.0010 .. 0.0001

// mongodb
$configs['mongodb'] = [];
$configs['mongodb']['type'] 		= 'mongo-standalone'; 					// mongodb server(s) type: 'mongo-standalone' | 'mongo-cluster' (sharding) | 'mongo-replica-set:My-Replica' (replica set)
$configs['mongodb']['server-host']	= '127.0.0.1';							// mongodb host or comma separed list of multiple hosts
$configs['mongodb']['server-port']	= 27017;								// mongodb port
$configs['mongodb']['dbname']		= 'some_database';						// mongodb database name
$configs['mongodb']['username'] 	= '';									// mongodb username
$configs['mongodb']['password'] 	= '';									// mongodb Base64-Encoded password
$configs['mongodb']['timeout']		= 5;									// mongodb connect timeout in seconds
$configs['mongodb']['slowtime']		= 0.0035;								// mongodb slow query time (for debugging) 0.0025 .. 0.0090

// pgsql
$configs['pgsql'] = [];
$configs['pgsql']['type'] 			= 'postgresql'; 						// postgresql / pgpool2 (UTF8)
$configs['pgsql']['server-host'] 	= '127.0.0.1';							// postgresql host (default is 127.0.0.1)
$configs['pgsql']['server-port']	= 5432;									// postgresql port (default is 5432)
$configs['pgsql']['dbname']			= 'smart_framework';					// postgresql database name ; Encoding=UTF8 ; Collation=C ; CharacterType=C
$configs['pgsql']['username']		= 'pgsql';								// postgresql server username
$configs['pgsql']['password']		= base64_encode('pgsql');				// postgresql server Base64-Encoded password for that user name B64
$configs['pgsql']['timeout']		= 10;									// postgresql connection timeout (how many seconds to wait for a valid PgSQL Connection)
$configs['pgsql']['slowtime']		= 0.0050; 								// postgresql slow query time (for debugging) 0.0025 .. 0.0090
$configs['pgsql']['transact']		= 'READ COMMITTED';						// postgresql session Default Transaction Level: 'READ COMMITTED' | 'REPEATABLE READ' | 'SERIALIZABLE' | '' to leave it as default

// mysql
$configs['mysqli'] = [];
$configs['mysqli']['type'] 			= 'mariadb'; 							// mariadb (UTF8.MB4) / mysql (UTF8)
$configs['mysqli']['server-host'] 	= '127.0.0.1';							// server host (default is 127.0.0.1)
$configs['mysqli']['server-port']	= 3306;									// server port (default is 3306)
$configs['mysqli']['dbname']		= 'smart_framework';					// database name
$configs['mysqli']['username']		= 'root';								// server username
$configs['mysqli']['password']		= base64_encode('root');				// server Base64-Encoded password for that user name B64
$configs['mysqli']['timeout']		= 10;									// server connection timeout (how many seconds to wait for a valid MySQL Connection)
$configs['mysqli']['slowtime']		= 0.0050; 								// server slow query time (for debugging) 0.0025 .. 0.0090
$configs['mysqli']['transact']		= 'REPEATABLE READ';					// session Default Transaction Level: 'REPEATABLE READ' | 'READ COMMITTED' | '' to leave it as default

// sqlite
$configs['sqlite'] = [];
$configs['sqlite']['timeout'] 		= 60;									// connection timeout
$configs['sqlite']['slowtime'] 		= 0.0025;								// slow query time (for debugging)

// dba
$configs['dba']['handler'] 			= '@autoselect'; 						// @autoselect or specific: gdbm, qdbm, db4
$configs['dba']['slowtime'] 		= 0.0025;								// slow query time (for debugging)

// example of loading a model
//require_once(__DIR__.'/../app/Models/MyModel.php');

*/

if(version_compare((string)phpversion(), '8.1.0') < 0) { // check for PHP 8.1 or later
	@http_response_code(500);
	die('Smart.Framework: PHP Runtime not supported: '.phpversion().' !'.'<br>PHP versions to run this software are: 8.1 / 8.2 / 8.3 / 8.4 / 8.5 or later');
} //end if

setlocale(LC_ALL, 'C'); // DON'T CHANGE THIS !!! THIS IS COMPATIBLE WILL ALL UTF-8 UNICODE CONTEXTS !!!
if((string)setlocale(LC_ALL, 0) != 'C') { // {{{SYNC-LOCALES-CHECK}}}
	@http_response_code(500);
	die('Smart.Framework: PHP Default Locales must be: `C` but it set to: `'.setlocale(LC_ALL, 0).'`');
} //end if

if((string)ini_get('zend.multibyte') != '0') {
	@http_response_code(500);
	die('Smart.Framework: PHP.INI Zend-MultiByte must be disabled ! Unicode support is managed via MBString into Smart.Framework ...');
} //end if

if(!define('SMART_FRAMEWORK_VENDOR_BASE_DIR', (string)__DIR__.'/src/')) {
	@http_response_code(500);
	die('Smart.Framework: Failed to define: SMART_FRAMEWORK_VENDOR_BASE_DIR');
} //end if

const SMART_MODULES_USE_VENDOR_CLASSES = true; // vendor classes are not includded in any provided modules, use the composer way to install and autoload vendor classes
const SMART_FRAMEWORK_VERSION = 'smart.framework.v.8.7'; // major version ; required for the framework libs
const SMART_FRAMEWORK_SECURITY_FILTER_INPUT = '/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/'; // !!! DO NOT MODIFY THIS UNLESS YOU KNOW WHAT YOU ARE DOING !!! This is a Safe Unicode Filter Input (GET/POST/COOKIE) Variables (Strings) as it will remove all lower dangerous characters: x00 - x1F and x7F except: \t = x09 \n = x0A \r = x0D
const SMART_FRAMEWORK_CHARSET = 'UTF-8'; // This must be `UTF-8`:: Default Character Set for PHP
const SMART_FRAMEWORK_SQL_CHARSET = 'UTF8'; // This must be `UTF8` :: Default Character Set for DB SQL Servers
const SMART_FRAMEWORK_NETSOCKET_TIMEOUT = 60; // Network Socket (Stream) TimeOut in Seconds
const SMART_FRAMEWORK_RUNTIME_MODE = 'web.vendor'; // runtime mode: 'web.vendor'
const SMART_FRAMEWORK_ADMIN_AREA = false; // run app in public/index mode ; must be set to FALSE for the public web environment
//const SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER = false; // Persistent Cache Handler ; If set to FALSE will use no handler ; If set otherwise can use Built-In: 'redis' or 'mongodb' ; or a Custom handler can be set as (example): 'modules/app/persistent-cache-custom-adapter.php'
define('SMART_FRAMEWORK_RUNTIME_READY', microtime(true)); // semaphore, runtime can execute scripts

ini_set('default_charset', (string)SMART_FRAMEWORK_CHARSET); // set the default charset
ini_set('default_socket_timeout', (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT); // socket timeout (2 min.)
ini_set('auto_detect_line_endings', '0'); // auto detect line endings
ini_set('y2k_compliance', '0'); // it is recommended to use this as disabled since POSIX systems keep time based on UNIX epoch
ini_set('precision', '14'); // decimal number precision
ini_set('pcre.backtrack_limit', '8000000'); // PCRE BackTrack Limit 8M (min req. is 1M = 1000000) ; PCRE String Limits
ini_set('pcre.recursion_limit', '800000'); // PCRE Recursion Limit 800K (min req. is 100K = 100000) ; PCRE Expression Limits
if((int)ini_get('pcre.jit') > 0) {
	ini_set('pcre.jit', '0'); // PCRE JIT can be disabled if explicit needed so
	if((int)ini_get('pcre.jit') > 0) {
		@http_response_code(500);
		die('Smart.Framework: The PHP.INI PCRE JIT could not be DISABLED !');
	} //end if
} //end if


const SMART_FRAMEWORK_NETSERVER_ID = 1; // Load Balancing: Unique ID, integer+ (min=0 ; max=1295) ; this is used for the main purpose to be able to generate very unique UUIDS in a cluster of apps ; every server in the cluster running the same app must have a different ID
const SMART_FRAMEWORK_SECURITY_KEY = 'Private-Key#0987654321'; // *** YOU HAVE TO CHANGE IT *** ; This is the Security Key that will be used to generate secure hashes
const SMART_SOFTWARE_NAMESPACE = 'smart-framework.vendor'; // APP Namespace ID :: [ _ a-z 0-9 - . ], length 10..25 :: This should be used as a unique ID identifier for the application (aka application unique ID)
const SMART_FRAMEWORK_ENV = 'prod'; // APP Environment: can be set to 'dev' or 'prod' ; id set to 'prod' (production environment) will not log E_USER_NOTICE and E_DEPRECATED and will not display in-page error details but just log them ; for development mode set this to 'dev'
const SMART_SOFTWARE_SQLDB_FATAL_ERR = true; // If set to false will throw \EXCEPTION which can be catched instead of raise a fatal error on all SQL DB adapters such as PostgreSQL / SQLite / MySQL (NOSQL adapters, ex: MongoDB or Redis can be set per instance and are not affected by this setting) ; WARNING: disabling SQL Fatal Errors is not safe, especially when using SQL transactions ... ; DO NOT modify this parameter unless you know what you are doing !!!
const SMART_SOFTWARE_MKTPL_DEBUG_LEN = 0; // If set will use this TPL Debug Length (255..524280) ; If not set will use default: 512
const SMART_FRAMEWORK_DEBUG_MODE = false; // enable debug mode

const SMART_FRAMEWORK_SSL_MODE =  				'tls';		// SSL/TLS Mode: tls | tls:1.1 | tls:1.2 | tls:1.3
const SMART_FRAMEWORK_SSL_CIPHERS = 			'HIGH';		// SSL/TLS Context Ciphers: ciphers ; default: 'HIGH' ; generally allow only high ciphers
const SMART_FRAMEWORK_SSL_VFY_HOST = 			true;		// SSL/TLS Context Verify Host: verify_host ; default: true
const SMART_FRAMEWORK_SSL_VFY_PEER = 			false;		// SSL/TLS Context Verify Peer: verify_peer ; default: false ; this may fail with some CAs
const SMART_FRAMEWORK_SSL_VFY_PEER_NAME = 		false;		// SSL/TLS Context Verify Peer Name: verify_peer_name ; default: false ; allow also wildcard names *
const SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED = 	true;		// SSL/TLS Context Allow Self-Signed Certificates: allow_self_signed ; default: true ; generally must allow self-signed certificates but verified above
const SMART_FRAMEWORK_SSL_DISABLE_COMPRESS = 	true;		// SSL/TLS Context Allow Self-Signed Certificates: disable_compression ; default: true ; help mitigate the CRIME attack vector
//const SMART_FRAMEWORK_SSL_CA_FILE = 			'';			// SSL/TLS Context CA Path: cafile ; default: '' ; if non-empty, must point to something like 'etc/cacert.pem' or another path to a certification authority pem


//--
function autoload__SmartFrameworkVendorBase($classname) {
	//--
	if((string)substr((string)$classname, 0, 5) !== 'Smart') { // must start with Smart
		return;
	} //end if
	//--
	switch((string)$classname) {
		//-- framework
		case 'SmartUnicode':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_unicode.php'); // smart unicode (support)
			break;
		case 'SmartEnvironment':
		case 'SmartFrameworkSecurity':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_security.php'); // smart security (compliance)
			break;
		case 'Smart':
		case 'SmartFileSysUtils':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_smart.php'); // smart (base) core + filesysutils
			break;
		case 'SmartHashPoly1305':
		case 'SmartHashCrypto':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_cryptohs.php'); // smart crypto (utils) hash
			break;
		case 'SmartDhKx':
		case 'SmartCryptoCiphersThreefishCBC':
		case 'SmartCryptoCiphersTwofishCBC':
		case 'SmartCryptoCiphersBlowfishCBC':
		case 'SmartCryptoCiphersOpenSSL':
		case 'SmartCryptoCiphersHashCryptOFB':
		case 'SmartCipherCrypto':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_cryptoss.php'); // smart crypto (utils) symmetric
			break;
		case 'SmartCsrf':
		case 'SmartCryptoEddsaSodium':
		case 'SmartCryptoEcdsaAsn1Sig':
		case 'SmartInterfaceCryptoAsOpenSSL':
		case 'SmartAbstractCryptoAsOpenSSL':
		case 'SmartCryptoEcdsaOpenSSL':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_cryptoas.php'); // smart crypto (utils) asymmetric
			break;
		case 'SmartZLib':
		case 'SmartGZip':
		case 'SnappyUtils':
		case 'SnappyCompressor':
		case 'SnappyDecompressor':
		case 'SmartSnappy':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_archive.php'); // smart archive compress/uncompress support
			break;
		case 'SmartCache':
		case 'SmartAbstractPersistentCache':
		case 'SmartPersistentCache': // *ONLY* if SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER === false, otherwise must load a derived persistent cache class such as 'redis' or 'mongodb', later, in configs
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_caching.php'); // smart cache (non-persistent + abstract persistent)
			break;
		case 'SmartMarkersTemplating':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_templating.php'); // smart markers templating engine
			break;
		case 'SmartValidator':
		case 'SmartParser':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_valid_parse.php'); // smart validators and parsers
			break;
		case 'SmartHttpUtils':
		case 'SmartHttpClient':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_http_cli.php'); // smart http client
			break;
		case 'SmartAuth':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/lib_auth.php'); // smart authentication
			break;
		//-- plugins
		case 'SmartYamlConverter':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_yaml.php'); // yaml converter
			break;
		case 'SmartDomUtils':
		case 'SmartXmlComposer':
		case 'SmartXmlParser':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_xml.php'); // xml parser and composer
			break;
		case 'SmartHtmlParser':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_html.php'); // html parser
			break;
		case 'SmartMarkdownToHTML':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_markdown.php'); // markdown to html parser, v2
			break;
		case 'SmartPunycode':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_idn_punycode.php'); // idn punnycode converter
			break;
		case 'SmartDetectImages':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_detect_img.php'); // detect img
			break;
		case 'SmartMailerSmtpClient':
		case 'SmartMailerSend':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_mail_send.php'); // mail send client (sendmail, smtp)
			break;
		case 'SmartMailerImap4Client':
		case 'SmartMailerPop3Client':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_mail_get.php'); // mail get client (pop3, imap4)
			break;
		case 'SmartMailerNotes':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_mail_notes.php'); // mail notes (mime parsing fixes, decode, encode)
			break;
		case 'SmartMailerMimeExtract':
		case 'SmartMailerMimeDecode':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_mail_decode.php'); // mail message decoder (mime)
			break;
		case 'SmartSQliteFunctions':
		case 'SmartSQliteUtilDb':
		case 'SmartSQliteDb':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_db_sqlite.php'); // sqlite3 db connector
			break;
		case 'SmartRedisDb':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_db_redis.php'); // redis db connector
			break;
		case 'SmartMongoDb':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_db_mongodb.php'); // mongodb db connector
			break;
		case 'SmartPgsqlDb':
		case 'SmartPgsqlExtDb':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_db_pgsql.php'); // postgresql db connector
			break;
		case 'SmartMysqliDb':
		case 'SmartMysqliExtDb':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_db_mysqli.php'); // mysqli db connector
			break;
		case 'SmartRedisPersistentCache':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_pcache_redis.php'); // redis persistent cache
			break;
		case 'SmartMongoDbPersistentCache':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_pcache_mongodb.php'); // mongodb persistent cache
			break;
		case 'SmartSpreadSheetExport':
		case 'SmartSpreadSheetImport':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_spreadsheet.php'); // spreadsheet export / import
			break;
		case 'SmartQR2DBarcode':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_qrcode.php'); // qrcode integration for captcha
			break;
		case 'SmartAsciiCaptcha':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_captcha_ascii.php'); // captcha ascii plugin
			break;
		case 'SmartImageGdProcess':
			require_once(SMART_FRAMEWORK_VENDOR_BASE_DIR.'lib/framework/plugins/lib_imgd.php'); // img (gd) process
			break;
		//--
		default:
			return; // other classes are not managed here ...
		//--
	} //end switch
	//--
} //END FUNCTION
//--
spl_autoload_register('autoload__SmartFrameworkVendorBase', true, true); // throw / prepend
//--
function autoload__SmartFrameworkVendorModClasses($classname) {
	//--
	$classname = (string) strval($classname);
	//--
	if((strpos((string)$classname, '\\') === false) OR (!preg_match('/^[a-zA-Z0-9_\\\]+$/', (string)$classname))) { // if have no namespace or not valid character set
		return;
	} //end if
	//--
	if((strpos((string)$classname, 'SmartModExtLib\\') === false) AND (strpos((string)$classname, 'SmartModDataModel\\') === false)) { // must start with this namespaces only
		return;
	} //end if
	//--
	$parts = (array) explode('\\', (string)$classname);
	if((int)count($parts) != 3) { // need for [0], [1] and [2]
		return;
	} //end if
	if((string)trim((string)$parts[0]) == '') { // type
		return; // no module detected
	} //end if
	if((string)trim((string)$parts[1]) == '') { // mod suffix
		return; // no module detected
	} //end if
	if((string)trim((string)$parts[2]) == '') { // class file
		return; // invalid
	} //end if
	//--
	$dir = (string) SMART_FRAMEWORK_VENDOR_BASE_DIR.'modules/mod';
	$dir .= (string) strtolower((string)implode('-', preg_split('/(?=[A-Z])/', (string)$parts[1])));
	if((string)$parts[0] == 'SmartModExtLib') {
		$dir .= '/libs/';
	} elseif((string)$parts[0] == 'SmartModDataModel') {
		$dir .= '/models/';
	} else {
		return; // other namespaces are not managed here
	} //end if else
	$dir = (string) $dir;
	$file = (string) $parts[2];
	$path = (string) $dir.$file;
	$path = (string) trim((string)str_replace(['\\', "\0"], '', (string)$path)); // filter out null byte and backslash
	//--
	if(((string)$path == '') OR (!preg_match('/^[_a-zA-Z0-9\-\/]+$/', (string)$path))) {
		return; // invalid path characters in file
	} //end if
	//--
	if(!is_file((string)$path.'.php')) { // here must be used is_file() because is autoloader ...
		return; // file does not exists
	} //end if
	//--
	require_once((string)$path.'.php');
	//--
} //END FUNCTION
//--
spl_autoload_register('autoload__SmartFrameworkVendorModClasses', true, false); // throw / append
//--

// #end
