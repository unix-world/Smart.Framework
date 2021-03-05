<?php
// [LIB - Smart.Framework / Plugins / FTP Client]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - FTP Client
// DEPENDS:
//	* Smart::
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartFtpClient - provides a FTP Client with support for common FTP protocol. In addition it supports the extended Hylafax FTP protocol.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20210305
 * @package 	Plugins:Network
 *
 */
final class SmartFtpClient {

	// ->


	//=================================================== Public variables
	//--

	/**
	 * Error Message(s) Collector
	 * If any error occurs will be set here
	 * @var STRING
	 * default ''
	 */
	public $error_msg;

	/**
	 * @var BOOLEAN
	 * @default FALSE
	 * to debug or not
	 */
	public $debug;

	/**
	 * @var ENUM
	 * @default ''
	 * debug level '' as standard, log only responses | 'full' as full, log both requests and responses
	 */
	public $debug_level;

	/**
	 * Debug Log Collector
	 * If Debug, this will return the partial or full Log as string, depend on how $debug_level is set
	 * @var STRING
	 * default ''
	 */
	public $debug_msg;

	/**
	 * Connect timeout in seconds
	 * @var INTEGER+
	 * @default 30
	 */
	public $timeout;

	//--
	//=================================================== Private variables
	private $_sock;			// connection socket
	private $_buf;			// socket buffer
	private $_resp;			// server response
	//===================================================


	//===================================================
	/**
	 * Class Constructor
	 */
	public function __construct($debug=false, $timeout=30) {
		//--
		$this->error_msg 	= '';
		//--
		if($debug) {
			$this->debug   	= true;
		} else {
			$this->debug   	= false;
		} //end if else
		$this->debug_level 	= '';
		$this->debug_msg = '';
		//--
		$this->timeout 		= $timeout;
		//--
		$this->_sock 		= false;
		$this->_buf  		= 4096;
		$this->_resp 		= '';
		//--
	} //END FUNCTION
	//===================================================


	//===================================================
	//=================================================== Public functions
	//===================================================


