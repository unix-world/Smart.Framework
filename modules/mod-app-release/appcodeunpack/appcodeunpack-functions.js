
// AppCodeUnpack JS Functions
// (c) 2013-2021 unix-world.org
// License: BSD
// v.20210511

// DEPENDS: smartJ$Utils, smartJ$Date, smartJ$CryptoHash, jQuery

//==================================================================
//==================================================================

const AppCodeUnpackJs = new class{constructor(){ // STATIC CLASS, ES6
	const _N$ = 'AppCodeUnpackJs';

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

	const $ = jQuery;


	const cssAlertable = 'background:#555555; color:#FFFFFF; font-size:1.5rem; font-weight:bold; text-align:right; padding-top:3px; padding-left:10px; padding-right:10px; margin-bottom:20px;';


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


	const AjaxRequestByURL = (y_url, y_method, y_data_type, y_data_arr_or_serialized=null) => {
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
			case 'PUT':
			case 'HEAD':
			case 'POST':
			case 'GET':
				break;
			default:
				_p$.error(_N$, _m$, 'ERR: Invalid Method:', y_method);
				return null;
		}
		y_data_type = _Utils$.stringPureVal(y_data_type, true);
		switch(y_data_type) {
			case 'json':
			case 'jsonp':
			case 'script':
			case 'html':
			case 'xml':
			case 'text':
				break;
			default:
				_p$.error(_N$, _m$, 'ERR: Invalid DataType:', y_data_type);
				return null;
		}
		const ajxOpts = {
			async: 			true,
			type: 			String(y_method),
			url: 			String(y_url),
			contentType: 	false,
			processData: 	false,
			data: 			y_data_arr_or_serialized,
			dataType: 		String(y_data_type),
		};
		return $.ajax(ajxOpts);
	};
	_C$.AjaxRequestByURL = AjaxRequestByURL;


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
				ajax = AjaxRequestByURL(url, 'POST', 'json', data);
			} catch(err) {
				displayAlertDialog('<div class="operation_error">Multipart Form DATA FAILED</div><div class="operation_notice">Try to upgrade or change your browser. It may be not compliant to support HTML5 File Uploads.</div>' + '<div><b>' + _Utils$.escape_html('FormID: `#' + the_form_id + '`') + '<br>' + _Utils$.escape_html('ERROR Details: ' + err) + '</b></div>', null, 'ERROR', 720, 350);
				return null;
			}
		}
		return ajax;
	};
	_C$.AjaxRequestByTheForm = AjaxRequestByTheForm;


	const SubmitTheFormByAjax = (the_form_id, url, evcode=null, everrcode=null, evfailcode=null) => {
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
						'SubmitTheFormByAjax (1)',
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
						'SubmitTheFormByAjax (2)',
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
					'SubmitTheFormByAjax (3)',
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

}}; //END CLASS

AppCodeUnpackJs.secureClass();

window.AppCodeUnpackJs = AppCodeUnpackJs;

//==================================================================
//==================================================================

// #END
