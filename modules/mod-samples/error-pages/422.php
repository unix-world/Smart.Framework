<?php
// [CUSTOM 422 Status Code Page]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

/**
 * Function: Custom 422 Answer (can be customized on your needs ...)
 *
 * @access 		private
 * @internal
 *
 */
function custom_http_message_422_unprocessablecontent($y_message, $y_html_message='') {
	//--
	return SmartComponents::http_error_message('*Custom* 422 Unprocessable Content', $y_message, $y_html_message); // 422 Unprocessable Entity
	//--
} //END FUNCTION

// end of php code
