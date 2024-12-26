// smart-jRate.js
// (c) 2022 unix-world.org
// v.20221005

const smartjRate = (ratingArea, ratingRegisterUrl) => {

	const _p$ = console;
	const ERRMSG = 'ERROR:';
	const MSGAREA = 'Rating Stars';
	if(typeof(ratingArea) == 'undefined') {
		_p$.error(ERRMSG, MSGAREA, 'Page is undefined'); // the rating area ; can be the relative URL to the current HTML page ; ex: '/my-page-with-ratings.html' ; or 'sub-dom.mydom.ext/my-page-with-ratings.html' ; MUST NOT CONTAIN ANY URL PREFIX such as: '//' or 'http(s)://'
		return;
	}
	if(typeof(ratingRegisterUrl) == 'undefined') {
		_p$.error(ERRMSG, MSGAREA, 'URL is undefined'); // the URL to register the rating ; JSON ; ex: '/handle-ratings.json' or 'http(s)://my-dom.ext/handle-ratings.json' ; {"status":"ERROR|WARN|OK","message":"The Message"}
		return;
	}
	const $ = jQuery;
	const u$ = smartJ$Utils;
	const pure$ = u$.stringPureVal;
	const html$ = u$.escape_html;
	const strc$ = u$.stringContains;
	const decf$ = u$.format_number_dec;
	const intf$ = u$.format_number_int;
	const hash$ = smartJ$CryptoHash.sha512;
	const b$ = smartJ$Browser;
	const growl$ = b$.GrowlNotificationAdd;
	const ajx$ = b$.AjaxRequestFromURL;
	const ckg$ = b$.getCookie;
	const cks$ = b$.setCookie;
	const rUrl = pure$(ratingRegisterUrl, true);
	if(rUrl == '') {
		_p$.warn(ERRMSG, MSGAREA, 'URL is Empty');
		return;
	}
	if(strc$(rUrl, '//', 0)) {
		_p$.warn(ERRMSG, MSGAREA, 'URL is Disallowed');
		return;
	}
	const rArea = pure$(ratingArea, true);
	if(rArea == '') {
		_p$.warn(ERRMSG, MSGAREA, 'Area is Empty');
		return;
	}
	const fxActiveRating = (rating) => {
		const hTitle = '*Ratings';
		const ckId = 'Ratings_' + hash$(rUrl);
		const pRate = pure$(ckg$(ckId), true);
		const cRate = hash$(location.href || '');
		const imgPath = 'lib/framework/img/';
		if(pRate === cRate) {
			growl$(String(hTitle), '<h5>Already Rated ...</h5>', imgPath + 'sign-notice.svg', 2500, false, 'light');
			return;
		}
		let ckSet = false;
		ajx$(String(rUrl), 'POST', 'json', { frm: { 'form:action':'rating-stars', 'form#url':String(rArea), 'rating/value':String(rating) } }).done((msg) => {
			if(msg && msg.status && (msg.status === 'OK')) {
				growl$(String(hTitle), (msg.message || ''), imgPath + 'sign-ok.svg', 3000, false, 'info');
				ckSet = true;
			} else {
				if(msg.status == 'WARN') {
					growl$(String(hTitle), '<h5>' + (msg.message || '') + '</h5>', imgPath + 'sign-notice.svg', 2500, false, 'light');
					ckSet = true;
				} else {
					_p$.warn('ERR:', msg.status, $('<div>' + String(msg.message || '') + '</div>').text(), 'rating:', rating);
					growl$(String(hTitle) + (msg.status || ''), (msg.message || ''), imgPath + 'sign-warn.svg', 3500, false, 'colored');
				}
			}
			if(!!ckSet) {
				cks$(ckId, cRate);
			}
		}).fail((msg) => {
			_p$.warn('FAIL:', 'Rating Failed: Invalid Server Response !', (msg.status || 0), 'rating:', rating);
			growl$('Request FAILED', 'Invalid Server Response !', imgPath + 'sign-error.svg', 3500, false, 'error');
		});
	};
	const renderStars = (timeOut, id, yd, isAct, rVal, clrInStar, clrStar1, clrStar2, szStar, fxRating) => {
		setTimeout(() => {
			$('#' + String(id) + ' > div#' + String(yd)).jRate({
				readOnly: ! isAct,
				rating: rVal,
				transparency: 1,
				backgroundColor: String(clrInStar),
				strokeColor: String(clrInStar),
				startColor: String(clrStar1),
				endColor: String(clrStar2),
				width: szStar,
				height: szStar,
				precision: 0.1,
				minSelected: 0.1,
				onSet: fxRating,
			});
		}, timeOut);
	};
	$('.rating-stars').each((idx, el) => {
		$el = $(el);
		let rVal = decf$($el.text(), 2, false, false);
		if(rVal < 0) {
			rVal = 0;
		} else if(rVal > 5) {
			rVal = 5;
		}
		let szStar = intf$(($el.attr('data-stars') || ''), false);
		if((szStar < 16) || (szStar > 96)) {
			szStar = 32;
		}
		let clrInStar = '#E9EDCA';
		let clrStar1 = '#5E6885';
		let clrStar2 = '#4D5774';
		const vCnt = pure$($el.attr('data-count') || '', true);
		let isAct = false;
		const cfgAct = pure$($el.attr('data-ratings') || '', true);
		const cfgAltClr = pure$($el.attr('data-color') || '', true);
		if(cfgAltClr == 'alt') { // for reviews
			clrInStar = '#CCDDEE';
			clrStar1 = '#FF4173';
			clrStar2 = '#FF5273';
		}
		switch(cfgAct.toLowerCase()) {
			case 'true':
			case 'yes':
			case '1':
				isAct = true;
				break;
			default:
				isAct = false;
		}
		let fxRating = null;
		if(!!isAct) { // active
			clrInStar = '#EDEDED';
			clrStar1 = '#FFDD00';
			clrStar2 = '#FFCC00';
			fxRating = fxActiveRating;
		}
		let vNum = 0;
		let vTxt = '';
		let vTtl = '';
		const txtRating = 'Rating';
		const txtVotes = 'Votes';
		if(vCnt != '') {
			vNum = intf$(vCnt, false);
			vTtl = ' ; ' + html$(txtVotes) + ': ' + html$(vCnt);
			vTxt = '&nbsp;&middot;&nbsp;' + txtVotes + ':&nbsp;' + vNum;
		}
		const id = 'rating-stars-area-' + u$.uuid().toLowerCase();
		const yd = 'jRate-stars-' + u$.uuid().toLowerCase();
		$el.parent().append('<div id="' + html$(id) + '" class="rating-stars-area" title="' + html$(txtRating) + ': ' + html$(rVal) + vTtl + '"><div id="' + html$(yd) + '"></div><span>&nbsp;' + html$(txtRating) + ':&nbsp;<span>' + decf$(rVal, 1, false, true) + '&nbsp;/&nbsp;5' + vTxt + '</span></span></div>');
		renderStars(250, id, yd, isAct, rVal, clrInStar, clrStar1, clrStar2, szStar, fxRating);
	});

};

// #END
