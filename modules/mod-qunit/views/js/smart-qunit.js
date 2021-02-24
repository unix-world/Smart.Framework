
// r.7.2.1 / smart.framework.v.7.2

/*
 * SmartQUnit 1.2.1
 * @version 20210224
 *
 * (c) 2018-2021 unix-world.org
 * Released under the BSD license
 */

var SmartQUnit = new function() { // START CLASS

	//--
	// :: static
	//--

	this.runAjaxTest = function(url, method, dataType, assert, testOK, fxDone) {
		//--
		var QAsyncTestDone = assert.async(); // qunit async promise
		var testHtmlDiv = elHtmlDynDiv;
		//--
		jQuery.ajax({
			async: true,
			url: String(url),
			method: String(method),
			dataType: String(dataType),
			timeout: parseInt(QUnit.config.testTimeout) * 1000, // ajax timeout in sec
			cache: false // no cache at all for any ajax request !!!
		}).done(function(msg) {
			if(typeof fxDone == 'function') {
				fxDone(QAsyncTestDone, testOK, msg, testHtmlDiv);
			} else {
				var value = 'Test Implementation ERROR: INVALID AJAX TEST DONE FUNCTION !';
				assert.equal(
					value, testOK,
					testOK
				);
				QAsyncTestDone();
			} //end if else
		}).fail(function(msg) {
			var value = 'Ajax REQUEST FAILED with HTTP Status: ' + String(msg.status) + ' ' + String(msg.statusText);
			assert.equal(
				value, testOK,
				testOK
			);
			QAsyncTestDone();
		});
		//--
	} //END FUNCTION

	//--

	this.runiFrameTest = function(url, timeoutMs, assert, testOK, elID) {
		//--
		var QAsyncTestDone = assert.async(); // qunit async promise
		//--
		elHtmlDynIFrame(url, timeoutMs, assert, QAsyncTestDone, testOK, elID);
		//--
	} //END FUNCTION

	//--

	var elHtmlDynDiv = function(assert, QAsyncTestDone, testOK, value, content, timeoutMs) {
		//--
		jQuery('<div id="qu-smart-div-sandbox" style="position:fixed; bottom:1px; right:1px; width:1px; height:1px; visibility:hidden;"></div>').html(String(content)).appendTo('body'); // create a temporary div, make it hidden, and attach to the DOM
		//--
		setTimeout(function() {
			var value = jQuery('#qunit-test-result').text();
			jQuery('#qu-smart-div-sandbox').empty().html('').remove();
			assert.equal(
				value, testOK,
				testOK
			);
			QAsyncTestDone();
		}, parseInt(timeoutMs));
		//--
	} //END FUNCTION

	//--

	var elHtmlDynIFrame = function(url, timeoutMs, assert, QAsyncTestDone, testOK, elID) {
		//--
		if(!elID) {
			elID = 'qunit-test-result';
		} //end if
		//--
		var frame = jQuery('<iframe id="qu-smart-ifrm-sandbox" src="' + htmlspecialchars(url) + '" style="position:fixed; bottom:1px; right:1px; width:1px; height:1px; visibility:hidden;"></iframe>').appendTo('body'); // create a temporary iframe, make it hidden, and attach to the DOM # iFrame display:none loading jquery will throw since jquery >= 3.4.0
		jQuery(frame).on('load', function(){ // // proceed after the iframe has loaded content
			var html = jQuery(this).contents();
			//console.log(html);
			setTimeout(function() {
				var value = html.find('#' + elID).text();
				html = null;
				assert.equal(
					value, testOK,
					testOK
				);
				jQuery('#qu-smart-ifrm-sandbox').attr('src', '').remove(); // remove the temporary iframe
				QAsyncTestDone();
			}, parseInt(timeoutMs));
		});
		//--
	} //END FUNCTION

	//--

	var htmlspecialchars = function(str) {
		//--
		if((typeof str == 'undefined') || (str == undefined) || (str == null)) {
			str = '';
		} else {
			str = String(str); // force string
		} //end if else
		//-- replace basics
		str = str.replace(/&/g, '&amp;');
		str = str.replace(/</g, '&lt;');
		str = str.replace(/>/g, '&gt;');
		str = str.replace(/"/g, '&quot;');
		//--
		return String(str); // fix to return empty string instead of null
		//--
	} //END FUNCTION

	//--

} //END CLASS


// #END
