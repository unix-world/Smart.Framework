<?php
// Smart.Framework / Abstract Middleware
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


// requires: SMART_FRAMEWORK_RELEASE_MIDDLEWARE

//--
if(defined('SMART_APP_TEMPLATES_DIR')) {
	@http_response_code(500);
	die('A Reserved Constant have been already defined: SMART_APP_TEMPLATES_DIR');
} //end if
define('SMART_APP_TEMPLATES_DIR', 'etc/templates/'); // App Templates Dir
//--

//==================================================================================
//================================================================================== CLASS START
//==================================================================================

// [REGEX-SAFE-OK]

/**
 * Class Smart.Framework Abstract Middleware
 *
 * It must contain ONLY public functions to avoid late state binding (self:: vs static::)
 *
 * @access 		private
 * @internal
 * @ignore		THIS CLASS IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
 *
 * @version		20210421
 *
 */
abstract class SmartAbstractAppMiddleware {

	// :: ABSTRACT
	// {{{SYNC-SMART-HTTP-STATUS-CODES}}}

	private static $LANGUAGE_DETECTED = null;

	private static $DEBUG_COOKIE_DATA = '';

	private const DEBUG_COOKIE_IDX = 'SmartFramework__DebugIdxID';
	private const DEBUG_COOKIE_ADM = 'SmartFramework__DebugAdmID';


	//=====
	public static function Run() {
		// THIS HAVE TO IMPLEMENT THE MIDDLEWARE SERVICE HANDLER (MANDATORY)
	} //END FUNCTION
	//=====


