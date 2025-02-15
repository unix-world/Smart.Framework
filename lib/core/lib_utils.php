<?php
// [LIB - Smart.Framework / Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Utils
//======================================================



// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartUtils - provides various utility functions for Smart.Framework
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartUnicode, SmartValidator, SmartHashCrypto, SmartAuth, SmartFileSysUtils, SmartFileSystem, SmartFrameworkSecurity, SmartFrameworkRegistry, SmartValidator, SmartParser ; optional-constants: SMART_FRAMEWORK_SECURITY_CRYPTO, SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME, SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN, SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE, SMART_FRAMEWORK_SRVPROXY_ENABLED, SMART_FRAMEWORK_SRVPROXY_CLIENT_IP, SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO, SMART_FRAMEWORK_SRVPROXY_SERVER_IP, SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN, SMART_FRAMEWORK_SRVPROXY_SERVER_PORT, SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS, SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, SMART_FRAMEWORK_IDENT_ROBOTS
 * @version 	v.20250214
 * @package 	Application:Utils
 *
 */
final class SmartUtils {

	// ::

	public const GENERIC_VALUE_OS_BROWSER_IP = '[?]';

	private static $AppReleaseHash = null;

	private static $cache = [];

	private const VALID_HEADERS_CLIENT_OR_PROXY_IP = [
		'REMOTE_ADDR', // the REMOTE_ADDR must be the 1st in this list, ALWAYS ; it will be used if set so ONLY when behind a proxy and the SMART_FRAMEWORK_SRVPROXY_ENABLED is enabled, but should not be missing !!!
		'HTTP_X_FORWARDED_CLIENT_IP', // this is non-standard but can be used by haproxy without interfere with standard headers to be trusted as the Visitor Real IP !
		'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR_IP', // the HTTP_X_FORWARDED_FOR is de facto standard # see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For
		'HTTP_X_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_VIA',
		'HTTP_X_CLUSTER_CLIENT_IP', // rackspace
		'HTTP_TRUE_CLIENT_IP', // akamai / cloudflare
		'HTTP_CF_CONNECTING_IP', // cloudflare
		'HTTP_INCAP_CLIENT_IP', // Incapsula
	];

	private const VALID_HEADERS_SERVER_PROTO = [
		'HTTP_X_FORWARDED_PROTO' => [
			'https' => 'https',
			'http' 	=> 'http' ],
		'HTTP_X_FORWARDED_HTTPS' => [
			'on' 	=> 'https',
			'off' 	=> 'http',
		],
	];

	private const VALID_HEADERS_SERVER_PORT 	= [ 'SERVER_PORT', 'HTTP_X_FORWARDED_PORT',   'HTTP_X_PORT' ]; 		// can be on the same port on a different IP/domain thus can use by default: 	'SERVER_PORT'
	private const VALID_HEADERS_SERVER_DOMAIN 	= [ 'SERVER_NAME', 'HTTP_X_FORWARDED_DOMAIN', 'HTTP_X_DOMAIN' ]; 	// if on the same domain can use by default: 									'SERVER_NAME'
	private const VALID_HEADERS_SERVER_IP 		= [ 'SERVER_ADDR', 'HTTP_X_FORWARDED_IP',     'HTTP_X_IP' ]; 		// if on the same IP can use by default: 										'SERVER_ADDR'

	private const FAKE_IP_CLIENT = '0.0.0.0'; 			// must differ from FAKE_IP_SERVER ; must be 0.0.0.0 to show as undetected
	private const FAKE_IP_SERVER = '256.256.256.256'; 	// must differ from FAKE_IP_CLIENT ; must be 256.256.256.256 that does not exists ... so there is no risk to be solved on something that exists ...


