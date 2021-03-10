<?php
// [LIB - Smart.Framework / Plugins / Captcha Form]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Captcha Form
// DEPENDS:
//	* Smart::
//	* SmartUtils::
//	* SmartTextTranslations::
// REQUIRED CSS:
//	* captcha.css
// REQUIRED JS:
//	* jquery.js
//	* smart-framework.pak.js
// REQUIRED TEMPLATES:
//	* captcha-form.inc.htm
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// [REGEX-SAFE-OK]

/**
 * Class: SmartCaptcha - Manages, Render and Check the Captcha Form.
 *
 * <code>
 * //==
 * //-- captch form needs a captcha plugin to work with (ex: image)
 * // See: \SmartModExtLib\Samples\TestUnitMain::captchaImg()
 * // Also See: modules/mod-samples/testunit.php # case 'testunit.captcha'
 * // The method SmartCaptcha::initCaptchaPlugin() must be used to init a captcha plugin (ex: image)
 * //-- captcha form (draw)
 * echo SmartCaptcha::drawCaptchaForm('form_name', '?page=samples.testunit&op=testunit.captcha'); // this controller should output HTML code to render the form
 * //-- captcha check (verify)
 * echo $check = SmartCaptcha::verifyCaptcha('form_name', true, 'cookie'); // and this is the way you verify the captcha (1 = ok ; 0 = not ok)
 * //-- some more info on verify()
 * // captcha will reset (clear) by default upon each SmartCaptcha::verifyCaptcha()
 * // to avoid this (default) behaviour, you can set the 3rd parameters of verify() to FALSE
 * // but if you do so, don't forget to manually clear captcha by calling SmartCaptcha::clearCaptcha() at the end !!!
 * //--
 * //==
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		To render captcha, SmartCaptcha::drawCaptchaForm() and a captcha plugin is required. To verify, use SmartCaptcha::verifyCaptcha() ; SmartCaptcha::clearCaptcha() is optional to be call after verify, depending how SmartCaptcha::verifyCaptcha() is called
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartUtils, SmartTextTranslations ; javascript: jquery.js, smart-framework.pak.js ; css: captcha.css
 * @version 	v.20210310
 * @package 	development:Captcha
 *
 */
final class SmartCaptcha {

	// ::


