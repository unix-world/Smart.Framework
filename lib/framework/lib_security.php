<?php
// [LIB - Smart.Framework / Security]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Security Compliance
//======================================================

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class Smart.Framework Security
 * It may be used anywhere inside Smart.Framework or by Plugins and Application Modules.
 *
 * <code>
 * // Usage example:
 * SmartFrameworkSecurity::some_method_of_this_class(...);
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		This is generally intended for advanced usage !
 *
 * @depends 	SmartUnicode
 *
 * @version 	v.20210506
 * @package 	Application
 *
 */
final class SmartFrameworkSecurity {

	// ::
	// This class have to be able to run before loading the Smart.Framework and must not depend on it's classes.


	//======================================================================
	/**
	 * Validate an URL variable name
	 *
	 * @param 	STRING 		$y_varname 		:: The value to be tested
	 *
	 * @return 	0/1							:: 1 if Valid, 0 if Invalid
	 */
	public static function ValidateUrlVariableName(?string $y_varname) {

		// VALIDATE INPUT (REQUEST / COOKIES) VARIABLE NAMES .20210413

		//--
		$y_varname = (string) $y_varname; // force string
		//--

		//--
		$regex_var_name = '/^[a-zA-Z0-9_\-]+$/'; // {{{SYNC-REGEX-URL-VARNAME}}}
		//--

		//-- init
		$out = 0;
		//-- validate characters (variable must not be empty, must not start with an underscore or a number
		if(((string)$y_varname != '') AND (preg_match((string)$regex_var_name, (string)$y_varname))) {
			$out = 1;
		} //end if else
		//-- corrections (variable name must be between 1 char and 128 chars)
		if((int)strlen((string)$y_varname) < 1) {
			$out = 0;
		} elseif((int)strlen((string)$y_varname) > 128) {
			$out = 0;
		} //end if
		//--

		//--
		return (int) $out;
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Validate a PHP variable name
	 *
	 * @param 	STRING 		$y_varname 					:: The value to be tested
	 * @param 	BOOLEAN 	$y_allow_upper_letters 		:: Default is TRUE; If set to FALSE will dissalow upper case letters
	 *
	 * @return 	0/1										:: 1 if Valid, 0 if Invalid
	 */
	public static function ValidateVariableName(?string $y_varname, bool $y_allow_upper_letters=true) {

		// VALIDATE PHP VARIABLE NAMES v.20210413

		//--
		$y_varname = (string) $y_varname; // force string
		//--

		//--
		$regex_only_number = '/^[0-9_]+$/'; 		// not allowed as first character, especially the _ because $_ have a very special purpose in PHP
		//--
		if($y_allow_upper_letters === true) {
			$regex_var_name = '/^[a-zA-Z0-9_]+$/'; 	// allowed characters in a variable name (only small letters, upper letters, numbers and _ ; in PHP upper letters for variables are reserved)
		} else {
			$regex_var_name = '/^[a-z0-9_]+$/'; 	// allowed characters in a variable name (only small letters, numbers and _ ; in PHP upper letters for variables are reserved)
		} //end if else
		//--

		//-- init
		$out = 0;
		//-- validate characters (variable must not be empty, must not start with an underscore or a number
		if(((string)$y_varname != '') AND ((string)$y_varname != '_') AND (preg_match((string)$regex_var_name, (string)$y_varname)) AND (!preg_match((string)$regex_only_number, (string)substr((string)$y_varname, 0, 1)))) {
			$out = 1;
		} //end if else
		//-- corrections (variable name must be between 1 char and 255 chars)
		if((int)strlen((string)$y_varname) < 1) {
			$out = 0;
		} elseif((int)strlen((string)$y_varname) > 255) {
			$out = 0;
		} //end if
		//--

		//--
		return (int) $out;
		//--

	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return a filtered string value for untrusted string (or similar, scalar or null) variables.
	 * It may be used for filtering insecure / untrusted variables.
	 * Notice: For this to work correctly expects the filter to be provided by SMART_FRAMEWORK_SECURITY_FILTER_INPUT
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param MIXED 						$y_value	the input variable value
	 * @return STRING/NULL								the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterUnsafeString($y_value) {
		//-- v.20210413
		if(is_object($y_value) OR is_resource($y_value) OR is_array($y_value)) { // dissalow here, it always
			return null;
		} //end if
		//--
		$is_filtered = false;
		if(defined('SMART_FRAMEWORK_SECURITY_FILTER_INPUT')) {
			if((string)SMART_FRAMEWORK_SECURITY_FILTER_INPUT != '') {
				$is_filtered = true;
				if((string)$y_value != '') {
					$y_value = (string) preg_replace((string)SMART_FRAMEWORK_SECURITY_FILTER_INPUT, '', (string)$y_value);
				} //end if
			} //end if
		} //end if
		//--
		if(!$is_filtered) {
			@trigger_error(__CLASS__.'::'.__FUNCTION__.'() // Could Not Apply Filter, No Filter Defined !', E_USER_WARNING);
		} //end if
		//--
		return (string) $y_value;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered values for GET/POST/REQUEST/COOKIE variables, using the FilterUnsafeString method
	 * It may be used for filtering insecure / untrusted string or array variables
	 * For array variables it also filters the keys
	 * When using the raw values from $_GET, $_POST, $_REQUEST or $_COOKIE - all the values should be always filtered prior to be used in PHP to avoid insecure characters.
	 * Notice: For this to work correctly expects the filter to be provided by SMART_FRAMEWORK_SECURITY_FILTER_INPUT
	 * Important: All the REQUEST=GET+POST and COOKIE variables from SmartFrameworkRegistry are already filtered, no need to filter them again, but if you are using any raw value from $_GET, $_POST, $_REQUEST or $_COOKIE it must be filtered !
	 *
	 * @param MIXED 											$y_var	the input variable value
	 * @return MIXED											the filtered value (if OBJECT or RESOURCE will return null)
	 */
	public static function FilterRequestVar($y_var) {
		//--
		if(!isset($y_var)) {
			return null; // fix for Illegal string offset
		} //end if
		//--
		if(is_object($y_var) OR is_resource($y_var)) { // objects or resources are not allowed to com from GET/POST/REQUEST/COOKIE
			//--
			$y_var = null; // invalid !! it comes from request
			//--
		} elseif(is_array($y_var)) { // array
			//--
			$narr = [];
			foreach($y_var as $key => $val) {
				if(is_object($val) OR is_resource($val)) { // objects or resources are not allowed to com from GET/POST/REQUEST/COOKIE
					$val = null;
				} elseif(is_array($val)) { // array
					$val = (array) self::FilterRequestVar((array)$val);
				} else { // nScalar
					$val = (string) self::FilterUnsafeString((string)$val);
				} //end if else
				$narr[self::FilterUnsafeString((string)$key)] = $val; // mixed
			} //end foreach
			$y_var = (array) $narr;
			$narr = null;
			//--
		} else { // nScalar
			//--
			$y_var = (string) self::FilterUnsafeString((string)$y_var);
			//--
		} //end if
		//--
		return $y_var; // mixed
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered value for a cookie from a COOKIE variable, using the FilterUnsafeString method
	 * It may be used for filtering the insecure / untrusted raw values from $_COOKIE
	 *
	 * @param MIXED 						$y_value	the input variable value
	 * @return STRING/NULL								the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterCookieVar($y_var) {
		//--
		if(!isset($y_var)) {
			return null; // fix for Illegal string offset
		} //end if
		//--
		return (string) self::FilterUnsafeString((string)$y_var);
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the filtered values for PATH_INFO server variable, using the FilterUnsafeString method, and apply trim
	 * It may be used for filtering the insecure / untrusted raw value of $_SERVER['PATH_INFO']
	 *
	 * @param MIXED 						$y_value	the input variable value
	 * @return STRING/NULL								the filtered value (if ARRAY or OBJECT or RESOURCE will return null)
	 */
	public static function FilterRequestPath($y_var) {
		//--
		if(!isset($y_var)) {
			return null; // fix for Illegal string offset
		} //end if
		//--
		return (string) trim((string)self::FilterUnsafeString((string)$y_var));
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Return the url decoded (+/- filtered) variable from RAWURLENCODE / URLENCODE
	 * It may be used ONLY when working with RAW PATH INFO / RAW QUERY URLS !!!
	 * IMPORTANT: the $_GET and $_REQUEST are already decoded. Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.
	 *
	 * @param STRING 				$y_urlencoded_str_var		the input variable
	 * @param BOOLEAN 				$y_filter 					*Optional* Default to TRUE ; if FALSE will only decode but not filter variable ; DO NOT DISABLE FILTERING EXCEPT WHEN YOU CALL IT LATER EXPLICIT !!!
	 * @return STRING											the decoded +/- filtered value
	 */
	public static function DecodeAndFilterUrlVarString(?string $y_urlencoded_str_var, $y_filter=true) {
		//--
		$y_urlencoded_str_var = (string) urldecode((string)$y_urlencoded_str_var); // use urldecode() which decodes all % but also the + ; instead of rawurldecode() which does not decodes + !
		//--
		if($y_filter !== false) {
			$y_urlencoded_str_var = (string) self::FilterUnsafeString((string)$y_urlencoded_str_var);
		} //end if
		//--
		return (string) $y_urlencoded_str_var;
		//--
	} //END FUNCTION
	//======================================================================


	//======================================================================
	/**
	 * Prepare a safe value to be used with the header() function
	 * It will filter out all dangerous characters and will replace some control characters with spaces
	 * It willalso trim the string thus it may return an empty string if the passed value contains only invalid characters
	 *
	 * @param STRING 				$value						the input value
	 * @return STRING											the prepared value
	 */
	public static function PrepareSafeHeaderValue(?string $value) {
		//--
		$value = (string) trim((string)$value);
		if((string)$value == '') {
			return '';
		} //end if
		//--
		$value = (string) self::FilterUnsafeString((string)$value); // variables from PathInfo are already URL Decoded, so must be ONLY Filtered !
		$value = (string) str_replace(["\r\n", "\r", "\n", "\t", "\x0B", "\0", "\f", "\x7F"], ' ', (string)$value); // {{{SYNC-NORMALIZE-SPACES}}} + DEL char ; security fix: avoid newline in header
		$value = (string) SmartUnicode::deaccent_str((string)$value); // HTTP headers doesn't support UTF-8. They officially support ISO-8859-1 only # https://www.w3.org/Protocols/rfc2616/rfc2616-sec2.html
		$value = (string) trim((string)$value);
		//--
		return (string) $value;
		//--
	} //END FUNCTION
	//======================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
