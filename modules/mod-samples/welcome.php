<?php
// Controller: Samples/Welcome
// Route: ?/page/samples.welcome (?page=samples.welcome)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // INDEX, ADMIN, TASK, SHARED

// This is a Sample Controller of Smart.Framework / Samples Module
// The controller classes: SmartAppIndexController, SmartAppAdminController and SmartAppTaskController can be identic (extend one from another) or different or be implemented in separate controller files
// The SMART_APP_MODULE_AREA constant must be adjusted as necessary: INDEX (allow just SmartAppIndexController) ; ADMIN (allow just SmartAppAdminController) ; TASK (allow just SmartAppTaskController) ; SHARED (allow all: SmartAppIndexController, SmartAppAdminController and SmartAppTaskController) - in the same controller

/**
 * Index Controller
 *
 * @ignore
 *
 */
class SmartAppIndexController extends SmartAbstractAppController {


	public function Initialize() {
		//--
		// this is pre-run
		//--
		$this->PageViewSetCfg('template-path', 'default'); 		// set the template path (must be inside etc/templates/)
		$this->PageViewSetCfg('template-file', 'template.htm');	// set the template file
		//--
		$rand = (int) rand(0,2);
		//--
		$semaphores = [];
	//	$semaphores[] = 'skip:js-ui';
		if((int)$rand == 1) {
			$semaphores[] = 'load:searchterm-highlight-js';
		} //end if
		//--
		$semaphores[] = 'load:code-highlight-js';
		if((int)$rand == 1) {
			$semaphores[] = 'theme:dark';
		} elseif((int)$rand == 2) {
			$semaphores[] = 'theme:light';
		} //end if
		//--
	//	$semaphores[] = 'skip:unveil-js';
		//--
		$this->PageViewSetVar('semaphore', (string) Smart::array_to_list($semaphores));
		//--
	} //END FUNCTION


	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		SmartAppInfo::TestIfTemplateExists('default');
		if(!SmartAppInfo::TestIfTemplateExists('default')) {
			$this->PageViewSetErrorStatus(500, 'ERROR: The `default` template is missing ...');
			return;
		} //end if
		//--
		SmartAppInfo::TestIfModuleExists('mod-samples');
		if(!SmartAppInfo::TestIfModuleExists('mod-samples')) {
			$this->PageViewSetErrorStatus(500, 'ERROR: The `mod-samples` module is missing ...');
			return;
		} //end if
		//--

		//-- sample page variable from Request (GET/POST)
		$some_var_from_request = $this->RequestVarGet('extra_text', 'default', 'string');
		//--

