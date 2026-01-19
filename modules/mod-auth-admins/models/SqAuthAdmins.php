<?php
// Abstract Class: \SmartModDataModel\AuthAdmins\SqAuthAdmins
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
 * SQLite Model for ModAuthAdmins (Abstract)
 * @ignore
 */
abstract class SqAuthAdmins extends \SmartModDataModel\AuthAdmins\AbstractAuthAdmins {

	// ->
	// v.20260118

	private $db;
	private $dbFile;

	private const ERR_NO_CONNECTION = 'Invalid AUTH DB Connection !';


	final public function __construct(bool $initdb=true) { // THIS SHOULD BE THE ONLY METHOD IN THIS CLASS THAT THROW EXCEPTIONS !!!
		//--
		if(!\SmartEnvironment::isAdminArea()) {
			throw new \Exception('AUTH DB can operate under admin/task area only !');
			return;
		} //end if
		//--
		if(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			throw new \Exception('AUTH DB SQLITE: Secret is Undefined');
			return;
		} //end if
		if((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '') {
			throw new \Exception('AUTH DB SQLITE: Secret is Empty');
			return;
		} //end if
		//--
		$dbPath =  (string) \SmartFileSysUtils::APP_DB_FOLDER.'auth-admins-'.\SmartHashCrypto::safesuffix('Mod.AuthAdmins').'.sqlite'; // {{{SYNC-APP-DB-FOLDER}}}
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dbPath, true, true)) { // {{{SYNC-AUTHDB-CHECK-DB-SAFE-PATH}}}
			throw new \Exception('AUTH DB SQLITE File Path is Unsafe !');
			return;
		} //end if
		$this->dbFile = (string) $dbPath;
		//--
		if($initdb !== false) {
			$err = (string) $this->dbConnect();
			if((string)$err != '') {
				throw new \Exception((string)$err);
				return;
			} //end if
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


	final public function dbExists() : bool {
		//--
		if((string)\trim((string)$this->dbFile) == '') {
			return false;
		} //end if
		if(!\SmartFileSysUtils::checkIfSafePath((string)$this->dbFile, true, true)) { // {{{SYNC-AUTHDB-CHECK-DB-SAFE-PATH}}}
			return false;
		} //end if
		//--
		if(!\SmartFileSystem::is_type_file((string)$this->dbFile)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	final public function getLoginData(string $auth_user_name, string $auth_pass_hash) : array {
		//--
		// the combination of UserName/PassHash must match an account which is marked also as ACTIVE
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
		} //end if
		//--
		if(\SmartAuth::validate_auth_username(
			(string) $auth_user_name,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return [];
		} //end if
		//--
		if((string)\trim((string)$auth_pass_hash) == '') {
			return [];
		} //end if
		//--
		if((int)\strlen((string)$auth_pass_hash) != (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) { // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			return [];
		} //end if
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT * FROM `admins` WHERE ((`id` = ?) AND (`pass` = ?) AND (`active` = 1)) LIMIT 1 OFFSET 0',
			[
				(string) $auth_user_name,
				(string) $auth_pass_hash,
			]
		);
		if((int)\Smart::array_size($arr) <= 0) {
			return [];
		} //end if
		$arr['pass'] = (string) \trim((string)($arr['pass'] ?? null));
		if((string)$arr['pass'] == '') {
			return [];
		} //end if
		if((int)\strlen((string)$arr['pass']) != (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) { // {{{SYNC-AUTH-HASHPASS-LENGTH}}}
			return [];
		} //end if
		if((string)$arr['pass'] !== (string)$auth_pass_hash) {
			return [];
		} //end if
		//--
		return (array) $arr; // OK
		//--
	} //END FUNCTION


	//===== Management


	final public function getById(string $id) : array {
		//--
		if((string)\trim((string)$id) == '') {
			return [];
		} //end if
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
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


	final public function insertAccount(array $data, bool $active=false) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
		} //end if
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
			(string) $data['id'],
			true // check for reasonable length, as 5 chars
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
		//-- {{{SYNC-MOD-AUTH-EMAIL-VALIDATION}}} ; {{{SYNC-SMART-EMAIL-LENGTH}}}
		if((\strlen((string)$data['email']) < 7) OR (\strlen((string)$data['email']) > 72) OR (!\preg_match((string)\SmartValidator::regex_stringvalidation_expression('email'), (string)$data['email']))) {
			$data['email'] = null; // NULL, as the email is invalid
		} //end if
		//--
		$out = -1;
		//--
		$cnt = (int) $this->db->count_data('SELECT COUNT(1) FROM `admins`');
		if((int)$cnt >= (int)self::MAX_ADMIN_ACCOUNTS) { // hardcoded admin accounts limit
			return (int) $out;
		} //end if
		//--
		$firstLetter = (string) substr((string)$data['id'], 0, 1);
		$cntPerLetter = (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `admins` WHERE (`id` LIKE ?)',
			[ (string)$this->db->quote_likes((string)$firstLetter).'%' ]
		);
		if((int)$cntPerLetter >= (int)self::MAX_ADMIN_START_LETTER_ACCOUNTS) { // hardcoded admin accounts limit that may start with one letter
			return (int) $out;
		} //end if
		//--
		$this->db->write_data('BEGIN');
		//--
		$check_id = (array) $this->db->read_asdata(
			'SELECT `id` FROM `admins` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			[ (string)$data['id'] ]
		);
		if($data['email'] === null) {
			$check_eml = [];
		} else {
			$check_eml = (array) $this->db->read_asdata(
				'SELECT `id` FROM `admins` WHERE (`email` = ?) LIMIT 1 OFFSET 0',
				[ (string)$data['email'] ]
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
						'fa2' 		=> (string) '',
						'ip_addr' 	=> (string) \SmartUtils::get_ip_client(),
					],
					'insert'
				)
			);
			$out = (int) $wr[1];
			$wr = null;
		} //end if else
		//--
		$this->db->write_data('COMMIT');
		//--
		$this->db->write_data('VACUUM');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function enableAccount2FA(string $id, string $fa2_secret, string $fa2_pin) : int {
		//--
		if((string)$id == '') {
			return -10; // invalid username length
		} //end if
		//--
		$fa2_secret = (string) \trim((string)$fa2_secret); // req. a code generated by: Auth2FTotp::GenerateSecret()
		if((string)$fa2_secret == '') {
			return -11;
		} //end if
		//--
		$fa2_pin = (string) \trim((string)$fa2_pin);
		if(((int)\strlen((string)$fa2_pin) < 4) || ((int)\strlen((string)$fa2_pin) > 16) || (!\preg_match((string)\SmartModExtLib\AuthAdmins\Auth2FTotp::REGEX_VALID_TOTP_CODE, (string)$fa2_pin))) {
			return -12; // {{{SYNC-TOTP-PIN-LENGTH-CHECK}}}
		} //end if
		//--
		if((string)$this->get2FAPinToken((string)$fa2_secret) != (string)$fa2_pin) {
			return -21; // {{{SYNC-ENABLE-2FA-INVALID-CODE-ERR}}} ; the code must be verified that is a working code before enabling this ...
		} //end if
		//--
		$wr = (array) $this->db->write_data(
			'UPDATE `admins` SET `fa2` = ? WHERE ((`id` = ?) AND (`fa2` = ?))',
			[
				(string) $this->encrypt2FAKey((string)$fa2_secret, (string)$id),
				(string) $id,
				'',
			]
		);
		$out = (int) ($wr[1] ?? null);
		$wr = null;
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function disableAccount2FA(string $id) : int {
		//--
		if((string)$id == '') {
			return -10; // invalid username length
		} //end if
		//--
		$wr = (array) $this->db->write_data(
			'UPDATE `admins` SET `fa2` = ? WHERE ((`id` = ?) AND (`fa2` != ?))',
			[
				(string) '',
				(string) $id,
				'',
			]
		);
		$out = (int) ($wr[1] ?? null);
		$wr = null;
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function deleteAccount(string $id) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
		} //end if
		//--
		if((string)\trim((string)$id) == '') {
			return -10;
		} //end if
		//--
		$this->db->write_data('BEGIN');
		//--
		$del = (array) $this->db->write_data(
			'DELETE FROM `admins` WHERE (`id` = ?)',
			[
				(string) $id
			]
		);
		//--
		$out = (int) $del[1];
		$del = null;
		//--
		$this->db->write_data('COMMIT');
		//--
		$this->db->write_data('VACUUM');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function updateAccount(string $id, array $data) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
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
		//-- {{{SYNC-MOD-AUTH-EMAIL-VALIDATION}}} ; {{{SYNC-SMART-EMAIL-LENGTH}}}
		if((\strlen((string)$data['email']) < 7) OR (\strlen((string)$data['email']) > 72) OR (!\preg_match((string)\SmartValidator::regex_stringvalidation_expression('email'), (string)$data['email']))) {
			$data['email'] = null; // NULL, as the email is invalid
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		if($data['email'] === null) {
			$check_eml = [];
		} else {
			$check_eml = (array) $this->db->read_asdata(
				'SELECT `id` FROM `admins` WHERE ((`email` = ?) AND (`id` != ?)) LIMIT 1 OFFSET 0',
				[ (string)$data['email'], (string)$id ]
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
					'ip_addr' 	=> (string) \SmartUtils::get_ip_client(),
					'modif' 	=> (int)    \time(),
					'name_f' 	=> (string) $data['name_f'],
					'name_l' 	=> (string) $data['name_l'],
					'email' 	=>          $data['email'] // mixed: false (NULL) or string
				];
				if((string)$id == (string)\SmartAuth::get_auth_id()) { // current account only ; do not update keys except for current logged in user only
					if((string)$data['upd-keys'] == 'yes') {
						$arr['keys'] = (string) $this->encryptSecretKey((string)$data['keys']); // avoid update keys for other user since the password of other users is completely unknown, it is stored in an ireversible hash format
					} //end if
					// disallow self user edit privileges !
				} else { // for editing other users, if they have superadmin privileges, they can except if is the default account
					if(\SmartAuth::test_login_restriction('def-account', (string)($test['restrict'] ?? null)) !== true) { // {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-AUTH-RESTRICTIONS}}}
						$arr['priv'] = (array) $data['priv'];
					} //end if
				} //end if
				$wr = [];
				if(\array_key_exists('priv', $arr)) {
					$wr = (array) $this->db->write_data(
						'UPDATE `admins` '.$this->db->prepare_statement(
							(array) $arr,
							'update'
						).' '.$this->db->prepare_param_query(
							'WHERE ((`id` = ?) AND (`restrict` NOT LIKE ?))',
							[
								(string) $id,
								(string) '%<def-account>%' // {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-AUTH-RESTRICTIONS}}}
							]
						)
					);
				} else {
					$wr = (array) $this->db->write_data(
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
				$out = (int) ($wr[1] ?? null);
				$wr = null;
			} //end if else
		} //end if else
		//--
		$this->db->write_data('COMMIT');
		//--
		if(\Smart::random_number(0, 100) == 51) { // just 1% of cases
			$this->db->write_data('VACUUM');
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function updateStatus(string $id, int $status) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
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
		$wr = (array) $this->db->write_data(
			'UPDATE `admins` '.$this->db->prepare_statement(
				(array) [
					'ip_addr' 	=> (string) \SmartUtils::get_ip_client(),
					'modif' 	=> (int)    \time(),
					'active' 	=> (int)    $status,
				],
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE ((`id` = ?) AND (`restrict` NOT LIKE ?))',
				[
					(string) $id,
					(string) '%<def-account>%' // {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-AUTH-RESTRICTIONS}}}
				]
			)
		);
		$out = (int) $wr[1];
		$wr = null;
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function updatePassword(string $id, string $pass) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
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
		$pkeys = (string) \trim((string)($rd_arr['keys'] ?? null));
		$ohash = (string) \trim((string)($rd_arr['pass'] ?? null));
		//--
		$rd_arr = null;
		//--
		if(\SmartAuth::validate_auth_password(
			(string) $pass
		) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}} ; don't check complexity here !
			return -12; // invalid password
		} //end if
		$hash = (string) \trim((string)\SmartHashCrypto::password((string)$pass, (string)$id));
		if((int)\strlen((string)$hash) !== (int)\SmartHashCrypto::PASSWORD_HASH_LENGTH) {
			return -13; // invalid password, it have to be a fixed length as defined
		} //end if
		//--
		$arr = [
			'ip_addr' 	=> (string) \SmartUtils::get_ip_client(),
			'modif' 	=> (int)    \time(),
			'pass' 		=> (string) $hash
		];
		//--
		if((string)$pkeys != '') {
			$pkeys = (string) $this->decryptSecretKey((string)$pkeys, (string)$ohash); // {{{SYNC-ADM-AUTH-KEYS}}}
			if((string)$pkeys != '') {
				$pkeys = (string) $this->encryptSecretKey((string)$pkeys, (string)$hash); // {{{SYNC-ADM-AUTH-KEYS}}} :: re-encode keys with the new pass (not with hash which is visible in the DB !!!)
			} //end if
		} //end if
		//--
		$arr['keys'] = (string) $pkeys;
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = (array) $this->db->write_data(
			'UPDATE `admins` '.$this->db->prepare_statement(
				(array) $arr,
				'update'
			).' '.$this->db->prepare_param_query('WHERE (`id` = ?)', [(string)$id])
		);
		$out = (int) $wr[1];
		$wr = null;
		//--
		$this->db->write_data('COMMIT');
		//--
		if(\Smart::random_number(0, 100) == 51) { // just 1% of cases
			$this->db->write_data('VACUUM');
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function countByFilter(string $id='', bool $strict=false) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return 0;
		} //end if
		//--
		$where = '';
		$params = [];
		$id = (string) \trim((string)$id);
		if((bool)$strict === false) {
			if((string)$id != '') {
				$where = ' WHERE (`id` LIKE ?)';
				$params = [ (string)$this->db->quote_likes((string)$id).'%' ];
			} //end if
		} else {
			$where = ' WHERE (`id` = ?)';
			$params = [ (string)$id ];
		} //end if else
		//--
		return (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `admins`'.$where,
			(array) $params
		);
		//--
	} //END FUNCTION


	final public function getListByFilter(array $fields=[], int $limit=10, int $ofs=0, string $sortby='id', string $sortdir='ASC', string $id='', bool $strict=false) : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
		} //end if
		//--
		if(\Smart::array_size($fields) > 0) {
			$tmp_arr = (array) $fields;
			$fields = [];
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
		$params = [];
		$id = (string) \trim((string)$id);
		if((bool)$strict === false) {
			if((string)$id != '') {
				$where = ' WHERE (`id` LIKE ?)';
				$params = [ (string)$this->db->quote_likes((string)$id).'%' ];
			} //end if
		} else {
			$where = ' WHERE (`id` = ?)';
			$params = [ (string)$id ];
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
			(array) $params
		);
		//--
	} //END FUNCTION


	//-------- Tokens


	final public function getLoginActiveTokenByIdAndKey(string $id, string $token_key) : array {
		//--
		// for login access only ; will get just active tokens
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return [];
		} //end if
		//--
		$token_key = (string) trim((string)$token_key);
		if((string)$token_key == '') {
			return [];
		} //end if
		//--
		return (array) $this->db->read_asdata(
			'SELECT * FROM `authtokens` WHERE ((`id` = ?) AND (`active` = 1) AND (`token_hash` = ?)) LIMIT 1 OFFSET 0',
			[
				(string) $id,
				(string) \SmartModExtLib\AuthAdmins\AuthTokens::createHexHash((string)$id, (string)$token_key),
			]
		);
		//--
	} //END FUNCTION


	final public function getTokenByIdAndHash(string $id, string $token_hash) : array {
		//--
		// for internal access only
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return [];
		} //end if
		//--
		$token_hash = (string) trim((string)$token_hash);
		if((string)$token_hash == '') {
			return [];
		} //end if
		//--
		return (array) $this->db->read_asdata(
			'SELECT * FROM `authtokens` WHERE ((`id` = ?) AND (`token_hash` = ?)) LIMIT 1 OFFSET 0',
			[
				(string) $id,
				(string) $token_hash,
			]
		);
		//--
	} //END FUNCTION


