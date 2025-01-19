<?php
// [LIB - Smart.Framework / Lib Runtime]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart.Framework - Lib Runtime
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]


//==================================================================================
//================================================================================== CLASS START
//==================================================================================

/**
 * Class Smart.Framework Runtime
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY !!!
 *
 * @depends 	classes: SmartFrameworkSecurity, SmartFrameworkRegistry, SmartUnicode, Smart, SmartHashCrypto, SmartFileSysUtils, SmartFileSystem, SmartUtils, SmartComponents ; constants: SMART_FRAMEWORK_NETSERVER_MAXLOAD, SMART_SOFTWARE_URL_ALLOW_PATHINFO, SMART_FRAMEWORK_SEMANTIC_URL_DISABLE, SMART_FRAMEWORK_VERSION, SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME, SMART_FRAMEWORK_UUID_COOKIE_NAME, SMART_FRAMEWORK_UUID_COOKIE_SKIP, SMART_FRAMEWORK_INFO_DIR_LOG
 * @version		v.20250107
 * @package 	Application
 *
 */
final class SmartFrameworkRuntime {

	// ::
	// {{{SYNC-SMART-HTTP-STATUS-CODES}}}

	private static $NoCacheHeadersSent 		= false;

	private static $HttpStatusCodesOK  		= [ 200, 201, 202, 203, 204, 208, 304 ]; 																						// list of framework available HTTP OK Status Codes (sync with middlewares)
	private static $HttpStatusCodesRDR 		= [ 301, 302 ]; 																												// list of framework available HTTP Redirect Status Codes (sync with middlewares)
	private static $HttpStatusCodesERR 		= [ 400, 401, 402, 403, 404, 405, 406, 408, 409, 410, 415, 422, 423, 424, 429, 500, 501, 502, 503, 504, 507 ]; 	// list of framework available HTTP Error Status Codes (sync with middlewares)

	private static $ServerProcessed 		= false; // after all server variables are processed this will be set to true to avoid re-process server variables which can be a security or performance issue if re-process is called by mistake !
	private static $RequestProcessed 		= false; // after all request variables are processed this will be set to true to avoid re-process request variables which can be a security or performance issue if re-process is called by mistake !

	private static $HighLoadMonitorStats 	= null;  // register the high load monitor caches
	private static $RedirectionMonitorOn 	= false; // after the redirection monitor have been started this will be set to true to avoid re-run it


