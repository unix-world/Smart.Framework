<?php
// [LIB - Smart.Framework / Unicode Strings]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Unicode Strings
// DEPENDS-PHP: 7.3 or later
// DEPENDS-EXT: MBString, XML
//======================================================

//-- other locales than C may break many things ; Example: 3.5 may become become 3,5 or dates may become uncompatible as format in the overall context ; starting from date() to SQL escapes all will be affected with unpredictable results when working in a mixed locales unicode context other than C
if((string)setlocale(LC_ALL, 0) != 'C') { // {{{SYNC-LOCALES-CHECK}}}
	@http_response_code(500);
	die('ERROR: The PHP locales must be reset to C (default) to support the standard UTF-8 context in Smart.Framework / Unicode');
} //end if
//-- require the PHP MBString Extension (this is the fastest and safest Unicode library to use in PHP)
if(!function_exists('mb_stripos')) {
	@http_response_code(500);
	die('ERROR: The PHP MBString Extension is required for Unicode support into Smart.Framework / Unicode');
} //end if
if((!function_exists('utf8_decode')) OR (!function_exists('utf8_encode'))) {
	@http_response_code(500);
	die('ERROR: The PHP UTF8-Decode/Encode (from XML Extension) is required for Smart.Framework / Unicode');
} //end if
//-- require UTF-8 Character Set
if(defined('SMART_FRAMEWORK_CHARSET')) {
	if((string)SMART_FRAMEWORK_CHARSET != 'UTF-8') {
		@http_response_code(500);
		die('Smart-Framework Character Set must be set as: UTF-8');
	} //end if
	if((string)SMART_FRAMEWORK_CHARSET != (string)strtoupper((string)ini_get('default_charset'))) {
		@http_response_code(500);
		die('PHP Internal Character Set must be set as: `'.SMART_FRAMEWORK_CHARSET.'` but it set to: `'.strtoupper((string)ini_get('default_charset')).'`');
	} //end if
	if((string)SMART_FRAMEWORK_CHARSET != (string)strtoupper((string)mb_internal_encoding())) {
		@http_response_code(500);
		die('MBString Internal Character Set must be set as: `'.SMART_FRAMEWORK_CHARSET.'` but it set to: `'.strtoupper((string)mb_internal_encoding()).'`');
	} //end if
} else {
	@http_response_code(500);
	die('The SMART_FRAMEWORK_CHARSET must be set ...');
} //end if
//-- the MBString replacement character must be ? to be compatible with utf8_decode()
if(mb_substitute_character() !== 63) {
	@http_response_code(500);
	die('MBString Internal Substitute Character must be set to 63(?) but is set to: '.mb_substitute_character());
} //end if
//--

// [REGEX-SAFE-OK]

//================================================================
// SPECIAL REGEX CHARACTERS:  . \ + * ? [ ^ ] $ ( ) { } = ! < > | : -
// WARNING: The Regex u modifier is incompatible with Perl Regex and must be used carefully ...
//		it can generate strange matches that can differ from a PCRE version to another if PHP is compiled or not with mbregex !!!
//		Example: '/[^\\t\\r\\n[:print:]]/u' works as expected if PHP is compiled with mbregex but completely different if not !
//================================================================
//================================================================


//=================================================================================
//================================================================================= CLASS START
//=================================================================================


