<?php
// Smart.Framework / Middleware / Admin | Task
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//##### WARNING: #####
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//####################

if(defined('SMART_FRAMEWORK_RELEASE_MIDDLEWARE')) {
	@http_response_code(500);
	die('SMART_FRAMEWORK_RELEASE_MIDDLEWARE cannot be defined outside MIDDLEWARE [A][T]');
} //end if
define('SMART_FRAMEWORK_RELEASE_MIDDLEWARE', '[A][T]@v.8.7');

//==================================================================================
//================================================================================== CLASS START
//==================================================================================

// [REGEX-SAFE-OK]

/**
 * Class: Middleware Admin | Task Service Handler
 *
 * DO NOT CALL THIS CLASS ANYWHERE AS THIS IS THE MAIN HANDLER FOR admin.php | task.php
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
 *
 * @version		20250124
 * @package 	Application
 *
 */
final class SmartAppAdminMiddleware extends SmartAbstractAppMiddleware { // return mixed: true (main request) ; false (child request) ; null/void (other cases)

	// ::

	private static $MiddlewareCompleted = false;


	//====================================================================
	public static function Run() {
		//--
		//==
		//--
		if(self::$MiddlewareCompleted !== false) { // avoid to execute more than 1 this middleware !
			SmartFrameworkRuntime::Raise500Error('Middleware App Execution already completed ...');
			return;
		} //end if
		self::$MiddlewareCompleted = true;
		//--
		if(SmartEnvironment::isAdminArea() !== true) {
			Smart::raise_error(
				'Middleware ERROR: This Middleware can run only for Admin or Task Areas'
			);
			return;
		} //end if
		//--
		// ### TASKS are behaving like admin area but with some extra special privileges but having some extra restrictions
		// Tasks:
		// 	* must be restricted by a specific list of IP addresses
		// 	* must be restricted via the admin authentication system
		// 	* must run in a separate area than admin, but using the admin middleware
		// 	* will not be allowed to run in shared controller mode or inherit from 3rd party controllers except the abstract controller
		// 	* will have certain unrestricted privileges that are not applicable for admin or index
		// ###
		//--
		if(SmartEnvironment::isTaskArea() === true) {
			$the_midmark = '[T]';
			$the_area = 'task';
			if(defined('SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS') AND ((string)trim((string)SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS) != '')) {
				if(stripos((string)SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS, '<'.SmartUtils::get_ip_client().'>') === false) {
					SmartFrameworkRuntime::Raise403Error('Tasks: The access to this service is restricted by an IP Address list. The IP: `'.SmartUtils::get_ip_client().'` is not in that list ...');
					return;
				} //end if
			} else {
				SmartFrameworkRuntime::Raise503Error('Tasks: The access to this service is disabled. The IP: `'.SmartUtils::get_ip_client().'` is not allowed by current IP Address list ...'.(SmartEnvironment::ifDevMode() ? "\n".'Review the settings for SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS in: etc/init.php' : ''));
				return;
			} //end if else
		} else {
			$the_midmark = '[A]';
			$the_area = 'admin';
		} //end if else
		//--
		if(!defined('SMART_APP_TEMPLATES_DIR')) {
			SmartFrameworkRuntime::Raise500Error('The SMART_APP_TEMPLATES_DIR not defined ...');
			return;
		} //end if
		//--
		if(defined('SMART_APP_MODULE_AREA')) {
			SmartFrameworkRuntime::Raise500Error('Smart App Area must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_AUTH')) {
			SmartFrameworkRuntime::Raise500Error('Smart App Module Auth must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_REALM_AUTH')) {
			SmartFrameworkRuntime::Raise500Error('Smart App Module Realm Auth must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_DIRECT_OUTPUT')) {
			SmartFrameworkRuntime::Raise500Error('Smart App Module Direct Output must NOT be Defined outside controllers ...');
			return;
		} //end if
		//--
		//==
		//--
		$smartframeworkservice = ''; // special operation
		if(SmartFrameworkRegistry::issetRequestVar('smartframeworkservice') === true) {
			$smartframeworkservice = (string) strtolower((string)SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getRequestVar('smartframeworkservice', '', 'string')));
			switch((string)$smartframeworkservice) {
				case 'status':
					break;
				case 'debug':
				case 'debug-tpl':
				//	if(!SmartEnvironment::ifDebug()) {
				//		$smartframeworkservice = '';
				//	} //end if
					break;
				default: // invalid value
					$smartframeworkservice = '';
			} //end switch
		} //end if
		//--
		//==
		//-- switch language by url var (lang) or by cookie: with order @ GPC
		self::DetectInputLanguage();
		//--
		//== RAW OUTPUT FOR STATUS
		//--
		if((string)$smartframeworkservice == 'status') {
			//--
			if(!headers_sent()) {
				http_response_code(202); // Accepted
			} else {
				Smart::log_warning('Headers Already Sent before SERVICE STATUS');
			} //end if else
			SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // headers: cache control, force no-cache
			echo self::ServiceStatus($the_midmark);
			//--
			return false; // break stop
			//--
		} //end if
		//--
		//== OVERALL AUTHENTICATION BREAKPOINT
		//--
		SmartAppBootstrap::Authenticate('admin'); // if the auth uses session it may start now ; tasks should authenticate also on 'admin' area realm
		//--
		//== RAW OUTPUT FOR DEBUG
		//--
		if((string)$smartframeworkservice == 'debug') {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // headers: cache control, force no-cache
				if(SmartEnvironment::isTaskArea() === true) {
					echo self::DebugInfoGet('tsk');
				} else {
					echo self::DebugInfoGet('adm');
				} //end if else
			} else {
				http_response_code(404);
				echo SmartComponents::http_message_404_notfound('NO DEBUG Service has been activated on this server ...');
			} //end if
			//--
			return false; // break stop
			//--
		} elseif((string)$smartframeworkservice == 'debug-tpl') {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // headers: cache control, force no-cache
				echo SmartDebugProfiler::display_marker_tpl_debug((string)SmartFrameworkRegistry::getRequestVar('tpl', '', 'string'));
			} else {
				http_response_code(404);
				echo SmartComponents::http_message_404_notfound('NO TPL-DEBUG Service has been activated on this server ...');
			} //end if
			//--
			return false; // break stop
			//--
		} //end if else
		//--
		//== LOAD THE MODULE (OR DEFAULT MODULE)
		//--
		$reserved_controller_names = []; // these are reserved extensions and cannot be used as controller names because they need to be used also with friendly URLs as the 2nd param if module is missing from URL page param
		if(defined('SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES')) {
			$reserved_controller_names = (array) Smart::list_to_array((string)SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES);
		} //end if
		//--
		$err404 = '';
		$arr = array();
		//--
		$page = (string) SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getRequestVar('page', '', 'string'));
		$page = (string) trim((string)str_replace(array('/', '\\', ':', '?', '&', '=', '%'), array('', '', '', '', '', '', ''), $page)); // fix for get as it automatically replaces . with _ (so, reverse), but also fix some invalid characters ...
		if((string)$page == '') {
			$page = (string) trim((string)Smart::get_from_config('app.'.$the_area.'-home', 'string'));
		} //end if
		$defmod = (string) trim((string)Smart::get_from_config('app.'.$the_area.'-default-module', 'string'));
		//--
		if(strpos($page, '.') !== false) { // page can be as module.controller / module.controller(.php|html|stml|json|...)
			//--
			$arr = (array) explode('.', (string)$page, 3); // separe 1st and 2nd from the rest
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			//#
			//#
			$arr[0] = (string) trim((string)strtolower((string)$arr[0])); // module
			$arr[1] = (string) trim((string)strtolower((string)$arr[1])); // controller
			//#
			//# Admin or Task will NOT integrate with friendly URLs SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE
			//# that feature is just for Index
			//#
			//--
		} elseif((string)$defmod != '') {
			//--
			$arr[0] = (string) trim((string)strtolower((string)$defmod)); // get default module
			$arr[1] = (string) trim((string)strtolower((string)$page)); // controller
			//--
		} else {
			//--
			if((string)$err404 == '') {
				$err404 = 'Invalid Page (Invalid URL Page Segments Syntax): '.$page;
			} //end if
			//--
		} //end if else
		//--
		if(!array_key_exists(0, $arr)) {
			$arr[0] = null;
		} //end if
		if(!array_key_exists(1, $arr)) {
			$arr[1] = null;
		} //end if
		//--
		if(((string)$arr[0] == '') OR ((string)$arr[1] == '')) {
			if((string)$err404 == '') {
				$err404 = 'Invalid Page (Empty or Missing URL Page Segments): '.$page;
			} //end if
		} //end if
		if((!preg_match('/^[a-z0-9_\-]+$/', (string)$arr[0])) OR (!preg_match('/^[a-z0-9_\-]+$/', (string)$arr[1]))) {
			if((string)$err404 == '') {
				$err404 = 'Invalid Page (Invalid Characters in the URL Page Segments): '.$page;
			} //end if
		} //end if
		if(in_array((string)$arr[1], (array)$reserved_controller_names)) {
			if((string)$err404 == '') {
				$err404 = 'Invalid Page (Reserved Page Controller Name): ['.$arr[1].'] in: '.$page;
			} //end if
		} //end if
		//--
		$the_controller_name = (string) $arr[0].'.'.$arr[1];
		$the_path_to_module = (string) Smart::safe_pathname(SmartFileSysUtils::addPathTrailingSlash('modules/mod-'.Smart::safe_filename((string)$arr[0])));
		$the_controller_file = (string) Smart::safe_pathname($the_path_to_module.Smart::safe_filename($arr[1]).'.php');
		if(!SmartFileSystem::is_type_file((string)$the_controller_file)) {
			if((string)$err404 == '') {
				$err404 = 'Page does not exist: '.$page;
			} //end if
		} //end if
		//--
		if((string)$err404 != '') {
			SmartFrameworkRuntime::Raise404Error((string)$err404);
			return;
		} //end if
		//--
		if((!SmartFileSysUtils::checkIfSafePath((string)$the_path_to_module)) OR (!SmartFileSysUtils::checkIfSafePath((string)$the_controller_file))) {
			SmartFrameworkRuntime::Raise400Error('Insecure Module Access for Page: '.$page);
			return;
		} //end if
		//--
		if((class_exists('SmartAppIndexController')) OR (class_exists('SmartAppAdminController')) OR (class_exists('SmartAppTaskController'))) {
			SmartFrameworkRuntime::Raise500Error('Module Class Runtimes must be defined only in modules ...');
			return;
		} //end if
		//--
		require((string)$the_controller_file);
		//--
		if(SmartEnvironment::isTaskArea() === true) {
			if(((string)SMART_APP_MODULE_AREA !== 'TASK') AND ((string)SMART_APP_MODULE_AREA !== 'SHARED')) {
				SmartFrameworkRuntime::Raise502Error('Page Access Denied for Task Area: '.$page);
				return;
			} //end if
		} else {
			if(((string)SMART_APP_MODULE_AREA !== 'ADMIN') AND ((string)SMART_APP_MODULE_AREA !== 'SHARED')) {
				SmartFrameworkRuntime::Raise502Error('Page Access Denied for Admin Area: '.$page);
				return;
			} //end if
		} //end if else
		if(defined('SMART_APP_MODULE_AUTOLOAD')) {
			if((!SmartFileSystem::is_type_file((string)$the_path_to_module.'lib/autoload.php')) OR (!SmartFileSystem::have_access_read((string)$the_path_to_module.'lib/autoload.php'))) {
				SmartFrameworkRuntime::Raise500Error('FAILED to load the lib autoloader for module: '.$arr[0]);
				return;
			} //end if
			require_once((string)$the_path_to_module.'lib/autoload.php');
		} //end if
		if(defined('SMART_APP_MODULE_AUTH')) {
			if(SmartAuth::is_authenticated() !== true) {
				SmartFrameworkRuntime::Raise401Error('Authentication is Required for this page: '.$page);
				return;
			} //end if
			if(defined('SMART_APP_MODULE_REALM_AUTH')) {
				if((string)SmartAuth::get_auth_realm() !== (string)SMART_APP_MODULE_REALM_AUTH) {
					SmartFrameworkRuntime::Raise423Error('Page Access Denied ! Invalid Login Realm: '.$page);
					return;
				} //end if
			} //end if
		} //end if
		//--
		if(SmartEnvironment::isTaskArea() === true) {
			if(!class_exists('SmartAppTaskController')) {
				if((string)SMART_APP_MODULE_AREA === 'SHARED') {
					SmartFrameworkRuntime::Raise502Error('Page Access Not Allowed for TASK Area: '.$page);
				} else {
					SmartFrameworkRuntime::Raise500Error('Invalid Module Class Runtime for TASK Page: '.$page);
				} //end if
				return;
			} //end if
			if(!is_subclass_of('SmartAppTaskController', 'SmartAbstractAppController')) {
				SmartFrameworkRuntime::Raise500Error('Invalid Module Class Inheritance for TASK Controller Page: '.$page);
				return;
			} //end if
		} else {
			if(!class_exists('SmartAppAdminController')) {
				if((string)SMART_APP_MODULE_AREA === 'SHARED') {
					SmartFrameworkRuntime::Raise502Error('Page Access Not Allowed for ADMIN Area: '.$page);
				} else {
					SmartFrameworkRuntime::Raise500Error('Invalid Module Class Runtime for ADMIN Page: '.$page);
				} //end if
				return;
			} //end if
			if(!is_subclass_of('SmartAppAdminController', 'SmartAbstractAppController')) {
				SmartFrameworkRuntime::Raise500Error('Invalid Module Class Inheritance for ADMIN Controller Page: '.$page);
				return;
			} //end if
		} //end if else
		//--
		//== RUN THE MODULE
		//--
		if(SmartEnvironment::isTaskArea() === true) {
			$appModule = new SmartAppTaskController(
				(string) $the_path_to_module,
				(string) $the_controller_name,
				(string) $page,
				(string) 'task'
			);
		} else {
			$appModule = new SmartAppAdminController(
				(string) $the_path_to_module,
				(string) $the_controller_name,
				(string) $page,
				(string) 'admin'
			);
		} //end if else
		//--
		if(!defined('SMART_APP_MODULE_DIRECT_OUTPUT') OR SMART_APP_MODULE_DIRECT_OUTPUT !== true) {
			ob_start();
		} //end if
		//--
		$appStatusCode = 0; // init (for PHP8)
		$appSkipRun = false;
		//--
		$appStatusCode = $appModule->Initialize(); // mixed: null (void) / FALSE / TRUE / INT Status-Code
		$appSettings = (array) $appModule->PageViewGetCfgs();
		if(
			(($appStatusCode === false) OR (($appStatusCode !== true) AND ((int)$appStatusCode != 0))) OR
			((isset($appSettings['status-code'])) AND ((int)$appSettings['status-code'] != 0)) // {{{SYNC-SMART-FRAMEWORK-HANDLE-HTTP-STATUS-CODE}}}
		) {
			$appSkipRun = true; // skip Run
		} else {
			$appStatusCode = $appModule->Run(); // mixed: null (void) / FALSE / TRUE / INT Status-Code
			$appSettings = (array) $appModule->PageViewGetCfgs();
		} //end if
		//--
		$appReturnedCode = $appStatusCode; // mixed, preserve as original, used below
		//--
		if($appStatusCode === false) {
			$appStatusCode = 500;
		} elseif($appStatusCode === true) {
			$appStatusCode = 200;
		} else {
			$appStatusCode = intval($appStatusCode);
		} //end if
		$appStatusCode = (int) $appStatusCode; // ensure int
		//--
		$appModule->ShutDown();
		if((isset($appSettings['status-code'])) AND ((int)$appSettings['status-code'] != 0)) { // {{{SYNC-SMART-FRAMEWORK-HANDLE-HTTP-STATUS-CODE}}}
			if(((int)$appReturnedCode != 0) AND ((int)$appStatusCode != (int)$appSettings['status-code'])) {
				Smart::log_warning('The middleware service '.$the_midmark.' detected a different status codes in controller: '.$page.' ; '.($appSkipRun === true ? 'Initialize' : 'Run').'='.(int)$appStatusCode.' ; Status-Code='.(int)$appSettings['status-code']);
			} //end if
			$appStatusCode = (int) $appSettings['status-code']; // this rewrites what the Run() function returns, which is very OK as this is authoritative !
		} //end if
		//--
		$appRawHeads = (array) $appModule->PageViewGetRawHeaders();
		$appData = (array) $appModule->PageViewGetVars();
		if(!defined('SMART_APP_MODULE_DIRECT_OUTPUT') OR SMART_APP_MODULE_DIRECT_OUTPUT !== true) {
			$ctrl_output = ob_get_contents();
			ob_end_clean();
			if((string)$ctrl_output != '') {
				Smart::log_warning('The middleware service '.$the_midmark.' detected an illegal output in controller: '.$page."\n".'The result of this output is: '.$ctrl_output);
			} //end if
			$ctrl_output = '';
		} else {
			return; // break stop after the controller has terminated the direct output
		} //end if else
		//--
		$appModule = null; // free mem
		//--
		//== CACHE CONTROL
		//--
		if((int)$appStatusCode < 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
			if(((int)$appSettings['expires'] > 0) AND (!SmartEnvironment::ifDebug())) {
				SmartFrameworkRuntime::outputHttpHeadersCacheControl((int)$appSettings['expires'], (int)$appSettings['modified'], (string)$appSettings['c-control']); // headers: cache expiration control
			} elseif((int)$appSettings['expires'] != 304) { // {{{SYNC-MIDDLEWARE-CACHED-STATUS-CODE}}}
				SmartFrameworkRuntime::outputHttpHeadersCacheControl(); // headers: cache control, force no-cache
			} //end if else
		} //end if
		//--
		//== STATUS CODE {{{SYNC-SMART-HTTP-STATUS-CODES}}}
		//--
		switch((int)$appStatusCode) {
			//-- server errors
			case 507:
				SmartFrameworkRuntime::Raise507Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 504:
				SmartFrameworkRuntime::Raise504Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 503:
				SmartFrameworkRuntime::Raise503Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 502:
				SmartFrameworkRuntime::Raise502Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 501:
				SmartFrameworkRuntime::Raise501Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 500:
				SmartFrameworkRuntime::Raise500Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			//-- client errors
			case 429:
				SmartFrameworkRuntime::Raise429Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 424:
				SmartFrameworkRuntime::Raise424Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 423:
				SmartFrameworkRuntime::Raise423Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 422:
				SmartFrameworkRuntime::Raise422Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 415:
				SmartFrameworkRuntime::Raise415Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 410:
				SmartFrameworkRuntime::Raise410Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 409:
				SmartFrameworkRuntime::Raise409Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 408:
				SmartFrameworkRuntime::Raise408Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 406:
				SmartFrameworkRuntime::Raise406Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 405:
				SmartFrameworkRuntime::Raise405Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 404:
				SmartFrameworkRuntime::Raise404Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 403:
				SmartFrameworkRuntime::Raise403Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 402:
				SmartFrameworkRuntime::Raise402Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 401:
				SmartFrameworkRuntime::Raise401Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 400:
				SmartFrameworkRuntime::Raise400Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			//-- redirect 3xx statuses
			case 301:
			case 302:
				if((string)$appSettings['redirect-url'] != '') { // expects a valid URL
					SmartFrameworkRuntime::Raise3xxRedirect((int)$appStatusCode, (string)$appSettings['redirect-url']);
					return; // break stop
				} else {
					Smart::log_warning('Redirection HTTP Status ['.(int)$appStatusCode.'] was used in controller ['.$page.'] without a redirection URL ...');
					SmartFrameworkRuntime::Raise500Error('Empty Redirect URL for HTTP Status: '.(int)$appStatusCode);
				} //end if
				break;
			//-- extended 3xx statuses (CACHE CONTROL)
			case 304: // OK, CACHED
				if(!headers_sent()) {
					http_response_code(304); // Not Modified (use it carefully)
				} else {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS='.(int)$appStatusCode);
				} //end if else
				break;
			//-- No Content 204 status, for APIs
			case 204:
				SmartFrameworkRuntime::Raise204NoContentStatus(); // here should be no output !
				return;
				break;
			//-- extended 2xx statuses: NOTICE / WARNING / ERROR that can be used for REST / API
			case 208: // ERROR
				if(!headers_sent()) {
					http_response_code(208); // Already Reported (this should be used only as an alternate SUCCESS code instead of 200 for ERRORS)
				} else {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS='.(int)$appStatusCode);
				} //end if else
				break;
			case 203: // WARNING
				if(!headers_sent()) {
					http_response_code(203); // Non-Authoritative Information (this should be used only as an alternate SUCCESS code instead of 200 for WARNINGS)
				} else {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS='.(int)$appStatusCode);
				} //end if else
				break;
			case 202: // NOTICE
				if(!headers_sent()) {
					http_response_code(202); // Accepted (this should be used only as an alternate SUCCESS code instead of 200 for NOTICES)
				} else {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS='.(int)$appStatusCode);
				} //end if else
				break;
			case 201: // INFO
				if(!headers_sent()) {
					http_response_code(201); // Created, for APIs (this should be used only as an alternate SUCCESS code instead of 200 for NOTICES)
				} else {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS='.(int)$appStatusCode);
				} //end if else
				break;
			//-- DEFAULT: OK
			case 200:
			default: // any other codes not listed above are not supported and will be interpreted as 200
				if(headers_sent()) {
					Smart::log_warning('Headers Already Sent in controller ['.$page.'] before HTTP-STATUS=200');
				} //end if
				if(((int)$appStatusCode != 0) AND ((int)$appStatusCode != 200)) {
					Smart::log_warning('Invalid HTTP-STATUS='.(int)$appStatusCode.' detected in controller ['.$page.'] ; was set to HTTP-STATUS=200');
				} //end if
		} //end switch
		//--
		//== PREPARE THE OUTPUT
		//--
		$rawpage = '';
		if(isset($appSettings['rawpage'])) {
			$rawpage = (string) strtolower((string)$appSettings['rawpage']);
			if((string)$rawpage == 'yes') {
				$rawpage = 'yes'; // standardize the value
			} //end if
		} //end if
		if((string)$rawpage != 'yes') {
			$rawpage = '';
		} //end if
		//--
		$rawmime = '';
		if((string)$rawpage == 'yes') {
			if(isset($appSettings['rawmime'])) {
				$rawmime = (string) $appSettings['rawmime'];
				if((string)$rawmime != '') {
					$rawmime = (string) SmartValidator::validate_mime_type($rawmime);
				} //end if
			} //end if else
		} //end if
		//--
		$rawdisp = '';
		if((string)$rawpage == 'yes') {
			if(isset($appSettings['rawdisp'])) {
				$rawdisp = (string) $appSettings['rawdisp'];
				if((string)$rawdisp != '') {
					$rawdisp = (string) SmartValidator::validate_mime_disposition($rawdisp);
				} //end if
			} //end if else
		} //end if
		//--
		//== RAW HEADERS
		//--
		self::SetRawHeaders($appRawHeads); // headers must be set before downloads and after STD.HTTP STATUS CODES
		//--
		//== DOWNLOADS HANDLER (downloads can be set only explicit from Controllers)
		//--
		if(((string)$appSettings['download-packet'] != '') AND ((string)$appSettings['download-key'] != '')) { // expects an encrypted data packet and a key
			$dwl_result = self::DownloadsHandler((string)$appSettings['download-packet'], (string)$appSettings['download-key']);
			if((string)$dwl_result != '') {
				Smart::log_info('File-Download: '.$dwl_result, 'Client: '.SmartUtils::get_visitor_signature()); // log result and mark it as finalized
			} //end if
			return; // break stop
		} //end if
		//--
		//== RAW OUTPUT FOR PAGES
		//--
		if((string)$rawpage == 'yes') {
			//--
			if(headers_sent()) {
				Smart::raise_error(
					'Middleware ERROR: Headers already sent',
					'ERROR: Headers already sent !' // msg to display
				);
				return; // avoid serve raw pages with errors injections before headers
			} //end if
			//--
			if((string)$rawmime != '') {
				SmartFrameworkRuntime::outputHttpSafeHeader('Content-Type: '.$rawmime);
			} //end if
			if((string)$rawdisp != '') {
				SmartFrameworkRuntime::outputHttpSafeHeader('Content-Disposition: '.$rawdisp);
			} //end if
			SmartFrameworkRuntime::outputHttpSafeHeader('Content-Length: '.((int)strlen((string)($appData['main'] ?? null)))); // must be strlen NOT SmartUnicode::str_len as it must get number of bytes not characters
			echo (string) ($appData['main'] ?? null);
			return; // break stop
			//--
		} //end if else
		//--
		//== DEFAULT OUTPUT
		//--
		if((isset($appSettings['template-path'])) AND ((string)trim((string)$appSettings['template-path']) != '')) {
			if((string)$appSettings['template-path'] == '@') { // if template path is set to self (module)
				$the_template_path = '@'; // this is a special setting
			} else {
				$the_template_path = (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)trim((string)$appSettings['template-path'])));
			} //end if else
		} else { // use default template path
			$the_template_path = (string) trim((string)Smart::get_from_config('app.'.$the_area.'-template-path', 'string'));
			if((string)$the_template_path == '') {
				Smart::log_warning('Invalid Page Template Path In Config: `'.$the_template_path.'`');
				SmartFrameworkRuntime::Raise500Error('Invalid Page Template Path. See the error log !');
				return;
			} //end if
			if((string)$the_template_path != '@') {
				$the_template_path = (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)$the_template_path));
			} //end if
		} //end if else
		//--
		if((isset($appSettings['template-file'])) AND ((string)trim((string)$appSettings['template-file']) != '')) {
			$the_template_file = Smart::safe_filename(trim((string)$appSettings['template-file']));
		} else { // use default template file
			$the_template_file = (string) trim((string)Smart::get_from_config('app.'.$the_area.'-template-file', 'string'));
			if((string)$the_template_file == '') {
				Smart::log_warning('Invalid Page Template File In Config: `'.$the_template_file.'`');
				SmartFrameworkRuntime::Raise500Error('Invalid Page Template File. See the error log !');
				return;
			} //end if
			$the_template_file = Smart::safe_filename($the_template_file);
		} //end if else
		//--
		if((string)$the_template_path == '@') {
			$the_template_path = (string) $the_path_to_module.'templates/'; // must have the dir last slash as above
		} elseif(strpos((string)trim((string)$the_template_path, '/'), '/') === false) { // if not contains a path but only a dir name, otherwise leave as is
			$the_template_path = (string) SMART_APP_TEMPLATES_DIR.$the_template_path; // finally normalize and set the complete template path if only a dir name
		} //end if else
		$the_template_file = (string) $the_template_file; // finally normalize
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$the_template_path)) {
			Smart::log_warning('Invalid Page Template Path: '.$the_template_path);
			SmartFrameworkRuntime::Raise500Error('Invalid Page Template Path. See the error log !');
			return;
		} //end if
		if(!SmartFileSystem::is_type_dir($the_template_path)) {
			Smart::log_warning('Page Template Path does not Exists: '.$the_template_path);
			SmartFrameworkRuntime::Raise500Error('Page Template Path does not Exists. See the error log !');
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$the_template_path.$the_template_file)) {
			Smart::log_warning('Invalid Page Template File: '.$the_template_path.$the_template_file);
			SmartFrameworkRuntime::Raise500Error('Invalid Page Template File. See the error log !');
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($the_template_path.$the_template_file)) {
			Smart::log_warning('Page Template File does not Exists: '.$the_template_path.$the_template_file);
			SmartFrameworkRuntime::Raise500Error('Page Template File does not Exists. See the error log !');
			return;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			if(SmartEnvironment::isTaskArea() === true) {
				self::DebugInfoCookieSet('tsk');
			} else {
				self::DebugInfoCookieSet('adm');
			} //end if else
		} // end if
		echo SmartComponents::render_app_template((string)$the_template_path, (string)$the_template_file, (array)$appData);
		//--
		if(!defined('SMART_SOFTWARE_DISABLE_STATUS_POWERED') OR SMART_SOFTWARE_DISABLE_STATUS_POWERED !== true) {
			//-- {{{SYNC-RESOURCES}}}
			if(function_exists('memory_get_peak_usage')) {
				$res_memory = (int) @memory_get_peak_usage(false);
			} else {
				$res_memory = -1; // unknown
			} //end if else
			$res_time = (float) (microtime(true) - (float)SMART_FRAMEWORK_RUNTIME_READY);
			//-- #END-SYNC
			echo "\n".'<!-- Smart.Framework PHP/Javascript :: '.SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION.' @ '.SMART_FRAMEWORK_RELEASE_URL.' -->';
			echo "\n".'<!-- WebPage Server-Side Metrics '.Smart::escape_html((string)$the_midmark).': '.str_pad('Total Execution Time = '.Smart::format_number_dec($res_time, 13, '.', '').' seconds', 55, ' ', STR_PAD_BOTH).' | '.str_pad('Memory Peak Usage = '.SmartUtils::pretty_print_bytes((int)$res_memory, 2), 37, ' ', STR_PAD_BOTH).' -->'."\n";
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//====================================================================


} //END CLASS


//==================================================================================
//================================================================================== CLASS END
//==================================================================================


// end of php code
