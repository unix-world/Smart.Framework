<?php
// [LIB - Smart.Framework / Plugins / DBA Persistent Cache]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - DBA Persistent Cache
// DEPENDS:
//	* Smart::
//	* SmartDbaUtilDb::
//	* SmartDbaDb::
// DEPENDS-EXT: PHP DBA Extension
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Provides a persistent Cache (in-DBA-Files), that can be shared and/or reused between multiple PHP executions.
 * Requires DBA to be set-up in config properly.
 *
 * It uses a structure like below:
 * tmp/pcache#dba/9af/realm#9afbcde0/z/p-cache-#-z-0-a.gdbm.dba
 * Realms are spreaded in 16x16x16 = 4096 sub-folders by CRC32B hash of the realm name
 * The dba files in each realm will spread 37x37x37 = 50653 (max dba files per realm) organized in 37 sub-folders per each realm [0-9a-z] ; will result a max of 1369x2 files per dir as dba and lock files
 * Each dba file can store unlimited number of keys in theory
 * Scenario:
 * - using 10,000,000 keys in a realm will spread the dba cache storage in optimal way in 37 sub-folders, each sub-folder containing 1369x2 dba files and lock files
 * - each dba file will store ~ 200 cache keys in a total of 50653x2 dba files spreaded in those 37 sub-dirs
 * - if each key have an archived size of 500KB will result in storage size of no more than 100MB per dba file which is super light ... dba files can size much more than that
 * - but this is only theory, in practice, tested in a real production environment with a realm that have ~ 3,000,000 keys (2D QRCodes) results a max dba size as of 2.7 MB (~ 15 KB / key size)
 *
 * THIS CLASS IS FOR PRIVATE USE ONLY (used as a backend for for SmartPersistentCache)
 * @access 		private
 * @internal
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	Smart, PHP DBA Extension, SmartDbaUtilDb, SmartDbaDb
 * @version 	v.20231031
 * @package 	Application:Plugins:PersistentCache:Dba
 *
 */
class SmartDbaPersistentCache extends SmartAbstractPersistentCache {

	// ::

	// !!! THIS CLASS MUST NOT BE MARKED AS FINAL to allow the class SmartPersistentCache@DBA to be extended from this !!!
	// But this class have all PUBLIC Methods marked as FINAL to avoid being rewritten ...

	private const DBA_FOLDER 	= 'tmp/cache/pcache#dba/'; 	// base cached folder
	private const DBA_FILE   	= 'p-cache.dba';			// base name for dba cache file

	private static $is_active 	= null;						// Cache Active State ; by default is null ; on 1st check must set to TRUE or FALSE


	final public static function getVersionInfo() : string {
		//--
		return (string) 'DBA: DB File based Persistent Cache, using the handler: '.SmartDbaUtilDb::getDbaHandler();
		//--
	} //END FUNCTION


	final public static function isActive() : bool {
		//--
		if(self::$is_active !== null) {
			return (bool) self::$is_active;
		} //end if
		//--
		$dba_cfg = (array) Smart::get_from_config('dba');
		//--
		if((Smart::array_size($dba_cfg) > 0) && (SmartDbaUtilDb::isDbaAndHandlerAvailable() === true)) {
			self::$is_active = true;
		} else {
			self::$is_active = false;
		} //end if else
		//--
		return (bool) self::$is_active;
		//--
	} //END FUNCTION


	final public static function isMemoryBased() : bool {
		//--
		return false; // DBA is not a memory based cache backend (it is file based), so it is FALSE
		//--
	} //END FUNCTION


	final public static function isFileSystemBased() : bool {
		//--
		return true; // DBA is a hybrid FileSystem/Database based cache backend, so it is TRUE
		//--
	} //END FUNCTION


	final public static function isDbBased() : bool {
		//--
		return true; // DBA is a hybrid FileSystem/Database based cache backend, so it is TRUE
		//--
	} //END FUNCTION


	final public static function clearData() : bool {
		//--
		if(!self::isActive()) {
			return false;
		} //end if
		//--
		return (bool) SmartFileSystem::dir_delete(
			(string) SmartFileSysUtils::addPathTrailingSlash((string)self::DBA_FOLDER),
			true // recursive delete all p-cache folder
		);
		//--
	} //END FUNCTION


