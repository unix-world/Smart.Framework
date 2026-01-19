<?php
// [LIB - Smart.Framework / FileSystem]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - FileSystem Utils
// {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}} :: the constant SMART_FRAMEWORK_RUNTIME_MODE can change the behaviour of this library ; if set to 'web.task' will override the allow access to protected folders and will always allow ...
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//--
if(!defined('SMART_FRAMEWORK_HTACCESS_NOEXECUTION')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_NOEXECUTION');
} //end if
if(!defined('SMART_FRAMEWORK_HTACCESS_FORBIDDEN')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_FORBIDDEN');
} //end if
if(!defined('SMART_FRAMEWORK_HTACCESS_NOINDEXING')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_HTACCESS_NOINDEXING');
} //end if
//--


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartFileSystem - provides the File System Access functions.
 *
 * This class enforces the use of RELATIVE PATHS to force using correct path access in a web environment application.
 * Relative paths must be relative to the web application folder as folder: `some-folder/` or file: `some-folder/my-file.txt`.
 * Absolute paths are denied by internal checks as they are NOT SAFE in a Web Application from the security point of view ...
 * Also the backward path access like `../some-file-or-folder` is denied from the above exposed reasons.
 * Files and Folders must contain ONLY safe characters as: `[a-z] [A-Z] [0-9] _ - . @ #` ; folders can also contain slashes `/` (as path separators); no spaces are allowed in paths !!
 * All operations in this class are safe against TOC/TOU (time-of-check to time-of-use) expoits
 *
 * <code>
 * // Usage example:
 * SmartFileSystem::some_method_of_this_class(...);
 * </code>
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 * @hints 		This class can handle thread concurency to the filesystem in a safe way by using the LOCK_EX (lock exclusive) feature on each file written / appended thus making also reads to be mostly safe ; Reads can also use optional shared locking if needed
 *
 * @depends 	classes: Smart, SmartEnvironment ; constants: SMART_FRAMEWORK_CHMOD_DIRS, SMART_FRAMEWORK_CHMOD_FILES
 * @version 	v.20260118
 * @package 	Application:FileSystem
 *
 */
final class SmartFileSystem {

	// ::


