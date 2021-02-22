<?php
// AppCodePack - a PHP, JS and CSS Optimizer / NetArchive Pack Upgrade Script
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APPCODEPACK_APP_ID')) { // this must be defined in the first line of the application
	throw new Exception('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//#####
// Sample AppCodePack Upgrade Script, v.20210222.1157
// CUSTOMIZE IT AS NEEDED and rename it to: appcode-upgrade.php
//#####

//--
// THIS IS A SAMPLE UPGRADE SCRIPT THAT WILL BE RUN AFTER THE PACKAGE DEPLOYMENT
// AS THIS SCRIPT WILL RUN INSIDE THE APPCODEUNPACK (UNDER A DIFFERENT DIRECTORY TAKE IN CONSIDERATION THIS ASPECT ...)
// THIS SCRIPT IS VERY LIMITED AND MUST BE USED FOR SUCCESSFUL AFTER-DEPLOYMENT TASKS LIKE:
// 		* clear Redis cache
// 		* upgrade the SQL databases
// 		* do a hit on a post-upgrade task
//--
// IMPORTANT:
// 		* Below this line, this script should not die() but only throw catcheable exceptions of the \Exception object because will terminate the parent script (appcodeunpack.php) prematurely ...
// 		* This script should not output anything
// 		* If script result is OK, no exception will be throw, thus considered as SUCCESS
//--

//===== Code below is only sample, and can be removed if not needed =====

//-- 1st, clear redis cache DB#17 after deployment
AppCodePackUpgrade::RunCmd('/usr/local/bin/redis-cli -n 17 FLUSHDB'); // throws if unsuccessful
//--

//-- if success, remove maintenance.html (need to exit maintenance mode before run the next command)
AppCodePackUpgrade::RemoveMaintenanceFile(); // throws if unsuccessful
//--

//-- if success, run a post deployment task (by example, check if website works ...)
$arr = (array) AppCodePackUpgrade::RunCmd('/usr/local/bin/curl -s -o /dev/null -w '.escapeshellarg('%{http_code}').' --get --connect-timeout 30 --max-time 150 --insecure --url '.escapeshellarg('https://127.0.0.1'));
//--
if((string)trim((string)$arr['stdout']) != '200') { // expect HTTP Status 200
	throw new Exception('CURL GET - HTTP Status FAILED # Expect 200 but Result is: '.(string)trim((string)$arr['stdout']));
} //end if
//--


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class AppCodePackUpgrade extends AppCodePackAbstractUpgrade {

	// ::

	// customize this as you need

} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
