
// [LIB - Smart.Framework / JS / DateUtils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: smartJ$Utils

//==================================================================
// The code is released under the BSD License. Copyright (c) unix-world.org
// The file contains portions of code from:
//	- https://github.com/joshduck/simple-day # A simple library for working with calendar days (YYYY-MM-DD) as plain old JavaScript objects.
//	- https://www.npmjs.com/package/date-offset @ http://howardhinnant.github.io/date_algorithms.html # A simple library for converting Gregorian dates to an integer offset.
//==================================================================

//================== [ES6]

/**
 * CLASS :: Smart DateUtils (ES6, Strict Mode)
 *
 * @package Sf.Javascript:Core
 *
 * @requires		smartJ$Utils
 *
 * @desc Date Utils class for Javascript
 * @author unix-world.org
 * @license BSD
 * @file date_utils.js
 * @version 20251216
 * @class smartJ$Date
 * @static
 * @frozen
 *
 * @example
 * let d = new Date();
 * console.log(JSON.stringify(d, null, 2));
 *
 * let dz = smartJ$Date.standardizeDate(d);
 * console.log(JSON.stringify(dz, null, 2));
 *
 * let ds = smartJ$Date.standardizeDate({ year: d.getFullYear(), month: d.getMonth()+1, day: d.getDate() });
 * console.log(JSON.stringify(ds, null, 2));
 *
 * let iso = smartJ$Date.getIsoDate(ds);
 * console.log(iso);
 *
 * let iso2 = smartJ$Date.getIsoDate(ds, true);
 * console.log(iso2);
 *
 * let d1 = smartJ$Date.createSafeDate(d.getFullYear(), d.getMonth()+1, d.getDate());
 * console.log(JSON.stringify(d1, null, 2));
 *
 * let d2 = smartJ$Date.createSafeDate(d.getFullYear(), (d.getMonth()+1)+3, d.getDate());
 * console.log(JSON.stringify(d2, null, 2));
 *
 * let o = smartJ$Date.calculateDaysOffset(d1, d2);
 * console.log(o);
 *
 * let ox = smartJ$Date.calculateDaysOffset(d2, d1);
 * console.log(ox);
 *
 * let m = smartJ$Date.calculateMonthsOffset(d1, d2);
 * console.log(m);
 *
 * let mx = smartJ$Date.calculateMonthsOffset(d2, d1);
 * console.log(mx);
 *
 * let a1 = smartJ$Date.addYears(ds, 1);
 * console.log(JSON.stringify(a1, null, 2));
 *
 * let a2 = smartJ$Date.addMonths(ds, 12);
 * console.log(JSON.stringify(a2, null, 2));
 *
 * let a3 = smartJ$Date.addDays(ds, 365);
 * console.log(JSON.stringify(a3, null, 2));
 *
 * let fd = smartJ$Date.formatDate('yy-mm-dd', d);
 * console.log(fd);
 *
 * let dd = smartJ$Date.determineDate(d);
 * console.log(JSON.stringify(dd, null, 2));
 */
