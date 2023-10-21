/**
 * (c) 2009-2010 Michael Leibman (michael.leibman@gmail.com)
 * http://github.com/mleibman/slickgrid
 * Distributed under MIT license
 *
 * DEPENDS: jQuery, smartJ$Utils
 * Syntax: ES6
 *
 * (c) 2012-2023 unix-world.org
 * SlickGrid v1.4.3.2.smart.20231021 : Smart.Framework
 * 		List of changes / fixes (unixman):
 * 			1. 	changed the style 'ui-state-active' with 'slickgrid-row-selected'
 *			2. 	bugfix for row into asyncPostRender
 *			3. 	Default formatter to escapeHtml() ; dependency on smartJ$Utils
 * 			4. 	Add parameters for onColumnsResized(theArgs)
 * 			5. 	Add parameters for onColumnsReordered(theArgs)
 * 			6. 	Add column option cssHeadClass
 * 			7. 	Add column option defaultSortDir
 * 			8. 	Fix a bug in getMaxSupportedCssHeight() that was preventing IE9-IE11 with jQuery.v3 to work with large tables (1 mil. rows)
 * 			9. 	Disable support for IE < 9 as jQuery current does not support it
 * 			10.	Replace all dependencies for $.browser and use browser detection from v.2.2
 * 			11. Add Enable Cell Text Selection feature as option (default true)
 * 			12. Many Fixes for borders ; replaced css classes: ui-state-* with slick-state-* / ui-widget* with slick-uiwidget*
 * 			13. Fix scrollbar calculation
 * 			14. Fix multiselection on MacOS / Replaced e.metaKey with e.altKey
 * 			15. Code reflow, add missing if/else brackets
 * 			16. Fixed several HTML escapes
 * 			17. jQuery 3.5.0 ready (fixed XHTML Tags)
 * 			18. ES6 + optimizations ; quotes optimizations; use all over single quotes except for HTML attributes which have to be double quotes
 *
 *
 * TODO:
 * - frozen columns
 * - consistent events (EventHelper?  jQuery events?)
 *
 * OPTIONS:
 *     rowHeight                   - (default 25px) Row height in pixels.
 *     enableAddRow                - (default false) If true, a blank row will be displayed at the bottom - typing values in that row will add a new one.
 *     leaveSpaceForNewRows        - (default false)
 *     editable                    - (default false) If false, no cells will be switched into edit mode.
 *     autoEdit                    - (default true) Cell will not automatically go into edit mode when selected.
 *     enableCellNavigation        - (default true) If false, no cells will be selectable.
 *     enableCellRangeSelection    - (default false) If true, user will be able to select a cell range.  onCellRangeSelected event will be fired.
 *     defaultColumnWidth          - (default 80px) Default column width in pixels (if columns[cell].width is not specified).
 *     enableColumnReorder         - (default false) Allows the user to reorder columns.
 *     asyncEditorLoading          - (default false) Makes cell editors load asynchronously after a small delay. This greatly increases keyboard navigation speed.
 *     asyncEditorLoadDelay        - (default 100msec) Delay after which cell editor is loaded. Ignored unless asyncEditorLoading is true.
 *     forceFitColumns             - (default false) Force column sizes to fit into the viewport (avoid horizontal scrolling).
 *     enableAsyncPostRender       - (default false) If true, async post rendering will occur and asyncPostRender delegates on columns will be called.
 *     asyncPostRenderDelay        - (default 60msec) Delay after which async post renderer delegate is called.
 *     autoHeight                  - (default false) If true, vertically resizes to fit all rows.
 *     editorLock                  - (default Slick.GlobalEditorLock) A Slick.EditorLock instance to use for controlling concurrent data edits.
 *     showSecondaryHeaderRow      - (default false) If true, an extra blank (to be populated externally) row will be displayed just below the header columns.
 *     secondaryHeaderRowHeight    - (default 25px) The height of the secondary header row.
 *     syncColumnCellResize        - (default false) Synchronously resize column cells when column headers are resized
 *     rowCssClasses               - (default null) A function which (given a row's data item as an argument) returns a space-delimited string of CSS classes that will be applied to the slick-row element. Note that this should be fast, as it is called every time a row is displayed.
 *     cellHighlightCssClass       - (default 'highlighted') A CSS class to apply to cells highlighted via setHighlightedCells().
 *     cellFlashingCssClass        - (default 'flashing') A CSS class to apply to flashing cells (flashCell()).
 *     formatterFactory            - (default null) A factory object responsible to creating a formatter for a given cell. Must implement getFormatter(column).
 *     editorFactory               - (default null) A factory object responsible to creating an editor for a given cell. Must implement getEditor(column).
 *     multiSelect                 - (default true) Enable multiple row selection.
 *     enableTextSelectionOnCells  - (default true) Enable text selection in cells
 *
 * COLUMN DEFINITION (columns) OPTIONS:
 *     id                  - Column ID.
 *     name                - Column name to put in the header.
 *     toolTip             - Tooltip (if different from name).
 *     field               - Property of the data context to bind to.
 *     formatter           - default `return value || ''` ; Function responsible for rendering the contents of a cell. Signature: function formatter(row, cell, value, columnDef, dataContext) { ... return '...'; }
 *     editor              - An Editor class.
 *     validator           - An extra validation function to be passed to the editor.
 *     unselectable        - If true, the cell cannot be selected (and therefore edited).
 *     cannotTriggerInsert - If true, a new row cannot be created from just the value of this cell.
 *     width               - Width of the column in pixels.
 *     resizable           - (default true) If false, the column cannot be resized.
 *     sortable            - (default false) If true, the column can be sorted (onSort will be called).
 *     defaultSortDir      - (default true = ascending) If true, the column will be sorted ascending on first column header click (default true = ascending) If true, the column will be sorted ascending on first column header click, else descending
 *     minWidth            - Minimum allowed column width for resizing.
 *     maxWidth            - Maximum allowed column width for resizing.
 *     cssClass            - A CSS class to add to the column cell.
 *     cssHeadClass        - A CSS class for column header.
 *     rerenderOnResize    - Rerender the column when it is resized (useful for columns relying on cell width or adaptive formatters).
 *     asyncPostRender     - Function responsible for manipulating the cell DOM node after it has been rendered (called in the background).
 *     behavior            - Configures the column with one of several available predefined behaviors:  'select', 'move', 'selectAndMove'.
 *
 *
 * EVENTS:
 *     onSort                -
 *     onHeaderContextMenu   -
 *     onHeaderClick         -
 *     onClick               -
 *     onDblClick            -
 *     onContextMenu         -
 *     onKeyDown             -
 *     onAddNewRow           -
 *     onValidationError     -
 *     onViewportChanged     -
 *     onSelectedRowsChanged -
 *     onColumnsReordered    -
 *     onColumnsResized      -
 *     onBeforeMoveRows      -
 *     onMoveRows            -
 *     onCellChange          -  Raised when cell has been edited.   Args: row,cell,dataContext.
 *     onBeforeEditCell      -  Raised before a cell goes into edit mode.  Return false to cancel.  Args: row,cell,dataContext.
 *     onBeforeCellEditorDestroy    - Raised before a cell editor is destroyed.  Args: current cell editor.
 *     onBeforeDestroy       -  Raised just before the grid control is destroyed (part of the destroy() method).
 *     onCurrentCellChanged  -  Raised when the selected (active) cell changed.  Args: {row:currentRow, cell:currentCell}.
 *     onCellRangeSelected   -  Raised when a user selects a range of cells.  Args: {from:{row,cell}, to:{row,cell}}.
 *
 * NOTES:
 *     Cell/row DOM manipulations are done directly bypassing jQuery's DOM manipulation methods.
 *     This increases the speed dramatically, but can only be done safely because there are no event handlers
 *     or data associated with any cell/row DOM nodes.  Cell editors must make sure they implement .destroy()
 *     and do proper cleanup.
 *
 *
 * @param {Node}              container   Container node to create the grid in.
 * @param {Array} or {Object} data        An array of objects for databinding.
 * @param {Array}             columns     An array of column definitions.
 * @param {Object}            options     Grid options.
 */

// make sure required JavaScript modules are loaded
if(typeof jQuery === 'undefined') {
	throw new Error('SlickGrid requires jquery module to be loaded');
}
if(!jQuery.fn.drag) {
	throw new Error('SlickGrid requires jquery.event.drag module to be loaded');
}

