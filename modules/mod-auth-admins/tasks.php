<?php
// Controller: AuthAdmins/Tasks
// Route: task.php?/page/auth-admins.tasks (task?page=auth-admins.tasks)
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'TASK'); // TASK

/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppTaskController extends SmartAbstractAppController {

	// v.20231020


	public function Run() {

		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module
		$this->PageViewSetCfg('template-file', 'template.htm'); // the default template
		//--

		//--
		$tasks = (array) \SmartModExtLib\AuthAdmins\AuthNameSpaces::GetNameSpaces();
		//--

		//--
		$this->PageViewSetVars([
			'title' 	=> 'Task NameSpaces',
			'main' 		=> SmartMarkersTemplating::render_file_template(
				$this->ControllerGetParam('module-view-path').'tasks.mtpl.htm',
				[
					'DATE-TIME' 		=> (string) date('Y-m-d H:i:s O'),
					'HTML-POWERED-INFO' => (string) SmartComponents::app_powered_info('yes'),
					'TASKS' 			=> (array)  $tasks,
				]
			)
		]);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
