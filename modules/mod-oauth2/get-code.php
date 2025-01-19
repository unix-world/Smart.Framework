<?php
// Controller: OAuth2 Manager / GetCode
// Route: ?/page/oauth2.get-code (?page=oauth2.get-code)
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX, ADMIN, SHARED

/**
 * Index Controller
 *
 * @ignore
 * @version v.20250112
 *
 */
final class SmartAppIndexController extends SmartAbstractAppController {

	public function Run() {

		//--
		// Sample return URLs:
		// 		* https://127.0.0.1/sites/smart-framework/?page=oauth2.get-code
		// 		* https://127.0.0.1/sites/smart-framework/index.php/page/oauth2.get-code/
		// The prefered format (wide supported) is: https://127.0.0.1/sites/smart-framework/index.php/page/oauth2.get-code/
		// Expected Params: &code={a-new-code-provided-by-the api}&state={csrf-public-key}
		//--
		// IMPORTANT:
		// 	* this page will be loaded through the OAuth2 proxy and re-displayed
		// 	* thus everything must be inline (no linked page resources)
		//--

		//--
		if(SmartEnvironment::isAdminArea() OR SmartEnvironment::isTaskArea()) {
			$this->PageViewSetCfg('error', 'OAuth2 Get.Code requires Index Area');
			return 502;
		} //end if
		//--

		//--
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'text/html');
		$this->PageViewSetCfg('rawdisp', 'inline');
		//--

		//--
		$csrf = '';
		//--
		if(SmartUtils::isset_cookie((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF)) { // {{{SYNC-OAUTH2-COOKIE-NAME-CSRF}}}
			//--
			$csrf = (string) trim((string)SmartUtils::get_cookie((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF));
			if((string)$csrf != '') {
				$csrf = (string) \SmartModExtLib\Oauth2\Oauth2Api::csrfPrivateKeyDecrypt((string)$csrf);
			} //end if
			//--
			SmartUtils::unset_cookie((string)\SmartModExtLib\Oauth2\Oauth2Api::OAUTH2_COOKIE_NAME_CSRF);
			//--
		} //end if
		//--
		if((string)trim((string)$csrf) == '') {
			$this->PageViewSetErrorStatus(403, 'OAuth2 Code Exchange: CSRF Key is empty or invalid ...');
			return; // dissalow access this page directly
		} //end if
		//--

		//--
		$vars = (array) $this->RequestVarsGet();
		//--
		if(
			(!isset($vars['code']))
			OR
			((string)trim((string)$vars['code']) == '')
		) {
			$this->PageViewSetErrorStatus(400, 'OAuth2 Code Exchange: Code parameter is empty or not provided ...');
			return;
		} //end if else
		//--
		$code = (string) trim((string)$vars['code']);
		//--
		$state = (string) trim((string)($vars['state'] ?? null));
		//--
		unset($vars['code']); // this is displayed separately
		unset($vars['state']);
		unset($vars['page']); // this should not be displayed, it is SF internally only
		//--
		$vars = Smart::json_decode((string)Smart::json_encode((array)$vars, false, true, false), true); // safety: max levels as default, this comes from GET/POST, will be limited there ; limiting here will log unwanted json encode/decode warnings
		if(!is_array($vars)) {
			$vars = []; // failed to re-encode, something is messy, don't display !
		} //end if
		if((int)Smart::array_size((array)$vars) > 16) {
			$vars = [ 'data-size' => (int)Smart::array_size((array)$vars) ]; // too large, skip display
		} //end if
		//--
		$displayExtraData = false;
		if((int)Smart::array_size((array)$vars) > 0) {
			$displayExtraData = true;
		} //end if
		//--

		//--
		$isCsrfValid = false;
		//--
		if(((string)$csrf != '') AND ((string)$state != '')) {
			$isCsrfValid = (bool) \SmartModExtLib\Oauth2\Oauth2Api::csrfCheckState((string)$state, (string)$csrf);
		} //end if
		//--

		//--
		$this->PageViewSetVar(
			'main',
			(string) SmartComponents::http_status_message(
				'OAuth2 Client :: Code Exchange',
				'OAuth2 New Code :: API response',
				(string) SmartMarkersTemplating::render_file_template(
						(string) $this->ControllerGetParam('module-view-path').'get-code.mtpl.htm',
						[
							'CRR-TIME' 		=> (int)    time(),
							'CODE' 			=> (string) $code,
							'PRETTY-VARS' 	=> (string) (!!$displayExtraData ? SmartUtils::pretty_print_var((array)$vars, 0, true) : ''),
							'HTTP-REFERER' 	=> (string) SmartUtils::get_server_current_request_referer(),
							'CSRF-STATE' 	=> (string) $state,
							'CSRF-VALUE' 	=> (string) $csrf,
							'CSRF-VALID' 	=> (string) (!!$isCsrfValid ? 1 : 0)
						]
					),
				'proxy' // everything must be inline this is why using this TPL type here ...
			)
		);
		//--

		//--
		return 200; // explicit
		//--

	} //END FUNCTION

} //END CLASS


// end of php code
