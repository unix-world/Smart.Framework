/**
 CLEditor SmartFramework/Components Image Plugin v.1.2
 requires CLEditor v1.2.2 or later

 Copyright 2011-2019, unix-world.org (unixman)
 Dual licensed under the MIT or GPL Version 2 licenses.
*/

// v.20210411

//-- settings
var CLEditor_SmartFrameworkComponents_fileBrowserCallURL = '';
if(typeof CLEditor_SmartFrameworkComponents_fileBrowserCallBackURL != undefined) {
	if(CLEditor_SmartFrameworkComponents_fileBrowserCallBackURL != '') {
		CLEditor_SmartFrameworkComponents_fileBrowserCallURL = '' + CLEditor_SmartFrameworkComponents_fileBrowserCallBackURL;
	} //end if
} //end if
var CLEditor_SmartFrameworkComponents_fileBrowserImgLibBttn = '<span style="color:#DDDDDD;">No Image Library Set</span>';
if(CLEditor_SmartFrameworkComponents_fileBrowserCallURL != '') {
	CLEditor_SmartFrameworkComponents_fileBrowserImgLibBttn = '<button class="ux-button ux-button-small ux-button-secondary" style="width:215px;" onClick="smartJ$Browser.PopUpLink(CLEditor_SmartFrameworkComponents_fileBrowserCallURL, \'wnd__net__filemanager_imgsel\', 920, 560, 1); return false;" title="Pick-up an Image from Library">Browse the Image Library</button>';
} //end if else
//--

//-- external integration
var CLEditor_SmartFrameworkComponents_fileBrowserCallValue = '';
function CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(yUrl) {
	CLEditor_SmartFrameworkComponents_fileBrowserCallValue = '' + yUrl;
	$('#CLEditor__FileManager__FieldSmartFrameworkComponents').val(CLEditor_SmartFrameworkComponents_fileBrowserCallValue);
	$('#CLEditor__FileManager__SmartFrameworkComponents').html('<center><div style="width:450px; height:270px;"><img src="' + smartJ$Utils.escape_html(CLEditor_SmartFrameworkComponents_fileBrowserCallValue) + '" style="max-width:100%!important; max-height:96%!important;" alt="Selected Image: ' + smartJ$Utils.escape_html(CLEditor_SmartFrameworkComponents_fileBrowserCallValue) + '" title="Selected Image: ' + smartJ$Utils.escape_html(CLEditor_SmartFrameworkComponents_fileBrowserCallValue) + '"></div></center>');
} //END FUNCTION
//--

(function($) {

	// Define the table button
	$.cleditor.buttons.smartframeworkcomponents_filemanager_img = {
		name: "smartframeworkcomponents_filemanager_img",
		image: "image.png",
		title: "Insert Image",
		command: "inserthtml",
		popupName: "smartframeworkcomponents_filemanager_img",
		popupClass: "cleditorPrompt",
		popupContent:
		  '<br><center><table cellpadding="2" cellspacing="0" width="450">' +
		  '<tr>' +
		  '<td align="left"><input id="CLEditor__FileManager__FieldSmartFrameworkComponents" class="ux-field" type="text" value="" placeholder="Image URL" title="The Relative Image URL (local/image.png) or the Absolute Image URL (http://remote.url/image.png) or Embedded Image (data:image/png;base64,...)" style="width:255px;" onClick="if(this.value != \'\') { CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(this.value); }" onBlur="if(this.value != \'\') { CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(this.value); }"></td>' +
		  '<td> &nbsp; </td>' +
		  '<td align="right">' + CLEditor_SmartFrameworkComponents_fileBrowserImgLibBttn + '</td>' +
		  '</tr>' +
		  '<tr>' +
		  '<td align="center" colspan="3"><input id="CLEditor__FileManager__AltSmartFrameworkComponents" class="ux-field" type="text" value="" maxlength="255" placeholder="Alternate Text" title="Alternate Text" style="width:100%;"></td>' +
//		  '<td align="center"><input id="CLEditor__FileManager__AltSmartFrameworkComponents" type="text" class="ux-field" value="" maxlength="255" placeholder="Alternate Text" title="Alternate Text" style="width:210px;"></td>' +
//		  '<td>&nbsp;&nbsp;&nbsp;</td>' +
//		  '<td align="center"><input id="CLEditor__FileManager__TitleSmartFrameworkComponents" type="text" class="ux-field" value="" maxlength="255" placeholder="Title Text" title="Title Text" style="width:210px;"></td>' +
		  '</tr>' +
		  '<tr>' +
		  '<td colspan="3" align="center"><button id="CLEditor__Bttn__FileManager__SmartFrameworkComponents" title="Insert the selected Image to Editor" class="ux-button ux-button-small ux-button-secondary" style="width:250px;">Insert the Selected Image</button></td>' +
		  '</tr>' +
		  '</table></center><br><div id="CLEditor__FileManager__SmartFrameworkComponents" style="width:480px; height:auto; min-height:20px; max-height:320px; background:#FFFFFF; border:1px solid #ECECEC; overflow:auto;"></div>'
		  ,
		buttonClick: smartframeworkcomponents_filemanager_img_ButtonClick
	};

	// Add the button to the default controls
	$.cleditor.defaultOptions.controls = $.cleditor.defaultOptions.controls.replace("image ", "smartframeworkcomponents_filemanager_img "); // "smartframeworkcomponents_filemanager_img image "

	// Table button click event handler
	function smartframeworkcomponents_filemanager_img_ButtonClick(e, data) {

		// Wire up the submit button click event handler
		$(data.popup).find('button#CLEditor__Bttn__FileManager__SmartFrameworkComponents').unbind("click").bind("click", function(e) {

			var $text = $(data.popup).find(":text");
			var the_img_url = '' + $text[0].value;
			var the_img_alt = '' + $text[1].value;
			var the_img_title = '' + $text[1].value;
			//var the_img_title = '' + $text[2].value;

			// Get the editor
			var editor = data.editor;

			// Build the html
			var html = '';
			if((CLEditor_SmartFrameworkComponents_fileBrowserCallValue != undefined) && (CLEditor_SmartFrameworkComponents_fileBrowserCallValue != '')) {
				html = '<img src="' + CLEditor_SmartFrameworkComponents_fileBrowserCallValue + '" style="max-width:100%!important;" alt="' + smartJ$Utils.escape_html(the_img_alt) + '" title="' + smartJ$Utils.escape_html(the_img_title) + '">';
			} else {
				alert('No image has been selected !');
			} //end if

			CLEditor_SmartFrameworkComponents_fileBrowserCallValue = ''; // clear

			// Insert the html
			if(html) {
			  editor.execCommand(data.command, html, null, data.button);
			  $(data.popup).find('div#CLEditor__FileManager__SmartFrameworkComponents').html(''); // clear the image
			  $(data.popup).find('input#CLEditor__FileManager__FieldSmartFrameworkComponents').val('');
			  $(data.popup).find('input#CLEditor__FileManager__AltSmartFrameworkComponents').val('');
			  $(data.popup).find('input#CLEditor__FileManager__TitleSmartFrameworkComponents').val('');
			} //end if

			// Reset the text, hide the popup and set focus
			editor.hidePopups();
			editor.focus();

		});

	} //END FUNCTION

})(jQuery);

// #END