/**
 * Class: SmartUnicode - provides the string util functions to work safe with Unicode (Multibyte) Strings / Characters.
 *
 * Compatbile with: UTF-8 (Unicode) - all latin based character sets ; ISO-8859-1 (latin base) - usa, uk, irish, italian, sweden, norway, denmark, spanish, portugal, dutch ; ISO-8859-* (latin extended) - german, austrian, finland, czech, slovak, poland, hungary, romania, slovenia, croatia, serbia, bosnia, albania
 *
 * Take a look at the replacement table below.
 *
 * <code>
 *
 * // Usage example:
 * SmartUnicode::some_method_of_this_class(...);
 *
 *  //-----------------------------------------------------------------------------------------------------
 *  //-----------------------------------------------------------------------------------------------------
 *  // SAFE / MultiByte functions Reference with Replacements (Smart.Framework UTF-8)
 *  // MORE INFO AT: http://www.phpwact.org/php/i18n/utf-8
 *  //-----------------------------------------------------------------------------------------------------
 *  // FUNCTION NAME                BETTER / SAFER REPLACEMENT              STATUS      NOTICE
 *  //-----------------------------------------------------------------------------------------------------
 *  //--
 *  //(int)                         Smart::format_number_int()              [ok]        The replacement function have a second parameter to allow also unsigned integers
 *  //number_format()               Smart::format_number_dec()              [ok]        It is easier to use and rely on framework
 *  //htmlspecialchars()            Smart::escape_html()                    [ok]        The replacement function will take in count if unicode strings or not are used
 *  //--
 *  //mail()                        SmartUnicode::mailsend()                [ok]        The PHP mail() is not unicode safe
 *  //--
 *  //split() / str_split()         explode()                               [ok]        Use the explode() function ; avoid to use split() or str_split() because they are not binary safe an can break unicode strings
 *  //join()                        implode()                               [ok]        The join() is deprecated and alias to implode()
 *  //substr_replace()              str_replace()                           [ok]        It is not certified to be safe 100% with unicode strings ; try to use the the replacement function
 *  //str_ireplace()                * str_ireplace()                        [ok!]       Will fail if try to replace unicode accented characters if case differs (lower vs. upper)
 *  //--
 *  //substr_count()                SmartUnicode::substr_count()            [ok]        the PHP substr_count() is not unicode safe
 *  //strlen()                      SmartUnicode::str_len() / strlen()      [ok!]       the PHP strlen() is not unicode safe ; but for counting bytes in a string use always the strlen() ; for counting characters in a string always use SmartUnicode::str_len() in an unicode environment
 *  //substr()                      SmartUnicode::sub_str()                 [ok]        the PHP substr() is not unicode safe
 *  //--
 *  //strstr()                      SmartUnicode::str_str()                 [ok]        the PHP strstr() is not unicode safe
 *  //stristr()                     SmartUnicode::stri_str()                [ok]        the PHP stristr() is not unicode safe
 *  //--
 *  //strpos()                      SmartUnicode::str_pos()                 [ok]        the PHP strpos() is not unicode safe
 *  //stripos()                     SmartUnicode::str_ipos()                [ok]        the PHP stripos() is not unicode safe
 *  //strrpos()                     SmartUnicode::str_rpos()                [ok]        the PHP strrpos() is not unicode safe
 *  //strripos()                    SmartUnicode::str_ripos()               [ok]        the PHP strripos() is not unicode safe
 *  //--
 *  //strtolower()                  SmartUnicode::str_tolower()             [ok]        the PHP strtolower() is not unicode safe and will not make lower case the accented characters
 *  //strtoupper()                  SmartUnicode::str_toupper()             [ok]        the PHP strtoupper() is not unicode safe and will not make upper case the accented characters
 *  //--
 *  //utf8_decode()                 SmartUnicode::utf8_to_iso()             [ok]        the PHP utf8_decode() breaks strings that are used in unicode environments thus the strings need to be re-encoded ; if not re-encoded back to unicode the regex \u will fail in strange modes ...
 *  //utf8_encode()                 -                                       [!!]        there is a risk to double encode the string and break it !! ; Fix: avoid do utf8 encode on an empty string (some PHP versions have a bug with exhaust memory)
 *  //wordwrap()                    SmartUnicode::word_wrap()               [ok]        the PHP wordwrap() is not unicode safe
 *  //strip_tags()                  Smart::striptags()                      [ok+]       the PHP strip_tags() will not replace some extra things like &nbsp; and much other html entities
 *  //--
 *  //printf()                      * printf()                              [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //sprintf()                     * sprintf()                             [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //vsprintf()                    * vsprintf()                            [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //strcasecmp()                  * strcasecmp()                          [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //strcspn()                     * strcspn()                             [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //strspn()                      * strspn()                              [!+]        Will not take care of real multibyte string length and may return unexpected results
 *  //--
 *  //strtr()                                                               [!!]        Use only with non-unicode characters, will break the unicode strings if unicode characters are multibyte
 *  //strrev()                                                              [!!]        Use only with non-unicode characters, will break the unicode strings if unicode characters are multibyte
 *  //--
 *  //chunk_split()                 -                                       [!!-]       This really breaks unicode strings ... Use only with non-unicode characters, will break the unicode strings if unicode characters are multibyte
 *  //--
 *  //-----------------------------------------------------------------------------------------------------
 *  //---------------   DEPRECATED Functions, AVOID TO USE THEM ...         ---------------
 *  //---------------   They are not binary safe and will be OFF since PHP7 ---------------
 *  // Replacement hint: ereg("^(5|6)$", 'some value') [=] preg_match("/^(5|6)$/", 'some value')
 *  //-----------------------------------------------------------------------------------------------------
 *  //ereg()                        preg_match*()                           [ok]        Will work after regex pattern conversion ; If String is unicode, then using \u regex modifier is required
 *  //eregi()                       preg_match*()                           [ok]        Will work after regex pattern conversion ; If String is unicode, then using \u regex modifier is required
 *  //ereg_replace()                preg_replace*()                         [ok]        Will work after regex pattern conversion ; If String is unicode, then using \u regex modifier is required
 *  //eregi_replace()               preg_replace*()                         [ok]        Will work after regex pattern conversion ; If String is unicode, then using \u regex modifier is required
 *  //-----------------------------------------------------------------------------------------------------
 *  //-----------------------------------------------------------------------------------------------------
 *
 * </code>
 *
 * @usage       static object: Class::method() - This class provides only STATIC methods
 * @hints       You must make always a difference for what you are goint to do with a string. If you need bytes length, then use strlen() with all strings, includding unicode ! If you need instead the number of characters in a string, then use SmartUnicode::str_len() for all variables / strings you know (or you just suppose) that are unicode.
 *
 * @access      PUBLIC
 * @depends     extensions: PHP MBString, PHP XML ; constants: SMART_FRAMEWORK_CHARSET
 * @version     v.20210903
 * @package     @Core
 *
 */
final class SmartUnicode {

	// ::

