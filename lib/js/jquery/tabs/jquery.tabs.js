
// jQuery Tabs 1.2.5
// (c) 2016-2021 unix-world.org
// inspired from: http://www.jacklmoore.com/notes/jquery-tabs/
// v.20210413

// DEPENDS: jQuery, smartJ$Utils, smartJ$CryptoHash, smartJ$Browser
// REQUIRES-CSS: jquery.tabs.css

//==================================================================
//==================================================================

//================== [OK:evcode]


var SmartSimpleTabs = new function() { // START CLASS

//=======================================

this.initTabs = function(tabs_id, prevent_reload, selected) {
	//-- get tabs element
	var theTabsEl = jQuery('#' + tabs_id);
	//-- apply tab styles
	theTabsEl.removeAttr('style').removeAttr('class').addClass('simple_tabs_container').data('tabs-active', 'yes');
	//-- fix from: ' div:first ul:first'
	jQuery('#' + tabs_id + ' ul:first').removeAttr('style').removeAttr('class').addClass('simple_tabs_head').each(function(){
		//-- process tabs
		var jq_exttabs = []; // register external tabs if any
		var jq_links = jQuery(this).find('a').not('#tabs-exclude'); // find tab header links except the ones that have the attribute: id="tabs-exclude"
		var crr_sel_tab_by_id = String(location.hash); // if hash provided, use it to select tab
		var crr_sel_tab_id = 0;
		var crr_tab = 0;
		//--
		jq_links.each(function(idx, el){
			jQuery(this).removeAttr('style').removeAttr('class').addClass('simple_tabs_inactive');
			var crrHash = String(this.hash);
			var CrrHref = String(this.href);
			if(crrHash) {
				jQuery(crrHash).data('tabs-num', crr_tab).data('tabs-mode', 'internal').removeAttr('style').removeAttr('class').addClass('simple_tabs_content').hide();
			} else {
				var crrSha1 = tabs_id + '__AjxExt__' + smartJ$CryptoHash.sha1(CrrHref);
				crrHash = '#' + crrSha1;
				this.href = crrHash;
				jq_exttabs.push([CrrHref, crrSha1]); // build references to external tabs
				theTabsEl.append('<div id="' + crrSha1 + '" class="simple_tabs_content" data-tabs-num="' + crr_tab + '" data-tabs-mode="external" data-tabs-loaded="" data-tabs-url="' + CrrHref + '">... loading ...</div>');
				jQuery('#' + crrSha1).hide();
			} //end if
			if(crr_sel_tab_id === selected) {
				if(!crr_sel_tab_by_id) {
					crr_sel_tab_by_id = crrHash; // update selected tab by ID only if not already selected by hash
				} //end if
			} //end if
			crr_sel_tab_id++;
			crr_tab++;
		});
		//--
		//console.log(jq_exttabs);
		//-- if the location.hash matches one of the links, use that as the active tab ; if no match is found, use the first link as the initial active tab.
		var jq_active = jQuery(jq_links.filter('[href="'+crr_sel_tab_by_id+'"]')[0] || jq_links[0]);
		jq_active.addClass('simple_tabs_active');
		displayTabContent(jq_active[0].hash, prevent_reload);
		//-- bind the click event handler
		jq_links.each(function(idx, el){
			//--
			jQuery(this).click(function(evt){
				//-- prevent the anchor's default click action
				evt.preventDefault();
				//-- test if tabs active
				if(theTabsEl.data('tabs-active') != 'yes') {
					return;
				} //end if
				//-- make the old tab inactive
				jq_active.removeClass('simple_tabs_active');
				jQuery(jq_active[0].hash).hide();
				//-- update the variables with the new link and content
				jq_active = jQuery(this);
				jq_active.addClass('simple_tabs_active');
				//-- activate tab
				displayTabContent(this.hash, prevent_reload);
				//--
			});
			//--
		});
		//--
	});
	//--
	return theTabsEl;
	//--
} //END FUNCTION

//=======================================

this.activateTabs = function(tabs_id, activation) {
	//-- get tabs element
	var theTabsEl = jQuery('#' + tabs_id);
	//-- apply tab styles
	if(activation === false) {
		theTabsEl.data('tabs-active', 'no');
	} else {
		theTabsEl.data('tabs-active', 'yes');
	} //end if else
	//--
	jQuery('#' + tabs_id + ' ul:first').each(function(){
		//--
		var jq_links = jQuery(this).find('a').not('#tabs-exclude'); // find tab header links except the ones that have the attribute: id="tabs-exclude"
		//--
		jq_links.each(function(idx, el){
			//--
			if(activation === false) {
				jQuery(this).addClass('simple_tabs_disabled');
			} else {
				jQuery(this).removeClass('simple_tabs_disabled');
			} //end if else
			//--
		});
		//--
	});
	//--
	return theTabsEl;
	//--
} //END FUNCTION

//=======================================

// #PRIVATES#

//=======================================

var displayTabContent = function(the_hash, prevent_reload) {
	//--
	var jq_content = jQuery(the_hash);
	//--
	if(jq_content.data('tabs-mode') == 'external') {
		//--
		jq_content.show();
		//--
		if(jq_content.data('tabs-url') != '') {
			//--
			if((jq_content.data('tabs-loaded') != 'yes') || (prevent_reload !== true)) {
				//--
				var imgLoader = '';
				if(smartJ$Browser.param_LoaderImg) {
					imgLoader = '<img src="' + smartJ$Utils.escape_html(smartJ$Browser.param_LoaderImg) + '" alt="... loading Tab data ...">';
				} //end if
				jq_content.empty().html('<div class="simple_tabs_loader">' + imgLoader + '</div>');
				//--
				setTimeout(function() {
					var ajx = smartJ$Browser.AjaxRequestFromURL(jq_content.data('tabs-url'), 'GET', 'html');
					ajx.done(function(msg) { // instead of .success() (which is deprecated or removed from newest jQuery)
						jq_content.data('tabs-loaded', 'yes').empty().html(String(msg));
					}).fail(function(msg) { // instead of .error() (which is deprecated or removed from newest jQuery)
						smartJ$Browser.AlertDialog('<h2 style="display:inline;">WARNING: Asyncronous Load Timeout or URL is broken !</h2>', 'jQuery(\'' + the_hash + '\').empty();', 'TAB #' + ' :: ');
					});
				}, 500);
				//--
			} //end if else
			//--
		} //end if
		//--
	} else if(jq_content.data('tabs-mode') == 'internal') {
		//--
		jq_content.show();
		//--
	} else {
		//--
		console.log('Failed to activate Tab: ' + the_hash + ' / Data-Mode: ' + jq_content.data('tabs-mode'));
		//--
	} //end if else
	//--
} //END FUNCTION

//=======================================

} //END CLASS

//==================================================================
//==================================================================

// END
