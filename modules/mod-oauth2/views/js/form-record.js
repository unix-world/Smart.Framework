
// oauth2 :: formRecord Handler
// v.20231220

const oauth2FormHandler = new class{constructor(){ // STATIC CLASS
	'use strict';
	const _N$ = 'oauth2FormHandler';

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

	const $ = jQuery;

	const _Utils$ = smartJ$Utils;
	const _Ba$eConv = smartJ$BaseConv;
	const _Crypto$Hash = smartJ$CryptoHash;
	const _BwUtils$ = smartJ$Browser;

	let UrlActions = '';

	let TplAuthParamsUrl = ''; // init
	let TplAuthCPartUrl = ''; // init
	let RegexExprValidApiID = /^[\x21-\x7E]{5,127}$/g; // init ; before x20 are only special characters ; x20 is space, exclude ; last is 7F (delete, special)

	let CsrfState = '';
	let CsrfCookieName = '';
	let CsrfCookieValue = '';

	let theDataStepOne = null;
	let theDataStepTwo = null;
	let submitStep = 0;

	const validateUrl = (url) => { // {{{SYNC-OAUTH2-VALIDATE-URL}}}
		url = _Utils$.stringPureVal(url, true); // +trim
		if((url.length < 15) || (url.length > 255)) {
			return false;
		} //end if
		if(url.indexOf('https://') !== 0) {
			return false;
		}
		return true;
	};

	const notificationDialog = (msg) => {
		_BwUtils$.GrowlNotificationAdd('Notice', _Utils$.escape_html(_Utils$.stringPureVal(msg, true)), null, 3500, false);
	};

	const readDataStepOne = () => {
		theDataStepOne = null;
		const theApiId = _Utils$.stringPureVal($('input#api_id').val() || '', true);
		if((theApiId == '') || (theApiId.length < 5) || (theApiId.length > 127)) {
			notificationDialog('API-ID is empty or have an invalid length: `' + theApiId + '`');
			return false;
		}
		if(!theApiId.match(RegexExprValidApiID)) {
			notificationDialog('API-ID contains invalid characters: `' + theApiId + '`');
			return false;
		}
		const theDescription = _Utils$.stringPureVal($('textarea#api_desc').val() || '', true);
		if((theDescription == '') || (theDescription.length < 10)) {
			notificationDialog('API Description is empty or too short');
			return null;
		}
		const theClientId = _Utils$.stringPureVal($('input#oauth2_client_id').val() || '', true);
		if(theClientId == '') {
			notificationDialog('OAuth2 Client ID is empty');
			return null;
		}
		const theClientSecret = _Utils$.stringPureVal($('input#oauth2_client_secret').val() || '', true);
		if(theClientSecret == '') {
			notificationDialog('OAuth2 Client Secret is empty');
			return null;
		}
		const theScope = _Utils$.stringPureVal($('input#oauth2_scope').val() || '', true);
	//	if(theScope == '') {
	//		notificationDialog('OAuth2 Scope is empty');
	//		return null;
	//	}
		const oauth2UrlRedirect = _Utils$.stringPureVal($('input#oauth2_url_redirect').val() || '', true);
		if(oauth2UrlRedirect == '') {
			notificationDialog('OAuth2 URL /auth is empty');
			return null;
		}
		const oauth2UrlAuth = _Utils$.stringPureVal($('input#oauth2_url_auth').val() || '', true);
		if((oauth2UrlAuth == '') || (!validateUrl(oauth2UrlAuth))) {
			notificationDialog('OAuth2 URL /auth is empty or invalid');
			return false;
		}
		const oauth2UrlToken = _Utils$.stringPureVal($('input#oauth2_url_token').val() || '', true);
		if((oauth2UrlToken == '') || (!validateUrl(oauth2UrlToken))) {
			notificationDialog('OAuth2 URL /token is empty or invalid');
			return null;
		}
		return {
			theApiId: theApiId,
			theDescription: theDescription,
			theClientId: theClientId,
			theClientSecret: theClientSecret,
			theScope: theScope,
			oauth2UrlRedirect: oauth2UrlRedirect,
			oauth2UrlAuth: oauth2UrlAuth,
			oauth2UrlToken: oauth2UrlToken,
		}
	};

	const readDataStepTwo = () => {
		theDataStepTwo = null;
		const theCode = _Utils$.stringPureVal($('input#oauth2_code').val() || '', true);
		if(theCode == '') {
			notificationDialog('OAuth2 Code is empty');
			return null;
		}
		return {
			theCode: theCode,
		}
	};

	const btnHandlerSubmit = () => { // used by submit button
		const csrfState = String(CsrfState || '');
		const csrfCookieName = String(CsrfCookieName || '');
		const csrfCookieValue = String(CsrfCookieValue || '');
		submitStep = _Utils$.format_number_int(submitStep);
		const $elemId = $('span#btn-submit-label');
		if(submitStep <= 0) {
			submitStep = 1;
			$elemId.text('Step 1: Get the OAuth2 Code');
		} else if(submitStep == 1) {
			theDataStepOne = readDataStepOne();
			if(!theDataStepOne) {
				return false;
			}
			const oauth2UrlRedirect = _Utils$.stringPureVal(theDataStepOne.oauth2UrlRedirect, true);
			let isStandaloneUri = true;
			if((oauth2UrlRedirect.indexOf('https://') == 0) || (oauth2UrlRedirect.indexOf('http://') == 0)) {
				isStandaloneUri = false;
			}
			let oauth2UrlAuth = _Utils$.stringPureVal(theDataStepOne.oauth2UrlAuth, true);
			if((oauth2UrlAuth == '') || (!validateUrl(oauth2UrlAuth))) {
				notificationDialog('OAuth2 URL `/auth` is empty or invalid');
				return false;
			}
			oauth2UrlAuth = new URL(String(oauth2UrlAuth));
			const fragUrlAuth = String(oauth2UrlAuth.hash || '').trim().substring(1); // eliminate # from fragment
			oauth2UrlAuth.hash = ''; // clear fragment
			oauth2UrlAuth = String(oauth2UrlAuth); // rewrite url without fragment
			const settingsUrlAuth = new URLSearchParams(fragUrlAuth);
			let usePKCE = true;
			for(const [kk, vv] of settingsUrlAuth) {
				if(kk === 'skip-PKCE') {
					if(!!vv) {
						usePKCE = false;
					}
				}
			}
			if(oauth2UrlAuth.indexOf('?') == -1) {
				oauth2UrlAuth += '?';
			} else {
				oauth2UrlAuth += '&';
			}
			const theApiId = _Utils$.stringPureVal(theDataStepOne.theApiId, true);
			if(theApiId == '') {
				notificationDialog('OAuth2: API-ID is Empty');
				return false;
			}
			const cId = _Utils$.stringPureVal(theDataStepOne.theClientId, true);
			if(cId == '') {
				notificationDialog('OAuth2: Client-ID is Empty');
				return false;
			}
			const cVfy = _Ba$eConv.base_from_hex_convert(_Crypto$Hash.hmac('sha3-384', _Utils$.strRot13(_Ba$eConv.b64s_enc(theApiId)), cId), 62);
			const cChl = _Ba$eConv.b64_to_b64s(_Crypto$Hash.sha256(cVfy, true), false);
			oauth2UrlAuth += _Utils$.renderMarkersTpl(_Utils$.stringPureVal(TplAuthParamsUrl), {
					'CLIENT-ID': 				String(cId || ''),
					'SCOPE': 					_Utils$.stringPureVal(theDataStepOne.theScope, true),
					'REDIRECT-URI': 			_Utils$.stringPureVal(oauth2UrlRedirect, true),
					'STATE': 					_Utils$.stringPureVal(csrfState, true),
				},
				true
			);
			if(usePKCE === true) {
				oauth2UrlAuth += _Utils$.renderMarkersTpl(_Utils$.stringPureVal(TplAuthCPartUrl), {
						'CODE-CHALLENGE': 			String(cChl || ''),
					},
					true
				);
			}
			oauth2UrlAuth += '&';
			$('div.form-step1').each((index, element) => {
				$(element).find('input[type=text],textarea').prop('readonly', true);
			});
			$('div.form-step2').each((index, element) => {
				$(element).show();
			});
			submitStep++;
			$elemId.text('Step 2: Initialize OAuth2 Tokens and Save API');
			if(!isStandaloneUri) {
				_BwUtils$.setCookie(csrfCookieName, csrfCookieValue);
			}
			_BwUtils$.PopUpLink(oauth2UrlAuth, 'oauth2_wnd_auth', 0.75, 0.7, 1, 1);
		} else if(submitStep == 2) {
			theDataStepTwo = readDataStepTwo();
			if(!theDataStepTwo) {
				return false;
			}
			$('div.form-step2').each((index, element) => {
				$(element).find('input[type=text],textarea').prop('readonly', true);
			});
			$('button#btn-submit-btn').prop('disabled', true);
			submitStep++;
			_BwUtils$.SubmitFormByAjax('oauth2_form', String(UrlActions || ''), 'yes');
		} else {
			notificationDialog('ERROR: Invalid Submit Step: ' + submitStep);
		}
		return false;
	};
	_C$.btnHandlerSubmit = btnHandlerSubmit; // export

	const btnHandlerCancel = () => { // use by the cancel button
		if(submitStep === 2) {
			const $frmStep1 = $('div.form-step1');
			const $frmStep2 = $('div.form-step2');
			$frmStep2.each((index, element) => {
				$(element).find('input[type=text],textarea').prop('readonly', false).val('');
			});
			$frmStep2.each((index, element) => {
				$(element).hide();
			});
			$frmStep1.each((index, element) => {
				$(element).find('input[type=text],textarea').prop('readonly', false);
			});
			submitStep--;
		} else {
			_BwUtils$.CloseDelayedModalPopUp();
		}
		return false;
	};
	_C$.btnHandlerCancel = btnHandlerCancel; // export

	const initForm = (regexExprValidApiID, tplAuthParamsUrl, tplAuthCPartUrl, csrfState, csrfCookieName, csrfCookieValue, urlActions) => { // inits the form
		RegexExprValidApiID 	= regexExprValidApiID;
		TplAuthParamsUrl 		= tplAuthParamsUrl;
		TplAuthCPartUrl 		= tplAuthCPartUrl;
		CsrfState 				= csrfState;
		CsrfCookieName 			= csrfCookieName;
		CsrfCookieValue 		= csrfCookieValue;
		UrlActions 				= urlActions;
		$('div.form-step2').hide();
		btnHandlerSubmit();
	};
	_C$.initForm = initForm; // export

	const setFormOauth2Code = (code) => { // used by getCode as a callback ; returns boolean
		code = _Utils$.stringPureVal(code, true);
		if(code == '') {
			return false;
		}
		$('input#oauth2_code').val(code).prop('readonly', true);
		return !! _Utils$.stringPureVal($('input#oauth2_code').val(), true);
	};
	_C$.setFormOauth2Code = setFormOauth2Code; // export

}}; //END CLASS

oauth2FormHandler.secureClass(); // implements class security

window.oauth2FormHandler = oauth2FormHandler; // global export

// #END
