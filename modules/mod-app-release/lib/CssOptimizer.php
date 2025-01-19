<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// CSS Code Optimizer
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
 * CSS Code Optimizer for Software Releases
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
 * @depends external: node+uglifyCss ; constants: TASK_APP_RELEASE_CODEPACK_NODEJS_BIN, TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS
 *
 * @access 		private
 * @internal
 *
 */
final class CssOptimizer {

	// ::
	// v.20250107


	//====================================================
	// css strip
	public static function strip_code(?string $y_file) {
		//--
		$y_file = (string) trim((string)$y_file);
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
		if(!method_exists('StripCode', 'strip_css_code')) {
			Smart::raise_error(
				'Method Not Found: StripCode::strip_css_code()',
				'A required PHP Method Not Found: StripCode::strip_css_code() in StripCode.php'
			);
		} //end if
		//--
		return (string) StripCode::strip_css_code((string)$y_file);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// css minify
	public static function minify_code(?string $y_stylesheet_path) {
		//--
		$y_stylesheet_path = (string) trim((string)$y_stylesheet_path);
		$y_stylesheet_path = (string) Smart::real_path((string)$y_stylesheet_path);
		//--
		$enc_content = '';
		$err = '';
		//--
		if((string)$y_stylesheet_path == '') {
			$err = 'ERROR: CssOptimizer/Minify / Empty Path ...';
		} elseif((defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN')) && (defined('TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS'))) {
			if(AppCodeUtils::checkIfExecutable((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN) === true) {
				if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS)) {
					if(SmartFileSystem::have_access_read((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS)) {
						//--
						$exitcode = -1;
						$enc_errors = '';
						//--
						$parr = (array) SmartUtils::run_proc_cmd( // {{{SYNC-NODEJS-MINIFY-OPTIONS}}}
							(string) escapeshellcmd((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN).' --no-addons --no-deprecation --no-warnings --no-global-search-paths '.Smart::real_path((string)TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS).' '.escapeshellarg((string)$y_stylesheet_path),
							null,
							null,
							null
						);
						$exitcode = $parr['exitcode']; // don't make it INT !!!
						$enc_errors = (string) $parr['stderr'];
						$enc_content = (string) $parr['stdout'];
						//--
						if(($exitcode === 0) AND ((string)$enc_errors == '')) { // exitcode is zero (0) on success and no stderror
							//--
							// OK ; $enc_content = (string) preg_replace((string)APPCODE_REGEX_STRIP_MULTILINE_CSS_COMMENTS, ' ', (string)$enc_content); // remove multi-line comments if not removed by utility !!!
							//--
						} else {
							//--
							$err = 'ERROR: CssOptimizer/Minify Failed with ExitCode['.$exitcode.'] on this File: '.$y_stylesheet_path."\n".$enc_errors;
						} //end if
						//--
						$exitcode = -1;
						$enc_errors = '';
						//--
					} else {
						$err = 'ERROR: CssOptimizer/Minify MODULE is NOT Readable: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS;
					} //end if else
				} else {
					$err = 'ERROR: CssOptimizer/Minify MODULE NOT Found: '.TASK_APP_RELEASE_CODEPACK_NODE_MODULE_MINIFY_CSS;
				} //end if
			} else {
				$err = 'ERROR: CssOptimizer/Minify / BINARY NOT Found: '.TASK_APP_RELEASE_CODEPACK_NODEJS_BIN;
			} //end if
		} else {
			$err = 'ERROR: CssOptimizer/Minify / Incomplete Configuration ...';
		} //end if else
		//--
		return array('content' => (string)$enc_content, 'error' => (string)$err);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// css lint ; TODO: find a real css linter !!
	public static function lint_code(?string $y_script_path) {
		//--
		$err = '';
		//--
		$test = (string) trim((string)self::strip_code((string)$y_script_path)); // just pass through strip code like a filter ... must not be empty
		if((string)$test == '') {
			$err = 'ERROR: CSS-Lint Failed with ExitCode[-1] on this File: '.$y_script_path."\n".'CSS Content is Empty, it was not valid ...';
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
