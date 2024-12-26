<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: Webdav/TestCardDAV (CardDAV:FileSystem)
// Route: admin.php/page/webdav.test-carddav/~
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN'); // admin area only
define('SMART_APP_MODULE_AUTH', true); // requires auth always
define('SMART_APP_MODULE_DIRECT_OUTPUT', true); // do direct output

//--
if(!SmartAppInfo::TestIfModuleExists('mod-webdav')) {
	SmartFrameworkRuntime::Raise500Error('ERROR: WebDAV Module is missing ...');
	die('');
} //end if
//--

/**
 * Admin Controller (direct output)
 * @ignore
 */
class SmartAppAdminController extends \SmartModExtLib\Webdav\ControllerAdmCardDavFs {

	// v.20240116

	public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			http_response_code(503);
			echo SmartComponents::http_message_503_serviceunavailable('ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		if(SmartAuth::check_login() !== true) {
			http_response_code(403);
			echo SmartComponents::http_message_403_forbidden('ERROR: CardDAV Invalid Auth ...');
			return;
		} //end if
		//--
		if(!defined('SMART_FRAMEWORK_TESTUNIT_ALLOW_WEBDAV_TESTS') OR (SMART_FRAMEWORK_TESTUNIT_ALLOW_WEBDAV_TESTS !== true)) {
			http_response_code(503);
			echo SmartComponents::http_message_503_serviceunavailable('ERROR: CardDAV Test mode is disabled ...');
			return;
		} //end if
		//--

		//--
		if(SmartFrameworkRuntime::PathInfo_Enabled() !== true) {
			http_response_code(500);
			echo SmartComponents::http_message_500_internalerror('ERROR: CardDAV requires PathInfo to be enabled into init.php for Admin Area ...');
			return;
		} //end if
		//--
		if(strpos((string)SmartUtils::get_server_current_request_uri(), '/~') === false) {
			http_response_code(400);
			echo SmartComponents::http_message_400_badrequest('ERROR: CardDAV requires to be accessed in a special mode: `/~`');
			return;
		} //end if
		//--
		// !!! To SECURE the below folder for PRIVATE access, create a .htaccess in wpub/test-webdav to deny all access to this folder and sub-folders !!!
		$this->DavFsRunServer(
			'wpub/test-webdav/addrbook',
			true // you may disable this on large webdav file systems to avoid huge calculations
		);
		//--

		//-- HINTS:
		// # CardDAV Folder Structure [addrbook/]:
		// addressbooks/
		// addressbooks/{user}/
		// addressbooks/{user}/DefaultAddressBook/
		// principals/
		// principals/{user}/
		// # ThunderBird Sogo (connector, extension) Remote AddressBook URL: {http(s)://prefix-and-path}/admin.php/page/webdav.test-carddav/~/addressbooks/admin/DefaultAddressBook/
		// # MacOS Contacts / iOS Contacts URL: {http(s)://prefix-and-path}/admin.php/page/webdav.test-carddav/~/principals/admin/
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
