<?php
// Controller: Samples/BenchMarkWithSession
// Route: ?/page/samples.benchmark-with-session (?page=samples.benchmark-with-session)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED'); 						// ADMIN, TASK, SHARED
define('SMART_APP_MODULE_AUTH', true); 							// if set to TRUE requires auth always
//define('SMART_APP_MODULE_REALM_AUTH', 'SMART-ADMINS-AREA'); 	// if set will check the login realm

/**
 * Admin Controller
 *
 * @ignore
 *
 */
class SmartAppAdminController extends SmartAbstractAppController {

	public function Initialize() {
		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
		$this->PageViewSetCfg('template-file', 'template-simple.htm');
		//--
		$semaphores = [];
		$semaphores[] = 'skip:unveil-js';
		$this->PageViewSetVar('semaphore', (string) Smart::array_to_list($semaphores));
		//--
		return true;
		//--
	} //END FUNCTION

	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//-- Session will be started also by set
		$sess_key = 'Samples_Benchmark_WithSession (just-for-admin)';
		$sess_test = (string) SmartSession::get($sess_key);
		if((string)$sess_test == '') {
			SmartSession::set($sess_key, date('Y-m-d H:i:s'));
			$sess_test = (string) SmartSession::get($sess_key);
		} //end if
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> 'Benchmark with Session Test URL',
			'head-meta' => '<meta name="author" content="Smart.Framework by Unix-World, https://github.com/unix-world/Smart.Framework">',
			'main' 		=> SmartMarkersTemplating::render_file_template(
				$this->ControllerGetParam('module-path').'views/benchmark.mtpl.htm',
				[
					'BENCHMARK-TITLE' => '[ Benchmark Test URL with PHP Session ]<br>use this URL to run a benchmark of this PHP framework with the PHP Session started ...<br>(Session Value = \''.Smart::escape_html($sess_test).'\')'
				]
			)
		]);
		//--

	} //END FUNCTION

} //END CLASS


/**
 * Task Controller
 *
 * @ignore
 *
 */
class SmartAppTaskController extends SmartAppAdminController {} //END CLASS


// end of php code
