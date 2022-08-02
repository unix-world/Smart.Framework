<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderHttpBasic
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
 * @version 	v.20220730
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthProviderHttpBasic implements \SmartModExtLib\AuthAdmins\AuthProviderInterface {

	// ::

	//================================================================
	public static function GetCredentials(bool $is_https_required) {
		//--
		$credentials = [
			'auth-user' => null,
			'auth-pass' => null,
			'auth-mode' => null,
			'auth-safe' => -1, // auth safety grade ; https basic auth: 100..102 ; http basic auth: 0..2
		];
		//--
		if(!\is_array($_SERVER)) {
			$credentials['auth-user'] = '';
			$credentials['auth-pass'] = '';
			$credentials['auth-mode'] = 'AUTH:SKIP:NOT-SERVER-DATA';
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--
		$is_https = (bool) ((string)\SmartUtils::get_server_current_protocol() === 'https://');
		//--
		if($is_https === true) {
			$credentials['auth-safe'] += 100;
		} else {
			if($is_https_required !== false) {
				$credentials['auth-user'] = '';
				$credentials['auth-pass'] = '';
				$credentials['auth-mode'] = 'AUTH:SKIP:NOT-HTTPS';
				return (array) $credentials; // stop here if is HTTPS required and is not !
			} //end if
		} //end if
		//--
		if(
			\array_key_exists('PHP_AUTH_USER', $_SERVER)
			AND
			\array_key_exists('PHP_AUTH_PW', $_SERVER)
		) { // standard support: apache + php module
			//--
			$credentials['auth-user'] = (string) $_SERVER['PHP_AUTH_USER'];
			$credentials['auth-pass'] = (string) $_SERVER['PHP_AUTH_PW'];
			$credentials['auth-mode'] = 'HTTP-BASIC:PHP';
			$credentials['auth-safe'] += 10;
			//--
		} else { // special support, for PHP FPM SAPI, PHP CGI or non-standard HTTP Servers / non-standard configurations
			//--
			$authheader = null;
			$authmode = null;
			//--
			if(\function_exists('\\getallheaders')) { // FPM SAPI under Apache ; since PHP 7.3 getallheaders works also FPM SAPI ...
				//--
				foreach((array)\getallheaders() as $key => $val) {
					if((string)\strtoupper((string)trim((string)$key)) == 'AUTHORIZATION') {
						$authheader = (string) trim((string)$val);
						$authmode = 'HTTP-BASIC:HEADER-APACHE-AUTHORIZATION';
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
				$authmode = 'HTTP-BASIC:HEADER-SERVER-AUTHORIZATION';
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
				$authmode = 'HTTP-BASIC:HEADER-SERVER-REDIRECT-AUTHORIZATION';
				$credentials['auth-safe'] += 1;
				//--
			} //end if else
			//--
			if(((string)$authheader != '') AND (strlen((string)$authheader) <= 1024)) {
				if(\stripos((string)$authheader, 'Basic ') === 0) {
					$authheader = (string) \trim((string)\substr((string)$authheader, 6));
					if((string)$authheader != '') {
						$authheader = (string) \base64_decode((string)$authheader);
						if(((string)\trim((string)$authheader) != '') AND (\strpos((string)$authheader, ':') !== false)) {
							$authheader = \explode(':', (string)$authheader, 2);
							if(\is_array($authheader) AND ((int)\count($authheader) == 2)) {
								$credentials['auth-user'] = (string) $authheader[0];
								$credentials['auth-pass'] = (string) $authheader[1];
								$credentials['auth-mode'] = (string) $authmode;
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
			//--
			$authheader = null;
			//--
		} //end if else
		//-- {{{SYNC-AUTH-METHODS-NAME}}}
		$credentials['auth-user'] = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-user'])); // filter + trim: for basic auth the username cannot start or end with spaces
		$credentials['auth-pass'] = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-pass'])); // filter + trim: for basic auth the password cannot start or end with spaces
		$credentials['auth-mode'] = (string) 'AUTH:'.\strtoupper((string)$credentials['auth-mode']);
		$credentials['auth-safe'] = (int)    $credentials['auth-safe'];
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
