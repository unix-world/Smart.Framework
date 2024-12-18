<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Strip Code (PHP, JS, CSS)
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
 * PHP / Javascript / CSS :: Code Strip for Software Releases
 *
 * DEPENDS:
 * Smart::
 * SmartFileSysUtils::
 * SmartFileSystem::
 * SmartUtils::
 *
 * ShrinkCode->ShrinkJsCode->
 * ShrinkCode->ShrinkCssCode->
 *
 * @access 		private
 * @internal
 *
 */
final class StripCode {

	// ::
	// v.20221222


	//====================================================
	public static function should_strip_php_code($phpcode) {
		//--
		if(
			(defined('SMART_APP_RELEASE_PHP_OPTIMIZER_SKIP') AND (SMART_APP_RELEASE_PHP_OPTIMIZER_SKIP === true))
			OR
			( // {{{SYNC-SMART-PHP-OPTIMIZER-TAG-RETURNTYPEWILLCHANGE}}} ; fix for PHP8.1 tags when executed by PHP 7.4 or 8.0 the special comment
				(strpos((string)$phpcode, ' #'.'[\ReturnTypeWillChange] ') !== false)
				OR
				(strpos((string)$phpcode, ' #'.'[ReturnTypeWillChange] ') !== false)
			)
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function strip_php_code(?string $filePath) { // expects: file.php
		//--
		if(
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)substr((string)$filePath, -4, 4) != '.php') OR // expects: file.php or path/to/file.php
			((int)strlen((string)$filePath) < 5)
		) {
			return '';
		} //end if
		//--
		$output = (string) file_get_contents((string)$filePath, false); // must allow absolute path !!
		if((string)$output == '') {
			return '';
		} //end if
		if(self::should_strip_php_code((string)$output) === false) { // {{{SYNC-SMART-PHP-OPTIMIZER-TAG-RETURNTYPEWILLCHANGE}}}
			return (string) $output;
		} //end if
		//--
		$strip = [
			T_COMMENT 		=> true,
			T_DOC_COMMENT 	=> true
		];
		$tokens = token_get_all($output);
		$output = '';
		if(is_array($tokens)) {
			foreach($tokens as $key => $token) {
				if(!is_array($token)) {
					$output .= (string) $token;
				} elseif(!isset($strip[(string)(isset($token[0]) ? $token[0] : '')])) { // skip strip tokens as set above
					$output .= (string) (isset($token[1]) ? $token[1] : '');
				} else {
					$output .= ''; // previous was "\n"
				} //end if else
			} // end foreach
		} // end if
		//--
		$strip = [
			T_WHITESPACE 	=> true,
		];
		$tokens = token_get_all($output);
		$output = '';
		if(is_array($tokens)) {
			foreach($tokens as $key => $token) {
				if(!is_array($token)) {
					$output .= (string) $token;
				} elseif(!isset($strip[(string)(isset($token[0]) ? $token[0] : '')])) { // skip strip tokens as set above
					$output .= (string) (isset($token[1]) ? $token[1] : '');
				} else {
					if(isset($token[1])) {
						$token[1] = (string) str_replace(["\r\n", "\r"], "\n", (string)$token[1]); // standardize line endings as LF
						$token[1] = (string) str_replace(['    ',  '   '], "\t", (string)$token[1]); // replace 4 or 3 spaces with tab
						$token[1] = (string) str_replace('  ', ' ', (string)$token[1]); // replace 2 spaces with one space
						if(strpos($token[1], "\n") !== false) {
							$tmp_arr = (array) explode("\n", (string)$token[1]);
							$output .= "\n";
							if(count($tmp_arr) > 0) {
								if(isset($tmp_arr[count($tmp_arr)-1])) {
									$output .= (string) $tmp_arr[count($tmp_arr)-1];
								} //end if
							} //end if
							$tmp_arr = null;
						} else {
							$output .= (string) $token[1];
						} //end if
					} //end if
				} //end if else
			} // end foreach
		} //end if
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function strip_js_code(?string $filePath) {
		//--
		if(
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)substr((string)$filePath, -3, 3) != '.js') OR // expects: file.js or path/to/file.js
			((int)strlen((string)$filePath) < 4)
		) {
			return '';
		} //end if
		//--
		$output = '';
		try {
			$output = (new ShrinkJsCode((string)$filePath))->stripCode();
		} catch (Exception $e) {
			$output = '';
			return '';
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public static function strip_css_code(?string $filePath) {
		//--
		if(
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)substr((string)$filePath, -4, 4) != '.css') OR // expects: file.css or path/to/file.css
			((int)strlen((string)$filePath) < 5)
		) {
			return '';
		} //end if
		//--
		$output = '';
		try {
			$output = (new ShrinkCssCode((string)$filePath))->stripCode();
		} catch(Exception $e) {
			$output = '';
			return '';
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION
	//====================================================


	//===== [PRIVATES]


	//====================================================
	private static function checkFilePath(?string $filePath) {
		//--
		return (bool) SmartFileSysUtils::checkIfSafePath((string)$filePath, false); // must allow absolute paths
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
