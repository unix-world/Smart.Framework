<?php
// [LIB - Smart.Framework / HTTP(S) Client]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - HTTP(S) Client w. (TLS/SSL)
//======================================================

//--
if(!function_exists('stream_context_create')) {
	@http_response_code(500);
	die('ERROR: The PHP stream_context_create is required for Smart.Framework / Lib HTTP Cli');
} //end if
//--
array_map(function($const){ if(!defined((string)$const)) { @http_response_code(500); die('A required INIT constant has not been defined: '.$const); } }, ['SMART_FRAMEWORK_SSL_MODE', 'SMART_FRAMEWORK_SSL_CIPHERS', 'SMART_FRAMEWORK_SSL_VFY_HOST', 'SMART_FRAMEWORK_SSL_VFY_PEER', 'SMART_FRAMEWORK_SSL_VFY_PEER_NAME', 'SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED', 'SMART_FRAMEWORK_SSL_DISABLE_COMPRESS', 'SMART_FRAMEWORK_SSL_CA_FILE']);
//--


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartHttpClient - provides a HTTP / HTTPS Client (browser).
 *
 * To work with TLS / SSL (requires the PHP OpenSSL Module).
 * Implemented Methods: Standard (GET / POST / HEAD) ; Extended (PUT / DELETE / MKCOL / OPTIONS / MOVE / COPY / PROPFIND).
 * The Standard Methods will prefere HTTP 1.0 (which provide a faster access)
 * The Extended Methods will prefere HTTP 1.1 instead of 1.0 because of some additional headers that may validate for error checks ...
 *
 * <code>
 * // Sample GET
 * $browser = new SmartHttpClient(); // HTTP 1.0
 * $browser->connect_timeout = 20;
 * print_r(
 * 		$browser->browse_url('https://some-website.ext:443/some-path/', 'GET', 'tls')
 * );
 *
 * // Sample POST, with optional Files and Cookies ; If Files, will send multipart form data
 * $browser = new SmartHttpClient();
 * $browser->postvars = [
 * 		'var1' => 'val1',
 * 		'var2' => 'val2'
 * ];
 * $browser->postfiles = [ // optional
 * 		'my_file' => [
 * 			'filename' => 'sample.txt',
 * 			'content'  => 'this is the content of the file'
 * 		],
 * 		'my_other_file' => [
 * 			'filename' => 'sample.xml',
 * 			'content'  => '<xml>test</xml>'
 * 		]
 * ];
 * $browser->cookies = [ // optional
 * 		'sessionID' => '12345'
 * ];
 * print_r(
 * 		$browser->browse_url('https://some-website.ext:443/some-path/', 'POST', 'tls')
 * );
 *
 * // Sample PUT
 * $browser = new SmartHttpClient('1.1'); // HTTP 1.1
 * $browser->connect_timeout = 20;
 * $browser->putbodyres = 'tmp/test-file.txt'; // using a file
 * $browser->putbodymode = 'file';
 * //$browser->putbodyres = '123'; // alternate can use a string
 * //$browser->putbodymode = 'string';
 * print_r(
 * 		$browser->browse_url('https://some-website.ext:443/some-path/~/some-file.txt', 'PUT', 'tls', 'admin', 'pass')
 * );
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	extensions: PHP OpenSSL (optional, just for HTTPS) ; classes: Smart, SmartFileSysUtils, SmartFileSystem, SmartHttpUtils ; constants: SMART_FRAMEWORK_SSL_MODE, SMART_FRAMEWORK_SSL_CIPHERS, SMART_FRAMEWORK_SSL_VFY_HOST, SMART_FRAMEWORK_SSL_VFY_PEER, SMART_FRAMEWORK_SSL_VFY_PEER_NAME, SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED, SMART_FRAMEWORK_SSL_DISABLE_COMPRESS, SMART_FRAMEWORK_SSL_CA_FILE
 * @version 	v.20210613
 * @package 	@Core:Network
 *
 */
final class SmartHttpClient {

	// ->

	//==============================================

	//--

	/**
	 * User agent (if used as robot, it must have the robot in the name to avoid start un-necessary sessions)
	 * @var STRING
	 * @default 'NetSurf/3.9 (Smart.Framework {version}; {OS} {Release} {Arch}; PHP/{php-ver})'
	 */
	public $useragent;

	/**
	 * Connect timeout in seconds
	 * @var INTEGER+
	 * @default 30
	 */
	public $connect_timeout;

	//--

	/**
	 * Array of RawHeaders (to send)
	 * @var ARRAY
	 * @default []
	 */
	public $rawheaders;

	/**
	 * Array of Cookies (to send)
	 * @var ARRAY
	 * @default []
	 */
	public $cookies;

	//--

	/**
	 * Pre-Built Post String (as alternative to PostVars) ; must not contain unencoded \r\n ; must use the RFC 3986 standard.
	 * If $poststring is used the $postvars and $postfiles are ignored
	 * @var STRING
	 * @default ''
	 */
	public $poststring;

	/**
	 * Array of PostVars (to send)
	 * @var ARRAY
	 * @default []
	 */
	public $postvars;

	/**
	 * Array of PostFiles (to send) ; This can be used only in combination with $postvars ; Example [ 'filename' => 'file.txt', 'content' => 'the contents go here' ]
	 * @var ARRAY
	 * @default []
	 */
	public $postfiles;

	//--

	/**
	 * PUT Request Mode (used for $putbodyres)
	 * Can have one of the values: 'string' or 'file'
	 * @var ENUM
	 * @default 'string'
	 */
	public $putbodymode;

	/**
	 * PUT Request (to send)
	 * If $putbodymode is set to 'string', this string will be sent as put
	 * If $putbodymode is set to 'file', the content of this file will be sent as put ; this must be a valid relative path to a file (ex: tmp/file-to-put.txt)
	 * @var MIXED
	 * @default ''
	 */
	public $putbodyres;

	//--

