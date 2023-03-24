
// [LIB - Smart.Framework / JS / Browser UI Utils - LightJsUI]
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// LICENSE: BSD

//==================================================================
//==================================================================

/**
 * CLASS :: UIUtils # SimpleUI :: (ES6)
 * The class is a standalone implementation of the UI components (Javascript) in Smart.Framework
 * An alternate implementation 100% compatible is provided using jQueryUI and can be loaded by loading from Smart.Framework.Modules: modules/mod-ui-jqueryui/toolkit/jquery.smartframework.ui.js
 *
 * @package Sf.Javascript:UI
 *
 * @requires		jQuery
 * @requires		smartJ$Utils
 * @requires		smartJ$Date
 * @requires		smartJ$Browser
 * @requires		jQuery.gritter/jQuery.toastr
 * @requires		SmartSimpleDialog
 * @requires		SmartSimpleTabs
 * @requires		SmartAutoSuggest
 * @requires		jQuery.ListSelect
 * @requires		jQuery.DatePicker
 * @requires		jQuery.TimePicker
 * @requires		jQuery.DataTable
 *
 * @desc The JavaScript class provides methods to simplify implementation of several basic UI components.
 * @author unix-world.org
 * @license BSD
 * @file jquery.smartframework.ui.js
 * @version 20230123
 * @class smartJ$UI
 * @static
 * @frozen
 *
 */
