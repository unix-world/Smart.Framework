<?php
// [LIB - Smart.Framework / YAML Text Translations Parser]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// REQUIRED
//======================================================
// Smart-Framework - Parse Regional Text # YAML
define('SMART_FRAMEWORK__INFO__TEXT_TRANSLATIONS_ADAPTER', 'YAML: File based');
//======================================================

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class App.Custom.TextTranslationsAdapter.Yaml - YAML files based text translations adapter (default).
 * This class should not be used directly it is just an adapter for the SmartTextTranslations. Use SmartTextTranslations to get translations not this class
 *
 * To use your own custom adapter for the text translations in Smart.Framework you have to build it by implementing the SmartInterfaceAdapterTextTranslations interface and define it in etc/init.php at the begining such as: define('SMART_FRAMEWORK_TRANSLATIONS_ADAPTER_CUSTOM', 'modules/app/translations-custom-adapter.php');
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartFileSystem, SmartFileSysUtils, SmartYamlConverter, SmartTextTranslations ; constants: SMART_FRAMEWORK_RELEASE_VERSION, SMART_FRAMEWORK_RELEASE_TAGVERSION, SMART_APP_MODULES_RELEASE, SMART_FRAMEWORK__INFO__TEXT_TRANSLATIONS_ADAPTER
 * @version 	v.20221220
 * @package 	Application:Translations:Adapters:Yaml
 *
 */
final class SmartAdapterTextTranslations implements SmartInterfaceAdapterTextTranslations {

	// ::


	//==================================================================
	// This returns the last update version of the translations
	public static function getTranslationsVersion() {
		//--
		$version = 'Smart.Framework :: '.(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : '').' '.(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '');
		//--
		if(defined('SMART_APP_MODULES_RELEASE')) {
			$version .= "\n".'App.Modules :: '.SMART_APP_MODULES_RELEASE;
		} //end if
		if(defined('SMART_FRAMEWORK__INFO__TEXT_TRANSLATIONS_ADAPTER')) {
			$version .= "\n".'App.Adapter :: '.SMART_FRAMEWORK__INFO__TEXT_TRANSLATIONS_ADAPTER;
		} //end if
		//--
		return (string) trim('#TextTranslations::Version#'."\n".$version."\n".'#.#');
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// This reads and parse the YAML translation files by language, area and sub-area
	public static function getTranslationsFromSource(?string $the_lang, ?string $y_area, ?string $y_subarea) {
		//--
		$the_lang = (string) strtolower((string)Smart::safe_varname((string)$the_lang)); // from camelcase to lower
		if(((string)$the_lang == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_lang))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation Language: '.$the_lang);
			return array();
		} //end if
		//--
		$y_area = (string) Smart::safe_filename((string)$y_area);
		if(((string)$y_area == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$y_area))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation Area: '.$y_area);
			return array();
		} //end if
		//--
		$y_subarea = (string) Smart::safe_filename((string)$y_subarea);
		if(((string)$y_subarea == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$y_subarea))) {
			Smart::log_warning(__METHOD__.'() :: Invalid/Empty parameter for Translation SubArea: '.$y_subarea);
			return array();
		} //end if
		//--
		$is_custom_core_en = false;
		if(defined('SMART_FRAMEWORK_TRANSLATIONS_BUILTIN_CUSTOM')) {
			if(SMART_FRAMEWORK_TRANSLATIONS_BUILTIN_CUSTOM === true) {
				$is_custom_core_en = true;
			} //end if
		} //end if
		//--
		if((string)substr((string)$y_area, 0, 1) == '@') {
			if(((string)$the_lang == 'en') AND ($is_custom_core_en !== true)) {
				$fdb_dir = 'lib/app/translations/'; // @core translations for EN language
			} else {
				$fdb_dir = (string) 'modules/app/translations/'; // @core translations for other languages
			} //end if else
			$fdb_template = (string) strtolower($y_area.'/'.$y_subarea.'-'.$the_lang);
		} else { // translations for other modules in: modules/{$y_area}/translations/ ; where {$y_area} can be: 'app', 'mod-something-else', ...
			$fdb_dir = (string) Smart::safe_pathname('modules/'.Smart::safe_filename((string)$y_area).'/translations/');
			$fdb_template = (string) strtolower((string)$y_subarea.'-'.$the_lang);
		} //end if else
		//--
		$fdb_file = (string) $fdb_dir.$fdb_template.'.yaml';
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$fdb_file);
		//--
		if(!SmartFileSystem::is_type_dir((string)$fdb_dir)) {
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
	// This will register the usage of every translation as pair of language, area and sub-area, key ; if not dev mode will not register
	public static function setTranslationsKeyUsageCount(?string $the_lang, ?string $y_area, ?string $y_subarea, ?string $y_textkey) {
		//--
		if(SmartEnvironment::ifDevMode() !== true) {
			return; // this can be used only in DEV mode
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK__DEBUG__TEXT_TRANSLATIONS')) {
			return; // only go below if this has been explicit defined
		} //end if
		//--
		if(!defined('SMART_ERROR_LOGDIR')) {
			return;
		} //end if
		if(((string)trim((string)SMART_ERROR_LOGDIR) == '') OR ((string)SMART_ERROR_LOGDIR == '/')) { // must not be empty or root path
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)SMART_ERROR_LOGDIR, false)) { // allow absolute paths here as the SMART_ERROR_LOGDIR is absolute
			return;
		} //end if
		if((string)substr((string)SMART_ERROR_LOGDIR, -1, 1) != '/') { // must end in slash
			return;
		} //end if
		//--
		$the_translations_area = '';
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$the_translations_area = 'tsk/';
			} else {
				$the_translations_area = 'adm/';
			} //end if else
		} else {
			$the_translations_area = 'idx/';
		} //end if else
		//--
		$logpath = (string) SMART_ERROR_LOGDIR.$the_translations_area.Smart::safe_filename('yaml-translations-usage-'.date('Y-m-d@H').'.tab.tsv');
		if(!SmartFileSysUtils::checkIfSafePath((string)$logpath, false)) { // allow absolute paths here as the SMART_ERROR_LOGDIR is absolute
			return;
		} //end if
		//--
		@file_put_contents(
			(string) $logpath,
			(string) '1'."\t".$the_lang."\t".$y_area."\t".$y_subarea."\t".$y_textkey."\t".str_replace(["\t", "\n", "\r"], ' ', (string)'['.SmartUtils::get_server_current_request_method().']'.SmartUtils::get_server_current_request_uri())."\n",
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
