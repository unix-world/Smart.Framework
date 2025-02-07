<?php
// [LIB - Smart.Framework / Marker-TPL Templating]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Marker-TPL Templating
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

//===== INFO:
// Marker-TPL Templating Engine is a very fast and very secure [*] PHP Templating Engine.
// Because the Marker-TPL Templating is rendering the Views by injecting plain strings and data arrays directly into these Views (no PHP code, no re-interpreted PHP code) there is NO SECURITY RISK by injecting malicious PHP code into the Views
// It does support: MARKERS, IF/ELSE, LOOP, INCLUDE syntax.
// Nested identic IF/ELSE or nested identic LOOP syntax must be separed with unique terminators such as: (1), (2), ...
// For IF/ELSE syntax variable order matters for comparison if used inside LOOP ; when comparing a (special context) variable inside a LOOP with another variable (from out of this context), the LOOP context variable must be placed in the left side, otherwise the comparison will fail as the left variable may be evaluated prior the LOOP variable to be initialized ...
// For nested LOOP it only supports max 5 nested levels (combining more levels would be inefficient - because of the exponential structure complexity of context data, such as metadata context that must be replicated)
// 		-_MAXSIZE_- 		The max array index = arraysize ;Available also in LOOP / IF
// 		_-MAXCOUNT-_ 		The max iterator of array: arraysize-1 ; Available also in LOOP / IF
// 		-_INDEX_- 			The current array index: 1..arraysize ; Available also in LOOP / IF
// 		_-ITERATOR-_		The current array iterator: 0..(arraysize-1) ; Available also in LOOP / IF
// 		_-VAL-_				The current loop value ; Available also in LOOP / IF
// 		_-KEY-_				The current loop key ; Available also in LOOP / IF
//===== TECHNICAL REFERENCE:
// Because the recursion patterns are un-predictable, as a template can be rendered in other template in controllers or libs,
// the str_replace() is used internally instead of strtr() but with an important fix: will replace all values before assign as follows:
// `[###` => `⁅###¦` ; `###]` => `¦###⁆` ; `[%%%` => `⁅%%%¦` ; `%%%]` => `¦%%%⁆` ; `[@@@` -> `⁅@@@¦` ; `@@@]` -> `¦@@@⁆`
// in order to protect against unwanted or un-predictable recursions / replacements on the values will replace `]` with `］` as:
// `[###` => `［###` ; `###]` => `###］` ; `[%%%` => `［%%%` ; `%%%]` => `%%%］` ; `[@@@` -> `［@@@` ; `@@@]` -> `@@@］`
//=====

/**
 * Class: SmartMarkersTemplating - provides a very fast and low footprint templating system: Marker-TPL
 * Max template size to parse is limited to 16MB
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartHashCrypto, SmartEnvironment, SmartUnicode, SmartFileSysUtils ; constants: SMART_FRAMEWORK_ERR_PCRE_SETTINGS, SMART_SOFTWARE_MKTPL_DEBUG_LEN (optional)
 * @version 	v.20250126
 * @package 	@Core:TemplatingEngine
 *
 */
final class SmartMarkersTemplating {

	// ::

	// syntax: r.20250126

	private static $MkTplAnalyzeLdDbg 		= false; 	// flag for template analysis
	private static $MkTplAnalyzeLdRegDbg 	= []; 		// registry of template analysis

