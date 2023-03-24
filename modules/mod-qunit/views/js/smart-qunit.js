
// r.8.7 / smart.framework.v.8.7

/*
 * SmartQUnit 1.3.2 [ES6]
 * @version 20230109
 *
 * (c) 2018-2022 unix-world.org
 * Released under the BSD license
 */

const SmartQUnit = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartQUnit';

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


	const runAjaxTest = function(url, method, dataType, assert, testOK, fxDone, postData=null, httpHeaders=null, authUser=null, authPass=null, withCredentials=false) {
		//--
		const QAsyncTestDone = assert.async(); // qunit async promise
		const testHtmlDiv = elHtmlDynDiv;
		//--
		method = String(method).trim().toUpperCase();
		switch(method) {
			case 'OPTIONS':
			case 'DELETE':
			case 'PATCH':
			case 'PUT':
			case 'HEAD':
			case 'POST':
				break;
			case 'GET':
			default:
				method = 'GET';
				postData = null;
		} //end switch
		//--
		if(typeof(httpHeaders) != 'object') {
			httpHeaders = {}; // default
		} //end if
		//--
		let ajxOpts = {
			async: true,
			cache: false, // no cache at all for any ajax request !!!
			timeout: parseInt(QUnit.config.testTimeout) * 1000, // ajax timeout in sec
			url: String(url),
			method: String(method),
			dataType: String(dataType),
			headers: httpHeaders,
			data: postData,
		};
		if(!!authUser && !!authPass) { // don't pass if empty, it works as sandboxed ! ; will fail to auto-send auth in admin/task environments ; set ONLY if explicit set
			ajxOpts.username = String(authUser);
			ajxOpts.password = String(authPass);
		} //end if
		if(withCredentials === true) {
			ajxOpts.xhrFields = {
				withCredentials: true // allow send credentials (CORS): FALSE / TRUE ; Default is FALSE
			};
		} //end if
		//--
		$.ajax(ajxOpts).done((msg) => {
			if(typeof(fxDone) == 'function') {
				fxDone(QAsyncTestDone, testOK, msg, testHtmlDiv);
			} else {
				const value = 'Test Implementation ERROR: INVALID AJAX TEST DONE FUNCTION !';
				assert.equal(
					value, testOK,
					testOK
				);
				QAsyncTestDone();
			} //end if else
		}).fail((msg) => {
			const value = 'Ajax REQUEST FAILED with HTTP Status: ' + String(msg.status) + ' ' + String(msg.statusText);
			assert.equal(
				value, testOK,
				testOK
			);
			QAsyncTestDone();
		});
		//--
	}; //END
	_C$.runAjaxTest = runAjaxTest; // export


	const runiFrameTest = function(url, timeoutMs, assert, testOK, elID) {
		//--
		const QAsyncTestDone = assert.async(); // qunit async promise
		//--
		elHtmlDynIFrame(url, timeoutMs, assert, QAsyncTestDone, testOK, elID);
		//--
	}; //END
	_C$.runiFrameTest = runiFrameTest; // export


	const elHtmlDynDiv = (assert, QAsyncTestDone, testOK, invalidValue, content, timeoutMs) => {
		//--
		$('<div id="qu-smart-div-sandbox" style="position:fixed; bottom:1px; right:1px; width:1px; height:1px; visibility:hidden;"></div>').html(String(content)).appendTo('body'); // create a temporary div, make it hidden, and attach to the DOM
		//--
		setTimeout(() => {
			const value = $('#qunit-test-result').text();
			$('#qu-smart-div-sandbox').empty().html('').remove();
			assert.equal(
				value, testOK,
				testOK
			);
			QAsyncTestDone();
		}, parseInt(timeoutMs));
		//--
	}; //END


	const elHtmlDynIFrame = function(url, timeoutMs, assert, QAsyncTestDone, testOK, elID) {
		//--
		if(!elID) {
			elID = 'qunit-test-result';
		} //end if
		//--
		const frame = $('<iframe id="qu-smart-ifrm-sandbox" src="' + htmlspecialchars(url) + '" style="position:fixed; bottom:1px; right:1px; width:1px; height:1px; visibility:hidden;"></iframe>').appendTo('body'); // create a temporary iframe, make it hidden, and attach to the DOM # iFrame display:none loading jquery will throw since jquery >= 3.4.0
		$(frame).on('load', (evt) => { // proceed after the iframe has loaded content
			let html = $(evt.currentTarget).contents();
			// _p$.log('elHtmlDynIFrame', html);
			setTimeout(() => {
				const value = html.find('#' + elID).text();
				html = null;
				assert.equal(
					value, testOK,
					testOK
				);
				$('#qu-smart-ifrm-sandbox').attr('src', '').remove(); // remove the temporary iframe
				QAsyncTestDone();
			}, parseInt(timeoutMs));
		});
		//--
	}; //END


	const htmlspecialchars = function(text) { // it performs better, particularly on large blocks of text
		if(text == undefined) {
			return '';
		} //end if
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;'
		};
		return String(text || '').replace(/[&\<\>"]/g, (m) => map[m]);
	}; //END


}}; //END CLASS

SmartQUnit.secureClass(); // implements class security

window.SmartQUnit = SmartQUnit; // global export

// #END
