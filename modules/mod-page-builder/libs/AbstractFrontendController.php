<?php
// Class: \SmartModExtLib\PageBuilder\AbstractFrontendController
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\PageBuilder;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Class: AbstractFrontendController - Abstract Frontend Controller, provides the Abstract Definitions to create PageBuilder (Frontend) Controllers.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints		extend frontend controllers from this one
 *
 * @access 		PUBLIC
 *
 * @version 	v.20250301
 * @package 	development:modules:PageBuilder
 *
 */
abstract class AbstractFrontendController extends \SmartModExtLib\PageBuilder\AbstractFrontendPageBuilder {

	private const REGEX_TPL_MARKER_KEY  = '/^[A-Z0-9_\-\.@]+$/'; 	// regex for TPL markers
	private const REGEX_PBS_MARKER_KEY 	= '/^[A-Z0-9_\-\.]+$/'; 	// regex for internal markers {{{SYNC-PAGEBUILDER-REGEX-MARKERS-INT}}}

	private $max_depth 			= 2; 						// 0=page, 1=segment, 2=sub-segment (max allow depth)
	private $cache_time 		= 3600; 					// cache time in seconds ; can be overriden by define('SMART_PAGEBUILDER_RENDER_CACHE_TIME', 7200); // min 1 minute ; max 1 year

	private $crr_lang 			= '';						// current language

	private $auth_required 		= 0; 						// 0: no auth ; if > 0, will req. auth
	private $recursion_control 	= 0; 						// initialize
	private $current_page 		= []; 						// array of page load
	private $page_markers 		= []; 						// extra markers to allow be direct set in template except MAIN (and others as: TITLE, META-DESCRIPTION, META-KEYWORDS)
	private $page_params 		= []; 						// array of level zero export page params to pass from controller to plugins and @fields
	private $plugin_markers 	= []; 						// array of level zero export plugin markers

	private $page_is_cached 	= false; 					// true if found in pcache
	private $segments_cached 	= []; 						// registers cached segments

	private $render_done 		= false; 					// internal flag to avoid re-render
	private $rendered_segments 	= []; 						// register rendered segments
	private $rendered_pages 	= []; 						// register rendered pages

	private $translators 		= []; 						// registers the text translators

	private $debug 				= false; 					// internal debug


	//=====
	final public function renderBuilderPage(string $page_id, string $tpl_path, string $tpl_file, array $markers, array $arr_markers=[]) { // (OUTPUTS: HTML)

		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			$this->fatalError(
				'ERROR: Invalid Area for PageBuilder Abstract Controller: Render Page'
			);
			return;
		} //end if
		//--

		//--
		$this->max_depth = (int) $this->max_depth;
		//--

		//--
		$this->crr_lang = (string) \SmartTextTranslations::getLanguage();
		if((string)$this->crr_lang == (string)\SmartTextTranslations::getDefaultLanguage()) {
			$this->crr_lang = ''; // fix to avoid query translations on default language
		} //end if
		//--

		//--
		$page_id = (string) \trim((string)$page_id);
		//--
		if(((string)$page_id == '') OR ((string)\substr((string)$page_id, 0, 1) == '#')) {
			$this->PageViewSetErrorStatus(404, 'PageBuilder: Empty / Invalid Page ID to Render ...');
			return;
		} //end if
		//--

		//-- must test after seing if the page ID is valid
		if($this->render_done !== false) {
			$this->fatalError(
				'PageBuilder: The abstract controller '.__CLASS__.' dissalow rendering multiple pages per controller'
			);
			return;
		} //end if
		//--
		$this->render_done = true; // flag: dissalow multiple page renders per controller
		//--
		if(!\array_key_exists((string)$page_id, (array)$this->rendered_pages)) {
			$this->rendered_pages[(string)$page_id] = 0;
		} //end if
		$this->rendered_pages[(string)$page_id]++; // register rendered pages
		//--

		//--
		$this->PageViewSetCfg('template-path', (string)$tpl_path);
		$this->PageViewSetCfg('template-file', (string)$tpl_file);
		//--
		$this->page_markers = (array) $this->fixAllowedTemplateMarkers($markers);
		$cnt_page_markers = (int) \Smart::array_size($this->page_markers);
		for($i=0; $i<$cnt_page_markers; $i++) {
			if(\strpos((string)$this->page_markers[$i], 'TEMPLATE@') === 0) {
				$tmp_marker = (string) \substr((string)$this->page_markers[$i], \strlen('TEMPLATE@'));
				if((string)\trim((string)$tmp_marker) != '') {
					if(\preg_match((string)self::REGEX_PBS_MARKER_KEY, (string)$tmp_marker)) {
						if($this->IfDebug()) {
							$this->SetDebugData('Frontend PageBuilder Controller Initialize PageView Variable, No Overwrite', (string)$tmp_marker);
						} //end if
						$this->PageViewSetVar($tmp_marker, '', false); // initialize all page vars
					} //end if
				} //end if
			} //end if
		} //end foreach
		//--

		//--
		$arr = array();
		//-- {{{SYNC-PAGEBUILDER-PCACHE-ID}}}
		$the_pcache_key = (string) $page_id.'@'.\SmartTextTranslations::getLanguage(); // .'__d'.(int)$this->max_depth.'__m-'.\sha1((string)\implode(';', $this->page_markers))
		//--
		if($this->PageCacheisActive()) {
			//$arr = (array) $this->PageGetFromCache(
			$pcache_arr = (array) $this->PageGetFromCache(
				'smart-pg-builder',
				$this->PageCacheSafeKey((string)$the_pcache_key)
			); // get arr vars structure from pcache
			// \print_r($pcache_arr); die();
			if(\Smart::array_size($pcache_arr) > 0) {
				if((\is_array($pcache_arr['headers'])) && (\is_array($pcache_arr['configs'])) && (\is_array($pcache_arr['vars'])) && (\is_array($pcache_arr['params']))) { // if valid cache (test ... there must be the 3 sub-arrays as exported previous in pcache)
				//	$this->PageViewResetRawHeaders();
					$this->PageViewSetRawHeaders((array)$pcache_arr['headers']);
					$this->PageViewSetCfgs((array)$pcache_arr['configs']);
					$arr = (array) $pcache_arr['vars'];
					$this->page_params = (array) $pcache_arr['params'];
					// \print_r($arr);
					$this->page_is_cached = true;
				} //end if
			} //end if
			$pcache_arr = array();
		} //end if
		//--
		$is_ok = true;
		//--
		if($this->page_is_cached !== true) {
			$arr = (array) $this->loadSegmentOrPage((string)$page_id, 'page'); // get arr vars structure from db
		} //end if
		if((int)$this->PageViewGetStatusCode() >= 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
			if(\in_array((int)$this->PageViewGetStatusCode(), (array)\SmartFrameworkRuntime::getHttpStatusCodesERR())) {
				$is_ok = false;
			} else {
				$this->PageViewSetErrorStatus(500, 'WARNING: Invalid PageBuilder Status Code: '.(int)$this->PageViewGetStatusCode(), 'WARN');
				$is_ok = false;
			} //end if
		} //end if
		if(\Smart::array_size($arr) <= 0) {
			$is_ok = false;
			if((int)$this->PageViewGetStatusCode() < 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
				$this->PageViewSetErrorStatus(500, 'WARNING: Invalid PageBuilder Page Load Data', 'WARN');
			} //end if
		} //end if
		//--
		if(($this->page_is_cached !== true) AND ($this->PageCacheisActive())) {
			//--
			$result_pcached = (bool) $this->PageSetInCache(
				'smart-pg-builder',
				$this->PageCacheSafeKey((string)$the_pcache_key),
				[
					'headers' 	=> (array) $this->PageViewGetRawHeaders(),
					'configs' 	=> (array) $this->PageViewGetCfgs(),
					'vars' 		=> (array) $arr,
					'params' 	=> (array) $this->page_params
				], // this will het the full array with all page vars and configs
				(int) $this->getPCacheTime()
			); // save arr vars structure to pcache
			//--
			if($result_pcached === true) {
				$this->page_is_cached = true;
			} //end if
			//--
		} //end if
		//--
		if($this->IfDebug()) {
			$this->SetDebugData('Page ['.(string)$page_id.'] Pre-Render Data', $arr);
		} //end if
		//-- check if OK
		if($is_ok !== true) {
			return; // may be 404 or a different non 200 status code
		} //end if
		//-- check auth
		if($arr['auth'] > 0) { // if auth > 0, req. to be logged in
			if(\SmartAuth::is_authenticated() !== true) {
				if(!\defined('\\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS') OR (\SMART_FRAMEWORK_ENABLE_MOD_AUTH_USERS !== true)) {
					$this->PageViewSetErrorStatus(403, 'Authentication Required / Not Available');
				} else {
					$this->PageViewSetErrorStatus(401, 'Authentication Required');
				} //end if else
				return; // auth required
			} //end if
		} //end if
		//-- render the page
		$arr = (array) $this->doRenderPage($page_id, $arr);
		//--

		//-- process extra markers if supplied
		if((\Smart::array_size($this->plugin_markers) > 0) OR (\Smart::array_size($arr_markers) > 0)) {
			if(\Smart::array_size($arr) > 0) {
				foreach($arr as $kk => $vv) {
					if(!\is_array($vv)) {
						$arr[(string)$kk] = (string) $this->renderSegmentMarkers((string)$vv, (array)\array_merge((array)$this->plugin_markers, (array)$arr_markers));
					} //end if
				} //end foreach
			} //end if
		} //end if
		//--

