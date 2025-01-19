<?php
// [LIB - Smart.Framework / Smart Validators and Parsers]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Validators and Parsers
//======================================================


//=================================================================================
//================================================================================= CLASS START
//=================================================================================


/**
 * Class: SmartValidator - provides misc validating methods.
 *
 * <code>
 * // Usage example:
 * SmartValidator::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 *
 * @access      PUBLIC
 * @depends     classes: Smart, SmartUnicode
 * @version     v.20250107
 * @package     @Core:Extra
 *
 */
final class SmartValidator {

	// ::


	//=================================================================
	/**
	 * Regex Expressions for Text Parsing of: Numbers, IP addresses, Valid eMail Address with TLD domain, Phone (US), Unicode Text (UTF-8)
	 *
	 * @param 	ENUM 	$y_mode 			:: The Regex mode to be returned ; valid modes:
	 * 											number-integer			:: number integer: as -10 or 10
	 * 											number-decimal			:: number decimal: as -0.05 or 0.05
	 * 											number-list-integer 	:: number, list integer: as 1;2;30 (numbers separed by semicolon=;)
	 * 											number-list-decimal 	:: number, list decimal: as 1.0;2;30.44 (numbers separed by semicolon=;)
	 *											ipv4 					:: IP (v4): 0.0.0.0 .. 255.255.255.255
	 *											ipv6 					:: IP (v4): ::1 .. 2a00:1450:400c:c01::68 ...
	 * 											email					:: eMail@address.tld ; MUST contain a TLD ; TLD can be 2 letters long as well as 3 or more
	 * 											phone-us 				:: US phone numbers
	 * 											utf8-text 				:: Unicode (UTF-8) Text
	 *
	 * @param 	ENUM 	$y_match 			:: Match Type: full / partial
	 *
	 * @return 	STRING						:: The Regex expression or empty if invalid mode is provided
	 */
	public static function regex_stringvalidation_expression($y_mode, $y_match='full') {
		//--
		switch((string)strtolower((string)$y_match)) {
			case 'full':
				$rxs = '^';
				$rxe = '$';
				break;
			case 'partial':
				$rxs = '';
				$rxe = '';
				break;
			default:
				Smart::raise_error(
					'INVALID match type in function '.__CLASS__.'::'.__FUNCTION__.'(): '.$y_match,
					'Validations Internal ERROR' // msg to display
				);
				die(''); 	// just in case
				return '+'; // just in case
		} //end switch
		//--
		switch((string)strtolower((string)$y_mode)) { // WARNING: Never use class modifiers like [:print:] with /u modifier as it fails with some versions of PHP / Regex / PCRE
			//--
			//== #EXTERNAL USE
			//--
			case 'date':
				$regex = '/'.$rxs.'([0-9]{4,}\-[0-9]{2}\-[0-9]{2})'.$rxe.'/'; // example: `2023-10-12`
				break;
			case 'date-time':
				$regex = '/'.$rxs.'([0-9]{4,}\-[0-9]{2}\-[0-9]{2} [0-9]{2}\:[0-9]{2}\:[0-9]{2})'.$rxe.'/'; // example: `2023-10-12 22:33:44`
				break;
			case 'date-time-tzofs':
				$regex = '/'.$rxs.'([0-9]{4,}\-[0-9]{2}\-[0-9]{2} [0-9]{2}\:[0-9]{2}\:[0-9]{2} (\+|\-)[0-9]{4})'.$rxe.'/'; // example: `2023-10-12 22:33:44 +0000`
				break;
			//--
			case 'number-integer': 										// strict validation
				$regex = '/'.$rxs.'(\-)?[0-9]+?'.$rxe.'/'; 				// before was: '/([0-9\-])+/' but was not good enough as a strict rule
				break;
			case 'number-decimal': 										// strict validation ; must match also integer values ; {{{SYNC-DETECT-PURE-NUMERIC-INT-OR-DECIMAL-VALUES}}}
				$regex = '/'.$rxs.'(\-)?[0-9]+(\.[0-9]+)?'.$rxe.'/'; 	// before was: '/([0-9\-\.])+$/' but was not good enough as a strict rule
				break;
			//--
			case 'number-list-integer': 								// flexible validation (since this is a list, it may contain any numbers and ;)
				$regex = '/'.$rxs.'([0-9\-\;])+'.$rxe.'/'; 				// example: 1;2;30
				break;
			case 'number-list-decimal': 								// flexible validation (since this is a list, it may contain any numbers and ;) ; must match also integer list values
				$regex = '/'.$rxs.'([0-9\-\.\;])+'.$rxe.'/'; 			// example: 1.0;2;30.44
				break;
			//--
			case 'ipv4':
				$regex = '/'.$rxs.'([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}'.$rxe.'/';
				break;
			case 'ipv6':
				$regex = '/'.$rxs.'s*((([0-9A-Fa-f]{1,4}\:){7}([0-9A-Fa-f]{1,4}|\:))|(([0-9A-Fa-f]{1,4}\:){6}(\:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|\:))|(([0-9A-Fa-f]{1,4}\:){5}(((\:[0-9A-Fa-f]{1,4}){1,2})|\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3})|\:))|(([0-9A-Fa-f]{1,4}\:){4}(((\:[0-9A-Fa-f]{1,4}){1,3})|((\:[0-9A-Fa-f]{1,4})?\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|\:))|(([0-9A-Fa-f]{1,4}\:){3}(((\:[0-9A-Fa-f]{1,4}){1,4})|((\:[0-9A-Fa-f]{1,4}){0,2}\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|\:))|(([0-9A-Fa-f]{1,4}\:){2}(((\:[0-9A-Fa-f]{1,4}){1,5})|((\:[0-9A-Fa-f]{1,4}){0,3}\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|\:))|(([0-9A-Fa-f]{1,4}\:){1}(((\:[0-9A-Fa-f]{1,4}){1,6})|((\:[0-9A-Fa-f]{1,4}){0,4}\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|\:))|(\:(((\:[0-9A-Fa-f]{1,4}){1,7})|((\:[0-9A-Fa-f]{1,4}){0,5}\:((25[0-5]|2[0-4]d|1dd|[1-9]?d)(.(25[0-5]|2[0-4]d|1dd|[1-9]?d)){3}))|\:)))(%.+)?s*'.$rxe.'/';
				break;
			case 'macaddr':
				$regex = '/'.$rxs.'([0-9a-fA-F][0-9a-fA-F]\:){5}([0-9a-fA-F][0-9a-fA-F])|([0-9a-fA-F][0-9a-fA-F]\-){5}([0-9a-fA-F][0-9a-fA-F])'.$rxe.'/';
				break;
			//--
			case 'url':
				$regex = '/'.$rxs.'(http|https)(:\/\/)([^\s<>\(\)\|]*)'.$rxe.'/'; // url recognition in a text / html code :: fixed in html <>
				break;
			case 'domain':
				$regex = '/'.$rxs.'([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}'.$rxe.'/'; // internet (subdomain.)domain.name
				break;
			case 'email':
				$regex = '/'.$rxs.'([_a-z0-9\-\.]){1,63}@'.'[a-z0-9\-\.]{3,63}'.$rxe.'/'; // internet email@(subdomain.)domain.name :: [_a-z0-9\-\.]*+@+[_a-z0-9\-\.]*
				break;
			//--
			case 'fax':
				$regex = '/'.$rxs.'(~)([0-9\-\+\.\(\)][^~]*)(~)'.$rxe.'/'; // fax number recognition in a text / html code (must stay between ~)
				break;
			//--
			//== #ERROR: INVALID
			//--
			default:
				Smart::raise_error(
					'INVALID mode in function '.__CLASS__.'::'.__FUNCTION__.'(): '.$y_mode,
					'Validations Internal ERROR' // msg to display
				);
				die(''); 	// just in case
				return '+'; // just in case
			//--
			//== #END
			//--
		} //end switch
		//--
		return (string) $regex;
		//--
	} //END FUNCTION
	//=================================================================