	final public static function keyExists(?string $y_realm, ?string $y_key) : bool {
		//--
		if(!self::isActive()) {
			return false;
		} //end if
		//--
		if(!self::validateRealm((string)$y_realm)) {
			Smart::log_warning(__METHOD__.' # Invalid Realm: '.$y_realm);
			return false;
		} //end if
		if(!self::validateKey((string)$y_key)) {
			Smart::log_warning(__METHOD__.' # Invalid Key: '.$y_key);
			return false;
		} //end if
		//--
		$dba_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($dba_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid DBA Instance: '.$y_realm.':'.$y_key);
			return false;
		} //end if
		//--
		return (bool) $dba_obj->keyExists((string)$y_key);
		//--
	} //END FUNCTION


	final public static function getTtl(?string $y_realm, ?string $y_key) : int {
		//--
		if(!self::isActive()) {
			return -3;
		} //end if
		//--
		if(!self::validateRealm((string)$y_realm)) {
			Smart::log_warning(__METHOD__.' # Invalid Realm: '.$y_realm);
			return -3;
		} //end if
		if(!self::validateKey((string)$y_key)) {
			Smart::log_warning(__METHOD__.' # Invalid Key: '.$y_key);
			return -3;
		} //end if
		//--
		$dba_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($dba_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid DBA Instance: '.$y_realm.':'.$y_key);
			return -3;
		} //end if
		//--
		return (int) $dba_obj->getTtl((string)$y_key);
		//--
	} //END FUNCTION


	final public static function getKey(?string $y_realm, ?string $y_key) { // : MIXED
		//--
		if(!self::isActive()) {
			return null;
		} //end if
		//--
		if(!self::validateRealm((string)$y_realm)) {
			Smart::log_warning(__METHOD__.' # Invalid Realm: '.$y_realm);
			return null;
		} //end if
		if(!self::validateKey((string)$y_key)) {
			Smart::log_warning(__METHOD__.' # Invalid Key: '.$y_key);
			return null;
		} //end if
		//--
		$dba_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($dba_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid DBA Instance: '.$y_realm.':'.$y_key);
			return null;
		} //end if
		//--
		return $dba_obj->getKey((string)$y_key); // mixed
		//--
	} //END FUNCTION


	final public static function setKey(?string $y_realm, ?string $y_key, $y_value, ?int $y_expiration=0) : bool {
		//--
		// $y_value is MIXED TYPE, DO NOT CAST
		//--
		if(!self::isActive()) {
			return false;
		} //end if
		//--
		if(!self::validateRealm((string)$y_realm)) {
			Smart::log_warning(__METHOD__.' # Invalid Realm: '.$y_realm);
			return false;
		} //end if
		if(!self::validateKey((string)$y_key)) {
			Smart::log_warning(__METHOD__.' # Invalid Key: '.$y_key);
			return false;
		} //end if
		//--
		if(!self::validateValue((string)$y_value)) { // {{{SYNC-PCACHE-MAX-OBJ-SIZE}}}
			Smart::log_warning(__METHOD__.' # Invalid Value: must be not EMPTY or OVERSIZED (max 16MB) ; size='.strlen((string)$y_value));
			return false;
		} //end if
		//--
		$dba_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($dba_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid DBA Instance: '.$y_realm.':'.$y_key);
			return false;
		} //end if
		//--
		$y_value = (string) SmartUnicode::fix_charset((string)$y_value); // fix
		$y_expiration = Smart::format_number_int($y_expiration, '+');
		if((int)$y_expiration < 0) {
			$y_expiration = 0; // zero is for not expiring records
		} //end if
		//--
		return (bool) $dba_obj->setKey((string)$y_key, (string)$y_value, (int)$y_expiration);
		//--
	} //END FUNCTION


