<?php
// [@[#[!NO-STRIP!]#]@]
// AppCodePack Upgrade Script
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APPCODEUNPACK_READY')) { // this must be defined in the first line of the application
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('APPCODEPACK_APP_ID')) { // this must be defined in the first line of the application
	die('Invalid App Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//=====
// AppCodePack Upgrade Script, v.20250207
//=====

//--
// IMPORTANT:
// 		* Below this line, this script should not die() but only throw catcheable exceptions of the Exception object to avoid terminate the parent script (appcodeunpack.php) prematurely ...
// 		* This script should not output anything
// 		* When running this script if no exceptions are throw, is considered SUCCESS
//--

//--
$server_id = (string) AppCodePackUpgrade::GetWebsiteInstanceId();
if((string)trim((string)$server_id) == '') {
	throw new Exception('Run Test Website ID: FAILED to find a valid website ID for this release.');
} //end if else
//--

//-- remove a file after release (sample)
AppCodePackUpgrade::RemoveAppFile('tmp/logs/index.html');
//-- remove a dir after release (sample)
AppCodePackUpgrade::RemoveAppDir('tmp/cache/');
AppCodePackUpgrade::CreateAppDir('tmp/cache/', true);
//--

//-- release the maintenance.html file (may need to release maintenance mode before runing commands that may hit via http, not the case here but this is a sample)
AppCodePackUpgrade::ReleaseMaintenanceFile(); // throws if unsuccessful
//--

//-- run a sample command after deployment
AppCodePackUpgrade::RunCmd('date'); // throws if unsuccessful
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


final class AppCodePackUpgrade {

	// ::
	// v.20221219


	public static function GetVisitorIpAddr() : string {
		//--
		return (string) SmartUtils::get_ip_client();
		//--
	} //END FUNCTION


	public static function GetWebsiteInstanceId() : string {
		//--
		if((!defined('APPCODEPACK_APP_ID')) OR ((string)APPCODEPACK_APP_ID == '')) {
			throw new Exception(__METHOD__.'() # Empty or Undefined APPCODEPACK_APP_ID');
			return '';
		} //end if
		//--
		if(!preg_match('/^[_a-z0-9\-\.]+$/', (string)APPCODEPACK_APP_ID)) { // regex namespace
			throw new Exception(__METHOD__.'() # Invalid APPCODEPACK_APP_ID');
			return '';
		} //end if
		//--
		return (string) APPCODEPACK_APP_ID;
		//--
	} //END FUNCTION


	public static function IsValidWebsiteInstanceId() : bool {
		//--
		$appid = '';
		try {
			$appid = (string) self::GetWebsiteInstanceId();
		} catch(Exception $e) {
			$appid = '';
		} //end if
		//--
		return (bool) ($appid ? true : false);
		//--
	} //END FUNCTION


	// run a command and if not successful throw error
	// returns: array ; Throws Error if not successful
	public static function RunCmd(?string $cmd) : array {
		//--
		$parr = (array) SmartUtils::run_proc_cmd(
			(string) $cmd,
			null,
			null,
			null
		);
		$exitcode = $parr['exitcode']; // don't make it INT !!!
		$stdout = (string) $parr['stdout'];
		$stderr = (string) $parr['stderr'];
		//--
		if(($exitcode !== 0) OR ((string)$stderr != '')) { // exitcode is zero (0) on success and no stderror
			throw new Exception(__METHOD__.'() # FAILED to run command ['.$cmd.'] # ExitCode: '.$exitcode.' ; Errors: '.$stderr);
		} //end if
		//--
		return (array) $parr;
		//--
	} //END FUNCTION


	// clear the maintenance.html file (may be needed if need to run a command after maintenance has been disabled ...)
	// returns: true/false ; Throws Error if not successful
	public static function ReleaseMaintenanceFile() : bool {
		//--
		$test = (string) AppCodeUnpack::releaseMaintenanceFile();
		if((string)$test != '') {
			throw new Exception(__METHOD__.'() # '.$test);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	// remove a file inside app base folder (may be needed for some upgrades to remove temporary task files ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if not successful
	public static function RemoveAppFile(?string $file_path) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$file_path = (string) Smart::safe_pathname((string)$file_path);
		if((string)$file_path == '') {
			throw new Exception(__METHOD__.'() # Empty FilePath');
			return false;
		} //end if
		//--
		$file_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$file_path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$file_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$file_app_path);
			return false;
		} //end if
		//--
		if(SmartFileSystem::is_type_file((string)$file_app_path)) { // this scripts runs in the parent of {app-id}/
			SmartFileSystem::delete((string)$file_app_path);
		} //end if
		if(SmartFileSystem::path_exists((string)$file_app_path)) {
			throw new Exception(__METHOD__.'() # FAILED to remove the file: '.(string)$file_app_path);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	// remove a folder inside app base folder (may be needed for some upgrades to remove temporary dirs ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if not successful
	public static function RemoveAppDir(?string $dir_path) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$dir_path = (string) Smart::safe_pathname((string)$dir_path);
		if((string)$dir_path == '') {
			throw new Exception(__METHOD__.'() # Empty DirPath');
			return false;
		} //end if
		//--
		$dir_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$dir_path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$dir_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$dir_app_path);
			return false;
		} //end if
		//--
		if(SmartFileSystem::is_type_dir((string)$dir_app_path)) { // this scripts runs in the parent of {app-id}/
			SmartFileSystem::dir_delete((string)$dir_app_path);
		} //end if
		if(SmartFileSystem::path_exists((string)$dir_app_path)) {
			throw new Exception(__METHOD__.'() # FAILED to remove the dir: '.(string)$dir_app_path);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	// create a folder inside app base folder (may be needed for some upgrades to create temporary dirs ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if not successful
	public static function CreateAppDir(?string $dir_path, bool $recursive=false) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$dir_path = (string) Smart::safe_pathname((string)$dir_path);
		if((string)$dir_path == '') {
			throw new Exception(__METHOD__.'() # Empty DirPath');
			return false;
		} //end if
		//--
		$dir_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$dir_path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$dir_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$dir_app_path);
			return false;
		} //end if
		//--
		if(!SmartFileSystem::dir_create((string)$dir_app_path, (bool)$recursive)) {
			throw new Exception(__METHOD__.'() # Failed to Create Dir: '.$dir_app_path);
			return false;
		} //end if
		if(!SmartFileSystem::is_type_dir((string)$dir_app_path)) {
			throw new Exception(__METHOD__.'() # Cannot Find the Created Dir: '.$dir_app_path);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	// test if a folder exists inside app base folder (may be needed for some upgrades to test temporary dirs ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if any issue
	public static function ExistsAppDir(?string $dir_path) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$dir_path = (string) Smart::safe_pathname((string)$dir_path);
		if((string)$dir_path == '') {
			throw new Exception(__METHOD__.'() # Empty DirPath');
			return false;
		} //end if
		//--
		$dir_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$dir_path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$dir_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$dir_app_path);
			return false;
		} //end if
		//--
		return (bool) SmartFileSystem::is_type_dir((string)$dir_app_path);
		//--
	} //END FUNCTION


	// test if a path exists inside app base folder (may be needed for some upgrades to test temporary paths ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if any issue
	public static function ExistsAppPath(?string $path) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$path = (string) Smart::safe_pathname((string)$path);
		if((string)$path == '') {
			throw new Exception(__METHOD__.'() # Empty Path');
			return false;
		} //end if
		//--
		$the_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$the_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$the_app_path);
			return false;
		} //end if
		//--
		return (bool) SmartFileSystem::path_exists((string)$the_app_path);
		//--
	} //END FUNCTION


	// test if a file exists inside app base folder (may be needed for some upgrades to test temporary files ...)
	// works with protected paths because operates from outside app dir thus paths will not start with #
	// returns: true/false ; Throws Error if any issue
	public static function ExistsAppFile(?string $file_path) : bool {
		//--
		if(!self::IsValidWebsiteInstanceId()) {
			throw new Exception(__METHOD__.'() # Empty or Invalid APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$file_path = (string) Smart::safe_pathname((string)$file_path);
		if((string)$file_path == '') {
			throw new Exception(__METHOD__.'() # Empty FilePath');
			return false;
		} //end if
		//--
		$file_app_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)APPCODEPACK_APP_ID).$file_path;
		if(!SmartFileSysUtils::checkIfSafePath((string)$file_app_path)) {
			throw new Exception(__METHOD__.'() # Unsafe Path: '.$file_app_path);
			return false;
		} //end if
		//--
		return (bool) SmartFileSystem::is_type_file((string)$file_app_path);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
