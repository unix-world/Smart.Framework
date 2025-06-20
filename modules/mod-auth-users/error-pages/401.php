<?php
// [CUSTOM 401 Status Code Page]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

/**
 * Function: Custom 401 Answer (auth users)
 *
 * @access 		private
 * @internal
 *
 */
function custom_http_message_401_unauthorized($y_message, $y_html_message='') {
	//-- 20250606
	// {{{SYNC-AUTH-USERS-LOGIN-REDIRECT}}}
	//--
	$redirect = '';
	//--
	if(defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') AND (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS === true)) {
		//--
		$urlQuery = (string) SmartUtils::get_server_current_queryurl(true);
		if((string)$urlQuery != '') {
			$arrUrlQuery = (array) Smart::url_parse_query((string)$urlQuery);
			if((string)strtolower((string)trim((string)($arrUrlQuery['page'] ?? null))) == (string)\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_PAGE_SIGNIN) {
				unset($arrUrlQuery['page']);
			} //end if
			$arrSafeUrlQuery = [];
			foreach($arrUrlQuery as $key => $val) {
				if((string)trim((string)$val) != '') {
					$arrSafeUrlQuery[(string)$key] = (string) $val;
				} //end if
			} //end if
			$arrUrlQuery = null;
			$urlQuery = '';
			if((int)Smart::array_size($arrSafeUrlQuery) > 0) {
				$urlQuery = (string) trim((string)Smart::url_build_query((array)$arrSafeUrlQuery, false));
				if((string)$urlQuery != '') {
					$urlQuery = '?'.$urlQuery;
				} //end if
			} //end if
			if(((string)trim((string)$urlQuery) != '') AND ((int)strlen((string)$urlQuery) <= 255)) { // this may be set to a cookie, disallow too long cookies as this encrypted doubles the size
				$urlQuery = (string) SmartUtils::url_obfs_encrypt((string)$urlQuery); // to decrypt use: SmartUtils::url_obfs_decrypt($urlQuery)
			} else {
				$urlQuery = '';
			} //end if else
		} //end if
		//--
		$redirect = (string) SmartUtils::get_server_current_url().\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
		if((string)trim((string)$urlQuery) != '') {
			$redirect .= '&redir='.Smart::escape_url((string)$urlQuery);
		} //end if
		//--
	} //end if
	//--
	$htmlJsRedirect = '';
	$htmlRedirect = '';
	$hrefUrl = (string) SmartUtils::get_server_current_url();
	$img = 'lib/framework/img/sign-important.svg';
	//--
	$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
	//--
	$txtLink = (string) $translator->text('homepage');
	if(defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') AND (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS === true)) {
		$txtLink = (string) $translator->text('auth-required');
	} //end if
	//--
	if((string)trim((string)$redirect) != '') {
		SmartFrameworkRuntime::Raise3xxRedirect(302, (string)$redirect);
		$htmlRedirect = '<meta http-equiv="refresh" content="1; url='.Smart::escape_html((string)$redirect).'">';
		$htmlJsRedirect = '<script>setTimeout(function(){ self.location = \''.Smart::escape_js((string)$redirect).'\'; }, 850);</script>';
		$hrefUrl = (string) $redirect;
		$img = 'lib/framework/img/loading-spokes.svg';
	} else {
		if(defined('SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') AND (SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS === true)) {
			$hrefUrl = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
		} //end if
	} //end if
	//--
	$logo = 'lib/core/img/app/app.svg';
	//--
	$htmlPage = '<!DOCTYPE html><html><head><meta charset="UTF-8">'.$htmlRedirect.'<title>401 Authorization Required</title><style>body { background-color: #2E2E2E; color: #777788; } hr { height:1px; border:none 0; border-top:1px dashed #444455; } a { color: #777788; font-weight: normal; font-size: 1rem; text-decoration: none !important; border-bottom: dotted 1px #777788 !important; }</style></head><body>'.$htmlJsRedirect.'<div style="text-align:center;">'."\n".'<img height="128" style="opacity:0.4;" src="data:image/svg+xml,'.Smart::escape_html((string)Smart::escape_url((string)SmartFileSysUtils::readStaticFile((string)$logo))).'">'.'<hr>'."\n".'<span style="font-size:1.5rem;">HTTP Status 401 Unauthorized</span><br>'."\n".'<img style="opacity:0.88;" src="data:image/svg+xml,'.Smart::escape_html((string)Smart::escape_url((string)SmartFileSysUtils::readStaticFile((string)$img))).'"><br>'."\n".'<hr><a href="'.Smart::escape_html((string)$hrefUrl).'">'.Smart::escape_html((string)$txtLink).'</a></div></body></html>';
	//--
	return (string) $htmlPage;
	//--
} //END FUNCTION

// end of php code
