<?php
// [LIB - Smart.Framework / Marker-TPL Templating]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Marker-TPL Templating
// DEPENDS:
//	* Smart::
//	* SmartUnicode::
//	* SmartParser::
//	* SmartFileSystem::
//	* SmartFileSysUtils::
//	* SmartPersistentCache::
// 	* SmartFrameworkRegistry::
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

//===== INFO:
// Marker-TPL Templating Engine is a very fast and 100% secure [*] PHP Templating Engine.
// Because the Marker-TPL Templating is rendering the Views by injecting plain strings and data arrays directly into these Views (no PHP code, no re-interpreted PHP code) there is NO SECURITY RISK by injecting malicious PHP code into the Views
// It does support: MARKERS, IF/ELSE, LOOP, INCLUDE syntax.
// Nested identic IF/ELSE or nested identic LOOP syntax must be separed with unique terminators such as: (1), (2), ...
// For IF/ELSE syntax variable order matters for comparison if used inside LOOP ; when comparing a (special context) variable inside a LOOP with another variable (from out of this context), the LOOP context variable must be placed in the left side, otherwise the comparison will fail as the left variable may be evaluated prior the LOOP variable to be initialized ...
// For nested LOOP it only supports max 5 nested levels (combining more levels would be inefficient - because of the exponential structure complexity of context data, such as metadata context that must be replicated)
// 		-_MAXSIZE_- 		The max array index = arraysize ; Available *ONLY* in LOOP
// 		_-MAXCOUNT-_ 		The max iterator of array: arraysize-1 ; Available also in LOOP / IF
// 		-_INDEX_- 			The current array index: 1..arraysize ; Available *ONLY* in LOOP
// 		_-ITERATOR-_		The current array iterator: 0..(arraysize-1) ; Available also in LOOP / IF
// 		_-VAL-_				The current loop value ; Available also in LOOP / IF
// 		_-KEY-_				The current loop key ; Available also in LOOP / IF
// Thus, this limitation must be compensated from the design of input variables.
//===== TECHNICAL REFERENCE:
// Because the recursion patterns are un-predictable, as a template can be rendered in other template in controllers or libs,
// the str_replace() is used internally instead of strtr()
// but with a fix: will replace all values before assign as:
// `[###` => `⁅###¦` ; `###]` => `¦###⁆` ; `[%%%` => `⁅%%%¦` ; `%%%]` => `¦%%%⁆` ; `[@@@` -> `⁅@@@¦` ; `@@@]` -> `¦@@@⁆`
// in order to protect against unwanted or un-predictable recursions / replacements
// On the values will replace `]` with `］` as:
// `[###` => `［###` ; `###]` => `###］` ; `[%%%` => `［%%%` ; `%%%]` => `%%%］` ; `[@@@` -> `［@@@` ; `@@@]` -> `@@@］`
//=====

/**
 * Class: SmartMarkersTemplating - provides a very fast and low footprint templating system: Marker-TPL
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartFileSystem, SmartFileSysUtils
 * @version 	v.20210313
 * @package 	@Core:TemplatingEngine
 *
 */
final class SmartMarkersTemplating { // syntax: r.20210313

	// ::

	private static $MkTplAnalyzeLdDbg = false; 			// flag for template analysis
	private static $MkTplAnalyzeLdRegDbg = array(); 	// registry of template analysis

	private static $MkTplVars = array(); 				// registry of template variables
	private static $MkTplFCount = array(); 				// counter to register how many times a template / sub-template file is read from filesystem (can be used for optimizations)
	private static $MkTplCache = array(); 				// registry of cached template data