		//--
		$module_area = $this->ControllerGetParam('module-area');
		$the_lang = (string) $this->ConfigParamGet('regional.language-id');
		$the_xlang = (string) $this->ConfigParamGet('regional.language-id'); // repeat this to check if caching works
		$local_1num = (string) SmartTextTranslations::formatAsLocalNumber('3141.59265359', -1, true);
		$local_2num = (string) SmartTextTranslations::formatAsLocalNumber('3.14159265359', -1, true);
		$local_3num = (string) SmartTextTranslations::formatAsLocalNumber('3141.59265359', 4, true);
		$local_4num = (string) SmartTextTranslations::formatAsLocalNumber('3.14159265359', 4, true);
		//--
		if($this->IfDebug()) {
			$this->SetDebugData('App Domain', $this->ControllerGetParam('app-domain'));
			$this->SetDebugData('App Namespace', $this->ControllerGetParam('app-namespace'));
			$this->SetDebugData('Module Area', $module_area);
			$this->SetDebugData('Module Path', $this->ControllerGetParam('module-path'));
			$this->SetDebugData('Module Views Path', $this->ControllerGetParam('module-view-path'));
			$this->SetDebugData('Module Models Path', $this->ControllerGetParam('module-model-path'));
			$this->SetDebugData('Module Libs Path', $this->ControllerGetParam('module-lib-path'));
			$this->SetDebugData('Module Templates Path', $this->ControllerGetParam('module-tpl-path'));
			$this->SetDebugData('Module Plugins Path', $this->ControllerGetParam('module-plugins-path'));
			$this->SetDebugData('Module Translations Path', $this->ControllerGetParam('module-translations-path'));
			$this->SetDebugData('Module Name', $this->ControllerGetParam('module-name'));
			$this->SetDebugData('URL Script', $this->ControllerGetParam('url-script'));
			$this->SetDebugData('URL Path', $this->ControllerGetParam('url-path'));
			$this->SetDebugData('URL Address', $this->ControllerGetParam('url-addr'));
			$this->SetDebugData('URL Page', $this->ControllerGetParam('url-page'));
			$this->SetDebugData('URL Base Domain', $this->ControllerGetParam('url-basedomain'));
			$this->SetDebugData('URL Domain', $this->ControllerGetParam('url-domain'));
			$this->SetDebugData('Current (Set) Regional Settings', SmartTextTranslations::getSafeRegionalSettings());
			$this->SetDebugData('Current (Set) Language ID', $this->ControllerGetParam('lang'));
			$this->SetDebugData('Config Language ID', $the_lang);
			$this->SetDebugData('Current Charset', $this->ControllerGetParam('charset'));
			$this->SetDebugData('Current TimeZone', $this->ControllerGetParam('timezone'));
			$this->SetDebugData('Test: Local Number Display (PI * 1000)', $local_1num);
			$this->SetDebugData('Test: Local Number Display, Reverse Sign to minus (PI * 1000)', SmartTextTranslations::reverseSignOfLocalFormattedNumber($local_1num));
			$this->SetDebugData('Test: Local Number Display (PI)', $local_2num);
			$this->SetDebugData('Test: Local Number Display, Reverse Sign to minus (PI)', SmartTextTranslations::reverseSignOfLocalFormattedNumber($local_2num));
			$this->SetDebugData('Test: Local Number Display (PI * 1000, with only 4 decimals)', $local_3num);
			$this->SetDebugData('Test: Local Number Display, Reverse Sign to minus (PI * 1000, with only 4 decimals)', SmartTextTranslations::reverseSignOfLocalFormattedNumber($local_3num));
			$this->SetDebugData('Test: Local Number Display (PI, with only 4 decimals)', $local_4num);
			$this->SetDebugData('Test: Local Number Display, Reverse Sign to minus (PI. with only 4 decimals)', SmartTextTranslations::reverseSignOfLocalFormattedNumber($local_4num));
		} //end if
		//--

		//--
		if($this->PageCacheisActive()) {
			//-- because the Request can modify the content, also the unique key must take in account variables that will vary the page config or page content vars
			$the_page_cache_key = (string) $this->PageCacheSafeKey('samples-welcome-'.$module_area.'@'.SmartTextTranslations::getLanguage().'__'.SmartHashCrypto::sha256((string)$some_var_from_request));
			//--
		} //end if
		//--

		//--
		if($this->PageCacheisActive()) {
			//--
			$test_pcache_data_arr = $this->PageGetFromCache(
				'cached-samples', // the cache sample namespace
				$the_page_cache_key  // the unique key (if there are GET/POST variables that will change the content
			);
			//--
			if(Smart::array_size($test_pcache_data_arr) > 0) {
				$pCacheInfo = (string) strtoupper((string)substr((string)SmartPersistentCache::getVersionInfo(), 0, 1));
				if($this->PageViewSetData($test_pcache_data_arr) === true) {
					//-- *optional* code
					if($this->IfDebug()) {
						$this->SetDebugData('Page Cache Info', 'Serving page from Persistent Cache (override PHP full code Execution). Page namespace/key is: cached-samples / '.$the_page_cache_key);
					} // end if
					$this->PageViewSetVar(
						'main',
						(string) SmartMarkersTemplating::render_placeholder_tpl(
							(string) $this->PageViewGetVar('main'),
							[
								'cache-key' 	=> Smart::escape_html((string)'cached-samples'.':'.$the_page_cache_key),
								'Cache-CkSum' 	=> Smart::escape_html((string)sha1((string)$this->PageViewGetVar('main'))),
								'IF-YOU-SEE-THIS-PLACEHOLDER-SOMETHING-WENT-WRONG' => '',
							]
						)
					);
					$this->PageViewPrependVar('main', "\n".'<!-- ['.Smart::escape_html($pCacheInfo).']: Cached Content: -->'."\n"); // add a markup to the HTML to know was served from cache ...
					$this->PageViewAppendVar('main',  "\n".'<!-- ['.Smart::escape_html($pCacheInfo).']: # Cached Content -->'."\n"); // add a markup to the HTML to know was served from cache ...
					//-- #end: *optional code
					return; // this is mandatory, the page was served from Cache (stop here ...)
				} //end if
			} //end if
			//--
		} //end if
		//--

