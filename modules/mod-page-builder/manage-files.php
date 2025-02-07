<?php
// Controller: PageBuilder/ManageFiles
// Route: ?/page/page-builder.manage-files
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN');
define('SMART_APP_MODULE_AUTH', true);
define('SMART_APP_MODULE_DIRECT_OUTPUT', true); // do direct output

//--
if(!SmartAppInfo::TestIfModuleExists('mod-webdav')) {
	SmartFrameworkRuntime::Raise500Error('ERROR: PageBuilder.Files WebDAV requires the WebDAV Module ...');
	die('');
} //end if
//--

/**
 * PageBuilder Manage Files
 *
 * @ignore
 *
 */
class SmartAppAdminController extends \SmartModExtLib\Webdav\ControllerAdmDavFs {

	// v.20250124

	public function Run() {

		//--
		if(SmartAuth::is_authenticated() !== true) {
			SmartFrameworkRuntime::Raise403Error('ERROR: PageBuilder.Files requires Authentication ...');
			return;
		} //end if
		//--
		if(
			(\SmartAuth::test_login_privilege('admin') !== true)
			AND
			(\SmartAuth::test_login_privilege('pagebuilder:files') !== true)
		) {
			SmartFrameworkRuntime::Raise403Error('ERROR: PageBuilder.Files requires Privileged User ...');
			return;
		} //end if
		//--
		if(SmartFrameworkRuntime::PathInfo_Enabled() !== true) {
			SmartFrameworkRuntime::Raise500Error('ERROR: PageBuilder.Files WebDAV requires PathInfo to be enabled into init.php for Admin Area ...');
			return;
		} //end if
		//--
		$url_base = (string) SmartUtils::get_server_current_url().SmartUtils::get_server_current_script().'/page/page-builder.manage-files/~';
		//--
		if(strpos((string)SmartUtils::get_server_current_request_uri(), '/~') === false) {
			SmartFrameworkRuntime::Raise400Error('ERROR: PageBuilder.Files WebDAV requires to be accessed in a special mode: `'.$url_base.'`');
			return;
		} //end if
		//--
		$dav_root_folder = (string) \SmartModExtLib\PageBuilder\Utils::getFilesFolderRoot();
		if(!SmartFileSystem::path_exists((string)$dav_root_folder)) {
			SmartFileSystem::dir_create((string)$dav_root_folder, true);
		} //end if
		if(SmartFileSystem::is_type_dir((string)$dav_root_folder)) {
			if(!SmartFileSystem::path_exists((string)$dav_root_folder.'index.html')) {
				SmartFileSystem::write((string)$dav_root_folder.'index.html');
			} //end if
		} //end if
		//--
		define('SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE', -1); // !!! etags on PROPFIND / HEAD :: set = -2 to disable etags ; set to -1 to show etags for all files ; if >= 0, will show the etag only if the file size is <= with this limit (etag on PROPFIND / HEAD is not mandatory for WebDAV and may impact performance if there are a large number of files in a directory or big size files ...) ; etags will always show on PUT method
		//--
		if((string)ltrim((string)$this->RequestPathGet(), '/') != '') {
			$txt_lnk = 'Return to PageBuilder.Files :: Home';
			$url_lnk = (string) $url_base;
			$img_lnk = 'modules/mod-page-builder/libs/views/manager/img/webdav-files.svg';
		} else {
			$txt_lnk = 'PageBuilder.Files :: Home';
			$url_lnk = (string) ''; // SmartUtils::get_server_current_url().SmartUtils::get_server_current_script().'?/page/page-builder.manage';
			$img_lnk = 'modules/mod-page-builder/libs/views/manager/img/webdav-objects.svg';
		} //end if else
		//--
		$this->DavFsRunServer(
			(string) $dav_root_folder,
			false, // show quota
			'webDAV@PageBuilderFiles',
			'PageBuilder.WebDAV',
			'[PATH]:'.$dav_root_folder,
			(string) $url_lnk,
			(string) $txt_lnk,
			(string) $img_lnk
		);
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
