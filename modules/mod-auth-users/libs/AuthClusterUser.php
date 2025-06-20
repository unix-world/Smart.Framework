<?php
// PHP Auth Users Cluster for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\AuthUsers;

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
 * Class: \SmartModExtLib\AuthUsers\AuthClusterUser
 * Auth Users Cluster
 *
 * @depends \SmartModExtLib\AuthUsers\Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250620
 * @package 	modules:AuthUsers
 *
 */
final class AuthClusterUser {

	// ::


	private static ?array $nodes = null;


	public static function getAuthClusterUrlPrefixMaster() : string {
		//--
		if(\SmartAuth::is_cluster_master_auth() === true) {
			return ''; // on the Master Server use no prefix
		} //end if
		//--
		if(!\defined('\\SMART_AUTH_USERS_CLUSTER_MASTER')) {
			\Smart::log_warning(__METHOD__.' # The SMART_AUTH_USERS_CLUSTER_MASTER constant is undefined on this Auth Slave Server');
			return '';
		} //end if
		//--
		return (string) \SMART_AUTH_USERS_CLUSTER_MASTER; // on Slave Servers use the Master Prefix
		//--
	} //END FUNCTION


	public static function getAuthClusterUrlPrefixLocal() : string {
		//--
		if(\SmartAuth::is_cluster_master_auth() !== true) {
			return '';
		} //end if
		//--
		$cluster = (string) \SmartAuth::get_auth_cluster_id();
		if((string)\trim((string)$cluster) == '') {
			return ''; // if account have no cluster ID set, it is on Master Server
		} //end if
		//--
		$node = (array) self::getAuthClusterNode((string)$cluster);
		//--
		if((int)\Smart::array_size($node) <= 0) {
			return '';
		} //end if
		//--
		return (string) ($node['url-base'] ?? null);
		//--
	} //END FUNCTION


	public static function getAuthClusterNode(string $cluster) : array {
		//--
		$cluster = (string) \trim((string)$cluster);
		if((string)$cluster == '') {
			\Smart::log_warning(__METHOD__.' # Empty Cluster Node ID');
			return [];
		} //end if
		if(\SmartAuth::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			\Smart::log_warning(__METHOD__.' # Invalid Cluster Node ID: `'.$cluster.'`');
			return [];
		} //end if
		//--
		$arr = (array) self::getAuthClusterNodes();
		if((int)\Smart::array_size($arr) <= 0) {
			\Smart::log_warning(__METHOD__.' # No Available Cluster Nodes Found');
			return [];
		} //end if
		//--
		$node = (array) ($arr[(string)$cluster] ?? null);
		if((int)\Smart::array_size($node) <= 0) {
			\Smart::log_warning(__METHOD__.' # Cluster Node Not Found: `'.$cluster.'`');
			return [];
		} //end if
		//--
		return (array) $node;
		//--
	} //END FUNCTION


