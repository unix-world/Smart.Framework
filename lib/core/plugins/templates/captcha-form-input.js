
// Captcha Input Handler
// (c) 2021-2022 unix-world.org
// License: BSD
// r.20220114

jQuery(() => {

	if((typeof(smartCaptchaInputHandler) == 'undefined')) {

		const smartCaptchaInputHandler = new class{constructor(){ // STATIC CLASS, ES6
			const _N$ = 'smartCaptchaInputHandler';

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

			const u$ = smartJ$Utils;
			const e$ = smartJ$Base64;
			const h$ = smartJ$CryptoHash;
			const c$ = smartJ$CryptoBlowfish;
			const c$d = c$.decrypt;
			const x$ = smartJ$DhKx;
			const t$ = smartJ$TestBrowser;
			const b$ = smartJ$Browser;

			let numClicks = 0;


			const covarianceObserver = (num) => { // ES6
				num = u$.format_number_float(num);
				return !! ((num >= -162) && (num <= -20));
			};
			_C$.covarianceObserver = covarianceObserver; // export


			const pointerObserver = function(isHit, arrPointerPos, limPointerPos, arrPointerHit, limPointerHit) { // ES6
				if((!Array.isArray(arrPointerPos)) || (!Array.isArray(arrPointerHit))) {
					return false;
				}
				limPointerPos = u$.format_number_int(limPointerPos, false);
				limPointerHit = u$.format_number_int(limPointerHit, false);
				if((limPointerPos < 50) || (limPointerHit < 2)) { // hardcoded: min 50 pos, min 2 hits
					return false;
				}
				let mRatio = 1;
				if(t$.checkIsMobileDevice(true)) {
					mRatio = 3; // tunning for mobile: there are much fewer pointer events than desktop
				}
				if(
					(arrPointerPos.length > limPointerPos) ||
					(arrPointerHit.length > limPointerHit) ||
					(arrPointerPos.length < Math.ceil(limPointerPos / mRatio)) ||
					(arrPointerHit.length < Math.ceil(limPointerHit / 2))
				) {
					return false;
				}
				return !!isHit; // true or false, depending by the hit data

			}; //END
			_C$.pointerObserver = pointerObserver; // export


			const handleleave = (fld, evt) => { // ES6
				const _m = 'Captcha Input Leave';
				const _err$ = _p$.error;
				const _warn$ = _p$.warn;
				if(fld == undefined) {
					_err$(_m, 'Undefined Field');
					return;
				}
				if(fld == undefined) { return; }
				if(fld.data('captcha') !== h$.sha1(h$.crc32b(fld.data('id')) + h$.md5(fld.data('time')))) {
					fld.val('');
					return;
				}
				const parseEvent = (evt, data) => u$.stringTrim(c$d(u$.stringPureVal(u$.stringTrim(evt)), u$.stringPureVal(u$.stringTrim(data))));
				const dataEvent = parseEvent(
					u$.stringPureVal(evt),
					u$.stringPureVal(
						u$.stringPureVal(typeof(null)).toLowerCase()+'.id_'+u$.stringPureVal(Number(fld.data('time')).toFixed(0)) +
						'==\'' +
						u$.stringPureVal(getDhKx(fld.data('dhkx')), true)+'\''
					)
				);
				if(!dataEvent) {
					_err$(_m, 'Null Event');
					return;
				}
				let fx = Function('fld', u$.stringPureVal(dataEvent));
				try {
					fx(fld);
				} catch(err) {
					_err$(_m, 'Invalid Event', err);
					return;
				}
			}; //END
			_C$.handleleave = handleleave; // export


			const handleHit = function(fld, evt) { // ES6
				const _m = 'Captcha Input Hit';
				const _err$ = _p$.error;
				const _warn$ = _p$.warn;
				if(fld == undefined) {
					_err$(_m, 'Undefined Field');
					return;
				}
				fld.val('');
				if(!fld.data('id')) {
					_err$(_m, 'Invalid Field Data ID');
					return;
				}
				if(evt == undefined) {
					_err$(_m, 'Undefined Event');
					return;
				}
				let ofs = fld.offset();
				let evData = b$.getCurrentPointerEventXY(evt); // must be the full page, not the viewport, expects page X/Y not client X/Y !
				if(evData.x < 0) { evData.x = 0; } // dissalow negatives to avoid change the sense
				if(evData.y < 0) { evData.y = 0; } // dissalow negatives to avoid change the sense
				let _x = Math.round(evData.x - u$.format_number_int(ofs.left));
				let _y = Math.round(evData.y - u$.format_number_int(ofs.top));
				if(!_x || !_y) { // detect click coordinates ; if not pass here it is not a real click event, it is emulated, stop here
					return;
				}
				if(!fld.attr('data-captcha')) { // avoid duplicate action
					let errDataPointer = false;
					let dataPointer = null;
					if(fld.data('pointer')) { // cond. req. for std !
						dataPointer = u$.stringPureVal(fld.data('pointer') || ''); // if found set to non-null, empty str
					}
					if((dataPointer !== null) && (!dataPointer)) {
						_err$(_m, 'No Pointer Data');
						return;
					} else if(dataPointer) {
						try {
							dataPointer = JSON.parse(dataPointer);
						} catch(jErr){
							dataPointer = false;
							errDataPointer = 1;
						}
						if(!errDataPointer) {
							if(typeof(dataPointer) != 'object') {
								dataPointer = false;
								errDataPointer = 2;
							} else {
								dataPointer.pointerData = e$.decode(dataPointer.pointerData);
								if(h$.sha512(u$.stringPureVal(fld.data('id')) + ':' + u$.stringPureVal(dataPointer.pointerData)) !== dataPointer.checksum) {
									dataPointer = false;
									errDataPointer = 3;
								} else {
									let data = dataPointer.pointerData;
									dataPointer = null;
									try {
										dataPointer = JSON.parse(data);
									} catch(jErr){
										dataPointer = false;
										errDataPointer = 4;
									}
								}
							}
						}
						if(errDataPointer) {
							_err$(_m, 'Invalid Pointer Data', '(' + errDataPointer + ')');
							return;
						}
						if(typeof(dataPointer) != 'object') {
							_err$(_m, 'Invalid Pointer Data');
							return;
						}
						if(!pointerObserver(true, dataPointer.arrPointerPos, dataPointer.limPointerPos, dataPointer.arrPointerHit, dataPointer.limPointerHit)) {
							numClicks++;
							if(numClicks < 2) {
								return;
							}
						}
					}
					fld.attr('data-captcha', h$.sha1(h$.crc32b(fld.data('id')) + h$.md5(fld.data('time'))));
				}
			}; //END
			_C$.handleHit = handleHit; // export


			const getDhKx = function(dhkx) {
				dhkx = x$.getIdzShadData(dhkx);
				if(dhkx.err) {
					return null;
				} //end if
				return u$.bin2hex(dhkx.shad);
			}; //END


		}}; //END CLASS

		smartCaptchaInputHandler.secureClass(); // implements class security

		window.smartCaptchaInputHandler = smartCaptchaInputHandler; // global export

	} //end if

});

// #END
