<?php
// Controller: OAuth2 Client Manager
// Route: admin.php?page=oauth2.manager.stml
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN'); // INDEX, ADMIN, SHARED
define('SMART_APP_MODULE_AUTH', true);

/**
 * Admin Controller
 *
 * @ignore
 * @version v.20250203
 *
 */
final class SmartAppAdminController extends SmartAbstractAppController {


	public function Initialize() {
		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
		$this->PageViewSetCfg('template-file', 'template.htm');
		return true;
		//--
	} //END FUNCTION


	public function Run() {

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetCfg('error', 'OAuth2 Manager Requires Authentication ! ...');
			return 403;
		} //end if
		//--
		if(!SmartEnvironment::isAdminArea()) { // allow: adm/tsk ; but this controller does not extends in a task controller
			$this->PageViewSetCfg('error', 'OAuth2 Manager is allowed to run under `Admin` area only ! ...');
			return 403;
		} //end if
		if(SmartEnvironment::isTaskArea()) {
			$this->PageViewSetCfg('error', 'OAuth2 Manager cannot run under Admin Task Area !');
			return 502;
		} //end if
		//--
		if(SmartAuth::test_login_privilege('oauth2') !== true) { // PRIVILEGES
			$this->PageViewSetCfg('error', 'OAuth2 Manager requires the following privileges: `oauth2` ...');
			return 403;
		} //end if
		//--

		//--
		$action = $this->RequestVarGet('action', '', 'string');
		//--