(function($) { // ES6

	const _Utils$ = smartJ$Utils;
	const escapeHtml = _Utils$.escape_html;

	let scrollbarDimensions; // shared across all grids on this page

	//////////////////////////////////////////////////////////////////////////////////////////////
	// EditorLock class implementation (available as Slick.EditorLock)

	/** @constructor */
	const EditorLock = function() {

		/// <summary>
		/// Track currently active edit controller and ensure
		/// that onle a single controller can be active at a time.
		/// Edit controller is an object that is responsible for
		/// gory details of looking after editor in the browser,
		/// and allowing EditorLock clients to either accept
		/// or cancel editor changes without knowing any of the
		/// implementation details. SlickGrid instance is used
		/// as edit controller for cell editors.
		/// </summary>

		let currentEditController = null;

		this.isActive = function isActive(editController) {
			/// <summary>
			/// Return true if the specified editController
			/// is currently active in this lock instance
			/// (i.e. if that controller acquired edit lock).
			/// If invoked without parameters ('editorLock.isActive()'),
			/// return true if any editController is currently
			/// active in this lock instance.
			/// </summary>
			return (editController ? currentEditController === editController : currentEditController !== null);
		}; //END FUNCTION

		this.activate = function activate(editController) {
			/// <summary>
			/// Set the specified editController as the active
			/// controller in this lock instance (acquire edit lock).
			/// If another editController is already active,
			/// an error will be thrown (i.e. before calling
			/// this method isActive() must be false,
			/// afterwards isActive() will be true).
			/// </summary>
			if(editController === currentEditController) { // already activated?
				return;
			}
			if(currentEditController !== null) {
				throw 'SlickGrid.EditorLock.activate: an editController is still active, can\'t activate another editController';
			}
			if(!editController.commitCurrentEdit) {
				throw 'SlickGrid.EditorLock.activate: editController must implement .commitCurrentEdit()';
			}
			if(!editController.cancelCurrentEdit) {
				throw 'SlickGrid.EditorLock.activate: editController must implement .cancelCurrentEdit()';
			}
			currentEditController = editController;
		}; //END FUNCTION

		this.deactivate = function deactivate(editController) {
			/// <summary>
			/// Unset the specified editController as the active
			/// controller in this lock instance (release edit lock).
			/// If the specified editController is not the editController
			/// that is currently active in this lock instance,
			/// an error will be thrown.
			/// </summary>
			if(currentEditController !== editController) {
				throw 'SlickGrid.EditorLock.deactivate: specified editController is not the currently active one';
			}
			currentEditController = null;
		}; //END FUNCTION

		this.commitCurrentEdit = function commitCurrentEdit() {
			/// <summary>
			/// Invoke the 'commitCurrentEdit' method on the
			/// editController that is active in this lock
			/// instance and return the return value of that method
			/// (if no controller is active, return true).
			/// 'commitCurrentEdit' is expected to return true
			/// to indicate successful commit, false otherwise.
			/// </summary>
			return (currentEditController ? currentEditController.commitCurrentEdit() : true);
		}; //END FUNCTION

		this.cancelCurrentEdit = function cancelCurrentEdit() {
			/// <summary>
			/// Invoke the 'cancelCurrentEdit' method on the
			/// editController that is active in this lock
			/// instance (if no controller is active, do nothing).
			/// Returns true if the edit was succesfully cancelled.
			/// </summary>
			return (currentEditController ? currentEditController.cancelCurrentEdit() : true);
		}; //END FUNCTION

	} // END CLASS (EditorLock)

	Object.freeze(EditorLock);


	//////////////////////////////////////////////////////////////////////////////////////////////
	// SlickGrid class implementation (available as Slick.Grid)

	/** @constructor */
	const SlickGrid = function(container,data,columns,options) { // START CLASS

		/// <summary>
		/// Create and manage virtual grid in the specified $container,
		/// connecting it to the specified data source. Data is presented
		/// as a grid with the specified columns and data.length rows.
		/// Options alter behaviour of the grid.
		/// </summary>

		// settings
		const defaults = {
			rowHeight: 25,
			defaultColumnWidth: 80,
			enableAddRow: false,
			leaveSpaceForNewRows: false,
			editable: false,
			autoEdit: true,
			enableCellNavigation: true,
			enableCellRangeSelection: false,
			enableColumnReorder: false,
			asyncEditorLoading: false,
			asyncEditorLoadDelay: 100,
			forceFitColumns: false,
			enableAsyncPostRender: false,
			asyncPostRenderDelay: 60,
			autoHeight: false,
			editorLock: Slick.GlobalEditorLock,
			showSecondaryHeaderRow: false,
			secondaryHeaderRowHeight: 25,
			syncColumnCellResize: false,
			enableAutoTooltips: true,
			toolTipMaxLength: null,
			formatterFactory: null,
			editorFactory: null,
			cellHighlightCssClass: 'highlighted',
			cellFlashingCssClass: 'flashing',
			multiSelect: true,
			enableTextSelectionOnCells: true
		};

		let gridData, gridDataGetLength, gridDataGetItem;

		const columnDefaults = {
			name: '',
			resizable: true,
			sortable: false,
			defaultSortDir: true, // ascending
			minWidth: 30
		};

		// scroller
		let maxSupportedCssHeight;      // browser's breaking point
		let th;                         // virtual height
		let h;                          // real scrollable height
		let ph;                         // page height
		let n;                          // number of pages
		let cj;                         // 'jumpiness' coefficient

		let page = 0;                   // current page
		let offset = 0;                 // current page offset
		let scrollDir = 1;

		// private
		let $container;
		const uid = 'slickgrid_' + Math.round(1000000 * Math.random());
		const self = this;
		let $headerScroller;
		let $headers;
		let $secondaryHeaderScroller;
		let $secondaryHeaders;
		let $viewport;
		let $canvas;
		let $style;
		let stylesheet;
		let viewportH, viewportW;
		let viewportHasHScroll;
		let headerColumnWidthDiff, headerColumnHeightDiff, cellWidthDiff, cellHeightDiff;  // padding+border
		let absoluteColumnMinWidth;

		let currentRow, currentCell;
		let currentCellNode = null;
		let currentEditor = null;
		let serializedEditorValue;
		let editController;

		let rowsCache = {};
		let renderedRows = 0;
		let numVisibleRows;
		let prevScrollTop = 0;
		let scrollTop = 0;
		let lastRenderedScrollTop = 0;
		let prevScrollLeft = 0;
		let avgRowRenderTime = 10;

		let selectedRows = [];
		let selectedRowsLookup = {};
		let columnsById = {};
		let highlightedCells;
		let sortColumnId;
		let sortAsc = true;

		// async call handles
		let h_editorLoader = null;
		let h_render = null;
		let h_postrender = null;
		let postProcessedRows = {};
		let postProcessToRow = null;
		let postProcessFromRow = null;

		// perf counters
		let counter_rows_rendered = 0;
		let counter_rows_removed = 0;


		//////////////////////////////////////////////////////////////////////////////////////////////
		// Initialization

		function init() {
			//--
			/// <summary>
			/// Initialize 'this' (self) instance of a SlickGrid.
			/// This function is called by the constructor.
			/// </summary>
			//--
			$container = $(container);
			//--
			gridData = data;
			gridDataGetLength = gridData.getLength || defaultGetLength;
			gridDataGetItem = gridData.getItem || defaultGetItem;
			//--
			//maxSupportedCssHeight = getMaxSupportedCssHeight();
			maxSupportedCssHeight = maxSupportedCssHeight || getMaxSupportedCssHeight(); // unixman: fix from v.2.2
			//--
			scrollbarDimensions = scrollbarDimensions || measureScrollbar(); // skip measurement if already have dimensions
			options = $.extend({},defaults,options);
			columnDefaults.width = options.defaultColumnWidth;
			//--
			// validate loaded JavaScript modules against requested options
			if(options.enableColumnReorder && !$.fn.sortable) {
				options.enableColumnReorder = false;
				console.error('WARNING: SlickGrid\'s `enableColumnReorder = true` option requires jQueryUI Sortable which is NOT Available ...');
			}
			//--
			editController = {
				'commitCurrentEdit': commitCurrentEdit,
				'cancelCurrentEdit': cancelCurrentEdit
			};
			//--
			$container
				.empty()
				.attr('tabIndex',0)
				.attr('hideFocus',true)
				.css('overflow','hidden')
				.css('outline',0)
				.addClass(uid)
				.addClass('slick-uiwidget');
			//--
			// set up a positioning container if needed
			if(!/relative|absolute|fixed/.test($container.css('position'))) {
				$container.css('position','relative');
			}
			$headerScroller = $('<div class="slick-header slick-state-default" style="overflow:hidden;position:relative;"></div>').appendTo($container);
			$headers = $('<div class="slick-header-columns" style="width:100000px; left:-10000px"></div>').appendTo($headerScroller);
			//--
			$secondaryHeaderScroller = $('<div class="slick-header-secondary slick-state-default" style="overflow:hidden;position:relative;"></div>').appendTo($container);
			$secondaryHeaders = $('<div class="slick-header-columns-secondary" style="width:100000px"></div>').appendTo($secondaryHeaderScroller);
			//--
			if(!options.showSecondaryHeaderRow) {
				$secondaryHeaderScroller.hide();
			}
			//--
			$viewport = $('<div class="slick-viewport" tabIndex="0" style="width:100%;overflow-x:auto;outline:0;position:relative;overflow-y:auto;" hideFocus></div>').appendTo($container);
			$canvas = $('<div class="grid-canvas" tabIndex="0" hideFocus></div>').appendTo($viewport);
			//--
			// header columns and cells may have different padding/border skewing width calculations (box-sizing, hello?)
			// calculate the diff so we can set consistent sizes
			measureCellPaddingAndBorder();
			//--
			$viewport.height(
				$container.innerHeight() -
				$headerScroller.outerHeight() -
				(options.showSecondaryHeaderRow ? $secondaryHeaderScroller.outerHeight() : 0));
			//--
			// for usability reasons, all text selection in SlickGrid is disabled
			// with the exception of input and textarea elements (selection must
			// be enabled there so that editors work as expected); note that
			// selection in grid cells (grid body) is already unavailable in
			// all browsers except IE
			disableSelection($headers); // disable all text selection in header (including input and textarea)
			if(!options.enableTextSelectionOnCells) {
				$viewport.bind('selectstart.ux', function(event){
					return $(event.target).is('input,textarea');
				}); // disable text selection in grid cells except in input and textarea elements (this is IE-specific, because selectstart event will only fire in IE)
			} //end if
			//--
			createColumnHeaders();
			setupColumnSort();
			setupDragEvents();
			createCssRules();
			//--
			resizeAndRender();
			//--
			bindAncestorScrollEvents();
			$viewport.bind('scroll.slickgrid', handleScroll);
			$container.bind('resize.slickgrid', resizeAndRender);
			$canvas.bind('keydown.slickgrid', handleKeyDown);
			$canvas.bind('click.slickgrid', handleClick);
			$canvas.bind('dblclick.slickgrid', handleDblClick);
			$canvas.bind('contextmenu.slickgrid', handleContextMenu);
			$canvas.bind('mouseover.slickgrid', handleHover);
			$headerScroller.bind('contextmenu.slickgrid', handleHeaderContextMenu);
			$headerScroller.bind('click.slickgrid', handleHeaderClick);
			//--
		} //END FUNCTION

		function measureScrollbar() {
			/// <summary>
			/// Measure width of a vertical scrollbar
			/// and height of a horizontal scrollbar.
			/// </summary
			/// <returns>
			/// { width: pixelWidth, height: pixelHeight }
			/// </returns>
		/*	let $c = $('<div style="position:absolute; top:-10000px; left:-10000px; width:100px; height:100px; overflow:scroll;"></div>').appendTo('body');
			let dim = { width: $c.width() - $c[0].clientWidth, height: $c.height() - $c[0].clientHeight };
			$c.remove(); */
			//-- fix by unixman (on chromium the scrollbar calculation failed ...)
			const $outerdiv = $('<div style="position:absolute; top:-10000px; left:-10000px; overflow:auto; width:100px; height:100px;"></div>').appendTo('body');
			const $innerdiv = $('<div style="width:200px; height:200px; overflow:auto;"></div>').appendTo($outerdiv);
			const dim = {
				width: $outerdiv[0].offsetWidth - $outerdiv[0].clientWidth,
				height: $outerdiv[0].offsetHeight - $outerdiv[0].clientHeight
			};
			$innerdiv.remove();
			$outerdiv.remove();
			//--
		//	console.log(JSON.stringify(dim));
			return dim;
		} //END FUNCTION

		function setCanvasWidth(width) {
			$canvas.width(width);
			viewportHasHScroll = (width > viewportW - scrollbarDimensions.width);
		} //END FUNCTION

		function disableSelection($target) {
			/// <summary>
			/// Disable text selection (using mouse) in
			/// the specified target.
			/// </summary
			if($target && $target.jquery) {
				$target.attr('unselectable', 'on').css('MozUserSelect', 'none').bind('selectstart.ux', function() { return false; }); // from jquery:ui.core.js 1.7.2
			}
		} //END FUNCTION

		function defaultGetLength() {
			/// <summary>
			/// Default implementation of getLength method
			/// returns the length of the array.
			/// </summary
			return gridData.length;
		} //END FUNCTION

		function defaultGetItem(i) {
			/// <summary>
			/// Default implementation of getItem method
			/// returns the item at specified position in
			/// the array.
			/// </summary
			return gridData[i];
		} //END FUNCTION

		function getMaxSupportedCssHeight() {
			/* BUG: this was currently not working with jQuery v3 in Internet Explorer 9..11 # fixed by unixman below
			let increment = 1000000;
			let supportedHeight = 0;
			// FF reports the height back but still renders blank after ~6M px
			let testUpTo = ($.browser.mozilla) ? 5000000 : 1000000000;
			let div = $('<div style="display:none"></div>').appendTo(document.body);
			while(supportedHeight <= testUpTo) {
				div.css('height', supportedHeight + increment);
				if(div.height() !== supportedHeight + increment)
					break;
				else
					supportedHeight += increment;
			}
			div.remove();
			return supportedHeight;
			*/
			//-- unixman: fix from v.2.2
			let supportedHeight = 1000000;
			// FF reports the height back but still renders blank after ~6M px
			//let testUpTo = navigator.userAgent.toLowerCase().match(/firefox/) ? 6000000 : 1000000000;
			let testUpTo = 6000000; // unixman: will not support more than Firefox can as Firefox is the etalon browser for all opensource software
			let div = $('<div style="display:none"></div>').appendTo(document.body);
			while(true) {
				let test = supportedHeight * 2;
				div.css('height', test);
				if(test > testUpTo || div.height() !== test) {
					break;
				} else {
					supportedHeight = test;
				} //end if else
			} //end while
			div.remove();
			return supportedHeight;
			//--
		} //END FUNCTION

		// TODO:  this is static.  need to handle page mutation.
		function bindAncestorScrollEvents() {
			let elem = $canvas[0];
			while((elem = elem.parentNode) != document.body) {
				// bind to scroll containers only
				if(elem == $viewport[0] || elem.scrollWidth != elem.clientWidth || elem.scrollHeight != elem.clientHeight)
					$(elem).bind('scroll.slickgrid', handleCurrentCellPositionChange);
			}
		} //END FUNCTION

		function unbindAncestorScrollEvents() {
			$canvas.parents().unbind('scroll.slickgrid');
		} //END FUNCTION

		function createColumnHeaders() {
			let i;
			function hoverBegin() {
				$(this).addClass('slick-state-hover');
			}
			function hoverEnd() {
				$(this).removeClass('slick-state-hover');
			}
			$headers.empty();
			columnsById = {};
			for(i = 0; i < columns.length; i++) {
				let m = columns[i] = $.extend({},columnDefaults,columns[i]);
				columnsById[m.id] = i;
				let header = $('<div class="slick-header-column c' + i + ' slick-state-default' + (m.cssHeadClass ? ' ' + escapeHtml(m.cssHeadClass) : '') + '" id="' + escapeHtml('' + uid + m.id) + '"></div>')
					.html('<span class="slick-column-name">' + m.name + '</span>') // header can be HTML
					.width((m.currentWidth || m.width) - headerColumnWidthDiff)
					.attr('title', m.toolTip || m.name || '')
					.data('fieldId', m.id)
					.appendTo($headers);
				if(options.enableColumnReorder || m.sortable) {
					header.hover(hoverBegin, hoverEnd);
				}
				if(m.sortable) {
					header.append('<span class="slick-sort-indicator"></span>');
				}
				//if(m.id === sortColumnId) { // per column default sort order (this is not necessary ; comment out)
				//	setSortColumn(m.id, !!m.defaultSortDir);
				//}
			}
			setSortColumn(sortColumnId,sortAsc); // per column default sort order (this is left as default ; uncommented)
			setupColumnResize();
			if(options.enableColumnReorder) {
				setupColumnReorder();
			}
		} //END FUNCTION

		function setupColumnSort() {
			$headers.click(function(e) {
				if($(e.target).hasClass('slick-resizable-handle')) {
					return;
				}
				if(self.onSort) {
					const $col = $(e.target).closest('.slick-header-column');
					if(!$col.length) {
						return;
					}
					const column = columns[getSiblingIndex($col[0])];
					if(column.sortable) {
						if(!options.editorLock.commitCurrentEdit()) {
							return;
						}
						if(column.id === sortColumnId) {
							sortAsc = !sortAsc;
						} else {
							sortColumnId = column.id;
							//sortAsc = true; // per column default sort order
							sortAsc = !!column.defaultSortDir; // per column default sort order
						}
						setSortColumn(sortColumnId,sortAsc);
						self.onSort(column,sortAsc);
					}
				}
			});
		} //END FUNCTION

		function setupColumnReorder() {
			$headers.sortable({
				containment: 'parent',
				axis: 'x',
				cursor: 'default',
				tolerance: 'intersection',
				helper: 'clone',
				placeholder: 'slick-sortable-placeholder slick-state-default slick-header-column',
				forcePlaceholderSize: true,
				start: function(e, ui) { $(ui.helper).addClass('slick-header-column-active'); },
				beforeStop: function(e, ui) { $(ui.helper).removeClass('slick-header-column-active'); },
				stop: function(e) {
					if(!options.editorLock.commitCurrentEdit()) {
						$(this).sortable('cancel');
						return;
					}
					const reorderedIds = $headers.sortable('toArray');
					let reorderedColumns = [];
					let theArgs = []; // unixman
					let theCrrCol;
					for(let i=0; i<reorderedIds.length; i++) {
						theCrrCol = reorderedIds[i].replace(uid,''); // unixman
						//reorderedColumns.push(columns[getColumnIndex(reorderedIds[i].replace(uid,''))]);
						reorderedColumns.push(columns[getColumnIndex(theCrrCol)]); // unixman
						theArgs.push(theCrrCol); // unixman
					}
					setColumns(reorderedColumns);
					if(self.onColumnsReordered) {
						//self.onColumnsReordered();
						self.onColumnsReordered(theArgs); // unixman
					}
					e.stopPropagation();
					setupColumnResize();
				}
			});
		} //END FUNCTION

		function setupColumnResize() {
			let $col, j, c, pageX, columnElements, minPageX, maxPageX, firstResizable, lastResizable, originalCanvasWidth;
			columnElements = $headers.children();
			columnElements.find('.slick-resizable-handle').remove();
			columnElements.each(function(i,e) {
				if(columns[i].resizable) {
					if(firstResizable === undefined) { firstResizable = i; }
					lastResizable = i;
				}
			});
			columnElements.each(function(i,e) {
				if((firstResizable !== undefined && i < firstResizable) || (options.forceFitColumns && i >= lastResizable)) { return; }
				$col = $(e);
				$('<div class="slick-resizable-handle"></div>')
					.appendTo(e)
					.bind('dragstart', function(e,dd) {
						if(!options.editorLock.commitCurrentEdit()) {
							return false;
						}
						pageX = e.pageX;
						$(this).parent().addClass('slick-header-column-active');
						let shrinkLeewayOnRight = null;
						let stretchLeewayOnRight = null;
						// lock each column's width option to current width
						columnElements.each(function(i,e) { columns[i].previousWidth = $(e).outerWidth(); });
						if(options.forceFitColumns) {
							shrinkLeewayOnRight = 0;
							stretchLeewayOnRight = 0;
							// colums on right affect maxPageX/minPageX
							for(j = i + 1; j < columnElements.length; j++) {
								c = columns[j];
								if(c.resizable) {
									if(stretchLeewayOnRight !== null) {
										if(c.maxWidth) {
											stretchLeewayOnRight += c.maxWidth - c.previousWidth;
										} else {
											stretchLeewayOnRight = null;
										}
									}
									shrinkLeewayOnRight += c.previousWidth - Math.max(c.minWidth || 0, absoluteColumnMinWidth);
								}
							}
						}
						let shrinkLeewayOnLeft = 0;
						let stretchLeewayOnLeft = 0;
						for(j = 0; j <= i; j++) {
							// columns on left only affect minPageX
							c = columns[j];
							if(c.resizable) {
								if(stretchLeewayOnLeft !== null) {
									if(c.maxWidth) {
										stretchLeewayOnLeft += c.maxWidth - c.previousWidth;
									} else {
										stretchLeewayOnLeft = null;
									}
								}
								shrinkLeewayOnLeft += c.previousWidth - Math.max(c.minWidth || 0, absoluteColumnMinWidth);
							}
						}
						if(shrinkLeewayOnRight === null) { shrinkLeewayOnRight = 100000; }
						if(shrinkLeewayOnLeft === null) { shrinkLeewayOnLeft = 100000; }
						if(stretchLeewayOnRight === null) { stretchLeewayOnRight = 100000; }
						if(stretchLeewayOnLeft === null) { stretchLeewayOnLeft = 100000; }
						maxPageX = pageX + Math.min(shrinkLeewayOnRight, stretchLeewayOnLeft);
						minPageX = pageX - Math.min(shrinkLeewayOnLeft, stretchLeewayOnRight);
						originalCanvasWidth = $canvas.width();
					}).bind('drag', function(e,dd) {
						let actualMinWidth, d = Math.min(maxPageX, Math.max(minPageX, e.pageX)) - pageX, x, ci;
						if(d < 0) { // shrink column
							x = d;
							for(j = i; j >= 0; j--) {
								c = columns[j];
								if(c.resizable) {
									actualMinWidth = Math.max(c.minWidth || 0, absoluteColumnMinWidth);
									if(x && c.previousWidth + x < actualMinWidth) {
										x += c.previousWidth - actualMinWidth;
										styleColumnWidth(j, actualMinWidth, options.syncColumnCellResize);
									} else {
										styleColumnWidth(j, c.previousWidth + x, options.syncColumnCellResize);
										x = 0;
									}
								}
							}
							if(options.forceFitColumns) {
								x = -d;
								for(j = i + 1; j < columnElements.length; j++) {
									c = columns[j];
									if(c.resizable) {
										if(x && c.maxWidth && (c.maxWidth - c.previousWidth < x)) {
											x -= c.maxWidth - c.previousWidth;
											styleColumnWidth(j, c.maxWidth, options.syncColumnCellResize);
										} else {
											styleColumnWidth(j, c.previousWidth + x, options.syncColumnCellResize);
											x = 0;
										}
									}
								}
							} else if(options.syncColumnCellResize) {
								setCanvasWidth(originalCanvasWidth + d);
							}
						} else { // stretch column
							x = d;
							for(j = i; j >= 0; j--) {
								c = columns[j];
								if(c.resizable) {
									if(x && c.maxWidth && (c.maxWidth - c.previousWidth < x)) {
										x -= c.maxWidth - c.previousWidth;
										styleColumnWidth(j, c.maxWidth, options.syncColumnCellResize);
									} else {
										styleColumnWidth(j, c.previousWidth + x, options.syncColumnCellResize);
										x = 0;
									}
								}
							}
							if(options.forceFitColumns) {
								x = -d;
								for(j = i + 1; j < columnElements.length; j++) {
									c = columns[j];
									if(c.resizable) {
										actualMinWidth = Math.max(c.minWidth || 0, absoluteColumnMinWidth);
										if(x && c.previousWidth + x < actualMinWidth) {
											x += c.previousWidth - actualMinWidth;
											styleColumnWidth(j, actualMinWidth, options.syncColumnCellResize);
										} else {
											styleColumnWidth(j, c.previousWidth + x, options.syncColumnCellResize);
											x = 0;
										}
									}
								}
							} else if(options.syncColumnCellResize) {
								setCanvasWidth(originalCanvasWidth + d);
							}
						}
					}).bind('dragend', function(e,dd) {
						let newWidth;
						$(this).parent().removeClass('slick-header-column-active');
						let theArgs = []; // unixman
						for(j = 0; j < columnElements.length; j++) {
							c = columns[j];
							newWidth = $(columnElements[j]).outerWidth();
							theArgs.push(newWidth); // unixman
							if(c.previousWidth !== newWidth && c.rerenderOnResize) {
								removeAllRows();
							}
							if(options.forceFitColumns) {
								c.width = Math.floor(c.width * (newWidth - c.previousWidth) / c.previousWidth) + c.width;
							} else {
								c.width = newWidth;
							}
							if(!options.syncColumnCellResize && c.previousWidth !== newWidth) {
								styleColumnWidth(j, newWidth, true);
							}
						}
						resizeCanvas();
						if(self.onColumnsResized) {
							//self.onColumnsResized();
							self.onColumnsResized(theArgs); // unixman
						}
					});
				});
		} //END FUNCTION

		function setupDragEvents() {
			const MOVE_ROWS = 1;
			const SELECT_CELLS = 2;
			function fixUpRange(range) {
				const r1 = Math.min(range.start.row,range.end.row);
				const c1 = Math.min(range.start.cell,range.end.cell);
				const r2 = Math.max(range.start.row,range.end.row);
				const c2 = Math.max(range.start.cell,range.end.cell);
				return {
					start: { row:r1, cell:c1 },
					end:   { row:r2, cell:c2 },
				};
			} //end function
			$canvas.bind('draginit', function(e,dd) {
					const $cell = $(e.target).closest('.slick-cell');
					if($cell.length === 0) {
						return false;
					}
					if(parseInt($cell.parent().attr('row'), 10) >= gridDataGetLength()) {
						return false;
					}
					const colDef = columns[getSiblingIndex($cell[0])];
					if(colDef.behavior == 'move' || colDef.behavior == 'selectAndMove') {
						dd.mode = MOVE_ROWS;
					} else if(options.enableCellRangeSelection) {
						dd.mode = SELECT_CELLS;
					} else {
						return false;
					}
				}).bind('dragstart', function(e,dd) {
					if(!options.editorLock.commitCurrentEdit()) {
						return false;
					}
					const row = parseInt($(e.target).closest('.slick-row').attr('row'), 10);
					if(dd.mode == MOVE_ROWS) {
						if(!selectedRowsLookup[row]) {
							setSelectedRows([row]);
						}
						dd.selectionProxy = $('<div class="slick-reorder-proxy"></div>')
							.css('position', 'absolute')
							.css('zIndex', '99999')
							.css('width', $(this).innerWidth())
							.css('height', options.rowHeight*selectedRows.length)
							.appendTo($viewport);
						dd.guide = $('<div class="slick-reorder-guide"></div>')
							.css('position', 'absolute')
							.css('zIndex', '99998')
							.css('width', $(this).innerWidth())
							.css('top', -1000)
							.appendTo($viewport);
						dd.insertBefore = -1;
					}
					if(dd.mode == SELECT_CELLS) {
						const start = getCellFromPoint(dd.startX - $canvas.offset().left, dd.startY - $canvas.offset().top);
						if(!cellExists(start.row,start.cell)) {
							return false;
						}
						dd.range = {start:start,end:{}};
						$('.slick-selection').remove();
						return $('<div class="slick-selection" onClick="$(this).remove();"></div>').appendTo($canvas);
					}
				}).bind('drag', function(e,dd) {
					if(dd.mode == MOVE_ROWS) {
						const top = e.pageY - $(this).offset().top;
						dd.selectionProxy.css('top',top-5);
						const insertBefore = Math.max(0,Math.min(Math.round(top/options.rowHeight),gridDataGetLength()));
						if(insertBefore !== dd.insertBefore) {
							if(self.onBeforeMoveRows && self.onBeforeMoveRows(getSelectedRows(),insertBefore) === false) {
								dd.guide.css('top', -1000);
								dd.canMove = false;
							} else {
								dd.guide.css('top',insertBefore*options.rowHeight);
								dd.canMove = true;
							}
							dd.insertBefore = insertBefore;
						}
					}
					if(dd.mode == SELECT_CELLS) {
						const end = getCellFromPoint(e.clientX - $canvas.offset().left, e.clientY - $canvas.offset().top);
						if(!cellExists(end.row,end.cell)) {
							return;
						}
						dd.range.end = end;
						const r = fixUpRange(dd.range);
						const from = getCellNodeBox(r.start.row,r.start.cell);
						const to = getCellNodeBox(r.end.row,r.end.cell);
						$(dd.proxy).css({
							top: from.top,
							left: from.left,
							height: to.bottom - from.top - 2,
							width: to.right - from.left - 2
						});
					}
				}).bind('dragend', function(e,dd) {
					if(dd.mode == MOVE_ROWS) {
						dd.guide.remove();
						dd.selectionProxy.remove();
						if(self.onMoveRows && dd.canMove) {
							self.onMoveRows(getSelectedRows(),dd.insertBefore);
						}
					}
					if(dd.mode == SELECT_CELLS) {
						//$(dd.proxy).remove();
						//console.log(JSON.stringify(fixUpRange(dd.range),null,2));
						if(self.onCellRangeSelected) {
							self.onCellRangeSelected(fixUpRange(dd.range), $(dd.proxy)); // this is a function that can call $(dd.proxy).remove();
						}
					}
				});
		} //END FUNCTION

		function measureCellPaddingAndBorder() {
			const bFix = 1; // fix pixels by unixman (this appears to be a valid fix in Chrome)
			let tmp;
			tmp = $('<div class="slick-state-default slick-header-column" style="visibility:hidden">-</div>').appendTo($headers);
			headerColumnWidthDiff = tmp.outerWidth() - tmp.width();
			headerColumnWidthDiff += bFix; // fix by unixman to substract the borders
			headerColumnHeightDiff = tmp.outerHeight() - tmp.height();
			tmp.remove();
			const r = $('<div class="slick-row"></div>').appendTo($canvas);
			tmp = $('<div class="slick-cell" id="" style="visibility:hidden">-</div>').appendTo(r);
			cellWidthDiff = tmp.outerWidth() - tmp.width();
			cellWidthDiff += bFix; // fix by unixman to substract the borders
			cellHeightDiff = tmp.outerHeight() - tmp.height();
			r.remove();
			absoluteColumnMinWidth = Math.max(headerColumnWidthDiff,cellWidthDiff);
		} //END FUNCTION

		function createCssRules() {
			$style = $('<style type="text/css" rel="stylesheet"></style>').appendTo($('head'));
			const rowHeight = (options.rowHeight - cellHeightDiff);
			let rules = [
				'.' + uid + ' .slick-header-column { left: 10000px; }',
				'.' + uid + ' .slick-header-columns-secondary {  height:' + options.secondaryHeaderRowHeight + 'px; }',
				'.' + uid + ' .slick-cell { height:' + rowHeight + 'px; }'
			];
			for(let i=0; i<columns.length; i++) {
				rules.push(
					'.' + uid + ' .c' + i + ' { ' +
					'width:' + ((columns[i].currentWidth || columns[i].width) - cellWidthDiff) + 'px; ' +
					' } ');
			}
			if($style[0].styleSheet) { // IE
				$style[0].styleSheet.cssText = rules.join('');
			} else {
				$style[0].appendChild(document.createTextNode(rules.join(' ')));
			}
			const sheets = document.styleSheets;
			for(let i=0; i<sheets.length; i++) {
				if((sheets[i].ownerNode || sheets[i].owningElement) == $style[0]) {
					stylesheet = sheets[i];
					break;
				}
			}
		} //END FUNCTION

		function findCssRule(selector) {
			const rules = (stylesheet.cssRules || stylesheet.rules);
			for(let i=0; i<rules.length; i++) {
				if(rules[i].selectorText == selector) {
					return rules[i];
				}
			}
			return null;
		} //END FUNCTION

		function findCssRuleForCell(index) {
			return findCssRule('.' + uid + ' .c' + index);
		} //END FUNCTION

		function removeCssRules() {
			$style.remove();
		} //END FUNCTION

		function destroy() {
			options.editorLock.cancelCurrentEdit();
			if(self.onBeforeDestroy) {
				self.onBeforeDestroy();
			}
			if(options.enableColumnReorder && $headers.sortable) {
				$headers.sortable('destroy');
			}
			unbindAncestorScrollEvents();
			$container.unbind('.slickgrid');
			removeCssRules();
			$canvas.unbind('draginit dragstart dragend drag');
			$container.empty().removeClass(uid);
		} //END FUNCTION


		//////////////////////////////////////////////////////////////////////////////////////////////
		// General

		function getEditController() {
			return editController;
		} //END FUNCTION

		function getColumnIndex(id) {
			return columnsById[id];
		} //END FUNCTION

		function autosizeColumns() {
			let i, c,
				widths = [],
				shrinkLeeway = 0,
				viewportW = $viewport.innerWidth(), // may not be initialized yet
				availWidth = (options.autoHeight ? viewportW : viewportW - scrollbarDimensions.width), // with AutoHeight, we do not need to accomodate the vertical scroll bar
				total = 0,
				existingTotal = 0;
			for(i = 0; i < columns.length; i++) {
				c = columns[i];
				widths.push(c.width);
				existingTotal += c.width;
				shrinkLeeway += c.width - Math.max(c.minWidth || 0, absoluteColumnMinWidth);
			}
			total = existingTotal;
			removeAllRows();
			// shrink
			/* BUGFIX (unixman): this is un-necessary as will blow up the browser in some cases ...
			while(total > availWidth) {
				if(!shrinkLeeway) { return; }
				let shrinkProportion = (total - availWidth) / shrinkLeeway;
				for(i = 0; i < columns.length && total > availWidth; i++) {
					c = columns[i];
					if(!c.resizable || c.minWidth === c.width || c.width === absoluteColumnMinWidth) { continue; }
					let shrinkSize = Math.floor(shrinkProportion * (c.width - Math.max(c.minWidth || 0, absoluteColumnMinWidth))) || 1;
					total -= shrinkSize;
					widths[i] -= shrinkSize;
				}
			}
			*/
			// grow
			let previousTotal = total;
			while(total < availWidth) {
				const growProportion = availWidth / total;
				for(i = 0; i < columns.length && total < availWidth; i++) {
					c = columns[i];
					if(!c.resizable || c.maxWidth <= c.width) {
						continue;
					}
					let growSize = Math.min(Math.floor(growProportion * c.width) - c.width, (c.maxWidth - c.width) || 1000000) || 1;
					total += growSize;
					widths[i] += growSize;
				}
				if(previousTotal == total) {
					break; // if total is not changing, will result in infinite loop
				}
				previousTotal = total;
			}
			for(i=0; i<columns.length; i++) {
				styleColumnWidth(i, columns[i].currentWidth = widths[i], true);
			}
			resizeCanvas();
		} //END FUNCTION

		function styleColumnWidth(index,width,styleCells) {
			columns[index].currentWidth = width;
			$headers.children().eq(index).css('width', width - headerColumnWidthDiff);
			if(styleCells) {
				findCssRuleForCell(index).style.width = (width - cellWidthDiff) + 'px';
			}
		} //END FUNCTION

		function setSortColumn(columnId, ascending) {
			sortColumnId = columnId;
			sortAsc = ascending;
			const columnIndex = getColumnIndex(sortColumnId);
			$headers.children().removeClass('slick-header-column-sorted');
			$headers.find('.slick-sort-indicator').removeClass('slick-sort-indicator-asc slick-sort-indicator-desc');
			if(columnIndex != null) {
				$headers.children().eq(columnIndex)
					.addClass('slick-header-column-sorted')
					.find('.slick-sort-indicator')
					.addClass(sortAsc ? 'slick-sort-indicator-asc' : 'slick-sort-indicator-desc');
			}
		} //END FUNCTION

		function getSelectedRows() {
			return selectedRows.concat();
		} //END FUNCTION

		function setSelectedRows(rows) {
			let i, row;
			let lookup = {};
			for(i=0; i<rows.length; i++) {
				lookup[rows[i]] = true;
			}
			const ui_class_selected = 'slickgrid-row-selected selected'; // 'slick-state-active selected' ; changed by unixman
			// unselect old rows
			for(i=0; i<selectedRows.length; i++) {
				row = selectedRows[i];
				if(rowsCache[row] && !lookup[row]) {
					$(rowsCache[row]).removeClass(ui_class_selected);
				}
			}
			// select new ones
			for(i=0; i<rows.length; i++) {
				row = rows[i];
				if(rowsCache[row] && !selectedRowsLookup[row]) {
					$(rowsCache[row]).addClass(ui_class_selected);
				}
			}
			selectedRows = rows.concat();
			selectedRowsLookup = lookup;
		} //END FUNCTION

		function getColumns() {
			return columns;
		} //END FUNCTION

		function setColumns(columnDefinitions) {
			columns = columnDefinitions;
			removeAllRows();
			createColumnHeaders();
			removeCssRules();
			createCssRules();
			resizeAndRender();
			handleScroll();
		} //END FUNCTION

		function getOptions() {
			return options;
		} //END FUNCTION

		function setOptions(args) {
			if(!options.editorLock.commitCurrentEdit()) {
				return;
			}
			makeSelectedCellNormal();
			if(options.enableAddRow !== args.enableAddRow) {
				removeRow(gridDataGetLength());
			}
			options = $.extend(options,args);
			render();
		} //END FUNCTION

		function setData(newData,scrollToTop) {
			removeAllRows();
			data = newData;
			gridData = data;
			gridDataGetLength = gridData.getLength || defaultGetLength;
			gridDataGetItem = gridData.getItem || defaultGetItem;
			if(scrollToTop) {
				scrollTo(0);
			}
		} //END FUNCTION

		function getData() {
			return gridData;
		} //END FUNCTION

		function getSecondaryHeaderRow() {
			return $secondaryHeaders[0];
		} //END FUNCTION

		function showSecondaryHeaderRow() {
			options.showSecondaryHeaderRow = true;
			$secondaryHeaderScroller.slideDown('fast', resizeCanvas);
		} //END FUNCTION

		function hideSecondaryHeaderRow() {
			options.showSecondaryHeaderRow = false;
			$secondaryHeaderScroller.slideUp('fast', resizeCanvas);
		} //END FUNCTION

		//////////////////////////////////////////////////////////////////////////////////////////////
		// Rendering / Scrolling

		function scrollTo(y) {
			let oldOffset = offset;
			page = Math.min(n-1, Math.floor(y / ph));
			offset = Math.round(page * cj);
			let newScrollTop = y - offset;
			if(offset != oldOffset) {
				let range = getVisibleRange(newScrollTop);
				cleanupRows(range.top,range.bottom);
				updateRowPositions();
			}
			if(prevScrollTop != newScrollTop) {
				scrollDir = (prevScrollTop + oldOffset < newScrollTop + offset) ? 1 : -1;
				$viewport[0].scrollTop = (lastRenderedScrollTop = scrollTop = prevScrollTop = newScrollTop);
				if(self.onViewportChanged) {
					self.onViewportChanged();
				}
			}
		} //END FUNCTION

		function defaultFormatter(row, cell, value, columnDef, dataContext) {
			//return (value === null || value === undefined) ? '' : value;
			return (value === null || value === undefined) ? '' : escapeHtml(value);
		} //END FUNCTION

		function getFormatter(column) {
			return column.formatter || (options.formatterFactory && options.formatterFactory.getFormatter(column)) || defaultFormatter;
		} //END FUNCTION

		function getEditor(column) {
			return column.editor || (options.editorFactory && options.editorFactory.getEditor(column));
		} //END FUNCTION

		function appendRowHtml(stringArray,row) {
			let d = gridDataGetItem(row);
			let dataLoading = row < gridDataGetLength() && !d;
			let cellCss;
			let css = 'slick-row ' + (dataLoading ? ' loading' : '') + (selectedRowsLookup[row] ? ' selected slickgrid-row-selected' : '') + (row % 2 == 1 ? ' odd' : ' even'); // unixman: ' selected slick-state-active'
			// if the user has specified a function to provide additional per-row css classes, call it here
			if(options.rowCssClasses) {
				css += ' ' + options.rowCssClasses(d);
			} //end if
			stringArray.push('<div class="slick-uiwidget-content ' + escapeHtml(css) + '" row="' + escapeHtml(row) + '" style="top:' + Math.ceil(options.rowHeight*row-offset) + 'px">');
			for(let i=0, cols=columns.length; i<cols; i++) {
				let m = columns[i];
				cellCss = 'slick-cell c' + i + (m.cssClass ? ' ' + m.cssClass : '');
				if(highlightedCells && highlightedCells[row] && highlightedCells[row][m.id]) {
					cellCss += (' ' + options.cellHighlightCssClass);
				} //end if
				stringArray.push('<div class="' + escapeHtml(cellCss) + '">');
				if(d) { // if there is a corresponding row (if not, this is the Add New row or this data hasn't been loaded yet)
					stringArray.push(getFormatter(m)(row, i, d[m.field], m, d));
				} //end if
				stringArray.push('</div>');
			} //end for
			stringArray.push('</div>');
		} //END FUNCTION

		function cleanupRows(rangeToKeep) {
			for(let i in rowsCache) {
				if(((i = parseInt(i, 10)) !== currentRow) && (i < rangeToKeep.top || i > rangeToKeep.bottom)) {
					removeRowFromCache(i);
				}
			}
		} //END FUNCTION

		function invalidate() {
			updateRowCount();
			removeAllRows();
			render();
		} //END FUNCTION

		function removeAllRows() {
			if(currentEditor) {
				makeSelectedCellNormal();
			}
			//-- #fix from v2 (unixman)
		//	$canvas[0].innerHTML = '';
			for(let row in rowsCache) {
				removeRowFromCache(row);
			}
			$canvas.empty().html('');
			//--
			rowsCache = {};
			postProcessedRows = {};
			counter_rows_removed += renderedRows;
			renderedRows = 0;
		} //END FUNCTION

		function removeRowFromCache(row) {
			const node = rowsCache[row];
			if(!node) {
				return;
			}
			$canvas[0].removeChild(node);
			delete rowsCache[row];
			delete postProcessedRows[row];
			renderedRows--;
			counter_rows_removed++;
		} //END FUNCTION

		function removeRows(rows) {
			let i, rl, nl;
			if(!rows || !rows.length) {
				return;
			}
			scrollDir = 0;
			let nodes = [];
			for(i=0, rl=rows.length; i<rl; i++) {
				if(currentEditor && currentRow === i) {
					makeSelectedCellNormal();
				}
				if(rowsCache[rows[i]]) {
					nodes.push(rows[i]);
				}
			}
			if(renderedRows > 10 && nodes.length === renderedRows) {
				removeAllRows();
			} else {
				for(i=0, nl=nodes.length; i<nl; i++) {
					removeRowFromCache(nodes[i]);
				}
			}
		} //END FUNCTION

		function removeRow(row) {
			removeRows([row]);
		} //END FUNCTION

		function updateCell(row,cell) {
			if(!rowsCache[row]) {
				return;
			}
			let $cell = $(rowsCache[row]).children().eq(cell);
			if($cell.length === 0) {
				return;
			}
			let m = columns[cell], d = gridDataGetItem(row);
			if(currentEditor && currentRow === row && currentCell === cell) {
				currentEditor.loadValue(d);
			} else {
				$cell[0].innerHTML = d ? getFormatter(m)(row, cell, d[m.field], m, d) : '';
				invalidatePostProcessingResults(row);
			}
		} //END FUNCTION

		function updateRow(row) {
			if(!rowsCache[row]) {
				return;
			}
			$(rowsCache[row]).children().each(function(i) {
				let m = columns[i];
				if(row === currentRow && i === currentCell && currentEditor) {
					currentEditor.loadValue(gridDataGetItem(currentRow));
				} else if(gridDataGetItem(row)) {
					this.innerHTML = getFormatter(m)(row, i, gridDataGetItem(row)[m.field], m, gridDataGetItem(row));
				} else {
					this.innerHTML = '';
				}
			});
			invalidatePostProcessingResults(row);
		} //END FUNCTION

		function resizeCanvas() {
			const newViewportH = options.rowHeight * (gridDataGetLength() + (options.enableAddRow ? 1 : 0) + (options.leaveSpaceForNewRows? numVisibleRows - 1 : 0));
			if(options.autoHeight) { // use computed height to set both canvas _and_ divMainScroller, effectively hiding scroll bars.
				$viewport.height(newViewportH);
			} else {
				$viewport.height(
					$container.innerHeight() -
					$headerScroller.outerHeight() -
					(options.showSecondaryHeaderRow ? $secondaryHeaderScroller.outerHeight() : 0)
				);
			}
			viewportW = $viewport.innerWidth();
			viewportH = $viewport.innerHeight();
			numVisibleRows = Math.ceil(viewportH / options.rowHeight);
			let totalWidth = 0;
			$headers.find('.slick-header-column').each(function() {
				totalWidth += $(this).outerWidth();
			});
			setCanvasWidth(totalWidth);
			updateRowCount();
			render();
		} //END FUNCTION

		function resizeAndRender() {
			if(options.forceFitColumns) {
				autosizeColumns();
			} else {
				resizeCanvas();
			}
		} //END FUNCTION

		function updateRowCount() {
			const newRowCount = gridDataGetLength() + (options.enableAddRow?1:0) + (options.leaveSpaceForNewRows?numVisibleRows-1:0);
			const oldH = h;
			// remove the rows that are now outside of the data range
			// this helps avoid redundant calls to .removeRow() when the size of the data decreased by thousands of rows
			let l = options.enableAddRow ? gridDataGetLength() : gridDataGetLength() - 1;
			for(let i in rowsCache) {
				if(i >= l) {
					removeRowFromCache(i);
				}
			}
			th = Math.max(options.rowHeight * newRowCount, viewportH - scrollbarDimensions.height);
			if(th < maxSupportedCssHeight) {
				// just one page
				h = ph = th;
				n = 1;
				cj = 0;
			} else {
				// break into pages
				h = maxSupportedCssHeight;
				ph = h / 100;
				n = Math.floor(th / ph);
				cj = (th - h) / (n - 1);
			}
			if(h !== oldH) {
				$canvas.css('height',h);
				scrollTop = $viewport[0].scrollTop;
			}
			const oldScrollTopInRange = (scrollTop + offset <= th - viewportH);
			if(th == 0 || scrollTop == 0) {
				page = offset = 0;
			} else if(oldScrollTopInRange) {
				// maintain virtual position
				scrollTo(scrollTop+offset);
			} else {
				// scroll to bottom
				scrollTo(th-viewportH);
			}
			if(h != oldH && options.autoHeight) {
				resizeCanvas();
			}
		} //END FUNCTION

		function getVisibleRange(viewportTop) {
			if(viewportTop == null) {
				viewportTop = scrollTop;
			}
			return {
				top: Math.floor((scrollTop+offset)/options.rowHeight),
				bottom: Math.ceil((scrollTop+offset+viewportH)/options.rowHeight)
			};
		} //END FUNCTION

		function getRenderedRange(viewportTop) {
			let range = getVisibleRange(viewportTop);
			let buffer = Math.round(viewportH/options.rowHeight);
			let minBuffer = 3;
			if(scrollDir == -1) {
				range.top -= buffer;
				range.bottom += minBuffer;
			} else if(scrollDir == 1) {
				range.top -= minBuffer;
				range.bottom += buffer;
			} else {
				range.top -= minBuffer;
				range.bottom += minBuffer;
			}
			range.top = Math.max(0,range.top);
			range.bottom = Math.min(options.enableAddRow ? gridDataGetLength() : gridDataGetLength() - 1,range.bottom);
			return range;
		} //END FUNCTION

		function renderRows(range) {
			let i, l,
				parentNode = $canvas[0],
				rowsBefore = renderedRows,
				stringArray = [],
				rows = [],
				startTimestamp = new Date(),
				needToReselectCell = false;
			for(i = range.top; i <= range.bottom; i++) {
				if(rowsCache[i]) { continue; }
				renderedRows++;
				rows.push(i);
				appendRowHtml(stringArray,i);
				if(currentCellNode && currentRow === i) {
					needToReselectCell = true;
				}
				counter_rows_rendered++;
			}
			let x = document.createElement('div');
			x.innerHTML = stringArray.join('');
			for(i = 0, l = x.childNodes.length; i < l; i++) {
				rowsCache[rows[i]] = parentNode.appendChild(x.firstChild);
			}
			if(needToReselectCell) {
				currentCellNode = $(rowsCache[currentRow]).children().eq(currentCell)[0];
				setSelectedCell(currentCellNode,false);
			}
			if(renderedRows - rowsBefore > 5) {
				avgRowRenderTime = (new Date() - startTimestamp) / (renderedRows - rowsBefore);
			}
		} //END FUNCTION

		function startPostProcessing() {
			if(!options.enableAsyncPostRender) { return; }
			clearTimeout(h_postrender);
			h_postrender = setTimeout(asyncPostProcessRows, options.asyncPostRenderDelay);
		} //END FUNCTION

		function invalidatePostProcessingResults(row) {
			delete postProcessedRows[row];
			postProcessFromRow = Math.min(postProcessFromRow,row);
			postProcessToRow = Math.max(postProcessToRow,row);
			startPostProcessing();
		} //END FUNCTION

		function updateRowPositions() {
			for(let row in rowsCache) {
				rowsCache[row].style.top = (row*options.rowHeight-offset) + 'px';
			}
		} //END FUNCTION

		function render() {
			const visible = getVisibleRange();
			const rendered = getRenderedRange();
			// remove rows no longer in the viewport
			cleanupRows(rendered);
			// add new rows
			renderRows(rendered);
			postProcessFromRow = visible.top;
			postProcessToRow = Math.min(options.enableAddRow ? gridDataGetLength() : gridDataGetLength() - 1, visible.bottom);
			startPostProcessing();
			lastRenderedScrollTop = scrollTop;
			h_render = null;
		} //END FUNCTION

		function handleScroll() {
			//--
			scrollTop = $viewport[0].scrollTop;
			let scrollLeft = $viewport[0].scrollLeft;
			let scrollDist = Math.abs(scrollTop - prevScrollTop);
			//--
			if(scrollLeft !== prevScrollLeft) {
				prevScrollLeft = scrollLeft;
				$headerScroller[0].scrollLeft = scrollLeft;
				$secondaryHeaderScroller[0].scrollLeft = scrollLeft;
			}
			//--
			if(!scrollDist) {
				return;
			}
			//--
			scrollDir = prevScrollTop < scrollTop ? 1 : -1;
			prevScrollTop = scrollTop;
			//-- switch virtual pages if needed
			if(scrollDist < viewportH) {
				scrollTo(scrollTop + offset);
			} else {
				let oldOffset = offset;
				page = Math.min(n - 1, Math.floor(scrollTop * ((th - viewportH) / (h - viewportH)) * (1 / ph)));
				offset = Math.round(page * cj);
				if(oldOffset != offset) {
					removeAllRows();
				} //end if
			} //end if else
			//--
			if(h_render) {
				clearTimeout(h_render);
			} //end if
			//--
			if(Math.abs(lastRenderedScrollTop - scrollTop) < viewportH) {
				render();
			} else {
				h_render = setTimeout(render, 50);
			} //end if else
			//--
			if(self.onViewportChanged) {
				self.onViewportChanged();
			} //end if
			//--
		} //END FUNCTION

		function asyncPostProcessRows() {
			while(postProcessFromRow <= postProcessToRow) {
				let row = (scrollDir >= 0) ? postProcessFromRow++ : postProcessToRow--;
				let rowNode = rowsCache[row];
				if(!rowNode || postProcessedRows[row] || row>=gridDataGetLength()) {
					continue;
				}
				let d = gridDataGetItem(row), cellNodes = rowNode.childNodes;
				for(let i=0, j=0, l=columns.length; i<l; ++i) {
					let m = columns[i];
					//-- unixman bug fix
					//if(m.asyncPostRender) { m.asyncPostRender(cellNodes[j], postProcessFromRow, d, m); }
					if(m.asyncPostRender) { m.asyncPostRender(cellNodes[j], row, d, m); }
					//-- #end unixman
					++j;
				}
				postProcessedRows[row] = true;
				h_postrender = setTimeout(asyncPostProcessRows, options.asyncPostRenderDelay);
				return;
			}
		} //END FUNCTION

		function setHighlightedCells(cellsToHighlight) {
			let i, $cell, hasHighlight, hadHighlight;
			for(let row in rowsCache) {
				for(i=0; i<columns.length; i++) {
					hadHighlight = highlightedCells && highlightedCells[row] && highlightedCells[row][columns[i].id];
					hasHighlight = cellsToHighlight && cellsToHighlight[row] && cellsToHighlight[row][columns[i].id];
					if(hadHighlight != hasHighlight) {
						$cell = $(rowsCache[row]).children().eq(i);
						if($cell.length) {
							$cell.toggleClass(options.cellHighlightCssClass);
						}
					}
				}
			}
			highlightedCells = cellsToHighlight;
		} //END FUNCTION

		function flashCell(row, cell, speed) {
			speed = speed || 100;
			if(rowsCache[row]) {
				let $cell = $(rowsCache[row]).children().eq(cell);
				function toggleCellClass(times) {
					if(!times) return;
					setTimeout(function() {
						$cell.queue(function() {
							$cell.toggleClass(options.cellFlashingCssClass).dequeue();
							toggleCellClass(times-1);
						});
					},
					speed);
				}
				toggleCellClass(4);
			}
		} //END FUNCTION

		//////////////////////////////////////////////////////////////////////////////////////////////
		// Interactivity

		function getSiblingIndex(node) {
			let idx = 0;
			while(node && node.previousSibling) {
				idx++;
				node = node.previousSibling;
			}
			return idx;
		} //END FUNCTION

		function handleKeyDown(e) {
			// give registered handler chance to process the keyboard event
			let handled = (self.onKeyDown && // a handler must be registered
				!options.editorLock.isActive() && // grid must not be in edit mode;
				self.onKeyDown(e, currentRow, currentCell)); // handler must return truthy-value to indicate it handled the event
			if(!handled) {
				if(!e.shiftKey && !e.altKey && !e.ctrlKey) {
					if(e.which == 27) {
						if(!options.editorLock.isActive()) {
							return; // no editing mode to cancel, allow bubbling and default processing (exit without cancelling the event)
						}
						cancelEditAndSetFocus();
					} else if(e.which == 37) {
						navigateLeft();
					} else if(e.which == 39) {
						navigateRight();
					} else if(e.which == 38) {
						navigateUp();
					} else if(e.which == 40) {
						navigateDown();
					} else if(e.which == 9) {
						navigateNext();
					} else if(e.which == 13) {
						if(options.editable) {
							if(currentEditor) {
								// adding new row
								if(currentRow === defaultGetLength()) {
									navigateDown();
								} else {
									commitEditAndSetFocus();
								}
							} else {
								if(options.editorLock.commitCurrentEdit()) {
									makeSelectedCellEditable();
								}
							}
						}
					} else {
						return;
					}
				} else if(e.which == 9 && e.shiftKey && !e.ctrlKey && !e.altKey) {
					navigatePrev();
				} else {
					return;
				}
			}
			// the event has been handled so don't let parent element (bubbling/propagation) or browser (default) handle it
			e.stopPropagation();
			e.preventDefault();
			try {
				e.originalEvent.keyCode = 0; // prevent default behaviour for special keys in IE browsers (F3, F5, etc.)
			}
			catch (error) {} // ignore exceptions - setting the original event's keycode throws access denied exception for 'Ctrl' (hitting control key only, nothing else), 'Shift' (maybe others)
		} //END FUNCTION

		function handleClick(e) {
			const $cell = $(e.target).closest('.slick-cell', $canvas);
			if($cell.length === 0) {
				return;
			}
			// are we editing this cell?
			if(currentCellNode === $cell[0] && currentEditor !== null) {
				return;
			}
			let row = parseInt($cell.parent().attr('row'), 10);
			let cell = getSiblingIndex($cell[0]);
			let validated = null;
			let c = columns[cell];
			let item = gridDataGetItem(row);
			// is this a 'select' column or a Ctrl/Shift-click?
		//	if(item && (c.behavior === 'selectAndMove' || c.behavior === 'select' || (e.ctrlKey || e.shiftKey))) {
			if(item && (c.behavior === 'selectAndMove' || c.behavior === 'select' || (e.ctrlKey || e.altKey || e.shiftKey))) { // fix by unixman
				e.preventDefault();
				// grid must not be in edit mode
				validated = options.editorLock.commitCurrentEdit();
				if(validated) {
					let selection = getSelectedRows();
					let idx = $.inArray(row, selection);
					if(!e.ctrlKey && !e.shiftKey && !e.altKey) {
						selection = [row];
					} else if(options.multiSelect) {
						if(idx === -1 && (e.ctrlKey || e.altKey)) {
							selection.push(row);
						} else if(idx !== -1 && (e.ctrlKey || e.altKey)) {
							selection = $.grep(selection, function(o, i) { return (o !== row); });
						} else if(selection.length && e.shiftKey) {
							let last = selection.pop();
							let from = Math.min(row, last);
							let to = Math.max(row, last);
							selection = [];
							for(let i = from; i <= to; i++) {
								if(i !== last) {
									selection.push(i);
								}
							}
							selection.push(last);
						}
					}
					resetCurrentCell();
					setSelectedRows(selection);
					if(self.onSelectedRowsChanged) {
						self.onSelectedRowsChanged();
					}
					//if(!$.browser.msie) {
					if(!navigator.userAgent.toLowerCase().match(/msie/)) { // unixman: fix from v.2.2 to avoid use $.browser
					//	$canvas[0].focus();
						$canvas.focus(); // use jquery focus instead of pure js (fix by unixman)
					}
					return false;
				}
			}
			// do we have any registered handlers?
			if(item && self.onClick) {
				// grid must not be in edit mode
				validated = options.editorLock.commitCurrentEdit();
				if(validated) {
					// handler will return true if the event was handled
					if(self.onClick(e, row, cell)) {
						e.stopPropagation();
						e.preventDefault();
						return false;
					}
				}
			}
			if(options.enableCellNavigation && !columns[cell].unselectable) {
				// commit current edit before proceeding
				if(validated === true || (validated === null && options.editorLock.commitCurrentEdit())) {
					scrollRowIntoView(row,false);
					setSelectedCellAndRow($cell[0], (row === defaultGetLength()) || options.autoEdit);
				}
			}
		} //END FUNCTION

		function handleContextMenu(e) {
			const $cell = $(e.target).closest('.slick-cell', $canvas);
			if($cell.length === 0) {
				return;
			}
			// are we editing this cell?
			if(currentCellNode === $cell[0] && currentEditor !== null) {
				return;
			}
			let row = parseInt($cell.parent().attr('row'), 10);
			let cell = getSiblingIndex($cell[0]);
			let validated = null;
			// do we have any registered handlers?
			if(gridDataGetItem(row) && self.onContextMenu) {
				// grid must not be in edit mode
				validated = options.editorLock.commitCurrentEdit();
				if(validated) {
					// handler will return true if the event was handled
					if(self.onContextMenu(e, row, cell)) {
						e.stopPropagation();
						e.preventDefault();
						return false;
					}
				}
			}
		} //END FUNCTION

		function handleDblClick(e) {
			const $cell = $(e.target).closest('.slick-cell', $canvas);
			if($cell.length === 0) {
				return;
			}
			// are we editing this cell?
			if(currentCellNode === $cell[0] && currentEditor !== null) {
				return;
			}
			let row = parseInt($cell.parent().attr('row'), 10);
			let cell = getSiblingIndex($cell[0]);
			let validated = null;
			// do we have any registered handlers?
			if(gridDataGetItem(row) && self.onDblClick) {
				// grid must not be in edit mode
				validated = options.editorLock.commitCurrentEdit();
				if(validated) {
					// handler will return true if the event was handled
					if(self.onDblClick(e, row, cell)) {
						e.stopPropagation();
						e.preventDefault();
						return false;
					}
				}
			}
			if(options.editable) {
				gotoCell(row, cell, true);
			}
		} //END FUNCTION

		function handleHeaderContextMenu(e) {
			if(self.onHeaderContextMenu && options.editorLock.commitCurrentEdit()) {
				e.preventDefault();
				const selectedElement = $(e.target).closest('.slick-header-column', '.slick-header-columns');
				self.onHeaderContextMenu(e, columns[self.getColumnIndex(selectedElement.data('fieldId'))]);
			}
		} //END FUNCTION

		function handleHeaderClick(e) {
			const $col = $(e.target).closest('.slick-header-column');
			if($col.length ==0) {
				return;
			}
			const column = columns[getSiblingIndex($col[0])];
			if(self.onHeaderClick && options.editorLock.commitCurrentEdit()) {
				e.preventDefault();
				self.onHeaderClick(e, column);
			}
		} //END FUNCTION

		function handleHover(e) {
			if(!options.enableAutoTooltips) {
				return;
			}
			const $cell = $(e.target).closest('.slick-cell',$canvas);
			if($cell.length) {
				if($cell.innerWidth() < $cell[0].scrollWidth) {
					const text = $.trim($cell.text() || '');
					$cell.attr('title', (options.toolTipMaxLength && text.length > options.toolTipMaxLength) ?  text.substr(0, options.toolTipMaxLength - 3) + '...' : text);
				} else {
					$cell.attr('title', '');
				}
			}
		} //END FUNCTION

		function cellExists(row,cell) {
			return !(row < 0 || row >= gridDataGetLength() || cell < 0 || cell >= columns.length);
		} //END FUNCTION

		function getCellFromPoint(x,y) {
			const row = Math.floor((y+offset)/options.rowHeight);
			let cell = 0;
			let w = 0;
			for(let i=0; i<columns.length && w<x; i++) {
				w += columns[i].width;
				cell++;
			}
			return {
				row: row,
				cell: cell-1,
			};
		} //END FUNCTION

		function getCellFromEvent(e) {
			const $cell = $(e.target).closest('.slick-cell', $canvas);
			if(!$cell.length) {
				return null;
			}
			return {
				row: $cell.parent().attr('row') | 0,
				cell: getSiblingIndex($cell[0]),
			};
		} //END FUNCTION

		function getCellNodeBox(row,cell) {
			if(!cellExists(row,cell)) {
				return null;
			}
			let y1 = row * options.rowHeight - offset;
			let y2 = y1 + options.rowHeight - 1;
			let x1 = 0;
			for(let i=0; i<cell; i++) {
				x1 += columns[i].width;
			}
			x1 = x1 - cell; // fix by unixman (each cell have a right border +)
			let x2 = x1 + columns[cell].width;
			return {
				top: y1,
				left: x1,
				bottom: y2,
				right: x2
			};
		} //END FUNCTION

		//////////////////////////////////////////////////////////////////////////////////////////////
		// Cell switching

		function resetCurrentCell() {
			setSelectedCell(null,false);
		} //END FUNCTION

		function focusOnCurrentCell() {
			// lazily enable the cell to receive keyboard focus
			$(currentCellNode)
				.attr('tabIndex',0)
				.attr('hideFocus',true);
			// IE7 tries to scroll the viewport so that the item being focused is aligned to the left border
			// IE-specific .setActive() sets the focus, but doesn't scroll
			//if($.browser.msie && parseInt($.browser.version) < 8)
			//	currentCellNode.setActive();
			//else # unixman: disable support for IE < 9
				currentCellNode.focus();
			let left = $(currentCellNode).position().left,
				right = left + $(currentCellNode).outerWidth(),
				scrollLeft = $viewport.scrollLeft(),
				scrollRight = scrollLeft + $viewport.width();
			if(left < scrollLeft) {
				$viewport.scrollLeft(left);
			} else if(right > scrollRight) {
				$viewport.scrollLeft(Math.min(left, right - $viewport[0].clientWidth));
			}
		} //END FUNCTION

		function setSelectedCell(newCell,editMode) {
			if(currentCellNode !== null) {
				makeSelectedCellNormal();
				$(currentCellNode).removeClass('selected');
			}
			currentCellNode = newCell;
			if(currentCellNode != null) {
				currentRow = parseInt($(currentCellNode).parent().attr('row'), 10);
				currentCell = getSiblingIndex(currentCellNode);
				$(currentCellNode).addClass('selected');
				if(options.editable && editMode && isCellPotentiallyEditable(currentRow,currentCell)) {
					clearTimeout(h_editorLoader);
					if(options.asyncEditorLoading) {
						h_editorLoader = setTimeout(makeSelectedCellEditable, options.asyncEditorLoadDelay);
					} else {
						makeSelectedCellEditable();
					}
				} else {
					focusOnCurrentCell()
				}
				if(self.onCurrentCellChanged) {
					self.onCurrentCellChanged(getCurrentCell());
				}
			} else {
				currentRow = null;
				currentCell = null;
			}
		} //END FUNCTION

		function setSelectedCellAndRow(newCell,editMode) {
			setSelectedCell(newCell,editMode);
			if(newCell) {
				setSelectedRows([currentRow]);
			} else {
				setSelectedRows([]);
			}
			if(self.onSelectedRowsChanged) {
				self.onSelectedRowsChanged();
			}
		} //END FUNCTION

		function clearTextSelection() {
			if(document.selection && document.selection.empty) {
				document.selection.empty();
			} else if(window.getSelection) {
				const sel = window.getSelection();
				if(sel && sel.removeAllRanges) {
					sel.removeAllRanges();
				}
			}
		} //END FUNCTION

		function isCellPotentiallyEditable(row,cell) {
			// is the data for this row loaded?
			if(row < gridDataGetLength() && !gridDataGetItem(row)) {
				return false;
			}
			// are we in the Add New row?  can we create new from this cell?
			if(columns[cell].cannotTriggerInsert && row >= gridDataGetLength()) {
				return false;
			}
			// does this cell have an editor?
			if(!getEditor(columns[cell])) {
				return false;
			}
			return true;
		} //END FUNCTION

		function makeSelectedCellNormal() {
			if(!currentEditor) {
				return;
			}
			if(self.onBeforeCellEditorDestroy) {
				self.onBeforeCellEditorDestroy(currentEditor);
			}
			currentEditor.destroy();
			currentEditor = null;
			if(currentCellNode) {
				$(currentCellNode).removeClass('editable invalid');
				if(gridDataGetItem(currentRow)) {
					const column = columns[currentCell];
					currentCellNode.innerHTML = getFormatter(column)(currentRow, currentCell, gridDataGetItem(currentRow)[column.field], column, gridDataGetItem(currentRow));
					invalidatePostProcessingResults(currentRow);
				}
			}
			// if there previously was text selected on a page (such as selected text in the edit cell just removed),
			// IE can't set focus to anything else correctly
			//if($.browser.msie) {
			if(navigator.userAgent.toLowerCase().match(/msie/)) { // unixman: fix from v.2.2 to avoid use $.browser
				clearTextSelection();
			}
			options.editorLock.deactivate(editController);
		} //END FUNCTION

		function makeSelectedCellEditable() {
			if(!currentCellNode) {
				return;
			}
			if(!options.editable) {
				throw 'Grid : makeSelectedCellEditable : should never get called when options.editable is false';
			}
			// cancel pending async call if there is one
			clearTimeout(h_editorLoader);
			if(!isCellPotentiallyEditable(currentRow,currentCell)) {
				return;
			}
			if(self.onBeforeEditCell && self.onBeforeEditCell(currentRow,currentCell,gridDataGetItem(currentRow)) === false) {
				focusOnCurrentCell();
				return;
			}
			options.editorLock.activate(editController);
			$(currentCellNode).addClass('editable');
			currentCellNode.innerHTML = '';
			const columnDef = columns[currentCell];
			const item = gridDataGetItem(currentRow);
			currentEditor = new (getEditor(columnDef))({
				grid: self,
				gridPosition: absBox($container[0]),
				position: absBox(currentCellNode),
				container: currentCellNode,
				column: columnDef,
				item: item || {},
				commitChanges: commitEditAndSetFocus,
				cancelChanges: cancelEditAndSetFocus
			});
			if(item) {
				currentEditor.loadValue(item);
			}
			serializedEditorValue = currentEditor.serializeValue();
			if(currentEditor.position) {
				handleCurrentCellPositionChange();
			}
		} //END FUNCTION

		function commitEditAndSetFocus() {
			// if the commit fails, it would do so due to a validation error
			// if so, do not steal the focus from the editor
			if(options.editorLock.commitCurrentEdit()) {
				focusOnCurrentCell();
				if(options.autoEdit) {
					navigateDown();
				}
			}
		} //END FUNCTION

		function cancelEditAndSetFocus() {
			if(options.editorLock.cancelCurrentEdit()) {
				focusOnCurrentCell();
			}
		} //END FUNCTION

		function absBox(elem) {
			const box = {
				top: elem.offsetTop,
				left: elem.offsetLeft,
				bottom: 0,
				right: 0,
				width: $(elem).outerWidth(),
				height: $(elem).outerHeight(),
				visible: true,
			};
			box.bottom = box.top + box.height;
			box.right = box.left + box.width;
			// walk up the tree
			let offsetParent = elem.offsetParent;
			while((elem = elem.parentNode) != document.body) {
				if(box.visible && elem.scrollHeight != elem.offsetHeight && $(elem).css('overflowY') != 'visible') {
					box.visible = box.bottom > elem.scrollTop && box.top < elem.scrollTop + elem.clientHeight;
				}
				if(box.visible && elem.scrollWidth != elem.offsetWidth && $(elem).css('overflowX') != 'visible') {
					box.visible = box.right > elem.scrollLeft && box.left < elem.scrollLeft + elem.clientWidth;
				}
				box.left -= elem.scrollLeft;
				box.top -= elem.scrollTop;
				if(elem === offsetParent) {
					box.left += elem.offsetLeft;
					box.top += elem.offsetTop;
					offsetParent = elem.offsetParent;
				}
				box.bottom = box.top + box.height;
				box.right = box.left + box.width;
			}
			return box;
		} //END FUNCTION

		function getCurrentCellPosition(){
			return absBox(currentCellNode);
		} //END FUNCTION

		function getGridPosition(){
			return absBox($container[0])
		} //END FUNCTION

		function handleCurrentCellPositionChange() {
			if(!currentCellNode) {
				return;
			}
			let cellBox;
			if(self.onCurrentCellPositionChanged){
				cellBox = getCurrentCellPosition();
				self.onCurrentCellPositionChanged(cellBox);
			}
			if(currentEditor) {
				cellBox = cellBox || getCurrentCellPosition();
				if(currentEditor.show && currentEditor.hide) {
					if(!cellBox.visible) {
						currentEditor.hide();
					} else {
						currentEditor.show();
					}
				}
				if(currentEditor.position) {
					currentEditor.position(cellBox);
				}
			}
		} //END FUNCTION

		function getCellEditor() {
			return currentEditor;
		} //END FUNCTION

		function getCurrentCell() {
			if(!currentCellNode) {
				return null;
			} else {
				return {row: currentRow, cell: currentCell};
			}
		} //END FUNCTION

		function getCurrentCellNode() {
			return currentCellNode;
		} //END FUNCTION

		function scrollRowIntoView(row, doPaging) {
			const rowAtTop = row * options.rowHeight;
			const rowAtBottom = (row + 1) * options.rowHeight - viewportH + (viewportHasHScroll?scrollbarDimensions.height:0);
			if((row + 1) * options.rowHeight > scrollTop + viewportH + offset) { // need to page down?
				scrollTo(doPaging ? rowAtTop : rowAtBottom);
				render();
			} else if(row * options.rowHeight < scrollTop + offset) { // or page up?
				scrollTo(doPaging ? rowAtBottom : rowAtTop);
				render();
			}
		} //END FUNCTION

		function gotoDir(dy, dx, rollover) {
			if(!currentCellNode || !options.enableCellNavigation) {
				return;
			}
			if(!options.editorLock.commitCurrentEdit()) {
				return;
			}
			function selectableCellFilter() {
				return !columns[getSiblingIndex(this)].unselectable
			}
			let nextRow = rowsCache[currentRow + dy];
			let nextCell = (nextRow && currentCell + dx >= 0)
					? $(nextRow).children().eq(currentCell+dx).filter(selectableCellFilter)
					: null;
			if(nextCell && !nextCell.length) {
				let nodes = $(nextRow).children()
					.filter(function(index) { return (dx>0) ? index > currentCell + dx : index < currentCell + dx })
					.filter(selectableCellFilter);
				if(nodes && nodes.length) {
					nextCell = (dx>0)
							? nodes.eq(0)
							: nodes.eq(nodes.length-1);
				}
			}
			if(rollover && dy === 0 && !(nextRow && nextCell && nextCell.length)) {
				if(!nextCell || !nextCell.length) {
					nextRow = rowsCache[currentRow + dy + ((dx>0)?1:-1)];
					let nodes = $(nextRow).children().filter(selectableCellFilter);
					if(dx > 0) {
						nextCell = nextRow
							? nodes.eq(0)
							: null;
					} else {
						nextCell = nextRow
							? nodes.eq(nodes.length-1)
							: null;
					}
				}
			}
			if(nextRow && nextCell && nextCell.length) {
				// if selecting the 'add new' row, start editing right away
				let row = parseInt($(nextRow).attr('row'), 10);
				let isAddNewRow = (row == defaultGetLength());
				scrollRowIntoView(row,!isAddNewRow);
				setSelectedCellAndRow(nextCell[0], isAddNewRow || options.autoEdit);
				// if no editor was created, set the focus back on the cell
				if(!currentEditor) {
					focusOnCurrentCell();
				}
			} else {
				focusOnCurrentCell();
			}
		} //END FUNCTION

		function gotoCell(row, cell, forceEdit) {
			if(row > gridDataGetLength() || row < 0 || cell >= columns.length || cell < 0) {
				return;
			}
			if(!options.enableCellNavigation || columns[cell].unselectable) {
				return;
			}
			if(!options.editorLock.commitCurrentEdit()) {
				return;
			}
			scrollRowIntoView(row, false);
			let newCell = null;
			if(!columns[cell].unselectable) {
				newCell = $(rowsCache[row]).children().eq(cell)[0];
			}
			// if selecting the 'add new' row, start editing right away
			setSelectedCellAndRow(newCell, forceEdit || (row === gridDataGetLength()) || options.autoEdit);
			// if no editor was created, set the focus back on the cell
			if(!currentEditor) {
				focusOnCurrentCell();
			}
		} //END FUNCTION

		function navigateUp() {
			gotoDir(-1, 0, false);
		} //END FUNCTION

		function navigateDown() {
			gotoDir(1, 0, false);
		} //END FUNCTION

		function navigateLeft() {
			gotoDir(0, -1, false);
		} //END FUNCTION

		function navigateRight() {
			gotoDir(0, 1, false);
		} //END FUNCTION

		function navigatePrev() {
			gotoDir(0, -1, true);
		} //END FUNCTION

		function navigateNext() {
			gotoDir(0, 1, true);
		} //END FUNCTION

		//////////////////////////////////////////////////////////////////////////////////////////////
		// IEditor implementation for the editor lock

		function commitCurrentEdit() {
			let item = gridDataGetItem(currentRow);
			let column = columns[currentCell];
			if(currentEditor) {
				if(currentEditor.isValueChanged()) {
					let validationResults = currentEditor.validate();
					if(validationResults.valid) {
						if(currentRow < gridDataGetLength()) {
							let editCommand = {
								row: currentRow,
								cell: currentCell,
								editor: currentEditor,
								serializedValue: currentEditor.serializeValue(),
								prevSerializedValue: serializedEditorValue,
								execute: function() {
									this.editor.applyValue(item,this.serializedValue);
									updateRow(this.row);
								},
								undo: function() {
									this.editor.applyValue(item,this.prevSerializedValue);
									updateRow(this.row);
								}
							};
							if(options.editCommandHandler) {
								makeSelectedCellNormal();
								options.editCommandHandler(item,column,editCommand);

							} else {
								editCommand.execute();
								makeSelectedCellNormal();
							}
							if(self.onCellChange) {
								//self.onCellChange(currentRow,currentCell,item);
								self.onCellChange(currentRow,currentCell,item,column); // extend by unixman with column as columnDef
							}
						} else if(self.onAddNewRow) {
							let newItem = {};
							currentEditor.applyValue(newItem,currentEditor.serializeValue());
							makeSelectedCellNormal();
							self.onAddNewRow(newItem,column);
						}
						// check whether the lock has been re-acquired by event handlers
						return !options.editorLock.isActive();
					} else {
						// TODO: remove and put in onValidationError handlers in examples
						$(currentCellNode).addClass('invalid');
						$(currentCellNode).stop(true,true).effect('highlight', {color:'red'}, 300);
						if(self.onValidationError) {
							self.onValidationError(currentCellNode, validationResults, currentRow, currentCell, column);
						}
						currentEditor.focus();
						return false;
					}
				}
				makeSelectedCellNormal();
			}
			return true;
		} //END FUNCTION

		function cancelCurrentEdit() {
			makeSelectedCellNormal();
			return true;
		} //END FUNCTION


		//////////////////////////////////////////////////////////////////////////////////////////////
		// Debug

		this.debug = function() {
			let s = '';
			s += ('\n' + 'counter_rows_rendered:  ' + counter_rows_rendered);
			s += ('\n' + 'counter_rows_removed:  ' + counter_rows_removed);
			s += ('\n' + 'renderedRows:  ' + renderedRows);
			s += ('\n' + 'numVisibleRows:  ' + numVisibleRows);
			s += ('\n' + 'maxSupportedCssHeight:  ' + maxSupportedCssHeight);
			s += ('\n' + 'n(umber of pages):  ' + n);
			s += ('\n' + '(current) page:  ' + page);
			s += ('\n' + 'page height (ph):  ' + ph);
			s += ('\n' + 'scrollDir:  ' + scrollDir);
			console.log(s);
		}; //END FUNCTION

		// a debug helper to be able to access private members
		this.eval = function(expr) {
			return eval(expr);
		}; //END FUNCTION

		init();


		//////////////////////////////////////////////////////////////////////////////////////////////
		// Public API

		$.extend(this, {

			'slickGridVersion':            '1.4.3.2',

			// Events
			'onSort':                       null,
			'onHeaderContextMenu':          null,
			'onClick':                      null,
			'onDblClick':                   null,
			'onContextMenu':                null,
			'onKeyDown':                    null,
			'onAddNewRow':                  null,
			'onValidationError':            null,
			'onViewportChanged':            null,
			'onSelectedRowsChanged':        null,
			'onColumnsReordered':           null,
			'onColumnsResized':             null,
			'onBeforeMoveRows':             null,
			'onMoveRows':                   null,
			'onCellChange':                 null,
			'onBeforeEditCell':             null,
			'onBeforeCellEditorDestroy':    null,
			'onBeforeDestroy':              null,
			'onCurrentCellChanged':         null,
			'onCurrentCellPositionChanged': null,
			'onCellRangeSelected':          null,

			// Methods
			'getColumns':               getColumns,
			'setColumns':               setColumns,
			'getOptions':               getOptions,
			'setOptions':               setOptions,
			'getData':                  getData,
			'setData':                  setData,
			'destroy':                  destroy,
			'getColumnIndex':           getColumnIndex,
			'autosizeColumns':          autosizeColumns,
			'updateCell':               updateCell,
			'updateRow':                updateRow,
			'removeRow':                removeRow,
			'removeRows':               removeRows,
			'removeAllRows':            removeAllRows,
			'render':                   render,
			'invalidate':               invalidate,
			'setHighlightedCells':      setHighlightedCells,
			'flashCell':                flashCell,
			'getViewport':              getVisibleRange,
			'resizeCanvas':             resizeCanvas,
			'updateRowCount':           updateRowCount,
			'getCellFromPoint':         getCellFromPoint,
			'getCellFromEvent':         getCellFromEvent,
			'getCurrentCell':           getCurrentCell,
			'getCurrentCellNode':       getCurrentCellNode,
			'resetCurrentCell':         resetCurrentCell,
			'navigatePrev':             navigatePrev,
			'navigateNext':             navigateNext,
			'navigateUp':               navigateUp,
			'navigateDown':             navigateDown,
			'navigateLeft':             navigateLeft,
			'navigateRight':            navigateRight,
			'gotoCell':                 gotoCell,
			'editCurrentCell':          makeSelectedCellEditable,
			'getCellEditor':            getCellEditor,
			'scrollRowIntoView':        scrollRowIntoView,
			'getSelectedRows':          getSelectedRows,
			'setSelectedRows':          setSelectedRows,
			'getSecondaryHeaderRow':    getSecondaryHeaderRow,
			'showSecondaryHeaderRow':   showSecondaryHeaderRow,
			'hideSecondaryHeaderRow':   hideSecondaryHeaderRow,
			'setSortColumn':            setSortColumn,
			'getCurrentCellPosition' :  getCurrentCellPosition,
			'getGridPosition':          getGridPosition,

			// IEditor implementation
			'getEditController':        getEditController

		});

	} //END CLASS

	Object.freeze(SlickGrid);

	// Slick.Grid
	$.extend(true, window, {
		Slick: {
			Grid: SlickGrid,
			EditorLock: EditorLock,
			GlobalEditorLock: new EditorLock(),
		}
	});

}(jQuery));

// #END
