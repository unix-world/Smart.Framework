<?php
// [LIB - Smart.Framework / DBA Custom Session]
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

define('SMART_FRAMEWORK__INFO__CUSTOM_SESSION_ADAPTER', 'DBA: DB file based');

/**
 * Class App.Custom.Session.DBA - Provides a custom session adapter to use DBA (an alternative for the default files based session).
 * To use this set in etc/init.php the constant SMART_FRAMEWORK_SESSION_HANDLER = dba
 * NOTICE: using this adapter if the Session is set to expire as 0 (when browser is closed), in DBA the session will expire at session.gc_maxlifetime seconds ...
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	SmartDbaDb, Smart, SmartPersistentCache, PHP DBA Extension
 * @version 	v.20221219
 * @package 	Application:Session
 *
 */
final class SmartCustomSession extends SmartAbstractCustomSession {

	// ->
	// DBA Custom Session [OPTIONAL]
	// NOTICE: This object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE WILL NOT WORK !!!


	//-- PUBLIC VARS
	public $sess_area;
	public $sess_ns;
	public $sess_expire;
	//--
	private $dba;
	//--


	//==================================================
	public function open() {
		//--
		$dba_cfg = (array) Smart::get_from_config('dba');
		//--
		if((Smart::array_size($dba_cfg) <= 0) OR (SmartDbaUtilDb::isDbaAndHandlerAvailable() !== true)) {
			Smart::raise_error(
				'ERROR: DBA Custom Session requires the DBA Configuration to be set in Smart.Framework with a supported Handler ...',
				'ERROR: Invalid Settings for App Session Handler. See the Error Log for more details ...'
			);
			die('');
		} //end if
		//--
		$is_fatal_err = false; // for session do not use fatal errors, just log them
		//-- {{{SYNC-SESSION-FILE_BASED-PREFIX}}}
		$path_prefix = (string) SmartPersistentCache::cachePathPrefix(2, (string)$this->sess_ns); // this is a safe path
		$db_path = (string) 'tmp/sessions/'.SmartFileSysUtils::addPathTrailingSlash((string)Smart::safe_filename((string)$this->sess_area)).SmartFileSysUtils::addPathTrailingSlash((string)$path_prefix).'db-sess_'.Smart::safe_filename((string)$this->sess_ns).'.dba';
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$db_path);
		//--
		$this->dba = new SmartDbaDb(
			(string) $db_path, // avoid prefix with sess_ ; the class will check itself if it is a safe, relative path
			(string) __CLASS__, 	// desc
			(bool)   $is_fatal_err // fatal err
		); // use the rest of values from configs
		//--
		$this->gc((int)time()); // this runs probabilistic ; no need to check if init, DBA is iterating keys to do the cleanup so at init there are no keys ...
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function close() {
		//--
		$this->dba = null;
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function write($id, $data) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).':'.SmartPersistentCache::safeKey((string)$id);
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
		$result = $this->dba->setKey(
			(string) $key,
			(string) SmartPersistentCache::varCompress((string)$data),
			(int)    $expire
		);
		//--
		if($result !== true) {
			Smart::log_warning('DBA Custom Session: Failed to write ...');
			return false;
		} //end if
		//--
		return true; // don't throw if DBA error !
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function read($id) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).':'.SmartPersistentCache::safeKey((string)$id);
		//--
		$data = $this->dba->getKey((string)$key);
		//--
		if(!is_string($data)) {
			$data = ''; // if key does not exists it returns null or if expired returns false
		} //end if
		//--
		return (string) SmartPersistentCache::varUncompress((string)$data);
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function destroy($id) {
		//--
		$key = (string) SmartPersistentCache::safeKey((string)$this->sess_area).':'.SmartPersistentCache::safeKey((string)$this->sess_ns).':'.SmartPersistentCache::safeKey((string)$id);
		//--
		$this->dba->unsetKey((string)$key);
		//--
		// do not check the write result because other processes may unset an expired key when do GC and in that case may return false here ...
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function gc($lifetime) {
		//--
		if(Smart::random_number(0, 100) == 10) { // 1% chance to cleanup ; PHP is calling session gc with a very small chance ... so call it randomly in 1% of cases on opening the session
			$this->dba->clearExpiredKeys(250); // session.gc_probability = 1 ; session.gc_divisor = 100 ; run this just on 10% of Garbage Collections ...
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
