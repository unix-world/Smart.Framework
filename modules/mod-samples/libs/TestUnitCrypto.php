<?php
// [LIB - Smart.Framework / Samples / Test Crypto]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitCrypto
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\Samples;

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
 * Test Crypto
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250118
 *
 */
final class TestUnitCrypto {

	// ::

	//============================================================
	public static function testPhpAndJs() {

		//--
		$time = \microtime(true);
		//--

		//--
		$err_misc = [];
		//--

		//--
		$test_int = (int) \time();
		$test_hex_int = (string) \Smart::int10_to_hex((int)$test_int);
		$test_rev_int = (int) \Smart::hex_to_int10((string)$test_hex_int);
		if((int)$test_int != (int)$test_rev_int) {
			$err_misc[] = 'TestUnit FAILED :: Int64 to Hex and reverse test failed: '.$test_int.' -> '.$test_hex_int.' -> '.$test_rev_int;
		} //end if
		$test_b62_from_int10_old = (string) self::int10_to_base62_str((int)$test_int);
		$test_b62_from_int10_new = (string) \Smart::int10_to_base62_str((int)$test_int);
		if(((string)$test_b62_from_int10_old != (string)$test_b62_from_int10_new) OR ((string)trim((string)$test_b62_from_int10_new) == '')) {
			$err_misc[] = 'TestUnit FAILED :: B62 From Int (base10) test Errors'."\n".'OLD='.$test_b62_from_int10_old."\n".'NEW='.$test_b62_from_int10_new."\n".'INT='.(int)$test_int;
		} //end if
		//--
		$test_base_str = '0'.'Unicode String:		şŞţŢăĂîÎâÂșȘțȚ (05-09#';
		$test_base_hex_str = (string) \bin2hex((string)$test_base_str);
		$arr_test_bases = [ // from golang tests
			32 => 'O5ARJ9CDNM8P90ADQ74QBECST0I2E5JV2PTHD3OMHC90U4GB1QTGSEOEHC70M8J749HI4RP2D20A1G6KMJ0E93',
			36 => '1elnj06p95mfkucujc7u987d40pl1b21dks5crn8xf2sydir002rqsfciwwbq5zwkuq25r6fo479p66sl9ur',
			58 => '4u2LgSK1DVqRhVd4zo68r5qDfpqhwgG8YtaQsqt1Ex7sJtx8QQL6zcZbpB5Um8cvEfmSSfz7va',
			64 => 'MFVuaWNvZGUgU3RyaW5nOgkJxZ_FnsWjxaLEg8SCw67DjsOiw4LImciYyJvImiAoMDUtMDkj',
			62 => '1R7z2fSx7kHHxhDzO6k7eJEJCulg2bqxDCaMQMRemuXqzPgyU26S9Mz7vWTVpLum4nlDf8FwL',
			85 => '1atZCS1nD!]j5{Von?z#gz%T15LT)j!oGkdLW?[x$goD<DMPclTVT0#EX&c(</zbedMq',
			92 => 'LqRRt)tem^dKaDDPJpAeT`lvBP3&?;U,IByi/ekI9Eb#ifL<C2;8;E/n^/Z_DX3h7{',
		];
		$arr_test_dec_bases = [];
		foreach($arr_test_bases as $key => $val) {
			if((int)$key == 64) {
				$tmp_bconv = (string) \Smart::b64s_enc((string)\hex2bin((string)$test_base_hex_str));
			} else {
				$tmp_bconv = (string) \Smart::base_from_hex_convert((string)$test_base_hex_str, (int)$key);
			} //end if else
			if((string)$tmp_bconv != (string)$val) {
				$err_misc[] = 'TestUnit FAILED :: BaseFromHex Convert to Base `'.(int)$key.'` Errors'."\n".'EXPECTED='.$val."\n".'RESULT='.$tmp_bconv."\n".'HEXSTR='.(string)$test_base_hex_str;
			} //end if
			if((int)$key == 64) {
				$tmp_back_str = (string) \Smart::b64s_dec((string)$tmp_bconv, true); // STRICT
				$tmp_back_hex = (string) \bin2hex((string)$tmp_back_str);
			} else {
				$tmp_back_hex = (string) \Smart::base_to_hex_convert((string)$tmp_bconv, (int)$key);
				$tmp_back_str = (string) \hex2bin((string)$tmp_back_hex);
			} //end if else
			$arr_test_dec_bases[$key] = (string) $tmp_back_str;
			if((string)$tmp_back_str !== (string)$test_base_str) { // hex may difer due to tha fact that backward will not do dechex() but only binhex() over result ...
				$err_misc[] = 'TestUnit FAILED :: BaseToHex Convert from Base `'.(int)$key.'` Errors'."\n".'EXPECTED='.$test_base_str."\n".'RESULT='.$tmp_back_str."\n".'HEXSTR='.(string)$tmp_back_hex;
			} //end if
		} //end for
		//--

		//--
		$unicode_text = "Unicode String [ ".\time()." ]: @ Smart スマート // Cloud Application Platform クラウドアプリケーションプラットフォーム '".\implode('', (array)\array_keys((array)\SmartUnicode::ACCENTED_CHARS))." \" <p></p>
		? & * ^ $ @ ! ` ~ % () [] {} | \\ / + - _ : ; , . #'".\microtime().'#';
		//--

		//--
		$b64enc = (string) \base64_encode((string)$unicode_text);
		$b64dec = (string) \base64_decode((string)$b64enc);
		//--

		//--
		$bin2hex = (string) \bin2hex((string)$unicode_text);
		$hex2bin = (string) \hex2bin((string)\trim((string)$bin2hex));
		//--
		if(
			((string)trim((string)$hex2bin) == '')
			OR
			((string)$hex2bin !== (string)$unicode_text)
			OR
			((string)\Smart::safe_hex_2_bin((string)$bin2hex, true, false) !== (string)$unicode_text) // insensitive case
			OR
			((string)\Smart::safe_hex_2_bin((string)$bin2hex, false, false) !== (string)$unicode_text) // sensitive case
			OR
			((string)\Smart::safe_hex_2_bin((string)strtoupper((string)$bin2hex), false, false) !== '') // sensitive case, inversed
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Bin/Hex, Hex/Bin conversions test';
		} //end if
		//--

		//--
		$test_key = (string) 'TestUnit // This is a test key for Crypto Cipher ...'.'!$'.SMART_FRAMEWORK_SECURITY_KEY.'#'.time().'#'.microtime(true).'::'.$unicode_text;
		//--

		//--
		// this is the PHP side test only ...
		// the JS side test is: modules/mod-samples/demo/dhkx.html
		//--
		$dh = (array) \SmartCipherCrypto::dhkx_enc();
		$dhfails = 0;
		$dhflarr = [];
		if((string)$dh['err'] != '') {
			$dhflarr[] = (string) 'DH-ERR: '.$dh['err'];
			$dhfails++;
		} //end if
		$eidz = null;
		if((int)$dhfails <= 0) {
			$eidz = (array)\SmartCipherCrypto::dhkx_dec((string)$dh['eidz']);
			if((string)$eidz['err'] != '') {
				$dhflarr[] = (string) 'eIDZ-ERR: '.$eidz['err'];
				$dhfails++;
			} else {
				if((string)$eidz['shad'] != (string)$dh['shad']) {
					$dhflarr[] = (string) 'eIDZ-ERR: shad != srv.shad';
					$dhfails++;
				} //end if
			} //end if else
		} //end if
		//--
		if((int)$dhfails > 0) {
			$err_misc[] = 'TestUnit FAILED :: Diffie-Hellman Key Exchange Failures'."\n".'EXPECTED=0'."\n".'RESULT='.(int)$dhfails."\n".\print_r($dhflarr,1);
		} elseif($eidz === null) {
			$err_misc[] = 'TestUnit FAILED :: Diffie-Hellman Key Exchange: IDZ is NULL';
		} //end if else
		//--
		$dhfails = null;
		$dhflarr = null;
		$dh = null;
		//--

		//--
		$poly1305Text = 'Lorem Ipsum dolor sit Amet'; //.str_repeat('a', 4096);
		$poly1305Key = 'abcdefgh12345678abcdefgh12345678';
		$poly1305Iv = 'abcdef123456';
		$poly1305RefEmpty = 'YWJjZGVmZ2gxMjM0NTY3OA==';
		$poly1305RefLorem = 'bQiUTxbEHWkl2+m1tRKxIQ==';
		$poly1305Sum1 = (string) \SmartHashCrypto::poly1305((string)$poly1305Key, '', true); // b64
		$poly1305Sum2 = (string) \SmartHashCrypto::poly1305((string)$poly1305Key, '', false); // hex
		$poly1305Sum3 = (string) \SmartHashCrypto::poly1305((string)$poly1305Key, (string)$poly1305Text, true); // b64
		$poly1305Sum4 = (string) \SmartHashCrypto::poly1305((string)$poly1305Key, (string)$poly1305Text, false); // hex
		$poly1305Sum5 = (string) \SmartHashCrypto::poly1305((string)$poly1305Key, (string)$unicode_text); // hex
		if(
			((string)$poly1305Sum1 !== (string)$poly1305RefEmpty)
			OR
			((string)$poly1305Sum2 !== (string)bin2hex((string)base64_decode((string)$poly1305RefEmpty)))
			OR
			((string)$poly1305Sum3 !== (string)$poly1305RefLorem)
			OR
			((string)$poly1305Sum4 !== (string)bin2hex((string)base64_decode((string)$poly1305RefLorem)))
			OR
			((int)strlen((string)$poly1305Sum5) != 32)
		) {
			$err_misc[] = 'TestUnit FAILED # Poly1305 Hash/Sum test';
		} //end if
		//--

		//-- hash checksums tests, try to see if there are variations
		$testChkSum1 = (string) \SmartHashCrypto::checksum((string)$unicode_text);
		$testChkSum2 = (string) \SmartHashCrypto::checksum((string)$unicode_text, (string)$test_key);
		//--
		if(
			((string)\trim((string)$testChkSum1) == '')
			OR
			((string)\trim((string)$testChkSum2) == '')
			OR
			((string)\trim((string)$testChkSum1) == (string)\trim((string)$testChkSum2)) // should be different
			OR
			((string)trim((string)$testChkSum1) != (string)\SmartHashCrypto::checksum((string)$unicode_text))
			OR
			((string)trim((string)$testChkSum2) != (string)\SmartHashCrypto::checksum((string)$unicode_text, (string)$test_key))
		) {
			$err_misc[] = 'TestUnit FAILED # Hash Checksums test';
		} //end if
		//--

		//-- hash passwords tests, try to see if there are variations, inconsistencies, see if validations work
		$plainPass = 'Smart スマート // Cloud Application Platform :: The max '.rand(100,999); // 55 chars, unicode
		$testPassw1 = (string) \SmartHashCrypto::password((string)$plainPass, (string)$unicode_text);
		$testPassw2 = (string) \SmartHashCrypto::password((string)$plainPass, (string)$test_key);
		//--
		if(
			((string)\trim((string)$testPassw1) == '')
			OR
			((string)\trim((string)$testPassw2) == '')
			OR
			((string)\trim((string)$testPassw1) == (string)\trim((string)$testPassw2)) // hashes should be different
			OR
			((string)trim((string)$testPassw1) == (string)\SmartHashCrypto::password((string)$plainPass, (string)$test_key)) // inversed salt, should be different
			OR
			((string)trim((string)$testPassw2) == (string)\SmartHashCrypto::password((string)$plainPass, (string)$unicode_text)) // inversed salt, should be different
			OR
			((string)trim((string)$testPassw1) != (string)\SmartHashCrypto::password((string)$plainPass, (string)$unicode_text)) // should be no variations
			OR
			((string)trim((string)$testPassw2) != (string)\SmartHashCrypto::password((string)$plainPass, (string)$test_key)) // should be no variations
			OR
			(\SmartHashCrypto::validatepasshashformat((string)$testPassw1) !== true)
			OR
			(\SmartHashCrypto::validatepasshashformat((string)$testPassw2) !== true)
			OR
			(\SmartHashCrypto::validatepasshashformat('') === true)
			OR
			(\SmartHashCrypto::validatepasshashformat(' ') === true)
			OR
			(\SmartHashCrypto::validatepasshashformat((string)$plainPass) === true)
			OR
			(\SmartHashCrypto::validatepasshashformat((string)$unicode_text) === true)
			OR
			(\SmartHashCrypto::validatepasshashformat((string)$test_key) === true)
			OR
			(\SmartHashCrypto::validatepasshashformat(\SmartHashCrypto::sh3a512((string)$plainPass)) === true)
			OR
			(\SmartHashCrypto::validatepasshashformat(\SmartHashCrypto::sh3a384((string)$plainPass, true)) === true)
			OR
			(\SmartHashCrypto::checkpassword((string)$plainPass, (string)$testPassw1, (string)$unicode_text) !== true)
			OR
			(\SmartHashCrypto::checkpassword((string)$plainPass, (string)$testPassw1, (string)$test_key) === true) // inversed
			OR
			(\SmartHashCrypto::checkpassword((string)$plainPass, (string)$testPassw2, (string)$test_key) !== true)
			OR
			(\SmartHashCrypto::checkpassword((string)$plainPass, (string)$testPassw2, (string)$unicode_text) === true) // inversed
		) {
			$err_misc[] = 'TestUnit FAILED # Hash Passwords test';
		} //end if
		//--

		//-- hash passwords tests, try to see if there are variations, inconsistencies, see if validations work
		$plainPass = \SmartUnicode::sub_str('Smart スマート // Cloud Application Platform :: The max', 0, 48).' '.rand(1000,9999); // 55 chars, unicode
		$testAPassw1 = (string) \SmartAuth::password_hash_create((string)$plainPass);
		$testAPassw2 = (string) \SmartAuth::password_hash_create((string)strrev((string)$plainPass));
		//--
		if(
			((string)\trim((string)$testAPassw1) == '')
			OR
			((string)\trim((string)$testAPassw2) == '')
			OR
			((string)\trim((string)$testAPassw1) == (string)\trim((string)$testAPassw2)) // should be different
			OR
			((string)trim((string)$testAPassw1) == (string)\SmartAuth::password_hash_create((string)$plainPass)) // should be different, random salt
			OR
			((string)trim((string)$testAPassw2) == (string)\SmartAuth::password_hash_create((string)strrev((string)$plainPass))) // should be different, random salt
			OR
			(\SmartAuth::password_hash_validate_format((string)$testAPassw1) !== true)
			OR
			(\SmartAuth::password_hash_validate_format((string)$testAPassw2) !== true)
			OR
			(\SmartAuth::password_hash_validate_format('') === true)
			OR
			(\SmartAuth::password_hash_validate_format(' ') === true)
			OR
			(\SmartAuth::password_hash_validate_format((string)$plainPass) === true)
			OR
			(\SmartAuth::password_hash_validate_format((string)$unicode_text) === true)
			OR
			(\SmartAuth::password_hash_validate_format((string)$test_key) === true)
			OR
			(\SmartAuth::password_hash_validate_format((string)\SmartHashCrypto::sh3a512((string)$plainPass)) === true)
			OR
			(\SmartAuth::password_hash_validate_format((string)\SmartHashCrypto::sh3a384((string)$plainPass, true)) === true)
			OR
			(\SmartAuth::password_hash_check((string)$plainPass, (string)$testAPassw1) !== true)
			OR
			(\SmartAuth::password_hash_check((string)$plainPass, (string)$testAPassw1.$test_key) === true) // inversed
			OR
			(\SmartAuth::password_hash_check((string)strrev((string)$plainPass), (string)$testAPassw2) !== true)
			OR
			(\SmartAuth::password_hash_check((string)strrev((string)$plainPass), (string)$testAPassw2.$unicode_text) === true) // inversed
		) {
			$err_misc[] = 'TestUnit FAILED # Auth Hash Passwords test';
		} //end if
		//--

		//-- test Hash Crypto :: encrypt/decrypt
		$hkey = (string) $test_key;
		$he_enc = \SmartCipherCrypto::encrypt((string)$unicode_text, (string)$hkey);
		$he_dec = \SmartCipherCrypto::decrypt((string)$he_enc, (string)$hkey);
		if(
			((string)trim((string)$he_dec) == '')
			OR
			((string)$he_dec != (string)$unicode_text)
			OR
			((string)\sha1((string)$he_dec) != \SmartHashCrypto::sha1((string)$unicode_text))
			OR
			((string)\md5((string)$he_dec) != \SmartHashCrypto::md5((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # Crypto, Cipher ['.\SmartCipherCrypto::algo().'] test';
		} //end if
		//--

		//-- test Threefish.CBC v3 :: encrypt/decrypt
		$t3fV3Key = 'some.Threefish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testT3fV3Data = '3f1kD.v1!qqXdkeUlziftjT6kzNvXc5j3B3YSj0ATsKYIW1;tB8E7itpCwzrjfW5aR5wFzdAl4ilRYzAzpKZrJlXeBTTLjFJ3kP_ewmlF9JZmU7E75dgRyC3v8t8oifmYJMV44lWr7yOlyYE0RMyNf2ZqTWc-SY6i3wudXrKDWc3msTYeAuGtrkA_12KejADM-ib1ZUn5G9zdvM5Pp789u-eAXtEENU5A9F42Mwkm6dBt2af_mCiYJcHZLwrpydUONbAqxME8KeCpDUascFqyD4pwKDkkjWSWTgwrx3ay89Azp_RZ4U9CY3Gw44-3DQZ65zUoVwWcE1AiM5j-JSQwZTiz3r2r_L_U-94S8KIajO0rFgipMnK_mhnlgZ6GucKgVDfam';
		$testT3fV3Plain = 'Lorem Ipsum dolor sit Amet';
		$t3f_v3_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$testT3fV3Data, (string)$t3fV3Key);
		$t3f_v3_enc = (string) \SmartCipherCrypto::t3f_encrypt((string)$testT3fV3Plain, (string)$t3fV3Key, false, false); // no randomizer
		if(
			((string)trim((string)$t3f_v3_dec) == '')
			OR
			((string)trim((string)$t3f_v3_enc) == '')
			OR
			((string)$t3f_v3_dec != (string)$testT3fV3Plain)
			OR
			((string)$t3f_v3_enc != (string)$testT3fV3Data)
			OR
			((string)\SmartHashCrypto::sh3a384((string)$t3f_v3_dec) != (string)\SmartHashCrypto::sh3a384((string)$testT3fV3Plain))
			OR
			((string)\SmartHashCrypto::sha384((string)$t3f_v3_dec) != (string)\SmartHashCrypto::sha384((string)$testT3fV3Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, ThreeFish V3 Decrypt test';
		} //end if
		//--
		$t3f_key = (string) $test_key;
		$t3f_enc = (string) \SmartCipherCrypto::t3f_encrypt((string)$unicode_text, (string)$t3f_key);
		$t3f_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$t3f_enc, (string)$t3f_key);
		$t3f_nornd_enc = (string) \SmartCipherCrypto::t3f_encrypt((string)$unicode_text, (string)$t3f_key, false, false); // no randomizer
		$t3f_nornd_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$t3f_nornd_enc, (string)$t3f_key);
		if(
			((string)$t3f_nornd_enc == (string)$t3f_enc) // must be different, one is using randomization the other does not
			OR
			((string)trim((string)$t3f_dec) == '')
			OR
			((string)trim((string)$t3f_nornd_dec) == '')
			OR
			((string)$t3f_dec != (string)$unicode_text)
			OR
			((string)$t3f_nornd_dec != (string)$unicode_text)
			OR
			((string)$t3f_dec != (string)$t3f_nornd_dec)
			OR
			((string)\SmartHashCrypto::sh3a512((string)$t3f_dec) != (string)\SmartHashCrypto::sh3a512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha512((string)$t3f_nornd_dec) != (string)\SmartHashCrypto::sha512((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, ThreeFish V3 Encrypt/Decrypt test';
		} //end if
		//--
		$t3ffb_key = (string) $test_key;
		$t3ffb_enc = (string) \SmartCipherCrypto::t3f_encrypt((string)$unicode_text, (string)$t3ffb_key, true);
		$t3ffb_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$t3ffb_enc, (string)$t3ffb_key);
		$t3ffb_nornd_enc = (string) \SmartCipherCrypto::t3f_encrypt((string)$unicode_text, (string)$t3ffb_key, true);
		$t3ffb_nornd_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$t3ffb_nornd_enc, (string)$t3ffb_key);
		if(
			((string)$t3ffb_nornd_enc == (string)$t3ffb_enc) // must be different, one is using randomization the other does not
			OR
			((string)trim((string)$t3ffb_dec) == '')
			OR
			((string)trim((string)$t3ffb_nornd_dec) == '')
			OR
			((string)$t3ffb_dec != (string)$unicode_text)
			OR
			((string)$t3ffb_nornd_dec != (string)$unicode_text)
			OR
			((string)$t3ffb_dec != (string)$t3ffb_nornd_dec)
			OR
			((string)\SmartHashCrypto::sh3a512((string)$t3ffb_dec) != (string)\SmartHashCrypto::sh3a512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha512((string)$t3ffb_nornd_dec) != (string)\SmartHashCrypto::sha512((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, ThreeFish+Twofish+BlowFish V3 Encrypt/Decrypt test';
		} //end if
		//--

		//-- test Twofish.CBC v3 :: encrypt/decrypt
		$tfV3Key = 'some.Twofish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testTfV3Data = '2f256.v1!jaHNknZA0ckccWW9DEnspvZz9TEYMyeUtcyXE3;qWdxLQd-9a0eliR5UJ_s2Z_1R5yN81NtOA9v72Tg6Ev_rUzuP33l3-jlm7FHQT_2dgCtcGgtTeHeRS6hYWDTvAzrucsr9RXugM9vQRVrRhNNpJJqfm3wrRJijgme6JXuedlUt0z62y0vWOpQYRePrybm2HK_JNpM8Eu8ijK1UcqKqkSVNzZnLXE-hLCTmoSU';
		$testTfV3Plain = 'Lorem Ipsum dolor sit Amet';
		$tf_v3_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$testTfV3Data, (string)$tfV3Key);
		$tf_v3_enc = (string) \SmartCipherCrypto::tf_encrypt((string)$testTfV3Plain, (string)$tfV3Key, false, false); // no randomizer
		if(
			((string)trim((string)$tf_v3_dec) == '')
			OR
			((string)trim((string)$tf_v3_enc) == '')
			OR
			((string)$tf_v3_dec != (string)$testTfV3Plain)
			OR
			((string)$tf_v3_enc != (string)$testTfV3Data)
			OR
			((string)\SmartHashCrypto::sh3a384((string)$tf_v3_dec) != (string)\SmartHashCrypto::sh3a384((string)$testTfV3Plain))
			OR
			((string)\SmartHashCrypto::sha384((string)$tf_v3_dec) != (string)\SmartHashCrypto::sha384((string)$testTfV3Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Twofish V3 Decrypt test';
		} //end if
		//--
		$tf_key = (string) $test_key;
		$tf_enc = (string) \SmartCipherCrypto::tf_encrypt((string)$unicode_text, (string)$tf_key);
		$tf_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$tf_enc, (string)$tf_key);
		$tf_nornd_enc = (string) \SmartCipherCrypto::tf_encrypt((string)$unicode_text, (string)$tf_key, false, false); // no randomizer
		$tf_nornd_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$tf_nornd_enc, (string)$tf_key);
		if(
			((string)$tf_nornd_enc == (string)$tf_enc) // must be different, one is using randomization the other does not
			OR
			((string)trim((string)$tf_dec) == '')
			OR
			((string)trim((string)$tf_nornd_dec) == '')
			OR
			((string)$tf_dec != (string)$unicode_text)
			OR
			((string)$tf_nornd_dec != (string)$unicode_text)
			OR
			((string)$tf_dec != (string)$tf_nornd_dec)
			OR
			((string)\SmartHashCrypto::sh3a512((string)$tf_dec) != (string)\SmartHashCrypto::sh3a512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha512((string)$tf_nornd_dec) != (string)\SmartHashCrypto::sha512((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Twofish V3 Encrypt/Decrypt test';
		} //end if
		//--
		$tfb_key = (string) $test_key;
		$tfb_enc = (string) \SmartCipherCrypto::tf_encrypt((string)$unicode_text, (string)$tfb_key, true);
		$tfb_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$tfb_enc, (string)$tfb_key);
		$tfb_nornd_enc = (string) \SmartCipherCrypto::tf_encrypt((string)$unicode_text, (string)$tfb_key, true, false); // no randomizer
		$tfb_nornd_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$tfb_nornd_enc, (string)$tfb_key);
		if(
			((string)$tfb_nornd_enc == (string)$tfb_enc) // must be different, one is using randomization the other does not
			OR
			((string)trim((string)$tfb_dec) == '')
			OR
			((string)trim((string)$tfb_nornd_dec) == '')
			OR
			((string)$tfb_dec != (string)$unicode_text)
			OR
			((string)$tfb_nornd_dec != (string)$unicode_text)
			OR
			((string)$tfb_dec != (string)$tfb_nornd_dec)
			OR
			((string)\SmartHashCrypto::sh3a512((string)$tfb_dec) != (string)\SmartHashCrypto::sh3a512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha512((string)$tfb_nornd_dec) != (string)\SmartHashCrypto::sha512((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Twofish+Blowfish V3 Encrypt/Decrypt test';
		} //end if
		//--

		//-- test Blowfish.CBC v3 :: encrypt/decrypt
		$bfV3Key = 'some.BlowFish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testBfV3Data = 'bf448.v3!qHRuZCEMDQgo0moJck9OLEZaeVToFEmmLSIj23;jGUxs2I2RHwo5wMvbm12ph9wK4FVYkhzvld2GRLXFMjF5HG8vpGbMcibYPdGfMq0jMeLa4dKyuWbEGRwa54GrGr6QL_613rlJSZHUExySJMkWjI7OnWUKEDOG2w9fPKwUcrl8S-zgCucfsyoVzBUDUdlFv3Igzuh2qhLmbpxMu-WB96aa_BHZm';
		$testBfV3Plain = 'Lorem Ipsum dolor sit Amet';
		$bf_v3_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$testBfV3Data, (string)$bfV3Key);
		$bf_v3_enc = (string) \SmartCipherCrypto::bf_encrypt((string)$testBfV3Plain, (string)$bfV3Key, false); // no randomizer
		if(
			((string)trim((string)$bf_v3_dec) == '')
			OR
			((string)trim((string)$bf_v3_enc) == '')
			OR
			((string)$bf_v3_dec != (string)$testBfV3Plain)
			OR
			((string)$bf_v3_enc != (string)$testBfV3Data)
			OR
			((string)\SmartHashCrypto::sh3a384((string)$bf_v3_dec) != (string)\SmartHashCrypto::sh3a384((string)$testBfV3Plain))
			OR
			((string)\SmartHashCrypto::sha384((string)$bf_v3_dec) != (string)\SmartHashCrypto::sha384((string)$testBfV3Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Blowfish V3 Decrypt test';
		} //end if
		//--
		$bf_key = (string) $test_key;
		$bf_enc = (string) \SmartCipherCrypto::bf_encrypt((string)$unicode_text, (string)$bf_key);
		$bf_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$bf_enc, (string)$bf_key);
		$bf_nornd_enc = (string) \SmartCipherCrypto::bf_encrypt((string)$unicode_text, (string)$bf_key, false); // no randomizer
		$bf_nornd_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$bf_nornd_enc, (string)$bf_key);
		$btf_dec = (string) \SmartCipherCrypto::tf_decrypt((string)$bf_enc, (string)$bf_key, true);
		$bttf_dec = (string) \SmartCipherCrypto::t3f_decrypt((string)$bf_enc, (string)$bf_key, true);
		if(
			((string)$bf_nornd_enc == (string)$bf_enc) // must be different, one is using randomization the other does not
			OR
			((string)trim((string)$bf_dec) == '')
			OR
			((string)trim((string)$bf_nornd_dec) == '')
			OR
			((string)trim((string)$btf_dec) == '')
			OR
			((string)trim((string)$bttf_dec) == '')
			OR
			((string)$bf_dec != (string)$unicode_text)
			OR
			((string)$bf_nornd_dec != (string)$unicode_text)
			OR
			((string)$btf_dec != (string)$unicode_text)
			OR
			((string)$bttf_dec != (string)$unicode_text)
			OR
			((string)\SmartHashCrypto::sh3a512((string)$bf_dec) != (string)\SmartHashCrypto::sh3a512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha512((string)$bf_nornd_dec) != (string)\SmartHashCrypto::sha512((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sh3a384((string)$btf_dec) != (string)\SmartHashCrypto::sh3a384((string)$unicode_text))
			OR
			((string)\SmartHashCrypto::sha384((string)$bttf_dec) != (string)\SmartHashCrypto::sha384((string)$unicode_text))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Blowfish V3 Encrypt/Decrypt, Twofish and ThreeFish Decrypt Fallback test';
		} //end if
		//--

		//-- test Blowfish.CBC v2 :: decrypt only ; encrypt is N/A for v2
		$bfV2Key = 'some.BlowFish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testBfV2Data = 'bf448.v2!zMUO_nn69OJ-hZkcozYud2uhmtV3iSyqHQHOmIblfsphPtm-F8yepF8KJy7ag_LwPl0KwLuVqAeg32DQnogB5NJA7iTGlvHCzsTSCkM83RGrXagyp2hFZ0RMDgQXsGSI';
		$testBfV2Plain = 'Lorem Ipsum dolor sit Amet';
		$bf_v2_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$testBfV2Data, (string)$bfV2Key);
		if(
			((string)trim((string)$bf_v2_dec) == '')
			OR
			((string)$bf_v2_dec != (string)$testBfV2Plain)
			OR
			((string)\SmartHashCrypto::sh3a256((string)$bf_v2_dec) != (string)\SmartHashCrypto::sh3a256((string)$testBfV2Plain))
			OR
			((string)\SmartHashCrypto::sha224((string)$bf_v2_dec) != (string)\SmartHashCrypto::sha224((string)$testBfV2Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Blowfish V2 Decrypt test';
		} //end if
		//--

		//-- test Blowfish.CBC v1 :: decrypt only ; encrypt is N/A ; because is all upper hex can be tested with signature or without
		$bfV1Key = 'some.BlowFish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testBfV1Data = '695C491EF3E92DD8975423A91460F05F9DBBFDBE91DC55AE1D96CC43747B096D64CE08F42885D792505A56DF40CEE6B51FC399A3D756FADB4CE9A492BAE157B4B0DB0C6197D0E35B4C69F99266965686CB41628B75EA56CE006518F408CC0AF1';
		$testBfV1XData = 'bf'.(48*8).'.'.'v1'.'!'.$testBfV1Data;
		$testBfV1Plain = 'Lorem Ipsum dolor sit Amet';
		$bf_v1_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$testBfV1Data, (string)$bfV1Key);
		if(
			((string)trim((string)$bf_v1_dec) == '')
			OR
			((string)$bf_v1_dec != (string)$testBfV1Plain)
			OR
			((string)\SmartHashCrypto::sh3a256((string)$bf_v1_dec) != (string)\SmartHashCrypto::sh3a256((string)$testBfV1Plain))
			OR
			((string)\SmartHashCrypto::sha256((string)$bf_v1_dec) != (string)\SmartHashCrypto::sha256((string)$testBfV1Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Blowfish V1 Decrypt (without signature) test';
		} //end if
		$bf_v1x_dec = (string) \SmartCipherCrypto::bf_decrypt((string)$testBfV1XData, (string)$bfV1Key);
		if(
			((string)trim((string)$bf_v1x_dec) == '')
			OR
			((string)$bf_v1x_dec != (string)$testBfV1Plain)
			OR
			((string)\SmartHashCrypto::sh3a224((string)$bf_v1x_dec) != (string)\SmartHashCrypto::sh3a224((string)$testBfV1Plain))
			OR
			((string)\SmartHashCrypto::sha224((string)$bf_v1x_dec) != (string)\SmartHashCrypto::sha224((string)$testBfV1Plain))
		) {
			$err_misc[] = 'TestUnit FAILED # PHP Crypto, Blowfish V1 Decrypt (with signature) test';
		} //end if
		//--

		//--
		$time = 'TOTAL TIME was: '.(\microtime(true) - $time);
		//--

		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-samples/libs/templates/testunit/partials/crypto-test.inc.htm',
			[
				//--
				'EXE-TIME' 							=> (string) $time,
				'MISC-ERR' 							=> (string) \Smart::json_encode((array)$err_misc),
				'UNICODE-TEXT' 						=> (string) $unicode_text,
				'JS-ESCAPED' 						=> (string) \Smart::escape_js((string)$unicode_text),
				'HASH-SHA3-512-HEX' 				=> (string) \SmartHashCrypto::sh3a512((string)$unicode_text), // hex
				'HASH-SHA3-512-B64' 				=> (string) \SmartHashCrypto::sh3a512((string)$unicode_text, true), // b64
				'HASH-SHA3-384-HEX' 				=> (string) \SmartHashCrypto::sh3a384((string)$unicode_text), // hex
				'HASH-SHA3-384-B64' 				=> (string) \SmartHashCrypto::sh3a384((string)$unicode_text, true), // b64
				'HASH-SHA3-256-HEX' 				=> (string) \SmartHashCrypto::sh3a256((string)$unicode_text), // hex
				'HASH-SHA3-256-B64' 				=> (string) \SmartHashCrypto::sh3a256((string)$unicode_text, true), // b64
				'HASH-SHA3-224-HEX' 				=> (string) \SmartHashCrypto::sh3a224((string)$unicode_text), // hex
				'HASH-SHA3-224-B64' 				=> (string) \SmartHashCrypto::sh3a224((string)$unicode_text, true), // b64
				'HASH-SHA512-HEX' 					=> (string) \SmartHashCrypto::sha512((string)$unicode_text), // hex
				'HASH-SHA512-B64' 					=> (string) \SmartHashCrypto::sha512((string)$unicode_text, true), // b64
				'HASH-SHA384-HEX' 					=> (string) \SmartHashCrypto::sha384((string)$unicode_text), // hex
				'HASH-SHA384-B64' 					=> (string) \SmartHashCrypto::sha384((string)$unicode_text, true), // b64
				'HASH-SHA256-HEX' 					=> (string) \SmartHashCrypto::sha256((string)$unicode_text), // hex
				'HASH-SHA256-B64' 					=> (string) \SmartHashCrypto::sha256((string)$unicode_text, true), // b64
				'HASH-SHA224-HEX' 					=> (string) \SmartHashCrypto::sha224((string)$unicode_text), // hex
				'HASH-SHA224-B64' 					=> (string) \SmartHashCrypto::sha224((string)$unicode_text, true), // b64
				'HASH-SHA1-HEX' 					=> (string) \SmartHashCrypto::sha1((string)$unicode_text), // hex
				'HASH-SHA1-B64' 					=> (string) \SmartHashCrypto::sha1((string)$unicode_text, true), // b64
				'HASH-MD5-HEX' 						=> (string) \SmartHashCrypto::md5((string)$unicode_text), // hex
				'HASH-MD5-B64' 						=> (string) \SmartHashCrypto::md5((string)$unicode_text, true), // b64
				'HASH-CRC32B-HEX' 					=> (string) \SmartHashCrypto::crc32b((string)$unicode_text), // hex
				'HASH-CRC32B-B36' 					=> (string) \SmartHashCrypto::crc32b((string)$unicode_text, true), // b36
				'BIN2HEX-ENCODED' 					=> (string) $bin2hex,
				'HEX2BIN-DECODED' 					=> (string) $hex2bin,
				'BASE64-ENCODED' 					=> (string) $b64enc,
				'BASE64-DECODED' 					=> (string) $b64dec,
				'BASE-CONV-TESTS' 					=> (array)  $arr_test_bases,
				'BASE-CONV-DEC-TESTS' 				=> (array)  $arr_test_dec_bases,
				'BASE-CONV-STR' 					=> (string) $test_base_str,
				//--
				'THREEFISH_TF_BF-ENCRYPTED' 		=> (string) $t3ffb_enc,
				'THREEFISH_TF_BF-DECRYPTED' 		=> (string) $t3ffb_dec,
				'THREEFISH_TF_BF-NORND-ENCRYPTED' 	=> (string) $t3ffb_nornd_enc,
				'THREEFISH_TF_BF-NORND-DECRYPTED' 	=> (string) $t3ffb_nornd_dec,
				'THREEFISH_TF_BF-KEY' 				=> (string) $t3ffb_key,
				'THREEFISH_TF_BF-ALGO' 				=> (string) \Smart::escape_html((string)\SmartCipherCrypto::t3f_algo()).'+'.\Smart::escape_html((string)\SmartCipherCrypto::tf_algo()).'+'.\Smart::escape_html((string)\SmartCipherCrypto::bf_algo()),
				//--
				'THREEFISH-ENCRYPTED' 				=> (string) $t3f_enc,
				'THREEFISH-DECRYPTED' 				=> (string) $t3f_dec,
				'THREEFISH-NORND-ENCRYPTED' 		=> (string) $t3f_nornd_enc,
				'THREEFISH-NORND-DECRYPTED' 		=> (string) $t3f_nornd_dec,
				'THREEFISH-KEY' 					=> (string) $t3f_key,
				'THREEFISH-ALGO' 					=> (string) \Smart::escape_html((string)\SmartCipherCrypto::t3f_algo()),
				//--
				'TWOFISH_BLOWFISH-ENCRYPTED' 		=> (string) $tfb_enc,
				'TWOFISH_BLOWFISH-DECRYPTED' 		=> (string) $tfb_dec,
				'TWOFISH_BLOWFISH-NORND-ENCRYPTED' 	=> (string) $tfb_nornd_enc,
				'TWOFISH_BLOWFISH-NORND-DECRYPTED' 	=> (string) $tfb_nornd_dec,
				'TWOFISH_BLOWFISH-KEY' 				=> (string) $tfb_key,
				'TWOFISH_BLOWFISH-ALGO' 			=> (string) \Smart::escape_html((string)\SmartCipherCrypto::tf_algo()).'+'.\Smart::escape_html((string)\SmartCipherCrypto::bf_algo()),
				//--
				'TWOFISH-ENCRYPTED' 				=> (string) $tf_enc,
				'TWOFISH-DECRYPTED' 				=> (string) $tf_dec,
				'TWOFISH-NORND-ENCRYPTED' 			=> (string) $tf_nornd_enc,
				'TWOFISH-NORND-DECRYPTED' 			=> (string) $tf_nornd_dec,
				'TWOFISH-KEY' 						=> (string) $tf_key,
				'TWOFISH-ALGO' 						=> (string) \Smart::escape_html((string)\SmartCipherCrypto::tf_algo()),
				//--
				'BLOWFISH-ENCRYPTED' 				=> (string) $bf_enc,
				'BLOWFISH-DECRYPTED' 				=> (string) $bf_dec,
				'BLOWFISH-NORND-ENCRYPTED' 			=> (string) $bf_nornd_enc,
				'BLOWFISH-NORND-DECRYPTED' 			=> (string) $bf_nornd_dec,
				'BLOWFISH-KEY' 						=> (string) $bf_key,
				'BLOWFISH-ALGO' 					=> (string) \Smart::escape_html((string)\SmartCipherCrypto::bf_algo()),
				//--
				'CUSTOMCRYPT-ENC' 					=> (string) $he_enc,
				'CUSTOMCRYPT-DEC' 					=> (string) $he_dec,
				'CUSTOMCRYPT-ALGO' 					=> (string) \Smart::escape_html((string)\SmartCipherCrypto::algo()),
				//--
				'DIALOG-WIDTH' 						=> '725',
				'DIALOG-HEIGHT' 					=> '400',
				'IMG-SIGN' 							=> 'lib/framework/img/sign-info.svg',
				'IMG-CHECK' 						=> 'modules/mod-samples/libs/templates/testunit/img/test-crypto.svg',
				'TXT-MAIN-HTML' 					=> '<span style="color:#83B953;">Test OK: PHP / Javascript Cryptography.</span>',
				'TXT-INFO-HTML' 					=> '<h2><span style="color:#333333;"><span style="color:#83B953;">All</span> the SmartFramework Cryptography <span style="color:#83B953;">Tests PASSED on both PHP&nbsp;&amp;&nbsp;Javascript</span>:</span></h2>'.'<span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html("===== CRYPTO / TESTS: ===== \n * Unicode support / UTF-8 \n * JS-Escape \n * SHA3-512 \n * SHA3-384 \n * SHA3-256 \n * SHA3-224 \n * SHA512 \n * SHA384 \n * SHA256 \n * SHA224 \n * SHA1 \n * MD5 \n * CRC32B \n * Base64: Encode / Decode \n * Base[32, 36, 58, 62, 64s, 85, 92]: Encode / Decode \n * Bin2Hex / Hex2Bin \n * Threefish.1024+Twofish.256+Blowfish.448.CBC (v1): Encrypt / Decrypt (** PHP only) \n * Threefish.1024.CBC (v1): Encrypt / Decrypt (** PHP only) \n * Twofish.256+Blowfish.448.CBC (v1): Encrypt / Decrypt (** PHP only) \n * Twofish.256.CBC (v1): Encrypt / Decrypt \n * Blowfish.448.CBC (v3): Encrypt / Decrypt \n * Blowfish.448.CBC (v2): Decrypt only \n * Blowfish.384.CBC (v1): Decrypt only \n * Dynamic: Encrypt / Decrypt (** Only for PHP: ".\Smart::escape_html((string)\SmartCipherCrypto::algo()).") \n ===== END TESTS ... =====")).'</span>',
				'TEST-INFO' 						=> (string) 'Crypto Test Suite for SmartFramework: PHP + Javascript'
				//--
			]
		);
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	// converts a 64-bit integer number to base62 (string)
	private static function int10_to_base62_str(?int $num) {
		//--
		$num = (int) $num;
		if((int)$num < 0) {
			$num = 0;
		} //end if
		//--
		$b = 62;
		$base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		//--
		$r = (int) $num % $b;
		$res = (string) $base[$r];
		//--
		$q = (int) \floor((int)$num / (int)$b);
		while($q) {
			$r = (int) $q % $b;
			$q = (int) \floor((int)$q / (int)$b);
			$res = (string) $base[$r].$res;
		} //end while
		//--
		return (string) $res;
		//--
	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