const smartJ$Date = new class{constructor(){ // STATIC CLASS
	'use strict';
	const _N$ = 'smartJ$Date';

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

	/**
	 * Create a Safe Date Object
	 *
	 * @memberof smartJ$Date
	 * @method createSafeDate
	 * @static
	 * @arrow
	 *
	 * @param 	{Integer} 	year 	The Raw Year: YYYY
	 * @param 	{Integer} 	month 	The Raw Month: 1..12 ; if wrong will fix ahead or behind
	 * @param 	{Integer} 	day 	The Raw Day: 1..31 ; if wrong will fix ahead or behind
	 * @return 	{Object} 			Normalized Date Object as: { year: YYYY, month: 1..12, day: 1..31 }
	 */
	const createSafeDate = (year, month, day) => { // ES6
		//--
		year = _Utils$.format_number_int(year); // allow negative
		month = _Utils$.format_number_int(month, false);
		day = _Utils$.format_number_int(day, false);
		//--
		return normalizeAndClone({
			year: year,
			month: month,
			day: day
		});
		//--
	}; //END
	_C$.createSafeDate = createSafeDate; // export

	/**
	 * Normalize a Date Object
	 *
	 * @memberof smartJ$Date
	 * @method standardizeDate
	 * @static
	 *
	 * @param 	{Object} 	date 	The instanceof Date() or the Raw Date Object that need to be safe fixed as { year: YYYY, month: 1..12, day: 1..31 }
	 * @return 	{Object} 			Normalized Date Object as: { year: YYYY, month: 1..12, day: 1..31 }
	 */
	const standardizeDate = function(date) { // ES6
		//--
		if(date instanceof Date) {
			let d = date;
			date = null;
			date = {
				year:  d.getFullYear(),
				month: d.getMonth()+1,
				day:   d.getDate()
			};
			d = null;
		} //end if
		//--
		return normalizeAndClone(date);
		//--
	}; //END
	_C$.standardizeDate = standardizeDate; // export

	/**
	 * Get a Date Object as ISO
	 *
	 * @memberof smartJ$Date
	 * @method getIsoDate
	 * @static
	 *
	 * @param 	{Object} 	date 		The Raw Date Object as { year: YYYY, month: 1..12, day: 1..31 }
	 * @param 	{Boolean} 	withTime 	If TRUE will use the date as provided, without any normalization and will return in addition: HH:ii:ss
	 * @return 	{String} 				Normalized Date String as: YYYY-MM-DD or YYYY-MM-DD HH:ii:ss
	 */
	const getIsoDate = function(date, withTime=false) { // ES6
		//--
		if(date instanceof Date) {
			let d = date;
			date = null;
			date = {
				year:  d.getFullYear(),
				month: d.getMonth()+1,
				day:   d.getDate()
			};
			if(withTime === true) {
				date.hour = d.getHours() || 0;
				date.minute = d.getMinutes() || 0;
				date.second = d.getSeconds() || 0;
			} //end if
			d = null;
		} //end if
		//--
		if(withTime !== true) {
			date = normalizeAndClone(date);
		} //end if
		//--
		const add_d_m_LeadingZero = (d_m) => { // add leading zero to day or month: d_m is Integer
			if(d_m < 1) {
				d_m = 1;
			} //end if
			if(d_m < 10) {
				d_m = '0' + String(d_m);
			} //end if
			return String(d_m);
		}; //END
		const add_h_m_s_LeadingZero = (h_m_s) => { // add leading zero to hour, minute or second: h_m_s is Integer
			if(h_m_s < 0) {
				h_m_s = 0;
			} //end if
			if(h_m_s < 10) {
				h_m_s = '0' + String(h_m_s);
			} //end if
			return String(h_m_s);
		}; //END
		//--
		let y = String(date.year);
		let m = String(add_d_m_LeadingZero(date.month));
		let d = String(add_d_m_LeadingZero(date.day));
		//--
		if(withTime === true) {
			return String(y + '-' + m + '-' + d + ' ' + add_h_m_s_LeadingZero(date.hour || 0) + ':' + add_h_m_s_LeadingZero(date.minute || 0) + ':' + add_h_m_s_LeadingZero(date.second || 0));
		} else {
			return String(y + '-' + m + '-' + d);
		} //end if else
		//--
	}; //END
	_C$.getIsoDate = getIsoDate; // export

	/**
	 * Calculate Days Offset between two dates
	 *
	 * @memberof smartJ$Date
	 * @method calculateDaysOffset
	 * @static
	 *
	 * @param 	{Object} 	sdate1 	Normalized Date #1 Object as: { year: YYYY, month: MM, day: DD }
	 * @param 	{Object} 	sdate2 	Normalized Date #2 Object as: { year: YYYY, month: MM, day: DD }
	 * @return 	{Integer} 			The Date Offset in seconds between sdate1 and sdate2 as: sdate2 - sdate1
	 */
	const calculateDaysOffset = function(sdate1, sdate2) { // ES6
		//--
		let ofs1 = toOffset(sdate1.year, sdate1.month, sdate1.day);
		let ofs2 = toOffset(sdate2.year, sdate2.month, sdate2.day);
		//--
		return ofs2 - ofs1;
		//--
	}; //END
	_C$.calculateDaysOffset = calculateDaysOffset; // export

	/**
	 * Calculate Months Offset between two dates
	 *
	 * @memberof smartJ$Date
	 * @method calculateMonthsOffset
	 * @static
	 * @arrow
	 *
	 * @param 	{Object} 	sdate1 	Normalized Date #1 Object as: { year: YYYY, month: MM, day: DD }
	 * @param 	{Object} 	sdate2 	Normalized Date #2 Object as: { year: YYYY, month: MM, day: DD }
	 * @return 	{Integer} 			The Date Offset in seconds between sdate1 and sdate2 as: sdate2 - sdate1
	 */
	const calculateMonthsOffset = (sdate1, sdate2) => ((sdate2.year - sdate1.year) * 12) + (sdate2.month - sdate1.month); //ES6
	_C$.calculateMonthsOffset = calculateMonthsOffset; // export

	/**
	 * Add Years to a Date Object
	 *
	 * @memberof smartJ$Date
	 * @method addYears
	 * @static
	 *
	 * @param 	{Object} 	date 	The Raw Date Object as { year: YYYY, month: 1..12, day: 1..31 }
	 * @param 	{Integer} 	years 	The number of Years to add or substract
	 * @return 	{Object} 			Normalized Date Object as: { year: YYYY, month: MM, day: DD }
	 */
	const addYears = function(date, years) { //ES6
		//--
		years = _Utils$.format_number_int(years);
		//--
		let sd = normalizeAndClone(date);
		sd.year += years;
		sd = clipDay(sd);
		//--
		return sd;
		//--
	}; //END
	_C$.addYears = addYears; // export

	/**
	 * Add Months to a Date Object
	 *
	 * @memberof smartJ$Date
	 * @method addMonths
	 * @static
	 *
	 * @param 	{Object} 	date 	The Raw Date Object as { year: YYYY, month: 1..12, day: 1..31 }
	 * @param 	{Integer} 	months 	The number Months to add or substract
	 * @return 	{Object} 			Normalized Date Object as: { year: YYYY, month: MM, day: DD }
	 */
	const addMonths = function(date, months) { // ES6
		//--
		months = _Utils$.format_number_int(months);
		//--
		let sd = normalizeAndClone(date);
		sd.month += months;
		//--
		const wrapMonth = function(date) { // wraps a month
			//--
			let yearOffset = yearOffsetForMonth(date.month);
			//--
			date.year += yearOffset;
			date.month -= yearOffset * 12;
			//--
			return date;
			//--
		};
		//--
		sd = wrapMonth(sd);
		sd = clipDay(sd);
		//--
		return sd;
		//--
	}; //END
	_C$.addMonths = addMonths; // export

	/**
	 * Add Days to a Date Object
	 *
	 * @memberof smartJ$Date
	 * @method addDays
	 * @static
	 *
	 * @param 	{Object} 	date 	The Raw Date Object as { year: YYYY, month: 1..12, day: 1..31 }
	 * @param 	{Integer} 	days 	The number Days to add or substract
	 * @return 	{Object} 			Normalized Date Object as: { year: YYYY, month: MM, day: DD }
	 */
	const addDays = function(date, days) { // ES6
		//--
		days = _Utils$.format_number_int(days);
		//--
		let normalized = normalizeAndClone(date);
		let offset = toOffset(normalized.year, normalized.month, normalized.day);
		//--
		return toDate(offset + days);
		//--
	}; //END
	_C$.addDays = addDays; // export

	/**
	 * Test if the given Year is a Leap Year or not
	 *
	 * @memberof smartJ$Date
	 * @method isLeapYear
	 * @static
	 *
	 * @param 	{Integer} 	year 	The Year to be tested
	 * @return 	{Boolean} 			TRUE if the Year is Leap or FALSE if is Not Leap
	 */
	const isLeapYear = function(year) { // ES6
		//--
		year = _Utils$.format_number_int(year);
		//--
		let leap = true;
		if(year % 4 !== 0) {
			leap = false;
		} else if(year % 400 == 0) {
			leap = true;
		} else if(year % 100 == 0) {
			leap = false;
		} //end if else
		//--
		return leap;
		//--
	}; //END
	_C$.isLeapYear = isLeapYear; // export

	/**
	 * Get the Number Of Days in a specific Month of the given Year
	 *
	 * @memberof smartJ$Date
	 * @method daysInMonth
	 * @static
	 *
	 * @param 	{Integer} 	year 	The Year to be tested
	 * @param 	{Integer} 	month 	The Month to be tested
	 * @return 	{Integer} 			the Number of Days in the tested month as: 28, 29, 30 or 31
	 */
	const daysInMonth = function(year, month) { // ES6
		//--
		year = _Utils$.format_number_int(year);
		month = _Utils$.format_number_int(month);
		//--
		const DAYS_IN_MONTH = [
			31,
			28,
			31,
			30,
			31,
			30,
			31,
			31,
			30,
			31,
			30,
			31,
		];
		//--
		let d = DAYS_IN_MONTH[month - 1];
		if(month === 2 && isLeapYear(year)) {
			d = 29;
		} //end if
		//--
		return d;
		//--
	}; //END
	_C$.daysInMonth = daysInMonth; // export

	/**
	 * Format a date object into a string value.
	 * The format can be combinations of the following:
	 * d  - day of month (no leading zero) ;
	 * dd - day of month (two digit) ;
	 * o  - day of year (no leading zeros) ;
	 * oo - day of year (three digit) ;
	 * D  - day name short ;
	 * DD - day name long ;
	 * m  - month of year (no leading zero) ;
	 * mm - month of year (two digit) ;
	 * M  - month name short ;
	 * MM - month name long ;
	 * y  - year (two digit) ;
	 * yy - year (four digit) ;
	 * @ - Unix timestamp (ms since 01/01/1970) ;
	 * ! - Windows ticks (100ns since 01/01/0001) ;
	 * "..." - literal text ;
	 * '' - single quote ;
	 * @hint It is similar with jQueryUI formatDate
	 *
	 * @memberof smartJ$Date
	 * @method formatDate
	 * @static
	 *
	 * @param 	{String} 	format 			The desired format of the date
	 * @param 	{Date} 		date 			The date value to format, from Date() object
	 * @param 	{Object} 	settings 		Attributes include: dayNamesShort string[7] - abbreviated names of the days from Sunday (optional) ; dayNames string[7] - names of the days from Sunday (optional) ; monthNamesShort string[12] - abbreviated names of the months (optional) ; monthNames string[12] - names of the months (optional)
	 * @return 	{String} 					The date in the above format
	 */
	const formatDate = function(format, date, settings) { // ES6
		//--
		// The function was taken from (c) jQueryUI/v1.12.0/2016-07-30 ; modified by unixman
		//--
		format = _Utils$.stringPureVal(format, true); // cast to string, trim
		if(format == '') {
			format = 'yy-mm-dd';
		} //end if
		//--
		if(!date) {
			return '';
		} //end if
		//--
		const defaultSettings = {
			monthNames: 		['January','February','March','April','May','June', 'July','August','September','October','November','December' ], // Names of months for drop-down and formatting
			monthNamesShort: 	['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ], // For formatting
			dayNames: 			['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ], // For formatting
			dayNamesShort: 		['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ] // For formatting
		};
		//--
		let iFormat,
			dayNamesShort = (settings ? settings.dayNamesShort : null) || defaultSettings.dayNamesShort,
			dayNames = (settings ? settings.dayNames : null) || defaultSettings.dayNames,
			monthNamesShort = (settings ? settings.monthNamesShort : null) || defaultSettings.monthNamesShort,
			monthNames = (settings ? settings.monthNames : null) || defaultSettings.monthNames,
			_ticksTo1970 = (((1970 - 1) * 365 + Math.floor(1970 / 4) - Math.floor(1970 / 100) + Math.floor(1970 / 400)) * 24 * 60 * 60 * 10000000), // ticks to 1970
			output = '',
			literal = false;
		//--
		const lookAhead = function(match) { // Check whether a format character is doubled
			let matches = (iFormat + 1 < format.length && format.charAt(iFormat + 1) === match);
			if(matches) {
				iFormat++;
			} //end if
			return matches;
		};
		const formatNumber = function(match, value, len) { // Format a number, with leading zero if necessary
			let num = String(value);
			if(lookAhead(match)) {
				while(num.length < len) {
					num = '0' + num;
				} //end while
			} //end if
			return num;
		};
		const formatName = (match, value, shortNames, longNames) => (lookAhead(match) ? longNames[value] : shortNames[value]); // Format a name, short or long as requested
		//--
		if(date) {
			for(iFormat=0; iFormat<format.length; iFormat++) {
				if(literal) {
					if(format.charAt(iFormat) === "'" && !lookAhead("'")) {
						literal = false;
					} else {
						output += format.charAt(iFormat);
					} //end if else
				} else {
					switch(format.charAt(iFormat)) {
						case 'd':
							output += formatNumber('d', date.getDate(), 2);
							break;
						case 'D':
							output += formatName('D', date.getDay(), dayNamesShort, dayNames);
							break;
						case 'o':
							output += formatNumber('o', Math.round((new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime() - new Date(date.getFullYear(), 0, 0 ).getTime()) / 86400000), 3);
							break;
						case 'm':
							output += formatNumber('m', date.getMonth()+1, 2);
							break;
						case 'M':
							output += formatName('M', date.getMonth(), monthNamesShort, monthNames);
							break;
						case 'y':
							output += (lookAhead( 'y' ) ? date.getFullYear() : (date.getFullYear() % 100 < 10 ? '0' : '') + date.getFullYear() % 100);
							break;
						case '@':
							output += date.getTime();
							break;
						case '!':
							output += date.getTime() * 10000 + _ticksTo1970;
							break;
						case "'":
							if(lookAhead("'")) {
								output += "'";
							} else {
								literal = true;
							} //end if else
							break;
						default:
							output += format.charAt(iFormat);
					} //end switch
				} //end if else
			} //end for
		} //end if
		//--
		return String(output);
		//--
	}; //END
	_C$.formatDate = formatDate; // export

	/**
	 * Determine a date by a Date object or Expression
	 * Valid date objects or expressions:
	 * new Date(1937, 1 - 1, 1) 	:: a date in the past, as object ;
	 * '-1y -1m -1d' 				:: a date in the past as relative expression to the defaultDate ;
	 * new Date(2037, 12 - 1, 31) 	:: a date in the future as object ;
	 * '1y 1m 1d' 					:: a date in the future as relative expression to the defaultDate ;
	 * @hint It is similar with jQueryUI determineDate
	 *
	 * @memberof smartJ$Date
	 * @method determineDate
	 * @static
	 *
	 * @param 	{Mixed} 	date 				The Date object or date relative expression to the defaultDate
	 * @param 	{Mixed} 	defaultDate 		*Optional* null (for today) or a Date object / timestamp as default (selected) date to be used for relative expressions
	 * @return 	{Mixed} 						A Date object or null if fails to validate expression
	 */
	const determineDate = function(date, defaultDate) { // ES6
		//--
		// The function was taken from (c) jQueryUI/v1.12.0/2016-07-30 ; modified by unixman
		//--
		if((defaultDate == undefined) || (defaultDate == 'undefined') || (defaultDate == '') || (!defaultDate)) { // undef tests also for null
			defaultDate = null; // fix by unixman
		} //end if
		//--
		const _daylightSavingAdjust = (date) => {
			if(!date) {
				return null;
			} //end if
			date.setHours(date.getHours() > 12 ? date.getHours() + 2 : 0);
			return date;
		};
		//--
		const offsetNumeric = function(offset) {
			let date = new Date();
			date.setDate(date.getDate() + offset);
			return date;
		};
		//--
		const offsetString = function(offset) {
			let date = null;
			//if(offset.toLowerCase().match(/^c/)) {
			if(offset) { // fix by unixman
				date = defaultDate;
			} //end if
			if(date == null) {
				date = new Date();
			} //end if
			let year = date.getFullYear(),
				month = date.getMonth(),
				day = date.getDate();
			let pattern = /([+\-]?[0-9]+)\s*(d|D|w|W|m|M|y|Y)?/g,
				matches = pattern.exec(offset);
			while(matches) {
				switch(matches[2] || "d") {
					case 'd':
					case 'D':
						day += parseInt(matches[1], 10);
						break;
					case 'w':
					case 'W':
						day += parseInt(matches[1], 10) * 7;
						break;
					case 'm':
					case 'M':
						month += parseInt(matches[1], 10);
						day = Math.min(day, new Date(year, month+1, 0).getDate()); // 2nd param is get days in month
						break;
					case 'y':
					case 'Y':
						year += parseInt(matches[1], 10);
						day = Math.min(day, new Date(year, month+1, 0).getDate()); // 2nd param is get days in month
						break;
				} //end switch
				matches = pattern.exec(offset);
			} //end while
			return new Date(year, month, day);
		};
		//--
		let newDate = (date == null || date === '' ? defaultDate : (typeof(date) === 'string' ? offsetString(date) : (typeof(date) === 'number' ? (!_Utils$.isFiniteNumber(date) ? defaultDate : offsetNumeric(date)) : new Date(date.getTime()))));
		newDate = (newDate && newDate.toString() === 'Invalid Date' ? defaultDate : newDate);
		if(newDate) {
			newDate.setHours(0);
			newDate.setMinutes(0);
			newDate.setSeconds(0);
			newDate.setMilliseconds(0);
		} //end if
		//--
		return _daylightSavingAdjust(newDate);
		//--
	}; //END
	_C$.determineDate = determineDate; // export

	/**
	 * Convert the number of seconds (unixtime) into Human Readable, Pretty Format: Years, Days, Hours, Minutes, Seconds
	 *
	 * @memberof smartJ$Date
	 * @method prettySecondsHFmt
	 * @static
	 *
	 * @param 	{Integer} 	numSec 				The number of seconds to convert
	 * @param 	{Bool} 		rounded 			If set to TRUE will show only the first significant value
	 * @return 	{String} 						The pretty formated time as: '4d 16h 28m 7s'
	 */
	const prettySecondsHFmt = (numSec, rounded=false, txtSeparator='', txtSec='', txtMins='', txtHours='', txtDays='', txtYears='', txtJoin='') => { // convert number of seconds (unix time) to pretty days, hours, minutes, seconds
		//--
		rounded = !!rounded;
		//--
		txtSeparator = _Utils$.stringPureVal(txtSeparator); // do not trim ; can be empty
		txtSec = _Utils$.stringPureVal(txtSec, true); // trim
		if(txtSec == '') {
			txtSec = 's';
		} //end if
		txtMins = _Utils$.stringPureVal(txtMins, true); // trim
		if(txtMins == '') {
			txtMins = 'm';
		} //end if
		txtHours = _Utils$.stringPureVal(txtHours, true); // trim
		if(txtHours == '') {
			txtHours = 'h';
		} //end if
		txtDays = _Utils$.stringPureVal(txtDays, true); // trim
		if(txtDays == '') {
			txtDays = 'd';
		} //end if
		txtYears = _Utils$.stringPureVal(txtYears, true); // trim
		if(txtYears == '') {
			txtYears = 'y';
		} //end if
		txtJoin = _Utils$.stringPureVal(txtJoin); // do not trim
		if(txtJoin == '') {
			txtJoin = ' ';
		} //end if
		//--
		numSec = _Utils$.format_number_int(numSec, false);
		if(numSec < 60) {
			return _Utils$.stringTrim(numSec + txtSeparator + txtSec);
		} //end if
		//--
		const seconds = Math.floor(numSec % 60);
		const minutes = Math.floor(numSec % 3600 / 60);
		const hours = Math.floor(numSec % (3600 * 24) / 3600);
		const days = Math.floor(numSec / (3600 * 24));
		const years = Math.floor(days / 365);
		//--
		let prettyFmt = [];
		if(years > 0) {
			prettyFmt.push(years + txtSeparator + txtYears);
			if(days % 365 > 0) {
				prettyFmt.push(days % 365 + txtSeparator + txtDays);
			} //end if
		} else if(days > 0) {
			prettyFmt.push(days + txtSeparator + txtDays);
		} //end if
		if(hours > 0) {
			prettyFmt.push(hours + txtSeparator + txtHours);
		} //end if
		if(minutes > 0) {
			prettyFmt.push(minutes + txtSeparator + txtMins);
		} //end if
		if(seconds > 0) {
			prettyFmt.push(seconds + txtSeparator + txtSec);
		} //end if
		//--
		if(rounded === true) {
			return _Utils$.stringTrim(prettyFmt[0] || '');
		} //end if
		//--
		return _Utils$.stringTrim(prettyFmt.join(txtJoin));
		//--
	};
	_C$.prettySecondsHFmt = prettySecondsHFmt; // export

	//===== PRIVATES

	// normalize a date
	const normalizeAndClone = function(date) { // ES6
		//--
		let yearOffset = yearOffsetForMonth(date.month);
		let year = date.year + yearOffset;
		let month = date.month - yearOffset * 12;
		//--
		return toDate(toOffset(year, month, date.day));
		//--
	}; //END

	// clips a day
	const clipDay = (date) => { // ES6
		//--
		date.day = Math.min(date.day, daysInMonth(date.year, date.month));
		//--
		return date;
		//--
	}; //END

	// get the Year offset for a specific Month
	const yearOffsetForMonth = function(month) { // ES6
		//--
		let ofs = 0;
		if(month > 12) {
			ofs = Math.ceil(month / 12) - 1;
		} else if(month < 1) {
			ofs = Math.floor((month - 1) / 12);
		} //end if else
		//--
		return ofs;
		//--
	}; //END

	// date-offset: calculate Y,M,D to Date
	const toDate = function(z) { // ES6
		//--
		z += 719468;
		//--
		let era = ((z >= 0 ? z : z - 146096) / 146097) | 0;
		let doe = z - era * 146097; 																						// [0, 146096]
		let yoe = Math.floor((doe - Math.floor(doe / 1460) + Math.floor(doe / 36524) - Math.floor(doe / 146096)) / 365); 	// [0, 399]
		let y = yoe + era * 400;
		let doy = doe - (365 * yoe + Math.floor(yoe / 4) - Math.floor(yoe / 100)); 											// [0, 365]
		let mp = Math.floor((5 * doy + 2) / 153); 																			// [0, 11]
		let d = doy - Math.floor((153 * mp + 2) / 5) + 1; 																	// [1, 31]
		let m = mp + (mp < 10 ? 3 : -9); 																					// [1, 12]
		//--
		return {
			year: y + (m <= 2),
			month: m,
			day: d
		};
		//--
	}; //END

	// date-offset: calculate Y,M,D to Offset
	const toOffset = function(y, m, d) { // ES6
		//--
		y -= m <= 2;
		//--
		let era = ((y >= 0 ? y : y - 399) / 400) | 0;
		let yoe = y - era * 400; 													// [0, 399]
		let doy = Math.floor((153 * (m + (m > 2 ? -3 : 9)) + 2) / 5) + d - 1; 		// [0, 365]
		let doe = yoe * 365 + Math.floor(yoe / 4) - Math.floor(yoe / 100) + doy; 	// [0, 146096]
		//--
		return era * 146097 + doe - 719468;
		//--
	}; //END

}}; //END CLASS

smartJ$Date.secureClass(); // implements class security

if(typeof(window) != 'undefined') {
	window.smartJ$Date = smartJ$Date; // global export
} //end if

//==================================================================
//==================================================================

// #END
