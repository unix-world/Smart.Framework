<?php
// Controller: AuthAdmins/Manager
// Route: admin.php?page=auth-admins.manager.stml
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN');
define('SMART_APP_MODULE_AUTH', true); 		// if set to TRUE requires auth always

// [PHP8]

/**
 * Admin Controller (direct output)
 * @ignore
 */
final class SmartAppAdminController extends SmartAbstractAppController {

	// v.20210526

	public function Run() { // (OUTPUTS: HTML)

		//--
		if(SmartAuth::check_login() !== true) {
			$this->PageViewSetCfg('error', 'Auth Admins Manager Requires Authentication ! ...');
			return 403;
		} //end if
		//--
		if(SmartAuth::get_login_realm() !== 'ADMINS-AREA') {
			$this->PageViewSetCfg('error', 'This Area Requires the ADMINS-AREA Auth Realm ! ...');
			return 403;
		} //end if
		//--

		//-- use defaults
	//	$this->PageViewSetCfg('template-path', 'default');
	//	$this->PageViewSetCfg('template-file', 'template.htm');
		//--

		//--
		$action = $this->RequestVarGet('action', '', 'string');
		//--

		switch((string)$action) {

			case 'login-timeout': // {{{SYNC-ADM-AUTH-REDIRECT-ON-LOGIN}}}
				//--
				$url = $this->RequestVarGet('url', '', 'string');
				$redirect = (string) SmartUtils::crypto_blowfish_decrypt((string)$url);
				//--
				if((string)$redirect == '') {
					$redirect = 'admin.php';
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => 'Redirecting Back',
					'main' => (string) '<script>setTimeout(() => { self.location = \''.Smart::escape_js((string)$redirect).'\'; }, 750);</script>'
				]);
				//--
				break;

			case 'login-check': // {{{SYNC-ADM-AUTH-REDIRECT-ON-LOGIN}}}
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$url = $this->RequestVarGet('url', '', 'string');
				$redirect = (string) SmartUtils::crypto_blowfish_decrypt((string)$url);
				//--
				if((string)$redirect != '') {
					$this->PageViewSetVar(
						'main',
						SmartViewHtmlHelpers::js_ajax_replyto_html_form(
							'OK',
							'You are logged in',
							'Login Check Successful ...<br>'.Smart::escape_html((string)date('Y-m-d H:i:s O')),
							(string) $redirect
						)
					);
				} else {
					$this->PageViewSetVar(
						'main',
						SmartViewHtmlHelpers::js_ajax_replyto_html_form(
							'WARNING',
							'You are logged in',
							'NO Redirect URL Provided for Login !<br>Login Check Successful ...<br>'.Smart::escape_html((string)date('Y-m-d H:i:s O')),
							'admin.php'
						)
					);
				} //end if else
				//--
				break;

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

			case 'change-pass-form': // Change Pass Form
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Change Password for Account';
				//--
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('You are not authorized to use this area !')
					]);
					return;
				} //end if
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				//--
				$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
				$select_user = $model->getById((string)$id);
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
				$this->PageViewSetVar(
					'main',
					SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'admins-change-pass.mtpl.htm',
						[
							'ACTIONS-URL' 		=> 'admin.php?page='.$this->ControllerGetParam('controller').'&action=',
							'ID' 				=> (string) $select_user['id']
						]
					)
				);
				//--
				break;

			case 'change-pass-update':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)$frm['id']);
				$frm['pass'] 	= (string) trim((string)$frm['pass']);
				$frm['repass'] 	= (string) trim((string)$frm['repass']);
				//--
				$message = '';
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif((string)trim((string)$frm['id']) == '') {
					$message = 'INVALID ID (empty)';
				} elseif(((string)trim((string)$frm['pass']) == '') OR ((int)SmartUnicode::str_len((string)$frm['pass']) < 7) OR ((int)SmartUnicode::str_len((string)$frm['pass']) > 30)) { // {{{SYNC-AUTH-ADMINS-CONDITION-VALIDATE-PASSWORD}}}
					$message = 'Invalid Password Length: must be between 7 and 30 characters';
				} elseif(((string)$frm['repass'] !== (string)$frm['pass']) OR ((string)sha1((string)$frm['repass']) !== (string)sha1((string)$frm['pass']))) {
					$message = 'Invalid Password: Password and Retype of Password does not match';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
					$wr = $model->updatePassword(
						(string) $frm['id'],
						(string) SmartHashCrypto::password((string)$frm['pass'], (string)$frm['id']),
						(string) $frm['pass'] // this is required to re-encode keys
					);
					$model = null; // close connection
				} //end if
				//--
				if(($wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account: [<b>'.Smart::escape_html($frm['id']).'</b>] password updated !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to update password for Account: [<b>'.Smart::escape_html($frm['id']).'</b>] / Error: '.Smart::escape_html($wr);
					} //end if
				} //end if else
				//--
				if($status == 'OK') {
					$redirect = 'admin.php?page='.$this->ControllerGetParam('controller').'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$jsevcode = '';
				if((string)$frm['id'] == (string)SmartAuth::get_login_id()) { // if change password for current account must handle different
					$redirect = '';
					$jsevcode = 'smartJ$Browser.RefreshParent(); smartJ$Browser.CloseDelayedModalPopUp();';
				} //end if
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						'Account Password Update',
						$message,
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'edit-form': // Edit form for User Edit (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Edit Account';
				//--
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$this->PageViewSetVars([
						'title' => $title,
						'main' => SmartComponents::operation_error('You are not authorized to use this area !')
					]);
					return;
				} //end if
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				//--
				$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
				$select_user = $model->getById((string)$id);
				//--
				$user_pkeys = '';
				if(Smart::array_size($select_user) > 0) { // Check if exist id in admins table
					if((string)$select_user['id'] == (string)SmartAuth::get_login_id()) { // do not try to decrypt priv keys of another user because they can only be decrypted with the user's self password
						$user_pkeys = (string) $model->decryptPrivKey((string)$select_user['keys']); // {{{SYNC-ADM-AUTH-KEYS}}}
					} //end if
				} else {
					$this->PageViewSetVars([
						'title' => (string) $title,
						'main' => SmartComponents::operation_error('Invalid Account Selected for Edit ...')
					]);
					return;
				} //end if
				//--
				$model = null; // close connection
				//--
				$all_privs = '<superadmin>, '.APP_AUTH_PRIVILEGES;
				//--
				if(strpos((string)$select_user['restrict'], '<modify>') !== false) { // {{{SYNC-EDIT-PRIVILEGES}}}
					$form_edit_priv = '<b>[You cannot edit privileges for this Restricted Account]</b><br>'.SmartViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) $select_user['priv'], // list of selected values
						'list',
						(array) SmartAuth::build_arr_privileges((string)$all_privs) // array with all values
					);;
				} elseif((string)$select_user['id'] == (string)SmartAuth::get_login_id()) {
					$form_edit_priv = '<b>[You cannot edit your own privileges]</b><br>'.SmartViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) $select_user['priv'], // list of selected values
						'list',
						(array) SmartAuth::build_arr_privileges((string)$all_privs) // array with all values
					);
				} else {
					$form_edit_priv = SmartViewHtmlHelpers::html_select_list_multi(
						'priv-list', // html element ID
						(string) $select_user['priv'], // list of selected values
						'form',
						(array) SmartAuth::build_arr_privileges((string)APP_AUTH_PRIVILEGES), // array with all values
						'frm[priv][]', // html form variable
						'list',
						'no',
						'300/120', // dimensions
						'', // custom JS on selected done
						'#JS-UI#' // display mode (just for list !!!)
					);
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'admins-form-edit.mtpl.htm',
						[
							'ACTIONS-URL' 		=> 'admin.php?page='.$this->ControllerGetParam('controller').'&action=',
							'ID' 				=> (string) $select_user['id'],
							'EMAIL' 			=> (string) $select_user['email'],
							'FIRST-NAME' 		=> (string) $select_user['name_f'],
							'LAST-NAME' 		=> (string) $select_user['name_l'],
							'SELF-KEYS' 		=> (string) ((string)$select_user['id'] == (string)SmartAuth::get_login_id()) ? 'yes' : 'no',
							'KEYS' 				=> (string) $user_pkeys, // {{{SYNC-ADM-AUTH-KEYS}}}
							'PRIV-LIST-HTML' 	=> (string) $form_edit_priv // HTML code
						]
					)
				);
				//--
				break;

			case 'edit-update': // update a column and (RETURNS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$status = 'WARNING';
				$message = 'No Operation ...';
				//--
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)(isset($frm['id']) 		? $frm['id'] 		: ''));
				$frm['name_f'] 	= (string) trim((string)(isset($frm['name_f']) 	? $frm['name_f'] 	: ''));
				$frm['name_l'] 	= (string) trim((string)(isset($frm['name_l']) 	? $frm['name_l'] 	: ''));
				$frm['email'] 	= (string) trim((string)(isset($frm['email']) 	? $frm['email'] 	: ''));
				$frm['keys'] 	= (string) trim((string)(isset($frm['keys']) 	? $frm['keys'] 		: ''));
				$frm['priv'] 	= (array)  ((isset($frm['priv']) && is_array($frm['priv'])) ? $frm['priv'] : []);
				//--
				$message = '';
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif((string)$frm['id'] == '') {
					$message = 'INVALID ID (empty)';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_f']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_f']) > 64)) {
					$message = 'Invalid First Name Length: must be between 1 and 64 characters';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_l']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_l']) > 64)) {
					$message = 'Invalid Last Name Length: must be between 1 and 64 characters';
				} elseif((string)$frm['email'] != '') {
					if(((int)strlen((string)$frm['email']) < 6) OR ((int)strlen((string)$frm['email']) > 96)) {
						$message = 'Invalid Email Length: must be between 6 and 96 characters';
					} elseif(!preg_match((string)SmartValidator::regex_stringvalidation_expression('email'), (string)$frm['email'])) {
						$message = 'Invalid Email Format: must use the standard format a-b.c_d@dom.ext';
					} //end if else
				} elseif((int)strlen((string)$frm['keys']) > 512) { // 512 B = 4096 b
					$message = 'Invalid Key: max size is 512';
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
					$wr = $model->updateAccount(
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
					$message = 'Account: [<b>'.Smart::escape_html($frm['id']).'</b>] updated !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to update Account: [<b>'.Smart::escape_html($frm['id']).'</b>] / Error: '.Smart::escape_html($wr);
					} //end if
				} //end if else
				//--
				if($status == 'OK') {
					$redirect = 'admin.php?page='.$this->ControllerGetParam('controller').'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						'Account Update',
						$message,
						$redirect
					)
				);
				//--
				break;

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
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} else {
					switch((string)$column) {
						case 'active':
							//--
							$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
							$select_user = $model->getById((string)$id);
							//--
							if(strpos((string)$select_user['restrict'], '<modify>') !== false) { // {{{SYNC-EDIT-PRIVILEGES}}}
								//--
								$message = 'This is a restricted account and cannot be modified';
								//--
							} elseif((string)$id == (string)SmartAuth::get_login_id()) {
								//--
								$message = 'Current account cannot be modified';
								//--
							} else {
								//--
								$wr = $model->updateStatus(
									(string) $id,
									(int)    (strtolower($value) == 'true' || $value == '1') ? 1 : 0
								);
								//--
								if($wr == 1) {
									$status = 'OK';
									$message = 'Status ['.ucfirst($column).'] updated';
								} else {
									$message = 'FAILED to update Status ['.ucfirst($column).']';
								} //end if else
								//--
							} //end if else
							//--
							$model = null; // close connection
							//--
							break;
						default:
							$message = 'Data column is not editable: '.$column;
					} //end switch
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						(string) $status,
						(string) $title,
						(string) Smart::escape_html((string)$message)
					)
				);
				//--
				break;

			case 'new-form': // Form for Add new User (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				if(SmartAuth::test_login_privilege('admin') === true) { // PRIVILEGES
					//--
					$this->PageViewSetVars([
						'title' => 'Admins Management - Add User',
						'main' => SmartMarkersTemplating::render_file_template(
							$this->ControllerGetParam('module-view-path').'admins-form-add.mtpl.htm',
							[
								'ACTIONS-URL' => 'admin.php?page='.$this->ControllerGetParam('controller').'&action=new-add'
							]
						)
					]);
					//--
				} else {
					//--
					$main = SmartComponents::operation_notice('You are not authorized to view this area !');
					//--
				} // end if (PRIVILEGES)
				//--
				break;

			case 'new-add': // Do Add new User (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$frm['id'] 		= (string) trim((string)(isset($frm['id']) 		? $frm['id'] 		: ''));
				$frm['pass'] 	= (string) trim((string)(isset($frm['pass']) 	? $frm['pass'] 		: ''));
				$frm['repass'] 	= (string) trim((string)(isset($frm['repass']) 	? $frm['repass'] 	: ''));
				$frm['name_f'] 	= (string) trim((string)(isset($frm['name_f']) 	? $frm['name_f'] 	: ''));
				$frm['name_l'] 	= (string) trim((string)(isset($frm['name_l']) 	? $frm['name_l'] 	: ''));
				$frm['email'] 	= (string) trim((string)(isset($frm['email']) 	? $frm['email'] 	: ''));
				//--
				$message = '';
				if(SmartAuth::test_login_privilege('admin') !== true) { // PRIVILEGES
					$message = 'You are not authorized to use this area !';
				} elseif(((string)trim((string)$frm['id']) == '') OR ((int)strlen((string)$frm['id']) < 3) OR ((int)strlen((string)$frm['id']) > 25)) { // {{{SYNC-AUTH-ADMINS-CONDITION-VALIDATE-USERNAME}}}
					$message = 'Invalid ID: must be between 3 and 25 characters';
				} elseif(!preg_match('/^[a-z0-9\.]+$/', (string)$frm['id'])) { // {{{SYNC-AUTH-ADMINS-CONDITION-VALIDATE-USERNAME}}}
					$message = 'Invalid ID: must use only this pattern: a-z 0-9 .';
				} elseif(((string)trim((string)$frm['pass']) == '') OR ((int)SmartUnicode::str_len((string)$frm['pass']) < 7) OR ((int)SmartUnicode::str_len((string)$frm['pass']) > 30)) { // {{{SYNC-AUTH-ADMINS-CONDITION-VALIDATE-PASSWORD}}}
					$message = 'Invalid Password Length: must be between 7 and 30 characters';
				} elseif(((string)$frm['repass'] !== (string)$frm['pass']) OR ((string)sha1((string)$frm['repass']) !== (string)sha1((string)$frm['pass']))) {
					$message = 'Invalid Password: Password and Retype of Password does not match';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_f']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_f']) > 64)) {
					$message = 'Invalid First Name Length: must be between 1 and 64 characters';
				} elseif(((int)SmartUnicode::str_len((string)$frm['name_l']) < 1) OR ((int)SmartUnicode::str_len((string)$frm['name_l']) > 64)) {
					$message = 'Invalid Last Name Length: must be between 1 and 64 characters';
				} elseif((string)$frm['email'] != '') {
					if(((int)strlen((string)$frm['email']) < 6) OR ((int)strlen((string)$frm['email']) > 96)) {
						$message = 'Invalid Email Length: must be between 6 and 96 characters';
					} elseif(!preg_match((string)SmartValidator::regex_stringvalidation_expression('email'), (string)$frm['email'])) {
						$message = 'Invalid Email Format: must use the standard format a-b.c_d@dom.ext';
					} //end if else
				} //end if else
				//--
				$wr = 0;
				$status = 'INVALID';
				//--
				if((string)$message == '') {
					$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
					$wr = $model->insertAccount([
						'id' 	 => (string) $frm['id'],
						'email'  => (string) $frm['email'],
						'pass' 	 => (string) SmartHashCrypto::password((string)$frm['pass'], (string)$frm['id']),
						'name_f' => (string) $frm['name_f'],
						'name_l' => (string) $frm['name_l']
					]);
					$model = null; // close connection
				} //end if
				if(($wr == 1) AND ((string)$message == '')) {
					$status = 'OK';
					$message = 'Account [<b>'.Smart::escape_html($frm['id']).'</b>] was created !';
				} else {
					$status = 'ERROR';
					if((string)$message == '') {
						$message = 'Failed to add new Account: [<b>'.Smart::escape_html($frm['id']).'</b>] / Error: '.Smart::escape_html($wr);
					} //end if
				} //end if else
				//--
				if($status == 'OK') {
					$redirect = 'admin.php?page='.$this->ControllerGetParam('controller').'&action=close-modal'; // redirect URL (just on success)
				} else {
					$redirect = '';
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						'New Account Creation',
						$message,
						$redirect
					)
				);
				//--
				break;

			case 'list': // list data (RETURNS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				if(SmartAuth::test_login_privilege('admin') === true) { // PRIVILEGES
					//-- list vars
					$ofs = $this->RequestVarGet('ofs', 0, 'integer+');
					$sortby = $this->RequestVarGet('sortby', 'id', 'string');
					$sortdir = $this->RequestVarGet('sortdir', 'ASC', 'string');
					$sorttype = $this->RequestVarGet('sorttype', 'string', 'string');
					//-- filter vars
					$id = $this->RequestVarGet('id', '', 'string');
					//-- output var(s)
					$data['status'] = 'OK';
					$data['crrOffset'] = (int) $ofs;
					$data['itemsPerPage'] = 25;
					$data['sortBy'] = (string) $sortby;
					$data['sortDir'] = (string) $sortdir;
					$data['sortType'] = (string) $sorttype;
					$data['filter'] = array(
						'id' => (string) $id
					);
					$model = new \SmartModDataModel\AuthAdmins\SqAuthAdmins(); // open connection
					$data['totalRows'] = $model->countByFilter($id);
					$data['rowsList'] = $model->getListByFilter(['id', 'active', 'email', 'name_f', 'name_l', 'modif', 'priv', ['keys' => 'length']], $data['itemsPerPage'], $ofs, $sortby, $sortdir, $id);
					$model = null; // close connection
					//--
				} else {
					//--
					$data['status'] = 'WARNING';
					$data['error'] = 'You are not authorized to view this area !';
					//--
				} // end if else (PRIVILEGES)
				//--
				$this->PageViewSetVar(
					'main', Smart::json_encode((array)$data)
				);
				//--
				break;

			default: // display the grid (OUTPUTS: HTML)
				//--
				$title = 'Admins Management - List';
				//--
				if(SmartAuth::test_login_privilege('admin') === true) { // PRIVILEGES
					//--
					$main = SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'admins-list.mtpl.htm',
						[
							'ACTIONS-URL' => 'admin.php?page='.$this->ControllerGetParam('controller').'&action=',
							'RELEASE-HASH' => (string) $this->ControllerGetParam('release-hash')
						]
					);
					//--
				} else {
					//--
					$main = SmartComponents::operation_notice('You are not authorized to view this area !');
					//--
				} //end if else
				//--
				$this->PageViewSetVars([
					'title' => $title,
					'main' => $main
				]);
				//--

		} // end switch


	} //END FUNCTION


} //END CLASS


//end of php code
