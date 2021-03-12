
// [LIB - SmartFramework / JS / Smart Suggest - LightJsUI]
// (c) 2006-2020 unix-world.org - all rights reserved
// v.20210312

// DEPENDS: jQuery, SmartJS_CoreUtils
// REQUIRES-CSS: lib/js/jquery/autosuggest/smart-suggest.css

//==================================================================
//==================================================================

//================== [OK:evcode]

var SmartLoaderAutoSuggest = ''; // 'lib/js/jquery/autosuggest/img/ajax_loader.gif'; // *** optional ***

var SmartAutoSuggest = new function() {

// :: static

var _class = this; // self referencing

//=======================================

this.bindToInput = function(y_type, y_id, y_txt, y_res_url, y_use_bttn, y_size, min_term_len, evcode) {
	//--
	min_term_len = parseInt(min_term_len);
	if(min_term_len < 0) {
		min_term_len = 0; // allow zero as min for click the button with empty field
	} //end if
	if(min_term_len > 255) {
		min_term_len = 255;
	} //end if
	//--
	var input = jQuery('#' + y_id);
	input.attr('autocomplete', 'off');
	if((y_txt != '') && (y_txt != null)) {
		input.attr('placeholder', y_txt);
	} //end if
	//--
	if(y_type === 'multilist') {
		y_use_bttn = false; // button does not work correctly with multilist as it pops last element from list because that is supposed to be the typed value for suggest search
	} //end if
	//--
	var bttn = null;
	if(y_use_bttn === true) {
		jQuery('<button id="' + y_id + '-AutosuggestBttnAction" data-status="closed">...</button>').insertAfter('#' + y_id);
		bttn = jQuery('#' + y_id + '-AutosuggestBttnAction').click(function(){
			var status = jQuery('#Smart_Suggest_Container').attr('data-status');
			if(status == 'closed') {
				AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode);
			} else {
				_class.AutoSuggestHide();
			} //end if else
		});
	} else {
		if(min_term_len < 1) {
			min_term_len = 1;
		} //end if
	} //end if
	//--
	var arrow_up = 38;
	var arrow_down = 40;
	var enter = 13;
	var tab = 9
	var esc = 27;
	//--
	var globalTimeout = null;
	//--
	input.dblclick(function() {
		//--
		$(this).val('');
		//--
	}).blur(function() {
		//--
		if(y_use_bttn !== true) {
			setTimeout(function(){ SmartAutoSuggest.AutoSuggestHide(); }, 500); // this may missbehave on select
		} //end if
		//--
	}).bind('keydown', function(event) {
		//--
		if(event.keyCode != enter && event.keyCode != arrow_down && event.keyCode != arrow_up && event.keyCode != tab && event.keyCode != esc) {
			//--
			if(bttn === null) { // use timeout instead of button
				//--
				if(globalTimeout != null) {
					clearTimeout(globalTimeout);
				} //end if
				//--
				globalTimeout = setTimeout(function() {
					//--
					globalTimeout = null;
					//--
					AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode);
					//--
				}, 500); // delay: 500
				//--
			} //end if
			//--
		} else {
			//--
			event.preventDefault();
			//--
		} //end if
		//--
		if((event.keyCode == enter) || event.keyCode == arrow_down) {
			AutoSuggestAction(input, y_type, y_size, y_res_url, min_term_len, evcode);
		} //end if
		//--
		return (event.keyCode != enter);
		//--
	});
	//--
	return input;
	//--
} //END FUNCTION

//=======================================

this.handleSelection = function(y_type, y_id, id, value, label, data, evcode) {
	//--
	// for evcode the selected items structure is: id (id to display, can be empty, is optional), value (id to select), label (extra description), data (optional, can be a json to be used with JSON.parse(data) to pass extra properties)
	//--
	if(y_type === 'multilist') {
		jQuery('#' + y_id).val(SmartJS_CoreUtils.addToList(value, jQuery('#' + y_id).val(), ','));
	} else {
		jQuery('#' + y_id).val(value);
	} //end if else
	//--
	if((typeof evcode != 'undefined') && (evcode != 'undefined') && (evcode != null) && (evcode != '')) {
		try { // FIX: evcode can be: null, function, string-to-eval
			if(typeof evcode === 'function') {
				evcode(id, value, label, data); // call :: sync params ui-autosuggest
			} else {
				eval('(function(){ ' + evcode + ' })();'); // sandbox
			} //end if else
		} catch(err) {
			console.log('SmartAutoSuggest ERROR: JS-Eval Error on Element: ' + y_id + '\nDetails: ' + err);
		} //end try catch
	} //end if
	//--
	setTimeout(function(){
		_class.AutoSuggestHide();
	}, 100);
	//--
} //END FUNCTION