	//================================================================
	// get the App Release Hash based on Framework Version.Release.ModulesRelease
	// Avoid run this function before Smart.Framework was loaded, it depends on it
	// THIS FUNCTION IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!
	public static function get_app_release_hash() : string {
		//--
		if((string)self::$AppReleaseHash == '') {
			$hash = (string) SmartHashCrypto::crc32b((string)(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '').(string)(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : '').(string)(defined('SMART_APP_MODULES_RELEASE') ? SMART_APP_MODULES_RELEASE : ''), true); // get as b36
			self::$AppReleaseHash = (string) strtolower((string)$hash);
		} //end if
		//--
		return (string) trim((string)ltrim((string)self::$AppReleaseHash, '0'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// return the size of all current used cookies for the current domain
	public static function cookies_current_size_used_on_domain() : int {
		//--
		return (int) strlen((string)SmartFrameworkRegistry::getServerVar('HTTP_COOKIE'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// return the max size of all current used cookies for the current domain
	public static function cookie_size_max() : int {
		//--
		return (int) SMART_FRAMEWORK_MAX_BROWSER_COOKIE_SIZE; // the max cookie size is 4096 includding name, time, domain, ... and the rest of cookie data, thus use max safe is 3072 bytes per cookie, as the rest will be reserved for UID and Session Cookies
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to get the cookie default expiration
	public static function cookie_default_expire() : int {
		//--
		$expire = 0;
		if(defined('SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME')) {
			$expire = (int) SMART_FRAMEWORK_COOKIES_DEFAULT_LIFETIME;
			if($expire <= 0) {
				$expire = 0;
			} //end if
		} //end if
		//--
		return (int) $expire;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to get the cookie domain
	public static function cookie_default_domain() : string {
		//--
		$cookie_domain = '';
		if((defined('SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN')) AND ((string)SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN != '')) {
			if((string)SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN == '*') {
				$cookie_domain = (string) self::get_server_current_basedomain_name();
			} else {
				$cookie_domain = (string) SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN;
			} //end if
		} //end if
		//--
		return (string) $cookie_domain;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to get the cookie samesite policy: None, Lax, Strict, Empty
	public static function cookie_default_samesite_policy() : string {
		//--
		$cookie_samesite_policy = '';
		if((defined('SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE')) AND ((string)SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE != '')) {
			$cookie_samesite_policy = (string) ucfirst((string)strtolower((string)trim((string)SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE)));
		} //end if
		//--
		switch((string)ucfirst((string)strtolower((string)trim((string)SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE)))) {
			case 'None':
			case 'Lax':
			case 'Strict':
				break;
			case 'Empty':
			default:
				$cookie_samesite_policy = ''; // empty or invalid: fallback to empty ...
		} //end switch
		//--
		return (string) $cookie_samesite_policy;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to get cookies as it takes care of safe filtering of cookie values
	public static function get_cookie(?string $cookie_name) : ?string {
		//--
		return SmartFrameworkRegistry::getCookieVar((string)$cookie_name); // mixed: null / string
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to check if a cookie is set
	public static function isset_cookie(?string $cookie_name) : bool {
		//--
		return (bool) SmartFrameworkRegistry::issetCookieVar((string)$cookie_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to set cookies as it takes care to set them according with the cookie domain if set or not per app ; use zero expire time for cookies that will expire with browser session
	public static function set_cookie(?string $cookie_name, ?string $cookie_data, ?int $expire_seconds=0, ?string $cookie_path='/', ?string $cookie_domain='@', ?string $cookie_samesite='@', bool $cookie_secure=false, bool $cookie_httponly=false) : bool {
		//--
		if(headers_sent()) {
			return false;
		} //end if
		//--
		$cookie_name = (string) trim((string)$cookie_name);
		if(((string)$cookie_name == '') OR (!SmartFrameworkSecurity::ValidateUrlVariableName((string)$cookie_name))) { // {{{SYNC-REQVARS-VALIDATION}}}
			return false;
		} //end if
		//--
		$expire_seconds = (int) $expire_seconds;
		if((int)$expire_seconds > 0) { // set with an expire date in the future
			$expire_seconds = (int) ((int)time() + (int)$expire_seconds); // now + (seconds)
		} elseif((int)$expire_seconds < 0) { // unset (set expire date in the past)
			$expire_seconds = (int) ((int)time() - (int)((3600 * 24) - (int)$expire_seconds)); // now - (1 day) - (seconds)
			$cookie_data = null; // unsetting a cookie needs an empty value
		} else { // expire by session
			$expire_seconds = 0; // explicit set to zero
		} //end if
		if((string)$cookie_data == '') {
			$cookie_data = null;
			$expire_seconds = -1; // explicit set to -1 to unset an empty cookie
		} //end if
		//--
		if((string)$cookie_domain == '@') { // if empty or non @ leave as it is
			$cookie_domain = (string) self::cookie_default_domain();
		} //end if
		//--
		$cookie_samesite = (string) trim((string)$cookie_samesite);
		if((string)$cookie_samesite == '@') {
			$cookie_samesite = (string) self::cookie_default_samesite_policy();
		} //end if
		if((string)$cookie_samesite != '') {
			$cookie_samesite = (string) ucfirst((string)strtolower((string)$cookie_samesite));
		} //end if
		switch((string)$cookie_samesite) {
			case 'None':
				if((string)self::get_server_current_protocol() == 'https://') { // fix: try to not set COOKIE SECURE except if over HTTPS ; some new browsers, ex: Chromium will not accept None policy cookies over plain HTTP but only over HTTPS ; but if on HTTP and not HTTPS with Chromium a None policy cookie is useless even with COOKIE SECURE set ON if already on HTTP plain ...
					$cookie_secure = true; // {{{SYNC-COOKIE-POLICY-NONE}}} ; new browsers (ex: Chromium) require this if SameSite cookie policy is set explicit to None ; but anyway, if not already on a secure protocol try to not set this, will still work in Firefox
				} //end if
				break;
			case 'Lax':
			case 'Strict':
				break;
			case 'Empty':
			default:
				$cookie_samesite = '';
		} //end switch
		//--
		$options = [ // {{{SYNC-PHP-COOKIE-OPTIONS}}}
			'expires' 	=> (int) $expire_seconds,
			'path' 		=> (string) $cookie_path
		]; // by default set it without specific domain (will using current IP or subdomain)
		//--
		if((string)$cookie_domain != '') {
			$options['domain'] = (string) $cookie_domain; // set cookie using domain (if running on IP will be set on current IP)
		} //end if
		if((string)$cookie_samesite != '') {
			$options['samesite'] = (string) $cookie_samesite; // use the same site policy if valid
		} //end if
		if($cookie_secure === true) {
			$options['secure'] = true; // if this is set the cookie will be sent only via HTTPS secure connections
		} //end if
		if($cookie_httponly === true) {
			$options['httponly'] = true; // WARNING: a cookie with HttpOnly cannot be accessed by javascript, so use it with precaution
		} //end if
		//--
		SmartFrameworkRegistry::setCookieVar((string)$cookie_name, (string)$cookie_data);
		//--
		return (bool) @setcookie((string)$cookie_name, (string)$cookie_data, (array)$options); // req. PHP 7.3+
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// use this function to unset cookies as it takes care to set them according with the cookie domain if set or not per app
	public static function unset_cookie(?string $cookie_name, ?string $cookie_path='/', ?string $cookie_domain='@', ?string $cookie_samesite='@', bool $cookie_secure=false, bool $cookie_httponly=false) : bool {
		//--
		return (bool) self::set_cookie((string)$cookie_name, null, -1, (string)$cookie_path, (string)$cookie_domain, (string)$cookie_samesite, (bool)$cookie_secure, (bool)$cookie_httponly);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// obfuscate an URL parameter using b64s encode
	public static function url_obfs_encode(?string $y_val) : string {
		//--
		if((string)$y_val == '') {
			return '';
		} //end if
		//--
		return (string) Smart::b64s_enc((string)$y_val);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// de-obfuscate an URL parameter using b64s strict decode + safe filter
	public static function url_obfs_decode(?string $y_enc_val) : string {
		//--
		if((string)$y_enc_val == '') {
			return '';
		} //end if
		//--
		return (string) SmartFrameworkSecurity::FilterUnsafeString((string)Smart::b64s_dec((string)$y_enc_val, true)); // B64 STRICT
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// encrypt an URL parameter using 2F and app private key
	public static function url_obfs_encrypt(?string $y_val) : string {
		//--
		if((string)$y_val == '') {
			return '';
		} //end if
		//--
		$enc = (string) SmartCipherCrypto::tf_encrypt((string)$y_val);
		if(strpos((string)$enc, SmartCipherCrypto::SIGNATURE_2FISH_V1_DEFAULT) === 0) {
			$enc = (string) substr((string)$enc, (int)strlen((string)SmartCipherCrypto::SIGNATURE_2FISH_V1_DEFAULT));
		} //end if
		//--
		return (string) $enc;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// decrypt an URL parameter using 2F and app private key
	public static function url_obfs_decrypt(?string $y_enc_val) : string {
		//--
		if((string)$y_enc_val == '') {
			return '';
		} //end if
		//--
		return (string) SmartCipherCrypto::tf_decrypt((string)SmartCipherCrypto::SIGNATURE_2FISH_V1_DEFAULT.$y_enc_val);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// will return the time interval in days between 2 dates (negative = past ; positive = future)
	public static function date_interval_days(?string $y_date_now, ?string $y_date_past) : int {
		//--
		$y_date_now  = (string) date('Y-m-d', @strtotime((string)$y_date_now));
		$y_date_past = (string) date('Y-m-d', @strtotime((string)$y_date_past));
		//--
		$tmp_ux_start = (int) date('U', @strtotime((string)$y_date_now));  // get date now in seconds
		$tmp_ux_end   = (int) date('U', @strtotime((string)$y_date_past)); // get date past in seconds
		//--
		$tmp_ux_diff = (int) Smart::format_number_int((int)$tmp_ux_start - (int)$tmp_ux_end); // calc interval in seconds
		$tmp_ux_diff = (int) Smart::format_number_int((int)ceil((int)$tmp_ux_diff / (int)(60 * 60 * 24))); // calc interval in days
		//--
		return (int) $tmp_ux_diff;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ Calculate DateTime with FIXED TimeZoneOffset
	// this will NOT count the DayLight Savings when calculating date and time from GMT with offset
	public static function datetime_fixed_offset(?string $y_timezone_offset, ?string $ydate) : string {
		//--
		// y_timezone_offset 	:: +0300 :: date('O')
		// ydate 				:: yyyy-mm-dd H:i:s
		//--
		$tmp_tz_offset_sign = (string) substr((string)$y_timezone_offset, 0, 1);
		$tmp_tz_offset_hour = (string) substr((string)$y_timezone_offset, 1, 2);
		$tmp_tz_offset_mins = (string) substr((string)$y_timezone_offset, 3, 2);
		//--
		return (string) date('Y-m-d H:i:s', @strtotime((string)$ydate.' '.$tmp_tz_offset_sign.''.$tmp_tz_offset_hour.' hours '.$tmp_tz_offset_sign.''.$tmp_tz_offset_mins.' minutes'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function pretty_print_var($y_var, ?int $indent=0, bool $jsstyle=false) : string {
		//--
		$out = '';
		//--
		$marker = '=>';
		if($jsstyle === true) {
			$marker = ':';
		} //end if
		//--
		if(is_array($y_var)) {
			//--
			$spaces = '';
			for($i=0; $i<(int)$indent; $i++) {
				$spaces .= "\t";
			} //end for
			$indent += 1;
			//--
			$out .= $spaces.'['."\n";
			//--
			foreach($y_var as $key => $val) {
				//--
				$out .= $spaces;
				//--
				if(is_array($val)) {
					//--
					$out .= "\t".$key.' '.$marker.' '.self::pretty_print_var($val, $indent);
					//--
				} else {
					//--
					if(is_object($val)) { // {{{SYNC-UTILS-PRETTY-PRINT-VAR}}}
						$val = '!OBJECT! # `'.get_class($val).'`';
					} elseif($val === null) {
						$val = 'NULL';
					} elseif($val === false) {
						$val = 'FALSE';
					} elseif($val === true) {
						$val = 'TRUE';
					} elseif(!is_numeric($val)) {
						$val = '`'.$val.'`';
					} //end if else
					//--
					$out .= "\t".$key.' '.$marker.' '.$val;
					//--
				} //end if else
				//--
				$out .= "\n";
				//--
			} //end foreach
			//--
			$out .= $spaces.']';
			//--
		} else {
			//--
			$val = $y_var; // mixed
			//--
			if(is_object($val)) { // {{{SYNC-UTILS-PRETTY-PRINT-VAR}}}
				$val = '!OBJECT!';
			} elseif($val === null) {
				$val = 'NULL';
			} elseif($val === false) {
				$val = 'FALSE';
			} elseif($val === true) {
				$val = 'TRUE';
			} elseif(!is_numeric($val)) {
				$val = '`'.$val.'`';
			} //end if else
			//--
			$out = (string) $val;
			//--
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function pretty_print_bytes(int $y_bytes, int $y_decimals=1, ?string $y_separator=' ', int $y_base=1000) : string {
		//--
		$y_decimals = (int) $y_decimals;
		if($y_decimals < 0) {
			$y_decimals = 0;
		} //end if
		if($y_decimals > 4) {
			$y_decimals = 4;
		} //end if
		//--
		if((int)$y_base === 1024) {
			$y_base = (int) 1024;
		} else {
			$y_base = (int) 1000;
		} //end if else
		//--
		if(!is_int($y_bytes)) {
			return (string) $y_bytes;
		} //end if
		//--
		if($y_bytes < $y_base) {
			return (string) Smart::format_number_int($y_bytes).$y_separator.'bytes';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) Smart::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'KB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) Smart::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'MB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		if($y_bytes < $y_base) {
			return (string) Smart::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'GB';
		} //end if
		//--
		$y_bytes = $y_bytes / $y_base;
		//--
		return (string) Smart::format_number_dec($y_bytes, $y_decimals, '.', '').$y_separator.'TB';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// min: 0 ; max: 4999
	public static function number_to_roman(?int $num) : string {
		//--
		$n = (int) $num;
		//--
		if((int)$n === 0) {
			return (string) '0';
		} //end if
		if((int)$n < 0) {
			return 'ERR:MIN:0';
		} //end if
		if((int)$n > 4999) {
			return 'ERR:MAX:4999';
		} //end if
		//--
		$result = '';
		//-- declare a lookup array that we will use to traverse the number:
		$lookup = [ 'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1 ];
		//--
		foreach($lookup as $roman => $value) {
			//--
			if((int)$value <= 0) {
				return 'ERR:DIV:0'; // avoid division by zero
			} //end if
			//-- determine the number of matches
			$matches = (int) intval((int)$n / (int)$value);
			//-- store that many characters
			$result .= (string) str_repeat((string)$roman, (int)$matches);
			//-- substract that from the number
			$n = (int) ((int)$n % (int)$value);
			//--
		} //end foreach
		//--
		return (string) $result; // the roman numeral should be built, return it
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// min: 0 ; max: MMMMCMXCIX
	public static function roman_to_number(?string $roman) : int {
		//--
		// based on PHP roman to number, author: Sterling Hughes
		//--
		$roman = (string) $roman;
		//--
		if(!preg_match('/^(?i:(?=[MDCLXVI])((M{0,4})((C[DM])|(D?C{0,3}))?((X[LC])|(L?XX{0,2})|L)?((I[VX])|(V?(II{0,2}))|V)?))$/i', (string)$roman)) {
			return (int) 0;
		} //end if
		//--
		$conv = [
			[ 'letter' => 'I', 'number' =>    1 ],
			[ 'letter' => 'V', 'number' =>    5 ],
			[ 'letter' => 'X', 'number' =>   10 ],
			[ 'letter' => 'L', 'number' =>   50 ],
			[ 'letter' => 'C', 'number' =>  100 ],
			[ 'letter' => 'D', 'number' =>  500 ],
			[ 'letter' => 'M', 'number' => 1000 ],
			[ 'letter' => '0', 'number' =>    0 ],
		];
		//--
		$arabic = 0;
		$state = 0;
		$sidx = 0;
		$len = (int) ((int)strlen((string)$roman) - 1);
		//--
		while((int)$len >= 0) {
			//--
			$i = 0;
			$sidx = (int) $len;
			//--
			while($conv[$i]['number'] > 0) {
				//--
				if((string)strtoupper((string)$roman[$sidx]) == (string)$conv[$i]['letter']) {
					if($state > $conv[$i]['number']) {
						$arabic -= $conv[$i]['number'];
					} else {
						$arabic += $conv[$i]['number'];
						$state = $conv[$i]['number'];
					} //end if else
				} //end if
				//--
				$i++;
				//--
			} //end while
			//--
			$len--;
			//--
		} //end while
		//--
		return (int) $arabic;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// extract HTML title (must not exceed 128 characters ; recommended is max 65) ; no changes
	public static function extract_title(?string $ytxt, ?int $y_limit=65, bool $clear_numbers=false) : string {
		//--
		$ytxt = (string) Smart::stripTags((string)$ytxt, false); // will do strip tags
		$ytxt = (string) Smart::normalize_spaces((string)$ytxt); // will do normalize spaces
		//--
		if($clear_numbers === true) {
			$ytxt = (string) self::cleanup_numbers_from_text((string)$ytxt); // do after strip tags to avoid break html
		} //end if
		$ytxt = (string) self::cleanup_extra_spaces_from_text((string)$ytxt);
		//--
		$ytxt = (string) trim((string)$ytxt);
		if((string)$ytxt == '') {
			return '';
		} //end if
		//--
		$y_limit = Smart::format_number_int($y_limit, '+');
		if($y_limit < 10) {
			$y_limit = 10;
		} elseif($y_limit > 255) { // for other purposes, leave a max of 255
			$y_limit = 255;
		} //end if
		//--
		return (string) trim((string)Smart::text_cut_by_limit((string)$ytxt, (int)$y_limit, false, ''));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// extract HTML meta description (must not exceed 256 characters ; recommended is max 155 characters)
	public static function extract_description(?string $ytxt, ?int $y_limit=155, bool $clear_numbers=false) : string {
		//--
		$ytxt = (string) trim((string)$ytxt);
		if((string)$ytxt == '') {
			return '';
		} //end if
		//--
		$y_limit = Smart::format_number_int($y_limit, '+');
		if($y_limit < 10) {
			$y_limit = 10;
		} //end if
		if($y_limit > 1024) { // for other purposes, leave a max of 1024
			$y_limit = 1024;
		} //end if
		//--
		$arr = (array) self::extract_words_from_text_html($ytxt); // will do strip tags + normalize spaces
		$ytxt = (string) implode(' ', (array)$arr);
		$arr = null; // free mem
		//--
		if($clear_numbers === true) {
			$ytxt = (string) self::cleanup_numbers_from_text((string)$ytxt); // do after strip tags to avoid break html
		} //end if
		$ytxt = (string) self::cleanup_extra_spaces_from_text((string)$ytxt);
		//--
		return (string) trim((string)Smart::text_cut_by_limit((string)$ytxt, (int)$y_limit, false, ''));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// prepare HTML compliant keywords from a string
	// max is 1024 words, recommended is 97 words
	// will find the keywords listed descending by the occurence number
	// keywords with higher frequency will be listed first
	// We add Strategy: Max 2% up to 7% of keywords from existing text (SEO req.)
	public static function extract_keywords(?string $ytxt, ?int $y_count=97, bool $clear_numbers=true) : string {
		//--
		$ytxt = (string) trim((string)$ytxt);
		if((string)$ytxt == '') {
			return '';
		} //end if
		//--
		$y_count = (int) Smart::format_number_int($y_count, '+');
		if($y_count < 2) {
			$y_count = 2;
		} //end if
		if($y_count > 4096) { // for other purposes, leave a max of 4096
			$y_count = 4096;
		} //end if
		//--
		$ytxt = (string) str_replace(',', ' ', (string)SmartUnicode::str_tolower((string)$ytxt));
		$arr = (array) self::extract_words_from_text_html((string)$ytxt); // will do strip tags + normalize spaces
		if((int)Smart::array_size($arr) > 0) {
			$arr = (array) array_unique($arr);
		} //end if
		//--
		$kw = [];
		foreach($arr as $kk => $vv) { // array_unique will drop some keys so cannot go with for{} here
			//--
			$tmp_word = (string) trim((string)str_replace(['`', '~', '!', '@', '#', '$', '%', '^', '*', '(', ')', '_', '+', '=', '[', ']', '{', '}', '|', '\\', '/', '?', '<', '>', ',', ';', '"', "'"], ' ', (string)$vv));
			$tmp_word = (string) trim((string)$tmp_word, ':'); // fix: this must not be replaced, just trimmed if on margins
			$tmp_word = (string) preg_replace("/(\.)\\1+/", '.', (string)$tmp_word); // suppress multiple . dots and replace with single dot
			$tmp_word = (string) preg_replace("/(\-)\\1+/", '-', (string)$tmp_word); // suppress multiple - minus signs and replace with single minus sign
			$tmp_word = (string) trim((string)$tmp_word, '.-'); // trim left or right dots and minus signs
			//--
			if($clear_numbers === true) {
				$tmp_word = (string) self::cleanup_numbers_from_text((string)$tmp_word); // do on each keyword after all processing
			} //end if
			//--
			$tmp_word = (string) trim((string)$tmp_word);
			//--
			if((string)$tmp_word != '') {
				if(!array_key_exists((string)$tmp_word, $kw)) {
					$kw[(string)$tmp_word] = 0;
				} //end if
				$kw[(string)$tmp_word]++;
			} //end if
			//--
			if((int)Smart::array_size($kw) >= (int)$y_count) {
				break;
			} //end if
			//--
		} //end for
		//--
		return (string) trim((string)implode(', ', (array)array_keys((array)$kw)));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// prepare HTML compliant keywords from a string
	public static function extract_words_from_text_html(?string $ytxt) : array {
		//--
		$ytxt = Smart::stripTags((string)$ytxt, false);
		$ytxt = Smart::normalize_spaces((string)$ytxt);
		//--
		return (array) explode(' ', (string)$ytxt);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function cleanup_extra_spaces_from_text(?string $ytxt) : string {
		//--
		return (string) trim((string)preg_replace('/\s+/', ' ', (string)$ytxt)); // remove extra spaces from a text and replace them with single space
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function cleanup_numbers_from_text(?string $ytxt) : string {
		//-- TODO: keep version: '/(v|r)[0-9\.]+(x|X|y|Y|z|Z)?/'
		return (string) trim((string)preg_replace('/(\b|\#)[0-9\(\)\[\]\-\:\+\.]+(\b|\#)/', ' ', ' '.$ytxt.' ')); // remove numbers from a text
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function conform_serialized_js_object_form(?string $jsonStr, string $dataKey) : array {
		//-- r.20241226.2358
		// normalize serialized form data from jQuery.serializeArray(), created by smartJ$Browser.SerializeFormAsObject()
		//--
		$regexValidKey = '/[a-zA-Z0-9\-]{1,64}/';
		//--
		$jsonStr = (string) trim((string)$jsonStr);
		if((string)$jsonStr == '') {
			return [];
		} //end if
		if((int)strlen((string)$jsonStr) > 65535) {
			return [];
		} //end if
		//--
		$json = Smart::json_decode((string)$jsonStr);
		if((int)Smart::array_size($json) <= 0) {
			return [];
		} //end if
		if((int)Smart::array_size($json) > 16384) {
			return [];
		} //end if
		if((int)Smart::array_type_test($json) != 2) { // expects associative array (key/val)
			return [];
		} //end if
		//--
		$dataKey = (string) trim((string)$dataKey);
		if((string)$dataKey == '') {
			return [];
		} //end if
		if(((string)$dataKey == '') || (!preg_match((string)$regexValidKey, (string)$dataKey))) {
			return [];
		} //end if
		//--
		$arr = [];
		foreach($json as $key => $val) {
			//--
			if(((string)$key == '') || (!preg_match((string)$regexValidKey, (string)$key))) {
				return [];
			} //end if
			//--
			if(!is_array($val)) {
				return [];
			} //end if
			if((int)Smart::array_type_test($val) != 2) { // expects associative array (key/val)
				return [];
			} //end if
			//--
			if((int)Smart::array_size(($val['#'] ?? null)) <= 0) {
				return [];
			} //end if
			//--
			$levels = ($val['#']['levels'] ?? null);
			if(!Smart::is_nscalar($levels)) {
				return [];
			} //end if
			$levels = (int) intval((string)$levels);
			if((int)$levels < 0) { // can be zero if there is no nested data, but no lower than zero
				return [];
			} //end if
			//--
			$keys = ($val['#']['keys'] ?? null);
			if(!Smart::is_nscalar($keys)) {
				return [];
			} //end if
			$keys = (int) intval((string)$keys);
			if((int)$keys <= 0) { // cannot be zero or lower
				return [];
			} //end if
			//--
			$size = ($val['#']['size'] ?? null);
			if(!Smart::is_nscalar($size)) {
				return [];
			} //end if
			$size = (int) intval((string)$size);
			if((int)$size <= 0) { // cannot be zero or lower
				return [];
			} //end if
			//--
			if((int)$keys > (int)$size) {
				return [];
			} //end if
			//--
			if((int)Smart::array_size(($val[(string)$dataKey] ?? null)) <= 0) {
				return [];
			} //end if
			if((int)Smart::array_size(($val[(string)$dataKey] ?? null)) != (int)$keys) {
				return [];
			} //end if
			//--
			$lData = 0;
			$data = [];
			$items = [];
			foreach($val[(string)$dataKey] as $kk => $vv) {
				if(is_array($vv)) {
					if((int)Smart::array_type_test($vv) == 2) { // associative array
						if((int)Smart::array_size($vv) > 512) {
							return [];
						} //end if
						$data[(string)$kk] = (array) $vv;
						$lData += count((array)$vv);
					} else if((int)Smart::array_type_test($vv) == 1) { // non-associative array (list)
						$items[(string)$kk] = (array) $vv;
					} //end if else
				} else if(Smart::is_nscalar($vv)) { // scalar (string)
					if((int)strlen((string)$vv) > 8192) {
						return [];
					} //end if
					$data[(string)$kk] = (string) $vv;
					$lData++;
				} //end if
			} //end foreach
			if((int)Smart::array_size($items) > 512) {
				return [];
			} //end if
			if((int)Smart::array_size($data) > 512) {
				return [];
			} //end if
			//--
			$arr[(string)$key] = (array) $data;
			//--
			if((int)Smart::array_size($items) > 0) {
				//--
				$total = (int) ((int)Smart::array_size($data) + (int)Smart::array_size($items));
				if((int)$total != (int)$keys) {
					return [];
				} //end if
				$diff = (int) ((int)$size - (int)$lData);
				$delta = $diff / (int)Smart::array_size($items);
				if(!is_int($delta)) { // check: the diff size divided to number of items must be an integer, that ~ means each item have the same size
					return [];
				} //end if
				$delta = (int) $delta;
				if((int)$delta < 0) {
					return [];
				} //end if
				//--
				$ikeys = [];
				foreach($items as $ik => $iv) {
					if(!is_array($iv)) {
						return [];
					} //end if
					if((int)Smart::array_type_test($iv) != 1) { // expects non-associative array (list)
						return [];
					} //end if
					if((int)Smart::array_size($iv) != (int)$delta) {
						return [];
					} //end if
					$ik = (string) trim((string)$ik);
					if((string)$ik == '') {
						return [];
					} //end if
					$ikeys[] = (string) $ik;
				} //end foreach
				$arr[(string)$key.':@'] = [];
				for($i=0; $i<$delta; $i++) {
					$item = [];
					for($j=0; $j<count($ikeys); $j++) {
						$itk = (string) $ikeys[$j];
						if(!Smart::is_nscalar(($items[(string)$itk][$i] ?? null))) {
							return [];
						} //end if
						$item[(string)$itk] = (string) ($items[(string)$itk][$i] ?? null);
					} //end for
					$arr[(string)$key.':@'][$i] = (array) $item;
				} //end for
				//--
			} //end if
			//--
		} //end foreach
		//--
		//print_r($arr); print_r($json); die();
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Reads and return one Uploaded File
	 *
	 * @param STRING 	$var_name					:: The HTML Variable Name
	 * @param INTEGER 	$var_index					:: The HTML Variable Index: -1 for single file uploads ; 0..n for multi-file uploads ; DEFAULT is -1
	 * @param INTEGER 	$max_size					:: The max file size in bytes that would be accepted ; set to zero for allow maximum size supported by PHP via INI settings ; DEFAULT is zero
	 * @param STRING	$allowed_extensions			:: The list of allowed file extensions ; Default is '' ; Example to restrict to several extensions: '<ext1>,<ext2>,...<ext100>,...' ; set to empty string to allow all extenstions supported via Smart.Framework INI: SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS / SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS
	 * @return ARRAY								:: array [ status => 'OK' | 'WARN' | 'ERR', 'message' => '' | 'WARN Message' | 'ERR Message', 'msg-code' => 0..n, 'filename' => '' | 'filename.ext', 'filetype' => '' | 'ext', 'filesize' => Bytes, 'filecontent' => '' | 'the Contents of the file ...' ]
	 */
	public static function read_uploaded_file(?string $var_name, ?int $var_index=-1, ?int $max_size=0, ?string $allowed_extensions='') : array {
		//-- {{{SYNC-HANDLE-F-UPLOADS}}} v.20220509
		$var_name 	= (string) trim((string)$var_name);
		$var_index 	= (int)    $var_index; // can be negative or 0..n
		$max_size 	= (int)    Smart::format_number_int($max_size,'+');
		if($max_size <= 0) {
			$max_size = (int) SmartFileSysUtils::maxUploadFileSize();
		} //end if
		$allowed_extensions = (string) trim((string)$allowed_extensions);
		//--
		$out = [
			'status' 		=> 'ERR', 			// 'OK' | 'WARN' | 'ERR'
			'message' 		=> '???', 			// '' | 'WARN Message' | 'ERR Message'
			'msg-code' 		=> -999, 			// Message Code
			'filename' 		=> '', 				// '' | 'filename.ext'
			'filetype' 		=> '', 				// '' | 'ext'
			'filesize' 		=> 0, 				// Bytes
			'filecontent' 	=> '' 				// '' | 'the Contents of the file ...'
		];
		//--
		if((string)$var_name == '') {
			$out['status'] = 'ERR';
			$out['message'] = 'Invalid File VarName for Upload';
			$out['msg-code'] = 2;
			return (array) $out;
		} //end if
		//--
		if(Smart::array_size($_FILES) <= 0) {
			$out['status'] = 'WARN';
			$out['message'] = 'No files uploads detected ...';
			$out['msg-code'] = 1;
			return (array) $out;
		} //end if
		if((!isset($_FILES[$var_name])) OR (!is_array($_FILES[$var_name]))) {
			$out['status'] = 'WARN';
			$out['message'] = 'No files uploads detected for `'.$var_name.'` ...';
			$out['msg-code'] = 1;
			return (array) $out;
		} //end if
		//--
		$the_upld_file_name 	= '';
		$the_upld_file_tmpname 	= '';
		$the_upld_file_error 	= -777;
		//--
		if($var_index >= 0) {
			if( // {{{SYNC-CHECK-MULTI-UPLOAD-FILES-ARR}}}
				(!isset($_FILES[$var_name]['name']))     OR (!is_array($_FILES[$var_name]['name']))     OR (!isset($_FILES[$var_name]['name'][$var_index])) OR
				(!isset($_FILES[$var_name]['tmp_name'])) OR (!is_array($_FILES[$var_name]['tmp_name'])) OR (!isset($_FILES[$var_name]['tmp_name'][$var_index])) OR
				(!isset($_FILES[$var_name]['error']))    OR (!is_array($_FILES[$var_name]['error']))    OR (!isset($_FILES[$var_name]['error'][$var_index]))
			) {
				$out['status'] = 'WARN';
				$out['message'] = 'No files uploads detected ...';
				$out['msg-code'] = 1;
				return (array) $out;
			} //end if
			$the_upld_file_name 	= (string) $_FILES[$var_name]['name'][$var_index];
			$the_upld_file_tmpname 	= (string) $_FILES[$var_name]['tmp_name'][$var_index];
			$the_upld_file_error 	= (int)    $_FILES[$var_name]['error'][$var_index];
		} else {
			$the_upld_file_name 	= (string) $_FILES[$var_name]['name'];
			$the_upld_file_tmpname 	= (string) $_FILES[$var_name]['tmp_name'];
			$the_upld_file_error 	= (int)    $_FILES[$var_name]['error'];
		} //end if else
		//-- check uploaded tmp name
		$the_upld_file_tmpname = (string) trim((string)$the_upld_file_tmpname);
		if((string)$the_upld_file_tmpname == '') {
			$out['status'] = 'WARN';
			$out['message'] = 'No File Uploaded (Empty TMP Name) ...';
			$out['msg-code'] = 3;
			return (array) $out;
		} //end if
		//-- fix file name
		$the_upld_file_name = (string) SmartUnicode::deaccent_str($the_upld_file_name);
		$the_upld_file_name = (string) str_replace('#', '-', $the_upld_file_name); // {{{SYNC-WEBDAV-#-ISSUE}}}
		$the_upld_file_name = (string) Smart::safe_filename($the_upld_file_name, '-'); // {{{SYNC-SAFE-FNAME-REPLACEMENT}}}
		//-- remove versioning if any
		$the_upld_file_name = (string) SmartFileSysUtils::fnameVersionClear((string)$the_upld_file_name);
		//-- remove dangerous characters
		$the_upld_file_name = (string) trim((string)str_replace(['\\', ' ', '?'], ['-', '-', '-'], (string)$the_upld_file_name));
		$the_upld_file_name = (string) trim((string)$the_upld_file_name);
		//-- hard limit for file name length for max 100 characters
		if((string)$the_upld_file_name == '') {
			$out['status'] = 'WARN';
			$out['message'] = 'Uploaded File Name is Invalid (Empty)';
			$out['msg-code'] = 4;
			return (array) $out;
		} //end if
		if(strlen((string)$the_upld_file_name) > 100) {
			$out['status'] = 'WARN';
			$out['message'] = 'Uploaded File Name is too long (oversize 100 characters): '.$the_upld_file_name;
			$out['msg-code'] = 5;
			return (array) $out;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_upld_file_name)) {
			$out['status'] = 'WARN';
			$out['message'] = 'Uploaded File Name is Invalid (not Safe): '.$the_upld_file_name;
			$out['msg-code'] = 6;
			return (array) $out;
		} //end if
		//-- protect against dot files .*
		if(substr((string)$the_upld_file_name, 0, 1) == '.') {
			$out['status'] = 'WARN';
			$out['message'] = 'Uploaded File Name is Invalid (Dot .Files are not allowed for safety): '.$the_upld_file_name;
			$out['msg-code'] = 7;
			return (array) $out;
		} //end if
		//--
		$tmp_fext = (string) strtolower((string)SmartFileSysUtils::extractPathFileExtension((string)$the_upld_file_name)); // get the extension
		//-- {{{SYNC-CHK-ALLOWED-DENIED-EXT}}}
		if((string)$allowed_extensions != '') {
			if(stripos((string)$allowed_extensions, '<'.$tmp_fext.'>') === false) {
				$out['status'] = 'WARN';
				$out['message'] = 'Upload Failed: The uploaded file extension is not in the current custom allowed extensions list for file: '.$the_upld_file_name;
				$out['msg-code'] = 8;
				return (array) $out;
			} //end if
		} else {
			if(defined('SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS') AND ((string)SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS != '')) {
				if(stripos((string)SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') === false) {
					$out['status'] = 'WARN';
					$out['message'] = 'Upload Failed: The uploaded file extension is not in the current allowed extensions list configuration for file: '.$the_upld_file_name;
					$out['msg-code'] = 9;
					return (array) $out;
				} //end if
			} //end if
			if((!defined('SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS')) OR (stripos((string)SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') !== false)) {
				$out['status'] = 'WARN';
				$out['message'] = 'Upload Failed: The uploaded file extension is denied by the current configuration for file: '.$the_upld_file_name;
				$out['msg-code'] = 10;
				return (array) $out;
			} //end if
		} //end if else
		//-- check for upload errors
		$up_err = '';
		$up_code = 0;
		switch((int)$the_upld_file_error) {
			case UPLOAD_ERR_OK:
				// OK, no error
				break;
			case UPLOAD_ERR_INI_SIZE:
				$up_code = 101;
				$up_err = 'UPLOAD ERROR: The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$up_code = 102;
				$up_err = 'UPLOAD ERROR: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case UPLOAD_ERR_PARTIAL:
				$up_code = 103;
				$up_err = 'UPLOAD ERROR: The uploaded file was only partially uploaded';
				break;
			case UPLOAD_ERR_NO_FILE:
				$up_code = 104;
				$up_err = 'UPLOAD ERROR: No file was uploaded';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$up_code = 105;
				$up_err = 'UPLOAD ERROR: Missing a temporary folder';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$up_code = 106;
				$up_err = 'UPLOAD ERROR: Failed to write file to disk';
				break;
			case UPLOAD_ERR_EXTENSION:
				$up_code = 107;
				$up_err = 'UPLOAD ERROR: File upload stopped by extension';
				break;
			default:
				$up_code = 108;
				$up_err =  'UPLOAD ERROR: Unknown error ...';
		} //end switch
		if((string)$up_err != '') {
			$out['status'] = 'ERR';
			$out['message'] = (string) $up_err.' for file: '.$the_upld_file_name;
			$out['msg-code'] = (int) $up_code;
			return (array) $out;
		} //end if
		//-- do upload
		if(!is_uploaded_file((string)$the_upld_file_tmpname)) {
			$out['status'] = 'ERR';
			$out['message'] = 'UPLOAD ERROR: Cannot find the uploaded data for file: '.$the_upld_file_name.' at: '.$the_upld_file_tmpname;
			$out['msg-code'] = 11;
			return (array) $out;
		} //end if
		$fsize_upld = (int) SmartFileSystem::get_file_size($the_upld_file_tmpname);
		if((int)$fsize_upld <= 0) { // dissalow upload empty files, does not make sense or there was an error !!!
			$out['status'] = 'WARN';
			$out['message'] = 'Upload Failed: File is empty: '.$the_upld_file_name;
			$out['msg-code'] = 12;
			return (array) $out;
		} elseif((int)$fsize_upld > (int)$max_size) {
			$out['status'] = 'WARN';
			$out['message'] = 'Upload Failed: File is oversized: '.$the_upld_file_name;
			$out['msg-code'] = 13;
			return (array) $out;
		} //end if
		//--
		$out['status'] = 'OK';
		$out['message'] = '';
		$out['msg-code'] = 0;
		$out['filename'] = (string) $the_upld_file_name;
		$out['filetype'] = (string) $tmp_fext;
		$out['filecontent'] = (string) SmartFileSystem::read_uploaded($the_upld_file_tmpname);
		$out['filesize'] = (int) strlen($out['filecontent']);
		return (array) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Store one Uploaded File to a destination directory
	 *
	 * @param STRING 	$dest_dir 					:: The destination directory ; Example: 'wpub/my-test/'
	 * @param STRING 	$var_name					:: The HTML Variable Name
	 * @param INTEGER 	$var_index					:: The HTML Variable Index: -1 for single file uploads ; 0..n for multi-file uploads
	 * @param BOOLEAN 	$allow_rewrite 				:: Allow rewrite if already exists that file in the destination directory ; default is TRUE
	 * @param INTEGER 	$max_size					:: The max file size in bytes that would be accepted ; set to zero for allow maximum size supported by PHP via INI settings
	 * @param STRING	$allowed_extensions			:: The list of allowed file extensions ; Default is '' ; Example to restrict to several extensions: '<ext1>,<ext2>,...<ext100>,...' ; set to empty string to allow all extenstions supported via Smart.Framework INI: SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS / SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS
	 * @param STRING 	$new_name 					:: Use a new file name for the uploaded file instead of the original one ; Set to empty string to preserve the uploaded file name ; DEFAULT is ''
	 * @param BOOLEAN 	$enforce_lowercase 			:: Set to TRUE to enforce lowercase file name ; DEFAULT is FALSE
	 * @return MIXED								:: '' (empty string) if all OK ; FALSE (boolean) if upload failed ; otherwise will return a non-empty string with the ERROR / WARNING message if the file was not successfuly stored in the destination directory
	 */
	public static function store_uploaded_file(?string $dest_dir, ?string $var_name, ?int $var_index=-1, bool $allow_rewrite=true, ?int $max_size=0, ?string $allowed_extensions='', ?string $new_name='', bool $enforce_lowercase=false) { // : MIXED return !!
		//-- {{{SYNC-HANDLE-F-UPLOADS}}} v.20220509
		$dest_dir = (string) $dest_dir;
		$var_name = (string) trim((string)$var_name);
		$var_index = (int) $var_index;
		if((string)$allow_rewrite === 'versioning') {
			$allow_rewrite = (string) $allow_rewrite;
		} else {
			$allow_rewrite = (bool) $allow_rewrite;
		} //end if else
		$max_size = (int) Smart::format_number_int($max_size,'+');
		if($max_size <= 0) {
			$max_size = (int) SmartFileSysUtils::maxUploadFileSize();
		} //end if
		$allowed_extensions = (string) trim((string)$allowed_extensions);
		$new_name = (string) $new_name; // an optional override file name (NO extension !!! The extension will be preserved from the uploaded file)
		//--
		if((string)$var_name == '') {
			return 'Invalid File VarName for Upload';
		} //end if
		//--
		if(Smart::array_size($_FILES) <= 0) {
			return false; // {{{SYNC-FILE-UPLD-FALSE-RET}}} no files uploads detected ; should return no error ...
		} //end if
		if((!isset($_FILES[$var_name])) OR (!is_array($_FILES[$var_name]))) {
			return false; // {{{SYNC-FILE-UPLD-FALSE-RET}}} no files uploads detected ; should return no error ...
		} //end if
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$dest_dir) != '1') {
			return 'Invalid Destination Dir: Unsafe DirName';
		} //end if
		$dest_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dest_dir);
		if(SmartFileSysUtils::checkIfSafePath((string)$dest_dir) != '1') {
			return 'Invalid Destination Dir: Unsafe Path';
		} //end if
		if(SmartFileSystem::is_type_dir((string)$dest_dir) !== true) {
			return 'Invalid Destination Dir: Path must exist and it must be a directory';
		} //end if
		//--
		if(is_array($_FILES[$var_name]['tmp_name'])) { // if detected multi file uploads sent by client, index must be >= 0 to explicit allow this
			if($var_index >= 0) {
				// OK
			} else {
				return false;
			} //end if
		} else { // client sent a single file upload (if PHP code expects multi, need to fix the var index as 0 = -1 to handle this
			if($var_index === 0) {
				$var_index = -1; // fix: support the upload of single file when support many
			} elseif($var_index > 0) {
				return false; // {{{SYNC-FILE-UPLD-FALSE-RET}}} should return no error because the file may not be uploaded
			} //end if
		} //end if
		//--
		$the_upld_file_name 	= '';
		$the_upld_file_tmpname 	= '';
		$the_upld_file_error 	= -777;
		//--
		if($var_index >= 0) {
			if( // {{{SYNC-CHECK-MULTI-UPLOAD-FILES-ARR}}}
				(!isset($_FILES[$var_name]['name']))     OR (!is_array($_FILES[$var_name]['name']))     OR (!isset($_FILES[$var_name]['name'][$var_index])) OR
				(!isset($_FILES[$var_name]['tmp_name'])) OR (!is_array($_FILES[$var_name]['tmp_name'])) OR (!isset($_FILES[$var_name]['tmp_name'][$var_index])) OR
				(!isset($_FILES[$var_name]['error']))    OR (!is_array($_FILES[$var_name]['error']))    OR (!isset($_FILES[$var_name]['error'][$var_index]))
			) {
				return false; // {{{SYNC-FILE-UPLD-FALSE-RET}}} should return no error because the file may not be uploaded
			} //end if
			$the_upld_file_name 	= (string) $_FILES[$var_name]['name'][$var_index];
			$the_upld_file_tmpname 	= (string) $_FILES[$var_name]['tmp_name'][$var_index];
			$the_upld_file_error 	= (int)    $_FILES[$var_name]['error'][$var_index];
		} else {
			$the_upld_file_name 	= (string) $_FILES[$var_name]['name'];
			$the_upld_file_tmpname 	= (string) $_FILES[$var_name]['tmp_name'];
			$the_upld_file_error 	= (int)    $_FILES[$var_name]['error'];
		} //end if else
		//-- check uploaded tmp name
		$the_upld_file_tmpname = (string) trim((string)$the_upld_file_tmpname);
		if((string)$the_upld_file_tmpname == '') {
			return false; // {{{SYNC-FILE-UPLD-FALSE-RET}}} should return no error because the file may not be uploaded
		} //end if
		//-- fix file name
		$the_upld_file_name = (string) SmartUnicode::deaccent_str($the_upld_file_name);
		$the_upld_file_name = (string) str_replace('#', '-', $the_upld_file_name); // {{{SYNC-WEBDAV-#-ISSUE}}}
		$the_upld_file_name = (string) Smart::safe_filename($the_upld_file_name, '-'); // {{{SYNC-SAFE-FNAME-REPLACEMENT}}}
		//-- remove versioning if any
		$the_upld_file_name = (string) SmartFileSysUtils::fnameVersionClear((string)$the_upld_file_name);
		//-- remove dangerous characters
		$the_upld_file_name = (string) trim((string)str_replace(['\\', ' ', '?'], ['-', '-', '-'], (string)$the_upld_file_name));
		$the_upld_file_name = (string) trim((string)$the_upld_file_name);
		//-- hard limit for file name length for max 100 characters
		if((string)$the_upld_file_name == '') {
			return 'Uploaded File Name is Invalid (Empty)';
		} //end if
		if(strlen((string)$the_upld_file_name) > 100) {
			return 'Uploaded File Name is too long (oversize 100 characters): '.$the_upld_file_name;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_upld_file_name)) {
			return 'Uploaded File Name is Invalid (not Safe): '.$the_upld_file_name;
		} //end if
		//-- protect against dot files .*
		if(substr((string)$the_upld_file_name, 0, 1) == '.') {
			return 'Uploaded File Name is Invalid (Dot .Files are not allowed for safety): '.$the_upld_file_name;
		} //end if
		//--
		$tmp_fext = (string) strtolower((string)SmartFileSysUtils::extractPathFileExtension((string)$the_upld_file_name)); // get the extension
		if((string)$new_name != '') {
			if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$new_name)) {
				return 'Uploaded File New Name: `'.$new_name.'` is Invalid for file name: '.$the_upld_file_name;
			} //end if
			if(substr((string)$new_name, 0, 1) == '.') {
				return 'Uploaded File New Name: `'.$new_name.'` is Invalid (Dot .Files are not allowed for safety): '.$the_upld_file_name;
			} //end if
			$the_upld_file_name = (string) SmartFileSysUtils::fnameVersionClear((string)trim((string)$new_name)); // since the new name is provided programatically we do not check if > 100 chars ...
			if($var_index >= 0) {
				$the_upld_file_name .= ''.(int)$var_index;
			} //end if
			$the_upld_file_name .= '.'.$tmp_fext;
			if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_upld_file_name)) {
				return 'Uploaded New File Name `'.$the_upld_file_name.'` is Invalid (not Safe): '.$the_upld_file_name;
			} //end if
		} //end if
		if($enforce_lowercase === true) {
			$the_upld_file_name = (string) strtolower((string)$the_upld_file_name);
		} //end if
		//-- {{{SYNC-CHK-ALLOWED-DENIED-EXT}}}
		if((string)$allowed_extensions != '') {
			if(stripos((string)$allowed_extensions, '<'.$tmp_fext.'>') === false) {
				return 'Upload Failed: The uploaded file extension is not in the current custom allowed extensions list for file: '.$the_upld_file_name;
			} //end if
		} else {
			if(defined('SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS') AND ((string)SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS != '')) {
				if(stripos((string)SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') === false) {
					return 'Upload Failed: The uploaded file extension is not in the current allowed extensions list configuration for file: '.$the_upld_file_name;
				} //end if
			} //end if
			if((!defined('SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS')) OR (stripos((string)SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') !== false)) {
				return 'Upload Failed: The uploaded file extension is denied by the current configuration for file: '.$the_upld_file_name;
			} //end if
		} //end if else
		//-- check for upload errors
		$up_err = '';
		switch((int)$the_upld_file_error) {
			case UPLOAD_ERR_OK:
				// OK, no error
				break;
			case UPLOAD_ERR_INI_SIZE:
				$up_err = 'UPLOAD ERROR: The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$up_err = 'UPLOAD ERROR: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case UPLOAD_ERR_PARTIAL:
				$up_err = 'UPLOAD ERROR: The uploaded file was only partially uploaded';
				break;
			case UPLOAD_ERR_NO_FILE:
				$up_err = 'UPLOAD ERROR: No file was uploaded';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$up_err = 'UPLOAD ERROR: Missing a temporary folder';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$up_err = 'UPLOAD ERROR: Failed to write file to disk';
				break;
			case UPLOAD_ERR_EXTENSION:
				$up_err = 'UPLOAD ERROR: File upload stopped by extension';
				break;
			default:
				$up_err =  'UPLOAD ERROR: Unknown error ...';
		} //end switch
		if((string)$up_err != '') {
			return (string) $up_err.' for file: '.$the_upld_file_name;
		} //end if
		//-- bug-fix: check if uploaded file before re-versioning
		if(!is_uploaded_file((string)$the_upld_file_tmpname)) {
			return 'UPLOAD ERROR: Cannot find the uploaded data for file: '.$the_upld_file_name.' at: '.$the_upld_file_tmpname;
		} //end if
		//-- if there is an existing file already with the same name
		if(SmartFileSystem::is_type_file($dest_dir.$the_upld_file_name)) {
			if((string)$allow_rewrite === 'versioning') {
				if(!SmartFileSystem::rename($dest_dir.$the_upld_file_name, $dest_dir.SmartFileSysUtils::fnameVersionAdd((string)$the_upld_file_name))) { // use default versioning: stdmtime
					return 'Upload Failed: Destination File Versioning Failed for file: '.$the_upld_file_name;
				} //end if
			} elseif($allow_rewrite === false) {
				return 'Upload Failed: Destination File Exists and Allow Rewrite is turned off for file: '.$the_upld_file_name;
			} else { // true
				if(!SmartFileSystem::delete($dest_dir.$the_upld_file_name)) { // try to remove the destination file (will be replaced with new uploaded version)
					return 'Upload Failed: Destination File Exists and could not be removed for file: '.$the_upld_file_name;
				} //end if
			} //end if else
		} //end if
		//-- do upload
		$fsize_upld = (int) SmartFileSystem::get_file_size($the_upld_file_tmpname);
		if((int)$fsize_upld <= 0) { // dissalow upload empty files, does not make sense or there was an error !!!
			return 'Upload Failed: File is empty: '.$the_upld_file_name;
		} elseif((int)$fsize_upld > (int)$max_size) {
			return 'Upload Failed: File is oversized: '.$the_upld_file_name;
		} //end if
		if(!SmartFileSystem::move_uploaded($the_upld_file_tmpname, $dest_dir.$the_upld_file_name, true)) { // also check sha1-file
			return 'Failed to Move the Uploaded File: '.$the_upld_file_name.' to the Destination Directory';
		} //end if
		//--
		return ''; // OK
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function client_ident_private_key() : string {
		//--
		return (string) self::get_visitor_signature().' [#] '.SMART_SOFTWARE_NAMESPACE.'*'.SMART_FRAMEWORK_SECURITY_KEY.'.';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// generate a PRIVATE unique, very secure hash of the current user by IP and Browser Signature
	// This key should never be exposed to the public, it is used to check signed data (which may be paired with visitor unique track id)
	public static function unique_client_private_key() : string {
		//--
		return (string) SmartHashCrypto::sh3a512('*'.self::client_ident_private_key(), true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// generate a PRIVATE unique, very secure hash of the current user by loginID, IP and Browser Signature
	// This key should never be exposed to the public, it is used to check signed data (which may be paired with visitor unique track id)
	public static function unique_auth_client_private_key() : string {
		//--
		return (string) SmartHashCrypto::sh3a512('*'.SmartAuth::get_auth_id().'@'.SmartAuth::get_auth_area().'::'.SmartAuth::get_auth_realm().'|'.self::client_ident_private_key(), true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// this provide a stable but unique, non variable signature for the self browser robot
	// this should be used just for identification purposes
	// this should be never be trusted, the signature is public
	// it must contain also the Robot keyword as it fails to identify as self-browser, at least to be identified as robot
	// this signature should be used just for the internal browsing operations
	public static function get_selfrobot_useragent_name() : string {
		//--
		return (string) 'Smart.Framework :: PHP/Robot :: SelfBrowser ('.php_uname().') @ '.SmartHashCrypto::sha224('SelfBrowser/PHP/'.php_uname().'/'.SMART_SOFTWARE_NAMESPACE.'/'.SMART_FRAMEWORK_SECURITY_KEY);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function get_visitor_useragent() : string {
		//--
		return (string) SmartFrameworkRegistry::getServerVar((string)'HTTP_USER_AGENT');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function get_visitor_signature() : string {
		//--
		return (string) 'Visitor // '.trim((string)self::get_ip_client()).' :: '.trim((string)self::get_visitor_useragent()); // fix: do not use self::get_ip_proxyclient() here ... if using DNS load balancing + multiple load balancers with multiple backends switching the load balancer (aka reverse proxy) when browsing and changing between web pages will change this signature which will change the client_ident_private_key() and then may lead to user session expired ...
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// This is the visitor UID as SHA512/B62 (86 bytes) calculated using the visitor private key, visitor public key (cookie) and the internal secret
	// This can be public exposed and used for tracking purposes other than SMART_APP_VISITOR_COOKIE as it is a derivation of it, where another UID related with SMART_APP_VISITOR_COOKIE is needed ; can be really trusted, but the SMART_APP_VISITOR_COOKIE does not
	public static function get_visitor_tracking_uid() : string {
		//--
		$uuid = '#';
		if(defined('SMART_APP_VISITOR_COOKIE') AND ((string)trim((string)SMART_APP_VISITOR_COOKIE) != '')) { // {{{SYNC-SMART-UNIQUE-VAL-COOKIE}}}
			$uuid = (string) SMART_APP_VISITOR_COOKIE;
		} //end if
		//--
		return (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sh3a512('>'.SMART_SOFTWARE_NAMESPACE.'['.self::client_ident_private_key().']'.$uuid.'>'.SMART_FRAMEWORK_SECURITY_KEY), 62);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function get_encoding_charset() : string {
		//--
		return (string) SMART_FRAMEWORK_CHARSET;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// ex: XMLHttpRequest
	public static function get_server_current_request_with() : string {
		//--
		return (string) SmartFrameworkRegistry::getServerVar('HTTP_X_REQUESTED_WITH');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// if lowercase requested with is xmlhttprequest returns TRUE
	public static function is_ajax_request() : bool {
		//--
		return (bool) ((string)strtolower((string)self::get_server_current_request_with()) == 'xmlhttprequest');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// ex: GET / POST / HEAD ... ; must not return an empty value ; if not detected, fallback to default GET
	public static function get_server_current_request_method() : string {
		//--
		$method = (string) SmartFrameworkRegistry::getServerVar('REQUEST_METHOD');
		//--
		if((string)$method == '') {
			$method = 'GET';
		} //end if
		//--
		return (string) strtoupper((string)$method); // string
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: http:// or https:// ; must not return an empty protocol if not set but fallback to default: http://
	public static function get_server_current_protocol() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$current_protocol = '';
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true)) {
			//--
			$err = false;
			//--
			$skey = '';
			$hkey = '';
			//--
			if(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO')) {
				$hkey = (string) strtoupper((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO));
				if((string)$hkey != '') {
					if((string)strtolower((string)$hkey) == 'http') { // explicit set to HTTP
						$current_protocol = 'http://';
					} elseif((string)strtolower((string)$hkey) == 'https') { // explicit set to HTTPS
						$current_protocol = 'https://';
					} elseif(preg_match('/^[_A-Z]+$/', (string)$hkey)) { // must to be a valid header key from the allowed list
						$allowed_list = (array) self::VALID_HEADERS_SERVER_PROTO;
						if(
							in_array((string)$hkey, (array)array_keys((array)$allowed_list))
							AND
							isset($allowed_list[(string)$hkey])
							AND
							is_array($allowed_list[(string)$hkey])
						) {
							if(SmartFrameworkRegistry::issetServerVar((string)$hkey)) {
								$skey = (string) strtolower((string)SmartFrameworkRegistry::getServerVar((string)$hkey));
								$skey = (string) self::_head_value_get_last_val((string)$skey); // may contain multiple as list, separed by comma, the last one is trusted as added by the reverse proxy
								if(in_array((string)$skey, (array)array_keys((array)$allowed_list[(string)$hkey]))) {
									if(isset($allowed_list[(string)$hkey][(string)$skey])) {
										$sval = (string) $allowed_list[(string)$hkey][(string)$skey];
										if((string)$sval != '') {
											if(in_array((string)$sval, [ 'http', 'https' ])) {
												$current_protocol = (string) $sval.'://';
											} else {
												$err = true;
											} //end if
										} else {
											$err = true;
										} //end if
									} else {
										$err = true;
									} //end if else
								} else {
									$err = true;
								} //end if else
							} else {
								$err = true;
							} //end if else
						} else {
							$err = true;
						} //end if else
					} else {
						$err = true;
					} //end if else
				} else {
					// if set to empty string: skip, not an error, it is set so to be detected below
				} //end if else
			} else {
				$err = true;
			} //end if
			//--
			if($err) {
				Smart::log_warning(__METHOD__.' # ERR: Invalid definition or value for SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO: `'.$hkey.'`=`'.$skey.'`');
				$current_protocol = 'http://'; // fallback to default
			} //end if
			//--
		} //end if
		//--
		if((string)$current_protocol == '') { // if not get custom above, detect
			if((string)strtolower((string)SmartFrameworkRegistry::getServerVar('HTTPS')) == 'on') {
				$current_protocol = 'https://';
			} else {
				$current_protocol = 'http://';
			} //end if else
		} //end if
		//--
		if((string)$current_protocol == '') {
			$current_protocol = 'http://'; // fallback to default
			Smart::log_warning(__METHOD__.' # ERR: Failed to determine the server current protocol: `http://` or `https://` ...');
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $current_protocol;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: 80 or 443 or ... ; must not return an empty port if not set but fallback to default: 80
	public static function get_server_current_port() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$current_port = '';
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true)) {
			//--
			$err = false;
			//--
			$skey = '';
			$hkey = '';
			//--
			if(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_PORT')) {
				$hkey = (string) strtoupper((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_PORT));
				if((string)$hkey != '') {
					if((preg_match('/^[0-9]+$/', (string)$hkey)) AND (((int)$hkey >= 1) AND ((int)$hkey <= 65535))) { // explicit set to a valid TCP port
						$current_port = (int) $hkey;
					} elseif(preg_match('/^[_A-Z]+$/', (string)$hkey)) { // must to be a valid header key from the allowed list
						$allowed_list = (array) self::VALID_HEADERS_SERVER_PORT;
						if(in_array((string)$hkey, (array)$allowed_list)) {
							if(SmartFrameworkRegistry::issetServerVar((string)$hkey)) {
								$skey = (string) (int) SmartFrameworkRegistry::getServerVar((string)$hkey);
								$skey = (string) self::_head_value_get_last_val((string)$skey); // may contain multiple as list, separed by comma, the last one is trusted as added by the reverse proxy
								if(((int)$skey >= 1) AND ((int)$skey <= 65535)) {
									$current_port = (string) (int) $skey;
								} else {
									$err = true;
								} //end if else
							} else {
								$err = true;
							} //end if else
						} else {
							$err = true;
						} //end if else
					} else {
						$err = true;
					} //end if else
				} else {
					// if set to empty string: skip, not an error, it is set so to be detected below
				} //end if else
			} else {
				$err = true;
			} //end if
			//--
			if($err) {
				Smart::log_warning(__METHOD__.' # ERR: Invalid definition or value for SMART_FRAMEWORK_SRVPROXY_SERVER_PORT: `'.$hkey.'`=`'.$skey.'`');
				$current_port = '80'; // fallback to default
			} //end if
			//--
		} //end if
		//--
		if((string)$current_port == '') { // if not get custom above, detect
			$port = null;
			if(SmartFrameworkRegistry::issetServerVar('SERVER_PORT')) {
				$port = (string) (int)  SmartFrameworkRegistry::getServerVar('SERVER_PORT');
			} //end if
			if(((int)$port >= 1) AND ((int)$port <= 65535)) {
				$current_port = (string) (int) $port;
			} //end if
		} //end if
		//--
		if((string)$current_port == '') {
			$current_port = '80'; // fallback to default
			Smart::log_warning(__METHOD__.' # ERR: Failed to determine the server current port: ex: `80` or `443` ...');
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $current_port;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: 127.0.0.1 ; must not return an empty value ; in case of failure must log and return a fake, inexistent IP
	// IMPORTANT: if proxy mode enabled this can be override by setting: SMART_FRAMEWORK_SRVPROXY_SERVER_IP
	// for IPv6 will return the compressed format
	public static function get_server_current_ip() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$ip = '';
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true)) { // if behind a proxy the ip can be other
			if(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_IP') AND ((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP) != '')) {
				if(preg_match('/^[_A-Z]+$/', (string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP) AND in_array((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP, (array)self::VALID_HEADERS_SERVER_IP)) {
					$ip = (string)  SmartFrameworkRegistry::getServerVar((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP));
				} elseif((string)trim((string)SmartValidator::validate_filter_ip_address((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP))) != '') {
					$ip = (string)  SmartFrameworkRegistry::getServerVar((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP));
				} elseif((string)SMART_FRAMEWORK_SRVPROXY_SERVER_IP != '') {
					Smart::log_warning(__METHOD__.' # ERR: Invalid definition or value for SMART_FRAMEWORK_SRVPROXY_SERVER_IP: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_IP.'`');
				} //end if
			} //end if
		} //end if
		//--
		if((string)$ip == '') {
			if((string)SmartFrameworkRegistry::getServerVar('SERVER_ADDR') != '') {
				$ip = (string) SmartFrameworkRegistry::getServerVar('SERVER_ADDR');
			} else { // this is a fix for the PHP built-in web server which does not provide the SERVER_ADDR but only the SERVER_NAME ; if the SERVER_NAME is an IP address use that, otherwise cannot detect it !
				$ip = (string) trim((string)SmartValidator::validate_filter_ip_address((string)trim((string)SmartFrameworkRegistry::getServerVar('SERVER_NAME'))));
				if((string)$ip == '') {
					$ip = (string) self::FAKE_IP_SERVER; // return a fake IP, that does not exists {{{SYNC-SRV-DETECTION-FAKE-IP}}}
					Smart::log_warning(__METHOD__.' # ERR: Failed to get current server IP address. Fallback to: `'.$ip.'`');
				} //end if
			} //end if
		} //end if
		//--
		$ip = (string) Smart::ip_addr_compress((string)$ip); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
		if((string)$ip == '') {
			Smart::raise_error(__METHOD__.' # IP compression Failed');
			return '';
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $ip;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// get the current domain or IP (Ex: localhost or mydom.ext or IP address) ; must not return an empty value ; if no domain detected return the server's IP
	// IMPORTANT: if proxy mode enabled this can be override by setting: SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN
	public static function get_server_current_domain_name() : string {
		//--
		// for IPv6 will return ex: [2001:0:6dcd:8c74::] ; {{{SYNC-SMART-SERVER-DOMAIN-IPV6-BRACKETS}}} ; {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$dm = '';
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true)) { // if behind a proxy the domain can be other
			if(defined('SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN') AND ((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN) != '')) {
				if(preg_match('/^[_A-Z]+$/', (string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN) AND in_array((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN, (array)self::VALID_HEADERS_SERVER_DOMAIN)) {
					$dm = (string)  SmartFrameworkRegistry::getServerVar((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN));
				} elseif(preg_match('/^[a-z0-9\-\.]+$/', (string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN)) {
					$dm = (string)  SmartFrameworkRegistry::getServerVar((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN));
				} elseif((string)trim((string)SmartValidator::validate_filter_ip_address((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN))) != '') { // domain can be also an IP
					$dm = (string)  SmartFrameworkRegistry::getServerVar((string)trim((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN));
				} elseif((string)SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN != '') {
					Smart::log_warning(__METHOD__.' # ERR: Invalid definition or value for SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN.'`');
				} //end if
			} //end if
		} //end if
		//--
		if((string)$dm == '') {
			if((string)SmartFrameworkRegistry::getServerVar('SERVER_NAME') != '') {
				$dm = (string) SmartFrameworkRegistry::getServerVar('SERVER_NAME');
			} else {
				$dm = (string) self::get_server_current_ip(); // fallback to IP
				if((string)trim((string)$dm) == '') { // if still empty, log
					$dm = (string) self::FAKE_IP_SERVER; // return a fake IP, as a domain name, that does not exists {{{SYNC-SRV-DETECTION-FAKE-IP}}}
					Smart::log_warning(__METHOD__.' # ERR: Failed to get current server Domain Name. Fallback to: `'.$dm.'`');
				} //end if
			} //end if else
		} //end if
		//--
		if(strpos((string)$dm, ':') !== false) { // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-BRACKETS}}} ; {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
			//-- IPv6 addresses contain colons, they cannot be directly used in URLs because the colons would conflict with both the protocol declaration (http:// or https://) and port numbers
			$dm = (string) Smart::ip_addr_compress((string)$dm); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
			if((string)$dm == '') {
				Smart::raise_error(__METHOD__.' # IP compression Failed');
				return '';
			} //end if
			$dm = (string) '['.$dm.']'; // when a literal IPv6 address is used, it is encased in a bracket
			//--
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) strtolower((string)$dm);
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// get base domain without sub-domain (Ex: mydom.ext or IP address)
	// IMPORTANT: if proxy mode enabled this can be override by setting: SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN
	public static function get_server_current_basedomain_name() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$domain = (string) Smart::ip_domain_to_ip_addr((string)self::get_server_current_domain_name()); // remove [] from IPv6 as domain
		//--
		$xout = (string) SmartParser::extract_base_domain_from_domain((string)$domain);
		//--
		if(strpos((string)$xout, ':') !== false) { // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-BRACKETS}}} {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
			//-- IPv6 addresses contain colons, they cannot be directly used in URLs because the colons would conflict with both the protocol declaration (http:// or https://) and port numbers
			$xout = (string) Smart::ip_addr_compress((string)$xout); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
			if((string)$xout == '') {
				Smart::raise_error(__METHOD__.' # IP compression Failed');
				return '';
			} //end if
			$xout = (string) '['.$xout.']'; // when a literal IPv6 address is used, it is encased in a bracket
			//--
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $xout;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// get sub-domain without base domain (Ex: www.) ; works with subdom.domain.ext or sub.dom.domain.ext ; If IP address or no-extension domain, will return empty string
	// IMPORTANT: if proxy mode enabled this can be override by setting: SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN
	public static function get_server_current_subdomain_name() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$sdom = '';
		//--
		$the_dom_crr = (string) SmartUtils::get_server_current_domain_name();
		if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$the_dom_crr)) == '') { // if not IP address
			//--
			$the_dom_base = (string) SmartUtils::get_server_current_basedomain_name();
			if((strpos((string)$the_dom_base, '.') !== false) AND ((string)$the_dom_base != (string)$the_dom_crr)) { // for sub-domain the base domain must contain a dot
				$sdom = (string) trim((string)substr((string)$the_dom_crr, 0, (int)((int)strlen((string)$the_dom_crr) - (int)strlen((string)$the_dom_base) - 1)));
			} //end if
			//--
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $sdom;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: /sites/test/script.php/page.html|path/to/something-else ; the path is decoded ; can be empty
	public static function get_server_current_request_path() : string {
		//--
		return (string) SmartFrameworkRegistry::getServerVar('PATH_INFO');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: /sites/test/script.php?param= | /page.html (rewrited to some-script.php?var=val&ofs=...) ; it includes the current path. but RAW (not decoded)
	public static function get_server_current_request_uri() : string {
		//--
		return (string) SmartFrameworkRegistry::getServerVar('REQUEST_URI');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: Empty or http(s)://some-url
	public static function get_server_current_request_referer() : string {
		//--
		return (string) SmartFrameworkRegistry::getServerVar('HTTP_REFERER');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: /sites/test/script.php
	public static function get_server_current_full_script() : string {
		//--
		return (string) Smart::fix_path_separator((string)SmartFrameworkRegistry::getServerVar('SCRIPT_NAME'), true); // Fix: if PHP is running on a Windows server it can contain \ instead of / so FORCE it
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: script.php ; can return empty on failure
	public static function get_server_current_script() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$current_script = '';
		if((string)self::get_server_current_full_script() != '') {
			$current_script = (string) basename((string)self::get_server_current_full_script());
		} //end if
		if((string)$current_script == '') {
			Smart::log_warning(__METHOD__.' # Cannot Determine Current Server Script'); // do not return here, must be cached
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $current_script;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: ?param1=one&param2=two ; if empty can return just ? or empty string if 2nd param is set to true
	public static function get_server_current_queryurl(bool $use_blank_if_empty=false) : string {
		//--
		$url_query = (string) SmartFrameworkRegistry::getServerVar('QUERY_STRING'); // will get without the prefix '?' as: page=one&subpage=two
		//--
		if((string)$url_query == '') {
			if($use_blank_if_empty !== true) {
				$url_query = '?'; // at least '?' is expected even if the url query is empty
			} //end if
		} elseif((string)substr((string)$url_query, 0, 1) != '?') { // add '?' prefix if missing, this is required for building url with suffixes, all current url builders rely on assuming there will be a '?' as prefix
			$url_query = '?'.$url_query;
		} //end if else
		//--
		return (string) $url_query;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: /sites/test/
	public static function get_server_current_path() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$current_path = '/'; // this is default
		//--
		if((string)self::get_server_current_full_script() != '') {
			$current_path = (string) Smart::dir_name(self::get_server_current_full_script()); // may return '' or .
			if(((string)$current_path == '') OR ((string)$current_path == '.') OR ((string)$current_path == '//')) {
				$current_path = '/';
			} //end if
			if((string)substr((string)$current_path, 0, 1) != '/') {
				$current_path = '/'.$current_path;
			} //end if
			if((string)substr((string)$current_path, -1, 1) != '/') {
				$current_path .= '/';
			} //end if
		} //end if
		if((string)$current_path == '') {
			Smart::log_warning(__METHOD__.' # Cannot Determine Current Server URL / Path'); // do not return here, must be cached
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $current_path;
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Ex: http(s)://domain(:port)/sites/test/ (skip port if 80 or 443) if skip port default is set to TRUE (default behaviour) ; or http(s)://domain:port/sites/test/ if skip port default is set to FALSE
	public static function get_server_current_url(bool $skip_port_if_default=true) : string {
		//--
		$cache_key = (string)__FUNCTION__.'#with-port';
		if($skip_port_if_default === true) {
			$cache_key = (string)__FUNCTION__.'#skip-default-port';
		} //end if
		//--
		if(array_key_exists((string)$cache_key, self::$cache)) {
			return (string) self::$cache[(string)$cache_key];
		} //end if
		//--
		$current_port = (string) self::get_server_current_port(); // this shoud not return an empty value, but just in case
		if((string)$current_port == '') {
			Smart::log_warning(__METHOD__.' # Cannot Determine Current Server URL / Port');
			$current_port = '80'; // fallback on default
		} //end if
		$used_port = ':'.$current_port;
		//--
		$current_domain = (string) self::get_server_current_domain_name(); // this shoud not return an empty value, but just in case
		if((string)$current_domain == '') {
			Smart::log_warning(__METHOD__.' # Cannot Determine Current Server URL / Domain');
			$current_domain = (string) self::FAKE_IP_SERVER; // use a fake IP, that does not exists {{{SYNC-SRV-DETECTION-FAKE-IP}}}
		} //end if
		//--
		$current_prefix = 'http://';
		if((string)$current_port == '80') {
			if($skip_port_if_default === true) {
				$used_port = ''; // avoid specify port if default, 80 on http://
			} //end if
		} //end if
		if((string)self::get_server_current_protocol() == 'https://') {
			$current_prefix = 'https://';
			if((string)$current_port == '443') {
				if($skip_port_if_default === true) {
					$used_port = ''; // avoid specify port if default, 443 on https://
				} //end if
			} //end if
		} //end if
		//--
		self::$cache[(string)$cache_key] = (string) $current_prefix.$current_domain.$used_port.self::get_server_current_path();
		//--
		return (string) self::$cache[(string)$cache_key];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function get_webserver_version() : array {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (array) self::$cache[(string)__FUNCTION__];
		} //end if
		//-- it may be hidden: ex: 'Apache'
		$tmp_srv_software = (string) SmartFrameworkRegistry::getServerVar('SERVER_SOFTWARE');
		//--
		$tmp_version_arr = (array) explode('/', (string)$tmp_srv_software);
		$tmp_name_str = (string) trim((string)($tmp_version_arr[0] ?? ''));
		$tmp_out = (string) trim((string)($tmp_version_arr[1] ?? ''));
		$tmp_version_arr = (array) explode(' ', (string)$tmp_out);
		$tmp_version_str = (string) trim((string)($tmp_version_arr[0] ?? ''));
		//--
		self::$cache[(string)__FUNCTION__] = [
			'signature'	=> (string) $tmp_srv_software,
			'name' 		=> (string) $tmp_name_str,
			'version' 	=> (string) $tmp_version_str,
		];
		//--
		return (array) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function get_server_os() : string {
		//--
		if(!array_key_exists((string)__FUNCTION__, self::$cache)) {
			self::$cache[(string)__FUNCTION__] = null; // fix for PHP8
		} //end if
		$out = (string) self::$cache[(string)__FUNCTION__];
		//--
		if((string)$out == '') {
			//-- Notice: if Apache Tokens OS may be hidden ... so this is only an approximate guess of the server OS, not accurate !
			$the_lower_os = (string) strtolower((string)SmartFrameworkRegistry::getServerVar('SERVER_SOFTWARE'));
			//--
			switch((string)strtolower((string)PHP_OS)) { // {{{SYNC-SRV-OS-ID}}}
				case 'openbsd': // OpenBSD
					$out = 'openbsd';
					break;
				case 'netbsd': //NetBSD
					$out = 'netbsd';
					break;
				case 'freebsd': // FreeBSD
					$out = 'freebsd';
					break;
				case 'dragonfly':
				case 'dragonflybsd':
					$out = 'dragonfly'; // DragonFlyBSD
					break;
				case 'bsdos':
				case 'bsd': // Generic BSD OS
					$out = 'bsd-os';
					break;
				case 'linux':
					$out = 'linux'; // Generic Linux
					//- Notice: there is no easy method to guess the linux release ; there are some complicated methods but they are too slow ...
					if(strpos($the_lower_os, '(debian') !== false) {
						$out = 'debian';
					} elseif(strpos($the_lower_os, '(ubuntu') !== false) {
						$out = 'ubuntu';
					} elseif(strpos($the_lower_os, '(mint') !== false) {
						$out = 'mint';
					} elseif(strpos($the_lower_os, '(redhat') !== false) {
						$out = 'redhat';
					} elseif(strpos($the_lower_os, '(centos') !== false) {
						$out = 'centos';
					} elseif(strpos($the_lower_os, '(fedora') !== false) {
						$out = 'fedora';
					} elseif(strpos($the_lower_os, '(suse') !== false) {
						$out = 'suse';
					} elseif(strpos($the_lower_os, '(opensuse') !== false) {
						$out = 'opensuse';
					} elseif(strpos($the_lower_os, '(alpine') !== false) {
						$out = 'alpine';
					} elseif(strpos($the_lower_os, '(void') !== false) {
						$out = 'void';
					} elseif(strpos($the_lower_os, '(arch') !== false) {
						$out = 'arch';
					} elseif(strpos($the_lower_os, '(manjaro') !== false) {
						$out = 'manjaro';
					} elseif(strpos($the_lower_os, '(solus') !== false) {
						$out = 'solus';
					} //end if else
					//-
					break;
				case 'openindiana':
				case 'illumos':
				case 'opensolaris':
				case 'solaris':
				case 'sunos':
					$out = 'solaris'; // SOLARIS
					break;
				case 'mac':
				case 'macos':
				case 'darwin':
				case 'macosx':
					$out = 'macosx'; // MacOSX
					break;
				case 'windows':
				case 'winnt':
				case 'win32':
				case 'win64':
					$out = 'winnt'; // Windows NT
					break;
				default:
					// UNKNOWN
					$out = (string) strtoupper((string)self::GENERIC_VALUE_OS_BROWSER_IP.' '.PHP_OS);
			} //end switch
			//--
			self::$cache[(string)__FUNCTION__] = (string) $out;
			//--
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check if the Client real IP is trusted
	 * If something fails in the detection of the Client real IP address this will return FALSE
	 *
	 * @return 	BOOLEAN						:: TRUE if trusted ; FALSE if not
	 */
	public static function is_ip_client_trusted() : bool {
		//--
		$ip = (string) self::get_ip_client(); // this will check the validity of SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP !
		if((string)trim((string)$ip) == '') {
			return false;
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP')) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the Client real IP ; by default will use $_SERVER['REMOTE_ADDR'] ; but if set in configs can use by example $_SERVER['HTTP_X_FORWARDED_FOR'] or other values (ex: in the case of a reverse proxy)
	 * This function should be used always across the Smart.Framework to get the client's real IP instead using directly the $_SERVER['REMOTE_ADDR'] as the real client IP may change when using apache/php behind haproxy or varnish and in this case by example the $_SERVER['HTTP_X_FORWARDED_FOR'] may return client's real IP address and the $_SERVER['REMOTE_ADDR'] will be in this case the IP of the proxy ...
	 * This can be override if proxy mode enabled, by setting SMART_FRAMEWORK_SRVPROXY_CLIENT_IP
	 *
	 * @return 	STRING						:: IP Address ; if no address detected will RAISE a FATAL ERROR ...
	 */
	public static function get_ip_client() : string {
		//--
		// # if SMART_FRAMEWORK_SRVPROXY_ENABLED is set to TRUE, should be only for PRIVATE NETWORKS like local behind a trusted reverse proxy (ex: load balancers)
		// if custom IP detection was set and the custom IP detection fails (ex: missing the specific header defined in SMART_FRAMEWORK_SRVPROXY_CLIENT_IP) than the SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP will be set internally and some features of Smart.Framework that depend on a trusted client IP detection will not be available (ex: session)
		// # !!! NEVER define the SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP in any config or custom development context, it is reserved for internal usage only !!!
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP')) { // this should be fatal error !
			Smart::log_warning(__METHOD__.' # The constant SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP is reserved for internal usage only ... should never be defined outside this method: '.__METHOD__);
			// do not return here, let it go, at least will determine as much as it can and since this is already defined will be anyway marked as untrusted ...
		} //end if
		//--
		$hkey = '';
		$hval = '';
		//--
		$err = false;
		//--
		if(!defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')) { // must be defined
			$err = true;
		} //end if
		//--
		$custom = (bool) (defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true));
		//--
		if(!$err) {
			//--
			$hkey = (string) strtoupper((string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP));
			//--
			if((string)$hkey == '') {
				$err = true;
			} elseif(!preg_match('/^[_A-Z]+$/', (string)$hkey)) {
				$err = true;
			} //end if else
			//--
			if(!$err) {
				//--
				if($custom === true) {
					if(!in_array((string)$hkey, (array)self::VALID_HEADERS_CLIENT_OR_PROXY_IP)) {
						$err = true;
					} //end if
				} else { // default
					if((string)$hkey != 'REMOTE_ADDR') { // if not custom detection, the REMOTE_ADDR should be used
						$err = true;
					} //end if
				} //end if else
				//--
			} //end if
			//--
		} //end if
		//--
		$ip = ''; // init
		//--
		if($err) {
			//--
			Smart::log_warning(__METHOD__.' # Invalid definition for SMART_FRAMEWORK_SRVPROXY_CLIENT_IP : `'.SMART_FRAMEWORK_SRVPROXY_CLIENT_IP.'`', 'Cannot Determine Current Client IP Address');
			$ip = ''; // must be empty, will fallback below on a fake ip and set as untrusted
			//--
		} else {
			//--
			$hval = (string) SmartFrameworkRegistry::getServerVar((string)$hkey);
			//--
			if((string)$hkey == 'REMOTE_ADDR') {
				$ip = (string) self::_head_value_get_first_val((string)$hval); // when using this one, normally there is just one address ; but if there are many, trust the 1st one being the client's IP
			} else {
				$ip = (string) self::_head_value_get_last_val((string)$hval); // can be one or multiple IP addresses ; since this is mostly a custom header which can be faked, trust the last one as it should be the one added by the proxy by example (a proxy will rewrite or append it's address to this field ...)
			} //end if else
			//--
			$ip = (string) trim((string)SmartValidator::validate_filter_ip_address((string)$ip));
			//--
		} //end if
		//--
		if((string)$ip == '') { // fallback on a fake IP, log warning, mark as untrusted
			//--
			$hkey = 'REMOTE_ADDR';
			$hval = (string) SmartFrameworkRegistry::getServerVar((string)$hkey);
			$ip = (string) self::_head_value_get_first_val((string)$hval); // when using this one, normally there is just one address ; but if there are many, trust the 1st one being the client's IP
			$ip = (string) trim((string)SmartValidator::validate_filter_ip_address((string)$ip));
			//--
			if((string)$ip == '') { // fallback
				$hkey = 'FAKE_ADDR';
				$ip = (string) self::FAKE_IP_CLIENT; // fake IP, could not detect a real one
			} //end if else
			//--
			if(!defined('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP')) {
				define('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP', 'CUSTOM-IP-DETECTION-FALLBACK:['.$hkey.']'); // this is important, some Smart.Framework features will not be enabled when this set, but this is how it should be from the security point of view ... with an untrusted client IP that could not be properly detected !
			} //end if
			Smart::log_warning(__METHOD__.' # Invalid Client IP Address. Fallback to: '.SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP.' ; IP='.$ip);
			//--
		} //end if
		//--
		$ip = (string) Smart::ip_addr_compress((string)$ip); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
		if((string)$ip == '') {
			Smart::raise_error(__METHOD__.' # IP compression Failed');
			return '';
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $ip; // the validated client IP ; {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
		//--
		if(SmartEnvironment::ifDebug()) {
			self::$cache[(string)__FUNCTION__.':validated-header-key'] = (string) $hkey; // the header key
			self::$cache[(string)__FUNCTION__.':validated-header-val'] = (string) $hval; // the header raw value
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Client',
				'data' => 'Validation Header Key: `'.self::$cache[(string)__FUNCTION__.':validated-header-key'].'`'
			]);
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Client',
				'data' => 'Validation Header Raw Value: `'.self::$cache[(string)__FUNCTION__.':validated-header-val'].'`'
			]);
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Client',
				'data' => 'IP Detected: `'.self::$cache[(string)__FUNCTION__].'`'.($custom === true ? ' [SRV-PROXY=true]' : '').(defined('SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP') ? ' [UNTRUSTED:true;'.SMART_FRAMEWORK_SRVPROXY_UNTRUSTED_CLIENT_IP.']' : '')
			]);
		} //end if
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the Client Proxy IP if any such as: HTTP_CLIENT_IP or HTTP_X_FORWARDED_FOR
	 * This function should be used always across the Smart.Framework to get the client's proxy IP and never must use directly HTTP_CLIENT_IP or HTTP_X_FORWARDED_FOR or other headers directly as they may change when using apache/php behind haproxy or varnish !
	 *
	 * @return 	STRING						:: IP Address or a space (if no proxy address detected)
	 */
	public static function get_ip_proxyclient() : string {
		//--
		if(array_key_exists((string)__FUNCTION__, self::$cache)) {
			return (string) self::$cache[(string)__FUNCTION__];
		} //end if
		//--
		$arr_valid_hdrs = (array) self::VALID_HEADERS_CLIENT_OR_PROXY_IP;
		//--
		$arr_hdrs = [];
		$err = false;
		//--
		if(!defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_IP')) { // must be defined, it is used also in this method
			$err = true;
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP')) { // must be defined
			$err = true;
		} //end if
		//--
		$custom = (bool) (defined('SMART_FRAMEWORK_SRVPROXY_ENABLED') AND (SMART_FRAMEWORK_SRVPROXY_ENABLED === true));
		//--
		if(!$err) {
			$tmp_arr_hdrs = (array) Smart::list_to_array((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP);
			$use_remote_addr = false;
			if(in_array('REMOTE_ADDR', (array)$tmp_arr_hdrs)) {
				if($custom === true) {
					if((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP != 'REMOTE_ADDR') {
						$use_remote_addr = true;
					} //end if
				} else { // this should be just warning
					Smart::log_warning(__METHOD__.' # The SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP must not contain REMOTE_ADDR when SMART_FRAMEWORK_SRVPROXY_ENABLED is not set to TRUE !');
					return ''; // proxy can be empty if not proper detected
				} //end if
			} //end if
			for($i=0; $i<Smart::array_size($tmp_arr_hdrs); $i++) {
				$tmp_arr_hdrs[$i] = (string) strtoupper((string)$tmp_arr_hdrs[$i]);
				if(preg_match('/^[_A-Z]+$/', (string)$tmp_arr_hdrs[$i])) {
					if((string)$tmp_arr_hdrs[$i] != 'REMOTE_ADDR') { // except REMOTE_ADDR, which is usable by conditions will be added at the end any other valid headers can be in both places: SMART_FRAMEWORK_SRVPROXY_CLIENT_IP and SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP as they can contain more than one IP, separed by comma and get ip client from SMART_FRAMEWORK_SRVPROXY_CLIENT_IP will use the first in this list and the get proxy ip from SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP will use the last ; ex: 127.0.0.1, 127.0.1.1, ...
						if(in_array((string)$tmp_arr_hdrs[$i], (array)$arr_valid_hdrs)) {
							$arr_hdrs[] = (string) $tmp_arr_hdrs[$i];
						} //end if
					} //end if
				} //end if
			} //end for
			$tmp_arr_hdrs = null;
			if($custom === true) {
				if($use_remote_addr === true) {
					$arr_hdrs[] = 'REMOTE_ADDR'; // add at the end, only if present in SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP and not in SMART_FRAMEWORK_SRVPROXY_CLIENT_IP only when SMART_FRAMEWORK_SRVPROXY_ENABLED is TRUE it must be add at the end only
				} //end if
			} elseif(Smart::array_size($arr_hdrs) <= 0) {
				$err = true; // this must not be an error, if using a proxy there are situations when no client proxy may be returned ...
			} //end if
		} //end if
		//--
		if($err) {
			Smart::log_warning(__METHOD__.' # Invalid definition for SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP');
			return ''; // proxy can be empty if not proper detected
		} //end if
		//--
		$chkey = '';
		if($custom === true) {
			$chkey = (string) strtoupper((string)trim((string)SMART_FRAMEWORK_SRVPROXY_CLIENT_IP));
			if(((string)trim((string)$chkey) == '') OR (!in_array((string)$chkey, (array)self::VALID_HEADERS_CLIENT_OR_PROXY_IP))) {
				Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP definition must be validated against SMART_FRAMEWORK_SRVPROXY_CLIENT_IP which contains an invalid value');
				return ''; // proxy can be empty if not proper detected
			} //end if
		} //end if
		//--
		$proxy = ''; // init
		$hkey = '';
		$hval = '';
		//--
		for($i=0; $i<Smart::array_size($arr_hdrs); $i++) {
			$hkey = (string) strtoupper((string)trim((string)$arr_hdrs[$i]));
			if((string)$hkey != '') {
				if((string)$chkey != '') {
					if((string)$hkey == (string)$chkey) {
						Smart::log_warning(__METHOD__.' # SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP definition cannot contain the same key as defined in SMART_FRAMEWORK_SRVPROXY_CLIENT_IP', 'Failed to register Current Client Proxy IP Address');
						return ''; // proxy can be empty if not proper detected
					} //end if
				} //end if
				$hval = (string) SmartFrameworkRegistry::getServerVar((string)$hkey);
				if((string)$hval != '') {
					if((string)SmartFrameworkSecurity::FilterUnsafeString((string)$hval) != '') {
						$proxy = (string) self::_head_value_get_last_val((string)$hval); // since this is mostly from a custom header which can be faked, trust the last one as it should be the one added by the proxy by example (a proxy will rewrite or append it's address to this field ...)
						$proxy = (string) trim((string)SmartValidator::validate_filter_ip_address((string)$proxy));
						if((string)$proxy != '') {
							break;
						} //end if
					} //end if
				} //end if
			} //end if
			$hkey = ''; // must reset each cycle, except break
			$hval = ''; // must reset each cycle, except break
		} //end for
		//--
		if((string)$proxy != '') { // this may be empty, compress and check below just if not empty
			$proxy = (string) Smart::ip_addr_compress((string)$proxy); // {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
			if((string)$proxy == '') {
				Smart::raise_error(__METHOD__.' # IP compression Failed');
				return '';
			} //end if
		} //end if
		//--
		self::$cache[(string)__FUNCTION__] = (string) $proxy; // the validated proxy-client IP ; {{{SYNC-SMART-SERVER-DOMAIN-IPV6-SHORT-COMPRESS}}}
		//--
		if(SmartEnvironment::ifDebug()) {
			self::$cache[(string)__FUNCTION__.':check-header-keys'] 	= (array)  $arr_hdrs; // the header keys
			self::$cache[(string)__FUNCTION__.':validated-header-key'] 	= (string) $hkey; // the validated header key
			self::$cache[(string)__FUNCTION__.':validated-header-val'] 	= (string) $hval; // the header raw value
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Proxy Client',
				'data' => 'Check Header Keys:'."\n".self::pretty_print_var(self::$cache[(string)__FUNCTION__.':check-header-keys'])
			]);
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Proxy Client',
				'data' => 'Validation Header Key: `'.self::$cache[(string)__FUNCTION__.':validated-header-key'].'`'
			]);
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Proxy Client',
				'data' => 'Validation Header Raw Value: `'.self::$cache[(string)__FUNCTION__.':validated-header-val'].'`'
			]);
			SmartEnvironment::setDebugMsg('extra', 'SMART-UTILS', [
				'title' => 'SmartUtils // Get IP Proxy Client',
				'data' => 'IP Proxy Detected: `'.self::$cache[(string)__FUNCTION__].'`'.($custom === true ? ' [SRV-PROXY=true]' : '')
			]);
		} //end if
		//--
		return (string) self::$cache[(string)__FUNCTION__];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// GET OS, BROWSER, IP :: ACCESS LOG
	// This will be used only once
	public static function get_os_browser_ip(?string $y_mode='') { // : MIXED return, ARRAY/STRING
		//-- r.20241031
		if((!array_key_exists((string)__FUNCTION__, self::$cache)) OR (!is_array(self::$cache[(string)__FUNCTION__]))) {
			self::$cache[(string)__FUNCTION__] = []; // fix for PHP8
		} //end if
		//--
		$arr = (array) self::$cache[(string)__FUNCTION__];
		//--
		if(Smart::array_size($arr) <= 0) {
			//--
			$wp_browser = (string) self::GENERIC_VALUE_OS_BROWSER_IP;
			$wp_class 	= (string) self::GENERIC_VALUE_OS_BROWSER_IP;
			$wp_os 		= (string) self::GENERIC_VALUE_OS_BROWSER_IP;
			$wp_ip 		= (string) self::GENERIC_VALUE_OS_BROWSER_IP;
			$wp_px 		= (string) self::GENERIC_VALUE_OS_BROWSER_IP;
			$wp_mb 		= 'no'; // by default is not mobile
			//--
			$the_srv_signature = '';
			$the_srv_signature = (string) self::get_visitor_useragent();
			$the_lower_signature = (string) strtolower((string)$the_srv_signature);
			//--
			// {{{SYNC-CLI-BW-ID}}}
			//-- identify browser ; real supported browser classes: gk, ie, wk ; other classes as: xy are not trusted ... ;  tx / rb are text/robots browsers
			if((strpos($the_lower_signature, 'firefox') !== false) OR (strpos($the_lower_signature, 'iceweasel') !== false) OR (strpos($the_lower_signature, ' fxios/') !== false)) {
				$wp_browser = 'fox'; // firefox
				$wp_class = 'gk'; // gecko class
			} elseif(strpos($the_lower_signature, 'seamonkey') !== false) {
				$wp_browser = 'smk'; // mozilla seamonkey
				$wp_class = 'gk'; // gecko class
			} elseif((strpos($the_lower_signature, ' edg/') !== false) OR (strpos($the_lower_signature, ' edge/') !== false)) {
				$wp_browser = 'iee'; // microsoft edge
				$wp_class = 'bk'; // blink class
			} elseif((strpos($the_lower_signature, 'opera') !== false) OR (strpos($the_lower_signature, ' opr/') !== false) OR (strpos($the_lower_signature, ' oupeng/') !== false) OR (strpos($the_lower_signature, ' opios/') !== false)) {
				$wp_browser = 'opr'; // opera
				$wp_class = 'bk'; // blink class
			} elseif((strpos($the_lower_signature, 'iridium') !== false) OR (strpos($the_lower_signature, 'chromium') !== false) OR (strpos($the_lower_signature, 'chrome') !== false) OR (strpos($the_lower_signature, ' crios/') !== false)) {
				$wp_browser = 'crm'; // chromium
				$wp_class = 'bk'; // blink class
			} elseif(strpos($the_lower_signature, 'konqueror') !== false) { // must be detected before safari because includes safari signature
				$wp_browser = 'knq'; // konqueror (kde)
				$wp_class = 'bk'; // blink class ; since recently all konqueror releases are using qt webengine (blink)
			} elseif(strpos($the_lower_signature, 'epiphany') !== false) { // must be detected before safari because includes safari signature
				$wp_browser = 'eph'; // epiphany (gnome)
				$wp_class = 'wk'; // webkit class
			} elseif((strpos($the_lower_signature, 'safari') !== false) OR (strpos($the_lower_signature, 'applewebkit') !== false)) {
				$wp_browser = 'sfr'; // safari
				$wp_class = 'wk'; // webkit class
			} elseif(strpos($the_lower_signature, 'webkit') !== false) { // general webkit signature, untrusted
				$wp_browser = 'wkt'; // webkit (component)
				$wp_class = 'wk'; // webkit class
			} elseif((strpos($the_lower_signature, 'mozilla') !== false) OR (strpos($the_lower_signature, 'gecko') !== false)) { // general mozilla signature, untrusted
				$wp_browser = 'moz'; // mozilla derivates, but not firefox or seamonkey which are detected above
				$wp_class = 'xy'; // various class
			} elseif(strpos($the_lower_signature, 'netsurf/') !== false) { // netsurf have just a simple signature, but avoid detect robots which are faking this signature ...
				$wp_browser = 'nsf'; // netsurf
				$wp_class = 'xy'; // various class
			} elseif((strpos($the_lower_signature, 'lynx') !== false) OR (strpos($the_lower_signature, 'links') !== false)) { // console text browsers and derivates
				$wp_browser = 'lyx'; // lynx / links (text browser)
				$wp_class = 'tx'; // text class
			} //end if else
			//--
			// {{{SYNC-CLI-OS-ID}}}
			//-- identify os
			if((strpos($the_lower_signature, 'windows') !== false) OR (strpos($the_lower_signature, 'winnt') !== false)) {
				$wp_os = 'win'; // ms windows
			} elseif((strpos($the_lower_signature, 'macos') !== false) OR (strpos($the_lower_signature, ' mac ') !== false) OR (strpos($the_lower_signature, 'os x') !== false) OR (strpos($the_lower_signature, 'osx') !== false) OR (strpos($the_lower_signature, 'darwin') !== false) OR (strpos($the_lower_signature, 'macintosh') !== false)) {
				$wp_os = 'mac'; // apple mac / osx / darwin
			} elseif(strpos($the_lower_signature, 'linux') !== false) {
				$wp_os = 'lnx'; // *linux
			} elseif((strpos($the_lower_signature, 'openbsd') !== false) OR (strpos($the_lower_signature, 'netbsd') !== false) OR (strpos($the_lower_signature, 'freebsd') !== false) OR (strpos($the_lower_signature, 'dragonfly') !== false) OR (strpos($the_lower_signature, ' bsd ') !== false)) {
				$wp_os = 'bsd'; // *bsd
			} elseif((strpos($the_lower_signature, 'openindiana') !== false) OR (strpos($the_lower_signature, 'illumos') !== false) OR (strpos($the_lower_signature, 'opensolaris') !== false) OR (strpos($the_lower_signature, 'solaris') !== false) OR (strpos($the_lower_signature, 'sunos') !== false)) {
				$wp_os = 'sun'; // sun solaris incl clones
			} //end if
			//-- identify mobile os
			if((strpos($the_lower_signature, 'iphone') !== false) OR (strpos($the_lower_signature, 'ipad') !== false) OR (strpos($the_lower_signature, 'ipod') !== false) OR (strpos($the_lower_signature, 'iwatch') !== false) OR (strpos($the_lower_signature, ' opios/') !== false) OR (strpos($the_lower_signature, ' crios/') !== false)) {
				$wp_os = 'ios'; // apple mobile ios: iphone / ipad / ipod / iwatch (and derivatives)
				$wp_mb = 'yes';
			} elseif((strpos($the_lower_signature, 'android') !== false) OR (strpos($the_lower_signature, 'saphi') !== false) OR (strpos($the_lower_signature, ' opr/') !== false) OR (strpos($the_lower_signature, ' oupeng/') !== false)) {
				$wp_os = 'and'; // google android (and derivatives)
				$wp_mb = 'yes';
			} elseif((strpos($the_lower_signature, 'windows ce') !== false) OR (strpos($the_lower_signature, 'windows phone') !== false) OR (strpos($the_lower_signature, 'windows mobile') !== false) OR (strpos($the_lower_signature, 'windows rt') !== false)) {
				$wp_os = 'wmo'; // ms windows mobile
				$wp_mb = 'yes';
			} elseif(((strpos($the_lower_signature, 'mobile') !== false) AND ((strpos($the_lower_signature, 'linux') !== false) OR (strpos($the_lower_signature, 'ubuntu') !== false))) OR (strpos($the_lower_signature, 'tizen') !== false) OR (strpos($the_lower_signature, 'webos') !== false) OR (strpos($the_lower_signature, 'raspberry') !== false) OR (strpos($the_lower_signature, 'blackberry') !== false)) {
				$wp_os = 'lxm'; // linux mobile
				$wp_mb = 'yes';
			} //end if
			//-- later fix
			if((string)$wp_browser == 'sfr') {
				if(((string)$wp_os != 'mac') AND ((string)$wp_os != 'ios')) { // safari can run just on Mac and iOS ; Webkit fakes also the signature as Safari
					$wp_browser = 'wkt'; // webkit
					$wp_class = 'xy'; // various class
				} //end if
			} //end if
			//-- robots
			if(defined('SMART_FRAMEWORK_IDENT_ROBOTS')) {
					$robots = [];
					if(is_array(SMART_FRAMEWORK_IDENT_ROBOTS)) {
						$robots = (array) SMART_FRAMEWORK_IDENT_ROBOTS;
					} else {
						$robots = (array) Smart::list_to_array((string)SMART_FRAMEWORK_IDENT_ROBOTS, false); // do not trim inside values
					} //end if else
					$imax = (int) Smart::array_size($robots);
					for($i=0; $i<$imax; $i++) {
						if(stripos((string)$the_lower_signature, (string)$robots[$i]) !== false) {
							$wp_browser = 'bot'; // robot
							$wp_class = 'rb'; // bot class
							break;
						} //end if
					} //end for
			//	} //end if
			} //end if else
			//-- this is just for self-robot which name is always unique and impossible to guess ; this must override the rest of detections just in the case that someone adds it to the ident robots in init ...
			if((string)trim((string)$the_lower_signature) == (string)strtolower((string)self::get_selfrobot_useragent_name())) {
				$wp_browser = '@s#'; // self-robot
				$wp_class = 'rb'; // bot class
			} //end if
			//-- identify ip addr
			$wp_ip = (string) self::get_ip_client();
			//-- identify proxy ip if any
			$wp_px = (string) self::get_ip_proxyclient();
			//-- out data arr
			$arr = [
				'signature'	=> (string) $the_srv_signature,
				'mobile' 	=> (string) $wp_mb,
				'os' 		=> (string) $wp_os,
				'bw' 		=> (string) $wp_browser,
				'bc' 		=> (string) $wp_class,
				'ip' 		=> (string) $wp_ip,
				'px' 		=> (string) $wp_px,
			];
			//--
			self::$cache[(string)__FUNCTION__] = (array) $arr;
			//--
		} //end if
		//--
		if((string)$y_mode != '') {
			return (string) $arr[(string)$y_mode];
		} else {
			return (array) $arr;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//##### NON-PUBLICS


	//================================================================
	/**
	 * Function: Run Proc Cmd
	 * This method is using the proc_open() which provides a much greater degree of control over the program execution
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param $cmd 		STRING 			:: the command to run ; must be escaped using escapeshellcmd() and arguments using escapeshellarg()
	 * @param $inargs 	ARRAY / NULL 	:: *Optional*, Default NULL ; the array containing the input for the STDIN
	 * @param $cwd 		STRING / NULL 	:: *Optional*, Default 'tmp/cache/run-proc-cmd' ; Use NULL to use the the working dir of the current PHP process (not recommended) ; A path for a directory to run the process in ; If not null, if path does not exists will be created
	 * @param $env 		ARRAY / NULL 	:: *Optional*, default $env ; the array with environment variables ; If NULL will use the same environment as the current PHP process
	 *
	 * @return ARRAY					:: [ stdout, stderr, exitcode ]
	 *
	 */
	public static function run_proc_cmd(?string $cmd, ?array $inargs=null, ?string $cwd='tmp/cache/run-proc-cmd', ?array $env=null) : array {

		//-- initialize
		$descriptorspec = [
			0 => [ 'pipe', 'r' ], // stdin
			1 => [ 'pipe', 'w' ], // stdout
			2 => [ 'pipe', 'w' ]  // stderr
		];
		//--
		$output = array();
		$rderr = false;
		$pipes = array();
		//--

		//--
		$outarr = [
			'stdout' 	=> '',
			'stderr' 	=> '',
			'exitcode' 	=> -999
		];
		//--

		//-- checks
		if((string)trim((string)$cmd) == '') {
			Smart::log_warning(__METHOD__.' # The CMD Path is empty');
			$outarr['exitcode'] = -800;
			return (array) $outarr;
		} //end if
		if((int)strlen((string)$cmd) > (int)PHP_MAXPATHLEN) {
			Smart::log_warning(__METHOD__.' # The CMD Path is too long: `'.$cmd.'`');
			$outarr['exitcode'] = -799;
			return (array) $outarr;
		} //end if
		//--
		if((int)strlen((string)$cwd) > (int)PHP_MAXPATHLEN) {
			Smart::log_warning(__METHOD__.' # The CWD Path is too long: `'.$cwd.'`');
			$outarr['exitcode'] = -798;
			return (array) $outarr;
		} //end if
		if((string)$cwd != '') {
			if(!SmartFileSysUtils::checkIfSafePath((string)$cwd, true, true)) { // this is synced with SmartFileSystem::dir_create() ; without this check if non-empty will fail with dir create below
				Smart::log_warning(__METHOD__.' # The CWD Path is not safe: `'.$cwd.'`');
				$outarr['exitcode'] = -797;
				return (array) $outarr;
			} //end if
		} //end if
		//--

		//-- exec
		if((string)$cwd != '') {
			if(!SmartFileSystem::path_exists((string)$cwd)) {
				SmartFileSystem::dir_create((string)$cwd, true); // recursive
			} //end if
			if(!SmartFileSystem::is_type_dir((string)$cwd)) {
				//--
				Smart::log_warning(__METHOD__.' # The Proc Open CWD Path: `'.$cwd.'` cannot be created and is not available !');
				//--
				$outarr['stdout'] 	= '';
				$outarr['stderr'] 	= '';
				$outarr['exitcode'] = -998;
				//--
				return (array) $outarr;
				//--
			} //end if
		} else {
			$cwd = null;
		} //end if
		$resource = proc_open((string)$cmd, (array)$descriptorspec, $pipes, $cwd, $env);
		//--
		if(!is_resource($resource)) {
			//--
			$outarr['stdout'] 	= '';
			$outarr['stderr'] 	= 'Could not open Process / Not Resource';
			$outarr['exitcode'] = -997;
			//--
			return (array) $outarr;
			//--
		} //end if
		//--

		//-- write to stdin
		if(is_array($inargs)) {
			if(count($inargs) > 0) {
				foreach($inargs as $key => $val) {
					fwrite($pipes[0], (string)$val);
				} //end foreach
			} //end if
		} //end if
		//--

		//-- read stdout
		$output = (string) stream_get_contents($pipes[1]); // don't convert charset as it may break binary files
		//--

		//-- read stderr (here may be errors or warnings)
		$errors = (string) stream_get_contents($pipes[2]); // don't convert charset as it may break binary files
		//--

		//--
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		//--
		$exitcode = proc_close($resource);
		//--

		//--
		$outarr['stdout'] 	= (string) $output;
		$outarr['stderr'] 	= (string) $errors;
		$outarr['exitcode'] = $exitcode; // don't make it INT !!!
		//--
		return (array) $outarr;
		//--

	} //END FUNCTION
	//================================================================


	//##### PRIVATES


	//================================================================
	// gets the first value from simple or composed headers
	// Example: 'X-Forwarded-For: client'
	// Example: 'X-Forwarded-For: client, proxy1, proxy2'
	private static function _head_value_get_first_val(?string $str) : string {
		//--
		$str = (string) trim((string)$str);
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		if(strpos((string)$str, ',') !== false) { // if we detect many values in a header, separed by comma
			//--
			$arr = (array) explode(',', (string)$str);
			$str = ''; // we clear it
			//--
			$imax = (int) Smart::array_size($arr);
			if((int)$imax > 0) {
				for($i=0; $i<$imax; $i++) { // loop forward ; do not validate ; the trusted is the first value before first comma
					$str = (string) trim((string)$arr[$i]);
					break;
				} //end for
			} //end if
			//--
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// gets the first value from simple or composed headers
	// Example: 'X-Forwarded-For: client'
	// Example: 'X-Forwarded-For: client, proxy1, proxy2'
	private static function _head_value_get_last_val(?string $str) : string {
		//--
		$str = (string) trim((string)$str);
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		if(strpos((string)$str, ',') !== false) { // if we detect many values in a header, separed by comma
			//--
			$arr = (array) explode(',', (string)$str);
			$str = ''; // we clear it
			//--
			$imax = (int) Smart::array_size($arr);
			if((int)$imax > 1) {
				for($i=($imax-1); $i>0; $i--) { // loop backward ; do not validate ; the trusted is the last value after comma
					$str = (string) trim((string)$arr[$i]);
					break;
				} //end for
			} //end if
			//--
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//##### DEBUG ONLY


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartUtils // Internal Cache',
					'data' => 'Dump:'."\n".print_r(self::$cache,1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
