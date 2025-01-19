<?php
// [LIB - Smart.Framework / Webdav / AbstractController Admin CalDav:Fs]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Webdav\ControllerAdmCalDavFs
// Type: Module Library

namespace SmartModExtLib\Webdav;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * DAV / CalDAV FileSystem :: Admin Controller
 *
 * It supports only the Admin area (admin.php) so it can be used only to implement SmartAppAdminController
 * It does a direct output so it needs to set in controller: SMART_APP_MODULE_DIRECT_OUTPUT = TRUE
 *
 * @hint This abstract controller can be used to build a DAV Service / CalDAV over the Admin Middleware service
 *
 * @version		20250107
 * @package 	development:modules:Webdav
 *
 */
abstract class ControllerAdmCalDavFs extends \SmartAbstractAppController {

	// ->

	private $dav_author = '.unknown.';
	private $dav_uri = '';
	private $dav_url = '';
	private $dav_method = '';
	private $dav_request_path = '';
	private $dav_request_back_path = '';
	private $dav_vfs_path = '';
	private $dav_vfs_root = '.none.';
	private $dav_is_root_path = true;


	final public function DavFsRunServer(?string $dav_fs_root_path, bool $show_usage_quota=false, ?string $nfo_title='DAV@webICalendar', ?string $nfo_signature='Smart.Framework::CalDAV', ?string $nfo_prefix_crrpath='DAV:', ?string $nfo_lnk_welcome='', ?string $nfo_txt_welcome='CalDAV :: Home', ?string $nfo_svg_logo='modules/mod-webdav/libs/img/ical.svg') : void {

		//-- set nocache headers {{{SYNC-HTTP-NOCACHE-HEADERS}}}
		\header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1 no-cache, not use their stale copy
		\header('Pragma: no-cache'); // HTTP 1.0 no-cache
		//--

		//--
		if(\defined('\\SMART_WEBDAV_SHOW_USAGE_QUOTA')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_SHOW_USAGE_QUOTA must NOT be defined outside DavRunServer !');
			return;
		} //end if
		//--
		\define('SMART_WEBDAV_SHOW_USAGE_QUOTA', (bool)$show_usage_quota);
		//--

		//--
		if(!\defined('\\SMART_APP_MODULE_AREA') OR (\strtoupper((string)\SMART_APP_MODULE_AREA) !== 'ADMIN')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: Requires an Admin Module Area controller to run !');
			return;
		} //end if
		//--

		//--
		if(!\defined('\\SMART_APP_MODULE_DIRECT_OUTPUT') OR (\SMART_APP_MODULE_DIRECT_OUTPUT !== true)) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: Requires Direct Output set to True in the controller !');
			return;
		} //end if
		//--