	public static function getAuthClusterNodes() : array {
		//--
		if(self::$nodes !== null) {
			return (array) self::$nodes;
		} //end if
		//--
		if(\SmartAuth::is_cluster_master_auth() !== true) {
			self::$nodes = [];
			return (array) self::$nodes;
		} //end if
		//--
		if(!\defined('\\SMART_AUTH_USERS_CLUSTER_NODES')) { // this is just for master
			self::$nodes = [];
			return (array) self::$nodes; // no warning, this is the default case running without cluster nodes
		} //end if
		//--
		if((int)\Smart::array_size(\SMART_AUTH_USERS_CLUSTER_NODES) <= 0) {
			\Smart::log_warning(__METHOD__.' # Invalid Cluster Nodes definition format');
			self::$nodes = [];
			return (array) self::$nodes;
		} //end if
		//--
		$arr = [];
		foreach(\SMART_AUTH_USERS_CLUSTER_NODES as $key => $val) {
			$key = (string) \trim((string)$key);
			if((string)$key != '') {
				if(\SmartAuth::validate_cluster_id((string)$key) === true) {
					if((int)\Smart::array_size($val) > 0) {
						$allowInsecure = (bool) (($val['insecure'] ?? null) === true) ? true : false;
						$url = (string) \trim((string)($val['url'] ?? null));
						if(
							((string)$url != '')
							AND
							(
								(\strpos((string)$url, 'https://') === 0)
								OR
								(($allowInsecure === true) AND (\strpos((string)$url, 'http://') === 0))
							)
							AND
							(\strpos((string)$url, '?') === false)
							AND
							(\strpos((string)$url, '&') === false)
							AND
							((int)\strlen((string)$url) >= 10)
							AND
							((int)\strlen((string)$url) <= 255)
							AND
							((string)\substr((string)$url, -1, 1) == '/') // must end with slash
						) {
							$user = (string) \trim((string)($val['user'] ?? null));
							if((string)$user != '') { // do not validate user, may be valid or SmartHttpUtils::AUTH_USER_TOKEN
								$pass = (string) \trim((string)($val['pass'] ?? null));
								if((string)$pass != '') {
									$arr[(string)$key] = [
										'insecure' 	=> (bool)   $allowInsecure,
										'url-base' 	=> (string) $url,
										'url' 		=> (string) $url.'task.php?page=auth-users.cluster',
										'user' 		=> (string) $user,
										'pass' 		=> (string) $pass,
									];
								} //end if
							} else {
								\Smart::log_warning(__METHOD__.' # Invalid Cluster Node definition (user) for key: `'.$key.'`');
							} //end if else
						} else {
							\Smart::log_warning(__METHOD__.' # Invalid Cluster Node definition (url) for key: `'.$key.'`');
						} //end if else
					} else {
						\Smart::log_warning(__METHOD__.' # Invalid Cluster Node value for key: `'.$key.'`');
					} //end if else
				} else {
					\Smart::log_warning(__METHOD__.' # Invalid Cluster Node definition for key: `'.$key.'`');
				} //end if else
			} else {
				\Smart::log_warning(__METHOD__.' # Empty Cluster Node definition key');
			} //end if else
		} //end foreach
		//--
		self::$nodes = (array) $arr;
		//--
		return (array) self::$nodes;
		//--
	} //END FUNCTION


