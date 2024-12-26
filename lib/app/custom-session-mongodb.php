<?php
// [LIB - Smart.Framework / MongoDB Custom Session]
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

define('SMART_FRAMEWORK__INFO__CUSTOM_SESSION_ADAPTER', 'MongoDB: DB NoSQL based');

/**
 * Class App.Custom.Session.MongoDB - Provides a custom session adapter to use MongoDB (an alternative for the default files based session).
 * To use this set in etc/init.php the constant SMART_FRAMEWORK_SESSION_HANDLER = mongodb
 * NOTICE: using this adapter if the Session is set to expire as 0 (when browser is closed), in db the session will expire at session.gc_maxlifetime seconds ...
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		PUBLIC
 * @depends 	SmartMongoDb, Smart
 * @version 	v.20210830
 * @package 	Application:Session
 *
 */
final class SmartCustomSession extends SmartAbstractCustomSession {

	// ->
	// MongoDB Custom Session [OPTIONAL]
	// NOTICE: This object MUST NOT CONTAIN OTHER FUNCTIONS BECAUSE WILL NOT WORK !!!


	//-- PUBLIC VARS
	public $sess_area;
	public $sess_ns;
	public $sess_expire;
	//--
	private $mongo;
	private $collection;
	//--


	//==================================================
	public function open() {
		//--
		$mongo_cfg = (array) Smart::get_from_config('mongodb');
		//--
		if(Smart::array_size($mongo_cfg) <= 0) {
			Smart::raise_error(
				'ERROR: MongoDB Custom Session requires the MongoDB server Configuration to be set in Smart.Framework ...',
				'ERROR: Invalid Settings for App Session Handler. See the Error Log for more details ...'
			);
			die('');
		} //end if
		//--
		$is_fatal_err = false; // for session do not use fatal errors, just log them
		//--
		$this->mongo = new SmartMongoDb(
			array(), 				// no custom config
			(bool)   $is_fatal_err 	// fatal err
		); // use the connection values from configs
		//--
		$ping = $this->mongo->igcommand(
			[
				'ping' => 1,
			]
		);
		if(!$this->mongo->is_command_ok($ping)) {
			Smart::log_warning('MongoDB Custom Session: Server Failed to answer to ping after connect ...');
			return false;
		} //end if
		//--
		$this->collection = 'SmartFrameworkSessions';
		//--
		$create_collection = $this->mongo->igcommand(
			[
				'create' => (string) $this->collection
			]
		);
		if($this->mongo->is_command_ok($create_collection)) { // cmd is OK just when creates
			//--
			$create_indexes = $this->mongo->igcommand(
				[
					'createIndexes' => (string) $this->collection,
					'indexes' 		=> [
						[
							'name' 				=> 'id',
							'key' 				=> [ 'id' => 1 ]
						],
						[
							'name' 				=> 'area',
							'key' 				=> [ 'area' => 1 ]
						],
						[
							'name' 				=> 'ns',
							'key' 				=> [ 'ns' => 1 ]
						],
						[
							'name' 				=> 'unique_idx',
							'key' 				=> [ 'id' => 1, 'area' => 1, 'ns' => 1 ],
							'unique' 			=> true
						],
						[
							'name' 				=> 'created',
							'key' 				=> [ 'created' => -1 ]
						],
						[
							'name' 				=> 'modified',
							'key' 				=> [ 'modified' => -1 ]
						],
						[
							'name' 				=> 'expire',
							'key' 				=> [ 'expire' => -1 ]
						],
						[
							'name' 				=> 'expire_at',
							'key' 				=> [ 'expire_at' => -1 ]
						]
					]
				]
			);
			//--
			if(!$this->mongo->is_command_ok($create_indexes)) {
				$drop_collection = $this->mongo->igcommand(
					[
						'drop' => (string) $this->collection
					]
				);
				Smart::log_warning('MongoDB Custom Session: Failed to create collection indexes, dropping collection: '.(int)$this->mongo->is_command_ok($drop_collection));
				return false;
			} //end if
			//--
		} //end if
		//--
		$this->gc((int)time()); // this runs probabilistic
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function close() {
		//--
		$this->mongo = null;
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
		try {
			$upsert = (array) $this->mongo->upsert(
				(string) $this->collection,
				[ // filter by Unique
					'id' 		=> (string) $id,
					'area' 		=> (string) $this->sess_area,
					'ns' 		=> (string) $this->sess_ns
				],
				'$set', 			// operation
				[ // update array
					'_id' 		=> (string) Smart::base_from_hex_convert((string)SmartHashCrypto::sha512($this->sess_ns.':'.$this->sess_area.':'.$id), 62).'-'.SmartHashCrypto::crc32b($this->sess_ns.':'.$this->sess_area.':'.$id, true), // ensure the same uuid to avoid 2 different uuids are upserted in the same time and generate duplicate error on high concurrency
					'id' 		=> (string) $id,
					'area' 		=> (string) $this->sess_area,
					'ns' 		=> (string) $this->sess_ns,
					//--
					'mtime' 	=> (string) microtime(true), // ensure get at least one field changed to force return row as changed
					'created' 	=> (string) date('Y-m-d H:i:s O'),
					'modified' 	=> (int)    $now,
					'expire' 	=> (int)    $expire,
					'expire_at' => (int)    ((int)$now + (int)$expire),
					'session' 		=> (string) Smart::seryalize([
						'checksum' 	=> (string) SmartHashCrypto::sha512($id.':'.$this->sess_area.':'.$this->sess_ns.':'.$data, true), // b64
						'data' 		=> (string) $data
					]) // data is serialized session as string
				]
			);
		} catch(Exception $err) { // don't throw if MongoDB error !
			Smart::log_warning('MongoDB Custom Session: Write Error: '.$err->getMessage());
			return false;
		} //end try catch
		//--
		if($upsert[1] != 1) {
			Smart::log_warning('MongoDB Custom Session: Failed to write. Updated Rows is invalid #: '.$upsert[1]);
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function read($id) {
		//--
		$arr = array();
		try {
			$arr = $this->mongo->findone(
				(string) $this->collection,
				[ // find filter (by Unique)
					'id' 		=> (string) $id,
					'area' 		=> (string) $this->sess_area,
					'ns' 		=> (string) $this->sess_ns
				]
			);
		} catch(Exception $err) { // don't throw if MongoDB error !
			Smart::log_warning('MongoDB Custom Session: Read Error: '.$err->getMessage());
			return '';
		} //end try catch
		//--
		if(Smart::array_size($arr) <= 0) {
			return ''; // not found
		} //end if
		//--
		if((int)$arr['expire_at'] < (int)time()) {
			return ''; // expired
		} //end if
		//--
		$arr['session'] = Smart::unseryalize((string)$arr['session']);
		if(Smart::array_size($arr['session']) <= 0) {
			Smart::log_warning('MongoDB Custom Session: Read Error: Invalid Session Structure');
			return ''; // invalid
		} //end if
		if(!array_key_exists('checksum', $arr['session'])) {
			Smart::log_warning('MongoDB Custom Session: Read Error: Invalid Session Key: checksum');
			return ''; // invalid
		} //end if
		if((string)trim((string)$arr['session']['checksum']) == '') { // expects sha512/b64
			Smart::log_warning('MongoDB Custom Session: Read Error: Empty Session Checksum');
			return ''; // invalid
		} //end if
		if(!array_key_exists('data', $arr['session'])) {
			Smart::log_warning('MongoDB Custom Session: Read Error: Invalid Session Key: data');
			return ''; // invalid
		} //end if
		if((string)SmartHashCrypto::sha512($id.':'.$this->sess_area.':'.$this->sess_ns.':'.$arr['session']['data'], true) !== (string)$arr['session']['checksum']) {
			Smart::log_warning('MongoDB Custom Session: Read Error: Invalid Session Data Checksum');
			return ''; // invalid
		} //end if
		//--
		return (string) $arr['session']['data']; // data is serialized session as string
		//--
	} //END FUNCTION
	//==================================================


	//==================================================
	public function destroy($id) {
		//--
		try {
			$this->mongo->delete(
				(string) $this->collection,
				[ // find filter (by Unique)
					'id' 		=> (string) $id,
					'area' 		=> (string) $this->sess_area,
					'ns' 		=> (string) $this->sess_ns
				]
			);
		} catch(Exception $err) { // don't throw if MongoDB error !
			Smart::log_warning('MongoDB Custom Session: Destroy Error: '.$err->getMessage());
			return false;
		} //end try catch
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
			try {
				$this->mongo->delete(
					(string) $this->collection,
					[ // find filter: all expired
						'expire_at' => [
							'$lt' => (int) time() // session.gc_probability = 1 ; session.gc_divisor = 100 ; run this just on 10% of Garbage Collections ...
						]
					]
				);
			} catch(Exception $err) { // don't throw if MongoDB error !
				Smart::log_warning('MongoDB Custom Session: GC Error: '.$err->getMessage());
				return false;
			} //end try catch
		} //end if
		//--
		return true; // for MongoDB the Keys are Expiring from it's internal mechanism, so GC will not be used here ...
		//--
	} //END FUNCTION
	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
