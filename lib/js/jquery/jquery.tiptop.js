/*
 * jQuery TipTop v1.0.2
 * http://gilbitron.github.io/TipTop
 *
 * Copyright 2013, Dev7studios
 * Free to use and abuse under the MIT license.
 *
 * (c) 2019-2021 unix-world.org
 * contain fixes by unixman: r.20210415
 *
 */

;(function($, window, document, undefined) {

	var pluginName = 'tipTop',
		defaults = {
			offsetVertical: 10, // Vertical offset
			offsetHorizontal: 10  // Horizontal offset
		},
		initialized = false;

	function TipTop(element, options) {
		this.el = element;
		this.$el = $(this.el);
		this.options = $.extend({}, defaults, options);
		this.init();
	}

	TipTop.prototype = {
		init: function() {
			var $this = this;
			if(!initialized) {
				initialized = true;
				$('<div id="tiptop-tooltip-jquery" class="tiptop"></div>').appendTo('body').hide();
			} //end if
			var $tooltip = $('#tiptop-tooltip-jquery');
			$this.$el.on('mouseover', function() {
				var title = $(this).attr('data-tiptop-title');
				if(!title) {
					title = $(this).attr('title');
					if(!title) {
						return; // nothing to do
					} //end if
					$(this).attr('data-tiptop-title', title).attr('title', '');
				} //end if
				$tooltip.text(title).show();
			}).on('mouseout mouseleave focus blur click', function(){
				$tooltip.text('').hide();
			}).on('mousemove', function(e) {
				var title = $(this).attr('data-tiptop-title');
				if(!title) {
					return;
				} //end if
				var top = e.pageY + $this.options.offsetVertical,
					bottom = 'auto',
					left = e.pageX + $this.options.offsetHorizontal,
					right = 'auto';
				if(top + $tooltip.outerHeight() >= $(window).scrollTop() + $(window).height()) {
					bottom = $(window).height() - top + ($this.options.offsetVertical * 2);
					top = 'auto';
				} //end if
				if(left + $tooltip.outerWidth() >= $(window).width()) {
					right = $(window).width() - left + ($this.options.offsetHorizontal * 2);
					left = 'auto';
				} //end if
				$tooltip.css({ 'top': top, 'bottom': bottom, 'left': left, 'right': right }).show();
			});
		}
	};

	$.fn[pluginName] = function(options){
		return this.each(function(){
			if(!$.data(this, pluginName)){
				$.data(this, pluginName, new TipTop(this, options));
			} //end if
		});
	};

})(jQuery, window, document);

// #END
