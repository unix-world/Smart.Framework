<?php
// [LIB - Smart.Framework / Plugins / Detect Images]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Detect Images:
// DEPENDS:
//	* Smart::
//	* SmartFileSysUtils::
// DEPENDS-EXT:
//	* PHP GD *optional*
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartDetectImages - Easy Detect Images (SVG / PNG / GIF / JPG / WEBP)
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartFileSysUtils
 * @version 	v.20221220
 * @package 	Plugins:Image
 *
 */
final class SmartDetectImages {

	// ::


	//================================================================
	// require the first 16 bytes (first 16 characters - string) of an image to detect or full size for GD detect: SVG / PNG / GIF / JPG / WEBP
	public static function guess_image_extension_by_img_content($pict, $use_gd=false) {
		//--
		if(strlen((string)$pict) < 16) {
			return '';
		} //end if
		//--
		if((stripos((string)$pict, '<svg') !== false) OR (stripos((string)$pict, '</svg>') !== false)) { // use OR here not AND ! it may be only partial content for guess ... {{{SYNC VALIDATE SVG}}}
			return '.svg'; // {{{SYNC-IMG-DETECT-SVG}}}
		} //end if
		//--
		if(($use_gd === true) AND (!function_exists('getimagesizefromstring'))) {
			//--
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice(__METHOD__.'(): GD / getimagesizefromstring() is not available, fall back to quick detection ...');
			} //end if
			//--
			$use_gd = false;
			//--
		} //end if
		//--
		$ext = (string) self::guess_quick_image_extension((string)substr((string)$pict, 0, 16));
		if($use_gd != true) {
			return (string) $ext;
		} //end if
		//--
		$arr_info = (array) @getimagesizefromstring((string)$pict);
		//$width 	= (int) ($arr_info[0] ?? null); // not used here
		//$height 	= (int) ($arr_info[1] ?? null); // not used here
		$imgtyp 	= (int) ($arr_info[2] ?? null); // image type constant
		if($imgtyp <= 0) {
			return ''; // invalid type detected
		} //end if
		$ext = (string) strtolower((string)@image_type_to_extension((int)$imgtyp, true)); // return the image extension with . (dot) prepend
		$type = '';
		switch((string)$ext) {
			case '.gif':
				$type = '.gif';
				break;
			case '.png':
				$type = '.png';
				break;
			case '.jpg':
			case '.jpeg':
				$type = '.jpg';
				break;
			case '.webp':
				$type = '.webp';
				break;
			default:
				$type = '';
		} //end switch
		//--
		return (string) $type;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// guess extension from Data-URL OR HTTP-Headers: SVG / PNG / GIF / JPG / WEBP
	public static function guess_image_extension_by_url_head($y_headers) {
		//--
		$y_headers = (string) $y_headers;
		//--
		$temp_image_extension = '';
		$temp_where_was_detected = '???';
		//--
		if((string)$y_headers != '') {
			//-- {{{SYNC-DATA-IMAGE}}}
			if(
				((string)strtolower((string)substr((string)$y_headers, 0, 11)) == 'data:image/')
				AND
				(
					(stripos((string)$y_headers, ';base64,') !== false)
					OR
					(stripos((string)$y_headers, 'data:image/svg+xml,') === 0) // {{{SYNC-DATA-IMG-SVG-URLENCODED}}} ; svg url encoded
				)
			) { // DATA-URL
				//--
				$temp_where_was_detected = '??? Try to guess by Data-URL as data:image/ ...';
				//--
				if(stripos((string)$y_headers, 'data:image/svg+xml,') === 0) { // {{{SYNC-DATA-IMG-SVG-URLENCODED}}} ; svg url encoded
					$y_headers = (string) substr((string)$y_headers, 19);
					$y_headers = (string) trim((string)urldecode((string)$y_headers)); // use url decode instead of rawurldecode ; will do the job of rawurldecode + will decode also + as spaces
					if((string)$y_headers != '') {
						$temp_image_extension = '.svg'; // add the point
						$temp_where_was_detected = ' * Data-URL as # data:image/, (urlencoded) = '.'svg+xml';
					} //end if
				} else { // base64
					$y_headers = (string) substr($y_headers, 11);
					$eimg = (array) explode(';base64,', $y_headers);
					$eimg[0] = (string) strtolower((string)trim((string)(isset($eimg[0]) ? $eimg[0] : '')));
					if( // {{{SYNC-DETECT-IMG-TYPES}}}
						((string)$eimg[0] == 'svg+xml') OR ((string)$eimg[0] == 'svg') OR
						((string)$eimg[0] == 'png') OR
						((string)$eimg[0] == 'gif') OR
						((string)$eimg[0] == 'jpeg') OR ((string)$eimg[0] == 'jpg') OR
						((string)$eimg[0] == 'webp')
					) {
						$temp_image_extension = '.'.$eimg[0]; // add the point
						$temp_where_was_detected = ' * Data-URL as # data:image/ + ;base64, = '.$eimg[0];
					} //end if
				} //end if else
				//--
			} else { // HTTP-HEADERS
				//--
				$temp_where_was_detected = '??? Try to guess by HTTP-Headers ...';
				//-- try to get file extension by the content (strategy 1)
				$temp_guess_ext_tmp = array();
				preg_match('/^content\-disposition:(.*)$/mi', (string)$y_headers, $temp_guess_ext_tmp);
				$temp_guess_extension = (string) trim((string)($temp_guess_ext_tmp[1] ?? ''));
				$temp_guess_extension = (array)  explode(' filename=', (string)$temp_guess_extension);
				$temp_guess_extension = (string) trim((string)($temp_guess_extension[1] ?? ''));
				$temp_guess_extension = (array)  explode('"', (string)$temp_guess_extension);
				$temp_guess_extension = (string) trim((string)($temp_guess_extension[1] ?? ''));
				$temp_guess_extension = (string) trim((string)strtolower((string)SmartFileSysUtils::extractPathFileExtension((string)$temp_guess_extension))); // [OK]
				$temp_guess_ext_tmp = array();
				//-- test
				if((string)$temp_guess_extension == 'jpeg') {
					$temp_guess_extension = 'jpg'; // correction
				} //end if
				if(((string)$temp_guess_extension == 'svg') OR ((string)$temp_guess_extension == 'png') OR ((string)$temp_guess_extension == 'gif') OR ((string)$temp_guess_extension == 'jpg') OR ((string)$temp_guess_extension == 'webp')) {
					// OK, we guess it
					$temp_where_was_detected = '[content-disposition]: \''.$temp_guess_extension.'\'';
					$temp_image_extension = (string) Smart::safe_validname((string)$temp_guess_extension); // make it safe
					if((string)$temp_image_extension != '') {
						$temp_image_extension = '.'.strtolower((string)$temp_image_extension); // add the point only if non-empty to avoid issues
					} //end if
				} else {
					//-- try to guess by the content type (strategy 2)
					$temp_guess_ext_tmp = array();
					preg_match('/^content\-type:(.*)$/mi', (string)$y_headers, $temp_guess_ext_tmp);
					$temp_guess_extension = (string) trim((string)($temp_guess_ext_tmp[1] ?? ''));
					$temp_guess_extension = (array) explode('/', (string)$temp_guess_extension);
					$temp_guess_extension = (string) trim((string)($temp_guess_extension[1] ?? ''));
					$temp_guess_extension = (array) explode(';', (string)$temp_guess_extension);
					$temp_guess_extension = (string) trim((string)($temp_guess_extension[0] ?? ''));
					//--
					switch((string)$temp_guess_extension) {
						case 'svg+xml':
						case 'svg':
							$temp_image_extension = '.svg';
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						case 'png':
							$temp_image_extension = '.png';
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						case 'gif':
							$temp_image_extension = '.gif';
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						case 'jpg':
						case 'jpeg':
							$temp_image_extension = '.jpg';
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						case 'webp':
							$temp_image_extension = '.webp';
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						case 'html':
							$temp_image_extension = '.htm'; // we want to avoid a wrong answer from server to be get as image
							$temp_where_was_detected = '[content-type]: \''.$temp_image_extension.'\'';
							break;
						default:
							// nothing
							$temp_where_was_detected = '[content-type]: COULD NOT GUESS EXTENSION ! :: \''.$temp_guess_extension.'\'';
					} //end switch
					//--
				} //end if else
				//--
			} //end if
			//--
		} //end if else
		//--
		return array('extension' => (string)$temp_image_extension, 'where-was-detected' => (string)$temp_where_was_detected);
		//--
	} //END FUNCTION
	//================================================================


