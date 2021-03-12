
// [LIB - Smart.Framework / JS / Smart Modal iFrame]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

// DEPENDS: jQuery, SmartJS_CoreUtils
// DEPENDS-OPTIONAL: SmartJS_BrowserUtils (for the scanner)

//==================================================================
//==================================================================

//================== [NO:evcode]

/**
 * CLASS :: Smart Modal Box
 *
 * @package Sf.Javascript:Browser
 *
 * @requires		jQuery
 * @requires		SmartJS_CoreUtils
 * @requires		*SmartJS_BrowserUtils
 *
 * @desc a Modal iFrame component for JavaScript / jQuery
 * @author unix-world.org
 * @license BSD
 * @file ifmodalbox.js
 * @version 20210312
 * @class SmartJS_ModalBox
 * @fires iFrame: Show / Load / Unload / Hide
 * @listens on_Before_Unload()
 * @static
 *
 */
var SmartJS_ModalBox = new function() { // START CLASS

	// :: static

	var _class = this; // self referencing

	var version = 'r.20200121';

	//== setup: can be changed after loading this script

	/**
	 * Loader Image used to display when loading ...
	 * @default 'lib/js/framework/img/loading.svg'
	 * @var {String} param_LoaderImg
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_LoaderImg = 'lib/js/framework/img/loading.svg';
	/**
	 * Loader Blank HTML Page used to clear and free memory before loading or after unloading ...
	 * @default 'lib/js/framework/blank.html'
	 * @var {String} param_LoaderBlank
	 * @static
	 * @private
	 * @memberof SmartJS_ModalBox
	 */
	this.param_LoaderBlank = 'lib/js/framework/blank.html';

	/**
	 * Close Button Image
	 * @default 'lib/js/framework/img/close.svg'
	 * @var {String} param_CloseImg
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_CloseImg = 'lib/js/framework/img/close.svg';
	/**
	 * Close Button Horizontal Align Mode :: 'left' or 'right'
	 * @default 'right'
	 * @var {String} param_CloseAlign
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_CloseAlign = 'right';
	/**
	 * Close Button Vertical Align Mode :: 'top' or 'middle' or 'center'
	 * @default 'top'
	 * @var {String} param_vAlign
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_vAlign = 'top';
	/**
	 * Close Button Customization: alternate html code for the modal close button ... can be used to completely replace the above param_CloseImg
	 * @default ''
	 * @var {String} param_CloseBtnAltHtml
	 * @static
	 * @private
	 * @memberof SmartJS_ModalBox
	 */
	this.param_CloseBtnAltHtml = ''; 								//

	/**
	 * Modal iFrame Open Delay (500 ... 1000)
	 * @default 850
	 * @var {Integer+} param_DelayOpen
	 * @static
	 * @private
	 * @memberof SmartJS_ModalBox
	 */
	this.param_DelayOpen = 850;
	/**
	 * Modal iFrame Close Delay (250 ... 750)
	 * @default 500
	 * @var {Integer+} param_DelayClose
	 * @static
	 * @private
	 * @memberof SmartJS_ModalBox
	 */
	this.param_DelayClose = 500;

	/**
	 * Overlay Customization - Background Color
	 * Allowed Values: hexa color between #000000 - #FFFFFF
	 * @default '#333333'
	 * @var {String} param_CssOverlayBgColor
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_CssOverlayBgColor = '#333333';

	/**
	 * Overlay Customization - Opacity
	 * Allowed Values: between 0.1 and 1
	 * @default 0.85
	 * @var {Decimal} param_CssOverlayOpacity
	 * @static
	 * @memberof SmartJS_ModalBox
	 */
	this.param_CssOverlayOpacity = '0.85'; 							// modal overlay bg opacity

	//==

	//-- private settings, access only as readonly or use get/set functions
	this.iFBoxStatus = '';			// hold the status: '' | 'visible'
	this.iFBoxRefreshState = 0; 	// if=1, will refresh parent
	this.iFBoxRefreshURL = ''; 		// ^=1 -> if != '' will redirect parent
	//--

	//-- private registry
	this.iFBoxProtect = 0;			// 1 protect ; 0 not protect
	this.iFBoxWidth = 320;			// current width
	this.iFBoxHeight = 200;			// current height
	//--


	/**
	 * Get the Name of Smart Modal Box
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method getName
	 * @static
	 *
	 * @return {String} The name of the Modal Box
	 */
	this.getName = function() {
		//--
		return 'smart__iFModalBox__iFrame';
		//--
	} //END FUNCTION


	/**
	 * Get the Status of Smart Modal Box
	 *
	 * @public
	 *
	 * @memberof SmartJS_ModalBox
	 * @method getStatus
	 * @static
	 *
	 * @return {String} The status of the Modal Box as: 'visible' or ''
	 */
	this.getStatus = function() {
		//--
		return String(_class.iFBoxStatus);
		//--
	} //END FUNCTION


	/**
	 * Set/Unset the Refresh Parent State/URL for Smart Modal Box
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method setRefreshParent
	 * @static
	 *
	 * @param {Boolean} state :: TRUE will SET / FALSE will UNSET
	 * @param {String} yURL the Refresh URL that will execute on destruct of this Modal Box
	 */
	this.setRefreshParent = function(state, yURL) {
		//--
		if((typeof yURL == 'undefined') || (yURL == 'undefined') || (yURL == null)) {
			yURL = '';
		} //end if
		//--
		if(state) {
			_class.iFBoxRefreshState = 1;
			_class.iFBoxRefreshURL = String(yURL);
		} else {
			_class.iFBoxRefreshState = 0;
			_class.iFBoxRefreshURL = '';
		} //end if else
		//--
	} //END FUNCTION


	/**
	 * Set the Refresh Parent State/URL for Smart Modal Box
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method Refresh_SET_iFrame_Parent
	 * @static
	 *
	 * @param {String} yURL the Refresh URL that will execute on destruct of this Modal Box
	 */
	this.Refresh_SET_iFrame_Parent = function(yURL) {
		//--
		if((typeof yURL == 'undefined') || (yURL == 'undefined') || (yURL == null)) {
			yURL = '';
		} //end if
		//--
		if(SmartJS_BrowserUtils.param_PageUnloadConfirm === true) {
			console.log('Parent Refresh skip. Parent have PageAway Confirmation (2)');
			return;
		} //end if
		//--
		_class.iFBoxRefreshState = 1;
		_class.iFBoxRefreshURL = String(yURL);
		//--
	} //END FUNCTION


	/**
	 * Force the Smart Modal Box to become visible
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method make_Visible
	 * @static
	 */
	this.make_Visible = function() {
		//--
		jQuery('#smart__iFModalBox__X').css({
			'visibility': 'visible'
		});
		//--
		jQuery('#smart__iFModalBox__iFrame').css({
			'background-color': '#FFFFFF',
			'visibility': 'visible' // BugFix: we use opacity to hide/show iFrame because some bug in browsers if the iframe is hidden while loading
		});
		jQuery('#smart__iFModalBox__Ldr').empty().html('');
		//--
		return false;
		//--
	} //END FUNCTION


	/**
	 * Make the Smart Modal Box to Load a new URL ; after load will show
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method go_Load
	 * @static
	 *
	 * @param {String} yURL :: the URL to be loaded ; must differ from the URL loaded in parent !
	 * @param {Boolean} yProtect :: if TRUE will protect closing Modal Box by Escape or click outside and can be closed only by close button
	 * @param {Integer} windowWidth :: the width of the Modal Box
	 * @param {Integer} windowHeight :: the height of the Modal Box
	 */
	this.go_Load = function(yURL, yProtect, windowWidth, windowHeight) {
		//--
		do_load(yURL, yProtect, windowWidth, windowHeight);
		//--
		return false;
		//--
	} //END FUNCTION


	/**
	 * Make the Smart Modal Box to Unload the URL ; after unload will hide
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method go_UnLoad
	 * @static
	 */
	this.go_UnLoad = function() {
		//--
		var test_unload = true;
		try {
			test_unload = _class.on_Before_Unload();
		} catch(e) {}
		if(!test_unload) {
			return false; // this is like onbeforeunload
		} //end if
		//--
		do_unload();
		//--
		var closeDelay = parseInt(SmartJS_ModalBox.param_DelayClose);
		if(!SmartJS_CoreUtils.isFiniteNumber(closeDelay)) {
			closeDelay = 500; // default
		} //end if
		if(closeDelay < 250) {
			closeDelay = 250;
		} //end if
		if(closeDelay > 750) {
			closeDelay = 750;
		} //end if
		//--
		setTimeout(function(){ SmartJS_ModalBox.fx_Unload(); }, closeDelay); // delayed close
		//--
		return false;
		//--
	} //END FUNCTION


	/**
	 * Force the Smart Modal Box to Unload the URL and hide
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method fx_Unload
	 * @static
	 */
	this.fx_Unload = function() {
		//--
		fx_out();
		//--
		return false;
		//--
	} //END FUNCTION


	//================================== # [PRIVATES]


	var RefreshEXEC_Self = function() {
		//--
		if(_class.iFBoxRefreshState) {
			//--
			if((typeof _class.iFBoxRefreshURL == 'undefined') || (_class.iFBoxRefreshURL === undefined) || (_class.iFBoxRefreshURL == '')) {
				//--
				//self . location . reload(false); // false is to reload from cache
				self.location = self.location; // FIX: avoid reload to resend POST vars !!
				//--
			} else {
				//--
				self.location = String(_class.iFBoxRefreshURL);
				//--
			} //end if else
			//--
			_class.iFBoxRefreshState = 0;
			_class.iFBoxRefreshURL = '';
			//--
		} //end if
		//--
	} //END FUNCTION


	var get_Window_Width = function(windowWidth) {
		//--
		windowWidth = parseInt(windowWidth);
		//--
		if((windowWidth <= 0) || !SmartJS_CoreUtils.isFiniteNumber(windowWidth)) {
			//--
			windowWidth = parseInt(parseInt(jQuery(window).width()) - 40);
			//--
			if(!SmartJS_CoreUtils.isFiniteNumber(windowWidth)) {
				windowWidth = 920; // just in case
			} //end if
			//--
		} //end if
		//--
		if(windowWidth < 200) {
			windowWidth = 200;
		} //end if
		//--
		return windowWidth;
		//--
	} //END FUNCTION


	var get_Window_Height = function(windowHeight) {
		//--
		windowHeight = parseInt(windowHeight);
		//--
		if((windowHeight <= 0) || !SmartJS_CoreUtils.isFiniteNumber(windowHeight)) {
			//--
			windowHeight = parseInt(parseInt(jQuery(window).height()) - 20);
			//--
			if(!SmartJS_CoreUtils.isFiniteNumber(windowHeight)) {
				windowHeight = 700; // just in case
			} //end if
			//--
		} //end if
		//--
		if(windowHeight < 100) {
			windowHeight = 100;
		} //end if
		//--
		return windowHeight;
		//--
	} //END FUNCTION


	var do_load = function(yURL, yProtect, windowWidth, windowHeight) {
		//-- register
		_class.iFBoxStatus = 'visible';
		_class.iFBoxProtect = yProtect ? 1 : 0;
		_class.iFBoxWidth = windowWidth;
		_class.iFBoxHeight = windowHeight;
		//-- disable parent scrolling
		jQuery('body').css({
			'overflow': 'hidden' // need to be hidden
		});
		//-- show loading
		jQuery('#smart__iFModalBox__Ldr').empty();
		if(SmartJS_ModalBox.param_LoaderImg) {
			jQuery('#smart__iFModalBox__Ldr').html('<br><br><img src="' + SmartJS_CoreUtils.escape_html(SmartJS_ModalBox.param_LoaderImg) + '" alt="..." title="...">');
		} //end if
		//-- init display
		fx_in(_class.iFBoxProtect, _class.iFBoxWidth, _class.iFBoxHeight);
		//-- force no-cache and fix a bug if same URL as parent
		var UrlTime = new Date().getTime();
		if(yURL.indexOf('?') != -1) {
			yURL += '&';
		} else {
			yURL += '?';
		} //end if else
		yURL += 'smart__iFModalBox__iFrame=' + encodeURIComponent(UrlTime);
		//--
		jQuery('#smart__iFModalBox__iFrame').show().css({
			'width': '100%',
			'height': '100%',
			'visibility': 'hidden' // BugFix: we use opacity to hide/show iFrame because some bug in browsers if the iframe is hidden while loading
		}).attr('src', String(yURL));
		//--
		var the_closebtn = '';
		if(SmartJS_ModalBox.param_CloseBtnAltHtml === '') {
			the_closebtn = '<img id="ifrm-close" src="' + SmartJS_CoreUtils.escape_html(SmartJS_ModalBox.param_CloseImg) + '" alt="[X]" title="[X]">';
		} else {
			the_closebtn = String(SmartJS_ModalBox.param_CloseBtnAltHtml);
		} //end if else
		//--
		var the_align_left = 'auto';
		var the_align_right = 'auto';
		if(SmartJS_ModalBox.param_CloseAlign == 'left') {
			the_align_left = '-20px'; // left
		} else { // right
			the_align_right = '-20px'; // right
		} //end if else
		//--
		jQuery('#smart__iFModalBox__X').show().css({
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
		}).empty().html(the_closebtn).click(function(){
			_class.go_UnLoad();
		});
		//--
		if(yProtect != 1) {
			jQuery('#smart__iFModalBox__Bg').click(function(){
				_class.go_UnLoad();
			});
		} else {
			jQuery('#smart__iFModalBox__Bg').unbind('click');
		} //end if
		//-- show delayed
		var openDelay = parseInt(SmartJS_ModalBox.param_DelayOpen);
		if(!SmartJS_CoreUtils.isFiniteNumber(openDelay)) {
			openDelay = 750; // default
		} //end if
		if(openDelay < 500) {
			openDelay = 500;
		} //end if
		if(openDelay > 1000) {
			openDelay = 1000;
		} //end if
		setTimeout(function(){ SmartJS_ModalBox.make_Visible(); }, openDelay); // delay a bit to avoid show a blank area
		//--
		return false;
		//--
	} //END FUNCTION


	var do_unload = function() {
		//--
		jQuery('#smart__iFModalBox__Bg').unbind('click');
		jQuery('#smart__iFModalBox__X').unbind('click');
		jQuery('#smart__iFModalBox__Ldr').empty().html('');
		//--
		var the_align_left = 'auto';
		var the_align_right = 'auto';
		if(SmartJS_ModalBox.param_CloseAlign == 'left') {
			the_align_left = '0px'; // left
		} else { // right
			the_align_right = '0px'; // right
		} //end if else
		//--
		jQuery('#smart__iFModalBox__X').css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': the_align_left,
			'right': the_align_right,
			'top': '0px',
		}).empty().html('').hide();
		//--
		jQuery('#smart__iFModalBox__iFrame').css({
			'width': '1px',
			'height': '1px'
		});
		if(SmartJS_ModalBox.param_LoaderBlank) {
			jQuery('#smart__iFModalBox__iFrame').attr('src', SmartJS_CoreUtils.escape_html(SmartJS_ModalBox.param_LoaderBlank)); // force unload
		} //end if
		jQuery('#smart__iFModalBox__iFrame').attr('src', '').hide();
		//--
		jQuery('#smart__iFModalBox__Div').css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//-- restore parent scrolling
		jQuery('body').css({
			'overflow': 'auto' // need to be 'auto' instead 'visible' to work with IE
		});
		//--
		_class.iFBoxStatus = '';
		//--
		return false;
		//--
	} //END FUNCTION


	var fx_in = function(yProtect, windowWidth, windowHeight) {
		//--
		var the_wWidth = get_Window_Width(windowWidth);
		var the_wHeight = get_Window_Height(windowHeight);
		//--
		var the_wRealWidth = get_Window_Width(0);
		if(the_wRealWidth < windowWidth) {
			the_wWidth = the_wRealWidth;
		} //end if
		var the_wRealHeight = get_Window_Height(0);
		if(the_wRealHeight < windowHeight) {
			the_wHeight = the_wRealHeight;
		} //end if
		//--
		var the_style_cursor = 'auto';
		if(yProtect != 1) {
			the_style_cursor = 'pointer';
		} //end if
		jQuery('#smart__iFModalBox__Bg').css({
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
		fx_position(the_wWidth, the_wHeight);
		//--
		//window.onresize = function () { // called when the window is resized
		//	fx_in(yProtect, windowWidth, windowHeight);
		//} //end function
		//--
		return false;
		//--
	} //END FUNCTION


	var fx_out = function() {
		//--
		//window.onresize = function () { // clear window resize
		//} //end function
		//--
		jQuery('#smart__iFModalBox__Div').css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//--
		jQuery('#smart__iFModalBox__Bg').css({
			'position': 'absolute',
			'width': '1px',
			'height': '1px',
			'left': '0px',
			'top': '0px'
		}).hide();
		//--
		RefreshEXEC_Self(); // {{{SYNC-MODAL-Refresh-Parent-By-EXEC}}}
		//--
		return false;
		//--
	} //END FUNCTION


	var fx_position = function(windowWidth, windowHeight) {
		//--
		var the_h_align = parseInt(parseInt(jQuery(window).scrollLeft()) + ((parseInt(jQuery(window).width()) - windowWidth) / 2)) + 'px';
		var the_v_align = parseInt(parseInt(jQuery(window).scrollTop()) + 10) + 'px';
		if((SmartJS_ModalBox.param_vAlign == 'center') || (SmartJS_ModalBox.param_vAlign == 'middle')) {
			the_v_align = parseInt((parseInt(jQuery(window).scrollTop()) + ((parseInt(jQuery(window).height()) - windowHeight) / 2))) + 'px';
		} //end if else
		//--
		jQuery('#smart__iFModalBox__Div').css({
			'position': 'absolute',
			'z-index': 2111111098, //9999998,
			'text-align': 'center',
			'left': the_h_align,
			'top': the_v_align,
			'width': windowWidth + 'px',
			'height': windowHeight + 'px'
		}).show();
		//--
	} //END FUNCTION


	//================================== # [EXTERNAL HANDLERS THAT CAN BE REDEFINED PER INSTANCE]


	/**
	 * Per-Instance Before Unload custom Handler (BOOLEAN:TRUE or function(){ return true; // or false; } that can be defined
	 * It have to be re-implemented for custom needs when doing unload of Modal Box
	 * @hint By default this function does nothing ...
	 *
	 * @private
	 *
	 * @memberof SmartJS_ModalBox
	 * @method on_Before_Unload
	 * @static
	 *
	 */
	this.on_Before_Unload = function() {
		//--
		return true; // execute code onUnload and must return true if do unload or false to prevent unload
		//--
	} //END FUNCTION


	//================================== # [EXTERNAL EVENT HANDLERS AND DOM REGISTERS]


	jQuery(function() {
		//--
		jQuery('body').append('<!-- SmartJS.Modal.Loader :: Start --><div id="smart__iFModalBox__Bg" data-info-smartframework="SmartFramework.Js.ModalBox: ' + SmartJS_CoreUtils.escape_html(version) + '" style="background-color:' + SmartJS_CoreUtils.escape_html(SmartJS_ModalBox.param_CssOverlayBgColor) + '; position:absolute; top:0px; left:0px; width:1px; height:1px; opacity: ' + SmartJS_CoreUtils.escape_html(SmartJS_ModalBox.param_CssOverlayOpacity) + ';"></div><div id="smart__iFModalBox__Div" style="position:absolute; top:0px; left:0px; width:1px; height:1px;"><center><div id="smart__iFModalBox__Ldr"></div></center><div id="smart__iFModalBox__X" title="[X]"></div><iframe name="smart__iFModalBox__iFrame" id="smart__iFModalBox__iFrame" width="1" height="1" scrolling="auto" src="" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0"></iframe></div><!-- END: SmartJS.Modal.Loader -->');
		SmartJS_ModalBox.fx_Unload();
		//--
		jQuery(window).on('resize scroll', function(ev) {
			if(SmartJS_ModalBox.iFBoxStatus === 'visible') {
				//console.log('Resizing the iFrmodalBox by Window event: Resize or Scroll');
				fx_in(SmartJS_ModalBox.iFBoxProtect, SmartJS_ModalBox.iFBoxWidth, SmartJS_ModalBox.iFBoxHeight);
			} //end if
		});
		//--
	}); // end on document ready


	//==================================


} //END CLASS

//==================================================================
//==================================================================

// #END