		switch((string)$action) {

			case 'close-modal': // Closes the Modal and Refresh the Parent (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$this->PageViewSetVars([
					'title' => 'Wait ...',
					'main' => '<br><div><center><img src="lib/framework/img/loading-bars.svg" width="64" height="64"></center></div>'.
					'<script>smartJ$Browser.RefreshParent();</script>'.
					'<script>smartJ$Browser.CloseDelayedModalPopUp();</script>'
				]);
				//--
				break;

			case 'new-form': // Form for Add new Record (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$csrfPrivKey = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfNewPrivateKey().'#OAuth2/AppID:'.'[NEW]';
				$csrfPubKey  = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPublicKey((string)$csrfPrivKey);
				//--
				$this->PageViewSetVars([
					'title' => 'OAuth2 Manager - Register New API',
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'form-record.mtpl.htm',
						[ // TODO: this will need an intermediary step, because App-Id is unknown at this step
							'PATTERN-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_PATTERN_VALID_ID,
							'REGEX-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_REGEX_VALID_ID,
							'DEFAULT-REDIRECT-URL' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_STANDALONE_REFRESH_URL, // {{{SYNC-OAUTH2-DEFAULT-REDIRECT-URL}}}
							'ACTIONS-URL' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=new-add',
							'TPL-AUTH-URL-PARAMS' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_PARAMS),
							'TPL-AUTH-URL-CHPART' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_CHPART),
							'IS-EDIT-FORM' 			=> (string) 'no',
							'FORM-ID' 				=> (string) '',
							'FORM-DESC' 			=> (string) '',
							'FORM-CLI-ID' 			=> (string) '',
							'FORM-CLI-SECRET' 		=> (string) '',
							'FORM-SCOPE' 			=> (string) '',
							'FORM-URL-REDIR' 		=> (string) $this->ControllerGetParam('url-addr').'index.php/page/oauth2.get-code/',
							'FORM-URL-AUTH' 		=> (string) '',
							'FORM-URL-TOKEN' 		=> (string) '',
							'FORM-CODE' 			=> (string) '',
							'COOKIE-NAME-CSRF' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF, // {{{SYNC-OAUTH2-COOKIE-NAME-CSRF}}}
							'COOKIE-VALUE-CSRF' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPrivateKeyEncrypt((string)$csrfPrivKey),
							'STATE-CSRF' 			=> (string) $csrfPubKey,
						]
					)
				]);
				//--
				break;

			case 'new-add': // Do Add new Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$data = $this->RequestVarGet('frm', [], 'array');
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				$test = \SmartModExtLib\Oauth2\Oauth2Api::initApiData((array)$data); // mixed
				if(is_array($test)) {
					$status = 'OK';
					$message = 'OAuth2 Client Initialization Done';
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal';
				} else {
					$status = 'ERROR';
					$message = 'ERR.Message: '.Smart::nl_2_br((string)Smart::escape_html((string)$test));
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						'Initialize OAuth2 API Tokens',
						$message,
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'reinit-update': // Re-Init Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$data = $this->RequestVarGet('frm', [], 'array');
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				$test = \SmartModExtLib\Oauth2\Oauth2Api::initApiData((array)$data, true); // mixed ; RE-INIT is TRUE
				if(is_array($test)) {
					$status = 'OK';
					$message = 'OAuth2 Client Re-Initialization Done';
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal';
				} else {
					$status = 'ERROR';
					$message = 'ERR.Message: '.Smart::nl_2_br((string)Smart::escape_html((string)$test));
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						'Re-Initialize OAuth2 API Tokens',
						$message,
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'reinit-token-form': // Form for Re-Authorize Record, when expired (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				$id = (string) trim((string)$id);
				if((string)$id == '') {
					$this->PageViewSetCfg('error', 'ID is Empty');
					return 400;
				} //end if
				//--
				$data = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiData((string)$id);
				if((int)Smart::array_size($data) <= 0) {
					$this->PageViewSetCfg('error', 'ID Not Found');
					return 400;
				} //end if
				//--
				$csrfPrivKey = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfNewPrivateKey().'#OAuth2/AppID:'.($data['id'] ?? null);
				$csrfPubKey  = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPublicKey((string)$csrfPrivKey);
				//--
				$this->PageViewSetVars([
					'title' => 'OAuth2 Manager - ReInitialize API',
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'form-record.mtpl.htm',
						[
							'PATTERN-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_PATTERN_VALID_ID,
							'REGEX-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_REGEX_VALID_ID,
							'DEFAULT-REDIRECT-URL' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_STANDALONE_REFRESH_URL, // {{{SYNC-OAUTH2-DEFAULT-REDIRECT-URL}}}
							'ACTIONS-URL' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=reinit-update',
							'TPL-AUTH-URL-PARAMS' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_PARAMS),
							'TPL-AUTH-URL-CHPART' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_CHPART),
							'IS-EDIT-FORM' 			=> (string) 'yes',
							'FORM-ID' 				=> (string) ($data['id'] ?? null),
							'FORM-DESC' 			=> (string) ($data['description'] ?? null),
							'FORM-CLI-ID' 			=> (string) ($data['client_id'] ?? null),
							'FORM-CLI-SECRET' 		=> (string) ($data['client_secret'] ?? null),
							'FORM-SCOPE' 			=> (string) ($data['scope'] ?? null),
							'FORM-URL-REDIR' 		=> (string) ($data['url_redirect'] ?? null),
							'FORM-URL-AUTH' 		=> (string) ($data['url_auth'] ?? null),
							'FORM-URL-TOKEN' 		=> (string) ($data['url_token'] ?? null),
							'FORM-CODE' 			=> (string) '', // this is step 2, must remain empty, it needs a refresh
							'COOKIE-NAME-CSRF' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF, // {{{SYNC-OAUTH2-COOKIE-NAME-CSRF}}}
							'COOKIE-VALUE-CSRF' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPrivateKeyEncrypt((string)$csrfPrivKey),
							'STATE-CSRF' 			=> (string) $csrfPubKey,
						]
					)
				]);
				//--
				break;

			case 'view-data': // Form for Display Record (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				$id = (string) trim((string)$id);
				if((string)$id == '') {
					$this->PageViewSetCfg('error', 'ID is Empty');
					return 400;
				} //end if
				//--
			//	$data = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiData((string)$id);
				$data = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiDisplayData((string)$id); // this will also pretty print JWT / Tokens
				if((int)Smart::array_size($data) <= 0) {
					$this->PageViewSetCfg('error', 'ID Not Found');
					return 400;
				} //end if
				//--
				$title = 'OAuth2 API - Display';
				//--
				$haveRefreshToken = true;
				if((string)trim((string)$data['refresh_token']) == '') {
					$haveRefreshToken = false;
				} //end if
				//--
				$isExpiringToken = true;
				if( // {{{SYNC-TOKEN-NON-EXPIRING-TEST}}}
					((int)$data['access_expire_seconds'] <= 0) // if expiring seconds is not greater than zero it means also does not expires ; test the `access_expire_seconds` (provided by OAuth2 answer) instead of `access_expire_time` (calculated only)
				) {
					$isExpiringToken = false;
				} //end if
				//--
				$crrTime = (int) time();
				//--
				$isExpired = false;
				if($isExpiringToken === true) {
					if((int)$data['access_expire_seconds'] > 0) {
						if((int)$crrTime > ((int)$data['modified'] + (int)$data['access_expire_seconds'])) {
							$isExpired = true;
						} //end if
					} //end if
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'display-record.mtpl.htm',
						[
							'THE-TITLE' 			=> (string) $title,
							'DATE-NOW' 				=> (string) date('Y-m-d H:i:s O'),
							'ACTION-GET-TOKEN' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=get-the-access-token&id='.Smart::escape_url((string)$id),
							'ACTION-REFRESH-TOKEN' 	=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=refresh-token&id='.Smart::escape_url((string)$id),
							'ACTION-REINIT-TOKEN' 	=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=reinit-token-form&id='.Smart::escape_url((string)$id),
							'ACTION-DELETE-TOKEN' 	=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=delete-token&id='.Smart::escape_url((string)$id),
							'HAVE-REFRESH-TOKEN' 	=> (string) (($haveRefreshToken === true) ? 'yes' : 'no'),
							'IS-EXPIRING' 			=> (string) (($isExpiringToken === true) ? 'yes' : 'no'), // {{{SYNC-TOKEN-NON-EXPIRING-TEST}}}
							'IS-EXPIRED' 			=> (string) (($isExpired === true) ? 'yes' : 'no'),
							'IS-ACTIVE' 			=> (string) (((int)$data['active'] == 1) ? 'yes' : 'no'),
							'IS-JWT' 				=> (string) ((strpos((string)trim((string)$data['access_token']), '[') === 0) && (strpos((string)$data['access_token'], '"JWT:signature":') !== false)) ? 'yes' : 'no', // if already decoded and is JWT should start with `[` because is JSON array
							'DATA-ARR' 				=> (array)  $data,
						]
					)
				]);
				//--
				break;

			case 'refresh-token': // Refresh the Token for an API (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				$id = (string) trim((string)$id);
				if((string)$id == '') {
					$this->PageViewSetCfg('error', 'ID is Empty');
					return 400;
				} //end if
				//--
				$upd = (array) \SmartModExtLib\Oauth2\Oauth2Api::updateApiAccessToken((string)$id);
				if((int)Smart::array_size($upd) > 0) {
					$result = 'OK';
					$img = 'lib/framework/img/sign-ok.svg';
				} else {
					$result = 'FAILED';
					$img = 'lib/framework/img/sign-warn.svg';
				} //end if else
				//--
				$title = 'Refreshing the Access Token for OAuth2 API';
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => '<h1 style="color:#666699;!important">'.Smart::escape_html((string)$title).'</h1><h2>'.Smart::escape_html((string)$id).'</h2><div style="font-size:2rem; font-weight:bold;">Status: ['.Smart::escape_html((string)$result).' ]<br><img width="96" height="96" src="'.Smart::escape_html((string)$img).'"></div><div><br><br><img src="lib/framework/img/loading-spin.svg" width="48" height="48"></div>'.
					'<script>smartJ$Browser.RefreshParent();</script>'.
					'<script>setTimeout(function(){ self.location=\''.Smart::escape_js((string)$this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=view-data&id='.Smart::escape_url((string)$id)).'\'; }, 3000);</script>'
				]);
				//--
				break;

			case 'get-the-access-token': // Get the Access Token and If Expired will Update for an API (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				$id = (string) trim((string)$id);
				if((string)$id == '') {
					$this->PageViewSetCfg('error', 'ID is Empty');
					return 400;
				} //end if
				//--
				$data = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiData((string)$id);
				if((int)Smart::array_size($data) <= 0) {
					$this->PageViewSetCfg('error', 'ID Not Found');
					return 400;
				} //end if
				//--
				$token = (string) $data['access_token']; // first, get what is in the DB
				//--
				$isExpiringToken = true;
				if( // {{{SYNC-TOKEN-NON-EXPIRING-TEST}}}
					((string)trim((string)$data['refresh_token']) == '') // if there is no refresh token found, cannot update
					OR
					((int)$data['access_expire_seconds'] <= 0) // if expiring seconds is not greater than zero it means also does not expires ; test the `access_expire_seconds` (provided by OAuth2 answer) instead of `access_expire_time` (calculated only)
				) {
					$isExpiringToken = false;
				} //end if
				//--
				$active = (int) (string) trim((string)($data['active'] ?? null));
				if((int)$active == 1) { // only if it is active ; inactive tokens will get an empty token when using the below method
					if($isExpiringToken === true) { // if it is expiring token, may need to get a fresh one, use the below method, if expired, will get a new one
						$token = (string) \SmartModExtLib\Oauth2\Oauth2Api::getApiAccessToken((string)$data['id']); // this is neede before the netx line as it will do refresh token if expired
					} //end if
				} //end if
				$errs = (int) (string) trim((string)($data['errs'] ?? null));
				if((int)$errs > 0) {
					$result = 'ERROR';
					$img = 'lib/framework/img/sign-crit-error.svg';
				} else {
					if((string)$token != '') {
						if((int)$active == 1) {
							$result = 'OK';
							$img = 'lib/framework/img/sign-ok.svg';
						} else {
							$result = 'INACTIVE';
							$img = 'lib/framework/img/sign-notice.svg';
						} //end if else
					} else {
						$result = 'FAILED';
						$img = 'lib/framework/img/sign-warn.svg';
					} //end if else
				} //end if else
				//--
				$jwtPretty = '';
				if(SmartAuth::jwt_valid_format((string)$token) === true) {
					$jwtPretty = (string) SmartAuth::jwt_token_display((string)$token, true);
					if((string)trim((string)$jwtPretty) == '') {
						$jwtPretty = '';
					} //end if
				} //end if
				//--
				$title = 'OAuth2 Access Token';
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'display-token.mtpl.htm',
						[
							'URL-BACK' 	=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=view-data&id='.Smart::escape_url((string)$id),
							'TITLE' 	=> (string) $title,
							'ID' 		=> (string) $id,
							'STATUS' 	=> (string) $result,
							'IMG' 		=> (string) $img,
							'TOKEN' 	=> (string) $token,
							'JWT' 		=> (string) $jwtPretty,
							'HTML-HLJS' => (string) SmartViewHtmlHelpers::html_jsload_hilitecodesyntax('body', 'light'),
						]
					)
				]);
				//--
				break;

			case 'delete-token': // Delete the Token for an API (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = $this->RequestVarGet('id', '', 'string');
				$id = (string) trim((string)$id);
				if((string)$id == '') {
					$this->PageViewSetCfg('error', 'ID is Empty');
					return 400;
				} //end if
				//--
				$del = (int) \SmartModExtLib\Oauth2\Oauth2Api::deleteApiAccessToken((string)$id);
				if((int)$del == 1) {
					$result = 'OK';
				} else {
					$result = 'FAILED';
				} //end if else
				//--
				$title = 'Deleting the OAuth2 API';
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => '<h1 style="color:#FF3300;!important">'.Smart::escape_html((string)$title).'</h1><h2>'.Smart::escape_html((string)$id).'</h2><h3>[ '.Smart::escape_html((string)$result).' ]</h3><br><div><center><img src="lib/framework/img/loading-spin.svg" width="64" height="64"></center></div>'.
					'<script>smartJ$Browser.RefreshParent();</script>'.
					'<script>smartJ$Browser.CloseDelayedModalPopUp();</script>'
				]);
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
				switch((string)$column) {
					case 'active':
						//--
						$upd = (int) \SmartModExtLib\Oauth2\Oauth2Api::updateApiStatus((string)$id, (string)$value);
						//--
						if((int)$upd == 1) {
							$status = 'OK';
							$message = 'Status ['.ucfirst((string)$column).'] updated';
						} else {
							$message = 'FAILED to update Status ['.ucfirst((string)$column).']';
						} //end if else
						//--
						break;
					case 'description':
						//--
						$upd = (int) \SmartModExtLib\Oauth2\Oauth2Api::updateApiDesc((string)$id, (string)$value);
						//--
						if((int)$upd == 1) {
							$status = 'OK';
							$message = 'Status ['.ucfirst((string)$column).'] updated';
						} else {
							$message = 'FAILED to update Status ['.ucfirst((string)$column).']';
						} //end if else
						//--
						break;
					default:
						$message = 'Data column is not editable: '.$column;
				} //end switch
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
				$model = new \SmartModDataModel\Oauth2\SqOauth2(); // open connection
				$data['totalRows'] = $model->countByFilter($id);
				$data['rowsList'] = $model->getListByFilter([], $data['itemsPerPage'], $ofs, $sortby, $sortdir, $id);
				unset($model); // close connection
				//--
				$this->PageViewSetVar(
					'main', Smart::json_encode((array)$data)
				);
				//--
				break;

			case '': // list: display the grid (OUTPUTS: HTML)
				//--
				$this->PageViewSetVars([
					'title' => 'OAuth2 Manager',
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'list-records.mtpl.htm',
						[
							'RELEASE-HASH' 		=> (string) $this->ControllerGetParam('release-hash'),
							'CURRENT-SCRIPT' 	=> (string) $this->ControllerGetParam('url-script'),
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'AREAS' 			=> (array)  \SmartModExtLib\AuthAdmins\AuthNameSpaces::GetNameSpaces(),
							'CRR-TIME' 			=> (int)    time(),
						]
					)
				]);
				//--
				break;

			//------- DEFAULT

			default: // other invalid actions
				//--
				$this->PageViewSetCfg('error', 'OAuth2 Client Management :: Invalid Action `'.$action.'` ...');
				//--
				return 400;
				//--

		} // end switch

	} //END FUNCTION


} //END CLASS


// end of php code