		//=== if not cached, execute the code below ...

		//--
		$this->PageViewResetRawHeaders();
		$this->PageViewSetRawHeaders([
			'Z-Test-Header-1:' 	=> 'This is a test (1) with '.SmartUnicode::uc_first('mb-ucfirst'),
			'Z-Test-Header-2' 	=> SmartUnicode::uc_words('This is a test (2) with mb-ucwords')
		]);
		$this->PageViewSetRawHeader(
			'Z-Test-Header-3', 'This is a test (3)'
		);
		//--

		//-- building a semantic URL
		$url_test_unit = (string) Smart::url_add_params(
			$this->ControllerGetParam('url-script'),
			[
				'page' => 'samples.testunit',
				'tab' => 0,
				'CamelCase' => 'Test'
			],
			true // default
		); // will generate: index.php?page=samples.testunit OR admin.php?page=samples.testunit
		$url_test_unit = (string) SmartFrameworkRuntime::Create_Semantic_URL($url_test_unit); // convert the above to a pretty URL as: ?/page/(samples.)testunit (in this case index.php is ignored) OR admin.php?/page/samples.testunit
		//--

		//-- building a regular URL
		if((string)$module_area == 'admin') {
			$sign_benchmark = '[A]';
			$page_benchmark = 'samples.benchmark-with-session.html';
		} elseif((string)$module_area == 'task') {
			$sign_benchmark = '[T]';
			$page_benchmark = 'samples.benchmark-with-session.html';
		} else { // index (default)
			$sign_benchmark = '[I]';
			$page_benchmark = 'samples.benchmark.html';
		} //end if else
		$url_benchmark = (string) Smart::url_add_params(
			$this->ControllerGetParam('url-script'),
			[
				'page' => (string) $page_benchmark
			],
			false
		);
		$url_benchmark = (string) SmartFrameworkRuntime::Create_Semantic_URL($url_benchmark);
		//--

		//--
		$translator_core 			= SmartTextTranslations::getTranslator('@core', 'messages');
		//--
		$translator_mod_samples 	= SmartTextTranslations::getTranslator('mod-samples', 'samples');
		$txt_hello_world = $translator_mod_samples->text('hello-world'); // get key with defaults (escape HTML) + fallback on english if not found
		unset($translator_mod_samples); // this is just an internal test, normally the translator should not be unset ...
		$translator_mod_samples 	= SmartTextTranslations::getTranslator('mod-samples', 'samples');
		$txt_this_is = $translator_mod_samples->text('this-is');
		//--

		//--
		$this->PageViewSetVars([
			'title' => SmartUtils::extract_title('Smart Framework - A   PHP / Javascript Framework for 123 Web !!!!!', 57, true),
			'main'	=> '<h1>This text should not be displayed, it was RESET !!!</h1>'
		]);
		$this->PageViewSetVar('title', 'This title should not overwrite the above', false);
		$this->PageViewResetVar('main'); // test reset
		$this->PageViewSetVar(
			'main',
			SmartMarkersTemplating::render_file_template(
				$this->ControllerGetParam('module-view-path').'welcome.mtpl.htm',
				[
					'DATE-TIME' 		=> (string) date('Y-m-d H:i:s O'),
					'TXT-LANG' 			=> (string) $translator_core->text('lang'),
					'TXT-OK' 			=> (string) $translator_core->text('ok'),
					'TXT-HELLO-WORLD' 	=> (string) $txt_hello_world,
					'TXT-THIS-IS' 		=> (string) $txt_this_is,
					'URL-TESTUNIT'		=> (string) $url_test_unit,
					'URL-BENCHMARK'		=> (string) $url_benchmark,
					'AREA-BENCHMARK' 	=> (string) $sign_benchmark,
					'THE-LANGUAGE' 		=> (string) $the_lang,
					'THE-LANGUAGE-ID' 	=> (string) $the_xlang
				]
			)
		);
		//--
		$this->PageViewAppendVar('main', (string) (new SmartMarkdownToHTML(true, false, false, null, null, true, null, true))->parse((string)SmartFileSystem::read('README.md'))); // C:1
		$this->PageViewAppendVar('main', '<div style="text-align:right; color:#CCCCCC;">['.Smart::escape_html($some_var_from_request).']</div>'.'<hr>'.'<div style="color:#DDDDDD">Smart.Framework have Full Unicode (UTF-8) Support: '.Smart::escape_html('Unicode@String :: Smart スマート // Cloud Application Platform クラウドアプリケーションプラットフォーム :: áâãäåāăąÁÂÃÄÅĀĂĄ ćĉčçĆĈČÇ ďĎ èéêëēĕėěęÈÉÊËĒĔĖĚĘ ĝģĜĢ ĥħĤĦ ìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮ ĳĵĲĴ ķĶ ĺļľłĹĻĽŁ ñńņňÑŃŅŇ óôõöōŏőøœÒÓÔÕÖŌŎŐØŒ ŕŗřŔŖŘ șşšśŝßȘŞŠŚŜ țţťȚŢŤ ùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲ ŵŴ ẏỳŷÿýẎỲŶŸÝ źżžŹŻŽ').'</div><hr><br>');
		$txt_meta = 'Smart.Framework, a modern, high-performance   PHP / Javascript Framework (for Web) featuring MVC + Middlewares #123-456:789+10 11.12';
		$this->PageViewSetVars([
			'head-meta' => '<meta name="description" content="'.Smart::escape_html(SmartUtils::extract_description($txt_meta, 150, true)).'">'."\n".'<meta name="keywords" content="'.Smart::escape_html(SmartUtils::extract_keywords($txt_meta, 90, true)).'">'."\n"
		]);
		//--

