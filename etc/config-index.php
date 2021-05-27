<?php
// [@[#[!NO-STRIP!]#]@]
// [Smart.Framework / CFG - SETTINGS / INDEX]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//--------------------------------------- Templates and Home Page
$configs['app']['index-domain'] 					= 'localhost.local'; 		// index domain as yourdomain.ext
$configs['app']['index-home'] 						= 'samples.welcome';		// index home page action
$configs['app']['index-default-module'] 			= 'samples'; 				// index default module ; check also SMART_FRAMEWORK_SEMANTIC_URL_SKIP_MODULE
$configs['app']['index-template-path'] 				= 'default';				// default index template folder from etc/templates/
$configs['app']['index-template-file'] 				= 'template.htm';			// default index template file
//---------------------------------------

//--------------------------------------- OTHER SPECIAL SETTINGS :: DO NOT MODIFY IF YOU DON'T KNOW WHAT YOU ARE DOING, really ...
//define('SMART_FRAMEWORK_CUSTOM_ERR_PAGES', 'modules/mod-samples/error-pages/'); // `` or custom path to error pages: 400.php, 401.php, 403.php, 404.php, 429.php, 500.php, 502.php, 503.php, 504.php ; if this is enabled will serve customized responses for 4xx/5xx HTTP Status Codes ; you can customize any of 4xx/5xx or all ...
//---------------------------------------

// end of php code
