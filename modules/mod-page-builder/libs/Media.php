<?php
// Class: \SmartModExtLib\PageBuilder\Media
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\PageBuilder;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Class: PageBuilder Media
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20251216
 * @package 	PageBuilder
 *
 */
final class Media {

	// ::


	public static function safeProcessUploadedDataImg(string $type, string $dataimage, string $convertAs, int $maxWidth, int $maxHeight, float $imgQuality, float $imgMaxSizeMB) : array {
		//--
		// {{{SYNC-WITH-PAGEBUILDER-MEDIA}}}
		//--
		$err = '';
		//--
		$dataimage = (string) \trim((string)$dataimage);
		if(!$err) {
			if((string)$dataimage == '') {
				$err = 'Image Content is Empty';
			} //end if
		} //end if
		//--
		if(!$err) {
			if(((int)$maxWidth < 16) || ((int)$maxWidth > 1920)) {
				$err = 'Image Max Width is Invalid';
			} //end if
		} //end if
		//--
		if(!$err) {
			if(((int)$maxHeight < 16) || ((int)$maxHeight > 1920)) {
				$err = 'Image Max Height is Invalid';
			} //end if
		} //end if
		//--
		$imgQuality = (float) \Smart::format_number_dec($imgQuality); // 2 decimals
		if(!$err) {
			if(((float)$imgQuality < 0.15) || ((float)$imgQuality > 1)) { // 15%..100% ; just for JPEG or WEBP
				$err = 'Image Quality is Invalid';
			} //end if
		} //end if
		//--
		$imgMaxSizeMB = (float) \Smart::format_number_dec($imgMaxSizeMB); // 2 decimals
		if(!$err) {
			if(((float)$imgMaxSizeMB < 0.01) || ((float)$imgMaxSizeMB > 4)) { // 10KB..4MB ; 4MB is max allowed by VirtualImageUploadHandler
				$err = 'Image Max Size is Invalid';
			} //end if
		} //end if
		//--
		if(!$err) {
			if(((string)\substr((string)$dataimage, 0, 11) != 'data:image/') OR (\strpos((string)$dataimage, ';base64,') === false)) {
				$err = 'Image Content Format is Invalid';
			} //end if
		} //end if
		if(!$err) {
			$dataimage = (array) \explode(';base64,', (string)$dataimage);
			$dataimage = (string) \Smart::b64_dec((string)\trim((string)($dataimage[1] ?? null)));
			if((string)\trim((string)$dataimage) == '') {
				$err = 'Image Content Encoding is Invalid';
			} //end if
		} //end if
		//--
		$img_ext = '';
		//--
		$type = (string) \strtolower((string)\trim((string)$type));
		$convertAs = (string) \strtolower((string)\trim((string)$convertAs));
		if(!$err) {
			switch((string)$type) {
				case 'image/svg+xml': // here SVGs can be only base64 encoded as canvas will send them !
					if((\stripos((string)$dataimage, '<svg') !== false) AND (\stripos((string)$dataimage, '</svg>') !== false)) { // {{{SYNC VALIDATE SVG}}}
						$dataimage = (new \SmartXmlParser())->format((string)$dataimage, false, false, false, true); // avoid injection of other content than XML, remove the XML header
					} else {
						$dataimage = ''; // not a SVG !
					} //end if else
					if((string)$dataimage == '') {
						$err = 'SVG Content is Invalid';
					} else {
						$img_ext = 'svg';
					} //end if
					break;
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
				case 'image/webp':
					$imgd = new \SmartImageGdProcess((string)$dataimage);
					$img_ext = (string) $imgd->getImageType();
					$img_as = '';
					if((string)$type == 'image/webp') { // fix back: {{{SYNC-JS-CANVAS-CANNOT-HANDLE-WEBP}}}
						$img_ext = 'webp';
						$img_as = 'webp';
					} //end if
					$is_converted = false;
					$skip_filter_imgd = false;
					switch((string)$convertAs) {
						case 'gif':
						case 'png':
						case 'jpg':
						case 'webp':
							$is_converted = true;
							$img_ext = (string) $convertAs;
							$img_as = (string) $convertAs;
							break;
						default:
							// n/a
					} //end if
					if(((string)$img_ext == 'gif') || ((string)$img_ext == 'png')) {
						if($is_converted === false) { // skip original GIfs and PNGs (but not those converted)
							$skip_filter_imgd = true; // preserve original gifs to keep animations ; they will be only validated for errors via IMGD (if there is a real image to avoid injection of other content)
						} //end if
					} //end if
					$resize = $imgd->resizeImage((int)$maxWidth, (int)$maxHeight, false, 2, [255, 255, 255]); // create resample with: preserve if lower + relative dimensions
					if(!$resize) {
						$err = 'Image Resized Content is Invalid: '.$imgd->getLastMessage();
					} //end if
					if(!$err) {
						if($imgd->getStatusOk() === true) {
							if($skip_filter_imgd === false) { // skip original GIfs and PNGs (but not those converted)
								$dataimage = (string) $imgd->getImageData((string)$img_as, (int)\intval((float)$imgQuality * 100), 9, ((float)$imgQuality < 1) ? -1 : 0, true); // if quality is lower than 1 will use all png filters for compression ; will preserve transparency on png and gif
							} //end if
						} else {
							$dataimage = '';
						} //end if
					} else {
						$dataimage = '';
					} //end if else
					if(!$err) {
						if((string)$dataimage == '') {
							$err = 'Image Processed Content is Invalid';
						} elseif((int)\strlen((string)$dataimage) > (int)\intval(1024 * 1024 * (float)$imgMaxSizeMB)) {
							$err = 'Image Processed Content is Oversized';
						} //end if
					} //end if
					break;
				default:
					$err = 'Invalid Image Type: '.$type;
					$type = 'unknown';
			} //end switch
		} //end if
		//--
		if(!!$err) {
			$dataimage = ''; // reset on error, avoid return malformed or unprocessed content
		} //end if
		//--
		return [
			'error' 	=> (string) $err,
			'mime' 		=> (string) $type,
			'extension' => (string) $img_ext,
			'content' 	=> (string) $dataimage,
		];
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