	//===================================
	/**
	 * FTP Connect :: Connect to a FTP Server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_connect($server, $port=21) {
		//--
		if((string)$this->debug_level == 'full') {
			$this->_debug_print('Connecting to '.$server.':'.$port.' (TimeOut='.$this->timeout.')...'."\n");
		} //end if
		//--
		$this->_sock = @fsockopen($server, $port, $errno, $errstr, $this->timeout);
		//--
		if((!$this->_sock) || (!$this->_ok())) {
			$this->error_msg = 'ERROR: Cannot connect to remote host @ '.$server.':'.$port.' // '.$errstr.' ('.$errno.')';
			$this->_close_data_connection($this->_sock);
			return false;
		} //end if
		//--
		$this->_debug_print('OK: Connected to remote host '.$server.':'.$port."\n");
		//--
		return true;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP Login :: Authenticate on FTP Server using a username and a password
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_login($user, $pass) {
		//--
		$this->_putcmd("USER", $user);
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: USER command failed';
			return false;
		} //end if
		//--
		if((string)$pass != '') {
		  $this->_putcmd("PASS", $pass);
		  if(!$this->_ok()) {
			$this->error_msg = 'ERROR: PASS command failed';
			return false;
		  } //end if
		} //end if
		//--
		$this->_debug_print('OK: Authentication succeeded'."\n");
		//--
		return true;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP PWD :: Get the current path on FTP server
	 * @return MIXED On Success return Reponse String ; If Failure return FALSE
	 */
	public function ftp_pwd() {
		//--
		$this->_putcmd("PWD");
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: PWD command failed';
			return false;
		} //end if
		//--
		return (string) preg_replace("/^[0-9]{3} \"(.+)\" .+\r\n/", "\\1", (string)$this->_resp);
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP SIZE :: The size of a file on server
	 * @return INTEGER On Success return the SIZE ; If Failure return -1
	 */
	public function ftp_size($pathname) {
		// if file does not exists returns -1
		//--
		$this->_putcmd("SIZE", $pathname);
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: SIZE command failed';
			return -1;
		} //end if
		//--
		return (string) preg_replace("/^[0-9]{3} ([0-9]+)\r\n/", "\\1", (string)$this->_resp);
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP MDTM :: Last Modification Time for a PATH on FTP server
	 * @return INTEGER On Success return the LAST MODIF TIME ; If Failure return -1
	 */
	public function ftp_mdtm($pathname) {
		//--
		$this->_putcmd("MDTM", $pathname);
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: MDTM command failed';
			return -1;
		} //end if
		//--
		$mdtm = preg_replace("/^[0-9]{3} ([0-9]+)\r\n/", "\\1", (string)$this->_resp);
		//--
		$date = sscanf($mdtm, "%4d%2d%2d%2d%2d%2d");
		$timestamp = mktime($date[3], $date[4], $date[5], $date[1], $date[2], $date[0]);
		//--
		return (int) $timestamp;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP SYST :: Asks FTP Server for information about the server's operating system
	 * @return MIXED On Success return Reponse String ; If Failure return FALSE
	 */
	public function ftp_systype() {
		//--
		$this->_putcmd("SYST");
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: SYST command failed';
			return false;
		} //end if
		//--
		$res_data = (array) explode(" ", (string)$this->_resp);
		//--
		return (string) (isset($res_data[1]) ? $res_data[1] : '');
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP CDUP :: Change directory to parent directory on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_cdup() {
		//--
		$this->_putcmd("CDUP");
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: CDUP command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP CWD :: Change to a new directory on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_chdir($pathname) {
		//--
		$this->_putcmd("CWD", $pathname);
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: CWD command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP DELE :: Delete a file on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_delete($pathname) {
		//--
		$this->_putcmd("DELE", $pathname);
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: DELE command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP RMD :: Remove a directory on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_rmdir($pathname) {
		//--
		$this->_putcmd("RMD", $pathname);
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: RMD command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP MKD :: Create a new directory on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_mkdir($pathname) {
		//--
		$this->_putcmd("MKD", $pathname);
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: MKD command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP Check If File Exists on server
	 * @return ENUM If Exists return 1 ; If Not Exists return 0 ; If Error return -1
	 */
	public function ftp_file_exists($pathname) {
		//--
		if(!($remote_list = $this->ftp_nlist("-a"))) {
			$this->error_msg = 'ERROR: Cannot get remote file list';
			return -1;
		} //end if
		//--
		reset($remote_list);
		//--
		//while(list(,$value) = @each($remote_list)) {
		//while(list($key,$value) = @each($remote_list)) { // FIX to be compatible with the upcoming PHP 7
		foreach($remote_list as $key => $value) { // Fix: the above is deprecated as of PHP 7.2
			//--
			if((string)$value == (string)$pathname) {
				$this->error_msg = 'ERROR: Remote file exists: '.$pathname;
				return 1;
			} //end if
			//--
		} //end while
		//--
		if((string)$this->debug_level == 'full') {
			$this->_debug_print('OK: Remote file does not exists: '.$pathname."\n");
		} //end if
		//--
		return 0;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP RNFR/RNTO :: Rename a file or a directory on FTP server
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_rename($from, $to) {
		//--
		$this->_putcmd("RNFR", $from);
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: RNFR command failed';
			return false;
		} //end if
		//--
		$this->_putcmd("RNTO", $to);
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: RNTO command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP NLST :: Get the Files List on a FTP Server
	 * Unlike the FTP LIST command, the server will send only the list of files and no other information on those files
	 * @return MIXED The list of files as ARRAY or FALSE on error
	 */
	public function ftp_nlist($arg='', $pathname='') {
		//--
		if(!($string = $this->_pasv())) {
			$this->error_msg = 'ERROR: NLST command failed - PASSIVE';
			return false;
		} //end if
		//--
		if((string)$arg == "") {
			$nlst = "NLST";
		} else {
			$nlst = "NLST ".$arg;
		} //end if else
		//--
		$this->_putcmd($nlst, $pathname);
		//--
		$sock_data = $this->_open_data_connection($string);
		if(!$sock_data) {
			$this->error_msg = 'ERROR: NLST // Cannot connect to remote host';
			return false;
		} //end if
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: NLST command failed (1)';
			$this->_close_data_connection($sock_data);
			return false;
		} //end if
		//--
		$this->_debug_print('OK: NLST // Connected to remote host'."\n");
		//--
		$list = array();
		while(!feof($sock_data)) {
			$list[] = preg_replace("/[\r\n]/", "", (string)@fgets($sock_data, 512));
		} //end while
		//--
		$this->_close_data_connection($sock_data);
		//--
		if((string)$this->debug_level == 'full') {
			$this->_debug_print((string)implode("\n", (array)$list));
		} //end if
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: NLST command failed (2)';
			return array();
		} //end if
		//--
		return (array) $list;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP LIST :: Get the Raw Files List on a FTP Server
	 * Unlike the FTP NLST command, the server will send all information on those files
	 * @return MIXED The list of files as ARRAY or FALSE on error
	 */
	public function ftp_rawlist($pathname='') {
		//--
		if(!($string = $this->_pasv())) {
			$this->error_msg = 'ERROR: LIST command failed - PASSIVE';
			return false;
		} //end if
		//--
		$this->_putcmd("LIST", $pathname);
		$sock_data = $this->_open_data_connection($string);
		//--
		if(!$sock_data) {
			$this->error_msg = 'ERROR: LIST // Cannot connect to remote host';
			return false;
		} //end if
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: LIST command failed (1)';
			$this->_close_data_connection($sock_data);
			return false;
		} //end if
		//--
		$this->_debug_print('OK: LIST // Connected to remote host'."\n");
		//--
		$list = array();
		while(!feof($sock_data)) {
			$list[] = preg_replace("/[\r\n]/", "", (string)@fgets($sock_data, 512));
		} //end while
		//--
		if((string)$this->debug_level == 'full') {
			$this->_debug_print((string)implode("\n", (array)$list));
		} //end if
		//--
		$this->_close_data_connection($sock_data);
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: LIST command failed (2)';
			return array();
		} //end if
		//--
		return (array) $list;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP GET :: Get a file from FTP server and store locally
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_get($localfile, $remotefile, $mode=1) {
		//--
		if(SmartFileSystem::path_exists($localfile)) {
			SmartFileSystem::delete($localfile);
			if((string)$this->debug_level == 'full') {
				$this->_debug_print('WARNING: local file will be overwritten'."\n");
			} //end if
		} //end if
		//--
		$fp = @fopen($localfile, 'wb');
		if(!$fp) {
			$this->error_msg = 'ERROR: GET command failed // Cannot create local file: '.$localfile;
			return false;
		} //end if
		//--
		if(!$this->_type($mode)) {
			$this->error_msg = 'ERROR: GET command failed - TYPE: '.$mode;
			@fclose($fp);
			return false;
		} //end if
		//--
		if(!($string = $this->_pasv())) {
			$this->error_msg = 'ERROR: GET command failed - PASSIVE';
			@fclose($fp);
			return false;
		} //end if
		//--
		$this->_putcmd("RETR", $remotefile);
		//--
		$sock_data = $this->_open_data_connection($string);
		//--
		if(!$sock_data) {
			$this->error_msg = 'ERROR: GET // Cannot connect to remote host';
			return false;
		} //end if
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: GET command failed (1)';
			$this->_close_data_connection($sock_data);
			return false;
		} //end if
		//--
		$this->_debug_print('OK: GET // Connected to remote host'."\n");
		if((string)$this->debug_level == 'full') {
			$this->_debug_print('Retrieving remote file: '.$remotefile.' to local file: '.$localfile."\n");
		} //end if
		//--
		while(!feof($sock_data)) {
			@fwrite($fp, @fread($sock_data, $this->_buf));
		} //end if
		//--
		@fclose($fp);
		//--
		$this->_close_data_connection($sock_data);
		//--
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: GET command failed (2)';
			return '';
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP PUT :: Put a local file on FTP server and store remote
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_put($remotefile, $localfile, $mode=1) {
		//--
		if(!SmartFileSystem::path_real_exists($localfile)) {
			$this->error_msg = 'ERROR: PUT command failed // No such file or directory (or broken link): '.$localfile;
			return false;
		} //end if
		//--
		$fp = @fopen($localfile, "rb");
		if(!$fp) {
			$this->error_msg = 'ERROR: PUT command failed // Cannot read file: '.$localfile;
			return false;
		} //end if
		//--
		if(!$this->_type($mode)) {
			$this->error_msg = 'ERROR: PUT command failed - TYPE: '.$mode;
			@fclose($fp);
			return false;
		} //end if
		//--
		if(!($string = $this->_pasv())) {
			$this->error_msg = 'ERROR: PUT command failed - PASSIVE';
			@fclose($fp);
			return false;
		} //end if
		//--
		$this->_putcmd("STOR", $remotefile);
		//--
		$sock_data = $this->_open_data_connection($string);
		//--
		if(!$sock_data) {
			$this->error_msg = 'ERROR: PUT // Cannot connect to remote host';
			return false;
		} //end if
		//--
		if(!$this->_ok()) {
			$this->error_msg = 'ERROR: PUT command failed (1)';
			$this->_close_data_connection($sock_data);
			return false;
		} //end if
		//--
		$this->_debug_print('OK: PUT // Connected to remote host'."\n");
		if((string)$this->debug_level == 'full') {
			$this->_debug_print('Storing local file: '.$localfile.' to remote file: '.$remotefile."\n");
		} //end if
		//--
		while(!feof($fp)) {
			@fwrite($sock_data, @fread($fp, $this->_buf));
		} //end while
		//--
		@fclose($fp);
		//--
		$this->_close_data_connection($sock_data);
		//--
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: PUT command failed (2)';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP SITE :: Ask FTP server to accept non-standard FTP commands that may not be universally supported by the FTP standard protocol
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_site($command) {
		//--
		$this->_putcmd("SITE", $command);
		//--
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: SITE command failed';
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP EXTENDED Command :: Send an extra and non-standard FTP command to the FTP Server that may not be universally supported by the FTP standard protocol
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_extcmd($command) {
		//--
		$this->_putcmd($command, '');
		$response = $this->_ok();
		if(!$response) {
			$this->error_msg = 'ERROR: EXTENDED command failed: '.$command;
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * FTP EXTENDED Command :: Send an extra and non-standard FTP command to the FTP Server that returns an answer and that may not be universally supported by the FTP standard protocol
	 * @return MIXED If Success return a string with the server's answer ; If Failure return FALSE
	 */
	public function ftp_extcmdx($command) {
		//--
		$this->_putcmd($command, '');
		$response = $this->_answer();
		if(!$response) {
			$this->error_msg = 'ERROR: EXTENDED-XTRA command failed: '.$command;
			return false;
		} //end if
		//--
		return (string) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	/**
	 * Sends the QUIT command to the FTP server
	 * Closes the communication socket after sending QUIT command
	 * @return BOOLEAN If Success return TRUE ; If Failure return FALSE
	 */
	public function ftp_quit() {
		//--
		$this->_putcmd("QUIT");
		//--
		if(!$this->_ok()) {
			$this->_debug_print('ERROR: QUIT command failed'."\n");
		} //end if
		//--
		$out = (bool) $this->_close_data_connection($this->_sock);
		//--
		if($out) {
			$this->_debug_print('Disconnected from remote host'."\n");
		} else {
			$this->_debug_print('WARNING: Disconnecting from remote host Failed ...'."\n");
		} //end if
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//===================================


	//===================================================
	//=================================================== Private Functions
	//===================================================


	//===================================
	private function _type($mode) {
		//--
		if($mode) {
			$type = "I"; //Binary mode
		} else {
			$type = "A"; //ASCII mode
		} //end if else
		//--
		$this->_putcmd("TYPE", $type);
		$response = $this->_ok();
		if(!$response) {
			$this->_debug_print('ERROR: TYPE command failed'."\n");
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _port($ip_port) {
		//--
		$this->_putcmd("PORT", $ip_port);
		$response = $this->_ok();
		if(!$response) {
			$this->_debug_print('ERROR: PORT command failed'."\n");
			return false;
		} //end if
		//--
		return (bool) $response;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _pasv() {
		//--
		$this->_putcmd("PASV");
		if(!$this->_ok()) {
			$this->_debug_print('ERROR: PASV command failed'."\n");
			return false;
		} //end if
		//--
		$ip_port = preg_replace("/^.+ \\(?([0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]+,[0-9]+)\\)?.*\r\n$/", "\\1", (string)$this->_resp);
		//--
		return (string) $ip_port;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _putcmd($cmd, $arg='') {
		//--
		if((string)$arg != '') {
			$cmd = $cmd.' '.$arg;
		} //end if
		//--
		if(!$this->_sock) {
			$this->_debug_print('ERROR: CMD command failed: '.$cmd."\n");
			return false;
		} //end if
		//--
		@fwrite($this->_sock, $cmd."\r\n");
		//--
		if($this->debug_level == 'full') {
			//--
			if(SmartUnicode::str_toupper(substr($cmd, 0, 5)) == 'PASS ') {
				$this->_debug_print('# '.'PASS ********'."\n");
			} elseif(SmartUnicode::str_toupper(substr($cmd, 0, 6)) == 'ADMIN ') {
				$this->_debug_print('# '.'ADMIN ********'."\n");
			} else {
				$this->_debug_print('# '.$cmd."\n");
			} //end if else
			//--
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _ok() {
		//--
		$this->_resp = '';
		//--
		for($i=0; $i<3; $i++) {
			//--
			$res = @fgets($this->_sock, 512);
			$this->_resp .= $res;
			//--
			$rstop = SmartUnicode::sub_str($res, 3, 1);
			$rstop_plus = SmartUnicode::sub_str($res, 0, 3);
			//--
			if(is_numeric($rstop_plus)) {
				if((string)$rstop == ' ') {
					$i = 3; // stop
				} else {
					$i = 1; // continue
				} //end if else
			} else {
				$i = 1; // continue
			} //end if else
			//--
		} //end for
		//--
		$this->_debug_print(str_replace("\r\n", "\n", $this->_resp));
		//--
		if(!preg_match("/^[123]/", (string)$this->_resp)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _answer() {
		//--
		$this->_resp = '';
		//--
		for($i=0; $i<3; $i++) {
			//--
			$res = @fgets($this->_sock, 512);
			$this->_resp .= $res;
			//--
			$rstop = SmartUnicode::sub_str($res, 3, 1);
			$rstop_plus = SmartUnicode::sub_str($res, 0, 3);
			//--
			if(is_numeric($rstop_plus)) {
				if((string)$rstop == ' ') {
					$i = 3; // stop
				} else {
					$i = 1; // continue
				} //end else
			} else {
				$i = 1; // continue
			} //end if else
			//--
		} //end for
		//--
		$this->_debug_print(str_replace("\r\n", "\n", $this->_resp));
		//--
		return (string) $this->_resp;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _close_data_connection($sock) {
		//--
		if((string)$this->debug_level == 'full') {
			$this->_debug_print('Closing Data Connection for Channel: '.$sock."\n");
		} //end if
		//--
		if($sock) {
			$out = @fclose($sock);
		} else {
			$out = false;
		} //end if else
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _open_data_connection($ip_port) {
		//--
		if(!preg_match("/[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]+,[0-9]+/", (string)$ip_port)) {
			$this->_debug_print("Error : Illegal ip-port format(".$ip_port.")\n");
			return false;
		} //end if
		//--
		$res_data = (array) explode(",", (string)$ip_port);
		if(!array_key_exists(0, $res_data)) {
			$res_data[0] = null;
		} //end if
		if(!array_key_exists(1, $res_data)) {
			$res_data[1] = null;
		} //end if
		if(!array_key_exists(2, $res_data)) {
			$res_data[2] = null;
		} //end if
		if(!array_key_exists(3, $res_data)) {
			$res_data[3] = null;
		} //end if
		if(!array_key_exists(4, $res_data)) {
			$res_data[4] = null;
		} //end if
		if(!array_key_exists(5, $res_data)) {
			$res_data[5] = null;
		} //end if
		//--
		$ipaddr = $res_data[0].".".$res_data[1].".".$res_data[2].".".$res_data[3];
		$port = $res_data[4]*256 + $res_data[5];
		//--
		$this->_debug_print("Opening Data Connection to ".$ipaddr.":".$port." ...\n");
		//--
		$data_connection = @fsockopen($ipaddr, $port, $errno, $errstr);
		if(!$data_connection) {
			$this->_debug_print('Error : Cannot open data connection to @ '.$ipaddr.':'.$port.' // '.$errstr.' ('.$errno.')'."\n");
			return false;
		} //end if
		//--
		return $data_connection; // resource
		//--
	} //END FUNCTION
	//===================================


	//===================================
	private function _debug_print($message='') {
		//--
		if($this->debug) {
			$this->debug_msg .= $message;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//===================================


} //END CLASS


//============================================================
//============================================================


//======================================================= USAGE
//ftp_connect($server, $port = 21);
//ftp_login($user, $pass);
//ftp_pwd();
//ftp_size($pathname);
//ftp_mdtm($pathname);
//ftp_systype();
//ftp_cdup();
//ftp_chdir($pathname);
//ftp_delete($pathname);
//ftp_rmdir($pathname);
//ftp_mkdir($pathname);
//ftp_file_exists($pathname);
//ftp_rename($from, $to);
//ftp_nlist($arg = "", $pathname = "");
//ftp_rawlist($pathname = "");
//ftp_get($localfile, $remotefile, $mode = 1);
//ftp_put($remotefile, $localfile, $mode = 1);
//ftp_site($command);
//ftp_extcmd($command); // sends ftp raw commands [XNT]
//ftp_quit();
//======================================================= EXAMPLE
/**
	$ftp = new SmartFtpClient();
	$ftp->debug = true;
	$ftp->debug_level = 'full';
	$next = $ftp->ftp_connect('IP.ADDRESS.SERVER', '21');
	if($next) {
		$next = $ftp->ftp_login('username', 'password');
	} //end if
	if($next) {
		//$ftp->ftp_size('/image.jpg');
		//$ftp->ftp_file_exists('/some_folder');
		//$ftp->ftp_mkdir('/some_folder');
		$next = $ftp->ftp_put('/image.jpg', 'local/dir/file122.jpg', 1); // for binary files always use binary !!!
	} //end if
	$ftp->ftp_quit();
	//$ftp->debug_msg;
**/
//=======================================================

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
