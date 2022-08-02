<?php
// [LIB - Smart.Framework / FileSystem]
// (c) 2006-2022 unix-world.org - all rights reserved
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
if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHMOD_DIRS');
} //end if
if(
	(!is_int(SMART_FRAMEWORK_CHMOD_DIRS)) OR
	(!in_array((string)str_pad((string)decoct(SMART_FRAMEWORK_CHMOD_DIRS), 4, '0', STR_PAD_LEFT), [ '0700', '0750', '0755', '0770', '0775', '0777' ]))
) {
	@http_response_code(500);
	die('Invalid INIT constant value for SMART_FRAMEWORK_CHMOD_DIRS: '.SMART_FRAMEWORK_CHMOD_DIRS.' (decimal) / '.str_pad((string)decoct(SMART_FRAMEWORK_CHMOD_DIRS), 4, '0', STR_PAD_LEFT).' (octal)');
} //end if
//--
if(!defined('SMART_FRAMEWORK_CHMOD_FILES')) {
	@http_response_code(500);
	die('A required INIT constant has not been defined: SMART_FRAMEWORK_CHMOD_FILES');
} //end if
if(
	(!is_int(SMART_FRAMEWORK_CHMOD_DIRS)) OR
	(!in_array((string)str_pad((string)decoct(SMART_FRAMEWORK_CHMOD_FILES), 4, '0', STR_PAD_LEFT), [ '0600', '0640', '0644', '0660', '0664', '0666' ]))
) {
	@http_response_code(500);
	die('Invalid INIT constant value for SMART_FRAMEWORK_CHMOD_FILES: '.SMART_FRAMEWORK_CHMOD_FILES.' (decimal) / '.str_pad((string)decoct(SMART_FRAMEWORK_CHMOD_FILES), 4, '0', STR_PAD_LEFT).' (octal)');
} //end if
//--

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartFileSysUtils - provides the File System Util functions.
 *
 * This class enforces the use of RELATIVE PATHS to force using correct path access in a web environment application.
 * Relative paths must be relative to the web application folder as folder: `some-folder/` or file: `some-folder/my-file.txt`.
 * Absolute paths are denied by internal checks as they are NOT SAFE in a Web Application from the security point of view ...
 * Also the backward path access like `../some-file-or-folder` is denied from the above exposed reasons.
 * Files and Folders must contain ONLY safe characters as: `[a-z] [A-Z] [0-9] _ - . @ #` ; folders can also contain slashes `/` (as path separators); no spaces are allowed in paths !!
 *
 * NOTICE: To use paths in a safe manner, never add manually a / at the end of a path variable, because if it is empty will result in accessing the root of the file system (/).
 * To handle this in an easy and safe manner, use the function SmartFileSysUtils::add_dir_last_slash($my_dir) so it will add the trailing slash ONLY if misses but NOT if the $my_dir is empty to avoid root access !
 *
 * <code>
 *
 * // Usage example:
 * SmartFileSysUtils::some_method_of_this_class(...);
 *
 *  //-----------------------------------------------------------------------------------------------------
 *  //-----------------------------------------------------------------------------------------------------
 *  // SAFE REPLACEMENTS:
 *  // In order to supply a common framework for Unix / Linux but also on Windows,
 *  // because on Windows dir separator is \ instead of / the following functions must be used as replacements:
 *  //-----------------------------------------------------------------------------------------------------
 *  // Smart::real_path()        instead of:        realpath()
 *  // Smart::dir_name()         instead of:        dirname()
 *  // Smart::path_info()        instead of:        pathinfo()
 *  //-----------------------------------------------------------------------------------------------------
 *  // Also, when folders are get from external environments and are not certified if they have
 *  // been converted from \ to / on Windows, those paths have to be fixed using: Smart::fix_path_separator()
 *  //-----------------------------------------------------------------------------------------------------
 *
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20220406
 * @package 	@Core:FileSystem
 *
 */
final class SmartFileSysUtils {

	// ::