	//======================================================================
	final public static function SetRawHeaders($headers) {
		//--
		if(!is_array($headers)) {
			$headers = array();
		} //end if
		//--
		if(!headers_sent()) {
			//--
			foreach($headers as $key => $val) {
				//--
				if(((string)$key != '') AND ((string)$val != '')) {
					$hdr = (string) trim((string)$key.': '.(string)$val);
					SmartFrameworkRuntime::outputHttpSafeHeader((string)$hdr); // set raw header key => value
				} //end if
				//--
			} //end foreach
			//--
		} else {
			//--
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before RawHeaders');
			//--
		} //end if else
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise400Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(400);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 400 ...');
		} //end if else
		die(SmartComponents::http_message_400_badrequest((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise401Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(401);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 401 ...');
		} //end if else
		die(SmartComponents::http_message_401_unauthorized((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise403Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(403);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 403 ...');
		} //end if else
		die(SmartComponents::http_message_403_forbidden((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise404Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(404);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 404 ...');
		} //end if else
		die(SmartComponents::http_message_404_notfound((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise429Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(429);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 429 ...');
		} //end if else
		die(SmartComponents::http_message_429_toomanyrequests((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise500Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(500);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 500 ...');
		} //end if else
		die(SmartComponents::http_message_500_internalerror((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise502Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(502);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 502 ...');
		} //end if else
		die(SmartComponents::http_message_502_badgateway((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise503Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(503);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 503 ...');
		} //end if else
		die(SmartComponents::http_message_503_serviceunavailable((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function Raise504Error($y_msg, $y_htmlmsg='') {
		//--
		if(!headers_sent()) {
			http_response_code(504);
		} else {
			Smart::log_warning('WARNING: AppMiddleware :: Headers Already Sent before 504 ...');
		} //end if else
		die(SmartComponents::http_message_504_gatewaytimeout((string)$y_msg, (string)$y_htmlmsg));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function DetectInputLanguage() {
		//--
		if(self::$LANGUAGE_DETECTED !== null) {
			return (bool) self::$LANGUAGE_DETECTED;
		} //end if
		//--
		$lang = ''; // init
		//--
		if(
			(!defined('SMART_FRAMEWORK_URL_PARAM_LANGUAGE')) OR
			(SMART_FRAMEWORK_URL_PARAM_LANGUAGE == '') OR // this is the default case
			((string)trim((string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE) == '') OR // this if was wrong set
			(!preg_match('/^[a-z]{1,10}$/', (string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE)) // {{{SYNC-APP-URL-LANG-PARAM}}} ; if not empty may contain only characters: [a-z]
		) {
			self::$LANGUAGE_DETECTED = false;
			return (bool) self::$LANGUAGE_DETECTED;
		} //end if
		//-- prefer from URL
		$lang = (string) trim((string)SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getRequestVar((string)SMART_FRAMEWORK_URL_PARAM_LANGUAGE, '', (array)SmartTextTranslations::getAvailableLanguages())));
		//-- if not from URL, try cookie
		if((string)$lang == '') {
			if(!defined('SMART_APP_LANG_COOKIE')) {
				self::$LANGUAGE_DETECTED = false;
				return (bool) self::$LANGUAGE_DETECTED;
			} //end if
			if((string)trim((string)SMART_APP_LANG_COOKIE) == '') {
				self::$LANGUAGE_DETECTED = false;
				return (bool) self::$LANGUAGE_DETECTED;
			} //end if
			$lang = (string) trim((string)SmartUnicode::utf8_to_iso((string)SmartFrameworkRegistry::getCookieVar((string)SMART_APP_LANG_COOKIE)));
			if((string)$lang != '') {
				if(!in_array((string)$lang, (array)SmartTextTranslations::getAvailableLanguages())) {
					$lang = ''; // allow from subset as get request var
				} //end if
			} //end if
		} //end if
		//--
		if((string)$lang != '') {
			if(SmartTextTranslations::validateLanguage($lang) !== true) {
				$lang = ''; // dissalow invalid languages
			} //end if
		} //end if
		//--
		if((string)$lang != '') {
			self::$LANGUAGE_DETECTED = (bool) SmartTextTranslations::setLanguage((string)$lang);
		} else {
			self::$LANGUAGE_DETECTED = false;
		} //end if
		//--
		return (bool) self::$LANGUAGE_DETECTED;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	// This will handle the file downloads. The file PACKET will be sent to this function.
	// The PACKET (containing the File Download URL) is a data packet that have a structure like (see below: PACKET-STRUCTURE).
	// All PACKETS are signed with an AccessKey based on a unique key SMART_FRAMEWORK_SECURITY_KEY, so they cant't be guessed or reversed.
	// Event in the case that the AccessKey could be guessed, there is a two factor security layer that contains another key: UniqueKey (the unique client key, generated by the IP address and the unique browser signature).
	// So the two factor security combination (secret server key: AccessKey based on SMART_FRAMEWORK_SECURITY_KEY / almost unique client key: UniqueKey) will assure enough protection.
	// when used, the execution script must die('') after to avoid injections of extra content ...
	// the nocache headers must be set before using this
	// it returns the downloaded file path on success or empty string on error.
	final public static function DownloadsHandler($encrypted_download_pack, $controller_key) {
		//--
		$encrypted_download_pack = (string) $encrypted_download_pack;
		$controller_key = (string) $controller_key;
		//--
		$client_signature = (string) SmartUtils::get_visitor_signature();
		//--
		if((string)SMART_APP_VISITOR_COOKIE == '') {
			Smart::log_info('File Download', 'Failed: 400 / Invalid Visitor Cookie'.' on Client: '.$client_signature);
			self::Raise400Error('ERROR: Invalid Visitor UUID. Cookies must be enabled to enable this feature !');
			return '';
		} //end if
		//--
		$downloaded_file = ''; // init
		//--
		$decoded_download_packet = (string) SmartUtils::decode_download_link((string)$encrypted_download_pack);
		//--
		if((string)$decoded_download_packet != '') { // if data is corrupted, decrypt checksum does not match, will return an empty string
			//--
			if(SmartFrameworkRuntime::isAdminArea() === true) { // {{{SYNC-DWN-CTRL-PREFIX}}}
				$controller_key = (string) 'AdminArea/'.$controller_key;
			} else {
				$controller_key = (string) 'IndexArea/'.$controller_key;
			} //end if
			//-- {{{SYNC-DOWNLOAD-ENCRYPT-ARR}}}
			$arr_metadata = explode("\n", (string)$decoded_download_packet, 6); // only need first 5 parts
			//print_r($arr_metadata);
			// #PACKET-STRUCTURE# [we will have an array like below, according with the: SmartUtils::create_download_link()]
			// [TimedAccess]\n
			// [FilePath]\n
			// [AccessKey]\n
			// [UniqueKey]\n
			// [SFR.UA]\n
			// #END#
			//--
			$crrtime 	= (string) trim((string)(isset($arr_metadata[0]) ? $arr_metadata[0] : ''));
			$filepath 	= (string) trim((string)(isset($arr_metadata[1]) ? $arr_metadata[1] : ''));
			$access_key = (string) trim((string)(isset($arr_metadata[2]) ? $arr_metadata[2] : ''));
			$unique_key = (string) trim((string)(isset($arr_metadata[3]) ? $arr_metadata[3] : ''));
			$arr_metadata = array(); // clear
			//--
			$timed_hours = 1; // default expire in 1 hour
			if(defined('SMART_FRAMEWORK_DOWNLOAD_EXPIRE')) {
				if((int)SMART_FRAMEWORK_DOWNLOAD_EXPIRE > 0) {
					if((int)SMART_FRAMEWORK_DOWNLOAD_EXPIRE <= 24) { // max is 24 hours (since download link is bind to unique browser signature + unique cookie ... make non-sense to keep more)
						$timed_hours = (int) SMART_FRAMEWORK_DOWNLOAD_EXPIRE;
					} //end if
				} //end if
			} //end if
			//--
			if((int)$timed_hours > 0) {
				if((int)$crrtime < (int)(time() - (60 * 60 * $timed_hours))) {
					Smart::log_info('File Download', 'Failed: 403 / Download expired at: '.date('Y-m-d H:i:s O', (int)$crrtime).' for: '.$filepath.' on Client: '.$client_signature);
					self::Raise403Error('ERROR: The Access Key for this Download is Expired !');
					return '';
				} //end if
			} //end if
			//--
			if((string)$access_key != (string)sha1('DownloadLink:'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.SMART_APP_VISITOR_COOKIE.':'.$filepath.'^'.$controller_key)) {
				Smart::log_info('File Download', 'Failed: 403 / Invalid Access Key for: '.$filepath.' on Client: '.$client_signature);
				self::Raise403Error('ERROR: Invalid Access Key for this Download !');
				return '';
			} //end if
			//--
			if((string)$unique_key != (string)SmartHashCrypto::sha1('Time='.$crrtime.'#'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_key.'-'.SmartUtils::unique_auth_client_private_key().':'.$filepath.'+'.$controller_key)) {
				Smart::log_info('File Download', 'Failed: 403 / Invalid Client (Unique) Key for: '.$filepath.' on Client: '.$client_signature);
				self::Raise403Error('ERROR: Invalid Client Key to Access this Download !');
				return '';
			} //end if
			//--
			if(SmartFileSysUtils::check_if_safe_path($filepath)) {
				//--
				$skip_log = 'no'; // default log
				if(defined('SMART_FRAMEWORK_DOWNLOAD_SKIP_LOG')) {
					$skip_log = 'yes'; // do not log if accessed via admin area and user is authenticated
				} //end if
				//--
				$tmp_file_ext = (string) strtolower(SmartFileSysUtils::get_file_extension_from_path($filepath)); // [OK]
				$tmp_file_name = (string) strtolower(SmartFileSysUtils::get_file_name_from_path($filepath));
				//--
				$tmp_eval = SmartFileSysUtils::mime_eval($tmp_file_name);
				$mime_type = (string) $tmp_eval[0];
				$mime_disp = (string) $tmp_eval[1];
				//-- the path must not start with / but this is tested below
				$tmp_arr_paths = (array) explode('/', $filepath, 2); // only need 1st part for testing
				//-- allow file downloads just from specific folders like wpub/ or wsys/ (this is a very important security fix to dissalow any downloads that are not in the specific folders)
				if(((string)substr((string)$filepath, 0, 1) != '/') AND (strpos((string)SMART_FRAMEWORK_DOWNLOAD_FOLDERS, '<'.trim((string)(isset($tmp_arr_paths[0]) ? $tmp_arr_paths[0] : '')).'>') !== false) AND (stripos((string)SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, '<'.$tmp_file_ext.'>') === false)) {
					//--
					SmartFileSysUtils::raise_error_if_unsafe_path($filepath); // re-test finally
					//-- no need to clear the stat cache as the following checks will do it
					if(SmartFileSystem::is_type_file($filepath) AND SmartFileSystem::have_access_read($filepath)) {
						//--
						if(!headers_sent()) {
							//--
							$fsize = (int) SmartFileSystem::get_file_size($filepath);
							//--
							if(($fsize <= 0) OR (!SmartFileSystem::have_access_read($filepath))) {
								//--
								Smart::log_info('File Download', 'Failed: 404 / The requested File is Empty or Not Readable: '.$filepath.' on Client: '.$client_signature);
								self::Raise404Error('WARNING: The requested File is Empty or Not Readable !');
								return '';
								//--
							} //end if
							//-- set max execution time to zero
							ini_set('max_execution_time', 0); // we can expect a long time if file is big, but this will be anyway overriden by the WebServer Timeout Directive
							//--
							// cache headers are presumed to be sent by runtime before of this step
							//--
							SmartFrameworkRuntime::outputHttpSafeHeader('Content-Type: '.$mime_type);
							SmartFrameworkRuntime::outputHttpSafeHeader('Content-Disposition: '.$mime_disp);
							SmartFrameworkRuntime::outputHttpSafeHeader('Content-Length: '.$fsize);
							//--
							if(ob_get_level()) {
								ob_end_flush(); // fix to avoid get out of memory with big files
							} //end if
							@readfile((string)$filepath); // output without reading all in memory
							//--
						} else {
							//--
							Smart::log_info('File Download', 'Failed: 500 / Headers Already Sent: '.$filepath.' on Client: '.$client_signature);
							self::Raise500Error('ERROR: Download Failed, Headers Already Sent !');
							return '';
							//--
						} //end if else
						//--
						if((string)$skip_log != 'yes') {
							//--
							$downloaded_file = (string) $filepath; // return the file name to be logged
							//--
						} //end if
						//--
					} else {
						//--
						Smart::log_info('File Download', 'Failed: 404 / The requested File does not Exists or is Not Accessible: '.$filepath.' on Client: '.$client_signature);
						self::Raise404Error('WARNING: The requested File for Download does not Exists or is Not Accessible !');
						return '';
						//--
					} //end if else
				} else {
					//--
					Smart::log_info('File Download', 'Failed: 403 / Access to this File is Denied: '.$filepath.' on Client: '.$client_signature);
					self::Raise403Error('ERROR: Download Access to this File is Denied !');
					return '';
					//--
				} //end if else
				//--
			} else {
				//--
				Smart::log_info('File Download', 'Failed: 400 / Unsafe File Path: '.$filepath.' on Client: '.$client_signature);
				self::Raise400Error('ERROR: Unsafe Download File Path !');
				return '';
				//--
			} //end if else
			//--
		} else {
			//--
			Smart::log_info('File Download', 'Failed: 400 / Invalid Data Packet'.' on Client: '.$client_signature);
			self::Raise400Error('ERROR: Invalid Download Data Packet !');
			return '';
			//--
		} //end if else
		//--
		return (string) $downloaded_file;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function ServiceStatus($the_midmark) {
		//--
		if(SmartFrameworkRuntime::isAdminArea() === true) {
			$txt_area = 'Admin';
		} else {
			$txt_area = 'Index';
		} //end if else
		//--
		if(defined('SMART_SOFTWARE_DISABLE_STATUS_POWERED') AND (SMART_SOFTWARE_DISABLE_STATUS_POWERED === true)) {
			$html_status_powered_info = '';
		} else {
			$html_status_powered_info = (string) SmartComponents::app_powered_info('no');
		} //end if else
		//--
		return (string) SmartComponents::http_status_message('OK :: '.Smart::escape_html($txt_area).' / Service Available', '<script>setTimeout(function(){ self.location = self.location; }, 60000);</script><img height="32" src="lib/framework/img/loading-bars.svg"><div><h2 style="display:inline;">'.date('Y-m-d H:i:s O').' // Smart.Framework :: '.Smart::escape_html($the_midmark).'</h2></div><br>'.$html_status_powered_info.'<br>');
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function DebugInfoCookieSet($area) {
		//--
		if(!SmartFrameworkRuntime::ifDebug()) {
			return false;
		} //end if
		//--
		if((string)self::$DEBUG_COOKIE_DATA != '') {
			return true;
		} //end if
		//--
		$area = (string) $area;
		//--
		switch((string)$area) {
			case 'idx':
				$cookie_name = (string) self::DEBUG_COOKIE_IDX;
				break;
			case 'adm':
				$cookie_name = (string) self::DEBUG_COOKIE_ADM;
				break;
			default:
				return false;
		} //end switch
		if((string)$cookie_name == '') {
			return false;
		} //end if
		//--
		self::$DEBUG_COOKIE_DATA = (string) $area.'-'.Smart::uuid_32();
		//--
		return (bool) SmartUtils::set_cookie((string)$cookie_name, (string)self::$DEBUG_COOKIE_DATA);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function DebugInfoGet($area) {
		//--
		if(!SmartFrameworkRuntime::ifDebug()) {
			return '';
		} //end if
		//--
		$area = (string) $area;
		//--
		switch((string)$area) {
			case 'idx':
				$cookie_name = (string) self::DEBUG_COOKIE_IDX;
				break;
			case 'adm':
				$cookie_name = (string) self::DEBUG_COOKIE_ADM;
				break;
			default:
				return false;
		} //end switch
		if((string)$cookie_name == '') {
			return '';
		} //end if
		//--
		$cookie_data = (string) trim((string)SmartUtils::get_cookie((string)$cookie_name));
		if((string)$cookie_data == '') {
			return '';
		} //end if
		//--
		return (string) SmartDebugProfiler::print_debug_info((string)$area, (string)$cookie_data);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	final public static function DebugInfoSet($area, $is_main) {
		//--
		if(!SmartFrameworkRuntime::ifDebug()) {
			return false;
		} //end if
		//--
		$area = (string) $area;
		$is_main = (bool) $is_main;
		$req_with = '';
		if(array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)) {
			$req_with = (string) $_SERVER['HTTP_X_REQUESTED_WITH'];
		} //end if
		if(((int)http_response_code() > 299) OR ((string)$req_with != '')) {
			$is_main = false;
		} //end if
		//--
		switch((string)$area) {
			case 'idx':
				$cookie_name = (string) self::DEBUG_COOKIE_IDX;
				break;
			case 'adm':
				$cookie_name = (string) self::DEBUG_COOKIE_ADM;
				break;
			default:
				return false;
		} //end switch
		if((string)$cookie_name == '') {
			return false;
		} //end if
		//--
		$cookie_data = (string) self::$DEBUG_COOKIE_DATA;
		if((string)$cookie_data == '') {
			$cookie_data = (string) SmartUtils::get_cookie((string)$cookie_name);
			if((string)$cookie_data == '') {
				return false;
			} //end if
		} //end if
		//-- {{{SYNC-RESOURCES}}}
		if(function_exists('memory_get_peak_usage')) {
			$res_memory = (int) @memory_get_peak_usage(false);
		} else {
			$res_memory = -1; // unknown
		} //end if else
		$res_time = (float) (microtime(true) - (float)SMART_FRAMEWORK_RUNTIME_READY);
		//-- #END-SYNC
		SmartFrameworkRegistry::setDebugMsg('stats', 'memory', $res_memory); // bytes
		SmartFrameworkRegistry::setDebugMsg('stats', 'time', $res_time); // seconds
		SmartDebugProfiler::save_debug_info((string)$area, (string)$cookie_data, (bool)$is_main);
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//==================================================================================
//================================================================================== CLASS END
//==================================================================================


// end of php code
