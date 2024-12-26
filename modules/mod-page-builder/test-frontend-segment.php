<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: PageBuilder/TestFrontendSegment
// Route: ?page=page-builder.test-frontend-segment
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
		if((!$this->checkIfPageOrSegmentExist('#website-menu')) OR (!$this->checkIfPageOrSegmentExist('#seg-plug')) OR (!$this->checkIfPageOrSegmentExist('#website-footer'))) {
			$this->PageViewSetErrorStatus(404, 'PageBuilder SampleData Not Found ...');
			return;
		} //end if
		//--

		$this->PageViewSetCfg('template-path', '@');
		$this->PageViewSetCfg('template-file', 'template-test-frontend.htm');

		// IMPORTANT: trying to render a segment more than once per execution will raise fatal error !
		$top  = (string) $this->getRenderedBuilderSegmentCode('#website-menu');
		$main = (string) $this->getRenderedBuilderSegmentCode('#seg-plug');
		$foot = (string) $this->getRenderedBuilderSegmentCode('#website-footer');

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
