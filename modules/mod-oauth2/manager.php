<?php
// Controller: OAuth2 Client Manager
// Route: ?page=oauth2.manager.stml
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // INDEX, ADMIN, SHARED
define('SMART_APP_MODULE_AUTH', true);


final class SmartAppIndexController extends AbstractController {}

final class SmartAppTaskController  extends AbstractController {}

final class SmartAppAdminController extends AbstractController {}


abstract class AbstractController extends SmartAbstractAppController {

	// v.20250711

	private ?object $translator = null;

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

		//--
		if(SmartAuth::test_login_privilege('oauth2') !== true) { // PRIVILEGES
			$this->PageViewSetCfg('error', 'OAuth2 Manager requires the following privilege: `oauth2` ...');
			return 403;
		} //end if
		//--
		if(SmartAuth::test_login_restriction('readonly') === true) { // RESTRICTIONS
			$this->PageViewSetCfg('error', 'OAuth2 Manager is unavailable for the following restriction: `readonly` ...');
			return 403;
		} //end if
		if(SmartAuth::test_login_restriction('virtual') === true) { // RESTRICTIONS
			$this->PageViewSetCfg('error', 'OAuth2 Manager is unavailable for the following restriction: `virtual` ...');
			return 403;
		} //end if
		//--

		//--
		if(SmartAuth::is_cluster_current_workspace() !== true) {
			$this->PageViewSetCfg('error', 'OAuth2 Manager is unavailable outside of your User Account Clustered WorkSpace ...');
			return 502;
		} //end if
		//--

		//--
		if($this->translator === null) {
			$this->translator = SmartTextTranslations::getTranslator('mod-oauth2', 'oauth2');
		} //end if
		//--

		//--
		$homeLink = (string) $this->ControllerGetParam('url-script');
		$arrNameSpaces = [];
		//--
		if(SmartEnvironment::isAdminArea() === true) { // allow: adm/tsk ; but this controller does not extends in a task controller
			//--
			if(SmartEnvironment::isTaskArea() !== false) {
				$this->PageViewSetCfg('error', 'OAuth2 Manager cannot run under Admin Task Area !');
				return 502;
			} //end if
			//--
			$arrNameSpaces = (array) \SmartModExtLib\AuthAdmins\AuthNameSpaces::GetNameSpaces();
			//--
		} else { // idx
			if(SmartAppInfo::TestIfModuleExists('mod-auth-users')) {
				//--
				if(class_exists('\\SmartModExtLib\\AuthUsers\\Utils')) {
					$homeLink = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_APPS;
				} //end if
				//--
				if(method_exists('\\SmartModExtLib\\AuthUsers\\AuthNameSpaces', 'GetNameSpaces')) {
					$arrNameSpaces = (array) \SmartModExtLib\AuthUsers\AuthNameSpaces::GetNameSpaces();
				} //end if
				//--
			} //end if
		} //end if else
		//--

