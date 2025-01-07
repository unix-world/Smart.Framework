<?php
// Class: \SmartModExtLib\AuthAdmins\Auth2FTotp
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

// The HOTP / TOTP code in this class is based on HOTP/TOTP Token Generator: github.com/lfkeitel/php-totp
// (c) 2016 Lee Keitel
// License: BSD

// The Base32 encode/decode in this class is based on: github.com/bbars/utils/blob/master/php-base32-encode-decode/
// (c) 2016 Denis Borzenko
// License: MIT

/**
 * Auth 2FA TOTP
 * This class provides 2Factor Authentication TOTP Time Based Token
 *
 * compatible with FreeOTP by RedHat ; support various combinations ; default is: algo SHA512 / 8 digits / 30 seconds (or 30 seconds, pref.)
 * compatible with GoogleAuthenticator ; algo SHA1 / 8 digits (or 6 digits, pref.) / 60 seconds (or 30 seconds, pref.)
 *
 * (c) 2023 unix-world.org
 * License: BSD
 *
 * @ignore
 *
 * @depends     PHP classes: Smart, SmartHashCrypto, SmartQR2DBarcode
 * @version 	v.20250103
 * @package 	development:modules:AuthAdmins
 *
 */
final class Auth2FTotp {

	// ::


	private const BITS_BASE_32_RIGHT_5 = 31;
	private const CHARSET_BASE_32 = 'abcdefghijklmnopqrstuvwxyz234567'; // RFC3548

	private const URL_OTPAUTH = 'otpauth://totp/';


