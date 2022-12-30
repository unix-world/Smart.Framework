<?php
// [LIB - Smart.Framework / Plugins / Captcha ASCII Image]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Captcha ASCII Image
// DEPENDS:
//	* Smart::
//======================================================


//==================================================================
/*
 * ASCII Captcha Plugin
 * Generate ASCII (art) captchas.
 * This version contains many changes and optimizations from the original work.
 * @author unixman
 * @copyright (c) 2021 unix-world.org
 * @license: BSD
 *
 * Original work: ASCII Captcha, https://github.com/bohnelang/ascii_captcha # head.20210317
 * @copyright (c) 2021, Andreas Bohne-Lang
 * @license https://github.com/bohnelang/ascii_captcha/blob/master/LICENSE # CC0
 *
 * It will draw a very secure captcha using ASCII art converted to HTML code.
 * Since all the ASCII art is converted to HTML made of # (hashes) only and code visibility is made by color contrast of different # is almost impossible for robots to pass this !
*/
//==================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: ASCII Captcha - An ASCII Image Plugin for Smart Captcha
 * Create a Form Captcha Validation ASCII Art Text Image (HTML)
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart
 * @version 	v.20220925
 * @package 	development:Captcha
 */
final class SmartAsciiCaptcha {

	// ::

	private static $numchars = 5;
	private static $size = 0.33; // 0.1 ... 1
	private static $greyscale = true; // false / true

	private static $RANDSTR = null;

	private const CAPTCHA_GLYPHS_MAP = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	private const CAPTCHA_GLYPHS_RPL = '#'; // hash # as render character is the best choice because the html hex colors are prefixed also with a hash as: #RRGGBB
	private const CAPTCHA_GLYPHS_CHR = '*';
	private const CAPTCHA_GLYPHS_ARR = [
		'         ***  **   **  * *   *****          **     ***  ',
		'         ***  **   **  * *  *  *  ***   *  *  *    ***  ',
		'         ***   *   * ********  *   **  *    **      *   ',
		'          *            * *   *****    *    ***     *    ',
		'                     *******   *  *  *    *   * *       ',
		'         ***           * *  *  *  * *  ** *    *        ',
		'         ***           * *   ***** *   **  **** *       ',

		'   **    **                                            *',
		'  *        *   *   *    *                             * ',
		' *          *   * *     *                            *  ',
		' *          * ******* *****   ***   *****           *   ',
		' *          *   * *     *     ***                  *    ',
		'  *        *   *   *    *      *            ***   *     ',
		'   **    **                   *             ***  *      ',

		'  ***     *    *****  ***** *      ******* ***** *******',
		' *   *   **   *     **     **    * *      *     **    * ',
		'*   * * * *         *      **    * *      *          *  ',
		'*  *  *   *    *****  ***** ******* ***** ******    *   ',
		'* *   *   *   *            *     *       **     *  *    ',
		' *   *    *   *      *     *     * *     **     *  *    ',
		'  ***   ***** ******* *****      *  *****  *****   *    ',

		' *****  *****          ***      *           *     ***** ',
		'*     **     *  ***    ***     *             *   *     *',
		'*     **     *  ***           *     *****     *        *',
		' *****  ******         ***   *                 *     ** ',
		'*     *      *         ***    *     *****     *     *   ',
		'*     **     *  ***     *      *             *          ',
		' *****  *****   ***    *        *           *       *   ',

		' *****    *   ******  ***** ****** ************** ***** ',
		'*     *  * *  *     **     **     **      *      *     *',
		'* *** * *   * *     **      *     **      *      *      ',
		'* * * **     ******* *      *     ******  *****  *  ****',
		'* **** ********     **      *     **      *      *     *',
		'*     **     **     **     **     **      *      *     *',
		' ***** *     *******  ***** ****** ********       ***** ',

		'*     *  ***        **    * *      *     **     ********',
		'*     *   *         **   *  *      **   ****    **     *',
		'*     *   *         **  *   *      * * * ** *   **     *',
		'*******   *         ****    *      *  *  **  *  **     *',
		'*     *   *   *     **  *   *      *     **   * **     *',
		'*     *   *   *     **   *  *      *     **    ***     *',
		'*     *  ***   ***** *    * ********     **     ********',

		'******  ***** ******  ***** ********     **     **     *',
		'*     **     **     **     *   *   *     **     **  *  *',
		'*     **     **     **         *   *     **     **  *  *',
		'****** *     *******  *****    *   *     **     **  *  *',
		'*      *   * **   *        *   *   *     * *   * *  *  *',
		'*      *    * *    * *     *   *   *     *  * *  *  *  *',
		'*       **** **     * *****    *    *****    *    ** ** ',

		'*     **     ******** ***** *       *****    *          ',
		' *   *  *   *      *  *      *          *   * *         ',
		'  * *    * *      *   *       *         *  *   *        ',
		'   *      *      *    *        *        *               ',
		'  * *     *     *     *         *       *               ',
		' *   *    *    *      *          *      *               ',
		'*     *   *   ******* *****       * *****        *******',

		'  ***                                                   ',
		'  ***     **   *****   ****  *****  ****** ******  **** ',
		'   *     *  *  *    * *    * *    * *      *      *    *',
		'    *   *    * *****  *      *    * *****  *****  *     ',
		'        ****** *    * *      *    * *      *      *  ***',
		'        *    * *    * *    * *    * *      *      *    *',
		'        *    * *****   ****  *****  ****** *       **** ',

		'                                                        ',
		' *    *    *        * *    * *      *    * *    *  **** ',
		' *    *    *        * *   *  *      **  ** **   * *    *',
		' ******    *        * ****   *      * ** * * *  * *    *',
		' *    *    *        * *  *   *      *    * *  * * *    *',
		' *    *    *   *    * *   *  *      *    * *   ** *    *',
		' *    *    *    ****  *    * ****** *    * *    *  **** ',

		'                                                        ',
		' *****   ****  *****   ****   ***** *    * *    * *    *',
		' *    * *    * *    * *         *   *    * *    * *    *',
		' *    * *    * *    *  ****     *   *    * *    * *    *',
		' *****  *  * * *****       *    *   *    * *    * * ** *',
		' *      *   *  *   *  *    *    *   *    *  *  *  **  **',
		' *       *** * *    *  ****     *    ****    **   *    *',

		'                       ***     *     ***   **    * * * *',
		' *    *  *   * ****** *        *        * *  *  * * * * ',
		'  *  *    * *      *  *        *        *     ** * * * *',
		'   **      *      *  **                 **        * * * ',
		'   **      *     *    *        *        *        * * * *',
		'  *  *     *    *     *        *        *         * * * ',
		' *    *    *   ******  ***     *     ***         * * * *'
	];