	//##### PRIVATES


	//================================================================
	// require the first 16 bytes (first 16 characters - string) of an image to detect: PNG / GIF / JPG / WEBP
	private static function guess_quick_image_extension($pict) {
		//--
		// .jpg:  FF D8 FF
		// .png:  89 50 4E 47 0D 0A 1A 0A
		// .gif:  GIF89a | GIF87a
		// .webp: RIFF____WEBP
		//--
		$pict = (string) $pict;
		if(strlen($pict) < 16) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice(__METHOD__.'(): expects the first 16 bytes for detection (but have only '.strlen($pict).' bytes) ...');
			} //end if
			return '';
		} //end if
		//--
		$type = '';
		if(((string)substr($pict, 0, 6) == 'GIF89a') OR ((string)substr($pict, 0, 6) == 'GIF87a')) {
			$type = '.gif';
		} elseif(((string)bin2hex((string)substr($pict, 0, 1)) == '89') AND ((string)substr($pict, 1, 3) == 'PNG')) {
			$type = '.png';
		} elseif(((string)strtolower((string)bin2hex((string)substr($pict, 0, 1))) == 'ff') AND ((string)strtolower((string)bin2hex((string)substr($pict, 1, 1))) == 'd8')) {
			$type = '.jpg';
		} elseif(((string)substr($pict, 0, 4) == 'RIFF') AND ((string)substr($pict, 8, 4) == 'WEBP')) {
			$type = '.webp';
		} //end if else
		//--
		return (string) $type;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
