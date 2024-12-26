<?php
// PHP Oauth2 Api for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\Oauth2;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------



//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: \SmartModExtLib\Oauth2\Oauth2Api
 * Manages the OAuth2 API Requests
 *
 * @version 	v.20240115
 * @package 	modules:Oauth2
 *
 */
final class Oauth2Api {


	public const OAUTH2_AUTHORIZE_URL_CHPART 	= '&code_challenge=[###CODE-CHALLENGE|url###]&code_challenge_method=S256'; // sha256 ; currently this is wide supported and by example github does not support others
	public const OAUTH2_AUTHORIZE_URL_PARAMS 	= 'response_type=code&client_id=[###CLIENT-ID|url###]&scope=[###SCOPE|url###]&redirect_uri=[###REDIRECT-URI|url###]&state=[###STATE|url###]';
	public const OAUTH2_STANDALONE_REFRESH_URL 	= 'urn:ietf:wg:oauth:2.0:oob';

	public const OAUTH2_COOKIE_NAME_CSRF 		= 'SfOAuth2_Csrf'; // {{{SYNC-OAUTH2-COOKIE-NAME-CSRF}}} ; public key

	public const OAUTH2_PATTERN_VALID_ID 		= '[_a-zA-Z0-9,@\#\/\-\:\.]{5,127}'; // OK
	public const OAUTH2_REGEX_VALID_ID 			= '/^'.self::OAUTH2_PATTERN_VALID_ID.'$/';

	private const OAUTH2_REQUEST_MAX_REDIRECTS 	= 2;

	private static $model = null;


