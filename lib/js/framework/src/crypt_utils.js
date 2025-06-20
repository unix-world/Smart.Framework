
// [LIB - Smart.Framework / JS / Crypto Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: smartJ$Utils
// CONTAINS:
// 	* BaseConvert: 32/36/58/62/64s/85/92 ; 64s/64/64s ; E64s / D64s
// 	* CryptoHash: CRC32B, MD5, SHA1, SHA224, SHA256, SHA384, SHA512, SHA3-224, SHA3-256, SHA3-384, SHA3-512, HMAC (Hex / B64) ; PBKDF2 (Hex / B92)
// 	* DhKx: Srv/Cli :: Shad
// 	* CipherCrypto: Twofish / Blowfish :: enc/dec :: CBC
// r.20250304

//==================================================================
// The code is released under the BSD License.
//  Copyright (c) unix-world.org
// The file contains portions of code from:
//	JS-CRC32B: github.com/fastest963/js-crc32, Copyright (c) James Hartig, License: MIT
//	CryptoJS: code.google.com/p/crypto-js, (c) Jeff Mott, License BSD
//	JS-Twofish: github.com/wouldgo/twofish, Copyright (c) Dario Andrei, License: MIT
//	JS-Blowfish: github.com/agorlov/javascript-blowfish, Copyright (c) Alexandr Gorlov, License: MIT
//==================================================================

//================== [ES6]


//=======================================
// CLASS :: Base Convert enc/dec
//=======================================

/**
 * CLASS :: Smart Base Convert (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 *
 * @throws 			console.error
 *
 * @desc Base Convert for JavaScript: Convert from/to Hex: base32, base36, base58, base62, base85, base92 ; Convert from/to B64: base64s ; Encode/Decode: base64s
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
 * @class smartJ$BaseConv
 * @static
 * @frozen
 *
 */
const smartJ$BaseConv = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$BaseConv';

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

	/*
	 * Converts from Base64 to Base64s / Base64u (Base64 Safe URL / Base64 URL) by replacing characters as follows:
	 * '+' with '-',
	 * '/' with '_',
	 * '=' with '.' (if padding) or '' (if no padding)
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseConv
	 * @method b64_to_b64s
	 * @static
	 *
	 * @param {String} 	str 				The Base64 string to be converted
	 * @param {Boolean} pad 				Use padding ; Default is TRUE ; if set to FALSE will not use padding (`=`:`` instead of `=`:`.`)
	 * @return {String} 					The Base64s or Base64u (if no padding) encoded string
	 */
	const b64_to_b64s = function(str, pad=true) {
		str = _Utils$.stringPureVal(str, true);
		if(str == '') {
			return '';
		} //end if
		let dot = '.';
		if(pad === false) {
			dot = '';
		} //end if
		return str.replace(/\+/g, '-').replace(/\//g, '_').replace(/\=/g, String(dot || ''));
	};
	_C$.b64_to_b64s = b64_to_b64s; // export

	/*
	 * Converts from Base64s / Base64u (Base64 Safe URL / Base64 URL) to Base64 by replacing characters as follows:
	 * '-' with '+',
	 * '_' with '/',
	 * '.' with '=' (optional, if padded, otherwise ignore)
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseConv
	 * @method b64s_to_b64
	 * @static
	 *
	 * @param {String} 	str 				The Base64s or Base64u (if no padding) string to be converted
	 * @return {String} 					The Base64 string
	 */
	const b64s_to_b64 = function(str) {
		str = _Utils$.stringPureVal(str, true);
		if(str == '') {
			return '';
		} //end if
		return str.replace(/\-/g, '+').replace(/_/g, '/').replace(/\./g, '=');
	};
	_C$.b64s_to_b64 = b64s_to_b64; // export

	/*
	 * Returns the Base64s / Base64u (Base64 Safe URL / Base64 URL) Modified Encoding from a string by replacing the standard base64 encoding as follows:
	 * '+' with '-',
	 * '/' with '_',
	 * '=' with '.' (if padding) or '' (if no padding)
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseConv
	 * @method b64s_enc
	 * @static
	 *
	 * @param {String} 	str 				The string to be encoded
	 * @param {Boolean} bin 				Set to TRUE if the string is binary to avoid re-encode to UTF-8
	 * @param {Boolean} pad 				Use padding ; Default is TRUE ; if set to FALSE will not use padding (`=`:`` instead of `=`:`.`)
	 * @return {String} 					The Base64s or Base64u (if no padding) encoded string
	 */
	const b64s_enc = function(str, bin=false, pad=true) {
		//--
		return b64_to_b64s(_Utils$.b64Enc(str, bin), pad);
		//--
	}; //END
	_C$.b64s_enc = b64s_enc; // export

	/*
	 * Returns the Decoded string from Base64s / Base64u (Base64 Safe URL / Base64 URL) Encoding by replacing back as follows before applying the standard base64 decoding:
	 * '-' with '+',
	 * '_' with '/',
	 * '.' with '=' (optional, if padded, otherwise ignore)
	 *
	 * @private internal use only
	 *
	 * @memberof smartJ$BaseConv
	 * @method b64s_dec
	 * @static
	 *
	 * @param STRING 	enc 				The Base64s or Base64u (if no padding) encoded string
	 * @param {Boolean} bin 				Set to TRUE if the string is binary to avoid re-decode as UTF-8
	 * @return STRING 						The decoded string
	 *
	 */
	const b64s_dec = function(enc, bin=false) {
		//--
		return _Utils$.b64Dec(b64s_to_b64(enc), bin);
		//--
	}; //END
	_C$.b64s_dec = b64s_dec; // export

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

smartJ$BaseConv.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$BaseConv = smartJ$BaseConv; // global export
} //end if


//=======================================
// CLASS :: Hash Crypto
//=======================================