	public const ACCENTED_CHARS = [ // Unicode Accented Characters Table
		'á' => 'a',
		'â' => 'a',
		'ã' => 'a',
		'ä' => 'a',
		'å' => 'a',
		'ā' => 'a',
		'ă' => 'a',
		'ą' => 'a',
		'Á' => 'A',
		'Â' => 'A',
		'Ã' => 'A',
		'Ä' => 'A',
		'Å' => 'A',
		'Ā' => 'A',
		'Ă' => 'A',
		'Ą' => 'A',
		'ć' => 'c',
		'ĉ' => 'c',
		'č' => 'c',
		'ç' => 'c',
		'Ć' => 'C',
		'Ĉ' => 'C',
		'Č' => 'C',
		'Ç' => 'C',
		'ď' => 'd',
		'Ď' => 'D',
		'è' => 'e',
		'é' => 'e',
		'ê' => 'e',
		'ë' => 'e',
		'ē' => 'e',
		'ĕ' => 'e',
		'ė' => 'e',
		'ě' => 'e',
		'ę' => 'e',
		'È' => 'E',
		'É' => 'E',
		'Ê' => 'E',
		'Ë' => 'E',
		'Ē' => 'E',
		'Ĕ' => 'E',
		'Ė' => 'E',
		'Ě' => 'E',
		'Ę' => 'E',
		'ĝ' => 'g',
		'ģ' => 'g',
		'Ĝ' => 'G',
		'Ģ' => 'G',
		'ĥ' => 'h',
		'ħ' => 'h',
		'Ĥ' => 'H',
		'Ħ' => 'H',
		'ì' => 'i',
		'í' => 'i',
		'î' => 'i',
		'ï' => 'i',
		'ĩ' => 'i',
		'ī' => 'i',
		'ĭ' => 'i',
		'ȉ' => 'i',
		'ȋ' => 'i',
		'į' => 'i',
		'Ì' => 'I',
		'Í' => 'I',
		'Î' => 'I',
		'Ï' => 'I',
		'Ĩ' => 'I',
		'Ī' => 'I',
		'Ĭ' => 'I',
		'Ȉ' => 'I',
		'Ȋ' => 'I',
		'Į' => 'I',
		'ĳ' => 'j',
		'ĵ' => 'j',
		'Ĳ' => 'J',
		'Ĵ' => 'J',
		'ķ' => 'k',
		'Ķ' => 'K',
		'ĺ' => 'l',
		'ļ' => 'l',
		'ľ' => 'l',
		'ł' => 'l',
		'Ĺ' => 'L',
		'Ļ' => 'L',
		'Ľ' => 'L',
		'Ł' => 'L',
		'ñ' => 'n',
		'ń' => 'n',
		'ņ' => 'n',
		'ň' => 'n',
		'Ñ' => 'N',
		'Ń' => 'N',
		'Ņ' => 'N',
		'Ň' => 'N',
		'ò' => 'o',
		'ó' => 'o',
		'ô' => 'o',
		'õ' => 'o',
		'ö' => 'o',
		'ō' => 'o',
		'ŏ' => 'o',
		'ő' => 'o',
		'ø' => 'o',
		'œ' => 'o',
		'Ò' => 'O',
		'Ó' => 'O',
		'Ô' => 'O',
		'Õ' => 'O',
		'Ö' => 'O',
		'Ō' => 'O',
		'Ŏ' => 'O',
		'Ő' => 'O',
		'Ø' => 'O',
		'Œ' => 'O',
		'ŕ' => 'r',
		'ŗ' => 'r',
		'ř' => 'r',
		'Ŕ' => 'R',
		'Ŗ' => 'R',
		'Ř' => 'R',
		'ș' => 's',
		'ş' => 's',
		'š' => 's',
		'ś' => 's',
		'ŝ' => 's',
		'ß' => 'ss', // s (prev.)
		'Ș' => 'S',
		'Ş' => 'S',
		'Š' => 'S',
		'Ś' => 'S',
		'Ŝ' => 'S',
		'ț' => 't',
		'ţ' => 't',
		'ť' => 't',
		'Ț' => 'T',
		'Ţ' => 'T',
		'Ť' => 'T',
		'ù' => 'u',
		'ú' => 'u',
		'û' => 'u',
		'ü' => 'u',
		'ũ' => 'u',
		'ū' => 'u',
		'ŭ' => 'u',
		'ů' => 'u',
		'ű' => 'u',
		'ų' => 'u',
		'Ù' => 'U',
		'Ú' => 'U',
		'Û' => 'U',
		'Ü' => 'U',
		'Ũ' => 'U',
		'Ū' => 'U',
		'Ŭ' => 'U',
		'Ů' => 'U',
		'Ű' => 'U',
		'Ų' => 'U',
		'ŵ' => 'w',
		'Ŵ' => 'W',
		'ẏ' => 'y',
		'ỳ' => 'y',
		'ŷ' => 'y',
		'ÿ' => 'y',
		'ý' => 'y',
		'Ẏ' => 'Y',
		'Ỳ' => 'Y',
		'Ŷ' => 'Y',
		'Ÿ' => 'Y',
		'Ý' => 'Y',
		'ź' => 'z',
		'ż' => 'z',
		'ž' => 'z',
		'Ź' => 'Z',
		'Ż' => 'Z',
		'Ž' => 'Z'
	];

