<?php
// Controller: AuthAdmins/Login
// Route: task|admin.php?page=auth-admins.login.stml
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // ADMIN or TASK
define('SMART_APP_MODULE_AUTH', true);

// [PHP8]

/**
 * Abstract Admin Login Controller
 * @ignore
 */
abstract class AbstractAdminLoginsController extends SmartAbstractAppController {

	// v.20240115


	public function Initialize() {
		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML/JSON)

		//-- {{{SYNC-AUTH-ADMINS-PRE-CHECKS}}}
		if(SmartAuth::check_login() !== true) {
			$this->PageViewSetCfg('error', 'Auth Admins Login Requires Authentication ! ...');
			return 403;
		} //end if
		//--
		if(!SmartEnvironment::isAdminArea()) { // allow: adm/tsk
			$this->PageViewSetCfg('error', 'Auth Admins Login Manager is allowed to run under `Admin` or Task areas only ! ...');
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
		// DO NOT CHECK THE 'account' RESTRICTIONS HERE, THIS IS PROVIDED FOR LOGIN AUTH ONLY AND IS REQUIRED IN ALL SCENARIOS
		//--

		//-- use defaults
		$this->PageViewSetCfg('template-file', 'template.htm');
		//--

		//--
		$action = $this->RequestVarGet('action', '', 'string');
		//--

		switch((string)$action) {

			//------- LOGIN OPS

			case 'check': // login check URL ; outputs: JSON ; {{{SYNC-ADM-AUTH-REDIRECT-ON-LOGIN}}}
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$url = $this->RequestVarGet('url', '', 'string');
				$url = (string) $this->decryptUrl((string)$url); // {{{SYNC-AUTH-LOGIN-URLS-ENC/DEC}}}
				//--
				$area = (string) strtolower((string)$this->ControllerGetParam('app-realm'));
				//--
				if(strpos((string)$url, (string)'#!'.$area.'/DISPLAY-REALMS/') === 0) {
					//--
					$arr_login_namespaces = (array) \SmartModExtLib\AuthAdmins\AuthNameSpaces::GetNameSpaces();
					//--
					$this->PageViewSetVar(
						'main',
						(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
							'OK',
							'Login Check Successful ...',
							(string) Smart::escape_html((string)date('Y-m-d H:i:s O')),
							'',
							'auth-admins-area-login',
							(string) SmartMarkersTemplating::render_file_template(
								'modules/mod-auth-admins/libs/templates/auth-admins-handler/realms.inc.htm',
								[
									'AUTH-ID' 		=> (string) SmartAuth::get_auth_id(),
									'AUTH-USERNAME' => (string) SmartAuth::get_auth_username(),
									'LOGIN-AREA' 	=> (string) $this->ControllerGetParam('module-area'),
									'AREA' 			=> (string) $area,
									'LOGIN-NSPACES' => (array)  $arr_login_namespaces,
								]
							)
						)
					);
				} else {
					$this->PageViewSetVar(
						'main',
						(string) SmartViewHtmlHelpers::js_ajax_replyto_html_form(
							'WARNING',
							'You are logged in',
							'<b>Invalid Login URL Provided !</b><br>Login Check Successful ...<br>'.Smart::escape_html((string)date('Y-m-d H:i:s O')),
							(string) $this->ControllerGetParam('url-script')
						)
					);
				} //end if else
				//--
				break;

			case 'redir': // if the ajax reply of above login URL fails, will redirect back to this URL ; outputs: HTML ; {{{SYNC-ADM-AUTH-REDIRECT-ON-LOGIN}}}
				//--
				$url = $this->RequestVarGet('url', '', 'string');
				$redirect = (string) $this->decryptUrl((string)$url); // {{{SYNC-AUTH-LOGIN-URLS-ENC/DEC}}}
				//--
				if((string)$redirect == '') {
					$redirect = (string) $this->ControllerGetParam('url-script');
				} elseif(strpos((string)$redirect, '#') === 0) { // fix: if it is anchor, prefix with the current script !
					$redirect = (string) $this->ControllerGetParam('url-script').$redirect;
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => 'Redirecting Back',
					'main' 	=> (string) '<br><center><img width="48" height="48" title="Redirecting..." alt="Redirecting..." src="data:image/svg+xml,'.Smart::escape_url((string)SmartFileSysUtils::readStaticFile('lib/framework/img/loading-cylon.svg')).'"></center>'."\n".
										'<script>setTimeout(() => { self.location = \''.Smart::escape_js((string)$redirect).'\'; }, 1250);</script>'
				]);
				//--
				break;

			//------- DEFAULT

			default: // other invalid actions
				//--
				$this->PageViewSetCfg('error', 'Auth Admins Login Invalid Action `'.$action.'` ...');
				//--
				return 400;
				//--

		} // end switch


	} //END FUNCTION


	private function decryptUrl(string $url) : string {
		//--
		return (string) trim((string)SmartCipherCrypto::tf_decrypt((string)$url, '', true)); // TF / TF+BF / BF (fallback:true) ; default key
		//--
	} //END FUNCTION


} //END CLASS


/**
 * Admin Login Controller
 * @ignore
 */
final class SmartAppAdminController extends AbstractAdminLoginsController {
} //END CLASS

/**
 * Task Admin Login Controller
 * @ignore
 */
final class SmartAppTaskController extends AbstractAdminLoginsController {
} //END CLASS


//end of php code
