<?php
// [LIB - Smart.Framework / Core / Plugins / StaticLoad]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//-- r.20221225
// #PLUGINS# :: they can be loaded always (require) or as dependency injection (require_once)
//--
require_once('lib/core/plugins/lib_robot.php'); 			// smart robot
//--
require_once('lib/core/plugins/lib_mail_utils.php');		// mail utils (verify, parse)
//--
require_once('lib/core/plugins/lib_db_dba.php');			// dba db connector
require_once('lib/core/plugins/lib_db_sqlite.php');			// sqlite3 db connector
//--
require_once('lib/core/plugins/lib_pcache_dba.php'); 		// dba persistent cache
require_once('lib/core/plugins/lib_pcache_sqlite.php'); 	// sqlite3 persistent cache
//--
require_once('lib/core/plugins/lib_session.php');			// session storage
//--
require_once('lib/core/plugins/lib_captcha_svg.php'); 		// captcha svg plugin
require_once('lib/core/plugins/lib_captcha_form.php'); 		// captcha form manager
//--
require_once('lib/core/plugins/lib_viewhelpers.php'); 		// viewhelpers (html / js)
//--


// end of php code
