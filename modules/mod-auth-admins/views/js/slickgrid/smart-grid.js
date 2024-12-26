
// JS: Smart Grid Object (SlickGrid + NavPager)
// (c) 2006-2023 unix-world.org - all rights reserved
// v.20231021

// DEPENDS: jQuery, smartJ$Utils, smartJ$Browser, Growl (jQuery Gritter / jQuery Toastr), jQuery SlickGrid, jQuery SimplePagination

//================== [ES6]

if(typeof(SmartGrid) != 'undefined') { // fix for load by ajax tabs

	console.warn('SmartGrid class has been already loaded !');

} else {

const SmartGrid = class{constructor(gridID, infoListID, jsonURL, cookieStorePrefix, sortColumn, sortDir, sortType, formNameID=null, showLoadNotification=true, notifCssGrowlClass=null, evcode=null) { // OBJECT-CLASS
	const _N$ = 'SmartGrid';

	// -> dynamic (new)
	const _C$ = this; // self referencing

	const _p$ = console;

	// class security: below

	const $ = jQuery; // jQuery referencing

	const _Utils$ = smartJ$Utils;
	const _BwUtils$ = smartJ$Browser;

	const _Grid$ = Slick.Grid;


	//-- SETTINGS
	//gridID 					= 'MyGrid'; 			// the HTML ID of the Grid Element (div)
	//infoListID 				= 'MyListInfo'; 		// the HTML ID of the InfoList Element
	//jsonURL 					= 'data/grid/json';		// the URL to Json Data to load into Grid
	//cookieStorePrefix 		= 'myCookie';			// the Cookie name to be used to save Grid status
	//sortColumn 				= 'id'; 				// sorting field;
	//sortDir 					= 'ASC'; 				// sorting direction: ASC / DESC
	//sortType 					= ''; 					// sorting type: 'text' / 'numeric' (applies only with clientSort and is not used for Server-Side sort);
	//--
	//formNameID 				= 'filtering'; 			// filter form HTML ID or null
	//showLoadNotification 		= true; 				// show loading growl or not
	//notifCssGrowlClass 		= true;					// if show loading growl the css class for it or default is 'white'
	//evcode 					= null; 				// the js code as fucntion to execute on load data
	//--

	//-- CHECKS
	if((jsonURL == undefined) || (jsonURL == '')) { // test undefined is also for null
		throw 'ERR: ' + _N$ + ': Invalid jsonURL !';
		return;
	} //end if
	if((cookieStorePrefix == undefined) || (cookieStorePrefix == '')) { // test undefined is also for null
		throw 'ERR: ' + _N$ + ': Invalid cookieStorePrefix !';
		return;
	} //end if
	//--
	if(sortColumn == undefined) { // test undefined is also for null
		throw 'ERR: ' + _N$ + ': Invalid sortColumn !';
		return;
	} //end if
	if(sortDir == undefined) { // test undefined is also for null
		throw 'ERR: ' + _N$ + ' SmartGrid: Invalid sortDir !';
		return;
	} //end if
	if(sortType == undefined) { // test undefined is also for null
		throw 'ERR: ' + _N$ + ': Invalid sortType !';
		return;
	} //end if
	//--

	//-- PUBLIC PROPS
	_C$.grid 		= null;
	_C$.navPager 	= null;
	_C$.data 		= [];
	//--

	//-- PRIVATE PROPS
	let offs 				= 0;
	let itemsPerPage 		= 1;
	let itemsTotal 			= 0;
	let crrOffset 			= 0;
	let cookieURL 			= String(String(cookieStorePrefix) + '_URL');
	let cookieWidths 		= String(String(cookieStorePrefix) + '_Wdts');
	let crrSavedURL 		= '';
	let clientSort 			= false;
	let saved_sortColumn 	= sortColumn;
	let saved_sortDir 		= sortDir;
	let saved_sortType 		= sortType;
	//--

	//== METHODS

	//-- reset the Grid
	const resetGrid = function() {
		//--
		_BwUtils$.setCookie(cookieURL, '&'); // delete cookie is not good enough because requires a page refresh, thus the solution is to change cookie value ...
		//--
		sortColumn = saved_sortColumn;
		sortDir = saved_sortDir;
		sortType = saved_sortType;
		offs = 0;
		crrSavedURL = '';
		//--
	}; //END
	_C$.resetGrid = resetGrid; // export
	//--

	//-- loads Data into Grid
	const loadGridData = function(offs=0) {
		//--
		if(_C$.grid === null) {
			_p$.error(_N$, 'ERR: Grid was not initialized ... use initGrid(columns, options); first !');
			return;
		} //end if
		//--
		if(showLoadNotification !== false) {
			_BwUtils$.OverlayShow('<div><center><b>... loading data ...</b></center></div>', '', notifCssGrowlClass);
		} //end if
		//--
		let fdata = '';
		if((formNameID != undefined) && (formNameID != '')) {
			fdata = String($('#' + String(formNameID)).serialize());
		} //end if
		//--
		let crrCookieURL = _BwUtils$.getCookie(cookieURL);
		//--
		if(location.hash) { // ex: #!&id=test
			crrSavedURL = String(location.hash.substring(2)); // remove #! from hash
			//location.hash=''; // it leaves the # at the end and breaks self.location = ...
			let clean_uri = String(location.protocol + '//' + location.host + location.pathname + location.search); // location.host returns also host and also the port
			try {
				self.history.replaceState({}, document.title, String(clean_uri));
			} catch(err) {
				self.location = String(clean_uri);
			} //end try catch
		} else if((crrSavedURL === '') && (crrCookieURL != '') && (crrCookieURL != '&')) {
			crrSavedURL = String(crrCookieURL);
		} else {
			crrSavedURL = 'sortby=' + _Utils$.escape_url(sortColumn) + '&sortdir=' + _Utils$.escape_url(sortDir) + '&sorttype=' + _Utils$.escape_url(sortType) + '&ofs=' + _Utils$.format_number_int(offs, false);
			if(crrCookieURL != '&') {
				crrSavedURL += '&' + fdata;
			} //end if
		} //end if else
		//--
		_BwUtils$.setCookie(cookieURL, crrSavedURL);
		//--
		let crrURL = String(jsonURL + '&' + crrSavedURL);
		//--
		_BwUtils$.AjaxRequestFromURL(crrURL, 'POST', 'json').done((msg) => { // {{{JQUERY-AJAX}}}
			//--
			if((msg.status !== undefined) && (msg.status === 'OK') && (msg.rowsList !== undefined) && (msg.crrOffset !== undefined) && (msg.itemsPerPage !== undefined) && (msg.totalRows !== undefined)) {
				//--
				_C$.data = msg.rowsList;
				crrOffset = _Utils$.format_number_int(msg.crrOffset, false);
				itemsPerPage = _Utils$.format_number_int(msg.itemsPerPage, false);
				itemsTotal = _Utils$.format_number_int(msg.totalRows, false);
				sortColumn = String(msg.sortBy);
				sortDir = String(msg.sortDir);
				sortType = String(msg.sortType);
				clientSort = false;
				if(msg.clientSort !== undefined) { // if the server-side did not provide the sorting
					if(msg.clientSort === sortColumn) {
						clientSort = true;
					} //end if
				} //end if
				//--
				if((formNameID !== null) && (formNameID != '')) {
					if(msg.filter !== undefined) {
						$.each(msg.filter, (index, value) => {
							try {
								$('#' + String(formNameID)).find(':input[name=' + _Utils$.escape_url(index) + ']').val(value); // https://api.jquery.com/input-selector/
							} catch(err) {
								_p$.warn(_N$, 'WARN: Grid Failed to Set a Filter value: [' + value + '] on Control: [' + index + ']' + '\nDetails:', err);
							} //end try catch
						});
					} //end if
				} //end if
				//--
				if(itemsPerPage <= 0) {
					_p$.error(_N$, 'ERR: Invalid Value for Pagination itemsPerPage ... Must be > 0 !');
				} else {
					if(_C$.navPager !== null) {
						_C$.navPager.pagination('updateItemsOnPage', itemsPerPage);
						_C$.navPager.pagination('updateItems', itemsTotal);
						_C$.navPager.pagination('drawPage', (Math.round(_Utils$.format_number_float(_Utils$.format_number_int(crrOffset, false) / _Utils$.format_number_int(itemsPerPage, false))) + 1));
						_C$.navPager.pagination('redraw');
					} //end if
				} //end if else
				//-- sort
				if(clientSort === true) { // if it is not already done via server-side, do it client-side
					if(sortType === 'numeric') {
						_C$.data.sort(_Utils$.numericSort(sortColumn));
					} else {
						_C$.data.sort(_Utils$.textSort(sortColumn, true)); // use locale sorting
					} //end if else
					if(sortDir === 'DESC') {
						_C$.data.reverse(); // it is made server-side
					} //end if else
				} //end if
				//--
				_C$.grid.setSelectedRows([]);
				_C$.grid.invalidate();
			//	_C$.grid.removeAllRows(); // invalidate already does it
				if(sortDir === 'DESC') {
					_C$.grid.setSortColumn(sortColumn, false);
				} else {
					_C$.grid.setSortColumn(sortColumn, true);
				} //end if else
				_C$.grid.setData(_C$.data);
				_C$.grid.render();
				//--
				let tLstRec = _Utils$.format_number_int(crrOffset, false) + _Utils$.format_number_int(itemsPerPage, false);
				if(!_Utils$.isFiniteNumber(tLstRec)) {
					tLstRec - 0;
				} //end if
				if(tLstRec > itemsTotal) {
					tLstRec = itemsTotal;
				} //end if
				if((infoListID != undefined) && (infoListID != '')) {
					$('#' + String(infoListID)).text(String(crrOffset + ' - ' + tLstRec + ' / ' + itemsTotal));
				} //end if
				//--
				_Utils$.evalJsFxCode( // EV.CTX
					_N$ + '.loadGridData',
					(typeof(evcode) === 'function' ?
						() => {
							'use strict'; // req. strict mode for security !
							(evcode)(gridID, _C$.grid, _C$.navPager, _C$.data, msg, formNameID, infoListID);
						} :
						() => {
							'use strict'; // req. strict mode for security !
							!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: gridID, _C$.grid, _C$.navPager, _C$.data, msg, formNameID, infoListID
						}
					)
				);
				//--
				_BwUtils$.OverlayHide();
				//--
			} else {
				//--
				_BwUtils$.AlertDialog('ERROR: ' + _N$ + ' :: Invalid Data Format while trying to get Data via AJAX !' + '<hr>' + 'Details:<br>' + String((msg.status !== undefined) ? msg.status : '') + ': ' + String((msg.error !== undefined) ? msg.error : ''), '', 'ERROR', 550, 250);
				//--
				_BwUtils$.OverlayHide();
				//--
			} //end if else
			//--
		}).fail((msg) => {
			//--
			_BwUtils$.AlertDialog('ERROR: ' + _N$ + ' :: Invalid Server Response via AJAX !' + '<hr>' + msg.responseText, '', 'ERROR', 750, 425);
			//--
			_BwUtils$.OverlayHide();
			//--
		});
		//--
	}; //END
	_C$.loadGridData = loadGridData; // export
	//--

	//-- inits the Grid
	const initGrid = function(columns, options={}) {
		//--
		_C$.grid = new _Grid$($('#' + String(gridID)), _C$.data, columns, options);
		//--
		let theSavedWidths = _BwUtils$.getCookie(cookieWidths);
		if(theSavedWidths != '') {
			let arrSavedWidths = theSavedWidths.split(';');
			$.each(columns, (index, value) => {
				//_p$.log(_N$, columns[index].width);
				let theCrrWidth = _Utils$.format_number_int(parseInt(arrSavedWidths[index])); // must parse from string with px (ex: 100px)
				if(theCrrWidth < 5) {
					theCrrWidth = 5
				} else if(theCrrWidth > 1500) {
					theCrrWidth = 1500
				} //end if else
				columns[index].width = theCrrWidth;
			});
		} //end if
		//--
		_C$.grid.autosizeColumns();
		//--
		_C$.grid.onColumnsResized = (colWidths) => {
			let savedWidths = '', j = 0;
			for(let j=0; j < colWidths.length; j++) {
				savedWidths += String(colWidths[j]) + ';';
			} //end for
			_BwUtils$.setCookie(cookieWidths, savedWidths);
		};
		//--
		_C$.grid.onSort = (sortCol, sortAsc) => {
			//--
			sortColumn = sortCol.field;
			//--
			if(sortCol.sortNumeric) {
				sortType = 'numeric';
			} else {
				sortType = 'text';
			} //end if else
			//--
			if(sortAsc !== true) {
				sortDir = 'DESC';
			} else {
				sortDir = 'ASC';
			} //end if
			//--
			loadGridData(0);
			//--
		};
		//--
	}; //END
	_C$.initGrid = initGrid; // export
	//--

	//-- get current offset
	const getOffset = () => {
		//--
		return _Utils$.format_number_int(parseInt(crrOffset, 10) || 0); // must parse from string with px (ex: 100px)
		//--
	}; //END
	_C$.getOffset = getOffset; // export
	//--

	//-- inits the NavPager (optional)
	const initNavPager = function(pager_id, pagerDisplayPages=5) {
		//--
		if((pager_id == undefined) || (pager_id == '')) { // undefined tests also for null
			throw 'ERR: ' + _N$ + ': Invalid PagerID on initNavPager !';
			return;
		} //end if
		//--
		pagerDisplayPages = _Utils$.format_number_int(pagerDisplayPages, false);
		if((pagerDisplayPages < 1) || (pagerDisplayPages > 50)) {
			pagerDisplayPages = 5; // default
		} //end if
		//--
		_C$.navPager = $('#' + String(pager_id));
		if(typeof(_C$.navPager.pagination) == 'undefined') {
			throw 'ERR: ' + _N$ + ': NavPager is N/A on initNavPager !';
			return;
		} //end if
		//--
		_C$.navPager.pagination({
			ellipseClass: 'ux-field',
			cssStyle: 'light-theme',
			displayedPages: pagerDisplayPages,
			edges: pagerDisplayPages,
			onPageClick: (pageNumber, event) => {
				if(event != undefined) {
					if(event.type == 'click') {
						crrOffset = _Utils$.format_number_int((pageNumber - 1) * itemsPerPage, false);
						loadGridData(crrOffset);
					} //end if
				} //end if
				return false;
			},
			itemsOnPage: itemsPerPage,
			items: itemsTotal
		});
		//--
	}; //END
	_C$.initNavPager = initNavPager;
	//--

	//-- handle the rows selector and retuns array as [ rowNum => data[rowNum][selectedDataColumn], ... ]
	const getSelectedRows = function(selectedDataColumn) {
		//--
		// the returning array can be iterated as: $.each(a, (key, value) => { if(value != 'undefined') { _p$.log(key, ':', value); } });
		//--
		if(!selectedDataColumn) {
			return [];
		} //end if
		//--
		let arr_sel_items = [];
		//--
		if(_C$.data.length <= 0) {
			return arr_sel_items;
		} //end if
		//--
		let rows_selected = _C$.grid.getSelectedRows();
		if(rows_selected.length <= 0) {
			return arr_sel_items;
		} //end if
		//--
		let theRowIndexSelected = -1;
		for(let i=0; i<rows_selected.length; i++) {
			if(rows_selected[i] != undefined) {
				theRowIndexSelected = _Utils$.format_number_int(rows_selected[i], false);
				if((theRowIndexSelected >= 0) && (theRowIndexSelected < _C$.data.length)) {
					arr_sel_items[String(theRowIndexSelected)] = _C$.data[theRowIndexSelected][selectedDataColumn];
				} //end if
			} //end if
		} //end for
		//--
		return arr_sel_items;
		//--
	}; //END
	_C$.getSelectedRows = getSelectedRows;
	//--

	//-- reload the Grid
	const reloadGrid = function(redirect=null) {
		//--
		if((redirect != undefined) && (redirect != '')) { // test undefined is also for null
			self.location = String(redirect);
		} else {
			self.location = self.location; // self.location.reload(false); does not work
		} //end if
		//--
	}; //END
	_C$.reloadGrid = reloadGrid;
	//--

}}; //END OBJECT-CLASS

Object.freeze(SmartGrid); // this must be cloned with new, thus the new object will not be frozen !

window.SmartGrid = SmartGrid; // global export

} //end if

//==

/* Sample Server-Side Data Response by Ajax:
{
	"status":"OK",
	"crrOffset":0,
	"itemsPerPage":25,
	"sortBy":"id",
	"sortDir":"ASC",
	"sortType":"", // "numeric" or "text" if clientSort
	"filter":{
		"id":"",
		"name":"",
		//... populate the rest of filters
	},
	"totalRows":5000,
	"rowsList":[
		{
			"id":"1",
			"name":"Name 1"
		},
		{
			"id":"2",
			"name":"Name 2"
		},
		// ...
	]
}
*/

// #END
