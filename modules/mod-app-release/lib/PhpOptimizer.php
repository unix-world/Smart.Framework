<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// PHP Code Optimizer
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
	// v.20250107


	//====================================================
	// php strip
	public static function strip_code(?string $y_file) {
		//--
		$y_file = (string) $y_file;
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$y_file, false); // allow absolute path
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
		if(!method_exists('StripCode', 'should_strip_php_code')) {
			Smart::raise_error(
				'Method Not Found: StripCode::should_strip_php_code()',
				'A required PHP Method Not Found: StripCode::should_strip_php_code() in StripCode.php'
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
		$out = (string) php_strip_whitespace((string)$file);
		if(StripCode::should_strip_php_code((string)$out) === false) { // {{{SYNC-SMART-PHP-OPTIMIZER-TAG-RETURNTYPEWILLCHANGE}}}
			$out = (string) file_get_contents((string)$file, false); // return the un-min. file
		} //end if
		//--
		return (string) $out; // return min.
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
		} else {
			$err = 'ERROR: PHP-Lint / NO BINARY Defined ... it is mandatory !'; // for PHP always a lint is required !!
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// php remove start and end tag
	public static function check_and_remove_start_end_tags(?string $y_php_code) {
		//--
		$y_php_code = (string) trim((string)$y_php_code);
		if((string)$y_php_code == '') {
			return '';
		} //end if
		//--
		if((string)substr((string)$y_php_code, 0, 5) != '<'.'?php') {
			return ''; // invalid !
		} //end if
		$y_php_code = (string) trim((string)substr((string)$y_php_code, 5));
		//--
		$y_php_code = (string) trim((string)$y_php_code);
		if((string)substr((string)$y_php_code, -2, 2) == '?'.'>') {
			$y_php_code = (string) substr((string)$y_php_code, 0, -2);
		} //end if
		//--
		return (string) trim((string)$y_php_code);
		//--
	} //END FUNCTION
	//====================================================


} //END FUNCTION


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
