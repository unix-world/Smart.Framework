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
 * @version 	v.20210819
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
		$err_bases = [];
		//--
		$test_int = (int) \time();
		$test_b62_from_int10_old = (string) self::int10_to_base62_str((int)$test_int);
		$test_b62_from_int10_new = (string) \Smart::int10_to_base62_str((int)$test_int);
		if((string)$test_b62_from_int10_old != (string)$test_b62_from_int10_new) {
			$err_bases[] = 'TestUnit FAILED :: B62 From Int (base10) test Errors'."\n".'OLD='.$test_b62_from_int10_old."\n".'NEW='.$test_b62_from_int10_new."\n".'INT='.(int)$test_int;
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
				$err_bases[] = 'TestUnit FAILED :: BaseFromHex Convert to Base `'.(int)$key.'` Errors'."\n".'EXPECTED='.$val."\n".'RESULT='.$tmp_bconv."\n".'HEXSTR='.(string)$test_base_hex_str;
			} //end if
			if((int)$key == 64) {
				$tmp_back_str = (string) \Smart::b64s_dec((string)$tmp_bconv);
				$tmp_back_hex = (string) \bin2hex((string)$tmp_back_str);
			} else {
				$tmp_back_hex = (string) \Smart::base_to_hex_convert((string)$tmp_bconv, (int)$key);
				$tmp_back_str = (string) \hex2bin((string)$tmp_back_hex);
			} //end if else
			$arr_test_dec_bases[$key] = (string) $tmp_back_str;
			if((string)$tmp_back_str !== (string)$test_base_str) { // hex may difer due to tha fact that backward will not do dechex() but only binhex() over result ...
				$err_bases[] = 'TestUnit FAILED :: BaseToHex Convert from Base `'.(int)$key.'` Errors'."\n".'EXPECTED='.$test_base_str."\n".'RESULT='.$tmp_back_str."\n".'HEXSTR='.(string)$tmp_back_hex;
			} //end if
		} //end for
		//--

		//--
		$unicode_text = "Unicode String [ ".\time()." ]: @ Smart スマート // Cloud Application Platform クラウドアプリケーションプラットフォーム '".\implode('', \array_keys(\SmartUnicode::accented_chars()))." \" <p></p>
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

		//--
		$test_key = (string) 'TestUnit // This is a test key for Crypto Cipher ...'.'!$'.SMART_FRAMEWORK_SECURITY_KEY.'#'.time().'#'.microtime(true).'::'.$unicode_text;
		//--

		//--
		$hkey = (string) $test_key;
		//--
		$he_enc = \SmartUtils::crypto_encrypt($unicode_text, $hkey);
		$he_dec = \SmartUtils::crypto_decrypt($he_enc, $hkey);
		//--
		if(((string)$he_dec != (string)$unicode_text) OR (\sha1($he_dec) != \SmartHashCrypto::sha1($unicode_text))) {
			\Smart::raise_error('TestUnit FAILED in '.__METHOD__.'() :: Crypto Cipher test', 'TestUnit: Crypto Cipher test failed ...');
			return;
		} //end if
		//--

		//-- test v2 encrypt/decrypt
		$bf_key = (string) $test_key;
		$bf_enc = \SmartUtils::crypto_blowfish_encrypt($unicode_text, $bf_key);
		$bf_dec = \SmartUtils::crypto_blowfish_decrypt($bf_enc, $bf_key);
		if(((string)$bf_dec != (string)$unicode_text) OR ((string)\SmartHashCrypto::sha512($bf_dec) != (string)\SmartHashCrypto::sha512($unicode_text))) {
			\Smart::raise_error('TestUnit FAILED in '.__METHOD__.'() :: Crypto Blowfish test', 'TestUnit: Blowfish test failed ...');
			return;
		} //end if
		//--

		//-- test v1 (decrypt only)
		$bfV1Key = 'some.BlowFish! - Key@Test 2ks i782s982 s2hwgsjh2wsvng2wfs2w78s528 srt&^ # *&^&#*# e3hsfejwsfjh';
		$testBfV1Data = '695C491EF3E92DD8975423A91460F05F9DBBFDBE91DC55AE1D96CC43747B096D64CE08F42885D792505A56DF40CEE6B51FC399A3D756FADB4CE9A492BAE157B4B0DB0C6197D0E35B4C69F99266965686CB41628B75EA56CE006518F408CC0AF1';
		$testBfV1XData = 'bf'.(48*8).'.'.'v1'.'!'.$testBfV1Data;
		$testBfV1Plain = 'Lorem Ipsum dolor sit Amet';
		$bf_v1_dec = \SmartUtils::crypto_blowfish_decrypt($testBfV1Data, $bfV1Key);
		if(((string)$bf_v1_dec != (string)$testBfV1Plain) OR ((string)\SmartHashCrypto::sha256($bf_v1_dec) != (string)\SmartHashCrypto::sha256($testBfV1Plain))) {
			\Smart::raise_error('TestUnit FAILED in '.__METHOD__.'() :: Crypto Blowfish V1 Decrypt test', 'TestUnit: Blowfish V1 Decrypt test failed ...');
			return;
		} //end if
		$bf_v1x_dec = \SmartUtils::crypto_blowfish_decrypt($testBfV1XData, $bfV1Key);
		if(((string)$bf_v1x_dec != (string)$testBfV1Plain) OR ((string)\SmartHashCrypto::sha256($bf_v1x_dec) != (string)\SmartHashCrypto::sha256($testBfV1Plain))) {
			\Smart::raise_error('TestUnit FAILED in '.__METHOD__.'() :: Crypto Blowfish V1 Decrypt test', 'TestUnit: Blowfish V1 Decrypt test failed ...');
			return;
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
				'EXE-TIME' 					=> (string) $time,
				'UNICODE-TEXT' 				=> (string) $unicode_text,
				'JS-ESCAPED' 				=> (string) \Smart::escape_js($unicode_text),
				'HASH-SHA512-HEX' 			=> (string) \SmartHashCrypto::sha512($unicode_text), // hex
				'HASH-SHA512-B64' 			=> (string) \SmartHashCrypto::sha512($unicode_text, true), // b64
				'HASH-SHA256-HEX' 			=> (string) \SmartHashCrypto::sha256($unicode_text), // hex
				'HASH-SHA256-B64' 			=> (string) \SmartHashCrypto::sha256($unicode_text, true), // b64
				'HASH-SHA1-HEX' 			=> (string) \SmartHashCrypto::sha1($unicode_text), // hex
				'HASH-SHA1-B64' 			=> (string) \SmartHashCrypto::sha1($unicode_text, true), // b64
				'HASH-MD5-HEX' 				=> (string) \SmartHashCrypto::md5($unicode_text), // hex
				'HASH-MD5-B64' 				=> (string) \SmartHashCrypto::md5($unicode_text, true), // b64
				'HASH-CRC32B-HEX' 			=> (string) \SmartHashCrypto::crc32b($unicode_text), // hex
				'HASH-CRC32B-B36' 			=> (string) \SmartHashCrypto::crc32b($unicode_text, true), // b36
				'BIN2HEX-ENCODED' 			=> (string) $bin2hex,
				'HEX2BIN-DECODED' 			=> (string) $hex2bin,
				'BASE64-ENCODED' 			=> (string) $b64enc,
				'BASE64-DECODED' 			=> (string) $b64dec,
				'BASE-CONV-ERR' 			=> (string) \Smart::json_encode((array)$err_bases),
				'BASE-CONV-TESTS' 			=> (array)  $arr_test_bases,
				'BASE-CONV-DEC-TESTS' 		=> (array)  $arr_test_dec_bases,
				'BASE-CONV-STR' 			=> (string) $test_base_str,
				'BLOWFISH-ENCRYPTED' 		=> (string) $bf_enc,
				'BLOWFISH-DECRYPTED' 		=> (string) $bf_dec,
				'BLOWFISH-KEY' 				=> (string) $bf_key,
				'BLOWFISH-OPTIONS' 			=> (string) \Smart::escape_html((string)\SmartUtils::crypto_blowfish_algo()),
				'HASHCRYPT-ENC' 			=> (string) $he_enc,
				'HASHCRYPT-DEC' 			=> (string) $he_dec,
				'HASHCRYPT-OPTIONS' 		=> (string) \Smart::escape_html((string)\SmartUtils::crypto_algo()),
				//--
				'DIALOG-WIDTH' 				=> '725',
				'DIALOG-HEIGHT' 			=> '400',
				'IMG-SIGN' 					=> 'lib/framework/img/sign-info.svg',
				'IMG-CHECK' 				=> 'modules/mod-samples/libs/templates/testunit/img/test-crypto.svg',
				'TXT-MAIN-HTML' 			=> '<span style="color:#83B953;">Test OK: PHP / Javascript Unicode Crypto.</span>',
				'TXT-INFO-HTML' 			=> '<h2><span style="color:#333333;"><span style="color:#83B953;">All</span> the SmartFramework Unicode <span style="color:#83B953;">Tests PASSED on both PHP&nbsp;&amp;&nbsp;Javascript</span>:</span></h2>'.'<span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html("===== Unicode CRYPTO / TESTS: ===== \n * Unicode support / UTF-8 \n * JS-Escape \n * SHA512 \n * SHA256 \n * SHA1 \n * MD5 \n * CRC32B \n * Base64: Encode / Decode \n * Base[32, 36, 58, 62, 64s, 85, 92]: Encode / Decode \n * Bin2Hex / Hex2Bin \n * Blowfish.448.CBC (v2): Encrypt / Decrypt \n * Blowfish.384.CBC (v1): Decrypt only \n * Custom: Encrypt / Decrypt (** Only for PHP: ".\Smart::escape_html((string)\SmartUtils::crypto_algo()).") \n ===== END TESTS ... =====")).'</span>',
				'TEST-INFO' 				=> (string) 'Crypto Test Suite for SmartFramework: PHP + Javascript'
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
		if($num < 0) {
			$num = 0;
		} //end if
		//--
		$b = 62;
		$base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		//--
		$r = (int) $num % $b;
		$res = (string) $base[$r];
		//--
		$q = (int) \floor($num / $b);
		while($q) {
			$r = (int) $q % $b;
			$q = (int) \floor($q / $b);
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
