
// [LIB - Smart.Framework / JS / Browser Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// DEPENDS: jQuery, smartJ$Utils, smartJ$Date, smartJ$CryptoHash, smartJ$TestBrowser
// DEPENDS-OPTIONAL: smartJ$ModalBox, jQuery.toastr, jQuery.alertable, smartJ$UI

//==================================================================
//==================================================================

// minimal settings:
// `<script>const smartJ$Options = { BrowserUtils: { LanguageId: 'en', Charset: 'UTF-8', CookieLifeTime: 0, CookieDomain: '', CookieSameSitePolicy: 'Lax', Prefix: '', }, }; Object.freeze(smartJ$Options); window.smartJ$Options = smartJ$Options;</script>`

//================== [ES6]

/**
 * CLASS :: Smart BrowserUtils (ES6)
 *
 * @package Sf.Javascript:Browser
 *
 * @requires		smartJ$Utils
 * @requires		smartJ$Date
 * @requires		smartJ$CryptoHash
 * @requires		smartJ$TestBrowser
 * @requires		jQuery
 * @requires		*smartJ$ModalBox
 * @requires		*jQuery.toastr
 * @requires		*jQuery.alertable
 * @requires		*smartJ$UI (optional) [ smartJ$UI.GrowlAdd ; smartJ$UI.GrowlRemove ; smartJ$UI.DialogAlert ; smartJ$UI.DialogConfirm ; smartJ$UI.overlayCssClass ]
 *
 * @desc The JavaScript class provides methods to simplify the interraction with the Browser, Ajax XHR Requests, Forms, Message Alerts and Message Dialogs, Growl and provide many useful methods for browser interraction.
 * @author unix-world.org
 * @license BSD
 * @file browser_utils.js
 * @version 20250205
 * @class smartJ$Browser
 * @static
 * @frozen
 *
 */
