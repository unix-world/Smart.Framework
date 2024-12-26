/*
 * (c) 2018 Bob Hageman (https://gitlab.com/b.hageman)
 * License: MIT
 * Version 1.0.3
 *
 * (c) 2023 unix-world.org
 * v.20231020
 * contains fixes by unixman:
 * 		- fix xhtml tags
 */

;( function( $, window, document, undefined ) {

	"use strict";

	var pluginName = "pinlogin",

	defaults = {
		fields: 6,						// number of fields
		placeholder: '•',				// character that's displayed after entering a number in a field
		hideinput: true,				// hide the input digits and replace them with placeholder
		delay: 250,						// delay in ms for hiding the input value (by unixman)
		autofocus: false,				// focus on the first field at loading time
	//	reset: false,					// resets all fields when completely filled ; disabled by unixman ; should use it only programatically on complete
		complete: function(pin) {		// fires when all fields are filled in
			// pin	:	the entered pincode
		},
		invalid: function(field, nr) { // fires when user enters an invalid value in a field
			// field: 	the jquery field object
			// nr	:	the field number
		},
		keydown: function(e, field, nr) { // fires when user pressed a key down in a field
			// e	:	the event object
			// field: 	the jquery field object
			// nr	:	the field number
		},
		input: function(e, field, nr) { // fires when a value is entered in a field
			// e	:	the event object
			// field: the jquery field object
			// nr:	the field number
		}
	};


	// constructor
	function Plugin ( element, options ) {
		this.element = element;

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

		// Avoid Plugin.prototype conflicts
		$.extend( Plugin.prototype, {
			init: function() {

				// Place initialization logic here
				// You already have access to the DOM element and
				// the options via the instance, e.g. this.element
				// and this.settings
				// you can add more functions like the one below and
				// call them like the example below
				//this.yourOtherFunction( "jQuery Boilerplate" );
				this._values = new Array(this.settings.fields); // keeping track of the entered values

				this._container = $('<div></div>').prop({ // fix by unixman of XHTML tags
					'id' : this._name,
					'class' : this._name
				});

				// main loop creating the fields
				for(var i = 0; i < this.settings.fields; i++) {
					var input = this._createInput(this._getFieldId(i), i);
					this._attachEvents(input, i);
					this._container.append(input);
				}

				$(this.element).append(this._container);

				// reset all fields to starting state
				this.reset();
			},

			// create a single input field
			_createInput : function(id, nr) {
				return $('<input>').prop({
					'type' : 'tel', // Thanks to Manuja Jayawardana (https://gitlab.com/mjayawardana) this is 'tel' and not 'text'
					'id': id,
					'name': id,
					'maxlength': 1,
					'inputmode' : 'numeric',
					'x-inputmode' : 'numeric',
					'pattern' : '^[0-9]*$',
					'autocomplete' : 'off',
					'class' : 'pinlogin-field'
				});
			},

			// attach events to the field
			_attachEvents : function(field, nr) {
				var that = this;

				field.on('focus', $.proxy(function(e) {
					$(this).val('');
				}));

				field.on('blur', $.proxy(function(e) {
					if(!$(this).is('[readonly]') && that._values[nr] != undefined && that.settings.hideinput) {
						var $fld = $(this);
						setTimeout(function(){ $fld.val(that.settings.placeholder); }, that.settings.delay); // with timeout, by unixman
					}
				}));

				field.on('input', $.proxy(function(e) {

					// validate input pattern
					var pattern = new RegExp($(this).prop('pattern'));
					if(!$(this).val().match(pattern)) {
						$(this)
							.val('')
							.addClass('invalid');
						that.settings.invalid($(this), nr); // fire error callback
						e.preventDefault();
						e.stopPropagation();
						return;
					} else {
						$(this).removeClass('invalid');
					}

					// fire input callback
					that.settings.input(e, $(this), nr);

					// store value
					that._values[nr] = $(this).val();

					/* unixman: avoid double event, use just blur
					if(that.settings.hideinput) {
						var $fld = $(this);
						setTimeout(function(){ $fld.val(that.settings.placeholder); }, that.settings.delay); // with timeout, by unixman
					}
					*/

					// when it's not the last field
					if(nr < (that.settings.fields-1)) {
						// make next field editable
						that._getField(nr + 1).removeProp('readonly');
						// set focus to the next field
						that.focus(nr + 1);
					} else { // and when you're done
						var pin = that._values.join('');
						//-- fix by unixman
						/* if(that.settings.reset) { // reset the plugin
							that.reset();
						} */
						that._getField(nr).trigger('blur');
						//-- #end fix
						// fire complete callback
						that.settings.complete(pin);
					}

				}));

				field.on('keydown', $.proxy(function(e) {

					// fire keydown callback
					that.settings.keydown(e, $(this), nr);

					// when user goes back
					if((e.keyCode == 37 || e.keyCode == 8) && nr > 0) { // arrow back, backspace
						that.resetField(nr);
						that.focus(nr-1); // set focus to previous input
						e.preventDefault();
						e.stopPropagation();
					}

				}));
			},

			// get the id for a given input number
			_getFieldId : function (nr) {
				return this.element.id + '_' + this._name + '_' + nr;
			},

			// get the input field object
			_getField : function(nr) {
				return $('#' + this._getFieldId(nr));
			},

			// focus on the input field object
			focus : function(nr) {
				this.enableField(nr);	// make sure its enabled
				this._getField(nr).focus();
			},

			// reset the saved value and input fields
			reset : function() {
				var that = this;
				this._values = new Array(this.settings.fields);

				this._container.children('input').each(function(index) {
					var $fld = $(this);
					setTimeout(function(){
						$fld.val('');
						if(index > 0) {
							$fld.prop('readonly', true).removeClass('invalid');
						}
					}, that.settings.delay + 250);
				});

				// focus on first field
				if(this.settings.autofocus) {
					this.focus(0);
				}
			},

			// reset a single field
			resetField : function(nr) {
				this._values[nr] = '';
				this._getField(nr)
					.val('')
					.prop('readonly',true)
					.removeClass('invalid');
			},

			// disable all fields
			disable : function() {
				//console.log('disable all fields');
				this._container.children('input').each(function(index) {
					$(this).prop('readonly', true);
				});
			},

			// disable specified field
			disableField : function(nr) {
				this._getField(nr).prop('readonly', true);

			},

			// enable all fields
			enable : function() {
				this._container.children('input').each(function(index) {
					$(this).prop('readonly', false);
				});

			},

			// enable specified field
			enableField : function(nr) {
				this._getField(nr).prop('readonly', false);
			}

		});

		// A really lightweight plugin wrapper around the constructor,
		$.fn[pluginName] = function (options) {
			var plugin;
			this.each(function() {
				plugin = $.data(this, 'plugin_' + pluginName);
				if(!plugin) {
					plugin = new Plugin(this, options);
					$.data(this, 'plugin_' + pluginName, plugin);
				}
			});
			return plugin;
		};


} )( jQuery, window, document );

// #END
