/*
 * Toastr v.3.0.8
 * (c) 2021 unix-world.org
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

// (c) 2021 unix-world.org
// fixes by unixman r.20210330:
// 	* jQuery 3.5.0 ready (fixed XHTML Tags)
// 	* Added options: onBeforeVisible, onVisible, onBeforeHidden + fix detect onHidden and these if type function
// 	* Added renders: darknote, notice, light, colored
// 	* Added css translator for css classes from other growl components: translateCssClasses ; by now only supports gritter classes

/* global define */
; (function (define) {
	define(['jquery'], function ($) {
		return (function () {
			var $container;
			var listener;
			var toastId = 0;
			var toastType = {
				darknote: 'darknote', // black (dark)
				notice: 'notice', // white
				light: 'light', // pale yellow
				info: 'info', // blue
				success: 'success', // green
				warning: 'warning', // yellow
				error: 'error', // red
				colored: 'colored', // pink
			};

			var toastr = {
				options: {},
				darknote: darknote,
				notice: notice,
				light: light,
				info: info,
				success: success,
				warning: warning,
				error: error,
				colored: colored,
				clear: clear,
				remove: remove,
				subscribe: subscribe,
				getContainer: getContainer,
				translateCssClasses: translateCssClasses,
				version: '3.0.8',
			};

			var previousToast;

			return toastr;

			////////////////

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

			function translateCssClasses(mode, class_name) {
				mode = String(mode || ''); // {{{SYNC-JS-GROWL-TRANSLATE-CLASSES}}}
				class_name = String(class_name || '');
				if(mode === 'gritter') {
					switch(class_name) {
						case 'gritter-dark':
						case 'dark':
							class_name = 'darknote';
							break;
						case 'gritter-neutral':
						case 'neutral':
						case 'white':
							class_name = 'notice';
							break;
						case 'gritter-light':
						case 'light':
							class_name = 'light';
							break;
						case 'gritter-blue':
						case 'blue':
							class_name = 'info';
							break;
						case 'gritter-green':
						case 'green':
							class_name = 'success';
							break;
						case 'gritter-yellow':
						case 'yellow':
							class_name = 'warning';
							break;
						case 'gritter-red':
						case 'red':
							class_name = 'error';
							break;
						case 'gritter-pink':
						case 'pink':
							class_name = 'colored';
							break;
						default:
							// leave as is, will be fixed below
					}
				} else if(mode === 'toastr') {
					// leave as they are, will be validated below
				} else {
					class_name = ''; // invalid mode
				}
				switch(class_name) {
					case 'darknote':
					case 'notice':
					case 'light':
					case 'info':
					case 'success':
					case 'warning':
					case 'error':
					case 'colored':
						// OK
						break;
					default:
						class_name = 'darknote'; // invalid, map to toastr default class
				} //end switch
				return String(class_name);
			}

			//--

			function darknote(message, title, optionsOverride) {
				return notify({
					type: toastType.darknote,
					iconClass: getOptions().iconClasses.darknote,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function notice(message, title, optionsOverride) {
				return notify({
					type: toastType.notice,
					iconClass: getOptions().iconClasses.notice,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function light(message, title, optionsOverride) {
				return notify({
					type: toastType.light,
					iconClass: getOptions().iconClasses.light,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function info(message, title, optionsOverride) {
				return notify({
					type: toastType.info,
					iconClass: getOptions().iconClasses.info,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function success(message, title, optionsOverride) {
				return notify({
					type: toastType.success,
					iconClass: getOptions().iconClasses.success,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function warning(message, title, optionsOverride) {
				return notify({
					type: toastType.warning,
					iconClass: getOptions().iconClasses.warning,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function error(message, title, optionsOverride) {
				return notify({
					type: toastType.error,
					iconClass: getOptions().iconClasses.error,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
			}

			function colored(message, title, optionsOverride) {
				return notify({
					type: toastType.colored,
					iconClass: getOptions().iconClasses.colored,
					message: message,
					optionsOverride: optionsOverride,
					title: title
				});
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
					debug: false,

					tapToDismiss: false,
					toastClass: 'toast',
					containerId: 'toast-container',

					showMethod: 'fadeIn', //fadeIn, slideDown, and show are built into jQuery
					showDuration: 300,
					showEasing: 'swing', //swing and linear are built into jQuery
					onShown: undefined,
					hideMethod: 'fadeOut',
					hideDuration: 1000,
					hideEasing: 'swing',

					onBeforeVisible: undefined, // or function
					onVisible: undefined, // or function
					onBeforeHidden: undefined, // or function
					onHidden: undefined, // or function

					extendedTimeOut: 1000,
					iconClasses: {
						darknote: 'toast-darknote',
						notice: 'toast-notice',
						light: 'toast-light',
						info: 'toast-info',
						success: 'toast-success',
						warning: 'toast-warning',
						error: 'toast-error',
						colored: 'toast-colored',
					},
					iconClass: '',
					positionClass: 'toast-top-right',
					timeOut: 6000, // Set timeOut and extendedTimeOut to 0 to make it sticky
					titleClass: 'toast-title',
					messageClass: 'toast-message',
					target: 'body',
					closeButton: true,
					closeHtml: '<button type="button">&times;</button>',
					newestOnTop: true,
					preventDuplicates: false,
					progressBar: false
				};
			}

			function publish(args) {
				if (!listener) { return; }
				listener(args);
			}

			function notify(map) {
				var options = getOptions();
				var iconClass = map.iconClass || options.iconClass;

				if (typeof (map.optionsOverride) !== 'undefined') {
					options = $.extend(options, map.optionsOverride);
					iconClass = map.optionsOverride.iconClass || iconClass;
				}

				if (shouldExit(options, map)) { return; }

				toastId++;

				$container = getContainer(options, true);

				var intervalId = null;
				var $toastElement = $('<div></div>');
				var $titleElement = $('<div></div>');
				var $messageElement = $('<div></div>');
				var $progressElement = $('<div></div>');
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
					options: options,
					map: map
				};

				personalizeToast();

				displayToast();

				handleEvents();

				publish(response);

				if (options.debug && console) {
					console.log(response);
				}

				return $toastElement;

				function personalizeToast() {
					setIcon();
					setTitle();
					setMessage();
					setCloseButton();
					setProgressBar();
					setSequence();
				}

				function handleEvents() {
					$toastElement.hover(stickAround, delayedHideToast);
					if (!options.onclick && options.tapToDismiss) {
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

					if (options.onclick && (typeof options.onclick == 'function')) {
						$toastElement.click(function () {
							options.onclick();
							hideToast();
						});
					}
				}

				function displayToast() {
					$toastElement.hide();

					if(options.onBeforeVisible && (typeof options.onBeforeVisible == 'function')) {
						options.onBeforeVisible();
					}

					$toastElement[options.showMethod](
						{duration: options.showDuration, easing: options.showEasing, complete: options.onShown}
					);

					if(options.onVisible && (typeof options.onVisible == 'function')) {
						options.onVisible();
					}

					if (options.timeOut > 0) {
						intervalId = setTimeout(hideToast, options.timeOut);
						progressBar.maxHideTime = parseFloat(options.timeOut);
						progressBar.hideEta = new Date().getTime() + progressBar.maxHideTime;
						if (options.progressBar) {
							progressBar.intervalId = setInterval(updateProgress, 10);
						}
					}
				}

				function setIcon() {
					if (map.iconClass) {
						$toastElement.addClass(options.toastClass).addClass(iconClass);
					}
				}

				function setSequence() {
					if (options.newestOnTop) {
						$container.prepend($toastElement);
					} else {
						$container.append($toastElement);
					}
				}

				function setTitle() {
					if (map.title) {
						$titleElement.append(map.title).addClass(options.titleClass);
						$toastElement.append($titleElement);
					}
				}

				function setMessage() {
					if (map.message) {
						$messageElement.append(map.message).addClass(options.messageClass);
						$toastElement.append($messageElement);
					}
				}

				function setCloseButton() {
					if (options.closeButton) {
						$closeElement.addClass('toast-close-button').attr('role', 'button');
						$toastElement.prepend($closeElement);
					}
				}

				function setProgressBar() {
					if (options.progressBar) {
						$progressElement.addClass('toast-progress');
						$toastElement.prepend($progressElement);
					}
				}

				function shouldExit(options, map) {
					if (options.preventDuplicates) {
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
					if (options.timeOut > 0 || options.extendedTimeOut > 0) {
						intervalId = setTimeout(hideToast, options.extendedTimeOut);
						progressBar.maxHideTime = parseFloat(options.extendedTimeOut);
						progressBar.hideEta = new Date().getTime() + progressBar.maxHideTime;
					}
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
	if (typeof module !== 'undefined' && module.exports) { //Node
		module.exports = factory(require('jquery'));
	} else {
		jQuery.toastr = factory(window['jQuery']);
	}
}));

// #END