	//======= [PUBLICS]


	public static function getCaptchaImageAndCode(?int $numchars=null, ?float $size=null, ?bool $greyscale=null, string $pool='247AcEFHKLMNPRTuVwxYz') {
		//--
		if($numchars === null) {
			$numchars = (int) self::$numchars;
		} //end if
		$numchars = (int) $numchars;
		if((int)$numchars < 3) {
			$numchars = 3;
		} elseif((int)$numchars > 7) {
			$numchars = 7;
		} //end if
		if((int)$numchars > (int)strlen((string)$pool)) { // safety check
			$numchars = (int) strlen((string)$pool);
		} //end if
		self::$numchars = (int) $numchars;
		//--
		if($size === null) {
			$size = (float) self::$size;
		} //end if
		$size = (float) (0 + (float)number_format((float)$size, 2, '.', ''));
		if((float)$size < 0.1) {
			$size = 0.1;
		} elseif((float)$size > 1) {
			$size = 1;
		} //end if
		self::$size = (float) $size;
		//--
		if($greyscale === null) {
			$greyscale = (bool) self::$greyscale;
		} //end if
		self::$greyscale = (bool) $greyscale;
		//--
		$pool = (string) trim((string)$pool);
		if(((string)$pool == '') OR (!preg_match('/^[A-Za-z0-9]+$/', (string)$pool))) {
			$pool = (string) self::CAPTCHA_GLYPHS_MAP;
		} //end if
		//--
		self::resetRandStr();
		self::generateRandStr((string)$pool);
		//--
		$ascii = (string) self::generateAsciiart((string)self::getTheRandStr());
		//--
		return (array) [
			'code' 	=> (string) strtoupper((string)self::getTheRandStr()),
			'html' 	=> (string) '<div><div class="Smart-Captcha-AsciiArt" style="border:1px solid #E7E7E7; display:inline-block!important; padding:0!important; padding-left:5px; padding-right:5px;"><pre style="margin:3px!important; padding:0!important; font-weight:bold!important; font-size:'.(float)$size.'rem!important; line-height:'.(float)$size.'rem!important;">'."\n".self::renderHtml((string)$ascii)."\n".'</pre></div></div>'
		];
		//--
	} //END FUNCTION


	//======= [PRIVATES]


	private static function getTheRandStr() {
		//--
		return (string) self::$RANDSTR;
		//--
	} //END FUNCTION


