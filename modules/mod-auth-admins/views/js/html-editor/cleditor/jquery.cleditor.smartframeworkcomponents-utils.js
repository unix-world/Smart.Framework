
// CL Editor - Smart.Framework Utils
// v.20231125
// (c) unixman, iradu@unix-world.org

// returns the area
function Smart_CLEditor_Activate_HTML_AREA(area_id, width, height, allowScripts, allowScriptSrc, tagsDefinition, tagsMode, controls) {
	//--
	var the_area = $('#' + area_id);
	var options = {
		'width': width,
		'height': height
	};
	//--
	if(allowScripts === true) {
		options['removeTags'] = [
			"?xml", "!doctype", "html", "head", "body", "meta", "style", "link",
			"base", "basefont", "dir", "isindex", "menu", "command", "keygen",
			"frame", "frameset", "noframes", "iframe",
			"noscript", "embed", "object", "param"
		];
	} else {
		options['removeTags'] = [
			"?xml", "!doctype", "html", "head", "body", "meta", "style", "link",
			"base", "basefont", "dir", "isindex", "menu", "command", "keygen",
			"frame", "frameset", "noframes", "iframe",
			"noscript", "embed", "object", "param",
			"script"
		];
	} //end if
	if((typeof tagsDefinition != 'undefined') && (tagsDefinition != null) && (tagsDefinition !== '')) {
		if(tagsMode === 'DISALLOW') {
			options['removeTags'] = tagsDefinition;
		} else if(tagsMode === 'ALLOW') {
			options['allowedTags'] = tagsDefinition;
		} else {
			console.log('Invalid Mode for: Smart_CLEditor_Activate_HTML_AREA / tagsMode ; Feature not used ... Value is: '.tagsMode);
		} //end if else
	} //end if
	//--
	if(allowScriptSrc === true) {
		options['allowedAttributes'] = ["script", ["src"]];
	} else {
		options['allowedAttributes'] = [];
	} //end if else
	//--
	if((typeof controls != 'undefined') && (controls != null) && (controls !== '')) {
		options['controls'] = controls;
	} //end if
	//--
	return the_area.cleditor(options)[0];
	//--
} //END FUNCTION

// returns the text
function Smart_CLEditor_Remove_HTML_AREA(area_id) {
	//--
	var tmp_text = '';
	//--
	area_id.execCommand('selectall');
	tmp_text = area_id.selectedText();
	area_id.clear();
	area_id.$area.removeData("cleditor");
	area_id.$main.remove();
	area_id = '';
	//--
	return String(tmp_text);
	//--
} //END FUNCTION

//#END
