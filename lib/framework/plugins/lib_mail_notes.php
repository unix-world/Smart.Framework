<?php
// [LIB - Smart.Framework / Plugins / Mail Notes]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Mail Notes
// DEPENDS:
//	* SmartUnicode::
//	* Smart::
//	* SmartHashCrypto::
//	* SmartCipherCrypto::
//	* SmartAuth::
//======================================================

// [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartMailerNotes - provides various functions for eMail Notes like: Apple Notes.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @depends 	classes: SmartUnicode, Smart, SmartHashCrypto, SmartCipherCrypto, SmartAuth
 * @version 	v.20250107
 * @package 	Plugins:Mailer
 *
 */
final class SmartMailerNotes {

	// ::


	//==================================================================
	public static function encrypted_eml_message_as_apple_notes_signature() {
		//--
		return 'X-SF-AppleNotes-MimeMessage: Apple/Notes';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function decrypt_apple_notes_as_eml_message($y_eml) {
		//--
		if((string)trim((string)$y_eml) == '') {
			return '';
		} //end if
		//--
		$auth_id = (string) SmartAuth::get_auth_id();
		$auth_privkeys = (string) SmartAuth::get_user_privkey();
		//--
		if((string)trim((string)$auth_id) == '') {
			return '[ERROR #1]: WARNING: Cannot Decrypt: Auth UserName is Empty';
		} //end if
		//--
		if((string)trim((string)$auth_privkeys) == '') {
			return '[ERROR #2]: Cannot Decrypt: Auth PrivateKey is Empty';
		} //end if
		//--
		if((string)trim((string)$y_eml) == '') {
			return '[ERROR #3]: Mime Message: Note is Empty';
		} //end if
		//--
		$y_eml = (string) Smart::b64_dec((string)$y_eml);
		if((string)trim((string)$y_eml) == '') {
			return '[ERROR #4]: Mime Message: Note is Empty after Base64 Decode';
		} //end if
		//--
		$y_eml = (string) SmartCipherCrypto::tf_decrypt(
			(string) $y_eml, // data
			(string) self::crypto_key((string)$auth_id, (string)$auth_privkeys), // key
			(bool)   true // fallback to BF dec, ... support backward compatible enc data
		);
		//--
		if((string)$y_eml == '') { // do not trim here !
			return '[ERROR #5]: Mime Message: Note is Empty after Decrypt';
		} //end if
		if(strpos((string)$y_eml, (string)self::encrypted_eml_message_as_apple_notes_signature()."\r\n") !== 0) {
			return '[ERROR #6]: Mime Message: Note contains an Invalid Signature after Decrypt:'."\r\n".str_repeat('-', 100)."\r\n".(string) SmartUnicode::sub_str((string)$y_eml, 0, 1024)."\r\n".str_repeat('-', 100)."\r\n\r\n"; // {{{SYNC-INVALID-APPLENOTE-MAXLEN-BODY-ERR}}} : here is 1024
		} //end if
		//--
		return (string) $y_eml;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function encrypt_eml_message_as_apple_notes($y_uuid, $y_date, $y_from, $y_subj, $y_eml) {
		//--
		$auth_id = (string) SmartAuth::get_auth_id();
		$auth_privkeys = (string) SmartAuth::get_user_privkey();
		//--
		if(((string)trim((string)$auth_id) == '') OR ((string)trim((string)$auth_privkeys) == '')) {
			Smart::log_warning(__METHOD__.' # ERROR: Failed to Encrypt Note # Auth UserName or Auth PrivateKey is Empty ...');
			return '';
		} //end if
		//--
		$y_eml = (string) trim((string)self::encrypted_eml_message_as_apple_notes_signature())."\r\n".trim((string)$y_eml); // add header signature
		//--
		$cksum = (string) sha1((string)$y_eml);
		//--
		$y_eml = (string) Smart::b64_enc(
			(string) SmartCipherCrypto::tf_encrypt(
				(string) $y_eml, // data
				(string) self::crypto_key((string)$auth_id, (string)$auth_privkeys), // key
				(bool)   true // BF pre-enc !
			)
		);
		//--
		$msg = '';
		//--
		$boundary = (string) '_=Smart.Framework=_Enc-MimePart_'.Smart::uuid_36().'=_';
		//--
		$msg .= 'X-SF-AppleNotes-Account: '.Smart::normalize_spaces((string)trim((string)$auth_id))."\r\n";
		$msg .= 'X-SF-AppleNotes-Type: apple/notes'."\r\n";
		$msg .= 'X-SF-AppleNotes-Date: '.Smart::normalize_spaces((string)trim((string)date('Y-m-d H:i:s O')))."\r\n";
		$msg .= 'Date: '.Smart::normalize_spaces((string)trim((string)$y_date))."\r\n";
		$msg .= 'From: <'.Smart::normalize_spaces((string)trim((string)$y_from)).'>'."\r\n";
		$msg .= 'Subject: '.Smart::normalize_spaces((string)trim((string)$y_subj))."\r\n";
		$msg .= 'X-Universally-Unique-Identifier: '.Smart::normalize_spaces((string)trim((string)$y_uuid))."\r\n";
		$msg .= 'Mime-Version: 1.0 (Apple.Notes Smart.Framework '.Smart::normalize_spaces((string)trim((string)SMART_FRAMEWORK_RELEASE_TAGVERSION)).' '.Smart::normalize_spaces((string)trim((string)SMART_FRAMEWORK_RELEASE_VERSION)).')'."\r\n";
		$msg .= 'Content-Type: multipart/mixed; boundary="'.$boundary.'"'."\r\n";
		$msg .= "\r\n";
		$msg .= 'This is a Smart.Framework encrypted multi-part apple/note in MIME format.'."\r\n";
		$msg .= "\r\n";
		$msg .= '--'.$boundary."\r\n";
		$msg .= 'Content-Type: text/plain'."\r\n";
		$msg .= 'Content-Transfer-Encoding: 7bit'."\r\n";
		$msg .= 'Content-Disposition: inline'."\r\n";
		$msg .= "\r\n";
		$msg .= 'This Apple/Note is encrypted using Blowfish CBC cipher based on Smart.Framework Authentication Data.'."\r\n";
		$msg .= 'If any of the Authentication Data (username or privacy-key) are changed it will not be decrypted on-the-fly and'."\r\n";
		$msg .= 'in this case it can be decrypted with the old username / privacy-key and re-encrypted with the new username / privacy-key ...'."\r\n";
		$msg .= '--'.$boundary."\r\n";
		$msg .= 'Content-Type: message/smart-framework-msg-notes-bfenc-by-acc'."\r\n";
		$msg .= 'Content-Transfer-Encoding: BASE64'."\r\n";
		$msg .= 'Content-Disposition: inline; filename="apple-note-encrypted-by-smart-framework.eml.bfenc.txt"'."\r\n";
		$msg .= 'Content-Length: '.(int)strlen((string)$y_eml)."\r\n";
		$msg .= 'Content-Decoded-Checksum-SHA1: '.Smart::normalize_spaces((string)trim((string)$cksum))."\r\n";
		$msg .= "\r\n";
		$msg .= (string) trim((string)chunk_split((string)$y_eml, 76, "\r\n"));
		$msg .= "\r\n";
		$msg .= '--'.$boundary.'--'."\r\n";
		//--
		return (string) $msg;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function mime_fix_apple_notes_objects_in_html($y_mime_part) {
		//-- Apple Notes Objects may be converted to CID images (at least most of them)
		if((stripos((string)$y_mime_part, '<object ') === false) OR (stripos((string)$y_mime_part, 'application/x-apple-msg-attachment') === false)) {
			return (string) $y_mime_part;
		} //end if
		//--
		$htmlparser = new SmartHtmlParser((string)$y_mime_part); // do not get clean HTML here else the objects will vanish ... will be cleaned later by mime_fix_clean_html()
		//--
		$arr_obj_tags = (array) $htmlparser->get_tags('object');
		$have_apple_notes_obj_cid = false;
		for($i=0; $i<Smart::array_size($arr_obj_tags); $i++) {
			if((string)trim((string)strtolower((string)$arr_obj_tags[$i]['type'])) == 'application/x-apple-msg-attachment') {
				if(stripos((string)trim((string)$arr_obj_tags[$i]['data']), 'cid:') === 0) {
					$have_apple_notes_obj_cid = true;
					break;
				} //end if
			} //end if
		} //end for
		if($have_apple_notes_obj_cid === true) {
			$arr_all_tags = (array) $htmlparser->get_all_tags();
			$found_apple_objects = 0;
			for($i=0; $i<Smart::array_size($arr_all_tags); $i++) {
				if($found_apple_objects >= Smart::array_size($arr_obj_tags)) {
					break;
				} //end if
				$tmp_line = (string) trim((string)strtolower((string)$arr_all_tags[$i]));
				if(stripos((string)$tmp_line, '<object ') !== false) {
					if(stripos((string)$tmp_line, '"application/x-apple-msg-attachment"') !== false) {
						if(stripos((string)$tmp_line, '"cid:') !== false) {
							$arr_all_tags[$i] = '<img src="'.Smart::escape_html($arr_obj_tags[$found_apple_objects]['data']).'" data-obj-type="'.Smart::escape_html($arr_obj_tags[$found_apple_objects]['type']).'">';
							$found_apple_objects++;
						} //end if
					} //end if
				} //end if
			} //end for
			if($found_apple_objects > 0) {
				$y_mime_part = (string) implode('', (array)$arr_all_tags); // re-compose :: {{{SYNC-HTML-PARSER-RECOMPOSE}}}
			} //end if
			$arr_all_tags = null;
			$found_apple_objects = null;
		} //end if
		$arr_obj_tags = null;
		$have_apple_notes_obj_cid = null;
		//--
		return (string) $y_mime_part;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function crypto_key(?string $y_auth_id, ?string $y_auth_privkeys) : string {
		//--
		$y_auth_id = (string) trim((string)$y_auth_id);
		$y_auth_privkeys = (string) trim((string)$y_auth_privkeys);
		//--
		if((string)$y_auth_id == '') {
			Smart::raise_error('ERROR: MAIL Notes // BfKey :: UserName is Empty');
			return '';
		} //end if
		if((string)$y_auth_privkeys == '') {
			Smart::raise_error('ERROR: MAIL Notes // BfKey :: Privacy Key is Empty');
			return '';
		} //end if
		//--
		return (string) SmartHashCrypto::sha512((string)$y_auth_id.':'.$y_auth_privkeys);
		//--
	} //END FUNCTION
	//==================================================================



} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
