<?php
// [LIB - Smart.Framework / Plugins / SQLite Persistent Cache]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - SQLite Persistent Cache
// DEPENDS:
//	* Smart::
//	* SmartSQliteDb::
// DEPENDS-EXT: PHP SQLite3 Extension
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Provides a persistent Cache (in-SQLite-Files), that can be shared and/or reused between multiple PHP executions.
 * Requires SQlite to be set-up in config properly.
 * It is not supported yet on Windows OS because of the different locking mechanism used by SQLite and is note released when folders are deleted ...
 *
 * It uses a structure like below:
 * tmp/pcache#sqlite/9af/realm#9afbcde0/z/p-cache-#-z-0-a.sqlite
 * Realms are spreaded in 16x16x16 = 4096 sub-folders by CRC32B hash of the realm name
 * The sqlite files in each realm will spread 37x37x37 = 50653 (max sqlite files per realm) organized in 37 sub-folders per each realm [0-9a-z] ; will result a max of 1369 files per dir as sqlite
 * Each sqlite file can store unlimited number of keys in theory
 * Scenario:
 * - using 10,000,000 keys in a realm will spread the sqlite cache storage in optimal way in 37 sub-folders, each sub-folder containing 1369 sqlite files
 * - each sqlite file will store ~ 200 cache keys in a total of 50653 sqlite files spreaded in those 37 sub-dirs
 * - if each key have an archived size of 500KB will result in storage size of no more than 100MB per sqlite file which is super light ... sqlite files can size much more than that
 * - but this is only theory, in practice, tested in a real production environment with a realm that have ~ 3,000,000 keys (2D QRCodes) results a max sqlite size as of 2.7 MB (~ 15 KB / key size)
 *
 * THIS CLASS IS FOR PRIVATE USE ONLY (used as a backend for for SmartPersistentCache)
 * @access 		private
 * @internal
 *
 * @hints 		The SQLite based key/value store is significant slower than DBA ; whenever is available DBA should be used
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	Smart, PHP SQLite3 Extension, SmartSQliteDb
 * @version 	v.20231031
 * @package 	Application:Plugins:PersistentCache:SQlite
 *
 */
class SmartSQlitePersistentCache extends SmartAbstractPersistentCache {

	// ::

	// !!! THIS CLASS MUST NOT BE MARKED AS FINAL to allow the class SmartPersistentCache@SQLITE to be extended from this !!!
	// But this class have all PUBLIC Methods marked as FINAL to avoid being rewritten ...

	private const SQLITE_FOLDER 	= 'tmp/cache/pcache#sqlite/'; 	// base cached folder
	private const SQLITE_FILE   	= 'p-cache.sqlite';				// base name for sqlite cache file

	private static $is_active 	= null; // Cache Active State ; by default is null ; on 1st check must set to TRUE or FALSE


	final public static function getVersionInfo() : string {
		//--
		return (string) 'SQLite: DB File based Persistent Cache';
		//--
	} //END FUNCTION