	//==============================================================
	// returns a base32 encoded key (special algorithm, different than smart, does not have strings compression)
	public static function GenerateSecret(int $bits=256) : string {
		//--
		// RFC 4226 section 4 Requirement 6 requires a minimum key length of 128 bits = 16 bytes [key len is 26 bytes (32 bit hex)] ; recommended is at least 160 bits = 20 bytes ; however, the best supported key is 256 bits = 32 bytes (default, for SHA512) ; 384 bits = 48 bytes ; 512 bits = 64 bytes ; [key len is 103 bytes (64 bit hex)]
		// $bits can be (multiple of 8, between 16 and 64): 16, 24, 32, 40, 48, 56, 64 (default)
		// returns a string between 26 and 103 characters long
		//--
		// The Bits selected here must be lower or equal with the hashing algo to have ZERO possible collisions !
		//--
		if((int)((int)$bits % 8) != 0) {
			\Smart::log_warning(__METHOD__.' # ERROR: Bits must be a multiple of 8');
			return '';
		} //end if
		//--
		$bytes = (int) ceil((int)$bits / 8);
		//--
		if((int)$bytes < 16) { // 128 bit
			\Smart::log_warning(__METHOD__.' # ERROR: Min supported Bytes is 16');
			return '';
		} elseif((int)$bytes > 64) { // 512 bit
			\Smart::log_warning(__METHOD__.' # ERROR: Max supported Bytes is 64');
			return '';
		} //end if
		//--
		$secret = (string) \random_bytes((int)$bytes); // binary
		//--
		return (string) self::b32enc((string)$secret);
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	public static function GenerateBarcodeUrl(string $secret, string $user, string $issuer='', string $algo='sha512', int $length=8, int $tint=30) : string {
		//--
		// https://github.com/google/google-authenticator/wiki/Key-Uri-Format
		//--
		$issuer = (string) \trim((string)$issuer);
		if((string)$issuer == '') {
			$issuer = 'SmartTwoFactorAuthTOTP';
		} //end if
		//--
		return (string) self::URL_OTPAUTH.\Smart::escape_url((string)(\defined('\\SMART_SOFTWARE_NAMESPACE') ? \SMART_SOFTWARE_NAMESPACE : 'Smart.Auth2FTotp')).':'.\Smart::escape_url((string)$user).'?secret='.\Smart::escape_url((string)$secret).'&algorithm='.\Smart::escape_url((string)\strtoupper((string)$algo)).'&digits='.(int)$length.'&period='.(int)$tint.'&issuer='.\Smart::escape_url((string)$issuer).'&'; //lock=false';
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	public static function GenerateBarcodeQrCodeSVGFromUrl(string $barcode_2faurl, string $hexcolor='#373737') {
		//--
		$barcode_2faurl = (string) \trim((string)$barcode_2faurl);
		if(
			((string)$barcode_2faurl == '')
			OR
			(strpos((string)$barcode_2faurl, (string)self::URL_OTPAUTH) !== 0)
		) {
			return '';
		} //end if
		//--
		return (string) (new \SmartQR2DBarcode('L'))->renderAsSVG((string)$barcode_2faurl, [ 'cm' => (string)$hexcolor, 'wq' => 0 ]); // {{{SYNC-QRCODE-2FA}}}
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	// returns a number as string (digits) ; the input key will be decoded from base32 (special algorithm, different than smart, does not have strings compression)
	public static function GenerateToken(string $key, string $algo='sha512', int $length=8, int $tint=30, int $adjsec=0) : string {
		//--
		$key = (string) \trim((string)$key);
		if((string)$key == '') {
			\Smart::log_warning(__METHOD__.' # ERROR: Empty Hex Key');
			return '';
		} elseif((int)\strlen((string)$key) < 20) { // actually is 26 (128 bit), but be more flexible
			\Smart::log_warning(__METHOD__.' # ERROR: Hex Key is too short');
			return '';
		} elseif((int)\strlen((string)$key) > 128) { // actually is 103 (512 bit), but be more flexible
			\Smart::log_warning(__METHOD__.' # ERROR: Hex Key is too long');
			return '';
		} //end if
		//--
		$key = (string) self::b32dec((string)$key); // do not trim decoded key !
		if((string)\trim((string)$key) == '') {
			\Smart::log_warning(__METHOD__.' # ERROR: Empty Key');
			return '';
		} //end if
		//--
		$algo = (string) \strtolower((string)\trim((string)$algo));
		switch((string)$algo) {
			case 'md5':
			case 'sha1':
			case 'sha224':
			case 'sha256':
			case 'sha384':
			case 'sha512':
				// ok ...
				break;
			default:
				\Smart::log_warning(__METHOD__.' # ERROR: Invalid Algo Selected: '.\strtoupper((string)$algo).' Hash/HMAC/Algo');
				return '';
		} //end switch
		//--
		if((int)$length < 4) {
			\Smart::log_warning(__METHOD__.' # ERROR: Min Length is 4');
			return '';
		} elseif((int)$length > 16) {
			\Smart::log_warning(__METHOD__.' # ERROR: Max Length is 16');
			return '';
		} //end if
		//--
		if((int)$tint < 15) {
			\Smart::log_warning(__METHOD__.' # ERROR: Min TimeInterval is 15'); // 15 seconds
			return '';
		} elseif((int)$tint > 600) {
			\Smart::log_warning(__METHOD__.' # ERROR: Min TimeInterval is 600'); // 10 min
			return '';
		} //end if
		//-- get the current unix timestamp if one isn't given
		$time = (int) \time();
		//-- calculate the count
		$now   = (int) ((int)$time + (int)$adjsec); // adjust with seconds, the clock
		$count = (int) \floor((int)$now / (int)$tint);
		//-- generate a normal HOTP token
		return (string) self::genHmac((string)$key, (string)$algo, (int)$count, (int)$length);
		//--
	} //END FUNCTION
	//==============================================================


	//======== [PRIVATES]


	//==============================================================
	private static function genHmac(string $key, string $algo, int $count, int $length) : string {
		//--
		$scount = (string) self::packCounter((int)$count);
		//--
		$hash = (string) \SmartHashCrypto::hmac((string)$algo, (string)$key, (string)$scount);
		//--
		$code = (string) self::genValue((string)$hash, (int)$length);
		$code = (string) \str_pad((string)$code, (int)$length, '0', \STR_PAD_LEFT);
		$code = (string) \substr((string)$code, (int)(-1 * (int)$length));
		//--
		return (string) $code;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function genValue(string $hash, int $length) : string {
		//-- convert to decimal
		$arr = (array) \str_split((string)$hash, 2);
		$hmac_result = []; // store calculate decimal
		foreach($arr as $kk => $hex) {
			$hmac_result[] = (int) \hexdec((string)$hex);
		} //end foreach
		//--
		$offset = (int) ($hmac_result[(int)\Smart::array_size((array)$hmac_result)-1] & 0xf);
		//--
		$code = (int)(
			$hmac_result[$offset] & 0x7f) << 24
			| ($hmac_result[$offset+1] & 0xff) << 16
			| ($hmac_result[$offset+2] & 0xff) << 8
			| ($hmac_result[$offset+3] & 0xff
		);
		//--
		return (string) ((int)$code % \pow(10, (int)$length));
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function packCounter(int $counter) : string {
		//-- the counter value can be more than one byte long, so we need to pack it down properly.
		$cur_counter = [ 0, 0, 0, 0, 0, 0, 0, 0 ];
		for($i=7; $i>=0; $i--) {
			$cur_counter[$i] = (string) \pack('C*', (int)$counter);
			$counter = (int) ($counter >> 8);
		} //end for
		$bin_counter = (string) \implode('', (array)$cur_counter);
		//-- pad to 8 chars
		if((string)\strlen((string)$bin_counter) < 8) {
			$bin_counter = (string) \str_repeat((string)\chr(0), (int)(8 - (int)\strlen((string)$bin_counter))).$bin_counter;
		} //end if
		//--
		return (string) $bin_counter;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function b32enc(string $data) : string { // this is a different algorithm than the one in smart framework, compatible with google authenticator
		//--
		$dataSize = (int) \strlen((string)$data);
		$res = '';
		$remainder = 0;
		$remainderSize = 0;
		//--
		for($i=0; $i<$dataSize; $i++) {
			$b = \ord((string)$data[$i]);
			$remainder = ($remainder << 8) | $b;
			$remainderSize += 8;
			while($remainderSize > 4) {
				$remainderSize -= 5;
				$c = (int) $remainder & (self::BITS_BASE_32_RIGHT_5 << $remainderSize);
				$c >>= $remainderSize;
				$res .= self::CHARSET_BASE_32[(int)$c];
			} //end while
		} //end for
		if((int)$remainderSize > 0) {
			$remainder <<= (5 - $remainderSize); // remainderSize < 5:
			$c = (int) $remainder & self::BITS_BASE_32_RIGHT_5;
			$res .= self::CHARSET_BASE_32[(int)$c];
		} //end if
		//--
		return (string) $res;
		//--
	} //END FUNCTION
	//==============================================================


	//==============================================================
	private static function b32dec(string $data) : string {
		//--
		$data     = (string) \strtolower((string)\trim((string)$data));
		$dataSize = (int)    \strlen((string)$data);
		$buf = 0;
		$bufSize = 0;
		$res = '';
		//--
		for($i=0; $i<$dataSize; $i++) {
			$c = (string) $data[$i];
			$b = \strpos((string)self::CHARSET_BASE_32, (string)$c);
			if($b === false) {
				\Smart::log_notice(__METHOD__.' # ERROR: Encoded string is invalid, it contains unknown char #'.\ord((string)$c));
				return '';
			} //end if
			$buf = ($buf << 5) | $b;
			$bufSize += 5;
			if($bufSize > 7) {
				$bufSize -= 8;
				$b = ($buf & (0xff << $bufSize)) >> $bufSize;
				$res .= (string) \chr($b);
			} //end if
		} //end for
		//--
		return (string) $res;
		//--
	} //END FUNCTION
	//==============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


/* Sample Usage

// # generate a new key
//$key = \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateSecret(16); die('Key: '.$key);

$key = 'sm57jvghyoer7pw6w3jkx5ay7q'; // test key, generated above

// # generate tokens based on a previous generated key, below
$totp = (string) \SmartModExtLib\AuthAdmins\Auth2FTotp::GenerateToken((string)$key, 'sha512', 8, 60); die('Token: '.$totp);

*/

// end of php code
