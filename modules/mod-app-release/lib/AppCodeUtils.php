<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// App Code Utils
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT S EXECUTION [T]
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// AppPackUtils free

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * App Code Utils
 *
 * DEPENDS:
 * SmartFileSystem::
 *
 * @access 		private
 * @internal
 *
 */
final class AppCodeUtils {

	// ::
	// v.20210511

	private const CODEPACK_INI = 'etc/appcodepack/appcodepack.ini';
	private const CODEPACK_SETTINGS = 'etc/appcodepack/appcodepack.yaml';

	private const CODEPACK_UGLIFY_JS = 'modules/mod-app-release/node-modules/uglify-js/bin/uglifyjs';
	private const CODEPACK_UGLIFY_CSS = 'modules/mod-app-release/node-modules/uglifycss/uglifycss';

	public const APPCODEPACK_DESTINATION_DIR = 'tmp/#APP-RELEASE#/';
	public const APPCODEPACK_SUFFIX_OPTIMIZATIONS = '#_OPTIMIZED_#/';


	//====================================================
	public static function getSfVersion() {
		//--
		return (string) (defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '').'-'.(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : '').' @ '.(defined('SMART_SOFTWARE_APP_NAME') ? SMART_SOFTWARE_APP_NAME : '');
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function getVersion() {
		//--
		return (string) SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// executable path must not contain any space ; spaces are considered between executable and options: ex: `/path/to/exe --opts` will check if executable the: `/path/to/exe`
	public static function checkIfExecutable(?string $utility) {
		//--
		$utility = (string) trim((string)$utility);
		if((string)$utility == '') {
			return false;
		} //end if
		//--
		if(!SmartFileSystem::is_type_file((string)$utility)) {
			return false;
		} //end if
		if(!SmartFileSystem::have_access_executable((string)$utility)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function parseIniSettings() {
		//--
		$out = 0;
		//--
		if(!SmartFileSystem::is_type_file((string)self::CODEPACK_INI)) {
			return 'INI Settings File Not Found: '.self::CODEPACK_INI;
		} //end if
		//--
		$ini = (string) SmartFileSystem::read((string)self::CODEPACK_INI);
		if((string)trim((string)$ini) == '') {
			return 'Empty INI Settings File: '.self::CODEPACK_INI;
		} //end if
		//--
		$arr = parse_ini_string($ini, false, INI_SCANNER_RAW); // mixed: array or false on failure
		if(!is_array($arr)) {
			return 'Invalid INI Settings File: '.self::CODEPACK_INI.' # INI Parse Errors';
		} //end if
		//--
		$valid_arr_settings = [
			'OPTIMIZATIONS_MAX_RUN_TIMEOUT' 		=> true,
			'TASK_APP_RELEASE_CODEPACK_PHP_BIN' 	=> true,
			'TASK_APP_RELEASE_CODEPACK_NODEJS_BIN' 	=> true,
			'TASK_APP_RELEASE_CODEPACK_MOZJS_BIN' 	=> false, // optional
		];
		foreach($valid_arr_settings as $key => $val) {
			if($val === true) { // mandatory
				if((!isset($arr[(string)$key])) OR ((string)trim((string)$arr[(string)$key]) == '')) {
					return 'Missing or Empty INI Settings from File: '.self::CODEPACK_INI.' # '.$key;
					break;
				} //end if
				if(defined((string)$key)) {
					return 'INI Settings: '.self::CODEPACK_INI.' Constant Already Defined # '.$key;
					break;
				} //end if
				$out += (int) define((string)$key, (string)trim((string)$arr[(string)$key]));
			} else { // optionals
				if((isset($arr[(string)$key])) AND ((string)trim((string)$arr[(string)$key]) != '')) {
					if(defined((string)$key)) {
						return 'INI Settings: '.self::CODEPACK_INI.' Constant Already Defined # '.$key;
						break;
					} //end if
					define((string)$key, (string)trim((string)$arr[(string)$key]));
				} //end if
			} //end if else
		} //end if
		//--
		if(defined('TASK_APP_RELEASE_CODEPACK_PHP_BIN')) {
			if(defined('TASK_APP_RELEASE_CODEPACK_PHP_VERSION')) {
				return 'TASK_APP_RELEASE_CODEPACK_PHP_VERSION # CONSTANT ALREADY DEFINED';
			} //end if
			if(self::checkIfExecutable((string)TASK_APP_RELEASE_CODEPACK_PHP_BIN)) {
				define('TASK_APP_RELEASE_CODEPACK_PHP_VERSION', (string)shell_exec((string)TASK_APP_RELEASE_CODEPACK_PHP_BIN.' --version'));
			} //end if
		} //end if
		//--
		if(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN')) {
			if(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION')) {
				return 'TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION # CONSTANT ALREADY DEFINED';
			} //end if
			if(self::checkIfExecutable((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN)) {
				define('TASK_APP_RELEASE_CODEPACK_NODEJS_VERSION', (string)shell_exec((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN.' --version'));
			} //end if
		} //end if
		//--
		if(defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS')) {
			return 'TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS # CONSTANT ALREADY DEFINED';
		} //end if
		if(SmartFileSystem::is_type_file((string)self::CODEPACK_UGLIFY_JS)) {
			define('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS',  (string)self::CODEPACK_UGLIFY_JS);
		} //end if
		//--
		if(defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS')) {
			return 'TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS # CONSTANT ALREADY DEFINED';
		} //end if
		if(SmartFileSystem::is_type_file((string)self::CODEPACK_UGLIFY_CSS)) {
			define('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS',  (string)self::CODEPACK_UGLIFY_CSS);
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function parseYamlSettings(?string $appid) {
		//--
		$out = 0;
		//--
		$appid = (string) trim((string)$appid);
		$test_err_appid = (string) AppNetUnPackager::unpack_valid_app_id((string)$appid);
		if((string)$test_err_appid != '') {
			return 'APP ID ERROR: '.$test_err_appid;
		} //end if
		if(defined('APPCODEPACK_APP_ID')) {
			return 'APPCODEPACK_APP_ID # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APPCODEPACK_APP_ID', (string)$appid);
		//--
		if(!SmartFileSystem::is_type_file((string)self::CODEPACK_SETTINGS)) {
			return 'YAML Settings File Not Found: '.self::CODEPACK_SETTINGS;
		} //end if
		//--
		$yaml = (string) SmartFileSystem::read((string)self::CODEPACK_SETTINGS);
		if((string)trim((string)$yaml) == '') {
			return 'Empty YAML Settings File: '.self::CODEPACK_SETTINGS;
		} //end if
		//--
		$yobj = new SmartYamlConverter(false); // do not log YAML Errors
		$arr = (array) $yobj->parse((string)$yaml);
		$err = (string) $yobj->getError();
		$yobj = null;
		if((string)$err != '') {
			return 'Invalid YAML Settings File: '.self::CODEPACK_SETTINGS.' # YAML Errors: '.$err;
		} //end if
		//--
		if(Smart::array_size($arr) <= 0) {
			return 'YAML SETTINGS PARSE ERROR: NOT ARRAY';
		} //end if
		//--
		if((!isset($arr['APP-RELEASE'])) OR (Smart::array_size($arr['APP-RELEASE']) <= 0)) {
			return 'APP-RELEASE YAML SETTINGS ERROR';
		} //end if
		$arr = (array) $arr['APP-RELEASE'];
		//--
		if((!isset($arr[(string)APPCODEPACK_APP_ID])) OR (Smart::array_size($arr[(string)APPCODEPACK_APP_ID]) <= 0)) {
			return 'APP-RELEASE/APPID YAML SETTINGS ERROR';
		} //end if
		$arr = (array) $arr[(string)APPCODEPACK_APP_ID];
		//--
		if((!isset($arr['files'])) OR (Smart::array_size($arr['files']) <= 0) OR (Smart::array_type_test($arr['files']) != 1)) {
			return 'APP-RELEASE/APPID/FILES YAML SETTINGS ERROR # must be array: non-empty, non-associative';
		} //end if
		if(defined('APP_DEPLOY_FILES')) {
			return 'APP_DEPLOY_FILES # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_FILES', (string)Smart::json_encode((array)$arr['files']));
		//--
		if((!isset($arr['folders'])) OR (Smart::array_size($arr['folders']) <= 0) OR (Smart::array_type_test($arr['folders']) != 1)) {
			return 'APP-RELEASE/APPID/FOLDERS YAML SETTINGS ERROR # must be array: non-empty, non-associative';
		} //end if
		if(defined('APP_DEPLOY_FOLDERS')) {
			return 'APP_DEPLOY_FOLDERS # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_FOLDERS', (string)Smart::json_encode((array)$arr['folders']));
		//--
		if((!isset($arr['deploy-auth-pass'])) OR (!Smart::is_nscalar($arr['deploy-auth-pass'])) OR ((string)trim((string)$arr['deploy-auth-pass']) == '')) {
			return 'APP-RELEASE/APPID/DEPLOY-AUTH-PASS YAML SETTINGS ERROR';
		} //end if
		$password = (string) trim((string)SmartUtils::crypto_blowfish_decrypt((string)$arr['deploy-auth-pass']));
		if((string)$password == '') {
			return 'APP-RELEASE/APPID/DEPLOY-AUTH-PASS YAML SETTINGS ERROR # decode failed';
		} //end if
		if(defined('APP_DEPLOY_AUTH_PASSWORD')) {
			return 'APP_DEPLOY_AUTH_PASSWORD # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_AUTH_PASSWORD', (string)$password);
		//--
		if((!isset($arr['deploy-auth-user'])) OR (!Smart::is_nscalar($arr['deploy-auth-user'])) OR ((string)trim((string)$arr['deploy-auth-user']) == '')) {
			return 'APP-RELEASE/APPID/DEPLOY-AUTH-USER YAML SETTINGS ERROR';
		} //end if
		if((strlen((string)$arr['deploy-auth-user']) < 3) OR (strlen((string)$arr['deploy-auth-user']) > 25)) { // sync with mod auth admins, model
			return 'APP-RELEASE/APPID/DEPLOY-AUTH-USER YAML SETTINGS ERROR # must be between 3 and 25 characters long';
		} //end if
		if(!\preg_match('/^[a-z0-9\.]+$/', (string)$arr['deploy-auth-user'])) { // sync with mod auth admins, model
			return 'APP-RELEASE/APPID/DEPLOY-AUTH-USER YAML SETTINGS ERROR # contains invalid characters ; can contain only: a-z 0-9 .';
		} //end if
		if(defined('APP_DEPLOY_AUTH_USERNAME')) {
			return 'APP_DEPLOY_AUTH_USERNAME # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_AUTH_USERNAME', (string)trim((string)$arr['deploy-auth-user']));
		//--
		if((!isset($arr['deploy-secret'])) OR (!Smart::is_nscalar($arr['deploy-secret'])) OR ((string)trim((string)$arr['deploy-secret']) == '')) {
			return 'APP-RELEASE/APPID/DEPLOY-SECRET YAML SETTINGS ERROR';
		} //end if
		$secret = (string) trim((string)$arr['deploy-secret']);
		if((string)$secret == '') {
			return 'APP-RELEASE/APPID/DEPLOY-SECRET YAML SETTINGS ERROR # decode failed';
		} //end if
		if(defined('APP_DEPLOY_SECRET')) {
			return 'APP_DEPLOY_SECRET # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_SECRET', (string)$secret);
		if(defined('APP_DEPLOY_HASH')) {
			return 'APP_DEPLOY_HASH # CONSTANT ALREADY DEFINED';
		} //end if
		define('APP_DEPLOY_HASH', (string)AppNetUnPackager::unpack_app_hash((string)APP_DEPLOY_SECRET));
		//--
		if((!isset($arr['deploy-urls'])) OR (Smart::array_size($arr['deploy-urls']) <= 0) OR (Smart::array_type_test($arr['deploy-urls']) != 1)) {
			return 'APP-RELEASE/APPID/DEPLOY-URLS YAML SETTINGS ERROR # must be array: non-empty, non-associative';
		} //end if
		for($i=0; $i<Smart::array_size($arr['deploy-urls']); $i++) {
			if((strpos((string)$arr['deploy-urls'][$i], 'http://') !== 0) AND (strpos((string)$arr['deploy-urls'][$i], 'https://') !== 0)) {
				return 'APP-RELEASE/APPID/DEPLOY-URLS YAML SETTINGS ERROR # invalid URL protocol: '.$arr['deploy-urls'][$i];
			} elseif((strpos((string)$arr['deploy-urls'][$i], '|') !== false) OR (strpos((string)$arr['deploy-urls'][$i], ' ') !== false)) {
				return 'APP-RELEASE/APPID/DEPLOY-URLS YAML SETTINGS ERROR # invalid URL characters [ | ]: '.$arr['deploy-urls'][$i];
			} //end if
		} //end for
		if(defined('APP_DEPLOY_URLS')) {
			return 'APP_DEPLOY_URLS # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('APP_DEPLOY_URLS', (string)implode(' | ', (array)$arr['deploy-urls']));
		//--
		if((!isset($arr['deploy-strategy'])) OR (!Smart::is_nscalar($arr['deploy-strategy'])) OR ((string)trim((string)$arr['deploy-strategy']) == '')) {
			return 'APP-RELEASE/APPID/DEPLOY-STRATEGY YAML SETTINGS ERROR';
		} //end if
		switch((string)trim((string)$arr['deploy-strategy'])) {
			case 'comments':
			case 'minify':
				// OK
				break;
			default:
				return 'APP-RELEASE/APPID/DEPLOY-STRATEGY YAML SETTINGS ERROR # invalid value: '.$arr['deploy-strategy'];
		} //end switch
		if(defined('TASK_APP_RELEASE_CODEPACK_MODE')) {
			return 'TASK_APP_RELEASE_CODEPACK_MODE # CONSTANT ALREADY DEFINED';
		} //end if
		$out += (int) define('TASK_APP_RELEASE_CODEPACK_MODE', (string)trim((string)$arr['deploy-strategy']));
		//--
		define('TASK_APP_RELEASE_CODEPACK_APP_DIR', (string)self::APPCODEPACK_DESTINATION_DIR.APPCODEPACK_APP_ID.'/');
		define('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR', (string)TASK_APP_RELEASE_CODEPACK_APP_DIR.self::APPCODEPACK_SUFFIX_OPTIMIZATIONS);
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function getAppsFromYamlSettings() {
		//--
		$yaml = (string) SmartFileSystem::read((string)self::CODEPACK_SETTINGS);
		if((string)trim((string)$yaml) == '') {
			return 'Empty YAML Settings File: '.self::CODEPACK_SETTINGS;
		} //end if
		//--
		$yobj = new SmartYamlConverter(false); // do not log YAML Errors
		$arr = (array) $yobj->parse((string)$yaml);
		$err = (string) $yobj->getError();
		$yobj = null;
		if((string)$err != '') {
			return 'Invalid YAML Settings File: '.self::CODEPACK_SETTINGS.' # YAML Errors: '.$err;
		} //end if
		//--
		if(Smart::array_size($arr) <= 0) {
			return 'YAML SETTINGS PARSE ERROR: NOT ARRAY';
		} //end if
		//--
		if((!isset($arr['APP-RELEASE'])) OR (Smart::array_size($arr['APP-RELEASE']) <= 0)) {
			return 'APP-RELEASE YAML SETTINGS ERROR';
		} //end if
		//--
		$arr = (array) $arr['APP-RELEASE'];
		//--
		$apps = [];
		//--
		foreach($arr as $key => $val) {
			$appid = (string) trim((string)$key);
			$test_err_appid = (string) AppNetUnPackager::unpack_valid_app_id((string)$appid);
			if((string)$test_err_appid != '') {
				return 'APP ID ERROR: '.$test_err_appid;
			} else {
				$apps[(string)$appid] = (string) $appid;
			} //end if else
		} //end foreach
		//--
		return (array) $apps;
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
