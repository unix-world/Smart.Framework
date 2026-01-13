<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: Samples/EcdsaTest
// Route: ?page=samples.ecdsa-test&encryptedprivkey=yes|no&nonasn1=no|yes
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED');

/**
 * Abstract Controller
 *
 * @ignore
 *
 */
abstract class SmartAppAbstractController extends SmartAbstractAppController {

// go client certificate/privKey/pubKey from create-x509-certs.go

private const theGoCertPEM = '
-----BEGIN CERTIFICATE-----
MIIEQjCCA6SgAwIBAgIhAO+Jo93h152gTMpJp8XiiN+KxSzUqQ6+m4lRGOvBlXBt
MAoGCCqGSM49BAMEMIHdMQswCQYDVQQGEwJVSzEPMA0GA1UECBMGTG9uZG9uMQ8w
DQYDVQQHEwZMb25kb24xHDAaBgNVBAkTE1N0cmVldCBOb05hbWUgbm8uMDAxETAP
BgNVBBETCFpFUk8wMDAwMQ8wDQYDVQQKEwZnb2xhbmcxEjAQBgNVBAMTCWxvY2Fs
aG9zdDFWMFQGA1UEBRNNNTY3MjE3NTUzMTczNjEwMTA2OTk3NzQ0NzM0OTcwMzY5
MzAyNzMxMTY3MjUyNTc2NDEyOTc1NzM1NDExODA1ODMxNzc0Mzg2OTk2MDcwIBcN
MjYwMTExMjExMDE4WhgPMjEyNjAxMTEyMTEwMTlaMIHkMQswCQYDVQQGEwJSTzEN
MAsGA1UECBMEQ2x1ajEUMBIGA1UEBxMLQ2x1ai1OYXBvY2ExGDAWBgNVBAkTD05v
IFN0cmVldCBuby4wMDENMAsGA1UEERMEMzQwMDEQMA4GA1UEChMHc21hcnRnbzEc
MBoGA1UEAwwTd2VibWFzdGVyQGxvY2FsaG9zdDFXMFUGA1UEBRNOMTA4MzQ1OTU5
ODI1ODYyMDEwNTY3NDM5NjMwNTc2NDUxNDA3NDY2MDg3OTAxNTMzNzgxNjIzMTE4
MjE4MDEwOTMzODQ3NTIzNjg0NDYxMIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQB
gMqm+D0kMNk/TlF/tsNBRnG7SBjzOd1874sXeGfTf+P6d2MPWcN6NdfyNOuuAQ+P
sBWJGPq0la65Ym32tSib6g0BuPmBDhErJFG2/qeDV3JSDfzH5IklLAoD/I64VxR5
2JbuKUWzlemrHS/xIF0rUcY9OSiRRYnrai/jDlgd3+oDGGCjgeYwgeMwDgYDVR0P
AQH/BAQDAgP4MDsGA1UdJQQ0MDIGCCsGAQUFBwMEBggrBgEFBQcDAwYIKwYBBQUH
AwgGCCsGAQUFBwMBBggrBgEFBQcDAjAMBgNVHRMBAf8EAjAAMDMGA1UdDgQsBCow
MUtBMEhZWDVNN0FINjJRRi01NFZaQjlXM1ZPMjdOLTkwMDU3NDQxOTQwNQYDVR0j
BC4wLIAqMDFLQTBIWVg1TFQwOVo5VkctN1hJQUJCVkFMME4yQi0yMTA1ODUyMDQz
MBoGA1UdEQQTMBGCCWxvY2FsaG9zdIcEfwAAATAKBggqhkjOPQQDBAOBiwAwgYcC
QgDJa3Ojj8V/yXdbdGUn4FKdWwLMvKNLqtRPgEWuUsWDPLIcekOdrU9bXVBZlUT3
/3nVNdl8kZhkcsrLHOfAw2zpmQJBJTQDcqEjez6CDbQHuTdf4DSg82FU3FsnDf3W
dfg7dM09Iqumf+AWwzWT/3K+JgjMP+VqShKYibhgwmAup4/qDQw=
-----END CERTIFICATE-----
';

private const theGoPrivkeyPEM = '
-----BEGIN PRIVATE KEY-----
MIHuAgEAMBAGByqGSM49AgEGBSuBBAAjBIHWMIHTAgEBBEIAyF1a4Sgn9pjO2Opb
H3cjWdjG0VW6RSdc/B0+HmBKqjnYwH+uxYp2ciQdWP73YIG5R/0GU7qF6NzSwrHh
DwnX/IChgYkDgYYABAGAyqb4PSQw2T9OUX+2w0FGcbtIGPM53Xzvixd4Z9N/4/p3
Yw9Zw3o11/I0664BD4+wFYkY+rSVrrlibfa1KJvqDQG4+YEOESskUbb+p4NXclIN
/MfkiSUsCgP8jrhXFHnYlu4pRbOV6asdL/EgXStRxj05KJFFietqL+MOWB3f6gMY
YA==
-----END PRIVATE KEY-----
';

private const theGoPubkeyPEM = '
-----BEGIN PUBLIC KEY-----
MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQBgMqm+D0kMNk/TlF/tsNBRnG7SBjz
Od1874sXeGfTf+P6d2MPWcN6NdfyNOuuAQ+PsBWJGPq0la65Ym32tSib6g0BuPmB
DhErJFG2/qeDV3JSDfzH5IklLAoD/I64VxR52JbuKUWzlemrHS/xIF0rUcY9OSiR
RYnrai/jDlgd3+oDGGA=
-----END PUBLIC KEY-----
';

private const theGoSignature = '
MIGIAkIB2D6ZaTZo0Fxa8D48vVvTwXSSOsyKIRGRYkCWGJvqxYLzHOO5lsry2mDEV+06k+cQxTWOq5GMPd6BkyP5f9+hwAcCQgEzBiJaN9b0FoO3wCwY9BVd6nKV5Y9SJxfFE2/scIeBxu6XJpAdyb5PcWEDQVnKjmMI2lIRxr0XLFlp5ZaFLV32jA==
';



private const theEncGoCertPEM = '
-----BEGIN CERTIFICATE-----
MIIEQTCCA6KgAwIBAgIgGNgSDTxBYkNNW+aGKXihwl+qzZKRIHJbLHfTaMPmL/ww
CgYIKoZIzj0EAwQwgd0xCzAJBgNVBAYTAlVLMQ8wDQYDVQQIEwZMb25kb24xDzAN
BgNVBAcTBkxvbmRvbjEcMBoGA1UECRMTU3RyZWV0IE5vTmFtZSBuby4wMDERMA8G
A1UEERMIWkVSTzAwMDAxDzANBgNVBAoTBmdvbGFuZzESMBAGA1UEAxMJbG9jYWxo
b3N0MVYwVAYDVQQFE00xMjEwODM3OTk4NDMyMzMwMjMwNTg2NjYxNDM2MzkyNzcz
MzA1Mjc2NzYzODg1NDMzMDMyMDAzNDQ3MDg3MDczMTY0MzAwNTI0MzQzMDAgFw0y
NjAxMTIxODAwMzJaGA8yMTI2MDExMjE4MDAzM1owgeMxCzAJBgNVBAYTAlJPMQ0w
CwYDVQQIEwRDbHVqMRQwEgYDVQQHEwtDbHVqLU5hcG9jYTEYMBYGA1UECRMPTm8g
U3RyZWV0IG5vLjAwMQ0wCwYDVQQREwQzNDAwMRAwDgYDVQQKEwdzbWFydGdvMRww
GgYDVQQDDBN3ZWJtYXN0ZXJAbG9jYWxob3N0MVYwVAYDVQQFE00xMTIzNzI3MTky
MDI0OTcwODA5MDgxNzE1NDUzNzA0NjIyNzE0MjQyNzY1NDQ1NDM4ODUyOTc0NjU3
MDQxOTE0NDU4OTI1MzY4NTI0NDCBmzAQBgcqhkjOPQIBBgUrgQQAIwOBhgAEAboH
dDKLqlg4mfW+fgr0M7BOSbGmpQgHHVGURASH0oBmVgGYQtREoqDA5IB21BS9BEMa
hS35XOaeJU4qBopHhjGlAUvvWN/obOyOYT1xZ9E0G9dInhTdRksiBb+AehVM9Bs9
6ZAuJSYeHejN7rmvred3bEb30bhBbWlf7xHSyUimskJCo4HmMIHjMA4GA1UdDwEB
/wQEAwID+DA7BgNVHSUENDAyBggrBgEFBQcDBAYIKwYBBQUHAwMGCCsGAQUFBwMI
BggrBgEFBQcDAQYIKwYBBQUHAwIwDAYDVR0TAQH/BAIwADAzBgNVHQ4ELAQqMDFL
QTBHT0IwREswVVBNS1MtNVBEVjRPSE5ZWVo2MS0wNzcxNTE1NzgyMDUGA1UdIwQu
MCyAKjAxS0EwR09CMEQ2V09KVE5ELTNTSE9FSzdGS0Y2NFUtNDYxNTM4MTMyNjAa
BgNVHREEEzARgglsb2NhbGhvc3SHBH8AAAEwCgYIKoZIzj0EAwQDgYwAMIGIAkIA
xC8wH5unyx/dof/8F/+yU3UexLGw47Nh0Q8nMmfNVmsXELXq33vVvk3pGFqAIKtB
/SHUN1Otj53klagzFCHn8+ACQgCDUZeq5G9BIkLLn7obQX87VtLcyBHRzdhIxNHI
x4/8/9yWDbQV7b+cf0Xhh447i3LUE4vdTWhRHbH5DQZhZnTSIA==
-----END CERTIFICATE-----
';

private const theEncGoPrivkeyPEM = '
-----BEGIN PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-256-CBC,e1fd4ffff7ac13020c11f7f4dba65abf

ktl3fEhm0FM7TIrviVUJyxP387Ce3PHQ55kZvq12QkAWGa5USLYcrOkhwKv8fK7r
pNSrU7FHiBFzk9d7wMTIWpgHCsU2XE80V+Y23qS7sle1+1lVPnH5aclZGNe13myJ
huNvW2ypoaYz5rFwW+wNjDNcHVA4fM63e4wJ0J54SF++e68Si8xJxC+jn/mYEg82
vaUa/JjX+/lY8QQfVmojExoh5egqjq6/BVXRkNJJ24zvJ/JRXgSVmnL4aj1DUz9T
EGiI5wTaQXemR19jHcmAEyV5ECnFCIhcnZW4IhexouMzIplOrJhmzbNRENFTlYqZ
T4wU+k6CQPhUXLZvtMqxjQ==
-----END PRIVATE KEY-----
';

private const theEncGoPubkeyPEM = '
-----BEGIN PUBLIC KEY-----
MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQBugd0MouqWDiZ9b5+CvQzsE5Jsaal
CAcdUZREBIfSgGZWAZhC1ESioMDkgHbUFL0EQxqFLflc5p4lTioGikeGMaUBS+9Y
3+hs7I5hPXFn0TQb10ieFN1GSyIFv4B6FUz0Gz3pkC4lJh4d6M3uua+t53dsRvfR
uEFtaV/vEdLJSKayQkI=
-----END PUBLIC KEY-----
';

private const theEncGoSignature = '
MIGIAkIBR5sHnh6w9L4+CWCxSSMWYPwIOVSN1BLxfKkOzXpkfegm0JXtjt1SPDdEQ2dyHLUU+ieP4IElCNjxdMmA4I0nisQCQgGVJIwgBDafxFECOvGHfoRhg9MP5HgAe4OsUVfGAM1CqIq/tgG+EPuY6v62Y+eKySAHs3BXnPgvdHvJzUX+hd2umw==
';

private const theEncPrivKeyPass = 'pass:my'; // {{{SYNC-WITH-GOLANG-TESTS-CREATE-X509}}}


