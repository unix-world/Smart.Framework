
// JQuery Compatibility: Browser and Trim
// v.20200629
// LICENSE: BSD

//===== INFO: $.browser has been deprecated and removed since jQuery 1.9
// provide: $.browser
// source: https://github.com/jquery/jquery-migrate/blob/master/src/core.js
// copyright: MIT License / Based on jQuery migration plugin
// modified by: unixman
//=====

//--
jQuery.uaMatch = function(ua) {
	//--
	ua = ua.toLowerCase();
	//--
	var match = / (firefox)\//.exec(ua) || / (fxios)\//.exec(ua) || /(msie) ([\w.]+)/.exec(ua) || / (trident)\//.exec(ua) || / (edge)\//.exec(ua) || / (opr)\//.exec(ua) || / (opios)\//.exec(ua) || /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) || / (crios)\//.exec(ua) || /(chromium)[ \/]([\w.]+)/.exec(ua) || /(chrome)[ \/]([\w.]+)/.exec(ua) || /(webkit)[ \/]([\w.]+)/.exec(ua) || ua.indexOf('compatible') < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua) || [];
	//--
	return {
		browser: String(match[1] || ''),
		version: String(match[2] || '0')
	};
	//--
} //END FUNCTION
//--

//--
jQuery.SmartBrowserGetVersion = function() {
	//--
	var browser = {};
	//--
	var matched = jQuery.uaMatch(navigator.userAgent);
	//--
	if(matched.browser) {
		browser[matched.browser] = true;
		browser.version = matched.version;
	} //end if
	//-- fixes
	if((browser.trident) || (browser.edge)) {
		browser.msie = true;
	} //end if
	if((browser.opr) || (browser.opios)) {
		browser.opera = true;
	} //end if
	if((browser.crios) || (browser.chromium)) {
		browser.chrome = true;
	} //end if
	if(browser.fxios) {
		browser.firefox = true;
	} //end if
	if(browser.webkit) {
		browser.safari = true;
	} //end if else
	if((browser.chrome) || (browser.opera)) {
		browser.webkit = true;
	} //end if
	if(browser.firefox) {
		browser.mozilla = true;
	} //end if
	//--
	jQuery.browser = browser;
	//--
} //END FUNCTION
//--

//--
//if(!jQuery.browser) {
jQuery.SmartBrowserGetVersion();
//} //end if
//--

//--
//if(!jQuery.trim) {
//	jQuery.trim = function(s) {
//		return s.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
//	} //END FUNCTION
//} //end if
//--

//-- jQuery TapHold for Mobiles (Double-Click Emulation): https://gist.github.com/attenzione/7098476
(function($){
	$.event.special.doubletap = {
		bindType: 'touchend',
		delegateType: 'touchend',
		handle: function(event) {
			var handleObj = event.handleObj,
				targetData  = jQuery.data(event.target),
				now         = new Date().getTime(),
				delta       = targetData.lastTouch ? now - targetData.lastTouch : 0,
				delay       = delay == null ? 300 : delay;
			if(delta < delay && delta > 30) {
				targetData.lastTouch = null;
				event.type = handleObj.origType;
				['clientX', 'clientY', 'pageX', 'pageY'].forEach(function(property) {
					event[property] = event.originalEvent.changedTouches[0][property];
				});
				handleObj.handler.apply(this, arguments); // let jQuery handle the triggering of "doubletap" event handlers
			} else {
				targetData.lastTouch = now;
			} //end if else
		} //end function
	};
})(jQuery);
//--

// #END FILE
