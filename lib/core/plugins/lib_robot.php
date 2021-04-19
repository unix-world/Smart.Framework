<?php
// [LIB - Smart.Framework / Plugins / (HTTP) Robot]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - (HTTP) Robot:
// DEPENDS:
//	* Smart::
//	* SmartUtils::
//	* SmartDetectImages::
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartRobot - Easy to use HTTP Robot.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20210412
 * @package 	Plugins:Network
 *
 */
final class SmartRobot {

	// ::


	//================================================================
	/**
	 * Load URL IMG (svg / png / gif / jpg) Content (if relative path to a file will be prefixed with current URL for security reasons)
	 *
	 * @param STRING 	$y_img_link					:: relative/path/to/image (assumes current URL as prefix) | http(s)://some.url:port/path/to/image (port is optional) ; works also with Data-URL (Data-Image only)
	 * @param YES/NO	$y_allow_set_credentials	:: DEFAULT IS SET to NO ; if YES must be set just for internal URLs ; if set to AUTO will try to detect if can trust based on admin.php / index.php local framework scripts ; if the $y_url_or_path to get is detected to be under current URL will send also the Unique / session IDs ; more if detected that is from admin.php and if this is set to YES will send the HTTP-BASIC Auth credentials if detected (using YES with other URLs than Smart.Framework's current URL can be a serious SECURITY ISSUE, so don't !)
	 * @return ARRAY
	 */
	public static function load_url_img_content($y_img_link, $y_allow_set_credentials='no') {
		//--
		// ### IMPORTANT ###
		// BECAUSE OF SECURITY CONCERNS, NEVER USE OR MODIFY THIS FUNCTION TO LOAD A FILE PATH
		// ALL FILE PATHS WILL BE CONSIDERED AS URL PATHS AND WILL BE PREFIXED WITH CURRENT BASE URL TO FORCE ACCESSING ALL FILES VIA HTTP(S) REQUESTS TO AVOID BYPASS THAT SECURITY !!!
		// #################
		//--
		$tmp_imglink = (string) trim((string)$y_img_link);
		$tmp_fake_fname = '';
		$tmp_fcontent = '';
		//--
		$out_arr = [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
			'client' 			=> (string) __METHOD__,
			'log' 				=> '',
			'extension' 		=> '',
			'filename' 			=> '',
			'mode' 				=> 'file', // DO NOT CHANGE !!
			'result' 			=> 0,
			'code' 				=> '400', // HTTP 400 BAD REQUEST
			'headers' 			=> '',
			'content' 			=> '',
			'browse-url-info' 	=> [],
			'debuglog' 			=> ''
		];
		//--
		if((string)$tmp_imglink != '') {
			//-- {{{SYNC-LOAD-URL-OR-FILE-OR-IMG}}}
			$tmp_arr_trust_reference = (array) self::get_url_or_path_trust_reference((string)$tmp_imglink);
			$tmp_fixed_url_or_path 	= (string) $tmp_arr_trust_reference['url-or-path-fixed'];
			$tmp_url_or_path_type 	= (string) $tmp_arr_trust_reference['url-or-path-type'];
			$tmp_allow_credentials 	= (string) $tmp_arr_trust_reference['allow-credentials'];
			$tmp_trust_headers 		= (string) $tmp_arr_trust_reference['trust-headers'];
			$tmp_arr_trust_reference = null;
			//--
			$tmp_imglink = (string) $tmp_fixed_url_or_path;
			//--
			switch((string)$y_allow_set_credentials) {
				case 'no':
					$tmp_allow_credentials = 'no';
					break;
				case 'yes':
					$tmp_trust_headers = 'yes';
					break;
				case 'auto':
				default:
					if((string)$tmp_allow_credentials == 'yes') {
						$y_allow_set_credentials = 'yes';
						$tmp_trust_headers = 'yes';
					} else {
						$y_allow_set_credentials = 'no';
					} //end if else
			} //end switch
			//--
			$is_ok = false;
			switch((string)$tmp_url_or_path_type) {
				case 'url':
				case 'data-url':
					$is_ok = true;
					break;
				case '!empty!':
				case '!unknown!':
				default:
					$is_ok = false;
			} //end switch
			//--
			$out_arr['log'] = 'INF: Type='.$tmp_url_or_path_type."\n";
			//--
			if($is_ok === true) {
				//--
				$tmp_browse_arr = (array) self::load_url_content((string)$tmp_imglink, (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT, 'GET', '', '', '', (string)$tmp_allow_credentials); // [OK]
				//--
				$out_arr['mode'] 			= (string) $tmp_browse_arr['mode'];
				$out_arr['result'] 			= (string) $tmp_browse_arr['result'];
				$out_arr['code'] 			= (string) $tmp_browse_arr['code'];
				$out_arr['headers'] 		= (string) $tmp_browse_arr['headers'];
				$out_arr['browse-url-info'] = (array)  $tmp_browse_arr['browse-url-info'];
				//--
				if(!array_key_exists('debuglog', $out_arr)) {
					$out_arr['debuglog'] = '';
				} //end if
				$out_arr['debuglog'] .= (string) $tmp_browse_arr['debuglog'];
				//--
				if(!array_key_exists('log', $out_arr)) {
					$out_arr['log'] = '';
				} //end if
				$out_arr['log'] .= (string) $tmp_browse_arr['log'];
				//--
				if($tmp_browse_arr['result'] == 1) {
					if(((string)$tmp_browse_arr['mode'] == 'file') OR ((string)$tmp_browse_arr['mode'] == 'embedded')) {
						$tmp_trust_headers = 'yes'; // SHOULD TRUST ALSO IF LOCAL FILE OR EMBEDDED DATAURL IMAGE
					} //end if
				} //end if
				//Smart::log_notice(print_r($tmp_browse_arr,1));
				//--
				$guess_arr = (array) SmartDetectImages::guess_image_extension_by_url_head((string)$tmp_browse_arr['headers']);
				$tmp_img_ext = (string) $guess_arr['extension'];
				$tmp_where_we_guess = (string) $guess_arr['where-was-detected'];
				//Smart::log_notice('Guess Ext by URL Head: '.$tmp_browse_arr['headers']."\n".'### '.print_r($guess_arr,1)."\n".'#');
				if((string)$tmp_trust_headers != 'yes') {
					$tmp_img_ext = ''; // not trusted, try re-detect !
				} //end if
				//--
				if((string)$tmp_img_ext == '') {
					$tmp_img_ext = (string) SmartDetectImages::guess_image_extension_by_img_content((string)$tmp_browse_arr['content'], true);
					if((string)$tmp_img_ext != '') {
						$tmp_where_we_guess = ' Img Content ...';
					} //end if
				} //end if
				//Smart::log_notice('Guess Ext by Img Content: '.$tmp_img_ext."\n".'#');
				if((string)$tmp_img_ext != '') {
					$tmp_fake_fname = 'file'.$tmp_img_ext;
				} //end if
				//--
				if(((string)$tmp_browse_arr['result'] == '1') AND ((string)$tmp_browse_arr['code'] == '200')) {
					if((string)$tmp_fake_fname != '') {
						if(((string)$tmp_img_ext == '.svg') OR ((string)$tmp_img_ext == '.png') OR ((string)$tmp_img_ext == '.gif') OR ((string)$tmp_img_ext == '.jpg')) { // using a deep detection above and is not safe unknown image extension
							$tmp_fcontent = (string) $tmp_browse_arr['content'];
						} //end if
					} //end if else
				} //end if else
				//--
				$tmp_browse_arr = null;
				//--
				if((string)$tmp_fake_fname != '') {
					if((string)$tmp_fcontent != '') {
						$out_arr['result']  	= 1;
						$out_arr['extension'] 	= (string) $tmp_img_ext;
						$out_arr['filename'] 	= (string) $tmp_fake_fname;
						$out_arr['content'] 	= (string) $tmp_fcontent;
					} //end if
				} //end if
				//--
			} //end if
			//--
		} //end if else
		//--
		return (array) $out_arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Load URL Content (if relative path to a file will be prefixed with current URL for security reasons)
	 *
	 * @param STRING 	$y_url_or_path				:: relative/path/to/file (assumes current URL as prefix) | http(s)://some.url:port/path (port is optional) ; works also with Data-URL (Data-Image only)
	 * @param NUMBER 	$y_timeout					:: timeout in seconds
	 * @param ENUM 		$y_method 					:: used only for URLs, the browsing method: GET | POST
	 * @param ENUM		$y_ssl_method				:: SSL Mode: tls | sslv3 | sslv2 | ssl
	 * @param STRING 	$y_auth_name				:: used only for URLs, the auth user name
	 * @param STRING 	$y_auth_pass				:: used only for URLs, the auth password
	 * @param YES/NO	$y_allow_set_credentials	:: DEFAULT IS SET to NO ; if YES must be set just for internal URLs ; if set to AUTO will try to detect if can trust based on admin.php / index.php local framework scripts ; if the $y_url_or_path to get is detected to be under current URL will send also the Unique / session IDs ; more if detected that is from admin.php and if this is set to YES will send the HTTP-BASIC Auth credentials if detected (using YES with other URLs than Smart.Framework's current URL can be a serious SECURITY ISSUE, so don't !)
	 * @param BOOL 		$y_allow_num_redirects 		:: DEFAULT IS SET to 2 ; Between 0..5 ; if > 0 will allow this number of redirects if 301/302, but only if not set to send any auth username/pass/credentials to avoid security leaks) ; if 0 will allow no redirects
	 * @return ARRAY
	 */
	public static function load_url_content($y_url_or_path, $y_timeout=30, $y_method='GET', $y_ssl_method='', $y_auth_name='', $y_auth_pass='', $y_allow_set_credentials='no', $y_allow_num_redirects=2) {
		//--
		// fixed sessionID with new Dynamic generated
		// added support for redirects if 301/302 and no auth/credentials
		//--
		// ### IMPORTANT ###
		// BECAUSE OF SECURITY CONCERNS, NEVER USE OR MODIFY THIS FUNCTION TO LOAD A FILE PATH
		// ALL FILE PATHS WILL BE CONSIDERED AS URL PATHS AND WILL BE PREFIXED WITH CURRENT BASE URL TO FORCE ACCESSING ALL FILES VIA HTTP(S) REQUESTS TO AVOID BYPASS THAT SECURITY !!!
		// #################
		//--
		$y_url_or_path = (string) $y_url_or_path;
		$y_allow_num_redirects = (int) $y_allow_num_redirects;
		if((int)$y_allow_num_redirects < 0) {
			$y_allow_num_redirects = 0;
		} elseif((int)$y_allow_num_redirects > 5) {
			$y_allow_num_redirects = 5;
		} //end if
		//--
		if((string)$y_url_or_path == '') {
			//--
			return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
				'client' => (string) __METHOD__,
				'log' => 'ERROR: FILE Name is Empty ...',
				'mode' => 'file', // DO NOT CHANGE !!
				'result' => '0',
				'code' => '400', // HTTP 400 BAD REQUEST
				'headers' => '',
				'content' => '',
				'browse-url-info' => [],
				'debuglog' => ''
			);
			//--
		} //end if
		//-- detect if file or url {{{SYNC-LOAD-URL-OR-FILE-OR-IMG}}}
		$tmp_arr_trust_reference = (array) self::get_url_or_path_trust_reference((string)$y_url_or_path);
		$tmp_fixed_url_or_path 	= (string) $tmp_arr_trust_reference['url-or-path-fixed'];
		$tmp_url_or_path_type 	= (string) $tmp_arr_trust_reference['url-or-path-type'];
		$tmp_allow_credentials 	= (string) $tmp_arr_trust_reference['allow-credentials'];
		$tmp_trust_headers 		= (string) $tmp_arr_trust_reference['trust-headers'];
		$tmp_arr_trust_reference = null;
		//--
		$y_url_or_path = (string) $tmp_fixed_url_or_path;
		//--
		switch((string)$y_allow_set_credentials) {
			case 'no':
				$tmp_allow_credentials = 'no';
				break;
			case 'yes':
				$tmp_trust_headers = 'yes';
				break;
			case 'auto':
			default:
				if((string)$tmp_allow_credentials == 'yes') {
					$y_allow_set_credentials = 'yes';
					$tmp_trust_headers = 'yes';
				} else {
					$y_allow_set_credentials = 'no';
				} //end if else
		} //end switch
		//--
		switch((string)$tmp_url_or_path_type) {
			case 'url':
			case 'data-url':
				break;
			case '!empty!':
			case '!unknown!':
			default:
				return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'ERROR: FILE Not Found or Invalid Path or Invalid DataURL ...',
					'mode' => 'file', // DO NOT CHANGE !!
					'result' => '0',
					'code' => '404', // HTTP 404 NOT FOUND
					'headers' => '',
					'content' => '',
					'browse-url-info' => [],
					'debuglog' => ''
				);
		} //end switch
		//--
		if((string)$tmp_url_or_path_type == 'data-url') { // DATA-URL
			//-- try to detect if data:image/ :: {{{SYNC-DATA-IMAGE}}}
			if(((string)strtolower((string)substr((string)$y_url_or_path, 0, 11)) == 'data:image/') AND (stripos((string)$y_url_or_path, ';base64,') !== false)) {
				//--
				$eimg = (array) explode(';base64,', (string)$y_url_or_path);
				//--
				return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'OK ? Not sure, decoded from embedded B64 image !',
					'mode' => 'embedded', // DO NOT CHANGE !!
					'result' => '1',
					'code' => '200', // HTTP 200 OK
					'headers' => (string) SmartUnicode::sub_str($y_url_or_path, 0, 50).'...', // try to get the 1st 50 chars for trying to guess the extension
					'content' => (string) @base64_decode((string)trim((string)(isset($eimg[1]) ? $eimg[1] : ''))),
					'browse-url-info' => [],
					'debuglog' => ''
				);
				//--
			} else {
				//--
				return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'ERROR: Invalid DataURL ...',
					'mode' => 'file', // DO NOT CHANGE !!
					'result' => '0',
					'code' => '404', // HTTP 404 NOT FOUND
					'headers' => '',
					'content' => '',
					'browse-url-info' => [],
					'debuglog' => ''
				);
				//--
			} //end if
			//--
		} else { // URL
			//--
			if((string)$y_ssl_method == '') {
				if(defined('SMART_FRAMEWORK_SSL_MODE')) {
					$y_ssl_method = (string) SMART_FRAMEWORK_SSL_MODE;
				} else {
					Smart::log_notice('NOTICE: LibUtils/Load-URL-or-File // The SSL Method not defined and SMART_FRAMEWORK_SSL_MODE was not defined. Using the `tls` as default ...');
					$y_ssl_method = 'tls';
				} //end if else
			} //end if
			//--
			$browser = new SmartHttpClient();
			//--
			$y_timeout = Smart::format_number_int($y_timeout,'+');
			if($y_timeout <= 0) {
				$y_timeout = 30; // default value
			} //end if
			$browser->connect_timeout = (int) $y_timeout;
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				$browser->debug = 1;
			} //end if
			//--
			if((string)SmartUtils::get_server_current_protocol() == 'https://') {
				$tmp_current_protocol = 'https://';
			} else {
				$tmp_current_protocol = 'http://';
			} //end if else
			//--
			$tmp_current_server = (string) SmartUtils::get_server_current_domain_name();
			$tmp_current_port 	= (string) SmartUtils::get_server_current_port();
			$tmp_current_path 	= (string) SmartUtils::get_server_current_request_uri();
			$tmp_current_script = (string) SmartUtils::get_server_current_full_script();
			//--
			$tmp_test_url_arr 		= (array) Smart::url_parse($y_url_or_path);
			$tmp_test_browser_id 	= (array) SmartUtils::get_os_browser_ip();
			//--
			$tmp_extra_log = '';
			if(SmartFrameworkRuntime::ifDebug()) {
				$tmp_extra_log .= "\n".'===== # ====='."\n";
			} //end if
			//--
			$cookies = array();
			$auth_name = (string) $y_auth_name;
			$auth_pass = (string) $y_auth_pass;
			//--
			if((string)$y_allow_set_credentials == 'yes') {
				//--
				$tmp_extra_log .= '[EXTRA]: Send Auth CREDENTIALS set to YES (will check if safe) ...'."\n";
				//--
				if(SmartFrameworkRuntime::ifDebug()) {
					$tmp_extra_log .= '[EXTRA]: I will try to detect if this is my current Domain and I will check if it is safe to send my sessionID COOKIE and my Auth CREDENTIALS ...'."\n";
				} //end if
				//--
				if(((string)$tmp_current_protocol == (string)$tmp_test_url_arr['protocol']) AND ((string)$tmp_current_server == (string)$tmp_test_url_arr['host']) AND ((string)$tmp_current_port == (string)$tmp_test_url_arr['port'])) {
					//--
					if(SmartFrameworkRuntime::ifDebug()) {
						$tmp_extra_log .= '[EXTRA]: OK, Seems that the browsed Domain is identical with my current Domain which is: '.$tmp_current_protocol.$tmp_current_server.':'.$tmp_current_port.' and the browsed one is: '.$tmp_test_url_arr['protocol'].$tmp_test_url_arr['host'].':'.$tmp_test_url_arr['port']."\n";
						$tmp_extra_log .= '[EXTRA]: I will also check if my current script and path are identical with the browsed ones ...'."\n";
					} //end if
					//--
					if(((string)$tmp_current_script == (string)$tmp_test_url_arr['path']) AND (substr($tmp_current_path, 0, strlen($tmp_current_script)) == (string)$tmp_test_url_arr['path'])) {
						//--
						if(SmartFrameworkRuntime::ifDebug()) {
							$tmp_extra_log .= '[EXTRA]: OK, Seems that the current script is identical with the browsed one :: '.'Current Path is: \''.$tmp_current_script.'\' / Browsed Path is: \''.$tmp_test_url_arr['path'].'\' !'."\n";
							$tmp_extra_log .= '[EXTRA]: I will check if I have to send my SessionID so I will check the browserID ...'."\n";
						} //end if
						//--
						$browser->useragent = (string) SmartUtils::get_selfrobot_useragent_name(); // this must be set just when detected the same path and script ; it is a requirement to detect it as the self-robot [ @s# ] in order to send the credentials or the current
						//-- {{{SYNC-SMART-UNIQUE-COOKIE}}}
						if((defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME')) AND (!defined('SMART_FRAMEWORK_UNIQUE_ID_COOKIE_SKIP'))) {
							if((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME != '') {
								if(SmartFrameworkSecurity::ValidateVariableName((string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME)) { // {{{SYNC-VALIDATE-UID-COOKIE-NAME}}}
									//--
									if((string)SMART_APP_VISITOR_COOKIE != '') { // if set, then forward
										if(SmartFrameworkRuntime::ifDebug()) {
											$tmp_extra_log .= '[EXTRA]: OK, I will send my current Visitor Unique Cookie ID as it is set and not empty ...'."\n";
										} //end if
										$cookies[(string)SMART_FRAMEWORK_UNIQUE_ID_COOKIE_NAME] = (string) SMART_APP_VISITOR_COOKIE; // this is a requirement
									} //end if
									//--
								} //end if
							} //end if
						} //end if
						//-- #end# sync
						if(((string)SmartAuth::get_login_method() == 'HTTP-BASIC') AND ((string)$auth_name == '') AND ((string)$auth_pass == '') AND (strpos($tmp_current_script, '/admin.php') !== false) AND (strpos($tmp_test_url_arr['path'], '/admin.php') !== false)) {
							//--
							if(SmartFrameworkRuntime::ifDebug()) {
								$tmp_extra_log .= '[EXTRA]: HTTP-BASIC Auth method detected / Allowed to pass the Credentials - as the browsed URL belongs to this ADMIN Server as I run, the Auth credentials are set but passed as empty - everything seems to be safe I will send my credentials: USERNAME = \''.SmartAuth::get_login_id().'\' ; PASS = *****'."\n";
							} //end if
							//--
							$auth_name = (string) SmartAuth::get_login_id();
							$auth_pass = (string) SmartAuth::get_login_password();
							//--
						} //end if
						//--
					} else {
						//--
						if(SmartFrameworkRuntime::ifDebug()) {
							$tmp_extra_log .= '[EXTRA]: Seems that the scripts are NOT identical :: '.'Current Script is: \''.$tmp_current_script.'\' / Browsed Script is: \''.$tmp_test_url_arr['path'].'\' !'."\n";
							$tmp_extra_log .= '[EXTRA]: This is the diff for having a comparation: '.substr($tmp_current_path, 0, strlen($tmp_current_script))."\n";
						} //end if
						//--
					} //end if
					//--
				} //end if
				//--
			} //end if
			//--
			$browser->cookies = (array) $cookies;
			//--
			$data = (array) $browser->browse_url($y_url_or_path, $y_method, $y_ssl_method, $auth_name, $auth_pass); // do browse
			//--
			$redirect_url = '';
			$redirect_code = '';
			$prev_redirect = '';
			if((int)$y_allow_num_redirects > 0) { // only if allow redirects is > 0
				if(
					((string)$y_allow_set_credentials == 'no') AND // must not test != 'yes' because can be 'auto' !!!
					((string)trim((string)$y_auth_name) == '') AND
					((string)trim((string)$y_auth_pass) == '')
				) { // allow only redirects without any auth username/pass/credentials
					if((string)trim((string)$data['redirect-url']) != '') {
						if(((string)substr((string)$data['redirect-url'], 0, 7) == 'http://') OR ((string)substr((string)$data['redirect-url'], 0, 8) == 'https://')) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
							//--
							$redirect_url = (string) $data['redirect-url'];
							$redirect_code = (int) $data['code'];
							//--
							$data = array(); // clear data
							$data = (array) self::load_url_content($redirect_url, $y_timeout, $y_method, $y_ssl_method, '', '', 'no', (int)((int)$y_allow_num_redirects - 1)); // ALLOW FINITE NUMBER OF REDIRECTS TO AVOID INFINITE LOOPS BY BUGGY HTTP(S) SERVERS
							//--
							$prev_redirect = (string) (isset($data['browse-url-info']['redirect']) ? $data['browse-url-info']['redirect'] : '');
							//--
						} //end if
					} //end if
				} //end if
			} //end if
			//--
			return array( // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
				'client' 			=> (string) __METHOD__,
				'log' 				=> (string) $data['log'].$tmp_extra_log,
				'mode' 				=> (string) $data['mode'], // DO NOT CHANGE !!
				'result' 			=> (string) $data['result'],
				'code' 				=> (string) $data['code'],
				'headers' 			=> (string) $data['headers'],
				'content' 			=> (string) $data['content'],
				'browse-url-info' 	=> (array) [
					'url' 			=> (string) $y_url_or_path,
					'timeout' 		=> (string) (int)$y_timeout,
					'method' 		=> (string) $y_method,
					'auth' 			=> (string) ((strlen((string)$y_auth_name) && strlen((string)$y_auth_pass)) ? ($y_auth_name.':'.str_repeat('*', strlen((string)$y_auth_pass))) : ''),
					'credentials' 	=> (string) $y_allow_set_credentials,
					'redirect' 		=> (string) ($prev_redirect ? $prev_redirect.' <- ' : '').trim('['.(int)$y_allow_num_redirects.'] '.(string)$redirect_code.' '.$redirect_url),
				],
				'debuglog' 			=> (string) $data['debuglog']
			);
			//--
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: get the trust reference for an URL or URL path ; works also with Data-URL (Data-Image only)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param $y_url_or_path 	STRING 	:: the absolute URL or RELATIVE URL (as path)
	 * @return ARRAY					:: fixed URL, reference elements
	 */
	public static function get_url_or_path_trust_reference($y_url_or_path) {
		//--
		// v.20210310
		//--
		// ### SECURITY: IMPORTANT !!! ###
		// BECAUSE OF SECURITY CONCERNS, NEVER USE OR MODIFY THIS FUNCTION TO USE WITH A FILE SYSTEM PATH
		// ALL FILE PATHS WILL BE CONSIDERED AS URL PATHS AND WILL BE PREFIXED WITH CURRENT BASE URL TO FORCE ACCESSING ALL FILES VIA HTTP(S) REQUESTS TO AVOID BYPASS THAT SECURITY !!!
		// #################
		//--
		$y_url_or_path = (string) trim((string)$y_url_or_path);
		//--
		$the_url_or_path_type = '!empty!';
		$allow_credentials = 'no';
		$trust_headers = 'no'; // if external URL
		//--
		if((string)$y_url_or_path != '') {
			//--
			if((string)substr($y_url_or_path, 0, 10) == 'admin.php?') {
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				if(SmartFrameworkRuntime::isAdminArea() === true) {
					$allow_credentials = 'yes'; // send only if admin
				} //end if
				$trust_headers = 'yes';
			} elseif(((string)substr($y_url_or_path, 0, strlen(SmartUtils::get_server_current_url().'admin.php?')) == (string)SmartUtils::get_server_current_url().'admin.php?') AND (((string)substr($y_url_or_path, 0, 7) == 'http://') OR ((string)substr($y_url_or_path, 0, 8) == 'https://'))) {
				if(SmartFrameworkRuntime::isAdminArea() === true) {
					$allow_credentials = 'yes'; // send only if admin
				} //end if
				$trust_headers = 'yes';
			} elseif(((string)substr($y_url_or_path, 0, 10) == 'index.php?') OR ((string)substr($y_url_or_path, 0, 1) == '?')) {
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				if(SmartFrameworkRuntime::isAdminArea() !== true) {
					$allow_credentials = 'yes'; // send only if not admin
				} //end if
				$trust_headers = 'yes';
			} elseif(((string)substr($y_url_or_path, 0, strlen(SmartUtils::get_server_current_url().'index.php?')) == (string)SmartUtils::get_server_current_url().'index.php?') AND (((string)substr($y_url_or_path, 0, 7) == 'http://') OR ((string)substr($y_url_or_path, 0, 8) == 'https://'))) {
				if(SmartFrameworkRuntime::isAdminArea() !== true) {
					$allow_credentials = 'yes'; // send only if not admin
				} //end if
				$trust_headers = 'yes';
			} elseif(((string)substr($y_url_or_path, 0, strlen(SmartUtils::get_server_current_url().'?')) == (string)SmartUtils::get_server_current_url().'?') AND (((string)substr($y_url_or_path, 0, 7) == 'http://') OR ((string)substr($y_url_or_path, 0, 8) == 'https://'))) {
				if(SmartFrameworkRuntime::isAdminArea() !== true) {
					$allow_credentials = 'yes'; // send only if not admin
				} //end if
				$trust_headers = 'yes';
			} //end if
			//--
			$the_url_or_path_type = '!unknown!';
			if(((string)strtolower((string)substr((string)$y_url_or_path, 0, 11)) == 'data:image/') AND (stripos((string)$y_url_or_path, ';base64,') !== false)) { // {{{SYNC-DATA-IMAGE}}}
				$the_url_or_path_type = 'data-url'; // it is a data-url
				$trust_headers = 'yes';
			} elseif(((string)substr((string)$y_url_or_path, 0, 7) == 'http://') OR ((string)substr((string)$y_url_or_path, 0, 8) == 'https://')) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
				$the_url_or_path_type = 'url'; // it is an url
				// trust the headers, must be preserved as above
			} else { // !!! it is a relative path but for the security reasons must be accessed via the local URL only since content source may be untrusted !!!
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				$the_url_or_path_type = 'url'; // it is an url
				// trust the headers, must be preserved as above
			} //end if
			//--
		} //end if else
		//--
		return array(
			'url-or-path-fixed' => (string) $y_url_or_path, 		// fixed URL (ex: for relative URLs the current URL as prefix will be added)
			'url-or-path-type' 	=> (string) $the_url_or_path_type, 	// can be URL or Data-URL
			'allow-credentials' => (string) $allow_credentials, 	// yes/no (yes only for internal ...)
			'trust-headers' 	=> (string) $trust_headers 			// yes/no
		);
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