	final public function Initialize() {
		//--
		return true;
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		// do nothing
		//--
	} //END FUNCTION


	final public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		if(SmartCryptoEcdsaOpenSSL::isAvailable() !== true) {
			$this->PageViewSetErrorStatus(503, 'WARNING: OpenSSL EcDSA is N/A ...');
			return;
		} //end if

		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'text/plain');
		$this->PageViewSetCfg('rawdisp', 'inline; filename="sample-ecdsa.txt"');

		//--

		$usePassPhrase = (string) trim((string)$this->RequestVarGet('encryptedprivkey', '', 'string'));
		$nonASN1mode   = (string) trim((string)$this->RequestVarGet('nonasn1', '', 'string'));

		$pKeyPassPhrase = (string) self::theEncPrivKeyPass;
		if((string)$usePassPhrase == 'no') {
			$pKeyPassPhrase = null;
		} //end if

		$useASN1 = true;
		if((string)$nonASN1mode == 'yes') {
			$useASN1 = false;
		} //end if
		//--

		$main = [];

		//-- php

		$arrCerts = (array) SmartCryptoEcdsaOpenSSL::newCertificate(
			['commonName' => 'My Sample Name', 'emailAddress' => 'my@email.local', 'organizationName' => 'my.local', 'organizationalUnitName' => 'My Sample Test - ECDSA Digital Signature'],
			1, // years
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_ECDSA_DEF_CURVE,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_CSR_DEF_ALGO,
			$pKeyPassPhrase // do not cast to string, can be null
		);
		if((string)($arrCerts['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'New Certificate Error: '.($arrCerts['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'New Certificate: '.SmartUtils::pretty_print_var($arrCerts);

		$dataMsg = 'A message to Sign'; // {{{SYNC-SIGN-MSG-GO-PHP}}}
		$main[] = 'Data to Sign/Verify: `'.Smart::escape_html($dataMsg).'`';

		$arrSign = (array) SmartCryptoEcdsaOpenSSL::signData(
			(string) ($arrCerts['privKey'] ?? null),
			(string) ($arrCerts['pubKey'] ?? null),
			(string) $dataMsg,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
			$pKeyPassPhrase, // do not cast to string, can be null
			(bool)   $useASN1
		);
		if((string)($arrSign['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'Sign Error: '.($arrSign['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Sign Data: '.SmartUtils::pretty_print_var($arrSign);

		$arrVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) ($arrCerts['pubKey'] ?? null),
			(string) $dataMsg,
			(string) ($arrSign['signatureB64'] ?? null),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
			(bool)   $useASN1
		);
		if(((string)($arrVfy['err'] ?? null) != '') OR (($arrVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Sign Error: '.($arrVfy['err'] ?? null).' # '.($arrVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Data: '.SmartUtils::pretty_print_var($arrVfy);

		//-- go plain

		$arrGoSign = (array) SmartCryptoEcdsaOpenSSL::signData(
			(string) trim((string)self::theGoPrivkeyPEM),
			(string) trim((string)self::theGoPubkeyPEM),
			(string) $dataMsg,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
		);
		if((string)($arrGoSign['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'Sign Go Error: '.($arrGoSign['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Sign Go Data: '.SmartUtils::pretty_print_var($arrGoSign);

		$arrGoVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theGoPubkeyPEM),
			(string) $dataMsg,
			(string) ($arrGoSign['signatureB64'] ?? null),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)($arrGoVfy['err'] ?? null) != '') OR (($arrGoVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Sign Go Error: '.($arrGoVfy['err'] ?? null).' # '.($arrGoVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Go Data: '.SmartUtils::pretty_print_var($arrGoVfy);

		$main[] = 'Go Signature of Data: '.trim((string)self::theGoSignature);

		$arrGoSignVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theGoPubkeyPEM),
			(string) $dataMsg,
			(string) trim((string)self::theGoSignature),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)($arrGoSignVfy['err'] ?? null) != '') OR (($arrGoSignVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Sign Go Error: '.($arrGoSignVfy['err'] ?? null).' # '.($arrGoSignVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Go Signed Data: '.SmartUtils::pretty_print_var($arrGoSignVfy);

		//-- go enc

		$decryptedGoPrivKeyArr = (array) SmartCryptoEcdsaOpenSSL::decryptPrivateKeyPem(
			(string) trim((string)self::theEncGoPrivkeyPEM),
			(string) self::theEncPrivKeyPass
		);
		if((string)($decryptedGoPrivKeyArr['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'Go Enc Decrypted PrivKey Error: '.($decryptedGoPrivKeyArr['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Go Enc Decrypted PrivKey: '.SmartUtils::pretty_print_var($decryptedGoPrivKeyArr);

		$reEncryptedGoPrivKeyArr = (array) SmartCryptoEcdsaOpenSSL::encryptPrivateKeyPem(
			(string) ($decryptedGoPrivKeyArr['privKey'] ?? null),
			(string) self::theEncPrivKeyPass
		);
		if((string)($reEncryptedGoPrivKeyArr['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'Go Enc Re-Encrypted PrivKey Error: '.($reEncryptedGoPrivKeyArr['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Go Enc Re-Encrypted PrivKey: '.SmartUtils::pretty_print_var($reEncryptedGoPrivKeyArr);

		$arrGoEncSign = (array) SmartCryptoEcdsaOpenSSL::signData(
			(string) trim((string)self::theEncGoPrivkeyPEM),
			(string) trim((string)self::theEncGoPubkeyPEM),
			(string) $dataMsg,
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO,
			(string) self::theEncPrivKeyPass
		);
		if((string)($arrGoEncSign['err'] ?? null) != '') {
			$this->PageViewSetCfg('error', 'Enc Sign Go Error: '.($arrGoEncSign['err'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Enc Sign Go Data: '.SmartUtils::pretty_print_var($arrGoEncSign);

		$arrGoEncVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theEncGoPubkeyPEM),
			(string) $dataMsg,
			(string) ($arrGoEncSign['signatureB64'] ?? null),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)($arrGoEncVfy['err'] ?? null) != '') OR (($arrGoEncVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Enc Sign Go Error: '.($arrGoEncVfy['err'] ?? null).' # '.($arrGoEncVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Enc Verify Go Data: '.SmartUtils::pretty_print_var($arrGoEncVfy);

		$main[] = 'Go Enc Signature of Data: '.trim((string)self::theEncGoSignature);

		$arrGoEncSignVfy = (array) SmartCryptoEcdsaOpenSSL::verifySignedData(
			(string) trim((string)self::theEncGoPubkeyPEM),
			(string) $dataMsg,
			(string) trim((string)self::theEncGoSignature),
			(string) SmartCryptoEcdsaOpenSSL::OPENSSL_SIGN_DEF_ALGO
		);
		if(((string)($arrGoEncSignVfy['err'] ?? null) != '') OR (($arrGoEncSignVfy['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Enc Sign Go Error: '.($arrGoEncSignVfy['err'] ?? null).' # '.($arrGoEncSignVfy['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Go Enc Signed Data: '.SmartUtils::pretty_print_var($arrGoEncSignVfy);

		//--

		$this->PageViewSetVar(
			'main',
			(string) implode("\n\n", (array)$main)
		);

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
