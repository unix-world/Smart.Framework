
// Smart.Framework MathLatex Render Helper using mathquill
// v.20220906
// (c) 2022 unix-world.org

// REQUIRES: jQuery, smartJ$Utils

/* complete setup:
<link rel="stylesheet" href="lib/js/jsmathquill/mathquill/fonts.css">
<link rel="stylesheet" href="lib/js/jsmathquill/mathquill/mathquill.css">
<script src="lib/js/jsmathquill/mathquill/mathquill.js"></script>
<script src="lib/js/jsmathquill/smart-mathquill.js"></script>
*/

const smartRenderMathLatexFormula = ($code, slotId, hideSource) => {

	if($code == undefined) {
		return;
	} //end if
	if(!$code.length) {
		return;
	} //end if
	const code = smartJ$Utils.stringPureVal(($code.text() || ''), true);
	if(code == '') {
		return;
	} //end if

	slotId = smartJ$Utils.stringTrim(smartJ$Utils.create_htmid(smartJ$Utils.stringPureVal(slotId, true)));
	const theUid = 'math-latex-uid-' + smartJ$Utils.uuid().toLowerCase();
	if(slotId == '') {
		slotId = theUid;
	} //end if

	hideSource = !! hideSource; // bool

	let theElId = smartJ$Utils.stringPureVal($code.attr('id'));
	if(theElId == '') {
		theElId = theUid;
		$code.attr('id', theElId); // set back
	} //end if

	const elemId = 'mathquill-render-area-' + slotId;
	let theHtml = '<div role="math" id="' + smartJ$Utils.escape_html(elemId) + '" class="math-render-latex" data-math-latex="' + smartJ$Utils.escape_html(theElId);
	theHtml += '" title="Math Latex Formula Render #' + smartJ$Utils.escape_html(slotId);
	if(hideSource === true) {
		theHtml += '\n' + '`' + smartJ$Utils.escape_html(code) + '`';
	} //end if
	theHtml += '"></div>';
	$code.after(theHtml);
	const $render = jQuery('#' + elemId);
	if(!$render.length) {
		return;
	} //end if

	const mqi = MathQuill.getInterface(2);
	let isOk = true;
	try {
		mqi.StaticMath($render[0]).latex(code);
	} catch(err) {
		isOk = false;
		console.warn('MathQuill ERR:', err);
	} //end try catch

	if(isOk === true) {
		const testRenderTxt = smartJ$Utils.stringTrim(jQuery('#' + elemId).text());
		if((testRenderTxt == '') || (testRenderTxt == '$$')) { // sometimes when could not render will return just '$$'
			hideSource = false; // could not render ...
			$code.addClass('mathLatexRenderFail');
		} //end if
		if(hideSource === true) {
			$code.empty().html('');
		} //end if
		$code.attr('data-math-latex', smartJ$Utils.escape_url(code)).append($render);
	} else {
		$code.addClass('mathLatexRenderFail');
	} //end if

};

if(typeof(SmartViewHelpersMathLatexHide) == 'undefined') { // avoid export more than once
	var SmartViewHelpersMathLatexHide = true; // var, global export
} //end if

if(typeof(SmartJS_Custom_Math_Latex_Render) != 'function') { // avoid export more than once
	var SmartJS_Custom_Math_Latex_Render = (selector) => { // var, global export
		const $ = jQuery;
		setTimeout(() => {
			$(String(selector) + ' div.math-latex').each((i, el) => {
				let $el = $(el);
				smartRenderMathLatexFormula($el, i+1, !!SmartViewHelpersMathLatexHide);
			});
		}, 70);
	} // END FUNCTION
} //end if

// #END


// #END
