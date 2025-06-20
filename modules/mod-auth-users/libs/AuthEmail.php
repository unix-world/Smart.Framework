<?php
// PHP Auth Users Email for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\AuthUsers;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: \SmartModExtLib\AuthUsers\AuthEmail
 * Auth Users Email
 *
 * @depends \SmartModExtLib\AuthUsers\AuthRegister
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250319
 * @package 	modules:AuthUsers
 *
 */
final class AuthEmail {

	// ::

	private const TPL_PATH 			= 'modules/mod-auth-users/templates/email/';
	private const EMAIL_LOG_PATH 	= 'tmp/mail-sent/auth-users/';


	//-------- [ REGISTRATION ]


	public static function renderTplRegistration(?string $token, ?string $hash, bool $urlContainsHash) : string {
		//--
		$token = (string) \trim((string)$token);
		if((string)$token == '') {
			\Smart::log_warning(__METHOD__.' # Token is Empty');
			return '';
		} //end if
		//--
		$hash  = (string) \trim((string)$hash);
		if((string)$hash == '') {
			\Smart::log_warning(__METHOD__.' # Hash is Empty');
			return '';
		} //end if
		//--
		$websiteName = (string) \trim((string)\Smart::get_from_config('app.info-url', 'string'));
		if((string)$websiteName == '') {
			\Smart::log_warning(__METHOD__.' # App Info URL is Empty');
			return '';
		} //end if
		//--
		$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		//--
		$txtActivate 	= (string) $translator->text('api-activate-acc-eml-txt-activate');
		$txtFinalize 	= (string) $translator->text('api-activate-acc-eml-txt-finalize');
		$txtMain 		= (string) $translator->text('api-activate-acc-eml-txt-main');
		$txtHint 		= (string) $translator->text('api-activate-acc-eml-txt-hint');
		$txtCode 		= (string) $translator->text('api-activate-acc-eml-txt-code');
		$txtValid 		= (string) $translator->text('api-activate-acc-eml-txt-valid');
		//--
		$urlHash = '';
		if($urlContainsHash === true) {
			$urlHash = (string) $hash;
		} //end if
		//--
		$link = (string) \SmartUtils::get_server_current_url().\SmartModExtLib\AuthUsers\AuthRegister::buildUrlActivate((string)$token, (string)$urlHash);
		//--
		$tplPath = (string) self::TPL_PATH;
		if((\defined('\\SMART_AUTHUSERS_EMAIL_TPL_PATH')) AND ((string)\trim((string)\SMART_AUTHUSERS_EMAIL_TPL_PATH) != '')) {
			$tplPath = (string) \SMART_AUTHUSERS_EMAIL_TPL_PATH;
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) $tplPath.'template-register.htm',
			[
				'WEBSITE-NAME' 	=> (string) $websiteName,
				'TITLE' 		=> (string) $txtActivate,
				'SUB-TITLE' 	=> (string) $txtFinalize,
				'MAIN' 			=> (string) $txtMain,
				'TXT-HINT' 		=> (string) $txtHint,
				'TXT-VALID' 	=> (string) $txtValid,
				'TXT-ACTIVATE' 	=> (string) $txtActivate,
				'LNK-ACTIVATE' 	=> (string) $link,
				'TXT-CODE' 		=> (string) $txtCode,
				'THE-CODE' 		=> (string) $hash,
			]
		);
		//--
	} //END FUNCTION


	public static function mailSendRegistration(?string $email, ?string $token, ?string $hash, bool $urlContainsHash) : int {
		//--
		$email = (string) \trim((string)$email);
		if(self::isValidEmail((string)$email) !== true) {
			return 101;
		} //end if
		//--
		$token = (string) \trim((string)$token);
		if((string)$token == '') {
			return 102;
		} //end if
		//--
		$hash = (string) \trim((string)$hash);
		if((string)$hash == '') {
			return 103;
		} //end if
		//--
		$message = (string) self::renderTplRegistration(
			(string) $token,
			(string) $hash,
			(bool)   $urlContainsHash
		);
		if((string)\trim((string)$message) == '') {
			return 111;
		} //end if
		//--
		$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		//--
		$subject = (string) $translator->text('api-activate-acc-eml-subj');
		//--
		return (int) \SmartMailerUtils::send_email(
			(string) self::EMAIL_LOG_PATH, // log path
			(string) $email, // to
			'', // cc
			'', // bcc
			(string) $subject.': '.$hash, // subject
			(string) $message, // message
			true // is html
		);
		//--
	} //END FUNCTION


	//-------- [ RECOVERY ]


	public static function renderTplRecovery(?string $oneTimePassCode) : string {
		//--
		$oneTimePassCode = (string) \trim((string)$oneTimePassCode);
		if((string)$oneTimePassCode == '') {
			\Smart::log_warning(__METHOD__.' # OneTimePass is Empty');
			return '';
		} //end if
		//--
		$websiteName = (string) \trim((string)\Smart::get_from_config('app.info-url', 'string'));
		if((string)$websiteName == '') {
			\Smart::log_warning(__METHOD__.' # App Info URL is Empty');
			return '';
		} //end if
		//--
		$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		//--
		$txtRecovery 		= (string) $translator->text('api-pass-recovery-eml-txt-recovery');
		$txtPassRecovery 	= (string) $translator->text('api-pass-recovery-eml-txt-pass-recovery');
		$txtMain 			= (string) $translator->text('api-pass-recovery-eml-txt-warn');
		$txtHint 			= (string) $translator->text('api-pass-recovery-eml-txt-hint');
		$txtValid 			= (string) $translator->text('api-pass-recovery-eml-txt-valid');
		$txtSignIn 			= (string) $translator->text('sign-in-acc');
		$txtCode 			= (string) $translator->text('api-pass-recovery-eml-txt-code');
		//--
		$link = (string) \SmartUtils::get_server_current_url().\SmartModExtLib\AuthUsers\Utils::AUTH_USERS_URL_SIGNIN;
		//--
		$tplPath = (string) self::TPL_PATH;
		if((\defined('\\SMART_AUTHUSERS_EMAIL_TPL_PATH')) AND ((string)\trim((string)\SMART_AUTHUSERS_EMAIL_TPL_PATH) != '')) {
			$tplPath = (string) \SMART_AUTHUSERS_EMAIL_TPL_PATH;
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) $tplPath.'template-recovery.htm',
			[
				'WEBSITE-NAME' 	=> (string) $websiteName,
				'TITLE' 		=> (string) $txtRecovery,
				'SUB-TITLE' 	=> (string) $txtPassRecovery,
				'MAIN' 			=> (string) $txtMain,
				'TXT-HINT' 		=> (string) $txtHint,
				'TXT-VALID' 	=> (string) $txtValid,
				'TXT-SIGNIN' 	=> (string) $txtSignIn,
				'LNK-SIGNIN' 	=> (string) $link,
				'TXT-CODE' 		=> (string) $txtCode,
				'THE-CODE' 		=> (string) $oneTimePassCode,
			]
		);
		//--
	} //END FUNCTION


	public static function mailSendRecovery(?string $email, ?string $oneTimePassCode) : int {
		//--
		$email = (string) \trim((string)$email);
		if(self::isValidEmail((string)$email) !== true) {
			return 101;
		} //end if
		//--
		$oneTimePassCode = (string) \trim((string)$oneTimePassCode);
		if((string)$oneTimePassCode == '') {
			return 102;
		} //end if
		//--
		$message = (string) self::renderTplRecovery(
			(string) $oneTimePassCode
		);
		if((string)\trim((string)$message) == '') {
			return 111;
		} //end if
		//--
		$translator = \SmartTextTranslations::getTranslator('mod-auth-users', 'auth-users');
		//--
		$subject = (string) $translator->text('api-pass-recovery-eml-subj');
		//--
		return (int) \SmartMailerUtils::send_email(
			(string) self::EMAIL_LOG_PATH, // log path
			(string) $email, // to
			'', // cc
			'', // bcc
			(string) $subject.': '.$oneTimePassCode, // subject
			(string) $message, // message
			true // is html
		);
		//--
	} //END FUNCTION


	//-------- [ PRIVATES ]


	private static function isValidEmail(string $email) : bool {
		//--
		if( // {{{SYNC-AUTH-USERS-EMAIL-AS-USERNAME-SAFE-VALIDATION}}}
			((string)\trim((string)$email) == '')
			OR
			((int)\strlen((string)$email) < 5)
			OR
			((int)\strlen((string)$email) > 72)
			OR
			(\strpos((string)$email, '@') == false)
			OR
			(\SmartAuth::validate_auth_ext_username((string)$email) !== true)
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
