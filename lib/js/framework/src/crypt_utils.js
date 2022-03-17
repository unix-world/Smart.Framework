
// [LIB - Smart.Framework / JS / Crypto Utils]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: smartJ$Utils
// CONTAINS: Base64 encode/decode ; Base32/36/58/62/64s/85/92 encode/decode ; CryptoHash: CRC32B, MD5, SHA1, SHA256, SHA512 (Hex / B64) ; Blowfish (CBC) encrypt / decrypt
// r.20220313

//==================================================================
// The code is released under the BSD License.
//  Copyright (c) unix-world.org
// The file contains portions of code from:
//	CRC32B: https://github.com/fastest963/js-crc32, Copyright (c) James Hartig
//	MD5, SHA1, SHA256, SHA512, Base64.Encode/Decode: https://github.com/hirak/phpjs, Copyright (c) Kevin van Zonneveld and Contributors (http://phpjs.org/authors)
//	Blowfish.Encrypt/Decrypt: https://github.com/agorlov/javascript-blowfish, Copyright (c) Alexandr Gorlov
//==================================================================

//================== [ES6]

//=======================================
// CLASS :: Crypto Test
//=======================================

/**
 * CLASS :: Smart TestCrypto (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @private : used only internally
 *
 * @desc Test the browser compliance for the crypto classes
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$TestCrypto
 * @static
 * @frozen
 *
 */
const smartJ$TestCrypto = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$TestCrypto';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	// :: static

	/**
	 * Raise Error
	 *
	 * @memberof smartJ$TestCrypto
	 * @method raiseError
	 * @static
	 *
	 * @throws ERROR
	 *
	 * @param {String} err The Error Message
	 */
	const raiseError = (err) => {
		_p$.error(_N$, 'ERR: Browser FAILED with:', err);
	}; //END
	_C$.raiseError = raiseError;

	//--
	// Base32  Test: 5ARJ9CDNM8P90ADQ74QBECST0I2E5JV2PTHD3OMHC90U4GB1QTGSEOEHC70M8J749HI4RP2D20A1G6KMJ0E93
	// Base36  Test: cksdjkz3988qjf4fcdy5bczersui2n235512z0czjcd5fwyk0ougp75sns9yckqgr67073sipmjr0n4nxf
	// Base58  Test: 2ZWiQiAbzWECL8Q7F297zGcNCeZHjccgwo7hw61WzGDuGErXR2ZaiB1Hc8AUuZEWTT4kj8xPL
	// Base62  Test: Nhp2biqj3pWbD2CRYvtc2AwDuJe44CJp8fRLSrw4x2bXrmDeGbOCW1kvxzasvrJXzJAmV4n
	// Base64s Test: VW5pY29kZSBTdHJpbmc6CQnFn8WexaPFosSDxILDrsOOw6LDgsiZyJjIm8iaICgwNS0wOSM.
	// Base85  Test: T@Df@<a).Oqo{b+!vomLhRM.OG^<(+?h!R?hqX)maSv9?C+/4[Ie2LRCx1S^Q6dQ+-
	// Base92  Test: u3LL}_`$ThRdx{k;ukc~B|0td8N~EuQoJa"SL/_c/@fWV>3)&b`+{+&ubLMikMcyH
	//--

	/**
	 * Return Test expected result for Base64 algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testBase64
	 * @static
	 */
	const testBase64 = () => {
		return 'VW5pY29kZSBTdHJpbmc6CQnFn8WexaPFosSDxILDrsOOw6LDgsiZyJjIm8iaICgwNS0wOSM=';
	}; //END
	_C$.testBase64 = testBase64; // export

	/**
	 * Return Test expected result for CRC32B algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testCRC32B
	 * @static
	 */
	const testCRC32B = (b36=false) => {
		if(b36 === true) {
			return '01hcnnc';
		} else {
			return '055757b8';
		} //end if else
	}; //END
	_C$.testCRC32B = testCRC32B; // export

	/**
	 * Return Test expected result for MD5 algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testMD5
	 * @static
	 */
	const testMD5 = (b64=false) => {
		if(b64 === true) {
			return 'PW7773idgEOjkmVB/rmhjA==';
		} else {
			return '3d6efbef789d8043a3926541feb9a18c';
		} //end if else
	}; //END
	_C$.testMD5 = testMD5; // export

	/**
	 * Return Test expected result for SHA1 algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testSHA1
	 * @static
	 */
	const testSHA1 = (b64=false) => {
		if(b64 === true) {
			return 'WgoSffvbg0kbn7wO453JRwZsQmw=';
		} else {
			return '5a0a127dfbdb83491b9fbc0ee39dc947066c426c';
		} //end if else
	}; //END
	_C$.testSHA1 = testSHA1; // export

	/**
	 * Return Test expected result for SHA256 algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testSHA256
	 * @static
	 */
	const testSHA256 = (b64=false) => {
		if(b64 === true) {
			return 'GTvIwHM9xKaYl7+tgXkV22cyONrQnWDMkSm7PMsiVKg=';
		} else {
			return '193bc8c0733dc4a69897bfad817915db673238dad09d60cc9129bb3ccb2254a8';
		} //end if else
	}; //END
	_C$.testSHA256 = testSHA256; // export

	/**
	 * Return Test expected result for SHA512 algo for the string set in the class unicodeString()
	 *
	 * @memberof smartJ$TestCrypto
	 * @method testSHA512
	 * @static
	 */
	const testSHA512 = (b64=false) => {
		if(b64 === true) {
			return 'tRt7Uw8rciM8Vf4P08PES7q+TbboJq/Fi0hmilLqef5fvSclO5iu287kAdu4qgCpcFbdCIpWtEugozIN8ttcHg==';
		} else {
			return 'b51b7b530f2b72233c55fe0fd3c3c44bbabe4db6e826afc58b48668a52ea79fe5fbd27253b98aedbcee401dbb8aa00a97056dd088a56b44ba0a3320df2db5c1e';
		} //end if else
	}; //END
	_C$.testSHA512 = testSHA512; // export

	/**
	 * Return Test expected result for a sample unicode string to test the algo's with
	 *
	 * @memberof smartJ$TestCrypto
	 * @method unicodeString
	 * @static
	 */
	const unicodeString = () => {
		return 'Unicode String:		şŞţŢăĂîÎâÂșȘțȚ (05-09#';
	}; //END
	_C$.unicodeString = unicodeString; // export

}}; //END CLASS

smartJ$TestCrypto.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$TestCrypto = smartJ$TestCrypto; // global export
} //end if

//=======================================
// CLASS :: Base64 enc/dec
//=======================================

/**
 * CLASS :: Smart Base64 (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$TestCrypto
 *
 * @throws 			console.error
 *
 * @desc Base64 for JavaScript: Encode / Decode
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$Base64
 * @static
 * @frozen
 *
 */
const smartJ$Base64 = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$Base64';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const _Utils$ = smartJ$Utils;

	const _Test$ = smartJ$TestCrypto;

	//== PRIVATES BASE64

	let testBase64PassedEnc = false;
	let testBase64PassedDec = false;

	//== PUBLIC BASE64

	/**
	 * Encode a string to Base64
	 * Supports also UTF-8
	 *
	 * @hint Javascript btoa() does not support UTF-8 but only ASCII
	 *
	 * @memberof smartJ$Base64
	 * @method encode
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The plain string (to be encoded)
	 * @param {Boolean} bin Set to TRUE if the string is binary to avoid re-encode to UTF-8
	 * @return {String} the Base64 encoded string
	 */
	const encode = function(s, bin=false) {
		//--
		if(testBase64PassedEnc !== true) {
			if(base64_core_enc(_Test$.unicodeString()) == _Test$.testBase64()) {
				testBase64PassedEnc = true; // test passed
			} else { // test failed
				_Test$.raiseError('Base64/Encode');
				return '';
			} //end if else
		} //end if
		//--
		return String(base64_core_enc(s, bin));
		//--
	}; //END
	_C$.encode = encode;

	/**
	 * Decode a string from Base64
	 * Supports also UTF-8
	 *
	 * @hint Javascript atob() does not support UTF-8 but only ASCII
	 *
	 * @memberof smartJ$Base64
	 * @method decode
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The B64 encoded string
	 * @param {Boolean} bin Set to TRUE if the string is binary to avoid re-decode as UTF-8
	 * @return {String} the plain (B64 decoded) string
	 */
	const decode = function(s, bin=false) {
		//--
		if(testBase64PassedDec !== true) {
			if(base64_core_dec(_Test$.testBase64()) == _Test$.unicodeString()) {
				testBase64PassedDec = true; // test passed
			} else { // test failed
				_Test$.raiseError('Base64/Decode');
				return '';
			} //end if else
		} //end if
		//--
		return String(base64_core_dec(s, bin));
		//--
	}; //END
	_C$.decode = decode;

	//== PRIVATES BASE64

	// PRIVATE :: BASE64 :: key
	const b64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

	// PRIVATE :: BASE64 :: encode
	const base64_core_enc = function(input, is_binary) {
		//-- safety checks
		input = _Utils$.stringPureVal(input); // cast to string, don't trim ! need to preserve the value
		if(input == '') {
			return '';
		} //end if
		//-- make it unicode
		if(is_binary !== true) { // binary content must not be re-encoded to UTF-8
			input = _Utils$.utf8_encode(input);
		} //end if
		//-- keys
		const keyStr = String(b64Chars);
		//-- encoder
		let output = '';
		let chr1, chr2, chr3;
		let enc1, enc2, enc3, enc4;
		let i = 0;
		//--
		do {
			//--
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
			//--
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
			//--
			if(isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if(isNaN(chr3)) {
				enc4 = 64;
			} //end if
			//--
			output = output + keyStr.charAt(enc1) + keyStr.charAt(enc2) +
			keyStr.charAt(enc3) + keyStr.charAt(enc4);
			//--
		} while(i < input.length);
		//--
		return String(output);
		//--
	}; //END

	// PRIVATE :: BASE64 :: decode
	const base64_core_dec = function(input, is_binary) {
		//-- safety checks
		input = _Utils$.stringPureVal(input); // cast to string, don't trim ! need to preserve the value
		if(input == '') {
			return '';
		} //end if
		//-- keys
		const keyStr = String(b64Chars);
		//-- decoder
		let output = '';
		let chr1, chr2, chr3;
		let enc1, enc2, enc3, enc4;
		let i = 0;
		//--
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, ''); // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
		//--
		do {
			//--
			enc1 = keyStr.indexOf(input.charAt(i++));
			enc2 = keyStr.indexOf(input.charAt(i++));
			enc3 = keyStr.indexOf(input.charAt(i++));
			enc4 = keyStr.indexOf(input.charAt(i++));
			//--
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
			//--
			output = output + String.fromCharCode(chr1);
			//--
			if(enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			} //end if
			//--
			if(enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			} //end if
			//--
		} while(i < input.length);
		//--
		if(is_binary !== true) { // binary content must not be re-decoded as UTF-8
			output = _Utils$.utf8_decode(output); // make it back unicode safe
		} //end if
		//--
		return String(output);
		//--
	}; //END

}}; //END CLASS

smartJ$Base64.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$Base64 = smartJ$Base64; // global export
} //end if

//=======================================
// CLASS :: Base Convert enc/dec
//=======================================

/**
 * CLASS :: Smart Base Convert (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$TestCrypto
 *
 * @throws 			console.error
 *
 * @desc Base Convert for JavaScript: Encode / Decode: base32, base36, base58, base62, base64s, base85, base92
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$BaseEncode
 * @static
 * @frozen
 *
 */
