<?php
// [LIB - Smart.Framework / Webdav / AbstractController Admin Dav:Fs]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Webdav\ControllerAdmDavFs
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
 * DAV / WebDAV FileSystem :: Admin Controller
 *
 * It supports only the Admin area (admin.php) so it can be used only to implement SmartAppAdminController
 * It does a direct output so it needs to set in controller: SMART_APP_MODULE_DIRECT_OUTPUT = TRUE
 *
 * @hint This abstract controller can be used to build a DAV Service / WebDAV over the Admin Middleware service
 *
 * @version		20250124
 * @package 	development:modules:Webdav
 *
 */
abstract class ControllerAdmDavFs extends \SmartAbstractAppController {

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


	final public function DavFsRunServer(?string $dav_fs_root_path, bool $show_usage_quota=false, ?string $nfo_title='DAV@webCloudFileSystem', ?string $nfo_signature='Smart.Framework::WebDAV', ?string $nfo_prefix_crrpath='DAV:', ?string $nfo_lnk_welcome='', ?string $nfo_txt_welcome='WebDAV :: Home', ?string $nfo_svg_logo='modules/mod-webdav/libs/img/files.svg') : void {

		//-- set nocache headers {{{SYNC-HTTP-NOCACHE-HEADERS}}}
		\header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1 no-cache, not use their stale copy
		\header('Pragma: no-cache'); // HTTP 1.0 no-cache
		//--

		//--
		if(!\defined('\\SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE')) { // {{{SYNC-DEFAULT-PROPFIND-ETAG-MAX-FSIZE}}}
			\define('SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE', -2); // etags on PROPFIND :: set = -2 to disable etags ; set to -1 to show etags for all files ; if >= 0, if the file size is >= with this limit will only calculate the etag
		} //end if
		//--

		//--
		if(\defined('\\SMART_WEBDAV_SHOW_USAGE_QUOTA')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: The constant SMART_WEBDAV_SHOW_USAGE_QUOTA must NOT be defined outside DavRunServer !');
			return;
		} //end if
		//--
		\define('SMART_WEBDAV_SHOW_USAGE_QUOTA', (bool)$show_usage_quota);
		//--

		//--
		if(!\defined('\\SMART_APP_MODULE_AREA') OR (\strtoupper((string)\SMART_APP_MODULE_AREA) !== 'ADMIN')) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: Requires an Admin Module Area controller to run !');
			return;
		} //end if
		//--

		//--
		if(!\defined('\\SMART_APP_MODULE_DIRECT_OUTPUT') OR (\SMART_APP_MODULE_DIRECT_OUTPUT !== true)) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: Requires Direct Output set to True in the controller !');
			return;
		} //end if
		//--

		//-- check auth
		if(!\defined('\\SMART_APP_MODULE_AUTH') OR (\SMART_APP_MODULE_AUTH !== true)) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: Requires Module Auth set to True in the controller !');
			return;
		} //end if
		//--
		if(\SmartAuth::is_authenticated() !== true) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: Authentication required but not detected !');
			return;
		} //end if
		//--
		$this->dav_author = (string) \Smart::safe_validname((string)\SmartAuth::get_auth_id(), '', true); // allow also uppercase
		//--

		//--
		$dav_fs_root_path = (string) \trim((string)$dav_fs_root_path);
		if((string)$dav_fs_root_path == '') {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: DAV FS Root Path is Empty !');
			return;
		} //end if
		//--
		$dav_fs_root_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)\SmartModExtLib\Webdav\DavServer::safePathName((string)$dav_fs_root_path));
		if(\SmartFileSysUtils::checkIfSafePath((string)$dav_fs_root_path) != '1') {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: DAV FS Root Path is Invalid: '.$dav_fs_root_path);
			return;
		} //end if
		if(\SmartFileSystem::path_exists((string)$dav_fs_root_path) !== true) {
			\http_response_code(500);
			echo \SmartComponents::http_message_500_internalerror('FATAL ERROR @ WebDAV: DAV FS Root Path does Not Exists: '.$dav_fs_root_path);
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
		switch((string)$this->dav_method) {

			case 'OPTIONS':
				\SmartModExtLib\Webdav\DavFileSystem::methodOptions();
				break;

			case 'HEAD':
				\SmartModExtLib\Webdav\DavFileSystem::methodHead((string)$this->dav_vfs_path);
				break;

			case 'PROPFIND':
				\SmartModExtLib\Webdav\DavFileSystem::methodPropfind(
					(string) $this->dav_uri,
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(bool)   $this->dav_is_root_path,
					(string) $this->dav_vfs_root
				);
				break;

			/*
			// LOCK and UNLOCK are needed only by MacOS Finder which is very buggy atm, thus commenting this out will force MacOS Finder to run in read-only mode over this webDAV service
			case 'LOCK':
				\SmartModExtLib\Webdav\DavFileSystem::methodLock(
					(string) $this->dav_request_path,
					(string) $this->dav_author
				);
				break;
			case 'UNLOCK':
				\SmartModExtLib\Webdav\DavFileSystem::methodUnlock(
					(string) $this->dav_request_path,
					(string) $this->dav_author
				);
				break;
			*/

			case 'MKCOL':
				\SmartModExtLib\Webdav\DavFileSystem::methodMkcol((string)$this->dav_vfs_path);
				break;

			case 'PUT':
				\SmartModExtLib\Webdav\DavFileSystem::methodPut((string)$this->dav_vfs_path);
				break;

			case 'COPY':
				\SmartModExtLib\Webdav\DavFileSystem::methodCopy(
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(string) $this->dav_vfs_root
				);
				break;

			case 'MOVE':
				\SmartModExtLib\Webdav\DavFileSystem::methodMove(
					(string) $this->dav_request_path,
					(string) $this->dav_vfs_path,
					(string) $this->dav_vfs_root
				);
				break;

			case 'DELETE':
				\SmartModExtLib\Webdav\DavFileSystem::methodDelete((string)$this->dav_vfs_path);
				break;

			case 'GET':
				\SmartModExtLib\Webdav\DavFileSystem::methodGet(
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

			case 'POST':
				$webdav_action = $this->RequestVarGet('webdav_action', '', 'string');
				$webdav_mode = $this->RequestVarGet('webdav_mode', '', 'string');
				$new_dir_name  = $this->RequestVarGet('name', '', 'string');
				switch((string)$webdav_action) {
					case 'mkd':
						if((string)\trim((string)$new_dir_name != '')) {
							if((int)\SmartModExtLib\Webdav\DavFileSystem::methodPostMkd((string)$this->dav_url, (string)$this->dav_vfs_path, (string)\trim((string)$new_dir_name)) != 200) {
								return;
							} //end if
						} //end if
						break;
					case 'upf':
						if(\Smart::array_size($_FILES) > 0) {
							if((int)\SmartModExtLib\Webdav\DavFileSystem::methodPostUpf((string)$this->dav_url, (string)$this->dav_vfs_path) != 200) {
								return;
							} //end if
						} //end if
					default:
						// do nothing
				} //end switch
				//--
				if((string)$webdav_mode == 'bw') { // for browsers only
					\http_response_code(302); // force redirect to get to avoid refresh post file
					\header('Location: '.(string)$this->dav_url);
				} //end if
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
