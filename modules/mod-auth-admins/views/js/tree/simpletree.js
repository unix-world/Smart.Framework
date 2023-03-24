// jQuery Simple Tree v.2013-12-06
// https://github.com/innoq/simpletree
// (c) 2013 innoQ Deutschland GmbH
// License: Apache License, Version 2.0

// modified by unixman, v.180606

(function($) {

"use strict";

function CollapsibleTree(list, options) {

	var self = this;

	options = options || {};
	if(!options.useAnimation) {
		options.useAnimation = false;
	}
	if(!options.signCollapsed) {
		options.signCollapsed = '▸';
	}
	if(!options.signExpanded) {
		options.signExpanded = '▾';
	}

	list = list.jquery ? list : $(list);
	list.addClass("simpletree-tree").on("click", "span.simpletree-toggle", function() {
		var context = { context: this, instance: self };
		return self.onToggle.apply(context, arguments);
	});
	list.attr('data-simpletree-use-animation', options.useAnimation ? 1 : 0);
	list.attr('data-simpletree-sign-collapsed', encodeURIComponent(String(options.signCollapsed)));
	list.attr('data-simpletree-sign-expanded', encodeURIComponent(String(options.signExpanded)));

	if(!options.nocheck) {
		list.on("change", "input:checkbox", this.onChange);
	}

	$("li:has(ul)", list).prepend('<span class="simpletree-button simpletree-toggle">' + String(options.signExpanded) + '</span> ');
	$("li", list).not(":has(ul)").prepend('<span class="simpletree-notoggle">' + '&nbsp;' + '</span> ');

	var toggle = function(i, node) {
		var btn = $(node);
		self.toggle(btn, true);
	};
	$(".simpletree-toggle", list).each(toggle);
	$("input:checked").parents("li").children(".simpletree-toggle").each(toggle);

	list.data("simpletree", this);

}

CollapsibleTree.prototype.onToggle = function(ev) {
	var btn = $(this.context);
	this.instance.toggle(btn);
	// TODO: unselect hidden items?
};

CollapsibleTree.prototype.onChange = function(ev) {
	var checkbox = $(this);
	var active = checkbox.prop("checked");
	checkbox.closest("li").find("input:checkbox").prop("checked", active);
};

// `btn` is a jQuery object referencing a toggle button
CollapsibleTree.prototype.toggle = function(btn, init) {
	var item = btn.closest("li");
	var state = item.attr('data-simpletree');
	//console.log('state=' + state);
	if(state !== 'collapsed') {
		state = 'expanded';
	}
	this.setState(item, state, init);
};

// `item` is a jQuery object referencing the respective list item
// `state` is either "collapsed" or "expanded"
CollapsibleTree.prototype.setState = function(item, state, init) {
	var collapse;
	if(state === "collapsed") {
		collapse = false;
	} else {
		collapse = true;
	}
	if(init !== true) {
		collapse = !collapse;
	}
	var list = item.parents('ul.simpletree-tree');
	if(list) {
		item.attr('data-simpletree', collapse ? 'expanded' : 'collapsed');
		var animated = list.attr('data-simpletree-use-animation') == '1' ? true : false;
		var action = animated ? ["slideUp", "slideDown"] : ["hide", "show"];
		action = collapse ? action[1] : action[0];
		item.children("ul")[action]();
		var attr_c = String(decodeURIComponent(list.attr('data-simpletree-sign-collapsed')));
		var attr_e = String(decodeURIComponent(list.attr('data-simpletree-sign-expanded')));
		item.children(".simpletree-toggle").html(!collapse ? String(attr_c) : String(attr_e));
	}
};

// jQuery API wrapper
$.fn.simpletree = function(options) {
	this.each(function(i, node) {
		new CollapsibleTree(node, options);
	});
	return this;
};

}(jQuery));
