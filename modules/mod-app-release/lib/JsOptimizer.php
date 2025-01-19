<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// JS Code Optimizer
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION [T]
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// PHP8

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Javascript Code Optimizer for Software Releases
 *
 * DEPENDS:
 * Smart::
 * SmartFileSysUtils::
 * SmartFileSystem::
 * SmartUtils::
 *
 * AppCodeUtils::
 * StripCode::
 *
 * @depends external: node+uglifyJs ; constants: TASK_APP_RELEASE_CODEPACK_NODEJS_BIN, TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS ; optional constants: TASK_APP_RELEASE_CODEPACK_MOZJS_BIN
 *
 * @access 		private
 * @internal
 *
 */
final class JsOptimizer {

	// ::
	// v.20250107


	//====================================================
	// js strip
	public static function strip_code(?string $y_file) {
		//--
		$y_file = (string) trim((string)$y_file);
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$y_file, false); // allow absolute paths
		//--
		if(!SmartFileSystem::is_type_file($y_file)) {
			return '';
		} //end if
		//--
		if(!class_exists('StripCode')) {
			if(!is_file('StripCode.php')) {
				Smart::raise_error(
					'Not Found: StripCode.php',
					'A required PHP File was not Found: StripCode.php'
				);
			} //end if
			require_once('StripCode.php');
		} //end if
		//--
		if(!method_exists('StripCode', 'strip_js_code')) {
			Smart::raise_error(
				'Method Not Found: StripCode::strip_js_code()',
				'A required PHP Method Not Found: StripCode::strip_js_code() in StripCode.php'
			);
		} //end if
		//--
		return (string) StripCode::strip_js_code((string)$y_file);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// js minify
	public static function minify_code(?string $y_script_path, ?string $y_strategy='minify') {
		//--
		$y_script_path = (string) trim((string)$y_script_path);
		$y_script_path = (string) Smart::real_path((string)$y_script_path);
		//--
		$enc_content = '';
		$err = '';
		//--
		$strategy_options = ' -m';
		if((string)$y_strategy == 'strip') {
			$strategy_options = '';
		} //end if else
		//--
		if((string)$y_script_path == '') {
			$err = 'ERROR: JsOptimizer/Minify / Empty Path ...';
		} elseif((defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN')) && (defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS'))) {
			if(AppCodeUtils::checkIfExecutable((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN) === true) {
				if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS)) {
					if(SmartFileSystem::have_access_read((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS)) {
						//--
						$exitcode = -1;
						$enc_errors = '';
						//--
						$parr = (array) SmartUtils::run_proc_cmd( // {{{SYNC-NODEJS-MINIFY-OPTIONS}}} ; for ulifyjs: the --validate option is a bit slow, but may be considered in the future ; at the moment, validation is made via post-minify using node or spidermonkey ...
							(string) escapeshellcmd((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN).' --no-addons --no-deprecation --no-warnings --no-global-search-paths '.Smart::real_path((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS).$strategy_options.' --beautify beautify=false,braces=true,semicolons=true,ascii_only=true -- '.escapeshellarg((string)$y_script_path),
							null,
							null,
							null
						); // [--beautify beautify=false,ascii-only=true] required to preserve safe unicode sequences ; [--screw-ie8] required to dissalow IE8 hacks to support IE<9 which can break other code
						$exitcode = $parr['exitcode']; // don't make it INT !!!
						$enc_content = (string) $parr['stdout'];
						$enc_errors = (string) $parr['stderr'];
						//--
						if(($exitcode === 0) AND ((string)$enc_errors == '')) { // exitcode is zero (0) on success and no stderror
							//-- this is risky for a language like javascript or php and fails if comments are inside strings !!!
							// OK
							//--
						} else {
							//--
							$err = 'ERROR: JsOptimizer/Minify Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$enc_errors;
						} //end if
						//--
						$exitcode = -1;
						$enc_errors = '';
						//--
					} else {
						$err = 'ERROR: JsOptimizer/Minify MODULE is NOT Readable: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS;
					} //end if else
				} else {
					$err = 'ERROR: JsOptimizer/Minify MODULE NOT Found: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_JS;
				} //end if
			} else {
				$err = 'ERROR: JsOptimizer/Minify / BINARY NOT Found: '.TASK_APP_RELEASE_CODEPACK_NODEJS_BIN;
			} //end if
		} else {
			$err = 'ERROR: JsOptimizer/Minify / Incomplete Configuration ...';
		} //end if else
		//--
		return array('content' => (string)$enc_content, 'error' => (string)$err);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// js lint
	public static function lint_code(?string $y_script_path) {
		//--
		$y_script_path = (string) trim((string)$y_script_path);
		//--
		$err = '';
		//--
		$linter = (string) self::getLintBinary();
		//--
		if((string)$y_script_path == '') {
			$err = 'ERROR: Js-Lint / Empty Script Path';
		} elseif((string)$linter != '') {
			if(AppCodeUtils::checkIfExecutable((string)$linter) === true) {
				$parr = (array) SmartUtils::run_proc_cmd(
					(string) escapeshellcmd((string)$linter).' -c '.escapeshellarg((string)$y_script_path),
					null,
					null,
					null
				);
				$exitcode = $parr['exitcode']; // don't make it INT !!!
				$lint_content = (string) $parr['stdout'];
				$lint_errors = (string) $parr['stderr'];
				if(($exitcode !== 0) OR ((string)$lint_errors != '')) { // exitcode is zero (0) on success and no stderror
					$err = 'ERROR: Js-Lint Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$lint_errors;
				} //end if
			} else {
				$err = 'ERROR: Js-Lint / BINARY NOT Found or NOT Executable: '.$linter;
			} //end if
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	private static function getLintBinary() {
		//--
		$linter = '';
		//--
		if(defined('TASK_APP_RELEASE_CODEPACK_MOZJS_BIN') AND ((string)TASK_APP_RELEASE_CODEPACK_MOZJS_BIN != '')) {
			$linter = (string) TASK_APP_RELEASE_CODEPACK_MOZJS_BIN;
		} elseif(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN') AND ((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN != '')) {
			$linter = (string) TASK_APP_RELEASE_CODEPACK_NODEJS_BIN;
		} //end if else
		//--
		return (string) $linter;
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
