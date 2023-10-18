
// jQuery Smart DataTables
// (c) 2006-2023 unix-world.org
// License: BSD
// v.20231012

// DEPENDS: jQuery, smartJ$Utils, DataTable (datatables-responsive.js)
// REQUIRES-CSS: datatables-responsive.css

//==================================================================
//==================================================================

//================== [ES6]

const SmartDataTables = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartDataTables';

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


	/**
	 * Creates a DataTable from a regular HTML Table ; UI Component
	 * DataTables is a table enhancing plug-in for the jQuery Javascript library,
	 * adding sorting, paging and filtering abilities to plain HTML tables.
	 *
	 * @hint Add advanced interaction controls to HTML tables
	 *
	 * @requires jQuery
	 * @requires datatables-responsive.css
	 * @requires datatables-responsive.js
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
	 * SmartDataTables.DataTableInit('myTable', {
	 *		columns: null, // or array of column definitions ; see as example in mod auth admins / manage tokens view
	 *		data: null, // or array of data ; see as example in mod auth admins / manage tokens view
	 * 		responsive: false, // if TRUE on responsive mode columns may become fluid on small screens and have a clickable expand
	 * 		insensitive: false, // by default will do case insensitive search ; set this to true to use case sensitive search
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
	 *		coldefs: [ // these can be set also by columns option above !
	 *			{ // column one
	 *				targets: 0,
	 *				width: '25px',
	 * 				render: (data, type, row) => { // 4th param is: meta
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
	 * @memberof SmartDataTables
	 * @method DataTableInit
	 * @static
	 *
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a HTML <table></table>
	 * @param 	{Object} 	options 			:: The Options for DataTable
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const DataTableInit = function(elem_id, options=null) { // ES6
		//--
		const _m$ = 'DataTableInit';
		//--
		if(typeof(DataTable) == undefined) {
			_p$.error(_N$, _m$, 'DataTable is not loaded ...');
			return;
		} //end if
		//--
		elem_id = _Utils$.stringPureVal(elem_id, true); // +trim
		elem_id = _Utils$.create_htmid(elem_id);
		if(elem_id == '') {
			_p$.warn(_N$, _m$, 'Invalid or Empty Element ID');
			return;
		} //end if
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
		if(!options.hasOwnProperty('insensitive')) {
			options['insensitive'] = true; // by default
		} else {
			options['insensitive'] = !(!options['insensitive']); // force boolean
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
			columns: 						null,
			data: 							null,
			responsive: 					!!options.responsive,
			bFilter: 						!!options.filter,
			bSort: 							!!options.sort,
			bSortMulti: 					!!options.sort,
			order: 							Array.from(options.colorder),
			search: 						{
				caseInsensitive: 			!!options.insensitive, // defaults will do insensitive case search
				regex: 						false,
			},
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
		if(options.hasOwnProperty('columns') && options.hasOwnProperty('data')) { // columns
			if(Array.isArray(options['columns']) && Array.isArray(options['data'])) {
				opts.columns = Array.from(options.columns);
				opts.data = Array.from(options.data);
			} //end if
		} //emd if
		//--
		let HtmlElement = $('table#' + elem_id);
		//--
		HtmlElement.DataTable(opts);
		HtmlElement.data('smart-js-elem-type', 'DataTable');
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.DataTableInit = DataTableInit; // export


	/**
	 * Apply a Filter over DataTable using a regular expression ; UI Component
	 * If a filter is applied oved data, will display just the filtered data and if no data match the filter will display no data
	 *
	 * @requires jQuery
	 * @requires datatables-responsive.css
	 * @requires datatables-responsive.js
	 *
	 * @example
	 * // filter a DataTable by column no.1 (2nd column, starting from zero) and display only lines where column no.1 have the value: 'warning' or 'error'
	 * SmartDataTables.DataTableColumnsFilter('myTable', 1, '^(warning|error)$');
	 *
	 * @memberof SmartDataTables
	 * @method DataTableColumnsFilter
	 * @static
	 *
	 * @param 	{String} 	elem_id 			:: The HTML Element ID to bind to ; must be a DataTable already previous initiated with SmartDataTables.DataTableInit()
	 * @param 	{Integer+} 	filterColNumber 	:: The DataTable column number 0..n
	 * @param 	{Regex} 	regexStr 			:: A valid Regex Partial Expression String (without enclosing slashes /../, as string) to filter the column values ; ex: '^(val1|val\-2)$'
	 * @return 	{Object} 						:: The jQuery HTML Element
	 */
	const DataTableColumnsFilter = function(elem_id, filterColNumber, regexStr) { // ES6
		//--
		const _m$ = 'DataTableColumnsFilter';
		//--
		if(typeof(DataTable) == undefined) {
			_p$.error(_N$, _m$, 'DataTable is not loaded ...');
			return;
		} //end if
		//--
		elem_id = _Utils$.stringPureVal(elem_id, true); // +trim
		elem_id = _Utils$.create_htmid(elem_id);
		if(elem_id == '') {
			_p$.warn(_N$, _m$, 'Invalid or Empty Element ID');
			return;
		} //end if
		//--
		let HtmlElement = $('table#' + elem_id);
		//--
		if(HtmlElement.data('smart-js-elem-type') !== 'DataTable') {
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
				_p$.warn(_N$, _m$, 'ERR: Filter Expression', regexStr, err);
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


}}; //END CLASS


SmartDataTables.secureClass(); // implements class security

window.SmartDataTables = SmartDataTables; // global export


//==================================================================
//==================================================================


// #END