	/**
	 * Return no Content (empty response body) if Not Auth (401)
	 * If the response HTTP Status will return 401, if this is set to TRUE, will return empty content body ; else will return the HTML response body that server response set for 401 (irrelevant but in some cases may be important to parse)
	 * @var BOOLEAN
	 * @default true
	 */
	public $skipcontentif401;

	/**
	 * If set to TRUE will disable expect-100-continue and will skip parsing 100-continue header from server
	 * Applies just for HTTP 1.1 and
	 * This is a fix for those servers that does not comply with situations where 100-continue is required on HTTP 1.1
	 * @var BOOLEAN
	 * @default false
	 */
	public $skip100continue;

	//--

	/**
	 * Enable or Disable Debug for this class and the HTTP Request
	 * If set to TRUE will enable debug
	 * @var BOOLEAN
	 * @default false
	 */
	public $debug;

	//--

	//============================================== privates
	//-- set
	private $protocol = '1.0';								// HTTP Protocol :: 1.0 (default) or 1.1
	//-- returns
	private $header;										// Header (answer)
	private $body;											// Body (answer)
	private $status;										// STATUS (answer) :: 200, 401, 403 ...
	private $put_body_len;									// PUT Body Length
	//-- log
	private $log;											// Operations Log (debug only)
	//-- internals
	private $socket = false;								// The Communication Socket
	private $raw_headers = [];								// Raw-Headers (internals)
	private $url_parts = [];								// URL Parts
	private $method = 'GET';								// method: GET / POST / HEAD + WebDAV methods (HTTP 1.1 is recommended): PUT / DELETE / MKCOL / OPTIONS / MOVE / COPY / PROPFIND ...
	//--
	private $cafile = '';									// Certificate Authority File (instead of using the global SMART_FRAMEWORK_SSL_CA_FILE can use a private cafile
	//--
	//==============================================


	//==============================================
	/**
	 * Class constructor
	 *
	 * @param ENUM $http_protocol The HTTP protocol version to use ; can be set to 1.0 or 1.1 ; default is 1.0 (HTTP 1.0) which is fastest for single requests, especially in simple situations like GET or POST ; can be set to 1.1 (HTTP 1.1) which may be required in special situations for more complex requests ; HTTP 1.1 mostly will serve transfer content chunked on GET/POST so for GET/POST is better to sue 1.0 if supported by server
	 */
	public function __construct($http_protocol='1.0') {

		//-- signature (fake) as NetSurf Browser - which is a real browser but have no default support for Javascript
		$this->useragent = 'NetSurf/3.9 ('.'Smart.Framework '.SMART_FRAMEWORK_RELEASE_TAGVERSION.' '.SMART_FRAMEWORK_RELEASE_VERSION.'; '.php_uname('s').' '.php_uname('r').' '.php_uname('m').'; '.'PHP/'.PHP_VERSION.')';
		//--

		//-- connection timeout
		$this->connect_timeout = 30;
		//--

		//-- HTTP protocol: 1.0 or 1.1
		switch((string)$http_protocol) {
			case '1.1':
				$this->protocol = '1.1'; // for extended methods (Ex: WebDAV this is safer than 1.0)
				break;
			case '1.0':
			default:
				$this->protocol = '1.0'; // default is 1.0 (is faster than 1.1 for GET / POST because HTTP 1.1 will mostly return chunked transfer which is costly to parse on client side ...)
		} //end switch
		//--

		//-- misc
		$this->rawheaders = array();
		$this->cookies = array();
		//--
		$this->poststring = '';
		$this->postvars = array();
		$this->postfiles = array();
		//--
		$this->putbodymode = 'string';
		$this->putbodyres = '';
		//--
		$this->skipcontentif401 = true;
		$this->skip100continue = false;
		//--

		//-- debugging
		$this->debug = false;
		//--

		//-- reset
		$this->reset();
		//--

	} //END FUNCTION
	//==============================================