// This class contains portions of code from:
// 	* CRC32B: github.com/fastest963/js-crc32, Copyright (c) James Hartig, License: MIT
// 	* CryptoJS: code.google.com/p/crypto-js, (c) Jeff Mott, License BSD
/**
 * CLASS :: Smart CryptoHash (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$BaseConv
 *
 * @throws 			console.error
 *
 * @desc Crypto Hash for JavaScript: CRC32B :: (Hex / B36) ; MD5 / SHA1 / SHA224 / SHA256 / SHA384 / SHA512 / SHA3-224 / SHA3-256 / SHA3-384 / SHA3-512 / HMAC :: (Hex / B64) ; PBKDF2 :: (Hex / B92)
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
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
	const _Ba$eConv = smartJ$BaseConv;

	//==== [ CRC32B SUM: START ]

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
	 * @return {String} The CRC32B hash of the string (Hex or B36)
	 */
	const crc32b = function(s, b36=false) {
		//--
		b36 = !! b36;
		//--
		if(b36 === true) {
			//--
			return String(crc32b_b36(s));
			//--
		} else {
			//--
			return String(crc32b_hex(s));
			//--
		} //end if else
		//--
	}; //END
	_C$.crc32b = crc32b; // export

	//== PRIVATE CRC32

	const crc32b_hex = function(s) { // returns CRC32B Hex
		//--
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8Enc(s); // make it unicode
		//--
		let crc = String(crc32b_core(s).toString(16)); // hex
		//-- unixman fix (pad with leading zeroes)
		crc = crc.padStart(8, '0');
		//--
		return String(crc);
		//--
	}; //END

	const crc32b_b36 = function(s) { // returns CRC32B B36
		//--
		s = _Utils$.stringPureVal(s); // cast to string, don't trim ! need to preserve the value
		s = _Utils$.utf8Enc(s); // make it unicode
		//--
		let crc = String(crc32b_core(s).toString(36)); // b36
		//-- unixman fix (pad with leading zeroes)
		crc = crc.padStart(7, '0');
		//--
		return String(crc);
		//--
	}; //END

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

	const crc32b_core = function(s) {
		//--
		const crypt_strtoarr = (str) => {
			const l = str.length;
			let bytes = new Array(l);
			for(let i=0; i<l; i++) {
				bytes[i] = str.charCodeAt(i);
			} //end for
			return bytes;
		};
		//--
		crc32b_init_tbl();
		//--
		let values = crypt_strtoarr(s), l = values.length;
		let isObjects = (Array.isArray(values) && (values.length > 0) && (typeof(values[0]) === 'object'));
		let crc = -1, id = 0;
		for(let i=0; i<l; i++) {
			id = isObjects ? (values[i].id >>> 0) : values[i];
			crc = CRC32B_TABLE[(crc ^ id) & 0xFF] ^ (crc >>> 8);
		} //end for
		crc = (~crc >>> 0); // bitflip then cast to 32-bit unsigned
		//--
		return crc;
		//--
	}; //END

	//== [ CRC32B SUM: #END ]

	//================================================================================
	//-- CryptoJS: START: js-crypto-uxm.js

	// ===== [#]

	// # JS Package: js-crypto-uxm.js :: #START#
	// Included Files: core.js x64-core.js hmac.js sha512.js sha384.js sha256.js sha224.js sha1.js md5.js sha3.js pbkdf2.js #

	// ### DO NOT EDIT THIS FILE AS IT WILL BE OVERWRITTEN EACH TIME THE INCLUDED SCRIPTS WILL CHANGE !!! ###


	//== # cryptojs-uxm/core.js @patch: r.uxm.20231117
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	const CryptoJS = (function(Math, undefined) {

		// ES6
		// bug-fixes: unixman:
		//	* out of context variables, after using `let` or `const` instead of `var` (ES6) migration
		//	* fix HMAC Create Helper (for SHA3 was broken, ignoring the length param)

		/*
		 * CryptoJS namespace.
		 */
		const C = {};

		/*
		 * Library namespace.
		 */
		const C_lib = C.lib = {};

		/*
		 * Base object for prototypal inheritance.
		 */
		const Base = C_lib.Base = (function () {
			const F = function() {};

			return {
				/*
				 * Creates a new object that inherits from this object.
				 *
				 * @static
				 * @param {Object} overrides Properties to copy into the new object.
				 * @return {Object} The new object.
				 *
				 * @example
				 *     let MyType = CryptoJS.lib.Base.extend({
				 *         field: 'value',
				 *
				 *         method: function () {
				 *         }
				 *     });
				 */
				extend: function (overrides) {
					// Spawn
					F.prototype = this;
					const subtype = new F();

					// Augment
					if(overrides) {
						subtype.mixIn(overrides);
					}

					// Create default initializer
					if(!subtype.hasOwnProperty('init')) {
						subtype.init = function () {
							subtype.$super.init.apply(this, arguments);
						};
					}

					// Initializer's prototype is the subtype object
					subtype.init.prototype = subtype;

					// Reference supertype
					subtype.$super = this;

					return subtype;
				},

				/*
				 * Extends this object and runs the init method.
				 * Arguments to create() will be passed to init().
				 *
				 * @static
				 * @return {Object} The new object.
				 *
				 * @example
				 *     let instance = MyType.create();
				 */
				create: function () {
					const instance = this.extend();
					instance.init.apply(instance, arguments);

					return instance;
				},

				/*
				 * Initializes a newly created object.
				 * Override this method to add some logic when your objects are created.
				 *
				 * @example
				 *     let MyType = CryptoJS.lib.Base.extend({
				 *         init: function () {
				 *             // ...
				 *         }
				 *     });
				 */
				init: function () {
				},

				/*
				 * Copies properties into this object.
				 *
				 * @param {Object} properties The properties to mix in.
				 *
				 * @example
				 *     MyType.mixIn({
				 *         field: 'value'
				 *     });
				 */
				mixIn: function (properties) {
					for(let propertyName in properties) {
						if(properties.hasOwnProperty(propertyName)) {
							this[propertyName] = properties[propertyName];
						}
					}

					// IE won't copy toString using the loop above
					if(properties.hasOwnProperty('toString')) {
						this.toString = properties.toString;
					}
				},

				/*
				 * Creates a copy of this object.
				 *
				 * @return {Object} The clone.
				 *
				 * @example
				 *     let clone = instance.clone();
				 */
				clone: function () {
					return this.init.prototype.extend(this);
				}
			};
		}());

		/*
		 * An array of 32-bit words.
		 *
		 * @property {Array} words The array of 32-bit words.
		 * @property {number} sigBytes The number of significant bytes in this word array.
		 */
		const WordArray = C_lib.WordArray = Base.extend({
			/*
			 * Initializes a newly created word array.
			 *
			 * @param {Array} words (Optional) An array of 32-bit words.
			 * @param {number} sigBytes (Optional) The number of significant bytes in the words.
			 *
			 * @example
			 *     let wordArray = CryptoJS.lib.WordArray.create();
			 *     let wordArray = CryptoJS.lib.WordArray.create([0x00010203, 0x04050607]);
			 *     let wordArray = CryptoJS.lib.WordArray.create([0x00010203, 0x04050607], 6);
			 */
			init: function (words, sigBytes) {
				words = this.words = words || [];

				if(sigBytes != undefined) {
					this.sigBytes = sigBytes;
				} else {
					this.sigBytes = words.length * 4;
				}
			},

			/*
			 * Converts this word array to a string.
			 *
			 * @param {Encoder} encoder (Optional) The encoding strategy to use. Default: CryptoJS.enc.Hex
			 *
			 * @return {string} The stringified word array.
			 *
			 * @example
			 *     let string = wordArray + '';
			 *     let string = wordArray.toString();
			 *     let string = wordArray.toString(CryptoJS.enc.Utf8);
			 */
			toString: function (encoder) {
				return (encoder || Hex).stringify(this);
			},

			/*
			 * Concatenates a word array to this word array.
			 *
			 * @param {WordArray} wordArray The word array to append.
			 * @return {WordArray} This word array.
			 *
			 * @example
			 *     wordArray1.concat(wordArray2);
			 */
			concat: function (wordArray) {
				// Shortcuts
				let thisWords = this.words;
				let thatWords = wordArray.words;
				let thisSigBytes = this.sigBytes;
				let thatSigBytes = wordArray.sigBytes;

				// Clamp excess bits
				this.clamp();

				// Concat
				if(thisSigBytes % 4) {
					// Copy one byte at a time
					for(let i = 0; i < thatSigBytes; i++) {
						let thatByte = (thatWords[i >>> 2] >>> (24 - (i % 4) * 8)) & 0xff;
						thisWords[(thisSigBytes + i) >>> 2] |= thatByte << (24 - ((thisSigBytes + i) % 4) * 8);
					}
				//-- start fix, by unixman from: github.com/brix/crypto-js
				} else {
			//	} else if(thatWords.length > 0xffff) {
					// Copy one word at a time
					for(let i = 0; i < thatSigBytes; i += 4) {
						thisWords[(thisSigBytes + i) >>> 2] = thatWords[i >>> 2];
					}
				/*
				} else {
					// Copy all words at once
					thisWords.push.apply(thisWords, thatWords);
				*/
				//-- #end fix
				}
				this.sigBytes += thatSigBytes;

				// Chainable
				return this;
			},

			/*
			 * Removes insignificant bits.
			 *
			 * @example
			 *     wordArray.clamp();
			 */
			clamp: function () {
				// Shortcuts
				let words = this.words;
				let sigBytes = this.sigBytes;

				// Clamp
				words[sigBytes >>> 2] &= 0xffffffff << (32 - (sigBytes % 4) * 8);
				words.length = Math.ceil(sigBytes / 4);
			},

			/*
			 * Creates a copy of this word array.
			 *
			 * @return {WordArray} The clone.
			 *
			 * @example
			 *     let clone = wordArray.clone();
			 */
			clone: function () {
				let clone = Base.clone.call(this);
				clone.words = this.words.slice(0);

				return clone;
			},

			/*
			 * Creates a word array filled with random bytes.
			 *
			 * @static
			 * @param {number} nBytes The number of random bytes to generate.
			 * @return {WordArray} The random word array.
			 *
			 * @example
			 *     let wordArray = CryptoJS.lib.WordArray.random(16);
			 */
			random: function (nBytes) {
				let words = [];
				for(let i = 0; i < nBytes; i += 4) {
					words.push((Math.random() * 0x100000000) | 0);
				}

				return new WordArray.init(words, nBytes);
			}
		});

		/*
		 * Encoder namespace.
		 */
		const C_enc = C.enc = {};

		/*
		 * Hex encoding strategy.
		 */
		const Hex = C_enc.Hex = {
			/*
			 * Converts a word array to a hex string.
			 *
			 * @static
			 * @param {WordArray} wordArray The word array.
			 * @return {string} The hex string.
			 *
			 * @example
			 *     let hexString = CryptoJS.enc.Hex.stringify(wordArray);
			 */
			stringify: function (wordArray) {
				// Shortcuts
				let words = wordArray.words;
				let sigBytes = wordArray.sigBytes;

				// Convert
				let hexChars = [];
				for(let i = 0; i < sigBytes; i++) {
					let bite = (words[i >>> 2] >>> (24 - (i % 4) * 8)) & 0xff;
					hexChars.push((bite >>> 4).toString(16));
					hexChars.push((bite & 0x0f).toString(16));
				}

				return hexChars.join('');
			},

			/*
			 * Converts a hex string to a word array.
			 *
			 * @static
			 * @param {string} hexStr The hex string.
			 * @return {WordArray} The word array.
			 *
			 * @example
			 *     let wordArray = CryptoJS.enc.Hex.parse(hexString);
			 */
			parse: function (hexStr) {
				// Shortcut
				let hexStrLength = hexStr.length;

				// Convert
				let words = [];
				for(let i = 0; i < hexStrLength; i += 2) {
					words[i >>> 3] |= parseInt(hexStr.substr(i, 2), 16) << (24 - (i % 8) * 4);
				}

				return new WordArray.init(words, hexStrLength / 2);
			}
		};

		/*
		 * Latin1 encoding strategy.
		 */
		const Latin1 = C_enc.Latin1 = {
			/*
			 * Converts a word array to a Latin1 string.
			 *
			 * @static
			 * @param {WordArray} wordArray The word array.
			 * @return {string} The Latin1 string.
			 *
			 * @example
			 *     let latin1String = CryptoJS.enc.Latin1.stringify(wordArray);
			 */
			stringify: function (wordArray) {
				// Shortcuts
				let words = wordArray.words;
				let sigBytes = wordArray.sigBytes;

				// Convert
				let latin1Chars = [];
				for(let i = 0; i < sigBytes; i++) {
					let bite = (words[i >>> 2] >>> (24 - (i % 4) * 8)) & 0xff;
					latin1Chars.push(String.fromCharCode(bite));
				}

				return latin1Chars.join('');
			},

			/*
			 * Converts a Latin1 string to a word array.
			 *
			 * @static
			 * @param {string} latin1Str The Latin1 string.
			 * @return {WordArray} The word array.
			 *
			 * @example
			 *     let wordArray = CryptoJS.enc.Latin1.parse(latin1String);
			 */
			parse: function (latin1Str) {
				// Shortcut
				let latin1StrLength = latin1Str.length;

				// Convert
				let words = [];
				for(let i = 0; i < latin1StrLength; i++) {
					words[i >>> 2] |= (latin1Str.charCodeAt(i) & 0xff) << (24 - (i % 4) * 8);
				}

				return new WordArray.init(words, latin1StrLength);
			}
		};

		/*
		 * UTF-8 encoding strategy.
		 */
		const Utf8 = C_enc.Utf8 = {
			/*
			 * Converts a word array to a UTF-8 string.
			 *
			 * @static
			 * @param {WordArray} wordArray The word array.
			 * @return {string} The UTF-8 string.
			 *
			 * @example
			 *     let utf8String = CryptoJS.enc.Utf8.stringify(wordArray);
			 */
			stringify: function (wordArray) {
				try {
					return decodeURIComponent(escape(Latin1.stringify(wordArray)));
				} catch (e) {
					throw new Error('Malformed UTF-8 data');
				}
			},

			/*
			 * Converts a UTF-8 string to a word array.
			 *
			 * @static
			 * @param {string} utf8Str The UTF-8 string.
			 * @return {WordArray} The word array.
			 *
			 * @example
			 *     let wordArray = CryptoJS.enc.Utf8.parse(utf8String);
			 */
			parse: function (utf8Str) {
				return Latin1.parse(unescape(encodeURIComponent(utf8Str)));
			}
		};

		/*
		 * Abstract buffered block algorithm template.
		 *
		 * The property blockSize must be implemented in a concrete subtype.
		 *
		 * @property {number} _minBufferSize The number of blocks that should be kept unprocessed in the buffer. Default: 0
		 */
		const BufferedBlockAlgorithm = C_lib.BufferedBlockAlgorithm = Base.extend({
			/*
			 * Resets this block algorithm's data buffer to its initial state.
			 *
			 * @example
			 *     bufferedBlockAlgorithm.reset();
			 */
			reset: function () {
				// Initial values
				this._data = new WordArray.init();
				this._nDataBytes = 0;
			},

			/*
			 * Adds new data to this block algorithm's buffer.
			 *
			 * @param {WordArray|string} data The data to append. Strings are converted to a WordArray using UTF-8.
			 *
			 * @example
			 *     bufferedBlockAlgorithm._append('data');
			 *     bufferedBlockAlgorithm._append(wordArray);
			 */
			_append: function (data) {
				// Convert string to WordArray, else assume WordArray already
				if(typeof data == 'string') {
					data = Utf8.parse(data);
				}

				// Append
				this._data.concat(data);
				this._nDataBytes += data.sigBytes;
			},

			/*
			 * Processes available data blocks.
			 * This method invokes _doProcessBlock(offset), which must be implemented by a concrete subtype.
			 *
			 * @param {boolean} doFlush Whether all blocks and partial blocks should be processed.
			 * @return {WordArray} The processed data.
			 *
			 * @example
			 *     let processedData = bufferedBlockAlgorithm._process();
			 *     let processedData = bufferedBlockAlgorithm._process(!!'flush');
			 */
			_process: function (doFlush) {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;
				let dataSigBytes = data.sigBytes;
				let blockSize = this.blockSize;
				let blockSizeBytes = blockSize * 4;

				// Count blocks ready
				let nBlocksReady = dataSigBytes / blockSizeBytes;
				if(doFlush) {
					// Round up to include partial blocks
					nBlocksReady = Math.ceil(nBlocksReady);
				} else {
					// Round down to include only full blocks,
					// less the number of blocks that must remain in the buffer
					nBlocksReady = Math.max((nBlocksReady | 0) - this._minBufferSize, 0);
				}

				// Count words ready
				let nWordsReady = nBlocksReady * blockSize;

				// Count bytes ready
				let nBytesReady = Math.min(nWordsReady * 4, dataSigBytes);

				// Process blocks
				let processedWords = new Array(); // fix by unixman
				if(nWordsReady) {
					for(let offset = 0; offset < nWordsReady; offset += blockSize) {
						// Perform concrete-algorithm logic
						this._doProcessBlock(dataWords, offset);
					}

					// Remove processed words
					processedWords = dataWords.splice(0, nWordsReady); // bug fix by unixman, it had to be initialized above, if did not enter this block, after this block was undefined !
					data.sigBytes -= nBytesReady;
				}

				// Return processed words
				return new WordArray.init(processedWords, nBytesReady);
			},

			/*
			 * Creates a copy of this object.
			 *
			 * @return {Object} The clone.
			 *
			 * @example
			 *     let clone = bufferedBlockAlgorithm.clone();
			 */
			clone: function () {
				let clone = Base.clone.call(this);
				clone._data = this._data.clone();

				return clone;
			},

			_minBufferSize: 0
		});

		/*
		 * Abstract hasher template.
		 *
		 * @property {number} blockSize The number of 32-bit words this hasher operates on. Default: 16 (512 bits)
		 */
		const Hasher = C_lib.Hasher = BufferedBlockAlgorithm.extend({
			/*
			 * Configuration options.
			 */
			cfg: Base.extend(),

			/*
			 * Initializes a newly created hasher.
			 *
			 * @param {Object} cfg (Optional) The configuration options to use for this hash computation.
			 *
			 * @example
			 *     let hasher = CryptoJS.algo.SHA256.create();
			 */
			init: function (cfg) {
				// Apply config defaults
				this.cfg = this.cfg.extend(cfg);

				// Set initial values
				this.reset();
			},

			/*
			 * Resets this hasher to its initial state.
			 *
			 * @example
			 *     hasher.reset();
			 */
			reset: function () {
				// Reset data buffer
				BufferedBlockAlgorithm.reset.call(this);

				// Perform concrete-hasher logic
				this._doReset();
			},

			/*
			 * Updates this hasher with a message.
			 *
			 * @param {WordArray|string} messageUpdate The message to append.
			 * @return {Hasher} This hasher.
			 *
			 * @example
			 *     hasher.update('message');
			 *     hasher.update(wordArray);
			 */
			update: function (messageUpdate) {
				// Append
				this._append(messageUpdate);

				// Update the hash
				this._process();

				// Chainable
				return this;
			},

			/*
			 * Finalizes the hash computation.
			 * Note that the finalize operation is effectively a destructive, read-once operation.
			 *
			 * @param {WordArray|string} messageUpdate (Optional) A final message update.
			 * @return {WordArray} The hash.
			 *
			 * @example
			 *     let hash = hasher.finalize();
			 *     let hash = hasher.finalize('message');
			 *     let hash = hasher.finalize(wordArray);
			 */
			finalize: function (messageUpdate) {
				// Final message update
				if(messageUpdate) {
					this._append(messageUpdate);
				}

				// Perform concrete-hasher logic
				let hash = this._doFinalize();

				return hash;
			},

			blockSize: 512/32,

			/*
			 * Creates a shortcut function to a hasher's object interface.
			 *
			 * @static
			 * @param {Hasher} hasher The hasher to create a helper for.
			 * @return {Function} The shortcut function.
			 *
			 * @example
			 *     let SHA256 = CryptoJS.lib.Hasher._createHelper(CryptoJS.algo.SHA256);
			 */
			_createHelper: function (hasher) {
				return function (message, cfg) {
					return new hasher.init(cfg).finalize(message);
				};
			},

			/*
			 * Creates a shortcut function to the HMAC's object interface.
			 *
			 * @static
			 * @param {Hasher} hasher The hasher to use in this HMAC helper.
			 * @return {Function} The shortcut function.
			 *
			 * @example
			 *     let HmacSHA256 = CryptoJS.lib.Hasher._createHmacHelper(CryptoJS.algo.SHA256);
			 */
			_createHmacHelper: function (hasher) {
			//	return function (message, key) {
				return function (message, key, cfg) { // fix by unixman
			//		return new C_algo.HMAC.init(hasher, key).finalize(message);
					return new C_algo.HMAC.init(hasher, key, cfg).finalize(message); // fix by unixman
				};
			}
		});

		/*
		 * Algorithm namespace.
		 */
		const C_algo = C.algo = {};

		return C;

	}(Math));
	//== # end

	//== # cryptojs-uxm/x64-core.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function(undefined) {

		// ES6
		// bug-fixes: -
		// clean: remove commented code for X64 ...

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const Base = C_lib.Base;
		const X32WordArray = C_lib.WordArray;

		/*
		 * x64 namespace.
		 */
		const C_x64 = C.x64 = {};

		/*
		 * A 64-bit word.
		 */
		const X64Word = C_x64.Word = Base.extend({
			/*
			 * Initializes a newly created 64-bit word.
			 *
			 * @param {number} high The high 32 bits.
			 * @param {number} low The low 32 bits.
			 *
			 * @example
			 *     let x64Word = CryptoJS.x64.Word.create(0x00010203, 0x04050607);
			 */
			init: function (high, low) {
				this.high = high;
				this.low = low;
			},
		});

		/*
		 * An array of 64-bit words.
		 *
		 * @property {Array} words The array of CryptoJS.x64.Word objects.
		 * @property {number} sigBytes The number of significant bytes in this word array.
		 */
		const X64WordArray = C_x64.WordArray = Base.extend({
			/*
			 * Initializes a newly created word array.
			 *
			 * @param {Array} words (Optional) An array of CryptoJS.x64.Word objects.
			 * @param {number} sigBytes (Optional) The number of significant bytes in the words.
			 *
			 * @example
			 *     let wordArray = CryptoJS.x64.WordArray.create();
			 *
			 *     let wordArray = CryptoJS.x64.WordArray.create([
			 *         CryptoJS.x64.Word.create(0x00010203, 0x04050607),
			 *         CryptoJS.x64.Word.create(0x18191a1b, 0x1c1d1e1f)
			 *     ]);
			 *
			 *     let wordArray = CryptoJS.x64.WordArray.create([
			 *         CryptoJS.x64.Word.create(0x00010203, 0x04050607),
			 *         CryptoJS.x64.Word.create(0x18191a1b, 0x1c1d1e1f)
			 *     ], 10);
			 */
			init: function (words, sigBytes) {
				words = this.words = words || [];
				if(sigBytes != undefined) {
					this.sigBytes = sigBytes;
				} else {
					this.sigBytes = words.length * 8;
				}
			},

			/*
			 * Converts this 64-bit word array to a 32-bit word array.
			 *
			 * @return {CryptoJS.lib.WordArray} This word array's data as a 32-bit word array.
			 *
			 * @example
			 *     let x32WordArray = x64WordArray.toX32();
			 */
			toX32: function () {
				// Shortcuts
				let x64Words = this.words;
				let x64WordsLength = x64Words.length;
				// Convert
				let x32Words = [];
				for(let i = 0; i < x64WordsLength; i++) {
					let x64Word = x64Words[i];
					x32Words.push(x64Word.high);
					x32Words.push(x64Word.low);
				}
				return X32WordArray.create(x32Words, this.sigBytes);
			},

			/*
			 * Creates a copy of this word array.
			 *
			 * @return {X64WordArray} The clone.
			 *
			 * @example
			 *     let clone = x64WordArray.clone();
			 */
			clone: function () {
				let clone = Base.clone.call(this);
				// Clone "words" array
				let words = clone.words = this.words.slice(0);
				// Clone each X64Word object
				let wordsLength = words.length;
				for(let i = 0; i < wordsLength; i++) {
					words[i] = words[i].clone();
				}
				return clone;
			}
		});

	}());
	//== # end

	//== # cryptojs-uxm/hmac.js @patch: r.uxm.20231117
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// bug-fixes: unixman:
		//	* fix HMAC Init (for SHA3 was broken, ignoring the length param)

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const Base = C_lib.Base;
		const C_enc = C.enc;
		const Utf8 = C_enc.Utf8;
		const C_algo = C.algo;

		/*
		 * HMAC algorithm.
		 */
		const HMAC = C_algo.HMAC = Base.extend({
			/*
			 * Initializes a newly created HMAC.
			 *
			 * @param {Hasher} hasher The hash algorithm to use.
			 * @param {WordArray|string} key The secret key.
			 *
			 * @example
			 *     let hmacHasher = CryptoJS.algo.HMAC.create(CryptoJS.algo.SHA256, key, cfg);
			 */
		//	init: function (hasher, key) {
			init: function (hasher, key, cfg) { // fix by unixman
				// Init hasher
		//		hasher = this._hasher = new hasher.init();
				hasher = this._hasher = new hasher.init(cfg); // fix by unixman

				// Convert string to WordArray, else assume WordArray already
				if(typeof key == 'string') {
					key = Utf8.parse(key);
				}

				// Shortcuts
				let hasherBlockSize = hasher.blockSize;
				let hasherBlockSizeBytes = hasherBlockSize * 4;

				// Allow arbitrary length keys
				if(key.sigBytes > hasherBlockSizeBytes) {
					key = hasher.finalize(key);
				}

				// Clamp excess bits
				key.clamp();

				// Clone key for inner and outer pads
				let oKey = this._oKey = key.clone();
				let iKey = this._iKey = key.clone();

				// Shortcuts
				let oKeyWords = oKey.words;
				let iKeyWords = iKey.words;

				// XOR keys with pad constants
				for(let i = 0; i < hasherBlockSize; i++) {
					oKeyWords[i] ^= 0x5c5c5c5c;
					iKeyWords[i] ^= 0x36363636;
				}
				oKey.sigBytes = iKey.sigBytes = hasherBlockSizeBytes;

				// Set initial values
				this.reset();
			},

			/*
			 * Resets this HMAC to its initial state.
			 *
			 * @example
			 *     hmacHasher.reset();
			 */
			reset: function () {
				// Shortcut
				let hasher = this._hasher;

				// Reset
				hasher.reset();
				hasher.update(this._iKey);
			},

			/*
			 * Updates this HMAC with a message.
			 *
			 * @param {WordArray|string} messageUpdate The message to append.
			 * @return {HMAC} This HMAC instance.
			 *
			 * @example
			 *     hmacHasher.update('message');
			 *     hmacHasher.update(wordArray);
			 */
			update: function (messageUpdate) {
				this._hasher.update(messageUpdate);

				// Chainable
				return this;
			},

			/*
			 * Finalizes the HMAC computation.
			 * Note that the finalize operation is effectively a destructive, read-once operation.
			 *
			 * @param {WordArray|string} messageUpdate (Optional) A final message update.
			 * @return {WordArray} The HMAC.
			 *
			 * @example
			 *     let hmac = hmacHasher.finalize();
			 *     let hmac = hmacHasher.finalize('message');
			 *     let hmac = hmacHasher.finalize(wordArray);
			 */
			finalize: function (messageUpdate) {
				// Shortcut
				let hasher = this._hasher;

				// Compute HMAC
				let innerHash = hasher.finalize(messageUpdate);
				hasher.reset();
				let hmac = hasher.finalize(this._oKey.clone().concat(innerHash));

				return hmac;
			}
		});

	}());
	//== # end

	//== # cryptojs-uxm/sha512.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// bug-fixes: unixman:
		// 	* out of context variables, after using `let` or `const` instead of `var` (ES6) migration

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const Hasher = C_lib.Hasher;
		const C_x64 = C.x64;
		const X64Word = C_x64.Word;
		const X64WordArray = C_x64.WordArray;
		const C_algo = C.algo;

		const X64Word_create = function() {
			return X64Word.create.apply(X64Word, arguments);
		};

		// Constants
		const K = [
			X64Word_create(0x428a2f98, 0xd728ae22), X64Word_create(0x71374491, 0x23ef65cd),
			X64Word_create(0xb5c0fbcf, 0xec4d3b2f), X64Word_create(0xe9b5dba5, 0x8189dbbc),
			X64Word_create(0x3956c25b, 0xf348b538), X64Word_create(0x59f111f1, 0xb605d019),
			X64Word_create(0x923f82a4, 0xaf194f9b), X64Word_create(0xab1c5ed5, 0xda6d8118),
			X64Word_create(0xd807aa98, 0xa3030242), X64Word_create(0x12835b01, 0x45706fbe),
			X64Word_create(0x243185be, 0x4ee4b28c), X64Word_create(0x550c7dc3, 0xd5ffb4e2),
			X64Word_create(0x72be5d74, 0xf27b896f), X64Word_create(0x80deb1fe, 0x3b1696b1),
			X64Word_create(0x9bdc06a7, 0x25c71235), X64Word_create(0xc19bf174, 0xcf692694),
			X64Word_create(0xe49b69c1, 0x9ef14ad2), X64Word_create(0xefbe4786, 0x384f25e3),
			X64Word_create(0x0fc19dc6, 0x8b8cd5b5), X64Word_create(0x240ca1cc, 0x77ac9c65),
			X64Word_create(0x2de92c6f, 0x592b0275), X64Word_create(0x4a7484aa, 0x6ea6e483),
			X64Word_create(0x5cb0a9dc, 0xbd41fbd4), X64Word_create(0x76f988da, 0x831153b5),
			X64Word_create(0x983e5152, 0xee66dfab), X64Word_create(0xa831c66d, 0x2db43210),
			X64Word_create(0xb00327c8, 0x98fb213f), X64Word_create(0xbf597fc7, 0xbeef0ee4),
			X64Word_create(0xc6e00bf3, 0x3da88fc2), X64Word_create(0xd5a79147, 0x930aa725),
			X64Word_create(0x06ca6351, 0xe003826f), X64Word_create(0x14292967, 0x0a0e6e70),
			X64Word_create(0x27b70a85, 0x46d22ffc), X64Word_create(0x2e1b2138, 0x5c26c926),
			X64Word_create(0x4d2c6dfc, 0x5ac42aed), X64Word_create(0x53380d13, 0x9d95b3df),
			X64Word_create(0x650a7354, 0x8baf63de), X64Word_create(0x766a0abb, 0x3c77b2a8),
			X64Word_create(0x81c2c92e, 0x47edaee6), X64Word_create(0x92722c85, 0x1482353b),
			X64Word_create(0xa2bfe8a1, 0x4cf10364), X64Word_create(0xa81a664b, 0xbc423001),
			X64Word_create(0xc24b8b70, 0xd0f89791), X64Word_create(0xc76c51a3, 0x0654be30),
			X64Word_create(0xd192e819, 0xd6ef5218), X64Word_create(0xd6990624, 0x5565a910),
			X64Word_create(0xf40e3585, 0x5771202a), X64Word_create(0x106aa070, 0x32bbd1b8),
			X64Word_create(0x19a4c116, 0xb8d2d0c8), X64Word_create(0x1e376c08, 0x5141ab53),
			X64Word_create(0x2748774c, 0xdf8eeb99), X64Word_create(0x34b0bcb5, 0xe19b48a8),
			X64Word_create(0x391c0cb3, 0xc5c95a63), X64Word_create(0x4ed8aa4a, 0xe3418acb),
			X64Word_create(0x5b9cca4f, 0x7763e373), X64Word_create(0x682e6ff3, 0xd6b2b8a3),
			X64Word_create(0x748f82ee, 0x5defb2fc), X64Word_create(0x78a5636f, 0x43172f60),
			X64Word_create(0x84c87814, 0xa1f0ab72), X64Word_create(0x8cc70208, 0x1a6439ec),
			X64Word_create(0x90befffa, 0x23631e28), X64Word_create(0xa4506ceb, 0xde82bde9),
			X64Word_create(0xbef9a3f7, 0xb2c67915), X64Word_create(0xc67178f2, 0xe372532b),
			X64Word_create(0xca273ece, 0xea26619c), X64Word_create(0xd186b8c7, 0x21c0c207),
			X64Word_create(0xeada7dd6, 0xcde0eb1e), X64Word_create(0xf57d4f7f, 0xee6ed178),
			X64Word_create(0x06f067aa, 0x72176fba), X64Word_create(0x0a637dc5, 0xa2c898a6),
			X64Word_create(0x113f9804, 0xbef90dae), X64Word_create(0x1b710b35, 0x131c471b),
			X64Word_create(0x28db77f5, 0x23047d84), X64Word_create(0x32caab7b, 0x40c72493),
			X64Word_create(0x3c9ebe0a, 0x15c9bebc), X64Word_create(0x431d67c4, 0x9c100d4c),
			X64Word_create(0x4cc5d4be, 0xcb3e42b6), X64Word_create(0x597f299c, 0xfc657e2a),
			X64Word_create(0x5fcb6fab, 0x3ad6faec), X64Word_create(0x6c44198c, 0x4a475817)
		];

		// Reusable objects
		let W = [];
		(function () {
			for(let i = 0; i < 80; i++) {
				W[i] = X64Word_create();
			}
		}());

		/*
		 * SHA-512 hash algorithm.
		 */
		const SHA512 = C_algo.SHA512 = Hasher.extend({
			_doReset: function () {
				this._hash = new X64WordArray.init([
					new X64Word.init(0x6a09e667, 0xf3bcc908), new X64Word.init(0xbb67ae85, 0x84caa73b),
					new X64Word.init(0x3c6ef372, 0xfe94f82b), new X64Word.init(0xa54ff53a, 0x5f1d36f1),
					new X64Word.init(0x510e527f, 0xade682d1), new X64Word.init(0x9b05688c, 0x2b3e6c1f),
					new X64Word.init(0x1f83d9ab, 0xfb41bd6b), new X64Word.init(0x5be0cd19, 0x137e2179)
				]);
			},

			_doProcessBlock: function (M, offset) {
				// Shortcuts
				let H = this._hash.words;

				let H0 = H[0];
				let H1 = H[1];
				let H2 = H[2];
				let H3 = H[3];
				let H4 = H[4];
				let H5 = H[5];
				let H6 = H[6];
				let H7 = H[7];

				let H0h = H0.high;
				let H0l = H0.low;
				let H1h = H1.high;
				let H1l = H1.low;
				let H2h = H2.high;
				let H2l = H2.low;
				let H3h = H3.high;
				let H3l = H3.low;
				let H4h = H4.high;
				let H4l = H4.low;
				let H5h = H5.high;
				let H5l = H5.low;
				let H6h = H6.high;
				let H6l = H6.low;
				let H7h = H7.high;
				let H7l = H7.low;

				// Working variables
				let ah = H0h;
				let al = H0l;
				let bh = H1h;
				let bl = H1l;
				let ch = H2h;
				let cl = H2l;
				let dh = H3h;
				let dl = H3l;
				let eh = H4h;
				let el = H4l;
				let fh = H5h;
				let fl = H5l;
				let gh = H6h;
				let gl = H6l;
				let hh = H7h;
				let hl = H7l;

				// Rounds
				for(let i = 0; i < 80; i++) {
					// Shortcut
					let Wi = W[i];
					let Wih, Wil; // fix by unixman

					// Extend message
					if(i < 16) {
						Wih = Wi.high = M[offset + i * 2]     | 0;
						Wil = Wi.low  = M[offset + i * 2 + 1] | 0;
					} else {
						// Gamma0
						let gamma0x  = W[i - 15];
						let gamma0xh = gamma0x.high;
						let gamma0xl = gamma0x.low;
						let gamma0h  = ((gamma0xh >>> 1) | (gamma0xl << 31)) ^ ((gamma0xh >>> 8) | (gamma0xl << 24)) ^ (gamma0xh >>> 7);
						let gamma0l  = ((gamma0xl >>> 1) | (gamma0xh << 31)) ^ ((gamma0xl >>> 8) | (gamma0xh << 24)) ^ ((gamma0xl >>> 7) | (gamma0xh << 25));

						// Gamma1
						let gamma1x  = W[i - 2];
						let gamma1xh = gamma1x.high;
						let gamma1xl = gamma1x.low;
						let gamma1h  = ((gamma1xh >>> 19) | (gamma1xl << 13)) ^ ((gamma1xh << 3) | (gamma1xl >>> 29)) ^ (gamma1xh >>> 6);
						let gamma1l  = ((gamma1xl >>> 19) | (gamma1xh << 13)) ^ ((gamma1xl << 3) | (gamma1xh >>> 29)) ^ ((gamma1xl >>> 6) | (gamma1xh << 26));

						// W[i] = gamma0 + W[i - 7] + gamma1 + W[i - 16]
						let Wi7  = W[i - 7];
						let Wi7h = Wi7.high;
						let Wi7l = Wi7.low;

						let Wi16  = W[i - 16];
						let Wi16h = Wi16.high;
						let Wi16l = Wi16.low;

						Wil = gamma0l + Wi7l;
						Wih = gamma0h + Wi7h + ((Wil >>> 0) < (gamma0l >>> 0) ? 1 : 0);
						Wil = Wil + gamma1l;
						Wih = Wih + gamma1h + ((Wil >>> 0) < (gamma1l >>> 0) ? 1 : 0);
						Wil = Wil + Wi16l;
						Wih = Wih + Wi16h + ((Wil >>> 0) < (Wi16l >>> 0) ? 1 : 0);

						Wi.high = Wih;
						Wi.low  = Wil;
					}

					let chh  = (eh & fh) ^ (~eh & gh);
					let chl  = (el & fl) ^ (~el & gl);
					let majh = (ah & bh) ^ (ah & ch) ^ (bh & ch);
					let majl = (al & bl) ^ (al & cl) ^ (bl & cl);

					let sigma0h = ((ah >>> 28) | (al << 4))  ^ ((ah << 30)  | (al >>> 2)) ^ ((ah << 25) | (al >>> 7));
					let sigma0l = ((al >>> 28) | (ah << 4))  ^ ((al << 30)  | (ah >>> 2)) ^ ((al << 25) | (ah >>> 7));
					let sigma1h = ((eh >>> 14) | (el << 18)) ^ ((eh >>> 18) | (el << 14)) ^ ((eh << 23) | (el >>> 9));
					let sigma1l = ((el >>> 14) | (eh << 18)) ^ ((el >>> 18) | (eh << 14)) ^ ((el << 23) | (eh >>> 9));

					// t1 = h + sigma1 + ch + K[i] + W[i]
					let Ki  = K[i];
					let Kih = Ki.high;
					let Kil = Ki.low;

					let t1l, t1h; // fix by unixman
					t1l = hl + sigma1l;
					t1h = hh + sigma1h + ((t1l >>> 0) < (hl >>> 0) ? 1 : 0);
					t1l = t1l + chl;
					t1h = t1h + chh + ((t1l >>> 0) < (chl >>> 0) ? 1 : 0);
					t1l = t1l + Kil;
					t1h = t1h + Kih + ((t1l >>> 0) < (Kil >>> 0) ? 1 : 0);
					t1l = t1l + Wil;
					t1h = t1h + Wih + ((t1l >>> 0) < (Wil >>> 0) ? 1 : 0);

					// t2 = sigma0 + maj
					let t2l = sigma0l + majl;
					let t2h = sigma0h + majh + ((t2l >>> 0) < (sigma0l >>> 0) ? 1 : 0);

					// Update working variables
					hh = gh;
					hl = gl;
					gh = fh;
					gl = fl;
					fh = eh;
					fl = el;
					el = (dl + t1l) | 0;
					eh = (dh + t1h + ((el >>> 0) < (dl >>> 0) ? 1 : 0)) | 0;
					dh = ch;
					dl = cl;
					ch = bh;
					cl = bl;
					bh = ah;
					bl = al;
					al = (t1l + t2l) | 0;
					ah = (t1h + t2h + ((al >>> 0) < (t1l >>> 0) ? 1 : 0)) | 0;
				}

				// Intermediate hash value
				H0l = H0.low  = (H0l + al);
				H0.high = (H0h + ah + ((H0l >>> 0) < (al >>> 0) ? 1 : 0));
				H1l = H1.low  = (H1l + bl);
				H1.high = (H1h + bh + ((H1l >>> 0) < (bl >>> 0) ? 1 : 0));
				H2l = H2.low  = (H2l + cl);
				H2.high = (H2h + ch + ((H2l >>> 0) < (cl >>> 0) ? 1 : 0));
				H3l = H3.low  = (H3l + dl);
				H3.high = (H3h + dh + ((H3l >>> 0) < (dl >>> 0) ? 1 : 0));
				H4l = H4.low  = (H4l + el);
				H4.high = (H4h + eh + ((H4l >>> 0) < (el >>> 0) ? 1 : 0));
				H5l = H5.low  = (H5l + fl);
				H5.high = (H5h + fh + ((H5l >>> 0) < (fl >>> 0) ? 1 : 0));
				H6l = H6.low  = (H6l + gl);
				H6.high = (H6h + gh + ((H6l >>> 0) < (gl >>> 0) ? 1 : 0));
				H7l = H7.low  = (H7l + hl);
				H7.high = (H7h + hh + ((H7l >>> 0) < (hl >>> 0) ? 1 : 0));
			},

			_doFinalize: function () {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;

				let nBitsTotal = this._nDataBytes * 8;
				let nBitsLeft = data.sigBytes * 8;

				// Add padding
				dataWords[nBitsLeft >>> 5] |= 0x80 << (24 - nBitsLeft % 32);
				dataWords[(((nBitsLeft + 128) >>> 10) << 5) + 30] = Math.floor(nBitsTotal / 0x100000000);
				dataWords[(((nBitsLeft + 128) >>> 10) << 5) + 31] = nBitsTotal;
				data.sigBytes = dataWords.length * 4;

				// Hash final blocks
				this._process();

				// Convert hash to 32-bit word array before returning
				let hash = this._hash.toX32();

				// Return final computed hash
				return hash;
			},

			clone: function () {
				let clone = Hasher.clone.call(this);
				clone._hash = this._hash.clone();
				return clone;
			},

			blockSize: 1024/32
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA512('message');
		 *     let hash = CryptoJS.SHA512(wordArray);
		 */
		C.SHA512 = Hasher._createHelper(SHA512);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA512(message, key);
		 */
		C.HmacSHA512 = Hasher._createHmacHelper(SHA512);

	}());
	//== # end

	//== # cryptojs-uxm/sha384.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// bug-fixes: -

		// Shortcuts
		const C = CryptoJS;
		const C_x64 = C.x64;
		const X64Word = C_x64.Word;
		const X64WordArray = C_x64.WordArray;
		const C_algo = C.algo;
		const SHA512 = C_algo.SHA512;

		/*
		 * SHA-384 hash algorithm.
		 */
		const SHA384 = C_algo.SHA384 = SHA512.extend({
			_doReset: function () {
				this._hash = new X64WordArray.init([
					new X64Word.init(0xcbbb9d5d, 0xc1059ed8), new X64Word.init(0x629a292a, 0x367cd507),
					new X64Word.init(0x9159015a, 0x3070dd17), new X64Word.init(0x152fecd8, 0xf70e5939),
					new X64Word.init(0x67332667, 0xffc00b31), new X64Word.init(0x8eb44a87, 0x68581511),
					new X64Word.init(0xdb0c2e0d, 0x64f98fa7), new X64Word.init(0x47b5481d, 0xbefa4fa4)
				]);
			},

			_doFinalize: function () {
				let hash = SHA512._doFinalize.call(this);
				hash.sigBytes -= 16;
				return hash;
			}
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA384('message');
		 *     let hash = CryptoJS.SHA384(wordArray);
		 */
		C.SHA384 = SHA512._createHelper(SHA384);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA384(message, key);
		 */
		C.HmacSHA384 = SHA512._createHmacHelper(SHA384);

	}());
	//== # end

	//== # cryptojs-uxm/sha256.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function(Math) {

		// ES6
		// bug-fixes: -

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const WordArray = C_lib.WordArray;
		const Hasher = C_lib.Hasher;
		const C_algo = C.algo;

		// Initialization and round constants tables
		let H = [];
		let K = [];

		// Compute constants
		(function () {
			function isPrime(n) {
				let sqrtN = Math.sqrt(n);
				for(let factor = 2; factor <= sqrtN; factor++) {
					if(!(n % factor)) {
						return false;
					}
				}

				return true;
			}

			function getFractionalBits(n) {
				return ((n - (n | 0)) * 0x100000000) | 0;
			}

			let n = 2;
			let nPrime = 0;
			while(nPrime < 64) {
				if(isPrime(n)) {
					if(nPrime < 8) {
						H[nPrime] = getFractionalBits(Math.pow(n, 1 / 2));
					}
					K[nPrime] = getFractionalBits(Math.pow(n, 1 / 3));

					nPrime++;
				}

				n++;
			}
		}());

		// Reusable object
		let W = [];

		/*
		 * SHA-256 hash algorithm.
		 */
		const SHA256 = C_algo.SHA256 = Hasher.extend({
			_doReset: function () {
				this._hash = new WordArray.init(H.slice(0));
			},

			_doProcessBlock: function (M, offset) {
				// Shortcut
				let H = this._hash.words;

				// Working variables
				let a = H[0];
				let b = H[1];
				let c = H[2];
				let d = H[3];
				let e = H[4];
				let f = H[5];
				let g = H[6];
				let h = H[7];

				// Computation
				for(let i = 0; i < 64; i++) {
					if(i < 16) {
						W[i] = M[offset + i] | 0;
					} else {
						let gamma0x = W[i - 15];
						let gamma0  = ((gamma0x << 25) | (gamma0x >>> 7))  ^
									  ((gamma0x << 14) | (gamma0x >>> 18)) ^
									   (gamma0x >>> 3);

						let gamma1x = W[i - 2];
						let gamma1  = ((gamma1x << 15) | (gamma1x >>> 17)) ^
									  ((gamma1x << 13) | (gamma1x >>> 19)) ^
									   (gamma1x >>> 10);

						W[i] = gamma0 + W[i - 7] + gamma1 + W[i - 16];
					}

					let ch  = (e & f) ^ (~e & g);
					let maj = (a & b) ^ (a & c) ^ (b & c);

					let sigma0 = ((a << 30) | (a >>> 2)) ^ ((a << 19) | (a >>> 13)) ^ ((a << 10) | (a >>> 22));
					let sigma1 = ((e << 26) | (e >>> 6)) ^ ((e << 21) | (e >>> 11)) ^ ((e << 7)  | (e >>> 25));

					let t1 = h + sigma1 + ch + K[i] + W[i];
					let t2 = sigma0 + maj;

					h = g;
					g = f;
					f = e;
					e = (d + t1) | 0;
					d = c;
					c = b;
					b = a;
					a = (t1 + t2) | 0;
				}

				// Intermediate hash value
				H[0] = (H[0] + a) | 0;
				H[1] = (H[1] + b) | 0;
				H[2] = (H[2] + c) | 0;
				H[3] = (H[3] + d) | 0;
				H[4] = (H[4] + e) | 0;
				H[5] = (H[5] + f) | 0;
				H[6] = (H[6] + g) | 0;
				H[7] = (H[7] + h) | 0;
			},

			_doFinalize: function () {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;

				let nBitsTotal = this._nDataBytes * 8;
				let nBitsLeft = data.sigBytes * 8;

				// Add padding
				dataWords[nBitsLeft >>> 5] |= 0x80 << (24 - nBitsLeft % 32);
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 14] = Math.floor(nBitsTotal / 0x100000000);
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 15] = nBitsTotal;
				data.sigBytes = dataWords.length * 4;

				// Hash final blocks
				this._process();

				// Return final computed hash
				return this._hash;
			},

			clone: function () {
				let clone = Hasher.clone.call(this);
				clone._hash = this._hash.clone();
				return clone;
			}
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA256('message');
		 *     let hash = CryptoJS.SHA256(wordArray);
		 */
		C.SHA256 = Hasher._createHelper(SHA256);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA256(message, key);
		 */
		C.HmacSHA256 = Hasher._createHmacHelper(SHA256);

	}(Math));
	//== # end

	//== # cryptojs-uxm/sha224.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// bug-fixes: -

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const WordArray = C_lib.WordArray;
		const C_algo = C.algo;
		const SHA256 = C_algo.SHA256;

		/*
		 * SHA-224 hash algorithm.
		 */
		const SHA224 = C_algo.SHA224 = SHA256.extend({
			_doReset: function () {
				this._hash = new WordArray.init([
					0xc1059ed8, 0x367cd507, 0x3070dd17, 0xf70e5939,
					0xffc00b31, 0x68581511, 0x64f98fa7, 0xbefa4fa4
				]);
			},

			_doFinalize: function () {
				let hash = SHA256._doFinalize.call(this);
				hash.sigBytes -= 4;
				return hash;
			}
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA224('message');
		 *     let hash = CryptoJS.SHA224(wordArray);
		 */
		C.SHA224 = SHA256._createHelper(SHA224);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA224(message, key);
		 */
		C.HmacSHA224 = SHA256._createHmacHelper(SHA224);

	}());
	//== # end

	//== # cryptojs-uxm/sha1.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// bug-fixes: -

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const WordArray = C_lib.WordArray;
		const Hasher = C_lib.Hasher;
		const C_algo = C.algo;

		// Reusable object
		let W = [];

		/*
		 * SHA-1 hash algorithm.
		 */
		const SHA1 = C_algo.SHA1 = Hasher.extend({
			_doReset: function () {
				this._hash = new WordArray.init([
					0x67452301, 0xefcdab89,
					0x98badcfe, 0x10325476,
					0xc3d2e1f0
				]);
			},

			_doProcessBlock: function (M, offset) {
				// Shortcut
				let H = this._hash.words;

				// Working variables
				let a = H[0];
				let b = H[1];
				let c = H[2];
				let d = H[3];
				let e = H[4];

				// Computation
				for(let i = 0; i < 80; i++) {
					if(i < 16) {
						W[i] = M[offset + i] | 0;
					} else {
						let n = W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16];
						W[i] = (n << 1) | (n >>> 31);
					}

					let t = ((a << 5) | (a >>> 27)) + e + W[i];
					if(i < 20) {
						t += ((b & c) | (~b & d)) + 0x5a827999;
					} else if(i < 40) {
						t += (b ^ c ^ d) + 0x6ed9eba1;
					} else if(i < 60) {
						t += ((b & c) | (b & d) | (c & d)) - 0x70e44324;
					} else /* if(i < 80) */ {
						t += (b ^ c ^ d) - 0x359d3e2a;
					}

					e = d;
					d = c;
					c = (b << 30) | (b >>> 2);
					b = a;
					a = t;
				}

				// Intermediate hash value
				H[0] = (H[0] + a) | 0;
				H[1] = (H[1] + b) | 0;
				H[2] = (H[2] + c) | 0;
				H[3] = (H[3] + d) | 0;
				H[4] = (H[4] + e) | 0;
			},

			_doFinalize: function () {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;

				let nBitsTotal = this._nDataBytes * 8;
				let nBitsLeft = data.sigBytes * 8;

				// Add padding
				dataWords[nBitsLeft >>> 5] |= 0x80 << (24 - nBitsLeft % 32);
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 14] = Math.floor(nBitsTotal / 0x100000000);
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 15] = nBitsTotal;
				data.sigBytes = dataWords.length * 4;

				// Hash final blocks
				this._process();

				// Return final computed hash
				return this._hash;
			},

			clone: function () {
				let clone = Hasher.clone.call(this);
				clone._hash = this._hash.clone();
				return clone;
			}
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA1('message');
		 *     let hash = CryptoJS.SHA1(wordArray);
		 */
		C.SHA1 = Hasher._createHelper(SHA1);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA1(message, key);
		 */
		C.HmacSHA1 = Hasher._createHmacHelper(SHA1);

	}());
	//== # end

	//== # cryptojs-uxm/md5.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function(Math) {

		// ES6
		// bug-fixes: -

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const WordArray = C_lib.WordArray;
		const Hasher = C_lib.Hasher;
		const C_algo = C.algo;

		// Constants table
		let T = [];

		// Compute constants
		(function () {
			for(let i = 0; i < 64; i++) {
				T[i] = (Math.abs(Math.sin(i + 1)) * 0x100000000) | 0;
			}
		}());

		/*
		 * MD5 hash algorithm.
		 */
		const MD5 = C_algo.MD5 = Hasher.extend({
			_doReset: function () {
				this._hash = new WordArray.init([
					0x67452301, 0xefcdab89,
					0x98badcfe, 0x10325476
				]);
			},

			_doProcessBlock: function (M, offset) {
				// Swap endian
				for(let i = 0; i < 16; i++) {
					// Shortcuts
					let offset_i = offset + i;
					let M_offset_i = M[offset_i];
					M[offset_i] = (
						(((M_offset_i << 8)  | (M_offset_i >>> 24)) & 0x00ff00ff) |
						(((M_offset_i << 24) | (M_offset_i >>> 8))  & 0xff00ff00)
					);
				}

				// Shortcuts
				let H = this._hash.words;

				let M_offset_0  = M[offset + 0];
				let M_offset_1  = M[offset + 1];
				let M_offset_2  = M[offset + 2];
				let M_offset_3  = M[offset + 3];
				let M_offset_4  = M[offset + 4];
				let M_offset_5  = M[offset + 5];
				let M_offset_6  = M[offset + 6];
				let M_offset_7  = M[offset + 7];
				let M_offset_8  = M[offset + 8];
				let M_offset_9  = M[offset + 9];
				let M_offset_10 = M[offset + 10];
				let M_offset_11 = M[offset + 11];
				let M_offset_12 = M[offset + 12];
				let M_offset_13 = M[offset + 13];
				let M_offset_14 = M[offset + 14];
				let M_offset_15 = M[offset + 15];

				// Working varialbes
				let a = H[0];
				let b = H[1];
				let c = H[2];
				let d = H[3];

				// Computation
				a = FF(a, b, c, d, M_offset_0,  7,  T[0]);
				d = FF(d, a, b, c, M_offset_1,  12, T[1]);
				c = FF(c, d, a, b, M_offset_2,  17, T[2]);
				b = FF(b, c, d, a, M_offset_3,  22, T[3]);
				a = FF(a, b, c, d, M_offset_4,  7,  T[4]);
				d = FF(d, a, b, c, M_offset_5,  12, T[5]);
				c = FF(c, d, a, b, M_offset_6,  17, T[6]);
				b = FF(b, c, d, a, M_offset_7,  22, T[7]);
				a = FF(a, b, c, d, M_offset_8,  7,  T[8]);
				d = FF(d, a, b, c, M_offset_9,  12, T[9]);
				c = FF(c, d, a, b, M_offset_10, 17, T[10]);
				b = FF(b, c, d, a, M_offset_11, 22, T[11]);
				a = FF(a, b, c, d, M_offset_12, 7,  T[12]);
				d = FF(d, a, b, c, M_offset_13, 12, T[13]);
				c = FF(c, d, a, b, M_offset_14, 17, T[14]);
				b = FF(b, c, d, a, M_offset_15, 22, T[15]);

				a = GG(a, b, c, d, M_offset_1,  5,  T[16]);
				d = GG(d, a, b, c, M_offset_6,  9,  T[17]);
				c = GG(c, d, a, b, M_offset_11, 14, T[18]);
				b = GG(b, c, d, a, M_offset_0,  20, T[19]);
				a = GG(a, b, c, d, M_offset_5,  5,  T[20]);
				d = GG(d, a, b, c, M_offset_10, 9,  T[21]);
				c = GG(c, d, a, b, M_offset_15, 14, T[22]);
				b = GG(b, c, d, a, M_offset_4,  20, T[23]);
				a = GG(a, b, c, d, M_offset_9,  5,  T[24]);
				d = GG(d, a, b, c, M_offset_14, 9,  T[25]);
				c = GG(c, d, a, b, M_offset_3,  14, T[26]);
				b = GG(b, c, d, a, M_offset_8,  20, T[27]);
				a = GG(a, b, c, d, M_offset_13, 5,  T[28]);
				d = GG(d, a, b, c, M_offset_2,  9,  T[29]);
				c = GG(c, d, a, b, M_offset_7,  14, T[30]);
				b = GG(b, c, d, a, M_offset_12, 20, T[31]);

				a = HH(a, b, c, d, M_offset_5,  4,  T[32]);
				d = HH(d, a, b, c, M_offset_8,  11, T[33]);
				c = HH(c, d, a, b, M_offset_11, 16, T[34]);
				b = HH(b, c, d, a, M_offset_14, 23, T[35]);
				a = HH(a, b, c, d, M_offset_1,  4,  T[36]);
				d = HH(d, a, b, c, M_offset_4,  11, T[37]);
				c = HH(c, d, a, b, M_offset_7,  16, T[38]);
				b = HH(b, c, d, a, M_offset_10, 23, T[39]);
				a = HH(a, b, c, d, M_offset_13, 4,  T[40]);
				d = HH(d, a, b, c, M_offset_0,  11, T[41]);
				c = HH(c, d, a, b, M_offset_3,  16, T[42]);
				b = HH(b, c, d, a, M_offset_6,  23, T[43]);
				a = HH(a, b, c, d, M_offset_9,  4,  T[44]);
				d = HH(d, a, b, c, M_offset_12, 11, T[45]);
				c = HH(c, d, a, b, M_offset_15, 16, T[46]);
				b = HH(b, c, d, a, M_offset_2,  23, T[47]);

				a = II(a, b, c, d, M_offset_0,  6,  T[48]);
				d = II(d, a, b, c, M_offset_7,  10, T[49]);
				c = II(c, d, a, b, M_offset_14, 15, T[50]);
				b = II(b, c, d, a, M_offset_5,  21, T[51]);
				a = II(a, b, c, d, M_offset_12, 6,  T[52]);
				d = II(d, a, b, c, M_offset_3,  10, T[53]);
				c = II(c, d, a, b, M_offset_10, 15, T[54]);
				b = II(b, c, d, a, M_offset_1,  21, T[55]);
				a = II(a, b, c, d, M_offset_8,  6,  T[56]);
				d = II(d, a, b, c, M_offset_15, 10, T[57]);
				c = II(c, d, a, b, M_offset_6,  15, T[58]);
				b = II(b, c, d, a, M_offset_13, 21, T[59]);
				a = II(a, b, c, d, M_offset_4,  6,  T[60]);
				d = II(d, a, b, c, M_offset_11, 10, T[61]);
				c = II(c, d, a, b, M_offset_2,  15, T[62]);
				b = II(b, c, d, a, M_offset_9,  21, T[63]);

				// Intermediate hash value
				H[0] = (H[0] + a) | 0;
				H[1] = (H[1] + b) | 0;
				H[2] = (H[2] + c) | 0;
				H[3] = (H[3] + d) | 0;
			},

			_doFinalize: function () {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;

				let nBitsTotal = this._nDataBytes * 8;
				let nBitsLeft = data.sigBytes * 8;

				// Add padding
				dataWords[nBitsLeft >>> 5] |= 0x80 << (24 - nBitsLeft % 32);

				let nBitsTotalH = Math.floor(nBitsTotal / 0x100000000);
				let nBitsTotalL = nBitsTotal;
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 15] = (
					(((nBitsTotalH << 8)  | (nBitsTotalH >>> 24)) & 0x00ff00ff) |
					(((nBitsTotalH << 24) | (nBitsTotalH >>> 8))  & 0xff00ff00)
				);
				dataWords[(((nBitsLeft + 64) >>> 9) << 4) + 14] = (
					(((nBitsTotalL << 8)  | (nBitsTotalL >>> 24)) & 0x00ff00ff) |
					(((nBitsTotalL << 24) | (nBitsTotalL >>> 8))  & 0xff00ff00)
				);
				data.sigBytes = (dataWords.length + 1) * 4;
				// Hash final blocks
				this._process();
				// Shortcuts
				let hash = this._hash;
				let H = hash.words;
				// Swap endian
				for(let i = 0; i < 4; i++) {
					// Shortcut
					let H_i = H[i];
					H[i] = (((H_i << 8)  | (H_i >>> 24)) & 0x00ff00ff) |
						   (((H_i << 24) | (H_i >>> 8))  & 0xff00ff00);
				}
				// Return final computed hash
				return hash;
			},

			clone: function () {
				let clone = Hasher.clone.call(this);
				clone._hash = this._hash.clone();
				return clone;
			}
		});

		const FF = function(a, b, c, d, x, s, t) {
			let n = a + ((b & c) | (~b & d)) + x + t;
			return ((n << s) | (n >>> (32 - s))) + b;
		};

		const GG = function(a, b, c, d, x, s, t) {
			let n = a + ((b & d) | (c & ~d)) + x + t;
			return ((n << s) | (n >>> (32 - s))) + b;
		};

		const HH = function(a, b, c, d, x, s, t) {
			let n = a + (b ^ c ^ d) + x + t;
			return ((n << s) | (n >>> (32 - s))) + b;
		};

		const II = function(a, b, c, d, x, s, t) {
			let n = a + (c ^ (b | ~d)) + x + t;
			return ((n << s) | (n >>> (32 - s))) + b;
		};

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.MD5('message');
		 *     let hash = CryptoJS.MD5(wordArray);
		 */
		C.MD5 = Hasher._createHelper(MD5);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacMD5(message, key);
		 */
		C.HmacMD5 = Hasher._createHmacHelper(MD5);

	}(Math));
	//== # end

	//== # cryptojs-uxm/sha3.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function(Math) {

		// ES6
		// bug-fixes: unixman:
		// 	* out of context variables, after using `let` or `const` instead of `var` (ES6) migration
		// 	* the original standard of SHA3 in this library was done in 2013, and did not comply with actual SHA3 standard
		// 	* thus the padding had to be changed from `1` (`0x1`) to `6` (`0x6`)
		// 	* fix correct SHA3 padding to comply with the official SHA-3 standard in August 2015, FIPS 202

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const WordArray = C_lib.WordArray;
		const Hasher = C_lib.Hasher;
		const C_x64 = C.x64;
		const X64Word = C_x64.Word;
		const C_algo = C.algo;

		// Constants tables
		const RHO_OFFSETS = [];
		const PI_INDEXES  = [];
		const ROUND_CONSTANTS = [];

		// Compute Constants
		(function () {
			// Compute rho offset constants
			let x = 1, y = 0;
			for(let t = 0; t < 24; t++) {
				RHO_OFFSETS[x + 5 * y] = ((t + 1) * (t + 2) / 2) % 64;

				let newX = y % 5;
				let newY = (2 * x + 3 * y) % 5;
				x = newX;
				y = newY;
			}

			// Compute pi index constants
			for(let x = 0; x < 5; x++) {
				for(let y = 0; y < 5; y++) {
					PI_INDEXES[x + 5 * y] = y + ((2 * x + 3 * y) % 5) * 5;
				}
			}

			// Compute round constants
			let LFSR = 0x01;
			for(let i = 0; i < 24; i++) {
				let roundConstantMsw = 0;
				let roundConstantLsw = 0;

				for(let j = 0; j < 7; j++) {
					if(LFSR & 0x01) {
						let bitPosition = (1 << j) - 1;
						if(bitPosition < 32) {
							roundConstantLsw ^= 1 << bitPosition;
						} else /* if(bitPosition >= 32) */ {
							roundConstantMsw ^= 1 << (bitPosition - 32);
						}
					}

					// Compute next LFSR
					if(LFSR & 0x80) {
						// Primitive polynomial over GF(2): x^8 + x^6 + x^5 + x^4 + 1
						LFSR = (LFSR << 1) ^ 0x71;
					} else {
						LFSR <<= 1;
					}
				}

				ROUND_CONSTANTS[i] = X64Word.create(roundConstantMsw, roundConstantLsw);
			}
		}());

		// Reusable objects for temporary values
		const T = [];
		(function () {
			for(let i = 0; i < 25; i++) {
				T[i] = X64Word.create();
			}
		}());

		/*
		 * SHA-3 hash algorithm.
		 */
		const SHA3 = C_algo.SHA3 = Hasher.extend({
			/*
			 * Configuration options.
			 *
			 * @property {number} outputLength
			 *   The desired number of bits in the output hash.
			 *   Only values permitted are: 224, 256, 384, 512.
			 *   Default: 512
			 */
			cfg: Hasher.cfg.extend({
				outputLength: 512
			}),

			_doReset: function () {
				let state = this._state = []
				for(let i = 0; i < 25; i++) {
					state[i] = new X64Word.init();
				}

				this.blockSize = (1600 - 2 * this.cfg.outputLength) / 32;
			},

			_doProcessBlock: function (M, offset) {
				// Shortcuts
				let state = this._state;
				let nBlockSizeLanes = this.blockSize / 2;

				// Absorb
				for(let i = 0; i < nBlockSizeLanes; i++) {
					// Shortcuts
					let M2i  = M[offset + 2 * i];
					let M2i1 = M[offset + 2 * i + 1];

					// Swap endian
					M2i = (
						(((M2i << 8)  | (M2i >>> 24)) & 0x00ff00ff) |
						(((M2i << 24) | (M2i >>> 8))  & 0xff00ff00)
					);
					M2i1 = (
						(((M2i1 << 8)  | (M2i1 >>> 24)) & 0x00ff00ff) |
						(((M2i1 << 24) | (M2i1 >>> 8))  & 0xff00ff00)
					);

					// Absorb message into state
					let lane = state[i];
					lane.high ^= M2i1;
					lane.low  ^= M2i;
				}

				// Rounds
				for(let round = 0; round < 24; round++) {
					// Theta
					for(let x = 0; x < 5; x++) {
						// Mix column lanes
						let tMsw = 0, tLsw = 0;
						for(let y = 0; y < 5; y++) {
							let lane = state[x + 5 * y];
							tMsw ^= lane.high;
							tLsw ^= lane.low;
						}

						// Temporary values
						let Tx = T[x];
						Tx.high = tMsw;
						Tx.low  = tLsw;
					}
					for(let x = 0; x < 5; x++) {
						// Shortcuts
						let Tx4 = T[(x + 4) % 5];
						let Tx1 = T[(x + 1) % 5];
						let Tx1Msw = Tx1.high;
						let Tx1Lsw = Tx1.low;

						// Mix surrounding columns
						let tMsw = Tx4.high ^ ((Tx1Msw << 1) | (Tx1Lsw >>> 31));
						let tLsw = Tx4.low  ^ ((Tx1Lsw << 1) | (Tx1Msw >>> 31));
						for(let y = 0; y < 5; y++) {
							let lane = state[x + 5 * y];
							lane.high ^= tMsw;
							lane.low  ^= tLsw;
						}
					}

					// Rho Pi
					for(let laneIndex = 1; laneIndex < 25; laneIndex++) {
						// Shortcuts
						let lane = state[laneIndex];
						let laneMsw = lane.high;
						let laneLsw = lane.low;
						let rhoOffset = RHO_OFFSETS[laneIndex];

						// Rotate lanes
						let tMsw, tLsw; // bug fix by unixman, need to be defined outside the next if/else block
						if(rhoOffset < 32) {
							tMsw = (laneMsw << rhoOffset) | (laneLsw >>> (32 - rhoOffset));
							tLsw = (laneLsw << rhoOffset) | (laneMsw >>> (32 - rhoOffset));
						} else /* if(rhoOffset >= 32) */ {
							tMsw = (laneLsw << (rhoOffset - 32)) | (laneMsw >>> (64 - rhoOffset));
							tLsw = (laneMsw << (rhoOffset - 32)) | (laneLsw >>> (64 - rhoOffset));
						}

						// Transpose lanes
						let TPiLane = T[PI_INDEXES[laneIndex]];
						TPiLane.high = tMsw;
						TPiLane.low  = tLsw;
					}

					// Rho pi at x = y = 0
					let T0 = T[0];
					let state0 = state[0];
					T0.high = state0.high;
					T0.low  = state0.low;

					// Chi
					for(let x = 0; x < 5; x++) {
						for(let y = 0; y < 5; y++) {
							// Shortcuts
							let laneIndex = x + 5 * y;
							let lane = state[laneIndex];
							let TLane = T[laneIndex];
							let Tx1Lane = T[((x + 1) % 5) + 5 * y];
							let Tx2Lane = T[((x + 2) % 5) + 5 * y];

							// Mix rows
							lane.high = TLane.high ^ (~Tx1Lane.high & Tx2Lane.high);
							lane.low  = TLane.low  ^ (~Tx1Lane.low  & Tx2Lane.low);
						}
					}

					// Iota
					let lane = state[0];
					let roundConstant = ROUND_CONSTANTS[round];
					lane.high ^= roundConstant.high;
					lane.low  ^= roundConstant.low;;
				}
			},

			_doFinalize: function () {
				// Shortcuts
				let data = this._data;
				let dataWords = data.words;
				let nBitsTotal = this._nDataBytes * 8;
				let nBitsLeft = data.sigBytes * 8;
				let blockSizeBits = this.blockSize * 32;

				// Add padding
				//-- fix start: by unixman 2023-10-31, :-) Happy Haloween ... will have party tonight !
				// CryptoJS original 'sha3' implementation is not actually using the current SHA-3 standard.
				// It was superseded. In 2014, NIST made slight changes to the Keccak submission and published FIPS 202,
				// which became the official SHA-3 standard in August 2015.
				// The article was found here: https://stackoverflow.com/questions/36657354/cryptojs-sha3-and-php-sha3
				//-- Thereafter, the fix comes from here: https://www.cybertest.com/blog/keccak-vs-sha3
				// as: `change the padding to 6 from 1` aka to 0x6 from 0x1, as bellow:
				//--
			//	dataWords[nBitsLeft >>> 5] |= 0x1 << (24 - nBitsLeft % 32); // original code (does not match the current SHA3 standard)
				dataWords[nBitsLeft >>> 5] |= 0x6 << (24 - nBitsLeft % 32); // modified code, fix by unixman, to comply with FIPS 202 (matches the current SHA3 standard)
				//-- #end fix
				dataWords[((Math.ceil((nBitsLeft + 1) / blockSizeBits) * blockSizeBits) >>> 5) - 1] |= 0x80;
				data.sigBytes = dataWords.length * 4;

				// Hash final blocks
				this._process();

				// Shortcuts
				let state = this._state;
				let outputLengthBytes = this.cfg.outputLength / 8;
				let outputLengthLanes = outputLengthBytes / 8;

				// Squeeze
				let hashWords = [];
				for(let i = 0; i < outputLengthLanes; i++) {
					// Shortcuts
					let lane = state[i];
					let laneMsw = lane.high;
					let laneLsw = lane.low;

					// Swap endian
					laneMsw = (
						(((laneMsw << 8)  | (laneMsw >>> 24)) & 0x00ff00ff) |
						(((laneMsw << 24) | (laneMsw >>> 8))  & 0xff00ff00)
					);
					laneLsw = (
						(((laneLsw << 8)  | (laneLsw >>> 24)) & 0x00ff00ff) |
						(((laneLsw << 24) | (laneLsw >>> 8))  & 0xff00ff00)
					);

					// Squeeze state to retrieve hash
					hashWords.push(laneLsw);
					hashWords.push(laneMsw);
				}

				// Return final computed hash
				return new WordArray.init(hashWords, outputLengthBytes);
			},

			clone: function () {
				let clone = Hasher.clone.call(this);

				let state = clone._state = this._state.slice(0);
				for(let i = 0; i < 25; i++) {
					state[i] = state[i].clone();
				}

				return clone;
			}
		});

		/*
		 * Shortcut function to the hasher's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @return {WordArray} The hash.
		 *
		 * @example
		 *     let hash = CryptoJS.SHA3('message');
		 *     let hash = CryptoJS.SHA3(wordArray);
		 */
		C.SHA3 = Hasher._createHelper(SHA3);

		/*
		 * Shortcut function to the HMAC's object interface.
		 *
		 * @static
		 * @param {WordArray|string} message The message to hash.
		 * @param {WordArray|string} key The secret key.
		 * @return {WordArray} The HMAC.
		 *
		 * @example
		 *     let hmac = CryptoJS.HmacSHA3(message, key);
		 */
		C.HmacSHA3 = Hasher._createHmacHelper(SHA3);

	}(Math));
	//== # end

	//== # cryptojs-uxm/pbkdf2.js @patch: r.uxm.20231105
	//--
	// (c) 2023-present unix-world.org
	//--
	// CryptoJS v3.1.2
	// code.google.com/p/crypto-js
	// (c) 2009-2013 by Jeff Mott
	// License: BSD
	//--
	(function() {

		// ES6
		// fixes: unixman:
		// 	* default algorithm changed from SHA1 to SHA384
		// 	* default iterations changed from 1 to 10
		// 	* fix return keySize length, previous the full key was returned

		// Shortcuts
		const C = CryptoJS;
		const C_lib = C.lib;
		const Base = C_lib.Base;
		const WordArray = C_lib.WordArray;
		const C_algo = C.algo;
		//-- fix by unixman
	//	const SHA1 = C_algo.SHA1;
		const SHA384 = C_algo.SHA384;
		//-- #end fix
		const HMAC = C_algo.HMAC;

		/*
		 * Password-Based Key Derivation Function 2 algorithm.
		 */
		const PBKDF2 = C_algo.PBKDF2 = Base.extend({
			/*
			 * Configuration options.
			 *
			 * @property {number} keySize The key size in words to generate. Default: 4 (128 bits)
			 * @property {Hasher} hasher The hasher to use. Default: SHA384
			 * @property {number} iterations The number of iterations to perform. Default: 1
			 */
			cfg: Base.extend({
				keySize: 128/32,
				//-- fix by unixman
			//	hasher: SHA1,
			//	iterations: 1,
				hasher: SHA384,
				iterations: 10,
				//-- #end fix
			}),

			/*
			 * Initializes a newly created key derivation function.
			 *
			 * @param {Object} cfg (Optional) The configuration options to use for the derivation.
			 *
			 * @example
			 *     let kdf = CryptoJS.algo.PBKDF2.create();
			 *     let kdf = CryptoJS.algo.PBKDF2.create({ keySize: 8 });
			 *     let kdf = CryptoJS.algo.PBKDF2.create({ keySize: 8, iterations: 1000 });
			 */
			init: function (cfg) {
				this.cfg = this.cfg.extend(cfg);
			},

			/*
			 * Computes the Password-Based Key Derivation Function 2.
			 *
			 * @param {WordArray|string} password The password.
			 * @param {WordArray|string} salt A salt.
			 * @return {WordArray} The derived key.
			 *
			 * @example
			 *     let key = kdf.compute(password, salt);
			 */
			compute: function (password, salt) {
				// Shortcut
				let cfg = this.cfg;

				// Init HMAC
				let hmac = HMAC.create(cfg.hasher, password);

				// Initial values
				let derivedKey = WordArray.create();
				let blockIndex = WordArray.create([0x00000001]);

				// Shortcuts
				let derivedKeyWords = derivedKey.words;
				let blockIndexWords = blockIndex.words;
				let keySize = cfg.keySize;
				let iterations = cfg.iterations;

				// Generate key
				while(derivedKeyWords.length < keySize) {
					let block = hmac.update(salt).finalize(blockIndex);
					hmac.reset();

					// Shortcuts
					let blockWords = block.words;
					let blockWordsLength = blockWords.length;

					// Iterations
					let intermediate = block;
					for(let i = 1; i < iterations; i++) {
						intermediate = hmac.finalize(intermediate);
						hmac.reset();

						// Shortcut
						let intermediateWords = intermediate.words;

						// XOR intermediate with block
						for(let j = 0; j < blockWordsLength; j++) {
							blockWords[j] ^= intermediateWords[j];
						}
					}

					derivedKey.concat(block);
					blockIndexWords[0]++;
				}
				derivedKey.sigBytes = keySize * 4;

				return derivedKey;
			}
		});

		/*
		 * Computes the Password-Based Key Derivation Function 2.
		 *
		 * @static
		 * @param {WordArray|string} password The password.
		 * @param {WordArray|string} salt A salt.
		 * @param {Object} cfg (Optional) The configuration options to use for this computation.
		 * @return {WordArray} The derived key.
		 *
		 * @example
		 *     let key = CryptoJS.PBKDF2(password, salt);
		 *     let key = CryptoJS.PBKDF2(password, salt, { keySize: 8 });
		 *     let key = CryptoJS.PBKDF2(password, salt, { keySize: 8, iterations: 1000 });
		 */
		C.PBKDF2 = function (password, salt, cfg) {
			//-- fix by unixman: return only the keySize not the whole key ... this is how should be !
		//	return PBKDF2.create(cfg).compute(password, salt);
			let hash = PBKDF2.create(cfg).compute(password, salt);
			let str = hash.toString().substring(0, cfg.keySize);
			return CryptoJS.enc.Hex.parse(str);
			//-- #end fix
		};

	}());
	//== # end
	// ===== [#]

	// # JS Package: js-crypto-uxm.js :: #END#

	// ===== [#]

	//-- CryptoJS: #END: js-crypto-uxm.js
	//================================================================================
	Object.freeze(CryptoJS);
	const cryptoJs = CryptoJS;
	//================================================================================

	//==== [ SHA-MD5 HASH: START ]

	//== SHA3 / SHA3-Hmac: 512, 384, 256, 224

	//--
	const Sha3 = (len, str, b64=false, hmac=false, key=null) => {
		//--
		const _m$ = 'sha3';
		//--
		len = _Utils$.stringPureVal(len || '')
		switch(len) {
			case '512':
			case '384':
			case '256':
			case '224':
				len = _Utils$.format_number_int(len, false);
				break;
			default:
				_p$.error(_N$, _m$, 'Invalid Mode:', len);
				return '';
		} //end switch
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA3(str, _Utils$.stringPureVal(key || ''), { outputLength: len });
		} else {
			hash = cryptoJs.SHA3(str, { outputLength: len });
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA3-512 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sh3a512
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA3-512 hash of the string (Hex or B64)
	 */
	const sh3a512 = (str, b64=false) => String(Sha3(512, str, b64));
	_C$.sh3a512 = sh3a512;// export
	//--
	/**
	 * Returns the SHA3-384 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sh3a384
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA3-384 hash of the string (Hex or B64)
	 */
	const sh3a384 = (str, b64=false) => String(Sha3(384, str, b64));
	_C$.sh3a384 = sh3a384;// export
	//--
	/**
	 * Returns the SHA3-256 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sh3a256
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA3-256 hash of the string (Hex or B64)
	 */
	const sh3a256 = (str, b64=false) => String(Sha3(256, str, b64));
	_C$.sh3a256 = sh3a256;// export
	//--
	/**
	 * Returns the SHA3-224 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sh3a224
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA3-224 hash of the string (Hex or B64)
	 */
	const sh3a224 = (str, b64=false) => String(Sha3(224, str, b64));
	_C$.sh3a224 = sh3a224;// export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== SHA512 / SHA512-Hmac

	//--
	const Sha512 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA512(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.SHA512(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA512 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha512
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA512 hash of the string (Hex or B64)
	 */
	const sha512 = (str, b64=false) => String(Sha512(str, b64, false));
	_C$.sha512 = sha512; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== SHA384 / SHA384-Hmac

	//--
	const Sha384 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA384(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.SHA384(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA384 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha384
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA384 hash of the string (Hex or B64)
	 */
	const sha384 = (str, b64=false) => String(Sha384(str, b64, false));
	_C$.sha384 = sha384; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== SHA256 / SHA256-Hmac

	//--
	const Sha256 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA256(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.SHA256(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA256 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha256
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA256 hash of the string (Hex or B64)
	 */
	const sha256 = (str, b64=false) => String(Sha256(str, b64, false));
	_C$.sha256 = sha256; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== SHA224 / SHA224-Hmac

	//--
	const Sha224 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA224(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.SHA224(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA224 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha224
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA224 hash of the string (Hex or B64)
	 */
	const sha224 = (str, b64=false) => String(Sha224(str, b64, false));
	_C$.sha224 = sha224; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== SHA1 / SHA1-Hmac

	//--
	const Sha1 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacSHA1(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.SHA1(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the SHA1 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method sha1
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The SHA1 hash of the string (Hex or B64)
	 */
	const sha1 = (str, b64=false) => String(Sha1(str, b64, false));
	_C$.sha1 = sha1; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//== MD5 / MD5-Hmac

	//--
	const Md5 = (str, b64=false, hmac=false, key=null) => {
		//--
		str = _Utils$.stringPureVal(str || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make it unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		b64 = !! b64;
		//--
		let hash;
		if(hmac === true) {
			hash = cryptoJs.HmacMD5(str, _Utils$.stringPureVal(key || ''));
		} else {
			hash = cryptoJs.MD5(str);
		} //end if else
		let sum = _Utils$.stringPureVal(hash.toString() || '');
		if(!!b64) {
			sum = _Utils$.b64Enc(_Utils$.hex2bin(sum, true), true); // binary encode B64
		} //end if
		//--
		return String(sum);
		//--
	}; // no export
	//--
	/**
	 * Returns the MD5 hash of a string
	 *
	 * @memberof smartJ$CryptoHash
	 * @method md5
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {String} str The string
	 * @param {Boolean} b64 If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} The MD5 hash of the string (Hex or B64)
	 */
	const md5 = (str, b64=false) => String(Md5(str, b64, false));
	_C$.md5 = md5; // export
	//--
	// hmac is exported below, in a hmac method
	//--

	//==== [ SHA-MD5 HASH: #END ]

	//==== [ HMAC SHA-MD5 HASH: #START ]

	/**
	 * Returns the HMAC hash of a string with support for various algorithms
	 *
	 * @memberof smartJ$CryptoHash
	 * @method hmac
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {Enum} 	algo 	The hashing algo: md5, sha1, sha224, sha256, sha384, sha512, sha3-224, sha3-256, sha3-384, sha3-512
	 * @param {String} 	key 	The secret key
	 * @param {String} 	str 	The string to be hashed
	 * @param {Boolean} b64 	If set to TRUE will use Base64 Encoding instead of Hex Encoding
	 * @return {String} 		The Hmac Hash of the string, salted by key (Hex or B64)
	 */
	const hmac = (algo, key, str, b64=false) => {
		//--
		const _m$ = 'hmac';
		//--
		b64 = !! b64;
		//--
		algo = _Utils$.stringPureVal(algo, true);
		algo = algo.toUpperCase();
		let hash = null;
		switch(algo) {
			case 'SHA3-512':
				hash = Sha3(512, str, !!b64, true, key);
				break;
			case 'SHA3-384':
				hash = Sha3(384, str, !!b64, true, key);
				break;
			case 'SHA3-256':
				hash = Sha3(256, str, !!b64, true, key);
				break;
			case 'SHA3-224':
				hash = Sha3(224, str, !!b64, true, key);
				break;
			case 'SHA512':
				hash = Sha512(str, !!b64,  true, key);
				break; // ok
			case 'SHA384':
				hash = Sha384(str, !!b64,  true, key);
				break; // ok
			case 'SHA256':
				hash = Sha256(str, !!b64,  true, key);
				break; // ok
			case 'SHA224':
				hash = Sha224(str, !!b64,  true, key);
				break; // ok
			case 'SHA1':
				hash = Sha1(str, !!b64,  true, key);
				break; // ok
			case 'MD5':
				hash = Md5(str, !!b64,  true, key);
				break; // ok
			default:
				hash = false; // N/A
		} //end switch
		if(!hash) {
			_p$.error(_N$, _m$, 'Invalid Algo:', algo);
			return '';
		} //end if
		//--
		return String(hash);
		//--
	};
	_C$.hmac = hmac; // export

	//==== [ HMAC SHA-MD5 HASH: #END ]

	//==== [ PBKDF2: START ]

	/*
	 * Returns the PBKDF2 Derived Key
	 *
	 * @memberof smartJ$CryptoHash
	 * @method pbkdf2
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param {Enum} 		algo 	The hashing algo: md5, sha1, sha224, sha256, sha384, sha512, sha3-224, sha3-256, sha3-384, sha3-512
	 * @param {String} 		key 	The key (min 7 bytes ; max 4096 bytes)
	 * @param {String} 		salt 	The salt ; it should not be empty
	 * @param {Integer+} 	len 	The derived key length
	 * @param {Integer+} 	iter 	The number of iterations ; between 1 and 2500
	 * @param {Boolean} 	b92 	If set to TRUE will use Base92 Encoding instead of Hex Encoding
	 * @return {String} 			The PBKDF2 Hash of the key, salted (Hex or B92)
	 */
	const pbkdf2 = (algo, key, salt, len, iter, b92=false) => {
		//--
		const _m$ = 'pbkdf2';
		//--
		algo = _Utils$.stringPureVal(algo, true);
		algo = algo.toUpperCase();
		let hasher = null;
		switch(algo) {
			case 'SHA3-512':
				hasher = cryptoJs.algo.SHA3;
				hasher.cfg.outputLength = 512;
				break;
			case 'SHA3-384':
				hasher = cryptoJs.algo.SHA3;
				hasher.cfg.outputLength = 384;
				break;
			case 'SHA3-256':
				hasher = cryptoJs.algo.SHA3;
				hasher.cfg.outputLength = 256;
				break;
			case 'SHA3-224':
				hasher = cryptoJs.algo.SHA3;
				hasher.cfg.outputLength = 224;
				break;
			case 'SHA512':
				hasher = cryptoJs.algo.SHA512;
				break; // ok
			case 'SHA384':
				hasher = cryptoJs.algo.SHA384;
				break; // ok
			case 'SHA256':
				hasher = cryptoJs.algo.SHA256;
				break; // ok
			case 'SHA224':
				hasher = cryptoJs.algo.SHA224;
				break; // ok
			case 'SHA1':
				hasher = cryptoJs.algo.SHA1;
				break; // ok
			case 'MD5':
				hasher = cryptoJs.algo.MD5;
				break; // ok
			default:
				hasher = false; // N/A
		} //end switch
		if(!hasher) {
			_p$.error(_N$, _m$, 'Invalid Algo:', algo);
			return '';
		} //end if
		//--
		key = _Utils$.stringPureVal(key || ''); // cast to string, don't trim ! need to preserve the value
		salt = _Utils$.stringPureVal(salt || ''); // cast to string, don't trim ! need to preserve the value
		// no need to make them unicode using the CryptoJS implementation: str = _Utils$.utf8Enc(str);
		//--
		len = _Utils$.format_number_int(len, false);
		if(len <= 0) {
			_p$.error(_N$, _m$, 'The length parameter is zero or negative');
			return '';
		} //end if
		//--
		iter = _Utils$.format_number_int(iter, false);
		if(iter < 1) {
			_p$.warn(_N$, _m$, 'The Number of iterations is too low:', iter);
			iter = 1;
		} else if(iter > 2500) { // for JS this is a bit too much ... lower limit than 5000 as in PHP
			_p$.warn(_N$, _m$, 'The Number of iterations is too high:', iter);
			iter = 2500;
		} //end if
		//--
		let hLen = len;
		if(b92 === true) {
			hLen = len * 2;
		} //end if
		//--
		let dkey = '';
		try {
			dkey = CryptoJS.PBKDF2(key, salt, { hasher: hasher, keySize: hLen, iterations: iter });
		} catch(err) {
			_p$.error(_N$, _m$, '(' + (b92 ? 'B92' : 'Hex') + ') Failed with Error:', err);
			return '';
		} //end try catch
		dkey = _Utils$.stringPureVal(dkey.toString() || '', true); // trim
		//--
		b92 = !! b92;
		//--
		if(b92 === true) {
			dkey = _Utils$.stringTrim(_Ba$eConv.base_from_hex_convert(dkey, 92));
			dkey = dkey.padEnd(len, "'");
			dkey = dkey.substring(0, len);
		} //end if
		//--
		if((dkey == '') || (dkey.length !== len)) {
			_p$.error(_N$, _m$, 'The PBKDF2 (' + (b92 ? 'B92' : 'Hex') + ') Derived Key is empty or does not match the expected size ; required size is:', len, 'bytes ; but the actual size is:', dkey.length, 'bytes');
			return '';
		} //end if
		//--
		return String(dkey);
		//--
	};
	//--
	_C$.pbkdf2 = pbkdf2; // export
	//--

	//==== [ PBKDF2: #END ]

}}; //END CLASS

smartJ$CryptoHash.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CryptoHash = smartJ$CryptoHash; // global export
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
 * @requires		smartJ$BaseConv
 *
 * @desc The JavaScript class provides methods to implement a secure algorithm for Diffie-Hellman key exchange between a server and a client ; Supports dual operation mode (Int64 or BigInt ; for using BigInt the broser must support it ...)
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
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
	const _Ba$eConv = smartJ$BaseConv;

	//==

	const _Option$ = ((typeof(smartJ$Options) != 'undefined') && smartJ$Options && (smartJ$Options.DhKx != undefined) && (typeof(smartJ$Options) === 'object') && (typeof(smartJ$Options.DhKx) === 'object')) ? smartJ$Options.DhKx : null;

	const bigIntSupport = ((_Option$ && (!!_Option$.BigIntSupport)) || (_w$ && _w$.crypto && _w$.crypto.getRandomValues && _w$.BigInt)) ? true : false;

	_C$.param_Size = (_Option$ && (typeof(_Option$.Size) == 'string') && _Option$.Size) ? _Utils$.stringTrim(_Option$.Size) : 'default';
	_C$.param_Prix = (_Option$ && (typeof(_Option$.Prix) == 'string') && _Option$.Prix) ? _Utils$.stringTrim(_Option$.Prix) : 'default';
	_C$.param_UseBigInt = (bigIntSupport && (_Option$ && (!_Option$.UseBigInt))) ? false : true;

	_w$ = null;

	//== [PUBLIC]

	/**
	 * Get the DH BigInt Use
	 *
	 * @memberof smartJ$DhKx
	 * @method useBigInt
	 * @static
	 *
	 * @return 	{Boolean} 				TRUE if uses BigInt or FALSE if uses Int64
	 */
	const useBigInt = () => {
		//--
		return !! _C$.param_UseBigInt;
		//--
	};
	_C$.useBigInt = useBigInt; // export

	/**
	 * Get the DH Mode
	 *
	 * @memberof smartJ$DhKx
	 * @method getMode
	 * @static
	 *
	 * @return 	{String} 				The operating mode: BigInt or Int64
	 */
	const getMode = () => {
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
	const getBaseGen = function() {
		//--
		return rng(String(_C$.param_Size));
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

	//== [PRIVATES]

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
		} //end if else
		return (powmInt64((a * a) % pri, b / 2 | 0, pri) * a) % pri;
		//--
	}; //END

	// BigInt pwr deriv by prim
	const powmBigint = (a, b, pri) => { // must return only BigInt values ; BigInt will automatically trim off decimals as: 5n / 2n = 2n (not 2.5n)
		//--
		a = BigInt(a);
		b = BigInt(b);
		pri = BigInt(pri);
		//--
		if(b <= BigInt(0)) {
			return BigInt(1);
		} else if(b == BigInt(1)) {
			return a % pri;
		} else if(b % BigInt(2) == BigInt(0)) {
			return powmBigint((a * a) % pri, b / BigInt(2), pri) % pri;
		} //end if else
		return powmBigint((a * a) % pri, b / BigInt(2), pri) * a % pri;
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
		//-- {{{SYNC-DHKX-HIGH-PRIMES}}}
		const hcBase1 = 'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245E485B576625E7EC6F44C42E9A63';
		const hcBase2 = '7ED6B0BFF5CB6F406B7EDEE386BFB5A899FA5AE9F24117C4B1FE649286651ECE';
		const hcBase3 = '45B3DC2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB9ED529077096966D670C354E4ABC9804F1746C08CA';
		const hcBase4 = '18217C32905E462E36CE3BE39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF6955817183995497CEA956AE515D2261898FA051015728E5A8AA';
		const hcBase5 = 'AC42DAD33170D04507A33A85521ABDF1CBA64ECFB850458DBEF0A8AEA71575D060C7DB3970F85A6E1E4C7ABF5AE8CDB0933D71E8C94E04A25619DCEE3D2261AD2EE6BF12FFA06D98A0864D87602733EC86A64521F2B18177B200CBBE117577A615D6C770988C0BAD946E208E24FA074E5AB3143DB5BFCE0FD108E4B82D120A9';
		const hcBase6 = '2108011A723C12A787E6D788719A10BDBA5B2699C327186AF4E23C1A946834B6150BDA2583E9CA2AD44CE8DBBBC2DB04DE8EF92E8EFC141FBECAA6287C59474E6BC05D99B2964FA090C3A2233BA186515BE7ED1F612970CEE2D7AFB81BDD762170481CD0069127D5B05AA993B4EA988D8FDDC186FFB7DC90A6C08F4DF435C9340';
		const hcBase7 = '2849236C3FAB4D27C7026C1D4DCB2602646DEC9751E763DBA37BDF8FF9406AD9E530EE5DB382F413001AEB06A53ED9027D831179727B0865A8918DA3EDBEBCF9B14ED44CE6CBACED4BB1BDB7F1447E6CC254B332051512BD7AF426FB8F401378CD2BF5983CA01C64B92ECF032EA15D1721D03F482D7CE6E74FEF6D55E702F46980C82B5A84031900B1C9E59E7C97FBEC7E8F323A97A7E36CC88BE0F1D45B7FF585AC54BD407B22B4154AACC8F6D7EBF48E1D814CC5ED20F8037E0A79715EEF29BE32806A1D58BB7C5DA76F550AA3D8A1FBFF0EB19CCB1A313D55CDA56C9EC2EF29632387FE8D76E3C0468043E8F663F4860EE12BF2D5B0B7474D6E694F91E6D';
		const primesBigint = {
			h017: 		'0x1141317432f7b89',
			h031: 		'0x6febe061005175e46c896e4079',
			h047: 		'0xf3f2b0ee30050c5f6bfcb9df1b9454e77bc3503',
			h061: 		'0x4771cfc3c2b8ad4561cb5437132e35e8398e8f956a2f2c94c51',
			h097: 		'0x426f09b2b25aba6bbcbf9ca5edb660b91d033440916732af9ae175a84afb665a25b392361c6952119',
			h127: 		'0x2c6121e6b14ecf756c083544de0e0933cac90dbeb6239905bfbec764527bbb4166ff832a2bcc3b4d6f634eddd30e40634adbbb5bfd',
			h257: 		'0x279e569032f0c7256218b58ad6418aa0e9436be424ab8f1431b1f9e6b5814e0ebda0ff65ef085d7e73fee51744dec07fe08c1a1cc65855630ca983927ca277406ac42094064387d65aeaa849f9bf449e04df8cb0e99a44b004ce0efca3386f1e82c078723cd265288d9a41',
			h232c1: 	'0x' + hcBase1 + 'A3620FFFFFFFFFFFFFFFF',
			h309c2: 	'0x' + hcBase1 + hcBase2 + '65381FFFFFFFFFFFFFFFF',
			h463c5: 	'0x' + hcBase1 + hcBase2 + hcBase3 + '237327FFFFFFFFFFFFFFFF', // 1536-bit MODP
			h617c14: 	'0x' + hcBase1 + hcBase2 + hcBase3 + hcBase4 + 'CAA68FFFFFFFFFFFFFFFF', // 2048-bit MODP (default)
			h925c15: 	'0x' + hcBase1 + hcBase2 + hcBase3 + hcBase4 + hcBase5 + '3AD2CAFFFFFFFFFFFFFFFF', // 3072-bit MODP
			h1234c16: 	'0x' + hcBase1 + hcBase2 + hcBase3 + hcBase4 + hcBase5 + hcBase6 + '63199FFFFFFFFFFFFFFFF', // 4096-bit MODP
			h1850c17: 	'0x' + hcBase1 + hcBase2 + hcBase3 + hcBase4 + hcBase5 + hcBase6 + hcBase7 + 'CC4024FFFFFFFFFFFFFFFF', // 6144-bit MODP
			h2467c18: 	'0x' + hcBase1 + hcBase2 + hcBase3 + hcBase4 + hcBase5 + hcBase6 + hcBase7 + 'BE115974A3926F12FEE5E438777CB6A932DF8CD8BEC4D073B931BA3BC832B68D9DD300741FA7BF8AFC47ED2576F6936BA424663AAB639C5AE4F5683423B4742BF1C978238F16CBE39D652DE3FDB8BEFC848AD922222E04A4037C0713EB57A81A23F0C73473FC646CEA306B4BCBC8862F8385DDFA9D4B7FA2C087E879683303ED5BDD3A062B3CF5B3A278A66D2A13F83F44F82DDF310EE074AB6A364597E899A0255DC164F31CC50846851DF9AB48195DED7EA1B1D510BD7EE74D73FAF36BC31ECFA268359046F4EB879F924009438B481C6CD7889A002ED5EE382BC9190DA6FC026E479558E4475677E9AA9E3050E2765694DFC81F56E880B96E7160C980DD98EDD3DFFFFFFFFFFFFFFFFF', // 8192-bit MODP
		};
		//_p$.log(_N$, 'DhKx High Primes', primesBigint);
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
			case 'h463c5':
			case 'h617c14':
			case 'h925c15':
			case 'h1234c16':
			case 'h1850c17':
			case 'h2467c18':
				px = primesBigint[String(prix)];
				break;
			default:
				if(prix !== 'default') {
					_p$.warn(_N$, 'prime: Invalid Prime Selection (Bigint), using defaults:', prix);
				} //end if
				px = primesBigint['h061'];
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
// CLASS :: Crypto Cipher Twofish CBC
//=======================================

// This class contains portions of code from:
// 	* JS-Twofish: JS-Twofish: github.com/wouldgo/twofish, Copyright (c) Dario Andrei, License: MIT # 2017-04-18
/**
 * CLASS :: Smart Crypto Cipher Twofish (ES6, Strict Mode)
 *
 * Required Key size: 32 bytes (256 bits)
 * Required Iv  size: 16 bytes (128 bits)
 *
 * @package Sf.Javascript:Crypto
 *
 * @private : for internal use only
 *
 * @requires		smartJ$Utils
 *
 * @desc Twofish (CBC) for JavaScript: Encrypt / Decrypt
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
 * @class smartJ$CryptoCipherTwofish
 * @static
 * @frozen
 *
 */
const smartJ$CryptoCipherTwofish = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$CryptoCipherTwofish';

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

	//==
	// Based on twofish.js # v1.0.1 # github.com/wouldgo/twofish
	// (c) 2013 Dario Andrei
	// License: MIT
	//==

	//-- from twofish.js { utils }
	const isByteArray = function(someVar) { // do not make it arrow ... don't know if behaves correctly with Object Prototype ...
		if(Array.isArray(someVar) || Object.prototype.toString.call(someVar) === '[object Uint8Array]') {
			return true;
		} //end if
		return false;
	};
	//--

	//--
	// DO NOT CHANGE ANYTHING BELOW ... will break up things badly !
	// the below setting seems correctly set when comparing output with Twofish-PHP or Twofish-Go ... it is similar
	//--

	const BLOCK_SIZE = 16; // the Twofish blocksize is exactly 16 bytes = 128 bit
	const ROUNDS = 16; // Twofish rounds
	const SK_STEP = 0x02020202;
	const SK_BUMP = 0x01010101;
	const SK_ROTL = 9;

	const INPUT_WHITEN = 0;
	const OUTPUT_WHITEN = INPUT_WHITEN + BLOCK_SIZE / 4;
	const ROUND_SUBKEYS = OUTPUT_WHITEN + BLOCK_SIZE / 4; // 2 * (# rounds)

	//-- S-boxes
	const P0 = new Uint8Array([
		0xA9, 0x67, 0xB3, 0xE8, 0x04, 0xFD, 0xA3, 0x76,
		0x9A, 0x92, 0x80, 0x78, 0xE4, 0xDD, 0xD1, 0x38,
		0x0D, 0xC6, 0x35, 0x98, 0x18, 0xF7, 0xEC, 0x6C,
		0x43, 0x75, 0x37, 0x26, 0xFA, 0x13, 0x94, 0x48,
		0xF2, 0xD0, 0x8B, 0x30, 0x84, 0x54, 0xDF, 0x23,
		0x19, 0x5B, 0x3D, 0x59, 0xF3, 0xAE, 0xA2, 0x82,
		0x63, 0x01, 0x83, 0x2E, 0xD9, 0x51, 0x9B, 0x7C,
		0xA6, 0xEB, 0xA5, 0xBE, 0x16, 0x0C, 0xE3, 0x61,
		0xC0, 0x8C, 0x3A, 0xF5, 0x73, 0x2C, 0x25, 0x0B,
		0xBB, 0x4E, 0x89, 0x6B, 0x53, 0x6A, 0xB4, 0xF1,
		0xE1, 0xE6, 0xBD, 0x45, 0xE2, 0xF4, 0xB6, 0x66,
		0xCC, 0x95, 0x03, 0x56, 0xD4, 0x1C, 0x1E, 0xD7,
		0xFB, 0xC3, 0x8E, 0xB5, 0xE9, 0xCF, 0xBF, 0xBA,
		0xEA, 0x77, 0x39, 0xAF, 0x33, 0xC9, 0x62, 0x71,
		0x81, 0x79, 0x09, 0xAD, 0x24, 0xCD, 0xF9, 0xD8,
		0xE5, 0xC5, 0xB9, 0x4D, 0x44, 0x08, 0x86, 0xE7,
		0xA1, 0x1D, 0xAA, 0xED, 0x06, 0x70, 0xB2, 0xD2,
		0x41, 0x7B, 0xA0, 0x11, 0x31, 0xC2, 0x27, 0x90,
		0x20, 0xF6, 0x60, 0xFF, 0x96, 0x5C, 0xB1, 0xAB,
		0x9E, 0x9C, 0x52, 0x1B, 0x5F, 0x93, 0x0A, 0xEF,
		0x91, 0x85, 0x49, 0xEE, 0x2D, 0x4F, 0x8F, 0x3B,
		0x47, 0x87, 0x6D, 0x46, 0xD6, 0x3E, 0x69, 0x64,
		0x2A, 0xCE, 0xCB, 0x2F, 0xFC, 0x97, 0x05, 0x7A,
		0xAC, 0x7F, 0xD5, 0x1A, 0x4B, 0x0E, 0xA7, 0x5A,
		0x28, 0x14, 0x3F, 0x29, 0x88, 0x3C, 0x4C, 0x02,
		0xB8, 0xDA, 0xB0, 0x17, 0x55, 0x1F, 0x8A, 0x7D,
		0x57, 0xC7, 0x8D, 0x74, 0xB7, 0xC4, 0x9F, 0x72,
		0x7E, 0x15, 0x22, 0x12, 0x58, 0x07, 0x99, 0x34,
		0x6E, 0x50, 0xDE, 0x68, 0x65, 0xBC, 0xDB, 0xF8,
		0xC8, 0xA8, 0x2B, 0x40, 0xDC, 0xFE, 0x32, 0xA4,
		0xCA, 0x10, 0x21, 0xF0, 0xD3, 0x5D, 0x0F, 0x00,
		0x6F, 0x9D, 0x36, 0x42, 0x4A, 0x5E, 0xC1, 0xE0
	]);
	const P1 = new Uint8Array([
		0x75, 0xF3, 0xC6, 0xF4, 0xDB, 0x7B, 0xFB, 0xC8,
		0x4A, 0xD3, 0xE6, 0x6B, 0x45, 0x7D, 0xE8, 0x4B,
		0xD6, 0x32, 0xD8, 0xFD, 0x37, 0x71, 0xF1, 0xE1,
		0x30, 0x0F, 0xF8, 0x1B, 0x87, 0xFA, 0x06, 0x3F,
		0x5E, 0xBA, 0xAE, 0x5B, 0x8A, 0x00, 0xBC, 0x9D,
		0x6D, 0xC1, 0xB1, 0x0E, 0x80, 0x5D, 0xD2, 0xD5,
		0xA0, 0x84, 0x07, 0x14, 0xB5, 0x90, 0x2C, 0xA3,
		0xB2, 0x73, 0x4C, 0x54, 0x92, 0x74, 0x36, 0x51,
		0x38, 0xB0, 0xBD, 0x5A, 0xFC, 0x60, 0x62, 0x96,
		0x6C, 0x42, 0xF7, 0x10, 0x7C, 0x28, 0x27, 0x8C,
		0x13, 0x95, 0x9C, 0xC7, 0x24, 0x46, 0x3B, 0x70,
		0xCA, 0xE3, 0x85, 0xCB, 0x11, 0xD0, 0x93, 0xB8,
		0xA6, 0x83, 0x20, 0xFF, 0x9F, 0x77, 0xC3, 0xCC,
		0x03, 0x6F, 0x08, 0xBF, 0x40, 0xE7, 0x2B, 0xE2,
		0x79, 0x0C, 0xAA, 0x82, 0x41, 0x3A, 0xEA, 0xB9,
		0xE4, 0x9A, 0xA4, 0x97, 0x7E, 0xDA, 0x7A, 0x17,
		0x66, 0x94, 0xA1, 0x1D, 0x3D, 0xF0, 0xDE, 0xB3,
		0x0B, 0x72, 0xA7, 0x1C, 0xEF, 0xD1, 0x53, 0x3E,
		0x8F, 0x33, 0x26, 0x5F, 0xEC, 0x76, 0x2A, 0x49,
		0x81, 0x88, 0xEE, 0x21, 0xC4, 0x1A, 0xEB, 0xD9,
		0xC5, 0x39, 0x99, 0xCD, 0xAD, 0x31, 0x8B, 0x01,
		0x18, 0x23, 0xDD, 0x1F, 0x4E, 0x2D, 0xF9, 0x48,
		0x4F, 0xF2, 0x65, 0x8E, 0x78, 0x5C, 0x58, 0x19,
		0x8D, 0xE5, 0x98, 0x57, 0x67, 0x7F, 0x05, 0x64,
		0xAF, 0x63, 0xB6, 0xFE, 0xF5, 0xB7, 0x3C, 0xA5,
		0xCE, 0xE9, 0x68, 0x44, 0xE0, 0x4D, 0x43, 0x69,
		0x29, 0x2E, 0xAC, 0x15, 0x59, 0xA8, 0x0A, 0x9E,
		0x6E, 0x47, 0xDF, 0x34, 0x35, 0x6A, 0xCF, 0xDC,
		0x22, 0xC9, 0xC0, 0x9B, 0x89, 0xD4, 0xED, 0xAB,
		0x12, 0xA2, 0x0D, 0x52, 0xBB, 0x02, 0x2F, 0xA9,
		0xD7, 0x61, 0x1E, 0xB4, 0x50, 0x04, 0xF6, 0xC2,
		0x16, 0x25, 0x86, 0x56, 0x55, 0x09, 0xBE, 0x91
	]);
	//--

	const P = [ P0, P1 ];

	// Fixed p0/p1 permutations used in S-box lookup.
	// Change the following constant definitions, then S-boxes will automatically get changed.
	const P_00 = 1;
	const P_01 = 0;
	const P_02 = 0;
	const P_03 = P_01 ^ 1;
	const P_04 = 1;
	const P_10 = 0;
	const P_11 = 0;
	const P_12 = 1;
	const P_13 = P_11 ^ 1;
	const P_14 = 0;
	const P_20 = 1;
	const P_21 = 1;
	const P_22 = 0;
	const P_23 = P_21 ^ 1;
	const P_24 = 0;
	const P_30 = 0;
	const P_31 = 1;
	const P_32 = 1;
	const P_33 = P_31 ^ 1;
	const P_34 = 1;

	const GF256_FDBK_2 = Math.floor(0x169 / 2);
	const GF256_FDBK_4 = Math.floor(0x169 / 4);
	const RS_GF_FDBK = 0x14D;

	const lfsr1 = (x) => {
		return x >> 1 ^ ((x & 0x01) !== 0 ? GF256_FDBK_2 : 0);
	};
	const lfsr2 = (x) => {
		return x >> 2 ^ ((x & 0x02) !== 0 ? GF256_FDBK_2 : 0) ^ ((x & 0x01) !== 0 ? GF256_FDBK_4 : 0);
	};
	const mxX = (x) => {
		return x ^ lfsr2(x);
	};
	const mxY = (x) => {
		return x ^ lfsr1(x) ^ lfsr2(x);
	};

	const calcMDS = () => { // MDS expand boxes
		//--
		let localMDS = [ [], [], [], [] ];
		let m1 = [], mX = [], mY = [];
		let i, j;
		//--
		for(i=0; i<256; i+=1) {
			//--
			j = P[0][i] & 0xFF;
			m1[0] = j;
			mX[0] = mxX(j) & 0xFF;
			mY[0] = mxY(j) & 0xFF;
			//--
			j = P[1][i] & 0xFF;
			m1[1] = j;
			mX[1] = mxX(j) & 0xFF;
			mY[1] = mxY(j) & 0xFF;
			//--
			localMDS[0][i] = m1[P_00] << 0 | mX[P_00] << 8 | mY[P_00] << 16 | mY[P_00] << 24;
			localMDS[1][i] = mY[P_10] << 0 | mY[P_10] << 8 | mX[P_10] << 16 | m1[P_10] << 24;
			localMDS[2][i] = mX[P_20] << 0 | mY[P_20] << 8 | m1[P_20] << 16 | mY[P_20] << 24;
			localMDS[3][i] = mX[P_30] << 0 | m1[P_30] << 8 | mY[P_30] << 16 | mX[P_30] << 24;
			//--
		} //end for
		//--
		return [ new Uint32Array(localMDS[0]), new Uint32Array(localMDS[1]), new Uint32Array(localMDS[2]), new Uint32Array(localMDS[3]) ];
		//--
	};

	const MDS = calcMDS();

	const b0 = (x) => x & 0xFF;
	const b1 = (x) => x >>> 8 & 0xFF;
	const b2 = (x) => x >>> 16 & 0xFF;
	const b3 = (x) => x >>> 24 & 0xFF;

	const chooseB = (x, N) => {
		//--
		let result = 0;
		switch(N % 4) {
			case 0:
				result = b0(x);
				break;
			case 1:
				result = b1(x);
				break;
			case 2:
				result = b2(x);
				break;
			case 3:
				result = b3(x);
				break;
			default:
		} //end switch
		//--
		return result;
		//--
	};

	const rsRem = (x) => {
		//--
		const b = x >>> 24 & 0xFF;
		const g2 = (b << 1 ^ ((b & 0x80) !== 0 ? RS_GF_FDBK : 0)) & 0xFF;
		const g3 = b >>> 1 ^ ((b & 0x01) !== 0 ? RS_GF_FDBK >>> 1 : 0 ) ^ g2;
		const result = x << 8 ^ g3 << 24 ^ g2 << 16 ^ g3 << 8 ^ b;
		//--
		return result;
		//--
	};

	const rsMDSEncode = (k0, k1) => {
		//--
		let index = 0;
		for(; index < 4; index += 1) {
			k1 = rsRem(k1);
		} //end for
		k1 ^= k0;
		for(index = 0; index < 4; index += 1) {
			k1 = rsRem(k1);
		} //end for
		//--
		return k1;
		//--
	};

	const f32 = (k64Cnt, x, k32) => {
		//--
		let lB0 = b0(x), lB1 = b1(x), lB2 = b2(x), lB3 = b3(x);
		let k0 = k32[0] || 0, k1 = k32[1] || 0, k2 = k32[2] || 0, k3 = k32[3] || 0;
		let result = 0;
		//--
		switch(k64Cnt & 3) {
			case 1:
				result = MDS[0][P[P_01][lB0] & 0xFF ^ b0(k0)] ^
						 MDS[1][P[P_11][lB1] & 0xFF ^ b1(k0)] ^
						 MDS[2][P[P_21][lB2] & 0xFF ^ b2(k0)] ^
						 MDS[3][P[P_31][lB3] & 0xFF ^ b3(k0)];
				break;
			case 0:  // same as 4
				lB0 = P[P_04][lB0] & 0xFF ^ b0(k3);
				lB1 = P[P_14][lB1] & 0xFF ^ b1(k3);
				lB2 = P[P_24][lB2] & 0xFF ^ b2(k3);
				lB3 = P[P_34][lB3] & 0xFF ^ b3(k3);
				// falls through, no break
			case 3:
				lB0 = P[P_03][lB0] & 0xFF ^ b0(k2);
				lB1 = P[P_13][lB1] & 0xFF ^ b1(k2);
				lB2 = P[P_23][lB2] & 0xFF ^ b2(k2);
				lB3 = P[P_33][lB3] & 0xFF ^ b3(k2);
				// falls through, no break
			case 2:
				result = MDS[0][P[P_01][P[P_02][lB0] & 0xFF ^ b0(k1)] & 0xFF ^ b0(k0)] ^
				 MDS[1][P[P_11][P[P_12][lB1] & 0xFF ^ b1(k1)] & 0xFF ^ b1(k0)] ^
				 MDS[2][P[P_21][P[P_22][lB2] & 0xFF ^ b2(k1)] & 0xFF ^ b2(k0)] ^
				 MDS[3][P[P_31][P[P_32][lB3] & 0xFF ^ b3(k1)] & 0xFF ^ b3(k0)];
				break;
			default:
				// nothing
		} //end switch
		//--
		return result;
		//--
	};

	const fe32 = (sBox, x, R) => {
		//--
		const toReturn = sBox[2 * chooseB(x, R)] ^ sBox[2 * chooseB(x, R + 1) + 1] ^ sBox[0x200 + 2 * chooseB(x, R + 2)] ^ sBox[0x200 + 2 * chooseB(x, R + 3) + 1];
		//--
		return new Uint32Array([toReturn])[0];
		//--
	};

	const xorBuffers = (a, b) => { // throws
		//--
		const _m$ = 'xorBuffers';
		//--
		if(
			!a || !isByteArray(a) || !a.length
			||
			!b || !isByteArray(b) || !b.length
		) {
			throw _m$ + ': Invalid Input';
			return new Uint8Array();
		} //end if
		//--
		if(a.length !== b.length) {
			throw _m$ + ': Buffer length (xor) must be equal';
			return new Uint8Array();
		} //end if
		//--
		a = new Uint8Array(a);
		b = new Uint8Array(b);
		//--
		let res = [], index = 0;
		for(index = 0; index < a.length; index += 1) {
			res[index] = (a[index] ^ b[index]) & 0xFF;
		} //end for
		//--
		return new Uint8Array(res);
		//--
	};

	const expandKey = (aKey) => { // throws
		//--
		const _m$ = 'expandKey';
		//--
		if(!aKey || !isByteArray(aKey)) {
			throw _m$ + ': key parameter is undefined or not an array';
			return [];
		} //end if
		if(aKey.length != 32) { // fix by unixman, allow just max supported key size (security)
			throw _m$ + ': key size must be 32';
			return [];
		} //end if
		//--
		let tmpKey = [];
		let index = 0;
		let nValue, limitedKey;
		let keyLenght = aKey.length;
		let k64Cnt = keyLenght / 8;
		let subKeys = [], subkeyCnt = ROUND_SUBKEYS + 2 * ROUNDS;
		let k32e = [], k32o = [], sBoxKey = [], sBox = [];
		let i, j;
		let offset = 0;
		let q, A, B;
		let lB0, lB1, lB2, lB3;
		let k0, k1, k2, k3;
		/* key length different than 32 bytes (max supported by Twofish) is disabled: unixman
		if(
			keyLenght < 8 || keyLenght > 8
			&&
			keyLenght < 16 || keyLenght > 16
			&&
			keyLenght < 24 || keyLenght > 24
			&&
			keyLenght < 32
		) {
			for(index = 0; index < aKey.length + (8 - aKey.length); index += 1) {
				nValue = aKey[index];
				if(nValue !== undefined) {
					tmpKey.push(nValue);
				} else {
					tmpKey.push(0x00);
				} //end if
			} //end for
			aKey = tmpKey;
		} else if(keyLenght > 32) {
			limitedKey = [];
			for(index = 0; index < 32; index += 1) {
				limitedKey.push(aKey[index]);
			} //end for
			aKey = limitedKey;
		} //end if else
		*/
		aKey = new Uint8Array(aKey);
		keyLenght = aKey.length;
		for(i = 0, j = k64Cnt - 1; i < 4 && offset < keyLenght; i += 1, j -= 1) {
			k32e[i] = aKey[offset++] & 0xFF | (aKey[offset++] & 0xFF) << 8 | (aKey[offset++] & 0xFF) << 16 | (aKey[offset++] & 0xFF) << 24;
			k32o[i] = aKey[offset++] & 0xFF | (aKey[offset++] & 0xFF) << 8 | (aKey[offset++] & 0xFF) << 16 | (aKey[offset++] & 0xFF) << 24;
			sBoxKey[j] = rsMDSEncode(k32e[i], k32o[i]);
		} //end for
		for(i = q = 0; i < subkeyCnt / 2; i += 1, q += SK_STEP) {
			A = f32(k64Cnt, q, k32e);
			B = f32(k64Cnt, q + SK_BUMP, k32o);
			B = B << 8 | B >>> 24;
			A += B;
			subKeys[2 * i] = A;
			A += B;
			subKeys[2 * i + 1] = A << SK_ROTL | A >>> 32 - SK_ROTL;
		} //end for
		k0 = sBoxKey[0];
		k1 = sBoxKey[1];
		k2 = sBoxKey[2];
		k3 = sBoxKey[3];
		for(i = 0; i < 256; i += 1) {
			lB0 = lB1 = lB2 = lB3 = i;
			switch(k64Cnt & 3) {
				case 1:
					sBox[2 * i] = MDS[0][P[P_01][lB0] & 0xFF ^ b0(k0)];
					sBox[2 * i + 1] = MDS[1][P[P_11][lB1] & 0xFF ^ b1(k0)];
					sBox[0x200 + 2 * i] = MDS[2][P[P_21][lB2] & 0xFF ^ b2(k0)];
					sBox[0x200 + 2 * i + 1] = MDS[3][P[P_31][lB3] & 0xFF ^ b3(k0)];
					break;
				case 0:
					lB0 = P[P_04][lB0] & 0xFF ^ b0(k3);
					lB1 = P[P_14][lB1] & 0xFF ^ b1(k3);
					lB2 = P[P_24][lB2] & 0xFF ^ b2(k3);
					lB3 = P[P_34][lB3] & 0xFF ^ b3(k3);
					// falls through, no break
				case 3:
					lB0 = P[P_03][lB0] & 0xFF ^ b0(k2);
					lB1 = P[P_13][lB1] & 0xFF ^ b1(k2);
					lB2 = P[P_23][lB2] & 0xFF ^ b2(k2);
					lB3 = P[P_33][lB3] & 0xFF ^ b3(k2);
					// falls through, no break
				case 2:
					sBox[2 * i] = MDS[0][P[P_01][P[P_02][lB0] & 0xFF ^ b0(k1)] & 0xFF ^ b0(k0)];
					sBox[2 * i + 1] = MDS[1][P[P_11][P[P_12][lB1] & 0xFF ^ b1(k1)] & 0xFF ^ b1(k0)];
					sBox[0x200 + 2 * i] = MDS[2][P[P_21][P[P_22][lB2] & 0xFF ^ b2(k1)] & 0xFF ^ b2(k0)];
					sBox[0x200 + 2 * i + 1] = MDS[3][P[P_31][P[P_32][lB3] & 0xFF ^ b3(k1)] & 0xFF ^ b3(k0)];
					break;
				default:
					// nothing
			} //end switch
		} //end for
		//--
		return [ sBox, subKeys ];
		//--
	};

	const blockEncrypt = (sessionKey, input, inOffset) => { // throws
		//--
		if(
			!sessionKey || !isByteArray(sessionKey) || !sessionKey.length
			||
			!input || !isByteArray(input) || !input.length
		) {
			throw 'blockEncrypt: sessionKey is empty or input block is not a non-empty array';
			return new Uint8Array();
		} //end if
		//--
		input = new Uint8Array(input);
		//--
		const sBox = sessionKey[0];
		const sKey = sessionKey[1];
		//--
		let x0 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x1 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x2 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x3 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		//--
		let	k = ROUND_SUBKEYS;
		let t0, t1;
		//--
		x0 ^= sKey[INPUT_WHITEN];
		x1 ^= sKey[INPUT_WHITEN + 1];
		x2 ^= sKey[INPUT_WHITEN + 2];
		x3 ^= sKey[INPUT_WHITEN + 3];
		//--
		for(let R = 0; R < ROUNDS; R += 2) {
			//--
			t0 = fe32(sBox, x0, 0);
			t1 = fe32(sBox, x1, 3);
			//--
			x2 ^= t0 + t1 + sKey[k++];
			//--
			x2 = x2 >>> 1 | x2 << 31;
			x3 = x3 << 1 | x3 >>> 31;
			//--
			x3 ^= t0 + 2 * t1 + sKey[k++];
			//--
			t0 = fe32(sBox, x2, 0);
			t1 = fe32(sBox, x3, 3);
			//--
			x0 ^= t0 + t1 + sKey[k++];
			//--
			x0 = x0 >>> 1 | x0 << 31;
			x1 = x1 << 1 | x1 >>> 31;
			//--
			x1 ^= t0 + 2 * t1 + sKey[k++];
			//--
		} //end for
		//--
		x2 ^= sKey[OUTPUT_WHITEN];
		x3 ^= sKey[OUTPUT_WHITEN + 1];
		x0 ^= sKey[OUTPUT_WHITEN + 2];
		x1 ^= sKey[OUTPUT_WHITEN + 3];
		//--
		return new Uint8Array([
			x2, x2 >>> 8, x2 >>> 16, x2 >>> 24,
			x3, x3 >>> 8, x3 >>> 16, x3 >>> 24,
			x0, x0 >>> 8, x0 >>> 16, x0 >>> 24,
			x1, x1 >>> 8, x1 >>> 16, x1 >>> 24
		]);
		//--
	};

	const blockDecrypt = (sessionKey, input, inOffset) => { // throws
		//--
		if(
			!sessionKey || !isByteArray(sessionKey) || !sessionKey.length
			||
			!input || !isByteArray(input) || !input.length
		) {
			throw 'blockDecrypt: sessionKey is empty or input block is not a non-empty array';
			return new Uint8Array();
		} //end if
		//--
		const sBox = sessionKey[0];
		const sKey = sessionKey[1];
		//--
		let x2 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x3 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x0 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		let x1 = input[inOffset++] & 0xFF |
				(input[inOffset++] & 0xFF) <<  8 |
				(input[inOffset++] & 0xFF) << 16 |
				(input[inOffset++] & 0xFF) << 24;
		//--
		let k = ROUND_SUBKEYS + 2 * ROUNDS - 1;
		let t0, t1;
		//--
		x2 ^= sKey[OUTPUT_WHITEN];
		x3 ^= sKey[OUTPUT_WHITEN + 1];
		x0 ^= sKey[OUTPUT_WHITEN + 2];
		x1 ^= sKey[OUTPUT_WHITEN + 3];
		//--
		for(let R = 0; R < ROUNDS; R += 2) {
			//--
			t0 = fe32(sBox, x2, 0);
			t1 = fe32(sBox, x3, 3);
			//--
			x1 ^= t0 + 2 * t1 + sKey[k--];
			//--
			x1 = x1 >>> 1 | x1 << 31;
			x0 = x0 << 1 | x0 >>> 31;
			//--
			x0 ^= t0 + t1 + sKey[k--];
			//--
			t0 = fe32(sBox, x0, 0);
			t1 = fe32(sBox, x1, 3);
			//--
			x3 ^= t0 + 2 * t1 + sKey[k--];
			//--
			x3 = x3 >>> 1 | x3 << 31;
			x2 = x2 << 1 | x2 >>> 31;
			//--
			x2 ^= t0 + t1 + sKey[k--];
			//--
		} //end for
		//--
		x0 ^= sKey[INPUT_WHITEN];
		x1 ^= sKey[INPUT_WHITEN + 1];
		x2 ^= sKey[INPUT_WHITEN + 2];
		x3 ^= sKey[INPUT_WHITEN + 3];
		//--
		return new Uint8Array([
			x0, x0 >>> 8, x0 >>> 16, x0 >>> 24,
			x1, x1 >>> 8, x1 >>> 16, x1 >>> 24,
			x2, x2 >>> 8, x2 >>> 16, x2 >>> 24,
			x3, x3 >>> 8, x3 >>> 16, x3 >>> 24
		]);
		//--
	};

	//-- by unixman
	const twofishFixPadding = (str) => {
		str = String(str || '');
		const padding = Math.ceil(str.length / BLOCK_SIZE) * BLOCK_SIZE; // twofish blocksize is 16 ; {{{SYNC-ENCRYPTY-B64-PADDING}}}
		str = str.padEnd(padding, ' ');
		return String(str || '');
	};
	//-- #end unixman

	//==

	/**
	 * Twofish encrypts (CBC) plainText using the encryption key and iv
	 *
	 * @private : for internal use only
	 *
	 * @memberof smartJ$CryptoCipherTwofish
	 * @method encryptCBC
	 * @static
	 *
	 * @param {String} plainText The plain string
	 * @param {String} theKey The encryption key ; 32 bytes (256 bit)
	 * @param {String} IV The initialization vector ; 16 bytes (128 bit)
	 * @return {String} The Twofish encrypted string
	 */
	const encryptCBC = (plainText, theKey, IV) => {
		//--
		const _m$ = 'encryptCBC';
		//--
		plainText = _Utils$.stringPureVal(plainText); // do not trim
		if(plainText == '') {
			return '';
		} //end if
		//--
		theKey = _Utils$.stringPureVal(theKey, true);
		if((theKey == '') || (theKey.length !== (BLOCK_SIZE * 2))) { // 32
			_p$.error(_N$, _m$, 'ERR:', 'Key Length is Invalid (req: 256 bit)', theKey.length);
			return '';
		} //end if else
		//--
		IV = _Utils$.stringPureVal(IV, true);
		if((IV == '') || (IV.length !== BLOCK_SIZE)) { // 16
			_p$.error(_N$, _m$, 'ERR:', 'IV Length is Invalid (req: 128 bit)', IV.length);
			return '';
		} //end if else
		//--
		plainText = twofishFixPadding(plainText);
		plainText = _Utils$.strToByteArray(plainText);
		plainText = new Uint8Array(plainText);
		//--
		theKey = _Utils$.strToByteArray(theKey);
		theKey = new Uint8Array(theKey);
		try {
			theKey = expandKey(theKey);
		} catch(err) {
			_p$.error(_N$, _m$, 'ERR:', 'Key Expand', err);
			return '';
		} //end if
		//--
		IV = _Utils$.strToByteArray(IV);
		IV = new Uint8Array(IV);
		//--
		let vector = IV;
		let result = [], cBuffer = [], buffer1 = [], buffer2 = [];
		let pos = 0, index = 0, secondIndex = 0;
		let tmpCBuffer, nVal, position;
		//--
		const loops = plainText.length / BLOCK_SIZE;
		for(; index < loops; index += 1) {
			//--
			cBuffer = plainText.subarray(pos, pos + BLOCK_SIZE);
			//--
			if(cBuffer.length < BLOCK_SIZE) {
				tmpCBuffer = [];
				for(let paddingIndex = 0; paddingIndex < BLOCK_SIZE; paddingIndex += 1) {
					nVal = cBuffer[paddingIndex];
					if(nVal !== undefined) {
						tmpCBuffer.push(nVal);
					} else {
						tmpCBuffer.push(0x00);
					} //end if else
				} //end for
				cBuffer = tmpCBuffer;
			} //end if
			//--
			try {
				buffer1 = xorBuffers(cBuffer, vector);
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR:', 'Buff#1', err);
				return '';
			} //end if
			try {
				buffer2 = blockEncrypt(theKey, buffer1, 0);
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR:', 'Buff#2', err);
				return '';
			} //end if
			//--
			for(secondIndex = pos; secondIndex < buffer2.length + pos; secondIndex += 1) {
				position = secondIndex - pos;
				if(buffer2[position] !== undefined) {
					result.splice(secondIndex, 0, buffer2[position]);
				} //end if
			} //end for
			//--
			vector = buffer2;
			pos += BLOCK_SIZE;
			//--
		} //end for
		//--
		return String.fromCharCode(...result); // raw binary data ; do not trim
		//--
	};
	_C$.encryptCBC = encryptCBC; // export

	/**
	 * Twofish decrypts (CBC) cipherText using the encryption key and iv
	 *
	 * @private : for internal use only
	 *
	 * @memberof smartJ$CryptoCipherTwofish
	 * @method decryptCBC
	 * @static
	 *
	 * @param {String} cipherText The Twofish encrypted string
	 * @param {String} theKey The encryption key ; 32 bytes (256 bit)
	 * @param {String} IV The initialization vector ; 16 bytes (128 bit)
	 * @return {String} The Twofish decrypted string
	 */
	const decryptCBC = (cipherText, theKey, IV) => {
		//--
		const _m$ = 'decryptCBC';
		//--
		cipherText = _Utils$.stringPureVal(cipherText); // do not trim, it is raw data
		if(cipherText == '') {
			return '';
		} //end if
		//--
		theKey = _Utils$.stringPureVal(theKey, true);
		if((theKey == '') || (theKey.length !== (BLOCK_SIZE * 2))) { // 32
			_p$.error(_N$, _m$, 'ERR:', 'Key Length is Invalid (req: 256 bit)', theKey.length);
			return '';
		} //end if else
		//--
		IV = _Utils$.stringPureVal(IV, true);
		if((IV == '') || (IV.length !== BLOCK_SIZE)) { // 16
			_p$.error(_N$, _m$, 'ERR:', 'IV Length is Invalid (req: 128 bit)', IV.length);
			return '';
		} //end if else
		//--
		cipherText = _Utils$.strToByteArray(cipherText);
		cipherText = new Uint8Array(cipherText);
		//--
		theKey = _Utils$.strToByteArray(theKey);
		theKey = new Uint8Array(theKey);
		try {
			theKey = expandKey(theKey);
		} catch(err) {
			_p$.error(_N$, _m$, 'ERR:', 'Key Expand', err);
			return '';
		} //end if
		//--
		IV = _Utils$.strToByteArray(IV);
		IV = new Uint8Array(IV);
		//--
		let vector = IV;
		let result = [], cBuffer = [], buffer1 = [], buffer2 = [];
		let pos = 0, index = 0, secondIndex = 0;
		let tmpCBuffer, nVal, position;
		//--
		const loops = cipherText.length / BLOCK_SIZE;
		for(; index < loops; index += 1) {
			//--
			cBuffer = cipherText.subarray(pos, pos + BLOCK_SIZE);
			//--
			if(cBuffer.length < BLOCK_SIZE) {
				tmpCBuffer = [];
				for(let paddingIndex = 0; paddingIndex < BLOCK_SIZE; paddingIndex += 1) {
					nVal = cBuffer[paddingIndex];
					if(nVal !== undefined) {
						tmpCBuffer.push(nVal);
					} else {
						tmpCBuffer.push(0x00);
					} //end if else
				} //end for
				cBuffer = tmpCBuffer;
			} //end if
			//--
			try {
				buffer1 = blockDecrypt(theKey, cBuffer, 0);
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR:', 'Buff#1', err);
				return '';
			} //end if
			try {
				buffer2 = xorBuffers(buffer1, vector);
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR:', 'Buff#2', err);
				return '';
			} //end if
			//--
			for(secondIndex = pos; secondIndex < buffer2.length + pos; secondIndex += 1) {
				position = secondIndex - pos;
				if(buffer2[position] !== undefined) {
					result.splice(secondIndex, 0, buffer2[position]);
				} //end if
			} //end for
			//--
			buffer2 = [];
			vector = cBuffer;
			pos += BLOCK_SIZE;
			//--
		} //end for
		//--
		result = _Utils$.stringTrim(String.fromCharCode(...result)); // {{{SYNC-CRYPTO-DECRYPT-TRIM-B64}}}
		//--
		return String(result || '');
		//--
	};
	_C$.decryptCBC = decryptCBC; // export

	//==

}}; //END CLASS

smartJ$CryptoCipherTwofish.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CryptoCipherTwofish = smartJ$CryptoCipherTwofish; // global export
} //end if


//=======================================
// CLASS :: Crypto Cipher Blowfish CBC
//=======================================

// Blowfish.js from Dojo Toolkit 1.8.1 # 2005-12-08
// License: New BSD License
// Cut of by Sladex (xslade@gmail.com)
// Based on the C# implementation by Marcus Hahn (http://www.hotpixel.net/)
// Unsigned math based on Paul Johnstone and Peter Wood patches (#5791).
// This class contains portions of code from:
// 	* JS-Blowfish: github.com/agorlov/javascript-blowfish, Copyright (c) Alexandr Gorlov, License: MIT # 2017-06-23
// Modified by unixman: port to ES6, improved code and optimizations
// NOTICE: Max Key for Blowfish is up to 56 chars length (56 bytes = 448 bits)
/**
 * CLASS :: Smart Crypto Cipher Blowfish CBC (ES6, Strict Mode)
 *
 * Required Key size: 56 bytes (448 bits)
 * Required Iv  size:  8 bytes  (64 bits)
 *
 * @package Sf.Javascript:Crypto
 *
 * @private : for internal use only
 *
 * @requires		smartJ$Utils
 *
 * @desc Blowfish (CBC) for JavaScript: Encrypt / Decrypt
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
 * @class smartJ$CryptoCipherBlowfish
 * @static
 * @frozen
 *
 */
const smartJ$CryptoCipherBlowfish = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$CryptoCipherBlowfish';

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

	//== PRIVATE BLOWFISH

	const BLOCK_SIZE = 8; // Blowfish blocksize is exactly 8 bytes = 64 bit

	// Objects for processing Blowfish encryption/decryption
	const POW2 = Math.pow(2,2);
	const POW3 = Math.pow(2,3);
	const POW4 = Math.pow(2,4);
	const POW8 = Math.pow(2,8);
	const POW16 = Math.pow(2,16);
	const POW24 = Math.pow(2,24);

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

	//== PRIVATES BLOWFISH

	const isString = (it) => {
		return !! (typeof(it) == 'string' || it instanceof String); // Boolean ; return true if it is a String
	}; //END

	const add = (x,y) => {
		return (((x>>0x10)+(y>>0x10)+(((x&0xffff)+(y&0xffff))>>0x10))<<0x10)|(((x&0xffff)+(y&0xffff))&0xffff);
	}; //END

	const xor = (x,y) => {
		return (((x>>0x10)^(y>>0x10))<<0x10)|(((x&0xffff)^(y&0xffff))&0xffff);
	}; //END

	const dollar = (v, box) => {
		//--
		const d = box.s3[v&0xff]; v>>=8;
		const c = box.s2[v&0xff]; v>>=8;
		const b = box.s1[v&0xff]; v>>=8;
		const a = box.s0[v&0xff];
		//--
		let r;
		r = (((a>>0x10)+(b>>0x10)+(((a&0xffff)+(b&0xffff))>>0x10))<<0x10)|(((a&0xffff)+(b&0xffff))&0xffff);
		r = (((r>>0x10)^(c>>0x10))<<0x10)|(((r&0xffff)^(c&0xffff))&0xffff);
		//--
		return (((r>>0x10)+(d>>0x10)+(((r&0xffff)+(d&0xffff))>>0x10))<<0x10)|(((r&0xffff)+(d&0xffff))&0xffff);
		//--
	}; //END

	const eb = (o, box) => {
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

	const db = (o, box) => {
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

	const arrMap = function(arr, callback) {
		//--
		const _m$ = 'arrMap';
		//--
		if(!Array.isArray(arr)) {
			_p$.error(_N$, _m$, 'Input must be array'); // err
			return [];
		} //end if
		//--
		if(typeof(callback) != 'function') {
			_p$.error(_N$, _m$, 'CallBack must be function'); // err
			return [];
		} //end if
		//--
		return arr.map(callback);
		//--
	};

	// Important: the contexts are not cached here ; this ways is more secure ...
	const expandKey = (key) => {
		//--
		let k = key;
		//--
		if(isString(k)) {
			k = arrMap(k.split(''), (item) => { return item.charCodeAt(0) & 0xff; });
		} //end if
		//-- init the boxes
		let pos = 0, data = 0;
		const res = {
			left: 0,
			right: 0,
		};
		const box = {
			p: arrMap(
				boxes.p.slice(0),
				(item) => {
					const q = k.length;
					for(let r=0; r<4; r++) {
						data = (data*POW8)|k[pos++ % q];
					} //end for
					return (((item>>0x10)^(data>>0x10))<<0x10)|(((item&0xffff)^(data&0xffff))&0xffff);
				}
			),
			s0: boxes.s0.slice(0),
			s1: boxes.s1.slice(0),
			s2: boxes.s2.slice(0),
			s3: boxes.s3.slice(0)
		};
		//-- encrypt p and the s boxes
		let i, j, l;
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

	const setKey = (key) => {
		//--
		key = _Utils$.stringPureVal(key, true); // trim
		//--
		return String(key || '');
		//--
	}; //END

	const setIV = (iV) => {
		//--
		const iv = {
			IV: '', // must be string, not null
			left: null,
			right: null,
		};
		//--
		iv.IV = _Utils$.stringPureVal(iV, true); // trim
		//--
		const byt = arrMap(iv.IV.split(''), (item) => { return item.charCodeAt(0); }); // pre-process
		//--
		iv.left = byt[0]*POW24|byt[1]*POW16|byt[2]*POW8|byt[3];
		iv.right = byt[4]*POW24|byt[5]*POW16|byt[6]*POW8|byt[7];
		//--
		return iv;
		//--
	}; //END

	//-- by unixman
	const blowfishFixPadding = (str) => {
		str = String(str || '');
		const padding = Math.ceil(str.length / BLOCK_SIZE) * BLOCK_SIZE; // blowfish blocksize is 8 ; {{{SYNC-ENCRYPTY-B64-PADDING}}}
		str = str.padEnd(padding, ' ');
		return String(str || '');
	};
	//-- #end unixman

	//== PUBLIC BLOWFISH

	const bfsig = 'b' + 'f' + (56*8) + '.' + 'v' + (21/7);

	/**
	 * Blowfish encrypts (CBC) plainText using the encryption key and iv
	 *
	 * @private : for internal use only
	 *
	 * @memberof smartJ$CryptoCipherBlowfish
	 * @method encrypt
	 * @static
	 *
	 * @param {String} plainText The plain string
	 * @param {String} key The encryption key ; 56 bytes (448 bit)
	 * @param {String} iV The initialization vector ; 8 bytes (64 bit)
	 * @return {String} The Blowfish encrypted string
	 */
	const encryptCBC = function(plainText, key, iV) {
		//--
		const _m$ = 'encryptCBC';
		//--
		plainText = _Utils$.stringPureVal(plainText); // do not trim
		if(plainText == '') {
			return '';
		} //end if
		//--
		key = _Utils$.stringPureVal(key, true);
		if((key == '') || (key.length !== 56)) { // 56
			_p$.error(_N$, _m$, 'ERR:', 'Key Length is Invalid (req: 448 bit)', key.length);
			return '';
		} //end if else
		//--
		iV = _Utils$.stringPureVal(iV, true);
		if((iV == '') || (iV.length !== 8)) { // 8
			_p$.error(_N$, _m$, 'ERR:', 'IV Length is Invalid (req: 64 bit)', iV.length);
			return '';
		} //end if else
		const objIV = setIV(iV); // needs original key
		key = setKey(key);
		//--
		if(key.length != 7*BLOCK_SIZE) {
			_p$.error(_N$, _m$, 'Invalid Key Init', key.length); // err
			return '';
		} //end if
		if(objIV.IV.length != BLOCK_SIZE) {
			_p$.error(_N$, _m$, 'Invalid IV Init', objIV.IV.length); // err
			return '';
		} //end if
		//--
		const exK = expandKey(key);
		//--
		plainText = blowfishFixPadding(plainText);
		//--
		let cipher = [];
		const count = plainText.length >> 3;
		let pos = 0, o = {};
		const vector = {
			left:  objIV.left  || null,
			right: objIV.right || null,
		};
		//--
		for(let i=0; i<count; i++) {
			//--
			o.left = plainText.charCodeAt(pos) * POW24
				|plainText.charCodeAt(pos+1) * POW16
				|plainText.charCodeAt(pos+2) * POW8
				|plainText.charCodeAt(pos+3);
			o.right = plainText.charCodeAt(pos+4) * POW24
				|plainText.charCodeAt(pos+5) * POW16
				|plainText.charCodeAt(pos+6) * POW8
				|plainText.charCodeAt(pos+7);
			//--
			o.left = (((o.left>>0x10)^(vector.left>>0x10))<<0x10)|(((o.left&0xffff)^(vector.left&0xffff))&0xffff);
			o.right = (((o.right>>0x10)^(vector.right>>0x10))<<0x10)|(((o.right&0xffff)^(vector.right&0xffff))&0xffff);
			//--
			eb(o, exK); // encrypt the block
			//--
			vector.left = o.left;
			vector.right = o.right;
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
		return String(arrMap(cipher, (item) => (String.fromCharCode(item))).join('')); // raw binary data
		//--
	}; //END
	_C$.encryptCBC = encryptCBC; // export

	/**
	 * Blowfish decrypts (CBC) cipherText using the encryption key and iv
	 *
	 * @memberof smartJ$CryptoCipherBlowfish
	 * @method decrypt
	 * @static
	 *
	 * @param {String} cipherText The Blowfish encrypted string
	 * @param {String} key The encryption key ; 56 bytes (448 bit)
	 * @param {String} iV The initialization vector ; 8 bytes (64 bit)
	 * @return {String} The Blowfish decrypted string
	 */
	const decryptCBC = function(cipherText, key, iV) {
		//--
		const _m$ = 'decryptCBC';
		//--
		cipherText = _Utils$.stringPureVal(cipherText); // do not trim, it is raw data
		if(cipherText == '') {
			return '';
		} //end if
		//--
		key = _Utils$.stringPureVal(key, true);
		if((key == '') || (key.length !== 56)) { // 56
			_p$.error(_N$, _m$, 'ERR:', 'Key Length is Invalid (req: 448 bit)', key.length);
			return '';
		} //end if else
		//--
		iV = _Utils$.stringPureVal(iV, true);
		if((iV == '') || (iV.length !== 8)) { // 8
			_p$.error(_N$, _m$, 'ERR:', 'IV Length is Invalid (req: 64 bit)', iV.length);
			return '';
		} //end if else
		const objIV = setIV(iV); // needs original key
		key = setKey(key);
		//--
		if(key.length != 7*BLOCK_SIZE) {
			_p$.error(_N$, _m$, 'Invalid Key Init', key.length); // err
			return '';
		} //end if
		if(objIV.IV.length != BLOCK_SIZE) {
			_p$.error(_N$, _m$, 'Invalid IV Init', objIV.IV.length); // err
			return '';
		} //end if
		//--
		const exK = expandKey(key);
		//--
		let pt = [];
		let c = null;
		//--
		c = arrMap(cipherText.split(''), (item) => { return item.charCodeAt(0); });
		cipherText = null; // free mem
		//--
		const count = c.length >> 3;
		let pos = 0, o = {};
		const vector = {
			left:  objIV.left  || null,
			right: objIV.right || null,
		};
		//--
		for(let i=0; i<count; i++) {
			//--
			o.left = c[pos]*POW24|c[pos+1]*POW16|c[pos+2]*POW8|c[pos+3];
			o.right = c[pos+4]*POW24|c[pos+5]*POW16|c[pos+6]*POW8|c[pos+7];
			//--
			let left = null, right = null;
			left = o.left;
			right = o.right;
			//--
			db(o, exK); // decrypt the block
			//--
			o.left = (((o.left>>0x10)^(vector.left>>0x10))<<0x10)|(((o.left&0xffff)^(vector.left&0xffff))&0xffff);
			o.right = (((o.right>>0x10)^(vector.right>>0x10))<<0x10)|(((o.right&0xffff)^(vector.right&0xffff))&0xffff);
			vector.left = left;
			vector.right = right;
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
		//--
		pt = _Utils$.stringTrim(arrMap(pt, (item) => String.fromCharCode(item)).join('')); // {{{SYNC-CRYPTO-DECRYPT-TRIM-B64}}}
		//--
		return String(pt || '');
		//--
	}; //END
	_C$.decryptCBC = decryptCBC; // export

}}; //END CLASS

smartJ$CryptoCipherBlowfish.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CryptoCipherBlowfish = smartJ$CryptoCipherBlowfish; // global export
} //end if


//=======================================
// CLASS :: Cipher Crypto
//=======================================

/**
 * CLASS :: Smart Cipher Crypto (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Crypto
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$BaseConv
 * @requires		smartJ$CryptoHash
 * @requires		smartJ$DhKx
 * @requires		smartJ$CryptoCipherTwofish
 * @requires		smartJ$CryptoCipherBlowfish
 *
 * @desc Smart Twofish / Blowfish (CBC) for JavaScript: Encrypt / Decrypt
 * @author unix-world.org
 * @license BSD
 * @file crypt_utils.js
 * @version 20250304
 * @class smartJ$CipherCrypto
 * @static
 * @frozen
 *
 */
const smartJ$CipherCrypto = new class{constructor(){ // STATIC CLASS (ES6)
	'use strict';
	const _N$ = 'smartJ$CipherCrypto';

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
	const _Ba$eConv = smartJ$BaseConv;
	const _Crypto$Hash = smartJ$CryptoHash;
	const _Dhkx = smartJ$DhKx;
	const _Twofish = smartJ$CryptoCipherTwofish;
	const _Blowfish = smartJ$CryptoCipherBlowfish;

	//--

	const regexSafeCryptoPackageStr = RegExp(/^[a-zA-Z0-9\-_\.;\!]+$/);

	const TfSig = '2' + 'f' + (32*8) + '.' + 'v' + (16/16);
	const BfSig = 'b' + 'f' + (56*8) + '.' + 'v' + (21/7);
	const CkSep = '#' + 'CK' + 'SUM' + (1024/2) + 'V' + (24/8) + '#';

	const DERIVE_MIN_KLEN = 1;
	const DERIVE_MAX_KLEN = 1024*4;
	const DERIVE_PREKEY_LEN = 8*10;
	const DERIVE_CENTITER_EK = 80+7;
	const DERIVE_CENTITER_EV = 70+8;

	const pureStr = (s, t) => _Utils$.stringPureVal(s, t);
	const strm = (s) => _Utils$.stringTrim(s);
	const sstw = (s, c) => _Utils$.stringStartsWith(s, c);
	const scnt = (s, c, p=0) => _Utils$.stringContains(s, c, p);
	const sra = (t, n, s) => _Utils$.stringReplaceAll(t, n, s);
	const rr13 = (s) => _Utils$.strRRot13(s);
	const b2hx = (s, b) => _Utils$.bin2hex(s, b);
	const hx2b = (s, b) => _Utils$.hex2bin(s, b);
	const b64E = (s, b) => _Utils$.b64Enc(s, b);
	const b64D = (s, b) => _Utils$.b64Dec(s, b);
	const b64sE = (s, b, p=!0) => _Ba$eConv.b64s_enc(s, b, p);
	const b64sD = (s, b) => _Ba$eConv.b64s_dec(s, b);
	const bwzchx = (ch, wz) => _Ba$eConv.base_to_hex_convert(ch, wz);
	const bwzhxc = (hx, wz) => _Ba$eConv.base_from_hex_convert(hx, wz);
	const b62hxc = (hx) => bwzhxc(hx, 62);
	const b85hxc = (hx) => bwzhxc(hx, 85);
	const b92hxc = (hx) => bwzhxc(hx, 92);
	const c32b = (s, i) => _Crypto$Hash.crc32b(s, i);
	const hm5 = (s, t) => _Crypto$Hash.md5(s, t);
	const hs1 = (s, t) => _Crypto$Hash.sha1(s, t);
	const hs256 = (s, t) => _Crypto$Hash.sha256(s, t);
	const hs512 = (s, t) => _Crypto$Hash.sha512(s, t);
	const h3a224 = (s, t) => _Crypto$Hash.sh3a224(s, t);
	const h3a384 = (s, t) => _Crypto$Hash.sh3a384(s, t);
	const h3a512 = (s, t) => _Crypto$Hash.sh3a512(s, t);
	const aK = 'Sh' + 'A3' + '-5' + '12';
	const aV = 'Sh' + 'A3' + '-2' + '56';
	const kdf2 = (a, k, s, l, i, n) => _Crypto$Hash.pbkdf2(a, k, s, l, i, n);
	const hmc = (a, k, s, t) => _Crypto$Hash.hmac(a, k, s, t);

	const vByte = '\v';
	const nByte = '\u0000';

	//==

	const pbkdf2PreDv = (k) => {
		//--
		const _m$ = 'pbkdf2PreDv';
		//--
		k = pureStr(k, true);
		const len = k.length;
		if((k == '') || (len < DERIVE_MIN_KLEN) || (len > DERIVE_MAX_KLEN)) {
			_p$.error(_N$, _m$, 'ERR:', 'Invalid Key Length');
			return '';
		} //end if
		//--
		const b64 = h3a384(k, !0); // 64 chars fixed length, B64
		const hex = h3a512(k + vByte + c32b(k, !0) + vByte + rr13(b64)); // 128 chars fixed length, HEX
		const b92 = b92hxc(hex); // variable length, 78..80 (mostly 79) characters, B92
		//--
		const pK = strm(rr13(strm(b92).substring(0, DERIVE_PREKEY_LEN).padEnd(DERIVE_PREKEY_LEN, "'"))); // 80 chars fixed length, B92+
		//--
		if(
			(strm(pK) == '') // avoid being empty
			||
			(strm(sra("'", '', strm(pK))) == '') // avoid being all '
			||
			(pK.length != DERIVE_PREKEY_LEN)
		) {
			_p$.error(_N$, _m$, 'ERR:', 'The B92 PBKDF2 Pre-Derived Key is empty or does not match the expected size');
			return '';
		} //end if
		//--
		return String(pK || '');
		//--
	};
	// no export

	const tfKdIv = (k) => {
		//--
		const kdiv = {
			'err': '?',
			'key': '',
			'iv':  '',
		};
		//--
		const kSz = 256 / 8;
		const iSz = 128 / 8;
		//--
		k = pureStr(k, true);
		const len = k.length;
		if((k == '') || (len < DERIVE_MIN_KLEN) || (len > DERIVE_MAX_KLEN)) {
			kdiv.err = 'Invalid Key Length';
			return kdiv;
		} //end if else
		//--
		const nkSz = kSz * 2; // ensure double size
		const niSz = iSz * 2; // ensure double size
		//--
		const pbkdf2PK = pbkdf2PreDv(k);
		if(
			(pbkdf2PK == '')
			||
			(pbkdf2PK.length != DERIVE_PREKEY_LEN)
		) {
			kdiv.err = 'Invalid Pre-Derived Key Length';
			return kdiv;
		} //end if
		//--
		const pbkdf2PV = pbkdf2PreDv(rr13(b64sE(k, !1)) + nByte + pbkdf2PK);
		if(
			(pbkdf2PV == '')
			||
			(pbkdf2PV.length != DERIVE_PREKEY_LEN)
		) {
			kdiv.err = 'Invalid Pre-Derived Iv Length';
			return kdiv;
		} //end if
		//--
		const sK = '[' + nByte + pbkdf2PV + vByte + c32b(vByte + k + nByte, !0) + nByte + ']'; // s + B36
		const pbkdf2K = strm(kdf2(aK, pbkdf2PK, sK, nkSz, DERIVE_CENTITER_EK, false)); // hex
		if(
			(pbkdf2K == '')
			||
			(pbkdf2K.length != nkSz)
		) {
			kdiv.err = 'Invalid Derived Key Length';
			return kdiv;
		} //end if
		const b92k = strm(rr13(strm(b92hxc(pbkdf2K).substring(0, kSz))));
		if(
			(b92k == '')
			||
			(b92k.length != kSz)
		) {
			kdiv.err = 'Invalid Derived B92 Key Length';
			return kdiv;
		} //end if
		//--
		const sV = '(' + nByte + pbkdf2PK + vByte + c32b(vByte + k + nByte) + nByte + ')'; // s + Hex
		const pbkdf2V = strm(kdf2(aV, pbkdf2PV, sV, niSz, DERIVE_CENTITER_EV, false)); // hex
		if(
			(pbkdf2V == '')
			||
			(pbkdf2V.length != niSz)
		) {
			kdiv.err = 'Invalid Derived Iv Length';
			return kdiv;
		} //end if
		const b85v = strm(rr13(strm(b85hxc(pbkdf2V).substring(0, iSz))));
		if(
			(b85v == '')
			||
			(b85v.length != iSz)
		) {
			kdiv.err = 'Invalid Derived B85 Iv Length';
			return kdiv;
		} //end if
		//--
		kdiv.err = ''; // reset
		kdiv.key = String(b92k || '');
		kdiv.iv  = String(b85v || '');
		return kdiv;
		//--
	};
	// no export

	//--

	const cryptUnpack = (algo, str, CkSep, csum, pak, isBinary) => {
		//--
		const _m$ = 'cryptUnpack:' + pureStr(algo, true);
		//--
		str = pureStr(str, true);
		CkSep = pureStr(CkSep, true);
		csum = pureStr(csum, true);
		pak = pureStr(pak, true);
		isBinary = !! isBinary;
		//--
		if((str == '') || (CkSep == '') || (csum == '') || (pak == '')) {
			_p$.error(_N$, _m$, 'ERR:', 'Data or a required parameter is Empty');
			return '';
		} //end if
		//--
		if(
			(str == '')
			||
			(!scnt(str, CkSep))
		) {
			_p$.error(_N$, _m$, 'ERR:', 'Data Checksum N/A'); // error
			return '';
		} //end if
		str = str.split(CkSep, 2);
		const checksum = pureStr(str[1], true); // trim
		str = pureStr(str[0], true); // trim
		if(b62hxc(h3a512(str)) !== checksum) {
			_p$.error(_N$, _m$, 'ERR:', 'Data Checksum Failed'); // error
			return '';
		} //end if
		//--
		if(scnt(str, '$')) {
			str = str.split('$', 5);
			if(str.length != 4) {
				_p$.warn(_N$, _m$, 'ERR:', 'RND Segments Failed'); // warn
				return '';
			} //end if
			str[0] = strm(str[0]); // random prefix
			str[1] = strm(str[1]); // data
			str[2] = strm(str[2]); // crc32b26
			str[3] = strm(str[3]); // random suffix
			if(!str[0] || (str[0].length != 10)) {
				_p$.warn(_N$, _m$, 'ERR:', 'RND Prefix Failed'); // warn
				return '';
			} //end if
			if(!str[1]) {
				_p$.warn(_N$, _m$, 'ERR:', 'RND Data Failed'); // warn
				return '';
			} //end if
			if((!str[2]) || (c32b(str[1], !0) !== str[2])) {
				_p$.warn(_N$, _m$, 'ERR:', 'RND Checksum Failed'); // warn
				return '';
			} //end if
			if(!str[3] || (str[3].length != 10)) {
				_p$.warn(_N$, _m$, 'ERR:', 'RND Suffix Failed'); // warn
				return '';
			} //end if
			str = str[1];
		} //end if
		//--
		str = b64D(str, isBinary); // decapsulate from B64 (supports unicode data but also binary data)
		if(csum !== b62hxc(h3a224(pak + nByte + str))) {
			_p$.warn(_N$, _m$, 'ERR:', 'Checksum Failed'); // warn
			return '';
		} //end if
		//--
		return String(str);
		//--
	};
	// no export

	//--

	/**
	 * Smart.Twofish encrypts (CBC) a string using a smart derived encryption key and iv (PBKDF2 based)
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method tfEnc
	 * @static
	 *
	 * @param 	{String} 	key 		The encryption key ; 32 bytes (256 bit) ; iV is auto-managed, 16 bytes (128 bit)
	 * @param 	{String} 	str 		The plain string
	 * @param 	{Boolean} 	isBinary 	Encoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @param 	{Boolean} 	randomize	Add randomization ; default is TRUE ; set to false to get a fixed encrypted data, without randomization
	 * @return 	{String} 	The Smart.Twofish encrypted string
	 */
	const tfEnc = (key, str, isBinary=false, randomize=true) => {
		//--
		const _m$ = 'tfEnc';
		//--
		str = pureStr(str); // do not trim
		if(str == '') {
			return '';
		} //end if
		//--
		const oStr = str;
		str = b64E(str, isBinary); // encapsulate to B64 (supports unicode data but also binary data)
		//--
		if(randomize) { // randomize encryption which results always in a different encrypted string
			str = _Utils$.uuid('str') + '$' + str + '$' + c32b(str, !0) + '$' + _Utils$.uuid('num'); // because the prefix is random and CBC is sequential (first block will be always different) will results in a completely different string with every encryption ; also add a suffix because data may be reversed
		} //end if
		//--
		str = str + CkSep + b62hxc(h3a512(str)); // add checksum
		//--
		const kdiv = tfKdIv(key);
		if(!!kdiv.err) {
			_p$.error(_N$, _m$, 'ERR:', 'KD Failed:', kdiv.err);
			return '';
		} //end if
		//--
		str = _Twofish.encryptCBC(str, kdiv.key, kdiv.iv);
		//--
	//	const pak = b64sE(str, !0); // b64s
		const pak = b64sE(str, !0, !1); // b64u
		const csum = b62hxc(h3a224(pak + nByte + oStr));
		//--
		return String(TfSig+'!'+rr13(pak+';'+csum));
		//--
	};
	_C$.tfEnc = tfEnc; // export

	/**
	 * Smart.Twofish decrypts (CBC) a string using a smart derived encryption key and iv (PBKDF2 based)
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method tfDec
	 * @static
	 *
	 * @param {String} key The encryption key ; 32 bytes (256 bit) ; iV is auto-managed, 16 bytes (128 bit)
	 * @param {String} str The Smart.Twofish encrypted string
	 * @return {String} The Smart.Twofish decrypted string
	 */
	const tfDec = (key, str, isBinary=false) => {
		//--
		const _m$ = 'tfDec';
		//--
		str = pureStr(str); // do not trim
		if(str == '') {
			return '';
		} //end if
		//--
		if(!(regexSafeCryptoPackageStr.test(str))) {
			_p$.warn(_N$, _m$, 'Invalid TF Pak'); // warn
			return '';
		} //end if
		//--
		if(!sstw(str, TfSig+'!')) {
			_p$.warn(_N$, _m$, 'Invalid TF Data'); // warn
			return '';
		} //end if
		//--
		str = str.split('!', 2);
		str = rr13(pureStr(str[1], true));
		str = str.split(';', 2);
		const csum = pureStr(str[1], true);
		const pak  = pureStr(str[0], true);
		str = String(pak);
		//--
		str = b64sD(str, !0); // binary raw data
		if(str == '') {
			_p$.error(_N$, _m$, 'ERR:', 'B64 Decode Failed');
			return '';
		} //end if
		//--
		const kdiv = tfKdIv(key);
		if(!!kdiv.err) {
			_p$.error(_N$, _m$, 'ERR:', 'KD Failed:', kdiv.err);
			return '';
		} //end if
		//--
		str = _Twofish.decryptCBC(str, kdiv.key, kdiv.iv);
		if(str == '') {
			_p$.error(_N$, _m$, 'ERR:', 'Decrypt Failed');
			return '';
		} //end if
		//--
		return String(cryptUnpack(_m$, str, CkSep, csum, pak, isBinary));
		//--
	};
	_C$.tfDec = tfDec; // export

	//==

	const safePreDerive = (key) => {
		//--
		const sk = String(nByte + (key || ''));
		//--
		const hk1 = String(c32b(key) + nByte + hm5(key) + nByte + hs1(key) + nByte + hs256(key) + nByte + hs512(key));
		const hk2 = String(c32b(sk)  + nByte + hm5(sk)  + nByte + hs1(sk)  + nByte + hs256(sk)  + nByte + hs512(sk));
		//--
		const ck = String(hk1 + nByte + hk2);
		if((ck.length != 553) || (strm(ck) !== ck)) {
			_p$.error(_N$, 'ERR:', 'Invalid Composed Key Length');
			return '';
		} //end if
		//--
		const dk = b92hxc(hs256(ck)) + "'" + b92hxc(hm5(ck));
		const rk = strm(String(dk).substring(0, 448/8));
		//--
		return String(rk || '');
		//--
	};

	const bfKdIv = (k) => {
		//--
		const kdiv = {
			'err': '?',
			'key': '',
			'iv':  '',
		};
		//--
		const kSz = 448 / 8;
		const iSz =  64 / 8;
		//--
		k = pureStr(k, true);
		const len = k.length;
		if((k == '') || (len < DERIVE_MIN_KLEN) || (len > DERIVE_MAX_KLEN)) {
			kdiv.err = 'Invalid Key Length';
			return kdiv;
		} //end if else
		//--
		const b92k = safePreDerive(k);
		const b36v = String(String(c32b(k, !0)).padStart(8, '0')).substring(0, 64/8);
		//--
		if(b92k.length != kSz) {
			kdiv.err = 'Invalid Derived Key Length';
			return kdiv;
		} //end if
		if(b36v.length != iSz) {
			kdiv.err = 'Invalid Derived Iv Length';
			return kdiv;
		} //end if
		//--
		kdiv.err = ''; // reset
		kdiv.key = String(b92k || '');
		kdiv.iv  = String(b36v || '');
		return kdiv;
		//--
	};
	// no export

	//--

	/**
	 * Smart.Blowfish encrypts (CBC) a string using a smart derived encryption key and iv (SAFE based)
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method bfEnc
	 * @static
	 *
	 * @param 	{String} 	key 		The encryption key ; 56 bytes (448 bit) ; iV is auto-managed, 16 bytes (128 bit)
	 * @param 	{String} 	str 		The plain string
	 * @param 	{Boolean} 	isBinary 	Encoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @param 	{Boolean} 	randomize	Add randomization ; default is TRUE ; set to false to get a fixed encrypted data, without randomization
	 * @return 	{String} 	The Smart.Blowfish encrypted string
	 */
	const bfEnc = (key, str, isBinary=false, randomize=true) => {
		//--
		const _m$ = 'bfEnc';
		//--
		str = pureStr(str); // do not trim
		if(str == '') {
			return '';
		} //end if
		//--
		const oStr = str;
		str = b64E(str, isBinary); // encapsulate to B64 (supports unicode data but also binary data)
		//--
		if(randomize) { // randomize encryption which results always in a different encrypted string
			str = _Utils$.uuid('str') + '$' + str + '$' + c32b(str, !0) + '$' + _Utils$.uuid('num'); // because the prefix is random and CBC is sequential (first block will be always different) will results in a completely different string with every encryption ; also add a suffix because data may be reversed
		} //end if
		//--
		str = str + CkSep + b62hxc(h3a512(str)); // add checksum
		//--
		const kdiv = bfKdIv(key);
		if(!!kdiv.err) {
			_p$.error(_N$, _m$, 'ERR:', 'KD Failed:', kdiv.err);
			return '';
		} //end if
		//--
		str = _Blowfish.encryptCBC(str, kdiv.key, kdiv.iv);
		//--
	//	const pak = b64sE(str, !0); // b64s
		const pak = b64sE(str, !0, !1); // b64u
		const csum = b62hxc(h3a224(pak + nByte + oStr));
		//--
		return String(BfSig+'!'+rr13(pak+';'+csum));
		//--
	};
	_C$.bfEnc = bfEnc; // export

	/**
	 * Smart.Blowfish decrypts (CBC) a string using a smart derived encryption key and iv (SAFE based)
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method bfDec
	 * @static
	 *
	 * @param {String} key The encryption key ; 56 bytes (448 bit) ; iV is auto-managed, 16 bytes (128 bit)
	 * @param {String} str The Smart.Blowfish encrypted string
	 * @return {String} The Smart.Blowfish decrypted string
	 */
	const bfDec = (key, str, isBinary=false) => {
		//--
		const _m$ = 'bfDec';
		//--
		str = pureStr(str); // do not trim
		if(str == '') {
			return '';
		} //end if
		//--
		if(!(regexSafeCryptoPackageStr.test(str))) {
			_p$.warn(_N$, _m$, 'Invalid BF Pak'); // warn
			return '';
		} //end if
		//--
		if(!sstw(str, BfSig+'!')) {
			_p$.warn(_N$, _m$, 'Invalid BF Data'); // warn
			return '';
		} //end if
		//--
		str = str.split('!', 2);
		str = rr13(pureStr(str[1], true));
		str = str.split(';', 2);
		const csum = pureStr(str[1], true);
		const pak  = pureStr(str[0], true);
		str = String(pak);
		//--
		str = b64sD(str, !0); // binary raw data
		if(str == '') {
			_p$.error(_N$, _m$, 'ERR:', 'B64 Decode Failed');
			return '';
		} //end if
		//--
		const kdiv = bfKdIv(key);
		if(!!kdiv.err) {
			_p$.error(_N$, _m$, 'ERR:', 'KD Failed:', kdiv.err);
			return '';
		} //end if
		//--
		str = _Blowfish.decryptCBC(str, kdiv.key, kdiv.iv);
		if(str == '') {
			_p$.error(_N$, _m$, 'ERR:', 'Decrypt Failed');
			return '';
		} //end if
		//--
		return String(cryptUnpack(_m$, str, CkSep, csum, pak, isBinary));
		//--
	};
	_C$.bfDec = bfDec; // export

	//==

	// Smart.DhKx Dec EIDZ
	const dhkxEIdzDs = (eidz) => {
		//--
		eidz = pureStr(eidz, true);
		if(eidz == '') {
			return {
				'err': 'Invalid IDZ (1)'
			};
		} //end if
		//--
		if(!scnt(eidz, '!')) {
			return {
				'err': 'Invalid IDZ (2)'
			};
		} //end if
		//--
		const arr = eidz.split('!');
		if(arr.length !== 3) {
			return {
				'err': 'Invalid IDZ (3)'
			};
		} //end if
		const pfx = 'dH.';
		const ver = 'v3';
		let sig = '';
		let mod = '0';
		if(_Dhkx.useBigInt() === true) {
			sig = 'iHg.';
			mod = '1';
		} else {
			sig = 'i64.';
			mod = '2';
		} //end if else
		arr[0] = pureStr(arr[0], true);
		if(arr[0] !== pfx+sig+ver) {
			return {
				'err': 'Invalid IDZ (4.'+mod+')'
			};
		} //end if
		//--
		arr[1] = strm(b64sD(rr13(pureStr(arr[1], true)), !0));
		if(arr[1] == '') {
			return {
				'err': 'Invalid IDZ (5)'
			};
		} //end if
		//--
		arr[2] = strm(
			bfDec(
				kdf2('sha3-384', pfx+sig, hmc('sha224', pfx+sig, rr13(b2hx(pfx+sig, !0)), !1), 56, 14, true), // k
				strm(hx2b(bwzchx(rr13(pureStr(arr[2], true)), 62), !0)), // s
			)
		);
		if(arr[2] == '') {
			return {
				'err': 'Invalid IDZ (6)'
			};
		} //end if
		//--
		const asx = strm(bwzchx(arr[2], 85));
		if(asx == '') {
			return {
				'err': 'Invalid IDZ (7)'
			};
		} //end if
		arr[2] = strm(String(strm(hx2b(asx, !0))).substring(1));
		if(arr[2] == '') {
			return {
				'err': 'Invalid IDZ (8)'
			};
		} //end if
		//--
		arr[1] = strm(
			tfDec(
				bwzhxc(hmc('sha3-224', pfx+sig, '&='+asx+'#', !1), 92), // k
				String(arr[1]), // s
			)
		);
		if(arr[1] == '') {
			return {
				'err': 'Invalid IDZ (9)'
			};
		} //end if
		arr[1] = strm(String(hx2b(rr13(strm(bwzchx(arr[1], 92))), !0)).substring(1));
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
	// no export

	//--

	/*
	 * Smart.DhKx Dec
	 *
	 * @private special usage
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method dhkxDs
	 * @static
	 *
	 * @return {Array} Data
	 */
	const dhkxDs = (eidz) => {
		//--
		eidz = pureStr(eidz, true);
		//--
		let err = '';
		let cliShad = '';
		if(eidz == '') {
			err = 'Empty Idz';
		} else {
			const arr = dhkxEIdzDs(eidz);
			if(arr.err && (arr.err != '')) {
				err = String(arr.err);
			} else {
				cliShad = pureStr(_Dhkx.getCliShad(arr.csec, arr.spub), true);
				if(cliShad == '') {
					err = 'Empty Shad';
				} //end if
			} //end if
		} //end if
		//--
		return {
			type: 'DhkxShadData',
			mode: String(_Dhkx.getMode()),
			shad: String(cliShad),
			err:  String(err),
		};
		//--
	}; //END
	_C$.dhkxDs = dhkxDs; // export

	/*
	 * Smart.DhKx Enc
	 *
	 * @private special usage
	 *
	 * @memberof smartJ$CipherCrypto
	 * @method dhkxEs
	 * @static
	 *
	 * @return {Array} Data
	 */
	const dhkxEs = () => {
		//--
		const arr = {
			err: '?',
			shad: '',
			eidz: '',
		};
		//--
		const basegen = _Dhkx.getBaseGen();
		//--
		const srvData = _Dhkx.getSrvData(basegen);
		const cliData = _Dhkx.getCliData(basegen);
		//--
		const srvShad = _Dhkx.getSrvShad(String(srvData['sec']), String(cliData['pub']));
		const cliShad = _Dhkx.getCliShad(String(cliData['sec']), String(srvData['pub']));
		//--
		if(
			(strm(srvShad) == '')
			||
			(strm(cliShad) == '')
			||
			(srvShad !== cliShad)
		) {
			arr.err = 'Shad Mismatch';
			return arr; // shad failed !
		} //end if
		//--
		let shd = 'dH';
		if(_Dhkx.useBigInt() === true) {
			shd += '.iHg';
		} else {
			shd += '.i64';
		} //end if else
		//--
		const asx = b2hx('@'+srvData['pub'], !0);
		//--
		const etf = tfEnc(
			bwzhxc(hmc('sha3-224', shd+'.', '&='+asx+'#', !1), 92), // k
			bwzhxc(rr13(b2hx('$'+cliData['sec'], !0)), 92) // s
		);
		//--
		const ebf = bfEnc(
			kdf2('sha3-384', shd+'.', hmc('sha224', shd+'.', rr13(b2hx(shd+'.', !0)), !1), 56, 14, true), // k
			bwzhxc(asx, 85) // s
		);
		//--
		arr.err = ''; // clear
		arr.shad = String(srvShad || '');
		arr.eidz = String(shd + '.v3!' + rr13(b64sE(etf, !0, !1)) + '!' + rr13(bwzhxc(b2hx(ebf, !0), 62)));
		return arr;
		//--
	}; //END
	_C$.dhkxEs = dhkxEs; // export

	//==

}}; //END CLASS

smartJ$CipherCrypto.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$CipherCrypto = smartJ$CipherCrypto; // global export
} //end if


//=======================================
// #
//=======================================

//==================================================================
//==================================================================

// #END
