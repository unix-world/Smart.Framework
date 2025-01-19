<?php
// [LIB - Smart.Framework / Plugins / Mail Get (IMAP4 and POP3 Client)]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Mail Get (SSL/TLS): IMAP4 / POP3
// DEPENDS:
//	* Smart::
//	* SmartUnicode::
//======================================================

// [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== START CLASS
//=====================================================================================


/**
 * Class: SmartMailerImap4Client - provides an IMAP4 Mail Client with SSL/TLS support.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		After each operation on the IMAP4 Server should check the $imap4->error string and if non-empty stop and disconnect to free the socket
 *
 * @depends 	classes: Smart, SmartUnicode
 * @version 	v.20250107
 * @package 	Plugins:Mailer
 *
 */
final class SmartMailerImap4Client {

	// ->


	/**
	 * @var INT
	 * @default 2048
	 * socket read buffer
	 */
	public $buffer = 2048;

	/**
	 * @var INT
	 * @default 30
	 * socket timeout in seconds
	 */
	public $timeout = 30;

	/**
	 * @var BOOLEAN
	 * @default FALSE
	 * debug on/off
	 */
	public $debug = false;

	/**
	 * @var STRING
	 * @default ''
	 * This should be checked after each operation on the server
	 * The error message(s) will be collected here
	 * do not SET a value here, but just GET the result
	 */
	public $error = '';

	/**
	 * @var STRING
	 * @default ''
	 * The operations log (only if debug is enabled)
	 * Do not SET a value here, but just GET the result
	 */
	public $log = '';

	//--
	private $socket = false; 	// socket resource ID
	private $tag = '';			// unique ID Tag
	private $username = '';		// the username
	private $authmec = ''; 		// the auth mechanism
	//--
	private $crr_mbox = '';		// current selected mailbox
	private $crr_uiv = 0;		// current UIVALIDITY for the selected mailbox folder
	private $inf_count = 0;		// store mailbox count
	private $inf_recent = 0;	// store mailbox recent number
	private $inf_size = 0;		// store mailbox total size
	//--
	private $cafile = '';		// Certificate Authority File (instead of using the global SMART_FRAMEWORK_SSL_CA_FILE can use a private cafile
	//--

	//--
	private $is_connected_and_logged_in = false;
	private $selected_box = '';
	//--


