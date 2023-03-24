[%%%COMMENT%%%]
	// This is a Javascript template and will be used for inline Javascript into a HTML on*=""
	// DO NOT ADD Javascript or HTML Comments here or you may break this code when executing in HTML on*="" context
	// Partial Template: Plugins.HTMLEditorCallBack (Js) r.20230123
[%%%/COMMENT%%%]
jQuery(() => {
	const url = '[###URL|js###]';
	if([###IS_POPUP|bool###]) {
		if(window.opener) {
			window.opener.CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(url);
		} else {
			parent.CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(url);
		}
		smartJ$Browser.CloseModalPopUp();
		return false;
	} else {
		return CLEditor_SmartFrameworkComponents_fileBrowserCallExchange(url);
	}
});
