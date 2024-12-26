
// jQuery WYSIWYG HTML Editor
// v.20220906
// enhanced by unixman (c) unix-world.org
// 		* many fixes includding allow script tags: <script></script>
// 		* depends on jquery.htmlcleaner.js
//		* fixes for newer chromium / webkit
//		* add support for custom stylesheet

// LICENSE: Dual licensed under the MIT or GPL Version 2 licenses.
// This script is based on: CLEditor WYSIWYG HTML Editor v1.4.5 (http://premiumsoftware.net/CLEditor), Copyright 2010, Chris Landowski, Premium Software, LLC

if(!jQuery().htmlClean) { // unixman
	throw 'ERROR: This CkEditor version requires the jQuery htmlClean';
} //end if

(function ($) {

	//==============
	// jQuery Plugin
	//==============

	$.cleditor = {

		// Define the defaults used for all new cleditor instances
		defaultOptions: {
			width:        550, //'auto', // width not including margins, borders or padding
			height:       250, // height not including margins, borders or padding
			controls:     // controls to add to the toolbar
						  /* unixman
						  "bold italic underline strikethrough subscript superscript | font size " +
						  "style | color highlight removeformat | bullets numbering | outdent " +
						  "indent | alignleft center alignright justify | undo redo | " +
						  "rule image link unlink | cut copy paste pastetext | print source",
						  */
						  "| source | rule image | link unlink | bold italic underline | strikethrough subscript superscript | size " +
						  "style | color highlight | removeformat | bullets numbering | outdent " +
						  "indent | alignleft center alignright justify | pastetext | undo redo | ",
			colors:       // colors in the color popup
						  "FFF FCC FC9 FF9 FFC 9F9 9FF CFF CCF FCF " +
						  "CCC F66 F96 FF6 FF3 6F9 3FF 6FF 99F F9F " +
						  "BBB F00 F90 FC6 FF0 3F3 6CC 3CF 66C C6C " +
						  "999 C00 F60 FC3 FC0 3C0 0CC 36F 63F C3C " +
						  "666 900 C60 C93 990 090 399 33F 60C 939 " +
						  "333 600 930 963 660 060 366 009 339 636 " +
						  "000 300 630 633 330 030 033 006 309 303",
			fonts:        // font names in the font popup
						  //"Arial,Arial Black,Comic Sans MS,Courier New,Narrow,Garamond," + // unixman
						  //"Georgia,Impact,Sans Serif,Serif,Tahoma,Trebuchet MS,Verdana", // unixman
						  "IBM Plex Sans, IBM Plex Serif, IBM Plex Mono, Arial, Tahoma, Courier New", // unixman
			sizes:        // sizes in the font size popup
						  //"1,2,3,4,5,6,7", // unixman
						  "1,2,3,4,5", // unixman
			styles:       // styles in the style popup
						  [["Paragraph", "<p>"], ["Header 1", "<h1>"], ["Header 2", "<h2>"],
						  ["Header 3", "<h3>"], ["Header 4", "<h4>"], ["Header 5", "<h5>"]],
			useCSS: 	  false, // use CSS to style HTML when possible (not supported in ie)
			docType:      // Document type contained within the editor
						  //'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">', // unixman
						  '<!DOCTYPE html>', // unixman
			docCSSFile:   // CSS file used to style the document contained within the editor
						  "",
			bodyStyle:    // style to assign to document body contained within the editor
						  //"margin:4px; font:10pt Arial,Verdana; cursor:text" // unixman
						  "background:#FFFFFF; margin:4px; font-size:13px; cursor:text;", // unixman
			allowedTags: [], // unixman
			allowedAttributes: [ // unixman
							"script", ["src"]
							], // {{{SYNC-HTML-CLEAN-DEFAULTS}}}
			removeTags: [ // unixman
							"?xml", "!doctype", "html", "head", "body", "title", "style", "link", // allow: "meta"
							"base", "basefont", "dir", "isindex", "menu", "command", "keygen",
							"frame", "frameset", "noframes", "iframe",
							"noscript", "script",
							"embed", "object", "param",
							"header", "main", "footer", "aside",
						]
		},

		// Define all usable toolbar buttons - the init string property is
		//   expanded during initialization back into the buttons object and
		//   separate object properties are created for each button.
		//   e.g. buttons.size.title = "Font Size"
		buttons: {
			// name,title,command,popupName (""=use name)
			init:
			"bold,,|" +
			"italic,,|" +
			"underline,,|" +
			"strikethrough,,|" +
			"subscript,,|" +
			"superscript,,|" +
			"font,,fontname,|" +
			"size,Font Size,fontsize,|" +
			"style,,formatblock,|" +
			"color,Font Color,forecolor,|" +
			"highlight,Text Highlight Color,hilitecolor,color|" +
			"removeformat,Remove Formatting,|" +
			"bullets,,insertunorderedlist|" +
			"numbering,,insertorderedlist|" +
			"outdent,,|" +
			"indent,,|" +
			"alignleft,Align Text Left,justifyleft|" +
			"center,,justifycenter|" +
			"alignright,Align Text Right,justifyright|" +
			"justify,,justifyfull|" +
			"undo,,|" +
			"redo,,|" +
			"rule,Insert Horizontal Rule,inserthorizontalrule|" +
			"image,Insert Image from URL,insertimage,url|" +
			"link,Insert Hyperlink,createlink,url|" +
			"unlink,Remove Hyperlink,|" +
			"cut,,|" +
			"copy,,|" +
			"paste,,|" +
			"pastetext,Insert Code,inserthtml,|" +
			"print,,|" +
			"source,Show Source"
		},

		// imagesPath - returns the path to the images folder
		imagesPath: function() {
			return imagesPath();
		}, //end function

		// by unixman
		editorPath: function() {
			return editorPath();
		}

	};

	// cleditor - creates a new editor for each of the matched textareas
	$.fn.cleditor = function (options) {

		// Create a new jQuery object to hold the results
		var $result = $([]);

		// Loop through all matching textareas and create the editors
		this.each(function (idx, elem) {
			if(elem.tagName.toUpperCase() === "TEXTAREA") {
				var data = $.data(elem, CLEDITOR);
				if(!data) {
					data = new cleditor(elem, options);
				} //end if
				$result = $result.add(data);
			} //end if
		});

		// return the new jQuery object
		return $result;

	};

	//==================
	// Private Variables
	//==================

	var

	// Misc constants
	BACKGROUND_COLOR = "backgroundColor",
	BLURRED = "blurred",
	BUTTON = "button",
	BUTTON_NAME = "buttonName",
	CHANGE = "change",
	CLEDITOR = "cleditor",
	CLICK = "click",
	DISABLED = "disabled",
	DIV_TAG = "<div>",
	FOCUSED = "focused",
	TRANSPARENT = "transparent",
	UNSELECTABLE = "unselectable",

	// Class name constants
	MAIN_CLASS = "cleditorMain",    // main containing div
	TOOLBAR_CLASS = "cleditorToolbar", // toolbar div inside main div
	GROUP_CLASS = "cleditorGroup",   // group divs inside the toolbar div
	BUTTON_CLASS = "cleditorButton",  // button divs inside group div
	DISABLED_CLASS = "cleditorDisabled",// disabled button divs
	DIVIDER_CLASS = "cleditorDivider", // divider divs inside group div
	POPUP_CLASS = "cleditorPopup",   // popup divs inside body
	LIST_CLASS = "cleditorList",    // list popup divs inside body
	COLOR_CLASS = "cleditorColor",   // color popup div inside body
	PROMPT_CLASS = "cleditorPrompt",  // prompt popup divs inside body
	MSG_CLASS = "cleditorMsg",     // message popup div inside body

	// Browser detection
	ua = navigator.userAgent.toLowerCase(),
	ie = /msie/.test(ua),
	iege11 = /(trident)(?:.*rv:([\w.]+))?/.test(ua),
	//webkit = /webkit/.test(ua),
	webkit = /webkit/.test(ua) || /chrome/.test(ua) || /safari/.test(ua), // bug fix :: unixman

	// Test for iPhone/iTouch/iPad
//	iOS = /iPhone|iPad|iPod/i.test(ua),

	// Popups are created once as needed and shared by all editor instances
	popups = {},

	// Used to prevent the document click event from being bound more than once
	documentClickAssigned,

	// Local copy of the buttons object
	buttons = $.cleditor.buttons;

	//===============
	// Initialization
	//===============

	// Expand the buttons.init string back into the buttons object
	//   and create seperate object properties for each button.
	//   e.g. buttons.size.title = "Font Size"
	$.each(buttons.init.split("|"), function (idx, button) {
		var items = button.split(","), name = items[0];
		buttons[name] = {
			stripIndex: idx,
			name: name,
			image: name + '.png', // added by unixman to load icons instead the map
			title: items[1] === "" ? name.charAt(0).toUpperCase() + name.substr(1) : items[1],
			command: items[2] === "" ? name : items[2],
			popupName: items[3] === "" ? name : items[3]
		};
	});

	delete buttons.init;

	//============
	// Constructor
	//============

	// cleditor - creates a new editor for the passed in textarea element
	cleditor = function (area, options) {

		var editor = this;

		// Get the defaults and override with options
		editor.options = options = $.extend({}, $.cleditor.defaultOptions, options);

		// Hide the textarea and associate it with this editor
		var $area = editor.$area = $(area)
			.css({ border: "none", margin: 0, padding: 0 }) // Needed for IE 7 (won't work in CSS file)
			.hide()
			.data(CLEDITOR, editor)
			.blur(function () {
				// Update the iframe when the textarea loses focus
				//console.log('area blurred');
				updateFrame(editor);
				//-- re-sync (unixman)
				updateTextArea(editor);
				updateFrame(editor);
				//-- #
			});

		// Create the main container
		var $main = editor.$main = $(DIV_TAG)
			.addClass(MAIN_CLASS)
			.width(options.width)
			.height(options.height);

		// Create the toolbar
		var $toolbar = editor.$toolbar = $(DIV_TAG)
			.addClass(TOOLBAR_CLASS)
			.appendTo($main);

		// Add the first group to the toolbar
		var $group = $(DIV_TAG)
			.addClass(GROUP_CLASS)
			.appendTo($toolbar);

		// Initialize the group width
		var groupWidth = 0;

		// Add the buttons to the toolbar
		$.each(options.controls.split(" "), function(idx, buttonName) {

			if(buttonName === "") {
				return true;
			} //end if

			// Divider
			if(buttonName === "|") {

				// Add a new divider to the group
				var $div = $(DIV_TAG)
					.addClass(DIVIDER_CLASS)
					.appendTo($group);

				// Update the group width
				$group.width(groupWidth + 1);
				groupWidth = 0;

				// Create a new group
				$group = $(DIV_TAG)
					.addClass(GROUP_CLASS)
					.appendTo($toolbar);

			} else { // Button

				// Get the button definition
				var button = buttons[buttonName];

				// Add a new button to the group
				var $buttonDiv = $(DIV_TAG)
					.data(BUTTON_NAME, button.name)
					.addClass(BUTTON_CLASS)
					.attr("title", button.title)
					.bind(CLICK, $.proxy(buttonClick, editor))
					.appendTo($group)
					.hover(hoverEnter, hoverLeave);

				// Update the group width
				groupWidth += 24;
				$group.width(groupWidth + 1);

				// Prepare the button image
				var map = {};
				if(button.css) {
					map = button.css;
				} else if(button.image) {
					map.backgroundImage = imageUrl(button.image);
				} //end if else
//				if(button.stripIndex) {
//					map.backgroundPosition = button.stripIndex * -24;
//				} //end if
				$buttonDiv.css(map);

				// Add the unselectable attribute for ie
				if(ie) {
					$buttonDiv.attr(UNSELECTABLE, "on");
				} //end if

				// Create the popup
				if(button.popupName) {
					createPopup(button.popupName, options, button.popupClass, button.popupContent, button.popupHover);
				} //end if

			} //end if else

		});

		// Add the main div to the DOM and append the textarea
		$main.insertBefore($area)
			.append($area);

		// Bind the document click event handler
		if(!documentClickAssigned) {
			$(document).click(function (e) {
				// Dismiss all non-prompt popups
				var $target = $(e.target);
				if(!$target.add($target.parents()).is("." + PROMPT_CLASS)) {
					hidePopups();
				} //end if
			});
			documentClickAssigned = true;
		} //end if

		// Bind the window resize event when the width or height is auto or %
		if(/auto|%/.test("" + options.width + options.height)) {
			$(window).bind("resize.cleditor", function () {
				//alert('bug fixed in refresh ...');
				refresh(editor);
			});
		} //end if

		// Create the iframe and resize the controls
		refresh(editor);

	}; //end function

	//===============
	// Public Methods
	//===============

	var fn = cleditor.prototype,

	// Expose the following private functions as methods on the cleditor object.
	// The closure compiler will rename the private functions. However, the
	// exposed method names on the cleditor object will remain fixed.
	methods = [
		["clear", clear],
		["disable", disable],
		["execCommand", execCommand],
		["focus", focus],
		["hidePopups", hidePopups],
		["sourceMode", sourceMode, true],
		["refresh", refresh],
		["select", select],
		["selectedHTML", selectedHTML, true],
		["selectedText", selectedText, true],
		["showMessage", showMessage],
		["updateFrame", updateFrame],
		["updateTextArea", updateTextArea]
	];

	$.each(methods, function (idx, method) {
		fn[method[0]] = function () {
			var editor = this, args = [editor];
			// using each here would cast booleans into objects!
			for(var x = 0; x < arguments.length; x++) {
				args.push(arguments[x]);
			} //end for
			var result = method[1].apply(editor, args);
			if(method[2]) {
				return result;
			} //end if
			return editor;
		};
	});

	// blurred - shortcut for .bind("blurred", handler) or .trigger("blurred")
	fn.blurred = function (handler) {
		var $this = $(this);
		return handler ? $this.bind(BLURRED, handler) : $this.trigger(BLURRED);
	};

	// change - shortcut for .bind("change", handler) or .trigger("change")
	fn.change = function change(handler) {
		var $this = $(this);
		return handler ? $this.bind(CHANGE, handler) : $this.trigger(CHANGE);
	};

	// focused - shortcut for .bind("focused", handler) or .trigger("focused")
	fn.focused = function (handler) {
		var $this = $(this);
		return handler ? $this.bind(FOCUSED, handler) : $this.trigger(FOCUSED);
	};

	//===============
	// Event Handlers
	//===============

	// buttonClick - click event handler for toolbar buttons
	function buttonClick(e) {

		var editor = this, buttonDiv = e.target, buttonName = $.data(buttonDiv, BUTTON_NAME), button = buttons[buttonName], popupName = button.popupName, popup = popups[popupName];

		// Check if disabled
		if(editor.disabled || $(buttonDiv).attr(DISABLED) === DISABLED) {
			return;
		} //end if

		// Fire the buttonClick event
		var data = {
			editor: editor,
			button: buttonDiv,
			buttonName: buttonName,
			popup: popup,
			popupName: popupName,
			command: button.command,
			useCSS: editor.options.useCSS
		};

		if(button.buttonClick && button.buttonClick(e, data) === false) {
			return false;
		} //end if

		// Toggle source
		if(buttonName === "source") {
			if(sourceMode(editor)) { // Show the iframe
				delete editor.range;
				editor.$area.hide();
				editor.$frame.show();
				buttonDiv.title = button.title;
			} else { // Show the textarea
				editor.$frame.hide();
				editor.$area.show();
				buttonDiv.title = "Show Rich Text";
			} //end if else
			refreshButtons(editor); // fix: unixman

		} else if(!sourceMode(editor)) { // Check for rich text mode

			// Handle popups
			if(popupName) {

				var $popup = $(popup);

				// URL
				if(popupName === "url") {

					// Check for selection before showing the link url popup
					if(buttonName === "link" && selectedText(editor) === "") {
						showMessage(editor, "A selection is required when inserting a link.", buttonDiv);
						return false;
					} //end if

					// Wire up the submit button click event handler
					$popup.children(":button")
						.unbind(CLICK)
						.bind(CLICK, function () {
							// Insert the image or link if a url was entered
							var $text = $popup.find(":text"), url = $.trim($text.val());
							if(url !== "") {
								execCommand(editor, data.command, url, null, data.button);
							} //end if
							// Reset the text, hide the popup and set focus
							$text.val("http://");
							hidePopups();
							focus(editor);
						});

				} else if(popupName === "pastetext") { // Paste as Text

					// Wire up the submit button click event handler
					$popup.children(":button")
						.unbind(CLICK)
						.bind(CLICK, function () {
							// Insert the unformatted text replacing new lines with break tags
							var $textarea = $popup.find("textarea"), text = $textarea.val(); //.replace(/\n/g, "<br>");
							if(text !== "") {
								execCommand(editor, data.command, text, null, data.button);
							} //end if
							// Reset the text, hide the popup and set focus
							$textarea.val("");
							hidePopups();
							focus(editor);
						});

				} //end if else

				// Show the popup if not already showing for this button
				if(buttonDiv !== $.data(popup, BUTTON)) {
					showPopup(editor, popup, buttonDiv);
					return false; // stop propagination to document click
				} //end if

				return; // propaginate to document click

			} else if(buttonName === "print") { // Print

				editor.$frame[0].contentWindow.print();

			} else if(!execCommand(editor, data.command, data.value, data.useCSS, buttonDiv)) { // All other buttons

				return false;

			} //end if else

		} //end if else

		// Focus the editor
		focus(editor);

	} //END FUNCTION

	// hoverEnter - mouseenter event handler for buttons and popup items
	function hoverEnter(e) {
		var $div = $(e.target).closest("div");
		$div.css(BACKGROUND_COLOR, $div.data(BUTTON_NAME) ? "#FFF" : "#FFC");
	} //END FUNCTION

	// hoverLeave - mouseleave event handler for buttons and popup items
	function hoverLeave(e) {
		$(e.target).closest("div").css(BACKGROUND_COLOR, "transparent");
	} //END FUNCTION

	// popupClick - click event handler for popup items
	function popupClick(e) {

		var editor = this, popup = e.data.popup, target = e.target;

		// Check for message and prompt popups
		if(popup === popups.msg || $(popup).hasClass(PROMPT_CLASS)) {
			return;
		} //end if

		// Get the button info
		var buttonDiv = $.data(popup, BUTTON), buttonName = $.data(buttonDiv, BUTTON_NAME), button = buttons[buttonName], command = button.command, value, useCSS = editor.options.useCSS;

		// Get the command value
		if(buttonName === "font") {
			// Opera returns the fontfamily wrapped in quotes
			value = target.style.fontFamily.replace(/"/g, "");
		} else if(buttonName === "size") {
			if(target.tagName.toUpperCase() === "DIV") {
				target = target.children[0];
			} //end if
			value = target.innerHTML;
		} else if(buttonName === "style") {
			value = "<" + target.tagName + ">";
		} else if(buttonName === "color") {
			value = hex(target.style.backgroundColor);
		} else if(buttonName === "highlight") {
			value = hex(target.style.backgroundColor);
			if(ie) {
				command = 'backcolor';
			} else {
				useCSS = true;
			} //end if else
		} //end if else

		// Fire the popupClick event
		var data = {
			editor: editor,
			button: buttonDiv,
			buttonName: buttonName,
			popup: popup,
			popupName: button.popupName,
			command: command,
			value: value,
			useCSS: useCSS
		};

		if(button.popupClick && button.popupClick(e, data) === false) {
			return;
		} //end if

		// Execute the command
		if(data.command && !execCommand(editor, data.command, data.value, data.useCSS, buttonDiv)) {
			return false;
		} //end if

		// Hide the popup and focus the editor
		hidePopups();
		focus(editor);

	} //END FUNCTION

	//==================
	// Private Functions
	//==================

	// clear - clears the contents of the editor
	function clear(editor) {
		editor.$area.val('');
		updateFrame(editor);
	} //END FUNCTION

	// createPopup - creates a popup and adds it to the body
	function createPopup(popupName, options, popupTypeClass, popupContent, popupHover) {

		// Check if popup already exists
		if(popups[popupName]) {
			return popups[popupName];
		} //end if

		// Create the popup
		var $popup = $(DIV_TAG)
			.hide()
			.addClass(POPUP_CLASS)
			.appendTo("body");

		// Add the content
		if(popupContent) { // Custom popup
			$popup.html(popupContent);
		} else if(popupName === "color") { // Color
			var colors = options.colors.split(" ");
			if(colors.length < 10) {
				$popup.width("auto");
			} //end if
			$.each(colors, function (idx, color) {
				$(DIV_TAG).appendTo($popup)
					.css(BACKGROUND_COLOR, "#" + color);
			});
			popupTypeClass = COLOR_CLASS;
		} else if(popupName === "font") { // Font
			$.each(options.fonts.split(","), function (idx, font) {
				$(DIV_TAG).appendTo($popup)
					.css("fontFamily", font)
					.html(font);
			});
		} else if(popupName === "size") { // Size
			$.each(options.sizes.split(","), function (idx, size) {
				$(DIV_TAG).appendTo($popup)
					.html('<font size="' + size + '">' + size + '</font>');
			});
		} else if(popupName === "style") { // Style
			$.each(options.styles, function (idx, style) {
				$(DIV_TAG).appendTo($popup)
					.html(style[1] + style[0] + style[1].replace("<", "</"));
			});
		} else if(popupName === "url") { // URL
			$popup.html('<label>Enter the Link URL:<br><input class="ux-field" type="text" value="http://" style="width:200px; font-size:13px;"></label><br><input type="button" class="ux-button ux-button-small ux-button-secondary" style="width:100%; margin-top:5px;" value="Make Hyperlink">');
			popupTypeClass = PROMPT_CLASS;
		} else if(popupName === "pastetext") { // Paste as Text
			$popup.html('<label>Paste your code here:<br><textarea class="ux-field" style="width:225px; height:150px; font-size:13px;"></textarea></label><br><input type="button" class="ux-button ux-button-small ux-button-secondary" style="width:100%; margin-top:5px;" value="Insert Code">');
			popupTypeClass = PROMPT_CLASS;
		} //end if else

		// Add the popup type class name
		if(!popupTypeClass && !popupContent) {
			popupTypeClass = LIST_CLASS;
		} //end if
		$popup.addClass(popupTypeClass);

		// Add the unselectable attribute to all items
		if(ie) {
			$popup.attr(UNSELECTABLE, "on")
				.find("div,font,p,h1,h2,h3,h4,h5,h6")
				.attr(UNSELECTABLE, "on");
		} //end if

		// Add the hover effect to all items
		if($popup.hasClass(LIST_CLASS) || popupHover === true) {
			$popup.children().hover(hoverEnter, hoverLeave);
		} //end if

		// Add the popup to the array and return it
		popups[popupName] = $popup[0];
		return $popup[0];

	} //END FUNCTION

	// disable - enables or disables the editor
	function disable(editor, disabled) {

		// Update the textarea and save the state
		if(disabled) {
			editor.$area.attr(DISABLED, DISABLED);
			editor.disabled = true;
		} else {
			editor.$area.removeAttr(DISABLED);
			delete editor.disabled;
		} //end if else

		// Switch the iframe into design mode.
		// ie7 & ie8 do not properly support designMode="off".
		try {
			if(ie) {
				editor.doc.body.contentEditable = !disabled;
			} else {
				editor.doc.designMode = !disabled ? "on" : "off";
			} //end if else
		} catch(err) {
			 // Firefox 1.5 throws an exception that can be ignored when toggling designMode from off to on.
		} //end try catch

		// Enable or disable the toolbar buttons
		refreshButtons(editor);

	} //END FUNCTION

	// execCommand - executes a designMode command
	function execCommand(editor, command, value, useCSS, button) {

		// Restore the current ie selection
		restoreRange(editor);

		// Set the styling method
		if(!ie) {
			if(useCSS === undefined || useCSS === null) {
				useCSS = editor.options.useCSS;
			} //end if
			editor.doc.execCommand("styleWithCSS", 0, useCSS.toString());
		} //end if

		// Execute the command and check for error
		var inserthtml = command.toLowerCase() === "inserthtml";
		if(ie && inserthtml) {
			getRange(editor).pasteHTML(value);
		} else if(iege11 && inserthtml) {
			var selection = getSelection(editor), range = selection.getRangeAt(0);
			range.deleteContents();
			range.insertNode(range.createContextualFragment(value));
			selection.removeAllRanges();
			selection.addRange(range);
		} else {
			var success = true, message;
			try {
				success = editor.doc.execCommand(command, 0, value || null);
			} catch(err) {
				message = err.message;
				success = false;
			} //end try catch
			if(!success) {
				if("cutcopypaste".indexOf(command) > -1) {
					showMessage(editor, "For security reasons, your browser does not support the " + command + " command. Try using the keyboard shortcut or context menu instead.", button);
				} else {
					showMessage(editor, (message ? message : "Error executing the " + command + " command."), button);
				} //end if else
			} //end if
		} //end if else

		// Enable the buttons and update the textarea
		refreshButtons(editor);
		updateTextArea(editor);

		return success;

	} //END FUNCTION

	// focus - sets focus to either the textarea or iframe
	function focus(editor) {
		setTimeout(function () {
			if(sourceMode(editor)) {
				editor.$area.focus();
			} else {
				editor.$frame[0].contentWindow.focus();
			} //end if else
			refreshButtons(editor);
		}, 0);
	} //END FUNCTION

	// getRange - gets the current text range object
	function getRange(editor) {
		if(ie) {
			return getSelection(editor).createRange();
		} else {
			return getSelection(editor).getRangeAt(0);
		} //end if else
	} //END FUNCTION

	// getSelection - gets the current text range object
	function getSelection(editor) {
		if(ie) {
			return editor.doc.selection;
		} else {
			return editor.$frame[0].contentWindow.getSelection();
		} //end if else
	} //END FUNCTION

	// hex - returns the hex value for the passed in color string
	function hex(s) {

		// hex("rgb(255, 0, 0)") returns #FF0000
		var m = /rgba?\((\d+), (\d+), (\d+)/.exec(s);
		if(m) {
			s = (m[1] << 16 | m[2] << 8 | m[3]).toString(16);
			while(s.length < 6) {
				s = "0" + s;
			} //end while
			return "#" + s;
		} //end if

		// hex("#F00") returns #FF0000
		var c = s.split("");
		if(s.length === 4) {
			return "#" + c[1] + c[1] + c[2] + c[2] + c[3] + c[3];
		} //end if

		// hex("#FF0000") returns #FF0000
		return s;

	} //END FUNCTION

	// hidePopups - hides all popups
	function hidePopups() {
		$.each(popups, function (idx, popup) {
			$(popup)
				.hide()
				.unbind(CLICK)
				.removeData(BUTTON);
		});
	} //END FUNCTION

	// by unixman
	function editorPath() {
		//--
		var href = $("link[href$='jquery.cleditor.css']").attr("href");
		var re = /^(.*\/)?(jquery\.cleditor\.css)+$/;
		var path = re.exec('' + href);
		if(typeof path[1] == 'undefined') {
			path[1] = '';
		} //end if
		//console.log('Editor Path: ' + path[1]);
		return '' + path[1];
	} //END FUNCTION

	// imagesPath - returns the path to the images folder
	function imagesPath() {
		//--
		//var href = $("link[href*=cleditor]").attr("href");
		//return href.replace(/^(.*\/)[^\/]+$/, '$1') + "images/";
		//-- fix by unixman
		//console.log(editorPath() + "images/");
		return editorPath() + "images/";
		//--
	} //END FUNCTION

	// imageUrl - Returns the css url string for a filemane
	function imageUrl(filename) {
		return "url(" + imagesPath() + filename + ")";
	} //END FUNCTION

	// refresh - creates the iframe and resizes the controls
	function refresh(editor) {

		var $main = editor.$main, options = editor.options;

		if($main.hasClass('fullscreen')) {
			return; // avoid refresh in full screen mode ... too many complications !
		} //end if

		//-- bug fix by unixman (on resize ...)
		if(sourceMode(editor)) { // Show the iframe
			delete editor.range;
			editor.$area.hide();
			editor.$frame.show();
		} //end if
		//-- #

		// Remove the old iframe
		if(editor.$frame) {
			editor.$frame.remove();
		} //end if

		// Create a new iframe
		var the_html_frame = '';
		if(webkit) {
			the_html_frame = '<iframe frameborder="0" src="javascript:true;" sandbox="allow-pointer-lock allow-popups allow-same-origin allow-top-navigation allow-scripts"></iframe>'; // unixman: Webkit misbehaves and block parent JS without this, but executing JS is not what we want in the editor ...
		} else { // Firefox, IE
			the_html_frame = '<iframe frameborder="0" src="javascript:true;" sandbox="allow-pointer-lock allow-popups allow-same-origin allow-top-navigation"></iframe>';
		} //end if else
		var $frame = editor.$frame = $(the_html_frame).hide().appendTo($main);

		// Load the iframe document content
		var contentWindow = $frame[0].contentWindow, doc = editor.doc = contentWindow.document, $doc = $(doc);

		var styleSheet = editorPath() + 'jquery.cleditor.smartframeworkcomponents.css';
		if(typeof CLEditor_SmartFrameworkComponents_EditorStyles != 'undefined') {
			if(CLEditor_SmartFrameworkComponents_EditorStyles) {
				styleSheet = String(CLEditor_SmartFrameworkComponents_EditorStyles);
			}
		}

		doc.open();
		doc.write(
			options.docType +
			'<html>' +
			((options.docCSSFile === '') ? '<head><link rel="stylesheet" type="text/css" href="' + styleSheet + '"></head>' : '<head><link rel="stylesheet" type="text/css" href="' + options.docCSSFile + '"></head>') +
			'<body style="' + options.bodyStyle + '"></body></html>'
		);
		doc.close();

		// Work around for bug in IE which causes the editor to lose
		// focus when clicking below the end of the document.
		if(ie || iege11) {
			$doc.click(function () { focus(editor); });
		} //end if

		// Load the content
		updateFrame(editor);

		// Bind the ie specific iframe event handlers
		if(ie || iege11) {

			// Save the current user selection. This code is needed since IE will
			// reset the selection just after the beforedeactivate event and just
			// before the beforeactivate event.
			$doc.bind("beforedeactivate beforeactivate selectionchange keypress keyup", function (e) {
				// Flag the editor as inactive
				if(e.type === "beforedeactivate") {
					editor.inactive = true;
				} else if(e.type === "beforeactivate") { // Get rid of the bogus selection and flag the editor as active
					if(!editor.inactive && editor.range && editor.range.length > 1) {
						editor.range.shift();
					} //end if
					delete editor.inactive;
				} else if(!editor.inactive) { // Save the selection when the editor is active
					if(!editor.range) {
						editor.range = [];
					} //end if
					editor.range.unshift(getRange(editor));
					while(editor.range.length > 2) { // We only need the last 2 selections
						editor.range.pop();
					} //end while
				} //end if else
			});

			// Restore the text range and trigger focused event when the iframe gains focus
			$frame.focus(function () {
				restoreRange(editor);
				$(editor).triggerHandler(FOCUSED);
			});

			// Trigger blurred event when the iframe looses focus
			$frame.blur(function () {
				//console.log('CLEditor iFrame Blur ...');
				updateTextArea(editor); // unixman
				$(editor).triggerHandler(BLURRED);
			});

		} else {

			// Trigger focused and blurred events for all other browsers

			$($frame[0].contentWindow)
				.focus(function () {
					$(editor).triggerHandler(FOCUSED);
				})
				.blur(function () {
					//console.log('CLEditor iFrame Blur ...');
					updateTextArea(editor); // unixman
					$(editor).triggerHandler(BLURRED);
				});

		} //end if else

		// Enable the toolbar buttons and update the textarea as the user types or clicks
		$doc.click(hidePopups)
			.keydown(function (e) {
				// Prevent Internet Explorer from going to prior page when an image
				// is selected and the backspace key is pressed.
				if(ie && getSelection(editor).type == "Control" && e.keyCode == 8) {
					getSelection(editor).clear();
					e.preventDefault();
				} //end if
			})
			.bind("keyup mouseup", function () {
				refreshButtons(editor);
				updateTextArea(editor);
			});

		// Show the textarea for iPhone/iTouch/iPad or
		// the iframe when design mode is supported.
	//	if(iOS) {
	//		//editor.$area.show();
	//		$frame.show();
	//		editor.disabled = true;
	//	} else {
			$frame.show();
	//	} //end if else

		// Wait for the layout to finish - shortcut for $(document).ready()
		$(function () {
			var $toolbar = editor.$toolbar, $group = $toolbar.children("div:last");
			var wid = $main.width();
			var hgt;
			// Resize the toolbar
			hgt = $group.offset().top + $group.outerHeight() - $toolbar.offset().top + 1;
			$toolbar.height(hgt);
			// Resize the iframe
		//	hgt = (/%|vh/.test(String(options.height)) ? $main.height() : parseInt(options.height, 10)) - hgt;
			hgt = (/px/.test(String(options.height)) ? parseInt(options.height, 10) : $main.height()) - hgt;
			$frame.width(wid).height(hgt);
			// Resize the textarea. IE7 textareas have a 1px top
			// & bottom margin that cannot be removed using css.
			editor.$area.width(wid).height(hgt);
			// Switch the iframe into design mode if enabled
			disable(editor, editor.disabled);
			// Enable or disable the toolbar buttons
			refreshButtons(editor);
		//	if(iOS) {
		//		console.log('HTML5 Editor is not supported under mobile iOS ...');
		//	} //end if
//			else if(webkit) {
//				console.log('Your browser (Webkit) have some missing features like resize images or resize tables. HTML5 Live Editor recommends using Firefox 24+ or Internet Explorer 11+ ...');
//			} //end if
		});

	} //END FUNCTION

	// refreshButtons - enables or disables buttons based on availability
	function refreshButtons(editor) {

		// Webkit requires focus before queryCommandEnabled will return anything but false
	//	if(!iOS && webkit) { // && !editor.focused) { // bug fix :: unixman
		if(webkit) { // && !editor.focused) { // bug fix :: unixman
		//	window.focus(); // unixman: fix for chromium browser
			editor.$frame[0].contentWindow.focus();
			editor.focused = true;
		} //end if

		// Get the object used for checking queryCommandEnabled
		var queryObj = editor.doc;
		if(ie) {
			queryObj = getRange(editor);
		} //end if

		// Loop through each button
		var inSourceMode = sourceMode(editor);
		$.each(editor.$toolbar.find("." + BUTTON_CLASS), function (idx, elem) {

			var $elem = $(elem), button = $.cleditor.buttons[$.data(elem, BUTTON_NAME)], command = button.command, enabled = true;

			// Determine the state
			if(editor.disabled) {
				enabled = false;
			} else if(button.getEnabled) {
				var data = {
					editor: editor,
					button: elem,
					buttonName: button.name,
					popup: popups[button.popupName],
					popupName: button.popupName,
					command: button.command,
					useCSS: editor.options.useCSS
				};
				enabled = button.getEnabled(data);
				if(enabled === undefined) {
					enabled = true;
				} //end if
		//	} else if(((inSourceMode || iOS) && button.name !== "source" && button.name !== "fullscreen") || (ie && (command === "undo" || command === "redo"))) {
			} else if(((inSourceMode) && button.name !== "source" && button.name !== "fullscreen") || (ie && (command === "undo" || command === "redo"))) {
				enabled = false;
			} else if(command && command !== "print") {
				if(ie && command === "hilitecolor") {
					command = "backcolor";
				} //end if
				// IE does not support inserthtml, so it's always enabled
				if((!ie && !iege11)  || command !== "inserthtml") {
					try {
						enabled = queryObj.queryCommandEnabled(command);
					} catch(err) {
						enabled = false;
					} //end try catch
				} //end if
			} //end if else

			// Enable or disable the button
			if(enabled) {
				$elem.removeClass(DISABLED_CLASS);
				$elem.removeAttr(DISABLED);
			} else {
				$elem.addClass(DISABLED_CLASS);
				$elem.attr(DISABLED, DISABLED);
			} //end if else

		});

	} //END FUNCTION

	// restoreRange - restores the current ie selection
	function restoreRange(editor) {
		if(editor.range) {
			if(ie) {
				editor.range[0].select();
			} else if(iege11) {
				getSelection(editor).addRange(editor.range[0]);
			} //end if else
		} //end if
	} //END FUNCTION

	// select - selects all the text in either the textarea or iframe
	function select(editor) {
		setTimeout(function () {
			if(sourceMode(editor)) {
				editor.$area.select();
			} else {
				execCommand(editor, "selectall");
			} //end if else
		}, 0);
	} //END FUNCTION

	// selectedHTML - returns the current HTML selection or and empty string
	function selectedHTML(editor) {
		restoreRange(editor);
		var range = getRange(editor);
		if(ie) {
			return range.htmlText;
		} //end if
		var layer = $("<layer>")[0];
		layer.appendChild(range.cloneContents());
		var html = layer.innerHTML;
		layer = null;
		return html;
	} //END FUNCTION

	// selectedText - returns the current text selection or and empty string
	function selectedText(editor) {
		restoreRange(editor);
		if(ie) {
			return getRange(editor).text;
		} else {
			return getSelection(editor).toString();
		} //end if else
	} //END FUNCTION

	// showMessage - alert replacement
	function showMessage(editor, message, button) {
		var popup = createPopup("msg", editor.options, MSG_CLASS);
		popup.innerHTML = message;
		showPopup(editor, popup, button);
	} //END FUNCTION

	// showPopup - shows a popup
	function showPopup(editor, popup, button) {

		var offset, left, top, $popup = $(popup);

		// Determine the popup location
		if(button) {
			var $button = $(button);
			offset = $button.offset();
			left = --offset.left;
			top = offset.top + $button.height();
		} else {
			var $toolbar = editor.$toolbar;
			offset = $toolbar.offset();
			left = Math.floor(($toolbar.width() - $popup.width()) / 2) + offset.left;
			top = offset.top + $toolbar.height() - 2;
		} //end if else

		// Position and show the popup
		hidePopups();
		$popup.css({ left: left, top: top }).show();

		// Assign the popup button and click event handler
		if(button) {
			$.data(popup, BUTTON, button);
			$popup.bind(CLICK, { popup: popup }, $.proxy(popupClick, editor));
		} //end if

		// Focus the first input element if any
		setTimeout(function () {
			$popup.find(":text,textarea").eq(0).focus().select();
		}, 100);

	} //END FUNCTION

	// sourceMode - returns true if the textarea is showing
	function sourceMode(editor) {
		return editor.$area.is(":visible");
	} //END FUNCTION

	// updateFrame - updates the iframe with the textarea contents
	function updateFrame(editor) {

		var html = editor.$area.val();
		var options = editor.options;
		var $body = $(editor.doc.body);

		// Prevent script injection attacks by html encoding script tags
		/* unixman: commented out: we wish to allow scripts
		html = html.replace(/<(?=\/?script)/ig, "&lt;");
		*/

		// Update the iframe and trigger the change event
		//-- unixman fix: bug fixed: scripts are dissapearing ...
		/*
		if(html !== $body.html()) {
			$body.html(html);
			$(editor).triggerHandler(CHANGE);
		} //end if
		*/
		if(html !== editor.doc.body.innerHTML) {
			editor.doc.body.innerHTML = html;
			$(editor).triggerHandler(CHANGE);
		} //end if
		//-- #end fix by unixman

	} //END FUNCTION

	// updateTextArea - updates the textarea with the iframe contents
	function updateTextArea(editor) {

		var html = $(editor.doc.body).html();
		var options = editor.options;
		var $area = editor.$area;

		//-- unixman: clean, sanitize and prettify the HTML source {{{SYNC-HTML-CLEAN}}}
		html = $.htmlClean(html, {
			'format': true,
			'allowedTags': options.allowedTags,
			'allowedAttributes': options.allowedAttributes,
			'removeTags': options.removeTags
		});
		//-- unixman: fix to remove all content contain just one <br> (this is a bugfix, ... on repetitive switching between Source / Wysisyg modes it populates a <br> by default even if empty data ... this is a bug deep inside jQuery thus have to be fixed here !)
		if($.trim(html) == '<br>') {
			html = '';
		} //end if
		//--

		//-- Update the textarea and trigger the change event
		if(html !== $area.val()) {
			$area.val(html);
			$(editor).triggerHandler(CHANGE);
		} //end if
		//--

	} //END FUNCTION

})(jQuery);

// #END
