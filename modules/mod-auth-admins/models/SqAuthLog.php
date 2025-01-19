<?php
// Class: \SmartModDataModel\AuthAdmins\SqAuthLog
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModDataModel\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * SQLite Model for ModAuthAdmins Logging (Abstract)
 * @ignore
 */
abstract class SqAuthLog extends \SmartModDataModel\AuthAdmins\AbstractAuthLog {

	// ->
	// v.20250107

	private $db;
	private $dbFile;
	private $dbDate;

	public const FAIL_LOGIN_MINUTES = 5; // min is 1 min ; max is 10 mins
	public const FAIL_LOGIN_LIMITS = 5; // min is 5 ; max is 10
	public const FAIL_LOGIN_FACTOR = 3; // min is 2 ; max is 4 ; fail logins multiplication factor ; ex: if set to 3, after 3 * 5 = 15 total failed logins, will force ask for captcha until the day ends for that IP

	private const ERR_NO_CONNECTION = 'Invalid AUTH-LOG DB Connection !';


	final public function __construct() { // THIS SHOULD BE THE ONLY METHOD IN THIS CLASS THAT THROW EXCEPTIONS !!!
		//--
		if(!\SmartEnvironment::isAdminArea()) {
			throw new \Exception('AUTH-LOG DB can operate under admin/task area only !');
			return;
		} //end if
		//--
		$this->dbDate = (string) \date('Y-m-d');
		//--
		$areaPfx = \Smart::safe_username((string)(\SmartEnvironment::isAdminArea() === true) ? ((\SmartEnvironment::isTaskArea() === true) ? 'tsk' : 'adm') : 'idx');
		if((string)\trim((string)$areaPfx) == '') {
			throw new \Exception('AUTH-LOG DB SQLITE: Invalid Area Prefix `'.$areaPfx.'`');
			return;
		} //end if
		//--
		$this->dbFile = 'tmp/logs/'.$areaPfx.'/smart-auth-log-'.$this->dbDate.'.sqlite';
		if(!\SmartFileSysUtils::checkIfSafePath((string)$this->dbFile, true, false)) {
			throw new \Exception('AUTH-LOG DB SQLITE File Path is Unsafe !');
			return;
		} //end if
		//--
		$this->db = new \SmartSQliteDb((string)$this->dbFile);
		$this->db->open();
		//--
		if(!\SmartFileSystem::is_type_file((string)$this->dbFile)) {
			if($this->db instanceof \SmartSQliteDb) {
				$this->db->close();
			} //end if
			throw new \Exception('AUTH-LOG DB SQLITE File does NOT Exists !');
			return;
		} //end if
		//--
		$init_schema = $this->initDBSchema(); // null or string
		if($init_schema !== null) { // create default schema if not exists (and a default account)
			throw new \Exception('AUTH-LOG DB Init Schema Failed with Message: '.$init_schema);
		} //end if
		//--
	} //END FUNCTION


