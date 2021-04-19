
// Smart.Framework Syntax Highlight Helper using highlightjs
// v.20210407

/* minimal complete setup:
<link rel="stylesheet" href="lib/js/jshighlight/css/github.css">
<script type="text/javascript" src="lib/js/jshighlight/highlight.js"></script>
<script type="text/javascript" src="lib/js/jshighlight/syntax/plaintext.js"></script>
<script type="text/javascript" src="lib/js/jshighlight/syntax.pak.js"></script>
*/

if(typeof(SmartJS_Custom_Syntax_Highlight) != 'function') { // avoid export more than once
	var SmartJS_Custom_Syntax_Highlight = (selector) => { // var, global export
		const $ = jQuery;
		setTimeout(() => {
			$(String(selector) + ' pre code').each((i, el) => {
				let $el = $(el);
				let theClass = $el.attr('class');
				if((theClass != undefined) && (theClass != '')) {
					$el.attr('title', 'Syntax: ' + String(theClass));
					try {
						hljs.highlightBlock(el);
					} catch(err) {
						console.error('ERR: SmartJS_Custom_Syntax_Highlight Failed to instantiate on selector #' + i + ' @ for css class: ' + String(theClass), err);
					} //end try catch
				} //end if
				$el = null;
				theClass = null;
			});
		}, 50);
	}
} // END FUNCTION

// #END