		//--
		$action = (string) $this->RequestVarGet('action', '', 'string');
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
				$template = (string) trim((string)$this->RequestVarGet('template', '', 'string'));
				//--
				$data = [];
				if((string)$template != '') {
					$data = (array) $this->readYamlTemplate((string)$template);
				} //end if
				if(SmartAuth::test_login_restriction('oauth2:template') === true) { // with this restriction, allow use just from templates
					if(((string)$template == '') OR ((int)Smart::array_size($data) <= 0)) {
						$this->PageViewSetCfg('error', 'OAuth2 Manager is unavailable outside of pre-defined templates ...');
						return 403;
					} //end if
				} //end if
				//--
				if(!isset($data['url_redirect']) OR empty($data['url_redirect'])) { // {{{SYNC-OAUTH-API-EMPTY-REDIR-URL}}}
					$data['url_redirect'] = (string) $this->ControllerGetParam('url-addr').'index.php/page/oauth2.get-code/';
				} //end if
				//--
				$url_cancel = '';
				if(isset($data['template_redir']) && !empty($data['template_redir'])) {
					$url_cancel = (string) ($data['template_redir'] ?? null);
				} //end if
				//--
				$csrfPrivKey = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfNewPrivateKey().'#OAuth2/AppID:'.'[NEW]';
				$csrfPubKey  = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPublicKey((string)$csrfPrivKey);
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$this->PageViewSetVars([
					'title' => (string) $this->translator->text('ttl-new'),
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'form-record.mtpl.htm',
						[ // this will need an intermediary step, because App-Id is unknown at this step
							//--
							'PATTERN-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_PATTERN_VALID_ID,
							'REGEX-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_REGEX_VALID_ID,
							'DEFAULT-REDIRECT-URL' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_STANDALONE_REFRESH_URL, // {{{SYNC-OAUTH2-DEFAULT-REDIRECT-URL}}}
							'ACTIONS-URL' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=new-add',
							'TPL-AUTH-URL-PARAMS' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_PARAMS),
							'TPL-AUTH-URL-CHPART' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_CHPART),
							'IS-EDIT-FORM' 			=> (string) 'no',
							'FORM-TEMPLATE' 		=> (string) $template,
							'FORM-NAME' 			=> (string) ($data['description'] ?? null),
							'FORM-IMG' 				=> (string) ($data['logo'] ?? null),
							'FORM-URL-CANCEL' 		=> (string) $url_cancel,
							'FORM-ID' 				=> (string) ($data['id'] ?? null),
							'FORM-DESC' 			=> (string) ($data['description'] ?? null),
							'FORM-CLI-ID' 			=> (string) ($data['client_id'] ?? null),
							'FORM-CLI-SECRET' 		=> (string) ($data['client_secret'] ?? null),
							'FORM-SCOPE' 			=> (string) ($data['scope'] ?? null),
							'FORM-URL-REDIR' 		=> (string) ($data['url_redirect'] ?? null),
							'FORM-URL-AUTH' 		=> (string) ($data['url_auth'] ?? null),
							'FORM-URL-TOKEN' 		=> (string) ($data['url_token'] ?? null),
							'FORM-CODE' 			=> (string) '',
							'COOKIE-NAME-CSRF' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF, // {{{SYNC-OAUTH2-COOKIE-NAME-CSRF}}}
							'COOKIE-VALUE-CSRF' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPrivateKeyEncrypt((string)$csrfPrivKey),
							'STATE-CSRF' 			=> (string) $csrfPubKey,
							//--
							'LANG' 					=> (string) SmartTextTranslations::getLanguage(),
							//--
							'FORM-TTL-NEW' 			=> (string) $this->translator->text('ttl-form-new'),
							'FORM-TTL-REINIT' 		=> (string) $this->translator->text('ttl-form-reinit'),
							'BTN-LBL-CANCEL' 		=> (string) $this->translator->text('btn-cancel'),
							'LABEL-ID' 				=> (string) $this->translator->text('db-fld-id'),
							'LABEL-DESCRIPTION' 	=> (string) $this->translator->text('db-fld-description'),
							'LABEL-CLIENT-ID' 		=> (string) $this->translator->text('db-fld-client_id'),
							'LABEL-CLIENT-SECRET' 	=> (string) $this->translator->text('db-fld-client_secret'),
							'LABEL-SCOPE' 			=> (string) $this->translator->text('db-fld-scope'),
							'LABEL-REDIRECT-URL' 	=> (string) $this->translator->text('db-fld-url_redirect'),
							'LABEL-O2-URL-AUTH' 	=> (string) $this->translator->text('db-fld-url_auth'),
							'LABEL-O2-URL-TOKEN' 	=> (string) $this->translator->text('db-fld-url_token'),
							'LABEL-O2-CODE' 		=> (string) $this->translator->text('db-fld-code'),
							//--
							'HINT-ID' 				=> (string) $this->translator->text('hint-frm-id'),
							'HINT-DESCRIPTION' 		=> (string) $this->translator->text('hint-frm-description'),
							'HINT-CLIENT-ID' 		=> (string) $this->translator->text('hint-frm-client_id'),
							'HINT-CLIENT-SECRET' 	=> (string) $this->translator->text('hint-frm-client_secret'),
							'HINT-SCOPE' 			=> (string) $this->translator->text('hint-frm-scope'),
							'HINT-REDIRECT-URL' 	=> (string) $this->translator->text('hint-frm-url_redirect'),
							'HINT-O2-URL-AUTH' 		=> (string) $this->translator->text('hint-frm-url_auth'),
							'HINT-O2-URL-TOKEN' 	=> (string) $this->translator->text('hint-frm-url_token'),
							'HINT-O2-CODE' 			=> (string) $this->translator->text('hint-frm-code'),
							//--
							'TXT-EXAMPLE' 			=> (string) $this->translator->text('example'),
							'TXT-VALID-PATERN' 		=> (string) $this->translator->text('validation-pattern'),
							'TXT-VALID-REGEX' 		=> (string) $this->translator->text('validation-regex'),
							//--
						]
					)
				]);
				//--
				break;

			case 'new-add': // Do Add new Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$template = (string) trim((string)$this->RequestVarGet('template', '', 'string'));
				$data = $this->RequestVarGet('frm', [], 'array');
				$code = (string) trim((string)($data['code'] ?? null));
				$rdr  = (string) trim((string)($data['url_redirect'] ?? null));
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if(SmartAuth::test_login_restriction('oauth2:template') === true) { // with this restriction, allow use just from templates
					$data = [];
				} //end if
				if((string)$template != '') {
					$data = (array) $this->readYamlTemplate((string)$template);
					$data['url_redirect'] = (string) $rdr;
					$data['code'] = (string) $code;
				} //end if
				//--
				$test = \SmartModExtLib\Oauth2\Oauth2Api::initApiData((array)$data); // mixed
				if(is_array($test)) {
					$status = 'OK';
					$message = (string) Smart::escape_html((string)$this->translator->text('api-init-ok'));
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal';
					if(isset($data['template_redir']) && !empty($data['template_redir'])) {
						$redirect = (string) $data['template_redir'];
					} //end if
				} else {
					$status = 'ERROR';
					$message = (string) Smart::escape_html((string)$this->translator->text('api-init-err')). ':'.'<br>'.Smart::nl_2_br((string)Smart::escape_html((string)$test));
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						(string) $this->translator->text('api-init'),
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
				$template = (string) trim((string)$this->RequestVarGet('template', '', 'string'));
				$data = $this->RequestVarGet('frm', [], 'array');
				//--
				$id   = (string) trim((string)($data['id'] ?? null));
				$code = (string) trim((string)($data['code'] ?? null));
				//--
				$data = [];
				if((string)$template != '') {
					$data = (array) $this->readYamlTemplate((string)$template);
					if((string)($data['id'] ?? null) !== (string)$id) {
						$data = [];
					} else {
						$dbData = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiData((string)$id);
						if((string)($dbData['id'] ?? null) !== (string)$id) {
							$data = [];
						} else {
							if(!isset($data['url_redirect']) OR empty($data['url_redirect'])) { // {{{SYNC-OAUTH-API-EMPTY-REDIR-URL}}}
								$data['url_redirect'] = (string) $this->ControllerGetParam('url-addr').'index.php/page/oauth2.get-code/';
							} //end if
						} //end if
					} //end if
				} else {
					$data = (array) \SmartModExtLib\Oauth2\Oauth2Api::getApiData((string)$id);
				} //end if
				$data['code'] = (string) $code;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				$test = \SmartModExtLib\Oauth2\Oauth2Api::initApiData((array)$data, true); // mixed ; RE-INIT is TRUE
				if(is_array($test)) {
					$status = 'OK';
					$message = (string) Smart::escape_html((string)$this->translator->text('api-reinit-ok'));
					$redirect = (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=close-modal';
					if(isset($data['template_redir']) && !empty($data['template_redir'])) {
						$redirect = (string) $data['template_redir'];
					} //end if
				} else {
					$status = 'ERROR';
					$message = (string) Smart::escape_html((string)$this->translator->text('api-reinit-err')). ':'.'<br>'.Smart::nl_2_br((string)Smart::escape_html((string)$test));
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						(string) $this->translator->text('api-reinit'),
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
				$template = (string) trim((string)$this->RequestVarGet('template', '', 'string'));
				//--
				$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
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
				if(SmartAuth::test_login_restriction('oauth2:template') === true) { // with this restriction, allow use just from templates
					if(((string)$template == '') OR ((int)Smart::array_size($data) <= 0)) {
						$this->PageViewSetCfg('error', 'OAuth2 Manager is unavailable outside of pre-defined templates ...');
						return 403;
					} //end if
				} //end if
				$url_cancel = 'admin.php?page=oauth2.manager&action=view-data&id='.Smart::escape_url((string)($data['id'] ?? null));
				if((string)$template != '') {
					$tmpData = (array) $this->readYamlTemplate((string)$template);
					if(((int)Smart::array_size($tmpData) <= 0) OR ((string)($tmpData['id'] ?? null) !== (string)$id)) {
						$this->PageViewSetCfg('error', 'OAuth2 Manager template mismatch ...');
						return 400;
					} //end if
					$data['logo'] = (string) ($tmpData['logo'] ?? null);
					if(isset($tmpData['template_redir']) && !empty($tmpData['template_redir'])) {
						$url_cancel = (string) ($tmpData['template_redir'] ?? null);
					} //end if
				} //end if
				//--
				$csrfPrivKey = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfNewPrivateKey().'#OAuth2/AppID:'.($data['id'] ?? null);
				$csrfPubKey  = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPublicKey((string)$csrfPrivKey);
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$this->PageViewSetVars([
					'title' => (string) $this->translator->text('ttl-reinit'),
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'form-record.mtpl.htm',
						[
							//--
							'PATTERN-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_PATTERN_VALID_ID,
							'REGEX-VALID-ID' 		=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_REGEX_VALID_ID,
							'DEFAULT-REDIRECT-URL' 	=> (string) \SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_STANDALONE_REFRESH_URL, // {{{SYNC-OAUTH2-DEFAULT-REDIRECT-URL}}}
							'ACTIONS-URL' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=reinit-update',
							'TPL-AUTH-URL-PARAMS' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_PARAMS),
							'TPL-AUTH-URL-CHPART' 	=> (string) SmartMarkersTemplating::escape_template((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_AUTHORIZE_URL_CHPART),
							'IS-EDIT-FORM' 			=> (string) 'yes',
							'FORM-TEMPLATE' 		=> (string) $template,
							'FORM-NAME' 			=> (string) ($data['description'] ?? null),
							'FORM-IMG' 				=> (string) ($data['logo'] ?? null),
							'FORM-URL-CANCEL' 		=> (string) $url_cancel,
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
							//--
							'LANG' 					=> (string) SmartTextTranslations::getLanguage(),
							//--
							'FORM-TTL-NEW' 			=> (string) $this->translator->text('ttl-form-new'),
							'FORM-TTL-REINIT' 		=> (string) $this->translator->text('ttl-form-reinit'),
							'BTN-LBL-CANCEL' 		=> (string) $this->translator->text('btn-cancel'),
							'LABEL-ID' 				=> (string) $this->translator->text('db-fld-id'),
							'LABEL-DESCRIPTION' 	=> (string) $this->translator->text('db-fld-description'),
							'LABEL-CLIENT-ID' 		=> (string) $this->translator->text('db-fld-client_id'),
							'LABEL-CLIENT-SECRET' 	=> (string) $this->translator->text('db-fld-client_secret'),
							'LABEL-SCOPE' 			=> (string) $this->translator->text('db-fld-scope'),
							'LABEL-REDIRECT-URL' 	=> (string) $this->translator->text('db-fld-url_redirect'),
							'LABEL-O2-URL-AUTH' 	=> (string) $this->translator->text('db-fld-url_auth'),
							'LABEL-O2-URL-TOKEN' 	=> (string) $this->translator->text('db-fld-url_token'),
							'LABEL-O2-CODE' 		=> (string) $this->translator->text('db-fld-code'),
							//--
							'HINT-ID' 				=> (string) $this->translator->text('hint-frm-id'),
							'HINT-DESCRIPTION' 		=> (string) $this->translator->text('hint-frm-description'),
							'HINT-CLIENT-ID' 		=> (string) $this->translator->text('hint-frm-client_id'),
							'HINT-CLIENT-SECRET' 	=> (string) $this->translator->text('hint-frm-client_secret'),
							'HINT-SCOPE' 			=> (string) $this->translator->text('hint-frm-scope'),
							'HINT-REDIRECT-URL' 	=> (string) $this->translator->text('hint-frm-url_redirect'),
							'HINT-O2-URL-AUTH' 		=> (string) $this->translator->text('hint-frm-url_auth'),
							'HINT-O2-URL-TOKEN' 	=> (string) $this->translator->text('hint-frm-url_token'),
							'HINT-O2-CODE' 			=> (string) $this->translator->text('hint-frm-code'),
							//--
							'TXT-EXAMPLE' 			=> (string) $this->translator->text('example'),
							'TXT-VALID-PATERN' 		=> (string) $this->translator->text('validation-pattern'),
							'TXT-VALID-REGEX' 		=> (string) $this->translator->text('validation-regex'),
							//--
						]
					)
				]);
				//--
				break;

			case 'view-data': // Form for Display Record (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
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
				if(SmartAuth::test_login_restriction('oauth2:template') === true) {
					$this->PageViewSetCfg('error', 'OAuth2 Manager is available only with pre-defined templates ...');
					return 403;
				} //end if
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
				$dataTranslations = [];
				foreach($data as $key => $val) {
					$dataTranslations[(string)$key] = (string) $this->translator->text('db-fld-'.$key);
				} //end foreach
				//--
				$title = (string) $this->translator->text('display-ttl');
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'display-record.mtpl.htm',
						[
							//--
							'THE-TITLE' 				=> (string) $title,
							//--
							'BTN-GO-BACK' 				=> (string) $this->translator->text('btn-go-back'),
							'BTN-TK-DISPLAY' 			=> (string) $this->translator->text('display-btn-token'),
							'BTN-TK-DISPLAY-EXP' 		=> (string) $this->translator->text('display-btn-token-exp'),
							'BTN-TK-DISPLAY-HINT1' 		=> (string) $this->translator->text('display-btn-token-hint1'),
							'BTN-TK-DISPLAY-HINT2' 		=> (string) $this->translator->text('display-btn-token-hint2'),
							'TXT-TK-VALID' 				=> (string) $this->translator->text('display-tk-valid'),
							'TXT-TK-EXPIRED' 			=> (string) $this->translator->text('display-tk-expired'),
							'TXT-BTN-REAUTH' 			=> (string) $this->translator->text('display-btn-reauth'),
							'TXT-REAUTH' 				=> (string) $this->translator->text('display-reauth'),
							'TXT-ONLY-ACTIVE-REAUTH' 	=> (string) $this->translator->text('display-only-active-reauth'),
							'TXT-SECRET' 				=> (string) $this->translator->text('display-secret'),
							'TXT-SIZE' 					=> (string) $this->translator->text('display-size'),
							'TXT-TK-NO-REFRESH' 		=> (string) $this->translator->text('display-tk-no-refresh'),
							'TXT-ID-JWT' 				=> (string) $this->translator->text('display-id-jwt'),
							'TXT-ID-JWT-NONE' 			=> (string) $this->translator->text('display-id-jwt-none'),
							'TXT-REFRESH-QUESTION' 		=> (string) $this->translator->text('display-refresh-question'),
							'TXT-REFRESH-HINT' 			=> (string) $this->translator->text('display-refresh-hint'),
							'TXT-REFRESH-TITLE' 		=> (string) $this->translator->text('display-refresh-ttl'),
							'TXT-REFRESH-CANNOT' 		=> (string) $this->translator->text('display-refresh-cannot'),
							'TXT-REFRESH-CANNOT-HINT' 	=> (string) $this->translator->text('display-refresh-cannot-hint'),
							'TXT-REFRESH-EXPLAIN' 		=> (string) $this->translator->text('display-refresh-explain'),
							'TXT-REFRESH-BTN' 			=> (string) $this->translator->text('display-refresh-btn'),
							'TXT-DELETE-QUESTION' 		=> (string) $this->translator->text('display-delete-question'),
							'TXT-DELETE-HINT' 			=> (string) $this->translator->text('display-delete-hint'),
							'TXT-DELETE-TITLE' 			=> (string) $this->translator->text('display-delete-ttl'),
							'TXT-DELETE-CANNOT' 		=> (string) $this->translator->text('display-delete-cannot'),
							'TXT-DELETE-CANNOT-HINT' 	=> (string) $this->translator->text('display-delete-cannot-hint'),
							'TXT-DELETE-BTN' 			=> (string) $this->translator->text('display-delete-btn'),
							//--
							'TXT-NOTICE' 				=> (string) $this->translator->text('display-notice'),
							'TXT-EMPTY-VALUE' 			=> (string) $this->translator->text('display-empty-value'),
							//--
							'TXT-YES' 					=> (string) $this->translator->text('yes'),
							'TXT-NO' 					=> (string) $this->translator->text('no'),
							//--
							'DATE-NOW' 					=> (string) date('Y-m-d H:i:s O'),
							'ACTION-GET-TOKEN' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=get-the-access-token&id='.Smart::escape_url((string)$id),
							'ACTION-REFRESH-TOKEN' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=refresh-token&id='.Smart::escape_url((string)$id),
							'ACTION-REINIT-TOKEN' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=reinit-token-form&id='.Smart::escape_url((string)$id),
							'ACTION-DELETE-TOKEN' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=delete-token&id='.Smart::escape_url((string)$id),
							'HAVE-REFRESH-TOKEN' 		=> (string) (($haveRefreshToken === true) ? 'yes' : 'no'),
							'IS-EXPIRING' 				=> (string) (($isExpiringToken === true) ? 'yes' : 'no'), // {{{SYNC-TOKEN-NON-EXPIRING-TEST}}}
							'IS-EXPIRED' 				=> (string) (($isExpired === true) ? 'yes' : 'no'),
							'IS-ACTIVE' 				=> (string) (((int)$data['active'] == 1) ? 'yes' : 'no'),
							'IS-JWT' 					=> (string) ((strpos((string)trim((string)$data['access_token']), '[') === 0) && (strpos((string)$data['access_token'], '"JWT:signature":') !== false)) ? 'yes' : 'no', // if already decoded and is JWT should start with `[` because is JSON array
							'DATA-ARR' 					=> (array)  $data,
							'DATA-ARR:(TRANSLATIONS)' 	=> (array)  $dataTranslations,
							//--
						]
					)
				]);
				//--
				break;

			case 'refresh-token': // Refresh the Token for an API (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
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
				$title = (string) $this->translator->text('api-token-refresh');
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
				$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
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
				$title = (string) $this->translator->text('token-display-ttl');
				//--
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'display-token.mtpl.htm',
						[
							//--
							'URL-BACK' 			=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=view-data&id='.Smart::escape_url((string)$id),
							//--
							'TITLE' 			=> (string) $title,
							'ID' 				=> (string) $id,
							'STATUS' 			=> (string) $result,
							'IMG' 				=> (string) $img,
							'TOKEN' 			=> (string) $token,
							'JWT' 				=> (string) $jwtPretty,
							//--
							'TXT-STATUS' 		=> (string) $this->translator->text('token-status'),
							'TXT-ID' 			=> (string) $this->translator->text('token-id'),
							'TXT-TOKEN' 		=> (string) $this->translator->text('token-label'),
							'TXT-CLIPBOARD' 	=> (string) $this->translator->text('copy-to-clipboard'),
							'TXT-CLIPBOARD-OK' 	=> (string) $this->translator->text('copy-to-clipboard-ok'),
							'TXT-CLIPBOARD-ERR' => (string) $this->translator->text('copy-to-clipboard-err'),
							'TXT-GO-BACK' 		=> (string) $this->translator->text('btn-go-back'),
							//--
							'HTML-HLJS' 		=> (string) SmartViewHtmlHelpers::html_jsload_hilitecodesyntax('body', 'light'),
							//--
						]
					)
				]);
				//--
				break;

			case 'delete-token': // Delete the Token for an API (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$id = (string) trim((string)$this->RequestVarGet('id', '', 'string'));
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
				$title = (string) $this->translator->text('api-delete');
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
				$column = (string) $this->RequestVarGet('column', '', 'string');
				$value  = (string) $this->RequestVarGet('value', '', 'string');
				$id     = (string) $this->RequestVarGet('id', '', 'string');
				//--
				$prettyColumnName = (string) Smart::create_idtxt((string)$column);
				//--
				$title = (string) $this->translator->text('api-edit').': '.$id; //.' @ '.$value;
				$status = 'ERROR';
				$message = (string) $this->translator->text('api-edit-xx').': '.$prettyColumnName;
				//--
				switch((string)$column) {
					case 'active':
						//--
						$upd = (int) \SmartModExtLib\Oauth2\Oauth2Api::updateApiStatus((string)$id, (string)$value);
						//--
						if((int)$upd == 1) {
							$status = 'OK';
							$message = (string) $this->translator->text('api-edit-ok').': '.$prettyColumnName;
						} else {
							$message = (string) $this->translator->text('api-edit-err').': '.$prettyColumnName;
						} //end if else
						//--
						break;
					case 'description':
						//--
						$upd = (int) \SmartModExtLib\Oauth2\Oauth2Api::updateApiDesc((string)$id, (string)$value);
						//--
						if((int)$upd == 1) {
							$status = 'OK';
							$message = (string) $this->translator->text('api-edit-ok').': '.$prettyColumnName;
						} else {
							$message = (string) $this->translator->text('api-edit-err').': '.$prettyColumnName;
						} //end if else
						//--
						break;
					default:
						// not editable
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
				$ofs      = (int)    $this->RequestVarGet('ofs', 0, 'integer+');
				$sortby   = (string) $this->RequestVarGet('sortby', 'id', 'string');
				$sortdir  = (string) $this->RequestVarGet('sortdir', 'ASC', 'string');
				$sorttype = (string) $this->RequestVarGet('sorttype', 'string', 'string');
				//-- filter vars
				$id       = (string) $this->RequestVarGet('id', '', 'string');
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
				if(SmartAuth::test_login_restriction('oauth2:template') === true) {
					$this->PageViewSetCfg('error', 'OAuth2 Manager is available only with pre-defined templates ...');
					return 403;
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => (string) $this->translator->text('ttl-list'),
					'main' 	=> (string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'list-records.mtpl.htm',
						[
							//--
							'RELEASE-HASH' 		=> (string) $this->ControllerGetParam('release-hash'),
							'CURRENT-SCRIPT' 	=> (string) $this->ControllerGetParam('url-script'),
							'HOME-LINK' 		=> (string) $homeLink,
							'ACTIONS-URL' 		=> (string) $this->ControllerGetParam('url-script').'?page='.Smart::escape_url((string)$this->ControllerGetParam('controller')).'&action=',
							'AREAS' 			=> (array)  $arrNameSpaces,
							'CRR-TIME' 			=> (int)    time(),
							//--
							'TBL-TTL' 			=> (string) $this->translator->text('ttl-list-table'),
							'LABEL-ID' 			=> (string) $this->translator->text('db-fld-id'),
							'LABEL-ACTIVE' 		=> (string) $this->translator->text('db-fld-active'),
							'LABEL-SCOPE' 		=> (string) $this->translator->text('db-fld-scope'),
							'LABEL-DESC' 		=> (string) $this->translator->text('db-fld-description'),
							'LABEL-TK-REFRESH' 	=> (string) $this->translator->text('db-fld-refresh_token'),
							'LABEL-TK-IDJWT' 	=> (string) $this->translator->text('db-fld-id_token'),
							'LABEL-EXP-SEC' 	=> (string) $this->translator->text('db-fld-access_expire_seconds'),
							'LABEL-EXP-TIME' 	=> (string) $this->translator->text('db-fld-access_expire_time'),
							'LABEL-LAST-UPD' 	=> (string) $this->translator->text('db-fld-modified'),
							//--
							'HINT-TBL-OP' 		=> (string) $this->translator->text('hint-tbl-op'),
							'HINT-ID' 			=> (string) $this->translator->text('hint-fld-id'),
							'HINT-STATUS' 		=> (string) $this->translator->text('hint-fld-active'),
							'HINT-EXP-SEC' 		=> (string) $this->translator->text('hint-fld-access_expire_seconds'),
							//--
							'TXT-ERRORS' 		=> (string) $this->translator->text('errors'),
							'TXT-EXPIRED' 		=> (string) $this->translator->text('expired'),
							//--
							'SRC-FRM-FILTER' 	=> (string) $this->translator->text('list-frm-filter'),
							'SRC-FRM-RESET' 	=> (string) $this->translator->text('list-frm-reset'),
							//--
							'DOUBLE-CLICK' 		=> (string) $this->translator->text('double-click'),
							//--
							'COL-STATUS-ON' 	=> (string) $this->translator->text('list-col-status-active'),
							'COL-STATUS-OFF' 	=> (string) $this->translator->text('list-col-status-inactive'),
							'COL-R-TK-TRUE' 	=> (string) $this->translator->text('list-col-refresh-token-true'),
							'COL-R-TK-FALSE' 	=> (string) $this->translator->text('list-col-refresh-token-false'),
							'COL-R-JWTID-TRUE' 	=> (string) $this->translator->text('list-col-jwt-id-true'),
							'COL-R-JWTID-FALSE' => (string) $this->translator->text('list-col-jwt-id-false'),
							//--
							'BTN-CREATE-NEW' 	=> (string) $this->translator->text('ttl-form-new'),
							//--
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


	private function readYamlTemplate(string $template) : array {
		//--
		$template = (string) trim((string)$template);
		//--
		$data = [];
		//--
		if((string)$template != '') {
			if((string)$template == (string)Smart::safe_validname((string)$template)) {
				//--
				$template = (string) Smart::safe_validname((string)$template);
				//--
				if((string)$template != '') {
					//--
					$basePath = (string) 'etc/oauth2/'.$template;
					$template = (string) $basePath.'.yaml';
					$logo     = (string) $basePath.'.svg';
					//--
					if(SmartFileSysUtils::staticFileExists((string)$template) === true) {
						$yaml = (string) SmartFileSysUtils::readStaticFile((string)$template);
						$yaml = (string) trim((string)$yaml);
						if((string)$yaml != '') {
							$yaml = (array) (new SmartYamlConverter())->parse($yaml);
							if((int)Smart::array_size($yaml) > 0) {
								$yaml['oauth2'] = $yaml['oauth2'] ?? null;
								if((int)Smart::array_size($yaml['oauth2']) > 0) {
									$data = (array) $yaml['oauth2'];
									$isDataOk = true;
									foreach($data as $key => $val) {
										if(!Smart::is_nscalar($val)) {
											$isDataOk = false;
											break;
										} //end if
										$data[(string)$key] = (string) trim((string)$val);
									} //end foreach
									if($isDataOk === true) {
										if(SmartFileSysUtils::staticFileExists((string)$logo) === true) {
											$data['logo'] = (string) SmartFileSysUtils::readStaticFile((string)$logo);
											$data['logo'] = (string) trim((string)$data['logo']);
											if((string)$data['logo'] != '') {
												if(strpos($data['logo'], '<svg ') === 0) {
													$data['logo'] = 'data:image/svg+xml,'.Smart::escape_url((string)$data['logo']);
												} else {
													$data['logo'] = '';
												} //end if
											} //end if
										} //end if
									} else {
										$data = [];
									} //end if
								} //end if
							} //end if
						} //end if
						$yaml = null;
					} //end if
					//--
				} //end if
				//--
			} //end if
		} //end if
		//--
		return (array) $data;
		//--
	} //END FUNCTION


} //END CLASS


// end of php code
