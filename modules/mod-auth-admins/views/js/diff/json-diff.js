
// Smart Json Diff
// (c) 2015-2021 unixman r.20210411

// require: json-diff.css

// based on projects:
//		* http://tlrobinson.net/projects/javascript-fun/jsondiff
//		* Javascript Diff Algorithm (MIT license) by John Resig (http://ejohn.org/) with Modifications by Chu Alan "sprite"


var Smart_JsonDiff = new function() { // START CLASS

	// :: static

	var theRootName = '#-.JSON:OBJECT.-#';
	var nodeChanges = 0;

	this.Compare = function(divElement, aValue, bValue) {

		removeAllChildren(divElement); // clear the div

		var objA, objB;

		try {
			objA = JSON.parse(aValue);
		} catch(err) {
			divElement.innerHTML = '<div style="background:#FF3300; color:#FFFFFF; padding:5px;">ERROR Parsing Diff Json-A</div>';
			return;
		} //end try catch

		try {
			objB = JSON.parse(bValue);
		} catch(err) {
			divElement.innerHTML = '<div style="background:#FF3300; color:#FFFFFF; padding:5px;">ERROR Parsing Diff Json-B</div>';
			return;
		} //end try catch

		nodeChanges = 0;
		compareTree(objA, objB, theRootName, divElement, 0);

	} //END FUNCTION


	var compareTree = function(a, b, name, divElement, rootLevel) {
		//--
		var typeA = typeofReal(a);
		var typeB = typeofReal(b);
		//--
		var typeSpanA = document.createElement('span');
		typeSpanA.appendChild(document.createTextNode('(' + typeA + ')'));
		typeSpanA.setAttribute('class', 'smart_json_data_type');
		//--
		var typeSpanB = document.createElement('span');
		typeSpanB.appendChild(document.createTextNode('(' + typeB + ')'));
		typeSpanB.setAttribute('class', 'smart_json_data_type');
		//--
		var aString = (typeA === 'object' || typeA === 'array') ? '' : smartJ$Utils.stripslashes(String(a) + ' ');
		var bString = (typeB === 'object' || typeB === 'array') ? '' : smartJ$Utils.stripslashes(String(b) + ' ');
		//--
		var leafNode = document.createElement('span');
		leafNode.appendChild(document.createTextNode(name));
		//--
		if(a === undefined) {
			nodeChanges++;
			leafNode.setAttribute('class', 'smart_json_added');
			leafNode.setAttribute('title', 'Added');
			leafNode.appendChild(document.createTextNode(': ' + bString));
			leafNode.appendChild(typeSpanB);
		} else if(b === undefined) {
			nodeChanges++;
			leafNode.setAttribute('class', 'smart_json_removed');
			leafNode.setAttribute('title', 'Removed');
			leafNode.appendChild(document.createTextNode(': ' + aString));
			leafNode.appendChild(typeSpanA);
		} else if(typeA !== typeB || (typeA !== 'object' && typeA !== 'array' && aString !== bString)) {
			nodeChanges++;
			leafNode.appendChild(document.createTextNode(': '));
			var leafExt1Node = document.createElement('span');
			leafExt1Node.setAttribute('class', 'smart_json_changed');
			leafExt1Node.setAttribute('title', 'Changed');
			leafExt1Node.innerHTML = '' + Smart_StrDiff.Compare(aString, bString);
			leafExt1Node.appendChild(typeSpanA);
			leafExt1Node.appendChild(typeSpanB);
			leafNode.appendChild(leafExt1Node);
		} else {
			leafNode.appendChild(document.createTextNode(': ' + aString));
			leafNode.appendChild(typeSpanA);
		} //end if else
		//--
		if(typeA === 'object' || typeA === 'array' || typeB === 'object' || typeB === 'array') {
			//--
			var keys = [];
			//--
			for(var i in a) {
				if(a.hasOwnProperty(i)) {
					keys.push(i);
				} //end if
			} //end for
			//--
			for(var i in b) {
				if(b.hasOwnProperty(i)) {
					keys.push(i);
				} //end if
			} //end for
			//--
			keys.sort();
			//--
			var listNode = document.createElement('ul');
			//--
			if(name !== theRootName) {
				var ulClick = document.createElement('span');
				ulClick.setAttribute('title', 'Click to toggle (Open/Close) this node');
				ulClick.setAttribute('class', 'openclose');
				ulClick.innerHTML = '&plusmn;';
				listNode.appendChild(ulClick);
				ulClick.onclick = function(event) {
					if(this.parentElement.getAttribute('class') == 'closed') {
						this.parentElement.setAttribute('class', '');
					} else {
						this.parentElement.setAttribute('class', 'closed');
					} //end if else
				};
			} //end if
			//--
			listNode.appendChild(leafNode);
			//--
			for(var i = 0; i < keys.length; i++) {
				if(keys[i] === keys[i-1]) {
					continue;
				} //end if
				var li = document.createElement('li');
				listNode.appendChild(li);
				var rLevel = 2;
				if(rootLevel === 0) {
					rLevel = 1;
				} //end if
				compareTree(a && a[keys[i]], b && b[keys[i]], keys[i], li, rLevel);
			} //end for
			//--
			if(rootLevel === 1) {
				if(nodeChanges <= 0) {
					listNode.setAttribute('class', 'closed');
				} //end if
			} //end if
			//--
			divElement.appendChild(listNode);
			//--
		} else {
			//--
			divElement.appendChild(leafNode);
			//--
		} //end if else
		//--

		//--
		if(rootLevel <= 1) {
			nodeChanges = 0;
		} //end if
		//--

	} //END FUNCTION


	var removeAllChildren = function(node) {
		//--
		var child;
		//--
		while(child = node.lastChild) {
			node.removeChild(child);
		} //end while
		//--
	} //END FUNCTION


	var isArray = function(value) {
		//--
		return value && typeof value === 'object' && value.constructor === Array;
		//--
	} //END FUNCTION


	var typeofReal = function(value) {
		//--
		return isArray(value) ? 'array' : typeof value;
		//--
	} //END FUNCTION


} //END CLASS


// #END
