<?php
// AppCodePack - a PHP, JS and CSS Optimizer / NetArchive Pack Upgrade Script
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APPCODEPACK_PROCESS_EXTRA_RUN')) { // this must be defined in the first line of the application
	throw new Exception('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//#####
// Sample AppCodePack Extra Script, v.20210222.1157
// CUSTOMIZE IT AS NEEDED and rename it to: appcodepack-extra-run.php ; It also need a corresponding: appcodepack-extra-run.inc.htm
//#####

//--
switch((string)APPCODEPACK_PROCESS_EXTRA_RUN) {
	case 'extra-test-ok':
		// emulate ok
		echo 'This is a test with OK result ...'; // the output is optional
		break;
	case 'extra-test-err':
		// emulate error
		echo 'This is an ERROR test ...'; // the output is optional
		throw new Exception('Failed ...'); // emulate error (throw is required for the error case)
		break;
	case 'extra-test-external':
		// run external script
		define('APPCODEPACK_PROCESS_EXTRA_RUN_EXTERNAL', 'appcodepack-extra-run-external-task1.php');
		break;
	default:
		throw new Exception('Invalid Extra Task: '.APPCODEPACK_PROCESS_EXTRA_RUN);
} //end switch
//--

// end of php code