	//================================================================
	/**
	 * Return the MAXIMUM allowed Upload Size
	 *
	 * @return INTEGER								:: the Max Upload Size in Bytes
	 */
	public static function max_upload_size() {
		//--
		$inival = (string) trim((string)ini_get('upload_max_filesize'));
		if((string)$inival == '') {
			return 0;
		} //end if
		//--
		$last = (string) strtoupper((string)substr((string)$inival, -1, 1));
		$value = (int) intval((string)$inival);
		//--
		if((string)$last === 'K') { // kilo
			$value *= 1000;
		} elseif((string)$last === 'M') { // mega
			$value *= 1000 * 1000;
		} elseif((string)$last === 'G') { // giga
			$value *= 1000 * 1000 * 1000;
		} elseif((string)$last === 'T') { // tera
			$value *= 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'P') { // peta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000;
		/* the below unit of measures may overflow the max 64-bit integer value with higher values set in php.ini ... anyway there is no case to upload such large files ...
		} elseif((string)$last === 'E') { // exa
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'Z') { // zetta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		} elseif((string)$last === 'Y') { // yotta
			$value *= 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000 * 1000;
		*/
		} //end if else
		//--
		return (int) Smart::format_number_int($value, '+');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a Name of a File or Directory (not a path containing /) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe filenames or dirnames are accessed.
	 *
	 * @param 	STRING 	$y_fname 								:: The dirname or filename, (not path containing /) to validate
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function check_if_safe_file_or_dir_name($y_fname) {
		//-- test empty filename
		if((string)trim((string)$y_fname) == '') {
			return 0;
		} //end if else
		//-- test valid characters in filename or dirname (must not contain / (slash), it is not a path)
		if(!preg_match((string)Smart::REGEX_SAFE_FILE_NAME, (string)$y_fname)) { // {{{SYNC-CHK-SAFE-FILENAME}}}
			return 0;
		} //end if
		//-- test valid path (should pass all tests from valid, especially: must not be equal with: / or . or .. (and they are includded in valid path)
		if(self::test_valid_path($y_fname) !== 1) {
			return 0;
		} //end if
		//--
		if((int)strlen((string)$y_fname) > 255) {
			return 0;
		} //end if
		//--
		// IMPORTANT: it should not test if filenames or dirnames start with a # (protected) as they are not paths !!!
		//--
		return 1;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check a Path (to a Directory or to a File) if contain valid characters (to avoid filesystem path security injections)
	 * Security: provides check if unsafe paths are accessed.
	 *
	 * @param 	STRING 	$y_path 								:: The path (dir or file) to validate
	 * @param 	YES/NO 	$y_deny_absolute_path 					:: *Optional* If YES will dissalow absolute paths
	 * @param 	YES/NO 	$y_allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to YES except if you know what you are really doing ! If set to YES will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext ; for task area this is always hardcoded to 'yes' and cannot be overrided
	 *
	 * @return 	0/1												:: returns 1 if VALID ; 0 if INVALID
	 */
	public static function check_if_safe_path($y_path, $y_deny_absolute_path='yes', $y_allow_protected_relative_paths='no') { // {{{SYNC-FS-PATHS-CHECK}}}
		//-- override
		if(SmartFrameworkRegistry::isAdminArea() === true) {
			if(SmartFrameworkRegistry::isTaskArea() === true) {
				$y_allow_protected_relative_paths = 'yes'; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//-- dissalow empty paths
		if((string)trim((string)$y_path) == '') {
			return 0;
		} //end if else
		//-- test valid path
		if(self::test_valid_path($y_path) !== 1) {
			return 0;
		} //end if
		//-- test backward path
		if(self::test_backward_path($y_path) !== 1) {
			return 0;
		} //end if
		//-- test absolute path and protected path
		if((string)$y_deny_absolute_path != 'no') {
			if(self::test_absolute_path($y_path) !== 1) {
				return 0;
			} //end if
		} //end if
		//-- test protected path
		if((string)$y_allow_protected_relative_paths != 'yes') {
			if(self::test_special_path($y_path) !== 1) { // check protected path only if deny absolute path access, otherwise n/a
				return 0;
			} //end if
		} //end if
		//-- test max path length
		if(((int)strlen($y_path) > 1024) OR ((int)strlen($y_path) > (int)PHP_MAXPATHLEN)) { // IMPORTANT: this also protects against cycled loops that can occur when scanning linked folders
			return 0; // path is longer than the allowed path max length by PHP_MAXPATHLEN between 512 to 4096 (safe is 1024)
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ CHECK ABSOLUTE PATH ACCESS
	/**
	 * Function: Raise Error if Unsafe Path.
	 * Security: implements protection if unsafe paths are accessed.
	 *
	 * @param 	STRING 	$y_path 								:: The path (dir or file) to validate
	 * @param 	YES/NO 	$y_deny_absolute_path 					:: *Optional* If YES will dissalow absolute paths
	 * @param 	YES/NO 	$y_allow_protected_relative_paths 		:: *Optional* ! This is for very special case usage only so don't set it to YES except if you know what you are really doing ! If set to YES will allow access to special protected paths of this framework which may have impact on security ... ; this parameter is intended just for relative paths only (not absolute paths) as: #dir/.../file.ext ; #file.ext ; for task area this is always hardcoded to 'yes' and cannot be overrided
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function raise_error_if_unsafe_path($y_path, $y_deny_absolute_path='yes', $y_allow_protected_relative_paths='no') { // {{{SYNC-FS-PATHS-CHECK}}}
		//-- override
		if(SmartFrameworkRegistry::isAdminArea() === true) {
			if(SmartFrameworkRegistry::isTaskArea() === true) {
				$y_allow_protected_relative_paths = 'yes'; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//-- dissalow empty paths
		if((string)trim((string)$y_path) == '') {
			//--
			Smart::raise_error(
				'Smart.Framework // FileSystemUtils // Check Valid Path // EMPTY PATH IS DISALLOWED',
				'FileSysUtils: EMPTY PATH IS DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test valid path
		if(self::test_valid_path($y_path) !== 1) {
			//--
			Smart::raise_error(
				'Smart.Framework // FileSystemUtils // Check Valid Path // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: INVALID CHARACTERS IN PATH ARE DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test backward path
		if(self::test_backward_path($y_path) !== 1) {
			//--
			Smart::raise_error(
				'Smart.Framework // FileSystemUtils // Check Backward Path // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: BACKWARD PATH ACCESS IS DISALLOWED !' // msg to display
			);
			return;
			//--
		} //end if
		//-- test absolute path and protected path
		if((string)$y_deny_absolute_path != 'no') {
			if(self::test_absolute_path($y_path) !== 1) {
				//--
				Smart::raise_error(
					'Smart.Framework // FileSystemUtils // Check Absolute Path // ACCESS DENIED to invalid path: '.$y_path,
					'FileSysUtils: ABSOLUTE PATH ACCESS IS DISALLOWED !' // msg to display
				);
				return;
				//--
			} //end if
		} //end if
		//-- test protected path
		if((string)$y_allow_protected_relative_paths != 'yes') {
			if(self::test_special_path($y_path) !== 1) { // check protected path only if deny absolute path access, otherwise n/a
				//--
				Smart::raise_error(
					'Smart.Framework // FileSystemUtils // Check Protected Path // ACCESS DENIED to invalid path: '.$y_path,
					'FileSysUtils: PROTECTED PATH ACCESS IS DISALLOWED !' // msg to display
				);
				return;
				//--
			} //end if
		} //end if
		//-- test max path length
		if((int)strlen((string)$y_path) > 1024) {
			//--
			Smart::raise_error(
				'Smart.Framework // FileSystemUtils // Check Max Path Length (1024) // ACCESS DENIED to invalid path: '.$y_path,
				'FileSysUtils: PATH LENGTH IS EXCEEDING THE MAX ALLOWED LENGTH !' // msg to display
			);
			return;
			//--
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF SPECIAL PATH
	// special protected paths (only relative) start with '#'
	// returns 1 if OK
	private static function test_special_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if((string)substr((string)trim($y_path), 0, 1) == '#') {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF VALID PATH
	// test if path is valid ; on windows paths must use the / instead of backslash (and without drive letter prefix c:) to comply
	// path should not contain SPACE, BACKSLASH, :, |, ...
	// the : is denied also on unix because can lead to unpredictable paths behaviours
	// the | is denied because old MacOS is not supported
	// path should not be EMPTY or equal with: / . .. ./ ../ ./.
	// path should contain just these characters _ a-z A-Z 0-9 - . @ # /
	// returns 1 if OK
	private static function test_valid_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if(!preg_match((string)Smart::REGEX_SAFE_PATH_NAME, (string)$y_path)) { // {{{SYNC-SAFE-PATH-CHARS}}} {{{SYNC-CHK-SAFE-PATH}}} only ISO-8859-1 characters are allowed in paths (unicode paths are unsafe for the network environments !!!)
			return 0;
		} //end if
		//--
		if(
			((string)trim($y_path) == '') OR 							// empty path: error
			((string)trim($y_path) == '.') OR 							// special: protected
			((string)trim($y_path) == '..') OR 							// special: protected
			((string)trim($y_path) == '/') OR 							// root dir: security
			(strpos($y_path, ' ') !== false) OR 						// no space allowed
			(strpos($y_path, '\\') !== false) OR 						// no backslash allowed
			(strpos($y_path, '://') !== false) OR 						// no protocol access allowed
			(strpos($y_path, ':') !== false) OR 						// no dos/win disk access allowed
			(strpos($y_path, '|') !== false) OR 						// no macos disk access allowed
			((string)trim($y_path) == './') OR 							// this must not be used - dissalow FS operations to the app root path, enforce use relative paths such as path/to/something
			((string)trim($y_path) == '../') OR 						// backward path access denied: security
			((string)trim($y_path) == './.') OR 						// this is a risk that can lead to unpredictable results
			(strpos($y_path, '...') !== false) OR 						// this is a risk that can lead to unpredictable results
			((string)substr((string)trim($y_path), -2, 2) == '/.') OR 	// special: protected ; this may lead to rewrite/delete the special protected . in a directory if refered as a filename or dirname that may break the filesystem
			((string)substr((string)trim($y_path), -3, 3) == '/..')  	// special: protected ; this may lead to rewrite/delete the special protected .. in a directory if refered as a filename or dirname that may break the filesystem
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF BACKWARD PATH
	// test backpath or combinations against crafted paths to access backward paths on filesystem
	// will test only combinations allowed by test_valid_path()
	// returns 1 if OK
	private static function test_backward_path($y_path) {
		//--
		$y_path = (string) $y_path;
		//--
		if(
			(strpos($y_path, '/../') !== false) OR
			(strpos($y_path, '/./') !== false) OR
			(strpos($y_path, '/..') !== false) OR
			(strpos($y_path, '../') !== false)
		) {
			return 0;
		} //end if else
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ TEST IF ABSOLUTE PATH
	// test against absolute path access
	// will test only combinations allowed by test_valid_path() and test_backward_path()
	// the first character should not be / ; path must not contain :, :/
	// returns 1 if OK
	private static function test_absolute_path($y_path) {
		//--
		$y_path = (string) trim((string)$y_path);
		//--
		$c1 = (string) substr((string)$y_path, 0, 1);
		$c2 = (string) substr((string)$y_path, 1, 1);
		//--
		if(
			((string)$c1 == '/') OR // unix/linux # /path/to/
			((string)$c1 == ':') OR // windows # :/path/to/
			((string)$c2 == ':')    // windows # c:/path/to
		) {
			return 0;
		} //end if
		//--
		return 1; // valid
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Safe add a trailing slash to a path if not already have it, with safe detection and avoid root access.
	 *
	 * Adding a trailing slash to a path is not a simple task as if path is empty, adding the trailing slash will result in accessing the root file system as will be: /.
	 * Otherwise it have to detect if the trailing slash exists already to avoid double slash.
	 *
	 * @param 	STRING 	$y_path 					:: The path to add the trailing slash to
	 *
	 * @return 	STRING								:: The fixed path with a trailing
	 */
	public static function add_dir_last_slash($y_path) {
		//--
		$y_path = (string) trim((string)Smart::fix_path_separator((string)trim((string)$y_path)));
		//--
		if(((string)$y_path == '') OR ((string)$y_path == '.') OR ((string)$y_path == './')) {
			return './'; // this is a mandatory security fix for the cases when used with dirname() which may return empty or just .
		} //end if
		//--
		if(((string)$y_path == '/') OR ((string)trim((string)str_replace(['/', '.'], ['', ''], (string)$y_path)) == '') OR (strpos($y_path, '\\') !== false)) {
			Smart::log_warning(__METHOD__.'() // Add Last Dir Slash: Invalid Path: ['.$y_path.'] ; Returned TMP/INVALID/');
			return 'tmp/invalid/'; // Security Fix: avoid make the path as root: / (if the path is empty, adding a trailing slash is a huge security risk)
		} //end if
		//--
		if(substr($y_path, -1, 1) != '/') {
			$y_path = $y_path.'/';
		} //end if
		//--
		self::raise_error_if_unsafe_path($y_path, 'yes', 'yes'); // deny absolute paths ; allow #special paths
		//--
		return (string) $y_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates a Standard File Version based on Microtime Float to use with version add
	 * Ex: 1517576620.6128
	 *
	 * @return 	STRING								:: The version as string to be used with version add
	 */
	public static function version_stdmtime() {
		//--
		return (string) microtime(true);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Remove the version from a file name
	 * Ex: myfile.@1505240360@.ext will become: myfile.ext
	 *
	 * @param 	STRING 	$file 						:: The file name (with version or not) to be processed
	 *
	 * @return 	STRING								:: The fixed file name without the version
	 */
	public static function version_remove($file) {
		//--
		$file = (string) $file;
		//--
		if((strpos($file, '.@') !== false) AND (strpos($file, '@.') !== false)) {
			//--
			$arr = (array) explode('.@', (string)$file);
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			$arr2 = (array) explode('@.', (string)$arr[1]);
			if(!array_key_exists(0, $arr2)) {
				$arr2[0] = null;
			} //end if
			if(!array_key_exists(1, $arr2)) {
				$arr2[1] = null;
			} //end if
			//--
			if((string)trim((string)$arr[0]) == '') {
				$arr[0] = '_empty-filename_';
			} //end if
			if(((string)$arr2[1] === '_no-ext_') OR ((string)$arr2[1] === '')) { // {{{SYNC-FILE-VER-NOEXT}}}
				$file = (string) $arr[0];
			} else {
				$file = (string) $arr[0].'.'.$arr2[1];
			} //end if else
			//--
		} //end if
		//--
		return (string) $file;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Add the version to a file name.
	 * Ex: myfile.ext will become: myfile.@1505240360@.ext
	 *
	 * @param 	STRING 	$file 						:: The file name (with version or not) to be processed ; if version detected will be preserved
	 * @param 	STRING 	$version 					:: The version to be added
	 *
	 * @return 	STRING								:: The fixed file name with a version
	 */
	public static function version_add($file, $version) {
		//--
		$version = (string) trim(strtolower(str_replace(array('.', '@'), array('', ''), Smart::safe_validname((string)$version))));
		if((string)$version == '') {
			return (string) $file;
		} //end if
		//--
		$file = (string) self::version_remove((string)trim((string)$file));
		//--
		$file_no_ext = (string) self::get_noext_file_name_from_path($file); // fix: removed strtolower()
		$file_ext = (string) self::get_file_extension_from_path($file); // fix: removed strtolower()
		//--
		if((string)$file_ext == '') { // because file versioning expects a valid file extension, to avoid break when version remove will find no extension and would consider the version as extension, add something as extension
			$file_ext = '_no-ext_'; // {{{SYNC-FILE-VER-NOEXT}}}
		} //end if
		//--
		return (string) $file_no_ext.'.@'.$version.'@.'.$file_ext;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the version from a file name.
	 *
	 * @param 	STRING 	$file 						:: The file name to be processed
	 *
	 * @return 	STRING								:: The version from filename or ''
	 */
	public static function version_get($file) {
		//--
		$file = (string) $file;
		//--
		$version = '';
		//--
		if((strpos($file, '.@') !== false) AND (strpos($file, '@.') !== false)) {
			//--
			$arr = (array) explode('.@', (string)$file);
			if(!array_key_exists(0, $arr)) {
				$arr[0] = null;
			} //end if
			if(!array_key_exists(1, $arr)) {
				$arr[1] = null;
			} //end if
			//--
			$arr2 = (array) explode('@.', (string)$arr[1]);
			if(!array_key_exists(0, $arr2)) {
				$arr2[0] = null;
			} //end if
			if(!array_key_exists(1, $arr2)) {
				$arr2[1] = null;
			} //end if
			//--
			$version = (string) $arr2[0];
			//--
		} //end if
		//--
		return (string) $version;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Check the version from a file name.
	 *
	 * @param 	STRING 	$file 						:: The file name to be checked
	 * @param 	STRING 	$version 					:: The version to be checked
	 *
	 * @return 	0/1									:: returns 1 if the version is detected ; otherwise returns 0 if version not detected
	 */
	public static function version_check($file, $version) {
		//--
		$file = (string) trim((string)$file);
		$version = (string) trim(strtolower(str_replace(array('.', '@'), array('', ''), Smart::safe_validname((string)$version))));
		//--
		if(stripos($file, '.@'.$version.'@.') !== false) {
			return 1;
		} else {
			return 0;
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the folder name from a path (except last trailing slash: /)
	 *
	 * @param STRING 	$ypath						:: the path (dir or file)
	 * @return STRING 								:: a directory path [FOLDER NAME]
	 */
	public static function get_dir_from_path($y_path) {
		//--
		$y_path = (string) Smart::safe_pathname($y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)Smart::safe_pathname((string)$arr['dirname'])); // this may contain /
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file name (includding extension) from path
	 * WARNING: path_info('c:\\file.php') will not work correct on unix, but on windows will work correct both: path_info('c:\\file.php') and path_info('path/file.php'
	 * @param STRING 		$ypath		path or file
	 * @return STRING 				[FILE NAME]
	 */
	public static function get_file_name_from_path($y_path) {
		//--
		$y_path = (string) Smart::safe_pathname($y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)Smart::safe_filename((string)$arr['basename']));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file name (WITHOUT extension) from path
	 *
	 * @param STRING 		$ypath		path or file
	 * @return STRING 				[FILE NAME]
	 */
	public static function get_noext_file_name_from_path($y_path) {
		//--
		$y_path = (string) Smart::safe_pathname($y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		$tmp_ext = (string) $arr['extension'];
		$tmp_file = (string) $arr['basename'];
		//--
		$str_len = (int) ((int)strlen($tmp_file) - (int)strlen($tmp_ext) - 1);
		//--
		if((int)strlen($tmp_ext) > 0) {
			// with .extension
			$tmp_xfile = (string) substr((string)$tmp_file, 0, (int)$str_len);
		} else {
			// no extension
			$tmp_xfile = (string) $tmp_file;
		} //end if else
		//--
		return (string) trim((string)Smart::safe_filename((string)$tmp_xfile));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Return the file extension (without .) from path
	 *
	 * @param STRING 		$ypath		path or file
	 * @return STRING 					[FILE EXTENSION]
	 */
	public static function get_file_extension_from_path($y_path) {
		//--
		$y_path = (string) Smart::safe_pathname($y_path);
		//--
		if((string)$y_path == '') {
			return '';
		} //end if
		//--
		$arr = (array) Smart::path_info((string)$y_path);
		//--
		return (string) trim((string)strtolower((string)Smart::safe_filename((string)$arr['extension'])));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a prefixed dir from a base36 UUID, 10 chars length : [0-9A-Z].
	 * It does include also the UUID as final folder segment.
	 * Example: for ID ABCDEFGHIJ09 will return: 9T/5B/0B/9M/9T5B0B9M8M/ as the generated prefixed path.
	 * This have to be used for large folder storage structure to avoid limitations on some filesystems (ext3 / ntfs) where max sub-dirs per dir is 32k.
	 *
	 * The prefixed path will be grouped by each 2 characters (max sub-folders per folder: 36 x 36 = 1296).
	 * If a lower length than 10 chars is provided will pad with 0 on the left.
	 * If a higher length or an invalid ID is provided will reset the ID to 000000..00 (10 chars) for the given length, but also drop a warning.
	 *
	 * @param STRING 		$y_id		10 chars id (uuid10)
	 * @return STRING 					Prefixed Path
	 */
	public static function prefixed_uuid10_dir($y_id) { // check len is default 10 as set in lib core uuid 10s
		//--
		$y_id = (string) strtoupper((string)trim((string)$y_id));
		//--
		if(((int)strlen((string)$y_id) != 10) OR (!preg_match('/^[A-Z0-9]+$/', (string)$y_id))) {
			Smart::log_warning(__METHOD__.'() // Prefixed Path UID10(B36) // Invalid ID ['.$y_id.']');
			$y_id = '0000000000'; // str-10.B36 (uuid10)
		} //end if
		//--
		$dir = (string) self::add_dir_last_slash(self::add_dir_last_slash((string)implode('/', (array)str_split((string)substr((string)$y_id, 0, 8), 2))).$y_id); // split by 2 grouping except last 2 chars
		//--
		if(!self::check_if_safe_path($dir)) {
			Smart::log_warning(__METHOD__.'() // Prefixed Path UID10(B36) // Invalid Dir Path: ['.$dir.'] :: From ID: ['.$y_id.']');
			return 'tmp/invalid/pfx-b36uid-path/'; // this error should not happen ...
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a prefixed dir from a base16 UUID (sha1), 40 chars length : [0-9a-f].
	 * It does NOT include the ID final folder.
	 * Example: for ID df3a808b2bf20aaab4419c43d9f3a6143bd6b4bb will return: d/f3a/808/b2b/f20/aaa/b44/19c/43d/9f3/a61/43b/d6b/ as the generated prefixed path.
	 * This have to be used for large folder storage structure to avoid limitations on some filesystems (ext3 / ntfs) where max sub-dirs per dir is 32k.
	 *
	 * The prefixed folder will be grouped by each 3 characters (max sub-folders per folder: 16 x 16 x 16 = 4096).
	 * If a lower length than 40 chars is provided will pad with 0 on the left.
	 * If a higher length than 40 chars or an invalid ID is provided will reset the ID to 000000..00 (40 chars) for the given length, but also drop a warning.
	 *
	 * @param STRING 		$y_id		40 chars id (sha1)
	 * @return STRING 					Prefixed Path
	 */
	public static function prefixed_sha1_path($y_id) { // here the number of levels does not matter too much as at the end will be a cache file
		//--
		$y_id = (string) strtolower((string)trim((string)$y_id));
		//--
		if(((int)strlen((string)$y_id) != 40) OR (!preg_match('/^[a-f0-9]+$/', (string)$y_id))) {
			Smart::log_warning(__METHOD__.'() // Prefixed Path SHA1-40(B16) // Invalid ID ['.$y_id.']');
			$y_id = '0000000000000000000000000000000000000000'; // str-40.hex (sha1)
		} //end if
		//--
		$dir = (string) self::add_dir_last_slash((string)substr((string)$y_id, 0, 1).'/'.implode('/', (array)str_split((string)substr((string)$y_id, 1, 36), 3))); // split by 3 grouping
		//--
		if(!self::check_if_safe_path($dir)) {
			Smart::log_warning(__METHOD__.'() // Prefixed Path SHA1-40(B16) // Invalid Dir Path: ['.$dir.'] :: From ID: ['.$y_id.']');
			return 'tmp/invalid/pfx-b16sha-path/'; // this error should not happen ...
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Evaluate and return the File MimeType (and *Optional* the Disposition by File Extension).
	 *
	 * @param STRING 		$yfile			the file name (includding file extension) ; Ex: file.ext
	 * @param ENUM 			$ydisposition 	STRING: '' to leave as is ; attachment | inline to force a type ; BOOLEAN: FALSE to get just Mime Type
	 * @return MIXED 						ARRAY [ 0 => mime type ; 1 => inline/attachment; filename="file.ext" ; 2 => inline/attachment ] ; IF $ydisposition == FALSE will return STRING: mime type
	 */
	public static function mime_eval($yfile, $ydisposition='') {
		//--
		$yfile = (string) Smart::safe_pathname((string)$yfile);
		//--
		$file = (string) self::get_file_name_from_path((string)$yfile); // bug fixed: if a full path is sent, try to get just the file name to return
		$lfile = (string) strtolower((string)$file);
		//--
		if(in_array((string)$lfile, [ // all lowercase as file is already strtolower
			'#release',
			'license',
			'license-bsd',
			'license-gplv3',
			'changelog',
			'changes',
			'readme',
			'makefile',
			'cmake',
			'meson.build',
			'go.mod', // go module
			'go.sum', // go checksum
			'mime.types', // apache
			'magic', // apache
			'manifest', // github
			'exports',
			'fstab',
			'group',
			'hosts',
			'machine-id',
			'.htaccess',
			'.htpasswd',
			'.gitignore',
			'.gitattributes',
			'.gitmodules',
			'.properties',
		])) {
			$extension = (string) $lfile;
		} else {
			$extension = (string) strtolower((string)self::get_file_extension_from_path((string)$yfile)); // [OK]
		} //end if else
		//--
		switch((string)$extension) {
			//-------------- html / css
			case 'htm':
			case 'html':
			case 'mtpl': // marker tpl templating
			case 'tpl':  // tpl templating
			case 'twist': // tpl twist
			case 'twig': // twig templating
			case 't3fluid': // typo3 fluid templating
			case 'django': // django templating
				$type = 'text/html';
				$disp = 'attachment';
				break;
			case 'css':
			case 'less':
			case 'scss':
			case 'sass':
				$type = 'text/css';
				$disp = 'attachment';
				break;
			//-------------- php
			case 'php':
				$type = 'application/x-php';
				$disp = 'attachment';
				break;
			//-------------- javascript
			case 'js':
				$type = 'application/javascript';
				$disp = 'attachment';
				break;
			case 'json':
				$type = 'application/json';
				$disp = 'attachment';
				break;
			//-------------- xml
			case 'xhtml':
			case 'xml':
			case 'xsl':
			case 'dtd':
			case 'sgml': // Standard Generalized Markup Language
			case 'glade': // glade UI XML file
			case 'ui': // qt ui XML file
				$type = 'application/xml';
				$disp = 'attachment';
				break;
			//-------------- plain text and development
			case 'tex': // TeX
			case 'txt': // text
			case 'log': // log file
			case 'sql': // sql file
			case 'cf': // config file
			case 'cfg': // config file
			case 'conf': // config file
			case 'config': // config file
			case 'sh': // shell script
			case 'bash': // bash (shell) script
			case 'awk': // AWK script
			case 'll': // llvm IR assembler
			case 's': // llvm IR assembler
			case 'asm': // assembler (x86)
			case 'aasm': // assembler (arm)
			case 'masm': // assembler (mips)
			case 'cmd': // windows command file
			case 'bat': // windows batch file
			case 'ps1': // windows powershell
			case 'psm1': // windows powershell
			case 'psd1': // windows powershell
			case 'asp': // active server page
			case 'csharp': // C#
			case 'cs': // C#
			case 'm': // Objective C Method
			case 'c': // C
			case 'h': // C header
			case 'y': // Yacc source code file
			case 'f': // Fortran
			case 'fs': // Fortran Sharp
			case 'fsharp': // Fortran Sharp
			case 'r': // R language
			case 'd': // D language
			case 'diff': // Diff File
			case 'patch': // Diff Patch
			case 'pro': // QT project file
			case 'cpp': // C++
			case 'hpp': // C++ header
			case 'ypp': // Bison source code file
			case 'cxx': // C++
			case 'hxx': // C++ header
			case 'yxx': // Bison source code file
			case 'csh': // C-Shell script
			case 'tcl': // TCL
			case 'tk': // Tk
			case 'lua': // Lua
			case 'gjs': // gnome js
			case 'toml': // Tom's Obvious, Minimal Language (used with Cargo / Rust definitions)
			case 'rs': // Rust Language
			case 'go': // Go Lang
			case 'go.mod': // Go Module
			case 'go.sum': // Go Module Checksum
			case 'coffee': // Coffee Script
			case 'cson': // Coffee Script
			case 'ocaml': // Ocaml
			case 'ml': // Ocaml ML
			case 'mli': // Ocaml MLI (plain signature)
			case 'erl': // Erlang
			case 'hrl': // Erlang macro
			case 'pl': // perl
			case 'pm': // perl module
			case 'py': // python
			case 'phps': // php source, assign text/plain !
			case 'php3': // php3 source, assign text/plain !
			case 'php4': // php4 source, assign text/plain !
			case 'php5': // php5 source, assign text/plain !
			case 'php6': // n/a ; php6 source, assign text/plain !
			case 'php7': // php7 source, assign text/plain !
			case 'php8': // php8 source, assign text/plain !
			case 'php9': // php9 source, assign text/plain !
			case 'hh': // hip-hop (a kind of PHP for HipHop VM)
			case 'swift': // apple swift language
			case 'vala': // vala language
			case 'vapi': // vala vapi
			case 'deps': // vala deps
			case 'hx': // haxe
			case 'hxml': // haxe compiler arguments
			case 'hs': // haskell
			case 'lhs': // haskell literate
			case 'jsp': // java server page (html + syntax)
			case 'java': // java source code
			case 'groovy': // apache groovy language
			case 'gvy': // apache groovy language
			case 'gy': // apache groovy language
			case 'gsh': // apache groovy language, shell script
			case 'kotlin': // kotlin language
			case 'kt': // kotlin language
			case 'ktm': // kotlin language module
			case 'kts': // kotlin language script, shell script
			case 'scala': // Scala
			case 'sc': // scala
			case 'gradle': // automation tool for java like languages
			case 'pas': // Delphi / Pascal
			case 'as': // action script
			case 'ts': // type script
			case 'tsx': // type script
			case 'basic': // Basic
			case 'bas': // basic
			case 'vb': // visual basic - vbnet
			case 'vbs': // visual basic script - vbnet
			case 'openscad': // openscad
			case 'jscad': // openscad (js version)
			case 'scad': // openscad
			case 'stl': // openscad
			case 'obj': // openscad
			case 'inc': // include file
			case 'ins': // install config file
			case 'inf': // info file
			case 'ini': // ini file
			case 'yml': // yaml file
			case 'yaml': // yaml file
			case 'md': // markdown
			case 'markdown': // markdown
			case 'protobuf': // protocol buffers
			case 'pb': // protocol buffers
			case 'vhd': // vhdl
			case '#release': // release
			case 'license': // license
			case 'license-bsd': // license
			case 'license-gplv3': // license
			case 'changelog': // changelog
			case 'changes': // changes
			case 'readme': // license
			case 'makefile': // makefile
			case 'cmake': // cmake file
			case 'meson.build': // meson build file
			case 'mime.types': // apache
			case 'magic': // apache
			case 'manifest': // github
			case 'exports': // linux exports
			case 'fstab': // linux fstab
			case 'group': // linux group
			case 'hosts': // linux hosts
			case 'machine-id': // openbsd machine-id
			case '.htaccess': // .htaccess
			case '.htpasswd': // .htpasswd
			case '.gitignore': // git ignore
			case '.gitattributes': // git attributes
			case '.gitmodules': // git modules
			case '.properties': // properties file
			case 'pem': // PEM Certificate File
			case 'crl': // Certificate Revocation List
			case 'crt': // Certificate File
			case 'key': // Certificate Key File
			case 'keys': // Bind DNS keys
			case 'dns': // DNS Config
			case 'csp': // Content Security Policy
			case 'httph': // HTTP Header
			case 'dist': // .dist files are often configuration files which do not contain the real-world deploy-specific parameters
			case 'lock': // ex: yarn.lock
				$type = 'text/plain';
				$disp = 'attachment';
				break;
			//-------------- web images
			case 'svg':
				$type = 'image/svg+xml';
				$disp = 'inline';
				break;
			case 'png':
				$type = 'image/png';
				$disp = 'inline';
				break;
			case 'gif':
				$type = 'image/gif';
				$disp = 'inline';
				break;
			case 'jpg':
			case 'jpe':
			case 'jpeg':
				$type = 'image/jpeg';
				$disp = 'inline';
				break;
			case 'webp':
				$type = 'image/webp';
				$disp = 'inline';
				break;
			//-------------- other images
			case 'tif':
			case 'tiff':
				$type = 'image/tiff';
				$disp = 'attachment';
				break;
			case 'wmf':
				$type = 'application/x-msmetafile';
				$disp = 'attachment';
				break;
			case 'bmp':
				$type = 'image/bmp';
				$disp = 'attachment';
				break;
			//-------------- fonts
			case 'ttf':
				$type = 'application/x-font-ttf';
				$disp = 'attachment';
				break;
			case 'woff':
				$type = 'application/x-font-woff';
				$disp = 'attachment';
				break;
			case 'woff2':
				$type = 'application/x-font-woff2';
				$disp = 'attachment';
				break;
			//-------------- portable documents
			case 'pdf':
				$type = 'application/pdf';
				$disp = 'inline'; // 'attachment';
				break;
			case 'xfdf':
				$type = 'application/vnd.adobe.xfdf';
				$disp = 'attachment';
				break;
			case 'epub':
				$type = 'application/epub+zip';
				$disp = 'attachment';
				break;
			//-------------- email / calendar / addressbook
			case 'eml':
				$type = 'message/rfc822';
				$disp = 'attachment';
				break;
			case 'ics':
				$type = 'text/calendar';
				$disp = 'attachment';
				break;
			case 'vcf':
				$type = 'text/x-vcard';
				$disp = 'attachment';
				break;
			case 'vcs':
				$type = 'text/x-vcalendar';
				$disp = 'attachment';
				break;
			case 'ldif':
				$type = 'text/ldif';
				$disp = 'attachment';
				break;
			//-------------- data
			case 'csv': // csv comma
			case 'tab': // csv tab
				$type = 'text/csv';
				$disp = 'attachment';
				break;
			//-------------- specials
			case 'asc':
			case 'sig':
				$type = 'application/pgp-signature';
				$disp = 'attachment';
				break;
			case 'curl':
				$type = 'application/vnd.curl';
				$disp = 'attachment';
				break;
			//-------------- graphics
			case 'psd': // photoshop file
			case 'xcf': // gimp file
				$type = 'image/x-xcf';
				$disp = 'attachment';
				break;
			case 'ai': // illustrator file
			case 'eps':
			case 'ps':
				$type = 'application/postscript';
				$disp = 'attachment';
				break;
			//-------------- web video
			case 'ogg': // theora audio
			case 'oga':
				$type = 'audio/ogg';
				$disp = 'inline';
				break;
			case 'ogv': // theora video
				$type = 'video/ogg';
				$disp = 'inline';
				break;
			case 'webm': // google vp8
				$type = 'video/webm';
				$disp = 'inline';
				break;
			//-------------- other video
			case 'mpeg':
			case 'mpg':
			case 'mpe':
			case 'mpv':
			case 'mp4':
				$type = 'video/mpeg';
				$disp = 'attachment';
				break;
			case 'mpga':
			case 'mp2':
			case 'mp3':
			case 'mp4a':
				$type = 'audio/mpeg';
				$disp = 'attachment';
				break;
			case 'qt':
			case 'mov':
				$type = 'video/quicktime';
				$disp = 'attachment';
				break;
			case 'flv':
				$type = 'video/x-flv';
				$disp = 'attachment';
				break;
			case 'avi':
				$type = 'video/x-msvideo';
				$disp = 'attachment';
				break;
			case 'wm':
			case 'wmv':
			case 'wmx':
			case 'wvx':
				$type = 'video/x-ms-'.$extension;
				$disp = 'attachment';
				break;
			//-------------- flash
			case 'swf':
				$type = 'application/x-shockwave-flash';
				$disp = 'attachment';
				break;
			//-------------- rich text
			case 'rtf':
				$type = 'application/rtf';
				$disp = 'attachment';
				break;
			case 'abw': // Abi Word
				$type = 'application/x-abiword';
				$disp = 'attachment';
				break;
			//-------------- openoffice / libreoffice
			case 'odc':
				$type = 'application/vnd.oasis.opendocument.chart';
				$disp = 'attachment';
				break;
			case 'otc':
				$type = 'application/vnd.oasis.opendocument.chart-template';
				$disp = 'attachment';
				break;
			case 'odf':
			case 'sxm':
				$type = 'application/vnd.oasis.opendocument.formula';
				$disp = 'attachment';
				break;
			case 'otf':
				$type = 'application/vnd.oasis.opendocument.formula-template';
				$disp = 'attachment';
				break;
			case 'odg':
			case 'fodg':
			case 'sxd':
				$type = 'application/vnd.oasis.opendocument.graphics';
				$disp = 'attachment';
				break;
			case 'otg':
				$type = 'application/vnd.oasis.opendocument.graphics-template';
				$disp = 'attachment';
				break;
			case 'odi':
				$type = 'application/vnd.oasis.opendocument.image';
				$disp = 'attachment';
				break;
			case 'oti':
				$type = 'application/vnd.oasis.opendocument.image-template';
				$disp = 'attachment';
				break;
			case 'odp':
			case 'fodp':
			case 'sxi':
				$type = 'application/vnd.oasis.opendocument.presentation';
				$disp = 'attachment';
				break;
			case 'otp':
			case 'sti':
				$type = 'application/vnd.oasis.opendocument.presentation-template';
				$disp = 'attachment';
				break;
			case 'ods':
			case 'fods':
			case 'sxc':
				$type = 'application/vnd.oasis.opendocument.spreadsheet';
				$disp = 'attachment';
				break;
			case 'ots':
			case 'stc':
				$type = 'application/vnd.oasis.opendocument.spreadsheet-template';
				$disp = 'attachment';
				break;
			case 'odt':
			case 'fodt':
			case 'sxw':
				$type = 'application/vnd.oasis.opendocument.text';
				$disp = 'attachment';
				break;
			case 'ott':
			case 'stw':
				$type = 'application/vnd.oasis.opendocument.text-template';
				$disp = 'attachment';
				break;
			case 'otm':
				$type = 'application/vnd.oasis.opendocument.text-master';
				$disp = 'attachment';
				break;
			case 'oth':
				$type = 'application/vnd.oasis.opendocument.text-web';
				$disp = 'attachment';
				break;
			case 'odb':
				$type = 'application/vnd.oasis.opendocument.database';
				$disp = 'attachment';
				break;
			//-------------- ms office
			case 'doc':
			case 'dot':
				$type = 'application/msword';
				$disp = 'attachment';
				break;
			case 'docx':
			case 'dotx':
				$type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				$disp = 'attachment';
				break;
			case 'xla':
			case 'xlc':
			case 'xlm':
			case 'xls':
			case 'xlt':
			case 'xlw':
				$type = 'application/vnd.ms-excel';
				$disp = 'attachment';
				break;
			case 'xlsx':
			case 'xltx':
				$type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				$disp = 'attachment';
				break;
			case 'pot':
			case 'pps':
			case 'ppt':
				$type = 'application/vnd.ms-powerpoint';
				$disp = 'attachment';
				break;
			case 'potx':
			case 'ppsx':
			case 'pptx':
				$type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
				$disp = 'attachment';
				break;
			case 'mdb':
				$type = 'application/x-msaccess';
				$disp = 'attachment';
				break;
			//-------------- archives
			case '7z':
				$type = 'application/x-7z-compressed';
				$disp = 'attachment';
				break;
			case 'xz':
				$type = 'application/x-xz';
				$disp = 'attachment';
				break;
			case 'tar':
				$type = 'application/x-tar';
				$disp = 'attachment';
				break;
			case 'tgz':
			case 'tbz':
				$type = 'application/x-compressed';
				$disp = 'attachment';
				break;
			case 'gz':
				$type = 'application/x-gzip';
				$disp = 'attachment';
				break;
			case 'bz2':
				$type = 'application/x-bzip2';
				$disp = 'attachment';
				break;
			case 'z':
				$type = 'application/x-compress';
				$disp = 'attachment';
				break;
			case 'zip':
				$type = 'application/zip';
				$disp = 'attachment';
				break;
			case 'rar':
				$type = 'application/x-rar-compressed';
				$disp = 'attachment';
				break;
			case 'sit':
				$type = 'application/x-stuffit';
				$disp = 'attachment';
				break;
			//-------------- executables
			case 'exe':
			case 'msi':
			case 'dll':
			case 'com':
				$type = 'application/x-msdownload';
				$disp = 'attachment';
				break;
			//-------------- others, default
			default:
				$type = 'application/octet-stream';
				$disp = 'attachment';
			//--------------
		} //end switch
		//--
		if($ydisposition === false) {
			//--
			return (string) $type; // mime type
			//--
		} else {
			//--
			switch((string)$ydisposition) {
				case 'inline':
					$disp = 'inline'; // rewrite display mode
					break;
				case 'attachment':
					$disp = 'attachment'; // rewrite display mode
					break;
				default:
					// nothing
			} //end switch
			//--
			return array(
				(string) $type, // mime type
				(string) $disp.'; filename="'.Smart::safe_filename((string)$file, '-').'"', // mime header disposition suffix
				(string) $disp // mime disposition
			);
			//--
		} //end if else
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
 * @depends 	classes: Smart ; constants: SMART_FRAMEWORK_CHMOD_DIRS, SMART_FRAMEWORK_CHMOD_FILES
 * @version 	v.20220729
 * @package 	@Core:FileSystem
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
	public static function fix_dir_chmod($dir_name) {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			Smart::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return false;
		} //end if
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)trim((string)$dir_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty DirName');
			return false;
		} //end if
		if(!self::is_type_dir($dir_name)) { // not a dir
			Smart::log_warning(__METHOD__.'() // Skip: Not a Directory Type: '.$dir_name);
			return false;
		} //end if
		if(self::is_type_link($dir_name)) { // skip links !!
			return true;
		} //end if
		//--
		$chmod = (bool) @chmod($dir_name, SMART_FRAMEWORK_CHMOD_DIRS);
		if(!$chmod) {
			Smart::log_warning(__METHOD__.'() // Failed to CHMOD ('.SMART_FRAMEWORK_CHMOD_DIRS.') a Directory: '.$dir_name);
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
	public static function fix_file_chmod($file_name) {
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_FILES')) {
			Smart::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_FILES) has not been defined ...');
			return false;
		} //end if
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return false;
		} //end if
		if(!self::is_type_file($file_name)) { // not a file
			Smart::log_warning(__METHOD__.'() // Skip: Not a File Type: '.$file_name);
			return false;
		} //end if
		if(self::is_type_link($file_name)) { // skip links !!
			return true;
		} //end if
		//--
		$chmod = (bool) @chmod($file_name, SMART_FRAMEWORK_CHMOD_FILES);
		if(!$chmod) {
			Smart::log_warning(__METHOD__.'() // Failed to CHMOD ('.SMART_FRAMEWORK_CHMOD_FILES.') a File: '.$file_name);
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
	public static function get_file_size($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists($file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filesize((string)$file_name); // should return INTEGER as some comparisons may fail if casted type
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
	public static function get_file_ctime($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists($file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filectime((string)$file_name); // should return INTEGER as some comparisons may fail if casted type
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
	public static function get_file_mtime($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return 0;
		} //end if
		if(!self::path_real_exists($file_name)) {
			return 0;
		} //end if
		//--
		return (int) @filemtime((string)$file_name); // should return INTEGER as some comparisons may fail if casted type
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
	public static function get_file_md5_checksum($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)trim((string)$file_name) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty FileName');
			return '';
		} //end if
		if(!self::path_real_exists($file_name)) {
			return '';
		} //end if
		//--
		return (string) @md5_file((string)$file_name); // should return STRING
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
	public static function is_type_dir($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_dir($path);
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
	public static function is_type_file($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_file($path);
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
	public static function is_type_link($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_link($path);
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
	public static function have_access_read($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_readable($path);
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
	public static function have_access_write($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_writable($path);
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
	public static function have_access_executable($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) is_executable($path);
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
	public static function path_exists($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		if((file_exists($path)) OR (is_link($path))) { // {{{SYNC-SF-PATH-EXISTS}}}
			return true;
		} else {
			return false;
		} //end if else
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
	public static function path_real_exists($path) {
		//--
		$path = (string) $path;
		//--
		if((string)trim((string)$path) == '') {
			Smart::log_warning(__METHOD__.'() // Skip: Empty Path');
			return false;
		} //end if
		//--
		clearstatcache(true, $path);
		//--
		return (bool) file_exists($path); // checks if a file or directory exists (but this is not safe with symlinks as if a symlink is broken will return false ...)
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
	 * @param 	BOOLEAN 	$safelock 				:: DEFAULT is 'no' ; If 'yes' will try to get a read shared lock on file prior to read ; If cannot lock the file will return empty string to avoid partial content read where reading a file that have intensive writes (there is always a risk to cannot achieve the lock ... there is no perfect scenario for intensive file operations in multi threaded environments ...)
	 *
	 * @return 	STRING								:: The file contents (or a part of file contents if $file_len parameter is used) ; if the file does not exists will return an empty string
	 */
	public static function read($file_name, $file_len=0, $markchmod='no', $safelock='no') {
		//--
		$file_name = (string) $file_name;
		$file_len = (int) $file_len;
		$markchmod = (string) $markchmod; // no/yes
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // ReadFile // Empty File Name');
			return '';
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		$fcontent = '';
		//--
		if(SmartFileSysUtils::check_if_safe_path($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if(self::is_type_file($file_name)) {
					//--
					if((string)$markchmod == 'yes') {
						self::fix_file_chmod($file_name); // force chmod
					} elseif(!self::have_access_read($file_name)) {
						self::fix_file_chmod($file_name); // try to make ir readable by applying chmod
					} //end if
					//--
					if(!self::have_access_read($file_name)) {
						Smart::log_warning(__METHOD__.'() // ReadFile // A file is not readable: '.$file_name);
						return '';
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
						if($file_len > 0) {
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
						Smart::log_warning(__METHOD__.'() // ReadFile // Failed to read the file: '.$file_name);
						$fcontent = '';
					} //end if
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.'() // ReadFile // Invalid FileName to read: '.$file_name);
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
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write($file_name, $file_content='', $write_mode='w') {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // Write: Empty File Name');
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		$result = false;
		//--
		if(SmartFileSysUtils::check_if_safe_path($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if(self::is_type_link($file_name)) {
					if(!self::path_real_exists($file_name)) {
						self::delete($file_name); // delete the link if broken
					} //end if
				} //end if
				//--
				if(self::is_type_file($file_name)) {
					if(!self::have_access_write($file_name)) {
						self::fix_file_chmod($file_name); // apply chmod first to be sure file is writable
					} //end if
					if(!self::have_access_write($file_name)) {
						Smart::log_warning(__METHOD__.'() // WriteFile // A file is not writable: '.$file_name);
						return 0;
					} //end if
				} //end if
				//-- fopen/fwrite method lacks the real locking which can be achieved just with flock which is not as safe as doing at once with: file_put_contents
				if((string)$write_mode == 'a') { // a (append, binary safe)
					$result = @file_put_contents($file_name, (string)$file_content, FILE_APPEND | LOCK_EX);
				} else { // w (write, binary safe)
					$result = @file_put_contents($file_name, (string)$file_content, LOCK_EX);
				} //end if else
				//--
				if(self::is_type_file($file_name)) {
					self::fix_file_chmod($file_name); // apply chmod afer write (fix as the file create chmod may be different !!)
					if(!self::have_access_write($file_name)) {
						Smart::log_warning(__METHOD__.'() // WriteFile // A file is not writable: '.$file_name);
					} //end if
				} //end if
				//-- check the write result (number of bytes written)
				if($result === false) {
					Smart::log_warning(__METHOD__.'() // WriteFile // Failed to write a file: '.$file_name);
				} else {
					if($result !== @strlen((string)$file_content)) {
						Smart::log_warning(__METHOD__.'() // WriteFile // A file was not completely written (removing it ...): '.$file_name);
						@unlink($file_name); // delete the file, was not completely written (do not use self::delete here, the file is still locked !)
					} //end if
				} //end if
				//--
			} else {
				//--
				Smart::log_warning(__METHOD__.'() // WriteFile // Failing to write file as this is a type Directory: '.$file_name);
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
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function write_if_not_exists($file_name, $file_content, $y_chkcompare='no') {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_content == '') {
			$y_chkcompare = 'no'; // fix: without this will not write the file !
		} //end if
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // WriteIfNotExists: Empty File Name');
			return 0;
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		//--
		$x_ok = 0;
		//--
		if((string)$y_chkcompare == 'yes') {
			//--
			if((string)self::read($file_name) != (string)$file_content) { // compare content
				$x_ok = self::write($file_name, (string)$file_content);
			} else {
				$x_ok = 1;
			} //end if
			//--
		} else {
			//--
			if(!self::is_type_file($file_name)) {
				$x_ok = self::write($file_name, (string)$file_content);
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
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function copy($file_name, $newlocation, $overwrite_destination=false, $check_copy_contents=true) {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // Copy: Empty Source File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			Smart::log_warning(__METHOD__.'() // Copy: Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.'() // Copy: The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (!self::is_type_file(self::link_get_origin($file_name))))) {
			Smart::log_warning(__METHOD__.'() // Copy // Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists($newlocation)) {
				Smart::log_warning(__METHOD__.'() // Copy // The destination file exists (1): S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		SmartFileSysUtils::raise_error_if_unsafe_path($newlocation);
		//--
		clearstatcache(true, $file_name);
		clearstatcache(true, $newlocation);
		//--
		$result = false;
		//--
		if(self::is_type_file($file_name)) {
			//--
			if(($overwrite_destination === true) OR (!self::path_exists($newlocation))) {
				//--
				$result = @copy($file_name, $newlocation); // if destination exists will overwrite it
				//--
				if(self::is_type_file($newlocation)) {
					//--
					self::fix_file_chmod($newlocation); // apply chmod
					//--
					if(!self::have_access_read($newlocation)) {
						Smart::log_warning(__METHOD__.'() // CopyFile // Destination file is not readable: '.$newlocation);
					} //end if
					//--
					if((int)self::get_file_size($file_name) !== (int)self::get_file_size($newlocation)) {
						$result = false; // clear
						self::delete($newlocation); // remove incomplete copied file
						Smart::log_warning(__METHOD__.'() // CopyFile // Destination file is not same size as original: '.$newlocation);
					} //end if
					//--
					if($check_copy_contents === true) {
						if((string)sha1_file($file_name) !== (string)sha1_file($newlocation)) {
							$result = false; // clear
							self::delete($newlocation); // remove broken copied file
							Smart::log_warning(__METHOD__.'() // CopyFile // Destination file checksum failed: '.$newlocation);
						} //end if
					} //end if
					//--
				} else {
					//--
					Smart::log_warning(__METHOD__.'() // CopyFile // Failed to copy a file: '.$file_name.' // to destination: '.$newlocation);
					//--
				} //end if
				//--
			} else {
				Smart::log_warning(__METHOD__.'() // CopyFile // Destination file exists (2): '.$newlocation);
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
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function rename($file_name, $newlocation, $overwrite_destination=false) {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // Rename/Move: Empty Source File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			Smart::log_warning(__METHOD__.'() // Rename/Move: Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.'() // Rename/Move: The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (!self::is_type_file(self::link_get_origin($file_name))))) {
			Smart::log_warning(__METHOD__.'() // Rename/Move // Source is not a FILE: S='.$file_name.' ; D='.$newlocation);
			return 0;
		} //end if
		if($overwrite_destination !== true) {
			if(self::path_exists($newlocation)) {
				Smart::log_warning(__METHOD__.'() // Rename/Move // The destination already exists: S='.$file_name.' ; D='.$newlocation);
				return 0;
			} //end if
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		SmartFileSysUtils::raise_error_if_unsafe_path($newlocation);
		//--
		clearstatcache(true, $file_name);
		clearstatcache(true, $newlocation);
		//--
		$f_cx = false;
		//--
		if(((string)$file_name != (string)$newlocation) AND (SmartFileSysUtils::check_if_safe_path($file_name)) AND (SmartFileSysUtils::check_if_safe_path($newlocation))) {
			//--
			if((self::is_type_file($file_name)) OR ((self::is_type_link($file_name)) AND (self::is_type_file(self::link_get_origin($file_name))))) { // don't move broken links
				//--
				if(!self::is_type_dir($newlocation)) {
					//--
					self::delete($newlocation); // just to be sure
					//--
					if(($overwrite_destination !== true) AND (self::path_exists($newlocation))) {
						//--
						Smart::log_warning(__METHOD__.'() // RenameFile // Destination file points to an existing file or link: '.$newlocation);
						//--
					} else {
						//--
						$f_cx = @rename($file_name, $newlocation); // If renaming a file and newname exists, it will be overwritten. If renaming a directory and newname exists, this function will emit a warning.
						//--
						if((self::is_type_file($newlocation)) OR ((self::is_type_link($newlocation)) AND (self::is_type_file(self::link_get_origin($newlocation))))) {
							if(self::is_type_file($newlocation)) {
								self::fix_file_chmod($newlocation); // apply chmod just if file and not a linked dir
							} //end if
						} else {
							$f_cx = false; // clear
							Smart::log_warning(__METHOD__.'() // RenameFile // Failed to rename a file: '.$file_name.' // to destination: '.$newlocation);
						} //end if
						//--
						if(!self::have_access_read($newlocation)) {
							Smart::log_warning(__METHOD__.'() // RenameFile // Destination file is not readable: '.$newlocation);
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
	public static function read_uploaded($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // Read-Uploaded: Empty Uploaded File Name');
			return '';
		} //end if
		//-- {{{SYNC-FILESYS-UPLD-FILE-CHECKS}}}
		if((string)DIRECTORY_SEPARATOR != '\\') { // if not on Windows (this test will FAIL on Windows ...)
			if(!SmartFileSysUtils::check_if_safe_path($file_name, 'no')) { // here we do not test against absolute path access because uploaded files always return the absolute path
				Smart::log_warning(__METHOD__.'() // Read-Uploaded: The Uploaded File Path is Not Safe: '.$file_name);
				return '';
			} //end if
			SmartFileSysUtils::raise_error_if_unsafe_path($file_name, 'no'); // here we do not test against absolute path access because uploaded files always return the absolute path
		} //end if
		//--
		clearstatcache(true, $file_name);
		//--
		$f_cx = '';
		//--
		if(is_uploaded_file($file_name)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if((self::is_type_file($file_name)) AND (self::have_access_read($file_name))) {
					//--
					$f_cx = (string) @file_get_contents($file_name);
					//--
				} else {
					//--
					Smart::log_warning(__METHOD__.'() // ReadUploadedFile // The file is not readable: '.$file_name);
					//--
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.'() // ReadUploadedFile // Cannot Find the Uploaded File or it is NOT an Uploaded File: '.$file_name);
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
	public static function move_uploaded($file_name, $newlocation, $check_moved_contents=true) {
		//--
		$file_name = (string) $file_name;
		$newlocation = (string) $newlocation;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // Move-Uploaded: Empty Uploaded File Name');
			return 0;
		} //end if
		if((string)$newlocation == '') {
			Smart::log_warning(__METHOD__.'() // Move-Uploaded: Empty Destination File Name');
			return 0;
		} //end if
		if((string)$file_name == (string)$newlocation) {
			Smart::log_warning(__METHOD__.'() // Move-Uploaded: The Source and the Destination Files are the same: '.$file_name);
			return 0;
		} //end if
		//--
		if(!is_uploaded_file($file_name)) { // double check if uploaded
			Smart::log_warning(__METHOD__.'() // Move-Uploaded: Cannot Find the Uploaded File or it is NOT an Uploaded File: '.$file_name);
			return 0;
		} //end if
		//-- {{{SYNC-FILESYS-UPLD-FILE-CHECKS}}}
		if((string)DIRECTORY_SEPARATOR != '\\') { // if not on Windows (this test will FAIL on Windows ...)
			if(!SmartFileSysUtils::check_if_safe_path($file_name, 'no')) { // here we do not test against absolute path access because uploaded files always return the absolute path
				Smart::log_warning(__METHOD__.'() // MoveUploadedFile: The Uploaded File Path is Not Safe: '.$file_name);
				return 0;
			} //end if
			SmartFileSysUtils::raise_error_if_unsafe_path($file_name, 'no'); // here we do not test against absolute path access because uploaded files always return the absolute path
		} //end if
		//--
		if(!SmartFileSysUtils::check_if_safe_path($newlocation)) {
			Smart::log_warning(__METHOD__.'() // MoveUploadedFile: The Destination File Path is Not Safe: '.$file_name);
			return 0;
		} //end if
		SmartFileSysUtils::raise_error_if_unsafe_path($newlocation);
		//--
		clearstatcache(true, $file_name);
		clearstatcache(true, $newlocation);
		//--
		$f_cx = false;
		//--
		if(SmartFileSysUtils::check_if_safe_path($newlocation)) {
			//--
			if(!self::is_type_dir($file_name)) {
				//--
				if(!self::is_type_dir($newlocation)) {
					//--
					self::delete($newlocation);
					//--
					if($check_moved_contents === true) {
						$sha_tmp_f = (string) sha1_file($file_name);
					} //end if
					$f_cx = @move_uploaded_file($file_name, $newlocation);
					//--
					if(self::is_type_file($newlocation)) {
						@touch($newlocation, time()); // touch modified time to avoid upload differences in time
						self::fix_file_chmod($newlocation); // apply chmod
						if($check_moved_contents === true) {
							$sha_new_f = (string) sha1_file($newlocation);
							if((string)$sha_tmp_f != (string)$sha_new_f) {
								$f_cx = 0;
								Smart::log_warning(__METHOD__.'() // MoveUploadedFile // Checksum Failed for: '.$file_name.' // to destination: '.$newlocation);
								self::delete($newlocation);
							} //end if
						} //end if
					} else {
						Smart::log_warning(__METHOD__.'() // MoveUploadedFile // Failed to move uploaded file: '.$file_name.' // to destination: '.$newlocation);
					} //end if
					//--
					if(!self::have_access_read($newlocation)) {
						Smart::log_warning(__METHOD__.'() // MoveUploadedFile // Destination file is not readable: '.$newlocation);
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
	 *
	 * @return 	INTEGER								:: 1 if SUCCESS ; 0 on FAIL (this is integer instead of boolean for future extending with status codes)
	 */
	public static function delete($file_name) {
		//--
		$file_name = (string) $file_name;
		//--
		if((string)$file_name == '') {
			Smart::log_warning(__METHOD__.'() // FileDelete // The File Name is Empty !');
			return 0; // empty file name
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($file_name);
		//--
		clearstatcache(true, $file_name);
		//--
		if(!self::path_exists($file_name)) {
			//--
			return 1;
			//--
		} //end if
		//--
		if(self::is_type_link($file_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($file_name);
			//--
			if(($f_cx) AND (!self::is_type_link($file_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$f_cx = false;
		//--
		if(SmartFileSysUtils::check_if_safe_path($file_name)) {
			//--
			if((self::is_type_file($file_name)) OR (self::is_type_link($file_name))) {
				//--
				if(self::is_type_file($file_name)) {
					//--
					self::fix_file_chmod($file_name); // apply chmod
					//--
					$f_cx = @unlink($file_name);
					//--
					if(self::path_exists($file_name)) {
						$f_cx = false;
						Smart::log_warning(__METHOD__.'() // DeleteFile // FAILED to delete this file: '.$file_name);
					} //end if
					//--
				} //end if
				//--
			} elseif(self::is_type_dir($file_name)) {
				//--
				Smart::log_warning(__METHOD__.'() // DeleteFile // A file was marked for deletion but that is a directory: '.$file_name);
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
	 *
	 * @return 	STRING								:: The relative or absolute (or non-safe) path to the symlink origin or empty string if broken link (no path safety checks are implemented over)
	 */
	public static function link_get_origin($y_link) {
		//--
		$y_link = (string) $y_link;
		//--
		if((string)$y_link == '') {
			Smart::log_warning(__METHOD__.'() // Get Link: The Link Name is Empty !');
			return '';
		} //end if
		//--
		if(!SmartFileSysUtils::check_if_safe_path($y_link)) { // pre-check
			Smart::log_warning(__METHOD__.'() // Get Link: Invalid Path Link : '.$y_link);
			return '';
		} //end if
		if(substr($y_link, -1, 1) == '/') { // test if end with one or more trailing slash(es) and rtrim
			Smart::log_warning(__METHOD__.'() // Get Link: Link ends with one or many trailing slash(es) / : '.$y_link);
			$y_link = (string) rtrim($y_link, '/');
		} //end if
		if(!SmartFileSysUtils::check_if_safe_path($y_link)) { // post-check
			Smart::log_warning(__METHOD__.'() // Get Link: Invalid Link Path : '.$y_link);
			return '';
		} //end if
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($y_link);
		//--
		if(!self::is_type_link($y_link)) {
			Smart::log_warning(__METHOD__.'() // Get Link: Link does not exists : '.$y_link);
			return '';
		} //end if
		//--
		return (string) @readlink($y_link);
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
	public static function link_create($origin, $destination) {
		//--
		$origin = (string) $origin;
		$destination = (string) $destination;
		//--
		if((string)$origin == '') {
			Smart::log_warning(__METHOD__.'() // Create Link: The Origin Name is Empty !');
			return 0;
		} //end if
		if((string)$destination == '') {
			Smart::log_warning(__METHOD__.'() // Create Link: The Destination Name is Empty !');
			return 0;
		} //end if
		//--
		/* DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY SmartFileSystem::link_get_origin() ...
		if(!SmartFileSysUtils::check_if_safe_path($origin, 'no')) { // here we do not test against absolute path access because readlink may return an absolute path
			Smart::log_warning(__METHOD__.'() // Create Link: Invalid Path for Origin : '.$origin);
			return 0;
		} //end if
		*/
		if(!SmartFileSysUtils::check_if_safe_path($destination)) {
			Smart::log_warning(__METHOD__.'() // Create Link: Invalid Path for Destination : '.$destination);
			return 0;
		} //end if
		//--
		if(!self::path_exists($origin)) {
			Smart::log_warning(__METHOD__.'() // Create Link: Origin does not exists : '.$origin);
			return 0;
		} //end if
		if(self::path_exists($destination)) {
			Smart::log_warning(__METHOD__.'() // Create Link: Destination exists : '.$destination);
			return 0;
		} //end if
		//--
		// DO NOT CHECK, IT MAY BE AN ABSOLUTE + NON-SAFE PATH RETURNED BY SmartFileSystem::link_get_origin() ...
		//SmartFileSysUtils::raise_error_if_unsafe_path($origin, 'no'); // here we do not test against absolute path access because readlink may return an absolute path
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($destination);
		//--
		$result = @symlink($origin, $destination);
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
	public static function dir_create($dir_name, $recursive=false, $allow_protected_paths=false) {
		//-- override (this is actually done automatically in raise_error_if_unsafe_path and check_if_safe_path but reflect also here this as there are logs below ...
		if(SmartFrameworkRegistry::isAdminArea() === true) {
			if(SmartFrameworkRegistry::isTaskArea() === true) {
				$allow_protected_paths = true; // this is required as default for various tasks that want to access #protected dirs
			} //end if
		} //end if
		//--
		$dir_name = (string) $dir_name;
		//--
		if(!defined('SMART_FRAMEWORK_CHMOD_DIRS')) {
			Smart::log_warning(__METHOD__.'() // Skip: A required constant (SMART_FRAMEWORK_CHMOD_DIRS) has not been defined ...');
			return 0;
		} //end if
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // Create Dir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The Dir Name is Empty !');
			return 0;
		} //end if
		//--
		if($allow_protected_paths === true) {
			SmartFileSysUtils::raise_error_if_unsafe_path($dir_name, 'yes', 'yes'); // deny absolute paths ; allow protected paths (starting with a `#`)
			$is_path_chk_safe = SmartFileSysUtils::check_if_safe_path($dir_name, 'yes', 'yes'); // deny absolute paths ; allow protected paths (starting with a `#`)
		} else {
			SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
			$is_path_chk_safe = SmartFileSysUtils::check_if_safe_path($dir_name);
		} //end if else
		//--
		clearstatcache(true, $dir_name);
		//--
		$result = false;
		//--
		if($is_path_chk_safe) {
			//--
			if(!self::path_exists($dir_name)) {
				//--
				if($recursive === true) {
					$result = @mkdir($dir_name, SMART_FRAMEWORK_CHMOD_DIRS, true);
					$dir_elements = (array) explode('/', $dir_name);
					$tmp_crr_dir = '';
					for($i=0; $i<count($dir_elements); $i++) { // fix: to chmod all dir segments (in PHP the mkdir chmod is applied only to the last dir segment if recursive mkdir ...)
						$dir_elements[$i] = (string) trim((string)$dir_elements[$i]);
						if((string)$dir_elements[$i] != '') {
							$tmp_crr_dir .= (string) SmartFileSysUtils::add_dir_last_slash((string)$dir_elements[$i]);
							if((string)$tmp_crr_dir != '') {
								if(self::is_type_dir((string)$tmp_crr_dir)) {
									self::fix_dir_chmod((string)$tmp_crr_dir); // apply separate chmod to each segment
								} //end if
							} //end if
						} //end if
					} //end for
				} else {
					$result = @mkdir($dir_name, SMART_FRAMEWORK_CHMOD_DIRS);
					if(self::is_type_dir($dir_name)) {
						self::fix_dir_chmod($dir_name); // apply chmod
					} //end if
				} //end if else
				//--
			} elseif(self::is_type_dir($dir_name)) {
				//--
				$result = true; // dir exists
				//--
			} else {
				//--
				Smart::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // FAILED to create a directory because it appear to be a File: '.$dir_name);
				//--
			} //end if else
			//--
			if(!self::is_type_dir($dir_name)) {
				Smart::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // FAILED to create a directory: '.$dir_name);
				$out = 0;
			} //end if
			//--
			if(!self::have_access_write($dir_name)) {
				Smart::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The directory is not writable: '.$dir_name);
				$out = 0;
			} //end if
			//--
		} else {
			//--
			Smart::log_warning(__METHOD__.'() // CreateDir [R='.(int)$recursive.'/S='.(int)$allow_protected_paths.'] // The directory path is not Safe: '.$dir_name);
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
	public static function dir_copy($dirsource, $dirdest, $check_copy_contents=true) {
		//--
		return (int) self::dir_recursive_private_copy($dirsource, $dirdest, $check_copy_contents);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================ PRIVATE RECURSIVE COPY A DIRECTORY (FULL CLONE)
	// NOTICE: This function is PRIVATE because the last two params are private, they SHOULD NOT be used unauthorized (they are used only internal to remember the initial dirs ...)
	private static function dir_recursive_private_copy($dirsource, $dirdest, $check_copy_contents, $protected_dirsource='', $protected_dirdest='') {
		//--
		$dirsource = (string) $dirsource;
		$dirdest = (string) $dirdest;
		$check_copy_contents = (bool) $check_copy_contents;
		$protected_dirsource = (string) $protected_dirsource;
		$protected_dirdest = (string) $protected_dirdest;
		//--
		if((int)strlen((string)$dirsource) <= 0) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Source Dir Name is Empty !');
			return 0; // empty source dir
		} //end if
		if((int)strlen((string)$dirdest) <= 0) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir Name is Empty !');
			return 0; // empty destination dir
		} //end if
		//--
		clearstatcache(true, $dirsource);
		clearstatcache(true, $dirdest);
		//--
		if((int)strlen((string)$protected_dirsource) <= 0) {
			$protected_dirsource = (string) $dirsource; // 1st time
		} //end if
		if((int)strlen((string)$protected_dirdest) <= 0) {
			if(self::path_exists($dirdest)) {
				Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir exists: S='.$destination);
				return 0;
			} //end if else
			$protected_dirdest = (string) $dirdest; // 1st time
		} //end if
		//-- add trailing slash
		$dirsource = SmartFileSysUtils::add_dir_last_slash($dirsource);
		$dirdest = SmartFileSysUtils::add_dir_last_slash($dirdest);
		//-- checks (must be after adding trailing slashes)
		SmartFileSysUtils::raise_error_if_unsafe_path($dirsource);
		SmartFileSysUtils::raise_error_if_unsafe_path($dirdest);
		SmartFileSysUtils::raise_error_if_unsafe_path($protected_dirsource);
		SmartFileSysUtils::raise_error_if_unsafe_path($protected_dirdest);
		//-- protect against infinite loop if the source and destination are the same or destination contained in source
		if((string)$dirdest == (string)$dirsource) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Source Dir is the same as Destination Dir: S&D='.$dirdest);
			return 0;
		} //end if
		if((string)$dirdest == (string)SmartFileSysUtils::add_dir_last_slash(Smart::dir_name($dirsource))) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir is the same as Source Parent Dir: S='.$dirsource.' ; D='.$dirdest);
			return 0;
		} //end if
		if((string)substr($dirdest, 0, strlen($dirsource)) == (string)$dirsource) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir is inside the Source Dir: S='.$dirsource.' ; D='.$dirdest);
			return 0;
		} //end if
		if((string)substr($protected_dirdest, 0, strlen($protected_dirsource)) == (string)$protected_dirsource) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Original Destination Dir is inside the Original Source Dir: S*='.$protected_dirsource.' ; D*='.$protected_dirdest);
			return 0;
		} //end if
		//-- protect against infinite loop (this can happen with loop sym-links)
		if((string)$dirsource == (string)$protected_dirdest) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Source Dir is the same as Previous Step Source Dir (Loop Detected): S='.$dirsource.' ; S*='.$protected_dirdest);
			return 0;
		} //end if
		//--
		if(!SmartFileSysUtils::check_if_safe_path($dirsource)) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Source Dir Name is Invalid: S='.$dirsource);
			return 0;
		} //end if
		if(!SmartFileSysUtils::check_if_safe_path($dirdest)) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir Name is Invalid: D='.$dirdest);
			return 0;
		} //end if
		//--
		if(!self::is_type_dir($dirsource)) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Source Dir Name is not a Directory or does not exists: S='.$dirsource);
			return 0;
		} //end if else
		//--
		if(self::path_exists($dirdest)) {
			if(!self::is_type_dir($dirdest)) {
				Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir appear to be a file: D='.$dirdest);
				return 0;
			} //end if
		} else {
			if(self::dir_create($dirdest, true) !== 1) { // recursive
				Smart::log_warning(__METHOD__.'() // Copy Dir: Could Not Recursively Create the Destination: D='.$dirdest);
				return 0;
			} //end if
		} //end if else
		//--
		$out = 1; // default is ok
		//--
		if($handle = opendir($dirsource)) {
			//--
			while(false !== ($file = readdir($handle))) {
				//--
				if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty
					//--
					if(SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$file) != 1) {
						Smart::log_warning(__METHOD__.'() // Copy Dir: Skip Unsafe FileName or DirName `'.$file.'` detected in path: '.$dirsource);
						continue; // skip
					} //end if
					//--
					$tmp_path = (string) $dirsource.$file;
					$tmp_dest = (string) $dirdest.$file;
					//--
					SmartFileSysUtils::raise_error_if_unsafe_path($tmp_path);
					SmartFileSysUtils::raise_error_if_unsafe_path($tmp_dest);
					//--
					if(self::path_exists($tmp_path)) {
						//--
						if(self::is_type_link($tmp_path)) { // link
							//--
							self::delete($tmp_dest);
							if(self::path_exists($tmp_dest)) {
								Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Destination link still exists: '.$tmp_dest);
							} //end if
							//--
							if(self::link_create(self::link_get_origin($tmp_path), $tmp_dest) !== 1) {
								Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Failed to copy a Link: '.$tmp_path);
								return 0;
							} //end if else
							//--
						} elseif(self::is_type_file($tmp_path)) { // file
							//--
							self::delete($tmp_dest);
							if(self::path_exists($tmp_dest)) {
								Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Destination file still exists: '.$tmp_dest);
							} //end if
							//--
							if(self::copy($tmp_path, $tmp_dest, false, $check_copy_contents) !== 1) { // do not rewrite destination, use check from param
								Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Failed to copy a File: '.$tmp_path);
								return 0;
							} //end if else
							//--
						} elseif(self::is_type_dir($tmp_path)) { // dir
							//--
							if(self::dir_recursive_private_copy($tmp_path, $tmp_dest, $check_copy_contents, $protected_dirsource, $protected_dirdest) !== 1) {
								Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Failed on Dir: '.$tmp_path);
								return 0;
							} //end if
							//--
						} else {
							//--
							Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Invalid Type: '.$tmp_path);
							return 0;
							//--
						} //end if else
						//--
					} elseif(self::is_type_link($tmp_path)) { // broken link (we still copy it)
						//--
						self::delete($tmp_dest);
						if(self::path_exists($tmp_dest)) {
							Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Destination Link still exists: '.$tmp_dest);
						} //end if
						//--
						if(self::link_create(self::link_get_origin($tmp_path), $tmp_dest) !== 1) {
							Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // Failed to copy a Link: '.$tmp_path);
							return 0;
						} //end if else
						//--
					} else {
						//--
						Smart::log_warning(__METHOD__.'() // RecursiveDirCopy // File does not exists or is not accessible: '.$tmp_path);
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
	public static function dir_rename($dir_name, $new_dir_name) {
		//--
		$dir_name = (string) $dir_name;
		$new_dir_name = (string) $new_dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: Source Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$new_dir_name == '') {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: Destination Dir Name is Empty !');
			return 0;
		} //end if
		if((string)$dir_name == (string)$new_dir_name) {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: The Source and the Destination Files are the same: '.$dir_name);
			return 0;
		} //end if
		//--
		if((!self::is_type_dir($dir_name)) OR ((self::is_type_link($dir_name)) AND (!self::is_type_dir(self::link_get_origin($dir_name))))) {
			Smart::log_warning(__METHOD__.'() // RenameDir // Source is not a DIR: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists($new_dir_name)) {
			Smart::log_warning(__METHOD__.'() // RenameDir // The destination already exists: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		//--
		$dir_name = SmartFileSysUtils::add_dir_last_slash($dir_name); // trailing slash
		$new_dir_name = SmartFileSysUtils::add_dir_last_slash($new_dir_name); // trailing slash
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
		SmartFileSysUtils::raise_error_if_unsafe_path($new_dir_name);
		//--
		if((string)$dir_name == (string)$new_dir_name) {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: Source and Destination are the same: S&D='.$dir_name);
			return 0;
		} //end if
		if((string)$new_dir_name == (string)SmartFileSysUtils::add_dir_last_slash(Smart::dir_name($dir_name))) {
			Smart::log_warning(__METHOD__.'() // Copy Dir: The Destination Dir is the same as Source Parent Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if((string)substr($new_dir_name, 0, strlen($dir_name)) == (string)$dir_name) {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: The Destination Dir is inside the Source Dir: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(!self::is_type_dir(SmartFileSysUtils::add_dir_last_slash(Smart::dir_name($new_dir_name)))) {
			Smart::log_warning(__METHOD__.'() // Rename/Move Dir: The Destination Parent Dir is missing: P='.SmartFileSysUtils::add_dir_last_slash(Smart::dir_name($new_dir_name)).' of D='.$new_dir_name);
			return 0;
		} //end if
		//--
		clearstatcache(true, $dir_name);
		clearstatcache(true, $new_dir_name);
		//--
		$result = false;
		//--
		$dir_name = (string) rtrim((string)$dir_name, '/'); // FIX: remove trailing slash, it may be a link
		$new_dir_name = (string) rtrim((string)$new_dir_name, '/'); // FIX: remove trailing slash, it may be a link
		//--
		if(((string)$dir_name != (string)$new_dir_name) AND (SmartFileSysUtils::check_if_safe_path($dir_name)) AND (SmartFileSysUtils::check_if_safe_path($new_dir_name))) {
			if((self::is_type_dir($dir_name)) OR ((self::is_type_link($dir_name)) AND (self::is_type_dir(self::link_get_origin($dir_name))))) {
				if(!self::path_exists($new_dir_name)) {
					$result = @rename($dir_name, $new_dir_name);
				} //end if
			} //end if
		} //end if else
		//--
		if((!self::is_type_dir($new_dir_name)) OR ((self::is_type_link($new_dir_name)) AND (!self::is_type_dir(self::link_get_origin($new_dir_name))))) {
			Smart::log_warning(__METHOD__.'() // RenameDir // FAILED to rename a directory: S='.$dir_name.' ; D='.$new_dir_name);
			return 0;
		} //end if
		if(self::path_exists($dir_name)) {
			Smart::log_warning(__METHOD__.'() // RenameDir // Source DIR still exists: S='.$dir_name.' ; D='.$new_dir_name);
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
	public static function dir_delete($dir_name, $recursive=true) {
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // Dir Name is Empty !');
			return 0;
		} //end if
		//--
		clearstatcache(true, $dir_name);
		//--
		if(self::is_type_link($dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($dir_name); // avoid deleting content from a linked dir, just remove the link :: THIS MUST BE DONE BEFORE ADDING THE TRAILING SLASH
			//--
			if(($f_cx) AND (!self::is_type_link($dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$dir_name = SmartFileSysUtils::add_dir_last_slash($dir_name); // fix invalid path (must end with /)
		//--
		SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
		//--
		if(!self::path_exists($dir_name)) {
			//--
			return 1;
			//--
		} //end if
		//-- avoid deleting content from a linked dir, just remove the link (2nd check, after adding the trailing slash)
		if(self::is_type_link($dir_name)) { // {{{SYNC-BROKEN-LINK-DELETE}}}
			//--
			$f_cx = @unlink($dir_name);
			//--
			if(($f_cx) AND (!self::is_type_link($dir_name))) {
				return 1;
			} else {
				return 0;
			} //end if else
			//--
		} //end if
		//--
		$result = false;
		//-- remove all subdirs and files within
		if(SmartFileSysUtils::check_if_safe_path($dir_name)) {
			//--
			if((self::is_type_dir($dir_name)) AND (!self::is_type_link($dir_name))) { // double check if type link
				//--
				self::fix_dir_chmod($dir_name); // apply chmod
				//--
				if($handle = opendir($dir_name)) {
					//--
					while(false !== ($file = readdir($handle))) {
						//--
						if(((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..')) { // fix empty
							//--
							if(SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$file) != 1) { // skip non-safe filenames to avoid raise error if a directory contains accidentally a nn-safe filename or dirname (at least delete as much as can) ...
								//--
								Smart::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // SKIP Unsafe FileName or DirName `'.$file.'` detected in path: '.$dir_name);
								//--
							} else {
								//--
								if((self::is_type_dir($dir_name.$file)) AND (!self::is_type_link($dir_name.$file))) {
									//--
									if($recursive == true) {
										//--
										self::dir_delete($dir_name.$file, $recursive);
										//--
									} else {
										//--
										return 0; // not recursive and in this case sub-folders are not deleted
										//--
									} //end if else
									//--
								} else { // file or link
									//--
									self::delete($dir_name.$file);
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
					Smart::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // FAILED to open the directory: '.$dir_name);
					//--
				} //end if
				//-- finally, remove itself
				$result = @rmdir($dir_name);
				//--
			} else { // the rest of cases: is a file or a link
				//--
				$result = false;
				Smart::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // This is not a directory: '.$dir_name);
				//--
			} //end if
			//--
		} //end if
		//--
		if(self::path_exists($dir_name)) { // last final check
			$result = false;
			Smart::log_warning(__METHOD__.'() // DeleteDir [R='.(int)$recursive.'] // FAILED to delete a directory: '.$dir_name);
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
	public static function compare_folders($dir1, $dir2, $include_dot_files=true, $recurring=true) { // TODO: add a 4th parameter as search pattern to compare
		//-- get storage data for each folder
		$arr_dir1 = (array) (new SmartGetFileSystem(true))->get_storage($dir1, $recurring, $include_dot_files);
		$arr_dir2 = (array) (new SmartGetFileSystem(true))->get_storage($dir2, $recurring, $include_dot_files);
		//-- the above on error return empty array, so this error must be catched
		if(Smart::array_size($arr_dir1) <= 0) {
			return array('compare-error' => 'First Folder returned empty storage data: '.$dir1);
		} //end if
		if(Smart::array_size($arr_dir2) <= 0) {
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
 * $arr = $obj->search_files(true, 'uploads/', false, '.svg', '100');
 * print_r($arr);
 *
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		This class can handle thread concurency to the filesystem in a safe way by using the LOCK_EX (lock exclusive) feature on each file written / appended thus making also reads to be safe
 *
 * @depends 	classes: Smart
 * @version 	v.20210513
 * @package 	@Core:FileSystem
 *
 */
final class SmartGetFileSystem {

	// ->


	//================================================================
	//--
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
	public function __construct($list_files_and_dirs=false) { // CONSTRUCTOR
		//--
		if($list_files_and_dirs === true) {
			$this->list_files_and_dirs = true;
		} //end if
		//--
		$this->init_vars();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function init_vars() {
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
	public function get_storage($dir_name, $recurring=true, $include_dot_files=false, $search_pattern='') {
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // GetStorage // Dir Name is Empty !');
			return array();
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::add_dir_last_slash($dir_name);
		//-- protection
		SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
		//--
		$this->init_vars();
		//--
		$this->limit_search_files = 0;
		//-- get
		$this->folder_iterator($recurring, $dir_name, $include_dot_files, $search_pattern); // no search pattern (return only sizes)
		//-- sort dirs list descending by modified time, newest first
		$this->pattern_dir_matches = array_keys($this->pattern_dir_matches);
		natsort($this->pattern_dir_matches);
		$this->pattern_dir_matches = array_values($this->pattern_dir_matches);
		//-- sort files list descending by modified time, newest first
		$this->pattern_file_matches = array_keys($this->pattern_file_matches);
		natsort($this->pattern_file_matches);
		$this->pattern_file_matches = array_values($this->pattern_file_matches);
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
		$arr['list-dirs'] 	= (array) $this->pattern_dir_matches;
		$arr['list-files'] 	= $this->pattern_file_matches;
		//--
		return (array) $arr ;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public function search_files($recurring, $dir_name, $include_dot_files, $search_pattern, $limit_search_files, $search_prevent_file='', $search_prevent_override='') {
		//--
		$dir_name = (string) $dir_name;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // SearchFiles // Dir Name is Empty !');
			return array();
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::add_dir_last_slash($dir_name);
		//-- protection
		SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
		//--
		$this->init_vars();
		//--
		$this->limit_search_files = Smart::format_number_int($limit_search_files, '+');
		//--
		$this->list_files_and_dirs = true;
		$this->folder_iterator($recurring, $dir_name, $include_dot_files, $search_pattern, $search_prevent_file, $search_prevent_override); // ! search pattern (return found files and dirs up to max matches)
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
	private function get_std_real_dir_path($relative_or_linked_path) {
		//--
		$relative_or_linked_path = (string) $relative_or_linked_path;
		if((string)$relative_or_linked_path == '') {
			return '';
		} //end if
		//--
		$relative_or_linked_path = (string) Smart::real_path((string)$relative_or_linked_path);
		$relative_or_linked_path = (string) rtrim($relative_or_linked_path, '/');
		//--
		return (string) $relative_or_linked_path;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function folder_iterator($recurring, $dir_name, $include_dot_files, $search_pattern='', $search_prevent_file='', $search_prevent_override='') {
		//--
		$recurring = (bool) $recurring;
		$dir_name = (string) $dir_name;
		$include_dot_files = (bool) $include_dot_files;
		$search_pattern = (string) $search_pattern;
		$search_prevent_file = (string) $search_prevent_file;
		$search_prevent_override = (string) $search_prevent_override;
		//--
		if((string)$dir_name == '') {
			Smart::log_warning(__METHOD__.'() // ReadsFolderRecurring // Dir Name is Empty !');
			return; // this function does not return anything, but just stop here in this case
		} //end if
		//-- fix invalid path (must end with /)
		$dir_name = (string) SmartFileSysUtils::add_dir_last_slash($dir_name);
		//-- protection
		SmartFileSysUtils::raise_error_if_unsafe_path($dir_name);
		//--
		clearstatcache(true, $dir_name);
		//--
		$this->pattern_search_str = $search_pattern;
		$this->search_prevent_file = $search_prevent_file;
		$this->search_prevent_override = $search_prevent_override;
		//--
		if((SmartFileSystem::path_exists($dir_name)) AND (!SmartFileSystem::is_type_file($dir_name))) { // can be dir or link
			//-- circular reference check for linked dirs that can trap execution into an infinite loop ; catch here ... otherwise will be catched by the max path lenth allowance
			if(!array_key_exists((string)$this->get_std_real_dir_path($dir_name), $this->scanned_folders)) {
				$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name)] = 0; // PHP8 Fix
			} //end if
			if((int)$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name)] > 1) {
			//	Smart::log_notice(__METHOD__.'() // ReadsFolderRecurring // Cycle Trap Linked Dir Detected for: '.$dir_name);
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
						if(SmartFileSysUtils::check_if_safe_file_or_dir_name((string)$file) != 1) {
							Smart::log_warning(__METHOD__.'() // Skip Unsafe FileName or DirName `'.$file.'` detected in path: '.$dir_name);
							continue; // skip
						} //end if
						//--
						if(($include_dot_files) OR ((!$include_dot_files) AND (substr($file, 0, 1) != '.'))) {
							//--
							SmartFileSysUtils::raise_error_if_unsafe_path($dir_name.$file);
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
									if(!array_key_exists((string)$this->get_std_real_dir_path($dir_name.$file), $this->scanned_folders)) {
										$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name.$file)] = 0; // PHP8 Fix
									} //end if
									$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name.$file)]++;
									//--
									if($recurring) {
										//-- we go search inside even if this folder name may not match the search pattern, it is a folder, except if dissalow addition from above
										$this->folder_iterator($recurring, SmartFileSysUtils::add_dir_last_slash($dir_name.$file), $include_dot_files, $search_pattern, $search_prevent_file, $search_prevent_override);
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
									$link_origin = (string) SmartFileSystem::link_get_origin($dir_name.$file);
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
													if($recurring) { // if recurring, add the full path
														$this->pattern_dir_matches[$dir_name.$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} else { // if not recurring, add just base path, without dir name prefix
														$this->pattern_dir_matches[$file] = SmartFileSystem::get_file_mtime($dir_name.$file);
													} //end if else
													//--
													if((string)$this->get_std_real_dir_path($link_origin) != (string)$this->get_std_real_dir_path($dir_name.$file)) { // avoid register if this is the same as the linked folder (this happen in rare situations ; ex: with the 1st sub-level linked folders)
														if(!array_key_exists((string)$this->get_std_real_dir_path($link_origin), $this->scanned_folders)) {
															$this->scanned_folders[(string)$this->get_std_real_dir_path($link_origin)] = 0; // PHP8 Fix
														} //end if
														$this->scanned_folders[(string)$this->get_std_real_dir_path($link_origin)]++; // if link origin real path is different, register it too
													} //end if
													if(!array_key_exists((string)$this->get_std_real_dir_path($dir_name.$file), $this->scanned_folders)) {
														$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name.$file)] = 0; // PHP8 Fix
													} //end if
													$this->scanned_folders[(string)$this->get_std_real_dir_path($dir_name.$file)]++;
													//--
													if($recurring) {
														//-- we go search inside even if this folder name may not match the search pattern, it is a folder, except if dissalow addition from above
														$this->folder_iterator($recurring, SmartFileSysUtils::add_dir_last_slash($dir_name.$file), $include_dot_files, $search_pattern, $search_prevent_file, $search_prevent_override);
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
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
