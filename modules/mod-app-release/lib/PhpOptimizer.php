<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// PHP Optimizer
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
 * PHP Code Optimizer for Software Releases
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
 * @depends: external: php ; constants: TASK_APP_RELEASE_CODEPACK_PHP_BIN
 *
 * @access 		private
 * @internal
 *
 */
final class PhpOptimizer {

	// ::
	// v.20210511


	//====================================================
	// php strip
	public static function strip_code(?string $y_file) {
		//--
		$y_file = (string) $y_file;
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($y_file, 'no');
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
		if(!method_exists('StripCode', 'strip_php_code')) {
			Smart::raise_error(
				'Method Not Found: StripCode::strip_php_code()',
				'A required PHP Method Not Found: StripCode::strip_php_code() in StripCode.php'
			);
		} //end if
		//--
		return (string) StripCode::strip_php_code((string)$y_file);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// php minify
	public static function minify_code(?string $file) {
		//--
		if((string)$file == '') {
			return '';
		} //end if
		//--
		if(!SmartFileSystem::is_type_file($file)) {
			return '';
		} //end if
		//--
		return (string) php_strip_whitespace((string)$file);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// php lint
	public static function lint_code(?string $y_script_path) {
		//--
		$y_script_path = (string) trim((string)$y_script_path);
		//--
		$err = '';
		//--
		if((string)$y_script_path == '') {
			$err = 'ERROR: PHP-Lint / Empty Script Path';
		} elseif(defined('TASK_APP_RELEASE_CODEPACK_PHP_BIN')) {
			if(AppCodeUtils::checkIfExecutable((string)TASK_APP_RELEASE_CODEPACK_PHP_BIN) === true) {
				$parr = (array) SmartUtils::run_proc_cmd(
					(string) escapeshellcmd((string)TASK_APP_RELEASE_CODEPACK_PHP_BIN).' -l '.escapeshellarg((string)$y_script_path),
					null,
					null,
					null
				);
				$exitcode = $parr['exitcode']; // don't make it INT !!!
				$lint_content = (string) $parr['stdout'];
				$lint_errors = (string) $parr['stderr'];
				if(($exitcode !== 0) OR ((string)$lint_errors != '')) { // exitcode is zero (0) on success and no stderror
					$err = 'ERROR: PHP-Lint Failed with ExitCode['.$exitcode.'] on this File: '.$y_script_path."\n".$lint_errors;
				} //end if
			} else {
				$err = 'ERROR: PHP-Lint / BINARY NOT Found: '.TASK_APP_RELEASE_CODEPACK_PHP_BIN;
			} //end if
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//====================================================


} //END FUNCTION


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