	public static function refreshAccountWorkspace(string $id) : bool {
		//--
		// this method operates just on auth master server, used by refresh account settings only
		// it must operate only for a logged in email address
		//--
		$id = (string) \trim((string)$id);
		if( // {{{SYNC-VALIDATE-AUTH-BY-USERNAME}}}
			((string)$id == '')
			OR
			((int)\strlen((string)$id) != 21)
			OR
			(\strpos((string)$id, '@') !== false)
		) {
			\Smart::log_warning(__METHOD__.' # ID is Empty or Invalid: `'.$id.'`');
			return false;
		} //end if
		//--
		if(\SmartAuth::is_cluster_master_auth() !== true) {
			\Smart::log_warning(__METHOD__.' # This method can be run just on Auth Master Server');
			return false;
		} //end if
		//--
		$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)$id);
		if((int)\Smart::array_size($userData) <= 0) {
			\Smart::log_warning(__METHOD__.' # Account Not Found for: `'.$id.'`');
			return false;
		} //end if
		//--
		if((string)\trim((string)($userData['jwtserial'] ?? null)) == '') {
			\Smart::log_warning(__METHOD__.' # Account JWT Serial is Empty for: `'.$id.'`');
			return false;
		} //end if
		if((string)\trim((string)($userData['jwtsignature'] ?? null)) == '') {
			\Smart::log_warning(__METHOD__.' # Account JWT Signature is Empty for: `'.$id.'`');
			return false;
		} //end if
		//--
		$arrLoginInfo = \Smart::json_decode((string)$userData['authlog'], true); // this is using 1 level on standard and 2 levels on SSO
		if((int)\Smart::array_size($arrLoginInfo) <= 0) {
			\Smart::log_warning(__METHOD__.' # Account Auth Log is Invalid for: `'.$id.'`');
			return false;
		} //end if
		//--
		return (bool) self::setAccountWorkspace( // {{{SYNC-CREATE-ACCOUNT-LOGIN-WORKSPACE}}}
			(string) ($userData['cluster'] ?? null),
			(string) ($userData['email'] ?? null),
			(string) ($userData['jwtserial'] ?? null),
			(string) ($userData['jwtsignature'] ?? null),
			(array)  $arrLoginInfo
		);
		//--
	} //END FUNCTION


	public static function setAccountWorkspace(string $cluster, string $email, string $jwtserial, string $jwtsignature, array $arrLoginInfo) : bool {
		//--
		// this method operates just on auth master server, used by login only
		// if the account cluster is set on this server will create localy, else will push to the assigned cluster server for that account
		//--
		if((!\defined('\\SMART_FRAMEWORK_SECURITY_KEY')) OR ((string)\trim((string)\SMART_FRAMEWORK_SECURITY_KEY) == '')) {
			\Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SECURITY_KEY is not defined or empty !');
			return false;
		} //end if
		//--
		if(\SmartAuth::is_cluster_master_auth() !== true) {
			\Smart::log_warning(__METHOD__.' # This method can be run just on Auth Master Server');
			return false;
		} //end if
		if((string)\SmartAuth::get_cluster_id() != '') {
			\Smart::log_warning(__METHOD__.' # This method must run on Auth Cluster Default Server');
			return false;
		} //end if
		//--
		$cluster = (string) \trim((string)$cluster); // can be empty
		if(\SmartAuth::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			\Smart::log_warning(__METHOD__.' # Cluster is Empty or Invalid: `'.$cluster.'` for `'.$email.'`');
			return false;
		} //end if
		//--
		$email = (string) \trim((string)$email);
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			\Smart::log_warning(__METHOD__.' # Email is Empty or Invalid: `'.$email.'`');
			return false;
		} //end if
		//--
		$jwtserial = (string) \trim((string)$jwtserial);
		if((string)$jwtserial == '') {
			\Smart::log_warning(__METHOD__.' # JWT Serial is Empty for: `'.$email.'`');
			return false;
		} //end if
		//--
		$jwtsignature = (string) \trim((string)$jwtsignature);
		if((string)$jwtsignature == '') {
			\Smart::log_warning(__METHOD__.' # JWT Signature is Empty for: `'.$email.'`');
			return false;
		} //end if
		//--
		if((int)\Smart::array_size($arrLoginInfo) <= 0) {
			\Smart::log_warning(__METHOD__.' # Auth Log is Empty for: `'.$email.'`');
			return false;
		} //end if
		//--
		$setLogin = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::setAccountLogin( // this is mandatory to set the JWT serial in DB to allow the login with this JWT
			[
				'email' 		=> (string) $email,
				'jwtserial' 	=> (string) $jwtserial,
				'jwtsignature' 	=> (string) $jwtsignature,
				'authlog' 		=> (string) \Smart::json_encode((array)$arrLoginInfo, true, true, false), // prettyprint, unescaped unicode,html
			]
		);
		if((int)$setLogin != 1) {
			\Smart::log_warning(__METHOD__.' # Auth Set Login Failed ('.$setLogin.') for: `'.$email.'`');
			return false;
		} //end if
		//--
		$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountByEmail((string)$email);
		if((int)\Smart::array_size($userData) <= 0) {
			\Smart::log_warning(__METHOD__.' # Account Not Found for: `'.$email.'`');
			return false;
		} //end if
		//--
		if((string)$cluster == '') { // local account
			//--
			if(self::updateAccountWorkspaceAuthData((array)$userData) !== true) {
				\Smart::log_warning(__METHOD__.' # Auth Set Workspace Data Failed for: `'.$email.'`');
				return false;
			} //end if
			//--
		} else { // remote account: push
			//--
			$node = (array) self::getAuthClusterNode((string)$cluster);
			if((int)\Smart::array_size($node) <= 0) {
				\Smart::log_warning(__METHOD__.' # Auth Cluster Remote Update Failed, Node is missing for: `'.$email.'`');
				return false;
			} //end if
			//--
			if((string)$node['user'] === ':SWT') {
				$tmpArr = (array) explode('#', (string)$node['pass']);
				$swt_token = (array) \SmartAuth::swt_token_create(
					'A',
					(string) ($tmpArr[0] ?? null),
					(string) \Smart::b64_dec((string)($tmpArr[1] ?? null)),
					60,
					[
						(string) \SmartUtils::get_server_current_ip(),
					],
					[
						'auth-users:cluster',
					]
				);
				$tmpArr = null;
				if($swt_token['error'] !== '') {
					\Smart::log_warning(__METHOD__.' # SWT Token Creation Failed with ERROR: '.$swt_token['error']);
					return false;
				} //end if
				$node['user'] = (string) \SmartHttpUtils::AUTH_USER_BEARER;
				$node['pass'] = (string) $swt_token['token'];
				$swt_token = null;
			} //end if
			//--
			$data = (string) \trim((string)\Smart::json_encode((array)$userData, false, true, false, 1));
			if((string)$data == '') {
				\Smart::log_warning(__METHOD__.' # JSON Encoding Failed');
				return false;
			} //end if
			//--
			$data = (string) \trim((string)\SmartCipherCrypto::t3f_encrypt((string)$data, 'AuthUsers:Cluster:'."\r".\SMART_FRAMEWORK_SECURITY_KEY, true)); // {{{SYNC-USER-AUTH-CLUSTER-CRYPTO-EXCHANGE-KEY}}}
			if((string)$data == '') {
				\Smart::log_warning(__METHOD__.' # Encryption Failed');
				return false;
			} //end if
			//--
			$action = 'auth-users:cluster:set:account.json';
			$checksum = (string) \SmartHashCrypto::checksum((string)$action."\r".$data);
			//--
			$bw = new \SmartHttpClient();
			$bw->connect_timeout = 10;
		//	$bw->rawheaders = [ 'Accept' => 'application/json' ];
			if($node['insecure'] !== true) {
				$bw->securemode = true; // enable SSL/TLS Strict Secure Mode by default
			} //end if
			$bw->postvars = [
				'action' 	=> (string) $action,
				'data' 		=> (string) $data,
				'checksum' 	=> (string) $checksum,
			];
			$response = (array) $bw->browse_url((string)$node['url'], 'POST', '', (string)$node['user'], (string)$node['pass'], 0);
			$isAccountSynced = false;
			if(((int)$response['result'] == 1) AND (((string)$response['code'] == '201'))) {
				if((int)\strlen((string)$response['content']) <= 65535) {
					$answer = \Smart::json_decode((string)$response['content'], true, 1);
					if((string)($answer['status'] ?? null) == '200') {
						if((string)($answer['message'] ?? null) == 'OK') {
							$isAccountSynced = true;
						} //end if
					} //end if
				} //end if
			} //end if
			$response = null;
			$bw = null;
			//--
			if($isAccountSynced !== true) {
				\Smart::log_warning(__METHOD__.' # Auth Set Remote Workspace Failed for: `'.$email.'` on cluster: `'.$cluster.'`');
				return false;
			} //end if
			//--
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION


	public static function getAccountWorkspace(string $cluster, string $id, string $email) : array {
		//--
		// this method operates just on any server
		// on account's current server will return the data from the account workspace
		// on master server, if the master server is not the current workspace will return the data from DB
		// this method allows a login on the master server even if the account workspace is elsewhere because the account settings can be modified just on master server
		//--
		$cluster = (string) \trim((string)$cluster); // can be empty
		if(\SmartAuth::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			\Smart::log_warning(__METHOD__.' # Cluster ID is Invalid: `'.$email.'`');
			return [];
		} //end if
		//--
		$id = (string) \trim((string)$id);
		//--
		if( // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-ID}}}
			((string)$id == '')
			OR
			((int)\strlen((string)$id) != 21)
			OR
			(\strpos((string)$id, '.') === false)
			OR
			(\SmartAuth::validate_auth_username((string)$id, false) !== true)
		) {
			\Smart::log_warning(__METHOD__.' # ID is Empty or Invalid: `'.$email.'`');
			return [];
		} //end if
		//--
		$email = (string) \trim((string)$email);
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			\Smart::log_warning(__METHOD__.' # Email is Empty or Invalid: `'.$id.'`');
			return [];
		} //end if
		//--
		$userDbPath = (string) \trim((string)self::getAccountWorkspacePath((string)$id));
		if(((string)$userDbPath == '') OR (\strpos((string)$userDbPath, '#db/') !== 0)) {
			\Smart::log_warning(__METHOD__.' # Account Data WorkSpace Path is Empty or Invalid');
			return [];
		} //end if
		//--
		$userData = [];
		$isLocalWorkspace = false;
		if(
			(\SmartFileSystem::path_exists((string)$userDbPath) === true)
			AND
			(\SmartFileSystem::is_type_dir((string)$userDbPath) === true)
			AND
			(\SmartFileSystem::path_exists((string)$userDbPath.'account.json') === true)
			AND
			(\SmartFileSystem::is_type_file((string)$userDbPath.'account.json') === true)
		) { // local workspace (master or slave server)
			$userJsonData = (string) \trim((string)\SmartFileSystem::read((string)$userDbPath.'account.json', 0, 'no', 'yes', true, false));
			if((string)$userJsonData == '') {
				\Smart::log_warning(__METHOD__.' # Account Data JSON is Empty for `'.$email.'`');
				return [];
			} //end if
			$userData = \Smart::json_decode((string)$userJsonData, true, 1); // do not cast
			if(!\is_array($userData)) {
				\Smart::log_warning(__METHOD__.' # Account Data JSON is Wrong for `'.$email.'`');
				$userData = [];
			} //end if
			$isLocalWorkspace = true;
		} else if(\SmartAuth::is_cluster_master_auth() === true) { // master server with remote workspace
			$userData = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)$id);
		} //end if
		//--
		if((int)\Smart::array_size($userData) <= 0) {
			return []; // do not log, this is the case where the login server is not master and not the one where user have it's own account workspace set
		} //end if
		//--
		if((string)($userData['cluster'] ?? null) !== (string)$cluster) {
			\Smart::log_warning(__METHOD__.' # Account Data Cluster ID Mismatch for `'.$email.'`');
			return [];
		} //end if
		if((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)($userData['id'] ?? null)) !== (string)$id) {
			\Smart::log_warning(__METHOD__.' # Account Data ID Mismatch for `'.$email.'`');
			return [];
		} //end if
		if((string)($userData['id'] ?? null) !== (string)\SmartModExtLib\AuthUsers\Utils::userNameToUserAccountId((string)$id)) {
			\Smart::log_warning(__METHOD__.' # Account Data User ID Mismatch for `'.$email.'`');
			return [];
		} //end if
		//--
		if($isLocalWorkspace !== true) {
			if((string)($userData['cluster'] ?? null) === (string)\SmartAuth::get_cluster_id()) {
				\Smart::log_warning(__METHOD__.' # Account Data Workspace is missing for `'.$email.'`');
				return []; // force logout, the account workspace should exist on this server but is not, should be created on next login ...
			} //end if
		} //end if
		//--
		$userData['#workspace:is:local'] = (bool) $isLocalWorkspace;
		//--
		return (array) $userData;
		//--
	} //END FUNCTION


	public static function getAccountWorkspacePath(string $id) : string {
		//--
		// this method operates on any server because it just returns a path
		// ... but of course the path may not exist except on the server where the account namespace is set
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$userDbPath = (string) \trim((string)\SmartAuth::get_user_prefixed_path_by_area_and_account_id('idx', (string)$id));
		if((string)$userDbPath == '') {
			return '';
		} //end if
		//--
		return (string) \SmartFileSysUtils::addPathTrailingSlash('#db/'.$userDbPath);
		//--
	} //END FUNCTION


	public static function updateAccountWorkspaceAuthData(array $userData) : bool {
		//--
		// this method operates just on account's current server, where the account workspace is set
		// on master server for the local accounts should be used directly by setAccountWorkspace()
		// on remote servers should be called inside cluster.php sync action that will do the job remotely
		//--
		if((int)\Smart::array_size($userData) <= 0) {
			\Smart::log_warning(__METHOD__.' # Account Data is Empty');
			return false;
		} //end if
		//--
		$email = (string) \trim((string)($userData['email'] ?? null));
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)$email == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
		) {
			\Smart::log_warning(__METHOD__.' # Email is Empty or Invalid: `'.$email.'`');
			return false;
		} //end if
		//--
		$id = (string) \trim((string)($userData['id'] ?? null));
		if((string)$id == '') {
			\Smart::log_warning(__METHOD__.' # Account User ID is Empty for: `'.$email.'`');
			return false;
		} //end if
		$id = (string) \trim((string)\SmartModExtLib\AuthUsers\Utils::userAccountIdToUserName((string)$id));
		if( // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-ID}}}
			((string)$id == '')
			OR
			((int)\strlen((string)$id) != 21)
			OR
			(\strpos((string)$id, '.') === false)
			OR
			(\SmartAuth::validate_auth_username((string)$id, false) !== true)
		) {
			\Smart::log_warning(__METHOD__.' # Account ID is Empty or Invalid: `'.$id.'` for `'.$email.'`');
			return false;
		} //end if
		//--
		$cluster = (string) \trim((string)($userData['cluster'] ?? null)); // can be empty
		if(\SmartAuth::validate_cluster_id((string)$cluster) !== true) { // {{{SYNC-AUTH-USERS-SAFE-VALIDATE-CLUSTER}}}
			\Smart::log_warning(__METHOD__.' # Cluster is Empty or Invalid: `'.$cluster.'` for `'.$email.'`');
			return false;
		} //end if
		//--
		if((string)\SmartAuth::get_cluster_id() != (string)$cluster) {
			\Smart::log_warning(__METHOD__.' # Wrong Cluster ID selected for Account: `'.$cluster.'` for `'.$email.'`');
			return false;
		} //end if
		//--
		$jsonContent = (string) \trim((string)\Smart::json_encode((array)$userData, true, true, false, 1));
		if((string)$jsonContent == '') {
			\Smart::log_warning(__METHOD__.' # JSON Data is Empty for: `'.$email.'`');
			return false;
		} //end if
		//--
		$userDbPath = (string) \trim((string)self::getAccountWorkspacePath((string)$id));
		if(((string)$userDbPath == '') OR (\strpos((string)$userDbPath, '#db/') !== 0)) {
			\Smart::log_warning(__METHOD__.' # DB Dir is Empty or Invalid: `'.$userDbPath.'` for ID: `'.$id.'`');
			return false;
		} //end if
		//--
		$err = (string) \SmartFileSystem::create_protected_dir('#db/');
		if((string)$err != '') {
			\Smart::log_warning(__METHOD__.' # Failed to Create Protected DB Base Dir for: `'.$userDbPath.'` for ID: `'.$id.'`');
			return false;
		} //end if
		//--
		$err = (string) \SmartFileSystem::create_protected_dir((string)$userDbPath);
		if((string)$err != '') {
			\Smart::log_warning(__METHOD__.' # Failed to Create Protected DB Dir for: `'.$userDbPath.'` for ID: `'.$id.'`');
			return false;
		} //end if
		//--
		$wr = (int) \SmartFileSystem::write((string)$userDbPath.'account.json', (string)$jsonContent, 'w', true);
		if($wr !== 1) {
			\Smart::log_warning(__METHOD__.' # Failed to Write Data JSON for: `'.$userDbPath.'` for ID: `'.$id.'`');
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