	final public static function isActive() : bool {
		//--
		if(self::$is_active !== null) {
			return (bool) self::$is_active;
		} //end if
		//--
		$sqlite_cfg = (array) Smart::get_from_config('sqlite');
		//--
		if(Smart::array_size($sqlite_cfg) > 0) {
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
		return false; // SQLite is not a memory based cache backend (it is file based), so it is FALSE
		//--
	} //END FUNCTION


	final public static function isFileSystemBased() : bool {
		//--
		return true; // SQLite is a hybrid FileSystem/Database based cache backend, so it is TRUE
		//--
	} //END FUNCTION


	final public static function isDbBased() : bool {
		//--
		return true; // SQLite is a hybrid FileSystem/Database based cache backend, so it is TRUE
		//--
	} //END FUNCTION


	final public static function clearData() : bool {
		//--
		if(!self::isActive()) {
			return false;
		} //end if
		//--
		return (bool) SmartFileSystem::dir_delete(
			(string) SmartFileSysUtils::addPathTrailingSlash((string)self::SQLITE_FOLDER),
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
		$sqlite_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($sqlite_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid SQLite Instance: '.$y_realm.':'.$y_key);
			return false;
		} //end if
		//--
		$rd = (array) $sqlite_obj->read_asdata( // OK
			'SELECT `id`, `key`, `realm`, `expire`, `expire_at` FROM `smart_framework_pcache` WHERE ((`id` = ?) AND ((`expire` <= 0) OR ((`expire` > 0) AND (`expire_at` > 0) AND (`expire_at` >= ?)))) LIMIT 1 OFFSET 0',
			[
				(string) sha1((string)$y_realm.':'.$y_key),
				(int)    time()
			]
		);
		//--
		$exists = false;
		if(Smart::array_size($rd) > 0) {
			if(((string)$rd['key'] === (string)$y_key) AND ((string)$rd['realm'] === (string)$y_realm)) {
				$exists = true;
			} //end if
		} //end if
		//--
		return (bool) $exists;
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
		$sqlite_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($sqlite_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid SQLite Instance: '.$y_realm.':'.$y_key);
			return -3;
		} //end if
		//--
		$rd = (array) $sqlite_obj->read_asdata( // OK
			'SELECT `id`, `key`, `realm`, `expire`, `expire_at` FROM `smart_framework_pcache` WHERE ((`id` = ?) AND ((`expire` <= 0) OR ((`expire` > 0) AND (`expire_at` > 0) AND (`expire_at` >= ?)))) LIMIT 1 OFFSET 0',
			[
				(string) sha1((string)$y_realm.':'.$y_key),
				(int)    time()
			]
		);
		//--
		$ttl = -2; // does not exists
		if(Smart::array_size($rd) > 0) {
			if(((string)$rd['key'] === (string)$y_key) AND ((string)$rd['realm'] === (string)$y_realm)) {
				if((int)$rd['expire'] <= 0) {
					if((int)$rd['expire_at'] <= 0) {
						$ttl = -1; // does not expire
					} else {
						$ttl = -4; // error !!
					} //end if else
				} else {
					$ttl = (int) ((int)$rd['expire_at'] - (int)time()); // {{{SYNC-PCACHE-TTL}}}
					if($ttl < 0) {
						$ttl = 0;
					} //end if
				} //end if else
			} //end if
		} //end if
		//--
		return (int) $ttl;
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
		$sqlite_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($sqlite_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid SQLite Instance: '.$y_realm.':'.$y_key);
			return null;
		} //end if
		//--
		$rd = (array) $sqlite_obj->read_asdata( // OK
			'SELECT * FROM `smart_framework_pcache` WHERE ((`id` = ?) AND ((`expire` <= 0) OR ((`expire` > 0) AND (`expire_at` > 0) AND (`expire_at` >= ?)))) LIMIT 1 OFFSET 0',
			[
				(string) sha1((string)$y_realm.':'.$y_key),
				(int)    time()
			]
		);
		//--
		$data = null;
		if(Smart::array_size($rd) > 0) {
			if(((string)$rd['key'] === (string)$y_key) AND ((string)$rd['realm'] === (string)$y_realm)) {
				$data = (string) self::varUncompress((string)$rd['data']);
			} //end if
		} //end if
		//--
		return $data; // mixed
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
		$sqlite_obj = self::initCacheManager($y_realm, $y_key);
		if(!is_object($sqlite_obj)) {
			Smart::log_warning(__METHOD__.' # Invalid SQLite Instance: '.$y_realm.':'.$y_key);
			return false;
		} //end if
		//--
		$y_value = (string) SmartUnicode::fix_charset((string)$y_value); // fix
		$y_expiration = Smart::format_number_int($y_expiration, '+');
		if((int)$y_expiration < 0) {
			$y_expiration = 0; // zero is for not expiring records
		} //end if
		//--
		$now = (int) time();
		//--
		if((int)$y_expiration > 0) {
			$expire = (int) $y_expiration;
			$expiration = (int) ((int)$now + (int)$y_expiration); // {{{SYNC-PCACHE-EXPIRE}}}
		} else {
			$expire = 0;
			$expiration = -1; // does not expire (compatible to Redis)
		} //end if else
		//--
		$arr_insert = [
			'id' 		=> (string) sha1((string)$y_realm.':'.$y_key),
			'key' 		=> (string) $y_key,
			'realm' 	=> (string) $y_realm,
			'modified' 	=> (int)    $now,
			'expire' 	=> (int)    $expire,
			'expire_at' => (int)    $expiration,
			'data' 		=> (string) self::varCompress((string)$y_value)
		];
		//--
		$wr = (array) $sqlite_obj->write_data(
			'INSERT OR REPLACE INTO `smart_framework_pcache` '.
			$sqlite_obj->prepare_statement($arr_insert, 'insert')
		);
		//--
		return (bool) $wr[1];
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
			$sqlite_obj = self::initCacheManager($y_realm, $y_key);
			if(!is_object($sqlite_obj)) {
				Smart::log_warning(__METHOD__.' # Invalid SQLite Instance: '.$y_realm.':'.$y_key);
				return false;
			} //end if
			//--
			$sqlite_obj->write_data(
				'DELETE FROM `smart_framework_pcache` WHERE (`id` = ?)',
				[
					(string) sha1((string)$y_realm.':'.$y_key)
				]
			);
			//--
			return true;
			//--
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION


	//##### PRIVATES


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
		return (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)self::SQLITE_FOLDER).$db_file_folder, '-');
		//--
	} //END FUNCTION


