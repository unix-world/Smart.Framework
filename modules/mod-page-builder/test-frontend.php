<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: PageBuilder/TestFrontend
// Route: ?page=page-builder.test-frontend&section=test-page
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX');

//--
if(!SmartAppInfo::TestIfModuleExists('mod-page-builder')) {
	SmartFrameworkRuntime::Raise500Error('ERROR: PageBuilder Module is missing ...');
	die('');
} //end if
//--

/**
 * Index Sample Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends \SmartModExtLib\PageBuilder\AbstractFrontendController {

	// r.20220915

	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		$section = $this->RequestVarGet('section', 'test-page', 'string');
		if((string)$section == 'test-page') {
		//	if(!$this->checkIfPageOrSegmentExist('test-page')) {
			if(!$this->checkIfPageExist('test-page')) {
				$this->PageViewSetErrorStatus(404, 'PageBuilder SampleData Not Found ...');
				return;
			} //end if
		} //end if

		$this->PageViewSetCfg('template-path', '@');
		$this->PageViewSetCfg('template-file', 'template-test-frontend.htm');

		$this->renderBuilderPage( // it does not output but set all the vars directly in the controller
			(string)$section,				// page ID
			'@',							// TPL Path
			'template-test-frontend.htm', 	// TPL File
			[ 'AREA.TOP', 'MAIN', 'AREA.FOOTER', 'TITLE', 'META-DESCRIPTION', 'META-KEYWORDS' ], // Allowed TPL Markers in page Data
			[ 'SAMPLE-MARKER' => 'this is a sample marker that have been post-rendered ... and perhaps escaped ...' ] // extra markers to render: {{=#SAMPLE-MARKER|html#=}}
		);
		$this->PageViewSetVar('title', 'Sample PageBuilder Frontend Page', false); // fallback title

		$test_segments = (array) $this->getSegmentById('#my-segment-1'); // just for test ...
		$this->PageViewAppendVar('main', 'A segment By ID %: '.Smart::escape_html(SmartUtils::pretty_print_var($test_segments)).'<br>');

		$test_segments = (array) $this->getListOfSegmentsByArea(null, '%', 'name', 'DESC', 0, 10); // just for test ...
		$this->PageViewAppendVar('main', 'List of segments ['.(int)$this->countListOfSegmentsByArea(null, '%').'] By Area %: '.Smart::escape_html(SmartUtils::pretty_print_var($test_segments)).'<br>');

		$test_segments = (array) $this->getListOfSegmentsByTag(null, 'abc', 'name', 'DESC', 0, 2); // just for test ...
		$this->PageViewAppendVar('main', 'List of segments ['.(int)$this->countListOfSegmentsByTag(null, 'abc').'] By Tag `abc`: '.Smart::escape_html(SmartUtils::pretty_print_var($test_segments)).'<br>');

		$test_segments = (array) $this->getListOfPagesByTag(null, 'abc', 'name', 'DESC', 0, 2); // just for test ...
		$this->PageViewAppendVar('main', 'List of pages ['.(int)$this->countListOfPagesByTag(null, 'abc').'] By Tag `abc`: '.Smart::escape_html(SmartUtils::pretty_print_var($test_segments)).'<br>');

		//-- this code can be uncommented to dissalow Raw Pages from PageBuilder
		/*
		if($this->IsRawPage() === true) {
			$this->PageViewSetErrorStatus(500, 'Page / Section is Invalid: RAW ...');
			return;
		} //end if
		*/
		//--

		//-- INTERNAL DEBUG
		/*
		$arr = $this->PageViewGetVars();
		$this->PageViewResetVars();
		$hdrs = $this->PageViewGetRawHeaders();
		$cfgs = $this->PageViewGetCfgs();
		$this->PageViewResetCfgs();
		$this->PageViewResetRawHeaders();
		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetVars([
			'main' => '<h1>PageBuilder / Test Frontend (Cached='.\Smart::escape_html($this->PageCacheisActive()).')</h1>'.'<pre>'.\Smart::escape_html(print_r($cfgs,1)).\Smart::escape_html(print_r($hdrs,1)).\Smart::escape_html(print_r($arr,1)).'</pre>'
		]);
		unset($cfgs);
		unset($hdrs);
		unset($arr);
		*/
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
