<?php
// Controller: Samples/TaskSample2
// Route: task.php?/page/samples.task-sample-2 (task.php?page=samples.task-sample-2)
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
	SmartFrameworkRuntime::Raise500Error('Mod AuthAdmins is missing !');
	die('Mod AuthAdmins is missing !');
} //end if

define('SMART_APP_MODULE_AREA', 'TASK');

/**
 * Task Controller
 *
 * @ignore
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AuthAdmins\AbstractTaskController {

	protected $title = 'Sample Task #2';

	protected $name_prefix = 'Sample';
	protected $name_suffix = 'Task';

	protected $msg = '';
	protected $err = '';
	protected $notice = '';
	protected $notehtml = '';

	protected function InitTask() {
		//--
		if(!$this->TestDirectOutput()) {
			return 'ERROR: Direct Output is not enabled ...';
		} //end if
		//--
		$this->name_prefix = 'Sample';
		$this->name_suffix = 'Task #2';
		//--
		$this->app_tpl = '';
		$this->app_main_url = '';
		//--
		return null;
		//--
	} //END FUNCTION


	public function Run() {

		//--
		$this->goback = 'task.php?page=app-release.app-manage&appid=smart-framework.test';
		//--
		$this->sficon = 'cogs';
		$this->notice = 'Task #2 Completed (this is just a sample ...)';
		//--

	} //END FUNCTION


} //END CLASS


// end of php code
