<?php
// [@[#[!NO-STRIP!]#]@]
// [AppCodeUnpack / APP] v.20250207 s.20250207.2358
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APP_CUSTOM_LOG_PATH')) { // for standalone apps this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status (Custom Log) in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//-----------------------------------------------------
if((!defined('SMART_STANDALONE_APP')) OR (SMART_STANDALONE_APP !== true)) {
	@http_response_code(500);
	die('Invalid Runtime Status (Standalone) in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) {
	@http_response_code(500);
	die('Invalid Runtime Status (Mode) in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//-----------------------------------------------------
if(!defined('APP_CUSTOM_STANDALONE_DTIME')) {
	@http_response_code(500);
	die('The constant APP_CUSTOM_STANDALONE_DTIME is required by apcoddeunpack app and is undefined ...');
} //end if
//-----------------------------------------------------
if(!defined('APP_CUSTOM_STANDALONE_RHASH')) {
	@http_response_code(500);
	die('The constant APP_CUSTOM_STANDALONE_RHASH is required by apcoddeunpack app and is undefined ...');
} //end if
//-----------------------------------------------------
if(defined('APPCODEUNPACK_READY')) {
	@http_response_code(500);
	die('The constant APPCODEUNPACK_READY must be not defined outside apcoddeunpack app ...');
} //end if
const APPCODEUNPACK_READY = true;
//-----------------------------------------------------

// !!! IMPORTANT: do not do instant flush here, will make all headers after useless, includding the 500 status !!!


/**
 * Method: AppCodeUnpack Upgrade Script for Software Releases
 * Must be separate from the class AppCodeUnpack to avoid access on private things
 *
 * DEPENDS:
 * Smart.Framework + what depends Smart.Framework on
 *
 * @access 		private
 * @internal
 *
 */
function AppCodeUnpackIncludeUpgradeScript(string $path_to_upgrade_script) : void {
	//--
	// ISOLATE THE AFTER DEPLOYMENT UPGRADE SCRIPT
	//--
	if(!SmartFileSystem::is_type_file((string)$path_to_upgrade_script)) {
		throw new Exception('Upgrade Script not found: '.$path_to_upgrade_script);
		return;
	} //end if
	//--
	ob_start(); // prevent output from this script ...
	include_once((string)$path_to_upgrade_script); // don't suppress output errors ; isolate the upgrade script run (to avoid rewrite variables inside calling method)
	ob_end_clean();
	//--
} //END FUNCTION


/**
 * Class: AppCodeUnpack for Software Releases
 *
 * DEPENDS:
 * Smart.Framework + what depends Smart.Framework on
 *
 * @access 		private
 * @internal
 *
 */
final class AppCodeUnpack {

	// ::
	// v.20250207

	private const APPCODEUNPACK_VERSION = 's.20250207.2358';
	private const APPCODEUNPACK_SCRIPT = 'appcodeunpack.php';
	private const APPCODEUNPACK_TITLE = 'AppCodeUnpack';


	public static function runApp() : ?string {
		//--
		if(defined('APPCODEUNPACK_DONE')) { // prevent run this twice
			self::raiseXXXError(500, ' # Run Unpack Already Completed');
			die(__METHOD__.' # Run Unpack Already Completed');
			return null;
		} //end if
		define('APPCODEUNPACK_DONE', true);
		//--
		$initerr = (string) self::initUnpack();
		if((string)$initerr != '') {
			self::raiseXXXError(503, ' # Init Unpack ERR: '.$initerr);
			die(__METHOD__.' # Init Unpack ERR: '.$initerr);
			return null;
		} //end if
		//--
		self::prompt401Auth((string)APP_AUTH_ADMIN_USERNAME, (string)APP_AUTH_ADMIN_PLAIN_PASSWORD);
		SmartAuth::lock_auth_data(); // disallow authentication after this point
		if(SmartAuth::is_authenticated() !== true) {
			self::raiseXXXError(403, ' # Unpack Requires Authentication');
			die(__METHOD__.' # Unpack Requires Authentication');
			return null;
		} //end if
		//--
		$action = (string) trim((string)SmartFrameworkRegistry::getRequestVar('action', '', 'string'));
		$err = false;
		$out = '';
		//--
		switch((string)$action) {
			//--
			case 'logs-list':
				//--
				$appid = (string) trim((string)SmartFrameworkRegistry::getRequestVar('appid', '', 'string'));
				//--
				$logs = [];
				$apps_arr = [];
				$the_app_log_dir = '';
				$url_log_display = '#';
				//--
				if(
					((string)trim((string)$appid) == '')
					OR
					((int)strlen((string)$appid) < 4)
					OR // {{{SYNC-APPCODEPACK-ID-SIZE}}}
					((int)strlen((string)$appid) > 63)
					OR
					((string)AppNetUnPackager::unpack_valid_app_id((string)$appid) != '')
					OR
					(strpos((string)APPCODEPACK_DEPLOY_APPLIST, '<'.(string)$appid.'>') === false)
					OR
					(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$appid))
				) {
					$appid = ''; // reset !
					$apps_arr = (array) Smart::list_to_array((string)(defined('APPCODEPACK_DEPLOY_APPLIST') ? APPCODEPACK_DEPLOY_APPLIST : ''));
				} else {
					$the_app_log_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$appid).'tmp/logs/';
					if(
						SmartFileSysUtils::checkIfSafePath((string)$the_app_log_dir)
						AND
						SmartFileSystem::is_type_dir((string)$the_app_log_dir)
					) {
						$logs = (array) (new SmartGetFileSystem(true))->get_storage((string)$the_app_log_dir, false, false, '.log');
						$tmp_logs = (array) $logs['list-files'];
						$logs = [];
						for($i=0; $i<Smart::array_size($tmp_logs); $i++) {
							if(
								SmartFileSysUtils::checkIfSafeFileOrDirName((string)$tmp_logs[$i])
								AND
								SmartFileSysUtils::checkIfSafePath((string)$the_app_log_dir.$tmp_logs[$i])
							) {
								$logs[] = [
									'id' 	=> (string) $tmp_logs[$i],
									'size' 	=> (string) SmartUtils::pretty_print_bytes((int)SmartFileSystem::get_file_size((string)$the_app_log_dir.$tmp_logs[$i]), 2)
								];
							} //end if
						} //end for
						$url_log_display = (string) self::APPCODEUNPACK_SCRIPT.'?action=log-display';
					} //end if
				} //end if else
				//--
				$title = 'List: ERROR LOGS on this App Server';
				$main = (string) SmartMarkersTemplating::render_template(
					(string) (defined('APPCODEUNPACK_HTML_LIST_LOGS_TPL') ? APPCODEUNPACK_HTML_LIST_LOGS_TPL : '{#EMPTY-APPCODEUNPACK-LIST-LOGS-TPL#}'),
					[
						'SCRIPT' 		=> (string) self::APPCODEUNPACK_SCRIPT,
						'URL-LOG-VIEW' 	=> (string) $url_log_display,
						'APP-ID' 		=> (string) $appid,
						'LOGS-ARR' 		=> (array)  Smart::array_sort((array)$logs, 'rsort'),
						'APP-IDS-ARR' 	=> (array)  $apps_arr,
						'APP-LOG-DIR' 	=> (string) $the_app_log_dir,
					]
				);
				//--
				$out = (string) self::renderTPL(
					(string) $title,
					(string) $main
				);
				//--
				break;
				//--
			case 'log-display':
				//--
				$appid = (string) trim((string)SmartFrameworkRegistry::getRequestVar('appid', '', 'string'));
				$logfile = (string) trim((string)SmartFrameworkRegistry::getRequestVar('log', '', 'string'));
				//--
				if(
					((string)trim((string)$appid) == '')
					OR
					((int)strlen((string)$appid) < 4)
					OR // {{{SYNC-APPCODEPACK-ID-SIZE}}}
					((int)strlen((string)$appid) > 63)
					OR
					((string)AppNetUnPackager::unpack_valid_app_id((string)$appid) != '')
					OR
					(strpos((string)APPCODEPACK_DEPLOY_APPLIST, '<'.(string)$appid.'>') === false)
					OR
					(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$appid))
				) {
					self::raiseXXXError(400, ' # Invalid AppID: '.$appid);
					die(__METHOD__.' # Invalid AppID: '.$appid);
					return null;
				} //end if
				//--
				if(
					((string)trim((string)$logfile) == '') OR
					(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$logfile)) OR
					((string)substr((string)$logfile, -4, 4) != '.log')
				) {
					self::raiseXXXError(400, ' # Invalid Log File: '.$logfile);
					die(__METHOD__.' # Invalid Log File: '.$logfile);
					return null;
				} //end if
				//--
				$the_app_log_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$appid).'tmp/logs/';
				$the_app_log_file = (string) $the_app_log_dir.$logfile;
				$log_found = false;
				if(
					SmartFileSysUtils::checkIfSafePath((string)$the_app_log_dir)
					AND
					SmartFileSystem::is_type_dir((string)$the_app_log_dir)
					AND
					SmartFileSysUtils::checkIfSafePath((string)$the_app_log_file)
					AND
					SmartFileSystem::is_type_file((string)$the_app_log_file)
					AND
					SmartFileSystem::have_access_read((string)$the_app_log_file)
				) {
					$log_found = true;
				} //end if else
				//--
				if($log_found === true) {
					$fsize = (int) SmartFileSystem::get_file_size((string)$the_app_log_file);
					header('Content-Type: text/plain');
					header('Content-Disposition: inline; filename="'.Smart::safe_filename((string)$logfile).'"');
					header('Content-Length: '.(int)$fsize);
					echo '####### [ LogFile: `'.$the_app_log_file.'` # Size: '.(int)$fsize.' bytes @ '.date('Y-M-D H:i:s O').' # '.SmartUtils::get_server_current_url().' ] #######'."\n\n";
					readfile((string)$the_app_log_file);
					echo "\n".'####### [ #END: LogFile: `'.$the_app_log_file.'` ] #######'."\n";
					die(''); // stop here !
					return null;
				} else {
					self::raiseXXXError(400, ' # Log File Not Found: `'.$the_app_log_file.'`');
					die(__METHOD__.' # Log File Not Found: `'.$the_app_log_file.'`');
					return null;
				} //end if else
				//--
				break;
				//--
			case 'logs-cleanup':
				//--
				$frm = (array) SmartFrameworkRegistry::getRequestVar('frm', [], 'array');
				//--
				$status = 'ERROR';
				$msg = '?';
				if(Smart::array_size($frm) != 4) {
					$err = true;
					$status = 'Invalid Post Data';
					$msg = 'Data is empty or invalid !';
				} //end if
				if(!$err) {
					if(
						(!isset($frm['appid'])) 	OR (!Smart::is_nscalar($frm['appid']))
						OR
						(!isset($frm['uuid'])) 		OR (!Smart::is_nscalar($frm['uuid']))
						OR
						(!isset($frm['list'])) 		OR (!Smart::is_nscalar($frm['list']))
						OR
						(!isset($frm['checksum'])) 	OR (!Smart::is_nscalar($frm['checksum']))
					) {
						$err = true;
						$status = 'Invalid Post Data';
						$msg = 'Data format is not valid !';
					} //end if
				} //end if
				if(!$err) {
					if(
						((string)trim((string)$frm['appid']) == '')
						OR
						((int)strlen((string)$frm['appid']) < 4)
						OR // {{{SYNC-APPCODEPACK-ID-SIZE}}}
						((int)strlen((string)$frm['appid']) > 63)
					) {
						$err = true;
						$status = 'AppID is mandatory';
						$msg = 'AppID is empty or invalid';
					} //end if
				} //end if
				if(!$err) {
					if((string)AppNetUnPackager::unpack_valid_app_id((string)$frm['appid']) != '') {
						$err = true;
						$status = 'AppID must be valid';
						$msg = 'AppID is not valid';
					} //end if
				} //end if
				if(!$err) {
					if(strpos((string)APPCODEPACK_DEPLOY_APPLIST, '<'.(string)$frm['appid'].'>') === false) {
						$err = true;
						$status = 'AppID must be allowed';
						$msg = 'AppID is not allowed by current settings';
					} //end if
				} //end if
				if(!$err) {
					if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$frm['appid'])) {
						$err = true;
						$status = 'AppID must be allowed';
						$msg = 'AppID is not allowed by current settings';
					} //end if
				} //end if
				if(!$err) {
					if((string)SmartHashCrypto::sh3a512((string)$frm['list'].'#'.$frm['uuid'], true) !== (string)$frm['checksum']) {
						$err = true;
						$status = 'Data Checksum Error';
						$msg = 'Data checksum is wrong !';
					} //end if
				} //end if
				//--
				$fdata = null;
				if(!$err) {
					$fdata = (string) trim((string)Smart::b64_dec((string)$frm['list'], true)); // B64 STRICT
					if((string)$fdata == '') {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after unpack !';
					} //end if
				} //end if
				if(!$err) {
					$fdata = (string) trim((string)SmartCipherCrypto::bf_decrypt((string)$fdata, (string)$frm['uuid']));
					if((string)$fdata == '') {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after decrypt !';
					} //end if
				} //end if
				if(!$err) {
					$fdata = Smart::json_decode((string)$fdata);
					if((Smart::array_size($fdata) <= 0) OR (Smart::array_type_test($fdata) != 1)) {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after expand !';
					} //end if
				} //end if
				//--
				$arr_del_files = [];
				if(!$err) {
					$the_app_log_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$frm['appid']).'tmp/logs/';
					if((is_array($fdata)) AND (SmartFileSysUtils::checkIfSafePath((string)$the_app_log_dir))) {
						foreach($fdata as $key => $val) {
							if(Smart::is_nscalar($val)) {
								$val = (string) trim((string)$val);
								if((string)$val != '') {
									if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$val)) {
										if(
											((string)substr((string)$val, -4, 4) == '.log')
											AND
											(strpos((string)$the_app_log_dir, '/tmp/logs/') !== false)
											AND
											SmartFileSysUtils::checkIfSafePath((string)$the_app_log_dir.$val)
											AND
											SmartFileSystem::is_type_file((string)$the_app_log_dir.$val)
										) {
											if(SmartFileSystem::delete((string)$the_app_log_dir.$val)) {
												$arr_del_files[] = (string) $the_app_log_dir.$val;
											} //end if
										} //end if
									} //end if
								} //end if
							} //end if
						} //end foreach
					} //end if
				} //end if
				//--
				if(!$err) {
					$msg = 'DEPLOYMENTS Cleanup: Removed #'.(int)Smart::array_size($arr_del_files).' log file(s) ...';
					$status = 'OK';
				} //end if
				//--
				$out = (string) self::jsAjaxReplyToHtmlForm((string)$status, (string)$status, (string)$msg);
				//--
				break;
				//--
			case 'deploys-list':
				//--
				$deploys = [];
				//--
				if(
					SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)
					AND
					SmartFileSystem::is_type_dir(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)
				) {
					if(
						SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER)
						AND
						SmartFileSystem::is_type_dir(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER)
					) {
						$deploys = (array) (new SmartGetFileSystem(true))->get_storage((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER, false);
						$deploys = (array) $deploys['list-dirs'];
					} //end if
				} //end if
				//--
				$title = 'List: DEPLOYMENTS on this App Server';
				$main = (string) SmartMarkersTemplating::render_template(
					(string) (defined('APPCODEUNPACK_HTML_LIST_DEPLOYS_TPL') ? APPCODEUNPACK_HTML_LIST_DEPLOYS_TPL : '{#EMPTY-APPCODEUNPACK-LIST-DEPLOYS-TPL#}'),
					[
						'SCRIPT' 		=> (string) self::APPCODEUNPACK_SCRIPT,
						'DEPLOYS-ARR' 	=> (array)  Smart::array_sort((array)$deploys, 'rsort'),
					]
				);
				//--
				$out = (string) self::renderTPL(
					(string) $title,
					(string) $main
				);
				//--
				break;
				//--
			case 'deploys-cleanup':
				//--
				$frm = (array) SmartFrameworkRegistry::getRequestVar('frm', [], 'array');
				//--
				$status = 'ERROR';
				$msg = '?';
				if(Smart::array_size($frm) != 3) {
					$err = true;
					$status = 'Invalid Post Data';
					$msg = 'Data is empty or invalid !';
				} //end if
				if(!$err) {
					if(
						(!isset($frm['uuid'])) 		OR (!Smart::is_nscalar($frm['uuid']))
						OR
						(!isset($frm['list'])) 		OR (!Smart::is_nscalar($frm['list']))
						OR
						(!isset($frm['checksum'])) 	OR (!Smart::is_nscalar($frm['checksum']))
					) {
						$err = true;
						$status = 'Invalid Post Data';
						$msg = 'Data format is not valid !';
					} //end if
				} //end if
				if(!$err) {
					if((string)SmartHashCrypto::sh3a512((string)$frm['list'].'#'.$frm['uuid'], true) !== (string)$frm['checksum']) {
						$err = true;
						$status = 'Data Checksum Error';
						$msg = 'Data checksum is wrong !';
					} //end if
				} //end if
				//--
				$fdata = null;
				if(!$err) {
					$fdata = (string) trim((string)Smart::b64_dec((string)$frm['list'], true)); // B64 STRICT
					if((string)$fdata == '') {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after unpack !';
					} //end if
				} //end if
				if(!$err) {
					$fdata = (string) trim((string)SmartCipherCrypto::tf_decrypt((string)$fdata, (string)$frm['uuid']));
					if((string)$fdata == '') {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after decrypt !';
					} //end if
				} //end if
				if(!$err) {
					$fdata = Smart::json_decode((string)$fdata);
					if((Smart::array_size($fdata) <= 0) OR (Smart::array_type_test($fdata) != 1)) {
						$err = true;
						$status = 'Empty Data';
						$msg = 'Data is Empty after expand !';
					} //end if
				} //end if
				//--
				$arr_del_dirs = [];
				$arr_del_files = [];
				if(!$err) {
					if(is_array($fdata)) {
						foreach($fdata as $key => $val) {
							if(Smart::is_nscalar($val)) {
								$val = (string) trim((string)$val);
								if((string)$val != '') {
									if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$val)) {
										if(
											SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)
											AND
											SmartFileSystem::is_type_dir(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)
										) {
											if(
												SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER)
												AND
												SmartFileSystem::is_type_dir(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER)
											) {
												if(
													SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val)
													AND
													SmartFileSystem::is_type_dir(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val)
												) {
													if(SmartFileSystem::dir_delete((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val)) {
														$arr_del_dirs[] = (string) AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val;
													} //end if
												} //end if
												if(
													SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val.'.log')
													AND
													SmartFileSystem::is_type_file(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val.'.log')
												) {
													if(SmartFileSystem::delete((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val.'.log')) {
														$arr_del_files[] = (string) AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.AppNetUnPackager::APP_NET_UNPACKAGER_DEPLOYS_FOLDER.$val.'.log';
													} //end if
												} //end if
											} //end if
										} //end if
									} //end if
								} //end if
							} //end if
						} //end foreach
					} //end if
				} //end if
				//--
				if(!$err) {
					$msg = 'DEPLOYMENTS Cleanup: Removed #'.(int)Smart::array_size($arr_del_dirs).' dir(s) and #'.(int)Smart::array_size($arr_del_files).' file(s) ...';
					$status = 'OK';
				} //end if
				//--
				$out = (string) self::jsAjaxReplyToHtmlForm((string)$status, (string)$status, (string)$msg);
				//--
				break;
				//--
			case 'deploy':
				//--
				$frm = (array) SmartFrameworkRegistry::getRequestVar('frm', [], 'array');
				//--
				$status = '?';
				$msg = '??';
				//--
				$znetarch_att_safecheck = false;
				$znetarch_att_fsize = 0;
				$znetarch_att_fcsize = 0;
				$znetarch_att_fname = '';
				$znetarch_att_content = '';
				$znetarch_att_sh2a512b64 = '';
				//--
				if(defined('APPCODEPACK_APP_ID')) {
					$err = true;
					$status = 'AppID Define';
					$msg = 'AppID must not be pre-defined !';
				} //end if
				//--
				if(!$err) {
					if(Smart::array_size($frm) <= 0) {
						$err = true;
						$status = 'No Form Data';
						$msg = 'Form contains no data';
					} //end if
				} //end if
				if(!$err) {
					if(
						(!isset($frm['appid']))
						OR
						(!Smart::is_nscalar($frm['appid']))
						OR
						((string)trim((string)$frm['appid']) == '')
						OR
						((int)strlen((string)$frm['appid']) < 4)
						OR // {{{SYNC-APPCODEPACK-ID-SIZE}}}
						((int)strlen((string)$frm['appid']) > 63)
					) {
						$err = true;
						$status = 'AppID is mandatory';
						$msg = 'AppID is empty or invalid';
					} //end if
				} //end if
				if(!$err) {
					if((string)AppNetUnPackager::unpack_valid_app_id((string)$frm['appid']) != '') {
						$err = true;
						$status = 'AppID must be valid';
						$msg = 'AppID is not valid';
					} //end if
				} //end if
				if(!$err) {
					if(strpos((string)APPCODEPACK_DEPLOY_APPLIST, '<'.(string)$frm['appid'].'>') !== false) {
						define('APPCODEPACK_APP_ID', (string)$frm['appid']);
					} else {
						$err = true;
						$status = 'AppID must be allowed';
						$msg = 'AppID is not allowed by current settings';
					} //end if
				} //end if
				if(!$err) {
					if(
						(!isset($frm['appid-hash']))
						OR
						(!Smart::is_nscalar($frm['appid-hash']))
						OR
						((int)strlen((string)trim((string)$frm['appid-hash'])) < 64) // HMAC B62 SHA384
					) {
						$err = true;
						$status = 'AppID-Hash is mandatory';
						$msg = 'Empty or Invalid AppID-Hash';
					} //end if
				} //end if
				if(!$err) {
					if((string)$frm['appid-hash'] !== (string)AppNetUnPackager::unpack_app_hash((string)APPCODEPACK_DEPLOY_SECRET)) {
						$err = true;
						$status = 'AppID-Hash must be valid';
						$msg = 'AppID-Hash is not valid';
					} //end if
				} //end if
				//--
				if(!$err) {
					$tmp_att = (array) SmartUtils::read_uploaded_file(
						'znetarch',
						-1,
						0,
						'<z-netarch>'
					);
					if(((string)$tmp_att['status'] == 'OK') AND ((string)$tmp_att['msg-code'] == '0')) {
						//--
						$znetarch_att_fsize = (int) $tmp_att['filesize']; // fsize reported from disk, not safe
						$znetarch_att_fname = (string) trim((string)$tmp_att['filename']);
						$znetarch_att_content = (string) trim((string)$tmp_att['filecontent']);
						$znetarch_att_sh2a512b64 = (string) SmartHashCrypto::sh3a512((string)$tmp_att['filecontent'], true);
						$znetarch_att_fcsize = (int) strlen((string)$tmp_att['filecontent']); // this is safe, does not depened on what OS reports !
						//--
						if(
							isset($frm['packsh3a512b64'])
							AND
							Smart::is_nscalar($frm['packsh3a512b64'])
							AND
							((string)trim((string)$frm['packsh3a512b64']) != '')
							AND
							((int)strlen((string)$frm['packsh3a512b64']) >= (int)88) // SH3A512 hex/b64
							AND
							isset($frm['packsize'])
							AND
							Smart::is_nscalar($frm['packsize'])
							AND
							((string)trim((string)$frm['packsize']) != '')
							AND
							((int)$frm['packsize'] > 0)
						) {
							if((string)$frm['packsh3a512b64'] !== (string)$znetarch_att_sh2a512b64) {
								$err = true;
								$status = 'Uploaded Package Safety Checksum does not match !';
								$msg = 'The uploaded content checksum: '.(string)$znetarch_att_sh2a512b64.' # the safety checksum: '.(string)$frm['packsh3a512b64'];
							} elseif((int)$frm['packsize'] != (int)$znetarch_att_fcsize) {
								$err = true;
								$status = 'Uploaded Package Safety Size does not match !';
								$msg = 'The uploaded content size: '.(int)$znetarch_att_fcsize.' # the safety checksum: '.(int)$frm['packsize'];
							} //end if else
							$znetarch_att_safecheck = true; // must be set also on err, to know it was checked
						} //end if
						//--
					} //end if else
					$tmp_att = null;
					if($err) {
						// stop here, there are errors from above
					} elseif((int)$znetarch_att_fsize <= 0) {
						$err = true;
						$status = 'Empty File Uploaded';
						$msg = 'The uploaded file size is: '.(int)$znetarch_att_fsize.' bytes';
					} elseif((int)$znetarch_att_fcsize <= 0) {
						$err = true;
						$status = 'Empty File Uploaded Content';
						$msg = 'The uploaded content size is: '.(int)$znetarch_att_fcsize.' bytes';
					} elseif((string)$znetarch_att_content == '') {
						$err = true;
						$status = 'Empty File Content Uploaded';
						$msg = 'The uploaded file content size is: '.(int)strlen((string)$znetarch_att_content).' bytes';
					} elseif((string)$znetarch_att_fname == '') {
						$err = true;
						$status = 'Empty File Name Uploaded';
						$msg = 'The uploaded file name is empty';
					} elseif((string)substr((string)$znetarch_att_fname, -10, 10) != '.z-netarch') {
						$err = true;
						$status = 'Invalid File Type Uploaded';
						$msg = 'The uploaded file type is invalid: '.$znetarch_att_fname;
					} else {
						if(!defined('APPCODEPACK_APP_ID')) {
							$err = true;
							$status = 'INTERNAL ERROR';
							$msg = 'A required constant has not been defined: APPCODEPACK_APP_ID';
						} else {
							$test_err_appid = (string) AppNetUnPackager::unpack_valid_app_id((string)APPCODEPACK_APP_ID);
							if((string)$test_err_appid != '') {
								$err = true;
								$status = 'Invalid AppID';
								$msg = 'APP ID ERROR: '.$test_err_appid.' APPCODEPACK_APP_ID='.APPCODEPACK_APP_ID;
							} else {
								$unpack_err = (string) AppNetUnPackager::unpack_netarchive((string)$znetarch_att_content, true); // test only
								$unpack_err = (string) trim((string)$unpack_err);
								if((string)$unpack_err != '') {
									$err = true;
									$status = 'Unpack Test Archive';
									$msg = (string) 'Unpack Test Errors: '.$unpack_err;
								} else {
									$unpack_err = (string) AppNetUnPackager::unpack_netarchive((string)$znetarch_att_content, false); // extract
									$unpack_err = (string) trim((string)$unpack_err);
									if((string)$unpack_err != '') {
										$err = true;
										$status = 'Unpack Archive';
										$msg = (string) 'Unpack Errors: '.$unpack_err;
									} else {
										$upgrade_err = (string) self::runUpgradeScript(
											(string) Smart::safe_filename((string)APPCODEPACK_APP_ID),
											(string) AppNetUnPackager::unpack_get_last_log_file()
										);
										if((string)$upgrade_err != '') {
											$err = true;
											$status = 'Appcode Upgrade Script';
											$msg = (string) 'Unpack Completed but the Upgrade Script returned some ERRORS: '.$upgrade_err;
										} else {
											$release_maintenance_err = (string) self::releaseMaintenanceFile(
												(string) Smart::safe_filename((string)APPCODEPACK_APP_ID),
												(string) AppNetUnPackager::unpack_get_last_log_file()
											);
											if((string)$release_maintenance_err != '') {
												$err = true;
												$status = 'Appcode Release Maintenance';
												$msg = (string) 'Unpack Completed but FAILED to release the maintenance file. ERRORS: '.$release_maintenance_err;
											} //end if
										} //end if else
									} //end if else
								} //end if else
							} //end if else
						} //end if else
					} //end if else
				} //end if
				//--
				if(!$err) {
					$status = 'OK';
					$msg = 'Package Deploy Successful';
				} else {
					$status = 'ERR: '.$status;
				} //end if
				//--
				$crr_url = (string) SmartUtils::get_server_current_url().SmartUtils::get_server_current_script();
				//--
				$msg .= "\n".'FileSize: '.SmartUtils::pretty_print_bytes((int)$znetarch_att_fsize, 2);
				if(isset($frm['client']) AND ((string)$frm['client'] == 'appcodepack')) {
					$msg .= "\n".'FileSize-Bytes: `'.(int)$znetarch_att_fcsize.'`'; // safe size
					$msg .= "\n".'FileContent-Checksum: `'.$znetarch_att_sh2a512b64.'`';
				} //end if
				$msg .= "\n".'FileName: `'.$znetarch_att_fname.'`';
				$msg .= "\n".'AppID: `'.$frm['appid'].'`';
				$msg .= "\n".'AppID-Hash: `'.$frm['appid-hash'].'`';
				if(isset($frm['client']) AND ((string)$frm['client'] == 'appcodepack')) {
					$msg .= "\n".'Deploy-URL: `'.$crr_url.'`';
					$msg .= "\n".'Signature: `'.sha1('#'.$frm['appid'].'#'.$frm['appid-hash'].'#'.$znetarch_att_fname.'#'.$crr_url.'#').'`'; // {{{SYNC-APP-DEPLOY-SIGNATURE}}}
					$msg .= "\n".'Safety-Checks: `'.(($znetarch_att_safecheck === true) ? 'yes' : 'no').'`';
				} //end if
				//--
				if(isset($frm['appcodeunpack']) AND (Smart::array_size($frm['appcodeunpack']) > 0)) { // {{{SYNC-APPCODEUNPACK-SELF-UPDATE}}}
					//--
					$a_err = '';
					//--
					if(
						isset($frm['appcodeunpack']['#']) AND Smart::is_nscalar($frm['appcodeunpack']['#']) AND ((string)trim((string)$frm['appcodeunpack']['#']) != '')
						AND
						isset($frm['appcodeunpack']['=']) AND Smart::is_nscalar($frm['appcodeunpack']['=']) AND ((string)trim((string)$frm['appcodeunpack']['=']) != '')
						AND
						isset($frm['appcodeunpack']['@']) AND Smart::is_nscalar($frm['appcodeunpack']['@']) AND ((string)trim((string)$frm['appcodeunpack']['@']) != '')
						AND
						isset($frm['appcodeunpack']['!']) AND Smart::is_nscalar($frm['appcodeunpack']['!']) AND ((string)trim((string)$frm['appcodeunpack']['!']) != '')
					) {
						$tmp_upd_ctx = (string) SmartHashCrypto::hmac(
							'sha3-384',
							(string) APPCODEPACK_DEPLOY_SECRET.'#'.((defined('SMART_FRAMEWORK_SECURITY_KEY') && ((string)SMART_FRAMEWORK_SECURITY_KEY != '')) ? SMART_FRAMEWORK_SECURITY_KEY : Smart::uuid_34()),
							(string)$frm['appcodeunpack']['#'].chr(0).$frm['appcodeunpack']['@'],
							true
						);
						if((string)$tmp_upd_ctx !== (string)$frm['appcodeunpack']['!']) {
							$a_err = 'AppCodeUnpack Update: Invalid Checksum';
						} else {
							$tmp_upd_ctx = null;
							if((string)$frm['appcodeunpack']['@'] !== (string)SmartHashCrypto::sh3a384((string)$frm['appcodeunpack']['='], true)) {
								$a_err = 'AppCodeUnpack Update: Invalid Data Checksum';
							} else {
								$tmp_upd_ctx = (string) SmartCipherCrypto::tf_decrypt((string)$frm['appcodeunpack']['='], (string)APPCODEPACK_DEPLOY_SECRET);
								if(
									((string)trim((string)$tmp_upd_ctx) != '') AND
									((string)$frm['appcodeunpack']['#'] === (string)SmartHashCrypto::sh3a512((string)$tmp_upd_ctx, true))
								) {
									SmartFileSystem::write((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php', (string)$tmp_upd_ctx);
									if((string)SmartFileSystem::read((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php') === (string)$tmp_upd_ctx) {
										$tmp_upd_ctx = null;
										$tmp_upd_ctx = (string) php_strip_whitespace((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php');
										if((string)$tmp_upd_ctx != '') {
											if(!SmartFileSystem::rename((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php', 'appcodeunpack.php', true)) {
												$a_err = 'AppCodeUnpack Update: Replace Failed !';
											} //end if
										} else {
											$a_err = 'AppCodeUnpack Update: Saved content check Failed !';
										} //end if else
									} else {
										$a_err = 'AppCodeUnpack Update: Saved content does not match !';
									} //end if else
								} else {
									$a_err = 'AppCodeUnpack Update: Invalid Source Checksum';
								} //end if else
								$tmp_upd_ctx = null;
							} //end if
						} //end if else
						$tmp_upd_ctx = null;
					} //end if
					//--
					$msg .= "\n".'AppCodeUnpack-Update: `'.(((string)$a_err == '') ? 'OK' : 'ERR: '.$a_err).'`';
					//--
				} //end if
				if(SmartFileSystem::is_type_file(AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php')) {
					SmartFileSystem::delete((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'appcodeunpack.php'); // just in case
				} //end if
				//--
				$msg .= "\n".'AppCodeUnpack-Version: '.self::APPCODEUNPACK_VERSION;
				//--
				$out = (string) self::jsAjaxReplyToHtmlForm((string)$status, (string)$status, (string)$msg);
				//--
				break;
				//--
			case '':
				//--
				$loader = false;
				$title = 'DEPLOY: Upload a new AppCodePack Archive on this App Server';
				$main = (string) SmartMarkersTemplating::render_template(
					(string) (defined('APPCODEUNPACK_HTML_DEPLOY') ? APPCODEUNPACK_HTML_DEPLOY : '{#EMPTY-APPCODEUNPACK-DEPLOY-TPL#}'),
					[
						'SCRIPT' 			=> (string) self::APPCODEUNPACK_SCRIPT,
						'APP-IDS-ARR' 		=> (array)  Smart::list_to_array((string)(defined('APPCODEPACK_DEPLOY_APPLIST') ? APPCODEPACK_DEPLOY_APPLIST : '')),
						'MAX-UPLD-SIZE' 	=> (string) ini_get('upload_max_filesize'),
						'HTML-WATCH' 		=> (string) (defined('APPCODEUNPACK_HTML_WATCH') ? APPCODEUNPACK_HTML_WATCH : ''),
					]
				);
				//--
				$out = (string) self::renderTPL((string)$title, (string)$main, (bool)$loader);
				//--
				break;
				//--
			default:
				//--
				self::raiseXXXError(404, 'Action not implemented: `'.$action.'`');
				//--
				return null;
				//--
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION


	//======= [PRIVATES]


	private static function initUnpack() : string {
		//--
		if(defined('APPCODEUNPACK_INIT_DONE')) { // prevent run this twice
			self::raiseXXXError(500, ' # Init Unpack Already Completed');
			die(__METHOD__.' # Init Unpack Already Completed');
		} //end if
		define('APPCODEUNPACK_INIT_DONE', true);
		//--
		if(!headers_sent()) { // {{{SYNC-HTTP-NOCACHE-HEADERS}}}
			header('Cache-Control: no-cache, must-revalidate'); // HTTP 1.1 no-cache
			header('Pragma: no-cache'); // HTTP 1.0 no-cache
			header('X-Powered-By: Smart AppCodeUnpack [T] [S]');
		} else {
			Smart::log_warning(__METHOD__.' # headers already sent');
		} //end if else
		//--
		if(defined('SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER') AND ((string)SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER != '')) {
			Smart::raise_error(__METHOD__.' # Standalone Apps do not support enabling SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER ...');
			die('ERR:'.__METHOD__.': SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER should be disabled');
		} //end if
		//--
		if(!function_exists('apache_get_version')) {
			self::raiseXXXError(500, ' # This script requires PHP and Apache HTTP/S Server');
			die(__METHOD__.' # No Apache HTTPD Environment Detected ...');
		} //end if
		//-- EXTRACT, FILTER AND REGISTER INPUT VARIABLES: SERVER, GET, POST, COOKIE and SERVER[PATH_INFO] # {{{SYNC-SF-EXTRACT-VARS}}}
		SmartFrameworkRegistry::registerFilteredServerVars((array)(is_array($_SERVER) ? $_SERVER 			: [])); 			// extract and filter $_SERVER ; must be above the line which extracts te PATH_INFO
		SmartFrameworkRegistry::lockServerRegistry();																			// lock request vars registry and prevent re-process the Server vars
		// no support for parse semantic URL in this context, but need path info (as set below, which is handled internally by parse semantic URL ...)
		SmartFrameworkRegistry::setRequestPath((string)SmartFrameworkRegistry::getServerVar('PATH_INFO')); 						// extract the Special PathInfo handled by Smart.Framework using $_SERVER['PATH_INFO'] (the path after the first occurence of `/~` if any, and register it to registry)
		SmartFrameworkRegistry::registerFilteredRequestVars((array)(is_array($_GET)   ? $_GET 				: []), 'GET'); 		// extract and filter $_GET
		SmartFrameworkRegistry::registerFilteredRequestVars((array)(is_array($_POST)  ? $_POST 				: []), 'POST'); 	// extract and filter $_POST
		SmartFrameworkRegistry::registerFilteredCookieVars((array)(is_array($_COOKIE) ? $_COOKIE 			: [])); 			// extract and filter $_COOKIE
		SmartFrameworkRegistry::lockRequestRegistry(); 																			// lock request vars registry and prevent re-process Request or Cookie variables after they were processed 1st time (this is mandatory from security point of view)
		//--
		if(!defined('APP_AUTH_ADMIN_ENFORCE_HTTPS')) { // this is mandatory ...
			define('APP_AUTH_ADMIN_ENFORCE_HTTPS', true); // just in case ...
		} //end if
		$enforce_https = false;
		if(APP_AUTH_ADMIN_ENFORCE_HTTPS !== false) {
			$enforce_https = true;
		} //end if
		if($enforce_https === true) {
			if((string)SmartUtils::get_server_current_protocol() !== 'https://') {
				self::raiseXXXError(403, 'This Area require HTTPS'."\n".'Switch from http:// to https:// in order to use this Web Area');
				die('ERR:'.__METHOD__.':403#NotHTTPS');
			} //end if
		} //end if
		//--
		if(defined('SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS') AND ((string)trim((string)SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS) != '')) {
			if(strpos((string)SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS, '<'.SmartUtils::get_ip_client().'>') === false) {
				self::raiseXXXError(403, 'Allowed IP Address list does not contain this IP ...');
				die('ERR:'.__METHOD__.':403#InvalidIP');
			} //end if
		} else {
			self::raiseXXXError(403, 'Allowed IP Address list has not been defined or is empty ...');
			die('ERR:'.__METHOD__.':403#NoIPList');
		} //end if else
		//--
		array_map(
			function($const){
				if(
					(!defined((string)$const)) OR
					(
						(constant((string)$const) !== null) AND
						(constant((string)$const) !== false) AND
						(
							(!Smart::is_nscalar(constant((string)$const)))
							OR
							((string)trim((string)constant((string)$const)) == '')
						)
					)
				) {
					self::raiseXXXError(500, 'An AppCodeUnpack constant has not been defined or have an empty or non-scalar value: '.$const);
					die('ERR:Undefined:'.$const);
				}
			},
			[
				'APPCODEUNPACK_HTML_ERRTPL', 'APPCODEUNPACK_HTML_TPL',
				'APPCODEUNPACK_BASE_STYLES', 'APPCODEUNPACK_TOOLKIT_STYLES', 'APPCODEUNPACK_NOTIFICATION_STYLES', 'APPCODEUNPACK_LOCAL_STYLES',
				'APPCODEUNPACK_JS_JQUERY', 'APPCODEUNPACK_JS_SMART_UTILS', 'APPCODEUNPACK_JS_SMART_DATE', 'APPCODEUNPACK_JS_SMART_CRYPTO',
				'APPCODEUNPACK_CSS_GRITTER', 'APPCODEUNPACK_JS_GRITTER', 'APPCODEUNPACK_CSS_ALERTABLE', 'APPCODEUNPACK_JS_ALERTABLE',
				'APPCODEUNPACK_JS_WATCH', 'APPCODEUNPACK_HTML_WATCH',
				'APPCODEUNPACK_LOGO_SVG', 'APPCODEUNPACK_LOGO_APACHE_SVG', 'APPCODEUNPACK_LOGO_PHP_SVG', 'APPCODEUNPACK_LOGO_NETARCH_SVG',
				'APPCODEUNPACK_LOGO_SF_SVG', 'APPCODEUNPACK_LOADING_SVG',
				'APPCODEUNPACK_CSS_LOCAL_FX', 'APPCODEUNPACK_JS_LOCAL_FX',
				'APPCODEUNPACK_HTML_DEPLOY',
				'APP_AUTH_ADMIN_USERNAME', 'APP_AUTH_ADMIN_PASSWORD', // 'APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY',
				'APPCODEPACK_DEPLOY_SECRET', 'APPCODEPACK_DEPLOY_APPLIST',
				'SMART_FRAMEWORK_SRVPROXY_ENABLED',
			]
		);
		//--
		if(((string)trim((string)APPCODEPACK_DEPLOY_SECRET) == '') OR ((int)strlen((string)trim((string)APPCODEPACK_DEPLOY_SECRET)) < 28) OR ((int)strlen((string)trim((string)APPCODEPACK_DEPLOY_SECRET)) > 98)) { // {{{SYNC-APPCODE-CONDITION-VALIDATE-SECRET}}}
			Smart::raise_error(__METHOD__.' # Invalid AppCodeUnpack Deploy Secret ! Must be between 28 and 98 characters ...');
			die('ERR:'.__METHOD__.': Invalid AppCodeUnpack Deploy Secret');
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)) {
			Smart::raise_error(__METHOD__.' # Invalid AppCodeUnpack Dir: `'.AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'`');
			die('ERR:'.__METHOD__.': Invalid AppCodeUnpack Dir');
		} //end if
		if(!SmartFileSystem::is_type_dir((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)) {
			SmartFileSystem::dir_create((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER, true);
		} //end if
		if(!SmartFileSystem::is_type_dir((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER)) {
			Smart::raise_error(__METHOD__.' # Failed to create the AppCodeUnpack Dir: `'.AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'`');
			die('ERR:'.__METHOD__.': Failed to create the AppCodeUnpack Dir');
		} //end if
		//--
		if(SmartFileSystem::write_if_not_exists((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'.htaccess', (string)AppNetUnPackager::APP_NET_UNPACKAGER_HTACCESS_PROTECT, 'yes') != 1) { // write if not exists wit content compare
			return 'AppCodeUnpack Base Folder .htaccess failed to be (re)written !';
		} //end if
		if(!SmartFileSystem::is_type_file((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'index.html')) {
			if(SmartFileSystem::write((string)AppNetUnPackager::APP_NET_UNPACKAGER_FOLDER.'index.html', '') != 1) {
				return 'AppCodeUnpack Base Folder index.html failed to be (re)written !';
			} //end if
		} //end if
		//--
		if(SmartAuth::validate_auth_username(
			(string) APP_AUTH_ADMIN_USERNAME,
			true // check for reasonable length, as 5 chars
		) !== true) { // {{{SYNC-AUTH-VALIDATE-USERNAME}}}
			self::raiseXXXError(403, 'Init Settings ERROR: Invalid UserName');
			die('ERR:'.__METHOD__.':403#InitAuthUserError');
		} //end if
		if(defined('APP_AUTH_ADMIN_PLAIN_PASSWORD')) {
			Smart::raise_error(__METHOD__.' # The constant `APP_AUTH_ADMIN_PLAIN_PASSWORD` was already defined and should not !');
			die('ERR:'.__METHOD__.': APP_AUTH_ADMIN_PLAIN_PASSWORD already defined');
		} //end if
		define('APP_AUTH_ADMIN_PLAIN_PASSWORD', (string)SmartCipherCrypto::bf_decrypt((string)APP_AUTH_ADMIN_PASSWORD, (string)APPCODEPACK_DEPLOY_SECRET));
		if(SmartAuth::validate_auth_password( // {{{SYNC-AUTH-VALIDATE-PASSWORD}}}
			(string) \APP_AUTH_ADMIN_PLAIN_PASSWORD,
			true // check for complexity
		) !== true) {
			self::raiseXXXError(403, 'Init Settings ERROR: Invalid Password');
			die('ERR:'.__METHOD__.':403#InitAuthPassError');
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


	private static function prompt401Auth(?string $username, ?string $password) : void {
		//--
		$logout = (string) trim((string)SmartFrameworkRegistry::getRequestVar('logout', '', 'string'));
		//--
		$username = (string) trim((string)$username);
		$password = (string) trim((string)$password);
		//--
		// {{{SYNC-AUTH-TOKEN-SWT}}}
		//--
		if(
			((string)$logout == '')
			AND
			isset($_SERVER['PHP_AUTH_USER'])
			AND
			isset($_SERVER['PHP_AUTH_PW'])
			AND
			((string)$username != '') AND ((string)$password != '')
			AND
			((string)trim((string)$_SERVER['PHP_AUTH_USER']) != '') AND ((string)trim((string)$_SERVER['PHP_AUTH_PW']) != '')
			AND
			((string)$_SERVER['PHP_AUTH_USER'] === (string)$username) AND ((string)$_SERVER['PHP_AUTH_PW'] === (string)$password)
			AND
			(SmartAuth::validate_auth_username(
				(string) $_SERVER['PHP_AUTH_USER'],
				true // check for reasonable length, as min 5 chars
			) === true)
			AND
			(SmartAuth::validate_auth_password(
				(string) $_SERVER['PHP_AUTH_PW'],
				true // check for complexity + length, as min 8 chars
			) === true)
		) {
			//-- OK, logged in
			$privileges = (array) Smart::list_to_array((string)\SmartAuth::DEFAULT_PRIVILEGES); // {{{SYNC-SMART-DEFAULT-PRIVILEGES}}}
			$priv_keys = '';
			if(\defined('\\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY')) {
				if((string)\trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY) != '') {
					$priv_keys = (string) \trim((string)\APP_AUTH_ADMIN_ENCRYPTED_PRIVKEY);
					if((string)$priv_keys != '') {
						$priv_keys = (string) \SmartAuth::decrypt_sensitive_data((string)$priv_keys, (string)$hash_of_pass);
					} //end if
				} //end if
			} //end if
			//--
			$hash_of_pass = (string) SmartHashCrypto::password((string)$_SERVER['PHP_AUTH_PW'], (string)$_SERVER['PHP_AUTH_USER']);
			//--
			SmartAuth::set_auth_data( // v.20250128
				'APPCODEUNPACK-AREA', 								// auth realm
				'HTTP-BASIC', 										// auth method {{{SYNC-AUTH-METHODS-NAME}}}
				(int)    \SmartAuth::ALGO_PASS_SMART_SAFE_SF_PASS,  // pass algo
				(string) $hash_of_pass, 							// auth password hash (will be stored as encrypted, in-memory)
				(string) $_SERVER['PHP_AUTH_USER'], 				// auth user name
				(string) $_SERVER['PHP_AUTH_USER'], 				// auth ID (on backend must be set exact as the auth username)
				'', 												// user email * Optional *
				'Super Admin', 										// user full name (First Name + ' ' + Last name) * Optional *
				(array) $privileges, 								// user privileges * Optional *
				(array)  [ 'def-account', 'account' ], 				// user restrictions ; {{{SYNC-AUTH-RESTRICTIONS}}} ; {{{SYNC-DEF-ACC-EDIT-RESTRICTION}}} ; {{{SYNC-ACC-NO-EDIT-RESTRICTION}}}
				(array)  [ // {{{SYNC-AUTH-KEYS}}}
					'privkey' => (string) $priv_keys 				// user private key (will be stored as encrypted, in-memory) {{{SYNC-ADM-AUTH-KEYS}}}
					// TODO: have also a private or security key ?
				],
				0, 													// user quota in MB * Optional * ... zero, aka unlimited
				[ 													// user metadata (array) ; may vary
					'auth-safe' => 1,
					'name_f' 	=> 'Super',
					'name_l' 	=> 'Admin',
				]
			);
			//--
		} else {
			//-- log unsuccessful login
			if(defined('APP_CUSTOM_LOG_PATH')) {
				if(isset($_SERVER['PHP_AUTH_USER']) AND ((string)$_SERVER['PHP_AUTH_USER'] != '')) {
					SmartFileSystem::write(
					APP_CUSTOM_LOG_PATH.'auth-fail-'.date('Y-m-d@H').'.log',
						'[FAIL]'."\t".Smart::normalize_spaces((string)\date('Y-m-d H:i:s O'))."\t".Smart::normalize_spaces((string)$_SERVER['PHP_AUTH_USER'])."\t".Smart::normalize_spaces((string)SmartUtils::get_ip_client())."\t".Smart::normalize_spaces((string)SmartUtils::get_visitor_useragent())."\n",
						'a'
					);
				} //end if
			} //end if
			//-- NOT OK, display the Login Form and Exit
			if(!headers_sent()) {
				self::outputSafeHttpHeader('WWW-Authenticate: Basic realm="'.self::APPCODEUNPACK_TITLE.'"');
				self::outputSafeHttpHeader('HTTP/1.0 401 Authorization Required');
				http_response_code(401);
			} else {
				Smart::log_warning(__METHOD__.' # Headers Already Sent before prompt 401 ...');
			} //end if else
			//--
			die((string)self::renderErrorTPL(
				'401 Unauthorized',
				'Authorization Required',
				'Login Failed. Either you supplied the wrong credentials or your browser doesn\'t understand how to supply the credentials required.',
				(string) (((string)$logout != '') ? '<script>self.location=\''.Smart::escape_js(self::APPCODEUNPACK_SCRIPT).'\'</script>' : '')
			));
			//--
		} //end if
		//--
	} //END FUNCTION


	private static function jsAjaxReplyToHtmlForm(?string $y_status, ?string $y_title, ?string $y_message) : string {
		//--
		if((string)$y_status !== 'OK') {
			$y_status = 'ERROR';
		} //end if else
		//--
		$out = (string) Smart::json_encode([
			'completed'			=> 'DONE',
			'status'			=> (string) $y_status,
			'title'				=> (string) $y_title,
			'message'			=> (string) $y_message
		]);
		//--
		if(!headers_sent()) {
			if((string)trim((string)$out) != '') {
				http_response_code(202); // for JSON output OK this service must answer 202 (not default 200) to differentiate from other used codes (ex: HTML)
			} else {
				http_response_code(502); // for JSON output ERR this service must answer 502 to differentiate from other used codes (ex: HTML)
			} //end if else
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent ...');
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION


	private static function raiseXXXError(int $errcode, ?string $msg_txt, ?string $extmsg_txt='', ?string $ext_html='') : void {
		//--
		if(!in_array((int)$errcode, [ 400, 401, 403, 404, 429, 500, 502, 503, 504 ])) {
			$errcode = 500;
		} //end if
		//--
		$title = 'Error';
		switch((int)$errcode) {
			case 400:
				$title = 'Bad Request';
				break;
			case 401:
				$title = 'Unauthorized';
				break;
			case 403:
				$title = 'Forbidden';
				break;
			case 404:
				$title = 'Not Found';
				break;
			case 429:
				$title = 'Too Many Requests';
				break;
			case 500:
				$title = 'Internal Server Error';
				break;
			case 502:
				$title = 'Bad Gateway';
				break;
			case 503:
				$title = 'Service Unavailable';
				break;
			case 504:
				$title = 'Gateway Timeout';
				break;
			default:
				$title = 'Unknown Error';
		} //end switch
		//--
		if(!headers_sent()) {
			@http_response_code((int)$errcode);
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent before '.(int)$errcode.' ...');
		} //end if
		die((string)self::renderErrorTPL((string)((int)$errcode).' '.$title, (string)$msg_txt, (string)$extmsg_txt, (string)$ext_html));
		//--
	} //END FUNCTION


	private static function renderErrorTPL(?string $title, ?string $msg_txt, ?string $extmsg_txt='', ?string $ext_html='') : string {
		//--
		if(!defined('APPCODEUNPACK_HTML_ERRTPL')) {
			return '{#EMPTY-APPCODEUNPACK-ERRTPL#}';
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_template(
			(string) APPCODEUNPACK_HTML_ERRTPL,
			[
				'SCRIPT' 				=> (string) self::APPCODEUNPACK_SCRIPT,
				'CHARSET' 				=> (string) (defined('SMART_FRAMEWORK_CHARSET') ? SMART_FRAMEWORK_CHARSET : ''),
				'TITLE' 				=> (string) $title,
				'CSS-BASE-STYLES' 		=> (string) (defined('APPCODEUNPACK_BASE_STYLES') ? APPCODEUNPACK_BASE_STYLES : ''),
				'CSS-NOTIF-STYLES' 		=> (string) (defined('APPCODEUNPACK_NOTIFICATION_STYLES') ? APPCODEUNPACK_NOTIFICATION_STYLES : ''),
				'SIGNATURE-HTML' 		=> (string) '<b>'.self::APPCODEUNPACK_TITLE.'</b><br>'.Smart::escape_html((string)SmartUtils::get_server_current_url(false)),
				'APPCODEUNPACK-SVG' 	=> (string) (defined('APPCODEUNPACK_LOGO_SVG') ? APPCODEUNPACK_LOGO_SVG : ''),
				'MESSAGE-TXT' 			=> (string) $msg_txt,
				'EXTMSG-TXT' 			=> (string) $extmsg_txt,
				'EXT-HTML' 				=> (string) $ext_html,
			]
		);
		//--
	} //END FUNCTION


	private static function renderTPL(?string $title, ?string $main, bool $loader=false) : string {
		//--
		if(!defined('APPCODEUNPACK_HTML_TPL')) {
			return '{#EMPTY-APPCODEUNPACK-TPL#}';
		} //end if
		//--
		$name_prefix = 'AppRelease';
		$name_suffix = 'CodeUnPack';
		$name_all = (string) $name_prefix.'.'.$name_suffix;
		//--
		$out = (string) SmartMarkersTemplating::render_template(
			(string) APPCODEUNPACK_HTML_TPL,
			[
				'REALPATH-CRR' 			=> (string) rtrim((string)Smart::real_path('./'), '/').'/{%-APP-ID-%}/',
				'SCRIPT' 				=> (string) self::APPCODEUNPACK_SCRIPT,
				'AUTH-USER-ID' 			=> (string) SmartAuth::get_auth_id(),
				'AUTH-ENF-HTTPS' 		=> (string) (((!defined('APP_AUTH_ADMIN_ENFORCE_HTTPS')) OR (APP_AUTH_ADMIN_ENFORCE_HTTPS !== false)) ? 'yes' : 'no'),
				'AUTH-IP-LIST' 			=> (string) (defined('SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS') ? SMART_FRAMEWORK_RUNTIME_TASK_ALLOWED_IPS : ''),
				'APP-IDS-LST' 			=> (string) (defined('APPCODEPACK_DEPLOY_APPLIST') ? APPCODEPACK_DEPLOY_APPLIST : ''),
				'CHARSET' 				=> (string) (defined('SMART_FRAMEWORK_CHARSET') ? SMART_FRAMEWORK_CHARSET : ''),
				'TITLE' 				=> (string) self::APPCODEUNPACK_TITLE.($title ? ' :: '.$title : ''),
				'OP-TITLE' 				=> (string) ($title ? $title : self::APPCODEUNPACK_TITLE),
				'CSS-TOOLKIT-STYLES' 	=> (string) (defined('APPCODEUNPACK_TOOLKIT_STYLES') ? APPCODEUNPACK_TOOLKIT_STYLES : ''),
				'CSS-BASE-STYLES' 		=> (string) (defined('APPCODEUNPACK_BASE_STYLES') ? APPCODEUNPACK_BASE_STYLES : ''),
				'CSS-NOTIF-STYLES' 		=> (string) (defined('APPCODEUNPACK_NOTIFICATION_STYLES') ? APPCODEUNPACK_NOTIFICATION_STYLES : ''),
				'CSS-LOCAL-STYLES' 		=> (string) (defined('APPCODEUNPACK_LOCAL_STYLES') ? APPCODEUNPACK_LOCAL_STYLES : ''),
				'JS-JQUERY' 			=> (string) (defined('APPCODEUNPACK_JS_JQUERY') ? APPCODEUNPACK_JS_JQUERY : ''),
				'JS-SMART-UTILS' 		=> (string) (defined('APPCODEUNPACK_JS_SMART_UTILS') ? APPCODEUNPACK_JS_SMART_UTILS : ''),
				'JS-SMART-DATE' 		=> (string) (defined('APPCODEUNPACK_JS_SMART_DATE') ? APPCODEUNPACK_JS_SMART_DATE : ''),
				'JS-SMART-CRYPTO' 		=> (string) (defined('APPCODEUNPACK_JS_SMART_CRYPTO') ? APPCODEUNPACK_JS_SMART_CRYPTO : ''),
				'CSS-GRITTER' 			=> (string) (defined('APPCODEUNPACK_CSS_GRITTER') ? APPCODEUNPACK_CSS_GRITTER : ''),
				'JS-GRITTER' 			=> (string) (defined('APPCODEUNPACK_JS_GRITTER') ? APPCODEUNPACK_JS_GRITTER : ''),
				'CSS-ALERTABLE' 		=> (string) (defined('APPCODEUNPACK_CSS_ALERTABLE') ? APPCODEUNPACK_CSS_ALERTABLE : ''),
				'JS-ALERTABLE' 			=> (string) (defined('APPCODEUNPACK_JS_ALERTABLE') ? APPCODEUNPACK_JS_ALERTABLE : ''),
				'CSS-APPCODEUNPACK' 	=> (string) (defined('APPCODEUNPACK_CSS_LOCAL_FX') ? APPCODEUNPACK_CSS_LOCAL_FX : ''),
				'JS-APPCODEUNPACK' 		=> (string) (defined('APPCODEUNPACK_JS_LOCAL_FX') ? APPCODEUNPACK_JS_LOCAL_FX : ''),
				'JS-WATCH' 				=> (string) (defined('APPCODEUNPACK_JS_WATCH') ? APPCODEUNPACK_JS_WATCH : ''),
				'APPCODEUNPACK-SVG' 	=> (string) (defined('APPCODEUNPACK_LOGO_SVG') ? APPCODEUNPACK_LOGO_SVG : ''),
				'APACHE-SVG' 			=> (string) (defined('APPCODEUNPACK_LOGO_APACHE_SVG') ? APPCODEUNPACK_LOGO_APACHE_SVG : ''),
				'APACHE-VER' 			=> (string) apache_get_version(),
				'PHP-SVG' 				=> (string) (defined('APPCODEUNPACK_LOGO_PHP_SVG') ? APPCODEUNPACK_LOGO_PHP_SVG : ''),
				'PHP-VER' 				=> (string) phpversion(),
				'NETARCH-SVG' 			=> (string) (defined('APPCODEUNPACK_LOGO_NETARCH_SVG') ? APPCODEUNPACK_LOGO_NETARCH_SVG : ''),
				'SF-SVG' 				=> (string) (defined('APPCODEUNPACK_LOGO_SF_SVG') ? APPCODEUNPACK_LOGO_SF_SVG : ''),
				'SF-VER' 				=> (string) (defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '').'-'.(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : ''),
				'LOADING-SVG' 			=> (string) (defined('APPCODEUNPACK_LOADING_SVG') ? APPCODEUNPACK_LOADING_SVG : ''),
				'NAME' 					=> (string) $name_all,
				'NAME-PREFIX' 			=> (string) $name_prefix,
				'NAME-SUFFIX' 			=> (string) $name_suffix,
				'VERSION' 				=> (string) self::APPCODEUNPACK_VERSION.' :: '.(defined('APP_CUSTOM_STANDALONE_RHASH') ? APP_CUSTOM_STANDALONE_RHASH : '').' @ '.(defined('APP_CUSTOM_STANDALONE_DTIME') ? APP_CUSTOM_STANDALONE_DTIME : ''),
				'MAIN' 					=> (string) $main,
				'YEAR' 					=> (string) date('Y'),
				'SHOW-LOADER' 			=> (string) (($loader === true) ? 'yes' : 'no'),
			]
		);
		//--
		if(!headers_sent()) {
			if((string)trim((string)$out) != '') {
				http_response_code(203); // for HTML output OK this service must answer 203 (not default 200) to differentiate from other used codes (ex: JSON)
			} else {
				http_response_code(504); // for HTML output ERR this service must answer 504 to differentiate from other used codes (ex: JSON)
			} //end if else
		} else {
			Smart::log_warning(__METHOD__.' # Headers Already Sent ...');
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION


	private static function outputSafeHttpHeader(?string $value) : void {
		//--
		$original_value = (string) $value;
		//--
		$value = (string) SmartFrameworkSecurity::PrepareSafeHeaderValue((string)$value);
		//--
		if((string)$value == '') {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Trying to set an empty header (after filtering the value) with original value of: '.$original_value, E_USER_WARNING);
			return;
		} //end if
		//--
		if(headers_sent()) {
			trigger_error(__CLASS__.'::'.__FUNCTION__.'() # '.'Headers Already Sent while trying to set a header with value of: '.$value, E_USER_WARNING);
			return;
		} //end if
		header((string)$value);
		//--
	} //END FUNCTION


	private static function runUpgradeScript(string $restoreroot, string $the_last_unpack_logfile) : string {
		//--
		// RUN THE AFTER DEPLOYMENT UPGRADE SCRIPT
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$restoreroot)) {
			return 'Invalid App Root Path: '.$restoreroot;
		} //end if
		$path_to_upgrade_script = (string) SmartFileSysUtils::addPathTrailingSlash((string)$restoreroot).'appcode-upgrade.php';
		if(!SmartFileSysUtils::checkIfSafePath((string)$path_to_upgrade_script)) {
			return 'Invalid Upgrade Script Path: '.$path_to_upgrade_script;
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$the_last_unpack_logfile)) {
			return 'Invalid Upgrade Log Path: '.$the_last_unpack_logfile;
		} //end if
		if(!SmartFileSystem::is_type_file((string)$the_last_unpack_logfile)) {
			return 'The Upgrade Log Path cannot be found: '.$the_last_unpack_logfile;
		} //end if
		//--
		if(SmartFileSystem::is_type_file((string)$path_to_upgrade_script)) {
			//--
			$upgr_lint_test = (string) php_strip_whitespace((string)$path_to_upgrade_script);
			//--
			if((string)$upgr_lint_test != '') {
				//--
				SmartFileSystem::write((string)$the_last_unpack_logfile, "\n\n".'##### UPGRADE: Running '.$path_to_upgrade_script.' ...', 'a'); // apend to log
				//--
				try {
					AppCodeUnpackIncludeUpgradeScript((string)$path_to_upgrade_script); // if not OK must THROW ERROR
					SmartFileSystem::write((string)$the_last_unpack_logfile, "\n\n".'### '.$path_to_upgrade_script.' [OK]', 'a'); // apend to log
				} catch (Exception $e) {
					$the_upgr_err = (string) $e->getMessage();
					SmartFileSystem::write((string)$the_last_unpack_logfile, "\n\n".'### '.$path_to_upgrade_script.' [ERRORS]: '.$the_upgr_err, 'a'); // apend to log
					return 'Running the UPGRADE PHP script generated some errors: '.$the_upgr_err;
				} //end try catch
				//--
			} else {
				//--
				SmartFileSystem::write((string)$the_last_unpack_logfile, "\n\n".'##### UPGRADE ERROR: Failed to Run '.$path_to_upgrade_script.' (appear to have some errors) ...', 'a'); // apend to log
				//--
			} //end if else
			//--
			SmartFileSystem::delete((string)$path_to_upgrade_script);
			if(SmartFileSystem::path_exists((string)$path_to_upgrade_script)) {
				return 'The APPCODE UPGRADE PHP script could not be removed ...';
			} //end if
			//--
			SmartFileSystem::write((string)$the_last_unpack_logfile, "\n\n".'##### UPGRADE: Removing the '.$path_to_upgrade_script.' ...', 'a'); // apend to log
			//--
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


	public static function releaseMaintenanceFile() : string { // MUST BE PUBLIC AS THE APPCODE UPGRADE SCRIPT CAN CALL THIS
		//--
		// RELEASE THE MAINTENANCE FILE AFTER UNPACK + UPGRADE
		//--
		if(!defined('APPCODEPACK_APP_ID')) {
			return 'APPCODEPACK_APP_ID is not defined';
		} //end if
		if(((string)trim((string)APPCODEPACK_APP_ID) == '') OR (!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID))) {
			return 'Invalid AppID Path: '.APPCODEPACK_APP_ID;
		} //end if
		//--
		$restoreroot = (string) Smart::safe_filename((string)APPCODEPACK_APP_ID);
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$restoreroot)) {
			return 'Invalid App Root Path: '.$restoreroot;
		} //end if
		$path_to_maintenance_file = (string) SmartFileSysUtils::addPathTrailingSlash((string)$restoreroot).'maintenance.html';
		if(!SmartFileSysUtils::checkIfSafePath((string)$path_to_maintenance_file)) {
			return 'Invalid Maintenance File Path: '.$path_to_maintenance_file;
		} //end if
		$path_to_maintenance_503_file = (string) SmartFileSysUtils::addPathTrailingSlash((string)$restoreroot).'maintenance-503.html';
		if(!SmartFileSysUtils::checkIfSafePath((string)$path_to_maintenance_503_file)) {
			return 'Invalid Maintenance 503 File Path: '.$path_to_maintenance_503_file;
		} //end if
		//--
		if(SmartFileSystem::is_type_file((string)$path_to_maintenance_file)) {
			//--
			SmartFileSystem::rename((string)$path_to_maintenance_file, (string)$path_to_maintenance_503_file, true);
			if(SmartFileSystem::is_type_file((string)$path_to_maintenance_file)) {
				return 'Maintenance File could not be renamed to Maintenance 503 File Path: '.$path_to_maintenance_file.' # '.$path_to_maintenance_503_file;
			} //end if
			//--
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


} //END CLASS


//======= APP

echo (string) AppCodeUnpack::runApp(); // using HTTP 200 is risky with such a simplistic service (standalone, not a framework) because 200 is the default status code in any case if not set else !

//=======


// end of php code
