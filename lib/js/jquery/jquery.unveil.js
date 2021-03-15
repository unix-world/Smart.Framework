/**
 * jQuery Unveil
 * A very lightweight jQuery plugin to lazy load images (video tag does not need this as it can use preload="metadata" or preload="none" attribute)
 * https://github.com/luis-almeida
 * Licensed under the MIT license.
 * Copyright 2013 Luís Almeida
 *
 * (c) 2019-2021 unix-world.org
 * r.20210316
 * contains fixes by unixman:
 * 	- optimized for latest jquery 3.3.1
 * 	- replaced 'threshold' option with a complex object option {threshold:0, attribute:''}
 * 	- add support for allow tags to support both: img[src] and picture[source[srcset]]
 * 	- fix: unveil also the hidden images (ex: some carousel images may be hidden)
 */

;(function($) {

	$.fn.unveil = function(options, callback) {

		var defaults = {
			threshold: 0, 											// image offset treshold in pixels : INTEGER+
			attribute: 'data-src', 									// image attribute ; default is: 'data-src' with fallback
			allowtags: { 'img':'src', 'picture.source':'srcset' } 	// allowed tags with attribute and parents ; Ex: tag:attribute or parent.tag:attribute
		};

		options = $.extend({}, defaults, options);

		var $w = $(window);
		var th = Math.ceil(options.threshold > 0 ? options.threshold : 0) || 0;
		var dataDefAttr = 'data-src';
		var dataAttr = String(dataDefAttr);
		if(options.attribute) {
			options.attribute = String(options.attribute || '');
			if(options.attribute.indexOf('data-') === 0) {
				dataAttr = String(options.attribute);
			}
		}
		var allowTags = {};
		if(options.allowtags && $.isPlainObject(options.allowtags)) {
			allowTags = options.allowtags;
		}
		var images = this;
		var loaded;

		this.one('unveil', function(){

			var source = $(this).attr(String(dataAttr));
			if(!source) {
				return;
			}
			source = String(source || '');
			if((source == '') || (source.indexOf('#unveil!') == 0)) {
				return;
			}

			var theParentTag = String($(this).parent().prop('tagName') || '').toLowerCase();
			var theTag = String($(this).prop('tagName') || '').toLowerCase();

		//	console.log('Unveil element # Parent Tag is: ' + theParentTag);
		//	console.log('Unveil element # The Tag is: ' + theTag);

			var isKeyValid, isParentValid, isValValid, isTagValid;
			var theElSrc, theUnveilDataAttr, theObjKey, theObjVal, theObjParent, theOriginalKey;
			var regexKey = /^[a-z\.]+$/;
			var regexVal = /^[a-z]+$/;
			if(allowTags && $.isPlainObject(allowTags)) {
				for(theObjKey in allowTags) {
					isKeyValid = false;
					isParentValid = false;
					isValValid = false;
					isTagValid = false;
					theOriginalKey = '';
					theObjVal = ''; // reset
					theObjParent = ''; // reset
					theObjKey = String(theObjKey || '').toLowerCase();
					theOriginalKey = theObjKey;
					if(theObjKey && (regexKey.test(theObjKey))) {
						if(theObjKey.indexOf('.') !== -1) {
							theObjParent = theObjKey.split('.');
							if(theObjParent.length == 2) {
								theObjKey = String(theObjParent[1] || '');
								theObjParent = String(theObjParent[0] || '');
								if(theObjParent && (theObjParent.length >= 1) && (theObjParent.length <= 16) && (regexVal.test(theObjParent))) { // shortest HTML tag is a ; longest is blockquote ; parent can be empty
									isParentValid = true;
								}
							}
						} else {
							isParentValid = true;
						}
						if(isParentValid) {
							if(theObjKey && (theObjKey.length >= 1) && (theObjKey.length <= 16) && (regexVal.test(theObjKey))) { // shortest HTML tag is a ; longest is blockquote
								isKeyValid = true;
							}
							if(isKeyValid) {
								theObjVal = String(allowTags[String(theObjParent ? String(String(theObjParent) + '.') : '') + String(theObjKey)] || '').toLowerCase();
								if(theObjVal && (theObjVal.length >= 1) && (theObjVal.length <= 16) && (regexVal.test(theObjVal))) { // max allowed html attribute is 16 chars
									isValValid = true;
								//	console.log('Unveil # ' + theObjKey + '=' + theObjVal + ' # PARENT=' + theObjParent);
								}
							}
						}
					}
					if((!isParentValid) || (!isValValid) || (!isValValid)) {
						console.error('Unveil # INVALID setting for option.allowtags @ Key: `' + String(theOriginalKey) + '` with Value: `' + String(theObjVal) + '`');
					} else {
						if(theObjKey == theTag) { // if tag is the same as the one found
							if(theObjParent) {
								if(theObjParent == theParentTag) { // if have parent defined and parent of tag is the same
									isTagValid = true;
								}
							} else {
								isTagValid = true;
							}
						}
						if(isTagValid) {
							$(this).attr(theObjVal, String(source));
							$(this).attr(dataAttr, '#unveil!' + theOriginalKey + ':' + theObjVal);
							if(typeof callback === 'function') {
								callback.call(this);
							}
						}
					}
				}
			}

		});

		function unveil() {
			var inview = images.filter(function(){
				var $e = $(this);
				// below code is commented out to unveil also hidden images (see the comments in the head of this file)
			//	if($e.is(':hidden')) {
			//		return;
			//	}
				var wt = $w.scrollTop(),
					wb = wt + $w.height(),
					et = $e.offset().top,
					eb = et + $e.height();
				var shouldLoad = (eb >= wt - th && et <= wb + th);
				//console.log('Unveil Loader Log', shouldLoad, 'eb='+eb, 'wt='+wt, 'th='+th, 'et='+et, 'wb='+wb);
				return shouldLoad;
			});
			loaded = inview.trigger('unveil');
			images = images.not(loaded);
		}
		$w.on('scroll.unveil resize.unveil lookup.unveil', unveil);
		unveil();
		return this;
	};

})(window.jQuery);

// #END
