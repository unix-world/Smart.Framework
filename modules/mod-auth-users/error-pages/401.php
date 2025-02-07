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
	//-- 20250207
	// {{{SYNC-AUTH-USERS-LOGIN-REDIRECT}}}
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
	$redirect = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
	if((string)trim((string)$urlQuery) != '') {
		$redirect .= '&redir='.Smart::escape_url((string)$urlQuery);
	} //end if
	$jsRedirect = '';
	$htmlRedirect = '';
	$hrefUrl = '';
	$img = 'lib/framework/img/sign-important.svg';
	if((string)trim((string)$redirect) != '') {
	//	SmartFrameworkRuntime::Raise3xxRedirect(302, (string)$redirect);
		$htmlRedirect = '<meta http-equiv="refresh" content="1; url='.Smart::escape_html((string)$redirect).'">';
		$jsRedirect = '<script>setTimeout(function(){ self.location = \''.Smart::escape_js((string)$redirect).'\'; }, 850);</script>';
		$hrefUrl = (string) $redirect;
	//	$img = 'lib/framework/img/loading-spokes.svg';
	} else {
		$hrefUrl = (string) \SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
	} //end if
	//--
	$htmlPage = '<!DOCTYPE html><html><head><meta charset="UTF-8">'.$htmlRedirect.'<title>401 Authorization Required</title><style>body { background-color: #2E2E2E; color: #556666; } hr { height:1px; border:none 0; border-top:1px dashed #888888; } a { color: #556666; font-weight: normal; font-size: 0.875rem; text-decoration: none !important; border-bottom: dotted 1px #445555 !important; }</style></head><body>'.$jsRedirect.'<div style="text-align:center;">'.'<img style="opacity:0.4;" src="data:image/svg+xml,'.Smart::escape_html((string)Smart::escape_url((string)SmartFileSysUtils::readStaticFile((string)$img))).'">'.'<br><br><span>HTTP Status 401 Unauthorized</span><br><hr><a href="'.Smart::escape_html((string)$hrefUrl).'">Sign-In is Required</a></div></body></html>';
	//--
	return (string) $htmlPage;
	//--
} //END FUNCTION

// end of php code