	private static function getSafeStorageNameFile($y_realm, $y_key) {
		//--
		// This function will spread the cache files in a range of 0-0-0 z-z-z as of ~ 50000 (+50000 lock files) files for each realm but divided in 37 sub-dirs (very reasonable ; ex, for 10 million keys in a realm will store no more than 200 keys in a db file)
		//--
		if(((string)$y_key == '') OR ((string)$y_key == '*')) {
			return 'sqlite-pcache-error.err'; // this must not have the .sqlite extension to force driver raise error
		} //end if
		//-- {{{SYNC-PREFIXES-FOR-FS-CACHE}}}
		if(((string)trim((string)$y_realm) == '') OR (!self::validateRealm((string)$y_realm))) {
			$y_realm = 'default';
		} //end if
		$cachePathPrefix = (string) self::cachePathPrefix(3, $y_realm, $y_key); // this is already safe path
		$arrPathPrefix = (array) explode('/', (string)$cachePathPrefix);
		$cachePathPrefix = (string) implode('-', (array)$arrPathPrefix); // replaces / with - to avoid use sub-folders in this context
		$sqlite_fname = (string) substr((string)self::SQLITE_FILE, 0, -7).'-#-'.Smart::safe_filename($cachePathPrefix).substr((string)self::SQLITE_FILE, -7, 7); // NOTICE: $y_realm can contain slashes as they are allowed by validateRealm, so must apply Smart::safe_filename() !!
		//--
		return (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)$arrPathPrefix[0])).Smart::safe_filename((string)$sqlite_fname, '-');
		//--
	} //END FUNCTION


	private static function initCacheManager($y_realm, $y_key) {
		//--
		if((string)DIRECTORY_SEPARATOR == '\\') { // on Windows this is currently unsupported and will not work because clearData() and unset() with wildcard are failing to delete sqlite files due possible bug on SQLite+Windows
			Smart::log_warning(__METHOD__.' # This class is currently unsupported on Windows like OSes ...');
			return '';
		} //end if
		//--
		if(!self::isActive()) {
			Smart::log_warning(__METHOD__.' # SQLite does not appear to be active in configs');
			return '';
		} //end if
		//--
		$db_file_path = (string) self::getSafeStorageNameDir($y_realm).self::getSafeStorageNameFile($y_realm, $y_key);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$db_file_path);
		//--
		$sqlite_cfg = (array) Smart::get_from_config('sqlite');
		//-- !!! must create each time a new object because reusing a large number of resources / opened files may run out of memory/resources
		// Ex: inserting in a session ~ 1000 keys in a single realm that spread in ~1000 separate sqlite files will run out of resources ; so this is the only way to create a new object each time ; works well and tested ...
		$obj = new SmartSQliteDb(
			(string) $db_file_path, 		// file :: for each realm there is a separate DB file (in a separate sub-folder)
			(int)    $sqlite_cfg['timeout'],
			false // do not register extra SQL functions, they are not needed in this context
		); // use the rest of values from configs
		//--
		$obj->open();
		//--
		$is_init = false;
		if(!$obj->check_if_table_exists('smart_framework_pcache')) { // better check here and make create table in a transaction if does not exists ; if not check here the create_table() will anyway check
			$is_init = true;
			$obj->write_data('BEGIN'); // start transaction ; avoid transaction run each time on pcache table ...
			$obj->create_table(
				'smart_framework_pcache',
				'`id` CHARACTER VARYING(256) PRIMARY KEY NOT NULL, `key` CHARACTER VARYING(256) NOT NULL, `realm` CHARACTER VARYING(256) NOT NULL, `created` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, `modified` BIGINT NOT NULL, `expire` BIGINT NOT NULL, `expire_at` BIGINT NOT NULL, `data` TEXT NOT NULL',
				[ // indexes
				//	'id' 		=> 'id', // not necessary, it is the primary key
				//	'key' 		=> 'key ASC',
				//	'realm' 	=> 'realm ASC',
				//	'created' 	=> 'created',
					'modified' 	=> 'modified',
					'expire' 	=> 'expire',
					'expire_at' => 'expire_at'
				]
			);
			$obj->write_data('COMMIT'); // commit transaction
		} //end if
		//--
		if($is_init !== true) { // avoid run on init, mostly the locking issues comes from here
			if(Smart::random_number(0, 500) == 50) { // 1% chance to cleanup, but 5x numbers ... sqlite is not too efficient as pcache, avoid locks
				$obj->write_data(
					'DELETE FROM `smart_framework_pcache` WHERE ((`expire` > 0) AND (`expire_at` >= 0) AND (`expire_at` < ?))',
					[
						(int) time()
					]
				);
				if(Smart::random_number(0, 100) == 51) { // run vacuum just in 1% (with higher numbers) of 1% of cases, aka 0.1% ... the cost is significant !
					$obj->write_data('VACUUM');
				} //end if
			} //end if
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