	//================================================================
	/**
	 * Inits a captha plugin by setting the required values in cookie or session depend how mode is set
	 * This should be used for internal development only of new captcha Plugins (ex: image)
	 *
	 * @param $y_form_name 		STRING the name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_captcha_word 	The Captcha Word to be initialized (this must be supplied by the Captcha Plugin)
	 * @param $y_mode 			ENUM the storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	public static function initCaptchaPlugin($y_form_name, $y_captcha_word, $y_mode='cookie') {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		if(self::validate_form_name($y_form_name) !== true) {
			return false;
		} //end if
		//--
		if((string)trim((string)$y_captcha_word) == '') {
			return false;
		} //end if
		//--
		$ok = (bool) SmartUtils::set_cookie(self::cookie_name_chk($y_form_name), (string)sha1((string)$y_form_name.SMART_FRAMEWORK_SECURITY_KEY));
		if(!$ok) {
			return false;
		} //end if
		//--
		if((string)$y_mode == 'session') {
			$ok = (bool) SmartSession::set(self::cookie_name_frm($y_form_name), self::cksum_hash($y_captcha_word));
		} else {
			$ok = (bool) SmartUtils::set_cookie(self::cookie_name_frm($y_form_name), self::cksum_hash($y_captcha_word));
		} //end if else
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draw the Captcha Form partial HTML
	 * Requires a captcha plugin (ex: image)
	 *
	 * @param $y_form_name 			STRING 	The name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_captcha_image_url 	MIXED 	The URL to a Captcha Plugin ; Example STRING: 'index.php?page=mymodule.mycaptcha-image' ; If NULL will use the interractive captcha
	 * @param $y_mode 				ENUM 	The storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @param $y_use_absolute_url 	BOOL 	If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 * @return 						STRING 	The partial captcha HTML to include in a form
	 */
	public static function drawCaptchaForm($y_form_name, $y_captcha_image_url=null, $y_mode='cookie', $y_use_absolute_url=false) {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		if(self::validate_form_name($y_form_name) !== true) {
			return 'ERROR: Invalid Captcha Form Name';
		} //end if
		//--
		$js_cookie_name = (string) self::cookie_name_jsc($y_form_name);
		//--
		$release_time = (int) time();
		//--
		$translator_core_captcha = SmartTextTranslations::getTranslator('@core', 'captcha');
		//--
		$uuid = (string) strtoupper(Smart::uuid_10_num().'-'.Smart::uuid_10_str());
		$js_solver = "var SmartCaptchaChecksum = SmartJS_BrowserUtils.getCookie('".Smart::escape_js(self::cookie_name_chk($y_form_name))."'); if(SmartCaptchaChecksum == '') { SmartCaptchaChecksum = 'invalid-captcha'; alert('".Smart::escape_js($translator_core_captcha->text('error'))."'); } var smartCaptchaTimerCookie = new Date(); var smartCaptchaCookie = SmartJS_CoreUtils.bin2hex(SmartJS_CryptoBlowfish.encrypt(SmartJS_Base64.encode(smartCaptchaTimerCookie.getTime() + '!' + String(SmartJS_CryptoBlowfish.decrypt(fldVal,String(kZ))) + '!Smart.Framework'), SmartJS_CoreUtils.stringTrim(SmartCaptchaChecksum))); SmartJS_BrowserUtils.setCookie('".Smart::escape_js($js_cookie_name)."', smartCaptchaCookie);";
		//--
		if($y_use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) SmartUtils::get_server_current_url();
		} //end if else
		//--
		if($y_captcha_image_url) {
			//--
			$captcha_url = (string) $y_captcha_image_url;
			$captcha_url = (string) Smart::url_add_suffix($captcha_url, 'captcha_form='.rawurlencode($y_form_name));
			$captcha_url = (string) Smart::url_add_suffix($captcha_url, 'captcha_mode=image');
			$captcha_url = (string) Smart::url_add_suffix($captcha_url, 'new=');
			//--
			$qrcode_str = ''; // n/a in this context
			//--
			$input_style = '';
			//--
			$tpl = 'lib/core/plugins/templates/captcha-form-image.inc.htm';
			//--
		} else {
			//--
			$captcha_obj = new SmartSVGCaptcha(5, 175, 50, -1);
			$captcha_url = (string) $captcha_obj->draw_image();
			$captcha_code = (string) $captcha_obj->get_code();
			$captcha_obj = null; // free mem
			if(!self::initCaptchaPlugin((string)$y_form_name, (string)$captcha_code, (string)$y_mode)) {
				return 'Captcha Form Init ERROR ...';
			} //end if
			$zsvg_fx = '(function(zSVG){return new zSVG;})(this.key('.(int)$release_time.'));';
			$captcha_url = (string) SmartUtils::crypto_blowfish_encrypt('data:image/svg+xml;base64,'.base64_encode((string)$captcha_url), (string)$zsvg_fx.'object');
			$captcha_url = (string) base64_encode('if((sim >= 0.85) && (sim <= 0.95)) { zSim = Number(sim); var fldVal = \''.SmartUtils::crypto_blowfish_encrypt((string)$captcha_code, (string)strtoupper((string)$uuid)).'\'; var kZ = String(jQuery(\'#Smart-Captcha-Container-'.strtolower((string)$uuid).'\').find(\'input\').data(\'id\')).toUpperCase(); '.$js_solver.' } else { zSVG = SmartJS_CryptoBlowfish.decrypt(\''.Smart::escape_js($captcha_url).'\', \''.Smart::escape_js($zsvg_fx).'\'+String(typeof([])).toLowerCase()); }');
			//-- the min sim is 0.75 (but too perfect is not human, so max is 0.95, but trusted is 0.85..0.95)
			$qsvg_fx = '(function(qSVG){return new qSVG;})(this.key('.(int)$release_time.'));';
			$qrcode_str = (string) (new SmartQR2DBarcode('L'))->renderAsSVG((string)$captcha_code, ['cm'=>'#888888','wq'=>0]);
			$qrcode_str = (string) SmartUtils::crypto_blowfish_encrypt('data:image/svg+xml;base64,'.base64_encode((string)$qrcode_str), (string)$qsvg_fx.'string');
			$qrcode_str = (string) base64_encode('qSVG = SmartJS_CryptoBlowfish.decrypt(\''.Smart::escape_js($qrcode_str).'\', \''.Smart::escape_js($qsvg_fx).'\'+String(typeof(\'\')).toLowerCase());');
			//--
			$input_style = 'display:none;';
			//--
			$tpl = 'lib/core/plugins/templates/captcha-form.inc.htm';
			//--
		} //end if else
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			(string) $tpl,
			[
				'BASE-URL' 					=> (string) $the_abs_url,
				'RELEASE-HASH' 				=> (string) SmartFrameworkRuntime::getAppReleaseHash(),
				'RELEASE-UUID' 				=> (string) strtolower((string)$uuid),
				'RELEASE-TIME' 				=> (int)    $release_time,
				'CAPTCHA-RAND' 				=> (string) '0'.Smart::random_number(10,99), // must start with zero to avoid toFixed(0) roundUp
				'CAPTCHA-QR-CODE' 			=> (string) $qrcode_str,
				'CAPTCHA-PASSED' 			=> (string) $translator_core_captcha->text('passed'),
				'CAPTCHA-QR-HELPER' 		=> (string) $translator_core_captcha->text('helper'),
				'CAPTCHA-TXT-IMG' 			=> (string) $translator_core_captcha->text('image'),
				'CAPTCHA-TXT-CONFIRM' 		=> (string) $translator_core_captcha->text('confirm'),
				'CAPTCHA-IMG-TITLE' 		=> (string) $translator_core_captcha->text('click'),
				'CAPTCHA-TXT-VERIFY' 		=> (string) $translator_core_captcha->text('verify'),
				'CAPTCHA-TXT-ENTER' 		=> (string) $translator_core_captcha->text('enter'),
				'CAPTCHA-TXT-INTERRACTIVE' 	=> (string) $translator_core_captcha->text('interractive'),
				'CAPTCHA-IMG-SRC' 			=> (string) $captcha_url,
				'CAPTCHA-INPUT-STYLE' 		=> (string) $input_style,
				'CAPTCHA-JS-FIELD-BLUR' 	=> (string) SmartUtils::crypto_blowfish_encrypt("try { var kZ = String(fld.data('id')).toUpperCase(); var fldVal = SmartJS_CryptoBlowfish.encrypt(fld.val().toUpperCase(),String(kZ)); ".$js_solver." } catch(err) { console.error('Captcha ERROR: ' + err); } if(fld.val()) { fld.val('*******'); }", strtolower('Object'.'_'.'ID').':'.((string)(int)$release_time).'infinity!=NaN')
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Verify Captcha and *OPTIONAL* Clear It
	 *
	 * @param $y_form_name 		STRING the name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_clear 			BOOLEAN if clear Captcha on verify success ; Default is TRUE ; If TRUE if the captcha verification pass will clear all value from the storage (cookie or session)
	 * @param $y_mode 			ENUM the storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	public static function verifyCaptcha($y_form_name, $y_clear=true, $y_mode='cookie') {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		if(self::validate_form_name($y_form_name) !== true) {
			return false; // invalid form name
		} //end if
		//--
		$cookie_name = (string) self::cookie_name_frm($y_form_name);
		//--
		if((string)$y_mode == 'session') {
			//--
			$cookie_value = (string) SmartSession::get((string)$cookie_name);
			$run_mode = 'session';
			//--
		} else {
			//--
			$cookie_value = (string) SmartUtils::get_cookie((string)$cookie_name);
			$run_mode = 'cookie';
			//--
		} //end if else
		//--
		$var_name = (string) self::cookie_name_jsc($y_form_name);
		$var_value = (string) trim((string)SmartUtils::get_cookie((string)$var_name));
		//--
		$arr_value = array();
		if((string)$var_value != '') {
			$arr_value = (array) explode('!', (string)base64_decode((string)SmartUtils::crypto_blowfish_decrypt(hex2bin((string)$var_value), sha1($y_form_name.SMART_FRAMEWORK_SECURITY_KEY)))); // explode by '!'
		} //end if
		//--
		$ok = false; // error check by default
		//--
		if((strlen($var_value) > 0) AND ((string)$cookie_value == (string)self::cksum_hash((string)trim((string)(isset($arr_value[1]) ? $arr_value[1] : ''))))) {
			//--
			$ok = true;
			//--
			if($y_clear === true) { // clear is optional (there are situations when after veryfying captcha, even if OK, other code must be run and if that code returns error, still captcha must be active, not cleared (so clearing it manually is a solution ...)
				self::clearCaptcha($y_form_name, $y_mode);
			} //end if
			//--
		} //end if
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Programatically clear the Captcha (from cookie or session)
	 * On Verify Success the Captcha clears automatically all stored values
	 *
	 * @param $y_form_name 		STRING the name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_mode 			ENUM the storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	public static function clearCaptcha($y_form_name, $y_mode='cookie') {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		if(self::validate_form_name($y_form_name) !== true) {
			return false; // invalid form name
		} //end if
		//--
		$cookie_name = (string) self::cookie_name_frm($y_form_name);
		//--
		if((string)$y_mode == 'session') {
			//--
			$ok = (bool) SmartSession::unsets((string)$cookie_name); // unset from session
			//--
		} else {
			//--
			$ok = (bool) SmartUtils::unset_cookie((string)$cookie_name); // unset cookie
			//--
		} //end if else
		//--
		return (bool) $ok; // OK
		//--
	} //END FUNCTION
	//================================================================


	//===== PRIVATES


	//================================================================
	private static function validate_form_name($y_form_name) {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		$out = true;
		//--
		if((string)$y_form_name == '') {
			$out = false; // empty form name
		} //end if
		//--
		if(!preg_match('/^[A-Za-z0-9_\-]+$/', (string)$y_form_name)) {
			$out = false; // invalid characters in form name
		} //end if
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cksum_hash($y_code) {
		//--
		return (string) sha1('Captcha#Code'.$y_code.SMART_FRAMEWORK_SECURITY_KEY);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_jsc($y_form_name) {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SmartCaptcha_DATA_'.sha1($y_form_name.SMART_FRAMEWORK_SECURITY_KEY);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_chk($y_form_name) {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SmartCaptcha_CHK_'.sha1($y_form_name.SMART_FRAMEWORK_SECURITY_KEY);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_frm($y_form_name) {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SmartCaptcha_CODE_'.sha1($y_form_name.SMART_FRAMEWORK_SECURITY_KEY);
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
