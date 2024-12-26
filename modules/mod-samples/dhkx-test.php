<?php
// Controller: Samples/DhkxTest
// Route: ?/page/samples.dhkx-test (?page=samples.dhkx-test)
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX

/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends SmartAbstractAppController {

	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> 'DHKX Test',
			'main' 		=> (string) \SmartModExtLib\Samples\DhkxTest::renderViewJsPhpExchange().
			'<a class="ux-button" href="?/page/samples.dhkx-test">Reload</a>'.
			'<a class="ux-button ux-button-dark" href="modules/mod-samples/demo/dhkx.html" data-smart="open.modal">DHKX BigInt Demo</a>',
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