const smartJ$Browser = new class{constructor(){ // STATIC CLASS
	const _N$ = 'smartJ$Browser';

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

	const _Option$ = ((typeof(smartJ$Options) != 'undefined') && smartJ$Options && (typeof(smartJ$Options) === 'object') && (smartJ$Options.BrowserUtils != undefined) && (typeof(smartJ$Options.BrowserUtils) === 'object')) ? smartJ$Options.BrowserUtils : null;

	const _Te$tBrowser = smartJ$TestBrowser;
	const _Utils$ = smartJ$Utils;
	const _Date$ = smartJ$Date;
	const _Crypto$Hash = smartJ$CryptoHash;

	//== params (options)

	/**
	 * Default Language ID (must be set as in PHP, see the SMART_FRAMEWORK_DEFAULT_LANG)
	 * @default 'en'
	 * @var {String} param_LanguageId
	 * @set [before] smartJ$Options.BrowserUtils.LanguageId
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_LanguageId = (_Option$ && (typeof(_Option$.LanguageId) == 'string') && _Option$.LanguageId) ? _Utils$.stringTrim(_Option$.LanguageId) : 'en';

	/**
	 * Character Set (must be set as in PHP, see the SMART_FRAMEWORK_CHARSET)
	 * @default 'UTF-8'
	 * @var {String} param_Charset
	 * @set [before] smartJ$Options.BrowserUtils.Charset
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_Charset = (_Option$ && (typeof(_Option$.Charset) == 'string') && _Option$.Charset) ? _Utils$.stringTrim(_Option$.Charset) : 'UTF-8';

	/**
	 * Cookie Domain (must be set as in PHP, see the SMART_FRAMEWORK_UNIQUE_ID_COOKIE_LIFETIME)
	 * @default 0
	 * @var {Integer} param_CookieLifeTime
	 * @set [before] smartJ$Options.BrowserUtils.CookieLifeTime
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_CookieLifeTime = (_Option$ && (typeof(_Option$.CookieLifeTime) == 'number') && _Option$.CookieLifeTime && _Utils$.isFiniteNumber(_Option$.CookieLifeTime)) ? _Utils$.format_number_int(_Option$.CookieLifeTime, false) : 0;

	/**
	 * Cookie Domain (must be set as in PHP, see the SMART_FRAMEWORK_UNIQUE_ID_COOKIE_DOMAIN)
	 * @default ''
	 * @var {String} param_CookieDomain
	 * @set [before] smartJ$Options.BrowserUtils.CookieDomain
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_CookieDomain = (_Option$ && (typeof(_Option$.CookieDomain) == 'string') && _Option$.CookieDomain) ? _Utils$.stringTrim(_Option$.CookieDomain) : '';

	/**
	 * Cookie SameSite Policy (must be set as in PHP, see the SMART_FRAMEWORK_UNIQUE_ID_COOKIE_SAMESITE) ; To avoid set a policy, use 'Empty'
	 * @default 'Lax'
	 * @var {String} param_CookieSameSitePolicy
	 * @set [before] smartJ$Options.BrowserUtils.CookieSameSitePolicy
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_CookieSameSitePolicy = (_Option$ && (typeof(_Option$.CookieSameSitePolicy) == 'string') && _Option$.CookieSameSitePolicy) ? _Utils$.stringTrim(_Option$.CookieSameSitePolicy) : 'Lax';

	/**
	 * Notification Mode
	 * Allowed Values: 'growl' | 'dialog'
	 * @default 'growl'
	 * @var {String} param_Notifications
	 * @set [before] smartJ$Options.BrowserUtils.Notifications
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_Notifications = (_Option$ && (typeof(_Option$.Notifications) == 'string') && _Option$.Notifications) ? _Utils$.stringTrim(_Option$.Notifications) : 'growl';

	/**
	 * Growl Notification Dialog Type
	 * Allowed Values: 'auto' | 'alertable' | 'ui' | 'native' ; auto=autoselect (reverse order, starting from ui to 'native' with fallback) ; native = browser:alert/prompt ; alertable = use jQuery.alertable ; ui = use smartJ$UI
	 * @default 'auto'
	 * @var {String} param_NotificationDialogType
	 * @set [before] smartJ$Options.BrowserUtils.NotificationDialogType
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_NotificationDialogType = (_Option$ && (typeof(_Option$.NotificationDialogType) == 'string') && _Option$.NotificationDialogType) ? _Utils$.stringTrim(_Option$.NotificationDialogType) : 'auto';

	/**
	 * Growl Notification Growl Type
	 * Allowed Values: 'auto' | 'toastr' | 'ui' ; If Explicit set on 'ui' and UI is n/a will fallback to alert ; If Explicit set on 'toastr' ; ui = use smartJ$UI ; if any is n/a will fallback to browser:alert
	 * @default 'auto'
	 * @var {String} param_NotificationGrowlType
	 * @set [before] smartJ$Options.BrowserUtils.NotificationGrowlType
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_NotificationGrowlType = (_Option$ && (typeof(_Option$.NotificationGrowlType) == 'string') && _Option$.NotificationGrowlType) ? _Utils$.stringTrim(_Option$.NotificationGrowlType) : 'auto';

	/**
	 * Growl Notification Time when OK (in microseconds)
	 * @default 1000
	 * @var {Integer} param_NotificationTimeOK
	 * @set [before] smartJ$Options.BrowserUtils.NotificationTimeOK
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_NotificationTimeOK = (_Option$ && (typeof(_Option$.NotificationTimeOK) == 'number') && _Option$.NotificationTimeOK && _Utils$.isFiniteNumber(_Option$.NotificationTimeOK)) ? _Utils$.format_number_int(_Option$.NotificationTimeOK, false) : 1000;

	/**
	 * Growl Notification Time when ERR (in microseconds)
	 * @default 3500
	 * @var {Integer} param_NotificationTimeERR
	 * @set [before] smartJ$Options.BrowserUtils.NotificationTimeERR
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_NotificationTimeERR = (_Option$ && (typeof(_Option$.NotificationTimeERR) == 'number') && _Option$.NotificationTimeERR && _Utils$.isFiniteNumber(_Option$.NotificationTimeERR)) ? _Utils$.format_number_int(_Option$.NotificationTimeERR, false) : 3500;

	/**
	 * Errors Notification Mode
	 * If set to FALSE will not raise notifications on errors but only will log them
	 * Allowed Values: true | false
	 * @default false
	 * @var {Boolean} param_NotifyLoadError
	 * @set [before] smartJ$Options.BrowserUtils.NotifyLoadError
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_NotifyLoadError = (_Option$ && (!!_Option$.NotifyLoadError)) ? true : false;

	/**
	 * Use ModalBox
	 * If set to 0 will disable the modal iframe ; If set to 1 (as default) will use ModalBox except on mobiles ; If set to 2 will use ModalBox also on mobiles
	 * Allowed Values: 2=true (includding mobile) | 1=true (except on mobile) | 0=false
	 * @default 1
	 * @var {Boolean} param_ModalBoxActive
	 * @set [before] smartJ$Options.BrowserUtils.ModalBoxActive
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_ModalBoxActive = (_Option$ && (_Option$.ModalBoxActive === 2)) ? 2 : ((_Option$ && (_Option$.ModalBoxActive === 0)) ? 0 : 1);

	/**
	 * Enable or Disable the ModalBox Cascading
	 * If set to FALSE will enable the ModalBox (iframe) cascading (inefficient) ; otherwise will use PopUp every next level starting from the ModalBox iframe level, but inside PopUp can open another ModalBox ... and so on
	 * Allowed Values: true | false
	 * @default true
	 * @var {Boolean} param_ModalBoxNoCascade
	 * @set [before] smartJ$Options.BrowserUtils.ModalBoxNoCascade
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_ModalBoxNoCascade = (_Option$ && (_Option$.ModalBoxNoCascade === false)) ? false : true;

	/**
	 * Set ModalBox protected mode (if used)
	 * If set to TRUE will use the protected mode for the modal iFrame (can be closed just explicit by buttons, not clicking outside of it)
	 * Allowed Values: true | false
	 * @default false
	 * @var {Boolean} param_ModalBoxProtected
	 * @set [before] smartJ$Options.BrowserUtils.ModalBoxProtected
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_ModalBoxProtected = (_Option$ && (!!_Option$.ModalBoxProtected)) ? true : false;

	/**
	 * Prefix, for the below Loaders, Images, Icons
	 * @default ''
	 * @var {String} param_Prefix
	 * @set [before] smartJ$Options.BrowserUtils.Prefix
	 * @static
	 * @memberof smartJ$Browser
	 */
	 _C$.param_Prefix = (_Option$ && (typeof(_Option$.Prefix) == 'string') && _Option$.Prefix) ? _Utils$.stringTrim(_Option$.Prefix) : '';

	/**
	 * Loader Image used to display in various contexts when loading ...
	 * @default 'lib/js/framework/img/loading.svg'
	 * @var {String} param_LoaderImg
	 * @set [before] smartJ$Options.BrowserUtils.LoaderImg
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_LoaderImg = (_Option$ && (typeof(_Option$.LoaderImg) == 'string') && _Option$.LoaderImg) ? _Utils$.stringTrim(_Option$.LoaderImg) : _C$.param_Prefix + 'lib/js/framework/img/loading.svg';

	/**
	 * Loader Blank HTML Page used to clear and free memory before loading or after unloading ... (ModalBox / PopUp)
	 * @default 'lib/js/framework/loading.html'
	 * @var {String} param_LoaderBlank
	 * @set [before] smartJ$Options.BrowserUtils.LoaderBlank
	 * @static
	 * @private
	 * @memberof smartJ$Browser
	 */
	_C$.param_LoaderBlank = (_Option$ && (typeof(_Option$.LoaderBlank) == 'string') && _Option$.LoaderBlank) ? _Utils$.stringTrim(_Option$.LoaderBlank) : _C$.param_Prefix + 'lib/js/framework/loading.html';

	/**
	 * OK sign image, used in various contexts
	 * @default 'lib/framework/img/sign-ok.svg'
	 * @var {String} param_ImgOK
	 * @set [before] smartJ$Options.BrowserUtils.ImgOK
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_ImgOK = (_Option$ && (typeof(_Option$.ImgOK) == 'string') && _Option$.ImgOK) ? _Utils$.stringTrim(_Option$.ImgOK) : _C$.param_Prefix + 'lib/framework/img/sign-ok.svg';

	/**
	 * Not OK sign image, used in various contexts
	 * @default 'lib/framework/img/sign-warn.svg'
	 * @var {String} param_ImgNotOK
	 * @set [before] smartJ$Options.BrowserUtils.ImgNotOK
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_ImgNotOK = (_Option$ && (typeof(_Option$.ImgNotOK) == 'string') && _Option$.ImgNotOK) ? _Utils$.stringTrim(_Option$.ImgNotOK) : _C$.param_Prefix + 'lib/framework/img/sign-warn.svg';

	/**
	 * Clone Add (Insert), used for the cloning HTML elements context by CloneElement()
	 * @default 'lib/js/framework/img/clone-insert.svg'
	 * @var {String} param_ImgCloneInsert
	 * @set [before] smartJ$Options.BrowserUtils.ImgCloneInsert
	 * @static
	 * @private
	 * @memberof smartJ$Browser
	 */
	_C$.param_ImgCloneInsert = (_Option$ && (typeof(_Option$.ImgCloneInsert) == 'string') && _Option$.ImgCloneInsert) ? _Utils$.stringTrim(_Option$.ImgCloneInsert) : _C$.param_Prefix + 'lib/js/framework/img/clone-insert.svg';

	/**
	 * Clone Remove (Delete), used for the cloning HTML elements context by CloneElement()
	 * @default 'lib/js/framework/img/clone-remove.svg'
	 * @var {String} param_ImgCloneRemove
	 * @set [before] smartJ$Options.BrowserUtils.ImgCloneRemove
	 * @static
	 * @private
	 * @memberof smartJ$Browser
	 */
	_C$.param_ImgCloneRemove = (_Option$ && (typeof(_Option$.ImgCloneRemove) == 'string') && _Option$.ImgCloneRemove) ? _Utils$.stringTrim(_Option$.ImgCloneRemove) : _C$.param_Prefix + 'lib/js/framework/img/clone-remove.svg'; // private, used by CloneElement()

	/**
	 * Maximize / Unmaximize HTML element, used by createMaxContainer()
	 * @default 'lib/js/framework/img/fullscreen-on.svg'
	 * @var {String} param_IconImgFullScreen
	 * @set [before] smartJ$Options.BrowserUtils.IconImgFullScreen
	 * @static
	 * @private
	 * @memberof smartJ$Browser
	 */
	_C$.param_IconImgFullScreen = (_Option$ && (typeof(_Option$.IconImgFullScreen) == 'string') && _Option$.IconImgFullScreen) ? _Utils$.stringTrim(_Option$.IconImgFullScreen) : _C$.param_Prefix + 'lib/js/framework/img/fullscreen-on.svg'; // private, used by createMaxContainer()

	/**
	 * Overlay Customization - Background Color
	 * Allowed Values: hexa color between #000000 - #FFFFFF
	 * @default '#FFFFFF'
	 * @var {String} param_CssOverlayBgColor
	 * @set [before] smartJ$Options.BrowserUtils.CssOverlayBgColor
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_CssOverlayBgColor = (_Option$ && (typeof(_Option$.CssOverlayBgColor) == 'string') && _Option$.CssOverlayBgColor && _Utils$.isHexColor(_Option$.CssOverlayBgColor)) ? _Utils$.stringTrim(_Option$.CssOverlayBgColor) : '#FFFFFF';
	// {{{SYNC-OVERLAY}}}
	/**
	 * Overlay Customization - Opacity
	 * Allowed Values: between 0.1 and 1
	 * @default 0.1
	 * @var {Decimal} param_CssOverlayOpacity
	 * @set [before] smartJ$Options.BrowserUtils.CssOverlayOpacity
	 * @static
	 * @memberof smartJ$Browser
	 */
	_C$.param_CssOverlayOpacity = (_Option$ && (typeof(_Option$.CssOverlayOpacity) == 'number') && _Utils$.isFiniteNumber(_Option$.CssOverlayOpacity) && (_Utils$.format_number_float(_Option$.CssOverlayOpacity, false) >= 0) && (_Utils$.format_number_float(_Option$.CssOverlayOpacity, false) <= 1)) ? _Utils$.format_number_dec(_Option$.CssOverlayOpacity, 2, false, true) : 0.1;

	/**
	 * The time in microseconds for delayed close of PopUp or Modal
	 * @default 750
	 * @var {Integer} param_TimeDelayCloseWnd
	 * @set [before] smartJ$Options.BrowserUtils.TimeDelayCloseWnd
	 * @static
	 * @private
	 * @memberof smartJ$Browser
	 */
	_C$.param_TimeDelayCloseWnd = (_Option$ && (typeof(_Option$.TimeDelayCloseWnd) == 'number') && _Option$.TimeDelayCloseWnd && _Utils$.isFiniteNumber(_Option$.TimeDelayCloseWnd)) ? _Utils$.format_number_int(_Option$.TimeDelayCloseWnd, false) : 750;

	//==

	//-- specials: don't change them ...
	let flag_PageUnloadConfirm = false;		// keeps the status of PageUnloadConfirm ; default is false
	let flag_PageAway = false;				// keeps the status of PageAway handler ; default is false
	let flag_RefreshState = 0; 				// if=1, will refresh parent ; default is 0
	let flag_RefreshURL = ''; 				// ^=1 -> if != '' will redirect parent ; default is ''
	//-- Debug stuff
	let flag_DebugEnabled = false; 			// needed for some javascript actions to confirm when leaving the page
	let flag_DebugPageAway = false;			// keeps the status of PageAway handler ; default is false
	//--

	//==

	//--
	const defSmartPopupTarget = 'smartPWin'; // the Default SmartPopUp Target Name ; will be used when no target is passed
	//--
	let objRefWinPopup = null; 				 // null or holds the pop-up window reference to avoid opening new popups each time ; reuse it if exists and just focus it (identified by window.name / target.name)
	//--

	//==

	//--
	const sfOverlayID = 'smart-framework-overlay';
	//--
	const cssAlertable = 'background:#555566; color:#FFFFFF; font-size:1.5rem; font-weight:bold; text-align:right; padding-top:2px; padding-bottom:2px; padding-left:7px; padding-right:7px; margin:-8px; margin-bottom:10px;';
	//--
	const regexValidCookieName = RegExp(/^[a-zA-Z0-9_\-]+$/); // {{{SYNC-REGEX-URL-VARNAME}}}
	//--
	const HTTP_STATUS_CODES = {
		'200': 'OK',
		'201': 'Created',
		'202': 'Accepted',
		'203': 'Non-Authoritative Information',
		'204': 'No Content',
		'208': 'Already Reported',
		'301': 'Moved Permanently',
		'302': 'Found',
		'304': 'Not Modified',
		'400': 'Bad Request',
		'401': 'Unauthorized',
		'402': 'Subscription Required',
		'403': 'Forbidden',
		'404': 'Not Found',
		'405': 'Method Not Allowed',
		'406': 'Not Acceptable',
		'408': 'Request Timeout',
		'409': 'Conflict',
		'410': 'Gone',
		'415': 'Unsupported Media Type',
		'422': 'Unprocessable Content',
		'423': 'Locked',
		'424': 'Dependency Failed',
		'429': 'Too Many Requests',
		'500': 'Internal Server Error',
		'501': 'Not Implemented',
		'502': 'Bad Gateway',
		'503': 'Service Unavailable',
		'504': 'Gateway Timeout',
		'507': 'Insufficient Storage',
	};
	//--

	//==

	/**
	 * Set an internal flag
	 * Available flags are:
	 * 		PageUnloadConfirm 	{Boolean}
	 * 		PageAway 			{Boolean}
	 * 		RefreshState 		{0/1}
	 * 		RefreshURL 			{String} || ''
	 *
	 * @memberof smartJ$Browser
	 * @method setFlag
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	flag 		The flag to set
	 * @param 	{Mixed} 	value 		The value of that flag
	 *
	 * @return 	{Boolean} 				TRUE if success, FALSE if not
	 */
	const setFlag = (flag, value) => { // ES6
		//--
		flag = String(flag || '');
		//--
		switch(flag) {
			case 'PageUnloadConfirm':
				flag_PageUnloadConfirm = !! value; // boolean
				return true;
				break;
			case 'PageAway':
				if(flag_DebugEnabled === true) {
					_p$.log(_N$, 'Debug is ON, the setFlag:PageAway is overriden ...');
					flag_DebugPageAway = !! value; // boolean, store in a separate place, need for restore
				} else {
					flag_PageAway = !! value; // boolean
				} //end if
				return true;
				break;
			case 'RefreshState':
				flag_RefreshState = ( !! value ? 1 : 0); // 0/1
				return true;
				break;
			case 'RefreshURL':
				flag_RefreshURL = _Utils$.stringPureVal(value, true); // cast to string, trim ; String: '%url%' || ''
				return true;
				break;
			case 'DebugEnabled':
				flag_DebugEnabled = !! value; // boolean
				if(flag_DebugEnabled === true) {
					PageAwayControl('Debug is ON. Confirm Leaving the page. This confirmation is to avoid javascript redirects without explicit confirmation when Debug is enabled.');
				} else {
					flag_PageAway = !! flag_DebugPageAway; // restore
				} //end if else
				return true;
				break;
			default: // N/A
				_p$.warn(_N$, 'Set Invalid Flag:', flag, value);
		} //end switch
		//--
		return false;
		//--
	}; //END
	_C$.setFlag = setFlag; // export

	/**
	 * Get an internal flag
	 * Available flags are:
	 * 		PageUnloadConfirm 	{Boolean}
	 * 		PageAway 			{Boolean}
	 * 		RefreshState 		{0/1}
	 * 		RefreshURL 			{String} || ''
	 *
	 * @memberof smartJ$Browser
	 * @method getFlag
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	flag 		The flag to return
	 *
	 * @return 	{Mixed} 				The value of that flag
	 */
	const getFlag = (flag) => { // ES6
		//--
		flag = String(flag || '');
		//--
		switch(flag) {
			case 'PageUnloadConfirm':
				return !! flag_PageUnloadConfirm; // bool
				break;
			case 'PageAway':
				return !! flag_PageAway; // bool
				break;
			case 'RefreshState':
				return (flag_RefreshState ? 1 : 0); // 0/1
				break;
			case 'RefreshURL':
				return _Utils$.stringPureVal(flag_RefreshURL, true); // cast to string, trim ; String: '%url%' || ''
				break;
			case 'DebugEnabled':
				return !! flag_DebugEnabled; // bool
				break;
			default: // N/A
				_p$.warn(_N$, 'Get Invalid Flag:', flag);
		} //end switch
		//--
		return null;
		//--
	}; //END
	_C$.getFlag = getFlag; // export

	/**
	 * Get the popUp Window Object Reference if any
	 *
	 * @memberof smartJ$Browser
	 * @method getRefPopup
	 * @static
	 * @arrow
	 *
	 * @return 	{Mixed} 				NULL or Object (PopUp Window Reference)
	 */
	const getRefPopup = () => objRefWinPopup; // ES6
	_C$.getRefPopup = getRefPopup; // export

	/**
	 * Get the Current ISO Date and Time
	 *
	 * @memberof smartJ$Browser
	 * @method getCurrentIsoDateTime
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 				The Current ISO Date and Time as YYYY-MM-DD HH:II:SS
	 */
	const getCurrentIsoDateTime = () => { // ES6
		//--
		const crrDate = new Date();
		//--
		return String(_Date$.getIsoDate(crrDate, true));
		//--
	}; // END
	_C$.getCurrentIsoDateTime = getCurrentIsoDateTime; // export

	/**
	 * Strip HTML Tags and return plain text from HTML Code
	 *
	 * @memberof smartJ$Browser
	 * @method stripTags
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	html 		The html code to be processed
	 *
	 * @return 	{String} 				Plain Text
	 */
	const stripTags = (html) => { // ES6
		//--
		html = _Utils$.stringPureVal(html); // cast to string, don't trim ! need to preserve the value
		if(html == '') {
			return '';
		} //end if
		//--
		return $('<div>' + html.replace(/(<([^>]+)>)/g, ' ') + '</div>').text();
		//--
	}; // END
	_C$.stripTags = stripTags; // export

	/**
	 * Parse Current URL to extract GET Params
	 * @example 	'http(s)://some.url/?param1=value1&param2=value%202' // sample URL
	 *
	 * @memberof smartJ$Browser
	 * @method parseCurrentUrlGetParams
	 * @static
	 *
	 * @param 	{Boolean} 	semantic_sf_url 		*Optional* ; Default is TRUE ; If set to FALSE will not post-process the semantic URL parts from Smart.Framework such as: `/page/one/something/else`
	 *
	 * @return 	{Object} 							{ param1:'value1', param1:'value 2', ... }
	 */
	const parseCurrentUrlGetParams = function(semantic_sf_url=true) { // ES6
		//--
		let result = {};
		//--
		if(!location.search) {
			return result; // Object
		} //end if
		let query = String(location.search.substring(1)); // 'param1=value1&param2=value%202' from '?param1=value1&param2=value%202'
		if(!query) {
			return result; // Object
		} //end if
		//--
		query.split('&').forEach((part) => {
			let item = '';
			let v = '';
			let s = '';
			part = String(part);
			if(part) {
				item = part.split('=');
				v = String(item[0]);
				if((semantic_sf_url === true) && (v.indexOf('/') !== -1)) { // process or not semantic url from sf
					let arr = v.split('/');
					let found = false;
					for(let i=0; i<arr.length; i++) {
						if(found !== true) {
							if(arr[i] !== '') {
								found = true; // start with 1st valid sequence
							} else {
								continue;
							} //end if
						} //end if
						result[String(arr[i])] = String(decodeURIComponent(String(arr[i+1] ? arr[i+1] : '')) || '');
						i += 1;
					} //end for
				} else {
					result[String(item[0])] = String(decodeURIComponent(String(item[1] ? item[1] : '')) || '');
				} //end if else
			} //end if
		});
		//--
		return result; // Object
		//--
	}; //END
	_C$.parseCurrentUrlGetParams = parseCurrentUrlGetParams; // export

	/**
	 * Print current Browser Page
	 *
	 * @memberof smartJ$Browser
	 * @method PrintPage
	 * @static
	 * @arrow
	 *
	 * @fires Print Dialog Show Event
	 */
	const PrintPage = () => { // ES6
		//--
		try {
			self.print();
		} catch(err){
			_p$.warn(_N$, 'Print Page is N/A:', err);
			AlertDialog('NOTICE: Printing may not be available in your browser');
		} //end try catch
		//--
	}; //END
	_C$.PrintPage = PrintPage; // export

	/**
	 * Count Down handler that bind to a HTML Element
	 *
	 * @memberof smartJ$Browser
	 * @method CountDown
	 * @static
	 *
	 * @param 	{Integer} 	counter 		The countdown counter, Min value is 1
	 * @param 	{String} 	elID 			The HTML Element ID to bind to or NULL
	 * @param 	{JS-Code} 	evcode 			*Optional* the JS Code to execute on countdown complete (when countdown to zero)
	 * @param	{Boolean}	prettyFormat	*Optional* if set to TRUE instead to display the left time in seconds will display as pretty format like: Days, Hours, Minutes, Seconds
	 * @fires 	A custom event set in the Js Code to execute when done
	 */
	const CountDown = function(counter, elID, evcode, prettyFormat) { // ES6
		//--
		const _m$ = 'CountDown';
		//--
		if((counter == undefined) || (counter == '')) { // undef tests also for null
			_p$.error(_N$, _m$, 'ERR: undefined Counter Init');
			return;
		} //end if
		//--
		counter = _Utils$.format_number_int(counter, false);
		if(counter < 1) {
			return; // avoid infinite cycle
		} //end if
		//--
		const cdwn = setInterval(() => {
			//--
			if(counter > 0) {
				//--
				counter = counter - 1;
				//--
				if((elID != undefined) && (elID != '')) { // undef tests also for null
					const theID = _Utils$.create_htmid(elID);
					let cntTxt = String(counter);
					if(prettyFormat === true) {
						cntTxt = String(_Date$.prettySecondsHFmt(counter));
					} //end if
					if(theID != '') {
						$('#' + theID).empty().text(cntTxt);
					} //end if
				} //end if
				//--
			} else {
				//--
				clearInterval(cdwn);
				//--
				_Utils$.evalJsFxCode( // EV.CTX
					_N$ + '.' + _m$,
					(typeof(evcode) === 'function' ?
						() => {
							'use strict'; // req. strict mode for security !
							(evcode)(counter, elID);
						} :
						() => {
							'use strict'; // req. strict mode for security !
							!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: counter, elID
						}
					)
				);
				//--
			} //end if
			//--
		}, 1000); // 1 second
		//--
	}; //END
	_C$.CountDown = CountDown; // export

	/**
	 * Focus a browser window by reference
	 *
	 * @memberof smartJ$Browser
	 * @method windowFocus
	 * @static
	 * @arrow
	 *
	 * @param 	{Object} 	wnd 		The window (reference) object ; which ? self, window, ...
	 *
	 * @fires activate Focus on a PopUp window
	 */
	const windowFocus = (wnd) => {
		//--
		try {
			wnd.focus(); // focus the window (it may fail if window reference with parent is lost, needs try/catch)
		} catch(err){} // older browsers have some bugs
		//--
	}; //END
	_C$.windowFocus = windowFocus; // export

	/**
	 * Detect if a Browser Window is an iFrame
	 *
	 * @memberof smartJ$Browser
	 * @method WindowIsiFrame
	 * @static
	 * @arrow
	 *
	 * @return 	{Boolean} 							TRUE if iFrame, FALSE if not
	 */
	const WindowIsiFrame = () => { // ES6
		//--
		if(window.self !== window.top) {
			return true; // is iframe
		} //end if
		//--
		return false; // not an iframe
		//--
	}; //END
	_C$.WindowIsiFrame = WindowIsiFrame; // export

	/**
	 * Detect if a Browser Window is a PopUp
	 *
	 * @memberof smartJ$Browser
	 * @method WindowIsPopup
	 * @static
	 * @arrow
	 *
	 * @return 	{Boolean} 							TRUE if PopUp, FALSE if not
	 */
	const WindowIsPopup = () => { // ES6
		//--
		if(window.opener) {
			return true; // is popup
		} //end if
		//--
		return false; // not an popup
		//--
	}; //END
	_C$.WindowIsPopup = WindowIsPopup; // export

	/**
	 * Page Away control handler. Take control over the events as onbeforeunload to prevent leaving the browser page in unattended mode
	 *
	 * @memberof smartJ$Browser
	 * @method PageAwayControl
	 * @static
	 *
	 * @param 	{String} 	the_question 	The Question to confirm for navigate away of the page
	 *
	 * @fires Ask Confirmation Dialog to confirm navigate away of the page
	 * @listens Browser Page Unload
	 */
	const PageAwayControl = function(the_question) { // ES6
		//--
		_C$.setFlag('PageUnloadConfirm', true);
		//--
		the_question = _Utils$.stringPureVal(the_question, true); // cast to string, trim
		if(the_question == '') {
			the_question = 'Confirm leaving the page ... ?';
		} //end if
		//--
		$(window).on('beforeunload', (evt) => {
			let e = evt || window.event;
			if(_C$.getFlag('PageAway') != true) {
				e.preventDefault();
				if(_C$.getFlag('DebugEnabled') == true) {
					const debugMsgTitle = '*** DEBUG IS ON ***';
					const debugMsgText = 'Page Leave Confirmation is ENABLED';
					_p$.log(_N$, debugMsgTitle, debugMsgText);
					if(GrowlSelectType() != '') {
						GrowlNotificationAdd(debugMsgTitle, _Utils$.escape_html(debugMsgText), '', 10000, false, 'yellow');
					} //end if
				} //end if
				return String(the_question);
			} //end if
		}).on('unload', () => {
			_C$.setFlag('PageAway', true);
		});
		//--
		if(WindowIsiFrame() === true) { // try to set only if iframe
			if(typeof(parent.smartJ$ModalBox) != 'undefined') {
				try {
					parent.smartJ$ModalBox.setHandlerOnBeforeUnload(() => {
						if(_C$.getFlag('PageAway') != true) {
							let is_exit = confirm(String(the_question)); // true or false
							if(is_exit) {
								_C$.setFlag('PageAway', true);
							} //end if
							return is_exit;
						} else {
							return true;
						} //end if else
					});
				} catch(err) {
					_p$.error(_N$, 'ERR: PageAwayControl failed on ModalBox:', err);
				} //end try catch
			} //end if
		} //end if
		//--
	}; //END
	_C$.PageAwayControl = PageAwayControl; // export

	/**
	 * Redirect to a new URL or Reload the current browser location
	 *
	 * @memberof smartJ$Browser
	 * @method RedirectToURL
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	yURL 		The URL (relative or absolute) to redirect to ; if = '@' will just autorefresh the page
	 * @fires Browser Location Redirect or Location Reload
	 */
	const RedirectToURL = (yURL) => { // ES6
		//--
		yURL = _Utils$.stringPureVal(yURL, true); // cast to string, trim
		if(yURL == '') {
			_p$.warn(_N$, 'WARN: RedirectToURL: empty URL');
			return;
		} //end if
		//--
		setFlag('PageAway', true);
		if(yURL === '@') {
			self.location = self.location; // avoid location reload to avoid re-post vars
		} else {
			self.location = String(yURL);
		} //end if else
		//--
	}; //END
	_C$.RedirectToURL = RedirectToURL; // export

	/**
	 * Delayed Redirect to URL current browser window
	 *
	 * @memberof smartJ$Browser
	 * @method RedirectDelayedToURL
	 * @static
	 *
	 * @param 	{String} 	yURL 		The URL (relative or absolute) to redirect to
	 * @param 	{Integer} 	ytime 		The time delay in milliseconds
	 *
	 * @fires Redirect Browser to a new URL
	 * @listens TimeOut counter on Set as Delay
	 */
	const RedirectDelayedToURL = function(yURL, ytime) { // ES6
		//--
		ytime = _Utils$.format_number_int(ytime, false);
		//--
		setTimeout(() => { RedirectToURL(yURL); }, ytime);
		//--
	}; //END
	_C$.RedirectDelayedToURL = RedirectDelayedToURL; // export

	/**
	 * Redirect to URL the browser parent window
	 *
	 * @memberof smartJ$Browser
	 * @method RedirectParent
	 * @static
	 *
	 * @param 	{String} 	yURL 			The URL (relative or absolute) to redirect to
	 *
	 * @fires Redirect Browser to a new URL
	 */
	const RedirectParent = function(yURL) { // ES6
		//--
		const _m$ = 'RedirectParent';
		//--
		yURL = _Utils$.stringPureVal(yURL, true); // cast to string, trim
		if(yURL == '') {
			_p$.warn(_N$, _m$, 'WARN: empty URL');
			return;
		} //end if
		//--
		yURL = String(yURL);
		//--
		if(WindowIsPopup() === true) { // when called from PopUp
			try {
				window.opener.location = yURL;
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR: PopUp:', err);
			} //end try catch
		} else if(WindowIsiFrame() === true) { // when called from iFrame
			try {
				parent.location = yURL;
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR: iFrame:', err);
			} //end try catch
		} //end if else
		//--
	}; //END
	_C$.RedirectParent = RedirectParent;

	/**
	 * Lazy Refresh parent browser window or Lazy redirect parent window to another URL.
	 * @desc The method is different than RedirectParent() because will be executed just after the child (modal iFrame / PopUp) is closed.
	 * @hint It will just trigger a lazy refresh on parent that will be executed later, after closing current child window.
	 *
	 * @memberof smartJ$Browser
	 * @method RefreshParent
	 * @static
	 *
	 * @param 	{String} 	yURL 			*Optional* The URL (relative or absolute) to redirect to ; if the parameter is not specified will just reload the parent window with the same URL
	 *
	 * @fires Refresh Parent Window direct (instant) / or indirect (after closing modal)
	 */
	const RefreshParent = function(yURL) { // ES6
		//--
		const _m$ = 'RefreshParent';
		//--
		yURL = _Utils$.stringPureVal(yURL, true); // cast to string, trim
		//--
		if(WindowIsPopup() === true) { // when called from PopUp
			//--
			try {
				if(window.opener) {
					if((typeof(window.opener.smartJ$Browser) == 'undefined') || (typeof(window.opener.smartJ$ModalBox) == 'undefined')) {
						_p$.warn(_N$, _m$, 'ERR: N/A for PopUp Parent');
						return;
					} //end if
					if(window.opener.smartJ$Browser.getFlag('PageUnloadConfirm') === true) {
						return; // Parent Refresh skip, already have PageAway Confirmation
					} //end if
					if(window.opener.smartJ$ModalBox.getStatus() === 'visible') { // {{{SYNC-TRANSFER-MODAL-POPUP-REFRESH}}}
						window.opener.smartJ$ModalBox.setRefreshParent(1, String(yURL));
						return;
					} //end if
					window.opener.smartJ$Browser.setFlag('RefreshState', 1);
					window.opener.smartJ$Browser.setFlag('RefreshURL', yURL);
				} //end if
			} catch(err){
				_p$.error(_N$, _m$, 'ERR: Failed for PopUp:', err);
			} //end try catch
			//--
		} else if(WindowIsiFrame() === true) { // when called from iFrame
			//--
			try {
				if(self.name) {
					if((typeof(parent.smartJ$Browser) == 'undefined') || (typeof(parent.smartJ$ModalBox) == 'undefined')) {
						_p$.warn(_N$, _m$, 'ERR: N/A for iFrame Parent');
						return;
					} //end if
					if(self.name === parent.smartJ$ModalBox.getName()) {
						if(parent.smartJ$Browser.getFlag('PageUnloadConfirm') !== true) {
							parent.smartJ$ModalBox.setRefreshParent(true, yURL);
						} //end if
					} //end if
				} //end if
			} catch(err){
				_p$.error(_N$, _m$, 'ERR: Failed from iFrame:', err);
			} //end try catch
			//--
		} //end if else
		//--
	}; //END
	_C$.RefreshParent = RefreshParent; // export

	/**
	 * Close a Modal / PopUp child browser window.
	 * If a parent refresh is pending will execute it after.
	 * @hint It is the prefered way to close a child window (modal iFrame / PopUp) using a button or just executing some js code in a child page.
	 *
	 * @memberof smartJ$Browser
	 * @method CloseModalPopUp
	 * @static
	 *
	 * @fires Closes a Modal Box or PopUp Window
	 */
	const CloseModalPopUp = function() { // ES6
		//--
		const _m$ = 'CloseModalPopUp';
		//--
		if(WindowIsPopup() === true) { // when called from PopUp ; not necessary for normal situations as it is fired directly by close monitor {{{SYNC-POPUP-Refresh-Parent-By-EXEC}}} ; err is catched inside ; it is required only for the situation when popup is created manually with window.open NOT by using the initModalOrPopUp()
			//--
			if((window.opener) && (typeof(window.opener.smartJ$Browser) != 'undefined')) {
				try {
					if((window.opener.smartJ$Browser.getRefPopup()) && (window.opener.smartJ$Browser.getRefPopup() === self)) {
						// situation will be handled by the initModalOrPopUp handler (timer) for PopUps, no needed to use directly
					} else {
						if(window.opener.smartJ$Browser.getFlag('RefreshState')) {
							if(window.opener.smartJ$Browser.getFlag('RefreshURL')) {
								window.opener.location = String(window.opener.smartJ$Browser.getFlag('RefreshURL'));
							} else {
								window.opener.location = window.opener.location; // FIX: avoid location reload to resend POST vars !!
							} //end if else
							window.opener.smartJ$Browser.setFlag('RefreshState', 0);
							window.opener.smartJ$Browser.setFlag('RefreshURL', '');
						} //end if
					} //end if else
				} catch(err) {
					_p$.error(_N$, _m$, 'ERR: Failed with PopUp:', err);
				} //end try catch
			} else {
				_p$.warn(_N$, _m$, 'ERR: PopUp Parent BrowserUtils is N/A');
			} //end if else
			//--
			try {
				self.close(); // it may fail if window reference with parent is lost (aka orphan window), needs try/catch
			} catch(err){}
			//--
		} else if(WindowIsiFrame() === true) { // when called from iFrame
			//--
			if(typeof(parent.smartJ$ModalBox) == 'undefined') {
				_p$.warn(_N$, _m$, 'ERR: iFrame Parent ModalBox is N/A');
				return;
			} //end if
			//--
			try {
				if(self.name) {
					if(self.name === parent.smartJ$ModalBox.getName()) {
						parent.smartJ$ModalBox.UnloadURL(); // {{{SYNC-MODAL-Refresh-Parent-By-EXEC}}}
					} //end if
				} //end if
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR: Failed with iFrame:', err);
			} //end try catch
			//--
		} else { // if a popup lost parent reference or a manual opened link, try to close it
			//--
			try {
				self.close(); // it may fail if window reference with parent is lost (aka orphan window), needs try/catch
			} catch(err){}
			//--
		} //end if else
		//--
	}; //END
	_C$.CloseModalPopUp = CloseModalPopUp; // export

	/**
	 * Delayed Close a Modal / PopUp child browser window.
	 * If a parent refresh is pending will execute it after.
	 * @hint It is the prefered way to close a child window (modal iFrame / PopUp) using a button or just executing some js code in a child page.
	 *
	 * @memberof smartJ$Browser
	 * @method CloseDelayedModalPopUp
	 * @static
	 *
	 * @param 	{Integer} 	timeout 		The time delay in milliseconds ; If is NULL or 0 (zero) will use the default timeout
	 *
	 * @fires Closes a Modal Box or PopUp Window
	 * @listens TimeOut counter on Set
	 */
	const CloseDelayedModalPopUp = function(timeout) { // ES6
		//--
		timeout = _Utils$.format_number_int(timeout, false);
		if(timeout <= 0) {
			timeout = _Utils$.format_number_int(_C$.param_TimeDelayCloseWnd, false);
		} //end if
		//--
		if(timeout < 100) {
			timeout = 100; // min 0.1 sec.
		} else if(timeout > 60000) {
			timeout = 60000; // max 60 sec.
		} //end if
		//--
		setTimeout(() => { setFlag('PageAway', true); CloseModalPopUp(); }, timeout);
		//--
	}; //END
	_C$.CloseDelayedModalPopUp = CloseDelayedModalPopUp; // export

	/**
	 * Get the Pointer Event pageX / pageY or clientX / clientY coordinates, depending how viwePortOnly is set.
	 * It works on both: mouse or touch devices.
	 * On touch devices will look after: event.touches[0]
	 * On mouse devices directly to: event
	 * The pageX/Y coordinates are relative to the top left corner of the whole rendered page (including parts hidden by scrolling), while clientX/Y coordinates are relative to the top left corner of the visible part of the page (aka ViewPort)
	 *
	 * @param 	{Event} 	evt 					The EVENT Object Reference (must be a pointer event: mouse or touch)
	 * @param 	{Boolean} 	viwePortOnly 			Default is FALSE ; if TRUE will return clientX / clientY instead of pageX / pageY
	 *
	 * @memberof smartJ$Browser
	 * @method getCurrentPointerEventXY
	 * @static
	 *
	 * @return 	{Object} 							An object with x and y coordinates as: { x: 123, y: 234, t: 'mousemove' } or { x: 123, y: 234, t: 'touchmove' } ; t (type) can be also other event type ... any of supported by browser ; if can't detect will return by default: { x: -1, y: -1 }
	 */
	const getCurrentPointerEventXY = function(evt, viwePortOnly=false) {
		//--
		let coordinates = { x: -1, y: -1, t: null, q: null };
		//--
		if(!evt) {
			return coordinates;
		} //end if
		//--
		let pointEv = evt;
		if(evt.touches && evt.touches.length > 0) {
			pointEv = evt.touches[0];
		} //end if
		//--
		let X = -1;
		let Y = -1;
		if(viwePortOnly === true) {
			coordinates.q = 'clientXY';
			X = pointEv.clientX || -1;
			Y = pointEv.clientY || -1;
		} else {
			coordinates.q = 'pageXY';
			X = pointEv.pageX || -1;
			Y = pointEv.pageY || -1;
		} //end if else
		coordinates.x = _Utils$.format_number_int(X);
		coordinates.y = _Utils$.format_number_int(Y);
		coordinates.t = _Utils$.stringPureVal(evt.type || 'unknown', true).toLowerCase();
		//--
		return coordinates;
		//--
	}; //END
	_C$.getCurrentPointerEventXY = getCurrentPointerEventXY; // export

	/**
	 * Get the highest available Z-Index from a browser page taking in account all visible layers (div).
	 * It will ignore non-visible layers for speed-up, as they have anyway no Z-Index assigned.
	 *
	 * @memberof smartJ$Browser
	 * @method getHighestZIndex
	 * @static
	 *
	 * @return 	{Integer} 							The highest available Z-Index from current page
	 */
	const getHighestZIndex = function() {
		//-- inits
		let index_highest = 1;
		let index_current = 1;
		//--
		$('div').each((i, el) => { // it will scan just divs to be efficient
			let $el = $(el);
			if(
				($el.css('display') == 'none') ||
				($el.css('visibility') == 'hidden') ||
				($el.attr('id') == 'SmartFramework___Debug_InfoBar') // it should be not considered
			) {
				// skip
			} else {
				index_current = _Utils$.format_number_int(parseInt($el.css('z-index'), 10));
				if(index_current > 0) {
					if(index_current > index_highest) {
						index_highest = index_current;
					} //end if
				} //end if
			} //end if else
		});
		//--
		if(index_highest < 2147483647) {
			index_highest += 1; // return next
		} //end if
		//--
		return index_highest;
		//--
	}; //END
	_C$.getHighestZIndex = getHighestZIndex; // export

	/**
	 * Create a Max Container arround a HTML Element that will add the Maximize View handler over it ; element must be a layer (div, iframe, ...).
	 * It will add a maximize / un-maximize button and the handler for it to be able to maximize the element to match full window width and height.
	 * @hint When the selected element will be maximized the page will be protected by an overlay (all content below the element).
	 *
	 * @memberof smartJ$Browser
	 * @method createMaxContainer
	 * @static
	 *
	 * @param 	{String} 	id 			The HTML id of the HTML element to bind to
	 * @param 	{Boolean} 	maximized 	*Optional* Default is FALSE ; If set to TRUE the element will be auto-maximized when bind to it
	 */
	const createMaxContainer = function(id, maximized=false) {
		//--
		id = _Utils$.stringPureVal(id, true); // cast to string, trim
		id = _Utils$.create_htmid(id);
		if(id == '') {
			_p$.error(_N$, 'ERR: createMaxContainer: empty ID');
			return;
		} //end if
		//--
		const $el = $('#' + id);
		//--
		if(($el.attr('data-fullscreen') == 'default') || ($el.attr('data-fullscreen') == 'fullscreen')) {
			return; // avoid apply twice
		} //end if
		//--
		$el.attr('data-fullscreen', 'default').append('<div style="position:absolute; top:-4px; left:-4px; width:20px; height: 20px; overflow:hidden; text-align:center; cursor:pointer; opacity:0.7;" title="Toggle Element Full Screen"><img height="20" src="' + _Utils$.escape_html(_C$.param_IconImgFullScreen) + '"></div>').css({ 'position': 'relative', 'border': '1px solid #DDDDDD', 'z-index': 1 }).click((btn) => {
			const $btn = $(btn.currentTarget);
			if($btn.attr('data-fullscreen') == 'fullscreen') {
				OverlayHide();
				$btn.attr('data-fullscreen', 'default');
				$btn.css({
					'position': 'relative',
					'top': 0,
					'left': 0,
					'width':  Math.max(_Utils$.format_number_int(parseInt($btn.attr('data-width'))), 100),
					'height': Math.max(_Utils$.format_number_int(parseInt($btn.attr('data-height'))), 100),
					'z-index': 1
				});
			} else {
				OverlayShow();
				OverlayClear();
				$btn.attr('data-width', String(_Utils$.escape_html(String($btn.width()))));
				$btn.attr('data-height', String(_Utils$.escape_html(String($btn.height()))));
				$btn.attr('data-fullscreen', 'fullscreen');
				$btn.css({
					'position': 'fixed',
					'top': '7px',
					'left': '7px',
					'width': '99%',
					'height': '98%',
					'z-index': 2147403000
				});
			} //end if else
		});
		//--
		if(maximized === true) {
			$el.trigger('click');
		} //end if
		//--
	}; //END
	_C$.createMaxContainer = createMaxContainer; // export

	/**
	 * Display a page overlay in the current browser window
	 * All page elements that must be visible over the overlay must have zIndex in range: 2147401000 - 214740999
	 * @hint The ZIndex of the overlay is: 2147400000
	 *
	 * @memberof smartJ$Browser
	 * @method OverlayShow
	 * @static
	 *
	 * @param 	{String} 	text 			*Optional* a text to display (HTML) in overlay as notification
	 * @param 	{String} 	title 			*Optional* a title to display (Plain Text) for overlay as notification
	 * @param 	{String} 	css_class 		*Optional* a CSS class name for the notification (if any)
	 * @param 	{String} 	overlay_id 		*Optional* the overlay ID ; default is null and will use a hard-coded ID
	 * @param 	{Boolean} 	clear 			*Optional* clear the overlay from loading img
	 * @return 	{Object} 					The overlay as HTML object
	 */
	const OverlayShow = function(text, title, css_class, overlay_id=null, clear=false) { // ES6
		//--
		text =  _Utils$.stringPureVal(text, true); // cast to string, trim
		title =  _Utils$.stringPureVal(title, true); // cast to string, trim
		//--
		css_class =  _Utils$.stringPureVal(css_class, true); // cast to string, trim
		if(css_class == '') {
			css_class = 'white';
		} //end if
		//--
		overlay_id = OverlayValidID(overlay_id);
		//--
		let the_style = 'style="display: none; position: fixed; top: -50px; left: -50px; width: 1px; height: 1px; background: ' + _Utils$.escape_html(_Utils$.hex2rgba(_C$.param_CssOverlayBgColor, _C$.param_CssOverlayOpacity)) + '; backdrop-filter: blur(1px);"'; // {{{SYNC-OVERLAY}}}
		if(typeof(smartJ$UI) == 'object') {
			if((typeof(smartJ$UI.overlayCssClass) != 'undefined') && (smartJ$UI.overlayCssClass != null) && (smartJ$UI.overlayCssClass != '')) {
				the_style = 'class="' + _Utils$.escape_html(smartJ$UI.overlayCssClass) + '"'; // integrate with UI's Overlay
			} //end if
		} //end if
		//--
		const have_growl = (GrowlSelectType() != '') ? true : false;
		//--
		let inner_html = '<img src="' + _Utils$.escape_html(_C$.param_LoaderImg) + '" alt="... loading ..." style="background:transparent!important;color:#555555!important;opacity:1!important;">';
		if(clear === true) {
			inner_html = '';
		} //end if
		if(have_growl !== true) {
			inner_html = inner_html + '<div>' + '<h1>' + _Utils$.escape_html(title) + '</h1>' + '<div>' + text + '</div>' + '</div>';
		} //end if else
		//--
		$('#' + overlay_id).remove(); // remove any instance if previous exist
		const overlay = $('<div id="' + _Utils$.escape_html(overlay_id) + '" ' + the_style + '></div>').css({ 'z-index': 2147400000, 'position': 'fixed', 'top': '0px', 'left': '0px', 'width': '100%', 'height': '100%' }).hide().appendTo('body');
		$('#' + overlay_id).html('<div style="width:100%; position:fixed; top:25px; left:0px;"><center><div>' + inner_html + '</div></center></div>');
		//--
		try {
			overlay.fadeIn();
		} catch(err){
			overlay.show();
		} //end try catch
		//--
		if((text != '') || (title != '')) {
			if(have_growl === true) {
				GrowlNotificationAdd(title, text, '', 500, false, css_class);
			} //end if else
		} //end if else
		//--
		return overlay;
		//--
	}; //END
	_C$.OverlayShow = OverlayShow; // export

	/**
	 * Clear all notifications from the page overlay in the current browser window
	 *
	 * @memberof smartJ$Browser
	 * @method OverlayClear
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	overlay_id 		*Optional* the overlay ID ; default is null and will use a hard-coded ID
	 */
	const OverlayClear = (overlay_id=null) => { // ES6
		//--
		overlay_id = OverlayValidID(overlay_id);
		//--
		$('#' + overlay_id).empty().html('');
		//--
	}; //END
	_C$.OverlayClear = OverlayClear; // export

	/**
	 * Hide (destroy) the page overlay in the current browser window
	 *
	 * @memberof smartJ$Browser
	 * @method OverlayHide
	 * @static
	 */
	const OverlayHide = function(overlay_id=null) { // ES6
		//--
		overlay_id = OverlayValidID(overlay_id);
		//--
		let overlay = $('#' + overlay_id);
		//--
		try {
			overlay.fadeOut();
		} catch(err){
			overlay.hide();
		} //end try catch
		//--
		overlay.css({ 'z-index': 1, 'position': 'fixed', 'top': '-50px', 'left': '-50px', 'width': 1, 'height': 1 }).remove(); // remove the instance
		//--
	}; //END
	_C$.OverlayHide = OverlayHide; // export

	/**
	 * Create a Message Growl Notification
	 *
	 * @memberof smartJ$Browser
	 * @method GrowlNotificationAdd
	 * @static
	 *
	 * @param 	{String} 		title 				a title for the notification (Plain Text) ; it can be empty string
	 * @param 	{String} 		text 				the main notification message (HTML) ; it is mandatory
	 * @param 	{String} 		image 				the URL link to a notification icon image (svg/gif/png/jpg/webp) or null
	 * @param 	{Integer} 		time 				the notification display time in milliseconds ; use 0 for sticky ; between 0 (0 sec) and 60000 (60 sec)
	 * @param 	{Boolean} 		sticky 				*Optional* FALSE by default (will auto-close after the display time expire) ; TRUE to set sticky (require manual close, will not auto-close)
	 * @param 	{Enum} 			css_class 			*Optional* a CSS class name for the notification or empty string: info (white), hint (black), notice (blue), success (green), warning (yellow), error (pink), fail (red)
	 * @param 	{Array-Obj} 	options 			*Optional* Extra Growl Properties:
	 * 		{ // example of extra Options
	 * 			before_open: 	() => {},
	 * 			after_open: 	() => {},
	 * 			before_close: 	() => {},
	 * 			after_close: 	() => {}
	 * 		}
	 * @param 	{Boolean} 		isTitleHtml 		*Optional* The Dialog Type ; default is FALSE ; If TRUE, will allow HTML Title
	 * @return 	{Boolean} 							TRUE if Success FALSE if Fail
	 */
	const GrowlNotificationAdd = function(title, html, image, time, sticky=false, css_class=null, options=null, isTitleHtml=false) { // ES6 {{{SYNC-JS-GROWL-TRANSLATE-CLASSES}}}
		//--
		const _m$ = 'GrowlNotificationAdd';
		//--
		title = _Utils$.stringPureVal(title); // do not trim, needed for NL2BR
		if(isTitleHtml === true) {
			// let as it is, it is supposed to be valid HTML
		} else {
			title = _Utils$.nl2br(_Utils$.escape_html(title)); // escape, it is plain text + NL2BR
		} //end if
		html  = _Utils$.stringPureVal(html, true);
		//--
		time = _Utils$.format_number_int(time, false);
		if(time < 0) {
			time = 0;
		} else if(time > 60000) {
			time = 60000;
		} //end if
		//--
		css_class = _Utils$.stringPureVal(css_class, true); // cast to string, trim
		if(css_class == 'undefined') {
			css_class = '';
		} //end if
		//--
		let growl_before_open = null;
		let growl_after_open = null;
		let growl_before_close = null;
		let growl_after_close = null;
		//--
		if(options) {
			if(options.hasOwnProperty('before_open')) {
				growl_before_open = options.before_open;
			} //end if
			if(options.hasOwnProperty('after_open')) {
				growl_after_open = options.after_open;
			} //end if
			if(options.hasOwnProperty('before_close')) {
				growl_before_close = options.before_close;
			} //end if
			if(options.hasOwnProperty('after_close')) {
				growl_after_close = options.after_close;
			} //end if
		} //end if
		//--
		const theGrowlType = GrowlSelectType();
		//--
		if(theGrowlType === 'ui') {
			//--
			try {
				smartJ$UI.GrowlAdd(title, html, image, time, sticky, css_class, options);
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR: growl:', err);
			} // end try catch
			//--
		} else if(theGrowlType === 'toastr') {
			//--
			if((image != undefined) && (image != '') && (image !== false)) { // undef tests also for null
				image = '<img src="' + _Utils$.escape_html(image) + '" align="right">';
			} else {
				image = '';
			} //end if
			css_class = $.toastr.translateCssClasses(css_class);
			try {
				$.toastr.notify({
					appearanceClass: String(css_class),
					title: String(String(title) + String(image)),
					message: String(html),
					onBeforeVisible: growl_before_open,
					onVisible: growl_after_open,
					onBeforeHidden: growl_before_close,
					onHidden: growl_after_close,
					timeOut: (sticky ? 0 : time),
				});
			} catch(err) {
				_p$.error(_N$, _m$, 'ERR: toastr:', err);
			} //end try catch
			//--
		} else { // fallback to dialog
			//--
			if($.isFunction(growl_before_open)) {
				growl_before_open();
			} //end if
			if($.isFunction(growl_after_open)) {
				growl_after_open();
			} //end if
			let fx = () => {
				if($.isFunction(growl_before_close)) {
					growl_before_close();
				} //end if
				if($.isFunction(growl_after_close)) {
					growl_after_close();
				} //end if
			};
			AlertDialog(html, fx, title);
			//--
			return false;
			//--
		} //end if else
		//--
		return true;
		//--
	}; //END
	_C$.GrowlNotificationAdd = GrowlNotificationAdd; // export

	/**
	 * Remove all Growl Notifications from the current browser window (page)
	 *
	 * @memberof smartJ$Browser
	 * @method GrowlNotificationRemove
	 * @static
	 *
	 * @return 	{Boolean} 					TRUE if Success FALSE if Fail
	 */
	const GrowlNotificationRemove = function() {
		//--
		const growlType = GrowlSelectType();
		//--
		switch(growlType) {
			case 'ui':
				smartJ$UI.GrowlRemove();
				return true;
				break;
			case 'toastr':
				$.toastr.clear();
				return true;
				break;
		} //end switch
		//--
		return false;
		//--
	}; //END
	_C$.GrowlNotificationRemove = GrowlNotificationRemove; // export

	/**
	 * Create a Browser Alert Dialog that will have just the OK button.
	 * It will detect if the UIDialog is available and will prefer to use it if set.
	 * Otherwise will try to detect if SimpleDialog is available and will use it if UIDialog is not available - fallback.
	 * If none of the above are available will try to use jQuery.alertable/alert else just display a simple alert() - fallback.
	 *
	 * @memberof smartJ$Browser
	 * @method AlertDialog
	 * @static
	 *
	 * @param 	{String} 	y_message 		The message to display (HTML)
	 * @param 	{JS-Code} 	evcode 			*Optional* the JS Code to execute on closing the alert / Dialog by pressing the OK button (the alert / Dialog can be closed only if OK button is clicked)
	 * @param 	{String} 	y_title 		*Optional* a title for the alert / Dialog (Plain Text)
	 * @param 	{Integer} 	y_width 		*Optional* the width of the Dialog (will be used just if UIDialog / SimpleDialog are detected) ; if not set the default width will be used
	 * @param 	{Integer} 	y_height 		*Optional* the height of the Dialog (will be used just if UIDialog / SimpleDialog are detected) ; if not set the default height will be used
	 * @param 	{String} 	dialogType 		*Optional* The Dialog Type ; default is 'auto'
	 * @param 	{Boolean} 	isTitleHtml 	*Optional* The Dialog Type ; default is FALSE ; If TRUE, will allow HTML Title
	 *
	 * @fires open an Alert Dialog (custom or default, depends on settings)
	 */
	const AlertDialog = function(y_message, evcode=null, y_title='', y_width=null, y_height=null, dialogType='auto', isTitleHtml=false) {
		//--
		const _m$ = 'AlertDialog';
		//--
		y_message = _Utils$.stringPureVal(y_message, true); // cast to string, trim
		y_title = _Utils$.stringPureVal(y_title, true); // cast to string, trim
		//--
		if(isTitleHtml === true) {
			// let as it is, it is supposed to be valid HTML
		} else {
			y_title = _Utils$.nl2br(_Utils$.escape_html(y_title)); // escape, it is plain text + NL2BR
		} //end if else
		//--
		if(dialogType === 'auto') {
			dialogType = DialogSelectType();
		} //end if
		//--
		if((dialogType === 'ui') && (typeof(smartJ$UI.DialogAlert) == 'function')) { // use UI Dialog (the best choice)
			//--
			smartJ$UI.DialogAlert(y_message, evcode, y_title, y_width, y_height);
			//--
		} else { // fallback to alertable or native browser alert
			//--
			const evErrMsg = 'ERR: JS-Eval Failed on DialogAlert';
			//--
			if((dialogType === 'alertable') && (typeof($.alertable) != 'undefined')) {
				//--
				$.alertable.alert((y_title ? '<div style="' + _Utils$.escape_html(cssAlertable) + '">' + y_title + '</div>' : '') + y_message, { html:true, width:((y_width && (y_width >= 275) && (y_width <= 960)) ? y_width : 550), height:((y_height && (y_height >= 100) && (y_height <= 720)) ? y_height : 270) }).always(() => { // use always not done to simulate real alert
					_Utils$.evalJsFxCode(_N$ + '.' + _m$ + ' (1)', evcode); // EV.NOCTX
				});
				//--
			} else { // native
				//--
				y_title = stripTags(y_title);
				y_message = stripTags(y_message);
				//--
				alert(y_title + '\n' + y_message);
				_Utils$.evalJsFxCode(_N$ + '.' + _m$ + ' (2)', evcode); // EV.NOCTX
				//--
			} //end if else
			//--
		} //end if else
		//--
	}; //END
	_C$.AlertDialog = AlertDialog;

	/**
	 * Create a Browser Confirm Dialog that will have 2 buttons: OK / Cancel.
	 * It will detect if the UIDialog is available and will prefer to use it if set.
	 * Otherwise will try to detect if SimpleDialog is available and will use it if UIDialog is not available - fallback.
	 * If none of the above are available will try to use jQuery.alertable/confirm else display a simple confirm() - fallback.
	 *
	 * @memberof smartJ$Browser
	 * @method ConfirmDialog
	 * @static
	 *
	 * @param 	{String} 	y_question 		The question / message to display (HTML)
	 * @param 	{JS-Code} 	evcode 			*Optional* the JS Code to execute on closing the confirm / Dialog by pressing the OK button (the confirm / Dialog can be closed by either OK button clicked or Cancel button clicked ; the code will be not executed on Cancel button click / close)
	 * @param 	{String} 	y_title 		*Optional* a title for the confirm / Dialog (HTML)
	 * @param 	{Integer} 	y_width 		*Optional* the width of the Dialog (will be used just if UIDialog / SimpleDialog are detected) ; if not set the default width will be used
	 * @param 	{Integer} 	y_height 		*Optional* the height of the Dialog (will be used just if UIDialog / SimpleDialog are detected) ; if not set the default height will be used
	 * @param 	{String} 	dialogType 		*Optional* The Dialog Type ; default is 'auto'
	 * @param 	{Boolean} 	isTitleHtml 	*Optional* The Dialog Type ; default is FALSE ; If TRUE, will allow HTML Title
	 *
	 * @fires open a Confirmation Dialog (custom or default, depends on settings)
	 */
	const ConfirmDialog = function(y_question, evcode=null, y_title='', y_width=null, y_height=null, dialogType='auto', isTitleHtml=false) { // ES6
		//--
		const _m$ = 'ConfirmDialog';
		//--
		y_question = _Utils$.stringPureVal(y_question, true); // cast to string, trim
		y_title = _Utils$.stringPureVal(y_title, true); // cast to string, trim
		//--
		if(isTitleHtml === true) {
			// let as it is, it is supposed to be valid HTML
		} else {
			y_title = _Utils$.nl2br(_Utils$.escape_html(y_title)); // escape, it is plain text + NL2BR
		} //end if else
		//--
		if(dialogType === 'auto') {
			dialogType = DialogSelectType();
		} //end if
		//--
		if((dialogType === 'ui') && (typeof(smartJ$UI.DialogConfirm) == 'function')) { // use UI Dialog (the best choice)
			//--
			smartJ$UI.DialogConfirm(y_question, evcode, y_title, y_width, y_height);
			//--
		} else { // fallback to alertable or native browser confirm dialog
			//--
			const evErrMsg = 'ERR: JS-Eval Failed on DialogConfirm';
			//--
			if((dialogType === 'alertable') && (typeof($.alertable) != 'undefined')) {
				//--
				$.alertable.confirm((y_title ? '<div style="' + _Utils$.escape_html(cssAlertable) + '">' + y_title + '</div>' : '') + y_question, { html:true, width:((y_width && (y_width >= 275) && (y_width <= 960)) ? y_width : 550), height:((y_height && (y_height >= 100) && (y_height <= 720)) ? y_height : 270) }).then(() => {
					_Utils$.evalJsFxCode(_N$ + '.' + _m$ + ' (1)', evcode); // EV.NOCTX
				});
				//--
			} else { // native
				//--
				y_title = stripTags(y_title);
				y_question = stripTags(y_question);
				//--
				let the_confirmation = confirm(y_title + '\n' + y_question);
				if(the_confirmation) {
					_Utils$.evalJsFxCode(_N$ + '.' + _m$ + ' (2)', evcode); // EV.NOCTX
				} //end if
				//--
			} //end if else
			//--
		} //end if else
		//--
	}; //END
	_C$.ConfirmDialog = ConfirmDialog; // export

	/**
	 * Create a Message (Dialog or Growl) Notification
	 *
	 * @memberof smartJ$Browser
	 * @method MessageNotification
	 * @static
	 *
	 * @param 	{String} 	ytitle 						The Title
	 * @param 	{String} 	ymessage 					The Message (HTML code)
	 * @param 	{String} 	yredirect 					*Optional* The URL to redirect
	 * @param 	{Yes/No} 	growl 						*Optional* If 'yes' will use the Growl notifications otherwise (default: if 'no') will use Dialog notification
	 * @param 	{Enum} 		class_growl 				*Optional* If Growl is used, a CSS class for Growl is required
	 * @param 	{Integer} 	timeout 					*Optional* If Growl is used, the Growl timeout in milliseconds
	 *
	 * @fires open a Notification Message (custom or default, depends on settings)
	 */
	const MessageNotification = function(ytitle, ymessage, yredirect, growl, class_growl, timeout) { // ES6
		//--
		yredirect = _Utils$.stringPureVal(yredirect, true); // cast to string, trim
		growl = _Utils$.stringPureVal(growl, true); // cast to string, trim
		timeout = _Utils$.format_number_int(timeout, false);
		//--
		if(growl === 'yes') {
			//--
			let redirectAfterClose = null;
			if((yredirect != undefined) && (yredirect != '')) { // undef tests also for null
				RedirectDelayedToURL(yredirect, timeout + 750); // let this just in case if growl fails to close because of user interraction ; a redirect is mandatory !
				redirectAfterClose = () => {
					RedirectDelayedToURL(String(yredirect), 250); // must be 250 here to close overlay before redirects
				};
			} //end if
			//--
			const growlOptions = {
				before_close: redirectAfterClose
			};
			//--
			GrowlNotificationAdd(ytitle, ymessage, '', timeout, false, class_growl, growlOptions);
			//--
		} else {
			//--
			let fx = null;
			if((yredirect != undefined) && (yredirect != '')) { // undef tests also for null
				fx = () => {
					RedirectDelayedToURL(yredirect, 100);
				};
			} //end if
			//--
			AlertDialog(ymessage, fx, ytitle, 550, 275);
			//--
		} //end if else
		//--
	}; //END
	_C$.MessageNotification = MessageNotification; // export

	/**
	 * Confirm a Form Submit by a confirm / Dialog, with OK and Cancel buttons.
	 * To Submit the form to the Modal/iFrame, the target name should be: smart__iFModalBox__iFrame.
	 * The form will be submitted just if OK button is clicked.
	 * @hint The function is using ConfirmDialog() and will detect and prefer in the specific order if UIDialog / SimpleDialog or just confirm() are available in Browser.
	 *
	 * @memberof smartJ$Browser
	 * @method confirmSubmitForm
	 * @static
	 *
	 * @param 	{String} 		y_confirm 		The question / message to display for confirmation (HTML)
	 * @param 	{Html-Form} 	y_form 			The HTML Form Object to bind to
	 * @param 	{String} 		y_target 		The window target to send the form to
	 * @param 	{Integer} 		windowWidth 	*Optional* the width of the new Modal/PopUp child window if new window target is used ; if not set the default width will be used
	 * @param 	{Integer} 		windowHeight 	*Optional* the height of the new Modal/PopUp child window if new window target is used ; if not set the default height will be used
	 * @param 	{Enum} 			forcePopUp 		*Optional* if a new target window is required, 0 = default, use modal/iFrame if set by smartJ$Browser.param_ModalBoxActive, 1 = force PopUp ; -1 force modal/iFrame
	 * @param	{Enum} 			forceDims 		*Optional* if set to 1 will try force set the width/height for the new modal/iFrame or PopUp
	 *
	 * @fires open a confirmation dialog
	 * @listens form submit event
	 */
	const confirmSubmitForm = function(y_confirm, y_form, y_target, windowWidth=0, windowHeight=0, forcePopUp=0, forceDims=0) { // ES6
		//--
		y_confirm = _Utils$.stringPureVal(y_confirm, true); // cast to string, trim
		//--
		if((y_form == undefined) || (y_form == '')) {
			//--
			_p$.error(_N$, 'confirmSubmitForm', 'ERR: Form Object is undefined');
			return;
			//--
		} //end if
		//--
		let submit_fx = () => { y_form.submit(); }; // by default we do just submit
		//--
		y_target = _Utils$.stringPureVal(y_target, true); // cast to string, trim
		if(y_target != '') {
			//--
			submit_fx = () => {
				PopUpSendForm(
					y_form, // object
					String(y_target), // string
					_Utils$.format_number_int(windowWidth, false), // int
					_Utils$.format_number_int(windowHeight, false), // int
					_Utils$.format_number_int(forcePopUp, false), // int
					_Utils$.format_number_int(forceDims, false) // int
				);
			};
			//--
		} //end if
		//--
		ConfirmDialog(y_confirm, submit_fx);
		//--
	}; //END
	_C$.confirmSubmitForm = confirmSubmitForm; // export

	/**
	 * Open a Modal/iFrame or PopUp child window with a new target to post a form within.
	 * To POST to the Modal/iFrame, the target name should be: smart__iFModalBox__iFrame.
	 * It will get the form URL and form method GET/POST directly from the objForm.
	 * The function must be called by a form button onClick followed by 'return false;' not by classic submit to avoid fire the form send twice 1st before (in a _blank window) and 2nd after opening the child popup/modal.
	 * @hint The function if used in a button with 'return false;' will catch the form send behaviour and will trigger it just after the child modal/iFrame or PopUp child window (new) target is opened and available.
	 *
	 * @memberof smartJ$Browser
	 * @method PopUpSendForm
	 * @static
	 *
	 * @param 	{Html-Form} 	objForm 		The HTML Form Object reference
	 * @param 	{String} 		strTarget 		The child window target to post the form to
	 * @param 	{Integer} 		windowWidth 	*Optional* the width of the new Modal/PopUp child window if new window target is used ; if not set the default width will be used
	 * @param 	{Integer} 		windowHeight 	*Optional* the height of the new Modal/PopUp child window if new window target is used ; if not set the default height will be used
	 * @param 	{Enum} 			forcePopUp 		*Optional* if a new target window is required, 0 = default, use modal/iFrame if set by smartJ$Browser.param_ModalBoxActive, 1 = force PopUp ; -1 force modal/iFrame
	 * @param	{Enum} 			forceDims 		*Optional* if set to 1 will try force uwing the width/height set for the new modal/iFrame or PopUp
	 * @param 	{JS-Code} 		evcode 			*Optional* the JS Code to execute after submit the form
	 *
	 * @fires send a form in a pop-up window to avoid loose the current page
	 * @listens form submit event
	 */
	const PopUpSendForm = function(objForm, strTarget, windowWidth=0, windowHeight=0, forcePopUp=0, forceDims=0, evcode=null) { // ES6
		//--
		const _m$ = 'PopUpSendForm';
		//--
		strTarget = _Utils$.stringPureVal(strTarget, true); // cast to string, trim
		//-- try to get the form action
		let strUrl = '';
		try {
			strUrl = String(objForm.action || ''); // ensure string and get form action
		} catch(err){
			_p$.error(_N$, _m$, 'ERR: Invalid Form Object');
			return;
		} //end try catch
		//-- if cross domain calls switching protocols http:// and https:// will be made will try to force pop-up to avoid XSS Error
		let crr_protocol = String(document.location.protocol);
		let crr_arr_url = strUrl.split(':');
		let crr_url = String(crr_arr_url[0]) + ':';
		//--
		if(
			((crr_protocol === 'http:') || (crr_protocol === 'https:')) &&
			((crr_url === 'http:') || (crr_url === 'https:')) &&
			(crr_url !== crr_protocol)
		) {
			forcePopUp = 1;
		} //end if
		//--
		objForm.target = strTarget; // normal popUp use
		if((((allowModalCascading()) && (forcePopUp != 1)) || (forcePopUp == -1)) && (allowModalBox())) {
			if(typeof(smartJ$ModalBox) != 'undefined') {
				objForm.target = smartJ$ModalBox.getName(); // use smart modal box
			} //end if else
		} //end if else
		//--
		initModalOrPopUp(_C$.param_LoaderBlank, objForm.target, windowWidth, windowHeight, forcePopUp, forceDims);
		//--
		let fx = () => { objForm.submit(); }; // by default we do just submit
		//--
		if((evcode != undefined) && (evcode != '') && (evcode != 'undefined')) { // test undefined is also for null
			fx = () => {
				objForm.submit();
				try {
					if(typeof(evcode) === 'function') {
						evcode(objForm, strTarget, forcePopUp, forceDims);
					} else {
						eval(evcode);
					} //end if else
				} catch(err) {
					_p$.error(_N$, _m$, 'ERR: Form After-Submit JS Code Failed:', err);
				} //end try catch
			};
		} //end if
		//--
		setTimeout(fx, _C$.param_TimeDelayCloseWnd + 500); // delay submit, required
		//--
		return false;
		//--
	}; //END
	_C$.PopUpSendForm = PopUpSendForm; // export

	/**
	 * Open a Modal/iFrame or PopUp child window with a new target to open a URL link within.
	 * @hint The function can be called by a button, a link or other HTML elements at onClick (if 'a' element onClick is used must be followed by 'return false;' to avoid fire page refresh.
	 *
	 * @memberof smartJ$Browser
	 * @method PopUpLink
	 * @static
	 *
	 * @param 	{String} 	strUrl 			The URL link to be opened in a new browser child window target or modal iframe (modalbox)
	 * @param 	{String} 	strTarget 		The child window target to post the form to ; name ; do not use _self or _blank here, and try ensure a compliant name
	 * @param 	{Integer} 	windowWidth 	*Optional* the width of the new Modal/PopUp child window if new window target is used ; if not set the default width will be used
	 * @param 	{Integer} 	windowHeight 	*Optional* the height of the new Modal/PopUp child window if new window target is used ; if not set the default height will be used
	 * @param 	{Enum} 		forcePopUp 		*Optional* if a new target window is required, 0 = default, use modal/iFrame if set by smartJ$Browser.param_ModalBoxActive, 1 = force PopUp ; -1 force modal/iFrame
	 * @param	{Enum} 		forceDims 		*Optional* if set to 1 will try force uwing the width/height set for the new modal/iFrame or PopUp
	 * @param 	{Enum}		handlerMode		*Optional* If explicit set to 1 or 2 will use only a simple modal/popup without binding to the parent window ; if set to 2, the popup will not use redirect handler in addition to no binding ; if set to -1 the popup will not use redirect handler, but will use binding ; if set to -2 (default) will use same domain detection
	 *
	 * @fires open a Modal Box or a PopUp Window
	 */
	const PopUpLink = function(strUrl, strTarget, windowWidth=0, windowHeight=0, forcePopUp=0, forceDims=0, handlerMode=-2) { // ES6
		//--
		strUrl = _Utils$.stringPureVal(strUrl, true); // cast to string, trim
		if(strUrl == '') {
			return;
		} //end if
		strUrl = String(strUrl || ''); // ensure string
		//--
		strTarget = _Utils$.stringPureVal(strTarget, true); // cast to string, trim
		//-- if cross domain calls between http:// and https:// will be made will try to force pop-up to avoid XSS Error
		const crr_protocol = String(document.location.protocol);
		const crr_arr_url = strUrl.split(':');
		const crr_url = crr_arr_url[0] + ':';
		//--
		if(
			((crr_protocol === 'http:') || (crr_protocol === 'https:')) &&
			((crr_url === 'http:') || (crr_url === 'https:')) &&
			(crr_url !== crr_protocol)
		) {
			forcePopUp = 1;
		} //end if
		//--
		let crrHostName = null;
		let tgtHostName = null;
		try {
			crrHostName = new URL(document.location).hostname;
			tgtHostName = new URL(strUrl).hostname;
		} catch(err){
			crrHostName = '';
			tgtHostName = '';
		}
		if((crrHostName != '') && (tgtHostName != '') && (crrHostName != tgtHostName)) { // if on different host
			if(handlerMode < -1) {
				handlerMode = 2; // on different domain don't use binding or redirect to avoid detect as a tricky script by browsers or SEO
			} //end if
		} //end if
	//	console.log('crrHostName', crrHostName);
	//	console.log('tgtHostName', tgtHostName);
		initModalOrPopUp(strUrl, strTarget, windowWidth, windowHeight, forcePopUp, forceDims, handlerMode);
		//--
		return false;
		//--
	}; //END
	_C$.PopUpLink = PopUpLink; // export

	/**
	 * Get a Cookie from Browser by Name and return it's Value
	 *
	 * @memberof smartJ$Browser
	 * @method getCookie
	 * @static
	 *
	 * @param 	{String} 	name 			The cookie Name ; must be a valid cookie name
	 * @return 	{String} 					The cookie Value
	 */
	const getCookie = function(name) { // ES6
		//--
		name = _Utils$.stringPureVal(name, true); // cast to string, trim
		if((name == '') || !(regexValidCookieName.test(name))) { // {{{SYNC-REGEX-URL-VARNAME}}}
			return null;
		} //end if
		//--
		let c;
		try {
			c = document.cookie.match(new RegExp('(^|;)\\s*' + _Utils$.preg_quote(String(name)) + '=([^;\\s]*)')); // Array
		} catch(err){
			c = [];
			_p$.error(_N$, 'ERR: getCookie(' + name + ') Failed:', err);
		} //end try catch
		//--
		if(c && c.length >= 3) {
			const d = decodeURIComponent(c[2] || '') || ''; // fix to avoid working with null !!
			return String(d);
		} //end if
		//--
		return ''; // fix to avoid working with null !!
		//--
	}; //END
	_C$.getCookie = getCookie; // export

	/**
	 * Set a Cookie in Browser by Name and Value
	 *
	 * @memberof smartJ$Browser
	 * @method setCookie
	 * @static
	 *
	 * @param 	{String} 	name 			The cookie Name
	 * @param 	{String} 	value 			The cookie Value
	 * @param	{Numeric} 	expire 			*Optional* The cookie expire time in seconds since now ; if set to zero will expire by session ; if set to -1 will be unset (deleted) ; if set to NULL will use the value from smartJ$Browser.param_CookieLifeTime ; default is 0
	 * @param 	{String} 	path 			*Optional* The cookie path (default is /)
	 * @param 	{String} 	domain 			*Optional* The cookie domain (default is @ and will use '@' to use the smartJ$Browser.param_CookieDomain)
	 * @param 	{Enum} 		samesite 		*Optional* The SameSite cookie policy ; Can be: None, Lax, Strict or Empty ; (default is @ and will use '@' to use the smartJ$Browser.param_CookieSameSitePolicy: Lax)
	 * @param 	{Boolean} 	secure 			*Optional* Force Cookie Secure Mode (default is FALSE)
	 * @return 	{Boolean} 					TRUE on success and FALSE on failure
	 */
	const setCookie = function(name, value, expire=0, path='/', domain='@', samesite='@', secure=false) { // ES6
		//--
		name = _Utils$.stringPureVal(name, true); // cast to string, trim
		if((name == '') || !(regexValidCookieName.test(name))) { // {{{SYNC-REGEX-URL-VARNAME}}}
			_p$.warn(_N$, 'WARN: setCookie: Invalid Cookie Name:', name);
			return false;
		} //end if
		//--
		value = _Utils$.stringPureVal(value, true); // cast to string, trim
		//--
		let d = new Date();
		//--
		if(expire === null) {
			expire = _C$.param_CookieLifeTime;
		} //end if
		expire = _Utils$.format_number_int(expire); // allow negatives
		if(expire) {
			if(expire > 0) { // set with an expire date in the future
				d.setTime(d.getTime() + (expire * 1000)); // now + (seconds)
			} else if(expire < 0) { // unset (set expire date in the past)
				d.setTime(d.getTime() - (3600 * 24) - expire); // now - (1 day) - (seconds)
				value = null; // unsetting a cookie needs an empty value
			} else { // expire by session
				expire = 0; // explicit set to zero
			} //end if else
		} else {
			expire = 0;
		} //end if
		if(!value) {
			value = null;
			expire = -1; // force compatibility with PHP and unset empty cookies
		} //end if
		//--
		path = _Utils$.stringPureVal(path, true); // cast to string, trim
		path = String(path || '/');
		//--
		domain = _Utils$.stringPureVal(domain, true); // cast to string, trim
		if(domain === '@') { // NULL is not in the case
			domain = String(_C$.param_CookieDomain);
		} //end if
		//--
		samesite = _Utils$.stringPureVal(samesite, true); // cast to string, trim
		if(samesite === '@') { // NULL is not in the case
			samesite = String(_C$.param_CookieSameSitePolicy);
		} //end if
		samesite = String(samesite).toLowerCase();
		switch(samesite) {
			case 'none':
				samesite = 'None';
				secure = true; // new browsers require it if SameSite cookie policy is set explicit to None !!
				break;
			case 'lax':
				samesite = 'Lax';
				break;
			case 'strict':
				samesite = 'Strict';
				break;
			case 'empty':
			default:
				samesite = '';
		} //end switch
		//--
		// IMPORTANT: a cookie with the HttpOnly attribute set cannot be set or accessed by javascript, so ignore it
		//--
		try {
			document.cookie = _Utils$.escape_url(name) + '=' + _Utils$.escape_url(value) + (expire ? ('; expires=' + d.toGMTString()) : '') + '; path=' + path + (domain ? ('; domain=' + domain) : '') + (samesite ? '; SameSite=' + samesite : '') + (secure ? '; secure' : '');
			return true;
		} catch(err){
			_p$.warn(_N$, 'WARN: Failed to setCookie:', err);
			return false;
		} //end try catch
		//--
	}; //END
	_C$.setCookie = setCookie; // export

	/**
	 * Delete a Cookie from Browser by Name
	 *
	 * @memberof smartJ$Browser
	 * @method deleteCookie
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	name 			The cookie Name
	 * @param 	{String} 	path 			*Optional* The cookie path (default is /)
	 * @param 	{String} 	domain 			*Optional* The cookie domain (default is @ and will use '@' to use the smartJ$Browser.param_CookieDomain)
	 * @param 	{Enum} 		samesite 		*Optional* The SameSite cookie policy ; Can be: None, Lax, Strict ; (default is @ and will use '@' to use the smartJ$Browser.param_CookieSameSitePolicy)
	 * @param 	{Boolean} 	secure 			*Optional* Force Cookie Secure Mode (default is FALSE)
	 * @return 	{Boolean} 					TRUE on success and FALSE on failure
	 */
	const deleteCookie = (name, path='/', domain='@', samesite='@', secure=false) => setCookie(name, null, -1, path, domain, samesite, secure); // ES6
	_C$.deleteCookie = deleteCookie; // export

	/**
	 * Force a text limit on a TextArea (will also attach a CounterField)
	 *
	 * @memberof smartJ$Browser
	 * @method TextAreaAddLimit
	 * @static
	 *
	 * @param 	{String} 	elemID 			The TextArea field name
	 * @param 	{Integer+} 	maxChars 		The max limit of characters to accept in the TextArea
	 *
	 * @fires limits the text area for input more than max allowed characters
	 * @listens typing text in a text area, paste or other input methods
	 */
	const TextAreaAddLimit = function(elemID, maxChars) { // ES6
		//--
		elemID = _Utils$.stringPureVal(elemID); // cast to string, don't trim ! need to preserve the value
		if(elemID == '') {
			_p$.error(_N$, 'TextAreaAddLimit', 'Empty Element ID');
			return;
		} //end if
		//--
		maxChars = _Utils$.format_number_int(maxChars, false);
		if(maxChars < 1) {
			_p$.warn(_N$, 'ERR: TextArea Add Limit: Invalid Limit, will reset to 1');
			maxChars = 1;
		} //end if
		//--
		$('#' + elemID).on('change click blur keydown keyup paste', (evt) => {
			//--
			const field = $(evt.currentTarget);
			if(field.val().length > maxChars) { // if too long then trim it!
				field.val(field.val().substring(0, maxChars));
			} //end if
			//--
			field.attr('title', '# Max: ' + maxChars + ' ; Chars: ' + (maxChars - field.val().length) + ' #'); // update the counter
			//--
		}).attr('title', '# Max: ' + maxChars + ' #').attr('maxlength', maxChars);
		//--
	}; //END
	_C$.TextAreaAddLimit = TextAreaAddLimit; // export

	/**
	 * Catch ENTER Key in an Input[type=text] or other compatible input field
	 * @hint Usage: onKeyDown="smartJ$Browser.catchKeyENTER(event);"
	 *
	 * @memberof smartJ$Browser
	 * @method catchKeyENTER
	 * @static
	 *
	 * @param 	{Event} 	evt 			The EVENT Object Reference
	 *
	 * @fires Catch and Disable use of ENTER key in a field
	 * @listens ENTER Key press event
	 *
	 * @example				<input type="text" onKeyDown="smartJ$Browser.catchKeyENTER(event);">
	 */
	const catchKeyENTER = function(evt) { // ES6
		//--
		if(!evt) {
			return true;
		} //end if
		//--
		let key = null;
		//--
		if(evt.key) {
			key = evt.key; // string
		} else if(evt.which) { // jQuery standard, deprecated
			key = evt.which; // numeric
		} else if(evt.keyCode) { // deprecated
			key = evt.keyCode; // numeric
		} //end if else # evt.code does not have native support in jQuery and the key codes are strings
		//--
		if((key === 'Enter') || (key == 13)) {
			evt.preventDefault();
			return false;
		} //end if
		//--
		return true;
		//--
	}; //END
	_C$.catchKeyENTER = catchKeyENTER;

	/**
	 * Catch TAB Key and insert a TAB character (\t) at the cursor instead to switch to next input field as default in html
	 * It works on TextArea or other compatible input fields ...
	 * @hint Usage: onKeyDown="smartJ$Browser.catchKeyTAB(event);"
	 *
	 * @memberof smartJ$Browser
	 * @method catchKeyTAB
	 * @static
	 *
	 * @param 	{Event} 	evt 			The EVENT Object Reference
	 *
	 * @fires Insert a real TAB character in the selected input
	 * @listens TAB Key press event
	 *
	 * @example				<textarea id="txt" onKeyDown="smartJ$Browser.catchKeyTAB(event);">
	 */
	const catchKeyTAB = function(evt) { // ES6
		//--
		if(!evt) {
			return;
		} //end if
		//--
		let key = null;
		if(evt.key) {
			key = evt.key; // string
		} else if(evt.which) { // jQuery standard, deprecated
			key = evt.which; // numeric
		} else if(evt.keyCode) { // deprecated
			key = evt.keyCode; // numeric
		} //end if else # evt.code does not have native support in jQuery and the key codes are strings
		//--
		if((key === 'Tab') || (key == 9)) {
			//-- Tab key - insert tab expansion
			evt.preventDefault();
			//-- defs
			const tab = "\t";
			const t = evt.target;
			const ss = t.selectionStart;
			const se = t.selectionEnd;
			const scrollTop = t.scrollTop;
			const scrollLeft = t.scrollLeft;
			//-- Special case of multi line selection
			if(ss != se && t.value.slice(ss,se).indexOf("\n") != -1) {
				//-- In case selection was not of entire lines (e.g. selection begins in the middle of a line) we have to tab at the beginning as well as at the start of every following line.
				const pre = t.value.slice(0,ss);
				const sel = t.value.slice(ss,se).replace(/\n/g,"\n"+tab);
				const post = t.value.slice(se,t.value.length);
				//--
				t.value = pre.concat(tab).concat(sel).concat(post);
				t.selectionStart = ss + tab.length;
				t.selectionEnd = se + tab.length;
			} else {
				//-- The Normal Case (no selection or selection on one line only)
				t.value = t.value.slice(0,ss).concat(tab).concat(t.value.slice(ss,t.value.length));
				if(ss == se) {
					t.selectionStart = t.selectionEnd = ss + tab.length;
				} else {
					t.selectionStart = ss + tab.length;
					t.selectionEnd = se + tab.length;
				} //end if
			} //end if else
			//--
			t.scrollTop = scrollTop;
			t.scrollLeft = scrollLeft;
			//--
		} //end if
		//--
	}; //END
	_C$.catchKeyTAB = catchKeyTAB; // export

	/**
	 * Check or Uncheck all checkboxes in a form element
	 *
	 * @memberof smartJ$Browser
	 * @method CheckAllCheckBoxes
	 * @static
	 *
	 * @param 	{String} 	y_form_name 			The form name (if empty string will operate on all checkboxes in the page otherwise just inside the form)
	 * @param 	{String} 	y_element_id 			The checkboxes element ID
	 * @param 	{Boolean} 	y_element_checked 		If TRUE will do check ; If FALSE will do uncheck ; otherwise will just inverse check
	 *
	 * @fires check/uncheck all the checkboxes inside a form
	 */
	const CheckAllCheckBoxes = function(y_form_name, y_element_id, y_element_checked) { // ES6
		//--
		let selector = 'body';
		if(y_form_name) {
			selector = 'form[name="' + _Utils$.escape_html(y_form_name) + '"]';
		} //end if
		selector += ' input[type="checkbox"]';
		//--
		let checked = ! y_element_checked;
		$(selector).each((i, el) => {
			const $el = $(el);
			checked ? $el.prop('checked', false) : $el.prop('checked', true); // $el.is(':checked');
		});
		//--
		return false;
		//--
	}; //END
	_C$.CheckAllCheckBoxes = CheckAllCheckBoxes; // export

	/**
	 * Clone a HTML Element
	 *
	 * @example
	 * <div id="multifile_list" style="text-align:left; max-width:550px;">
	 * 		<input id="multifile_uploader" type="file" name="myvar[]" style="width:90%;">
	 * </div>
	 * <script>
	 * 		smartJ$Browser.CloneElement('multifile_uploader', 'multifile_list', 'file-input', 10);
	 * </script>
	 *
	 * @memberof smartJ$Browser
	 * @method CloneElement
	 * @static
	 *
	 * @param 	{String} 	elID 					The element ID to be cloned
	 * @param 	{String} 	containerID 			The destination container ID
	 * @param 	{Enum} 		elType 					The type of the element to be cloned: text-input ; text-area ; file-input ; html-element
	 * @param 	{Integer} 	maxLimit 				The max limit number of elements includding the element itself and the cloned elements: between 1 and 255
	 */
	const CloneElement = function(elID, containerID, elType, maxLimit) { // ES6
		//--
		const _m$ = 'CloneElement';
		//--
		elID = _Utils$.stringPureVal(elID, true); // cast to string, trim
		elID = _Utils$.create_htmid(elID);
		if(elID == '') { // undef tests also for null
			_p$.error(_N$, _m$, 'ERR: Invalid elID');
			return;
		} //end if
		//--
		containerID = _Utils$.stringPureVal(containerID, true); // cast to string, trim
		containerID = _Utils$.create_htmid(containerID);
		if(containerID == '') { // undef tests also for null
			_p$.error(_N$, _m$, 'ERR: Invalid containerID');
			return;
		} //end if
		//--
		maxLimit = _Utils$.format_number_int(maxLimit, false);
		if(maxLimit < 1) {
			maxLimit = 1; // min limit
		} else if(maxLimit > 255) {
			maxLimit = 255; // hardcoded max limit
		} //end if
		//-- init
		const $el = $('#' + elID);
		const control_num = _Utils$.format_number_int(parseInt($('body').find('[id^=' + 'clone_control__' + elID + ']').length), false);
		if(control_num <= 0) {
			$el.before('<img id="' + 'clone_control__' + _Utils$.escape_html(elID) + '" alt="AddNew" title="Add New (Max: ' + _Utils$.escape_html(maxLimit) + ')" height="16" src="' + _Utils$.escape_html(_C$.param_ImgCloneInsert) + '" style="cursor:pointer; vertical-align:middle;" onClick="smartJ$Browser.CloneElement(\'' + _Utils$.escape_js(elID) + '\', \'' + _Utils$.escape_js(containerID) + '\', \'' + _Utils$.escape_js(elType) + '\', ' + _Utils$.format_number_int(maxLimit) + ');' + '">&nbsp;&nbsp;</span>');
			return;
		} //end if
		//-- do clone
		let cloned_num = _Utils$.format_number_int(_Utils$.format_number_int($('body').find('[id^=' + 'clone_of__' + elID + '_' + ']').length), false);
		if(cloned_num <= 0) {
			cloned_num = 0;
		} //end if
		if(cloned_num >= (maxLimit - 1)) {
			return;
		} //end if
		//--
		const date = new Date();
		const seconds = date.getTime();
		const milliseconds = date.getMilliseconds();
		const randNum = Math.random().toString(36);
		const uuID = _Crypto$Hash.sha1('The UUID for #' + cloned_num + ' @ ' + randNum + ' :: ' + seconds + '.' + milliseconds);
		//--
		const $clone = $el.clone().attr('id', 'clone_of__' + elID + '_' + _Utils$.create_htmid(uuID));
		//--
		$('#' + containerID).append('<div class="cloned__' + _Utils$.escape_html(elID) + '" id="' + 'clone_container__' + _Utils$.escape_html(elID) + '_' + _Utils$.escape_html(uuID) + '"><img alt="Remove" title="Remove" height="16" src="' + _Utils$.escape_html(_C$.param_ImgCloneRemove) + '" style="cursor:pointer; vertical-align:middle;" onClick="jQuery(this).parent().remove();">&nbsp;&nbsp;</div>');
		//--
		switch(String(elType)) {
			case 'text-input':
			case 'text-area':
			case 'file-input':
				$clone.val('').appendTo('#' + 'clone_container__' + elID + '_' + _Utils$.create_htmid(uuID));
				break;
			case 'html-element': // regular html element
			default: // other cases
				$clone.appendTo('#' + 'clone_container__' + elID + '_' + _Utils$.create_htmid(uuID));
		} //end switch
		//--
	}; //END
	_C$.CloneElement = CloneElement; // export

	/**
	 * Serialize Form as Object
	 *
	 * @memberof smartJ$Browser
	 * @method SerializeFormAsObject
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	formID 					The element ID of the Form to be serialized
	 * @param 	{String} 	rKey 					The object root key ; Optional ; if not specified will be set as 'data' ; '#' is the object metainfo
	 * @return 	{Object} 	 						Serialized data: ex: { "#":{ "levels": 2, "keys":6, "size":11}, "data":{...} }
	 */
	const SerializeFormAsObject = (formID, rKey) => {
		//-- r.20241226.2358
		formID = _Utils$.stringPureVal(formID, true);
		formID = _Utils$.create_htmid(formID);
		if(formID == '') {
			return null;
		} //end if
		//--
		rKey = _Utils$.stringPureVal(rKey, true);
		if(rKey == '') {
			rKey = 'formData';
		} //end if
		//--
		let a = $('form#' + formID).serializeArray();
		if(!Array.isArray(a)) {
			return {};
		} //end if
		if(a.length <= 0) {
			return {};
		} //end if
		if(a.length > 16384) {
			return {};
		} //end if
		let o = {};
		let i = 0;
		let l = 0; // array levels
		$.each((a), (idx, val) => {
			let n = _Utils$.stringTrim(val.name);
			let z = '';
			let isTypeArr = false;
			let isTypeObj = false;
			if(n != '') {
				if(_Utils$.stringEndsWith(n, '[]')) { // []
					isTypeArr = true;
					if(n.length > 2) {
						n = n.substring(0, n.length-2);
					} else {
						n = '';
					} //end if
				} else if(_Utils$.stringEndsWith(n, ']')) { // [key]
					isTypeObj = true;
					if(n.length > 3) {
						let at = n.split('[')
						n = String(at[0] || '');
						z = _Utils$.stringCharTrim(String(at[1] || ''), ']');
					} else {
						n = '';
					} //end if
				} //end if
			} //end if
			n = _Utils$.stringTrim(n);
			if(n != '') {
				const v = val.value || '';
				if(!!isTypeArr) { // arr
					if(o[n] !== undefined) {
						if(!o[n].push) {
							o[n] = [o[n]];
						} //end if
						o[n].push(v);
						l = Math.max(l, o[n].length); // 2..n levels
					} else {
						o[n] = [v];
						l = Math.max(l, 1); // 1st level
						i++; // increment keys just on 1st level
					} //end if else
				} else if((!!isTypeObj) && (z != '')) { // obj
					if(o[n] !== undefined) {
						o[n][z] = v;
						// do not increment here levels or keys after object initialization
					} else {
						o[n] = {};
						o[n][z] = v;
						l = Math.max(l, 1); // 1st level only
						i++; // increment keys just on 1st level
					} //end if else
				} else {
					o[n] = v; // scalar value
					i++; // increment keys for any scalar value
				} //end if else
			} //end if
		});
		const obj = {};
		obj['#'] = { levels: l, keys: i, size: a.length }; // levels are the max sub-key levels
		obj[rKey] = o;
		return obj;
	};
	_C$.SerializeFormAsObject = SerializeFormAsObject; // export

	/**
	 * Submit a Form by Ajax via POST and Handle the Answer
	 * The function expects a json answer with the following structure: see more in framework PHP lib SmartComponents::js_ajax_replyto_html_form()
	 * @example
	 * // Json Structure for Answer
	 * 		{
	 * 			'completed': 	'DONE',
	 * 			'status': 		'OK|ERROR',
	 * 			'action': 		'Notification Button Text: Ok/Cancel',
	 * 			'title': 		'Notification Title',
	 * 			'message': 		'Notification Message HTML Content', // if empty, on success will not show any notification
	 * 			'js_evcode': 	'If non-empty, the JS Code to execute on either SUCCESS or ERROR (before redirect or Div Replace) ; params: the_form_id, url, msg'
	 * 			'redirect': 	'If non-empty, a redirect URL on either SUCCESS or ERROR ; on SUCCESS if message is Empty will redirect without confirmation: Growl / Dialog',
	 * 			'replace_div': 	'If non-empty, an ID for a div to be replaced with content from [replace_html] on Success',
	 * 			'replace_html': 'If non-empty, a HTML code to populate / replace the current Content for the [replace_div] on Success'
	 * 		}
	 * // #END Json Structure
	 * @hint It supports simple forms or complex forms with multipart/form-data and file attachments.
	 *
	 * @memberof smartJ$Browser
	 * @method SubmitFormByAjax
	 * @static
	 *
	 * @param 	{String} 	the_form_id 			The form ID ; Set it to FALSE/NULL/'' (to use without a real form by emulating a form like XHR request from URL)
	 * @param 	{String} 	url 					The destination URL where form or XHR request will be sent to
	 * @param 	{Yes/No} 	growl 					*Optional* If 'yes' will use the Growl notifications otherwise (if 'no') will use Dialog notifications ; default is set to 'auto'
	 * @param 	{JS-Code} 	evcode 					*Optional* the JS Code to execute on SUCCESS answer (before anything else) ; params: the_form_id, url, msg
	 * @param 	{JS-Code} 	everrcode 				*Optional* the JS Code to execute on ERROR answer (before anything else) ; params: the_form_id, url, msg
	 * @param 	{JS-Code} 	evfailcode 				*Optional* the JS Code to execute on REQUEST FAIL answer ; params: the_form_id, url, msg
	 * @param 	{Boolean} 	failalertable 			*Optional* if set to TRUE will set the fail dialog to 'alertable' instead of 'auto' if alertable is available
	 *
	 * @fires send a form by ajax
	 * @listens fire a form submit
	 */
	const SubmitFormByAjax = function(the_form_id, url, growl='auto', evcode=null, everrcode=null, evfailcode=null, failalertable=false) { // ES6
		//--
		const _m$ = 'SubmitFormByAjax';
		//--
		growl = _Utils$.stringPureVal(growl, true); // cast to string, trim
		if(growl == '') {
			growl = 'auto';
		} else if(growl != 'no') {
			growl = 'yes';
		} //end if
		//--
		let dlg = 'auto';
		if(failalertable === true) {
			if($.alertable) {
				dlg = 'alertable'; // use alertable instead of dialog
			} //end if
		} //end if
		//--
		let haveGrowl = false;
		switch(growl) {
			case 'no':
				break;
			case 'yes':
				if(GrowlSelectType() != '') {
					haveGrowl = true;
				} else {
					_p$.warn(_N$, _m$, 'WARN: Growl is required but is N/A');
				} //end if
				break;
			case 'auto':
			default:
				if(GrowlSelectType() != '') {
					haveGrowl = true;
				} //end if
		} //end switch
		//--
		let ajax = null;
		if(!the_form_id) { // false, null or ''
			ajax = AjaxRequestFromURL(url, 'GET', 'json');
		} else {
			ajax = AjaxRequestByForm(the_form_id, url, 'json');
		} //end if else
		if(ajax === null) {
			_p$.error(_N$, _m$, 'ERR: Null XHR Object on FormID:', the_form_id);
			AlertDialog('<h3>FAIL: The XHR Object is NULL !</h3>', null, 'ERROR', null, null, dlg);
			return;
		} //end if
		//--
		OverlayShow();
		//--
		ajax.done((msg) => { // {{{JQUERY-AJAX}}}
			//--
			OverlayClear();
			//--
			if((typeof(msg) == 'object') && (msg.hasOwnProperty('completed')) && (msg.completed === 'DONE') && (msg.hasOwnProperty('status')) && (msg.status != null) && (msg.hasOwnProperty('action')) && (msg.action != null) && (msg.hasOwnProperty('title')) && (msg.title != null) && (msg.hasOwnProperty('message')) && (msg.message != null) && (msg.hasOwnProperty('js_evcode')) && (msg.hasOwnProperty('redirect')) && (msg.hasOwnProperty('replace_div')) && (msg.hasOwnProperty('replace_html'))) {
				//--
				msg.status = _Utils$.stringPureVal(msg.status, true).toUpperCase();
				if(msg.status == 'OK') { // OK
					//--
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$ + ' (1.1)',
						(typeof(evcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(evcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
							}
						)
					);
					//--
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$ + ' (1.2)',
						(typeof(msg.js_evcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(msg.js_evcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! msg.js_evcode ? eval(msg.js_evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
							}
						)
					);
					//--
					if((msg.hasOwnProperty('hide_form_on_success')) && (!!msg.hide_form_on_success)) {
						if(!!the_form_id) {
							$('form#' + the_form_id).hide(); // {{{SYNC-FORM-SELECTOR-BY-ID}}}
						} //end if
					} //end if
					//--
					if((msg.redirect != null) && (msg.redirect != '') && (msg.message == '')) { // if no message handle redirect here
						RedirectDelayedToURL(msg.redirect, 250);
					} else { // if message, the redirect will be handled by MessageNotification on closing dialog or growl
						if((msg.replace_div != null) && (msg.replace_div != '') && (msg.replace_html != null) && (msg.replace_html != '')) {
							$('#' + msg.replace_div).empty().html(String(msg.replace_html));
						} //end if
						if(msg.message != '') {
							MessageNotification(String(msg.title || ''), '<img alt="ok" title="' + _Utils$.escape_html(msg.action) + '" src="' + _Utils$.escape_html(_C$.param_ImgOK) + '" align="right">' + msg.message, msg.redirect, (haveGrowl === true) ? 'yes' : 'no', (haveGrowl === true) ? 'success' : '', _C$.param_NotificationTimeOK);
						} //end if
						setTimeout(() => { OverlayHide(); }, _C$.param_NotificationTimeOK + 100);
					} //end if
					//--
				} else { // ERROR, WARN, NOTIFY
					//--
					let growlClass = ''; // UNKNOWN
					switch(msg.status) {
						case 'INFO':
							growlClass = 'info';
							break;
						case 'HINT':
							growlClass = 'hint';
							break;
						case 'NOTICE':
							growlClass = 'notice';
							break;
						case 'WARN':
						case 'WARNING':
							growlClass = 'warning';
							break;
						case 'ERR':
						case 'ERROR':
							growlClass = 'error';
							break;
						case 'FAIL':
						case 'FAILED':
							growlClass = 'fail';
							break;
					} //end switch
					//--
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$ + ' (2.1)',
						(typeof(everrcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(everrcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! everrcode ? eval(everrcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
							}
						)
					);
					//--
					_Utils$.evalJsFxCode( // EV.CTX
						_N$ + '.' + _m$ + ' (2.2)',
						(typeof(msg.js_evcode) === 'function' ?
							() => {
								'use strict'; // req. strict mode for security !
								(msg.js_evcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict'; // req. strict mode for security !
								!! msg.js_evcode ? eval(msg.js_evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
							}
						)
					);
					//-- on error, the redirect will only be handled by MessageNotification on closing dialog or growl, does not count if have message or not, an error must be display
					MessageNotification('* ' + String(msg.title || ''), String(msg.message), msg.redirect, (haveGrowl === true) ? 'yes' : 'no', (haveGrowl === true) ? growlClass : '', _C$.param_NotificationTimeERR);
					setTimeout(() => { OverlayHide(); }, _C$.param_NotificationTimeERR + 100);
					//--
				} //end if else
				//--
			} else {
				//--
				_p$.warn(_N$, _m$, 'WARN: Invalid Data Object Format');
				MessageNotification('* ERR', '<img alt="fail" src="' + _Utils$.escape_html(_C$.param_ImgNotOK) + '" align="right">' + 'Invalid Form Submit Response: Unexpected Data Object Format', null, (haveGrowl === true) ? 'yes' : 'no', (haveGrowl === true) ? 'fail' : '', _C$.param_NotificationTimeERR);
				setTimeout(() => { OverlayHide(); }, _C$.param_NotificationTimeERR + 500);
				//--
				_Utils$.evalJsFxCode( // EV.CTX
					_N$ + '.' + _m$ + ' (3)',
					(typeof(evfailcode) === 'function' ?
						() => {
							'use strict'; // req. strict mode for security !
							(evfailcode)(the_form_id, url, msg);
						} :
						() => {
							'use strict'; // req. strict mode for security !
							!! evfailcode ? eval(evfailcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
						}
					)
				);
				//--
			} //end if else
			//--
		}).fail((msg) => {
			//--
			OverlayClear();
			//--
			AlertDialog(
				'<h4>HTTP&nbsp;Status: ' + _Utils$.escape_html(msg.status) + ' ' + _Utils$.escape_html(HTTP_STATUS_CODES[String(msg.status)] ? HTTP_STATUS_CODES[String(msg.status)] : (msg.statusText ? msg.statusText : 'Unknown')) + '</h4><br>' + '\n' + '<h5>XHR Server Response NOT Validated ...</h5>' + ((msg.status == 401) ? '<h6>If the auth credentials are valid and still getting this message do a full page refresh in the browser and try again, it could be a re-auth issue !</h6>' : ''), // + '\n' + msg.responseText (... not safe to display responseText, it may contain unattended javascripts and external css styles)
				(typeof(evfailcode) === 'function' ?
					() => {
						'use strict'; // req. strict mode for security !
						(evfailcode)(the_form_id, url, msg);
					} :
					() => {
						'use strict'; // req. strict mode for security !
						!! evfailcode ? eval(evfailcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: the_form_id, url, msg
					}
				),
				'FAIL',
				null,
				null,
				dlg
			);
			setTimeout(() => { OverlayHide(); }, _C$.param_NotificationTimeERR + 1000);
			//--
		});
		//--
	}; //END
	_C$.SubmitFormByAjax = SubmitFormByAjax; // export

	/**
	 * Create an Ajax XHR Request (POST) by Form
	 * It supports simple forms or complex forms with multipart/form-data and file attachments.
	 *
	 * @memberof smartJ$Browser
	 * @method AjaxRequestByForm
	 * @static
	 *
	 * @param 	{String} 	the_form_id 			The element ID of the form to be create the Ajax XHR Request for
	 * @param 	{String} 	url 					The URL to send form to via POST
	 * @param 	{Enum} 		data_type 				The type of Data served back by the Request: json | html | text
	 * @return 	{Object} 							The Ajax XHR Request Object ; The following methods must be bind to the object and redefined:
	 *
	 * @example
	 * let ajax = smartJ$Browser.AjaxRequestByForm('my-form', 'http(s);//url-to', 'json')
	 * 		.done: 		function(msg) {}
	 * 		.fail: 		function(msg) {}
	 * 		.always: 	function(msg) {}
	 */
	const AjaxRequestByForm = function(the_form_id, url, data_type) { // ES6
		//--
		const _m$ = 'AjaxRequestByForm';
		//--
		the_form_id = _Utils$.stringPureVal(the_form_id, true); // cast to string, trim
		url = _Utils$.stringPureVal(url, true); // cast to string, trim
		//--
		let $form = null;
		let ajax = null;
		let data = '';
		//--
		if(the_form_id != '') {
			$form = $('form#' + the_form_id); // {{{SYNC-FORM-SELECTOR-BY-ID}}}
			if(typeof($form.get(0)) == 'undefined') {
				_p$.error(_N$, _m$, 'ERR: Form [' + the_form_id + '] was not found');
				return null;
			} //end if
			if(url == '') { // undef tests also for null
				url = _Utils$.stringPureVal($form.attr('action'), true); // try to get form action if URL is empty ; cast to string, trim
			} //end if
		} //end if
		if((url == '') || (url == '#')) {
			_p$.error(_N$, _m$, 'ERR: Invalid or Empty URL');
			return null;
		} //end if
		//--
		if(the_form_id == '') {
			//--
			ajax = AjaxRequestFromURL(url, 'GET', data_type);
			//--
		} else {
			//--
			const fMethod = _Utils$.stringPureVal($form.attr('method'), true); // cast to string, trim
			const fEncType = _Utils$.stringPureVal($form.attr('enctype'), true); // cast to string, trim
			let isMultipart = false;
			if((fMethod.toLowerCase() == 'post') && (fEncType.toLowerCase() == 'multipart/form-data')) {
				let haveFiles = $form.find('input:file');
				if(typeof(haveFiles) != 'undefined') {
					if(typeof(haveFiles.get(0)) != 'undefined') {
						isMultipart = true; // have files
					} //end if
				} //end if
			} //end if
			//--
			if(isMultipart !== true) {
				//--
				data = $form.serialize(); // no files detected use serialize
				ajax = AjaxRequestFromURL(url, 'POST', data_type, data);
				//--
			} else {
				//--
				try {
					//--
					data = new FormData($form.get(0)); // data.append('__fix', '...multipart form fix for arr vars...'); // workarround for IE10/11 bugfix with array variables, after array of vars a non-array var must be to avoid corruption: http://blog.yorkxin.org/posts/2014/02/06/ajax-with-formdata-is-broken-on-ie10-ie11/
					ajax = AjaxPostMultiPartToURL(url, data_type, data);
					//--
				} catch(err) { // it must alert to anounce the user
					//--
					data = $form.serialize(); // no files detected use serialize
					ajax = AjaxRequestFromURL(url, 'POST', data_type, data);
					//--
					const warnMsg = 'ERR: ' + _m$ + ' FormData Object Failed. File Attachments were NOT sent ! Try to upgrade / change your browser. It may not support HTML5 File Uploads.';
					_p$.error(_N$, warnMsg, ' # Form:', the_form_id);
					AlertDialog(_Utils$.escape_html(warnMsg), null, 'Form: ' + the_form_id);
					//--
				} //end try catch
				//--
			} //end if else
			//--
		} //end if
		//--
		return ajax;
		//--
		/* the below functions must be assigned later to avoid execution here {{{SYNC-JQUERY-AJAX-EVENTS}}}
		let ajax = smartJ$Browser.AjaxRequestByForm(...)
		ajax.done(function(data, textStatus, jqXHR) {}); // instead of .success() (which is deprecated or removed from newest jQuery)
		ajax.fail(function(jqXHR, textStatus, errorThrown) {}); // instead of .error() (which is deprecated or removed from newest jQuery)
		ajax.always(function(data|jqXHR, textStatus, jqXHR|errorThrown) {}); // *optional* instead of .complete() (which is deprecated or removed from newest jQuery)
		*/
		//--
	}; //END
	_C$.AjaxRequestByForm = AjaxRequestByForm; // export

	/**
	 * Create an Ajax XHR Request (POST) using multipart/form-data type to bse used with file attachments.
	 * Instead using it directly is better to use:
	 * 		smartJ$Browser.AjaxRequestByForm(); 		// basic, will detect the form type if must use multipart/form-data + attachments (if any)
	 * or even much better use:
	 * 		smartJ$Browser.SubmitFormByAjax(); 	// advanced, will handle the XHR form request and the answer
	 * @hint NOTICE: It does not send the 'contentType' to allow new FormData send multipart form if necessary ...
	 *
	 * @private : internal development only
	 *
	 * @memberof smartJ$Browser
	 * @method AjaxPostMultiPartToURL
	 * @static
	 *
	 * @param 	{String} 	y_url 						The URL to send form to via method POST
	 * @param 	{Enum} 		y_data_type 				The type of Data served back by the Request: json | jsonp | script | html | xml | text
	 * @param 	{Mixed} 	y_data_formData 			The *special* serialized form data as object using: new FormData(jQuery('#'+the_form_id).get(0)) to support attachments or string via serialize() such as: '&var1=value1&var2=value2'
	 * @param 	{String} 	y_AuthUser 					*Optional* The Authentication UserName (if custom Authentication need to be used) ; set to FALSE to avoid accidental auto-send, if unused, for CORS or 3rd party domains (security !)
	 * @param 	{String} 	y_AuthPass 					*Optional* The Authentication Password (if custom Authentication need to be used) ; set to FALSE to avoid accidental auto-send, if unused, for CORS or 3rd party domains (security !)
	 * @param 	{Object} 	y_Headers 					*Optional* Extra Headers to be sent with the Request ; Default is NULL (ex: { 'X-Head1': 'Test1', ... })
	 * @param 	{Boolean} 	y_withCredentials 			*Optional* Send Credentials over CORS ; default is FALSE ; set to TRUE to send XHR Credentials ; !!! Never set this when URL is a 3rd party domain to avoid leak credentials ; this can be set to TRUE just when exchanging requests between different sub-domains of the same domain !
	 * @param 	{Boolean} 	y_crossDomain 				*Optional* when set to TRUE will handle a CROSS Domain Request (y_withCredentials is forced to FALSE in this context) ; default is FALSE
	 * @return 	{Object} 								The Ajax XHR Request Object ; The following methods must be bind to the object and redefined:
	 *
	 * @example
	 * 		.done: 		function(msg) {}
	 * 		.fail: 		function(msg) {}
	 * 		.always: 	function(msg) {}
	 */
	const AjaxPostMultiPartToURL = function(y_url, y_data_type, y_data_formData, y_AuthUser=null, y_AuthPass=null, y_Headers=null, y_withCredentials=false, y_crossDomain=false) { // ES6
		//--
		const _m$ = 'AjaxPostMultiPartToURL';
		//--
		y_url = _Utils$.stringPureVal(y_url, true); // cast to string, trim
		if((y_url == '') || (y_url == '#')) {
			_p$.error(_N$, _m$, 'ERR: Empty URL');
			return null;
		} //end if
		//--
		y_data_type = getValidjQueryAjaxType(y_data_type);
		//--
		let ajxOpts = { // not const, below will change
			//--
			async: 				true,
		//	cache: 				false, // by default is true ; let it be set globally via ajaxSetup
		//	timeout: 			0, // by default is zero ; let it be set globally via ajaxSetup
			type: 				'POST',
			url: 				String(y_url),
			//--
			contentType: 		false,
			processData: 		false,
			//--
			data: 				y_data_formData, // String or Object
			dataType: 			String(y_data_type), // json, jsonp, script, html, xml or text
			//--
		};
		//--
		if(!!y_Headers && (typeof(y_Headers) == 'object')) {
			ajxOpts.headers = y_Headers; // set only if explicit set
		} //end if
		//--
		if(y_AuthUser === false && y_AuthPass === false) { // if explicit set to false, both, set as empty to avoid auto-send them (ex: CORS or 3rd party domains ...)
			y_AuthUser = '';
			y_AuthPass = '';
			ajxOpts.username = '';
			ajxOpts.password = '';
		} else {
			y_AuthUser = _Utils$.stringPureVal(y_AuthUser, true); // cast to string, trim
			y_AuthPass = _Utils$.stringPureVal(y_AuthPass); // cast to string, do not trim
			if(
				(y_crossDomain === true) // if cross domain request, make sure either have auth credentials either reset them to avoid leaks
				||
				(!!y_AuthUser && !!y_AuthPass)
			) { // don't pass if empty ; if works as sandboxed will fail to auto-send auth in admin/task environments ; set ONLY if explicit set
				ajxOpts.username = String(y_AuthUser);
				ajxOpts.password = String(y_AuthPass);
			} //end if
		} //end if else
		//--
		if(y_crossDomain === true) { // this option is for external domains only !!
			y_withCredentials = false;
			ajxOpts.crossDomain = true;
		} //end if
		//--
		if(y_withCredentials === true) {
			ajxOpts.xhrFields = {
				withCredentials: true // allow send credentials (CORS): FALSE / TRUE
			};
		} //end if
		//--
		return $.ajax(ajxOpts);
		//--
		/* the below functions can be assigned later to avoid execution here {{{SYNC-JQUERY-AJAX-EVENTS}}}
		let ajax = smartJ$Browser.AjaxPostMultiPartToURL(...)
		ajax.done(function(data, textStatus, jqXHR) {}); // instead of .success() (which is deprecated or removed from newest jQuery)
		ajax.fail(function(jqXHR, textStatus, errorThrown) {}); // instead of .error() (which is deprecated or removed from newest jQuery)
		ajax.always(function(data|jqXHR, textStatus, jqXHR|errorThrown) {}); // *optional* instead of .complete() (which is deprecated or removed from newest jQuery)
		*/
		//--
	}; //END
	_C$.AjaxPostMultiPartToURL = AjaxPostMultiPartToURL; // export

	/**
	 * Create a general purpose Ajax XHR Request (GET/POST) with Optional support for Authentication and Extra Headers
	 * For creating an Ajax XHR Request to be used with HTML Forms use:
	 * 		smartJ$Browser.AjaxRequestByForm(); 		// basic, will detect the form type if must use multipart/form-data + attachments (if any)
	 * or even much better use:
	 * 		smartJ$Browser.SubmitFormByAjax(); 	// advanced, will handle the XHR form request and the answer
	 * @hint It is NOT intended to be used with HTML forms that may contain multipart/form-data and file attachments or not.
	 *
	 * @memberof smartJ$Browser
	 * @method AjaxRequestFromURL
	 * @static
	 *
	 * @param 	{String} 	y_url 						The URL to send the Request to
	 * @param 	{Enum} 		y_method 					The Request Method: HEAD / GET / POST / PUT / DELETE / OPTIONS / HEAD
	 * @param 	{Enum} 		y_data_type 				The type of Data served back by the Request: json | jsonp | script | html | xml | text
	 * @param 	{Mixed} 	y_data_arr_or_serialized 	The Data to be sent: a serialized string via serialize() such as: '&var1=value1&var2=value2' or an associative array (Object) as: { var1: "value1", var2: "value2" }
	 * @param 	{String} 	y_AuthUser 					*Optional* The Authentication UserName (if custom Authentication need to be used) ; set to FALSE to avoid accidental auto-send, if unused, for CORS or 3rd party domains (security !)
	 * @param 	{String} 	y_AuthPass 					*Optional* The Authentication Password (if custom Authentication need to be used) ; set to FALSE to avoid accidental auto-send, if unused, for CORS or 3rd party domains (security !)
	 * @param 	{Object} 	y_Headers 					*Optional* Extra Headers to be sent with the Request ; Default is NULL (ex: { 'X-Head1': 'Test1', ... })
	 * @param 	{Boolean} 	y_withCredentials 			*Optional* Send Credentials over CORS ; default is FALSE ; set to TRUE to send XHR Credentials ; !!! Never set this when URL is a 3rd party domain to avoid leak credentials ; this can be set to TRUE just when exchanging requests between different sub-domains of the same domain !
	 * @param 	{Boolean} 	y_crossDomain 				*Optional* when set to TRUE will handle a CROSS Domain Request (y_withCredentials is forced to FALSE in this context) ; default is FALSE
	 * @return 	{Object} 								The Ajax XHR Request Object ; The following methods must be bind to the object and redefined:
	 *
	 * @example
	 * let ajax = smartJ$Browser.AjaxRequestFromURL('http(s);//url', 'POST', 'json', '&var1=value1&var2=value2')
	 * 		.done: 		function(msg) {}
	 * 		.fail: 		function(msg) {}
	 * 		.always: 	function(msg) {}
	 */
	const AjaxRequestFromURL = function(y_url, y_method, y_data_type, y_data_arr_or_serialized, y_AuthUser=null, y_AuthPass=null, y_Headers=null, y_withCredentials=false, y_crossDomain=false) { // ES6
		//--
		const _m$ = 'AjaxRequestFromURL';
		//--
		y_url = _Utils$.stringPureVal(y_url, true); // cast to string, trim
		if(y_url == '') {
			_p$.error(_N$, _m$, 'ERR: Empty URL');
			return null;
		} //end if
		//--
		y_method = _Utils$.stringPureVal(y_method, true).toUpperCase(); // cast to string, trim + uppercase
		switch(y_method) {
			case 'OPTIONS':
			case 'DELETE':
			case 'PATCH':
			case 'PUT':
			case 'POST':
			case 'HEAD':
			case 'GET':
				break;
			default:
				y_method = 'GET';
		} //end switch
		//--
		y_data_type = getValidjQueryAjaxType(y_data_type);
		//--
		if(typeof(y_data_arr_or_serialized) != 'object') {
			y_data_arr_or_serialized = _Utils$.stringPureVal(y_data_arr_or_serialized); // cast to string, do not trim
		} //end if
		//--
		let ajxOpts = {
			//--
			async: 				true,
		//	cache: 				false, // by default is true ; let it be set globally via ajaxSetup
		//	timeout: 			0, // by default is zero ; let it be set globally via ajaxSetup
			type: 				String(y_method),
			url: 				String(y_url),
			//--
			data: 				y_data_arr_or_serialized, // it can be a serialized STRING as: '&var1=value1&var2=value2' or OBJECT as: { var1: "value1", var2: "value2" }
			dataType: 			String(y_data_type) // json, jsonp, script, html, xml or text
			//--
		};
		//--
		if(!!y_Headers && (typeof(y_Headers) == 'object')) {
			ajxOpts.headers = y_Headers; // set only if explicit set
		} //end if
		//--
		if(y_AuthUser === false && y_AuthPass === false) { // if explicit set to false, both, set as empty to avoid auto-send them (ex: CORS or 3rd party domains ...)
			y_AuthUser = '';
			y_AuthPass = '';
			ajxOpts.username = '';
			ajxOpts.password = '';
		} else {
			y_AuthUser = _Utils$.stringPureVal(y_AuthUser, true); // cast to string, trim
			y_AuthPass = _Utils$.stringPureVal(y_AuthPass); // cast to string, do not trim
			if(
				(y_crossDomain === true) // if cross domain request, make sure either have auth credentials either reset them to avoid leaks
				||
				(!!y_AuthUser && !!y_AuthPass)
			) { // don't pass if empty ; if works as sandboxed will fail to auto-send auth in admin/task environments ; set ONLY if explicit set
				ajxOpts.username = String(y_AuthUser);
				ajxOpts.password = String(y_AuthPass);
			} //end if
		} //end if else
		//--
		if(y_crossDomain === true) { // this option is for external domains only !!
			y_withCredentials = false;
			ajxOpts.crossDomain = true;
		} //end if
		//--
		if(y_withCredentials === true) {
			ajxOpts.xhrFields = {
				withCredentials: true // allow send credentials (CORS): FALSE / TRUE
			};
		} //end if
		//--
		return $.ajax(ajxOpts);
		//--
		/* [Sample Implementation:] {{{SYNC-JQUERY-AJAX-EVENTS}}}
		let ajax = smartJ$Browser.AjaxRequestFromURL(...);
		// {{{JQUERY-AJAX}}} :: the below functions: done() / fail() / always() must be assigned on execution because they are actually executing the ajax request and the AjaxRequestFromURL() just creates the request object !
		ajax.done(function(data, textStatus, jqXHR) { // instead of .success() (which is deprecated or removed from newest jQuery)
			// code for done
		}).fail(function(jqXHR, textStatus, errorThrown) { // instead of .error() (which is deprecated or removed from newest jQuery)
			// code for fail
		}).always(function(data|jqXHR, textStatus, jqXHR|errorThrown) { // *optional* instead of .complete() (which is deprecated or removed from newest jQuery)
			// code for always
		});
		*/
		//--
	}; //END
	_C$.AjaxRequestFromURL = AjaxRequestFromURL; // export

	/**
	 * Loads the contents for a Div (or other compatible) HTML Element(s) by Ajax using a GET / POST Ajax Request
	 * @hint It is intended to simplify populating a Div (or other compatible) HTML Element(s) with content(s) by Ajax Requests.
	 *
	 * @memberof smartJ$Browser
	 * @method LoadElementContentByAjax
	 * @static
	 *
	 * @param 	{String} 	y_elemID 					The ID of the Div (or other compatible) HTML Element(s) to bind to
	 * @param 	{String} 	y_img_loader 				If non-empty, a pre-loader image that will be displayed while loading ; if set to TRUE will use smartJ$Browser.param_LoaderImg;
	 * @param 	{String} 	y_url 						The URL to send the Request to
	 * @param 	{Enum} 		y_method 					The Request Method: GET / POST
	 * @param 	{Enum} 		y_data_type 				The type of Data served back by the Request: html | text | json (div_content_html)
	 * @param 	{Mixed} 	y_data_arr_or_serialized 	The Data to be sent: a serialized string via serialize() such as: '&var1=value1&var2=value2' or an associative array as: { var1: "value1", var2: "value2" }
	 * @param 	{Boolean} 	y_replace 					*Optional* Default is FALSE ; If set to TRUE will use the jQuery method .replaceWith() instead of .empty().html()
	 * @param 	{Mixed} 	y_notifyerr 				*Optional* Default is NULL ; If set to TRUE or FALSE will override the general setting for notification error ; if TRUE on Error loading the content will display a message Dialog instead of logging the errros to console
	 *
	 * @fires load the div content by ajax in the background and if successful will replace the div content with what comes by ajax
	 */
	const LoadElementContentByAjax = function(y_elemID, y_img_loader, y_url, y_method, y_data_type, y_data_arr_or_serialized, y_replace=false, y_notifyerr=null) {
		//--
		const _m$ = 'LoadElementContentByAjax';
		//--
		y_elemID = _Utils$.stringPureVal(y_elemID, true); // cast to string, trim
		y_elemID = _Utils$.create_htmid(y_elemID);
		if(y_elemID == '') {
			_p$.error(_N$, _m$, 'ERR: Empty Element ID');
			return;
		} //end if
		//--
		if(y_img_loader === true) {
			y_img_loader = _C$.param_LoaderImg;
		} //end if
		y_img_loader = _Utils$.stringPureVal(y_img_loader, true); // cast to string, trim
		if(y_img_loader != '') {
			if($('#' + y_elemID + '__' + _Utils$.create_htmid(_m$)).length == 0) {
				$('#' + y_elemID).prepend('<span id="' + _Utils$.escape_html(y_elemID) + '__' + _Utils$.create_htmid(_m$) + '"><img src="' + _Utils$.escape_html(y_img_loader) + '" title="Loading ..." alt="Loading ..."></span><br>');
			} //end if
		} //end if
		//--
		let ajax = AjaxRequestFromURL(y_url, y_method, y_data_type, y_data_arr_or_serialized);
		//--
		ajax.done((msg) => { // {{{JQUERY-AJAX}}}
			let divContent = '';
			if(y_data_type === 'json') {
				if(msg.hasOwnProperty('div_content_html')) {
					divContent = String(msg.div_content_html);
				} //end if
			} else { // text | html
				divContent = String(msg);
			} //end if else
			if(y_replace === true) {
				$('#' + y_elemID).replaceWith(divContent);
			} else {
				$('#' + y_elemID).empty().html(divContent);
			} //end if else
		}).fail((msg) => {
			let notifyErr = !! _C$.param_NotifyLoadError;
			if((y_notifyerr === true) || (y_notifyerr === false)) {
				notifyErr = !! y_notifyerr;
			} //end if
			const errDetails = 'ElementID: ' + y_elemID + ' ; ' + 'URL: ' + y_url + ' ; ' + 'Method: ' + y_method + ' ; ' + 'DataType: ' + y_data_type;
			if(notifyErr === true) {
				GrowlNotificationAdd('HTTP Status: ' + msg.status + ' ' + HTTP_STATUS_CODES[String(msg.status)] ? HTTP_STATUS_CODES[String(msg.status)] : (msg.statusText ? msg.statusText : 'Unknown'), 'FAIL: Invalid Server Response for: ' + _Utils$.escape_html(_m$) + '<br>' + '<b><i>Details:</i></b><br>' + _Utils$.nl2br(_Utils$.escape_html(_Utils$.stringReplaceAll(' ; ', '\n', errDetails))), _C$.param_ImgNotOK, 0, true, 'dark'); // , msg.responseText
			} else {
				_p$.error(_N$, _m$, 'ERR: Invalid Server Response', 'HTTP Status Code:', msg.status, ';', errDetails); // , msg.responseText
			} //end if else
			$('#' + y_elemID).empty().html(''); // clear
		});
		//--
	}; //END
	_C$.LoadElementContentByAjax = LoadElementContentByAjax;

	/**
	 * Scroll down a browser window by reference.
	 * It will focus the referenced browser window first.
	 *
	 * @memberof smartJ$Browser
	 * @method windwScrollDown
	 * @static
	 *
	 * @param 	{Object} 	wnd 		The window (reference) object
	 * @param 	{Integer} 	offset 		The offset in pixels to scroll down ; use -1 to scroll to the end of document
	 *
	 * @fires Scroll Down a Browser window
	 */
	const windwScrollDown = function(wnd, offset) { // ES6
		//--
		let scrollY = _Utils$.format_number_int(parseInt(offset));
		if(scrollY < 0) { // if offset is -1 will go to end
			scrollY = _Utils$.format_number_int(parseInt($(document).height()));
			if(scrollY < 0) {
				scrollY = 0;
			} //end if
		} //end if
		//--
		try {
			wnd.scrollBy(0, scrollY);
		} catch(err){} // just in case
		//--
	}; //END
	_C$.windwScrollDown = windwScrollDown; // export

	/**
	 * Trigger a function when you scroll the page to a specific target element
	 * @hint It may be used to make an infinite scroll behaviour to load more content on page when page reach the bottom end
	 *
	 * @memberof smartJ$Browser
	 * @method WayPoint
	 * @static
	 *
	 * @param 	{String} 	elSelector 					The Element Selector ; example: '#elem-id' or '.elem-class' (interpretable by jQuery)
	 * @param 	{JS-Code} 	evcode 						the JS Code to execute on complete
	 *
	 * @fires trigger the function that is set (2nd param)
	 * @listens the target element (1st param) to be scrolled in the visible area
	 */
	const WayPoint = function(elSelector, evcode) {
		//--
		const _m$ = 'WayPoint';
		//--
		elSelector = _Utils$.stringPureVal(elSelector, true); // cast to string, trim
		if((elSelector == '') || (elSelector == '.') || (elSelector == '#')) {
			_p$.error(_N$, _m$, 'ERR: invalid selector:', elSelector);
			return;
		} //end if
		if(typeof(evcode) !== 'function') {
			evcode = _Utils$.stringPureVal(evcode); // cast to string
			if(_Utils$.stringTrim(evcode) == '') { // undef tests also for null
				_p$.error(_N$, _m$, 'ERR: empty evcode');
				return;
			} //end if
		} //end if
		//--
		let scrollTrigger = true;
		const $w = $(window);
		//--
		$(window).scroll(() => {
			//--
			if(!scrollTrigger) {
				return;
			} //end if
			//--
			const $e = $(String(elSelector));
			if((!$e.length) || $e.is(':hidden')) {
				return false;
			} //end if
			//--
			const wt = $w.scrollTop(),
				wb = wt + $w.height(),
				et = $e.offset().top,
				eb = et + $e.height(),
				th = 0; // treshold
			const shouldLoad = (eb >= wt - th && et <= wb + th);
			//--
			if(shouldLoad) { // scrolled to the target
				//--
				scrollTrigger = false;
				//--
				_Utils$.evalJsFxCode( // EV.CTX
					_N$ + '.' + _m$,
					(typeof(evcode) === 'function' ?
						() => {
							'use strict'; // req. strict mode for security !
							(evcode)($e, elSelector);
						} :
						() => {
							'use strict'; // req. strict mode for security !
							!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: $e, elSelector
						}
					)
				);
				//--
			} //end if
			//--
		});
		//--
	}; //END
	_C$.WayPoint = WayPoint; // export

	/**
	 * Copy Element Text Content to ClipBoard
	 *
	 * @memberof smartJ$Browser
	 * @method copyToClipboard
	 * @static
	 * @arrow
	 *
	 * @fires copy text element by using ranges
	 *
	 * @param 	{String} 	elId 		The element ID
	 *
	 * @return 	{String} 				Error or Empty String
	 */
	const copyToClipboard = (elId) => {
		//--
		elId = _Utils$.stringPureVal(elId, true);
		if(elId == '') {
			return String('ERR: No ID');
		} //end if
		//--
		const w = window;
		const d = document;
		//--
		let elem;
		//--
		try {
			elem = d.getElementById(elId);
		} catch(err) {
			return String('ERR: ' + err);
		} //end try catch
		//--
		try {
			const range = d.createRange();
			range.selectNode(elem);
			w.getSelection().removeAllRanges();
			w.getSelection().addRange(range);
			d.execCommand('copy');
			setTimeout(() => { w.getSelection().removeAllRanges(); }, 250);
		} catch(err) {
			return String('ERR: ' + err);
		} //end try catch
		//--
		return '';
		//--
	};
	_C$.copyToClipboard = copyToClipboard; // export

	/**
	 * Create a virtual file download from Javascript
	 *
	 * @memberof smartJ$Browser
	 * @method VirtualFileDownload
	 * @static
	 *
	 * @param 	{String} 	data 						The content of the file to be downloaded
	 * @param 	{String} 	fileName 					The file name (ex: 'file.txt')
	 * @param 	{String} 	mimeType 					The mime type (ex: 'text/plain')
	 * @param 	{String} 	charset 					*Optional* ; Charset ; Default is NULL, will use the value from param_Charset or if not set will use 'UTF-8' ; set it to FALSE if binary is set to TRUE ; The character set of the file content (ex: 'UTF-8' for text OR FALSE for binary ASCII)
	 * @param 	{Boolean} 	isBinary 					*Optional* ; Encoding Charset mode ; Default is FALSE ; set to TRUE if the string is binary ASCII to avoid normalize as UTF-8 ; for normal, UTF-8 content this must be set to FALSE
	 *
	 * @fires emulates a file download
	 */
	const VirtualFileDownload = function(data, fileName, mimeType, charset=null, isBinary=false) {
		//--
		const _m$ = 'VirtualFileDownload';
		//--
		data = _Utils$.stringPureVal(data); // cast to string, don't trim ! need to preserve the value
		//--
		fileName = _Utils$.stringPureVal(fileName, true); // cast to string, trim
		if(!fileName) {
			fileName = 'file.none';
		} //end if
		//--
		mimeType = _Utils$.stringPureVal(mimeType, true); // cast to string, trim
		if(!mimeType) {
			mimeType = 'application/octet-stream';
		} //end if
		//--
		if(charset === null) {
			charset = String(_C$.param_Charset);
		} //end if else
		charset = _Utils$.stringPureVal(charset, true); // cast to string, trim
		//--
		if(isBinary === true) {
			charset = '';
		} else {
			isBinary = false;
			if(charset == '') { // fallback
				charset = 'UTF-8'; // default
			} //end if
		} //end if
		//--
		let link = null;
		try {
			link = document.createElement('a');
			if(link) {
				link.href = String('data:' + String(mimeType) + (charset ? ';charset=' + charset : '') + ';base64,' + _Utils$.b64Enc(data, isBinary));
				link.target = '_blank';
				link.setAttribute('download', String(fileName));
				document.body.appendChild(link);
				link.style = 'display:none';
				link.click();
			} //end if
		} catch(err) {
			link = false;
			_p$.error(_N$, _m$, 'ERR: Create Failed:', err);
		} //end try catch
		//--
		if(link) {
			setTimeout(() => {
				try {
					document.body.removeChild(link);
				} catch(e) {
					_p$.warn(_N$, _m$, 'WARN: Remove Failed:', e);
				} //end try catch
			}, 250);
		} //end if
		//--
	}; //END
	_C$.VirtualFileDownload = VirtualFileDownload; // export

	/**
	 * Create a virtual image upload handler
	 * Requires in HTML:
	 * 		(#1) an input type file (can be any of visible or hidden/triggered by a button click)
	 * 		(#2) an image preview container (div)
	 *
	 * @memberof smartJ$Browser
	 * @method VirtualImageUploadHandler
	 * @static
	 *
	 * @param 	{String} 	inputUpldFileId 			The HTML-Id of the input type file (#1)
	 * @param 	{String} 	previewPlaceholderId 		The HTML-Id of the preview container (#2)
	 * @param 	{Decimal} 	imgQuality 					The image quality (0.1 ... 1)
	 * @param 	{Decimal} 	imgMaxResizeMBSize 			The max resized image size in MB (0.01 ... 2) ; Default is 0.1
	 * @param 	{Integer} 	imgMaxResizeW 				The max resize width of the image (if the image width is lower will be not resized)
	 * @param 	{Integer} 	imgMaxResizeH 				The max resize height of the image (if the image height is lower will be not resized)
	 * @param 	{CallBack} 	fxDone 						A callback function(imgDataURL, w, h, isSVG, type, size, name) { ... } for done
	 * @param 	{Boolean} 	clearPreview 				If FALSE will not clear the preview container after done; Default is TRUE
	 * @param 	{Integer} 	widthPreview 				The Preview Width to set if not clearing the preview ; if not defined, full resized image width will be used (imgMaxResizeW)
	 * @param 	{Integer} 	heightPreview 				The Preview Height to set if not clearing the preview ; if not defined, full resized image height will be used (imgMaxResizeH)
	 * @param 	{Boolean} 	preserveGifs 				If TRUE will not process GIFS (which will be converted to PNG if processed via canvas) ; it may be used to avoid break animated Gifs
	 */
	const VirtualImageUploadHandler = function(inputUpldFileId, previewPlaceholderId, imgQuality, imgMaxResizeMBSize, imgMaxResizeW, imgMaxResizeH, fxDone, clearPreview, widthPreview, heightPreview, preserveGifs) { // ES6
		//--
		const _m$ = 'VirtualImageUploadHandler';
		const txtWarn = 'Image Uploader WARNING: ';
		//--
		inputUpldFileId = _Utils$.stringPureVal(inputUpldFileId, true); // cast to string, trim
		inputUpldFileId = _Utils$.create_htmid(inputUpldFileId);
		if(inputUpldFileId == '') {
			_p$.error(_N$, _m$, 'ERR:', 'Invalid uploadInput ID');
			return;
		} //end if
		//--
		previewPlaceholderId = _Utils$.stringPureVal(previewPlaceholderId, true); // cast to string, trim
		previewPlaceholderId = _Utils$.create_htmid(previewPlaceholderId);
		if(previewPlaceholderId == '') {
			_p$.error(_N$, _m$, 'ERR:', 'Invalid uploadPreview ID');
			return;
		} //end if
		//--
		if(!imgQuality) {
			imgQuality = 0.85; // jpeg quality set at 0.85 by default, if not specified
		} //end if
		imgQuality = _Utils$.format_number_float(imgQuality);
		if(imgQuality < 0.1) {
			imgQuality = 0.1;
		} else if(imgQuality > 1) {
			imgQuality = 1;
		} //end if else
		//--
		if(!imgMaxResizeMBSize) {
			imgMaxResizeMBSize = 0.1;
		} //end if
		imgMaxResizeMBSize = _Utils$.format_number_float(imgMaxResizeMBSize);
		if(imgMaxResizeMBSize < 0.01) {
			imgMaxResizeMBSize = 0.01; // min 0.01 MB
		} else if(imgMaxResizeMBSize > 2) {
			imgMaxResizeMBSize = 2; // max 2MB
		} //end if else
		//--
		imgMaxResizeW = _Utils$.format_number_int(imgMaxResizeW);
		if(imgMaxResizeW <= 0) {
			imgMaxResizeW = 800;
		} //end if
		//--
		imgMaxResizeH = _Utils$.format_number_int(imgMaxResizeH);
		if(imgMaxResizeH <= 0) {
			imgMaxResizeH = 600;
		} //end if
		//--
		if(clearPreview !== false) {
			clearPreview = true;
		} //end if
		//--
		widthPreview = _Utils$.format_number_int(widthPreview);
		if(widthPreview <= 0) {
			widthPreview = imgMaxResizeW;
		} //end if
		//--
		heightPreview = _Utils$.format_number_int(heightPreview);
		if(heightPreview <= 0) {
			heightPreview = heightPreview;
		} //end if
		//--
		if(preserveGifs !== true) {
			preserveGifs = false;
		} //end if
		//--
		if(typeof(fxDone) != 'function') {
			fxDone = null;
		} //end if
		//--
		const $upldr = $('#' + inputUpldFileId);
		const $imgpw = $('#' + previewPlaceholderId);
		//--
		$upldr.on('change', () => {
			//--
			const the_file = $upldr[0].files[0]; // object
			if(!the_file) {
				$upldr.val('');
				_p$.error(_N$, _m$, 'ERR:', 'Invalid File Uploader');
				return;
			} //end if
			//--
			let the_name_of_file = String(the_file.name || '');
			let the_type_of_file = String(the_file.type || '').toLowerCase();
			let the_size_of_file = _Utils$.format_number_int(the_file.size, false);
			//--
			const the_filter = /^(image\/svg\+xml|image\/webp|image\/jpeg|image\/png|image\/gif)$/i;
			if(!the_filter.test(the_type_of_file)) { // check file type
				$upldr.val('');
				$imgpw.text(txtWarn + 'Invalid File Type - Only SVG / JPEG / WEBP / PNG or GIF Images are allowed): ' + the_type_of_file);
				return;
			} //end if
			//--
			/* it has been fixed between Firefox 78 and 91, now works and WEBP is a standard ! It should work in all browsers ...
			if(the_type_of_file == 'image/webp') {
				if(!window.chrome) { // TODO: Remove it after Firefox can handle canvas.toDataURL("image/webp") ; currently there is a bug in Firefox that instead producing a webp image it falls back to png and gives data:image/png;base64 https://bugzilla.mozilla.org/show_bug.cgi?id=1559743
					the_type_of_file = 'image/jpeg'; // {{{SYNC-JS-CANVAS-CANNOT-HANDLE-WEBP}}} ; fix: currently only Chrome supports image/webp for Canvas DataURL ; https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement/toDataURL
				} //end if
			} //end if
			*/
			//--
			if(the_size_of_file < 43) { // check file size: min WEBP or JPEG size is 134 bytes ; min PNG size is unknown ; min GIF size is 43 bytes
				$upldr.val('');
				$imgpw.text(txtWarn + 'Invalid File Size - Image size is empty or too small');
				return;
			} else if(the_size_of_file > (1024 * 1024 * 32)) { // check uploaded max file size (<32MB)
				$upldr.val('');
				$imgpw.text(txtWarn + 'Invalid File Size - Image is larger than 32MB');
				return;
			} //end if
			//--
			const imgRemoveBtn = '<div title="Remove the Image" style="width:20px; height:20px; line-height:20px; font-size:15px; font-weight:bold; color:#111111; cursor:pointer;" onClick="jQuery(\'#' + _Utils$.escape_js(inputUpldFileId) + '\').val(\'\'); jQuery(\'#' + _Utils$.escape_js(previewPlaceholderId) + '\').html(\'\');">&times;</div>';
			//--
			$imgpw.html(imgRemoveBtn);
			//--
			try {
				//--
				const the_frd = new FileReader();
				//--
				the_frd.onloadend = () => {
					$imgpw.append('<img id="uxm-img-uploader-result-img" src="' + _Utils$.escape_html(the_frd.result) + '" style="max-width:' + _Utils$.escape_html(imgMaxResizeW) + 'px; max-height:' + _Utils$.escape_html(imgMaxResizeH) + 'px; width:auto !important; height:auto !important;">');
					setTimeout(() => {
							const isSVG = (String(the_type_of_file).toLowerCase() === 'image/svg+xml') ? true : false;
							const img = $('#uxm-img-uploader-result-img');
							let isOK = false;
							let w = Math.round(img.width()) || 1;
							let h = Math.round(img.height()) || 1;
							let isPreservedGif = false;
							if(preserveGifs === true) {
								if(the_type_of_file === 'image/gif') {
									isPreservedGif = true;
								} //end if
							} //end if
							//_p$.log(_N$, _m$, 'Metadata:', w, h, isSVG);
							if(isSVG || isPreservedGif) {
								if(String(the_frd.result).length <= (1024 * 1024 * imgMaxResizeMBSize)) {
									isOK = true;
									if(typeof(fxDone) == 'function') {
										try {
											fxDone(String(the_frd.result), w, h, isSVG, String(the_type_of_file), String(the_frd.result).length, String(the_name_of_file));
										} catch(e) {
											$upldr.val('');
											_p$.error(_N$, _m$, 'ERR:', 'SVG CallBack Failed:', e);
											return;
										} //end try catch
									} //end if
								} else {
									$upldr.val('');
									$imgpw.text(txtWarn + 'Size is higher than allowed size: ' + String(the_frd.result).length + ' Bytes');
								} //end if else
								if(isOK) {
									$imgpw.empty().html('');
									if(!clearPreview) {
										$imgpw.html(String(imgRemoveBtn) + "\n" + '<img id="uxm-img-uploader-result-img" src="' + _Utils$.escape_html(the_frd.result) + '" style="max-width:' + _Utils$.escape_html(widthPreview) + 'px; max-height:' + _Utils$.escape_html(heightPreview) + 'px; width:auto !important; height:auto !important;">');
									} //end if
								} //end if
							} else {
								$('#uxm-img-uploader-result-img').remove();
								$imgpw.append('<canvas id="uxm-img-uploader-result-cnvs" width="' + _Utils$.escape_html(w) + '" height="' + _Utils$.escape_html(h) + '" style="border: 1px dotted #ECECEC;"></canvas>');
								const im = new Image();
								im.width = w;
								im.height = h;
								im.onload = () => {
									const cnv = $('#uxm-img-uploader-result-cnvs')[0];
									if(!cnv) {
										$upldr.val('');
										_p$.error(_N$, _m$, 'ERR:', 'Failed to Get Resizable Container');
										return;
									} //end if
									const ctx = cnv.getContext('2d');
									if(!ctx) {
										$upldr.val('');
										_p$.error(_N$, _m$, 'ERR:', 'Failed to Get Resizable Container Context');
										return;
									} //end if
									//ctx.fillStyle = '#FFFFFF'; // make sense just for image/jpeg
									//ctx.fillRect(0, 0, w, h); // make sense just for image/jpeg
									try {
										ctx.drawImage(im, 0, 0, w, h);
									} catch(imgerr) {
										$upldr.val('');
										_p$.error(_N$, _m$, 'ERR:', 'Failed to Draw Canvas Image:', imgerr);
										return;
									} //end try catch
									const imgResizedB64 = cnv.toDataURL(String(the_type_of_file), imgQuality); // preserve file type ; set image quality ... just for jpeg right now ...
									//_p$.log(_N$, _m$, 'Before fxDone' + the_frd.result.length, 'After: ' + imgResizedB64.length);
									if(String(imgResizedB64).length <= (1024 * 1024 * imgMaxResizeMBSize)) {
										isOK = true;
										if(typeof(fxDone) == 'function') {
											try {
												fxDone(String(imgResizedB64), w, h, false, String(the_type_of_file), String(imgResizedB64).length, String(the_name_of_file));
											} catch(e) {
												$upldr.val('');
												_p$.error(_N$, _m$, 'ERR:', 'IMG CallBack Failed:', e);
												return;
											} //end try catch
										} //end if
										if(isOK) {
											$imgpw.empty().html('');
											if(!clearPreview) {
												$imgpw.html(String(imgRemoveBtn) + "\n" + '<img id="uxm-img-uploader-result-img" src="' + _Utils$.escape_html(imgResizedB64) + '" style="max-width:' + _Utils$.escape_html(widthPreview) + 'px; max-height:' + _Utils$.escape_html(heightPreview) + 'px; width:auto !important; height:auto !important;">');
											} //end if
										} //end if
									} else {
										$upldr.val('');
										$imgpw.text(txtWarn + 'Image Size after resize is higher than allowed size: ' + String(imgResizedB64).length + ' Bytes');
										return;
									} //end if else
								};
								im.src = String(the_frd.result);
							} //end if else
						},
						500 // timeout
					);
				}; //end =>
				//--
				try {
					the_frd.readAsDataURL(the_file);
				} catch(fail) {
					$upldr.val('');
					_p$.error(_N$, _m$, 'ERR: Failed to Read Data URL:', fail);
					return;
				} //end try catch
				//--
			} catch(err){
				//--
				$upldr.val('');
				_p$.error(_N$, _m$, 'ERR:', err);
				return;
				//--
			} //end try catch
			//--
		});
		//--
	}; //END
	_C$.VirtualImageUploadHandler = VirtualImageUploadHandler; // export

	/**
	 * Converts a Date Field to a Smart Date Field
	 * Requires in HTML: an input type date that will display ISO yyyy-mm-dd date instead as browser locales as mm/dd/yyyy.
	 *
	 * @memberof smartJ$Browser
	 * @method DateSmartField
	 * @static
	 * @arrow
	 *
	 * @param 	{String} 	elem 						The jQuery element selector to apply this method to ; expects: `<input type="date">`
	 */
	const DateSmartField = (elem) => {
		//--
		const _m$ = 'DateSmartField';
		//--
		if(elem.length <= 0) {
			_p$.warn(_N$, _m$, 'Element is Empty');
			return;
		} //end if
		try {
			const $elem = $(elem);
			if(($elem.length <= 0) || ($elem.is('input') != true) || ($elem.attr('type') != 'date')) {
				_p$.warn(_N$, _m$, 'Element is Not an Input type Date');
				return;
			} //end if
			//--
			const min = _Utils$.stringPureVal($elem.attr('min'), true);
			const max = _Utils$.stringPureVal($elem.attr('max'), true);
			const step = _Utils$.stringPureVal($elem.attr('step'), true);
			$elem.attr('data-min', min).attr('data-max', max).attr('data-step', step).attr('placeholder', 'yyyy-mm-dd');
			$elem.removeAttr('step').removeAttr('max').removeAttr('min').attr('type', 'text');
			$elem.prop('readonly', true);
			//--
			$elem.on('click', (evt) => {
				const $tgt = $(evt.currentTarget);
				const dataMin = _Utils$.stringPureVal($tgt.attr('data-min'), true);
				const dataMax = _Utils$.stringPureVal($tgt.attr('data-max'), true);
				const dataStep = _Utils$.stringPureVal($tgt.attr('data-step'), true);
				$tgt.prop('readonly', false);
				$tgt.attr('type', 'date').attr('min', dataMin).attr('max', dataMax).attr('step', dataStep);
			}).on('blur', (evt) => {
				const $tgt = $(evt.currentTarget);
				$tgt.removeAttr('step').removeAttr('max').removeAttr('min').attr('type', 'text');
				$tgt.prop('readonly', true);
			});
		} catch(err) {
			_p$.error(_N$, _m$, 'ERR:', err);
		} //end try catch
		//--
	};
	_C$.DateSmartField = DateSmartField; // export

	/**
	 * Converts all Text Fields of type .ux-date-field to Date Fields
	 * See: DateSmartField()
	 *
	 * @memberof smartJ$Browser
	 * @method DateSmartsFields
	 * @static
	 * @arrow
	 *
	 */
	const DateSmartsFields = (className) => {
		//--
		const _m$ = 'DateSmartsFields';
		//--
		className = _Utils$.stringTrim(_Utils$.create_htmid(_Utils$.stringPureVal(className, true)));
		if(className == '') {
			_p$.warn(_N$, _m$, 'ClassName is Empty or Invalid');
			return '';
		} //end if
		//--
		$('input[type=date].' + className).each((idx, elem) => {
			DateSmartField(elem);
		});
		//--
	};
	_C$.DateSmartsFields = DateSmartsFields; // export

	// ========== PRIVATES

	/*
	 * Allow or dissalow the use of ModalBox.
	 * This will behave depending how is set the smartJ$Browser.param_ModalBoxActive
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method allowModalBox
	 * @static
	 *
	 * @return 	{Boolean} 		Will return FALSE if modal cascading is enabled for every situation when a ModalBox will open another ModalBox thus will force PopUp and will return TRUE for the rest of situations
	 */
	const allowModalBox = function() {
		//--
		let isActive = false;
		if((_C$.param_ModalBoxActive == 1) && (!_Te$tBrowser.checkIsMobileDevice())) {
			isActive = !! _C$.param_ModalBoxActive;
		} else if(_C$.param_ModalBoxActive > 1) {
			isActive = !! _C$.param_ModalBoxActive;
		} //end if else
		//--
		return !! isActive; // bool
		//--
	}; //END
	// no export

	/*
	 * Control the ModalBox Cascading in browser.
	 * This will behave depending how is set the smartJ$Browser.param_ModalBoxNoCascade
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method allowModalCascading
	 * @static
	 *
	 * @return 	{Boolean} 		Will return FALSE if modal cascading is enabled for every situation when a ModalBox will open another ModalBox thus will force PopUp and will return TRUE for the rest of situations
	 */
	const allowModalCascading = function() {
		//--
		const _m$ = 'allowModalCascading';
		//--
		let cascadeModal = true;
		if(!!_C$.param_ModalBoxNoCascade) {
			cascadeModal = false;
		} //end if
		//--
		try {
			//--
			if(typeof(smartJ$ModalBox) != 'undefined') {
				if(self.name) {
					if(self.name == smartJ$ModalBox.getName()) {
						return cascadeModal; // force popup
					} //end if else
				} //end if
			} //end if
			//--
		} catch(err) {
			_p$.error(_N$, _m$, 'ERR: Failed for Self:', err);
		} //end try catch
		//--
		try {
			//--
			if(typeof(parent.smartJ$ModalBox) != 'undefined') {
				if(parent.name) {
					if(parent.name == parent.smartJ$ModalBox.getName()) {
						return cascadeModal; // force popup
					} //end if else
				} //end if
			} //end if
			//--
		} catch(err) {
			_p$.error(_N$, _m$, 'ERR: Failed for Parent:', err);
		} //end try catch
		//--
		return true;
		//--
	}; //END
	// no export

	/*
	 * Return the a valid overlay ID based on original ID
	 * If the overlay_id is empty will return the sfOverlayID as a fallback
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method OverlayValidID
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 									A valid Overlay ID
	 */
	const OverlayValidID = (overlay_id) => {
		//--
		overlay_id = _Utils$.stringPureVal(overlay_id, true); // cast to string, trim
		overlay_id = _Utils$.create_htmid(overlay_id);
		if(overlay_id == '') {
			overlay_id = String(sfOverlayID);
		} //end if
		//--
		return String(overlay_id);
		//--
	}; //END
	// no export

	/*
	 * Return the available growl type based on param_NotificationDialogType and detect if loaded
	 * If param_NotificationDialogType is set to auto mode the smartJ$UI will have priority against the jQuery.alertable if both are loaded
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method DialogSelectType
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 									The selected growl type by settings logic or empty string
	 */
	const DialogSelectType = () => { // ES6
		//--
		switch(_C$.param_NotificationDialogType) {
			case 'alertable':
				if(typeof($.alertable) != 'undefined') {
					return 'alertable';
				} //end if
				break;
			case 'ui':
			case 'auto':
				if((typeof(smartJ$UI) == 'object') && (typeof(smartJ$UI.DialogAlert) == 'function') && (typeof(smartJ$UI.DialogConfirm) == 'function')) {
					return 'ui';
				} else if((_C$.param_NotificationDialogType === 'auto') && (typeof($.alertable) != 'undefined')) {
					return 'alertable';
				} //end if
				break;
		} //end switch
		//--
		return 'native';
		//--
	}; //END
	// no export

	/*
	 * Return the available growl type based on param_NotificationGrowlType and detect if loaded
	 * If param_NotificationGrowlType is set to auto mode the smartJ$UI.growl will have priority against the jQuery.toastr if both are loaded
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method GrowlSelectType
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 									The selected growl type by settings logic or empty string
	 */
	const GrowlSelectType = () => { // ES6
		//--
		if(_C$.param_Notifications !== 'growl') {
			return '';
		} //end if
		//--
		switch(_C$.param_NotificationGrowlType) {
			case 'toastr':
				if(typeof($.toastr) != 'undefined') {
					return 'toastr';
				} //end if
				break;
			case 'ui':
			case 'auto':
				if((typeof(smartJ$UI) != 'undefined') && (typeof(smartJ$UI.GrowlAdd) == 'function') && (typeof(smartJ$UI.GrowlRemove) == 'function')) {
					return 'ui';
				} else if((_C$.param_NotificationGrowlType === 'auto') && (typeof($.toastr) != 'undefined')) {
					return 'toastr';
				} //end if else
				break;
		} //end switch
		//--
		return '';
		//--
	}; //END
	// no export

	/*
	 * Return a valid type for the the jQuery Ajax
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method getValidjQueryAjaxType
	 * @static
	 * @arrow
	 *
	 * @return 	{String} 									The type ; if not valid will fallback to 'text'
	 */
	const getValidjQueryAjaxType = (y_data_type) => {
		//--
		y_data_type = _Utils$.stringPureVal(y_data_type, true); // cast to string, trim
		switch(y_data_type) {
			case 'jsonp': // Loads in a JSON block using JSONP. Adds an extra "?callback=?" to the end of your URL to specify the callback
			case 'json': // Evaluates the response as JSON and returns a JavaScript object. The JSON data is parsed in a strict manner; any malformed JSON is rejected and a parse error is thrown
			case 'script': // Evaluates the response as JavaScript and returns it as plain text
			case 'html': // Expects valid HTML ; included javascripts are evaluated when inserted in the DOM
			case 'xml': // Expects valid XML
			case 'text': // Expects Text or HTML ; If HTML, includded javascripts are not evaluated when inserted in the DOM
				break;
			default:
				y_data_type = 'text';
		} //end switch
		//--
		return String(y_data_type);
		//--
	}; //END
	// no export

	/*
	 * Inits and Open a Modal or PopUp Window by Form or Link
	 *
	 * @private
	 *
	 * @memberof smartJ$Browser
	 * @method initModalOrPopUp
	 * @static
	 *
	 * @param 	{String} 	strUrl 			The URL to open
	 * @param 	{String} 	strTarget 		The URL target (window name)
	 * @param 	{String} 	windowWidth 	*Optional* The Window Width
	 * @param 	{String} 	windowHeight 	*Optional* The Window Height
	 * @param 	{Enum} 		forcePopUp 		*Optional* Open Mode:
	 * 		 0 (default) don't force, if modal Open Modal otherwise open PopUp
	 * 		 1 force PopUp
	 * 		-1 force Modal
	 * @param 	{Enum} 		forceDims 		*Optional* If Modal must be set to 1 to force use the specified Width and Height
	 * @param 	{Enum}		handlerMode		*Optional* If explicit set to 1 or 2 will use only a simple modal/popup without binding to the parent window ; if set to 2, the popup will not use redirect handler in addition to no binding ; if set to -1 the popup will not use redirect handler, but will use binding
	 */
	const initModalOrPopUp = function(strUrl, strTarget, windowWidth, windowHeight, forcePopUp, forceDims, handlerMode) {
		//--
		const _m$ = 'initModalOrPopUp';
		//--
		strUrl = _Utils$.stringPureVal(strUrl); // cast to string, trim
		strTarget = _Utils$.stringPureVal(strTarget); // cast to string, trim
		windowWidth = _Utils$.format_number_int(parseInt(windowWidth), false);
		windowHeight = _Utils$.format_number_int(parseInt(windowHeight), false);
		//--
		forcePopUp = _Utils$.format_number_int(parseInt(forcePopUp), true);
		forceDims = _Utils$.format_number_int(parseInt(forceDims), true);
		handlerMode = _Utils$.format_number_int(parseInt(handlerMode), true);
		//--
		if((((typeof(smartJ$ModalBox) != 'undefined') && (allowModalCascading()) && (forcePopUp != 1)) || (forcePopUp == -1)) && (allowModalBox())) { // use smart modal box
			//-- trasfer current parent refresh settings to the modal {{{SYNC-TRANSFER-MODAL-POPUP-REFRESH}}}
			if(handlerMode <= 0) {
				smartJ$ModalBox.setRefreshParent(_C$.getFlag('RefreshState'), _C$.getFlag('RefreshURL'));
			} //end if
			//-- reset refresh on each modal open else a popup opened previous may refresh the parent on close
			_C$.setFlag('RefreshState', 0);
			_C$.setFlag('RefreshURL', '');
			//-- open
			if(forceDims != 1) {
				smartJ$ModalBox.LoadURL(strUrl, _C$.param_ModalBoxProtected); // we do not use here custom size
			} else {
				smartJ$ModalBox.LoadURL(strUrl, _C$.param_ModalBoxProtected, windowWidth, windowHeight); // we use here custom size
			} //end if else
			//--
		} else { // use pop up
			//--
			if((strTarget == '') || (strTarget.toLowerCase() == '_blank') || (strTarget.toLowerCase() == '_self')) {
				strTarget = String(defSmartPopupTarget); // dissalow empty target name
			} //end if
			//--
			let the_screen_width = 0;
			try { // try to center
				the_screen_width = _Utils$.format_number_int(parseInt(screen.width));
			} catch(e){} //end try catch
			if(the_screen_width <= 0) {
				the_screen_width = 920;
			} //end if
			//--
			let the_screen_height = 0;
			try { // try to center
				the_screen_height = _Utils$.format_number_int(parseInt(screen.height));
			} catch(e){} //end try catch
			if(the_screen_height <= 0) {
				the_screen_height = 700;
			} //end if
			//--
			let maxW = _Utils$.format_number_int(Math.round(the_screen_width * 0.90));
			if(windowWidth > maxW) {
				windowWidth = maxW;
			} //end if
			//--
			let maxH = _Utils$.format_number_int(Math.round(the_screen_height * 0.80)); // on height there are menus or others
			if(windowHeight > maxH) {
				windowHeight = maxH;
			} //end if
			//--
			if((windowWidth < 200) || (windowHeight < 100)) {
				windowWidth = maxW;
				windowHeight = maxH;
			} //end if
			//--
			let windowTop = 50;
			let windowLeft = _Utils$.format_number_int(Math.round((the_screen_width / 2) - (windowWidth / 2)));
			if(windowLeft < 10) {
				windowLeft = 10;
			} //end if
			//--
			if(objRefWinPopup) {
				windowFocus(objRefWinPopup); // pre-focus if opened, with try/catch in windowFocus
			} //end if
			let useMonitor = true;
			let useRdr = true;
			if(handlerMode == -1) {
				useRdr = false;
			} else if(handlerMode == 1) {
				useMonitor = false;
			} else if(handlerMode == 2) {
				useMonitor = false;
				useRdr = false;
			} //end if
			let pUrl = String(_C$.param_LoaderBlank);
			if(useRdr !== true) {
				pUrl = strUrl;
			} //end if
			try {
				objRefWinPopup = window.open(pUrl, strTarget, 'top=' + windowTop + ',left=' + windowLeft + ',width=' + windowWidth + ',height=' + windowHeight + ',toolbar=0,scrollbars=1,resizable=1'); // most of modern browsers do no more support display toolbar on popUp
			} catch(err){
				_p$.error(_N$, _m$, 'ERROR raising a new PopUp Window:', err);
			} //end try catch
			if(objRefWinPopup) {
				windowFocus(objRefWinPopup); // post-focus, with try/catch in windowFocus
				if(useRdr === true) {
					try { // redirect to the URL after loading
						setTimeout(() => { objRefWinPopup.location = strUrl; }, _C$.param_TimeDelayCloseWnd);
					} catch(err){
						_p$.error(_N$, _m$, 'ERROR redirecting the PopUp Window to [' + strUrl + ']:', err);
					} //end try catch
				} //end if
				if(useMonitor !== true) {
					_C$.setFlag('RefreshState', 0);
					_C$.setFlag('RefreshURL', '');
				} else {
					try { // monitor when popup is closed, every 250ms
						const wnd_popup_timer = setInterval(() => {
							if(getFlag('PageAway') !== true) {
								let pop = _C$.getRefPopup();
								if(pop && pop.closed) {
									clearInterval(wnd_popup_timer); // first stop
									try { // {{{SYNC-POPUP-Refresh-Parent-By-EXEC}}}
										if(_C$.getFlag('RefreshState')) {
											if(!_C$.getFlag('RefreshURL')) {
												self.location = self.location; // FIX: avoid location reload to resend POST vars !!
											} else {
												self.location = String(_C$.getFlag('RefreshURL'));
											} //end if else
											_C$.setFlag('RefreshState', 0);
											_C$.setFlag('RefreshURL', '');
										} //end if
									} catch(err){
										_p$.warn(_N$, _m$, 'WARN: Failed to Set Refresh on Self:', err);
									} //end try catch
									return false;
								} //end if
							} //end if
						}, 250);
					} catch(err){}
				} //end if
			} //end if
			//--
		} //end if else
		//--
	}; //END
	// no export

}}; //END CLASS

smartJ$Browser.secureClass(); // implements class security

window.smartJ$Browser = smartJ$Browser; // global export

//==================================================================
//==================================================================

// #END
