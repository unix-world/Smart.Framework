
// JS: Smart Grid Object (SlickGrid + NavPager)
// (c) 2006-2019 unix-world.org - all rights reserved
// v.20210312

// DEPENDS: jQuery, jQuery-Growl, SmartJS_CoreUtils, SmartJS_BrowserUtils, jQuery SlickGrid, jQuery SimplePagination

//================== [OK:evcode]

var Smart_Grid = function(gridID, infoListID, jsonURL, cookieStorePrefix, sortColumn, sortDir, sortType, formNameID, showLoadNotification, notifCssGrowlClass, evcode) { // OBJECT-CLASS

// -> dynamic (new)

var _class = this; // self referencing

//-- SETTINGS
//gridID 			= 'MyGrid'; 			// the HTML ID of the Grid Element (div)
//infoListID 		= 'MyListInfo'; 		// the HTML ID of the InfoList Element
//jsonURL 			= 'data/grid/json';		// the URL to Json Data to load into Grid
//cookieStorePrefix = 'myCookie';			// the Cookie name to be used to save Grid status
//sortColumn 		= 'id'; 				// sorting field;
//sortDir 			= 'ASC'; 				// sorting direction: ASC / DESC
//sortType 			= ''; 					// sorting type: 'text' / 'numeric' (applies only with clientSort and is not used for Server-Side sort);
//formNameID 		= 'filtering'; 			// filter form HTML ID or null
//--

//-- CHECKS
if((typeof jsonURL == 'undefined') || (jsonURL == null) || (jsonURL == '')) {
	throw 'ERROR: SmartGrid: Invalid jsonURL !';
} //end if
if((typeof cookieStorePrefix == 'undefined') || (cookieStorePrefix == null) || (cookieStorePrefix == '')) {
	throw 'ERROR: SmartGrid: Invalid cookieStorePrefix !';
} //end if
//--
if((typeof sortColumn == 'undefined') || (sortColumn == null)) {
	throw 'ERROR: SmartGrid: Invalid sortColumn !';
} //end if
if((typeof sortDir == 'undefined') || (sortDir == null)) {
	throw 'ERROR: SmartGrid: Invalid sortDir !';
} //end if
if((typeof sortType == 'undefined') || (sortType == null)) {
	throw 'ERROR: SmartGrid: Invalid sortType !';
} //end if
//--

//-- PUBLIC-PROTECTED (read-only) VARS
this.grid 		= null;
this.navPager 	= null;
this.data 		= [];
//--

//-- PRIVATE
var itemsPerPage 		= 1;
var itemsTotal 			= 0;
var crrOffset 			= 0;
var cookieURL 			= String(cookieStorePrefix + '_URL');
var cookieWidths 		= String(cookieStorePrefix + '_Wdts');
var crrSavedURL 		= '';
var clientSort 			= false;
var saved_sortColumn 	= sortColumn;
var saved_sortDir 		= sortDir;
var saved_sortType 		= sortType;
//--

//-- reset the Grid
this.resetGrid = function() {
	//--
	SmartJS_BrowserUtils.setCookie(cookieURL, '&'); // delete cookie is not good enough because requires a page refresh, thus the solution is to change cookie value ...
	//--
	sortColumn = saved_sortColumn;
	sortDir = saved_sortDir;
	sortType = saved_sortType;
	offs = 0;
	crrSavedURL = '';
	//--
} //END FUNCTION
//--

//-- reload the Grid
this.reloadGrid = function(yredirect) {
	//--
	if((typeof yredirect != 'undefined') && (yredirect != '') && (yredirect !== null)) {
		self.location = String(yredirect);
	} else {
		self.location = self.location; // self.location.reload(false); does not work
	} //end if
	//--
} //END FUNCTION
//--

//-- loads Data into Grid
this.loadGridData = function(offs) {
	//--
	if(_class.grid === null) {
		console.error('ERROR: Smart_Grid :: Grid was not initialized ... use Smart_Grid.initGrid(columns, options); to init this grid first !');
		return;
	} //end if
	//--
	if(showLoadNotification !== false) {
		SmartJS_BrowserUtils.Overlay_Show('<div><center><b>... loading data ...</b></center></div>', '', notifCssGrowlClass);
	} //end if
	//--
	var fdata = '';
	if((typeof formNameID != 'undefined') && (formNameID != 'undefined') && (formNameID !== null) && (formNameID != '')) {
		fdata = String(jQuery('#' + formNameID).serialize());
	} //end if
	//--
	var crrCookieURL = SmartJS_BrowserUtils.getCookie(cookieURL);
	//--
	if(location.hash) { // ex: #!&id=test
		crrSavedURL = String(location.hash.substring(2)); // remove #! from hash
		//location.hash=''; // this leaves the # at the end and breaks self.location = ...
		var clean_uri = String(location.protocol + '//' + location.host + location.pathname + location.search); // location.host returns also host and also the port
		try {
			self.history.replaceState({}, document.title, String(clean_uri));
		} catch(err) {
			self.location = String(clean_uri);
		} //end try catch
	} else if((crrSavedURL === '') && (crrCookieURL != '') && (crrCookieURL != '&')) {
		crrSavedURL = String(crrCookieURL);
	} else {
		crrSavedURL = 'sortby=' + encodeURIComponent(sortColumn) + '&sortdir=' + encodeURIComponent(sortDir) + '&sorttype=' + encodeURIComponent(sortType) + '&ofs=' + parseInt(offs);
		if(crrCookieURL != '&') {
			crrSavedURL += '&' + fdata;
		} //end if
	} //end if else
	//--
	SmartJS_BrowserUtils.setCookie(cookieURL, crrSavedURL);
	//--
	var crrURL = String(jsonURL + '&' + crrSavedURL);
	//--
	SmartJS_BrowserUtils.Ajax_XHR_Request_From_URL(crrURL, 'POST', 'json').done(function(msg) { // {{{JQUERY-AJAX}}}
		//--
		if((msg.hasOwnProperty('status')) && (msg.status === 'OK') && (msg.hasOwnProperty('rowsList')) && (msg.hasOwnProperty('crrOffset')) && (msg.hasOwnProperty('itemsPerPage')) && (msg.hasOwnProperty('totalRows'))) {
			//--
			_class.data = msg.rowsList;
			crrOffset = parseInt(msg.crrOffset);
			itemsPerPage = parseInt(msg.itemsPerPage);
			itemsTotal = parseInt(msg.totalRows);
			sortColumn = String(msg.sortBy);
			sortDir = String(msg.sortDir);
			sortType = String(msg.sortType);
			clientSort = false;
			if(msg.hasOwnProperty('clientSort')) { // if the server-side did not provide the sorting
				if(msg.clientSort === sortColumn) {
					clientSort = true;
				} //end if
			} //end if
			//--
			if((formNameID !== null) && (formNameID != '')) {
				if(msg.hasOwnProperty('filter')) {
					jQuery.each(msg.filter, function(index, value) {
						try {
							jQuery('#' + formNameID).find(':input[name=' + encodeURIComponent(index) + ']').val(value); // https://api.jquery.com/input-selector/
						} catch(err) {
							console.log('WARNING: Smart Grid Failed to Set a Filter value: [' + value + '] on Control: [' + index + ']' + '\nDetails: ' + err);
						} //end try catch
					});
				} //end if
			} //end if
			//--
			if(itemsPerPage <= 0) {
				console.error('ERROR: Smart_Grid :: Invalid Value for Smart_Grid.itemsPerPage ... Must be > 0 !');
			} else {
				if(_class.navPager !== null) {
					_class.navPager.pagination('updateItemsOnPage', itemsPerPage);
					_class.navPager.pagination('updateItems', itemsTotal);
					_class.navPager.pagination('drawPage', (parseInt(crrOffset / itemsPerPage) + 1));
					_class.navPager.pagination('redraw');
				} //end if
			} //end if else
			//-- sort
			if(clientSort === true) { // if this is not already done via server-side, do it client-side
				if(sortType === 'numeric') {
					_class.data.sort(SmartJS_CoreUtils.numericSort(sortColumn));
				} else {
					_class.data.sort(SmartJS_CoreUtils.textSort(sortColumn));
				} //end if else
				if(sortDir === 'DESC') {
					_class.data.reverse(); // this is made server-side
				} //end if else
			} //end if
			//--
			_class.grid.setSelectedRows([]);
			_class.grid.invalidate();
		//	_class.grid.removeAllRows(); // invalidate already does this
			if(sortDir === 'DESC') {
				_class.grid.setSortColumn(sortColumn, false);
			} else {
				_class.grid.setSortColumn(sortColumn, true);
			} //end if else
			_class.grid.setData(_class.data);
			_class.grid.render();
			//--
			var tLstRec = parseInt(crrOffset + itemsPerPage);
			if(tLstRec > itemsTotal) {
				tLstRec = itemsTotal;
			} //end if
			if((typeof infoListID != 'undefined') && (infoListID != 'undefined') && (infoListID !== null) && (infoListID != '')) {
				jQuery('#' + infoListID).text(String(crrOffset + ' - ' + tLstRec + ' / ' + itemsTotal));
			} //end if
			//--
			if((typeof evcode != 'undefined') && (evcode != 'undefined') && (evcode != null) && (evcode != '')) {
				try {
					if(typeof evcode === 'function') {
						evcode(gridID, grid, navPager, data, msg, formNameID, infoListID); // call
					} else {
						eval('(function(){ ' + evcode + ' })();'); // sandbox
					} //end if else
				} catch(err) {
					console.error('ERROR: JS-Eval Error on Smart Grid CallBack Function' + '\nDetails: ' + err);
				} //end try catch
			} //end if
			//--
			SmartJS_BrowserUtils.Overlay_Hide();
			//--
		} else {
			//--
			SmartJS_BrowserUtils.alert_Dialog('ERROR: Smart_Grid :: Invalid Data Format while trying to get Data via AJAX !' + '<hr>' + 'Details:<br>' + msg.status + ': ' + msg.error, '', 'ERROR', 550, 225);
			//--
			SmartJS_BrowserUtils.Overlay_Hide();
			//--
		} //end if else
		//--
	}).fail(function(msg) {
		//--
		SmartJS_BrowserUtils.alert_Dialog('ERROR: Smart_Grid :: Invalid Server Response via AJAX !' + '<hr>' + msg.responseText, '', 'ERROR', 750, 425);
		//--
		SmartJS_BrowserUtils.Overlay_Hide();
		//--
	});
	//--
} //END FUNCTION
//--

//-- inits the Grid
this.initGrid = function(columns, options) {
	//--
	_class.grid = new Slick.Grid(jQuery('#' + gridID), _class.data, columns, options);
	//--
	var theSavedWidths = SmartJS_BrowserUtils.getCookie(cookieWidths);
	if(theSavedWidths != '') {
		var arrSavedWidths = theSavedWidths.split(';');
		jQuery.each(columns, function(index, value){
			//console.error(columns[index].width);
			var theCrrWidth = parseInt(arrSavedWidths[index]);
			if(theCrrWidth < 5) {
				theCrrWidth = 5
			} else if(theCrrWidth > 1500) {
				theCrrWidth = 1500
			} //end if else
			columns[index].width = theCrrWidth;
		});

	} //end if
	//--
	_class.grid.autosizeColumns();
	//--
	_class.grid.onColumnsResized = function(colWidths) {
		var savedWidths = '';
		for (var j = 0; j < colWidths.length; j++) {
			savedWidths += colWidths[j] + ';';
		} //end for
		SmartJS_BrowserUtils.setCookie(cookieWidths, savedWidths);
	};
	//--
	_class.grid.onSort = function(sortCol, sortAsc) {
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
		_class.loadGridData(0);
		//--
	};
	//--
} //END FUNCTION
//--

//-- get current offset
this.getOffset = function() {
	//--
	return parseInt(crrOffset);
	//--
} //END FUNCTION
//--

//-- inits the NavPager (optional)
this.initNavPager = function(pager_id, pagerDisplayPages) {
	//--
	if((typeof pagerDisplayPages == 'undefined') || (pagerDisplayPages == null) || (pagerDisplayPages < 1) || (pagerDisplayPages > 50)) {
		pagerDisplayPages = 5;
	} //end if
	//--
	_class.navPager = jQuery('#' + pager_id);
	//--
	_class.navPager.pagination({
		ellipseClass: 'ux-field',
		cssStyle: 'light-theme',
		displayedPages: pagerDisplayPages,
		edges: pagerDisplayPages,
		onPageClick: function(pageNumber, event) {
			if(typeof event != 'undefined') {
				if(event.type == 'click') {
					crrOffset = parseInt((pageNumber - 1) * itemsPerPage);
					_class.loadGridData(crrOffset);
				} //end if
			} //end if
			return false;
		},
		itemsOnPage: itemsPerPage,
		items: itemsTotal
	});
	//--
} //END FUNCTION
//--

//-- handle the rows selector and retuns array as [ rowNum => data[rowNum][selectedDataColumn], ... ]
this.getSelectedRows = function(selectedDataColumn) {
	//--
	// the returning array can be iterated as: $.each(a, function(key, value) { if(typeof value != 'undefined') { console.log(key + ': ' + value); } });
	//--
	var arr_sel_items = [];
	//--
	if(_class.data.length <= 0) {
		return arr_sel_items;
	} //end if
	//--
	var rows_selected = _class.grid.getSelectedRows();
	if(rows_selected.length <= 0) {
		return arr_sel_items;
	} //end if
	//--
	var theRowIndexSelected = -1;
	for(var i=0; i<rows_selected.length; i++) {
		if(rows_selected[i] != undefined) {
			theRowIndexSelected = parseInt(rows_selected[i]);
			if((theRowIndexSelected >= 0) && (theRowIndexSelected < _class.data.length)) {
				arr_sel_items[''+theRowIndexSelected] = _class.data[theRowIndexSelected][selectedDataColumn];
			} //end if
		} //end if
	} //end for
	//--
	return arr_sel_items;
	//--
} //END FUNCTION
//--

} //END OBJECT-CLASS
//==

/* Sample Server-Side Data Definition:
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