	//================================================================
	/**
	 * Fix the Directory CHMOD as defined in SMART_FRAMEWORK_CHMOD_DIRS.
	 * This provides a safe way to fix chmod on directories (symlinks or files will be skipped) ...
	 *
	 * @param 	STRING 	$dir_name 					:: The relative path to the directory name to fix chmod for (folder)
	 *
	 * @return 	BOOLEAN								:: TRUE if success, FALSE if not
	 */
	public static function fix_dir_chmod(?string $dir_name) : bool {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			Smart::log_warning(__METHOD__.' # Skip: A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return false;
		} //end if
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)trim((string)$dir_name) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty DirName');
			return false;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name, true, true); // deny absolute paths
		//--
		if(!self::is_type_dir($dir_name)) { // not a dir
			Smart::log_warning(__METHOD__.' # Skip: Not a Directory Type: '.$dir_name);
			return false;
		} //end if
		if(self::is_type_link($dir_name)) { // skip links !!
			return true;
		} //end if
		//--
		$chmod = (bool) @chmod((string)$dir_name, SMART_FRAMEWORK_CHMOD_DIRS); // SMART_APP_FS_ROOT
		if(!$chmod) {
			Smart::log_warning(__METHOD__.' # Failed to CHMOD ('.SMART_FRAMEWORK_CHMOD_DIRS.') a Directory: '.$dir_name);
		} //end if
		//--
		return (bool) $chmod;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Fix the File CHMOD as defined in SMART_FRAMEWORK_CHMOD_FILES.
	 * This provides a safe way to fix chmod on files (symlinks or dirs will be skipped) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to fix chmod for (file)
	 *
	 * @return 	BOOLEAN								:: TRUE if success, FALSE if not
	 */
	public static function fix_file_chmod(?string $file_name) : bool {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_FILES')) {
			Smart::log_warning(__METHOD__.' # Skip: A required constant (SMART_FRAMEWORK_CHMOD_FILES) has not been defined ...');
			return false;
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)trim((string)$file_name)) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty FileName');
			return false;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, true); // deny absolute paths
		//--
		if(!self::is_type_file((string)$file_name)) { // not a file
			Smart::log_warning(__METHOD__.' # Skip: Not a File Type: '.$file_name);
			return false;
		} //end if
		if(self::is_type_link((string)$file_name)) { // skip links !!
			return true;
		} //end if
		//--
		$chmod = (bool) @chmod((string)$file_name, SMART_FRAMEWORK_CHMOD_FILES); // SMART_APP_FS_ROOT
		if(!$chmod) {
			Smart::log_warning(__METHOD__.' # Failed to CHMOD ('.SMART_FRAMEWORK_CHMOD_FILES.') a File: '.$file_name);
		} //end if
		//--
		return (bool) $chmod;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File Size in Bytes. If invalid file or not file or broken link will return 0 (zero).
	 * This provides a safe way to get the file size (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the size for (file or symlink)
	 *
	 * @return 	INTEGER								:: 0 (zero) if file does not exists or invalid file type ; the file size in bytes for the rest of cases
	 */
	public static function get_file_size(?string $file_name) : int {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists((string)$file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filesize((string)$file_name); // SMART_APP_FS_ROOT ; should return INTEGER as some comparisons may fail if casted type
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File Creation Timestamp. If invalid file or not file or broken link will return 0 (zero).
	 * This provides a safe way to get the file creation timestamp (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the creation timestamp for (file or symlink)
	 *
	 * @return 	INTEGER								:: 0 (zero) if file does not exists or invalid file type ; the file creation timestamp for the rest of cases
	 */
	public static function get_file_ctime(?string $file_name) : int {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists((string)$file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filectime((string)$file_name); // SMART_APP_FS_ROOT ; should return INTEGER as some comparisons may fail if casted type
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File Modification Timestamp. If invalid file or not file or broken link will return 0 (zero).
	 * This provides a safe way to get the file modification timestamp (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the last modification timestamp for (file or symlink)
	 *
	 * @return 	INTEGER								:: 0 (zero) if file does not exists or invalid file type ; the file modification timestamp for the rest of cases
	 */
	public static function get_file_mtime(?string $file_name) : int {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists((string)$file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filemtime((string)$file_name); // SMART_APP_FS_ROOT ; should return INTEGER as some comparisons may fail if casted type
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * GET the File MD5 Checksum. If invalid file or not file or broken link will return empty string.
	 * This provides a safe way to get the md5_file checksum (works also with symlinks) ...
	 *
	 * @param 	STRING 	$file_name 					:: The relative path to the file name to get the last modification timestamp for (file or symlink)
	 *
	 * @return 	STRING								:: empty string if file does not exists or invalid file type ; the file md5 checksum for the rest of cases
	 */
	public static function get_file_md5_checksum(?string $file_name) : string {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty FileName');
			return '';
		} //end if
		if(!self::path_real_exists((string)$file_name)) {
			return '';
		} //end if
		//--
		return (string) @md5_file((string)$file_name); // SMART_APP_FS_ROOT ; should return STRING
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is a directory (folder) type and exists.
	 * This provides a safe way to check if a path is directory (folder) (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if directory (folder), FALSE if not
	 */
	public static function is_type_dir(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_dir((string)$path); // SMART_APP_FS_ROOT
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is a file type and exists.
	 * This provides a safe way to check if a path is file (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if file, FALSE if not
	 */
	public static function is_type_file(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_file((string)$path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file is a symlink and exists. Will not check if symlink is broken (not check if symlink origin exists)
	 * This provides a safe way to check if a path is symlink (may be broken or not) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if symlink, FALSE if not
	 */
	public static function is_type_link(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_link((string)$path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file exists and is readable (includding if a symlink).
	 * This provides a safe way to check if a path is readable ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if readable, FALSE if not
	 */
	public static function have_access_read(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_readable((string)$path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path directory or file exists and is writable (includding if a symlink).
	 * This provides a safe way to check if a path is writable ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if writable, FALSE if not
	 */
	public static function have_access_write(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_writable((string)$path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path is an executable file.
	 * This provides a safe way to check if a file path is executable (works also with symlinks) ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if file, FALSE if not
	 */
	public static function have_access_executable(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) is_executable((string)$path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path exists (includding if a symlink or a broken symlink).
	 * This provides a safe way to check if a path exists because using only PHP file_exists() will return false if the path is a broken symlink ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if exists, FALSE if not
	 */
	public static function path_exists(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		if((file_exists((string)$path)) OR (is_link((string)$path))) { // {{{SYNC-SF-PATH-EXISTS}}}
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * CHECK if a path real exists (excluding if a symlink or a broken symlink).
	 * This provides a way to check if a path exists but only if take in consideration that the path may be a broken symlink that will return false if checked
	 * For normal checking if a path exists use SmartFileSystem::path_exists().
	 * Use this in special cases where you need to check if a path that may be a broken link ...
	 *
	 * @param 	STRING 	$path 						:: The relative path name to be checked (file or dir or symlink)
	 *
	 * @return 	BOOLEAN								:: TRUE if exists, FALSE if not
	 */
	// will return TRUE if file or dir exists ; if a symlink will return TRUE just if the symlink is not broken (it's target exists)
	public static function path_real_exists(?string $path) : bool {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.' # Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, (string)$path);
		//--
		return (bool) file_exists((string)$path); // checks if a file or directory exists (but this is not safe with symlinks as if a symlink is broken will return false ...)
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ READ FILES
	/**
	 * Safe READ A FILE contents. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can read the full file content or just a part, starting from the zero offset (ex: first 100 bytes only)
	 * IT CANNOT BE USED TO ACCESS TEMPORARY UPLOAD FILES WHICH ARE ALWAYS ABSOLUTE PATHS. To access uploaded files use the method SmartFileSystem::read_uploaded()
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be read (can be a symlink to a file)
	 * @param 	INTEGER+ 	$file_len 				:: DEFAULT is 0 (zero) ; If zero will read the entire file ; If > 0 (ex: 100) will read only the first 100 bytes fro the file or less if the file size is under 100 bytes
	 * @param 	YES/NO 		$markchmod 				:: DEFAULT is 'no' ; If 'yes' will force a chmod (as defined in SMART_FRAMEWORK_CHMOD_FILES) on the file before trying to read to ensure consistent chmod on all accesible files.
	 * @param 	YES/NO 		$safelock 				:: DEFAULT is 'no' ; If 'yes' will try to get a read shared lock on file prior to read ; If cannot lock the file will return empty string to avoid partial content read where reading a file that have intensive writes (there is always a risk to cannot achieve the lock ... there is no perfect scenario for intensive file operations in multi threaded environments ...)
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	STRING								:: The file contents (or a part of file contents if $file_len parameter is used) ; if the file does not exists will return an empty string
	 */
	public static function read(?string $file_name, ?int $file_len=0, ?string $markchmod='no', ?string $safelock='no', bool $allow_protected_paths=false, bool $dont_read_if_overSized=false) : string {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		$file_len = (int) $file_len;
		$markchmod = (string) $markchmod; // no/yes
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # Empty File Path');
			return '';
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, (string)$file_name);
		//--
		$fcontent = '';
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$file_name, true, (bool)$allow_protected_paths)) { // deny absolute paths
			//--
			if(!self::is_type_dir((string)$file_name)) {
				//--
				if(self::is_type_file((string)$file_name)) {
					//--
					if((string)$markchmod == 'yes') {
						self::fix_file_chmod((string)$file_name); // force chmod
					} elseif(!self::have_access_read((string)$file_name)) {
						self::fix_file_chmod((string)$file_name); // try to make ir readable by applying chmod
					} //end if
					//--
					if(!self::have_access_read((string)$file_name)) {
						Smart::log_warning(__METHOD__.' # A file is not readable: '.$file_name);
						return '';
					} //end if
					//--
					if($dont_read_if_overSized === true) { // {{{SYNC-DONT-READ-FILE-IF-SPECIFIC-LEN-AND-OVERSIZED}}}
						if((int)filesize((string)$file_name) > (int)$file_len) { // if this param is set to TRUE even if the max length was not specified and that is zero stop here !
							return '';
						} //end if
					} //end if
					//-- fix for read file locking when using file_get_contents() # https://stackoverflow.com/questions/49262971/does-phps-file-get-contents-ignore-file-locking
					// USE LOCKING ON READS, only if specified so:
					//	* because there is a risk a file remain locked if a process crashes, there are a lot of reads in every execution, but few writes
					//	* on systems where locking is mandatory and not advisory this is expensive from resources point of view
					//	* if a process have to wait until obtain a lock ... is not what we want in web environment ...
					//	* neither the LOCK_NB does not resolv this issue, what we do if not locked ? return empty file contents instead of partial ? ... actually this is how it works also without locking ... tricky ... :-)
					//	* without a lock there is a little risk to get empty file (a partial file just on Windows), but that risk cannot be avoid, there is no perfect solution in multi-threaded environments with file read/writes concurrency ... use an sqlite or dba if having many writes and reads on the same file and care of data integrity
					//	* anyway, hoping for the best file_get_contents() should be atomic and if writes are made with atomic and LOCK_EX file_put_contents() everything should be fine, in any scenario there is a compromise
					if((string)$safelock === 'yes') {
						$lock = @fopen((string)$file_name, 'rb');
						if($lock) {
							$is_locked = @flock($lock, LOCK_SH);
						} //end if
					} else {
						$is_locked = true; // non-required
					} //end if
					//--
					if($is_locked !== true) {
						$fcontent = '';
					} else {
						if((int)$file_len > 0) {
							$tmp_file_len = Smart::format_number_int(self::get_file_size((string)$file_name), '+');
							if((int)$file_len > (int)$tmp_file_len) {
								$file_len = (int) $tmp_file_len; // cannot be more than file length
							} //end if
							$fcontent = @file_get_contents(
								(string) $file_name,
								false, // don't use include path
								null, // context resource
								0, // start from begining (negative offsets still don't work)
								(int) $file_len // max length to read ; if zero, read the entire file
							);
						} else {
							$file_len = 0; // can't be negative (by mistake) ; if zero reads the entire file
							$fcontent = @file_get_contents(
								(string) $file_name,
								false, // don't use include path
								null, // context resource
								0 // start from begining (negative offsets still don't work)
								// max length to read ; don't use this parameter here ...
							);
						} //end if else
					} //end if else
					//--
					if((string)$safelock === 'yes') {
						if($lock) {
							if($is_locked) {
								@flock($lock, LOCK_UN);
							} //end if
							@fclose($lock); // will release any lock even if not unlocked by flock LOCK_UN
						} //end if
					} //end if
					//-- #fix for locking
					if($fcontent === false) { // check
						Smart::log_warning(__METHOD__.' # Failed to read the file: '.$file_name);
						$fcontent = '';
					} //end if
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.' # Invalid FileName to read: '.$file_name);
			//--
		} //end if
		//--
		return (string) $fcontent;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CREATE AND WRITE FILES
	/**
	 * Safe CREATE AND/OR WRITE/APPEND CONTENTS TO A FILE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can create a new file or overwrite an existing file.
	 * It also can to write append to a file.
	 * The file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be written (can be an existing symlink to a file)
	 * @param 	STRING 		$file_content 			:: DEFAULT is '' ; The content string to be written to the file (binary safe)
	 * @param 	ENUM 		$write_mode 			:: DEFAULT is 'w' Write (If file exists then overwrite. If the file does not exist create it) ; If 'a' will use Write-Append by appending the content to a file which can exists or not.
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write(?string $file_name, ?string $file_content='', ?string $write_mode='w', bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # Empty File Name');
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, (string)$file_name);
		//--
		$result = false;
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$file_name, true, (bool)$allow_protected_paths)) { // deny absolute paths
			//--
			if(!self::is_type_dir((string)$file_name)) {
				//--
				if(self::is_type_link((string)$file_name)) {
					if(!self::path_real_exists((string)$file_name)) {
						self::delete((string)$file_name, (bool)$allow_protected_paths); // delete the link if broken
					} //end if
				} //end if
				//--
				if(self::is_type_file((string)$file_name)) {
					if(!self::have_access_write((string)$file_name)) {
						self::fix_file_chmod((string)$file_name); // apply chmod first to be sure file is writable
					} //end if
					if(!self::have_access_write((string)$file_name)) {
						Smart::log_warning(__METHOD__.' # A file is not writable: '.$file_name);
						return 0;
					} //end if
				} //end if
				//-- fopen/fwrite method lacks the real locking which can be achieved just with flock which is not as safe as doing at once with: file_put_contents
				if((string)$write_mode == 'a') { // a (append, binary safe)
					$result = @file_put_contents((string)$file_name, (string)$file_content, FILE_APPEND | LOCK_EX);
				} else { // w (write, binary safe)
					$result = @file_put_contents((string)$file_name, (string)$file_content, LOCK_EX);
				} //end if else
				//--
				if(self::is_type_file((string)$file_name)) {
					self::fix_file_chmod((string)$file_name); // apply chmod afer write (fix as the file create chmod may be different !!)
					if(!self::have_access_write((string)$file_name)) {
						Smart::log_warning(__METHOD__.' # A file is not writable: '.$file_name);
					} //end if
				} //end if
				//-- check the write result (number of bytes written)
				if($result === false) {
					Smart::log_warning(__METHOD__.' # Failed to write a file: '.$file_name);
				} else {
					if($result !== @strlen((string)$file_content)) {
						Smart::log_warning(__METHOD__.' # A file was not completely written (removing it ...): '.$file_name);
						@unlink((string)$file_name); // delete the file, was not completely written (do not use self::delete here, the file is still locked !)
					} //end if
				} //end if
				//--
			} else {
				//--
				Smart::log_warning(__METHOD__.' # Failing to write file as this is a type Directory: '.$file_name);
				//--
			} //end if else
			//--
		} //end if else
		//--
		if($result === false) { // file was not written
			$out = 0;
		} else { // result can be zero or a positive number of bytes written
			$out = 1;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ WRITE IF NOT EXISTS OR CONTENT DIFFERS
	/**
	 * Safe CREATE OR WRITE TO A FILE IF NOT EXISTS OR CONTENT DIFFERS. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It can only create a new file or overwrite an existing file if the content does not match.
	 * The file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be written (can be an existing symlink to a file)
	 * @param 	STRING 		$file_content 			:: DEFAULT is '' ; The content string to be written to the file (binary safe)
	 * @param 	YES/NO 		$y_chkcompare 			:: DEFAULT is 'no' ; If 'yes' will check the existing fiile contents and will overwrite if different than the passed contents in $file_content
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write_if_not_exists(?string $file_name, ?string $file_content, ?string $y_chkcompare='no', bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_content == '') {
			$y_chkcompare = 'no'; // fix: without this will not write the file !
		} //end if
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # Empty File Name');
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		$x_ok = 0;
		//--
		if((string)$y_chkcompare == 'yes') {
			//--
			if((string)self::read((string)$file_name, 0, 'no', 'no', (bool)$allow_protected_paths) != (string)$file_content) { // compare content
				$x_ok = self::write((string)$file_name, (string)$file_content, 'w', true); // allow protected paths
			} else {
				$x_ok = 1;
			} //end if
			//--
		} else {
			//--
			if(!self::is_type_file((string)$file_name)) {
				$x_ok = self::write((string)$file_name, (string)$file_content, 'w', true); // allow protected paths
			} else {
				$x_ok = 1;
			} //end if else
			//--
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ COPY A FILE TO A DESTINATION
	/**
	 * Safe COPY A FILE TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will copy the file from source location to a destination location (includding across partitions).
	 * The destination file will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be copied (can be a symlink to a file)
	 * @param 	STRING 		$newlocation 			:: The relative path of the destination file (where to copy)
	 * @param 	BOOLEAN 	$overwrite_destination 	:: DEFAULT is FALSE ; If set to FALSE will FAIL if destination file exists ; If set to TRUE will overwrite the file destination if exists
	 * @param 	BOOLEAN 	$check_copy_contents 	:: DEFAULT is TRUE ; If set to TRUE (safe mode) will compare the copied content from the destination file with the original file content using sha1-file checksums ; If set to FALSE (non-safe mode) will not do this comparison check (but may save a big amount of time when working with very large files)
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function copy(?string $file_name, ?string $newlocation, bool $overwrite_destination=false, bool $check_copy_contents=true, bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Empty Source File Name');
			return 0;
		} //end if
		if((string)trim((string)$newlocation) == '') {
			Smart::log_warning(__METHOD__.' # Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.' # The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$newlocation, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, (string)$file_name);
		clearstatcache(true, (string)$newlocation);
		//--
		if((!self::is_type_file((string)$file_name)) OR ((self::is_type_link((string)$file_name)) AND (!self::is_type_file((string)self::link_get_origin((string)$file_name, (bool)$allow_protected_paths))))) {
			Smart::log_warning(__METHOD__.' # Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists((string)$newlocation)) {
				Smart::log_warning(__METHOD__.' # The destination file exists (1): S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		$result = false;
		//--
		if(
			(SmartFileSysUtils::checkIfSafePath((string)$file_name, true, (bool)$allow_protected_paths)) // deny absolute paths
			AND
			(SmartFileSysUtils::checkIfSafePath((string)$newlocation, true, (bool)$allow_protected_paths)) // deny absolute paths
			AND
			(self::is_type_file((string)$file_name))
		) {
			//--
			if(($overwrite_destination === true) OR (!self::path_exists((string)$newlocation))) {
				//--
				$result = @copy((string)$file_name, (string)$newlocation); // if destination exists will overwrite it
				//--
				if(self::is_type_file((string)$newlocation)) {
					//--
					self::fix_file_chmod((string)$newlocation); // apply chmod
					//--
					if(!self::have_access_read((string)$newlocation)) {
						Smart::log_warning(__METHOD__.' # Destination file is not readable: '.$newlocation);
					} //end if
					//--
					if((int)self::get_file_size((string)$file_name) !== (int)self::get_file_size((string)$newlocation)) {
						$result = false; // clear
						self::delete((string)$newlocation, (bool)$allow_protected_paths); // remove incomplete copied file
						Smart::log_warning(__METHOD__.' # Destination file is not same size as original: '.$newlocation);
					} //end if
					//--
					if($check_copy_contents === true) {
						if((string)sha1_file((string)$file_name) !== (string)sha1_file((string)$newlocation)) {
							$result = false; // clear
							self::delete((string)$newlocation, (bool)$allow_protected_paths); // remove broken copied file
							Smart::log_warning(__METHOD__.' # Destination file checksum failed: '.$newlocation);
						} //end if
					} //end if
					//--
				} else {
					//--
					Smart::log_warning(__METHOD__.' # Failed to copy a file: '.$file_name.' // to destination: '.$newlocation);
					//--
				} //end if
				//--
			} else {
				Smart::log_warning(__METHOD__.' # Destination file exists (2): '.$newlocation);
			} //end if
			//--
		} //end if
		//--
		if($result) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ RENAME / MOVE FILES
	/**
	 * Safe RENAME OR MOVE A FILE TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will rename or move the file from source location to a destination location (includding across partitions).
	 * The destination file will NOT be rewritten if exists and the $overwrite_destination is set to FALSE, so in this case
	 * be sure to check and remove the destination if you intend to overwrite it.
	 * If the $overwrite_destination is set to TRUE the $newlocation will be overwritten.
	 * After rename or move the destination will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_FILES.
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be renamed or moved (can be a symlink to a file)
	 * @param 	STRING 		$newlocation 			:: The relative path of the destination file (new file name to rename or a new path where to move)
	 * @param 	BOOL 		$overwrite_destination 	:: DEFAULT is FALSE ; If set to FALSE will FAIL if destination file exists ; If set to TRUE will overwrite the file destination if exists
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function rename(?string $file_name, ?string $newlocation, bool $overwrite_destination=false, bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.' # Empty Source File Name');
			return 0;
		} //end if
		if((string)trim((string)$newlocation) == '') {
			Smart::log_warning(__METHOD__.' # Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.' # The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$newlocation, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, (string)$file_name);
		clearstatcache(true, (string)$newlocation);
		//--
		if((!self::is_type_file((string)$file_name)) OR ((self::is_type_link((string)$file_name)) AND (!self::is_type_file((string)self::link_get_origin((string)$file_name, (bool)$allow_protected_paths))))) {
			Smart::log_warning(__METHOD__.' # Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists((string)$newlocation)) {
				Smart::log_warning(__METHOD__.' # The destination already exists: S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		$f_cx = false;
		//--
		if(
			((string)$file_name != (string)$newlocation)
			AND
			(SmartFileSysUtils::checkIfSafePath((string)$file_name, true, (bool)$allow_protected_paths)) // deny absolute paths
			AND
			(SmartFileSysUtils::checkIfSafePath((string)$newlocation, true, (bool)$allow_protected_paths)) // deny absolute paths
		) {
			//--
			if((self::is_type_file((string)$file_name)) OR ((self::is_type_link((string)$file_name)) AND (self::is_type_file((string)self::link_get_origin((string)$file_name, (bool)$allow_protected_paths))))) { // don't move broken links
				//--
				if(!self::is_type_dir((string)$newlocation)) {
					//--
					self::delete((string)$newlocation, (bool)$allow_protected_paths); // just to be sure
					//--
					if(($overwrite_destination !== true) AND (self::path_exists((string)$newlocation))) {
						//--
						Smart::log_warning(__METHOD__.' # Destination file points to an existing file or link: '.$newlocation);
						//--
					} else {
						//--
						$f_cx = @rename((string)$file_name, (string)$newlocation); // If renaming a file and newname exists, it will be overwritten. If renaming a directory and newname exists, this function will emit a warning.
						//--
						if((self::is_type_file((string)$newlocation)) OR ((self::is_type_link((string)$newlocation)) AND (self::is_type_file((string)self::link_get_origin($newlocation, (bool)$allow_protected_paths))))) {
							if(self::is_type_file((string)$newlocation)) {
								self::fix_file_chmod((string)$newlocation); // apply chmod just if file and not a linked dir
							} //end if
						} else {
							$f_cx = false; // clear
							Smart::log_warning(__METHOD__.' # Failed to rename a file: '.$file_name.' # to destination: '.$newlocation);
						} //end if
						//--
						if(!self::have_access_read($newlocation)) {
							Smart::log_warning(__METHOD__.' # Destination file is not readable: '.$newlocation);
						} //end if
					} //end if
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end if
		//--
		if($f_cx == true) {
			$x_ok = 1;
		} else {
			$x_ok = 0;
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ READ UPLOADED FILES
	/**
	 * Safe READ AN UPLOADED FILE contents. WORKS WITH ABSOLUTE PATHS (Ex: /tmp/path/to/uploaded-file.ext).
	 * INFO: This function is 100% SAFE ON LINUX and UNIX file systems.
	 * WARNING: This function is NOT VERY SAFE TO USE ON WINDOWS file systems (use it on your own risk) because extra checks over the absolute path are not available on windows paths, thus in theory it may lead to insecure path access if crafted paths may result ...
	 * It will read the full file content of the uploaded file.
	 * IT SHOULD BE USED TO ACCESS ONLY TEMPORARY UPLOAD FILES. To read other files use the method SmartFileSystem::read()
	 *
	 * @param 	STRING 		$file_name 				:: The absolute path of the uploaded file to be read
	 *
	 * @return 	STRING								:: The file contents ; if the file does not exists or it is not an uploaded file will return an empty string
	 */
	public static function read_uploaded(?string $file_name) : string {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # Empty Uploaded File Name');
			return '';
		} //end if
		//-- {{{SYNC-FILESYS-UPLD-FILE-CHECKS}}}
		if((string)DIRECTORY_SEPARATOR != '\\') { // if not on Windows (this test will FAIL on Windows ...)
			if(!SmartFileSysUtils::checkIfSafePath((string)$file_name, false)) { // here we do not test against absolute path access because uploaded files always return the absolute path
				Smart::log_warning(__METHOD__.' # The Uploaded File Path is Not Safe: '.$file_name);
				return '';
			} //end if
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, false); // here we do not test against absolute path access because uploaded files always return the absolute path
		} //end if
		//--
		clearstatcache(true, (string)$file_name);
		//--
		$f_cx = '';
		//--
		if(is_uploaded_file((string)$file_name)) {
			//--
			if(!self::is_type_dir((string)$file_name)) {
				//--
				if((self::is_type_file((string)$file_name)) AND (self::have_access_read((string)$file_name))) {
					//--
					$f_cx = (string) @file_get_contents((string)$file_name, false);
					//--
				} else {
					//--
					Smart::log_warning(__METHOD__.' # The file is not readable: '.$file_name);
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.' # Cannot Find the Uploaded File or it is NOT an Uploaded File: '.$file_name);
			//--
		} //end if
		//--
		return (string) $f_cx;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ MOVE UPLOADED FILE
	/**
	 * Safe MOVE AN UPLOADED FILE to a new RELATIVE location. WORKS WITH ABSOLUTE PATH FOR UPLOADED FILE (Ex: /tmp/path/to/uploaded-file.ext) and a RELATIVE PATH FOR THE DESTINATION FILE (Ex: path/to/a/file.ext).
	 * INFO: This function is 100% SAFE ON LINUX and UNIX file systems.
	 * WARNING: This function is NOT VERY SAFE TO USE ON WINDOWS file systems (use it on your own risk) because extra checks over the absolute path are not available on windows paths, thus in theory it may lead to insecure path access if crafted paths may result ...
	 * It will move an uploaded file to a new destination.
	 * The destination file will be rewritten if exists.
	 * IT SHOULD BE USED TO MOVE ONLY TEMPORARY UPLOAD FILES. To move/rename other files use the method SmartFileSystem::rename()
	 *
	 * @param 	STRING 		$file_name 				:: The absolute path of the uploaded file to be moved
	 * @param 	STRING 		$newlocation 			:: The relative path of the destination file (the path where to move)
	 * @param 	BOOLEAN 	$check_moved_contents 	:: If TRUE will compare the TMP File with Destination using SHA1-File
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function move_uploaded(?string $file_name, ?string $newlocation, bool $check_moved_contents=true) : int {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # Empty Uploaded File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			Smart::log_warning(__METHOD__.' # Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.' # The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if(!is_uploaded_file((string)$file_name)) { // double check if uploaded
			Smart::log_warning(__METHOD__.' # Cannot Find the Uploaded File or it is NOT an Uploaded File: '.$file_name);
			return 0;
		} //end if
		//-- {{{SYNC-FILESYS-UPLD-FILE-CHECKS}}}
		if((string)DIRECTORY_SEPARATOR != '\\') { // if not on Windows (this test will FAIL on Windows ...)
			if(!SmartFileSysUtils::checkIfSafePath((string)$file_name, false)) { // here we do not test against absolute path access because uploaded files always return the absolute path
				Smart::log_warning(__METHOD__.' # The Uploaded File Path is Not Safe: '.$file_name);
				return 0;
			} //end if
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, false); // here we do not test against absolute path access because uploaded files always return the absolute path
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$newlocation)) {
			Smart::log_warning(__METHOD__.' # The Destination File Path is Not Safe: '.$file_name);
			return 0;
		} //end if
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$newlocation);
		//--
		clearstatcache(true, (string)$file_name);
		clearstatcache(true, (string)$newlocation);
		//--
		$f_cx = false;
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$newlocation)) {
			//--
			if(!self::is_type_dir((string)$file_name)) {
				//--
				if(!self::is_type_dir((string)$newlocation)) {
					//--
					self::delete((string)$newlocation);
					//--
					if($check_moved_contents === true) {
						$sha_tmp_f = (string) sha1_file((string)$file_name);
					} //end if
					$f_cx = @move_uploaded_file((string)$file_name, (string)$newlocation);
					//--
					if(self::is_type_file((string)$newlocation)) {
						@touch((string)$newlocation, time()); // touch modified time to avoid upload differences in time
						self::fix_file_chmod((string)$newlocation); // apply chmod
						if($check_moved_contents === true) {
							$sha_new_f = (string) sha1_file((string)$newlocation);
							if((string)$sha_tmp_f != (string)$sha_new_f) {
								$f_cx = 0;
								Smart::log_warning(__METHOD__.' # Checksum Failed for: '.$file_name.' # to destination: '.$newlocation);
								self::delete((string)$newlocation);
							} //end if
						} //end if
					} else {
						Smart::log_warning(__METHOD__.' # Failed to move uploaded file: '.$file_name.' # to destination: '.$newlocation);
					} //end if
					//--
					if(!self::have_access_read((string)$newlocation)) {
						Smart::log_warning(__METHOD__.' # Destination file is not readable: '.$newlocation);
					} //end if
					//--
					sleep(1); // stay one second to release a second difference between uploaded files
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end if
		//--
		if($f_cx == true) {
			$x_ok = 1;
		} else {
			$x_ok = 0;
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ DELETE FILES
	/**
	 * Safe DELETE A FILE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/file.ext).
	 * It will delete a file (or a symlink) if exists
	 *
	 * @param 	STRING 		$file_name 				:: The relative path of file to be deleted (can be a symlink to a file)
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function delete(?string $file_name, bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.' # The File Name is Empty !');
			return 0; // empty file name
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$file_name, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, (string)$file_name);
		//--
		if(!self::path_exists((string)$file_name)) {
			//--
			return 1;
			//--
		} //end if
		//--
		if(self::is_type_link((string)$file_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink((string)$file_name);
			//--
			if(($f_cx) AND (!self::is_type_link((string)$file_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$f_cx = false;
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$file_name, true, (bool)$allow_protected_paths)) { // deny absolute paths
			//--
			if((self::is_type_file((string)$file_name)) OR (self::is_type_link((string)$file_name))) {
				//--
				if(self::is_type_file((string)$file_name)) {
					//--
					self::fix_file_chmod((string)$file_name); // apply chmod
					//--
					$f_cx = @unlink((string)$file_name);
					//--
					if(self::path_exists((string)$file_name)) {
						$f_cx = false;
						Smart::log_warning(__METHOD__.' # FAILED to delete this file: '.$file_name);
					} //end if
					//--
				} //end if
				//--
			} elseif(self::is_type_dir((string)$file_name)) {
				//--
				Smart::log_warning(__METHOD__.' # A file was marked for deletion but that is a directory: '.$file_name);
				//--
			} //end if
			//--
		} //end if
		//--
		if($f_cx == true) {
			$x_ok = 1;
		} else {
			$x_ok = 0;
		} //end if
		//--
		return (int) $x_ok;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe GET THE ORIGIN OF A SYMLINK. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/symlink).
	 * It will get the origin path of a symlink if exists (not broken).
	 * WARNING: Use this function carefuly as it may return an absolute path or a non-safe path of the link origin which may result in unpredictable security issues ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$y_link 				:: The relative path of symlink to be analyzed
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	STRING								:: The relative or absolute (or non-safe) path to the symlink origin or empty string if broken link (no path safety checks are implemented over)
	 */
	public static function link_get_origin(?string $y_link, bool $allow_protected_paths=false) : string {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$y_link = (string) $y_link;
		//--
		if((string)trim((string)$y_link) == '') {
			Smart::log_warning(__METHOD__.' # The Link Name is Empty !');
			return '';
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_link, true, (bool)$allow_protected_paths)) { // pre-check, deny absolute paths
			Smart::log_warning(__METHOD__.' # Invalid Path Link : '.$y_link);
			return '';
		} //end if
		if(substr((string)$y_link, -1, 1) == '/') { // test if end with one or more trailing slash(es) and rtrim
			Smart::log_warning(__METHOD__.' # Link ends with one or many trailing slash(es) / : '.$y_link);
			$y_link = (string) rtrim((string)$y_link, '/');
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_link, true, (bool)$allow_protected_paths)) { // post-check, deny absolute paths
			Smart::log_warning(__METHOD__.' # Invalid Link Path : '.$y_link);
			return '';
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$y_link, true, (bool)$allow_protected_paths); // deny absolute paths
		//--
		if(!self::is_type_link((string)$y_link)) {
			Smart::log_warning(__METHOD__.' # Link does not exists : '.$y_link);
			return '';
		} //end if
		//--
		return (string) @readlink((string)$y_link);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe CREATE A SYMLINK. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/something).
	 * It will create a symlink of the origin into destination.
	 * WARNING: Use this function carefuly as the origin path may be an absolute path or a non-safe path of the link origin which may result in unpredictable security issues ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$origin 				:: The origin of the symlink, relative or absolute path or even may be a non-safe path (no path safety checks are implemented over)
	 * @param 	STRING 		$destination 			:: The destination of the symlink, relative path
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function link_create(?string $origin, ?string $destination) : int {
		//--
		$origin = (string) $origin;
		$destination = (string) $destination;
		//--
		if((string)$origin == '') {
			Smart::log_warning(__METHOD__.' # The Origin Name is Empty !');
			return 0;
		} //end if
		if((string)$destination == '') {
			Smart::log_warning(__METHOD__.' # The Destination Name is Empty !');
			return 0;
		} //end if
		//--
		/* DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY SmartFileSystem::link_get_origin() ...
		if(!SmartFileSysUtils::checkIfSafePath((string)$origin, false)) { // here we do not test against absolute path access because readlink may return an absolute path
			Smart::log_warning(__METHOD__.' # Invalid Path for Origin : '.$origin);
			return 0;
		} //end if
		*/
		if(!SmartFileSysUtils::checkIfSafePath((string)$destination)) {
			Smart::log_warning(__METHOD__.' # Invalid Path for Destination : '.$destination);
			return 0;
		} //end if
		//--
		if(!self::path_exists((string)$origin)) {
			Smart::log_warning(__METHOD__.' # Origin does not exists : '.$origin);
			return 0;
		} //end if
		if(self::path_exists((string)$destination)) {
			Smart::log_warning(__METHOD__.' # Destination exists : '.$destination);
			return 0;
		} //end if
		//--
		// DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY SmartFileSystem::link_get_origin() ...
		//SmartFileSysUtils::raiseErrorIfUnsafePath((string)$origin, false); // here we do not test against absolute path access because readlink may return an absolute path
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$destination);
		//--
		$result = @symlink((string)$origin, (string)$destination);
		//--
		if($result) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CREATE DIRS
	/**
	 * Safe CREATE A DIRECTORY (FOLDER) RECURSIVE OR NON-RECURSIVE. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/new-dir).
	 * It will create a new directory (folder) if not exists. If non-recursive will try to create just the last directory (folder) segment.
	 * The directory (folder) will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_DIRS.
	 *
	 * WARNING: The $allow_protected_paths parameter MUST BE SET TO TRUE ONLY FOR VERY SPECIAL USAGE ONLY, TO ALLOW relative paths like : #path/to/a/new-dir that may not be used with standard SmartFileSystem functions as they should be PROTECTED.
	 * Protected Paths (Directories / Folders) are intended for separing the accesible part of filesystem (for regular operations provided via this class) by the protected part of filesystem that can be by example accessed only from special designed libraries.
	 * Example: create a folder #db/sqlite/ and it's content (files, sub-dirs) will not be accessed by this class but only from outside libraries like SQLite).
	 * This feature implements a separation between regular file system folders that this class can access and other application level protected folders in order to avoid filesystem direct access to the protected folders.
	 * As long as all file system operations will be provided only by this class and not using the PHP internal file system functions this separation is safe and secure.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory to be created (can be an existing symlink to a directory)
	 * @param 	BOOLEAN 	$recursive 				:: DEFAULT is FALSE ; If TRUE will attempt to create the full directory (folder) structure if not exists and apply over each segment the standardized chmod, as set in SMART_FRAMEWORK_CHMOD_DIRS
	 * @param 	BOOLEAN 	$allow_protected_paths 	:: DEFAULT is FALSE ; If TRUE it may be used to create special protected folders (set to TRUE only if you know what you are really doing and you need to create a folder starting with a `#`, otherwise may lead to security issues ...) ; for task area this is always hardcoded to TRUE and cannot be overrided
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_create(?string $dir_name, bool $recursive=false, bool $allow_protected_paths=false) : int {
		//-- override (this is actually done automatically in raiseErrorIfUnsafePath() and checkIfSafePath() but reflect also here this as there are logs below ...
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$dir_name = (string) $dir_name;
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			Smart::log_warning(__METHOD__.' # A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return 0;
		} //end if
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] The Dir Name is Empty !');
			return 0;
		} //end if
		//--
		if($allow_protected_paths === true) {
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name, true, true); // deny absolute paths ; allow protected paths (starting with a `#`)
			$is_path_chk_safe = SmartFileSysUtils::checkIfSafePath((string)$dir_name, true, true); // deny absolute paths ; allow protected paths (starting with a `#`)
		} else {
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name);
			$is_path_chk_safe = SmartFileSysUtils::checkIfSafePath((string)$dir_name);
		} //end if else
		//--
		clearstatcache(true, (string)$dir_name);
		//--
		$result = false;
		//--
		if($is_path_chk_safe) {
			//--
			if(!self::path_exists((string)$dir_name)) {
				//--
				if($recursive === true) {
					$result = @mkdir((string)$dir_name, SMART_FRAMEWORK_CHMOD_DIRS, true);
					$dir_elements = (array) explode('/', (string)$dir_name);
					$tmp_crr_dir = '';
					for($i=0; $i<count($dir_elements); $i++) { // fix: to chmod all dir segments (in PHP the mkdir chmod is applied only to the last dir segment if recursive mkdir ...)
						$dir_elements[$i] = (string) trim((string)$dir_elements[$i]);
						if((string)$dir_elements[$i] != '') {
							if( // {{{SYNC-RECURSIVE-DIR_CHMOD_SAFETY-CHECKS}}}
								((string)$dir_elements[$i] != '/')
								AND
								((string)$dir_elements[$i] != '.')
								AND
								((string)$dir_elements[$i] != '..')
								AND
								(Smart::str_contains((string)$dir_elements[$i], '/') == false)
							) {
								$tmp_crr_dir .= (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_elements[$i]);
								if((string)$tmp_crr_dir != '') {
									if(self::is_type_dir((string)$tmp_crr_dir)) {
										self::fix_dir_chmod((string)$tmp_crr_dir); // apply separate chmod to each segment
									} //end if
								} //end if
							} else {
								Smart::log_warning(__METHOD__.' # Skip to CHMOD ('.SMART_FRAMEWORK_CHMOD_DIRS.') an Unsafe Directory Segment ['.$dir_elements[$i].']: '.$tmp_crr_dir);
							} //end if else
						} //end if
					} //end for
				} else {
					$result = @mkdir((string)$dir_name, SMART_FRAMEWORK_CHMOD_DIRS);
					if(self::is_type_dir((string)$dir_name)) {
						self::fix_dir_chmod((string)$dir_name); // apply chmod
					} //end if
				} //end if else
				//--
			} elseif(self::is_type_dir((string)$dir_name)) {
				//--
				$result = true; // dir exists
				//--
			} else {
				//--
				Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] # FAILED to create a directory because it appear to be a File: '.$dir_name);
				//--
			} //end if else
			//--
			if(!self::is_type_dir($dir_name)) {
				Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] # FAILED to create a directory: '.$dir_name);
				$out = 0;
			} //end if
			//--
			if(!self::have_access_write($dir_name)) {
				Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] # The directory is not writable: '.$dir_name);
				$out = 0;
			} //end if
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] # The directory path is not Safe: '.$dir_name);
			//--
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ RECURSIVE COPY A DIRECTORY (FULL CLONE)
	/**
	 * Safe COPY (CLONE) A DIRECTORY (FOLDER) RECURSIVE WITH ALL FILES AND SUB-DIRS, USING FILE COPY COMPARISON OR NOT. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 * It will clone an existing directory (folder) into a new directory (destination) by copying all the sub-directories, files and symlinks (includding broken symlinks on Linux/Unix ; except broken symlinks on Windows)
	 * The entire copied (cloned) dir structure (files, folders) will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_DIRS (for dirs) / SMART_FRAMEWORK_CHMOD_FILES (for files).
	 * WARNING: DO NOT Copy Destination inside Source to avoid Infinite Loops (anyway there is a loop protection but it is not safe as we don't know if all files were copied ...) !!!
	 * NOTICE: use this with single-user mode enabled as using it into multi-concurency environents if the source directory structure / files are altered meanwhile it may result in unpredictable errors ...
	 * IMPORTANT: after cloning a directory with this function it is recommended to do a check using use SmartFileSystem::compare_folders() to check if ALL the files and sub-dirs where copied as if other processes may write inside the source directory meanwhile not all content may be copied ...
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param 	STRING 		$dirsource 				:: The relative path of the source directory to be cloned (can be an existing symlink to a directory)
	 * @param 	STRING 		$dirdest 				:: The relative path of the destination directory - where the files and sub-folders will be copied (can be an existing symlink to a directory)
	 * @param 	BOOLEAN 	$check_copy_contents 	:: DEFAULT is TRUE ; If set to TRUE will compare the copied content from the destination files with the original reference files content using sha1-file checksums ; If set to FALSE will not do this comparison check (which may take a big amount of time on very large files)
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0, -1, -2, -3, -4, -5 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_copy(?string $dirsource, ?string $dirdest, bool $check_copy_contents=true) : int {
		//--
		return (int) self::dir_recursive_private_copy((string)$dirsource, (string)$dirdest, (bool)$check_copy_contents);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ PRIVATE RECURSIVE COPY A DIRECTORY (FULL CLONE)
	// NOTICE: This function is PRIVATE because the last two params are private, they SHOULD NOT be used unauthorized (they are used only internal to remember the initial dirs ...)
	private static function dir_recursive_private_copy(?string $dirsource, ?string $dirdest, bool $check_copy_contents, ?string $protected_dirsource='', ?string$protected_dirdest='') : int {
		//--
		$dirsource = (string) $dirsource;
		$dirdest = (string) $dirdest;
		$check_copy_contents = (bool) $check_copy_contents;
		$protected_dirsource = (string) $protected_dirsource;
		$protected_dirdest = (string) $protected_dirdest;
		//--
		if((int)strlen((string)$dirsource) <= 0) {
			Smart::log_warning(__METHOD__.' # The Source Dir Name is Empty !');
			return 0; // empty source dir
		} //end if
		if((int)strlen((string)$dirdest) <= 0) {
			Smart::log_warning(__METHOD__.' # The Destination Dir Name is Empty !');
			return 0; // empty destination dir
		} //end if
		//--
		clearstatcache(true, (string)$dirsource);
		clearstatcache(true, (string)$dirdest);
		//--
		if((int)strlen((string)$protected_dirsource) <= 0) {
			$protected_dirsource = (string) $dirsource; // 1st time
		} //end if
		if((int)strlen((string)$protected_dirdest) <= 0) {
			if(self::path_exists((string)$dirdest)) {
				Smart::log_warning(__METHOD__.' # The Destination Dir exists: S='.$destination);
				return 0;
			} //end if else
			$protected_dirdest = (string) $dirdest; // 1st time
		} //end if
		//-- add trailing slash
		$dirsource = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirsource);
		$dirdest = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirdest);
		//-- checks (must be after adding trailing slashes)
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirsource);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirdest);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$protected_dirsource);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$protected_dirdest);
		//-- protect against infinite loop if the source and destination are the same or destination contained in source
		if((string)$dirdest == (string)$dirsource) {
			Smart::log_warning(__METHOD__.' # The Source Dir is the same as Destination Dir: S&D='.$dirdest);
			return 0;
		} //end if
		if((string)$dirdest == (string)SmartFileSysUtils::addPathTrailingSlash((string)Smart::dir_name((string)$dirsource))) {
			Smart::log_warning(__METHOD__.' # The Destination Dir is the same as Source Parent Dir: S='.$dirsource.' ; D='.$dirdest);
			return 0;
		} //end if
		if((string)substr((string)$dirdest, 0, (int)strlen((string)$dirsource)) == (string)$dirsource) {
			Smart::log_warning(__METHOD__.' # The Destination Dir is inside the Source Dir: S='.$dirsource.' ; D='.$dirdest);
			return 0;
		} //end if
		if((string)substr((string)$protected_dirdest, 0, (int)strlen((string)$protected_dirsource)) == (string)$protected_dirsource) {
			Smart::log_warning(__METHOD__.' # The Original Destination Dir is inside the Original Source Dir: S*='.$protected_dirsource.' ; D*='.$protected_dirdest);
			return 0;
		} //end if
		//-- protect against infinite loop (this can happen with loop sym-links)
		if((string)$dirsource == (string)$protected_dirdest) {
			Smart::log_warning(__METHOD__.' # The Source Dir is the same as Previous Step Source Dir (Loop Detected): S='.$dirsource.' ; S*='.$protected_dirdest);
			return 0;
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$dirsource)) {
			Smart::log_warning(__METHOD__.' # The Source Dir Name is Invalid: S='.$dirsource);
			return 0;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$dirdest)) {
			Smart::log_warning(__METHOD__.' # The Destination Dir Name is Invalid: D='.$dirdest);
			return 0;
		} //end if
		//--
		if(!self::is_type_dir((string)$dirsource)) {
			Smart::log_warning(__METHOD__.' # The Source Dir Name is not a Directory or does not exists: S='.$dirsource);
			return 0;
		} //end if else
		//--
		if(self::path_exists((string)$dirdest)) {
			if(!self::is_type_dir((string)$dirdest)) {
				Smart::log_warning(__METHOD__.' # The Destination Dir appear to be a file: D='.$dirdest);
				return 0;
			} //end if
		} else {
			if(self::dir_create((string)$dirdest, true) !== 1) { // recursive
				Smart::log_warning(__METHOD__.' # Could Not Recursively Create the Destination: D='.$dirdest);
				return 0;
			} //end if
		} //end if else
		//--
		$out = 1; // default is ok
		//--
		if($handle = opendir((string)$dirsource)) {
			//--
			while(false !== ($file = readdir($handle))) {
				//--
				if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty
					//--
					if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$file) != 1) {
						Smart::log_warning(__METHOD__.' # Skip Unsafe FileName or DirName `'.$file.'` detected in path: '.$dirsource);
						continue; // skip
					} //end if
					//--
					$tmp_path = (string) $dirsource.$file;
					$tmp_dest = (string) $dirdest.$file;
					//--
					SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_path);
					SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_dest);
					//--
					if(self::path_exists((string)$tmp_path)) {
						//--
						if(self::is_type_link((string)$tmp_path)) { // link
							//--
							self::delete((string)$tmp_dest);
							if(self::path_exists((string)$tmp_dest)) {
								Smart::log_warning(__METHOD__.' # Destination link still exists: '.$tmp_dest);
							} //end if
							//--
							if(self::link_create(self::link_get_origin((string)$tmp_path), (string)$tmp_dest) !== 1) {
								Smart::log_warning(__METHOD__.' # Failed to copy a Link: '.$tmp_path);
								return 0;
							} //end if else
							//--
						} elseif(self::is_type_file((string)$tmp_path)) { // file
							//--
							self::delete((string)$tmp_dest);
							if(self::path_exists((string)$tmp_dest)) {
								Smart::log_warning(__METHOD__.' # Destination file still exists: '.$tmp_dest);
							} //end if
							//--
							if(self::copy((string)$tmp_path, (string)$tmp_dest, false, (bool)$check_copy_contents) !== 1) { // do not rewrite destination, use check from param
								Smart::log_warning(__METHOD__.' # Failed to copy a File: '.$tmp_path);
								return 0;
							} //end if else
							//--
						} elseif(self::is_type_dir((string)$tmp_path)) { // dir
							//--
							if(self::dir_recursive_private_copy((string)$tmp_path, (string)$tmp_dest, (bool)$check_copy_contents, (string)$protected_dirsource, (string)$protected_dirdest) !== 1) {
								Smart::log_warning(__METHOD__.' # Failed on Dir: '.$tmp_path);
								return 0;
							} //end if
							//--
						} else {
							//--
							Smart::log_warning(__METHOD__.' # Invalid Type: '.$tmp_path);
							return 0;
							//--
						} //end if else
						//--
					} elseif(self::is_type_link($tmp_path)) { // broken link (we still copy it)
						//--
						self::delete($tmp_dest);
						if(self::path_exists($tmp_dest)) {
							Smart::log_warning(__METHOD__.' # Destination Link still exists: '.$tmp_dest);
						} //end if
						//--
						if(self::link_create(self::link_get_origin($tmp_path), $tmp_dest) !== 1) {
							Smart::log_warning(__METHOD__.' # Failed to copy a Link: '.$tmp_path);
							return 0;
						} //end if else
						//--
					} else {
						//--
						Smart::log_warning(__METHOD__.' # File does not exists or is not accessible: '.$tmp_path);
						return 0;
						//--
					} //end if
					//--
				} //end if
				//--
			} //end while
			//--
			@closedir($handle);
			//--
		} else {
			//--
			$out = 0;
			//--
		} //end if else
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ RENAME DIR / MOVE DIR
	/**
	 * Safe RENAME OR MOVE A DIRECTORY (FOLDER) TO A DIFFERENT LOCATION. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 * It will rename or move the source directory (folder) to a new location (destination), includding across partitions.
	 * It will FAIL if the destination directory exists, so be sure to check and remove the destination if you intend to overwrite it.
	 * After rename or move the destination will be chmod standardized, as set in SMART_FRAMEWORK_CHMOD_DIRS.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory (folder) to be renamed or moved (can be a symlink to a directory)
	 * @param 	STRING 		$new_dir_name 			:: The relative path of the destination directory (folder) or a new directory (folder) name to rename or a new path where to move
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_rename(?string $dir_name, ?string $new_dir_name) : int {
		//--
		$dir_name = (string) $dir_name;
		$new_dir_name = (string) $new_dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # Source Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$new_dir_name == '') {
			Smart::log_warning(__METHOD__.' # Destination Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$dir_name == (string)$new_dir_name) {
			Smart::log_warning(__METHOD__.' # The Source and the Destination Files are the same: '.$dir_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_dir((string)$dir_name)) OR ((self::is_type_link((string)$dir_name)) AND (!self::is_type_dir((string)self::link_get_origin((string)$dir_name))))) {
			Smart::log_warning(__METHOD__.' # Source is not a DIR: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists((string)$new_dir_name)) {
			Smart::log_warning(__METHOD__.' # The destination already exists: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		//--
		$dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_name); // trailing slash
		$new_dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$new_dir_name); // trailing slash
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$new_dir_name);
		//--
		if((string)$dir_name == (string)$new_dir_name) {
			Smart::log_warning(__METHOD__.' # Source and Destination are the same: S&D='.$dir_name);
			return 0;
		} //end if
		if((string)$new_dir_name == (string)SmartFileSysUtils::addPathTrailingSlash((string)Smart::dir_name((string)$dir_name))) {
			Smart::log_warning(__METHOD__.' # The Destination Dir is the same as Source Parent Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if((string)substr((string)$new_dir_name, 0, (int)strlen((string)$dir_name)) == (string)$dir_name) {
			Smart::log_warning(__METHOD__.' # The Destination Dir is inside the Source Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(!self::is_type_dir((string)SmartFileSysUtils::addPathTrailingSlash((string)Smart::dir_name((string)$new_dir_name)))) {
			Smart::log_warning(__METHOD__.' # The Destination Parent Dir is missing: P='.SmartFileSysUtils::addPathTrailingSlash((string)Smart::dir_name($new_dir_name)).' of D='.$new_dir_name);
			return 0;
		} //end if
		//--
		clearstatcache(true, (string)$dir_name);
		clearstatcache(true, (string)$new_dir_name);
		//--
		$result = false;
		//--
		$dir_name = (string) rtrim((string)$dir_name, '/'); // FIX: remove trailing slash, it may be a link
		$new_dir_name = (string) rtrim((string)$new_dir_name, '/'); // FIX: remove trailing slash, it may be a link
		//--
		if(((string)$dir_name != (string)$new_dir_name) AND (SmartFileSysUtils::checkIfSafePath((string)$dir_name)) AND (SmartFileSysUtils::checkIfSafePath((string)$new_dir_name))) {
			if((self::is_type_dir((string)$dir_name)) OR ((self::is_type_link((string)$dir_name)) AND (self::is_type_dir((string)self::link_get_origin((string)$dir_name))))) {
				if(!self::path_exists((string)$new_dir_name)) {
					$result = @rename((string)$dir_name, (string)$new_dir_name);
				} //end if
			} //end if
		} //end if else
		//--
		if((!self::is_type_dir((string)$new_dir_name)) OR ((self::is_type_link((string)$new_dir_name)) AND (!self::is_type_dir((string)self::link_get_origin((string)$new_dir_name))))) {
			Smart::log_warning(__METHOD__.' # FAILED to rename a directory: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists((string)$dir_name)) {
			Smart::log_warning(__METHOD__.' # Source DIR still exists: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ DELETE DIRS
	/**
	 * Safe DELETE (REMOVE) A DIRECTORY (FOLDER). IF RECURSIVE WILL REMOVE ALL THE SUB-DIR CONTENTS (FILES AND SUB-DIRS). WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 * It will try to remove the directory (folder) if empty in non-recursive mode or will try to delete all directory content (files and sub-folders), if recursive mode enabled.
	 * It will FAIL in non-recursive mode if the directory (folder) is not empty.
	 *
	 * @param 	STRING 		$dir_name 				:: The relative path of directory (folder) to be deleted (removed) ; it can be a symlink to another directory
	 * @param 	BOOLEAN 	$recursive 				:: DEFAULT is TRUE ; If set to TRUE will remove directory and all it's content ; If FALSE will try just to remove the directory if empty, otherwise will FAIL
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function dir_delete(?string $dir_name, bool $recursive=true) : int {
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'] # Dir Name is Empty !');
			return 0;
		} //end if
		//--
		clearstatcache(true, (string)$dir_name);
		//--
		if(self::is_type_link((string)$dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink((string)$dir_name); // avoid deleting content from a linked dir, just remove the link :: THIS MUST BE DONE BEFORE ADDING THE TRAILING SLASH
			//--
			if(($f_cx) AND (!self::is_type_link((string)$dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_name); // fix invalid path (must end with /)
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name);
		//--
		if(!self::path_exists((string)$dir_name)) {
			//--
			return 1;
			//--
		} //end if
		//-- avoid deleting content from a linked dir, just remove the link (2nd check, after adding the trailing slash)
		if(self::is_type_link((string)$dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink((string)$dir_name);
			//--
			if(($f_cx) AND (!self::is_type_link((string)$dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$result = false;
		//-- remove all subdirs and files within
		if(SmartFileSysUtils::checkIfSafePath((string)$dir_name)) {
			//--
			if((self::is_type_dir((string)$dir_name)) AND (!self::is_type_link((string)$dir_name))) { // double check if type link
				//--
				self::fix_dir_chmod((string)$dir_name); // apply chmod
				//--
				if($handle = opendir((string)$dir_name)) {
					//--
					while(false !== ($file = readdir($handle))) {
						//--
						if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty
							//--
							if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$file) != 1) { // skip non-safe filenames to avoid raise error if a directory contains accidentally a nn-safe filename or dirname (at least delete as much as can) ...
								//--
								Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'] # SKIP Unsafe FileName or DirName `'.$file.'` detected in path: '.$dir_name);
								//--
							} else {
								//--
								if((self::is_type_dir((string)$dir_name.$file)) AND (!self::is_type_link((string)$dir_name.$file))) {
									//--
									if($recursive === true) {
										//--
										self::dir_delete((string)$dir_name.$file, (bool)$recursive);
										//--
									} else {
										//--
										return 0; // not recursive and in this case sub-folders are not deleted
										//--
									} //end if else
									//--
								} else { // file or link
									//--
									self::delete((string)$dir_name.$file);
									//--
								} //end if else
								//--
							} //end if else
							//--
						} //end if
						//--
					} //end while
					//--
					@closedir($handle);
					//--
				} else {
					//--
					$result = false;
					Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'] # FAILED to open the directory: '.$dir_name);
					//--
				} //end if
				//-- finally, remove itself
				$result = @rmdir((string)$dir_name);
				//--
			} else { // the rest of cases: is a file or a link
				//--
				$result = false;
				Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'] # This is not a directory: '.$dir_name);
				//--
			} //end if
			//--
		} //end if
		//--
		if(self::path_exists((string)$dir_name)) { // last final check
			$result = false;
			Smart::log_warning(__METHOD__.' # [R='.(int)$recursive.'] # FAILED to delete a directory: '.$dir_name);
		} //end if
		//--
		if($result == true) {
			$out = 1;
		} else {
			$out = 0;
		} //end if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe COMPARE TWO DIRECTORIES (FOLDERS) FROM LEFT TO RIGHT. RECURSIVE OR NOT. WORKS ONLY WITH RELATIVE PATHS (Ex: path/to/a/some-dir).
	 *
	 * @param 	STRING 		$dir1 					:: The relative path of the LEFT directory (folder) to compare ; it can be a symlink to another directory
	 * @param 	STRING 		$dir2 					:: The relative path of the RIGHT directory (folder) to compare ; it can be a symlink to another directory
	 * @param 	BOOLEAN 	$include_dot_files 		:: DEFAULT is TRUE ; If set to TRUE will compare also dot files (ex: .gitignore) ; If FALSE will skip comparing dot files
	 * @param 	BOOLEAN 	$recurring 				:: DEFAULT is TRUE ; If set to TRUE will do a full comparison (recuring) ; If set to FALSE will compare just the first level of each directory and will not recurse and compare into sub-directories
	 *
	 * @return 	ARRAY								:: Array of Differences ; If Empty Array, there are no diferences ; If array size > 0, will contain the differences between the compared directories (folders)
	 */
	public static function compare_folders(?string $dir1, ?string $dir2, bool $include_dot_files=true, bool $recurring=true) : array { // TODO: add a 4th parameter as search pattern to compare
		//-- get storage data for each folder
		$arr_dir1 = (array) (new SmartGetFileSystem(true))->get_storage((string)$dir1, (bool)$recurring, (bool)$include_dot_files);
		$arr_dir2 = (array) (new SmartGetFileSystem(true))->get_storage((string)$dir2, (bool)$recurring, (bool)$include_dot_files);
		//-- the above on error return empty array, so this error must be catched
		if((int)Smart::array_size($arr_dir1) <= 0) {
			return array('compare-error' => 'First Folder returned empty storage data: '.$dir1);
		} //end if
		if((int)Smart::array_size($arr_dir2) <= 0) {
			return array('compare-error' => 'Second Folder returned empty storage data: '.$dir2);
		} //end if
		//-- paths are not identical, so wipe out of compare
		unset($arr_dir1['path']);
		unset($arr_dir2['path']);
		//-- size dirs are not identical if on different file systems (EXT4 / FFS / NFS)
		unset($arr_dir1['size-dirs']);
		unset($arr_dir2['size-dirs']);
		//-- because size dirs is includded in (total) size unset this also (will remain to compare just 'size-files') !!
		unset($arr_dir1['size']);
		unset($arr_dir2['size']);
		//-- array_diff_assoc() will not go recursive over sub-arrays and in PHP8 will not ignore it
		unset($arr_dir1['list-dirs']);
		unset($arr_dir2['list-dirs']);
		//-- array_diff_assoc() will not go recursive over sub-arrays and in PHP8 will not ignore it
		unset($arr_dir1['list-files']);
		unset($arr_dir2['list-files']);
		//-- array_diff_assoc() will not go recursive over sub-arrays and in PHP8 will not ignore it
		unset($arr_dir1['errors']);
		unset($arr_dir2['errors']);
		//-- compute array diffs (must be on both directions)
		$arr_diff1 = array_diff_assoc($arr_dir1, $arr_dir2);
		$arr_diff2 = array_diff_assoc($arr_dir2, $arr_dir1);
		if((Smart::array_size($arr_diff1) > 0) OR (Smart::array_size($arr_diff2) > 0)) {
			return array('compare-error' => 'The two folders are not identical: '.$dir1.' [::] '.$dir2."\n".'@Diffs1: '.print_r($arr_diff1,1)."\n".'@Diffs2: '.print_r($arr_diff2,1)."\n".'@Dir1: '.print_r($arr_dir1,1)."\n".'@Dir2: '.print_r($arr_dir2,2));
		} //end if
		//--
		return array(); // this means no differences
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartGetFileSystem - provides the File System Get/Scan functions.
 *
 * This class requires the use of RELATIVE PATHS to force using correct path access in a web environment application.
 * Relative paths must be relative to the web application folder as folder: `some-folder/` or file: `some-folder/my-file.txt`.
 * Absolute paths are denied by internal checks as they are NOT SAFE in a Web Application from the security point of view ...
 * Also the backward path access like `../some-file-or-folder` is denied from the above exposed reasons.
 * Files and Folders must contain ONLY safe characters as: `[a-z] [A-Z] [0-9] _ - . @ #` ; folders can also contain slashes `/` (as path separators); no spaces are allowed in paths !!
 *
 * <code>
 *
 * // Get Storage example:
 * $filesys = new SmartGetFileSystem(true);
 * $data = $filesys->get_storage('my_dir/my_subdir');
 * print_r($data);
 *
 * // Search for Files example:
 * $obj = new SmartGetFileSystem();
 * $arr = $obj->search_files(true, 'uploads/', false, '.svg', 100);
 * print_r($arr);
 *
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		This class can handle thread concurency to the filesystem in a safe way by using the LOCK_EX (lock exclusive) feature on each file written / appended thus making also reads to be safe
 *
 * @depends 	classes: Smart
 * @version 	v.20260103
 * @package 	Application:FileSystem
 *
 */
final class SmartGetFileSystem {

	// ->


	//================================================================
	//--
	private $allow_protected_paths		= false;
	private $list_files_and_dirs		= false;
	private $num_size 					= 0;
	private $num_dirs_size				= 0;
	private $num_files_size				= 0;
	private $num_links 					= 0;
	private $num_dirs 					= 0;
	private $num_files 					= 0;
	private $errors_arr 				= [];
	private $scanned_folders 			= [];
	private $pattern_file_matches 		= [];
	private $pattern_dir_matches 		= [];
	private $pattern_search_str			= '';
	private $search_prevent_file		= '';
	private $search_prevent_override 	= '';
	private $limit_search_files			= 0;
	//--
	//================================================================


	//================================================================
	public function __construct(bool $list_files_and_dirs=false, bool $allow_protected_paths=false) { // CONSTRUCTOR
		//--
		$this->list_files_and_dirs   = (bool) $list_files_and_dirs;
		$this->allow_protected_paths = (bool) $allow_protected_paths;
		//--
		$this->init_vars();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function init_vars() : void {
		//--
		$this->num_size 			= 0;
		$this->num_dirs_size 		= 0;
		$this->num_files_size 		= 0;
		$this->num_links 			= 0;
		$this->num_dirs 			= 0;
		$this->num_files 			= 0;
		$this->pattern_file_matches = array();
		$this->pattern_dir_matches 	= array();
		$this->scanned_folders 		= array();
		$this->errors_arr 			= array();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public function get_storage(?string $dir_name, bool $recurring=true, bool $include_dot_files=false, ?string $search_pattern='') : array {
		//--
		$dir_name = (string) $dir_name;
		$search_pattern = (string) $search_pattern;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # Dir Name is Empty !');
			return array();
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_name);
		//-- protection
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name, true, (bool)$this->allow_protected_paths); // deny absolute paths
		//--
		$this->init_vars();
		//--
		$this->limit_search_files = 0;
		//-- get
		$this->folder_iterator((bool)$recurring, (string)$dir_name, (bool)$include_dot_files, (string)$search_pattern); // no search pattern (return only sizes)
		//-- sort dirs list descending by modified time, newest first
		$this->pattern_dir_matches = array_keys($this->pattern_dir_matches);
		natsort($this->pattern_dir_matches);
		$this->pattern_dir_matches = array_values($this->pattern_dir_matches);
		//-- sort files list descending by modified time, newest first
		$this->pattern_file_matches = array_keys($this->pattern_file_matches);
		natsort($this->pattern_file_matches);
		$this->pattern_file_matches = array_values($this->pattern_file_matches);
		//--
		$arr = []; // {{{SYNC-SmartGetFileSystem-Output}}}
		//--
		$arr['quota'] 		= 0; //this will be set later
		$arr['path'] 		= (string) $dir_name;
		$arr['reccuring'] 	= (string) $recurring;
		$arr['search@max-files'] = $this->limit_search_files;
		$arr['search@pattern'] = $this->pattern_search_str;
		$arr['restrict@dir-containing-file'] = $this->search_prevent_file;
		$arr['restrict@dir-override'] = $this->search_prevent_override;
		//--
		$arr['errors'] 		= (array) $this->errors_arr;
		//--
		$arr['size']		= $this->num_size;
		$arr['size-dirs']	= $this->num_dirs_size;
		$arr['size-files']	= $this->num_files_size;
		$arr['links'] 		= $this->num_links; // this is just for info, it is contained in the dirs or files num
		$arr['dirs'] 		= $this->num_dirs;
		$arr['files'] 		= $this->num_files;
		$arr['list#dirs'] 	= Smart::array_size($this->pattern_dir_matches);
		$arr['list#files']	= Smart::array_size($this->pattern_file_matches);
		$arr['list-dirs'] 	= (array) $this->pattern_dir_matches;
		$arr['list-files'] 	= $this->pattern_file_matches;
		//--
		return (array) $arr ;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public function search_files(bool $recurring, ?string $dir_name, bool $include_dot_files, ?string $search_pattern, ?int $limit_search_files, ?string $search_prevent_file='', ?string $search_prevent_override='') : array {
		//--
		$dir_name = (string) $dir_name;
		$search_pattern = (string) $search_pattern;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # Dir Name is Empty !');
			return array();
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_name);
		//-- protection
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name, true, (bool)$this->allow_protected_paths); // deny absolute paths
		//--
		$this->init_vars();
		//--
		$this->limit_search_files = (int) Smart::format_number_int((int)$limit_search_files, '+');
		//--
		$this->list_files_and_dirs = true;
		$this->folder_iterator((bool)$recurring, (string)$dir_name, (bool)$include_dot_files, (string)$search_pattern, (string)$search_prevent_file, (string)$search_prevent_override); // ! search pattern (return found files and dirs up to max matches)
		//-- sort dirs list descending by modified time, newest first
		arsort($this->pattern_dir_matches, SORT_NUMERIC);
		$this->pattern_dir_matches = array_keys($this->pattern_dir_matches);
		//-- sort files list descending by modified time, newest first
		arsort($this->pattern_file_matches, SORT_NUMERIC);
		$this->pattern_file_matches = array_keys($this->pattern_file_matches);
		//--
		$arr = array(); // {{{SYNC-SmartGetFileSystem-Output}}}
		//--
		$arr['quota'] 		= 0; //this will be set later
		$arr['path'] 		= (string) $dir_name;
		$arr['reccuring'] 	= (string) $recurring;
		$arr['search@max-files'] = $this->limit_search_files;
		$arr['search@pattern'] = $this->pattern_search_str;
		$arr['restrict@dir-containing-file'] = $this->search_prevent_file;
		$arr['restrict@dir-override'] = $this->search_prevent_override;
		//--
		$arr['errors'] 		= (array) $this->errors_arr;
		//--
		$arr['size']		= $this->num_size;
		$arr['size-dirs']	= $this->num_dirs_size;
		$arr['size-files']	= $this->num_files_size;
		$arr['links'] 		= $this->num_links; // this is just for info, it is contained in the dirs or files num
		$arr['dirs'] 		= $this->num_dirs;
		$arr['files'] 		= $this->num_files;
		$arr['list#dirs'] 	= Smart::array_size($this->pattern_dir_matches);
		$arr['list#files']	= Smart::array_size($this->pattern_file_matches);
		$arr['list-dirs'] 	= $this->pattern_dir_matches;
		$arr['list-files'] 	= $this->pattern_file_matches;
		//--
		return (array) $arr ;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function get_std_real_dir_path(?string $relative_or_linked_path) : string {
		//--
		$relative_or_linked_path = (string) trim((string)$relative_or_linked_path);
		if((string)$relative_or_linked_path == '') {
			return '';
		} //end if
		//--
		$relative_or_linked_path = (string) Smart::real_path((string)$relative_or_linked_path);
		$relative_or_linked_path = (string) rtrim((string)$relative_or_linked_path, '/');
		//--
		return (string) $relative_or_linked_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function folder_iterator(bool $recurring, ?string $dir_name, bool $include_dot_files, ?string $search_pattern='', ?string $search_prevent_file='', ?string $search_prevent_override='') : void {
		//--
		$recurring = (bool) $recurring;
		$dir_name = (string) $dir_name;
		$include_dot_files = (bool) $include_dot_files;
		$search_pattern = (string) $search_pattern;
		$search_prevent_file = (string) $search_prevent_file;
		$search_prevent_override = (string) $search_prevent_override;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.' # Dir Name is Empty !');
			return; // this function does not return anything, but just stop here in this case
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_name);
		//-- protection
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name, true, (bool)$this->allow_protected_paths); // deny absolute paths
		//--
		clearstatcache(true, $dir_name);
		//--
		$this->pattern_search_str = $search_pattern;
		$this->search_prevent_file = $search_prevent_file;
		$this->search_prevent_override = $search_prevent_override;
		//--
		if((SmartFileSystem::path_exists($dir_name)) AND (!SmartFileSystem::is_type_file($dir_name))) { // can be dir or link
			//-- circular reference check for linked dirs that can trap execution into an infinite loop ; catch here ... otherwise will be catched by the max path lenth allowance
			if(!array_key_exists((string)$this->get_std_real_dir_path((string)$dir_name), $this->scanned_folders)) {
				$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name)] = 0; // PHP8 Fix
			} //end if
			if((int)$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name)] > 1) {
			//	Smart::log_notice(__METHOD__.' # Cycle Trap Linked Dir Detected for: '.$dir_name);
				$this->errors_arr[] = (string) $dir_name;
				return; // this function does not return anything, but just stop here in this case
			} //end if
			//-- list
			$arr_dir_files = scandir((string)$dir_name); // mixed: can be array or false
			//--
			//if($handle = opendir($dir_name)) {
			if(($arr_dir_files !== false) AND (Smart::array_size($arr_dir_files) > 0)) {
				//---------------------------------------
				//while(false !== ($file = readdir($handle))) {
				for($i=0; $i<Smart::array_size($arr_dir_files); $i++) {
					//--
					$file = (string) $arr_dir_files[$i]; // used by for loop
					//--
					if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty, skip get the unsafe file names to avoid errors
						//--
						if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$file, true, (bool)$this->allow_protected_paths) != 1) { // deny absolute paths
							Smart::log_warning(__METHOD__.' # Skip Unsafe FileName or DirName `'.$file.'` detected in path: '.$dir_name);
							continue; // skip
						} //end if
						//--
						if(($include_dot_files) OR ((!$include_dot_files) AND (substr($file, 0, 1) != '.'))) {
							//--
							SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_name.$file, true, (bool)$this->allow_protected_paths); // deny absolute paths
							//-- params to see if counted or added to pattern matches
							$tmp_allow_addition = 1;
							$tmp_add_pattern = 0;
							//-- this is for #private folders, will prevent searching in folders containing for example this file: .private-folder but can be overriden by the $search_prevent_override option exluding a particular path like folder/private/user1
							if(((int)strlen($search_prevent_file) > 0) AND (SmartFileSystem::is_type_file($dir_name.$search_prevent_file))) {
								if(((int)strlen($search_prevent_override) <= 0) OR (((int)strlen($search_prevent_override) > 0) AND (!SmartFileSystem::is_type_file($dir_name.$search_prevent_override)))) {
									$tmp_allow_addition = 0;
								} //end if
							} //end if
							//-- this is a search pattern (search pattern does not apply to folders !!) ; if no empty will populate the pattern matches array with all files and folders matching ; to include all, use * or a particular search for the rest like myfile1
							if(((string)$search_pattern == '') OR (SmartFileSystem::is_type_dir($dir_name.$file))) {
								if($tmp_allow_addition) {
									if($this->list_files_and_dirs) {
										$tmp_add_pattern = 1;
									} //end if
								} //end if
							} else {
								if(($this->limit_search_files <= 0) OR (Smart::array_size($this->pattern_file_matches) < $this->limit_search_files)) {
									if(
										((string)$search_pattern == '*') OR
										(
											((string)$search_pattern == '[image]') AND
											(
												(substr($file, -4, 4) == '.png') OR
												(substr($file, -4, 4) == '.gif') OR
												(substr($file, -4, 4) == '.jpg') OR
												(substr($file, -5, 5) == '.jpeg')
												// TODO: add support for .webp
											)
										) OR
										(
											((string)$search_pattern != '*') AND
											((string)$search_pattern != '[image]') AND
											(
												((strpos($search_pattern, '*') !== false) AND (stripos($file, str_replace('*', '', $search_pattern)) !== false)) // search for file contains the pattern # TODO: try to parse this in a more fancy way ...
												OR
												((strpos($search_pattern, '*') === false) AND (stripos(strrev($file), strrev($search_pattern)) === 0)) // search for file ends with pattern
											)
										)
									) {
										if($tmp_allow_addition) {
											if($this->list_files_and_dirs) {
												$tmp_add_pattern = 1;
											} //end if
										} //end if
									} else {
										$tmp_allow_addition = 0;
									} //end if else
								} //end if
							} //end if
							//--
							if($this->limit_search_files > 0) { // the dir should not be taken in count here
								if(($this->num_files + $this->num_links) >= $this->limit_search_files) {
									break;
								} //end if
							} //end if
							//--
							if(!SmartFileSystem::is_type_link($dir_name.$file)) {
								//--
								if(SmartFileSystem::is_type_dir($dir_name.$file)) {
									//-- dir
									if($tmp_allow_addition) {
										//--
										$tmp_fsize = Smart::format_number_int(SmartFileSystem::get_file_size($dir_name.$file),'+');
										//--
										$this->num_dirs++;
										$this->num_size += $tmp_fsize;
										$this->num_dirs_size += $tmp_fsize;
										//--
										$tmp_fsize = 0;
										//--
										if($tmp_add_pattern) {
											if($recurring) { // if recurring, add the full path
												$this->pattern_dir_matches[$dir_name.$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
											} else { // if not recurring, add just base path, without dir name prefix
												$this->pattern_dir_matches[$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
											} //end if else
										} //end if
										//--
									} //end if
									//--
									if(!array_key_exists((string)$this->get_std_real_dir_path((string)$dir_name.$file), $this->scanned_folders)) {
										$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name.$file)] = 0; // PHP8 Fix
									} //end if
									$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name.$file)]++;
									//--
									if($recurring) {
										//-- we go search inside even if this folder name may not match the search pattern, it is a folder, except if dissalow addition from above
										$this->folder_iterator((bool)$recurring, (string)SmartFileSysUtils::addPathTrailingSlash((string)$dir_name.$file), (bool)$include_dot_files, (string)$search_pattern, (string)$search_prevent_file, (string)$search_prevent_override);
										//--
									} //end if
									//--
								} else {
									//-- file
									if($tmp_allow_addition) {
										//--
										$tmp_fsize = Smart::format_number_int(SmartFileSystem::get_file_size($dir_name.$file),'+');
										//--
										$this->num_files++;
										$this->num_size += $tmp_fsize;
										$this->num_files_size += $tmp_fsize;
										//--
										$tmp_fsize = 0;
										//--
										if($tmp_add_pattern) {
											if($recurring) { // if recurring, add the full path
												$this->pattern_file_matches[$dir_name.$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
											} else { // if not recurring, add just base path, without dir name prefix
												$this->pattern_file_matches[$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
											} //end if else
										} //end if
										//--
									} //end if
									//--
								} //end else
								//--
							} else {
								//-- link: dir or file or broken link
								if($tmp_allow_addition) {
									//--
									$link_origin = (string) SmartFileSystem::link_get_origin((string)$dir_name.$file, (bool)$this->allow_protected_paths); // deny absolute paths
									//--
									if(((string)$link_origin == '') OR (!SmartFileSystem::path_exists($link_origin))) {
										//--
										// case of broken link ..., not includding broken links, they are useless
										//--
									} else {
										//--
										$tmp_size_arr = array();
										$tmp_fsize = 0;
										//$tmp_size_arr = (array) @lstat($dir_name.$file);
										//$tmp_fsize = Smart::format_number_int($tmp_size_arr[7],'+'); // $tmp_size_arr[7] -> size, but may break compare if on a different file system or in distributed storage on various OS
										//--
										$this->num_links++;
										//--
										if(SmartFileSystem::path_real_exists($dir_name.$file)) { // here the real path must exists to be tested because if broken link and stat on it (file modif time) will log un-necessary errors ...
											//-- bugfix: not if broken link
											$this->num_size += $tmp_fsize;
											if($tmp_add_pattern) {
												if(SmartFileSystem::is_type_dir($dir_name.$file)) {
													//--
													$this->num_dirs++;
													$this->num_dirs_size += $tmp_fsize;
													if(!!$recurring) { // if recurring, add the full path
														$this->pattern_dir_matches[$dir_name.$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} else { // if not recurring, add just base path, without dir name prefix
														$this->pattern_dir_matches[$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} //end if else
													//--
													if((string)$this->get_std_real_dir_path((string)$link_origin) != (string)$this->get_std_real_dir_path((string)$dir_name.$file)) { // avoid register if this is the same as the linked folder (this happen in rare situations ; ex: with the 1st sub-level linked folders)
														if(!array_key_exists((string)$this->get_std_real_dir_path((string)$link_origin), $this->scanned_folders)) {
															$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$link_origin)] = 0; // PHP8 Fix
														} //end if
														$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$link_origin)]++; // if link origin real path is different, register it too
													} //end if
													if(!array_key_exists((string)$this->get_std_real_dir_path((string)$dir_name.$file), $this->scanned_folders)) {
														$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name.$file)] = 0; // PHP8 Fix
													} //end if
													$this->scanned_folders[(string)$this->get_std_real_dir_path((string)$dir_name.$file)]++;
													//--
													if(!!$recurring) {
														//-- we go search inside even if this folder name may not match the search pattern, it is a folder, except if dissalow addition from above
														$this->folder_iterator((bool)$recurring, (string)SmartFileSysUtils::addPathTrailingSlash((string)$dir_name.$file), (bool)$include_dot_files, (string)$search_pattern, (string)$search_prevent_file, (string)$search_prevent_override);
														//--
													} //end if
													//--
												} else {
													//--
													$this->num_files++;
													$this->num_files_size += $tmp_fsize;
													if($recurring) { // if recurring, add the full path
														$this->pattern_file_matches[$dir_name.$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} else { // if not recurring, add just base path, without dir name prefix
														$this->pattern_file_matches[$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} //end if else
													//--
												} //end if else
											} //end if
											//--
										} //end if
										//--
										$tmp_fsize = 0;
										$tmp_size_arr = array();
										//--
									} //end if else
									//--
								} //end if
								//--
							} //end if else
							//--
						} //end if
						//--
					} //end if (. ..)
					//--
				} //end for
				//---------------------------------------
				//@closedir($handle);
				//---------------------------------------
			} else {
				//---------------------------------------
				$this->errors_arr[] = (string) $dir_name;
				//---------------------------------------
			} //end else
			//--
		} else {
			//---------------------------------------
			// nothing ...
			//---------------------------------------
		} //end if else
		//--
		return;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
