<?php
// [LIB - Smart.Framework / Plugins / SpreadSheet Import/Export]
// (c) 2006-2022 unix-world.org - all rights reserved
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
//	* js-ui.inc.htm [smartJ$UI] or an extension
//	* js/jsedithtml 	[cleditor]
//	* js/jseditcode 	[codemirror]
//	* js/jshilitecode 	[prism]
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartViewHelpers - Easy to use HTML ViewHelper Components.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20221226
 * @package 	Application:Plugins:ViewComponents
 *
 */
final class SmartViewHtmlHelpers {

	// ::


	//================================================================
	public static function html_js_preview_iframe($yid, $y_contents, $y_width='720px', $y_height='300px', $y_maximized=false, $y_sandbox='allow-popups allow-same-origin') {
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
	public static function html_jsload_hilitecodesyntax($dom_selector, $theme='', $use_absolute_url=false) {
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
	public static function html_jsload_editarea(bool $y_use_absolute_url=false, ?array $custom_themes=[]) {
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
	public static function html_js_editarea(?string $yid, ?string $yvarname, ?string $yvalue='', ?string $y_mode='text', bool $y_editable=true, ?string $y_width='720px', ?string $y_height='300px', bool $y_line_numbers=true, ?string $custom_theme='') {
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
	 * Outputs the HTML Code to init the HTML (wysiwyg) Editor
	 *
	 * @param $y_filebrowser_link 	STRING 		URL to Image Browser (Example: script.php?op=image-gallery&type=images)
	 * @param $y_styles 			ENUM 		Can be '' or 'a/path/to/styles.css'
	 * @param $y_use_absolute_url 	BOOL 		If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 *
	 * @return STRING							[HTML Code]
	 */
	public static function html_jsload_htmlarea($y_filebrowser_link='', $y_stylesheet='', $y_use_absolute_url=false) {
		//--
		if($y_use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) SmartUtils::get_server_current_url();
		} //end if else
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/html-editor-init.inc.htm',
			[
				'LANG' 						=> (string) SmartTextTranslations::getLanguage(),
				'HTMED-PREFIX-URL' 			=> (string) $the_abs_url,
				'STYLESHEET' 				=> (string) $y_stylesheet,
				'FILE-BROWSER-CALLBACK-URL' => (string) $y_filebrowser_link
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draw a TextArea with a built-in javascript HTML (wysiwyg) Editor
	 *
	 * @param STRING $yid					[Unique HTML Page Element ID]
	 * @param STRING $yvarname				[HTML Form Variable Name]
	 * @param STRING $yvalue				[HTML Data]
	 * @param INTEGER+ $ywidth				[Area Width: (Example) 720px or 75%]
	 * @param INTEGER+ $yheight				[Area Height (Example) 480px or 50%]
	 * @param BOOLEAN $y_allow_scripts		[Allow JavaScripts]
	 * @param BOOLEAN $y_allow_script_src	[Allow JavaScript SRC attribute]
	 * @param MIXED $y_cleaner_deftags 		['' or array of HTML Tags to be allowed / dissalowed by the cleaner ... see HTML Cleaner Documentation]
	 * @param ENUM $y_cleaner_mode 			[HTML Cleaner mode for defined tags: ALLOW / DISALLOW]
	 * @param STRING $y_toolbar_ctrls		[Toolbar Controls: ... see CLEditor Documentation]
	 *
	 * @return STRING						[HTML Code]
	 *
	 */
	public static function html_js_htmlarea($yid, $yvarname, $yvalue='', $ywidth='720px', $yheight='480px', $y_allow_scripts=false, $y_allow_script_src=false, $y_cleaner_deftags='', $y_cleaner_mode='', $y_toolbar_ctrls='') {
		//--
		if((string)$y_cleaner_mode != '') {
			if((string)$y_cleaner_mode !== 'DISALLOW') {
				$y_cleaner_mode = 'ALLOW';
			} //end if
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/html-editor-draw.inc.htm',
			[
				'TXT-AREA-ID' 					=> (string) $yid, // HTML or JS ID
				'TXT-AREA-VAR-NAME' 			=> (string) $yvarname, // HTML variable name
				'TXT-AREA-WIDTH' 				=> (string) $ywidth, // 100px or 100%
				'TXT-AREA-HEIGHT' 				=> (string) $yheight, // 100px or 100%
				'TXT-AREA-CONTENT' 				=> (string) $yvalue,
				'TXT-AREA-ALLOW-SCRIPTS' 		=> (bool)   $y_allow_scripts, // boolean
				'TXT-AREA-ALLOW-SCRIPT-SRC' 	=> (bool)   $y_allow_script_src, // boolean
				'CLEANER-REMOVE-TAGS' 			=> (string) Smart::json_encode($y_cleaner_deftags), // mixed, will be json encoded in tpl
				'CLEANER-MODE-TAGS' 			=> (string) $y_cleaner_mode,
				'TXT-AREA-TOOLBAR' 				=> (string) $y_toolbar_ctrls
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns HTML / JS code for CallBack Mapping for HTML (wysiwyg) Editor - FileBrowser Integration
	 *
	 * @param STRING $yurl					The Callback URL
	 * @param BOOLEAN $is_popup 			Set to True if Popup (incl. Modal)
	 *
	 * @return STRING						[JS Code]
	 */
	public static function html_js_htmlarea_fm_callback($yurl, $is_popup=false) {
		//--
		return (string) str_replace(array("\r\n", "\r", "\n", "\t"), array(' ', ' ', ' ', ' '), (string)SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/html-editor-fm-callback.inc.js',
			[
				'IS_POPUP' 	=> (bool)   $is_popup,
				'URL' 		=> (string) $yurl
			],
			'yes' // export to cache
		));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draws a HTML JS-UI Date Selector Field
	 *
	 * @param STRING 	$y_id					[HTML page ID for field (unique) ; used foor JavaScript]
	 * @param STRING 	$y_var					[HTML Variable Name or empty if no necessary]
	 * @param DATE 		$yvalue					[DATE, empty or formated as YYYY-MM-DD]
	 * @param STRING 	$y_text_select			[The text as title: 'Select Date']
	 * @param JS-Date 	$yjs_mindate			[JS Expression, Min Date] :: new Date(1937, 1 - 1, 1) or '-1y -1m -1d'
	 * @param JS-Date 	$yjs_maxdate			[JS Expression, Max Date] :: new Date(2037, 12 - 1, 31) or '1y 1m 1d'
	 * @param ARRAY 	$y_extra_options		[Options Array[width, ...] for for datePicker]
	 * @param JS-Code 	$y_js_evcode			[JS Code to execute on Select(date)]
	 *
	 * @return STRING 							[HTML Code]
	 */
	public static function html_js_date_field($y_id, $y_var, $yvalue, $y_text_select='', $yjs_mindate='', $yjs_maxdate='', array $y_extra_options=[], $y_js_evcode='') {
		//-- v.20200605
		if((string)$yvalue != '') {
			$yvalue = date('Y-m-d', @strtotime($yvalue)); // enforce this date format for internals and be sure is valid
		} //end if
		//--
		$y_js_evcode = (string) trim((string)$y_js_evcode);
		//--
		if((int)Smart::get_from_config('regional.calendar-week-start') == 1) {
			$the_first_day = 1; // Calendar Start on Monday
		} else {
			$the_first_day = 0; // Calendar Start on Sunday
		} //end if else
		//--
		if(!is_array($y_extra_options)) {
			$y_extra_options = array();
		} //end if
		//--
		if((!array_key_exists('format', $y_extra_options)) OR ((string)$y_extra_options['format'] == '')) {
			$the_altdate_format = (string) SmartTextTranslations::getDateFormatForJs((string)Smart::get_from_config('regional.calendar-date-format-client'));
		} else {
			$the_altdate_format = (string) SmartTextTranslations::getDateFormatForJs((string)$y_extra_options['format']);
		} //end if else
		//--
		if((!array_key_exists('width', $y_extra_options)) OR ((string)$y_extra_options['width'] == '')) {
			$the_option_size = '85';
		} else {
			$the_option_size = (string) $y_extra_options['width'];
		} //end if
		$the_option_size = (float) $the_option_size;
		if($the_option_size >= 1) {
			$the_option_size = ' width:'.((int)$the_option_size).'px;';
		} elseif($the_option_size > 0) {
			$the_option_size = ' width:'.($the_option_size * 100).'%;';
		} else {
			$the_option_size = '';
		} //end if else
		//--
		if((string)$yjs_mindate == '') {
			$yjs_mindate = 'null';
		} //end if
		if((string)$yjs_maxdate == '') {
			$yjs_maxdate = 'null';
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/ui-picker-date.inc.htm',
			[
				'LANG' 				=> (string) SmartTextTranslations::getLanguage(),
				'THE-ID' 			=> (string) $y_id,
				'THE-VAR' 			=> (string) $y_var,
				'THE-VALUE' 		=> (string) $yvalue,
				'TEXT-SELECT' 		=> (string) $y_text_select,
				'ALT-DATE-FORMAT' 	=> (string) $the_altdate_format,
				'STYLE-SIZE' 		=> (string) $the_option_size,
				'FDOW' 				=> (int)    $the_first_day, // of week
				'DATE-MIN' 			=> (string) $yjs_mindate,
				'DATE-MAX' 			=> (string) $yjs_maxdate,
				'EVAL-JS' 			=> (string) $y_js_evcode
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draws a HTML JS-UI Time Selector Field
	 *
	 * @param STRING 	$y_id					[HTML page ID for field (unique) ; used foor JavaScript]
	 * @param STRING 	$y_var					[HTML Variable Name]
	 * @param HH:ii 	$yvalue					[TIME, pre-definned value, formated as 24h HH:ii]
	 * @param STRING 	$y_text_select			[The text for 'Select Time']
	 * @param 0..22 	$y_h_st					[Starting Time]
	 * @param 1..23 	$y_h_end				[Ending Time]
	 * @param 0..58 	$y_i_st					[Starting Minute]
	 * @param 1..59 	$y_i_end				[Ending Minute]
	 * @param 1..30 	$y_i_step				[Step of Minutes]
	 * @param INTEGER 	$y_rows 				[Default is 2]
	 * @param JS-Code 	$y_extra_options		[Options Array[width, ...] for timePicker]
	 * @param JS-Code 	$y_js_evcode			[JS Code to execute on Select(time)]
	 *
	 * @return STRING 							[HTML Code]
	 */
	public static function html_js_time_field($y_id, $y_var, $yvalue, $y_text_select='', $y_h_st='0', $y_h_end='23', $y_i_st='0', $y_i_end='55', $y_i_step='5', $y_rows='2', array $y_extra_options=[], $y_js_evcode='') {
		//-- v.20200605
		if((string)$yvalue != '') {
			$yvalue = date('H:i', @strtotime(date('Y-m-d').' '.$yvalue)); // enforce this time format for internals and be sure is valid
		} //end if
		//--
		$y_js_evcode = (string) trim((string)$y_js_evcode);
		//--
		$prep_hstart = Smart::format_number_int($y_h_st, '+');
		$prep_hend = Smart::format_number_int($y_h_end, '+');
		$prep_istart = Smart::format_number_int($y_i_st, '+');
		$prep_iend = Smart::format_number_int($y_i_end, '+');
		$prep_iinterv = Smart::format_number_int($y_i_step, '+');
		$prep_rows = Smart::format_number_int($y_rows, '+');
		//--
		if(!is_array($y_extra_options)) {
			$y_extra_options = array();
		} //end if
		if((!array_key_exists('width', $y_extra_options)) OR ((string)$y_extra_options['width'] == '')) {
			$the_option_size = '50';
		} else {
			$the_option_size = (string) $y_extra_options['width'];
		} //end if
		$the_option_size = (float) $the_option_size;
		if($the_option_size >= 1) {
			$the_option_size = ' width:'.((int)$the_option_size).'px;';
		} elseif($the_option_size > 0) {
			$the_option_size = ' width:'.($the_option_size * 100).'%;';
		} else {
			$the_option_size = '';
		} //end if else
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/ui-picker-time.inc.htm',
			[
				'LANG' 			=> (string) SmartTextTranslations::getLanguage(),
				'THE-ID' 		=> (string) $y_id,
				'THE-VAR' 		=> (string) $y_var,
				'THE-VALUE' 	=> (string) $yvalue,
				'TEXT-SELECT' 	=> (string) $y_text_select,
				'STYLE-SIZE' 	=> (string) $the_option_size,
				'H-START' 		=> (int)    $prep_hstart,
				'H-END' 		=> (int)    $prep_hend,
				'MIN-START'		=> (int)    $prep_istart,
				'MIN-END' 		=> (int)    $prep_iend,
				'MIN-INTERVAL' 	=> (int)    $prep_iinterv,
				'DISPLAY-ROWS' 	=> (int)    $prep_rows,
				'EVAL-JS' 		=> (string) $y_js_evcode
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Manage a SINGLE Selection HTML List Element for Edit or Display data
	 *
	 * @param STRING			$y_id					the HTML element ID
	 * @param STRING 			$y_selected_value		selected value of the list ; ex: 'id1'
	 * @param ENUM				$y_mode					'form' = display form | 'list' = display list
	 * @param ARRAY				$_yarr_data				DATASET ROWS AS: ['id' => 'name', 'id2' => 'name2'] OR ['id', 'name', 'id2', 'name2']
	 * @param STRING 			$y_varname				as 'frm[test]'
	 * @param INTEGER			$y_dimensions			dimensions in pixels (width or width / (list) height for '#JS-UI#' or '#JS-UI-FILTER#')
	 * @param CODE				$y_custom_js			custom js code (Ex: onsubmit="" or onchange="")
	 * @param YES/NO			$y_raw					If Yes, the description values will not apply html special chars
	 * @param YES/NO			$y_allowblank			If Yes, a blank value is allowed in list
	 * @param STRING 			$y_blank_name 			The name of the blank value ; If none will use empty (nbsp) space
	 * @param CSS/#JS-UI#		$y_extrastyle			Extra CSS Style | or Extra CSS Class 'class:a-css-class' | Visual UI Mode '#JS-UI#' or '#JS-UI-FILTER#'
	 *
	 * @return STRING 									[HTML Code]
	 */
	public static function html_select_list_single($y_id, $y_selected_value, $y_mode, $_yarr_data, $y_varname='', $y_dimensions='150/0', $y_custom_js='', $y_raw='no', $y_allowblank='yes', $y_blank_name='', $y_extrastyle='') {

		//-- fix associative array
		$arr_type = Smart::array_type_test($_yarr_data);
		if($arr_type === 2) { // associative array detected
			$arr_save = (array) $_yarr_data;
			$_yarr_data = array();
			foreach((array)$arr_save as $key => $val) {
				$_yarr_data[] = (string) $key;
				$_yarr_data[] = (string) $val;
			} //end foreach
			$arr_save = array();
		} //end if
		//--

		//--
		$tmp_dimens = (array) explode('/', (string)trim((string)$y_dimensions));
		//--
		$the_width = 0;
		if(array_key_exists(0, $tmp_dimens)) {
			$the_width = (int) isset($tmp_dimens[0]) ? $tmp_dimens[0] : 0;
		} //end if
		$the_height = 0;
		if(array_key_exists(1, $tmp_dimens)) {
			$the_height = (int) isset($tmp_dimens[1]) ? $tmp_dimens[1] : 0;
		} //end if
		//--
		if($the_width < 0) {
			$the_width = 0;
		} //end if
		if($the_width > 0) {
			if($the_width < 50) {
				$the_width = 50;
			} elseif($the_width > 1200) {
				$the_width = 1200;
			} //end if
		} //end if
		//--
		if($the_height < 0) {
			$the_height = 0;
		} //end if
		//--

		//--
		$y_varname = (string) trim((string)$y_varname);
		$y_custom_js = (string) trim((string)$y_custom_js);
		$y_blank_name = (string) trim((string)$y_blank_name);
		//--

		//--
		$element_id = (string) Smart::escape_html((string)Smart::create_htmid((string)trim((string)$y_id)));
		//--

		//--
		$js = '';
		$css_class = '';
		//--
		if(((string)$element_id != '') && (((string)$y_extrastyle == '#JS-UI#') || ((string)$y_extrastyle == '#JS-UI-FILTER#'))) {
			//--
			$tmp_extra_style = (string) $y_extrastyle;
			$y_extrastyle = ''; // reset
			//--
			if((string)$y_mode == 'form') {
				//--
				if($the_width <= 0) {
					$the_width = 150;
				} //end if
				$the_width = $the_width + 20;
				if($the_height > 0) {
					if($the_height < 50) {
						$the_height = 50;
					} //end if
					if($the_height > 200) {
						$the_height = 200;
					} //end if
				} else {
					$the_height = 200; // default
				} //end if else
				//--
				if((string)$tmp_extra_style == '#JS-UI-FILTER#') {
					$have_filter = true;
					$the_width += 25;
				} else {
					$have_filter = false;
				} //end if else
				//--
				$js = (string) SmartMarkersTemplating::render_file_template(
					'lib/core/plugins/templates/ui-list-single.inc.htm',
					[
						'LANG' => (string) SmartTextTranslations::getLanguage(),
						'ID' => (string) $element_id,
						'WIDTH' => (int) $the_width,
						'HEIGHT' => (int) $the_height,
						'HAVE-FILTER' => (bool) $have_filter
					],
					'yes' // export to cache
				);
				//--
			} //end if else
			//--
		} else {
			//--
			if((string)$y_mode == 'form') {
				$css_class = 'class="ux-field';
				if((string)$y_extrastyle != '') {
					$y_extrastyle = (string) trim((string)$y_extrastyle);
					if(stripos($y_extrastyle, 'class:') === 0) {
						$y_extrastyle = (string) trim((string)substr($y_extrastyle, strlen('class:')));
						if((string)$y_extrastyle != '') {
							$css_class .= ' '.Smart::escape_html($y_extrastyle);
						} //end if
						$y_extrastyle = '';
					} //end if
				} //end if else
				$css_class .= '"';
			} //end if
			//--
		} //end if else
		//--

		//--
		$out = '';
		//--
		if((string)$y_mode == 'form') {
			//--
			$out .= '<select '.($y_varname ? 'name="'.Smart::escape_html((string)$y_varname).'" ' : '').($element_id ? 'id="'.$element_id.'" ' : '').'size="1" '.$css_class;
			//--
			$style = [];
			if((int)$the_width > 0) {
				$style[] = 'width:'.(int)$the_width.'px;';
			} //end if
			$y_extrastyle = (string) trim((string)$y_extrastyle);
			if((string)$y_extrastyle != '') {
				$style[] = (string) Smart::escape_html($y_extrastyle);
			} //end if
			//--
			if(Smart::array_size($style) > 0) {
				$out .= ' style="'.implode(' ', $style).'"';
			} //end if
			//--
			if((string)$y_custom_js != '') {
				$out .= ' '.$y_custom_js;
			} //end if
			//--
			$out .= '>'."\n";
			//--
			if((string)$y_allowblank == 'yes') {
				$out .= '<option value="">'.($y_blank_name ? Smart::escape_html($y_blank_name) : '&nbsp;').'</option>'."\n"; // we need a blank value to avoid wrong display of selected value
			} //end if
			//--
		} //end if
		//--
		$found = 0;
		for($i=0; $i<Smart::array_size($_yarr_data); $i++) {
			//--
			$i_key = $i;
			$i_val = $i+1;
			$i=$i+1;
			//--
			if((string)$y_mode == 'form') {
				//--
				$tmp_sel = '';
				//--
				if((strlen($y_selected_value) > 0) AND ((string)$y_selected_value == (string)$_yarr_data[$i_key])) {
					$tmp_sel = ' selected'; // single ID
				} //end if
				//--
				if((string)$y_raw == 'yes') {
					$tmp_desc_val = (string) $_yarr_data[$i_val];
				} else {
					$tmp_desc_val = (string) SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val]));
				} //end if else
				//--
				if(strpos((string)$_yarr_data[$i_key], '#OPTGROUP#') === 0) {
					$out .= '<optgroup label="'.$tmp_desc_val.'">'."\n"; // the optgroup
				} else {
					$out .= '<option value="'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_key])).'"'.$tmp_sel.'>'.$tmp_desc_val.'</option>'."\n";
				} //end if else
				//--
			} else {
				//--
				if(((string)$_yarr_data[$i_val] != '') AND ((string)$y_selected_value == (string)$_yarr_data[$i_key])) {
					//-- single ID
					if((string)$y_raw == 'yes') {
						$out .= (string) $_yarr_data[$i_val]."\n";
					} else {
						$out .= (string) SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val]))."\n";
					} //end if else
					//--
					$found += 1;
					//--
				} //end if
				//--
			} //end if else
			//--
		} //end for
		//--
		if((string)$y_mode == 'form') {
			//--
			$out .= '</select>'."\n";
			//--
			$out .= (string) $js."\n";
			//--
		} else {
			//--
			if($found <= 0) {
				if($y_allowblank != 'yes') {
					$out .= (string) SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html((string)$y_selected_value)).'<sup>?</sup>'."\n";
				} //end if
			} //end if
			//--
		} //end if
		//--

		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a MULTIPLE (many selections) View/Edit List to manage ID Selections
	 *
	 * @param STRING			$y_id					the HTML element ID
	 * @param STRING 			$y_selected_value		selected value(s) data as ARRAY [ 'id1', 'id2' ] or STRING LIST as: '<id1>,<id2>'
	 * @param ENUM				$y_mode					'form' = display form | checkboxes | 'list' = display list
	 * @param ARRAY				$_yarr_data				DATASET ROWS AS: ['id' => 'name', 'id2' => 'name2'] OR ['id', 'name', 'id2', 'name2']
	 * @param STRING 			$y_varname				as 'frm[test][]'
	 * @param ENUM				$y_draw 				list | checkboxes
	 * @param YES/NO 			$y_sync_values			If Yes, sync select similar values used (curently works only for checkboxes)
	 * @param INTEGER			$y_dimensions			dimensions in pixels (width or width / (list) height for '#JS-UI#' or '#JS-UI-FILTER#')
	 * @param CODE				$y_custom_js			custom js code (Ex: submit on change)
	 * @param CSS/#JS-UI#		$y_extrastyle			Extra CSS Style | 'class:a-css-class' | '#JS-UI#' or '#JS-UI-FILTER#'
	 * @param INTEGER 			$y_msize 				Multi List Size (if applicable) ; Default is 8 ; accept values between 2 and 32
	 *
	 * @return STRING 									[HTML Code]
	 */
	public static function html_select_list_multi($y_id, $y_selected_value, $y_mode, $_yarr_data, $y_varname='', $y_draw='list', $y_sync_values='no', $y_dimensions='300/0', $y_custom_js='', $y_extrastyle='#JS-UI-FILTER#', $y_msize=8) {

		//-- fix associative array
		$arr_type = Smart::array_type_test($_yarr_data);
		if($arr_type === 2) { // associative array detected
			$arr_save = (array) $_yarr_data;
			$_yarr_data = array();
			foreach((array)$arr_save as $key => $val) {
				$_yarr_data[] = (string) $key;
				$_yarr_data[] = (string) $val;
			} //end foreach
			$arr_save = array();
		} //end if
		//--

		//-- fix (if only one element show single list, will not apply if checkboxes ...)
		$y_msize = (int) $y_msize;
		if($y_msize < 2) {
			$y_msize = 2;
		} elseif($y_msize > 32) {
			$y_msize = 32;
		} //end if else
		if(Smart::array_size($_yarr_data) > 2) { // to be multi list must have at least 2 values, else make non-sense
			$use_multi_list_ok = true;
			$use_multi_list_htm = 'multiple size="'.(int)$y_msize.'"';
		} else {
			$use_multi_list_ok = false;
			$use_multi_list_htm = 'size="1"';
		} //end if else
		//--

		//--
		$tmp_dimens = (array) explode('/', (string)trim((string)$y_dimensions));
		//--
		$the_width = 0;
		if(array_key_exists(0, $tmp_dimens)) {
			$the_width = (int) isset($tmp_dimens[0]) ? $tmp_dimens[0] : 0;
		} //end if
		$the_height = 0;
		if(array_key_exists(1, $tmp_dimens)) {
			$the_height = (int) isset($tmp_dimens[1]) ? $tmp_dimens[1] : 0;
		} //end if
		//--
		if($the_width < 0) {
			$the_width = 0;
		} //end if
		if($the_width > 0) {
			if($the_width < 50) {
				$the_width = 50;
			} elseif($the_width > 1200) {
				$the_width = 1200;
			} //end if
		} //end if
		//--
		if($the_height < 0) {
			$the_height = 0;
		} //end if
		//--

		//--
		$y_varname = (string) trim((string)$y_varname);
		$y_custom_js = (string) trim((string)$y_custom_js);
		//--

		//--
		$element_id = (string) Smart::escape_html((string)Smart::create_htmid((string)trim((string)$y_id)));
		//--

		//--
		$js = '';
		$css_class = '';
		//--
		if(((string)$element_id != '') && (((string)$y_extrastyle == '#JS-UI#') || ((string)$y_extrastyle == '#JS-UI-FILTER#'))) {
			//--
			$use_blank_value = 'no';
			//--
			$tmp_extra_style = (string) $y_extrastyle;
			$y_extrastyle = ''; // reset
			//--
			if((string)$y_mode == 'form') {
				//--
				if($the_width <= 0) {
					$the_width = 150;
				} //end if
				if($the_height > 0) {
					if($the_height < 50) {
						$the_height = 50;
					} //end if
					if($the_height > 200) {
						$the_height = 200;
					} //end if
				} else {
					$the_height = 90; // default (sync with jQuery Chosen Multi default)
				} //end if else
				//--
				if((string)$tmp_extra_style == '#JS-UI-FILTER#') {
					$have_filter = true;
					$the_width += 25;
				} else {
					$have_filter = false;
				} //end if else
				//--
				if($use_multi_list_ok === false) {
					$use_blank_value = 'yes';
					$have_filter = false; // if multi will be enforced to single because of just 2 rows or less, disable filter !
				} //end if
				//--
				$js = (string) SmartMarkersTemplating::render_file_template(
					'lib/core/plugins/templates/ui-list-multi.inc.htm',
					[
						'LANG' => (string) SmartTextTranslations::getLanguage(),
						'ID' => (string) $element_id,
						'WIDTH' => (int) $the_width,
						'HEIGHT' => (int) $the_height,
						'IS-MULTI' => (bool) $use_multi_list_ok,
						'HAVE-FILTER' => (bool) $have_filter
					],
					'yes' // export to cache
				);
				//--
			} //end if
			//--
		} else {
			//--
			$use_blank_value = 'no';
			if($use_multi_list_ok === false) {
				$use_blank_value = 'yes';
			} //end if
			//--
			if((string)$y_mode == 'form') {
				$css_class = 'class="ux-field';
				if((string)$y_extrastyle != '') {
					$y_extrastyle = (string) trim((string)$y_extrastyle);
					if(stripos($y_extrastyle, 'class:') === 0) {
						$y_extrastyle = (string) trim((string)substr($y_extrastyle, strlen('class:')));
						if((string)$y_extrastyle != '') {
							$css_class .= ' '.Smart::escape_html($y_extrastyle);
						} //end if
						$y_extrastyle = '';
					} //end if
				} //end if else
				$css_class .= '"';
			} //end if
			//--
		} //end if else
		//--

		//--
		$out = '';
		//--
		if((string)$y_mode == 'form') {
			//--
			if((string)$y_draw == 'checkboxes') { // checkboxes
				//--
				$out .= '<input type="hidden" name="'.Smart::escape_html((string)$y_varname).'" value="">'."\n"; // we need a hidden value
				//--
			} else { // list
				//--
				$out .= '<select '.($y_varname ? 'name="'.Smart::escape_html((string)$y_varname).'" ' : '').($element_id ? 'id="'.Smart::escape_html((string)$element_id).'" ' : '').$css_class;
				//--
				$style = [];
				if((int)$the_width > 0) {
					$style[] = 'width:'.(int)$the_width.'px;';
				} //end if
				$y_extrastyle = (string) trim((string)$y_extrastyle);
				if((string)$y_extrastyle != '') {
					$style[] = (string) Smart::escape_html($y_extrastyle);
				} //end if
				//--
				if(Smart::array_size($style) > 0) {
					$out .= ' style="'.implode(' ', $style).'"';
				} //end if
				//--
				if((string)$y_custom_js != '') {
					$out .= ' '.$y_custom_js;
				} //end if
				//--
				$out .= ' '.$use_multi_list_htm.'>'."\n";
				//--
				if((string)$use_blank_value == 'yes') {
					$out .= '<option value="">&nbsp;</option>'."\n"; // we need a blank value to unselect
				} //end if
				//--
			} //end if else
			//--
		} //end if
		//--
		for($i=0; $i<Smart::array_size($_yarr_data); $i++) {
			//--
			$i_key = $i;
			$i_val = $i+1;
			$i=$i+1;
			//--
			if((string)$y_mode == 'form') {
				//--
				$tmp_el_id = 'SmartFrameworkComponents_MultiSelect_ID__'.sha1((string)$y_varname.$_yarr_data[$i_key]);
				//--
				$tmp_sel = '';
				$tmp_checked = '';
				//--
				if(is_array($y_selected_value)) {
					//--
					if(in_array($_yarr_data[$i_key], $y_selected_value)) {
						//--
						$tmp_sel = ' selected';
						$tmp_checked = ' checked';
						//--
					} //end if
					//--
				} else {
					//--
					if(SmartUnicode::str_icontains($y_selected_value, '<'.$_yarr_data[$i_key].'>')) { // multiple categs as <id1>,<id2>
						//--
						$tmp_sel = ' selected';
						$tmp_checked = ' checked';
						//--
					} //end if
					//--
				} //end if
				//--
				if((string)$y_draw == 'checkboxes') { // checkboxes
					//--
					if((string)$y_sync_values == 'yes') {
						$tmp_onclick = ' onClick="try { smartJ$Browser.CheckAllCheckBoxes(this.form.name, \''.Smart::escape_html(Smart::escape_js($tmp_el_id)).'\', this.checked); } catch(err){}"';
					} else {
						$tmp_onclick = '';
					} //end if else
					//--
					$out .= '<input type="checkbox" name="'.Smart::escape_html((string)$y_varname).'" id="'.Smart::escape_html($tmp_el_id).'" value="'.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($_yarr_data[$i_key])).'"'.$tmp_checked.$tmp_onclick.'>';
					$out .= ' &nbsp; '.SmartMarkersTemplating::prepare_nosyntax_html_template(Smart::escape_html($_yarr_data[$i_val])).'<br>';
					//--
				} else { // list
					//--
					if(strpos((string)$_yarr_data[$i_key], '#OPTGROUP#') === 0) {
						$out .= '<optgroup label="'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val])).'">'."\n"; // the optgroup
					} else {
						$out .= '<option value="'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_key])).'"'.$tmp_sel.'>&nbsp;'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val])).'</option>'."\n";
					} //end if else
					//--
				} //end if else
				//--
			} else {
				//--
				if(is_array($y_selected_value)) {
					//--
					if(in_array($_yarr_data[$i_key], $y_selected_value)) {
						//--
						$out .= '&middot;&nbsp;'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val])).'<br>'."\n";
						//--
					} //end if
					//--
				} else {
					//--
					if(SmartUnicode::str_icontains($y_selected_value, '<'.$_yarr_data[$i_key].'>')) {
						//-- multiple categs as <id1>,<id2>
						$out .= '&middot;&nbsp;'.SmartMarkersTemplating::prepare_nosyntax_html_template((string)Smart::escape_html($_yarr_data[$i_val])).'<br>'."\n";
						//--
					} // end if
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end for
		//--
		if((string)$y_mode == 'form') {
			//--
			if((string)$y_draw == 'checkboxes') { // checkboxes
				$out .= '<br>'."\n";
			} else { // list
				$out .= '</select>'."\n";
				$out .= $js."\n";
			} //end if else
			//--
		} //end if
		//--

		//--
		return (string) $out;
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
	public static function html_navpager($link, $total, $limit, $current, $display_if_empty=false, $adjacents=3, array $options=[]) {
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
	} //END FUNCTION
	//================================================================


	//================================================================
	// $link = 'some-script.php?ofs={{{offset}}}';
	private static function html_navpager_type_arrows($tpl, $link, $total, $limit, $current, $display_if_empty=false, $adjacents=3, array $options=[]) {
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
	private static function html_navpager_type_numeric($tpl, $link, $total, $limit, $current, $display_if_empty=false, $adjacents=3, array $options=[]) {
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
	public static function html_js_limited_text_area($y_field_id, $y_var_name, $y_var_value, $y_limit, $y_css_w='125px', $y_css_h='50px', $y_placeholder='', $y_wrap='physical', $y_rawval='no') {
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
	public static function html_selector_true_false($y_var, $y_val) {
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
	public static function html_selector_yes_no($y_var, $y_val) {
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
	 * Function: HTML Form Vars
	 *
	 * @param MIXED $y_var			:: ARRAY or STRING :: PHP variable
	 * @param STRING $y_html_var	:: HTML Variable Name
	 * @return STRING				:: HTML Code
	 */
	public static function html_hidden_formvars($y_var, $y_html_var) {
		//--
		$out = '';
		//--
		$regex_var = '/^([_a-zA-Z0-9])+$/';
		//--
		if(((string)$y_html_var != '') AND (preg_match((string)$regex_var, (string)$y_html_var))) {
			if(is_array($y_var)) { // SYNC VARS
				foreach($y_var as $key => $val) {
					if(((string)$key != '') AND (preg_match((string)$regex_var, (string)$key))) {
						$out .= '<input type="hidden" name="'.Smart::escape_html((string)$y_html_var).'['.Smart::escape_html((string)$key).']" value="'.Smart::escape_html((string)$val).'">'."\n";
					} //end if
				} //end for
			} elseif((string)$y_var != '') {
				$out .= '<input type="hidden" name="'.Smart::escape_html((string)$y_html_var).'" value="'.Smart::escape_html((string)$y_var).'">'."\n";
			} //end if else
		} //end if
		//--
		return (string) $out;
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
	public static function js_code_wnd_redirect($y_redir_url, $delay=-1) {
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
	public static function js_code_wnd_refresh_parent($y_redir_url='') {
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
	public static function js_code_wnd_close_modal_popup($y_delay=-1) {
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
	 * Function: JS Escape Mixed JS Code
	 *
	 * @param STRING $y_jscode		:: The inline JS Code to be escaped
	 * @return STRING				:: JS Code
	 */
	private static function escape_inline_js_mixed_type_code($y_jscode) {
		//--
		$y_jscode = (string) trim((string)$y_jscode);
		//--
		$iscode = false;
		if((string)substr($y_jscode, 0, 11) == 'javascript:') {
			$iscode = true;
			$y_jscode = (string) trim((string)substr((string)$y_jscode, 11)); // javascript explicit prefixed executable code (ex: javascript: some code) ; need to remove out the javascript: part
		} elseif(preg_match('/^\s?function\s?\(/i', (string)$y_jscode)) {
			$iscode = true;
			$y_jscode = (string) $y_jscode; // javascript variable function (ex: function(){ ...})
		} //end if else
		if(($iscode === false) OR ((string)$y_jscode == '')) {
			$y_jscode = (string) "'".Smart::escape_js($y_jscode)."'"; // text or eval code
		} //end if
		//--
		return (string) $y_jscode;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to add (raise) a Growl Notification (sticky or not)
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @param STRING $y_title		:: The Growl Title
	 * @param STRING $y_text		:: The Growl Text
	 * @param STRING $y_image		:: The Growl Image (can be empty)
	 * @param INTEGER+ $y_time 		:: *optional* ; default is 5000 (5 seconds) ; The Display Time in microseconds
	 * @param ENUM $y_sticky 		:: *optional* ; default is 'false' ; If set to 'false' will be not sticky ; if set to 'true' will be sticky
	 * @param STRING $y_class 		:: *optional* the CSS class
	 * @return STRING				:: JS Code
	 */
	public static function js_code_notification_add($y_title, $y_text, $y_image, $y_time=5000, $y_sticky='false', $y_class='') {
		//--
		$y_title 	= (string) self::escape_inline_js_mixed_type_code($y_title);
		$y_text 	= (string) self::escape_inline_js_mixed_type_code($y_text);
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
		return 'smartJ$Browser.GrowlNotificationAdd('.$y_title.', '.$y_text.', \''.Smart::escape_js($y_image).'\', '.(int)$y_time.', '.(string)$y_sticky.', \''.Smart::escape_js((string)$y_class).'\');';
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
	public static function js_code_notification_remove($y_id='') {
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
	public static function js_code_ui_confirm_dialog($y_question_html, $y_ok_jscript_function='', $y_width='', $y_height='', $y_title='?') {
		//--
		return 'smartJ$Browser.ConfirmDialog(\''.Smart::escape_js($y_question_html).'\', \''.Smart::escape_js($y_ok_jscript_function).'\', \''.Smart::escape_js($y_title).'\', '.(int)$y_width.', '.(int)$y_height.');';
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
	public static function js_code_ui_alert_dialog($y_message, $y_ok_jscript_function='', $y_width='', $y_height='', $y_title='!') {
		//--
		return 'smartJ$Browser.AlertDialog(\''.Smart::escape_js($y_message).'\', \''.Smart::escape_js($y_ok_jscript_function).'\', \''.Smart::escape_js($y_title).'\', '.(int)$y_width.', '.(int)$y_height.');';
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
	public static function js_ajax_submit_html_form($y_form_id, $y_script_url, $y_confirm_question='', $y_js_evcode='', $y_js_everrcode='', $y_js_evfailcode='', $y_failalertable=false) {
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
	public static function js_ajax_replyto_html_form($y_status, $y_title, $y_message, $y_redirect_url='', $y_replace_div='', $y_replace_html='', $y_js_evcode='', $y_hide_form_on_success=false) {
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
	public static function js_code_confirm_form_submit($y_question, $y_popuptarget='', $y_width='', $y_height='', $y_force_popup='', $y_force_dims='') {
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
	public static function js_code_init_away_page($y_question='') {
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
	public static function js_code_disable_away_page() {
		//--
		return 'smartJ$Browser.setFlag(\'PageAway\', true);';
		//--
	} //END FUCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init an Input Field with AutoComplete Single
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_init_select_autocomplete_single($y_element_id, $y_script, $y_term_var, $y_min_len=1, $y_js_evcode='') {
		//--
		$y_min_len = Smart::format_number_int($y_min_len, '+');
		if($y_min_len < 1) {
			$y_min_len = 1;
		} elseif($y_min_len > 255) {
			$y_min_len = 255;
		} //end if
		//--
		$y_js_evcode = (string) trim((string)$y_js_evcode);
		//--
		return 'try { smartJ$UI.AutoCompleteField(\'single\', \''.Smart::escape_js((string)$y_element_id).'\', \''.Smart::escape_js((string)$y_script).'\', \''.Smart::escape_js((string)$y_term_var).'\', '.(int)$y_min_len.', \''.Smart::escape_js((string)$y_js_evcode).'\'); } catch(e) { console.log(\'Failed to initialize JS-UI AutoComplete-Single: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init an Input Field with AutoComplete Multi
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_init_select_autocomplete_multi($y_element_id, $y_script, $y_term_var, $y_min_len=1, $y_js_evcode='') {
		//--
		$y_min_len = Smart::format_number_int($y_min_len, '+');
		if($y_min_len < 1) {
			$y_min_len = 1;
		} elseif($y_min_len > 255) {
			$y_min_len = 255;
		} //end if
		//--
		$y_js_evcode = (string) trim((string)$y_js_evcode);
		//--
		return 'try { smartJ$UI.AutoCompleteField(\'multilist\', \''.Smart::escape_js((string)$y_element_id).'\', \''.Smart::escape_js((string)$y_script).'\', \''.Smart::escape_js((string)$y_term_var).'\', '.(int)$y_min_len.', \''.Smart::escape_js((string)$y_js_evcode).'\'); } catch(e) { console.log(\'Failed to initialize JS-UI AutoComplete-Multi: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init a JS-UI Tabs Element
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_uitabs_init($y_id_of_tabs, $y_selected=0, $y_prevent_reload=true) {
		//--
		$y_selected = Smart::format_number_int($y_selected, '+');
		//--
		if($y_prevent_reload === true) {
			$prevreload = 'true';
		} else {
			$prevreload = 'false';
		} //end if else
		//--
		return 'try { smartJ$UI.TabsInit(\''.Smart::escape_js($y_id_of_tabs).'\', '.$y_selected.', '.$prevreload.'); } catch(e) { console.warn(\'Failed to initialize JS-UI Tabs: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Activate/Deactivate JS-UI Tabs Element
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_uitabs_activate($y_id_of_tabs, $y_activate) {
		//--
		if($y_activate === false) {
			$activate = 'false';
		} else {
			$activate = 'true';
		} //end if else
		//--
		return 'try { smartJ$UI.TabsActivate(\''.Smart::escape_js($y_id_of_tabs).'\', '.$activate.'); } catch(e) { console.log(\'Failed to activate JS-UI Tabs: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// end of php code
