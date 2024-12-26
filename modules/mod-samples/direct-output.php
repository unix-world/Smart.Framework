<?php
// Controller: Samples/DirectOutput
// Route: ?/page/samples.direct-output (?page=direct-output)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // INDEX, ADMIN, TASK, SHARED
define('SMART_APP_MODULE_DIRECT_OUTPUT', true);

// NOTICE: For this type of controllers you must echo everything and build manually the output from the scratch ...
// When the SMART_APP_MODULE_DIRECT_OUTPUT is set to true:
//		* the Middleware will end bypass the output directly to the controller
//		* all the page settings will be ignored, no headers, no templates no other features will be available
//		* the use for this type of controllers is when you need by example use passthru() from PHP or other functions that need gradually output !
//		* calling InstantFlush() after each portion of output will do a gradually output (see the running example !!)

/**
 * Index Controller
 *
 * @ignore
 *
 */
class SmartAppIndexController extends SmartAbstractAppController {

	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			SmartFrameworkRuntime::Raise503Error('ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		SmartFrameworkRuntime::outputHttpHeadersCacheControl();
		$this->InstantFlush();
		//--

		//--
		for($i=0; $i<3; $i++) {
			echo 'Line #'.($i+1).'<br>';
			$this->InstantFlush();
			sleep(1);
		} //end for
		//--

		//--
		echo '<br><span id="qunit-test-result">Test OK: Smart.Framework Direct Output.</span>';
		//--

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
