
// AppJs Local Functions
// (c) 2008-present unix-world.org
// License: BSD
// v.20250107

// DEPENDS: smartJ$Utils, smartJ$Date, smartJ$CryptoHash, smartJ$CipherCrypto, jQuery

//==================================================================
//==================================================================

const AppJs = new class{constructor(){ // STATIC CLASS, ES6
	const _N$ = 'AppJs';

	// :: static
	const _C$ = this;

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => {
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		}
	};

	const _Utils$ = smartJ$Utils;
	const _Date$ = smartJ$Date;
	const _Crypto$Hash = smartJ$CryptoHash;
	const _Crypto$Cipher = smartJ$CipherCrypto;

	const $ = jQuery;


	const cssAlertable = 'background:#555555; color:#FFFFFF; font-size:1.5rem; font-weight:bold; text-align:right; padding-top:3px; padding-left:10px; padding-right:10px; margin-bottom:20px;';


	let objRefWinPopup = null;


	const displayAlertDialog = (y_message, evcode=null, y_title='', y_width=null, y_height=null) => {
		y_message = _Utils$.stringPureVal(y_message, true);
		y_title = _Utils$.stringPureVal(y_title, true);
		$.alertable.alert((y_title ? '<div style="' + _Utils$.escape_html(cssAlertable) + '">' + y_title + '</div>' : '') + y_message, { html:true, width:((y_width && (y_width >= 275) && (y_width <= 960)) ? y_width : 550), height:((y_height && (y_height >= 100) && (y_height <= 720)) ? y_height : 270) }).always(() => {
			_Utils$.evalJsFxCode('displayAlertDialog', evcode);
		});
	};
	_C$.displayAlertDialog = displayAlertDialog;


	const displayConfirmDialog = (y_question, evcode=null, y_title='', y_width=null, y_height=null) => {
		y_question = _Utils$.stringPureVal(y_question, true);
		y_title = _Utils$.stringPureVal(y_title, true);
		$.alertable.confirm((y_title ? '<div style="' + _Utils$.escape_html(cssAlertable) + '">' + y_title + '</div>' : '') + y_question, { html:true, width:((y_width && (y_width >= 275) && (y_width <= 960)) ? y_width : 550), height:((y_height && (y_height >= 100) && (y_height <= 720)) ? y_height : 270) }).then(() => {
			_Utils$.evalJsFxCode('displayAlertDialog', evcode);
		});
	};
	_C$.displayConfirmDialog = displayConfirmDialog;


	const selectAllCheckBoxes = (name) => {
		name = _Utils$.stringPureVal(name, true);
		if(name == '') {
			_p$.warn(_N$, 'selectCheckBoxes', 'Empty Selector Name');
			return false;
		}
		let numSelected = 0;
		$('input:checkbox[name=' + name + ']').each((idx, el) => {
			const isSelected = !! $(el).prop('checked');
			if(isSelected === false) {
				numSelected++;
			}
			$(el).prop('checked', !isSelected);
		});
		return !! numSelected;
	};
	_C$.selectAllCheckBoxes = selectAllCheckBoxes;


	const removeAllGrowls = () => {
		try {
			$.gritter.removeAll();
		} catch(err) {
			_p$.error(_N$, 'removeAllGrowls', 'ERR:', err);
		}
	};
	_C$.removeAllGrowls = removeAllGrowls;


	const displayGrowl = function(title, html, time, sticky=false, css_class=null, options=null) {
		title = _Utils$.stringPureVal(title, true);
		html = _Utils$.stringPureVal(html, true);
		time = _Utils$.format_number_int(time, false);
		if(time < 0) {
			time = 0;
		} else if(time > 60000) {
			time = 60000;
		}
		css_class = _Utils$.stringPureVal(css_class, true);
		if(css_class == 'undefined') {
			css_class = '';
		}
		let growl_before_open = null;
		let growl_after_open = null;
		let growl_before_close = null;
		let growl_after_close = null;
		if(options) {
			if(options.hasOwnProperty('before_open')) {
				growl_before_open = options.before_open;
			}
			if(options.hasOwnProperty('after_open')) {
				growl_after_open = options.after_open;
			}
			if(options.hasOwnProperty('before_close')) {
				growl_before_close = options.before_close;
			}
			if(options.hasOwnProperty('after_close')) {
				growl_after_close = options.after_close;
			}
		}
		css_class = String($.gritter.translateCssClasses(css_class));
		try {
			$.gritter.add({
				class_name: String(css_class),
				title: String(title),
				text: String(html),
				before_open:  growl_before_open,
				after_open:   growl_after_open,
				before_close: growl_before_close,
				after_close:  growl_after_close,
				time: (sticky ? 0 : time),
				sticky: !! sticky,
			});
		} catch(err) {
			_p$.error(_N$, 'displayGrowl', 'ERR:', err);
		}
	};
	_C$.displayGrowl = displayGrowl;


	const AjaxRequestByURL = function(y_url, y_method, y_data_type, y_data_arr_or_serialized=null, y_is_multipart=false) {
		const _m$ = 'AjaxRequestByURL';
		y_url = _Utils$.stringPureVal(y_url, true);
		if((y_url == '') || (y_url == '#')) {
			_p$.error(_N$, _m$, 'ERR: URL is Empty');
			return null;
		}
		y_method = _Utils$.stringPureVal(y_method, true).toUpperCase();
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
				_p$.error(_N$, _m$, 'ERR: Invalid Method:', y_method);
				return null;
		}
		y_data_type = _Utils$.stringPureVal(y_data_type, true);
		switch(y_data_type) {
			case 'jsonp':
			case 'json':
			case 'script':
			case 'html':
			case 'xml':
			case 'text':
				break;
			default:
				_p$.error(_N$, _m$, 'ERR: Invalid DataType:', y_data_type);
				return null;
		}
		let ajxOpts = {
			async: 			true,
			type: 			String(y_method),
			url: 			String(y_url),
			data: 			y_data_arr_or_serialized,
			dataType: 		String(y_data_type),
		};
		if(y_is_multipart === true) {
			ajxOpts.contentType = false;
			ajxOpts.processData = false;
		}
		return $.ajax(ajxOpts);
	};
	_C$.AjaxRequestByURL = AjaxRequestByURL;


	const SubmitAjaxRequest = function(url, data=null, evcode=null, everrcode=null, evfailcode=null) {
		const _m$ = 'SubmitAjaxRequest';
		const ajax = AjaxRequestByURL(url, 'POST', 'json', data);
		if(ajax === null) {
			_p$.error(_N$, _m$, 'ERR: Null XHR Object !');
			displayGrowl('Submit ERROR', '<h3>XHR Object is NULL ! See the javascript console for more details ...</h3>', 0, true, 'red');
			return;
		}
		ajax.done((msg) => {
			if((typeof(msg) == 'object') && (msg.hasOwnProperty('completed')) && (msg.completed == 'DONE') && (msg.hasOwnProperty('status')) && ((msg.status == 'OK') || (msg.status == 'ERROR')) && (msg.hasOwnProperty('title')) && (msg.title != null) && (msg.hasOwnProperty('message')) && (msg.message != null)) {
				if(msg.status == 'OK') {
					_Utils$.evalJsFxCode(
						_m$ + ' (1)',
						(typeof(evcode) === 'function' ?
							() => {
								'use strict';
								(evcode)(null, url, msg);
							} :
							() => {
								'use strict';
								!! evcode ? eval(evcode) : null
							}
						)
					);
					displayGrowl(_Utils$.escape_html(msg.title), '<h5>' + _Utils$.nl2br(_Utils$.escape_html(msg.message)) + '</h5>', 2500, false, 'green');
				} else {
					_Utils$.evalJsFxCode(
						_m$ + ' (2)',
						(typeof(everrcode) === 'function' ?
							() => {
								'use strict';
								(everrcode)(null, url, msg);
							} :
							() => {
								'use strict';
								!! everrcode ? eval(everrcode) : null
							}
						)
					);
					displayGrowl('FAIL # ' + _Utils$.escape_html(msg.title), '<h4>' + _Utils$.nl2br(_Utils$.escape_html(msg.message)) + '</h4>', 0, true, 'pink');
				}
			} else {
				_p$.warn(_N$, _m$, 'WARN: Invalid Data Object Format');
				displayGrowl('POST ERROR', '<h3>Invalid Submit Response: Unexpected Data Object Format</h3>', 0, true, 'red');
				_Utils$.evalJsFxCode(
					_m$ + ' (3)',
					(typeof(evfailcode) === 'function' ?
						() => {
							'use strict';
							(evfailcode)(null, url, msg);
						} :
						() => {
							'use strict';
							!! evfailcode ? eval(evfailcode) : null
						}
					)
				);
			}
		}).fail((msg) => {
			displayAlertDialog(
				'<h4>HTTP&nbsp;Status: ' + _Utils$.escape_html(msg.status) + '</h4><br>' + '\n' + '<h5>XHR Server Response NOT Validated ...</h5><h6>' + ((msg.status == 401) ? 'If the auth credentials are valid and still getting this message do a full page refresh in the browser and try again, it could be a re-auth issue !<br>Status: `' : (msg.statusText ? _Utils$.escape_html(msg.statusText) : 'Unknown')) + '`</h6>',
				(typeof(evfailcode) === 'function' ?
					() => {
						'use strict';
						(evfailcode)(null, url, msg);
					} :
					() => {
						'use strict';
						!! evfailcode ? eval(evfailcode) : null
					}
				),
				'FAIL'
			);
		});
	};
	_C$.SubmitAjaxRequest = SubmitAjaxRequest;


	const AjaxRequestByTheForm = function(the_form_id, url) {
		const _m$ = 'AjaxRequestByTheForm';
		url = _Utils$.stringPureVal(url, true);
		if((url == '') || (url == '#')) {
			_p$.error(_N$, _m$, 'ERR: URL is Empty');
			return null;
		}
		the_form_id = _Utils$.stringPureVal(the_form_id, true);
		if(the_form_id == '') {
			_p$.error(_N$, _m$, 'ERR: Form ID is Empty');
			return null;
		}
		const $form = $('form#' + the_form_id);
		if(typeof($form.get(0)) == 'undefined') {
			_p$.error(_N$, _m$, 'ERR: FormID:', the_form_id, 'was not found');
			return null;
		}
		let ajax = null;
		let data = '';
		const fMethod = _Utils$.stringPureVal($form.attr('method'), true);
		const fEncType = _Utils$.stringPureVal($form.attr('enctype'), true);
		let isMultipart = false;
		if((fMethod.toLowerCase() == 'post') && (fEncType.toLowerCase() == 'multipart/form-data')) {
			let haveFiles = $form.find('input:file');
			if(typeof(haveFiles) != 'undefined') {
				if(typeof(haveFiles.get(0)) != 'undefined') {
					isMultipart = true;
				}
			}
		}
		if(isMultipart !== true) {
			data = $form.serialize();
			ajax = AjaxRequestByURL(url, 'POST', 'json', data);
		} else {
			try {
				data = new FormData($form.get(0));
				ajax = AjaxRequestByURL(url, 'POST', 'json', data, true);
			} catch(err) {
				displayAlertDialog('<div class="operation_error">Multipart Form DATA FAILED</div><div class="operation_notice">Try to upgrade or change your browser. It may be not compliant to support HTML5 File Uploads.</div>' + '<div><b>' + _Utils$.escape_html('FormID: `#' + the_form_id + '`') + '<br>' + _Utils$.escape_html('ERROR Details: ' + err) + '</b></div>', null, 'ERROR', 720, 350);
				return null;
			}
		}
		return ajax;
	};
	_C$.AjaxRequestByTheForm = AjaxRequestByTheForm;


	const SubmitTheFormByAjax = function(the_form_id, url, evcode=null, everrcode=null, evfailcode=null) {
		const _m$ = 'SubmitTheFormByAjax';
		const ajax = AjaxRequestByTheForm(the_form_id, url);
		if(ajax === null) {
			_p$.error(_N$, _m$, 'ERR: Null XHR Object !');
			displayGrowl('Form POST ERROR', '<h3>XHR Object is NULL ! See the javascript console for more details ...</h3>', 0, true, 'red');
			return;
		}
		ajax.done((msg) => {
			if((typeof(msg) == 'object') && (msg.hasOwnProperty('completed')) && (msg.completed == 'DONE') && (msg.hasOwnProperty('status')) && ((msg.status == 'OK') || (msg.status == 'ERROR')) && (msg.hasOwnProperty('title')) && (msg.title != null) && (msg.hasOwnProperty('message')) && (msg.message != null)) {
				if(msg.status == 'OK') {
					_Utils$.evalJsFxCode(
						_m$ + ' (1)',
						(typeof(evcode) === 'function' ?
							() => {
								'use strict';
								(evcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict';
								!! evcode ? eval(evcode) : null
							}
						)
					);
					displayGrowl(_Utils$.escape_html(msg.title), '<h5>' + _Utils$.nl2br(_Utils$.escape_html(msg.message)) + '</h5>', 2500, false, 'green');
				} else {
					_Utils$.evalJsFxCode(
						_m$ + ' (2)',
						(typeof(everrcode) === 'function' ?
							() => {
								'use strict';
								(everrcode)(the_form_id, url, msg);
							} :
							() => {
								'use strict';
								!! everrcode ? eval(everrcode) : null
							}
						)
					);
					displayGrowl('FAIL # ' + _Utils$.escape_html(msg.title), '<h4>' + _Utils$.nl2br(_Utils$.escape_html(msg.message)) + '</h4>', 0, true, 'pink');
				}
			} else {
				_p$.warn(_N$, _m$, 'WARN: Invalid Data Object Format');
				displayGrowl('Form POST ERROR', '<h3>Invalid Form Submit Response: Unexpected Data Object Format</h3>', 0, true, 'red');
				_Utils$.evalJsFxCode(
					_m$ + ' (3)',
					(typeof(evfailcode) === 'function' ?
						() => {
							'use strict';
							(evfailcode)(the_form_id, url, msg);
						} :
						() => {
							'use strict';
							!! evfailcode ? eval(evfailcode) : null
						}
					)
				);
			}
		}).fail((msg) => {
			displayAlertDialog(
				'<h4>HTTP&nbsp;Status: ' + _Utils$.escape_html(msg.status) + '</h4><br>' + '\n' + '<h5>XHR Server Response NOT Validated ...</h5>' + ((msg.status == 401) ? '<h6>If the auth credentials are valid and still getting this message do a full page refresh in the browser and try again, it could be a re-auth issue !</h6>' : ''),
				(typeof(evfailcode) === 'function' ?
					() => {
						'use strict';
						(evfailcode)(the_form_id, url, msg);
					} :
					() => {
						'use strict';
						!! evfailcode ? eval(evfailcode) : null
					}
				),
				'FAIL'
			);
		});
	};
	_C$.SubmitTheFormByAjax = SubmitTheFormByAjax;


	const getCrrIsoDateTime = () => String(_Date$.getIsoDate(new Date(), true));
	_C$.getCrrIsoDateTime = getCrrIsoDateTime;


	const createUUID = (seed) => {
		seed = _Utils$.stringPureVal(seed);
		if(_Utils$.stringTrim(seed) == '') {
			_p$.error(_N$, 'getUUID', 'The seed is empty');
			return '';
		}
		return String(_Crypto$Hash.crc32b(_Utils$.uuid() + '#' + seed)).toUpperCase();
	};
	_C$.createUUID = createUUID;


	const popUpWnd = function(strUrl, strTarget) {
		//--
		const _m$ = 'popUpWnd';
		//--
		strUrl = _Utils$.stringPureVal(strUrl); // cast to string, trim
		strTarget = _Utils$.stringPureVal(strTarget); // cast to string, trim
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
		let windowWidth = _Utils$.format_number_int(Math.round(the_screen_width * 0.90));
		let windowHeight = _Utils$.format_number_int(Math.round(the_screen_height * 0.80)); // on height there are menus or others
		//--
		let windowTop = 50;
		let windowLeft = _Utils$.format_number_int(Math.round((the_screen_width / 2) - (windowWidth / 2)));
		if(windowLeft < 10) {
			windowLeft = 10;
		} //end if
		//--
		try { // pre-focus if opened
			if(objRefWinPopup) {
				objRefWinPopup.focus();
			} //end if
		} catch(err){}
		try {
			objRefWinPopup = window.open(String(strUrl), strTarget, 'top=' + windowTop + ',left=' + windowLeft + ',width=' + windowWidth + ',height=' + windowHeight + ',toolbar=0,scrollbars=1,resizable=1'); // most of modern browsers do no more support display toolbar on popUp
		} catch(err){
			_p$.error(_N$, _m$, 'ERROR raising a new PopUp Window:', err);
		} //end try catch
		if(objRefWinPopup) {
			try { // post-focus
				objRefWinPopup.focus();
			} catch(err){}
		} //end if
		//--
	}; //END
	_C$.popUpWnd = popUpWnd;


	_C$.isFiniteNumber = _Utils$.isFiniteNumber;
	_C$.stringPureVal = _Utils$.stringPureVal;
	_C$.stringTrim = _Utils$.stringTrim;

	_C$.format_number_float = _Utils$.format_number_float;
	_C$.format_number_int = _Utils$.format_number_int;
	_C$.format_number_dec = _Utils$.format_number_dec;

	_C$.escape_css = _Utils$.escape_css;
	_C$.escape_html = _Utils$.escape_html;
	_C$.escape_js = _Utils$.escape_js;
	_C$.escape_url = _Utils$.escape_url;

	_C$.create_slug = _Utils$.create_slug;
	_C$.create_htmid = _Utils$.create_htmid;
	_C$.create_jsvar = _Utils$.create_jsvar;

	_C$.preg_quote = _Utils$.preg_quote;
	_C$.nl2br = _Utils$.nl2br;
	_C$.url_add_suffix = _Utils$.url_add_suffix;
	_C$.renderMarkersTpl = _Utils$.renderMarkersTpl;

	_C$.b64enc = _Utils$.b64Enc;
	_C$.b64dec = _Utils$.b64Dec;

	_C$.crc32b 	= _Crypto$Hash.crc32b;
	_C$.md5 	= _Crypto$Hash.md5;
	_C$.sha1 	= _Crypto$Hash.sha1;
	_C$.sha224 	= _Crypto$Hash.sha224;
	_C$.sha256 	= _Crypto$Hash.sha256;
	_C$.sha384 	= _Crypto$Hash.sha384;
	_C$.sha512 	= _Crypto$Hash.sha512;
	_C$.sh3a224 = _Crypto$Hash.sh3a224;
	_C$.sh3a256 = _Crypto$Hash.sh3a256;
	_C$.sh3a384 = _Crypto$Hash.sh3a384;
	_C$.sh3a512 = _Crypto$Hash.sh3a512;
	_C$.hmac 	= _Crypto$Hash.hmac;
	_C$.pbkdf2 	= _Crypto$Hash.pbkdf2;

	_C$.bfEnc = _Crypto$Cipher.bfEnc;
	_C$.bfDec = _Crypto$Cipher.bfDec;
	_C$.tfEnc = _Crypto$Cipher.tfEnc;
	_C$.tfDec = _Crypto$Cipher.tfDec;


}}; //END CLASS

AppJs.secureClass();

window.AppJs = AppJs;

//==================================================================
//==================================================================

// #END
