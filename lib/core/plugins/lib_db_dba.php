<?php
// [LIB - Smart.Framework / Plugins / DBA Database Client]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - DBA Database Client
// DEPENDS:
// 	* SmartEnvironment::
//	* Smart::
//	* SmartFileSysUtils::
// 	* SmartHashCrypto::
//	* SmartFileSystem::
//	* SmartComponents::
// DEPENDS-EXT: PHP DBA Extension
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartDbaDb - provides a Client for DBA Databases.
 * By default this class will just log the errors.
 * It supports the following handlers: gdbm, qdbm, db4 which do not have limits on the record size.
 * The handler will be locked using LockFile as it is supported on all platforms and all handlers.
 *
 * The DBA file path should be starting with '#db/' or 'tmp/', which are protected via HtAccess files or by config.
 * Do not use other dir prefixes unless you know what you are doing ...
 *
 * <code>
 * // Example (must be set in etc/config.php)
 * $configs['dba']['handler'] = 'gdbm';
 * </code>
 *
 * @hints 		This class is mainly written to be used with DBA Key/Value store
 *
 * @usage 		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @access 		PUBLIC
 * @depends 	extensions: PHP DBA Extension ; classes: Smart, SmartEnvironment, SmartHashCrypto, SmartComponents, SmartFileSysUtils, SmartFileSystem
 * @version 	v.20250107
 * @package 	Application:Plugins:Database:Dba
 *
 */
final class SmartDbaDb {

	// ->

	private $file 		= null;
	private $dbres 		= '';
	private $dba 		= null;

	private $handler;
	private $hext;
	private $mode;
	private $nmode;
	private $omode;
	private $lock;

	private $fatal_err;
	private $err;
	private $description;


