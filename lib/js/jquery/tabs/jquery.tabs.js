
// jQuery Tabs 2.1.1
// (c) 2016-2021 unix-world.org
// inspired from: http://www.jacklmoore.com/notes/jquery-tabs/
// v.20210512

// DEPENDS: jQuery, smartJ$Utils, smartJ$CryptoHash
// REQUIRES-CSS: jquery.tabs.css

//==================================================================
//==================================================================

//================== [ES6]

const SmartSimpleTabs = new class{constructor(){ // STATIC CLASS
	const _N$ = 'SmartSimpleTabs';

	// :: static
	const _C$ = this; // self referencing

	const _p$ = console;

	let SECURED = false;
	_C$.secureClass = () => { // implements class security
		if(SECURED === true) {
			_p$.warn(_N$, 'Class is already SECURED');
		} else {
			SECURED = true;
			Object.freeze(_C$);
		} //end if
	}; //END

	const $ = jQuery; // jQuery referencing

	const _Utils$ = smartJ$Utils;
	const _Crypto$Hash = smartJ$CryptoHash;

	const svgLoaderImg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 32 32" width="32" height="32" fill="#778888" id="loading-spin"><path opacity="0.25" d="M16 0 A16 16 0 0 0 16 32 A16 16 0 0 0 16 0 M16 4 A12 12 0 0 1 16 28 A12 12 0 0 1 16 4"/><path d="M16 0 A16 16 0 0 1 32 16 L28 16 A12 12 0 0 0 16 4z"><animateTransform attributeName="transform" type="rotate" from="0 16 16" to="360 16 16" dur="0.8s" repeatCount="indefinite" /></path></svg><!-- github.com/jxnblk/loading ; License: MIT -->';
	const errLoadTab = 'TAB Loading FAILED: Async Load Timeout or Broken URL ...';
	const errInvalidLoadUrl = 'TAB Loading ERROR: Empty or Invalid URL ...';

	//=======================================

	const initTabs = function(tabs_id, selected=0, prevent_reload=true) {
		//-- input safety
		tabs_id = _Utils$.stringPureVal(tabs_id, true); // +trim
		tabs_id = _Utils$.create_htmid(tabs_id);
		if(tabs_id == '') {
			_p$.warn(_N$, 'initTabs', 'Invalid or Empty Element ID');
			return;
		} //end if
		prevent_reload = !! prevent_reload; // bool
		selected = _Utils$.format_number_int(selected, false);
		if(selected < 0) {
			selected = 0;
		} //end if
		//-- get tabs element
		const $elTab = $('#' + tabs_id);
		$elTab.data('smart-js-elem-type', String(_N$));
		//-- apply tab styles
		$elTab.removeAttr('style').removeAttr('class').addClass('simple_tabs_container').data('tabs-active', 'yes');
		//--
		$elTab.find('ul:first').removeAttr('style').removeAttr('class').addClass('simple_tabs_head').each((idex, elm) => {
			//-- process tabs
			const $tabsLinks = $(elm).find('a').not('#tabs-exclude'); // find tab header links except the ones that have the attribute: id="tabs-exclude"
			let selectedTabById = _Utils$.stringPureVal(location.hash, true); // +trim ; if hash provided, use it to select tab
			//--
			let crrTab = 0;
			$tabsLinks.each((idx, el) => {
				$(el).removeAttr('style').removeAttr('class').addClass('simple_tabs_inactive');
				let CrrHref = _Utils$.stringPureVal($(el).attr('href'), true); // +trim
				if(CrrHref == '') {
					CrrHref = _Utils$.uuid();
					CrrHref =  String('#UNDEF--' + CrrHref + '--' + _Crypto$Hash.sha512(CrrHref));
				} //end if
				let crrHash = '';
				if(_Utils$.stringStartsWith(CrrHref, '#')) {
					crrHash = String(CrrHref);
				} //end if
				if(crrHash) {
					$(crrHash).data('tabs-num', crrTab).data('tabs-mode', 'internal').removeAttr('style').removeAttr('class').addClass('simple_tabs_content').hide();
				} else {
					const crrSha1 = String(tabs_id + '--URL--' + _Crypto$Hash.sha1(CrrHref));
					crrHash = String('#' + crrSha1);
					$(el).attr('href', crrHash);
					$elTab.append('<div id="' + _Utils$.escape_html(crrSha1) + '" class="simple_tabs_content" data-tabs-num="' + _Utils$.escape_html(crrTab) + '" data-tabs-mode="external" data-tabs-loaded="" data-tabs-url="' + _Utils$.escape_html(CrrHref) + '">... loading ...</div>');
					$('#' + crrSha1).hide();
				} //end if
				if(crrTab === selected) {
					if(selectedTabById == '') {
						selectedTabById = String(crrHash); // update selected tab by ID only if not already selected by hash
					} //end if
				} //end if
				crrTab++;
			});
			//-- if the location hash matches one of the links, use that as the active tab ; if no match is found, use the first link as the initial active tab.
			let $activeTab = $($tabsLinks[0]); // by default, select tab 0
			if(selectedTabById != '') {
				$activeTab = $($tabsLinks.filter('[href="' + String(selectedTabById) + '"]')[0] || $tabsLinks[0]);
			} //end if
			$activeTab.addClass('simple_tabs_active');
			displayTabContent($activeTab.attr('href'), prevent_reload);
			//-- bind the click event handler
			$tabsLinks.each((idx, el) => {
				//--
				$(el).click((evt) => {
					//-- prevent the anchor's default click action
					evt.preventDefault();
					//-- test if tabs active
					if($elTab.data('tabs-active') != 'yes') {
						return;
					} //end if
					//-- make the old tab inactive
					$activeTab.removeClass('simple_tabs_active');
					$($activeTab.attr('href')).hide();
					//-- update the selected tab with the new link and content
					$activeTab = $(el);
					$activeTab.addClass('simple_tabs_active');
					//-- activate tab
					displayTabContent($activeTab.attr('href'), prevent_reload);
					//--
				});
				//--
			});
			//--
		});
		//--
		return $elTab;
		//--
	} //END FUNCTION
	_C$.initTabs = initTabs; // export

	//=======================================

	const activateTabs = function(tabs_id, activation) {
		//-- input safety
		tabs_id = _Utils$.stringPureVal(tabs_id, true); // +trim
		tabs_id = _Utils$.create_htmid(tabs_id);
		if(tabs_id == '') {
			_p$.warn(_N$, 'activateTabs', 'Invalid or Empty Element ID');
			return;
		} //end if
		activation = !! activation;
		//-- get tabs element
		const $elTab = $('#' + tabs_id);
		//-- apply tab styles
		if(activation === false) {
			$elTab.data('tabs-active', 'no');
		} else {
			$elTab.data('tabs-active', 'yes');
		} //end if else
		//--
		$elTab.find('ul:first').each((ix, emt) => {
			const $links = $(emt).find('a').not('#tabs-exclude'); // find tab header links except the ones that have the attribute: id="tabs-exclude"
			$links.each((idx, el) => {
				if(activation === false) {
					$(el).addClass('simple_tabs_disabled');
				} else {
					$(el).removeClass('simple_tabs_disabled');
				} //end if else
			});
		});
		//--
		return $elTab;
		//--
	}; //END
	_C$.activateTabs = activateTabs; // export

	//=======================================

	// #PRIVATES#

	//=======================================

	const AjaxRequestGetURL = (y_url) => { // ES6
		//--
		const _m$ = 'AjaxRequestGetURL';
		//--
		y_url = _Utils$.stringPureVal(y_url, true); // +trim
		if(y_url == '') {
			_p$.error(_N$, _m$, 'WARN:', 'Empty URL');
			return null;
		} //end if
		if((y_url == '#') || (_Utils$.stringStartsWith(y_url, '#'))) {
			_p$.error(_N$, _m$, 'WARN:', 'Invalid URL:', y_url);
			return null;
		} //end if
		//--
		const ajxOpts = {
			async: 			true,
			type: 			'GET',
			url: 			String(y_url),
			contentType: 	false,
			processData: 	false,
			data: 			null,
			dataType: 		'html',
		};
		//--
		return $.ajax(ajxOpts);
		//--
	}; // END
	// no export

	//=======================================

	const getLoaderImg = () => String('data:image/svg+xml,' + _Utils$.escape_url(svgLoaderImg)); // ES6

	//=======================================

	const displayTabContent = function(the_hash, prevent_reload) { // ES6
		//--
		const _m$ = 'displayTabContent';
		//--
		the_hash = _Utils$.stringPureVal(the_hash, true); // +trim
		if(the_hash == '') {
			_p$.warn(_N$, _m$, 'WARN:', 'Empty Hash');
		} //end of
		//--
		prevent_reload = !! prevent_reload; // bool
		//--
		const $tabContent = $(the_hash);
		//--
		if($tabContent.data('tabs-mode') == 'external') {
			//--
			$tabContent.show();
			//--
			if($tabContent.data('tabs-url') != '') {
				//--
				if(($tabContent.data('tabs-loaded') != 'yes') || (prevent_reload !== true)) {
					//--
					const imgLoader = '<img src="' + _Utils$.escape_html(getLoaderImg()) + '" alt="... loading TAB content ...">';
					//--
					$tabContent.empty().html('<div class="simple_tabs_loader">' + imgLoader + '</div>');
					//--
					setTimeout(() => {
						const ajx = AjaxRequestGetURL($tabContent.data('tabs-url'));
						if(ajx !== null) {
							ajx.done((msg) => { // instead of .success() (which is deprecated or removed from newest jQuery)
								$tabContent.data('tabs-loaded', 'yes').empty().html(_Utils$.stringPureVal(msg));
							}).fail((msg) => { // instead of .error() (which is deprecated or removed from newest jQuery)
								_p$.warn(_N$, _m$, 'WARN:', errLoadTab, 'Tab:', the_hash);
								$(the_hash).empty().html('<div class="simple_tabs_load_err">' + _Utils$.escape_html(errLoadTab) + '</div>');
							});
						} else {
							_p$.warn(_N$, _m$, 'WARN:', errInvalidLoadUrl, 'Tab:', the_hash);
							$(the_hash).empty().html('<div class="simple_tabs_load_err">' + _Utils$.escape_html(errInvalidLoadUrl) + '</div>');
						} //end if else
					}, 500);
					//--
				} //end if else
				//--
			} //end if
			//--
		} else if($tabContent.data('tabs-mode') == 'internal') {
			//--
			$tabContent.show();
			//--
		} else {
			//--
			_p$.warn(_N$, _m$, 'WARN:', 'Failed to activate Tab:', the_hash, '# Data-Mode:', $tabContent.data('tabs-mode'))
			//--
		} //end if else
		//--
	}; //END
	// no export

	//=======================================

}}; //END CLASS

SmartSimpleTabs.secureClass(); // implements class security

window.SmartSimpleTabs = SmartSimpleTabs; // global export

//==================================================================
//==================================================================

// END
