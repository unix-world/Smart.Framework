/*
highlight v5 - JS
	Highlights arbitrary terms.
Johann Burkard - <http://johannburkard.de/blog/programming/javascript/highlight-javascript-text-higlighting-jquery-plugin.html>
	MIT license
modified by unixman (180531)
	- allow custom css style class
	- allow skip classes to be able to use multi highlights
	- fix redundant nested highlights
*/

jQuery.fn.highlight = function(pat, cssclass, skipCssClass) {

	if((typeof cssclass != 'undefined') && (cssclass != '') && (cssclass !== '') && (cssclass != null)) {
		cssclass = String(cssclass); // force string
	} else {
		cssclass = 'jqtxthighlight'; // init
	} //end if else

	if(!Array.isArray(skipCssClass)) {
		skipCssClass = []; // init
	} //end if else

	function innerHighlight(node, pat, isSkip) {
		var skip = 0;
		if((node.nodeType == 3) && (isSkip !== true)) {
			var pos = node.data.toUpperCase().indexOf(pat);
			pos -= (node.data.substr(0, pos).toUpperCase().length - node.data.substr(0, pos).length);
			if(pos >= 0) {
				var spannode = document.createElement('span');
				spannode.className = cssclass;
				var middlebit = node.splitText(pos);
				var endbit = middlebit.splitText(pat.length);
				var middleclone = middlebit.cloneNode(true);
				spannode.appendChild(middleclone);
				middlebit.parentNode.replaceChild(spannode, middlebit);
				skip = 1;
			} //end if
		} else if(node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
			for(var i = 0; i < node.childNodes.length; ++i) {
				if((node.childNodes[i].nodeType != 3) || (skipCssClass.indexOf(node.className) >= 0)) {
					isSkip = true;
				} //end if
				isSkip = innerHighlight(node.childNodes[i], pat, isSkip);
				i += isSkip;
				isSkip = !!isSkip;
			} //end for
		} //end if else
		return skip;
	} //END FUNCTION

	return this.length && pat && pat.length ? this.each(function() { innerHighlight(this, pat.toUpperCase()); }) : this;

};

jQuery.fn.removeHighlight = function(cssclass) {

	if((typeof cssclass != 'undefined') && (cssclass != '') && (cssclass !== '') && (cssclass != null)) {
		cssclass = String(cssclass); // force string
	} else {
		cssclass = 'jqtxthighlight';
	} //end if else

	return this.find('span.'+cssclass).each(function() {
		this.parentNode.firstChild.nodeName;
		with (this.parentNode) {
			replaceChild(this.firstChild, this);
			normalize();
		}
	}).end();

};

// #END