	/**
	 * Init the Oauth2 API Data
	 * If ALL OK will store it into the storage API (SQLite)
	 *
	 * @param ARRAY 	$data 		The Array of Input Data
	 * @param INTEGER+ 	$timeout 	The timeout in seconds to retrieve the Oauth2 Data via HTTP(S) from the Token URL
	 * @return ARRAY|STRING 		Error STRING if any error occus or if OK will return the Data ARRAY (incl. access token, expire time and refresh token)
	 */
	public static function initApiData(array $data, bool $reinit=false, int $timeout=15) { // : MIXED (STRING err | ARRAY)
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			return 'Requires an Authenticated User';
		} //end if
		//--
		if(\Smart::array_size($data) <= 0) {
			return 'Invalid Data Format';
		} //end if else
		//--
		if(
			(!\array_key_exists('id', $data))
			OR
			(!\array_key_exists('client_id', $data))
			OR
			(!\array_key_exists('client_secret', $data))
			OR
			(!\array_key_exists('scope', $data))
			OR
			(!\array_key_exists('url_redirect', $data))
			OR
		//	((string)$data['url_redirect'] != (string)self::OAUTH2_STANDALONE_REFRESH_URL) // can be also a valid redirect URL
		//	OR
			(!\array_key_exists('url_auth', $data))
			OR
			(!\array_key_exists('url_token', $data))
			OR
			(\strpos((string)$data['url_token'], 'https://') !== 0)
			OR // {{{SYNC-OAUTH2-VALIDATE-URL}}}
			(!\array_key_exists('code', $data))
			OR
			(!\array_key_exists('description', $data))
		) {
			return 'Invalid Data Structure';
		} //end if
		//--
		if((string)\trim((string)$data['id']) == '') {
			return 'Empty ID for the Token API Initialization';
		} //end if
		//--
		if($reinit === true) {
			$testExists = (array) self::getApiData((string)$data['id'], false); // no need to decrypt here, only ID is tested
			if(
				((int)\Smart::array_size((array)$testExists) <= 0)
				OR
				!isset($testExists['id'])
				OR
				((string)$testExists['id'] != (string)$data['id'])
			) {
				return 'Invalid ID for the Token API Refresh';
			} //end if
			$testExists = null;
		} //end if
		//--
		$cVfy = (string) self::codeVerifier((string)$data['id'], (string)$data['client_id']);
		if((string)$cVfy == '') {
			return 'Invalid ID Code Verifier';
		} //end if
		$cChl = (string) self::codeChallenge((string)$cVfy);
		if((string)$cChl == '') {
			return 'Invalid ID Code Challenge';
		} //end if
		//--
		$url = (string) \trim((string)$data['url_token']);
		if((string)\trim((string)$url) == '') {
			return []; // the token URL is empty, cannot update
		} //end if
		$uarr = (array) self::parseUrlAndSettings((string)$url);
		$url = (string) $uarr['url']; // settings for init have to be used from auth URL
		$uarr = null;
		//--
		$aurl = (string) \trim((string)$data['url_auth']);
		if((string)\trim((string)$aurl) == '') {
			return []; // the authorize URL is empty, error
		} //end if
		$uarr = (array) self::parseUrlAndSettings((string)$aurl);
		$settings = (array) $uarr['settings']; // use the settings from auth URL
		$uarr = null;
		//--
		$bw = new \SmartHttpClient();
		$bw->rawheaders = [ 'Accept' => 'application/json' ]; // this is because for github the answer can be without this header like: access_token=12345&token_type=bearer
		$bw->connect_timeout = (int) (((int)$timeout >= 15) && ((int)$timeout <= 60)) ? $timeout : 15; // {{{SYNC-OAUTH2-REQUEST-TIMEOUT}}}
		$bw->postvars = [
			'grant_type' 	=> (string) 'authorization_code',
			'code' 			=> (string) $data['code'],
			'client_id' 	=> (string) $data['client_id'],
			'client_secret' => (string) $data['client_secret'],
			'redirect_uri' 	=> (string) $data['url_redirect'],
		];
		if(!isset($settings['skip-PKCE'])) {
			$bw->postvars['code_verifier'] = (string) $cVfy;
		} //end if
		if(isset($settings['post-PARAMS'])) { // ex: the /authorize url when open in browser must send some params by get and after by post ...
			$extraParams = (array) \Smart::url_parse_query((string)\rawurldecode((string)$settings['post-PARAMS']));
			if((int)\Smart::array_size($extraParams) > 0) {
				$removeParams = [];
				foreach($extraParams as $kk => $vv) {
					$kk = (string) \trim((string)$kk);
					if((string)$kk != '') {
						if(!\array_key_exists((string)$kk, (array)$bw->postvars)) {
							$removeParams[] = (string)$kk; // must be removed from URL by GET method ; will be sent below with POST method
							$bw->postvars[(string)$kk] = $vv; // don't cast, may be string or array
						} //end if
					} //end if
				} //end foreach
				if((int)\Smart::array_size($removeParams) > 0) {
					$url = (string) \Smart::url_remove_params((string)$url, (array)$removeParams);
				} //end if
			} //end if
		} //end if
		$response = (array) $bw->browse_url((string)$url, 'POST', '', '', '', (int)self::OAUTH2_REQUEST_MAX_REDIRECTS);
		if(\SmartEnvironment::ifDebug()) {
			\Smart::log_notice(__METHOD__.' # DEBUG # '.$url."\n".'Post-Vars: '.print_r($bw->postvars,1)."\n".'Server-Response: '.print_r($response,1));
		} //end if
		if(((int)$response['result'] != 1) OR (((string)$response['code'] != '200'))) {
			return 'Invalid HTTP(S) Answer: ['.(int)$response['result'].'] / Status Code: '.(string)$response['code'];
		} //end if
		//--
		$json = \Smart::json_decode((string)$response['content']);
		if(\Smart::array_size($json) <= 0) {
			return 'Invalid HTTP(S) Answer: JSON Data is Invalid: `'.$response['content'].'`';
		} //end if
		//--
		if(
			(!isset($json['token_type']))
			OR
			((string)\strtolower((string)$json['token_type']) != 'bearer')
		//	OR
		//	(!isset($json['scope'])) // if the returned scope is empty or unset it means scope is invalid ...
		//	OR
		//	((string)\trim((string)$json['scope']) == '')
		//	OR
		//	( // facebook oauth2 does not provide any scope in the reply ...
		//		((string)$json['scope'] != (string)$data['scope']) // must contains exactly the scope
		//		AND
		//		(\strpos((string)$json['scope'], (string)$data['scope'].' ') !== 0) // scopes start with, appended with a space and other values ; ex: google apis append the returned scopes with ` openid`
		//		AND
		//		((string)\substr((string)$json['scope'], -1*(int)((int)\strlen((string)$json['scope'])+1), (int)((int)\strlen((string)$json['scope'])+1)) != (string)' '.$data['scope']) // scopes end with, prepend with a space and other values ; ex: google apis prepend the returned scopes with `openid `
		//	)
			OR
			(!isset($json['access_token']))
			OR
			((string)\trim((string)$json['access_token']) == '')
		) {
			return 'Invalid HTTP(S) Answer: JSON Structure is NOT Valid: '.$response['content'];
		} //end if else
		//--
		if(!isset($json['refresh_token'])) {
			$json['refresh_token'] = ''; // some providers do not use this (ex: github)
		} //end if
		if(!isset($json['expires_in'])) {
			$json['expires_in'] = 0; // some providers do not use this (ex: github)
		} //end if
		if((string)$json['refresh_token'] != '') {
			if((int)$json['expires_in'] <= 0) {
				return 'Invalid HTTP(S) Answer: JSON Structure contains an Invalid ExpireIn value: '.$response['content'];
			} //end if
		} //end if
		//--
		if(isset($json['scope'])) {
			$data['scope'] = (string) \trim((string)$json['scope']);
		} //end if
		//--
		$data['access_token'] = (string) $json['access_token'];
		$data['refresh_token'] = (string) $json['refresh_token'];
		$data['id_token'] = (string) ($json['id_token'] ?? null); // this is completely optional and only provided if supports OpenID
		//--
		$data['access_expire_seconds'] = (int) $json['expires_in'];
		//--
		if($reinit === true) {
			$del = (int) self::getDataModel()->deleteRecord((string)$data['id']);
			if((int)$del !== 1) {
				return 'Failed to (Re)Initialize the API ... Register it manually !';
			} //end if
			$del = null;
		} //end if
		//--
		$insert = (int) self::getDataModel()->insertRecord((array)$data, (string)$data['url_redirect']);
		if((int)$insert != 1) {
			if((int)$insert == -2) {
				return 'Duplicate ID';
			} else {
				return 'Failed to Store the Token API Data: #'.(int)$insert;
			} //end if else
		} //end if
		//--
		return (array) $data;
		//--
	} //END FUNCTION


	/**
	 * Get the API Data by ID
	 *
	 * @param STRING $id 		The unique API ID
	 * @param BOOL $decrypt 	Decrypt sensitive information ; Default is TRUE
	 * @return ARRAY 			The array containing the full api data
	 */
	public static function getApiData(string $id, bool $decrypt=true) : array {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return [];
		} //end if
		//--
		return (array) self::getDataModel()->getById((string)$id, (bool)$decrypt); // decrypt
		//--
	} //END FUNCTION


	/**
	 * Get the valid AccessToken for the given API by ID
	 * If the AccessToken is expired this function will make a sub-call to update the AccessToken using the stored RefreshToken and will return it
	 * If no valid Access Token can be returned, it will return a NULL value
	 *
	 * @param STRING 		$id 			The unique API ID
	 * @param INTEGER+ 		$timeout 		Default is 15 ; Max Request Timeout ; between 15..60 seconds
	 * @param BOOL 			$json 			Default is FALSE, will return text ; If set to TRUE will return JSON instead text
	 * @return STRING|NULL 					A string containing the current, valid, unexpired Access Token OR : if Token is expired will try to refresh it ; if fail to find, finally, a valid Access Token will return a NULL value
	 */
	public static function getApiAccessToken(string $id, bool $json=false, int $timeout=15) : ?string {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return null;
		} //end if
		//--
		$arr = (array) self::getApiData((string)$id);
		//--
		if(\Smart::array_size($arr) <= 0) {
			return null;
		} //end if
		//--
		if((int)$arr['active'] != 1) { // inactive
			return null;
		} //end if
		//--
		if((string)\trim((string)$arr['access_token']) == '') {
			return null;
		} //end if
		//--
		$data = [
			'#' 						=> 'OAUTH2:'.(\defined('\\SMART_SOFTWARE_NAMESPACE') ? \SMART_SOFTWARE_NAMESPACE : '*').':'.\SmartEnvironment::getArea().':'.\SmartAuth::get_auth_id(), // {{{SYNC-OAUTH2-AREA-PFX}}}
			'id' 						=> (string) $id,
			'access_token' 				=> '',
			'access_expire_time' 		=> 0,
			'access_expire_datetime' 	=> '',
			'date-time:tz' 				=> (string) \date_default_timezone_get(),
			'date-time:now' 			=> (string) \date('Y-m-d H:i:s O'),
		];
		//--
		if((string)\trim((string)$arr['refresh_token']) == '') { // token does not expires
			//--
			$data['access_token'] 			= (string) $arr['access_token'];
			$data['access_expire_time'] 	= -1; // does not expire
			$data['access_expire_datetime'] = 'n/a';
			//--
		} else { // it is an expiring token
			//--
			$expired = (int) ((int)\time() - 15); // make it expired with 15 sec before it real expires because the socket times must be considered also
			//--
			if((int)$arr['access_expire_time'] >= (int)$expired) {
				//--
				$data['access_token'] 			= (string) $arr['access_token'];
				$data['access_expire_time'] 	= (int)    $arr['access_expire_time'];
				//--
			} else { // the expired Access Token must be updated, it is expired
				//--
				$upd = (array) self::updateApiAccessToken((string)$id, (int)$timeout);
				if(\Smart::array_size($upd) > 0) {
					$data['access_token'] 		= (string) ($upd['access_token'] ?? null);
					$data['access_expire_time'] = (int)    ($upd['access_expire_time'] ?? null);
				} //end if
				//--
			} //end if else
			//--
			if((int)$data['access_expire_time'] < (int)\time()) {
				$data['access_expire_time'] = (int) ((int)\time() - 1); // fix
			} //end if
			$data['access_expire_datetime'] = (string) \date('Y-m-d H:i:s O', (int)$data['access_expire_time']);
			//--
		} //end if else
		//--
		$out = '';
		if($json === true) {
			$out = (string) \Smart::json_encode((array)$data);
		} else {
			$out = (string) $data['access_token'];
		} //end if
		//--
		return (string) $out; // string
		//--
	} //END FUNCTION


	/**
	 * Updates the API Access Token by ID
	 *
	 * @param STRING 		$id 			The unique API ID
	 * @param INTEGER+ 		$timeout 		Default is 15 ; Max Request Timeout ; between 15..60 seconds
	 * @return ARRAY 						Token Record Data or Empty Array
	 */
	public static function updateApiAccessToken(string $id, int $timeout=15) : array {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return [];
		} //end if
		//--
		$arr = (array) self::getApiData((string)$id);
		//--
		if(\Smart::array_size($arr) <= 0) {
			return [];
		} //end if
		//--
		if( // {{{SYNC-TOKEN-NON-EXPIRING-TEST}}}
			((string)\trim((string)$arr['refresh_token']) == '') // if there is no refresh token found, cannot update
			OR
			((int)$arr['access_expire_seconds'] <= 0) // if expiring seconds is not greater than zero it means also does not expires ; test the `access_expire_seconds` (provided by OAuth2 answer) instead of `access_expire_time` (calculated only)
		) {
			return []; // this type of tokens have no expiration, cannot be updated
		} //end if
		//--
		$url = (string) \trim((string)$arr['url_token']);
		if((string)\trim((string)$url) == '') {
			return []; // the token URL is empty, cannot update
		} //end if
		$uarr = (array) self::parseUrlAndSettings((string)$url);
		$url = (string) $uarr['url'];
		$settings = (array) $uarr['settings'];
		$uarr = null;
		//--
		$aurl = (string) \trim((string)$arr['url_auth']);
		if((string)\trim((string)$aurl) == '') {
			return []; // the authorize URL is empty, error
		} //end if
		$uarr = (array) self::parseUrlAndSettings((string)$aurl);
		$settings = (array) array_merge((array)$settings, (array)$uarr['settings']); // merge with the settings from auth URL ; if in auth url PKCE is skip, skip also here !
		$uarr = null;
		//--
		$cVfy = (string) self::codeVerifier((string)$arr['id'], (string)$arr['client_id']);
		if((string)$cVfy == '') {
			return []; // Invalid ID Code Verifier
		} //end if
		$cChl = (string) self::codeChallenge((string)$cVfy);
		if((string)$cChl == '') {
			return []; // Invalid ID Code Challenge
		} //end if
		//--
		$bw = new \SmartHttpClient();
		$bw->rawheaders = [ 'Accept' => 'application/json' ];
		$bw->connect_timeout = (int) (((int)$timeout >= 15) && ((int)$timeout <= 60)) ? $timeout : 15; // {{{SYNC-OAUTH2-REQUEST-TIMEOUT}}}
		$bw->postvars = [
			'grant_type' 	=> (string) 'refresh_token',
			'refresh_token' => (string) $arr['refresh_token'],
			'client_id' 	=> (string) $arr['client_id'],
			'client_secret' => (string) $arr['client_secret'],
		];
		if(!isset($settings['skip-PKCE'])) {
			$bw->postvars['code_verifier'] = (string) $cVfy;
		} //end if
		if(isset($settings['post-PARAMS'])) { // ex: the /authorize url when open in browser must send some params by get and after by post ...
			$extraParams = (array) \Smart::url_parse_query((string)\rawurldecode((string)$settings['post-PARAMS']));
			if((int)\Smart::array_size($extraParams) > 0) {
				$removeParams = [];
				foreach($extraParams as $kk => $vv) {
					$kk = (string) \trim((string)$kk);
					if((string)$kk != '') {
						if(!\array_key_exists((string)$kk, (array)$bw->postvars)) {
							$removeParams[] = (string)$kk; // must be removed from URL by GET method ; will be sent below with POST method
							$bw->postvars[(string)$kk] = $vv; // don't cast, may be string or array
						} //end if
					} //end if
				} //end foreach
				if((int)\Smart::array_size($removeParams) > 0) {
					$url = (string) \Smart::url_remove_params((string)$url, (array)$removeParams);
				} //end if
			} //end if
		} //end if
		$response = (array) $bw->browse_url((string)$url, 'POST', '', '', '', (int)self::OAUTH2_REQUEST_MAX_REDIRECTS);
		if(\SmartEnvironment::ifDebug()) {
			\Smart::log_notice(__METHOD__.' # DEBUG # '.$url."\n".'Post-Vars: '.print_r($bw->postvars,1)."\n".'Server-Response: '.print_r($response,1));
		} //end if
		if(((int)$response['result'] != 1) OR (((string)$response['code'] != '200'))) {
			$logs = 'Invalid HTTP(S) Answer for Refresh Access Token: ['.(int)$response['result'].'] / Status Code: '.(string)$response['code'];
			$json = \Smart::json_decode((string)$response['content']);
			if(\Smart::array_size($json) > 0) {
				if(isset($json['error'])) {
					$logs .= "\n".'Error: `'.(string)\trim((string)$json['error']).'`';
				} //end if
				if(isset($json['error_description'])) {
					$logs .= "\n".'Error-Description: `'.(string)\trim((string)$json['error_description']).'`';
				} //end if
			} //end if
			self::getDataModel()->updateRecordLogs((string)$id, (string)'# '.\date('Y-m-d H:i:s O')."\n".'# '.$logs, true);
			return [];
		} //end if
		//--
		$json = \Smart::json_decode((string)$response['content']);
		if(\Smart::array_size($json) <= 0) {
			$logs = 'Invalid HTTP(S) JSON Answer for Refresh Access Token: '."\n".(string)\base64_encode((string)$response['content']);
			self::getDataModel()->updateRecordLogs((string)$id, (string)'# '.\date('Y-m-d H:i:s O')."\n".'# '.$logs, true);
			return [];
		} //end if
		//-- Fix: https://www.oauth.com/oauth2-servers/making-authenticated-requests/refreshing-an-access-token/
		if(!isset($json['refresh_token'])) {
			$json['refresh_token'] = (string) $arr['refresh_token']; // fix: preserve ! ; if you do not get back a new refresh token, then it means your existing refresh token will continue to work when the new access token expires
		} //end if
		//--
		if(
			(!isset($json['token_type']))
			OR
			((string)strtolower((string)$json['token_type']) != 'bearer')
		//	OR
		//	(!isset($json['scope']))
		//	OR // facebook oauth2 does not provide any scope in the reply ...
		//	((string)$json['scope'] != (string)$arr['scope'])
			OR
			(!isset($json['access_token']))
			OR
			((string)\trim((string)$json['access_token']) == '')
			OR
			(!isset($json['refresh_token']))
			OR
			((string)\trim((string)$json['refresh_token']) == '')
			OR
			(!isset($json['expires_in']))
			OR
			((int)$json['expires_in'] <= 0)
		) {
			$logs = 'Invalid HTTP(S) JSON Structure Answer for Refresh Access Token: '."\n".(string)\Smart::json_encode((array)$json, false, false, true);
			self::getDataModel()->updateRecordLogs((string)$id, (string)'# '.\date('Y-m-d H:i:s O')."\n".'# '.$logs, true);
			return [];
		} //end if else
		//--
		if(isset($json['id_token'])) {
			$json['id_token'] = (string) $json['id_token']; // ensure cast to string
		} else {
			$json['id_token'] = null; // ensure cast to null, ensure key
		} //end if
		//--
		$upd = (int) self::getDataModel()->updateRecordAccessToken(
			(string) $id,
			(int)    $json['expires_in'],
			(string) $json['access_token'],
			(string) $json['refresh_token'],
					 $json['id_token'] // do not cast, can be null
		);
		if((int)$upd != 1) {
			return [];
		} //end if
		//--
		return (array) self::getApiData((string)$id);
		//--
	} //END FUNCTION


	public static function deleteApiAccessToken(string $id) : int {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return -888;
		} //end if
		//--
		$arr = (array) self::getApiData((string)$id, false); // no need to decrypt here, just test if exists
		//--
		if(\Smart::array_size($arr) <= 0) {
			return -999;
		} //end if
		//--
		return (int) self::getDataModel()->deleteRecord((string)$id);
		//--
	} //END FUNCTION


	/**
	 * Update the API Status by ID
	 *
	 * @param STRING $id 		The unique API ID
	 * @param STRING $status 	The status value: 0/1, true/false, active/inactive
	 * @return INTEGER 			On SUCCESS will return 1
	 */
	public static function updateApiStatus(string $id, string $status) : int {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return -888;
		} //end if
		//--
		if(
			((string)\strtolower((string)$status) == 'active') OR
			((string)\strtolower((string)$status) == 'true') OR
			((string)$status == '1')
		) {
			$value = 1;
		} else {
			$value = 0;
		} //end if else
		//--
		return (int) self::getDataModel()->updateStatus((string)$id, (int)$value);
		//--
	} //END FUNCTION


	/**
	 * Update the API Description by ID
	 *
	 * @param STRING $id 		The unique API ID
	 * @param STRING $desc 		The description ; max length: 1024
	 * @return INTEGER 			On SUCCESS will return 1
	 */
	public static function updateApiDesc(string $id, string $desc) : int {
		//--
		if(\trim((string)\SmartAuth::get_auth_id()) == '') {
			\Smart::log_warning(__METHOD__.' # requires an Authenticated User !');
			return -888;
		} //end if
		//--
		return (int) self::getDataModel()->updateDesc((string)$id, (string)$desc);
		//--
	} //END FUNCTION


	// this is the private key, unencrypted
	public static function csrfNewPrivateKey() : string {
		//--
		return (string) \SmartCsrf::newPrivateKey();
		//--
	} //END FUNCTION


	public static function csrfPrivateKeyEncrypt(string $privKey) : string {
		//--
		$privKey = (string) \trim((string)$privKey);
		if((string)$privKey == '') {
			return '';
		} //end if
		//--
		return (string) \SmartCipherCrypto::tf_encrypt((string)$privKey, '', true); // def key,  TF + BF
		//--
	} //END FUNCTION


	public static function csrfPrivateKeyDecrypt(string $encPrivKey) : string {
		//--
		$encPrivKey = (string) \trim((string)$encPrivKey);
		if((string)$encPrivKey == '') {
			return '';
		} //end if
		//--
		return (string) \SmartCipherCrypto::tf_decrypt((string)$encPrivKey); // def key,  TF + BF, don't fallback
		//--
	} //END FUNCTION


	// this generate the private key, with a secret based on: ClientIP, UA-Signature and SMART_FRAMEWORK_SECURITY_KEY
	public static function csrfPublicKey(string $privKey) : string {
		//--
		return (string) \SmartCsrf::getPublicKey(
			(string) $privKey,
			(string) self::csrfSecret()
		);
		//--
	} //END FUNCTION


	// check ...
	public static function csrfCheckState(string $pubKey, string $privKey) : bool {
		//--
		$pubKey = (string) \trim((string)$pubKey);
		$privKey = (string) \trim((string)$privKey);
		//--
		$out = false;
		//--
		if(
			((string)$pubKey != '')
			AND
			((string)$privKey != '')
		) {
			$out = (bool) \SmartCsrf::verifyKeys(
				(string) $pubKey,
				(string) $privKey,
				(string) self::csrfSecret()
			);
		} //end if
		//--
		return (bool) $out;
		//--
	} //END FUNCTION


	//##### PRIVATES


	private static function parseUrlAndSettings(string $url) : array {
		//--
		$url = (string) \trim((string)$url);
		//--
		$arr = \Smart::url_parse((string)$url, true);
		//--
		return [
			'url' 		=> (string) $url = (string) $arr['protocol'].$arr['host'].($arr['port'] ? ':'.$arr['port'] : '').$arr['path'].($arr['query'] ? '?'.$arr['query'] : ''),
			'settings' 	=> (array)  \Smart::url_parse_query($arr['fragment']),
		];
		//--
	} //END FUNCTION


	private static function codeVerifier(string $id, string $cid) : string {
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$cid = (string) \trim((string)$cid);
		if((string)$cid == '') {
			return '';
		} //end if
		//--
		return (string) \Smart::base_from_hex_convert((string)\SmartHashCrypto::hmac('sha3-384', (string)\Smart::dataRot13((string)\Smart::b64s_enc((string)$id)), (string)$cid), 62);
		//--
	} //END FUNCTION


	private static function codeChallenge(string $cVfy) : string {
		//--
		$cVfy = (string) \trim((string)$cVfy);
		if((string)$cVfy == '') {
			return '';
		} //end if
		//--
		return (string) \Smart::b64_to_b64s((string)\SmartHashCrypto::sha256((string)$cVfy, true), false); // b64u ; {{{SYNC-OAUTH2-OAUTH2_AUTHORIZE_URL_CHPART}}}
		//--
	} //END FUNCTION


	private static function csrfSecret() : string {
		//--
		return (string) 'Oauth2:API:'.\SmartUtils::unique_client_private_key(); // the secret ; do not use \SmartUtils::unique_auth_client_private_key() here, the form is on backend, get-code is on frontend, auth user does not match !
		//--
	} //END FUNCTION


	private static function getDataModel() : \SmartModDataModel\Oauth2\SqOauth2 {
		//--
		if(self::$model === null) {
			self::$model = new \SmartModDataModel\Oauth2\SqOauth2();
		} //end if
		//--
		return (object) self::$model;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
