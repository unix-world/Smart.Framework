
// JS: Smart Watch (Clock)
// The most part of the following javascript code, the watch clock, (modified by unix-world.org) is open-source, taken from (c) 2021 developer.mozilla.org, License MIT # sample basic animations for canvas
// (c) 2021-present unix-world.org - all rights reserved
// v.20250207

// DEPENDS: jQuery

//================== [ES6]

if(typeof(WatchClock) != 'undefined') { // fix for load by ajax tabs

	console.warn('JS WatchClock class has been already loaded !');

} else {

const WatchClock = (divId, scaleFactor=1, themeDark=false) => {
	//--
	// default: divId: 'Watch-Clock'
	// external setting: watchCanvasScaleFactor | 0.4 .. 2.0
	// external setting: watchThemeDark | bool
	// sample canvas: '<canvas id="Watch-Clock" class="Watch-Clock" width="150" height="150"></canvas>'
	//--
	divId = String(divId || '').trim();
	if(divId == '') {
		divId = 'Watch-Clock';
	} //end if
	//--
	themeDark = !! themeDark;
	if(typeof(watchThemeDark) != 'undefined') {
		themeDark = !! watchThemeDark;
	} //end if
	//--
	if(typeof(jQuery) == 'undefined') {
		console.warn('Watch-Clock WARN: jQuery is missing ...');
		try {
			const cnv = document.getElementById('Watch-Clock'); cnv.style.opacity = 1; cnv.style.border = '1px dotted #FF3300'; const cx = cnv.getContext('2d'); cx.fillStyle = '#FF3300'; cx.textAlign = 'left'; cx.font = 'bold 10px mono'; cx.fillText('Watch-Clock: ERR:', 10, 20); cx.fillText('jQuery is missing', 10, 40);
		} catch(err) {
			console.error('Watch-Clock ERR:', err);
		} //end try catch
		return;
	} //end if
	//--
	const $canvas = jQuery('#' + divId);
	if(!$canvas.length) {
		console.warn('WatchClock: WARN: Canvas not found');
		return;
	} //end if
	//--
	let ctx;
	try {
		ctx = $canvas[0].getContext('2d');
	} catch(err) {
		ctx = null;
		console.error('WatchClock ERR:', err);
	} //end try catch
	if(!ctx) {
		console.warn('WatchClock: WARN: Could not find Canvas Context');
		return;
	} //end if
	//--
	scaleFactor = Number(scaleFactor);
	if(typeof(watchCanvasScaleFactor) != 'undefined') {
		scaleFactor = Number.parseFloat(watchCanvasScaleFactor);
	} //end if
	if(Number.isFinite(scaleFactor) && (!Number.isNaN(scaleFactor))) {
		if((scaleFactor < 0.4) || (scaleFactor > 2)) {
			scaleFactor = 1;
		} //end if
	} else {
		scaleFactor = 1;
	} //end if
	//--
	$canvas.width(Math.ceil(scaleFactor * 150)).height(Math.ceil(scaleFactor * 150));
	const animate = (fx) => {
		if(typeof(fx) !== 'function') {
			console.error('WatchClock ERR: animate function is Invalid');
			return;
		} //end if
		try {
			self.requestAnimationFrame(fx);
		} catch(err) {
			console.error('WatchClock: Animation ERR:', err);
		} //end try catch
	};
	//--
	const watch = () => {
		let now = new Date();
		$canvas.attr('title', String(now));
		ctx.save();
		ctx.clearRect(0, 0, 150, 150);
		ctx.translate(75, 75);
		ctx.scale(0.4, 0.4);
		ctx.rotate(-Math.PI / 2);
		ctx.strokeStyle = themeDark ? '#DDDDDD' : '#444444';
		ctx.fillStyle = '#FFFFFF';
		ctx.lineWidth = 8;
		ctx.lineCap = 'round';
		// Hour marks
		ctx.save();
		let i;
		for(i=0; i<12; i++) {
			switch(i) {
				case 2: // 3
				case 5: // 6
				case 8: // 9
				case 11: // 12
					ctx.strokeStyle = '#ED2839';
					break;
				default:
					ctx.strokeStyle = themeDark ? '#EEEEEE' : '#333333';
			} //end switch
			ctx.beginPath();
			ctx.rotate(Math.PI / 6);
			ctx.moveTo(100, 0);
			ctx.lineTo(120, 0);
			ctx.stroke();
		} //end for
		ctx.restore();
		// Minute marks
		ctx.save();
		ctx.lineWidth = 5;
		for(i=0; i<60; i++) {
			if(i % 5!= 0) {
				ctx.beginPath();
				ctx.moveTo(117, 0);
				ctx.lineTo(120, 0);
				ctx.stroke();
			} //end if
			ctx.rotate(Math.PI / 30);
		} //end for
		ctx.restore();
		let sec = now.getSeconds();
		let mins = now.getMinutes();
		let hr  = now.getHours();
		hr = hr >= 12 ? hr - 12 : hr;
		ctx.fillStyle = themeDark ? '#FFFFFF' : '#222222';
		// write Hours
		ctx.save();
		ctx.rotate(hr * (Math.PI / 6) + (Math.PI / 360) * mins + (Math.PI / 21600) *sec);
		ctx.lineWidth = 14;
		ctx.beginPath();
		ctx.moveTo(-20, 0);
		ctx.lineTo(80, 0);
		ctx.stroke();
		ctx.restore();
		// write Minutes
		ctx.save();
		ctx.rotate((Math.PI / 30) * mins + (Math.PI / 1800) * sec);
		ctx.lineWidth = 10;
		ctx.beginPath();
		ctx.moveTo(-28, 0);
		ctx.lineTo(112, 0);
		ctx.stroke();
		ctx.restore();
		// Write seconds
		ctx.save();
		ctx.rotate(sec * Math.PI / 30);
		ctx.strokeStyle = '#ED2839';
		ctx.fillStyle = '#ED2839';
		ctx.lineWidth = 6;
		ctx.beginPath();
		ctx.moveTo(-30, 0);
		ctx.lineTo(83, 0);
		ctx.stroke();
		ctx.beginPath();
		ctx.arc(0, 0, 10, 0, Math.PI * 2, true);
		ctx.fill();
		ctx.beginPath();
		ctx.arc(95, 0, 10, 0, Math.PI * 2, true);
		ctx.stroke();
		ctx.fillStyle = themeDark ? 'rgba(255, 255, 255, 0)' : 'rgba(0, 0, 0, 0)';
		ctx.arc(0, 0, 3, 0, Math.PI * 2, true);
		ctx.fill();
		ctx.restore();
		ctx.beginPath();
		ctx.lineWidth = 7;
		ctx.strokeStyle = themeDark ? '#CCCCCC' : '#555555';
		ctx.arc(0, 0, 142, 0, Math.PI * 2, true);
		ctx.stroke();
		ctx.restore();
		//--
		animate(watch); // handle animation
		//--
	};
	//--
	animate(watch); // start animation
	//--
};

window.WatchClock = WatchClock; // global export

} //end if

//==

// #END
