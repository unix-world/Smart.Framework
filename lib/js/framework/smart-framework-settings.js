
// [@[#[!NO-STRIP!]#]@]
// Default Settings for Smart.Framework JS
// smart-framework-settings.js
// v.20251216

const smartJ$Options = {
	BrowserTest: {
		isMobile: 'auto', // 'no'
	},
	BrowserUtils: {
		LanguageId: 'en',
		CookieLifeTime: 0,
		CookieDomain: '',
		CookieSameSitePolicy: 'Lax',
		Notifications: 'growl',
		NotificationDialogType: 'auto',
		ModalBoxProtected: true,
		NotifyLoadError: false,
	},
};
Object.freeze(smartJ$Options); // sec.

if(typeof(window) != 'undefined') {
	window.smartJ$Options = smartJ$Options; // g-exp.
} //end if

// #END