	//==============================================
	/**
	 * This is only for special cases and can be used before calling the browse_url() for the cases when the client requires a custom SSL Certificate to be set
	 * Set a SSL/TLS Certificate Authority File ; by default will use the SMART_FRAMEWORK_SSL_CA_FILE
	 */
	public function set_ssl_tls_ca_file($cafile) {
		//--
		$this->cafile = '';
		if(SmartFileSysUtils::check_if_safe_path((string)$cafile) == '1') {
			if(SmartFileSystem::is_type_file((string)$cafile)) {
				$this->cafile = (string) $cafile;
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	/* Browse a HTTP(S) URL
	 *
	 * @param STRING $url The URL to be browsed ; Ex: http(s)://url as a robot
	 * @param ENUM $method The HTTP Method: Available methods: GET, POST, HEAD, PUT
	 * @param ENUM $sslversion *Optional* Connection Mode, must be set to any of these accepted values: '', 'tls', 'tls:1.0', 'tls:1.1', 'tls:1.2', 'ssl', 'sslv3' ; If empty string is set here it will be operate in unsecure mode (NOT using any SSL/TLS Mode)
	 * @param STRING $user *Optional* If Basic Auth credentials have to be used, set the login username here, otherwise leave empty
	 * @param STRING $pwd  *Optional* If Basic Auth credentials have to be used, set the login password here, otherwise leave empty
	 * @return ARRAY The result of browsing ; If The connection was Successful will return the ARRAY['result'] = 1 ; The HTTP Status Code returned by server will be stored in ARRAY['code'] (by default a 200 Status OK is considered successful but other codes may be also OK in certain circumstances)
	 */
	public function browse_url($url, $method='GET', $ssl_version='', $user='', $pwd='') {
		//--
		$url = (string) trim((string)$url);
		//--
		$errmsg = '';
		if((int)strlen($url) <= 4096) {
			if(((string)substr((string)$url, 0, 7) == 'http://') OR ((string)substr((string)$url, 0, 8) == 'https://')) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
				$result = $this->get_answer((string)$url, (string)$user, (string)$pwd, (string)$method, (string)$ssl_version);
			} else {
				$errmsg = 'URL must start with a `http://` or `https://` prefix';
				$result = -1;
			} //end if else
		} else {
			$errmsg = 'URL is too long, more than 4096 characters';
			$result = -2;
		} //end if else
		//--
		$redirect_url = '';
		if((int)$result == 1) {
			if(((int)$this->status == 301) OR ((int)$this->status == 302)) {
				if((string)$this->header != '') {
					if(strpos((string)$this->header, 'Location:') !== false) {
						$redirect_url = (string) $this->header;
						$redirect_url = (array) explode('Location:', (string)$redirect_url);
						$redirect_url = (string) ($redirect_url[1] ?? '');
						$redirect_url = (array) explode("\n", (string)$redirect_url);
						$redirect_url = (string) ($redirect_url[0] ?? '');
						$redirect_url = (string) trim((string)$redirect_url);
						if((string)$redirect_url != '') {
							if(((string)substr((string)$redirect_url, 0, 7) == 'http://') OR ((string)substr((string)$redirect_url, 0, 8) == 'https://')) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
								// OK
							} else {
								$redirect_url = ''; // malformed redirect URL
							} //end if else
						} //end if
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
			'client' 			=> (string) __CLASS__,
			'date-time' 		=> (string) date('Y-m-d H:i:s O'),
			'protocol' 			=> (string) $this->protocol,
			'method' 			=> (string) $this->method,
			'url' 				=> (string) $url,
			'ssl'				=> (string) $ssl_version,
			'ssl-ca' 			=> (string) ($this->cafile ? $this->cafile : (defined('SMART_FRAMEWORK_SSL_CA_FILE') ? SMART_FRAMEWORK_SSL_CA_FILE : '')),
			'auth-user' 		=> (string) $user,
			'cookies-len' 		=> (int)    Smart::array_size($this->cookies),
			'post-str-len' 		=> (int)    strlen($this->poststring),
			'post-vars-len' 	=> (int)    Smart::array_size($this->postvars),
			'post-files-len' 	=> (int)    Smart::array_size($this->postfiles),
			'put-resource' 		=> (string) substr($this->putbodyres, 0, 255).' ...',
			'put-res-mode' 		=> (string) $this->putbodymode,
			'put-body-len' 		=> (int)    $this->put_body_len,
			'mode' 				=> (string) trim((string)($this->url_parts['protocol'] ?? '')),
			'errmsg' 			=> (string) $errmsg,
			'result' 			=> (int)    $result,
			'pre-code' 			=> (string) $this->pre_status, // if 100-continue, this is the HTTP 1.1 Pre-Status
			'pre-headers' 		=> (string) $this->pre_header, // if 100-continue, this is the HTTP 1.1 Pre-Header
			'code' 				=> (string) $this->status,
			'redirect-url' 		=> (string) $redirect_url,
			'headers' 			=> (string) $this->header,
			'content' 			=> (string) $this->body,
			'log' 				=> (string) 'User-Agent: '.$this->useragent."\n", // this is reserved for calltime functions
			'debuglog' 			=> (string) $this->log, // this is for internal use
		);
		//--
	} //END FUNCTION
	//==============================================


	## PRIVATES


	//==============================================
	private function get_answer($url, $user, $pwd, $method, $ssl_version) {

		//-- reset
		$this->reset();
		//--

		//--
		if($this->debug) {
			$run_time = microtime(true);
		} //end if
		//--

		//-- set raw headers
		if(is_array($this->rawheaders)) {
			foreach($this->rawheaders as $key => $val) {
				$this->raw_headers[(string)$key] = (string) $val;
			} //end foreach
		} //end if
		//--

		//-- user agent will not be rewritten above
		if((string)$this->protocol == '1.1') {
			$this->raw_headers['Connection'] = 'close'; // fix for HTTP 1.1: by default on HTTP 1.1 connection is: keep-alive and must be set to explicit: close
		} //end if
		$this->raw_headers['User-Agent'] = (string) $this->useragent;
		//--

		//-- log action
		if($this->debug) {
			$this->log .= '[INF] HTTP(S) Robot Browser :: Get Answer :: url \''.$url.'\' @ Auth-User: '.$user.' // Auth-Pass-Length: ('.strlen($pwd).') // Method: '.$method.' // SSLVersion: '.$ssl_version."\n";
		} //end if
		//--

		//-- check if url supplied
		if((string)$url == '') {
			if($this->debug) {
				$this->log .= '[ERR] URL to browse is missing !'."\n";
			} //end if
			Smart::log_warning('LibHTTP // GetAnswer // URL to browse is empty ...');
			return 0;
		} //end if
		//--

		//-- get from url
		$success = $this->send_request($url, $user, $pwd, $method, $ssl_version);
		//--
		if(!$success) {
			if($this->debug) {
				$this->log .= '[ERR] Robot Browser Failed !'."\n";
				Smart::log_notice('LibHTTP // GetAnswer // Robot Browser Failed ... '.$url);
			} //end if
			$this->close_connection();
			return 0;
		} //end if
		//--

		//-- check
		if(!$this->socket) {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Premature connection end (2.1)'."\n";
				Smart::log_notice('LibHTTP // GetAnswer // Premature connection end (2.1) ...'.$url);
			} //end if
			$this->close_connection();
			return 0;
			//--
		} //end if
		//--

		//-- Get response header
		$this->header = (string) @fgets($this->socket, 4096);
		$this->status = (string) trim((string)substr((string)trim((string)$this->header), 9, 3));
		//--
		$is_unauth = false;
		if(((string)$this->status == '401') AND (stripos($this->header, ' 401 Unauthorized') !== false)) {
			//--
			$is_unauth = true;
			//--
			if($this->debug) {
				if((string)$user != '') {
					$this->log .= '[ERR] HTTP Authentication Failed for URL: [User='.$user.']: '.$url."\n";
					Smart::log_notice('LibHTTP // GetAnswer // HTTP Authentication Failed for URL: [User='.$user.']: '.$url);
				} else {
					$this->log .= '[ERR] HTTP Authentication is Required for URL: '.$url."\n";
					Smart::log_notice('LibHTTP // GetAnswer // HTTP Authentication is Required for URL: '.$url);
				} //end if
			} //end if
			//--
		} //end if
		//--
		while(($this->socket) && (trim($line = @fgets($this->socket, 4096)) != '') && (!feof($this->socket))) {
			//--
			$this->header .= (string) $line;
			//--
			if(!$this->socket) {
				//--
				if($this->debug) {
					$this->log .= '[ERR] Premature connection end (2.2)'."\n";
					Smart::log_notice('LibHTTP // GetAnswer // Premature connection end (2.2) ... '.$url);
				} //end if
				$this->close_connection();
				return 0;
				//--
			} //end if
			//--
		} //end while
		//--
		if(($is_unauth === true) AND ($this->skipcontentif401 !== false)) { // in this case (by settings) skip the response body and stop here
			$this->close_connection();
			return 0;
		} //end if
		//--

		//-- check
		if(!$this->socket) {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Premature connection end (2.3)'."\n";
				Smart::log_notice('LibHTTP // GetAnswer // Premature connection end (2.3) ... '.$url);
			} //end if
			$this->close_connection();
			return 0;
			//--
		} //end if
		//--

		//-- Get response body
		while(($this->socket) && (!feof($this->socket))) {
			//--
			$this->body .= (string) @fgets($this->socket, 4096);
			//--
			if(!$this->socket) {
				//--
				if($this->debug) {
					$this->log .= '[ERR] Premature connection end (2.4)'."\n";
					Smart::log_notice('LibHTTP // GetAnswer // Premature connection end (2.4) ... '.$url);
				} //end if
				$this->close_connection();
				return 0;
				//--
			} //end if
			//--
		} //end while
		//-- if HTTP 1.1 Transfer Chunked, try to parse the chunked body
		if((string)$this->protocol == '1.1') {
			if((string)trim((string)$this->header) != '') {
				if(stripos((string)$this->header, 'Transfer-Encoding: chunked') !== false) {
					if((string)trim((string)$this->body) != '') {
						$this->body = (string) SmartHttpUtils::chunked_part_decode($this->body);
					} //end if
				} //end if
			} //end if
		} //end if
		//--

		//--
		$this->close_connection();
		//--

		//--
		if($this->debug) {
			//--
			$run_time = microtime(true) - $run_time;
			//--
			$this->log .= '[INF] Total Time: '.$run_time.' sec.'."\n";
			//--
		} //end if
		//--

		//--
		return 1;
		//--

	} //END FUNCTION
	//==============================================


	//==============================================
	private function reset() {
		//-- the log
		$this->log = '';
		//-- outputs
		$this->pre_status = '';
		$this->pre_header = '';
		$this->status = '';
		$this->header = '';
		$this->body = '';
		$this->put_body_len = 0;
		//-- internals
		$this->method = 'GET';
		$this->raw_headers = array();
		$this->url_parts = array();
		$this->socket = false;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	// [PRIVATE] :: close connection
	private function close_connection() {
		//--
		if($this->socket) {
			//--
			@fclose($this->socket);
			//--
			if($this->debug) {
				$this->log .= '[INF] Connection Closed: OK.'."\n";
			} //end if
			//--
		} else {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Connection is already closed ...'."\n";
				Smart::log_notice('LibHTTP // GetAnswer // Connection is already closed ...');
			} //end if
			//--
		} //end if
		//--
		$this->socket = false;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	// [PRIVATE] :: request from url and return content, headers, ...
	private function send_request($url, $user='', $pwd='', $method='GET', $ssl_version='') {

		//--
		$this->method = (string) strtoupper(trim((string)$method));
		//--

		//--
		$this->connect_timeout = (int) $this->connect_timeout;
		if($this->connect_timeout < 1) {
			$this->connect_timeout = 1;
		} //end if
		if($this->connect_timeout > 120) {
			$this->connect_timeout = 120;
		} //end if
		//--

		//-- log action
		if($this->debug) {
			$this->log .= '[INF] Request From URL :: is starting ...'."\n";
		} //end if
		//--

		//-- separations
		$this->url_parts 	= (array)  Smart::url_parse($url);
		$protocol 			= (string) $this->url_parts['protocol'];
		$host 				= (string) $this->url_parts['host'];
		$port 				= (string) $this->url_parts['port'];
		$path 				= (string) $this->url_parts['suffix']; // path + query
		//--
		if($this->debug) {
			$this->log .= '[INF] Analize of the URL result: '.print_r($this->url_parts,1)."\n";
		} //end if
		//--

		//--
		if((string)$host == '') {
			if($this->debug) {
				$this->log .= '[ERR] Invalid Server to Browse'."\n";
			} //end if
			Smart::log_warning('LibHTTP // RequestFromURL () // Invalid (empty) Server to Browse ...');
			return 0;
		} //end if
		//--

		//--
		$browser_protocol = '';
		//--
		if((string)$protocol == 'https://') {
			//--
			switch((string)strtolower((string)$ssl_version)) {
				//--
				case 'ssl':
					$browser_protocol = 'ssl://'; // deprecated
					break;
				case 'sslv3':
					$browser_protocol = 'sslv3://'; // deprecated
					break;
				//--
				case 'tls:1.0':
					$browser_protocol = 'tlsv1.0://';
					break;
				case 'tls:1.1':
					$browser_protocol = 'tlsv1.1://';
					break;
				case 'tls:1.2':
					$browser_protocol = 'tlsv1.2://';
					break;
				case 'tls':
				default: // other cases
					$browser_protocol = 'tls://';
			} //end switch
			//--
			if(!function_exists('openssl_open')) {
				if($this->debug) {
					$this->log .= '[ERR] PHP OpenSSL Extension is required to perform SSL requests'."\n";
				} //end if
				Smart::log_warning('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // PHP OpenSSL Extension not installed ...');
				return 0;
			} //end if
			//--
		} elseif((string)$protocol == 'http://') {
			//--
			// OK
			//--
		} else {
			//--
			Smart::log_notice('LibHTTP // RequestFromURL ('.$url.') // The URL is INVALID (not http or https) ...');
			return 0;
			//--
		} //end if else
		//--

		//--
		$have_cookies = false;
		if(is_array($this->cookies)) {
			if(count($this->cookies) > 0) {
				$have_cookies = true;
			} //end if
		} //end if
		//--
		$have_post_vars = false;
		$have_post_files = false;
		if(((string)$this->poststring != '') OR (Smart::array_size($this->postvars) > 0)) {
			$have_post_vars = true;
		} elseif(Smart::array_size($this->postfiles) > 0) {
			$have_post_files = true;
		} //end if
		//--

		//-- navigate
		if($this->debug) {
			$this->log .= 'Opening HTTP(S) Browser Connection to: '.$protocol.$host.':'.$port.$path.' using socket protocol: ['.$browser_protocol.']'."\n";
			$this->log .= '[INF] HTTP Protocol: '.$this->protocol."\n";
			$this->log .= '[INF] Connection TimeOut: '.$this->connect_timeout."\n";
		} //end if
		//--
		$stream_context = @stream_context_create();
		if((string)$browser_protocol != '') {
			//--
			$cafile = '';
			if((string)$this->cafile != '') {
				$cafile = (string) $this->cafile;
			} elseif(defined('SMART_FRAMEWORK_SSL_CA_FILE')) {
				if((string)SMART_FRAMEWORK_SSL_CA_FILE != '') {
					$cafile = (string) SMART_FRAMEWORK_SSL_CA_FILE;
				} //end if
			} //end if
			//--
			if((string)$cafile != '') {
				@stream_context_set_option($stream_context, 'ssl', 'cafile', Smart::real_path((string)$cafile));
			} //end if
			//--
			@stream_context_set_option($stream_context, 'ssl', 'ciphers', 				(string)SMART_FRAMEWORK_SSL_CIPHERS); // allow only high ciphers
			@stream_context_set_option($stream_context, 'ssl', 'verify_host', 			(bool)SMART_FRAMEWORK_SSL_VFY_HOST); // allways must be set to true !
			@stream_context_set_option($stream_context, 'ssl', 'verify_peer', 			(bool)SMART_FRAMEWORK_SSL_VFY_PEER); // this may fail with some CAs
			@stream_context_set_option($stream_context, 'ssl', 'verify_peer_name', 		(bool)SMART_FRAMEWORK_SSL_VFY_PEER_NAME); // allow also wildcard names *
			@stream_context_set_option($stream_context, 'ssl', 'allow_self_signed', 	(bool)SMART_FRAMEWORK_SSL_ALLOW_SELF_SIGNED); // must allow self-signed certificates but verified above
			@stream_context_set_option($stream_context, 'ssl', 'disable_compression', 	(bool)SMART_FRAMEWORK_SSL_DISABLE_COMPRESS); // help mitigate the CRIME attack vector
			//--
		} //end if else
		$this->socket = @stream_socket_client($browser_protocol.$host.':'.$port, $errno, $errstr, $this->connect_timeout, STREAM_CLIENT_CONNECT, $stream_context);
		//--
		if(!is_resource($this->socket)) {
			if($this->debug) {
				$this->log .= '[ERR] Could not open connection. Error : '.$errno.': '.$errstr."\n";
				Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Could not open connection. Error : '.$errno.': '.$errstr.' #');
			} //end if
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] Socket Resource ID: '.$this->socket."\n";
		} //end if
		//--
		@stream_set_timeout($this->socket, (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT);
		if($this->debug) {
			$this->log .= '[INF] Set Socket Stream TimeOut to: '.SMART_FRAMEWORK_NETSOCKET_TIMEOUT."\n";
		} //end if
		//--

		//-- avoid connect normally if SSL/TLS was explicit required
		$chk_crypto = (array) @stream_get_meta_data($this->socket);
		if((string)$browser_protocol != '') {
			if(stripos($chk_crypto['stream_type'], '/ssl') === false) { // will return something like: tcp_socket/ssl
				if($this->debug) {
					$this->log .= '[ERR] Connection CRYPTO CHECK Failed ...'."\n";
					Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Connection CRYPTO CHECK Failed ...');
				} //end if
				return 0;
			} //end if
		} //end if
		//--

		//--
		$this->raw_headers['Host'] = $host.':'.$port;
		//--

		//-- auth
		if(((string)$user != '') AND ((string)$pwd != '')) {
			//--
			if($this->debug) {
				$this->log .= '[INF] Authentication will be attempted for USERNAME = \''.$user.'\' ; PASSWORD('.strlen($pwd).') *****'."\n";
			} //end if
			//--
			$this->raw_headers['Authorization'] = 'Basic '.base64_encode($user.':'.$pwd);
			//--
		} //end if
		//--

		//-- cookies
		$send_cookies = '';
		//--
		if($have_cookies) {
			//--
			foreach($this->cookies as $key => $value) {
				if((string)$key != '') {
					if((string)$value != '') {
						$send_cookies .= (string) SmartHttpUtils::encode_var_cookie($key, $value);
					} //end if
				} //end if
			} //end foreach
			//--
			if((string)$send_cookies != '') {
				$this->raw_headers['Cookie'] = $send_cookies;
				if($this->debug) {
					$this->log .= '[INF] Cookies will be SET: '.$send_cookies."\n";
				} //end if
			} //end if
			//--
		} //end if
		//--

		//-- request
		if($have_post_vars OR $have_post_files) { // post vars or post files
			//--
			if($this->debug) {
				$this->log .= '[INF] POST request'."\n";
			} //end if
			//--
			$header_form_type = '';
			$post_string = '';
			if((string)$this->poststring != '') {
				$header_form_type = 'application/x-www-form-urlencoded';
				$post_string = (string) $this->poststring; // send raw post string
			} elseif(Smart::array_size($this->postfiles) > 0) { // build multipart form data with/without extra post vars (files have anyway)
				$boundary = (string) SmartHttpUtils::http_multipart_form_delimiter();
				$header_form_type = 'multipart/form-data; boundary='.$boundary;
				$post_string = (string) SmartHttpUtils::http_multipart_form_build($boundary, $this->postvars, $this->postfiles);
			} elseif(Smart::array_size($this->postvars) > 0) { // build post string from array
				$header_form_type = 'application/x-www-form-urlencoded';
				$post_string = '';
				foreach($this->postvars as $key => $value) {
					$post_string .= (string) SmartHttpUtils::encode_var_post($key, $value); // {{{SYNC-URL-REQ-LAST-AMPERSTAND}}}
				} //end foreach
			} //end if else
			//--
			if(((string)$post_string != '') AND ((string)$header_form_type != '')) {
				if((string)$this->method == 'GET') {
					$this->method = 'POST'; // FIX: if GET Method is used instead of POST fix this ; (have post vars)
				} //end if
			} else {
				if((string)$this->method == 'POST') {
					$this->method = 'GET'; // FIX: if POST Method is used instead of GET fix this ; (have no post vars)
				} //end if
			} //end if else
			//--
			$request = $this->method.' '.$path.' HTTP/'.$this->protocol."\r\n";
			//--
			if(((string)$post_string != '') AND ((string)$header_form_type != '')) {
			//	$this->raw_headers[' Content-Type'] = 'text/html; charset=UTF-8'; // trick: to add duplicate header values the keys can be preceded by one or more spaces
				$this->raw_headers['Content-Type'] = (string) $header_form_type;
				$this->raw_headers['Content-Length'] = (int) strlen((string)$post_string);
			} //end if
			//--
		} else { // other request: HEAD / GET / PUT ...
			//--
			if((string)$this->method == 'POST') {
				$this->method = 'GET'; // FIX: if POST Method is used instead of GET fix this (have no post vars) ; this should not be fixed for other methods like: HEAD, PUT, DELETE ...
			} //end if
			//--
			if($this->debug) {
				$this->log .= '[INF] '.$this->method.' request'."\n";
			} //end if
			//--
			if((string)strtoupper($this->method) == 'PUT') {
				$this->raw_headers['Accept'] = (string) '*/*';
				if((string)$this->putbodymode == 'file') {
					if((SmartFileSysUtils::check_if_safe_path((string)$this->putbodyres) == '1') AND (SmartFileSystem::is_type_file((string)$this->putbodyres) == true) AND (SmartFileSystem::have_access_read((string)$this->putbodyres) == true)) {
						$this->put_body_len = (int) SmartFileSystem::get_file_size((string)$this->putbodyres);
						if($this->debug) {
							$this->log .= '[INF] '.$this->method.' resource file: '.(string)$this->putbodyres.' @ Length: '.$this->put_body_len."\n";
						} //end if
					} else {
						Smart::log_warning('LibHTTP // RequestFromURL // Invalid PUT Resource File (1): '.(string)$this->putbodyres.' for URL: '.$url);
						return 0;
					} //end if else
				} else { // string
					$this->putbodymode == 'string';
					$this->put_body_len = (int) strlen((string)$this->putbodyres);
					if($this->debug) {
						$this->log .= '[INF] '.$this->method.' resource string @ Length: '.$this->put_body_len."\n";
					} //end if
				} //end if else
				$this->raw_headers['Content-Length'] 	= (string) $this->put_body_len;
				$this->raw_headers['Expect'] 			= (string) '100-continue';
			} //end if
			//--
			$request = $this->method.' '.$path.' HTTP/'.$this->protocol."\r\n";
			//--
		} //end if else
		//--

		//-- check
		if(!$this->socket) {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Premature connection end (1.1)'."\n";
				Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.1) ... '.$url);
			} //end if
			return 0;
			//--
		} //end if
		//--

		//--
		if(@fwrite($this->socket, $request) === false) {
			if($this->debug) {
				$this->log .= '[ERR] Error writing Request type to socket'."\n";
				Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing Request type to socket ...');
			} //end if
			return 0;
		} //end if
		//--

		//-- raw headers
		if(!$this->socket) {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Premature connection end (1.2)'."\n";
				Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.2) ... '.$url);
			} //end if
			return 0;
			//--
		} //end if
		//--
		foreach($this->raw_headers as $key => $value) {
			if(@fwrite($this->socket, trim((string)$key).': '.$value."\r\n") === false) {
				if($this->debug) {
					$this->log .= '[ERR] Error writing Raw-Headers to socket'."\n";
					Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing Raw-Headers to socket ...');
				} //end if
				return 0;
			} //end if
		} //end foreach
		//--

