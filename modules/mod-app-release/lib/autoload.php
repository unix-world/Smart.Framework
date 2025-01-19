<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// [LIB - Smart.Framework / Modules / AppRelease / AutoLoad]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

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
function autoload__SmartFrameworkLibModulesAppRelease($classname) { // v.20250107
	//--
	switch((string)$classname) {
		//--
		case 'AppCodeUtils':
		case 'AppCodeOptimizer':
		case 'ShrinkCode':
		case 'ShrinkJsCode':
		case 'ShrinkCssCode':
		case 'StripCode':
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
spl_autoload_register('autoload__SmartFrameworkLibModulesAppRelease', true, false); 	// throw / append
//--


// end of php code