	//======================================================
	/**
	 * Class Constructor :: It try to initiate the DB connection or FAIL
	 *
	 * @param STRING $file The DBA DB Path ; If does not exists will be created, but will be re-suffixed (see getDbRealPath() in this class ...)
	 * @param STRING $y_description :: *OPTIONAL* The description of the DBA instance to make easy debug and log errors
	 * @param BOOLEAN $y_fatal_err :: *OPTIONAL* If Errors are Fatal or Not ... ; Set this parameter to TRUE if you want to Raise a fatal error on DBA errors ; otherwise default is FALSE and will ignore DBA errors but just log them as warnings (this is the wanted behaviour on a production server ...)
	 * @param FLOAT $y_debug_exch_slowtime :: *OPTIONAL* The Debug Slow Time in microseconds to Record slow Queries ; if > 0 will use this, otherwise will use the value from configs
	 */
	public function __construct($file, $y_description='DEFAULT', $y_fatal_err=false, $y_debug_exch_slowtime=0) {
		//--
		$this->err = false;
		//--
		$this->fatal_err = (bool) $y_fatal_err;
		//--
		$this->description = (string) trim((string)$y_description);
		//--
		$handler = (string) SmartDbaUtilDb::getDbaHandler();
		switch((string)$handler) { // {{{SYNC-SAFE-DBA-HANDLERS}}}
			case 'gdbm':
				$this->handler = 'gdbm';
				$hext = '.gdbm';
				break;
			case 'qdbm':
				$this->handler = 'qdbm';
				$hext = '.qdbm';
				break;
			case 'db4':
				$this->handler = 'db4';
				$hext = '.db4';
				break;
			default:
				$this->error('DBA INIT: Handler', 'ERROR: Invalid DBA Handler in Config: '.$handler);
				return;
		} //end switch
		//--
		if(((string)trim((string)$file) == '') OR ((string)substr((string)$file, -4, 4) != '.dba')) {
			$this->fatal_err = true; // raise fatal error
			$this->error('DBA INIT', 'ERROR: DB Path must end with .dba file extension !', 'Original DB Path: '.$file);
			return;
		} //end if
		//--
		$this->file = (string) $this->fixDbFileExtensionByHandler($file, $hext);
		if((string)trim((string)$this->file) == '') {
			$this->fatal_err = true; // raise fatal error
			$this->error('DBA INIT', 'ERROR: DB path is empty or invalid !');
			return;
		} //end if
		//--
		$this->mode = 'c'; // read/write access and database creation if it doesn't currently exist
		$this->nmode = 'n'; // read/write access and database truncate mode
		$this->omode = '?'; // must be set on open
		//--
		// using no lock make non-sense, it could read partial data ...
		// 'd' lock mode not supported on Windows at all
		// 'd' lock mode not supported by GDBM handler version 1.8.3 or later
		$this->lock = 'l'; // use always the 'l' locking by using an external lock file .lck ; this is widely supported and PHP will handle this
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			if($this->fatal_err === true) {
				$txt_conn = 'FATAL ERRORS';
			} else {
				$txt_conn = 'IGNORED BUT LOGGED AS WARNINGS';
			} //end if else
			//--
			if((float)$y_debug_exch_slowtime > 0) {
				$this->slow_time = (float) $y_debug_exch_slowtime;
			} else {
				$this->slow_time = (float) Smart::get_from_config('dba.slowtime', 'numeric');
			} //end if
			if($this->slow_time < 0.0000001) {
				$this->slow_time = 0.0000001;
			} elseif($this->slow_time > 0.9999999) {
				$this->slow_time = 0.9999999;
			} //end if
			SmartEnvironment::setDebugMsg('db', 'dba|slow-time', number_format($this->slow_time, 7, '.', ''), '=');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'metainfo',
				'data' => 'DBA App Connector Version: '.SMART_FRAMEWORK_VERSION.' // Connection Errors are: '.$txt_conn
			]);
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'metainfo',
				'data' => 'DBA Settings for `'.$this->description.'`: [ Handler='.$this->handler.' ; Mode='.$this->mode.' ; TruncateMode='.$this->nmode.' ; Locking='.$this->lock.' ]'
			]);
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'metainfo',
				'data' => 'Fast Query Reference Time < '.$this->slow_time.' seconds'
			]);
			//--
		} //end if
		//--
		if(!$this->isAvailable()) {
			$this->fatal_err = true; // raise fatal error
			$this->error('DBA INIT', 'ERROR: PHP DBA Extenstion not Found or DBA is missing the handler `'.$this->handler.'` !');
			return;
		} //end if
		//--
		$connect = (bool) $this->open();
		//--
		if((!$connect) OR (!$this->dba)) {
			$this->error('DBA Connect', 'Connection Failed ...');
			return;
		} //end if
		//--
		$this->dbres = (string) SmartHashCrypto::crc32b($this->file.'#'.$this->description.'@'.time()); // fix: since PHP 8.4 the DBA resource is a class than cannot be casted to string, so building an approximate similar resource for info purposes is handled here
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'open-close',
				'data' => 'DBA :: Connected to: '.$this->file.' :: '.$this->description.' @ Resource: '.$this->dbres,
			]);
		} //end if
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Class Destructor :: It try to close the DB connection if any
	 * Will randomly optimize the DB prior to close connection
	 */
	public function __destruct() {
		//--
		$this->close();
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Check if the PHP DBA Extension is available and also have the handler set in config
	 *
	 * @return BOOLEAN Will return TRUE if available and FALSE if not
	 */
	public function isAvailable() {
		//--
		if($this->dba) {
			return true;
		} //end if
		//--
		if((string)$this->handler == '') {
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'metainfo',
				'data' => 'DBA Handlers: '.implode(', ', (array)SmartDbaUtilDb::getDbaHandlers())
			]);
		} //end if
		//--
		return (bool) SmartDbaUtilDb::isDbaAndHandlerAvailable($this->handler);
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the Real Path to the DB File. If the DB instance failed will return empty string.
	 * The DB File Path will be re-suffixed with the handler's name in the file extension to avoid operate a handler on another handler's DB
	 * Ex: tmp/my-db.dba will be actually be: tmp/my-db.gdbm.dba (for the gdbm hanlder), tmp/my-db.qdbm.dba (for the qdbm hanlder), tmp/my-db.db4.dba (for the db4 hanlder)
	 *
	 * @return STRING The Real Path to the current DB File OR Empty string if there is no connection on this instance
	 */
	public function getDbRealPath() {
		//--
		if(!$this->dba) {
			return '';
		} //end if
		//--
		return (string) $this->file;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the TTL in seconds for a key from DB
	 * If will try to get the TTL for an expired key will automatically unset it and will return a result like the key does not exists
	 *
	 * @param STRING	$key		The Key
	 *
	 * @return INTEGER	number of seconds the key will expire ; -1 if the key does not expire (is persistent) ; -2 if the key does not exists ; -3 if N/A or ERR
	 */
	public function getTtl($key) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		$key = (string) Smart::normalize_spaces((string)$key);
		$key = (string) trim((string)$key);
		$chk = $this->check_key($key);
		if($chk !== true) {
			$this->error('DBA '.__FUNCTION__, 'ERROR: '.$chk);
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$hash = (string) SmartHashCrypto::sha256((string)$key);
		//--
		$op = @dba_fetch((string)$hash, $this->dba); // get :: mixed: false or string
		$rlen = 0;
		$ttl = -2; // default if not found is -2
		if($op !== false) {
			$rlen = (int) strlen((string)$op);
			$ttl = $this->getKeyTtlFromDbRecord( // will unset key if expired
				SmartDbaUtilDb::dataUnpack((string)$op), // mixed
				(string) $key,
				(string) $hash
			); // false or string
			$op = null; // free mem
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'read',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Key='.$key.' ; RawLength='.(int)$rlen,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => (int) $ttl,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		if($ttl === -3) {
			$ttl = -2; // expired, but emulate does not exists
		} elseif($ttl < -3) {
			$ttl = -3; // there was an error
		} //end if else
		//--
		return $ttl; // mixed: -3 / -2 / -1 / expiration in seconds (INTEGER+)
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get a Key from DB and return it's value
	 * If will try to get an expired key will automatically unset it and will return a result like the key does not exists
	 *
	 * @param STRING 	$key		The Key
	 *
	 * @return MIXED	Returns FALSE if the Key is expired or Invalid ; NULL if the Key does not exists ; STRING as the value of the given Key as set in DB
	 */
	public function getKey($key) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		$key = (string) Smart::normalize_spaces((string)$key);
		$key = (string) trim((string)$key);
		$chk = $this->check_key($key);
		if($chk !== true) {
			$this->error('DBA '.__FUNCTION__, 'ERROR: '.$chk);
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			$time_start = microtime(true);
			//--
		} //end if
		//--
		$hash = (string) SmartHashCrypto::sha256((string)$key);
		//--
		$op = @dba_fetch((string)$hash, $this->dba); // get :: mixed: false or string
		$rlen = 0;
		$value = null; // default if not found is null
		if($op !== false) {
			$rlen = (int) strlen((string)$op);
			$value = $this->getKeyValFromDbRecord(
				SmartDbaUtilDb::dataUnpack((string)$op), // mixed
				(string) $key,
				(string) $hash
			); // false or string
			$op = null; // free mem
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'read',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Key='.$key.' ; Status='.(($value === null) ? 'N/A' : ( ($value === false) ? 'Expired' : 'Found' )).' ; PackSize='.(int)$rlen.' ; Value='.Smart::text_cut_by_limit((string)$value, 1024, true, '[...data-longer-than-1024-bytes-is-not-logged-all-here...]'),
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => ($value === null) ? 0 : ( ($value === false) ? (-1 * (int)$rlen) : strlen((string)$value) ),
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		return $value; // mixed: null / false / string
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Set a Key in the DB with a given VALUE
	 * If Key Exists will replace it's value with new one
	 *
	 * @param STRING 	$key			The Key
	 * @param STRING 	$value			The value to be stored (if non-string must be sent as json-encoded string)
	 * @param INTEGER+ 	$expire 		Key Expiration in seconds (zero if key does not expire)
	 *
	 * @return BOOLEAN	Returns TRUE if the key was set or FALSE if not (that might be an error)
	 */
	public function setKey($key, $value, $expire=0) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		$key = (string) Smart::normalize_spaces((string)$key);
		$key = (string) trim((string)$key);
		$chk = $this->check_key($key);
		if($chk !== true) {
			$this->error('DBA '.__FUNCTION__, 'ERROR: '.$chk);
			return false;
		} //end if
		//--
		$expire = (int) $expire;
		if($expire > 0) {
			$expiration = (int) ((int)time() + (int)$expire); // {{{SYNC-PCACHE-EXPIRE}}}
		} else {
			$expiration = -1; // does not expire (compatible to Redis)
		} //end if else
		//--
		$data = (string) SmartDbaUtilDb::dataPack([
			'hash' 		=> (string) SmartHashCrypto::sha256((string)$key),
			'key' 		=> (string) $key,
			'expire' 	=> (int)    $expiration,
			'value' 	=> (string) $value
		]);
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		@dba_delete((string)SmartHashCrypto::sha256((string)$key), $this->dba); // first delete to try save space and avoid duplicates on some drivers
		$op = @dba_replace((string)SmartHashCrypto::sha256((string)$key), (string)$data, $this->dba); // insert or replace (upsert)
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'write',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Key='.$key.' ; Expire='.$expire.' ; Value='.Smart::text_cut_by_limit((string)$value, 1024, true, '[...data-longer-than-1024-bytes-is-not-logged-all-here...]'),
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => ($op === true) ? strlen((string)$value) : 0,
				'wsize' => true,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		if($op !== true) {
			$this->error('DBA '.__FUNCTION__, 'Operation FAILED', (string)$key);
			return false;
		} //end if
		//--
		return (bool) $op;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Check if a Key exists in the DB
	 * If will check an expired key will automatically unset it and will return a result like the key does not exists
	 *
	 * @param STRING	$key		The Key
	 *
	 * @return BOOLEAN	TRUE if Key Exists or FALSE if not
	 */
	public function keyExists($key) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		$key = (string) Smart::normalize_spaces((string)$key);
		$key = (string) trim((string)$key);
		$chk = $this->check_key($key);
		if($chk !== true) {
			$this->error('DBA '.__FUNCTION__, 'ERROR: '.$chk);
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//-- check if key exists
		$op = @dba_exists((string)SmartHashCrypto::sha256((string)$key), $this->dba);
		//-- if key exists check if expired ; if expired unset it and return the same answer as it would not exists
		if($op !== false) {
			if((int)$this->getTtl($key) < -1) { // returns: -2 expired, -3 error ...
				$op = false; // getKey will unset key if expired via getKeyValFromDbRecord
			} //end if
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'count',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Key='.$key,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => ($op === true) ? 1 : 0,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		// if $op is FALSE is not an error, it means key does not exists
		//--
		return (bool) $op;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Unset a Key from DB
	 * Will randomly optimize the DB
	 *
	 * @param STRING 	$key		The Key
	 *
	 * @return BOOLEAN	Returns TRUE if the key was unset or FALSE if not
	 */
	public function unsetKey($key) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		$key = (string) Smart::normalize_spaces((string)$key);
		$key = (string) trim((string)$key);
		$chk = $this->check_key($key);
		if($chk !== true) {
			$this->error('DBA '.__FUNCTION__, 'ERROR: '.$chk);
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$op = @dba_delete((string)SmartHashCrypto::sha256((string)$key), $this->dba);
		$this->optimizeDb(true); // randomly optimize DB
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'write',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Key='.$key,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => ($op === true) ? 1 : 0,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		// if $op is FALSE is not an error, it means key does not exists
		//--
		return (bool) $op;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Optimize the DB
	 *
	 * @param BOOLEAN 	$randomly	If set to TRUE will only randomly run it with a probability as of 1 in 1000
	 *
	 * @return BOOLEAN	Returns TRUE if was running or FALSE if not
	 */
	public function optimizeDb($randomly=false) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			return false;
		} //end if
		//--
		if($randomly === true) {
			if(Smart::random_number(0, 1000) != 501) {
				return false; // stop running optimize ... probabilistic !
			} //end if
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$op = @dba_optimize($this->dba);
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'special',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Optimize DB',
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => ($op === true) ? 1 : 0,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		return (bool) $op;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the list with all available keys in the DB
	 * Will clear al expired key while iterating on all DB records to get the list
	 * Will optimize the DB
	 *
	 * @return ARRAY A non-associative array with all, non-expired keys in DB or empty array if no key available ; Ex: [ key1, key2, ..., keyN ]
	 */
	public function getKeysList() {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return [];
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return [];
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$xarr = (array) $this->iterateAllDbKeysAndCleanup();
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'nosql',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Get All Keys From DB',
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => (int) Smart::array_size($xarr),
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		return (array) $xarr;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Clear all expired keys from the DB
	 * Will have to iterate on all keys in DB
	 * Will optimize the DB
	 *
	 * @param INTEGER+ $limit How many Keys to iterate ; If zero will iterate over all ; If non zero and positive will stop after reaching this limit
	 * @return BOOLEAN Will return TRUE if success or FALSE if not
	 */
	public function clearExpiredKeys($limit=0) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$xarr = (array) $this->iterateAllDbKeysAndCleanup($limit);
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'special',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Clear Expired Keys from DB',
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => (int) Smart::array_size($xarr),
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * TRUNCATE The Database by clearing (erasing) all data
	 * This may break current transactions if called
	 *
	 * @return BOOLEAN Will return TRUE on success and FALSE if FAIL
	 */
	public function truncateDb($testFirstKey=false) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#DBA# :: '.__FUNCTION__.'() Method Aborted. Detected Previous DBA Error before calling this method');
			} //end if
			return false;
		} //end if
		//--
		if(!$this->dba) {
			$this->error('DBA '.__FUNCTION__, 'No DB Connection');
			return false;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		$this->close(false); // do not halt driver, must reconnect
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|total-time', $time_end, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'special',
				'data' => __FUNCTION__.' :: '.$this->description,
				'command' => 'Clear all Data (will re-init the connection if any): '.$this->file,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' => (!$this->dba) ? 1 : 0,
				'connection' => (string) $this->dbres,
			]);
			//--
		} //end if
		//--
		if($this->dba) { // if connection closed
			$this->error('DBA '.__FUNCTION__, 'Closing DB Connection FAILED');
			return false;
		} //end if
		//--
		$op = (bool) $this->open(true); // open with truncate
		//--
		if($testFirstKey === true) {
			if($this->dba) {
				if($op) {
					if(@dba_firstkey($this->dba) !== false) { // if a key found, is not empty (other processes may write but for tests this can be used)
						$op = false;
					} //end if
				} //end if
			} else {
				$op = false;
			} //end if else
		} //end if
		//--
		return (bool) $op;
		//--
	} //END FUNCTION
	//======================================================


	//===== PRIVATES


	//======================================================
	private function iterateAllDbKeysAndCleanup($limit=0) {
		//--
		$key = @dba_firstkey($this->dba);
		if($key === false) {
			return [];
		} //end if
		$arr = [];
		$arr[] = (string) $key;
		$i = 0;
		while($key = @dba_nextkey($this->dba)) {
			if((int)$limit > 0) { // must be at least 1
				if((int)$limit < 2) { // 1st key is read outside the loop
					break;
				} //end if
			} //end if
			if(!$key) {
				break;
			} //end if
			$arr[] = (string) $key;
			$i++;
			if((int)$limit > 0) { // must be at least 1
				if((int)$i >= (int)((int)$limit - 1)) { // 1st key is read outside the loop
					break;
				} //end if
			} //end if
		} //end while
		//--
		$xarr = [];
		//--
		if(Smart::array_size($arr) > 0) {
			for($i=0; $i<Smart::array_size($arr); $i++) {
				$op = @dba_fetch((string)$arr[$i], $this->dba);
				if($op !== false) {
					//--
					$hash = (string) $arr[$i];
					//-- getKeyIdFromDbRecord will clear expired records
					$key = $this->getKeyIdFromDbRecord(
						SmartDbaUtilDb::dataUnpack((string)$op), // mixed
						(string) $hash
					);
					if($this->check_key($key) === true) {
						$xarr[(string)$hash] = (string) $key;
					} //end if
					//--
				} //end if
			} //end for
			if(Smart::array_size($xarr) !== Smart::array_size($arr)) {
				@dba_optimize($this->dba); // optimize DB
			} //end if
		} //end if
		//--
		return (array) $xarr;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function checkDbRecordIntegrity($value, $key, $hash, $checkKey=true) {
		//--
		if(!is_array($value)) {
			return false;
		} //end if
		//--
		if(
			(array_key_exists('hash', (array)$value))  									AND
			(array_key_exists('key', (array)$value))  									AND
			(array_key_exists('expire', (array)$value)) 								AND
			(array_key_exists('value', (array)$value)) 									AND
			((string)$value['hash'] === (string)$hash) 									AND
			(preg_match('/^[a-f0-9]+$/', (string)$hash)) 								AND
			((int)strlen((string)$hash) === 64) 										AND
			((string)SmartHashCrypto::sha256((string)$value['key']) === (string)$hash) 	AND
			((int)$value['expire'] >= -1)
		) {
			if($checkKey !== false) {
				if((string)$value['key'] !== (string)$key) {
					return false;
				} //end if
			} //end if
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	// return mixed: false (if invalid or expired) else string
	private function getKeyIdFromDbRecord($value, $hash) {
		//--
		if($this->checkDbRecordIntegrity($value, null, $hash, false) !== true) {
			return false; // invalid record
		} //end if
		//--
		if(
			((int)$value['expire'] <= 0) OR  // does not expires
			(((int)$value['expire'] > 0) AND ((int)$value['expire'] > (int)time())) // expires but not yet expired
		) { // not expired
			$key = (string) $value['key']; // ok
		} else {
			$key = false; // expired
			@dba_delete((string)$hash, $this->dba); // remove expired
		} //end if else
		//--
		return $key;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	// return mixed: -4 (if invalid) ; -3 if expired ; -2 is reserved in parent function as default, key not found ; -1 if does not expire ; positive integer with expiration in seconds
	private function getKeyTtlFromDbRecord($value, $key, $hash) {
		//--
		if($this->checkDbRecordIntegrity($value, $key, $hash) !== true) {
			return -4; // invalid record
		} //end if
		//--
		if((int)$value['expire'] <= 0) {
			$value = -1; // does not expire
		} elseif(((int)$value['expire'] > 0) AND ((int)$value['expire'] > (int)time())) {
			$value = (int) ((int)$value['expire'] - (int)time()); // {{{SYNC-PCACHE-TTL}}}
			if($value < 0) {
				$value = 0;
			} //end if
		} else {
			$value = -3; // expired
			@dba_delete((string)$hash, $this->dba); // remove expired
		} //end if else
		//--
		return (int) $value;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	// return mixed: false (if invalid or expired) else string
	private function getKeyValFromDbRecord($value, $key, $hash) {
		//--
		if($this->checkDbRecordIntegrity($value, $key, $hash) !== true) {
			return false; // invalid record
		} //end if
		//--
		if(
			((int)$value['expire'] <= 0) OR  // does not expires
			(((int)$value['expire'] > 0) AND ((int)$value['expire'] > (int)time())) // expires but not yet expired
		) { // not expired
			$value = (string) $value['value']; // ok
		} else {
			$value = false; // expired
			@dba_delete((string)$hash, $this->dba); // remove expired
		} //end if else
		//--
		return $value;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function check_key($key) {
		//--
		if((string)trim((string)$key) == '') {
			return 'The Key is Empty';
		} //end if
		if(strlen((string)$key) > 255) {
			return 'The Key is Too Long';
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function open($truncate=false) {
		//--
		if($this->err !== false) {
			return false;
		} //end if
		//--
		if($truncate === true) {
			$this->omode = (string) $this->nmode;
		} else {
			$this->omode = (string) $this->mode;
		} //end if else
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'dba|log', [
				'type' => 'open-close',
				'data' => 'DBA :: '.($truncate === true ? 'Truncate @ Re' : '').'Open Connection ['.$this->omode.'] to: '.$this->file.' :: '.$this->description
			]);
		} //end if
		//--
		if($this->dba) {
			return true;
		} //end if
		//--
		if(SmartFileSysUtils::checkIfSafePath((string)$this->file, true, true) != 1) { // deny absolute path access ; allow protected path access (starting with #)
			$this->error('OPEN', 'ERROR: DB path is invalid !');
			return false;
		} //end if
		//--
		if(SmartFileSystem::is_type_dir((string)$this->file)) {
			$this->error('OPEN', 'ERROR: DB path is a directory !');
			return false;
		} //end if
		//--
		$file_of_db = (string) SmartFileSysUtils::extractPathFileName((string)$this->file);
	//	$dir_of_db = (string) SmartFileSysUtils::extractPathDir((string)$this->file);
		$dir_of_db = (string) Smart::dir_name((string)$this->file); // sync with SQLite, this method is better as if empty, the checks are below in create
		//--
		if(SmartFileSysUtils::checkIfSafeFileOrDirName((string)$file_of_db, true, true) != 1) {
			$this->error('OPEN', 'ERROR: DB file name is unsafe !');
			return false;
		} //end if
		//--
		$err = (string) SmartFileSystem::create_protected_dir((string)$dir_of_db); // {{{SYNC-APP-DB-FOLDER}}} ; this checks also if safe path of dir
		if((string)$err != '') {
			$this->error('OPEN', 'ERROR: DB path creation failed # '.$err);
			return;
		} //end if
		//--
		$dir_of_db = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dir_of_db);
		if(SmartFileSysUtils::checkIfSafePath((string)$dir_of_db, true, true) != 1) {
			$this->error('OPEN', 'ERROR: DB folder path is unsafe !');
			return false;
		} //end if
		//--
	//	$the_abs_path_to_db = (string) $this->file;
		$the_abs_path_to_db = (string) rtrim((string)Smart::real_path((string)$dir_of_db), '/').'/'.$file_of_db; // on Windows requires an absolute path to work ; detect as: ((string)DIRECTORY_SEPARATOR == '\\')
		if(!SmartFileSysUtils::checkIfSafePath((string)$the_abs_path_to_db, false, true)) { // allow both: absolute and protected paths here
			$this->error('OPEN', 'ERROR: DB unsafe absolute path: `'.$the_abs_path_to_db.'`'); // this will be an absolute path, bust still must be safe
			return false;
		} //end if
		//--
		$this->dba = @dba_open((string)$the_abs_path_to_db, (string)$this->omode.$this->lock, (string)$this->handler, (defined('SMART_FRAMEWORK_CHMOD_FILES') ? SMART_FRAMEWORK_CHMOD_FILES : 0664)); // open connection
		//--
		SmartEnvironment::$Connections['dba'][(string)$this->handler.'#'.$this->file.'#'.$this->omode.$this->lock] = (bool) $this->dba; // just register true if openend ...
		//--
		return (bool) $this->dba;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function close($halt=true) {
		//--
		if($this->dba) {
			//--
			if(SmartEnvironment::ifDebug()) {
				if($halt !== false) {
					SmartEnvironment::setDebugMsg('db', 'dba|log', [
						'type' => 'open-close',
						'data' => 'DBA :: Close Connection to: '.$this->file.' :: '.$this->description.' @ Resource: '.$this->dbres,
					]);
				} //end if
			} //end if
			//--
			@dba_sync($this->dba); // ensure sync before closing ... normally not needed but anyway, this function does exists :-)
			$this->optimizeDb(true); // randomly optimize DB
			@dba_close($this->dba); // close connection :: {{{SYNC-DBA-CLOSE}}}
			$this->dba = null;
			//--
			unset(SmartEnvironment::$Connections['dba'][(string)$this->handler.'#'.$this->file.'#'.$this->omode.$this->lock]);
			//--
			if($halt !== false) {
				$this->err = true; // required, to halt driver, no more allow operations and avoid reconnect, was explicit destroyed
			} //end if
			//--
		} //end if
		//--
		return true; // always return true
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function fixDbFileExtensionByHandler($file, $hext) {
		//--
		$file = (string) trim((string)$file);
		$file = (string) trim((string)$file, '.');
		$file = (string) trim((string)$file);
		if((string)$file == '') {
			return '';
		} //end if
		//--
		$hext = (string) trim((string)$hext);
		$hext = (string) trim((string)$hext, '.');
		$hext = (string) trim((string)$hext);
		//--
		if((string)$hext != '') {
			if((string)substr((string)$file, -4, 4) == '.dba') {
				if(strlen((string)$file) > 4) {
					if((string)substr((string)$file, 0, 1) != '.') {
						$file = (string) substr((string)$file, 0, -4);
						$file = (string) trim((string)$file);
						$file = (string) trim((string)$file, '.');
						$file = (string) trim((string)$file);
						if((string)$file == '') {
							return '';
						} //end if
						$file .= '.'.$hext.'.dba';
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return (string) $file;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Displays the DBA Errors and HALT EXECUTION (This have to be a FATAL ERROR as it occur when a FATAL DBA ERROR happens or when Data Exchange fails)
	 * PRIVATE
	 *
	 * @param STRING $y_area :: The Area
	 * @param STRING $y_error_message :: The Error Message to Display
	 * @param STRING $y_key :: The key
	 * @param STRING $y_warning :: The Warning Title
	 *
	 * @return :: HALT EXECUTION WITH ERROR MESSAGE
	 *
	 */
	private function error($y_area, $y_error_message, $y_key='', $y_warning='') {
		//--
		$this->err = true; // required, to halt driver
		//--
		$is_fatal = (bool) $this->fatal_err;
		//--
		if($is_fatal === false) { // NON-FATAL ERROR
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'dba|log', [
					'type' => 'metainfo',
					'data' => 'DBA (`'.$this->description.'`) :: SILENT WARNING: '.$y_area."\n".'Key: '.$y_key."\n".'Error-Message: '.$y_error_message."\n".'The settings for this DBA instance allow just silent warnings on connection fail.'."\n".'All next method calls to this DBA instance will be discarded silently ...'
				]);
			} //end if
			Smart::log_warning('#DBA@'.$this->file.'# (`'.$this->description.'`) :: Q# // DBA Client :: NON-FATAL ERROR :: '.$y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Key:'."\n".$y_key);
			return;
		} //end if
		//--
		$def_warn = 'Execution Halted !';
		$y_warning = (string) trim((string)$y_warning);
		if(SmartEnvironment::ifDebug()) {
			$width = 750;
			$the_area = (string) $y_area;
			if((string)$y_warning == '') {
				$y_warning = (string) $def_warn;
			} //end if
			$the_error_message = 'Operation FAILED: '.$def_warn."\n".$y_error_message;
			$the_params = '- '.$this->description.' -';
			$the_query_info = (string) $y_key;
			if((string)$the_query_info == '') {
				$the_query_info = '-'; // query cannot e empty in this case (templating enforcement)
			} else {
				$the_query_info = 'Key: '.$the_query_info;
			} //end if
		} else {
			$width = 550;
			$the_area = '';
			$the_error_message = 'Operation FAILED: '.$def_warn;
			$the_params = '';
			$the_query_info = ''; // do not display query if not in debug mode ... this a security issue if displayed to public ;)
		} //end if else
		//--
		$out = '';
		if(class_exists('SmartComponents')) {
			$out = (string) SmartComponents::app_error_message(
				'Dba Client',
				'Dba',
				'Embedded',
				'DataStore/DB',
				'lib/core/img/db/dba-logo.svg',
				(int)    $width, // width
				(string) $the_area, // area
				(string) $the_error_message, // err msg
				(string) $the_params, // title or params
				(string) $the_query_info // key
			);
		} //end if
		//--
		Smart::raise_error(
			'#DBA@'.$this->file.'# (`'.$this->description.'`) :: Q# // DBA Client :: ERROR :: '.$y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Key:'."\n".$y_key,
			$out, // msg to display
			true // is html
		);
		die(''); // just in case
		//--
	} //END FUNCTION
	//======================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartDbaUtilDb - provides a Static Utility for the DBA Database Client.
 *
 * @usage 		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	extensions: PHP DBA Extension ; classes: Smart
 * @version 	v.20250107
 * @package 	Application:Plugins:Database:Dba
 *
 */
final class SmartDbaUtilDb {

	// ::

	private static $handlers = null;
	private static $handler = null;


	/**
	 * Check if the DBA Extension is available with the given handler or if empty with the handler set in configs
	 *
	 * @param STRING $handler *Optional* The handler to check ; leave it empty to check the handler from configs: dba.handler
	 * @return BOOLEAN Will return TRUE if DBA extension is available and the handler is available ; FALSE if not
	 */
	public static function isDbaAndHandlerAvailable($handler='') {
		//--
		if((string)$handler == '') {
			$handler = (string) self::getCfgHandler();
			if((string)$handler == '') {
				return false;
			} //end if
		} //end if
		//--
		$handlers = (array) self::getDbaHandlers();
		if(Smart::array_size($handlers) <= 0) {
			return false;
		} //end if
		//--
		if(!in_array((string)$handler, (array)$handlers)) { // {{{SYNC-DBA-HANDLER-AVAIL-CHECK}}}
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	/**
	 * Get the current DBA Handler
	 *
	 * @return STRING The DBA Handler from configs, if available, or empty string if not
	 */
	public static function getDbaHandler() {
		//--
		$handler = (string) self::getCfgHandler();
		//--
		$handlers = (array) self::getDbaHandlers();
		if(Smart::array_size($handlers) <= 0) {
			return '';
		} //end if
		//--
		if(!in_array((string)$handler, (array)$handlers)) {
			return '';
		} //end if
		//--
		return (string) $handler;
		//--
	} //END FUNCTION


	/**
	 * Get the list of available DBA Handlers
	 *
	 * @return ARRAY The array list of available handlers or empty array if DBA extension is not found
	 */
	public static function getDbaHandlers() {
		//--
		if(is_array(self::$handlers)) {
			return (array) self::$handlers;
		} //end if
		//--
		if(function_exists('dba_handlers')) {
			self::$handlers = (array) @dba_handlers(false); // get simple list of DBA Handlers
		} else {
			self::$handlers = [];
		} //end if
		//--
		return (array) self::$handlers;
		//--
	} //END FUNCTION


	/**
	 * Pack the data using a safe serialized format
	 *
	 * @param MIXED $originalData The data to be packed ; can be: string, array, number ...
	 * @return STRING the packed data
	 */
	public static function dataPack($originalData) {
		//--
		return (string) Smart::seryalize($originalData);
		//--
	} //END FUNCTION


	/**
	 * UnPack the data from packed serialized format to it's original state
	 *
	 * @param STRING $packedData The previous packed data
	 * @return MIXED the unpacked data ; can be: string, array, number ...
	 */
	public static function dataUnpack($packedData) {
		//--
		return Smart::unseryalize((string)$packedData); // mixed
		//--
	} //END FUNCTION


	//=====


	// the allowed safe handlers that have no limits on record data size and are fast and reliable, in prefered order
	private static function allowedSafeHandlers() {
		//--
		return [ // {{{SYNC-SAFE-DBA-HANDLERS}}}
			'gdbm', // GNU DB, aka GDBM
			'qdbm', // Quick Database Manager, aka QDBM
			'db4' 	// Berkeley DB v4 aka DB4
		];
		//--
	} //END FUNCTION


	private static function getCfgHandler() {
		//--
		if(self::$handler !== null) {
			return (string) self::$handler;
		} //end if
		//--
		$arr = (array) Smart::get_from_config('dba', 'array');
		//--
		if(is_array($arr)) {
			if((string)$arr['handler'] == '@autoselect') {
				self::$handler = '';
				$handlers = (array) self::getDbaHandlers();
				$safehanders = (array) self::allowedSafeHandlers();
				if((Smart::array_size($handlers) > 0) AND (Smart::array_size($safehanders) > 0)) {
					for($i=0; $i<Smart::array_size($safehanders); $i++) { // {{{SYNC-DBA-HANDLER-AVAIL-CHECK}}}
						if(in_array((string)$safehanders[$i], (array)$handlers)) { // {{{SYNC-DBA-HANDLER-AVAIL-CHECK}}}
							self::$handler = (string) $safehanders[$i];
							break;
						} //end if
					} //end for
				} //end if
			} else {
				self::$handler = (string) $arr['handler'];
			} //end if else
		} else {
			self::$handler = ''; // must be non-null
		} //end if else
		//--
		return (string) self::$handler;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