	public const ACCENTED_HTML_ENTITIES = [ // Unicode Accented HTML-Entities Table
		'á' => '&#225;',
		'â' => '&#226;',
		'ã' => '&#227;',
		'ä' => '&#228;',
		'å' => '&#229;',
		'ā' => '&#257;',
		'ă' => '&#259;',
		'ą' => '&#261;',
		'Á' => '&#193;',
		'Â' => '&#194;',
		'Ã' => '&#195;',
		'Ä' => '&#196;',
		'Å' => '&#197;',
		'Ā' => '&#256;',
		'Ă' => '&#258;',
		'Ą' => '&#260;',
		'ć' => '&#263;',
		'ĉ' => '&#265;',
		'č' => '&#269;',
		'ç' => '&#231;',
		'Ć' => '&#262;',
		'Ĉ' => '&#264;',
		'Č' => '&#268;',
		'Ç' => '&#199;',
		'ď' => '&#271;',
		'Ď' => '&#270;',
		'è' => '&#232;',
		'é' => '&#233;',
		'ê' => '&#234;',
		'ë' => '&#235;',
		'ē' => '&#275;',
		'ĕ' => '&#277;',
		'ė' => '&#279;',
		'ě' => '&#283;',
		'ę' => '&#281;',
		'È' => '&#200;',
		'É' => '&#201;',
		'Ê' => '&#202;',
		'Ë' => '&#203;',
		'Ē' => '&#274;',
		'Ĕ' => '&#276;',
		'Ė' => '&#278;',
		'Ě' => '&#282;',
		'Ę' => '&#280;',
		'ĝ' => '&#285;',
		'ģ' => '&#291;',
		'Ĝ' => '&#284;',
		'Ģ' => '&#290;',
		'ĥ' => '&#293;',
		'ħ' => '&#295;',
		'Ĥ' => '&#292;',
		'Ħ' => '&#294;',
		'ì' => '&#236;',
		'í' => '&#237;',
		'î' => '&#238;',
		'ï' => '&#239;',
		'ĩ' => '&#297;',
		'ī' => '&#299;',
		'ĭ' => '&#301;',
		'ȉ' => '&#521;',
		'ȋ' => '&#523;',
		'į' => '&#303;',
		'Ì' => '&#204;',
		'Í' => '&#205;',
		'Î' => '&#206;',
		'Ï' => '&#207;',
		'Ĩ' => '&#296;',
		'Ī' => '&#298;',
		'Ĭ' => '&#300;',
		'Ȉ' => '&#520;',
		'Ȋ' => '&#522;',
		'Į' => '&#302;',
		'ĳ' => '&#307;',
		'ĵ' => '&#309;',
		'Ĳ' => '&#306;',
		'Ĵ' => '&#308;',
		'ķ' => '&#311;',
		'Ķ' => '&#310;',
		'ĺ' => '&#314;',
		'ļ' => '&#316;',
		'ľ' => '&#318;',
		'ł' => '&#322;',
		'Ĺ' => '&#313;',
		'Ļ' => '&#315;',
		'Ľ' => '&#317;',
		'Ł' => '&#321;',
		'ñ' => '&#241;',
		'ń' => '&#324;',
		'ņ' => '&#326;',
		'ň' => '&#328;',
		'Ñ' => '&#209;',
		'Ń' => '&#323;',
		'Ņ' => '&#325;',
		'Ň' => '&#327;',
		'ò' => '&#242;',
		'ó' => '&#243;',
		'ô' => '&#244;',
		'õ' => '&#245;',
		'ö' => '&#246;',
		'ō' => '&#333;',
		'ŏ' => '&#335;',
		'ő' => '&#337;',
		'ø' => '&#248;',
		'œ' => '&#339;',
		'Ò' => '&#210;',
		'Ó' => '&#211;',
		'Ô' => '&#212;',
		'Õ' => '&#213;',
		'Ö' => '&#214;',
		'Ō' => '&#332;',
		'Ŏ' => '&#334;',
		'Ő' => '&#336;',
		'Ø' => '&#216;',
		'Œ' => '&#338;',
		'ŕ' => '&#341;',
		'ŗ' => '&#343;',
		'ř' => '&#345;',
		'Ŕ' => '&#340;',
		'Ŗ' => '&#342;',
		'Ř' => '&#344;',
		'ș' => '&#537;',
		'ş' => '&#351;',
		'š' => '&#353;',
		'ś' => '&#347;',
		'ŝ' => '&#349;',
		'ß' => '&#223;',
		'Ș' => '&#536;',
		'Ş' => '&#350;',
		'Š' => '&#352;',
		'Ś' => '&#346;',
		'Ŝ' => '&#348;',
		'ț' => '&#539;',
		'ţ' => '&#355;',
		'ť' => '&#357;',
		'Ț' => '&#538;',
		'Ţ' => '&#354;',
		'Ť' => '&#356;',
		'ù' => '&#249;',
		'ú' => '&#250;',
		'û' => '&#251;',
		'ü' => '&#252;',
		'ũ' => '&#361;',
		'ū' => '&#363;',
		'ŭ' => '&#365;',
		'ů' => '&#367;',
		'ű' => '&#369;',
		'ų' => '&#371;',
		'Ù' => '&#217;',
		'Ú' => '&#218;',
		'Û' => '&#219;',
		'Ü' => '&#220;',
		'Ũ' => '&#360;',
		'Ū' => '&#362;',
		'Ŭ' => '&#364;',
		'Ů' => '&#366;',
		'Ű' => '&#368;',
		'Ų' => '&#370;',
		'ŵ' => '&#373;',
		'Ŵ' => '&#372;',
		'ẏ' => '&#7823;',
		'ỳ' => '&#7923;',
		'ŷ' => '&#375;',
		'ÿ' => '&#255;',
		'ý' => '&#253;',
		'Ẏ' => '&#7822;',
		'Ỳ' => '&#7922;',
		'Ŷ' => '&#374;',
		'Ÿ' => '&#376;',
		'Ý' => '&#221;',
		'ź' => '&#378;',
		'ż' => '&#380;',
		'ž' => '&#382;',
		'Ź' => '&#377;',
		'Ż' => '&#379;',
		'Ž' => '&#381;'
	];


