<?php
// [LIB - Smart.Framework / SQLite Custom Session]
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

define('SMART_FRAMEWORK__INFO__CUSTOM_SESSION_ADAPTER', 'SQLite: DB file based');

/**
 * Class App.Custom.Session.SQLite - Provides a custom session adapter to use SQLite (an alternative for the default files based session).
 * To use this set in etc/init.php the constant SMART_FRAMEWORK_SESSION_HANDLER = sqlite
 * NOTICE: using this adapter if the Session is set to expire as 0 (when browser is closed), in SQLite the session will expire at session.gc_maxlifetime seconds ...
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @hints 		The SQLite based key/value store is significant slower than DBA ; whenever is available DBA should be used
 *
 * @access 		PUBLIC
 * @depends 	SmartSQliteDb, Smart, SmartPersistentCache, PHP SQLite3 Extension
 * @version 	v.20221219
 * @package 	Application:Session
 *
 */
final class SmartCustomSession extends SmartAbstractCustomSession {

	// ->
	// SQlite Custom Session [OPTIONAL]
	// NOTICE: This object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE WILL NOT WORK !!!
	// IMPORTANT: this uses fatal err and will raise error when a fatal error will occur ; this behave is different from key/value DataStores like DBA or Redis which just log errors by using non-fatal err


	//-- PUBLIC VARS
	public $sess_area;
	public $sess_ns;
	public $sess_expire;
	//--
	private $sqlite;
	//--


	//==================================================
	public function open() {
		//--
		$sqlite_cfg = (array) Smart::get_from_config('sqlite');
		//--
		if((Smart::array_size($sqlite_cfg) <= 0) OR (!class_exists('SQLite3'))) {
			Smart::raise_error(
				'ERROR: SQLite Custom Session requires the PHP SQlite3 extension and SQlite Configuration to be set in Smart.Framework ...',
				'ERROR: Invalid Settings for App Session Handler. See the Error Log for more details ...'
			);
			die('');
		} //end if
		//-- {{{SYNC-SESSION-FILE_BASED-PREFIX}}}
		$path_prefix = (string) SmartPersistentCache::cachePathPrefix(2, (string)$this->sess_ns); // this is a safe path
		$db_path = (string) 'tmp/sessions/'.SmartFileSysUtils::addPathTrailingSlash((string)Smart::safe_filename((string)$this->sess_area)).SmartFileSysUtils::addPathTrailingSlash((string)$path_prefix).'db-sess_'.Smart::safe_filename((string)$this->sess_ns).'.sqlite';
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$db_path);
		//--
		$this->sqlite = new SmartSQliteDb(
			(string) $db_path, // avoid prefix with sess_ ; the class will check itself if it is a safe, relative path
			(int)    $sqlite_cfg['timeout'],
			false // do not register extra SQL functions, they are not needed in this context
		); // use the rest of values from configs
		$this->sqlite->open();
		//--
		$is_init = false;
		if(!$this->sqlite->check_if_table_exists('smart_framework_sessions')) { // better check here and make create table in a transaction if does not exists ; if not check here the create_table() will anyway check
			$is_init = true;
			$this->sqlite->write_data('BEGIN'); // start transaction ; avoid transaction run each time on session table ...
			$this->sqlite->create_table(
				'smart_framework_sessions',
				'`id` CHARACTER VARYING(256) PRIMARY KEY NOT NULL, `area` CHARACTER VARYING(64) NOT NULL, `ns` CHARACTER VARYING(512) NOT NULL, `created` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, `modified` BIGINT NOT NULL, `expire` BIGINT NOT NULL, `expire_at` BIGINT NOT NULL, `data` TEXT NOT NULL',
				[ // indexes
				//	'id' 		=> 'id', // not necessary, it is the primary key
					'area' 		=> 'area ASC',
					'ns' 		=> 'ns ASC',
				//	'created' 	=> 'created',
					'modified' 	=> 'modified',
				//	'expire' 	=> 'expire',
					'expire_at' => 'expire_at'
				]
			);
			$this->sqlite->write_data('COMMIT'); // commit transaction
		} //end if
		//--
		if($is_init !== true) { // avoid run on init, mostly the locking issues comes from here
			$this->gc((int)time()); // this runs probabilistic
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function close() {
		//--
		$this->sqlite = null; // will auto close connection
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function write($id, $data) {
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
		$now = (int) time();
		//--
		$arr_insert = [
			'id' 		=> (string) $id,
			'area' 		=> (string) $this->sess_area,
			'ns' 		=> (string) $this->sess_ns,
			'modified' 	=> (int)    $now,
			'expire' 	=> (int)    $expire,
			'expire_at' => (int)    ((int)$now + (int)$expire),
			'data' 		=> (string) SmartPersistentCache::varCompress((string)$data) // data is serialized session as string
		];
		//--
		$result = (array) $this->sqlite->write_data(
			'INSERT OR REPLACE INTO `smart_framework_sessions` '.
			$this->sqlite->prepare_statement($arr_insert, 'insert')
		);
		//--
		if($result[1] != 1) {
			Smart::log_warning('SQLite Custom Session: Failed to write ...');
			return false;
		} //end if
		//--
		return true; // don't throw if SQLite error !
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function read($id) {
		//--
		$arr = (array) $this->sqlite->read_asdata(
			'SELECT * FROM `smart_framework_sessions` WHERE ((`id` = ?) AND (`area` = ?) AND (`ns` = ?)) LIMIT 1 OFFSET 0',
			[
				(string) $id,
				(string) $this->sess_area,
				(string) $this->sess_ns
			]
		);
		//--
		if(Smart::array_size($arr) <= 0) {
			return ''; // not found
		} //end if
		//--
		if((int)$arr['expire_at'] < (int)time()) {
			return ''; // expired
		} //end if
		//--
		return (string) SmartPersistentCache::varUncompress((string)$arr['data']); // data is serialized session as string
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function destroy($id) {
		//--
		$this->sqlite->write_data(
			'DELETE FROM `smart_framework_sessions` WHERE ((`id` = ?) AND (`area` = ?) AND (`ns` = ?))',
			[
				(string) $id,
				(string) $this->sess_area,
				(string) $this->sess_ns
			]
		);
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
			$this->sqlite->write_data(
				'DELETE FROM `smart_framework_sessions` WHERE (`expire_at` < ?)',
				[
					(int) time() // session.gc_probability = 1 ; session.gc_divisor = 100 ; run this just on 10% of Garbage Collections ...
				]
			);
			if(Smart::random_number(0, 100) == 51) { // run vacuum just in 1% of 1% of cases, aka 0.1% ... the cost is significant !
				$this->sqlite->write_data('VACUUM');
			} //end if
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