		//-- end-line or blank line before post / cookies
		if(!$this->socket) {
			//--
			if($this->debug) {
				$this->log .= '[ERR] Premature connection end (1.3)'."\n";
				Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.3) ... '.$url);
			} //end if
			return 0;
			//--
		} //end if
		//--
		if(@fwrite($this->socket, "\r\n") === false) {
			if($this->debug) {
				$this->log .= '[ERR] Error writing End-Of-Line to socket'."\n";
				Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing End-Of-Line to socket ...');
			} //end if
			return 0;
		} //end if
		//--

		//--
		if($have_post_vars OR $have_post_files) {
			//--
			if(!$this->socket) {
				//--
				if($this->debug) {
					$this->log .= '[ERR] Premature connection end (1.6)'."\n";
					Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.6) ... '.$url);
				} //end if
				return 0;
				//--
			} //end if
			//--
			if(@fwrite($this->socket, $post_string."\r\n") === false) {
				if($this->debug) {
					$this->log .= '[ERR] Error writing POST data to socket'."\n";
					Smart::log_notice('LibHTTP // RequestFromURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing POST data to socket ...');
				} //end if
				return 0;
			} //end if
			//--
		} elseif((string)strtoupper($this->method) == 'PUT') { // for PUT prefered is to use HTTP 1.1 as we expect an earlier 100 Continue header to validate
			//--
			if(!$this->socket) {
				//--
				if($this->debug) {
					$this->log .= '[ERR] Premature connection end (1.7)'."\n";
					Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.7) ... '.$url);
				} //end if
				return 0;
				//--
			} //end if
			//--
			//Smart::log_notice('Path: '.$path."\n".'Method: '.$this->method);
			if((string)$this->protocol == '1.1') { // on HTTP 1.1 will get an earlier header as: HTTP/1.1 100 Continue
				if($this->put_body_len > 0) { // this comes ONLY if file or string to put is non-empty !!!
					$this->pre_header = (string) @fgets($this->socket, 4096);
					$this->pre_status = (string) trim(substr(trim($this->pre_header), 9, 3));
					//Smart::log_notice('Status: '.$this->pre_status.' @ Header: '."\n".$this->pre_header);
					if(((string)$this->pre_status == '100') AND ((string)$this->pre_header != '')) {
						while(($this->socket) && (trim($line = @fgets($this->socket, 4096)) != '') && (!feof($this->socket))) {
							$this->pre_header .= (string) $line; // this is required after 100-continue !!!
							if(!$this->socket) {
								if($this->debug) {
									$this->log .= '[ERR] Premature connection end (1.8)'."\n";
									Smart::log_notice('LibHTTP // RequestFromURL // Premature connection end (1.8) ... '.$url);
								} //end if
								return 0;
							} //end if
						} //end while
					} else {
						if($this->debug) {
							$this->log .= '[NOTICE] No 100-Continue Received from Server as Expected on HTTP 1.1 PUT Method ...'."\n";
							Smart::log_notice('LibHTTP // PutToURL 1.1 ('.$browser_protocol.$host.':'.$port.$path.') // Invalid Expect Code 100 but get back: '.$this->pre_status);
						} //end if
						return 0;
					} //end if
				} //end if
			} //end if
			//--
			if((string)$this->putbodymode == 'file') {
				//--
				if((SmartFileSysUtils::check_if_safe_path((string)$this->putbodyres) == '1') AND (SmartFileSystem::is_type_file((string)$this->putbodyres) == true) AND (SmartFileSystem::have_access_read((string)$this->putbodyres) == true)) {
					//--
					$fp = @fopen((string)$this->putbodyres, 'rb');
					//--
					while(!@feof($fp)) {
						//--
						if(@fwrite($this->socket, @fread($fp, 1024*8)) === false) {
							if($this->debug) {
								$this->log .= '[ERR] Error writing PUT data file to socket'."\n";
								Smart::log_notice('LibHTTP // PutToURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing PUT data file to socket ...');
							} //end if
							return 0;
						} //end if
						//--
					} //end while
					//--
					@fclose($fp);
					//--
				} else {
					//--
					Smart::log_warning('LibHTTP // RequestFromURL // Invalid PUT Resource File (2): '.(string)$this->putbodyres.' for URL: '.$url);
					return 0;
					//--
				} //end if else
				//--
			} else {
				//--
				$buf_start = (int) 0;
				$buf_length = (int) 1024 * 8;
				while(true) {
					//--
					if((int)($buf_start + $buf_length) > (int)$this->put_body_len) {
						$buf_length = (int) ((int)$this->put_body_len - (int)$buf_start);
						if($buf_length < 0) {
							$buf_length = 0;
						} //end if
					} //end if
					//--
					if($buf_length <= 0) {
						break; // no more chars to write ...
					} //end if
					//--
					if(@fwrite($this->socket, substr((string)$this->putbodyres, $buf_start, $buf_length)) === false) {
						if($this->debug) {
							$this->log .= '[ERR] Error writing PUT data string to socket'."\n";
							Smart::log_notice('LibHTTP // PutToURL ('.$browser_protocol.$host.':'.$port.$path.') // Error writing PUT data string to socket ...');
						} //end if
						return 0;
					} //end if
					//--
					$buf_start = (int) ((int)$buf_start + (int)$buf_length);
					//--
				} //end while
				//--
			} //end if else
			//--
		} //end if else
		//--

		//--
		return 1;
		//--

	} //END FUNCTION
	//==============================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: Smart HTTP Utils - provides utils function to work with HTTP protocol.
 *
 * <code>
 * // Usage example:
 * Smart::some_method_of_this_class(...);
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		It is recommended to use the methods in this class instead of PHP native methods whenever is possible because this class will offer Long Term Support and the methods will be supported even if the behind PHP methods can change over time, so the code would be easier to maintain.
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartHashCrypto, SmartFrameworkSecurity
 * @version 	v.20210528
 * @package 	@Core:Network
 *
 */