	//=================================================================
	/**
	 * Regex Segment to build Regex Expressions (Internal Use Only)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	ENUM 	$y_mode 			:: The Regex mode to be returned (see in function)
	 *
	 * @return 	STRING						:: The Regex expression or empty if invalid mode is provided
	 */
	public static function regex_stringvalidation_segment($y_mode) {
		//--
		switch((string)strtolower((string)$y_mode)) { // WARNING: Never use class modifiers like [:print:] with /u modifier as it fails with some versions of PHP / Regex / PCRE
			//--
			//== #INTERNAL USE ONLY
			//-- {{{SYNC-HTML-TAGS-REGEX}}} ; expression delimiter must be # (not / or others ...)
			case 'tag-name':
				$regex = 'a-z0-9\-\:'; // regex expr: the allowed characters in tag names (just for open tags ... the end tags will add / and space
				break;
			case 'tag-start':
				$regex = '\<\s*?'; // regex expr: tag start
				break;
			case 'tag-end-start':
				$regex = '\<\s*?/\s*?'; // regex expr: end tag start
				break;
			case 'tag-simple-end':
				$regex = '\s*?\>'; // regex expr: tag end without attributes
				break;
			case 'tag-complex-end':
				$regex = '\s+[^>]*?\>'; // regex expr: tag end with attributes or / (it needs at least one space after tag name)
				break;
			//--
			//== #ERROR: INVALID
			//--
			default:
				$regex = '+';
				Smart::raise_error(
					'INVALID mode in function '.__CLASS__.'::'.__FUNCTION__.'(): '.$y_mode,
					'Segment Validations Internal ERROR' // msg to display
				);
				die(''); // just in case
				return '';
			//--
			//== #END
			//--
		} //end switch
		//--
		return (string) $regex;
		//--
	} //END FUNCTION
	//=================================================================


