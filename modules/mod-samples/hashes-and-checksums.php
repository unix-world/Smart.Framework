<?php
// Controller: Samples/HashesAndChecksums
// Route: ?/page/samples.hashes-and-checksums (?page=samples.hashes-and-checksums)
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED');


/**
 * Admin Controller
 *
 * @ignore
 *
 */
abstract class SmartAppAbstractController extends SmartAbstractAppController {

	// v.20240114

	final public function Initialize() {

		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module
		$this->PageViewSetCfg('template-file', 'template-benchmark.htm'); // the default template
		//--

		//--
		return true;
		//--

	} //END FUNCTION


	final public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		$title = 'Hashes and Checksums';
		//--
		$date = (string) date('Y-m-d H:i:s O');
		//--
		$hr = '<hr style="border: none 0; border-top: 1px solid #EEEEEE; height: 1px;">';
		//--
		$this->PageViewSetVars([
			'title' 	=> (string) $title,
			'main' 		=> '<h1>'.Smart::escape_html((string)$title.' # '.$date).'</h1>'.$hr.'<pre>'.Smart::escape_html((string)SmartUtils::pretty_print_var((array)$this->getHashesAndChecsumsPrettyPrint())).'</pre>'.$hr.'<br><br>'
		]);
		//--

	} //END FUNCTION


	private function getHashesAndChecsumsPrettyPrint() : array {

		//--
		$emptyString 	= '';
		$isoString 		= 'Lorem Ipsum dolor sit Amet';
		$unicodeString 	= 'Unicode String:		şŞţŢăĂîÎâÂșȘțȚ (05-09#';
		$hmacKey 		= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#|;,_~`"';
		$polyKey 		= (string) md5((string)$hmacKey); // 32
		//--

		//--
		$bfKey = (string) SmartHashCrypto::sha224((string)$isoString); // 56
		$bfIv  = (string) substr((string)$bfKey, 0, 8); // 8
		//--
		$bfTimeEmpty = microtime(true);
		$bfEncEmpty = (string) (new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->encrypt((string)base64_encode((string)$emptyString));
		$bfDecEmpty = (string) base64_decode((string)(new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->decrypt((string)$bfEncEmpty));
		$bfTimeEmpty = microtime(true) - $bfTimeEmpty;
		//--
		$bfTimeIso = microtime(true);
		$bfEncIso = (string) (new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->encrypt((string)base64_encode((string)$isoString));
		$bfDecIso = (string) base64_decode((string)(new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->decrypt((string)$bfEncIso));
		$bfTimeIso = microtime(true) - $bfTimeIso;
		//--
		$bfTimeUnicode = microtime(true);
		$bfEncUnicode = (string) (new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->encrypt((string)base64_encode((string)$unicodeString));
		$bfDecUnicode = (string) base64_decode((string)(new SmartCryptoCiphersBlowfishCBC((string)$bfKey, (string)$bfIv))->decrypt((string)$bfEncUnicode));
		$bfTimeUnicode = microtime(true) - $bfTimeUnicode;
		//--
		$bSfTimeEmpty = microtime(true);
		$bSfEncEmpty = (string) SmartCipherCrypto::bf_encrypt((string)$emptyString, (string)$isoString);
		$bSfDecEmpty = (string) SmartCipherCrypto::bf_decrypt((string)$bSfEncEmpty, (string)$isoString);
		$bSfTimeEmpty = microtime(true) - $bSfTimeEmpty;
		//--
		$bSfTimeIso = microtime(true);
		$bSfEncIso = (string) SmartCipherCrypto::bf_encrypt((string)$isoString, (string)$isoString);
		$bSfDecIso = (string) SmartCipherCrypto::bf_decrypt((string)$bSfEncIso, (string)$isoString);
		$bSfTimeIso = microtime(true) - $bSfTimeIso;
		//--
		$bSfTimeUnicode = microtime(true);
		$bSfEncUnicode = (string) SmartCipherCrypto::bf_encrypt((string)$unicodeString, (string)$isoString);
		$bSfDecUnicode = (string) SmartCipherCrypto::bf_decrypt((string)$bSfEncUnicode, (string)$isoString);
		$bSfTimeUnicode = microtime(true) - $bSfTimeUnicode;
		//--

		//--
		$tfKey = (string) SmartHashCrypto::md5((string)$isoString); // 32
		$tfIv  = (string) substr((string)$tfKey, 0, 16); // 16
		//--
		$tfTimeEmpty = microtime(true);
		$tfEncEmpty = (string) (new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->encrypt((string)base64_encode((string)$emptyString));
		$tfDecEmpty = (string) base64_decode((string)(new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->decrypt((string)$tfEncEmpty));
		$tfTimeEmpty = microtime(true) - $tfTimeEmpty;
		//--
		$tfTimeIso = microtime(true);
		$tfEncIso = (string) (new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->encrypt((string)base64_encode((string)$isoString));
		$tfDecIso = (string) base64_decode((string)(new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->decrypt((string)$tfEncIso));
		$tfTimeIso = microtime(true) - $tfTimeIso;
		//--
		$tfTimeUnicode = microtime(true);
		$tfEncUnicode = (string) (new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->encrypt((string)base64_encode((string)$unicodeString));
		$tfDecUnicode = (string) base64_decode((string)(new SmartCryptoCiphersTwofishCBC((string)$tfKey, (string)$tfIv))->decrypt((string)$tfEncUnicode));
		$tfTimeUnicode = microtime(true) - $tfTimeUnicode;
		//--
		$tSfTimeEmpty = microtime(true);
		$tSfEncEmpty = (string) SmartCipherCrypto::tf_encrypt((string)$emptyString, (string)$isoString);
		$tSfDecEmpty = (string) SmartCipherCrypto::tf_decrypt((string)$tSfEncEmpty, (string)$isoString);
		$tSfTimeEmpty = microtime(true) - $tSfTimeEmpty;
		//--
		$tSfTimeIso = microtime(true);
		$tSfEncIso = (string) SmartCipherCrypto::tf_encrypt((string)$isoString, (string)$isoString);
		$tSfDecIso = (string) SmartCipherCrypto::tf_decrypt((string)$tSfEncIso, (string)$isoString);
		$tSfTimeIso = microtime(true) - $tSfTimeIso;
		//--
		$tSfTimeUnicode = microtime(true);
		$tSfEncUnicode = (string) SmartCipherCrypto::tf_encrypt((string)$unicodeString, (string)$isoString);
		$tSfDecUnicode = (string) SmartCipherCrypto::tf_decrypt((string)$tSfEncUnicode, (string)$isoString);
		$tSfTimeUnicode = microtime(true) - $tSfTimeUnicode;
		//--
		$tbSfTimeEmpty = microtime(true);
		$tbSfEncEmpty = (string) SmartCipherCrypto::tf_encrypt((string)$emptyString, (string)$isoString, true);
		$tbSfDecEmpty = (string) SmartCipherCrypto::tf_decrypt((string)$tbSfEncEmpty, (string)$isoString);
		$tbSfTimeEmpty = microtime(true) - $tbSfTimeEmpty;
		//--
		$tbSfTimeIso = microtime(true);
		$tbSfEncIso = (string) SmartCipherCrypto::tf_encrypt((string)$isoString, (string)$isoString, true);
		$tbSfDecIso = (string) SmartCipherCrypto::tf_decrypt((string)$tbSfEncIso, (string)$isoString);
		$tbSfTimeIso = microtime(true) - $tbSfTimeIso;
		//--
		$tbSfTimeUnicode = microtime(true);
		$tbSfEncUnicode = (string) SmartCipherCrypto::tf_encrypt((string)$unicodeString, (string)$isoString, true);
		$tbSfDecUnicode = (string) SmartCipherCrypto::tf_decrypt((string)$tbSfEncUnicode, (string)$isoString);
		$tbSfTimeUnicode = microtime(true) - $tbSfTimeUnicode;
		//--

		//--
		$t3fKey = (string) SmartHashCrypto::sh3a512((string)$isoString);
		$t3fIv  = (string) SmartHashCrypto::sha512((string)$isoString);
		$t3fTweak = 'tweak0123456789#';
		//--
		$t3fTimeEmpty = microtime(true);
		$t3fEncEmpty = (string) (new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->encrypt((string)base64_encode((string)$emptyString));
		$t3fDecEmpty = (string) base64_decode((string)(new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->decrypt((string)$t3fEncEmpty));
		$t3fTimeEmpty = microtime(true) - $t3fTimeEmpty;
		//--
		$t3fTimeIso = microtime(true);
		$t3fEncIso = (string) (new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->encrypt((string)base64_encode((string)$isoString));
		$t3fDecIso = (string) base64_decode((string)(new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->decrypt((string)$t3fEncIso));
		$t3fTimeIso = microtime(true) - $t3fTimeIso;
		//--
		$t3fTimeUnicode = microtime(true);
		$t3fEncUnicode = (string) (new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->encrypt((string)base64_encode((string)$unicodeString));
		$t3fDecUnicode = (string) base64_decode((string)(new SmartCryptoCiphersThreefishCBC((string)$t3fKey, (string)$t3fIv, (string)$t3fTweak))->decrypt((string)$t3fEncUnicode));
		$t3fTimeUnicode = microtime(true) - $t3fTimeUnicode;
		//--
		$t3SfTimeEmpty = microtime(true);
		$t3SfEncEmpty = (string) SmartCipherCrypto::t3f_encrypt((string)$emptyString, (string)$isoString);
		$t3SfDecEmpty = (string) SmartCipherCrypto::t3f_decrypt((string)$t3SfEncEmpty, (string)$isoString);
		$t3SfTimeEmpty = microtime(true) - $t3SfTimeEmpty;
		//--
		$t3SfTimeIso = microtime(true);
		$t3SfEncIso = (string) SmartCipherCrypto::t3f_encrypt((string)$isoString, (string)$isoString);
		$t3SfDecIso = (string) SmartCipherCrypto::t3f_decrypt((string)$t3SfEncIso, (string)$isoString);
		$t3SfTimeIso = microtime(true) - $t3SfTimeIso;
		//--
		$t3SfTimeUnicode = microtime(true);
		$t3SfEncUnicode = (string) SmartCipherCrypto::t3f_encrypt((string)$unicodeString, (string)$isoString);
		$t3SfDecUnicode = (string) SmartCipherCrypto::t3f_decrypt((string)$t3SfEncUnicode, (string)$isoString);
		$t3SfTimeUnicode = microtime(true) - $t3SfTimeUnicode;
		//--
		$t3t2bSfTimeEmpty = microtime(true);
		$t3t2bSfEncEmpty = (string) SmartCipherCrypto::t3f_encrypt((string)$emptyString, (string)$isoString, true);
		$t3t2bSfDecEmpty = (string) SmartCipherCrypto::t3f_decrypt((string)$t3t2bSfEncEmpty, (string)$isoString);
		$t3t2bSfTimeEmpty = microtime(true) - $t3t2bSfTimeEmpty;
		//--
		$t3t2bSfTimeIso = microtime(true);
		$t3t2bSfEncIso = (string) SmartCipherCrypto::t3f_encrypt((string)$isoString, (string)$isoString, true);
		$t3t2bSfDecIso = (string) SmartCipherCrypto::t3f_decrypt((string)$t3t2bSfEncIso, (string)$isoString);
		$t3t2bSfTimeIso = microtime(true) - $t3t2bSfTimeIso;
		//--
		$t3t2bSfTimeUnicode = microtime(true);
		$t3t2bSfEncUnicode = (string) SmartCipherCrypto::t3f_encrypt((string)$unicodeString, (string)$isoString, true);
		$t3t2bSfDecUnicode = (string) SmartCipherCrypto::t3f_decrypt((string)$t3t2bSfEncUnicode, (string)$isoString);
		$t3t2bSfTimeUnicode = microtime(true) - $t3t2bSfTimeUnicode;
		//--

		//--
		$arrTests = [
		//========
			'HEX-BIN' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) bin2hex((string)$emptyString),
					'dec' => (string) hex2bin((string)bin2hex((string)$emptyString)),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) bin2hex((string)$isoString),
					'dec' => (string) hex2bin((string)bin2hex((string)$isoString)),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) bin2hex((string)$unicodeString),
					'dec' => (string) hex2bin((string)bin2hex((string)$unicodeString)),
				],
			],
			'BASE-64' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) base64_encode((string)$emptyString),
					'dec' => (string) base64_decode((string)base64_encode((string)$emptyString)),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) base64_encode((string)$isoString),
					'dec' => (string) base64_decode((string)base64_encode((string)$isoString)),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) base64_encode((string)$unicodeString),
					'dec' => (string) base64_decode((string)base64_encode((string)$unicodeString)),
				],
			],
			'CRYPTO:BLOWFISH:448' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) base64_encode((string)$bfEncEmpty),
					'dec' => (string) $bfDecEmpty,
					'tms' => (string) $bfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) base64_encode((string)$bfEncIso),
					'dec' => (string) $bfDecIso,
					'tms' => (string) $bfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) base64_encode((string)$bfEncUnicode),
					'dec' => (string) $bfDecUnicode,
					'tms' => (string) $bfTimeUnicode.' s',
				],
			],
			'CRYPTO:BLOWFISH:448:Smart' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) $bSfEncEmpty,
					'dec' => (string) $bSfDecEmpty,
					'tms' => (string) $bSfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) $bSfEncIso,
					'dec' => (string) $bSfDecIso,
					'tms' => (string) $bSfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) $bSfEncUnicode,
					'dec' => (string) $bSfDecUnicode,
					'tms' => (string) $bSfTimeUnicode.' s',
				],
			],
			'CRYPTO:TWOFISH:256' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) base64_encode((string)$tfEncEmpty),
					'dec' => (string) $tfDecEmpty,
					'tms' => (string) $tfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) base64_encode((string)$tfEncIso),
					'dec' => (string) $tfDecIso,
					'tms' => (string) $tfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) base64_encode((string)$tfEncUnicode),
					'dec' => (string) $tfDecUnicode,
					'tms' => (string) $tfTimeUnicode.' s',
				],
			],
			'CRYPTO:TWOFISH:256:Smart' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) $tSfEncEmpty,
					'dec' => (string) $tSfDecEmpty,
					'tms' => (string) $tSfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) $tSfEncIso,
					'dec' => (string) $tSfDecIso,
					'tms' => (string) $tSfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) $tSfEncUnicode,
					'dec' => (string) $tSfDecUnicode,
					'tms' => (string) $tSfTimeUnicode.' s',
				],
			],
			'CRYPTO:TWOFISH:256+BLOWFISH:448:Smart' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) $tbSfEncEmpty,
					'dec' => (string) $tbSfDecEmpty,
					'tms' => (string) $tbSfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) $tbSfEncIso,
					'dec' => (string) $tbSfDecIso,
					'tms' => (string) $tbSfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) $tbSfEncUnicode,
					'dec' => (string) $tbSfDecUnicode,
					'tms' => (string) $tbSfTimeUnicode.' s',
				],
			],
			'CRYPTO:THREEFISH:1024' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) base64_encode((string)$t3fEncEmpty),
					'dec' => (string) $t3fDecEmpty,
					'tms' => (string) $t3fTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) base64_encode((string)$t3fEncIso),
					'dec' => (string) $t3fDecIso,
					'tms' => (string) $t3fTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) base64_encode((string)$t3fEncUnicode),
					'dec' => (string) $t3fDecUnicode,
					'tms' => (string) $t3fTimeUnicode.' s',
				],
			],
			'CRYPTO:THREEFISH:1024:Smart' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) $t3SfEncEmpty,
					'dec' => (string) $t3SfDecEmpty,
					'tms' => (string) $t3SfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) $t3SfEncIso,
					'dec' => (string) $t3SfDecIso,
					'tms' => (string) $t3SfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) $t3SfEncUnicode,
					'dec' => (string) $t3SfDecUnicode,
					'tms' => (string) $t3SfTimeUnicode.' s',
				],
			],
			'CRYPTO:THREEFISH:1024+TWOFISH:256+BLOWFISH:448:Smart' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'enc' => (string) $t3t2bSfEncEmpty,
					'dec' => (string) $t3t2bSfDecEmpty,
					'tms' => (string) $t3t2bSfTimeEmpty.' s',
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'enc' => (string) $t3t2bSfEncIso,
					'dec' => (string) $t3t2bSfDecIso,
					'tms' => (string) $t3t2bSfTimeIso.' s',
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'enc' => (string) $t3t2bSfEncUnicode,
					'dec' => (string) $t3t2bSfDecUnicode,
					'tms' => (string) $t3t2bSfTimeUnicode.' s',
				],
			],
			'DERIVATIONS' => [
				'[PASSWORD:B92+]' => [
					'hash' 			=> (string) SmartHashCrypto::password((string)$unicodeString, (string)$hmacKey),
				],
				'[CHECKSUM:B62]' => [
					'default' 		=> (string) SmartHashCrypto::checksum((string)$unicodeString),
					'salted' 		=> (string) SmartHashCrypto::checksum((string)$unicodeString, (string)$hmacKey),
				],
				'[PBKDF2-Key-HEX:8]' => [
					'sha3-512' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-512'),
					'sha3-384' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-384'),
					'sha3-256' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-256'),
					'sha3-224' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-224'),
					'sha512' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha512'),
					'sha384' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha384'),
					'sha256' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha256'),
					'sha224' 		=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha224'),
					'sha1' 			=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'sha1'),
					'md5' 			=> (string) SmartHashCrypto::pbkdf2DerivedHexKey((string)$unicodeString, (string)$isoString, 8, 20, 'md5'),
				],
				'[PBKDF2-Key-B92:8]' => [
					'sha3-512' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-512'),
					'sha3-384' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-384'),
					'sha3-256' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-256'),
					'sha3-224' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha3-224'),
					'sha512' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha512'),
					'sha384' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha384'),
					'sha256' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha256'),
					'sha224' 		=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha224'),
					'sha1' 			=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'sha1'),
					'md5' 			=> (string) SmartHashCrypto::pbkdf2DerivedB92Key((string)$unicodeString, (string)$isoString, 8, 20, 'md5'),
				],
				'[PBKDF2-Pre-Key-B92]' => [
					'key'			=> (string) SmartHashCrypto::pbkdf2PreDerivedB92Key((string)$isoString.chr(0).$unicodeString),
				],
			],
		//========
			'POLY1305' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::poly1305((string)$polyKey, (string)$unicodeString, true),
				],
			],
			'CRC32B' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::crc32b((string)$emptyString),
					'b36' => (string) SmartHashCrypto::crc32b((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::crc32b((string)$isoString),
					'b36' => (string) SmartHashCrypto::crc32b((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::crc32b((string)$unicodeString),
					'b36' => (string) SmartHashCrypto::crc32b((string)$unicodeString, true),
				],
			],
		//========
			'SHA-3-512' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sh3a512((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sh3a512((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sh3a512((string)$isoString),
					'b64' => (string) SmartHashCrypto::sh3a512((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sh3a512((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sh3a512((string)$unicodeString, true),
				],
			],
			'SHA-512' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sha512((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sha512((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sha512((string)$isoString),
					'b64' => (string) SmartHashCrypto::sha512((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sha512((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sha512((string)$unicodeString, true),
				],
			],
			'SHA-3-384' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sh3a384((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sh3a384((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sh3a384((string)$isoString),
					'b64' => (string) SmartHashCrypto::sh3a384((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sh3a384((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sh3a384((string)$unicodeString, true),
				],
			],
			'SHA-384' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sha384((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sha384((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sha384((string)$isoString),
					'b64' => (string) SmartHashCrypto::sha384((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sha384((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sha384((string)$unicodeString, true),
				],
			],
			'SHA-3-256' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sh3a256((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sh3a256((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sh3a256((string)$isoString),
					'b64' => (string) SmartHashCrypto::sh3a256((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sh3a256((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sh3a256((string)$unicodeString, true),
				],
			],
			'SHA-256' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sha256((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sha256((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sha256((string)$isoString),
					'b64' => (string) SmartHashCrypto::sha256((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sha256((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sha256((string)$unicodeString, true),
				],
			],
			'SHA-3-224' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sh3a224((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sh3a224((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sh3a224((string)$isoString),
					'b64' => (string) SmartHashCrypto::sh3a224((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sh3a224((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sh3a224((string)$unicodeString, true),
				],
			],
			'SHA-224' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sha224((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sha224((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sha224((string)$isoString),
					'b64' => (string) SmartHashCrypto::sha224((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sha224((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sha224((string)$unicodeString, true),
				],
			],
			'SHA1' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::sha1((string)$emptyString),
					'b64' => (string) SmartHashCrypto::sha1((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::sha1((string)$isoString),
					'b64' => (string) SmartHashCrypto::sha1((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::sha1((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::sha1((string)$unicodeString, true),
				],
			],
			'MD5' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::md5((string)$emptyString),
					'b64' => (string) SmartHashCrypto::md5((string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::md5((string)$isoString),
					'b64' => (string) SmartHashCrypto::md5((string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::md5((string)$unicodeString),
					'b64' => (string) SmartHashCrypto::md5((string)$unicodeString, true),
				],
			],
		//========
			'SHA-3-512-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-512', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-512-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA512', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-3-384-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-384', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-384-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA384', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-3-256-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-256', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-256-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA256', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-3-224-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA3-224', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA-224-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA224', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'SHA1-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('SHA1', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
			'MD5-Hmac' => [
				'[EMPTY-STRING]' => [
					'str' => (string) $emptyString,
					'hex' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$emptyString),
					'b64' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$emptyString, true),
				],
				'[ISO]' => [
					'str' => (string) $isoString,
					'hex' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$isoString),
					'b64' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$isoString, true),
				],
				'[UNICODE]' => [
					'str' => (string) $unicodeString,
					'hex' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$unicodeString),
					'b64' => (string) SmartHashCrypto::hmac('MD5', (string)$hmacKey, (string)$unicodeString, true),
				],
			],
		//========
		];
		//--

		//--
		return (array) $arrTests;
		//--

	} //END FUNCTION


} //END CLASS


/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends SmartAppAbstractController {} //END CLASS


/**
 * Admin Controller
 *
 * @ignore
 *
 */
final class SmartAppAdminController extends SmartAppAbstractController {} //END CLASS


/**
 * Task Controller
 *
 * @ignore
 *
 */
final class SmartAppTaskController extends SmartAppAbstractController {} //END CLASS


// end of php code
