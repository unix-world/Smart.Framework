<?php
// [LIB - Smart.Framework / Plugins / Captcha Form]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
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
//	* sf-icons.css
// REQUIRED JS:
//	* jquery.js
//	* smart-framework.pak.js + [growl, alertable]
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
 * // The method SmartCaptcha::initCaptchaPlugin() must be used only to init an external captcha plugin (ex: image)
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
 * @depends 	constants: SMART_FRAMEWORK_SECURITY_KEY ; classes: Smart, SmartUtils, SmartTextTranslations, SmartSVGCaptcha, SmartQR2DBarcode ; javascript: jquery.js, smart-framework.pak.js
 * @version 	v.20250107
 * @package 	Application:Plugins:Captcha
 *
 */
final class SmartCaptcha {

	// ::

	private const COOKIE_EXPIRE_TIME = 86400; // 24 hours ; to avoid send overlength head to web servers when using a lot of captchas

	private static $securityKey = null;


	//================================================================
	/**
	 * Inits a captha plugin by setting the required values in cookie or session depend how mode is set
	 * This should be used only with external captcha Plugins (ex: external captcha image by custom URL)
	 * This must not be used if the drawCaptchaForm() is using internal captcha mode with no external URL
	 *
	 * @param $y_form_name 		STRING the name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_captcha_word 	The Captcha Word to be initialized (this must be supplied by the Captcha Plugin)
	 * @param $y_mode 			ENUM the storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	public static function initCaptchaPlugin(?string $y_form_name, ?string $y_captcha_word, ?string $y_mode='cookie') : bool {
		//--
		return (bool) self::initCaptcha((string)$y_form_name, (string)$y_captcha_word, (string)$y_mode);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Inits a captha plugin by setting the required values in cookie or session depend how mode is set
	 * This should be used only with external captcha Plugins (ex: external captcha image by custom URL)
	 * This must not be used if the drawCaptchaForm() is using internal captcha mode with no external URL
	 *
	 * @param $y_form_name 		STRING the name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_captcha_word 	The Captcha Word to be initialized (this must be supplied by the Captcha Plugin)
	 * @param $y_mode 			ENUM the storage mode ; Can be set to 'cookie' or 'session'
	 * @param $y_dhkx_secret 	NULL or STRING ; set NULL for plugins ; for internal (smart) must be set to a valid DHKX server secret
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	private static function initCaptcha(string $y_form_name, string $y_captcha_word, string $y_mode, ?string $y_dhkx_secret=null) : bool {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		if(self::validate_form_name((string)$y_form_name) !== true) {
			return false;
		} //end if
		//--
		if((string)trim((string)$y_captcha_word) == '') {
			return false;
		} //end if
		//--
		$ok = (bool) SmartUtils::set_cookie((string)self::cookie_name_chk((string)$y_form_name), (string)self::cookie_val_frm((string)$y_form_name), (int)self::COOKIE_EXPIRE_TIME);
		if(!$ok) {
			return false;
		} //end if
		//--
		if($y_dhkx_secret === null) { // plugin
			$set_value = (string) self::cksum_hash((string)'plugin'."\t".$y_captcha_word).'|'; // must contain | even if there is no secret
		} else { // smart
			$y_dhkx_secret = (string) trim((string)$y_dhkx_secret);
			$y_dhkx_secret = (string) SmartCipherCrypto::encrypt((string)$y_dhkx_secret); // encrypted, only server can decrypt it: the algo and the secret are N/A on JS side ...
			$set_value = (string) self::cksum_hash((string)'smart'."\t".$y_captcha_word).'|'.$y_dhkx_secret; // prefixing with type is mandatory for security to dissalow an attacker modify code and skip DHKX on smart type
		} //end if
		//--
		if((string)$y_mode == 'session') {
			if((string)trim((string)SMART_APP_VISITOR_COOKIE) == '') { // {{{SYNC-SMART-UNIQUE-COOKIE}}}
				$ok = false; // session can't run without the UUID Cookie
				Smart::log_warning(__METHOD__.' # Captcha Session Mode: requires the UUID Cookie to be enabled as the session can not run without the UUID Cookie ...');
			} else {
				$ok = (bool) SmartSession::set((string)self::cookie_name_frm((string)$y_form_name), (string)$set_value);
			} //end if else
		} else { // cookie
			$ok = (bool) SmartUtils::set_cookie((string)self::cookie_name_frm((string)$y_form_name), (string)$set_value, (int)self::COOKIE_EXPIRE_TIME);
		} //end if else
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draw the Captcha Form partial HTML
	 * By default if no external URL is specified will use the internal interractive captcha
	 * IMPORTANT: When using the interractive internal captcha must not call initCaptchaPlugin() because the underlaying method initCaptcha() is automatically called !
	 *
	 * Difficulty levels for the internal interractive captcha:
	 * #very-easy
	 * #easy
	 * #moderate
	 * #hard
	 * #very-hard
	 *
	 * @param $y_form_name 			STRING 	The name of the HTML form to bind to ; This must be unique on a page with multiple forms
	 * @param $y_captcha_image_url 	MIXED 	Empty String / NULL or A Captcha difficulty level for internal interractive captcha ; The URL to a Captcha Plugin (ex: 'index.php?page=mymodule.mycaptcha-image')
	 * @param $y_mode 				ENUM 	The storage mode ; Can be set to 'cookie' or 'session' ; default is 'cookie'
	 * @param $y_use_absolute_url 	BOOL 	If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 * @return 						STRING 	The partial captcha HTML to include in a form
	 */
	public static function drawCaptchaForm(?string $y_form_name, ?string $y_captcha_image_url='', ?string $y_mode='cookie', bool $y_use_absolute_url=false, bool $y_include_js_requirements=false) : string {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		if(self::validate_form_name($y_form_name) !== true) {
			$err = 'ERR: Invalid Captcha Form Name';
			Smart::log_warning(__METHOD__.' # '.$err);
			return (string) SmartComponents::operation_error('<h2>'.(string)Smart::escape_html((string)$err).'</h2>');
		} //end if
		//--
		$js_cookie_name = (string) self::cookie_name_jsc((string)$y_form_name);
		//--
		$release_time = (int) time();
		//--
		$translator_core_captcha = SmartTextTranslations::getTranslator('@core', 'captcha');
		//--
		$uuid = (string) strtolower((string)Smart::uuid_10_num().'-'.Smart::uuid_10_str());
		//--
		$arrEDhkx = (array) SmartCipherCrypto::dhkx_enc();
		if((string)$arrEDhkx['err'] != '') {
			$err = 'ERR: Captcha eDhkx Failed';
			Smart::log_warning(__METHOD__.' # '.$err.': '.$arrEDhkx['err']);
			return (string) SmartComponents::operation_error('<h2>'.(string)Smart::escape_html((string)$err).'</h2>');
		} //end if
		$dhks = (string) $arrEDhkx['shad'];
		$dhkx = (string) $arrEDhkx['eidz'];
		$arrEDhkx = null;
		//--
		$arrDhkx = (array) self::get_dhkx_server_data();
		$dh = (string) $arrDhkx['dh'];
		$ds = (string) $arrDhkx['ds'];
		$arrDhkx = null;
		//--
		$js_exports = 'const u$ = smartJ$Utils; const d$ = smartJ$Date; const z$ = smartJ$BaseConv; const h$ = smartJ$CryptoHash; const c$ = smartJ$CipherCrypto; const c$e = c$.tfEnc; const c$d = c$.tfDec; const x$d = c$.dhkxDs; const b$ = smartJ$Browser; const s$ = \''.Smart::escape_js((string)SmartCipherCrypto::tf_encrypt((string)Smart::dataRRot13((string)$dh), (string)Smart::dataRRot13((string)$dhkx))).'\'; const smartCookieCaptcha = '."'".Smart::escape_js((string)self::cookie_name_chk((string)$y_form_name))."'".'; const data$Handler = (s,hash) => { data = String(u$.b64Enc(c$e(hash+u$.stringTrim(b$.getCookie(smartCookieCaptcha'.")||'')".'+\''.Smart::escape_js((string)'_'.Smart::create_jsvar((string)$uuid.'__'.$y_form_name)).'\')),u$.b64Dec(s)); return data; };';
		$js_solver = 'const SmartCaptchaChecksum = u$.addcslashes(u$.stringTrim(b$.getCookie('."'".Smart::escape_js((string)self::cookie_name_chk((string)$y_form_name))."') || ''), '\\x00..\\x1F'); if(!!!SmartCaptchaChecksum) { b$.AlertDialog('".Smart::escape_js((string)$translator_core_captcha->text('error'))."'); } else { const smartCaptchaTimerCookie = new Date(); const qx = '\\u0021' + (+[![]]) + '\\u0023'; const smartCaptchaCookie = ".'z$.base_from_hex_convert('.'u$.bin2hex('.'c$e(u$.stringTrim('."SmartCaptchaChecksum),".'u$.b64Enc('."smartCaptchaTimerCookie.getTime() + qx + String(".'c$d('."String(kZ),fldVal) + qx + ".'u$.b64Enc('."JSON.stringify(window.x\$exch||null)))))),62); ".'b$.setCookie('."'".Smart::escape_js((string)$js_cookie_name)."', smartCaptchaCookie, ".(int)self::COOKIE_EXPIRE_TIME."); }";
		//--
		if($y_use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) SmartUtils::get_server_current_url();
		} //end if else
		//--
		$y_captcha_image_url = (string) trim((string)$y_captcha_image_url);
		if(((string)$y_captcha_image_url != '') AND (strpos((string)$y_captcha_image_url, '#') !== 0)) {
			//--
			$captcha_url = (string) $y_captcha_image_url;
			$captcha_url = (string) Smart::url_add_suffix((string)$captcha_url, 'captcha_form='.Smart::escape_url((string)$y_form_name));
			$captcha_url = (string) Smart::url_add_suffix((string)$captcha_url, 'captcha_mode=image');
			$captcha_url = (string) Smart::url_add_suffix((string)$captcha_url, 'new=');
			//--
			$captcha_qurl = ''; // n/a in this context
			//--
			$input_style = '';
			//--
			$tpl = 'lib/core/plugins/templates/captcha-form-image.inc.htm';
			//--
		} else {
			//--
			$svg_difficulty_level = null;
			switch((string)$y_captcha_image_url) {
				case '#very-hard':
					$svg_difficulty_level = 3;
					break;
				case '#hard':
					$svg_difficulty_level = 2;
					break;
				case '#moderate':
					$svg_difficulty_level = 1;
					break;
				case '#easy':
					$svg_difficulty_level = 0;
					break;
				case '#very-easy':
				case '':
					$svg_difficulty_level = -1;
					break;
				default:
					Smart::log_warning(__METHOD__.' # Invalid Captcha Difficulty Mode: `'.$y_captcha_image_url.'`');
			} //end switch
			//--
			$captcha_url = null;
			$captcha_code = null;
			if(!((int)Smart::random_number(0, 100) % 2)) {
				$captcha_obj = new SmartSVGCaptcha(5, 185, 55, (int)$svg_difficulty_level);
				$captcha_url = (string) $captcha_obj->draw_image();
				$captcha_code = (string) $captcha_obj->get_code();
				$captcha_obj = null; // free mem
			} else { // TODO: implement difficulty level also in ASCII Captcha
				$captcha_arr = (array) SmartAsciiCaptcha::getCaptchaImageAndCode(true, 0.344, 5); // sync size to display best in smart captcha
				$captcha_url = (string) $captcha_arr['html'];
				$captcha_code = (string) $captcha_arr['code'];
				$captcha_arr = null; // free mem
			} //end if else
			if(!self::initCaptcha((string)$y_form_name, (string)$captcha_code, (string)$y_mode, (string)$ds)) {
				Smart::log_warning(__METHOD__.' # Failed to INIT SVG Captcha Plugin');
			} //end if
			$captcha_url = (string) Smart::b64_enc((string)$captcha_url);
			$js_interractive_solver = (string) 'covariancePointer(crrPointerPos.x, crrPointerPos.y); $entropy = h$.crc32b($form + \'#\' + data$Handler(JSON.stringify(b$.parseCurrentUrlGetParams()), d$.getIsoDate(new Date(), true))); $dhkx = \''.Smart::b64_enc((string)$dhks).'\';';
			$captcha_url = (string) Smart::b64_enc((string)SmartCipherCrypto::tf_encrypt('(() => { mFx = () => { if((typeof(jQuery) == \'undefined\') || (typeof(smartJ$Utils) == \'undefined\') || (typeof(u$) == \'undefined\') || (typeof(b$) == \'undefined\') || (typeof(c$e) == \'undefined\') || (typeof(mFy) == \'undefined\') || (typeof(sq) == \'undefined\') || (typeof(mTan) == \'undefined\') || (typeof(zSVG) == \'undefined\') || (typeof(event$Observer) != \'function\')) { console.warn(\'Captcha context is missing !\'); return; } if(!!event$Observer(mFy)) { let mX = 0, mY = 0; try { mX = u$.format_number_float(event.clientX); mY = u$.format_number_float(event.clientY); } catch(fail){} mTan = u$.format_number_float(Math.abs(Math.atan(Math.PI + Math.E + Math.sin(Math.abs(sq)) * (Math.pow(Math.tan(mX), 2) * Math.pow(Math.tan(mY), 2)))), false) + 1/100; let fldVal = \''.Smart::escape_js((string)SmartCipherCrypto::tf_encrypt((string)$captcha_code, (string)strtoupper((string)$uuid))).'\'; let kZ = String(jQuery(\'#Smart-Captcha-Container-'.Smart::escape_js((string)Smart::create_htmid((string)$uuid)).'\').find(\'input\').data(\'id\')).toUpperCase(); '.$js_solver.' } else { zSVG = \''.Smart::escape_js((string)$captcha_url).'\'; } }; })();', 'setInterval(() => { '.$js_exports.' '.$js_interractive_solver.' }, 700);'));
			//-- perfect scores are not for humans, but neither too low scores ...
			$captcha_qurl = (string) (new SmartQR2DBarcode('L'))->renderAsSVG((string)$captcha_code, ['cm'=>'#888888','wq'=>0]);
			$captcha_qurl = (string) Smart::b64_enc((string)SmartCipherCrypto::tf_encrypt('(() => { if((typeof(qSVG) == \'undefined\') || qSVG) { return; } qSVG = \''.Smart::escape_js((string)Smart::b64_enc((string)$captcha_qurl)).'\'; })();', 'setInterval(() => { '.$js_exports.' '.$js_interractive_solver.' }, 800);'));
			//--
			$input_style = 'display:none;';
			//--
			$tpl = 'lib/core/plugins/templates/captcha-form.inc.htm';
			//--
		} //end if else
		//--
		$tpl = (string) SmartMarkersTemplating::render_file_template(
			(string) $tpl,
			[
				//--
				'ALL-REQUIREMENTS' 				=> (string) (!!$y_include_js_requirements ? 'yes' : 'no'),
				'LANG' 							=> (string) SmartTextTranslations::getLanguage(),
				'CHARSET' 						=> (string) SmartUtils::get_encoding_charset(),
				'CLIENT-UID-COOKIE-LIFETIME' 	=> (int)    SmartUtils::cookie_default_expire(),
				'CLIENT-UID-COOKIE-DOMAIN' 		=> (string) SmartUtils::cookie_default_domain(),
				'CLIENT-UID-COOKIE-SAMESITE' 	=> (string) SmartUtils::cookie_default_samesite_policy(),
				//--
				'BASE-URL' 						=> (string) $the_abs_url,
				'RELEASE-HASH' 					=> (string) SmartUtils::get_app_release_hash(),
				'RELEASE-UUID' 					=> (string) $uuid,
				'RELEASE-DHKX' 					=> (string) $dhkx,
				'RELEASE-TIME' 					=> (int)    $release_time,
				'CAPTCHA-FORM' 					=> (string) $y_form_name,
				'CAPTCHA-RAND' 					=> (string) '0'.Smart::random_number(10,99), // must start with zero to avoid toFixed(0) roundUp
				'CAPTCHA-IQ-URL' 				=> (string) $captcha_qurl,
				'CAPTCHA-PASSED' 				=> (string) $translator_core_captcha->text('passed'),
				'CAPTCHA-QR-HELPER' 			=> (string) $translator_core_captcha->text('helper'),
				'CAPTCHA-TXT-IMG' 				=> (string) $translator_core_captcha->text('image'),
				'CAPTCHA-TXT-CONFIRM' 			=> (string) $translator_core_captcha->text('confirm'),
				'CAPTCHA-IMG-TITLE' 			=> (string) $translator_core_captcha->text('click'),
				'CAPTCHA-TXT-VERIFY' 			=> (string) $translator_core_captcha->text('verify'),
				'CAPTCHA-TXT-ENTER' 			=> (string) $translator_core_captcha->text('enter'),
				'CAPTCHA-TXT-EASY' 				=> (string) $translator_core_captcha->text('easy'),
				'CAPTCHA-TXT-TICK' 				=> (string) $translator_core_captcha->text('tick'),
				'CAPTCHA-TXT-ICONS' 			=> (string) $translator_core_captcha->text('icons'),
				'CAPTCHA-TXT-ICONS-DONE' 		=> (string) $translator_core_captcha->text('iconsdone'),
				'CAPTCHA-TXT-MOTION' 			=> (string) $translator_core_captcha->text('motion'),
				'CAPTCHA-TXT-MOTION-ERR' 		=> (string) $translator_core_captcha->text('motionerr'),
				'CAPTCHA-TXT-MOTION-WARN' 		=> (string) $translator_core_captcha->text('motionwarn'),
				'CAPTCHA-TXT-MOTION-DONE' 		=> (string) $translator_core_captcha->text('motiondone'),
				'CAPTCHA-TXT-QRCODE' 			=> (string) $translator_core_captcha->text('qrcode'),
				'CAPTCHA-TXT-ACCESSIBILITY' 	=> (string) $translator_core_captcha->text('accessibility'),
				'CAPTCHA-JS-EXPORTS' 			=> (string) $js_exports,
				'CAPTCHA-IM-URL' 				=> (string) $captcha_url,
				'CAPTCHA-CHKSUM' 				=> (string) self::hash_sh3a384b64s((string)'#'.$y_form_name.'#'.$uuid.'#'.$captcha_url.'#'.$captcha_qurl.'#'),
				'CAPTCHA-INPUT-STYLE' 			=> (string) $input_style,
				'CAPTCHA-JS-FIELD-BLUR' 		=> (string) SmartCipherCrypto::tf_encrypt('((fld) => { '.$js_exports.' if(typeof(jQuery) == \'undefined\') { console.warn(\'Captcha Field: jQuery N/A\'); return; } if(typeof(fld) == \'undefined\') { console.warn(\'Invalid Captcha Input Field\'); return; } '.'try { let kZ = u$.stringPureVal(fld.data(\'id\')).toUpperCase(); let fldVal = c$e('.'u$.stringPureVal(kZ),fld.val().toUpperCase()); '.$js_solver.' } catch(err) { console.error(\'Captcha Input ERROR\', err); }'.' if(fld.val()) { fld.val(\'*******\'); } })(fld);', (string)strtolower('Object'.'.'.'ID').'_'.((string)(int)$release_time).'==\''.bin2hex((string)$dhks).'\''),
				'CAPTCHA-UA-BC' 				=> (string) SmartUtils::get_os_browser_ip('bc'), // browser class
				'CAPTCHA-UA-BW' 				=> (string) SmartUtils::get_os_browser_ip('bw'), // browser type
				'CAPTCHA-UA-MOBILE' 			=> (string) SmartUtils::get_os_browser_ip('mobile'), // browser is mobile
				//--
			],
			'yes' // export to cache
		);
		//--
		if(SmartEnvironment::ifDevMode() !== true) {
			$tpl = (string) str_replace(["\r\n", "\r", "\n", "\t"], ' ', (string) $tpl);
		} //end if
		//--
		return (string) $tpl;
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
	public static function verifyCaptcha(?string $y_form_name, bool $y_clear=true, ?string $y_mode='cookie') : bool {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		if(self::validate_form_name((string)$y_form_name) !== true) {
			return false; // invalid form name
		} //end if
		//--
		$cookie_name = (string) self::cookie_name_frm((string)$y_form_name);
		//--
		$cookie_value = '';
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
		if(strpos((string)$cookie_value, '|') === false) { // new captchas must have this, with optional DHKX secret encrypted, attached
			return false;
		} //end if
		$cookie_value = (array) explode('|', (string)$cookie_value, 2);
		$dhkx_secret = (string) trim((string)($cookie_value[1] ?? null));
		if((string)$dhkx_secret != '') {
			$dhkx_secret = (string) SmartCipherCrypto::decrypt((string)$dhkx_secret);
		} //end if
		$cookie_value = (string) trim((string)($cookie_value[0] ?? null));
		//--
		$var_name = (string) self::cookie_name_jsc((string)$y_form_name);
		$var_value = (string) trim((string)SmartUtils::get_cookie((string)$var_name));
		//--
		$float   = (string) 'e';
		$factor  = (int) 2;
		$prime   = (int) 3;
		$prefix  = (int) (((int)$factor + (int)$prime) ** (int)$factor);
		$middle  = (int) ((((int)$factor + (int)$factor) ** (int)$factor) * (int)$factor);
		$suffix  = (int) ((int)$prime ** ((int)$prime - (int)$factor));
		$control = (int) ((((int)$factor ** (int)$factor) + (int)$prime) * (int)$factor);
		$shift   = (int) ((int)$suffix * (int)$factor);
		//--
		$arr_value = [];
		if((string)$var_value != '') {
			//-- use here rawurldecode instead of urldecode, because is a special context and this is how should be
			$arr_value = (array) explode((string)rawurldecode((string)hex2bin((string)$prefix.$middle.$suffix.$control.$float.$shift.$control.$float.$prefix.$middle.$suffix.$suffix)), (string)Smart::b64_dec((string)SmartCipherCrypto::tf_decrypt((string)hex2bin((string)Smart::base_to_hex_convert((string)$var_value, 62)), (string)self::cookie_val_frm((string)$y_form_name))), 3);
			$arr_value[0] = (string) trim((string)($arr_value[0] ?? null)); // timestamp
			$arr_value[1] = (string) trim((string)($arr_value[1] ?? null)); // captcha code
			$arr_value[2] = (string) trim((string)Smart::b64_dec((string)($arr_value[2] ?? null))); // dhkx data, as json
			$arr_value[2] = Smart::json_decode((string)$arr_value[2]);
			if(!is_array($arr_value[2])) {
				$arr_value[2] = [];
			} //end if
			$arr_value[2]['typ'] = ($arr_value[2]['typ'] ?? null);
			$arr_value[2]['cli'] = ($arr_value[2]['cli'] ?? null);
			$arr_value[2]['shd'] = ($arr_value[2]['shd'] ?? null);
			if(!Smart::is_nscalar($arr_value[2]['typ'])) {
				$arr_value[2]['typ'] = '';
			} //end if
			if(!Smart::is_nscalar($arr_value[2]['cli'])) {
				$arr_value[2]['cli'] = '';
			} //end if
			if(!Smart::is_nscalar($arr_value[2]['shd'])) {
				$arr_value[2]['shd'] = '';
			} //end if
			$arr_value[2]['typ'] = (string) trim((string)$arr_value[2]['typ']);
			$arr_value[2]['cli'] = (string) trim((string)$arr_value[2]['cli']);
			$arr_value[2]['shd'] = (string) trim((string)$arr_value[2]['shd']);
			$dhkx_shd = '';
			if(
				((string)$dhkx_secret != '')
				AND
				((string)$arr_value[2]['typ'] == 'smart')
				AND
				((string)$arr_value[2]['cli'] != '')
				AND
				((string)$arr_value[2]['shd'] != '')
			) {
				$dhkx_shd = (string) (new SmartDhKx())->getSrvShad((string)$dhkx_secret, (string)$arr_value[2]['cli']);
			} //end if
			//--
		} //end if
		//--
		$ok = false; // error by default
		//--
		if(
			((int)Smart::array_size($arr_value) == 3)
			AND
			((int)$arr_value[0] > 0)
			AND
			((string)$cookie_value === (string)self::cksum_hash((string)$arr_value[2]['typ']."\t".$arr_value[1]))
		) {
			//--
			if(
				((string)$arr_value[2]['typ'] === 'smart') // for smart type, DHKX is mandatory
				AND
				((string)$arr_value[2]['cli'] != '')
				AND
				((string)$arr_value[2]['shd'] != '')
				AND
				((string)$dhkx_shd != '')
				AND
				((string)$arr_value[2]['shd'] === (string)$dhkx_shd)
			) {
				//--
				$ok = true; // ok, verified, smart
				//--
			} elseif(
				((string)$arr_value[2]['typ'] === 'plugin') // for external plugin this is unpredictable as implementation ... too complicate and does not worth ... the real secure implementation is internal (smart) captcha ;-)
				AND
				((string)$arr_value[2]['cli'] == '')
				AND
				((string)$arr_value[2]['shd'] == '')
				AND
				((string)$dhkx_shd == '')
			) {
				//--
				$ok = true; // ok, verified, plugin
				//--
			} //end if
			//--
			if($y_clear === true) { // clear is optional (there are situations when after veryfying captcha, even if OK, other code must be run and if that code returns error, still captcha must be active, not cleared (so clearing it manually is a solution ...)
				//--
				self::clearCaptcha((string)$y_form_name, (string)$y_mode);
				//--
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
	 * @param $y_nodelete 		*OPTIONAL* ; DEFAULT is FALSE ; if set to TRUE will just clear the cookie values, not delete the cookies
	 * @return BOOLEAN 			TRUE on success or FALSE on failure
	 */
	public static function clearCaptcha(?string $y_form_name, ?string $y_mode='cookie', bool $y_nodelete=false) : bool {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		if(self::validate_form_name((string)$y_form_name) !== true) {
			return false; // invalid form name
		} //end if
		//--
		$var_name = (string) self::cookie_name_jsc((string)$y_form_name);
		$cookie_name = (string) self::cookie_name_frm((string)$y_form_name);
		//--
		if((string)$y_mode == 'session') {
			//--
			SmartSession::unsets((string)$var_name); // unset from session
			$ok = (bool) SmartSession::unsets((string)$cookie_name); // unset from session
			//--
		} else {
			//--
			if($y_nodelete === true) { // sometimes this method may be called before captcha to allow single captcha solve per many actions and if cookie is deleted a warning will appear in firefox console that cookie is already expired ; to avoid this warning, because later on the same page captcha is draw, just clear values of the cookies not delete
				SmartUtils::set_cookie((string)$var_name, ' ', (int)self::COOKIE_EXPIRE_TIME); // reset ; set a space, empty value will delete
				$ok = (bool) SmartUtils::set_cookie((string)$cookie_name, ' ', (int)self::COOKIE_EXPIRE_TIME); // reset ; set a space, empty value will delete
			} else {
				SmartUtils::unset_cookie((string)$var_name); // unset cookie
				$ok = (bool) SmartUtils::unset_cookie((string)$cookie_name); // unset cookie
			} //end if
			//--
		} //end if else
		//--
		return (bool) $ok; // OK
		//--
	} //END FUNCTION
	//================================================================


	//===== PRIVATES


	//================================================================
	private static function get_dhkx_server_data() : array {
		//--
		$dh  = new SmartDhKx();
		//--
		$bas = (string) $dh->getBaseGen();
		$srv = (array)  $dh->getSrvData((string)$bas);
		//--
		$dh = [
			'bas' => (string) $bas,
			'pub' => (string) ($srv['pub'] ?? null),
		];
		//--
		$dh = (string) Smart::json_encode((array)$dh);
		$dh = (string) Smart::b64s_enc((string)$dh);
		//--
		return [
			'dh' => (string) $dh,
			'ds' => (string) ($srv['sec'] ?? null),
		];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function validate_form_name(?string $y_form_name) : bool {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		$out = true;
		//--
		if((string)$y_form_name == '') {
			$out = false; // empty form name
		} //end if
		//--
		if(!preg_match('/^[A-Za-z0-9_\-]+$/', (string)$y_form_name)) { // sync with smart htmid
			$out = false; // invalid characters in form name
		} //end if
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function private_key() : string {
		//--
		if(self::$securityKey !== null) {
			return (string) self::$securityKey;
		} //end if
		//--
		$area = '[IDX]';
		if(SmartEnvironment::isAdminArea()) {
			$area = '[ADM]';
		} //end if
		//--
		self::$securityKey = (string) SmartHashCrypto::checksum((string)__CLASS__."\v".$area."\v".SMART_FRAMEWORK_SECURITY_KEY."\v".date('Y-m-d'));
		//--
		return (string) self::$securityKey;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cksum_hash(?string $y_code) : string {
		//--
		return (string) self::hash_sh3a224b62('Captcha#Cksum'.chr(0).$y_code.chr(0).self::private_key());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_area_marker() : string {
		//--
		$area = 'i';
		if(SmartEnvironment::isAdminArea()) {
			$area = 'a';
		} //end if
		//--
		return (string) $area;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_jsc(?string $y_form_name) : string {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SfCaptcha_'.self::cookie_name_area_marker().'DAT_'.self::hash_md5b62('Captcha#Jsc'.chr(0).$y_form_name.chr(0).self::private_key());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_chk(?string $y_form_name) : string {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SfCaptcha_'.self::cookie_name_area_marker().'CHK_'.self::hash_md5b62('Captcha#Chk'.chr(0).$y_form_name.chr(0).self::private_key());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_name_frm(?string $y_form_name) : string {
		//--
		$y_form_name = (string) trim((string)$y_form_name);
		//--
		return (string) 'SfCaptcha_'.self::cookie_name_area_marker().'FRM_'.self::hash_md5b62('Captcha#Frm'.chr(0).$y_form_name.chr(0).self::private_key());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function cookie_val_frm(?string $y_form_name) : string {
		//--
		return (string) self::hash_sh3a224b62('Captcha#Frm[Val]'.chr(0).$y_form_name."\v".self::private_key());
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ safe, used just for cookie names ...
	private static function hash_md5b62(?string $y_str) : string {
		//--
		return (string) Smart::base_from_hex_convert((string)SmartHashCrypto::md5((string)$y_str), 62);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ safe, used for frm val and chksum hash
	private static function hash_sh3a224b62(?string $y_str) : string {
		//--
		return (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sh3a224((string)$y_str), 62);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ safe, used for captcha checksum
	private static function hash_sh3a384b64s(?string $y_str) : string {
		//--
		return (string) Smart::b64_to_b64s((string)SmartHashCrypto::sh3a384((string)$y_str, true));
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
