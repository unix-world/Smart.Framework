
// [LIB - Smart.Framework / JS / Browser UI Utils - LightJsUI]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2
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
 * @version 20210421
 * @class smartJ$UI
 * @static
 * @frozen
 *
 */
const smartJ$UI = new class{constructor(){ // STATIC CLASS
	const _N$ = 'smartJ$UI';

	// :: static
	const _C$ = this; // self referencing

	let _p$ = console;

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

	//--
	const evFXStart = '(function(){ '; // keep function not arrow macro, is used in ajax evals, possible with $(this)
	const evFXEnd = ' })();';
	//--

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
	 * Display a Tooltip ; UI Component
	 *
	 * @requires jQuery
	 * @requires lib/js/jquery/jquery.tiptop.css
	 * @requires lib/js/jquery/jquery.tiptop.js
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
	 * @requires lib/js/jquery/dialog/simple-dialog.css
	 * @requires lib/js/jquery/dialog/simple-dialog.js
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
	const DialogAlert = (y_message_html, evcode, y_title, y_width, y_height) => { // ES6
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
	 * @requires lib/js/jquery/dialog/simple-dialog.css
	 * @requires lib/js/jquery/dialog/simple-dialog.js
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
	const DialogConfirm = (y_question_html, evcode, y_title, y_width, y_height) => { // ES6
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
	 * @requires lib/js/jquery/listselect/css/chosen.css
	 * @requires lib/js/jquery/listselect/chosen.jquery.js
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
		// evcode is taken from onBlur ; evcode params: elemID
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
			// unused: dimH
	//	}).on('change', (evt, params) => {
		}).on('chosen:hiding_dropdown', (evt, params) => {
			evt.preventDefault();
			let evcode = HtmlElement.attr('onBlur'); // onChange is always triggered, but useless on Multi-Select Lists on which we substitute it with the onBlur which is not triggered here but we catch and execute here
			if((evcode != undefined) && (evcode != 'undefined') && (evcode != '')) { // undef tests also for null
				try {
					if(typeof(evcode) === 'function') {
						evcode(elemID); // call :: sync params ui-selectlist
					} else { // sync :: eliminate javascript:
						evcode = _Utils$.stringTrim(evcode);
						evcode = evcode.replace('javascript:', '');
						evcode = _Utils$.stringTrim(evcode);
						if((evcode != null) && (evcode != '')) {
							eval(evFXStart + String(evcode) + evFXEnd); // sandbox
						} //end if
					} //end if else
				} catch(err) {
					_p$.error(_N$, 'SelectList', 'ERR: JS-Eval Failed on:', elemID, 'Details:', err);
				} //end try catch
			} //end if
		});
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
	 * @requires lib/js/jquery/datepicker/css/datepicker.css
	 * @requires lib/js/jquery/datepicker/datepicker.js
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
	const DatePickerInit = function(elemID, dateFmt, selDate, calStart, calMinDate, calMaxDate, noOfMonths, evcode) { // ES6
		//--
		const _m$ = 'DatePickerInit';
		//--
		// TODO: if possible show multiple months: noOfMonths
		//--
		// evcode params: date, altdate, inst, elemID
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
				altdate = date; // altdate is rewritten and re-converted below because format is different than standard if used
				try {
					altdate = _Date$.formatDate(String(dateFmt), new Date(date));
					if(/Invalid|NaN|Infinity/.test(altdate)) {
						altdate = date;
					} //end if
				} catch(err) {
					_p$.warn(_N$, _m$, 'Date conversion is not supported by the browser. Using ISO Date', err);
				} //end try catch
				$('#date-entry-' + elemID).val(altdate);
				//--
				if((evcode != undefined) && (evcode != 'undefined') && (evcode != '')) { // undef tests also for null
					try {
						if(typeof(evcode) === 'function') {
							evcode(date, altdate, inst, elemID); // call :: sync params ui-datepicker
						} else {
							eval(evFXStart + String(evcode) + evFXEnd); // sandbox
						} //end if else
					} catch(err) {
						_p$.error(_N$, _m$, 'ERR: JS-Eval Failed on:', elemID, 'Details:', err);
					} //end try catch
				} //end if
				//--
			}
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
	 * @requires lib/js/jquery/datepicker/css/datepicker.css
	 * @requires lib/js/jquery/datepicker/datepicker.js
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
	 * @requires lib/js/jquery/timepicker/css/jquery.timepicker.css
	 * @requires lib/js/jquery/timepicker/jquery.timepicker.js
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
	const TimePickerInit = function(elemID, hStart, hEnd, mStart, mEnd, mInterval, tmRows, evcode) { // ES6
		//--
		// evcode params: time, inst, elemID
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
				if(time != '') { //emulate on select because onSelect trigger twice (1 select hour + 2 select minutes), so if no time selected even if onClose means no onSelect !
					if((evcode != undefined) && (evcode != 'undefined') && (evcode != '')) { // undef tests also for null
						try {
							if(typeof(evcode) === 'function') {
								evcode(time, inst, elemID); // call :: sync params ui-timepicker
							} else {
								eval(evFXStart + String(evcode) + evFXEnd); // sandbox
							} //end if else
						} catch(err) {
							_p$.error(_N$, 'TimePickerInit', 'ERR: JS-Eval Failed on:', elemID, 'Details:', err);
						} //end try catch
					} //end if
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
	 * @requires lib/js/jquery/timepicker/css/jquery.timepicker.css
	 * @requires lib/js/jquery/timepicker/jquery.timepicker.js
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
	 * @requires lib/js/jquery/tabs/jquery.tabs.css
	 * @requires lib/js/jquery/tabs/jquery.tabs.js
	 *
	 * @memberof smartJ$UI
	 * @method TabsInit
	 * @static
	 *
	 * @param 	{String} 	tabs_id 			:: The HTML Element ID to bind to
	 * @param 	{Integer+} 	tab_selected 		:: The selected tab number ; Default is zero
	 * @param 	{Boolean} 	prevent_reload		:: *Optional* ; Default is FALSE ; If TRUE the tab content will not be reloaded after the first load
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const TabsInit = (tabs_id, tab_selected, prevent_reload) => { // ES6
		//--
		tab_selected = _Utils$.format_number_int(tab_selected, false);
		if(tab_selected < 0) {
			tab_selected = 0;
		} //end if
		//--
		return _Tab$.initTabs(tabs_id, prevent_reload, tab_selected);
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
	 * @requires lib/js/jquery/tabs/jquery.tabs.css
	 * @requires lib/js/jquery/tabs/jquery.tabs.js
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
		if(activation !== false) {
			activation = true;
		} //end if
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
	 * @requires lib/js/jquery/autosuggest/smart-suggest.css
	 * @requires lib/js/jquery/autosuggest/smart-suggest.js
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
	 * @param 	{Enum} 		selector 			:: Type: can be: 'single' or 'multilist'
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a text input or a text area
	 * @param 	{String} 	data_url 			:: The Data URL Prefix ; Ex: '?op=list&type=autosuggest'
	 * @param 	{String} 	param_src 			:: The Data URL Parameter to be appended as suffix to the above Data URL ; Ex: 'searchTerm' (will use: '?op=list&type=autosuggest&searchTerm=')
	 * @param 	{Integer+} 	min_term_len 		:: The minimum term search length ; expects a value between 0..255 ; will start searching only after the typed term length matches the value
	 * @param 	{Function} 	evcode 				:: The code to execute on select: (id, value, label, data) => {} or null
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const AutoCompleteField = function(single_or_multi, elem_id, data_url, param_src, min_term_len, evcode) { // ES6
		//--
		// evcode params: id, value, label, data
		//--
		if(!data_url) {
			_p$.warn(_N$, 'AutoCompleteField', 'The data_url is empty');
			return;
		} //end if
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
	 * @requires lib/js/jquery/datatables/datatables-responsive.css
	 * @requires lib/js/jquery/datatables/datatables-responsive.js
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
	const DataTableInit = function(elem_id, options) { // ES6
		//--
		if(!options || (typeof(options) !== 'object')) {
			options = {};
		} //end if
		//--
		if(!options.hasOwnProperty('responsive')) {
			options['responsive'] = false; // default not responsive (here responsive is something else ... will collapse rows under header with a + sign)
		} else {
			options['responsive'] = !(!options['responsive']); // force boolean
		} //end if
		//--
		if(!options.hasOwnProperty('filter')) {
			options['filter'] = true;
		} else {
			options['filter'] = !(!options['filter']); // force boolean
		} //end if
		//--
		if(!options.hasOwnProperty('sort')) {
			options['sort'] = true;
		} else {
			options['sort'] = !(!options['sort']); // force boolean
		} //end if
		//--
		if(!options.hasOwnProperty('paginate')) {
			options['paginate'] = true;
		} else {
			options['paginate'] = !(!options['paginate']); // force boolean
		} //end if
		//--
		if(!options.hasOwnProperty('pagesize')) {
			options['pagesize'] = 10;
		} else {
			options['pagesize'] = _Utils$.format_number_int(options['pagesize'], false); // force integer
			if(options['pagesize'] < 1) {
				options['pagesize'] = 1;
			} //end if
		} //end if
		//--
		const defPageSizes = [ 10, 25, 50, 100 ]; // default array
		if(!options.hasOwnProperty('pagesizes')) {
			options['pagesizes'] = defPageSizes;
		} else if(!Array.isArray(options['pagesizes'])) {
			options['pagesizes'] = defPageSizes;
		} //end if else
		//--
		if(!(!!options.paginate)) {
			options['pagesize'] = Number.MAX_SAFE_INTEGER;
			options['pagesizes'] = [ Number.MAX_SAFE_INTEGER ];
		} //end if
		//--
		if(!options.hasOwnProperty('classField')) {
			options['classField'] = 'ux-field'; // default class
		} //end if
		//--
		if(!options.hasOwnProperty('classButton')) {
			options['classButton'] = 'ux-button ux-button-small'; // default class
		} //end if
		//--
		if(!options.hasOwnProperty('classActiveButton')) {
			options['classActiveButton'] = 'ux-button-primary'; // default class
		} //end if
		//--
		let ordCols = []; // default array
		if(!options.hasOwnProperty('colorder')) {
			options['colorder'] = ordCols;
		} else if(!Array.isArray(options['colorder'])) {
			options['colorder'] = ordCols;
		} //end if else
		//--
		let defCols = [{}]; // default array
		if(!options.hasOwnProperty('coldefs')) {
			options['coldefs'] = defCols;
		} else if(!Array.isArray(options['coldefs'])) {
			options['coldefs'] = defCols;
		} //end if else
		//--
		const opts = {
			responsive: 					!!options.responsive,
			bFilter: 						!!options.filter,
			bSort: 							!!options.sort,
			bSortMulti: 					!!options.sort,
			order: 							Array.from(options.colorder),
			bPaginate: 						!!options.paginate,
			iDisplayLength: 				_Utils$.format_number_int(options.pagesize),
			aLengthMenu: 					Array.from(options.pagesizes), // , x => _Utils$.format_number_int(x)
			uxmHidePagingIfNoMultiPages: 	true,
			uxmCssClassLengthField: 		String(options.classField),
			uxmCssClassFilterField: 		String(options.classField),
			classes: {
				sPageButton: 				String(options.classButton),
				sPageButtonActive: 			String(options.classActiveButton)
			},
			columnDefs: 					Array.from(options.coldefs)
		};
		//--
		let HtmlElement = $('table#' + elem_id);
		//--
		HtmlElement.DataTable(opts);
		HtmlElement.data('smart-ui-elem-type', 'DataTable');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.DataTableInit = DataTableInit; // export

	//=======================================

	// Dependencies:
	//	jQuery, smartJ$Utils
	//	lib/js/jquery/datatables/datatables-responsive.css
	//	lib/js/jquery/datatables/datatables-responsive.js

	/**
	 * Apply a Filter over DataTable using a regular expression ; UI Component
	 * If a filter is applied oved data, will display just the filtered data and if no data match the filter will display no data
	 *
	 * @requires jQuery
	 * @requires lib/js/jquery/datatables/datatables-responsive.css
	 * @requires lib/js/jquery/datatables/datatables-responsive.js
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
	const DataTableColumnsFilter = function(elem_id, filterColNumber, regexStr) { // ES6
		//--
		let HtmlElement = $('table#' + elem_id);
		//--
		if(HtmlElement.data('smart-ui-elem-type') !== 'DataTable') {
			return null;
		} //end if
		//--
		let obj = HtmlElement.DataTable();
		//--
		let col = _Utils$.format_number_int(filterColNumber, false);
		if(col < 0) {
			col = 0;
		} //end if
		if(regexStr) {
			let testregex;
			try {
				testregex = new RegExp(String(regexStr));
			} catch(err) { // catch regex errors
				regexStr = '';
				_p$.warn(_N$, 'DataTableColumnsFilter', 'ERR: Filter Expression', regexStr, err);
			} //end try catch
			testregex = null;
		} //end if
		if(regexStr) {
			obj.columns(col).search(String(regexStr), true, false, true).draw();
		} else {
			obj.columns(col).search('').draw();
		} //end if else
		//--
		return HtmlElement;
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
