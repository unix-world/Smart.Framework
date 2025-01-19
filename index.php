<?php
// Smart.Framework / Runtime / Index
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//##### WARNING: #####
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
//####################

//== v.20250107
//--
ini_set('display_errors', '1'); 											// temporary enable this to display bootstrap errors if any ; will be managed later by Smart Error Handler
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); 						// on bootstrap show real-time errors (sync with Smart Error Handler)
//--
if((is_file('.sf-unpack')) OR (is_file('maintenance.html'))) { // {{{SYNC-HTTP-NOCACHE-HEADERS}}}
	@http_response_code(503); // 503 maintenance mode
	@header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1 no-cache
	@header('Pragma: no-cache'); // HTTP 1.0 no-cache
	if(!@readfile('maintenance.html', false)) {
		echo('<h1>503 Service under Maintenance ...</h1>');
	} //end if
	die('<!-- Smart.Framework [I] 503 Maintenance -->');
} //end if
//--
//==
ob_start();
//--
const SMART_FRAMEWORK_LIB_PATH =  			'lib/framework/'; 				// smart framework lib path
const SMART_FRAMEWORK_RUNTIME_MODE =  		'web.app'; 						// runtime mode: 'web.app'
const SMART_STANDALONE_APP =  				false; 							// must be set to false, except standalone scripts !
const SMART_FRAMEWORK_ADMIN_AREA =  		false; 							// run app in public/index mode
//--
define('SMART_FRAMEWORK_RUNTIME_READY', 	microtime(true)); 				// semaphore, runtime can execute scripts
//--
require('etc/init.php'); 													// the PHP.INI local settings (they must be called first !!!)
//--
require(SMART_FRAMEWORK_LIB_PATH.'smart-error-handler.php'); 				// Smart Framework Error Handler
require('lib/smart-runtime.php'); 											// Smart Runtime
require('etc/config-index.php'); 											// Index Config
require('lib/run/abstract-controller.php'); 								// Service Controller Definition
require('lib/run/middleware.php'); 											// Service Handler Definition
require('lib/run/middleware-index.php'); 									// Index Service Handler
//--
//==
//--
if((string)SMART_FRAMEWORK_RELEASE_MIDDLEWARE != '[I]@'.SMART_FRAMEWORK_RELEASE_TAGVERSION) {
	@http_response_code(500);
	die('Smart.Framework // App [I] Service: Middleware service validation Failed ... Invalid Version !');
} //end if
//--
if((string)get_parent_class('SmartAppIndexMiddleware') != 'SmartAbstractAppMiddleware') {
	@http_response_code(500);
	die('Smart.Framework // App [I] Service: the Class SmartAppIndexMiddleware must be extended from the Class SmartAbstractAppMiddleware ...');
} //end if
//--
$output = ob_get_contents();
ob_end_clean();
if((string)$output != '') {
	Smart::log_warning('The middleware service [I] detected an illegal output in initialize'."\n".'The result of this output is: '.$output);
} //end if
$output = '';
//--
//==
//--
$run = SmartAppIndexMiddleware::Run(); // Handle the Index service
ob_start();
if(SmartEnvironment::ifDebug()) {
	if($run !== false) {
		SmartAppIndexMiddleware::DebugInfoSet('idx', (bool)$run);
	} //end if
} //end if
$output = ob_get_contents();
ob_end_clean();
if((string)$output != '') {
	Smart::log_warning('The middleware service [I] detected an illegal output in shutdown'."\n".'The result of this output is: '.$output);
} //end if
$output = '';
//--
if((string)setlocale(LC_ALL, 0) != 'C') { // {{{SYNC-LOCALES-CHECK}}}
	trigger_error(
		'#SMART-FRAMEWORK-LOCALES-NOTICE#'."\n".
		'Invalid PHP Locales (other than C) detected: ['.setlocale(LC_ALL, 0).'].'."\n".
		'The locale information is maintained per process, not per thread'."\n".
		'Thus if other external PHP scripts changed locales while Smart.Framework was running in the same (server) process this will generate unpredictable results.'."\n".
		'Solution: You should assure to run Smart.Framework in an isolated (server) process context to avoid this issue !'."\n".
		'If you changed the locales using setlocale() in your PHP scripts inside Smart.Framework you should stop doing this as other locales than C are not supported ...',
		E_USER_NOTICE
	);
} //end if
//--
//==
//#END
//==

// end of php code
