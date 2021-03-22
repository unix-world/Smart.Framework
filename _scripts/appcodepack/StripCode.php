<?php
// Strip Code (PHP, JS, CSS)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APPCODEPACK_VERSION')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime AppCodePack in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//--
if(is_file('modules/vendor/MatthiasMullie/autoload.php')) {
	require_once('modules/vendor/MatthiasMullie/autoload.php');
} else {
	throw new Exception('A required PHP file not found: modules/vendor/MatthiasMullie/autoload.php in: '.@basename(__FILE__));
} //end if else
//--


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// Strip CSS, Javascript and PHP code (no comments)
final class StripCode {

	// ::
	// v.20210322


	public static function strip_css_code(string $filePath) {
		//--
		if( // {{{SYNC-CSS-CHEKS}}}
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)\substr((string)$filePath, -4, 4) != '.css') OR // expects: file.css or path/to/file.css
			((int)\strlen((string)$filePath) < 5)
		) {
			return '';
		} //end if
		//--
		$output = '';
		try {
			$cssMinifier = new \MatthiasMullie\Minify\CSS('/* CSS */');
			$cssMinifier->setMaxImportSize(0);
			$cssMinifier->setImportExtensions([]); // no extensions to import, this is a single file saved in place
			$cssMinifier->add($filePath);
			$output = (string) $cssMinifier->minify();
			$cssMinifier = null;
		} catch(Exception $e) {
		//	throw new Exception('CSS Minify Failed: '.$e->getMessage());
			return '';
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


	public static function strip_js_code(string $filePath) {
		//--
		if( // {{{SYNC-CSS-CHEKS}}}
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)\substr((string)$filePath, -3, 3) != '.js') OR // expects: file.js or path/to/file.js
			((int)\strlen((string)$filePath) < 4)
		) {
			return '';
		} //end if
		//--
		$output = '';
		try {
			$jsMinifier = new \MatthiasMullie\Minify\JS('/* Javascript */');
			$jsMinifier->add($filePath);
			$output = (string) $jsMinifier->minify();
			$jsMinifier = null;
		} catch (Exception $e) {
		//	throw new Exception('JS Minify Failed: '.$e->getMessage());
			return '';
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


	public static function strip_php_code(string $filePath) { // expects: file.php
		//--
		if( // {{{SYNC-CSS-CHEKS}}}
			(self::checkFilePath((string)$filePath) !== true) OR
			((string)\substr((string)$filePath, -4, 4) != '.php') OR // expects: file.php or path/to/file.php
			((int)\strlen((string)$filePath) < 5)
		) {
			return '';
		} //end if
		//--
		$output = (string) file_get_contents($filePath);
		if((string)$output == '') {
			return '';
		} //end if
		//--
		$strip = [
			T_COMMENT 		=> true,
			T_DOC_COMMENT 	=> true
		];
		//--
		$tokens = token_get_all($output);
		$output = '';
		if(is_array($tokens)) {
			foreach($tokens as $key => $token) {
				if(!is_array($token)) {
					$output .= (string) $token;
				} elseif(!isset($strip[(string)(isset($token[0]) ? $token[0] : '')])) { // skip strip tokens sa set above
					$output .= (string) (isset($token[1]) ? $token[1] : '');
				} else {
					$output .= "\n";
				} //end if else
			} // end foreach
		} // end if
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


	//===== [PRIVATES]


	private static function checkFilePath(string $filePath) {
		//-- must allow absolute paths
		if( // {{{SYNC-CSS-CHEKS}}}
			((string)$filePath == '') OR
			((string)$filePath == '/') OR
			((string)$filePath == '.') OR
			((string)$filePath == '..') OR
			(strpos((string)$filePath, '..') !== false) OR
			((string)\substr((string)$filePath, 0, 1) == '.')
		) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
