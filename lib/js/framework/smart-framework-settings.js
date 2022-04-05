
// [@[#[!NO-STRIP!]#]@]
// Default Settings for Smart.Framework JS
// smart-framework-settings.js
// v.20220404

const smartJ$Options = {
	BrowserTest: {
		isMobile: 'auto', // 'no'
	},
	ModalBox: {
		CssOverlayOpacity: 0.8,
	},
	BrowserUtils: {
		LanguageId: 'en',
		Charset: 'UTF-8',
		CookieLifeTime: 0,
		CookieDomain: '',
		CookieSameSitePolicy: 'Lax',
		Notifications: 'growl',
		NotificationDialogType: 'auto',
		ModalBoxProtected: true,
		NotifyLoadError: false,
		CssOverlayOpacity: 0.8,
	},
};
Object.freeze(smartJ$Options); // sec.

if(typeof(window) != 'undefined') {
	window.smartJ$Options = smartJ$Options; // g-exp.
} //end if

// #END
