
// Icons Captcha: v1.0
// (c) 2021-2023 unix-world.org
// License: BSD
// r.20231117
// jquery.iconsCaptcha.js

jQuery.fn.iconsCaptcha || (($) => {

	// it uses an iconic font that will load from external URL as: css-class2\ncss-class2 #
	// scope: # select the icon that is different than the rest of icons ; will be 5 identic and 1 different #

	$.fn.extend({

		iconsCaptcha: function(options) {

			const _Y$ = this;

			const _p$ = console;

			const defaults = { // default options
				clickDelay: 2500, // if < 0 needs trigger !
				iconsURL:   'css/sf-icons.txt',
				loaderImg:  'img/loading-spokes.svg',
				loaderEImg: 'img/sign-crit-warn.svg',
				hintText:   'Select the icon that does not belong in the series ...',
				doneText: 	'An icon has already been selected !',
				fxHandler:  (attainment, expr, done, obj) => { // completion: const test = Math.round(Math.exp(obj['.'])); if((1 <= test) && (test < 2)) { return attainment; }
					_p$.log('iCaptcha Selection Done', obj, attainment, done);
				},
			};

			const $opts =  $.extend(defaults, options);

		//	const escapeHtml = (text) => { // jQuery native html escaping ... it is not quite safe, some characters will not escape
		//		return String($('<div></div>').text(text).html() || '');
		//	};
			const escapeHtml = (text) => { // this it performs better, particularly on large blocks of text # https://stackoverflow.com/questions/1787322/htmlspecialchars-equivalent-in-javascript
				const map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;'
				};
				return String(text.replace(/[&\<\>"]/g, (m) => map[m]));
			};

			const tags = [
				'<div class="iCaptcha-box" title="' + escapeHtml($opts.hintText) + '">',
					'<div class="iCaptcha-line">',
						'<div class="iCaptcha-icon"></div>',
						'<div class="iCaptcha-icon"></div>',
						'<div class="iCaptcha-icon"></div>',
						'<div class="iCaptcha-icon"></div>',
						'<div class="iCaptcha-icon"></div>',
						'<div class="iCaptcha-icon"></div>',
					'</div>',
				'</div>'
			];

			const getArrRandVal = (arr) => {
				return arr[Math.floor(Math.random() * arr.length)];
			};

			let captchaInitialized = false;
			let captchaRunTime = null;

			const initCaptcha = (arr, $id, $container) => {

				$container.empty();

				if(typeof(arr) == 'string') {
					$container.html('<div class="iCaptcha-error"><img src="' + escapeHtml($opts.loaderEImg) + '" alt="ERR" title="' + escapeHtml(String(arr)) + '"></div>');
					_p$.warn('iCaptcha:', 'initCaptcha:', String(arr));
					return;
				}
				if((!Array.isArray(arr)) || (arr.length <= 0)) {
					_p$.error('iCaptcha:', 'initCaptcha:', 'Invalid Array');
					return;
				}
				$container.html(tags.join(''));

				let i1 = getArrRandVal(arr);
				let i2 = null;
				for(let i=0; i<arr.length; i++) {
					i2 = getArrRandVal(arr);
					if(i2 === i1) {
						i2 = null;
					} else {
						break;
					}
				}
				if(!i2) {
					_p$.error('iCaptcha Random UID Selection Failed:', $id);
					return;
				}

				const numIcns = 6;
				const i3 = Math.floor(Math.random() * numIcns);
				if((i3 < 0) || (i3 > (numIcns - 1))) {
					_p$.error('iCaptcha Invalid Icon Index:', i3, $id);
					return;
				}

				$container.find('div.iCaptcha-icon').each((ix, elm) => {
					let icon = null;
					if(ix === i3) {
						icon = i2;
					} else {
						icon = i1;
					}
					if(icon) {
						let uid = String((ix+1)*8).padStart(4, '0');
						$(elm).attr('data-icaptcha-idx', ix).data('icaptcha-icn', icon).addClass(String(icon) + ' sfi-2x iCaptcha-Uid-_u' + uid);
					}
				});

			};

			const selectionDone = ($id, $container, $selected) => {

				$container.addClass('iCaptcha-overlay');
				$container.find('div.iCaptcha-box').eq(0).attr('title', $opts.doneText);
				$container.find('div.iCaptcha-icon').each((index, element) => {
					$(element).addClass('iCaptcha-disabled');
				});

				$selected.addClass('iCaptcha-selected');

				const obj = {
					id: $id,
					'!': 0,
					'&': 2,
					'#': 0,
					'%': 3,
					'.': 0,
					'@': 4,
				};
				$container.find('div.iCaptcha-icon').filter((Ix, El) => {
					($selected.data('icaptcha-icn') === $(El).data('icaptcha-icn')) ? obj['.']++ : obj['#']++;
				});
				obj['!'] = (obj['!'] + Math.random()) / 10;
				obj['&'] = (obj['&'] + Math.random()) / 10;
				obj['%'] = (obj['%'] + Math.random()) / 10;
				obj['#'] = (obj['#'] + Math.random()) / 10;
				obj['.'] = (obj['.'] + Math.random()) / 10;
				obj['@'] = (obj['@'] + Math.random()) / 10;

				const expr = Math.round(Math.exp(obj['.']));
				const done = !! ((1 <= expr) && (expr < 2));
				const attainment = (expr * ((Math.PI / 10) * 3)) + ((Math.random() / 10) * 0.7); // just conform with motion captcha ; adjust after with: attainment - ((Math.random() / 10) * 0.3)

				if(typeof($opts.fxHandler) == 'function') {
					$opts.fxHandler(attainment, expr, done, obj);
				}

			};

			let $fX = _Y$.each((idx, el) => {
				$(el).empty().html('<div class="iCaptcha-ldr"><center><img src="' + escapeHtml($opts.loaderImg) + '"></center></div>')
			});

			captchaRunTime = () => {

				captchaInitialized = true;

				if(!$opts.iconsURL) {
					_p$.error('iCaptcha Empty Icons URL');
					return;
				}

				$.get($opts.iconsURL, null, null, 'text')
					.done((txt) => {

						txt = String(txt || '').trim();
						if(txt.substr(0, 8) !== 'sfi sfi-') {
							txt = null; // invalid
						}
						const arr = (txt ? txt.split('\n') : []);
						txt = null; // free mem

						$fX = _Y$.each((idx, el) => {

							const $container = $(el);
							const $id = $container.attr('id');

							if(!$id) {
								_p$.warn('iCaptcha have no ID:', idx);
								return;
							}

							if(arr.length <= 0) {
								initCaptcha('iCaptcha: FAILED to process the icons list', $id, $container);
								return;
							}

							const iTime = new Date();
							let iconSelected = false;
							let mOver = false;

							$container.on('click', '.iCaptcha-line > .iCaptcha-icon', (evt) => { // don't use here: mousedown touchstart ; will trigger later: mouseenter

								if(iconSelected) {
									return; // already selected, stop here
								}

								if((new Date() - iTime) <= 225) { // wait at least 225ms
									return; // click too fast, stop here
								}

								if(!mOver) {
									return; // if the cursor (mouse) is not over the element, stop here
								}

								const $selected = $(evt.currentTarget); // must be with original event
								$selected.trigger('mouseenter'); // req. by touch devices

								let pointEv = evt;
								if(evt.touches && evt.touches.length > 0) {
									pointEv = evt.touches[0];
								}
								let _x = Math.round(pointEv.pageX || -1);
								let _y = Math.round(pointEv.pageY || -1);
								if(_x < 0) { _x = 0; } // dissalow negatives to avoid change the sense
								if(_y < 0) { _y = 0; } // dissalow negatives to avoid change the sense
								_x = Math.round(_x - Math.round($selected.offset().left));
								_y = Math.round(_y - Math.round($selected.offset().top));
								if(!_x || !_y) { // detect click coordinates ; if not pass here it is not a real click event, it is emulated, stop here
									return;
								}

								iconSelected = true;
								selectionDone($id, $container, $selected);

							}).on('mouseenter touchenter touchstart', () => {
								if(!mOver) {
									mOver = true;
								}
							}).on('mouseleave touchleave', () => {
								if(mOver) {
									mOver = false;
								}
							});

							initCaptcha(arr, $id, $container);

						});

					}).fail((data, status) => {

						$fX = _Y$.each((idx, el) => {

							const $container = $(el);
							const $id = $container.attr('id');

							if(!$id) {
								_p$.warn('iCaptcha have no ID:', idx);
								return;
							}

							initCaptcha('iCaptcha Failed to get the Icons List: ' + String(status), $id, $container);

						});

					},

				);

			};

			if($opts.clickDelay < 0) {
				$(window).on('captcha.iconsCaptcha', () => { // needs trigger
					captchaRunTime();
				});
			} else {
				setTimeout(() => {
					captchaRunTime();
				}, $opts.clickDelay);
			}

			return $fX;

		}

	});

})(jQuery);

// #END
