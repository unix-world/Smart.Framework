<?php
// Class: \SmartModDataModel\Oauth2\SqOauth2
// (c) 2008-present unix-world.org - all rights reserved

namespace SmartModDataModel\Oauth2;

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
 * SQLite Model for ModOauth2
 * @ignore
 */
final class SqOauth2 {

	// ->
	// v.20250711

	private $db;
	private $userId;


	public function __construct() {
		//--
		if(
			(!\defined('\\SMART_FRAMEWORK_SECURITY_KEY'))
			OR
			((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '')
		) {
			\Smart::raise_error(__METHOD__.' # requires the following constant to be Set and Non-Empty: SMART_FRAMEWORK_SECURITY_KEY');
			return;
		} //end if
		//--
		if(\SmartAuth::is_authenticated() !== true) {
			\Smart::raise_error(__METHOD__.' # requires an Authentication !');
			return;
		} //end if
		//--
		$this->userId = (string) \trim((string)\SmartAuth::get_auth_id());
		if((string)$this->userId == '') {
			\Smart::raise_error(__METHOD__.' # requires an Authenticated User !');
			return;
		} //end if
		//--
		$db_pfx = (string) \SmartAuth::get_user_prefixed_path_by_area_and_auth_id();
		if((string)\trim((string)$db_pfx) == '') {
			\Smart::raise_error(__METHOD__.' # Failed to get a valid Authenticated UserName Valid Path !');
			return;
		} //end if
		$db_path = '#db/'.$db_pfx.'/oauth2-'.\SmartHashCrypto::safesuffix('Mod.OAuth2').'.sqlite';
		if(!\SmartFileSysUtils::checkIfSafePath((string)$db_path, true, true)) {
			\Smart::raise_error(__METHOD__.' # DB Path is UNSAFE !');
			return;
		} //end if
		//--
		$this->db = new \SmartSQliteDb((string)$db_path);
		$this->db->open();
		//--
		if(!\SmartFileSystem::is_type_file((string)$db_path)) {
			if($this->db instanceof \SmartSQliteDb) {
				$this->db->close();
			} //end if
			\Smart::raise_error(__METHOD__.' # DB File does NOT Exists !');
			return;
		} //end if
		//--
		$schema_ok = (bool) $this->initDBSchema(); // create default schema if not exists
		if($schema_ok !== true) {
			if($this->db instanceof \SmartSQliteDb) {
				$this->db->close();
			} //end if
			\Smart::raise_error(__METHOD__.' # INVALID DB Schema !');
			return;
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


	//===== Management


	public function countByFilter(string $id='') : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return 0;
		} //end if
		//--
		$where = '';
		$params = '';
		//--
		$id = (string) \trim((string)$id);
		if((string)$id != '') {
			$where = ' WHERE (`id` LIKE ?)';
			$params = [ '%'.$this->db->quote_likes((string)$id).'%' ];
		} //end if else
		//--
		return (int) $this->db->count_data(
			'SELECT COUNT(1) FROM `oauth2_data`'.$where,
			$params
		);
		//--
	} //END FUNCTION


	public function getListByFilter(array $fields=[], int $limit=10, int $ofs=0, string $sortby='id', string $sortdir='ASC', string $id='') : array {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return [];
		} //end if
		//--
		if((int)\Smart::array_size($fields) > 0) {
			$tmp_arr = (array) $fields;
			$fields = array();
			for($i=0; $i<(int)\Smart::array_size($tmp_arr); $i++) {
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
		if((string)$id != '') {
			$where = ' WHERE (`id` LIKE ?)';
			$params = [ '%'.$this->db->quote_likes((string)$id).'%' ];
		} //end if else
		//--
		$sortby = (string) \strtolower((string)\trim((string)$sortby));
		switch((string)$sortby) {
			case 'access_expire_time':
				// OK, CUSTOM
				break;
			case 'active':
			case 'account':
			case 'created':
			case 'modified':
				// OK, STD
				break;
			case 'id':
			default:
				$sortby = 'id'; // DEFAULT
		} //end switch
		//--
		$sortdir = (string) \strtoupper((string)$sortdir);
		if((string)$sortdir != 'DESC') {
			$sortdir = 'ASC';
		} //end if
		//--
		return (array) $this->db->read_adata(
			'SELECT '.$fields.' FROM `oauth2_data`'.$where.' ORDER BY `'.$sortby.'` '.$sortdir.$limit,
			$params
		);
		//--
	} //END FUNCTION


	public function getById(string $id, bool $decrypt=false, bool $displayOnlyFormat=false) : array {
		//--
		// if $decrypt is set to TRUE the tokens are decrypted
		// if $displayOnlyFormat is set to TRUE the tokens are usable just for display
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return [];
		} //end if
		//--
		if($displayOnlyFormat === true) {
			if($decrypt !== true) {
				\Smart::raise_error('Invalid OAUTH2 DB::getById() parameters ... if `displayOnlyFormat=TRUE` it also requires `decrypt=TRUE` !');
				return [];
			} //end if
		} //end if
		//--
		if((string)\trim((string)$id) == '') {
			return [];
		} //end if
		//--
		$arr = (array) $this->db->read_asdata(
			'SELECT * FROM `oauth2_data` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			array((string)$id)
		);
		//--
		if((int)\Smart::array_size($arr) > 0) {
			//--
			$arr['client_secret'] 	= (string) ($arr['client_secret'] ?? null);
			$arr['code'] 			= (string) ($arr['code'] ?? null);
			$arr['access_token'] 	= (string) ($arr['access_token'] ?? null);
			$arr['refresh_token'] 	= (string) ($arr['refresh_token'] ?? null);
			$arr['id_token'] 		= (string) ($arr['id_token'] ?? null);
			$arr['logs'] 			= (string) ($arr['logs'] ?? null);
			//--
			if($decrypt === true) {
				//--
				if((string)\trim((string)$arr['client_secret']) != '') {
					$arr['client_secret'] = (string) \SmartCipherCrypto::tf_decrypt((string)$arr['client_secret'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY); // no fallback
				} //end if
				//--
				if((string)\trim((string)$arr['code']) != '') {
					$arr['code'] = (string) \SmartCipherCrypto::tf_decrypt((string)$arr['code'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY); // no fallback
				} //end if
				//--
				if((string)\trim((string)$arr['access_token']) != '') {
					$arr['access_token'] = (string) \SmartCipherCrypto::tf_decrypt((string)$arr['access_token'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY); // no fallback
					if($displayOnlyFormat === true) {
						$arr['access_token'] = (string) \SmartAuth::jwt_token_display((string)$arr['access_token']);
					} //end if
				} //end if
				//--
				if((string)\trim((string)$arr['refresh_token']) != '') {
					$arr['refresh_token'] = (string) \SmartCipherCrypto::tf_decrypt((string)$arr['refresh_token'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY); // no fallback
					if($displayOnlyFormat === true) {
						$arr['refresh_token'] = (string) \SmartAuth::jwt_token_display((string)$arr['refresh_token']);
					} //end if
				} //end if
				//--
				if((string)\trim((string)$arr['id_token']) != '') {
					$arr['id_token'] = (string) \SmartCipherCrypto::tf_decrypt((string)$arr['id_token'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY); // no fallback
					if($displayOnlyFormat === true) {
						$arr['id_token'] = (string) \SmartAuth::jwt_token_display((string)$arr['id_token']);
					} //end if
				} //end if
				//--
				if((string)\trim((string)$arr['logs']) != '') {
					$arr['logs'] = (string) \SmartCipherCrypto::bf_decrypt((string)$arr['logs'], (string)$arr['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY);
				} //end if
				//--
			} //end if
			//--
		} //end if else
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public function insertRecord(array $arr_data, string $redirect_url) : int {
		//--
		// NEED TO INITIALIZE THE MODIFIED TIME TOO, it is a reference only for the Token-Refresh last time, ... if does not change here the things will break
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		$this->db->write_data('VACUUM');
		//--
		if(!\is_array($arr_data)) {
			$arr_data = array();
		} //end if
		//--
		$data = [];
		//--
		$data['modified'] = (int) \time();
		$data['created'] = (int) $data['modified'];
		//--
		$data['errs'] = 0;
		$data['active'] = 1;
		$data['account'] = (string) $this->userId;
		//--
		$data['id'] = (string) \trim((string)$arr_data['id']);
		if((\strlen((string)$data['id']) < 5) OR (\strlen((string)$data['id']) > 127)) {
			return -10; // invalid id length
		} //end if
		if(!\preg_match((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_REGEX_VALID_ID, (string)$data['id'])) { // {{{SYNC-OAUTH2-REGEX-ID}}}
			return -11; // invalid characters in id
		} //end if
		//--
		$data['scope'] = (string) \trim((string)$arr_data['scope']);
	//	if((string)$data['scope'] == '') {
	//		return -12; // empty scope
	//	} //end if
		if(strlen((string)$data['scope']) > 255) {
			return -13; // scope too long
		} //end if
		//--
		$data['url_auth'] = (string) \trim((string)$arr_data['url_auth']);
		if($this->isOauth2UrlValid((string)$data['url_auth']) !== true) {
			return -14; // empty or invalid auth URL ({...}/auth)
		} //end if
		//--
		$data['url_token'] = (string) \trim((string)$arr_data['url_token']);
		if($this->isOauth2UrlValid((string)$data['url_token']) !== true) {
			return -15; // empty or invalid token URL ({...}/token)
		} //end if
		//--
		$data['url_redirect'] = (string) \trim((string)$redirect_url);
		if((string)$data['url_redirect'] == '') {
			return -16; // empty url redirect
		} //end if
		//--
		$data['client_id'] = (string) \trim((string)$arr_data['client_id']);
		if((string)$data['client_id'] == '') {
			return -17; // empty client ID
		} //end if
		//--
		$data['client_secret'] = (string) \trim((string)$arr_data['client_secret']);
		if((string)$data['client_secret'] == '') {
			return -18; // empty client Secret
		} //end if
		$data['client_secret'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['client_secret'], (string)$data['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY, true); // TF+BF
		//--
		$data['code'] = (string) \trim((string)$arr_data['code']);
		if((string)$data['code'] == '') {
			return -19; // empty code
		} //end if
		$data['code'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['code'], (string)$data['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY, true); // TF+BF
		//--
		$data['access_token'] = (string) \trim((string)$arr_data['access_token']);
		if((string)$data['access_token'] == '') { // {{{SYNC-OAUTH2-CONDITION-ACCESS-TOKEN}}}
			return -20; // empty access token
		} //end if
		$data['access_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['access_token'], (string)$data['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY);
		//--
		$data['refresh_token'] = (string) \trim((string)$arr_data['refresh_token']);
		if((string)$data['refresh_token'] == '') { // OK: some providers do not use this (ex: github)
			//-- #fix: start
			/* wrong, it may expire but not support refresh token (ex: facebook) ; fixed below
			$data['access_expire_seconds'] = 0; // wrong, it may expire but not support refresh token (ex: facebook)
			$data['access_expire_time'] = 0; // wrong, it may expire but not support refresh token (ex: facebook)
			*/
			//--
			$data['access_expire_seconds'] = (int) \trim((string)$arr_data['access_expire_seconds']);
			if((int)$data['access_expire_seconds'] < 0) { // {{{SYNC-OAUTH2-CONDITION-ACCESS-EXPIRE-SECONDS-TOKEN}}}
				return -21; // invalid access token expire seconds
			} //end if
			//--
			if((int)$data['access_expire_seconds'] > 0) {
				$data['access_expire_time'] = (int) ((int)$data['access_expire_seconds'] + (int)$data['modified']);
				if((int)$data['access_expire_time'] < (int)\time()) {
					return -22; // invalid access token expire time
				} //end if
			} else {
				$data['access_expire_time'] = 0;
			} //end if else
			//-- #end fix
		}  else {
			//--
			$data['refresh_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['refresh_token'], (string)$data['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY);
			//--
			$data['access_expire_seconds'] = (int) \trim((string)$arr_data['access_expire_seconds']);
			if((int)$data['access_expire_seconds'] < 1) { // {{{SYNC-OAUTH2-CONDITION-ACCESS-EXPIRE-SECONDS-TOKEN}}}
				return -21; // invalid access token expire seconds
			} //end if
			//--
			$data['access_expire_time'] = (int) ((int)$data['access_expire_seconds'] + (int)$data['modified']);
			if((int)$data['access_expire_time'] <= (int)\time()) {
				return -22; // invalid access token expire time
			} //end if
			//--
		} //end if else
		//--
		$data['id_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$arr_data['id_token'], (string)$data['id'].':'.\SMART_FRAMEWORK_SECURITY_KEY, true); // TF+BF
		//--
		$data['description'] = (string) \trim((string)$arr_data['description']);
		if((string)$data['description'] == '') {
			return -23; // empty description
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$check_id = (array) $this->db->read_asdata(
			'SELECT `id` FROM `oauth2_data` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
			[
				(string) $data['id']
			]
		);
		if((int)\Smart::array_size($check_id) > 0) {
			return -2; // duplicate ID
		} //end if
		//--
		$wr = (array) $this->db->write_data(
			'INSERT INTO `oauth2_data` '.$this->db->prepare_statement(
				(array) $data,
				'insert'
			)
		);
		$out = $wr[1];
		//--
		$this->db->write_data('COMMIT');
		//--
		return (int) $out;
		//--
	} //END FUNCTION


	public function updateRecordAccessToken(string $id, int $access_expire_seconds, string $access_token, string $refresh_token, ?string $id_token=null) : int {
		//--
		// NEED TO UPDATE MODIFIED TIME, it is a reference only for the Token-Refresh last time, ... if does not change here the things will break
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		if(\Smart::random_number(0, 1000) == 500) {
			$this->db->write_data('VACUUM');
		} //end if
		//--
		if((string)$id == '') {
			return -10; // invalid ID
		} //end if
		//--
		$rdarr = (array) $this->getById((string)$id); // do not decrypt, no need here
		if((int)\Smart::array_size($rdarr) <= 0) {
			return -11; // record not found, Invalid ID
		} //end if
		if((string)$rdarr['refresh_token'] == '') {
			return -12; // records without a refresh token must not update the access token
		} //end if
		$rdarr = null;
		//--
		$data = [];
		$data['modified'] = (int) \time();
		$data['logs'] = (string) \SmartCipherCrypto::bf_encrypt((string)'# '.\date('Y-m-d H:i:s O')."\n".'# '.'Tokens Update', (string)$id.':'.\SMART_FRAMEWORK_SECURITY_KEY);
		//--
		$data['access_token'] = (string) \trim((string)$access_token);
		if((string)$data['access_token'] == '') { // {{{SYNC-OAUTH2-CONDITION-ACCESS-TOKEN}}}
			return -13; // empty access token
		} //end if
		$data['access_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['access_token'], (string)$id.':'.\SMART_FRAMEWORK_SECURITY_KEY);
		//--
		$data['refresh_token'] = (string) \trim((string)$refresh_token);
		if((string)$data['refresh_token'] == '') {
			return -14; // empty refresh token ; on update, a new refresh token must be provided, it is chained
		} //end if
		$data['refresh_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$data['refresh_token'], (string)$id.':'.\SMART_FRAMEWORK_SECURITY_KEY);
		//--
		if($id_token !== null) { // optional set, only if provided ... maybe provided just on 1st step !?
			$data['id_token'] = (string) \SmartCipherCrypto::tf_encrypt((string)$id_token, (string)$id.':'.\SMART_FRAMEWORK_SECURITY_KEY, true); // TF+BF
		} //end if
		//--
		$data['access_expire_seconds'] = (int) $access_expire_seconds;
		if((int)$data['access_expire_seconds'] < 1) { // {{{SYNC-OAUTH2-CONDITION-ACCESS-EXPIRE-SECONDS-TOKEN}}}
			return -15; // invalid access token expire seconds
		} //end if
		//--
		$data['access_expire_time'] = (int) ((int)$data['access_expire_seconds'] + (int)$data['modified']);
		if((int)$data['access_expire_time'] <= (int)\time()) {
			return -16; // invalid access token expire time
		} //end if
		//--
		$data['errs'] = 0;
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'UPDATE `oauth2_data` '.$this->db->prepare_statement(
				(array) $data,
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE (`id` = ?)',
				[
					(string) $id
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


	public function updateRecordLogs(string $id, string $logs, bool $errs=false) : int {
		//--
		// DO NOT UPDATE MODIFIED TIME, it is a reference only for the Token-Refresh last time, ... if change here the things will break
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		$logs = (string) \trim((string)$logs);
		if((string)$logs == '') {
			return -10;
		} //end if
		//--
		$data = [];
		$data['logs'] = (string) \SmartCipherCrypto::bf_encrypt((string)$logs, (string)$id.':'.\SMART_FRAMEWORK_SECURITY_KEY);
		if($errs === true) {
			$data['errs'] = 1;
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'UPDATE `oauth2_data` '.$this->db->prepare_statement(
				(array) $data,
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE (`id` = ?)',
				[
					(string) $id
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


	public function updateStatus(string $id, int $status) : int {
		//--
		// DO NOT UPDATE MODIFIED TIME, it is a reference only for the Token-Refresh last time, ... if change here the things will break
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		if(\Smart::random_number(0, 1000) == 500) {
			$this->db->write_data('VACUUM');
		} //end if
		//--
		if((string)$id == '') {
			return -10; // invalid ID
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
			'UPDATE `oauth2_data` '.$this->db->prepare_statement(
				(array) [
					'active' 	=> (int) $status
				],
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE (`id` = ?)',
				[
					(string) $id
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


	public function updateDesc(string $id, string $desc) : int {
		//--
		// DO NOT UPDATE MODIFIED TIME, it is a reference only for the Token-Refresh last time, ... if change here the things will break
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		if(\Smart::random_number(0, 1000) == 500) {
			$this->db->write_data('VACUUM');
		} //end if
		//--
		if((string)$id == '') {
			return -10; // invalid ID
		} //end if
		//--
		$desc = (string) \trim((string)$desc);
		if((int)\strlen((string)$desc) > 1024) {
			return -11;
		} //end if
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'UPDATE `oauth2_data` '.$this->db->prepare_statement(
				(array) [
					'description' => (string) $desc,
				],
				'update'
			).' '.$this->db->prepare_param_query(
				'WHERE (`id` = ?)',
				[
					(string) $id
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


	public function deleteRecord(string $id) : int {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return -100;
		} //end if
		//--
		$this->db->write_data('VACUUM');
		//--
		$out = -1;
		//--
		$this->db->write_data('BEGIN');
		//--
		$wr = $this->db->write_data(
			'DELETE FROM `oauth2_data` '.$this->db->prepare_param_query(
				'WHERE (`id` = ?)',
				[
					(string) $id
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


	//#####


	private function isOauth2UrlValid(string $url) { // {{{SYNC-OAUTH2-VALIDATE-URL}}}
		//--
		if(
			((string)\trim((string)$url) == '') OR
			(strpos((string)$url, 'https://') !== 0) OR
		//	(strpos((string)$url, 'http://') !== 0) OR // make nonsense to use http scheme for OAUTH2 because it is unsecure
			(strlen((string)\trim((string)$url)) < 15) OR
			(strlen((string)\trim((string)$url)) > 255)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	//@@@@@


	private function initDBSchema() {
		//--
		if(!$this->db instanceof \SmartSQliteDb) {
			\Smart::raise_error('Invalid OAUTH2 DB Connection !');
			return false;
		} //end if
		//--
		if($this->db->check_if_table_exists('oauth2_data') != 1) { // check and create DB table if not exists
			$this->db->write_data('BEGIN');
			$this->db->write_data((string)$this->dbDefaultSchema());
			$this->db->write_data('COMMIT');
			if($this->db->check_if_table_exists('oauth2_data') != 1) { // create DB table, it should exist
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	private function dbDefaultSchema() { // {{{SYNC-TABLE-OAUTH2_TEMPLATE}}}
//--
$version = (string) $this->db->escape_str((string)\SMART_FRAMEWORK_RELEASE_TAGVERSION.' '.\SMART_FRAMEWORK_RELEASE_VERSION);
$schema = <<<SQL
-- #START: tables schema: _smartframework_metadata / oauth2 @ 20250218
INSERT INTO `_smartframework_metadata` (`id`, `description`) VALUES ('version@oauth2', '{$version}');
CREATE TABLE 'oauth2_data' (
	`id` character varying(127) PRIMARY KEY NOT NULL,
	`active` smallint DEFAULT 0 NOT NULL,
	`access_expire_seconds` integer DEFAULT 0 NOT NULL,
	`access_expire_time` bigint DEFAULT 0 NOT NULL,
	`scope` character varying(255) NOT NULL,
	`url_auth` character varying(255) NOT NULL,
	`url_token` character varying(255) NOT NULL,
	`url_redirect` character varying(255) NOT NULL,
	`client_id` character varying(255) NOT NULL,
	`client_secret` character varying(255) NOT NULL,
	`code` character varying(3072) NOT NULL,
	`access_token` text NOT NULL,
	`refresh_token` text NOT NULL,
	`id_token` text NOT NULL,
	`description` text DEFAULT '' NOT NULL,
	`logs` text DEFAULT '' NOT NULL,
	`errs` smallint DEFAULT 0 NOT NULL,
	`account` character varying(25) DEFAULT '' NOT NULL,
	`modified` integer DEFAULT 0 NOT NULL,
	`created` integer DEFAULT 0 NOT NULL
);
CREATE UNIQUE INDEX 'oauth2_data_id' ON `oauth2_data` (`id` ASC);
CREATE INDEX 'oauth2_data_active' ON `oauth2_data` (`active`);
CREATE INDEX 'oauth2_data_access_expire_seconds' ON `oauth2_data` (`access_expire_seconds` ASC);
CREATE INDEX 'oauth2_data_access_expire_time' ON `oauth2_data` (`access_expire_time` ASC);
CREATE INDEX 'oauth2_data_errs' ON `oauth2_data` (`errs`);
CREATE INDEX 'oauth2_data_account' ON `oauth2_data` (`account`);
CREATE INDEX 'oauth2_data_modif' ON `oauth2_data` (`modified`);
CREATE INDEX 'oauth2_data_created' ON `oauth2_data` (`created`);
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
