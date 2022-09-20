// FORMATTERS & EDITORS for SlickGrid v1.4.3.1.smart.20220915
// Fixes:
// 	* 20160413: added selection handler
// 	* 20170323: replaced out references to $.ui(.keyCode)
// 	* 20170425: cleanup garbage code
// 	* 20181109: fix CheckboxCellEditor
// 	* 20200501: jQuery 3.5.0 ready (fixed XHTML Tags)

(function($) {

	// get from jQuery UI
	var SE_Keycode_ESCAPE = 27; // $.ui.keyCode.ESCAPE
	var SE_Keycode_ENTER  = 13; // $.ui.keyCode.ENTER
	var SE_Keycode_LEFT   = 37; // $.ui.keyCode.LEFT
	var SE_Keycode_RIGHT  = 39; // $.ui.keyCode.RIGHT
	var SE_Keycode_TAB    =  9; // $.ui.keyCode.TAB

	var SlickEditor = {

		TextCellSelector : function(args) { //-- added by unixman
			var $input;
			var defaultValue;
			var scope = this;

			this.init = function() {
				$input = $('<input type="text" class="ux-field" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;" readonly>')
					.appendTo(args.container)
					.bind("keydown.nav", function(e) {
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
				defaultValue = item[args.column.field] || "";
				$input.val(defaultValue);
				$input[0].defaultValue = defaultValue;
				$input.select();
			};

			this.serializeValue = function() {
				return $input.val();
			};

			this.applyValue = function(item,state) {
				// do nothing
			};

			this.isValueChanged = function() {
				return false;
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
			var $input;
			var defaultValue;
			var scope = this;

			this.init = function() {
				$input = $('<input type="text" class="ux-field editor-text" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;">')
					.appendTo(args.container)
					.bind("keydown.nav", function(e) {
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
				defaultValue = item[args.column.field] || "";
				$input.val(defaultValue);
				$input[0].defaultValue = defaultValue;
				$input.select();
			};

			this.serializeValue = function() {
				return $input.val();
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == "" && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				if (args.column.validator) {
					var validationResults = args.column.validator($input.val());
					if (!validationResults.valid)
						return validationResults;
				}

				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		IntegerCellEditor : function(args) {
			var $input;
			var defaultValue;
			var scope = this;

			this.init = function() {
				$input = $('<input type="number" class="ux-field editor-text" style="width:100%!important; height:100%!important; margin:0!important; background:#FFFFFF;">');

				$input.bind("keydown.nav", function(e) {
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
				return parseInt($input.val(),10) || 0;
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == "" && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				if (isNaN($input.val()))
					return {
						valid: false,
						msg: "Please enter a valid integer"
					};

				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		YesNoSelectCellEditor : function(args) {
			var $select;
			var defaultValue;
			var scope = this;

			this.init = function() {
				$select = $('<select tabIndex="0" class="editor-yesno"><option value="yes">Yes</option><option value="no">No</option></select>');
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
				$select.val((defaultValue = item[args.column.field]) ? "yes" : "no");
				$select.select();
			};

			this.serializeValue = function() {
				return ($select.val() == "yes");
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

		CheckboxCellEditor : function(args) {
			var $select;
			var defaultValue;
			var scope = this;

			this.init = function() {
				$select = $('<input type="checkbox" value="true" class="editor-checkbox" hideFocus>');
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
				if((defaultValue == 1) || (defaultValue == 'true')) {
					$select.prop("checked", true);
				} else {
					$select.prop("checked", false);
				}
			};

			this.serializeValue = function() {
				return $select.prop("checked") ? true : false;
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return ($select.prop("checked") != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		},

		LongTextCellEditor : function (args) {
			var $input, $wrapper;
			var defaultValue;
			var scope = this;

			this.init = function() {
				var $container = $("body");

				$wrapper = $('<div style="z-index:10000;position:absolute;background:white;padding:5px;border:3px solid gray; -moz-border-radius:10px; border-radius:10px;"></div>')
					.appendTo($container);

				$input = $('<textarea hidefocus rows=5 style="backround:white;width:250px;height:80px;border:0;outline:0"></textarea>')
					.appendTo($wrapper);

				$('<div style="text-align:right"><button>Save</button><button>Cancel</button></div>')
					.appendTo($wrapper);

				$wrapper.find("button:first").bind("click", this.save);
				$wrapper.find("button:last").bind("click", this.cancel);
				$input.bind("keydown", this.handleKeyDown);

				scope.position(args.position);
				$input.focus().select();
			};

			this.handleKeyDown = function(e) {
				if (e.which == SE_Keycode_ENTER && e.ctrlKey) {
					scope.save();
				}
				else if (e.which == SE_Keycode_ESCAPE) {
					e.preventDefault();
					scope.cancel();
				}
				else if (e.which == SE_Keycode_TAB && e.shiftKey) {
					e.preventDefault();
					grid.navigatePrev();
				}
				else if (e.which == SE_Keycode_TAB) {
					e.preventDefault();
					grid.navigateNext();
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
				$wrapper
					.css("top", position.top - 5)
					.css("left", position.left - 5)
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
				return $input.val();
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == "" && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		} //,

		/*
		// this ONLY works with jQueryUI !!
		// this is just a sample ; as formatters, external editors can be built outside this class !
		jqUiDateCellEditor : function(args) {
			var $input;
			var defaultValue;
			var scope = this;
			var calendarOpen = false;

			this.init = function() {
				$input = $('<input type="text" class="ux-field editor-text">');
				$input.appendTo(args.container);
				$input.focus().select();
				$input.datepicker({
					showOn: "button",
					buttonImageOnly: true,
					buttonImage: "jquery-ui/calendar.gif",
					beforeShow: function() { calendarOpen = true },
					onClose: function() { calendarOpen = false }
				});
				$input.width($input.width() - 18);
			};

			this.destroy = function() {
				$.datepicker.dpDiv.stop(true,true);
				$input.datepicker("hide");
				$input.datepicker("destroy");
				$input.remove();
			};

			this.show = function() {
				if (calendarOpen) {
					$.datepicker.dpDiv.stop(true,true).show();
				}
			};

			this.hide = function() {
				if (calendarOpen) {
					$.datepicker.dpDiv.stop(true,true).hide();
				}
			};

			this.position = function(position) {
				if (!calendarOpen) return;
				$.datepicker.dpDiv
					.css("top", position.top + 30)
					.css("left", position.left);
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
				return $input.val();
			};

			this.applyValue = function(item,state) {
				item[args.column.field] = state;
			};

			this.isValueChanged = function() {
				return (!($input.val() == "" && defaultValue == null)) && ($input.val() != defaultValue);
			};

			this.validate = function() {
				return {
					valid: true,
					msg: null
				};
			};

			this.init();
		}
		*/

	};

	$.extend(window, SlickEditor);

})(jQuery);

// #END