	private static $MkTplVars 				= []; 		// registry of template variables
	private static $MkTplFCount 			= []; 		// counter to register how many times a template / sub-template file is read from filesystem (can be used for optimizations)
	private static $MkTplCache 				= []; 		// registry of cached template data


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
	public static function analyze_debug_template(string $mtemplate) : string {
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
	public static function analyze_debug_file_template(string $y_file_path, array $y_arr_sub_templates=[]) : string {
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(self::is_a_valid_relative_file_path_and_exists((string)$y_file_path) !== true) {
			return '<h1>{### ERROR: Invalid Marker-TPL File Path ['.Smart::escape_html($y_file_path).'] ###}</h1>';
		} //end if
		//--
		$y_arr_sub_templates = (array) $y_arr_sub_templates;
		//--
		$mtemplate = (string) self::read_from_fs_the_template_file((string)$y_file_path);
		$original_mtemplate = (string) $mtemplate;
		//-- add TPL START/END to see where it starts load
		$matches = array();
		$pcre = preg_match_all('{\[@@@SUB\-TEMPLATE\:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}', (string)$mtemplate, $matches, PREG_SET_ORDER, 0); // FIX: add an extra % to parse also SUB-TPL %vars% # {{{SYNC-TPL-EXPR-SUBTPL}}} :: + %
		if($pcre === false) {
			return '<h1>{### ERROR: '.Smart::escape_html((string)SMART_FRAMEWORK_ERR_PCRE_SETTINGS).'] ###}</h1>';
		} //end if
		//die('<pre>'.Smart::escape_html(print_r($matches,1)).'</pre>');
		for($i=0; $i<Smart::array_size($matches); $i++) {
			$mtemplate = (string) str_replace((string)$matches[$i][0], '<!-- ⁅***¦SUB-TEMPLATE:'.(string)$matches[$i][1].'(*****INCLUDE:START{*****)¦***⁆ -->'.(string)$matches[$i][0].'<!-- ⁅***¦SUB-TEMPLATE:'.(string)$matches[$i][1].'(*****}INCLUDE:END*****)¦***⁆ -->', (string)$mtemplate);
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
			$tpl_basepath = (string) SmartFileSysUtils::addPathTrailingSlash((string)SmartFileSysUtils::extractPathDir((string)$y_file_path));
			$mtemplate = (string) self::load_subtemplates('no', (string)$tpl_basepath, (string)$mtemplate, (array)$arr_sub_templates); // load sub-templates before template processing and use caching also for sub-templates if set
			$mtemplate = (string) str_replace(['⁅@@@¦', '¦@@@⁆'], ['[@@@', '@@@]'], (string)$mtemplate); // FIX: revert protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
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
	 * NOTICE: This kind of escaped templates can be rendered by client-side javascript from a javascript variable in a HTML page using smartJ$Utils.renderMarkersTpl() function (not all features of the server-side Marker Templating are supported, see the smartJ$Utils documentation ...)
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
	public static function escape_template(string $mtemplate, string $y_ignore_if_empty='no') : string {
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
	 * Render Placeholder Template (String Template ; no marker syntax is parsed ; only placeholders are strict replaced
	 * This method adds a new feature to the Marker-TPL Templating system to allow add post-render variable areas to an already rendered Marker-TPL ; for example, a rendered Marker-TPL can be exported to cache but some areas perhaps need to be changed later so those areas can be replaced later by using placeholders
	 *
	 * @param 	STRING 		$ptemplate 						:: The Placeholder-TPL string (partial text/html + placeholders) ; Ex: '<span>[:::PLACEHOLDER:::], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template placeholders is case insensitive ; Ex: [ 'PLACEHOLDER1' => 'Value1', 'placeholder2' => 'Value2', ..., 'PlaceholderN' => 100 ]
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Placeholder-TPL is empty ; 'no' will add a warning (default)
	 *
	 * @return 	STRING										:: The parsed template
	 *
	 */
	public static function render_placeholder_tpl(string $ptemplate, array $y_arr_vars, string $y_ignore_if_empty='no') : string {
		//--
		$y_ignore_if_empty = (string) $y_ignore_if_empty;
		//-- do not trim partial template, to be consistent with render file template
		if((string)trim((string)$ptemplate) == '') {
			if((string)$y_ignore_if_empty != 'yes') {
				Smart::log_warning('Empty Placeholder-TPL Content: '.print_r($y_arr_vars,1));
				return (string) '{### ERROR: Empty Placeholder-TPL Template ###}';
			} //end if
			return '';
		} //end if
		//--
		if((int)strlen((string)$ptemplate) > (int)Smart::SIZE_BYTES_16M) { // {{{SYNC-TPL-MAX-SIZE}}}
			Smart::log_warning('OverSized Placeholder-TPL Content: '.print_r($y_arr_vars,1));
			return (string) '{### ERROR: OverSized Placeholder-TPL Template ###}';
		} //end if
		//--
		if((!is_array($y_arr_vars)) OR ((Smart::array_size($y_arr_vars) > 0) AND (Smart::array_type_test($y_arr_vars) != 2))) {
			Smart::log_warning('Invalid Placeholder-TPL Data-Set for Template: '.$ptemplate.' # '.print_r($y_arr_vars,1));
			return (string) $ptemplate; // no prepare syntax here, leave untouched
		} //end if
		//--
		if(!self::have_placeholder((string)$ptemplate)) {
			return (string) $ptemplate;
		} //end if
		//--
		$arr = [];
		foreach((array)$y_arr_vars as $key => $val) {
			if(Smart::is_nscalar($val)) {
				$key = (string) trim((string)$key);
				if((string)$key != '') {
					//-- {{{SYNC-TPL-EXPR-PLACEHOLDER}}}
					$valid = preg_match('/^[A-Z0-9_\-]+$/', (string)$key); // returns 1 if the pattern matches given subject, 0 if it does not, or false on failure
					if($valid === false) {
						Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
					} //end if
					//-- #end sync
					if($valid) {
						$arr[(string)'[:::'.strtoupper((string)$key).':::]'] = (string) $val;
					} //end if
				} //end if
			} //end if
		} //end foreach
		//--
		if(Smart::array_size($arr) <= 0) {
			return (string) $ptemplate;
		} //end if
		//--
		$ptemplate = (string) strtr((string)$ptemplate, (array)$arr); // use strtr no str_replace to avoid recursion !
		//--
		return (string) $ptemplate; // no prepare syntax here, leave untouched
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Render Marker Template (String Template ; no sub-templates are allowed as there is no possibility to set a relative path from where to get them)
	 * See the escapings and transformation at: Render Marker File Template
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string (partial text/html + markers) ; Ex: '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template markers is case insensitive ; Ex: [ 'MARKER1' => 'Value1', 'marker2' => 'Value2', ..., 'MarkerN' => 100 ]
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Marker-TPL is empty ; 'no' will add a warning (default)
	 *
	 * @return 	STRING										:: The parsed template
	 *
	 */
	public static function render_template(string $mtemplate, array $y_arr_vars, string $y_ignore_if_empty='no') : string {
		//--
		$y_ignore_if_empty = (string) $y_ignore_if_empty;
		//-- do not trim partial template, to be consistent with render file template
		if((string)trim((string)$mtemplate) == '') {
			if((string)$y_ignore_if_empty != 'yes') {
				Smart::log_warning('Empty Marker-TPL Content: '.print_r($y_arr_vars,1));
				return (string) '{### ERROR: Empty Marker-TPL Template ###}';
			} //end if
			return '';
		} //end if
		//--
		if((int)strlen((string)$mtemplate) > (int)Smart::SIZE_BYTES_16M) { // {{{SYNC-TPL-MAX-SIZE}}}
			Smart::log_warning('OverSized Placeholder-TPL Content: '.print_r($y_arr_vars,1));
			return (string) '{### ERROR: OverSized Placeholder-TPL Template ###}';
		} //end if
		//--
		if((!is_array($y_arr_vars)) OR ((Smart::array_size($y_arr_vars) > 0) AND (Smart::array_type_test($y_arr_vars) != 2))) {
			Smart::log_warning('Invalid Marker-TPL Data-Set for Template: '.$mtemplate.' # '.print_r($y_arr_vars,1));
			return (string) self::prepare_nosyntax_content($mtemplate);
		} //end if
		//-- make all keys upper
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / Render ; Ignore if Empty: '.$y_ignore_if_empty,
				'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//-- avoid use the sub-templates array later than this point ... not needed and safer to unset
		if(array_key_exists('@SUB-TEMPLATES@', (array)$y_arr_vars)) {
			unset($y_arr_vars['@SUB-TEMPLATES@']);
		} //end if
		$mtemplate = (string) str_replace(['[@@@', '@@@]'], ['⁅@@@¦', '¦@@@⁆'], (string)$mtemplate); // finally protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
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
	 * // [###MARKER|{escapes}] 									:: marker with escapes (one or many of): |bool |int |dec[1-4]{1} |num |date |datetime |datetimez |htmid |jsvar |stdvar |nobackslash |rxpattern |emptye |emptyna |idtxt |slug |substr[0-9]{1,5} |subtxt[0-9]{1,5} |lower |upper |ucfirst |ucwords |trim |url |json |jsonpretty |js |html |xml |css |nl2br |striptags |syntaxhtml |hex |hexi10 |b64 |b64s |b64tob64s |b64stob64 |b32 |b36 |b58 |b62 |b85 |b92 |crc32b |crc32b36 |md5 |md5b64 |sha1 |sha1b64 |sha224 |sha224b64 |sha256 |sha256b64 |sha384 |sha384b64 |sha512 |sha512b64 |sh3a224 |sh3a224b64 |sh3a256 |sh3a256b64 |sh3a384 |sh3a384b64 |sh3a512 |sh3a512b64
	 * // [@@@SUB-TEMPLATE:path/to/tpl.htm@@@] 				:: sub-template with relative path to template
	 * // [@@@SUB-TEMPLATE:?path/to/tpl.htm@@@] 				:: sub-template with relative path to template, optional, if exists
	 * // [@@@SUB-TEMPLATE:!etc/path/to/tpl.htm!@@@] 		:: sub-template with relative path to framework, using exact this path
	 * // [@@@SUB-TEMPLATE:?!if-exists/path/to/tpl.htm!@@@] 	:: sub-template with relative path to framework, using exact this path, optional, if exists
	 * // [@@@SUB-TEMPLATE:{tpl}|{escape}@@@] 				:: sub-template (using any kind of path from above), apply escape (any of): |syntax |syntaxhtml |html |xml |js |js-tpl-encode |tpl-uri-encode |tpl-b64-encode
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
	 * @param 	ENUM 		$y_use_caching 					:: 'yes' will cache the template (incl. sub-templates if any) into memory to avoid re-read them from file system (to be used if a template is used more than once per execution) ; 'no' means no caching is used ; default is YES
	 *
	 * @return 	STRING										:: The parsed and rendered template
	 *
	 */
	public static function render_file_template(string $y_file_path, array $y_arr_vars, string $y_use_caching='yes') : string {
		//--
		// it can *optional* use caching to avoid read a file template (or it's sub-templates) more than once per execution
		// if using the cache the template and also sub-templates (if any) are cached internally to avoid re-read them from filesystem
		// the replacement of sub-templates is made before injecting variables to avoid security issues
		//--
		// {{{SYNC-TPL-MAX-SIZE}}} ; the max allowed size is Smart::SIZE_BYTES_16M ; the size control is in another method, the one that reads from disk
		//--
		$y_file_path = (string) $y_file_path;
		//--
		if(self::is_a_valid_relative_file_path_and_exists((string)$y_file_path) !== true) {
			Smart::log_warning('Invalid Marker-TPL File Path: '.$y_file_path);
			return (string) '{### ERROR: Invalid Marker-TPL File Path ###}';
		} //end if
		//--
		$y_use_caching = (string) $y_use_caching;
		//--
		$mtemplate = (string) self::read_from_optimal_place_the_template_file((string)$y_file_path, (string)$y_use_caching);
		if((string)$mtemplate == '') {
			Smart::log_warning('Empty or Un-Readable Marker-TPL File: '.$y_file_path);
			return (string) '{### ERROR: Empty or Un-Readable Marker-TPL File ###}';
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / File-Render: '.$y_file_path,
				'data' => 'Caching: '.$y_use_caching
			]);
		} //end if
		//--
		if((!is_array($y_arr_vars)) OR ((Smart::array_size($y_arr_vars) > 0) AND (Smart::array_type_test($y_arr_vars) != 2))) {
			Smart::log_warning('Invalid Marker-TPL Data-Set for Template file: '.$y_file_path);
			return (string) self::prepare_nosyntax_content($mtemplate);
		} //end if
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//--
		$arr_sub_templates = (array) self::detect_subtemplates($mtemplate);
		if(array_key_exists('@SUB-TEMPLATES@', $y_arr_vars) AND (Smart::array_size($y_arr_vars['@SUB-TEMPLATES@']) > 0) AND (Smart::array_type_test($y_arr_vars['@SUB-TEMPLATES@']) == 2)) {
			foreach($y_arr_vars['@SUB-TEMPLATES@'] as $key => $val) {
				if((string)trim((string)$key) != '') {
					if(Smart::is_nscalar($val)) {
						if( // add only variable sub-tpls + rewrite the existing sub-tpls paths, no new keys should be added
							(
								((string)substr((string)$key, 0, 1) == '%') // {{{SYNC-TPLS-VARIABLE-SUB-TPL-CONDITION}}} // must not test here for ending % as the key might be as: %the-tpl%|html-no-subtpls
								AND
								(strpos((string)$mtemplate, '[@@@SUB-TEMPLATE:'.(string)$key.'@@@]') !== false)
							)
							OR
							array_key_exists((string)$key, (array)$arr_sub_templates)
						) {
							$arr_sub_templates[(string)$key] = (string) $val;
						} //end if
						// no need to log notice if not matched, the rest of keys are validated in later stages of TPL rendering
					} //end if
				} //end if
			} //end foreach
		} //end if else
		if(Smart::array_size($arr_sub_templates) > 0) {
			$tpl_basepath = (string) SmartFileSysUtils::addPathTrailingSlash((string)SmartFileSysUtils::extractPathDir((string)$y_file_path));
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
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
	 * Render Main Marker Template (String Template + Sub-Templates from Files if any)
	 * See the escapings and transformation at: Render Marker File Template
	 *
	 * !!! This is intended for special usage ; Ex: render a main template !!!
	 * The difference between this and render_file_template() is that this one does not support defining @SUB-TEMPLATES@ in the data array ... can use only sub-templates from the specified as syntax, using the base path method parameter
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL (partial text/html + markers) ; Ex: '<span>[###MARKER1###]<br>[###MARKER2###], ...</span>'
	 * @param 	ARRAY 		$y_arr_vars 					:: The associative array with the template variables ; mapping the array keys to template markers is case insensitive ; Ex: [ 'MARKER1' => 'Value1', 'marker2' => 'Value2', ..., 'MarkerN' => 100 ]
	 * @param 	STRING 		$y_sub_templates_base_path 		:: The (relative) base path of sub-templates files if they are used (required to be non-empty)
	 * @param 	ENUM 		$y_ignore_if_empty 				:: 'yes' will ignore if Marker-TPL is empty ; 'no' will add a warning (default)
	 * @param 	ENUM 		$y_use_caching 					:: 'yes' will cache the sub-templates if any into memory to avoid re-read them from file system (to be used if at least one sub-template is used more than once per execution) ; 'no' means no caching is used ; default is YES
	 *
	 * @return 	STRING										:: The parsed template
	 *
	 */
	public static function render_main_template(string $mtemplate, array $y_arr_vars, string $y_sub_templates_base_path, string $y_ignore_if_empty='no', string $y_use_caching='yes') : string {
		//--
		$y_ignore_if_empty = (string) $y_ignore_if_empty;
		//-- do not trim partial template, to be consistent with render file template
		if((string)trim((string)$mtemplate) == '') {
			if((string)$y_ignore_if_empty != 'yes') {
				Smart::log_warning('Empty Mixed Marker-TPL Content: '.print_r($y_arr_vars,1));
				return (string) '{### ERROR: Empty Marker-TPL Mixed Template ###}';
			} //end if
			return '';
		} //end if
		//--
		if((!is_array($y_arr_vars)) OR ((Smart::array_size($y_arr_vars) > 0) AND (Smart::array_type_test($y_arr_vars) != 2))) {
			Smart::log_warning('Invalid Mixed Marker-TPL Data-Set for Template: '.$mtemplate.' # '.print_r($y_arr_vars,1));
			return (string) self::prepare_nosyntax_content($mtemplate);
		} //end if
		//--
		if((string)$y_sub_templates_base_path == '') {
			Smart::log_warning('Empty Base Path for Mixed Marker-TPL Content: '.$mtemplate.' # '.print_r($y_arr_vars,1));
			return (string) self::prepare_nosyntax_content($mtemplate);
		} //end if
		//-- make all keys upper
		$y_arr_vars = (array) array_change_key_case((array)$y_arr_vars, CASE_UPPER); // make all keys upper (only 1st level, not nested)
		//-- process sub-templates if any
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Render.START] :: Marker-TPL / Mixed Render ; Ignore if Empty: '.$y_ignore_if_empty.' ; Sub-Templates Load Base Path: '.$y_sub_templates_base_path,
				'data' => 'Caching: '.$y_use_caching.' ; * Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//-- dissalow the use of sub-templates array here or later in this context
		if(array_key_exists('@SUB-TEMPLATES@', (array)$y_arr_vars)) {
			unset($y_arr_vars['@SUB-TEMPLATES@']); // replace this before injecting variables to avoid security issues
		} //end if
		//--
		$arr_sub_templates = (array) self::detect_subtemplates($mtemplate);
		if(Smart::array_size($arr_sub_templates) > 0) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-Render.LOAD-SUBTEMPLATES] :: Marker-TPL / Mixed Render ; Ignore if Empty: '.$y_ignore_if_empty.' ; Sub-Templates Load Base Path: '.$y_sub_templates_base_path,
					'data' => 'Caching: '.$y_use_caching.' ; '.'Sub-Templates: '."\n".print_r($arr_sub_templates,1)."\n".'* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
				]);
			} //end if
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
	 * Read a Marker File Template from FileSystem or from Memory Cache if exists, otherwise read from FileSystem or PCache (if enabled)
	 * !!! This is intended for very special usage ... !!!
	 * This is used automatically by the render_file_template() and used in combination with render_main_template() may produce the same results ... it make non-sense using it with render_template() as this should be used for internal (php) templates as all external templates should be loaded with render_file_template()
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$y_file_path 					:: The relative path to the file Marker-TPL
	 * @param 	ENUM 		$y_use_caching 					:: 'yes' will cache the template (incl. sub-templates if any) into memory to avoid re-read them from file system (to be used if a template is used more than once per execution) ; 'no' means no caching is used ; default is YES
	 *
	 * @return 	STRING										:: The template string
	 *
	 */
	public static function read_template_file(string $y_file_path, string $y_use_caching='yes') : string {
		//--
		return (string) self::read_from_optimal_place_the_template_file((string)$y_file_path, (string)$y_use_caching);
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
	public static function prepare_nosyntax_content(string $val) : string {
		//--
		return (string) strtr( // protect against replace reccurence
			(string) $val,
			[
				'[:::' => '［:::',
				':::]' => ':::］',
				'[###' => '［###',
				'###]' => '###］',
				'[%%%' => '［%%%',
				'%%%]' => '%%%］',
				'[@@@' => '［@@@',
				'@@@]' => '@@@］',
			]
		);
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
	public static function prepare_nosyntax_html_template(string $mtemplate, bool $titlecomments=false, bool $analyze_dbg=false) : string {
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
					' class="sf__tpl__highlight__subtpl" title="TPL SubTemplate: End"',
					' class="sf__tpl__highlight__subtpl" title="TPL Placeholder: Start"',
					' class="sf__tpl__highlight__subtpl" title="TPL Placeholder: End"',
				];
			} //end if else
			//--
			$arr_fix_src = [
				'[###',
				'###]',
				'[%%%',
				'%%%]',
				'[@@@',
				'@@@]',
				'[:::',
				':::]',
			];
			//--
			$arr_fix_back_src = [
				'［###',
				'###］',
				'［%%%',
				'%%%］',
				'［@@@',
				'@@@］',
				'［:::',
				':::］',
			];
			//--
			if($titlecomments === false) {
				$arr_fix_dst = [
					'&lbrack;&num;&num;&num;', 			// [###
					'&num;&num;&num;&rbrack;', 			// ###]
					'&lbrack;&percnt;&percnt;&percnt;', // [%%%
					'&percnt;&percnt;&percnt;&rbrack;', // %%%]
					'&lbrack;&commat;&commat;&commat;', // [@@@
					'&commat;&commat;&commat;&rbrack;', // @@@]
					'&lbrack;&colon;&colon;&colon;', 	// [:::
					'&colon;&colon;&colon;&rbrack;', 	// :::]
				];
			} else {
				$arr_fix_dst = [
					'<span'.$arr_repls[0].'>&lbrack;&num;&num;&num;</span>', 			// [###
					'<span'.$arr_repls[1].'>&num;&num;&num;&rbrack;</span>', 			// ###]
					'<span'.$arr_repls[2].'>&lbrack;&percnt;&percnt;&percnt;</span>', 	// [%%%
					'<span'.$arr_repls[3].'>&percnt;&percnt;&percnt;&rbrack;</span>', 	// %%%]
					'<span'.$arr_repls[4].'>&lbrack;&commat;&commat;&commat;</span>', 	// [@@@
					'<span'.$arr_repls[5].'>&commat;&commat;&commat;&rbrack;</span>', 	// @@@]
					'<span'.$arr_repls[6].'>&lbrack;&colon;&colon;&colon;</span>', 		// [:::
					'<span'.$arr_repls[7].'>&colon;&colon;&colon;&rbrack;</span>', 		// :::]
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
						'¦***⁆',
					],
					[
						'<span'.$arr_repls[4].'>&lbrack;@@@</span>',
						'<span'.$arr_repls[5].'>@@@&rbrack;</span>',
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


	//================================================================
	/**
	 * Extract all the Marker Template syntax # % @ into an array
	 * It doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
	 * !!! This is intended for very special usage ... !!!
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$mtemplate 						:: The template to be used
	 *
	 * @return 	ARRAY										:: The array of matches
	 *
	 */
	public static function extract_tpl_syntax(string $mtemplate) : array { // Quoted Blocks
		//--
		return array(
			'MTPL.SYNTAX:COLON' 	=> (array) self::extract_tpl_syntax_colon((string)$mtemplate), // :
			'MTPL.SYNTAX:NUMSHARP' 	=> (array) self::extract_tpl_syntax_numsharp((string)$mtemplate), // #
			'MTPL.SYNTAX:PERCENT' 	=> (array) self::extract_tpl_syntax_percent((string)$mtemplate), // %
			'MTPL.SYNTAX:COMMAT' 	=> (array) self::extract_tpl_syntax_commat((string)$mtemplate), // @
		);
		//--
	} //END FUNCTION
	//================================================================


	//===== PRIVATES


	//================================================================
	private static function extract_tpl_syntax_colon(string $mtemplate) : array { // extract [:::*:::] like syntax, approx., doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
		//--
		$matches = array();
		$pcre = preg_match_all( // {{{SYNC-TPL-EXPR-PLACEHOLDER}}}
			'/(\[\:\:\:){1}[A-Z0-9_\-]+?(\:\:\:\]){1}/s',
			(string) $mtemplate,
			$matches,
			PREG_PATTERN_ORDER,
			0
		);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function extract_tpl_syntax_numsharp(string $mtemplate) : array { // extract [###*###] like syntax, approx., doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
		//--
		$matches = array();
		$pcre = preg_match_all(
			'/(\[\#\#\#){1}[A-Z0-9_\-\.\|]+?((\|[a-z0-9]+?)*?)[^\s]*?(\#\#\#\]){1}/s', // '/(\[\#\#\#){1}[A-Z0-9_\-\.\|]+((\|[a-z0-9]+)*)[^\s]*(\#\#\#\]){1}/sU',
			(string) $mtemplate,
			$matches,
			PREG_PATTERN_ORDER,
			0
		);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function extract_tpl_syntax_percent(string $mtemplate) : array { // extract [%%%*%%%] like syntax, approx., doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
		//--
		$matches = array();
		$pcre = preg_match_all(
			'/(\[%%%){1}[\/]??(IF|ELSE|LOOP|COMMENT|\|SB\-L|\|SB\-R|\|R|\|N|\|T|\|SPACE){1}[^\s]*?(%%%\]){1}/s', // '/(\[%%%){1}[\/]?(IF|ELSE|LOOP|COMMENT|\|SB\-L|\|SB\-R|\|R|\|N|\|T|\|SPACE){1}[^\s]*(%%%\]){1}/sU',
			(string) $mtemplate,
			$matches,
			PREG_PATTERN_ORDER,
			0
		);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function extract_tpl_syntax_commat(string $mtemplate) : array { // extract [@@@*@@@] like syntax, approx., doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
		//--
		$matches = array();
		$pcre = preg_match_all(
			'/(\[@@@){1}SUB\-TEMPLATE\:[^\s]*?(@@@\]){1}/', // '/(\[@@@){1}SUB\-TEMPLATE\:[^\s]*(@@@\]){1}/sU',
			(string) $mtemplate,
			$matches,
			PREG_PATTERN_ORDER,
			0
		);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// extract and process extracted parts for analyze and return as array as match => the number of matches
	private static function analize_parts_extract(string $regex, string $mtemplate, bool $uppercasekeys) : array {
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
	 * Extract Placeholders for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected placeholders
	 *
	 */
	private static function analize_extract_placeholders(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'/\[\:\:\:([A-Z0-9_\-]+?)\:\:\:\]/s',
			(string) $mtemplate,
			true
		); // {{{SYNC-TPL-EXPR-PLACEHOLDER}}}
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
	private static function analize_extract_markers(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'/\#\#\#([A-Z0-9_\-\.]+)/',
			(string) $mtemplate,
			true
		); // {{{SYNC-TPL-EXPR-MARKER}}} :: start part only :: - [ - ] (can be in IF statement) ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract Markers for Logging (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected markers
	 *
	 */
	private static function logging_extract_markers(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'/\#\#\#([A-Za-z0-9_\-\.]+)/', // must allow also lowercase for invalid detected markers
			(string) $mtemplate,
			false // must allow also lowercase for invalid detected markers
		); // {{{SYNC-TPL-EXPR-MARKER}}} :: start part only :: - [ - ] (can be in IF statement) ; uppercase + lowercase
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
	private static function analize_extract_ifs(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'{\[%%%IF\:([a-zA-Z0-9_\-\.]+?)\:}s', // '{\[%%%IF\:([a-zA-Z0-9_\-\.]+)\:}sU'
			(string) $mtemplate,
			true
		); // {{{SYNC-TPL-EXPR-IF}}} :: start part only ; uppercase
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
	private static function analize_extract_loops(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+?)((\([0-9]+?\))??%)%%\]}s', // '{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+)((\([0-9]+\))?%)%%\]}sU'
			(string) $mtemplate,
			true
		); // {{{SYNC-TPL-EXPR-LOOP}}} ; uppercase
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Extract Specials (SB-L/SB-N/R/N/T/SPACE) for Analyze a Marker Template (String Template)
	 * This is intended for INTERNAL USE ONLY
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The array of detected Specials (SB-L/SB-N/R/N/T/SPACE) syntaxes
	 *
	 */
	private static function analize_extract_specials(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'{\[%%%\|??([a-zA-Z0-9_\-\.]+?)%%%\]}s', // '{\[%%%\|?([a-zA-Z0-9_\-\.]+)%%%\]}sU'
			(string) $mtemplate,
			true
		); // {{{SYNC-TPL-EXPR-SPECIALS}}} ; uppercase
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
	private static function analize_extract_subtpls(string $mtemplate) : array {
		//--
		return (array) self::analize_parts_extract(
			'{\[@@@SUB\-TEMPLATE\:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}',
			(string) $mtemplate,
			false
		); // FIX: add an extra % to parse also SUB-TPL %vars% # {{{SYNC-TPL-EXPR-SUBTPL}}} :: + % ; preserve case
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
	private static function analyze_do_debug_template(string $mtemplate, string $y_info, string $y_original_mtemplate='') : string {
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
		$html .= '<h2 style="display:inline;background:#777788;color:#FFFFFF;padding:3px;">Marker-TPL Debug Analysis</h2>';
		if((string)$y_info != '') {
			$html .= '<br><h3 style="display:inline;">'.Smart::escape_html($y_info).'</h3>';
		} //end if
		$html .= '<hr>';
		//-- main table
		$html .= '<table style="width:98vw !important;">';
		$html .= '<tr valign="top" align="center">';
		//-- loaded sub-tpls
		$html .= '<td align="left" colspan="2">';
		$html .= '<table id="'.'__marker__template__analyzer-ldsubtpls_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="80%" style="font-size:0.750em!important;">';
		$html .= '<tr align="center"><th>[@@@SUB-TEMPLATES:LOADED@@@]<br><small>*** All Loaded Sub-Templates are listed below ***</small></th><th>#'.'&nbsp;('.(int)Smart::array_size(self::$MkTplAnalyzeLdRegDbg).')'.'</th></tr>';
		if(Smart::array_size(self::$MkTplAnalyzeLdRegDbg) > 0) {
			foreach(self::$MkTplAnalyzeLdRegDbg as $key => $val) {
				$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
			} //end foreach
		} //end if
		$html .= '</table>';
		$html .= '</td>';
		//-- sub-tpls
		$html .= '<td align="right" colspan="2">';
		$html .= '<table id="'.'__marker__template__analyzer-subtpls_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="80%" style="font-size:0.750em!important;">';
		$arr_subtpls = (array) self::analize_extract_subtpls($y_original_mtemplate);
		ksort($arr_subtpls);
		$html .= '<tr align="center"><th>[@@@SUB-TEMPLATES:SLOTS@LEVEL-1@@@]<br><small>*** Only Level-1 Sub-Templates slots are listed below ***</small></th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_subtpls).')'.'</th></tr>';
		foreach($arr_subtpls as $key => $val) {
			$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
		} //end for
		$html .= '</table>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr valign="top" align="center"><td colspan="4"><hr></td></tr>';
		$html .= '<tr valign="top" align="center">';
		//-- placeholder vars
		$arr_placeholders = (array) self::analize_extract_placeholders($mtemplate);
		ksort($arr_placeholders);
		$html .= '<td width="25%"><table id="'.'__marker__template__analyzer-placeholders_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="100%" style="font-size:0.750em!important;"><tr align="center"><th>[:::PLACEHOLDER-VARIABLES:::]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_placeholders).')'.'</th></tr>';
		foreach($arr_placeholders as $key => $val) {
			$html .= '<tr><td align="left">'.Smart::escape_html((string)$key).'</td><td align="right">'.Smart::escape_html((string)$val).'</td></tr>';
		} //end for
		$html .= '</table></td>';
		//-- marker vars
		$arr_marks = (array) self::analize_extract_markers($mtemplate);
		ksort($arr_marks);
		$html .= '<td width="25%"><table id="'.'__marker__template__analyzer-markers_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="100%" style="font-size:0.750em!important;"><tr align="center"><th>[###MARKER-VARIABLES###]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_marks).')'.'</th></tr>';
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
		$html .= '<td width="25%"><table id="'.'__marker__template__analyzer-loopvars_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="100%" style="font-size:0.750em!important;"><tr align="center"><th>[%%%LOOP:VARIABLES%%%]<br>[%%%/LOOP:VARIABLES%%%]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_loops).')'.'</th></tr>';
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
		$html .= '<td width="25%"><table id="'.'__marker__template__analyzer-ifvars_'.Smart::escape_html($hash).'" class="debug-table debug-table-striped" cellspacing="0" cellpadding="4" width="100%" style="font-size:0.750em!important;"><tr align="center"><th>[%%%IF:VARIABLES:{condition};%%%]<br>[%%%ELSE:VARIABLES%%%]<br>[%%%/IF:VARIABLES%%%]</th><th>#'.'&nbsp;('.(int)Smart::array_size($arr_ifs).')'.'</th></tr>';
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
		$html .= '</div><h2 style="display:inline;background:#777788;color:#FFFFFF;padding:3px;">Marker-TPL Source - with ALL:[Level 1..n] Sub-Templates Includded (if any)</h2><div id="tpl-display-for-highlight"><pre id="'.'__marker__template__analyzer-tpl_'.Smart::escape_html($hash).'"><code class="debug-tpl" data-syntax="markertpl">'.Smart::escape_html($mtemplate).'</code></pre></div><hr>'."\n".'<!-- #END: Marker-TPL Analysis @ '.Smart::escape_html($hash).' -->';
		//-- ast
	//	if(SmartEnvironment::ifInternalDebug()) {
		//--
		$html .= '</div><h2 style="display:inline;background:#777788;color:#FFFFFF;padding:3px;">Marker-TPL AST Nodes Syntax Tree - with ALL:[Level 1..n] Sub-Templates Includded (if any)</h2>';
		//--
		$ast = (array) self::process_ast_raw_tree((string)$mtemplate);
		$tree = (array) self::process_ast_nodes_tree((array)$ast);
		$nodes = (array) $tree['nodes'];
		$tree = null;
		$ast = null;
		//--
		$html .= self::analyze_display_ast_nodes_tree((array)$nodes);
		//--
		$html .= '</div>';
		//--
	//	} //end if
		//-- return
		return (string) self::prepare_nosyntax_html_template($html, true, true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * DO Analyze a Marker Template Nodes
	 * This is intended for DEVELOPMENT / INTERNAL DEBUG ONLY (never use this in production environments !)
	 *
	 * @param 	ARRAY 		$arr 							:: The Nodes AST Syntax Tree of the TPL
	 * @param 	STRING 		$tag 							:: **INTERNAL USE ONLY** Curent Node Tag (must be initialized always with empty string) ; this parameter is only used by recursive calls of this method, later
	 *
	 * @return 	STRING										:: The analyze info HTML
	 *
	 */
	private static function analyze_display_ast_nodes_tree(array $nodes, string $tag='') : string {
		//--
		$html = '';
		//--
		if((string)$tag == '') {
			$html .= '<ul>'."\n";
		} //end if
		//--
		$html .= '<li>TPL&nbsp;<b>['.Smart::escape_html((string)($tag ? $tag : 'ROOT')).']</b></li><ul>'."\n";
		//--
		foreach($nodes as $key => $val) {
			//--
			$ttag = '?';
			if(strpos((string)$val[0], '[%%%COMMENT%%%]') === 0) {
				$ttag = 'COMMENT';
			} elseif(strpos((string)$val[0], '[%%%IF:') === 0) {
				$ttag = 'IF';
			} elseif(strpos((string)$val[0], '[%%%LOOP:') === 0) {
				$ttag = 'LOOP';
			} //end if else
			//--
			if(is_array($val)) {
				$html .= (string) self::analyze_display_ast_nodes_tree((array)$val, (string)$ttag);
			} else {
				$html .= '<li>'.Smart::escape_html((string)$val).'</li>';
			} //end if else
			//--
		} //end foreach
		//--
		$html .= '</ul>'."\n";
		//--
		if((string)$tag == '') {
			$html .= '</ul>'."\n";
		} //end if
		//--
		return (string) self::prepare_nosyntax_html_template($html, true, true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Process the Raw AST Syntax Tree for a Marker Template
	 * This is intended for DEVELOPMENT / DEBUG ONLY (never use this in production environments !)
	 *
	 * @param 	STRING 		$mtemplate 						:: The Marker-TPL string
	 *
	 * @return 	ARRAY										:: The Raw AST Syntax Tree of the TPL
	 *
	 */
	private static function process_ast_raw_tree(string $mtemplate) : array {
		//--
		if((string)trim((string)$mtemplate) == '') {
			return array();
		} //end if
		//--
		return (array) preg_split('/(\[%%%.*?%%%\]|\[@@@.*?@@@\]|\[\#\#\#.*?\#\#\#\]|\[\:\:\:.*?\:\:\:\])/s', (string)$mtemplate, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Process the Nodes AST Syntax Tree for a Marker Template
	 * This is intended for DEVELOPMENT / DEBUG ONLY (never use this in production environments !)
	 *
	 * @param 	ARRAY 		$arr 							:: The Raw AST Tree of the TPL
	 * @param 	INTEGER 	$cnt 							:: **INTERNAL USE ONLY** The start counter (must must be initialized always with zero) ; this parameter is only used by recursive calls of this method, later
	 * @param 	STRING 		$tag 							:: **INTERNAL USE ONLY** Curent Node Tag (must be initialized always with empty string) ; this parameter is only used by recursive calls of this method, later
	 *
	 * @return 	ARRAY										:: The Nodes AST Syntax Tree of the TPL
	 *
	 */
	private static function process_ast_nodes_tree(array $arr, int $crr=0, string $tag='') : array {
		//--
		$crrNode = [];
		//--
		if((string)$tag != '') {
			$crrNode[] = $tag;
		} //end if
		//--
		if((int)$crr < 0) {
			$crr = 0;
		} //end if
		//--
		for($i=(int)$crr; $i<Smart::array_size($arr); $i++) {
			//--
			if(strpos((string)$arr[$i], '[%%%COMMENT%%%]') === 0) {
				$t = (array) self::process_ast_nodes_tree((array)$arr, (int)($i+1), (string)$arr[$i]);
				$i = (int) $t['iterator'];
				$crrNode[] = (array) $t['nodes'];
				$t = null;
			} elseif(strpos((string)$arr[$i], '[%%%/COMMENT%%%]') === 0) {
				$crrNode[] = $arr[$i];
				if((string)$tag != '') {
					break;
				} //end if
			} elseif(strpos((string)$arr[$i], '[%%%IF:') === 0) {
				$t = (array) self::process_ast_nodes_tree((array)$arr, (int)($i+1), (string)$arr[$i]);
				$i = (int) $t['iterator'];
				$crrNode[] = (array) $t['nodes'];
				$t = null;
			} elseif(strpos((string)$arr[$i], '[%%%/IF:') === 0) {
				$crrNode[] = $arr[$i];
				if((string)$tag != '') {
					break;
				} //end if
			} elseif(strpos((string)$arr[$i], '[%%%LOOP:') === 0) {
				$t = (array) self::process_ast_nodes_tree((array)$arr, (int)($i+1), (string)$arr[$i]);
				$i = (int) $t['iterator'];
				$crrNode[] = (array) $t['nodes'];
				$t = null;
			} elseif(strpos((string)$arr[$i], '[%%%/LOOP:') === 0) {
				$crrNode[] = $arr[$i];
				if((string)$tag != '') {
					break;
				} //end if
			} else {
				$crrNode[] = $arr[$i];
			} //end if else
			//--
		} //end for
		//--
		return [ 'iterator' => (int)$i, 'nodes' => (array)$crrNode ];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// INFO: this renders the template except sub-templates loading which is managed separately
	// $mtemplate must be STRING, non-empty
	// $y_arr_vars must be a prepared ARRAY with all keys UPPERCASE
	private static function template_renderer(string $mtemplate, array $y_arr_vars) : string {
		//-- debug start
		if(SmartEnvironment::ifDebug()) {
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
			$arr_marks = (array) self::logging_extract_markers($mtemplate);
			Smart::log_warning('Invalid or Undefined Marker-TPL: Markers detected in Template:'."\n".'MARKERS:'.print_r($arr_marks,1)."\n".self::log_template($mtemplate));
			$mtemplate = (string) str_replace(['[###', '###]'], ['⁅###¦', '¦###⁆'], (string)$mtemplate); // finally protect against undefined variables
		} //end if
		//-- debug end
		if(SmartEnvironment::ifDebug()) {
			$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
			self::$MkTplVars['**TPL-RENDER: ['.sha1((string)$mtemplate).']'][] = 'Time = '.$bench.' sec.';
			SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
				'title' => '[TPL-Parsing:Render.DONE] :: Marker-TPL / Processing ; Time = '.$bench.' sec.',
				'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
			]);
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one marker
	private static function have_marker(string $mtemplate) : bool {
		//--
		if(strpos((string)$mtemplate, '[###') !== false) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one syntax
	private static function have_syntax(string $mtemplate) : bool {
		//--
		if(strpos((string)$mtemplate, '[%%%') !== false) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one sub-template
	private static function have_subtemplate(string $mtemplate) : bool {
		//--
		if(strpos((string)$mtemplate, '[@@@') !== false) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// test if the template have at least one placeholder
	private static function have_placeholder(string $mtemplate) : bool {
		//--
		if(strpos((string)$mtemplate, '[:::') !== false) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// do replacements (and escapings) for one marker ; a marker can contain: A-Z 0-9 _ - (and the dot . which is reserved as array level separator)
	/* {{{SYNC-MARKER-ALL-TEST-SEQUENCES}}}
	<!-- INFO: The VALID Escaping and Transformers for a Marker are all below ; If other escaping sequences are used the Marker will not be detected and replaced ... -->
	<!-- Valid Escapings and Transformers: for more, see the escapings and transformation at: Render Marker File Template -->
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
	[###MARKER|js###]
	[###MARKER|js|js###]
	[###MARKER|js|html###]
	[###MARKER|js|xml###]
	[###MARKER|html|js###]
	[###MARKER|json###]
		[###MARKER|json|url###]
		[###MARKER|json|js###]
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
	private static function replace_marker(string $mtemplate, string $key, string $val) : string {
		//-- {{{SYNC-TPL-EXPR-MARKER}}}
		$valid = preg_match('/^[A-Z0-9_\-\.]+$/', (string)$key);
		if($valid === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return (string) $mtemplate;
		} //end if
		//-- #end sync
		if(((string)$key != '') AND ($valid) AND (strpos((string)$mtemplate, '[###'.$key) !== false)) {
			//--
			$regex = '/\[\#\#\#'.preg_quote((string)$key, '/').'((\|[a-z0-9]+)*)'.'\#\#\#\]/'; // {{{SYNC-REGEX-MARKER-TEMPLATES}}}
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
			if(Smart::array_size($matches) > 0) {
				//--
				$val = (string) self::prepare_nosyntax_content($val);
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
			} //end if
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
	private static function escape_marker_value(array $crr_match, string $val) : string {
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
					} elseif((string)$escexpr == '|len') { // Length
						$val = (string) (int) strlen((string)$val);
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
					} elseif((string)substr((string)$escexpr, 0, 4) == '|dex') { // if not int, ensure this number of decimals
						$xnum = Smart::format_number_int((int)substr((string)$escexpr, 4), '+');
						if($xnum < 1) {
							$xnum = 1;
						} elseif($xnum > 4) {
							$xnum = 4;
						} //end if
						$val = (string) (float) Smart::format_number_dec((string)$val, (int)$xnum, '.', '');
						if(strpos($val, '.') !== false) { // if there are decimals, make sure there are at least xnum
							$val = (string) Smart::format_number_dec((string)$val, (int)$xnum, '.', '');
						} //end if
						$xnum = null; // free mem
					} elseif((string)$escexpr == '|num') { // Number (Float / Decimal / Integer)
						$val = (string) (float) $val;
					//--
					} elseif((string)$escexpr == '|date') { // Expects Unix Epoch Time to format as YYYY-MM-DD
						$val = (string) date('Y-m-d', (int)intval((string)trim((string)$val)));
					} elseif((string)$escexpr == '|datetime') { // Expects Unix Epoch Time to format as YYYY-MM-DD HH:II:SS
						$val = (string) date('Y-m-d H:i:s', (int)intval((string)trim((string)$val)));
					} elseif((string)$escexpr == '|datetimez') { // Expects Unix Epoch Time to format as YYYY-MM-DD HH:II:SS +0000
						$val = (string) date('Y-m-d H:i:s O', (int)intval((string)trim((string)$val)));
					//--
					} elseif((string)$escexpr == '|url') {
						$val = (string) Smart::escape_url((string)$val); // escape URL
					} elseif(((string)$escexpr == '|json') OR ((string)$escexpr == '|jsonpretty')) { // Json Data ; expects pure JSON !!!
						$isPrettyJson = false;
						if((string)$escexpr == '|jsonpretty') {
							$isPrettyJson = true;
						} //end if
						$val = (string) Smart::json_encode(Smart::json_decode($val, true), (bool)$isPrettyJson, true, true); // it MUST be JSON with HTML-Safe Options.
						$val = (string) trim((string)$val);
						if((string)$val == '') {
							$val = 'null'; // ensure a minimal json as empty string if no expr !
						} //end if
					} elseif((string)$escexpr == '|js') {
						$val = (string) Smart::escape_js((string)$val); // Escape JS
					} elseif((string)$escexpr == '|html') {
						$val = (string) Smart::escape_html((string)$val); // Escape HTML
					} elseif((string)$escexpr == '|xml') {
						$val = (string) Smart::escape_xml((string)$val, false); // Escape XML
					} elseif((string)$escexpr == '|exml') {
						$val = (string) Smart::escape_xml((string)$val, true); // Escape XML + extra entities: TAB, LF, CR, ...
					} elseif((string)$escexpr == '|css') {
						$val = (string) Smart::escape_css((string)$val); // Escape CSS
					} elseif((string)$escexpr == '|nl2br') {
						$val = (string) Smart::nl_2_br((string)$val); // Apply Nl2Br
					} elseif((string)$escexpr == '|nbsp') {
						$val = (string) strtr((string)$val, [ // Transform Spaces and Tabs to nbsp;
							' '  => '&nbsp;',
							"\t" => '&nbsp;',
						]);
					} elseif((string)$escexpr == '|striptags') {
						$val = (string) Smart::stripTags((string)$val); // Apply Strip Tags
					//--
					} elseif((string)$escexpr == '|emptye') { // if empty, display [EMPTY]
						if((string)trim((string)$val) == '') {
							$val = '[EMPTY]';
						} //end if
					} elseif((string)$escexpr == '|emptyna') { // if empty, display [N/A]
						if((string)trim((string)$val) == '') {
							$val = '[N/A]';
						} //end if
					} elseif((string)$escexpr == '|idtxt') { // id_txt: Id-Txt
						$val = (string) str_replace('_', '-', (string)$val);
						$val = (string) SmartUnicode::uc_words((string)$val);
					} elseif((string)$escexpr == '|slug') { // Slug: a-zA-Z0-9_- / - / -- : -
						$val = (string) Smart::create_slug((string)$val, false); // do not apply strtolower as it can be later combined with |lower flag
					} elseif((string)$escexpr == '|htmid') { // HTML-ID: a-zA-Z0-9_-
						$val = (string) Smart::create_htmid((string)$val);
					} elseif((string)$escexpr == '|jsvar') { // JS-Variable: a-zA-Z0-9_$
						$val = (string) Smart::create_jsvar((string)$val);
					} elseif((string)$escexpr == '|stdvar') { // Standard Variable: a-zA-Z0-9_
						$val = (string) Smart::safe_varname((string)$val, true);
					} elseif((string)$escexpr == '|nobackslash') { // remove backslashes from a string
						$val = (string) strtr((string)$val, [ '\\' => '' ]);
					} elseif((string)$escexpr == '|rxpattern') { // prepare a regex escaped pattern for a browser input
						$val = (string) strtr((string)$val, [ // the following characters need to be not escaped in a browser pattern sequence, but in PHP they are, in a regex pattern
							// the `-` and `/` must remain escaped
							'\\.' => '.',
							'\\:' => ':',
							'\\#' => '#',
							'\\=' => '=',
							'\\!' => '!',
							'\\<' => '<',
							'\\>' => '>',
							// when using backslashes in a regex string, it must be '\\\\' = \\ not '\\' = \ when string is evaluated !
						]);
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
						$val = (string) trim((string)$val);
					} elseif((string)$escexpr == '|rev') { // reverse string
						$val = (string) strrev((string)$val);
					//--
					} elseif((string)$escexpr == '|smartlist') { // Apply SmartList Fix Replacements
						$val = (string) str_replace(['<', '>'], ['‹', '›'], (string)$val); // {{{SYNC-SMARTLIST-BRACKET-REPLACEMENTS}}}
					} elseif((string)$escexpr == '|syntaxhtml') {
						$val = (string) self::prepare_nosyntax_html_template((string)$val); // Prepare a HTML template for display in no-conflict mode: no syntax or markers will be parsed
					//--
					} elseif((string)$escexpr == '|hex') {
						$val = (string) bin2hex((string)$val); // Apply Bin2Hex Encode
					} elseif((string)$escexpr == '|hexi10') {
						$val = (string) Smart::int10_to_hex((int)(string)$val); // Converts a 64-bit integer number to hex (string)
					//--
					} elseif((string)$escexpr == '|b64') {
						$val = (string) Smart::b64_enc((string)$val); // Apply Base64 Encode
					} elseif((string)$escexpr == '|b64s') {
						$val = (string) Smart::b64s_enc((string)$val); // Apply Base64 Safe URL Encode
					} elseif((string)$escexpr == '|b64tob64s') {
						$val = (string) Smart::b64_to_b64s((string)$val); // Convert from Base64 Encoding to Base64 Safe URL Encoding
					} elseif((string)$escexpr == '|b64stob64') {
						$val = (string) Smart::b64s_to_b64((string)$val); // Convert from Base64 Safe URL Encoding to Base64 Encoding
					//--
					} elseif((string)$escexpr == '|b32') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 32); // Apply Base32 Encoding
					} elseif((string)$escexpr == '|b36') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 36); // Apply Base36 Encoding
					} elseif((string)$escexpr == '|b58') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 58); // Apply Base58 Encoding
					} elseif((string)$escexpr == '|b62') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 62); // Apply Base62 Encoding
					} elseif((string)$escexpr == '|b85') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 85); // Apply Base85 Encoding
					} elseif((string)$escexpr == '|b92') {
						$val = (string) Smart::base_from_hex_convert((string)bin2hex((string)$val), 92); // Apply Base92 Encoding
					//--
					} elseif((string)$escexpr == '|crc32b') {
						$val = (string) SmartHashCrypto::crc32b((string)$val, false); // Apply crc32b/B16 (default) Hashing
					} elseif((string)$escexpr == '|crc32b36') {
						$val = (string) SmartHashCrypto::crc32b((string)$val, true); // Apply crc32b/B36 Hashing
					//--
					} elseif((string)$escexpr == '|md5') {
						$val = (string) SmartHashCrypto::md5((string)$val, false); // Apply MD5 Hashing
					} elseif((string)$escexpr == '|md5b64') {
						$val = (string) SmartHashCrypto::md5((string)$val, true); // Apply MD5B64 Hashing
					//--
					} elseif((string)$escexpr == '|sha1') {
						$val = (string) SmartHashCrypto::sha1((string)$val, false); // Apply SHA1 Hashing
					} elseif((string)$escexpr == '|sha1b64') {
						$val = (string) SmartHashCrypto::sha1((string)$val, true); // Apply SHA1B64 Hashing
					//--
					} elseif((string)$escexpr == '|sha224') {
						$val = (string) SmartHashCrypto::sha224((string)$val, false); // Apply SHA224 Hashing
					} elseif((string)$escexpr == '|sha224b64') {
						$val = (string) SmartHashCrypto::sha224((string)$val, true); // Apply SHA224B64 Hashing
					//--
					} elseif((string)$escexpr == '|sha256') {
						$val = (string) SmartHashCrypto::sha256((string)$val, false); // Apply SHA256 Hashing
					} elseif((string)$escexpr == '|sha256b64') {
						$val = (string) SmartHashCrypto::sha256((string)$val, true); // Apply SHA256B64 Hashing
					//--
					} elseif((string)$escexpr == '|sha384') { // not yet portable to JS ...
						$val = (string) SmartHashCrypto::sha384((string)$val, false); // Apply SHA384 Hashing
					} elseif((string)$escexpr == '|sha384b64') { // not yet portable to JS ...
						$val = (string) SmartHashCrypto::sha384((string)$val, true); // Apply SHA384B64 Hashing
					//--
					} elseif((string)$escexpr == '|sha512') {
						$val = (string) SmartHashCrypto::sha512((string)$val, false); // Apply SHA512 Hashing
					} elseif((string)$escexpr == '|sha512b64') {
						$val = (string) SmartHashCrypto::sha512((string)$val, true); // Apply SHA512B64 Hashing
					//--
					} elseif((string)$escexpr == '|sh3a224') {
						$val = (string) SmartHashCrypto::sh3a224((string)$val, false); // Apply SHA3-224 Hashing
					} elseif((string)$escexpr == '|sh3a224b64') {
						$val = (string) SmartHashCrypto::sh3a224((string)$val, true); // Apply SHA3-224B64 Hashing
					//--
					} elseif((string)$escexpr == '|sh3a256') {
						$val = (string) SmartHashCrypto::sh3a256((string)$val, false); // Apply SHA3-256 Hashing
					} elseif((string)$escexpr == '|sh3a256b64') {
						$val = (string) SmartHashCrypto::sh3a256((string)$val, true); // Apply SHA3-256B64 Hashing
					//--
					} elseif((string)$escexpr == '|sh3a384') { // not yet portable to JS ...
						$val = (string) SmartHashCrypto::sh3a384((string)$val, false); // Apply SHA3-384 Hashing
					} elseif((string)$escexpr == '|sh3a384b64') { // not yet portable to JS ...
						$val = (string) SmartHashCrypto::sh3a384((string)$val, true); // Apply SHA3-384B64 Hashing
					//--
					} elseif((string)$escexpr == '|sh3a512') {
						$val = (string) SmartHashCrypto::sh3a512((string)$val, false); // Apply SHA3-512 Hashing
					} elseif((string)$escexpr == '|sh3a512b64') {
						$val = (string) SmartHashCrypto::sh3a512((string)$val, true); // Apply SHA3-512B64 Hashing
					//--
					// prettybytes ; skip, depends on Lib Utils
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
	private static function process_syntax(string $mtemplate, array $y_arr_vars) : string {
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
			Smart::log_warning('Invalid or Undefined Marker-TPL: Marker Syntax detected in Template:'.$arr_ifs.$arr_loops.$arr_specials."\n".self::log_template($mtemplate));
			$mtemplate = (string) str_replace(['[%%%', '%%%]'], ['⁅%%%¦', '¦%%%⁆'], (string)$mtemplate); // {{{SYNC-TPL-INVALID-PERCENT-SYNTAX}}} ; finally protect against invalid loops (may have not bind to an existing var or invalid syntax)
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// process the template COMMENT syntax
	private static function process_comments_syntax(string $mtemplate) : string {
		//--
		if(strpos((string)$mtemplate, '[%%%COMMENT') !== false) {
			//--
		//	$pattern = '{\[%%%COMMENT%%%\](.*)?\[%%%\/COMMENT%%%\]}sU';
		//	$pattern = '{\s?\[%%%COMMENT%%%\](.*)?\[%%%\/COMMENT%%%\]\s?}sU'; // Fix: trim parts
			$pattern = '{\s??\[%%%COMMENT%%%\](.*?)??\[%%%\/COMMENT%%%\]\s??}s'; // Fix: trim parts
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
	private static function process_brntspace_syntax(string $mtemplate) : string {
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
	private static function process_if_syntax(string $mtemplate, array $y_arr_vars, string $y_context='', array $y_arr_context=[]) : string {
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
			//-- {{{SYNC-TPL-EXPR-IF}}} ; {{{SYNC-TPL-EXPR-IF-IN-LOOP}}}
		//	$pattern = '{\[%%%IF\:([a-zA-Z0-9_\-\.]+)\:(@\=\=|@\!\=|@\<\=|@\<|@\>\=|@\>|\=\=|\!\=|\<\=|\<|\>\=|\>|\!%|%|\!\?|\?|\^~|\^\*|&~|&\*|\$~|\$\*)([^\[\]]*);((\([0-9]+\))?)%%%\](.*)?(\[%%%ELSE\:\1\4%%%\](.*)?)?\[%%%\/IF\:\1\4%%%\]}sU';
			$pattern = '{\[%%%IF\:([a-zA-Z0-9_\-\.]+?)\:(@\=\=|@\!\=|@\<\=|@\<|@\>\=|@\>|\=\=|\!\=|\<\=|\<|\>\=|\>|\!%|%|\!\?|\?|\^~|\^\*|&~|&\*|\$~|\$\*)([^\[\]]*?);((\([0-9]+\))??)%%%\](.*?)??(\[%%%ELSE\:\1\4%%%\](.*?)??)??\[%%%\/IF\:\1\4%%%\]}s';
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
				$part_orig 		= (string) ($matches[$i][0] ?? null);
				$part_var 		= (string) ($matches[$i][1] ?? null);
				$part_sign 		= (string) ($matches[$i][2] ?? null);
				$part_value 	= (string) ($matches[$i][3] ?? null);
			//	$part_uniqid 	= (string) ($matches[$i][4] ?? null); // not used
			//	$part_uniqix 	= (string) ($matches[$i][5] ?? null); // not used
				$part_if 		= (string) ($matches[$i][6] ?? null);
			//	$part_tag_else 	= (string) ($matches[$i][7] ?? null); // not used
				$part_else 		= (string) ($matches[$i][8] ?? null);
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
					if(SmartEnvironment::ifDebug()) {
						if((string)$y_context != '') {
							self::$MkTplVars['%IF:'.$part_var][] = 'Processing IF Syntax in Context: '.$y_context;
						} else {
							self::$MkTplVars['%IF:'.$part_var][] = 'Processing IF Syntax';
						} //end if else
					} //end if
					//--
					if(((string)substr((string)$bind_value, 0, 3) == '###') AND ((string)substr((string)$bind_value, -3, 3) == '###')) { // compare with a comparison marker (from a variable) instead of static value
						$bind_value = (string) strtoupper((string)trim((string)$bind_value, '#'));
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
					//--
					$condition_part_else = null;
					//--
					switch((string)$part_sign) {
						//-- arrays
						case '@==': // array count ==
							if(Smart::array_size($tmp_the_arr) == (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '@!=': // array count !=
							if(Smart::array_size($tmp_the_arr) != (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '@<=': // array count <=
							if(Smart::array_size($tmp_the_arr) <= (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '@<': // array count <
							if(Smart::array_size($tmp_the_arr) < (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '@>=': // array count >=
							if(Smart::array_size($tmp_the_arr) >= (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '@>': // array count >
							if(Smart::array_size($tmp_the_arr) > (int)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						//-- strings or numbers (compare all as strings)
						case '==':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if((string)$tmp_the_arr == (string)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '!=':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if((string)$tmp_the_arr != (string)$bind_value) { // if evaluate to false keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						//-- numbers
						case '<=':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((float)$tmp_the_arr <= (float)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '<':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((float)$tmp_the_arr < (float)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '>=':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((float)$tmp_the_arr >= (float)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '>':
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((float)$tmp_the_arr > (float)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '%': // modulo (true/false)
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((int)((int)$tmp_the_arr % (int)$bind_value) == 0) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '!%': // not modulo (false/true)
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = 0; // fix PHP8 array to string conversion
							} //end if
							if((int)((int)$tmp_the_arr % (int)$bind_value) != 0) { // if evaluate to false keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						//-- string lists
						case '?': // in list (elements separed by |)
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							$tmp_compare_arr = (array) explode('|', (string)$bind_value);
							if(in_array((string)$tmp_the_arr, (array)$tmp_compare_arr)) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							$tmp_compare_arr = null;
							break;
						case '!?': // not in list (elements separed by |)
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							$tmp_compare_arr = (array) explode('|', (string)$bind_value);
							if(!in_array((string)$tmp_the_arr, (array)$tmp_compare_arr)) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							$tmp_compare_arr = null;
							break;
						//-- strings
						case '^~': // if variable starts with part, case sensitive
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if(SmartUnicode::str_pos((string)$tmp_the_arr, (string)$bind_value) === 0) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '^*': // if variable starts with part, case insensitive
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if(SmartUnicode::str_ipos((string)$tmp_the_arr, (string)$bind_value) === 0) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '&~': // if variable contains part, case sensitive
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if(SmartUnicode::str_contains((string)$tmp_the_arr, (string)$bind_value)) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '&*': // if variable contains part, case insensitive
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if(SmartUnicode::str_icontains((string)$tmp_the_arr, (string)$bind_value)) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '$~': // if variable ends with part, case sensitive
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len((string)$bind_value)), SmartUnicode::str_len((string)$bind_value)) == (string)$bind_value) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						case '$*': // if variable ends with part, case insensitive ### !!! Expensive in Execution !!! ###
							if(is_array($tmp_the_arr)) {
								$tmp_the_arr = ''; // fix PHP8 array to string conversion
							} //end if
							if((SmartUnicode::str_tolower(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len(SmartUnicode::str_tolower((string)$bind_value))), SmartUnicode::str_len(SmartUnicode::str_tolower((string)$bind_value)))) == (string)SmartUnicode::str_tolower((string)$bind_value)) OR (SmartUnicode::str_toupper(SmartUnicode::sub_str((string)$tmp_the_arr, (-1 * SmartUnicode::str_len(SmartUnicode::str_toupper((string)$bind_value))), SmartUnicode::str_len(SmartUnicode::str_toupper((string)$bind_value)))) == (string)SmartUnicode::str_toupper((string)$bind_value))) { // if evaluate to true keep the inner content
								$condition_part_else = false;
							} else {
								$condition_part_else = true;
							} //end if else
							break;
						//--
						default:
							// invalid syntax
							$condition_part_else = null;
							Smart::log_warning('Invalid Marker Template IF Syntax Sign: ['.$part_sign.' '.$part_var.'] / Template: '.$mtemplate);
					} //end switch
					//--
					$line = '';
					//--
					if($condition_part_else === false) {
						//-- Fix: trim {{{SYNC-TPL-FIX-TRIM-PARTS}}}
						$bind_if = (string) trim((string)$bind_if, "\n\r\0\x0B");
						//-- recursive process if part, find other nested ifs
						if(strpos((string)$bind_if, '[%%%IF:') !== false) {
							$bind_if = (string) self::process_if_syntax((string)$bind_if, (array)$y_arr_vars, (string)$y_context, (array)$y_arr_context);
						} //end if
						//--
						$line = (string) $bind_if; // if part
						//--
					} elseif($condition_part_else === true) {
						//-- Fix: trim {{{SYNC-TPL-FIX-TRIM-PARTS}}}
						$bind_else = (string) trim((string)$bind_else, "\n\r\0\x0B");
						//-- recursive process else part, find other nested ifs
						if(strpos((string)$bind_else, '[%%%IF:') !== false) {
							$bind_else = (string) self::process_if_syntax((string)$bind_else, (array)$y_arr_vars, (string)$y_context, (array)$y_arr_context);
						} //end if
						//--
						$line = (string) $bind_else; // else part ; if else not present will don't add = remove it !
						//--
					} else {
						//--
						Smart::log_warning('Invalid Marker Template IF Syntax: ['.$part_var.' '.$part_sign.' '.$part_value.']');
						//--
						$line = (string) str_replace(['[%%%', '%%%]'], ['⁅%%%¦', '¦%%%⁆'], (string)$part_orig); // {{{SYNC-TPL-INVALID-PERCENT-SYNTAX}}}
						//--
					} //end if else
					//--
					$mtemplate = (string) Smart::str_replace_first((string)$part_orig, (string)$line, (string)$mtemplate); // MUST REPLACE ONLY THE FIRST OCCURENCE because this function is recursive and the regex will already contain the original parts only and if a 2nd part is replaced but not yet parsed in this for loop the str replace will fail to find it
					//--
					$line = '';
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
	private static function process_loop_syntax(string $mtemplate, array $y_arr_vars, int $level=0) : string {
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
		//	$pattern = '{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+)((\([0-9]+\))?%)%%\](.*)?\[%%%\/LOOP\:\1\2%%\]}sU'; // {{{SYNC-TPL-EXPR-LOOP}}}
			$pattern = '{\[%%%LOOP\:([a-zA-Z0-9_\-\.]+?)((\([0-9]+?\))??%)%%\](.*?)??\[%%%\/LOOP\:\1\2%%\]}s'; // {{{SYNC-TPL-EXPR-LOOP}}}
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
				$part_orig 		= (string) ($matches[$i][0] ?? null);
				$part_var 		= (string) ($matches[$i][1] ?? null);
			//	$part_uniqid 	= (string) ($matches[$i][2] ?? null); // not used
			//	$part_uniqix 	= (string) ($matches[$i][3] ?? null); // not used
				$part_loop 		= (string) ($matches[$i][4] ?? null);
				//--
				$matches[$i] = null; // free mem
				//--
				$bind_var_key 	= (string) strtoupper((string)$part_var);
				//--
				if(((string)$bind_var_key != '') AND (array_key_exists((string)$bind_var_key, $y_arr_vars)) AND (is_array($y_arr_vars[(string)$bind_var_key]))) { // if the LOOP is binded to an existing Array Variable and a non-empty KEY
					//--
					if(SmartEnvironment::ifDebug()) {
						self::$MkTplVars['%LOOP:'.$bind_var_key][] = 'Processing LOOP Syntax: '.Smart::array_size($y_arr_vars[(string)$bind_var_key]);
					} //end if
					//--
					//$loop_orig = (string) rtrim((string)$part_loop);
					$loop_orig = (string) trim((string)$part_loop, "\n\r\0\x0B"); // Fix: trim parts {{{SYNC-TPL-FIX-TRIM-PARTS}}}
					$loop_orig = (string) ltrim((string)$part_loop); // fix
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
							if(strpos((string)$mks_line, '[%%%IF:'.$bind_var_key.'.') !== false) {
								$tmp_arr_context = array(); // init
								$tmp_arr_context[(string)$bind_var_key.'.'.'-_MAXSIZE_-'] = (string) ($mxcnt+1); // new behaviour: available also in if
								$tmp_arr_context[(string)$bind_var_key.'.'.'-_INDEX_-'] = (string) ($j+1); // new behaviour: available also in if
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
							if((strpos((string)$mks_line, '[%%%LOOP:'.$bind_var_key.'.') !== false) AND (is_array($y_arr_vars[(string)$bind_var_key][$j]))) {
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
								(string) $bind_var_key.'.'.'-_MAXSIZE_-', // new behaviour: available also in if
								(string) ($mxcnt+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_INDEX_-', // new behaviour: available also in if
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
							if(strpos((string)$mks_line, '[%%%IF:'.$bind_var_key.'.') !== false) {
								$tmp_arr_context = array(); // init
								$tmp_arr_context[(string)$bind_var_key.'.'.'-_MAXSIZE_-'] = (string) ($mxcnt+1); // new behaviour: available also in if
								$tmp_arr_context[(string)$bind_var_key.'.'.'-_INDEX_-'] = (string) ($ziterator+1); // new behaviour: available also in if
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
								(string) $bind_var_key.'.'.'-_MAXSIZE_-', // new behaviour: available also in if
								(string) ($mxcnt+1)
							);
							$mks_line = (string) self::replace_marker(
								(string) $mks_line,
								(string) $bind_var_key.'.'.'-_INDEX_-', // new behaviour: available also in if
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
										(string) (Smart::is_nscalar($val) ? $val : '')
									);
									$mks_line = (string) self::replace_marker(
										(string) $mks_line,
										(string) $bind_var_key.'.'.strtoupper((string)$zkey.'.'.(string)$key),
										(string) (Smart::is_nscalar($val) ? $val : '')
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
	private static function detect_subtemplates(string $mtemplate) : array {
		//--
		$arr_detected_sub_templates = array();
		//--
		if(self::have_subtemplate((string)$mtemplate) === true) {
			//--
			if(SmartEnvironment::ifDebug()) {
				$bench = microtime(true);
			} //end if
			//--
			if(self::$MkTplAnalyzeLdDbg === true) {
				$regex = '{\[@@@SUB\-TEMPLATE\:([a-zA-Z0-9_\-\.\/\!\?\|%]+)@@@\]}'; // this is a special case for debug where % must be includded
			} else {
				$regex = '{\[@@@SUB\-TEMPLATE\:([a-zA-Z0-9_\-\.\/\!\?\|]+)@@@\]}'; // here the % is missing as must not be detected as it is reserved only for special purpose if SUB-TPLS are pre-defined
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
			if(SmartEnvironment::ifDebug()) {
				$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
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
	 * Limits: max 3 levels: template -> sub-template -> sub-sub-template ; max 127 sub-tpls loads overall ; max 12 sub-levels
	 *
	 * @throws			Smart::raise_error()
	 *
	 * @param ENUM 		$y_use_caching 				:: yes / no
	 * @param STRING 	$y_base_path 				:: TPL Base Path (to use for loading sub-templates if any)
	 * @param STRING 	$mtemplate 					:: TPL string to be parsed
	 * @param ARRAY 	$y_arr_vars_sub_templates 	:: Empty Array or Mappings Array [ '%sub-tpl1%' => '@/tpl1.htm', 'tpl2.htm' => '@', 'tpl3.htm' => 'path/to/this/tpl/' ]
	 * @return STRING 								:: the prepared marker template contents
	 */
	private static function load_subtemplates(string $y_use_caching, string $y_base_path, string $mtemplate, array $y_arr_vars_sub_templates, int $sub_tpls_loaded=0, int $sub_levels=0, bool $load_subtpls=true) : string {
		//--
		$y_use_caching = (string) $y_use_caching;
		$y_base_path = (string) $y_base_path;
		$mtemplate = (string) $mtemplate;
		$y_arr_vars_sub_templates = (array) $y_arr_vars_sub_templates;
		$sub_tpls_loaded = (int) $sub_tpls_loaded;
		$sub_levels = (int) $sub_levels;
		//--
		if((int)$sub_levels <= 8) { // limit to max 12 levels as: 1 template, 1 sub-template, 1 sub-sub-template + other 9 sub-sub... levels (0..8)
			$process_sub_sub_templates = true;
		} else {
			$process_sub_sub_templates = false;
		} //end if else
		//--
		if((string)$y_base_path == '') {
			Smart::raise_error('Marker Template Load Sub-Templates: INVALID Base Path (Empty) ... / Template: '.$mtemplate);
			return (string) 'ERROR: (701) in '.__CLASS__;
		} //end if
		//--
		if(Smart::array_size($y_arr_vars_sub_templates) > 0) {
			//--
			$sub_levels++;
			//--
			if(SmartEnvironment::ifDebug()) {
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
							'', // clear (this is required for the cases the sub-templates must not includded in some cases: a kind of IF syntax ; this is used just for the case with programatically set a fixed array of templates to load)
							(string) $mtemplate
						);
						//--
						if(SmartEnvironment::ifDebug()) {
							SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
								'title' => '[TPL-Parsing:Load] :: Marker-TPL / Skipping Sub-Template File: Key='.$key.' ; *Path='.$val.' ; Sub-TPLs Loaded='.$sub_tpls_loaded.' ; Sub-Levels='.$sub_levels,
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
						//-- DO NOT MODIFY ORDER !
						if((string)substr($key, -7, 7) == '|syntax') {
							$key = (string) substr($key, 0, -7);
							$sfx = '|syntax';
						} elseif((string)substr($key, -11, 11) == '|syntaxhtml') {
							$key = (string) substr($key, 0, -11);
							$sfx = '|syntaxhtml';
						} elseif((string)substr($key, -16, 16) == '|html-no-subtpls') {
							$key = (string) substr($key, 0, -16);
							$sfx = '|html-no-subtpls';
							$load_subtpls = false;
							$y_use_caching = 'no'; // IMPORTANT: DO NOT USE CACHE IN THIS SITUATION, WILL NOT LOAD SUB-TPLS !!!
						} elseif((string)substr($key, -5, 5) == '|html') {
							$key = (string) substr($key, 0, -5);
							$sfx = '|html';
						} elseif((string)substr($key, -3, 3) == '|js') {
							$key = (string) substr($key, 0, -3);
							$sfx = '|js';
						} elseif((string)substr($key, -14, 14) == '|js-tpl-encode') {
							$key = (string) substr($key, 0, -14);
							$sfx = '|js-tpl-encode';
						} elseif((string)substr($key, -15, 15) == '|tpl-uri-encode') {
							$key = (string) substr($key, 0, -15);
							$sfx = '|tpl-uri-encode';
						} elseif((string)substr($key, -15, 15) == '|tpl-b64-encode') {
							$key = (string) substr($key, 0, -15);
							$sfx = '|tpl-b64-encode';
						} //end if
						//--
						if(((string)substr($key, 0, 1) == '%') AND ((string)substr($key, -1, 1) == '%')) { // {{{SYNC-TPLS-VARIABLE-SUB-TPL-CONDITION}}} variable, only can be set programatically, full path to the template file is specified
							if(SmartFileSysUtils::checkIfSafePath((string)$val) != 1) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template Path [%] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (704) in '.__CLASS__;
							} //end if
							if((string)substr((string)$val, 0, 2) == '@/') { // use a path suffix relative path to parent template, starting with @/ ; otherwise the full relative path is expected
								$val = (string) SmartFileSysUtils::addPathTrailingSlash((string)$y_base_path).substr((string)$val, 2);
							} //end if
							$stpl_path = (string) $val;
							$is_variable = true;
						} elseif(strpos($key, '%') !== false) { // % is not valid in other circumstances
							Smart::raise_error('Invalid Marker-TPL Sub-Template Syntax [%] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
							return (string) 'ERROR: (705) in '.__CLASS__;
						} elseif(((string)substr($key, 0, 1) == '!') AND ((string)substr($key, -1, 1) == '!')) { // path override: use this relative path instead of parent relative referenced path ; Ex: [@@@SUB-TEMPLATE:!path/to/sub-tpl.inc.htm!@@@]
							$stpl_path = (string) substr($key, 1, -1);
						} elseif(strpos($key, '!') !== false) { // ! is not valid in other circumstances
							Smart::raise_error('Invalid Marker-TPL Sub-Template Syntax [!] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
							return (string) 'ERROR: (706) in '.__CLASS__;
						} else {
							if(SmartFileSysUtils::checkIfSafePath((string)$val) != 1) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template Path [*] as: `'.$key.'` # `'.$val.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (707) in '.__CLASS__;
							} //end if
							if((string)$val == '@') { // use the same dir as parent
								$val = (string) $y_base_path;
							} elseif((string)substr((string)$val, 0, 2) == '@/') { // use a path suffix relative to parent template, starting with @/
								$val = (string) SmartFileSysUtils::addPathTrailingSlash((string)$y_base_path).substr((string)$val, 2);
							} //end if
							$stpl_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)$val).$key;
						} //end if else
						//--
						if(($is_optional === true) OR (self::$MkTplAnalyzeLdDbg === true)) { // for Analyze Make just TRY TO Load Sub-TPLs to avoid load errors if the paths are defined in the TPL-Load Array not in the TPL
							$stemplate = '';
							if(self::is_a_valid_relative_file_path_and_exists((string)$stpl_path) === true) {
								$stemplate = (string) self::read_from_optimal_place_the_template_file((string)$stpl_path, (string)$y_use_caching); // read
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
							if(self::is_a_valid_relative_file_path_and_exists((string)$stpl_path) !== true) {
								Smart::raise_error('Invalid Marker-TPL Sub-Template File for key: `'.$key.'` # `'.$stpl_path.'` '.'['.$y_base_path.']'.' detected in Template:'."\n".self::log_template($mtemplate));
								return (string) 'ERROR: (708) in '.__CLASS__;
							} //end if
							$stemplate = (string) self::read_from_optimal_place_the_template_file((string)$stpl_path, (string)$y_use_caching); // read
						} //end if else
						//--
						if($process_sub_sub_templates === true) {
							$arr_sub_sub_templates = (array) self::detect_subtemplates((string)$stemplate); // detect sub-sub templates
							$num_sub_sub_templates = Smart::array_size($arr_sub_sub_templates);
							if($num_sub_sub_templates > 0) {
								$stemplate = (string) self::load_subtemplates((string)$y_use_caching, (string)$y_base_path, (string)$stemplate, (array)$arr_sub_sub_templates, (int)$sub_tpls_loaded, (int)$sub_levels, (bool)$load_subtpls);
								$sub_tpls_loaded += $num_sub_sub_templates;
							} //end if
						} //end if
						//-- DO NOT MODIFY ORDER ! escapings must be before detecting and fixing unattended syntax ; any sequence from below must escape at least with prepare_nosyntax_content() or prepare_nosyntax_html_template() to avoid reparsing syntax
						if(($load_subtpls === false) AND ((int)$sub_levels > 1)) { // this situation can occur ONLY if base template have the `'|html-no-subtpls'` escaping
							$stemplate = '［@@@SUB-TEMPLATE:'.$pfx.$key.$sfx.'@@@］'; // no escape html ; will be done later by level zero '|html-no-subtpls' ; also these brackets will be converted back by '|html-no-subtpls' level zero by using: prepare_nosyntax_html_template()
						} else {
							if((string)$sfx == '|syntax') {
								$stemplate = (string) self::prepare_nosyntax_content((string)$stemplate); // fix here
							} elseif((string)$sfx == '|syntaxhtml') {
								$stemplate = (string) self::prepare_nosyntax_html_template((string)$stemplate); // fix here
							} elseif((string)$sfx == '|html-no-subtpls') { // on level 1 will do as |html ; on levels > 1 will skip load sub-templates
								$stemplate = (string) Smart::escape_html((string)$stemplate);
								$stemplate = (string) self::prepare_nosyntax_html_template((string)$stemplate); // fix after
							} elseif((string)$sfx == '|html') {
								$stemplate = (string) Smart::escape_html((string)$stemplate);
								$stemplate = (string) self::prepare_nosyntax_html_template((string)$stemplate); // fix after
							} elseif((string)$sfx == '|js') { // this is used to pass a tpl to a javascript string
								$stemplate = (string) self::prepare_nosyntax_content((string)$stemplate); // fix before
								$stemplate = (string) Smart::escape_js((string)$stemplate);
							} elseif((string)$sfx == '|js-tpl-encode') { // this is used to pass a tpl to js for render in js
								$stemplate = (string) self::escape_template((string)$stemplate, 'yes'); // fix here ; no need to fix before or after as encode (url escape) will escape all sequences to avoid conflicts
								$stemplate = (string) Smart::escape_js((string)$stemplate);
							} elseif((string)$sfx == '|tpl-uri-encode') { // this is used for inline uri encoded svgs
								$stemplate = (string) self::prepare_nosyntax_content((string)$stemplate); // fix before
								$stemplate = (string) Smart::escape_url((string)$stemplate); // not use the escape template here because may modify the contents (trim)
								$stemplate = (string) Smart::escape_html((string)$stemplate);
							} elseif((string)$sfx == '|tpl-b64-encode') { // this is used for inline images
								$stemplate = (string) Smart::b64_enc((string)$stemplate);
								$stemplate = (string) Smart::escape_html((string)$stemplate);
							} //end if
						} //end if else
						//-- fix unattended syntax
						if(self::have_subtemplate((string)$stemplate) === true) {
							if(self::$MkTplAnalyzeLdDbg !== true) { // if analyze TPL don't log to notice (because the [@@@SUB-TEMPLATE:%variable@@@] may not load always the variable replacements !!!
								$arr_subtpls = (array) self::analize_extract_subtpls($stemplate);
								$errmsg = 'Invalid / Undefined or Failed to load some Marker Sub-Templates detected in Template:'."\n".'SUB-TEMPLATES:'.print_r($arr_subtpls,1)."\n".self::log_template($stemplate);
								if($process_sub_sub_templates !== true) { // if false then it could not load all required sub-templates, so raise an error
									Smart::raise_error($errmsg);
									return (string) 'ERROR: (709) in '.__CLASS__;
								} else {
									Smart::log_warning($errmsg);
								} //end if
							} //end if
							$stemplate = (string) str_replace(['[@@@', '@@@]'], ['⁅@@@¦', '¦@@@⁆'], (string)$stemplate); // protect against cascade recursion or undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
						} //end if
						$mtemplate = (string) str_replace('[@@@SUB-TEMPLATE:'.$pfx.$key.$sfx.'@@@]', (string)$stemplate, (string)$mtemplate); // do replacements
						$arr_sub_sub_templates = array();
						$num_sub_sub_templates = 0;
						//--
						if(SmartEnvironment::ifDebug()) {
							SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
								'title' => '[TPL-Parsing:Load] :: Marker-TPL / INCLUDE Sub-Template File: Key='.$key.' ; Path='.$stpl_path.' ; Sub-TPLs Loaded='.$sub_tpls_loaded.' ; Sub-Levels='.$sub_levels,
								'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($stemplate)
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
				$sub_tpls_loaded++;
				if((int)$sub_tpls_loaded > 127) { // protect against infinite loop, max 127 loops (incl. sub-sub templates) :: hard limit
					Smart::log_warning('Marker-TPL: Inclusion of the Sub-Template: '.$stpl_path.' failed as it overflows the maximum hard limit: only 127 loops (sub-templates) are allowed. Current Sub-TPLs Loaded are: #'.$sub_tpls_loaded);
					break;
				} //end if
				//--
			} //end foreach
			//--
			if(SmartEnvironment::ifDebug()) {
				$bench = Smart::format_number_dec((float)(microtime(true) - (float)$bench), 9, '.', '');
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-Parsing:Load.DONE] :: Marker-TPL / INCLUDE Sub-Templates Completed ; Time = '.$bench.' sec.',
					'data' => 'Total Sub-TPLs Loaded: '.$sub_tpls_loaded
				]);
			} //end if
			//--
		} //end if
		//--
		if(self::have_subtemplate((string)$mtemplate) === true) {
			if(self::$MkTplAnalyzeLdDbg !== true) { // if analyze TPL don't log to notice (because the [@@@SUB-TEMPLATE:%variable@@@] may not load always the variable replacements !!!
				$arr_subtpls = (array) self::analize_extract_subtpls($mtemplate);
				$errmsg = (string) 'Invalid / Undefined or Failed to load some Marker Sub-Templates detected in Template:'."\n".'SUB-TEMPLATES:'.print_r($arr_subtpls,1)."\n".self::log_template($mtemplate);
				if($process_sub_sub_templates !== true) { // if false then it could not load all required sub-templates, so raise an error
					Smart::raise_error($errmsg);
					return (string) 'ERROR: (710) in '.__CLASS__;
				} else {
					Smart::log_warning($errmsg);
				} //end if
			} //end if
			$mtemplate = (string) str_replace(['[@@@', '@@@]'], ['⁅@@@¦', '¦@@@⁆'], (string)$mtemplate); // finally protect against undefined sub-templates {{{SYNC-SUBTPL-PROTECT}}}
		} //end if
		//--
		return (string) $mtemplate;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function is_a_valid_relative_file_path_and_exists(string $y_file_path) : bool {
		//--
		return (bool) SmartFileSysUtils::staticFileExists((string)$y_file_path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function read_from_fs_the_template_file(string $y_file_path) : string {
		//--
		if((strpos((string)$y_file_path, '.php.') !== false) OR ((string)substr((string)$y_file_path, -4, 4) == '.php')) {
			Smart::raise_error('ERROR: Invalid Marker-TPL File Path (PHP File Extension should not be used for a template): '.$y_file_path);
			return (string) 'ERROR: (401) in '.__CLASS__;
		} //end if
		//--
		return (string) SmartFileSysUtils::readStaticFile((string)$y_file_path, (int)Smart::SIZE_BYTES_16M, true); // {{{SYNC-TPL-MAX-SIZE}}} ; max read size enforced, don't read if oversized
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function read_from_optimal_place_the_template_file(string $y_file_path, string $y_use_caching) : string {
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
		$cached_key = 'read_from_optimal_place_the_template_file:'.$y_file_path; // {{{SYNC-TPL-DEBUG-CACHED-KEY}}}
		//--
		if(array_key_exists((string)$cached_key, (array)self::$MkTplCache)) {
			//--
			if(SmartEnvironment::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Includding a Sub-Template from VCache';
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-VCache] :: Marker-TPL / File-Read ; Serving from VCache the File Template: '.$y_file_path.' ; VCacheFlag: '.$y_use_caching,
					'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit(self::$MkTplCache[(string)$cached_key])
				]);
			} //end if
			//--
			return (string) self::$MkTplCache[(string)$cached_key];
			//--
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			if(!array_key_exists((string)$cached_key, self::$MkTplFCount)) {
				self::$MkTplFCount[(string)$cached_key] = 0;
			} //end if
			self::$MkTplFCount[(string)$cached_key]++; // register to counter anytime is read from FileSystem
		} //end if
		//--
		if((string)$y_use_caching == 'yes') {
			//--
			self::$MkTplCache[(string)$cached_key] = (string) self::read_from_fs_the_template_file($y_file_path);
			//--
			if(SmartEnvironment::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Reading a Sub-Template from FS ; VCacheFlag: '.$y_use_caching;
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-FS-Register-In-VCache] :: Marker-TPL / Registering to VCache the File Template: '.$y_file_path.' ;',
					'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit(self::$MkTplCache[(string)$cached_key])
				]);
			} //end if
			//--
			return (string) self::$MkTplCache[(string)$cached_key];
			//--
		} else {
			//--
			$mtemplate = (string) self::read_from_fs_the_template_file($y_file_path);
			//--
			if(SmartEnvironment::ifDebug()) {
				self::$MkTplVars['@SUB-TEMPLATE:'.$y_file_path][] = 'Reading a Sub-Template from FS ; VCacheFlag: '.$y_use_caching;
				SmartEnvironment::setDebugMsg('extra', 'SMART-TEMPLATING', [
					'title' => '[TPL-ReadFileTemplate-From-FS] :: Marker-TPL / File-Read ; Serving from FS the File Template: '.$y_file_path.' ;',
					'data' => '* Content SubStr[0-'.(int)self::debug_tpl_length().']: '."\n".self::debug_tpl_cut_by_limit($mtemplate)
				]);
			} //end if
			//--
			return (string) $mtemplate;
			//--
		} //end if else
		//--
		return ''; // just in case ...
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function log_template(string $mtemplate) : string {
		//--
		if(SmartEnvironment::ifDebug()) {
			return (string) $mtemplate;
		} //end if
		//--
		if(!SmartEnvironment::ifDevMode()) {
			$maxlen = 255;
			$txt = 'If the TPL is longer, switch to Dev Mode to increase the size logged or even turn on Debugging to have the full size logged ...';
		} else { // dev mode
			$maxlen = 65535;
			$txt = 'If the TPL is longer, turn on Debugging to have the full size logged ...';
		} //end if
		//--
		return (string) SmartUnicode::sub_str($mtemplate, 0, (int)$maxlen)."\n".'***** Max TPL log size is: '.(int)$maxlen.' bytes. '.$txt.' *****';
		//--
	} //END FUNCTION
	//================================================================


	//===== DEBUG ONLY


	//================================================================
	private static function debug_tpl_cut_by_limit(string $mtemplate) : string {
		//--
		$len = (int) self::debug_tpl_length();
		//--
		return (string) Smart::text_cut_by_limit((string)$mtemplate, (int)$len, true, '[...]');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private static function debug_tpl_length() : int {
		//--
		$len = 255; // default
		if(defined('SMART_SOFTWARE_MKTPL_DEBUG_LEN') AND ((int)SMART_SOFTWARE_MKTPL_DEBUG_LEN > 0)) {
			if(((int)SMART_SOFTWARE_MKTPL_DEBUG_LEN >= 255) AND ((int)SMART_SOFTWARE_MKTPL_DEBUG_LEN <= 524280)) {
				$len = (int) SMART_SOFTWARE_MKTPL_DEBUG_LEN;
			} else {
				$len = 65535;
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
	public static function registerOptimizationHintsToDebugLog() : void {
		//--
		if(SmartEnvironment::ifDebug()) {
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
			SmartEnvironment::setDebugMsg('optimizations', '*SMART-CLASSES:OPTIMIZATION-HINTS*', [
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
			SmartEnvironment::setDebugMsg('optimizations', '*SMART-CLASSES:OPTIMIZATION-HINTS*', [
				'title' => 'SmartMarkersTemplating // Optimization Notices @ Rendering Details of the current Template incl. Sub-Templates',
				'data' => (array) $optim_msg
			]);
			//--
			$optim_msg = [];
			//--
		} //end if
		//--
		// DO NOT RETURN: VOID !!
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
	public static function registerInternalCacheToDebugLog() : void {
		//--
		if(SmartEnvironment::ifInternalDebug()) {
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('extra', '***SMART-CLASSES:INTERNAL-CACHE***', [
					'title' => 'SmartMarkersTemplating // Internal Cache',
					'data' => 'Dump of Cached Templates / Sub-Templates:'."\n".print_r(self::$MkTplCache,1)
				]);
			} //end if
		} //end if
		//--
		// DO NOT RETURN: VOID !!
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
