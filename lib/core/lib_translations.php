<?php
// [LIB - Smart.Framework / Text Translations]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Regional Text
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class Smart Text Translations.
 * It provides a Language Based Text Translations Layer for the Smart.Framework based Applications.
 *
 * <code>
 * // Usage example:
 * SmartTextTranslations::some_method_of_this_class(...);
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartPersistentCache, SmartAdapterTextTranslations, SmartFrameworkRegistry
 * @version 	v.20231030
 * @package 	Application:Translations
 *
 */
final class SmartTextTranslations {

	// ::

	private static $cache = array();
	private static $translators = array();


	//=====
	/**
	 * Regional Text :: Get Available Languages
	 *
	 * @return 	ARRAY						:: The array with available language IDs ; sample: ['en', 'ro']
	 */
	public static function getAvailableLanguages() {
		//--
		$all_languages = (array) self::getSafeLanguagesArr();
		//--
		return (array) array_keys((array)$all_languages);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Get (available) Languages List
	 *
	 * @return 	ARRAY						:: The array with available languages List ['en' => 'English', 'ro' => 'Romanian']
	 */
	public static function getListOfLanguages() {
		//--
		$all_languages = (array) self::getSafeLanguagesArr();
		//--
		$list_languages = array();
		foreach($all_languages as $key => $val) {
			if(is_array($val)) {
				$list_languages[(string)$key] = (string) $val['name'];
			} else {
				$list_languages[(string)$key] = (string) $val;
			} //end if
		} //end for
		//--
		return (array) $list_languages;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Checks if the Current Language is the Default Language for the current session / or parameter
	 *
	 * @param 	STRING 	$y_language 		:: Optional, the language ID to be checked ; otherwise will check the session language ; sample (for English) will be: 'en'
	 *
	 * @return 	BOOLEAN						:: Returns TRUE if the Current Language is the Default Language for the current session / or parameter otherwise returns FALSE
	 */
	public static function isDefaultLanguage(?string $y_language='') {
		//--
		if((string)$y_language == '') {
			$y_language = (string) self::getDefaultLanguage();
		} //end if else
		//--
		if((string)self::getLanguage() == (string)$y_language) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Get the Default Language for the current session as Set by Init
	 *
	 * @return 	STRING						:: The language ID ; sample (for English) will return: 'en'
	 */
	public static function getDefaultLanguage() {
		//--
		$lang = 'en';
		//--
		if(defined('SMART_FRAMEWORK_DEFAULT_LANG')) {
			if(self::validateLanguage((string)SMART_FRAMEWORK_DEFAULT_LANG)) {
				$lang = (string) SMART_FRAMEWORK_DEFAULT_LANG;
			} else {
				Smart::raise_error(
					'Invalid Default Language set in SMART_FRAMEWORK_DEFAULT_LANG: '.SMART_FRAMEWORK_DEFAULT_LANG,
					'Invalid Default Language Set in Configs' // msg to display
				);
			} //end if
		} //end if
		//--
		return (string) $lang;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Get the Current Language for the current session as Set by Config / URL / Cookie / Method-Set
	 *
	 * @return 	STRING						:: The language ID ; sample (for English) will return: 'en'
	 */
	public static function getLanguage() {
		//--
		if((array_key_exists('#LANGUAGE#', self::$cache)) AND (strlen((string)self::$cache['#LANGUAGE#']) == 2)) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Get Language from Internal Cache',
						'data' => 'Content: '.self::$cache['#LANGUAGE#']
					]);
				} //end if
			} //end if
			return (string) self::$cache['#LANGUAGE#'];
		} //end if
		//--
		$the_lang = 'en'; // default
		//--
		$tmp_lang = (string) strtolower((string)Smart::get_from_config('regional.language-id', 'string'));
		if(self::validateLanguage($tmp_lang)) {
			$the_lang = (string) $tmp_lang;
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Get Language from Configs',
						'data' => 'Content: '.$the_lang
					]);
				} //end if
			} //end if
		} //end if
		//--
		self::$cache['#LANGUAGE#'] = (string) strtolower((string)$the_lang);
		//--
		return (string) self::$cache['#LANGUAGE#'];
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Set the Language for current session
	 *
	 * @param 	STRING 	$y_language 		:: The language ID ; sample (for English) will be: 'en'
	 *
	 * @return 	BOOLEAN						:: TRUE if successful, FALSE if not
	 */
	public static function setLanguage(?string $y_language) {
		//--
		global $configs;
		//--
		$result = false;
		//--
		if(is_array($configs) AND array_key_exists('regional', $configs) AND is_array($configs['regional'])) {
			//--
			$all_languages = (array) self::getSafeLanguagesArr();
			//--
			$tmp_lang = (string) strtolower((string)SmartUnicode::utf8_to_iso((string)$y_language));
			//--
			$id_cfg_lang = '';
			if(array_key_exists('language-id', $configs['regional'])) {
				if(Smart::is_nscalar($configs['regional']['language-id'])) {
					$id_cfg_lang = (string) $configs['regional']['language-id'];
				} //end if
			} //end if
			//--
			if(strlen((string)$tmp_lang) == 2) { // if language id have only 2 characters
				if(preg_match('/^[a-z]+$/', (string)$tmp_lang)) { // language id must contain only a..z characters (iso-8859-1)
					if(is_array($all_languages)) {
						if($all_languages[(string)$tmp_lang]) { // if that lang is set in languages array
							if((string)$tmp_lang != (string)$id_cfg_lang) { // if it is the same, don't make sense to set it again !
								$configs['regional']['language-id'] = (string) $tmp_lang;
								if(Smart::array_size($all_languages[(string)$tmp_lang]) > 0) {
									// set also the rest of regional params if available and set custom for that language ...
									foreach($all_languages[(string)$tmp_lang] as $k => $v) {
										if(array_key_exists((string)$k, (array)$configs['regional'])) {
											//Smart::log_notice('Setting Regional Key for Language: '.$tmp_lang.' as @ '.$k.'='.$v);
											$configs['regional'][(string)$k] = (string) $v;
										} //end if
									} //end foreach
								} //end if
								$configs['regional'] = (array) self::getSafeRegionalSettings(); // re-export fixed
								self::$cache['#LANGUAGE#'] = (string) $tmp_lang;
								$result = true;
								if(SmartEnvironment::ifInternalDebug()) {
									if(SmartEnvironment::ifDebug()) {
										SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
											'title' => 'Set Language in Configs and Internal Cache',
											'data' => 'Content: '.$tmp_lang
										]);
									} //end if
								} //end if
							} //end if
						} //end if
					} //end if
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (bool) $result;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Validate Language
	 *
	 * @param 	STRING 	$y_language 		:: The language ID ; sample (for English) will be: 'en'
	 *
	 * @return 	BOOLEAN						:: TRUE if language defined in configs, FALSE if not
	 *
	 */
	public static function validateLanguage(?string $y_language) {
		//--
		if((string)trim((string)$y_language) == '') {
			return false;
		} //end if
		//--
		$all_languages = (array) self::getSafeLanguagesArr();
		//--
		$ok = false;
		//--
		if(strlen((string)$y_language) == 2) { // if language id have only 2 characters
			if(preg_match('/^[a-z]+$/', (string)$y_language)) { // language id must contain only a..z characters (iso-8859-1)
				if(is_array($all_languages)) {
					if($all_languages[(string)$y_language]) { // if that lang is set in languages array
						$ok = true;
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Get the Translator object for: area, subarea and a custom language (if enforced and not using the current language)
	 *
	 * @param 	STRING 	$y_area 				:: The Translation Area
	 * @param 	STRING 	$y_subarea 				:: The Translation Sub-Area
	 * @param 	STRING 	$y_custom_language 		:: *OPTIONAL* a language code ; default empty ; if empty will use the current language
	 *
	 * @return 	OBJECT							:: An Instance of SmartTextTranslator->
	 */
	public static function getTranslator(?string $y_area, ?string $y_subarea, ?string $y_custom_language='') {
		//--
		$y_area 	= (string) self::validateArea($y_area);
		$y_subarea 	= (string) self::validateSubArea($y_subarea);
		//--
		if(((string)$y_custom_language != '') AND (self::validateLanguage($y_custom_language) === true)) { // get for a custom language
			$the_lang = (string) $y_custom_language;
		} else {
			$the_lang = (string) self::getLanguage(); // use default language
		} //end if else
		//--
		$translator_key = (string) $the_lang.'.'.$y_area.'.'.$y_subarea; // must use . as separator as it is the only character that is not allowed in lang / area / sub-area but is allowed in persistent cache
		//--
		if((!array_key_exists((string)$translator_key, self::$translators)) OR (!is_object(self::$translators[(string)$translator_key]))) {
			self::$translators[(string)$translator_key] = new SmartTextTranslator((string)$the_lang, (string)$y_area, (string)$y_subarea);
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Creating a New Translator: '.$translator_key,
						'data' => 'Content:'."\n".print_r(self::$translators[(string)$translator_key],1) // object
					]);
				} //end if
			} //end if
		} else {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Re-Using an Existing Translator: '.$translator_key,
						'data' => 'Content:'."\n".print_r(self::$translators[(string)$translator_key],1) // object
					]);
				} //end if
			} //end if
		} //end if
		//--
		return (object) self::$translators[(string)$translator_key];
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the safe regional date format for Js (Javascript)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	STRING							:: The date format (ex: 'yy-mm-dd')
	 */
	public static function getDateFormatForJs(?string $y_format) {
		//-- yy = year with 4 digits, mm = month 01..12, dd = day 01..31
		$format = 'yy-mm-dd'; // the default format
		//--
		switch((string)$y_format) {
			//--
			case 'yy.mm.dd':
			case 'yy-mm-dd':
			case 'yy mm dd':
			//--
			case 'dd.mm.yy':
			case 'dd-mm-yy':
			case 'dd mm yy':
			//--
			case 'mm.dd.yy':
			case 'mm-dd-yy':
			case 'mm dd yy':
			//--
				$format = $y_format;
				break;
			default:
				// nothing
		} //end switch
		//--
		return (string) $format;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the safe regional date format for PHP
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	STRING							:: The date format (ex: 'Y-m-d')
	 */
	public static function getDateFormatForPhp(?string $y_format) {
		//-- Y = year with 4 digits, m = month 01..12, d = day 01..31
		$format = 'Y-m-d'; // the default format
		//--
		switch((string)$y_format) {
			//--
			case 'Y.m.d':
			case 'Y-m-d':
			case 'Y m d':
			//--
			case 'd.m.Y':
			case 'd-m-Y':
			case 'd m Y':
			//--
			case 'm.d.Y':
			case 'm-d-Y':
			case 'm d Y':
			//--
				$format = $y_format;
				break;
			default:
				// nothing
		} //end switch
		//--
		return (string) $format;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get (Safe) Regional Settings
	 * If the regional settings are wrong they will be fixed
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	ARRAY							:: The updated ARRAY of Regional Settings for the Current Language
	 */
	public static function getSafeRegionalSettings() {
		//--
		$arr = Smart::get_from_config('regional');
		if(!is_array($arr)) {
			$arr = array();
		} //end if
		//--
		$arr = (array) array_change_key_case((array)$arr, CASE_LOWER); // make all keys lower
		$arr = (array) Smart::array_init_keys($arr, [
			'language-id',
			'decimal-separator',
			'thousands-separator',
			'calendar-week-start',
			'calendar-date-format-client',
			'calendar-date-format-server',
			'language-direction',
		]);
		//--
		if((string)strtoupper((string)trim((string)$arr['language-direction'])) == 'RTL') {
			$arr['language-direction'] = 'RTL'; // right to left
		} else {
			$arr['language-direction'] = 'LTR'; // left to right
		} //end if
		//--
		switch((string)$arr['decimal-separator']) {
			case ',':
				break;
			default:
				$arr['decimal-separator'] = '.';
		} //end switch
		switch((string)$arr['thousands-separator']) {
			case '.':
			case ' ':
				break;
			default:
				$arr['thousands-separator'] = ',';
		} //end switch
		if((string)$arr['decimal-separator'] == (string)$arr['thousands-separator']) {
			$arr['decimal-separator'] = '.';
			$arr['thousands-separator'] = ',';
		} //end if
		$arr['calendar-week-start'] = (int) $arr['calendar-week-start'];
		if($arr['calendar-week-start'] < 0) {
			$arr['calendar-week-start'] = 0;
		} elseif($arr['calendar-week-start'] > 1) {
			$arr['calendar-week-start'] = 1;
		} //end if else
		$arr['calendar-date-format-client'] = (string) self::getDateFormatForJs($arr['calendar-date-format-client']);
		$arr['calendar-date-format-server'] = (string) self::getDateFormatForPhp($arr['calendar-date-format-server']);
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Format a number as a localized number (using regional settings)
	 * The number to format must come in US format as 1234.56 or -1234.56 or 1,234.56 or 1 234.56 or -1,234.56 or -1 234.56
	 * [-1] will place auto decimals
	 * [-2] will place auto decimals but force as min as .0
	 *
	 * Use it just for display (a local formated number)
	 * WARNING: Do not use when you do calculations, because will break calculations
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	STRING							:: Formatted Local Number
	 */
	public static function formatAsLocalNumber($y_number, ?int $y_decimals=-1, bool $y_usethousandsep=true) {
		//--
		if(!Smart::is_nscalar($y_number)) {
			Smart::log_warning('Invalid Number to convert to local (invalid type): '.print_r($y_number,1));
			return (string) '!'.'?'.'!';
		} //end if
		$y_number = (string) trim((string)$y_number);
		//--
		if(!preg_match('/^[0-9\-\.\, ]+$/', (string)$y_number)) {
			Smart::log_warning('Invalid Number to convert to local (invalid characters): '.$y_number);
			return (string) '!'.$y_number.'!';
		} //end if
		//--
		$y_decimals = Smart::format_number_int($y_decimals);
		if($y_decimals > 4) {
			$y_decimals = 4;
		} elseif($y_decimals < -2) {
			$y_decimals = -2;
		} //end if else
		//--
		$separator_dec = (string) Smart::get_from_config('regional.decimal-separator', 'string');
		$separator_thd = (string) Smart::get_from_config('regional.thousands-separator', 'string');
		if((strlen((string)$separator_dec) > 1) OR (strlen((string)$separator_thd) > 1)) {
			$separator_dec = '.';
			$separator_thd = ',';
		} //end if
		//--
		$sign = '';
		if((string)substr((string)$y_number, 0, 1) == '-') {
			$sign = '-';
		} //end if
		//--
		$localnum = '0';
		//--
		$tmp_arr = (array) explode('.', (string)$y_number);
		if(!array_key_exists(0, $tmp_arr)) {
			$tmp_arr[0] = null;
		} //end if
		if(!array_key_exists(1, $tmp_arr)) {
			$tmp_arr[1] = null;
		} //end if
		//--
		$int_part = (string) trim((string)str_replace(['.', ',', ' ', '-', $separator_dec, $separator_thd], '', (string)$tmp_arr[0]));
		$dec_part = (string) trim((string)str_replace(['.', ',', ' ', '-', $separator_dec, $separator_thd], '', (string)$tmp_arr[1]));
		//--
		$intx_part = (string) strrev((string)chunk_split((string)strrev((string)$int_part), 3, $separator_thd));
		$intx_part = (string) trim((string)substr((string)$intx_part, 1));
		//--
		if($y_usethousandsep === false) {
			$intx_part = (string) $int_part;
		} //end if
		//--
		if(Smart::array_size($tmp_arr) > 2) { // invalid
			//--
			Smart::log_warning('Invalid Number to convert to local (have too many decimal parts): '.$y_number);
			return (string) '!'.$y_number.'!';
			//--
		} else {
			//--
			switch((string)$y_decimals) {
				case '0': // no decimals
					$localnum = (string) $intx_part;
					break;
				case '1': // fixed number of decimal: 1
				case '2': // fixed number of decimal: 2
				case '3': // fixed number of decimal: 3
				case '4': // fixed number of decimal: 4
					$localnum = (string) $intx_part.$separator_dec.str_pad((string)substr((string)(int)$dec_part, 0, (int)$y_decimals), (int)$y_decimals, '0', STR_PAD_RIGHT);
					break;
				case '-2': // auto decimals but force at least one
					$autodec = (int) $dec_part;
					$localnum = (string) $intx_part.$separator_dec.$autodec;
					break;
				case '-1': // auto decimals (zero or more)
				default:
					$autodec = (int) $dec_part;
					if((int)$autodec > 0) {
						$localnum = (string) $intx_part.$separator_dec.$autodec;
					} else {
						$localnum = (string) $intx_part;
					} //end if else
			} //end switch
			//--
		} //end if else
		//--
		return (string) $sign.$localnum;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Reverse (Inverse) the SIGN for a localized number (like localnum * -1)
	 * The Number will be treated as STRING, as it may be huge to avoid break it
	 *
	 * Use it just for display (a local formated number)
	 * WARNING: Do not use when you do calculations, because will break calculations
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return 	STRING							:: Formatted Local Number
	 */
	public static function reverseSignOfLocalFormattedNumber(?string $y_number) {
		//--
		$separator_dec = (string) Smart::get_from_config('regional.decimal-separator');
		$separator_thd = (string) Smart::get_from_config('regional.thousands-separator');
		//-- test if zero
		$tmp_number = str_replace(['.', ',', ' ', $separator_dec, $separator_thd], ['', '', '', '', ''], (string)$y_number); // remove garbage characters
		if((float)$tmp_number == 0) {
			return (string) $y_number; // it is zero, so no sign should be used
		} //end if
		//-- inverse the sign
		$y_number = (string) trim((string)$y_number);
		if((string)substr((string)$y_number, 0, 1) == '-') {
			$y_number = (string) trim((string)substr((string)$y_number, 1)); // remove the minus sign -
		} else {
			$y_number = (string) '-'.$y_number; // add the minus sign -
		} //end if
		//--
		return (string) $y_number;
		//--
	} //END FUNCTION
	//=====


	//##### INTERNAL USE PUBLICS


	//=====
	/**
	 * Regional Text :: Get Text Translation By Key for the current language / area / sub-area.
	 * This does not implement any control against the case if the key is missing.
	 * It is mainly implemented to be re-used just with programatic cases.
	 * Thus, it is recommended to use the getTranslator() function instead ...
	 *
	 * @param 	STRING 	$y_area 				:: The Translation Area
	 * @param 	STRING	$y_subarea 				:: The Translation Sub-Area
	 * @param 	STRING 	$y_textkey 				:: The Translation Key
	 *
	 * @return 	STRING							:: The Translation by Key for the specific language / area / sub-area
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function getTranslationByKey(?string $y_area, ?string $y_subarea, ?string $y_textkey, ?string $y_custom_language='') {
		//--
		$y_area 	= (string) self::validateArea($y_area);
		$y_subarea 	= (string) self::validateSubArea($y_subarea);
		//--
		if(((string)$y_custom_language != '') AND (self::validateLanguage($y_custom_language) === true)) { // get for a custom language
			$the_lang = (string) $y_custom_language;
		} else {
			$the_lang = (string) self::getLanguage(); // use default language
		} //end if else
		//--
		if(SmartEnvironment::ifDevMode() === true) {
			if(self::checkSourceParser() === true) {
				SmartAdapterTextTranslations::setTranslationsKeyUsageCount($the_lang, $y_area, $y_subarea, $y_textkey);
			} //end if
		} //end if
		//--
		$translations = (array) self::getFromOptimalPlace($the_lang, $y_area, $y_subarea);
		//--
		return (string) ($translations[(string)$y_textkey] ?? null);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Regional Text :: Get All Translations for the current language / area / sub-area.
	 * This does not implement any control against cases where some keys are missing.
	 * It is mainly implemented to be re-used just with programatic cases.
	 * Thus, it is recommended to use the getTranslator() function instead ...
	 *
	 * @param 	STRING 	$y_area 				:: The Translation Area
	 * @param 	STRING	$y_subarea 				:: The Translation Sub-Area
	 *
	 * @return 	ARRAY							:: An Array with the full set of Translations for the specific language / area / sub-area
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function getAllTranslations(?string $y_area, ?string $y_subarea, ?string $y_custom_language='') {
		//--
		$y_area 	= (string) self::validateArea($y_area);
		$y_subarea 	= (string) self::validateSubArea($y_subarea);
		//--
		if(((string)$y_custom_language != '') AND (self::validateLanguage($y_custom_language) === true)) { // get for a custom language
			$the_lang = (string) $y_custom_language;
		} else {
			$the_lang = (string) self::getLanguage(); // use default language
		} //end if else
		//--
		return (array) self::getFromOptimalPlace($the_lang, $y_area, $y_subarea);
		//--
	} //END FUNCTION
	//=====


	//#####


	//=====
	// get safe fixed languages arr
	private static function getSafeLanguagesArr() {
		//--
		global $languages;
		//--
		if(!is_array($languages)) {
			$languages = array('en' => '[EN]');
		} else {
			$languages = (array) array_change_key_case((array)$languages, CASE_LOWER); // make all keys lower
		} //end if
		//--
		return (array) $languages;
		//--
	} //END FUNCTION
	//=====


	//=====
	// validates the area name
	private static function validateArea(?string $y_area) {
		//--
		if(((string)$y_area != '') AND (preg_match('/^[a-z0-9_\-@]+$/', (string)$y_area))) {
			return (string) $y_area;
		} else {
			return 'invalid__area';
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	// validates the sub-area name
	private static function validateSubArea(?string $y_subarea) {
		//--
		if((string)$y_subarea != '') {
			return (string) $y_subarea;
		} else {
			return 'invalid__subarea';
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	private static function checkSourceParser() {
		//--
		if(class_exists('SmartAdapterTextTranslations')) {
			if(!is_subclass_of('SmartAdapterTextTranslations', 'SmartInterfaceAdapterTextTranslations', true)) {
				Smart::log_warning('Invalid instance of SmartAdapterTextTranslations ; Must implement the SmartInterfaceAdapterTextTranslations ...');
			} else {
				return true;
			} //end if
		} //end if
		return false;
		//--
	} //END FUNCTION
	//=====


	//=====
	// This will handle the Text Translations Source Parsing and will return the parsed Array
	private static function getFromSource(?string $the_lang, ?string $y_area, ?string $y_subarea) {
		//--
		if(self::checkSourceParser() === true) {
			return (array) SmartAdapterTextTranslations::getTranslationsFromSource($the_lang, $y_area, $y_subarea);
		} else {
			Smart::log_warning('SmartAdapterTextTranslations::getTranslationsFromSource() must be defined ...');
			return array();
		} //end if
		//--
	} //END FUNCTION
	//=====


	//=====
	// It returns the latest Version signature of the Text Translations.
	// If the Version cannot be provided is OK just returning the current date/time as YYYY-MM-DD (in this case it will invalidate the Translations once per day).
	// It will be used to invalidate the Persistent Cache every time when the translations version is changed.
	private static function getLatestVersion() {
		//--
		if(!array_key_exists('translations:persistent-cache-version', self::$cache)) {
			self::$cache['translations:persistent-cache-version'] = null;
		} //end if
		if((string)self::$cache['translations:persistent-cache-version'] != '') {
			return (string) self::$cache['translations:persistent-cache-version'];
		} //end if
		//--
		if(self::checkSourceParser() === true) {
			$version = (string) SmartAdapterTextTranslations::getTranslationsVersion();
			if((string)$version == '') {
				$version = (string) date('Y-m-d');
				Smart::log_warning('SmartAdapterTextTranslations::getTranslationsVersion() must return a non-empty string ...');
			} //end if
		} else {
			Smart::log_warning('SmartAdapterTextTranslations::getTranslationsVersion() must be defined ...');
			$version = (string) date('Y-m-d');
		} //end if
		//--
		self::$cache['translations:persistent-cache-version'] = (string) $version;
		//--
		return (string) self::$cache['translations:persistent-cache-version'];
		//--
	} //END FUNCTION
	//=====


	//=====
	// try to get from (in this order): Internal (in-memory) cache ; Persistent Cache ; Source
	private static function getFromOptimalPlace(?string $y_language, ?string $y_area, ?string $y_subarea) {
		//-- normalize params
		$y_language = (string) $y_language;
		$y_area = (string) $y_area;
		$y_subarea = (string) $y_subarea;
		//-- built the cache key
		$the_cache_key = (string) $y_language.'.'.$y_area.'.'.$y_subarea; // must use . as separator as it is the only character that is not allowed in lang / area / sub-area but is allowed in persistent cache
		//-- try to get from internal (in-memory) cache
		$translations = array();
		if(array_key_exists('translations@'.$the_cache_key, self::$cache)) {
			$translations = (array) self::$cache['translations@'.$the_cache_key];
		} //end if
		if(Smart::array_size($translations) > 0) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Get Text from Internal Cache for Key: '.$the_cache_key,
						'data' => 'Content:'."\n".SmartUtils::pretty_print_var($translations)
					]);
				} //end if
			} //end if
			return (array) $translations;
		} //end if
		//-- try to get from persistent cache
		$version_translations = (string) self::getLatestVersion(); // get translations version
		$translations = (array) self::getFromPersistentCache((string)$the_cache_key, (string)$version_translations);
		if(Smart::array_size($translations) > 0) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Get Text from Persistent Cache for Key: '.$the_cache_key,
						'data' => 'Version:'."\n".$version_translations."\n".'Content:'."\n".SmartUtils::pretty_print_var($translations)
					]);
				} //end if
			} //end if
			self::$cache['translations@'.$the_cache_key] = (array) $translations;
			return (array) $translations;
		} //end if
		//-- try to get from source
		$translations = (array) self::getFromSource($y_language, $y_area, $y_subarea);
		if(Smart::array_size($translations) > 0) {
			if(SmartEnvironment::ifInternalDebug()) {
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
						'title' => 'Get Text from Sources for Key: '.$the_cache_key,
						'data' => 'Content:'."\n".SmartUtils::pretty_print_var($translations)
					]);
				} //end if
			} //end if
			self::$cache['translations@'.$the_cache_key] = (array) $translations;
			self::setInPersistentCache((string)$the_cache_key, (array)$translations);
			return (array) $translations;
		} //end if
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***REGIONAL-TEXTS***', [
					'title' => '*** NOT FOUND: the Text from Sources for Key: '.$the_cache_key,
					'data' => 'Content:'."\n".SmartUtils::pretty_print_var($translations)
				]);
			} //end if
		} //end if
		if((string)self::getDefaultLanguage() == (string)$y_language) {
			Smart::log_warning('Cannot get from source Text Translations for Key: '.$the_cache_key); // show this if default language
		} elseif(SmartEnvironment::ifDebug()) {
			Smart::log_notice('The Text Translations Key is not available ; will fallback to default language ['.self::getDefaultLanguage().'] for: '.$the_cache_key); // show this if debug
		} //end if
		return array(); // this is invalid, means not found in any places
		//--
	} //END FUNCTION
	//=====


	//=====
	// try to get from persistent cache if active and cached
	private static function getFromPersistentCache(?string $the_cache_key, ?string $version_translations) {
		//--
		$arr = array();
		//--
		if(SmartPersistentCache::isActive() AND (!SmartPersistentCache::isFileSystemBased()) AND (SmartPersistentCache::isMemoryBased() OR SmartPersistentCache::isDbBased())) {
			//-- if not set translations versions, set them to internal cache :: this will be executed just once per session and is necessary to keep sync between Persistent Cache Translations and Real Translation Sources
			if((string)$version_translations == '') {
				Smart::log_warning('Empty Version for Text Translations ... It is needed for store them in the Persistent Cache !');
			} //end if
			//-- check if persistent cache texts are outdated
			$check_version = true;
			if(array_key_exists('#VERSION#', self::$cache) AND ((string)self::$cache['#VERSION#'] != '')) {
				$check_version = false;
			} elseif((string)$version_translations === (string)SmartPersistentCache::getKey('smart-regional-texts', 'version')) {
				$check_version = false;
			} //end if else
			if($check_version !== false) {
				//-- cleanup the outdated text keys from persistent cache
				SmartPersistentCache::unsetKey('smart-regional-texts', '*');
				//-- re-register in persistent cache the Date and Version (after cleanup)
				if(!SmartPersistentCache::keyExists('smart-regional-texts', 'version')) {
					if(!SmartPersistentCache::keyExists('smart-regional-texts', 'date')) {
						if(SmartPersistentCache::setKey('smart-regional-texts', 'date', 'Cached on: '.date('Y-m-d H:i:s O'))) {
							SmartPersistentCache::setKey('smart-regional-texts', 'version', (string)$version_translations);
						} //end if
					} //end if
				} //end if
				//--
			} else { // text keys in persistent cache appear to be latest version, try to get it
				//--
				self::$cache['#VERSION#'] = (string) $version_translations;
				//--
				$rdata = SmartPersistentCache::getKey('smart-regional-texts', (string)$the_cache_key);
				if($rdata) { // here evaluates if non-false
					$rdata = SmartPersistentCache::varDecode((string)$rdata);
				} //end if
				if(Smart::array_size($rdata) > 0) {
					$arr = (array) $rdata;
				} //end if
				$rdata = ''; // clear
				//--
			} //end if
			//--
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//=====


	//=====
	// try to set to persistent cache if active and non-empty array
	private static function setInPersistentCache(?string $the_cache_key, ?array $y_data_arr) {
		//--
		if(SmartPersistentCache::isActive() AND (!SmartPersistentCache::isFileSystemBased()) AND (SmartPersistentCache::isMemoryBased() OR SmartPersistentCache::isDbBased())) {
			if(Smart::array_size($y_data_arr) > 0) {
				SmartPersistentCache::setKey('smart-regional-texts', (string)$the_cache_key, (string)SmartPersistentCache::varEncode((array)$y_data_arr));
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class Smart Text Translator Object
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		Do not use this object directly by creating a new instance ; You should obtain this object as SmartTextTranslations::getTranslator($area, $subarea) for current language or as SmartTextTranslations::getTranslator($area, $subarea, $custom_language) for a custom language
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartTextTranslations, SmartFrameworkRegistry
 * @version 	v.20231030
 * @package 	Application:Translations
 *
 */
final class SmartTextTranslator {

	// ->

	private $language = '';
	private $area = '';
	private $subarea = '';


	/**
	* @access 		private
	* @internal
	 */
	public function __construct(?string $y_language, ?string $y_area, ?string $y_subarea) {
		//--
		if((string)$y_language != '') {
			$this->language = (string) $y_language;
		} else {
			$this->language = '??';
			Smart::log_warning('Invalid Language for Text Context Translator Area ['.$y_language.']: '.$y_area.' ; SubArea: '.$y_subarea);
		} //end if else
		//--
		if((string)$y_area != '') {
			$this->area = (string) $y_area;
		} else {
			$this->area = 'undefined__area';
			Smart::log_warning('Invalid Area for Text Context Translator Area ['.$y_language.']: '.$y_area.' ; SubArea: '.$y_subarea);
		} //end if else
		//--
		if((string)$y_subarea != '') {
			$this->subarea = (string) $y_subarea;
		} else {
			Smart::log_warning('Invalid Sub-Area for Text Context Translator Area['.$y_language.']: '.$y_area.' ; SubArea: '.$y_subarea);
			$this->subarea = 'undefined__subarea';
		} //end if
		//--
	} //END FUNCTION


	/**
	* @access 		private
	* @internal
	 */
	public function getinfo() {
		//--
		return [
			'language' 	=> (string) $this->language,
			'area' 		=> (string) $this->area,
			'sub-area' 	=> (string) $this->subarea
		];
		//--
	} //END FUNCTION


	/**
	* Get the Text Translation
	* @return STRING the Translated Text or Fallback Text to Default Language
	 */
	public function text(?string $y_textkey, ?string $y_fallback_language='@default', bool $y_ignore_empty=false) {
		//--
		// texts are returned as raw, they must be escaped when used with HTML or JS
		//--
		if((string)$y_textkey == '') {
			Smart::log_warning('Empty Key for Text Context Translator - Area: '.$this->area.' ; SubArea: '.$this->subarea);
			return '{Empty Translation Key}';
		} //end if
		//--
		if((string)$y_fallback_language == '@default') {
			$y_fallback_language = (string) SmartTextTranslations::getDefaultLanguage();
		} //end if else
		//--
		$text = (string) SmartTextTranslations::getTranslationByKey($this->area, $this->subarea, (string)$y_textkey, $this->language);
		if(((string)trim((string)$text) == '') AND ((string)$y_fallback_language != '') AND ((string)$y_fallback_language != (string)$this->language)) {
			$text = (string) SmartTextTranslations::getTranslationByKey($this->area, $this->subarea, (string)$y_textkey, (string)$y_fallback_language);
		} //end if
		if((string)trim((string)$text) == '') {
			if($y_ignore_empty !== true) {
				Smart::log_warning('Undefined Key: ['.$y_textkey.'] for Text Context Translator ['.$this->language.'] - Area: '.$this->area.' ; SubArea: '.$this->subarea);
				$text = '{Undefined Translation Key ['.$this->language.']: '.$y_textkey.'}';
			} else {
				$text = '';
			} //end if else
		} //end if
		//--
		return (string) $text;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== INTERFACE START
//=====================================================================================


/**
 * Abstract Inteface Smart Adapter Text Translations
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20231030
 * @package 	development:Application
 *
 */
interface SmartInterfaceAdapterTextTranslations {

	// :: INTERFACE
	// The extended object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE MAY NOT WORK as Expected !!!


	//=====
	/**
	 * Get Regional Text containing the Latest Version of the Translations
	 * This function must implement a way to get last version string to validate Text Translations.
	 * If a version cannot be provided, must return (string) date('Y-m-d') and this way the texts persistent cache will be re-validated daily.
	 * If a real version can be provided it is the best, so persistent cache would be re-validated just upon changes !
	 * RETURN: a non-empty string the provides the latest version string of the current texts translations
	 */
	public static function getTranslationsVersion();
	//=====


	//=====
	/**
	 * Get Regional Text Translation from Source by: Language, Area, Subarea
	 * This function must implement a Text Translations parser.
	 * It can be implemented to read from one of the variety of sources: Arrays, INI, YAML, XML, JSON, SQLite, PostgreSQL, MySQL, MongoDB, GetText, ...
	 * RETURN: an associative array as [key => value] for the specific translation set
	 */
	public static function getTranslationsFromSource(?string $the_lang, ?string $y_area, ?string $y_subarea);
	//=====


	//=====
	/**
	 * Register the usage (increment counter or register in logs) for a Regional Text Translation into Source or alternate source by: Language, Area, Subarea, Key
	 * This function must implement a way to increment or register the usage of every used pair of Language/Area/Subarea/Key as it was used to help the cleanup of unused translations.
	 * This function will operate only in DEV mode only (SmartEnvironment::ifDevMode() === true) ; for filesystem based adapters must also set in init.php: const SMART_FRAMEWORK__DEBUG__TEXT_TRANSLATIONS = true;
	 * It can be implemented to write into one of the variety of sources: Text/CSV, Database, ...
	 * RETURN: N/A
	 */
	public static function setTranslationsKeyUsageCount(?string $the_lang, ?string $y_area, ?string $y_subarea, ?string $y_textkey);
	//=====


} //END INTERFACE


//=====================================================================================
//===================================================================================== INTERFACE END
//=====================================================================================


//end of php code
