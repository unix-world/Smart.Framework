
// [LIB - Smart.Framework / JS / Smart Modal Scanner]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: jQuery, smartJ$ModalBox, smartJ$Utils, smartJ$Browser

//==================================================================
//==================================================================

//================== [ES6]

/**
 * jQuery Plugin class :: Smart ModalBoxScanner (ES6)
 *
 * @package Sf.Javascript:Browser
 *
 * @requires		jQuery
 * @requires		smartJ$ModalBox
 * @requires		smartJ$Utils
 * @requires		smartJ$Browser
 *
 * @private : used by smartJ$ModalBox only
 *
 * @desc on document.ready will use jQuery to scan all a[data-smart] links from a page to implement Modal iFrame / iPopUp based on smartJ$Browser
 * @author unix-world.org
 * @license BSD
 * @file ifmodalbox_scanner.js
 * @version 20250214
 * @class jQuery.Plugin::smartJ$ModalBox@Scanner
 * @static
 *
 */
(() => {

	const _p$ = console;

	const $ = jQuery; // jQuery referencing

	const _Utils$ = smartJ$Utils;
	const _BwUtils$ = smartJ$Browser;
	const _ModalBox$ = smartJ$ModalBox;

	$(() => { // ON DOCUMENT READY
		//--
		const version = _ModalBox$.getVersion();
		//--
		//$('body').delegate('a[data-smart]', 'click', function(el) { // delegate() does the job also with new dom inserted links
		$('body').on('click', 'a[data-smart]', (el) => { // jQuery 3+ : it is equivalent with delegate() which was deprecated
			//--
			const $el = $(el.currentTarget);
			//--
			const dataSmart = $el.attr('data-smart');
			if(!dataSmart) {
				return true; // let click function as default
			} //end if
			//--
			const isModal = RegExp(/^open.modal/i).test(dataSmart);
			const isPopup = RegExp(/^open.popup/i).test(dataSmart);
			if((isModal !== true) && (isPopup !== true)) { // does not have proper syntax
				return true; // let click function as default
			} //end if
			//--
			const attrHref = _Utils$.stringPureVal($el.attr('href'), true); // cast to string, trim
			if(attrHref == '') {
				_p$.error('iFrmBox Scanner (' + version + ')', 'The Clicked Data-Smart [' + dataSmart + '] Link has no Href Attribute: `' + _Utils$.stringTrim($el.text()) + '`');
				return false;
			} //end if
			//--
			let attrTarget = _Utils$.stringPureVal($el.attr('target'), true); // cast to string, trim
			if(attrTarget == '') {
				attrTarget = '_blank';
			} //end if
			//--
			let winWidth = _Utils$.format_number_int(parseInt($(window).width()), false);
			if(winWidth < 200) {
				winWidth = 200;
			} //end if
			let winHeight = parseInt($(window).height());
			if(winHeight < 100) {
				winHeight = 100;
			} //end if
			//--
			const aDim = dataSmart.match(/[0-9]+(\.[0-9][0-9]?)?/g); // dataSmart.match(/[0-9]+/g);
			let w = winWidth; // (aDim && (aDim[0] > 0)) ? aDim[0] : winWidth;
			let h = winHeight; // (aDim && (aDim[1] > 0)) ? aDim[1] : winHeight;
			let u = (aDim && (aDim[2] > 0)) ? aDim[2] : 0;
			//--
			if(aDim) {
				if(aDim[0] > 0) {
					if(aDim[0] < 1) {
						w = aDim[0] * winWidth;
					} else {
						w = aDim[0];
					} //end if else
				} //end if
				if(aDim[1] > 0) {
					if(aDim[1] < 1) {
						h = aDim[1] * winHeight;
					} else {
						h = aDim[1];
					} //end if else
				} //end if
			} //end if
			//--
			w = _Utils$.format_number_int(parseInt(w), false);
			h = _Utils$.format_number_int(parseInt(h), false);
			u = _Utils$.format_number_int(parseInt(u), false);
			//--
			if(w > winWidth) {
				w = _Utils$.format_number_int(parseInt(winWidth * 0.9), false);
			} //end if
			if(w < 200) {
				w = 200;
			} //end if
			if(h > winHeight) {
				h = _Utils$.format_number_int(parseInt(winHeight * 0.9), false);
			} //end if
			if(h < 100) {
				h = 100;
			} //end if
			//--
			let mode = 0; // 1 = popup, 0 = modal if not in modal, -1 = modal
			switch(u) {
				case 1:
					mode = -1; // force modal
					break;
				default:
					mode = 0; // default
			} //end switch
			//--
			if(isModal === true) {
				_BwUtils$.PopUpLink(attrHref, attrTarget, w, h, mode, 1);
			} else if(isPopup === true) {
				_BwUtils$.PopUpLink(attrHref, attrTarget, w, h, 1, 1);
			} //end if else
			//--
			return false;
			//--
		});
		//--
	}); //END ON DOCUMENT READY FUNCTION

})();

//==================================================================
//==================================================================

// #END
