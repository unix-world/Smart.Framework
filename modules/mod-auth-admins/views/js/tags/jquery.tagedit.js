
// jQuery TagEdit v.1.0
// (c) 2020 unix-world.org
// inspired from: jsfiddle.net / Javascript TagEdit (License MIT)
// r.20220918

(function(jQuery) {

	jQuery.fn.tagEdit = function(options) {

		var defaults = {

			items: [],
			separator: ',',

			tagMaxChars: 20, // between 1 and 255
			tagMaxNum: 10, // between 1 and 1024

			tagNamePattern: /^[A-Za-z0-9\- ]+$/g,

			tagCssClass: 'tagEdit',
			tagRemoveText: 'Remove this Tag',

			readOnly: false // set this to TRUE to avoid edit tags

		};

		options = jQuery.extend(defaults, options);

		var itemDataList, itemDataText = this, itemDataHiddenFld;
		var itemDataArr = [];
		var itemDataObj = {};

		var maxTagLen = (Math.floor(options.tagMaxChars) >= 1 && Math.floor(options.tagMaxChars) <= 255) ? Math.floor(options.tagMaxChars) : 10;
		var maxTagNum = (Math.floor(options.tagMaxNum) >= 1 && Math.floor(options.tagMaxNum) <= 1024) ? Math.floor(options.tagMaxNum) : 5;

		return this.each(function() {

			function addTag(tag) {
				try {
					new RegExp(options.tagNamePattern);
				} catch(e) {
					console.error('jQuery TagEdit: Invalid Regex: ' + options.tagNamePattern);
					return false;
				} //end try catch
				tag = String(jQuery.trim(tag));
				if(tag.length <= 0) {
					return false; // empty tag
				} //end if
				if(tag.length > maxTagLen) {
					return false; // tag length oversized
				} //end if
				if(!tag.match(options.tagNamePattern)) {
					return false; // regex not match
				} //end if
				var objLen = 0;
				for(var obj in itemDataObj) {
					if(itemDataObj[obj] === true) {
						objLen++;
					} //end if
				} //end for
				if(objLen >= maxTagNum) {
					return false; // max tags limit reached
				} //end if
				if(typeof itemDataObj[tag] == 'undefined') {
					itemDataObj[tag] = false;
				} //end if
				if(itemDataObj[tag] === true) {
					return false; // duplicate tag
				} //end if
				var item = jQuery('<li></li>');
				if(options.readOnly !== true) {
					item.attr('class', options.tagCssClass);
				} //end if
				item.text(tag);
				if(options.readOnly !== true) {
					item.attr('title', String(options.tagRemoveText));
					item.click(function() {
						var txt = jQuery.trim(jQuery(this).text());
						if(txt != '') {
							itemDataObj[txt] = false;
						} //end if
						jQuery(this).remove();
						dataParse();
					});
				} //end if
				itemDataList.append(item);
				itemDataObj[tag] = true;
				return true;
			} //end function

			function dataStructBuild() {
				itemDataArr = [];
				var items = jQuery('li', itemDataList);
				for(var i=0; i<items.length; i++) {
					itemDataArr.push(jQuery.trim(jQuery(items[i]).text()));
				} //end for
				if(options.readOnly !== true) {
					itemDataHiddenFld.val(itemDataArr.join(options.separator));
				} //end if
			} //end function

			function dataParse() {
				var items = itemDataText.val().split(options.separator);
				for(var i=0; i<items.length; i++) {
					addTag(String(jQuery.trim(items[i])));
				} //end for
				itemDataText.val('');
				dataStructBuild();
			} //end function

			function keyEvtHandler(ev) {
				var keyCode = (ev.which) ? ev.which : ev.keyCode;
				switch(keyCode) {
					case 13:
						var theVal = jQuery.trim(itemDataText.val());
						if(theVal == '') {
							return true;
						} //end if
						dataParse();
						return false;
						break;
					default:
						// no handler
				} //end switch
				return true;
			} //end function

			jQuery.fn.extend({
				tagEditTagsGet: function() {
					return itemDataArr.join(options.separator);
				},
				tagEditTagAdd: function(tag) {
					return addTag(tag);
				}
			});

			itemDataText.after('<div style="clear:both;"></div>');

			if(options.readOnly !== true) {
				itemDataHiddenFld = jQuery('<input type="hidden">');
				itemDataHiddenFld.attr('name', itemDataText.attr('name'));
				itemDataText.attr('name', '');
				itemDataText.attr('maxlength', maxTagLen);
				itemDataText.after(itemDataHiddenFld);
			} //end if

			itemDataList = jQuery(document.createElement('ul'));
			itemDataList.attr('class', options.tagCssClass);

			jQuery(this).after(itemDataList);

			for(var i=0; i<options.items.length; i++) {
				addTag(jQuery.trim(options.items[i]));
			} //end for

			if(options.readOnly !== true) {
				dataParse();
				jQuery(this).keypress(keyEvtHandler);
			} //end if

		});
	};

})(jQuery);

// #END