final class SmartHttpUtils {

	// ::

	//==============================================
	// encode a COOKIE variable ; returns the HTTP Cookie string
	public static function encode_var_cookie($name, $value) {
		//--
		$name = (string) trim((string)$name);
		//--
		if(((string)$name == '') OR (!SmartFrameworkSecurity::ValidateUrlVariableName((string)$name))) { // {{{SYNC-REQVARS-VALIDATION}}}
			return '';
		} //end if
		//--
		if(!Smart::is_nscalar($value)) {
			return '';
		} //end if
		//--
		return (string) $name.'='.rawurlencode((string)$value).';';
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	// encode a POST variable ; returns the HTTP POST String followed by an amperstand: &
	public static function encode_var_post($name, $value) {
		//--
		$name = (string) trim((string)$name);
		//--
		$out = (string) Smart::url_build_query([ (string)$name => $value ], false); // {{{SYNC-URL-REQ-LAST-AMPERSTAND}}}
		//--
		if((string)$out != '') {
			$out .= '&'; // {{{SYNC-URL-REQ-LAST-AMPERSTAND}}}
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	public static function http_multipart_form_delimiter() { // {{{SYNC-MULTIPART-BUILD}}}
		//--
		$timeduid = (string) strtolower((string)SmartHashCrypto::crc32b(microtime(true).'-'.time(), true));
		$entropy = (string) SmartHashCrypto::sha512(uniqid().'-'.microtime(true).'-'.time());
		//--
		return '_===-MForm.Part____.'.$timeduid.'_'.md5('@MFormPart---#Boundary@'.$entropy).'_P_.-=_'; // 69 chars of 70 max
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	public static function http_multipart_form_build($delimiter, $fields, $files) { // {{{SYNC-MULTIPART-BUILD}}}
		//--
		$delimiter = (string) $delimiter;
		if((strlen($delimiter) < 50) OR (strlen($delimiter) > 70)) {
			return '';
		} //end if
		//--
		if(!is_array($fields)) {
			$fields = array();
		} //end if
		//--
		if(!is_array($files)) {
			$files = array();
		} //end if
		//--
		if((Smart::array_size($fields) <= 0) AND (Smart::array_size($files) <= 0)) {
			return '';
		} //end if
		//--
		$data = '';
		//--
		foreach((array)$fields as $name => $content) {
			//--
			if(is_array($content)) {
				//--
				$flatten_arr = (array) self::flatten_form_arr((string)$name, (array)$content);
				if(Smart::array_size($flatten_arr) > 0) {
					for($i=0; $i<Smart::array_size($flatten_arr); $i++) {
						if(is_array($flatten_arr[$i])) {
							if((array_key_exists('name', (array)$flatten_arr[$i])) AND (array_key_exists('content', (array)$flatten_arr[$i]))) {
								$data .= '--'.$delimiter."\r\n";
								$data .= 'Content-Disposition: form-data; name="'.Smart::normalize_spaces((string)str_replace('"', '\\"', (string)$flatten_arr[$i]['name'])).'"'."\r\n";
								$data .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";
								$data .= 'Content-Length: '.(int)(strlen((string)$flatten_arr[$i]['content']))."\r\n";
								$data .= "\r\n".$flatten_arr[$i]['content']."\r\n";
							} //end if
						} //end if
					} //end for
				} //end if
				$flatten_arr = null; // free mem
				//--
			} else {
				//--
				$data .= '--'.$delimiter."\r\n";
				$data .= 'Content-Disposition: form-data; name="'.Smart::safe_varname($name).'"'."\r\n";
				$data .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";
				$data .= 'Content-Length: '.(int)(strlen((string)$content))."\r\n";
				$data .= "\r\n".$content."\r\n";
				//--
			} //end if else
			//--
		} //end foreach
		//--
		foreach((array)$files as $var_name => $arr_file) {
			//--
			if(Smart::array_size($arr_file) > 0) {
				//--
				$filename = (string) $arr_file['filename'];
				$content  = (string) $arr_file['content'];
				//--
				if($filename AND $content) {
					//--
					$data .= '--'.$delimiter."\r\n";
					//--
					$data .= 'Content-Disposition: form-data; name="'.Smart::safe_varname($var_name).'"; filename="'.Smart::safe_filename($filename).'"'."\r\n";
					$data .= 'Content-Transfer-Encoding: binary'."\r\n";
					$data .= 'Content-Length: '.(int)strlen((string)$content)."\r\n";
					$data .= "\r\n".$content."\r\n";
					//--
				} //end if
				//--
			} //end if
			//--
		} //end foreach
		//--
		$data .= '--'.$delimiter.'--'."\r\n";
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	/**
	 * Dechunk a HTTP 'transfer-encoding: chunked' part (Ex: Body)
	 * If The Chunked Part was not encoded properly it will be returned it unmodified.
	 *
	 * @param string $chunk the encoded message
	 * @return string the decoded message.
	 */
	public static function chunked_part_decode($chunk) {
		//--
		$chunk = (string) trim((string)$chunk);
		if((string)$chunk == '') {
			return (string) $chunk;
		} //end if
		//--
		$pos = 0;
		$len = (int) strlen((string)$chunk);
		//--
		$newlineAt = strpos((string)$chunk, "\n", ((int)$pos + 1)); // return mixed: int / false
		if($newlineAt === false) {
			return (string) $chunk; // failed, chunked content is broken
		} //end if
		//--
		$dechunk = '';
		while(($pos < $len) && ($chunkLenHex = (string)substr((string)$chunk, (int)$pos, ((int)$newlineAt - (int)$pos)))) {
			//--
			if($newlineAt === false) {
				return (string) $chunk; // failed, chunked content is broken
			} //end if
			//--
			if(self::is_hex((string)$chunkLenHex) !== true) {
				return (string) $chunk; // failed, chunked content is broken
			} //end if
			//--
			$pos = (int) ((int)$newlineAt + 1);
			$chunkLen = (int) hexdec((string)rtrim((string)$chunkLenHex, "\r\n"));
			$dechunk .= (string) substr((string)$chunk, (int)$pos, (int)$chunkLen);
			//--
			$pos = strpos((string)$chunk, "\n", (int)((int)$pos + (int)$chunkLen)); // return mixed: int / false
			if($pos === false) {
				return (string) $chunk; // failed, chunked content is broken
			} //end if
			$pos = (int) ((int)$pos + 1);
			//--
			$newlineAt = strpos((string)$chunk, "\n", ((int)$pos + 1)); // return mixed: int / false
			//--
		} //end while
		//--
		return (string) $dechunk;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	// return non-associative array as [ 'name[key][0][skey]' => 'string-value' ]
	private static function flatten_form_arr(string $key, array $data, array $result=[]) {
		//--
		foreach($data as $skey => $value) {
			$skey = (string) $key.'['.$skey.']';
			if(is_array($value)) {
				$result = (array) self::flatten_form_arr((string)$skey, (array)$value, (array)$result);
			} else {
				$result[] = [ 'name' => (string) $skey, 'content' => (string) $value ];
			} //end if else
		} //end foreach
		//--
		return (array) $result;
		//--
	} //END FUNCTION
	//==============================================


	//==============================================
	/**
	 * determine if a string can represent a number in hexadecimal
	 *
	 * @param string $hex
	 * @return boolean true if the string is a hex, otherwise false
	 */
	private static function is_hex($hex) {
		//--
		$hex = (string) strtolower((string)trim((string)ltrim((string)$hex, '0')));
		if(empty($hex)) {
			$hex = 0;
		} //end if
		//--
		$dec = (int) hexdec($hex);
		//--
		return (bool) ((string)$hex == (string)dechex((int)$dec));
		//--
	} //END FUNCTION
	//==============================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
