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
	// v.20210222.1157

	public static function strip_css_code($y_file) {
		//--
		$y_file = (string) $y_file; // expects: file.css
		//--
		$output = '';
		try {
			$cssMinifier = new \MatthiasMullie\Minify\CSS('/* CSS */');
			$cssMinifier->setImportExtensions([]); // no extensions
			$cssMinifier->add($y_file);
			$output = (string) $cssMinifier->minify();
		} catch (Exception $e) {
			throw new Exception('CSS Minify Failed: '.$e->getMessage());
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


	public static function strip_js_code($y_file) {
		//--
		$y_file = (string) $y_file; // expects: file.js
		//--
		$output = '';
		try {
			$jsMinifier = new \MatthiasMullie\Minify\JS('/* Javascript */');
			$jsMinifier->add($y_file);
			$output = (string) $jsMinifier->minify();
		} catch (Exception $e) {
			throw new Exception('JS Minify Failed: '.$e->getMessage());
		} //end try catch
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


	public static function strip_php_code($y_file) {
		//--
		$y_file = (string) $y_file; // expects: file.php
		//--
		$output = (string) file_get_contents($y_file);
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
				} elseif(!isset($strip[(string)$token[0]])) { // skip tokens in strip
					$output .= (string) $token[1];
				} else {
					$output .= "\n";
				} //end if else
			} // end foreach
		} // end if
		//--
		return (string) trim((string)$output);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
