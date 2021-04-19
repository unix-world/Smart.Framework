
// CLEditor FullScreen
// (c) 2015-2021 unix-world.org
// v.20210413

(function($) {

	//Style for fullscreen mode
	var fullscreen = '';
	var style_main = '';
	var style_iframe = '';
	var style_area = '';

	// Define the fullscreen button
	$.cleditor.buttons.fullscreen = {
		name: 'fullscreen',
		image: 'fullscreen.png',
		title: 'Fullscreen',
		command: '',
		popupName: '',
		popupClass: '',
		popupContent: '',
		getPressed: fullscreenGetPressed,
		buttonClick: fullscreenButtonClick,
	};

	// Add the button to the default controls before the bold button
	$.cleditor.defaultOptions.controls = $.cleditor.defaultOptions.controls.replace("source", "fullscreen | source");

	function fullscreenGetPressed(data) {
		//--
		return data.editor.$main.hasClass('fullscreen');
		//--
	} //END FUNCTION

	function fullscreenButtonClick(e, data) {
		//--
		var main = data.editor.$main;
		var iframe = data.editor.$frame;
		var area = data.editor.$area;
		var fullscreenAreaIframe = '';
		//--
		if(main.hasClass('fullscreen')) {
			//--
			if(typeof(smartJ$Browser) != 'undefined') {
				smartJ$Browser.OverlayHide();
			} //end if
			//--
			main.attr('style', style_main).removeClass('fullscreen');
			iframe.attr('style', style_iframe);
			area.attr('style', style_area);
			//--
			data.editor.refresh(data.editor); // bugfix
			//--
		} else {
			//--
			style_main = main.attr('style');
			style_iframe = iframe.attr('style');
			style_area = area.attr('style');
			//--
			fullscreen = 'display:block; position:fixed; left:5px; top:5px; width: ' + (parseInt($(window).width()) - 15) + 'px; height: ' + (parseInt($(window).height()) - 15) + 'px; z-index: 2147403000;';
			main.attr('style', fullscreen).addClass('fullscreen');
			fullscreenAreaIframe = 'width:' + parseInt(main.width() - 5) + 'px; height:' + parseInt(main.height() - 30) + 'px;';
			iframe.attr('style', fullscreenAreaIframe);
			area.attr('style', fullscreenAreaIframe);
			fullscreenAreaIframe = '';
			//--
			if(typeof(smartJ$Browser) != 'undefined') {
				smartJ$Browser.OverlayShow();
				smartJ$Browser.OverlayClear();
			} //end if
			//--
		} //end if else
		//-- bugfix
		var toolbar = data.editor.$toolbar;
		var group = toolbar.children("div:last");
		var wid = main.width();
		var hgt = group.offset().top + group.outerHeight() - toolbar.offset().top + 1;
		toolbar.height(hgt);
		//-- #
		area.show(); // force refresh buttons
		area.hide();
		iframe.show();
		//--
		data.editor.focus();
		//--
		return false;
		//--
	} //END FUNCTION

})(jQuery);
