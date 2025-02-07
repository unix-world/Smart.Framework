
// [LIB - Smart.Framework / JS / Smart Modal iFrame]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: jQuery, smartJ$Utils
// DEPENDS-OPTIONAL: smartJ$Browser (for scanner only)

//==================================================================
//==================================================================

//================== [ES6]

/**
 * CLASS :: Smart ModalBox (ES6)
 *
 * @package Sf.Javascript:Browser
 *
 * @requires		jQuery
 * @requires		smartJ$Utils
 * @requires		*smartJ$Browser (optional, for scanner only)
 *
 * @desc a Modal iFrame component for JavaScript / jQuery
 * @author unix-world.org
 * @license BSD
 * @file ifmodalbox.js
 * @version 20250205
 * @class smartJ$ModalBox
 * @fires iFrame: Show / Load / Unload / Hide
 * @listens getHandlerOnBeforeUnload() that can be set by setHandlerOnBeforeUnload(()=>{})
 * @static
 * @frozen
 *
 */
const smartJ$ModalBox = new class{constructor(){ // STATIC CLASS
	const _N$ = 'smartJ$ModalBox';
	const VER = 'r.20250205';

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

	const _Option$ = ((typeof(smartJ$Options) != 'undefined') && smartJ$Options && (typeof(smartJ$Options) === 'object') && (smartJ$Options.ModalBox != undefined) && (typeof(smartJ$Options.ModalBox) === 'object')) ? smartJ$Options.ModalBox : null;

	const _Utils$ = smartJ$Utils;
//	const _BwUtils$ = smartJ$Browser; // needed for scanner only ; because the smart box loads before it it throws referencing the class here in ES6 !

	//== privates

	//-- private settings, access only as readonly or use get/set methods
	let iFBoxStatus = '';			// hold the status: '' | 'visible'
	let iFBoxRefreshState = 0; 		// if=1, will refresh parent
	let iFBoxRefreshURL = ''; 		// ^=1 -> if != '' will redirect parent
	//--

	//-- private registry
	let iFBoxWidth = 200;			// current width, min is 200
	let iFBoxHeight = 100;			// current height, min is 100
	let iFBoxBeforeUnload = null; 	// null or method to execute before unload that can be set external
	//--

	//-- private const
	const iFBoxPrefix 		= 'smart__iFModalBox_';
	const iFBoxName 		= iFBoxPrefix + '_iFrame';
	const iFBoxBackground 	= iFBoxPrefix + '_Bg';
	const iFBoxDiv 			= iFBoxPrefix + '_Div';
	const iFBoxBtnClose 	= iFBoxPrefix + '_X';
	const iFBoxLoader 		= iFBoxPrefix + '_Ldr';
	const iFBoxBtnTTlClose 	= '[X]';
	//--

	//== setup: can be changed after loading the script

	/**
	 * Use Protection used to allow (when TRUE) the click on overlay to close the modal
	 * @default false
	 * @let {Boolean} param_UseProtection
	 * @set [before] smartJ$Options.ModalBox.UseProtection ; [after] can be changed by setting the 2nd param of the LoadURL() method to TRUE or FALSE, will persist
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	let param_UseProtection = (_Option$ && (!!_Option$.UseProtection)) ? 1 : 0; // 1 protect ; 0 not protect (eval as boolean) ; it is variable not constant !

	/**
	 * Loader Image used to display when loading ...
	 * @default 'lib/js/framework/img/loading.svg'
	 * @const {String} param_LoaderImg
	 * @set [before] smartJ$Options.ModalBox.LoaderImg
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_LoaderImg = (_Option$ && (typeof(_Option$.LoaderImg) == 'string') && _Option$.LoaderImg) ? _Utils$.stringTrim(_Option$.LoaderImg) : 'lib/js/framework/img/loading.svg';

	/**
	 * Loader Blank HTML Page used to clear and free memory before loading or after unloading ...
	 * @default 'lib/js/framework/loading.html'
	 * @const {String} param_LoaderBlank
	 * @set [before] smartJ$Options.ModalBox.LoaderBlank
	 * @get N/A
	 * @static
	 * @private
	 * @memberof smartJ$ModalBox
	 */
	const param_LoaderBlank = (_Option$ && (typeof(_Option$.LoaderBlank) == 'string') && _Option$.LoaderBlank) ? _Utils$.stringTrim(_Option$.LoaderBlank) : 'lib/js/framework/loading.html';

	/**
	 * Close Button Image
	 * @default 'lib/js/framework/img/close.svg'
	 * @const {String} param_CloseImg
	 * @set [before] smartJ$Options.ModalBox.CloseImg
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_CloseImg = (_Option$ && (typeof(_Option$.CloseImg) == 'string') && _Option$.CloseImg) ? _Utils$.stringTrim(_Option$.CloseImg) : 'lib/js/framework/img/close.svg';

	/**
	 * Close Button Horizontal Align Mode :: 'left' or 'right'
	 * @default 'right'
	 * @const {String} param_CloseAlign
	 * @set [before] smartJ$Options.ModalBox.CloseAlign
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_CloseAlign = (_Option$ && (_Option$.CloseAlign === 'left')) ? 'left' : 'right';

	/**
	 * Close Button Customization: alternate html code for the modal close button ... can be used to completely replace the above param_CloseImg
	 * @default ''
	 * @const {String} param_CloseBtnAltHtml
	 * @set [before] smartJ$Options.ModalBox.CloseBtnAltHtml
	 * @get N/A
	 * @static
	 * @private
	 * @memberof smartJ$ModalBox
	 */
	const param_CloseBtnAltHtml = (_Option$ && (typeof(_Option$.CloseBtnAltHtml) == 'string') && _Option$.CloseBtnAltHtml) ? _Utils$.stringTrim(_Option$.CloseBtnAltHtml) : '';

	/**
	 * Close Box Vertical Align Mode :: 'top' or 'middle' / 'center'
	 * @default 'top'
	 * @const {String} param_vAlign
	 * @set [before] smartJ$Options.ModalBox.vAlign
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_vAlign = (_Option$ && (typeof(_Option$.vAlign) == 'string') && ((_Option$.vAlign == 'middle') || (_Option$.vAlign == 'center'))) ? String(_Option$.vAlign) : 'top';

	/**
	 * Modal iFrame Open Delay (500 ... 1000)
	 * @default 850
	 * @const {Integer+} param_DelayOpen
	 * @set [before] smartJ$Options.ModalBox.DelayOpen
	 * @get N/A
	 * @static
	 * @private
	 * @memberof smartJ$ModalBox
	 */
	const param_DelayOpen = (_Option$ && (typeof(_Option$.DelayOpen) == 'number') && _Option$.DelayOpen && _Utils$.isFiniteNumber(_Option$.DelayOpen)) ? _Utils$.format_number_int(_Option$.DelayOpen, false) : 850;

	/**
	 * Modal iFrame Close Delay (250 ... 750)
	 * @default 500
	 * @const {Integer+} param_DelayClose
	 * @set [before] smartJ$Options.ModalBox.DelayClose
	 * @get N/A
	 * @static
	 * @private
	 * @memberof smartJ$ModalBox
	 */
	const param_DelayClose = (_Option$ && (typeof(_Option$.DelayClose) == 'number') && _Option$.DelayClose && _Utils$.isFiniteNumber(_Option$.DelayClose)) ? _Utils$.format_number_int(_Option$.DelayClose, false) : 500;

	/**
	 * Overlay Customization - Background Color
	 * Allowed Values: hexa color between '#000000' .. '#FFFFFF'
	 * @default '#222222'
	 * @const {String} param_CssOverlayBgColor
	 * @set [before] smartJ$Options.ModalBox.CssOverlayBgColor
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_CssOverlayBgColor = (_Option$ && (typeof(_Option$.CssOverlayBgColor) == 'string') && _Option$.CssOverlayBgColor && _Utils$.isHexColor(_Option$.CssOverlayBgColor)) ? _Utils$.stringTrim(_Option$.CssOverlayBgColor) : '#222222';
// {{{SYNC-OVERLAY}}}
	/**
	 * Overlay Customization - Opacity
	 * Allowed Values: between 0 and 1
	 * @default 0.8
	 * @const {Float} param_CssOverlayOpacity
	 * @set [before] smartJ$Options.ModalBox.CssOverlayOpacity
	 * @get N/A
	 * @static
	 * @memberof smartJ$ModalBox
	 */
	const param_CssOverlayOpacity = (_Option$ && (typeof(_Option$.CssOverlayOpacity) == 'number') && _Utils$.isFiniteNumber(_Option$.CssOverlayOpacity) && (_Utils$.format_number_float(_Option$.CssOverlayOpacity, false) >= 0) && (_Utils$.format_number_float(_Option$.CssOverlayOpacity, false) <= 1)) ? _Utils$.format_number_dec(_Option$.CssOverlayOpacity, 2, false, true) : 0.4;

	//==

	/**
	 * Get the Name of Smart Modal Box
	 *
	 * @public
	 *
	 * @memberof smartJ$ModalBox
	 * @method getName
	 * @static
	 * @arrow
	 *
	 * @return {String} The name of the Modal Box
	 */
	const getName = () => {
		//--
		return String(iFBoxName);
		//--
	}; //END
	_C$.getName = getName; // export

	/**
	 * Get the Status of Smart Modal Box
	 *
	 * @public
	 *
	 * @memberof smartJ$ModalBox
	 * @method getStatus
	 * @static
	 * @arrow
	 *
	 * @return {String} The status of the Modal Box as: 'visible' or ''
	 */
	const getStatus = () => {
		//--
		return String(iFBoxStatus);
		//--
	}; //END
	_C$.getStatus = getStatus; // export

	/**
	 * Get the Version of Smart Modal Box
	 *
	 * @private
	 *
	 * @memberof smartJ$ModalBox
	 * @method getVersion
	 * @static
	 * @arrow
	 *
	 * @return {String} The version of the Modal Box
	 */
	const getVersion = () => {
		//--
		return String(VER);
		//--
	}; //END
	_C$.getVersion = getVersion; // export, hidden

	/**
	 * Set/Unset the Refresh Parent State/URL for Smart Modal Box
	 *
	 * @memberof smartJ$ModalBox
	 * @method setRefreshParent
	 * @static
	 * @arrow
	 *
	 * @param {Boolean} state :: TRUE will SET / FALSE will UNSET
	 * @param {String} yURL the Refresh URL that will execute on destruct of the Modal Box
	 */
	const setRefreshParent = (state, yURL) => {
		//--
		yURL = _Utils$.stringPureVal(yURL, true); // cast to string, trim
		//--
		if(!!state) {
			iFBoxRefreshState = 1;
			iFBoxRefreshURL = String(yURL);
		} else {
			iFBoxRefreshState = 0;
			iFBoxRefreshURL = '';
		} //end if else
		//--
	}; //END
	_C$.setRefreshParent = setRefreshParent; // export

	/**
	 * Set Per-Instance Before Unload custom Handler: ()=>{} // return true or false; }
	 *
	 * @memberof smartJ$ModalBox
	 * @method setHandlerOnBeforeUnload
	 * @static
	 * @arrow
	 *
	 * @param {Function} fx :: if type FUNCTION, will set the iFBoxBeforeUnload handler else will log an error
	 * @return {Boolean} If fx is function and set, will return TRUE else FALSE
	 */
	const setHandlerOnBeforeUnload = (fx) => {
		//--
		if(typeof(fx) === 'function') {
			iFBoxBeforeUnload = fx;
			return true;
		} //end if
		//--
		_p$.error(_N$, 'ERR: setHandlerOnBeforeUnload', 'fx is not a function');
		//--
		return false;
	}; // END
	_C$.setHandlerOnBeforeUnload = setHandlerOnBeforeUnload; // export

	/**
	 * Make the Smart Modal Box to Load a new URL ; after load will show
	 *
	 * @memberof smartJ$ModalBox
	 * @method LoadURL
	 * @static
	 *
	 * @param {String} yURL :: the URL to be loaded ; must differ from the URL loaded in parent !
	 * @param {Boolean} yProtect :: default is NULL ; if TRUE will protect closing Modal Box by Escape or click outside and can be closed only by close button
	 * @param {Integer} windowWidth :: the width of the Modal Box
	 * @param {Integer} windowHeight :: the height of the Modal Box
	 */
	const LoadURL = function(yURL, yProtect=null, windowWidth=0, windowHeight=0) {
		//-- checks
		yURL = _Utils$.stringPureVal(yURL, true); // cast to string, trim
		//-- register
		iFBoxStatus = 'visible';
		if(yProtect !== null) {
			param_UseProtection = yProtect ? 1 : 0;
		} //end if
		iFBoxWidth = _Utils$.format_number_int(parseInt(windowWidth), false); // do not adjust value here, can have px as suffix
		iFBoxHeight = _Utils$.format_number_int(parseInt(windowHeight), false); // do not adjust value here, can have px as suffix
		//-- disable parent scrolling
		$('body').css({
			'overflow': 'hidden' // need to be hidden
		});
		//-- show loading
		$('#' + iFBoxLoader).empty();
		if(param_LoaderImg) {
			$('#' + iFBoxLoader).html('<br><br><img src="' + _Utils$.escape_html(param_LoaderImg) + '" alt="..." title="...">');
		} //end if
		//-- positioning
		executePositioning(param_UseProtection, iFBoxWidth, iFBoxHeight);
		//-- force no-cache and fix a bug if same URL as parent
		const UrlTime = new Date().getTime();
		if(yURL.indexOf('?') != -1) {
			yURL += '&';
		} else {
			yURL += '?';
		} //end if else
		yURL += String(iFBoxName + '=' + _Utils$.escape_url(UrlTime));
		//--
		$('#' + iFBoxName).show().css({
			'width': '100%',
			'height': '100%',
			'visibility': 'hidden' // BugFix: we use opacity to hide/show iFrame because some bug in browsers if the iframe is hidden while loading
		}).attr('src', String(yURL));
		//--
		let the_closebtn;
		if(param_CloseBtnAltHtml === '') {
			the_closebtn = '<img id="ifrm-close" src="' + _Utils$.escape_html(param_CloseImg) + '" alt="' + _Utils$.escape_html(iFBoxBtnTTlClose) + '" title="' + _Utils$.escape_html(iFBoxBtnTTlClose) + '">';
		} else {
			the_closebtn = String(param_CloseBtnAltHtml);
		} //end if else
		//--
		let the_align_left = 'auto';
		let the_align_right = 'auto';
		if(param_CloseAlign === 'left') {
			the_align_left = '-20px'; // left
		} else { // right
			the_align_right = '-20px'; // right
		} //end if else
		//--
		$('#' + iFBoxBtnClose).show().css({
			'position': 'absolute',
			'z-index': 2111111099, //9999999,
			'cursor': 'pointer',
			'top': '-12px',
			'left': the_align_left,
			'right': the_align_right,
			'min-width': '32px',
			'max-width': '64px',
			'min-height': '32px',
			'max-height': '64px',
			'visibility': 'hidden'
		}).empty().html(the_closebtn).click(() => {
			UnloadURL();
		});
		//--
		if(!yProtect) {
			$('#' + iFBoxBackground).click(() => {
				UnloadURL();
			});
		} else {
			$('#' + iFBoxBackground).unbind('click');
		} //end if
		//-- show delayed
		let openDelay = _Utils$.format_number_int(param_DelayOpen, false);
		if(openDelay < 500) {
			openDelay = 500;
		} //end if
		if(openDelay > 1000) {
			openDelay = 1000;
		} //end if
		setTimeout(() => { makeVisible(); }, openDelay); // delay a bit to avoid show a blank area
		//--
		return false;
		//--
	}; //END
	_C$.LoadURL = LoadURL; // export, hidden

	/**
	 * Make the Smart Modal Box to Unload the URL ; after unload will hide
	 *
	 * @memberof smartJ$ModalBox
	 * @method UnloadURL
	 * @static
	 */
	const UnloadURL = function() {
		//--
		let test_unload = true;
		try {
			test_unload = !! getHandlerOnBeforeUnload(); // boolean
		} catch(err){
			_p$.error(_N$, 'ERR: UnloadURL', err);
			test_unload = true;
		} //end try catch
		if(!test_unload) {
			return false; // it is like onbeforeunload
		} //end if
		//--
		executeUnload();
		//--
		let closeDelay = _Utils$.format_number_int(param_DelayClose, false);
		if(closeDelay < 250) {
			closeDelay = 250;
		} //end if
		if(closeDelay > 750) {
			closeDelay = 750;
		} //end if
		//--
		setTimeout(() => { initialize(); }, closeDelay); // delayed close
		//--
		return false;
		//--
	}; //END
	_C$.UnloadURL = UnloadURL; // export, hidden

	//================================== # [PRIVATES]

	const initialize = function() {
		//--
		//window.onresize = () => { // clear window resize
		//};
		//--
		$('#' + iFBoxDiv).css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//--
		$('#' + iFBoxBackground).css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//--
		if(iFBoxRefreshState) { // {{{SYNC-MODAL-Refresh-Parent-By-EXEC}}}
			//--
			const url = _Utils$.stringTrim(iFBoxRefreshURL);
			//--
			if(url == '') {
				self.location = self.location; // FIX from above line: avoid reload to resend POST vars !!
			} else {
				self.location = String(url);
			} //end if else
			//--
			iFBoxRefreshState = 0;
			iFBoxRefreshURL = '';
			//--
		} //end if
		//--
		return false;
		//--
	}; //END
	// no export

	const makeVisible = () => {
		//--
		$('#' + iFBoxBtnClose).css({
			'visibility': 'visible'
		});
		//--
		$('#' + iFBoxName).css({
			'background-color': '#FFFFFF',
			'visibility': 'visible' // BugFix: we use opacity to hide/show iFrame because some bug in browsers if the iframe is hidden while loading
		});
		$('#' + iFBoxLoader).empty().html('');
		//--
		return false;
		//--
	}; //END
	// no export

	const getHandlerOnBeforeUnload = () => {
		//--
		if(typeof(iFBoxBeforeUnload) === 'function') {
			return !! iFBoxBeforeUnload(); // boolean
		} //end if
		//--
		return true;
		//--
	}; //END
	// no export

	const getWindowWidth = (windowWidth) => {
		//--
		windowWidth = _Utils$.format_number_int(parseInt(windowWidth), false); // can have px as suffix
		if(windowWidth <= 0) {
			windowWidth = _Utils$.format_number_int(parseInt($(window).width()) - 40, false); // $(window).width() have px as suffix
		} //end if
		if(windowWidth < 200) {
			windowWidth = 200;
		} //end if
		//--
		return windowWidth;
		//--
	}; //END
	// no export

	const getWindowHeight = (windowHeight) => {
		//--
		windowHeight = _Utils$.format_number_int(parseInt(windowHeight), false); // can have px as suffix
		if(windowHeight <= 0) {
			windowHeight = _Utils$.format_number_int(parseInt($(window).height()) - 20, false); // $(window).height() have px as suffix
		} //end if
		if(windowHeight < 100) {
			windowHeight = 100;
		} //end if
		//--
		return windowHeight;
		//--
	}; //END
	// no export

	const executeUnload = function() {
		//--
		$('#' + iFBoxBackground).unbind('click');
		$('#' + iFBoxBtnClose).unbind('click');
		$('#' + iFBoxLoader).empty().html('');
		//--
		let the_align_left = 'auto';
		let the_align_right = 'auto';
		if(param_CloseAlign === 'left') {
			the_align_left = '0px'; // left
		} else { // right
			the_align_right = '0px'; // right
		} //end if else
		//--
		$('#' + iFBoxBtnClose).css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': the_align_left,
			'right': the_align_right,
			'top': '0px',
		}).empty().html('').hide();
		//--
		$('#' + iFBoxName).css({
			'width': '1px',
			'height': '1px'
		});
		if(param_LoaderBlank) {
			$('#' + iFBoxName).attr('src', _Utils$.escape_html(param_LoaderBlank)); // force unload
		} //end if
		$('#' + iFBoxName).attr('src', '').hide();
		//--
		$('#' + iFBoxDiv).css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//-- restore parent scrolling
		$('body').css({
			'overflow': 'auto' // need to be 'auto' instead 'visible' to work with IE
		});
		//--
		iFBoxStatus = '';
		//--
		return false;
		//--
	}; //END
	// no export

	const calculatePosition = function(windowWidth, windowHeight) {
		//--
		let the_h_align = _Utils$.format_number_int(parseInt($(window).scrollLeft()) + ((parseInt($(window).width()) - windowWidth) / 2)) + 'px';
		let the_v_align = _Utils$.format_number_int(parseInt($(window).scrollTop()) + 10) + 'px';
		if((param_vAlign === 'center') || (param_vAlign === 'middle')) {
			the_v_align = _Utils$.format_number_int((parseInt($(window).scrollTop()) + ((parseInt($(window).height()) - windowHeight) / 2))) + 'px';
		} //end if else
		//--
		$('#' + iFBoxDiv).css({
			'position': 'absolute',
			'z-index': 2111111098, //9999998,
			'text-align': 'center',
			'left': the_h_align,
			'top': the_v_align,
			'width': windowWidth + 'px',
			'height': windowHeight + 'px'
		}).show();
		//--
	}; //END
	// no export

	const executePositioning = function(yProtect, windowWidth, windowHeight) {
		//--
		let the_wWidth = getWindowWidth(windowWidth);
		let the_wHeight = getWindowHeight(windowHeight);
		//--
		const the_wRealWidth = getWindowWidth(0);
		if(the_wRealWidth < windowWidth) {
			the_wWidth = the_wRealWidth;
		} //end if
		const the_wRealHeight = getWindowHeight(0);
		if(the_wRealHeight < windowHeight) {
			the_wHeight = the_wRealHeight;
		} //end if
		//--
		let the_style_cursor = 'auto';
		if(yProtect != 1) {
			the_style_cursor = 'pointer';
		} //end if
		$('#' + iFBoxBackground).css({
			'position': 'fixed',
			'z-index': 2111111097, //9999997,
			'cursor': the_style_cursor,
			'text-align': 'center',
			'left': '0px',
			'top': '0px',
			'width': '100%',
			'height': '100%',
		}).show();
		//--
		calculatePosition(the_wWidth, the_wHeight);
		//--
		return false;
		//--
	}; //END
	// no export

	//================================== # [EXTERNAL EVENT HANDLERS AND DOM REGISTERS]

	$(() => {
		//-- {{{SYNC-OVERLAY}}}
		$('body').append('<!-- SmartJS.Modal.Loader :: Start --><div id="' + _Utils$.escape_html(iFBoxBackground) + '" data-info-smartframework="SmartFramework.Js.ModalBox: ' + _Utils$.escape_html(VER) + '" style="position:absolute; top:0px; left:0px; width:1px; height:1px; background:' + _Utils$.escape_html(_Utils$.hex2rgba(param_CssOverlayBgColor, param_CssOverlayOpacity)) + '; backdrop-filter: blur(4px);"></div><div id="' + _Utils$.escape_html(iFBoxDiv) + '" style="position:absolute; top:0px; left:0px; width:1px; height:1px;"><center><div id="' + _Utils$.escape_html(iFBoxLoader) + '"></div></center><div id="' + _Utils$.escape_html(iFBoxBtnClose) + '" title="[X]"></div><iframe name="' + _Utils$.escape_html(iFBoxName) + '" id="' + _Utils$.escape_html(iFBoxName) + '" width="1" height="1" scrolling="auto" src="" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0"></iframe></div><!-- END: SmartJS.Modal.Loader -->');
		//--
		initialize();
		//--
		$(window).on('resize scroll', (ev) => {
			if(getStatus() === 'visible') {
				//_p$.log(_N$, 'Resizing the ModalBox by Window event: Resize or Scroll');
				executePositioning(param_UseProtection, iFBoxWidth, iFBoxHeight);
			} //end if
		});
		//--
	}); // end on document ready

	//==================================

}}; //END CLASS

smartJ$ModalBox.secureClass(); // implements class security

window.smartJ$ModalBox = smartJ$ModalBox; // global export

//==================================================================
//==================================================================

// #END
