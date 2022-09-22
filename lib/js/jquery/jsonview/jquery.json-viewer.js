/**
 * jQuery json-viewer @ r.2016.08.22 # https://github.com/abodelot/jquery.json-viewer
 * @author: Alexandre Bodelot <alexandre.bodelot@gmail.com>
 * contains fixes by unixman: r.20220920
 * 		- add extra option: options.urlLink
 */

(function($){

	/**
	 * Check if arg is either an array with at least 1 element, or a dict with at least 1 key
	 * @return boolean
	 */
	function isCollapsable(arg) {
		return arg instanceof Object && Object.keys(arg).length > 0;
	}

	/**
	 * Check if a string represents a valid url
	 * @return boolean
	 */
	function isUrl(string) {
		 var regexp = /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
		 return regexp.test(string);
	}

	/**
	 * Escapes HTML (added by unixman)
	 * @return string
	 */
	 function escapeHtml(text) {
		text = text || '';
		text = String(text);
		text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		return String(text);
	}

	/**
	 * Transform a json object into html representation
	 * @return string
	 */
	function json2html(json, options) {

		var html = '';
		if(typeof json === 'string') {
			/* Escape tags */
		//	json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // fix by unixman
			json = escapeHtml(json); // fix by unixman
			if(options.urlLink && (isUrl(json))) {
				html += '<a href="' + json + '" class="json-string">' + json + '</a>';
			} else {
				html += '<span class="json-string">&quot;' + json + '&quot;</span>';
			}
		} else if(typeof json === 'number') {
			html += '<span class="json-numeric">' + json + '</span>';
		} else if(typeof json === 'boolean') {
			html += '<span class="json-boolean">' + json + '</span>';
		} else if(json === null) {
			html += '<span class="json-null">null</span>';
		} else if(json instanceof Array) {
			if(json.length > 0) {
				html += '[<ol class="json-array">';
				for(var i=0; i<json.length; ++i) {
					html += '<li>';
					/* Add toggle button if item is collapsable */
					if(isCollapsable(json[i])) {
						html += '<a href class="json-toggle"></a>';
					}
					html += json2html(json[i], options);
					/* Add comma if item is not last */
					if(i < json.length - 1) {
						html += ',';
					}
					html += '</li>';
				}
				html += '</ol>]';
			} else {
				html += '[]';
			}
		} else if(typeof json === 'object') {
			var key_count = Object.keys(json).length;
			if(key_count > 0) {
				html += '{<ul class="json-dict">';
				for(var key in json) {
					if(json.hasOwnProperty(key)) {
						html += '<li>';
						var keyRepr = options.withQuotes ? '<span class="json-key">&quot;' + escapeHtml(key.replace(/"/g, '\\"')) + '&quot;</span>' : '<span class="json-key">' + escapeHtml(key) + '</span>';
						/* Add toggle button if item is collapsable */
						if(isCollapsable(json[key])) {
							html += '<a href class="json-toggle">' + keyRepr + '</a>';
						} else {
							html += keyRepr;
						}
						html += ': ' + json2html(json[key], options);
						/* Add comma if item is not last */
						if(--key_count > 0) {
							html += ',';
						}
						html += '</li>';
					}
				}
				html += '</ul>}';
			} else {
				html += '{}';
			}
		}
		return html;
	}

	/**
	 * jQuery plugin method
	 * @param json: a javascript object
	 * @param options: an optional options hash ; options = { collapsed: false/true/1/-1, withQuotes: false/true }
	 */
	$.fn.jsonViewer = function(json, options) {
		options = options || {};

		/* jQuery chaining */
		return this.each(function() {

			/* Transform to HTML */
			var html = json2html(json, options);
			if(isCollapsable(json)) {
				html = '<a href class="json-toggle"></a>' + html;
			}

			/* Insert HTML in target DOM element */
			$(this).html(html);

			/* Bind click on toggle buttons */
			$(this).off('click');
			$(this).on('click', 'a.json-toggle', function() {
				var target = $(this).toggleClass('collapsed').siblings('ul.json-dict, ol.json-array');
				target.toggle();
				if(target.is(':visible')) {
					target.siblings('.json-placeholder').remove();
				} else {
					var count = target.children('li').length;
					var placeholder = count + (count > 1 ? ' fields' : ' field');
					target.after('<a href class="json-placeholder">' + placeholder + '</a>');
				}
				return false;
			});

			/* Simulate click on toggle button when placeholder is clicked */
			$(this).on('click', 'a.json-placeholder', function() {
				$(this).siblings('a.json-toggle').click();
				return false;
			});

			/* Trigger click to collapse all nodes (by default will not collapse) */
			if(options.collapsed === true) { // collapse all levels
				$(this).find('a.json-toggle').click();
			} else if(options.collapsed === -1) { // collapse all levels except first level
				$(this).find('a.json-toggle').not(':first').click();
			} else if(options.collapsed === 1) { // collapse only first level
				$(this).find('a.json-toggle').first().click();
			}
		});
	};

})(jQuery);

// #END