	private static function resetRandStr() {
		//--
		self::$RANDSTR = ''; // reset
		//--
		return true;
		//--
	} //END FUNCTION


	private static function generateRandStr(string $pool) {
		//--
		$pool = (string) trim((string)$pool);
		//--
		$len = (int) strlen((string)$pool) - 1;
		if($len <= 0) {
			return '';
		} //end if
		//--
		$str = '';
		//--
		for($i=0; $i<(int)self::$numchars; $i++) {
			$str .= (string) substr($pool, Smart::random_number(0, (int)$len), 1);
		} //end for
		//--
		self::$RANDSTR = (string) $str;
		//--
		return true;
		//--
	} //END FUNCTION


	private static function getRandNum() {
		//--
		return (int) Smart::random_number(0, -1); // 0 ... max
		//--
	} //END FUNCTION


	private static function randColorShift() {
		//--
		return (int) ((int)self::getRandNum() % 50); // return values between 0 and 49
		//--
	} //END FUNCTION


	private static function randColorLow() { // return values between 65 and 177
		//--
		return (int) ((int)Smart::random_number(16, 128) + (int)self::randColorShift()); // should not overflow 255 ; rand color shift return values between 0 and 49 !!!
		//--
	} //END FUNCTION


	private static function randColorHigh() { // return values between: 241 and 253
		//--
		return (int) ((int)Smart::random_number(192, 204) + (int)self::randColorShift()); // should not overflow 255 ; rand color shift return values between 0 and 49 !!!
		//--
	} //END FUNCTION


	private static function renderHtml(string $asciiart) {
		//--
		$ret = '';
		//--
		$in = (string) $asciiart;
		//--
		for($i=0; $i<strlen((string)$in); $i++) {
			//--
			$c = (string) substr((string)$in, (int)$i, 1);
			//--
			if(ord($c) < 32){
				//--
				$ret .= "\n";
				//--
			} else {
				//--
				$fsr  = (int) self::randColorLow();
				$fsg  = (int) ((!!self::$greyscale) ? $fsr : self::randColorLow());
				$fsb  = (int) ((!!self::$greyscale) ? $fsr : self::randColorLow());
				//--
				$fwr  = (int) self::randColorHigh();
				$fwg  = (int) ((!!self::$greyscale) ? $fwr : self::randColorHigh());
				$fwb  = (int) ((!!self::$greyscale) ? $fwr : self::randColorHigh());
				//--
				$nc   = (string) substr((string)self::CAPTCHA_GLYPHS_MAP, (int)((int)self::getRandNum() % (int)strlen((string)self::CAPTCHA_GLYPHS_MAP)), 1);
				$cols = (string) sprintf("%x%x%x", (int)$fsr, (int)$fsg, (int)$fsb);
				$colw = (string) sprintf("%x%x%x", (int)$fwr, (int)$fwg, (int)$fwb);
				//--
				$ret .= '<span style="background:#FFFFFF!important; color:#'.Smart::escape_html((string)(((string)$c === (string)self::CAPTCHA_GLYPHS_CHR) ? $cols : $colw)).'!important;">'.Smart::escape_html((string)self::CAPTCHA_GLYPHS_RPL).'</span>';
				//--
			} //end if else
			//--
		} //end for
		//--
		return (string) trim((string)$ret);
		//--
	} //END FUNCTION


	private static function generateAsciiEmptyLine() {
		//--
		return (string) str_repeat(' ', (int)(11 * (int)self::$numchars));
		//--
	} //END FUNCTION


	private static function generateAsciiart(string $codestr) {
		//--
		$word = (string) $codestr;
		//--
		$ret = (string) self::generateAsciiEmptyLine()."\n"; // add a blank line above
		//--
		for($j=0; $j<7; $j++) {
			$line = '';
			$line = '';
			for($k=0; $k<(int)strlen((string)$word); $k++) {
				$ind = (int) ord((string)substr((string)$word, (int)$k, 1)) - 32 ;
				$a = (int) (floor((int)$ind / 8) * 7 + (int)$j);
				$b = (int) ((int)$ind % 8 * 7);
				$line .= '  ';
				$line .= (string) substr((string)self::CAPTCHA_GLYPHS_ARR[$a], (int)$b, 7);
				$line .= '  ';
			} //end for
			$ret .= (string) sprintf("%s\n", (string)$line);
		} //end for
		//--
		$ret .= self::generateAsciiEmptyLine()."\n"; // add a blank line above
		//--
		return (string) $ret;
		//--
	} //END FUNCTION


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
