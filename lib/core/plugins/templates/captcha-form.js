
// Captcha Form Handler
// (c) 2021-present unix-world.org
// License: BSD
// r.20250203

jQuery(() => {

	if(!window) {
		return;
	}

	if((typeof(window.smartCaptchaFormHandler) == 'undefined')) {

		const smartCaptchaFormHandler = new class{constructor(){ // STATIC CLASS, ES6
			const _N$ = 'smartCaptchaFormHandler';

			// :: static
			const _C$ = this; // self referencing

			const _p$ = console;

			let SECURED = false;
			_C$.secureClass = () => { // implements class security
				if(SECURED === true) {
					_p$.warn(_N$, 'Class is already SECURED');
				} else {
					SECURED = true;
					Object.freeze(_C$);
				} //end if
			}; //END

			const $ = jQuery; // jQuery referencing

			const errx0 = 'Invalid UUID';
			const errx1 = 'Captcha Options are Undefined !';
			const errx2 = 'Captcha Error';
			const errx3 = 'Captcha DhKx ERR';
			const errx4 = 'Empty Captcha Form Name';
			const errx5 = 'Invalid Captcha Checksum';
			const errx6 = 'Wrong Captcha Checksum';
			const errx7 = 'Motion Captcha is N/A';

			const err0 = 'Captcha XHR Failed !';
			const err1 = 'Captcha method is missing';
			const err2 = 'Captcha helper is missing';
			const err3 = 'Captcha function is missing';
			const err4 = 'Invalid Captcha SVG ID';
			const err5 = 'Invalid Captcha SVG D Path';
			const err6 = 'Captcha XML SVG ERR';
			const err7 = 'Captcha Tick Invalid Target !';

			const errCanvas = 'Your browser does not support all the HTML5 features required to pass the Captcha. Try with a modern browser !';

			const handleForm = function($form, opt$, u$, d$, z$, h$, c$e, c$d, b$, s$, dhkx$KE, get$DhKx, data$Handler, mutation$Observer, event$Observer, pointer$Observer, chkSum) { // ES6
				const _m$ = 'handleForm';

				if(!window) {
					return;
				}

				const uuid = u$.create_htmid(u$.stringPureVal(opt$.uuid, true));
				if((uuid == '') || (uuid.length < 10)) {
					_p$.error(_N$, _m$, errx0);
					b$.AlertDialog('<h4>' + u$.escape_html(errx0) + '</h4>', null, 'Captcha');
					return;
				}

				const fxErr = (err) => {
					err = u$.stringPureVal(err, true);
					if(err == '') {
						err = 'Unknown';
					}
					err = 'ERR: ' + err;
					_p$.error(_N$, _m$, err);
					$('#Smart-Captcha-Area-' + uuid).empty().html('<div class="Smart-Captcha-Err">' + u$.escape_html(err) + '</div>');
				};

				if(typeof(opt$) !== 'object') {
					fxErr(errx1);
					return;
				}
				Object.freeze(opt$);

				if(typeof(window.x$exch) !== 'undefined') {
					fxErr(errx2 + ': ' + opt$.msgSec);
					return;
				}
				const x$exch = dhkx$KE(s$, opt$);
				if(!x$exch.hasOwnProperty('err')) {
					fxErr(errx3 + ': Undef');
					return;
				} else if(!!x$exch.err) {
					fxErr(errx3 + ': Init');
					return;
				} else if(x$exch.typ !== 'smart') {
					fxErr(errx3 + ': Type');
					return;
				}
				Object.freeze(x$exch);
				window.x$exch = x$exch; // export

				$form = u$.stringPureVal($form || '');
				if($form == '') {
					fxErr(errx4);
					return;
				}

				chkSum = u$.stringPureVal(chkSum, true);
				if((chkSum == '') || (chkSum.length < 62) || (chkSum.length > 66)) {
					fxErr(errx5);
					return;
				}
				const calcCkSum = h$.sh3a384('#' + String($form) + '#' + String(uuid) + '#' + String(opt$.imUrl) + '#' + String(opt$.iqURL) + '#', true);
				if((chkSum !== z$.b64_to_b64s(calcCkSum)) || (z$.b64s_to_b64(chkSum) !== calcCkSum)) {
					fxErr(errx6);
					return;
				}

				if(typeof($.fn.motionCaptcha) === 'undefined') {
					fxErr(errx7);
					return;
				}

				let deltaPointerX = 0, deltaPointerY = 0;
				const covariancePointer = (mouseX, mouseY) => {
					mouseX = u$.format_number_int(mouseX);
					mouseY = u$.format_number_int(mouseY);
					deltaPointerX = mouseX - deltaPointerX;
					deltaPointerY = mouseY - deltaPointerY;
					return u$.format_number_float(Math.atan(Math.PI / 2.651) + (Math.random() / 10) + u$.format_number_int(Math.atanh(deltaPointerX)) + u$.format_number_int(Math.atanh(deltaPointerY)));
				};

				let minPointerPos = { x: u$.format_number_int($(window).width()), y: u$.format_number_int($(window).height()), t: '@mixed:min' };
				$(window).resize(() => { // adjust on window resize
					minPointerPos.x = Math.min(minPointerPos.x, u$.format_number_int($(window).width()));
					minPointerPos.y = Math.min(minPointerPos.y, u$.format_number_int($(window).height()));
				});
				let maxPointerPos = { x: -1, y: -1, t: '@mixed:max' };
				let crrPointerPos = { x: -1, y: -1, t: null };
				let arrPointerPos = [];
				const limPointerPos = 50;
				const maxMetaPointerEvents = Math.ceil(limPointerPos / 2);
				let crrMetaPointerEvents = 0;
				$(document).on('mousemove touchmove touchend scroll input', (evt) => { // input is also considered, there are form fields
					let evData = b$.getCurrentPointerEventXY(evt, true); // viewport only
					if((evData.t === 'scroll') || (evData.t === 'input')) { // meta-events
						crrMetaPointerEvents++;
						if(crrMetaPointerEvents >= maxMetaPointerEvents) {
							return; // avoid having only scroll events recorder, max recorder is limPointerPos/2
						}
					}
					crrPointerPos.x = evData.x;
					crrPointerPos.y = evData.y;
					crrPointerPos.t = evData.t;
					arrPointerPos.push(crrPointerPos);
					if(arrPointerPos.length > limPointerPos) {
						arrPointerPos.shift();
					}
					if((evData.x >= 0) && (evData.y >= 0)) { // avoid record: touchend{-1, -1} or scroll{-1, -1}
						minPointerPos.x = Math.min(minPointerPos.x, evData.x);
						minPointerPos.y = Math.min(minPointerPos.y, evData.y);
					}
					// don't rewrite minPointerPos.t, is mixed
					maxPointerPos.x = Math.max(maxPointerPos.x, evData.x);
					maxPointerPos.y = Math.max(maxPointerPos.y, evData.y);
					// don't rewrite maxPointerPos.t, is mixed
					covariancePointer(evData.x, evData.y);
				});

				let crrPointerHit = { x: -1, y: -1, t: null };
				let arrPointerHit = [];
				const limPointerHit = 3;
				$(document).on('click touchstart', (evt) => { // do not fire mousedown, it is fired by both: click or touchstart
					let evData = b$.getCurrentPointerEventXY(evt, true); // viewport only
					crrPointerHit.x = evData.x;
					crrPointerHit.y = evData.y;
					crrPointerHit.t = evData.t;
					arrPointerHit.push(crrPointerHit);
					if(arrPointerHit.length > limPointerHit) {
						arrPointerHit.shift();
					}
					covariancePointer(evData.x, evData.y);
				});

				const $ecanvas = $('#Smart-Captcha-Check-Canvas-' + uuid);
				let ectx = false;
				try {
					ectx = $ecanvas[0].getContext('2d');
				} catch(err) {
					ectx = null;
				}
				if(!ectx) {
					$('#Smart-Captcha-Input-' + uuid).empty().html('<div style="text-align:center!important;color:#EF4756!important;"><br><br><b>' + u$.escape_html(errCanvas) + '<b></div>');
					$('#Smart-Captcha-Check-Info-' + uuid + ' i#Smart-Captcha-Check-Helper').removeClass('sfi-clock2').addClass('sfi-aid-kit').addClass('canvas-error').attr('title', errCanvas);
					return;
				}

				const $time = new Date();
				const $entropy = h$.crc32b($form + '#' + u$.stringIReplaceAll('-', '', d$.getIsoDate($time, true)));
				const $delay = Math.round(Math.random()) + 8;

				$('.Smart-Captcha-Logo').attr('title', 'Secure Captcha by Smart.Framework # E:' + $entropy).css({'cursor':'context-menu'});

				const $bclass = String(opt$.bclass);
				const $bwid = String(opt$.bwid);
				const $bmobile = String(opt$.bmobile);

				const $yc = $('#Smart-Captcha-Icons-' + uuid);
				const $mc = $('#Smart-Captcha-Motion-' + uuid);

				const tHelper = String(opt$.tHelper);
				const tQHelper = String(opt$.tQHelper);
				const tImg = String(opt$.tImg);

				const evs = (timeout) => 'setInterval(() => { ' + String(opt$.exports) + ' ' + 'covariancePointer(crrPointerPos.x, crrPointerPos.y); $entropy = h$.crc32b($form + \'#\' + data$Handler(JSON.stringify(b$.parseCurrentUrlGetParams()), d$.getIsoDate(new Date(), true))); $dhkx = \'' + get$DhKx(opt$.dhkx,4*4*4) + '\';' + ' }, ' + timeout + ');';
				let e1 = mutation$Observer(
					String(opt$.imUrl || ''),
					evs(700)
				);
				let e2 = mutation$Observer(
					String(opt$.iqURL || ''),
					evs(800)
				);

				const contextSupport = ($canvas) => {
					if(($bclass === 'rb') || ($bclass === 'tx') || ($bclass === 'xy') || (($bmobile === 'no') && ($bwid === 'wkt')) || (($bmobile === 'yes') && ($bwid === 'iex'))) {
						return null;
					}
					let cookies = null;
					try {
						cookies = document.cookie.split(';');
					} catch(err){}
					if((!cookies) || (!Array.isArray(cookies)) || (cookies.length < 2)) { // if session there are only 2 !
						return null;
					}
					if(!$canvas) {
						return !!pointer$Observer(!((crrPointerPos.x < -1) || (crrPointerPos.y < -1)), arrPointerPos, limPointerPos, arrPointerHit, limPointerHit); // set lower limit as -1, will consider also meta events ; the last one can be a metaevent !
					} else {
						return true; // canvas supported !
					}
				};

				const fxTick = () => {
					$('#Smart-Captcha-Area-' + uuid).find('div#Smart-Captcha-Info-' + uuid).empty().html('<div style="text-align:center;"><br><img width="24" height="24" src="' + u$.escape_html(String(opt$.baseURL) + String(opt$.imgTick)) + '"></div>');
				};

				const shiftContext = (timeOut, sq=null, $canvas=null) => {
					let txQHelper = tQHelper;
					let oSq = sq;
					sq = u$.format_number_float(sq);
					if(!contextSupport($canvas)) {
						sq = 1;
						txQHelper = '';
					}
					let mFy = Math.trunc(Math.pow(10,3) * Math.log(sq));
					if((typeof(mFy) != 'number') || (!u$.isFiniteNumber(mFy)) || (mFy >= 0)) {
						mFy = -1;
					}
					if(!$canvas) {
						if(timeOut === false) {
							timeOut = 1000;
							if(!event$Observer(mFy)) {
								setTimeout(() => {
									$yc.empty().html('<center><br><img width="48" height="48" src="' + u$.escape_html(String(opt$.baseURL) + String(opt$.ldrImg)) + '"></center>');
									setTimeout(() => {
										$yc.hide();
										$('#Smart-Captcha-Area-' + uuid).find('div#Smart-Captcha-Info-' + uuid).empty().css({'height':'auto'}).html(u$.escape_html(String(opt$.tMotion)));
										$mc.show();
									}, 1500);
								}, 750);
								return;
							} else {
								fxTick();
							}
						} else if((timeOut > 0) && (oSq !== false)) {
							fxTick();
						}
					}
					let mFx = null;
					let mTan = 0;
					let zSVG = null;
					let qSVG = null;
					timeOut = u$.format_number_int(timeOut, false);
					if((timeOut <= 100) || (timeOut > 1000)) {
						timeOut = 1000;
					}
					setTimeout(() => {
						let $box;
						if($canvas) {
							$box = $canvas.parent().parent();
						} else {
							$box = $('#Smart-Captcha-Plugin-' + uuid);
						}
						const $fld = $box.parent().find('div#Smart-Captcha-Input-' + uuid);
						const $inf = $fld.find('div#Smart-Captcha-Info-' + uuid);
						$box.width($box.width()).height($box.height());
						$fld.width($fld.width()).height($fld.height());
						const ajax = b$.AjaxRequestFromURL(String(opt$.baseURL) + String(opt$.motionSvg) + '?c=' + u$.escape_url($box.attr('id')) + '&f=' + u$.escape_url($form) + '&e=' + u$.escape_url($entropy) + '&m=' + u$.escape_url(Math.abs(mFy) + Math.random()) + '&s=' + u$.escape_url(Math.asin(Math.abs(sq)) + Math.random()) + '&t=' + u$.escape_url(d$.getIsoDate($time, true)), 'POST', 'xml', { pointerPosX: crrPointerPos.x, pointerPosY: crrPointerPos.y });
						ajax.done((mSVG) => {
							let isSvgErr = false;
							let $id, $unistrokes;
							if(!mSVG) {
								isSvgErr = true;
							}
							let $svg = null;
							if(!isSvgErr) {
								try {
									$svg = $(mSVG);
								} catch(xmlErr){
									_p$.warn(err6, xmlErr);
									return;
								}
								$id = u$.stringPureVal($svg.find('svg').attr('id'), true);
								if($id !== 'motionCaptcha-shapes') {
									_p$.warn(err4, $id);
									return;
								}
								$unistrokes = u$.stringPureVal($svg.find('svg>g>path').attr('d'), true);
								if((!u$.stringIStartsWith($unistrokes, 'm ')) || (!u$.stringIContains($unistrokes, ' c '))) {
									_p$.warn(err5, $unistrokes.substr(0, 255), '...');
									return;
								}
							}
							try {
								if((typeof(mFx) !== 'function') && e1) {
									eval('((id, Unistrokes) => { ' + String(e1) + ' })($id, $unistrokes, isSvgErr);');
									e1 = null;
									if(typeof(mFx) === 'function') {
										Object.freeze(mFx);
									}
								}
								if(typeof(mFx) === 'function') {
									mFx();
								} else {
									_p$.error('ERR:', err3);
									return;
								}
							} catch(err){
								_p$.error('ERR:', err1); // , err
								return;
							}
							$('#Smart-Captcha-Accessibility-Context-' + uuid).hide();
							if((typeof(zSVG) === 'string') && zSVG) {
								const pointerData = String(JSON.stringify({ arrPointerPos: arrPointerPos, limPointerPos: limPointerPos, arrPointerHit: arrPointerHit, limPointerHit: limPointerHit }));
								const checksum = String(h$.sh3a384(String(uuid) + ':' + String(pointerData)));
								$fld.find('input').data('pointer', JSON.stringify({ pointerData: u$.b64Enc(pointerData), checksum: checksum })).show();
								$fld.find('div#Smart-Captcha-Info-' + uuid).empty().css({'height':'auto'}).html(u$.escape_html(tHelper + ' ' + txQHelper));
								$box.empty().css({'height':'auto'}).addClass('strip-back').html('<center><div style="cursor:default;">' + $('<div></div>').html(u$.b64Dec(zSVG)).html() + '</div></center>').attr('title', tImg);
								if((typeof(qSVG) !== 'string') && e2) {
									try {
										eval('(() => { ' + String(e2) + ' })();');
										e2 = null;
									} catch(err){
										_p$.error('ERR:', err2);
										return;
									}
								}
								if((typeof(qSVG) === 'string') && qSVG) {
									if(contextSupport()) {
										$box.append('<br><div style="cursor:help; margin-top:7px;" title="' + u$.escape_html(tQHelper) + '"><center>' + $('<div></div>').html(u$.b64Dec(qSVG)).html() + '</center></div><div class="Smart-Captcha-Accessibility-Symbols" title="Accessibility Context for Captcha"><i class="sfi sfi-headphones"></i>&nbsp;<i class="sfi sfi-accessibility"></i></div>');
									} else {
										$box.append('<br><center><div class="Smart-Captcha-Accessibility-X" style="width:64px; height:64px;"><i class="sfi sfi-qrcode xbarcode" title="' + u$.escape_html(String(opt$.tQCode)) + '"></i><br></div></center><br>');
									}
								}
							} else if((typeof(mTan) == 'number') && u$.isFiniteNumber(mTan) && (mTan > 1)) {
								$inf.empty().html('<div class="Smart-Captcha-Done"><br><img width="48" height="48" src="' + u$.escape_html(String(opt$.baseURL) + String(opt$.ldrImg)) + '">');
								setTimeout(() => {
									$inf.empty().html('<div class="Smart-Captcha-Done"><br><img width="24" height="24" src="' + u$.escape_html(String(opt$.baseURL) + String(opt$.imgDone)) + '"><br><span><b>' + u$.escape_html(String(opt$.tPassed)) + '</b></span></div>');
								}, 1500);
							} else {
								$box.empty().text(errx2 + ' ...');
							}
						}).fail((jqXHR, textStatus, errorThrown) => {
							_p$.warn('ERR:', err0);
						});
					}, timeOut);
				};

				$yc.hide().find('#Smart-Captcha-Icons-Container-' + uuid).iconsCaptcha({
					clickDelay: -1, // trigger !
					iconsURL:   String(opt$.baseURL) + String(opt$.iIcnsLst + '?u=' + u$.escape_url(uuid) + '&f=' + u$.escape_url($form) + '&e=' + u$.escape_url($entropy) + '&t=' + u$.escape_url(d$.getIsoDate($time, true))),
					loaderImg:  String(opt$.baseURL) + String(opt$.ldrImg),
					loaderEImg: String(opt$.baseURL) + String(opt$.ldrEImg),
					hintText:   String(opt$.iIcns),
					doneText:   String(opt$.iIcnsDone),
					fxHandler: (attainment, expr) => {
						let theSel = 0;
						if((1 <= expr) && (expr < 2)) {
							theSel = ((attainment <= 1) ? (attainment - ((Math.random() / 10) * 0.3)) : covariancePointer(crrPointerHit.x, crrPointerHit.y));
						}
						setTimeout(() => { shiftContext(false, theSel); }, 1000); // next only through drawer
					}
				});

				$mc.hide().motionCaptcha({
					canvasId: '#Smart-Captcha-Motion-Canvas-' + uuid,
					maxTries: 2,
					errorMsg: String(opt$.tMotionErr),
					warnMsg: String(opt$.tMotionWarn),
					completedMsg: String(opt$.tMotionDone),
					onCompletion: ($canvas, ctx, attainment) => {
						$('#Smart-Captcha-Motion-Canvas-' + uuid).css({opacity:0.8});
						setTimeout(() => { shiftContext(1000, Math.atan(Math.tan(Math.abs(attainment))), $canvas); }, 500);
					}
				});

				$('#Smart-Captcha-Accessibility-Context-' + uuid).on('click', () => { // don't use here: mousedown touchstart ; will trigger later: mouseenter to avoid trigger more than once !
					shiftContext(250, false);
				});

				const eShift = 5;
				const sDefStroke = '#4D5774';
				const sDefShadow = '#5E6885';
				const sHiStroke = '#EF4755';
				const sHiShadow = '#C2203F';
				const sLockStroke = '#2A3140';
				ectx.canvasWidth = u$.format_number_int(Number.parseInt($ecanvas.width()), false);
				ectx.canvasHeight = u$.format_number_int(Number.parseInt($ecanvas.height()), false);

				let vTick = false;
				const dTick = () => {
					if(!!vTick) {
						return;
					}
					vTick = true;
					ectx.strokeStyle = sDefStroke;
					ectx.shadowColor = sDefShadow;
					ectx.shadowBlur = 2;
					ectx.lineJoin = 'bevel';
					ectx.lineWidth = 4;
					$ecanvas.hover( // fix back: use hover function to avoid trigger more than once per pointer event on touch devices
						() => {
							ectx.clearRect(120-eShift, 8-eShift, 24+(2*eShift), 24+(2*eShift));
							ectx.strokeStyle = sDefShadow;
							ectx.shadowColor = sDefStroke;
							ectx.shadowBlur = 0;
							ectx.stroke();
							ectx.fillStyle = sHiStroke;
							ectx.fillRect(120+eShift, 8+eShift, 24-(2*eShift), 24-(2*eShift));
						},
						() => {
							ectx.clearRect(120-eShift, 8-eShift, 24+(2*eShift), 24+(2*eShift));
							ectx.strokeStyle = sDefStroke;
							ectx.shadowColor = sDefShadow;
							ectx.shadowBlur = 2;
							ectx.stroke();
							ectx.clearRect(120+eShift, 8+eShift, 24-(2*eShift), 24-(2*eShift));
						}
					);
					ectx.beginPath();
					ectx.rect(120, 8, 24, 24);
					ectx.stroke();
				};

				const eTick = (mouseX, mouseY) => {
					mouseX = u$.format_number_int(mouseX);
					mouseY = u$.format_number_int(mouseY);
					const $earea = $ecanvas.parent();
					const $now = new Date();
					let isHit = true;
					if(Math.floor(($now - $time) / 1000) < u$.format_number_int($delay, false)) {
						isHit = false;
					}
					const entropy = h$.crc32b(String(JSON.stringify({ uuid: uuid, isHit: isHit, date: new Date(), time: $time, entropy: $entropy, urlParams: b$.parseCurrentUrlGetParams(), arrPointerPos: arrPointerPos, limPointerPos: limPointerPos, arrPointerHit: arrPointerHit, limPointerHit: limPointerHit })));
					const testEntropy = Number.parseInt(entropy.substr(1,1) + entropy.substr(3,1) + entropy.substr(5,1), 16);
					if((testEntropy < 16) || (testEntropy > 3584)) {
						isHit = false;
					}
					if(!!pointer$Observer(isHit, arrPointerPos, limPointerPos, arrPointerHit, limPointerHit)) {
						shiftContext(100, (mouseX || mouseY) ? covariancePointer(mouseX, mouseY) : null);
					} else {
						$ecanvas.fadeOut('slow');
						setTimeout(() => {
							$earea.hide();
							$('#Smart-Captcha-Area-' + uuid).find('div#Smart-Captcha-Info-' + uuid).empty().css({'height':'auto'}).html(u$.escape_html(String(opt$.iIcns)));
							$yc.fadeIn('slow'); // .show()
							setTimeout(() => {
								$yc.trigger('captcha');
							}, 1500);
						}, 700);
					}
				};

				let cTick = false;
				$('#Smart-Captcha-Check-Canvas-' + uuid).on('click', (evt) => { // don't use here: mousedown touchstart ; will trigger later: mouseenter to avoid trigger more than once !
					if(!!cTick) {
						return;
					}
					dTick();
					$('#Smart-Captcha-Check-Timer-' + uuid).hide();
					const $obj = $(evt.currentTarget);
					if(!$obj) {
						_p$.warn(err7);
						return;
					}
					$obj.trigger('mouseenter'); // req. by touch devices
					let ofs = $obj.offset();
					let evData = b$.getCurrentPointerEventXY(evt); // must be the full page, not the viewport, expects page X/Y not client X/Y !
					if(evData.x < 0) { evData.x = 0; } // dissalow negatives to avoid change the sense
					if(evData.y < 0) { evData.y = 0; } // dissalow negatives to avoid change the sense
					let mouseX = Math.round(evData.x - u$.format_number_int(ofs.left));
					let mouseY = Math.round(evData.y - u$.format_number_int(ofs.top));
					if((mouseX < 120) || (mouseX > 144) || (mouseY < 8) || (mouseY > 32)) { // detect click coordinates ; if not pass here it is not a real click event, it is emulated, stop here
						return;
					}
					covariancePointer(mouseX, mouseY);
					cTick = true;
					$ecanvas.hover().off();
					ectx.clearRect(120+eShift, 8+eShift, 24-(2*eShift), 24-(2*eShift));
					ectx.strokeStyle = sHiStroke;
					ectx.shadowColor = sHiShadow;
					ectx.shadowBlur = 0;
					ectx.stroke();
					ectx.fillStyle = sLockStroke;
					ectx.fillRect(120+eShift, 8+eShift, 24-(2*eShift), 24-(2*eShift));
					$('#Smart-Captcha-Check-Info-' + uuid + ' i#Smart-Captcha-Check-Helper').removeClass('sfi-clock2 sfi-arrow-right').addClass('sfi-radio-checked');
					const $earea = $ecanvas.parent();
					$earea.css({opacity:0.7});
					setTimeout(() => { eTick(mouseX, mouseY); }, 1250);
				});

				b$.CountDown(u$.format_number_int($delay, false), 'Smart-Captcha-Check-Timer-' + uuid, (counter, elID) => {
					dTick();
					$('#' + elID).hide();
					$('#Smart-Captcha-Check-Info-' + uuid + ' i#Smart-Captcha-Check-Helper').removeClass('sfi-clock2').addClass('sfi-arrow-right');
				});

			}; //END
			_C$.handleForm = handleForm;


		}}; //END CLASS

		smartCaptchaFormHandler.secureClass(); // implements class security

		window.smartCaptchaFormHandler = smartCaptchaFormHandler; // global export

	} //end if

});

// #END
