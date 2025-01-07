<?php
// Controller: AuthAdmins/Manager
// Route: admin.php?page=auth-admins.manager.stml
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN');
define('SMART_APP_MODULE_AUTH', true);

// [PHP8]

/**
 * Admin Controller
 * @ignore
 */
final class SmartAppAdminController extends SmartAbstractAppController {

	// v.20250103

	// TODO:
	// 	* Edit: support to bind to a specific IP address list, for extra security
	//	* Recovery for 2FA Key
	//	* https://github.com/freeotp/freeotp.github.io # javascript
	//	* https://github.com/freeotp/freeotp.github.io/blob/master/lib/qrcode.js


	public function Initialize() {
		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML/JSON)

		//-- {{{SYNC-CHECK-AUTH-ADMINS-MODEL}}}
		if((!class_exists('SmartModelAuthAdmins')) || (!is_subclass_of('SmartModelAuthAdmins', '\\SmartModDataModel\\AuthAdmins\\AbstractAuthAdmins'))) {
			$this->PageViewSetCfg('error', 'Authentication Model Not Available or Invalid');
			return 500;
		} //end if
		//--

		//-- {{{SYNC-AUTH-ADMINS-PRE-CHECKS}}}
		if(SmartAuth::check_login() !== true) {
			$this->PageViewSetCfg('error', 'Auth Admins Manager Requires Authentication ! ...');
			return 403;
		} //end if
		//--
		if(!SmartEnvironment::isAdminArea()) { // allow: adm/tsk ; but this controller does not extends in a task controller
			$this->PageViewSetCfg('error', 'Auth Admins Manager is allowed to run under `Admin` area only ! ...');
			return 403;
		} //end if
		//--
		if(SmartAuth::get_auth_realm() !== 'SMART-ADMINS-AREA') {
			$this->PageViewSetCfg('error', 'This Area Requires the `SMART-ADMINS-AREA` Auth Realm !'."\n".'The current Auth Realm is: `'.SmartAuth::get_auth_realm().'` ...');
			return 403;
		} //end if
		//--
		if(!defined('APP_AUTH_PRIVILEGES')) {
			$this->PageViewSetCfg('error', 'The following constant is missing from configs: APP_AUTH_PRIVILEGES');
			return 500;
		} //end if
		//-- #end sync

		//--
		if(SmartAuth::test_login_restriction('account') === true) { // {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
			$this->PageViewSetCfg('error', 'This Area is Restricted by your current Login Restrictions !');
			return 403;
		} //end if
		//--

		//-- use defaults
		$this->PageViewSetCfg('template-file', 'template.htm');
		//--

		//--
		$action = $this->RequestVarGet('action', '', 'string');
		//--