		//-- check auth
		if(!\defined('\\SMART_APP_MODULE_AUTH') OR (\SMART_APP_MODULE_AUTH !== true)) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: Requires Module Auth set to True in the controller !');
			return;
		} //end if
		//--
		if(\SmartAuth::check_login() !== true) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: Authentication required but not detected !');
			return;
		} //end if
		//--
		$this->dav_author = (string) \Smart::safe_validname((string)\SmartAuth::get_auth_id(), '', true); // allow also uppercase
		//--

		//--
		$dav_fs_root_path = (string) \trim((string)$dav_fs_root_path);
		if((string)$dav_fs_root_path == '') {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: DAV FS Root Path is Empty !');
			return;
		} //end if
		//--
		$dav_fs_root_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)\SmartModExtLib\Webdav\DavServer::safePathName((string)$dav_fs_root_path));
		if(\SmartFileSysUtils::checkIfSafePath((string)$dav_fs_root_path) != '1') {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: DAV FS Root Path is Invalid: '.$dav_fs_root_path);
			return;
		} //end if
		if(\SmartFileSystem::path_exists((string)$dav_fs_root_path) !== true) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: DAV FS Root Path does Not Exists: '.$dav_fs_root_path);
			return;
		} //end if
		//--

		//-- calculate base uri
		$this->dav_request_path = (string) \ltrim((string)$this->RequestPathGet(), '/');
		$this->dav_request_path = (string) \SmartUnicode::deaccent_str($this->dav_request_path);
		$this->dav_request_path = (string) \SmartModExtLib\Webdav\DavServer::safePathName($this->dav_request_path);
		if((string)$this->dav_request_path == '') {
			$this->dav_is_root_path = true;
			$this->dav_request_back_path = '';
		} else {
			$this->dav_is_root_path = false;
			$this->dav_request_back_path = (string) \trim((string)\Smart::dir_name((string)$this->dav_request_path));
			if((string)$this->dav_request_back_path == '.') {
				$this->dav_request_back_path = '';
			} //end if
			if((string)$this->dav_request_back_path != '') {
				if(\SmartFileSysUtils::checkIfSafePath((string)$this->dav_request_back_path) != '1') {
					$this->dav_request_back_path = '';
				} //end if
			} //end if
		} //end if
		//--
		$this->dav_uri = (string) \SmartUtils::get_server_current_full_script().\SmartUtils::get_server_current_request_path();
		$this->dav_url = (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script().\SmartUtils::get_server_current_request_path();
		$this->dav_method = (string) $this->RequestMethodGet();
		$this->dav_vfs_root = (string) $dav_fs_root_path;
		$this->dav_vfs_path = (string) \SmartModExtLib\Webdav\DavServer::safePathName(\rtrim((string)$this->dav_vfs_root.$this->dav_request_path, '/'));
		//--
		if((!\SmartModExtLib\Webdav\DavServer::safeCheckPathAgainstHtFiles($this->dav_vfs_path)) OR (!\SmartModExtLib\Webdav\DavServer::safeCheckPathAgainstHtFiles($this->dav_vfs_root))) {
			\http_response_code(403); // .ht* files are denied
			echo (string) \SmartComponents::http_message_403_forbidden('The access to the requested URL is Forbidden.');
			return;
		} //end if
		//--

		//--
		if(\defined('\\SMART_WEBDAV_CALDAV_ACC_PATH')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_CALDAV_ACC_PATH must NOT be defined outside DavRunServer !');
			return;
		} //end if
		\define('SMART_WEBDAV_CALDAV_ACC_PATH', $this->dav_vfs_root.'principals/'); // proxys path
		//--
		if(\defined('\\SMART_WEBDAV_CALDAV_ICAL_PATH')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_CALDAV_ICAL_PATH must NOT be defined outside DavRunServer !');
			return;
		} //end if
		\define('SMART_WEBDAV_CALDAV_ICAL_PATH', $this->dav_vfs_root.'calendars/'.$this->dav_author.'/'); // calendars path
		//--
		if(\defined('\\SMART_WEBDAV_CALDAV_ICAL_HOME')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_CALDAV_ICAL_HOME must NOT be defined outside DavRunServer !');
			return;
		} //end if
		\define('SMART_WEBDAV_CALDAV_ICAL_HOME', (string)\SmartUtils::get_server_current_full_script().'/page/'.$this->ControllerGetParam('url-page').'/~/calendars/'.$this->dav_author.'/');
		//--
		if(\defined('\\SMART_WEBDAV_CALDAV_ICAL_PPS')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_CALDAV_ICAL_PPS must NOT be defined outside DavRunServer !');
			return;
		} //end if
		\define('SMART_WEBDAV_CALDAV_ICAL_PPS', (string)\SmartUtils::get_server_current_full_script().'/page/'.$this->ControllerGetParam('url-page').'/~/principals/');
		//--
		if(\defined('\\SMART_WEBDAV_CALDAV_ICAL_ACC')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ CalDAV: The constant SMART_WEBDAV_CALDAV_ICAL_ACC must NOT be defined outside DavRunServer !');
			return;
		} //end if
		\define('SMART_WEBDAV_CALDAV_ICAL_ACC', (string)\SmartUtils::get_server_current_full_script().'/page/'.$this->ControllerGetParam('url-page').'/~/principals/'.$this->dav_author.'/');
		//--

		//--
		// \Smart::log_notice($this->dav_method.': '.$this->dav_request_path.' @ '.$this->dav_vfs_path);
		//--
		switch((string)$this->dav_method) {

			case 'OPTIONS':
				\SmartModExtLib\Webdav\DavFsCalDav::methodOptions();
				break;

			case 'HEAD':
				\SmartModExtLib\Webdav\DavFsCalDav::methodHead((string)$this->dav_vfs_path);
				break;

			case 'PROPFIND':
				\SmartModExtLib\Webdav\DavFsCalDav::methodPropfind(
					(string) $this->dav_uri,
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(bool)   $this->dav_is_root_path,
					(string) $this->dav_vfs_root
				);
				break;

			case 'REPORT':
				\SmartModExtLib\Webdav\DavFsCalDav::methodReport(
					(string) $this->dav_uri,
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(bool)   $this->dav_is_root_path,
					(string) $this->dav_vfs_root
				);
				break;

			case 'PUT':
				\SmartModExtLib\Webdav\DavFsCalDav::methodPut((string)$this->dav_vfs_path);
				break;

			case 'DELETE':
				\SmartModExtLib\Webdav\DavFsCalDav::methodDelete((string)$this->dav_vfs_path);
				break;

			case 'GET':
				\SmartModExtLib\Webdav\DavFsCalDav::methodGet(
					(string) $this->dav_method,
					(string) $this->dav_author,
					(string) $this->dav_url,
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(bool)   $this->dav_is_root_path,
					(string) $this->dav_vfs_root,
					(string) $this->dav_request_back_path,
					(string) $nfo_title,
					(string) $nfo_signature,
					(string) $nfo_prefix_crrpath,
					(string) $nfo_lnk_welcome,
					(string) $nfo_txt_welcome,
					(string) $nfo_svg_logo
				);
				break;

			default:
				\http_response_code(501); // not implemented
				// \Smart::log_notice('Method NOT Implemented: '.(string)$this->dav_method);

		} //end switch

	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
