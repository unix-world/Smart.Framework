
// jQuery Simple Dialog (SmartLightUI)
// (c) 2015-2021 unix-world.org
// License: BSD
// v.20210420

// DEPENDS: jQuery, smartJ$Utils
// REQUIRES-CSS: simple-dialog.css

//==================================================================
//==================================================================

//================== [ES6]

const SmartSimpleDialog = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartSimpleDialog';

	// :: static
	const _C$ = this; // self referencing

	let _p$ = console;

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

	const _Utils$ = smartJ$Utils;

	//--
	const evFXStart = '(function(){ ';
	const evFXEnd = ' })();';
	//--

	const Dialog_Alert = function(y_message_html, evcode=null, y_title='', y_width=550, y_height=250) { // sync with smartJ$Browser.AlertDialog()
		//--
		if((y_message_html == undefined) || (y_message_html == '')) {
			_p$.warn(_N$, 'WARN: Dialog was call with an empty message');
			y_message_html = '[No Message]';
		} //end if
		//--
		y_title = String((y_title == undefined) ? '' : y_title); // force string, test undefined is also for null
		if(y_title == '') {
			y_title = ' ';
		} //end if
		//--
		y_width = getMaxWidth(y_width);
		y_height = getMaxHeight(y_height);
		//--
		let the_top = 25; // 50 / 2
		let the_left = _Utils$.format_number_int((parseInt($(window).width()) / 2) - (y_width / 2) - 4); // -4 for borders
		if(the_left < 0) {
			the_left = 0;
		} //end if
		//--
		$('#simpledialog-overlay').css({
			'top': '0px',
			'left': '0px',
			'width': '100%',
			'height': '100%',
			'z-index': 2147482001
		}).show();
		//--
		$('#simpledialog-area-head').html(String(y_title));
		$('#simpledialog-area-msg').html(String(y_message_html));
		$('#simpledialog-bttn-no').show().text('').hide();
		$('#simpledialog-bttn-yes').show().html('<span class="uicon">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;OK').click(() => {
			_C$.CloseWidget(false); // - reset content
			if((evcode != undefined) && (evcode != '')) { // test undefined is also for null
				try {
					if(typeof(evcode) === 'function') {
						evcode(); // call :: sync params dialog-alert
					} else {
						eval(evFXStart + String(evcode) + evFXEnd); // sandbox
					} //end if else
				} catch(err) {
					_p$.error(_N$, 'ERR: JS-Eval Error on Eval' + '\nDetails: ' + err);
				} //end try catch
			} //end if
			_C$.ResetWidget(); // reset content ONLY after eval code otherwise jQuery selectors will fail !!!
		});
		$('#simpledialog-area-msg').css({
			'height': (y_height - 130) + 'px'
		});
		let HtmlElement = $('#simpledialog-container');
		HtmlElement.css({
			'top': the_top + 'px',
			'left': the_left + 'px',
			'width': y_width + 'px',
			'height': y_height + 'px',
			'z-index': 2147482002
		}).show();
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.Dialog_Alert = Dialog_Alert; // export


	const Dialog_Confirm = function(y_question_html, evcode=null, y_title='', y_width=550, y_height=250) { // sync with smartJ$Browser.ConfirmDialog()
		//--
		if((y_question_html == undefined) || (y_question_html == '')) {
			_p$.warn(_N$, 'WARN: Dialog was call with an empty message');
			y_question_html = '[No Question]';
		} //end if
		//--
		y_title = String((y_title == undefined) ? '' : y_title); // force string, test undefined is also for null
		if(y_title == '') {
			y_title = ' ';
		} //end if
		//--
		y_width = getMaxWidth(y_width);
		y_height = getMaxHeight(y_height);
		//--
		let the_top = 25; // 50 / 2
		let the_left = _Utils$.format_number_int((parseInt($(window).width()) / 2) - (y_width / 2) - 4); // -4 for borders
		if(the_left < 0) {
			the_left = 0;
		} //end if
		//--
		$('#simpledialog-overlay').css({
			'top': '0px',
			'left': '0px',
			'width': '100%',
			'height': '100%',
			'z-index': 2147482001
		}).show();
		//--
		$('#simpledialog-area-head').html(String(y_title));
		$('#simpledialog-area-msg').html(String(y_question_html));
		$('#simpledialog-bttn-no').show().html('<span class="uiconx">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;Cancel').click(() => {
			_C$.CloseWidget(true); // + reset content
		});
		$('#simpledialog-bttn-yes').show().html('<span class="uicon">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;OK').click(() => {
			_C$.CloseWidget(false); // - reset content
			if((evcode != undefined) && (evcode != '')) { // test undefined is also for null
				try {
					if(typeof(evcode) === 'function') {
						evcode(); // call :: sync params dialog-confirm
					} else {
						eval(evFXStart + String(evcode) + evFXEnd); // sandbox
					} //end if else
				} catch(err) {
					_p$.error(_N$, 'ERR: JS-Eval Error on Eval' + '\nDetails: ' + err);
				} //end try catch
			} //end if
			_C$.ResetWidget(); // reset content ONLY after eval code otherwise jQuery selectors will fail !!!
		});
		$('#simpledialog-area-msg').css({
			'height': (y_height - 130) + 'px'
		});
		let HtmlElement = $('#simpledialog-container');
		HtmlElement.css({
			'top': the_top + 'px',
			'left': the_left + 'px',
			'width': y_width + 'px',
			'height': y_height + 'px',
			'z-index': 2147482002
		}).show();
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.Dialog_Confirm = Dialog_Confirm; // export


	const ResetWidget = () => { // method is required to avoid reset contents on press OK prior to eval code
		//--
		$('#simpledialog-area-head').text('[T]');
		$('#simpledialog-area-msg').text('[M]');
		//--
	}; //END
	_C$.ResetWidget = ResetWidget; // export


	const CloseWidget = function(doReset) {
		//--
		if(doReset === true) {
			_C$.ResetWidget();
		} //end if
		//--
		$('#simpledialog-bttn-no').text('[N]').unbind('click').hide();
		$('#simpledialog-bttn-yes').text('[Y]').unbind('click').hide();
		$('#simpledialog-area-msg').css({
			'height': '40%'
		});
		let HtmlElement = $('#simpledialog-container');
		HtmlElement.css({
			'top':'-50px',
			'left':'-50px',
			'width':'1px',
			'height':'1px',
			'z-index':1
		}).hide();
		$('#simpledialog-overlay').css({
			'top':'-50px',
			'left':'-50px',
			'width':'1px',
			'height':'1px',
			'z-index':1
		}).hide();
		//--
		return HtmlElement;
		//--
	}; //END
	_C$.CloseWidget = CloseWidget; // export


	// #PRIVATES#


	const getMaxWidth = function(y_width) {
		//--
		y_width = _Utils$.format_number_int(y_width);
		if((y_width < 100) || (y_width > 1920)) {
			y_width = 550;
		} //end if
		let max_width = _Utils$.format_number_int($(window).width() - 50);
		if(max_width < 270) {
			max_width = 270;
		} //end if
		if(y_width > max_width) {
			y_width = max_width; // responsive fix width: min 320
		} //end if
		//--
		return y_width;
		//--
	}; //END


	const getMaxHeight = function(y_height) {
		//--
		y_height = _Utils$.format_number_int(y_height);
		if((y_height < 50) || (y_height > 1080)) {
			y_height = 250;
		} //end if
		let max_height = _Utils$.format_number_int($(window).height() - 50);
		if(max_height < 270) {
			max_height = 270;
		} //end if
		if(y_height > max_height) {
			y_height = max_height; // responsive fix height: min 320
		} //end if
		//--
		return y_height;
		//--
	}; //END


	//==
	$(() => {
		//-- requires: <link rel="stylesheet" type="text/css" href="lib/js/jquery/dialog/simple-dialog.css">
		$('body').append('<!-- SmartJS.Modal.Dialog :: Start --><div id="simpledialog-overlay"></div><div id="simpledialog-container"><div id="simpledialog-area-head" class="header">[T]</div><div id="simpledialog-area-msg" class="message">[M]</div><hr><div class="buttons"><div id="simpledialog-bttn-yes">[Y]</div><div id="simpledialog-bttn-no">[N]</div></div></div><!-- END: SmartJS.Modal.Dialog -->');
		//--
		_C$.CloseWidget(true); // + reset content
		//--
	}); //END DOCUMENT READY
	//==


}}; //END CLASS


SmartSimpleDialog.secureClass(); // implements class security

window.SmartSimpleDialog = SmartSimpleDialog; // global export


//==================================================================
//==================================================================


// #END
