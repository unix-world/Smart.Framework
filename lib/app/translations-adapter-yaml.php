<?php
// [LIB - Smart.Framework / YAML Text Translations Parser]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// REQUIRED
//======================================================
// Smart-Framework - Parse Regional Text
// DEPENDS:
//	* Smart::
//	* SmartFileSystem::
//	* SmartFileSysUtils::
//	* SmartYamlConverter->
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

define('SMART_FRAMEWORK__INFO__TEXT_TRANSLATIONS_ADAPTER', 'YAML: File based');

/**
 * Class App.Custom.TextTranslationsAdapter.Yaml - YAML files based text translations adapter (default).
 *
 * To use your own custom adapter for the text translations in Smart.Framework you have to build it by implementing the SmartInterfaceAdapterTextTranslations interface and define it in etc/init.php at the begining such as: define('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM', 'modules/app/translations-custom-adapter.php');
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	-
 * @version 	v.20210428
 * @package 	Application
 *
 */
final class SmartAdapterTextTranslations implements SmartInterfaceAdapterTextTranslations {

	// ::


	//==================================================================
	// This reads and parse the YAML translation files by language, area and sub-area
	public static function getTranslationsFromSource($the_lang, $y_area, $y_subarea) {
		//--
		$the_lang = (string) strtolower((string)Smart::safe_varname((string)$the_lang)); // from camelcase to lower
		if(((string)$the_lang == '') OR (!SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$the_lang))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation Language: '.$the_lang);
			return array();
		} //end if
		//--
		$y_area = (string) Smart::safe_filename((string)$y_area);
		if(((string)$y_area == '') OR (!SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$y_area))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation Area: '.$y_area);
			return array();
		} //end if
		//--
		$y_subarea = (string) Smart::safe_filename((string)$y_subarea);
		if(((string)$y_subarea == '') OR (!SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$y_subarea))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation SubArea: '.$y_subarea);
			return array();
		} //end if
		//--
		if(substr((string)$y_area, 0, 1) == '@') {
			if((string)$the_lang == 'en') {
				$fdb_dir = 'lib/app/translations/';
			} else { // default is: modules/app/translations/
				$fdb_dir = (string) SMART_FRAMEWORK_LANGUAGES_CACHE_DIR;
			} //end if else
			$fdb_template = (string) strtolower($y_area.'/'.$y_subarea.'-'.$the_lang);
		} else { // $y_area can be: apps, mod-something, ...
			$fdb_dir = (string) Smart::safe_pathname('modules/'.$y_area.'/translations/');
			$fdb_template = (string) strtolower($y_subarea.'-'.$the_lang);
		} //end if else
		//--
		$fdb_file = (string) $fdb_dir.$fdb_template.'.yaml';
		SmartFileSysUtils::raise_error_if_unsafe_path($fdb_file);
		//--
		if(!SmartFileSystem::is_type_dir($fdb_dir)) {
			//--
			// INFO: To be able to fallback to the default language, don't make this error FATAL ERROR except if this is the default language selected
			//--
			if((string)SmartTextTranslations::getDefaultLanguage() == (string)$the_lang) {
				Smart::raise_error(
					'Invalid Language Dir: '.$fdb_dir.' :: for: '.$y_area.'@'.$y_subarea,
					'Invalid Language Dir for: '.$y_area.'@'.$y_subarea // msg to display
				);
			} //end if
			return array();
		} //end if
		//--
		if(!SmartFileSystem::is_type_file($fdb_file)) {
			//--
			// INFO: To be able to fallback to the default language, don't make this error FATAL ERROR except if this is the default language selected
			//--
			if((string)SmartTextTranslations::getDefaultLanguage() == (string)$the_lang) {
				Smart::raise_error(
					'Invalid Language File: '.$fdb_file,
					'Invalid Language File: '.$fdb_template // msg to display
				);
			} //end if
			return array();
			//--
		} //end if
		//--
		$fcontent = (string) SmartFileSystem::read($fdb_file);
		$arr = (new SmartYamlConverter())->parse((string)$fcontent);
		$fcontent = '';
		//--
		if(!is_array($arr)) {
			Smart::raise_error(
				'Parse Error / TRANSLATIONS :: Language File: '.$fdb_file,
				'Parse Error / TRANSLATIONS :: Language File: '.$fdb_template // msg to display
			);
			return array();
		} //end if
		//--
		if(!is_array($arr['TRANSLATIONS'])) {
			Smart::raise_error(
				'Parse Error / TRANSLATIONS :: Language File: '.$fdb_file,
				'Parse Error / TRANSLATIONS :: Language File: '.$fdb_template // msg to display
			);
			return array();
		} //end if
		if(Smart::array_size($arr['TRANSLATIONS'][(string)$y_subarea]) <= 0) {
			Smart::log_warning('Parse Error / TRANSLATIONS.'.$y_subarea.' :: Language File: '.$fdb_template);
			return array();
		} //end if
		//--
		return (array) $arr['TRANSLATIONS'][(string)$y_subarea];
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// This returns the last update version of the translations
	public static function getTranslationsVersion() {
		//--
		$version = 'Smart.Framework :: '.SMART_FRAMEWORK_RELEASE_VERSION.' '.SMART_FRAMEWORK_RELEASE_TAGVERSION;
		//--
		if(defined('SMART_APP_MODULES_RELEASE')) {
			$version .= "\n".'App.Modules :: '.SMART_APP_MODULES_RELEASE;
		} //end if
		//--
		return (string) trim('#TextTranslations::Version#'."\n".$version."\n".'#.#');
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// This will register the usage of every translation as pair of language, area and sub-area, key ; if not dev mode will not register
	public static function setTranslationsKeyUsageCount($the_lang, $y_area, $y_subarea, $y_textkey) {
		//--
		if(SmartFrameworkRegistry::ifProdEnv() === true) {
			return; // this can be used only in DEV mode
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK__DEBUG__TEXT_TRANSLATIONS')) {
			return; // only go below if this has been explicit defined
		} //end if
		//--
		if(SmartFrameworkRegistry::isAdminArea() === true) {
			if(SmartFrameworkRegistry::isTaskArea() === true) {
				$the_translations_area = 'tsk';
			} else {
				$the_translations_area = 'adm';
			} //end if else
		} else {
			$the_translations_area = 'idx';
		} //end if else
		//--
		@file_put_contents(
			(string) Smart::safe_pathname('tmp/logs/'.Smart::safe_filename($the_translations_area).'/yaml-translations-usage-'.date('Y-m-d@H').'.tab.tsv'),
			(string) $the_lang."\t".$y_area."\t".$y_subarea."\t".$y_textkey."\t".'1'."\t".str_replace(["\t", "\n", "\r"], ' ', (string)'['.(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '').']'.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''))."\n",
			FILE_APPEND | LOCK_EX
		);
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
