
// jQuery AutoSuggest (SmartLightUI)
// (c) 2015-2021 unix-world.org
// License: BSD
// v.20210501

// DEPENDS: jQuery, smartJ$Utils
// REQUIRES-CSS: smart-suggest.css

//==================================================================
//==================================================================

//================== [ES6]

const SmartAutoSuggest = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartAutoSuggest';

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


	const bindToInput = function(y_type, y_id, y_txt, y_res_url, y_use_bttn, y_size, min_term_len, evcode) {
		//--
		min_term_len = _Utils$.format_number_int(min_term_len, false);
		if(min_term_len < 0) {
			min_term_len = 0; // allow zero as min for click the button with empty field
		} //end if
		if(min_term_len > 255) {
			min_term_len = 255;
		} //end if
		//--
		const input = $('#' + y_id);
		input.attr('autocomplete', 'off');
		if((y_txt != '') && (y_txt != null)) {
			input.attr('placeholder', y_txt);
		} //end if
		//--
		if(y_type === 'multilist') {
			y_use_bttn = false; // button does not work correctly with multilist as it pops last element from list because that is supposed to be the typed value for suggest search
		} //end if
		//--
		let bttn = null;
		if(y_use_bttn === true) {
			$('<button id="' + y_id + '-AutosuggestBttnAction" data-status="closed">...</button>').insertAfter('#' + y_id);
			bttn = $('#' + y_id + '-AutosuggestBttnAction').click(() => {
				const status = $('#Smart_Suggest_Container').attr('data-status');
				if(status == 'closed') {
					AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode);
				} else {
					AutoSuggestHide();
				} //end if else
			});
		} else {
			if(min_term_len < 1) {
				min_term_len = 1;
			} //end if
		} //end if
		//--
		const arrow_up = 38;
		const arrow_down = 40;
		const enter = 13;
		const tab = 9;
		const esc = 27;
		//--
		let globalTimeout = null;
		//--
		input.dblclick((e) => {
			//--
			$(e.currentTarget).val('');
			//--
		}).blur(() => {
			//--
			if(y_use_bttn !== true) {
				setTimeout(() => { AutoSuggestHide(); }, 500); // it may missbehave on select
			} //end if
			//--
		}).bind('keydown', (evt) => {
			//--
			if(evt.keyCode != enter && evt.keyCode != arrow_down && evt.keyCode != arrow_up && evt.keyCode != tab && evt.keyCode != esc) {
				//--
				if(bttn === null) { // use timeout instead of button
					//--
					if(globalTimeout != null) {
						clearTimeout(globalTimeout);
					} //end if
					//--
					globalTimeout = setTimeout(() => { globalTimeout = null; AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode); }, 500);
					//--
				} //end if
				//--
			} else {
				//--
				evt.preventDefault();
				//--
			} //end if
			//--
			if((evt.keyCode == enter) || evt.keyCode == arrow_down) {
				AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode);
			} //end if
			//--
			return (evt.keyCode != enter);
			//--
		});
		//--
		return input;
		//--
	}; //END
	_C$.bindToInput = bindToInput; // export


	const handleSelection = function(y_type, y_id, id, value, label, data, evcode) {
		//--
		// for evcode the selected items structure is: id (id to display, can be empty, is optional), value (id to select), label (extra description), data (optional, can be a json to be used with JSON.parse(data) to pass extra properties)
		//--
		if(y_type === 'multilist') {
			$('#' + y_id).val(_Utils$.addToList(value, $('#' + y_id).val(), ','));
		} else {
			$('#' + y_id).val(value);
		} //end if else
		//--
		_Utils$.evalJsFxCode( // EV.CTX
			_N$ + '.handleSelection',
			(typeof(evcode) === 'function' ?
				() => {
					'use strict'; // req. strict mode for security !
					(evcode)(id, value, label, data);
				} :
				() => {
					'use strict'; // req. strict mode for security !
					!! evcode ? eval(evcode) : null // already is sandboxed in a method to avoid code errors if using return ; need to be evaluated in this context because of parameters access: id, value, label, data
				}
			)
		);
		//--
		setTimeout(() => { AutoSuggestHide(); }, 100);
		//--
	}; //END
	_C$.handleSelection = handleSelection; // export


	//== PRIVATES


	const AutoSuggestShow = function(input, size, html) {
		//--
		if((size == undefined) || (size == '')) { // undefined tests also for null
			size = 'auto';
		} else {
			size = _Utils$.format_number_int(size, false);
			if(size < 50) {
				size = 50;
			} else if(size > 920) {
				size = 920;
			} //end if
			size = size + 'px';
		} //end if else
		//--
		const offset = input.offset();
		$('#Smart_Suggest_Container').css({
			'position': 'fixed',
			'top': (_Utils$.format_number_int(offset.top - $(window).scrollTop()) + input.outerHeight() + 1) + 'px',
			'left': _Utils$.format_number_int(offset.left - $(window).scrollLeft()) + 'px',
			'width': size,
			'min-width': '25px',
			'max-width': '920px',
			'height': 'auto',
			'min-height': '25px',
			'max-height': '225px',
			'overflow': 'auto',
			'z-index': 9000,
			'text-align': 'left'
		}).show().attr('data-status', 'opened').empty().html(html);
		//--
	}; //END


	const AutoSuggestHide = () => {
		//--
		$('#Smart_Suggest_Container').css({
			'position': 'fixed',
			'top': '-50px',
			'left': '-50px',
			'width': '1px',
			'height': '1px',
			'z-index': 1
		}).empty().html('').attr('data-status', 'closed').hide();
		//--
	}; //END


	const AutoSuggestAction = function(input, y_type, y_size, y_res_url, min_term_len, evcode) {
		//--
		const typedTxt = String(_Utils$.arrayGetLast(_Utils$.stringSplitbyComma(input.val())));
		if(typedTxt.length < min_term_len) {
			return;
		} //end if
		//--
		const theID = String(input.attr('id') || '');
		if(!theID) {
			return;
		} //end if
		//--
		const url = String(String(y_res_url) + String(_Utils$.escape_url(typedTxt)));
		const method = 'POST'; // it works with mixing GET/POST as POST
		//--
		const table_code_start = '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
		const table_code_end = '</table>';
		//--
		const theImg = 'data:image/gif;base64,R0lGODlhEAAQAPQAAN7n7MRCQtzi58hlZ9GXmcRFRcdaW9nM0NSussZQUM+OkM6DhdvV2dKkp9jCxcpwccx5egAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAAFUCAgjmRpnqUwFGwhKoRgqq2YFMaRGjWA8AbZiIBbjQQ8AmmFUJEQhQGJhaKOrCksgEla+KIkYvC6SJKQOISoNSYdeIk1ayA8ExTyeR3F749CACH5BAkKAAAALAAAAAAQABAAAAVoICCKR9KMaCoaxeCoqEAkRX3AwMHWxQIIjJSAZWgUEgzBwCBAEQpMwIDwY1FHgwJCtOW2UDWYIDyqNVVkUbYr6CK+o2eUMKgWrqKhj0FrEM8jQQALPFA3MAc8CQSAMA5ZBjgqDQmHIyEAIfkECQoAAAAsAAAAABAAEAAABWAgII4j85Ao2hRIKgrEUBQJLaSHMe8zgQo6Q8sxS7RIhILhBkgumCTZsXkACBC+0cwF2GoLLoFXREDcDlkAojBICRaFLDCOQtQKjmsQSubtDFU/NXcDBHwkaw1cKQ8MiyEAIfkECQoAAAAsAAAAABAAEAAABVIgII5kaZ6AIJQCMRTFQKiDQx4GrBfGa4uCnAEhQuRgPwCBtwK+kCNFgjh6QlFYgGO7baJ2CxIioSDpwqNggWCGDVVGphly3BkOpXDrKfNm/4AhACH5BAkKAAAALAAAAAAQABAAAAVgICCOZGmeqEAMRTEQwskYbV0Yx7kYSIzQhtgoBxCKBDQCIOcoLBimRiFhSABYU5gIgW01pLUBYkRItAYAqrlhYiwKjiWAcDMWY8QjsCf4DewiBzQ2N1AmKlgvgCiMjSQhACH5BAkKAAAALAAAAAAQABAAAAVfICCOZGmeqEgUxUAIpkA0AMKyxkEiSZEIsJqhYAg+boUFSTAkiBiNHks3sg1ILAfBiS10gyqCg0UaFBCkwy3RYKiIYMAC+RAxiQgYsJdAjw5DN2gILzEEZgVcKYuMJiEAOwAAAAAAAAAAAA==';
		//--
		AutoSuggestShow(input, y_size, '<div><center><img src="' + _Utils$.escape_html(theImg) + '"></center></div>');
		//--
		setTimeout(() => {
			//--
			$.ajax({
				async: true,
				cache: false,
				timeout: 0,
				type: method,
				url: url,
				data: '',
				dataType: 'json',
				success: (answer) => {
					//--
					if(!(answer instanceof Array)) {
						return;
					} //end if
					//--
					let extcols = 1;
					for(let i=0; i<answer.length; i++) {
						if(answer[i].label instanceof Array) {
							extcols = Math.max(extcols, answer[i].label.length);
						} //end if
					} //end for
					let cols = 0;
					//--
					let el = 0;
					let suggest = table_code_start;
					for(let i=0; i<answer.length; i++) {
						//--
						suggest += '<tr valign="top" onclick="' + _Utils$.escape_html(String(_N$)) + '.handleSelection(\'' + _Utils$.escape_js(String(y_type)) + '\', \'' + _Utils$.escape_js(String(theID)) + '\', \'' + _Utils$.escape_js(String(answer[i].id)) + '\', \'' + _Utils$.escape_js(String(answer[i].value)) + '\', \'' + _Utils$.escape_js(typeof(answer[i].label) == 'string' ? String(answer[i].label) : JSON.stringify(answer[i].label)) + '\', \'' + _Utils$.escape_js(JSON.stringify(answer[i].data)) + '\', ';
						if(typeof(evcode) === 'function') {
							suggest += String(evcode); // cast function to string !
						} else {
							suggest += '\'' + _Utils$.escape_js(evcode) + '\'';
						} //end if else
						suggest += ');" title="' + _Utils$.escape_html(el+1) + '">';
						//--
						suggest += '<th>';
						suggest += _Utils$.escape_html(answer[i].id);
						suggest += '</th>';
						//--
						if(answer[i].label instanceof Array) {
							cols = answer[i].label.length;
							for(let j=0; j<cols; j++) {
								if(answer[i].label[j].hasOwnProperty('color')) {
									suggest += '<td style="color:' + _Utils$.escape_html(answer[i].label[j].color) + '!important;">' + _Utils$.escape_html(answer[i].label[j].text) + '</td>';
								} else {
									suggest += '<td>' + _Utils$.escape_html(answer[i].label[j]) + '</td>';
								} //end if else
							} //end for
						} else {
							cols = 1;
							suggest += '<td>' + _Utils$.escape_html(answer[i].label) + '</td>';
						} //end if else
						//--
						if(cols < extcols) { // fix
							for(let k=0; k<(extcols - cols); k++) {
								suggest += '<td>&nbsp;</td>';
							} //end for
						} //end if
						//--
						suggest += '</tr>';
						//--
						el += 1;
						//--
					} //end for
					suggest += table_code_end;
					//-- set div content
					if(el > 0) {
						AutoSuggestShow(input, y_size, suggest);
					} else {
						AutoSuggestShow(input, y_size, table_code_start + '<tr><td> [No Matching Results] </td></tr>' + table_code_end);
					} //end if
					//-- cleanup
					el = 0;
					suggest = '';
					//--
				}, //END
				error: (answer) => {
					//--
					AutoSuggestShow(input, y_size, '<div style="background:#FF3300;">ERROR (JS-Smart-Suggest): Invalid Server Response !<br>' + $('<div>' + answer.responseText + '</div>').text() + '</div>');
					//--
				} //END
			});
			//--
		}, 250);
		//--
	}; //END


	//==
	$(() => {
		//-- requires: smart-suggest.css
		$('body').append('<!-- SmartJS.AutoSuggest :: Start --><div id="Smart_Suggest_Container"></div><!-- END: SmartJS.AutoSuggest -->');
		//--
		AutoSuggestHide();
		//--
	}); //END DOCUMENT READY
	//==


}}; //END CLASS


SmartAutoSuggest.secureClass(); // implements class security

window.SmartAutoSuggest = SmartAutoSuggest; // global export


//==================================================================
//==================================================================


// #END
