<?php
// Abstract Class: \SmartModDataModel\AuthAdmins\AbstractAuthAdmins
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModDataModel\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* SmartAuth
//	* \SmartModExtLib\AuthAdmins\Auth2FTotp

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Abstract Model for ModAuthAdmins
 * @ignore
 */
abstract class AbstractAuthAdmins {

	// ->
	// v.20250205

	public const MAX_TOKENS_PER_ACCOUNT = 25;

	public const MAX_ADMIN_START_LETTER_ACCOUNTS = 32768; // limit as max 32768 per starting letter
	public const MAX_ADMIN_ACCOUNTS = 851968; // can clusterize ..., this is only a limit per cluster, as 32768 * 26 = 851968 (there are 26 letters a-z as prefix, ex: `a/admin`) ; this is the max safe supported by one server storage system,  if stored on disk, which supports no more than 32k sub-dirs per dir ; many operating systems still have this limit ...


	abstract public function __construct(bool $initdb=true); // THIS SHOULD BE THE ONLY METHOD IN THIS CLASS THAT THROW EXCEPTIONS !!!
	abstract public function __destruct();
	abstract public function dbExists() : bool; // when calling this method the construct must be called with: $initdb=false !

	abstract public function getLoginData(string $auth_user_name, string $auth_pass_hash) : array;

	abstract public function getById(string $id) : array;
	abstract public function insertAccount(array $data, bool $active=false) : int; // this should generate the 2FA secret, even if 2FA is not enabled at the moment ...
	abstract public function deleteAccount(string $id) : int;
	abstract public function updateAccount(string $id, array $data) : int;
	abstract public function updateStatus(string $id, int $status) : int;
	abstract public function updatePassword(string $id, string $pass) : int;

	abstract public function countByFilter(string $id='', bool $strict=false) : int;
	abstract public function getListByFilter(array $fields=[], int $limit=10, int $ofs=0, string $sortby='id', string $sortdir='ASC', string $id='', bool $strict=false) : array;


	//-------- Tokens


	abstract public function getLoginActiveTokenByIdAndKey(string $id, string $token_key) : array;

	abstract public function getTokenByIdAndHash(string $id, string $token_hash) : array;
	abstract public function insertToken(array $data) : int;
	abstract public function deleteTokenByIdAndHash(string $id, string $token_hash) : int;
	abstract public function updateTokenStatus(string $id, string $token_hash, int $status) : int;

	abstract public function countTokensById(string $id) : int;
	abstract public function getTokensListById(string $id) : array;


	//-------- Priv Keys


	final public function encryptPrivKey(?string $pkey_plain, ?string $hash_pass=null) : string { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		if($hash_pass === null) {
			if(\SmartAuth::is_authenticated() !== true) {
				return '';
			} //end if
			$hash_pass = (string) \SmartAuth::get_auth_passhash();
		} //end if
		//--
		$hash_pass = (string) \trim((string)$hash_pass);
		if((string)$hash_pass == '') {
			return '';
		} //end if
		//--
		$secret = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$secret = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$secret) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::encrypt_sensitive_data(
			(string) $pkey_plain,
			(string) $hash_pass.\chr(0).$secret
		);
		//--
	} //END FUNCTION


	final public function decryptPrivKey(?string $pkey_enc, ?string $hash_pass=null) : string { // {{{SYNC-ADM-AUTH-KEYS}}}
		//--
		if($hash_pass === null) {
			if(\SmartAuth::is_authenticated() !== true) {
				return '';
			} //end if
			$hash_pass = (string) \SmartAuth::get_auth_passhash();
		} //end if
		//--
		$hash_pass = (string) \trim((string)$hash_pass);
		if((string)$hash_pass == '') {
			return '';
		} //end if
		//--
		$secret = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$secret = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$secret) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::decrypt_sensitive_data(
			(string) $pkey_enc,
			(string) $hash_pass.\chr(0).$secret
		);
		//--
	} //END FUNCTION


	//-------- 2FA



	final public function get2FAPinToken(?string $secret) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		if(\SmartModExtLib\AuthAdmins\Auth2FTotp::IsSecretValid((string)$secret) !== true) {
			return '';
		} //end if
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateToken((string)$secret);
		//--
	} //END FUNCTION


	final public function get2FAUrl(?string $secret, ?string $id) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$issuer = (string) \SmartUtils::get_server_current_basedomain_name();
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateBarcodeUrl((string)$secret, (string)$id, (string)$issuer);
		//--
	} //END FUNCTION


	final public function get2FASvgBarCode(?string $secret, ?string $id) : string {
		//--
		$url = (string) $this->get2FAUrl((string)$secret, (string)$id);
		if((string)\trim((string)$url) == '') {
			return '';
		} //end if
		//--
		return (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateBarcodeQrCodeSVGFromUrl((string)$url, '#4D5774');
		//--
	} //END FUNCTION


	final public function encrypt2FAKey(?string $secret, ?string $id) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$seckey = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$seckey = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$seckey) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::encrypt_sensitive_data(
			(string) $secret,
			(string) $id.\chr(0).$seckey
		);
		//--
	} //END FUNCTION


	final public function decrypt2FAKey(?string $secret, ?string $id) : string {
		//--
		$secret = (string) \trim((string)$secret);
		if((string)$secret == '') {
			return '';
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$seckey = '';
		if(\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) {
			$seckey = (string) \SMART_FRAMEWORK_SECURITY_KEY;
		} //end if
		if((string)\trim((string)$seckey) == '') {
			\Smart::log_warning(__METHOD__.' # Secret is empty');
			return '';
		} //end if
		//--
		return (string) \SmartAuth::decrypt_sensitive_data(
			(string) $secret,
			(string) $id.\chr(0).$seckey
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
