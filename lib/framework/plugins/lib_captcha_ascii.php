<?php
// [LIB - Smart.Framework / Plugins / Captcha ASCII Image]
// (c) 2006-present unix-world.org - all rights reserved
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
 * @copyright (c) 2021-present unix-world.org
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
 * @version 	v.20250714
 * @package 	development:Captcha
 */
final class SmartAsciiCaptcha {

	// ::

	private const NUM_CHARS = 5;
	private const FONT_SIZE = 0.375; // default font size, rem ; 0.250 ... 0.750 as rem ; 4px = 0.250rem ; 5px = 0.313rem ; 6px = 0.375rem ; 7px = 0.438rem ; 8px = 0.500rem ; 9px = 0.563rem ; 10px = 0.625rem ; 11px = 0.688rem ; 12px = 0.750rem

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
		' *    *    *   ******  ***     *     ***         * * * *',
	];


	//======= [PUBLICS]


	public static function getCaptchaImageAndCode(bool $greyscale, float $size=0, int $numChars=0, string $pool='247AcEFHKLMNPRTuVwxYz') : array {
		//--
		$numChars = (int) $numChars;
		if($numChars <= 0) {
			$numChars = (int) self::NUM_CHARS;
		} //end if
		//--
		if((int)$numChars < 3) { // {{{SYNC-ASCII-CAPTCHA-MIN}}}
			$numChars = 3;
		} elseif((int)$numChars > 7) { // {{{SYNC-ASCII-CAPTCHA-MAX}}}
			$numChars = 7;
		} //end if
		//--
		if((int)$numChars > (int)strlen((string)$pool)) { // safety check
			$numChars = (int) strlen((string)$pool);
		} //end if
		//--
		$size = (float) (0 + (float)number_format((float)$size, 3, '.', ''));
		if($size <= 0) {
			$size = (float) self::FONT_SIZE;
		} //end if
		if((float)$size < 0.250) { // {{{SYNC-ASCII-CAPTCHA-FONT-MIN}}}
			$size = 0.250;
		} elseif((float)$size > 0.750) { // {{{SYNC-ASCII-CAPTCHA-FONT-MAX}}}
			$size = 0.750;
		} //end if
		//--
		$pool = (string) trim((string)$pool);
		if(((string)$pool == '') OR (!preg_match('/^[A-Za-z0-9]+$/', (string)$pool))) {
			$pool = (string) self::CAPTCHA_GLYPHS_MAP;
		} //end if
		//--
		$code = (string) self::generateRandStr((string)$pool, (int)$numChars);
		$ascii = (string) self::generateAsciiart((string)$code);
		//--
		return (array) [
			'code' 	=> (string) strtoupper((string)$code),
			'html' 	=> (string) '<div><div class="Smart-Captcha-AsciiArt" style="border:1px solid #E7E7E7; display:inline-block!important; padding:0!important; padding-left:5px; padding-right:5px;"><pre style="margin:3px!important; padding:0!important; font-weight:bold!important; font-size:'.(float)$size.'rem!important; line-height:'.(float)$size.'rem!important; background:#FFFFFF!important;">'."\n".self::renderHtml((string)$ascii, (bool)$greyscale)."\n".'</pre></div></div>',
		];
		//--
	} //END FUNCTION


	//======= [PRIVATES]


	private static function generateRandStr(string $pool, int $numChars) : string {
		//--
		$numChars = (int) $numChars;
		//--
		if((int)$numChars < 3) { // {{{SYNC-ASCII-CAPTCHA-MIN}}}
			$numChars = 3;
		} elseif((int)$numChars > 7) { // {{{SYNC-ASCII-CAPTCHA-MAX}}}
			$numChars = 7;
		} //end if
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
		for($i=0; $i<(int)$numChars; $i++) {
			$str .= (string) substr((string)$pool, (int)Smart::random_number(0, (int)$len), 1);
		} //end for
		//--
		return (string) $str;
		//--
	} //END FUNCTION


	private static function getRandNum() : int {
		//--
		return (int) Smart::random_number(0, -1); // 0 ... max
		//--
	} //END FUNCTION


	private static function randColorShift() : int {
		//--
		return (int) ((int)self::getRandNum() % 50); // return values between 0 and 49
		//--
	} //END FUNCTION


	private static function randColorLow() : int { // return values between 65 and 177
		//--
		return (int) ((int)Smart::random_number(16, 128) + (int)self::randColorShift()); // should not overflow 255 ; rand color shift return values between 0 and 49 !!!
		//--
	} //END FUNCTION


	private static function randColorHigh() : int { // return values between: 241 and 253
		//--
		return (int) ((int)Smart::random_number(192, 204) + (int)self::randColorShift()); // should not overflow 255 ; rand color shift return values between 0 and 49 !!!
		//--
	} //END FUNCTION


	private static function renderHtml(string $asciiart, bool $greyscale) : string {
		//--
		$ret = '';
		//--
		$in = (string) $asciiart;
		//--
		for($i=0; $i<strlen((string)$in); $i++) {
			//--
			$c = (string) substr((string)$in, (int)$i, 1);
			//--
			if(ord($c) < 32) {
				//--
				$ret .= "\n";
				//--
			} else {
				//--
				$fsr  = (int) self::randColorLow();
				$fsg  = (int) ((!!$greyscale) ? $fsr : self::randColorLow());
				$fsb  = (int) ((!!$greyscale) ? $fsr : self::randColorLow());
				//--
				$fwr  = (int) self::randColorHigh();
				$fwg  = (int) ((!!$greyscale) ? $fwr : self::randColorHigh());
				$fwb  = (int) ((!!$greyscale) ? $fwr : self::randColorHigh());
				//--
				$cols = (string) sprintf('%x%x%x', (int)$fsr, (int)$fsg, (int)$fsb);
				$colw = (string) sprintf('%x%x%x', (int)$fwr, (int)$fwg, (int)$fwb);
				//--
				$ret .= '<span style="color:#'.Smart::escape_html((string)(((string)$c === (string)self::CAPTCHA_GLYPHS_CHR) ? $cols : $colw)).'!important;">'.Smart::escape_html((string)self::CAPTCHA_GLYPHS_RPL).'</span>';
				//--
			} //end if else
			//--
		} //end for
		//--
		return (string) trim((string)$ret);
		//--
	} //END FUNCTION


	private static function generateAsciiEmptyLine(int $numChars) : string {
		//--
		$numChars = (int) $numChars;
		//--
		if((int)$numChars < 3) { // {{{SYNC-ASCII-CAPTCHA-MIN}}}
			$numChars = 3;
		} elseif((int)$numChars > 7) { // {{{SYNC-ASCII-CAPTCHA-MAX}}}
			$numChars = 7;
		} //end if
		//--
		return (string) str_repeat(' ', (int)(11 * (int)$numChars));
		//--
	} //END FUNCTION


	private static function generateAsciiart(string $codestr) : string {
		//--
		$word = (string) $codestr;
		//--
		$emptyLine = (string) self::generateAsciiEmptyLine((int)strlen((string)$codestr));
		//--
		$ret = (string) $emptyLine."\n"; // add a blank line above
		//--
		for($j=0; $j<7; $j++) {
			$line = '';
			for($k=0; $k<(int)strlen((string)$word); $k++) {
				$ind = (int) ord((string)substr((string)$word, (int)$k, 1)) - 32 ;
				$a = (int) (Smart::floor_number((int)$ind / 8) * 7 + (int)$j);
				$b = (int) ((int)$ind % 8 * 7);
				$line .= '  ';
				$line .= (string) substr((string)self::CAPTCHA_GLYPHS_ARR[(int)$a], (int)$b, 7);
				$line .= '  ';
			} //end for
			$ret .= (string) sprintf('%s'."\n", (string)$line);
		} //end for
		//--
		$ret .= (string) $emptyLine."\n"; // add a blank line above
		//--
		return (string) $ret;
		//--
	} //END FUNCTION


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
