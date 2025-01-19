<?php
// [LIB - Smart.Framework / Debug Profiler]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart.Framework - Debug Profiler
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// Hint: for very advanced internal debugging define in etc/init.php the constant: SMART_FRAMEWORK_INTERNAL_DEBUG = true

/**
 * Class Smart Debug Profiler
 *
 * @access 		private
 * @internal
 *
 * @depends 	css: tpl-highlight.css ; classes: Smart, SmartComponents
 * @version 	v.20250107
 * @package 	Application:Development
 *
 */
final class SmartDebugProfiler {

	// ::

	private const TXT_PROTECT_PASS = '******* (passwords are not revealed)';
	private const TXT_PROTECT_KEYS = '+++++++ (keys are not revealed)';

	private static $extraDebuggers = [];


	//==================================================================
	public static function register_extra_debug_log(string $class_name, string $method_name) : bool {
		//--
		if(!SmartEnvironment::ifDebug()) {
			Smart::log_warning(__METHOD__.' # Debug is OFF when trying to register for Debug a Class/Method: `'.$class_name.'::'.$method_name.'`');
			return false;
		} //end if
		//--
		if(!is_array(self::$extraDebuggers)) {
			self::$extraDebuggers = [];
		} //end if
		//--
		if(((string)trim((string)$class_name) != '') AND ((string)trim((string)$method_name) != '')) {
			$the_key = (string) $class_name.'::'.$method_name.'()'; // supports only static calls
			if(!array_key_exists((string)$the_key, (array)self::$extraDebuggers)) {
				if(class_exists((string)$class_name)) {
					if(method_exists((string)$class_name, (string)$method_name)) {
						$reflect = new ReflectionMethod((string)$class_name, (string)$method_name);
						if($reflect->isStatic()) {
							self::$extraDebuggers[(string)$class_name.'::'.$method_name.'()'] = [
								'class' 	=> (string) $class_name,
								'method' 	=> (string) $method_name,
							];
						} else {
							Smart::log_warning(__METHOD__.' # Class Method is NOT STATIC: `'.$class_name.'::'.$method_name.'`');
							return false;
						} //end if else
					} else {
						Smart::log_warning(__METHOD__.' # Class Method DOES NOT EXISTS: `'.$class_name.'::'.$method_name.'`');
						return false;
					} //end if else
				} else {
					Smart::log_warning(__METHOD__.' # Class DOES NOT EXISTS: `'.$class_name.'`');
					return false;
				} //end if else
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function js_headers_debug(?string $y_profiler_url) : string {
		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return '';
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/templates/debug-profiler-head.inc.htm',
			[
				'BASE-URL' 				=> (string) SmartUtils::get_server_current_url(),
				'DEBUG-PROFILER-URL' 	=> (string) $y_profiler_url
			]
			// use caching, as default
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function div_main_debug() : string {
		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return '';
		} //end if
		//--
		return '<div id="SmartFramework__Debug__Profiler"></div>';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function save_debug_info(?string $y_area, ?string $y_debug_token, bool $is_main) : bool {

		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return false;
		} //end if
		//--

		//--
		if(((string)$y_area != 'idx') AND ((string)$y_area != 'adm') AND ((string)$y_area != 'tsk')) {
			return false;
		} //end if
		//--

		//--
		$y_debug_token = (string) trim((string)$y_debug_token);
		if((string)$y_debug_token == '') {
			return false;
		} //end if
		//-- the use of safe_filename() here is safe because the params are validated above
		$the_dir = 'tmp/logs/'.Smart::safe_filename($y_area).'/'.date('Y-m-d@H').'-debug-data/'.Smart::safe_filename($y_debug_token).'/';
		//-- #END# SYNC

		//--
		$the_request_uri = (string) SmartUtils::get_server_current_request_uri();
		//--

		//--
		if($is_main) {
			$the_file = $the_dir.'debug-main.log';
		} else {
			$the_file = $the_dir.'debug-sub-req-'.time().'-'.SmartHashCrypto::sha1($the_request_uri).'.log';
		} //end if else
		//--

		//--
		if(!SmartFileSystem::is_type_dir($the_dir)) {
			SmartFileSystem::dir_create($the_dir, true); // recursive
		} //end if
		//--
		if(SmartFileSystem::is_type_dir($the_dir)) {
			if(SmartFileSystem::have_access_write($the_dir)) {
				//-- generate extra debug info
				if(is_array(self::$extraDebuggers)) {
					foreach(self::$extraDebuggers as $key => $val) {
						if(Smart::array_size($val) > 0) {
							if((array_key_exists('class', $val)) AND (array_key_exists('method', $val))) {
								$run_class = (string) $val['class'];
								$run_method = (string) $val['method'];
								if(((string)$run_class != '') AND ((string)$run_method != '')) {
									if(class_exists((string)$run_class)) {
										if(method_exists((string)$run_class, (string)$run_method)) {
											$run_class::$run_method();
										} //end if
									} //end if
								} //end if
								$run_class = null;
								$run_method = null;
							} //end if
						} //end if
					} //end foreach
				} //end if
				//--
				$dbg_stats = (array) SmartEnvironment::getDebugMsgs('stats');
				//--
				$arr = array();
				$arr['date-time'] = (string) date('Y-m-d H:i:s O');
				$arr['debug-token'] = (string) $y_debug_token;
				$arr['is-request-main'] = $is_main;
				$arr['request-hash'] = (string) SmartHashCrypto::sha1((string)$the_request_uri);
				$arr['request-uri'] = (string) $the_request_uri;
				$arr['resources-time'] = $dbg_stats['time'];
				$arr['resources-memory'] = $dbg_stats['memory'];
				$arr['response-code'] = (int) http_response_code();
				$arr['response-headers'] = (string) Smart::b64_enc(Smart::seryalize((array)headers_list()));
				if(function_exists('getallheaders')) {
					$arr['request-headers'] = (string) Smart::b64_enc(Smart::seryalize((array)getallheaders()));
				} else {
					$arr['request-headers'] = (string) Smart::b64_enc(Smart::seryalize([]));
				} //end if else
				$arr['env-req-filtered'] = (string) Smart::b64_enc(Smart::seryalize((array)SmartFrameworkRegistry::getRequestVars()));
				$arr['env-get'] = (string) Smart::b64_enc(Smart::seryalize(is_array($_GET) ? (array)$_GET : []));
				$arr['env-post'] = (string) Smart::b64_enc(Smart::seryalize(is_array($_POST) ? (array)$_POST : []));
				$arr['env-cookies'] = (string) Smart::b64_enc(Smart::seryalize((array)SmartFrameworkRegistry::getCookieVars())); // reflect also cookies set in this request
				$arr['env-server'] = (string) Smart::b64_enc(Smart::seryalize((array)SmartFrameworkRegistry::getServerVars()));
				if(@session_status() === PHP_SESSION_ACTIVE) {
					$arr['php-session'] = (string) Smart::b64_enc(Smart::seryalize(is_array($_SESSION) ? (array)$_SESSION : []));
				} else {
					$arr['php-session'] = (string) Smart::b64_enc(Smart::seryalize(''));
				} //end if else
				if(SmartAuth::check_login() === true) {
					$arr['auth-data'] = array('is_auth' => true, 'login_data' => (array)SmartAuth::get_login_data(), '#login-pass-hash#', SmartAuth::get_auth_passhash());
				} else {
					$arr['auth-data'] = array('is_auth' => false, 'login_data' => []);
				} //end if else
				foreach((array)SmartEnvironment::getDebugMsgs('optimizations') as $key => $val) {
					$arr['log-optimizations'][(string)$key] = (string) Smart::b64_enc(Smart::seryalize((array)$val));
				} //end foreach
				foreach((array)SmartEnvironment::getDebugMsgs('extra') as $key => $val) {
					$arr['log-extra'][(string)$key] = (string) Smart::b64_enc(Smart::seryalize((array)$val));
				} //end foreach
				foreach((array)SmartEnvironment::getDebugMsgs('db') as $key => $val) {
					$arr['log-db'][(string)$key] = (string) Smart::b64_enc(Smart::seryalize((array)$val));
				} //end foreach
				if(Smart::array_size((array)SmartEnvironment::getDebugMsgs('mail')) > 0) {
					$arr['log-mail'] = (string) Smart::b64_enc(Smart::seryalize((array)SmartEnvironment::getDebugMsgs('mail')));
				} else {
					$arr['log-mail'] = '';
				} //end if else
				foreach((array)SmartEnvironment::getDebugMsgs('modules') as $key => $val) {
					$arr['log-modules'][(string)$key] = (string) Smart::b64_enc(Smart::seryalize((array)$val));
				} //end foreach
				//--
				SmartFileSystem::write((string)$the_file, (string)Smart::seryalize((array)$arr));
				//--
			} //end if
		} //end if
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function test_tpl_file_for_debug(?string $y_tpl_file) : bool {

		//--
		if(!SmartEnvironment::ifDebug()) {
			return false;
		} //end if
		//--

		//--
		$y_tpl_file = (string) trim((string)$y_tpl_file);
		if((string)$y_tpl_file == '') { // no file
			return false;
		} //end if
		//--

		//--
		if( // TODO: find a better logic here ... for now is OK
			((string)substr((string)$y_tpl_file, -9, 9) == '.htaccess') OR
			((string)substr((string)$y_tpl_file, -9, 9) == '.htpasswd') OR
			((string)substr((string)$y_tpl_file, -3, 3) == '.sh') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.log') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.pem') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.yml') OR
			((string)substr((string)$y_tpl_file, -5, 5) == '.yaml') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.sql') OR
			((string)substr((string)$y_tpl_file, -7, 7) == '.sqlite') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.png') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.jpg') OR
			((string)substr((string)$y_tpl_file, -5, 5) == '.jpeg') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.gif') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.webm') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.ini') OR
			((string)substr((string)$y_tpl_file, -4, 4) == '.php')
		) { // deny for the above files, they must be protected
			return false;
		} //end if
		//--

		//--
		if((strpos((string)$y_tpl_file, 'etc/') === 0) OR (strpos((string)$y_tpl_file, 'lib/') === 0) OR (strpos((string)$y_tpl_file, 'modules/') === 0)) {
			if(SmartFileSysUtils::checkIfSafePath((string)$y_tpl_file)) {
				if(SmartFileSysUtils::staticFileExists((string)$y_tpl_file)) {
					if(SmartFileSystem::is_type_file((string)$y_tpl_file)) {
						return true;
					} //end if
				} //end if
			} //end if
		} //end if
		//--

		//--
		return false;
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	// reads and returns the content of a generic TPL file template for debug (can be used to extend debuging over other TPL files other than Marker-TPL) {{{SYNC-DEBUG-TPL-FILES}}}
	public static function read_tpl_file_for_debug(?string $y_tpl_file) : array {

		//--
		if(!SmartEnvironment::ifDebug()) {
			return (array) [
				'dbg-file-name' 	=> (string) '',
				'dbg-file-contents' => (string) '',
				'error-msg' 		=> 'DEBUG is DISABLED',
			];
		} //end if
		//--

		//--
		$y_tpl_file = (string) trim((string)$y_tpl_file);
		if((string)$y_tpl_file == '') { // no file
			return (array) [
				'dbg-file-name' 	=> (string) '',
				'dbg-file-contents' => (string) '',
				'error-msg' 		=> 'EMPTY PATH provided',
			];
		} //end if
		//--

		//--
		$y_tpl_file = (string) self::url_tpl_decrypt((string)$y_tpl_file);
		//--

		//--
		if(self::test_tpl_file_for_debug((string)$y_tpl_file) !== true) {
			return (array) [
				'dbg-file-name' 	=> (string) '',
				'dbg-file-contents' => (string) '',
				'error-msg' 		=> 'INVALID FILE TYPE OR WRONG PATH, dissalowed',
			];
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_tpl_file)) {
			return (array) [
				'dbg-file-name' 	=> (string) '',
				'dbg-file-contents' => (string) '',
				'error-msg' 		=> 'FILE PATH IS UNSAFE',
			];
		} //end if
		//--
		if(!SmartFileSysUtils::staticFileExists((string)$y_tpl_file)) {
			return (array) [
				'dbg-file-name' 	=> (string) '',
				'dbg-file-contents' => (string) '',
				'error-msg' 		=> 'FILE DOES NOT EXISTS',
			];
		} //end if
		//--
		$fcontent = (string) SmartFileSysUtils::readStaticFile((string)$y_tpl_file);
		//--

		//--
		return (array) [
			'dbg-file-name' 	=> (string) $y_tpl_file,
			'dbg-file-contents' => (string) $fcontent,
			'error-msg' 		=> '', // no err
		];
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function display_debug_page(?string $title, ?string $content) : string {
		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return '';
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/templates/debug-profiler-util.htm',
			[
				'BASE-URL' 	=> (string) SmartUtils::get_server_current_url(),
				'CHARSET' 	=> (string) SmartUtils::get_encoding_charset(),
				'TITLE' 	=> (string) $title,
				'MAIN' 		=> (string) $content
			]
			// use caching, as default
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// reads and display a Marker-TPL file template for debug {{{SYNC-DEBUG-TPL-FILES}}}
	public static function display_marker_tpl_debug(?string $y_tpl_file, array $y_arr_sub_templates=[], bool $y_use_decrypt=true) : string {

		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return '';
		} //end if
		//--

		//--
		$y_tpl_file = (string) trim((string)$y_tpl_file);
		if((string)$y_tpl_file == '') {
			return '<h1>WARNING: Empty Marker-TPL Template to Debug</h1>';
		} //end if
		//--

		//--
		if($y_use_decrypt !== false) {
			$y_tpl_file = (string) self::url_tpl_decrypt((string)$y_tpl_file);
		} //end if
		//--

		//--
		if(self::test_tpl_file_for_debug((string)$y_tpl_file) === true) {
			$content = (string) SmartMarkersTemplating::analyze_debug_file_template((string)$y_tpl_file, (array)$y_arr_sub_templates);
			$content .= SmartFileSystem::read('lib/js/jshilitecode/templates/syntax-hilitecode-init.inc.htm');
			$content .= "\n".'<script>var SmartViewHelpersSyntaxHighlightAreas = \'div#tpl-display-for-highlight\';</script>'."\n";
			$content .= SmartFileSystem::read('lib/js/jshilitecode/templates/syntax-hilitecode-process.inc.htm');
			$content .= "\n".'<!-- SmartProfiler div-id:tpl-display-for-highlight -->'."\n";
		} else {
			$content = '<h1>WARNING: Invalid Marker-TPL Template to Debug: '.Smart::escape_html((string)$y_tpl_file).'</h1>';
		} //end if else
		//--

		//--
		return (string) self::display_debug_page(
			'### Marker-TPL ### Template Debug Profiling',
			(string) $content
		);
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	// return HTML Formatted Debug Messages
	// will no more echo because it can be used also with raw pages
	public static function print_debug_info(?string $y_area, ?string $y_debug_token) : string {

		global $configs;

		//-- {{{SYNC-DEBUG-DATA}}}
		if(!SmartEnvironment::ifDebug()) {
			return '';
		} //end if
		//--

		//--
		if(((string)$y_area != 'idx') AND ((string)$y_area != 'adm') AND ((string)$y_area != 'tsk')) {
			return '';
		} //end if
		//--
		$y_debug_token = trim((string)$y_debug_token);
		if((string)$y_debug_token == '') {
			return '';
		} //end if
		//--
		$the_dir = 'tmp/logs/'.Smart::safe_filename($y_area).'/'.date('Y-m-d@H').'-debug-data/'.Smart::safe_filename($y_debug_token).'/';
		//-- #END# SYNC

		//--
		$storage = (new SmartGetFileSystem(true))->get_storage($the_dir, true, false);
		$arr = array();
		if(is_array($storage['list-files'])) {
			$storage['list-files'] = Smart::array_sort($storage['list-files'], 'natsort');
			for($i=0; $i<Smart::array_size($storage['list-files']); $i++) {
				$arr[] = Smart::unseryalize(SmartFileSystem::read($storage['list-files'][$i]));
			} //end if
		} //end if
		$storage = array();
		//--

		//--
		$debug_response = '';
		$debug_resources = '';
		$debug_environment = '';
		$debug_session = '';
		$debug_auth = '';
		$debug_mail = '';
		$debug_dbqueries = '';
		$debug_optimizations = '';
		$debug_extra = '';
		$debug_modules = '';
		$tmp_decode_arr = array();
		//--
		$start_marker = '<div class="smartframework_debugbar_status smartframework_debugbar_status_title"><font size="5"><b># DEBUG Data :: ALL REQUESTS #</b></font></div>';
		$end_marker = '<div class="smartframework_debugbar_status smartframework_debugbar_status_title"><font size="3"><b># DEBUG # END #</b></font></div>';
		//--
		for($i=0; $i<Smart::array_size($arr); $i++) {
			//--
			if(((int)$arr[$i]['response-code'] > 299) AND ((int)$arr[$i]['response-code'] < 300)) { // redirects
				$status_style = 'smartframework_debugbar_status_token';
			} elseif((int)$arr[$i]['response-code'] > 399) { // error
				$status_style = 'smartframework_debugbar_status_warn';
			} else { // ok
				$status_style = 'smartframework_debugbar_status_head';
			} //end if else
			//--
			if($arr[$i]['is-request-main'] === true) {
				$txt_main = '<div class="smartframework_debugbar_status smartframework_debugbar_status_title"><font size="5"><b># DEBUG Data :: MAIN REQUEST #</b></font></div>';
			} else {
				$txt_main = '<div class="smartframework_debugbar_status smartframework_debugbar_status_title"><font size="3"><b># DEBUG Data :: SUB-REQUEST #</b></font></div>';
			} //end if else
			$txt_token = '<div class="smartframework_debugbar_status smartframework_debugbar_status_token" style="width: 50%;"><font size="2"><b>Debug Token: '.Smart::escape_html($arr[$i]['debug-token']).'</b></font></div>';
			$txt_url = '<div class="smartframework_debugbar_status smartframework_debugbar_status_url"><font size="2"><b>'.'<span class="'.Smart::escape_html((string)$status_style).'">'.(int)$arr[$i]['response-code'].'</span>'.'&nbsp;URL: '.Smart::escape_html((string)($arr[$i]['request-uri'] ?? '')).'</b></font></div>';
			//--
			$debug_response .= $txt_main.$txt_url.$txt_token.self::print_log_headers($arr[$i]['response-code'], Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['response-headers'])), Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['request-headers']))).'<hr>';
			//--
			$debug_resources .= $txt_main.$txt_url.$txt_token.self::print_log_resources($arr[$i]['resources-time'], $arr[$i]['resources-memory']);
			//--
			$debug_environment .= $txt_main.$txt_url.$txt_token.self::print_log_environment(Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['env-req-filtered'])), Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['env-cookies'])), Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['env-get'])), Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['env-post'])), Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['env-server']))).'<hr>';
			//--
			$debug_session .= $txt_main.$txt_url.$txt_token.self::print_log_session(Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['php-session']))).'<hr>';
			//--
			$debug_auth .= $txt_main.$txt_url.$txt_token.self::print_log_auth($arr[$i]['auth-data']).'<hr>';
			//--
			if(isset($arr[$i]['log-optimizations']) AND is_array($arr[$i]['log-optimizations'])) {
				$debug_optimizations .= $txt_main.$txt_url.$txt_token;
				foreach($arr[$i]['log-optimizations'] as $key => $val) {
					$debug_optimizations .= self::print_log_optimizations((string)strtoupper((string)$key), Smart::unseryalize((string)Smart::b64_dec((string)$val))).'<hr>';
				} //end foreach
			} //end if
			//--
			if(isset($arr[$i]['log-mail']) AND Smart::is_nscalar($arr[$i]['log-mail']) AND ((string)$arr[$i]['log-mail'] != '')) {
				$debug_mail .= $txt_main.$txt_url.$txt_token.self::print_log_mail(Smart::unseryalize((string)Smart::b64_dec((string)$arr[$i]['log-mail']))).'<hr>';
			} //end if
			//--
			if(isset($arr[$i]['log-db']) AND is_array($arr[$i]['log-db'])) {
				$debug_dbqueries .= $txt_main.$txt_url.$txt_token;
				foreach($arr[$i]['log-db'] as $key => $val) {
					$debug_dbqueries .= self::print_log_database((string)strtoupper((string)$key), Smart::unseryalize((string)Smart::b64_dec((string)$val))).'<hr>';
				} //end foreach
			} //end if
			//--
			if(isset($arr[$i]['log-extra']) AND is_array($arr[$i]['log-extra'])) {
				$debug_extra .= $txt_main.$txt_url.$txt_token;
				foreach($arr[$i]['log-extra'] as $key => $val) {
					$debug_extra .= self::print_log_extra((string)strtoupper((string)$key), Smart::unseryalize((string)Smart::b64_dec((string)$val))).'<hr>';
				} //end foreach
			} //end if
			//--
			if(isset($arr[$i]['log-modules']) AND is_array($arr[$i]['log-modules'])) {
				$debug_modules .= $txt_main.$txt_url.$txt_token;
				foreach($arr[$i]['log-modules'] as $key => $val) {
					$debug_modules .= self::print_log_modules((string)strtoupper((string)$key), Smart::unseryalize((string)Smart::b64_dec((string)$val))).'<hr>';
				} //end foreach
			} //end if
			//--
		} //end for
		//--
		if((string)$debug_optimizations == '') {
			$debug_optimizations = '<div class="smartframework_debugbar_status smartframework_debugbar_status_nodata"><font size="5"><b>Optimization Hints: N/A</b></font></div>';
		} else {
			$debug_optimizations .= $end_marker;
		} //end if else
		//--
		if((string)$debug_mail == '') {
			$debug_mail = '<div class="smartframework_debugbar_status smartframework_debugbar_status_nodata"><font size="5"><b>Mail Debug: No data</b></font></div>';
		} else {
			$debug_mail .= $end_marker;
		} //end if else
		//--
		if((string)$debug_dbqueries == '') {
			$debug_dbqueries = '<div class="smartframework_debugbar_status smartframework_debugbar_status_nodata"><font size="5"><b>Database Debug: No Queries found</b></font></div>';
		} else {
			$debug_dbqueries .= $end_marker;
		} //end if else
		//--
		if((string)$debug_extra == '') {
			$debug_extra = '<div class="smartframework_debugbar_status smartframework_debugbar_status_nodata"><font size="5"><b>Extra Debug: No data</b></font></div>';
		} else {
			$debug_extra .= $end_marker;
		} //end if else
		//--
		if((string)$debug_modules == '') {
			$debug_modules = '<div class="smartframework_debugbar_status smartframework_debugbar_status_nodata"><font size="5"><b>Modules Debug: No data</b></font></div>';
		} else {
			$debug_modules .= $end_marker;
		} //end if else
		//--

		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/templates/debug-profiler-footer.inc.htm',
			[
				'BASE-URL' 				=> (string) SmartUtils::get_server_current_url(),
				'DEBUG-TIME' 			=> (string) date('Y-m-d H:i:s O'),
				'DEBUG-RUNTIME' 		=> (string) $start_marker.self::print_log_runtime().$end_marker,
				'DEBUG-CONFIGS' 		=> (string) $start_marker.self::print_log_configs().$end_marker,
				'DEBUG-RESOURCES' 		=> (string) $debug_resources.$end_marker,
				'DEBUG-HEADERS' 		=> (string) $debug_response.$end_marker,
				'DEBUG-ENVIRONMENT' 	=> (string) $debug_environment.$end_marker,
				'DEBUG-SESSION' 		=> (string) $debug_session.$end_marker,
				'DEBUG-AUTH' 			=> (string) $debug_auth.$end_marker,
				'DEBUG-OPTIMIZATIONS' 	=> (string) $debug_optimizations,
				'DEBUG-MAIL' 			=> (string) $debug_mail,
				'DEBUG-DATABASE' 		=> (string) $debug_dbqueries,
				'DEBUG-EXTRA' 			=> (string) $debug_extra,
				'DEBUG-MODULES' 		=> (string) $debug_modules
			]
			// use caching, as default
		);
		//--


	} //END FUNCTION
	//==================================================================


	//===== PRIVATES


	//==================================================================
	private static function url_tpl_decrypt(?string $y_tpl_file) : string {
		//--
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$the_area = 'task';
			} else {
				$the_area = 'admin';
			} //end if else
		} else {
			$the_area = 'index';
		} //end if else
		//--
		$y_tpl_file = (string) SmartCipherCrypto::decrypt(
			(string)$y_tpl_file, // data
			(string)$the_area.' '.SMART_FRAMEWORK_SECURITY_KEY.' '.SMART_SOFTWARE_NAMESPACE // key
		);
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_tpl_file)) {
			$y_tpl_file = '';
		} //end if
		//--
		return (string) $y_tpl_file;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function url_tpl_encrypt(?string $y_tpl_file) : string {
		//--
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$the_area = 'task';
			} else {
				$the_area = 'admin';
			} //end if else
		} else {
			$the_area = 'index';
		} //end if else
		//--
		return (string) SmartCipherCrypto::encrypt(
			(string)$y_tpl_file, // data
			(string)$the_area.' '.SMART_FRAMEWORK_SECURITY_KEY.' '.SMART_SOFTWARE_NAMESPACE // key
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_mail($log_mail_arr) : string {
		//--
		// $log_mail_arr is MIXED !! comes from unserialization, unpredictable ...
		//--
		$log = '';
		//--
		$max = Smart::array_size($log_mail_arr);
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>MAIL Log</b></font></div>';
		//--
		if(is_array($log_mail_arr) AND ((int)$max > 0)) {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			//--
			foreach($log_mail_arr as $key => $val) {
				//--
				$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Operation: <b>'.strtoupper((string)$key).'</b></div>';
				$log .= '<div class="smartframework_debugbar_inforow" style="font-size:11px; color:#000000;">';
				if(is_array($val)) {
					$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), trim((string)implode("\n\n==========\n\n", $val)))), true).'</pre>';
				} else {
					$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), trim((string)SmartUtils::pretty_print_var($val)))), true).'</pre>';
				} //end if else
				$log .= '</div>';
				//--
			} //end foreach
			//--
		} else {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 100px; text-align: center;"><font size="2"><b>N/A</b></font></div>';
			//--
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_runtime() : string {
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>Client / Server :: RUNTIME Log</b></font></div>';
		//--
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$the_area = 'task';
			} else {
				$the_area = 'admin';
			} //end if else
		} else {
			$the_area = 'index';
		} //end if else
		//--
		$arr_ident = (array) SmartUtils::get_os_browser_ip();
		$arr_bw = (array) SmartComponents::get_imgdesc_by_bw_id((string)$arr_ident['bw']);
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>Client Runtime: Info</b></div>';
		$log .= '<div class="smartframework_debugbar_inforow">'.'<div style="display:inline-block; margin-right:20px; margin-bottom:10px; margin-top:10px; margin-left:5px;"><img src="'.Smart::escape_html(SmartUtils::get_server_current_url().(string)$arr_bw['img']).'" width="64" height="64" alt="'.Smart::escape_html((string)$arr_bw['img']).'" title="'.Smart::escape_html($arr_bw['img']).'"></div> <div style="display:inline-block;"><b>Browser User-Agent Signature:</b> '.Smart::escape_html((string)SmartUtils::get_visitor_useragent()).'<br><b>Browser ID / Browser Class / Client OS:</b> '.Smart::escape_html((string)$arr_ident['bw'].' / '.(string)$arr_ident['bc'].' / '.(string)$arr_ident['os']).'<br><b>Browser Is Mobile:</b> '.Smart::escape_html((string)$arr_ident['mobile']).'<br><b>Client IP / Client Proxy IP:</b> '.Smart::escape_html((string)$arr_ident['ip'].' / '.(trim((string)$arr_ident['px']) ? (string)$arr_ident['px'] : '-')).'</div>'.'</div>';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>Server App Runtime: Powered by</b></div>';
		$log .= '<div class="smartframework_debugbar_inforow">';
		$log .= (string) SmartComponents::app_powered_info(
			'yes',
			[], // no extra plugins
			false, // don't exclude db plugins
			false, // don't display watch
			true // display logo
		);
		$log .= '</div>';
		//--
		$arr = [
			'Server Runtime: Smart.Framework' => [
				'Smart.Framework Middleware Area' => $the_area,
				'Smart.Framework Release / Tag / Branch' => SMART_FRAMEWORK_RELEASE_VERSION.' / '.SMART_FRAMEWORK_RELEASE_TAGVERSION.' / '.SMART_FRAMEWORK_VERSION,
				'Smart.Framework Encoding: Internal / DB' => SMART_FRAMEWORK_CHARSET.' / '.SMART_FRAMEWORK_SQL_CHARSET
			],
			'Server Domain: Info' => [
				'Server Full URL' => SmartUtils::get_server_current_url(),
				'Server Script' => SmartUtils::get_server_current_script(),
				'Server IP' => SmartUtils::get_server_current_ip(),
				'Server Port' => SmartUtils::get_server_current_port(),
				'Server Protocol' => SmartUtils::get_server_current_protocol(),
				'Server Path' => SmartUtils::get_server_current_path(),
				'Server Domain' => SmartUtils::get_server_current_domain_name(),
				'Server Base Domain' => SmartUtils::get_server_current_basedomain_name()
			],
			'Server Runtime: PHP' => [
				'PHP OS' => (string) PHP_OS,
				'PHP Server API' => (string) PHP_SAPI, //.@php_sapi_name(),
				'PHP Version' => (string) 'PHP '.PHP_VERSION,
				'PHP Build (debug or release): ' => ((PHP_DEBUG !== 0) ? 'DEBUG' : 'RELEASE'),
				'PHP ZTS: ' => (string) ((PHP_ZTS !== 0) ? 'ON' : 'OFF'),
				'PHP End Of Line' => (string) (((string)PHP_EOL === "\n") ? '\n' : ( ((string)PHP_EOL === "\r\n") ? '\r\n' : '\r' )),
				'PHP Locales: ' => (string) setlocale(LC_ALL, 0),
				'PHP Encoding: Internal / MBString' => (string) ini_get('default_charset').' / '.@mb_internal_encoding(),
				'PHP Memory' => (string) ini_get('memory_limit'),
				'PHP Max File Descriptors (System Calls)' => (string) (defined('PHP_FD_SETSIZE') ? PHP_FD_SETSIZE : 'undefined'), // available just since PHP 7.1
				'PHP Max Path Length: ' => (string) PHP_MAXPATHLEN,
				'PHP Integer Size: ' => (string) PHP_INT_SIZE,
				'PHP Integer Max: ' => (string) PHP_INT_MAX,
				'PHP Integer Min: ' => (string) (defined('PHP_INT_MIN') ? PHP_INT_MIN : 'undefined'), // available just since PHP 7.0
				'PHP Loaded Modules (Extensions)' => (string) strtolower(implode(', ', (array)@get_loaded_extensions())),
				'PHP INI Settings (App Local)' => (array) ini_get_all(null, false)
			]
		];
		//--
		foreach($arr as $debug_key => $debug_val) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>'.Smart::escape_html($debug_key).'</b></div>';
			if(is_array($debug_val)) {
				$log .= '<table cellspacing="0" cellpadding="2" width="100%">';
				foreach($debug_val as $key => $val) {
					$pfx = '';
					$sfx = '';
					if(is_array($val)) {
						$pfx = '<pre style="max-width: 70vw !important; word-break: break-all !important;">';
						$sfx = '</pre>';
					} //end if
					$log .= '<tr valign="top"><td width="295"><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($key).'</b></div></td><td><div class="smartframework_debugbar_inforow">'.$pfx.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($val)), true).$sfx.'</div></td></tr>';
				} //end foreach
				$log .= '</table>';
			} else {
				$log .= '<div class="smartframework_debugbar_inforow">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$debug_val), true).'</div>';
			} //end if else
		} //end foreach
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_configs() : string {
		//--
		global $configs, $languages;
		//--
		$log = '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>Application :: CONFIGURATION Log</b></font></div>';
		//-- vars
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>App CONFIG VARIABLES</b></div>';
		$arr = (array) $configs;
		ksort($arr);
		$i=0;
		$j=0;
		foreach((array)$arr as $key => $val) {
			//--
			$i++;
			//--
			$log .= '<table cellspacing="0" cellpadding="2" width="100%">';
			$log .= '<tr valign="top" title="#'.$i.'"><td width="195"><div class="smartframework_debugbar_inforow">';
			$log .= '<b>'.Smart::escape_html((string)$key).'</b>';
			$log .= '</div></td><td><div class="smartframework_debugbar_inforow">';
			if(is_array($val)) {
				$log .= '<table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size:13px;">';
				$j=0;
				foreach($val as $k => $v) {
					if(stripos((string)$k, 'pass') !== false) {
						$v = (string) self::TXT_PROTECT_PASS; // avoid display passwords as clear text
					} elseif(stripos((string)$k, 'key') !== false) {
						$v = '['.(int)strlen((string)$v).']'.self::TXT_PROTECT_KEYS; // avoid display keys as clear text
					} //end if
					$j++;
					if($j % 2) {
						$color = '#FFFFFF';
					} else {
						$color = '#FAFAFA';
					} //end if else
					$log .= '<tr bgcolor="'.$color.'" valign="top" title="#'.$i.'.'.$j.'"><td width="290"><b>'.Smart::escape_html((string)$k).'</b></td><td><pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($v)), true).'</pre></td></tr>';
				} //end foreach
				$log .= '</table>';
			} else {
				if(stripos((string)$key, 'pass') !== false) {
					$val = (string) self::TXT_PROTECT_PASS; // avoid display passwords as clear text
				} elseif(stripos((string)$key, 'key') !== false) {
					$val = '['.(int)strlen((string)$val).']'.self::TXT_PROTECT_KEYS; // avoid display keys as clear text
				} //end if
				$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$val), true).'</pre>';
			} //end if else
			$log .= '</div></td></tr>';
			$log .= '</table>';
			//--
		} //end foreach
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>App REGIONAL LANGUAGES</b></div>';
		$arr = (array) $languages;
		ksort($arr);
		$i=0;
		$j=0;
		foreach((array)$arr as $key => $val) {
			//--
			$i++;
			//--
			$log .= '<table cellspacing="0" cellpadding="2" width="100%">';
			$log .= '<tr valign="top" title="#'.$i.'"><td width="195"><div class="smartframework_debugbar_inforow">';
			$log .= '<b>'.Smart::escape_html((string)$key).'</b>';
			$log .= '</div></td><td><div class="smartframework_debugbar_inforow">';
			if(is_array($val)) {
				$log .= '<table width="100%" cellpadding="1" cellspacing="0" border="0" style="font-size:13px;">';
				$j=0;
				foreach($val as $k => $v) {
					$j++;
					if($j % 2) {
						$color = '#FFFFFF';
					} else {
						$color = '#FAFAFA';
					} //end if else
					$log .= '<tr bgcolor="'.$color.'" valign="top" title="#'.$i.'.'.$j.'"><td width="290"><b>'.Smart::escape_html((string)$k).'</b></td><td><pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($v)), true).'</pre></td></tr>';
				} //end foreach
				$log .= '</table>';
			} else {
				$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$val), true).'</pre>';
			} //end if else
			$log .= '</div></td></tr>';
			$log .= '</table>';
			//--
		} //end foreach
		//-- constants
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>App SETTINGS CONSTANTS</b></div>';
		$arr = (array) get_defined_constants(true);
		$arr = (array) $arr['user'];
		ksort($arr);
		$i=0;
		$j=0;
		foreach((array)$arr as $key => $val) {
			//--
			$i++;
			//--
			if(((string)$key == 'SMART_FRAMEWORK_CHMOD_DIRS') OR ((string)$key == 'SMART_FRAMEWORK_CHMOD_FILES')) {
				if(is_int($val)) {
					$val = (string) str_pad((string)decoct((int)$val), 4, '0', STR_PAD_LEFT).' (octal)';
				} else {
					$val = (string) $val.' (!!! Warning, Invalid ... Must be NUMERIC / OCTAL !!!)';
				} //end if
			} elseif(stripos((string)$key, 'PASS') !== false) {
				$val = (string) self::TXT_PROTECT_PASS; // avoid display passwords as clear text
			} elseif(stripos((string)$key, 'KEY') !== false) {
				$val = '['.(int)strlen((string)$val).']'.self::TXT_PROTECT_KEYS; // avoid display keys as clear text
			} //end if
			//--
			$log .= '<table cellspacing="0" cellpadding="2" width="100%">';
			$log .= '<tr valign="top" title="#'.$i.'"><td width="375"><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html((string)$key).'</b></div></td><td><div class="smartframework_debugbar_inforow">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::nl_2_br(Smart::escape_html(SmartUtils::pretty_print_var($val))), true).'</div></td></tr>';
			$log .= '</table>';
			//--
		} //end foreach
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_resources($time_res, $mem_res) : string {
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>Script Execution :: RESOURCES Log</b></font></div>';
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>Script Execution Resources</b></div>';
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_inforow" style="width:450px;">Execution Time: <b>'.Smart::format_number_dec($time_res, 13, '.', '').' sec.'.'</b></div>';
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_inforow" style="width:450px;">Execution Memory: <b>'.SmartUtils::pretty_print_bytes((int)$mem_res, 2).'</b></div>';
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_headers($response_code, $response_heads_arr, $request_heads_arr) : string {
		//--
		$log = '';
		//--
		$status_code_msg = (string) SmartFrameworkRuntime::GetStatusMessageByStatusCode((int)$response_code);
		//--
		if($response_code >= 400) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn"><font size="4"><b>RESPONSE Headers: [ HTTP Status Code = '.Smart::escape_html($response_code).' / '.Smart::escape_html($status_code_msg).' ]</b></font></div>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>RESPONSE Headers: [ HTTP Status Code = '.Smart::escape_html($response_code).' / '.Smart::escape_html($status_code_msg).' ]</b></font></div>';
		} //end if else
		$max = Smart::array_size($response_heads_arr);
		if(is_array($response_heads_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($response_heads_arr as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td style="min-width: 25px;"><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($debug_val), true).'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 250px; text-align: center;"><font size="2"><b>RESPONSE Headers are Empty</b></font></div>';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>REQUEST Headers</b></font></div>';
		$max = Smart::array_size($request_heads_arr);
		if(is_array($request_heads_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($request_heads_arr as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td style="min-width: 150px;"><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($debug_val), true).'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 250px; text-align: center;"><font size="2"><b>REQUEST Headers are Empty</b></font></div>';
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_environment($req_filtered, $cookies_arr, $get_arr, $post_arr, $server_arr) : string {
		//--
		$log = '';
		//--
		$filter_strings = 'Non-Filtered';
		if(defined('SMART_FRAMEWORK_SECURITY_FILTER_INPUT')) {
			$filter_strings = 'Filtered: `'.Smart::escape_html((string)SMART_FRAMEWORK_SECURITY_FILTER_INPUT).'`';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>REQUEST SemanticURL/GET/POST Vars :: '.$filter_strings.'</b></font></div>';
		$max = Smart::array_size($req_filtered);
		if(is_array($req_filtered) AND ((int)$max > 0)) {
			$tbl = '<table cellspacing="0" cellpadding="2">';
			$cnt = 0;
			foreach($req_filtered as $debug_key => $debug_val) {
				$cnt++;
				$tbl .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($debug_val)), true).'</font></div></td></tr>';
			} //end foreach
			$tbl .= '</table>';
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total (Non-Empty) Variables: <b>'.Smart::escape_html($cnt).'</b></div>';
			$log .= $tbl;
			$tbl = '';
			$cnt = 0;
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width: 250px; text-align: center;"><font size="2"><b>No Variables Found</b></font></div>';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>COOKIE Vars</b></font></div>';
		$max = Smart::array_size($cookies_arr);
		if(is_array($cookies_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Variables: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($cookies_arr as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($debug_val), true).'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width: 250px; text-align: center;"><font size="2"><b>No Cookies Found</b></font></div>';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>(Raw) GET Vars</b></font></div>';
		$max = Smart::array_size($get_arr);
		if(is_array($get_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Variables: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($get_arr as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($debug_val)), true).'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width: 250px; text-align: center;"><font size="2"><b>No GET Vars Found</b></font></div>';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>(Raw) POST Vars</b></font></div>';
		$max = Smart::array_size($post_arr);
		if(is_array($post_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Variables: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($post_arr as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($debug_val)), true).'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width: 250px; text-align: center;"><font size="2"><b>No POST Vars Found</b></font></div>';
		} //end if
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>SERVER Vars</b></font></div>';
		$max = Smart::array_size($server_arr);
		if(is_array($server_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Variables: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($server_arr as $debug_key => $debug_val) {
				if((string)$debug_key == 'PHP_AUTH_PW') {
					$debug_val = (string) self::TXT_PROTECT_PASS;
				} //end if
				$log .= '<tr valign="top"><td style="min-width: 200px;"><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.($debug_val ? SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($debug_val), true) : '&nbsp;').'</font></div></td></tr>';
			} //end foreach
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 250px; text-align: center;"><font size="2"><b>Cannot find any SERVER Variable</b></font></div>';
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_session($session_arr) : string {
		//--
		$log = '';
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>SESSION Vars</b></font></div>';
		$max = Smart::array_size($session_arr);
		if(is_array($session_arr) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Variables: <b>'.Smart::escape_html($max).'</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($session_arr as $debug_key => $debug_val) {
				if((is_array($debug_val)) OR (is_object($debug_val))) {
					$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><font color="#333333"><b>'.Smart::escape_html($debug_key).'</b></font></div></td><td><div class="smartframework_debugbar_inforow"><pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($debug_val)), true).'</pre></div></td></tr>';
				} else {
					$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($debug_val), true).'</font></div></td></tr>';
				} //end if else
			} //end foreach
			$log .= '</table>';
		} elseif(is_array($session_arr)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 250px; text-align: center;"><font size="2"><b>Session contains NO Variables</b></font></div>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width: 250px; text-align: center;"><font size="2"><b>Session Not Started</b></font></div>';
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_auth($auth_arr) : string {
		//--
		if(!is_array($auth_arr)) {
			$auth_arr = [];
		} //end if
		//--
		$is_auth = (bool) ($auth_arr['is_auth'] ?? null);
		$login_data = (array) ((isset($auth_arr['login_data']) AND is_array($auth_arr['login_data'])) ? $auth_arr['login_data'] : []);
		//--
		$log = '';
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>Authentication Info</b></font></div>';
		//--
		if(is_array($login_data) AND ($is_auth === true)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;"><b>Authenticated :: OK</b></div>';
			$log .= '<table cellspacing="0" cellpadding="2">';
			foreach($login_data as $debug_key => $debug_val) {
				$log .= '<tr valign="top"><td><div class="smartframework_debugbar_inforow"><b>'.Smart::escape_html($debug_key).'</b></div></td><td><div class="smartframework_debugbar_inforow"><font color="#000000">';
				if(is_array($debug_val)) {
					$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var($debug_val)), true).'</pre>';
				} else {
					if((string)$debug_key == 'auth:passhash') {
						$debug_val = (string) self::TXT_PROTECT_PASS; // avoid display pass as clear text
					} elseif((string)$debug_key == 'user:privkey') {
						$debug_val = '['.(int)strlen((string)$debug_val).']'.self::TXT_PROTECT_KEYS; // avoid display pass as clear text
					} //end if
					$log .= SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$debug_val), true);
				} //end if else
				$log .= '</font></div></td></tr>';
			} //end for
			$log .= '</table>';
		} else {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 250px; text-align: center;"><font size="2"><b>Not Authenticated</b></font></div>';
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_database(?string $title, $db_log) : string {
		//--
		if(!is_array($db_log)) {
			$db_log = [];
		} //end if
		if((!isset($db_log['log'])) OR (!is_array($db_log['log']))) {
			$db_log['log'] = [];
		} //end if
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>'.Smart::escape_html((string)$title).' :: DATABASE Queries</b></font></div>';
		//--
		$max = (int) Smart::array_size($db_log['log']);
		if(is_array($db_log) AND ((int)$max > 0)) {
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Queries Number: <b>'.Smart::escape_html(Smart::format_number_int((array_key_exists('total-queries', $db_log) ? $db_log['total-queries'] : 0), '+')).'</b></div>';
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Queries Time: <b>'.Smart::escape_html(Smart::format_number_dec((array_key_exists('total-time', $db_log) ? $db_log['total-time'] : 0), 9, '.', '')).' sec.'.'</b></div>';
			$num = 0;
			for($i=0; $i<$max; $i++) {
				//--
				$tmp_arr = (array) $db_log['log'][$i];
				if(!array_key_exists('type', (array)$tmp_arr)) {
					$tmp_arr['type'] = null;
				} //end if
				//--
				switch((string)$tmp_arr['type']) {
					case 'transaction':
						//--
						$num++;
						//--
						$tmp_color = '#339900';
						//--
						$log .= '<div class="smartframework_debugbar_inforow" style="font-size:12px; color:'.$tmp_color.';">';
						$log .= $num.'. '.(array_key_exists('data', (array)$tmp_arr) ? '<b>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$tmp_arr['data']), true).'</b>' : '');
						if(array_key_exists('connection', (array)$tmp_arr) AND ((string)$tmp_arr['connection'] != '')) {
							$log .= ' @ '.Smart::escape_html($tmp_arr['connection']);
						} //end if
						if(array_key_exists('time', (array)$tmp_arr) AND ((float)$tmp_arr['time'] > 0)) {
							$log .= '<br><span style="padding:1px;"><b>@Time: '.Smart::format_number_dec($tmp_arr['time'], 9, '.', '').' sec.</b></span>';
						} //end if
						if(array_key_exists('query', (array)$tmp_arr) AND ((string)$tmp_arr['query'] != '')) {
							$log .= '<br><span class="smartframework_debugbar_status_highlight" style="padding:1px;"><b>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($tmp_arr['query']), true).'</b></span>';
						} //end if
						$log .= '</div>';
						//--
						break;
					case 'special':
					case 'set':
					case 'count':
					case 'read':
					case 'write':
					case 'sql':
					case 'nosql':
						//--
						if(!array_key_exists('type', $tmp_arr)) {
							$tmp_arr['type'] = null;
						} //end if
						if(!array_key_exists('connection', $tmp_arr)) {
							$tmp_arr['connection'] = null;
						} //end if
						if(!array_key_exists('query', $tmp_arr)) {
							$tmp_arr['query'] = null;
						} //end if
						if(!array_key_exists('params', $tmp_arr)) {
							$tmp_arr['params'] = null;
						} //end if
						if(!array_key_exists('command', $tmp_arr)) {
							$tmp_arr['command'] = null;
						} //end if
						if(!array_key_exists('rows', $tmp_arr)) {
							$tmp_arr['rows'] = null;
						} //end if
						if(!array_key_exists('skip-count', $tmp_arr)) {
							$tmp_arr['skip-count'] = null;
						} //end if
						if(!array_key_exists('time', $tmp_arr)) {
							$tmp_arr['time'] = null;
						} //end if
						//--
						if((string)$tmp_arr['skip-count'] != 'yes') {
							$num++;
						} //end if
						//--
						if((string)$tmp_arr['type'] == 'count') {
							$tmp_color = '#557788';
						} elseif((string)$tmp_arr['type'] == 'read') {
							$tmp_color = '#665599';
						} elseif((string)$tmp_arr['type'] == 'write') {
							$tmp_color = '#113377';
						} elseif((string)$tmp_arr['type'] == 'set') {
							$tmp_color = '#444444';
						} elseif((string)$tmp_arr['type'] == 'special') {
							$tmp_color = '#FF7700';
						} else { // nosql
							$tmp_color = '#111111';
						} //end if else
						//--
						$log .= '<div class="smartframework_debugbar_inforow" style="font-size:11px; color:'.$tmp_color.';">';
						if((string)$tmp_arr['skip-count'] != 'yes') {
							$log .= $num.'. ';
						} //end if else
						$log .= '<b>'.Smart::escape_html($tmp_arr['data']).'</b>';
						if((string)$tmp_arr['connection'] != '') {
							$log .= ' @ '.Smart::escape_html($tmp_arr['connection']);
						} //end if
						$log .= '<br>';
						if($tmp_arr['time'] > 0) {
							if($tmp_arr['time'] <= $db_log['slow-time']) {
								$log .= '<span style="padding:1px;"><b>@Time: '.Smart::format_number_dec($tmp_arr['time'], 9, '.', '').' sec.</b></span>';
							} else {
								$log .= '<span class="smartframework_debugbar_status_warn" style="padding:1px;" title="Slow-Time: '.Smart::escape_html($db_log['slow-time']).'"><b>@Time: '.Smart::format_number_dec($tmp_arr['time'], 9, '.', '').' sec.'.'</b></span>';
							} //end if else
						} //end if
						$is_command_mode = false;
						if(is_array($tmp_arr['command'])) {
							$datmod = 'DATA-SETS';
							$is_command_mode = true;
						} elseif((string)$tmp_arr['command'] != '') {
							$datmod = 'DATA-SIZE';
							$is_command_mode = true;
						} else {
							$datmod = 'ROWS';
						} //end if else
						if((string)$tmp_arr['rows'] != '') {
							if($tmp_arr['time'] > 0) {
								$log .= ' &nbsp;/&nbsp; ';
							} //end if
							if((string)$tmp_arr['type'] == 'count') {
								$log .= '<i>'.'MATCHED '.$datmod.': '.(int)$tmp_arr['rows'].'</i>';
							} elseif((string)$tmp_arr['type'] == 'read') {
								$log .= '<i>'.'RETURNED '.$datmod.': '.(int)$tmp_arr['rows'].'</i>';
							} elseif((string)$tmp_arr['type'] == 'write') {
								$log .= '<i>'.((isset($tmp_arr['wsize']) && ($tmp_arr['wsize'] === true)) ? 'LENGTH' : (($is_command_mode === true) ? 'MODIFIED' : 'AFFECTED')).' '.$datmod.': '.(int)$tmp_arr['rows'].'</i>';
							} else {
								$log .= '<i>'.'#'.$datmod.': '.(int)$tmp_arr['rows'].'</i>';
							} //end if
						} //end if
						if((string)$tmp_arr['query'] != '') {
							if((int)strlen((string)$tmp_arr['query']) > 8192) {
								$tmp_arr['query'] = (string) Smart::text_cut_by_limit((string)$tmp_arr['query'], 7168, true, '[%%%COMMENT%%%]... query is too long to display all here ...[%%%/COMMENT%%%]').substr((string)$tmp_arr['query'], -1024, 1024);
							} //end
							$log .= '<br>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$tmp_arr['query']), true);
							if(Smart::array_size($tmp_arr['params']) > 0) {
								$tmp_params = array();
								foreach($tmp_arr['params'] as $key => $val) {
									$tmp_param_key = (string) (is_numeric($key) ? '$'.((int)$key + 1) : $key);
									$tmp_params[] = SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html((string)$tmp_param_key.' : '.SmartUtils::pretty_print_var($val)), true);
								} //end foreach
								$log .= '<br>'.'@PARAMS:&nbsp;{ '.implode(', ', $tmp_params).' }';
								$tmp_params = array();
							} //end if
						} //end if
						if(is_array($tmp_arr['command'])) {
							$log .= '<br>'.'@COMMAND-PARAMS:&nbsp;( <pre style="display:inline; color:'.$tmp_color.';">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(SmartUtils::pretty_print_var((array)$tmp_arr['command'])), true).' )</pre>';
						} elseif((string)$tmp_arr['command'] != '') {
							$log .= '<br>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::nl_2_br(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), (string)$tmp_arr['command']))), true);
						} //end if
						$log .= '</div>';
						//--
						break;
					case 'open-close':
					case 'metainfo':
					default:
						//--
						if((string)$tmp_arr['type'] == 'open-close') {
							$tmp_color = '#4285F4';
						} elseif((string)$tmp_arr['type'] == 'metainfo') {
							$tmp_color = '#CCCCCC';
						} else {
							$tmp_color = '#000000';
						} //end if else
						//--
						$log .= '<div class="smartframework_debugbar_inforow" style="font-size:12px; color:'.$tmp_color.';">';
						$log .= '<b>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($tmp_arr['data']), true).'</b>';
						if(array_key_exists('connection', (array)$tmp_arr) AND ((string)$tmp_arr['connection'] != '')) {
							$log .= ' @ '.Smart::escape_html($tmp_arr['connection']);
						} //end if
						$log .= '</div>';
						//--
				} //end switch
				//--
			} //end for
			//--
		} else {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 100px; text-align: center;"><font size="2"><b>N/A</b></font></div>';
			//--
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_optimizations(?string $title, $optimizations_log) : string {
		//--
		if(!is_array($optimizations_log)) {
			$optimizations_log = [];
		} //end if
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>'.Smart::escape_html((string)$title).' :: OPTIMIZATIONS Log</b></font></div>';
		//--
		$max = (int) Smart::array_size($optimizations_log);
		if(is_array($optimizations_log) AND ((int)$max > 0)) {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			//--
			for($i=0; $i<$max; $i++) {
				//--
				$tmp_item = array(); // init
				$tmp_arr = (array) $optimizations_log[$i];
				//--
				$log .= '<div class="smartframework_debugbar_inforow" style="font-size:11px; color:#000000;">';
				$log .= '<b>'.Smart::escape_html((string)$tmp_arr['title']).'</b><br>';
				if(isset($tmp_arr['data']) AND (Smart::array_size($tmp_arr['data']) > 0)) {
					for($j=0; $j<Smart::array_size($tmp_arr['data']); $j++) {
						$tmp_item = $tmp_arr['data'][$j];
						if(is_array($tmp_item)) {
							$tmp_line = '# '.($tmp_item['value'] ?? null).' # '.($tmp_item['key'] ?? null).' # '.($tmp_item['msg'] ?? null);
							$color = '#555555';
							if(isset($tmp_item['optimal'])) {
								if($tmp_item['optimal'] === false) {
									$color = '#F5926C';
								} elseif($tmp_item['optimal'] === true) {
									$color = '#3FA325';
								} //end if else
							} //end if
							$have_link = false;
							if(isset($tmp_item['action'])) {
								if((string)$tmp_item['action'] == 'debug-tpl') {
									$have_link = true;
									$log .= '<a title="Click to View the Marker-TPL Template Debug Profiling" href="'.Smart::escape_html(SmartUtils::get_server_current_script()).'?smartframeworkservice=debug-tpl&tpl='.Smart::escape_url(self::url_tpl_encrypt(trim((string)$tmp_item['key']))).'" target="_blank" style="text-decoration-style:dotted; text-decoration-color:'.$color.';">';
								} elseif((string)trim((string)$tmp_item['action']) != '') {
									$have_link = true;
									$log .= '<a title="Click to View the Extended Debug Profiling" href="'.Smart::escape_html((string)trim((string)$tmp_item['action']).Smart::escape_url(self::url_tpl_encrypt(trim((string)$tmp_item['key'])))).'" target="_blank" style="text-decoration-style:dashed; text-decoration-color:'.$color.';">';
								} //end if
							} //end if
							$log .= '<span style="font-size:11px; color:'.$color.';">'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), $tmp_line)), true).'</span><br>';
							if($have_link) {
								$log .= '</a>';
							} //end if
						} //end if
					} //end for
				} else {
					$log .= '<span style="font-size:11px; color:#333333;">N/A</span>';
				} //end if else
				$log .= '</div>';
				//--
			} //end for
			//--
		} else {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 100px; text-align: center;"><font size="2"><b>N/A</b></font></div>';
			//--
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_extra(?string $title, $extra_log) : string {
		//--
		if(!is_array($extra_log)) {
			$extra_log = [];
		} //end if
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>'.Smart::escape_html((string)$title).' :: EXTRA Log</b></font></div>';
		//--
		$max = (int) Smart::array_size($extra_log);
		if(is_array($extra_log) AND ((int)$max > 0)) {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			//--
			for($i=0; $i<$max; $i++) {
				//--
				$tmp_arr = (array) $extra_log[$i];
				//--
				$log .= '<div class="smartframework_debugbar_inforow" style="font-size:11px; color:#000000;">';
				$log .= '<b>'.Smart::escape_html((string)$tmp_arr['title']).'</b><br>';
				$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), (string)trim((string)$tmp_arr['data']))), true).'</pre>';
				$log .= '</div>';
				//--
			} //end for
			//--
		} else {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 100px; text-align: center;"><font size="2"><b>N/A</b></font></div>';
			//--
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function print_log_modules(?string $title, $modules_log) : string {
		//--
		if(!is_array($modules_log)) {
			$modules_log = [];
		} //end if
		//--
		$log = '';
		//--
		$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_head"><font size="4"><b>'.Smart::escape_html((string)$title).' :: MODULE Log</b></font></div>';
		//--
		$max = (int) Smart::array_size($modules_log);
		if(is_array($modules_log) AND ((int)$max > 0)) {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_highlight" style="width:450px;">Total Entries: <b>'.Smart::escape_html($max).'</b></div>';
			//--
			for($i=0; $i<$max; $i++) {
				//--
				$tmp_arr = (array) $modules_log[$i];
				//--
				$log .= '<div class="smartframework_debugbar_inforow" style="font-size:11px; color:#000000;">';
				$log .= '<b>'.Smart::escape_html((string)$tmp_arr['title']).'</b><br>';
				$log .= '<pre>'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html(str_replace(array("\r\n", "\r", "\t"), array("\n", "\n", ' '), trim((string)$tmp_arr['data']))), true).'</pre>';
				$log .= '</div>';
				//--
			} //end for
			//--
		} else {
			//--
			$log .= '<div class="smartframework_debugbar_status smartframework_debugbar_status_warn" style="width: 100px; text-align: center;"><font size="2"><b>N/A</b></font></div>';
			//--
		} //end if
		//--
		return (string) $log;
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
