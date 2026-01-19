<?php
// [LIB - Smart.Framework / Core / Plugins / AutoLoad]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//-- r.20260118
// #PLUGINS# :: they are loaded via Dependency Injection
//--
/**
 * Function AutoLoad Plugins
 *
 * @access 		private
 * @internal
 *
 */
function autoload__SmartFrameworkCorePlugins($classname) {
	//--
	if((string)substr((string)$classname, 0, 5) !== 'Smart') { // must start with Smart
		return;
	} //end if
	//--
	switch((string)$classname) {
		//-- robot
		case 'SmartRobot':
			require_once('lib/core/plugins/lib_robot.php'); 			// smart robot
			break;
		//-- mail
		case 'SmartMailerMimeParser':
		case 'SmartMailerUtils':
			require_once('lib/core/plugins/lib_mail_utils.php');		// mail utils (send, verify, parse)
			break;
		//-- db
		case 'SmartDbaUtilDb':
		case 'SmartDbaDb':
			require_once('lib/core/plugins/lib_db_dba.php');			// dba db connector
			break;
		//-- persistent cache
		case 'SmartDbaPersistentCache':
			require_once('lib/core/plugins/lib_pcache_dba.php'); 		// dba persistent cache
			break;
		case 'SmartSQlitePersistentCache':
			require_once('lib/core/plugins/lib_pcache_sqlite.php'); 	// sqlite3 persistent cache
			break;
		//-- session handler
		case 'SmartAbstractCustomSession':
		case 'SmartSession':
			require_once('lib/core/plugins/lib_session.php');			// session handler
			break;
		//-- captcha
		case 'SmartSVGCaptcha':
			require_once('lib/core/plugins/lib_captcha_svg.php'); 		// captcha svg plugin
			break;
		case 'SmartCaptcha':
			require_once('lib/core/plugins/lib_captcha_form.php'); 		// captcha form manager
			break;
		//-- viewhelpers
		case 'SmartViewHtmlHelpers':
			require_once('lib/core/plugins/lib_viewhelpers.php'); 		// viewhelpers (html / js)
			break;
		//--
		default:
			return; // other classes are not managed here ...
		//--
	} //end switch
	//--
} //END FUNCTION
//--
spl_autoload_register('autoload__SmartFrameworkCorePlugins', true, true); 	// throw / prepend
//--


// end of php code
