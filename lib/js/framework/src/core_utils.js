
// [LIB - Smart.Framework / JS / Core Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: -

//==================================================================
//==================================================================

//================== [ES6]

/**
 * CLASS :: Smart CoreUtils (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Core
 *
 * @desc The class provides the core methods for JavaScript API of Smart.Framework
 * @author unix-world.org
 * @license BSD
 * @file core_utils.js
 * @version 20251216
 * @class smartJ$Utils
 * @static
 * @frozen
 *
 */
const smartJ$Utils = new class{constructor(){ // STATIC CLASS
	'use strict';
	const _N$ = 'smartJ$Utils';

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

	const debug = false;

	/**
	 * Evaluate Mixed Js Code in a context
	 * Will evalute and execute mixed Js Code (Js callable method or Js plain code) within a try/catch context
	 * If fails, will log errors to the console and optional if set so it can display an alert message
	 *
	 * @memberof smartJ$Utils
	 * @method evalJsFxCode
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	fxContext 		The Fx context where is executed, used just for logging if fails to know which context is
	 * @param 	{JS-Code} 	jsCode 			Js callable method or Js plain code
	 * @param 	{Boolean} 	alertErr 		*Optional* Default is FALSE ; If set to TRUE if eval fails will display an alert message
	 *
	 * @return 	{Boolean} 					TRUE if successful, FALSE otherwise
	 */
	const evalJsFxCode = (fxContext, jsCode, alertErr=false) => { // ES6
		// use strict mode in this context ! this is enabled per class, otherwise is mandatory here for security, eval !
		const _m$ = 'evalJsFxCode';
		if(jsCode != undefined) {
			const errMsg = 'Js Code Eval FAILED';
			const errHint = 'See the javascript console for more details ...';
			fxContext = stringPureVal(fxContext);
			if(typeof(jsCode) === 'function') {
				try {
					jsCode();
				} catch(err) {
					_p$.error(_N$, _m$, 'ERR:', errMsg, 'Context:', fxContext, '[F]', 'JS Errors:', err);
					if(alertErr === true) {
						alert('JS Errors: ' + errMsg + ' in [F] Context: ' + fxContext + '\n' + errHint);
					} //end if
					return false;
				}
			} else {
				jsCode = stringPureVal(jsCode, true);
				if(stringIStartsWith(jsCode, 'javascript:')) { // eliminate the 'javascript:' prefix, if string may come from an element onSomething html attribute which can contain this as prefix
					jsCode = jsCode.substring(11);
					jsCode = stringTrim(jsCode);
				} //end if
				if((typeof(jsCode) === 'string') && (jsCode != 'undefined') && (jsCode != '')) { // important: and if was undefined and if casted by mistake to string by passing from a method to another it may become the 'undefined' string, which is not valid !
					try {
						eval('(() => { ' + '\n' + ' \'use strict\';' + '\n' + String(jsCode) + '\n' + ' })();'); // need to be sandboxed in a method ; the code can make use of return ; avoid this type of error !
					} catch(err) {
						_p$.error(_N$, _m$, 'ERR:', errMsg, 'Context:', fxContext, '[S]', 'JS Errors:', err);
						if(alertErr === true) {
							alert('JS Errors: ' + errMsg + ' in [S] Context: ' + fxContext + '\n' + errHint);
						} //end if
						return false;
					}
				}
			}
		}
		return true;
	};
	_C$.evalJsFxCode = evalJsFxCode; // export

	/**
	 * Check if a variable is plain object derived from {}
	 *
	 * @memberof smartJ$Utils
	 * @method isPlainObject
	 * @static
	 * @arrow
	 *
	 * @param 	{Mixed} 	a 		The input variable
	 * @return 	{Boolean} 			TRUE is it is a plain object ; FALSE otherwise
	 */
	const isPlainObject = (a) => {
		return !!((!!a) && (a.constructor === Object));
	};
	_C$.isPlainObject = isPlainObject; // export

	/**
	 * Check if a number is valid: must be Number, Finite and !NaN
	 *
	 * @memberof smartJ$Utils
	 * @method isFiniteNumber
	 * @static
	 * @arrow
	 *
	 * @param 	{Number} 	num 	The number to be tested
	 * @return 	{Boolean} 			TRUE is number is Finite and !NaN ; FALSE otherwise
	 */
	//--
	// http://stackoverflow.com/questions/5690071/why-check-for-isnan-after-isfinite
	// # https://www.w3schools.com/jsref/jsref_isfinite_number.asp
	// Number.isFinite() is different from the global isFinite() method.
	// The global isFinite() method converts the tested value to a Number, then tests it.
	// Number.isFinite() does not convert the values to a Number, and will not return true for any value that is not of the type Number.
	// thus, isFinite('') or isFinite(' ') will return TRUE while the Number.isFinite('') or Number.isFinite(' ') will return FALSE !!
	// Number.isFinite() is N/A in Internet Explorer or older browsers, so will use here the global isFinite() with a fix
	//--
	const isFiniteNumber = (num) => !! (typeof(num) != 'number') ? false : !! (Number.isFinite(num) && (!Number.isNaN(num))); // ES6
	_C$.isFiniteNumber = isFiniteNumber; // export

	/**
	 * Return a string pure value from an input of any kind
	 * RULES:
	 * - Undefined, Null or False will be casted to empty string as ''
	 * - True will be casted to '1' as in PHP not to 'true' as in Javascript
	 * - Object or Function will be casted to empty string for safety, as ''
	 * - Numerioc values will be casted to string as they are
	 *
	 * @memberof smartJ$Utils
	 * @method stringPureVal
	 * @static
	 * @arrow
	 *
	 * @param 	{Mixed} 	input 	The input
	 * @param 	{Boolean} 	trim 	If TRUE will trim the output result
	 * @return 	{String} 			The value converted to string, using rules from above
	 */
	const stringPureVal = (input, trim) => { // ES6
		//--
		if(
			(typeof(input) == 'undefined') || (input === undefined) ||
			(input === null) || (input === false) ||
			(typeof(input) === 'object') || (typeof(input) === 'function')
		) {
			input = '';
		} else if(input === true) {
			input = '1';
		} else {
			input = String(input);
		} //end if else
		//--
		if(trim === true) {
			input = stringTrim(input);
		} //end if
		//--
		return String(input);
		//--
	}; // END
	_C$.stringPureVal = stringPureVal; // export

	/**
	 * Trim a string for a given character
	 *
	 * @memberof smartJ$Utils
	 * @method stringCharTrim
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be trimmed
	 * @param 	{String} 	char 	The char to be used for trim (must be a single character)
	 * @return 	{String} 			The trimmed string by the given character
	 */
	const stringCharTrim = (str, char) => { // if more than one char need to be used for 2nd argument, it must be done in a loop for each one
		//--
		const _m$ = 'stringCharTrim';
		//--
		str = stringPureVal(str);
		if(str == '') {
			return '';
		} //end if
		//--
		char = stringPureVal(char);
		if(char == '') {
			_p$.warn(_N$, _m$, 'Character is Empty');
			return str;
		} //end if
		if(char.length != 1) {
			_p$.warn(_N$, _m$, 'Character Size must be 1');
			return str;
		} //end if
		//--
		return String(str.split(char).filter(Boolean).join(char));
		//--
	};
	_C$.stringCharTrim = stringCharTrim; // export

	/**
	 * Trim a string for whitespaces (at begining and/or end) ; whitespaces: space \ n \ r \ t)
	 *
	 * @memberof smartJ$Utils
	 * @method stringTrim
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be trimmed
	 * @return 	{String} 			The whitespaces trimmed string
	 */
	const stringTrim = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.replace(/^\s+|\s+$/g, '')); // fix to return empty string instead of null ; use the 'g' flag to contionue after first match (spaces from start, also for spaces from end)
		//--
	}; // END
	_C$.stringTrim = stringTrim; // export

	/**
	 * Left Trim a string for whitespaces (at begining and/or end) ; whitespaces: space \ n \ r \ t)
	 *
	 * @memberof smartJ$Utils
	 * @method stringLeftTrim
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be trimmed on Left
	 * @return 	{String} 			The whitespaces trimmed string (left only)
	 */
	const stringLeftTrim = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.replace(/^\s+/, '')); // fix to return empty string instead of null ; without the 'g' flag (no need to continue after first match)
		//--
	}; // END
	_C$.stringLeftTrim = stringLeftTrim; // export

	/**
	 * Right Trim a string for whitespaces (at begining and/or end) ; whitespaces: space \ n \ r \ t)
	 *
	 * @memberof smartJ$Utils
	 * @method stringRightTrim
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be trimmed on Right
	 * @return 	{String} 			The whitespaces trimmed string (right only)
	 */
	const stringRightTrim = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.replace(/\s+$/, '')); // fix to return empty string instead of null ; without the 'g' flag (no need to continue after first match)
		//--
	}; // END
	_C$.stringRightTrim = stringRightTrim; // export

	/**
	 * Make uppercase the first character of a string
	 * @hint The implementation is compatible with PHP unicode mb_convert_case/MB_CASE_UPPER on the first letter
	 *
	 * @memberof smartJ$Utils
	 * @method stringUcFirst
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be processed
	 * @return 	{String} 			The processed string
	 */
	const stringUcFirst = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		if(str.length > 1) {
			return String(str.charAt(0).toUpperCase() + str.substring(1));
		} else {
			return String(str.toUpperCase());
		} //end if else
		//--
	}; //END
	_C$.stringUcFirst = stringUcFirst; // export

	/**
	 * Make uppercase the first character of each word in a string while making lowercase the others
	 * @hint The implementation is compatible with PHP unicode mb_convert_case/MB_CASE_TITLE
	 *
	 * @memberof smartJ$Utils
	 * @method stringUcWords
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be processed
	 * @return 	{String} 			The processed string
	 */
	const stringUcWords = (str) => { // ES6
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
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.toLowerCase().replace(/^(.)|\s+(.)/g, ($1) => $1 ? String($1).toUpperCase() : ''));
		//--
	}; //END
	_C$.stringUcWords = stringUcWords; // export

	/**
	 * Replace all occurences in a string - Case Sensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringReplaceAll
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	token 		The string part to be replaced
	 * @param 	{String} 	newToken 	The string part replacement
	 * @param 	{String} 	str 		The string where to do the replacements
	 * @return 	{String} 				The processed string
	 */
	const stringReplaceAll = (token, newToken, str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.split(token).join(newToken)); // fix to return empty string instead of null
		//--
	}; //END
	_C$.stringReplaceAll = stringReplaceAll; // export

	/**
	 * Replace all occurences in a string - Case Insensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringIReplaceAll
	 * @static
	 *
	 * @param 	{String} 	token 		The string part to be replaced
	 * @param 	{String} 	newToken 	The string part replacement
	 * @param 	{String} 	str 		The string where to do the replacements
	 * @return 	{String} 				The processed string
	 */
	const stringIReplaceAll = function(token, newToken, str) { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		if((typeof(token) === 'string') && (typeof(newToken) === 'string')) {
			//--
			token = token.toLowerCase();
			//--
			let i = -1;
			while((i = str.toLowerCase().indexOf(token, i >= 0 ? i + newToken.length : 0)) !== -1) {
				str = String(str.substring(0, i) + newToken + str.substring(i + token.length));
			} //end while
			//--
		} //end if
		//--
		return String(str); // fix to return empty string instead of null
		//--
	}; //END
	_C$.stringIReplaceAll = stringIReplaceAll; // export

	/**
	 * Regex Match All occurences.
	 * @hint It is compatible just with the PHP preg_match_all()
	 *
	 * @memberof smartJ$Utils
	 * @method stringRegexMatchAll
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be searched
	 * @param 	{Regex} 	regexp 		A valid regular expression
	 * @return 	{Array} 				The array with matches
	 */
	const stringRegexMatchAll = function(str, regexp) { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return [];
		} //end if
		//--
		if(regexp == undefined) {
			return [];
		} //end if
		if(!(regexp instanceof RegExp)) {
			_p$.error(_N$, 'ERR: stringRegexMatchAll: 2nd param must be a Regex and is not');
			return [];
		} //end if
		//--
		let matches = [];
		//--
		if(String.prototype.matchAll) { // ES7 version ; FFox67, Chrome73, Safari13, Edge79
			//--
			try { // The RegExp object must have the /g flag, otherwise a TypeError will be thrown.
				matches = Array.from(str.matchAll(regexp));
			} catch(err) {
				_p$.error(_N$, 'ERR: stringRegexMatchAll (1):', err);
				return [];
			} //end try catch
			//--
		} else { // ES6 version
			//--
			const getRegexLimitCycles = () => { // {{{SYNC-JS-SMART-REGEX-RECURSION-LIMIT}}}
				//--
				const RegexRecursionLimit = 800000; // set a safe value as for PHP pcre.recursion_limit, but higher enough ; default is 800000
				//--
				const min = 100000;
				const max = min * 10;
				//--
				let cycles = RegexRecursionLimit;
				//--
				if(cycles < min) {
					cycles = min;
				} else if(cycles > max) {
					cycles = max;
				} //end if else
				//--
				return cycles;
				//--
			};
			//--
			const maxloops = getRegexLimitCycles();
			//--
			let match = null;
			let loopidx = 0;
			try { // The RegExp object must have the /g flag, otherwise will enter into an infinite loop so a hardlimit of maxloops is used
				while((match = regexp.exec(str)) !== null) { // fixed variant
					if(match.index === regexp.lastIndex) { // avoid infinite loops with zero-width matches
						regexp.lastIndex++;
					} //end if
					matches.push(match);
					loopidx++;
					if(loopidx >= maxloops) { // protect against infinite loop
						_p$.warn(_N$, 'WARN: stringRegexMatchAll hardlimit loop (2). Max recursion depth is', maxloops);
						break;
					} //end if
				} //end while
			} catch(err) {
				_p$.error(_N$, 'ERR: stringRegexMatchAll (2):', err);
				return [];
			} //end try catch
			//--
		} //end if else
		//--
		return matches; // Array
		//--
	}; //END
	_C$.stringRegexMatchAll = stringRegexMatchAll; // export

	/*
	 * Find if a string contains another sub-string, case sensitive or insensitive
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @param 	{Integer} 	pos 			*Optional* ; Default is zero ; The position to start (0 ... n)
	 * @param 	{Boolean} 	caseInsensitive *Optional* ; default is FALSE ; If TRUE will do a case insensitive search
	 * @return 	{Boolean} 					TRUE if the str contains search begining from the pos
	 */
	const strContains = (str, search, pos=0, caseInsensitive=false) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return false;
		} //end if
		//--
		search = String((search == undefined) ? '' : search); // force string, test undefined is also for null
		if(search == '') {
			return false;
		} //end if
		//--
		if(pos == undefined) {
			pos = 0;
		} //end if
		pos = format_number_int(pos);
		if(pos < 0) {
			_p$.error(_N$, 'ERR: strContains: Position is Negative:', pos);
			return false;
		} //end if
		//--
		if(caseInsensitive === true) {
			str = str.toLowerCase();
			search = search.toLowerCase();
		} //end if
		//--
		return !! str.includes(search, pos); // boolean
		//--
	}; //END
	// no export

	/**
	 * Find if a string contains another sub-string, case sensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringContains
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @param 	{Integer} 	pos 			*Optional* ; Default is zero ; The position to start (0 ... n)
	 * @return 	{Boolean} 					TRUE if the str contains search begining from the pos
	 */
	const stringContains = (str, search, pos=0) => !! strContains(str, search, pos, false); // ES6
	_C$.stringContains = stringContains; // export

	/**
	 * Find if a string contains another sub-string, case insensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringIContains
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @param 	{Integer} 	pos 			*Optional* ; Default is zero ; The position to start (0 ... n)
	 * @return 	{Boolean} 					TRUE if the str contains search begining from the pos
	 */
	const stringIContains = (str, search, pos=0) => !! strContains(str, search, pos, true); // ES6
	_C$.stringIContains = stringIContains; // export

	/*
	 * Find if a string starts with another sub-string
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @param 	{Boolean} 	caseInsensitive *Optional* ; default is FALSE ; If TRUE will do a case insensitive search
	 * @return 	{Boolean} 					TRUE if the str starts with the search
	 */
	const strStartsWith = (str, search, caseInsensitive=false) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return false;
		} //end if
		//--
		search = String((search == undefined) ? '' : search); // force string, test undefined is also for null
		if(search == '') {
			return false;
		} //end if
		//--
		if(caseInsensitive === true) {
			str = str.toLowerCase();
			search = search.toLowerCase();
		} //end if
		//--
		return !! str.startsWith(search, 0); // bool
		//--
	}; //END
	// no export

	/**
	 * Find if a string starts with another sub-string, case sensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringStartsWith
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @return 	{Boolean} 					TRUE if the str starts with the search
	 */
	const stringStartsWith = (str, search) => !! strStartsWith(str, search, false); // ES6
	_C$.stringStartsWith = stringStartsWith; // export

	/**
	 * Find if a string starts with another sub-string, case insensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringIStartsWith
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @return 	{Boolean} 					TRUE if the str starts with the search
	 */
	const stringIStartsWith = (str, search) => !! strStartsWith(str, search, true); // ES6
	_C$.stringIStartsWith = stringIStartsWith; // export

	/*
	 * Find if a string ends with another sub-string
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @param 	{Boolean} 	caseInsensitive *Optional* ; default is FALSE ; If TRUE will do a case insensitive search
	 * @return 	{Boolean} 					TRUE if the str ends with the search
	 */
	const strEndsWith = (str, search, caseInsensitive=false) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return false;
		} //end if
		//--
		search = String((search == undefined) ? '' : search); // force string, test undefined is also for null
		if(search == '') {
			return false;
		} //end if
		//--
		if(caseInsensitive === true) {
			str = str.toLowerCase();
			search = search.toLowerCase();
		} //end if
		//--
		return !! str.endsWith(search); // bool
		//--
	}; //END
	// no export

	/**
	 * Find if a string ends with another sub-string, case sensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringEndsWith
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @return 	{Boolean} 					TRUE if the str ends with the search
	 */
	const stringEndsWith = (str, search) => !! strEndsWith(str, search, false); // ES6
	_C$.stringEndsWith = stringEndsWith; // export

	/**
	 * Find if a string ends with another sub-string, case insensitive
	 *
	 * @memberof smartJ$Utils
	 * @method stringIEndsWith
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to search in (haystack)
	 * @param 	{String} 	search 			The string to search for in haystack (needle)
	 * @return 	{Boolean} 					TRUE if the str ends with the search
	 */
	const stringIEndsWith = (str, search) => !! strEndsWith(str, search, true); // ES6
	_C$.stringIEndsWith = stringIEndsWith; // export

	/**
	 * Quote regular expression characters in a string.
	 * @hint It is compatible with PHP preg_quote() method.
	 *
	 * @memberof smartJ$Utils
	 * @method preg_quote
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The string to be processed
	 * @return 	{String} 				The processed string
	 */
	const preg_quote = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
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
		return String(str.replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-\#])/g, '\\$1'));
		//--
	}; //END
	_C$.preg_quote = preg_quote; // export

	/*
	 * Split string by character with trimming prefix/suffix
	 *
	 * @private no export
	 *
	 * @noexport
	 * @static
	 *
	 * @param 	{Enum} 		str 	The string to be splitted by 2nd param
	 * @param 	{String} 	by 		The character the string to be splitted on ; available characters: '=' | ':' | ';' | ','
	 * @return 	{Array} 			The array with string parts splitted
	 */
	const stringSplitbyChar = function(str, by) { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return []; // empty string
		} //end if
		//--
		by = String((by == undefined) ? '' : by); // force string, test undefined is also for null
		let regex = false;
		switch(by) {
			case '=':
				regex = true; // /\s*\=\s*/;
				break;
			case ':':
				regex = true; // /\s*\:\s*/;
				break;
			case ';':
				regex = true; // /\s*;\s*/;
				break;
			case ',':
				regex = true; // /\s*,\s*/;
				break;
			default:
				regex = false; // invalid
		} //end switch
		//--
		if(!regex) {
			return []; // invalid
		} //end if
		//--
		regex = new RegExp('\\s*' + preg_quote(by) + '\\s*');
		//--
		str = stringTrim(str);
		//--
		return str.split(regex); // Array
		//--
	}; //END
	// no export

	/**
	 * Split string by equal (=) with trimming prefix/suffix
	 *
	 * @memberof smartJ$Utils
	 * @method stringSplitbyEqual
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be splitted by = (equal)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	const stringSplitbyEqual = (str) => stringSplitbyChar(str, '='); // Array, ES6
	_C$.stringSplitbyEqual = stringSplitbyEqual; // export

	/**
	 * Split string by colon (:) with trimming prefix/suffix
	 *
	 * @memberof smartJ$Utils
	 * @method stringSplitbyColon
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be splitted by : (colon)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	const stringSplitbyColon = (str) => stringSplitbyChar(str, ':'); // Array, ES6
	_C$.stringSplitbyColon = stringSplitbyColon; // export

	/**
	 * Split string by semicolon (;) with trimming prefix/suffix
	 *
	 * @memberof smartJ$Utils
	 * @method stringSplitbySemicolon
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The string to be splitted by ; (semicolon)
	 * @return 	{Array} 				The array with string parts splitted
	 */
	const stringSplitbySemicolon = (str) => stringSplitbyChar(str, ';'); // Array, ES6
	_C$.stringSplitbySemicolon = stringSplitbySemicolon; // export

	/**
	 * Split string by comma (,) with trimming prefix/suffix
	 *
	 * @memberof smartJ$Utils
	 * @method stringSplitbyComma
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 	The string to be splitted by , (comma)
	 * @return 	{Array} 			The array with string parts splitted
	 */
	const stringSplitbyComma = (str) => stringSplitbyChar(str, ','); // Array, ES6
	_C$.stringSplitbyComma = stringSplitbyComma; // export

	/**
	 * Get the first element from an Array
	 *
	 * @memberof smartJ$Utils
	 * @method arrayGetFirst
	 * @static
	 * @arrow
	 *
	 * @param 	{Array} 	arr 		The array to be used
	 * @return 	{Mixed} 				The first element from the array
	 */
	const arrayGetFirst = (arr) => (arr instanceof Array) ? arr.shift() : null; // Mixed, ES6
	_C$.arrayGetFirst = arrayGetFirst; // export

	/**
	 * Get the last element from an Array
	 *
	 * @memberof smartJ$Utils
	 * @method arrayGetLast
	 * @static
	 * @arrow
	 *
	 * @param 	{Array} 	arr 		The array to be used
	 * @return 	{Mixed} 				The last element from the array
	 */
	const arrayGetLast = (arr) => (arr instanceof Array) ? arr.pop() : null; // Mixed, ES6
	_C$.arrayGetLast = arrayGetLast; // export

	/**
	 * Format a number as FLOAT
	 *
	 * @memberof smartJ$Utils
	 * @method format_number_float
	 * @static
	 * @arrow
	 *
	 * @param 	{Numeric} 	num 					A numeric value
	 * @param 	{Boolean} 	allow_negatives 		*Optional* ; default is TRUE ; If set to FALSE will disallow negative values and if negative value detected will reset it to zero
	 * @return 	{Float} 							A float number
	 */
	const format_number_float = (num, allow_negatives=true) => { // ES6
		//--
		if(num == undefined) {
			num = 0;
		} //end if
		num = Number(num);
		num = isFiniteNumber(num) ? num : 0;
		if(allow_negatives !== true) {
			if(num < 0) {
				num = 0;
			} //end if
		} //end if
		num = Number(num);
		//--
		return isFiniteNumber(num) ? num : 0; // Float
		//--
	}; //END
	_C$.format_number_float = format_number_float; // export

	/**
	 * Format a number as INTEGER
	 *
	 * @memberof smartJ$Utils
	 * @method format_number_int
	 * @static
	 * @arrow
	 *
	 * @param 	{Numeric} 	num 					A numeric value
	 * @param 	{Boolean} 	allow_negatives 		If TRUE will allow negative values else will return just positive (unsigned) values
	 * @return 	{Integer} 							An integer number
	 */
	const format_number_int = (num, allow_negatives=true) => { // ES6
		//--
		if(num == undefined) {
			num = 0;
		} //end if
		num = Number(num);
		num = isFiniteNumber(num) ? Math.round(num) : 0;
		if(!Number.isInteger(num)) {
			num = 0; // check
		} //end if
		if(allow_negatives !== true) {
			if(num < 0) {
				num = 0;
			} //end if
		} //end if
		if(!Number.isSafeInteger(num)) { // {{{SMART-JS-NEWEST-METHOD}}}
			if(num > 0) {
				num = Number.MAX_SAFE_INTEGER;
			} else if(num < 0) {
				num = Number.MIN_SAFE_INTEGER;
			} //end if else
		} //end if
		num = Number(num);
		//--
		return (isFiniteNumber(num) && Number.isInteger(num)) ? num : 0; // Integer
		//--
	}; //END
	_C$.format_number_int = format_number_int; // export

	/**
	 * Format a number as DECIMAL
	 *
	 * @memberof smartJ$Utils
	 * @method format_number_dec
	 * @static
	 * @arrow
	 *
	 * @param 	{Numeric} 	num 						A numeric value as Number or String ; decimal separator is `.` (dot)
	 * @param 	{Integer} 	decimals 					*Optional* Default is 2 ; The number of decimal to use (between 1 and 13)
	 * @param 	{Boolean} 	allow_negatives 			*Optional* Default is TRUE ; If FALSE will disallow negative (will return just positive / unsigned values)
	 * @param 	{Boolean} 	discard_trailing_zeroes 	*Optional* Default is FALSE ; If set to TRUE will discard trailing zeroes
	 * @param 	{Boolean} 	decimals_separator 			*Optional* Default is FALSE ; If set to TRUE will add thousands separator `,` (comma)
	 * @return 	{String} 								A decimal number as string to keep the fixed decimals as specified
	 */
	const format_number_dec = (num, decimals=2, allow_negatives=true, discard_trailing_zeroes=false, decimals_separator=false) => { // ES6
		//--
		if(num == undefined) {
			num = 0;
		} //end if
		//--
		if(decimals == undefined) {
			decimals = 2; // default
		} //end if
		decimals = format_number_int(decimals, false);
		if(decimals < 1) {
			decimals = 1;
		} else if(decimals > 13) {
			decimals = 13;
		} //end if else
		//--
		if(allow_negatives !== false) {
			allow_negatives = true; // default
		} //end if
		//--
		if(discard_trailing_zeroes !== true) {
			discard_trailing_zeroes = false; // default
		} //end if
		//--
		num = format_number_float(num, allow_negatives);
		//--
		if(allow_negatives !== true) {
			if(num < 0) {
				num = 0;
			} //end if
		} //end if
		//--
		num = num.toFixed(decimals);
		if(discard_trailing_zeroes !== false) {
			num = Number.parseFloat(num); // must be parse float here
		} //end if
		//--
		if(decimals_separator === true) {
			num = String(num).replace(/\B(?<!\.\d*)(?=(\d{3})+(?!\d))/g, ',');
		} //end if
		//--
		return String(num); // String
		//--
	}; //END
	_C$.format_number_dec = format_number_dec; // export

	/**
	 * Get a Random (Integer) Number between Min and Max
	 *
	 * @memberof smartJ$Utils
	 * @method randNum
	 * @static
	 * @arrow
	 *
	 * @param 	{Integer} 	min 						The min value
	 * @param 	{Integer} 	max 						The max value
	 * @return 	{Integer} 								The random value
	 */
	const randNum = (min, max) => {
		//--
		min = format_number_int(min, false);
		max = format_number_int(max, false);
		//--
		if(min < 0) {
			return 0;
		} //end if
		if(max < 1) {
			return 0;
		} //end if
		if(min === max) {
			return min;
		} else if(min > max) {
			return max;
		} //end if else
		//--
		return format_number_int(min + Math.floor(Math.random() * (max - min + 1)), false);
		//--
	};
	_C$.randNum = randNum; // export

	/**
	 * Un-quotes a quoted string.
	 * It will remove double / quoted slashes.
	 * @hint It is compatible with PHP stripslashes() method.
	 *
	 * @memberof smartJ$Utils
	 * @method stripslashes
	 * @static
	 *
	 * @param {String} str The string to be processed
	 * @return {String} The processed string
	 */
	const stripslashes = function(str) {
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		const replacer = (s, n1) => {
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
		};
		//-- original written by: Kevin van Zonneveld (http://kevin.vanzonneveld.net) ; improved by: Ates Goral, marrtins, rezna ; fixed / bugfixed by: Mick@el, Onno Marsman, Brett Zamir, Rick Waldron, Brant Messenger
		return String(str.replace(/\\(.?)/g, replacer));
		//--
	}; //END
	_C$.stripslashes = stripslashes; // export

	/**
	 * Quote string with slashes in a C style.
	 * @hint It is compatible with PHP addcslashes() method.
	 *
	 * @memberof smartJ$Utils
	 * @method addcslashes
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @param 	{String} 	charlist 	A list of characters to be escaped. If charlist contains characters \n, \r etc., they are converted in C-like style, while other non-alphanumeric characters with ASCII codes lower than 32 and higher than 126 converted to octal representation
	 * @return 	{String} 				Returns the escaped string
	 */
	const addcslashes = function(str, charlist) { // ES6
		//--
		//  discuss at: http://phpjs.org/functions/addcslashes/
		// original by: Brett Zamir (http://brett-zamir.me)
		//        note: We show double backslashes in the return value example code below because a JavaScript string will not
		//        note: render them as backslashes otherwise
		//   example 1: addcslashes('foo[ ]', 'A..z'); // Escape all ASCII within capital A to lower z range, including square brackets
		//   returns 1: "\\f\\o\\o\\[ \\]"
		//   example 2: addcslashes("zoo['.']", 'z..A'); // Only escape z, period, and A here since not a lower-to-higher range
		//   returns 2: "\\zoo['\\.']"
		//   example 3: addcslashes("@a\u0000\u0010\u00A9", "\x00..\x1F!@\x7F..\xFF"); // Escape as octals those specified and less than 32 (0x20) or greater than 126 (0x7E), but not otherwise
		//   returns 3: '\\@a\\000\\020\\302\\251'
		//   example 4: addcslashes("\u0020\u007E", "\x20..\x7D"); // Those between 32 (0x20 or 040) and 126 (0x7E or 0176) decimal value will be backslashed if specified (not octalized)
		//   returns 4: '\\ ~'
		//   example 5: addcslashes("\r\u0007\n", '\x00..\x1F'); // Recognize C escape sequences if specified
		//   returns 5: "\\r\\a\\n"
		//   example 6: addcslashes("\r\u0007\n", '\x00'); // Do not recognize C escape sequences if not specified
		//   returns 6: "\r\u0007\n"
		// improved by: unix-world.org
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
		charlist = String((charlist == undefined) ? '' : charlist); // force string, test undefined is also for null
		if(charlist == '') {
			return '';
		} //end if
		//--
		let target = '',
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
			encoded = '';
		//--
		const percentHex = /%([\dA-Fa-f]+)/g;
		//--
		const _pad = (n, c) => {
			if((n = String(n)).length < c) {
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
					begin = rangeBegin.charCodeAt(0); // Octal begins range
					if((/\\\d/).test(charlist.charAt(postOctalPos + 2) + charlist.charAt(postOctalPos + 3))) {
						rangeEnd = charlist.slice(postOctalPos + 3).match(/^\d+/)[0]; // Range ends with octal
						i += 1; // Skip range end backslash
					} else if(charlist.charAt(postOctalPos + 2)) {
						rangeEnd = charlist.charAt(postOctalPos + 2); // Range ends with character
					} else {
						_p$.error(_N$, 'ERR: addcslashes: Range with no end point (1)');
					} //end if else
					end = rangeEnd.charCodeAt(0);
					if(end > begin) { // Treat as a range
						for(j=begin; j<=end; j++) {
							chrs.push(String.fromCharCode(j));
						} //end for
					} else { // Supposed to treat period, begin and end as individual characters only, not a range
						chrs.push('.', rangeBegin, rangeEnd);
					} //end if else
					i += rangeEnd.length + 2; // Skip dots and range end (already skipped range end backslash if present)
				} else { // Octal is by itself
					chr = String.fromCharCode(parseInt(rangeBegin, 8)); // must be parse int here
					chrs.push(chr);
				} //end if else
				i += octalLength; // Skip range begin
			} else if(next + charlist.charAt(i + 2) === '..') { // Character begins range
				rangeBegin = c;
				begin = rangeBegin.charCodeAt(0);
				if((/\\\d/).test(charlist.charAt(i + 3) + charlist.charAt(i + 4))) { // Range ends with octal
					rangeEnd = charlist.slice(i + 4).match(/^\d+/)[0];
					i += 1; // Skip range end backslash
				} else if(charlist.charAt(i + 3)) {
					rangeEnd = charlist.charAt(i + 3); // Range ends with character
				} else {
					_p$.error(_N$, 'ERR: addcslashes: Range with no end point (2)');
				} //end if else
				end = rangeEnd.charCodeAt(0);
				if(end > begin) { // Treat as a range
					for(j=begin; j<=end; j++) {
						chrs.push(String.fromCharCode(j));
					} //end for
				} else {
					chrs.push('.', rangeBegin, rangeEnd); // Supposed to treat period, begin and end as individual characters only, not a range
				} //end if else
				i += rangeEnd.length + 2; // Skip dots and range end (already skipped range end backslash if present)
			} else { // Character is by itself
				chrs.push(c);
			} //end if else
		} //end for
		//--
		for(i=0; i<str.length; i++) {
			c = str.charAt(i);
			if(chrs.indexOf(c) !== -1) {
				target += '\\';
				cca = c.charCodeAt(0);
				if(cca < 32 || cca > 126) { // Needs special escaping
					switch(c) {
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
							//target += _pad(cca.toString(8), 3);break; // it is Sufficient only for UTF-16 ; below handles all
							encoded = encodeURIComponent(c);
							let escHexGrps = stringRegexMatchAll(encoded, percentHex); // Array
							let z;
							for(z=0; z<escHexGrps.length; z++) { // 3-length-padded UTF-8 octets
								if(z > 0) { // already added a slash above, so add only for cycles >= 1
									target += '\\';
								} //end if
								target += _pad(parseInt(escHexGrps[z][1], 16).toString(8), 3); // must be parse int here
							} //end for
							break;
					} //end switch
				} else { // Perform regular backslashed escaping
					target += c;
				} //end if else
			} else { // Just add the character unescaped
				target += c;
			} //end if else
		} //end for
		//--
		return String(target ? target : '');
		//--
	}; //END
	_C$.addcslashes = addcslashes; // export

	/**
	 * Convert special characters to HTML entities, compatible with Twig standard.
	 * @hint It is like the Smart::escape_css() from the PHP Smart.Framework.
	 *
	 * @memberof smartJ$Utils
	 * @method escape_css
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The safe escaped string to be injected in CSS code
	 */
	const escape_css = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//--
	//	return String(addcslashes(String((str == undefined) ? '' : str), "\x00..\x1F!\"#$%&'()*+,./:;<=>?@[\\]^`{|}~")); // WD-CSS21-20060411 standard
		//--
		let out = '';
		for(let i=0; i<str.length; i++) {
			let c = str.substring(i, i + 1);
			let code = c.charCodeAt(0);
			if(((code >= 65) && (code <= 90)) || ((code >= 97) && (code <= 122)) || ((code >= 48) && (code <= 57))) {
				out += String(c); // a-zA-Z0-9
			} else {
				out += '\\' + ('0000' + code.toString(16)).slice(-4).toUpperCase(); // UTF-8
			} //end if else
		} //end for
		//--
		return String(out);
		//--
	}; //END
	_C$.escape_css = escape_css; // export

	/*
	 * Convert special characters to HTML entities with Options.
	 * Depending on the flag parameter, the following values will be converted to safe HTML entities:
	 * 		ENT_COMPAT: 	< > & "
	 * 		ENT_QUOTES: 	< > & " '
	 * 		ENT_NOQUOTES: 	< > &
	 * @hint It is like the htmlspecialchars() from PHP.
	 *
	 * @private internal development only
	 *
	 * @memberof smartJ$Utils
	 * @method htmlspecialchars
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @param 	{Enum} 		flag 		*Optional* A bitmask of one or more of the following flags: ENT_COMPAT (default) ; ENT_QUOTES ; ENT_NOQUOTES
	 * @return 	{String} 				The safe escaped string to be injected in HTML code
	 */
	const htmlspecialchars = (str, flag='ENT_COMPAT') => { // ES6
		//-- format string
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//-- test empty flag
		flag = String((flag == undefined) ? '' : flag); // force string, test undefined is also for null
		if(flag == '') {
			flag = 'ENT_COMPAT';
		} //end if
		//-- replace basics
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/\</g, '&lt;');
		str = str.replace(/\>/g, '&gt;');
		//-- replace quotes, depending on flag
		if(flag == 'ENT_QUOTES') { // ENT_QUOTES
			//-- replace all quotes: ENT_QUOTES
			str = str.replace(/"/g, '&quot;');
			str = str.replace(/'/g, '&#039;');
			//--
		} else if(flag != 'ENT_NOQUOTES') { // ENT_COMPAT
			//-- default, replace just double quotes
			str = str.replace(/"/g, '&quot;');
			//--
		} //end if else
		//--
		return String(str); // fix to return empty string instead of null
		//--
	}; //END
	_C$.htmlspecialchars = htmlspecialchars; // export, hidden

	/**
	 * Convert special characters to HTML entities.
	 * These values will be converted to safe HTML entities: < > & "
	 * @hint It is like the Smart::escape_html() from the PHP Smart.Framework.
	 *
	 * @memberof smartJ$Utils
	 * @method escape_html
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The safe escaped string to be injected in HTML code
	 */
	const escape_html = (str) => String(htmlspecialchars(str, 'ENT_COMPAT')); // ES6
	_C$.escape_html = escape_html; // export

	/**
	 * Convert special characters to escaped entities for safe use with Javascript Strings.
	 * @hint It is like the Smart::escape_js() from the PHP Smart.Framework.
	 *
	 * @memberof smartJ$Utils
	 * @method escape_js
	 * @static
	 *
	 * @param 	{String} 	str 		The string to be escaped
	 * @return 	{String} 				The escaped string using the json encode standard to be injected between single quotes '' or double quotes ""
	 */
	const escape_js = function(str) { // (ES6)
		//--
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
		//-- escape a string as unicode
		const escape_unicode = (str) => {
			str = String((str == undefined) ? '' : str);
			if(str == '') {
				return '';
			} //end if
			return String('\\u' + ('0000' + str.charCodeAt(0).toString(16)).slice(-4).toLowerCase());
		};
		//-- table of character substitutions: get from json2.js but excludding the " which is done later to preserve compatibility with PHP
		const replace_meta = (str) => {
			//--
			str = String((str == undefined) ? '' : str);
			if(str == '') {
				return '';
			} //end if
			//--
			switch(str) {
				case '\b':
					str = '\\b';
					break;
				case '\t':
					str = '\\t';
					break;
				case '\n':
					str = '\\n';
					break;
				case '\f':
					str = '\\f';
					break;
				case '\r':
					str = '\\r';
					break;
				case '\\':
					str = '\\\\';
					break;
				default:
					str = escape_unicode(str);
			} //end switch
			return String(str);
		};
		//-- init
		let encoded = '';
		//-- replace meta
		encoded = str.replace(/[\x00-\x1f\x7f-\x9f\\]/g, (a) => replace_meta(a));
		//-- replace unicode characters
		encoded = encoded.replace(/[\u007F-\uFFFF]/g, (c) => escape_unicode(c));
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
	}; //END
	_C$.escape_js = escape_js; // export

	/**
	 * Safe escape URL Variable (using RFC3986 standards to be full Unicode compliant).
	 * @hint It is like the Smart::escape_url() ; can be used as a shortcut to the encodeURIComponent() to provide a standard into Smart.Framework/JS.
	 *
	 * @memberof smartJ$Utils
	 * @method escape_url
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		The URL variable value to be escaped
	 * @return 	{String} 				The escaped URL variable
	 */
	const escape_url = (str) => { // ES6
		//-- format string
		str = String((str == undefined) ? '' : str); // force string, test undefined is also for null
		if(str == '') {
			return '';
		} //end if
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
	}; //END
	_C$.escape_url = escape_url; // export

	/**
	 * Replace new lines \ r \ n ; \ n with the <br> html tag.
	 * @hint It is compatible with the PHP nl2br() method.
	 *
	 * @memberof smartJ$Utils
	 * @method nl2br
	 * @static
	 * @arrow
	 *
	 * @param {String} str The string to be processed
	 * @return {String} The processed string with <br> html tags if new lines were detected
	 */
	const nl2br = (str) => { // ES6
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.replace(/\r\n/g, /\n/).replace(/\r/g, /\n/).replace(/\n/g, '<br>')); // fix to return empty string instead of null
		//--
	}; //END
	_C$.nl2br = nl2br; // export

	/*
	 * Strip HTML tags from a string
	 *
	 * @memberof smartJ$Utils
	 * @method stripTags
	 * @static
	 *
	 * @param 	{String} 	html 			The HTML code to be processed
	 * @return 	{String} 					The text
	 */
	const stripTags = function(html) {
		//--
		const _m$ = 'stripTags';
		//--
		html = String((html == undefined) ? '' : html); // force string, test undefined is also for null
		if(html == '') {
			return '';
		} //end if
		let text = '';
		try {
			const doc = new DOMParser().parseFromString(html, 'text/html');
			text = doc.body.textContent || '';
		} catch(err) {
			text = '';
			_p$.error(_N$, _m$, err);
		} //end try catch
		return String(text);
	};
	_C$.stripTags = stripTags; // export

	/**
	 * Encodes an ISO-8859-1 string to UTF-8
	 *
	 * @memberof smartJ$Utils
	 * @method utf8Enc
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to be processed
	 * @return 	{String} 					The processed string
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
			utftext = encodeURIComponent(str).replace(/%([0-9A-Fa-f]{2})/g,
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
	_C$.utf8Enc = utf8Enc; // export

	/**
	 * Decodes an UTF-8 string to ISO-8859-1
	 *
	 * @memberof smartJ$Utils
	 * @method utf8Dec
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	utftext 		The string to be processed
	 * @return 	{String} 					The processed string
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
	_C$.utf8Dec = utf8Dec; // export

	/**
	 * De-Accent a latin-based Unicode string
	 * Will convert all accented characters in UTF-8 / ISO-8859-* with their unnaccented versions into ISO-8859-1
	 * @hint It is like PHP SmartUnicode deaccent str
	 *
	 * @memberof smartJ$Utils
	 * @method deaccent_str
	 * @static
	 *
	 * @param 	{String} 	strIn 		The string to be de-accented
	 * @return 	{String} 				The de-accented string
	 */
	const deaccent_str = function(strIn) { // ES6
		//--
		strIn = String((strIn == undefined) ? '' : strIn);
		if(strIn == '') {
			return '';
		} //end if
		//-- deaccent strings c.176x2 (v.170305), pgsql
		const data_accented 	= 'áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝšȘŞŠŚŜŠțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ';
		const data_deaccented 	= 'aaaaaaaaAAAAAAAAccccCCCCdDeeeeeeeeeEEEEEEEEEggGGhhHHiiiiiiiiiiIIIIIIIIIIjjJJkKllllLLLLnnnnNNNNoooooooooOOOOOOOOOOrrrRRRssssssSSSSSStttTTTuuuuuuuuuuUUUUUUUUUUwWyyyyyYYYYYzzzZZZ';
		//--
		strIn = strIn.split('');
		let len = strIn.length;
		let strOut = new Array();
		//--
		let theIdxFound = -1;
		for(let y=0; y<len; y++) {
			theIdxFound = data_accented.indexOf(strIn[y]);
			if(theIdxFound !== -1) {
				strOut[y] = data_deaccented.substring(theIdxFound, theIdxFound + 1);
			} else {
				strOut[y] = strIn[y];
			} //end if else
		} //end for
		//--
		strOut = strOut.join('');
		//--
		return String(strOut);
		//--
	}; //END
	_C$.deaccent_str = deaccent_str; // export

	/**
	 * Convert data into hexadecimal representation.
	 * @hint It is compatible with PHP bin2hex() method.
	 *
	 * @memberof smartJ$Utils
	 * @method bin2hex
	 * @static
	 *
	 * @param 	{String} 	s 			The string to be processed
	 * @param 	{Boolean} 	isBinary 	Encoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @return 	{String} 				The processed string
	 */
	const bin2hex = function(s, isBinary=false) { // ES6
		//--
		s = String((s == undefined) ? '' : s);
		if(s == '') {
			return '';
		} //end if
		//--
		if(isBinary !== true) { // binary content must not be re-encoded to UTF-8
			s = String(utf8Enc(s)); // force string and make it unicode safe
		} //end if
		//--
		let hex = '';
		let i, l, n;
		for(i=0, l=s.length; i<l; i++) {
			n = s.charCodeAt(i).toString(16);
			hex += n.length < 2 ? '0' + n : n;
		} //end for
		//--
		return String(hex).toLowerCase(); // fix to return empty string instead of null
		//--
	}; //END
	_C$.bin2hex = bin2hex; // export

	/**
	 * Decodes a hexadecimally encoded string.
	 * @hint It is compatible with PHP hex2bin() method.
	 *
	 * @memberof smartJ$Utils
	 * @method hex2bin
	 * @static
	 *
	 * @param 	{String} 	hex 		The string to be processed
	 * @param 	{Boolean} 	isBinary 	Decoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @return 	{String} 				The processed string
	 */
	const hex2bin = function(hex, isBinary=false) { // ES6
		//--
		hex = String((hex == undefined) ? '' : hex);
		hex = String(stringTrim(hex)).toLowerCase(); // force string and trim to avoid surprises ...
		if(hex == '') {
			return '';
		} //end if
		//--
		let bytes = [], str;
		//--
		for(let i=0; i< hex.length-1; i+=2) {
			bytes.push(parseInt(hex.substring(i, i+2), 16)); // must be parse int here
		} //end for
		//-- fix to return empty string instead of null
		if(isBinary !== true) { // binary content must not be re-decoded as UTF-8
			return String(utf8Dec(String.fromCharCode.apply(String, bytes)));
		} else {
			return String.fromCharCode.apply(String, bytes);
		} //end if else
		//--
	}; //END
	_C$.hex2bin = hex2bin; // export

	/**
	 * Encode a string to Base64, using browser native implementation: btoa()
	 * Supports also UTF-8
	 *
	 * @memberof smartJ$Utils
	 * @method b64Enc
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param 	{String} 	str 		The plain string (to be encoded)
	 * @param 	{Boolean} 	isBinary 	Encoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @return 	{String} 				the Base64 encoded string or empty string if errors
	 */
	const b64Enc = function(str, isBinary=false) { // ES6
		//--
		const _m$ = 'b64Enc';
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		if(isBinary !== true) {
			str = utf8Enc(str);
		} //end if
		//--
		try {
			str = btoa(str);
		} catch(err) {
			str = '';
			_p$.error(_N$, _m$, err);
		} //end try catch
		//--
		return String(str || '');
		//--
	}; //END
	_C$.b64Enc = b64Enc; // export

	/**
	 * Decode a string from Base64
	 * Supports also UTF-8
	 *
	 * @memberof smartJ$Utils
	 * @method b64Dec
	 * @static
	 *
	 * @throws console.error
	 *
	 * @param 	{String} 	str 		The B64 encoded string
	 * @param 	{Boolean} 	isBinary 	Decoding character set mode ; default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 * @return 	{String} 				the plain (B64 decoded) string or empty string if errors
	 */
	const b64Dec = function(str, isBinary=false) { // ES6
		//--
		const _m$ = 'b64Dec';
		//--
		str = stringPureVal(str, true); // cast to string, trim
		if(str == '') {
			return '';
		} //end if
		//--
		const l = str.length % 4;
		if(l > 0) {
			if(l == 1) { // {{{SYNC-B64-WRONG-PAD-3}}} ; it cannot end with 3 "=" signs
				_p$.warn(_N$, _m$, 'Invalid B64 Padding Length, more than 2, L =', 4 - l);
				return '';
			} //end if
			str += '='.repeat(4 - l); // fix missing padding
		} //end if
		//--
		try {
			str = atob(str);
		} catch(err) {
			str = '';
			_p$.error(_N$, _m$, err);
		} //end try catch
		//--
		if(isBinary !== true) {
			str = utf8Dec(str);
		} //end if
		//--
		return String(str || '');
		//--
	}; //END
	_C$.b64Dec = b64Dec; // export

	/*
	 * Reverse a string
	 *
	 * @memberof smartJ$Utils
	 * @method strRev
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 			The reversed string
	 */
	const strRev = (str) => {
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		return str.split('').reverse().join(''); // explode to array, reverse, implode
		//--
	};
	_C$.strRev = strRev; // export

	/*
	 * Perform the rot13 transform on a string ; PHP compatible
	 *
	 * @memberof smartJ$Utils
	 * @method strRot13
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 			The ROT13 version of the given string
	 */
	const strRot13 = (str) => {
		//--
		str = String((str == undefined) ? '' : str);
		if(str == '') {
			return '';
		} //end if
		//--
		return String(str.replace(/[a-z]/gi, (s) => {
			return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13)); // github.com/locutusjs/locutus/blob/master/src/php/strings/str_rot13.js
		}));
		//--
	}; //END
	_C$.strRot13 = strRot13; // export

	/*
	 * Perform the rot13 transform on a reversed string
	 *
	 * @memberof smartJ$Utils
	 * @method strRRot13
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 			The ROT13 version of the given reversed string
	 */
	const strRRot13 = (s) => String(strRev(strRot13(s)));
	_C$.strRRot13 = strRRot13; // export

	/**
	 * Generate a base36 10-chars length UUID
	 *
	 * @memberof smartJ$Utils
	 * @method uuid
	 * @static
	 *
	 * @param 	{Enum} 		mode 	Operation Mode ; default is 'ser' ; 'ser': serial (time based) ; 'str': random string ; 'num': random numeric
	 * @return 	{String} 			A unique UUID, 10 characters, base36 ; Ex: 1A2B3C4D5E
	 */
	let tseed = (new Date()).valueOf(); // time based uuid seed, must be global in the class as it is re-seeded each time UUID is generated
	const uuid = function(mode='seq') { // ES6
		//--
		mode = String(mode).toLowerCase();
		switch(mode) {
			case 'num': // random, numeric
				break;
			case 'str': // random, string
				break;
			case 'seq': // serial, time based
			default:
				mode = 'seq';
		} //end switch
		//--
		let uid = '';
		if(mode === 'seq') {
			tseed++;
			uid = String(tseed.toString(36));
		} else { // num, str
			uid = Math.floor(Math.random() * Number.MAX_SAFE_INTEGER); // must remain numeric not string to apply later B36
			if(mode === 'str') {
				uid = String(uid.toString(36));
			} else { // num
				uid = String(uid);
			} //end if
		} //end if
		uid = uid.toUpperCase().substring(uid.length - 10); // if longer than 10 chars, take the last 10 chars only
		//--
		if(uid.length < 10) {
			for(let i=0; i<uid.length; i++) { // left pad with zeroes
				if(uid.length < 10) {
					uid = String('0' + uid);
				} else {
					break;
				} //end if else
			} //end for
		} //end if
		//--
		return String(uid);
		//--
	}; //END
	_C$.uuid = uuid; // export

	/*
	 * Add an Element to a List
	 *
	 * @private internal development only
	 *
	 * @memberof smartJ$Utils
	 * @method addToList
	 * @static
	 *
	 * @param 	{String} 	newVal 		The new val to add to the List
	 * @param 	{String} 	textList 	The string List to add newVal at
	 * @param 	{String} 	splitBy 	The string separator, any of: , ;
	 * @return 	{String} 				The processed string as List separed by separator
	 */
	const addToList = function(newVal, textList, splitBy) { // ES6
		//--
		newVal = String((newVal == undefined) ? '' : stringTrim(newVal));
		if(newVal == '') {
			return '';
		} //end if
		//--
		textList = String((textList == undefined) ? '' : textList);
		//--
		let terms = [];
		//--
		splitBy = String((splitBy == undefined) ? '' : splitBy);
		switch(splitBy) {
			case ',':
				terms = stringSplitbyComma(textList); // Array
				break;
			case ';':
				terms = stringSplitbySemicolon(textList); // Array
				break;
			default:
				_p$.error(_N$, 'ERR: addToList: Invalid splitBy separator. Must be any of [,;] and is:', splitBy);
				return '';
		} //end switch
		//--
		let found = false;
		if(terms.length > 0) {
			terms.pop(); // remove the current input
			for(let i=0; i<terms.length; i++) {
				if(terms[i] == newVal) {
					found = true;
					break;
				} //end if
			} //end for
		} //end if
		if(!found) {
			terms.push(newVal); // add the selected item
		} //end if
		terms.push(''); // add placeholder to get the comma-and-space at the end
		//--
		return String(terms.join(splitBy + ' '));
		//--
	}; //END
	_C$.addToList = addToList; // export, hidden

	/**
	 * Sort a stack (array / object / property) using String Sort algorithm
	 *
	 * @memberof smartJ$Utils
	 * @method textSort
	 * @static
	 *
	 * @param 	{Mixed} 	property 		The stack to be sorted
	 * @param 	{Boolean} 	useLocale 		*Optional* default is FALSE ; if TRUE will try sort using locales and if fail will fallback on raw string comparison ; if FALSE will use the default string comparison
	 * @return 	{Method} 					The stack sort method
	 */
	const textSort = function(property, useLocale=false) { // ES6
		//--
		return (a, b) => {
			//--
			a[property] = String(a[property]);
			b[property] = String(b[property]);
			//--
			let comparer = 0;
			let localeFailed = false;
			if(useLocale === true) {
				try { // a better compare using locales, if n/a fallback to non-locale compare
					comparer = a[property].localeCompare(b[property]);
					if(comparer < 0) {
						comparer = -1;
					} else if(comparer > 0) {
						comparer = 1;
					} else {
						comparer = 0;
					} //end if else
				} catch(e) { // non-locale compare
					localeFailed = true;
				} //end try catch
			} //end if
			if((useLocale !== true) || (localeFailed === true)) {
				comparer = ((a[property] < b[property]) ? -1 : (a[property] > b[property])) ? 1 : 0;
			} //end if
			//--
			return comparer; // mixed
			//--
		} //end
		//--
	}; //END
	_C$.textSort = textSort; // export

	/**
	 * Sort a stack (array / object / property) using Numeric Sort algorithm
	 *
	 * @memberof smartJ$Utils
	 * @method numericSort
	 * @static
	 * @arrow
	 *
	 * @param 	{Mixed} 	property 		The stack to be sorted
	 * @return 	{Method} 					The stack sort method
	 */
	const numericSort = (property) => { // ES6
		//--
		return (a, b) => {
			//--
			if((!isFiniteNumber(a[property])) || (!isFiniteNumber(b[property]))) {
				return 0;
			} //end if
			//--
			if(a[property] > b[property]) {
				return 1;
			} else if(a[property] < b[property]) {
				return -1;
			} else {
				return 0;
			} //end if else
			//--
		} //end
		//--
	}; //END
	_C$.numericSort = numericSort; // export

	/**
	 * Add URL Suffix (to a standard RFC3986 URL) as: script.php?a=b&C=D&e=%20d
	 *
	 * @memberof smartJ$Utils
	 * @method url_add_suffix
	 * @static
	 *
	 * @param 	{String} 	url 			The base URL to use as prefix like: script.php or script.php?a=b&c=d or empty
	 * @param 	{String} 	suffix 			A RFC3986 URL segment like: a=b or E=%20d (without ? or not starting with & as they will be detected if need append ? or &; variable values must be encoded using escape_url() RFC3986)
	 * @return 	{String} 					The prepared URL in the standard RFC3986 format (all values are escaped using escape_url() to be Unicode full compliant
	 */
	const url_add_suffix = function(url, suffix) { // ES6
		//--
		url = stringPureVal(url, true); // cast to string, trim
		//--
		suffix = stringPureVal(suffix, true); // cast to string, trim
		if(stringStartsWith(suffix, '?') || stringStartsWith(suffix, '&')) {
			suffix = stringPureVal(suffix.substring(1), true); // cast to string, trim
		} //end if
		if(suffix == '') {
			return String(url);
		} //end if
		//--
		let separator = '';
		if(stringContains(url, '?')) {
			separator = '&';
		} else {
			separator = '?';
		} //end if else
		//--
		return String(url + separator + suffix);
		//--
	}; //END
	_C$.url_add_suffix = url_add_suffix; // export

	/**
	 * Creates a Slug (URL safe slug) from a string
	 *
	 * @memberof smartJ$Utils
	 * @method create_slug
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to be processed
	 * @param 	{Boolean} 	lowercase 		*OPTIONAL* If TRUE will return the slug with enforced lowercase characters ; DEFAULT is FALSE
	 * @param 	{Integer} 	maxlen			*OPTIONAL* If a positive value greater than zero is supplied here the slug max length will be constrained to the value
	 * @return 	{String} 					The slug which will contain only: a-z 0-9 _ - (A-Z will be converted to a-z if lowercase is enforced)
	 */
	const create_slug = (str, lowercase=false, maxlen=0) => { // ES6
		//--
		str = stringPureVal(str, true); // cast to string, trim
		if(str == '') {
			return '';
		} //end if
		//--
		str = String(deaccent_str(stringTrim(str)));
		str = str.replace(/[^a-zA-Z0-9_\-]/g, '-');
		str = str.replace(/[\-]+/g, '-'); // suppress multiple -
		str = str.replace(/^[\-]+/, '').replace(/[\-]+$/, '');
		str = String(stringTrim(str));
		//--
		if(lowercase === true) {
			str = str.toLowerCase();
		} //end if
		//--
		maxlen = format_number_int(maxlen, false);
		if(maxlen > 0) {
			str = str.substring(0, maxlen);
			str = str.replace(/(\-)+$/, '');
		} //end if
		//--
		return String(str);
		//--
	}; //END
	_C$.create_slug = create_slug; // export

	/**
	 * Creates a compliant HTML-ID (HTML ID used for HTML elements) from a string
	 *
	 * @memberof smartJ$Utils
	 * @method create_htmid
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to be processed
	 * @return 	{String} 					The HTML-ID which will contain only: a-z A-Z 0-9 _ -
	 */
	const create_htmid = (str) => { // ES6
		//--
		str = stringPureVal(str, true); // cast to string, trim
		if(str == '') {
			return '';
		} //end if
		//--
		str = str.replace(/[^a-zA-Z0-9_\-]/g, '');
		str = stringTrim(str);
		//--
		return String(str);
		//--
	}; //END
	_C$.create_htmid = create_htmid; // export

	/**
	 * Creates a compliant Js-Var (JavaScript Variable Name) from a string
	 *
	 * @memberof smartJ$Utils
	 * @method create_jsvar
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 			The string to be processed
	 * @return 	{String} 					The Js-Var which will contain only: a-z A-Z 0-9 _ $
	 */
	const create_jsvar = (str) => { // ES6
		//--
		str = stringPureVal(str, true); // cast to string, trim
		if(str == '') {
			return '';
		} //end if
		//--
		str = str.replace(/[^a-zA-Z0-9_\$]/g, '');
		str = String(stringTrim(str));
		//--
		return String(str);
		//--
	}; //END
	_C$.create_jsvar = create_jsvar; // export

	/*
	 * Revert from Prepare a value or a template by escaping syntax
	 *
	 * @memberof smartJ$Utils
	 * @method revertNosyntaxContentMarkersTpl
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	const revertNosyntaxContentMarkersTpl = (tpl) => { // ES6
		//-- IMPORTANT: all the MarkerTPL Syntax has been written as '' + '' or \% in regex to allow this Js to be embedded in HTML TPLs without interfering with that syntax
		if(typeof(tpl) !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//-- keep as expr to allow embedding this js file in other HTML TPLs
		tpl = stringReplaceAll('［' + '###', '[' + '###', tpl);
		tpl = stringReplaceAll('###' + '］', '###' + ']', tpl);
		tpl = stringReplaceAll('［' + '%%%', '[' + '%%%', tpl);
		tpl = stringReplaceAll('%%%' + '］', '%%%' + ']', tpl);
		tpl = stringReplaceAll('［' + '@@@', '[' + '@@@', tpl);
		tpl = stringReplaceAll('@@@' + '］', '@@@' + ']', tpl);
		tpl = stringReplaceAll('［' + ':::', '[' + ':::', tpl);
		tpl = stringReplaceAll(':::' + '］', ':::' + ']', tpl);
		//--
		return String(tpl);
		//--
	}; //END
	_C$.revertNosyntaxContentMarkersTpl = revertNosyntaxContentMarkersTpl; // export, hidden

	/*
	 * Prepare a value or a template by escaping syntax
	 *
	 * @memberof smartJ$Utils
	 * @method prepareNosyntaxContentMarkersTpl
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	const prepareNosyntaxContentMarkersTpl = (tpl) => { // ES6
		//-- IMPORTANT: all the MarkerTPL Syntax has been written as '' + '' or \% in regex to allow this Js to be embedded in HTML TPLs without interfering with that syntax
		if(typeof(tpl) !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//--
		tpl = stringReplaceAll('[' + '###', '［' + '###', tpl);
		tpl = stringReplaceAll('###' + ']', '###' + '］', tpl);
		tpl = stringReplaceAll('[' + '%%%', '［' + '%%%', tpl);
		tpl = stringReplaceAll('%%%' + ']', '%%%' + '］', tpl);
		tpl = stringReplaceAll('[' + '@@@', '［' + '@@@', tpl);
		tpl = stringReplaceAll('@@@' + ']', '@@@' + '］', tpl);
		tpl = stringReplaceAll('[' + ':::', '［' + ':::', tpl);
		tpl = stringReplaceAll(':::' + ']', ':::' + '］', tpl);
		//--
		return String(tpl);
		//--
	}; //END
	_C$.prepareNosyntaxContentMarkersTpl = prepareNosyntaxContentMarkersTpl; // export, hidden

	/*
	 * Prepare a HTML template for display in no-conflict mode: no syntax / markers will be parsed
	 * To keep the markers and syntax as-is but avoiding conflicting with real markers / syntax it will encode as HTML Entities the following syntax patterns: [ ] # % @
	 * @hint !!! It is intended for very special usage ... !!!
	 *
	 * @memberof smartJ$Utils
	 * @method prepareNosyntaxHtmlMarkersTpl
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	template 	The string template with markers
	 * @return 	{String} 				The processed string
	 */
	const prepareNosyntaxHtmlMarkersTpl = (tpl) => { // ES6
		//-- IMPORTANT: all the MarkerTPL Syntax has been written as '' + '' or \% in regex to allow this Js to be embedded in HTML TPLs without interfering with that syntax
		if(typeof(tpl) !== 'string') {
			return '';
		} //end if
		//--
		tpl = String(tpl);
		//--
		tpl = stringReplaceAll('[' + '###', '&lbrack;&num;&num;&num;', tpl);
		tpl = stringReplaceAll('###' + ']', '&num;&num;&num;&rbrack;', tpl);
		tpl = stringReplaceAll('[' + '%%%', '&lbrack;&percnt;&percnt;&percnt;', tpl);
		tpl = stringReplaceAll('%%%' + ']', '&percnt;&percnt;&percnt;&rbrack;', tpl);
		tpl = stringReplaceAll('[' + '@@@', '&lbrack;&commat;&commat;&commat;', tpl);
		tpl = stringReplaceAll('@@@' + ']', '&commat;&commat;&commat;&rbrack;', tpl);
		tpl = stringReplaceAll('[' + ':::', '&lbrack;&colon;&colon;&colon;', tpl);
		tpl = stringReplaceAll(':::' + ']', '&colon;&colon;&colon;&rbrack;', tpl);
		//--
		tpl = stringReplaceAll('［' + '###', '&lbrack;&num;&num;&num;', tpl);
		tpl = stringReplaceAll('###' + '］', '&num;&num;&num;&rbrack;', tpl);
		tpl = stringReplaceAll('［' + '%%%', '&lbrack;&percnt;&percnt;&percnt;', tpl);
		tpl = stringReplaceAll('%%%' + '］', '&percnt;&percnt;&percnt;&rbrack;', tpl);
		tpl = stringReplaceAll('［' + '@@@', '&lbrack;&commat;&commat;&commat;', tpl);
		tpl = stringReplaceAll('@@@' + '］', '&commat;&commat;&commat;&rbrack;', tpl);
		tpl = stringReplaceAll('［' + ':::', '&lbrack;&colon;&colon;&colon;', tpl);
		tpl = stringReplaceAll(':::' + '］', '&colon;&colon;&colon;&rbrack;', tpl);
		//--
		return String(tpl);
		//--
	}; //END
	_C$.prepareNosyntaxHtmlMarkersTpl = prepareNosyntaxHtmlMarkersTpl; // export, hidden

	/**
	 * Render Simple Marker-TPL Template + Comments + Specials (only markers replacements with escaping or processing syntax and support for comments and special replacements: SPACE, TAB, R, N ; no support for IF / LOOP / INCLUDE syntax since the js regex is too simplistic and it can be implemented using real js code)
	 * It is compatible just with the REPLACE MARKERS of Smart.Framework PHP server-side Marker-TPL Templating for substitutions on client-side, except the extended syntax as IF/LOOP/INCLUDE.
	 * @hint To be used together with the server-side Marker-TPL templating (ex; PHP), to avoid the server-side markers to be rendered ; ex: `［###MARKER###］` will need the template to be escape_url+escape_js ; for this purpose the 3rd param can be set to true: isEncoded = TRUE
	 *
	 * @example
	 * // sample client side MarkersTPL rendering
	 * // HINTS on using |js escaping:
	 * // - never use a marker inside javascript backticks (`) ; ex: don't do this: const test = `［###TEST|js###］`; // it will raise js code compile errors if a string contains a backtick (`) ... this is because the backticks are not escaped by json escaping ...
	 * // - when using TPL markers inside javascript single quotes (') or double quotes (") just use the escaping: |js ; ex: const test = '［###TEST|js###］'; const test2 = "［###TEST|js###］";
	 * // - if the context of javascript is using escaped single quotes ('\'\'') or escaped double quotes ("\"\""), if possible use ("''") or ('""') as ("'［###TEST|js###］'") or ('"［###TEST|js###］"') is OK ; but ('\'［###TEST|js###］\'') or ("\"［###TEST|js###］\"") IS NOT OK because the js code will compile but when the js will evaluate the context will raise an error as the strings will terminate premature if they contain a single or double quote ; also using backticks like (`'［###TEST|js###］'`) or (`"［###TEST|js###］"`) is wrong, it is explained above ...
	 * // - if the javascript context is using escaped single quotes, have to use: ('\'［###TEST|js|js###］\''), which is OK
	 * // - if the javascript context is using escaped double quotes, have to use: ("\"［###TEST|js|js###］"') + "\""), which is OK
	 * // see below a real life situation when have to use this:
	 * const question = '［###QUESTION|js###］'; // OK
	 * console.log('Question:', question);
	 * const tpl = '<div onclick="let Question=\'［###QUESTION|js|js###］\'; alert(Question);">［###TITLE|html|js###］</div>'; // the TPL ; OK because is using double |js escaping with single quotes escaping to avoid js errors when js is evaluating the string ...
	 * const tpl2 = '<div onclick="alert(question);">［###TITLE|html|js###］</div>'; // alternate TPL ; OK, will use the global variable / constant question so making like this avoid adding escaped single or double quotes
	 * // it is a common situation when building html syntax with javascript to have combined single quotes (javascript) with double quotes (html), so adding in a html attribute another javascript have no other solution than using again single quotes but escaped like \' and if a marker is placed, must add like: let html = '<button onClick="let test = \'［###TEST|js|js###］\';">Test</button>'; // notice the double escaping
	 * let html = smartJ$Utils.renderMarkersTpl( // render TPL
	 * 		tpl,
	 * 		{
	 * 			'TITLE': 'A Title',
	 * 			'QUESTION': 'A Question'
	 * 		}
	 * jQuery('body').append(html); // display TPL
	 *
	 * @memberof smartJ$Utils
	 * @method renderMarkersTpl
	 * @static
	 *
	 * @param 	{String} 	template 		The string template with markers
	 * @param 	{ArrayObj} 	arrobj 			The Object-Array with marker replacements as { 'MAR.KER_1' => 'Value 1', 'MARKER-2' => 'Value 2', ... }
	 * @param 	{Boolean} 	isEncoded 		If TRUE will do a decoding over template string (apply if TPL is sent as encoded from server-side or directly in html page)
	 * @param 	{Boolean} 	revertSyntax 	If TRUE will do a revertNosyntaxContentMarkersTpl() over template string (apply if TPLs is sent with syntax escaped)
	 * @return 	{String} 					The processed string
	 */
	const renderMarkersTpl = function(template, arrobj, isEncoded=false, revertSyntax=false) { // ES6
		//-- syntax: r.20250126 ; IMPORTANT: all the MarkerTPL Syntax has been written as '' + '' or \% in regex to allow this Js to be embedded in HTML TPLs without interfering with that syntax
		if((typeof(template) === 'string') && (typeof(arrobj) === 'object')) {
			//--
			if(isEncoded === true) {
				template = String(decodeURIComponent(template));
			} //end if
			if(revertSyntax === true) {
				template = String(revertNosyntaxContentMarkersTpl(template));
			} //end if
			//--
			template = stringTrim(template);
			//-- remove comments: javascript regex miss the regex flags: s = single line: Dot matches newline characters ; U = Ungreedy: The match becomes lazy by default ; Now a ? following a quantifier makes it greedy
			// because missing the single line dot match and ungreedy is almost impossible to solve it with a regex in an optimum way, thus we use the trick :-)
			// because missing the /s flag, the extra \S have to be added to the \s to match new lines and the (.*) have become ([\s\S^]*)
			// because missing the /U flag, missing ungreedy, we need to split/join to solve it
			if(stringContains(template, '[' + '%%%COMMENT%%%' + ']') && stringContains(template, '[' + '%%%/COMMENT%%%' + ']')) {
				let arr_comments = [];
				arr_comments = template.split('[' + '%%%COMMENT%%%' + ']');
				for(let i=0; i<arr_comments.length; i++) {
					if(stringContains(arr_comments[i], '[' + '%%%/COMMENT%%%' + ']')) {
						arr_comments[i] = '[' + '%%%COMMENT%%%' + ']' + arr_comments[i];
						arr_comments[i] = arr_comments[i].replace(/[\s\S]?\[\%\%\%COMMENT\%\%\%\]([\s\S]*?)\[\%\%\%\/COMMENT\%\%\%\][\s\S]?/g, '');
					} //end if
				} //end for
				template = stringTrim(arr_comments.join(''));
				arr_comments = null;
			} //end if
			//-- replace markers
			if(template != '') {
				//--
				const regexp = /\[\#\#\#([A-Z0-9_\-\.]+)((\|[a-z0-9]+)*)\#\#\#\]/g; // {{{SYNC-REGEX-MARKER-TEMPLATES}}}
				//--
				let markers = stringRegexMatchAll(template, regexp);
				//--
				if(markers.length) {
					//--
					let marker, escaping;
					let tmp_marker_val, tmp_marker_id, tmp_marker_key, tmp_marker_esc, tmp_marker_arr_esc;
					//--
					let xnum, xlen, jsonObj;
					//--
					for(let i=0; i<markers.length; i++) {
						//--
						marker = markers[i]; // expects array
						//--
						if(marker.length) {
							//--
							tmp_marker_val 		= '';									// just initialize
							tmp_marker_id 		= marker[0] ? String(marker[0]) : ''; 	// ［###THE-MARKER|escapings...###］
							tmp_marker_key 		= marker[1] ? String(marker[1]) : ''; 	// THE-MARKER
							tmp_marker_esc 		= marker[2] ? String(marker[2]) : ''; 	// |escaping1(|escaping2...|escaping99)
							tmp_marker_arr_esc  = []; 									// just initialize
							//--
							if((tmp_marker_id != null) && (tmp_marker_id != '') && (tmp_marker_key != null) && (tmp_marker_key != '') && stringContains(template, tmp_marker_id)) { // check if exists because it does replaceAll on a cycle so another cycle can run without scope !
								//--
								if(tmp_marker_key in arrobj) {
									//-- prepare val from input array
									tmp_marker_val = arrobj[tmp_marker_key] ? arrobj[tmp_marker_key] : '';
									tmp_marker_val = String(tmp_marker_val);
									//-- protect against cascade recursion of syntax by escaping all the syntax in value
									tmp_marker_val = String(prepareNosyntaxContentMarkersTpl(tmp_marker_val));
									//--
									if(tmp_marker_esc) { // if non-empty before removing leading | ; else no escapings
										//--
										if(stringStartsWith(tmp_marker_esc, '|')) { // if contains leading |
											tmp_marker_esc = tmp_marker_esc.substring(1); // remove leading |
										} //end if
										//--
										if(tmp_marker_esc) { // if non-empty after removing leading | ; else no escapings
											//--
											tmp_marker_arr_esc = tmp_marker_esc.split(/\|/); // Array, split by |
											//--
											if(tmp_marker_arr_esc.length) {
												//--
												for(let j=0; j<tmp_marker_arr_esc.length; j++) {
													//--
													escaping = String('|' + String(tmp_marker_arr_esc[j]));
													//--
													if(escaping == '|bool') { // Boolean
														if(tmp_marker_val) {
															tmp_marker_val = 'true';
														} else {
															tmp_marker_val = 'false';
														} //end if else
													} else if(escaping == '|int') { // Integer
														tmp_marker_val = String(format_number_int(tmp_marker_val));
													} else if(escaping.substring(0, 4) == '|dec') { // Dec (1..4)
														xnum = format_number_int(escaping.substring(4));
														if(xnum < 1) {
															xnum = 1;
														} //end if
														if(xnum > 4) {
															xnum = 4;
														} //end if
														tmp_marker_val = String(format_number_dec(tmp_marker_val, xnum, true, false)); // allow negatives, do not discard trailing zeroes
														xnum = null;
													} else if(escaping == '|num') { // Number (Float / Decimal / Integer)
														tmp_marker_val = String(format_number_float(tmp_marker_val));
													} else if(escaping == '|idtxt') { // id_txt: Id-Txt
														tmp_marker_val = String(stringReplaceAll('_', '-', tmp_marker_val));
														tmp_marker_val = String(stringUcWords(tmp_marker_val));
													} else if(escaping == '|slug') { // Slug: a-zA-Z0-9_- / - / -- : -
														tmp_marker_val = String(create_slug(tmp_marker_val, false)); // do not apply strtolower as it can be later combined with |lower flag
													} else if(escaping == '|htmid') { // HTML-ID: a-zA-Z0-9_-
														tmp_marker_val = String(create_htmid(tmp_marker_val));
													} else if(escaping == '|jsvar') { // JS-Variable: a-zA-Z0-9_$
														tmp_marker_val = String(create_jsvar(tmp_marker_val));
													} else if((escaping.substring(0, 7) == '|substr') || (escaping.substring(0, 7) == '|subtxt')) { // Sub(String|Text) (0,num)
														xnum = format_number_int(escaping.substring(7));
														if(xnum < 1) {
															xnum = 1;
														} //end if
														if(xnum > 65535) {
															xnum = 65535;
														} //end if
														if(xnum >= 1 && xnum <= 65535) {
															xlen = tmp_marker_val.length;
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
															} else { // '|substr'
																if(xlen > xnum) {
																	tmp_marker_val = tmp_marker_val.substring(0, xnum);
																} //end if
															} //end if
															xlen = null;
														} //end if
														xnum = null;
														tmp_marker_val = String(tmp_marker_val);
													} else if(escaping == '|lower') { // apply lowercase
														tmp_marker_val = String(tmp_marker_val).toLowerCase();
													} else if(escaping == '|upper') { // apply uppercase
														tmp_marker_val = String(tmp_marker_val).toUpperCase();
													} else if(escaping == '|ucfirst') { // apply uppercase first character
														tmp_marker_val = String(stringUcFirst(tmp_marker_val));
													} else if(escaping == '|ucwords') { // apply uppercase on each word
														tmp_marker_val = String(stringUcWords(tmp_marker_val));
													} else if(escaping == '|trim') { // apply trim
														tmp_marker_val = String(stringTrim(tmp_marker_val));
													} else if(escaping == '|url') { // escape URL
														tmp_marker_val = String(escape_url(tmp_marker_val));
													} else if(escaping == '|json') { // format as Json Data ; expects pure JSON !!!
														jsonObj = null;
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
														// the JSON string will not be 100% like the one produced via PHP with HTML-Safe arguments but at least have the minimum escapes to avoid conflicting HTML tags
														tmp_marker_val = String(stringTrim(tmp_marker_val));
														if(tmp_marker_val == '') {
															tmp_marker_val = 'null'; // ensure a minimal json as null for empty string if no expr !
														} //end if
													} else if(escaping == '|js') { // Escape JS
														tmp_marker_val = String(escape_js(tmp_marker_val));
													} else if(escaping == '|html') { // Escape HTML
														tmp_marker_val = String(escape_html(tmp_marker_val));
													} else if(escaping == '|css') { // Escape CSS
														tmp_marker_val = String(escape_css(tmp_marker_val));
													} else if(escaping == '|nl2br') { // Format NL2BR
														tmp_marker_val = String(nl2br(tmp_marker_val));
													} else if(escaping == '|striptags') { // Apply Strip Tags
														tmp_marker_val = String(stripTags(tmp_marker_val));
													} else if(escaping == '|emptye') { // if empty, display [EMPTY]
														if(stringTrim(tmp_marker_val) == '') {
															tmp_marker_val = '[EMPTY]';
														} //end if
													} else if(escaping == '|emptyna') { // if empty, display [N/A]
														if(stringTrim(tmp_marker_val) == '') {
															tmp_marker_val = '[N/A]';
														} //end if
													} else if(escaping == '|smartlist') { // Apply SmartList Fix Replacements ; {{{SYNC-SMARTLIST-BRACKET-REPLACEMENTS}}}
														tmp_marker_val = String(stringReplaceAll('<', '‹', tmp_marker_val));
														tmp_marker_val = String(stringReplaceAll('>', '›', tmp_marker_val));
													} else if(escaping == '|syntaxhtml') { // fix back markers tpl escapings in html
														tmp_marker_val = String(prepareNosyntaxHtmlMarkersTpl(tmp_marker_val));
													} else if(escaping == '|hex') { // Apply Bin2Hex Encode
														tmp_marker_val = String(bin2hex(tmp_marker_val));
													} else if(escaping == '|b64') { // Apply Base64 Encode
														tmp_marker_val = String(b64Enc(tmp_marker_val));
													//--
													} else {
														_p$.warn(_N$, 'WARN: renderMarkersTpl: {### Invalid or Undefined Escaping for Marker: ' + escaping + ' - detected in Replacement Key: ' + tmp_marker_id + ' @ ' + tmp_marker_val + ' detected in Template: ^' + '\n' + template.substring(0, 512) + '$(0..512)... ###}');
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
									template = stringReplaceAll(tmp_marker_id, tmp_marker_val, template);
									//--
								} //end if
								//--
							} //end if
						} //end if
						//--
						marker = null;
						escaping = null;
						tmp_marker_val = null;
						tmp_marker_id = null;
						tmp_marker_key = null;
						tmp_marker_esc = null;
						tmp_marker_arr_esc = null;
						//--
					} //end for
					//--
				} //end if
				//--
				markers = null;
				//-- replace specials: Square-Brackets(L/R) R N TAB SPACE
				if(stringContains(template, '[' + '%%%|')) {
					template = stringReplaceAll('[' + '%%%|SB-L%%%'  + ']', '［', template);
					template = stringReplaceAll('[' + '%%%|SB-R%%%'  + ']', '］', template);
					template = stringReplaceAll('[' + '%%%|R%%%'     + ']', '\r', template);
					template = stringReplaceAll('[' + '%%%|N%%%'     + ']', '\n', template);
					template = stringReplaceAll('[' + '%%%|T%%%'     + ']', '\t', template);
					template = stringReplaceAll('[' + '%%%|SPACE%%%' + ']', ' ', template);
				} //end if
				//--
				if(stringContains(template, '[' + '###')) {
					_p$.warn(_N$, 'WARN: renderMarkersTpl:', stringRegexMatchAll(template, regexp), '{### Undefined Markers detected in Template:' + '\n' + template.substring(0, 512) + '$(0..512)... ###}');
				} //end if
				if(stringContains(template, '[' + '%%%')) {
					_p$.warn(_N$, 'WARN: renderMarkersTpl: {### Undefined Marker Syntax detected in Template: ^' + '\n' + template.substring(0, 512) + '$(0..512)... ###}');
				} //end if
				if(stringContains(template, '[' + '@@@')) {
					_p$.warn(_N$, 'WARN: renderMarkersTpl: {### Undefined Marker Sub-Templates detected in Template: ^' + '\n' + template.substring(0, 512) + '$(0..512)... ###}');
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			_p$.error(_N$, 'ERR: renderMarkersTpl: {### Invalid Marker-TPL Arguments ###}');
			template = '';
			//--
		} //end if
		//--
		return String(template); // fix to return empty string instead of null [OK]
		//--
	}; //END
	_C$.renderMarkersTpl = renderMarkersTpl; // export

	/*
	 * Converts a string to Byte Array
	 *
	 * @memberof smartJ$Utils
	 * @method strToByteArray
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	str 		A String to be converted
	 *
	 * @return 	{ByteArray} 			A Byte Array
	 */
	const strToByteArray = (str) => {
		str = stringPureVal(str); // do not trim
		let bytes = [];
		for(let i = 0; i < str.length; i++) {
			bytes.push(str.charCodeAt(i) & 255);
		} //end for
		return bytes;
	}; //END
	_C$.strToByteArray = strToByteArray; // export

	/*
	 * Test if a string is a valid Hex Color
	 * Ex: #FFFFFF
	 *
	 * @memberof smartJ$Utils
	 * @method isHexColor
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	hex 		A hex color, ex: #778866
	 *
	 * @return 	{Bool} 					TRUE if a valid hex color ; false of not
	 */
	const isHexColor = (hex) => {
		hex = stringPureVal(hex, true); // trim
		return !! ((hex.length == 7) && hex.match(/^\#([0-9a-f]{6})$/i));
	};
	_C$.isHexColor = isHexColor; // export

	/*
	 * Convert HEX Color to RGBA
	 *
	 * @memberof smartJ$Utils
	 * @method hex2rgba
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	hex 		A hex color, ex: #778866
	 * @param 	{Decimal} 	alpha 		A decimal value between 0 and 1 as opacity
	 *
	 * @return 	{Mixed} 				NULL or Object (PopUp Window Reference)
	 */
	const hex2rgba = (hex, alpha=1) => {
		const _m$ = 'hex2rgba';
		hex = stringPureVal(hex, true); // trim
		if((hex == '') || (!isHexColor(hex))) {
			_p$.warn(_N$, _m$, 'Invalid Hex Color:', hex);
			return '';
		} //end if
		if(alpha < 0) {
			alpha = 0;
		} else if(alpha > 1) {
			alpha = 1;
		} //end if else
		alpha = format_number_dec(alpha, 2, false, true); // disallow negatives ; discard trailing zeroes
		let rgba = '';
		try {
			const [r, g, b] = hex.match(/\w\w/g).map(x => parseInt(x, 16));
			rgba = `rgba(${r},${g},${b},${alpha})`;
		} catch(err) {
			rgba = '';
			_p$.warn(_N$, _m$, 'Conversion ERR', err);
		} //end try catch
		return String(rgba);
	};
	_C$.hex2rgba = hex2rgba; // export

}}; //END CLASS

smartJ$Utils.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$Utils = smartJ$Utils; // global export
} //end if

//==================================================================
//==================================================================

// #END
