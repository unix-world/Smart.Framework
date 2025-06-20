<?php
// [LIB - Smart.Framework / Plugins / (HTTP) Robot]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - (HTTP) Robot:
// DEPENDS:
//	* SmartUnicode::
//	* Smart::
//	* SmartDetectImages::
//	* SmartUtils::
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartRobot - Easy to use HTTP Robot.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: SmartUnicode, Smart, SmartDetectImages, SmartUtils, SmartEnvironment, SmartFrameworkSecurity, SmartFrameworkRegistry
 * @version 	v.20250221
 * @package 	Application:Plugins:Network:HTTP
 *
 */
final class SmartRobot {

	// ::


	//================================================================
	/**
	 * Load URL IMG (svg / png / gif / jpg / webp) Content (if relative path to a file will be prefixed with current URL for security reasons)
	 *
	 * @param STRING 	$y_img_link					:: relative/path/to/image (assumes current URL as prefix) | http(s)://some.url:port/path/to/image (port is optional) ; works also with Data-URL (Data-Image only)
	 * @param YES/NO	$y_allow_set_credentials	:: DEFAULT IS SET to NO ; if YES must be set just for internal URLs ; if set to AUTO will try to detect if can trust based on task.php / admin.php / index.php local framework scripts ; if the $y_url_or_path to get is detected to be under current URL will send also the Unique / session IDs ; more if detected that is from task.php / admin.php and if this is set to YES will send the HTTP-BASIC Auth credentials if detected (using YES with other URLs than Smart.Framework's current URL can be a serious SECURITY ISSUE, so don't !)
	 * @return ARRAY
	 */
	public static function load_url_img_content(?string $y_img_link, string $y_allow_set_credentials='no') : array {
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
			'errmsg' 			=> '',
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
			switch((string)strtolower((string)$y_allow_set_credentials)) {
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
				$out_arr['errmsg'] 			= (string) $tmp_browse_arr['errmsg'];
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
				$out_arr['log'] .= (string) trim((string)$tmp_browse_arr['log'])."\n";
				//--
				if($tmp_browse_arr['result'] == 1) {
					if(((string)$tmp_browse_arr['mode'] == 'file') OR ((string)$tmp_browse_arr['mode'] == 'embedded')) {
						$tmp_trust_headers = 'yes'; // SHOULD TRUST ALSO IF LOCAL FILE OR EMBEDDED DATAURL IMAGE
					} //end if
				} //end if
				//Smart::log_notice(print_r($tmp_browse_arr,1));
				//--
				$guess_arr = (array) SmartDetectImages::guess_image_extension_by_url_head((string)$tmp_browse_arr['headers']);
				$out_arr['log'] .= 'IMG Detected as: '.trim((string)$guess_arr['extension']).' ; in: '.trim((string)$guess_arr['where-was-detected'])."\n";
				//Smart::log_notice('Guess Ext by URL Head: '.$tmp_browse_arr['headers']."\n".'### '.print_r($guess_arr,1)."\n".'#');
				//-- always re-validate if an image !
				$tmp_img_ext = (string) SmartDetectImages::guess_image_extension_by_img_content((string)$tmp_browse_arr['content'], true);
				if((string)$tmp_img_ext != '') {
					$out_arr['log'] .= 'IMG Validated as: '.trim((string)$tmp_img_ext)."\n";
				} //end if
				//Smart::log_notice('Guess Ext by Img Content: '.$tmp_img_ext."\n".'#');
				if((string)$tmp_img_ext != '') {
					$tmp_fake_fname = 'file'.$tmp_img_ext;
				} //end if
				//--
				if(((string)$tmp_browse_arr['result'] == '1') AND ((string)$tmp_browse_arr['code'] == '200')) {
					if((string)$tmp_fake_fname != '') {
						if( // {{{SYNC-DETECT-IMG-TYPES}}}
							((string)$tmp_img_ext == '.svg') OR
							((string)$tmp_img_ext == '.png') OR
							((string)$tmp_img_ext == '.gif') OR
							((string)$tmp_img_ext == '.jpg') OR
							((string)$tmp_img_ext == '.webp')
						) { // using a deep detection above and is not safe unknown image extension
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
	 * @param ENUM		$y_ssl_method				:: SSL Mode: '' (default) ; or specific: 'tls' | 'tls:1.1' | 'tls:1.2' | 'tls:1.3'
	 * @param STRING 	$y_auth_name				:: used only for URLs, the auth user name
	 * @param STRING 	$y_auth_pass				:: used only for URLs, the auth password
	 * @param YES/NO	$y_allow_set_credentials	:: DEFAULT IS SET to NO ; if YES must be set just for internal URLs ; if set to AUTO will try to detect if can trust based on task.php / admin.php / index.php local framework scripts ; if the $y_url_or_path to get is detected to be under current URL will send also the Unique / session IDs ; more if detected that is from task.php / admin.php and if this is set to YES will send the HTTP-BASIC Auth credentials if detected (using YES with other URLs than Smart.Framework's current URL can be a serious SECURITY ISSUE, so don't !)
	 * @param BOOL 		$y_allow_num_redirects 		:: DEFAULT IS SET to 2 ; Between 0..10 ; if > 0 will allow this number of redirects if 301/302, but only if not set to send any auth username/pass/credentials to avoid security leaks) ; if 0 will allow no redirects
	 * @return ARRAY
	 */
	public static function load_url_content(?string $y_url_or_path, int $y_timeout=30, string $y_method='GET', string $y_ssl_method='', string $y_auth_name='', string $y_auth_pass='', string $y_allow_set_credentials='no', int $y_allow_num_redirects=2) : array {
		//--
		// fixed sessionID with new Dynamic generated
		// added support for safe redirects if 301/302 if no auth/credentials ; min: 0 ; max: 10 ; {{{SYNC-SAFE-HTTP-REDIRECT-POLICY}}}
		//--
		// ### IMPORTANT ###
		// BECAUSE OF SECURITY CONCERNS, NEVER USE OR MODIFY THIS FUNCTION TO LOAD A FILE PATH
		// ALL FILE PATHS WILL BE CONSIDERED AS URL PATHS AND WILL BE PREFIXED WITH CURRENT BASE URL TO FORCE ACCESSING ALL FILES VIA HTTP(S) REQUESTS TO AVOID BYPASS THAT SECURITY !!!
		// #################
		//--
		$y_url_or_path = (string) trim((string)$y_url_or_path);
		//--
		$y_allow_num_redirects = (int) $y_allow_num_redirects;
		if((int)$y_allow_num_redirects < 0) {
			$y_allow_num_redirects = 0;
		} elseif((int)$y_allow_num_redirects > 10) {
			$y_allow_num_redirects = 10;
		} //end if
		//--
		if((string)$y_url_or_path == '') {
			//--
			return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
				'client' => (string) __METHOD__,
				'log' => 'ERROR: FILE Name is Empty ...',
				'mode' => 'file', // DO NOT CHANGE !!
				'errmsg' => '',
				'result' => '0',
				'code' => '400', // HTTP 400 BAD REQUEST
				'headers' => '',
				'content' => '',
				'browse-url-info' => [],
				'debuglog' => ''
			];
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
		switch((string)strtolower((string)$y_allow_set_credentials)) {
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
				return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'ERROR: FILE Not Found or Invalid Path or Invalid DataURL ...',
					'mode' => 'file', // DO NOT CHANGE !!
					'errmsg' => '',
					'result' => '0',
					'code' => '404', // HTTP 404 NOT FOUND
					'headers' => '',
					'content' => '',
					'browse-url-info' => [],
					'debuglog' => ''
				];
		} //end switch
		//--
		if((string)$tmp_url_or_path_type == 'data-url') { // DATA-URL
			//-- try to detect if data:image/ :: {{{SYNC-DATA-IMAGE}}}
			if(
				(stripos((string)$y_url_or_path, 'data:image/') === 0)
				AND
				(
					(stripos((string)$y_url_or_path, ';base64,') !== false)
					OR
					(stripos((string)$y_url_or_path, 'data:image/svg+xml,') === 0) // {{{SYNC-DATA-IMG-SVG-URLENCODED}}} ; svg url encoded
				)
			) { // DATA-URL
				//--
				if(stripos((string)$y_url_or_path, 'data:image/svg+xml,') === 0) { // {{{SYNC-DATA-IMG-SVG-URLENCODED}}} ; svg url encode
					$eimg = (string) substr((string)$y_url_or_path, (int)strlen('data:image/svg+xml,'));
					$eimg = (string) trim((string)urldecode((string)$eimg)); // use url decode instead of rawurldecode ; will do the job of rawurldecode + will decode also + as spaces
				} else { // svg + b64 / png|gif|jpg|webp +b64
					$eimg = (array) explode(';base64,', (string)$y_url_or_path, 2);
					$eimg = (string) isset($eimg[1]) ? Smart::b64_dec((string)trim((string)($eimg[1]))) : '';
				} //end if
				//--
				if( // if svg, validate
					(stripos((string)$y_url_or_path, 'data:image/svg+xml,') === 0) OR
					(stripos((string)$y_url_or_path, 'data:image/svg+xml;') === 0)
				) {
					if((stripos((string)$eimg, '<svg') !== false) AND (stripos((string)$eimg, '</svg>') !== false)) { // {{{SYNC VALIDATE SVG}}}
						$eimg = (new SmartXmlParser())->format((string)$eimg, false, false, false, true); // avoid injection of other content than XML, remove the XML header
					} else {
						$eimg = ''; // not a SVG !
					} //end if else
				} //end if
				//--
				return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'OK: Content decoded from embedded data-url image !',
					'mode' => 'embedded', // DO NOT CHANGE !!
					'errmsg' => '',
					'result' => '1',
					'code' => '200', // HTTP 200 OK
					'headers' => (string) SmartUnicode::sub_str((string)$y_url_or_path, 0, 50).'...', // try to get the 1st 50 chars for trying to guess the extension
					'content' => (string) $eimg,
					'browse-url-info' => [],
					'debuglog' => ''
				];
				//--
			} else {
				//--
				return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
					'client' => (string) __METHOD__,
					'log' => 'ERROR: Invalid DataURL ...',
					'mode' => 'file', // DO NOT CHANGE !!
					'errmsg' => '',
					'result' => '0',
					'code' => '404', // HTTP 404 NOT FOUND
					'headers' => '',
					'content' => '',
					'browse-url-info' => [],
					'debuglog' => ''
				];
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
			if(SmartEnvironment::ifDebug()) {
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
			$tmp_test_url_arr 		= (array) Smart::url_parse((string)$y_url_or_path);
			//--
			$tmp_test_browser_id 	= (array) SmartUtils::get_os_browser_ip(); // unused, just for testing purposes if something fails to be logged ...
			//--
			$tmp_extra_log = '';
			if(SmartEnvironment::ifDebug()) {
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
				if(SmartEnvironment::ifDebug()) {
					$tmp_extra_log .= '[EXTRA]: I will try to detect if this is my current Domain and I will check if it is safe to send my sessionID COOKIE and my Auth CREDENTIALS ...'."\n";
				} //end if
				//--
				if(
					((string)$tmp_current_protocol == (string)$tmp_test_url_arr['protocol'])
					AND
					((string)$tmp_current_server == (string)$tmp_test_url_arr['host'])
					AND
					((string)$tmp_current_port == (string)$tmp_test_url_arr['port'])
				) {
					//--
					if(SmartEnvironment::ifDebug()) {
						$tmp_extra_log .= '[EXTRA]: OK, Seems that the browsed Domain is identical with my current Domain which is: '.$tmp_current_protocol.$tmp_current_server.':'.$tmp_current_port.' and the browsed one is: '.$tmp_test_url_arr['protocol'].$tmp_test_url_arr['host'].':'.$tmp_test_url_arr['port']."\n";
						$tmp_extra_log .= '[EXTRA]: I will also check if my current script and path are identical with the browsed ones ...'."\n";
					} //end if
					//--
					if(
						((string)$tmp_current_script == (string)$tmp_test_url_arr['path'])
						AND
						(strpos((string)$tmp_current_path, (string)$tmp_test_url_arr['path']) === 0)
					) {
						//--
						if(SmartEnvironment::ifDebug()) {
							$tmp_extra_log .= '[EXTRA]: OK, Seems that the current script is identical with the browsed one :: '.'Current Path is: \''.$tmp_current_script.'\' / Browsed Path is: \''.$tmp_test_url_arr['path'].'\' !'."\n";
							$tmp_extra_log .= '[EXTRA]: I will also send all the existing cookies ...'."\n";
						} //end if
						//--
						$browser->useragent = (string) SmartUtils::get_selfrobot_useragent_name(); // this must be set just when detected the same protocol, host, port, path, script ; it is a requirement to detect it as the self-robot [ @s# ] in order to send the credentials or the current ; this is a special signature
						//--
						$cookies = (array) SmartFrameworkRegistry::getCookieVars(); // safe (same protocol, host, port, path, script) ; these are needed for mail-decode/pdf and maybe others (because there is some extra cookie token in mail utils, but this may change anytime ...) ; it is unpredictable to know which cookies to be sent only, send them all
						//--
						// TODO: implement SWT also for [I] area
						//--
						if(
							(SmartEnvironment::isAdminArea() === true) // should be adm/task area only !
							AND
							(SmartAuth::is_authenticated() === true) // is logged in
							AND // {{{SYNC-ROBOT-AUTH-SELF-BEARER}}}
							(strpos((string)SmartAuth::get_auth_method(), 'AUTH:HTTP-') === 0) // Auth Method Should start with `AUTH:HTTP-` that can be Basic or Auth Bearer
							AND
							(((string)$auth_name == '') AND ((string)$auth_pass == '')) // there is not provided specific username/pass
							AND
							(
								((strpos((string)$tmp_current_script, '/admin.php') !== false) AND (strpos((string)$tmp_test_url_arr['path'], '/admin.php') !== false)) OR
								((strpos((string)$tmp_current_script, '/task.php') !== false)  AND (strpos((string)$tmp_test_url_arr['path'], '/task.php') !== false))
							)
						) {
							//--
							if(SmartEnvironment::ifDebug()) {
								$tmp_extra_log .= '[EXTRA]: HTTP-BASIC Auth method detected / Allowed to pass the Credentials - as the browsed URL belongs to this ADMIN or TASK Server as I run, the Auth credentials are set but passed as empty - everything seems to be safe I will send my credentials: USERNAME = \''.SmartAuth::get_auth_username().'\' ; PASS = *******'."\n"; // this should be the username not the ID ! on admin area the username is used for auth !
							} //end if
							//--
							// use HTTP Bearer (SWT) ; the SmartAuth is no more storing plain password, but only hash, thus using the standard HTTP Auth with SmartFramework internal is no more possible, only SWT Tokens with Bearer Auth can be used ...
							//-- {{{SYNC-AUTH-TOKEN-SWT}}}
							$swt_token = (array) SmartAuth::swt_token_create(
								'A', // bind to adm/tsk area only !
								(string) SmartAuth::get_auth_username(), // auth user name ; this should be the username not the ID ; on admin area the username is used for auth !
								(string) SmartAuth::get_auth_passhash(),  // password hash
								(int)    ((int)$y_timeout + 1), // add one second to be sure is never zero
								(array)  [ (string)SmartUtils::get_server_current_ip() ], // server's own IP Address only, in this List ; currently just one
								(array)  [], // wildcard ; or can be: // Smart::list_to_array((string)SmartAuth::get_user_privileges()) // list of allowed privs ; include all that this user have ; must be explicit since version 1.3
							);
							//--
							$auth_name = '';
							$auth_pass = '';
							if($swt_token['error'] === '') {
								$auth_name = (string) SmartHttpUtils::AUTH_USER_BEARER; // {{{SYNC-ROBOT-AUTH-SELF-BEARER}}}
								$auth_pass = (string) $swt_token['token'];
							} else {
								Smart::log_warning(__METHOD__.' # SWT Token Creation Failed with ERROR: '.$swt_token['error']);
							} //end if else
							//--
						} //end if
						//--
					} else {
						//--
						if(SmartEnvironment::ifDebug()) {
							$tmp_extra_log .= '[EXTRA]: Seems that the scripts are NOT identical :: '.'Current Script is: \''.$tmp_current_script.'\' / Browsed Script is: \''.$tmp_test_url_arr['path'].'\' !'."\n";
							$tmp_extra_log .= '[EXTRA]: This is the diff for having a comparation: '.substr((string)$tmp_current_path, 0, (int)strlen((string)$tmp_current_script))."\n";
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
			$data = (array) $browser->browse_url((string)$y_url_or_path, (string)$y_method, (string)$y_ssl_method, (string)$auth_name, (string)$auth_pass); // {{{SYNC-AUTH-TOKEN-SWT}}}
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
						if(
							(strpos((string)$data['redirect-url'], 'http://') === 0)
							OR
							(strpos((string)$data['redirect-url'], 'https://') === 0)
						) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
							//--
							$redirect_url = (string) $data['redirect-url'];
							$redirect_code = (int)   $data['code'];
							//--
							$data = array(); // clear data
							$data = (array) self::load_url_content((string)$redirect_url, (int)$y_timeout, (string)$y_method, (string)$y_ssl_method, '', '', 'no', (int)((int)$y_allow_num_redirects - 1)); // DISALLOW CREDENTIALS ; ALLOW FINITE NUMBER OF REDIRECTS TO AVOID INFINITE LOOPS BY BUGGY HTTP(S) SERVERS
							//--
							if(is_array($data['browse-url-info'])) {
								$prev_redirect = (string) ($data['browse-url-info']['redirect'] ?? null);
							} //end if
							//--
						} //end if
					} //end if
				} //end if
			} //end if
			//--
			return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
				'client' 			=> (string) __METHOD__,
				'log' 				=> (string) $data['log'].$tmp_extra_log,
				'mode' 				=> (string) $data['mode'], // DO NOT CHANGE !!
				'errmsg' 			=> (string) $data['errmsg'],
				'result' 			=> (string) $data['result'],
				'code' 				=> (string) $data['code'],
				'headers' 			=> (string) $data['headers'],
				'content' 			=> (string) $data['content'],
				'browse-url-info' 	=> (array) [
					'url' 				=> (string) $y_url_or_path,
					'timeout' 			=> (string) (int)$y_timeout,
					'method' 			=> (string) $y_method,
					'auth' 				=> (string) ((strlen((string)$y_auth_name) && strlen((string)$y_auth_pass)) ? ($y_auth_name.':'.str_repeat('*', strlen((string)$y_auth_pass))) : ''),
					'credentials' 		=> (string) $y_allow_set_credentials,
					'redirect' 			=> (string) ($prev_redirect ? $prev_redirect.' <- ' : '').trim('['.(int)$y_allow_num_redirects.'] '.(string)$redirect_code.' '.$redirect_url),
				],
				'debuglog' 			=> (string) $data['debuglog']
			];
			//--
		} //end if else
		//--
		return [ // {{{SYNC-GET-URL-OR-FILE-RETURN}}}
			'client' => (string) __METHOD__,
			'log' => 'ERROR: Unknown',
			'mode' => '?', // DO NOT CHANGE !!
			'errmsg' => '',
			'result' => '0',
			'code' => '777', // this should never happen, there are returns on each if/else, but just in case
			'headers' => '',
			'content' => '',
			'browse-url-info' => [],
			'debuglog' => ''
		];
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
	public static function get_url_or_path_trust_reference(?string $y_url_or_path) : array {
		//--
		// v.20231007
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
			if(strpos((string)$y_url_or_path, 'admin.php?') === 0) { // ADMIN
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				if(SmartEnvironment::isAdminArea() === true) {
					$allow_credentials = 'yes'; // send only if admin
				} //end if
				$trust_headers = 'yes';
			} elseif(strpos((string)$y_url_or_path, 'task.php?') === 0) { // TASK
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				if(SmartEnvironment::isAdminArea() === true) {
					if(SmartEnvironment::isTaskArea() === true) {
						$allow_credentials = 'yes'; // send only if admin
					} //end if
				} //end if
				$trust_headers = 'yes';
			} elseif( // ADMIN + URL-Prefix
				(strpos((string)$y_url_or_path, (string)SmartUtils::get_server_current_url().'admin.php?') === 0)
				AND
				(
					(strpos((string)$y_url_or_path, 'http://') === 0)
					OR
					(strpos((string)$y_url_or_path, 'https://') === 0)
				)
			) {
				if(SmartEnvironment::isAdminArea() === true) {
					$allow_credentials = 'yes'; // send only if admin
				} //end if
				$trust_headers = 'yes';
			} elseif( // TASK + URL-Prefix
				(strpos((string)$y_url_or_path, (string)SmartUtils::get_server_current_url().'task.php?') === 0)
				AND
				(
					(strpos((string)$y_url_or_path, 'http://') === 0)
					OR
					(strpos((string)$y_url_or_path, 'https://') === 0)
				)
			) {
				if(SmartEnvironment::isAdminArea() === true) {
					if(SmartEnvironment::isTaskArea() === true) {
						$allow_credentials = 'yes'; // send only if admin + task
					} //end if
				} //end if
				$trust_headers = 'yes';
			} elseif( // INDEX
				(strpos((string)$y_url_or_path, 'index.php?') === 0)
				OR
				(strpos((string)$y_url_or_path, '?') === 0)
			) {
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				if(SmartEnvironment::isAdminArea() !== true) {
					$allow_credentials = 'yes'; // send only if not admin
				} //end if
				$trust_headers = 'yes';
			} elseif( // INDEX + URL-Prefix
				(
					(strpos((string)$y_url_or_path, (string)SmartUtils::get_server_current_url().'index.php?') === 0)
					OR
					(strpos((string)$y_url_or_path, (string)SmartUtils::get_server_current_url().'?') === 0)
				)
				AND
				(
					(strpos((string)$y_url_or_path, 'http://') === 0)
					OR
					(strpos((string)$y_url_or_path, 'https://') === 0))
				) {
				if(SmartEnvironment::isAdminArea() !== true) {
					$allow_credentials = 'yes'; // send only if not admin
				} //end if
				$trust_headers = 'yes';
			} //end if
			//--
			$the_url_or_path_type = '!unknown!';
			//-- {{{SYNC-DATA-IMAGE}}}
			if(
				(stripos((string)$y_url_or_path, 'data:image/') === 0)
				AND
				(
					(stripos((string)$y_url_or_path, ';base64,') !== false)
					OR
					(stripos((string)$y_url_or_path, 'data:image/svg+xml,') === 0) // {{{SYNC-DATA-IMG-SVG-URLENCODED}}} ; svg url encoded
				)
			) { // DATA-URL
				$the_url_or_path_type = 'data-url'; // it is a data-url
				$trust_headers = 'yes';
			} elseif(
				(strpos((string)$y_url_or_path, 'http://') === 0)
				OR
				(strpos((string)$y_url_or_path, 'https://') === 0)
			) { // {{{SYNC-URL-TEST-HTTP-HTTPS}}}
				$the_url_or_path_type = 'url'; // it is an url
				// trust the headers, must be preserved as above
			} else { // !!! it should be considered as a relative path for the security reasons must be accessed via the local URL only since content source may be untrusted !!!
				$y_url_or_path = (string) SmartUtils::get_server_current_url().$y_url_or_path;
				$the_url_or_path_type = 'url'; // it is an url
				// trust the headers, must be preserved as above
			} //end if
			//--
		} //end if else
		//--
		return [
			'url-or-path-fixed' => (string) $y_url_or_path, 		// fixed URL (ex: for relative URLs the current URL as prefix will be added)
			'url-or-path-type' 	=> (string) $the_url_or_path_type, 	// can be URL or Data-URL
			'allow-credentials' => (string) $allow_credentials, 	// yes/no (yes only for internal ...)
			'trust-headers' 	=> (string) $trust_headers 			// yes/no
		];
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