	final public static function unsetKey(?string $y_realm, ?string $y_key) : bool {
		//--
		if(!self::isActive()) {
			return false;
		} //end if
		//--
		if(!self::validateRealm((string)$y_realm)) {
			Smart::log_warning(__METHOD__.' # Invalid Realm: '.$y_realm);
			return false;
		} //end if
		if((string)$y_key != '*') {
			if(!self::validateKey((string)$y_key)) {
				Smart::log_warning(__METHOD__.' # Invalid Key: '.$y_key);
				return false;
			} //end if
		} //end if
		//--
		if((string)$y_key == '*') { // delete all keys in this realm
			//--
			return (bool) SmartFileSystem::dir_delete(
				(string) self::getSafeStorageNameDir($y_realm),
				true // recursive delete all p-cache folder
			);
			//--
		} else { // delete just one key
			//--
			$dba_obj = self::initCacheManager($y_realm, $y_key);
			if(!is_object($dba_obj)) {
				Smart::log_warning(__METHOD__.' # Invalid DBA Instance: '.$y_realm.':'.$y_key);
				return false;
			} //end if
			//--
			return (bool) $dba_obj->unsetKey($y_key);
			//--
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION


	//===== PRIVATES


	private static function getSafeStorageNameDir($y_realm) {
		//--
		// This will spread the realms in 000..FFF sub-folders ~ 4096 sub-folders
		//-- {{{SYNC-PREFIXES-FOR-FS-CACHE}}}
		if(((string)trim((string)$y_realm) == '') OR (!self::validateRealm((string)$y_realm))) {
			$y_realm = 'default';
		} //end if
		//--
		$hash = (string) SmartHashCrypto::crc32b((string)$y_realm);
		$prefix = (string) substr((string)Smart::safe_filename((string)strtolower((string)$y_realm), '-'), 0, 35);
		$db_file_folder = (string) SmartFileSysUtils::addPathTrailingSlash((string)substr((string)$hash, 0, 3)).SmartFileSysUtils::addPathTrailingSlash((string)$prefix.'#'.$hash);
		//--
		return (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)self::DBA_FOLDER).$db_file_folder, '-');
		//--
	} //END FUNCTION


	private static function getSafeStorageNameFile($y_realm, $y_key) {
		//--
		// This function will spread the cache files in a range of 0-0-0 z-z-z as of ~ 50000 (+50000 lock files) files for each realm but divided in 37 sub-dirs (very reasonable ; ex, for 10 million keys in a realm will store no more than 200 keys in a db file)
		//--
		if(((string)$y_key == '') OR ((string)$y_key == '*')) {
			return 'dba-pcache-error.err'; // this must not have the .dba extension to force driver raise error
		} //end if
		//-- {{{SYNC-PREFIXES-FOR-FS-CACHE}}}
		if(((string)trim((string)$y_realm) == '') OR (!self::validateRealm((string)$y_realm))) {
			$y_realm = 'default';
		} //end if
		$cachePathPrefix = (string) self::cachePathPrefix(3, $y_realm, $y_key); // this is already safe path
		$arrPathPrefix = (array) explode('/', (string)$cachePathPrefix);
		$cachePathPrefix = (string) implode('-', (array)$arrPathPrefix); // replaces / with - to avoid use sub-folders in this context
		$dba_fname = (string) substr((string)self::DBA_FILE, 0, -4).'-#-'.Smart::safe_filename($cachePathPrefix).substr((string)self::DBA_FILE, -4, 4); // NOTICE: $y_realm can contain slashes as they are allowed by validateRealm, so must apply Smart::safe_filename() !!
		//--
		return (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)$arrPathPrefix[0])).Smart::safe_filename((string)$dba_fname, '-');
		//--
	} //END FUNCTION


	private static function initCacheManager($y_realm, $y_key) {
		//--
		if(!self::isActive()) {
			Smart::log_warning(__METHOD__.' # DBA does not appear to be active in configs');
			return '';
		} //end if
		//--
		$db_file_path = (string) self::getSafeStorageNameDir($y_realm).self::getSafeStorageNameFile($y_realm, $y_key);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$db_file_path);
		//--
		$is_fatal_err = false; // for a persistent cache do not use fatal errors, just log them
		//-- !!! must create each time a new object because reusing a large number of resources / opened files may run out of memory/resources
		// Ex: inserting in a session ~ 1000 keys in a single realm that spread in ~1000 separate dba files will run out of resources ; so this is the only way to create a new object each time ; works well and tested ...
		$obj = new SmartDbaDb(
			(string) $db_file_path, 		// file :: for each realm there is a separate DB file (in a separate sub-folder)
			(string) get_called_class(), 	// desc (late state binding to get this class or class that extends this)
			(bool)   $is_fatal_err 			// fatal err
		); // use the connection values from configs
		//--
		if(Smart::random_number(0, 100) == 10) { // 1% chance to cleanup ; no need to check if init, DBA is iterating keys to do the cleanup so at init there are no keys ...
			$obj->clearExpiredKeys(25); // limit to 10% of 250 = 25 vs sessions ... have to be very fast, it is a pcache manager ; also the cache keys are bigger in size than sessions !
		} //end if
		//--
		return (object) $obj;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
