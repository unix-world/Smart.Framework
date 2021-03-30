<?php
// Smart.Framework / Middleware / Index
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//##### WARNING: #####
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//####################


define('SMART_FRAMEWORK_RELEASE_MIDDLEWARE', '[I]@v.7.2.1');


//==================================================================================
//================================================================================== CLASS START
//==================================================================================

// [REGEX-SAFE-OK]

/**
 * Class: Middleware Index Service Handler
 *
 * DO NOT CALL THIS CLASS ANYWHERE AS THIS IS THE MAIN HANDLER FOR index.php
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
 *
 * @version		20210330
 *
 */
final class SmartAppIndexMiddleware extends SmartAbstractAppMiddleware {

	// ::

	private static $MiddlewareCompleted = false;


	//====================================================================
	public static function Run() {
		//--
		global $configs;
		//--
		//==
		//--
		if(self::$MiddlewareCompleted !== false) { // avoid to execute more than 1 this middleware !
			self::Raise500Error('Middleware App Execution already completed ...');
			return;
		} //end if
		self::$MiddlewareCompleted = true;
		//--
		$the_midmark = '[I]';
		//--
		if(SmartFrameworkRuntime::isAdminArea() !== false) {
			Smart::raise_error(
				'Middleware ERROR: This Middleware can run only for Index Area'
			);
			return;
		} //end if
		//--
		if(!defined('SMART_APP_TEMPLATES_DIR')) {
			self::Raise500Error('The SMART_APP_TEMPLATES_DIR not defined ...');
			return;
		} //end if
		//--
		if(defined('SMART_APP_MODULE_AREA')) {
			self::Raise500Error('Smart App Area must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_AUTH')) {
			self::Raise500Error('Smart App Module Auth must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_REALM_AUTH')) {
			self::Raise500Error('Smart App Module Realm Auth must NOT be Defined outside controllers ...');
			return;
		} //end if
		if(defined('SMART_APP_MODULE_DIRECT_OUTPUT')) {
			self::Raise500Error('Smart App Module Direct Output must NOT be Defined outside controllers ...');
			return;
		} //end if
		//--
		//==
		//--
		$smartframeworkservice = ''; // special operation
		if(SmartFrameworkRegistry::issetRequestVar('smartframeworkservice') === true) {
			$smartframeworkservice = (string) strtolower((string)SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getRequestVar('smartframeworkservice')));
			switch((string)$smartframeworkservice) {
				case 'status':
					break;
				case 'debug':
				case 'debug-tpl':
				//	if(!SmartFrameworkRuntime::ifDebug()) {
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
			SmartFrameworkRuntime::outputHttpHeadersNoCache(); // headers: cache control, force no-cache
			echo self::ServiceStatus($the_midmark);
			//--
			return false; // break stop
			//--
		} //end if
		//--
		//== OVERALL AUTHENTICATION BREAKPOINT
		//--
		SmartAppBootstrap::Authenticate('index'); // if the auth uses session it may start now
		//--
		//== RAW OUTPUT FOR DEBUG
		//--
		if((string)$smartframeworkservice == 'debug') {
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				SmartFrameworkRuntime::outputHttpHeadersNoCache(); // headers: cache control, force no-cache
				echo self::DebugInfoGet('idx');
			} else {
				http_response_code(404);
				echo SmartComponents::http_message_404_notfound('NO DEBUG Service has been activated on this server ...');
			} //end if
			//--
			return false; // break stop
			//--
		} elseif((string)$smartframeworkservice == 'debug-tpl') {
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				SmartFrameworkRuntime::outputHttpHeadersNoCache(); // headers: cache control, force no-cache
				echo SmartDebugProfiler::display_marker_tpl_debug((string)SmartFrameworkRegistry::getRequestVar('tpl'));
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
			$reserved_controller_names = (array) Smart::list_to_array((string)SMART_FRAMEWORK_RESERVED_CONTROLLER_NAMES, true);
		} //end if
		//--
		$err404 = '';
		$arr = array();
		//--
		$page = (string) SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getRequestVar('page'));
		$page = (string) trim((string)str_replace(array('/', '\\', ':', '?', '&', '=', '%'), array('', '', '', '', '', '', ''), $page)); // fix for get as it automatically replaces . with _ (so, reverse), but also fix some invalid characters ...
		if((string)$page == '') {
			$page = (string) $configs['app']['index-home'];
		} //end if
		//--
		if(strpos($page, '.') !== false) { // page can be as (module.)controller / (module.)controller(.php|html|stml|json|...) / module.controller.some-indexing-words-for-better-seo(.php|html|stml|json|...)
			//--
			$arr = (array) explode('.', (string)$page, 3); // separe 1st and 2nd from the rest
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			if(in_array((string)$arr[1], (array)$reserved_controller_names)) {
				// Fix to integrate with friendly URLs SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE, if just controller.(.php|html|stml|json|...) has been provided
				$arr[1] = (string) trim((string)strtolower((string)$arr[0])); // controller
				$arr[0] = (string) trim((string)strtolower((string)$configs['app']['index-default-module'])); // get default module
			} else {
				$arr[0] = (string) trim((string)strtolower((string)$arr[0])); // module
				$arr[1] = (string) trim((string)strtolower((string)$arr[1])); // controller
			} //end if else
			//--
		} elseif((string)$configs['app']['index-default-module'] != '') {
			//--
			$arr[0] = (string) trim((string)strtolower((string)$configs['app']['index-default-module'])); // get default module
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
		$the_path_to_module = Smart::safe_pathname(SmartFileSysUtils::add_dir_last_slash('modules/mod-'.Smart::safe_filename($arr[0])));
		$the_module = Smart::safe_pathname($the_path_to_module.Smart::safe_filename($arr[1]).'.php');
		if(!SmartFileSystem::is_type_file($the_module)) {
			if((string)$err404 == '') {
				$err404 = 'Page does not exist: '.$page;
			} //end if
		} //end if
		//--
		if((string)$err404 != '') {
			self::Raise404Error((string)$err404);
			return;
		} //end if
		//--
		if((!SmartFileSysUtils::check_if_safe_path($the_path_to_module)) OR (!SmartFileSysUtils::check_if_safe_path($the_module))) {
			self::Raise400Error('Insecure Module Access for Page: '.$page);
			return;
		} //end if
		//--
		if((class_exists('SmartAppIndexController')) OR (class_exists('SmartAppAdminController'))) {
			self::Raise500Error('Module Class Runtimes must be defined only in modules ...');
			return;
		} //end if
		//--
		require((string)$the_module);
		//--
		if(((string)SMART_APP_MODULE_AREA !== 'INDEX') AND ((string)SMART_APP_MODULE_AREA !== 'SHARED')) {
			self::Raise403Error('Page Access Denied for Index Area: '.$page);
			return;
		} //end if
		if(defined('SMART_APP_MODULE_AUTH')) {
			if(SmartAuth::check_login() !== true) {
				self::Raise403Error('Page Access Denied ! No Authentication: '.$page);
				return;
			} //end if
			if(defined('SMART_APP_MODULE_REALM_AUTH')) {
				if((string)SmartAuth::get_login_realm() !== (string)SMART_APP_MODULE_REALM_AUTH) {
					self::Raise403Error('Page Access Denied ! Invalid Login Realm: '.$page);
					return;
				} //end if
			} //end if
		} //end if
		//--
		if(!class_exists('SmartAppIndexController')) {
			self::Raise500Error('Invalid Module Class Runtime for Page: '.$page);
			return;
		} //end if
		if(!is_subclass_of('SmartAppIndexController', 'SmartAbstractAppController')) {
			self::Raise500Error('Invalid Module Class Inheritance for Controller Page: '.$page);
			return;
		} //end if
		//--
		//== RUN THE MODULE
		//--
		$appModule = new SmartAppIndexController(
			$the_path_to_module,
			$the_controller_name,
			$page,
			'index'
		);
		//--
		if(!defined('SMART_APP_MODULE_DIRECT_OUTPUT') OR SMART_APP_MODULE_DIRECT_OUTPUT !== true) {
			ob_start();
		} //end if
		//--
		$appStatusCode = 0; // init (for PHP8)
		$appTestInit = $appModule->Initialize(); // mixed: null (void) / FALSE
		if($appTestInit !== false) {
			$appStatusCode = (int) $appModule->Run();
		} //end if
		$appModule->ShutDown();
		$appSettings = (array) $appModule->PageViewGetCfgs();
		if((isset($appSettings['status-code'])) AND ((int)$appSettings['status-code'] > 0)) { // {{{SYNC-SMART-FRAMEWORK-HANDLE-HTTP-STATUS-CODE}}}
			$appStatusCode = (int) $appSettings['status-code']; // this rewrites what the Run() function returns, which is very OK as this is authoritative !
		} //end if
		if($appTestInit === false) {
			if((int)$appStatusCode < 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
				$appStatusCode = 500; // if Initialize returns FALSE then expects also and explicit error code (>= 400) and perhaps a message set via $appSettings['status-code'] ; if none implemented, raise the 500 HTTP Status Code
			} //end if
		} //end if
		//--
		$appRawHeads = (array) $appModule->PageViewGetRawHeaders();
		$appData = (array) $appModule->PageViewGetVars();
		if(!defined('SMART_APP_MODULE_DIRECT_OUTPUT') OR SMART_APP_MODULE_DIRECT_OUTPUT !== true) {
			$ctrl_output = ob_get_contents();
			ob_end_clean();
			if((string)$ctrl_output != '') {
				Smart::log_warning('The middleware service '.$the_midmark.' detected an illegal output in the controller: '.$page."\n".'The result of this output is: '.$ctrl_output);
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
			if(((int)$appSettings['expires'] > 0) AND (!SmartFrameworkRuntime::ifDebug())) {
				SmartFrameworkRuntime::outputHttpHeadersNoCache((int)$appSettings['expires'], (int)$appSettings['modified'], (string)$appSettings['c-control']); // headers: cache expiration control
			} else {
				SmartFrameworkRuntime::outputHttpHeadersNoCache(); // headers: cache control, force no-cache
			} //end if else
		} //end if
		//--
		//== STATUS CODE {{{SYNC-SMART-HTTP-STATUS-CODES}}}
		//--
		switch((int)$appStatusCode) {
			//-- server errors
			case 504:
				self::Raise504Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 503:
				self::Raise503Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 502:
				self::Raise502Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 500:
				self::Raise500Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			//-- client errors
			case 429:
				self::Raise429Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 404:
				self::Raise404Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 403:
				self::Raise403Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 401:
				self::Raise401Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			case 400:
				self::Raise400Error((string)$appSettings['error'], (string)$appSettings['errhtml']);
				return;
				break;
			//-- redirect 3xx statuses
			case 301:
			case 302:
				if((string)$appSettings['redirect-url'] != '') { // expects a valid URL
					$the_redirect_link = '<a href="'.Smart::escape_html((string)$appSettings['redirect-url']).'">'.Smart::escape_html((string)$appSettings['redirect-url']).'</a>';
					if(headers_sent()) {
						Smart::log_warning('Headers Already Sent before Redirection: ['.$appStatusCode.'] ; URL: '.$appSettings['redirect-url']);
						self::Raise500Error('The app failed to Redirect to: '.$the_redirect_link);
						return;
					} //end if
					if((int)$appStatusCode == 301) {
						$the_redirect_text = 'Moved Permanently'; // permanent redirect for HTTP 1.0 / HTTP 1.1
						http_response_code(301);
					} else { // any other code will be interpreted as 302 (the default redirection in PHP)
						$the_redirect_text = 'Found'; // temporary redirect for HTTP 1.0 / HTTP 1.1
						http_response_code(302);
					} //end if else
					SmartFrameworkRuntime::outputHttpSafeHeader('Location: '.$appSettings['redirect-url']);
					echo '<h1>'.Smart::escape_html($the_redirect_text).'</h1>'.'<br>'.'If the page redirection fails, click on the below link:'.'<br>'.$the_redirect_link;
					return; // break stop
				} else {
					Smart::log_warning('Redirection HTTP Status ['.(int)$appStatusCode.'] was used in a page controller without a redirection URL ...');
				} //end if
				break;
			//-- extended 2xx statuses: NOTICE / WARNING / ERROR that can be used for REST / API
			case 208: // ERROR
				if(!headers_sent()) {
					http_response_code(208); // Already Reported (this should be used only as an alternate SUCCESS code instead of 200 for ERRORS)
				} else {
					Smart::log_warning('Headers Already Sent before 208 ...');
				} //end if else
				break;
			case 203: // WARNING
				if(!headers_sent()) {
					http_response_code(203); // Non-Authoritative Information (this should be used only as an alternate SUCCESS code instead of 200 for WARNINGS)
				} else {
					Smart::log_warning('Headers Already Sent before 203 ...');
				} //end if else
				break;
			case 202: // NOTICE
				if(!headers_sent()) {
					http_response_code(202); // Accepted (this should be used only as an alternate SUCCESS code instead of 200 for NOTICES)
				} else {
					Smart::log_warning('Headers Already Sent before 202 ...');
				} //end if else
				break;
			//-- DEFAULT: OK
			case 200:
			default: // any other codes not listed above are not supported and will be interpreted as 200
				// nothing to do here ...
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
			SmartFrameworkRuntime::outputHttpSafeHeader('Content-Length: '.((int)strlen((string)$appData['main']))); // must be strlen NOT SmartUnicode::str_len as it must get number of bytes not characters
			echo (string) $appData['main'];
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
				$the_template_path = Smart::safe_pathname(SmartFileSysUtils::add_dir_last_slash(trim((string)$appSettings['template-path'])));
			} //end if else
		} else { // use default template path
			$the_template_path = (string) trim((string)$configs['app']['index-template-path']);
			if((string)$the_template_path == '') {
				Smart::log_warning('Invalid Page Template Path In Config: `'.$the_template_path.'`');
				self::Raise500Error('Invalid Page Template Path. See the error log !');
				return;
			} //end if
			if((string)$the_template_path != '@') {
				$the_template_path = Smart::safe_pathname(SmartFileSysUtils::add_dir_last_slash($the_template_path));
			} //end if
		} //end if else
		//--
		if((isset($appSettings['template-file'])) AND ((string)trim((string)$appSettings['template-file']) != '')) {
			$the_template_file = Smart::safe_filename(trim((string)$appSettings['template-file']));
		} else { // use default template file
			$the_template_file = (string) trim((string)$configs['app']['index-template-file']);
			if((string)$the_template_file == '') {
				Smart::log_warning('Invalid Page Template File In Config: `'.$the_template_file.'`');
				self::Raise500Error('Invalid Page Template File. See the error log !');
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
		if(!SmartFileSysUtils::check_if_safe_path($the_template_path)) {
			Smart::log_warning('Invalid Page Template Path: '.$the_template_path);
			self::Raise500Error('Invalid Page Template Path. See the error log !');
			return;
		} //end if
		if(!SmartFileSystem::is_type_dir($the_template_path)) {
			Smart::log_warning('Page Template Path does not Exists: '.$the_template_path);
			self::Raise500Error('Page Template Path does not Exists. See the error log !');
			return;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_path($the_template_path.$the_template_file)) {
			Smart::log_warning('Invalid Page Template File: '.$the_template_path.$the_template_file);
			self::Raise500Error('Invalid Page Template File. See the error log !');
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($the_template_path.$the_template_file)) {
			Smart::log_warning('Page Template File does not Exists: '.$the_template_path.$the_template_file);
			self::Raise500Error('Page Template File does not Exists. See the error log !');
			return;
		} //end if
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			self::DebugInfoCookieSet('idx');
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
			echo "\n".'<!-- WebPage Server-Side Metrics '.Smart::escape_html($the_midmark).': '.str_pad('Total Execution Time = '.Smart::format_number_dec($res_time, 13, '.', '').' seconds', 55, ' ', STR_PAD_BOTH).' | '.str_pad('Memory Peak Usage = '.SmartUtils::pretty_print_bytes($res_memory, 2), 37, ' ', STR_PAD_BOTH).' -->'."\n";
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
