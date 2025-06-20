
// jquery.alertable.js - Minimal alert, confirmation, and prompt alternatives.
// Developed by Cory LaViska for A Beautiful Site, LLC
// Licensed under the MIT license: http://opensource.org/licenses/MIT

// r.20250228
// (c) 2019-present unix-world.org
// License: BSD
// contains fixes by unixman

if(jQuery) (function($) {
	'use strict';

	var modal;
	var overlay;
	var okButton;
	var cancelButton;
	var activeElement;

	//-- unixman
	function htmlEscape(str) {
		//-- format sting
		if((str == undefined) || (str == '')) {
			return '';
		} //end if
		//-- force string
		str = String(str);
		//-- replacements map
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;'
		};
		//-- do replace
		return String(str.replace(/[&\<\>"]/g, function(m){ return map[m] })); // fix to return empty string instead of null
		//--
	} //END FUNCTION
	//--

	function show(type, message, options) {
		var defer = $.Deferred();

		// Remove focus from the background
		activeElement = document.activeElement;
		activeElement.blur();

		// Remove other instances
		$(modal).add(overlay).remove();

		// Merge options
		options = $.extend({}, $.alertable.defaults, options);

		// Create elements
		modal = $(options.modal).hide();
		overlay = $(options.overlay).hide();
		okButton = $(options.okButton);
		cancelButton = $(options.cancelButton);

		// Add message
		var displayMsg = String(message || '');
		var msgType = 'html';
		if(!options.html) {
			msgType = 'text';
			displayMsg = htmlEscape(displayMsg);
		}

		modal.find('.alertable-message').addClass('alertable-message-' + msgType).html(displayMsg);

		// Add prompt
		if(type === 'prompt') {
			var pdata = options.prompt;
			var value = '';
			if(options.value) {
				value = htmlEscape(options.value);
				if(value) {
					pdata = pdata.replace(' value="" ', ' value="' + value + '" ');
				}
			}
			modal.find('.alertable-prompt').html(pdata);
		} else {
			modal.find('.alertable-prompt').remove();
		}

		// Add buttons
		$(modal).find('.alertable-buttons')
		.append(type === 'alert' ? '' : cancelButton)
		.append(okButton);

		// Add to container
		$(options.container).append(overlay).append(modal);

		options.width = Math.min(options.width, ($(window).width() - 25));
		options.height = Math.min(options.height, ($(window).height() - 100));

		if(options.width) {
			$(modal).width(parseInt(options.width));
		}
		if(options.height) {
			$(modal).height(parseInt(options.height));
		}

		// Show it
		options.show.call({
			modal: modal,
			overlay: overlay
		});

		// Set focus
		if(type === 'prompt') {
			// First input in the prompt
			$(modal).find('.alertable-prompt :input:first').focus();
		} else {
			// OK button
			$(modal).find(':input[type="submit"]').focus();
		}

		// Watch for submit (OK button)
		okButton.on('click.alertable', function(event) { // fix by unixman, the form is just for prompt, don't use form, there may be another form in content, to be compatible with SF ui-dialogs
			event.preventDefault();
			var values = null;
			if(type === 'prompt') {
				values = {};
				$('input.alertable-input').each(function(index, element) {
					var $el = $(element);
					var elName = String($el.attr('name') || '');
					if(elName) {
						values[elName] = String($el.val() || '');
					}
				});
			}
			hide(options);
			//console.log(JSON.stringify(values));
			defer.resolve(values);
		});

		// Watch for cancel (Cancel button)
		cancelButton.on('click.alertable', function() {
			hide(options);
			defer.reject();
		});

		// Cancel on escape
		$(document).on('keydown.alertable', function(event) {
			if(event.keyCode === 27) {
				event.preventDefault();
				hide(options);
				defer.reject();
			}
		});

		// Prevent focus from leaving the modal
		$(document).on('focus.alertable', '*', function(event) {
			if(!$(event.target).parents().is('.alertable')) {
				event.stopPropagation();
				event.target.blur();
				$(modal).find(':input:first').focus();
			}
		});

		return defer.promise();
	}

	function hide(options) {
		// Hide it
		options.hide.call({
			modal: modal,
			overlay: overlay
		});

		// Remove bindings
		$(document).off('.alertable');
		modal.off('.alertable');
		cancelButton.off('.alertable');

		// Restore focus
		activeElement.focus();
	}

	// Defaults
	$.alertable = {
		// Show an alert
		alert: function(message, options) {
			return show('alert', message, options);
		},

		// Show a confirmation
		confirm: function(message, options) {
			return show('confirm', message, options);
		},

		// Show a prompt
		prompt: function(message, options) {
			return show('prompt', message, options);
		},

		defaults: {
			// Preferences
			container: 'body',
			html: false,

			// Templates
			cancelButton: '<button class="alertable-cancel" type="button">Cancel</button>',
			okButton: '<button class="alertable-ok" type="submit">OK</button>',
			overlay: '<div class="alertable-overlay"></div>',
			prompt: '<input class="alertable-input ux-field" type="text" name="value" value="" maxlength="65535" autocomplete="off" spellcheck="false" placeholder="Value (prompt)">',
			value: '', // just for prompt
			modal:
				'<div class="alertable">' +
				'<div class="alertable-message"></div>' +
				'<div class="alertable-prompt"></div>' +
				'<div class="alertable-buttons"></div>' +
				'</div>',

			width: null,
			height: null,

			// Hooks
			hide: function() {
				$(this.modal).add(this.overlay).fadeOut(100);
			},
			show: function() {
				$(this.modal).add(this.overlay).fadeIn(100);
			}
		}
	};
})(jQuery);

// #END
