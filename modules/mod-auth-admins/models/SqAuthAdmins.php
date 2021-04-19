<?php
// Class: \SmartModDataModel\AuthAdmins\SqAuthAdmins
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

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
 * SQLite Model for ModAuthAdmins
 * @ignore
 */
final class SqAuthAdmins {

	// ->
	// v.20210420

	private $db;


	public function __construct() {
		//--
		if(!\defined('\\APP_AUTH_DB_SQLITE')) {
			\Smart::raise_error('AUTH DB SQLITE is NOT Defined !');
			return;
		} //end if
		//--
		$this->db = new \SmartSQliteDb((string)\APP_AUTH_DB_SQLITE);
		$this->db->open();
		//--
		if(!\SmartFileSystem::is_type_file((string)\APP_AUTH_DB_SQLITE)) {
			if($this->db instanceof \SmartSQliteDb) {
				$this->db->close();
			} //end if
			\Smart::raise_error('AUTH DB SQLITE File does NOT Exists !');
			return;
		} //end if
		//--
		$this->initDBSchema(); // create default schema if not exists (and a default account)
		//--
	} //END FUNCTION


	public function __destruct() {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			return;
		} //end if
		//--
		$this->db->close();
		//--
	} //END FUNCTION


	public function getLoginData($auth_user_name, $auth_user_hash_pass) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return array();
		} //end if
		//--
		return (array) $this->db->read_asdata(
			'SELECT * FROM `admins` WHERE ((`id` = ?) AND (`pass` = ?) AND (`active` = 1)) LIMIT 1 OFFSET 0',
			array($auth_user_name, $auth_user_hash_pass)
		);
		//--
	} //END FUNCTION


	public function checkFailLoginData($id, $ip) {
		//--
		// FAIL LOGINS LIMIT: 7
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT * FROM `authfail` WHERE ((`id` = ?) AND (`ip_addr` = ?)) LIMIT 1 OFFSET 0',
			[
				(string) $id,
				(string) $ip
			]
		);
		//--
		if(\Smart::array_size($arr) <= 0) {
			return 0;
		} //end if
		//--
		if((int)$arr['tries'] <= 7) {
			return 0;
		} //end if
		if((int)$arr['trytime'] <= 0) {
			return 0;
		} //end if
		//--
		$nowtime 	= (int) \time();
		$allowtime 	= (int) ((int)$arr['trytime'] + (((int)$arr['tries'] - 7) * 60));
		if((int)$nowtime >= (int)$allowtime) {
			return 0;
		} //end if
		//--
		return (int) $allowtime;
		//--
	} //END FUNCTION


	public function logSuccessfulLoginData($id, $ip) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return false;
		} //end if
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT SUM(`tries`) AS `tot_tries`, MAX(`trytime`) AS `tot_trytime` FROM `authfail` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			[
				(string) $id
			]
		);
		//--
		$del = (array) $this->db->write_data(
			'DELETE FROM `authfail` WHERE (`id` = ?)',
			[
				(string) $id
			]
		);
		//--
		$upd = [];
		if((int)$arr['tot_tries'] > 0) {
			$upd = (array) $this->db->write_data(
				'UPDATE `admins` SET `ip_addr` = ?, `logintime` = ?, `tries` = ?, `trytime` = ? WHERE (`id` = ?)',
				[
					(string) $ip,
					(int)    \time(),
					(int)    $arr['tot_tries'],
					(int)    $arr['tot_trytime'],
					(string) $id
				]
			);
		} else {
			$upd = (array) $this->db->write_data(
				'UPDATE `admins` SET `ip_addr` = ?, `logintime` = ? WHERE ((`id` = ?) AND (`logintime` < ?))',
				[
					(string) $ip,
					(int)    \time(),
					(string) $id,
					(int)    (\time() - 60) // log just once per minute
				]
			);
		} //end if else
		//--
		return (bool) (($upd[1] == 1) ? true : false);
		//--
	} //END FUNCTION


	public function logUnsuccessfulLoginData($id, $ip, $ua) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return false;
		} //end if
		//--
		$arr = (array) $this->getById($id);
		if(\Smart::array_size($arr) <= 0) {
			return false;
		} //end if
		//--
		@\file_put_contents(
			'tmp/logs/adm/'.'auth-fail-'.\date('Y-m-d@H').'.log',
			'[ERR]'."\t".\Smart::normalize_spaces((string)\date('Y-m-d H:i:s O'))."\t".\Smart::normalize_spaces((string)$id)."\t".\Smart::normalize_spaces((string)$ip)."\t".\Smart::normalize_spaces((string)$ua)."\n",
			\FILE_APPEND | \LOCK_EX
		);
		//--
		$this->db->write_data('INSERT OR IGNORE INTO `authfail`'.$this->db->prepare_statement(
			[
				'id' 		=> (string) $id,
				'ip_addr' 	=> (string) $ip,
				'tries' 	=> 0,
				'trytime' 	=> 0,
				'ua' 		=> ''
			],
			'insert'
		));
		$upd = (array) $this->db->write_data(
			'UPDATE `authfail` SET `tries` = `tries` + 1, `trytime` = ?, `ua` = ? WHERE ((`id` = ?) AND (`ip_addr` = ?))',
			[
				(int)    \time(),
				(string) $ua,
				(string) $id,
				(string) $ip
			]
		);
		//--
		return (bool) (($upd[1] == 1) ? true : false);
		//--
	} //END FUNCTION


	//===== Management


	public function getById($id) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return array();
		} //end if
		//--
		return (array) $this->db->read_asdata(
			'SELECT * FROM `admins` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			array((string)$id)
		);
		//--
	} //END FUNCTION


	public function countByFilter($id='') {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return 0;
		} //end if
		//--
		$where = '';
		$params = '';
		//--
		if((string)$id != '') {
			$where = ' WHERE (`id` = ?)';
			$params = array($id);
		} //end if else
		//--
		return (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `admins`'.$where,
			$params
		);
		//--
	} //END FUNCTION


	public function getListByFilter($fields=array(), $limit=10, $ofs=0, $sortby='id', $sortdir='ASC', $id='') {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return array();
		} //end if
		//--
		if(\Smart::array_size($fields) > 0) {
			$tmp_arr = (array) $fields;
			$fields = array();
			for($i=0; $i<\Smart::array_size($tmp_arr); $i++) {
				if(\is_array($tmp_arr[$i])) {
					foreach($tmp_arr[$i] as $kk => $vv) {
						$fields[] = $vv.'('.'`'.$kk.'`'.') AS `'.$kk.'-'.$vv.'`';
						break;
					} //end foreach
				} else {
					$fields[] = '`'.$tmp_arr[$i].'`';
				} //end if else
			} //end for
			$tmp_arr = null;
			$fields = (string) \implode(', ', (array) $fields);
		} else {
			$fields = '*';
		} //end if else
		//--
		$limit = ' LIMIT '.\Smart::format_number_int($limit,'+').' OFFSET '.\Smart::format_number_int($ofs,'+');
		$where = '';
		$params = '';
		//--
		if((string)$id != '') {
			$limit = ' LIMIT 1 OFFSET 0';
			$where = ' WHERE (`id` = ?)';
			$params = array($id);
		} //end if else
		//--
		$sortby = (string) \strtolower((string)\trim((string)$sortby));
		switch((string)$sortby) {
			case 'active':
			case 'email':
			case 'name_f':
			case 'name_l':
			case 'modif':
				// OK
				break;
			case 'id':
			default:
				$sortby = 'id';
		} //end switch
		//--
		$sortdir = (string) \strtoupper((string)$sortdir);
		if((string)$sortdir != 'DESC') {
			$sortdir = 'ASC';
		} //end if
		//--
		return (array) $this->db->read_adata(
			'SELECT '.$fields.' FROM `admins`'.$where.' ORDER BY `'.$sortby.'` '.$sortdir.$limit,
			$params
		);
		//--
	} //END FUNCTION


	public function insertAccount($data) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return -100;
		} //end if
		//--
		$this->db->write_data('VACUUM');
		//--
		$data = (array) $data;
		$data['id'] = (string) \Smart::safe_username((string)\trim((string)$data['id']));
		$data['pass'] = (string) \trim((string)$data['pass']);
		$data['email'] = (string) \trim((string)$data['email']);
		//--
		if((\strlen((string)$data['id']) < 3) OR (\strlen((string)$data['id']) > 25)) {
			return -10; // invalid username length
		} //end if
		if(!\preg_match('/^[a-z0-9\.]+$/', (string)$data['id'])) {
			return -11; // invalid characters in username
		} //end if
		if(!\preg_match('/^[a-f0-9]+$/', (string)$data['pass'])) {
			return -12; // invalid password, must be hex hash
		} //end if
		if(\strlen((string)$data['pass']) != 128) {
			return -13; // invalid password, must be sha512 (128 chars)
		} //end if
		//-- {{{SYNC-MOD-AUTH-EMAIL-VALIDATION}}}
		if((\strlen((string)$data['email']) < 6) OR (\strlen((string)$data['email']) > 96) OR (!\preg_match((string)\SmartValidator::regex_stringvalidation_expression('email'), (string)$data['email']))) {
			$data['email'] = null; // NULL, as the email is invalid
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$check_id = (array) $this->db->read_asdata(
			'SELECT `id` FROM `admins` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			array((string)$data['id'])
		);
		if($data['email'] === null) {
			$check_eml = array();
		} else {
			$check_eml = (array) $this->db->read_asdata(
				'SELECT `id` FROM `admins` WHERE (`email` = ?) LIMIT 1 OFFSET 0',
				array((string)$data['email'])
			);
		} //end if else
		if(\Smart::array_size($check_id) > 0) {
			$out = -2; // duplicate ID
		} elseif(\Smart::array_size($check_eml) > 0) {
			$out = -3; // duplicate email
		} else {
			$wr = (array) $this->db->write_data(
				'INSERT INTO `admins` '.$this->db->prepare_statement(
					[
						'id' 		=> (string) $data['id'],
						'pass' 		=> (string) $data['pass'], // pass should be already a hash to avoid send it unsecure !!
						'email' 	=>          $data['email'], // mixed: false (NULL) or string
						'name_f' 	=> (string) $data['name_f'],
						'name_l' 	=> (string) $data['name_l'],
						'created' 	=> \time(),
						'active' 	=> '0'
					],
					'insert'
				)
			);
			$out = $wr[1];
		} //end if else
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	public function updateStatus($id, $status) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return -100;
		} //end if
		//--
		if((string)$id == '') {
			return -10; // invalid username length
		} //end if
		//--
		$status = (int) $status;
		if($status != 1) {
			$status = 0;
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'UPDATE `admins` '.$this->db->prepare_statement(
				(array) [
					'modif' 	=> \time(),
					'active' 	=> (int) $status
				],
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE ((`id` = ?) AND (`restrict` NOT LIKE ?))',
				[
					(string) $id,
					(string) '%<modify>%' // {{{SYNC-EDIT-PRIVILEGES}}}
				]
			)
		);
		$out = $wr[1];
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	public function decryptPrivKey($pkey_enc) { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		$pass = (string) \SmartAuth::get_login_password();
		//--
		if((string)trim((string)$pass) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartAuth::decrypt_privkey((string)$pkey_enc, (string)$pass);
		//--
	} //END FUNCTION


	public function encryptPrivKey($pkey_plain, $newpass=null) { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		if($newpass !== null) {
			$pass = (string) $newpass; // used only when a user changes his/her account password by him(her)self ; if so, re-encrypt the private key with this new password
		} else {
			$pass = (string) \SmartAuth::get_login_password();
		} //end if else
		//--
		if((string)trim((string)$pass) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartAuth::encrypt_privkey((string)$pkey_plain, (string)$pass);
		//--
	} //END FUNCTION


	public function updatePassword($id, $hash, $pass) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return -100;
		} //end if
		//--
		if(\Smart::random_number(0, 100) == 50) {
			$this->db->write_data('VACUUM');
		} //end if
		//--
		if((string)$id == '') {
			return -10; // invalid username ID length
		} //end if
		//--
		$rd_arr = (array) $this->getById($id);
		if(\Smart::array_size($rd_arr) <= 0) {
			return -11; // invalid username ID (does not exists)
		} //end if
		//--
		$pkeys = '';
		if((string)$id == (string)\SmartAuth::get_login_id()) { // for current logged in user, get his keys, oterwise don't as they can't be updated ... the password of each user is stored as ireversible hash format !
			$pkeys = (string) \trim((string)$rd_arr['keys']);
		} //end if
		//--
		$rd_arr = null;
		//--
		$hash = (string) $hash;
		if(!\preg_match('/^[a-f0-9]+$/', (string)$hash)) {
			return -12; // invalid password, must be hex hash
		} //end if
		if(\strlen((string)$hash) != 128) {
			return -13; // invalid password, must be sha512 (128 chars)
		} //end if
		//--
		$arr = [
			'modif' 	=> (int)    \time(),
			'pass' 		=> (string) $hash
		];
		//--
		if((string)$id == (string)\SmartAuth::get_login_id()) { // for current logged in user, get his keys, oterwise don't as they can't be updated ...
			//--
			if((string)$pkeys != '') {
				$pkeys = (string) $this->decryptPrivKey((string)$pkeys); // {{{SYNC-ADM-AUTH-KEYS}}}
				if((string)$pkeys != '') {
					$pkeys = (string) $this->encryptPrivKey((string)$pkeys, (string)$pass); // {{{SYNC-ADM-AUTH-KEYS}}} :: re-encode keys with the new pass (not with hash which is visible in the DB !!!)
				} //end if
			} //end if
			//--
			$arr['keys'] = (string) $pkeys;
			//--
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'UPDATE `admins` '.$this->db->prepare_statement(
				(array) $arr,
				'update'
			).' '.$this->db->prepare_param_query('WHERE (`id` = ?)', [(string)$id])
		);
		$out = $wr[1];
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	public function updateAccount($id, $data) {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return -100;
		} //end if
		//--
		if(\Smart::random_number(0, 100) == 50) {
			$this->db->write_data('VACUUM');
		} //end if
		//--
		$data = (array) $data;
		//--
		if((string)$id == '') {
			return -10; // invalid username length
		} //end if
		//-- {{{SYNC-MOD-AUTH-EMAIL-VALIDATION}}}
		$data['email'] = (string) $data['email'];
		if((\strlen((string)$data['email']) < 6) OR (\strlen((string)$data['email']) > 96) OR (!\preg_match((string)\SmartValidator::regex_stringvalidation_expression('email'), (string)$data['email']))) {
			$data['email'] = null; // NULL, as the email is invalid
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		if($data['email'] === null) {
			$check_eml = array();
		} else {
			$check_eml = (array) $this->db->read_asdata(
				'SELECT `id` FROM `admins` WHERE ((`email` = ?) AND (`id` != ?)) LIMIT 1 OFFSET 0',
				array((string)$data['email'], (string)$id)
			);
		} //end if else
		if(\Smart::array_size($check_eml) > 0) {
			$out = -2; // duplicate email
		} else {
			$test = (array) $this->getById($id);
			if(\Smart::array_size($test) <= 0) {
				$out = -3; // invalid account id
			} else {
				$arr = [
					'modif' 	=> (int)    \time(),
					'name_f' 	=> (string) $data['name_f'],
					'name_l' 	=> (string) $data['name_l'],
					'email' 	=>          $data['email'] // mixed: false (NULL) or string
				];
				if((string)$id == (string)\SmartAuth::get_login_id()) { // for current logged in user
					if((string)$data['upd-keys'] == 'yes') {
						$arr['keys'] = (string) $this->encryptPrivKey((string)$data['keys']); // avoid update keys for other user since the password of other users in completely unknown, it is stored in an ireversible hash format
					} //end if
				} else { // for editing other users, if they have no restrict modify on privileges (superadmin have restrict modify !!!), update the privileges
					if(\strpos((string)$test['restrict'], '<modify>') === false) {
						$arr['priv'] = (array) $data['priv'];
					} //end if
				} //end if
				if(\array_key_exists('priv', $arr)) {
					$wr = $this->db->write_data(
						'UPDATE `admins` '.$this->db->prepare_statement(
							(array) $arr,
							'update'
						).' '.$this->db->prepare_param_query(
							'WHERE ((`id` = ?) AND (`restrict` NOT LIKE ?))',
							[
								(string) $id,
								(string) '%<modify>%' // {{{SYNC-EDIT-PRIVILEGES}}}
							]
						)
					);
				} else {
					$wr = $this->db->write_data(
						'UPDATE `admins` '.$this->db->prepare_statement(
							(array) $arr,
							'update'
						).' '.$this->db->prepare_param_query(
							'WHERE (`id` = ?)',
							[
								(string) $id
							]
						)
					);
				} //end if else
				$out = $wr[1];
			} //end if else
		} //end if else
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	//@@@@@


	private function initDBSchema() {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid AUTH DB Connection !');
			return 0;
		} //end if
		//--
		if($this->db->check_if_table_exists('admins') != 1) { // create auth DB if not exists
			//--
			if((\defined('\\APP_AUTH_ADMIN_USERNAME')) AND (\defined('\\APP_AUTH_ADMIN_PASSWORD'))) {
				//--
				$init_username = (string) \APP_AUTH_ADMIN_USERNAME;
				if(\SmartUnicode::str_len($init_username) < 3) {
					\http_response_code(203);
					die(\SmartComponents::http_error_message('INVALID USERNAME set as APP_AUTH_ADMIN_USERNAME', 'The username is too short, must be minimum 3 characters. Manually REFRESH this page after by pressing F5 ...'));
					return 0;
				} //end if
				$init_password = (string) \APP_AUTH_ADMIN_PASSWORD;
				if(\SmartUnicode::str_len($init_password) < 7) {
					\http_response_code(203);
					die(\SmartComponents::http_error_message('INVALID PASSWORD set as APP_AUTH_ADMIN_PASSWORD', 'The password is too short, must be minimum 7 characters. Manually REFRESH this page after by pressing F5 ...'));
					return 0;
				} //end if
				$init_hash_pass = \SmartHashCrypto::password($init_password, $init_username);
				//--
				$init_privileges = (string) '<superadmin>,<admin>,'.\APP_AUTH_PRIVILEGES;
				$init_privileges = \Smart::list_to_array((string)$init_privileges, true);
				$init_privileges = \Smart::array_to_list((array)$init_privileges);
				//--
				$this->db->write_data('BEGIN');
				$this->db->write_data((string)$this->dbDefaultSchema());
				$this->db->write_data("INSERT INTO `admins` VALUES ('".$this->db->escape_str((string)$init_username)."', '".$this->db->escape_str((string)$init_hash_pass)."', 1, 0, 'admin@localhost', 'Mr.', 'Super', 'Admin', '', '', '', '', '', '', '', 0, 0, 0, '".$this->db->escape_str((string)$init_privileges)."', '<modify>', '', '', 0, ".(int)\time().")");
				$this->db->write_data('COMMIT');
				//--
				\http_response_code(202);
				die(\SmartComponents::http_status_message('OK :: AUTH DB Initialized', \SmartComponents::operation_ok('Login Info: username={what is set into APP_AUTH_ADMIN_USERNAME} ; password={what is set into APP_AUTH_ADMIN_PASSWORD}.<br>Manually REFRESH this page after by pressing F5 ...', '98%')));
				return 0;
				//--
			} else {
				//--
				\http_response_code(208);
				die(\SmartComponents::http_error_message('Cannot Initialize the AUTH DB !', 'Please Set the APP_AUTH_ADMIN_USERNAME / APP_AUTH_ADMIN_PASSWORD constants in config and Manually REFRESH this page after by pressing F5 ...'));
				return 0;
				//--
			} //end if
			//--
		} //end if
		//--
		if($this->db->check_if_table_exists('authfail') != 1) {
			$this->db->write_data('BEGIN');
			$this->db->create_table( // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
				'authfail',
				"-- #START: table schema: authfail
				`id` character varying(25) NOT NULL,
				`ip_addr` character varying(39) NOT NULL,
				`tries` smallint NOT NULL,
				`trytime` bigint NOT NULL,
				`ua` text NOT NULL,
				PRIMARY KEY (`id`, `ip_addr`)
				-- #END: table schema",
				[ // indexes
					'authfail_id' 		=> '`id` ASC',
				//	'authfail_uuid' 	=> [ 'mode' => 'unique', 'index' => '`id` ASC, `ip_addr` ASC' ], // not necessary as they are primary key
					'authfail_tries' 	=> '`tries`'
				]
			);
			$this->db->write_data('COMMIT');
		} //end if
		//--
		return 1;
		//--
	} //END FUNCTION


	private function dbDefaultSchema() { // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
//-- default schema ; default user: APP_AUTH_ADMIN_USERNAME ; default pass: APP_AUTH_ADMIN_PASSWORD
$version = (string) $this->db->escape_str(\SMART_FRAMEWORK_RELEASE_TAGVERSION.' '.\SMART_FRAMEWORK_RELEASE_VERSION);
$schema = <<<SQL
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@auth-admins', '{$version}');
CREATE TABLE 'admins' (
	`id` character varying(25) PRIMARY KEY NOT NULL,
	`pass` character varying(128) NOT NULL,
	`active` smallint DEFAULT 0 NOT NULL,
	`quota` bigint DEFAULT 0 NOT NULL,
	`email` character varying(96) DEFAULT NULL NULL,
	`title` character varying(16) DEFAULT '' NOT NULL,
	`name_f` character varying(64) DEFAULT '' NOT NULL,
	`name_l` character varying(64) DEFAULT '' NOT NULL,
	`address` character varying(64) DEFAULT '' NOT NULL,
	`city` character varying(64) DEFAULT '' NOT NULL,
	`region` character varying(64) DEFAULT '' NOT NULL,
	`country` character varying(2) DEFAULT '' NOT NULL,
	`zip` character varying(64) DEFAULT '' NOT NULL,
	`phone` character varying(32) DEFAULT '' NOT NULL,
	`ip_addr` character varying(39) DEFAULT '' NOT NULL,
	`logintime` bigint DEFAULT 0 NOT NULL,
	`tries` smallint DEFAULT 0 NOT NULL,
	`trytime` bigint DEFAULT 0 NOT NULL,
	`priv` text DEFAULT '' NOT NULL,
	`restrict` text DEFAULT '' NOT NULL,
	`settings` text DEFAULT '' NOT NULL,
	`keys` text DEFAULT '' NOT NULL,
	`modif` INTEGER DEFAULT 0 NOT NULL,
	`created` INTEGER DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX 'admins_id' ON `admins` (`id` ASC);
CREATE UNIQUE INDEX 'admins_email' ON `admins` (`email`);
CREATE INDEX 'admins_active' ON `admins` (`active`);
CREATE INDEX 'admins_modif' ON `admins` (`modif`);
CREATE INDEX 'admins_created' ON `admins` (`created`);
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