	//======================================================================
	public static function GetStatusMessageByStatusCode(int $y_http_status_code) {
		//--
		$status_code_msg = 'Unknown';
		//--
		switch((int)$y_http_status_code) {
			//--
			case 200:
				$status_code_msg = 'OK';
				break;
			case 201:
				$status_code_msg = 'Created';
				break;
			case 202:
				$status_code_msg = 'Accepted';
				break;
			case 203:
				$status_code_msg = 'Non-Authoritative Information';
				break;
			case 204:
				$status_code_msg = 'No Content';
				break;
			case 208:
				$status_code_msg = 'Already Reported';
				break;
			//--
			case 301:
				$status_code_msg = 'Moved Permanently'; // (Permanent Redirect)
				break;
			case 302:
				$status_code_msg = 'Found'; // (Temporary Redirect) ; Moved Temporarily
				break;
			//--
			case 304:
				$status_code_msg = 'Not Modified';
				break;
			//--
			case 400:
				$status_code_msg = 'Bad Request';
				break;
			case 401:
				$status_code_msg = 'Unauthorized';
				break;
			case 402:
				$status_code_msg = 'Subscription Required'; // 402 Payment Required
				break;
			case 403:
				$status_code_msg = 'Forbidden';
				break;
			case 404:
				$status_code_msg = 'Not Found';
				break;
			case 405:
				$status_code_msg = 'Method Not Allowed';
				break;
			case 406:
				$status_code_msg = 'Not Acceptable';
				break;
			case 408:
				$status_code_msg = 'Request Timeout';
				break;
			case 409:
				$status_code_msg = 'Conflict'; // example: conflicts occur if a request to create collection /a/b/c/d/ is made, and /a/b/c/ does not exist
				break;
			case 410:
				$status_code_msg = 'Gone'; // a more permanent 404 like status, ex: if a page was available just for a period of time
				break;
			case 415:
				$status_code_msg = 'Unsupported Media Type';
				break;
			case 422:
				$status_code_msg = 'Unprocessable Content'; // Unprocessable Entity
				break;
			case 423:
				$status_code_msg = 'Locked';
				break;
			case 424:
				$status_code_msg = 'Dependency Failed'; // 424 Failed Dependency ; the request failed because it depended on another request
				break;
			case 429:
				$status_code_msg = 'Too Many Requests';
				break;
			//--
			case 500:
				$status_code_msg = 'Internal Server Error';
				break;
			case 501:
				$status_code_msg = 'Not Implemented';
				break;
			case 502:
				$status_code_msg = 'Bad Gateway';
				break;
			case 503:
				$status_code_msg = 'Service Unavailable';
				break;
			case 504:
				$status_code_msg = 'Gateway Timeout';
				break;
			case 507:
				$status_code_msg = 'Insufficient Storage';
				break;
			default:
				$status_code_msg = '(Other: See the HTTP Status Codes @ rfc7231)';
			//--
		} //end switch
		//--
		return (string) $status_code_msg;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise200Status(?string $y_msg, ?string $y_title='', ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(200);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 200 ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)($y_title ?? '200 '.self::GetStatusMessageByStatusCode(200)), (string)$y_msg, (string)$y_htmlmsg, '200'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise201Status(?string $y_msg, ?string $y_title='', ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(201);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 201 ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)($y_title ?? '201 '.self::GetStatusMessageByStatusCode(201)), (string)$y_msg, (string)$y_htmlmsg, '201'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise202Status(?string $y_msg, ?string $y_title='', ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(202);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 202 ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)($y_title ?? '202 '.self::GetStatusMessageByStatusCode(202)), (string)$y_msg, (string)$y_htmlmsg, '202'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise203Status(?string $y_msg, ?string $y_title='', ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(203);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 203 ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)($y_title ?? '203 '.self::GetStatusMessageByStatusCode(203)), (string)$y_msg, (string)$y_htmlmsg, '203'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise204NoContentStatus() {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(204);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 204 ...');
		} //end if else
		die(''); // No Content !
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise208Status(?string $y_msg, ?string $y_title='', ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(208);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 208 ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)($y_title ?? '208 '.self::GetStatusMessageByStatusCode(208)), (string)$y_msg, (string)$y_htmlmsg, '208'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise3xxRedirect(int $y_code, string $y_location) {
		//--
		$y_location = (string) trim((string)$y_location);
		//--
		switch((int)$y_code) {
			case 302:
				$y_code = 302;
				break;
			case 301:
				$y_code = 301;
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid ('.$y_code.') as 3xx status ; Used 301 instead ...');
				$y_code = 301;
		} //end switch
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code((int)$y_code); // redirect
			if((string)$y_location != '') {
				self::outputHttpSafeHeader('Location: '.$y_location); // force redirect
			} else {
				Smart::log_warning(__METHOD__.' # Empty 3xx Location ...');
			} //end if
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 3xx ('.$y_code.') ...');
		} //end if else
		die((string)SmartComponents::http_status_message((string)$y_code.' '.self::GetStatusMessageByStatusCode((int)$y_code), (string)'HTTP Status Code '.$y_code.': Redirect to (a different) Location', '<hr><a style="font-size:1.5rem;" href="'.Smart::escape_html((string)$y_location).'" title="'.Smart::escape_html((string)$y_location).'">Click here if you are not redirected automatically ...</a>', '3xx'));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise400Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(400);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 400 ...');
		} //end if else
		die((string)SmartComponents::http_message_400_badrequest((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise401Prompt(?string $y_msg, ?string $y_htmlmsg, ?string $y_realm, bool $y_head_auth) {
		//--
		$y_realm = (string) trim((string)$y_realm);
		$y_realm = (string) str_replace(['"', "'", '`'], ' ', (string)$y_realm);
		$y_realm = (string) trim((string)$y_realm);
		if((string)$y_realm == '') {
			$y_realm = 'Default (Private Area)';
		} //end if
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			if($y_head_auth !== false) {
				self::outputHttpSafeHeader('WWW-Authenticate: Basic realm="'.self::outputHttpSafeHeader((string)$y_realm).'"');
			} //end if
			self::outputHttpSafeHeader('HTTP/1.0 401 Authorization Required');
			http_response_code(401);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before prompt 401 ...');
		} //end if else
		//--
		if((string)trim((string)$y_htmlmsg) != '') {
			die((string)$y_htmlmsg."\n".'<!-- 401 Message: '.Smart::escape_html((string)$y_msg).' -->');
		} //end if
		die((string)SmartComponents::http_message_401_unauthorized((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise401Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(401);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 401 ...');
		} //end if else
		die((string)SmartComponents::http_message_401_unauthorized((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise402Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(402);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 402 ...');
		} //end if else
		die((string)SmartComponents::http_message_402_subscriptionrequired((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise403Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(403);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 403 ...');
		} //end if else
		die((string)SmartComponents::http_message_403_forbidden((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise404Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(404);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 404 ...');
		} //end if else
		die((string)SmartComponents::http_message_404_notfound((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise405Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(405);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 405 ...');
		} //end if else
		die((string)SmartComponents::http_message_405_methodnotallowed((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise406Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(406);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 406 ...');
		} //end if else
		die((string)SmartComponents::http_message_406_notacceptable((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise408Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(408);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 408 ...');
		} //end if else
		die((string)SmartComponents::http_message_408_requesttimeout((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise409Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(409);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 409 ...');
		} //end if else
		die((string)SmartComponents::http_message_409_conflict((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise410Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(410);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 410 ...');
		} //end if else
		die((string)SmartComponents::http_message_410_gone((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise415Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(415);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 415 ...');
		} //end if else
		die((string)SmartComponents::http_message_415_unsupportedmediatype((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise422Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(422);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 422 ...');
		} //end if else
		die((string)SmartComponents::http_message_422_unprocessablecontent((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise423Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(423);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 423 ...');
		} //end if else
		die((string)SmartComponents::http_message_423_locked((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise424Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(424);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 424 ...');
		} //end if else
		die((string)SmartComponents::http_message_424_dependencyfailed((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise429Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(429);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 429 ...');
		} //end if else
		die((string)SmartComponents::http_message_429_toomanyrequests((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise500Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(500);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 500 ...');
		} //end if else
		die((string)SmartComponents::http_message_500_internalerror((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise501Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(501);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 501 ...');
		} //end if else
		die((string)SmartComponents::http_message_501_notimplemented((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise502Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(502);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 502 ...');
		} //end if else
		die((string)SmartComponents::http_message_502_badgateway((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise503Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(503);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 503 ...');
		} //end if else
		die((string)SmartComponents::http_message_503_serviceunavailable((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise504Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(504);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 504 ...');
		} //end if else
		die((string)SmartComponents::http_message_504_gatewaytimeout((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function Raise507Error(?string $y_msg, ?string $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			self::outputHttpHeadersCacheControl();
			http_response_code(507);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before 507 ...');
		} //end if else
		die((string)SmartComponents::http_message_507_insufficientstorage((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Include with Require a PHP Script (script must end with .php, be a safe relative path and cannot be includded more than once) ; $area must be a description in case of error
	public static function requirePhpScript(string $script, ?string $area) {
		//--
		global $configs; // req. by transl custom adapters
		//--
		$script = (string) trim((string)$script);
		$area = (string) trim((string)$area);
		//--
		$err = '';
		//--
		if(strlen((string)$script) < 5) {
			$err = 'path is too short';
		} elseif((string)substr((string)$script, -4, 4) !== '.php') {
			$err = 'path must end with .php file extension';
		} elseif(!SmartFileSysUtils::checkIfSafePath((string)$script)) {
			$err = 'path is not relative/safe';
		} elseif(!SmartFileSystem::is_type_file((string)$script)) {
			$err = 'was not found';
		} //end if
		//--
		if((string)$err != '') {
			Smart::raise_error('ERROR: Cannot Include a PHP Script for the area: `'.$area.'` ; script is: `'.$script.'` ; reason: the file '.$err);
			return;
		} //end if
		//--
		require((string)$script);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// security: use this instead of header() to filter out unsafe and dangerous header characters
	public static function outputHttpSafeHeader($value) {
		//--
		$original_value = (string) $value;
		//--
		$value = (string) SmartFrameworkSecurity::PrepareSafeHeaderValue((string)$value);
		//--
		if((string)$value == '') {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Trying to set an empty header (after filtering the value) with original value of: '.$original_value, E_USER_WARNING);
			return;
		} //end if
		//--
		if(headers_sent()) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Headers Already Sent while trying to set a header with value of: '.$value, E_USER_WARNING);
			return;
		} //end if
		header((string)$value);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// for Advanced use Only :: this function outputs !!! the HTTP NoCache / Expire Headers
	public static function outputHttpHeadersCacheControl($expiration=-1, $modified=-1, $control='private') {
		//--
		if(self::$NoCacheHeadersSent !== false) {
			return; // this function can be called more than once per execution ; thus if so stop it here ; no log required ...
		} //end if
		//--
		$expiration = (int) $expiration; // expire time, in seconds, since now
		$modified   = (int) $modified;
		switch((string)$control) {
			case 'public':
				$control = 'public';
				break;
			case 'private':
			default:
				$control = 'private';
		} //end switch
		//--
		if(!headers_sent()) {
			//--
			if(($expiration < 0) AND ($modified < 0)) { // default
				//-- {{{SYNC-HTTP-NOCACHE-HEADERS}}}
				header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1 no-cache, not use their stale copy
				header('Pragma: no-cache'); // HTTP 1.0 no-cache
				header('Expires: '.gmdate('D, d M Y', @strtotime('-1 year')).' '.gmdate('H:i:s').' GMT'); // HTTP 1.0 no-cache expires
				header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
				//--
			} else {
				//--
				if($expiration < 60) {
					$expiration = 60;
				} //end if
				$expires = (int) (time() + $expiration);
				//--
				$modified = (int) $modified; // last modification timestamp of the contents, in seconds, must be > 0 <= now
				if(($modified <= 0) OR ($modified > time())) {
					$modified = (int) time();
				} //end if
				//--
				header('Expires: '.gmdate('D, d M Y H:i:s', (int)$expires).' GMT'); // HTTP 1.0
				header('Pragma: cache'); // HTTP 1.0
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', (int)$modified).' GMT');
				header('Cache-Control: '.$control.', max-age='.(int)$expiration); // HTTP 1.1 (private will dissalow proxies to cache the content)
				//--
			} //end if else
			//--
		} else {
			//--
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Could not set No-Cache Headers (Expire='.$expiration.' ; Modified='.$modified.'), Headers Already Sent ...', E_USER_WARNING);
			//--
		} //end if else
		//--
		self::$NoCacheHeadersSent = true;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with OK HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesOK() {
		//--
		return (array) self::$HttpStatusCodesOK;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with REDIRECT HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesRDR() {
		//--
		return (array) self::$HttpStatusCodesRDR;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with ERROR HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesERR() {
		//--
		return (array) self::$HttpStatusCodesERR;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with ALL(OK,ERROR) HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesOKandERR() {
		//--
		return (array) array_merge(self::getHttpStatusCodesOK(), self::getHttpStatusCodesERR());
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// the the array list with ALL(OK,REDIRECT,ERROR) HTTP Status Codes (only codes that smart framework middlewares will handle)
	public static function getHttpStatusCodesALL() {
		//--
		return (array) array_merge(self::getHttpStatusCodesOK(), self::getHttpStatusCodesRDR(), self::getHttpStatusCodesERR());
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// check if pathInfo is enabled (allowed)
	public static function PathInfo_Enabled() {
		//--
		// this does not apply to standalone apps ; STANDALONE APPS MUST NOT USE THIS LIBRARY
		//--
		$status = false;
		//--
		if(defined('SMART_SOFTWARE_URL_ALLOW_PATHINFO')) {
			//--
			switch((int)SMART_SOFTWARE_URL_ALLOW_PATHINFO) {
				case 3: // only index enabled
					if(!SmartEnvironment::isAdminArea()) {
						$status = true;
					} //end if
					break;
				case 2: // both enabled: index & admin
					$status = true;
					break;
				case 1: // only admin enabled
					if(SmartEnvironment::isAdminArea()) {
						$status = true;
					} //end if
					break;
				case 0: // none enabled
				default:
					// not enabled
			} //end switch
			//--
		} //end if
		//--
		return (bool) $status;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Redirection_Monitor() {
		//--
		if(self::$RedirectionMonitorOn !== false) {
			return; // avoid run after it was used by runtime
		} //end if
		self::$RedirectionMonitorOn = true;
		//--
		$url_redirect = '';
		//--
		$the_current_url = (string) SmartUtils::get_server_current_url();
		$the_current_script = (string) SmartUtils::get_server_current_script();
		//--
		$is_disabled_frontent = (bool) ((defined('SMART_SOFTWARE_FRONTEND_DISABLED') && (SMART_SOFTWARE_FRONTEND_DISABLED === true)) ? true : false);
		$is_disabled_backend  = (bool) ((defined('SMART_SOFTWARE_BACKEND_DISABLED')  && (SMART_SOFTWARE_BACKEND_DISABLED === true))  ? true : false);
		$is_disabled_task 	  = (bool) ((defined('SMART_SOFTWARE_TASK_DISABLED')     && (SMART_SOFTWARE_TASK_DISABLED === true))     ? true : false);
		//--
		if(
			($is_disabled_frontent === true) AND
			($is_disabled_backend  === true) AND
			($is_disabled_task === true)
		) { // all frontend, backend and task services are disabled, avoid circular redirect from below
			self::Raise500Error('App Config ERROR'."\n".'All services (FRONTEND, BACKEND, TASK) of this application are currently DISABLED in the config/init ...');
			return;
		} //end if

		if(($is_disabled_frontent === true) AND ((string)$the_current_script == 'index.php')) {
			if($is_disabled_backend === true) {
				$url_redirect = $the_current_url.'task.php';
			} else {
				$url_redirect = $the_current_url.'admin.php';
			} //end if else
		} elseif(($is_disabled_backend === true) AND ((string)$the_current_script == 'admin.php')) {
			if($is_disabled_task === true) {
				$url_redirect = $the_current_url.'index.php';
			} else {
				$url_redirect = $the_current_url.'task.php';
			} //end if else
		} elseif(($is_disabled_task === true) AND ((string)$the_current_script == 'task.php')) {
			if($is_disabled_backend === true) {
				$url_redirect = $the_current_url.'index.php';
			} else {
				$url_redirect = $the_current_url.'admin.php';
			} //end if else
		} //end if else
		//--

		//--
		$pathinfo = null;
		if(SmartFrameworkRegistry::issetServerVar('PATH_INFO') === true) {
			$pathinfo = (string) SmartFrameworkRegistry::getServerVar('PATH_INFO'); // is already trimmed
		} //end if
		//--

		//--
		if(((string)$url_redirect == '') AND ((string)$pathinfo != '')) {
			$fix_pathinfo = (string) SmartFrameworkSecurity::FilterRequestPath((string)$pathinfo); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered ; actually they are filtered + trim when registered in Server's registry but in future the method FilterRequestPath may apply extra filters (currently only maps to filter string + trim)
			if((string)$fix_pathinfo != '') {
				if(self::PathInfo_Enabled() === true) {
					if(((string)$fix_pathinfo != '/') AND (strpos((string)$fix_pathinfo, '/~') !== false)) { // avoid common mistake to use just a / after script.php + detect tilda path
						return;
					} //end if
				} //end if
				$query_url = (string) ltrim((string)SmartUtils::get_server_current_queryurl(true), '?');
				if((string)$query_url != '') {
					$url_params = (array) Smart::url_parse_query((string)$query_url);
					if(array_key_exists('page', (array)$url_params)) {
						if(strpos((string)$fix_pathinfo, '/page/') !== false) {
							unset($url_params['page']); // dissalow having 'page' in url query if path contains it to avoid infinite loop infinite !
							$query_url = (string) Smart::url_build_query((array)$url_params, false);
						} //end if
					} //end if
					$url_params = [];
				} //end if
				if((string)$query_url != '') {
					$query_url = '&'.$query_url;
				} //end if
				$url_redirect = (string) $the_current_url.$the_current_script.'?'.$fix_pathinfo.$query_url;
			} //end if
		} //end if
		//--
		if((string)$url_redirect != '') {
			//--
			self::Raise3xxRedirect(302, (string)$url_redirect);
			die('Redirect 302 to: '.$url_redirect);
			return;
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Create a Download Link for the Download Handler
	public static function Decode_Download_Link(?string $y_encrypted_link) {
		//--
		return (string) trim((string)SmartCipherCrypto::decrypt(
			(string) $y_encrypted_link,
			(string) 'Smart.Framework//DownloadLink'.SMART_FRAMEWORK_SECURITY_KEY // {{{SYNC-DOWNLOAD-LINK-CRYPT-KEY}}}
		));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Create a Download Link for the Download Handler
	public static function Create_Download_Link(?string $y_file, ?string $y_ctrl_key) {
		//--
		// TODO: see: {{{TODO-DOWNLOADS-HANDLER-REFACTORING}}}
		// 		* move this and Decode_Download_Link() to lib utils
		// 		* unify this with SmartMailerMimeParser::encode_mime_fileurl() as:
		// 		* use a CSRF Access Token as in method above (see EMAIL_TOKEN_COOKIE_NAME)
		// 		* add here an option to use the self robot key, if URL is passed instead of file link (as used in email parsing and decoding)
		// 		* create a method to Unpack the Download Link and return the result as [ error=if-any, file=path/.../ ] ; use this method also in SmartMailerMimeParser::decode_mime_fileurl()
		// 		* add to Middleware DownloadsHandler() the option to handle also download URLs via SmartRobot !?? (... not sure)
		//--
		$y_file = (string) trim((string)$y_file);
		if((string)$y_file == '') {
			Smart::log_warning('Utils / Create Download Link: Empty File Path has been provided. This means the download link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_file)) {
			Smart::log_warning('Utils / Create Download Link: Invalid File Path has been provided. This means the download link will be unavaliable (empty) to assure security protection. File: '.$y_file);
			return '';
		} //end if
		//--
		$y_ctrl_key = (string) trim((string)$y_ctrl_key);
		if((string)$y_ctrl_key == '') {
			Smart::log_warning('Utils / Create Download Link: Empty Controller Key has been provided. This means the download link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		if(!defined('SMART_ERROR_AREA')) {
			Smart::log_warning('Utils / Create Download Link: Missing SMART_ERROR_AREA. This means the download link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		$y_ctrl_key = (string) SMART_ERROR_AREA.'/'.$y_ctrl_key; // {{{SYNC-DWN-CTRL-PREFIX}}}
		//--
		$crrtime = (int) time();
		$access_key = (string) SmartHashCrypto::checksum('DownloadLink:'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.SMART_APP_VISITOR_COOKIE.':'.$y_file.'^'.$y_ctrl_key);
		$unique_key = (string) SmartHashCrypto::checksum('Time='.$crrtime.'#'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_key.'-'.SmartUtils::unique_auth_client_private_key().':'.$y_file.'+'.$y_ctrl_key);
		//-- {{{SYNC-DOWNLOAD-ENCRYPT-ARR}}}
		$safe_download_link = (string) SmartCipherCrypto::encrypt(
			(string) trim((string)$crrtime)."\n". 									// set the current time
			(string) trim((string)$y_file)."\n". 									// the file path
			(string) trim((string)$access_key)."\n". 								// access key based on UniqueID cookie
			(string) trim((string)$unique_key)."\n".								// unique key based on: User-Agent and IP
			(string) '-'."\n",														// self robot browser UserAgentName/ID key (does not apply here)
			(string) 'Smart.Framework//DownloadLink'.SMART_FRAMEWORK_SECURITY_KEY 	// {{{SYNC-DOWNLOAD-LINK-CRYPT-KEY}}}
		);
		//-- {{{SYNC-ENCRYPTED-URL-LINK}}}
		return (string)trim((string)$safe_download_link); // DO NOT ESCAPE URL here ... it must be done in controllers ; if escaped here and passed directly, not via URL will encode also ; and ! ... will not work
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Create a semantic URL like: (script.php)?/param1/value1/Param2/Value2
	 *
	 * @param 	STRING 	$y_url 				:: The standard URL in RFC3986 format as: (script.php)?page=my-module.my-page&param1=value1&Param2=Value2
	 *
	 * @return 	STRING						:: The semantic URL, depends on SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE ( '' | 'standard' | 'semantic' ) ; 'standard' and 'semantic' requires Apache Rewrite ; Examples: '' -> (script.php)?/page/(my-module.)my-page/param1/value1/Param2/Value2 ; 'standard' -> (my-module.)my-page.html?param1=value1&Param2=Value2 ; 'semantic' -> (my-module.)my-page.html?/param1/value1/Param2/Value2
	 */
	public static function Create_Semantic_URL($y_url) { // v.20210429
		//--
		$y_url = (string) trim((string)$y_url);
		if((string)$y_url == '') {
			return ''; // if URL is empty nothing to do ...
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_SEMANTIC_URL_DISABLE') AND (SMART_FRAMEWORK_SEMANTIC_URL_DISABLE === true)) {
			return (string) $y_url;
		} //end if
		//--
		$ignore_script = '';
		$ignore_module = '';
		if(SmartEnvironment::isAdminArea() !== true) { // not for admin !
			//--
			if(defined('SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT')) {
				if(SMART_FRAMEWORK_SEMANTIC_URL_SKIP_SCRIPT === true) {
					$ignore_script = (string) 'index.php';
				} //end if
			} //end if
			//--
			if(defined('SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE')) {
				if(SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE === true) {
					$ignore_module = (string) trim((string)Smart::get_from_config('app.index-default-module', 'string'));
				} //end if
			} //end if
			//--
		} //end if
		//--
		$semantic_separator = '?/';
		//--
		if(strpos((string)$y_url, (string)$semantic_separator) !== false) {
			return (string) $y_url; // it is already semantic or at least appear to be ...
		} // end if
		//--
		$arr = (array) Smart::url_parse((string)$y_url);
		//--
		$arr['scheme'] 	= (string) trim((string)$arr['scheme']); 	// http / https
		$arr['host'] 	= (string) trim((string)$arr['host']); 		// 127.0.0.1
		$arr['port'] 	= (string) trim((string)$arr['port']); 		// 80 / 443 / 8088 ...
		$arr['path'] 	= (string) trim((string)$arr['path']); 		// /some/path
		$arr['query'] 	= (string) trim((string)$arr['query']);		// page=some&op=other
		//--
		if((string)$arr['query'] == '') {
			return (string) $y_url; // there is no query string to format as semantic
		} //end if
		//--
		$semantic_url = '';
		//--
		if((string)$arr['host'] != '') {
			if((string)$arr['scheme'] != '') {
				$semantic_url .= (string) $arr['scheme'].':';
			} //end if
			$semantic_url .= (string) '//'.$arr['host'];
			if(((string)$arr['port'] != '') AND ((string)$arr['port'] != '80') AND ((string)$arr['port'] != '443')) {
				$semantic_url .= (string) ':'.$arr['port'];
			} //end if
		} //end if
		//--
		if((string)$ignore_script != '') {
			$len = (int) strlen((string)$ignore_script);
			if((int)$len > 0) {
				if((string)$arr['path'] == (string)$ignore_script) {
					$arr['path'] = '';
				} elseif((string)substr((string)$arr['path'], (-1*(int)$len), (int)$len) == (string)$ignore_script) {
					$len = (int)strlen((string)$arr['path']) - (int)$len;
					if($len > 0) {
						$arr['path'] = (string) substr((string)$arr['path'], 0, (int)$len);
					} //end if
				} //end if
			} //end if
		} //end if
		$semantic_url .= (string) $arr['path'];
		//--
		$use_rewrite = false;
		$use_rfc_params = false;
		if(SmartEnvironment::isAdminArea() !== true) { // not for admin !
			if(defined('SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE')) {
				if((string)SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE == 'semantic') {
					$use_rewrite = true;
				} elseif((string)SMART_FRAMEWORK_SEMANTIC_URL_USE_REWRITE == 'standard') {
					$use_rewrite = true;
					$use_rfc_params = true;
				} //end switch
			} //end if
		} //end if
		//--
		$vars = explode('&', $arr['query']);
		$asvars = array(); // store params except page
		$detected_page = ''; // store page if found
		$parsing_ok = true;
		for($i=0; $i<Smart::array_size($vars); $i++) {
			//--
			$pair = (array) explode('=', $vars[$i]);
			if(!array_key_exists(0, $pair)) {
				$pair[0] = null;
			} //end if
			if(!array_key_exists(1, $pair)) {
				$pair[1] = null;
			} //end if
			//--
			if(((string)trim((string)$pair[0]) == '') OR (!SmartFrameworkSecurity::ValidateUrlVariableName((string)$pair[0])) OR ((string)$pair[1] == '')) { // {{{SYNC-REQVARS-VALIDATION}}}
				$parsing_ok = false;
				break;
			} //end if
			//--
			if((string)$pair[0] === 'page') {
				$detected_page = (string) $pair[1];
			} else {
				$asvars[(string)$pair[0]] = (string) $pair[1];
			} //end if else
			//--
		} //end for
		$vars = array();
		//--
		if($parsing_ok !== true) {
			return (string) $y_url; // there is something wrong with the URL
		} //end if
		//--
		if(Smart::array_size($asvars) > 0) {
			$have_params = true;
		} else {
			$have_params = false;
		} //end if else
		//--
		$semantic_suffix = '';
		$have_semantic_separator = false;
		$page_rewrite_ok = false;
		//--
		if((string)$detected_page != '') {
			//--
			if(strpos((string)$detected_page, '.') !== false) {
				$arr_pg = (array) explode('.', (string)$detected_page);
				$the_pg_mod = '';
				if(array_key_exists(0, $arr_pg)) {
					$the_pg_mod = (string) trim((string)$arr_pg[0]); 	// no controller, use the default one
				} //end if
				$the_pg_ctrl = '';
				if(array_key_exists(1, $arr_pg)) {
					$the_pg_ctrl = (string) trim((string)$arr_pg[1]); 	// page controller
				} //end if
				$the_pg_ext = '';
				if(array_key_exists(2, $arr_pg)) {
					$the_pg_ext = (string) trim((string)$arr_pg[2]); 	// page extension **OPTIONAL**
				} //end if
				$arr_pg = array();
			} else {
				$the_pg_mod = ''; 						// no controller, use the default one
				$the_pg_ctrl = (string) $detected_page; // page controller
				$the_pg_ext = ''; 						// page extension
			} //end if else
			//--
			$pg_link = '';
			if(((string)$the_pg_mod == '') OR ((string)$the_pg_mod == (string)$ignore_module)) {
				$pg_link .= (string) $the_pg_ctrl;
			} else {
				$pg_link .= (string) $the_pg_mod.'.'.$the_pg_ctrl;
			} //end if
			//--
			if(($use_rewrite === true) AND (((string)$semantic_url == '') OR ((string)substr((string)$semantic_url, -1, 1) == '/'))) { // PAGE (with REWRITE)
				//--
				if((string)$the_pg_ext == '') {
					$the_pg_ext = 'html';
				} //end if
				//--
				$page_rewrite_ok = true;
				$semantic_suffix .= (string) $pg_link.'.'.$the_pg_ext;
				//--
			} else {
				//--
				$semantic_suffix .= (string) $semantic_separator.'page'.'/'.$pg_link.'/';
				$have_semantic_separator = true;
				//--
			} //end if else
			//--
		} //end if
		//--
		if($have_params === true) {
			//--
			foreach($asvars as $key => $val) {
				//--
				if(($page_rewrite_ok === true) AND ($use_rfc_params === true)) {
					//--
					$semantic_suffix = (string) Smart::url_add_suffix((string)$semantic_suffix, (string)$key.'='.$val);
					//--
				} else {
					//--
					$val = (string) str_replace('/', (string)Smart::escape_url('/'), (string)$val);
					$val = (string) str_replace((string)Smart::escape_url('/'), (string)Smart::escape_url((string)Smart::escape_url('/')), (string)$val); // needs double encode the / character for semantic URLs to avoid conflict with param/value
					//--
					if($have_semantic_separator !== true) {
						$semantic_suffix .= (string) $semantic_separator;
						$have_semantic_separator = true;
					} //end if
					$semantic_suffix .= (string) $key.'/'.$val.'/';
					//--
				} //end if else
				//--
			} //end foreach
			//--
		} //end if
		//--
		if((string)$semantic_suffix == '') {
			return (string) $y_url; // something get wrong with the conversion, maybe the URL query is formatted in a different way that could not be understood
		} //end if
		//--
		$semantic_url .= (string) $semantic_suffix;
		//--
		return (string) $semantic_url;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Parse_Semantic_URL() {

		// PARSE SEMANTIC URL VIA GET
		// it limits the URL to 8192 and vars to 1000

		//-- check if can run
		if(self::$RequestProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Cannot Re-Parse the Semantic URLs, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--

		//-- check overall
		if(defined('SMART_FRAMEWORK_SEMANTIC_URL_DISABLE') AND (SMART_FRAMEWORK_SEMANTIC_URL_DISABLE === true)) {
			return;
		} //end if
		//--

		//--
		$pathinfo = null;
		if(SmartFrameworkRegistry::issetServerVar('PATH_INFO') === true) {
			$pathinfo = (string) SmartFrameworkRegistry::getServerVar('PATH_INFO'); // is already trimmed
		} //end if
		//--
		if((self::PathInfo_Enabled() === true) AND ((string)$pathinfo != '')) {
			$semantic_url = '';
			$fix_pathinfo = (string) SmartFrameworkSecurity::FilterRequestPath((string)$pathinfo); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered ; actually they are filtered + trim when registered in Server's registry but in future the method FilterRequestPath may apply extra filters (currently only maps to filter string + trim)
			$sem_path_pos = strpos((string)$fix_pathinfo, '/~');
			if(($sem_path_pos !== false) AND ((int)$sem_path_pos >= 0)) {
				$semantic_url = (string) '?'.substr((string)$fix_pathinfo, 0, (int)$sem_path_pos);
			} //end if
			SmartFrameworkRegistry::setRequestPath(
				(string) $pathinfo
			) OR trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Failed to register !path-info! variable', E_USER_WARNING);
		} else {
			$semantic_url = (string) SmartFrameworkRegistry::getServerVar('REQUEST_URI');
		} //end if
		//--
		if((int)strlen($semantic_url) > 8192) { // standard limit is 2048 chars ; Apache limit is higher as of 8192 chars
			$semantic_url = (string) substr($semantic_url, 0, 8192);
		} //end if
		//--
		if(strpos($semantic_url, '?/') === false) {
			return;
		} //end if
		//--

		//--
		$get_arr = (array) explode('?/', $semantic_url, 2); // separe 1st from 2nd by ?/ if set
		$location_str = (string) trim((string)($get_arr[1] ?? ''));
		$get_arr = (array) explode('&', $location_str, 2); // separe 1st from 2nd by & if set
		$location_str = (string) trim((string)($get_arr[0] ?? ''));
		$get_arr = array(); // cleanup
		//--

		//--
		if((string)$location_str != '') {
			//--
			$location_arx = (array) explode('/', (string)$location_str, 1001); // max is 1000, so separe them from the rest
			$cnt_arx = (int) count($location_arx);
			if($cnt_arx > 1000) {
				$cnt_arx = 1000;
			} //end if
			//--
			$location_arr = array();
			if(is_array($location_arx)) {
				for($i=0; $i<$cnt_arx; $i++) {
					if(((string)trim((string)$location_arx[$i]) != '') AND (array_key_exists($i+1, $location_arx)) AND ((string)trim((string)$location_arx[$i+1]) != '')) {
						$location_arx[$i+1] = (string) SmartFrameworkSecurity::DecodeAndFilterUrlVarString((string)$location_arx[$i+1], false); // do not filter here, will filter later when exracting to avoid double filtering !
						$location_arx[$i+1] = (string) str_replace((string)rawurlencode('/'), '/', (string)$location_arx[$i+1]);
						$location_arr[(string)$location_arx[$i]] = (string) $location_arx[$i+1];
					} //end if
					$i += 1;
				} //end for
			} //end if
			//--
			if(is_array($location_arr)) {
				if(count($location_arr) > 0) {
					self::Extract_Filtered_Request_Get_Post_Vars($location_arr, 'SEMANTIC-URL');
				} //end if
			} //end if
			//--
		} //end if
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Extract_Filtered_Server_Vars($arr) {
		//-- check if can run
		if(self::$ServerProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Cannot Register Server Vars, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--
		SmartFrameworkRegistry::registerFilteredServerVars((array)$arr);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function Extract_Filtered_Request_Get_Post_Vars($arr, $info) {
		//-- check if can run
		if(self::$RequestProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Cannot Register Request/'.$info.' Vars, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--
		SmartFrameworkRegistry::registerFilteredRequestVars((array)$arr, (string)$info);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	// FILTER AND REGISTER THE INPUT COOKIES VARIABLES
	public static function Extract_Filtered_Cookie_Vars($arr) {
		//-- check if can run
		if(self::$RequestProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Cannot Register Cookie Vars, Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--
		SmartFrameworkRegistry::registerFilteredCookieVars((array)$arr);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// After all Server vars are processed this have to be called to lock and avoid re-processing the Server variables
	public static function Lock_Server_Processing() {
		//--
		if(self::$ServerProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--
		self::$ServerProcessed = true; // this will lock the Server vars processing
		//--
		SmartFrameworkRegistry::lockServerRegistry(); // this will lock the server vars registry
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// After all Request vars are processed this have to be called to lock and avoid re-processing the Request variables
	public static function Lock_Request_Processing() {
		//--
		if(self::$RequestProcessed !== false) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Registry is already locked !', E_USER_WARNING);
			return; // avoid run after it was already processed
		} //end if
		//--
		self::$RequestProcessed = true; // this will lock the Request vars processing
		//--
		SmartFrameworkRegistry::lockRequestRegistry(); // this will lock the request vars registry
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION MUST RUN AFTER AUTHENTICATION COMPLETED TO BE ABLE TO CHECK ALSO THE LOGIN ID ; IF SINGLE USER MODE IS ENABLED WILL CHECK IF CAN BYPASS IT OR IF NOT WILL STOP HERE !
	public static function SingleUser_Mode_AuthBreakPoint() {
		//--
		if( // single user login hook by user account {{{SYNC-SINGLE-USER-LOGIN-HOOK}}}
			defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE') AND
			defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE') AND
			defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID')
		) {
			if((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID != '') {
				if((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID !== (string)SmartAuth::get_auth_id()) {
					self::Raise503Error(
						(string) SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE,
						(string) SmartComponents::operation_ok('Single User Lock File: '.Smart::escape_html((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE), '80%').SmartComponents::operation_notice((string)Smart::nl_2_br((string)Smart::escape_html((string)SmartFileSystem::read((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE))), '80%')
					);
					die(__CLASS__.':SingleUserAccountIdHook');
				} //end if
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function SingleUser_Mode_Monitor() {
		//--
		if(defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE') OR defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE') OR defined('SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID')) {
			self::Raise500Error('Single User Monitor requires exclusive management of the following constants which should not be defined before: SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE, SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE, SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID');
			die('SingleUserModeMonitor:Constants');
		} //end if
		//--
		define('SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE', '.ht-sf-singleuser-mode');
		//--
		$have_valid_lockfile = false;
		$is_lock = false;
		$retry_seconds = 0;
		$special_unlock_by_username = ''; // this must be managed later by the authentication system because if accidentally will log out when locked by specific account id will never can re-login again while locked !
		$unlock_for_area = '';
		$unlock_for_ip = '';
		if(SmartFileSystem::is_type_file((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE)) { // here must be used the functions is_file() as the filesys lib is may yet initialized ...
			//--
			$arr = parse_ini_file((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_FILE, true, INI_SCANNER_RAW);
			/* Sample: .ht-sf-singleuser-mode
			[SINGLE-USER-LOCK]
			username=admin
			area=tsk
			ipaddr=127.0.0.1
			lifetime=3600
			time=1619216050
			*/
			//--
			if(is_array($arr) AND isset($arr['SINGLE-USER-LOCK']) AND is_array($arr['SINGLE-USER-LOCK'])) {
				if(
					isset($arr['SINGLE-USER-LOCK']['username']) AND
					isset($arr['SINGLE-USER-LOCK']['area']) AND
					isset($arr['SINGLE-USER-LOCK']['ipaddr']) AND
					isset($arr['SINGLE-USER-LOCK']['time']) AND
					isset($arr['SINGLE-USER-LOCK']['lifetime'])
				) {
					//--
					$have_valid_lockfile = true;
					//--
					$arr['SINGLE-USER-LOCK']['username'] = (string) trim((string)$arr['SINGLE-USER-LOCK']['username']);
					$arr['SINGLE-USER-LOCK']['area'] = (string) trim((string)$arr['SINGLE-USER-LOCK']['area']);
					$arr['SINGLE-USER-LOCK']['ipaddr'] = (string) trim((string)$arr['SINGLE-USER-LOCK']['ipaddr']);
					$arr['SINGLE-USER-LOCK']['time'] = (int) $arr['SINGLE-USER-LOCK']['time'];
					$arr['SINGLE-USER-LOCK']['lifetime'] = (int) $arr['SINGLE-USER-LOCK']['lifetime'];
					//--
					if((int)$arr['SINGLE-USER-LOCK']['time'] >= 0) { // reference time must be integer >= 0, for safety
						//--
						if((int)$arr['SINGLE-USER-LOCK']['lifetime'] > 0) {
							$exp_time = (int) ((int)$arr['SINGLE-USER-LOCK']['time'] + $arr['SINGLE-USER-LOCK']['lifetime']);
							$now_time = (int) time();
							if((int)$now_time < (int)$exp_time) {
								$retry_seconds = (int) ((int)$exp_time - (int)$now_time);
								if($retry_seconds > 0) {
									$is_lock = true; // lock with expire, lifetime is positive integer
								} //end if
							} //end if
						} else {
							$is_lock = true; // lock with no expire, lifetime is zero
						} //end if
						//--
						if($is_lock === true) {
							//--
							$unlock_for_ip = (string) $arr['SINGLE-USER-LOCK']['ipaddr'];
							$unlock_for_area = (string) $arr['SINGLE-USER-LOCK']['area'];
							$special_unlock_by_username = (string) $arr['SINGLE-USER-LOCK']['username'];
							//--
							$can_unlock_by_ip = false;
							$can_unlock_by_area = false;
							//--
							if(
								(((string)$unlock_for_ip != '') AND ((string)SmartUtils::get_ip_client() === (string)$unlock_for_ip)) OR
								((string)$unlock_for_ip == '')
							) {
								$can_unlock_by_ip = true;
							} //end if
							if(
								(((string)$arr['SINGLE-USER-LOCK']['area'] === 'idx') AND (SmartEnvironment::isAdminArea() !== true)) OR
								(((string)$arr['SINGLE-USER-LOCK']['area'] === 'adm') AND (SmartEnvironment::isAdminArea() === true) AND (SmartEnvironment::isTaskArea() !== true)) OR
								(((string)$arr['SINGLE-USER-LOCK']['area'] === 'tsk') AND (SmartEnvironment::isAdminArea() === true) AND (SmartEnvironment::isTaskArea() === true)) OR
								((string)$arr['SINGLE-USER-LOCK']['area'] == '')
							) {
								$can_unlock_by_area = true;
							} //end if
							//--
							if(
								($can_unlock_by_ip === true)  AND
								($can_unlock_by_area === true)
							) {
								$is_lock = false; // unlocked for who match all 3 criterias
							} //end if
							//--
						} //end if
						//--
					} //end if
					//--
				} //end if
			} //end if
			//--
			$arr = null;
			//--
		} //end if
		//--
		define('SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE', (string)'The Service is under Maintenance (SingleUser Mode), try again later ...'.(($retry_seconds > 0) ? "\n".'Retry in: '.(int)$retry_seconds.' seconds' : ''));
		define('SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID', (string)$special_unlock_by_username);
		//--
		if($have_valid_lockfile === true) {
			if($retry_seconds > 0) {
				self::outputHttpSafeHeader('Retry-After: '.(int)$retry_seconds);
			} //end if
			self::outputHttpSafeHeader('Y-Single-User-Id: '.SMART_FRAMEWORK_SINGLEUSER_LOCK_ACCOUNT_ID);
			self::outputHttpSafeHeader('Y-Single-User-Ip: '.$unlock_for_ip);
			self::outputHttpSafeHeader('Y-Single-User-Area: '.$unlock_for_area);
		} //end if
		//--
		if($is_lock === true) {
			self::Raise503Error((string)SMART_FRAMEWORK_SINGLEUSER_LOCK_MESSAGE);
			die('SingleUserModeMonitor:On');
		} //end if else
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	// If RUN 1st time will handle OVERLOAD PROTECTION if the
	// IF RUN 2nd time will return the values of load avgs
	public static function High_Load_Monitor() {
		//--
		if(is_array(self::$HighLoadMonitorStats)) {
			return (array) self::$HighLoadMonitorStats; // avoid re-run and serve from cache
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_NETSERVER_MAXLOAD') AND (SMART_FRAMEWORK_NETSERVER_MAXLOAD !== false)) {
			$tmp_max_load = (int) SMART_FRAMEWORK_NETSERVER_MAXLOAD; // ex: 90
			if((int)$tmp_max_load > 0) {
				$tmp_max_load = (float) ((int)$tmp_max_load / 100); // ex: real value is 0.9 from 90
			} else {
				$tmp_max_load = 0;
			} //end if else
		} else {
			$tmp_max_load = 0;
		} //end if
		//--
		$is_highload = false;
		//--
		$tmp_sysload_avg = [ 0, 0, 0 ]; // 1, 5, 15 : minutes
		//--
		if($tmp_max_load > 0) { // run only if set to a value > 0
			if(function_exists('sys_getloadavg')) {
				$tmp_sysload_avg = @sys_getloadavg(); // mixed: array or false
				if(!is_array($tmp_sysload_avg)) {
					$tmp_sysload_avg = array();
				} //end if
				if(!isset($tmp_sysload_avg[0])) {
					$tmp_sysload_avg[0] = 0;
				} //end if
				$tmp_sysload_avg[0] = (float) $tmp_sysload_avg[0];
				if((float)$tmp_sysload_avg[0] > (float)$tmp_max_load) { // protect against system overload over max
					$is_highload = true;
				} //end if
			} //end if
		} //end if
		//--
		self::$HighLoadMonitorStats = (array) $tmp_sysload_avg;
		//--
		if($is_highload === true) {
			Smart::log_warning('#SMART-FRAMEWORK-HIGH-LOAD-PROTECT#'."\n".'Smart.Framework // Web :: Service Premature Stop :: System Overload Protection: The System is Too Busy ... Try Again Later. The Load Averages reached the maximum allowed value by current settings ... ['.(float)$tmp_sysload_avg[0].' of '.(float)$tmp_max_load.']');
			self::Raise504Error('The Service is Too busy, try again later ...', SmartComponents::operation_warn('<b>Smart.Framework // Web :: System Overload Protection</b><br>The Load Averages reached the maximum allowed value by current settings ...', '100%'));
			die('HighLoadMonitor:TooBusy');
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	public static function IsVisitorEntropyIDCookieAvaliable() {
		//--
		$is_available = false;
		//--
		if(defined('SMART_FRAMEWORK_UUID_COOKIE_NAME')) {
			if( // {{{SYNC-SF-UID-SESS-COOKIE-NAME-LENGTH}}}
				((string)SMART_FRAMEWORK_UUID_COOKIE_NAME != '')
				AND
				(strlen((string)SMART_FRAMEWORK_UUID_COOKIE_NAME) >= 7)
				AND
				(strlen((string)SMART_FRAMEWORK_UUID_COOKIE_NAME) <= 25)
			) {
				if(SmartFrameworkSecurity::ValidateVariableName((string)SMART_FRAMEWORK_UUID_COOKIE_NAME)) { // {{{SYNC-VALIDATE-UID-COOKIE-NAME}}}
					if(
						(!defined('SMART_FRAMEWORK_UUID_COOKIE_SKIP')) OR
						(
							defined('SMART_FRAMEWORK_UUID_COOKIE_SKIP')
							AND
							(SMART_FRAMEWORK_UUID_COOKIE_SKIP !== true)
						)
					) {
						$is_available = true;
					} //end if
				} else {
					Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_UUID_COOKIE_NAME value is Invalid: '.SMART_FRAMEWORK_UUID_COOKIE_NAME);
				} //end if else
			} else {
				Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_UUID_COOKIE_NAME value is Empty');
			} //end if
		} //end if
		//--
		return (bool) $is_available;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function SetVisitorEntropyIDCookie() {
		//--
		if(!defined('SMART_FRAMEWORK_VERSION')) {
			self::Raise500Error('Smart Runtime // Set Visitor Entropy ID Cookie :: Requires Smart.Framework to be loaded ...');
			return;
		} //end if
		//--
		if(defined('SMART_APP_VISITOR_COOKIE')) {
			self::Raise500Error('SetVisitorEntropyIDCookie :: SMART_APP_VISITOR_COOKIE must not be re-defined ...');
			return;
		} //end if
		//--
		$cookie = '';
		//--
		if(self::IsVisitorEntropyIDCookieAvaliable() === true) { // {{{SYNC-SMART-UNIQUE-COOKIE}}}
			//--
			$wasset = false;
			//--
			$expire = 0;
			if(defined('SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME')) {
				$expire = (int) SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME;
				if($expire <= 0) {
					$expire = 0;
				} //end if
			} //end if
			//--
			$cookie = (string) trim((string)SmartFrameworkRegistry::getCookieVar((string)SMART_FRAMEWORK_UUID_COOKIE_NAME));
			if(((string)$cookie == '') OR ((int)strlen((string)$cookie) < 34) OR ((int)strlen((string)$cookie) > 52) OR (!preg_match('/^[A-Za-z0-9]+$/', (string)$cookie))) { // if sh3a224 (b62) is mostly ~ 38 characters ; be flexible as +/- 4 characters (34..52 bytes)
				$entropy = (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sh3a224((string)Smart::unique_entropy('uuid-cookie')), 62); // generate a random unique key ; cookie was not yet set or is invalid ; use B62 to avoid re-encode as url encode which will raise the size as encoding %xy non-letter or non-numeric chars ...
				SmartUtils::set_cookie((string)SMART_FRAMEWORK_UUID_COOKIE_NAME, (string)$entropy, (int)$expire);
				$cookie = (string) $entropy;
				$wasset = true;
			} //end if
			if($wasset !== true) {
				SmartUtils::set_cookie((string)SMART_FRAMEWORK_UUID_COOKIE_NAME, (string)$cookie, (int)$expire);
			} //end if
			//--
		} //end if
		//-- #end# sync
		define('SMART_APP_VISITOR_COOKIE', (string)$cookie); // empty or cookie ID
		//--
	} //END FUNCTION
	//======================================================================


	//=====


	//======================================================================
	public static function DebugRequestLog($y_message) {
		//--
		if(!SmartEnvironment::ifDebug()) {
			return;
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK_INFO_DIR_LOG')) {
			return;
		} //end if
		//--
		$the_log = SMART_FRAMEWORK_INFO_DIR_LOG.'debug-requests-'.date('Y-m-d@H').'.log';
		//--
		if(is_dir((string)SMART_FRAMEWORK_INFO_DIR_LOG)) { // here must be is_dir() and file_put_contents() as the smart framework libs are not yet initialized in this phase ...
			@file_put_contents((string)$the_log, $y_message."\n", FILE_APPEND | LOCK_EX); // init
		} //end if
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//==================================================================================
//================================================================================== CLASS END
//==================================================================================


// end of php code