	final public function insertToken(array $data) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
		} //end if
		//--
		$data = (array) $data;
		$data['id'] = (string) \trim((string)($data['id'] ?? null));
		$data['active'] = (int) ($data['active'] ?? null);
		if((int)$data['active'] != 1) {
			$data['active'] = 0; // inactive
		} //end if
		$data['expires'] = (int) ($data['expires'] ?? null);
		if((int)$data['expires'] < 0) {
			$data['expires'] = 0; // inactive
		} //end if
		$data['token_priv'] = (string) \trim((string)($data['token_priv'] ?? null));
		$data['token_name'] = (string) \trim((string)($data['token_name'] ?? null));
		if(
			((string)$data['id'] == '')
			OR
			((string)$data['token_priv'] == '')
			OR
			((string)$data['token_name'] == '')
			OR
			(
				((int)$data['expires'] < 0)
			)
		) {
			return -10; // empty username or token name or invalid expire
		} //end if
		if(\SmartAuth::validate_auth_username(
			(string) $data['id'],
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			return -11; // invalid username
		} //end if
		$token_seed = (string) \SmartModExtLib\AuthAdmins\AuthTokens::generatePrivateSeed();
		$token_key  = (string) \SmartModExtLib\AuthAdmins\AuthTokens::createPublicPassKey((string)$data['id'], (string)$token_seed);
		if((string)\trim((string)$token_key) == '') {
			return -12; // invalid token key
		} //end if
		$this->db->write_data('BEGIN');
		$check_id = (array) $this->getById((string)$data['id']);
		$num_tokens = (int) $this->countTokensById((string)$data['id']);
		$out = -1;
		if(\Smart::array_size($check_id) <= 0) {
			$out = -2; // invalid ID
		} elseif((int)$num_tokens >= (int)self::MAX_TOKENS_PER_ACCOUNT) { // {{{SYNC-AUTH-ADM-MAX-TOKENS-PER-ACCOUNT}}} ; max num of tokens per ID must be limited to: (int)self::MAX_TOKENS_PER_ACCOUNT
			$out = -3; // max tokens limit reached
		} else {
			$token_hash = (string) \SmartModExtLib\AuthAdmins\AuthTokens::createHexHash((string)$data['id'], (string)$token_key);
			if(
				((string)\trim((string)$token_hash) == '')
				OR
				((int)\strlen((string)$token_hash) !== 128) // 128 hex
				OR
				(!\preg_match((string)\Smart::REGEX_SAFE_HEX_STR, (string)$token_hash)) // {{{SYNC-STK-128BIT-HEXHASH}}}
			) {
				$out = -4; // invalid or empty token hash (hex)
			} else {
				$check_token = (array) $this->getTokenByIdAndHash((string)$data['id'], (string)$token_hash);
				if(\Smart::array_size($check_token) > 0) { // prevent duplicates
					$out = -5; // duplicate token
				} else {
					$stk = (array) \SmartModExtLib\AuthAdmins\AuthTokens::createSTKData(
						(string) $data['id'],
						(int)    $data['expires'],
						(string) $data['token_priv'],
						(string) $token_seed
					);
					if(
						((string)($stk['error'] ?? null) != '')
						OR
						(($stk['ernum'] ?? null) !== 0)
						OR
						((string)\trim((string)($stk['token'] ?? null)) == '')
					) {
						$out = (int) (-600 + (int)($stk['ernum'] ?? null)); // token creation failed for some reason
						\Smart::log_notice(__METHOD__.' # STK Token Creation Failed: '.($stk['error'] ?? null));
					} else {
						$token_data 	= (string) \SmartModExtLib\AuthAdmins\AuthTokens::encryptSTKData((string)$data['id'], (string)$token_hash, (string)($stk['token'] ?? null));
						$token_checksum = (string) \SmartModExtLib\AuthAdmins\AuthTokens::createChecksum((string)$token_hash, (string)$token_data);
						$valid_token 	= false;
						$test_dec 		= (string) \SmartModExtLib\AuthAdmins\AuthTokens::decryptSTKData((string)$data['id'], (string)$token_hash, (string)$token_data, (string)$token_checksum);
						$test_valid_stk = [];
						if((string)\trim((string)$test_dec) == '') {
							$test_valid_stk = [
								'error' => 'Token Validation Decryption Failed',
								'ernum' => 38,
							];
						} else {
							$test_valid_stk = (array) \SmartModExtLib\AuthAdmins\AuthTokens::validateSTKData((string)$data['id'], (int)$data['expires'], (string)$test_dec);
							if(((string)$test_valid_stk['error'] == '') AND ($test_valid_stk['ernum'] === 0)) {
								$valid_token = true;
							} //end if
						} //end if
						if($valid_token !== true) {
							$out = (int) (-700 + (int)($test_valid_stk['ernum'] ?? null)); // token creation failed for some reason
							\Smart::log_notice(__METHOD__.' # STK Token Validation Failed: '.($test_valid_stk['error'] ?? null));
						} else {
							$wr = (array) $this->db->write_data(
								'INSERT INTO `authtokens` '.$this->db->prepare_statement(
									[
										'id' 			=> (string) $data['id'],
										'active' 		=> (int)    $data['active'], // 0 or 1
										'expires' 		=> (int)    $data['expires'],
										'token_hash' 	=> (string) $token_hash,
										'token_cksum' 	=> (string) $token_checksum,
										'token_data' 	=> (string) $token_data,
										'token_name' 	=> (string) $data['token_name'],
										'created' 		=> (int)    \time(),
									],
									'insert'
								)
							);
							$out = (int) $wr[1];
							$wr = null;
						} //end if else
					} //end if else
				} //end if else
			} //end if else
		} //end if else
		$this->db->write_data('COMMIT');
		//--
		$this->db->write_data('VACUUM');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function deleteTokenByIdAndHash(string $id, string $token_hash) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return -100;
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return -10;
		} //end if
		//--
		$token_hash = (string) trim((string)$token_hash);
		if((string)$token_hash == '') {
			return -20;
		} //end if
		//--
		$this->db->write_data('BEGIN');
		//--
		$del = (array) $this->db->write_data(
			'DELETE FROM `authtokens` WHERE ((`id` = ?) AND (`token_hash` = ?))',
			[
				(string) $id,
				(string) $token_hash,
			]
		);
		//--
		$out = (int) $del[1];
		$del = null;
		//--
		$this->db->write_data('COMMIT');
		//--
		$this->db->write_data('VACUUM');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function updateTokenStatus(string $id, string $token_hash, int $status) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
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
		$wr = (array) $this->db->write_data(
			'UPDATE `authtokens` '.$this->db->prepare_statement(
				(array) [
					'active' => (int) $status,
				],
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE ((`id` = ?) AND (`token_hash` = ?))',
				[
					(string) $id,
					(string) $token_hash,
				]
			)
		);
		$out = (int) $wr[1];
		$wr = null;
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	final public function countTokensById(string $id) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return 0;
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return 0;
		} //end if
		//--
		return (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `authtokens` WHERE (`id` = ?)',
			[ (string)$id ]
		);
		//--
	} //END FUNCTION


	final public function getTokensListById(string $id) : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # '.self::ERR_NO_CONNECTION);
			return [];
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return [];
		} //end if
		//--
		return (array) $this->db->read_adata(
			'SELECT * FROM `authtokens` WHERE (`id` = ?) ORDER BY `created` DESC LIMIT '.(int)((int)self::MAX_TOKENS_PER_ACCOUNT * 2).' OFFSET 0', // {{{SYNC-AUTH-ADM-MAX-TOKENS-PER-ACCOUNT}}} ; keep this hardcoded limit as double of max tokens per user
			[ (string)$id ]
		);
		//--
	} //END FUNCTION


	//======== [ PRIVATES ]


	private function dbConnect() : string {
		//--
		if(!!$this->db instanceof \SmartSQliteDb) {
			\Smart::log_warning(__METHOD__.' # Connect called more than once');
			return '';
		} //end if
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$this->dbFile, true, true)) { // {{{SYNC-AUTHDB-CHECK-DB-SAFE-PATH}}}
			\Smart::log_warning(__METHOD__.' # DB Path is Empty or Unsafe: `'.$this->dbFile.'`');
			return '';
		} //end if
		//--
		$this->db = new \SmartSQliteDb((string)$this->dbFile);
		$this->db->open();
		//--
		if(!\SmartFileSystem::is_type_file((string)$this->dbFile)) {
			if($this->db instanceof \SmartSQliteDb) {
				$this->db->close();
			} //end if
			return 'AUTH DB SQLITE File does NOT Exists !';
		} //end if
		//--
		$init_schema = $this->initDBSchema(); // null or string
		if($init_schema !== null) { // create default schema if not exists (and a default account)
			return 'AUTH DB Init Schema Failed with Message: '.$init_schema;
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


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
		if($this->db->check_if_table_exists('authtokens') != 1) {
			$this->db->write_data('BEGIN');
			$this->db->create_table( // {{{SYNC-TABLE-AUTH_TEMPLATE}}}
				'authtokens',
				"-- #START: table schema: authtokens @ 20250207
				`id` character varying(25) NOT NULL,
				`active` smallint DEFAULT 0 NOT NULL,
				`expires` bigint DEFAULT 0 NOT NULL,
				`token_hash` character varying(128) NOT NULL,
				`token_cksum` character varying(48) NOT NULL,
				`token_data` text NOT NULL,
				`token_name` character varying(50) NOT NULL,
				`created` bigint DEFAULT 0 NOT NULL,
				PRIMARY KEY (`id`, `token_hash`)
				-- #END: table schema",
				[ // indexes
					'authtokens_id' 			=> '`id` ASC',
					'authtokens_active' 		=> '`active` DESC',
					'authtokens_expires' 		=> '`expires` ASC',
					'authtokens_token_hash' 	=> '`token_hash` ASC',
					'authtokens_token_name' 	=> '`token_name` ASC',
					'authtokens_created' 		=> '`created` DESC',
					'authtokens_uuid' 			=> [ 'mode' => 'unique', 'index' => '`id` ASC, `token_hash` ASC' ],
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
$ipadr = (string) $this->db->escape_str((string)\SmartUtils::get_ip_client());
$passlen = (int) \SmartHashCrypto::PASSWORD_HASH_LENGTH;
//-- {{{SYNC-ADMINS-ACCOUNT-DATA-STRUCTURE}}}
$schema = <<<SQL
-- #START: tables schema: _smartframework_metadata / admins @ 20250207
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@auth-admins', '{$version}');
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('init-ip@auth-admins', '{$ipadr}');
CREATE TABLE 'admins' (
	`id` character varying(25) PRIMARY KEY NOT NULL,
	`pass` character varying({$passlen}) NOT NULL,
	`active` smallint DEFAULT 0 NOT NULL,
	`quota` bigint DEFAULT 0 NOT NULL,
	`email` character varying(72) DEFAULT NULL NULL,
	`title` character varying(16) DEFAULT '' NOT NULL,
	`name_f` character varying(64) DEFAULT '' NOT NULL,
	`name_l` character varying(64) DEFAULT '' NOT NULL,
	`address` character varying(64) DEFAULT '' NOT NULL,
	`zip` character varying(64) DEFAULT '' NOT NULL,
	`city` character varying(64) DEFAULT '' NOT NULL,
	`region` character varying(64) DEFAULT '' NOT NULL,
	`country` character varying(2) DEFAULT '' NOT NULL,
	`phone` character varying(32) DEFAULT '' NOT NULL,
	`priv` text DEFAULT '' NOT NULL,
	`restrict` text DEFAULT '' NOT NULL,
	`settings` text DEFAULT '' NOT NULL,
	`keys` text DEFAULT '' NOT NULL,
	`fa2` text DEFAULT '' NOT NULL,
	`ip_restr` text DEFAULT '' NOT NULL,
	`ip_addr` character varying(39) DEFAULT '' NOT NULL,
	`modif` bigint DEFAULT 0 NOT NULL,
	`created` bigint DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX 'admins_id' ON `admins` (`id` ASC);
CREATE INDEX 'admins_active' ON `admins` (`active` DESC);
CREATE INDEX 'admins_quota' ON `admins` (`quota` DESC);
CREATE UNIQUE INDEX 'admins_email' ON `admins` (`email` ASC);
CREATE INDEX 'admins_name_f' ON `admins` (`name_f`);
CREATE INDEX 'admins_name_l' ON `admins` (`name_l`);
CREATE INDEX 'admins_zip' ON `admins` (`zip`);
CREATE INDEX 'admins_city' ON `admins` (`city`);
CREATE INDEX 'admins_region' ON `admins` (`region`);
CREATE INDEX 'admins_country' ON `admins` (`country`);
CREATE INDEX 'admins_ip_addr' ON `admins` (`ip_addr`);
CREATE INDEX 'admins_modif' ON `admins` (`modif`);
CREATE INDEX 'admins_created' ON `admins` (`created`);
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