	final public function __destruct() {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			return;
		} //end if
		//--
		$this->db->close();
		//--
	} //END FUNCTION


	final public function logAuthSuccess(?string $auth_id, ?string $ip, ?string $msg) : bool {
		//--
		return (bool) $this->logAuthData(true, (string)$auth_id, (string)$ip, (string)$msg); // true = success
		//--
	} //END FUNCTION


	final public function logAuthFail(?string $auth_id, ?string $ip, ?string $msg) : bool {
		//--
		return (bool) $this->logAuthData(false, (string)$auth_id, (string)$ip, (string)$msg); // false = fail
		//--
	} //END FUNCTION


	final public function checkFailLoginsByIp(string $ip) : int {
		//--
		// ALGO DESCRIPTION:
		// Example:
		// 	* if more than 5 logins in last 5 minutes will block logins for 5 minutes
		// 	* after 5 * 3 = 15 failed logins needs to solve captcha (if solved, will reset the failed logins status and will re-init this algo) or expect the end of the day 23:59:59 (after, the Auth Logs DB will reset)
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return 0; // must be zero, not negative or positive if there is an issue with the auth logging DB
		} //end if
		//--
		$failseconds = (int) ((int)self::FAIL_LOGIN_MINUTES * 60);
		if((int)$failseconds < 60) {
			$failseconds = 60; // dissalow this setting being under 60 seconds (1 minute)
		} //end if
		//--
		$loginslimit = (int) self::FAIL_LOGIN_LIMITS;
		if((int)$loginslimit < 5) {
			$loginslimit = 5;
		} elseif((int)$loginslimit > 10) {
			$loginslimit = 10;
		} //end if
		//--
		$loginsmaxfactor = (int) self::FAIL_LOGIN_FACTOR;
		if($loginsmaxfactor < 2) {
			$loginsmaxfactor = 2;
		} elseif((int)$loginsmaxfactor > 4) {
			$loginsmaxfactor = 4;
		} //end if else
		//--
		$nowtime     = (int) \time();
		$checktime   = (int) ((int)$nowtime - (int)$failseconds);
		//--
		$ip = (string) \Smart::ip_addr_compress((string)\trim((string)$ip));
		//--
		$cntAll = (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `authfail` WHERE ((`expired` = 0) AND (`ip_addr` = ?))',
			[
				(string) $ip,
			]
		);
		//--
		$factor = 1;
		if((int)$cntAll > 0) {
			$factor = (int) floor((int)$cntAll / (int)$loginslimit);
		} //end if
		if((int)$factor < 1) {
			$factor = 1;
		} //end if
		if((int)$factor >= (int)$loginsmaxfactor) {
			return -1; // ask for captcha
		} //end if
		//--
		$cntLatest = (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `authfail` WHERE ((`expired` = 0) AND (`ip_addr` = ?) AND (`created` > ?))',
			[
				(string) $ip,
				(int)    $checktime,
			]
		);
		$chkArrLatest = (array) $this->db->read_asdata(
			'SELECT COALESCE(MAX(`created`),0) AS `newest_time` FROM `authfail` WHERE ((`expired` = 0) AND (`ip_addr` = ?) AND (`created` > ?)) LIMIT 1 OFFSET 0',
			[
				(string) $ip,
				(int)    $checktime,
			]
		);
		$chkArrLatest['newest_time'] = (int) $chkArrLatest['newest_time'];
		//--
		if(
			((int)$cntLatest <= 0)
			OR
			((int)\Smart::array_size($chkArrLatest) <= 0)
		) {
			return 0; // something went wrong with queries
		} //end if
		//--
		if(
			((int)$cntLatest <= (int)$loginslimit)
			OR
			((int)$chkArrLatest['newest_time'] <= 0) // unsupported if negative ; if zero all ok ; going further should be positive
		) {
			return 0; // under limit, with fixes
		} //end if
		//--

		//--
		$allowtime = (int) ((int)$chkArrLatest['newest_time'] + (int)$failseconds);
		if((int)$allowtime <= 0) {
			return 0;
		} //end if
		if((int)$nowtime >= (int)$allowtime) {
			return 0;
		} //end if
		//--
		return (int) $allowtime;
		//--
	} //END FUNCTION


	final public function resetFailedLogins(string $ip) : bool {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return false;
		} //end if
		//--
		$ip = (string) \Smart::ip_addr_compress((string)\trim((string)$ip));
		//--
		$wr = (array) $this->db->write_data(
			'UPDATE `authfail` SET `expired` = 1 WHERE (`ip_addr` = ?)', // mark all authfail entries as expired on 1st successful login
			[
				(string) $ip,
			]
		);
		//--
		return (bool) (($wr[1] == 1) ? true : false);
		//--
	} //END FUNCTION


	//======== [ PRIVATES ]


	private function logAuthData(bool $is_success, string $auth_id, string $ip, string $msg) : bool {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return false;
		} //end if
		//--
		$auth_id 	= (string) \trim((string)$auth_id);
		$ip 		= (string) \Smart::ip_addr_compress((string)\trim((string)$ip));
		$msg 		= (string) \trim((string)$msg);
		//--
		$bw = (array) \SmartUtils::get_os_browser_ip();
		//--
		$arrMsg = [
			'auth' 		=> (string) $msg,
			'ip' 		=> (string) $bw['ip'],
			'proxy' 	=> (string) $bw['px'],
			'url' 		=> (string) \SmartUtils::get_server_current_url(false),
			'request' 	=> [
				'uri' 		=> (string) \SmartUtils::get_server_current_script(), // \SmartUtils::get_server_current_request_uri(), // security: the request URI have to be encrypted or not disclosed ... it may reveal sensitive information to 3rd party persons (aka other admins) ...
				'method' 	=> (string) \SmartUtils::get_server_current_request_method(),
				'with' 		=> (string) \SmartUtils::get_server_current_request_with(),
			],
			'os' 		=> (string) $bw['os'],
			'bw' 		=> (string) $bw['bw'],
			'bc' 		=> (string) $bw['bc'],
			'mobile' 	=> (string) $bw['mobile'],
			'ua' 		=> (string) \SmartUtils::get_visitor_useragent(),
		];
		//--
		if(\SmartAuth::validate_auth_username(
			(string) $auth_id,
			false // do not check for reasonable length
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			$auth_id = '';
		} //end if
		//--
		$tbl_name = 'authfail';
		if($is_success === true) {
			$tbl_name = 'authlog';
			$this->resetFailedLogins((string)$ip);
		} //end if else
		//--
		$wr = (array) $this->db->write_data('INSERT OR IGNORE INTO `'.$tbl_name.'`'.$this->db->prepare_statement(
			[
				'id' 		=> (string) \Smart::uuid_15_seq(),
				'auth_id' 	=> (string) $auth_id,
				'ip_addr' 	=> (string) $ip,
				'log_msg' 	=> (string) \Smart::json_encode((array)$arrMsg, false, false, false), //  non-pretty, escaped unicode: in URL may be weird chars ; non HTML safe (does not make sense here)
				'expired' 	=> (int)    0,
				'created' 	=> (int)    \time(),
			],
			'insert'
		));
		//--
		return (bool) (($wr[1] == 1) ? true : false);
		//--
	} //END FUNCTION


	private function initDBSchema() : ?string {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			return 'Invalid AUTH DB Connection !';
		} //end if
		//--
		if($this->db->check_if_table_exists('authlog') != 1) { // create auth DB if not exists
			//--
			$this->db->write_data('BEGIN');
			$this->db->write_data((string)$this->dbDefaultSchema());
			$this->db->write_data('COMMIT');
			//--
		} //end if
		//--
		if($this->db->check_if_table_exists('authfail') != 1) {
			$this->db->write_data('BEGIN');
			$this->db->create_table( // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
				'authfail',
				"-- #START: table schema: authfail @ 20250107
	`id` character varying(15) PRIMARY KEY NOT NULL,
	`auth_id` character varying(25) NOT NULL,
	`ip_addr` character varying(39) NOT NULL,
	`log_msg` text NOT NULL,
	`expired` smallint DEFAULT 0 NOT NULL,
	`created` bigint DEFAULT 0 NOT NULL
				-- #END: table schema",
				[ // indexes
					'authfail_auth_id' 	=> '`auth_id` ASC',
					'authfail_ip_addr' 	=> '`ip_addr` ASC',
					'authfail_expired' 	=> '`expired` DESC',
					'authfail_created' 	=> '`created` DESC',
				]
			);
			$this->db->write_data('COMMIT');
		} //end if
		//--
		return null;
		//--
	} //END FUNCTION


	private function dbDefaultSchema() : string { // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return 'UNESCAPED-SQL:CAN NOT CONTINUE';
		} //end if
		//--
