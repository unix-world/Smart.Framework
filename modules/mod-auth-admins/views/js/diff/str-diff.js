
// Smart Str(ing) Diff
// (c) 2015-2021 unix-world.org # r.20210411


var Smart_StrDiff = new function() { // START CLASS

	// :: static


	this.Compare = function(strOld, strNew) {
		//--
		return diffString(String(strOld), String(strNew)); // outputs HTML
		//--
	} //END FUNCTION


	// the code below is based on Javascript Diff Algorithm, By John Resig
	// http://ejohn.org/projects/javascript-diff-algorithm/
	// Modified by Chu Alan "sprite"
	// Released under the MIT license.
	// modified by unix-world.org


	function diffString(o, n) {

		// fix for the 1st line to make things simpler and compare the changes also on 1st line by making it identical
		o = '\n' + smartJ$Utils.stringTrim(o);
		n = '\n' + smartJ$Utils.stringTrim(n);

		o = o.replace(/\s+$/, '');
		n = n.replace(/\s+$/, '');

		var out = diffRun(o == '' ? [] : o.split(/\s+/), n == '' ? [] : n.split(/\s+/) );
		var str = '';

		var oSpace = o.match(/\s+/g);
		if(oSpace == null) {
			oSpace = ['\n'];
		} else {
			oSpace.push('\n');
		} //end if else
		var nSpace = n.match(/\s+/g);
		if(nSpace == null) {
			nSpace = ['\n'];
		} else {
			nSpace.push('\n');
		} //end if else

		if(out.n.length == 0) {
			for(var i = 0; i < out.o.length; i++) {
				str += '<del>' + smartJ$Utils.escape_html(out.o[i]) + '</del>' + oSpace[i];
			} //end for
		} else {
			if(out.n[0].text == null) {
				for(n = 0; n < out.o.length && out.o[n].text == null; n++) {
					str += '<del>' + smartJ$Utils.escape_html(out.o[n]) + '</del>' + oSpace[n];
				} //end for
			} //end if
			for(var i = 0; i < out.n.length; i++ ) {
				if(out.n[i].text == null) {
					str += '<ins>' + smartJ$Utils.escape_html(out.n[i]) + '</ins>' + nSpace[i];
				} else {
					var pre = '';
					for(n = out.n[i].row + 1; n < out.o.length && out.o[n].text == null; n++ ) {
						pre += '<del>' + smartJ$Utils.escape_html(out.o[n]) + '</del>' + oSpace[n];
					} //end for
					str += ' ' + smartJ$Utils.escape_html(out.n[i].text) + nSpace[i] + pre;
				} //end if else
			} //end for
		} //end if else

			str = smartJ$Utils.stringReplaceAll('</ins>\n<del>', '</ins> <del>', str);
			str = smartJ$Utils.stringReplaceAll('</del>\n<ins>', '</del> <ins>', str);

			str = smartJ$Utils.stringTrim(str);

			return String(str);

	} //END FUNCTION


	var diffRun = function(o, n) {

		var ns = new Object();
		var os = new Object();

		for(var i=0; i<n.length; i++) {
			if(ns[n[i]] == null ) {
				ns[n[i]] = { rows: new Array(), o: null };
			} //end if
			ns[n[i]].rows.push( i );
		} //end for

		for(var i=0; i<o.length; i++) {
			if(os[o[i]] == null) {
				os[o[i]] = { rows: new Array(), n: null };
			} //end if
			os[o[i]].rows.push( i );
		} //end for

		for(var i in ns) {
			if(ns[i].rows.length == 1 && typeof(os[i]) != 'undefined' && os[i].rows.length == 1) {
				n[ ns[i].rows[0] ] = { text: n[ns[i].rows[0]], row: os[i].rows[0] };
				o[ os[i].rows[0] ] = { text: o[os[i].rows[0]], row: ns[i].rows[0] };
			} //end if
		} //end for

		for(var i=0; i<n.length-1; i++) {
			if(n[i].text != null && n[i+1].text == null && n[i].row + 1 < o.length && o[n[i].row+1].text == null && n[i+1] == o[n[i].row+1]) {
				n[i+1] = { text: n[i+1], row: n[i].row+1 };
				o[n[i].row+1] = { text: o[n[i].row+1], row: i+1 };
			} //end if
		} //end for

		for(var i=n.length-1; i>0; i--) {
			if(n[i].text != null && n[i-1].text == null && n[i].row > 0 && o[n[i].row-1].text == null && n[i-1] == o[n[i].row-1]) {
				n[i-1] = { text: n[i-1], row: n[i].row-1 };
				o[n[i].row-1] = { text: o[n[i].row-1], row: i-1 };
			} //end if
		} //end for

		return { o: o, n: n };

	} //END FUNCTION


} //END CLASS


// #END
