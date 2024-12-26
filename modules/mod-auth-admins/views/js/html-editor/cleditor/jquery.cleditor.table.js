/*
 CLEditor Table Plugin v1.0.4
 http://premiumsoftware.net/cleditor
 requires CLEditor v1.2.2 or later
 Copyright 2010, Chris Landowski, Premium Software, LLC
 Dual licensed under the MIT or GPL Version 2 licenses.
*/

// modified by unixman: 20191214

(function ($) {

	// Define the table button
	$.cleditor.buttons.table = {
		name: 'table',
		image: 'table.png',
		title: 'Simple Table',
		command: 'inserthtml',
		popupName: 'table',
		popupClass: 'cleditorPrompt',
		popupContent:
			'<table>' +
			'<tr><td>Cols: </td><td><input class="ux-field" type="text" value="4" style="width:40px"></td></tr>' +
			'<tr><td>Rows: </td><td><input class="ux-field" type="text" value="4" style="width:40px"></td></tr>' +
			'</table><input class="ux-button ux-button-small ux-button-secondary" style="width:99%;" type="button" value="Insert Table">',
		buttonClick: tableButtonClick
	};

	// Add the button to the default controls
	$.cleditor.defaultOptions.controls = $.cleditor.defaultOptions.controls
		.replace('rule ', 'rule table ');

	// Table button click event handler
	function tableButtonClick(e, data) {

		// Wire up the submit button click event handler
		$(data.popup).children(':button')
			.unbind('click')
			.bind('click', function (e) {

				// Get the editor
				var editor = data.editor;

				// Get the column and row count
				var $text = $(data.popup).find(':text'),
					cols = parseInt($text[0].value),
					rows = parseInt($text[1].value);

				// Build the html
				var html, x, y;
				if (cols > 0 && rows > 0) {
					html = '<table cellpadding="2" cellspacing="2" border="0" style="width:100%;">'; // responsive table
					for (y = 0; y < rows; y++) {
						html += '<tr>';
						for (x = 0; x < cols; x++)
							html += '<td>' + x + ',' + y + '</td>';
						html += '</tr>';
					}
					html += '</table><br>';
				}

				// Insert the html
				if(html) {
					editor.execCommand(data.command, html, null, data.button);
				} //end if

				// Reset the text, hide the popup and set focus
				$text.val('4');
				editor.hidePopups();
				editor.focus();

		  });

	}

})(jQuery);
