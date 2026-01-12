<?php
// Class: \SmartModExtLib\AuthAdmins\AuthProviderHttp
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
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
 * Depends: SmartFrameworkSecurity
 *
 * @ignore
 *
 * @version 	v.20251230
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthProviderHttp implements \SmartModExtLib\AuthAdmins\AuthProviderInterface {

	// ::

	//================================================================
	public static function GetCredentials(bool $enable_tokens, bool $disable_basic=false) : array {

		//--
		// IMPORTANT: if the ['auth-error'] is non-empty that means that the Authentication is Invalid by some reason ; otherwise the error should be empty ...
		//--

		//-- init result
		$credentials = (array) self::AUTH_RESULT;
		//--

		//-- reset: inits
		$credentials['auth-error'] 	= 'Authentication NOT found or NOT provided by the Server';
		$credentials['auth-safe'] 	= 0;
		$credentials['auth-user'] 	= '';
		$credentials['auth-pass'] 	= '';
		$credentials['auth-bearer'] = '';
		$credentials['auth-token'] 	= '';
		$credentials['auth-mode'] 	= '';
		//--

		//--
		if(($enable_tokens !== true) AND ($disable_basic !== true)) {
			$credentials['auth-error'] 	 = 'Auth Data is unavailable, all providers are disabled';
			$credentials['auth-safe'] 	-= 10;
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--

		//--
		if(!\is_array($_SERVER)) {
			$credentials['auth-error'] 	 = 'Server Data is unavailable for Authentication purposes';
			$credentials['auth-safe'] 	-= 1;
			return (array) $credentials; // stop here if is HTTPS required and is not !
		} //end if
		//--

		//--
		$authmode = null;
		//--

		//--
		if(
			($disable_basic !== true)
			AND
			\array_key_exists('PHP_AUTH_USER', $_SERVER)
			AND
			\array_key_exists('PHP_AUTH_PW', $_SERVER)
		) { // standard support: apache + php module (Basic Auth)
			//--
			$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.'PHP';
			//--
			$credentials['auth-user'] 	= (string) \trim((string)$_SERVER['PHP_AUTH_USER']); 	// trim username
			$credentials['auth-pass'] 	= (string) \trim((string)$_SERVER['PHP_AUTH_PW']); 		// trim password
			$credentials['auth-bearer'] = ''; // safety reset
			$credentials['auth-token'] 	= ''; // safety reset
			//--
			if((string)\trim((string)$credentials['auth-user']) == '') {
				$credentials['auth-error'] = 'UserName is Empty';
			} elseif((string)\trim((string)$credentials['auth-pass']) == '') {
				$credentials['auth-error'] = 'Password is Empty';
			} else {
				$credentials['auth-error'] = ''; // reset, auth is OK
				$credentials['auth-safe'] 	+= 10;
			} //end if else
			//--
		} else { // special support, for PHP FPM SAPI, PHP CGI or non-standard HTTP Servers / non-standard configurations
			//--
			$authmode = ''; // reset
			//--
			$authheader = ''; // reset
			//--
			if(\function_exists('\\getallheaders')) { // FPM SAPI under Apache ; since PHP 7.3 getallheaders works also FPM SAPI ...
				//--
				foreach((array)\getallheaders() as $key => $val) {
					if((string)\strtoupper((string)\trim((string)$key)) == 'AUTHORIZATION') {
						$authheader = (string) \trim((string)$val);
						$authmode = (string) 'HEADER-APACHE-AUTHORIZATION';
						$credentials['auth-safe'] += 5;
						break; // stop at first
					} //end if
				} //end foreach
				//--
			} elseif(\array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) { // CGI, others, needs setup in config or .htaccess
				//--
				// # IMPORTANT: if not set by Apache it may require in .htaccess or apache config:
				// SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
				//--
				$authheader = (string) \trim((string)$_SERVER['HTTP_AUTHORIZATION']);
				$authmode = (string) 'HEADER-SERVER-AUTHORIZATION';
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
				$authmode = (string) 'HEADER-SERVER-REDIRECT-AUTHORIZATION';
				$credentials['auth-safe'] += 1;
				//--
			} else {
				//--
				$credentials['auth-safe'] 	-= 200;
				$credentials['auth-error'] 	 = 'Authentication NOT found or NOT provided by the Server';
				//--
			} //end if else
			//--
			if(((string)$authheader != '') AND ((int)\strlen((string)$authheader) <= (int)(4096 + 512)) AND ((string)$authmode != '')) { // {{{SYNC-BEARER-JWT-ALLOW-MAX-LEN}}} ; fix: allow 4096+512 header length for Bearer, JWT
				if(($disable_basic !== true) AND (\stripos((string)$authheader, 'Basic ') === 0)) { // 1st try Basic Auth
					$authheader = (string) \trim((string)\substr((string)$authheader, 6));
					if((string)$authheader != '') {
						$authheader = (string) \base64_decode((string)$authheader, true); // B64 STRICT
						if(((string)\trim((string)$authheader) != '') AND (\strpos((string)$authheader, ':') !== false)) {
							$authheader = \explode(':', (string)$authheader, 2); // get just first 2 parts, by first `:` occurence, even if there are multiple :
							if(\is_array($authheader) AND ((int)\count($authheader) == 2)) {
								$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BASIC.$authmode;
								$credentials['auth-user'] 	= (string) \trim((string)($authheader[0] ?? null)); // trim username
								$credentials['auth-pass'] 	= (string) \trim((string)($authheader[1] ?? null)); // trim password
								$credentials['auth-bearer'] = ''; // safety reset
								$credentials['auth-token'] 	= ''; // safety reset
								if((string)\trim((string)$credentials['auth-user']) == '') {
									$credentials['auth-error'] = 'UserName is Empty';
								} elseif((string)\trim((string)$credentials['auth-pass']) == '') {
									$credentials['auth-error'] = 'Password is Empty';
								} else {
									$credentials['auth-error'] = ''; // reset, auth is OK
									$credentials['auth-safe'] += 2;
								} //end if else
							} //end if
						} //end if
					} //end if
				} elseif(($enable_tokens === true) AND (\stripos((string)$authheader, 'Bearer ') === 0)) { // 2nd try Bearer Auth (Tokens)
					$authheader = (string) \trim((string)\substr((string)$authheader, 7));
					if((string)$authheader != '') {
						$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_BEARER.$authmode;
						$credentials['auth-user'] 	= ''; // safety reset
						$credentials['auth-pass'] 	= ''; // safety reset
						$credentials['auth-token'] 	= ''; // safety reset
						$credentials['auth-bearer'] = (string) \trim((string)$authheader); // trim bearer token
						if((string)\trim((string)$credentials['auth-bearer']) == '') {
							$credentials['auth-error'] = 'Bearer Token is Empty';
						} else {
							$credentials['auth-error'] = ''; // reset, auth is OK
							$credentials['auth-safe'] += 7;
						} //end if else
					} //end if
				} elseif(($enable_tokens === true) AND (\stripos((string)$authheader, 'Token ') === 0)) { // 2nd try Token Auth (Tokens)
					$authheader = (string) \trim((string)\substr((string)$authheader, 6));
					if((string)$authheader != '') {
						$authmode = (string) self::AUTH_MODE_PREFIX_HTTP_TOKEN.$authmode;
						$credentials['auth-user'] 	= ''; // safety reset
						$credentials['auth-pass'] 	= ''; // safety reset
						$credentials['auth-bearer'] = ''; // safety reset
						$credentials['auth-token'] = (string) \trim((string)$authheader); // trim token
						if((string)\trim((string)$credentials['auth-token']) == '') {
							$credentials['auth-error'] = 'Token is Empty';
						} else {
							$credentials['auth-error'] = ''; // reset, auth is OK
							$credentials['auth-safe'] += 6;
						} //end if else
					} //end if
				} //end if
			} //end if
			//--
			$authheader = null;
			//--
		} //end if else
		//--

		//--
		$credentials['auth-error'] 	= (string) $credentials['auth-error'];
		$credentials['auth-safe'] 	= (int)    $credentials['auth-safe'];
		//--
		$credentials['auth-user'] 	= (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-user'])); 	// filter + trim: for basic  auth the username cannot start or end with spaces
		$credentials['auth-pass'] 	= (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-pass'])); 	// filter + trim: for basic  auth the password cannot start or end with spaces
		$credentials['auth-bearer'] = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-bearer'])); // filter + trim: for bearer auth the token    cannot start or end with spaces
		$credentials['auth-token']  = (string) \trim((string)\SmartFrameworkSecurity::FilterUnsafeString((string)$credentials['auth-token'])); 	// filter + trim: for bearer auth the token    cannot start or end with spaces
		//-- {{{SYNC-AUTH-METHODS-NAME}}}
		$credentials['auth-mode'] 	= (string) self::AUTH_MODE_PREFIX_AUTHEN.\strtoupper((string)\trim((string)$authmode));
		//--

		//--
		$authmode = null;
		//--

		//-- validations checks
		if(
			((string)$credentials['auth-user'] != '') // only if a username has been set explicit, avoid validate if empty
			AND
			(
				((int)\strlen((string)$credentials['auth-user']) < 3)
				OR
				((int)\strlen((string)$credentials['auth-user']) > 64)
				OR
				(!\preg_match((string)self::REGEX_VALID_USERNAME_OR_TOKEN_BEARER, (string)(string)$credentials['auth-user']))
			)
		) {
			//--
			if((string)$credentials['auth-bearer'] != '') {
				$credentials['auth-error'] = 'When UserName/Password is set, the Bearer Token should not be set';
			} else if((string)$credentials['auth-token'] != '') {
				$credentials['auth-error'] = 'When UserName/Password is set, the Token should not be set';
			} elseif((string)$credentials['auth-pass'] == '') {
				$credentials['auth-error'] = 'When UserName is set, the Password should be set too';
			} else {
				$credentials['auth-error'] = 'UserName is Invalid, must be between 3 and 64 characters long and contain only ASCII printable characters';
			} //end if else
			//--
		} elseif(
			((string)$credentials['auth-bearer'] != '') // only if a bearer token has been set explicit, avoid validate if empty
			AND
			(
				((int)\strlen((string)$credentials['auth-bearer']) < 16)
				OR
				((int)\strlen((string)$credentials['auth-bearer']) > 4096) // {{{SYNC-BEARER-JWT-ALLOW-MAX-LEN}}} ; limit of HTTP header is 8k ; but the remaining part is reserved for other headers inc. cookies
				OR
				(!\preg_match((string)self::REGEX_VALID_USERNAME_OR_TOKEN_BEARER, (string)(string)$credentials['auth-bearer']))
			)
		) {
			//--
			if(
				((string)$credentials['auth-user'] != '')
				OR
				((string)$credentials['auth-pass'] != '')
			) {
				$credentials['auth-error'] = 'When Bearer Token is set, the UserName/Password should not be set';
			} else {
				$credentials['auth-error'] = 'Bearer Token is Invalid, must be between 16 and 4096 characters long and contain only ASCII printable characters';
			} //end if else
			//--
		} elseif(
			((string)$credentials['auth-token'] != '') // only if a token has been set explicit, avoid validate if empty
			AND
			( // {{{SYNC-VALIDATE-STK-TOKEN-LENGTH}}}
				((int)\strlen((string)$credentials['auth-token']) < 46) // (user:min=3) + # + (token:min=42)
				OR
				((int)\strlen((string)$credentials['auth-token']) > 72) // (user:max=25) + # + (token:min=46)
				OR
				(!\preg_match((string)self::REGEX_VALID_USERNAME_OR_TOKEN_BEARER, (string)(string)$credentials['auth-token']))
			)
		) {
			//--
			if(
				((string)$credentials['auth-user'] != '')
				OR
				((string)$credentials['auth-pass'] != '')
			) {
				$credentials['auth-error'] = 'When Token is set, the UserName/Password should not be set';
			} else {
				$credentials['auth-error'] = 'Token is Invalid, must be between 46 and 72 characters long and contain only ASCII printable characters';
			} //end if else
			//--
		} //end if else
		//--

		//-- final check
		if((string)$credentials['auth-error'] != '') {
			//-- reset
			$credentials['auth-user'] 	= '';
			$credentials['auth-pass'] 	= '';
			$credentials['auth-bearer'] = '';
			$credentials['auth-token'] 	= '';
			//--
		} //end if
		//--

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
