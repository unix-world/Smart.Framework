
// Smart.Framework Syntax Highlight Helper using prism
// v.20220918
// (c) 2021-2022 unix-world.org

// REQUIRES: jQuery

/* complete setup:
<link rel="stylesheet" href="lib/js/jshilitecode/prism/fonts.css">
<link rel="stylesheet" href="lib/js/jshilitecode/prism/prism.css">
<script src="lib/js/jshilitecode/prism/prism.js"></script>
<script src="lib/js/jshilitecode/smart-highlight.js"></script>
*/

if(typeof(SmartJS_Custom_Syntax_Highlight) != 'function') { // avoid export more than once
	var SmartJS_Custom_Syntax_Highlight = (selector) => { // var, global export
		const $ = jQuery;
		setTimeout(() => {
			$(String(selector) + ' pre > code').each((i, el) => {
				let $el = $(el);
				let theSyntax = $el.attr('data-syntax');
				let theTitle = '';
				if((theSyntax != undefined) && (theSyntax != '')) { // if syntax is empty highlight will use the class
					theTitle = theSyntax;
					$el.attr('title', 'Syntax: ' + String(theTitle)).addClass('syntax').addClass('language-' + theSyntax);
					$el.parent().addClass('syntax').addClass('line-numbers');
					try {
						Prism.highlightElement(el);
					} catch(err) {
						console.error('SmartJS_Custom_Syntax_Highlight', 'ERR: Failed to instantiate for selector #' + i + ' @ for syntax: ' + String(theSyntax), err);
					} //end try catch
					theTitle = null;
					theSyntax = null;
					$el = null;
				} //end if
			});
		}, 50);
	} // END FUNCTION
} //end if

// #END
