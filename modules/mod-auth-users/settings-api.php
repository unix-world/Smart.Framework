<?php
// Controller: AuthUsers/SettingsApi
// Route: ?page=auth-users.settings-api.json
// (c) 2025-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppIndexController extends SmartAbstractAppController {

	// r.20260115

	// it runs just on auth master server
	// this is the auth users public authorize (api) used for: signin, register, recovery

	private ?object $translator = null;

	public function Initialize() {

		// this controller can operate ONLY on master server

		//--
		if(!defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
			$this->PageViewSetErrorStatus(503, 'Mod Auth Users is NOT Enabled ...');
			return false;
		} //end if
		//--

		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--

		//--
		if(SmartEnvironment::isAdminArea() !== false) {
			Smart::log_warning(__METHOD__.' # ERR: Controller cannot run under Admin area');
			$this->PageViewSetErrorStatus(502, 'ERROR: This Controller must run inside Index Area');
			return false;
		} //end if
		//--

		//--
		if(SmartAuth::is_cluster_master_auth() !== true) {
			$this->PageViewSetErrorStatus(502, 'Not an Auth Cluster Master Server');
			return false;
		} //end if
		//--

		//--
		if(SmartUtils::is_ajax_request() !== true) {
			$this->PageViewSetErrorStatus(400, 'Invalid Api Request');
			return false;
		} //end if
		//--

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetErrorStatus(403, 'Authentication is Required');
			return false;
		} //end if
		//--

		//--
		if($this->translator === null) {
			$this->translator = SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		} //end if
		//--

	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML/JSON)

		//--
		$action = (string) $this->RequestVarGet('action', '', 'string');
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'application/json');
		//--

		//--
		$status   = 'INVALID';
		$title    = (string) $this->translator->text('api-invalid-request');
		$message  = (string) $this->translator->text('api-invalid-action');
		$redirect = '';
		$jsevcode = '';
		//--

		//--
		if(\SmartModExtLib\AuthUsers\Utils::isValidCsrfCookie() !== true) {
			//--
			$status   = 'FAILED';
			$title    = (string) $this->translator->text('api-session-expired');
			$message  = (string) $this->translator->text('api-session-error-persist');
			//--
			if((string)\SmartModExtLib\AuthUsers\Utils::setCsrfCookie() != '') {
				$message = (string) $this->translator->text('api-session-new');
			} //end if
			//--
		} else {
			//--
			$status = 'ERROR';
			$title = (string) $this->translator->text('api-sett-update-failed');
			$redirect = '';
			//--
			switch((string)$action) {
				//-------
				case 'ssekey:generate':
					//--
					$message = '';
					//--
					$theUserID = (string) SmartAuth::get_auth_id();
					if((string)$message == '') {
						if((string)trim((string)$theUserID) == '') {
							$message = (string) $this->translator->text('api-sett-empty-id');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\Utils::isSecurityKeyAvailable() !== true) {
							$message = (string) $this->translator->text('api-sett-ssekey-na');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$result = -100;
						//--
						$securityKey = (string) \trim((string)\SmartModExtLib\AuthUsers\Utils::generateSecurityKey((string)$theUserID));
						//--
						if((string)$securityKey != '') {
							$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccountSecurityKey(
								(string) $theUserID,
								(string) $securityKey
							);
						} //end if
						//--
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-ssekey-generate');
						$redirect = '?page=auth-users.settings&tab=certs';
						//--
					} //end if
					//--
					break;
				case 'certs:generate':
					//--
					$message = '';
					//--
					$theUserID = (string) SmartAuth::get_auth_id();
					if((string)$message == '') {
						if((string)trim((string)$theUserID) == '') {
							$message = (string) $this->translator->text('api-sett-empty-id');
						} //end if
					} //end if
					//--
					$theFullName  = (string) trim((string)SmartAuth::get_user_fullname());
					if((string)$message == '') {
						if((string)$theFullName == '') {
							$message = (string) $this->translator->text('api-sett-empty-name');
						} //end if
					} //end if
					//--
					$theEmailAddress = (string) SmartAuth::get_user_email();
					if((string)$message == '') {
						if((string)$theEmailAddress == '') {
							$message = (string) $this->translator->text('api-sett-empty-email');
						} elseif(\SmartAuth::validate_auth_ext_username((string)$theEmailAddress) !== true) {
							$message = (string) $this->translator->text('api-sett-invalid-email');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						if(\SmartModExtLib\AuthUsers\Utils::isSignKeysAvailable() !== true) {
							$message = (string) $this->translator->text('api-sett-certs-na');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$result = -100;
						//--
						$arrDigiCert = (array) \SmartModExtLib\AuthUsers\Utils::generateSignKeys(
							(string) $theUserID,
							(string) $theEmailAddress,
							(string) $theFullName,
							100 // 100 years
						);
						//--
						if((int)Smart::array_size($arrDigiCert) > 0) {
							$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccountSignKeys(
								(string) $theUserID,
								(array)  $arrDigiCert
							);
						} //end if
						//--
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-certs-generate');
						$redirect = '?page=auth-users.settings&tab=certs';
						//--
					} //end if
					//--
					break;
				case 'update:contact-info':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['name'] 	= (string) trim((string)($frm['name'] ?? null));
					//--
					$message = '';
					if((string)trim((string)$frm['name']) == '') {
						$message = (string) $this->translator->text('api-sett-empty-name');
					} //end if else
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccountContactInfo(
							(string) SmartAuth::get_auth_id(),
							(string) $frm['name'],
							[] // to be done: country, address, ...
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-contact-info');
						$redirect = '?page=auth-users.settings&tab=contact';
						//--
					} //end if
					//--
					break;
				//-------
				case 'update:change-password':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
					$frm['rpass'] 	= (string) trim((string)($frm['rpass'] ?? null));
					$frm['algo'] 	= (int)    intval((string)trim((string)($frm['algo'] ?? null)));
					//--
					$message = '';
					if((string)trim((string)$frm['pass']) == '') {
						$message = (string) $this->translator->text('api-auth-pass-empty');
					} else if((string)trim((string)$frm['rpass']) == '') {
						$message = (string) $this->translator->text('api-auth-repass-empty');
					} else if((string)$frm['pass'] !== (string)$frm['rpass']) {
						$message = (string) $this->translator->text('api-auth-repass-invalid');
					} else if(SmartAuth::validate_auth_password((string)$frm['pass']) !== true) {
						$message = (string) $this->translator->text('api-auth-pass-invalid');
					} else if(((int)$frm['algo'] !== 123) AND ((int)$frm['algo'] !== 77)) {
						$message = (string) $this->translator->text('api-sett-invalid-pass-algo');
					} //end if else
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccountPassword(
							(string) SmartAuth::get_auth_id(),
							(string) $frm['pass'],
							(int)    $frm['algo']
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-pass');
						//--
					} //end if
					//--
					break;
				//-------
				case 'update:sso-plugins':
					//--
					$frm 				= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['allowfed'] 	= ($frm['allowfed'] ?? null);
					if(!is_array($frm['allowfed'])) { // if no checkbox selected will send an empty value, non-array
						$frm['allowfed'] = [];
					} //end if
					//--
					$message = '';
					if((int)Smart::array_size($frm['allowfed']) > 0) {
						if((int)Smart::array_type_test($frm['allowfed']) != 1) { // non-associative
							$message = (string) $this->translator->text('api-sett-invalid-sso-list');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$allowfed = [];
						//--
						if((int)Smart::array_size($frm['allowfed']) > 0) {
							for($i=0; $i<count($frm['allowfed']); $i++) {
								$allowfed[] = '<'.$frm['allowfed'][$i].'>';
							} //end for
							$allowfed = (string) implode(',', (array)$allowfed);
							$safeAllowfed = (array) \SmartModExtLib\AuthUsers\AuthPlugins::getPluginsForAccountSecurity((string)$allowfed); // pass through filter of existing plugins, safety
							$allowfed = [];
							if((int)Smart::array_size($safeAllowfed) > 0) {
								for($i=0; $i<count($safeAllowfed); $i++) {
									if((string)($safeAllowfed[$i]['state'] ?? null) == 'active') {
										$safeAllowfed[$i]['id'] = (string) trim((string)($safeAllowfed[$i]['id'] ?? null));
										if((string)$safeAllowfed[$i]['id'] != '') {
											$allowfed[] = (string) '<'.$safeAllowfed[$i]['id'].'>';
										} //end if
									} //end if
								} //end for
							} //end if
							$safeAllowfed = null;
						} //end if
						//--
						$allowfed = (string) implode(',', (array)$allowfed);
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccountSSOPlugins(
							(string) SmartAuth::get_auth_id(),
							(string) $allowfed
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-sso-list');
						$redirect = '?page=auth-users.settings&tab=security';
						//--
					} //end if
					//--
					break;
				//-------
				case '2fa:enable':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['chk'] 	= (string) trim((string)($frm['chk'] ?? null));
					$frm['key'] 	= (string) trim((string)($frm['key'] ?? null));
					$frm['pin'] 	= (string) trim((string)($frm['pin'] ?? null));
					//--
					$message = '';
					if((int)strlen((string)$frm['key']) != 52) {
						$message = (string) $this->translator->text('api-sett-2fa-empty-key');
					} elseif(\SmartModExtLib\AuthUsers\Utils::validateAuth2FACodeFormat((string)$frm['pin']) !== true) { // 2FA code have an invalid format
						$message = (string) $this->translator->text('api-sett-2fa-empty-pin'); // {{{SYNC-TOTP-PIN-LENGTH-CHECK}}}
					} elseif(SmartHashCrypto::checksum((string)$frm['key'], (string)SmartAuth::get_auth_username()) != (string)$frm['chk']) {
						$message = (string) $this->translator->text('api-sett-2fa-invalid-key');
					} elseif(\SmartModExtLib\AuthUsers\Utils::verify2FACode((string)SmartAuth::get_auth_id(), (string)$frm['pin'], (string)$frm['key'], false) !== true) { // ok ; not encrypted
						$message = (string) $this->translator->text('api-sett-2fa-invalid-pin');
					} //end if else
					//--
					if((string)$message == '') {
						$frm['key'] = (string) trim((string)\SmartModExtLib\AuthUsers\Utils::encrypt2FASecret((string)SmartAuth::get_auth_id(), (string)$frm['pin'], (string)$frm['key'])); // ok
						if((string)$frm['key'] == '') {
							$message = (string) $this->translator->text('api-sett-2fa-encrypt-failed');
						} //end if
					} //end if
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccount2FASecret(
							(string) SmartAuth::get_auth_id(),
							(string) $frm['key']
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-2fa-enabled');
						//--
					} //end if
					//--
					break;
				//-------
				case '2fa:disable':
					//--
					$message = '';
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::updateAccount2FASecret(
							(string) SmartAuth::get_auth_id(),
							(string) ''
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-2fa-disabled');
						$redirect = '?page=auth-users.settings&tab=security';
						//--
					} //end if
					//--
					break;
				//-------
				case 'handle:multisessions':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', [], 'array');
					$frm['mode'] 	= (string) intval((string)trim((string)($frm['mode'] ?? null)));
					//--
					$message = '';
					if(((int)$frm['mode'] < 1) || ((int)$frm['mode'] > 2)) { // {{{SYNC-ACCOUNT-MULTISESSIONS}}}
						$message = (string) $this->translator->text('api-sett-msess-invalid');
					} //end if
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::handleAccountMultiSessions(
							(string) SmartAuth::get_auth_id(),
							(int)    $frm['mode']
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-msess-updated');
						$redirect = '?page=auth-users.settings&tab=security';
						//--
					} //end if
					//--
					break;
				//-------
				case 'disable:account':
					//--
					$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
					$frm['uid'] 	= (string) trim((string)($frm['uid'] ?? null));
					//--
					$message = '';
					if((string)trim((string)$frm['uid']) == '') {
						$message = (string) $this->translator->text('api-sett-acc-disable-uid-empty');
					} else if((int)strlen((string)$frm['uid']) != 21) {
						$message = (string) $this->translator->text('api-sett-acc-disable-uid-invalid');
					} else if((string)$frm['uid'] != (string)SmartAuth::get_auth_id()) {
						$message = (string) $this->translator->text('api-sett-acc-disable-uid-wrong');
					} //end if else
					//--
					if((string)$message == '') {
						//--
						$result = (int) \SmartModDataModel\AuthUsers\AuthUsersFrontend::deactivateAccount(
							(string) SmartAuth::get_auth_id()
						);
						if((int)$result != 1) {
							$message = (string) $this->translator->text('api-sett-op-failed').' ('.(int)$result.')';
						} elseif(\SmartModExtLib\AuthUsers\AuthClusterUser::refreshAccountWorkspace((string)SmartAuth::get_auth_id()) !== true) { // {{{SYNC-UPDATE-AUTH-USER-SETTINGS-CLUSTER}}}
							$message = (string) $this->translator->text('api-sett-op-failed').' [C]';
						} //end if else
						//--
					} //end if
					//--
					if((string)$message == '') {
						//--
						$status = 'OK';
						$title = (string) $this->translator->text('api-sett-op-success');
						$message = (string) $this->translator->text('api-sett-ok-acc-disable');
						$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNOUT;
						//--
					} //end if
					//--
					break;
				//------- DEFAULT
				default:
					//--
					// other invalid actions
					//--
			} // end switch
			//--
		} //end if else
		//--

		//--
		$this->PageViewSetVar(
			'main',
			(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
				(string) $status,
				(string) $title,
				(string) Smart::nl_2_br((string)Smart::escape_html((string)$message)),
				(string) $redirect,
				'',
				'',
				(string) $jsevcode
			)
		);
		//--
		return 200;
		//--

	} //END FUNCTION


} //END CLASS


//end of php code
