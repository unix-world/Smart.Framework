<?php
// [CUSTOM 404 Status Code Page]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


/**
 * Function: Custom 404 Answer (can be customized on your needs ...)
 *
 * @version		20210428
 *
 * @access 		private
 * @internal
 *
 */
function custom_http_message_404_notfound($y_message, $y_html_message='') {
	//-- This is a basic implementation (used here just for admin.php)
	if(\SmartEnvironment::isAdminArea() === true) {
		return \SmartComponents::http_error_message('*Custom* 404 Not Found', $y_message, $y_html_message);
	} //end if
	//-- This is a more advanced implementation (can be used here for both: index.php and admin.php)
	$controller = new \CustomErr404(
		'modules/mod-samples/',
		'samples.404',
		(string) \SmartFrameworkRegistry::getRequestVar('page', 'samples.404', 'string'),
		(string) ((\SmartEnvironment::isAdminArea() === true) ? ((\SmartEnvironment::isTaskArea() === true) ? 'task' : 'admin') : 'index') // if not admin or task, hardcoded to index
	);
	//--
	return $controller->outputErrorPage($y_message, $y_html_message);
	//--
} //END FUNCTION


/**
 * Class: Custom 404 Answer (used for the advanced implementation)
 *
 * @version		20210428
 *
 * @access 		private
 * @internal
 *
 */
class CustomErr404 extends \SmartModExtLib\Samples\ErrorXxx {

	protected $errcode = 404;
	protected $errtext = 'Not Found';

//	public function Initialize() {
//		//--
//		$this->PageViewSetCfg('template-path', '@'); 					// set template path to this module
//		$this->PageViewSetCfg('template-file', 'template-err-xxx.htm'); // the default template
//		//--
//	} //END FUNCTION


} //END CLASS


// end of php code