const smartJ$UI = new class{constructor(){ // STATIC CLASS
	const _N$ = 'smartJ$UI';

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

	const $ = jQuery; // jQuery referencing

	const _Utils$ = smartJ$Utils;
	const _Date$ = smartJ$Date;
	const _BwUtils$ = smartJ$Browser; // just for reference and be sure is loaded
	const _Dialog$ = SmartSimpleDialog;
	const _Auto$Complete = SmartAutoSuggest;
	const _Tab$ = SmartSimpleTabs;

	//=======================================

	/**
	 * Overlay CSS class
	 * @default 'simpledialog-overlay'
	 * @var {String} overlayCssClass
	 * @static
	 * @memberof smartJ$UI
	 */
	_C$.overlayCssClass = 'simpledialog-overlay';

	//=======================================

	/**
	 * Create a Message Growl Notification
	 *
	 * @requires jQuery
	 * @requires jQuery.gritter/jQuery.toastr
	 *
	 * @memberof smartJ$UI
	 * @method GrowlAdd
	 * @static
	 *
	 * @param 	{String} 		title 				a title for the notification (HTML) ; it can be empty string
	 * @param 	{String} 		text 				the main notification message (HTML) ; it is mandatory
	 * @param 	{String} 		image 				the URL link to a notification icon image (svg/gif/png/jpg/webp) or null
	 * @param 	{Integer} 		time 				the notification display time in milliseconds ; use 0 for sticky ; between 0 (0 sec) and 60000 (60 sec)
	 * @param 	{Boolean} 		sticky 				*Optional* FALSE by default (will auto-close after the display time expire) ; TRUE to set sticky (require manual close, will not auto-close)
	 * @param 	{Enum} 			css_class 			*Optional* a CSS class name for the notification or empty string to use default one: darknote (black), notice (white), info (blue), success (green), warning (yellow), error (red)
	 * @param 	{Array-Obj} 	options 			*Optional* Extra Growl Properties:
	 * 		{ // example of extra Options
	 * 			before_open: 	() => {},
	 * 			after_open: 	() => {},
	 * 			before_close: 	() => {},
	 * 			after_close: 	() => {}
	 * 		}
	 * @return 	{Boolean} 							TRUE if Success FALSE if Fail
	 */
	const GrowlAdd = function(title, html, image, time, sticky=false, css_class=null, options=null) {
		//--
		let growl_before_open = null;
		let growl_after_open = null;
		let growl_before_close = null;
		let growl_after_close = null;
		//--
		if(options) {
			if(options.hasOwnProperty('before_open')) {
				growl_before_open = options.before_open;
			} //end if
			if(options.hasOwnProperty('after_open')) {
				growl_after_open = options.after_open;
			} //end if
			if(options.hasOwnProperty('before_close')) {
				growl_before_close = options.before_close;
			} //end if
			if(options.hasOwnProperty('after_close')) {
				growl_after_close = options.after_close;
			} //end if
		} //end if
		//--
		if((image != undefined) && (image != '') && (image !== false)) { // undef tests also for null
			image = '<img src="' + _Utils$.escape_html(image) + '" align="right">';
		} else {
			image = '';
		} //end if
		//--
		if(typeof($.gritter) != 'undefined') {
			//--
			css_class = String($.gritter.translateCssClasses(css_class));
			$.gritter.add({
				class_name: String(css_class),
				title: String(String(title) + String(image)),
				text: String(html),
				before_open:  growl_before_open,
				after_open:   growl_after_open,
				before_close: growl_before_close,
				after_close:  growl_after_close,
				time: (sticky ? 0 : time),
				sticky: !! sticky, // bool
			});
			//--
		} else if(typeof($.toastr) != 'undefined') {
			//--
			css_class = $.toastr.translateCssClasses(css_class);
			$.toastr.notify({
				appearanceClass: String(css_class),
				title: String(String(title) + String(image)),
				message: String(html),
				onBeforeVisible: growl_before_open,
				onVisible: growl_after_open,
				onBeforeHidden: growl_before_close,
				onHidden: growl_after_close,
				timeOut: (sticky ? 0 : time),
			});
			//--
		} else {
			//--
			return false;
			//--
		} //end if else
		//--
		return true;
		//--
	}; //END
	_C$.GrowlAdd = GrowlAdd; // export

	/**
	 * Remove all Growl Notifications from the current browser window (page)
	 *
	 * @requires jQuery
	 * @requires jQuery.gritter/jQuery.toastr
	 *
	 * @memberof smartJ$UI
	 * @method GrowlRemove
	 * @static
	 *
	 * @return 	{Boolean} 					TRUE if Success FALSE if Fail
	 */
	const GrowlRemove = function() {
		//--
		if(typeof($.gritter) != 'undefined') {
			//--
			$.gritter.removeAll();
			//--
		} else if(typeof($.toastr) != 'undefined') {
			//--
			$.toastr.clear();
			//--
		} else {
			//--
			return false;
			//--
		} //end if else
		//--
		return true
		//--
	}; //END
	_C$.GrowlRemove = GrowlRemove; // export

	/**
	 * Display a Tooltip ; UI Component
	 *
	 * @requires jQuery
	 * @requires jquery.tiptop.css
	 * @requires jquery.tiptop.js
	 *
	 * @memberof smartJ$UI
	 * @method ToolTip
	 * @static
	 *
	 * @param 	{String} 	selector 	:: The jQuery element selector ; Ex: '.class' or '#id'
	 * @return 	{Object} 				:: The jQuery HTML Element
	 */
	const ToolTip = function(selector) { // ES6
		//--
		selector = _Utils$.stringPureVal(selector, true); // +trim
		if(selector == '') {
			_p$.warn(_N$, 'ToolTip', 'Invalid or Empty Selector');
			return;
		} //end if
		//--
		let HtmlElement = $(selector);
		const dataTooltipOk = 'tooltip-ok';
		//--
		$('body').on('mousemove', selector, (evt) => {
			$(selector).each((index, el) => {
				const $el = $(el);
				if($el.data(dataTooltipOk)) {
					return;
				} //end if
				$el.data(dataTooltipOk, '1').tipTop();
				let trigered = false;
				$el.on('mousemove', () => {
					if(trigered) {
						return;
					} //end if
					trigered = true;
					$el.trigger('mouseenter');
				});
			});
		});
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.ToolTip = ToolTip; // export

	//=======================================

	/**
	 * Display an Alert Dialog, with 1 button: OK ; UI Component
	 *
	 * @requires jQuery
	 * @requires dialog/simple-dialog.css
	 * @requires dialog/simple-dialog.js
	 *
	 * @memberof smartJ$UI
	 * @method DialogAlert
	 * @static
	 *
	 * @param 	{String} 		y_message_html 		:: Message to Display, HTML
	 * @param 	{Function} 		evcode 				:: The code to execute on press OK: () => {} or null
	 * @param 	{String} 		y_title 			:: The Title of Dialog, TEXT
	 * @param 	{Integer+} 		y_width 			:: The Dialog Width, *Optional*, Default: 550 (px)
	 * @param 	{Integer+} 		y_height 			:: The Dialog Height, *Optional*, Default: 250 (px)
	 * @return 	{Object} 							:: The jQuery HTML Element
	 */
	const DialogAlert = (y_message_html, evcode=null, y_title='', y_width=null, y_height=null) => { // ES6
		//--
		// KEEP SYNC WITH smartJ$Browser.AlertDialog()
		//--
		// evcode params: -
		//--
		return _Dialog$.Dialog_Alert(y_message_html, evcode, y_title, y_width, y_height);
		//--
	}; //END
	_C$.DialogAlert = DialogAlert; // export

	//=======================================

	/**
	 * Display a Confirm Dialog, with 2 buttons: OK and Cancel ; UI Component
	 *
	 * @requires jQuery
	 * @requires dialog/simple-dialog.css
	 * @requires dialog/simple-dialog.js
	 *
	 * @memberof smartJ$UI
	 * @method DialogConfirm
	 * @static
	 *
	 * @param 	{String} 		y_question_html 	:: Message (Question) to Display, HTML
	 * @param 	{Function} 		evcode 				:: The code to execute on press OK: () => {} or null
	 * @param 	{String} 		y_title 			:: The Title of Dialog, TEXT
	 * @param 	{Integer+} 		y_width 			:: The Dialog Width, *Optional*, Default: 550 (px)
	 * @param 	{Integer+} 		y_height 			:: The Dialog Height, *Optional*, Default: 250 (px)
	 * @return 	{Object} 							:: The jQuery HTML Element
	 */
	const DialogConfirm = (y_question_html, evcode=null, y_title='', y_width=null, y_height=null) => { // ES6
		//--
		// KEEP SYNC WITH smartJ$Browser.ConfirmDialog()
		//--
		// evcode params: -
		//--
		return _Dialog$.Dialog_Confirm(y_question_html, evcode, y_title, y_width, y_height);
		//--
	}; //END
	_C$.DialogConfirm = DialogConfirm; // export

	//=======================================

	/**
	 * Display a Single or Multi Select List ; UI Component
	 *
	 * @hint onChange handler is taken from onBlur html attribute of the element the component binds to ; can be: (elemID) => {} or null
	 *
	 * @requires jQuery
	 * @requires smartJ$Utils
	 * @requires listselect/css/chosen.css
	 * @requires listselect/chosen.jquery.js
	 *
	 * @memberof smartJ$UI
	 * @method SelectList
	 * @static
	 *
	 * @param 	{String} 		elemID 				:: The HTML Element ID to bind to (ussualy a real list single or multi)
	 * @param 	{Integer+} 		dimW 				:: The element Width (can be overriden with a CSS style !important)
	 * @param 	{Integer+} 		dimH 				:: The element Height (can be overriden with a CSS style !important)
	 * @param 	{Boolean} 		isMulti 			:: If the list is multi (TRUE) or single (FALSE)
	 * @param 	{Boolean} 		useFilter 			:: If TRUE will display a search filter list
	 * @return 	{Object} 							:: The jQuery HTML Element
	 */
	const SelectList = function(elemID, dimW, dimH, isMulti, useFilter) { // ES6
		//--
		// evcode is taken from onBlur, mostly used by multi-select lists ; single select lists can handle onChange
		// evcode params: elemID, useFilter, isMulti
		//--
		elemID = _Utils$.stringPureVal(elemID, true); // +trim
		elemID = _Utils$.create_htmid(elemID);
		if(elemID == '') {
			_p$.warn(_N$, 'SelectList', 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		dimW = _Utils$.format_number_int(dimW, false); // no negatives
		if(dimW < 50) {
			dimW = 50;
		} //end if
		//--
		dimH = _Utils$.format_number_int(dimH, false); // no negatives
		if(dimH < 50) {
			dimH = 50;
		} //end if
		//--
		isMulti = !! isMulti;
		useFilter = !! useFilter;
		//--
		let HtmlElement = $('#' + elemID);
		//--
		const disable_search = ! useFilter;
		HtmlElement.chosen({
			allow_single_deselect: true,
			disable_search_threshold: 10,
			enable_split_word_search: false,
			search_contains: true,
			no_results_text: 'Nothing found!',
			disable_search: disable_search,
			width: dimW
			// dimH is used below with a trick !
	//	}).on('change', (evt, params) => {
		}).on('chosen:hiding_dropdown', (evt, params) => {
			//--
			evt.preventDefault();
			//--
			const evcode = HtmlElement.attr('onBlur'); // onChange is always triggered, but useless on Multi-Select Lists on which we substitute it with the onBlur which is not triggered here but we catch and execute here
			//--
			_Utils$.evalJsFxCode( // EV.CTX
				_N$ + '.SelectList',
				(typeof(evcode) === 'function' ?
					() => {
						'use strict'; // req. strict mode for security !
						(evcode)(elemID, useFilter, isMulti);
					} :
					() => {
						'use strict'; // req. strict mode for security !
						!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: elemID, useFilter, isMulti
					}
				)
			);
			//--
		});
		if(isMulti) {
			$('#' + elemID + '__chosen').find('ul.chosen-choices').css({ 'max-height' : dimH + 'px' }); // n/a, choosen uses another id
		} //end if
		//--
		HtmlElement.data('smart-ui-elem-type', 'chosen');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.SelectList = SelectList; // export

	//=======================================

	/**
	 * Initialize a Date Picker ; UI Component
	 * The selected date will be in ISO format as yyyy-mm-dd
	 *
	 * @requires jQuery
	 * @requires datepicker/css/datepicker.css
	 * @requires datepicker/datepicker.js
	 *
	 * @memberof smartJ$UI
	 * @method DatePickerInit
	 * @static
	 *
	 * @param 	{String} 		elemID 				:: The HTML Element ID to bind to (ussualy a text input)
	 * @param 	{String} 		dateFmt 			:: Calendar Date Format, used to display only ; Ex: 'dd.mm.yy'
	 * @param 	{String} 		selDate 			:: Calendar selected date, ISO format as yyyy-mm-dd or empty string
	 * @param 	{Integer+} 		calStart 			:: Calendar first day of week (0..6) ; 0 = Sunday, 1 = Monday ...
	 * @param 	{Mixed} 		calMinDate 			:: Calendar min date to display and allow selection ; Ex Object: new Date(1937, 1 - 1, 1) ; Ex String: '-1y -1m -1d'
	 * @param 	{Mixed} 		calMaxDate 			:: Calendar max date to display and allow selection ; Ex Object: new Date(2037, 12 - 1, 31) ; Ex String: '1y 1m 1d'
	 * @param 	{Integer+} 		noOfMonths 			:: Calendar number of months to display ; Default is 1
	 * @param 	{Function} 		evcode 				:: The code to execute on select: (date, altdate, inst, elemID) => {} or null
	 * @return 	{Object} 							:: The jQuery HTML Date Picker
	 */
	const DatePickerInit = function(elemID, dateFmt, selDate, calStart, calMinDate, calMaxDate, noOfMonths, evcode=null) { // ES6
		//--
		// TODO: if possible show multiple months: noOfMonths
		//--
		// evcode params: date, altdate, inst, elemID
		//--
		const _m$ = 'DatePickerInit';
		//--
		elemID = _Utils$.stringPureVal(elemID, true); // +trim
		elemID = _Utils$.create_htmid(elemID);
		if(elemID == '') {
			_p$.warn(_N$, _m$, 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		dateFmt = _Utils$.stringPureVal(dateFmt, true); // +trim
		selDate = _Utils$.stringPureVal(selDate, true); // +trim
		calStart = _Utils$.format_number_int(calStart, false); // no negatives
		noOfMonths = _Utils$.format_number_int(noOfMonths, false); // no negatives
		//--
		let the_initial_date = String(selDate);
		let the_initial_altdate = '';
		if(the_initial_date != '') {
			the_initial_altdate = _Date$.formatDate(String(dateFmt), new Date(the_initial_date));
			$('#date-entry-' + elemID).val(the_initial_altdate);
		} //end if
		//--
		if((typeof(calMinDate) != 'undefined') || (calMinDate == 'undefined') || (calMinDate = '') || (calMinDate == null)) { // if not undefined or empty
			calMinDate = _Date$.determineDate(calMinDate);
			if(calMinDate == null) {
				calMinDate = '';
			} else {
				calMinDate = _Date$.formatDate('yy-mm-dd', calMinDate);
			} //end if
		} else {
			calMinDate = '';
		} //end if else
		if((typeof(calMaxDate) != 'undefined') || (calMaxDate == 'undefined') || (calMaxDate = '') || (calMaxDate == null)) { // if not undefined or empty
			calMaxDate = _Date$.determineDate(calMaxDate);
			if(calMaxDate == null) {
				calMaxDate = '';
			} else {
				calMaxDate = _Date$.formatDate('yy-mm-dd', calMaxDate);
			} //end if
		} else {
			calMaxDate = '';
		} //end if else
		//--
		if(calMinDate) {
			calMinDate = new Date(calMinDate);
		} else {
			calMinDate = '';
		} //end if
		if(calMaxDate) {
			calMaxDate = new Date(calMaxDate);
		} else {
			calMaxDate = '';
		} //end if
		//--
		let HtmlElement = $('#' + elemID);
		//--
		HtmlElement.val(the_initial_date).datepicker({
			//--
			keyboardNav: false,
			toggleSelected: false, // avoid unselect the selected date if clicking on it to avoid double hit for onSelect method with double click
			timepicker: false,
			inline: false,
			position: 'bottom left',
			offset: 12,
			todayButton: true,
			clearButton: true,
			showEvent: 'focus',
			autoClose: true,
			//--
			showOtherMonths: true,
			selectOtherMonths: true,
			moveToOtherMonthsOnSelect: false,
			showOtherYears: true,
			selectOtherYears: true,
			moveToOtherYearsOnSelect: false,
			//--
			weekends: [6, 0],
			firstDay: calStart,
			dateFormat: 'yyyy-mm-dd',
			altField: '#date-entry-' + elemID,
			altFieldDateFormat: 'yyyy-mm-dd',
			minDate: calMinDate,
			maxDate: calMaxDate,
			//--
			disableNavWhenOutOfRange: true,
			multipleDates: false,
			multipleDatesSeparator: ';',
			range: false,
			//--
			onSelect: (date, altdate, inst) => {
				//--
				date = _Utils$.stringPureVal(date, true); // +trim
				if(date != '') {
					altdate = date; // altdate is rewritten and re-converted below because format is different than standard if used
					try {
						altdate = _Date$.formatDate(String(dateFmt), new Date(date));
						if(/Invalid|NaN|Infinity/.test(altdate)) {
							altdate = date;
						} //end if
					} catch(err) {
						_p$.warn(_N$, _m$, 'Date conversion is not supported by the browser. Using ISO Date', err);
					} //end try catch
					altdate = _Utils$.stringPureVal(altdate, true); // +trim
					//--
					$('#date-entry-' + elemID).val(altdate);
					//--
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$,
						(typeof(evcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(evcode)(date, altdate, inst, elemID);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: date, altdate, inst, elemID
							}
						)
					);
				} //end if
				//--
			} // end
		});
		//--
		HtmlElement.data('smart-ui-elem-type', 'Air_DatePicker');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.DatePickerInit = DatePickerInit; // export

	//=======================================

	/**
	 * Handle Display for an already Initialized Date Picker ; UI Component
	 * The selected date will be in ISO format as yyyy-mm-dd
	 *
	 * @example
	 * let selectedDate = ''; // or can be yyyy-mm-dd
	 * let elemID = 'myDatePicker';
	 * let html = '
	 * <span id="date-area-' + myDatePicker + '">
	 *   <input type="hidden" id="' + myDatePicker + '" name="date" value="' + selectedDate + '"><!-- holds the ISO formatted date -->
	 *   <input type="text" id="date-entry-' + myDatePicker + '" name="dtfmt__date" maxlength="13" value="' + selectedDate + '" readonly="readonly" class="datetime_Field_DatePicker" autocomplete="off"><!-- holds the custom formatted date -->
	 * </span>
	 * ';
	 * $('body').append(html);
	 * smartJ$UI.DatePickerInit(elemID, 'dd.mm.yy', '', 1, '-1y -1m -1d', '1y 1m 1d', 1); // initialize the date picker
	 * $('#date-entry-' + elemID).on('click', (e) => { smartJ$UI.DatePickerDisplay(elemID); })}); // show date picker on click over text input
	 * $('#date-entry-' + elemID).on('dblclick doubletap', (e) => { $('#' + elemID).val(''); $('#date-entry-' + elemID).val(''); }); // reset on double click the text input
	 *
	 * @requires jQuery
	 * @requires datepicker/css/datepicker.css
	 * @requires datepicker/datepicker.js
	 *
	 * @memberof smartJ$UI
	 * @method DatePickerDisplay
	 * @static
	 *
	 * @param 	{String} 		datepicker_id 		:: The HTML Element ID to bind to (ussualy a text input) ; must be previous initialized with smartJ$UI.DatePickerInit()
	 * @return 	{Object} 							:: The jQuery HTML Date Picker
	 */
	const DatePickerDisplay = function(datepicker_id) { // ES6
		//--
		datepicker_id = _Utils$.stringPureVal(datepicker_id, true); // +trim
		datepicker_id = _Utils$.create_htmid(datepicker_id);
		if(datepicker_id == '') {
			_p$.warn(_N$, 'DatePickerDisplay', 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		let HtmlElement = $('#' + datepicker_id);
		//--
		if(HtmlElement.data('smart-ui-elem-type') !== 'Air_DatePicker') {
			return null;
		} //end if
		//--
		HtmlElement.data('datepicker').show();
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.DatePickerDisplay = DatePickerDisplay; // export

	//=======================================

	/**
	 * Initialize a Time Picker ; UI Component
	 * The selected time will be in ISO format as hh:ii
	 * The selected value will be get directly from the `value` attribute of the html element is binded to <input id="MyTimePicker" type="text" value="12:30"> element
	 *
	 * @requires jQuery
	 * @requires timepicker/css/jquery.timepicker.css
	 * @requires timepicker/jquery.timepicker.js
	 *
	 * @memberof smartJ$UI
	 * @method TimePickerInit
	 * @static
	 *
	 * @param 	{String} 		elemID 				:: The HTML Element ID to bind to (ussualy a text input)
	 * @param 	{Integer+} 		hStart 				:: Time Start Hour ; 0..22
	 * @param 	{Integer+} 		hEnd 				:: Time End Hour ; 1..23
	 * @param 	{Integer+} 		mStart 				:: Time Start Minute ; 0..58
	 * @param 	{Integer+} 		mEnd 				:: Time End Minute ; 1..59
	 * @param 	{Integer+} 		mInterval 			:: Time Interval in Minutes ; 1..30
	 * @param 	{Integer+} 		tmRows 				:: Time Table Rows ; 1..5 ; Default is 2
	 * @param 	{Function} 		evcode 				:: The code to execute on select: (time, inst, elemID) => {} or null
	 * @return 	{Object} 							:: The jQuery HTML Time Picker
	 */
	const TimePickerInit = function(elemID, hStart, hEnd, mStart, mEnd, mInterval, tmRows, evcode=null) { // ES6
		//--
		// evcode params: time, inst, elemID
		//--
		const _m$ = 'TimePickerInit';
		//--
		elemID = _Utils$.stringPureVal(elemID, true); // +trim
		elemID = _Utils$.create_htmid(elemID);
		if(elemID == '') {
			_p$.warn(_N$, _m$, 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		let HtmlElement = $('#' + elemID);
		//--
		HtmlElement.timepicker({
			defaultTime: '', // it must superset the default now() when now() is not in allowed h/m
			showOn: 'button',
			showCloseButton: false,
			showDeselectButton: true,
			showAnim: null,
			duration: null,
			timeSeparator: ':',
			showPeriodLabels: false,
			showPeriod: false,
			amPmText:['',''],
			rows: tmRows,
			hours: {
				starts: hStart,
				ends: hEnd
			},
			minutes: {
				starts: mStart,
				ends: mEnd,
				interval: mInterval
			},
			onSelect: (time, inst) => {
				//--
				time = _Utils$.stringPureVal(time, true); // +trim
				if(time != '') { //emulate on select because onSelect trigger twice (1 select hour + 2 select minutes), so if no time selected even if onClose means no onSelect !
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$,
						(typeof(evcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(evcode)(time, inst, elemID);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: time, inst, elemID
							}
						)
					);
				} //end if
				//--
			} //end
		});
		HtmlElement.data('smart-ui-elem-type', 'timepicker');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.TimePickerInit = TimePickerInit; // export

	//=======================================

	/**
	 * Handle Display for an already Initialized Time Picker ; UI Component
	 * The selected time will be in ISO format as hh:ii
	 *
	 * @example
	 * let selectedTime = ''; // or can be hh:ii
	 * let elemID = 'myTimePicker';
	 * let html = '
	 * <span id="time-area-' + elemID + '" title="[###TEXT-SELECT|html###] [HH:ii]">
	 *   <input type="text" name="time" id="' + elemID + '" maxlength="5" value="' + selectedTime + '" readonly="readonly" class="datetime_Field_TimePicker" autocomplete="off">
	 * </span>
	 * ';
	 * $('body').append(html);
	 * smartJ$UI.TimePickerInit(elemID, 0, 23, 0, 59, 5, 2); // initialize the time picker
	 * $('#' + elemID).on('click', (e) => { smartJ$UI.TimePickerDisplay(elemID); })}); // show time picker on click over text input
	 * $('#' + elemID).on('dblclick doubletap', (e) => { $('#' + elemID).val(''); }); // reset on double click the text input
	 *
	 * @requires jQuery
	 * @requires timepicker/css/jquery.timepicker.css
	 * @requires timepicker/jquery.timepicker.js
	 *
	 * @memberof smartJ$UI
	 * @method TimePickerDisplay
	 * @static
	 *
	 * @param 	{String} 		timepicker_id 		:: The HTML Element ID to bind to (ussualy a text input) ; must be previous initialized with smartJ$UI.TimePickerInit()
	 * @return 	{Object} 							:: The jQuery HTML Time Picker
	 */
	const TimePickerDisplay = function(timepicker_id) { // ES6
		//--
		timepicker_id = _Utils$.stringPureVal(timepicker_id, true); // +trim
		timepicker_id = _Utils$.create_htmid(timepicker_id);
		if(timepicker_id == '') {
			_p$.warn(_N$, 'TimePickerDisplay', 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		let HtmlElement = $('#' + timepicker_id);
		//--
		if(HtmlElement.data('smart-ui-elem-type') !== 'timepicker') {
			return null;
		} //end if
		//--
		HtmlElement.timepicker('show');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.TimePickerDisplay = TimePickerDisplay; // export

	//=======================================

	/**
	 * Initialize Tabs Component ; UI Component
	 * The content of each tab (from the tabs component) can be loaded async by Ajax when the Tab is selected for display
	 *
	 * @example
	 * let tabs = '
	 * <div id="tabs_draw">
	 *   <div id="tabs_nav">
	 *     <li><a href="#tab-in-page">Tab with Content Preset</a></li>
	 *     <li><a href="?content=external-tab-content-load-by-ajax">Tab which loads contents by Ajax</a></li>
	 *   </div>
	 *   <div id="tab-in-page">
	 *     <h1>The content of the first tab ...</h1>
	 *   </div>
	 *   <!-- second tab does not to be set in HTML, will be created on the fly by the tabs component and populated with the HTML contents that comes by Ajax from a GET request on (example): ?content=external-tab-content-load-by-ajax -->
	 * </div>
	 * ';
	 * $('body').append(html);
	 * smartJ$UI.TabsInit('tabs_draw');
	 *
	 * @requires jQuery
	 * @requires tabs/jquery.tabs.css
	 * @requires tabs/jquery.tabs.js
	 *
	 * @memberof smartJ$UI
	 * @method TabsInit
	 * @static
	 *
	 * @param 	{String} 	tabs_id 			:: The HTML Element ID to bind to
	 * @param 	{Integer+} 	tab_selected 		:: The selected tab number ; Default is zero
	 * @param 	{Boolean} 	prevent_reload		:: *Optional* ; Default is TRUE ; If TRUE the tab content will not be reloaded after the first load
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const TabsInit = (tabs_id, tab_selected=0, prevent_reload=true) => { // ES6
		//--
		return _Tab$.initTabs(tabs_id, tab_selected, prevent_reload);
		//--
	}; //END
	_C$.TabsInit = TabsInit; // export

	//=======================================

	/**
	 * Activate or Deactivate the Tabs Component ; UI Component
	 * By default all the Tabs are active ; Use the function to deactivate and perhaps activate again
	 * When deactivated, only the current selected tab can be used
	 * It can be useful for using by example with edit operations to prevent switch tabs before saving the current form from the current active Tab
	 *
	 * @example
	 * smartJ$UI.TabsActivate(false); // deactivate tabs
	 * smartJ$UI.TabsActivate(true); // re-activate tabs
	 *
	 * @requires jQuery
	 * @requires tabs/jquery.tabs.css
	 * @requires tabs/jquery.tabs.js
	 *
	 * @memberof smartJ$UI
	 * @method TabsActivate
	 * @static
	 *
	 * @param 	{String} 	tabs_id 			:: The HTML Element ID to bind to ; must be previous initialized with smartJ$UI.TabsInit()
	 * @param 	{Boolean} 	activation			:: If FALSE the Tabs component will become inactive, except the current selected Tab ; when set back to TRUE will re-activate all tabs
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const TabsActivate = (tabs_id, activation) => { // ES6
		//--
		return _Tab$.activateTabs(tabs_id, activation)
		//--
	}; //END
	_C$.TabsActivate = TabsActivate; // export

	//=======================================

	/**
	 * Creates a Single-Value or Multi-Value AutoComplete (AutoSuggest) Field ; UI Component
	 *
	 * @requires jQuery
	 * @requires autosuggest/smart-suggest.css
	 * @requires autosuggest/smart-suggest.js
	 *
	 * @example
	 * // the expected JSON structure that have to be served via the DataURL + ParamSrc
	 * [
	 * 		{ "id":"id1", "value":"Value1","label":"Label1" },
	 * 		{ "id":"id2", "value":"Value2","label":"Label2" },
	 * 		// ...
	 * 		{ "id":"idN", "value":"ValueN","label":"LabelN" },
	 * ]
	 * // the DataURL + ParamSrc controller must take care to return the values filetered by the value sent from the field via ParamSrc via HTTP GET
	 *
	 * @memberof smartJ$UI
	 * @method AutoCompleteField
	 * @static
	 *
	 * @param 	{Enum} 		single_or_multi 	:: Type: can be: 'single' or 'multilist'
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a text input or a text area
	 * @param 	{String} 	data_url 			:: The Data URL Prefix ; Ex: '?op=list&type=autosuggest'
	 * @param 	{String} 	param_src 			:: The Data URL Parameter to be appended as suffix to the above Data URL ; Ex: 'searchTerm' (will use: '?op=list&type=autosuggest&searchTerm=')
	 * @param 	{Integer+} 	min_term_len 		:: The minimum term search length ; expects a value between 0..255 ; will start searching only after the typed term length matches the value
	 * @param 	{Function} 	evcode 				:: The code to execute on select: (id, value, label, data) => {} or null
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const AutoCompleteField = function(single_or_multi, elem_id, data_url, param_src, min_term_len, evcode=null) { // ES6
		//--
		// evcode params: id, value, label, data
		//--
		const _m$ = 'AutoCompleteField';
		//--
		elem_id = _Utils$.stringPureVal(elem_id, true); // +trim
		elem_id = _Utils$.create_htmid(elem_id);
		if(elem_id == '') {
			_p$.warn(_N$, _m$, 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		data_url = _Utils$.stringPureVal(data_url, true); // +trim
		if(!data_url) {
			_p$.warn(_N$, _m$, 'The data_url is empty');
			return;
		} //end if
		//--
		param_src = _Utils$.stringPureVal(param_src, true); // +trim
		min_term_len = _Utils$.format_number_int(min_term_len, false);
		//--
		let url = '';
		//--
		data_url = String(data_url);
		if(data_url.indexOf('?') != -1) {
			url = String(data_url) + '&';
		} else {
			url = String(data_url) + '?';
		} //end if else
		//--
		if(param_src) {
			url += String(param_src) + '=';
		} //end if
		//--
		return _Auto$Complete.bindToInput(single_or_multi, elem_id, '', url, false, null, min_term_len, evcode);
		//--
	}; //END
	_C$.AutoCompleteField = AutoCompleteField; // export


	//=======================================

	/**
	 * Creates a DataTable from a regular HTML Table ; UI Component
	 * DataTables is a table enhancing plug-in for the jQuery Javascript library,
	 * adding sorting, paging and filtering abilities to plain HTML tables.
	 *
	 * @hint Add advanced interaction controls to HTML tables
	 *
	 * @requires jQuery
	 * @requires datatables/datatables-responsive.css
	 * @requires datatables/datatables-responsive.js
	 * @requires datatables/smart-datatables.js
	 *
	 * @example
	 * // <!-- transform the following table into a DataTable with filtering, pagination, column ordering and many other features -->
	 * //<table id="myTable">
	 * // <thead>
	 * // 	<tr>
	 * // 		<th>Col1</th>
	 * // 		<th>Col2</th>
	 * // 	</tr>
	 * // </thead>
	 * // <tbody>
	 * // 	<tr>
	 * // 		<td>Col1</td>
	 * // 		<td>Col2</td>
	 * // 	</tr>
	 * // </tbody>
	 * // <tfoot>
	 * // 	<tr>
	 * // 		<th>Col1</th>
	 * // 		<th>Col2</th>
	 * // 	</tr>
	 * // </tfoot>
	 * //</table>
	 * //--
	 * smartJ$UI.DataTableInit('myTable', {
	 * 		responsive: false, // if TRUE on responsive mode columns may become fluid on small screens
	 *		filter: true,
	 *		sort: true,
	 *		paginate: true,
	 * 		pagesize: 10,
	 * 		pagesizes: [ 10, 25, 50, 100 ],
	 * 		classField: 'ux-field', // css classes to display input fields (ex: filter)
	 * 		classButton: 'ux-button ux-button-small', // css classes to display the buttons
	 * 		classActiveButton: 'ux-button-primary', // css classes to display the active buttons
	 *		colorder: [
	 *			[ 0, 'asc' ], // [ 1, 'desc' ]
	 *		],
	 *		coldefs: [
	 *			{ // column one
	 *				targets: 0,
	 *				width: '25px',
	 * 				render: (data, type, row) => {
	 * 					if(type === 'type' || type === 'sort' || type === 'filter') { // preserve special objects from column render
	 * 						return data;
	 * 					} else { // customize the appearance of the 1st column
	 * 						return '<span style="color:#CCCCCC;">' + smartJ$Utils.escape_html(data) + '</span>';
	 * 					}
	 * 				}
	 * 				// for more options see: examples at https://github.com/DataTables/DataTables
	 *			},
	 *			{ // column two
	 *				targets: 1,
	 *				width: '275px'
	 *			}
	 *		]
	 * });
	 * //--
	 *
	 * @memberof smartJ$UI
	 * @method DataTableInit
	 * @static
	 *
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a HTML <table></table>
	 * @param 	{Object} 	options 			:: The Options for DataTable
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const DataTableInit = (elem_id, options=null) => { // ES6
		//--
		if(typeof(SmartDataTables) == undefined) {
			_p$.error(_N$, 'DataTableInit', 'SmartDataTables is not loaded ...');
			return;
		} //end if
		//--
		return SmartDataTables.DataTableInit(elem_id, options);
		//--
	}; //END
	_C$.DataTableInit = DataTableInit; // export

	//=======================================

	/**
	 * Apply a Filter over DataTable using a regular expression ; UI Component
	 * If a filter is applied oved data, will display just the filtered data and if no data match the filter will display no data
	 *
	 * @requires jQuery
	 * @requires datatables/datatables-responsive.css
	 * @requires datatables/datatables-responsive.js
	 * @requires datatables/smart-datatables.js
	 *
	 * @example
	 * // filter a DataTable by column no.1 (2nd column, starting from zero) and display only lines where column no.1 have the value: 'warning' or 'error'
	 * smartJ$UI.DataTableColumnsFilter('myTable', 1, '^(warning|error)$');
	 *
	 * @memberof smartJ$UI
	 * @method DataTableColumnsFilter
	 * @static
	 *
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a DataTable already previous initiated with smartJ$UI.DataTableInit()
	 * @param 	{Integer+} 	filterColNumber 	:: The DataTable column number 0..n
	 * @param 	{Regex} 	regexStr 			:: A valid Regex Partial Expression String (without enclosing slashes /../, as string) to filter the column values ; ex: '^(val1|val\-2)$'
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const DataTableColumnsFilter = (elem_id, filterColNumber, regexStr) => { // ES6
		//--
		if(typeof(SmartDataTables) == undefined) {
			_p$.error(_N$, 'DataTableColumnsFilter', 'SmartDataTables is not loaded ...');
			return;
		} //end if
		//--
		return SmartDataTables.DataTableColumnsFilter(elem_id, filterColNumber, regexStr);
		//--
	}; //END
	_C$.DataTableColumnsFilter = DataTableColumnsFilter; // export

	//=======================================

}}; //END CLASS

smartJ$UI.secureClass(); // implements class security

window.smartJ$UI = smartJ$UI; // global export

//==================================================================
//==================================================================


// #END