		//--
		$this->PageViewSetVars((array)$arr);
		$arr = array(); // free mem
		//--

	} //END FUNCTION
	//=====


	//=====
	final public function getRenderedBuilderSegmentCode(string $segment_id, array $arr_markers=[]) { // (OUTPUTS: HTML)

		// CHECK: $this->rendered_segments[]

		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			$this->fatalError(
				'ERROR: Invalid Area for PageBuilder Abstract Controller: Render Segment'
			);
			return '';
		} //end if
		//--

		//--
		$this->max_depth = (int) $this->max_depth;
		//--

		//--
		$this->crr_lang = (string) \SmartTextTranslations::getLanguage();
		if((string)$this->crr_lang == (string)\SmartTextTranslations::getDefaultLanguage()) {
			$this->crr_lang = ''; // fix to avoid query translations on default language
		} //end if
		//--

		//--
		$segment_id = (string) \trim((string)$segment_id);
		//--
		if(((string)$segment_id == '') OR ((string)\substr((string)$segment_id, 0, 1) != '#')) {
			$this->fatalError(
				'WARNING: Empty / Invalid PageBuilder Segment ID to Render: ['.$segment_id.']'
			);
			return '';
		} //end if
		//--

		//--
		if(!\array_key_exists((string)$segment_id, $this->rendered_segments)) {
			$this->rendered_segments[(string)$segment_id] = 0;
		} //end if
		//-- must test after seing if the segment ID is valid
		if($this->rendered_segments[(string)$segment_id] > 0) {
			$this->fatalError(
				'PageBuilder: The abstract controller '.__CLASS__.' dissalow rendering multiple times a segment ['.$segment_id.'] per controller'
			);
			return '';
		} //end if
		//--
		$this->rendered_segments[(string)$segment_id]++; // flag: dissalow renders the same segment multiple times per controller
		//--

		//--
		$arr = array();
		//-- {{{SYNC-PAGEBUILDER-PCACHE-ID}}}
		$the_pcache_key = (string) $segment_id.'@'.\SmartTextTranslations::getLanguage(); // .'__d'.(int)$this->max_depth
		//--
		if($this->PageCacheisActive()) {
			//$arr = (array) $this->PageGetFromCache(
			$pcache_arr = (array) $this->PageGetFromCache(
				'smart-pg-builder',
				$this->PageCacheSafeKey((string)$the_pcache_key)
			); // get arr vars structure from pcache
			// \print_r($pcache_arr); die();
			if(\Smart::array_size($pcache_arr) > 0) {
				if((\is_array($pcache_arr['vars'])) && (\is_array($pcache_arr['params']))) { // if valid cache (test ... there must be the 3 sub-arrays as exported previous in pcache)
					$arr = (array) $pcache_arr['vars'];
					$this->page_params = (array) $pcache_arr['params'];
					if(!\array_key_exists((string)$segment_id, $this->segments_cached)) {
						$this->segments_cached[(string)$segment_id] = 0;
					} //end if
					$this->segments_cached[(string)$segment_id]++;
				} //end if
			} //end if
			$pcache_arr = array();
		} //end if
		//--
		if(!\array_key_exists((string)$segment_id, $this->segments_cached)) {
			$this->segments_cached[(string)$segment_id] = null;
		} //end if
		if((int)$this->segments_cached[(string)$segment_id] <= 0) {
			$arr = (array) $this->loadSegmentOrPage((string)$segment_id, 'segment'); // get arr vars structure from db
		} //end if
		if(\Smart::array_size($arr) <= 0) {
			$arr = array();
		} //end if
		//--
		if(($this->segments_cached[(string)$segment_id] <= 0) AND ($this->PageCacheisActive())) {
			//--
			$result_pcached = (bool) $this->PageSetInCache(
				'smart-pg-builder',
				$this->PageCacheSafeKey((string)$the_pcache_key),
				[
					'vars' 		=> (array) $arr,
					'params' 	=> (array) $this->page_params
				], // this will het the full array with all page vars and configs
				(int) $this->getPCacheTime()
			); // save arr vars structure to pcache
			//--
			if($result_pcached === true) {
				if(!\array_key_exists((string)$segment_id, $this->segments_cached)) {
					$this->segments_cached[(string)$segment_id] = 0;
				} //end if
				$this->segments_cached[(string)$segment_id]++;
			} //end if
			//--
		} //end if
		//--
		if($this->IfDebug()) {
			$this->SetDebugData('Segment ['.(string)$segment_id.'] Pre-Render Data', $arr);
		} //end if
		//-- chk err
		if((int)$this->PageViewGetStatusCode() >= 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
			if(\in_array((int)$this->PageViewGetStatusCode(), (array)\SmartFrameworkRuntime::getHttpStatusCodesERR())) {
				return '';
			} //end if
		} //end if
		//-- render the page
		$arr = (array) $this->doRenderSegment($segment_id, $arr);
		// \print_r($arr); die();
		//--
	/*	if((string)\trim((string)$arr['code']) == '') {
			$this->PageViewSetErrorStatus(500, 'WARNING: Empty PageBuilder Segment Code to Render ...');
			return '';
		} //end if */
		//--

		//-- process extra markers if supplied
		if((\Smart::array_size($this->plugin_markers) > 0) OR (\Smart::array_size($arr_markers) > 0)) {
			$arr['code'] = (string) $this->renderSegmentMarkers((string)$arr['code'], (array)\array_merge((array)$this->plugin_markers, (array)$arr_markers));
		} //end if
		//--

		//--
		return (string) $arr['code'];
		//--

	} //END FUNCTION
	//=====


	//===== !!! this feature can be used separately, thus it is implemented already on render page or segment if extra arr markers is provided ; but perhaps there are cases when this have to be used separately !!!
	final public function renderSegmentMarkers(string $segment_code, array $arr_markers) { // (OUTPUTS: HTML)

		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			$this->fatalError(
				'ERROR: Invalid Area for PageBuilder Abstract Controller: Render Segment Markers'
			);
			return '';
		} //end if
		//--

		//--
		$segment_code = (string) \SmartMarkersTemplating::prepare_nosyntax_content($segment_code); // Safe Fix: comment out any of: [###*###] [%%%*%%%] [@@@*@@@]
		//--
		if(\Smart::array_size($arr_markers) > 0) { // if we provide express markers for replacing
			//--
			if(\strpos((string)$segment_code, '{{=%') !== false) {
				$segment_code = (string) \str_replace( // Pre-Render: replace IF/ELSE {{=%IF|ELSE|/IF:(condition);%=}} with [%%%IF|ELSE|/IF:(condition);%%%]
					[
						'{{=%IF:',
						'{{=%ELSE:',
						'{{=%/IF:',
						'%=}}'
					],
					[
						'[%%%IF:',
						'[%%%ELSE:',
						'[%%%/IF:',
						'%%%]'
					],
					(string) $segment_code
				);
			} //end if
			//--
			if(\strpos((string)$segment_code, '{{=#') !== false) {
				$segment_code = (string) \str_replace( // Pre-Render: replace MARKERS as: {{=#MARKER|escapings#=}} with [###MARKER|escapings###]
					[
						'{{=#',
						'#=}}'
					],
					[
						'[###',
						'###]'
					],
					(string) $segment_code
				);
			} //end if
			//--
			$segment_code = (string) \SmartMarkersTemplating::render_template((string)$segment_code, (array)$arr_markers, 'yes'); // ignore if empty
			//--
		} //end if
		//--

		//--
		if((\strpos((string)$segment_code, '{{=%') !== false) OR (\strpos((string)$segment_code, '{{=#') !== false)) {
			\Smart::log_warning('ERROR: A PageBuilder segment contains Marker-TPL Syntax that could not be solved because some Marker variables are missing ...'."\n".'--- Segment Code:'."\n".$segment_code);
		} //end if
		//--

		//--
		return (string) $segment_code;
		//--

	} //END FUNCTION
	//=====


	//=====
	public function getListOfRenderedPages() {
		//--
		return (array) $this->rendered_pages;
		//--
	} //END FUNCTION
	//=====


	//=====
	public function getListOfRenderedSegments() {
		//--
		return (array) $this->rendered_segments;
		//--
	} //END FUNCTION
	//=====


	//== [ PRIVATES ] ==


	//=====
	private function getPCacheTime() {
		//--
		if(\defined('\\SMART_PAGEBUILDER_RENDER_CACHE_TIME')) {
			$time = (int) \SMART_PAGEBUILDER_RENDER_CACHE_TIME;
		} else {
			$time = (int) $this->cache_time;
		} //end if else
		//--
		if($time < (60 * 1)) {
			$time = (int) 60 * 1; // min: 1 minute
		} elseif($time > (60 * 60 * 24 * 366)) {
			$time = (int) 60 * 60 * 24 * 366; // max: 366 days
		} //end if
		//--
		$this->cache_time = (int) $time; // fix back
		//--
		return (int) $time;
		//--
	} //END FUNCTION
	//=====


	//=====
	private function fixAllowedTemplateMarkers(array $markers) {
		//--
		if(!\is_array($markers)) {
			$markers = array();
		} //end if
		//--
		if(\Smart::array_type_test($markers) != 1) { // must be non-associative array
			$markers = array();
		} //end if
		//--
		$tmp_arr = (array) $markers;
		$markers = [];
		//--
		$cnt_tmp_arr = (int) \Smart::array_size($tmp_arr);
		for($i=0; $i<$cnt_tmp_arr; $i++) {
			$tmp_arr[$i] = (string) \strtoupper((string)\trim((string)$tmp_arr[$i]));
			if((string)$tmp_arr[$i] != '') {
				if(\preg_match((string)self::REGEX_PBS_MARKER_KEY, (string)$tmp_arr[$i])) {
					if(!\in_array((string)$tmp_arr[$i], [ 'MAIN' ])) {
						$markers[] = 'TEMPLATE@'.$tmp_arr[$i];
					} //end if
				} //end if
			} //end if
		} //end foreach
		//--
		$markers = (array) \Smart::array_sort((array)$markers, 'sort');
		//--
		return (array) $markers;
		//--
	} //END FUNCTION
	//=====


	//=====
	// load settings segment
	private function loadSegmentSettingsOnly(string $id) {

		//--
		$id = (string) \trim((string)$id);
		//--

		//--
		$arr = (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getSegment((string)$id, (string)$this->crr_lang);
		//--
		if((string)$arr['id'] == '') {
			\Smart::log_warning('PageBuilder: WARNING: (500) @ '.'Invalid Settings Segment: '.$id.' in Page: '.\implode(';', $this->current_page)); // log warning, this is internal, by page settings
			return array();
		} //end if
		//--

		//--
		$yaml = (string) \Smart::b64_dec((string)$arr['data'], true); // B64 STRICT
		//--
		if((string)$yaml != '') {
			$ymp = new \SmartYamlConverter(false); // do not log YAML errors
			$yaml = (array) $ymp->parse((string)$yaml);
			$yerr = (string) $ymp->getError();
			if($yerr) {
				\Smart::log_warning('PageBuilder: WARNING: (500) @ '.'Settings Segment YAML Error: '.$id.' in Page: '.\implode(';', $this->current_page).' # '.$yerr); // log warning, this is internal, by page settings
				return array();
			} //end if
			$ymp = null;
		} else {
			$yaml = array();
		} //end if
		//--
		if($this->debug === true) {
			if($this->IfDebug()) {
				$this->SetDebugData('Settings Segment ['.(string)$id.'] Runtime Data', $yaml);
			} //end if
		} //end if
		//--

		//-- fixes
		if(!\is_array($yaml)) {
			$yaml = array();
		} //end if
		if(!\is_array($yaml['SETTINGS'])) {
			$yaml['SETTINGS'] = array();
		} //end if
		//--

		//--
		return (array) $yaml['SETTINGS'];
		//--

	} //END FUNCTION
	//=====


	//=====
	// load a text translation key mapped from YAML Data
	private function loadTranslation(string $id, array $arr_cfg, array $arr, string $lang) {

		//--
		if(!\is_array($arr_cfg)) {
			$arr_cfg = array();
		} //end if
		//--
		if(((string)$lang == '') OR (\strlen((string)$lang) != 2) OR (\SmartTextTranslations::validateLanguage((string)$lang) !== true)) {
			$lang = (string) \SmartTextTranslations::getDefaultLanguage(); // fix
		} //end if
		//--
		$escape = (string) ($arr_cfg['escape'] ?? '');
		//--
		switch((string)$escape) {
			case 'js':
				break;
			default:
				$escape = '';
		} //end if
		//--
		$arr['id'] = (string) \trim((string)$arr['id']);
		//--
		$uid = (string) 'transl.'.$lang.'.'.\Smart::uuid_10_num().'-'.\sha1((string)\print_r($arr,1)).'-'.$escape;
		//--
		$translated_text = (string) '['.$lang.']'.$arr['id'];
		//--
		$arr_parse_transl_key = (array) \explode('.', (string)$arr['id']);
		if(\Smart::array_size($arr_parse_transl_key) != 3) {
			\Smart::log_warning('PageBuilder: WARNING: (500) @ '.'Invalid Translation Key '.$arr['id'].' (1) in PageBuilder Object: '.$id.' in Page: '.\implode(';', $this->current_page)); // log warning, this is internal, by page settings
			return array();
		} //end if
		for($i=0; $i<\Smart::array_size($arr_parse_transl_key); $i++) {
			$arr_parse_transl_key[$i] = (string) \trim((string)$arr_parse_transl_key[$i]);
			if((string)$arr_parse_transl_key[$i] == '') {
				\Smart::log_warning('PageBuilder: WARNING: (500) @ '.'Invalid Translation Key '.$arr['id'].' (2) in PageBuilder Object: '.$id.' in Page: '.\implode(';', $this->current_page)); // log warning, this is internal, by page settings
				return array();
			} //end if
		} //end for
		//--
		$realm = (string) ($arr_parse_transl_key[0] ?? null).($arr_parse_transl_key[1] ?? null);
		//--
		if(!\is_array($this->translators)) {
			$this->translators = []; // init array if not array # fix for PHP8
		} //end if
		if(!\array_key_exists((string)$realm.'@'.$lang, (array)$this->translators)) {
			$this->translators[(string)$realm.'@'.$lang] = null; // init key if not exists # fix for PHP8
		} //end if
		if(!\is_object($this->translators[(string)$realm.'@'.$lang])) {
			$this->translators[(string)$realm.'@'.$lang] = \SmartTextTranslations::getTranslator((string)($arr_parse_transl_key[0] ?? null), (string)($arr_parse_transl_key[1] ?? null), (string)$lang);
		} //end if
		if(\is_object($this->translators[(string)$realm.'@'.$lang])) {
			$translated_text = (string) $this->translators[(string)$realm.'@'.$lang]->text((string)($arr_parse_transl_key[2] ?? null));
		} //end if
		//--
		$translated_text = (string) \Smart::escape_html((string)$translated_text);
		//--
		if((string)$escape == 'js') {
			$translated_text = (string) \Smart::escape_js((string)$translated_text);
		} //end if else
		//--
		$out_arr = [
			'id' 	=> (string) $uid.'.'.$id,
			'type' 	=> 'translation', // preserve type
			'auth' 	=> 0, // n/a
			'mode' 	=> (string) 'translation:'.$lang.':rendered'.($escape ? ':'.$escape : ''),
			'name' 	=> (string) $id.' :: '.\strtoupper((string)$arr['id']).' @ '.$lang.' :: '.$uid,
			'code' 	=> (string) $translated_text
		];
		//--

		//--
		return (array) $out_arr;
		//--

	} //END FUNCTION
	//=====


	//=====
	// load a text value from YAML Data
	private function loadValue(string $id, array $arr_cfg, array $arr, array $translations_arr) {

		//--
		if(!\is_array($arr_cfg)) {
			$arr_cfg = array();
		} //end if
		//--
		$syntax = (string) \trim((string)($arr_cfg['syntax'] ?? ''));
		$escape = (string) \trim((string)($arr_cfg['escape'] ?? ''));
		//--

		//--
		if((string)\SmartTextTranslations::getLanguage() !== (string)\SmartTextTranslations::getDefaultLanguage()) {
			if(\Smart::array_size($translations_arr) > 0) {
				if(\array_key_exists((string)\SmartTextTranslations::getLanguage(), (array)$translations_arr)) {
					$arr['id'] = (string) $translations_arr[(string)\SmartTextTranslations::getLanguage()];
				} //end if
			} //end if
		} //end if
		//--

		//--
		switch((string)$escape) { // {{{SYNC-PAGEBUILDER-DATA.VALUE-ESCAPINGS}}}
			case 'url':
			case 'js':
			case 'num':
			case 'dec1':
			case 'dec2':
			case 'dec3':
			case 'dec4':
			case 'int':
			case 'bool':
				break;
			default:
				$escape = ''; // must reset escape if not validated above
		} //end if
		//--
		if((string)$syntax == 'text') {
			$syntax = 'text';
			$arr['mode'] = 'text:rendered';
			$arr['id'] = (string) \trim((string)$arr['id']); // trim
			if((string)$arr['id'] != '') {
				$arr['id'] = (string) \Smart::escape_html((string)$arr['id']); // escape text to HTML
			} //end if
		} elseif((string)$syntax == 'markdown') {
			$syntax = 'markdown';
			$arr['mode'] = 'markdown:rendered';
			$arr['id'] = (string) \trim((string)$arr['id']); // trim
			if((string)$arr['id'] != '') {
				$arr['id'] = (string) \SmartModExtLib\PageBuilder\Utils::renderMarkdown((string)$arr['id']); // render as markdown
			} //end if
		} elseif((string)$syntax == 'html') {
			$syntax = 'html';
			$arr['mode'] = 'html';
			$arr['id'] = (string) \trim((string)$arr['id']); // trim
			if((string)$arr['id'] != '') {
				$arr['id'] = (string) \SmartModExtLib\PageBuilder\Utils::fixSafeCode((string)$arr['id']); // {{{SYNC-PAGEBUILDER-HTML-SAFETY}}} avoid PHP code + cleanup XHTML tag style
			} //end if
		} elseif((string)$syntax == 'jsval') {
			$syntax = 'jsval';
			$arr['mode'] = 'jsval:escaped';
			$arr['id'] = (string) \Smart::escape_js((string)$arr['id']); // JS escape text, do not trim, preserve as is
		} elseif((string)$syntax == 'urlpart') {
			$syntax = 'urlpart';
			$arr['mode'] = 'urlpart:escaped';
			$arr['id'] = (string) \Smart::escape_url((string)$arr['id']); // RawURL escape text, do not trim, preserve as is
		} elseif((string)$syntax == 'raw') {
			$syntax = 'text';
			$arr['mode'] = 'text:raw';
			$arr['id'] = (string) $arr['id']; // do not trim
			// do not escape
		} else { // 'unknown'
			if((string)$escape == '') { // if no escape provided force it to url escape which is safe in all contexts (html / js / text / markdown / url)
				$escape = 'url'; // protect !! unknown values always require an escape for safety
			} //end if
		} //end if else
		//--
		switch((string)$escape) { // {{{SYNC-PAGEBUILDER-DATA.VALUE-ESCAPINGS}}}
			case 'url': // this must be the fallback case also for raw values that miss any escapings
				$arr['id'] = (string) \Smart::escape_url((string)$arr['id']); // RawURL escape text
				break;
			case 'js':
				$arr['id'] = (string) \Smart::escape_js((string)$arr['id']);
				break;
			case 'num':
				$arr['id'] = (float) \trim((string)$arr['id']); // force value as float
				break;
			case 'dec1':
				$arr['id'] = (string) \Smart::format_number_dec((string)\trim((string)$arr['id']), 1, '.', ''); // force value as decimal1
				break;
			case 'dec2':
				$arr['id'] = (string) \Smart::format_number_dec((string)\trim((string)$arr['id']), 2, '.', ''); // force value as decimal2
				break;
			case 'dec3':
				$arr['id'] = (string) \Smart::format_number_dec((string)\trim((string)$arr['id']), 3, '.', ''); // force value as decimal3
				break;
			case 'dec4':
				$arr['id'] = (string) \Smart::format_number_dec((string)\trim((string)$arr['id']), 4, '.', ''); // force value as decimal4
				break;
			case 'int':
				$arr['id'] = (int) \trim((string)$arr['id']); // force value as int
				break;
			case 'bool':
				if(((string)$arr['id'] == '') OR ((string)$arr['id'] == '0') OR ((string)\strtolower((string)$arr['id']) == 'f') OR ((string)\strtolower((string)$arr['id']) == 'false')) {
					$arr['id'] = 'false';
				} else {
					$arr['id'] = 'true';
				} //end if else
				break;
			default:
				// no escaping
		} //end if
		//--

		//--
		$uid = (string) 'val.'.\Smart::uuid_32().':'.\sha1((string)\print_r($arr,1)).':'.$escape; // uid must be generated here as raw can change the empty escape tu url escape if no escape provided !
		//--
		$out_arr = [
			'id' 		=> (string) $uid.'.'.$id,
			'type' 		=> 'value', // preserve type
			'auth' 		=> 0, // n/a
			'mode' 		=> (string) ($arr['mode'] ?? '').($escape ? ':'.$escape : ''),
			'name' 		=> (string) $id.' :: '.\strtoupper((string)$syntax).' :: '.$uid,
			'code' 		=> (string) $arr['id'],
			'syntax'	=> (string) $syntax
		];
		//--

		//--
		return (array) $out_arr;
		//--

	} //END FUNCTION
	//=====


	//=====
	// load page or segment ; page is level -1 ; segment is higher level
	// the execution of this method is pcached thus it never returns to re-render if pcached
	private function loadSegmentOrPage(string $id, string $type, int $level=-1, array $custom_arr_render=[]) {

		//--
		$id = (string) \trim((string)$id);
		//--

		//--
		$data_arr = array();
		//--
		$data_arr['id'] = (string) $id;
		//--

		//--
		$this->recursion_control = (int) \max((int)$this->recursion_control, (int)$level);
		//--
		if((int)$level >= (int)$this->max_depth) { // fix: needs >= instead of > to comply with page/sub/sub
			$this->fatalError(
				'PageBuilder ERROR: (500): The maximum Page Recursion Level overflow on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] # Level: '.(int)$level.' of max '.(int)$this->max_depth,
				'Too much recursion detected for a PageBuilder Object'
			);
			return array();
		} //end if
		//--
		$level = (int) $level + 1;
		//--

		//--
		switch((string)$type) {
			case 'page':
				$this->current_page[] = (string) $id;
				break;
			case 'segment':
				break;
			default:
				$this->fatalError(
					'Invalid Type for PageBuilder Object: ['.(string)$id.'] @ Type: '.$type
				);
				return array();
		} //end if
		//--
		$data_arr['type'] = (string) $type;
		//--

		//--
		$is_settings_segment = false;
		//--
		if((string)$type == 'segment') {
			//--
			$arr = (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getSegment((string)$id, (string)$this->crr_lang);
			//--
			if(\Smart::array_size($arr) <= 0) {
				$this->PageViewSetErrorStatus(500, 'A PageBuilder Page Segment does not exists');
				\Smart::log_warning('PageBuilder: WARNING: (500) @ '.'Invalid Segment: '.$id.' in Page: '.\implode(';', $this->current_page)); // log warning, this is internal, by page settings
				return (array) $data_arr;
			} //end if
			//--
			$data_arr['auth'] = 0;
			//--
			if((string)$arr['mode'] == 'settings') {
				$is_settings_segment = true;
			} //end if
			//--
		} else { // page
			//--
			$arr = (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getPage((string)$id, (string)$this->crr_lang);
			//--
			if(\Smart::array_size($arr) <= 0) {
				$this->PageViewSetErrorStatus(404, 'This PageBuilder Page does not exists');
				// log no warning as this is external, by request
				return (array) $data_arr;
			} //end if
			//--
			$this->auth_required += (int) ($arr['auth'] ?? null);
			$data_arr['auth'] = (int) $this->auth_required;
			//--
		} //end if
		//--

		//--
		$data_arr['ctrl-area'] 					= (string) $arr['ctrl'];
		//--
		$data_arr['publisher-date-created'] 	= (string) \date('Y-m-d H:i:s', (int)(((int)$arr['published'] > 0) ? $arr['published'] : \time()));
		$data_arr['publisher-date-modified'] 	= (string) \date('Y-m-d H:i:s', (string)\strtotime((string)($arr['modified'] ? $arr['modified'] : \date('Y-m-d H:i:s'))));
		//--
		$data_arr['publisher-id'] 				= (string) $arr['admin'];
		//--

		//--
		$yaml = (string) \Smart::b64_dec((string)$arr['data'], true); // B64 STRICT
		//--
		if((string)\trim((string)$yaml) != '') {
			$ymp = new \SmartYamlConverter(false); // do not log YAML errors
			$yaml = (array) $ymp->parse((string)$yaml);
			$yerr = (string) $ymp->getError();
			if($yerr) {
				$this->fatalError(
					'PageBuilder: Invalid Data Structure (YAML) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] # YAML Parse Error: '.$yerr,
					'Invalid PageBuilder Object Data Structure'
				);
				return array();
			} //end if
			$ymp = null;
		} else {
			$yaml = array();
		} //end if
		//--
		if(!\is_array($yaml)) {
			$yaml = [];
		} //end if
		if((!\array_key_exists('RENDER', $yaml)) OR (!\is_array($yaml['RENDER']))) {
			$yaml['RENDER'] = [];
		} //end if
		//--
		if($this->debug === true) {
			if($this->IfDebug()) {
				$this->SetDebugData('Page / Segment ['.(string)$id.'] Runtime Data', $yaml);
			} //end if
		} //end if
		//--

		//--
		$skip_rendering = false;
		if(\array_key_exists('@', (array)$yaml['RENDER'])) {
			$skip_rendering = true; // fix: avoid render twice this type of sengments because will be rendered as value later via loadValue()
		} //end if
		//--

		//--
		$data_arr['self-code']  = '';
		if($is_settings_segment !== true) {
			$data_arr['self-code'] = (string) $arr['code'];
		} //end if else
		$data_arr['self-syntax'] = (string) $arr['mode'];
		//--
		$data_arr['mode']  = (string) $arr['mode'];
		$data_arr['name'] = (string) $arr['name'];
		//--
		if($is_settings_segment === true) {
			//--
			$data_arr['layout'] = '';
			//--
			$data_arr['code'] = '';
			//--
		} else {
			//--
			if((string)$type == 'segment') {
				$data_arr['layout'] = '';
			} else {
				$data_arr['layout'] = (string) $arr['layout']; // no html escape on this as it is a file
			} //end if else
			//--
			$data_arr['code'] = (string) \Smart::b64_dec((string)$arr['code'], true); // B64 STRICT
			if((string)$data_arr['mode'] == 'raw') { // FIX: RAW Pages might have the code empty if need to output from a plugin and to avoid inject spaces ...
				if((string)\trim((string)$data_arr['code']) == '') {
					if((string)$type == 'segment') {
						$data_arr['code'] = '';
					} else { // a raw page cannot be blank at all
						$data_arr['code'] = '{{:RAW:}}'; // otherwise a raw page can have html/text with markers as normal pages
					} //end if else
				} else {
					if((string)$type == 'segment') {
						$data_arr['code'] = (string) \Smart::escape_html((string)$data_arr['code']); // raw segment is text
					} //end if else
				} //end if
			} elseif((string)$data_arr['mode'] == 'text') {
				//\Smart::log_warning('rendering text on ID='.$arr['id']);
				$data_arr['mode'] = 'text:rendered';
				if((string)\trim((string)$data_arr['code']) != '') {
					$data_arr['code'] = (string) \Smart::escape_html((string)$data_arr['code']);
				} //end if
			} elseif((string)$data_arr['mode'] == 'markdown') {
				//\Smart::log_warning('rendering markdown on ID='.$arr['id']);
				$data_arr['mode'] = 'markdown:safe';
				if((string)\trim((string)$data_arr['code']) != '') {
					if($skip_rendering === true) { // the case of @ self content objects: for these type of objects the content is not quite need to be rendeed in this stage, but mostly validated with data keys only ; the content of this objects will be later rewritten with another referred object and the content of this objects will be used there not here in this context ...
						$data_arr['code'] = (string) \Smart::escape_html((string)$data_arr['code']); // it is too costly to render the markdown if not needed thus only make it safe (just in case) to be able to validate with data keys !
					} else {
						$data_arr['mode'] = 'markdown:rendered';
						$data_arr['code'] = (string) \SmartModExtLib\PageBuilder\Utils::renderMarkdown((string)$data_arr['code']);
					} //end if else
				} //end if
			} elseif((string)$data_arr['mode'] == 'html') {
				$data_arr['mode'] = 'html:safe';
				if((string)\trim((string)$data_arr['code']) != '') {
					$data_arr['code'] = (string) \SmartModExtLib\PageBuilder\Utils::fixSafeCode((string)$data_arr['code']); // {{{SYNC-PAGEBUILDER-HTML-SAFETY}}} avoid PHP code + cleanup XHTML tag style
				} //end if
			} //end if
			//--
		} //end if else
		//--

		//-- feature: can use custom render vars as defined in prev level, but not for zero level
		if($level > 0) {
			if((string)$type == 'segment') {
				if(\Smart::array_size($custom_arr_render) > 0) {
					$yaml['RENDER'] = (array) \array_merge((array)$yaml['RENDER'], (array)$custom_arr_render); // the custom array rewrites the original array
				} //end if
			} //end if
		} //end if
		//--

		//-- pre-parse
		$preparse_arr = [];
		if(\Smart::array_size($yaml['RENDER']) > 0) {
			foreach((array)$yaml['RENDER'] as $key => $val) {
				$key = (string) \strtoupper((string)\trim((string)$key));
				if(((string)$key != '') AND (\Smart::array_size($val) > 0)) {
					$preparse_arr[(string)$key] = [];
					foreach((array)$val as $k => $v) {
						$k = (string) \trim((string)$k);
						if((\strpos((string)$k, 'content') === 0) AND (\Smart::array_size($v) > 0)) { // can be: 'content', 'content-1', ..., 'content-n'
							if(
								(isset($v['type']) AND \Smart::is_nscalar($v['type'])) AND
								(
									((string)$v['type'] === 'field') OR
									((string)$v['type'] === 'value') OR
									((string)$v['type'] === 'translation') OR
									((string)$v['type'] === 'segment') OR
									((string)$v['type'] === 'plugin')
								)
							) {
								$preparse_arr[(string)$key][] = [(string)$k => $v];
							} else {
								$this->fatalError(
									'PageBuilder: Invalid Data Structure (1.2) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key.'/'.(string)$k,
									'Invalid PageBuilder Object Data Structure'
								);
								return array();
							} //end if
						} else {
							$this->fatalError(
								'PageBuilder: Invalid Data Structure (1.1) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key.'/'.(string)$k,
								'Invalid PageBuilder Object Data Structure'
							);
							return array();
						} //end if
					} //end foreach
				} else {
					$this->fatalError(
						'PageBuilder: Invalid Data Structure (1.0) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key,
						'Invalid PageBuilder Object Data Structure'
					);
					return array();
				} //end if
			} //end foreach
		} //end if
		//--
		$props_arr = [];
		if((string)$type == 'segment') {
			//--
			unset($data_arr['layout']);
			//--
		} elseif((string)$data_arr['mode'] == 'raw') { // {{{SYNC-PAGEBUILDER-RAWPAGE-SAFETY}}}
			//--
			unset($data_arr['layout']);
			//--
			$props_arr['rawmime'] = ''; // default to: text/html (protected against PHP code injection)
			$props_arr['rawdisp'] = ''; // default to: inline
			//--
			if(\is_array($yaml['PROPS'])) { // PROPS [ FileName, Disposition ]
				//--
				$tmp_arr_props = (array) \array_change_key_case((array)$yaml['PROPS'], \CASE_LOWER);
				//--
				$tmp_arr_props['filename'] = ($tmp_arr_props['filename'] ?? null);
				if(!\Smart::is_nscalar($tmp_arr_props['filename'])) {
					$tmp_arr_props['filename'] = null;
				} //end if
				$tmp_arr_props['disposition'] = ($tmp_arr_props['disposition'] ?? null);
				if(!\Smart::is_nscalar($tmp_arr_props['disposition'])) {
					$tmp_arr_props['disposition'] = null;
				} //end if
				//--
				if((string)$tmp_arr_props['filename'] != '') {
					//--
					$mime_type = (array) \SmartFileSysUtils::getArrMimeType((string)$tmp_arr_props['filename'], (string)$tmp_arr_props['disposition']);
					$mime_disp = (string) $mime_type[1];
					$mime_type = (string) $mime_type[0];
					//--
					switch((string)$mime_type) { // for RAW Pages allow only certain mime types
						case 'text/html':
							$props_arr['rawmime'] = ''; // default to: text/html (protected against PHP code injection)
							$props_arr['rawdisp'] = ''; // default to: inline
							break;
						case 'text/css':
						case 'application/javascript':
						case 'application/json':
						case 'application/xml':
						case 'text/plain':
						case 'image/svg+xml':
						case 'message/rfc822':
						case 'text/calendar':
						case 'text/x-vcard':
						case 'text/x-vcalendar':
						case 'text/ldif':
						case 'application/pgp-signature':
						case 'text/csv':
							$props_arr['rawmime'] = (string) $mime_type;
							$props_arr['rawdisp'] = (string) $mime_disp;
							break;
						default: // force
							$props_arr['rawmime'] = 'text/plain';
							$props_arr['rawdisp'] = 'inline';
					} //end switch
					//--
					$mime_type = null; // free mem
					$mime_disp = null; // free mem
					//--
				} //end if
				//--
			} //end if
			//--
			$data_arr['props'] = (array) $props_arr;
			//--
		} //end if
		//--

		//--
		if($level === 0) {
			$this->page_params = (array) $data_arr;
			unset($this->page_params['code']); // fix: this is not needed and can be quite big, avoid re-export in cache as the $this->page_params will be exported for each object in pcache as key 'params'
		} //end if
		//--

		//-- parse
		$data_arr['render'] = [];
		//--
		foreach($preparse_arr as $key => $val) {
			//--
			$arr_item = [];
			//--
			$key = (string) \trim((string)$key);
			//--
			if(((string)$key != '') AND (\Smart::array_type_test($val) == 1)) {
				//--
				$cnt_val = (int) \Smart::array_size($val);
				//--
				for($i=0; $i<$cnt_val; $i++) {
					//--
					if(\is_array($val[$i])) {
						//--
						foreach($val[$i] as $k => $v) {
							//--
							if(\Smart::array_size($v) > 0) {
								//-- PHP8 fixes
								$v['id'] = ($v['id'] ?? null);
								if(!\Smart::is_nscalar($v['id'])) {
									$v['id'] = null;
								} //end if
								$v['type'] = ($v['type'] ?? null);
								if(!\Smart::is_nscalar($v['type'])) {
									$v['type'] = null;
								} //end if
								//-- bugfix: when ID is used for `type: value` and is false or null, it will break the code
								if($v['id'] === null) {
									$v['id'] = 'NULL';
								} elseif($v['id'] === true) {
									$v['id'] = 'TRUE';
								} elseif($v['id'] === false) {
									$v['id'] = 'FALSE';
								} //end if
								//--
								//$v['id'] = (string) \trim((string)$v['id']); // Fix: DO NOT Pre-Trim Always ; trim ONLY if is not a type value ; raw values must be preserved (Ex: page TEMPLATE@TITLE, mostly used with raw escape may be composed from many parts like a field and a value, which will be appended, must not be trimmed ...)
								//--
								$is_id_ok = false;
								if((string)$v['type'] == 'value') {
									$is_id_ok = true; // text values can be empty, should not be trimmed, they can be used for conditionals and later rewritten
									if((string)\trim((string)$v['id']) == '') {
										$v['id'] = ''; // dissalow only spaces values
									} //end if
								} elseif((string)\trim((string)$v['id']) != '') {
									$v['id'] = (string) \trim((string)$v['id']);
									$is_id_ok = true; // must have a valid ID, the type[plugin/segment] is tested in pre-parse phase
								} //end if else
								//--
								if($is_id_ok === true) {
									//--
									$arr_tmp_item = [
										'type' 		=> (string) $v['type'],
										'id' 		=> (string) $v['id']
									];
									//--
									if((string)$v['type'] == 'field') { // {{{SYNC-PAGEBUILDER-OBJ-EXPORT-LEVEL0-FIELDS}}} ; these are the fields from level zero object
										//--
										switch((string)\strtolower((string)\trim((string)$arr_tmp_item['id']))) {
											case '@id':
												$arr_tmp_item['id'] = (string) $this->page_params['id'];
												break;
											case '@name':
												$arr_tmp_item['id'] = (string) $this->page_params['name'];
												break;
											case '@auth':
												$arr_tmp_item['id'] = (string) $this->page_params['auth'];
												break;
											case '@type':
												$arr_tmp_item['id'] = (string) $this->page_params['type'];
												break;
											case '@mode':
												$arr_tmp_item['id'] = (string) $this->page_params['mode'];
												break;
											case '@ctrl-area':
												$arr_tmp_item['id'] = (string) $this->page_params['ctrl-area'];
												break;
											case '@layout':
												$arr_tmp_item['id'] = (string) $this->page_params['layout'];
												break;
											case '@date-created':
												$arr_tmp_item['id'] = (string) $this->page_params['publisher-date-created'];
												break;
											case '@date-modified':
												$arr_tmp_item['id'] = (string) $this->page_params['publisher-date-modified'];
												break;
											case '@author-id':
												$arr_tmp_item['id'] = (string) $this->authorNameById((string)$this->page_params['publisher-id']);
												break;
											case '@self-syntax':
												$arr_tmp_item['id'] = (string) $this->page_params['self-syntax'];
												break;
											case '@self-code':
												$arr_tmp_item['id'] = (string) \Smart::b64_dec((string)$this->page_params['self-code'], true); // B64 STRICT
												if((!\array_key_exists('config', $v)) OR (!\is_array($v['config']))) {
													$v['config'] = [];
												} //end if
												if(!\array_key_exists('syntax', $v['config'])) {
													$v['config']['syntax'] = (string) $this->page_params['self-syntax']; // by default use the page syntax as config if missing
												} //end if
												break;
											default:
												// nothing, leave as is
												\Smart::log_notice((string)__METHOD__.' # Invalid Field `'.$v['id'].'` on Page/Segment: ['.\implode(';', $this->current_page).']'.'/'.(string)$id.'] for referenced segment: '.$arr_tmp_item['id']);
										} //end switch
										//--
										$arr_tmp_item = (array) $this->loadValue((string)$id, (array)($v['config'] ?? []), (array)$arr_tmp_item, []);
										//--
									} elseif((string)$v['type'] == 'value') {
										//--
										$arr_tmp_item = (array) $this->loadValue((string)$id, (array)($v['config'] ?? []), (array)$arr_tmp_item, (array)((isset($v['translations']) && \is_array($v['translations'])) ? $v['translations'] : []));
										//--
									} elseif((string)$v['type'] == 'translation') {
										//--
										$arr_tmp_item = (array) $this->loadTranslation((string)$id, (array)($v['config'] ?? []), (array)$arr_tmp_item, (string)$this->crr_lang);
										//--
									} elseif((string)$v['type'] == 'segment') {
										//--
										$arr_tmp_item['id'] = (string) '#'.$arr_tmp_item['id'];
										//--
										if((string)$arr_tmp_item['id'] == (string)$id) {
											$this->fatalError(
												'PageBuilder: Page Self Circular Reference detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for referenced segment: '.$arr_tmp_item['id'],
												'Circular self reference detected for a PageBuilder Segment'
											);
											return array();
										} //end if
										//--
										$arr_tmp_item = (array) $this->loadSegmentOrPage((string)$arr_tmp_item['id'], 'segment', (int)$level, (array)((isset($v['render']) && \is_array($v['render'])) ? $v['render'] : []));
										if((int)$this->PageViewGetStatusCode() >= 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
										//	\Smart::log_warning('PageBuilder: Sub-Object Errors on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for referenced segment: '.$arr_tmp_item['id']);
											return (array) $data_arr;
										} //end if
										//--
									} elseif((string)$v['type'] == 'plugin') {
										//-- config is available just for plugin
										$v['config'] = ($v['config'] ?? null);
										if(\is_array($v['config'])) {
											$arr_tmp_item['config'] = (array) $v['config'];
										} elseif((string)$v['config'] != '') {
											$arr_tmp_item['config:settings-segment'] = (string) '#'.$v['config'];
											$arr_tmp_item['config'] = (array) $this->loadSegmentSettingsOnly((string)'#'.$v['config']);
										} else {
											$arr_tmp_item['config'] = array();
										} //end if else
										//--
									} else {
										//--
										$this->fatalError(
											'PageBuilder: Unknown Data Type ('.(string)$v['type'].') in Runtime detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.']',
											'Unknown Data Type in Runtime detected for this Page'
										);
										return array();
										//--
									} //end if
									//--
									$arr_item[] = (array) $arr_tmp_item;
									//--
									$arr_tmp_item = [];
									//--
								} else {
									//--
									$this->fatalError(
										'PageBuilder: Invalid Data Structure (2.3) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key.'/'.(string)$k,
										'Invalid Data Structure detected for this Page'
									);
									return array();
									//--
								} //end if
								//--
							} else {
								//--
								$this->fatalError(
									'PageBuilder: Invalid Data Structure (2.2) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key.'/'.(string)$k,
									'Invalid Data Structure detected for this Page'
								);
								return array();
								//--
							} //end if
							//--
						} //end foreach
						//--
					} else {
						//--
						$this->fatalError(
							'PageBuilder: Invalid Data Structure (2.1) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key,
							'Invalid Data Structure detected for this Page'
						);
						return array();
						//--
					} //end if else
					//--
				} //end for
				//--
			} else {
				//--
				$this->fatalError(
					'PageBuilder: Invalid Data Structure (2.0) detected on Page/Segment: ['.\implode(';', $this->current_page).'/'.(string)$id.'] for key: '.(string)$key,
					'Invalid Data Structure detected for this Page'
				);
				die();
				return array();
				//--
			} //end if
			//--
			if(\Smart::array_size($arr_item) > 0) {
				//--
				$data_arr['render'][(string)$key] = (array) $arr_item;
				//--
			} //end if
			//--
		} //end foreach
		//--

		//-- cleanup
		/*
		unset($preparse_arr);
		unset($arr_item);
		unset($arr_tmp_item);
		unset($key);
		unset($val);
		unset($k);
		unset($v);
		unset($i);
		*/
		//--

		//--
		return (array) $data_arr;
		//--

	} //END FUNCTION
	//=====


	//=====
	private function doRenderPage(string $id, array $data_arr) {

		//--
		return (array) $this->doRenderObject($id, $data_arr, -1); // pages MUST START AT -1 !!! {{{SYNC-PAGEBUILDER-RENDER-LEVELS}}}
		//--

	} //END FUNCTION
	//=====


	//=====
	private function doRenderSegment(string $id, array $data_arr) {

		//--
		return (array) $this->doRenderObject($id, $data_arr, 0); // segments MUST START AT 0 !!! {{{SYNC-PAGEBUILDER-RENDER-LEVELS}}}
		//--

	} //END FUNCTION
	//=====


	//=====
	private function doRenderObject(string $id, array $data_arr, int $level) {

		//--
		$level = (int) ((int)$level + 1); // must increment at start (pages default start at: -1 ; segments default start at : 0) {{{SYNC-PAGEBUILDER-RENDER-LEVELS}}}
		//--

		//--
		if($level === 0) {
			if(\SmartModExtLib\PageBuilder\Utils::allowPages() !== true) {
				$this->PageViewSetErrorStatus(503, 'PageBuilder: Page Objects are Disabled ... Only Segments are Allowed', 'WARN');
				return array();
			} //end if
		} //end if
		//--

		//--
		if((int)$this->PageViewGetStatusCode() >= 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
			return array(); // skip on first err to preserve the last status code
		} //end if
		//--

		//--
		if(!\is_array($data_arr)) {
			$this->PageViewSetErrorStatus(500, 'PageBuilder: Invalid Render Data Format on Page/Segment');
			\Smart::log_warning('PageBuilder: Invalid Render Data Format on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
			return array();
		} //end if
		//--
		if(!\is_array($data_arr['render'])) {
			$this->PageViewSetErrorStatus(500, 'PageBuilder: Invalid Render Data on Page/Segment');
			\Smart::log_warning('PageBuilder: Invalid Render Data on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
			return array();
		} //end if
		//--

		//--
		$is_raw_page = false;
		//--
		if((string)$data_arr['mode'] == 'settings') {
			//--
			$data_arr['code'] = ''; // clear ; this is n/a on a settings page
			//--
		} elseif((string)$data_arr['mode'] == 'raw') {
			//--
			if($level === 0) { // {{{SYNC-PAGEBUILDER-RENDER-LEVELS}}} (Level Zero is just for Pages, not for segments) ;
				//--
				$is_raw_page = true; // {{{SYNC-PAGEBUILDER-RAWPAGE-SAFETY}}}
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				if(\Smart::array_size($data_arr['props']) > 0) { // for RAW Page this is mandatory
					//--
					if((string)$data_arr['props']['rawmime'] != '') {
						$this->PageViewSetCfg('rawmime', (string)$data_arr['props']['rawmime']);
					} else { // text/html : to avoid security risk, escape all PHP code
						$data_arr['code'] = (string) \SmartModExtLib\PageBuilder\Utils::fixSafeCode((string)$data_arr['code']); // {{{SYNC-PAGEBUILDER-HTML-SAFETY}}} avoid PHP code + cleanup XHTML tag style
						$data_arr['props']['rawdisp'] = ''; // in this case do not use ...
					} //end if
					//--
					if((string)$data_arr['props']['rawdisp'] != '') {
						$this->PageViewSetCfg('rawdisp', (string)$data_arr['props']['rawdisp']);
					} //end if
					//--
				} else {
					$this->PageViewSetErrorStatus(500, 'Invalid Raw Page Data Props on Page/Segment');
					\Smart::log_warning('PageBuilder: Invalid Raw Page Data Props on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
					return array();
				} //end if
				//--
			} else { // do not allow RAW Page at higher levels than zero
				//--
				$this->PageViewSetErrorStatus(500, 'Invalid Raw Page/Segment at Level ['.$level.']');
				\Smart::log_warning('PageBuilder: Invalid Raw Page/Segment at Level ['.$level.']: '.(string)$id.' ; Level: '.(int)$level);
				return array();
				//--
			} //end if
			//--
		} //end if
		//--

		//--
		if($level === 0) { // {{{SYNC-PAGEBUILDER-RENDER-LEVELS}}} (Level Zero is just for Pages, not for segments)
			//--
			if($is_raw_page === true) {
				$data_arr['smart-markers'] = [
					'MAIN' 				=> ''
				];
			} else {
				if((string)\trim((string)$data_arr['layout']) != '') {
					$this->PageViewSetCfg('template-file', (string)$data_arr['layout']);
				} //end if
				$data_arr['smart-markers'] = [
					'MAIN' 				=> '',
					'TITLE' 			=> '',
					'META-DESCRIPTION' 	=> '',
					'META-KEYWORDS' 	=> ''
				];
			} //end if else
			//--
		} else {
			//--
			if((string)\substr((string)$data_arr['id'], 0, 1) != '#') { // on levels 1+ allow just segments !!!
				$this->PageViewSetErrorStatus(500, 'Invalid Segment to Render on Level: '.(int)$level);
				\Smart::log_warning('PageBuilder: Invalid Segment to Render on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
				return array();
			} //end if
			//--
		} //end if
		//--

	//	\print_r($data_arr); die();

		//--
		$arr_replacements = [];
		//--
		foreach($data_arr['render'] as $key => $val) {
			//--
			if(\Smart::array_type_test($val) == 1) {
				//--
				$cnt_val = (int) \Smart::array_size($val);
				//--
				for($i=0; $i<$cnt_val; $i++) {
					//--
					$plugin_obj 			= null; // reset each cycle
					$plugin_raw_heads 		= null; // reset each cycle
					$plugin_page_settings 	= null; // reset each cycle
					$plugin_exec 			= null; // reset each cycle
					$plugin_status 			= null; // reset each cycle
					//--
					$is_self_content = false;
					//--
					if(((string)$key != '') AND (\preg_match((string)self::REGEX_TPL_MARKER_KEY, (string)$key))) {
						//--
						if((string)$key == '@') {
							$is_self_content = true;
						} //end if
						//--
						if((string)$val[$i]['type'] == 'plugin') { // INFO: each template must provide it's content (already cached or not) and the pcache key suffixes
							//--
							$plugin_id 		= (string) $val[$i]['id'];
							$plugin_cfg 	= (array)  $val[$i]['config'];
							//--
							$plugin_part_d = (string) \trim((string)\Smart::safe_filename((string)\Smart::dir_name((string)$plugin_id)));
							$plugin_part_f = (string) \trim((string)\Smart::safe_filename((string)\Smart::base_name((string)$plugin_id)));
							//--
							$plugin_path = '';
							$plugin_class = '';
							//--
							if(((string)$plugin_part_d != '') AND ((string)$plugin_part_f != '')) {
								//--
								$plugin_modpath = (string) \Smart::safe_pathname((string)'modules/mod-'.$plugin_part_d.'/');
								$plugin_fname   = (string) \Smart::safe_filename((string)$plugin_part_f);
								$plugin_path 	= (string) \Smart::safe_pathname((string)$plugin_modpath.'plugins/'.$plugin_fname.'.php');
								$plugin_class 	= (string) 'PageBuilderFrontendPlugin'.\SmartModExtLib\PageBuilder\Utils::composePluginClassName($plugin_part_d).\SmartModExtLib\PageBuilder\Utils::composePluginClassName($plugin_part_f);
								//--
								if(((string)$plugin_path != '') AND (\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$plugin_fname)) AND (\SmartFileSysUtils::checkIfSafePath((string)$plugin_modpath)) AND (\SmartFileSysUtils::checkIfSafePath((string)$plugin_path)) AND (\SmartFileSystem::is_type_file((string)$plugin_path))) {
									//--
									require_once((string)$plugin_path);
									//--
									if(((string)$plugin_class != 'PageBuilderFrontendPlugin') AND (\class_exists((string)$plugin_class))) {
										//--
										if(\is_subclass_of((string)$plugin_class, '\\SmartModExtLib\\PageBuilder\\AbstractFrontendPlugin')) {
											//--
											$plugin_obj = new $plugin_class( // fix w. SmartAbstractAppController r.20200121 and later
												(string) $plugin_modpath, // this should be the module path to plugin's module
												(string) $this->ControllerGetParam('controller'), // this is the controller path where plugin runs into (it can be used to re-build the path to the current module)
												(string) $this->ControllerGetParam('url-page'), // the URL Page Param
												(string) 'index' // $this->ControllerGetParam('module-area') // the hard-coded Area
											);
											$plugin_obj->initPlugin((string)$plugin_fname, (array)$plugin_cfg, (string)$this->ControllerGetParam('module-path'), (array)$this->page_params, (array)$data_arr); // initialize before run !
											//--
											$plugin_status = 0;
											$plugin_skip_run = false;
											//--
											$plugin_status = $plugin_obj->Initialize(); // mixed: null (void) / FALSE / TRUE / INT Status-Code
											$plugin_page_settings = (array) $plugin_obj->PageViewGetCfgs();
											if(
												(($plugin_status === false) OR (($plugin_status !== true) AND ((int)$plugin_status != 0))) OR
												((isset($plugin_page_settings['status-code'])) AND ((int)$plugin_page_settings['status-code'] != 0)) // {{{SYNC-SMART-FRAMEWORK-HANDLE-HTTP-STATUS-CODE}}}
											) {
												$plugin_skip_run = true; // skip Run
											} else {
												$plugin_status = $plugin_obj->Run(); // mixed: null (void) / FALSE / TRUE / INT Status-Code
												$plugin_page_settings = (array) $plugin_obj->PageViewGetCfgs();
											} //end if else
											if($plugin_status === false) {
												$plugin_status = 500;
											} elseif($plugin_status === true) {
												$plugin_status = 200;
											} else {
												$plugin_status = intval($plugin_status);
											} //end if
											$plugin_status = (int) $plugin_status; // ensure int
											$plugin_obj->ShutDown();
											if((isset($plugin_page_settings['status-code'])) AND ((int)$plugin_page_settings['status-code'] != 0)) { // {{{SYNC-SMART-FRAMEWORK-HANDLE-HTTP-STATUS-CODE}}}
												if(((int)$plugin_status != 0) AND ((int)$plugin_status != (int)$plugin_page_settings['status-code'])) {
													\Smart::log_warning('PageBuilder: Render Template WARNING: Different HTTP Status Codes (Set='.(int)$plugin_page_settings['status-code'].'; Exit='.(int)$plugin_status.') in: ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.'PLUGIN'.') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
												} //end if
												$plugin_page_settings['status-code'] = (int) $plugin_page_settings['status-code']; // this rewrites what the Run() function returns, which is very OK as this is authoritative !
												if(!\in_array((int)$plugin_page_settings['status-code'], (array)\SmartFrameworkRuntime::getHttpStatusCodesALL())) {
													\Smart::log_notice('PageBuilder: Render Template ERROR: Wrong HTTP Status Code (Set='.(int)$plugin_page_settings['status-code'].') in: ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.'PLUGIN'.') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
													$plugin_page_settings['status-code'] = 200;
												} //end if
											} else {
												$plugin_page_settings['status-code'] = 200;
												if((int)$plugin_status != 0) {
													if(!\in_array((int)$plugin_status, (array)\SmartFrameworkRuntime::getHttpStatusCodesALL())) {
														\Smart::log_notice('PageBuilder: Render Template ERROR: Wrong HTTP Status Code (Return='.(int)$plugin_status.') in: ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.'PLUGIN'.') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
													} else {
														$plugin_page_settings['status-code'] = (int) $plugin_status;
													} //end if
												} //end if
											} //end if
											//--
											$plugin_exec = (array) $plugin_obj->PageViewGetVars();
											// \Smart::log_notice(\print_r($plugin_exec,1));
											//--
											if((int)$plugin_page_settings['status-code'] < 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
												//-- expires, modified
												if(isset($plugin_page_settings['expires']) AND ((int)$plugin_page_settings['expires'] > 0)) {
													$this->PageViewSetCfg('expires', (int)$plugin_page_settings['expires']);
													$this->PageViewSetCfg('modified', (int)$plugin_page_settings['modified']);
												} //end if
											} //end if
											//--
											if((int)$plugin_page_settings['status-code'] >= 400) { // {{{SYNC-MIDDLEWARE-MIN-ERR-STATUS-CODE}}}
												//--
												if((int)$this->PageViewGetStatusCode() < (int)$plugin_page_settings['status-code']) {
													$this->PageViewSetErrorStatus((int)$plugin_page_settings['status-code'], (string)$plugin_page_settings['error']);
												} //end if
												//--
												$plugin_exec['meta-title'] = ''; 		// reset
												$plugin_exec['meta-description'] = ''; 	// reset
												$plugin_exec['meta-keywords'] = ''; 	// reset
												$plugin_exec['content'] = ''; 			// reset
												//--
											} elseif(((int)$plugin_page_settings['status-code'] == 301) OR ((int)$plugin_page_settings['status-code'] == 302)) {
												//--
												if((string)$plugin_page_settings['redirect-url'] != '') {
													//--
													if((int)$this->PageViewGetStatusCode() < (int)$plugin_page_settings['status-code']) {
														$this->PageViewSetRedirectUrl((string)$plugin_page_settings['redirect-url'], (int)$plugin_page_settings['status-code']);
													} //end if
													//--
												} //end if
												//--
											} else { // 2xx / 304
												//--
												if((int)$this->PageViewGetStatusCode() < (int)$plugin_page_settings['status-code']) {
													$this->PageViewSetOkStatus((int)$plugin_page_settings['status-code']);
												} //end if
												//-- rawpage, rawmime, rawdisp
												if(isset($plugin_page_settings['rawpage'])) {
													$plugin_page_settings['rawpage'] = (string) \strtolower((string)$plugin_page_settings['rawpage']);
													if((string)$plugin_page_settings['rawpage'] == 'yes') {
														$this->PageViewSetCfg('rawpage', true);
													} //end if
												} else {
													$plugin_page_settings['rawpage'] = null;
												} //end if
												if((string)$plugin_page_settings['rawpage'] != 'yes') {
													$plugin_page_settings['rawpage'] = '';
												} //end if
												if((string)$plugin_page_settings['rawpage'] == 'yes') {
													if(isset($plugin_page_settings['rawmime'])) {
														$plugin_page_settings['rawmime'] = (string) \trim((string)$plugin_page_settings['rawmime']);
														if((string)$plugin_page_settings['rawmime'] != '') {
															$this->PageViewSetCfg('rawmime', (string)$plugin_page_settings['rawmime']);
														} //end if
													} //end if else
												} //end if
												if((string)$plugin_page_settings['rawpage'] == 'yes') {
													if(isset($plugin_page_settings['rawdisp'])) {
														$plugin_page_settings['rawdisp'] = (string) \trim((string)$plugin_page_settings['rawdisp']);
														if((string)$plugin_page_settings['rawdisp'] != '') {
															$this->PageViewSetCfg('rawdisp', (string)$plugin_page_settings['rawdisp']);
														} //end if
													} //end if else
												} //end if
												//-- raw heads
												$plugin_raw_heads = (array) $plugin_obj->PageViewGetRawHeaders();
												if(\Smart::array_size($plugin_raw_heads) > 0) {
													$this->PageViewSetRawHeaders((array)$plugin_raw_heads);
												} //end if
												//--
												if(isset($plugin_exec['meta-title']) AND ((string)$plugin_exec['meta-title'] != '')) {
													$data_arr['@meta-title'] = (string) $plugin_exec['meta-title'];
												} //end if
												if(isset($plugin_exec['meta-description']) AND ((string)$plugin_exec['meta-description'] != '')) {
													$data_arr['@meta-description'] = (string) $plugin_exec['meta-description'];
												} //end if
												if(isset($plugin_exec['meta-keywords']) AND ((string)$plugin_exec['meta-keywords'] != '')) {
													$data_arr['@meta-keywords'] = (string) $plugin_exec['meta-keywords'];
												} //end if
												//--
												$plugin_exp_vars = (array) $plugin_obj->getPluginExportVars();
												if((\Smart::array_size($plugin_exp_vars) > 0) AND (\Smart::array_type_test($plugin_exp_vars) == 2)) {
													foreach($plugin_exp_vars as $export_key => $export_var) {
														if(\preg_match((string)self::REGEX_PBS_MARKER_KEY, (string)$export_key)) {
															$this->plugin_markers[(string)$export_key] = (string) $export_var; // later values will rewrite previous ones if any
														} //end if
													} //end foreach
												} //end if
												$plugin_exp_vars = null;
												//--
												if(($level === 0) AND (\strpos((string)$key, 'TEMPLATE@') === 0) AND (\in_array((string)$key, (array)$this->page_markers))) { // ((string)$key != 'TEMPLATE@MAIN')) { // allow TEMPLATE@*(!MAIN) just on main page (level=0)
													//-- don't replace these markers, they are template markers
													if(!\array_key_exists((string)\substr((string)$key, \strlen('TEMPLATE@')), $data_arr['smart-markers'])) {
														$data_arr['smart-markers'][(string)\substr((string)$key, \strlen('TEMPLATE@'))] = '';
													} //end if
													$data_arr['smart-markers'][(string)\substr((string)$key, \strlen('TEMPLATE@'))] .= (string) ($plugin_exec['content'] ?? null); // append is mandatory here else will not render correctly more than one sub-segment/plugin
													//--
												} elseif(\preg_match((string)self::REGEX_PBS_MARKER_KEY, (string)$key)) {
													//--
													if(\strpos((string)$data_arr['code'], '{{:'.(string)$key) !== false) {
														//-- replace these markers, they are page markers
														if(!\array_key_exists('{{:'.(string)$key.':}}', $arr_replacements)) {
															$arr_replacements['{{:'.(string)$key.':}}'] = '';
														} //end if
														$arr_replacements['{{:'.(string)$key.':}}'] .= (string) ($plugin_exec['content'] ?? null); // OK: always append
														//--
													} else {
														//--
														\Smart::log_notice('PageBuilder: Render Template WARNING: Unused Render Marker (Plugin): ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.'PLUGIN'.') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
														//--
													} //end if
													//--
												} else {
													//--
													$this->PageViewSetErrorStatus(500, 'PageBuilder: Render Template ERROR: Invalid Render Marker (3)');
													\Smart::log_warning('PageBuilder: Render Template ERROR: Invalid Render Marker (3): ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.'PLUGIN'.') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
													//--
												} //end if else
												//--
											} //end if
											//--
										} else {
											//--
											$this->PageViewSetErrorStatus(500, 'Plugin Class is Invalid');
											\Smart::log_warning('PageBuilder: Plugin Class is Invalid ['.$plugin_id.']: '.$plugin_class);
											//--
										} //end if else
										//--
									} else {
										//--
										$this->PageViewSetErrorStatus(500, 'PageBuilder: Plugin Class is Missing');
										\Smart::log_warning('PageBuilder: Plugin Class is Missing ['.$plugin_id.']: '.$plugin_class);
										//--
									} //end if else
								} else {
									//--
									$this->PageViewSetErrorStatus(500, 'PageBuilder: Plugin is Missing');
									\Smart::log_warning('PageBuilder: Plugin is Missing ['.$plugin_id.']: '.$plugin_path);
									//--
								} //end if else
							} else {
								//--
								$this->PageViewSetErrorStatus(500, 'PageBuilder: Invalid Plugin');
								\Smart::log_warning('PageBuilder: Invalid Plugin: '.$plugin_id);
								//--
							} //end if else
							//--
						} else { // page / segment
							//--
							if(isset($val[$i]['render']) && \is_array($val[$i]['render'])) {
								$val[$i] = (array) $this->doRenderObject((string)$id, (array)$val[$i], (int)$level);
							} //end if
							//--
							if(isset($val[$i]['mode']) AND ((string)$val[$i]['mode'] == 'settings')) {
								//--
								$this->PageViewSetErrorStatus(500, 'PageBuilder: Render Template ERROR: Settings Segment Pages cannot be used for rendering context');
								\Smart::log_warning('PageBuilder: Render Template ERROR: Settings Segment Pages cannot be used for rendering context: ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.$val[$i]['mode'].') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
								//--
							} else {
								//--
								if(($level === 0) AND (\strpos((string)$key, 'TEMPLATE@') === 0) AND (\in_array((string)$key, (array)$this->page_markers))) { // ((string)$key != 'TEMPLATE@MAIN')) { // allow TEMPLATE@*(!MAIN) just on main page (level=0)
									//-- don't replace these markers, they are template markers
									if(!\array_key_exists((string)\substr((string)$key, \strlen('TEMPLATE@')), $data_arr['smart-markers'])) {
										$data_arr['smart-markers'][(string)\substr((string)$key, \strlen('TEMPLATE@'))] = '';
									} //end if
									$data_arr['smart-markers'][(string)\substr((string)$key, \strlen('TEMPLATE@'))] .= (string) $val[$i]['code']; // append is mandatory here else will not render correctly more than one sub-segment/plugin
									//--
								} elseif(\preg_match((string)self::REGEX_PBS_MARKER_KEY, (string)$key)) {
									//--
									if(\strpos((string)$data_arr['code'], '{{:'.(string)$key) !== false) {
										//-- replace these markers, they are page markers
										if(!\array_key_exists('{{:'.(string)$key.':}}', $arr_replacements)) {
											$arr_replacements['{{:'.(string)$key.':}}'] = '';
										} //end if
										//$arr_replacements['{{:'.(string)$key.':}}'] .= '<!-- Segment['.(int)$i.']: '.\Smart::escape_html((string)$key).' -->';
										$arr_replacements['{{:'.(string)$key.':}}'] .= (string) ($val[$i]['code'] ?? null); // OK: always append
										//$arr_replacements['{{:'.(string)$key.':}}'] .= '<!-- /Segment['.(int)$i.']: '.\Smart::escape_html((string)$key).' -->';
										//--
									} else {
										//--
										\Smart::log_notice('PageBuilder: Render Template WARNING: Unused Render Marker: ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.$val[$i]['mode'].') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
										//--
									} //end if
									//--
								} elseif($is_self_content === true) {
									//--
									if(!\array_key_exists('@', $arr_replacements)) {
										$arr_replacements['@'] = '';
									} //end if
									$arr_replacements['@'] .= (string) ($val[$i]['code'] ?? null); // OK: always append
									//--
								} else {
									//--
									$this->PageViewSetErrorStatus(500, 'PageBuilder: Render Template ERROR: Invalid Render Marker (2)');
									\Smart::log_warning('PageBuilder: Render Template ERROR: Invalid Render Marker (2): ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.$val[$i]['mode'].') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
									//--
								} //end if else
								//--
							} //end if else
							//--
						} //end if else
						//--
					} else {
						//--
						$this->PageViewSetErrorStatus(500, 'PageBuilder: Render Template ERROR: Invalid Render Marker (1)');
						\Smart::log_warning('PageBuilder: Render Template ERROR: Invalid Render Marker (1): ['.(string)$key.'] @ '.(string)$data_arr['id'].'/'.(string)$val[$i]['id'].' ('.(string)$val[$i]['type'].'/'.$val[$i]['mode'].') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
						//--
					} //end if else
					//--
				} //end for
				//--
			} else {
				//--
				$this->PageViewSetErrorStatus(500, 'PageBuilder: Render Template ERROR: Invalid Render Data Type');
				\Smart::log_warning('PageBuilder: Render Template ERROR: Invalid Render Data Type: ['.(string)$key.' @ '.(string)$data_arr['id'].' ('.(string)$val[$i]['type'].'/'.$val[$i]['mode'].') on Page/Segment: '.(string)$id.' ; Level: '.(int)$level);
				//--
			} //end if
			//--
		} //end foreach
		//--
		if(\Smart::array_size($arr_replacements) > 0) {
			if(\array_key_exists('@', $arr_replacements)) {
				$data_arr['code'] = (string) $arr_replacements['@'];
				unset($arr_replacements['@']);
			} //end if
			$data_arr['code'] = (string) \strtr((string)$data_arr['code'], (array)$arr_replacements); // since strtr treats strings as a sequence of bytes, and since UTF-8 and other multibyte encodings use - by definition - more than one byte for at least some characters, the unicode strings is likely to have problems. Fix: use the associative array as 2nd param to specify the mapping instead of using it with 3 params ; using strtr() for str replace with no recursion instead of str_replace() which goes with recursion over already replaced parts and is not safe in this context
		} //end if
		//--

		//--
		unset($arr_replacements);
		unset($data_arr['render']);
		//--
		if($level === 0) {
			//--
			$data_arr['smart-markers']['MAIN'] = (string) $data_arr['code'];
			unset($data_arr['code']);
			//--
		} //end if
		//--

		//-- manage meta from plugins
		if(\in_array('TEMPLATE@TITLE', (array)$this->page_markers)) {
			if(isset($data_arr['@meta-title']) AND ((string)$data_arr['@meta-title'] != '')) {
				$data_arr['smart-markers']['TITLE'] = (string) $data_arr['@meta-title'];
			} //end if
		} //end if
		unset($data_arr['@meta-title']);
		//--
		if(\in_array('TEMPLATE@META-DESCRIPTION', (array)$this->page_markers)) {
			if(isset($data_arr['@meta-description']) AND ((string)$data_arr['@meta-description'] != '')) {
				$data_arr['smart-markers']['META-DESCRIPTION'] = (string) $data_arr['@meta-description'];
			} //end if
		} //end if
		unset($data_arr['@meta-description']);
		//--
		if(\in_array('TEMPLATE@META-KEYWORDS', (array)$this->page_markers)) {
			if(isset($data_arr['@meta-keywords']) AND ((string)$data_arr['@meta-keywords'] != '')) {
				$data_arr['smart-markers']['META-KEYWORDS'] = (string) $data_arr['@meta-keywords'];
			} //end if
		} //end if
		unset($data_arr['@meta-keywords']);
		//--

		//--
		if($level === 0) {
			return (array) $data_arr['smart-markers'];
		} else {
			return (array) $data_arr;
		} //end if else
		//--

	} //END FUNCTION
	//=====


	//=====
	private function authorNameById(string $author_id) {
		//--
		$author_id = (string) \trim((string)$author_id);
		//--
		if((string)$author_id == '') {
			$author_id = '???';
		} //end if
		//--
		$author_id = (string) \str_replace(['.', '-', '_'], ' ', (string)$author_id);
		//--
		$author_id = (string) \ucwords((string)$author_id);
		//--
		return (string) $author_id;
		//--
	} //END FUNCTION
	//=====


	//=====
	private function fatalError(string $err_log, string $err_display='') {
		//--
		\Smart::raise_error(
			'PageBuilder ERROR: '.$err_log,
			'PageBuilder Render ERROR'.(((string)$err_display != '') ? ': '.$err_display : '')
		);
		//--
		die();
		//--
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
