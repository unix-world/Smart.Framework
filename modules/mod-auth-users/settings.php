<?php
// Controller: AuthUsers/Settings
// Route: ?page=auth-users.settings
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX');
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppIndexController extends \SmartModExtLib\AuthUsers\AbstractAccountController {

	// r.20260115

	// SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS 	is verified by Initialize() in AbstractAccountController
	// Custom request URI Restriction 			is verified by Initialize() in AbstractAccountController

	public function Run() {

		// this controller can operate ONLY on master server

/*
// TODO: use this to generate a token with some $data[] signed or hashed by the Security Key ; once the security key changes tokens are invalidated
// each time the generate API Key button (in the password tab is clicked will send a request to the settings.api and will return a new JWT like below
// the JWT will display below the password fields in a div of a readonly text area
$apiToken = \SmartModExtLib\AuthUsers\AuthJwt::newAuthJwtToken('api', '@', '', SmartAuth::get_auth_id(), SmartAuth::get_auth_username()); print_r($apiToken); die();
*/

		//--
		if(SmartAuth::is_cluster_master_auth() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Master Server');
			return;
		} //end if
		//--

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetErrorStatus(403, 'Authentication is Required');
			return;
		} //end if
		//--
		$record = (array) \SmartModDataModel\AuthUsers\AuthUsersFrontend::getAccountById((string)SmartAuth::get_auth_id());
		if((int)Smart::array_size($record) <= 0) {
			$this->PageViewSetErrorStatus(500, 'Account is N/A');
			return;
		} //end if
		if((string)($record['id'] ?? null) !== (string)\SmartModExtLib\AuthUsers\Utils::userNameToUserAccountId((string)SmartAuth::get_auth_id())) {
			$this->PageViewSetErrorStatus(500, 'Account ID Mismatch');
			return;
		} //end if
		if((string)($record['email'] ?? null) !== (string)SmartAuth::get_auth_username()) {
			$this->PageViewSetErrorStatus(500, 'Account UserName Mismatch');
			return;
		} //end if
		//--

		//--
		\SmartModExtLib\AuthUsers\Utils::setCsrfCookie();
		//--

		//--
		$mode = (string) $this->RequestVarGet('mode', '', 'string');
		//--
		if((string)$mode == '2fa') {
			//--
			$fa2_code   = (string) \SmartModExtLib\AuthUsers\Auth2FA::generateNewSecret();
			$fa2_url    = (string) \SmartModExtLib\AuthUsers\Auth2FA::get2FAUrl((string)$fa2_code, (string)SmartAuth::get_auth_username());
			$fa2_qrcode = (string) \SmartModExtLib\AuthUsers\Auth2FA::get2FASvgBarCode((string)$fa2_code, (string)SmartAuth::get_auth_username());
			//--
			$title = 'Modify Your Account Settings - 2FA';
			$this->PageViewSetVars([
				'title' => (string) $title,
				'main' => (string) SmartMarkersTemplating::render_file_template(
					(string) $this->ControllerGetParam('module-view-path').'settings-2fa.mtpl.htm',
					[
						'2FA-CHK' 				=> (string) SmartHashCrypto::checksum((string)$fa2_code, (string)SmartAuth::get_auth_username()),
						'2FA-CODE' 				=> (string) $fa2_code,
						'2FA-URL' 				=> (string) $fa2_url,
						'2FA-BARCODE' 			=> (string) $fa2_qrcode,
						'TXT-CANCEL' 			=> (string) $this->translator->text('btn-cancel'),
						'TXT-2FA-ENABLE' 		=> (string) $this->translator->text('sett-auth-2fa-ttl-enable'),
						'TXT-IMPORTANT' 		=> (string) $this->translator->text('sett-auth-2fa-important'),
						'TXT-2FA-NOTICE' 		=> (string) $this->translator->text('sett-auth-2fa-notice-enable'),
						'TXT-2FA-REC-KEY' 		=> (string) $this->translator->text('sett-auth-2fa-recovery-enable'),
						'TXT-2FA-HINT' 			=> (string) $this->translator->text('sett-auth-2fa-hint-enable'),
						'TXT-2FA-BTN-ENABLE' 	=> (string) $this->translator->text('sett-auth-2fa-ttl-enable'),
						'TXT-2FA-VERIFY-LBL' 	=> (string) $this->translator->text('sett-auth-2fa-totp-code-enter'),
						'TXT-2FA-VERIFY-HNT' 	=> (string) $this->translator->text('sett-auth-2fa-totp-code-hint'),
						'TXT-2FA-QUESTION' 		=> (string) $this->translator->text('sett-auth-2fa-confirm-question-enable'),
						'TXT-2FA-CONFIRM' 		=> (string) $this->translator->text('sett-auth-2fa-confirm-enable'),
						'TXT-2FA-WARN' 			=> (string) $this->translator->text('sett-auth-2fa-confirm-warn-enable'),
						'TXT-2FA-APP1' 			=> (string) $this->translator->text('sett-auth-2fa-name-app1'),
						'URL-2FA-APP1' 			=> (string) $this->translator->text('sett-auth-2fa-url-app1'),
						'TXT-2FA-APP2' 			=> (string) $this->translator->text('sett-auth-2fa-name-app2'),
						'URL-2FA-APP2' 			=> (string) $this->translator->text('sett-auth-2fa-url-app2'),
					]
				)
			]);
			//--
			$this->PageViewSetCfg('template-file', 'template.htm');
			//--
			return;
			//--
		} //end if
		//--
		$tab = (string) $this->RequestVarGet('tab', '', 'string');
		//--
		$securityKey = (string) trim((string)SmartAuth::get_user_ssekey());
		$SecurityArrKeys = [];
		$isSecurityKeysOk = false;
		$hashSecurityKey = '';
		if((string)$securityKey != '') {
			if(\SmartModExtLib\AuthUsers\Utils::isSecurityKeyAvailable() === true) {
				$SecurityArrKeys = (array) \SmartModExtLib\AuthUsers\Utils::unpackSecurityKey((string)$securityKey);
				if((int)\Smart::array_size($SecurityArrKeys) == 2) {
					$isSecurityKeysOk = true;
				} //end if
				$hashSecurityKey = (string) SmartHashCrypto::crc32b((string)$securityKey, false);
			} //end if
		} //end if
		//--
		$infoCert = (string) trim((string)SmartAuth::get_user_infocert());
		$arrCertInfo = Smart::json_decode((string)$infoCert);
		if(!is_array($arrCertInfo)) {
			$arrCertInfo = [];
		} //end if
		$certIsExpired = (bool) \SmartModExtLib\AuthUsers\Utils::isSignKeysExpired((array)$arrCertInfo);
		$txtDigiCertValid 	= (string) $this->translator->text('digicert-valid');
		$txtDigiCertExpired = (string) $this->translator->text('digicert-expired');
		$certType = (string) \SmartModExtLib\AuthUsers\Utils::getSignKeysType((array)$arrCertInfo);
		$userCert 				= (string) SmartAuth::get_user_cert();
		$userPubKey 			= (string) SmartAuth::get_user_pubkey();
		$userPrivKey 			= (string) SmartAuth::get_user_privkey();
		$isUserEncryptedPrivKey = (bool)   \SmartModExtLib\AuthUsers\Utils::isSignPrivateKeyEncrypted((string)$userPrivKey);
		$hashSignKeys = '';
		if(
			((string)$infoCert != '')
			AND
			((string)$userCert != '')
			AND
			((string)$userPrivKey != '')
			AND
			((string)$userPubKey != '')
		) {
			$hashSignKeys = (string) $infoCert."\n\n".$userCert."\n\n".$userPrivKey."\n\n".$userPubKey;
			$hashSignKeys = (string) SmartHashCrypto::crc32b((string)$hashSignKeys, false);
		} //end if
		//--
		$title = (string) $this->translator->text('sett-welcome');
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'settings.mtpl.htm',
				[
					//--
					'URL-PREFIX-MASTER' 		=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixMaster(),
					'URL-PREFIX-LOCAL' 			=> (string) \SmartModExtLib\AuthUsers\AuthClusterUser::getAuthClusterUrlPrefixLocal(),
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					'TXT-SIGNED-TITLE' 			=> (string) $this->translator->text('signed-in'),
					'TXT-SIGNOUT' 				=> (string) $this->translator->text('btn-signout'),
					'TXT-BTN-ACCOUNT' 			=> (string) $this->translator->text('btn-account-display'),
					'TXT-BTN-SETTINGS' 			=> (string) $this->translator->text('btn-account-settings'),
					'CURRENT-ACTION' 			=> (string) $this->ControllerGetParam('action'),
					'TXT-APPS' 					=> (string) $this->translator->text('apps-and-dashboard'),
					//--
				]
			),
			'aside' => (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'settings-aside.mtpl.htm',
				[
					//--
					'REDIR-TAB' 				=> (string) $tab,
					//--
					'AUTH-USERNAME' 			=> (string) SmartAuth::get_auth_username(),
					'TXT-ACC-TITLE' 			=> (string) $title,
					'AUTH-ID' 					=> (string) SmartAuth::get_auth_id(),
					'TXT-USER-ID' 				=> (string) $this->translator->text('id-user'),
					'CLUSTER-ID' 				=> (string) SmartAuth::get_auth_cluster_id(),
					'TXT-CLUSTER-ID' 			=> (string) $this->translator->text('id-cluster'),
					//--
					'TXT-GDPR-COMPLIANT' 		=> (string) $this->translator->text('gdpr-compliant'),
					'TXT-CONFIRM-ACTION' 		=> (string) $this->translator->text('sett-confirm-action'),
					'TXT-NAV-ACCOUNT' 			=> (string) $this->translator->text('nav-account'),
					'TXT-NAV-SETTINGS' 			=> (string) $this->translator->text('nav-settings'),
					'TXT-FULL-NAME' 			=> (string) $this->translator->text('sett-full-name'),
					'AUTH-FULL-NAME' 			=> (string) ($record['name'] ?? null),
					'TXT-2FA' 					=> (string) $this->translator->text('sett-auth-2fa'),
					'TXT-2FA-ENABLED' 			=> (string) $this->translator->text('sett-auth-2fa-enabled'),
					'TXT-2FA-DISABLED' 			=> (string) $this->translator->text('sett-auth-2fa-disabled'),
					'TXT-2FA-BTN-ENABLE' 		=> (string) $this->translator->text('sett-auth-2fa-btn-enable'),
					'TXT-2FA-BTN-DISABLE' 		=> (string) $this->translator->text('sett-auth-2fa-btn-disable'),
					'TXT-2FA-TTL-DISABLE' 		=> (string) $this->translator->text('sett-auth-2fa-ttl-disable'),
					'TXT-2FA-DISABLE-NOTICE' 	=> (string) $this->translator->text('sett-auth-2fa-notice-disable'),
					'TXT-2FA-DISABLE-HINT' 		=> (string) $this->translator->text('sett-auth-2fa-hint-disable'),
					'TXT-HINT-2FA' 				=> (string) $this->translator->text('sett-auth-2fa-notice-info'),
					'AUTH-2FA' 					=> (string) (($record['fa2'] ?? null) ? 'yes' : 'no'),
					'TXT-BTN-AUTH-SSO-UPDATE' 	=> (string) $this->translator->text('sett-auth-sso-btn-update'),
					'TXT-AUTH-SSO-PLUGINS' 		=> (string) $this->translator->text('sett-auth-sso-plugins'),
					'TXT-AUTH-SSO-NOTICE' 		=> (string) $this->translator->text('sett-auth-sso-notice'),
					'AUTH-ARR-PLUGINS' 			=> (array)  \SmartModExtLib\AuthUsers\AuthPlugins::getPluginsForAccountSecurity((string)($record['allowfed'] ?? null)),
					'TXT-DATE-REGISTER' 		=> (string) $this->translator->text('registration-date'),
					'AUTH-DATE-REGISTER' 		=> (string) ($record['registered'] ?? null),
					'TXT-TAB-CINFO' 			=> (string) $this->translator->text('sett-tab-cinfo'),
					'TXT-TAB-PASSWORD' 			=> (string) $this->translator->text('sett-tab-pass'),
					'TXT-TAB-SECURITY' 			=> (string) $this->translator->text('sett-tab-security'),
					'TXT-TAB-ACCOUNT' 			=> (string) $this->translator->text('sett-tab-account'),
					'TXT-TAB-CERTIFICATES' 		=> (string) $this->translator->text('sett-tab-certificates'),
					'TXT-SSEKEY-TITLE' 			=> (string) $this->translator->text('ssekey-title'),
					'TXT-AVAILABLE-SSEKEY' 		=> (string) $this->translator->text('ssekey-ok'),
					'TXT-BTN-SSEKEY' 			=> (string) $this->translator->text('sett-btn-ssekey'),
					'DAT-SSEKEY-HASH' 			=> (string) $hashSecurityKey,
					'DAT-SSEKEY-LEN' 			=> (int)    (strlen((string)$securityKey) * 8), // bit
					'DAT-SSEKEY-OK' 			=> (int)    $isSecurityKeysOk,
					'DAT-SSEKEY-TYP' 			=> (string) ((\SmartModExtLib\AuthUsers\Utils::isSecurityKeyAvailable() === true) ? \SmartModExtLib\AuthUsers\Utils::getSecurityKeyType() : ''),
					'DAT-SSEKEY-MOD' 			=> (string) ((\SmartModExtLib\AuthUsers\Utils::isSecurityKeyAvailable() === true) ? \SmartModExtLib\AuthUsers\Utils::getSecurityKeyMode().' ' : ''),
					'TXT-DIGICERT-TITLE' 		=> (string) $this->translator->text('digicert-title'),
					'TXT-DIGICERT-NEW' 			=> (string) $this->translator->text('digicert-empty'),
					'TXT-DIGICERT-CERTIFICATE' 	=> (string) $this->translator->text('digicert-certificate'),
					'TXT-DIGICERT-PRIVKEY' 		=> (string) $this->translator->text('digicert-privkey'),
					'TXT-DIGICERT-PUBKEY' 		=> (string) $this->translator->text('digicert-pubkey'),
					'DAT-DIGICERT-HASH' 		=> (string) $hashSignKeys,
					'DAT-DIGICERT-TYPE' 		=> (string) $certType,
					'DAT-DIGICERT-INFOCERT' 	=> (string) (((string)$infoCert != '') ? SmartUtils::pretty_print_var($arrCertInfo) : ''),
					'DAT-DIGICERT-CERTIFICATE' 	=> (string) $userCert,
					'DAT-DIGICERT-PRIVKEY-LEN' 	=> (int)    strlen((string)$userPrivKey),
					'DAT-DIGICERT-PRIVKEY-TYP' 	=> (string) (($isUserEncryptedPrivKey === true) ? 'Protected / Encrypted' : 'Protected'),
					'DAT-DIGICERT-PRIVKEY-ENC' 	=> (string) (($isUserEncryptedPrivKey === true) ? 'yes' : 'no'),
					'DAT-DIGICERT-PUBKEY' 		=> (string) $userPubKey,
					'DAT-DIGICERT-EXPIRED' 		=> (string) ($certIsExpired === true ? 'yes' : 'no'),
					'TXT-DIGICERT-EXPIRED' 		=> (string) ($certIsExpired === true ? (string)$txtDigiCertExpired : (string)$txtDigiCertValid),
					'TXT-AVAILABLE-DIGICERT' 	=> (string) $this->translator->text('digicert-ok'),
					'NOT-AVAILABLE-DIGICERT' 	=> (string) ((\SmartModExtLib\AuthUsers\Utils::isSignKeysAvailable() !== true) ? $this->translator->text('api-sett-certs-na') : ''),
					'BTN-DIGICERT-SHOW' 		=> (int)    (((string)$infoCert == '') ? 1 : 0),
					'SHOW-DIGICERT' 			=> (string) ((\SmartModExtLib\AuthUsers\Utils::isSignKeysAvailable() !== true) ? 'no' : 'yes'),
					'TXT-BTN-CERT-ECDSA' 		=> (string) $this->translator->text('sett-btn-gen-certs'),
					'TXT-BTN-UPD-CINFO' 		=> (string) $this->translator->text('sett-btn-upd-cinfo'),
					'TXT-NEW-PASS' 				=> (string) $this->translator->text('sett-newpass-pass'),
					'TXT-NEW-REPASS' 			=> (string) $this->translator->text('sett-newpass-repass'),
					'TXT-ALGO-PASS' 			=> (string) $this->translator->text('sett-newpass-algo'),
					'TXT-BTN-UPD-PASS' 			=> (string) $this->translator->text('sett-btn-upd-pass'),
					'TXT-AUTH-MULTISESSIONS' 	=> (string) $this->translator->text('api-sett-msess'),
					'TXT-HINT-MULTISESSIONS' 	=> (string) $this->translator->text('api-sett-msess-hint'),
					'AUTH-MULTISESSIONS-ON' 	=> (string) $this->translator->text('api-sett-msess-enabled'),
					'AUTH-MULTISESSIONS-OFF' 	=> (string) $this->translator->text('api-sett-msess-disabled'),
					'AUTH-ALLOW-MULTISESSIONS' 	=> (string) ((intval($record['status'] ?? null) === 2) ? 'no' : 'yes'),
					'TXT-BTN-ACC-MSESS-ENABLE' 	=> (string) $this->translator->text('api-sett-msess-btn-enable'),
					'TXT-BTN-ACC-MSESS-DISABLE' => (string) $this->translator->text('api-sett-msess-btn-disable'),
					'TXT-BTN-ACC-DEACTIVATE' 	=> (string) $this->translator->text('sett-btn-account-deactivate'),
					'TXT-ACC-DEACTIVATE-NOTE' 	=> (string) $this->translator->text('sett-account-deactivate-notice'),
					'TXT-ACC-DEACTIVATE-CHECK' 	=> (string) $this->translator->text('sett-account-deactivate-id-chk'),
				]
			),
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
