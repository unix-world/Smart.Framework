<?php
// [LIB - Smart.Framework / Redis Custom Session]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// OPTIONAL ; [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

define('SMART_FRAMEWORK__INFO__CUSTOM_SESSION_ADAPTER', 'Redis: Memory based');

/**
 * Class App.Custom.Session.Redis - Provides a custom session adapter to use Redis (an alternative for the default files based session).
 * To use this set in etc/init.php the constant SMART_FRAMEWORK_SESSION_HANDLER = redis
 * NOTICE: using this adapter if the Session is set to expire as 0 (when browser is closed), in redis the session will expire at session.gc_maxlifetime seconds ...
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	SmartRedisDb, Smart
 * @version 	v.20250107
 * @package 	Application:Session
 *
 */
final class SmartCustomSession extends SmartAbstractCustomSession {

	// ->
	// Redis Custom Session [OPTIONAL]
	// NOTICE: This object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE WILL NOT WORK !!!


	//-- PUBLIC VARS
	public $sess_area;
	public $sess_ns;
	public $sess_expire;
	//--
	private $redis;
	//--


	//==================================================
	public function open() {
		//--
		$redis_cfg = (array) Smart::get_from_config('redis');
		//--
		if(Smart::array_size($redis_cfg) <= 0) {
			Smart::raise_error(
				'ERROR: Redis Custom Session requires the Redis server Configuration to be set in Smart.Framework ...',
				'ERROR: Invalid Settings for App Session Handler. See the Error Log for more details ...'
			);
			die('');
		} //end if
		//--
		$is_fatal_err = false; // for session do not use fatal errors, just log them
		//--
		$this->redis = new SmartRedisDb(
			(string) __CLASS__, 	// desc
			(bool)   $is_fatal_err // fatal err
		); // use the connection values from configs
		//--
		// no need to call gc ... redis is expiring the keys through it's internal expire mechanism !
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function close() {
		//--
		$this->redis = null;
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function write($id, $data) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).'_'.SmartPersistentCache::safeKey((string)$id);
		//--
		$result = $this->redis->set((string)$key, (string)$data);
		//--
		if((string)strtoupper((string)trim((string)$result)) != 'OK') {
			Smart::log_warning('Redis Custom Session: Failed to write ...');
			return false;
		} //end if
		//--
		if((int)$this->sess_expire > 0) {
			$expire = (int) $this->sess_expire;
		} else {
			$expire = (int) ini_get('session.gc_maxlifetime');
			if($expire <= 0) {
				$expire = (int) 3600 * 24; // {{{SYNC-SESS-MAX-HARDCODED-VAL}}} max 24 hours from the last access if browser session, there is a security risk if SMART_FRAMEWORK_SESSION_LIFETIME is zero
			} //end if
		} //end if
		//--
		$result = $this->redis->expire((string)$key, (int)$expire);
		//--
		return true; // don't throw if redis error !
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function read($id) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).'_'.SmartPersistentCache::safeKey((string)$id);
		//--
		$data = $this->redis->get((string)$key);
		//--
		if(!is_string($data)) {
			$data = ''; // if key does not exists it returns null
		} //end if
		//--
		return (string) $data;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function destroy($id) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).'_'.SmartPersistentCache::safeKey((string)$id);
		//--
		$ok = $this->redis->del((string)$key);
		//--
		if($ok <= 0) {
			Smart::log_warning('Redis Custom Session: Failed to destroy ...');
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function gc($lifetime) {
		//--
		return true; // for Redis the Keys are Expiring from it's internal mechanism, so GC will have nothing to do here ...
		//--
	} //END FUNCTION
	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
