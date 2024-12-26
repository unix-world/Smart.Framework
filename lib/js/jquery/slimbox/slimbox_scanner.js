
// Smart.Framework JS - Slimbox Scanner
// (c) 2006-2019 unix-world.org
// r.20190525

// DEPENDS: jQuery, Slimbox

//===========================================

$(function() { // ON DOCUMENT READY
	//--
//	if(!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
	if(!/ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
		//$("a[rel^='slimbox']").slimbox({}, null, function(el) { return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); });
		$('body').on('click', 'a[data-slimbox]', function(el) {
			var SFSlimBox__Data = [];
			var crrIndex = -1;
			//console.log(el.currentTarget);
			$('a[data-slimbox]').each(function(index) {
				//console.log($(this)[0]);
				var href = $(this).attr('href');
				if(!href) {
					return; // exclude links with no href
				} //end if
				var title = $(this).attr('title');
				if($(this)[0] === el.currentTarget) {
					crrIndex = index;
				} //end if
				var arr = [];
				arr.push(href);
				arr.push(title ? title : '');
				SFSlimBox__Data.push(arr);
			});
			if(crrIndex >= 0) {
				return $.slimbox(SFSlimBox__Data, crrIndex, {});
			} //end if
		});
	} //end if
	//--
}); //END ON DOCUMENT READY FUNCTION

//===========================================

// #END
