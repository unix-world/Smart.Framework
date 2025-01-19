<?php
// [LIB - Smart.Framework / Plugins / SpreadSheet Import/Export]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - View HTML Helpers
// DEPENDS:
//	* Smart::
//	* SmartComponents::
//	* SmartMarkersTemplating::
//	* SmartTextTranslations::
// REQUIRED CSS:
//	* date-time.css
//	* navpager.css
// REQUIRED JS LIBS:
//	* js-base.inc.htm
//	* js/jseditcode 	[codemirror]
//	* js/jshilitecode 	[prism]
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartViewHtmlHelpers - Easy to use HTML ViewHelper Components.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20250107
 * @package 	Application:Plugins:ViewComponents
 *
 */
final class SmartViewHtmlHelpers {

	// ::


	//================================================================
	public static function html_js_preview_iframe(?string $yid, ?string $y_contents, ?string $y_width='720px', ?string $y_height='300px', bool $y_maximized=false, ?string $y_sandbox='allow-popups allow-same-origin') : string {
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/preview-iframe-draw.inc.htm',
			[
				'IFRM-ID' 		=> (string) $yid,
				'WIDTH' 		=> (string) $y_width,
				'HEIGHT' 		=> (string) $y_height,
				'SANDBOX' 		=> (string) $y_sandbox,
				'MAXIMIZED' 	=> (bool)   $y_maximized,
				'CONTENT' 		=> (string) $y_contents
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the HTML / CSS / Javascript code to Load the required Javascripts for the prism.js
	 * If a valid DOM Selector is specified all code areas in that dom selector will be highlighted
	 * This function should not be rendered more than once in a HTML page
	 *
	 * @param STRING 	$dom_selector			A valid jQuery HTML-DOM Selector as container(s) for Pre>Code (see jQuery ...) ; Can be: 'body', '#id-element', ...
	 * @param ENUM 		$theme 					The Visual CSS Theme to Load ; By default is set to '' which loads the default theme ; List of Available Themes: 'light', 'dark'
	 * @param BOOL 		$use_absolute_url 		If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 *
	 * @return STRING							[HTML Code]
	 */
	public static function html_jsload_hilitecodesyntax(?string $dom_selector, ?string $theme='', bool $use_absolute_url=false) : string {
		//--
		if($use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) SmartUtils::get_server_current_url();
		} //end if else
		//--
		$theme = (string) strtolower((string)$theme);
		switch((string)$theme) {
			case 'light':
			case 'dark':
				$theme = (string) $theme;
				break;
			case '':
			default:
				$theme = '';
		} //end switch
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/syntax-highlight-init-and-process.inc.htm',
			[
				'HLJS-PREFIX-URL' 	=> (string) $the_abs_url,
				'CSS-THEME' 		=> (string) ($theme ? '-'.$theme : ''),
				'AREAS-SELECTOR' 	=> (string) $dom_selector,
			]
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the HTML / Javascript code to Load the required Javascripts for the Code Editor (Edit Area).
	 * Should be called just once, before calling one or many ::html_js_editarea()
	 *
	 * @param BOOL 		$y_use_absolute_url 	If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 * @param ARRAY 	$custom_themes 		 	An array with custom themes to load from available themes like [ 'theme1', 'theme2', ... ]
	 *
	 * @return STRING							[HTML Code]
	 */
	public static function html_jsload_editarea(bool $y_use_absolute_url=false, ?array $custom_themes=[]) : string {
		//--
		if($y_use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) SmartUtils::get_server_current_url();
		} //end if else
		//--
		if(!is_array($custom_themes)) {
			$custom_themes = array();
		} //end if
		if(Smart::array_size($custom_themes) > 0) {
			if(Smart::array_type_test($custom_themes) != 1) {
				$custom_themes = array();
			} //end if
		} //end if
		//--
		$arr_available_themes = [ 'ambiance', 'elegant', 'mdn-like', 'neo' ]; // already loaded: [ 'uxm', 'uxw', 'oceanic-next', 'zenburn' ]
		$arr_load_themes = [];
		for($i=0; $i<Smart::array_size($custom_themes); $i++) {
			if(in_array('', (array)$arr_available_themes)) {
				$arr_load_themes[] = (string) $custom_themes[$i];
			} //end if
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/code-editor-init.inc.htm',
			[
				'LANG' 				=> (string) SmartTextTranslations::getLanguage(),
				'CODEED-PREFIX-URL' => (string) $the_abs_url,
				'CODEED-THEMES' 	=> (array)  $arr_load_themes,
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the HTML / Javascript code with a special TextArea with a built-in javascript Code Editor (Edit Area).
	 * Supported syntax parsers: CSS, Javascript, Json, HTML, XML, YAML, Markdown, SQL, PHP, Text (default).
	 *
	 * @param STRING $yid					[Unique HTML Page Element ID]
	 * @param STRING $yvarname				[HTML Form Variable Name]
	 * @param STRING $yvalue				[HTML Data]
	 * @param ENUM $y_mode 					[Parser mode: css, javascript, json, json-ld, html, xml, yaml, markdown, sql, php, ini, shell, go, text, gpg]
	 * @param BOOLEAN $y_editable 			[Editable: true / Not Editable: false]
	 * @param INTEGER+ $ywidth				[Area Width: (Example) 720px or 75%]
	 * @param INTEGER+ $yheight				[Area Height (Example) 480px or 50%]
	 * @param BOOLEAN $y_line_numbers		[Display line numbers: true ; Hide line numbersL false]
	 * @param STRING $custom_theme 			A custom theme to load from the available themes
	 *
	 * @return STRING						[HTML Code]
	 *
	 */
	public static function html_js_editarea(?string $yid, ?string $yvarname, ?string $yvalue='', ?string $y_mode='text', bool $y_editable=true, ?string $y_width='720px', ?string $y_height='300px', bool $y_line_numbers=true, ?string $custom_theme='') : string {
		//--
		switch((string)$y_mode) { // {{{SYNC-SMART-CODEMIRROR-MODES}}}
			case 'json-ld':
				$the_mode = 'application/ld+json';
				break;
			case 'json':
				$the_mode = 'application/json';
				break;
			case 'javascript':
				$the_mode = 'text/javascript';
				break;
			case 'css':
				$the_mode = 'text/css';
				break;
			case 'html':
				$the_mode = 'text/html';
				break;
			case 'xml':
				$the_mode = 'text/xml';
				break;
			case 'markdown':
				$the_mode = 'text/x-markdown';
				break;
			case 'yaml':
				$the_mode = 'text/x-yaml';
				break;
			case 'sql':
				$the_mode = 'text/x-sql';
				break;
			case 'php':
				$the_mode = 'application/x-php';
				break;
			case 'ini': // php ini
				$the_mode = 'text/x-toml';
				break;
			case 'shell':
				$the_mode = 'text/x-sh';
				break;
			case 'go':
				$the_mode = 'text/x-go';
				break;
			case 'gpg':
				$the_mode = 'application/gpg';
				break;
			case 'text':
			default:
				$the_mode = 'text/plain';
		} //end switch
		//--
		if(!$y_editable) {
			$is_readonly = true;
			$attrib_readonly = ' readonly';
			$cursor_blinking = 0;
			$theme = 'uxm';
		} else {
			$is_readonly = false;
			$attrib_readonly = '';
			$cursor_blinking = 530;
			$theme = 'uxw';
		} //end switch
		//--
		$arr_available_themes = [ 'ambiance', 'elegant', 'mdn-like', 'neo', 'uxm', 'uxw', 'oceanic-next', 'zenburn' ];
		//--
		if((string)$custom_theme != '') {
			if(in_array((string)$custom_theme, (array)$arr_available_themes)) {
				$theme = (string) $custom_theme;
			} //end if
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/code-editor-draw.inc.htm',
			[
				'TXT-AREA-ID' 		=> (string) $yid,
				'WIDTH' 			=> (string) $y_width,
				'HEIGHT' 			=> (string) $y_height,
				'SHOW-LINE-NUM' 	=> (bool)   $y_line_numbers,
				'READ-ONLY' 		=> (bool)   $is_readonly,
				'BLINK-CURSOR' 		=> (int)    Smart::format_number_int($cursor_blinking, '+'),
				'CODE-TYPE' 		=> (string) $the_mode,
				'THEME' 			=> (string) $theme,
				'TXT-AREA-VAR-NAME' => (string) $yvarname,
				'TXT-AREA-CONTENT' 	=> (string) $yvalue,
				'TXT-AREA-READONLY'	=> (string) $attrib_readonly,
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a navigation pager
	 * The style of the pager can be set overall in: $configs['nav']['pager'], and can be: arrows or numeric
	 *
	 * @hints				$link = 'some-script.php?ofs={{{offset}}}';
	 *
	 * @return STRING 		[HTML Code]
	 *
	 */
	public static function html_navpager(?string $link, ?int $total, ?int $limit, ?int $current, bool $display_if_empty=false, ?int $adjacents=3, array $options=[]) : string {
		//--
		$styles = '';
		//--
		$navpager_mode = (string) Smart::get_from_config('nav.pager');
		//--
		if(((string)$navpager_mode == 'arrows') OR (strpos((string)$navpager_mode, 'arrows:') === 0)) {
			//--
			if((string)$navpager_mode != 'arrows') { // arrows:path/to/navpager-arrows.inc.htm
				$tpl = trim((string)substr((string)$navpager_mode, 7));
			} else { // arrows
				$styles = '<!-- require: navpager.css -->'."\n";
				$tpl = 'lib/core/plugins/templates/navpager-arrows.inc.htm';
			} //end if else
			//--
			return (string) $styles.self::html_navpager_type_arrows($tpl, $link, $total, $limit, $current, $display_if_empty, $adjacents, $options);
			//--
		} else {
			//--
			if(strpos((string)$navpager_mode, 'numeric:') === 0) { // numeric:path/to/navpager-numeric.inc.htm
				$tpl = trim((string)substr((string)$navpager_mode, 8));
			} else { // pager
				$styles = '<!-- require: navpager.css -->'."\n";
				$tpl = 'lib/core/plugins/templates/navpager-numeric.inc.htm';
			} //end if else
			//--
			return (string) $styles.self::html_navpager_type_numeric($tpl, $link, $total, $limit, $current, $display_if_empty, $adjacents, $options);
			//--
		} //end if else
		//--
		return '<!-- NavPager N/A -->';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// $link = 'some-script.php?ofs={{{offset}}}';
	private static function html_navpager_type_arrows(?string $tpl, ?string $link, ?int $total, ?int $limit, ?int $current, bool $display_if_empty=false, ?int $adjacents=3, array $options=[]) : string {
		//--
		$tpl = (string) $tpl;
		$link = (string) $link;
		$total = Smart::format_number_int($total, '+');
		$limit = Smart::format_number_int($limit, '+');
		$current = Smart::format_number_int($current, '+');
		$display_if_empty = (bool) $display_if_empty;
		$adjacents = Smart::format_number_int($adjacents, '+');
		$options = (array) $options;
		//--
		if($limit <= 0) {
			Smart::log_warning('NavBox ERROR: Limit is ZERO in: '.__CLASS__.'::'.__FUNCTION__.'()');
			return (string) '<!-- Navigation Pager (1) -->[ ERROR: Invalid Navigation Pager: Limit is ZERO ]<!-- #END# Navigation Pager -->';
		} //end if
		//--
		$is_paging = false;
		$orig_total = $total;
		$orig_limit = $limit;
		if(isset($options['nav-mode']) AND ((string)$options['nav-mode'] == 'pages')) { // navigate by page number instead of offset
			$is_paging = true;
			$total = Smart::format_number_int(ceil($total / $limit), '+');
			$current = Smart::format_number_int(ceil($current / $limit), '+');
			$limit = (int) 1;
		} //end if
		$opt_zerolink = '';
		if(isset($options['zero-link']) AND ((string)$options['zero-link'] != '')) {
			if((string)$options['zero-link'] == '@') {
				$options['zero-link'] = (string) str_replace('{{{offset}}}', '', (string)$link);
			} //end if
			$opt_zerolink = (string) $options['zero-link'];
		} //end if
		$opt_emptydiv = '<div>&nbsp;</div>';
		if(array_key_exists('empty-div', $options)) {
			$opt_emptydiv = (string) $options['empty-div'];
		} //end if
		$showfirst = true; // show go first
		if(array_key_exists('show-first', $options) AND ($options['show-first'] === false)) {
			$showfirst = false;
		} //end if
		$showlast = true; // show go last
		if(array_key_exists('show-last', $options) AND ($options['show-last'] === false)) {
			$showlast = false;
		} //end if
		//--
		if($display_if_empty !== true) {
			if(($total <= 0) OR ($total <= $limit)) {
				return (string) '<!-- Navigation Pager (1) '.'T='.Smart::escape_html($total).' ; '.'L='.Smart::escape_html($limit).' ; '.'C='.Smart::escape_html($current).' -->'.$opt_emptydiv.'<!-- hidden, all results are shown (just one page) --><!-- #END# Navigation Pager -->'; // total is zero or lower than limit ; no pagination in this case
			} //end if
		} //end if
		//--
		$translator_core_nav_texts = SmartTextTranslations::getTranslator('@core', 'nav_texts'); // OK.rev2
		//--
		$txt_start 	= (string) $translator_core_nav_texts->text('start');
		$txt_prev 	= (string) $translator_core_nav_texts->text('prev');
		$txt_next 	= (string) $translator_core_nav_texts->text('next');
		$txt_end 	= (string) $translator_core_nav_texts->text('end');
		$txt_listed = (string) $translator_core_nav_texts->text('listed'); // Page
		$txt_res 	= (string) $translator_core_nav_texts->text('res'); // Results
		$txt_empty 	= (string) $translator_core_nav_texts->text('empty'); // No Results
		$txt_of 	= (string) $translator_core_nav_texts->text('of'); // of
		//--
		if($total > 0) {
			//--
			$tmp_lst_min = (int) $current + 1;
			$tmp_lst_max = (int) $current + $limit;
			//--
			$dys_next = (int) $current + $limit;
			$dys_prev = (int) $current - $limit;
			//--
			if($dys_prev < 0) {
				$dys_prev = 0;
			} //end if
			if($dys_prev > $total) {
				$dys_prev = (int) $total;
			} //end if
			//--
			if($dys_next < 0) {
				$dys_next = 0;
			} //end if
			if($dys_next > $total) {
				$dys_next = (int) $total;
			} //end if
			if($dys_next == 0) {
				$dys_prev = 0;
				$tmp_lst_min = 0;
				$tmp_lst_max = 0;
			} //end if
			//-- Fix max nav
			if($tmp_lst_max > $total) {
				$tmp_lst_max = (int) $total;
			} //end if
			//-- FFW
			$tmp_last_calc_pages = (int) floor((($total - 1) / $limit));
			$tmp_lastpage = (int) $tmp_last_calc_pages * $limit;
			//-- REW
			$tmp_firstpage = 0;
			//--
			if((string)$opt_zerolink != '') {
				$tmp_link_nav_start = (string) $opt_zerolink;
			} else {
				$tmp_link_nav_start = (string) str_replace('{{{offset}}}', $tmp_firstpage, $link);
			} //end if else
			if(((string)$opt_zerolink != '') AND ($dys_prev <= 0)) {
				$tmp_link_nav_prev = (string) $opt_zerolink;
			} else {
				$tmp_link_nav_prev = (string) str_replace('{{{offset}}}', $dys_prev, $link);
			} //end if else
			$tmp_link_nav_next = (string) str_replace('{{{offset}}}', $dys_next, $link);
			$tmp_link_nav_end = (string) str_replace('{{{offset}}}', $tmp_lastpage, $link);
			//--
			$tmp_box_nav_start = (string) $tmp_link_nav_start;
			$tmp_box_nav_prev = (string) $tmp_link_nav_prev;
			$tmp_box_nav_next = (string) $tmp_link_nav_next;
			$tmp_box_nav_end = (string) $tmp_link_nav_end;
			//--
			if($current <= 0) { // is at start
				$tmp_box_nav_start = '';
				$tmp_box_nav_prev = '';
			} //end if
			if($tmp_lst_max >= $total) { // is at end
				$tmp_box_nav_next = '';
				$tmp_box_nav_end = '';
			} //end if
			//--
			$tmp_pg_min = ceil($tmp_lst_max / $limit);
			$tmp_pg_max = ceil($total / $limit);
			//--
			if($is_paging) {
				$tmp_res_total 	= (int) $orig_total;
				$tmp_res_min 	= (int) (($tmp_lst_min - 1) * $orig_limit) + 1;
				$tmp_res_max 	= (int) $tmp_lst_min * $orig_limit;
				if($tmp_res_max > $tmp_res_total) {
					$tmp_res_max = $tmp_res_total;
				} //end if
			} else {
				$tmp_res_total 	= (int) $total;
				$tmp_res_min 	= (int) $tmp_lst_min;
				$tmp_res_max 	= (int) $tmp_lst_max;
			} //end if else
			//--
			$html = (string) SmartMarkersTemplating::render_file_template(
				(string) $tpl,
				[
					'NAV-LNK-START' 	=> (string) $tmp_box_nav_start,
					'NAV-LNK-PREV' 		=> (string) $tmp_box_nav_prev,
					'NAV-LNK-NEXT' 		=> (string) $tmp_box_nav_next,
					'NAV-LNK-END' 		=> (string) $tmp_box_nav_end,
					'NAV-TXT-START' 	=> (string) $txt_start,
					'NAV-TXT-PREV' 		=> (string) $txt_prev,
					'NAV-TXT-NEXT' 		=> (string) $txt_next,
					'NAV-TXT-END' 		=> (string) $txt_end,
					'NAV-TXT-LISTED' 	=> (string) $txt_listed,
					'NAV-TXT-EMPTY' 	=> (string) $txt_empty,
					'NAV-TXT-OF' 		=> (string) $txt_of,
					'NAV-TXT-RES' 		=> (string) $txt_res,
					'NAV-RES-MIN' 		=> (string) $tmp_res_min,
					'NAV-RES-MAX' 		=> (string) $tmp_res_max,
					'NAV-RES-TOTAL' 	=> (string) $tmp_res_total,
					'NAV-PG-MIN' 		=> (string) $tmp_pg_min,
					'NAV-PG-MAX' 		=> (string) $tmp_pg_max,
					'NAV-SHOW-FIRST' 	=> (string) ($showfirst ? 'yes' : 'no'),
					'NAV-SHOW-LAST' 	=> (string) ($showlast ? 'yes' : 'no'),
				],
				'yes' // export to cache
			);
			//--
		} else {
			//--
			if($showfirst === false) {
				$txt_start = '&nbsp;';
			} //end if
			if($showlast === false) {
				$txt_end = '&nbsp;';
			} //end if
			//--
			$html = (string) SmartMarkersTemplating::render_file_template(
				(string) $tpl,
				[
					'NAV-LNK-START' 	=> '',
					'NAV-LNK-PREV' 		=> '',
					'NAV-LNK-NEXT' 		=> '',
					'NAV-LNK-END' 		=> '',
					'NAV-TXT-START' 	=> (string) $txt_start,
					'NAV-TXT-PREV' 		=> (string) $txt_prev,
					'NAV-TXT-NEXT' 		=> (string) $txt_next,
					'NAV-TXT-END' 		=> (string) $txt_end,
					'NAV-TXT-LISTED' 	=> (string) $txt_listed,
					'NAV-TXT-EMPTY' 	=> (string) $txt_empty,
					'NAV-TXT-OF' 		=> (string) $txt_of,
					'NAV-TXT-RES' 		=> (string) $txt_res,
					'NAV-RES-MIN' 		=> (string) 0,
					'NAV-RES-MAX' 		=> (string) 0,
					'NAV-RES-TOTAL' 	=> (string) 0,
					'NAV-PG-MIN' 		=> (string) 0,
					'NAV-PG-MAX' 		=> (string) 0,
					'NAV-SHOW-FIRST' 	=> (string) ($showfirst ? 'yes' : 'no'),
					'NAV-SHOW-LAST' 	=> (string) ($showlast ? 'yes' : 'no'),
				],
				'yes' // export to cache
			);
			//--
		} //end if else
		//--
		return (string) $html;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// $link = 'some-script.php?ofs={{{offset}}}';
	private static function html_navpager_type_numeric(?string $tpl, ?string $link, ?int $total, ?int $limit, ?int $current, bool $display_if_empty=false, ?int $adjacents=3, array $options=[]) : string {
		//--
		$tpl = (string) $tpl;
		$link = (string) $link;
		$total = Smart::format_number_int($total, '+');
		$limit = Smart::format_number_int($limit, '+');
		$current = Smart::format_number_int($current, '+');
		$display_if_empty = (bool) $display_if_empty;
		$adjacents = Smart::format_number_int($adjacents, '+');
		$options = (array) $options;
		//--
		if($limit <= 0) {
			Smart::log_warning('NavBox ERROR: Limit is ZERO in: '.__CLASS__.'::'.__FUNCTION__.'()');
			return (string) '<!-- Navigation Pager (2) -->[ ERROR: Invalid Navigation Pager: Limit is ZERO ]<!-- #END# Navigation Pager -->';
		} //end if
		//--
		if(isset($options['nav-mode']) AND ((string)$options['nav-mode'] == 'pages')) { // navigate by page number instead of offset
			$total = Smart::format_number_int(ceil($total / $limit), '+');
			$current = Smart::format_number_int(ceil($current / $limit), '+');
			$limit = (int) 1;
		} //end if
		$opt_zerolink = '';
		if(isset($options['zero-link']) AND ((string)$options['zero-link'] != '')) {
			if((string)$options['zero-link'] == '@') {
				$options['zero-link'] = (string) str_replace('{{{offset}}}', '', (string)$link);
			} //end if
			$opt_zerolink = (string) $options['zero-link'];
		} //end if
		$opt_emptydiv = '<div>&nbsp;</div>';
		if(array_key_exists('empty-div', $options)) {
			$opt_emptydiv = (string) $options['empty-div'];
		} //end if
		$showfirst = true; // show go prev-next
		if(array_key_exists('show-first', $options) AND ($options['show-first'] === false)) {
			$showfirst = false;
		} //end if
		$showlast = true; // show go last
		if(array_key_exists('show-last', $options) AND ($options['show-last'] === false)) {
			$showlast = false;
		} //end if
		//--
		if($display_if_empty !== true) {
			if(($total <= 0) OR ($total <= $limit)) {
				return (string) '<!-- Navigation Pager (2) '.'T='.Smart::escape_html($total).' ; '.'L='.Smart::escape_html($limit).' ; '.'C='.Smart::escape_html($current).' -->'.$opt_emptydiv.'<!-- hidden, all results are shown (just one page) --><!-- #END# Navigation Pager -->'; // total is zero or lower than limit ; no pagination in this case
			} //end if
		} //end if
		//--
		$translator_core_nav_texts = SmartTextTranslations::getTranslator('@core', 'nav_texts'); // OK.rev2
		//--
		if($total > 0) {
			//--
			if($adjacents <= 0) {
				$adjacents = 2; // fix
			} //end if
			//--
			$min = 1;
			//--
			$max = ceil($total / $limit);
			if($max < 1) {
				$max = 1;
			} //end if
			//--
			$info_current = $current;
			$info_max = ($current + $limit);
			if($info_max > $total) {
				$info_max = $total;
			} //end if
			//--
			$crr = ceil($current / $limit) + 1;
			if($crr < $min) {
				$crr = $min;
			} //end if
			if($crr > $max) {
				$crr = $max;
			} //end if
			//--
			$prev = $crr - 1;
			if($prev <= 0) {
				$txt_prev = '';
				$lnk_prev = '';
			} else {
				$txt_prev = (string) $translator_core_nav_texts->text('prev');
				if(((string)$opt_zerolink != '') AND (((int)(($prev-1)*$limit)) <= 0)) {
					$lnk_prev = (string) $opt_zerolink;
				} else {
					$lnk_prev = (string) str_replace('{{{offset}}}', (int)(($prev-1)*$limit), (string)$link);
				} //end if else
			} //end if
			$next = $crr + 1;
			if($next > $max) {
				$txt_next = '';
				$lnk_next = '';
			} else {
				$txt_next = (string) $translator_core_nav_texts->text('next');
				$lnk_next = (string) str_replace('{{{offset}}}', (int)(($next-1)*$limit), (string)$link);
			} //end if
			//--
			$backmin = $crr - $adjacents;
			if($backmin < $min) {
				$backmin = $min;
			} //end if
			$backmax = $crr + $adjacents;
			if($backmax > $max) {
				$backmax = $max;
			} //end if
			//--
			$arr = array();
			for($i=($backmin+1); $i<$backmax; $i++) {
				$arr[(string)$i] = $i;
			} //end for
			//--
			$data = array();
			//--
			if((!isset($arr[(string)$min])) OR ((string)$arr[(string)$min] == '')) {
				if((int)$min === (int)$crr) {
					$data[(string)$min] = 'SELECTED';
				} else {
					if((string)$opt_zerolink != '') {
						$data[(string)$min] = (string) $opt_zerolink;
					} else {
						$data[(string)$min] = (string) str_replace('{{{offset}}}', (int)(($min-1)*$limit), (string)$link);
					} //end if else
				} //end if else
				if(($max > ($adjacents + 1)) AND ((!isset($arr[(string)($min+1)])) OR ((string)$arr[(string)($min+1)] == ''))) {
					$data['.'] = 'DOTS';
				} //end if else
			} //end if
			//--
			foreach($arr as $key => $val) {
				if((int)$val === (int)$crr) {
					$data[(string)$key] = 'SELECTED';
				} else {
					$data[(string)$key] = (string) str_replace('{{{offset}}}', (int)(($val-1)*$limit), (string)$link);
				} //end if else
			} //end foreach
			//--
			if((!isset($arr[(string)$max])) OR ((string)$arr[(string)$max] == '')) {
				if(($max > ($adjacents + 1)) AND ((!isset($arr[(string)($max-1)])) OR ((string)$arr[(string)($max-1)] == ''))) {
					$data['..'] = 'DOTS';
				} else {
					$showlast = true; // fix if on last pages !!
				} //end if else
				if((int)$max === (int)$crr) {
					$data[(string)$max] = 'SELECTED';
				} else {
					$data[(string)$max] = (string) str_replace('{{{offset}}}', (int)(($max-1)*$limit), (string)$link);
				} //end if else
			} //end if
			//--
			$html = (string) SmartMarkersTemplating::render_file_template(
				(string) $tpl,
				[
					'DATA-ARR' 		=> (array) $data,
					'PREV-PAGE' 	=> (string) $txt_prev,
					'PREV-LINK' 	=> (string) $lnk_prev,
					'NEXT-PAGE' 	=> (string) $txt_next,
					'NEXT-LINK' 	=> (string) $lnk_next,
					'TOTAL'			=> (int) $total,
					'LIMIT' 		=> (int) $limit,
					'CURRENT' 		=> (int) $current,
					'SHOW-FIRST' 	=> (string) ($showfirst ? 'yes' : 'no'),
					'SHOW-LAST' 	=> (string) (($showlast || ($current >= ($total - $adjacents - 1))) ? 'yes' : 'no'),
					'NO-RESULTS' 	=> '' // must be empty in this case
				],
				'yes' // export to cache
			);
			//--
		} else {
			//--
			$html = (string) SmartMarkersTemplating::render_file_template(
				(string) $tpl,
				[
					'DATA-ARR' 		=> [],
					'PREV-PAGE' 	=> '',
					'PREV-LINK' 	=> '',
					'NEXT-PAGE' 	=> '',
					'NEXT-LINK' 	=> '',
					'TOTAL'			=> 0,
					'LIMIT' 		=> 0,
					'CURRENT' 		=> 0,
					'SHOW-FIRST' 	=> 'no',
					'SHOW-LAST' 	=> 'no',
					'NO-RESULTS' 	=> (string) $translator_core_nav_texts->text('empty') // must be non-empty in this case
				],
				'yes' // export to cache
			);
			//--
		} //end if else
		//--
		return (string) '<!-- Navigation Pager (2) '.'T='.Smart::escape_html($total).' ; '.'L='.Smart::escape_html($limit).' ; '.'C='.Smart::escape_html($current).' -->'.$html.'<!-- #END# Navigation Pager -->';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: Draw Limited Text Area
	 *
	 */
	public static function html_js_limited_text_area(?string $y_field_id, ?string $y_var_name, ?string $y_var_value, ?string $y_limit, ?string $y_css_w='125px', ?string $y_css_h='50px', ?string $y_placeholder='', ?string $y_wrap='physical', ?string $y_rawval='no') : string {
		//--
		$y_limit = (int) $y_limit; // max characters :: between 100 and 99999
		//--
		if($y_limit < 50) {
			$y_limit = 50;
		} elseif($y_limit > 99999) {
			$y_limit = 99999;
		} //end if
		//--
		if($y_rawval != 'yes') {
			$y_var_value = (string) SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html((string)$y_var_value));
		} //end if
		//--
		if((string)$y_field_id != '') {
			$field = (string) $y_field_id;
		} else { //  no ID, generate a hash
			$fldhash = (string) sha1('Limited Text Area :: '.$y_var_name.' @@ '.$y_limit.' #').'_'.Smart::uuid_10_str();
			$field = (string) '__Fld_TEXTAREA__'.$fldhash.'__NO_Id__';
		} //end if else
		//--
		$placeholder = '';
		if((string)$y_placeholder != '') {
			$placeholder = (string) ' placeholder="'.Smart::escape_html((string)$y_placeholder).'"';
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/limited-text-area.inc.htm',
			[
				'LIMIT-CHARS' 		=> (int)    $y_limit,
				'ID-AREA' 			=> (string) $field,
				'VAR-AREA' 			=> (string) $y_var_name,
				'VAL-AREA-HTML' 	=> (string) $y_var_value, // this is pre-escaped if not raw
				'WRAP-MODE' 		=> (string) $y_wrap,
				'WIDTH' 			=> (string) $y_css_w,
				'HEIGHT' 			=> (string) $y_css_h,
				'PLACEHOLDER-HTML' 	=> (string) $placeholder
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * HTML Selector: TRUE / FALSE
	 * Display True=1/False=0 Selector
	 *
	 * @param STRING $y_var			:: HTML Var Name
	 * @param STRING $y_val			:: '' | '0' | '1'
	 * @return STRING				:: HTML Code
	 */
	public static function html_selector_true_false(?string $y_var, ?string $y_val) : string {
		//--
		$y_var = (string) trim((string)$y_var);
		$y_val = (string) strtolower(trim((string)$y_val));
		//--
		$translator_core_messages = SmartTextTranslations::getTranslator('@core', 'messages'); // OK.rev2
		//--
		$txt_y = (string) $translator_core_messages->text('yes');
		$txt_n = (string) $translator_core_messages->text('no');
		//--
		$code = '?';
		$sel_y = '';
		$sel_n = '';
		if((string)$y_val == '1') {
			$code = (string) Smart::escape_html($txt_y);
			$sel_y = ' checked';
		} else{ // '0' | ''
			$code = (string) Smart::escape_html($txt_n);
			$sel_n = ' checked';
		} //end if
		//--
		if((string)$y_var != '') { // if var is non empty, show radio buttons else show just Yes or No
			$code = SmartMarkersTemplating::render_file_template(
				'lib/core/plugins/templates/html-selector-yntf.inc.htm',
				[
					'TXT-YES' 	=> (string) $txt_y,
					'TXT-NO' 	=> (string) $txt_n,
					'THE-VAR' 	=> (string) $y_var,
					'SEL-YES' 	=> (string) $sel_y,
					'SEL-NO' 	=> (string) $sel_n,
					'VAL-YES' 	=> (string) '1',
					'VAL-NO' 	=> (string) '0'
				],
				'yes' // export to cache
			);
		} //end if else
		//--
		return (string) $code;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * HTML Selector: YES / NO
	 * Display Yes=y/No=n Selector
	 *
	 * @param STRING $y_var			:: HTML Var Name
	 * @param STRING $y_val			:: '' | 'y' | 'n'
	 * @return STRING				:: HTML Code
	 */
	public static function html_selector_yes_no(?string $y_var, ?string $y_val) : string {
		//--
		$y_var = (string) trim((string)$y_var);
		$y_val = (string) strtolower(trim((string)$y_val));
		//--
		$translator_core_messages = SmartTextTranslations::getTranslator('@core', 'messages'); // OK.rev2
		//--
		$txt_y = (string) $translator_core_messages->text('yes');
		$txt_n = (string) $translator_core_messages->text('no');
		//--
		$code = '?';
		$sel_y = '';
		$sel_n = '';
		if((string)$y_val == 'y') {
			$code = (string) Smart::escape_html($txt_y);
			$sel_y = ' checked';
		} else{ // 'n' | ''
			$code = (string) Smart::escape_html($txt_n);
			$sel_n = ' checked';
		} //end if
		//--
		if((string)$y_var != '') { // if var is non empty, show radio buttons else show just Yes or No
			$code = SmartMarkersTemplating::render_file_template(
				'lib/core/plugins/templates/html-selector-yntf.inc.htm',
				[
					'TXT-YES' 	=> (string) $txt_y,
					'TXT-NO' 	=> (string) $txt_n,
					'THE-VAR' 	=> (string) $y_var,
					'SEL-YES' 	=> (string) $sel_y,
					'SEL-NO' 	=> (string) $sel_n,
					'VAL-YES' 	=> (string) 'y',
					'VAL-NO' 	=> (string) 'n'
				],
				'yes' // export to cache
			);
		} //end if else
		//--
		return (string) $code;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Redirect to URL
	 *
	 * @param STRING $y_redir_url	:: URL to redirect page to
	 * @param INTEGER $delay		:: *optional* ; if > 0 will use Delayed Redirect, otherwise Instant Redirect
	 * @return STRING				:: JS Code
	 */
	public static function js_code_wnd_redirect(?string $y_redir_url, ?int $delay=-1) : string {
		//--
		$y_redir_url = (string) $y_redir_url;
		$delay = (int) $delay;
		//--
		if($delay > 0) {
			return 'smartJ$Browser.RedirectDelayedToURL(\''.Smart::escape_js((string)$y_redir_url).'\', '.(int)$delay.');';
		} else {
			return 'smartJ$Browser.RedirectToURL(\''.Smart::escape_js((string)$y_redir_url).'\');';
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Refresh Parent
	 * @param STRING $y_redir_url	:: *optional* URL to redirect by refresh page to
	 * @return STRING				:: JS Code
	 */
	public static function js_code_wnd_refresh_parent(?string $y_redir_url='') : string {
		//--
		$y_redir_url = (string) $y_redir_url;
		//--
		if((string)$y_redir_url != '') {
			return 'smartJ$Browser.RefreshParent(\''.Smart::escape_js((string)$y_redir_url).'\');';
		} else {
			return 'smartJ$Browser.RefreshParent();';
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * (Delayed) close Pop-Up / Modal
	 *
	 * @param INTEGER $y_delay		:: *optional* ; if > 0 will use Delayed Close, otherwise Instant Close
	 * @return STRING				:: JS Code
	 */
	public static function js_code_wnd_close_modal_popup(?int $y_delay=-1) : string {
		//--
		$y_delay = (int) $y_delay; // microseconds
		if($y_delay > 0) {
			return 'smartJ$Browser.CloseDelayedModalPopUp('.(int)$y_delay.');';
		} else {
			return 'smartJ$Browser.CloseDelayedModalPopUp();';
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to add (raise) a Growl Notification (sticky or not)
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @param STRING $y_title		:: The Growl Title (Plain Text)
	 * @param STRING $y_text		:: The Growl Body (HTML code)
	 * @param STRING $y_image		:: The Growl Image (can be empty)
	 * @param INTEGER+ $y_time 		:: *optional* ; default is 5000 (5 seconds) ; The Display Time in microseconds
	 * @param ENUM $y_sticky 		:: *optional* ; default is 'false' ; If set to 'false' will be not sticky ; if set to 'true' will be sticky
	 * @param STRING $y_class 		:: *optional* the CSS class
	 * @return STRING				:: JS Code
	 */
	public static function js_code_notification_add(?string $y_title, ?string $y_text, ?string $y_image, ?string $y_time='5000', ?string $y_sticky='false', ?string $y_class='') : string {
		//--
		if((string)$y_sticky != 'true') {
			$y_sticky = 'false';
		} //end if
		//--
		$y_time = (int) $y_time;
		if($y_time < 1) {
			$y_time = 1; // miliseconds
		} //end if
		//--
		return 'smartJ$Browser.GrowlNotificationAdd('.Smart::escape_js(Smart::escape_html($y_title)).', '.Smart::escape_js($y_text).', \''.Smart::escape_js($y_image).'\', '.(int)$y_time.', '.(string)$y_sticky.', \''.Smart::escape_js((string)$y_class).'\');';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to remove a Growl Notification (sticky or not)
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @param STRING $y_id 			:: *optional* the Growl ID ; default is '' ; if non empty will remove just the Growl that match the ID ; otherwise will remove ALL Growl instances
	 * @return STRING				:: JS Code
	 */
	public static function js_code_notification_remove(?string $y_id='') : string {
		//-- here we take it as raw as this is the name of a JS variable ...
		$y_id = trim((string)$y_id); // (no prepare js string)
		if(!preg_match('/^[a-zA-Z0-9_]+$/', (string)$y_id)) {
			$y_id = '';
		} //end if
		//--
		return 'smartJ$Browser.GrowlNotificationRemove('.$y_id.');';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to init a JS-UI Confirm Dialog
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_ui_confirm_dialog(?string $y_question_html, ?string $y_ok_jscript_function='', ?string $y_width='', ?string $y_height='', ?string $y_title='', ?string $y_type='auto') : string {
		//--
		$y_title = (string) trim((string)$y_title);
		if((string)$y_title == '') {
			$y_title = '?';
		} //end if
		//--
		if((string)$y_type != 'alertable') {
			$y_type = 'auto';
		} //end if
		//--
		return 'smartJ$Browser.ConfirmDialog(\''.Smart::escape_js($y_question_html).'\', \''.Smart::escape_js($y_ok_jscript_function).'\', \''.Smart::escape_js(Smart::escape_html($y_title)).'\', '.(int)$y_width.', '.(int)$y_height.', \''.Smart::escape_js($y_type).'\');';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to init a JS-UI Alert Dialog
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_ui_alert_dialog(?string $y_message, ?string $y_ok_jscript_function='', ?string $y_width='', ?string $y_height='', ?string $y_title='', ?string $y_type='auto') : string {
		//--
		$y_title = (string) trim((string)$y_title);
		if((string)$y_title == '') {
			$y_title = '!';
		} //end if
		//--
		if((string)$y_type != 'alertable') {
			$y_type = 'auto';
		} //end if
		//--
		return 'smartJ$Browser.AlertDialog(\''.Smart::escape_js($y_message).'\', \''.Smart::escape_js($y_ok_jscript_function).'\', \''.Smart::escape_js(Smart::escape_html($y_title)).'\', '.(int)$y_width.', '.(int)$y_height.', \''.Smart::escape_js($y_type).'\');';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to Submit a HTML Form by Ajax
	 * Expects a standardized (json) reply created with SmartViewHtmlHelpers::js_ajax_replyto_html_form()
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @param $y_form_id 			HTML form ID (Example: myForm)
	 * @param $y_script_url 		the php script to post to (Example: admin.php)
	 * @param $y_confirm_question 	if not empty will ask a confirmation question
	 * @param $y_js_evcode			if not empty, the JS Code to execute on SUCCESS answer (before anything else)
	 * @param $y_js_everrcode		if not empty, the JS Code to execute on ERROR answer (before anything else)
	 * @param $y_js_evfailcode		if not empty, the JS Code to execute on REQUEST FAIL answer
	 * @param $y_failalertable 		if set to TRUE will set the fail dialog to 'alertable' instead of 'auto' if alertable is available
	 *
	 * @return STRING				[javascript code]
	 */
	public static function js_ajax_submit_html_form(?string $y_form_id, ?string $y_script_url, ?string $y_confirm_question='', ?string $y_js_evcode='', ?string $y_js_everrcode='', ?string $y_js_evfailcode='', bool $y_failalertable=false) : string {
		//--
		$y_js_evcode = (string) trim((string)$y_js_evcode);
		//--
		$tmp_use_growl = 'auto';
		//--
		$js_post = 'smartJ$Browser.SubmitFormByAjax(\''.Smart::escape_js($y_form_id).'\', \''.Smart::escape_js($y_script_url).'\', \''.Smart::escape_js($tmp_use_growl).'\', \''.Smart::escape_js($y_js_evcode).'\', \''.Smart::escape_js($y_js_everrcode).'\', \''.Smart::escape_js($y_js_evfailcode).'\', '.($y_failalertable === true ? 'true' : 'false').');';
		//--
		if((string)$y_confirm_question != '') {
			$js_post = (string) self::js_code_ui_confirm_dialog($y_confirm_question, (string)$js_post);
		} else {
			$js_post = (string) $js_post;
		} //end if else
		//--
		return (string) $js_post;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Reply back to the HTML Form submited by Ajax by returning a Json answer
	 * Creates a standardized (json) reply for SmartViewHtmlHelpers::js_ajax_submit_html_form()
	 *
	 * NOTICE:
	 * - if OK: and redirect URL have been provided, the replace div is not handled
	 * - if ERROR: no replace div or redirect is handled
	 *
	 * @param 	$y_status 					OK / ERROR
	 * @param 	$y_title 					Dialog Title
	 * @param 	$y_message 					Dialog Message (Optional in the case of Success)
	 * @param 	$y_redirect_url 			**OPTIONAL** URL to redirect on either Success or Error
	 * @param 	$y_replace_div 				**OPTIONAL** The ID of the DIV to Replace on Success
	 * @param 	$y_replace_html 			**OPTIONAL** the HTML Code to replace in DIV on Success
	 * @param 	$y_js_evcode 				**OPTIONAL** the JS EvCode to be executed on either Success or Error (before redirect or Div Replace)
	 * @param 	$y_hide_form_on_success 	**OPTIONAL** if set to TRUE will set the flag to Javascript to hide form on success
	 *
	 * @return STRING				[JSON data string]
	 *
	 */
	public static function js_ajax_replyto_html_form(?string $y_status, ?string $y_title, ?string $y_message, ?string $y_redirect_url='', ?string $y_replace_div='', ?string $y_replace_html='', ?string $y_js_evcode='', bool $y_hide_form_on_success=false) : string {
		//--
		$translator_core_messages = SmartTextTranslations::getTranslator('@core', 'messages'); // OK.rev2
		//--
		if((string)$y_status == 'OK') {
			$y_status = 'OK';
			$button_text = $translator_core_messages->text('ok');
		} else {
			$y_status = 'ERROR';
			$button_text = $translator_core_messages->text('cancel');
		} //end if else
		//--
		return (string) Smart::json_encode([
			'completed'				=> 'DONE',
			'status'				=> (string) $y_status,
			'action'				=> (string) $button_text,
			'title'					=> (string) $y_title,
			'message'				=> (string) $y_message,
			'js_evcode' 			=> (string) $y_js_evcode,
			'redirect'				=> (string) $y_redirect_url,
			'replace_div'			=> (string) $y_replace_div,
			'replace_html'			=> (string) $y_replace_html,
			'hide_form_on_success' 	=> (string) (($y_hide_form_on_success === true) ? 'hide' : ''),
		]);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to Confirm Form Submit by raising a Dialog / Notification (depend on global settings)
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_confirm_form_submit(?string $y_question, ?string $y_popuptarget='', ?string $y_width='', ?string $y_height='', ?string $y_force_popup='', ?string $y_force_dims='') : string {
		//--
		if((string)$y_width != '') {
			$y_width = Smart::format_number_int($y_width, '+');
		} //end if
		if((string)$y_height != '') {
			$y_height = Smart::format_number_int($y_height, '+');
		} //end if
		if((string)$y_force_popup != '') {
			$y_force_popup = Smart::format_number_int($y_force_popup); // this can be -1, 0, 1
		} //end if
		if((string)$y_force_dims != '') {
			$y_force_dims = Smart::format_number_int($y_force_dims, '+'); // 0 or 1
		} //end if
		//--
		return 'smartJ$Browser.confirmSubmitForm(\''.Smart::escape_js($y_question).'\', this.form, \''.Smart::escape_js($y_popuptarget).'\', \''.$y_width.'\', \''.$y_height.'\', \''.$y_force_popup.'\', \''.$y_force_dims.'\'); return false;';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to Init Page-Away Confirmation when trying to leave a page
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_init_away_page(?string $y_question='') : string {
		//--
		$translator_core_js_messages = SmartTextTranslations::getTranslator('@core', 'js_messages'); // OK.rev2
		//--
		if((string)$y_question == '') {
			$y_question = $translator_core_js_messages->text('page_away');
		} //end if else
		if((string)$y_question == '') {
			$y_question = 'Do you want to leave this page ?';
		} //end if else
		//--
		return 'smartJ$Browser.PageAwayControl(\''.Smart::escape_js($y_question).'\');';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the JS Code to Disable Page-Away Confirmation when trying to leave a page
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_disable_away_page() : string {
		//--
		return 'smartJ$Browser.setFlag(\'PageAway\', true);';
		//--
	} //END FUCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// end of php code