	//================================================================
	/**
	 * Unicode Safe strlen()	:: Get string length as number of characters in the string, which may differ from number of bytes in a string if Unicode (Multibyte) string is used
	 *
	 * @param STRING 	$ytext 	:: The string
	 *
	 * @return INTEGER			:: The number of characters in a string
	 */
	public static function str_len($ytext) {
		//--
		return (int) mb_strlen((string)$ytext);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe substr()			:: Get part of string
	 *
	 * @param STRING 	$ystr			:: The string
	 * @param INTEGER 	$ystart			:: The start offset
	 * @param INTEGER 	$ylen OPTIONAL	:: The number of characters to use, starting from start offset
	 *
	 * @return STRING					:: The sub-string
	 */
	public static function sub_str($ystr, $ystart, $ylen=null) {
		//--
		if($ylen === null) { // fixed bug that incorrectly interpret the last (optional) argument
			return (string) mb_substr((string)$ystr, (int)$ystart); // without optional param (len)
		} else {
			return (string) mb_substr((string)$ystr, (int)$ystart, (int)$ylen); // with optional param (len)
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe substr_count()	:: Count the number of substring occurrences
	 *
	 * @param STRING 	$ystr		:: The string being checked
	 * @param STRING 	$ypiece		:: The string to be found
	 *
	 * @return INTEGER				:: The number of times the piece sub-string occurs in the string being checked
	 */
	public static function substr_count($ystr, $ypiece) {
		//--
		return (int) mb_substr_count((string)$ystr, (string)$ypiece);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe str_replace() with Limit 	:: Replace a fixed (by count) number of occurrences of the search string with the replacement string
	 *
	 * @param STRING 	$needle		:: The sub-string being searched for
	 * @param STRING 	$replace	:: The replacement value that replaces found search values
	 * @param STRING 	$haystack	:: The string on which to make the replacements
	 * @param INTEGER	$count 		:: The number of replacements to operate
	 *
	 * @return STRING				:: The processed string with replacements if the needle is found
	 */
	public static function str_limit_replace($needle, $replace, $haystack, $count) {
		//--
		return (string) implode((string)$replace, (array)explode((string)$needle, (string)$haystack, ((int)$count + 1)));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strpos()		:: Find position of first occurrence of string in a string, Case Sensitive
	 *
	 * @param STRING 	$ystr		:: The string to search in
	 * @param STRING 	$ypiece		:: The sub-string to be found
	 * @param INTEGER 	$offset 	:: The search offset. If it is not specified, 0 is used
	 *
	 * @return INTEGER / FALSE		:: The numeric position of the first occurrence of piece in the string. If not found, it returns FALSE
	 */
	public static function str_pos($ystr, $ypiece, $offset=0) {
		//--
		return mb_strpos((string)$ystr, (string)$ypiece, (int)$offset); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe stripos()		:: Find position of first occurrence of string in a string, Case Insensitive
	 *
	 * @param STRING 	$ystr		:: The string to search in
	 * @param STRING 	$ypiece		:: The sub-string to be found
	 * @param INTEGER 	$offset 	:: The search offset. If it is not specified, 0 is used
	 *
	 * @return INTEGER / FALSE		:: The numeric position of the first occurrence of piece in the string. If not found, it returns FALSE
	 */
	public static function str_ipos($ystr, $ypiece, $offset=0) {
		//--
		return mb_stripos((string)$ystr, (string)$ypiece, (int)$offset); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strrpos()		:: Find position of last occurrence of string in a string, Case Sensitive
	 *
	 * @param STRING 	$ystr		:: The string to search in
	 * @param STRING 	$ypiece		:: The sub-string to be found
	 * @param INTEGER 	$offset 	:: The search offset. If it is not specified, 0 is used
	 *
	 * @return INTEGER / FALSE		:: The numeric position of the last occurrence of piece in the string. If not found, it returns FALSE
	 */
	public static function str_rpos($ystr, $ypiece, $offset=0) {
		//--
		return mb_strrpos((string)$ystr, (string)$ypiece, (int)$offset); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strripos()		:: Find position of last occurrence of string in a string, Case Insensitive
	 *
	 * @param STRING 	$ystr		:: The string to search in
	 * @param STRING 	$ypiece		:: The sub-string to be found
	 * @param INTEGER 	$offset 	:: The search offset. If it is not specified, 0 is used
	 *
	 * @return INTEGER / FALSE		:: The numeric position of the last occurrence of piece in the string or FALSE if not found
	 */
	public static function str_ripos($ystr, $ypiece, $offset=0) {
		//--
		return mb_strripos((string)$ystr, (string)$ypiece, (int)$offset); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strstr() 		:: Finds first occurrence of a string within another, Case Sensitive
	 *
	 * @param STRING 	$ystring	:: The string to search in
	 * @param STRING 	$ypart		:: The sub-string to search for in the string
	 *
	 * @return STRING / FALSE		:: Returns the portion of string starting with first match or FALSE if not found
	 */
	public static function str_str($ystring, $ypart, $ybefore_needle=false) {
		//--
		return mb_strstr((string)$ystring, (string)$ypart, (bool)$ybefore_needle); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe stristr() 		:: Finds first occurrence of a string within another, Case Insensitive
	 *
	 * @param STRING 	$ystring	:: The string to search in
	 * @param STRING 	$ypart		:: The sub-string to search for in the string
	 *
	 * @return STRING / FALSE		:: Returns the portion of string starting with first match or FALSE if not found
	 */
	public static function stri_str($ystring, $ypart, $ybefore_needle=false) {
		//--
		return mb_stristr((string)$ystring, (string)$ypart, (bool)$ybefore_needle); // return MIXED !
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe 				:: Check if a string contains another, Case Sensitive
	 *
	 * @param STRING 	$ystring	:: The string to check
	 * @param STRING 	$ypart		:: The sub-string to search for in the string
	 *
	 * @return BOOLEAN				:: Returns TRUE if found or FALSE if not found
	 */
	public static function str_contains($ystring, $ypart) {
		//--
		if(((string)$ystring == '') OR ((string)$ypart == '')) {
			return false;
		} //end if
		//--
		if(self::str_pos((string)$ystring, (string)$ypart) !== false) { // we don't need unicode here because not using the count, just return very fast if string contains or not the sub-string
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode ~ Safe * 			:: Check if a string contains another, Case Insensitive
	 *
	 * @param STRING 	$ystring	:: The string to check
	 * @param STRING 	$ypart		:: The sub-string to search for in the string
	 *
	 * @return BOOLEAN				:: Returns TRUE if found or FALSE if not found
	 */
	public static function str_icontains($ystring, $ypart) {
		//--
		if(((string)$ystring == '') OR ((string)$ypart == '')) {
			return false;
		} //end if
		//--
		if(self::str_ipos((string)$ystring, (string)$ypart) !== false) { // we don't need unicode here because not using the count, just return very fast if string contains or not the sub-string
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode ~ Safe * 			:: Find the aproximative number of words in an unicode string
	 *
	 * @param STRING 	$str		:: The string to find words into
	 *
	 * @return INTEGER				:: Returns the number of words found
	 */
	public static function str_wordcount($str) {
		//--
		$arr = preg_split('/\s+/', (string)$str, -1, PREG_SPLIT_NO_EMPTY); // mixed ; don't cast to array no need to trim with this flag
		if(is_array($arr)) {
			return (int) count($arr); // avoid rely on smart array size here to avoid circular dependency with smart lib
		} //end if
		//--
		return 0; // if preg_split returned false on failure
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Detect Charset 				:: Try to detect the charset
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 	$ystr		:: The string
	 * @param STRING 	$csetlist 	:: The comma separed list of allowed charsets
	 *
	 * @return MIXED				:: The detected charset if any as string or bool FALSE if could not detect
	 */
	//public static function detect_encoding($ystr, $csetlist='UTF-8, ISO-8859-1, ISO-8859-15, ISO-8859-2, ISO-8859-9, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-10, ISO-8859-11, ISO-8859-13, ISO-8859-14, ISO-8859-16, UTF-7, ASCII, SJIS, EUC-JP, JIS, ISO-2022-JP, EUC-CN, GB18030, ISO-2022-KR, KOI8-R, KOI8-U') {
	public static function detect_encoding($ystr, $csetlist='UTF-8, ISO-8859-1, ISO-8859-15, ISO-8859-2, ISO-8859-9, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-10, ISO-8859-13, ISO-8859-14, ISO-8859-16, UTF-7, ASCII, SJIS, EUC-JP, JIS, ISO-2022-JP, EUC-CN, GB18030, ISO-2022-KR, KOI8-R, KOI8-U') { // Fix: starting from PHP 7.1 it warns about illegal argument if using: ISO-8859-11
		//--
		return @mb_detect_encoding((string)$ystr, (string)$csetlist, true); // mixed: (bool) FALSE or (string) 'CHARSET'
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strtolower() 	:: Make a Unicode string lowercase ; works with any of these encodings: UTF-8, ISO-8859-1, ISO-8859-2
	 *
	 * @param STRING 	$ystr		:: The string
	 *
	 * @return STRING				:: The processed string as lowercase string
	 */
	public static function str_tolower($ystr) {
		//--
		if((string)$ystr == '') {
			return '';
		} //end if
		//--
		return (string) @mb_convert_case((string)$ystr, MB_CASE_LOWER, self::detect_encoding((string)$ystr, 'UTF-8, ISO-8859-1, ISO-8859-2')); // much better than mb_strtolower($ystr);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe ucfirst() 		:: Make a Unicode string uppercase on first character ; works with any of these encodings: UTF-8, ISO-8859-1, ISO-8859-2
	 *
	 * @param STRING 	$ystr		:: The string
	 *
	 * @return STRING				:: The processed string as uppercase of first character string
	 */
	public static function uc_first($ystr) {
		//--
		if((string)$ystr == '') {
			return '';
		} //end if
		//--
		$first = self::sub_str((string)$ystr, 0, 1);
		$rest = self::sub_str((string)$ystr, 1, self::str_len((string)$ystr));
		return (string) self::str_toupper((string)$first).$rest;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe ucwords() 		:: Make a Unicode string uppercase on each word ; works with any of these encodings: UTF-8, ISO-8859-1, ISO-8859-2
	 * Notice: this is only partial compatible with PHP ucwords() as it makes first letter of each word Upper while force lowercase on the rest of the word letters as it complies with MB_CASE_TITLE
	 *
	 * @param STRING 	$ystr		:: The string
	 *
	 * @return STRING				:: The processed string as uppercase on each word
	 */
	public static function uc_words($ystr) {
		//--
		return (string) @mb_convert_case($ystr, MB_CASE_TITLE, self::detect_encoding((string)$ystr, 'UTF-8, ISO-8859-1, ISO-8859-2'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe strtoupper() 	:: Make a Unicode string uppercase ; works with any of these encodings: UTF-8, ISO-8859-1, ISO-8859-2
	 *
	 * @param STRING 	$ystr		:: The string
	 *
	 * @return STRING				:: The processed string as uppercase string
	 */
	public static function str_toupper($ystr) {
		//--
		if((string)$ystr == '') {
			return '';
		} //end if
		//--
		return (string) @mb_convert_case((string)$ystr, MB_CASE_UPPER, self::detect_encoding((string)$ystr, 'UTF-8, ISO-8859-1, ISO-8859-2')); // much better than mb_strtoupper($ystr);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Convert the CharSet Encoding of a String
	 * NOTICE: If the charset to is different than UTF-8 (unicode) if using the string in this framework it must be re-converted
	 * NOTICE: If used to convert to HTML-ENTITIES charset this function will consume a lot of memory and may run out of memory for large strings > 10% of memory_limit set in init.php
	 *
	 * @param STRING 	$ystr			:: The string
	 * @param ENUM 		$ychar_from		:: Empty to detect / Select one of the: UTF-8, ISO-8859-1, ISO-8859-15, ISO-8859-2, ISO-8859-9, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-10, ISO-8859-11, ISO-8859-13, ISO-8859-14, ISO-8859-16, UTF-7, ASCII, SJIS, EUC-JP, JIS, ISO-2022-JP, EUC-CN, ISO-2022-KR, KOI8-R, KOI8-U
	 * @param ENUM 		$ychar_to		:: Empty to use the framework internal charset defined in SMART_FRAMEWORK_CHARSET / Select one of the: UTF-8, HTML-ENTITIES or another valid charset
	 * @param BOOLEAN	$normalize		:: Normalize (Default is TRUE) - will normalize the string into the default framework charset else the string will be incompatible with the current encoding ... ; Using this to false must be use with very much attention !!!
	 *
	 * @return STRING					:: The processed string
	 */
	public static function convert_charset($ystr, $y_charset_from='', $y_charset_to='', $normalize=true) {
		//--
		if((string)$ystr == '') {
			return '';
		} //end if
		//--
		$ystr = (string) SmartFrameworkSecurity::FilterUnsafeString((string)$ystr); // Fix: remove unsafe characters from original string
		//--
		if((string)$y_charset_from == '') { // if empty, try to detect it
			$y_charset_from = self::detect_encoding((string)$ystr);
		} //end if else
		//--
		if((string)$y_charset_to == '') { // if no charset provided use the default
			$y_charset_to = (string) SMART_FRAMEWORK_CHARSET; // if default is defined is checked in the top of this lib
		} //end if
		//--
		if($y_charset_from) { // avoid make it string if bool FALSE !!
			$y_charset_from = (string) strtoupper((string)$y_charset_from);
		} //end if
		$y_charset_to = (string) strtoupper((string)$y_charset_to);
		//--
		if(($y_charset_from) AND ((string)$y_charset_to != '')) {
			//--
			$ystr = (string) @mb_convert_encoding((string)$ystr, (string)$y_charset_to, $y_charset_from);
			//--
			if((string)SMART_FRAMEWORK_CHARSET == 'UTF-8') {
				if((string)$y_charset_to != (string)SMART_FRAMEWORK_CHARSET) {
					if($normalize) {
						if((string)$ystr != '') { // Fix: avoid do utf8 encode on an empty string (some PHP versions have a bug with exhaust memory)
							$ystr = (string) utf8_encode((string)$ystr); // fix: this is needed to normalize the strings into the framework's current charset
						} //end if
					} //end if
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) $ystr;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Fix the string to contain only current charset.
	 * All the unsafe characters will be replaced by: ?
	 * This is not necessary after SmartUnicode::convert_charset() as it will already fix it
	 *
	 * @param STRING 	$str			:: The string
	 *
	 * @return STRING					:: The fixed string
	 */
	public static function fix_charset($str) {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		return (string) self::convert_charset((string)$str, '', (string)SMART_FRAMEWORK_CHARSET); // charset from is EMPTY to try to detect it
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe Convert UTF-8 to Unicode ISO.
	 * It will remove all invalid characters except latin1.
	 *
	 * NOTICE: It converts the string back to unicode since all the strings in the framework are unicode (UTF-8) to avoid breaking the regex with \u over those strings !!!
	 * Never use just single utf8_decode() when the framework is in UTF-8 mode, else the regex \u will fail over those strings ...
	 *
	 * @param STRING 	$str			:: The string
	 * @param BOOLEAN	$normalize		:: Normalize (Default is TRUE) - will normalize the string into the default framework charset else the string will be incompatible with the current encoding ... ; Using this to false must be use with very much attention !!!
	 *
	 * @return STRING					:: The processed string
	 */
	public static function utf8_to_iso($str, $normalize=true) {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$str = (string) SmartFrameworkSecurity::FilterUnsafeString((string)$str); // Fix: remove unsafe characters from original string
		//--
		$str = (string) utf8_decode((string)$str);
		//--
		if($normalize) {
			if((string)$str != '') { // Fix: avoid do utf8 encode on an empty string (some PHP versions have a bug with exhaust memory)
				$str = (string) utf8_encode($str);
			} //end if
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * De-Accent a latin-based Unicode string 	:: will convert all accented characters in UTF-8 / ISO-8859-* with their unnaccented versions into ISO-8859-1
	 *
	 * @param STRING 	$str					:: The string
	 * @param BOOLEAN	$normalize				:: Normalize (Default is TRUE) - will normalize the string by forcing the ISO-8859-1 character set
	 *
	 * @return STRING							:: The processed string
	 */
	public static function deaccent_str($str, $normalize=true) {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$str = (string) strtr((string)$str, (array)self::ACCENTED_CHARS);
		if($normalize) {
			$str = (string) self::utf8_to_iso((string)$str);
		} //end if
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Convert the Unicode Accented Characters to Safe HTML Entities
	 *
	 * @param STRING 	$str			:: The string
	 *
	 * @return STRING					:: The processed string
	 */
	public static function html_entities($str) {
		//--
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$str = (string) strtr((string)$str, (array)self::ACCENTED_HTML_ENTITIES);
		//--
		$str = (string) self::convert_charset($str, '', 'HTML-ENTITIES', true);
		//--
		return (string) self::utf8_to_iso((string)$str); // use utf8 to iso for safety
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe wordwrap() 				:: Wraps a string to a given number of characters
	 *
	 * @param STRING 	$str				:: The string to be wrapped
	 * @param INTEGER+ 	$width				:: The number of characters at which the string will be wrapped
	 * @param STRING	$break 	OPTIONAL	:: The line is broken using the optional break parameter ; Default is: \n ; will also add the optional visual break by default '¬'
	 * @param BOOLEAN 	$cut 	OPTIONAL	:: If the cut is set to TRUE, the string is always wrapped at or before the specified width ; When FALSE the function dose not split the word until the end of the word even if the width is smaller than the word width. Default is FALSE.
	 *
	 * @return STRING						:: The processed string
	 */
	public static function word_wrap($str, $width=75, $break="\n", $cut=false, $visualbreak='¬') {
		//-- there is no mb_word_wrap, so this would be like ; an alternative but not well tested on unicode strings and may break them would be: return preg_replace('/([^\s]{'.(int)$width.'})(?=[^\s])/m', '$1` '.$break, (string)$str); // this needs the unicode modifier to avoid break characters
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$width = (int) $width;
		if($width < 1) {
			$width = 1; // min
		} //end if
		if($width > 2048) {
			$width = 2048; // max
		} //end if
		//--
		$break = (string) $break;
		if((string)$break == '') {
			$break = "\n"; // default
		} //end if
		//--
		$cut = (bool) $cut;
		if($cut !== true) {
			$cut = false; // default
		} //end if
		//--
		$lines = (array) explode((string)$break, (string)$str);
		//--
		foreach($lines as &$line) { // PHP7-CHECK:FOREACH-BY-VAL
			//--
			$line = (string) rtrim((string)$line);
			//--
			if(self::str_len($line) <= $width) {
				continue;
			} //end if
			//--
			$words = (array) explode(' ', (string)$line);
			$line = '';
			$actual = '';
			//--
			foreach($words as $word) {
				//--
				if(self::str_len((string)$actual.$word) <= $width) {
					//--
					$actual .= (string) $word.' ';
					//--
				} else {
					//--
					if((string)$actual != '') {
						//--
						$line .= (string) rtrim((string)$actual).$break;
						//--
					} //end if
					//--
					$actual = (string) $word;
					//--
					if($cut) {
						//--
						while(self::str_len($actual) > $width) {
							$line .= (string) self::sub_str($actual, 0, $width).$visualbreak.$break;
							$actual = (string) self::sub_str($actual, $width);
						} //end while
						//--
					} //end if
					//--
					$actual .= ' ';
					//--
				} //end if else
				//--
			} //end foreach
			//--
			$line .= (string) trim((string)$actual);
			//--
		} //end foreach
		//--
		return (string) implode((string)$break, (array)$lines);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Unicode Safe mail() 				:: Send unicode safe mail
	 * This function is provided just for compatibility.
	 * It is recommended to use advanced mailer functionalities such as SmartMailerUtils::send_extended_email() instead of this function
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param STRING 	$yto			:: To Email Address
	 * @param STRING 	$ysubj			:: Subject
	 * @param STRING 	$ymsg			:: Message
	 * @param STRING 	$yyhead			:: Default Headers
	 * @param STRING 	$yyxtra			:: Extra Headers
	 *
	 * @return BOOLEAN 					:: Returns TRUE on success or FALSE on failure
	 */
	public static function mailsend($yto, $ysubj, $ymsg, $yyhead, $yyxtra) {
		//--
		$out = false; // init
		//--
		if(((string)$yyhead == '') AND ((string)$yyxtra == '')) {
			$out = @mb_send_mail((string)$yto, (string)$ysubj, (string)$ymsg); // simple
		} else {
			if((string)$yyxtra == '') {
				$out = @mb_send_mail((string)$yto, (string)$ysubj, (string)$ymsg, (string)$yyhead); // medium
			} else {
				$out = @mb_send_mail((string)$yto, (string)$ysubj, (string)$ymsg, (string)$yyhead, (string)$yyxtra); // full
			} //end if else
		} //end if else
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=================================================================================
//================================================================================= CLASS END
//=================================================================================


//end of php code
