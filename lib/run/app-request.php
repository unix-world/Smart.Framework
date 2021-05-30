<?php
// [APP - Request Handler / Smart.Framework]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// @ignore		THIS FILE IS FOR INTERNAL USE ONLY BY SMART-FRAMEWORK.RUNTIME !!!

//======================================================
// Smart-Framework - App Request Handler :: r.20210530
// DEPENDS: SmartFramework, SmartFrameworkRuntime
//======================================================
// This file can be customized per App ...
// DO NOT MODIFY ! IT IS CUSTOMIZED FOR: Smart.Framework
//======================================================

//##### WARNING: #####
// Changing the code below is on your own risk and may lead to severe disrupts in the execution of this software !
// This code part registers the REQUEST variables in the right order (according with the security standards: G=Get/P=Post and C=Cookie from GPCS ; S=Server will not be processed here and must be used from PHP super-globals: $_SERVER)
//####################


//-- EXTRACT, FILTER AND REGISTER INPUT VARIABLES: GET, POST, COOKIE and SERVER[PATH_INFO]
SmartFrameworkRuntime::Parse_Semantic_URL(); 																	// extract the Special PathInfo handled by Smart.Framework using $_SERVER['PATH_INFO'] (the path after the first occurence of `/~` if any, and register it to registry) - ONLY if PathInfo is enabled ; Handle also Smart.Framework Semantic URLs
SmartFrameworkRuntime::Extract_Filtered_Request_Get_Post_Vars((array)(is_array($_GET)  ? $_GET  : []), 'GET'); 	// extract and filter $_GET
SmartFrameworkRuntime::Extract_Filtered_Request_Get_Post_Vars((array)(is_array($_POST) ? $_POST : []), 'POST'); // extract and filter $_POST
SmartFrameworkRuntime::Extract_Filtered_Cookie_Vars((array)(is_array($_COOKIE) ? $_COOKIE : [])); 				// extract and filter $_COOKIE
SmartFrameworkRuntime::Lock_Request_Processing(); 																// lock request registry and prevent re-process Request or Cookie variables after they were processed 1st time (this is mandatory from security point of view)
//--
// $_SERVER will not be processed, use $_SERVER['some-key'] for reading server variables, except $_SERVER['PATH_INFO']
//--

// end of php code
