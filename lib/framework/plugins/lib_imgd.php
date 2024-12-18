<?php
// [LIB - Smart.Framework / Plugins / Image GD Processing]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Image Processing (GD):
// 	* Create Preview
// 	* Create Resize
// 	* Apply Watermark
// DEPENDS:
//	* Smart::
//	* SmartFileSysUtils
// DEPENDS-EXT:
//	* PHP GD (with TrueColor + CreateFromString + GetImgSizeFromString)
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: Smart Image GD Process - provide a class for GD image processing for:
 * Safe Filter, Convert Format, Resize (Resample), Apply Watermark for a GIF / PNG / JPG / WEBP image.
 *
 * For Safe Filter / Convert Format just construct this class and then ->getImageData().
 * For Resize (Resample) an image use ->resizeImage().
 * For Apply an image watermark over an image use: ->applyWatermark()
 *
 * <code>
 *
 * $imgd = new SmartImageGdProcess(
 *    (string) file_get_contents('img/sample-2.jpg')
 * );
 *
 * $resize = $imgd->resizeImage(160, 160, true, 0); // create preview (with crop)
 * //$resize = $imgd->resizeImage(1280, 1280, false, 2); // create resample with: preserve if lower + relative dimensions
 * if(!$resize) {
 *    throw new Exception('R! '.(string)$imgd->getLastMessage());
 * } //end if
 *
 * $wtistr = file_get_contents('img/watermark.gif');
 * $watermark = $imgd->applyWatermark($wtistr, 'c', 0, 0);
 * if(!$watermark) {
 *     throw new Exception('W! '.(string)$imgd->getLastMessage());
 * } //end if
 *
 * $png = '';
 * if($imgd->getStatusOk() === true) {
 *     $png = (string) $imgd->getImageData('png', 100, 9);
 * } //end if
 * if((string)$png == '') {
 *     throw new Exception('S! '.(string)$imgd->getLastMessage());
 * } //end if
 *
 * header('Content-Type: image/png');
 * header('Content-Disposition: inline; filename="sample-image-'.time().'.png"');
 * echo $png;
 *
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		PUBLIC
 * @depends     PHP GD extension with support for: imagecreatetruecolor / imagecreatefromstring / getimagesizefromstring ; classes: Smart, SmartFileSysUtils
 * @version 	v.20221223
 * @package 	Plugins:Image
 *
 */
final class SmartImageGdProcess {

	// ->

	private $status = false;
	private $message = '';

	private $img = null;
	private $width = 0;
	private $height = 0;
	private $type = '';
	private $info = '';

	private $debug = false;