//=======================================

this.AutoSuggestHide = function() {
	//--
	jQuery('#Smart_Suggest_Container').css({
		'position': 'fixed',
		'top': '-50px',
		'left': '-50px',
		'width': '1px',
		'height': '1px',
		'z-index': 1
	}).empty().html('').attr('data-status', 'closed').hide();
	//--
} //END FUNCTION

//=======================================

var AutoSuggestShow = function(input, y_size, html) {
	//--
	if((typeof y_size == 'undefined') || (y_size == null) || (y_size == '')) {
		y_size = 'auto';
	} else {
		y_size = parseInt(y_size);
		if(y_size < 50) {
			y_size = 50;
		} else if(y_size > 920) {
			y_size = 920;
		} //end if
		y_size = y_size + 'px';
	} //end if else
	//--
	var offset = input.offset();
	jQuery('#Smart_Suggest_Container').css({
		'position': 'fixed',
		'top': (parseInt(offset.top - jQuery(window).scrollTop()) + input.outerHeight() + 1) + 'px',
		'left': parseInt(offset.left - jQuery(window).scrollLeft()) + 'px',
		'width': y_size,
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
} //END FUNCTION

//=======================================

var AutoSuggestAction = function(input, y_type, y_size, y_res_url, min_term_len, evcode) {
	//--
	var typedTxt = String(SmartJS_CoreUtils.arrayGetLast(SmartJS_CoreUtils.stringSplitbyComma(input.val())));
	if(typedTxt.length < min_term_len) {
		return;
	} //end if
	//--
	var theID = input.attr('id');
	//--
	var url = String(String(y_res_url) + String(encodeURIComponent(typedTxt)));
	var method = 'POST'; // it works with mixing GET/POST as POST
	//--
	var table_code_start = '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
	var table_code_end = '</table>';
	//--
	//console.log(method + ' @ ' + url);
	var theImg = 'data:image/gif;base64,R0lGODlhEAAQAPQAAN7n7MRCQtzi58hlZ9GXmcRFRcdaW9nM0NSussZQUM+OkM6DhdvV2dKkp9jCxcpwccx5egAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAAFUCAgjmRpnqUwFGwhKoRgqq2YFMaRGjWA8AbZiIBbjQQ8AmmFUJEQhQGJhaKOrCksgEla+KIkYvC6SJKQOISoNSYdeIk1ayA8ExTyeR3F749CACH5BAkKAAAALAAAAAAQABAAAAVoICCKR9KMaCoaxeCoqEAkRX3AwMHWxQIIjJSAZWgUEgzBwCBAEQpMwIDwY1FHgwJCtOW2UDWYIDyqNVVkUbYr6CK+o2eUMKgWrqKhj0FrEM8jQQALPFA3MAc8CQSAMA5ZBjgqDQmHIyEAIfkECQoAAAAsAAAAABAAEAAABWAgII4j85Ao2hRIKgrEUBQJLaSHMe8zgQo6Q8sxS7RIhILhBkgumCTZsXkACBC+0cwF2GoLLoFXREDcDlkAojBICRaFLDCOQtQKjmsQSubtDFU/NXcDBHwkaw1cKQ8MiyEAIfkECQoAAAAsAAAAABAAEAAABVIgII5kaZ6AIJQCMRTFQKiDQx4GrBfGa4uCnAEhQuRgPwCBtwK+kCNFgjh6QlFYgGO7baJ2CxIioSDpwqNggWCGDVVGphly3BkOpXDrKfNm/4AhACH5BAkKAAAALAAAAAAQABAAAAVgICCOZGmeqEAMRTEQwskYbV0Yx7kYSIzQhtgoBxCKBDQCIOcoLBimRiFhSABYU5gIgW01pLUBYkRItAYAqrlhYiwKjiWAcDMWY8QjsCf4DewiBzQ2N1AmKlgvgCiMjSQhACH5BAkKAAAALAAAAAAQABAAAAVfICCOZGmeqEgUxUAIpkA0AMKyxkEiSZEIsJqhYAg+boUFSTAkiBiNHks3sg1ILAfBiS10gyqCg0UaFBCkwy3RYKiIYMAC+RAxiQgYsJdAjw5DN2gILzEEZgVcKYuMJiEAOwAAAAAAAAAAAA==';
	if(SmartLoaderAutoSuggest) {
		theImg = String(SmartLoaderAutoSuggest);
	} //end if
	AutoSuggestShow(input, y_size, '<div><center><img src="' + theImg + '"></center></div>');
	//--
	setTimeout(function(){
		//--
		jQuery.ajax({
			async: true,
			cache: false,
			timeout: 0,
			type: method,
			url: url,
			data: '',
			dataType: 'json',
			success: function(answer) {
				//--
				if(answer instanceof Array) {
					// OK
				} else {
					return;
				} //end if
				//--
				var extcols = 1;
				for(var i=0; i<(answer.length); i++) {
					if(answer[i].label instanceof Array) {
						extcols = Math.max(extcols, answer[i].label.length);
					} //end if
				} //end for
				var cols = 0;
				//--
				var el = 0;
				var suggest = table_code_start;
				for(var i=0; i<(answer.length); i++) {
					//--
					suggest += '<tr valign="top" onclick="SmartAutoSuggest.handleSelection(\'' + SmartJS_CoreUtils.escape_js(y_type) + '\', \'' + SmartJS_CoreUtils.escape_js(theID) + '\', \'' + SmartJS_CoreUtils.escape_js(answer[i].id) + '\', \'' + SmartJS_CoreUtils.escape_js(answer[i].value) + '\', \'' + SmartJS_CoreUtils.escape_js(answer[i].label) + '\', \'' + SmartJS_CoreUtils.escape_js(answer[i].data) + '\', ';
					if(typeof evcode === 'function') {
						suggest += String(evcode);
					} else {
						suggest += '\'' + SmartJS_CoreUtils.escape_js(evcode) + '\'';
					} //end if else
					suggest += ');" title="' + SmartJS_CoreUtils.escape_html(el+1) + '">';
					//--
					suggest += '<th>';
					suggest += SmartJS_CoreUtils.escape_html(answer[i].id);
					suggest += '</th>';
					//--
					if(answer[i].label instanceof Array) {
						cols = answer[i].label.length;
						for(var j=0; j<cols; j++) {
							if(answer[i].label[j].hasOwnProperty('color')) {
								suggest += '<td style="color:' + SmartJS_CoreUtils.escape_html(answer[i].label[j].color) + '!important;">' + SmartJS_CoreUtils.escape_html(answer[i].label[j].text) + '</td>';
							} else {
								suggest += '<td>' + SmartJS_CoreUtils.escape_html(answer[i].label[j]) + '</td>';
							} //end if else
						} //end for
					} else {
						cols = 1;
						suggest += '<td>' + SmartJS_CoreUtils.escape_html(answer[i].label) + '</td>';
					} //end if else
					//--
					if(cols < extcols) { // fix
						for(var k=0; k<(extcols - cols); k++) {
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
			}, //END FUNCTION
			error: function(answer) {
				//--
				AutoSuggestShow(input, y_size, '<div style="background:#FF3300;">ERROR (JS-Smart-Suggest): Invalid Server Response !<br>' + jQuery('<div>' + answer.responseText + '</div>').text() + '</div>');
				//--
			} //END FUNCTION
		});
		//--
	}, 250);
	//--
} //END FUNCTION

//=======================================

} //END CLASS

//=======================================
//=======================================

jQuery(function() {
	//-- requires: <link rel="stylesheet" type="text/css" href="lib/js/jquery/autosuggest/smart-suggest.css">
	jQuery('body').append('<!-- SmartJS.AutoSuggest :: Start --><div id="Smart_Suggest_Container"></div><!-- END: SmartJS.AutoSuggest -->');
	//--
	SmartAutoSuggest.AutoSuggestHide();
	//--
}); //END DOCUMENT READY


//==================================================================
//==================================================================


// #END
