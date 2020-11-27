
// [LIB - Smart.Framework / JS / Core Utils]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

// DEPENDS: -

//==================================================================
//==================================================================

//================== [NO:evcode]

/**
 * CLASS :: Core Utils
 *
 * @package Sf.Javascript:Core
 *
 * @desc This class provide the core functions for JavaScript API of Smart.Framework
 * @author unix-world.org
 * @license BSD
 * @file core_utils.js
 * @version 20201127
 * @class SmartJS_CoreUtils
 * @static
 *
 */
var SmartJS_CoreUtils = new function() { // START CLASS

	// :: static

	var _class = this; // self referencing


	/**
	 * Check if a number is valid: Finite and !NaN
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method isFiniteNumber
	 * @static
	 *
	 * @param 	{Number} 	num 	The number to be tested
	 * @return 	{Boolean} 			TRUE is number is Finite and !NaN ; FALSE otherwise
	 */
	this.isFiniteNumber = function(num) { // http://stackoverflow.com/questions/5690071/why-check-for-isnan-after-isfinite
		//--
		return Boolean(isFinite(num) && !isNaN(num));
		//--
	} //END FUNCTION


	/**
	 * Trim a string (at begining or end by any whitespace: space \ n \ r \ t)
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringTrim
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be trimmed
	 * @return 	{String} 			The trimmed string
	 */
	this.stringTrim = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		return String(str.replace(/^\s\s*/, '').replace(/\s\s*$/, '')); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Make uppercase the first character of a string
	 * @hint This implementation is compatible with PHP unicode mb_convert_case/MB_CASE_UPPER on the first letter
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringUcFirst
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be processed
	 * @return 	{String} 			The processed string
	 */
	this.stringUcFirst = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		var out = '';
		if(str.length == 1) {
			out = str.toUpperCase();
		} else if(str.length > 1) {
			out = str.charAt(0).toUpperCase() + str.substr(1);
		} //end if else
		//--
		return String(out);
		//--
	} //END FUNCTION


	/**
	 * Make uppercase the first character of each word in a string while making lowercase the others
	 * @hint This implementation is compatible with PHP unicode mb_convert_case/MB_CASE_TITLE
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringUcWords
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be processed
	 * @return 	{String} 			The processed string
	 */
	this.stringUcWords = function(str) {
		//--
		// discuss at: http://locutus.io/php/ucwords/
		// original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// improved by: Waldo Malqui Silva (http://waldo.malqui.info)
		// improved by: Robin
		// improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
		// bugfixed by: Cetvertacov Alexandr (https://github.com/cetver)
		// improved by: unix-world.org
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		var out = String(str).toLowerCase().replace(/^(.)|\s+(.)/g, function($1) {
			if($1) {
				return String($1).toUpperCase();
			} else {
				return '';
			} //end if else
		});
		//--
		return String(out);
		//--
	} //END FUNCTION


	/**
	 * Replace all occurences in a string - Case Sensitive
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringReplaceAll
	 * @static
	 *
	 * @param 	{String} 	token 		The string part to be replaced
	 * @param 	{String} 	newToken 	The string part replacement
	 * @param 	{String} 	str 		The string where to do the replacements
	 * @return 	{String} 				The processed string
	 */
	this.stringReplaceAll = function(token, newToken, str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		return String(str.split(token).join(newToken)); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Replace all occurences in a string - Case Insensitive
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringIReplaceAll
	 * @static
	 *
	 * @param 	{String} 	token 		The string part to be replaced
	 * @param 	{String} 	newToken 	The string part replacement
	 * @param 	{String} 	str 		The string where to do the replacements
	 * @return 	{String} 				The processed string
	 */
	this.stringIReplaceAll = function(token, newToken, str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		var i = -1;
		//--
		if((str != '') && (typeof token === 'string') && (typeof newToken === 'string')) {
			//--
			token = token.toLowerCase();
			//--
			while((i = str.toLowerCase().indexOf(token, i >= 0 ? i + newToken.length : 0)) !== -1) {
				str = String(str.substring(0, i) + newToken + str.substring(i + token.length));
			} //end while
			//--
		} //end if
		//--
		return String(str); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Regex Match All occurences.
	 * @hint This is compatible just with the PHP preg_match_all()
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringRegexMatchAll
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be searched
	 * @param 	{Regex} 	regexp 		A valid regular expression
	 * @return 	{Array} 				The array with matches
	 */
	this.stringRegexMatchAll = function(str, regexp) { // v.170922
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		var matches = [];
		//--
		var match = null;
		var loopidx = 0;
		var maxloops = 1000000; // set a safe value as in php.ini pcre.recursion_limit, but higher enough: 1 million
		while(match = regexp.exec(str)) {
			matches.push(match);
			loopidx++;
			if(loopidx >= maxloops) { // protect against infinite loop
				console.error('WARNING: SmartJS/Core@stringRegexMatchAll has entered in a possible infinite loop. Max recursion depth reached at: ' + maxloops);
				break;
			} //end if
		} //end while
		//--
		/* this is a non safe alternative of the above code but does not have a protection against infinite loops !!!
		//var arguments = {}; // DON'T use it as arguments is a reserved word in JS and the minifiers will break it below if found as defined as var here ... (prior this was a fix, but not necessary: required init to avoid using using arguments from a global context !)
		str.replace(regexp, function() {
			var arr = ([]).slice.call(arguments, 0);
			var extras = arr.splice(-2);
			arr.index = extras[0];
			arr.input = extras[1];
			matches.push(arr);
		}); */
		//--
		return matches; // Array
		//--
	} //END FUNCTION


	/**
	 * Find if a string contains another sub-string
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringContains
	 * @static
	 *
	 * @param 	{String} 	str 		The string to search in (haystack)
	 * @param 	{String} 	search 		The string to search for in haystack (needle)
	 * @param 	{Integer} 	pos 		The position to start
	 * @return 	{Boolean} 				TRUE if the str contains search at the pos
	 */
	this.stringContains = function(str, search, pos) { // v.181222
		//--
		// inspired from: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith#Polyfill
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		if((typeof search == 'undefined') || (search == undefined) || (search == null)) {
			search = '';
		} else {
			search = String(search); // force string
		} //end if else
		//--
		if(search === '') {
			return false;
		} //end if
		//--
		if((typeof pos == 'undefined') || (pos == undefined) || (pos == null)) {
			return str.includes(search);
		} //end if
		pos = parseInt(pos);
		if(pos < 0) {
			pos = 0;
		} //end if
		if(!_class.isFiniteNumber(pos)) {
			pos = 0;
		} //end if
		//--
		if(!str || !search) {
			return false;
		} //end if
		//--
		return str.substr(!pos || pos < 0 ? 0 : +pos, search.length) === search;
		//--
	} //END FUNCTION


	/**
	 * Split string by colon with trimming prefix/suffix
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringSplitbyColon
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be splitted by : (colon)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	this.stringSplitbyColon = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		str = _class.stringTrim(str);
		//--
		return str.split(/\s*\:\s*/); // Array
		//--
	} //END FUNCTION


	/**
	 * Split string by semicolon with trimming prefix/suffix
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringSplitbySemicolon
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be splitted by ; (semicolon)
	 * @return 	{Array} 				The array with string parts splitted
	 */
	this.stringSplitbySemicolon = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		str = _class.stringTrim(str);
		//--
		return str.split(/\s*;\s*/); // Array
		//--
	} //END FUNCTION


	/**
	 * Split string by comma with trimming prefix/suffix
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringSplitbyComma
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be splitted by , (comma)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	this.stringSplitbyComma = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		str = _class.stringTrim(str);
		//--
		return str.split(/\s*,\s*/); // Array
		//--
	} //END FUNCTION


	/**
	 * Split string by equal with trimming prefix/suffix
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stringSplitbyEqual
	 * @static
	 *
	 * @param 	{String} 	str 	The string to be splitted by = (equal)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	this.stringSplitbyEqual = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		str = _class.stringTrim(str);
		//--
		return str.split(/\s*\=\s*/); // Array
		//--
	} //END FUNCTION


	/**
	 * Get the first element from an Array
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method arrayGetFirst
	 * @static
	 *
	 * @param 	{Array} 	arr 		The array to be used
	 * @return 	{Mixed} 				The first element from the array
	 */
	this.arrayGetFirst = function(arr) {
		//--
		if(arr instanceof Array) {
			return arr.shift(); // Mixed
		} else {
			return '';
		} //end if
		//--
	} //END FUNCTION


	/**
	 * Get the last element from an Array
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method arrayGetLast
	 * @static
	 *
	 * @param 	{Array} 	arr 		The array to be used
	 * @return 	{Mixed} 				The last element from the array
	 */
	this.arrayGetLast = function(arr) {
		//--
		if(arr instanceof Array) {
			return arr.pop(); // Mixed
		} else {
			return '';
		} //end if
		//--
	} //END FUNCTION


	/**
	 * Format a number as INTEGER
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method format_number_int
	 * @static
	 *
	 * @param 	{Numeric} 	y_number 				A numeric value
	 * @param 	{Boolean} 	y_allow_negatives 		If TRUE will allow negative values else will return just positive (unsigned) values
	 * @return 	{Integer} 							An integer number
	 */
	this.format_number_int = function(y_number, y_allow_negatives) {
		//--
		if((typeof y_number == 'undefined') || (y_number == null) || (y_number == '') || (!_class.isFiniteNumber(y_number))) {
			y_number = 0;
		} //end if
		//--
		if(y_allow_negatives !== true) {
			y_allow_negatives = false;
		} //end if
		//--
		y_number = parseInt(String(y_number));
		if(!_class.isFiniteNumber(y_number)) {
			y_number = 0;
		} //end if
		//--
		if(y_allow_negatives !== true) { // force as positive
			if(y_number < 0) {
				y_number = parseInt(-1 * y_number);
			} //end if
			if(!_class.isFiniteNumber(y_number)) {
				y_number = 0;
			} //end if
			if(y_number < 0) {
				y_number = 0;
			} //end if
		} //end if
		//--
		return y_number; // Integer
		//--
	} //END FUNCTION


	/**
	 * Format a number as DECIMAL
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method format_number_dec
	 * @static
	 *
	 * @param 	{Numeric} 	y_number 					A numeric value
	 * @param 	{Integer} 	y_decimals 					The number of decimal to use (between 1 and 4)
	 * @param 	{Boolean} 	y_allow_negatives 			*Optional* If FALSE will disallow negative (will return just positive / unsigned values)
	 * @param 	{Boolean} 	y_discard_trailing_zeroes 	*Optional* If set to FALSE will keep trailing zeroes, otherwise will discard them
	 * @return 	{Integer} 								A decimal number
	 */
	this.format_number_dec = function(y_number, y_decimals, y_allow_negatives, y_discard_trailing_zeroes) {
		//--
		if((typeof y_number == 'undefined') || (y_number == null) || (y_number == '') || (!_class.isFiniteNumber(y_number))) {
			y_number = 0;
		} //end if
		//--
		if((typeof y_decimals == 'undefined') || (y_decimals == null) || (y_decimals == '')) {
			y_decimals = 2; // default;
		} //end if
		y_decimals = parseInt(y_decimals);
		if(!_class.isFiniteNumber(y_decimals)) {
			y_decimals = 2;
		} //end if
		if((y_decimals < 1) || (y_decimals > 4)) {
			y_decimals = 2;
		} //end if
		//--
		if(y_allow_negatives !== false) {
			y_allow_negatives = true; // default
		} //end if
		//--
		if(y_discard_trailing_zeroes !== true) {
			y_discard_trailing_zeroes = false; // default
		} //end if
		//--
		y_number = parseFloat(String(y_number)).toFixed(y_decimals);
		if(!_class.isFiniteNumber(y_number)) {
			y_number = parseFloat(0).toFixed(y_decimals);
		} //end if
		//--
		if(y_allow_negatives !== true) { // force as positive
			if(y_number < 0) {
				y_number = parseFloat(-1 * y_number).toFixed(y_decimals);
			} //end if
			if(!_class.isFiniteNumber(y_number)) {
				y_number = parseFloat(0).toFixed(y_decimals);
			} //end if
			if(y_number < 0) {
				y_number = parseFloat(0).toFixed(y_decimals);
			} //end if
		} //end if
		//-- remove trailing zeroes if not set to keep them
		if(y_discard_trailing_zeroes !== false) {
			y_number = parseFloat(y_number);
		} //end if
		//--
		return y_number; // Integer
		//--
	} //END FUNCTION


	/**
	 * Un-quotes a quoted string.
	 * It will remove double / quoted slashes.
	 * @hint This is compatible with PHP stripslashes() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method stripslashes
	 * @static
	 *
	 * @param {String} str The string to be processed
	 * @return {String} The processed string
	 */
	this.stripslashes = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//-- original written by: Kevin van Zonneveld (http://kevin.vanzonneveld.net) ; improved by: Ates Goral, marrtins, rezna ; fixed / bugfixed by: Mick@el, Onno Marsman, Brett Zamir, Rick Waldron, Brant Messenger
		return str.replace(/\\(.?)/g, function(s, n1) {
			switch(n1) {
				case '\\':
					return '\\';
				case '0':
					return '\u0000';
				case '':
					return '';
				default:
					return String(n1);
			} //end switch
		});
		//--
	} //END FUNCTION


	/**
	 * Quote string with slashes in a C style.
	 * @hint This is compatible with PHP addcslashes() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method addcslashes
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @param 	{String} 	charlist 	A list of characters to be escaped. If charlist contains characters \n, \r etc., they are converted in C-like style, while other non-alphanumeric characters with ASCII codes lower than 32 and higher than 126 converted to octal representation
	 * @return 	{String} 				Returns the escaped string
	 */
	this.addcslashes = function(str, charlist) {
		//--
		//  discuss at: http://phpjs.org/functions/addcslashes/
		// original by: Brett Zamir (http://brett-zamir.me)
		//        note: We show double backslashes in the return value example code below because a JavaScript string will not
		//        note: render them as backslashes otherwise
		//   example 1: addcslashes('foo[ ]', 'A..z'); // Escape all ASCII within capital A to lower z range, including square brackets
		//   returns 1: "\\f\\o\\o\\[ \\]"
		//   example 2: addcslashes("zoo['.']", 'z..A'); // Only escape z, period, and A here since not a lower-to-higher range
		//   returns 2: "\\zoo['\\.']"
		//   example 3: addcslashes("@a\u0000\u0010\u00A9", "\0..\37!@\177..\377"); // Escape as octals those specified and less than 32 (0x20) or greater than 126 (0x7E), but not otherwise
		//   returns 3: '\\@a\\000\\020\\302\\251'
		//   example 4: addcslashes("\u0020\u007E", "\40..\175"); // Those between 32 (0x20 or 040) and 126 (0x7E or 0176) decimal value will be backslashed if specified (not octalized)
		//   returns 4: '\\ ~'
		//   example 5: addcslashes("\r\u0007\n", '\0..\37'); // Recognize C escape sequences if specified
		//   returns 5: "\\r\\a\\n"
		//   example 6: addcslashes("\r\u0007\n", '\0'); // Do not recognize C escape sequences if not specified
		//   returns 6: "\r\u0007\n"
		// improved by: unix-world.org
		//--
		var target = '',
			chrs = [],
			i = 0,
			j = 0,
			c = '',
			next = '',
			rangeBegin = '',
			rangeEnd = '',
			chr = '',
			begin = 0,
			end = 0,
			octalLength = 0,
			postOctalPos = 0,
			cca = 0,
			escHexGrp = [],
			encoded = '',
			percentHex = /%([\dA-Fa-f]+)/g;
		//--
		var _pad = function(n, c) {
			if((n = n + '').length < c) {
				return new Array(++c - n.length).join('0') + n;
			} //end if
			return n;
		};
		//--
		for(i=0; i<charlist.length; i++) {
			c = charlist.charAt(i);
			next = charlist.charAt(i + 1);
			if(c === '\\' && next && (/\d/).test(next)) {
				rangeBegin = charlist.slice(i + 1).match(/^\d+/)[0]; // Octal
				octalLength = rangeBegin.length;
				postOctalPos = i + octalLength + 1;
				if(charlist.charAt(postOctalPos) + charlist.charAt(postOctalPos + 1) === '..') {
					// Octal begins range
					begin = rangeBegin.charCodeAt(0);
					if((/\\\d/).test(charlist.charAt(postOctalPos + 2) + charlist.charAt(postOctalPos + 3))) {
						// Range ends with octal
						rangeEnd = charlist.slice(postOctalPos + 3).match(/^\d+/)[0];
						// Skip range end backslash
						i += 1;
					} else if(charlist.charAt(postOctalPos + 2)) {
						// Range ends with character
						rangeEnd = charlist.charAt(postOctalPos + 2);
					} else {
						console.error('ERROR: SmartJS/Core@addcslashes: Range with no end point');
					} //end if else
					end = rangeEnd.charCodeAt(0);
					if(end > begin) {
						// Treat as a range
						for(j=begin; j<=end; j++) {
							chrs.push(String.fromCharCode(j));
						} //end for
					} else {
						// Supposed to treat period, begin and end as individual characters only, not a range
						chrs.push('.', rangeBegin, rangeEnd);
					} //end if else
					// Skip dots and range end (already skipped range end backslash if present)
					i += rangeEnd.length + 2;
				} else {
					// Octal is by itself
					chr = String.fromCharCode(parseInt(rangeBegin, 8));
					chrs.push(chr);
				} //end if else
				// Skip range begin
				i += octalLength;
			} else if(next + charlist.charAt(i + 2) === '..') {
				// Character begins range
				rangeBegin = c;
				begin = rangeBegin.charCodeAt(0);
				if((/\\\d/).test(charlist.charAt(i + 3) + charlist.charAt(i + 4))) {
					// Range ends with octal
					rangeEnd = charlist.slice(i + 4).match(/^\d+/)[0];
					// Skip range end backslash
					i += 1;
				} else if(charlist.charAt(i + 3)) {
					// Range ends with character
					rangeEnd = charlist.charAt(i + 3);
				} else {
					console.error('ERROR: SmartJS/Core@addcslashes: Range with no end point');
				} //end if else
				end = rangeEnd.charCodeAt(0);
				if(end > begin) {
					// Treat as a range
					for(j=begin; j<=end; j++) {
						chrs.push(String.fromCharCode(j));
					} //end for
				} else {
					// Supposed to treat period, begin and end as individual characters only, not a range
					chrs.push('.', rangeBegin, rangeEnd);
				} //end if else
				// Skip dots and range end (already skipped range end backslash if present)
				i += rangeEnd.length + 2;
			} else {
				// Character is by itself
				chrs.push(c);
			} //end if else
		} //end for
		//--
		for(i = 0; i < str.length; i++) {
			c = str.charAt(i);
			if(chrs.indexOf(c) !== -1) {
				target += '\\';
				cca = c.charCodeAt(0);
				if(cca < 32 || cca > 126) {
					// Needs special escaping
					switch (c) {
						case '\n':
							target += 'n';
							break;
						case '\t':
							target += 't';
							break;
						case '\u000D':
							target += 'r';
							break;
						case '\u0007':
							target += 'a';
							break;
						case '\v':
							target += 'v';
							break;
						case '\b':
							target += 'b';
							break;
						case '\f':
							target += 'f';
							break;
						default:
							//target += _pad(cca.toString(8), 3);break; // Sufficient for UTF-16
							encoded = encodeURIComponent(c);
							// 3-length-padded UTF-8 octets
							if((escHexGrp = percentHex.exec(encoded)) !== null) {
								target += _pad(parseInt(escHexGrp[1], 16).toString(8), 3); // already added a slash above
							} //end if
							while((escHexGrp = percentHex.exec(encoded)) !== null) {
								target += '\\' + _pad(parseInt(escHexGrp[1], 16).toString(8), 3);
							} //end while
							break;
					} //end switch
				} else {
					// Perform regular backslashed escaping
					target += c;
				} //end if else
			} else {
				// Just add the character unescaped
				target += c;
			} //end if else
		} //end for
		//--
		return target ? String(target) : '';
		//--
	} //END FUNCTION


	/**
	 * Convert special characters to HTML entities.
	 * @hint This is like the Smart::escape_css() from the PHP Smart.Framework.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method escape_css
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The safe escaped string to be injected in CSS code
	 */
	this.escape_css = function(str) { // v.181217
		//--
		return String(_class.addcslashes(str, "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~"));
		//--
	} //END FUNCTION


	/*
	 * Convert special characters to HTML entities with Options.
	 * Depending on the flag parameter, the following values will be converted to safe HTML entities:
	 * 		ENT_COMPAT: 	< > & "
	 * 		ENT_QUOTES: 	< > & " '
	 * 		ENT_NOQUOTES: 	< > &
	 * @hint This is like the htmlspecialchars() from PHP.
	 *
	 * @private internal development only
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method htmlspecialchars
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @param 	{Enum} 		flag 		*Optional* A bitmask of one or more of the following flags: ENT_COMPAT (default) ; ENT_QUOTES ; ENT_NOQUOTES
	 * @return 	{String} 				The safe escaped string to be injected in HTML code
	 */
	this.htmlspecialchars = function(str, flag) { // v.170308
		//-- format sting
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		if(str == '') {
			return '';
		} //end if
		//-- format flag
		if((typeof flag == 'undefined') || (flag == undefined) || (flag == null) || (flag == '')) {
			flag = 'ENT_COMPAT';
		} //end if
		//-- replace basics
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/</g, '&lt;');
		str = str.replace(/>/g, '&gt;');
		//-- replace quotes, depending on flag
		if(flag == 'ENT_QUOTES') { // ENT_QUOTES
			//-- replace all quotes: ENT_QUOTES
			str = str.replace(/"/g, '&quot;');
			str = str.replace(/'/g, '&#039;');
			//--
		} else if (flag != 'ENT_NOQUOTES') { // ENT_COMPAT
			//-- default, replace just double quotes
			str = str.replace(/"/g, '&quot;');
			//--
		} //end if else
		//--
		return String(str); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Convert special characters to HTML entities.
	 * These values will be converted to safe HTML entities: < > & "
	 * @hint This is like the Smart::escape_html() from the PHP Smart.Framework.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method escape_html
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The safe escaped string to be injected in HTML code
	 */
	this.escape_html = function(str) { // v.170308
		//--
		return String(_class.htmlspecialchars(str, 'ENT_COMPAT'));
		//--
	} //END FUNCTION


	/**
	 * Convert special characters to escaped entities for safe use with Javascript Strings.
	 * @hint This is like the Smart::escape_js() from the PHP Smart.Framework.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method escape_js
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The escaped string using the json encode standard to be injected between single quotes '' or double quotes ""
	 */
	this.escape_js = function(str) { // v.170831
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//-- sub-function to escape a string as unicode
		var escape_unicode = function(str) {
			str = String(str);
			return String('\\u' + ('0000' + str.charCodeAt(0).toString(16)).slice(-4).toLowerCase());
		} //END FUNCTION
		//-- table of character substitutions: get from json2.js but excludding the " which is done later to preserve compatibility with PHP
		var meta = {
			'\b': '\\b',
			'\t': '\\t',
			'\n': '\\n',
			'\f': '\\f',
			'\r': '\\r',
			'\\': '\\\\'
		};
		//-- replace meta
		var encoded = str.replace(/[\x00-\x1f\x7f-\x9f\\]/g, function(a){ var c = meta[a]; return typeof c === 'string' ? c: escape_unicode(a); });
		//-- replace unicode characters
		encoded = encoded.replace(/[\u007F-\uFFFF]/g, function(c){ return escape_unicode(c); });
		//-- replace special characters (use uppercase unicode escapes as in PHP ; example: u003C / u003E )
		encoded = encoded.replace(/[\u0026]/g, '\\u0026');	// & 	JSON_HEX_AMP
		encoded = encoded.replace(/[\u0022]/g, '\\u0022');	// " 	JSON_HEX_QUOT
		encoded = encoded.replace(/[\u0027]/g, '\\u0027');	// ' 	JSON_HEX_APOS
		encoded = encoded.replace(/[\u003C]/g, '\\u003C'); 	// < 	JSON_HEX_TAG
		encoded = encoded.replace(/[\u003E]/g, '\\u003E'); 	// > 	JSON_HEX_TAG
		encoded = encoded.replace(/[\/]/g,     '\\/');	    // / 	JSON_UNESCAPED_SLASHES
		//-- return string
		return String(encoded); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Safe escape URL Variable (using RFC3986 standards to be full Unicode compliant).
	 * @hint This is a shortcut to the encodeURIComponent() to provide a standard into Smart.Framework/JS.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method escape_url
	 * @static
	 *
	 * @param 	{String} 	str 		The URL variable value to be escaped
	 * @return 	{String} 				The escaped URL variable
	 */
	this.escape_url = function(str) {
		//-- format sting
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		str = String(encodeURIComponent(str));
		//-- fixes to make it more compliant with it RFC 3986
		str = str.replace('!', '%21');
		str = str.replace("'", '%27');
		str = str.replace('(', '%28');
		str = str.replace(')', '%29');
		str = str.replace('*', '%2A');
		//--
		return String(str); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Replace new lines \ r \ n ; \ n with the <br> html tag.
	 * @hint This is compatible with the PHP nl2br() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method nl2br
	 * @static
	 *
	 * @param {String} str The string to be processed
	 * @return {String} The processed string with <br> html tags if new lines were detected
	 */
	this.nl2br = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		return String(str.replace(/\r\n/g, /\n/).replace(/\r/g, /\n/).replace(/\n/g, '<br>')); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Encodes an ISO-8859-1 string to UTF-8
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method utf8_encode
	 * @static
	 *
	 * @param 	{String} 	string 			The string to be processed
	 * @return 	{String} 					The processed string
	 */
	this.utf8_encode = function(string) {
		//--
		if((typeof string == 'undefined') || (string == undefined) || (string == null)) {
			string = '';
		} else {
			string = String(string); // force string
		} //end if else
		//--
		var utftext = '';
		//--
		string = string.replace(/\r\n/g,"\n");
		for(var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
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
	} //END FUNCTION


	/**
	 * Decodes an UTF-8 string to ISO-8859-1
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method utf8_decode
	 * @static
	 *
	 * @param 	{String} 	string 			The string to be processed
	 * @return 	{String} 					The processed string
	 */
	this.utf8_decode = function(utftext) {
		//--
		if((typeof utftext == 'undefined') || (utftext == undefined) || (utftext == null)) {
			utftext = '';
		} else {
			utftext = String(utftext); // force string
		} //end if else
		//--
		var string = '';
		//--
		var i = 0;
		var c, c1, c2, c3;
		c = c1 = c2 = c3 = 0;
		while ( i < utftext.length ) {
			c = utftext.charCodeAt(i);
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			} else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			} //end if else
		} //end while
		//--
		return String(string); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * De-Accent a latin-based Unicode string
	 * Will convert all accented characters in UTF-8 / ISO-8859-* with their unnaccented versions into ISO-8859-1
	 * @hint This is like PHP SmartUnicode deaccent str
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method deaccent_str
	 * @static
	 *
	 * @param 	{String} 	strIn 		The string to be de-accented
	 * @return 	{String} 				The de-accented string
	 */
	this.deaccent_str = function(strIn) {
		//--
		if((typeof strIn == 'undefined') || (strIn == undefined) || (strIn == null)) {
			strIn = '';
		} else {
			strIn = String(strIn); // force string
		} //end if else
		//--
		if(!strIn) {
			return '';
		} //end if
		//--
		var strIn = strIn.split('');
		var len = strIn.length;
		var strOut = new Array();
		//- -- deaccent strings c.176x2 (v.170305), pgsql
		var data_accented 	= 'áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝšȘŞŠŚŜŠțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ';
		var data_deaccented 	= 'aaaaaaaaAAAAAAAAccccCCCCdDeeeeeeeeeEEEEEEEEEggGGhhHHiiiiiiiiiiIIIIIIIIIIjjJJkKllllLLLLnnnnNNNNoooooooooOOOOOOOOOOrrrRRRssssssSSSSSStttTTTuuuuuuuuuuUUUUUUUUUUwWyyyyyYYYYYzzzZZZ';
		//--
		for(var y=0; y<len; y++) {
			if(data_accented.indexOf(strIn[y]) != -1) {
				strOut[y] = data_deaccented.substr(data_accented.indexOf(strIn[y]), 1);
			} else {
				strOut[y] = strIn[y];
			} //end if else
		} //end for
		strOut = strOut.join('');
		//--
		return String(strOut);
		//--
	} //END FUNCTION


	/**
	 * Convert binary data into hexadecimal representation.
	 * @hint This is compatible with PHP bin2hex() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method bin2hex
	 * @static
	 *
	 * @param 	{String} 	s 			The string to be processed
	 * @return 	{String} 				The processed string
	 */
	this.bin2hex = function(s) {
		//--
		s = String(_class.utf8_encode(s)); // force string and make it unicode safe
		//--
		var hex = '';
		var i, l, n;
		for(i = 0, l = s.length; i < l; i++) {
			n = s.charCodeAt(i).toString(16);
			hex += n.length < 2 ? '0' + n : n;
		} //end for
		//--
		return String(hex); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Decodes a hexadecimally encoded binary string.
	 * @hint This is compatible with PHP hex2bin() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method hex2bin
	 * @static
	 *
	 * @param 	{String} 	hex 		The string to be processed
	 * @return 	{String} 				The processed string
	 */
	this.hex2bin = function(hex) {
		//--
		hex = String(_class.stringTrim(hex)); // force string and trim to avoid surprises ...
		//--
		var bytes = [], str;
		//--
		for(var i=0; i< hex.length-1; i+=2) {
			bytes.push(parseInt(hex.substr(i, 2), 16));
		} //end for
		//--
		return String(_class.utf8_decode(String.fromCharCode.apply(String, bytes))); // fix to return empty string instead of null
		//--
	} //END FUNCTION


	/**
	 * Quote regular expression characters in a string.
	 * @hint This is compatible with PHP preg_quote() function.
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method preg_quote
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be processed
	 * @return 	{String} 				The processed string
	 */
	this.preg_quote = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//--
		// http://kevin.vanzonneveld.net
		// + original by: booeyOH
		// + improved by: Ates Goral (http://magnetiq.com)
		// + improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// + bugfixed by: Onno Marsman
		// *   example 1: preg_quote("$40");
		// *   returns 1: '\$40'
		// *   example 2: preg_quote("*RRRING* Hello?");
		// *   returns 2: '\*RRRING\* Hello\?'
		// *   example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
		// *   returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
		// improved by: unix-world.org
		//--
		return String(str.replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, '\\$1'));
		//--
	} //END FUNCTION


	var tseed = (new Date()).valueOf(); // time based uuid seed, must be global in this class as it is re-seeded each time UUID is generated

	/**
	 * Generate a base36 10-chars length UUID
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method uuid
	 * @static
	 *
	 * @return 	{String} 			A unique UUID, time based, base36 ; Ex: 1A2B3C4D5E
	 */
	this.uuid = function() {
		//--
		uuid = String((tseed++).toString(36)).toUpperCase().substr(-10); // if longer than 10 chars, take the last 10 chars only
		//--
		if(uuid.length < 10) {
			for(var i=0; i<uuid.length; i++) { // left pad with zeroes
				if(uuid.length < 10) {
					uuid = String('0' + uuid);
				} else {
					break;
				} //end if else
			} //end for
		} //end if
		//--
		return String(uuid);
		//--
	} //END FUNCTION


	/*
	 * Add an Element to a List
	 *
	 * @private internal development only
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method addToList
	 * @static
	 *
	 * @param 	{String} 	newVal 		The new val to add to the List
	 * @param 	{String} 	textList 	The string List to add newVal at
	 * @param 	{String} 	splitBy 	The string separator, any of: , ;
	 * @return 	{String} 				The processed string as List separed by separator
	 */
	this.addToList = function(newVal, textList, splitBy) {
		//--
		newVal = String(newVal);
		//--
		var terms = [];
		switch(splitBy) {
			case ',':
				terms = SmartJS_CoreUtils.stringSplitbyComma(textList);
				break;
			case ';':
				terms = SmartJS_CoreUtils.stringSplitbySemicolon(textList);
				break;
			default:
				console.error('ERROR: SmartJS/Core@addToList: Invalid splitBy separator. Must be any of [,;]');
				return '';
		} //end switch
		//--
		terms.pop(); // remove the current input
		var found = 0;
		if(terms.length > 0) {
			for(var i=0; i<terms.length; i++) {
				if(terms[i] == newVal) {
					found = 1;
					break;
				} //end if
			} //end for
		} //end if
		if(found == 0) {
			terms.push(newVal); // add the selected item
		} //end if
		terms.push(''); // add placeholder to get the comma-and-space at the end
		//--
		return String(terms.join(splitBy + ' '));
		//--
	} //END FUNCTION


	/**
	 * Sort a stack (array / object / property) using String Sort algorithm
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method textSort
	 * @static
	 *
	 * @param 	{Mixed} 	property 		The stack to be sorted
	 * @return 	{Mixed} 					The sorted stack
	 */
	this.textSort = function(property) {
		//--
		return function(a, b) {
			//--
			if(a[property] == null) {
					a[property] = '';
			} //end if
			if(b[property] == null) {
					b[property] = '';
			} //end if
			//--
			try {
				var comparer = a[property].localeCompare(b[property]); // a better compare
				if(comparer < 0) {
						comparer = -1;
				} //end if
				if(comparer > 0) {
						comparer = 1;
				} //end if
			} catch(e) {
				comparer = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
			} //end try catch
			//--
			return comparer; // Mixed
			//--
		} //end function
		//--
	} //END FUNCTION


	/**
	 * Sort a stack (array / object / property) using Numeric Sort algorithm
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method numericSort
	 * @static
	 *
	 * @param 	{Mixed} 	property 		The stack to be sorted
	 * @return 	{Mixed} 					The sorted stack
	 */
	this.numericSort = function(property) {
		//--
		return function(a, b) {
			//--
			if(a[property].toString() == '') {
					a[property] = 0;
			} //end if
			if(b[property].toString() == '') {
					b[property] = 0;
			} //end if
			//--
			a[property] = parseFloat(a[property]); // parse as number
			b[property] = parseFloat(b[property]); // parse as number
			//--
			if(a[property] > b[property]) {
				return 1;
			} //end if
			//--
			if(a[property] < b[property]) {
				return -1;
			} //end if
			//--
			return 0;
			//--
		} //end function
		//--
	} //END FUNCTION


	/*
	 * Revert from Prepare a value or a template by escaping syntax
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method revertNosyntaxContentMarkersTpl
	 * @static
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	this.revertNosyntaxContentMarkersTpl = function(tpl) {
		//--
		if(typeof tpl !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//--
		tpl = _class.stringReplaceAll('［###', '[###', tpl);
		tpl = _class.stringReplaceAll('###］', '###]', tpl);
		tpl = _class.stringReplaceAll('［%%%', '[%%%', tpl);
		tpl = _class.stringReplaceAll('%%%］', '%%%]', tpl);
		tpl = _class.stringReplaceAll('［@@@', '[@@@', tpl);
		tpl = _class.stringReplaceAll('@@@］', '@@@]', tpl);
		//--
		return String(tpl);
		//--
	} //END FUNCTION


	/*
	 * Prepare a value or a template by escaping syntax
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method prepareNosyntaxContentMarkersTpl
	 * @static
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	this.prepareNosyntaxContentMarkersTpl = function(tpl) {
		//--
		if(typeof tpl !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//--
		tpl = _class.stringReplaceAll('[###', '［###', tpl);
		tpl = _class.stringReplaceAll('###]', '###］', tpl);
		tpl = _class.stringReplaceAll('[%%%', '［%%%', tpl);
		tpl = _class.stringReplaceAll('%%%]', '%%%］', tpl);
		tpl = _class.stringReplaceAll('[@@@', '［@@@', tpl);
		tpl = _class.stringReplaceAll('@@@]', '@@@］', tpl);
		//--
		return String(tpl);
		//--
	} //END FUNCTION


	/*
	 * Prepare a HTML template for display in no-conflict mode: no syntax / markers will be parsed
	 * To keep the markers and syntax as-is but avoiding conflicting with real markers / syntax it will encode as HTML Entities the following syntax patterns: [ ] # % @
	 * @hint !!! This is intended for very special usage ... !!!
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method prepareNosyntaxHtmlMarkersTpl
	 * @static
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	this.prepareNosyntaxHtmlMarkersTpl = function(tpl) {
		//--
		if(typeof tpl !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//--
		tpl = _class.stringReplaceAll('[###', '&lbrack;###', tpl);
		tpl = _class.stringReplaceAll('###]', '###&rbrack;', tpl);
		tpl = _class.stringReplaceAll('[%%%', '&lbrack;%%%', tpl);
		tpl = _class.stringReplaceAll('%%%]', '%%%&rbrack;', tpl);
		tpl = _class.stringReplaceAll('[@@@', '&lbrack;@@@', tpl);
		tpl = _class.stringReplaceAll('@@@]', '@@@&rbrack;', tpl);
		tpl = _class.stringReplaceAll('［###', '&lbrack;###', tpl);
		tpl = _class.stringReplaceAll('###］', '###&rbrack;', tpl);
		tpl = _class.stringReplaceAll('［%%%', '&lbrack;%%%', tpl);
		tpl = _class.stringReplaceAll('%%%］', '%%%&rbrack;', tpl);
		tpl = _class.stringReplaceAll('［@@@', '&lbrack;@@@', tpl);
		tpl = _class.stringReplaceAll('@@@］', '@@@&rbrack;', tpl);
		//--
		return String(tpl);
		//--
	} //END FUNCTION


	/**
	 * Render Simple Marker-TPL Template + Comments + Specials (only markers replacements with escaping or processing syntax and support for comments and special replacements: SPACE, TAB, R, N ; no support for IF / LOOP / INCLUDE syntax since the js regex is too simplistic and this can be implemented using real js code)
	 * This is compatible just with the REPLACE MARKERS of Smart.Framework PHP server-side Marker-TPL Templating for substitutions on client-side, except the extended syntax as IF/LOOP/INCLUDE.
	 * @hint To be used together with the server-side Marker-TPL templating, to avoid the server-side markers to be replaced as `［###MARKER###］` will need the template to be escape_url+escape_js and 3rd param: isEncoded = TRUE
	 *
	 * @example
	 * // sample client side MarkersTPL rendering
	 * var tpl = '<div onclick="var question=\'[###QUESTION|js|html###]\'; alert(question);">[###TITLE|html###]</div>'; // the TPL
	 * var html = SmartJS_CoreUtils.renderMarkersTpl( // render TPL
	 * 		tpl,
	 * 		{
	 * 			'TITLE': 'A Title',
	 * 			'QUESTION': 'A Question'
	 * 		}
	 * jQuery('body').append(html); // display TPL
	 *
	 * @memberof SmartJS_CoreUtils
	 * @method renderMarkersTpl
	 * @static
	 *
	 * @param 	{String} 	template 		The string template with markers
	 * @param 	{ArrayObj} 	arrobj 			The Object-Array with marker replacements as { 'MAR.KER_1' => 'Value 1', 'MARKER-2' => 'Value 2', ... }
	 * @param 	{Boolean} 	isEncoded 		If TRUE will do a decoding over template string (apply if TPL is sent as encoded from server-side or directly in html page)
	 * @param 	{Boolean} 	revertSyntax 	If TRUE will do a revertNosyntaxContentMarkersTpl() over template string (apply if TPLs is sent with syntax escaped)
	 * @return 	{String} 					The processed string
	 */
	this.renderMarkersTpl = function(template, arrobj, isEncoded, revertSyntax) { // syntax: r.20200717
		//--
		var debug = false;
		//--
		if((typeof template === 'string') && (typeof arrobj === 'object')) {
			//--
			if(isEncoded === true) {
				template = String(decodeURIComponent(template));
			} //end if
			if(revertSyntax === true) {
				template = String(_class.revertNosyntaxContentMarkersTpl(template));
			} //end if
			//--
			template = _class.stringTrim(template);
			//-- remove comments: javascript regex miss the regex flags: s = single line: Dot matches newline characters ; U = Ungreedy: The match becomes lazy by default ; Now a ? following a quantifier makes it greedy
			// because missing the single line dot match and ungreedy is almost impossible to solve this with a regex in an optimum way, tus we use this trick :-)
			// because missing the /s flag, the extra \S have to be added to the \s to match new lines and the (.*) have become ([\s\S^]*)
			// because missing the /U flag, missing ungreedy, we need to split/join to solve this
			if((template.indexOf('[%%%COMMENT%%%]') >= 0) && (template.indexOf('[%%%/COMMENT%%%]') >= 0)) { // indexOf() :: if not found returns -1
				var arr_comments = [];
				arr_comments = template.split('[%%%COMMENT%%%]');
				for(var i=0; i<arr_comments.length; i++) {
					if(arr_comments[i].indexOf('[%%%/COMMENT%%%]') >= 0) { // indexOf() :: if not found returns -1
						arr_comments[i] = '[%%%COMMENT%%%]' + arr_comments[i];
						arr_comments[i] = arr_comments[i].replace(/[\s\S]?\[%%%COMMENT%%%\]([\s\S^]*)\[%%%\/COMMENT%%%\][\s\S]?/g, '');
					} //end if
				} //end for
				template = _class.stringTrim(arr_comments.join(''));
				arr_comments = null;
			} //end if
			//-- replace markers
			if(template != '') {
				//--
				var regexp = /\[###([A-Z0-9_\-\.]+)((\|[a-z0-9]+)*)###\]/g; // {{{SYNC-REGEX-MARKER-TEMPLATES}}}
				//--
				var markers = _class.stringRegexMatchAll(template, regexp);
			//	if(debug) {
			//		console.log(markers);
			//	} //end if
				//--
				if(markers.length) {
					//--
					for(var i=0; i<markers.length; i++) {
						//--
						var marker = markers[i]; // expects array
					//	if(debug) {
					//		console.log(JSON.stringify(marker, null, 2));
					//	} //end if
						//--
						if(marker.length) {
							//--
							var tmp_marker_val 		= '';									// just initialize
							var tmp_marker_id 		= marker[0] ? String(marker[0]) : ''; 	// [###THE-MARKER|escapings...###]
							var tmp_marker_key 		= marker[1] ? String(marker[1]) : ''; 	// THE-MARKER
							var tmp_marker_esc 		= marker[2] ? String(marker[2]) : ''; 	// |escaping1(|escaping2...|escaping99)
							var tmp_marker_arr_esc  = []; 									// just initialize
							//--
							if((tmp_marker_id != null) && (tmp_marker_id != '') && (tmp_marker_key != null) && (tmp_marker_key != '') && (template.indexOf(tmp_marker_id) >= 0)) { // indexOf() :: if not found returns -1 # check if exists because it does replaceAll on a cycle so another cycle can run without scope !
								//--
							//	if(debug) {
							//		console.log('Marker Found: ' + tmp_marker_id + ' :: ' + tmp_marker_key);
							//	} //end if
								//--
								if(tmp_marker_key in arrobj) {
									//-- prepare val from input array
									tmp_marker_val = arrobj[tmp_marker_key] ? arrobj[tmp_marker_key] : '';
									tmp_marker_val = String(tmp_marker_val);
									//-- protect against cascade recursion of syntax by escaping all the syntax in value
								//	if(debug) { // this notice is too complex to fix in all situations, thus make it show just on DBG !
								//		if(tmp_marker_val.indexOf('[###') >= 0) { // indexOf() :: if not found returns -1
								//			console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Markers detected in Replacement Key: ' + tmp_marker_key + ' in Template for Value: ^' + '\n' + tmp_marker_val + '\n' + template + '$ ###}');
								//		} //end if
								//		if(tmp_marker_val.indexOf('[%%%') >= 0) { // indexOf() :: if not found returns -1
								//			console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Marker Syntax detected in Replacement Key: ' + tmp_marker_key + ' in Template for Value: ^' + '\n' + tmp_marker_val + '\n' + template + '$ ###}');
								//		} //end if
								//		if(tmp_marker_val.indexOf('[@@@') >= 0) { // indexOf() :: if not found returns -1
								//			console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Marker Sub-Templates detected in Replacement Key: ' + tmp_marker_key + ' in Template for Value: ^' + '\n' + tmp_marker_val + '\n' + template + '$ ###}');
								//		} //end if
								//	} //end if
									tmp_marker_val = String(_class.prepareNosyntaxContentMarkersTpl(tmp_marker_val));
									//--
									if(tmp_marker_esc) { // if non-empty before removing leading | ; else no escapings
										//--
										if(_class.stringContains(tmp_marker_esc, '|', 0)) { // if contains leading |
											tmp_marker_esc = tmp_marker_esc.substr(1); // remove leading |
										} //end if
										//--
										if(tmp_marker_esc) { // if non-empty after removing leading | ; else no escapings
											//--
											tmp_marker_arr_esc = tmp_marker_esc.split(/\|/); // Array, split by |
										//	if(debug) {
										//		console.log(JSON.stringify(tmp_marker_arr_esc, null, 2));
										//	} //end if
											//--
											if(tmp_marker_arr_esc.length) {
												//--
												for(var j=0; j<tmp_marker_arr_esc.length; j++) {
													//--
													var escaping = String('|' + String(tmp_marker_arr_esc[j]));
												//	if(debug) {
												//		console.log(tmp_marker_id + ' / ' + escaping);
												//	} //end if
													//--
													if(escaping == '|bool') { // Boolean
														if(tmp_marker_val) {
															tmp_marker_val = 'true';
														} else {
															tmp_marker_val = 'false';
														} //end if else
													//	if(debug) {
													//		console.log('Marker Format Bool: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|int') { // Integer
														tmp_marker_val = parseInt(tmp_marker_val);
														if(!_class.isFiniteNumber(tmp_marker_val)) {
															tmp_marker_val = 0;
														} //end if
														tmp_marker_val = String(tmp_marker_val);
													//	if(debug) {
													//		console.log('Marker Format Int: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping.substring(0, 4) == '|dec') { // Dec (1..4)
														var xnum = parseInt(escaping.substring(4));
														if(!_class.isFiniteNumber(xnum)) {
															xnum = 1;
														} //end if
														if(xnum < 1) {
															xnum = 1;
														} //end if
														if(xnum > 4) {
															xnum = 4;
														} //end if
														tmp_marker_val = _class.format_number_dec(tmp_marker_val, xnum, true, false); // allow negatives, do not discard trailing zeroes
														if(!_class.isFiniteNumber(tmp_marker_val)) {
															tmp_marker_val = 0;
														} //end if
														tmp_marker_val = String(tmp_marker_val);
													//	if(debug) {
													//		console.log('Marker Format Dec[' + xnum + ']: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
														xnum = null;
													} else if(escaping == '|num') { // Number (Float / Decimal / Integer)
														tmp_marker_val = parseFloat(tmp_marker_val);
														if(!_class.isFiniteNumber(tmp_marker_val)) {
															tmp_marker_val = 0;
														} //end if
														tmp_marker_val = String(tmp_marker_val);
													//	if(debug) {
													//		console.log('Marker Format Number: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|idtxt') { // id_txt: Id-Txt
														tmp_marker_val = String(_class.stringReplaceAll('_', '-', tmp_marker_val));
														tmp_marker_val = String(_class.stringUcWords(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format IdTxt: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|slug') { // Slug: a-zA-Z0-9_- / - / -- : -
														tmp_marker_val = String(_class.deaccent_str(_class.stringTrim(tmp_marker_val)));
														tmp_marker_val = tmp_marker_val.replace(/[^a-zA-Z0-9_\-]/g, '-');
														tmp_marker_val = tmp_marker_val.replace(/[\-]+/g, '-'); // suppress multiple -
														tmp_marker_val = tmp_marker_val.replace(/^[\-]+/, '').replace(/[\-]+$/, '');
														tmp_marker_val = String(_class.stringTrim(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format SLUG: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|htmid') { // HTML-ID: a-zA-Z0-9_-
														tmp_marker_val = tmp_marker_val.replace(/[^a-zA-Z0-9_\-]/g, '');
														tmp_marker_val = String(_class.stringTrim(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format HTML-ID: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|jsvar') { // JS-Variable: a-zA-Z0-9_
														tmp_marker_val = tmp_marker_val.replace(/[^a-zA-Z0-9_]/g, '');
														tmp_marker_val = String(_class.stringTrim(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format JS-VAR: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if((escaping.substring(0, 7) == '|substr') || (escaping.substring(0, 7) == '|subtxt')) { // Sub(String|Text) (0,num)
														var xnum = parseInt(escaping.substring(7));
														if(!_class.isFiniteNumber(xnum)) {
															xnum = 1;
														} //end if
														if(xnum < 1) {
															xnum = 1;
														} //end if
														if(xnum > 65535) {
															xnum = 65535;
														} //end if
														if(xnum >= 1 && xnum <= 65535) {
															var xlen = tmp_marker_val.length;
															if(escaping.substring(0, 7) == '|subtxt') {
																if(xnum < 5) {
																	xnum = 5;
																} //end if
																xnum = xnum - 3;
																if(xlen > xnum) {
																	tmp_marker_val = tmp_marker_val.substring(0, xnum);
																	tmp_marker_val = tmp_marker_val.replace(/\s+?(\S+)?$/, ''); // {{{SYNC-REGEX-TEXT-CUTOFF}}}
																	tmp_marker_val = String(tmp_marker_val) + '...';
																} //end if
															//	if(debug) {
															//		console.log('Marker Sub-Text(' + xnum + '): ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
															//	} //end if
															} else { // '|substr'
																if(xlen > xnum) {
																	tmp_marker_val = tmp_marker_val.substring(0, xnum);
																} //end if
															//	if(debug) {
															//		console.log('Marker Sub-String(' + xnum + '): ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
															//	} //end if
															} //end if
															xlen = null;
														} //end if
														xnum = null;
														tmp_marker_val = String(tmp_marker_val);
													} else if(escaping == '|lower') { // apply lowercase
														tmp_marker_val = String(tmp_marker_val).toLowerCase();
													//	if(debug) {
													//		console.log('Marker Format Apply LowerCase: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|upper') { // apply uppercase
														tmp_marker_val = String(tmp_marker_val).toUpperCase();
													//	if(debug) {
													//		console.log('Marker Format Apply UpperCase: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|ucfirst') { // apply uppercase first character
														tmp_marker_val = String(_class.stringUcFirst(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format Apply UcFirst: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|ucwords') { // apply uppercase on each word
														tmp_marker_val = String(_class.stringUcWords(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format Apply UcWords: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|trim') { // apply trim
														tmp_marker_val = String(_class.stringTrim(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Format Apply Trim: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|url') { // escape URL
														tmp_marker_val = String(_class.escape_url(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker URL-Escape: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|json') { // format as Json Data ; expects pure JSON !!!
														var jsonObj = null;
														try {
															jsonObj = JSON.parse(tmp_marker_val); // it MUST be JSON !
														} catch(err){
															jsonObj = null;
														} //end try catch
														tmp_marker_val = JSON.stringify(jsonObj, null, null); // ensure no pretty print or json replacer is used
														tmp_marker_val = String(tmp_marker_val); // force string
														// Fixes: the JSON stringify does not make the JSON to be HTML-Safe, thus we need several minimal replacements: https://www.drupal.org/node/479368 + escape /
														tmp_marker_val = tmp_marker_val.replace(/[\u0026]/g, '\\u0026');	// & 	JSON_HEX_AMP
														tmp_marker_val = tmp_marker_val.replace(/[\u003C]/g, '\\u003C'); 	// < 	JSON_HEX_TAG (use uppercase as in PHP)
														tmp_marker_val = tmp_marker_val.replace(/[\u003E]/g, '\\u003E'); 	// > 	JSON_HEX_TAG (use uppercase as in PHP)
														tmp_marker_val = tmp_marker_val.replace(/[\/]/g,     '\\/');	    // / 	JSON_UNESCAPED_SLASHES
														// this JSON string will not be 100% like the one produced via PHP with HTML-Safe arguments but at least have the minimum escapes to avoid conflicting HTML tags
														tmp_marker_val = String(_class.stringTrim(tmp_marker_val));
														if(tmp_marker_val == '') {
															tmp_marker_val = 'null'; // ensure a minimal json as null for empty string if no expr !
														} //end if
													//	if(debug) {
													//		console.log('Marker Format JSON: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|js') { // Escape JS
														tmp_marker_val = String(_class.escape_js(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker JS-Escape: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|html') { // Escape HTML
														tmp_marker_val = String(_class.escape_html(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker HTML-Escape: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|css') { // Escape CSS
														tmp_marker_val = String(_class.escape_css(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker CSS-Escape: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|nl2br') { // Format NL2BR
														tmp_marker_val = String(_class.nl2br(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker NL2BR-Reflow: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else if(escaping == '|syntaxhtml') { // fix back markers tpl escapings in html
														tmp_marker_val = String(_class.prepareNosyntaxHtmlMarkersTpl(tmp_marker_val));
													//	if(debug) {
													//		console.log('Marker Syntax-Html-Escape: ' + tmp_marker_id + ' :: ' + tmp_marker_key + ' #' + j + ' ' + escaping + ' @ ' + tmp_marker_val);
													//	} //end if
													} else {
														console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Invalid or Undefined Escaping for Marker: ' + escaping + ' - detected in Replacement Key: ' + tmp_marker_id + ' @ ' + tmp_marker_val + ' detected in Template: ^' + '\n' + template.substr(0, 512) + '$(0..512)... ###}');
													} //end if else
													//--
												} //end for
												//--
											} //end if
											//--
										} //end if else
										//--
									} //end if
									//--
									template = _class.stringReplaceAll(tmp_marker_id, tmp_marker_val, template);
									//--
								} //end if
								//--
							} //end if
						} //end if
						//--
					} //end for
					//--
				} //end if
				//--
				marker = null;
				markers = null;
				//-- replace specials: Square-Brackets(L/R) R N TAB SPACE
				if(template.indexOf('[%%%|') >= 0) { // indexOf() :: if not found returns -1
					template = _class.stringReplaceAll('[%%%|SB-L%%%]', '［', template);
					template = _class.stringReplaceAll('[%%%|SB-R%%%]', '］', template);
					template = _class.stringReplaceAll('[%%%|R%%%]', '\r', template);
					template = _class.stringReplaceAll('[%%%|N%%%]', '\n', template);
					template = _class.stringReplaceAll('[%%%|T%%%]', '\t', template);
					template = _class.stringReplaceAll('[%%%|SPACE%%%]', ' ', template);
				} //end if
				//--
				if(template.indexOf('[###') >= 0) { // indexOf() :: if not found returns -1
					//--
					var undef_markers = _class.stringRegexMatchAll(template, regexp);
				//	if(debug) {
				//		console.log(undef_markers);
				//	} //end if
					//--
					var undef_log = '';
					for(var i=0; i<undef_markers.length; i++) {
						var undef_marker = undef_markers[i]; // expects array
						var tmp_undef_marker_id = undef_marker[0] ? String(undef_marker[0]) : ''; // [###THE-MARKER|escapings...###]
						undef_log += tmp_undef_marker_id + '\n';
					} //end for
					console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Markers detected in Template: ^' + '\n' + undef_log + '\n' + template.substr(0, 512) + '$(0..512)... ###}');
					//--
					undef_log = null;
					undef_marker = null;
					undef_markers = null;
					//--
				} //end if
				//--
				if(template.indexOf('[%%%') >= 0) { // indexOf() :: if not found returns -1
					console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Marker Syntax detected in Template: ^' + '\n' + template.substr(0, 512) + '$(0..512)... ###}');
				} //end if
				if(template.indexOf('[@@@') >= 0) { // indexOf() :: if not found returns -1
					console.error('WARNING: SmartJS/Core@renderMarkersTpl: {### Undefined Marker Sub-Templates detected in Template: ^' + '\n' + template.substr(0, 512) + '$(0..512)... ###}');
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			console.error('ERROR: SmartJS/Core@renderMarkersTpl: {### Invalid Marker-TPL Arguments ###}');
			template = '';
			//--
		} //end if
		//--
		return String(template); // fix to return empty string instead of null [OK]
		//--
	} //END FUNCTION


} //END CLASS

//==================================================================
//==================================================================

// #END