const smartJ$BaseEncode = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$BaseEncode';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const _Utils$ = smartJ$Utils;

	const _Ba$e64 = smartJ$Base64;

	/*
	 * Returns the Base64 (Safe URL) Modified Encoding from a string by replacing the standard base64 encoding as follows:
	 * '+' with '-',
	 * '/' with '_',
	 * '=' with '.'
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseEncode
	 * @method b64s_enc
	 * @static
	 *
	 * @param {String} 	str 				The string to be encoded
	 * @param {Boolean} bin Set to TRUE if the string is binary to avoid re-encode to UTF-8
	 * @return {String} 					The safe URL Base64 encoded string
	 */
	const b64s_enc = function(str, bin=false) {
		//--
		return _Ba$e64.encode(str, bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/\=/g, '.');
		//--
	}; //END
	_C$.b64s_enc = b64s_enc; // hidden export

	/*
	 * Returns the Decoded string from the Base64 (Safe URL) Encoding by replacing back as follows before applying the standard base64 decoding:
	 * '-' with '+',
	 * '_' with '/',
	 * '.' with '='
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseEncode
	 * @method b64s_dec
	 * @static
	 *
	 * @param STRING 	enc 				The safe URL Base64 encoded string
	 * @param {Boolean} bin 				Set to TRUE if the string is binary to avoid re-decode as UTF-8
	 * @return STRING 						The decoded string
	 *
	 */
	const b64s_dec = function(enc, bin=false) {
		//--
		return _Ba$e64.decode(_Utils$.stringPureVal(enc).replace(/\-/g, '+').replace(/_/g, '/').replace(/\./g, '='), bin);
		//--
	}; //END
	_C$.b64s_dec = b64s_dec; // hidden export

	/**
	 * Safe convert to hex from any of the following bases: 32, 36, 58, 62, 85, 92
	 * In case of error will return an empty string.
	 *
	 * @memberof smartJ$Utils
	 * @method base_to_hex_convert
	 * @static
	 *
	 * @param  {String} 	encoded			:: A string (baseXX encoded) that was previous encoded using base_from_hex_convert()
	 * @param  {Integer} 	currentBase		:: The base to convert ; Available source base: 32, 36, 58, 62, 85, 92
	 * @return {String} 					:: The encoded string in the selected base or empty string on error
	 */
	const base_to_hex_convert = function(encoded, currentBase) {
		//--
		// based on idea by: https://github.com/tuupola/base62 # License MIT
		//--
		const _m$ = 'base_to_hex_convert';
		//--
		encoded = _Utils$.stringPureVal(encoded, true);
		if(encoded == '') {
			_p$.warn(_N$, _m$, 'Empty Input');
			return '';
		} //end if
		//--
		let baseCharset = base_get_alphabet(currentBase);
		if(baseCharset == '') {
			_p$.warn(_N$, _m$, 'Invalid Current Base:', currentBase);
			return '';
		} //end if
		currentBase = baseCharset.length;
		//--
		let data = encoded.split('');
		encoded = null;
		data = data.map((c) => { const result = String(baseCharset).indexOf(c); if(result < 0) { _p$.warn(_N$, _m$, 'Invalid Base Character:', c); } return result; });
		//--
		let leadingZeroes = 0;
		while(data.length && 0 === data[0]) {
			leadingZeroes++;
			data.shift(); // trim off leading zeroes
		} //end while
		//--
		let converted = base_asciihex_convert(data, currentBase, 256);
		data = null;
		//--
		if(0 < leadingZeroes) {
			let arrZeroFill = new Array(leadingZeroes).fill(0, 0, leadingZeroes);
			converted = [].concat(arrZeroFill, converted);
		} //end if
		//--
		converted = converted.map((c) => String.fromCharCode(c)).join(''); // map ascii (256) to binary ; [php] chr($code) = [js] String.fromCharCode(code)
		//--
		return String(_Utils$.bin2hex(converted, true));
		//--
	}; //END
	_C$.base_to_hex_convert = base_to_hex_convert; // export

	/**
	 * Safe convert from hex to any of the following bases: 32, 36, 58, 62, 85, 92
	 * In case of error will return an empty string.
	 *
	 * @memberof smartJ$Utils
	 * @method base_from_hex_convert
	 * @static
	 *
	 * @param {String} 		hexstr			:: A hexadecimal string (base16) ; can be from bin2hex(string) or from dechex(integer) but in the case of using dechex must use also left padding with zeros to have an even length of the hex data
	 * @param {Integer} 	targetBase		:: The base to convert ; Available target base: 32, 36, 58, 62, 85, 92
	 * @return {String} 					:: The encoded string in the selected base or empty string on error
	 */
	const base_from_hex_convert = function(hexstr, targetBase) {
		//--
		// based on idea by: https://github.com/tuupola/base62 # License MIT
		//--
		const _m$ = 'base_from_hex_convert';
		//--
		hexstr = _Utils$.stringPureVal(hexstr, true);
		if(hexstr == '') {
			_p$.warn(_N$, _m$, 'Empty Input');
			return '';
		} //end if
		//--
		let baseCharset = base_get_alphabet(targetBase);
		if(baseCharset == '') {
			_p$.warn(_N$, _m$, 'Invalid Target Base:', targetBase);
			return '';
		} //end if
		targetBase = baseCharset.length;
		//--
		let source = String(_Utils$.hex2bin(hexstr, true)); // lowercase will apply in hex2bin
		if(source == '') {
			_p$.warn(_N$, _m$, 'Invalid Input, NOT HEX:', hexstr);
			return '';
		} //end if
		hexstr = null; // free mem
		source = source.split('');
		source = source.map((c) => String(c).charCodeAt(0)); // map hex (16) to ascii (256) ; [php] ord($str) = [js] str.charCodeAt(0)
		//--
		let leadingZeroes = 0;
		while(source.length && 0 === source[0]) {
			leadingZeroes++;
			source.shift(); // trim off leading zeroes
		} //end while
		//
		//--
		let result = base_asciihex_convert(source, 256, targetBase);
		source = null;
		//--
		if(0 < leadingZeroes) {
			let arrZeroFill = new Array(leadingZeroes).fill(0, 0, leadingZeroes);
			result = [].concat(arrZeroFill, result);
		} //end if
		//--
		baseCharset = baseCharset.split('');
		//--
		return String(result.map((el) => baseCharset[el]).join(''));
		//--
	}; //END
	_C$.base_from_hex_convert = base_from_hex_convert; // export

	/*
	 * Get the alphabet for base conversions
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 *
	 * @param 	{String} 	theBase 		The base: '32', '36', '58', '62', '85', '92'
	 * @return 	{String} 					The base alphabet or empty string if invalid base
	 */
	const base_get_alphabet = function(theBase) {
		//--
		const minAlphabet = '0123456789abcdefghijklmnopqrstuv'; // b32
		const minComplAlphabet = 'wxyz'; // b36 extra with b32
		const extraAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // b62
		const glyphAlphabet = '.-:+=^!/*?&<>()[]{}@%$#'; // b85
		const glyphExtAlphabet = '|;,_~`"'; // b92 extra with b85
		const altAlphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz'; // b58
		//--
		const targetBase = _Utils$.stringPureVal(theBase, true);
		let fromcharset = '';
		switch(targetBase) {
			case '32':
				fromcharset = String(minAlphabet).toUpperCase();
				break;
			case '36':
				fromcharset = String(minAlphabet) + String(minComplAlphabet);
				break;
			case '58':
				fromcharset = String(altAlphabet); // compatible with smartgo
				break;
			case '62':
				fromcharset = String(minAlphabet) + String(minComplAlphabet) + String(extraAlphabet);
				break;
			case '85':
				fromcharset = String(minAlphabet) + String(minComplAlphabet) + String(extraAlphabet) + String(glyphAlphabet); // https://rfc.zeromq.org/spec:32/Z85/
				break;
			case '92':
				fromcharset = String(minAlphabet) + String(minComplAlphabet) + String(extraAlphabet) + String(glyphAlphabet) + String(glyphExtAlphabet); // uxm, compatible with smartgo
				break;
			default:
				_p$.error(_N$, 'base_get_alphabet', 'Invalid Base:', targetBase);
		} //end switch
		//--
		return String(fromcharset);
		//--
	}; //END
	// no export

	/*
	 * Convert between bases: 16 (hex) to any of: 32, 36, 58, 62, 85, 92 | or viceversa
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 *
	 * @param 	{Array} 	source
	 * @param 	{Integer} 	sourceBase 		the source base to convert from
	 * @param 	{Integer} 	targetBase 		the target base to convert to
	 * @return 	{Array} 					result map for conversions
	 */
	const base_asciihex_convert = function(source, sourceBase, targetBase) {
		//--
		// based on idea by: https://github.com/tuupola/base62 # License MIT
		//--
		let result = [];
		let count;
		while(count = source.length) {
			let quotient = [];
			let remainder = 0;
			for(let i = 0; i !== count; i++) {
				let accumulator = source[i] + remainder * sourceBase;
				let digit = (accumulator - (accumulator % targetBase)) / targetBase;
				remainder = accumulator % targetBase;
				if(quotient.length || digit) {
					quotient.push(digit);
				} //end if
			} //end for
			result.unshift(remainder);
			source = quotient;
		} //end while
		//--
		return result;
		//--
	}; //END
	// no export

}}; //END CLASS

smartJ$BaseEncode.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$BaseEncode = smartJ$BaseEncode; // global export
} //end if

//=======================================
// CLASS :: Hash Crypto
//=======================================

/**
 * CLASS :: Smart CryptoHash (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$TestCrypto
 *
 * @throws 			smartJ$TestCrypto.raiseError
 *
 * @desc Crypto Hash for JavaScript: CRC32B / MD5 / SHA1 / SHA256 / SHA512 :: (Hex / B64)
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$CryptoHash
 * @static
 * @frozen
 *
 */
const smartJ$CryptoHash = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$CryptoHash';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const _Utils$ = smartJ$Utils;
	const _Ba$e64 = smartJ$Base64;

	const _Test$ = smartJ$TestCrypto;

	//== PRIVATES CRYPTO

	let testCryptoHash_hex_CRC32B = false;
	let testCryptoHash_b36_CRC32B = false;

	let testCryptoHash_hex_MD5 = false;
	let testCryptoHash_b64_MD5 = false;

	let testCryptoHash_hex_SHA1 = false;
	let testCryptoHash_b64_SHA1 = false;

	let testCryptoHash_hex_SHA256 = false;
	let testCryptoHash_b64_SHA256 = false;

	let testCryptoHash_hex_SHA512 = false;
	let testCryptoHash_b64_SHA512 = false;

