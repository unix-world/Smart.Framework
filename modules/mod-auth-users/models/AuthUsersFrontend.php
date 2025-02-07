<?php
// Class: \SmartModDataModel\AuthUsers\AuthUsersFrontend
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModDataModel\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * SQLite/PostgreSQL Model for ModAuthUsers/Frontend
 * @ignore
 */
final class AuthUsersFrontend {

	// ::
	// v.20250206


	private static $db = null;


	private static function dbType() {
		//--
		if((string)\SmartModExtLib\AuthUsers\Utils::getDbType() == 'sqlite') {
			//--
			if(self::$db === null) {
				//--
				$sqlitedbfile = '#db/auth-users.sqlite'; // TODO: do not use the same DB for all users if SQLite, can't scale ... use an algorithm to have a single DB for each user containing all the info
				//--
				if(!\SmartFileSysUtils::checkIfSafePath((string)$sqlitedbfile, true, true)) { // dissalow absolute ; allow protected
					\Smart::raise_error(
						__CLASS__.': SQLite DB PATH is UNSAFE !',
						'AuthUsers ERROR: UNSAFE DB ACCESS (1)'
					);
					return;
				} //end if
				//--
				if(!\SmartFileSystem::is_type_file((string)$sqlitedbfile)) {
					\Smart::raise_error(
						__CLASS__.': SQLite DB File does NOT Exists !',
						'AuthUsers ERROR: Please set the DB first by using first the AuthUsers Backend ...'
					);
					return;
				} //end if
				//--
				self::$db = new \SmartSQliteDb((string)$sqlitedbfile);
				self::$db->open();
				//--
				if(!\SmartFileSystem::is_type_file((string)$sqlitedbfile)) {
					if(self::$db instanceof \SmartSQliteDb) {
						self::$db->close();
					} //end if
					\Smart::raise_error(
						__CLASS__.': SQLite DB File does NOT Exists !',
						'AuthUsers ERROR: DB NOT FOUND (1)'
					);
					return;
				} //end if
				//--
			} //end if
			//--
			return 'sqlite';
			//--
		} elseif((string)\SmartModExtLib\AuthUsers\Utils::getDbType() == 'pgsql') {
			//--
			if(\Smart::array_size(\Smart::get_from_config('pgsql')) <= 0) {
				\Smart::raise_error(
					__CLASS__.': PostgreSQL DB CONFIG Not Found !',
					'AuthUsers ERROR: DB CONFIG Not Found (2)'
				);
				return;
			} //end if
			//--
			if(\SmartPgsqlDb::check_if_schema_exists('smart_runtime') != 1) {
				$sql = (string) \SmartFileSystem::read('_sql/postgresql/init-smart-framework.sql');
				if((string)$sql == '') {
					\Smart::raise_error(
						__CLASS__.': PostgreSQL Init Schema SQL File does NOT Exists or is NOT Readable !',
						'AuthUsers ERROR: DB Init Schema SQL File does NOT Exists or is NOT Readable (2)'
					);
					return;
				} //end if
				\SmartPgsqlDb::write_data((string)$sql);
			} //end if
			//--
			if((\SmartPgsqlDb::check_if_schema_exists('web') != 1) OR (\SmartPgsqlDb::check_if_table_exists('auth_users', 'web') != 1)) {
				$sql = (string) \SmartFileSystem::read('modules/mod-auth-users/models/sql/postgresql/auth-users-schema.sql');
				if((string)$sql == '') {
					\Smart::raise_error(
						__CLASS__.': PostgreSQL Schema SQL File does NOT Exists or is NOT Readable !',
						'AuthUsers ERROR: DB Schema SQL File does NOT Exists or is NOT Readable (2)'
					);
					return;
				} //end if
				\SmartPgsqlDb::write_data((string)$sql);
			} //end if
			//--
			return 'pgsql';
			//--
		} else {
			//--
			\SmartFrameworkRuntime::Raise503Error('503 Service Unavailable / AuthUsers', 'AuthUsers DB Type is not set in configs ! ...');
			die('AuthUsersFrontend:NO-DB-TYPE');
			//--
		} //end if else
		//--
	} //END FUNCTION


