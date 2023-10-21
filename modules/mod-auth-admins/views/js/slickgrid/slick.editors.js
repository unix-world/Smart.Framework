
//--------
// FORMATTERS & EDITORS for SlickGrid v1.4.3.2.smart.20231021
//
// (c) 2009-2010 Michael Leibman (michael.leibman@gmail.com)
//
// (c) 2012-2023 unix-world.org
// DEPENDS: jQuery, smartJ$Utils, smartJ$Browser
// Syntax: ES6
//
// Fixes:
// 	* 20160413: added selection handler
// 	* 20170323: replaced out references to $.ui(.keyCode)
// 	* 20170425: cleanup garbage code
// 	* 20181109: fix CheckboxCellEditor
// 	* 20200501: jQuery 3.5.0 ready (fixed XHTML Tags)
//	* 20231021: ES6 + optimizations ; implement css classes: .slick-cell-editor-field-*
//--------

(function($) { // ES6

	const _Utils$ = smartJ$Utils;
	const _BwUtils$ = smartJ$Browser;

	// get from jQuery UI
	const SE_Keycode_ESCAPE = 27; // $.ui.keyCode.ESCAPE
	const SE_Keycode_ENTER  = 13; // $.ui.keyCode.ENTER
	const SE_Keycode_LEFT   = 37; // $.ui.keyCode.LEFT
	const SE_Keycode_RIGHT  = 39; // $.ui.keyCode.RIGHT
	const SE_Keycode_TAB    =  9; // $.ui.keyCode.TAB

	const SlickEditor = {

		TextCellSelector : function(args) { //-- added by unixman
			let $input;
			let defaultValue;
			let scope = this;

			this.init = function() {
				$input = $('<input type="text" class="slick-cell-editor-field slick-cell-editor-field-text ux-field" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;" readonly>')
					.appendTo(args.container)
					.bind('keydown.nav', function(e) {
						if (e.keyCode === SE_Keycode_LEFT || e.keyCode === SE_Keycode_RIGHT) {
							e.stopImmediatePropagation();
						}
					})
					.focus()
					.select();
			};

			this.destroy = function() {
				$input.remove();
			};

			this.focus = function() {
				$input.focus();
			};

			this.loadValue = function(item) {
				defaultValue = item[args.column.field] || '';
				$input.val(defaultValue);
				$input[0].defaultValue = defaultValue;
				$input.select();
			};

			this.serializeValue = function() {
				return ''; // readonly
			};

			this.applyValue = function(item,state) {
				// do nothing ; readonly
			};

			this.isValueChanged = function() {
				return false; // readonly
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();

		}, //-- #end# unixman

		TextCellEditor : function(args) {
			let $input;
			let defaultValue;
			let scope = this;

			this.init = function() {
				$input = $('<input type="text" class="slick-cell-editor-field slick-cell-editor-field-text ux-field editor-text" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;">')
					.appendTo(args.container)
					.bind('keydown.nav', function(e) {
						if (e.keyCode === SE_Keycode_LEFT || e.keyCode === SE_Keycode_RIGHT) {
							e.stopImmediatePropagation();
						}
					})
					.focus()
					.select();
			};

			this.destroy = function() {
				$input.remove();
			};

			this.focus = function() {
				$input.focus();
			};

			this.loadValue = function(item) {
				defaultValue = item[args.column.field] || '';
				$input.val(defaultValue);
				$input[0].defaultValue = defaultValue;
				$input.select();
			};

			this.serializeValue = function() {
				return _Utils$.stringPureVal($input.val() || ''); // unixman fix
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == '' && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				if (args.column.validator) {
					let validationResults = args.column.validator($input.val());
					if(!validationResults.valid) {
						return validationResults;
					}
				}

				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		IntegerCellEditor : function(args) {
			let $input;
			let defaultValue;
			let scope = this;

			this.init = function() {
				$input = $('<input type="number" class="slick-cell-editor-field slick-cell-editor-field-int ux-field editor-text" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;">');

				$input.bind('keydown.nav', function(e) {
					if (e.keyCode === SE_Keycode_LEFT || e.keyCode === SE_Keycode_RIGHT) {
						e.stopImmediatePropagation();
					}
				});

				$input.appendTo(args.container);
				$input.focus().select();
			};

			this.destroy = function() {
				$input.remove();
			};

			this.focus = function() {
				$input.focus();
			};

			this.loadValue = function(item) {
				defaultValue = item[args.column.field];
				$input.val(defaultValue);
				$input[0].defaultValue = defaultValue;
				$input.select();
			};

			this.serializeValue = function() {
			//	return parseInt($input.val(),10) || 0;
				return _Utils$.format_number_int($input.val() || 0); // fix by unixman
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == '' && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				if (isNaN($input.val()))
					return {
						valid: false,
						msg: 'Please enter a valid integer'
					};

				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		YesNoSelectCellEditor : function(args) { // this is for 1/0 or TRUE/FALSE or YES/NO
			let $select;
			let defaultValue;
			let scope = this;

			this.init = function() {
				$select = $('<select tabIndex="0" class="slick-cell-editor-field slick-cell-editor-field-select ux-field editor-yesno"><option value="yes">Yes</option><option value="no">No</option></select>');
				$select.appendTo(args.container);
				$select.focus();
			};

			this.destroy = function() {
				$select.remove();
			};

			this.focus = function() {
				$select.focus();
			};

			this.loadValue = function(item) {
				defaultValue = item[args.column.field];
				defaultSValue = _Utils$.stringPureVal(defaultValue || '', true).toLowerCase();
				$select.val(((defaultValue == 1) || (defaultValue === true) || (defaultSValue == 'true') || (defaultSValue == 'yes')) ? 'yes' : 'no');
				$select.select();
			};

			this.serializeValue = function() {
				return !!($select.val() == 'yes');
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return ($select.val() != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		CheckboxCellEditor : function(args) { // this is for 0/1 or TRUE/FALSE
			let $select;
			let defaultValue;
			let scope = this;

			this.init = function() {
				$select = $('<input type="checkbox" value="true" class="slick-cell-editor-field slick-cell-editor-field-checkbox ux-field editor-checkbox" hideFocus>');
				$select.appendTo(args.container);
				$select.focus();
			};

			this.destroy = function() {
				$select.remove();
			};

			this.focus = function() {
				$select.focus();
			};

			this.loadValue = function(item) {
				defaultValue = item[args.column.field];
				defaultSValue = _Utils$.stringPureVal(defaultValue || '', true).toLowerCase();
				if((defaultValue == 1) || (defaultValue === true) || (defaultSValue == 'true')) {
					$select.prop('checked', true);
				} else {
					$select.prop('checked', false);
				}
			};

			this.serializeValue = function() {
				return $select.prop('checked') ? true : false;
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return ($select.prop('checked') != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		LongTextCellSelector : function(args) { //-- added by unixman

			let $input, $wrapper;
			let defaultValue;
			let scope = this;

			this.init = function() {
				const $container = $('body');

				$wrapper = $('<div style="z-index:10000;position:absolute; background:#FFFFFF; padding:3px; border:1px solid #ECECEC; border-radius:4px;"></div>')
					.appendTo($container);

				$input = $('<textarea readonly hidefocus rows="5" class="slick-cell-editor-field slick-cell-editor-field-textarea ux-field" style="width:250px; height:80px; background:#FEFEFE; color:#222222;"></textarea>')
					.appendTo($wrapper);

				$('<div style="text-align:right"><button class="ux-button ux-button-xsmall">Close</button></div>')
					.appendTo($wrapper);

				$wrapper.find('button:first').bind('click', this.cancel);
			//	$wrapper.find('button:last').bind('click', this.save); // N/A ; readonly have no Save button
				$input.bind('keydown', this.handleKeyDown);

				scope.position(args.position);
				$input.focus().select();
			};

			this.handleKeyDown = function(e) {
				if (e.which == SE_Keycode_ESCAPE) {
					e.preventDefault();
					scope.cancel();
				}
				// not handling the TAB key, it is readonly
			};

			this.save = function() {
				// do nothing ; readonly
			};

			this.cancel = function() {
				$input.val(defaultValue);
				args.cancelChanges();
			};

			this.hide = function() {
				$wrapper.hide();
			};

			this.show = function() {
				$wrapper.show();
			};

			this.position = function(position) {
				$wrapper.css('top', position.top - 5).css('left', position.left - 5);
			};

			this.destroy = function() {
				$wrapper.remove();
			};

			this.focus = function() {
				$input.focus();
			};

			this.loadValue = function(item) {
				$input.val(defaultValue = item[args.column.field]);
				$input.select();
			};

			this.serializeValue = function() {
				return ''; // readonly
			};

			this.applyValue = function(item,state) {
				// do nothing ; readonly
			};

			this.isValueChanged = function() {
				return false; // readonly
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();

		}, //-- #end# unixman

		LongTextCellEditor : function (args) {
			let $input, $wrapper;
			let defaultValue;
			let scope = this;

			this.init = function() {
				const $container = $('body');

				$wrapper = $('<div style="z-index:10000;position:absolute; background:#FFFFFF; padding:3px; border:1px solid #ECECEC; border-radius:4px;"></div>')
					.appendTo($container);

				$input = $('<textarea hidefocus rows="5" class="slick-cell-editor-field slick-cell-editor-field-textarea ux-field" style="width:250px; height:80px; background:#FFFFFF; color:#111111;"></textarea>')
					.appendTo($wrapper);

				$('<div style="text-align:right"><button class="ux-button ux-button-xsmall ux-button-primary">Cancel</button><button class="ux-button ux-button-xsmall ux-button-special">Save</button></div>')
					.appendTo($wrapper);

				$wrapper.find('button:first').bind('click', this.cancel);
				$wrapper.find('button:last').bind('click', this.save);
				$input.bind('keydown', this.handleKeyDown);

				scope.position(args.position);
				$input.focus().select();
			};

			this.handleKeyDown = function(e) {
				if (e.which == SE_Keycode_ESCAPE) {
					e.preventDefault();
					scope.cancel();
				} else if (e.which == SE_Keycode_TAB) {
					e.preventDefault();
					_BwUtils$.catchKeyTAB(e); // fix by unixman
				}
			};

			this.save = function() {
				args.commitChanges();
			};

			this.cancel = function() {
				$input.val(defaultValue);
				args.cancelChanges();
			};

			this.hide = function() {
				$wrapper.hide();
			};

			this.show = function() {
				$wrapper.show();
			};

			this.position = function(position) {
				$wrapper.css('top', position.top - 5).css('left', position.left - 5);
			};

			this.destroy = function() {
				$wrapper.remove();
			};

			this.focus = function() {
				$input.focus();
			};

			this.loadValue = function(item) {
				$input.val(defaultValue = item[args.column.field]);
				$input.select();
			};

			this.serializeValue = function() {
				return _Utils$.stringPureVal($input.val() || ''); // unixman fix
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == '' && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		} //,

	};

	Object.freeze(SlickEditor);

	$.extend(window, SlickEditor);

})(jQuery);

// #END
