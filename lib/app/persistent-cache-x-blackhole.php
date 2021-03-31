<?php
// [LIB - Smart.Framework / Blackhole (X-None) Persistent Cache]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartPersistentCache (Default, Blackhole)
 * Provides compatibility support for the Persistent Cache when not using any storage adapter (handler).
 * This class is used just for ensuring code reliability when no other real adapter was set.
 * If no real Persistent Cache adapter is set this class is used by default, automatically.
 * It will function as a blackhole, meaning the Persistent Cache will be just emulated.
 * Thus all variables set in this (blackhole) Persistent Cache will simply vanish ... and get will always return an empty result, because have no storage attached (emulated only)
 *
 * A real Persistent Cache adapter can be set in etc/init.php as SMART_FRAMEWORK_PERSISTENT_CACHE_HANDLER to use a real and functional Persistent Cache adapter (handler).
 * By default there are 2 built-in options and a 3rd extra option for the Persistent Cache Adapter in Smart.Framework:
 * 1) the built-in DBA adapter, using small DBA files across the FileSystem
 * 2) the built-in Redis adapter using inMemory Persistent Cache
 * 3) a custom implementation (that you must develop and set) to use your own custom Persistent Cache adapter (handler) by extending the SmartAbstractPersistentCache abstract class
 * NOTICE: When developing a custom Persistent Cache adapter (handler) if the key expiration is not supported natively, then this functionality must be implemented in a custom way to expire and to delete expired keys.
 * By example Memcached is not a good choice for a Persistent Cache storage because the max length of a string that can be stored in Memcached is 1MB.
 * This is why Smart.Framework supply only the Redis and DBA adapters (and not a Memcached adapter) because another requirement of the Persistent Cache implementation is to have no limit on the key/value that can be stored. And Redis does not have such limit. Also DBA does not have any limit.
 *
 * @hints The Persistent Cache will share the keys between both areas (INDEX and ADMIN) ; It is developer's choice and work to ensure realm separation if needed for the keys if required so (Ex: INDEX area may use separate realms than ADMIN area)
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	-
 * @version 	v.20210331
 * @package 	Application:Caching
 *
 */
final class SmartPersistentCache extends SmartAbstractPersistentCache {

	// ::


	public static function getVersionInfo() {
		//--
		return (string) 'BLACKHOLE: FAKE, EMULATED Persistent Cache ; THIS HAVE NO STORAGE ATTACHED ; Provides just compatibility support for the Persistent Cache when not using any real adapter to ensure the code requiring the class `'.__CLASS__.'` is functional ...';
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