	//=================================================================
	/**
	 * Validate a string using SmartValidator::regex_stringvalidation_expression(), Full Match
	 *
	 * @param 	STRING		$y_string			:: The String to be validated
	 * @param 	ENUM 		$y_mode 			:: The Regex mode to use for validation ; see reference for SmartValidator::regex_stringvalidation_expression()
	 *
	 * @return 	BOOLEAN							:: TRUE if validated by regex ; FALSE if not validated
	 */
	public static function validate_string($y_string, $y_mode) {
		//--
		$regex = self::regex_stringvalidation_expression((string)$y_mode, 'full');
		//--
		if(preg_match((string)$regex, (string)$y_string)) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//=================================================================


	//================================================================
	/**
	 * Validate if a string or number is Integer or Decimal (positive / negative)
	 * This will not check if the number is finite or overflowing !!
	 *
	 * @param 	STRING		$val				:: The string or number to be validated
	 * @return 	BOOL							:: TRUE if Integer or Decimal (positive / negative) ; FALSE if not
	 */
	public static function validate_numeric_integer_or_decimal_values($val) { // {{{SYNC-DETECT-PURE-NUMERIC-INT-OR-DECIMAL-VALUES}}}
		//--
		$val = (string) $val; // do not use TRIM as it may strip out null or weird characters that may inject security issues if not trimmed outside (MUST VALIDATE THE REAL STRING !!!)
		//--
		$regex_decimal = (string) self::regex_stringvalidation_expression('number-decimal', 'full');
		//--
		if(((string)$val != '') AND (is_numeric($val)) AND (preg_match((string)$regex_decimal, (string)$val))) { // detect numbers: 0..9 - .
			return true; // VALID
		} else {
			return false; // NOT VALID
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate if a string or number is VALID numeric (finite, not overflowing, match precision, not Expressions like 1.3e3 ; may contain only -0123456789.)
	 *
	 * @param 	STRING		$val				:: The string or number to be validated
	 * @return 	BOOL							:: TRUE if match condition ; FALSE if not
	 */
	public static function validate_numeric_pure_valid_values($val) {
		//--
		if((self::validate_numeric_integer_or_decimal_values($val)) AND (!is_nan($val)) AND (!is_infinite($val)) AND ((string)$val == (string)(float)$val)) {
			return true;
		} else {
			return false;
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate if a string or number is VALID integer (finite, not overflowing, match precision and max/min integer, not Expressions like 1.3e3 ; may contain only -0123456789)
	 *
	 * @param 	STRING		$val				:: The string or number to be validated
	 * @return 	BOOL							:: TRUE if match condition ; FALSE if not
	 */
	public static function validate_integer_pure_valid_values($val) {
		//--
		if((self::validate_numeric_pure_valid_values($val)) AND ($val >= PHP_INT_MIN) AND ($val <= PHP_INT_MAX)) {
			return true;
		} else {
			return false;
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect HTML or XML code contain tags (if does not contain tags is not html or xml code ...)
	 *
	 * @param STRING 	$y_html_or_xml_code		:: The String to be tested
	 *
	 * @return BOOLEAN 							:: TRUE (if XML or HTML tags are detected) or FALSE if not
	 */
	public static function validate_html_or_xml_code($y_html_or_xml_code) {
		//-- enforce string
		$y_html_or_xml_code = (string) trim((string)$y_html_or_xml_code);
		//-- regex expr
		$expr_tag_name 			= self::regex_stringvalidation_segment('tag-name');
		$expr_tag_start 		= self::regex_stringvalidation_segment('tag-start');
		$expr_tag_end_start 	= self::regex_stringvalidation_segment('tag-end-start');
		$expr_tag_simple_end 	= self::regex_stringvalidation_segment('tag-simple-end');
		$expr_tag_complex_end 	= self::regex_stringvalidation_segment('tag-complex-end');
		//-- {{{SYNC-HTML-TAGS-REGEX}}}
		$regex_part_tag_name 	= '['.$expr_tag_name.']+'; // regex syntax: tag name def
		//-- build regex syntax
		$regex_match_tag = '#'.$expr_tag_start.$regex_part_tag_name.$expr_tag_simple_end.'|'.$expr_tag_start.$regex_part_tag_name.$expr_tag_complex_end.'#si';
		//-- evaluate
		//if(((string)$y_html_or_xml_code != '') AND (strpos((string)$y_html_or_xml_code, '<') !== false) AND (strpos((string)$y_html_or_xml_code, '>') !== false) AND ((string)$y_html_or_xml_code != (string)strip_tags((string)$y_html_or_xml_code))) {
		if(((string)$y_html_or_xml_code != '') AND (strpos((string)$y_html_or_xml_code, '<') !== false) AND (strpos((string)$y_html_or_xml_code, '>') !== false) AND (preg_match((string)$regex_match_tag, (string)$y_html_or_xml_code))) {
			$out = true;
		} else {
			$out = false;
		} //end if else
		//-- return
		return (bool) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ Validate an IP Address
	/**
	 * Validate and Filter an IP Address
	 *
	 * @param 	STRING		$ip					:: The IP Address to be validated
	 *
	 * @return 	STRING							:: The IP address if valid (as string) or an empty string if Invalid
	 */
	public static function validate_filter_ip_address($ip) {
		//-- {{{SYNC-IP-VALIDATE}}}
		$ip = filter_var((string)$ip, FILTER_VALIDATE_IP); // if fail will return FALSE
		if($ip === false) {
			$ip = '';
		} //end if
		//--
		return (string) Smart::ip_addr_compress((string)$ip);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate The Mime Disposition
	 *
	 * @param 	STRING		$y_disp				:: The Mime Disposition ; can be: inline / attachment / attachment; filename="somefile.pdf"
	 * @return 	STRING							:: The validated Mime Disposition
	 */
	public static function validate_mime_disposition($y_disp) {
		//--
		$y_disp = (string) trim((string)$y_disp);
		//--
		if((string)$y_disp == '') {
			return '';
		} //end if
		//--
		if(preg_match('/^[[:print:]]+$/', $y_disp)) { // mime types are only ISO-8859-1
			$disp = $y_disp;
		} else {
			$disp = '';
		} //end if
		//--
		return (string) $disp;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Validate The Mime Type
	 *
	 * @param 	STRING		$y_type				:: The Mime Type ; Ex: image/png
	 * @return 	STRING							:: The validated Mime Type
	 */
	public static function validate_mime_type($y_type) {
		//--
		$y_type = (string) strtolower((string)trim((string)$y_type));
		//--
		if((string)$y_type == '') {
			return '';
		} //end if
		//--
		if(preg_match('/^[[:graph:]]+$/', $y_type)) { // mime types are only ISO-8859-1
			$type = $y_type;
		} else {
			$type = '';
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=================================================================================
//================================================================================= CLASS END
//=================================================================================



//=================================================================================
//================================================================================= CLASS START
//=================================================================================


/**
 * Class: SmartParser - Provides misc parsing methods.
 *
 * <code>
 * // Usage example:
 * SmartParser::some_method_of_this_class(...);
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 *
 * @access      PUBLIC
 * @depends     classes: Smart, SmartUnicode, SmartValidator ; constants: SMART_FRAMEWORK_ERR_PCRE_SETTINGS
 * @version     v.20250107
 * @package     @Core:Extra
 *
 */
final class SmartParser {

	// ::


	//================================================================
	public static function extract_base_domain_from_domain(?string $domain) : string {
		//--
		$domain = (string) strtolower((string)trim((string)$domain));
		if((string)$domain == '') {
			return '';
		} //end if
		//--
		if(
			preg_match('/^[0-9\.]+$/', (string)$domain) // IPv4
			OR
			(strpos((string)$domain, ':') !== false)
		) { // IPv6
			if((string)trim((string)SmartValidator::validate_filter_ip_address((string)$domain)) != '') { // if IP address
				return (string) $domain; // domain is IP v4/v6, return as it is
			} //end if
		} //end if
		//--
		if(strpos((string)$domain, '.') === false) { // ex: localhost
			return (string) $domain; // domain cannot be split by .
		} //end if
		//-- ex: subdomain.domain.ext or subdomain.domain
		$domain = (array) explode('.', (string)$domain);
		$domain = (array) array_reverse((array)$domain);
		return (string) ($domain[1] ?? null).'.'.($domain[0] ?? null); // PHP8 OK, as it tests if . exists
		//--
	} //END fUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect URL links in a text string
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 *
	 * @return 	ARRAY						:: A non-associative array with the URL links detected in the string
	 */
	public static function get_arr_urls(?string $string) : array {
		//--
		$string = (string) $string;
		$expr = SmartValidator::regex_stringvalidation_expression('url', 'partial');
		$regex = $expr.'iu'; // insensitive, with /u modifier for unicode strings
		$arr = array();
		$pcre = preg_match_all($regex, $string, $arr);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) (is_array($arr[0]) ? $arr[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Replace URL in a text string with HTML links <a href="(DetectedURL)" target="{target}">[DetectedURL]</a>
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 * @param 	STRING	$ytarget			:: URL target ; default is '_blank' ; can be: '_self' or a specific window name: 'myWindow' ...
	 * @param 	STRING	$ypict				:: The image path to display as link ; default is blank: ''
	 * @param	INTEGER $y_lentrim			:: The length of the URL to be displayed into [DetectedURL] (used only if no image has been provided)
	 *
	 * @return 	STRING						:: The HTML processed text with URLs replaced with real tags
	 */
	public static function text_urls(?string $string, ?string $ytarget='_blank', ?string $ypict='', ?int $y_lentrim=100) : string {
		//--
		$string = (string) $string;
		$expr = (string) SmartValidator::regex_stringvalidation_expression('url', 'partial');
		$regex = (string) $expr.'iu'; //insensitive, with /u modifier for unicode strings
		//--
		return (string) preg_replace_callback(
			(string) $regex,
			function($matches) use ($ytarget, $ypict, $y_lentrim) {
				if((string)trim((string)$ypict) == '') {
					return '<a title="URL" data-parse-type="url" href="'.Smart::escape_html((string)$matches[0]).'" target="'.Smart::escape_html((string)$ytarget).'">'.Smart::escape_html((string)Smart::text_cut_by_limit((string)$matches[0], (int)$y_lentrim)).'</a>';
				} else {
					return '<a title="URL" data-parse-type="url" href="'.Smart::escape_html((string)$matches[0]).'" target="'.Smart::escape_html((string)$ytarget).'"><img class="url-recognition" src="'.Smart::escape_html((string)trim((string)$ypict)).'" alt="'.Smart::escape_html((string)$matches[0]).'"></a>&nbsp;'.Smart::escape_html((string)Smart::text_cut_by_limit((string)$matches[0], (int)$y_lentrim)).'<br>';
				} //end if else
			},
			(string) $string
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect EMAIL addresses in a text string
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 *
	 * @return 	ARRAY						:: A non-associative array with the EMAIL addresses detected in the string
	 */
	public static function get_arr_emails(?string $string) : array {
		//--
		$string = (string) $string;
		$expr = SmartValidator::regex_stringvalidation_expression('email', 'partial');
		$regex = $expr.'iu'; //insensitive, with /u modifier for unicode strings
		$arr = array();
		$pcre = preg_match_all($regex, $string, $arr);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) (is_array($arr[0]) ? $arr[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Replace EMAIL addresses in a text string with HTML links <a href="(emailAddr)" target="{target}">[emailAddr]</a>
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 * @param 	STRING 	$yaction			:: Action to append the email link to ; Default is: 'mailto:' but can be for example: 'script.php?action=email&addr='
	 * @param 	STRING	$ytarget			:: URL target ; default is '_blank' ; can be: '_self' or a specific window name: 'myWindow' ...
	 * @param	INTEGER $y_lentrim			:: The length of the URL to be displayed into [DetectedURL] (used only if no image has been provided)
	 *
	 * @return 	STRING						:: The HTML processed text with EMAIL addresses replaced with real tags as links
	 */
	public static function text_emails(?string $string, ?string $yaction='mailto:', ?string $ytarget='', ?int $y_lentrim=100) : string {
		//--
		$string = (string) $string;
		$expr = (string) SmartValidator::regex_stringvalidation_expression('email', 'partial');
		$regex = (string) $expr.'iu'; //insensitive, with /u modifier for unicode strings
		$string = (string) preg_replace_callback(
			(string) $regex,
			function($matches) use ($yaction, $ytarget, $y_lentrim) {
				return '<a title="eMail" data-parse-type="email" href="'.Smart::escape_html((string)$yaction.Smart::escape_url((string)trim((string)$matches[0]))).'" target="'.Smart::escape_html((string)$ytarget).'">'.Smart::escape_html((string)Smart::text_cut_by_limit((string)$matches[0], (int)$y_lentrim)).'</a>';
			},
			(string) $string
		);
		//--
		return (string) $string;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect FAX numbers in a text string
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 *
	 * @return 	ARRAY						:: A non-associative array with the FAX numbers detected in the string
	 */
	public static function get_arr_faxnums(?string $string) : array {
		//--
		$string = (string) $string;
		$expr = SmartValidator::regex_stringvalidation_expression('fax', 'partial');
		$regex = $expr.'iu'; //insensitive, with /u modifier for unicode strings
		$arr = array();
		$pcre = preg_match_all($regex, $string, $arr);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) (is_array($arr[0]) ? $arr[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Replace FAX numbers in a text string with HTML links <a href="(faxNum)" target="{target}">[faxNum]</a>
	 *
	 * @param 	STRING 	$string 			:: The text string to be processed
	 * @param 	STRING 	$yaction			:: Action to append the fax-num link to ; Default is: 'efax:' but can be for example: 'script.php?action=fax&number='
	 * @param 	STRING	$ytarget			:: URL target ; default is '_blank' ; can be: '_self' or a specific window name: 'myWindow' ...
	 * @param	INTEGER $y_lentrim			:: The length of the URL to be displayed into [DetectedURL] (used only if no image has been provided)
	 *
	 * @return 	STRING						:: The HTML processed text with FAX numbers replaced with real tags as links
	 */
	public static function text_faxnums(?string $string, ?string $yaction='efax:', ?string $ytarget='_blank', ?int $y_lentrim=100) : string {
		//--
		$string = (string) $string;
		$expr = (string) SmartValidator::regex_stringvalidation_expression('fax', 'partial');
		$regex = (string) $expr.'iu'; //insensitive, with /u modifier for unicode strings
		$string = (string) preg_replace_callback(
			(string) $regex,
			function($matches) use ($yaction, $ytarget, $y_lentrim) {
				return '<a title="eFax" data-parse-type="faxnum" href="'.Smart::escape_html((string)$yaction.Smart::escape_url((string)trim((string)$matches[2]))).'" target="'.Smart::escape_html((string)$ytarget).'">'.Smart::escape_html((string)Smart::text_cut_by_limit((string)$matches[2], (int)$y_lentrim)).'</a>';
			},
			(string) $string
		);
		//--
		return (string) $string;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=================================================================================
//================================================================================= CLASS END
//=================================================================================


//end of php code
