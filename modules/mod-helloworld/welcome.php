<?php
// Controller: Helloworld/Welcome
// Route: ?/page/helloworld.welcome (?page=helloworld.welcome)
// (c) 2006-2025 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'INDEX'); // INDEX

/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends SmartAbstractAppController {


	public function Initialize() {

		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module: `modules/mod-helloworld/templates/`
		$this->PageViewSetCfg('template-file', 'template-hello-world.htm'); // the template that this controller will use: `template-hello-world.htm`
		//--

	} //END FUNCTION


	public function Run() {

		//--
		$this->PageViewSetVars([
			'title' 	=> 'Hello, World',
			'main' 		=> (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'welcome.mtpl.htm',
				[
					'HELLO-WORLD' 	=> 'Hello World',
					'URL-WELCOME' 	=> '?page=samples.welcome',
					'URL-PROJECT' 	=> 'https://github.com/unix-world/Smart.Framework',
				]
			)
		]);
		//--

	} //END FUNCTION


} //END CLASS


// end of php code