		//-- the purpose of setting here 202 instead of 200 is just for testing the export of cfgs ...
		$this->PageViewSetOkStatus(202); // HTTP OK Status Code ; this is optional ; by default if no status code is set the 200 status code is served
		//$this->PageViewSetErrorStatus(500, 'Testing 500 Status Code ...'); // HTTP ERROR Status Code + Message ; this should be used for: 400, 403, 404, 500, 503
		//$this->PageViewSetRedirectUrl('https://demo.unix-world.org/smart-framework/', 302); // sample redirection with 302 (temporary) or can be 301 (permanent)
		//--

		//== cache page (if persistent cache is set in config)

		//-- if pCache is active this will cache the page for 1 hour ...
		if($this->PageCacheisActive()) {
			//--
			$this->PageSetInCache(
				'cached-samples', 					// the cache sample namespace
				(string) $the_page_cache_key, 		// the cache unique key (if there are GET/POST variables that will change the content
				(array)  $this->PageViewGetData(), 	// this will get the full array with all page vars, configs and heads
				(int)    3600 						// cache time: 60 mins
			);
			//--
			if($this->IfDebug()) {
				$this->SetDebugData('Page Cache Info', 'Setting page in Persistent Cache (after PHP Execution). Page key is: '.$the_page_cache_key);
			} //end if
			//--
		} else {
			//--
			if($this->IfDebug()) {
				$this->SetDebugData('Page Cache Info', 'Persistent Cache is not active. Serving Page from PHP Execution.');
			} //end if
			//--
		} //end if else
		//--

		//==

		//-- after cache content, to avoid save it into cache
		$this->PageViewSetVar(
			'main',
			(string) SmartMarkersTemplating::render_placeholder_tpl(
				(string) $this->PageViewGetVar('main'),
				[
					'cache-key' 	=> 'N/A',
					'Cache-CkSum' 	=> '-',
					'IF-YOU-SEE-THIS-PLACEHOLDER-SOMETHING-WENT-WRONG' => '',
				]
			)
		);
		//--
		$this->PageViewPrependVar('main', "\n".'<!-- [L]: Live Content -->'."\n"); // add a markup to the HTML to know was served live (not cached) ...
		$this->PageViewAppendVar('main',  "\n".'<!-- [L]: Live Content -->'."\n"); // add a markup to the HTML to know was served live (not cached) ...
		//--

	} //END FUNCTION


	public function ShutDown() {
		// nothing to do for post run ...
	} //END FUNCTION


} //END CLASS


/**
 * Admin Controller (optional)
 *
 * @ignore
 *
 */
class SmartAppAdminController extends SmartAppIndexController {

	// this will clone the SmartAppIndexController to run exactly the same action in admin.php
	// or this can implement a completely different controller if it is accessed via admin.php

} //END CLASS


/**
 * Task Controller (optional)
 *
 * @ignore
 *
 */
class SmartAppTaskController extends SmartAppAdminController {

	// this will clone the SmartAppIndexController to run exactly the same action in task.php
	// or this can implement a completely different controller if it is accessed via task.php

} //END CLASS


// end of php code
