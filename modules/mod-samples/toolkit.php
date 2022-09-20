<?php
// Controller: Samples/Toolkit
// Route: ?/page/samples.toolkit (?page=samples.toolkit)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); // INDEX, ADMIN, TASK, SHARED

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
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		$this->PageViewSetCfg('template-path', 'default'); 		// set the template path (must be inside etc/templates/)
		$this->PageViewSetCfg('template-file', 'template.htm');	// set the template file
		//--

		//--
		$fcontent = (string) SmartFileSystem::read('lib/css/toolkit/demo/sample.html');
		$arr_data = explode('<body>', $fcontent);
		$fcontent = (string) (isset($arr_data[1]) ? $arr_data[1] : '');
		$arr_data = explode('</body>', $fcontent);
		$fcontent = (string) $arr_data[0];
		$arr_data = array(); // free mem
		//--
		$this->PageViewSetVars([
			'title' => 'UX Toolkit Demo',
			'main'	=> (string) '<h1 style="font-size:3em !important; display: inline !important;">Smart.Framework / UI Toolkit Plugins - Demo</h1><h2 font-size:2.5em !important; display: inline !important;><span style="color:#778899; font-weight:normal;"><span style="cursor:help;" title="UX Toolkit is built into the Smart.Framework and provided as the default UI Toolkit. You can use it or not ... If you prefer you can use other UI Toolkits such as: Bootstrap, jQueryUI or any other you may like !"><i>UX Toolkit</i> (built-in)</span> - a very lightweight <span style="cursor:help;" title="This size is calculated when the CSS is minified ...">(~32KB)</span>, CSS only, responsive front-end Toolkit<br>for web interfaces &nbsp; <span><i><sup style="color:#DCDCDC;">inspired from Pure.css and W3.css UI Toolkits</sup></i></span></span></h2><hr>'.trim((string)$fcontent).'<hr><br><br>'
		]);
		//--
		$fcontent = ''; // free mem
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
