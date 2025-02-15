
// [LIB - Smart.Framework / JS / Browser Check]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: -

//==================================================================
//==================================================================

//================== [ES6]

/**
 * CLASS :: Smart BrowserTest (ES6)
 *
 * @package Sf.Javascript:Browser
 *
 * @desc The class provide a Browser Compliance Check for JavaScript
 * @author unix-world.org
 * @license BSD
 * @file browser_check.js
 * @version 20250214
 * @class smartJ$TestBrowser
 * @static
 * @frozen
 *
 */
const smartJ$TestBrowser = new class{constructor(){ // STATIC CLASS
	const _N$ = 'smartJ$TestBrowser';

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

	const _Option$ = ((typeof(smartJ$Options) != 'undefined') && smartJ$Options && (typeof(smartJ$Options) === 'object') && (smartJ$Options.BrowserTest != undefined) && (typeof(smartJ$Options.BrowserTest) === 'object')) ? smartJ$Options.BrowserTest : null;

	//==

	/**
	 * If set to 'yes' or 'no' will disable the javascript detection for checkIsMobileDevice() which will return always TRUE if set to 'yes' and always FALSE if set to 'no'
	 * This can be used to detect mobile devices by the backend (PHP) and skip the detection from javascript side
	 *
	 * @default 'auto'
	 * @const {String} param_isMobile
	 * @set [before] smartJ$Options.BrowserTest.isMobile
	 * @get N/A
	 * @static
	 * @memberof smartJ$TestBrowser
	 */
	const param_isMobile = (_Option$ && (_Option$.isMobile === 'yes')) ? 'yes' : ((_Option$ && (_Option$.isMobile === 'no')) ? 'no' : 'auto');

	//==

	const _w$ = (typeof(window) != 'undefined') ? window : null;
	const _n$ = (typeof(navigator) != 'undefined') ? navigator : null;

	/**
	 * Detect if the Browser is on a mobile device.
	 * @hint It is a very basic but effective and quick detection
	 *
	 * @param 	{Boolean} 	skipFallbackOnscreenSize 		*Optional* Default is FALSE ; If set to TRUE will skip the fallback on screensize which may be innacurate
	 *
	 * @memberof smartJ$TestBrowser
	 * @method checkIsMobileDevice
	 * @static
	 *
	 * @returns {Boolean} will return TRUE if Browser seems to be a Mobile Devices, FALSE if not
	 */
	const checkIsMobileDevice = function(skipFallbackOnscreenSize=false) {
		//--
		if(param_isMobile === 'yes') {
			return true; // bool
		} else if(param_isMobile === 'no') {
			return false; // bool
		} //end if
		//--
		let isMobile = false;
		if(
			_w$ && // any can be null, but if have it, consider is mobile
			((_w$.ontouchstart !== undefined) || (_w$.orientation !== undefined))
		) {
			isMobile = true;
		} else if(
			_w$ &&
			_w$.screen &&
			((_w$.screen.width != undefined) && (_w$.screen.width > 0) && (_w$.screen.width < 768)) &&
			((_w$.screen.height != undefined) && (_w$.screen.height > 0) && (_w$.screen.height < 768))
		) {
			if(skipFallbackOnscreenSize !== true) {
				isMobile = true;
			} //end if
		} //end if
		//--
		return !! isMobile; // bool
		//--
	}; //END
	_C$.checkIsMobileDevice = checkIsMobileDevice; // export

	/**
	 * Detect if a Browser support Cookies or does not have the Cookies disabled or even may not support cookies.
	 * @hint If a browser show that does not supports Cookies may be a situation like user disabled cookies in the browser or is a really unusual or old browser
	 *
	 * @example
	 * if(!smartJ$TestBrowser.checkCookies()) {
	 * 		alert('NOTICE: Your browser does not support Cookies !');
	 * }
	 *
	 * @memberof smartJ$TestBrowser
	 * @method checkCookies
	 * @static
	 * @arrow
	 *
	 * @returns {Boolean} will return TRUE if Browser supports Cookies and cookies are enabled, FALSE if not or cookies are disabled
	 */
	const checkCookies = () => {
		//--
		if(_n$ && (_n$.cookieEnabled === true)) {
			return true;
		} //end if
		//--
		return false;
		//--
	}; //END
	_C$.checkCookies = checkCookies; // export

}}; //END CLASS

smartJ$TestBrowser.secureClass(); // implements class security

window.smartJ$TestBrowser = smartJ$TestBrowser; // global export

//==================================================================
//==================================================================

// #END