	//================================================================
	/**
	 * Class constructor
	 *
	 * @param MIXED $imgstr 			:: the image data STRING of an image: GIF / PNG / JPG / WEBP ; or the ARRAY definition for a new image [ 'width' => 200, 'height' => 100, 'bgcolor' => [0..255, 0..255, 0..255] ]
	 * @param STRING $imginfo 			:: *Optional* an identifier of the image (Ex: the image name, used for error log or debug messages)
	 *
	 */
	public function __construct($imgdata, $imginfo='') {

		//--
		if(!function_exists('imagecreatetruecolor')) {
			Smart::raise_error(
				'[ERROR] :: '.__CLASS__.' :: PHP-GD extension with TrueColor support is required.',
				'A required component is missing ... See error log for more details'
			);
			die('Missing GD TrueColor Support');
		} //end if
		if((!function_exists('imagecreatefromstring')) OR (!function_exists('getimagesizefromstring'))) {
			Smart::raise_error(
				'[ERROR] :: '.__CLASS__.' :: PHP-GD extension with Img-from-String support is required.',
				'A required component is missing ... See error log for more details'
			);
			die('Missing GD Img-from-String Support');
		} //end if
		//--

		//--
		$this->_debugMsg((string)__METHOD__.' :: '.'Image Info: '.$imginfo);
		//--
		if(is_array($imgdata)) {
			//--
			$this->type   = (string) 'png';
			$this->width  = (int)    ($imgdata['width'] ?? null);
			$this->height = (int)    ($imgdata['height'] ?? null);
			//--
			if($this->width < 1) {
				$this->width = 1;
			} elseif($this->width > 8192) {
				$this->width = 8192;
			} //end if
			if($this->height < 1) {
				$this->height = 1;
			} elseif($this->height > 8192) {
				$this->height = 8192;
			} //end if
			//--
			$bg_color_rgb = (array) $this->_fixColorArray($imgdata['bgcolor']);
			//--
			if($this->_testImageSizeAndType('initialize', $this->type, $this->width, $this->height)) { // test image w,h,type
				//--
				$this->img = @imagecreatetruecolor($this->width, $this->height); // create a new gd img res. from width and height
				//--
				$imgbgcolor = @imagecolorallocate($this->img, $bg_color_rgb[0], $bg_color_rgb[1], $bg_color_rgb[2]);
				@imagefill($this->img, 0, 0, $imgbgcolor);
				$imgbgcolor = null; // free mem
				//--
				$this->status = true; // status flag OK
				//--
			} //end if
			//--
		} else {
			//--
			$ardet = (array) $this->_getImgSizeAndTypeFromImgStr((string)$imgdata); // get image w,h,type
			$this->type   = (string) $ardet['t'];
			$this->width  = (int)    $ardet['w'];
			$this->height = (int)    $ardet['h'];
			$ardet = null; // free mem
			//--
			if($this->_testImageSizeAndType('initialize', $this->type, $this->width, $this->height)) { // test image w,h,type
				//--
				$this->img = @imagecreatefromstring((string)$imgdata); // create a gd img res. from string
				//--
				$this->status = true; // status flag OK
				//--
			} //end if
			//--
		} //end if else
		//--
		$imgdata = null; // free mem
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Class destructor
	 *
	 */
	public function __destruct() {

		//--
		if($this->testIfValidImg($this->img)) {
			@imagedestroy($this->img);
		} //end if
		//--
		$this->img = null;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Set Debug ON / OFF
	 *
	 * @param BOOL $debug 				:: if TRUE set Debug ON else set Debug OFF ; By default Debug is OFF
	 *
	 */
	public function setDebug($debug) {

		//--
		$this->debug = (bool) $debug;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the OK status of the image processing
	 *
	 * @return BOOL 						:: TRUE if OK / FALSE if NOT OK
	 */
	public function getStatusOk() {

		//--
		return (bool) $this->status;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get last error / warning message
	 *
	 * @return STRING 						:: the message if any or empty string
	 */
	public function getLastMessage() {

		//--
		return (string) $this->message;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the Width of the Image (recalculated after each processing)
	 * By default (if no processing) returns the original width of the input image
	 * On error will return -1
	 *
	 * @return INTEGER 						:: image width in pixels or -1 on error
	 */
	public function getImageWidth() {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			return (int) -1;
		} //end if
		//--

		//--
		return (int) $this->width;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the Height of the Image (recalculated after each processing)
	 * By default (if no processing) returns the original width of the input image
	 * On error will return -1
	 *
	 * @return INTEGER 						:: image width in pixels or -1 on error
	 */
	public function getImageHeight() {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			return (int) -1;
		} //end if
		//--

		//--
		return (int) $this->height;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the detected Image type of the original image data
	 * This will be preserved and never changed upon different image processings
	 * On error will return empty string ''
	 * Possible errors when detecting an image type:
	 * - corrupted or invalid image data
	 * - invalid type: only GIF / PNG / JPG / WEBP types are supported
	 * - image size is very high: images over 8 Megapixels may fail !
	 *
	 * @return ENUM 						:: '' | 'gif' | 'png' | 'jpg' | 'webp'
	 */
	public function getImageType() {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			return (string) '';
		} //end if
		//--

		//--
		return (string) $this->type;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get GD image data as: GIF / PNG / JPG / WEBP
	 *
	 * @param ENUM 		$type 				:: *Optional* '' | 'gif' | 'png' | 'jpg' | 'webp' ; if '' will return the exact type as the input image
	 * @param INTEGER+ 	$quality 			:: *Optional* 1..100 ; Default is 100 ; The quality of the image ; currently applies just for JPG and WEBP
	 * @param INTEGER+ 	$compression 		:: *Optonal* 0..9 ; Default is 6 ; The image compression level (zlib) ; currently applies just for PNG
	 * @param MIXED 	$filters 			:: *Optional* ; Default is '' = no filters ; false = PNG_NO_FILTER ; true = PNG_ALL_FILTERS ; array(PNG_FILTER_SUB, PNG_FILTER_UP, PNG_FILTER_AVG, PNG_FILTER_PAETH, PNG_FILTER_NONE) = array with filters ; currently applies just for PNG (using array of filters depends on what filters are available in LibPNG)
	 *
	 * @return STRING 						:: '' on error or image data in the specified format: GIF / PNG / JPG / WEBP
	 */
	public function getImageData($type='', $quality=100, $compression=6, $filters='') {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			return (string) '';
		} //end if
		//--
		if(!$this->testIfValidImg($this->img)) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Resource');
			return (string) '';
		} //end if
		//--

		//--
		$compression = (int) $compression;
		if($compression < 0) {
			$compression = 0;
		} elseif($compression > 9) {
			$compression = 9;
		} //end if else
		//--
		$quality = (int) $quality;
		if($quality < 1) {
			$quality = 1;
		} elseif($quality > 100) {
			$quality = 100;
		} //end if else
		//--

		//--
		if((string)$type == '') {
			$type = (string) $this->type;
		} else {
			$type = (string) strtolower((string)trim((string)$type));
		} //end if
		//--

		//--
		ob_start();
		//--
		switch((string)$type) {
			case 'gif':
				@imagegif($this->img);
				break;
			case 'png':
				if($filters === true) {
					$this->_debugMsg((string)__METHOD__.' :: '.'Using PNG Filter: PNG_ALL_FILTERS', false); // notice
					@imagepng($this->img, null, $compression, PNG_ALL_FILTERS);
				} elseif($filters === false) {
					$this->_debugMsg((string)__METHOD__.' :: '.'Using PNG Filter: PNG_NO_FILTER', false); // notice
					@imagepng($this->img, null, $compression, PNG_NO_FILTER);
				} elseif(Smart::array_size($filters) > 0) { // (depends on libpng)
					$this->_debugMsg((string)__METHOD__.' :: '.'Using PNG Filters: '.implode(' | ', (array)$filters), false); // notice
					@imagepng($this->img, null, $compression, implode(' | ', (array)$filters));
				} else { // default
					$this->_debugMsg((string)__METHOD__.' :: '.'Using NO PNG Filters (default)', false); // notice
					@imagepng($this->img, null, $compression);
				} //end if else
				break;
			case 'jpg':
				@imagejpeg($this->img, null, $quality);
				break;
			case 'webp':
				if(function_exists('imagewebp')) {
					@imagewebp($this->img, null, $quality);
				} else {
					$this->_errMsg((string)__METHOD__.' :: '.'PHP is missing the WEBP support');
				} //end if else
				break;
			default:
				$this->_errMsg((string)__METHOD__.' :: '.'Invalid Image Type'); // this should not happen, it is catched above
		} //end switch
		//--
		$imgstr = ob_get_contents();
		//--
		ob_end_clean();
		//--

		//--
		return (string) $imgstr;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Resize (Resample) +/- Crop an image: GIF / PNG / JPG / WEBP
	 *
	 * @param INTEGER+ 	$resize_width 		:: The width of the resized image: 16..1920 ; can be zero only when $resize_height > 0 ; if zero, will become relative and calculated by aspect ratio based on resize height
	 * @param INTEGER+ 	$resize_height 		:: The height of the resized image: 16..1920 ; can be zero only when $resize_width > 0 ; if zero, will become relative and calculated by aspect ratio based on resize width
	 * @param BOOLEAN 	$crop 				:: *Optional* ; Default is 0 ; If 0 will do a normal resize (aspect fit) ; If > 0 will crop the resampled image (aspect fill) as 1=top-left ; 2=top-center ; 3=center-center
	 * @param ENUM 		$mode 				:: *Optional* 0 | 1 | 2 ; Default is 0 = absolute resample ; 1 = absolute resample + preserve dimensions if lower ; 2 = preserve if lower + relative dimensions ; 3 = relative dimensions
	 * @param ARRAY 	$bg_color_rgb 		:: The RGB color as Array [0..255, 0..255, 0..255]
	 *
	 * @hints 								:: Create a Preview:  w>0, h>0, crop=true/false,  mode=0 ; Create a (classic) image resize: w>=0, h>=0, crop=false, mode=2/1
	 *
	 * @return BOOLEAN 						:: TRUE on success ; FALSE on error / fail
	 */
	public function resizeImage($resize_width, $resize_height, $crop=0, $mode=0, $bg_color_rgb=[0, 0, 0]) {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			$this->status = false;
			return false;
		} //end if
		//--
		if(!$this->testIfValidImg($this->img)) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Resource');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$bg_color_rgb = (array) $this->_fixColorArray($bg_color_rgb);
		//--

		//--
		if(($resize_height <= 0) AND ($resize_width <= 0)) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Resample Dimensions: W and H are zero !');
			$this->status = false;
			return false;
		} //end if
		//--

		//-- check for relative sizes
		$fixratio = $this->width / $this->height;
		if($resize_height <= 0) {
			$resize_height = ceil($resize_width / $fixratio);
		} elseif($resize_width <= 0) {
			$resize_width = ceil($resize_height * $fixratio);
		} //end if
		unset($fixratio);
		//--

		//-- param fixes and constraints
		$resize_width = (int) $resize_width;
		if($resize_width < 16) {
			$resize_width = 16;
		} //end if
		if($resize_width > 1920) {
			$resize_width = 1920;
		} //end if
		//--
		$resize_height = (int) $resize_height;
		if($resize_height < 16) {
			$resize_height = 16;
		} //end if
		if($resize_height > 1920) {
			$resize_height = 1920;
		} //end if
		//--

		//--
		$crop = (int) $crop;
		if($crop < 0) {
			$crop = 0;
		} elseif($crop > 3) {
			$crop = 3;
		} //end if
		//--

		//-- crop mode
		$ratio = 1;
		//--
		if($crop) {
			$ratio = max(array($resize_width / $this->width, $resize_height / $this->height)); // aspect Fill (crop)
		} else {
			$ratio = min(array($resize_width / $this->width, $resize_height / $this->height)); // aspect Fit
		} //end if else
		$newImgW = $ratio * $this->width  + 1; // fix one pixel margin
		$newImgH = $ratio * $this->height + 1; // fix one pixel margin
		//--
		unset($ratio);
		//--

		//-- modes (default mode = 0)
		if(($mode == 1) OR ($mode == 2)) { // preserve if lower
			if(($this->width <= $resize_width) AND ($this->height <= $resize_height)) {
				$this->_debugMsg((string)__METHOD__.' :: '.'Will Preserve Image as it is Lower on Dimensions ...', false); // notice
				$newImgW = $this->width;
				$newImgH = $this->height;
				$resize_width = $this->width;
				$resize_height = $this->height;
			} //end if
		} //end if
		//--
		if(($mode == 2) OR ($mode == 3)) { // relative sizes
			$this->_debugMsg((string)__METHOD__.' :: '.'Will use Relative Dimensions ...', false); // notice
			$newImgW = $newImgW - 1;
			$resize_width = $newImgW;
			$newImgH = $newImgH - 1;
			$resize_height = $newImgH;
		} //end if
		//--

		//-- this situation can come from calculations from above ... having zero width or height, actually should be 1 !
		if((int)$newImgW <= 0) {
			$newImgW = 1; // correction, avoid zero width, PHP raise errro if so ...
		} //end if
		if((int)$newImgH <= 0) {
			$newImgH = 1; // correction, avoid zero width, PHP raise errro if so ...
		} //end if
		//--

		//-- create the new img
		$imgnew = @imagecreatetruecolor($newImgW, $newImgH);
		if(!$this->testIfValidImg($imgnew)) {
			$imgnew = null;
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Resample Resource');
			$this->status = false;
			return false;
		} //end if
		//--
		@imagefill($imgnew, 0, 0, @imagecolorallocate($imgnew, (int)$bg_color_rgb[0], (int)$bg_color_rgb[1], (int)$bg_color_rgb[2])); // fill with bg
		@imagecopyresampled($imgnew, $this->img, 0, 0, 0, 0, $newImgW, $newImgH, $this->width, $this->height); // copy image in the new created image
		//--
		if(!$this->testIfValidImg($imgnew)) {
			$imgnew = null;
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Resampled Image');
			$this->status = false;
			return false;
		} //end if
		//--

		//-- calculate center
		$center_w = floor($resize_width/2 - $newImgW/2);
		if($center_w < 0) {
			if((int)$crop == 1) {
				$center_w = 0;
			} //end if
		} //end if
		//--
		$center_h = floor($resize_height/2 - $newImgH/2);
		if($center_h < 0) {
			if(((int)$crop == 1) OR ((int)$crop == 2)) {
				$center_h = 0;
			} //end if
		} //end if
		//--

		//-- finalize
		@imagedestroy($this->img);
		$this->img = null;
		if($this->testIfValidImg($this->img)) {
			@imagedestroy($imgnew);
			$imgnew = null;
			$this->_debugMsg((string)__METHOD__.' :: '.'Failed to Destroy Original Image Resource for Resampling ...');
			$this->status = false;
			return false;
		} //end if
		//--
		//-- this situation can come from calculations from above ... having zero width or height, actually should be 1 !
		if((int)$resize_width <= 0) {
			$resize_width = 1; // correction, avoid zero width, PHP raise errro if so ...
		} //end if
		if((int)$resize_height <= 0) {
			$resize_height = 1; // correction, avoid zero width, PHP raise errro if so ...
		} //end if
		//--
		//--
		$this->img = @imagecreatetruecolor($resize_width, $resize_height);
		if(!$this->testIfValidImg($this->img)) {
			@imagedestroy($imgnew);
			$imgnew = null;
			$this->img = null; // reset back !
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Export Resource');
			$this->status = false;
			return false;
		} //end if
		@imagefill($this->img, 0, 0, @imagecolorallocate($this->img, (int)$bg_color_rgb[0], (int)$bg_color_rgb[1], (int)$bg_color_rgb[2])); // fill with bg again (needed after resampling)
		@imagecopy($this->img, $imgnew, $center_w, $center_h, 0, 0, $newImgW, $newImgH); // save back must clone, as it is resource ; not work direct as: $this->img = $imgnew;
		@imagedestroy($imgnew);
		$imgnew = null;
		if(!$this->testIfValidImg($this->img)) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Export Data');
			$this->status = false;
			return false;
		} //end if
		$this->width = $resize_width;
		$this->height = $resize_height;
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Apply an image Watermark (GIF / PNG / JPG / WEBP) on an image: GIF / PNG / JPG / WEBP
	 *
	 * @param STRING 	$wtistr 			:: the image data string of the watermark image: GIF / PNG / JPG / WEBP
	 * @param ENUM 		$gravity 			:: the placement of the watermark on image: c/center, n/north, s/south, w/west, e/east, nw/northwest, ne/northeast, sw/southwest, se/southeast
	 * @param INTEGER 	$offsx 				:: correction offset X for watermark placement
	 * @param INTEGER 	$offsy 				:: correction offset Y for watermark placement
	 *
	 * @hints 								:: the image transparency of the watermark will be preserved ; so if a watermark with transparency is required, use a watermark with embedded transparency
	 *
	 * @return BOOLEAN 						:: TRUE on success ; FALSE on error / fail
	 */
	public function applyWatermark($wtistr, $gravity, $offsx=0, $offsy=0) {

		//--
		if($this->status !== true) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			$this->status = false;
			return false;
		} //end if
		//--
		if(!$this->testIfValidImg($this->img)) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image Resource');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$arwdet = (array) $this->_getImgSizeAndTypeFromImgStr((string)$wtistr); // get image w,h,type
		$wtm_type   = (string) $arwdet['t'];
		$wtm_width  = (int)    $arwdet['w'];
		$wtm_height = (int)    $arwdet['h'];
		unset($arwdet);
		//--
		$nfo = ' :: WtType='.$wtm_type.' / WtWidth='.$wtm_width.' / WtSize='.$wtm_height;
		//--
		if(!$this->_testImageSizeAndType('watermark', $wtm_type, $wtm_width, $wtm_height)) { // test image w,h,type
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Watermark Type / Width / Height'.$nfo);
			$this->status = false;
			return false;
		} //end if
		//--
		$wtm_img = @imagecreatefromstring((string)$wtistr); // create a gd img res. from string
		$wtistr = ''; // free mem
		if(!$this->testIfValidImg($wtm_img)) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Watermark Resource'.$nfo);
			$this->status = false;
			return false;
		} //end if
		//--

		//-- detect gravity of watermark
		$gvtyX = 0;
		$gvtyY = 0;
		//--
		switch((string)strtolower((string)$gravity)) { // {{{SYNC-GRAVITY}}}
			//--
			case 'north':
			case 'n':
				$gvtyX = ceil(($this->width / 2) - ($wtm_width / 2));
				$gvtyY = 0;
				break;
			case 'south':
			case 's':
				$gvtyX = ceil(($this->width / 2) - ($wtm_width / 2));
				$gvtyY = floor($this->height - $wtm_height);
				break;
			case 'west':
			case 'w':
				$gvtyX = 0;
				$gvtyY = ceil(($this->height / 2) - ($wtm_height / 2));
				break;
			case 'east':
			case 'e':
				$gvtyX = floor($this->width - $wtm_width);
				$gvtyY = ceil(($this->height / 2) - ($wtm_height / 2));
				break;
			//--
			case 'northwest':
			case 'nw':
				$gvtyX = 0;
				$gvtyY = 0;
				break;
			case 'northeast':
			case 'ne':
				$gvtyX = floor($this->width - $wtm_width);
				$gvtyY = 0;
				break;
			case 'southwest':
			case 'sw':
				$gvtyX = 0;
				$gvtyY = floor($this->height - $wtm_height);
				break;
			case 'southeast':
			case 'se':
				$gvtyX = floor($this->width - $wtm_width);
				$gvtyY = floor($this->height - $wtm_height);
				break;
			//--
			case 'center':
			case 'c':
			case '':
			default:
				$gvtyX = ceil(($this->width / 2) - ($wtm_width / 2));
				$gvtyY = ceil(($this->height / 2) - ($wtm_height / 2));
			//--
		} //end switch
		//--
		$gvtyX = $gvtyX + (int) $offsx;
		$gvtyY = $gvtyY + (int) $offsy;
		//--

		//--
		@imagecopy($this->img, $wtm_img, $gvtyX, $gvtyY, 0, 0, $wtm_width, $wtm_height);
		@imagedestroy($wtm_img);
		$wtm_img = null;
		if(!$this->testIfValidImg($this->img)) {
			$this->img = null; // reset back !
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image+Watermark Export Data');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Calculate TTF text bounding box for an image
	 *
	 * @param STRING 	$text 				:: the text to be applied
	 * @param INTEGER 	$angle 				:: TTF angle rotation (0..180) degrees
	 * @param INTEGER 	$size 				:: TTF font size
	 * @param ENUM 		$font 				:: *Optional* The font to be used: character's font (1..5 for built-in gd font ; path/to/font.gdf ; path/to/font.ttf) ; Default is: lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf
	 *
	 * @hints 								:: this works just with TTF fonts
	 *
	 * @return ARRAY 						:: return an empty array on error / on success returns an array with 8 coordinates as [ 0: lower left corner, X position ; 1: lower left corner, Y position ; 2: lower right corner, X position ; 3: lower right corner, Y position ; 4: upper right corner, X position ; 5: upper right corner, Y position ; 6: upper left corner, X position ; 7: upper left corner, Y position ]
	 */
	public function calculateTextBBox($text, $angle, $size, $font='lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf') {

		//--
		$text = (string) Smart::normalize_spaces((string)$text); // only single line text is allowed
		$text = (string) trim((string)$text);
		//--
		$angle = (int) $angle; // angle in degrees (apply just for ttf fonts)
		if($angle < 0) {
			$angle = 0;
		} elseif($angle > 360) {
			$angle = 360;
		} //end if else
		//--
		$size = (int) $size;
		if($size < 8) {
			$size = 8;
		} elseif($size > 256) {
			$size = 256;
		} //end if else
		//--
		$font = (string) $font;
		//--

		//--
		$isttf = false;
		if(((string)$font != '') AND (SmartFileSysUtils::checkIfSafePath((string)$font)) AND (SmartFileSysUtils::staticFileExists((string)$font))) {
			if(function_exists('imagettfbbox') AND ((string)substr((string)$font, -4, 4) == '.ttf')) {
				$isttf = true;
			} //end if
		} //end if else
		//--

		//--
		if(!$isttf) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Font');
			return array();
		} else { // TTF Fonts
			return (array) @imagettfbbox((int)$size, (int)$angle, (string)Smart::real_path((string)$font), (string)$text);
		} //end if else
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Prepare multi-line text by width and margin (text will be wrapped on multiple lines) on an image
	 *
	 * @param STRING 	$text 				:: the text to be applied
	 * @param INTEGER 	$angle 				:: TTF angle rotation (0..180) degrees
	 * @param INTEGER 	$size 				:: TTF font size
	 * @param INTEGER 	$width 				:: width of text area (includding margins)
	 * @param INTEGER 	$margin 			:: left margin for text placement ; Default is 0
	 * @param ENUM 		$font 				:: The font to be used: character's font (1..5 for built-in gd font ; path/to/font.gdf ; path/to/font.ttf) ; Default is: lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf
	 *
	 * @hints 								:: this works just with TTF fonts ; the text transparency is made by the $color_rgb alpha channel
	 *
	 * @return ARRAY 						:: Array of Text split into lines [0..n]
	 */
	public function prepareTextLines($text, $angle, $size, $width, $margin, $font) {
		//--
		$text = (string) Smart::normalize_spaces((string)$text);
		$text = (string) trim((string)$text);
		if((string)$text == '') {
			return array();
		} //end if
		//--
		$angle = (int) $angle;
		$size = (int) $size;
		$width = (int) $width;
		$margin = (int) $margin;
		//--
		$text_a = (array) explode(' ', (string)$text);
		$text_new = '';
		$text_line = '';
		foreach($text_a as $kk => $word){
			//-- try to add the word and calculate the bbox of the text
			$box = $this->calculateTextBBox((string)$text_line.' '.$word, (int)$angle, (int)$size, (string)$font);
			//-- if the line fits to the specified width, then add the word with a space otherwise with new line
			if($box[2] > ($width - ($margin*2))){
				$text_new .= "\n".$word;
				$text_line = $word;
			} else {
				$text_new .= " ".$word;
				$text_line .= " ".$word;
			} //end if else
		} //end if else
		//--
		return (array) explode("\n", (string)trim((string)$text_new));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Apply multi-line text (text will be wrapped on multiple lines) on an image: GIF / PNG / JPG / WEBP
	 *
	 * @param STRING 	$text 				:: the text to be applied
	 * @param ENUM 		$align 				:: *Optional* the align mode: left | center | right ; default is left
	 * @param INTEGER 	$margin 			:: *Optional* left margin for text placement ; Default is 0
	 * @param INTEGER 	$top 				:: *Optional* top margin for text placement ; Default is 0
	 * @param INTEGER 	$vlinespace 		:: *Optional* vertical line spacing (space between vertical text lines)
	 * @param INTEGER 	$angle 				:: *Optional* TTF angle rotation (0..180) degrees ; Default is 0 ; Applies just for Align=left ; for Align=center / Align=right ... to be done in the future ...
	 * @param INTEGER 	$size 				:: *Optional* TTF font size ; Default is 10 | null (for built-in or GDF fonts)
	 * @param ENUM 		$font 				:: *Optional* The font to be used: character's font (1..5 for built-in gd font ; path/to/font.gdf ; path/to/font.ttf) ; Default is: lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf
	 * @param ARRAY 	$color_rgb 			:: *Optional* The RGB color with optional alpha channel as Array [0..255, 0..255, 0..255, 0..127] ; Default is: [0, 0, 0, 0]
	 *
	 * @hints 								:: this works just with TTF fonts ; the text transparency is made by the $color_rgb alpha channel
	 *
	 * @return BOOLEAN 						:: TRUE on success ; FALSE on error / fail
	 */
	public function applyWrapText($text, $align='left', $margin=0, $top=0, $vlinespace=5, $angle=0, $size=10, $font='lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf', $color_rgb=[0, 0, 0, 0]) {

		//--
		if($this->status !== true) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			$this->status = false;
			return false;
		} //end if
		//--
		if(!$this->testIfValidImg($this->img)) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Image Resource');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$text = (string) Smart::normalize_spaces((string)$text); // only single line text is allowed
		$text = (string) trim((string)$text);
		if((string)$text == '') {
			$this->_errMsg((string)__METHOD__.' :: '.'Empty Text to Apply on Image');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$align = (string) strtolower((string)trim((string)$align));
		switch((string)$align) {
			case 'left':
			case 'center':
			case 'right':
				break;
			default:
				$this->_errMsg((string)__METHOD__.' :: '.'Invalid Align Mode ('.$align.') to Apply Text on Image');
				$this->status = false;
				return false;
		} //end switch
		//--

		//--
		$margin = (int) $margin;
		if($margin < 0) {
			$margin = 0;
		} elseif($margin > floor($this->width / 2)) {
			$margin = (int) floor($this->width / 2);
		} //end if else
		//--
		$vlinespace = (int) $vlinespace;
		if($vlinespace < 0) {
			$vlinespace = 0;
		} elseif($vlinespace > floor($this->height / 3)) {
			$vlinespace = (int) floor($this->height / 3);
		} //end if else
		//--
		$angle = (int) $angle; // angle in degrees (apply just for ttf fonts)
		if($angle < 0) {
			$angle = 0;
		} elseif($angle >= 360) {
			$angle = 0;
		} //end if else
		//--
		if($angle > 0) {
			$align = 'left'; // TODO: calculate starting position with angles is very complicated ... by now it enforces align LEFT with any angle other than zero
		} //end if
		//--
		$size = (int) $size;
		if($size < 8) {
			$size = 8;
		} elseif($size > 256) {
			$size = 256;
		} //end if else
		//--
		$font = (string) $font;
		//--
		$color_rgb = (array) $this->_fixColorArray($color_rgb);
		//--

		//--
		$isttf = false;
		if(((string)$font != '') AND (SmartFileSysUtils::checkIfSafePath((string)$font)) AND (SmartFileSysUtils::staticFileExists((string)$font))) {
			if(function_exists('imagettfbbox') AND ((string)substr((string)$font, -4, 4) == '.ttf')) {
				$isttf = true;
			} //end if
		} //end if else
		//--

		//--
		if(!$isttf) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Font or TTF support is missing');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$lines = (array) $this->prepareTextLines((string)$text, (int)$angle, (int)$size, (int)$this->width, (int)$margin, (string)$font);
		//--
		for($i=0; $i<Smart::array_size($lines); $i++) {
			if((string)$lines[$i]) {
				$liney = (int) floor($top + ((int)$i * ((int)$vlinespace + (int)$size)));
				$this->_applyTextLine((string)$lines[$i], (int)$this->width, (int)$margin, (int)$angle, (int)$size, (int)$liney, (string)$align, (string)$font, (array)$color_rgb);
				if($this->status !== true) {
					$this->_debugMsg((string)__METHOD__.' :: '.'Failed to apply Wrap Text on Image with Font: '.$font.' / Size: '.$size.' / Angle: '.$angle.' / Align: '.$align.' / VLineSpace: '.$vlinespace.' / Margin: '.$margin);
					return false;
					break;
				} //end if
			} //end if
		} //end for
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Apply text on an image: GIF / PNG / JPG / WEBP
	 *
	 * @param STRING 	$text 				:: the text to be applied
	 * @param INTEGER 	$offsx 				:: *Optional* correction offset X for text placement ; Default is 0
	 * @param INTEGER 	$offsy 				:: *Optional* correction offset Y for text placement ; Default is 0
	 * @param INTEGER 	$angle 				:: *Optional* TTF angle rotation (0..180) degrees ; Default is 0 | null (for built-in or GDF fonts)
	 * @param INTEGER 	$size 				:: *Optional* TTF font size ; Default is 10 | null (for built-in or GDF fonts)
	 * @param ENUM 		$font 				:: *Optional* The font to be used: character's font (1..5 for built-in gd font ; path/to/font.gdf ; path/to/font.ttf) ; Default is: lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf
	 * @param ARRAY 	$color_rgb 			:: *Optional* The RGB color with optional alpha channel as Array [0..255, 0..255, 0..255, 0..127] ; Default is: [0, 0, 0, 0]
	 *
	 * @hints 								:: the text transparency is made by the $color_rgb alpha channel
	 *
	 * @return BOOLEAN 						:: TRUE on success ; FALSE on error / fail
	 */
	public function applyText($text, $offsx=0, $offsy=0, $angle=0, $size=10, $font='lib/core/plugins/fonts/typo/sans/ibm-plex-sans-regular.ttf', $color_rgb=[0, 0, 0, 0]) {

		//--
		if($this->status !== true) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Image Status');
			$this->status = false;
			return false;
		} //end if
		//--
		if(!$this->testIfValidImg($this->img)) {
			$this->_errMsg((string)__METHOD__.' :: '.'Invalid Image Resource');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$text = (string) Smart::normalize_spaces((string)$text); // only single line text is allowed
		$text = (string) trim((string)$text);
		if((string)$text == '') {
			$this->_errMsg((string)__METHOD__.' :: '.'Empty Text to Apply on Image');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		$offsx = (int) $offsx;
		if($offsx < 0) {
			$offsx = 0;
		} elseif($offsx > $this->width) {
			$offsx = (int) $this->width;
		} //end if else
		//--
		$offsy = (int) $offsy;
		if($offsy < 0) {
			$offsy = 0;
		} elseif($offsy > $this->height) {
			$offsy = (int) $this->height;
		} //end if else
		//--
		$angle = (int) $angle; // angle in degrees (apply just for ttf fonts)
		if($angle < 0) {
			$angle = 0;
		} elseif($angle > 360) {
			$angle = 360;
		} //end if else
		//--
		$size = (int) $size;
		if($size < 8) {
			$size = 8;
		} elseif($size > 256) {
			$size = 256;
		} //end if else
		//--
		$font = (string) $font;
		//--
		$color_rgb = (array) $this->_fixColorArray($color_rgb);
		//--

		//--
		$isttf = false;
		if(is_int($font) AND ($font > 0)) {
			$font = (int) $font;
		} elseif(((string)$font != '') AND (SmartFileSysUtils::checkIfSafePath((string)$font)) AND (SmartFileSysUtils::staticFileExists((string)$font))) {
			if(function_exists('imagettftext') AND ((string)substr((string)$font, -4, 4) == '.ttf')) {
				$font = (string) $font;
				$isttf = true;
			} else { // gdf font
				$font = @imageloadfont($font);
				if($font === false) {
					$font = 5; // on error
				} //end if
			} //end if else
		} else {
			$font = 5 ; // on error
		} //end if else
		//--

		//--
		if(Smart::array_size($color_rgb) >= 4) {
			$color = @imagecolorexactalpha($this->img, (int)$color_rgb[0], (int)$color_rgb[1], (int)$color_rgb[2], (int)$color_rgb[3]);
			if($color === -1) { // returns -1 if color does not exists in palette
				$color = @imagecolorallocatealpha($this->img, (int)$color_rgb[0], (int)$color_rgb[1], (int)$color_rgb[2], (int)$color_rgb[3]);
			} //end if
		} else {
			$color = @imagecolorexact($this->img, (int)$color_rgb[0], (int)$color_rgb[1], (int)$color_rgb[2]);
			if($color === -1) { // returns -1 if color does not exists in palette
				$color = @imagecolorallocate($this->img, (int)$color_rgb[0], (int)$color_rgb[1], (int)$color_rgb[2]);
			} //end if
		} //end if else
		//--

		//--
		if($isttf !== true) { // GDF font
			$write = @imagestring($this->img, (int)$font, (int)$offsx, (int)$offsy, (string)$text, $color);
		} else { // TTF font
			$offsy += $size; // correction
			$write = @imagettftext($this->img, (int)$size, (int)$angle, (int)$offsx, (int)$offsy, $color, (string)Smart::real_path((string)$font), (string)$text); // fix: on windows, PHP 7+ GD needs real path for TTF Fonts
		} //end if else
		//--

		//--
		if($write === false) {
			$this->_errMsg((string)__METHOD__.' :: '.'Failed to apply Text on Image with Font: '.$font.' / Size: '.$size.' / Angle: '.$angle.' / Coordinates: '.$offsx.'x'.$offsy);
			$this->status = false;
			return false;
		} //end if
		if(!$this->testIfValidImg($this->img)) {
			$this->img = null; // reset back !
			$this->_debugMsg((string)__METHOD__.' :: '.'Invalid Image+Text Export Data');
			$this->status = false;
			return false;
		} //end if
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//================================================================


	//### PRIVATES


	//================================================================
	private function _errMsg($msg) {

		//--
		$this->message = 'ERROR: '.$msg.' # Type='.$this->type.'; Width='.$this->width.'; Height='.$this->height.'; Info='.$this->info;
		//--
		Smart::log_warning((string)$this->message);
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _debugMsg($msg, $warn=true) {

		//--
		if(!$this->debug) {
			return;
		} //end if
		//--

		//--
		if($warn === false) {
			$this->message = 'NOTICE: ';
		} else {
			$this->message = 'WARNING: ';
		} //end if else
		$this->message .= (string) $msg.' # Type='.$this->type.'; Width='.$this->width.'; Height='.$this->height.'; Info='.$this->info;
		//--
		Smart::log_notice((string)$this->message);
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _fixColorArray($input_color_rgb) {

		//--
		$input_color_rgb = (array) $input_color_rgb;
		//--

		//--
		$color_rgb = array(); // init
		//--

		//-- RED
		$color_rgb[0] = (int) $input_color_rgb[0];
		if($color_rgb[0] < 0) {
			$color_rgb[0] = 0;
		} elseif($color_rgb[0] > 255) {
			$color_rgb[0] = 255;
		} //end if
		//--

		//-- GREEN
		$color_rgb[1] = (int) $input_color_rgb[1];
		if($color_rgb[1] < 0) {
			$color_rgb[1] = 0;
		} elseif($color_rgb[1] > 255) {
			$color_rgb[1] = 255;
		} //end if
		//--

		//-- BLUE
		$color_rgb[2] = (int) $input_color_rgb[2];
		if($color_rgb[2] < 0) {
			$color_rgb[2] = 0;
		} elseif($color_rgb[2] > 255) {
			$color_rgb[2] = 255;
		} //end if
		//--

		//-- ALPHA CHANNEL
		if(Smart::array_size($input_color_rgb) > 3) {
			//--
			$color_rgb[3] = (int) $input_color_rgb[3];
			if($color_rgb[3] < 0) {
				$color_rgb[3] = 0;
			} elseif($color_rgb[3] > 127) {
				$color_rgb[3] = 127;
			} //end if else
			//--
		} //end if
		//--

		//--
		return (array) $color_rgb;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _testImageSizeAndType($area, $type, $width, $height) {

		//-- test type
		switch((string)$type) {
			case 'gif':
			case 'png':
			case 'jpg':
			case 'webp':
				// OK
				break;
			default:
				$this->_debugMsg((string)__METHOD__.' :: '.'Unknown or Invalid Image ['.$area.'] Type (not PNG/GIF/JPG/WEBP): '.$type);
				return false;
		} //end switch
		//--

		//-- test dimensions
		if($width <= 0) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Unknown or Invalid Image ['.$area.'] Size: Width: '.$width);
			return false;
		} //end if
		if($height <= 0) {
			$this->_debugMsg((string)__METHOD__.' :: '.'Unknown or Invalid Image ['.$area.'] Size: Height: '.$height);
			return false;
		} //end if
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _getImgSizeAndTypeFromImgStr($imgstr) {

		//--
		$arr = [
			't' => '', // type
			'w' => 0,  // width
			'h' => 0   // height
		];
		//--

		//--
		if((string)$imgstr == '') {
			return (array) $arr;
		} //end if
		//--

		//--
		$arinfo 	= (array) @getimagesizefromstring((string)$imgstr);
		$width 		= (int) $arinfo[0]; // not used here
		$height 	= (int) $arinfo[1]; // not used here
		$imgtyp 	= (int) $arinfo[2]; // image type constant
		//--
		$imgstr = ''; // free mem
		unset($arinfo); // cleanup
		//--

		//--
		if($imgtyp > 0) { // if a valid type detected
			//--
			$ext = (string) strtolower((string)@image_type_to_extension((int)$imgtyp, true)); // returns the image extension with . (dot) prepend
			//--
			$arr['w'] = (int) Smart::format_number_int($width, '+');
			$arr['h'] = (int) Smart::format_number_int($height, '+');
			//--
			switch((string)$ext) {
				case '.gif':
					$arr['t'] = 'gif';
					break;
				case '.png':
					$arr['t'] = 'png';
					break;
				case '.jpg':
				case '.jpeg':
					$arr['t'] = 'jpg';
					break;
				case '.webp':
					$arr['t'] = 'webp';
					break;
				default:
					$arr['t'] = ''; // other type, but unusable
			} //end switch
			//--
		} else {
			//--
			$arr['t'] = ''; // unknown type
			//--
		} //end if
		//--

		//--
		return (array) $arr;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _applyTextLine($line, $width, $margin, $angle, $size, $offsy, $align, $font, $color_rgb) {
		//--
		$width = (int) floor((int)$width - ((int)$margin * 2));
		//--
		$bbox = $this->calculateTextBBox((string)$line, (int)$angle, (int)$size, (string)$font);
		//--
		$pos_x_center = (int) ceil((((int)$width - (float)$bbox[0]) / 2) - ((float)$bbox[2] / 2));
		$pos_x_right  = (int) floor((int)$width - (float)$bbox[2]);
		$pos_x_left   = 1;
		//--
		$pos_y_center = (int) $offsy;
		$pos_y_right  = (int) $offsy;
		$pos_y_left   = (int) $offsy;
		//--
		switch((string)$align) {
			case 'center': // TODO: calculate X and Y by angle, by now it enforces align LEFT with any angle other than zero
				$pos_x = (int) $pos_x_center + $margin;
				$pos_y = (int) $pos_y_center;
				break;
			case 'right': // TODO: calculate X and Y by angle, by now it enforces align LEFT with any angle other than zero
				$pos_x = (int) $pos_x_right + $margin;
				$pos_y = (int) $pos_y_right;
				break;
			case 'left':
			default:
				$pos_x = (int) $pos_x_left + $margin;
				$pos_y = (int) $pos_y_left;
		} //end switch
		//--
		$this->applyText((string)$line, (int)$pos_x, (int)$pos_y, (int)$angle, (int)$size, (string)$font, (array)$color_rgb);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function testIfValidImg($img) {
		//--
		if(is_resource($img) OR is_a($img, '\\GdImage')) { // fix to be PHP8 compatible # https://php.watch/versions/8.0/gdimage
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