	//=====================================================================================
	/**
	 * IMAP4 Client Class constructor
	 */
	public function __construct($buffer=0) { // IMAP4
		//--
		$this->socket = false;
		$this->tag = '';
		$this->crr_mbox = '';
		//--
		$this->username = '';
		$this->authmec = '';
		//--
		if($buffer > 0) {
			$this->buffer = (int) $buffer;
		} //end if
		if($this->buffer < 512) {
			$this->buffer = 512;
		} elseif($this->buffer > 8192) {
			$this->buffer = 8192;
		} //end if else
		//--
		$this->log = '';
		$this->error = '';
		//--
		$this->is_connected_and_logged_in = false;
		$this->selected_box = '';
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Connected + Logged In Status
	 */
	public function is_connected_and_logged_in() {
		//--
		if(($this->is_connected_and_logged_in === true) AND (is_resource($this->socket))) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Selected MailBox (on IMAP4 will be available just after connect + login + select mailbox)
	 */
	public function get_selected_mailbox() {
		//--
		if($this->is_connected_and_logged_in() !== true) {
			return '';
		} //end if
		//--
		return (string) $this->selected_box;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Clear the Last Error
	 */
	public function clear_last_error() {
		//--
		$this->error = '';
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Set a SSL/TLS Certificate Authority File
	 * If not set but SMART_FRAMEWORK_SSL_CA_FILE is defined will use the SMART_FRAMEWORK_SSL_CA_FILE
	 * @param STRING $cafile Relative Path to a SSL Certificate Authority File (Ex: store within smart-framework/etc/certificates ; specify as 'etc/certificates/ca.pem') ; IMPORTANT: in this case the 'etc/certificates/' directory must be protected with a .htaccess to avoid being public readable - the directory and any files within this directory ...)
	 * @return VOID
	 */
	public function set_ssl_tls_ca_file($cafile) {
		//--
		$this->cafile = '';
		if(SmartFileSysUtils::checkIfSafePath((string)$cafile) == '1') {
			if(SmartFileSysUtils::staticFileExists((string)$cafile)) {
				$this->cafile = (string) $cafile;
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Will try to open a socket to the specified IMAP4 Server using the host/ip and port ; If a SSL option is selected will try to establish a SSL socket or fail
	 * @param STRING $server The Server Hostname or IP address
	 * @param INTEGER+ $port *Optional* The Server Port ; Default is: 143
	 * @param ENUM $sslversion To connect using SSL mode this must be set to any of these accepted values: '', 'starttls', 'starttls:1.0', 'starttls:1.1', 'starttls:1.2', 'tls', 'tls:1.0', 'tls:1.1', 'tls:1.2', 'ssl', 'sslv3' ; If empty string is set here it will be operate in unsecure mode (NOT using any SSL/TLS Mode)
	 * @return INTEGER+ 1 on success, 0 on fail
	 */
	public function connect($server, $port=143, $sslversion='') { // IMAP4

		//-- inits
		$this->socket = false;
		$this->tag = 'smart77'.strtolower((string)Smart::uuid_10_seq()).'7framework';
		$this->crr_mbox = '';
		//--

		//-- checks
		$server = (string) trim((string)$server);
		if((strlen((string)$server) <= 0) OR (strlen((string)$server) > 255)) {
			$this->error = '[ERR] Invalid Server to Connect ! ['.$server.']';
			return 0;
		} //end if
		//--
		$port = (int) $port;
		if(($port <= 0) OR ($port > 65535)) {
			$this->error = '[ERR] Invalid Port to Connect ! ['.$port.']';
			return 0;
		} //end if
		//--

		//--
		$protocol = '';
		$start_tls = false;
		$start_tls_version = null;
		//--
		$is_secure = false;
		if((string)$sslversion != '') {
			//--
			if(!function_exists('openssl_open')) {
				$this->error = '[ERR] PHP OpenSSL Extension is required to perform SSL requests !';
				return 0;
			} //end if
			//--
			$is_secure = true;
			switch((string)strtolower((string)$sslversion)) {
				case 'starttls':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLS_CLIENT; // since PHP 5.6.7, STREAM_CRYPTO_METHOD_TLS_CLIENT (same for _SERVER) no longer means any tls version but tls 1.0 only (for "backward compatibility"...)
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.0':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.1':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.2':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.3':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				//--
				case 'ssl':
					$protocol = 'ssl://'; // deprecated
					break;
				case 'sslv3':
					$protocol = 'sslv3://'; // deprecated
					break;
				//--
				case 'tls:1.0':
					$protocol = 'tlsv1.0://';
					break;
				case 'tls:1.1':
					$protocol = 'tlsv1.1://';
					break;
				case 'tls:1.2':
					$protocol = 'tlsv1.2://';
					break;
				case 'tls:1.3':
					$protocol = 'tlsv1.3://';
					break;
				case 'tls':
				default:
					$protocol = 'tls://';
			} //end switch
			//--
		} //end if else
		//--

		//--
		if($this->debug) {
			$this->log .= '[INF] '.($is_secure === true ? 'SECURE ' : '').($start_tls === true ? '(STARTTLS:'.$start_tls_version.') ' : '').'Connecting to IMAP4 Mail Server: '.$protocol.$server.':'.$port."\n";
		} //end if
		//--

		//--
		//$sock = @fsockopen($protocol.$server, $port, $errno, $errstr, $this->timeout);
		$stream_context = @stream_context_create();
		if(((string)$protocol != '') OR ($start_tls === true)) {
			//--
			$cafile = '';
			if((string)$this->cafile != '') {
				$cafile = (string) $this->cafile;
			} elseif(defined('SMART_FRAMEWORK_SSL_CA_FILE')) {
				if((string)SMART_FRAMEWORK_SSL_CA_FILE != '') {
					$cafile = (string) SMART_FRAMEWORK_SSL_CA_FILE;
				} //end if
			} //end if
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
		//--
		if(!$this->debug) {
			Smart::disableErrLog(); // skip log, except debug, IMAP4 connection errors
		} //end if
		$sock = @stream_socket_client($protocol.$server.':'.$port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $stream_context);
		if(!$this->debug) {
			Smart::restoreErrLog(); // restore the original log handlers
		} //end if
		//--
		if(!is_resource($sock)) {
			$this->error = '[ERR] Could not open connection. Error: '.$errno.' :: '.$errstr;
			return 0;
		} //end if
		//--
		$this->socket = $sock;
		unset($sock);
		//--
		@stream_set_timeout($this->socket, (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT);
		if($this->debug) {
			$this->log .= '[INF] Set Socket Stream TimeOut to: '.SMART_FRAMEWORK_NETSOCKET_TIMEOUT.' ; ReadBuffer = '.$this->buffer."\n";
		} //end if
		//--

		//-- avoid connect normally if SSL/TLS was explicit required
		$chk_crypto = (array) @stream_get_meta_data($this->socket);
		if((string)$protocol != '') {
			if(!SmartUnicode::str_icontains($chk_crypto['stream_type'], '/ssl')) { // will return something like: tcp_socket/ssl
				$this->error = '[ERR] Connection CRYPTO CHECK Failed ...'."\n";
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
		} //end if
		//--

		//--
		$reply = $this->get_answer_line();
		$reply = $this->strip_clf($reply);
		//--
		if((string)substr((string)$reply, 0, 5) != '* OK ') {
			$this->error = '[ERR] Server Reply is NOT OK // '.$test.' // '.$reply;
			@fclose($this->socket);
			$this->socket = false;
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[REPLY] \''.$reply.'\''."\n";
		} //end if
		//--

		//--
		if($start_tls === true) {
			//--
			if($this->debug) {
				$this->log .= '[INF] StartTLS on Server'."\n";
			} //end if
			//--
			$reply = $this->send_cmd('STARTTLS');
			if((string)$this->error != '') {
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] Server StartTLS Failed :: '.$test.' // '.$reply;
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
			if(!$start_tls_version) {
				$this->error = '[ERR] Server StartTLS Invalid Protocol Selected ...';
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
			if($this->debug) {
				$this->log .= '[INF] Start TLS negotiation on Server'."\n";
			} //end if
			$test_starttls = @stream_socket_enable_crypto($this->socket, true, $start_tls_version);
			if(!$test_starttls) {
				$this->error = '[ERR] Server StartTLS Failed to be Enabled on Socket ...';
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
		} //end if
		//--

		//--
		return 1;
		//--

	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Ping the IMAP4 Server
	 * Sends the command NOOP to the Server
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function noop() { // IMAP4
		//--
		if($this->debug) {
			$this->log .= '[INF] Ping the Mail Server // NOOP'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$reply = $this->send_cmd('NOOP');
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] Server Noop Failed :: '.$test.' // '.$reply;
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Reset the IMAP4 server connection (includding all messages marked to be deleted)
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function reset() { // IMAP4
		//--
		if($this->debug) {
			$this->log .= '[INF] Reset the Connection to Mail Server'."\n";
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Sends the CLOSE/UNSELECT/LOGOUT commands set to the IMAP4 server
	 * Closes the communication socket after sending LOGOUT command
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function quit($expunge=false) { // IMAP4
		//--
		$was_connected_and_logged_in = (bool) $this->is_connected_and_logged_in;
		$last_selected_box = (string) trim((string)$this->selected_box);
		//--
		$this->selected_box = '';
		$this->is_connected_and_logged_in = false; // must be at the top of this function
		//--
		if($this->debug) {
			$this->log .= '[INF] Sending QUIT to Mail Server !'."\n";
		} //end if
		//--
		if(!$this->socket) {
			return 0;
		} //end if
		//--
		if(($was_connected_and_logged_in === true) AND ((string)$last_selected_box != '')) {
			//--
			if($expunge === true) {
				$this->send_cmd('EXPUNGE'); // delete messages marked as Deleted (overall)
			} else {
				$this->send_cmd('CLOSE'); // delete messages marked as Deleted (from selected mailbox only)
			} //end if else
			//--
			$this->send_cmd('UNSELECT'); // some servers req. this (dovecot to avoid throw CLIENTBUG warnings)
			//--
		} //end if
		//--
		$reply = $this->send_cmd('LOGOUT'); // imap4
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Logout Failed ['.$reply.']';
		} //end if else
		//--
		@fclose($this->socket);
		$this->socket = false;
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Try to Login/Authenticate on the IMAP4 Server with a username and password (or token for auth:xoauth2)
	 * Sends both user and pass to the Server
	 * @param STRING $username The authentication username
	 * @param STRING $pass The authentication password
	 * @param ENUM $mode *Optional* The authentication mode ; can be set to any of: 'login', 'auth:plain', 'auth:cram-md5', 'auth:xoauth2' ; Default is 'auth:plain'
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function login($username, $pass, $mode='') { // IMAP4
		//--
		$username = (string) Smart::normalize_spaces(SmartUnicode::utf8_to_iso($username)); // {{{SYNC-MAILGET-SAFE-FIX-NAMES}}}
		$pass = (string) Smart::normalize_spaces(SmartUnicode::utf8_to_iso($pass)); // {{{SYNC-MAILGET-SAFE-FIX-NAMES}}}
		//--
		if((string)trim((string)$username) == '') {
			$this->error = '[ERR] IMAP4 Login Username is Empty';
			return 0;
		} //end if
		if((string)trim((string)$pass) == '') {
			$this->error = '[ERR] IMAP4 Login Password is Empty';
			return 0;
		} //end if
		//--
		$this->username = (string) $username;
		//--
		$this->authmec = '';
		$mode = (string) strtolower((string)trim((string)$mode));
		if((string)$mode == '') {
			$mode = 'auth:plain'; // the default auth mode
		} //end if
		switch((string)$mode) {
			case 'login':
				$this->authmec = '';
				break;
			case 'auth:xoauth2':
				$this->authmec = 'XOAUTH2';
				break;
			case 'auth:cram-md5':
				$this->authmec = 'CRAM-MD5';
				break;
			case 'auth:plain':
				$this->authmec = 'PLAIN';
				break;
			default:
				$this->error = '[ERR] IMAP4 Invalid Auth/Login Mode: '.$mode;
				return 0;
		} //end switch
		if($this->debug) {
			$this->log .= '[INF] Login to Mail Server (TAG='.$this->tag.' ; MODE='.$mode.' ; USER='.$username.')'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$this->send_cmd('CAPABILITY');
		//--
		if((string)$this->authmec == '') { // login
			if($this->debug) {
				$this->log .= '[INF] Login Method: LOGIN { UNSECURE over non-encrypted connections }'."\n";
			} //end if
			$reply = $this->send_cmd('LOGIN '.$username.' '.$pass);
		} elseif((string)$this->authmec == 'PLAIN') { // auth:plain {{{SYNC-AUTH:PLAIN-METHOD}}}
			if($this->debug) {
				$this->log .= '[INF] Login Method: AUTHENTICATE / '.$this->authmec.' (DEFAULT) { UNSECURE over non-encrypted connections }'."\n";
			} //end if
			$reply = $this->send_cmd('AUTHENTICATE '.$this->authmec.' '.(string)Smart::b64_enc((string)"\0".$username."\0".$pass));
		} elseif((string)$this->authmec == 'CRAM-MD5') { // auth:cram-md5 {{{SYNC-AUTH:CRAM-MD5-METHOD}}}
			if($this->debug) {
				$this->log .= '[INF] Login Method: AUTHENTICATE / CRAM-MD5 { LESS SECURE ; if an encrypted connection is available is better to use PLAIN instead of CRAM-MD5 }'."\n";
			} //end if
			$secret = $this->send_cmd('AUTHENTICATE '.$this->authmec, false, true); // special command with get only line (req. to avoid freeze)
			$secret = (string) trim((string)$secret);
			if((string)substr((string)$secret, 0, 2) !== '+ ') {
				$this->error = '[ERR] IMAP4 Login: CRAM-MD5 Secret is WRONG ['.$secret.']';
				return 0;
			} //end if
			$secret = (string) trim((string)substr((string)$secret, 2));
			if((string)$secret == '') {
				$this->error = '[ERR] IMAP4 Login: CRAM-MD5 Secret is EMPTY';
				return 0;
			} //end if
			$secret = (string) Smart::b64_dec((string)$secret);
			if((string)trim((string)$secret) == '') {
				$this->error = '[ERR] IMAP4 Login: CRAM-MD5 Secret is INVALID';
				return 0;
			} //end if
			$digest = (string) hash_hmac('md5', (string)$secret, (string)$pass);
			$reply = $this->send_cmd((string)Smart::b64_enc((string)$username.' '.$digest), true); // raw command with no banner tag
		} elseif((string)$this->authmec == 'XOAUTH2') { // auth:xoauth2 {{{SYNC-AUTH:XOAUTH2-METHOD}}}
			if($this->debug) {
				$this->log .= '[INF] Login Method: AUTHENTICATE / XOAUTH2'."\n";
			} //end if
			$reply = $this->send_cmd('AUTHENTICATE '.$this->authmec.' '.(string)Smart::b64_enc((string)'user='.$username."\1".'auth=Bearer '.$pass."\1"."\1"), false, true); // special command with get only line (req. to avoid freeze) ; sometimes return a standard NOT OK answer, othertimes return an answer that start with +SPACE as B64 encoded json which can trap the client ... ; "\1" is ^A
		} else { // others, invalid or not supported
			$this->error = '[ERR] IMAP4 Invalid Auth/Login Mechanism: '.$this->authmec;
			return 0;
		} //end if else
		if((string)$this->error != '') {
			return 0;
		} //end if
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Login: User or Password Failed :: `'.str_replace((string)$this->tag, '#', (string)$reply).'`';
			return 0;
		} //end if
		//--
		$this->is_connected_and_logged_in = true;
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Selects a MailBox after login on the IMAP4 Server
	 * Unlike the POP3 servers the IMAP4 servers can operate on multiple MailBoxes over the same account
	 * @param STRING $mbox_name The MailBox to Select ; Ex: 'Inbox' or 'Sent' or 'Trash' or 'Spam' or ... 'Sent Items', ...
	 * @param BOOLEAN $allow_create If MailBox does not exists and this param is set to TRUE then MailBox will be created
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function select_mailbox($mbox_name, $allow_create=false) { // this is just for IMAP4, n/a for POP3
		//--
		$this->inf_count = 0;
		$this->inf_recent = 0;
		$this->inf_size = 0;
		//--
		if($this->debug) {
			$this->log .= '[INF] Select MailBox // '.$mbox_name."\n";
		} //end if
		if((string)trim((string)$mbox_name) == '') {
			$this->error = '[ERR] Select MailBox: Empty MailBox Name';
			return 0;
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		if($allow_create) {
			$this->send_cmd('CREATE "'.$this->mailbox_escape($mbox_name).'"'); // we do not check error now but on the next command
		} //end if
		//--
		$reply = $this->send_cmd('SELECT "'.$this->mailbox_escape($mbox_name).'"');
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] Select MailBox ('.$mbox_name.') Failed :: '.$test.' // '.$reply;
			return 0;
		} //end if
		//--
		$this->crr_mbox = (string) $mbox_name;
		//--
		$tmp_arr = (array) explode('* OK [UIDVALIDITY ', (string)$reply);
		if(!array_key_exists(0, $tmp_arr)) {
			$tmp_arr[0] = null;
		} //end if
		if(!array_key_exists(1, $tmp_arr)) {
			$tmp_arr[1] = null;
		} //end if
		//--
		$tmp_uiv = (string) trim((string)$tmp_arr[1]);
		if((string)$tmp_uiv == '') {
			$this->error = '[ERR] Select MailBox ('.$mbox_name.') Failed :: Cannot Determine the UIDVALIDITY';
			return 0;
		} //end if
		$tmp_arr = array();
		//--
		$tmp_arr = (array) explode('] UIDs', (string)$tmp_uiv);
		if(!array_key_exists(0, $tmp_arr)) {
			$tmp_arr[0] = null;
		} //end if
		//--
		$this->crr_uiv = (string) trim((string)$tmp_arr[0]);
		$tmp_arr = array();
		//--
		$count = -1;
		$recent = -1;
		$tmp_arr = (array) explode("\r\n", (string)$reply);
		for($i=0; $i<count($tmp_arr); $i++) {
			$tmp_arr[$i] = (string) trim((string)$tmp_arr[$i]);
			if((string)$tmp_arr[$i] != '') {
				if(($count < 0) AND (strpos((string)$tmp_arr[$i], ' EXISTS') !== false) AND ((string)substr((string)$tmp_arr[$i], 0, 2) == '* ')) {
					$tmp_x_arr = array();
					$tmp_txt = $tmp_arr[$i];
					$tmp_x_arr = (array) explode('* ', (string)$tmp_txt);
					if(!array_key_exists(0, $tmp_x_arr)) {
						$tmp_x_arr[0] = null;
					} //end if
					if(!array_key_exists(1, $tmp_x_arr)) {
						$tmp_x_arr[1] = null;
					} //end if
					$tmp_txt = (string) trim((string)$tmp_x_arr[1]);
					$tmp_x_arr = array();
					$tmp_x_arr = (array) explode(' EXISTS', (string)$tmp_txt);
					if(!array_key_exists(0, $tmp_x_arr)) {
						$tmp_x_arr[0] = null;
					} //end if
					$tmp_txt = (string) trim((string)$tmp_x_arr[0]);
					$count = (int) $tmp_txt;
					if((int)$count < 0) {
						$count = -1; // invalid
					} //end if
				} elseif(($recent < 0) AND (strpos((string)$tmp_arr[$i], ' RECENT') !== false) AND ((string)substr((string)$tmp_arr[$i], 0, 2) == '* ')) {
					$tmp_x_arr = array();
					$tmp_txt = $tmp_arr[$i];
					$tmp_x_arr = (array) explode('* ', (string)$tmp_txt);
					if(!array_key_exists(0, $tmp_x_arr)) {
						$tmp_x_arr[0] = null;
					} //end if
					if(!array_key_exists(1, $tmp_x_arr)) {
						$tmp_x_arr[1] = null;
					} //end if
					$tmp_txt = (string) trim((string)$tmp_x_arr[1]);
					$tmp_x_arr = array();
					$tmp_x_arr = (array) explode(' RECENT', (string)$tmp_txt);
					if(!array_key_exists(0, $tmp_x_arr)) {
						$tmp_x_arr[0] = null;
					} //end if
					$tmp_txt = (string) trim((string)$tmp_x_arr[0]);
					$recent = (int) $tmp_txt;
					if((int)$recent < 0) {
						$recent = -1; // invalid
					} //end if
				} //end if
			} //end if
		} //end for
		$tmp_arr = array();
		//--
		$size = -1; // we can't determine in IMAP except situation below if the server have STATUS=SIZE Extension
		$reply = $this->send_cmd('STATUS "'.$this->mailbox_escape($mbox_name).'" (MESSAGES UIDNEXT SIZE)'); // example: '* STATUS Inbox (MESSAGES 8 UIDNEXT 12345 SIZE 45678)';
		$test = $this->is_ok($reply);
		if((string)$test == 'ok') {
			//--
			$tmp_arr = (array) explode(' (MESSAGES ', (string)$reply);
			if(!array_key_exists(0, $tmp_arr)) {
				$tmp_arr[0] = null;
			} //end if
			if(!array_key_exists(1, $tmp_arr)) {
				$tmp_arr[1] = null;
			} //end if
			//--
			$tmp_arr = (string) trim((string)$tmp_arr[1]);
			//--
			$tmp_arr = (array) explode(' SIZE ', (string)$tmp_arr);
			if(!array_key_exists(0, $tmp_arr)) {
				$tmp_arr[0] = null;
			} //end if
			if(!array_key_exists(1, $tmp_arr)) {
				$tmp_arr[1] = null;
			} //end if
			//--
			if((int)$count < 0) { // bug fix: if could not get count from above, try here
				$count = (int) trim((string)$tmp_arr[0]);
				if((int)$count < 0) {
					$count = -1; // invalid
				} //end if
			} //end if
			//--
			$size = (int) trim((string)rtrim((string)$tmp_arr[1], ')'));
			if((int)$size < 0) {
				$size = -1; // invalid
			} //end if
			//--
			$tmp_arr = array();
			//--
		} //end if
		//--
		if((int)$count < 0) {
			$count = 0;
		} //end if
		if((int)$recent < 0) {
			$recent = 0;
		} //end if
		if((int)$size < 0) {
			$size = 0;
		} //end if
		//--
		$this->inf_count = (int) $count;
		$this->inf_recent = (int) $recent;
		$this->inf_size = (int) $size; // imap size is not reported except if the server have STATUS=SIZE Extension
		//--
		$this->send_cmd('CHECK'); // maintenance over mailbox
		if((string)$this->error != '') {
			$this->error = '[ERR] Select MailBox ('.$mbox_name.') Failed :: Errors for the CHECK command ...';
			return 0;
		} //end if
		//--
		$this->selected_box = (string) $mbox_name;
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get all the IMAP4 MetaData for the selected account MailBox
	 * @return ARRAY Which contains the following keys [ 'uivalidity' => 'CurrentUIV', 'count' => 101, 'recent' => '', 'size' => 1024 ]
	 */
	public function get_metadata() { // IMAP4
		//--
		return array(
			'uivalidity' 	=> $this->crr_uiv,
			'count' 		=> $this->inf_count,
			'recent' 		=> $this->inf_recent,
			'size' 			=> $this->inf_size
		);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Count Information for the selected account MailBox
	 * @return ARRAY Which contains the following keys [ 'size' => 1024, 'count' => 101, 'recent' => '' ]
	 */
	public function count() { // IMAP4
		//--
		if((string)$this->crr_mbox == '') {
			$this->error = '[ERR] IMAP4 Count // No MailBox Selected ...';
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] Reading the Messages Count and Size for MailBox ('.$this->crr_mbox.') ...'."\n";
		} //end if
		//--
		$size = $this->inf_size;
		$count = $this->inf_count;
		$recent = $this->inf_recent;
		//--
		if($this->debug) {
			$this->log .= '[INF] Messages Count [Size='.$size.' ; Count='.$count.' ; Recent='.$recent.']'."\n";
		} //end if
		//--
		return array('size' => $size, 'count' => $count, 'recent' => $recent);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the list of UIDs or a specific UID for the selected message
	 * The UID(s) will be formated as this library does: 'IMAP4-UIV-@num@-UID-@uid@'
	 * A list with multiple UIDs provided by this method can be parsed using $imap4->parse_uidls($list)
	 * @param STRING $msg_num *Optional* A specific UID for a specific message or leave empty to get the list with all UIDs
	 * @return STRING A unique UID or a list with multiple (ar all) UIDs ; returns empty string on error
	 */
	public function uid($msg_num='') { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 UID // No MailBox Selected ...';
		} //end if
		//--
		if($this->debug) {
			if((string)trim((string)$msg_num) != '') {
				$this->log .= '[INF] IMAP4 UID Message Number: '.$msg_num."\n";
			} else {
				$this->log .= '[INF] IMAP4 UID for all Messages'."\n";
			} //end if else
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if((string)trim((string)$msg_num) != '') {
			if($this->is_safe_uid_or_msgnum($msg_num) !== true) { // must check UID to avoid inject search expressions in this function as IMAP Search must escape the search expressions to avoid security issues ; Ex: UID SEARCH HEADER Message-Id <abc>
				$this->error = '[ERR] IMAP4 UID Message Failed [Invalid Characters in UID: '.$msg_num.']';
				return '';
			} //end if
			$reply = $this->send_cmd('UID SEARCH '.$msg_num);
		} else {
			$reply = $this->send_cmd('UID SEARCH ALL');
		} //end if else
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 UID Message(s) Failed ['.$reply.']';
			return '';
		} //end if
		//--
		if((string)trim((string)$msg_num) != '') {
			//--
			$uid = '';
			$tmp_arr = (array) explode("\r\n", (string)$reply);
			for($i=0; $i<count($tmp_arr); $i++) {
				$tmp_line = trim($tmp_arr[$i]);
				if((string)substr((string)$tmp_line, 0, 9) == '* SEARCH ') {
					$uid = 'IMAP4-UIV-'.$this->crr_uiv.'-UID-'.trim((string)substr((string)$tmp_line, 9)); // {{{SYNC-IMAP4-SAFE-UIDS}}}
					break;
				} //end if
			} //end for
			//--
		} else {
			//--
			$uid = '';
			$tmp_arr = (array) explode("\r\n", (string)$reply);
			for($i=0; $i<count($tmp_arr); $i++) {
				$tmp_line = (string) trim((string)$tmp_arr[$i]);
				if((string)substr((string)$tmp_line, 0, 9) == '* SEARCH ') {
					$uid = (string) trim((string)substr((string)$tmp_line, 9));
				} //end if
			} //end for
			//--
			if((string)trim((string)$uid) != '') {
				$tmp_arr = (array) explode(' ', (string)$uid);
				$uid = '';
				for($i=0; $i<count($tmp_arr); $i++) {
					$tmp_line = (string) trim((string)$tmp_arr[$i]);
					if((string)$tmp_line != '') {
						$uid .= (string) $i.' '.'IMAP4-UIV-'.$this->crr_uiv.'-UID-'.trim((string)$tmp_line)."\n"; // {{{SYNC-IMAP4-SAFE-UIDS}}} ; keep sync with POP3 format which is: ID[SPACE]UID\n
					} //end if
				} //end for
			} else {
				$tmp_arr = array();
			} //end if
			//--
		} //end if
		//--
		if($this->debug) {
			if((string)trim((string)$msg_num) != '') {
				$this->log .= '[INF] UID For Message #'.$msg_num.' is: ['.$uid.']'."\n";
			} else {
				$this->log .= '[INF] UID For Messages are: [(LIST)]'."\n";
			} //end if else
		} //end if
		//--
		return (string) $uid;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Parse the (standard) list of UIDs supplied IMAP4 protocol
	 * The UIDs will be formated as this library does: 'IMAP4-UIV-@num@-UID-@uid@'
	 * @hints Some IMAP4 servers have non-standard responses for the format of $mailget->uid() and in this case you may have to build your own parser ...
	 * @param STRING $y_list The UIDs list given by the IMAP4 server with command: $mailget->uid() ; Expects a format similar with: ID[SPACE]UID\n
	 * @return ASSOCIATIVE ARRAY the list of parsed UIDs as [ id1 => uid1, id2 => uid2, ..., idN => uidN ]
	 */
	public function parse_uidls($y_list) { // this is just for IMAP4, n/a for POP3

		//--
		$y_list = (string) trim((string)str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), (string)$y_list));
		//--
		$uidls = (array) explode("\n", (string)$y_list);
		//--

		//--
		$new_uidls = array();
		//--
		if(Smart::array_size($uidls) > 0) {
			//--
			foreach($uidls as $key => $val) {
				//--
				$val = (string) trim((string)$val);
				//--
				$tmp_arr = array();
				//--
				if((string)$val != '') {
					//--
					$tmp_arr = (array) explode(' ', (string)$val);
					if(!array_key_exists(0, $tmp_arr)) {
						$tmp_arr[0] = null;
					} //end if
					if(!array_key_exists(1, $tmp_arr)) {
						$tmp_arr[1] = null;
					} //end if
					$tmp_arr[0] = (string) trim((string)$tmp_arr[0]);
					$tmp_arr[1] = (string) trim((string)$tmp_arr[1]);
					//--
					if(preg_match('/^([0-9])+$/', (string)$tmp_arr[0])) { // message ID, always numeric
						if($this->is_safe_uid_or_msgnum((string)$tmp_arr[1]) === true) { // message UID ; numeric, but can be a hash
							$new_uidls[(string)$tmp_arr[0]] = (string) $tmp_arr[1]; // arr[id] = uid
						} //end if
					} //end if
					//--
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--
		$uidls = array();
		//--

		//--
		return (array) $new_uidls;
		//--

	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the size for the selected message
	 * @hints Some servers may not comply with this method and may return empty result
	 * @param STRING $msg_num The Message Number
	 * @param BOOLEAN $by_uid *Optional* ; Default is FALSE ; If TRUE a Message UID should be supplied for 1st param ; If FALSE a Message Number should be supplied for 1st param
	 * @return STRING the size of the selected message (ussualy in Bytes) or empty string if Error
	 */
	public function size($msg_num, $by_uid=false) { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Size // No MailBox Selected ...';
		} //end if
		if($this->is_safe_uid_or_msgnum($msg_num) !== true) {
			$this->error = '[ERR] IMAP4 Size // Empty or Invalid Message NUM / UID provided ...';
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] IMAP4 Size Message Number: '.$msg_num."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if($by_uid) {
			$reply = $this->send_cmd('UID FETCH '.$msg_num.' RFC822.SIZE');
		} else {
			$reply = $this->send_cmd('FETCH '.$msg_num.' RFC822.SIZE');
		} //end if else
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Size Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$tmp_arr = (array) explode('RFC822.SIZE ', (string)$reply); // The answer is like * {MSGNUM} FETCH (RFC822.SIZE 3663)
		$tmp_size = (string) trim((string)(isset($tmp_arr[1]) ? $tmp_arr[1] : ''));
		$tmp_arr = array();
		$tmp_arr = (array) explode(')', (string)$tmp_size);
		$size = (string) trim((string)(isset($tmp_arr[0]) ? $tmp_arr[0] : ''));
		//--
		if($this->debug) {
			$this->log .= '[INF] Size For Message #'.$msg_num.' is: ['.$size.']'."\n";
		} //end if
		//--
		return (string) $size;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Delete a message stored on the IMAP4 Server
	 * @param STRING $msg_num The Message Number or Message UID if 2nd param is set to TRUE
	 * @param BOOLEAN $by_uid *Optional* ; Default is FALSE ; If TRUE a Message UID should be supplied for 1st param ; If FALSE a Message Number should be supplied for 1st param
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function delete($msg_num, $by_uid=false) { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Delete // No MailBox Selected ...';
		} //end if
		if($this->is_safe_uid_or_msgnum($msg_num) !== true) {
			$this->error = '[ERR] IMAP4 Delete // Empty or Invalid Message NUM / UID provided ...';
		} //end if
		//--
		if($this->debug) {
			if($by_uid) {
				$this->log .= '[INF] IMAP4 Delete Message UID: '.$msg_num."\n";
			} else {
				$this->log .= '[INF] IMAP4 Delete Message Number: '.$msg_num."\n";
			} //end if else
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		if($by_uid) {
			$reply = $this->send_cmd('UID STORE '.$msg_num.' +FLAGS.SILENT (\Deleted)');
		} else {
			$reply = $this->send_cmd('STORE '.$msg_num.' +FLAGS.SILENT (\Deleted)');
		} //end if else
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Delete Message Failed ['.$reply.']';
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the Message Head Lines (first lines of the message body)
	 * @param STRING $msg_num The Message Number
	 * @param BOOLEAN $by_uid *Optional* ; Default is FALSE ; If TRUE a Message UID should be supplied for 1st param ; If FALSE a Message Number should be supplied for 1st param
	 * @param INTEGER+ $read_lines The number of lines to retrieve 1..255
	 * @return STRING The first lines of the message body or empty string on ERROR
	 */
	public function header($msg_num, $by_uid=false, $read_lines=5) { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Header // No MailBox Selected ...';
		} //end if
		if($this->is_safe_uid_or_msgnum($msg_num) !== true) {
			$this->error = '[ERR] IMAP4 Header // Empty or Invalid Message NUM / UID provided ...';
		} //end if
		//--
		if($this->debug) {
			if($by_uid) {
				$this->log .= '[INF] IMAP4 Header Message by UID: '.$msg_num.' // Lines: '.(int)$read_lines."\n";
			} else {
				$this->log .= '[INF] IMAP4 Header Message by Number: '.$msg_num.' // Lines: '.(int)$read_lines."\n";
			} //end if else
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if($by_uid) {
			$reply = $this->send_cmd('UID FETCH '.$msg_num.' BODY.PEEK[HEADER]');
		} else {
			$reply = $this->send_cmd('FETCH '.$msg_num.' BODY.PEEK[HEADER]');
		} //end if else
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Header Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$header_out = '';
		//--
		$mark_ok = ')'."\r\n".$this->tag.' OK ';
		if(strpos((string)$reply, (string)$mark_ok) !== false) {
			//--
			$tmp_repl_arr = (array) explode((string)$mark_ok, (string)$reply);
			$tmp_repl_txt = (string) (isset($tmp_repl_arr[0]) ? $tmp_repl_arr[0] : '');
			$tmp_repl_arr = (array) explode("\r\n", (string)trim((string)$tmp_repl_txt));
			$tmp_repl_arr[0] = ''; // the 1st line is the IMAP Answer
			//--
			if($read_lines <= 0) {
				//--
				$header_out = (string) trim((string)implode("\r\n", (array)$tmp_repl_arr));
				//--
			} else {
				//--
				$tmp_max = count($tmp_repl_arr);
				//--
				if($tmp_max > ($read_lines + 1)) {
					$tmp_max = $read_lines + 1;
				} //end if
				//--
				for($i=1; $i<$tmp_max; $i++) { // we start at 1 because the 1st line is the IMAP Answer
					$header_out .= (string) $tmp_repl_arr[$i]."\r\n";
				} //end for
				//--
			} //end if else
			//--
		} //end if
		//--
		return (string) $header_out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the Entire Message from Server
	 * @param STRING $msg_num The Message Number or Message UID if 2nd param is set to TRUE
	 * @param BOOLEAN $by_uid *Optional* ; Default is FALSE ; If TRUE a Message UID should be supplied for 1st param ; If FALSE a Message Number should be supplied for 1st param
	 * @return STRING The full message containing the Message Headers and Body as stored on the server or empty string on ERROR
	 */
	public function read($msg_num, $by_uid=false) { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Read // No MailBox Selected ...';
		} //end if
		if($this->is_safe_uid_or_msgnum($msg_num) !== true) {
			$this->error = '[ERR] IMAP4 Read // Empty or Invalid Message NUM / UID provided ...';
		} //end if
		//--
		if($this->debug) {
			if($by_uid) {
				$this->log .= '[INF] IMAP4 Read Message by UID: '.$msg_num."\n";
			} else {
				$this->log .= '[INF] IMAP4 Read Message Number: '.$msg_num."\n";
			} //end if
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if($by_uid) {
			$reply = $this->send_cmd('UID FETCH '.$msg_num.' BODY[]');
		} else {
			$reply = $this->send_cmd('FETCH '.$msg_num.' BODY[]');
		} //end if else
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Read Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$msg_out = '';
		//--
		$mark_ok = ')'."\r\n".$this->tag.' OK ';
		if(strpos((string)$reply, (string)$mark_ok) !== false) {
			//--
			$tmp_repl_arr = (array) explode((string)$mark_ok, (string)$reply);
			$tmp_repl_txt = (string) (isset($tmp_repl_arr[0]) ? $tmp_repl_arr[0] : '');
			$tmp_repl_arr = (array) explode("\r\n", (string)trim((string)$tmp_repl_txt));
			$tmp_repl_arr[0] = ''; // the 1st line is the IMAP Answer
			//--
			for($i=1; $i<count($tmp_repl_arr); $i++) { // we start at 1 because the 1st line is the IMAP Answer
				$msg_out .= $tmp_repl_arr[$i]."\r\n";
			} //end for
			//--
		} //end if
		//--
		return (string) $msg_out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * COPY a Message from the current selected MailBox to another (destination) MailBox
	 * @param STRING $msg_num The Message Number or Message UID if 3rd param is set to TRUE
	 * @param STRING $dest_mbox The destination MailBox name ; Ex: 'Trash' or 'Deleted Messages' or 'Archive', ...
	 * @param BOOLEAN $by_uid *Optional* ; Default is FALSE ; If TRUE a Message UID should be supplied for 1st param ; If FALSE a Message Number should be supplied for 1st param
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function copy($msg_uid, $dest_mbox, $by_uid=false) { // IMAP4
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Copy // No MailBox Selected ...';
		} //end if
		if($this->is_safe_uid_or_msgnum($msg_uid) !== true) {
			$this->error = '[ERR] IMAP4 Copy // Empty or Invalid Message UID provided ...';
		} //end if
		//--
		if($this->debug) {
			if($by_uid) {
				$this->log .= '[INF] Copy Message by UID ('.$msg_uid.') to: ('.$dest_mbox.')'."\n";
			} else {
				$this->log .= '[INF] Copy Message by NUM ('.$msg_uid.') to: ('.$dest_mbox.')'."\n";
			} //end if else
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//-- UID COPY {uid} {mbox} is to copy by UID ; COPY {num} {mbox} is to copy by number
		if($by_uid) {
			$reply = $this->send_cmd('UID COPY '.$msg_uid.' "'.$this->mailbox_escape($dest_mbox).'"');
		} else {
			$reply = $this->send_cmd('COPY '.$msg_uid.' "'.$this->mailbox_escape($dest_mbox).'"');
		} //end if else
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] UID Copy Failed :: '.$test.' // '.$reply;
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * STORE an uploaded Message to the current selected MailBox
	 * @param STRING $message The full message containing the headers and the body as stored locally
	 * @return STRING The UID of the new uploaded message or Empty string on Error
	 */
	public function append($message) {
		//--
		if((string)trim((string)$this->crr_mbox) == '') {
			$this->error = '[ERR] IMAP4 Append // No MailBox Selected ...';
		} //end if
		if((string)trim((string)$message) == '') {
			$this->error = '[ERR] IMAP4 Append // Message is Empty ...';
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] Appending a Message to MailBox ('.$this->crr_mbox.') ...'."\n";
		} //end if
		//--
		$message = (string) trim((string)str_replace([ "\r", "\n" ], [ '', "\r\n" ], (string)$message)); // {{{SYNC-MAIL-MSG-IMAP4-STORE}}} ; to be compliant with all IMAP servers, this is a fix (make all lines \r\n instead of \r or \n)
		$checksum = (string) sha1((string)$message);
		$len = (int) strlen((string)$message);
		//--
		if((int)$len <= 0) {
			$this->error = '[ERR] IMAP4 Append // Message is Empty ...';
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if(!fputs($this->socket, (string)$this->tag.' APPEND "'.$this->mailbox_escape($this->crr_mbox).'" (\\Seen) {'.$len.'}'."\r\n")) {
			$this->error = '[ERR] IMAP4 Append CMD // Failed ...';
			return '';
		} //end if
		//--
		if(!@fputs($this->socket, $message."\r\n")) {
			$this->error = '[ERR] IMAP4 Append MSG // Failed ...';
			return '';
		} //end if
		//--
		$data = (string) $this->get_answer_data();
		$this->log .= (string) trim((string)$data)."\n";
		//--
		$reply = (string) $data;
		$reply = $this->strip_clf($reply);
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] IMAP4 Count Messages Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$tmp_arr_uid = (array) explode('[APPENDUID ', (string)$data);
		$tmp_uid = (string) trim((string)(isset($tmp_arr_uid[1]) ? $tmp_arr_uid[1] : ''));
		$tmp_arr_uid = array();
		$tmp_arr_uid = (array) explode('] Append', (string)$tmp_uid);
		$tmp_uid = (string) trim((string)(isset($tmp_arr_uid[0]) ? $tmp_arr_uid[0] : ''));
		$tmp_arr_uid = array();
		$tmp_arr_uid = (array) explode(' ', (string)$tmp_uid);
		if(!array_key_exists(0, $tmp_arr_uid)) {
			$tmp_arr_uid[0] = null;
		} //end if
		if(!array_key_exists(1, $tmp_arr_uid)) {
			$tmp_arr_uid[1] = null;
		} //end if
		if((string)trim((string)$tmp_arr_uid[0]) == (string)$this->crr_uiv) {
			$tmp_uid = 'IMAP4-UIV-'.trim((string)$this->crr_uiv).'-UID-'.trim((string)$tmp_arr_uid[1]); // {{{SYNC-IMAP4-SAFE-UIDS}}}
		} else {
			$tmp_uid = 'IMAP4-UIV-'.trim((string)$this->crr_uiv).'-UIDSHA1-'.trim((string)$checksum); // {{{SYNC-IMAP4-SAFE-FALLBACK-UIDS}}}
		} //end if else
		//--
		$this->log .= '[INF] Appended Completed and the UID is: '.$tmp_uid."\n";
		//--
		return (string) $tmp_uid;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// :: PRIVATES ::
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// security check: UID must contain *ONLY* 0-9 ; extended support can contain: a-z A-Z _ - . and these are all !
	// important: it must support the extended charset to be compatible with webmail imap clients !!!
	private function is_safe_uid_or_msgnum($msg_num) {
		//--
		$msg_num = (string) trim((string)$msg_num);
		if((string)$msg_num == '') {
			return false;
		} //end if
		//--
		if(!preg_match('/^[_a-zA-Z0-9\-\.]+$/', (string)$msg_num)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// escapes a mailbox name (get from roundcube class)
	private function mailbox_escape($mboxname) { // escape the name of a mailbox
		//--
		$mboxname = (string) Smart::normalize_spaces(SmartUnicode::utf8_to_iso($mboxname)); // {{{SYNC-MAILGET-SAFE-FIX-NAMES}}}
		//--
		return (string) strtr((string)$mboxname, [ '"'=>'\\"', '\\' => '\\\\' ]);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Strips \r\n from server responses
	private function strip_clf($text) { // IMAP4
		//--
		return (string) trim((string)$text);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Return 'ok' on +OK or 'error#' on -ERR
	private function is_ok($reply) { // IMAP4
		//--
		$reply = (string)trim((string)$reply);
		//--
		$ok = 'ERROR: Reply is not OK !';
		//--
		if((string)$reply == '') {
			$ok = 'ERROR: Reply is Empty !';
		} //end if
		//--
		$arr_lines = (array) explode("\r\n", (string)$reply);
		//--
		for($i=0; $i<count($arr_lines); $i++) {
			//--
			$tmp_line = (string) trim((string)$arr_lines[$i]);
			//--
			if((string)$tmp_line != '') {
				if((string)substr((string)$tmp_line, 0, 1) != '*') {
					if((string)substr((string)$tmp_line, 0, (int)((int)strlen((string)$this->tag) + 4)) == (string)$this->tag.' OK ') {
						$ok = 'ok';
					} //end if
				} //end if
			} //end if
			//--
		} //end for
		//--
		return (string) $ok;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Sends a user defined command string to the IMAP server and returns the results.
	// Useful for non-compliant or custom IMAP servers.
	// Do NOT include the \r\n as part of your command string - it will be appended automatically.
	// The return value is a standard fgets() call, which will read up to buffer bytes of data,
	// until it encounters a new line, or EOF, whichever happens first.
	// This method works best if $cmd responds with only one line of data.
	private function send_cmd($cmd, $raw=false, $allow_partial_answer=false) { // IMAP4
		//--
		if(!$this->socket) {
			$this->error = '[ERR] IMAP4 Send Command: No connection to server // '.$cmd;
			return '';
		} //end if
		//--
		if((string)trim((string)$cmd) == '') {
			$this->error = '[ERR] IMAP4 Send Command: Empty command to send !';
			return '';
		} //end if
		//--
		$original_cmd = (string) $cmd;
		if($raw !== true) {
			$cmd = (string) $this->tag.' '.$cmd;
		} //end if
		//--
		if(!@fputs($this->socket, $cmd."\r\n")) {
			$this->error = '[ERR] IMAP4 Send Command: FAILED !';
			return '';
		} //end if
		//--
		$reply = $this->get_answer_data((bool)$allow_partial_answer);
		$reply = $this->strip_clf($reply);
		//--
		if($this->debug) {
			//--
			if((string)substr((string)trim((string)$original_cmd), 0, 6) == 'LOGIN ') {
				$tmp_cmd = $this->tag.' LOGIN '.$this->username.' *****'; // hide the password protection
			} elseif((string)substr((string)trim((string)$original_cmd), 0, 13) == 'AUTHENTICATE ') {
				$tmp_cmd = $this->tag.' AUTHENTICATE '.$this->authmec.' ['.$this->username.':*****]'; // hide the password protection
			} else {
				$tmp_cmd = $cmd;
			} //end if else
			//--
			$this->log .= '[COMMAND] IMAP4: `'.$tmp_cmd.'`'."\n".'[REPLY]: `'.$reply.'`'."\n";
			//--
		} //end if
		//--
		return (string) $reply;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// retrieve one response line from server
	private function get_answer_line() { // IMAP4
		//--
		if(!$this->socket) {
			$this->error = '[ERR] IMAP4 Get Answer: No connection to server // '.$cmd;
			return '';
		} //end if
		//--
		$line = '';
		//--
		while(!feof($this->socket)) {
			//--
			$line .= (string) fgets($this->socket, $this->buffer);
			//--
			if((strlen((string)$line) >= 2) && ((string)substr((string)$line, -2) == "\r\n")) {
				//--
				break;
				//--
			} //end if
			//--
		} //end while
		//--
		return (string) $line;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// retrive the full response message from server
	private function get_answer_data($allow_partial_answer=false) { // IMAP4
		//--
		$data = '';
		//--
		while(1) {
			//--
			$line = (string) $this->get_answer_line();
			//--
			$data .= (string) $line;
			//--
			if((string)substr((string)trim((string)$line), 0, (int)((int)strlen((string)$this->tag) + 1)) == (string)$this->tag.' ') {
				break;
			} //end if
			if($allow_partial_answer === true) {
				if((string)substr((string)trim((string)$line), 0, 2) == '+ ') {
					break;
				} //end if
			} //end if
			//--
		} //end while
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//=====================================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== END CLASS
//=====================================================================================



//=====================================================================================
//===================================================================================== START CLASS
//=====================================================================================


/**
 * Class: SmartMailerPop3Client - provides a POP3 Mail Client with SSL/TLS support.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		After each operation on the POP3 Server should check the $pop3->error string and if non-empty stop and disconnect to free the socket
 *
 * @depends 	classes: Smart, SmartUnicode
 * @version 	v.20250107
 * @package 	Plugins:Mailer
 *
 */
final class SmartMailerPop3Client {

	// ->


	/**
	 * @var INT
	 * @default 512
	 * socket read buffer
	 */
	public $buffer = 512;

	/**
	 * @var INT
	 * @default 30
	 * socket timeout in seconds
	 */
	public $timeout = 30;

	/**
	 * @var BOOLEAN
	 * @default FALSE
	 * debug on/off
	 */
	public $debug = false;

	/**
	 * @var STRING
	 * @default ''
	 * This should be checked after each operation on the server
	 * The error message(s) will be collected here
	 * do not SET a value here, but just GET the result
	 */
	public $error = '';

	/**
	 * @var STRING
	 * @default ''
	 * The operations log (only if debug is enabled)
	 * Do not SET a value here, but just GET the result
	 */
	public $log = '';

	//--
	private $socket = false; 	// socket resource ID
	private $apop_banner = ''; 	// store the banner used for APOP Auth Method
	//--
	private $cafile = '';		// Certificate Authority File (instead of using the global SMART_FRAMEWORK_SSL_CA_FILE can use a private cafile
	//--

	//--
	private $is_connected_and_logged_in = false;
	private $selected_box = '';
	//--


	//=====================================================================================
	/**
	 * POP3 Client Class constructor
	 */
	public function __construct($buffer=0) {
		//--
		$this->socket = false;
		$this->apop_banner = '';
		//--
		if($buffer > 0) {
			$this->buffer = (int) $buffer;
		} //end if
		if($this->buffer < 512) {
			$this->buffer = 512;
		} elseif($this->buffer > 8192) {
			$this->buffer = 8192;
		} //end if else
		//--
		$this->log = '';
		$this->error = '';
		//--
		$this->is_connected_and_logged_in = false;
		$this->selected_box = '';
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Connected + Logged In Status
	 */
	public function is_connected_and_logged_in() {
		//--
		if(($this->is_connected_and_logged_in === true) AND (is_resource($this->socket))) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Selected MailBox (on POP3 will be available just after connect + login, no need to select mailbox, there is only one: INBOX)
	 */
	public function get_selected_mailbox() {
		//--
		if($this->is_connected_and_logged_in() !== true) {
			return '';
		} //end if
		//--
		return (string) $this->selected_box;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Clear the Last Error
	 */
	public function clear_last_error() {
		//--
		$this->error = '';
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Set a SSL/TLS Certificate Authority File
	 * If not set but SMART_FRAMEWORK_SSL_CA_FILE is defined will use the SMART_FRAMEWORK_SSL_CA_FILE
	 * @param STRING $cafile Relative Path to a SSL Certificate Authority File (Ex: store within smart-framework/etc/certificates ; specify as 'etc/certificates/ca.pem') ; IMPORTANT: in this case the 'etc/certificates/' directory must be protected with a .htaccess to avoid being public readable - the directory and any files within this directory ...)
	 * @return VOID
	 */
	public function set_ssl_tls_ca_file($cafile) {
		//--
		$this->cafile = '';
		if(SmartFileSysUtils::checkIfSafePath((string)$cafile) == '1') {
			if(SmartFileSysUtils::staticFileExists((string)$cafile)) {
				$this->cafile = (string) $cafile;
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Will try to open a socket to the specified POP3 Server using the host/ip and port ; If a SSL option is selected will try to establish a SSL socket or fail
	 * @param STRING $server The Server Hostname or IP address
	 * @param INTEGER+ $port *Optional* The Server Port ; Default is: 110
	 * @param ENUM $sslversion To connect using SSL mode this must be set to any of these accepted values: '', 'starttls', 'starttls:1.0', 'starttls:1.1', 'starttls:1.2', 'tls', 'tls:1.0', 'tls:1.1', 'tls:1.2', 'ssl', 'sslv3' ; If empty string is set here it will be operate in unsecure mode (NOT using any SSL/TLS Mode)
	 * @return INTEGER+ 1 on success, 0 on fail
	 */
	public function connect($server, $port=110, $sslversion='') {

		//-- inits
		$this->socket = false;
		$this->apop_banner = '';
		//--

		//-- checks
		$server = (string) trim((string)$server);
		if((strlen((string)$server) <= 0) OR (strlen((string)$server) > 255)) {
			$this->error = '[ERR] Invalid Server to Connect ! ['.$server.']';
			return 0;
		} //end if
		//--
		$port = (int) $port;
		if(($port <= 0) OR ($port > 65535)) {
			$this->error = '[ERR] Invalid Port to Connect ! ['.$port.']';
			return 0;
		} //end if
		//--

		//--
		$protocol = '';
		$start_tls = false;
		$start_tls_version = null;
		//--
		$is_secure = false;
		if((string)$sslversion != '') {
			//--
			if(!function_exists('openssl_open')) {
				$this->error = '[ERR] PHP OpenSSL Extension is required to perform SSL requests !';
				return 0;
			} //end if
			//--
			$is_secure = true;
			switch((string)strtolower((string)$sslversion)) {
				case 'starttls':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLS_CLIENT; // since PHP 5.6.7, STREAM_CRYPTO_METHOD_TLS_CLIENT (same for _SERVER) no longer means any tls version but tls 1.0 only (for "backward compatibility"...)
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.0':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.1':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.2':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				case 'starttls:1.3':
					$start_tls_version = STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
					$start_tls = true;
					$protocol = ''; // reset because will connect in a different way
					break;
				//--
				case 'ssl':
					$protocol = 'ssl://'; // deprecated
					break;
				case 'sslv3':
					$protocol = 'sslv3://'; // deprecated
					break;
				//--
				case 'tls:1.0':
					$protocol = 'tlsv1.0://';
					break;
				case 'tls:1.1':
					$protocol = 'tlsv1.1://';
					break;
				case 'tls:1.2':
					$protocol = 'tlsv1.2://';
					break;
				case 'tls:1.3':
					$protocol = 'tlsv1.3://';
					break;
				case 'tls':
				default:
					$protocol = 'tls://';
			} //end switch
			//--
		} //end if else
		//--

		//--
		if($this->debug) {
			$this->log .= '[INF] '.($is_secure === true ? 'SECURE ' : '').($start_tls === true ? '(STARTTLS:'.$start_tls_version.') ' : '').'Connecting to POP3 Mail Server: '.$protocol.$server.':'.$port."\n";
		} //end if
		//--

		//--
		//$sock = @fsockopen($protocol.$server, $port, $errno, $errstr, $this->timeout);
		$stream_context = @stream_context_create();
		if(((string)$protocol != '') OR ($start_tls === true)) {
			//--
			$cafile = '';
			if((string)$this->cafile != '') {
				$cafile = (string) $this->cafile;
			} elseif(defined('SMART_FRAMEWORK_SSL_CA_FILE')) {
				if((string)SMART_FRAMEWORK_SSL_CA_FILE != '') {
					$cafile = (string) SMART_FRAMEWORK_SSL_CA_FILE;
				} //end if
			} //end if
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
		//--
		if(!$this->debug) {
			Smart::disableErrLog(); // skip log, except debug, POP3 connection errors
		} //end if
		$sock = @stream_socket_client($protocol.$server.':'.$port, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $stream_context);
		if(!$this->debug) {
			Smart::restoreErrLog(); // restore the original log handlers
		} //end if
		//--
		if(!is_resource($sock)) {
			$this->error = '[ERR] Could not open connection. Error: '.$errno.' :: '.$errstr;
			return 0;
		} //end if
		//--
		$this->socket = $sock;
		unset($sock);
		//--
		@stream_set_timeout($this->socket, (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT);
		if($this->debug) {
			$this->log .= '[INF] Set Socket Stream TimeOut to: '.SMART_FRAMEWORK_NETSOCKET_TIMEOUT.' ; ReadBuffer = '.$this->buffer."\n";
		} //end if
		//--

		//-- If mode is 0, the given stream will be switched to non-blocking mode, and if 1, it will be switched to blocking mode. This affects calls like fgets() and fread()  that read from the stream. In non-blocking mode an fgets() call will always return right away while in blocking mode it will wait for data to become available on the stream.
		@socket_set_blocking($this->socket, 1); // set to blocking mode
		//--

		//-- avoid connect normally if SSL/TLS was explicit required
		$chk_crypto = (array) @stream_get_meta_data($this->socket);
		if((string)$protocol != '') {
			if(!SmartUnicode::str_icontains($chk_crypto['stream_type'], '/ssl')) { // will return something like: tcp_socket/ssl
				$this->error = '[ERR] Connection CRYPTO CHECK Failed ...'."\n";
				@socket_set_blocking($this->socket, 0);
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
		} //end if
		//--

		//--
		$reply = @fgets($this->socket, $this->buffer);
		$reply = $this->strip_clf($reply);
		$test = $this->is_ok($reply);
		//--
		if((string)$test != 'ok') {
			$this->error = '[ERR] Server Reply is NOT OK // '.$test.' // '.$reply;
			@socket_set_blocking($this->socket, 0);
			@fclose($this->socket);
			$this->socket = false;
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[REPLY] \''.$reply.'\''."\n";
		} //end if
		//-- apop banner
		$this->apop_banner = $this->parse_banner($reply);
		//--

		//--
		if($start_tls === true) {
			//--
			if($this->debug) {
				$this->log .= '[INF] StartTLS on Server'."\n";
			} //end if
			//--
			$reply = $this->send_cmd('STLS');
			if((string)$this->error != '') {
				@socket_set_blocking($this->socket, 0);
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] Server StartTLS Failed :: '.$test.' // '.$reply;
				@socket_set_blocking($this->socket, 0);
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
			if(!$start_tls_version) {
				$this->error = '[ERR] Server StartTLS Invalid Protocol Selected ...';
				@socket_set_blocking($this->socket, 0);
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
			if($this->debug) {
				$this->log .= '[INF] Start TLS negotiation on Server'."\n";
			} //end if
			$test_starttls = @stream_socket_enable_crypto($this->socket, true, $start_tls_version);
			if(!$test_starttls) {
				$this->error = '[ERR] Server StartTLS Failed to be Enabled on Socket ...';
				@socket_set_blocking($this->socket, 0);
				@fclose($this->socket);
				$this->socket = false;
				return 0;
			} //end if
			//--
		} //end if
		//--

		//--
		return 1;
		//--

	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Ping the POP3 Server
	 * Sends the command NOOP to the Server
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function noop() {
		//--
		if($this->debug) {
			$this->log .= '[INF] Ping the Mail Server // NOOP'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$reply = $this->send_cmd('NOOP');
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] Server Noop Failed :: '.$test.' // '.$reply;
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Reset the POP3 server connection (includding all messages marked to be deleted)
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function reset() {
		//--
		if($this->debug) {
			$this->log .= '[INF] Reset the Connection to Mail Server'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$reply = $this->send_cmd('RSET');
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] Reset the Connection FAILED !';
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Sends the QUIT/LOGOUT commands set to the POP3 server
	 * Closes the communication socket after sending LOGOUT command
	 * @return INTEGER+ 1 on Success or 0 on Error
	 */
	public function quit() {
		//--
		$this->selected_box = '';
		$this->is_connected_and_logged_in = false; // must be at the top of this function
		//--
		if($this->debug) {
			$this->log .= '[INF] Sending QUIT to Mail Server !'."\n";
		} //end if
		//--
		if(!$this->socket) {
			return 0;
		} //end if
		//--
		$reply = $this->send_cmd('QUIT'); // pop3
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$reply = $this->send_cmd('? LOGOUT'); // pop3 over imap
		} //end if else
		//--
		@socket_set_blocking($this->socket, 0);
		@fclose($this->socket);
		$this->socket = false;
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Try to Authenticate on the POP3 Server with a username and password
	 * Sends both user and pass to the Server
	 * @param STRING $username The authentication username
	 * @param STRING $pass The authentication password
	 * @param ENUM $mode *Optional* The authentication mode ; can be set to any of: 'login', 'apop', 'auth:cram-md5' ; Default is 'login'
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function login($username, $pass, $mode='') {
		//--
		$username = (string) Smart::normalize_spaces(SmartUnicode::utf8_to_iso($username)); // {{{SYNC-MAILGET-SAFE-FIX-NAMES}}}
		$pass = (string) Smart::normalize_spaces(SmartUnicode::utf8_to_iso($pass)); // {{{SYNC-MAILGET-SAFE-FIX-NAMES}}}
		//--
		if((string)trim((string)$username) == '') {
			$this->error = '[ERR] POP3 Auth: Username is Empty';
			return 0;
		} //end if
		if((string)trim((string)$pass) == '') {
			$this->error = '[ERR] POP3 Auth: Password is Empty';
			return 0;
		} //end if
		//--
		$mode = (string) strtolower((string)trim((string)$mode));
		if((string)$mode == '') {
			$mode = 'login'; // the default auth mode
		} //end if
		switch((string)$mode) {
			case 'login':
				$mode = 'login';
				break;
			case 'apop':
				$mode = 'apop';
				break;
			case 'auth:cram-md5':
				$mode = 'auth:cram-md5';
				break;
			default:
				$this->error = '[ERR] POP3 Invalid Auth/Login Mode: '.$mode;
				return 0;
		} //end switch
		//--
		if($this->debug) {
			$this->log .= '[INF] Login to Mail Server (MODE='.$mode.' ; USER='.$username.')'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		if((string)$mode == 'login') { // normal login
			//-- normal login
			if($this->debug) {
				$this->log .= '[INF] Login Method: NORMAL (DEFAULT) { UNSECURE over non-encrypted connections }'."\n";
			} //end if
			//--
			$reply = $this->send_cmd('USER '.$username);
			if((string)$this->error != '') {
				return 0;
			} //end if
			//--
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] POP3 Auth: User Failed ['.$reply.']';
				return 0;
			} //end if
			//--
			$reply = $this->send_cmd('PASS '.$pass);
			if((string)$this->error != '') {
				return 0;
			} //end if
			//--
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] POP3 Auth: Pass Failed ['.$reply.']';
				return 0;
			} //end if
			//--
		} elseif((string)$mode == 'apop') { // apop login
			//--
			if($this->debug) {
				$this->log .= '[INF] Login Method: APOP ; Banner = ['.$this->apop_banner.'] { LESS SECURE ; if an encrypted connection is available is better to use NORMAL instead of APOP }'."\n";
			} //end if
			//--
			if((string)trim((string)$this->apop_banner) == '') {
				$this->error = '[ERR] POP3 Auth: APOP Method Failed, Server Banner is Empty';
				return 0;
			} //end if
			//--
			$reply = $this->send_cmd('APOP '.$username.' '.md5((string)$this->apop_banner.$pass));
			if((string)$this->error != '') {
				return 0;
			} //end if
			//--
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] POP3 Auth: APOP Failed ['.$reply.']';
				return 0;
			} //end if
			//--
		} elseif((string)$mode == 'auth:cram-md5') { // auth:cram-md5 {{{SYNC-AUTH:CRAM-MD5-METHOD}}}
			//--
			$secret = $this->send_cmd('AUTH CRAM-MD5');
			if((string)$this->error != '') {
				return 0;
			} //end if
			$secret = (string) trim((string)$secret);
			if((string)substr((string)$secret, 0, 2) !== '+ ') {
				$this->error = '[ERR] POP3 Login: CRAM-MD5 Secret is WRONG ['.$secret.']';
				return 0;
			} //end if
			$secret = (string) trim((string)substr((string)$secret, 2));
			if((string)$secret == '') {
				$this->error = '[ERR] POP3 Login: CRAM-MD5 Secret is EMPTY';
				return 0;
			} //end if
			$secret = (string) Smart::b64_dec((string)$secret);
			if((string)trim((string)$secret) == '') {
				$this->error = '[ERR] POP3 Login: CRAM-MD5 Secret is INVALID';
				return 0;
			} //end if
			$digest = (string) hash_hmac('md5', (string)$secret, (string)$pass);
			//--
			$reply = $this->send_cmd((string)Smart::b64_enc((string)$username.' '.$digest));
			if((string)$this->error != '') {
				return 0;
			} //end if
			//--
			$test = $this->is_ok($reply);
			if((string)$test != 'ok') {
				$this->error = '[ERR] POP3 Auth: CRAM-MD5 Failed ['.$reply.']';
				return 0;
			} //end if
			//--
		} else { // others, invalid or not supported
			//--
			$this->error = '[ERR] POP3 Invalid Auth/Login Mechanism: '.$mode;
			return 0;
			//--
		} //end if else
		//--
		$this->is_connected_and_logged_in = true;
		$this->selected_box = 'INBOX'; // on POP3 there is only INBOX ...
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get The Count Information for the selected account MailBox
	 * @return ARRAY Which contains the following keys [ 'size' => 1024, 'count' => 101 ]
	 */
	public function count() {
		//--
		if($this->debug) {
			$this->log .= '[INF] Reading the Messages Count and Size for MailBox ...'."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$reply = $this->send_cmd('STAT');
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 Count Messages Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$vars 	= (array) explode(' ', (string)$reply);
		$count 	= trim((string)(isset($vars[1]) ? $vars[1] : ''));
		$size 	= trim((string)(isset($vars[2]) ? $vars[2] : ''));
		//--
		$count = (int) $count;
		$size = (int) $size;
		//--
		if($this->debug) {
			$this->log .= '[INF] Messages Count [Count='.$count.' ; Size='.$size.']'."\n";
		} //end if
		//--
		return array('count' => $count, 'size' => $size);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the list of UIDs or a specific UID for the selected message
	 * @param STRING $msg_num *Optional* A specific Message Number for a specific message or leave empty to get the list with all UIDs (all message numbers on the server)
	 * @return STRING A unique UID or a list with multiple (ar all) UIDs ; returns empty string on error
	 */
	public function uid($msg_num='') {
		//--
		if($this->debug) {
			if((string)trim((string)$msg_num) != '') {
				$this->log .= '[INF] POP3 UID Message Number: '.$msg_num."\n";
			} else {
				$this->log .= '[INF] POP3 UID for all Messages'."\n";
			} //end if else
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		if((string)trim((string)$msg_num) != '') {
			if($this->is_safe_msgnum($msg_num) !== true) {
				$this->error = '[ERR] POP3 UID Message(s) Failed [Invalid Message Num: '.$msg_num.']';
				return '';
			} //end if
			$reply = $this->send_cmd('UIDL '.$msg_num);
		} else {
			$reply = $this->send_cmd('UIDL'); // returns: ID[SPACE]UID\n
		} //end if else
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 UID Message(s) Failed ['.$reply.']';
			return '';
		} //end if
		//--
		if((string)trim((string)$msg_num) != '') {
			$tmp_arr = (array) explode(' ', (string)$reply); // The answer is like [OK] [MSGNUM] [UIDL]
			$uid = (string) trim((string)(isset($tmp_arr[2]) ? $tmp_arr[2] : ''));
		} else {
			$uid = (string) $this->retry_data();
			if((string)$this->error != '') {
				return '';
			} //end if
		} //end if
		//--
		if($this->debug) {
			if((string)trim((string)$msg_num) != '') {
				$this->log .= '[INF] UID For Message #'.$msg_num.' is: ['.$uid.']'."\n";
			} else {
				$this->log .= '[INF] UID For Messages are: [(LIST)]'."\n";
			} //end if else
		} //end if
		//--
		return (string) $uid;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the size for the selected message
	 * @hints Some servers may not comply with this method and may return empty result
	 * @param STRING $msg_num The Message Number
	 * @return STRING the size of the selected message (ussualy in Bytes) or empty string if Error
	 */
	public function size($msg_num) {
		//--
		if($this->is_safe_msgnum($msg_num) !== true) {
			$this->error = '[ERR] POP3 Size Message: NUM is Empty or Invalid';
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] POP3 Size Message Number: '.$msg_num."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$reply = $this->send_cmd('LIST '.$msg_num);
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 Size Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		$tmp_arr = (array) explode(' ', (string)$reply); // The answer is like [JUNK] [MSGNUM] [MSGSIZE]
		$size = (string) trim((string)(isset($tmp_arr[2]) ? $tmp_arr[2] : ''));
		//--
		if($this->debug) {
			$this->log .= '[INF] Size For Message #'.$msg_num.' is: ['.$size.']'."\n";
		} //end if
		//--
		return (string) $size;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Delete a message stored on the POP3 Server
	 * @param STRING $msg_num The Message Number
	 * @return INTEGER+ 1 on Success or 0 on Failure or Error
	 */
	public function delete($msg_num) {
		//--
		if($this->is_safe_msgnum($msg_num) !== true) {
			$this->error = '[ERR] POP3 Delete Message: NUM is Empty or Invalid';
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] POP3 Delete Message Number: '.$msg_num."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$reply = $this->send_cmd('DELE '.$msg_num);
		if((string)$this->error != '') {
			return 0;
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 Delete Message Failed ['.$reply.']';
			return 0;
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the Message Head Lines (first lines of the message body)
	 * @param STRING $msg_num The Message Number
	 * @param INTEGER+ $read_lines The number of lines to retrieve 1..255
	 * @return STRING The first lines of the message body or empty string on ERROR
	 */
	public function header($msg_num, $read_lines=5) {
		//--
		if($this->is_safe_msgnum($msg_num) !== true) {
			$this->error = '[ERR] POP3 Header Message: NUM is Empty or Invalid';
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] POP3 Header Message Number: '.$msg_num.' // Lines: '.$read_lines."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$reply = $this->send_cmd('TOP '.$msg_num.' '.$read_lines);
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 Header Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		if(!$this->socket) {
			$this->error = '[ERR] POP3 Header Message: No connection to server';
			return '';
		} //end if
		//--
		$header_out = (string) $this->retry_data();
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		return (string) $header_out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	/**
	 * Get the Entire Message from Server
	 * @param STRING $msg_num The Message Number
	 * @return STRING The full message containing the Message Headers and Body as stored on the server or empty string on ERROR
	 */
	public function read($msg_num) {
		//--
		if($this->is_safe_msgnum($msg_num) !== true) {
			$this->error = '[ERR] POP3 Read Message: NUM is Empty or Invalid';
			return 0;
		} //end if
		//--
		if($this->debug) {
			$this->log .= '[INF] POP3 Read Message Number: '.$msg_num."\n";
		} //end if
		//--
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$reply = $this->send_cmd('RETR '.$msg_num);
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		$test = $this->is_ok($reply);
		if((string)$test != 'ok') {
			$this->error = '[ERR] POP3 Read Message Failed ['.$reply.']';
			return '';
		} //end if
		//--
		if(!$this->socket) {
			$this->error = '[ERR] POP3 Read Message: No connection to server';
			return '';
		} //end if
		//--
		$msg_out = $this->retry_data();
		if((string)$this->error != '') {
			return '';
		} //end if
		//--
		return (string) $msg_out;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// :: PRIVATES ::
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// for POP3 only numeric IDs can be used on server ; the UID is just for get as reply and store it, cannot issue commands by UID in POP3 protocol
	private function is_safe_msgnum($msg_num) {
		//--
		$msg_num = (int) $msg_num;
		if($msg_num <= 0) { // POP3 Message Num starts at 1
			return false;
		} //end if
		//--
		if(!preg_match('/^[0-9]+$/', (string)$msg_num)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Strips \r\n from server responses
	private function strip_clf($text) {
		//--
		return (string) str_replace(["\r", "\n"], ['', ''], (string)$text);
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Return 'ok' on +OK or 'error#' on -ERR
	private function is_ok($reply) {
		//--
		$reply = (string)trim((string)$reply);
		//--
		$ok = 'ok';
		//--
		if((string)$reply == '') {
			$ok = 'ERROR: Reply is Empty !';
		} //end if
		//--
		if(!preg_match("/^\+OK/", (string)$reply)) {
			$ok = 'ERROR: Reply is not OK !';
		} //end if
		//--
		return (string) $ok;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// parse the APOP banner
	private function parse_banner($reply) {
		//--
		$outside = true;
		$banner = '';
		//--
		$reply = (string) trim((string)$reply);
		if((string)trim((string)$reply) == '') {
			return '';
		} //end if
		//--
		for($i=0; $i<SmartUnicode::str_len($reply); $i++) {
			//--
			$digit = SmartUnicode::sub_str($reply, $i, 1);
			//--
			if((string)$digit != '') {
				//--
				if((!$outside) AND ((string)$digit != '<') AND ((string)$digit != '>')) {
					$banner .= $digit;
				} //end if
				//--
				if((string)$digit == '<') {
					$outside = false;
				} //end if
				//--
				if((string)$digit == '>') {
					$outside = true;
				} //end if
				//--
			} //end if
			//--
		} //end for
		//--
		$banner = (string) trim((string)$this->strip_clf($banner)); // just in case
		if((string)$banner != '') {
			$banner = '<'.$banner.'>';
		} //end if
		//--
		return (string) $banner;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Sends a user defined command string to the POP server and returns the results.
	// Useful for non-compliant or custom POP servers.
	// Do NOT include the \r\n as part of your command string - it will be appended automatically.
	// The return value is a standard fgets() call, which will read up to buffer bytes of data,
	// until it encounters a new line, or EOF, whichever happens first.
	// This method works best if $cmd responds with only one line of data.
	private function send_cmd($cmd) {
		//--
		if(!$this->socket) {
			$this->error = '[ERR] POP3 Send Command: No connection to server // '.$cmd;
			return '';
		} //end if
		//--
		if((string)trim((string)$cmd) == '') {
			$this->error = '[ERR] POP3 Send Command: Empty command to send !';
			return '';
		} //end if
		//--
		if(!@fputs($this->socket, $cmd."\r\n")) {
			$this->error = '[ERR] POP3 Send Command: FAILED !';
			return '';
		} //end if
		//--
		$reply = @fgets($this->socket, $this->buffer);
		$reply = $this->strip_clf($reply);
		//--
		if($this->debug) {
			//--
			if((string)substr((string)trim((string)$cmd), 0, 5) == 'PASS ') {
				$tmp_cmd = 'PASS *****'; // hide the password protection
			} else {
				$tmp_cmd = $cmd;
			} //end if else
			//--
			$this->log .= '[COMMAND] POP3: `'.$tmp_cmd.'`'."\n".'[REPLY]: `'.$reply.'`'."\n";
			//--
		} //end if
		//--
		return $reply;
		//--
	} //END FUNCTION
	//=====================================================================================


	//=====================================================================================
	// [PRIVATE]
	// Retry Data from Server
	private function retry_data() {
		//--
		if(!$this->socket) {
			$this->error = '[ERR] POP3 Retry Data: No connection to server';
			return '';
		} //end if
		//--
		$data = '';
		$line = (string) @fgets($this->socket, $this->buffer);
		if((string)$line != '') { // don't trim
			while(!preg_match("/^\.\r\n/", (string)$line)) {
				//--
				$data .= (string) $line;
				//--
				$line = (string) @fgets($this->socket, $this->buffer);
				if((string)$line == '') { // don't trim
					break;
				} //end if
				//--
			} //end while
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//=====================================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== END CLASS
//=====================================================================================


//end of php code
