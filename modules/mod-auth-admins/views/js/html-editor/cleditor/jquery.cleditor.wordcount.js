
// CLEditor Word Count
// (c) 2015-2019 unix-world.org
// v.20191214

(function($) {

	// Define the table button
	$.cleditor.buttons.wordcount = {
		name: "wordcount",
		image: "wordcount.png",
		title: "Words Counter",
		command: "",
		popupName: "wordcount",
		popupClass: "cleditorPrompt",
		popupContent: '<table width="225"><tr><td><b>Words Count: </td><td align="right"><input type="text" id="cledit_uxm_word_counter" value="#" class="ux-field" style="width:96px; text-align:right; padding-left:5px; padding-right:5px; background:#F7F7F7;" readonly></td></tr>' +
					  '<tr><td><b>Chars Count: </td><td align="right"><input type="text" id="cledit_uxm_wordchar_counter" value="#" class="ux-field" style="width:96px; text-align:right; padding-left:5px; padding-right:5px; background:#F7F7F7;" readonly></td></tr></table>',
		buttonClick: wordcountButtonClick
	};

	// Add the button to the default controls
	$.cleditor.defaultOptions.controls = $.cleditor.defaultOptions.controls.replace("rule ", "wordcount | rule ");

	// Table button click event handler
	function wordcountButtonClick(e, data) {

		// Get the editor
		var editor = data.editor;

		// Get the column and row count
		var $text = $(data.popup).find(":text");

		var the_wcount = '' + $text[0].value;
		var the_wcharcnt = '' + $text[1].value;

		var the_text = editor.$area.val();
		the_text = $('<div>' + the_text + '</div>').text();

		var the_words = [];
		if((typeof the_text != 'undefined') && (the_text != '') && (the_text !== null) && (the_text !== NaN)) {
			the_words = the_text.replace(/^\s\s*/, '').replace(/\s\s*$/, '').replace(/\s+/gi, ' ').split(' '); // trim + split
		} //end if

		$text[0].value = 0 + parseInt(the_words.length);
		$text[1].value = the_text.replace(/^\s\s*/, '').replace(/\s\s*$/, '').length;

		the_words = [];
		the_text = '';

		editor.hidePopups();
		editor.focus();

	} //END FUNCTION

})(jQuery);
