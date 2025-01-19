<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT DIRECT EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * PageBuilder Plugin
 *
 * @ignore
 *
 */
final class PageBuilderFrontendPluginPageBuilderTest3 extends \SmartModExtLib\PageBuilder\AbstractFrontendPlugin {

	// r.20250107

	public function Run() {
		//--
		$local_cfg = (array) $this->getPluginConfig();
		$txt_cfg = array();
		foreach($local_cfg as $key => $val) {
			$txt_cfg[] = '['.$key.'='.$val.']';
		} //end foreach
		//--
		$this->PageViewSetVar('content', '<div>this is Plugin3 Test :: '.Smart::escape_html(implode(' ; ', (array)$txt_cfg)).'</div>');
		//--
	} //END FUNCTION


	public function ShutDown() {
		// *** optional*** can be redefined in a plugin
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
