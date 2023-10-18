<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderHttp
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* SmartUtils
//	* SmartFrameworkSecurity

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * HTTP Basic Auth Provider
 * This class provides the mechanism for HTTP Basic Authentication
 * The HTTP Basic Authentication is more secure than HTTP Digest Authentication especially if used over HTTPS connections
 *
 * @ignore
 *
 * @version 	v.20231018
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthProviderHttp implements \SmartModExtLib\AuthAdmins\AuthProviderInterface {

	// ::

	//================================================================
	public static function GetCredentials(bool $is_https_required, bool $enable_tokens) : array {
		//--
		$credentials = (array) self::AUTH_RESULT;
		$credentials['auth-safe'] = 0;
		$credentials['auth-mode'] = '';
		$credentials['auth-user'] = '';
		$credentials['auth-pass'] = '';
		$credentials['auth-hash'] = '';
		$credentials['auth-priv'] = [];
		$credentials['auth-error'] = 'Unknown Error';
		//--
		if(!\is_array($_SERVER)) {
			$credentials['auth-safe'] -= 1;
			$credentials['auth-mode'] = (string) self::AUTH_MODE_PREFIX_AUTHEN.'SKIP:NOT-SERVER-DATA';
			$credentials['auth-user'] = '';
			$credentials['auth-pass'] = '';
			$credentials['auth-hash'] = '';
			$credentials['auth-priv'] = [];
			$credentials['auth-error'] = '';
			\Smart::log_warning(__METHOD__.' # ERROR: Server Data is unavailable');
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--
		if(!\SmartEnvironment::isAdminArea()) {
			$credentials['auth-safe'] -= 2;
			$credentials['auth-mode'] = (string) self::AUTH_MODE_PREFIX_AUTHEN.'SKIP:NOT-ADMIN-AREA';
			$credentials['auth-user'] = '';
			$credentials['auth-pass'] = '';
			$credentials['auth-hash'] = '';
			$credentials['auth-priv'] = [];
			$credentials['auth-error'] = 'Only Admin/Task areas can authenticate here';
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--
		$is_https = (bool) ((string)\SmartUtils::get_server_current_protocol() === 'https://');
		//--
		if($is_https === true) {
			$credentials['auth-safe'] += 100;
		} elseif($is_https_required !== false) {
			$credentials['auth-safe'] -= 3;
			$credentials['auth-mode'] = (string) self::AUTH_MODE_PREFIX_AUTHEN.'SKIP:NOT-HTTPS';
			$credentials['auth-user'] = '';
			$credentials['auth-pass'] = '';
			$credentials['auth-hash'] = '';
			$credentials['auth-priv'] = [];
			$credentials['auth-error'] = 'HTTPS is required';
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--
		$authmode = null;
		$token_swt = null;
		//--
		if(
			\array_key_exists('PHP_AUTH_USER', $_SERVER)
			AND
			\array_key_exists('PHP_AUTH_PW', $_SERVER)
		) { // standard support: apache + php module (Basic Auth)
			//--
			$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.'PHP';
			//--
			$credentials['auth-safe'] += 10;
			$credentials['auth-user'] = (string) \trim((string)$_SERVER['PHP_AUTH_USER']); // trim user name
			$credentials['auth-pass'] = (string) $_SERVER['PHP_AUTH_PW']; // do not trim passwords ...
			$credentials['auth-hash'] = '';
			$credentials['auth-priv'] = [];
			$credentials['auth-error'] = '';
			//--
		} else { // special support, for PHP FPM SAPI, PHP CGI or non-standard HTTP Servers / non-standard configurations
			//--
			$authheader = null;
			$authmode = '';
			//--
			$credentials['auth-user'] = '';
			$credentials['auth-pass'] = '';
			$credentials['auth-hash'] = '';
			$credentials['auth-priv'] = [];
			$credentials['auth-error'] = '';
			//--
			if(\function_exists('\\getallheaders')) { // FPM SAPI under Apache ; since PHP 7.3 getallheaders works also FPM SAPI ...
				//--
				foreach((array)\getallheaders() as $key => $val) {
					if((string)\strtoupper((string)\trim((string)$key)) == 'AUTHORIZATION') {
						$authheader = (string) \trim((string)$val);
						$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.'HEADER-APACHE-AUTHORIZATION';
						$credentials['auth-safe'] += 5;
					} //end if
				} //end foreach
				//--
			} elseif(\array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) { // CGI, others, needs setup in config or .htaccess
				//--
				// # IMPORTANT: if not set by Apache it may require in .htaccess or apache config:
				// SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
				//--
				$authheader = (string) \trim((string)$_SERVER['HTTP_AUTHORIZATION']);
				$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.'HEADER-SERVER-AUTHORIZATION';
				$credentials['auth-safe'] += 2;
				//--
			} elseif(\array_key_exists('REDIRECT_HTTP_AUTHORIZATION', $_SERVER)) { // CGI, others or apache after redirect via .htaccess, needs mod rewrite
				//--
				// ## php cgi may not pass the HTTP Basic Auth user/pass to PHP as expected
				// ## a workaround is to add to the .htaccess file one of the following:
				// CGIPassAuth On
				// ## if the above line does not work, there are other alternatives that require mod rewrite such as adding 2 lines in .htaccess after the line that contain [ RewriteEngine On ], depending if using multiviews or not
				// RewriteCond %{HTTP:Authorization} .+
				// RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
				// ## OR
				// RewriteCond %{HTTP:Authorization} .
				// RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
				// # END
				//--
				$authheader = (string) \trim((string)$_SERVER['REDIRECT_HTTP_AUTHORIZATION']); // REDIRECT_ environment variables are created from the environment variables which existed prior to the redirect
				$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.'HEADER-SERVER-REDIRECT-AUTHORIZATION';
				$credentials['auth-safe'] += 1;
				//--
			} //end if else
			//--
			if(((string)$authheader != '') AND ((int)\strlen((string)$authheader) <= 1024) AND ((string)$authmode != '')) {
				if(\stripos((string)$authheader, 'Basic ') === 0) { // Basic Auth
					$authheader = (string) \trim((string)\substr((string)$authheader, 6));
					if((string)$authheader != '') {
						$authheader = (string) \base64_decode((string)$authheader);
						if(((string)\trim((string)$authheader) != '') AND (\strpos((string)$authheader, ':') !== false)) {
							$authheader = \explode(':', (string)$authheader, 2); // get just first 2 parts, by first `:` occurence, even if there are multiple :
							if(\is_array($authheader) AND ((int)\count($authheader) == 2)) {
								$credentials['auth-user'] = (string) \trim((string)($authheader[0] ?? null)); // trim username
								$credentials['auth-pass'] = (string) ($authheader[1] ?? null); // do not trim passwords ...
							} //end if
						} //end if
					} //end if
				} elseif(($enable_tokens === true) AND (\stripos((string)$authheader, 'Bearer ') === 0)) { // Bearer Auth (Tokens)
					$authheader = (string) \trim((string)\substr((string)$authheader, 7));
					if((string)$authheader != '') {
						if(
							((string)\trim((string)\SmartAuth::SWT_VERSION_PREFIX) != '')
							AND
							((string)\trim((string)\SmartAuth::SWT_VERSION_SUFFIX) != '')
							AND
							(\strpos((string)$authheader, (string)\SmartAuth::SWT_VERSION_PREFIX.';') === 0)
							AND
							(\strpos((string)\strrev((string)$authheader), (string)\strrev((string)\SmartAuth::SWT_VERSION_SUFFIX).';') === 0)
						) { // {{{SYNC-AUTH-TOKEN-SWT}}}
							$token_swt = (string) \trim((string)$authheader);
							if(\stripos((string)$authmode, (string)self::AUTH_MODE_PREFIX_HTTP_BASIC) === 0) {
								$authmode = (string) \substr((string)$authmode, (int)\strlen((string)self::AUTH_MODE_PREFIX_HTTP_BASIC));
							} //end if
							$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BEARER.':SWT:'.$authmode;
							$credentials['auth-safe'] += 7;
						} //end if
					} //end if
				} //end if
			} //end if
			//--
			$authheader = null;
			//--
		} //end if else
		//-- {{{SYNC-AUTH-METHODS-NAME}}}
		$credentials['auth-safe']  = (int)    $credentials['auth-safe'];
		$credentials['auth-mode']  = (string) self::AUTH_MODE_PREFIX_AUTHEN.\strtoupper((string)\trim((string)$authmode));
		$credentials['auth-user']  = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-user'])); // filter + trim: for basic auth the username cannot start or end with spaces
		$credentials['auth-pass']  = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-pass'])); // filter + trim: for basic auth the password cannot start or end with spaces
		$credentials['auth-hash']  = (string) '';
		$credentials['auth-priv'] = [];
		$credentials['auth-error'] = (string) '';
		//--
		$authmode = null;
		//--
		if(((string)$token_swt != '') AND ((string)$credentials['auth-error'] == '')) { // {{{SYNC-AUTH-TOKEN-SWT}}}
			$swt_validate = (array) \SmartAuth::swt_token_validate(
				(string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$token_swt)), // swt token via Bearer
				(string) \SmartUtils::get_ip_client(), // client's current IP Address
			);
			$credentials['auth-user'] = ''; // reset
			$credentials['auth-pass'] = ''; // reset
			$credentials['auth-hash'] = ''; // reset
			$credentials['auth-priv'] = []; // reset
			if((string)$swt_validate['error'] == '') {
				$credentials['auth-user'] = (string) $swt_validate['user-name'];
				$credentials['auth-pass'] = ''; // not used, SWT uses pass hash
				$credentials['auth-hash'] = (string) $swt_validate['pass-hash'];
				$credentials['auth-priv'] = (array)  $swt_validate['restr-priv-arr'];
				if((int)\Smart::array_size($swt_validate['restr-priv-arr']) <= 0) { // {{{SYNC-AUTH-TOKENS-EMPTY-PRIVS-PROTECTION}}}
					if((string)$swt_validate['restr-priv-lst'] != '*') {
						$credentials['auth-priv'] = [ 'invalid' ]; // protection
					} //end if
				} //end if
			} else {
				$credentials['auth-error'] = (string) 'SWT Auth Error: '.$swt_validate['error'];
			} //end if
		} //end if
		//--
		if(
			((string)$credentials['auth-user'] != '') // only if a username has been set explicit, avoid validate if empty
			AND
			(
				((int)\strlen((string)$credentials['auth-user']) < 3)
				OR
				((int)\strlen((string)$credentials['auth-user']) > 64)
			)
		) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}} ; do not use here \SmartAuth::validate_auth_username() because can be user#token (STK)
			$credentials['auth-error'] = 'Auth Error, Invalid UserName: `'.$credentials['auth-user'].'`';
			$credentials['auth-user'] = ''; // reset
			$credentials['auth-pass'] = ''; // reset
			$credentials['auth-hash'] = ''; // reset
			$credentials['auth-priv'] = []; // reset
		} //end if
		if((string)$credentials['auth-hash'] != '') { // if no pass hash, expect having a password
			if(
				((int)\strlen((string)$credentials['auth-hash']) < 64)
				OR
				((int)\strlen((string)$credentials['auth-hash']) > 256)
			) { // {{{SYNC-PASS-HASH-LENGTH-VALIDATE}}}
				$credentials['auth-error'] = 'Auth Error, Invalid Password Hash Length';
				$credentials['auth-user'] = ''; // reset
				$credentials['auth-pass'] = ''; // reset
				$credentials['auth-hash'] = ''; // reset
				$credentials['auth-priv'] = []; // reset
			} elseif((string)$credentials['auth-pass'] != '') {
				$credentials['auth-error'] = 'Auth Error, Cannot set both: Password and Password Hash';
				$credentials['auth-user'] = ''; // reset
				$credentials['auth-pass'] = ''; // reset
				$credentials['auth-hash'] = ''; // reset
				$credentials['auth-priv'] = []; // reset
			} //end if else
		} else {
			if(
				((string)$credentials['auth-pass'] != '') // or a password has been set explicit
				AND
				(
					((int)\strlen((string)$credentials['auth-pass']) < 5)
					OR
					((int)\strlen((string)$credentials['auth-pass']) > 255)
				)
			) {
				$credentials['auth-error'] = 'Auth Error, Invalid Password Length';
				$credentials['auth-user'] = ''; // reset
				$credentials['auth-pass'] = ''; // reset
				$credentials['auth-hash'] = ''; // reset
				$credentials['auth-priv'] = []; // reset
			} //end if
		} //end if else
		//--
		// IMPORTANT: if the $credentials['auth-error'] is non-empty that means that the Authentication Attemt failed and must be logged as failed, otherwise the error is empty
		//--
		return (array) $credentials;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
