<?php
// [@[#[!NO-STRIP!]#]@]
// AppCodePack Upgrade Script
// (c) 2006-2021 unix-world.org - all rights reserved

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
// AppCodePack Upgrade Script, v.20210525
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
	// v.20210525


	public static function GetWebsiteInstanceId() {
		//--
		if((string)APPCODEPACK_APP_ID == '') {
			throw new Exception(__METHOD__.'() # Empty APPCODEPACK_APP_ID');
			return '';
		} //end if
		//--
		return (string) APPCODEPACK_APP_ID;
		//--
	} //END FUNCTION


	// run a command and if not successful throw error
	// returns: - ; Throws Error if not successful
	public static function RunCmd($cmd) {
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
		if(($exitcode !== 0) OR ((string)$stderr != '')) { // exitcode is zero (0) on success and no stderror
			throw new Exception(__METHOD__.'() # FAILED to run command ['.$cmd.'] # ExitCode: '.$exitcode.' ; Errors: '.$stderr);
			return (array) $parr;
		} //end if
		//--
		return (array) $parr;
		//--
	} //END FUNCTION


	// clear the maintenance.html file (may be needed if need to run a command after maintenance has been disabled ...)
	// returns: - ; Throws Error if not successful
	public static function ReleaseMaintenanceFile() {
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
	// returns: - ; Throws Error if not successful
	public static function RemoveAppFile($file_path) {
		//--
		if((string)APPCODEPACK_APP_ID == '') {
			throw new Exception(__METHOD__.'() # Empty APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_file_or_dir_name((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$file_path = Smart::safe_pathname((string)$file_path);
		if((string)$file_path == '') {
			throw new Exception(__METHOD__.'() # Empty FilePath');
			return false;
		} //end if
		//--
		$file_app_path = (string) SmartFileSysUtils::add_dir_last_slash((string)APPCODEPACK_APP_ID).$file_path;
		if(!SmartFileSysUtils::check_if_safe_path((string)$file_app_path)) {
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
	// returns: - ; Throws Error if not successful
	public static function RemoveAppDir($dir_path) {
		//--
		if((string)APPCODEPACK_APP_ID == '') {
			throw new Exception(__METHOD__.'() # Empty APPCODEPACK_APP_ID');
			return false;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_file_or_dir_name((string)APPCODEPACK_APP_ID)) {
			throw new Exception(__METHOD__.'() # Unsafe APPCODEPACK_APP_ID: '.APPCODEPACK_APP_ID);
			return false;
		} //end if
		//--
		$dir_path = Smart::safe_pathname((string)$dir_path);
		if((string)$dir_path == '') {
			throw new Exception(__METHOD__.'() # Empty DirPath');
			return false;
		} //end if
		//--
		$dir_app_path = (string) SmartFileSysUtils::add_dir_last_slash((string)APPCODEPACK_APP_ID).$dir_path;
		if(!SmartFileSysUtils::check_if_safe_path((string)$dir_app_path)) {
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


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
