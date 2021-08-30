<?php
// Class: \SmartModDataModel\AuthAdmins\SqAuthAdmins
// (c) 2006-2021 unix-world.org - all rights reserved
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
 * SQLite Model for ModAuthAdmins
 * @ignore
 */
final class SqAuthAdmins {

	// ->
	// v.20210830

	private $db;

	public const FAIL_LOGIN_LIMITS = 10;


	public function __construct() { // THIS SHOULD BE THE ONLY METHOD IN THIS CLASS THAT THROW EXCEPTIONS !!!
		//--
		if(!\defined('\\APP_AUTH_DB_SQLITE')) {
			throw new \Exception('AUTH DB SQLITE is NOT Defined !');
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
			throw new \Exception('AUTH DB SQLITE File does NOT Exists !');
			return;
		} //end if
		//--
		$init_schema = $this->initDBSchema(); // mixed
		if($init_schema !== null) { // create default schema if not exists (and a default account)
			throw new \Exception('DB Init Schema Failed with Message: '.$init_schema);
		} //end if
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


	public function getLoginData(string $auth_user_name, string $auth_user_pass) : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return array();
		} //end if
		//--
		if(\SmartAuth::validate_auth_username(
			(string) $auth_user_name
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return array();
		} //end if
		if(\SmartAuth::validate_auth_password(
			(string) $auth_user_pass
		) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			return array();
		} //end if
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT * FROM `admins` WHERE ((`id` = ?) AND (`active` = 1)) LIMIT 1 OFFSET 0',
			[
				(string) $auth_user_name
			]
		);
		if((int)\Smart::array_size($arr) <= 0) {
			return array();
		} //end if
		$arr['pass'] = (string) \trim((string)($arr['pass'] ?? null));
		if((string)$arr['pass'] == '') {
			return array();
		} //end if
		if(\SmartHashCrypto::checkpassword((string)$arr['pass'], (string)$auth_user_pass, (string)$auth_user_name) !== true) {
			return array();
		} //end if
		//--
		return (array) $arr; // OK
		//--
	} //END FUNCTION


	public function checkFailLoginData(string $id, string $pass, string $ip) : int {
		//--
		$ip = (string) \trim((string)$ip);
		$id = (string) \trim((string)$id);
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT SUM(`tries`) AS `tot_tries`, MAX(`trytime`) AS `max_trytime` FROM `authfail` WHERE (`ip_addr` = ?) LIMIT 1 OFFSET 0',
			[
				(string) $ip
			]
		);
		//--
		if(\Smart::array_size($arr) <= 0) {
			return 0;
		} //end if
		//--
		if((int)$arr['tot_tries'] <= (int)self::FAIL_LOGIN_LIMITS) {
			return 0;
		} //end if
		if((int)$arr['max_trytime'] <= 0) {
			return 0;
		} //end if
		//--
		$test_arr = (array) $this->getLoginData((string)$id, (string)$pass);
		if((int)\Smart::array_size($test_arr) > 0) { // successful login after a timeout
			return 0;
		} //end if
		$test_arr = null;
		//--
		$nowtime 	= (int) \time();
		$allowtime 	= (int) ((int)$arr['max_trytime'] + (((int)$arr['tot_tries'] - (int)self::FAIL_LOGIN_LIMITS) * 60));
		if((int)$nowtime >= (int)$allowtime) {
			return 0;
		} //end if
		//--
		return (int) $allowtime;
		//--
	} //END FUNCTION


	public function logSuccessfulLoginData(string $id, string $ip) : bool {
		//--
		$ip = (string) \trim((string)$ip);
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return false;
		} //end if
		//-- get by id
		$arr = (array) $this->db->read_asdata(
			'SELECT SUM(`tries`) AS `tot_tries`, MAX(`trytime`) AS `max_trytime` FROM `authfail` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			[
				(string) $id
			]
		);
		//-- delete by ip
		$del = (array) $this->db->write_data(
			'DELETE FROM `authfail` WHERE ((`ip_addr` = ?) OR (`trytime` <= ?))',
			[
				(string) $ip,
				(int)    ((int)\time() - (int)(60 * 60 * 24)), // cleanup old entries, older than 24 hours
			]
		);
		//-- upd by id
		$upd = [];
		if((int)$arr['tot_tries'] > 0) {
			$upd = (array) $this->db->write_data(
				'UPDATE `admins` SET `ip_addr` = ?, `logintime` = ?, `tries` = ?, `trytime` = ? WHERE (`id` = ?)',
				[
					(string) $ip,
					(int)    \time(),
					(int)    $arr['tot_tries'],
					(int)    $arr['max_trytime'],
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


	public function logUnsuccessfulLoginData(string $id, string $ip, string $ua) : bool {
		//--
		$ip = (string) \trim((string)$ip);
		$ua = (string) \trim((string)$ua);
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return false;
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if(\SmartAuth::validate_auth_username(
			(string) $id
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			$id = '';
		} else {
			$arr = (array) $this->getById((string)$id);
			if((int)\Smart::array_size($arr) <= 0) {
				$id = '';
			} //end if
			$arr = null;
		} //end if
		//--
		\SmartFileSystem::write(
			'tmp/logs/adm/'.\Smart::safe_filename('auth-fail-'.\date('Y-m-d@H').'.log'),
			'[FAIL]'."\t".\Smart::normalize_spaces((string)\date('Y-m-d H:i:s O'))."\t".\Smart::normalize_spaces((string)$id)."\t".\Smart::normalize_spaces((string)$ip)."\t".\Smart::normalize_spaces((string)$ua)."\n",
			'a'
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


	public function getById(string $id) : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return array();
		} //end if
		//--
		return (array) $this->db->read_asdata(
			'SELECT * FROM `admins` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			[
				(string) $id
			]
		);
		//--
	} //END FUNCTION


	public function countByFilter(string $id='') : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return 0;
		} //end if
		//--
		$where = '';
		$params = '';
		//--
		$id = (string) \trim((string)$id);
		$id = (string) \trim((string)$id. '%');
		$id = (string) \trim((string)$id);
		if((string)$id != '') {
			$where = ' WHERE (`id` LIKE ?)';
			$params = array($id.'%');
		} //end if else
		//--
		return (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `admins`'.$where,
			$params
		);
		//--
	} //END FUNCTION


	public function getListByFilter(array $fields=[], int $limit=10, int $ofs=0, string $sortby='id', string $sortdir='ASC', string $id='') : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
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
		$id = (string) \trim((string)$id);
		$id = (string) \trim((string)$id. '%');
		$id = (string) \trim((string)$id);
		if((string)$id != '') {
			$where = ' WHERE (`id` LIKE ?)';
			$params = array($id.'%');
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


	public function insertAccount(array $data, bool $active=false) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
			return -100;
		} //end if
		//--
		$this->db->write_data('VACUUM');
		//--
		$data = (array) $data;
		$data['id'] = (string) \Smart::safe_username((string)\trim((string)($data['id'] ?? null)));
		$data['pass'] = (string) \trim((string)($data['pass'] ?? null));
		$data['email'] = (string) \trim((string)($data['email'] ?? null));
		$data['name_f'] = (string) \trim((string)($data['name_f'] ?? null));
		$data['name_l'] = (string) \trim((string)($data['name_l'] ?? null));
		$data['priv'] = ($data['priv'] ?? null); // mixed: array or string
		$data['restrict'] = (string) \trim((string)($data['restrict'] ?? null));
		//--
		if($active !== true) {
			$active = false;
		} //end if
		//--
		if(((string)$data['id'] == '') OR ((string)$data['pass'] == '')) {
			return -10; // empty username or password
		} //end if
		if(\SmartAuth::validate_auth_username(
			(string) $data['id']
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return -11; // invalid username
		} //end if
		if(\SmartAuth::validate_auth_password(
			(string) $data['pass']
		) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}} ; don't check complexity here !
			return -12; // invalid password
		} //end if
		//--
		$data['pass'] = (string) \trim((string)\SmartHashCrypto::password((string)$data['pass'], (string)$data['id']));
		if((int)\strlen((string)$data['pass']) !== (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) {
			return -13; // invalid password, it have to be a fixed length as defined
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
						'priv' 		=> (string) $data['priv'],
						'restrict' 	=> (string) $data['restrict'],
						'created' 	=> (int)    \time(),
						'active' 	=> (int)    $active,
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


	public function updateStatus(string $id, int $status) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
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


	public function decryptPrivKey(?string $pkey_enc) : string { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		$pass = (string) \SmartAuth::get_login_password();
		//--
		if((string)\trim((string)$pass) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartAuth::decrypt_privkey((string)$pkey_enc, (string)$pass);
		//--
	} //END FUNCTION


	public function encryptPrivKey(?string $pkey_plain, ?string $newpass=null) : string { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		if($newpass !== null) {
			$pass = (string) $newpass; // used only when a user changes his/her account password by him(her)self ; if so, re-encrypt the private key with this new password
		} else {
			$pass = (string) \SmartAuth::get_login_password();
		} //end if else
		//--
		if((string)\trim((string)$pass) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartAuth::encrypt_privkey((string)$pkey_plain, (string)$pass);
		//--
	} //END FUNCTION


	public function updatePassword(string $id, string $pass) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
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
		$rd_arr = (array) $this->getById((string)$id);
		if((int)\Smart::array_size($rd_arr) <= 0) {
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
		if(\SmartAuth::validate_auth_password(
			(string) $pass
		) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}} ; don't check complexity here !
			return -12; // invalid password
		} //end if
		$hash = (string) (string) \trim((string)\SmartHashCrypto::password((string)$pass, (string)$id));
		if((int)\strlen((string)$hash) !== (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) {
			return -13; // invalid password, it have to be a fixed length as defined
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
		$out = (int) $wr[1];
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	public function updateAccount(string $id, array $data) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Invalid AUTH DB Connection !');
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
		//--
		$data['email'] = (string) \trim((string)($data['email'] ?? null));
		$data['name_f'] = (string) \trim((string)($data['name_f'] ?? null));
		$data['name_l'] = (string) \trim((string)($data['name_l'] ?? null));
		$data['priv'] = ($data['priv'] ?? null); // mixed: array or string
		$data['upd-keys'] = (string) ($data['upd-keys'] ?? null);
		$data['keys'] = (string) \trim((string)($data['keys'] ?? null));
		//-- {{{SYNC-MOD-AUTH-EMAIL-VALIDATION}}}
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
			$test = (array) $this->getById((string)$id);
			if((int)\Smart::array_size($test) <= 0) {
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


	//======= [ PRIVATES ]


	private function initDBSchema() : ?string {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			return 'Invalid AUTH DB Connection !';
		} //end if
		//--
		if($this->db->check_if_table_exists('admins') != 1) { // create auth DB if not exists
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
				"-- #START: table schema: authfail
				`id` character varying(25) NOT NULL,
				`ip_addr` character varying(39) NOT NULL,
				`tries` bigint NOT NULL,
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
		return null;
		//--
	} //END FUNCTION


	private function dbDefaultSchema() : string { // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
//-- default schema
$version = (string) $this->db->escape_str((string)\SMART_FRAMEWORK_RELEASE_TAGVERSION.' '.\SMART_FRAMEWORK_RELEASE_VERSION);
$passlen = (int) \SmartHashCrypto::PASSWORD_HASH_LENGTH;
$schema = <<<SQL
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@auth-admins', '{$version}');
CREATE TABLE 'admins' (
	`id` character varying(25) PRIMARY KEY NOT NULL,
	`pass` character varying({$passlen}) NOT NULL,
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
