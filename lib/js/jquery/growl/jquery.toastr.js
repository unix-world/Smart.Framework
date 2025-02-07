/*
 * Toastr v.3.1.7.3
 * (c) 2021-2023 unix-world.org
 *
 * Based on Toastr v.2.1.1
 * Copyright 2012-2015
 * Authors: John Papa, Hans Fjällemark, and Tim Ferrell.
 * All Rights Reserved.
 * Use, reproduction, distribution, and modification of this code is subject to the terms and
 * conditions of the MIT license
 * ARIA Support: Greta Krafsig
 * Project: https://github.com/CodeSeven/toastr
 */

// (c) 2021-present unix-world.org
// fixes by unixman r.20250205:
// 	* jQuery 3.5.0 ready (fixed XHTML Tags)
// 	* Added options: onBeforeVisible, onVisible, onBeforeHidden + fix detect onHidden and these if type function
// 	* Added extra styles to be compliant with gritter and std css classes for growl
// 	* Added css translator for css classes from other growl components: translateCssClasses ; by now only supports gritter classes
// 	* core optimizations and refactoring

/* global define */
;(function (define) { define(['jquery'], function($) {

	return (function() {

		var $container;
		var listener;
		var toastId = 0;
		var toastr = {
			options: {},
			notify: notify,
			clear: clear,
			remove: remove,
			subscribe: subscribe,
			getContainer: getContainer,
			translateCssClasses: translateCssClasses,
			version: '3.1.7',
		};
		var previousToast;

		return toastr;

		//--

		function getContainer(options, create) {
			if (!options) { options = getOptions(); }
			$container = $('#' + options.containerId);
			if ($container.length) {
				return $container;
			}
			if (create) {
				$container = createContainer(options);
			}
			return $container;
		}

		function subscribe(callback) {
			listener = callback;
		}

		//--

		function translateCssClasses(class_name) { // {{{SYNC-JS-GROWL-TRANSLATE-CLASSES}}}
			class_name = String(class_name || '');
			class_name = class_name.replace('gritter-', '');
			class_name = class_name.replace('toast-', '');
			switch(class_name) {
				case 'summer': // std
				case 'light': // gritter, std
					class_name = 'toast-light';
					break;
				case 'neutral': // gritter
				case 'white': // std
				case 'info': // std
					class_name = 'toast-info';
					break;
				case 'dark': // gritter
				case 'black': // std
				case 'hint': // std
					class_name = 'toast-darknote';
					break;
				case 'blue': // gritter
				case 'notice': // std
					class_name = 'toast-notice';
					break;
				case 'green': // gritter
				case 'success': // std
					class_name = 'toast-success';
					break;
				case 'yellow': // gritter
				case 'warning': // std
					class_name = 'toast-warning';
					break;
				case 'pink': // gritter
				case 'colored': // std
				case 'error': // std
					class_name = 'toast-colored';
					break;
				case 'red': // gritter
				case 'fail': // std
				case 'fatal': // std
					class_name = 'toast-error';
					break;
				case '': // toastr default
					class_name = 'toast';
					break;
				default: // invalid, map to fatal
					class_name = 'toast-error';
			}
			return String(class_name);
		}

		//--

		function clear($toastElement, clearOptions) {
			var options = getOptions();
			if (!$container) { getContainer(options); }
			if (!clearToast($toastElement, options, clearOptions)) {
				clearContainer(options);
			}
		}

		function remove($toastElement) {
			var options = getOptions();
			if (!$container) { getContainer(options); }
			if ($toastElement && $(':focus', $toastElement).length === 0) {
				removeToast($toastElement);
				return;
			}
			if ($container.children().length) {
				$container.remove();
			}
		}

		// internal functions

		function clearContainer (options) {
			var toastsToClear = $container.children();
			for (var i = toastsToClear.length - 1; i >= 0; i--) {
				clearToast($(toastsToClear[i]), options);
			}
		}

		function clearToast ($toastElement, options, clearOptions) {
			var force = clearOptions && clearOptions.force ? clearOptions.force : false;
			if ($toastElement && (force || $(':focus', $toastElement).length === 0)) {
				$toastElement[options.hideMethod]({
					duration: options.hideDuration,
					easing: options.hideEasing,
					complete: function () { removeToast($toastElement); }
				});
				return true;
			}
			return false;
		}

		function createContainer(options) {
			$container = $('<div></div>')
				.attr('id', options.containerId)
				.addClass(options.positionClass)
				.attr('aria-live', 'polite')
				.attr('role', 'alert');
			$container.appendTo($(options.target));
			return $container;
		}

		function getDefaults() {
			return {
				//-- public options

				title: '',
				message: '',

				timeOut: 6000, // Set timeOut to 0 to make it sticky

				appearanceClass: '', // to be set external

				closeButton: true,
				closeHtml: '<button type="button">&times;</button>',

				progressBar: true,

				onBeforeVisible: undefined, // or function
				onVisible: undefined, // or function
				onBeforeHidden: undefined, // or function
				onHidden: undefined, // or function

				newestOnTop: true,
				preventDuplicates: false,
				tapToDismiss: false,

				//-- private options

				containerId: 'toast-container',

				toastClass: 'toast',
				positionClass: 'toast-top-right',
				titleClass: 'toast-title',
				messageClass: 'toast-message',

				showMethod: 'fadeIn', // fadeIn, slideDown, and show are built into jQuery
				showDuration: 300,
				showEasing: 'swing', // swing and linear are built into jQuery
				hideMethod: 'fadeOut',
				hideDuration: 1000,
				hideEasing: 'swing',

				target: 'body',

				debug: false
			};
		}

		function publish(args) {
			if (!listener) { return; }
			listener(args);
		}

		function notify(map) {
			var options = getOptions();
			options = $.extend(options, map);

			if (shouldExit(options)) {
				return;
			}

			toastId++;

			$container = getContainer(options, true);

			var theEmptyDiv = '<div></div>';
			var intervalId = null;
			var $toastElement = $(theEmptyDiv);
			var $titleElement = $(theEmptyDiv);
			var $messageElement = $(theEmptyDiv);
			var $progressElement = $(theEmptyDiv);
			var $closeElement = $(options.closeHtml);
			var progressBar = {
				intervalId: null,
				hideEta: null,
				maxHideTime: null
			};
			var response = {
				toastId: toastId,
				state: 'visible',
				startTime: new Date(),
				options: options
			};

			setAppearance(options);
			setTitle(options);
			setMessage(options);
			setCloseButton(options);
			setProgressBar(options);
			setSequence(options);

			displayToast();

			handleEvents();

			publish(response);

			if (options.debug && console) {
				console.log(response);
			}

			return $toastElement;

			function handleEvents() {
				$toastElement.hover(stickAround, delayedHideToast);
				if (options.tapToDismiss) {
					$toastElement.click(hideToast);
				}
				if (options.closeButton && $closeElement) {
					$closeElement.click(function (event) {
						if (event.stopPropagation) {
							event.stopPropagation();
						} else if (event.cancelBubble !== undefined && event.cancelBubble !== true) {
							event.cancelBubble = true;
						}
						hideToast(true);
					});
				}
			}

			function displayProgressBar() {
				if (options.timeOut > 0) {
					intervalId = setTimeout(hideToast, options.timeOut);
					progressBar.maxHideTime = parseFloat(options.timeOut);
					progressBar.hideEta = new Date().getTime() + progressBar.maxHideTime;
					if (options.progressBar) {
						progressBar.intervalId = setInterval(updateProgress, 10);
					}
				}
			}

			function displayToast() {
				$toastElement.hide();
				if(options.onBeforeVisible && (typeof options.onBeforeVisible == 'function')) {
					options.onBeforeVisible();
				}
				$toastElement[options.showMethod](
					{duration: options.showDuration, easing: options.showEasing, complete: options.onVisible}
				);
				displayProgressBar();
			}

			function setAppearance(map) {
				if(map.appearanceClass != '') {
					$toastElement.addClass(map.appearanceClass);
				} else {
					$toastElement.addClass(map.toastClass);
				}
			}

			function setSequence(map) {
				if(map.newestOnTop === true) {
					$container.prepend($toastElement);
				} else {
					$container.append($toastElement);
				}
			}

			function setTitle(map) {
				if(map.title != '') {
					$titleElement.append(map.title).addClass(map.titleClass);
					$toastElement.append($titleElement);
				}
			}

			function setMessage(map) {
				if(map.message != '') {
					$messageElement.append(map.message).addClass(map.messageClass);
					$toastElement.append($messageElement);
				}
			}

			function setCloseButton(map) {
				if(map.closeButton === true) {
					$closeElement.addClass('toast-close-button').attr('role', 'button');
					$toastElement.prepend($closeElement);
				}
			}

			function setProgressBar(map) {
				if(map.progressBar === true) {
					$progressElement.addClass('toast-progress');
					$toastElement.prepend($progressElement);
				}
			}

			function shouldExit(map) {
				if(map.preventDuplicates) {
					if (map.message === previousToast) {
						return true;
					} else {
						previousToast = map.message;
					}
				}
				return false;
			}

			function hideToast(override) {
				if ($(':focus', $toastElement).length && !override) {
					return;
				}
				clearTimeout(progressBar.intervalId);
				return $toastElement[options.hideMethod]({
					duration: options.hideDuration,
					easing: options.hideEasing,
					complete: function () {
						if(options.onBeforeHidden && (typeof options.onBeforeHidden == 'function') && response.state !== 'hidden') {
							options.onBeforeHidden();
						}
						removeToast($toastElement);
						response.state = 'hidden';
						response.endTime = new Date();
						if(options.onHidden && (typeof options.onHidden == 'function') && response.state === 'hidden') {
							options.onHidden();
						}
						publish(response);
					}
				});
			}

			function delayedHideToast() {
				displayProgressBar();
			}

			function stickAround() {
				clearTimeout(intervalId);
				progressBar.hideEta = 0;
				$toastElement.stop(true, true)[options.showMethod](
					{duration: options.showDuration, easing: options.showEasing}
				);
			}

			function updateProgress() {
				var percentage = ((progressBar.hideEta - (new Date().getTime())) / progressBar.maxHideTime) * 100;
				$progressElement.width(percentage + '%');
			}
		}

		function getOptions() {
			return $.extend({}, getDefaults(), toastr.options);
		}

		function removeToast($toastElement) {
			if (!$container) { $container = getContainer(); }
			if ($toastElement.is(':visible')) {
				return;
			}
			$toastElement.remove();
			$toastElement = null;
			if ($container.children().length === 0) {
				$container.remove();
				previousToast = undefined;
			}
		}

	})();

});
}(typeof define === 'function' && define.amd ? define : function (deps, factory) {
//	if(typeof module !== 'undefined' && module.exports) { //Node
//		module.exports = factory(require('jquery'));
//	} else {
		jQuery.toastr = factory(window['jQuery']);
//	}
}));

// #END