		switch((string)$action) {

			//------- utils

			case 'close-modal': // Closes the Modal and Refresh the Parent (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$this->PageViewSetVars([
					'title' => 'Wait ...',
					'main' => '<br><center><div><img src="lib/framework/img/loading-bars.svg" width="64" height="64"></div></center>'.
					'<script>smartJ$Browser.RefreshParent();</script>'.
					'<script>smartJ$Browser.CloseDelayedModalPopUp();</script>'
				]);
				//--
				break;

			//------- stk tokens

			case 'tokens-create': // Tokens Create (New)
				//--
				if(SmartEnvironment::isATKEnabled() !== true) {
					$this->PageViewSetCfg('error', 'SmartAuth Tokens are DISABLED');
					return 503;
				} //end if
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) SmartAuth::get_auth_id(); // hardcoded
				$frm['name']	= (string) trim((string)($frm['name'] ?? null));
				$frm['priv'] 	= (string) trim((string)($frm['priv'] ?? null));
				$frm['exp'] 	= (string) trim((string)($frm['exp'] ?? null));
				//--
				$message = '';
				if(
					((string)$frm['id'] != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					$message = 'You are not authorized to create this new token';
				} elseif((string)trim((string)$frm['name']) == '') {
					$message = 'Name is Empty';
				} elseif((int)strlen((string)$frm['name']) < 5) {
					$message = 'Name is too short (min: 5)';
				} elseif((int)strlen((string)$frm['name']) > 25) {
					$message = 'Name is too long (max: 25)';
				} elseif((string)trim((string)$frm['priv']) == '') {
					$message = 'Privileges list is Empty';
				} elseif((int)strlen((string)$frm['priv']) > 255) {
					$message = 'Privileges list is too long (max: 255)';
				} elseif(
					((string)$frm['priv'] != '*')
					AND // {{{SYNC-STK-TOKEN-PRIVILEGES}}} ; {{{SYNC-STK-TOKEN-COMPOSE-PRIVILEGES}}}
					((int)Smart::array_size((array)SmartAuth::safe_arr_privileges_or_restrictions((array)explode(',', (string)str_replace([' ', "\t", "\r", "\n"], '', (string)strtolower((string)$frm['priv']))))) <= 0)
				) { // sync below message with related TPL html message: tokens manage
					$message = 'Privileges list is Invalid ; Must be either `*` or `priv-a, privb, ...` ; max 255 chars ; min 1 char ; valid privilege key length: min 3 / max 28 chars ; accepted chars: `a-z` and `-` ; privileges have to be separed by a comma `,`';
				} elseif(
					((string)$frm['exp'] != '')
					AND
					(!\preg_match((string)\SmartValidator::regex_stringvalidation_expression('date'), (string)$frm['exp']))
				) {
					$message = 'Expiration Date is Invalid ; Must be Empty or use YYYY-MM-DD format';
				} //end if else
				//--
				$expires = 0; // no expiration
				if((string)$frm['exp'] != '') { // if not empty, calculate time in seconds as required
					$expires = (int) strtotime((string)$frm['exp']); // expected format YYYY-MM-DD already validated above
					if((int)$expires < (int)time()) {
						if((string)$message == '') {
							$message = 'Expiration Date is invalid or in the past';
						} //end if
					} //end if
				} //end if
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->insertToken([
						'id' 			=> (string) SmartAuth::get_auth_id(),
						'active' 		=> (int)    1,
						'expires' 		=> (int)    $expires,
						'token_priv' 	=> (string) $frm['priv'],
						'token_name' 	=> (string) $frm['name'],
					]);
					$model = null; // close connection
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Token has been Created for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>].';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to Created Token for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>].'.' / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				$redirect = '';
				//--
				$timeout = 3500;
				if((string)$status == 'OK') {
					$timeout = 1000;
				} //end if
				$jsevcode = 'setTimeout(() => { self.location = self.location; }, '.(int)$timeout.');';
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'NEW Token',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			case 'tokens-delete': // Tokens Delete
				//--
				if(SmartEnvironment::isATKEnabled() !== true) {
					$this->PageViewSetCfg('error', 'SmartAuth Tokens are DISABLED');
					return 503;
				} //end if
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				$frm['hs'] 		= (string) trim((string)($frm['hs'] ?? null));
				//--
				$message = '';
				if(
					((string)$frm['id'] != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					$message = 'You are not authorized to delete this token';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif((string)trim((string)$frm['hs']) == '') {
					$message = 'Invalid Token Hash: Empty';
				} elseif((int)strlen((string)$frm['hs']) != 128) {
					$message = 'Invalid Token Hash: Length';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->deleteTokenByIdAndHash(
						(string) $frm['id'],
						(string) $frm['hs']
					);
					$model = null; // close connection
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Token has been Deleted for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>]. UUID:&nbsp;'.Smart::escape_html((string)$frm['hs']);
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to Delete Token for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>].'.' UUID:&nbsp;'.Smart::escape_html((string)$frm['hs']).' / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				$redirect = '';
				//--
				$timeout = 3500;
				if((string)$status == 'OK') {
					$timeout = 1000;
				} //end if
				$jsevcode = 'setTimeout(() => { self.location = self.location; }, '.(int)$timeout.');';
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Token DELETE',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			case 'tokens-status-change': // Tokens Activate/Deactivate
				//--
				if(SmartEnvironment::isATKEnabled() !== true) {
					$this->PageViewSetCfg('error', 'SmartAuth Tokens are DISABLED');
					return 503;
				} //end if
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				$frm['hs'] 		= (string) trim((string)($frm['hs'] ?? null));
				$frm['st'] 		= (string) trim((string)($frm['st'] ?? null));
				//--
				$message = '';
				if(
					((string)$frm['id'] != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					$message = 'You are not authorized to change status for this token';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif((string)trim((string)$frm['hs']) == '') {
					$message = 'Invalid Token Hash: Empty';
				} elseif((int)strlen((string)$frm['hs']) != 128) {
					$message = 'Invalid Token Hash: Length';
				} elseif(((string)$frm['st'] != '0') AND ((string)$frm['st'] != '1')) {
					$message = 'Invalid Token Status: must be 0 or 1';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->updateTokenStatus(
						(string) $frm['id'],
						(string) $frm['hs'],
						(int)    (!((int)$frm['st'])),
					);
					$model = null; // close connection
				} //end if
				//--
				$text_status = 'DEACTIVATED';
				if((int)$frm['st'] == 1) {
					$text_status = 'ACTIVATED';
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Token has been '.$text_status.' for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>]. UUID:&nbsp;'.Smart::escape_html((string)$frm['hs']);
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to Change a Token Status to '.$text_status.' for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>].'.' UUID:&nbsp;'.Smart::escape_html((string)$frm['hs']).' / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				$redirect = '';
				//--
				$timeout = 3500;
				if((string)$status == 'OK') {
					$timeout = 1000;
				} //end if
				$jsevcode = 'setTimeout(() => { self.location = self.location; }, '.(int)$timeout.');';
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Token Status Update',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			case 'tokens-list': // Tokens Manage
				//--
				if(SmartEnvironment::isATKEnabled() !== true) {
					$this->PageViewSetCfg('error', 'SmartAuth Tokens are DISABLED');
					return 503;
				} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts - Manage Account Tokens';
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				if((string)trim((string)$id) == '') {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_warn('Empty Account selected for Tokens Management')
					]);
					return;
					//--
				} //end if
				//--
				$have_access = (bool) ((string)$id == (string)SmartAuth::get_auth_id());
				$theData = [];
				$maxTokens = 0;
				//--
				if($have_access === true) {
					//--
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					//--
					$maxTokens = (int) $model::MAX_TOKENS_PER_ACCOUNT;
					$tokens = (array) $model->getTokensListById((string)$id);
					for($i=0; $i<Smart::array_size($tokens); $i++) {
						$testgk = (array) $model->getTokenByIdAndHash(
							(string) SmartAuth::get_auth_id(),
							(string) ($tokens[$i]['token_hash'] ?? null)
						);
						if(
							((string)($testgk['id'] ?? null) === (string)SmartAuth::get_auth_id())
							AND
							((string)($testgk['id'] ?? null) === (string)($tokens[$i]['id'] ?? null))
						) {
							$arrValid = (array) \SmartModExtLib\AuthAdmins\AuthTokens::validateSTKEncData(
								(string) ($tokens[$i]['id'] ?? null),
								(string) ($tokens[$i]['token_hash'] ?? null),
								(int)    ($tokens[$i]['expires'] ?? null),
								(string) ($tokens[$i]['token_data'] ?? null)
							);
							$theData[] = [
								'#' 			=> (string) '',
								'id' 			=> (string) ($tokens[$i]['id'] ?? null),
								'active' 		=> (int) 	($tokens[$i]['active'] ?? null),
								'expires' 		=> (string) (($tokens[$i]['expires'] ?? null) ? date('Y-m-d H:i:s O', (int)($tokens[$i]['expires'] ?? null)) : ''),
								'token_hash' 	=> (string) ($tokens[$i]['token_hash'] ?? null),
								'token_name' 	=> (string) ($tokens[$i]['token_name'] ?? null),
								'created' 		=> (string) date('Y-m-d H:i:s O', (int)($tokens[$i]['created'] ?? null)),
								'c-time' 		=> (string) ($tokens[$i]['created'] ?? null),
								'is-invalid' 	=> (int)    ($arrValid['ernum'] ?? null),
								'err-invalid' 	=> (string) ($arrValid['error'] ?? null),
								'exp-time' 		=> (string) ($arrValid['expires'] ?? null),
								'restr-priv' 	=> (string) str_replace(' ', '', (string)Smart::array_to_list((array)($arrValid['restr-priv'] ?? null))),
								'token-key' 	=> (string) Smart::base_from_hex_convert((string)bin2hex((string)($arrValid['key'] ?? null)), 92),
								'token-seed' 	=> (string) Smart::base_from_hex_convert((string)bin2hex((string)($arrValid['seed'] ?? null)), 85),
							];
						} else {
							Smart::log_warning(__METHOD__.' # Failed to get token by: ID=`'.SmartAuth::get_auth_id().'` ; Hash=`'.$tokens[$i]['token_hash'].'`');
						} //end if
					} //end for
					//--
					$model = null;
					//--
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => 'Tokens Management for account: '.$id,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-manage-tokens.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'HAVE-ACCESS' 		=> (string) (($have_access === true) ? 'yes' : 'no'),
							'CRR-ID' 			=> (string) SmartAuth::get_auth_id(),
							'ID' 				=> (string) $id,
							'THE-DATA' 			=> (string) Smart::json_encode((array)$theData),
							'MAX-TOKENS' 		=> (int)    $maxTokens,
						]
					)
				]);
				//--
				break;

			//------- password change

			case 'change-pass-form': // Change Pass Form
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts - Change Account Password';
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				if((string)trim((string)$id) == '') {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_warn('Empty Account selected for Change Password')
					]);
					return;
					//--
				} //end if
				//--
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
					AND // and
					((string)$id != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					//--
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' 	=> (string) SmartComponents::operation_error('You are not authorized to use this area !')
					]);
					return;
					//--
				} //end if
				//--
				$model = null;
				try {
					$model = new SmartModelAuthAdmins(); // open connection
				} catch(Exception $e) {
					$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
					return 500;
				} //end try catch
				$select_user = (array) $model->getById((string)$id);
				$model = null; // close connection
				//--
				if(Smart::array_size($select_user) <= 0) { // Check if exist id in admins table
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('Invalid Account Selected for Change Password ...')
					]);
					return;
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-change-pass.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'CRR-ID' 			=> (string) SmartAuth::get_auth_id(),
							'ID' 				=> (string) ($select_user['id'] ?? null),
							'LEN-KEYS' 			=> (int)    strlen((string)($select_user['keys'] ?? null)),
						]
					)
				]);
				//--
				break;

			case 'change-pass-update':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
				$frm['repass'] 	= (string) trim((string)($frm['repass'] ?? null));
				//--
				$message = '';
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
					AND // and
					((string)$frm['id'] != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif(SmartAuth::validate_auth_password((string)$frm['pass'], (bool)((defined('APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
					$message = 'Invalid Password: Too short or too long or does not match the minimal complexity level ...';
				} elseif(((string)$frm['repass'] !== (string)$frm['pass']) OR ((string)SmartHashCrypto::sha256((string)$frm['repass']) !== (string)SmartHashCrypto::sha256((string)$frm['pass']))) {
					$message = 'Invalid Password: Password and Retype of Password does not match';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->updatePassword(
						(string) $frm['id'],
						(string) $frm['pass']
					);
					$model = null; // close connection
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] password updated !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to update password for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				if((string)$status == 'OK') {
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$jsevcode = '';
				if((string)$frm['id'] == (string)SmartAuth::get_auth_id()) { // if change password for current account must handle different
					$redirect = '';
					$jsevcode = 'setTimeout(() => { smartJ$Browser.RefreshParent(); smartJ$Browser.CloseDelayedModalPopUp(); }, 3750);';
				} //end if
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Account Password Update',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			//------- 2FA enable

			case 'enable-2fa-form': // Enable 2FA Form
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts - Enable 2FA for Account';
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				if((string)trim((string)$id) == '') {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_warn('Empty Account selected for 2FA Enable')
					]);
					return;
					//--
				} //end if
				//--
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
					AND // and
					((string)$id != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					//--
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' 	=> (string) SmartComponents::operation_error('You are not authorized to use this area !')
					]);
					return;
					//--
				} //end if
				//--
				$model = null;
				try {
					$model = new SmartModelAuthAdmins(); // open connection
				} catch(Exception $e) {
					$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
					return 500;
				} //end try catch
				$select_user = (array) $model->getById((string)$id);
				$fa2_code   = (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateSecret();
				$fa2_url    = (string) $model->get2FAUrl((string)$fa2_code, (string)($select_user['id'] ?? null));
				$fa2_qrcode = (string) $model->get2FASvgBarCode((string)$fa2_code, (string)($select_user['id'] ?? null));
				$model = null; // close connection
				//--
				if(Smart::array_size($select_user) <= 0) { // Check if exist id in admins table
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('Invalid Account Selected for 2FA Enable ...')
					]);
					return;
				} //end if
				if((string)($select_user['fa2'] ?? null) != '') {
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('The Selected Account Selected already have Enabled 2FA ...')
					]);
					return;
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-enable-2fa.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'ID' 				=> (string) ($select_user['id'] ?? null),
							'2FA-CHK' 			=> (string) SmartHashCrypto::checksum((string)$fa2_code, (string)($select_user['id'] ?? null)),
							'2FA-CODE' 			=> (string) $fa2_code,
							'2FA-URL' 			=> (string) $fa2_url,
							'2FA-BARCODE' 		=> (string) $fa2_qrcode,
						]
					)
				]);
				//--
				break;

			case 'enable-2fa-confirm':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				$frm['chk'] 	= (string) trim((string)($frm['chk'] ?? null));
				$frm['key'] 	= (string) trim((string)($frm['key'] ?? null));
				//--
				$message = '';
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
					AND // and
					((string)$frm['id'] != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif((int)strlen((string)$frm['key']) != 52) {
					$message = 'INVALID 2FA KEY (empty)';
				} elseif(SmartHashCrypto::checksum((string)$frm['key'], (string)$frm['id']) != (string)$frm['chk']) {
					$message = 'INVALID 2FA KEY (checksum)';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->enableAccount2FA(
						(string) $frm['id'],
						(string) $frm['key']
					);
					$model = null; // close connection
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] 2FA Enabled !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to enable 2FA for Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				if((string)$status == 'OK') {
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$jsevcode = '';
				if((string)$frm['id'] == (string)SmartAuth::get_auth_id()) { // if 2FA enabled for current account must handle different
					$redirect = '';
					$jsevcode = 'setTimeout(() => { smartJ$Browser.RefreshParent(); smartJ$Browser.CloseDelayedModalPopUp(); }, 3750);';
				} //end if
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Account Enable 2FA',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			//------- account: view / edit

			case 'edit-form': // Edit form for User Edit (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts - Edit Account';
				//--
				$viewonly = $this->RequestVarGet('viewonly', '', 'string');
				$id = $this->RequestVarGet('id', '', 'string');
				if((string)trim((string)$id) == '') {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_warn('Empty Account selected for Edit')
					]);
					return;
					//--
				} //end if
				//--
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
					AND // and
					((string)$id != (string)SmartAuth::get_auth_id()) // not the current logged in user
				) {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('You are not authorized to view this area !')
					]);
					return;
					//--
				} //end if
				//--
				$model = null;
				try {
					$model = new SmartModelAuthAdmins(); // open connection
				} catch(Exception $e) {
					$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
					return 500;
				} //end try catch
				$select_user = (array) $model->getById((string)$id); // check if exist id in admins table
				//--
				if((int)Smart::array_size($select_user) <= 0) {
					//--
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' => SmartComponents::operation_error('Invalid Account Selected for Edit ...')
					]);
					return;
					//--
				} //end if
				//--
				$user_lkeys = 0;
				$user_pkeys = '';
				$user_2fakey = '';
				$user_2faurl = '';
				$user_2faqrcode = '';
				$user_2fatktest = '';
				$user_num_tokens = 0;
				$user_max_tokens = 0;
				if(
					(SmartAuth::test_login_privilege('super-admin') === true)
					OR
					((string)($select_user['id'] ?? null) == (string)SmartAuth::get_auth_id())
				) { // do not try to decrypt priv keys of another user (except superadmin) because are supposed to only be decrypted by the user itself only
					//--
					$user_lkeys = (int) strlen(((string)($select_user['keys'] ?? null)));
					if((string)($select_user['id'] ?? null) == (string)SmartAuth::get_auth_id()) {
						$user_pkeys = (string) $model->decryptPrivKey((string)($select_user['keys'] ?? null)); // {{{SYNC-ADM-AUTH-KEYS}}}
					} //end if
					if(SmartEnvironment::is2FAEnabled() === true) {
						if((string)($select_user['fa2'] ?? null) != '') {
							$user_2fakey = (string) $model->decrypt2FAKey((string)($select_user['fa2'] ?? null), (string)($select_user['id'] ?? null)); // {{{SYNC-ADM-AUTH-2FA-MANAGEMENT}}}
							$user_2faurl = (string) $model->get2FAUrl((string)$user_2fakey, (string)($select_user['id'] ?? null));
							$user_2faqrcode = (string) $model->get2FASvgBarCode((string)$user_2fakey, (string)($select_user['id'] ?? null));
							if(SmartEnvironment::ifDevMode()) {
								$user_2fatktest = (string) $model->get2FAPinToken((string)$user_2fakey);
							} //end if
						} //end if
					} //end if
					if(SmartEnvironment::isATKEnabled() === true) {
						$user_num_tokens = (int) $model->countTokensById((string)($select_user['id'] ?? null));
						$user_max_tokens = (int) $model::MAX_TOKENS_PER_ACCOUNT;
					} //end if
					//--
				} //end if
				//--
				$model = null; // close connection
				//--
				$all_privs = (string) \SmartAuth::DEFAULT_PRIVILEGES.','.APP_AUTH_PRIVILEGES; // {{{SYNC-AUTH-DEFAULT-ADM-SUPER-PRIVS}}}
				//--
				if(SmartAuth::test_login_restriction('def-account', (string)($select_user['restrict'] ?? null)) === true) { // {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-AUTH-RESTRICTIONS}}}
					$form_edit_priv = '<b>[Restricted / Default Account(s) can not modify privileges]</b><br>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) ($select_user['priv'] ?? null), // list of selected values
						'list',
						(array) SmartAuth::safe_arr_privileges_or_restrictions((string)($select_user['priv'] ?? null)) // array with all values
					);;
				} elseif((string)($select_user['id'] ?? null) == (string)SmartAuth::get_auth_id()) {
					$form_edit_priv = '<b>[Your own privileges can not be changed]</b><br>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) ($select_user['priv'] ?? null), // list of selected values
						'list',
						(array) SmartAuth::safe_arr_privileges_or_restrictions((string)($select_user['priv'] ?? null)) // array with all values
					);
				} else {
					$form_edit_priv = \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) ($select_user['priv'] ?? null), // list of selected values
						'form',
						(array) SmartAuth::safe_arr_privileges_or_restrictions((string)$all_privs), // array with all values
						'frm[priv][]', // html form variable
						'list',
						'no',
						'300/120', // dimensions
						'', // custom JS on selected done
						'#JS-UI#' // display mode (just for list !!!)
					);
				} //end if else
				//--
				if((string)$viewonly == 'yes') {
					$form_edit_priv = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) ($select_user['priv'] ?? null), // list of selected values
						'list',
						(array) SmartAuth::safe_arr_privileges_or_restrictions((string)($select_user['priv'] ?? null)) // array with all values
					);
				} //end if
				//--
				$restrictions = '';
				if((string)trim((string)($select_user['restrict'] ?? null)) !== '') {
					$restrictions = \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi(
						'restrictions-list', // html element ID
						(string) ($select_user['restrict'] ?? null), // list of selected values
						'list',
						(array) SmartAuth::safe_arr_privileges_or_restrictions((string)($select_user['restrict'] ?? null)) // array with all values
					);
				} //end if
				//--
				$this->PageViewSetVars([
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-form-view-edit.mtpl.htm',
						[
							'VIEW-ONLY' 		=> (string) (((string)$viewonly == 'yes') ? 'yes' : 'no'),
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'CRR-ID' 			=> (string) SmartAuth::get_auth_id(),
							'ID' 				=> (string) ($select_user['id'] ?? null),
							'EMAIL' 			=> (string) ($select_user['email'] ?? null),
							'FIRST-NAME' 		=> (string) ($select_user['name_f'] ?? null),
							'LAST-NAME' 		=> (string) ($select_user['name_l'] ?? null),
							'IS-SUPER-ADM' 		=> (string) ((SmartAuth::test_login_privilege('super-admin') === true) ? 'yes' : 'no'),
							'SELF-FA2' 			=> (string) (((string)($select_user['id'] ?? null) == (string)SmartAuth::get_auth_id() || (SmartAuth::test_login_privilege('super-admin') === true)) ? 'yes' : 'no'),
							'SELF-KEYS' 		=> (string) (((string)($select_user['id'] ?? null) == (string)SmartAuth::get_auth_id()) ? 'yes' : 'no'),
							'LEN-KEYS' 			=> (int)    $user_lkeys,
							'KEYS' 				=> (string) $user_pkeys, // {{{SYNC-ADM-AUTH-KEYS}}}
							'MAX-TOKENS' 		=> (int)    $user_max_tokens,
							'NUM-TOKENS' 		=> (int)    $user_num_tokens,
							'ENABLED-TOKENS' 	=> (int)    (bool) SmartEnvironment::isATKEnabled(),
							'ENABLED-2FA' 		=> (int)    (bool) SmartEnvironment::is2FAEnabled(),
							'2FA-KEY' 			=> (string) $user_2fakey, // {{{SYNC-ADM-AUTH-2FAKEY}}}
							'2FA-TEST-TK' 		=> (string) $user_2fatktest,
							'2FA-TEST-DATE' 	=> (string) date('Y-m-d H:i:s O'),
							'2FA-URL' 			=> (string) $user_2faurl,
							'2FA-BARCODE' 		=> (string) (($user_2faurl && $user_2faqrcode) ? $user_2faqrcode : ''),
							'PRIV-LIST-HTML' 	=> (string) $form_edit_priv, // HTML code
							'RESTR-LIST-HTML' 	=> (string) $restrictions,
						]
					)
				]);
				//--
				break;

			case 'edit-update': // update a column and (RETURNS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 				= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 			= (string) trim((string)($frm['id'] ?? null));
				$frm['name_f'] 		= (string) trim((string)($frm['name_f'] ?? null));
				$frm['name_l'] 		= (string) trim((string)($frm['name_l'] ?? null));
				$frm['email'] 		= (string) trim((string)($frm['email'] ?? null));
				$frm['keys'] 		= (string) trim((string)($frm['keys'] ?? null));
				$frm['upd-keys'] 	= (string) trim((string)($frm['upd-keys'] ?? null));
				$frm['priv'] 		= (array)  ((isset($frm['priv']) && is_array($frm['priv'])) ? $frm['priv'] : []);
				//--
				$message = '';
				if(
					(SmartAuth::test_login_privilege('super-admin') === true) // is superadmin
					OR
					((string)$frm['id'] == (string)SmartAuth::get_auth_id()) // or is the same logged in user
				) { // PRIVILEGES
					//--
					if((string)$frm['id'] == '') {
						$message = 'INVALID ID (empty)';
					} elseif(((int)SmartUnicode::str_len((string)$frm['name_f']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_f']) > 64)) {
						$message = 'Invalid First Name Length: must be between 1 and 64 characters';
					} elseif(((int)SmartUnicode::str_len((string)$frm['name_l']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_l']) > 64)) {
						$message = 'Invalid Last Name Length: must be between 1 and 64 characters';
					} elseif((string)$frm['email'] != '') {
						if(((int)strlen((string)$frm['email']) < 7) OR ((int)strlen((string)$frm['email']) > 72)) { // {{{SYNC-SMART-EMAIL-LENGTH}}}
							$message = 'Invalid Email Length: must be between 7 and 72 characters';
						} elseif(!preg_match((string)SmartValidator::regex_stringvalidation_expression('email'), (string)$frm['email'])) {
							$message = 'Invalid Email Format: must use the standard format a-b.c_d@dom.ext';
						} //end if else
					} elseif(((string)$frm['keys'] != '') AND ((int)strlen((string)$frm['keys']) < 64)) { // 64 B = 512 b
						$message = 'Invalid Key: min size is 64 Bytes';
					} elseif(((string)$frm['keys'] != '') AND ((int)strlen((string)$frm['keys']) > 512)) { // 512 B = 4096 b
						$message = 'Invalid Key: max size is 512 Bytes';
					} //end if else
					//--
				} else {
					//--
					$message = 'You are not authorized to use this area !';
					//--
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->updateAccount(
						(string) $frm['id'],
						(array)  [
							'email' 	=> (string) $frm['email'],
							'name_f' 	=> (string) $frm['name_f'],
							'name_l' 	=> (string) $frm['name_l'],
							'keys' 		=> (string) $frm['keys'],
							'upd-keys' 	=> (string) $frm['upd-keys'],
							'priv' 		=> (array)  $frm['priv'],
						]
					);
					$model = null; // close connection
				} //end if
				//--
				if(($wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] updated !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to update Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				if($status == 'OK') {
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Account Update',
						(string) $message,
						(string) $redirect
					)
				);
				//--
				break;

			//------- account: activate / deactivate

			case 'edit-cell':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$column = $this->RequestVarGet('column', '', 'string');
				$value = $this->RequestVarGet('value', '', 'string');
				$id = $this->RequestVarGet('id', '', 'string');
				//--
				$title = 'Update Column ['.$column.'] for ID: '.$id; //.' @ '.$value;
				$status = 'ERROR';
				$message = '???';
				//--
				if(SmartAuth::test_login_privilege('super-admin') !== true) { // PRIVILEGES
					//--
					$message = 'Only Super-Admins are authorized to use this feature !';
					//--
				} else {
					//--
					switch((string)$column) {
						case 'active':
							//--
							$model = null;
							try {
								$model = new SmartModelAuthAdmins(); // open connection
							} catch(Exception $e) {
								$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
								return 500;
							} //end try catch
							$select_user = (array) $model->getById((string)$id);
							//--
							if(SmartAuth::test_login_restriction('def-account', (string)($select_user['restrict'] ?? null)) === true) { // {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-AUTH-RESTRICTIONS}}}
								//--
								$message = 'This is a Restricted or Default account and cannot be modified';
								//--
							} elseif((string)$id == (string)SmartAuth::get_auth_id()) {
								//--
								$message = 'Current account cannot be modified';
								//--
							} else {
								//--
								$wr = (int) $model->updateStatus(
									(string) $id,
									(int)    ((((string)strtolower((string)$value) == 'true') || ((string)$value == '1')) ? 1 : 0)
								);
								//--
								if((int)$wr === 1) {
									$status = 'OK';
									$message = 'Status ['.ucfirst($column).'] updated';
								} else {
									$message = 'FAILED to update Status ['.ucfirst((string)$column).']';
								} //end if else
								//--
							} //end if else
							//--
							$model = null; // close connection
							//--
							break;
						default:
							//--
							$message = 'Data column is not editable: `'.$column.'`';
							//--
					} //end switch
					//--
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						(string) $title,
						(string) Smart::escape_html((string)$message)
					)
				);
				//--
				break;

			//------- delete account

			case 'delete-acc':
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts Management - Remove Account';
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				if((string)trim((string)$id) == '') {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_warn('Empty Account selected for Deletion')
					]);
					return;
					//--
				} //end if
				//--
				if(SmartAuth::test_login_privilege('super-admin') !== true) {
					//--
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('You are not authorized to view this area !')
					]);
					return;
					//--
				} //end if
				//--
				$model = null;
				try {
					$model = new SmartModelAuthAdmins(); // open connection
				} catch(Exception $e) {
					$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
					return 500;
				} //end try catch
				$select_user = (array) $model->getById((string)$id); // check if exist id in admins table
				//--
				if((int)Smart::array_size($select_user) <= 0) {
					//--
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' => SmartComponents::operation_error('Invalid Account Selected for Deletion ...')
					]);
					return;
					//--
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-confirm-delete.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'ID' 				=> (string) ($select_user['id'] ?? null)
						]
					)
				]);
				//--
				break;

			case 'delete-confirm-acc':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				//--
				$message = '';
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true) // not superadmin
				) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif((string)$frm['id'] == (string)SmartAuth::get_auth_id()) {
					$message = 'You can not delete your own account !';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$select_user = (array) $model->getById((string)$frm['id']); // check if exist id in admins table
					if((int)Smart::array_size($select_user) > 0) {
						if((string)($select_user['active'] ?? null) === '0') {
							$wr = (int) $model->deleteAccount((string)$frm['id']);
						} else {
							$message = 'Selected Account cannot be Deleted, it is still Active. Deactivate first ...';
						} //end if else
					} else {
						$message = 'Selected Account does not exists for Deletion ...';
					} //end if else
					$model = null; // close connection
				} //end if
				//--
				if(((int)$wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] DELETED !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to DELETE Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				if((string)$status == 'OK') {
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$jsevcode = '';
				if((string)$frm['id'] == (string)SmartAuth::get_auth_id()) { // if change password for current account must handle different
					$redirect = '';
					$jsevcode = 'setTimeout(() => { smartJ$Browser.RefreshParent(); smartJ$Browser.CloseDelayedModalPopUp(); }, 3750);';
				} //end if
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'Account Delete',
						(string) $message,
						(string) $redirect,
						'',
						'',
						(string) $jsevcode
					)
				);
				//--
				break;

			//------- new account

			case 'new-form': // Form for Add new User (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Auth.Admins Accounts - Add New Account';
				//--
				if(SmartAuth::test_login_privilege('super-admin') !== true) { // PRIVILEGES
					//--
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' 	=> (string) SmartComponents::operation_error('You are not authorized to view this area. Only Super-Admins are !'),
					]);
					return;
					//--
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'acc-form-add.mtpl.htm',
						[
							'ACTIONS-URL' => (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=new-add'
						]
					)
				]);
				//--
				break;

			case 'new-add': // Do Add new User (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm 			= (array)  $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)($frm['id'] ?? null));
				$frm['pass'] 	= (string) trim((string)($frm['pass'] ?? null));
				$frm['repass'] 	= (string) trim((string)($frm['repass'] ?? null));
				$frm['name_f'] 	= (string) trim((string)($frm['name_f'] ?? null));
				$frm['name_l'] 	= (string) trim((string)($frm['name_l'] ?? null));
				$frm['email'] 	= (string) trim((string)($frm['email'] ?? null));
				//--
				$message = '';
				if(SmartAuth::test_login_privilege('super-admin') !== true) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif(SmartAuth::validate_auth_username((string)$frm['id'], true) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
					$message = 'Invalid Username ID: Too short or too long. Must use only this pattern: a-z 0-9 .';
				} elseif(SmartAuth::validate_auth_password((string)$frm['pass'], (bool)((defined('APP_AUTH_ADMIN_COMPLEX_PASSWORDS') && (APP_AUTH_ADMIN_COMPLEX_PASSWORDS === true)) ? true : false)) !== true) { // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
					$message = 'Invalid Password: Too short or too long or does not match the minimal complexity level ...';
				} elseif(((string)$frm['repass'] !== (string)$frm['pass']) OR ((string)SmartHashCrypto::sha256((string)$frm['repass']) !== (string)SmartHashCrypto::sha256((string)$frm['pass']))) {
					$message = 'Invalid Password Retype: does not match the Password';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_f']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_f']) > 64)) {
					$message = 'Invalid First Name Length: must be between 1 and 64 characters';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_l']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_l']) > 64)) {
					$message = 'Invalid Last Name Length: must be between 1 and 64 characters';
				} elseif((string)$frm['email'] != '') {
					if(((int)strlen((string)$frm['email']) < 7) OR ((int)strlen((string)$frm['email']) > 72)) { // {{{SYNC-SMART-EMAIL-LENGTH}}}
						$message = 'Invalid Email Length: must be between 7 and 72 characters';
					} elseif(!preg_match((string)SmartValidator::regex_stringvalidation_expression('email'), (string)$frm['email'])) {
						$message = 'Invalid Email Format: must use the standard format a-b.c_d@dom.ext';
					} //end if else
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = null;
					try {
						$model = new SmartModelAuthAdmins(); // open connection
					} catch(Exception $e) {
						$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
						return 500;
					} //end try catch
					$wr = (int) $model->insertAccount(
						[
							'id' 	 => (string) $frm['id'],
							'email'  => (string) $frm['email'],
							'pass' 	 => (string) $frm['pass'],
							'name_f' => (string) $frm['name_f'],
							'name_l' => (string) $frm['name_l']
						]
					);
					$model = null; // close connection
				} //end if
				if(($wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account [<b>'.Smart::escape_html((string)$frm['id']).'</b>] was created !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to add new Account: [<b>'.Smart::escape_html((string)$frm['id']).'</b>] / Error: '.Smart::escape_html((string)$wr);
					} //end if
				} //end if else
				//--
				if($status == 'OK') {
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						'New Account Creation',
						(string) $message,
						(string) $redirect
					)
				);
				//--
				break;

			//------- accounts list

			case 'list': // list data (RETURNS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//-- list vars
				$ofs = $this->RequestVarGet('ofs', 0, 'integer+');
				$sortby = $this->RequestVarGet('sortby', 'id', 'string');
				$sortdir = $this->RequestVarGet('sortdir', 'ASC', 'string');
				$sorttype = $this->RequestVarGet('sorttype', 'string', 'string');
				//-- filter vars
				$id = $this->RequestVarGet('id', '', 'string');
				//--
				$strict_type = false;
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true)
					AND
					(SmartAuth::test_login_privilege('admin') !== true)
				) { // restrict list to it's own account only
					$id = (string) SmartAuth::get_auth_id();
					$strict_type = true;
				} //end if
				//-- output var(s)
				$data = [];
				$data['status'] = 'OK';
				$data['crrOffset'] = (int) $ofs;
				$data['itemsPerPage'] = 25;
				$data['sortBy'] = (string) $sortby;
				$data['sortDir'] = (string) $sortdir;
				$data['sortType'] = (string) $sorttype;
				$data['filter'] = array(
					'id' => (string) $id
				);
				//--
				$model = null;
				try {
					$model = new SmartModelAuthAdmins(); // open connection
				} catch(Exception $e) {
					$this->PageViewSetCfg('error', 'DB Exception: '.$e->getMessage());
					return 500;
				} //end try catch
				$data['totalRows'] = $model->countByFilter((string)$id, (bool)$strict_type);
				$data['rowsList'] = $model->getListByFilter(
					(array)  ['id', 'active', 'email', 'name_f', 'name_l', 'modif', 'priv', ['keys' => 'length'], ['fa2' => 'length']],
					(int)    $data['itemsPerPage'],
					(int)    $ofs,
					(string) $sortby,
					(string) $sortdir,
					(string) $id,
					(bool)   $strict_type
				);
				$model = null; // close connection
				//--
				$this->PageViewSetVar(
					'main',
					(string) Smart::json_encode((array)$data)
				);
				//--
				break;

			case '': // list: display the grid (OUTPUTS: HTML)
				//--
				$id = '';
				$strict_type = false;
				if(
					(SmartAuth::test_login_privilege('super-admin') !== true)
					AND
					(SmartAuth::test_login_privilege('admin') !== true)
				) { // restrict list to it's own account only
					$id = (string) SmartAuth::get_auth_id();
					$strict_type = true;
				} //end if
				//--
				$title = 'Auth.Admins Accounts - List Accounts';
				//--
				$areas = (array) \SmartModExtLib\AuthAdmins\AuthNameSpaces::GetNameSpaces();
				//--
				$main = (string) SmartMarkersTemplating::render_file_template(
					(string) $this->ControllerGetParam('module-view-path').'acc-list.mtpl.htm',
					[
						'RESTRICTED-ID' 	=> (string) $id,
						'IS-RESTRICTED' 	=> (string) (!!$strict_type ? 'yes' : false),
						'IS-SUPERADM' 		=> (string) ((SmartAuth::test_login_privilege('super-admin') === true) ? 'yes' : 'no'),
						'CURRENT-SCRIPT' 	=> (string) $this->ControllerGetParam('url-script'),
						'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
						'RELEASE-HASH' 		=> (string) $this->ControllerGetParam('release-hash'),
						'AREAS' 			=> (array)  $areas,
						'CRR-ID' 			=> (string) SmartAuth::get_auth_id(),
					]
				);
				//--
				$semaphores = [];
				$semaphores[] = 'load:js-uix';
				$this->PageViewSetVars([
					'semaphore' 	=> (string) $this->PageViewCreateSemaphores((array)$semaphores),
					'title' 		=> (string) $title,
					'main' 			=> (string) $main,
				]);
				//--
				break;

			//------- DEFAULT

			default: // other invalid actions
				//--
				$this->PageViewSetCfg('error', 'Auth Admins Manager :: Invalid Action `'.$action.'` ...');
				//--
				return 400;
				//--

		} // end switch


	} //END FUNCTION


} //END CLASS


//end of php code
