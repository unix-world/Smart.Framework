/**
	Slimbox v2.04.fix7 - The ultimate lightweight Lightbox clone for jQuery
	(c) 2007-2010 Christophe Beyls <http://www.digitalia.be>
	MIT-style license.

	(c) 2006-2021 unix-world.org
	r.20210407
	fixes by unixman:
		* overlay fix for jQuery 1.9 or later
		* changed XHTML Tags to HTML5
		* fixes by unixman, avoid preload prev/next image
		* use svg icons
		* fix parseInt() with Math.ceil()
		* fix is image width/height could not be detected
*/

(function($) {

	// Global variables, accessible to Slimbox only
	var win = $(window), options, images, activeImage = -1, activeURL,
		prevImage, nextImage, compatibleOverlay, middle, centerWidth, centerHeight,
		hiddenElements = [], documentElement = document.documentElement;

	// Preload images
	var preload = {},
	//var preloadPrev = new Image(), // unixman
	//var preloadNext = new Image(),

	// DOM elements
	overlay, center, image, sizer, prevLink, nextLink, bottomContainer, bottom, caption, number;

	//##### Initialization

	$(function() {

		// Append the Slimbox HTML code at the bottom of the document
		$("body").append(
			$([
				//overlay = $('<div id="lbOverlay"></div>')[0],
				overlay = $('<div id="lbOverlay"></div>').click(close)[0], // unixman :: this is a fix for jQuery 1.9 or later
				center = $('<div id="lbCenter"></div>')[0],
				bottomContainer = $('<div id="lbBottomContainer"></div>')[0]
			]).css({
				'display': 'none'
			})
		);

		image = $('<div id="lbImage"></div>').appendTo(center).append(
			sizer = $('<div style="position: relative;"></div>').append([
				prevLink = $('<a id="lbPrevLink" href="#"></a>').click(previous)[0],
				nextLink = $('<a id="lbNextLink" href="#"></a>').click(next)[0]
			])[0]
		)[0];

		bottom = $('<div id="lbBottom"></div>').appendTo(bottomContainer).append([
			//$('<a id="lbCloseLink" href="#"></a>').add(overlay).click(close)[0], // unixman :: this is a fix for jQuery 1.9 or later
			$('<a id="lbCloseLink" href="#"></a>').click(close)[0], // unixman :: extra feature
			caption = $('<div id="lbCaption"></div>')[0],
			number = $('<div id="lbNumber"></div>')[0],
			$('<div style="clear: both;"></div>')[0]
		])[0];

	});


	//##### API

	// Open Slimbox with the specified parameters
	$.slimbox = function(_images, startImage, _options) {

		options = $.extend({
			loop: false,					// Allows to navigate between first and last images
			overlayOpacity: 0.75,			// 1 is opaque, 0 is completely transparent (change the color in the CSS file)
			overlayFadeDuration: 400,		// Duration of the overlay fade-in and fade-out animations (in milliseconds)
			resizeDuration: 400,			// Duration of each of the box resize animations (in milliseconds)
			resizeEasing: "swing",			// "swing" is jQuery's default easing
			initialWidth: 250,				// Initial width of the box (in pixels)
			initialHeight: 250,				// Initial height of the box (in pixels)
			imageFadeDuration: 400,			// Duration of the image fade-in animation (in milliseconds)
			captionAnimationDuration: 400,	// Duration of the caption animation (in milliseconds)
			counterText: "# {x} / {y}",		// Translate or change as you wish, or set it to false to disable counter text for image groups
			closeKeys: [27, 88, 67],		// Array of keycodes to close Slimbox, default: Esc (27), 'x' (88), 'c' (67)
			previousKeys: [37, 80],			// Array of keycodes to navigate to the previous image, default: Left arrow (37), 'p' (80)
			nextKeys: [39, 78]				// Array of keycodes to navigate to the next image, default: Right arrow (39), 'n' (78)
		}, _options);

		// The function is called for a single image, with URL and Title as first two arguments
		if(typeof _images == "string") {
			_images = [[_images, startImage]];
			startImage = 0;
		} //end if

		middle = win.scrollTop() + (win.height() / 2);
		centerWidth = options.initialWidth;
		centerHeight = options.initialHeight;
		$(center).css({
			'top': Math.ceil(Math.max(0, middle - (centerHeight / 2))) + 'px',
			'width': Math.ceil(centerWidth) + 'px',
			'height': Math.ceil(centerHeight) + 'px',
			'marginLeft': Math.ceil(-1 * centerWidth / 2) + 'px'
		}).show();

		compatibleOverlay = (overlay.currentStyle && (overlay.currentStyle.position != "fixed"));
		if(compatibleOverlay) {
			overlay.style.position = "absolute";
		} //end if
		$(overlay).css({
			'opacity': options.overlayOpacity
		}).fadeIn(options.overlayFadeDuration);

		position();
		setup(1);

		images = _images;
		options.loop = options.loop && (images.length > 1);

		return changeImage(startImage);

	};

	/*
		options:	Optional options object, see jQuery.slimbox()
		linkMapper:	Optional function taking a link DOM element and an index as arguments and returning an array containing 2 elements:
				the image URL and the image caption (may contain HTML)
		linksFilter:	Optional function taking a link DOM element and an index as arguments and returning true if the element is part of
				the image collection that will be shown on click, false if not. "this" refers to the element that was clicked.
				This function must always return true when the DOM element argument is "this".
	*/
	$.fn.slimbox = function(_options, linkMapper, linksFilter) {
		linkMapper = linkMapper || function(el) {
			return [el.href, el.title];
		};

		linksFilter = linksFilter || function() {
			return true;
		};

		var links = this;

		return links.unbind("click").click(function() {
			// Build the list of images that will be displayed
			var link = this, startIndex = 0, filteredLinks, i = 0, length;
			filteredLinks = $.grep(links, function(el, i) {
				return linksFilter.call(link, el, i);
			});
			// We cannot use jQuery.map() because it flattens the returned array
			for(length = filteredLinks.length; i < length; ++i) {
				if (filteredLinks[i] == link) startIndex = i;
				filteredLinks[i] = linkMapper(filteredLinks[i], i);
			} //end for
			return $.slimbox(filteredLinks, startIndex, _options);
		});

	};


	/*
		Internal functions
	*/

	function position() {
		var l = win.scrollLeft(), w = win.width();
		$([center, bottomContainer]).css({
			'left': Math.ceil(l + (w / 2)) + 'px'
		});
		if(compatibleOverlay) {
			$(overlay).css({
				left: Math.ceil(l) + 'px',
				top: Math.ceil(win.scrollTop()) + 'px',
				width: Math.ceil(w) + 'px',
				height: Math.ceil(win.height()) + 'px'
			});
		} //end if
	} //END FUNCTION

	function setup(open) {
		if(open) {
			$("object").add("embed").each(function(index, el) {
				hiddenElements[index] = [el, el.style.visibility];
				el.style.visibility = "hidden";
			});
		} else {
			$.each(hiddenElements, function(index, el) {
				el[0].style.visibility = el[1];
			});
			hiddenElements = [];
		} //end if else
		var fn = open ? "bind" : "unbind";
		win[fn]("scroll resize", position);
		$(document)[fn]("keydown", keyDown);
	} //END FUNCTION

	function keyDown(event) {
		var code = event.keyCode, fn = $.inArray;
		// Prevent default keyboard action (like navigating inside the page)
		return (fn(code, options.closeKeys) >= 0) ? close()
			: (fn(code, options.nextKeys) >= 0) ? next()
			: (fn(code, options.previousKeys) >= 0) ? previous()
			: false;
	} //END FUNCTION

	function previous() {
		return changeImage(prevImage);
	} //END FUNCTION

	function next() {
		return changeImage(nextImage);
	} //END FUNCTION

	function changeImage(imageIndex) {
		if(imageIndex >= 0) {
			activeImage = imageIndex;
			activeURL = images[activeImage][0];
			prevImage = (activeImage || (options.loop ? images.length : 0)) - 1;
			nextImage = ((activeImage + 1) % images.length) || (options.loop ? 0 : -1);
			stop();
			center.className = "lbLoading";
			preload = new Image();
			preload.onload = animateBox;
			preload.src = activeURL;
		} //end if
		return false;
	} //END FUNCTION

	function animateBox() {

		//--
		var the_offset_left = 20;
		var the_offset_top = 20;
		//--
		var the_img_width = parseInt(preload.width);
		var the_img_height = parseInt(preload.height);
		//--
		if(the_img_width <= 0) {
			console.warn('Slideshow: Invalid Image Width !');
			the_img_width = 800; // add a dummy value
		} //end if
		if(the_img_height <= 0) {
			console.warn('Slideshow: Invalid Image Height !');
			the_img_height = 800; // add a dummy value
		} //end if
		//--

		//-- ratio
		var the_img_ratio = 1;
		//-- resize by height
		var the_hmax = (win.height() - the_offset_top - 75);
		if((the_img_height > 0) && (the_img_height > the_hmax)) {
			the_img_ratio = the_hmax / the_img_height;
			the_img_height = Math.ceil(the_hmax);
			the_img_width = Math.ceil(the_img_width * the_img_ratio);
		} //end if
		the_img_height = Math.ceil(the_img_height);
		//-- resize by width
		var the_wmax = (win.width() - the_offset_left - 5);
		if((the_img_width > 0) && (the_img_width > the_wmax)) {
			the_img_ratio = the_wmax / the_img_width;
			the_img_width = Math.ceil(the_wmax);
			the_img_height = Math.ceil(the_img_height * the_img_ratio);
		} //end if
		the_img_width = Math.ceil(the_img_width);
		//--

		//--
		center.className = '';
		$(image).css({
			'background-image': 'url(' + activeURL + ')',
			'background-size': the_img_width + 'px ' + the_img_height + 'px',
			'background-repeat': 'no-repeat',
			'background-position': 'center',
			'visibility': 'hidden',
			'display': ''
		});
		var the_sizer_width = the_img_width;
		if(the_sizer_width < 250) {
			the_sizer_width = 250;
		} //end if
		var the_sizer_height = the_img_height;
		if(the_sizer_height < 100) {
			the_sizer_height = 100;
		} //end if
		$(sizer).width(the_sizer_width);
		$([sizer, prevLink, nextLink]).height(the_sizer_height);
		//--
		$(caption).html(images[activeImage][1] || "");
		$(number).html((((images.length > 1) && options.counterText) || "").replace(/{x}/, activeImage + 1).replace(/{y}/, images.length));
		//--

		//--
		/* unixman
		if(prevImage >= 0) {
			preloadPrev.src = images[prevImage][0];
		} //end if
		if(nextImage >= 0) {
			preloadNext.src = images[nextImage][0];
		} //end if
		*/
		//--

		//--
		centerWidth = parseInt(image.offsetWidth);
		centerHeight = parseInt(image.offsetHeight);
		var sTop = parseInt(win.scrollTop());
		//--
		var top = Math.ceil(sTop + 10);
		if(center.offsetHeight != centerHeight) {
			$(center).animate({
				height: centerHeight,
				top: top
			}, options.resizeDuration, options.resizeEasing);
		} //end if
		if(center.offsetWidth != centerWidth) {
			$(center).animate({
				width: centerWidth,
				marginLeft: Math.ceil(-1 * centerWidth / 2)
			}, options.resizeDuration, options.resizeEasing);
		} //end if
		//--
		$(center).queue(function() {
			$(bottomContainer).css({
				'width': Math.ceil(centerWidth) + 'px',
				'top': Math.ceil(top + centerHeight) + 'px',
				'marginLeft': Math.ceil(-1 * centerWidth / 2) + 'px',
				'visibility': 'hidden',
				'display': ''
			});
			$(image).css({
				'display': 'none',
				'visibility': '',
				'opacity': ''
			}).fadeIn(options.imageFadeDuration, animateCaption);
		});
		//--

	} //END FUNCTION

	function animateCaption() {
		if (prevImage >= 0) $(prevLink).show();
		if (nextImage >= 0) $(nextLink).show();
		$(bottom).css({
			'marginTop': Math.ceil(-1 * bottom.offsetHeight) + 'px'
		}).animate({marginTop: 0}, options.captionAnimationDuration);
		bottomContainer.style.visibility = "";
	} //END FUNCTION

	function stop() {
		preload.onload = null;
		//preload.src = preloadPrev.src = preloadNext.src = activeURL; // unixman
		preload.src = activeURL; // unixman
		$([center, image, bottom]).stop(true);
		$([prevLink, nextLink, image, bottomContainer]).hide();
	} //END FUNCTION

	function close() {
		if(activeImage >= 0) {
			stop();
			activeImage = prevImage = nextImage = -1;
			$(center).hide();
			$(overlay).stop().fadeOut(options.overlayFadeDuration, setup);
		} //end if
		return false;
	} //END FUNCTION

})(jQuery);

// #END
