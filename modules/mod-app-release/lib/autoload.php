<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// [LIB - Smart.Framework / Modules / AppRelease / AutoLoad]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//--
// #Modules.AppRelease# :: they are loaded via Dependency Injection
//--
/**
 * Function AutoLoad Modules.AppRelease
 *
 * @access 		private
 * @internal
 *
 */
function autoload__SmartFrameworkModulesAppRelease($classname) { // v.20210511
	//--
	switch((string)$classname) {
		//--
		case 'AppCodeUtils':
		case 'AppCodeOptimizer':
		case 'StripCode':
		case 'CssLint':
		case 'CssOptimizer':
		case 'JsOptimizer':
		case 'PhpOptimizer':
		case 'AppNetPackager':
		case 'AppNetUnPackager':
			require_once('modules/mod-app-release/lib/'.(string)$classname.'.php');
			break;
		//--
		default:
			return; // other classes are not managed here ...
		//--
	} //end switch
	//--
} //END FUNCTION
//--
spl_autoload_register('autoload__SmartFrameworkModulesAppRelease', true, false); 	// throw / append
//--


// end of php code