	//================================================================
	/**
	 * Analyze a Marker Template String (NO Sub-Templates are loaded)
	 * This is intended for DEVELOPMENT / DEBUG ONLY (never use this in production environments !)
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	STRING										:: The analyze info HTML+JS
	 *
	 */
	public static function analyze_debug_template(string $mtemplate) {
		//--
		return (string) self::analyze_do_debug_template($mtemplate, 'TPL-String');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render Marker File Template (incl. Sub-Templates from Files if any)
	 * This is intended for DEVELOPMENT / DEBUG ONLY (never use this in production environments !)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$y_file_path 					:: The relative path to the Marker-TPL file (partial text/html + markers + *sub-templates*)
	 * @param 	ARRAY 		$y_arr_sub_templates 			:: *Optional* The associative array with the sub-template variables ( @SUB-TEMPLATES@ ) if any
	 *
	 * @return 	STRING										:: The analyze info HTML+JS
	 *
	 */
	public static function analyze_debug_file_template(string $y_file_path, array $y_arr_sub_templates=[]) {
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(SmartFileSysUtils::check_if_safe_path($y_file_path) != 1) {
			return '<h1>{### ERROR: Invalid Marker-TPL File Path ['.Smart::escape_html($y_file_path).'] ###}</h1>';
		} //end if
		if(!SmartFileSystem::is_type_file($y_file_path)) {
			return '<h1>{### ERROR: Invalid Marker-TPL File Type ['.Smart::escape_html($y_file_path).'] ###}</h1>';
		} //end if
		//--
		$y_arr_sub_templates = (array) $y_arr_sub_templates;
		//--
		$mtemplate = (string) self::do_read_template_file_from_fs((string)$y_file_path);
		$original_mtemplate = (string) $mtemplate;
		//-- add TPL START/END to see where it starts load
		$matches = array();
		$pcre = preg_match_all('{\[@@@SUB\-TEMPLATE:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}', (string)$mtemplate, $matches, PREG_SET_ORDER, 0); // FIX: add an extra % to parse also SUB-TPL %vars% # {{{SYNC-TPL-EXPR-SUBTPL}}} :: + %
		if($pcre === false) {
			return '<h1>{### ERROR: '.Smart::escape_html((string)SMART_FRAMEWORK_ERR_PCRE_SETTINGS).'] ###}</h1>';
		} //end if
		//die('<pre>'.Smart::escape_html(print_r($matches,1)).'</pre>');
		for($i=0; $i<Smart::array_size($matches); $i++) {
			$mtemplate = (string) str_replace((string)$matches[$i][0], '⁅***¦SUB-TEMPLATE:'.(string)$matches[$i][1].'(*****INCLUDE:START{*****)¦***⁆'.(string)$matches[$i][0].'⁅***¦SUB-TEMPLATE:'.(string)$matches[$i][1].'(*****}INCLUDE:END*****)¦***⁆', (string)$mtemplate);
		} //end for
		$matches = array();
		//--
		self::$MkTplAnalyzeLdDbg = true; // flag analyze load Sub-Tpls
		//--
		$arr_sub_templates = array();
		if(Smart::array_size($y_arr_sub_templates) > 0) { // if supplied then use it (preffered), never mix supplied with detection else results would be unpredictable ...
			$arr_sub_templates = (array) $y_arr_sub_templates;
		} else { // if not supplied, try to detect
			$arr_sub_templates = (array) self::detect_subtemplates($mtemplate);
		} //end if else
		if(Smart::array_size($arr_sub_templates) > 0) {
			$tpl_basepath = (string) SmartFileSysUtils::add_dir_last_slash(SmartFileSysUtils::get_dir_from_path($y_file_path));
			$mtemplate = (string) self::load_subtemplates('no', (string)$tpl_basepath, (string)$mtemplate, (array)$arr_sub_templates); // load sub-templates before template processing and use caching also for sub-templates if set
			$mtemplate = (string) str_replace(array('⁅@@@¦', '¦@@@⁆'), array('[@@@', '@@@]'), (string)$mtemplate); // FIX: revert protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
		} //end if
		$arr_sub_templates = array();
		//--
		self::$MkTplAnalyzeLdDbg = false; // reset flag to default
		//--
		return (string) self::analyze_do_debug_template($mtemplate, 'Marker-TPL File: '.$y_file_path, $original_mtemplate);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Escape a Marker Template (String Template ; no sub-templates are allowed as this function is intended to pass a template to be rendered via javascript ...)
	 * NOTICE: This kind of escaped templates can be rendered by client-side javascript from a javascript variable in a HTML page using SmartJS_CoreUtils.renderMarkersTpl() function (not all features of the server-side Marker Templating are supported, see the SmartJS_CoreUtils documentation ...)
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string (partial text/html + markers) ; Ex: '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Marker-TPL is empty ; 'no' will add a warning (default)
	 *
	 * @return 	STRING										:: The escaped template (it can be embedded in a javascript variable in a MTPL template to avoid conflicts with existing markers/syntax)
	 *
	 */
	public static function escape_template(string $mtemplate, string $y_ignore_if_empty='no') {
		//--
		$y_ignore_if_empty = (string) $y_ignore_if_empty;
		//--
		$mtemplate = (string) trim((string)$mtemplate);
		//--
		if(((string)$y_ignore_if_empty != 'yes') AND ((string)$mtemplate == '')) {
			//--
			Smart::log_warning('Empty Marker-TPL Escape Content !');
			$mtemplate = '{### Empty Marker-TPL Escape Content. See the ErrorLog for Details. ###}';
			//--
		} //end if
		//--
		return (string) Smart::escape_url((string)$mtemplate);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render Marker Template (String Template ; no sub-templates are allowed as there is no possibility to set a relative path from where to get them)
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string (partial text/html + markers) ; Ex: '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template markers is case insensitive ; Ex: [ 'MARKER1' => 'Value1', 'marker2' => 'Value2', ..., 'MarkerN' => 100 ]
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Marker-TPL is empty ; 'no' will add a warning (default)
	 *
	 * @return 	STRING										:: The parsed template
	 *
	 */
	public static function render_template(string $mtemplate, array $y_arr_vars, string $y_ignore_if_empty='no') {
		//--
		$y_ignore_if_empty = (string) $y_ignore_if_empty;
		//--
		$mtemplate = (string) trim((string)$mtemplate);
		//--
		if(((string)$y_ignore_if_empty != 'yes') AND ((string)$mtemplate == '')) {
			//--
			Smart::raise_error('Empty Marker-TPL Content: '.print_r($y_arr_vars,1));
			return (string) 'ERROR: (301) in '.__CLASS__;
			//--
		} //end if
		//--
		if(!is_array($y_arr_vars)) {
			$y_arr_vars = array();
			Smart::log_warning('Invalid Marker-TPL Data-Set for Template: '.$mtemplate);
		} //end if
		//-- make all keys upper
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / Render ; Ignore if Empty: '.$y_ignore_if_empty,
				'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//-- avoid use the sub-templates array later than this point ... not needed and safer to unset
		if(array_key_exists('@SUB-TEMPLATES@', (array)$y_arr_vars)) {
			unset($y_arr_vars['@SUB-TEMPLATES@']);
		} //end if
		$mtemplate = (string) str_replace(array('[@@@', '@@@]'), array('⁅@@@¦', '¦@@@⁆'), (string)$mtemplate); // finally protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
		//--
		return (string) self::template_renderer((string)$mtemplate, (array)$y_arr_vars);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render Marker File Template (incl. Sub-Templates from Files if any)
	 *
	 * <code>
	 * // [###MARKER###]											:: marker with no escapes
	 * // [###MARKER|{escapes}] 									:: marker with escapes (one or many of): |bool |int |dec[1-4]{1} |num |htmid |jsvar |slug |substr[0-9]{1,5} |subtxt[0-9]{1,5} |lower |upper |ucfirst |ucwords |trim |url |json |js |html |css |nl2br |syntaxhtml
	 * // [@@@SUB-TEMPLATE:path/to/tpl.htm@@@] 				:: sub-template with relative path to template
	 * // [@@@SUB-TEMPLATE:?path/to/tpl.htm@@@] 				:: sub-template with relative path to template, optional, if exists
	 * // [@@@SUB-TEMPLATE:!etc/path/to/tpl.htm!@@@] 		:: sub-template with relative path to framework, using exact this path
	 * // [@@@SUB-TEMPLATE:?!if-exists/path/to/tpl.htm!@@@] 	:: sub-template with relative path to framework, using exact this path, optional, if exists
	 * // [@@@SUB-TEMPLATE:{tpl}|{escape}@@@] 				:: sub-template (using any kind of path from above), apply escape (any of): |syntax |syntaxhtml |html |js |js-tpl-encode
	 * // [@@@SUB-TEMPLATE:%variable%@@@] 					:: variable sub-template ; must be defined in $y_arr_vars['@SUB-TEMPLATES@'] array as key (variable) => value (path)
	 * // [%%%IF:TEST-VARIABLE:@==|@!=|@<=|@<|@>=|@>|==|!=|<=|<|>=|>|!%|%|!?|?|^~|^*|&~|&*|$~|$*{string/number/a|list|with|elements/###MARKER###};%%%] conditional IF part, display when condition is matched [%%%ELSE:TEST-VARIABLE%%%] conditional ELSE part, display otherwise (optional) [%%%/IF:TEST-VARIABLE%%%]
	 * // [%%%LOOP:ARR-VARIABLE%%%] [###ARR-VARIABLE.ID|int###]. [###ARR-VARIABLE.NAME|html###] [%%%/LOOP:ARR-VARIABLE%%%]
	 * // [%%%COMMENT%%%] This is a comment in template that will not be displayed [%%%/COMMENT%%%]
	 * // And some special characters:
	 * //		[%%%|SB-L%%%] ensures a LEFT SQUARE BRACKET [
	 * //		[%%%|SB-R%%%] ensures a RIGHT SQUARE BRACKET ]
	 * //		[%%%|SPACE%%%] ensures a SPACE
	 * //		[%%%|T%%%] ensures a TAB \t
	 * //		[%%%|N%%%] ensures a LINE FEED \n
	 * //		[%%%|R%%%] ensures a CARRIAGE RETURN \r
	 *
	 * SmartMarkersTemplating::render_file_template(
	 * 		'views/my-template.mtpl.htm',
	 * 		[
	 * 			'MARKER' 				=> 'something ...',
	 * 			'TEST-VARIABLE' 		=> 'another thing !',
	 * 			'ARR-VARIABLE' 		=> [
	 * 				[ 'id' => 1, 'name' => 'One' ],
	 * 				[ 'id' => 2, 'name' => 'Two' ],
	 * 				[ 'id' => 3, 'name' => 'Three' ]
	 * 			]
	 * 		]
	 * );
	 * </code>
	 *
	 * @param 	STRING 		$y_file_path 					:: The relative path to the file Marker-TPL (partial text/html + markers + *sub-templates*) ; if sub-templates are used, they will use the base path from this (main template) file ; Ex: views/my-template.inc.htm ; (partial text/html + markers) ; Ex (file content): '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template markers is case insensitive ; Ex: [ 'MARKER1' => 'Value1', 'marker2' => 'Value2', ..., 'MarkerN' => 100 ]
	 * @param 	ENUM 		$y_use_caching 					:: 'yes' will cache the template (incl. sub-templates if any) into memory to avoid re-read them from file system (to be used if a template is used more than once per execution) ; 'no' means no caching is used (default)
	 *
	 * @return 	STRING										:: The parsed and rendered template
	 *
	 */
	public static function render_file_template(string $y_file_path, array $y_arr_vars, string $y_use_caching='no') {
		//--
		// it can *optional* use caching to avoid read a file template (or it's sub-templates) more than once per execution
		// if using the cache the template and also sub-templates (if any) are cached internally to avoid re-read them from filesystem
		// the replacement of sub-templates is made before injecting variables to avoid security issues
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(SmartFileSysUtils::check_if_safe_path($y_file_path) != 1) {
			Smart::raise_error('Invalid Marker-TPL File Path: '.$y_file_path);
			return (string) 'ERROR: (101) in '.__CLASS__;
		} //end if
		if(!SmartFileSystem::is_type_file($y_file_path)) {
			Smart::raise_error('Invalid Marker-TPL File Type: '.$y_file_path);
			return (string) 'ERROR: (102) in '.__CLASS__;
		} //end if
		//--
		if(!is_array($y_arr_vars)) {
			$y_arr_vars = array();
			Smart::log_warning('Invalid Marker-TPL Data-Set for Template file: '.$y_file_path);
		} //end if
		//--
		$y_use_caching = (string) $y_use_caching;
		//--
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//--
		$mtemplate = (string) self::read_template_or_subtemplate_file((string)$y_file_path, (string)$y_use_caching);
		if((string)$mtemplate == '') {
			Smart::raise_error('Empty or Un-Readable Marker-TPL File: '.$y_file_path);
			return (string) 'ERROR: (103) in '.__CLASS__;
		} //end if
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / File-Render: '.$y_file_path,
				'data' => 'Caching: '.$y_use_caching
			]);
		} //end if
		//--
		$arr_sub_templates = array();
		if(array_key_exists('@SUB-TEMPLATES@', $y_arr_vars) AND (is_array($y_arr_vars['@SUB-TEMPLATES@']))) { // if supplied then use it (preffered), never mix supplied with detection else results would be unpredictable ...
			$arr_sub_templates = (array) $y_arr_vars['@SUB-TEMPLATES@'];
		} else { // if not supplied, try to detect
			$arr_sub_templates = (array) self::detect_subtemplates($mtemplate);
		} //end if else
		if(Smart::array_size($arr_sub_templates) > 0) {
			$tpl_basepath = (string) SmartFileSysUtils::add_dir_last_slash(SmartFileSysUtils::get_dir_from_path($y_file_path));
			if(SmartFrameworkRuntime::ifDebug()) {
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-Render.LOAD-SUBTEMPLATES] :: Marker-TPL / File-Render: '.$y_file_path.' ; Sub-Templates Load Base Path: '.$tpl_basepath,
					'data' => 'Sub-Templates: '."\n".print_r($arr_sub_templates,1)
				]);
			} //end if
			$mtemplate = (string) self::load_subtemplates((string)$y_use_caching, (string)$tpl_basepath, (string)$mtemplate, (array)$arr_sub_templates); // load sub-templates before template processing and use caching also for sub-templates if set
		} //end if
		$arr_sub_templates = array();
		//-- avoid send the sub-templates array to the render_template() as the all sub-templates were processed here if any ; that function will try to detect only if used from separate context, this context will not allow re-detection as there would be no more
		if(array_key_exists('@SUB-TEMPLATES@', (array)$y_arr_vars)) {
			unset($y_arr_vars['@SUB-TEMPLATES@']);
		} //end if
		//--
		return (string) self::template_renderer((string)$mtemplate, (array)$y_arr_vars);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render Mixed Marker Template (String Template + Sub-Templates from Files if any)
	 * If no-subtemplates are available is better to use render_template() instead of this one.
	 * !!! This is intended for very special usage (Ex: render a main template) since it does not support defining @SUB-TEMPLATES@ in the data array (like the render_file_template() does) and the sub-templates base path is a required parameter !!!
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL (partial text/html + markers) ; Ex: '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template markers is case insensitive ; Ex: [ 'MARKER1' => 'Value1', 'marker2' => 'Value2', ..., 'MarkerN' => 100 ]
	 * @param 	STRING 		$y_sub_templates_base_path 		:: The (relative) base path of sub-templates files if they are used (required to be non-empty)
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Marker-TPL is empty ; 'no' will add a warning (default)
	 *
	 * @return 	STRING										:: The parsed template
	 *
	 */
	public static function render_mixed_template(string $mtemplate, array $y_arr_vars, string $y_sub_templates_base_path, string $y_ignore_if_empty='no') {
		//--
		// main templates cannot use self-caching as they are not intended for heavy repetitive TPL loads
		// mainly main TPLs have just few markers and sub-tpls and if a caching is required there is support for persistent cache !
		// the replacement of sub-templates is made before injecting variables to avoid security issues
		//--
		$y_use_caching = 'no';
		//--
		$mtemplate = (string) trim((string)$mtemplate);
		//--
		if(((string)$y_ignore_if_empty != 'yes') AND ((string)$mtemplate == '')) {
			//--
			Smart::raise_error('Empty Mixed Marker-TPL Content: '.print_r($y_arr_vars,1));
			return (string) 'ERROR: (201) in '.__CLASS__;
			//--
		} //end if
		//--
		if(!is_array($y_arr_vars)) {
			$y_arr_vars = array();
			Smart::log_warning('Invalid Mixed Marker-TPL Data-Set for Template: '.$mtemplate);
		} //end if
		//--
		if((string)$y_sub_templates_base_path == '') {
			Smart::raise_error('Empty Base Path for Mixed Marker-TPL Content: '.$mtemplate);
			return (string) 'ERROR: (202) in '.__CLASS__;
		} //end if
		//-- make all keys upper
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//-- process sub-templates if any
		if(SmartFrameworkRuntime::ifDebug()) {
			SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / Mixed Render ; Ignore if Empty: '.$y_ignore_if_empty.' ; Sub-Templates Load Base Path: '.$y_sub_templates_base_path,
				'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//-- dissalow the use of sub-templates array here or later in this context
		if(array_key_exists('@SUB-TEMPLATES@', (array)$y_arr_vars)) {
			unset($y_arr_vars['@SUB-TEMPLATES@']);
		} //end if
		//--
		$arr_sub_templates = (array) self::detect_subtemplates($mtemplate);
		if(Smart::array_size($arr_sub_templates) > 0) {
			$mtemplate = (string) self::load_subtemplates((string)$y_use_caching, (string)$y_sub_templates_base_path, (string)$mtemplate, (array)$arr_sub_templates); // load sub-templates before template processing
		} //end if
		$arr_sub_templates = array();
		//--
		return (string) self::template_renderer((string)$mtemplate, (array)$y_arr_vars);
		//--
	} //END FUNCTION
	//================================================================



	//================================================================
	/**
	 * Read a Marker File Template from FileSystem or from Persistent (Memory) Cache if exists
	 * !!! This is intended for very special usage ... !!! This is used automatically by the render_file_template() and used in combination with render_mixed_template() may produce the same results ... it make non-sense using it with render_template() as this should be used for internal (php) templates as all external templates should be loaded with render_file_template()
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$y_file_path 					:: The relative path to the file Marker-TPL
	 *
	 * @return 	STRING										:: The template string
	 *
	 */
	public static function read_template_file(string $y_file_path) {
		//--
		if(self::$MkTplAnalyzeLdDbg === true) {
			if(!array_key_exists((string)$y_file_path, self::$MkTplAnalyzeLdRegDbg)) {
				self::$MkTplAnalyzeLdRegDbg[(string)$y_file_path] = 0;
			} //end if
			self::$MkTplAnalyzeLdRegDbg[(string)$y_file_path] += 1;
		} //end if
		//--
		$mtemplate = (string) self::read_from_fs_or_pcache_the_template_file($y_file_path);
		//--
		$cached_key = 'read_template_file:'.$y_file_path; // {{{SYNC-TPL-DEBUG-CACHED-KEY}}}
		if(SmartFrameworkRuntime::ifDebug()) {
			if(!array_key_exists((string)$cached_key, self::$MkTplFCount)) {
				self::$MkTplFCount[(string)$cached_key] = 0;
			} //end if
			self::$MkTplFCount[(string)$cached_key]++; // register to counter anytime is read from FileSystem
		} //end if
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			self::$MkTplVars['@TEMPLATE:'.$y_file_path][] = 'Direct Reading a Template from FS';
			SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Direct-ReadFileTemplate-From-FS] :: Marker-TPL / Direct-File-Read ; Serving from FS the File Template: '.$y_file_path.' ;',
				'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Prepare a value or a template by escaping syntax
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$val 							:: The template or value to be escaped
	 *
	 * @return 	STRING										:: The escaped string
	 *
	 */
	public static function prepare_nosyntax_content(string $val) {
		//--
		if((string)$val != '') { // protect against replace reccurence if val is non-empty
			//--
			$arr_fix_safe = [];
			$arr_fix_dbg = [];
			//--
			if(self::have_marker((string)$val)) {
				$arr_fix_safe['[###'] = '［###';
				$arr_fix_safe['###]'] = '###］';
				$arr_fix_dbg[] = 'Markers';
			} //end if
			if(self::have_syntax((string)$val)) {
				$arr_fix_safe['[%%%'] = '［%%%';
				$arr_fix_safe['%%%]'] = '%%%］';
				$arr_fix_dbg[] = 'Marker Syntax';
			} //end if
			if(self::have_subtemplate((string)$val)) {
				$arr_fix_safe['[@@@'] = '［@@@';
				$arr_fix_safe['@@@]'] = '@@@］';
				$arr_fix_dbg[] = 'Marker Sub-Templates';
			} //end if
			//--
			if(Smart::array_size($arr_fix_safe) > 0) {
				if(SmartFrameworkRuntime::ifDebug()) {
					// this notice is too complex to fix in all situations, thus make it show just on Debug !
					// because many times the values come from variable sources: user input, database, ... this notice make non-sense anymore !!
					Smart::log_notice('Invalid or Undefined Marker-TPL: '.implode(', ', (array)$arr_fix_dbg).' - detected in Replacement Key: '.$key.' -> [Val: '.$val.'] for Template:'."\n".self::log_template($mtemplate));
				} //end if
				$val = (string) str_replace(
					(array) array_keys($arr_fix_safe), // dissalowed markers / syntax / sub-tpls
					(array) array_values($arr_fix_safe), // fixed content, marked with +
					(string) $val
				); // protect against cascade / recursion / undefined variables - for content injections of: variables / syntax / sub-templates
			} //end if
			//--
		} //end if
		//--
		return (string) $val;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Prepare a HTML template for display in no-conflict mode: no syntax or markers will be parsed
	 * To keep the markers and syntax as-is but avoiding conflicting with real markers / syntax it will encode as HTML Entities the following syntax patterns: [ ] # % @
	 * !!! This is intended for very special usage ... !!!
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$mtemplate 						:: The template to be prepared
	 * @param 	BOOLEAN 	$titlecomments 					:: *Optional* Default FALSE ; If TRUE will highlight TPL markers/syntax/sub-tpl bounds and will show hint comments over them (use for debugging)
	 * @param 	BOOLEAN 	$analyze_dbg 					:: *Optional* Default FALSE ; If TRUE will replace also analyze hints (this should be used ONLY with TPL analyze feature ...)
	 *
	 * @return 	STRING										:: The template string
	 *
	 */
	public static function prepare_nosyntax_html_template(string $mtemplate, bool $titlecomments=false, bool $analyze_dbg=false) {
		//--
		if((string)$mtemplate != '') {
			//--
			if($titlecomments === false) {
				$arr_repls = [
					'',
					'',
					'',
					'',
					'',
					'',
				];
			} else {
				$arr_repls = [
					' class="sf__tpl__highlight__marker" title="TPL Marker: Start"',
					' class="sf__tpl__highlight__marker" title="TPL Marker: End"',
					' class="sf__tpl__highlight__syntax" title="TPL Syntax: Start"',
					' class="sf__tpl__highlight__syntax" title="TPL Syntax: End"',
					' class="sf__tpl__highlight__subtpl" title="TPL SubTemplate: Start"',
					' class="sf__tpl__highlight__subtpl" title="TPL SubTemplate: End"'
				];
			} //end if else
			//--
			$arr_fix_src = [
				'[###',
				'###]',
				'[%%%',
				'%%%]',
				'[@@@',
				'@@@]'
			];
			//--
			$arr_fix_back_src = [
				'［###',
				'###］',
				'［%%%',
				'%%%］',
				'［@@@',
				'@@@］'
			];
			//--
			if($titlecomments === false) {
				$arr_fix_dst = [
					'&lbrack;###',
					'###&rbrack;',
					'&lbrack;%%%',
					'%%%&rbrack;',
					'&lbrack;@@@',
					'@@@&rbrack;'
				];
			} else {
				$arr_fix_dst = [
					'<span'.$arr_repls[0].'>&lbrack;###</span>',
					'<span'.$arr_repls[1].'>###&rbrack;</span>',
					'<span'.$arr_repls[2].'>&lbrack;%%%</span>',
					'<span'.$arr_repls[3].'>%%%&rbrack;</span>',
					'<span'.$arr_repls[4].'>&lbrack;@@@</span>',
					'<span'.$arr_repls[5].'>@@@&rbrack;</span>'
				];
			} //end if else
			//--
			$mtemplate = (string) str_replace(
				(array) $arr_fix_src,
				(array) $arr_fix_dst,
				(string) $mtemplate
			);
			$mtemplate = (string) str_replace(
				(array) $arr_fix_back_src,
				(array) $arr_fix_dst,
				(string) $mtemplate
			);
			//--
			if($analyze_dbg === true) {
				//--
				$mtemplate = (string) str_replace(
					[
						'⁅***¦',
						'¦***⁆'
					],
					[
						'<span'.$arr_repls[4].'>&lbrack;@@@</span>',
						'<span'.$arr_repls[5].'>@@@&rbrack;</span>'
					],
					(string) $mtemplate
				);
				//--
			} //end if else
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//===== PRIVATES


	//================================================================
	// extract and process extracted parts for analyze and return as array as match => the number of matches
	private static function analize_parts_extract(string $regex, string $mtemplate, bool $uppercasekeys) {
		//--
		if((string)trim((string)$regex) == '') {
			Smart::log_warning(__METHOD__.'() # Empty Regex ...');
			return array();
		} //end if
		//--
		$matches = array();
		$pcre = preg_match_all((string)$regex, (string)$mtemplate, $matches, PREG_SET_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//die('<pre>'.Smart::escape_html(print_r($matches,1)).'</pre>');
		//--
		$arr_parts = array();
		//--
		for($i=0; $i<Smart::array_size($matches); $i++) {
			//--
			$matches[$i] = (array) $matches[$i];
			//--
			$matches[$i][1] = (string) trim((string)$matches[$i][1]);
			if((string)$matches[$i][1] != '') {
				if($uppercasekeys === true) {
					if(!array_key_exists((string)strtoupper((string)$matches[$i][1]), $arr_parts)) {
						$arr_parts[(string)strtoupper((string)$matches[$i][1])] = 0;
					} //end if
					$arr_parts[(string)strtoupper((string)$matches[$i][1])] += 1;
				} else {
					if(!array_key_exists((string)$matches[$i][1], $arr_parts)) {
						$arr_parts[(string)$matches[$i][1]] = 0;
					} //end if
					$arr_parts[(string)$matches[$i][1]] += 1; // no strtoupper in this case !! (must preserve case)
				} //end if else
			} //end if
			//--
			$matches[$i] = null; // free mem
			//--
		} //end for
		//--
		return (array) $arr_parts;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract Markers for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected markers
	 *
	 */
	private static function analize_extract_markers(string $mtemplate) {
		//--
		return (array) self::analize_parts_extract('/###([A-Z0-9_\-\.]+)/', (string)$mtemplate, true); // {{{SYNC-TPL-EXPR-MARKER}}} :: start part only :: - [ - ] (can be in IF statement) ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract Ifs for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected if syntaxes
	 *
	 */
	private static function analize_extract_ifs(string $mtemplate) {
		//--
		return (array) self::analize_parts_extract('{\[%%%IF\:([a-zA-Z0-9_\-\.]+)\:}sU', (string)$mtemplate, true); // {{{SYNC-TPL-EXPR-IF}}} :: start part only ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract Loops for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected loop syntaxes
	 *
	 */
	private static function analize_extract_loops(string $mtemplate) {
		//--
		return (array) self::analize_parts_extract('{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+)((\([0-9]+\))?%)%%\]}sU', (string)$mtemplate, true); // {{{SYNC-TPL-EXPR-LOOP}}} ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract R/N/T/SPACE for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected R/N/T/SPACE syntaxes
	 *
	 */
	private static function analize_extract_specials(string $mtemplate) {
		//--
		return (array) self::analize_parts_extract('{\[%%%\|?([a-zA-Z0-9_\-\.]+)%%%\]}sU', (string)$mtemplate, true); // {{{SYNC-TPL-EXPR-SPECIALS}}} ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract SubTPLs for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected sub-template syntaxes
	 *
	 */
	private static function analize_extract_subtpls(string $mtemplate) {
		//--
		return (array) self::analize_parts_extract('{\[@@@SUB\-TEMPLATE:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}', (string)$mtemplate, false); // FIX: add an extra % to parse also SUB-TPL %vars% # {{{SYNC-TPL-EXPR-SUBTPL}}} :: + % ; preserve case
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * DO Analyze a Marker Template (String Template ; no sub-templates are allowed as there is no possibility to set a relative path from where to get them)
	 * This is intended for DEVELOPMENT / DEBUG ONLY (never use this in production environments !)
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 * @param 	STRING 		$y_info 						:: The Analysis Info (Title)
	 * @param 	STRING 		$y_original_mtemplate 			:: *OPTIONAL* ONLY for Loading File-Template :: the original template if loaded by file to pre-process level 1 Sub-Templates and display them
	 *
	 * @return 	STRING										:: The analyze info HTML
	 *
	 */
	private static function analyze_do_debug_template(string $mtemplate, string $y_info, string $y_original_mtemplate='') {
		//-- input vars
		$mtemplate = (string) $mtemplate;
		$y_info = (string) trim((string)$y_info);
		$y_original_mtemplate = (string) trim((string)$y_original_mtemplate);
		if((string)$y_original_mtemplate == '') {
			$y_original_mtemplate = (string) $mtemplate;
		} //end if
		//-- calculate hash
		$hash = (string) sha1((string)$y_info.$mtemplate);
		//-- inits
		$html = '<!-- START: Marker-TPL Debug Analysis @ '.Smart::escape_html($hash).' # -->'."\n";
		$html .= '<div align="left">';
		$html .= '<h2 style="display:inline;background:#003366;color:#FFFFFF;padding:3px;">Marker-TPL Debug Analysis</h2>';
		if((string)$y_info != '') {
			$html .= '<br><h3 style="display:inline;">'.Smart::escape_html($y_info).'</h3>';
		} //end if
		$html .= '<hr>';
		//-- main table
		$html .= '<table width="99%">';
		$html .= '<tr valign="top" align="center">';
		//-- loaded sub-tpls
		$html .= '<td align="left" colspan="2">';
		$html .= '<table id="'.'__marker__template__analyzer-ldsubtpls_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="950" style="font-size:0.750em!important;">';
		$html .= '<tr align="center"><th>[@@@SUB-TEMPLATES:LOADED@@@]<br><small>*** All Loaded Sub-Templates are listed below ***</small></th><th>#'.'&nbsp;('.(int)Smart::array_size(self::$MkTplAnalyzeLdRegDbg).')'.'</th></tr>';
		if(Smart::array_size(self::$MkTplAnalyzeLdRegDbg) > 0) {
			foreach(self::$MkTplAnalyzeLdRegDbg as $key => $val) {
				$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} //end foreach
		} //end if
		$html .= '</table>';
		$html .= '</td>';
		//-- sub-tpls
		$html .= '<td align="center">';
		$html .= '<table id="'.'__marker__template__analyzer-subtpls_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="525" style="font-size:0.750em!important;">';
		$arr_subtpls = (array) self::analize_extract_subtpls($y_original_mtemplate);
		ksort($arr_subtpls);
		$html .= '<tr align="center"><th>[@@@SUB-TEMPLATES:SLOTS@LEVEL-1@@@]<br><small>*** Only Level-1 Sub-Templates slots are listed below ***</small></th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_subtpls).')'.'</th></tr>';
		foreach($arr_subtpls as $key => $val) {
			$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
		} //end for
		$html .= '</table>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr valign="top" align="center"><td colspan="3"><hr></td></tr>';
		$html .= '<tr valign="top" align="center">';
		//-- marker vars
		$arr_marks = (array) self::analize_extract_markers($mtemplate);
		ksort($arr_marks);
		$html .= '<td width="33%"><table id="'.'__marker__template__analyzer-markers_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="525" style="font-size:0.750em!important;"><tr align="center"><th>[###MARKER-VARIABLES###]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_marks).')'.'</th></tr>';
		foreach($arr_marks as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} else {
				// listed below
			} //end if
		} //end for
		foreach($arr_marks as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				// listed above
			} else {
				$html .= '<tr><td align="left"><span style="color:#778899; font-style:italic;">'.Smart::escape_html((string)$key).'</span></td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} //end if
		} //end for
		$html .= '</table></td>';
		//-- loop vars
		$arr_loops = (array) self::analize_extract_loops($mtemplate);
		ksort($arr_loops);
		$html .= '<td width="33%"><table id="'.'__marker__template__analyzer-loopvars_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="525" style="font-size:0.750em!important;"><tr align="center"><th>[%%%LOOP:VARIABLES%%%]<br>[%%%/LOOP:VARIABLES%%%]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_loops).')'.'</th></tr>';
		foreach($arr_loops as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} else {
				// listed below
			} //end if
		} //end for
		foreach($arr_loops as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				// listed above
			} else {
				$html .= '<tr><td align="left"><span style="color:#778899; font-style:italic;">'.Smart::escape_html((string)$key).'</span></td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} //end if
		} //end for
		$html .= '</table></td>';
		//-- if vars
		$arr_ifs = (array) self::analize_extract_ifs($mtemplate);
		ksort($arr_ifs);
		$html .= '<td width="33%"><table id="'.'__marker__template__analyzer-ifvars_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="525" style="font-size:0.750em!important;"><tr align="center"><th>[%%%IF:VARIABLES:{condition};%%%]<br>[%%%ELSE:VARIABLES%%%]<br>[%%%/IF:VARIABLES%%%]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_ifs).')'.'</th></tr>';
		foreach($arr_ifs as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} else {
				// listed below
			} //end if
		} //end for
		foreach($arr_ifs as $key => $val) {
			if((strpos((string)$key, '.-_') === false) AND (strpos((string)$key, '_-') === false)) { // {{{SYNC-VARS-RESERVED-KEYS}}}
				// listed above
			} else {
				$html .= '<tr><td align="left"><span style="color:#778899; font-style:italic;">'.Smart::escape_html((string)$key).'</span></td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} //end if
		} //end for
		$html .= '</table></td>';
		//-- end main table
		$html .= '</tr></table><hr>';
		//-- ending
		$html .= '</div><h2 style="display:inline;background:#003366;color:#FFFFFF;padding:3px;">Marker-TPL Source - with ALL:[Level 1..n] Sub-Templates Includded (if any)</h2><div id="tpl-display-for-highlight"><pre id="'.'__marker__template__analyzer-tpl_'.Smart::escape_html($hash).'"><code class="markerstpl">'.Smart::escape_html($mtemplate).'</code></pre></div><hr>'."\n".'<!-- #END: Marker-TPL Analysis @ '.Smart::escape_html($hash).' -->';
		//-- return
		return (string) self::prepare_nosyntax_html_template($html, true, true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// INFO: this renders the template except sub-templates loading which is managed separately
	// $mtemplate must be STRING, non-empty
	// $y_arr_vars must be a prepared ARRAY with all keys UPPERCASE
	private static function template_renderer(string $mtemplate, array $y_arr_vars) {
		//-- debug start
		if(SmartFrameworkRuntime::ifDebug()) {
			$bench = microtime(true);
		} //end if
		//-- if have syntax, process it
		if(self::have_syntax((string)$mtemplate) === true) {
			$mtemplate = (string) self::process_syntax((string)$mtemplate, (array)$y_arr_vars);
		} //end if
		//-- process markers until the last one detected
		foreach((array)$y_arr_vars as $key => $val) {
			if(self::have_marker((string)$mtemplate) === true) {
			//	if(!is_array($val)) { // fix
				if(Smart::is_nscalar($val)) { // fix
					$mtemplate = (string) self::replace_marker((string)$mtemplate, (string)$key, (string)$val);
				} //end if # else do not log, it may occur many times with the loop variables !!!
			} else {
				break;
			} //end if else
		} //end foreach
		//-- if any garbage markers are still detected log warning
		if(self::have_marker((string)$mtemplate) === true) {
			$arr_marks = (array) self::analize_extract_markers($mtemplate);
			Smart::log_notice('Invalid or Undefined Marker-TPL: Markers detected in Template:'."\n".'MARKERS:'.print_r($arr_marks,1)."\n".self::log_template($mtemplate));
			$mtemplate = (string) str_replace(array('[###', '###]'), array('⁅###¦', '¦###⁆'), (string)$mtemplate); // finally protect against undefined variables
		} //end if
		//-- debug end
		if(SmartFrameworkRuntime::ifDebug()) {
			$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
			SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Parsing:Render.DONE] :: Marker-TPL / Processing ; Time = '.$bench.' sec.',
				'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one marker
	private static function have_marker(string $mtemplate) {
		//--
		if(strpos((string)$mtemplate, '[###') !== false) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one syntax
	private static function have_syntax(string $mtemplate) {
		//--
		if(strpos((string)$mtemplate, '[%%%') !== false) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one sub-template
	private static function have_subtemplate(string $mtemplate) {
		//--
		if(strpos((string)$mtemplate, '[@@@') !== false) {
			return true;
		} else {
			return false;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// do replacements (and escapings) for one marker ; a marker can contain: A-Z 0-9 _ - (and the dot . which is reserved as array level separator)
	/* {{{SYNC-MARKER-ALL-TEST-SEQUENCES}}}
	<!-- INFO: The VALID Escaping and Transformers for a Marker are all below ; If other escaping sequences are used the Marker will not be detected and replaced ... -->
	<!-- Valid Escapings and Transformers: |bool |int |dec[1-4]{1} |num |htmid |jsvar |slug |substr[0-9]{1,5} |subtxt[0-9]{1,5} |lower |upper |ucfirst |ucwords |trim |url |json |js |html |css |nl2br |smartlist |syntaxhtml -->
	[###MARKER###]
	[###MARKER|bool###]
	[###MARKER|int###]
	[###MARKER|dec1###]
	[###MARKER|dec2###]
	[###MARKER|dec3###]
	[###MARKER|dec4###]
	[###MARKER|num###]
	[###MARKER|idtxt###]
	[###MARKER|slug###]
	[###MARKER|htmid###]
	[###MARKER|jsvar###]
	[###MARKER|json###]
		[###MARKER|json|url###]
		[###MARKER|json|js###] 			** not necessary unless special purpose **
		[###MARKER|json|html###]
		[###MARKER|json|url|js###]
		[###MARKER|json|url|html###]
		[###MARKER|json|js|html###] 		** not necessary unless special purpose **
		[###MARKER|json|url|js|html###] 	** not necessary unless special purpose **
	[###MARKER|lower|html###]
	[###MARKER|upper|html###]
	[###MARKER|ucfirst|html###]
	[###MARKER|ucwords|html###]
	[###MARKER|trim|html###]
	[###MARKER|substr1|html###]
	[###MARKER|subtxt65535|html###]
	[###MARKER|url###]
	[###MARKER|url|js###]
	[###MARKER|url|html###]
	[###MARKER|url|js|html###] 			** not necessary unless special purpose **
	[###MARKER|js###]
	[###MARKER|js|html###] 				** not necessary unless special purpose **
	[###MARKER|html###]
	[###MARKER|html|nl2br###]
	[###MARKER|html|nl2br|url|js###]
	[###MARKER|html|nl2br|js|url###]
	[###MARKER|css###]
	*/
	private static function replace_marker(string $mtemplate, string $key, string $val) {
		//-- {{{SYNC-TPL-EXPR-MARKER}}}
		$found = preg_match('/^[A-Z0-9_\-\.]+$/', (string)$key);
		if($found === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return (string) $mtemplate;
		} //end if
		if(((string)$key != '') AND ($found) AND (strpos((string)$mtemplate, '[###'.$key) !== false)) {
			//--
			$regex = '/\[###'.preg_quote((string)$key, '/').'((\|[a-z0-9]+)*)'.'###\]/'; // {{{SYNC-REGEX-MARKER-TEMPLATES}}}
			//--
			$val = (string) self::prepare_nosyntax_content($val);
			//--
			$matches = array();
			$pcre = preg_match_all((string)$regex, (string)$mtemplate, $matches, PREG_SET_ORDER, 0);
			if($pcre === false) {
				Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
				return (string) $mtemplate;
			} //end if
			//--
			$arr_repls = [];
			//--
			for($i=0; $i<Smart::array_size($matches); $i++) {
				//--
				$crr_match = (array) $matches[$i];
				$matches[$i] = null; // free mem
				//--
				if(!array_key_exists((string)$crr_match[0], (array)$arr_repls)) {
					//--
					$crr_match[1] = (string) trim((string)$crr_match[1]);
					//--
					if((string)$crr_match[1] == '') { // if no escaping
						//--
						$arr_repls[(string)$crr_match[0]] = (string) $val; // use raw value
						//--
					} else { // if escapings, apply
						//--
						$crr_match[1] = (string) trim((string)$crr_match[1], '|');
						//--
						if((string)$crr_match[1] == '') {
							//--
							// in this case will skip the replacement
							//--
							Smart::log_warning('Invalid or Undefined Marker-TPL Escaping - detected in Replacement Key: '.$crr_match[0].' -> [Val: '.$val.']');
							//--
						} else {
							//--
							$arr_repls[(string)$crr_match[0]] = (string) self::escape_marker_value((array)$crr_match, (string)$val);
							//--
						} //end if else
						//--
					} //end if
					//--
				} //end if
				//--
			} //end for
			//--
			if(Smart::array_size($arr_repls) > 0) {
				$mtemplate = (string) str_replace((array)array_keys((array)$arr_repls), (array)array_values((array)$arr_repls), (string)$mtemplate);
			} //end if
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// escape a marker value conforming with the escapings sequence: |esc1|esc2|...|escn
	private static function escape_marker_value(array $crr_match, string $val) {
		//--
		$crr_match = (array) $crr_match;
		$val = (string) $val;
		//--
		if(!array_key_exists(0, $crr_match)) {
			$crr_match[0] = null;
		} //end if
		if(!array_key_exists(1, $crr_match)) {
			$crr_match[1] = null;
		} //end if
		if((string)$crr_match[1] != '') { // if escapings
			//--
			$escapes = (array) explode('|', (string)$crr_match[1]);
			$maxescapes = (int) Smart::array_size($escapes);
			if($maxescapes > 99) {
				Smart::raise_error(
					'Too much recursion for Marker-TPL Escapings - detected in Replacement Key: '.$crr_match[0].' -> [Val: '.$val.']',
					'Marker-TPL Fatal Error: Too much recursion' // msg to display
				);
				return ''; // OK empty
			} //end if
			//--
			if($maxescapes > 0) {
				//--
				for($i=0; $i<$maxescapes; $i++) {
					//--
					$escexpr = (string) '|'.$escapes[$i];
					//--
					if((string)$escexpr == '|bool') { // Boolean
						if($val) {
							$val = 'true';
						} else {
							$val = 'false';
						} //end if else
					} elseif((string)$escexpr == '|int') { // Integer
						$val = (string) (int) $val;
					} elseif((string)substr((string)$escexpr, 0, 4) == '|dec') {
						$xnum = Smart::format_number_int((int)substr((string)$escexpr, 4), '+');
						if($xnum < 1) {
							$xnum = 1;
						} elseif($xnum > 4) {
							$xnum = 4;
						} //end if
						$val = (string) Smart::format_number_dec((string)$val, (int)$xnum, '.', '');
						$xnum = null; // free mem
					} elseif((string)$escexpr == '|num') { // Number (Float / Decimal / Integer)
						$val = (string) (float) $val;
					//--
					} elseif((string)$escexpr == '|idtxt') { // id_txt: Id-Txt
						$val = (string) str_replace('_', '-', (string)$val);
						$val = (string) SmartUnicode::uc_words((string)$val);
					} elseif((string)$escexpr == '|slug') { // Slug: a-zA-Z0-9_- / - / -- : -
						$val = (string) Smart::create_slug((string)$val, false); // do not apply strtolower as it can be later combined with |lower flag
					} elseif((string)$escexpr == '|htmid') { // HTML-ID: a-zA-Z0-9_-
						$val = (string) Smart::create_htmid((string)$val);
					} elseif((string)$escexpr == '|jsvar') { // JS-Variable: a-zA-Z0-9_
						$val = (string) Smart::create_jsvar((string)$val);
					//--
					} elseif(((string)substr((string)$escexpr, 0, 7) == '|substr') OR ((string)substr((string)$escexpr, 0, 7) == '|subtxt')) { // Sub(String|Text) (0,num)
						$xnum = Smart::format_number_int((int)substr((string)$escexpr, 7), '+');
						if($xnum < 1) {
							$xnum = 1;
						} elseif($xnum > 65535) {
							$xnum = 65535;
						} //end if
						if((string)substr((string)$escexpr, 0, 7) == '|subtxt') {
							if($xnum < 5) {
								$xnum = 5;
							} //end if
							$val = (string) Smart::text_cut_by_limit((string)$val, (int)$xnum, false, '...');
						} else { // '|substr'
							$val = (string) Smart::text_cut_by_limit((string)$val, (int)$xnum, true, '');
						} //end if else
						$xnum = null; // free mem
					//--
					} elseif((string)$escexpr == '|lower') { // apply lowercase
						$val = (string) SmartUnicode::str_tolower((string)$val);
					} elseif((string)$escexpr == '|upper') { // apply uppercase
						$val = (string) SmartUnicode::str_toupper((string)$val);
					} elseif((string)$escexpr == '|ucfirst') { // apply uppercase first character
						$val = (string) SmartUnicode::uc_first((string)$val);
					} elseif((string)$escexpr == '|ucwords') { // apply uppercase on each word
						$val = (string) SmartUnicode::uc_words((string)$val);
					} elseif((string)$escexpr == '|trim') { // apply trim
					//--
						$val = (string) trim((string)$val);
					} elseif((string)$escexpr == '|url') {
						$val = (string) Smart::escape_url((string)$val); // escape URL
					//--
					} elseif((string)$escexpr == '|json') { // Json Data ; expects pure JSON !!!
						$val = (string) Smart::json_encode(Smart::json_decode($val, true), false, true, true); // it MUST be JSON with HTML-Safe Options.
						$val = (string) trim((string)$val);
						if((string)$val == '') {
							$val = 'null'; // ensure a minimal json as empty string if no expr !
						} //end if
					} elseif((string)$escexpr == '|js') {
						$val = (string) Smart::escape_js((string)$val); // Escape JS
					//--
					} elseif((string)$escexpr == '|html') {
						$val = (string) Smart::escape_html((string)$val); // Escape HTML
					} elseif((string)$escexpr == '|css') {
						$val = (string) Smart::escape_css((string)$val); // Escape CSS
					} elseif((string)$escexpr == '|nl2br') {
						$val = (string) Smart::nl_2_br((string)$val); // Apply Nl2Br
					//--
					} elseif((string)$escexpr == '|smartlist') { // Apply SmartList Fix Replacements
						$val = (string) str_replace(['<', '>'], ['‹', '›'], (string)$val); // {{{SYNC-SMARTLIST-BRACKET-REPLACEMENTS}}}
					} elseif((string)$escexpr == '|syntaxhtml') {
						$val = (string) self::prepare_nosyntax_html_template((string)$val); // Prepare a HTML template for display in no-conflict mode: no syntax or markers will be parsed
					//--
					} else {
						Smart::log_warning('Invalid or Undefined Marker-TPL Escaping: '.$escexpr.' - detected in Replacement Key: '.$crr_match[0].' -> [Val: '.$val.']');
					} //end if else
					//--
				} //end for
				//--
			} //end if
			//--
		} //end if
		//--
		return (string) $val;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template syntax: for now just LOOP and IF ...
	private static function process_syntax(string $mtemplate, array $y_arr_vars) {
		//-- zero priority: remove comments
		$mtemplate = (string) self::process_comments_syntax((string)$mtemplate);
		//-- 1st process IF and remove parts that will not be rendered
		$mtemplate = (string) self::process_if_syntax((string)$mtemplate, (array)$y_arr_vars); // this will auto-check if the template have any IF Syntax
		//-- 2nd process loop syntax (max 3 nested levels)
		$mtemplate = (string) self::process_loop_syntax((string)$mtemplate, (array)$y_arr_vars); // this will auto-check if the template have any LOOP Syntax
		//-- 3rd, process special characters: Square-Brackets(L/R) \r \n \t SPACE syntax
		$mtemplate = (string) self::process_brntspace_syntax($mtemplate);
		//-- 4th, finally if any garbage syntax is detected log warning
		if(self::have_syntax((string)$mtemplate) === true) {
			$arr_ifs = (array) self::analize_extract_ifs($mtemplate);
			if(Smart::array_size($arr_ifs) > 0) {
				$arr_ifs = "\n".'IF-SYNTAX:'.print_r($arr_ifs,1);
			} else {
				$arr_ifs = '';
			} //end if else
			$arr_loops = (array) self::analize_extract_loops($mtemplate);
			if(Smart::array_size($arr_loops) > 0) {
				$arr_loops = "\n".'LOOP-SYNTAX:'.print_r($arr_loops,1);
			} else {
				$arr_loops = '';
			} //end if else
			$arr_specials = (array) self::analize_extract_specials($mtemplate);
			if(Smart::array_size($arr_specials) > 0) {
				$arr_specials = "\n".'SPECIAL-SYNTAX:'.print_r($arr_specials,1);
			} else {
				$arr_specials = '';
			} //end if else
			Smart::log_notice('Invalid or Undefined Marker-TPL: Marker Syntax detected in Template:'.$arr_ifs.$arr_loops.$arr_specials."\n".self::log_template($mtemplate));
			$mtemplate = (string) str_replace(array('[%%%', '%%%]'), array('⁅%%%¦', '¦%%%⁆'), (string)$mtemplate); // finally protect against invalid loops (may have not bind to an existing var or invalid syntax)
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template COMMENT syntax
	private static function process_comments_syntax(string $mtemplate) {
		//--
		if(strpos((string)$mtemplate, '[%%%COMMENT') !== false) {
			//--
			//$pattern = '{\[%%%COMMENT%%%\](.*)?\[%%%\/COMMENT%%%\]}sU';
			$pattern = '{\s?\[%%%COMMENT%%%\](.*)?\[%%%\/COMMENT%%%\]\s?}sU'; // Fix: trim parts
			$mtemplate = (string) preg_replace($pattern, '', (string)$mtemplate);
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template special syntax to preserve special characters if required: Square-Brackets(L/R), \r, \n, \t, SPACE
	private static function process_brntspace_syntax(string $mtemplate) {
		//--
		if(strpos((string)$mtemplate, '[%%%|') !== false) {
			//--
			$mtemplate = (string) str_replace(
				[
					'[%%%|SB-L%%%]', // left square bracket
					'[%%%|SB-R%%%]', // right square bracket
					'[%%%|R%%%]',
					'[%%%|N%%%]',
					'[%%%|T%%%]',
					'[%%%|SPACE%%%]'
				],
				[
					'［', // a special replacement for [
					'］', // a special replacement for ]
					"\r",
					"\n",
					"\t",
					' '
				],
				(string) $mtemplate
			);
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template IF syntax, nested ... on n+ levels ; compare values are compared as binary (not unicode as the regex is bind to binary mode) ; if more than these # a-z A-Z 0-9 _ - . | have to be used, it can use a ###COMPARISON-MARKER### instead
	// values in $y_arr_vars have precedence over values in $y_arr_context ; to use $y_arr_context a non-empty $y_context is required ; $y_arr_context will hold contextual values for the current loop
	private static function process_if_syntax(string $mtemplate, array $y_arr_vars, string $y_context='', array $y_arr_context=[]) {
		//--
		if(strpos((string)$mtemplate, '[%%%IF:') !== false) {
			//--
			if(!is_array($y_arr_vars)) {
				Smart::log_warning('Marker Template LOOP: Invalid Array Passed ...');
				$y_arr_vars = [];
			} //end if
			if((string)$y_context == '') {
				$y_arr_context = []; // don't allow context var without explicit context
			} //end if
			if(!is_array($y_arr_context)) {
				Smart::log_warning('Marker Template LOOP: Invalid Context Array Passed ...');
				$y_arr_context = [];
			} //end if
			//-- {{{SYNC-TPL-EXPR-IF}}}
		//	$pattern = '{\[%%%IF\:([a-zA-Z0-9_\-\.]*)\:(@\=\=|@\!\=|@\<\=|@\<|@\>\=|@\>|\=\=|\!\=|\<\=|\<|\>\=|\>|\!%|%|\!\?|\?|\^~|\^\*|&~|&\*|\$~|\$\*)([#a-zA-Z0-9_\-\.\|]*);((\([0-9]*\))?)%%%\](.*)?(\[%%%ELSE\:\1\4%%%\](.*)?)?\[%%%\/IF\:\1\4%%%\]}sU'; // previous OK
			$pattern = '{\[%%%IF\:([a-zA-Z0-9_\-\.]+)\:(@\=\=|@\!\=|@\<\=|@\<|@\>\=|@\>|\=\=|\!\=|\<\=|\<|\>\=|\>|\!%|%|\!\?|\?|\^~|\^\*|&~|&\*|\$~|\$\*)([^\[\]]*);((\([0-9]+\))?)%%%\](.*)?(\[%%%ELSE\:\1\4%%%\](.*)?)?\[%%%\/IF\:\1\4%%%\]}sU'; // new
			$matches = array();
			$pcre = preg_match_all((string)$pattern, (string)$mtemplate, $matches, PREG_SET_ORDER, 0);
			if($pcre === false) {
				Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
				return (string) $mtemplate;
			} //end if
			//echo '<pre>'.Smart::escape_html(print_r($matches,1)).'</pre>'; die();
			//--
			for($i=0; $i<Smart::array_size($matches); $i++) {
				//--
				$matches[$i] = (array) $matches[$i];
				//--
				$part_orig 		= (string) $matches[$i][0];
				$part_var 		= (string) $matches[$i][1];
				$part_sign 		= (string) $matches[$i][2];
				$part_value 	= (string) $matches[$i][3];
			//	$part_uniqid 	= (string) $matches[$i][4]; // not used
			//	$part_uniqix 	= (string) $matches[$i][5]; // not used
				$part_if 		= (string) $matches[$i][6];
			//	$part_tag_else 	= (string) $matches[$i][7]; // not used
				$part_else 		= '';
				if(array_key_exists(8, $matches[$i])) {
					$part_else 	= (string) $matches[$i][8];
				} //end if
				//--
				$matches[$i] = null; // free mem
				//--
				$bind_var_key 	= (string) $part_var;
				$bind_value 	= (string) $part_value;
				$bind_if 		= (string) $part_if;
				$bind_else 		= (string) $part_else;
				//--
				$detect_var = 0;
				if(((string)$y_context != '') AND (array_key_exists((string)$bind_var_key, (array)$y_arr_context))) { // check first in the smallest array (optimization)
					$detect_var = 2; // exist in context arr
				} elseif(Smart::array_test_key_by_path_exists((array)$y_arr_vars, (string)$bind_var_key, '.')) {
					$detect_var = 1; // exist in original arr
				} //end if else
				//--
				if(((string)$bind_var_key != '') AND ($detect_var == 1 OR $detect_var == 2)) { // if the IF is binded to a non-empty KEY and an existing (which is mandatory to avoid mixing levels which will break this syntax in complex blocks !!!)
					//--
					if(SmartFrameworkRuntime::ifDebug()) {
						if((string)$y_context != '') {
							self::$MkTplVars['%IF:'.$part_var][] = 'Processing IF Syntax in Context: '.$y_context;
						} else {
							self::$MkTplVars['%IF:'.$part_var][] = 'Processing IF Syntax';
						} //end if else
					} //end if
					//--
					$line = '';
					//-- Fix: trim parts {{{SYNC-TPL-FIX-TRIM-PARTS}}}
					$bind_if 	= (string) trim((string)$bind_if,   "\n\r\0\x0B");
					$bind_else 	= (string) trim((string)$bind_else, "\n\r\0\x0B");
					//-- recursive process if in pieces of if or else
					if(strpos((string)$bind_if, '[%%%IF:') !== false) {
						$bind_if = (string) self::process_if_syntax((string)$bind_if, (array)$y_arr_vars, (string)$y_context, (array)$y_arr_context);
					} //end if
					if(strpos((string)$bind_else, '[%%%IF:') !== false) {
						$bind_else = (string) self::process_if_syntax((string)$bind_else, (array)$y_arr_vars, (string)$y_context, (array)$y_arr_context);
					} //end if
					//--
					if(((string)substr((string)$bind_value, 0, 3) == '###') AND ((string)substr((string)$bind_value, -3, 3) == '###')) { // compare with a comparison marker (from a variable) instead of static value
					//	$bind_value = (string) strtoupper((string)str_replace('#', '', (string)$bind_value));
						$bind_value = (string) strtoupper((string)trim((string)$bind_value, '#'));
						//Smart::log_notice('Marker-TPL found IF syntax In-Marker: '.$bind_value);
						if(array_key_exists((string)$bind_value, (array)$y_arr_context)) {
							$bind_value = $y_arr_context[(string)$bind_value]; // exist in context arr
						} elseif(Smart::array_test_key_by_path_exists((array)$y_arr_vars, (string)$bind_value, '.')) {
							$bind_value = Smart::array_get_by_key_path((array)$y_arr_vars, (string)$bind_value, '.'); // exist in original arr
						} else {
							$bind_value = ''; // if not found, consider empty string
						} //end if else
					} //end if
					//-- do last if / else processing
					if($detect_var == 2) { // exist in context arr
						$tmp_the_arr = $y_arr_context[(string)$bind_var_key]; // mixed
					} else { // exist in original arr
						$tmp_the_arr = Smart::array_get_by_key_path((array)$y_arr_vars, (string)$bind_var_key, '.'); // mixed
					} //end if else
					switch((string)$part_sign) {
						//-- arrays
						case '@==': // array count ==
							if(Smart::array_size($tmp_the_arr) == (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '@!=': // array count !=
							if(Smart::array_size($tmp_the_arr) != (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '@<=': // array count <=
							if(Smart::array_size($tmp_the_arr) <= (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '@<': // array count <
							if(Smart::array_size($tmp_the_arr) < (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '@>=': // array count >=
							if(Smart::array_size($tmp_the_arr) >= (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '@>': // array count >
							if(Smart::array_size($tmp_the_arr) > (int)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						//-- numbers
						case '==':
							if((string)$tmp_the_arr == (string)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '!=':
							if((string)$tmp_the_arr != (string)$bind_value) { // if evaluate to false keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '<=':
							if((float)$tmp_the_arr <= (float)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '<':
							if((float)$tmp_the_arr < (float)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '>=':
							if((float)$tmp_the_arr >= (float)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '>':
							if((float)$tmp_the_arr > (float)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '%': // modulo (true/false)
							if(((int)$tmp_the_arr % (int)$bind_value) == 0) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '!%': // not modulo (false/true)
							if(((int)$tmp_the_arr % (int)$bind_value) != 0) { // if evaluate to false keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						//-- string lists
						case '?': // in list (elements separed by |)
							$tmp_compare_arr = (array) explode('|', (string)$bind_value);
							if(in_array((string)$tmp_the_arr, (array)$tmp_compare_arr)) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							$tmp_compare_arr = array();
							break;
						case '!?': // not in list (elements separed by |)
							$tmp_compare_arr = (array) explode('|', (string)$bind_value);
							if(!in_array((string)$tmp_the_arr, (array)$tmp_compare_arr)) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							$tmp_compare_arr = array();
							break;
						//-- strings
						case '^~': // if variable starts with part, case sensitive
							if(SmartUnicode::str_pos((string)$tmp_the_arr, (string)$bind_value) === 0) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '^*': // if variable starts with part, case insensitive
							if(SmartUnicode::str_ipos((string)$tmp_the_arr, (string)$bind_value) === 0) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '&~': // if variable contains part, case sensitive
							if(SmartUnicode::str_contains((string)$tmp_the_arr, (string)$bind_value)) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '&*': // if variable contains part, case insensitive
							if(SmartUnicode::str_icontains((string)$tmp_the_arr, (string)$bind_value)) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '$~': // if variable ends with part, case sensitive
							if(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len((string)$bind_value)), SmartUnicode::str_len((string)$bind_value)) == (string)$bind_value) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						case '$*': // if variable ends with part, case insensitive ### !!! Expensive in Execution !!! ###
							if((SmartUnicode::str_tolower(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len(SmartUnicode::str_tolower((string)$bind_value))), SmartUnicode::str_len(SmartUnicode::str_tolower((string)$bind_value)))) == (string)SmartUnicode::str_tolower((string)$bind_value)) OR (SmartUnicode::str_toupper(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len(SmartUnicode::str_toupper((string)$bind_value))), SmartUnicode::str_len(SmartUnicode::str_toupper((string)$bind_value)))) == (string)SmartUnicode::str_toupper((string)$bind_value))) { // if evaluate to true keep the inner content
								$line .= (string) $bind_if; // if part
							} else {
								$line .= (string) $bind_else; // else part ; if else not present will don't add = remove it !
							} //end if else
							break;
						//--
						default:
							// invalid syntax
							Smart::log_warning('Invalid Marker Template IF Syntax: ['.$part_sign.'] / Template: '.$mtemplate);
					} //end switch
					//--
				//	$mtemplate = (string) str_replace((string)$part_orig, (string)$line, (string)$mtemplate); // BUGFIX: If there are duplicate sub-ifs str replace without limit will replace other occurences not yet parsed and when the loop try to replace original part (which was modified by previous no limit replaces) will fail to find that part and replace it ...
					$mtemplate = (string) Smart::str_replace_first((string)$part_orig, (string)$line, (string)$mtemplate); // MUST REPLACE ONLY THE FIRST OCCURENCE because this function is recursive and the regex will already contain the original parts only and if a 2nd part is replaced but not yet parsed in this for loop the str replace will fail to find it
					//--
				} //end if else
				//--
			} //end for
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template LOOP syntax ; support nested Loop (5th-level) ; allow max 5 loop levels ; will process IF syntax inside it also
	private static function process_loop_syntax(string $mtemplate, array $y_arr_vars, int $level=0) {
		//--
		$level++;
		if($level > 5) {
			Smart::log_warning('Invalid Marker Template LOOP Level: ['.$level.'] / Template: '.$mtemplate);
			return (string) $mtemplate;
		} //end if
		//--
		if(strpos((string)$mtemplate, '[%%%LOOP:') !== false) {
			//--
			if(!is_array($y_arr_vars)) {
				Smart::log_warning('Marker Template LOOP: Invalid Array Passed ...');
				$y_arr_vars = [];
			} //end if
			//--
			$pattern = '{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+)((\([0-9]+\))?%)%%\](.*)?\[%%%\/LOOP\:\1\2%%\]}sU'; // {{{SYNC-TPL-EXPR-LOOP}}}
			$matches = array();
			$pcre = preg_match_all((string)$pattern, (string)$mtemplate, $matches, PREG_SET_ORDER, 0);
			if($pcre === false) {
				Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
				return (string) $mtemplate;
			} //end if
			//echo '<pre>'.Smart::escape_html(print_r($matches,1)).'</pre>'; die();
			//--
			for($i=0; $i<Smart::array_size($matches); $i++) {
				//--
				$matches[$i] = (array) $matches[$i];
				//--
				$part_orig 		= (string) $matches[$i][0];
				$part_var 		= (string) $matches[$i][1];
			//	$part_uniqid 	= (string) $matches[$i][2]; // not used
			//	$part_uniqix 	= (string) $matches[$i][3]; // not used
				$part_loop 		= (string) $matches[$i][4];
				//--
				$matches[$i] = null; // free mem
				//--
				$bind_var_key 	= (string) strtoupper((string)$part_var);
				//--
				if(((string)$bind_var_key != '') AND (array_key_exists((string)$bind_var_key, $y_arr_vars)) AND (is_array($y_arr_vars[(string)$bind_var_key]))) { // if the LOOP is binded to an existing Array Variable and a non-empty KEY
					//--
					if(SmartFrameworkRuntime::ifDebug()) {
						self::$MkTplVars['%LOOP:'.$bind_var_key][] = 'Processing LOOP Syntax: '.Smart::array_size($y_arr_vars[(string)$bind_var_key]);
					} //end if
					//--
					//$loop_orig = (string) rtrim((string)$part_loop);
					$loop_orig = (string) trim((string)$part_loop, "\n\r\0\x0B"); // Fix: trim parts {{{SYNC-TPL-FIX-TRIM-PARTS}}}
					//--
					$line = '';
					//--
					$arrtype = Smart::array_type_test($y_arr_vars[(string)$bind_var_key]); // 0: not an array ; 1: non-associative ; 2:associative
					//--
					if($arrtype === 1) { // 1: non-associative
						//--
						$the_max = Smart::array_size($y_arr_vars[(string)$bind_var_key]);
						$mxcnt = (int) ($the_max - 1);
						//--
						for($j=0; $j<$the_max; $j++) {
							//-- operate on a copy of original
							$mks_line = (string) $loop_orig;
							//-- process IF inside LOOP for this context (the global context is evaluated prior as this function is called after process_if_syntax() in process_syntax() via render_template()
							if(strpos((string)$mks_line, '[%%%IF:') !== false) {
								$tmp_arr_context = array(); // init
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-MAXCOUNT-_'] = (string) $mxcnt;
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-ITERATOR-_'] = (string) $j;
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-KEY-_'] = (string) $j;
								if(is_array($y_arr_vars[(string)$bind_var_key][$j])) {
									$tmp_arr_context[(string)$bind_var_key.'.'.'_-VAL-_'] = (array) $y_arr_vars[(string)$bind_var_key][$j];
									foreach($y_arr_vars[(string)$bind_var_key][$j] as $key => $val) { // expects associative array
										$tmp_arr_context[(string)$bind_var_key.'.'.strtoupper((string)$key)] = $val; // the context here is PARENT.CHILD instead of PARENT.i.CHILD (non-associative)
									} //end foreach
								} else {
									$tmp_arr_context[(string)$bind_var_key.'.'.'_-VAL-_'] = (string) (Smart::is_nscalar($y_arr_vars[(string)$bind_var_key][$j]) ? $y_arr_vars[(string)$bind_var_key][$j] : '');
								} //end if else
								$mks_line = (string) self::process_if_syntax(
									(string) $mks_line,
									(array)  $y_arr_vars,
									(string) $bind_var_key,
									(array)  $tmp_arr_context
								);
								$tmp_arr_context = array(); // reset
							} //end if
							//-- process 2nd Level LOOP inside LOOP for non-Associative Array: base vars
							if(strpos((string)$mks_line, '[%%%LOOP:') !== false) {
								$mks_line = (string) self::process_loop_syntax(
									(string) $mks_line,
									(array) $y_arr_vars,
									(int) $level
								);
							} //end if
							//-- process 2nd Level LOOP inside LOOP for non-Associative Array: sub-array vars
							if((strpos((string)$mks_line, '[%%%LOOP:') !== false) AND (is_array($y_arr_vars[(string)$bind_var_key][$j]))) {
								foreach($y_arr_vars[(string)$bind_var_key][$j] as $qk => $qv) {
									if(((strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.strtoupper((string)$qk).'%') !== false) OR (strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.strtoupper((string)$qk).'(') !== false)) AND (is_array($qv))) {
										//echo '***** ['.$bind_var_key.'.'.strtoupper((string)$qk).'] = '.print_r($qv,1)."\n\n";
										$mks_line = (string) self::process_loop_syntax(
											(string) $mks_line,
											(array) array_merge((array)$y_arr_vars, [ (string) $bind_var_key.'.'.strtoupper((string)$qk) => (array) $qv ]),
											(int) $level
										);
									} //end if
								} //end foreach
							} //end if
							//-- process the loop replacements
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_MAXSIZE_-', // no if context
								(string) ($mxcnt+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_INDEX_-', // no if context
								(string) ($j+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-MAXCOUNT-_',
								(string) $mxcnt
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-ITERATOR-_',
								(string) $j
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-KEY-_',
								(string) $j
							);
							if(is_array($y_arr_vars[(string)$bind_var_key][$j]) AND (Smart::array_type_test($y_arr_vars[(string)$bind_var_key][$j]) === 2)) {
								foreach($y_arr_vars[(string)$bind_var_key][$j] as $key => $val) { // expects associative array
									$mks_line = (string) self::replace_marker(
										(string) $mks_line,
										(string) $bind_var_key.'.'.'_-VAL-_'.'.'.strtoupper((string)$key),
										(string) (Smart::is_nscalar($val) ? $val : '')
									);
									$mks_line = (string) self::replace_marker(
										(string) $mks_line,
										(string) $bind_var_key.'.'.strtoupper((string)$key), // a shortcut for _-VAL-_.KEY
										(string) (Smart::is_nscalar($val) ? $val : '')
									);
								} //end foreach
							} else {
								$mks_line = (string) self::replace_marker(
									(string) $mks_line,
									(string) $bind_var_key.'.'.'_-VAL-_',
									(string) (Smart::is_nscalar($y_arr_vars[(string)$bind_var_key][$j]) ? $y_arr_vars[(string)$bind_var_key][$j] : '')
								);
							} //end if else
							//-- render
							$line .= (string) $mks_line;
							//--
						} //end for
						//--
					} elseif($arrtype === 2) { // 2: associative
						//--
						$j=0;
						$the_max = Smart::array_size($y_arr_vars[(string)$bind_var_key]);
						$mxcnt = (int) ($the_max - 1);
						//--
						foreach($y_arr_vars[(string)$bind_var_key] as $zkey => $zval) {
							//-- operate on a copy of original
							$mks_line = (string) $loop_orig;
							//--
							$ziterator = $j;
							$j++;
							//-- process IF inside LOOP for this context (the global context is evaluated prior as this function is called after process_if_syntax() in process_syntax() via render_template()
							if(strpos((string)$mks_line, '[%%%IF:') !== false) {
								$tmp_arr_context = array(); // init
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-MAXCOUNT-_'] = (string) $mxcnt;
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-ITERATOR-_'] = (string) $ziterator;
								$tmp_arr_context[(string)$bind_var_key.'.'.'_-KEY-_'] = (string) $zkey;
								if(is_array($zval)) {
									$tmp_arr_context[(string)$bind_var_key.'.'.'_-VAL-_'] = (array) $zval;
									$tmp_arr_context[(string)$bind_var_key.'.'.strtoupper((string)$zkey)] = (array) $zval;
									foreach($zval as $key => $val) { // expects associative array
										$tmp_arr_context[(string)$bind_var_key.'.'.'_-VAL-_'.'.'.strtoupper((string)$key)] = $val;
										$tmp_arr_context[(string)$bind_var_key.'.'.strtoupper((string)$zkey.'.'.(string)$key)] = $val;
									} //end foreach
								} else {
									$tmp_arr_context[(string)$bind_var_key.'.'.'_-VAL-_'] = (string) (Smart::is_nscalar($zval) ? $zval : '');
								} //end if else
								$mks_line = (string) self::process_if_syntax(
									(string) $mks_line,
									(array)  $y_arr_vars,
									(string) $bind_var_key,
									(array)  $tmp_arr_context
								);
								$tmp_arr_context = array(); // reset
							} //end if
							//-- process 2nd Level LOOP inside LOOP for Associative Array: base vars
							if(strpos((string)$mks_line, '[%%%LOOP:') !== false) {
								$mks_line = (string) self::process_loop_syntax(
									(string) $mks_line,
									(array) $y_arr_vars,
									(int) $level
								);
							} //end if
							//-- process 2nd Level LOOP inside LOOP for Associative Array
							if((strpos((string)$mks_line, '[%%%LOOP:') !== false) AND (is_array($zval))) {
								if(((strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.strtoupper((string)$zkey).'%') !== false) OR (strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.strtoupper((string)$zkey).'(') !== false)) AND (is_array($zval))) {
									//echo '***** ['.$bind_var_key.'.'.strtoupper((string)$zkey).'] = '.print_r($zval,1)."\n\n";
									$mks_line = (string) self::process_loop_syntax(
										(string) $mks_line,
										(array) array_merge((array)$y_arr_vars, [ (string) $bind_var_key.'.'.strtoupper((string)$zkey) => (array) $zval ]),
										(int) $level
									);
								} //end if
								if($level > 0) { // uxm-extra: process also _-VAL-_
									if(((strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.'_-VAL-_'.'%') !== false) OR (strpos((string)$mks_line, '[%%%LOOP:'.(string)$bind_var_key.'.'.'_-VAL-_'.'(') !== false)) AND (is_array($zval))) {
										//echo '***** ['.$bind_var_key.'.'.'_-VAL-_'.'] = '.print_r($zval,1)."\n\n";
										$mks_line = (string) self::process_loop_syntax(
											(string) $mks_line,
											(array) array_merge((array)$y_arr_vars, [ (string) $bind_var_key.'.'.'_-VAL-_' => (array) $zval ]),
											(int) $level
										);
									} //end if
								} //end if
							} //end if
							//-- process the loop replacements
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_MAXSIZE_-', // no if context
								(string) ($mxcnt+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_INDEX_-', // no if context
								(string) ($ziterator+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-MAXCOUNT-_',
								(string) $mxcnt
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-ITERATOR-_',
								(string) $ziterator
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'_-KEY-_',
								(string) $zkey
							);
							if(is_array($zval) AND (Smart::array_type_test($zval) === 2)) {
								foreach($zval as $key => $val) { // expects associative array
									$mks_line = (string) self::replace_marker(
										(string) $mks_line,
										(string) $bind_var_key.'.'.'_-VAL-_'.'.'.strtoupper((string)$key),
										(string) $val
									);
									$mks_line = (string) self::replace_marker(
										(string) $mks_line,
										(string) $bind_var_key.'.'.strtoupper((string)$zkey.'.'.(string)$key),
										(string) $val
									);
								} //end foreach
							} else {
								$mks_line = (string) self::replace_marker(
									(string) $mks_line,
									(string) $bind_var_key.'.'.'_-VAL-_',
									(string) (Smart::is_nscalar($zval) ? $zval : '')
								);
							} //end if else
							//-- render
							$line .= (string) $mks_line;
							//--
						} //end foreach
						//--
					} //end if else
					//--
				//	$mtemplate = (string) str_replace((string)$part_orig, (string)$line, (string)$mtemplate); // BUGFIX: If there are duplicate sub-ifs str replace without limit will replace other occurences not yet parsed and when the loop try to replace original part (which was modified by previous no limit replaces) will fail to find that part and replace it ...
					$mtemplate = (string) Smart::str_replace_first((string)$part_orig, (string)$line, (string)$mtemplate); // MUST REPLACE ONLY THE FIRST OCCURENCE because this function is recursive and the regex will already contain the original parts only and if a 2nd part is replaced but not yet parsed in this for loop the str replace will fail to find it
					//--
				} //end if else
				//--
			} //end for
			//--
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// detect marker sub-templates and returns an array with them
	private static function detect_subtemplates(string $mtemplate) {
		//--
		$arr_detected_sub_templates = array();
		//--
		if(self::have_subtemplate((string)$mtemplate) === true) {
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				$bench = microtime(true);
			} //end if
			//--
			if(self::$MkTplAnalyzeLdDbg === true) {
				$regex = '{\[@@@SUB\-TEMPLATE:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}'; // this is a special case for debug where % must be includded
			} else {
				$regex = '{\[@@@SUB\-TEMPLATE:([a-zA-Z0-9_\-\.\/\!\?\|]+)@@@\]}'; // here the % is missing as must not be detected as it is reserved only for special purpose if SUB-TPLS are pre-defined
			} //end if else
			//--
			$matches = array();
			$pcre = preg_match_all((string)$regex, (string)$mtemplate, $matches, PREG_SET_ORDER, 0); // {{{SYNC-TPL-EXPR-SUBTPL}}}
			if($pcre === false) {
				Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
				return array();
			} //end if
			//print_r($matches);
			//--
			if(Smart::array_size($matches) > 0) {
				//--
				for($i=0; $i<Smart::array_size($matches); $i++) {
					//--
					$matches[$i] = (array) $matches[$i];
					//--
					$part_path = (string) $matches[$i][1];
					//--
					$matches[$i] = null; // free mem
					//--
					if((string)trim((string)$part_path) != '') {
						if(self::have_subtemplate((string)$part_path) !== true) {
							$arr_detected_sub_templates[(string)$part_path] = '@'; // add detected sub-template only if it does not contain the sub-templates syntax to avoid unpredictable behaviours
						} //end if
					} //end if
					//--
				} //end for
				//--
			} //end if
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-Parsing:Evaluate] :: Marker-TPL / Detecting Sub-Templates ; Time = '.$bench.' sec.',
					'data' => 'Sub-Templates Detected: '.print_r($arr_detected_sub_templates,1)
				]);
			} //end if
			//--
		} //end if
		//--
		return (array) $arr_detected_sub_templates;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/*
	 * Inject marker sub-templates
	 * Limits: max 3 levels: template -> sub-template -> sub-sub-template ; max 127 cycles overall: template + sub-templates + sub-sub-templates)
	 *
	 * @throws			Smart::raise_error()
	 *
	 * @param ENUM 		$y_use_caching 				:: yes / no
	 * @param STRING 	$y_base_path 				:: TPL Base Path (to use for loading sub-templates if any)
	 * @param STRING 	$mtemplate 					:: TPL string to be parsed
	 * @param ARRAY 	$y_arr_vars_sub_templates 	:: Empty Array or Mappings Array [ '%sub-tpl1%' => '@/tpl1.htm', 'tpl2.htm' => '@', 'tpl3.htm' => 'path/to/this/tpl/' ]
	 * @return STRING 								:: the prepared marker template contents
	 */
	private static function load_subtemplates(string $y_use_caching, string $y_base_path, string $mtemplate, array $y_arr_vars_sub_templates, int $cycles=0, bool $process_sub_sub_templates=true) {
		//--
		$y_use_caching = (string) $y_use_caching;
		$y_base_path = (string) $y_base_path;
		$mtemplate = (string) $mtemplate;
		$y_arr_vars_sub_templates = (array) $y_arr_vars_sub_templates;
		$cycles = (int) $cycles;
		//--
		if((string)$y_base_path == '') {
			Smart::raise_error('Marker Template Load Sub-Templates: INVALID Base Path (Empty) ... / Template: '.$mtemplate);
			return (string) 'ERROR: (701) in '.__CLASS__;
		} //end if
		//--
		if(Smart::array_size($y_arr_vars_sub_templates) > 0) {
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				$bench = microtime(true);
			} //end if
			//--
			$dbgnfo = 'To check if this is an ERROR OR NOT try to debug this Marker-Template directly from the real usage context by using the master template (of which base path may be different) or by passing also the @SUB-TEMPLATES@ custom definition if used.';
			//--
			foreach($y_arr_vars_sub_templates as $key => $val) {
				//--
				$key = (string) $key;
				$val = (string) $val;
				//--
				$found = preg_match('/^[a-zA-Z0-9_\-\.\/\!\?\|%]+$/', $key);
				if($found === false) {
					Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
					return (string) $mtemplate;
				} //end if
				//--
				if(((string)$key != '') AND (strpos($key, '..') === false) AND (strpos($val, '..') === false) AND ($found)) { // {{{SYNC-TPL-EXPR-SUBTPL}}} :: + %
					//--
					if((string)$val == '') {
						//--
						$mtemplate = (string) str_replace(
							'[@@@SUB-TEMPLATE:'.$key.'@@@]',
							'', // clear (this is required for the cases the sub-templates must not includded in some cases: a kind of IF syntax)
							(string) $mtemplate
						);
						//--
						if(SmartFrameworkRuntime::ifDebug()) {
							SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
								'title' => '[TPL-Parsing:Load] :: Marker-TPL / Skipping Sub-Template File: Key='.$key.' ; *Path='.$val.' ; Cycle='.$cycles,
								'data' => 'Unset based on empty Path value ...'
							]);
						} //end if
						//--
					} else {
						//--
						$pfx = '';
						$sfx = '';
						$is_optional = false;
						$is_variable = false;
						//--
						if((string)substr($key, 0, 1) == '?') { // actually if a TPL is N/A it means not found, so caching per execution with or without it should be allowed !
							$key = (string) substr($key, 1);
							$pfx = '?';
							$is_optional = true;
						} //end if
						/* not necessary as TPL is being cached at read in raw format
						if((string)$pfx != '') {
							if((string)$y_use_caching == 'yes') {
								Smart::raise_error('Invalid Marker-TPL Optional Sub-Template (optional sub-templates cannot be rendered with caching enabled) for key: `'.$key.'` # `'.$stpl_path.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (702) in '.__CLASS__;
							} //end if
						} //end if
						*/
						//-- DO NOT MODIFY ORDER !
						if((string)substr($key, -7, 7) == '|syntax') {
							$key = (string) substr($key, 0, -7);
							$sfx = '|syntax';
						} elseif((string)substr($key, -11, 11) == '|syntaxhtml') {
							$key = (string) substr($key, 0, -11);
							$sfx = '|syntaxhtml';
						} elseif((string)substr($key, -5, 5) == '|html') {
							$key = (string) substr($key, 0, -5);
							$sfx = '|html';
						} elseif((string)substr($key, -3, 3) == '|js') {
							$key = (string) substr($key, 0, -3);
							$sfx = '|js';
						} elseif((string)substr($key, -14, 14) == '|js-tpl-encode') {
							$key = (string) substr($key, 0, -14);
							$sfx = '|js-tpl-encode';
						} //end if
						if((string)$sfx != '') {
							/* not necessary as TPL is being cached at read in raw format
							if((string)$y_use_caching == 'yes') {
								Smart::raise_error('Invalid Marker-TPL Sub-Template Escapings (escaped sub-templates cannot be rendered with caching enabled) for key: `'.$key.'` # `'.$stpl_path.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (703) in '.__CLASS__;
							} //end if
							*/
							$process_sub_sub_templates = false; // no syntax parsed if escaped ; must not load sub-templates because it is considered a flat string not a template that must be escaped somehow
						} //end if
						//--
						if(((string)substr($key, 0, 1) == '%') AND ((string)substr($key, -1, 1) == '%')) { // variable, only can be set programatically, full path to the template file is specified
							if(SmartFileSysUtils::check_if_safe_path($val) != 1) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template Path [%] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (704) in '.__CLASS__;
							} //end if
							if((string)substr($val, 0, 2) == '@/') { // use a path suffix relative path to parent template, starting with @/ ; otherwise the full relative path is expected
								$val = (string) SmartFileSysUtils::add_dir_last_slash((string)$y_base_path).substr($val, 2);
							} //end if
							$stpl_path = (string) $val;
							$is_variable = true;
						} elseif(strpos($key, '%') !== false) { // % is not valid in other circumstances
							Smart::raise_error('Invalid Marker-TPL Sub-Template Syntax [%] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
							return (string) 'ERROR: (705) in '.__CLASS__;
						} elseif(((string)substr($key, 0, 1) == '!') AND ((string)substr($key, -1, 1) == '!')) { // path override: use this relative path instead of parent relative referenced path ; Ex: [@@@SUB-TEMPLATE:!etc/templates/default/js-base.inc.htm!@@@]
							$stpl_path = (string) substr($key, 1, -1);
						} elseif(strpos($key, '!') !== false) { // ! is not valid in other circumstances
							Smart::raise_error('Invalid Marker-TPL Sub-Template Syntax [!] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
							return (string) 'ERROR: (706) in '.__CLASS__;
						} else {
							if(SmartFileSysUtils::check_if_safe_path($val) != 1) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template Path [*] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (707) in '.__CLASS__;
							} //end if
							if((string)$val == '@') { // use the same dir as parent
								$val = (string) $y_base_path;
							} elseif((string)substr($val, 0, 2) == '@/') { // use a path suffix relative to parent template, starting with @/
								$val = (string) SmartFileSysUtils::add_dir_last_slash((string)$y_base_path).substr($val, 2);
							} //end if
							$stpl_path = (string) SmartFileSysUtils::add_dir_last_slash($val).$key;
						} //end if else
						//--
						if(($is_optional === true) OR (self::$MkTplAnalyzeLdDbg === true)) { // for Analyze Make just TRY TO Load Sub-TPLs to avoid load errors if the paths are defined in the TPL-Load Array not in the TPL
							$stemplate = '';
							if(SmartFileSystem::is_type_file((string)$stpl_path)) {
								$stemplate = (string) self::read_template_or_subtemplate_file((string)$stpl_path, (string)$y_use_caching); // read
							} elseif(self::$MkTplAnalyzeLdDbg === true) {
								if($is_variable === true) {
									$stemplate = "\n".'{@ *****'."\n".'Marker-TPL ANALYSIS INFO: THIS IS A *VARIABLE* SUB-TEMPLATE: '.$key.' # using the implicit base path: '.$val.' #'."\n".'The variable Sub-Templates must be specified in the real usage context using the @SUB-TEMPLATES@ custom definition.'."\n".'***** @}'."\n";
								} elseif($is_optional === true) {
									$stemplate = "\n".'{@ *****'."\n".'Marker-TPL ANALYSIS INFO: COULD NOT FIND TO INCLUDE THE *OPTIONAL* SUB-TEMPLATE: '.$key.' # using the implicit base path: '.$val.' #'."\n".'The optional Sub-Templates may be or may be not available or they can be specified in the real usage context using the @SUB-TEMPLATES@ custom definition or the base path of the master template may be different.'."\n".$dbgnfo."\n".'***** @}'."\n";
								} else {
									$stemplate = "\n".'{@ *****'."\n".'Marker-TPL ANALYSIS WARNING: FAILED TO INCLUDE THE SUB-TEMPLATE: '.$key.' # using the implicit base path: '.$val.' #'."\n".'If the PATHS for the Sub-Templates are defined in the real usage context using the @SUB-TEMPLATES@ custom definition or the base path of the master template is different THIS IS NOT AN ERROR.'."\n".'But if there is no @SUB-TEMPLATES@ custom definition in the real usage context and the base path of the master template is the same it means THIS IS AN ERROR and this particular Sub-Template cannot be found ...'."\n".$dbgnfo."\n".'***** @}'."\n";
								} //end if else
							} //end if else
						} else {
							if(!SmartFileSystem::is_type_file((string)$stpl_path)) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template File for key: `'.$key.'` # `'.$stpl_path.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (708) in '.__CLASS__;
							} //end if
							$stemplate = (string) self::read_template_or_subtemplate_file((string)$stpl_path, (string)$y_use_caching); // read
						} //end if else
						//--
						if($process_sub_sub_templates === true) {
							$arr_sub_sub_templates = (array) self::detect_subtemplates((string)$stemplate); // detect sub-sub templates
							$num_sub_sub_templates = Smart::array_size($arr_sub_sub_templates);
							if($num_sub_sub_templates > 0) {
								$stemplate = (string) self::load_subtemplates((string)$y_use_caching, (string)$y_base_path, (string)$stemplate, (array)$arr_sub_sub_templates, (int)$cycles, false); // this is level 3 !!
								$cycles += $num_sub_sub_templates;
							} //end if
						} //end if
						//-- DO NOT MODIFY ORDER ! escapings must be before detecting and fixing unattended syntax ; any sequence from below must escape at least with prepare_nosyntax_content() or prepare_nosyntax_html_template() to avoid reparsing syntax
						if((string)$sfx == '|syntax') {
							$stemplate = (string) self::prepare_nosyntax_content((string)$stemplate); // fix here
						} elseif((string)$sfx == '|syntaxhtml') {
							$stemplate = (string) self::prepare_nosyntax_html_template((string)$stemplate); // fix here
						} elseif((string)$sfx == '|html') {
							$stemplate = (string) Smart::escape_html((string)$stemplate);
							$stemplate = (string) self::prepare_nosyntax_html_template((string)$stemplate); // fix after
						} elseif((string)$sfx == '|js') { // this is used to pass a tpl to a javascript string
							$stemplate = (string) self::prepare_nosyntax_content((string)$stemplate); // fix before
							$stemplate = (string) Smart::escape_js((string)$stemplate);
						} elseif((string)$sfx == '|js-tpl-encode') { // this is used to pass a tpl to js for render in js
							$stemplate = (string) self::escape_template((string)$stemplate, 'yes'); // fix here ; no need to fix before or after as encode (url escape) will escape all sequences to avoid conflicts
							$stemplate = (string) Smart::escape_js((string)$stemplate);
						} //end if
						//-- fix unattended syntax
						if(self::have_subtemplate((string)$stemplate) === true) {
							if(self::$MkTplAnalyzeLdDbg !== true) { // if analyze TPL don't log to notice (because the [@@@SUB-TEMPLATE:%variable@@@] may not load always the variable replacements !!!
								$arr_subtpls = (array) self::analize_extract_subtpls($stemplate);
								Smart::log_notice('Invalid or Undefined Marker-TPL: Marker Sub-Templates detected in Template:'."\n".'SUB-TEMPLATES:'.print_r($arr_subtpls,1)."\n".self::log_template($stemplate));
							} //end if
							$stemplate = (string) str_replace(array('[@@@', '@@@]'), array('⁅@@@¦', '¦@@@⁆'), (string)$stemplate); // protect against cascade recursion or undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
						} //end if
						$mtemplate = (string) str_replace('[@@@SUB-TEMPLATE:'.$pfx.$key.$sfx.'@@@]', (string)$stemplate, (string)$mtemplate); // do replacements
						$arr_sub_sub_templates = array();
						$num_sub_sub_templates = 0;
						//--
						if(SmartFrameworkRuntime::ifDebug()) {
							SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
								'title' => '[TPL-Parsing:Load] :: Marker-TPL / INCLUDE Sub-Template File: Key='.$key.' ; Path='.$stpl_path.' ; Cycle='.$cycles,
								'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($stemplate)
							]);
						} //end if
						//--
						$stemplate = '';
						//--
					} //end if else
					//--
				} else { // invalid key
					//--
					Smart::log_warning('Invalid Marker-TPL Sub-Template Key: '.$key.' or Value: '.$val);
					//--
				} //end if else
				//--
				$cycles++;
				if($cycles > 127) { // protect against infinite loop, max 127 loops (incl. sub-sub templates) :: hard limit
					Smart::log_warning('Marker-TPL: Inclusion of the Sub-Template: '.$stpl_path.' failed as it overflows the maximum hard limit: only 127 loops (sub-templates) are allowed. Current Cycle is: #'.$cycles);
					break;
				} //end if
				//--
			} //end foreach
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-Parsing:Load.DONE] :: Marker-TPL / INCLUDE Sub-Templates Completed ; Time = '.$bench.' sec.',
					'data' => 'Total Cycles: '.$cycles
				]);
			} //end if
			//--
		} //end if
		//--
		if(self::have_subtemplate((string)$mtemplate) === true) {
			if(self::$MkTplAnalyzeLdDbg !== true) { // if analyze TPL don't log to notice (because the [@@@SUB-TEMPLATE:%variable@@@] may not load always the variable replacements !!!
				$arr_subtpls = (array) self::analize_extract_subtpls($mtemplate);
				Smart::log_notice('Invalid or Undefined Marker-TPL: Marker Sub-Templates detected in Template:'."\n".'SUB-TEMPLATES:'.print_r($arr_subtpls,1)."\n".self::log_template($mtemplate));
			} //end if
			$mtemplate = (string) str_replace(array('[@@@', '@@@]'), array('⁅@@@¦', '¦@@@⁆'), (string)$mtemplate); // finally protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function read_from_fs_or_pcache_the_template_file(string $y_file_path) {
		//--
		// This function uses static read from filesystem and if (memory) persistent cache is available will cache it and all future reads until key expire will be done from memory instead of overloading the file system
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(SmartFileSysUtils::check_if_safe_path($y_file_path) != 1) {
			Smart::log_warning('Invalid Path for Marker-TPL Read TPL File: '.$y_file_path);
			return ''; // OK empty
		} //end if
		//--
		$use_pcache = false;
		$ptime_cache = 0;
		if(!SmartFrameworkRuntime::ifDebug()) {
			if(defined('SMART_SOFTWARE_MKTPL_PCACHETIME')) {
				if(is_int(SMART_SOFTWARE_MKTPL_PCACHETIME)) {
					if(((int)SMART_SOFTWARE_MKTPL_PCACHETIME >= 0) AND ((int)SMART_SOFTWARE_MKTPL_PCACHETIME <= 31622400)) { // 0 unlimited ; 1 sec .. 366 days
						$use_pcache = true;
						$ptime_cache = (int) SMART_SOFTWARE_MKTPL_PCACHETIME;
					} //end if
				} //end if
			} //end if
		} //end if
		if(($use_pcache === true) AND SmartPersistentCache::isActive() AND SmartPersistentCache::isMemoryBased()) {
			$the_cache_key = SmartPersistentCache::safeKey('tpl__'.Smart::base_name((string)$y_file_path).'__'.sha1((string)$y_file_path));
		} else {
			$the_cache_key = '';
		} //end if else
		//--
		$tpl = '';
		//--
		if((string)$the_cache_key != '') {
			if(SmartPersistentCache::keyExists('smart-markertpl-cache', (string)$the_cache_key)) {
				$tpl = (string) SmartPersistentCache::getKey('smart-markertpl-cache', (string)$the_cache_key);
				if((string)$tpl != '') {
					//Smart::log_info('TPL found in cache: '.$y_file_path);
					$tpl = (string) SmartPersistentCache::varUncompress((string)$tpl);
					if((string)$tpl != '') {
						//Smart::log_info('TPL from cache is OK: '.$y_file_path);
						return (string) $tpl; // return from persistent cache
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		$tpl = (string) self::do_read_template_file_from_fs((string)$y_file_path);
		//--
		if((string)$the_cache_key != '') {
			if((string)$tpl != '') {
				//Smart::log_info('TPL fs-read OK: '.$y_file_path);
				$atpl = (string) SmartPersistentCache::varCompress((string)$tpl);
				if((string)$atpl != '') {
					//Smart::log_info('TPL saved in cache: '.$y_file_path);
					SmartPersistentCache::setKey('smart-markertpl-cache', (string)$the_cache_key.'__path', (string)$y_file_path, (int)$ptime_cache); // set to persistent cache
					SmartPersistentCache::setKey('smart-markertpl-cache', (string)$the_cache_key, (string)$atpl, (int)$ptime_cache); // set to persistent cache
				} //end if
				$atpl = '';
			} //end if
		} //end if
		//--
		return (string) $tpl; // return from fs read
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function read_template_or_subtemplate_file(string $y_file_path, string $y_use_caching) {
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(self::$MkTplAnalyzeLdDbg === true) {
			if(!array_key_exists((string)$y_file_path, self::$MkTplAnalyzeLdRegDbg)) {
				self::$MkTplAnalyzeLdRegDbg[(string)$y_file_path] = 0;
			} //end if
			self::$MkTplAnalyzeLdRegDbg[(string)$y_file_path] += 1;
		} //end if
		//--
		$cached_key = 'read_template_or_subtemplate_file:'.$y_file_path; // {{{SYNC-TPL-DEBUG-CACHED-KEY}}}
		//--
		if(array_key_exists((string)$cached_key, (array)self::$MkTplCache)) {
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Includding a Sub-Template from VCache';
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-VCache] :: Marker-TPL / File-Read ; Serving from VCache the File Template: '.$y_file_path.' ; VCacheFlag: '.$y_use_caching,
					'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit(self::$MkTplCache[(string)$cached_key])
				]);
			} //end if
			//--
			return (string) self::$MkTplCache[(string)$cached_key];
			//--
		} //end if
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			if(!array_key_exists((string)$cached_key, self::$MkTplFCount)) {
				self::$MkTplFCount[(string)$cached_key] = 0;
			} //end if
			self::$MkTplFCount[(string)$cached_key]++; // register to counter anytime is read from FileSystem
		} //end if
		//--
		if((string)$y_use_caching == 'yes') {
			//--
			self::$MkTplCache[(string)$cached_key] = (string) self::read_from_fs_or_pcache_the_template_file($y_file_path);
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Reading a Sub-Template from FS and REGISTER in VCache';
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-FS-Register-In-VCache] :: Marker-TPL / Registering to VCache the File Template: '.$y_file_path.' ;',
					'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit(self::$MkTplCache[(string)$cached_key])
				]);
			} //end if
			//--
			return (string) self::$MkTplCache[(string)$cached_key];
			//--
		} else {
			//--
			$mtemplate = (string) self::read_from_fs_or_pcache_the_template_file($y_file_path);
			//--
			if(SmartFrameworkRuntime::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Reading a Sub-Template from FS ; VCacheFlag: '.$y_use_caching;
				SmartFrameworkRegistry::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-FS] :: Marker-TPL / File-Read ; Serving from FS the File Template: '.$y_file_path.' ;',
					'data' => 'Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
				]);
			} //end if
			//--
			return (string) $mtemplate;
			//--
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function do_read_template_file_from_fs(string $y_file_path) {
		//--
		if((strpos((string)$y_file_path, '.php.') !== false) OR (substr((string)$y_file_path, -4, 4) == '.php')) {
			Smart::raise_error('ERROR: Invalid Marker-TPL File Path (PHP File Extension should not be used for a template): '.$y_file_path);
			return (string) 'ERROR: (401) in '.__CLASS__;
		} //end if
		//--
		return (string) SmartFileSystem::read((string)$y_file_path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function log_template(string $mtemplate) {
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			return (string) $mtemplate;
		} else {
			return (string) SmartUnicode::sub_str($mtemplate, 0, 255)."\n".'***** turn on Debugging to see more ... *****';
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//===== DEBUG ONLY


	//================================================================
	private static function debug_tpl_cut_by_limit(string $mtemplate) {
		//--
		$len = (int) self::debug_tpl_length();
		//--
		return (string) Smart::text_cut_by_limit((string)$mtemplate, (int)$len, true, '[...]');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function debug_tpl_length() {
		//--
		$len = 255; // default
		if(defined('SMART_SOFTWARE_MKTPL_DEBUG_LEN')) {
			if((int)SMART_SOFTWARE_MKTPL_DEBUG_LEN >= 255) {
				if((int)SMART_SOFTWARE_MKTPL_DEBUG_LEN <= 524280) {
					$len = (int) SMART_SOFTWARE_MKTPL_DEBUG_LEN;
				} //end if
			} //end if
		} //end if
		$len = Smart::format_number_int($len,'+');
		//--
		return (int) $len;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerOptimizationHintsToDebugLog() {
		//--
		if(SmartFrameworkRuntime::ifDebug()) {
			//--
			$optim_msg = [];
			foreach(self::$MkTplFCount as $key => $val) {
				$key = (string) $key;
				if(strpos($key, 'debug') === false) { // avoid hints for debug templates / sub-templates
					$is_direct_read = false;
					if(strpos($key, 'read_template_file:') === 0) {
						$is_direct_read = true;
					} //end if
					$key = (array) explode(':', $key);
					if(!array_key_exists(1, $key)) {
						$key[1] = null;
					} //end if
					$key = (string) $key[1];
					$val = (int) $val;
					if($val > 1) {
						$optim_msg[] = [
							'optimal' => false,
							'value' => (int) $val,
							'key' => (string) $key,
							'msg' => $is_direct_read ? '(Optimization Hint: Try to nou use direct read many times for Rendering this Template to avoid multiple reads on FileSystem)' : 'Optimization Hint: Set Caching Parameter for Rendering this (Sub)Template to avoid multiple reads on FileSystem',
							'action' => 'debug-tpl'
						];
					} else {
						$optim_msg[] = [
							'optimal' => true,
							'value' => (int) $val,
							'key' => (string) $key,
							'msg' => $is_direct_read ? '(OK)' : 'OK',
							'action' => 'debug-tpl'
						];
					} //end if else
				} //end if
			} //end foreach
			SmartFrameworkRegistry::setDebugMsg('optimizations', '*SMART-CLASSES:OPTIMIZATION-HINTS*', [
				'title' => 'SmartMarkersTemplating // Optimization Hints @ Number of FS Reads for Rendering the current Template incl. Sub-Templates',
				'data' => (array) $optim_msg
			]);
			//--
			$optim_msg = [];
			foreach(self::$MkTplVars as $key => $val) {
				$counter = Smart::array_size($val);
				if($counter > 0) {
					$optim_msg[] = [
						'optimal' => null,
						'value' => (int) $counter,
						'key' => (string) $key,
						'msg' => (string) implode(' ; ', array_unique($val)),
						'action' => ''
					];
				} //end if
			} //end foreach
			SmartFrameworkRegistry::setDebugMsg('optimizations', '*SMART-CLASSES:OPTIMIZATION-HINTS*', [
				'title' => 'SmartMarkersTemplating // Optimization Notices @ Rendering Details of the current Template incl. Sub-Templates',
				'data' => (array) $optim_msg
			]);
			//--
			$optim_msg = [];
			//--
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function registerInternalCacheToDebugLog() {
		//--
		if(SmartFrameworkRuntime::ifInternalDebug()) {
			if(SmartFrameworkRuntime::ifDebug()) {
				SmartFrameworkRegistry::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartMarkersTemplating // Internal Cache',
					'data' => 'Dump of Cached Templates / Sub-Templates:'."\n".print_r(self::$MkTplCache,1)
				]);
			} //end if
		} //end if
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
