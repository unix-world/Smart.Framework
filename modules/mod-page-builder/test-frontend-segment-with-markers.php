<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: PageBuilder/TestFrontendSegmentWithMarkers
// Route: ?page=page-builder.test-frontend-segment-with-markers
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
		if((!$this->checkIfPageOrSegmentExist('#website-menu')) OR (!$this->checkIfPageOrSegmentExist('#segment-with-markers')) OR (!$this->checkIfPageOrSegmentExist('#website-footer'))) {
			$this->PageViewSetErrorStatus(404, 'PageBuilder SampleData Not Found ...');
			return;
		} //end if
		//--

		$this->PageViewSetCfg('template-path', '@');
		$this->PageViewSetCfg('template-file', 'template-test-frontend.htm');

		$top = (string) $this->getRenderedBuilderSegmentCode('#website-menu');
		$foot = (string) $this->getRenderedBuilderSegmentCode('#website-footer');

		// IMPORTANT: trying to render a segment more than once per execution will raise fatal error !
		// thus if you have to render a sement multiple times with different extra markers can be done as this instead of the below line
		/*
		$segment = (string) $this->getRenderedBuilderSegmentCode('#segment-with-markers');
		$render1 = (string) $this->renderSegmentMarkers(
			(string) $segment,
			[
				'THE-MARKER' => '<b>This is a marker that should be HTML escaped</b>'
			]
		);
		$render2 = (string) $this->renderSegmentMarkers(
			(string) $segment,
			[
				'THE-MARKER' => 'Alternate content ...'
			]
		);
		$main = (string) $render1.$render2;
		*/
		$main = (string) $this->getRenderedBuilderSegmentCode('#segment-with-markers', [ 'THE-MARKER' => '<b>This is a marker that should be HTML escaped</b>' ]); // render segment with extra-markers to be replaced: {{=#MODULE|html#=}}

		$this->PageViewSetVars([
			'AREA.TOP' 			=> (string) $top,
			'MAIN' 				=> (string) $main,
			'AREA.FOOTER' 		=> (string) $foot,
			'META-DESCRIPTION' 	=> '',
			'META-KEYWORDS' 	=> ''
		]);

	} //END FUNCTION

} //END CLASS

// end of php code