//	const listHexaLower = '0123456789abcdef';

	// PRIVATE :: CRYPTO :: the method acts as a setting for chrsz
	const crypt_chrsz = () => {
		return 8;  // bits per input character. 8 - ASCII; 16 - Unicode (sync with the other methods)
	}; //END

	// PRIVATE :: CRYPTO :: Add integers, wrapping at 2^32. It uses 16-bit operations internally to work around bugs in some JS interpreters.
	const crypt_safe_add = function(x, y) {
		let lsw = (x & 0xFFFF) + (y & 0xFFFF);
		let msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		return (msw << 16) | (lsw & 0xFFFF);
	}; //END

	// PRIVATE :: CRYPTO :: Bitwise rotate a 32-bit number to the left.
	const crypt_bit_rol = (num, cnt) => {
		return (num << cnt) | (num >>> (32 - cnt));
	}; //END

	// PRIVATE :: CRYPTO :: Convert a string to an Array of little-endian words. If chrsz is ASCII, characters >255 have their hi-byte silently ignored.
	const crypt_str2binl = function(str) {
		let bin = Array();
		let mask = (1 << crypt_chrsz()) - 1;
		for(let i = 0; i < str.length * crypt_chrsz(); i += crypt_chrsz()) {
			bin[i>>5] |= (str.charCodeAt(i / crypt_chrsz()) & mask) << (i%32);
		} //end for
		return bin;
	}; //END

	// PRIVATE :: CRYPTO :: Convert an Array of little-endian words to a string
	const crypt_binl2str = function(bin) {
		let str = '';
		let mask = (1 << crypt_chrsz()) - 1;
		for(let i = 0; i < bin.length * 32; i += crypt_chrsz()) {
			str += String.fromCharCode((bin[i>>5] >>> (i % 32)) & mask);
		} //end for
		return str;
	}; //END

	/*
	// PRIVATE :: CRYPTO :: Convert an Array of little-endian words to a hex string.
	const crypt_binl2hex = function(binarray) {
		const hex_tab = listHexaLower;
		let str = '';
		for(let i = 0; i < binarray.length * 4; i++) {
			str += hex_tab.charAt((binarray[i>>2] >> ((i%4)*8+4)) & 0xF) + hex_tab.charAt((binarray[i>>2] >> ((i%4)*8)) & 0xF);
		} //end for
		return str;
	}; //END
	*/

	// PRIVATE :: CRYPTO :: Convert an 8-bit or 16-bit string to an Array of big-endian words
	const crypt_str2binb = function(str) {
		const mask = (1 << crypt_chrsz()) - 1;
		let bin = Array();
		for(let i = 0; i < str.length * crypt_chrsz(); i += crypt_chrsz()) {
			bin[i>>5] |= (str.charCodeAt(i / crypt_chrsz()) & mask) << (32 - crypt_chrsz() - i%32);
		} //end for
		return bin;
	}; //END

	// PRIVATE :: CRYPTO :: Convert an Array of big-endian words to a string
	const crypt_binb2str = function(bin) {
		const mask = (1 << crypt_chrsz()) - 1;
		let str = '';
		for(let i = 0; i < bin.length * 32; i += crypt_chrsz()) {
			str += String.fromCharCode((bin[i>>5] >>> (32 - crypt_chrsz() - i%32)) & mask);
		} //end for
		return str;
	}; //END

	/*
	// PRIVATE :: CRYPTO :: Convert an Array of big-endian words to a hex string.
	const crypt_binb2hex = function(binarray) {
		const hex_tab = listHexaLower;
		let str = '';
		for(let i = 0; i < binarray.length * 4; i++) {
			str += hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8+4)) & 0xF) + hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8)) & 0xF);
		} //end for
		return str;
	}; //END
	*/

	// PRIVATE :: CRYPTO :: Convert a String to Array.
	const crypt_strtoarr = function(str) {
		const l = str.length;
		let bytes = new Array(l);
		for(let i=0; i<l; i++) {
			bytes[i] = str.charCodeAt(i);
		} //end for
		return bytes;
	}; //END

	//== PUBLIC CRC32B

	/**
	 * Returns the CRC32B hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method crc32b
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The string
	 * @param {Boolean} b36 If set to TRUE will use Base36 Encoding instead of Hex Encoding
	 * @return {String} The CRC32B hash of the string
	 */
	const crc32b = function(s, b36=false) {
		//--
		if(b36 === true) {
			//--
			if(testCryptoHash_b36_CRC32B !== true) { // run test
				if(crc32b_b36(_Test$.unicodeString()) == _Test$.testCRC32B(true)) {
					testCryptoHash_b36_CRC32B = true; // test passed
				} else { // test failed
					_Test$.raiseError('CRC32B/Hash/B36');
					return '';
				} //end if else
			} //end if
			//--
			return String(crc32b_b36(s));
			//--
		} else {
			//--
			if(testCryptoHash_hex_CRC32B !== true) { // run test
				if(crc32b_hex(_Test$.unicodeString()) == _Test$.testCRC32B()) {
					testCryptoHash_hex_CRC32B = true; // test passed
				} else { // test failed
					_Test$.raiseError('CRC32B/Hash/Hex');
					return '';
				} //end if else
			} //end if
			//--
			return String(crc32b_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.crc32b = crc32b;

	//== PRIVATES CRC32

	let CRC32B_TABLE = null;
	const crc32b_init_tbl = function() {
		if(CRC32B_TABLE !== null) {
			return;
		} //end if
		CRC32B_TABLE = new Array(256);
		let i = 0, c = 0, b = 0;
		for(i=0; i<256; i++) {
			c = i;
			b = 8;
			while(b--) {
				c = (c >>> 1) ^ ((c & 1) ? 0xEDB88320 : 0);
			} //end while
			CRC32B_TABLE[i] = c;
		} //end for
	}; //END

	const crc32b_hex = function(s) {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
		let crc = String(crc32b_core(s).toString(16)); // hex
		//-- unixman fix (pad with leading zeroes)
		const padding = 8 - (crc.length);
		if(padding > 0) {
			for(let i=0; i<padding; i++) {
				crc = '0' + crc;
			} //end for
		} //end if
		//--
		return String(crc);
	}; //END

	const crc32b_b36 = function(s) {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
		let crc = String(crc32b_core(s).toString(36)); // b36
		//-- unixman fix (pad with leading zeroes)
		const padding = 7 - (crc.length);
		if(padding > 0) {
			for(let i=0; i<padding; i++) {
				crc = '0' + crc;
			} //end for
		} //end if
		//--
		return String(crc);
	}; //END

	const crc32b_core = function(s) {
		//--
		let values = crypt_strtoarr(s),
			crc = -1,
			i = 0,
			l = values.length,
			isObjects = (typeof(values[0]) === 'object'),
			id = 0;
		crc32b_init_tbl();
		for(i=0; i<l; i++) {
			id = isObjects ? (values[i].id >>> 0) : values[i];
			crc = CRC32B_TABLE[(crc ^ id) & 0xFF] ^ (crc >>> 8);
		} //end for
		crc = (~crc >>> 0); // bitflip then cast to 32-bit unsigned
		//--
		return crc;
		//--
	}; //END

	//== PRIVATES MD5

	// PRIVATE :: MD5 :: encode (hex)
	const md5_hex = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
	//	return crypt_binl2hex(md5_core(crypt_str2binl(s), s.length * crypt_chrsz()));
		return _Utils$.bin2hex(crypt_binl2str(md5_core(crypt_str2binl(s), s.length * crypt_chrsz())), true);
	}; //END

	// PRIVATE :: MD5 :: encode (b64)
	const md5_b64 = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
		return _Ba$e64.encode(crypt_binl2str(md5_core(crypt_str2binl(s), s.length * crypt_chrsz())), true);
	}; //END

	// PRIVATE :: MD5 :: basic operation 0 for the md5 algorithm
	const md5_cmn = (q, a, b, x, s, t) => {
		return crypt_safe_add(crypt_bit_rol(crypt_safe_add(crypt_safe_add(a, q), crypt_safe_add(x, t)), s),b);
	}; //END

	// PRIVATE :: MD5 :: basic operation 1 for the md5 algorithm
	const md5_ff = (a, b, c, d, x, s, t) => {
		return md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
	}; //END

	// PRIVATE :: MD5 :: basic operation 2 for the md5 algorithm
	const md5_gg = (a, b, c, d, x, s, t) => {
		return md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
	}; //END

	// PRIVATE :: MD5 :: basic operation 3 for the md5 algorithm
	const md5_hh = (a, b, c, d, x, s, t) => {
		return md5_cmn(b ^ c ^ d, a, b, x, s, t);
	}; //END

	// PRIVATE :: MD5 :: basic operation 4 for the md5 algorithm
	const md5_ii = (a, b, c, d, x, s, t) => {
		return md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
	}; //END

	// PRIVATE :: MD5 :: core
	const md5_core = function(x, len) {
		//-- append padding
		x[len >> 5] |= 0x80 << ((len) % 32);
		x[(((len + 64) >>> 9) << 4) + 14] = len;
		//--
		let a =  1732584193;
		let b = -271733879;
		let c = -1732584194;
		let d =  271733878;
		//--
		for(let i = 0; i < x.length; i += 16) {
			//--
			let olda = a;
			let oldb = b;
			let oldc = c;
			let oldd = d;
			//--
			a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
			d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
			c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
			b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
			a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
			d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
			c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
			b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
			a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
			d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
			c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
			b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
			a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
			d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
			c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
			b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);
			//--
			a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
			d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
			c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
			b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
			a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
			d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
			c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
			b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
			a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
			d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
			c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
			b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
			a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
			d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
			c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
			b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);
			//--
			a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
			d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
			c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
			b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
			a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
			d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
			c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
			b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
			a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
			d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
			c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
			b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
			a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
			d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
			c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
			b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);
			//--
			a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
			d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
			c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
			b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
			a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
			d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
			c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
			b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
			a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
			d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
			c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
			b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
			a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
			d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
			c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
			b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);
			//--
			a = crypt_safe_add(a, olda);
			b = crypt_safe_add(b, oldb);
			c = crypt_safe_add(c, oldc);
			d = crypt_safe_add(d, oldd);
			//--
		} //end for
		//--
		return Array(a, b, c, d);
		//--
	}; //END

	//== PUBLIC MD5

	/**
	 * Returns the MD5 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method md5
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The MD5 hash of the string
	 */
	const md5 = function(s, b64=false) {
		//--
		if(b64 === true) {
			//--
			if(testCryptoHash_b64_MD5 !== true) { // run test
				if(md5_b64(_Test$.unicodeString()) == _Test$.testMD5(true)) {
					testCryptoHash_b64_MD5 = true; // test passed
				} else { // test failed
					_Test$.raiseError('MD5/Hash/B64');
					return '';
				} //end if else
			} //end if
			//--
			return String(md5_b64(s));
			//--
		} else {
			//--
			if(testCryptoHash_hex_MD5 !== true) { // run test
				if(md5_hex(_Test$.unicodeString()) == _Test$.testMD5()) {
					testCryptoHash_hex_MD5 = true; // test passed
				} else { // test failed
					_Test$.raiseError('MD5/Hash/Hex');
					return '';
				} //end if else
			} //end if
			//--
			return String(md5_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.md5 = md5; // export

	//== PRIVATES SHA1

	// PRIVATE :: SHA1 :: encrypt (hex)
	const sha1_hex = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
	//	return crypt_binb2hex(sha1_core(crypt_str2binb(s), s.length * crypt_chrsz()));
		return _Utils$.bin2hex(crypt_binb2str(sha1_core(crypt_str2binb(s), s.length * crypt_chrsz())), true);
	}; //END

	// PRIVATE :: SHA1 :: encrypt (b64)
	const sha1_b64 = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
		return _Ba$e64.encode(crypt_binb2str(sha1_core(crypt_str2binb(s), s.length * crypt_chrsz())), true);
	}; //END

	// PRIVATE :: SHA1 :: basic operation 0 for the sha1 algorithm
	const sha1_ft = (t, b, c, d) => {
		if(t < 20) {
			return (b & c) | ((~b) & d);
		} //end if
		if(t < 40) {
			return b ^ c ^ d;
		} //end if
		if(t < 60) {
			return (b & c) | (b & d) | (c & d);
		} //end if
		return b ^ c ^ d;
	}; //END

	// PRIVATE :: SHA1 :: basic operation 1 for the sha1 algorithm
	const sha1_kt = (t) => {
	  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 : (t < 60) ? -1894007588 : -899497514 ;
	}; //END

	// PRIVATE :: SHA1 :: core
	const sha1_core = function(x, len) {
		//-- append padding
		x[len >> 5] |= 0x80 << (24 - len % 32);
		x[((len + 64 >> 9) << 4) + 15] = len;
		//--
		let a =  1732584193;
		let b = -271733879;
		let c = -1732584194;
		let d =  271733878;
		let e = -1009589776;
		//--
		let w = Array(80);
		//--
		for(let i = 0; i < x.length; i += 16) {
			//--
			let olda = a;
			let oldb = b;
			let oldc = c;
			let oldd = d;
			let olde = e;
			//--
			for(let j = 0; j < 80; j++) {
				if(j < 16) {
					w[j] = x[i + j];
				} else {
					w[j] = crypt_bit_rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
				} //end if else
				//--
				let t = crypt_safe_add(crypt_safe_add(crypt_bit_rol(a, 5), sha1_ft(j, b, c, d)), crypt_safe_add(crypt_safe_add(e, w[j]), sha1_kt(j)));
				//--
				e = d;
				d = c;
				c = crypt_bit_rol(b, 30);
				b = a;
				a = t;
				//--
			} //end for
			//--
			a = crypt_safe_add(a, olda);
			b = crypt_safe_add(b, oldb);
			c = crypt_safe_add(c, oldc);
			d = crypt_safe_add(d, oldd);
			e = crypt_safe_add(e, olde);
		} //end for
		//--
		return Array(a, b, c, d, e);
		//--
	}; //END

	//== PUBLIC SHA1

	/**
	 * Returns the SHA1 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha1
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA1 hash of the string
	 */
	const sha1 = function(s, b64=false) {
		//--
		if(b64 === true) {
			//--
			if(testCryptoHash_b64_SHA1 !== true) { // run test
				if(sha1_b64(_Test$.unicodeString()) == _Test$.testSHA1(true)) {
					testCryptoHash_b64_SHA1 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA1/Hash/B64');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha1_b64(s));
			//--
		} else {
			//--
			if(testCryptoHash_hex_SHA1 !== true) { // run test
				if(sha1_hex(_Test$.unicodeString()) == _Test$.testSHA1()) {
					testCryptoHash_hex_SHA1 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA1/Hash/Hex');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha1_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.sha1 = sha1; // export

	//== PRIVATES SHA512

	// Secure Hash Algorithm (SHA512), based on http://www.happycode.info

	// PRIVATE :: SHA512 :: encrypt (hex)
	const sha512_hex = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
	//	return crypt_binb2hex(sha512_core(s));
		return _Utils$.bin2hex(crypt_binb2str(sha512_core(s)), true);
	}; //END

	// PRIVATE :: SHA512 :: encrypt (b64)
	const sha512_b64 = (s) => {
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8_encode(s); // make it unicode
		return _Ba$e64.encode(crypt_binb2str(sha512_core(s)), true);
	}; //END

	// PRIVATE :: SHA512
	const sha512_int_64 = function(msint_32, lsint_32) { // it is function constructor !
		const _M$ = this; // self referencing
		_M$.highOrder = msint_32;
		_M$.lowOrder = lsint_32;
	}; //END

	// PRIVATE :: SHA512
	const sha512_safeadd_2 = function(x, y) {
		//--
		let lsw, msw, lowOrder, highOrder;
		//--
		lsw = (x.lowOrder & 0xFFFF) + (y.lowOrder & 0xFFFF);
		msw = (x.lowOrder >>> 16) + (y.lowOrder >>> 16) + (lsw >>> 16);
		lowOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		lsw = (x.highOrder & 0xFFFF) + (y.highOrder & 0xFFFF) + (msw >>> 16);
		msw = (x.highOrder >>> 16) + (y.highOrder >>> 16) + (lsw >>> 16);
		highOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		return new sha512_int_64(highOrder, lowOrder);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_safeadd_4 = function(a, b, c, d) {
		//--
		let lsw, msw, lowOrder, highOrder;
		//--
		lsw = (a.lowOrder & 0xFFFF) + (b.lowOrder & 0xFFFF) + (c.lowOrder & 0xFFFF) + (d.lowOrder & 0xFFFF);
		msw = (a.lowOrder >>> 16) + (b.lowOrder >>> 16) + (c.lowOrder >>> 16) + (d.lowOrder >>> 16) + (lsw >>> 16);
		lowOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		lsw = (a.highOrder & 0xFFFF) + (b.highOrder & 0xFFFF) + (c.highOrder & 0xFFFF) + (d.highOrder & 0xFFFF) + (msw >>> 16);
		msw = (a.highOrder >>> 16) + (b.highOrder >>> 16) + (c.highOrder >>> 16) + (d.highOrder >>> 16) + (lsw >>> 16);
		highOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		return new sha512_int_64(highOrder, lowOrder);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_safeadd_5 = function(a, b, c, d, e) {
		//--
		let lsw, msw, lowOrder, highOrder;
		//--
		lsw = (a.lowOrder & 0xFFFF) + (b.lowOrder & 0xFFFF) + (c.lowOrder & 0xFFFF) + (d.lowOrder & 0xFFFF) + (e.lowOrder & 0xFFFF);
		msw = (a.lowOrder >>> 16) + (b.lowOrder >>> 16) + (c.lowOrder >>> 16) + (d.lowOrder >>> 16) + (e.lowOrder >>> 16) + (lsw >>> 16);
		lowOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		lsw = (a.highOrder & 0xFFFF) + (b.highOrder & 0xFFFF) + (c.highOrder & 0xFFFF) + (d.highOrder & 0xFFFF) + (e.highOrder & 0xFFFF) + (msw >>> 16);
		msw = (a.highOrder >>> 16) + (b.highOrder >>> 16) + (c.highOrder >>> 16) + (d.highOrder >>> 16) + (e.highOrder >>> 16) + (lsw >>> 16);
		highOrder = ((msw & 0xFFFF) << 16) | (lsw & 0xFFFF);
		//--
		return new sha512_int_64(highOrder, lowOrder);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_maj = (x, y, z) => {
		//--
		return new sha512_int_64(
			(x.highOrder & y.highOrder) ^ (x.highOrder & z.highOrder) ^ (y.highOrder & z.highOrder),
			(x.lowOrder & y.lowOrder) ^ (x.lowOrder & z.lowOrder) ^ (y.lowOrder & z.lowOrder)
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_ch = (x, y, z) => {
		//--
		return new sha512_int_64(
			(x.highOrder & y.highOrder) ^ (~x.highOrder & z.highOrder),
			(x.lowOrder & y.lowOrder) ^ (~x.lowOrder & z.lowOrder)
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_rot_r = (x, n) => {
		//--
		if(n <= 32) {
			return new sha512_int_64(
				(x.highOrder >>> n) | (x.lowOrder << (32 - n)),
				(x.lowOrder >>> n) | (x.highOrder << (32 - n))
			);
		} else {
			return new sha512_int_64(
				(x.lowOrder >>> n) | (x.highOrder << (32 - n)),
				(x.highOrder >>> n) | (x.lowOrder << (32 - n))
			);
		} //end if else
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_sh_r = (x, n) => {
		//--
		if(n <= 32) {
			return new sha512_int_64(
				x.highOrder >>> n,
				x.lowOrder >>> n | (x.highOrder << (32 - n))
			);
		} else {
			return new sha512_int_64(
				0,
				x.highOrder << (32 - n)
			);
		} //end if else
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_sigma_0 = function(x) {
		//--
		let rotr28 = sha512_rot_r(x, 28);
		let rotr34 = sha512_rot_r(x, 34);
		let rotr39 = sha512_rot_r(x, 39);
		//--
		return new sha512_int_64(
			rotr28.highOrder ^ rotr34.highOrder ^ rotr39.highOrder,
			rotr28.lowOrder ^ rotr34.lowOrder ^ rotr39.lowOrder
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_sigma_1 = function(x) {
		//--
		let rotr14 = sha512_rot_r(x, 14);
		let rotr18 = sha512_rot_r(x, 18);
		let rotr41 = sha512_rot_r(x, 41);
		//--
		return new sha512_int_64(
			rotr14.highOrder ^ rotr18.highOrder ^ rotr41.highOrder,
			rotr14.lowOrder ^ rotr18.lowOrder ^ rotr41.lowOrder
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_gamma_0 = function(x) {
		//--
		let rotr1 = sha512_rot_r(x, 1), rotr8 = sha512_rot_r(x, 8), shr7 = sha512_sh_r(x, 7);
		//--
		return new sha512_int_64(
			rotr1.highOrder ^ rotr8.highOrder ^ shr7.highOrder,
			rotr1.lowOrder ^ rotr8.lowOrder ^ shr7.lowOrder
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_gamma_1 = function(x) {
		//--
		let rotr19 = sha512_rot_r(x, 19);
		let rotr61 = sha512_rot_r(x, 61);
		let shr6 = sha512_sh_r(x, 6);
		//--
		return new sha512_int_64(
			rotr19.highOrder ^ rotr61.highOrder ^ shr6.highOrder,
			rotr19.lowOrder ^ rotr61.lowOrder ^ shr6.lowOrder
		);
		//--
	}; //END

	// PRIVATE :: SHA512
	const sha512_core = function(str) {
		//--
		let H = [
				new sha512_int_64(0x6a09e667, 0xf3bcc908), new sha512_int_64(0xbb67ae85, 0x84caa73b),
				new sha512_int_64(0x3c6ef372, 0xfe94f82b), new sha512_int_64(0xa54ff53a, 0x5f1d36f1),
				new sha512_int_64(0x510e527f, 0xade682d1), new sha512_int_64(0x9b05688c, 0x2b3e6c1f),
				new sha512_int_64(0x1f83d9ab, 0xfb41bd6b), new sha512_int_64(0x5be0cd19, 0x137e2179)
			];
		//--
		let K = [
				new sha512_int_64(0x428a2f98, 0xd728ae22), new sha512_int_64(0x71374491, 0x23ef65cd),
				new sha512_int_64(0xb5c0fbcf, 0xec4d3b2f), new sha512_int_64(0xe9b5dba5, 0x8189dbbc),
				new sha512_int_64(0x3956c25b, 0xf348b538), new sha512_int_64(0x59f111f1, 0xb605d019),
				new sha512_int_64(0x923f82a4, 0xaf194f9b), new sha512_int_64(0xab1c5ed5, 0xda6d8118),
				new sha512_int_64(0xd807aa98, 0xa3030242), new sha512_int_64(0x12835b01, 0x45706fbe),
				new sha512_int_64(0x243185be, 0x4ee4b28c), new sha512_int_64(0x550c7dc3, 0xd5ffb4e2),
				new sha512_int_64(0x72be5d74, 0xf27b896f), new sha512_int_64(0x80deb1fe, 0x3b1696b1),
				new sha512_int_64(0x9bdc06a7, 0x25c71235), new sha512_int_64(0xc19bf174, 0xcf692694),
				new sha512_int_64(0xe49b69c1, 0x9ef14ad2), new sha512_int_64(0xefbe4786, 0x384f25e3),
				new sha512_int_64(0x0fc19dc6, 0x8b8cd5b5), new sha512_int_64(0x240ca1cc, 0x77ac9c65),
				new sha512_int_64(0x2de92c6f, 0x592b0275), new sha512_int_64(0x4a7484aa, 0x6ea6e483),
				new sha512_int_64(0x5cb0a9dc, 0xbd41fbd4), new sha512_int_64(0x76f988da, 0x831153b5),
				new sha512_int_64(0x983e5152, 0xee66dfab), new sha512_int_64(0xa831c66d, 0x2db43210),
				new sha512_int_64(0xb00327c8, 0x98fb213f), new sha512_int_64(0xbf597fc7, 0xbeef0ee4),
				new sha512_int_64(0xc6e00bf3, 0x3da88fc2), new sha512_int_64(0xd5a79147, 0x930aa725),
				new sha512_int_64(0x06ca6351, 0xe003826f), new sha512_int_64(0x14292967, 0x0a0e6e70),
				new sha512_int_64(0x27b70a85, 0x46d22ffc), new sha512_int_64(0x2e1b2138, 0x5c26c926),
				new sha512_int_64(0x4d2c6dfc, 0x5ac42aed), new sha512_int_64(0x53380d13, 0x9d95b3df),
				new sha512_int_64(0x650a7354, 0x8baf63de), new sha512_int_64(0x766a0abb, 0x3c77b2a8),
				new sha512_int_64(0x81c2c92e, 0x47edaee6), new sha512_int_64(0x92722c85, 0x1482353b),
				new sha512_int_64(0xa2bfe8a1, 0x4cf10364), new sha512_int_64(0xa81a664b, 0xbc423001),
				new sha512_int_64(0xc24b8b70, 0xd0f89791), new sha512_int_64(0xc76c51a3, 0x0654be30),
				new sha512_int_64(0xd192e819, 0xd6ef5218), new sha512_int_64(0xd6990624, 0x5565a910),
				new sha512_int_64(0xf40e3585, 0x5771202a), new sha512_int_64(0x106aa070, 0x32bbd1b8),
				new sha512_int_64(0x19a4c116, 0xb8d2d0c8), new sha512_int_64(0x1e376c08, 0x5141ab53),
				new sha512_int_64(0x2748774c, 0xdf8eeb99), new sha512_int_64(0x34b0bcb5, 0xe19b48a8),
				new sha512_int_64(0x391c0cb3, 0xc5c95a63), new sha512_int_64(0x4ed8aa4a, 0xe3418acb),
				new sha512_int_64(0x5b9cca4f, 0x7763e373), new sha512_int_64(0x682e6ff3, 0xd6b2b8a3),
				new sha512_int_64(0x748f82ee, 0x5defb2fc), new sha512_int_64(0x78a5636f, 0x43172f60),
				new sha512_int_64(0x84c87814, 0xa1f0ab72), new sha512_int_64(0x8cc70208, 0x1a6439ec),
				new sha512_int_64(0x90befffa, 0x23631e28), new sha512_int_64(0xa4506ceb, 0xde82bde9),
				new sha512_int_64(0xbef9a3f7, 0xb2c67915), new sha512_int_64(0xc67178f2, 0xe372532b),
				new sha512_int_64(0xca273ece, 0xea26619c), new sha512_int_64(0xd186b8c7, 0x21c0c207),
				new sha512_int_64(0xeada7dd6, 0xcde0eb1e), new sha512_int_64(0xf57d4f7f, 0xee6ed178),
				new sha512_int_64(0x06f067aa, 0x72176fba), new sha512_int_64(0x0a637dc5, 0xa2c898a6),
				new sha512_int_64(0x113f9804, 0xbef90dae), new sha512_int_64(0x1b710b35, 0x131c471b),
				new sha512_int_64(0x28db77f5, 0x23047d84), new sha512_int_64(0x32caab7b, 0x40c72493),
				new sha512_int_64(0x3c9ebe0a, 0x15c9bebc), new sha512_int_64(0x431d67c4, 0x9c100d4c),
				new sha512_int_64(0x4cc5d4be, 0xcb3e42b6), new sha512_int_64(0x597f299c, 0xfc657e2a),
				new sha512_int_64(0x5fcb6fab, 0x3ad6faec), new sha512_int_64(0x6c44198c, 0x4a475817)
			];
		//--
		let W = new Array(64);
		let a, b, c, d, e, f, g, h, i, j;
		let T1, T2;
		//--
		const strlen = str.length * crypt_chrsz();
		str = crypt_str2binb(str);
		//--
		str[strlen >> 5] |= 0x80 << (24 - strlen % 32);
		str[(((strlen + 128) >> 10) << 5) + 31] = strlen;
		//--
		for(let i = 0; i < str.length; i += 32) {
			//--
			a = H[0];
			b = H[1];
			c = H[2];
			d = H[3];
			e = H[4];
			f = H[5];
			g = H[6];
			h = H[7];
			//--
			for(let j = 0; j < 80; j++) {
				//--
				if(j < 16) {
					W[j] = new sha512_int_64(str[j*2 + i], str[j*2 + i + 1]);
				} else {
					W[j] = sha512_safeadd_4(sha512_gamma_1(W[j - 2]), W[j - 7], sha512_gamma_0(W[j - 15]), W[j - 16]);
				} //end if else
				//--
				T1 = sha512_safeadd_5(h, sha512_sigma_1(e), sha512_ch(e, f, g), K[j], W[j]);
				T2 = sha512_safeadd_2(sha512_sigma_0(a), sha512_maj(a, b, c));
				//--
				h = g;
				g = f;
				f = e;
				e = sha512_safeadd_2(d, T1);
				d = c;
				c = b;
				b = a;
				a = sha512_safeadd_2(T1, T2);
				//--
			} //end for
			//--
			H[0] = sha512_safeadd_2(a, H[0]);
			H[1] = sha512_safeadd_2(b, H[1]);
			H[2] = sha512_safeadd_2(c, H[2]);
			H[3] = sha512_safeadd_2(d, H[3]);
			H[4] = sha512_safeadd_2(e, H[4]);
			H[5] = sha512_safeadd_2(f, H[5]);
			H[6] = sha512_safeadd_2(g, H[6]);
			H[7] = sha512_safeadd_2(h, H[7]);
			//--
		} //end for
		//--
		let binarray = [];
		//--
		for(let i = 0; i < H.length; i++) {
			binarray.push(H[i].highOrder);
			binarray.push(H[i].lowOrder);
		} //end for
		//--
		return binarray;
		//--
	}; //END

	//== PUBLIC SHA512

	/**
	 * Returns the SHA512 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha512
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA512 hash of the string
	 */
	const sha512 = function(s, b64=false) {
		//--
		if(b64 === true) {
			//--
			if(testCryptoHash_b64_SHA512 !== true) { // run test
				if(sha512_b64(_Test$.unicodeString()) == _Test$.testSHA512(true)) {
					testCryptoHash_b64_SHA512 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA512/Hash/B64');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha512_b64(s));
			//--
		} else {
			//--
			if(testCryptoHash_hex_SHA512 !== true) { // run test
				if(sha512_hex(_Test$.unicodeString()) == _Test$.testSHA512()) {
					testCryptoHash_hex_SHA512 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA512/Hash/Hex');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha512_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.sha512 = sha512; // export

	//== PRIVATES SHA256

	// PRIVATE :: SHA256 :: encrypt (hex)
	let sha256_cache_h = [];
	let sha256_cache_k = [];
	const sha256_hex = function(str) {
		//--
		// based on: https://github.com/geraintluff/sha256
		// license: Public Domain
		//--
		str = _Utils$.stringPureVal(str); // cast to string, don't trim ! need to preserve the value
		str = _Utils$.utf8_encode(str); // make it unicode
		//--
		const rightRotate = (value, amount) => (value>>>amount) | (value<<(32 - amount));
		//--
		const mathPow = Math.pow;
		const maxWord = mathPow(2, 32);
		const lengthProperty = 'length';
		const lenStr = str[lengthProperty] * 8;
		//--
		let i, j; // Used as a counter across the whole file
		let result = '';
		//--
		let words = [];
		//--
		//* caching results is optional - remove/add slash from front of this line to toggle
		// Initial hash value: first 32 bits of the fractional parts of the square roots of the first 8 primes
		// (we actually calculate the first 64, but extra values are just ignored)
		let hash = sha256_cache_h = sha256_cache_h || [];
		// Round constants: first 32 bits of the fractional parts of the cube roots of the first 64 primes
		let k = sha256_cache_k = sha256_cache_k || [];
		let primeCounter = k[lengthProperty];
		/*/
		let hash = [], k = [];
		let primeCounter = 0;
		//*/
		if(primeCounter <= 0) { // only run once if cached (or run each time if not)
			let isComposite = {};
			for(let candidate = 2; primeCounter < 64; candidate++) {
				if(!isComposite[candidate]) {
					for(i=0; i<313; i+=candidate) {
						isComposite[i] = candidate;
					} //end for
					hash[primeCounter] = (mathPow(candidate, 0.5) * maxWord) | 0;
					k[primeCounter++] = (mathPow(candidate, 1/3) * maxWord) | 0;
				} //end if
			} //end for
		} //end if
		//--
		str += '\x80'; // Append Ƈ' bit (plus zero padding)
		//--
		while(str[lengthProperty] % 64 - 56) {
			str += '\x00'; // More zero padding
		} //end while
		//--
		for(i=0; i<str[lengthProperty]; i++) {
			j = str.charCodeAt(i);
			if(j>>8) {
				return ''; // ASCII check: only accept characters in range 0-255
			} //end if
			words[i>>2] |= j << ((3 - i) % 4) * 8;
		} //end for
		//--
		words[words[lengthProperty]] = ((lenStr / maxWord) | 0);
		words[words[lengthProperty]] = (lenStr);
		//-- process each chunk
		for(j=0; j<words[lengthProperty];) {
			//--
			let w = words.slice(j, j += 16); // The message is expanded into 64 words as part of the iteration
			let oldHash = hash;
			//-- This is now the undefinedworking hash", often labelled as variables a...g (we have to truncate as well, otherwise extra entries at the end accumulate
			hash = hash.slice(0, 8);
			for(i=0; i<64; i++) {
				let i2 = i + j;
				//-- Expand the message into 64 words ; Used below if
				let w15 = w[i - 15], w2 = w[i - 2];
				//-- Iterate
				let a = hash[0], e = hash[4];
				let temp1 = hash[7] + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25)) // S1
					+ ((e&hash[5])^((~e)&hash[6])) // ch
					+ k[i]
					+ (w[i] = (i < 16) ? w[i] : ( // Expand the message schedule if needed
							w[i - 16]
							+ (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15>>>3)) // s0
							+ w[i - 7]
							+ (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2>>>10)) // s1
						) | 0
					);
				//-- This is only used once, so *could* be moved below, but it only saves 4 bytes and makes things unreadble
				let temp2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22)) // S0
					+ ((a&hash[1])^(a&hash[2])^(hash[1]&hash[2])); // maj
				//--
				hash = [(temp1 + temp2) | 0].concat(hash); // We don't bother trimming off the extra ones, they're harmless as long as we're truncating when we do the slice()
				hash[4] = (hash[4] + temp1) | 0;
				//--
			} //end for
			//--
			for(i=0; i<8; i++) {
				hash[i] = (hash[i] + oldHash[i]) | 0;
			} //end for
			//--
		} //end for
		//--
		for(i=0; i<8; i++) {
			for(j=3; j+1; j--) {
				let b = (hash[i]>>(j*8))&255;
				result += ((b < 16) ? 0 : '') + b.toString(16);
			} //end for
		} //end for
		//--
		return String(result || '');
		//--
	}; // END

	// PRIVATE :: SHA256 :: encrypt (b64)
	const sha256_b64 = (s) => _Ba$e64.encode(_Utils$.hex2bin(sha256_hex(s), true), true); //END

	//== PUBLIC SHA256

	/**
	 * Returns the SHA256 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha256
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} s The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA256 hash of the string
	 */
	const sha256 = function(s, b64=false) {
		//--
		if(b64 === true) {
			//--
			if(testCryptoHash_b64_SHA256 !== true) { // run test
				if(sha256_b64(_Test$.unicodeString()) == _Test$.testSHA256(true)) {
					testCryptoHash_b64_SHA256 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA256/Hash/B64');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha256_b64(s));
			//--
		} else {
			//--
			if(testCryptoHash_hex_SHA256 !== true) { // run test
				if(sha256_hex(_Test$.unicodeString()) == _Test$.testSHA256()) {
					testCryptoHash_hex_SHA256 = true; // test passed
				} else { // test failed
					_Test$.raiseError('SHA256/Hash/Hex');
					return '';
				} //end if else
			} //end if
			//--
			return String(sha256_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.sha256 = sha256; // export

}}; //END CLASS

smartJ$CryptoHash.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CryptoHash = smartJ$CryptoHash; // global export
} //end if

//=======================================
// CLASS :: Crypto Blowfish CBC
//=======================================

/*
 * Blowfish.js from Dojo Toolkit 1.8.1
 * License: New BSD License
 * Cut of by Sladex (xslade@gmail.com)
 * Based on the C# implementation by Marcus Hahn (http://www.hotpixel.net/)
 * Unsigned math based on Paul Johnstone and Peter Wood patches.
 * 2005-12-08
 */
// NOTICE: Max Key for Blowfish is up to 56 chars length (56 bytes = 448 bits)
// Modified by unixman: port to ES6, improved encoding handler by using double base64 compression to preserve also UTF-8 characters, implementing safe key derivation, ...
/**
 * CLASS :: Smart CryptoBlowfish (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$Base64
 * @requires		smartJ$BaseEncode
 * @requires		smartJ$CryptoHash
 *
 * @desc Blowfish (CBC) for JavaScript: Encrypt / Decrypt
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$CryptoBlowfish
 * @static
 * @frozen
 *
 */
smartJ$CryptoBlowfish = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$CryptoBlowfish';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const _Utils$ = smartJ$Utils;
	const _Ba$e64 = smartJ$Base64;
	const _Ba$eConv = smartJ$BaseEncode;
	const _Crypto$Hash = smartJ$CryptoHash;

	//== PRIVATE BLOWFISH

	// Cipher Modes: ECB:0, CBC:1, PCBC:2, CFB:3, OFB:4, CTR:5
	const cipherModes = {
		CBC: 1 // other modes are not supported by this implementation ; CBC mode is the only one compatible with the PHP Crypto Api ...
	};

	// Objects for processing Blowfish encryption/decryption
	const POW2 = Math.pow(2,2);
	const POW3 = Math.pow(2,3);
	const POW4 = Math.pow(2,4);
	const POW8 = Math.pow(2,8);
	const POW16 = Math.pow(2,16);
	const POW24 = Math.pow(2,24);

	// CBC mode initialization vector
	let iv = null;
	let IV = null;

	// Blowfish Data Object
	const boxes = {
		p:[
			0x243f6a88, 0x85a308d3, 0x13198a2e, 0x03707344, 0xa4093822, 0x299f31d0, 0x082efa98, 0xec4e6c89,
			0x452821e6, 0x38d01377, 0xbe5466cf, 0x34e90c6c, 0xc0ac29b7, 0xc97c50dd, 0x3f84d5b5, 0xb5470917,
			0x9216d5d9, 0x8979fb1b
		],
		s0:[
			0xd1310ba6, 0x98dfb5ac, 0x2ffd72db, 0xd01adfb7, 0xb8e1afed, 0x6a267e96, 0xba7c9045, 0xf12c7f99,
			0x24a19947, 0xb3916cf7, 0x0801f2e2, 0x858efc16, 0x636920d8, 0x71574e69, 0xa458fea3, 0xf4933d7e,
			0x0d95748f, 0x728eb658, 0x718bcd58, 0x82154aee, 0x7b54a41d, 0xc25a59b5, 0x9c30d539, 0x2af26013,
			0xc5d1b023, 0x286085f0, 0xca417918, 0xb8db38ef, 0x8e79dcb0, 0x603a180e, 0x6c9e0e8b, 0xb01e8a3e,
			0xd71577c1, 0xbd314b27, 0x78af2fda, 0x55605c60, 0xe65525f3, 0xaa55ab94, 0x57489862, 0x63e81440,
			0x55ca396a, 0x2aab10b6, 0xb4cc5c34, 0x1141e8ce, 0xa15486af, 0x7c72e993, 0xb3ee1411, 0x636fbc2a,
			0x2ba9c55d, 0x741831f6, 0xce5c3e16, 0x9b87931e, 0xafd6ba33, 0x6c24cf5c, 0x7a325381, 0x28958677,
			0x3b8f4898, 0x6b4bb9af, 0xc4bfe81b, 0x66282193, 0x61d809cc, 0xfb21a991, 0x487cac60, 0x5dec8032,
			0xef845d5d, 0xe98575b1, 0xdc262302, 0xeb651b88, 0x23893e81, 0xd396acc5, 0x0f6d6ff3, 0x83f44239,
			0x2e0b4482, 0xa4842004, 0x69c8f04a, 0x9e1f9b5e, 0x21c66842, 0xf6e96c9a, 0x670c9c61, 0xabd388f0,
			0x6a51a0d2, 0xd8542f68, 0x960fa728, 0xab5133a3, 0x6eef0b6c, 0x137a3be4, 0xba3bf050, 0x7efb2a98,
			0xa1f1651d, 0x39af0176, 0x66ca593e, 0x82430e88, 0x8cee8619, 0x456f9fb4, 0x7d84a5c3, 0x3b8b5ebe,
			0xe06f75d8, 0x85c12073, 0x401a449f, 0x56c16aa6, 0x4ed3aa62, 0x363f7706, 0x1bfedf72, 0x429b023d,
			0x37d0d724, 0xd00a1248, 0xdb0fead3, 0x49f1c09b, 0x075372c9, 0x80991b7b, 0x25d479d8, 0xf6e8def7,
			0xe3fe501a, 0xb6794c3b, 0x976ce0bd, 0x04c006ba, 0xc1a94fb6, 0x409f60c4, 0x5e5c9ec2, 0x196a2463,
			0x68fb6faf, 0x3e6c53b5, 0x1339b2eb, 0x3b52ec6f, 0x6dfc511f, 0x9b30952c, 0xcc814544, 0xaf5ebd09,
			0xbee3d004, 0xde334afd, 0x660f2807, 0x192e4bb3, 0xc0cba857, 0x45c8740f, 0xd20b5f39, 0xb9d3fbdb,
			0x5579c0bd, 0x1a60320a, 0xd6a100c6, 0x402c7279, 0x679f25fe, 0xfb1fa3cc, 0x8ea5e9f8, 0xdb3222f8,
			0x3c7516df, 0xfd616b15, 0x2f501ec8, 0xad0552ab, 0x323db5fa, 0xfd238760, 0x53317b48, 0x3e00df82,
			0x9e5c57bb, 0xca6f8ca0, 0x1a87562e, 0xdf1769db, 0xd542a8f6, 0x287effc3, 0xac6732c6, 0x8c4f5573,
			0x695b27b0, 0xbbca58c8, 0xe1ffa35d, 0xb8f011a0, 0x10fa3d98, 0xfd2183b8, 0x4afcb56c, 0x2dd1d35b,
			0x9a53e479, 0xb6f84565, 0xd28e49bc, 0x4bfb9790, 0xe1ddf2da, 0xa4cb7e33, 0x62fb1341, 0xcee4c6e8,
			0xef20cada, 0x36774c01, 0xd07e9efe, 0x2bf11fb4, 0x95dbda4d, 0xae909198, 0xeaad8e71, 0x6b93d5a0,
			0xd08ed1d0, 0xafc725e0, 0x8e3c5b2f, 0x8e7594b7, 0x8ff6e2fb, 0xf2122b64, 0x8888b812, 0x900df01c,
			0x4fad5ea0, 0x688fc31c, 0xd1cff191, 0xb3a8c1ad, 0x2f2f2218, 0xbe0e1777, 0xea752dfe, 0x8b021fa1,
			0xe5a0cc0f, 0xb56f74e8, 0x18acf3d6, 0xce89e299, 0xb4a84fe0, 0xfd13e0b7, 0x7cc43b81, 0xd2ada8d9,
			0x165fa266, 0x80957705, 0x93cc7314, 0x211a1477, 0xe6ad2065, 0x77b5fa86, 0xc75442f5, 0xfb9d35cf,
			0xebcdaf0c, 0x7b3e89a0, 0xd6411bd3, 0xae1e7e49, 0x00250e2d, 0x2071b35e, 0x226800bb, 0x57b8e0af,
			0x2464369b, 0xf009b91e, 0x5563911d, 0x59dfa6aa, 0x78c14389, 0xd95a537f, 0x207d5ba2, 0x02e5b9c5,
			0x83260376, 0x6295cfa9, 0x11c81968, 0x4e734a41, 0xb3472dca, 0x7b14a94a, 0x1b510052, 0x9a532915,
			0xd60f573f, 0xbc9bc6e4, 0x2b60a476, 0x81e67400, 0x08ba6fb5, 0x571be91f, 0xf296ec6b, 0x2a0dd915,
			0xb6636521, 0xe7b9f9b6, 0xff34052e, 0xc5855664, 0x53b02d5d, 0xa99f8fa1, 0x08ba4799, 0x6e85076a
		],
		s1:[
			0x4b7a70e9, 0xb5b32944, 0xdb75092e, 0xc4192623, 0xad6ea6b0, 0x49a7df7d, 0x9cee60b8, 0x8fedb266,
			0xecaa8c71, 0x699a17ff, 0x5664526c, 0xc2b19ee1, 0x193602a5, 0x75094c29, 0xa0591340, 0xe4183a3e,
			0x3f54989a, 0x5b429d65, 0x6b8fe4d6, 0x99f73fd6, 0xa1d29c07, 0xefe830f5, 0x4d2d38e6, 0xf0255dc1,
			0x4cdd2086, 0x8470eb26, 0x6382e9c6, 0x021ecc5e, 0x09686b3f, 0x3ebaefc9, 0x3c971814, 0x6b6a70a1,
			0x687f3584, 0x52a0e286, 0xb79c5305, 0xaa500737, 0x3e07841c, 0x7fdeae5c, 0x8e7d44ec, 0x5716f2b8,
			0xb03ada37, 0xf0500c0d, 0xf01c1f04, 0x0200b3ff, 0xae0cf51a, 0x3cb574b2, 0x25837a58, 0xdc0921bd,
			0xd19113f9, 0x7ca92ff6, 0x94324773, 0x22f54701, 0x3ae5e581, 0x37c2dadc, 0xc8b57634, 0x9af3dda7,
			0xa9446146, 0x0fd0030e, 0xecc8c73e, 0xa4751e41, 0xe238cd99, 0x3bea0e2f, 0x3280bba1, 0x183eb331,
			0x4e548b38, 0x4f6db908, 0x6f420d03, 0xf60a04bf, 0x2cb81290, 0x24977c79, 0x5679b072, 0xbcaf89af,
			0xde9a771f, 0xd9930810, 0xb38bae12, 0xdccf3f2e, 0x5512721f, 0x2e6b7124, 0x501adde6, 0x9f84cd87,
			0x7a584718, 0x7408da17, 0xbc9f9abc, 0xe94b7d8c, 0xec7aec3a, 0xdb851dfa, 0x63094366, 0xc464c3d2,
			0xef1c1847, 0x3215d908, 0xdd433b37, 0x24c2ba16, 0x12a14d43, 0x2a65c451, 0x50940002, 0x133ae4dd,
			0x71dff89e, 0x10314e55, 0x81ac77d6, 0x5f11199b, 0x043556f1, 0xd7a3c76b, 0x3c11183b, 0x5924a509,
			0xf28fe6ed, 0x97f1fbfa, 0x9ebabf2c, 0x1e153c6e, 0x86e34570, 0xeae96fb1, 0x860e5e0a, 0x5a3e2ab3,
			0x771fe71c, 0x4e3d06fa, 0x2965dcb9, 0x99e71d0f, 0x803e89d6, 0x5266c825, 0x2e4cc978, 0x9c10b36a,
			0xc6150eba, 0x94e2ea78, 0xa5fc3c53, 0x1e0a2df4, 0xf2f74ea7, 0x361d2b3d, 0x1939260f, 0x19c27960,
			0x5223a708, 0xf71312b6, 0xebadfe6e, 0xeac31f66, 0xe3bc4595, 0xa67bc883, 0xb17f37d1, 0x018cff28,
			0xc332ddef, 0xbe6c5aa5, 0x65582185, 0x68ab9802, 0xeecea50f, 0xdb2f953b, 0x2aef7dad, 0x5b6e2f84,
			0x1521b628, 0x29076170, 0xecdd4775, 0x619f1510, 0x13cca830, 0xeb61bd96, 0x0334fe1e, 0xaa0363cf,
			0xb5735c90, 0x4c70a239, 0xd59e9e0b, 0xcbaade14, 0xeecc86bc, 0x60622ca7, 0x9cab5cab, 0xb2f3846e,
			0x648b1eaf, 0x19bdf0ca, 0xa02369b9, 0x655abb50, 0x40685a32, 0x3c2ab4b3, 0x319ee9d5, 0xc021b8f7,
			0x9b540b19, 0x875fa099, 0x95f7997e, 0x623d7da8, 0xf837889a, 0x97e32d77, 0x11ed935f, 0x16681281,
			0x0e358829, 0xc7e61fd6, 0x96dedfa1, 0x7858ba99, 0x57f584a5, 0x1b227263, 0x9b83c3ff, 0x1ac24696,
			0xcdb30aeb, 0x532e3054, 0x8fd948e4, 0x6dbc3128, 0x58ebf2ef, 0x34c6ffea, 0xfe28ed61, 0xee7c3c73,
			0x5d4a14d9, 0xe864b7e3, 0x42105d14, 0x203e13e0, 0x45eee2b6, 0xa3aaabea, 0xdb6c4f15, 0xfacb4fd0,
			0xc742f442, 0xef6abbb5, 0x654f3b1d, 0x41cd2105, 0xd81e799e, 0x86854dc7, 0xe44b476a, 0x3d816250,
			0xcf62a1f2, 0x5b8d2646, 0xfc8883a0, 0xc1c7b6a3, 0x7f1524c3, 0x69cb7492, 0x47848a0b, 0x5692b285,
			0x095bbf00, 0xad19489d, 0x1462b174, 0x23820e00, 0x58428d2a, 0x0c55f5ea, 0x1dadf43e, 0x233f7061,
			0x3372f092, 0x8d937e41, 0xd65fecf1, 0x6c223bdb, 0x7cde3759, 0xcbee7460, 0x4085f2a7, 0xce77326e,
			0xa6078084, 0x19f8509e, 0xe8efd855, 0x61d99735, 0xa969a7aa, 0xc50c06c2, 0x5a04abfc, 0x800bcadc,
			0x9e447a2e, 0xc3453484, 0xfdd56705, 0x0e1e9ec9, 0xdb73dbd3, 0x105588cd, 0x675fda79, 0xe3674340,
			0xc5c43465, 0x713e38d8, 0x3d28f89e, 0xf16dff20, 0x153e21e7, 0x8fb03d4a, 0xe6e39f2b, 0xdb83adf7
		],
		s2:[
			0xe93d5a68, 0x948140f7, 0xf64c261c, 0x94692934, 0x411520f7, 0x7602d4f7, 0xbcf46b2e, 0xd4a20068,
			0xd4082471, 0x3320f46a, 0x43b7d4b7, 0x500061af, 0x1e39f62e, 0x97244546, 0x14214f74, 0xbf8b8840,
			0x4d95fc1d, 0x96b591af, 0x70f4ddd3, 0x66a02f45, 0xbfbc09ec, 0x03bd9785, 0x7fac6dd0, 0x31cb8504,
			0x96eb27b3, 0x55fd3941, 0xda2547e6, 0xabca0a9a, 0x28507825, 0x530429f4, 0x0a2c86da, 0xe9b66dfb,
			0x68dc1462, 0xd7486900, 0x680ec0a4, 0x27a18dee, 0x4f3ffea2, 0xe887ad8c, 0xb58ce006, 0x7af4d6b6,
			0xaace1e7c, 0xd3375fec, 0xce78a399, 0x406b2a42, 0x20fe9e35, 0xd9f385b9, 0xee39d7ab, 0x3b124e8b,
			0x1dc9faf7, 0x4b6d1856, 0x26a36631, 0xeae397b2, 0x3a6efa74, 0xdd5b4332, 0x6841e7f7, 0xca7820fb,
			0xfb0af54e, 0xd8feb397, 0x454056ac, 0xba489527, 0x55533a3a, 0x20838d87, 0xfe6ba9b7, 0xd096954b,
			0x55a867bc, 0xa1159a58, 0xcca92963, 0x99e1db33, 0xa62a4a56, 0x3f3125f9, 0x5ef47e1c, 0x9029317c,
			0xfdf8e802, 0x04272f70, 0x80bb155c, 0x05282ce3, 0x95c11548, 0xe4c66d22, 0x48c1133f, 0xc70f86dc,
			0x07f9c9ee, 0x41041f0f, 0x404779a4, 0x5d886e17, 0x325f51eb, 0xd59bc0d1, 0xf2bcc18f, 0x41113564,
			0x257b7834, 0x602a9c60, 0xdff8e8a3, 0x1f636c1b, 0x0e12b4c2, 0x02e1329e, 0xaf664fd1, 0xcad18115,
			0x6b2395e0, 0x333e92e1, 0x3b240b62, 0xeebeb922, 0x85b2a20e, 0xe6ba0d99, 0xde720c8c, 0x2da2f728,
			0xd0127845, 0x95b794fd, 0x647d0862, 0xe7ccf5f0, 0x5449a36f, 0x877d48fa, 0xc39dfd27, 0xf33e8d1e,
			0x0a476341, 0x992eff74, 0x3a6f6eab, 0xf4f8fd37, 0xa812dc60, 0xa1ebddf8, 0x991be14c, 0xdb6e6b0d,
			0xc67b5510, 0x6d672c37, 0x2765d43b, 0xdcd0e804, 0xf1290dc7, 0xcc00ffa3, 0xb5390f92, 0x690fed0b,
			0x667b9ffb, 0xcedb7d9c, 0xa091cf0b, 0xd9155ea3, 0xbb132f88, 0x515bad24, 0x7b9479bf, 0x763bd6eb,
			0x37392eb3, 0xcc115979, 0x8026e297, 0xf42e312d, 0x6842ada7, 0xc66a2b3b, 0x12754ccc, 0x782ef11c,
			0x6a124237, 0xb79251e7, 0x06a1bbe6, 0x4bfb6350, 0x1a6b1018, 0x11caedfa, 0x3d25bdd8, 0xe2e1c3c9,
			0x44421659, 0x0a121386, 0xd90cec6e, 0xd5abea2a, 0x64af674e, 0xda86a85f, 0xbebfe988, 0x64e4c3fe,
			0x9dbc8057, 0xf0f7c086, 0x60787bf8, 0x6003604d, 0xd1fd8346, 0xf6381fb0, 0x7745ae04, 0xd736fccc,
			0x83426b33, 0xf01eab71, 0xb0804187, 0x3c005e5f, 0x77a057be, 0xbde8ae24, 0x55464299, 0xbf582e61,
			0x4e58f48f, 0xf2ddfda2, 0xf474ef38, 0x8789bdc2, 0x5366f9c3, 0xc8b38e74, 0xb475f255, 0x46fcd9b9,
			0x7aeb2661, 0x8b1ddf84, 0x846a0e79, 0x915f95e2, 0x466e598e, 0x20b45770, 0x8cd55591, 0xc902de4c,
			0xb90bace1, 0xbb8205d0, 0x11a86248, 0x7574a99e, 0xb77f19b6, 0xe0a9dc09, 0x662d09a1, 0xc4324633,
			0xe85a1f02, 0x09f0be8c, 0x4a99a025, 0x1d6efe10, 0x1ab93d1d, 0x0ba5a4df, 0xa186f20f, 0x2868f169,
			0xdcb7da83, 0x573906fe, 0xa1e2ce9b, 0x4fcd7f52, 0x50115e01, 0xa70683fa, 0xa002b5c4, 0x0de6d027,
			0x9af88c27, 0x773f8641, 0xc3604c06, 0x61a806b5, 0xf0177a28, 0xc0f586e0, 0x006058aa, 0x30dc7d62,
			0x11e69ed7, 0x2338ea63, 0x53c2dd94, 0xc2c21634, 0xbbcbee56, 0x90bcb6de, 0xebfc7da1, 0xce591d76,
			0x6f05e409, 0x4b7c0188, 0x39720a3d, 0x7c927c24, 0x86e3725f, 0x724d9db9, 0x1ac15bb4, 0xd39eb8fc,
			0xed545578, 0x08fca5b5, 0xd83d7cd3, 0x4dad0fc4, 0x1e50ef5e, 0xb161e6f8, 0xa28514d9, 0x6c51133c,
			0x6fd5c7e7, 0x56e14ec4, 0x362abfce, 0xddc6c837, 0xd79a3234, 0x92638212, 0x670efa8e, 0x406000e0
		],
		s3:[
			0x3a39ce37, 0xd3faf5cf, 0xabc27737, 0x5ac52d1b, 0x5cb0679e, 0x4fa33742, 0xd3822740, 0x99bc9bbe,
			0xd5118e9d, 0xbf0f7315, 0xd62d1c7e, 0xc700c47b, 0xb78c1b6b, 0x21a19045, 0xb26eb1be, 0x6a366eb4,
			0x5748ab2f, 0xbc946e79, 0xc6a376d2, 0x6549c2c8, 0x530ff8ee, 0x468dde7d, 0xd5730a1d, 0x4cd04dc6,
			0x2939bbdb, 0xa9ba4650, 0xac9526e8, 0xbe5ee304, 0xa1fad5f0, 0x6a2d519a, 0x63ef8ce2, 0x9a86ee22,
			0xc089c2b8, 0x43242ef6, 0xa51e03aa, 0x9cf2d0a4, 0x83c061ba, 0x9be96a4d, 0x8fe51550, 0xba645bd6,
			0x2826a2f9, 0xa73a3ae1, 0x4ba99586, 0xef5562e9, 0xc72fefd3, 0xf752f7da, 0x3f046f69, 0x77fa0a59,
			0x80e4a915, 0x87b08601, 0x9b09e6ad, 0x3b3ee593, 0xe990fd5a, 0x9e34d797, 0x2cf0b7d9, 0x022b8b51,
			0x96d5ac3a, 0x017da67d, 0xd1cf3ed6, 0x7c7d2d28, 0x1f9f25cf, 0xadf2b89b, 0x5ad6b472, 0x5a88f54c,
			0xe029ac71, 0xe019a5e6, 0x47b0acfd, 0xed93fa9b, 0xe8d3c48d, 0x283b57cc, 0xf8d56629, 0x79132e28,
			0x785f0191, 0xed756055, 0xf7960e44, 0xe3d35e8c, 0x15056dd4, 0x88f46dba, 0x03a16125, 0x0564f0bd,
			0xc3eb9e15, 0x3c9057a2, 0x97271aec, 0xa93a072a, 0x1b3f6d9b, 0x1e6321f5, 0xf59c66fb, 0x26dcf319,
			0x7533d928, 0xb155fdf5, 0x03563482, 0x8aba3cbb, 0x28517711, 0xc20ad9f8, 0xabcc5167, 0xccad925f,
			0x4de81751, 0x3830dc8e, 0x379d5862, 0x9320f991, 0xea7a90c2, 0xfb3e7bce, 0x5121ce64, 0x774fbe32,
			0xa8b6e37e, 0xc3293d46, 0x48de5369, 0x6413e680, 0xa2ae0810, 0xdd6db224, 0x69852dfd, 0x09072166,
			0xb39a460a, 0x6445c0dd, 0x586cdecf, 0x1c20c8ae, 0x5bbef7dd, 0x1b588d40, 0xccd2017f, 0x6bb4e3bb,
			0xdda26a7e, 0x3a59ff45, 0x3e350a44, 0xbcb4cdd5, 0x72eacea8, 0xfa6484bb, 0x8d6612ae, 0xbf3c6f47,
			0xd29be463, 0x542f5d9e, 0xaec2771b, 0xf64e6370, 0x740e0d8d, 0xe75b1357, 0xf8721671, 0xaf537d5d,
			0x4040cb08, 0x4eb4e2cc, 0x34d2466a, 0x0115af84, 0xe1b00428, 0x95983a1d, 0x06b89fb4, 0xce6ea048,
			0x6f3f3b82, 0x3520ab82, 0x011a1d4b, 0x277227f8, 0x611560b1, 0xe7933fdc, 0xbb3a792b, 0x344525bd,
			0xa08839e1, 0x51ce794b, 0x2f32c9b7, 0xa01fbac9, 0xe01cc87e, 0xbcc7d1f6, 0xcf0111c3, 0xa1e8aac7,
			0x1a908749, 0xd44fbd9a, 0xd0dadecb, 0xd50ada38, 0x0339c32a, 0xc6913667, 0x8df9317c, 0xe0b12b4f,
			0xf79e59b7, 0x43f5bb3a, 0xf2d519ff, 0x27d9459c, 0xbf97222c, 0x15e6fc2a, 0x0f91fc71, 0x9b941525,
			0xfae59361, 0xceb69ceb, 0xc2a86459, 0x12baa8d1, 0xb6c1075e, 0xe3056a0c, 0x10d25065, 0xcb03a442,
			0xe0ec6e0e, 0x1698db3b, 0x4c98a0be, 0x3278e964, 0x9f1f9532, 0xe0d392df, 0xd3a0342b, 0x8971f21e,
			0x1b0a7441, 0x4ba3348c, 0xc5be7120, 0xc37632d8, 0xdf359f8d, 0x9b992f2e, 0xe60b6f47, 0x0fe3f11d,
			0xe54cda54, 0x1edad891, 0xce6279cf, 0xcd3e7e6f, 0x1618b166, 0xfd2c1d05, 0x848fd2c5, 0xf6fb2299,
			0xf523f357, 0xa6327623, 0x93a83531, 0x56cccd02, 0xacf08162, 0x5a75ebb5, 0x6e163697, 0x88d273cc,
			0xde966292, 0x81b949d0, 0x4c50901b, 0x71c65614, 0xe6c6c7bd, 0x327a140a, 0x45e1d006, 0xc3f27b9a,
			0xc9aa53fd, 0x62a80f00, 0xbb25bfe2, 0x35bdd2f6, 0x71126905, 0xb2040222, 0xb6cbcf7c, 0xcd769c2b,
			0x53113ec0, 0x1640e3d3, 0x38abbd60, 0x2547adf0, 0xba38209c, 0xf746ce76, 0x77afa1c5, 0x20756060,
			0x85cbfe4e, 0x8ae88dd8, 0x7aaaf9b0, 0x4cf9aa7e, 0x1948c25c, 0x02fb8a8c, 0x01c36ae4, 0xd6ebe1f9,
			0x90d4f869, 0xa65cdea0, 0x3f09252d, 0xc208e69f, 0xb74e6132, 0xce77e25b, 0x578fdfe3, 0x3ac372e6
		]
	};

	const bfsig = 'b' + 'f' + (56*8) + '.' + 'v' + (16/8);

	//== PRIVATES BLOWFISH

	const array_map = function(arr, callback, theObj, Ctr) {
		//--
		// summary:
		//		applies callback to each element of arr and returns
		//		an Array with the results
		// arr: Array|String
		//		the array to iterate on. If a string, operates on
		//		individual characters.
		// callback: Function|String
		//		a function is invoked with three arguments, (item, index,
		//		array),	 and returns a value
		// theObj: Object?
		//		may be used to scope the call to callback
		// returns: Array
		// description:
		//		The function corresponds to the JavaScript 1.6 Array.map() method, with one difference: when
		//		run over sparse arrays, the implementation passes the 'holes' in the sparse array to
		//		the callback function with a value of undefined. JavaScript 1.6's map skips the holes in the sparse array.
		//		For more details, see:
		//		https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Objects/Array/map
		// example:
		//	| // returns [2, 3, 4, 5]
		//	| array.map([1, 2, 3, 4], function(item){ return item+1 });
		// TODO: why do we have a non-standard signature here? do we need 'Ctr' ?
		//--
		let i = 0, l = arr && arr.length || 0, out = new (Ctr || Array)(l);
		if(l && (typeof(arr) == 'string')) {
			arr = arr.split('');
		} //end if
		if(typeof(callback) == 'string') {
			callback = cache[callback] || buildFn(callback);
		} //end if
		//--
		if(theObj) {
			for(; i < l; ++i) {
				out[i] = callback.call(theObj, arr[i], i, arr);
			} //end for
		} else {
			for(; i < l; ++i) {
				out[i] = callback(arr[i], i, arr);
			} //end for
		} //end if else
		//--
		return out; // Array
		//--
	}; //END

	// fixes based on patch submitted by Peter Wood (#5791)

	const add = (x,y) => {
		return (((x>>0x10)+(y>>0x10)+(((x&0xffff)+(y&0xffff))>>0x10))<<0x10)|(((x&0xffff)+(y&0xffff))&0xffff);
	}; //END

	const xor = (x,y) => {
		return (((x>>0x10)^(y>>0x10))<<0x10)|(((x&0xffff)^(y&0xffff))&0xffff);
	}; //END

	const dollar = function(v, box) {
		//--
		let d = box.s3[v&0xff]; v>>=8;
		let c = box.s2[v&0xff]; v>>=8;
		let b = box.s1[v&0xff]; v>>=8;
		let a = box.s0[v&0xff];
		//--
		let r;
		r = (((a>>0x10)+(b>>0x10)+(((a&0xffff)+(b&0xffff))>>0x10))<<0x10)|(((a&0xffff)+(b&0xffff))&0xffff);
		r = (((r>>0x10)^(c>>0x10))<<0x10)|(((r&0xffff)^(c&0xffff))&0xffff);
		//--
		return (((r>>0x10)+(d>>0x10)+(((r&0xffff)+(d&0xffff))>>0x10))<<0x10)|(((r&0xffff)+(d&0xffff))&0xffff);
		//--
	}; //END

	const eb = function(o, box) {
		//--
		let l = o.left;
		let r = o.right;
		//--
		l = xor(l,box.p[0]);
		r = xor(r,xor(dollar(l,box),box.p[1]));
		l = xor(l,xor(dollar(r,box),box.p[2]));
		r = xor(r,xor(dollar(l,box),box.p[3]));
		l = xor(l,xor(dollar(r,box),box.p[4]));
		r = xor(r,xor(dollar(l,box),box.p[5]));
		l = xor(l,xor(dollar(r,box),box.p[6]));
		r = xor(r,xor(dollar(l,box),box.p[7]));
		l = xor(l,xor(dollar(r,box),box.p[8]));
		r = xor(r,xor(dollar(l,box),box.p[9]));
		l = xor(l,xor(dollar(r,box),box.p[10]));
		r = xor(r,xor(dollar(l,box),box.p[11]));
		l = xor(l,xor(dollar(r,box),box.p[12]));
		r = xor(r,xor(dollar(l,box),box.p[13]));
		l = xor(l,xor(dollar(r,box),box.p[14]));
		r = xor(r,xor(dollar(l,box),box.p[15]));
		l = xor(l,xor(dollar(r,box),box.p[16]));
		//--
		o.right = l;
		o.left = xor(r, box.p[17]);
		//--
	}; //END

	const db = function(o, box) {
		//--
		let l = o.left;
		let r = o.right;
		//--
		l = xor(l,box.p[17]);
		r = xor(r,xor(dollar(l,box),box.p[16]));
		l = xor(l,xor(dollar(r,box),box.p[15]));
		r = xor(r,xor(dollar(l,box),box.p[14]));
		l = xor(l,xor(dollar(r,box),box.p[13]));
		r = xor(r,xor(dollar(l,box),box.p[12]));
		l = xor(l,xor(dollar(r,box),box.p[11]));
		r = xor(r,xor(dollar(l,box),box.p[10]));
		l = xor(l,xor(dollar(r,box),box.p[9]));
		r = xor(r,xor(dollar(l,box),box.p[8]));
		l = xor(l,xor(dollar(r,box),box.p[7]));
		r = xor(r,xor(dollar(l,box),box.p[6]));
		l = xor(l,xor(dollar(r,box),box.p[5]));
		r = xor(r,xor(dollar(l,box),box.p[4]));
		l = xor(l,xor(dollar(r,box),box.p[3]));
		r = xor(r,xor(dollar(l,box),box.p[2]));
		l = xor(l,xor(dollar(r,box),box.p[1]));
		//--
		o.right = l;
		o.left = xor(r, box.p[0]);
		//--
	}; //END

	// Return true if it is a String
	const isString = (it) => {
		return (typeof(it) == 'string' || it instanceof String); // Boolean
	}; //END

	//	Note that we aren't caching contexts here; it might take a little longer but we should be more secure like so.
	const init = function(key) {
		//--
		let k = key;
		//--
		if(isString(k)){
			k = array_map(k.split(''), (item) => { return item.charCodeAt(0) & 0xff; });
		} //end if
		//-- init the boxes
		let pos = 0, data = 0;
		let res = {
			left: 0,
			right: 0
		};
		let i, j, l;
		let box = {
			p: array_map(boxes.p.slice(0), (item) => {
				let q = k.length, r;
				for(r=0; r<4; r++) {
					data = (data*POW8)|k[pos++ % q];
				} //end for
				return (((item>>0x10)^(data>>0x10))<<0x10)|(((item&0xffff)^(data&0xffff))&0xffff);
			}),
			s0: boxes.s0.slice(0),
			s1: boxes.s1.slice(0),
			s2: boxes.s2.slice(0),
			s3: boxes.s3.slice(0)
		};
		//-- encrypt p and the s boxes
		for(i=0, l=box.p.length; i<l;) {
			eb(res, box);
			box.p[i++]=res.left, box.p[i++]=res.right;
		} //end for
		for(i=0; i<4; i++) {
			for(j=0, l=box['s'+i].length; j<l;) {
				eb(res, box);
				box['s'+i][j++]=res.left, box['s'+i][j++]=res.right;
			} //end for
		} //end for
		//--
		return box;
		//--
	}; //END

	const setKey = function(key) { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		const _m$ = 'setKey';
		//-- generate a secure key {{{SYNC-BLOWFISH-KEY}}}
		key = _Utils$.stringPureVal(key, true); // trim // {{{SYNC-CRYPTO-KEY-TRIM}}}
		//--
		let klen = key.length;
		if(klen < 7) { // {{{SYNC-CRYPTO-KEY-MIN}}}
			_p$.warn(_N$, _m$, 'Key Size is lower than 7 bytes (' + klen + ') !');
			return '';
		} else if(klen > 4096) { // {{{SYNC-CRYPTO-KEY-MAX}}}
			_p$.warn(_N$, _m$, 'Key Size is higher than 4096 bytes (' + klen + ') !');
			return '';
		} //end if
		//--
		const nByte = '\0';
		const salted_key = String(nByte + key);
		//-- chances are zero in practice to have a key colission by ensuring 2 different (salted and not salted) input to produce a simultan colission in all 5 algos: CRC32 / MD5 / SHA1 / SHA256 / SHA512 at once !!!
		const hkey1 = String(_Crypto$Hash.crc32b(key)        + nByte + _Crypto$Hash.md5(key)        + nByte + _Crypto$Hash.sha1(key)        + nByte + _Crypto$Hash.sha256(key)        + nByte + _Crypto$Hash.sha512(key));
		const hkey2 = String(_Crypto$Hash.crc32b(salted_key) + nByte + _Crypto$Hash.md5(salted_key) + nByte + _Crypto$Hash.sha1(salted_key) + nByte + _Crypto$Hash.sha256(salted_key) + nByte + _Crypto$Hash.sha512(salted_key));
		const composed_key = String(hkey1 + nByte + hkey2);
		const derived_key = String(_Ba$eConv.base_from_hex_convert(_Crypto$Hash.sha256(composed_key), 92)) + "'" + String(_Ba$eConv.base_from_hex_convert(_Crypto$Hash.md5(composed_key), 92));
		const r_key = String(derived_key).substr(0,448/8); // 448/8
		//_p$.log(_N$, _m$, 'Key:', r_key);
		//--
		return String(r_key);
		//--
	}; //END

	// sets the initialization vector to data
	const setIV = function(key) { // {{{SYNC-CRYPTO-KEY-DERIVE}}}
		//--
		const _m$ = 'setIV';
		//--
		key = _Utils$.stringPureVal(key, true); // trim
		let klen = key.length;
		if(klen < 7) { // {{{SYNC-CRYPTO-KEY-MIN}}}
			_p$.warn(_N$, _m$, 'Key Size is lower than 7 bytes (' + klen + ') !');
			return '';
		} else if(klen > 4096) { // {{{SYNC-CRYPTO-KEY-MAX}}}
			_p$.warn(_N$, _m$, 'Key Size is higher than 4096 bytes (' + klen + ') !');
			return '';
		} //end if
		//-- SmartFramework compatible {{{SYNC-BLOWFISH-IV}}}
		const data = String(_Crypto$Hash.crc32b(key, true)).padStart(8, '0');
		IV = String(String(data) + ':' + _Crypto$Hash.sha1(key, true)).substr(0,64/8); // 64/8
		//_p$.log(_N$, _m$, 'iV:', IV);
		//--
		const byt = array_map(IV.split(''), (item) => { return item.charCodeAt(0); }); // pre-process
		iv = {}; // make it a pair of words
		iv.left = byt[0]*POW24|byt[1]*POW16|byt[2]*POW8|byt[3];
		iv.right = byt[4]*POW24|byt[5]*POW16|byt[6]*POW8|byt[7];
		//--
		return String(IV);
		//--
	}; //END

	//== PUBLIC BLOWFISH

	/**
	 * Blowfish encrypts (CBC) plaintext using an encryption key
	 *
	 * @memberof smartJ$CryptoBlowfish
	 * @method encrypt
	 * @static
	 *
	 * @param {String} plaintext The plain string
	 * @param {String} key The encryption key
	 * @return {String} The Blowfish encrypted string
	 */
	const encrypt = function(plaintext, key) {
		//--
		const _m$ = 'encrypt';
		//--
		plaintext = _Utils$.stringPureVal(plaintext); // cast to string, don't trim ! need to preserve the value
		if(plaintext == '') {
			return '';
		} //end if
		plaintext = _Ba$e64.encode(plaintext); // b64 is because is not UTF-8 safe and may corrupt unicode characters
		//--
		key = _Utils$.stringPureVal(key); // cast to string, don't trim ! need to preserve the value
		const testIV = setIV(key); // needs original key
		key = setKey(key);
		//--
		if(key.length != 56) {
			_p$.error(_N$, _m$, 'Invalid Key Length (req. 448 bytes)');
			return '';
		} //end if
		if(testIV.length != 8) {
			_p$.error(_N$, _m$, 'Invalid iV Length (req. 64 bytes)');
			return '';
		} //end if
		//--
		const mode = cipherModes.CBC;
		const bx = init(key);
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		plaintext = String(plaintext + '#CKSUM256#' + _Crypto$Hash.sha256(plaintext, true));
		//--
		//===== {{{SYNC-BLOWFISH-PADDING}}}
		//-- Blowfish is a 64-bit block cipher. It means that the data must be provided in units that are a multiple of 8 bytes
		const padding = 8 - (plaintext.length & 7);
		//-- unixman: fix: add spaces as padding as we have it as b64 encoded and will not modify the original
		// for(let i=0; i<padding; i++) { plaintext+=String.fromCharCode(padding); } // original padding
		for(let i=0; i<padding; i++) {
			plaintext += ' '; // unixman (pad with spaces)
		} //end for
		//--
		//=====
		//--
		let cipher = [];
		const count = plaintext.length >> 3;
		let pos = 0, o = {};
		const isCBC = (mode == cipherModes.CBC);
		const vector = {
			left: iv.left || null,
			right: iv.right || null
		};
		//--
		for(let i=0; i<count; i++) {
			//--
			o.left = plaintext.charCodeAt(pos) * POW24
				|plaintext.charCodeAt(pos+1) * POW16
				|plaintext.charCodeAt(pos+2) * POW8
				|plaintext.charCodeAt(pos+3);
			o.right = plaintext.charCodeAt(pos+4) * POW24
				|plaintext.charCodeAt(pos+5) * POW16
				|plaintext.charCodeAt(pos+6) * POW8
				|plaintext.charCodeAt(pos+7);
			//--
			if(isCBC){
				o.left = (((o.left>>0x10)^(vector.left>>0x10))<<0x10)|(((o.left&0xffff)^(vector.left&0xffff))&0xffff);
				o.right = (((o.right>>0x10)^(vector.right>>0x10))<<0x10)|(((o.right&0xffff)^(vector.right&0xffff))&0xffff);
			} //end if
			//-- encrypt the block
			eb(o, bx);
			//--
			if(isCBC){
				vector.left = o.left;
				vector.right = o.right;
			} //end if
			//--
			cipher.push((o.left>>24)&0xff);
			cipher.push((o.left>>16)&0xff);
			cipher.push((o.left>>8)&0xff);
			cipher.push(o.left&0xff);
			cipher.push((o.right>>24)&0xff);
			cipher.push((o.right>>16)&0xff);
			cipher.push((o.right>>8)&0xff);
			cipher.push(o.right&0xff);
			//--
			pos += 8;
			//--
		} //end for
		//--
		//=====
		//-- BASE64
	//	return _Ba$e64.encode(array_map(cipher, (item) => (String.fromCharCode(item))).join(''), true); // b64
		return bfsig+'!' + _Ba$eConv.b64s_enc(array_map(cipher, (item) => (String.fromCharCode(item))).join(''), true); // b64s
		//-- HEX
	//	return String(array_map(cipher, (item) => ((item<=0xf?'0':'') + item.toString(16))).join('').toUpperCase()); // HEX
		//--
		//=====
		//--
	}; //END
	_C$.encrypt = encrypt; // export

	/**
	 * Blowfish decrypts (CBC) ciphertext using the encryption key
	 *
	 * @memberof smartJ$CryptoBlowfish
	 * @method decrypt
	 * @static
	 *
	 * @param {String} ciphertext The Blowfish encrypted string
	 * @param {String} key The encryption key
	 * @return {String} The decrypted string
	 */
	const decrypt = function(ciphertext, key) {
		//--
		const _m$ = 'decrypt';
		//--
		ciphertext = _Utils$.stringPureVal(ciphertext, true); // cast to string, + trim
		if(ciphertext == '') {
			return '';
		} //end if
		if(!_Utils$.stringStartsWith(ciphertext, bfsig+'!')) {
			return '';
		} //end if
		ciphertext = ciphertext.split('!', 2);
		ciphertext = _Utils$.stringPureVal(ciphertext[1], true); // cast to string, + trim
		//--
		key = _Utils$.stringPureVal(key); // cast to string, don't trim ! need to preserve the value
		const testIV = setIV(key); // needs original key
		key = setKey(key);
		//--
		if(key.length != 56) {
			_p$.error(_N$, _m$, 'Invalid Key Length (req. 448 bytes)');
			return '';
		} //end if
		if(testIV.length != 8) {
			_p$.error(_N$, _m$, 'Invalid iV Length (req. 64 bytes)', testIV);
			return '';
		} //end if
		//--
		const mode = cipherModes.CBC;
		const bx = init(key);
		//--
		let pt = [];
		let c = null;
		//--
		//=====
		//-- BASE64
	//	c = array_map(_Ba$e64.decode(ciphertext, true).split(''), function(item){ return item.charCodeAt(0); }); // b64
		c = array_map(_Ba$eConv.b64s_dec(ciphertext, true).split(''), function(item){ return item.charCodeAt(0); }); // b64s
		//-- HEX
	//	c = [];
	//	ciphertext = _Utils$.stringTrim(ciphertext).toLowerCase(); // make back lowercase and trim (because in encrypt we delivered as uppercase)
	//	for(let i=0, l=ciphertext.length-1; i<l; i+=2){
	//		c.push(parseInt(ciphertext.substr(i,2), 16));
	//	} //end for
		//--
		ciphertext = null; // free mem
		//=====
		//--
		const count = c.length >> 3;
		let pos = 0, o = {};
		const isCBC = (mode == cipherModes.CBC);
		const vector = {
			left: iv.left || null,
			right: iv.right || null
		};
		//--
		for(let i=0; i<count; i++) {
			//--
			o.left = c[pos]*POW24|c[pos+1]*POW16|c[pos+2]*POW8|c[pos+3];
			o.right = c[pos+4]*POW24|c[pos+5]*POW16|c[pos+6]*POW8|c[pos+7];
			//--
			let left = null, right = null;
			if(isCBC) {
				left = o.left;
				right = o.right;
			} //end if
			//-- decrypt the block
			db(o, bx);
			//--
			if(isCBC) {
				o.left = (((o.left>>0x10)^(vector.left>>0x10))<<0x10)|(((o.left&0xffff)^(vector.left&0xffff))&0xffff);
				o.right = (((o.right>>0x10)^(vector.right>>0x10))<<0x10)|(((o.right&0xffff)^(vector.right&0xffff))&0xffff);
				vector.left = left;
				vector.right = right;
			} //end if
			//--
			pt.push((o.left>>24)&0xff);
			pt.push((o.left>>16)&0xff);
			pt.push((o.left>>8)&0xff);
			pt.push(o.left&0xff);
			pt.push((o.right>>24)&0xff);
			pt.push((o.right>>16)&0xff);
			pt.push((o.right>>8)&0xff);
			pt.push(o.right&0xff);
			pos += 8;
			//--
		} //end for
		//===== {{{SYNC-BLOWFISH-PADDING-TRIM}}} :: trim padding spaces
		// #un-padding# is not necessary as we added trailing spaces and we simply trim it below
		//if(pt[pt.length-1] == pt[pt.length-2]||pt[pt.length-1]==0x01) { let n = pt[pt.length-1]; pt.splice(pt.length-n, n); }
		//--
		let plaintext = String(_Utils$.stringTrim(array_map(pt, (item) => String.fromCharCode(item)).join('')));
		//=====
		//-- {{{SYNC-BLOWFISH-CHECKSUM}}}
		let parts = plaintext.split('#CKSUM256#', 2);
		plaintext = _Utils$.stringPureVal(parts[0], true); // trim
		const checksum = _Utils$.stringPureVal(parts[1], true); // trim
		parts = null;
		if(_Crypto$Hash.sha256(plaintext, true) !== String(checksum)) {
			_p$.warn(_N$, 'decrypt', 'Checksum Failed'); // do not raise error just alert
			return '';
		} //end if
		//-- convert to string and reverse b64 :: b64 is because is not UTF-8 safe and may corrupt unicode characters
		return String(_Ba$e64.decode(plaintext)); // string
		//--
	}; //END
	_C$.decrypt = decrypt; // export

}}; //END CLASS

smartJ$CryptoBlowfish.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CryptoBlowfish = smartJ$CryptoBlowfish; // global export
} //end if

//=======================================
// CLASS :: Crypto DH Kx
//=======================================

/**
 * CLASS :: Smart DH Kx (ES6)
 *
 * @package Sf.Javascript:DhKx
 *
 * @requires		smartJ$Utils
 *
 * @desc The JavaScript class provides methods to implement a secure algorithm for Diffie-Hellman key exchange between a server and a client ; Supports dual operation mode (Int64 or BigInt ; for using BigInt the broser must support it ...)
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20220313
 * @class smartJ$DhKx
 * @static
 * @frozen
 *
 */
const smartJ$DhKx = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$DhKx';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	let _w$ = (typeof(window) != 'undefined') ? window : null;

	const _Utils$ = smartJ$Utils;
	const _Ba$eConv = smartJ$BaseEncode;
	const _Crypto$Hash = smartJ$CryptoHash;
	const _Crypto$Blowfish = smartJ$CryptoBlowfish;

	const bigIntSupport = (_w$ && _w$.crypto && _w$.crypto.getRandomValues && _w$.BigInt) ? true : false

	//==

	const _Option$ = ((typeof(smartJ$Options) != 'undefined') && smartJ$Options && (smartJ$Options.DhKx != undefined) && (typeof(smartJ$Options) === 'object') && (typeof(smartJ$Options.DhKx) === 'object')) ? smartJ$Options.DhKx : null;

	_C$.param_Size = (_Option$ && (typeof(_Option$.Size) == 'string') && _Option$.Size) ? _Utils$.stringTrim(_Option$.Size) : 'default';
	_C$.param_Prix = (_Option$ && (typeof(_Option$.Prix) == 'string') && _Option$.Prix) ? _Utils$.stringTrim(_Option$.Prix) : 'default';
	_C$.param_UseBigInt = (bigIntSupport && (_Option$ && (!!_Option$.UseBigInt))) ? true : false;
	_C$.param_DevMode = (_Option$ && (!!_Option$.DevMode)) ? true : false;

	//== [PUBLIC]

	/**
	 * Get the DH Mode
	 *
	 * @memberof smartJ$DhKx
	 * @method getMode
	 * @static
	 *
	 * @return 	{String} 				The operating mode: BigInt or Int64
	 */
	const getMode = function() {
		//--
		let mode = '??';
		if(_C$.param_UseBigInt === true) {
			mode = 'BigInt';
		} else {
			mode = 'Int64';
		} //end if else
		//--
		return String(mode);
		//--
	}; //END
	_C$.getMode = getMode; // export

	/**
	 * Get the DH Base (Gen)
	 *
	 * @memberof smartJ$DhKx
	 * @method getBaseGen
	 * @static
	 *
	 * @return 	{Mixed} 				The random base generator ; size depends if running in BigInt or Int64 mode
	 */
	const getBaseGen = function(size) {
		//--
		size = _Utils$.stringPureVal(size, true); // trim
		if((size === '') || (size === 'default')) {
			size = _C$.param_Size;
		} //end if
		//--
		return rng(String(size));
		//--
	}; //END
	_C$.getBaseGen = getBaseGen; // export

	/**
	 * Get the DH SRV Side Data
	 *
	 * @memberof smartJ$DhKx
	 * @method getSrvData
	 * @static
	 *
	 * @param 	{Mixed} basegen			The random base generator ; size depends if running in BigInt or Int64 mode
	 * @return 	{Object} 				The SRV side Data
	 */
	const getSrvData = function(basegen) {
		//--
		const size = String(_C$.param_Size);
		const prix = String(_C$.param_Prix);
		const p = prime(prix);
		const ssec = rng(size);
		const spub = powm(basegen, ssec, p);
		//_p$.log(_N$, 'getSrvData', 'base:', basegen, 'p:', p, 'ssec:', ssec, 'spub:', spub);
		//--
		return {
			base: basegen,
			prix: prix,
			sec: ssec,
			pub: spub,
		};
		//--
	}; //END
	_C$.getSrvData = getSrvData; // export

	/**
	 * Get the DH SRV Side Shad
	 *
	 * @memberof smartJ$DhKx
	 * @method getSrvShad
	 * @static
	 *
	 * @param 	{Mixed} ssec			The random side of SRV
	 * @param 	{Mixed} ssec			The public side of CLI
	 * @return 	{Mixed} 				The SRV side Shad Data ; size depends if running in BigInt or Int64 mode
	 */
	const getSrvShad = function(ssec, cpub) {
		//--
		const prix = String(_C$.param_Prix);
		const p = prime(prix);
		const shad = powm(cpub, ssec, p);
		//_p$.log(_N$, 'getSrvShad', 'p:', p, 'ssec:', ssec, 'cpub:', cpub, 'shad:', shad);
		//--
		return shadizer(shad);
		//--
	}; //END
	_C$.getSrvShad = getSrvShad; // export

	/**
	 * Get the DH CLI Side Data
	 *
	 * @memberof smartJ$DhKx
	 * @method getCliData
	 * @static
	 *
	 * @param 	{Mixed} basegen			The random base generator ; size depends if running in BigInt or Int64 mode
	 * @return 	{Object} 				The CLI side Data
	 */
	const getCliData = function(basegen) {
		//--
		const size = String(_C$.param_Size);
		const prix = String(_C$.param_Prix);
		const p = prime(prix);
		const csec = rng(size);
		const cpub = powm(basegen, csec, p);
		//_p$.log(_N$, 'getCliData', 'base:', basegen, 'p:', p, 'csec:', csec, 'cpub:', cpub);
		//--
		return {
			base: basegen,
			prix: prix,
			sec: csec,
			pub: cpub,
		};
		//--
	}; //END
	_C$.getCliData = getCliData; // export

	/**
	 * Get the DH CLI Side Shad
	 *
	 * @memberof smartJ$DhKx
	 * @method getCliShad
	 * @static
	 *
	 * @param 	{Mixed} ssec			The random side of CLI
	 * @param 	{Mixed} ssec			The public side of SRV
	 * @return 	{Mixed} 				The CLI side Shad Data ; size depends if running in BigInt or Int64 mode
	 */
	const getCliShad = function(csec, spub) {
		//--
		const prix = String(_C$.param_Prix);
		const p = prime(prix);
		const shad = powm(spub, csec, p);
		//_p$.log(_N$, 'getCliShad', 'p:', p, 'csec:', csec, 'spub:', spub, 'shad:', shad);
		//--
		return shadizer(shad);
		//--
	}; //END
	_C$.getCliShad = getCliShad; // export

	/*
	 * Get the DH partial data (cli) derived from any valid idz data
	 *
	 * @private internal use
	 *
	 * @memberof smartJ$DhKx
	 * @method getIdzShadData
	 * @static
	 *
	 * @param 	{String} idz			The idz
	 * @return 	{Object} 				The data object (shad)
	 */
	const getIdzShadData = function(idz) {
		//--
		idz = _Utils$.stringPureVal(idz, true);
		//--
		let err = '';
		let cliShad = '';
		if(idz == '') {
			err = 'Empty Idz';
		} else {
			let arr = idxtizer(idz);
			if(arr.err && (arr.err != '')) {
				err = String(arr.err);
			} else {
				cliShad = _Utils$.stringPureVal(getCliShad(arr.csec, arr.spub), true);
				if(cliShad == '') {
					err = 'Empty Shad';
				} //end if
			} //end if
		} //end if
		//--
		return {
			type: 'IdzShadData',
			mode: String(getMode()),
			size: String(_C$.param_Size),
			prix: String(_C$.param_Prix),
			shad: String(cliShad),
			err:  String(err),
		};
		//--
	}; //END
	_C$.getIdzShadData = getIdzShadData; // export

	//== [DEVELOPMENT ONLY]

	/*
	 * This method returns the full DhKx exchange data
	 * This should be used just for development purposes only ... !!!
	 *
	 * @private development mode only
	 *
	 * @memberof smartJ$DhKx
	 * @method getData
	 * @static
	 *
	 * @return 	{Object} 				The data object
	 */
	const getData = function() {
		//--
		const mode = String(getMode());
		//--
		if(_C$.param_DevMode !== true) {
			const errMsg = 'Development Mode is N/A';
			_p$.warn(_N$, 'getData', errMsg);
			return {
				err: String(errMsg)
			};
		} //end if
		_p$.log(_N$, '[NOTICE]: getData: development mode is enabled', '[' + mode + ']');
		//--
		const basegen = getBaseGen(_C$.param_Size);
		//--
		const srvData = getSrvData(basegen);
		const cliData = getCliData(basegen);
		const srvShad = getSrvShad(srvData.sec, cliData.pub);
		const cliShad = getCliShad(cliData.sec, srvData.pub);
		//--
		let err = '';
		if((srvShad == undefined) || (srvShad == '') || (srvShad == '0') || (srvShad !== cliShad)) {
			err = 'Shad Mismatch';
		} //end if
		//--
		return {
			type: 'Data',
			mode: String(mode),
			size: String(_C$.param_Size),
			prix: String(_C$.param_Prix),
			prim: String(prime(String(_C$.param_Prix))),
			basegen: String(basegen),
			srv: {
				sec: String(srvData.sec),
				pub: String(srvData.pub),
				shad: String(srvShad),
			},
			cli: {
				sec: String(cliData.sec),
				pub: String(cliData.pub),
				shad: String(cliShad),
			},
			idz: String(idatizer(cliData.sec, srvData.pub)),
			err: String(err),
		};
		//--
	}; //END
	_C$.getData = getData; // export

	//== [PRIVATES]

	// iddatizer
	const idxtizer = function(idz) {
		//--
		idz = _Utils$.stringPureVal(idz, true);
		if(idz == '') {
			return {
				'err': 'Invalid IDZ (1)'
			};
		} //end if
		//--
		if(!_Utils$.stringContains(idz, '!')) {
			return {
				'err': 'Invalid IDZ (2)'
			};
		} //end if
		//--
		const arr = idz.split('!');
		if(arr.length !== 3) {
			return {
				'err': 'Invalid IDZ (3)'
			};
		} //end if
		const pfx = 'dH.';
		const ver = 'v1';
		let sig = '';
		let mod = '0';
		if(_C$.param_UseBigInt === true) {
			sig = 'iHg.';
			mod = '1';
		} else {
			sig = 'i64.';
			mod = '2';
		} //end if else
		if(arr[0] !== pfx+sig+ver) {
			return {
				'err': 'Invalid IDZ (4.'+mod+')'
			};
		} //end if
		//--
		arr[1] = _Utils$.stringPureVal(_Ba$eConv.b64s_dec(arr[1]), true);
		if(arr[1] == '') {
			return {
				'err': 'Invalid IDZ (5)'
			};
		} //end if
		//--
		arr[2] = _Utils$.stringPureVal(_Ba$eConv.b64s_dec(arr[2]), true);
		if(arr[2] == '') {
			return {
				'err': 'Invalid IDZ (6)'
			};
		} //end if
		//--
		const bk = _Utils$.stringTrim(_Ba$eConv.base_to_hex_convert(arr[2],85));
		if(bk == '') {
			return {
				'err': 'Invalid IDZ (7)'
			};
		} //end if
		arr[2] = _Utils$.stringTrim(String(_Utils$.stringTrim(_Utils$.hex2bin(bk))).substr(1));
		if(arr[2] == '') {
			return {
				'err': 'Invalid IDZ (8)'
			};
		} //end if
		//--
		arr[1] = _Utils$.stringPureVal(_Crypto$Blowfish.decrypt(String(arr[1]),_Ba$eConv.base_from_hex_convert(_Crypto$Hash.sha256('&='+bk+'#'),92)), true);
		if(arr[1] == '') {
			return {
				'err': 'Invalid IDZ (9)'
			};
		} //end if
		arr[1] = _Utils$.stringTrim(String(_Utils$.hex2bin(_Utils$.stringTrim(_Ba$eConv.base_to_hex_convert(arr[1],92)))).substr(1));
		if(arr[1] == '') {
			return {
				'err': 'Invalid IDZ (10)'
			};
		} //end if
		//--
		return {
			'csec': String(arr[1]),
			'spub': String(arr[2]),
		};
		//--
	};

	// iddatizer
	const idatizer = function(csec, spub) {
		//--
		let shd = 'dH';
		//--
		if(_C$.param_UseBigInt === true) {
			shd += '.iHg';
		} else {
			shd += '.i64';
		} //end if else
		//--
		const bk = _Utils$.bin2hex('@'+spub);
		//--
		return String(shd+'.v1!'+_Ba$eConv.b64s_enc(_Crypto$Blowfish.encrypt(_Ba$eConv.base_from_hex_convert(_Utils$.bin2hex('$'+csec),92),_Ba$eConv.base_from_hex_convert(_Crypto$Hash.sha256('&='+bk+'#'),92)))+'!'+_Ba$eConv.b64s_enc(_Ba$eConv.base_from_hex_convert(bk,85)));
		//--
	}; //END

	// hexfixer
	const evenhexlen = function(shx) {
		//--
		shx = _Utils$.stringPureVal(shx, true);
		//--
		const len = shx.length;
		if(len <= 0) {
			shx = '00'; // this should not happen but anyway, it have to be fixed just in the case
		} else if((len % 2) !== 0) {
			shx = '0' + shx; // even zeros padding
		} //end if
		//--
		return String(shx);
		//--
	}; //END

	// shaddowizer
	const shadizer = function(shad) {
		//--
		let shr = '';
		//--
		if(_C$.param_UseBigInt === true) {
			const shx = String(evenhexlen(shad.toString(16)));
			shr = _Ba$eConv.base_from_hex_convert(shx, 92);
		} else {
			const shx = String(evenhexlen(shad.toString(16)));
			shr = _Ba$eConv.base_from_hex_convert(shx, 85)+"'"+_Ba$eConv.base_from_hex_convert(shx, 62)+"'"+_Ba$eConv.base_from_hex_convert(shx, 92)+"'"+_Ba$eConv.base_from_hex_convert(shx, 58);
		} //end if else
		//--
		return String(shr);
		//--
	}; //END

	// randomizer
	const rng = (size) => {
		//--
		//_p$.log(_N$, 'rng', 'param_UseBigInt', _C$.param_UseBigInt);
		if(_C$.param_UseBigInt === true) {
			return rngBigint(size);
		} else {
			return rngInt64(size);
		} //end if else
		//--
	}; //END

	// pwr deriv by prim
	const powm = (a, b, pri) => {
		//--
		//_p$.log(_N$, 'powm', 'param_UseBigInt', _C$.param_UseBigInt);
		if(_C$.param_UseBigInt === true) {
			return powmBigint(a, b, pri);
		} else {
			return powmInt64(a, b, pri);
		} //end if else
		//--
	}; //END

	// primes ...
	const prime = (prix) => {
		//--
		//_p$.log(_N$, 'prime', 'param_UseBigInt', _C$.param_UseBigInt);
		if(_C$.param_UseBigInt === true) {
			return primeBigint(prix);
		} else {
			return primeInt64(prix);
		} //end if else
		//--
	}; //END

	//== [SPECIFIC PRIVATES: Int64 and BigInt]

	// Int64 randomizer
	const rngInt64 = function(size) {
		//--
		size = _Utils$.stringPureVal(size, true);
		if((size === '') || (size === 'default')) {
			size = 24;
		} //end if
		size = Math.ceil(size);
		switch(size) {
			case 12:
			case 16:
			case 24:
				break;
			default:
				size = 24;
				_p$.warn(_N$, 'rngInt64: Invalid Size Selection, using defaults:', size);
		} //end switch
		//_p$.log(_N$, 'rngInt64', 'size', size);
		//--
		let rnd = ~~(Math.random() * (Math.pow(2,size)-1)) >>> 0; // math rand can be 1 thus for safety using 2^52 (-4 as using 1000 as base) = 2^48 instead of 2^53 ; Javascript Number.MAX_SAFE_INTEGER is 2^53 - 1 ; the reasoning behind that number is that JavaScript uses double-precision floating-point format numbers as specified in IEEE 754 and can only safely represent integers between -(2^53 - 1) and 2^53 - 1 so need adjustement in this context
		if(rnd <= 0) {
			rnd = 1;
		} //end if
		//--
		return rnd;
		//--
	}; //END

	// BigInt randomizer
	const rngBigint = function(size) {
		//--
		size = _Utils$.stringPureVal(size, true);
		if((size === '') || (size === 'default')) {
			size = 16;
		} //end if
		size = Math.ceil(size);
		switch(size) {
			case 128:
			case 96:
			case 64:
			case 48:
			case 32:
			case 16:
			case 8:
				break;
			default:
				size = 16;
				_p$.warn(_N$, 'rngBigint: Invalid Size Selection, using defaults:', size);
		} //end switch
		//_p$.log(_N$, 'rngBigint', 'size', size);
		//--
		const randoms = new Uint32Array(size); // allocate space for four 32-bit numbers
	//	window.crypto.getRandomValues(randoms); // get random values (browser dependent)
		for(let i=0, l=randoms.length; i<l; i++) {
			randoms[i] = Math.floor(Math.random() * 256); // get random values (browser independent)
		} //end for
		//--
		return String(Array.from(randoms).map(elem => String(elem)).join('')); // join numbers together as string
		//--
	}; //END

	// Int64 pwr deriv by prim ; https://stackoverflow.com/questions/24677932/diffie-hellman-key-exchange-with-javascript-sometimes-wrong
	const powmInt64 = (a, b, pri) => {
		//--
		if(b <= 0) {
			return 1;
		} else if(b === 1) {
			return a % pri;
		} else if(b % 2 === 0) {
			return powmInt64((a * a) % pri, b / 2 | 0, pri) % pri;
		} else {
			return (powmInt64((a * a) % pri, b / 2 | 0, pri) * a) % pri;
		} //end if else
		//--
	}; //END

	// BigInt pwr deriv by prim
	const powmBigint = (a, b, pri) => { // must return only BigInt values ; BigInt will automatically trim off decimals as: 5n / 2n = 2n (not 2.5n)
		//--
		a = BigInt(a);
		b = BigInt(b);
		pri = BigInt(pri);
		if(b <= BigInt(0)) {
			return BigInt(1);
		} else if(b == BigInt(1)) {
			return a % pri;
		} else if(b % BigInt(2) == BigInt(0)) {
			return powmBigint((a * a) % pri, b / BigInt(2), pri) % pri;
		} else {
			return powmBigint((a * a) % pri, b / BigInt(2), pri) * a % pri;
		} //end if else
		//--
	}; //END

	// Int64 primes ...
	const primeInt64 = function(prix) {
		//--
		const primesInt64 = [ // max js safe int is: 9007199254740992 ; of which sqrt is: ~ 94906265 (length: 8)
			72419213, 54795931, 32926051, 21801887, 77635013, 25470191, 77639819, 42010253,
			33563273, 32792339, 15923857, 67022173, 84250253, 67680727, 63438329, 52164643,
			51603269, 61444631, 58831133, 55711141, 73596863, 48905489, 61642963, 53812273,
			16600799, 79158229, 56490361, 73391389, 64351751, 14227727, 40517299, 95234563,
			42913363, 63566527, 52338703, 80146337, 37597201, 93581269, 32547497, 75587359,
			26024821, 57042743, 13862969, 46496719, 42787387, 29830469, 59912407, 75206447,
			40343341, 72357113, 23434063, 24336373, 39422399, 12866611, 11592293, 83937899,
			79746883, 37997129, 76431193, 67774627, 72107393, 31363271, 30388361, 25149569,
			54104161, 50575709, 70327973, 54960077, 92119793, 80615231, 38967139, 65609657,
			66432673, 56145097, 73864853, 70708361, 23913011, 35283481, 58352201, 57881491,
			89206109, 70619069, 96913759, 66156679, 63395257, 70022237, 93547543, 10891057,
			75492367, 86902223, 33054397, 36325571, 49119293, 64100537, 31986431, 16636237,
		]; // 0x00 .. 0x5F
		//--
		prix = _Utils$.stringPureVal(prix, true);
		if((prix === '') || (prix === 'default')) {
			prix = -1;
		} //end if
		prix = Math.floor(prix);
		let px = primesInt64[47]; // 0x2F
		if((prix >= 0) && (prix < primesInt64.length)) {
			px = primesInt64[prix];
		} else if(prix !== -1) {
			_p$.warn(_N$, 'prime: Invalid Prime Selection (Int64), using defaults:', prix);
		} //end if
		//--
		return '0x' + px.toString(16);
		//--
	}; //END

	// BigInt primes ...
	const primeBigint = function(prix) {
		//--
		const primesBigint = {
			h017: 		'0x1141317432f7b89',
			h031: 		'0x6febe061005175e46c896e4079',
			h047: 		'0xf3f2b0ee30050c5f6bfcb9df1b9454e77bc3503',
			h061: 		'0x4771cfc3c2b8ad4561cb5437132e35e8398e8f956a2f2c94c51',
			h097: 		'0x426f09b2b25aba6bbcbf9ca5edb660b91d033440916732af9ae175a84afb665a25b392361c6952119',
			h127: 		'0x2c6121e6b14ecf756c083544de0e0933cac90dbeb6239905bfbec764527bbb4166ff832a2bcc3b4d6f634eddd30e40634adbbb5bfd',
			h257: 		'0x279e569032f0c7256218b58ad6418aa0e9436be424ab8f1431b1f9e6b5814e0ebda0ff65ef085d7e73fee51744dec07fe08c1a1cc65855630ca983927ca277406ac42094064387d65aeaa849f9bf449e04df8cb0e99a44b004ce0efca3386f1e82c078723cd265288d9a41',
			h232c1: 	'0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A63A3620FFFFFFFFFFFFFFFF',
			h309c2: 	'0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF',
			h617c14: 	'0xFFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF',
		};
		//--
		let px = null;
		prix = _Utils$.stringPureVal(prix, true);
		if(prix === '') {
			prix = 'default';
		} //end if
		switch(String(prix)) {
			case 'h017':
			case 'h031':
			case 'h047':
			case 'h061':
			case 'h097':
			case 'h127':
			case 'h257':
			case 'h232c1':
			case 'h309c2':
			case 'h617c14':
				px = primesBigint[String(prix)];
				break;
			default:
				if(prix !== 'default') {
					_p$.warn(_N$, 'prime: Invalid Prime Selection (Bigint), using defaults:', prix);
				} //end if
				px = primesBigint['h127'];
		} //end switch
		//--
		return String(px);
		//--
	}; //END

}}; //END CLASS

smartJ$DhKx.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$DhKx = smartJ$DhKx; // global export
} //end if

//=======================================
//=======================================

//==================================================================
//==================================================================

// #END
