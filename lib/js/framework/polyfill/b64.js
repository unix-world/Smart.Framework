
// b64.js [Base64 encode/decode]
// (c) 2006-2024 unix-world.org - all rights reserved
// v.20241124

//=======================================
// CLASS :: Base64 enc/dec | PolyFill
//=======================================

// OLD BROWSERS AND QUICKJS are missing btoa() and atob() methods !!!!!!!
// Modern browsers have a more efficient (C level) implementation of these methods: btoa() / atob()
// However, if any of these methods are missing, this is a working polyfill ...

// Base64.Encode/Decode: https://github.com/hirak/phpjs, Copyright (c) Kevin van Zonneveld and Contributors (http://phpjs.org/authors)

/**
 * CLASS :: Smart Base64 (ES6, Strict Mode)
 *
 * @package Sf.Javascript:PolyFill
 *
 * @throws 			console.error
 *
 * @desc Base64 for JavaScript: Encode / Decode
 * @author unix-world.org
 * @license BSD
 * @file b64.js
 * @version 20231130
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
		return String(base64_core_dec(s, bin));
		//--
	}; //END
	_C$.decode = decode;

	//== PRIVATES UTF8

	/*
	const utf8_encode = function(str) { // ES6, with backward support up to ES5 ; this is an alternative for utf8Enc
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		let utftext = '';
		//--
	//	str = str.replace(/\r\n/g, '\n');
		str = str.replace(/\r\n/g, /\n/).replace(/\r/g, /\n/); // fix, replace both: \r\n and \r to \n
		for(let n = 0; n < str.length; n++) {
			let c = str.charCodeAt(n);
			if(c < 128) {
				utftext += String.fromCharCode(c);
			} else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			} else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			} //end if else
		} //end for
		//--
		return String(utftext); // fix to return empty string instead of null
		//--
	}; //END
	*/
	const utf8Enc = (str) => { // ES6
		//--
		const _m$ = 'utf8Enc';
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		let utftext = '';
		try {
			utftext = encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
				(match, p1) => { return String.fromCharCode('0x' + p1); }
			);
		} catch(err) {
			utftext = '';
			_p$.error(_N$, _m$, err);
		} //end try catch
		//--
		return String(utftext || ''); // fix to return empty string instead of null
		//--
	}; //END

	/*
	const utf8_decode = function(utftext) { // ES6, with backward support up to ES5 ; this is an alternative for utf8Dec
		//--
		utftext = String((utftext == undefined) ? '' : utftext);
		if(utftext == '') {
			return '';
		} //end if
		//--
		let str = '';
		//--
		let i = 0;
		let c, c1, c2, c3;
		c = c1 = c2 = c3 = 0;
		while(i < utftext.length) {
			c = utftext.charCodeAt(i);
			if(c < 128) {
				str += String.fromCharCode(c);
				i++;
			} else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				str += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				str += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			} //end if else
		} //end while
		//--
		return String(str); // fix to return empty string instead of null
		//--
	}; //END
	*/
	const utf8Dec = (utftext) => { // ES6
		//--
		const _m$ = 'utf8Dec';
		//--
		utftext = String((utftext == undefined) ? '' : utftext);
		if(utftext == '') {
			return '';
		} //end if
		//--
		let str = '';
		try {
			str = decodeURIComponent(utftext.split('').map((c) => {
				return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
			}).join(''));
		} catch(err) {
			str = '';
			_p$.error(_N$, _m$, err);
		} //end try catch
		//--
		return String(str || ''); // fix to return empty string instead of null
		//--
	}; //END

	//== PRIVATES BASE64

	// PRIVATE :: BASE64 :: key
	const b64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

	// PRIVATE :: BASE64 :: encode
	const base64_core_enc = function(input, is_binary) {
		//-- safety checks
		input = String(input || ''); // cast to string, don't trim ! need to preserve the value
		if(input == '') {
			return '';
		} //end if
		//-- make it unicode
		if(is_binary !== true) { // binary content must not be re-encoded to UTF-8
			input = utf8Enc(input);
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
		input = String(input || ''); // cast to string, don't trim ! need to preserve the value
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
			output = utf8Dec(output); // make it back unicode safe
		} //end if
		//--
		return String(output);
		//--
	}; //END

}}; //END CLASS

smartJ$Base64.secureClass(); // implements class security

const btoa = smartJ$Base64.encode;
const atob = smartJ$Base64.decode;

if(typeof(window) != 'undefined') {
//	window.smartJ$Base64 = smartJ$Base64; // global export ; skip ; export just the below methods, as a supply for btoa/atob required by js smart utils ...
	if(typeof(window.btoa) != 'undefined') {
		window.btoa = smartJ$Base64.encode; // global export
	} //end if
	if(typeof(window.atob) != 'undefined') {
		window.atob = smartJ$Base64.decode; // global export
	} //end if
} //end if

// #END