//-- default schema
$version = (string) $this->db->escape_str((string)\SMART_FRAMEWORK_RELEASE_TAGVERSION.' '.\SMART_FRAMEWORK_RELEASE_VERSION);
$dbtag = (string) $this->db->escape_str((string)$this->dbDate);
$schema = <<<SQL
-- #START: tables schema: _smartframework_metadata / authlog @ 20250107
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@auth-authlog', '{$version}');
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('tag@auth-authlog', '{$dbtag}');
CREATE TABLE 'authlog' (
	`id` character varying(15) PRIMARY KEY NOT NULL,
	`auth_id` character varying(25) NOT NULL,
	`ip_addr` character varying(39) NOT NULL,
	`log_msg` text NOT NULL,
	`expired` smallint DEFAULT 0 NOT NULL,
	`created` bigint DEFAULT 0 NOT NULL
);
CREATE INDEX 'authlog_auth_id' ON `authlog` (`auth_id` ASC);
CREATE INDEX 'authlog_ip_addr' ON `authlog` (`ip_addr` ASC);
CREATE INDEX 'authlog_expired' ON `authlog` (`expired` DESC);
CREATE INDEX 'authlog_created' ON `authlog` (`created` DESC);
-- #END: tables schema
SQL;
//--
	//--
	return (string) $schema;
	//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code