	public static function getAccountById(?string $id) : array {
		//--
		$id = (string) \trim((string)$id);
		if(((string)$id == '') || ((int)\strlen((string)$id) != 21)) {
			return []; // early return
		} //end if
		//--
		$id = (string) \SmartModExtLib\AuthUsers\Utils::userNameToUserAccountId((string)$id); // {{{SYNC-ACCOUNT-ID-TO-USER-ID-TRANSFORMATION}}}
		if(((string)$id == '') || (\strpos((string)$id, '-') === false) || ((int)\strlen((string)$id) != 21)) {
			return []; // early return
		} //end if
		//--
		if((string)self::dbType() == 'pgsql') {
			$arr = (array) \SmartPgsqlDb::read_asdata(
				'SELECT * FROM "web"."auth_users" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$arr = (array) self::$db->read_asdata(
				'SELECT * FROM `auth_users` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $id
				]
			);
		} else {
			return [];
		} //end if else
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function getAccountByEmail(?string $email) : array {
		//--
		$email = (string) \strtolower((string)\trim((string)$email));
		if(((string)$email == '') || (\strpos((string)$email, '@') === false) || ((int)\strlen((string)$email) > 72)) {
			return []; // early return
		} //end if
		//--
		if((string)self::dbType() == 'pgsql') {
			$arr = (array) \SmartPgsqlDb::read_asdata(
				'SELECT * FROM "web"."auth_users" WHERE ("email" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $email
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$arr = (array) self::$db->read_asdata(
				'SELECT * FROM `auth_users` WHERE (`email` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $email
				]
			);
		} else {
			return [];
		} //end if else
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function createAccount(array $data, string $provider='@') : int { // 1 = OK ; 0 if not written ; -1..-n other are error codes
		//--
		$provider = (string) \strtolower((string)\trim((string)$provider));
		if((string)$provider == '') {
			return -1;
		} //end if
		//--
		$federated = false;
		if((string)$provider != '@') { // {{{SYNC-AUTH-USERS-PROVIDER-SELF}}} ; `@` means non-federated, any other value is considered federated ; empty is disallowed above ; by intention this is explicit non empty string = `@` for non-federated mode to disallow mistakes of coding
			$fedvalidate = (int) self::validateFederatedProvider((string)$provider);
			if($fedvalidate !== 0) {
				return (int) $fedvalidate;
			} //end if
			$federated = true;
		} //end if
		//-- {{{SYNC-AUTH-USERS-DB-KEYS-MAX-LEN}}}
		$keys = [ 'email' => 72 ];
		if($federated === true) {
			$keys['name'] = 129;
			$keys['authlog'] = 8192;
		} else {
			$keys['password'] = [
				(int) \SmartHashCrypto::PASSWORD_PLAIN_MIN_LENGTH, // min
				(int) \SmartHashCrypto::PASSWORD_PLAIN_MAX_LENGTH, // max
			];
		} //end if else
		$validate = (int) self::validateDataByKeys((array)$keys, (array)$data);
		if($validate !== 0) {
			return (int) $validate;
		} //end if
		if(\SmartAuth::validate_auth_ext_username((string)$data['email']) !== true) {
			return -1;
		} //end if
		if($federated !== true) {
			if(\SmartAuth::validate_auth_password((string)$data['password']) !== true) {
				return -2;
			} //end if
		} //end if
		if((int)\Smart::array_size($data) <= 0) {
			return -3;
		} //end if
		//--
		$data['id'] = (string) \Smart::uuid_10_seq().'-'.\Smart::uuid_10_num();
		$data['registered'] = (string) \date('Y-m-d H:i:s');
		$data['ipaddr'] = (string) \SmartUtils::get_ip_client();
		if($federated === true) {
			$data['allowfed'] = '<'.$provider.'>'; // on account creation bind federated login to the one that created the account to avoid security issues ; later user can edit this from his profile and can enable others or all ; or if user set a login password can disable federated at all
		} else {
			$data['allowfed'] = ''; // for normal login set by default federated to empty to disallow federated login ; user can enable them from his account thereafter
			$data['password'] = (string) \SmartAuth::password_hash_create((string)$data['password']);
			if((string)\trim((string)$data['password']) == '') {
				return -4; // password hash creation failed
			} //end if
			$data['passalgo'] = (int) \SmartAuth::ALGO_PASS_SMART_SAFE_BCRYPT;
		} //end if
		//--
		$data['status'] = 0; // needs verification ; if federated will be able to login ; if default, will be able to login only after verification
		//--
		$wr = [];
		if((string)self::dbType() == 'pgsql') {
			//--
			$wr = (array) \SmartPgsqlDb::write_data(
				'INSERT INTO "web"."auth_users" '.
				\SmartPgsqlDb::prepare_statement((array)$data, 'insert').' ON CONFLICT DO NOTHING' // PgSQL 9.5 or later
			);
			//--
		} elseif((string)self::dbType() == 'sqlite') {
			//--
			$wr = (array) self::$db->write_data(
				'INSERT OR IGNORE INTO `auth_users` '.
				self::$db->prepare_statement((array)$data, 'insert')
			);
			//--
		} //end if else
		//--
		return (int) ($wr[1] ?? null);
		//--
	} //END FUNCTION


	public static function setAccountLogin(array $data) : int { // 1 = OK ; 0 if not written ; -1..-n other are error codes
		//--
		if((int)\Smart::array_size($data) <= 0) {
			return -1;
		} //end if
		//--
		$keys = [ 'email' => 72, 'jwtserial' => 21, 'jwtsignature' => 255, 'authlog' => 8192 ]; // {{{SYNC-AUTH-USERS-DB-KEYS-MAX-LEN}}}
		$validate = (int) self::validateDataByKeys((array)$keys, (array)$data);
		if($validate !== 0) {
			return (int) $validate;
		} //end if
		//--
		if(\strpos((string)$data['email'], '@') === false) {
			return -2;
		} //end if
		if(((string)\trim((string)$data['jwtsignature']) == '') OR (!\preg_match((string)\Smart::REGEX_SAFE_B64U_STR, (string)$data['jwtsignature']))) {
			return -3;
		} //end if
		if((int)\strlen((string)$data['jwtserial']) != 21) {
			return -4;
		} //end if
		if(!\preg_match((string)\SmartAuth::REGEX_VALID_JWT_SERIAL, (string)$data['jwtserial'])) { // {{{SYNC-JWT-VALID-SERIAL}}}
			return -5;
		} //end if
		//--
		$exists = (array) self::getAccountByEmail((string)$data['email']);
		if(((int)\Smart::array_size($exists) <= 0) OR ((string)$data['email'] !== (string)($exists['email'] ?? null))) {
			return -6; // account does not exists or wrong account selected
		} //end if
		unset($data['email']); // exclude this from update
		//--
		$id = (string) \trim((string)($exists['id'] ?? null));
		if((string)$id == '') {
			return -7;
		} //end if
		//--
		if(
			((string)\trim((string)($exists['password'] ?? null)) == '')
			OR
			( // {{{SYNC-ALLOWED-PASS-ALGOS}}}
				((int)($exists['passalgo'] ?? null) != (int)\SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS)
				AND
				((int)($exists['passalgo'] ?? null) != (int)\SmartAuth::ALGO_PASS_SMART_SAFE_BCRYPT)
			)
		) {
			return -8;
		} //end if
		//--
		$data['ipaddr'] = (string) \SmartUtils::get_ip_client(); // lastseen is updated below
		//--
		return (int) self::updateAccountById((string)$id, (array)$data); // {{{SYNC-ACCOUNT-LOGIN-UPDATE}}}
		//--
	} //END FUNCTION


	public static function setAccountFederatedLogin(string $provider, array $data) : int { // 1 = OK ; 0 if not written ; -1..-n other are error codes
		//--
		$provider = (string) \strtolower((string)\trim((string)$provider));
		$fedvalidate = (int) self::validateFederatedProvider((string)$provider);
		if($fedvalidate !== 0) {
			return (int) $fedvalidate;
		} //end if
		//--
		if((int)\Smart::array_size($data) <= 0) {
			return -1;
		} //end if
		//--
		$keys = [ 'email' => 72, 'name' => 129, 'jwtserial' => 21, 'jwtsignature' => 255, 'authlog' => 8192 ]; // {{{SYNC-AUTH-USERS-DB-KEYS-MAX-LEN}}}
		$validate = (int) self::validateDataByKeys((array)$keys, (array)$data);
		if($validate !== 0) {
			return (int) $validate;
		} //end if
		//--
		if(((string)\trim((string)$data['email']) == '') OR (\strpos((string)$data['email'], '@') === false)) {
			return -2;
		} //end if
		if(((string)\trim((string)$data['jwtsignature']) == '') OR (!\preg_match((string)\Smart::REGEX_SAFE_B64U_STR, (string)$data['jwtsignature']))) {
			return -3;
		} //end if
		if((int)\strlen((string)$data['jwtserial']) != 21) {
			return -4;
		} //end if
		if(!\preg_match((string)\SmartAuth::REGEX_VALID_JWT_SERIAL, (string)$data['jwtserial'])) { // {{{SYNC-JWT-VALID-SERIAL}}}
			return -5;
		} //end if
		//--
		$exists = (array) self::getAccountByEmail((string)$data['email']);
		if((int)\Smart::array_size($exists) <= 0) {
			$create = (int) self::createAccount($data, (string)$provider); // federated
			if($create !== 1) {
				return (int) (-100 + $create);
			} //end if
			$exists = (array) self::getAccountByEmail((string)$data['email']);
		} //end if
		//--
		if(((int)\Smart::array_size($exists) <= 0) OR ((string)$data['email'] !== (string)($exists['email'] ?? null))) {
			return -6; // account does not exists or wrong account selected ; or the creation above may have failed if the account was not already existing
		} //end if
		unset($data['email']); // exclude this from update
		//--
		$id = (string) \trim((string)($exists['id'] ?? null));
		if((string)$id == '') {
			return -7;
		} //end if
		//--
		$fedallow = (string) \trim((string)($exists['allowfed'] ?? null));
		if(
			($fedallow == '')
			OR
			(
				((string)$fedallow != '*')
				AND
				(\strpos((string)$fedallow, '<'.$provider.'>') === false)
			)
		) {
			return -8; // {{{SYNC-FEDERATED-LOGIN-ALLOWED-ACCOUNT-PROVIDERS-CODE}}}
		} //end if
		//--
		$data['ipaddr'] = (string) \SmartUtils::get_ip_client(); // lastseen is updated below
		//--
		return (int) self::updateAccountById((string)$id, (array)$data); // {{{SYNC-ACCOUNT-LOGIN-UPDATE}}}
		//--
	} //END FUNCTION


	//-------- [PRIVATES]


	private static function validateDataByKeys(array $keys, array $data) : int { // 0 if OK ; -51..-59 if fails
		//--
		if((int)\Smart::array_size($keys) <= 0) {
			return -51;
		} //end if
		if((int)\Smart::array_type_test($keys) != 2) { // associative
			return -52;
		} //end if
		//--
		if((int)\Smart::array_size($data) <= 0) {
			return -53;
		} //end if
		if((int)\Smart::array_type_test($data) != 2) { // associative
			return -54;
		} //end if
		//--
		foreach($keys as $key => $val) {
			if(!\array_key_exists((string)$key, (array)$data)) {
				return -55;
			} //end if
			$data[(string)$key] =(string) \trim((string)$data[(string)$key]);
			if((string)$data[(string)$key] == '') {
				return -56;
			} //end if
			if(\is_array($val)) {
				if((int)\strlen((string)$data[(string)$key]) < (int)($val[0] ?? null)) {
					return -57;
				} //end if
				if((int)\strlen((string)$data[(string)$key]) > (int)($val[1] ?? null)) {
					return -58;
				} //end if
			} else {
				if((int)\strlen((string)$data[(string)$key]) > (int)$val) {
					return -59;
				} //end if
			} //end if else
		} //end for
		//--
		return 0; // must not return 1 to conflict by mistake with the caller methods OK code
		//--
	} //END FUNCTION


	private static function updateAccountById(string $id, array $data) : int {
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return -61;
		} //end if
		if((int)\strlen((string)$id) != 21) {
			return -62;
		} //end if
		//--
		if((int)\Smart::array_size($data) <= 0) {
			return -63;
		} //end if
		//--
		if(\array_key_exists('id', $data)) {
			return -64;
		} //end if
		if(\array_key_exists('registered', $data)) {
			return -65;
		} //end if
		if(\array_key_exists('email', $data)) {
			return -66;
		} //end if
		//-- just in case unset them !
		unset($data['id']); // disallow update, this is frozen
		unset($data['registered']); // disallow update, this is frozen
		unset($data['email']); // disallow update, this is frozen, if ever need to be updated use a different protected method like: updateAccountEmail() ...
		//--
		if((int)\Smart::array_size($data) <= 0) {
			return -67;
		} //end if
		//--
		$data['lastseen'] = (string) \date('Y-m-d H:i:s'); // this is mandatory to be handled here to ensure affected rows = 1 always even if the other data was not modified
		//--
		$wr = [];
		if((string)self::dbType() == 'pgsql') {
			$wr = (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."auth_users" '.
				\SmartPgsqlDb::prepare_statement((array)$data, 'update').
				' WHERE ("id" = \''.\SmartPgsqlDb::escape_str((string)$id).'\')'
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$wr = (array) self::$db->write_data(
				'UPDATE `auth_users` '.
				self::$db->prepare_statement((array)$data, 'update').
				' WHERE (`id` = \''.self::$db->escape_str((string)$id).'\')'
			);
		} //end if else
		//--
		return (int) ($wr[1] ?? null);
		//--
	} //END FUNCTION


	private static function validateFederatedProvider(string $provider) : int {
		//--
		$provider = (string) \strtolower((string)\trim((string)$provider));
		if((string)$provider == '') {
			return -77;
		} //end if
		if(!\preg_match((string)\SmartModExtLib\AuthUsers\AuthPlugins::AUTH_USERS_PLUGINS_VALID_ID_REGEX, (string)$provider)) {
			return -78;
		} //end if
		if(\SmartModExtLib\AuthUsers\AuthPlugins::pluginExists((string)$provider) !== true) {
			return -87;
		} //end if
		//--
		$plugin = (array) \SmartModExtLib\AuthUsers\AuthPlugins::getPluginIdentity((string)$provider);
		if((int)\Smart::array_size($plugin) <= 0) { // just an extra safety check
			return -88;
		} //end if
		//--
		return 0; // must not return 1 to conflict by mistake with the caller methods OK code
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
