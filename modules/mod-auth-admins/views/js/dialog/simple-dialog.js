
// jQuery Simple Dialog (SmartLightUI)
// (c) 2015-present unix-world.org
// License: BSD
// v.20250124

// DEPENDS: jQuery, smartJ$Utils
// REQUIRES-CSS: simple-dialog.css

//==================================================================
//==================================================================

//================== [ES6]

const SmartSimpleDialog = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartSimpleDialog';

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

	const _Utils$ = smartJ$Utils;

	let widgetIsOpen = null;


	const Dialog_Alert = function(y_message_html, evcode=null, y_title='', y_width=550, y_height=250) { // sync with smartJ$Browser.AlertDialog()
		//--
		widgetIsOpen = true;
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
		$('body').css('overflow', 'hidden'); // disable body scroll
		//--
		$('#simpledialog-overlay').css({
			'top': '0px',
			'left': '0px',
			'width': '100vw',
			'height': '100vh',
			'z-index': 2147482001
		}).show();
		//--
		$('#simpledialog-area-head').html(String(y_title));
		$('#simpledialog-area-msg').html(String(y_message_html));
		$('#simpledialog-bttn-no').show().text('').hide();
		$('#simpledialog-bttn-yes').show().html('<span class="uicon">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;OK').click(() => {
			CloseWidget(false); // - reset content
			_Utils$.evalJsFxCode(_N$ + '.Dialog_Alert', evcode); // EV.NOCTX
			ResetWidget(); // reset content ONLY after eval code otherwise jQuery selectors will fail !!!
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
		widgetIsOpen = true;
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
		$('body').css('overflow', 'hidden'); // disable body scroll
		//--
		$('#simpledialog-overlay').css({
			'top': '0px',
			'left': '0px',
			'width': '100vw',
			'height': '100vh',
			'z-index': 2147482001
		}).show();
		//--
		$('#simpledialog-area-head').html(String(y_title));
		$('#simpledialog-area-msg').html(String(y_question_html));
		$('#simpledialog-bttn-no').show().html('<span class="uiconx">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;Cancel').click(() => {
			CloseWidget(true); // + reset content
		});
		$('#simpledialog-bttn-yes').show().html('<span class="uicon">&nbsp;&nbsp;&nbsp;&nbsp;</span>&nbsp;OK').click(() => {
			CloseWidget(false); // - reset content
			_Utils$.evalJsFxCode(_N$ + '.Dialog_Confirm', evcode); // EV.NOCTX
			ResetWidget(); // reset content ONLY after eval code otherwise jQuery selectors will fail !!!
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
		setTimeout(() => {
			if(widgetIsOpen !== true) { // avoid reset it if evcode open another instance, it will rewrite the title and message !
			//	_p$.log(_N$, 'Reset ...');
				$('#simpledialog-area-head').text('[T]');
				$('#simpledialog-area-msg').text('[M]');
			} //end if
		}, 250);
		//--
	}; //END
	_C$.ResetWidget = ResetWidget; // export


	const CloseWidget = function(doReset) {
		//--
		widgetIsOpen = false; // signal this here before ResetWidget(), it will lok after this value ! the evcode may open another instance and will change this value to TRUE to avoid reset it after another instance is opened !!
		//--
		if(doReset === true) {
			ResetWidget();
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
		$('body').css('overflow', 'auto'); // re-enable body scroll
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
		//-- requires: simple-dialog.css
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
