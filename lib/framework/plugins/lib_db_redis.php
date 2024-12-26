<?php
// [LIB - Smart.Framework / Plugins / Redis Database Client]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Redis Database Client
// DEPENDS:
//	* Smart::
//	* SmartEnvironment
//	* SmartComponents:: (optional)
// DEPENDS-EXT: PHP Sockets
//======================================================

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// based on TinyRedisClient - the most lightweight Redis client written in PHP, by Petr Trofimov https://github.com/ptrofimov
// with portions of code from: PRedis StreamConnection, by Daniele Alessandri https://github.com/nrk/predis

/**
 * Class: SmartRedisDb - provides a Client for Redis MemDB Server.
 * By default this class will just log the errors.
 *
 * Tested and Stable on Redis versions: 3.x / 4.x / 5.x / 6.x / 7.x
 *
 * <code>
 *
 * // Redis Client usage example, with custom connection parameters:
 * $redis = new SmartRedisDb('SampleInstance', false, 'localhost', '6379', 3); // connects at the database no. 3 ; for debug and log will identify by 'SampleInstance' (1st param) ; 2nd param set the fatal errors to false and just log errors not raise fatal errors ... (this is default behaviour)
 * $redis->set('key1', 'value1');
 * $redis->set('list1:key1', 'value2');
 * $value1 = $redis->get('key1');
 * if($redis->exists('key1')) {
 *     $value2 = $redis->get('list5:key7');
 * } //end if
 *
 * </code>
 *
 * @usage 		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		for the rest of supported methods take a look at the SmartRedisDb class magic __call method ; Visit: http://redis.io/commands ; Most of the base methods are implemented.
 *
 * @access 		PUBLIC
 * @depends 	extensions: PHP Sockets ; classes: Smart, SmartEnvironment, SmartComponents (optional)
 * @version 	v.20241218
 * @package 	Plugins:Database:Redis
 *
 */
final class SmartRedisDb {

	// ->

	/**
	 * The Receiving Buffer Length (512..16384) ; Default is 4096
	 * @var int
	 * @ignore
	 */
	public $recvbuf = 4096;

	/** @var string */
	private $server;

	/** @var integer */
	private $db;

	/** @var integer+ */
	private $timeout;

	/** @var string */
	private $password;

	/** @var resource */
	private $socket;

	/** @var string */
	private $description;

	/** @var string */
	private $fatal_err;

	/** @var string */
	private $err;

	/** @var float */
	private $slow_time = 0.0005;

	//======================================================
	/**
	 * Object Constructor
	 * If no value is provided for Host, Port and DbNum it will use the values from the config[redis]
	 *
	 * <code>
	 * // Sample Redis configuration of Default Instance (must be set in etc/config.php)
	 * $configs['redis']['server-host']	= '127.0.0.1';							// redis host
	 * $configs['redis']['server-port']	= 6379;									// redis port
	 * $configs['redis']['dbnum'] 		= 8;									// redis db number 0..15
	 * $configs['redis']['password']	= '';									// redis Base64-Encoded password ; by default is empty
	 * $configs['redis']['timeout']		= 5;									// redis connect timeout in seconds
	 * $configs['redis']['slowtime']	= 0.0005;								// redis slow query time (for debugging) 0.0010 .. 0.0001
	 * </code>
	 *
	 * @param STRING $y_description 		:: *OPTIONAL* Default is 'DEFAULT' ; The description of the Redis connection to make easy debug and log errors
	 * @param BOOLEAN $y_fatal_err 			:: *OPTIONAL* Default is FALSE ; If Errors are Fatal or Not ... ; Set this parameter to TRUE if you want to Raise a fatal error on Redis errors ; otherwise default is FALSE and will ignore Redis errors but just log them as warnings (this is the wanted behaviour on a production server ...)
	 * @param STRING $host 					:: *OPTIONAL* The Redis Server Host (Ex: '127.0.0.1')
	 * @param INTEGER $port 				:: *OPTIONAL* The Redis Server Port (Ex: 6379)
	 * @param INTEGER+ $db 					:: *OPTIONAL* The Redis Server DB Number (Ex: 0) ; By Default min: 0 and max: 15 (16 databases) ; if redis.conf specify more than 16, can be a larger number, min: 0 ; max 255
	 * @param STRING $password 				:: *OPTIONAL* The Redis Auth Password ; Default is Empty String
	 * @param INTEGER+ $timeout 			:: *OPTIONAL* The connection TimeOut in seconds
	 * @param FLOAT $y_debug_exch_slowtime 	:: *OPTIONAL* The Debug Slow Time in microseconds to Record slow Queries
	 *
	 */
	public function __construct($y_description='DEFAULT', $y_fatal_err=false, $host='', $port='', $db='', $password='', $timeout=5, $y_debug_exch_slowtime=0.0005) {
		//--
		$this->err = false;
		//--
		$this->fatal_err = (bool) $y_fatal_err;
		//--
		$this->description = (string) trim((string)$y_description);
		//--
		if(((string)$host == '') AND ((string)$port == '') AND ((string)$db == '')) {
			//-- use values from configs
			$redis_cfg 				= (array)  Smart::get_from_config('redis', 'array');
			//--
			$host 					= (string) ($redis_cfg['server-host'] ?? null);
			$port 					= (string) ($redis_cfg['server-port'] ?? null);
			$db   					= (string) ($redis_cfg['dbnum']       ?? null);
			$password 				= (string) ($redis_cfg['password']    ?? null);
			$timeout 				= (int)    ($redis_cfg['timeout']     ?? null);
			$y_debug_exch_slowtime 	= (float)  ($redis_cfg['slowtime']    ?? null);
			//--
		} //end if
		//--
		if(((string)$host == '') OR ((string)$port == '') OR ((string)$db == '') OR ((string)$timeout == '')) {
			$this->error('Redis Configuration Init', 'Some Required Parameters are Empty', 'CFG:host:port@db#timeout'); // fatal error
			return;
		} //end if
		//--
		$this->server = $host.':'.$port;
		//--
		$this->db = Smart::format_number_int($db, '+');
		if($this->db < 0) {
			$this->db = 0;
		} //end if
		if($this->db > 255) {
			$this->db = 255;
		} //end if
		//--
		$this->timeout = Smart::format_number_int($timeout, '+');
		if($this->timeout < 1) {
			$this->timeout = 1;
		} //end if
		if($this->timeout > 30) {
			$this->timeout = 30;
		} //end if
		//--
		if((string)$password != '') {
			$this->password = (string) base64_decode((string)$password);
		} else {
			$this->password = '';
		} //end if else
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
			} //end if
			if($this->slow_time < 0.0000001) {
				$this->slow_time = 0.0000001;
			} elseif($this->slow_time > 0.9999999) {
				$this->slow_time = 0.9999999;
			} //end if
			//--
			SmartEnvironment::setDebugMsg('db', 'redis|slow-time', number_format($this->slow_time, 7, '.', ''), '=');
			SmartEnvironment::setDebugMsg('db', 'redis|log', [
				'type' => 'metainfo',
				'data' => 'Redis App Connector Version: '.SMART_FRAMEWORK_VERSION.' // Connection Errors are: '.$txt_conn
			]);
			//--
		} //end if
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * set the RecvBuff
	 *
	 * @access 		private
	 * @internal
	 *
	 * @param INTEGER $buff :: The Receiving Buffer Length (512..16384) ; Default is 4096
	 * @returns VOID
	 *
	 */
	public function setRecvBuf($buff) {
		//--
		$this->recvbuf = (int) $buff;
		//--
		if($this->recvbuf < 512) {
			$this->recvbuf = 512;
		} elseif($this->recvbuf > 16384) {
			$this->recvbuf = 16384;
		} //end if
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * This is the Magic Method (Call) that maps the PHP class extra methods to Redis methods.
	 * It have variadic parameters mapped to Redis sub-calls.
	 * The most common Redis methods are documented here. For the rest of the Redis methods see: https://redis.io/commands
	 * There are some methods that are not available in this driver because they need to use more than one database and this is not supported (this driver binds to only one database at a time). Example: MOVE (but MOVE can be emulated with an example by using two Redis instances that will GET the key from DB#1/connection#1 SET the key in DB#2/connection#2 and if successful UNSET the key from DB#1/connection#1)
	 * NOTICE: Not all Redis methods were ported to this driver but the most essential methods are available.
	 *
	 * @magic
	 *
	 * @method	STRING		ping()										:: Ping the Redis server ; returns: the test answer which is always PONG
	 * @method	STRING		watch(STRING $keys)							:: Must be used before starting the transaction (MULTI) ; Marks the given key(s) to be watched for conditional execution of a transaction ; A single key can be passed as 'key1'; If multiple keys have to be passed they have to be separed by comma as: 'key1, key2' ; This method always return: OK
	 * @method	STRING		unwatch()									:: Can be used after ending the transaction (EXEC) ; Flushes all the previously watched keys for a transaction ; If EXEC or DISCARD, there's not mandatory to manually call UNWATCH ; This method always return: OK
	 * @method	STRING		multi()										:: Marks the start of a transaction block. Subsequent commands will be queued for atomic execution using EXEC ; This method always return: OK ; NOTICE: all subsequent commands when used in a transaction block will return: QUEUED (instead of OK, 1, ... or other responses)
	 * @method	ARRAY		exec()										:: Executes all previously queued commands in a transaction started by MULTI ; When using WATCH before MULTI, EXEC will execute commands only if the watched keys were not modified, allowing for a check-and-set mechanism ; if using DISCARD all the statements before DISCARD will be discarded and will not be executed ; When using WATCH, EXEC can return a Null reply if the execution was aborted ; If not a Null reply the returned array looks like [ 0 => OK, 1 => OK, ..., 2 => 1, ..., 5 => OK ] (non-associative array), each entry represent the result of each statement after EXEC
	 * @method	STRING		discard()									:: Flushes all previously queued commands in a transaction ; If required, must be called withing transaction (after MULTI and before EXEC) ; This method always return: OK
	 * @method	INT			exists(STRING $key)							:: Determine if a key exists ; returns 1 if the key exists or 0 if does not exists
	 * @method	MIXED		get(STRING $key) 							:: Get a Redis Key ; returns: the value of key as STRING or NULL if not exists
	 * @method	MIXED	 	set(STRING $key, STRING $value)				:: Set a Redis Key ; returns: OK on success or NULL on failure
	 * @method	MIXED		append(STRING $key, STRING $value)			:: Append a Redis Key ; returns: OK on success or NULL on failure
	 * @method	MIXED		incr(STRING $key)							:: Increments a key that have an integer value with 1 ; max is 64-bit int ; if the value is non integer returns error ; returns the value after increment
	 * @method	MIXED		incrby(STRING $key, INTEGER $value)			:: Increments a key that have an integer value with the given int value ; max is 64-bit int ; if the value is non integer returns error ; returns the value after increment
	 * @method	MIXED		decr(STRING $key)							:: Decrements a key that have an integer value with 1 ; min is 64-bit int ; if the value is non integer returns error ; returns the value after decrement
	 * @method	MIXED		decrby(STRING $key, INTEGER $value)			:: Decrements a key that have an integer value with the given int value ; min is 64-bit int ; if the value is non integer returns error ; returns the value after decrement
	 * @method	STRING		rename(STRING $key, STRING $newkey) 		:: Renames key to newkey. It returns an error when key does not exist or OK. If newkey already exists it is overwritten
	 * @method	INT			del(STRING $key) 							:: Delete a Redis Key ; returns: 0 if not successful or 1 if successful
	 * @method	INT			ttl(STRING $key)							:: Get the TTL in seconds for a key ; -1 if the key does not expire ; -2 if the key does not exists
	 * @method	INT			expire(STRING $key, INT $expireinseconds)	:: Set the Expiration time for a Redis Key in seconds ; returns: 0 if not successful or 1 if successful
	 * @method	INT			expireat(STRING $key, INT $expirationtime)	:: Set the Expiration time for a Redis Key at unixtimestamp ; returns: 0 if not successful or 1 if successful
	 * @method	INT			persist(STRING $key)						:: Remove the existing expiration timeout on a key ; returns: 0 if not successful or 1 if successful
	 * @method	STRING		type(STRING $key)							:: Returns the string representation of the type of the value stored at key (string, list, set, zset, hash and stream)
	 * @method	INT			strlen(STRING $key) 						:: Returns the length of the string value stored at key. An error is returned when key holds a non-string value
	 * @method	MIXED		keys(STRING $pattern)						:: Get all keys matching a pattern ; return array of all keys matching a pattern or null if no key
	 * @method	MIXED		randomkey()									:: Return a random key from the currently selected database ; if no key exist, returns NULL
	 * @method	STRING		info()										:: Returns information and statistics about the server
	 * @method	INT			dbsize()									:: Return the number of keys in the currently-selected database
	 * @method	STRING		flushdb()									:: Remove all keys from the selected database ; This command never fails ; Returns OK
	 */
	public function __call($method, array $args) {
		//--
		if($this->err !== false) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice('#REDIS-DB# :: Method Call Aborted. Detected Previous Redis Error before calling the method: '.$method.'()');
			} //end if
			return null;
		} //end if
		//--
		$method = (string) strtoupper((string)$method);
		$args = (array) $args;
		//--
		switch((string)$method) {
			//--
			case 'MULTI': // start transaction
			case 'EXEC': // commit transaction ; when using WATCH, EXEC will execute commands only if the watched keys were not modified, allowing for a check-and-set mechanism
			case 'DISCARD': // flushes all previously queued commands in a transaction
			case 'WATCH': // arguments (key) or (key1) marks the given keys to be watched for conditional execution of a transaction ; if key is modified meanwhile it will not be operated by EXEC
			case 'UNWATCH': // (no arguments) ; flushes all the previously watched keys for a transaction
			//--
			case 'EXISTS': // determine if a key exists ; returns 1 if the key exists or 0 if does not exists
			case 'TYPE': // determine the type of the given key
			case 'STRLEN': // gets the strlen of a key
			//--
			case 'TTL': // get the TTL in seconds for a key ; -1 if the key does not expire ; -2 if the key does not exists
			case 'EXPIRE': // set the expire time for a key in seconds ; returns 1 on success or 0 on failure
			case 'EXPIREAT': // like EXPIRE but instead of set how many seconds to persist it sets the unix timestamp when will expire
			case 'PERSIST': // remove the existing expiration timeout on key
			//--
			case 'GET': // get a key value ; returns the key value or null
			case 'SET': // set a key with a value ; returns OK if successful
			case 'APPEND': // append a value to an existing key value
			case 'DEL': // delete a key ; a key is ignored if does not exists ; return (integer) the number of keys that have been deleted
			//--
			case 'RENAME': // renames key to newkey ; returns an error when the source and destination names are the same, or when key does not exist
		//	case 'MOVE': // move a key to the given DB ; returns 1 on success or 0 on failure ; !!! this is not implemented because operates on many databases and the current driver allow just per database management !!!
			//--
			case 'INCR': // increments a key that have an integer value with 1 ; max is 64-bit int ; if the value is non integer returns error ; returns the value after increment
			case 'INCRBY': // increments a key that have an integer value with the given int value ; max is 64-bit int ; if the value is non integer returns error ; returns the value after increment
			case 'DECR': // decrements a key that have an integer value with 1 ; min is 64-bit int ; if the value is non integer returns error ; returns the value after decrement
			case 'DECRBY': // decrements a key that have an integer value with the given int value ; min is 64-bit int ; if the value is non integer returns error ; returns the value after decrement
			//--
			case 'KEYS': // return all keys matching a pattern
			case 'RANDOMKEY': // return a random key from the currently selected database
			//--
			case 'SORT': // sort key by pattern ; SORT mylist DESC ; SORT mylist ALPHA ; for UTF-8 the !LC_COLLATE environment must be set
			case 'SCAN': // available since Redis 2.8 ; incrementally iterate over a collection of keys
			//--
			case 'HSET': // sets field in the hash stored at key to value ; if key does not exist, a new key holding a hash is created. If field already exists in the hash, it is overwritten
			case 'HDEL': // removes the specified fields from the hash stored at key
			case 'HEXISTS': // returns if field is an existing field in the hash stored at key
			case 'HGET': // returns the value associated with field in the hash stored at key
			case 'HGETALL': // returns all fields and values of the hash stored at key
			case 'HINCRBY': // increments the number stored at field in the hash stored at key by increment ; if key does not exist, a new key holding a hash is created
			case 'HINCRBYFLOAT': // increment the specified field of an hash stored at key, and representing a floating point number, by the specified increment
			case 'HKEYS': // returns all field names in the hash stored at key
			case 'HLEN': // returns the number of fields contained in the hash stored at key
			case 'HMGET': // returns the values associated with the specified fields in the hash stored at key
			case 'HMSET': // sets the specified fields to their respective values in the hash stored at key
			case 'HSCAN': // available since 2.8.0 ; iterates fields of Hash types and their associated values
			case 'HSETNX': // sets field in the hash stored at key to value, only if field does not yet exist
			case 'HSTRLEN': // returns the string length of the value associated with field in the hash stored at key
			case 'HVALS': // returns all values in the hash stored at key
			//--
			case 'LINSERT': // inserts value in the list stored at key either before or after the reference value pivot
			case 'LINDEX': // returns the element at index 0..n in the list stored at key
			case 'LLEN': // returns the length of the list stored at key ; if key does not exist, it is interpreted as an empty list and 0 is returned ; an error is returned when the value stored at key is not a list
			case 'LPOP': // removes and returns the first element of the list stored at key
			case 'RPOP': // removes and returns the last element of the list stored at key
			case 'LPUSH': // insert all the specified values at the begining of the list stored at key ; key value
			case 'LPUSHX': // inserts value at the head of the list stored at key, only if key already exists and holds a list. In contrary to LPUSH, no operation will be performed when key does not yet exist
			case 'RPUSH': // insert all the specified values at the end of the list stored at key ; key value
			case 'RPUSHX': // inserts value at the tail of the list stored at key, only if key already exists and holds a list. In contrary to RPUSH, no operation will be performed when key does not yet exist
			case 'RPOPLPUSH': // atomically returns and removes the last element (tail) of the list stored at source, and pushes the element at the first element (head) of the list stored at destination
			case 'LRANGE': // get a list key value(s) ; key start stop
			case 'LREM': // remove list key(s) ; key count value ; count > 0: Remove elements equal to value moving from head to tail ; count < 0: Remove elements equal to value moving from tail to head ; count = 0: Remove all elements equal to value
			case 'LSET': // set a list key value ; key index value
			case 'LTRIM': // trim an existing list so that it will contain only the specified range of elements specified ; key start stop
			//--
			case 'SADD': // add a key to a set
			case 'SREM': // remove a key from a set
			case 'SMOVE': // atomically move member from the set at source to the set at destination
			case 'SCARD': // returns the cardinality of a key
			case 'SDIFF': // returns the difference between the first set and all the successive sets
			case 'SDIFFSTORE': // identical with SDIFF but instead of return will store the result
			case 'SINTER': // returns the intersection of all the given sets
			case 'SINTERSTORE': // identical with SINTER but instead of return will store the result
			case 'SUNION': // returns the members of the set resulting from the union of all the given sets
			case 'SUNIONSTORE': // identical with SUNION but instead of return will store the result
			case 'SISMEMBER': // returns if member is a member of the set stored at key
			case 'SMEMBERS': // returns all the members of the set value stored at key
			case 'SPOP': // removes and returns one or more random elements from the set value store at key
			case 'SRANDMEMBER': // when called with just the key argument, return a random element from the set value stored at key ; since Redis 2.6 a second count parameter has been added
			case 'SSCAN': // available since Redis 2.8 ; incrementally iterate over a collection of elements in a set
			//--
			case 'ZADD': // adds all the specified members with the specified scores to the sorted set stored at key
			case 'ZREM': // removes the specified members from the sorted set stored at key ; non existing members are ignored
			case 'ZCARD': // returns the sorted set cardinality (number of elements) of the sorted set stored at key
			case 'ZCOUNT': // returns the number of elements in the sorted set at key with a score between min and max
			case 'ZINCRBY': // increments the score of member in the sorted set stored at key by increment
			case 'ZINTERSTORE': // computes the intersection of numkeys sorted sets given by the specified keys, and stores the result in destination
			case 'ZRANGE': // returns the specified range of elements in the sorted set stored at key
			case 'ZRANGEBYSCORE': // returns all the elements in the sorted set at key with a score between min and max (including elements with score equal to min or max)
			case 'ZRANK': // returns the rank of member in the sorted set stored at key, with the scores ordered from low to high
			case 'ZREMRANGEBYSCORE': // removes all elements in the sorted set stored at key with a score between min and max (inclusive)
			case 'ZREVRANGE': // returns the specified range of elements in the sorted set stored at key
			case 'ZREVRANGEBYSCORE': // returns all the elements in the sorted set at key with a score between max and min (including elements with score equal to max or min)
			case 'ZREVRANK': // returns the rank of member in the sorted set stored at key, with the scores ordered from high to low
			case 'ZSCORE': // returns the score of member in the sorted set at key
			case 'ZUNIONSTORE': // computes the union of numkeys sorted sets given by the specified keys, and stores the result in destination
			case 'ZSCAN': // available since 2.8.0 ; iterates elements of Sorted Set types and their associated scores
			//--
		//	case 'FLUSHALL': // remove all keys from all databases !!! this is unsafe because operates on many databases, don't allow in this context !!!
			case 'FLUSHDB': // remove all keys from the selected database
			case 'DBSIZE': // return the number of keys in the currently-selected database
			case 'SAVE': // synchronous save the DB to disk ; returns OK on success
			//--
			case 'INFO': // returns information and statistics about the server
			case 'TIME': // returns the current server time as a two items lists: a Unix timestamp and the amount of microseconds already elapsed in the current second
			//--
			case 'ECHO': // echo a message
			case 'PING': // ping the server ; returns PONG
			case 'QUIT': // always return OK ; ask the server to close the connection ; the connection is closed as soon as all pending replies have been written to the client
				//--
				if(!is_resource($this->connect())) { // this have it's own error raise mechanism
					if(SmartEnvironment::ifDebug()) {
						Smart::log_notice('#REDIS-DB# :: Redis connection FAILED just before calling the method: '.$method.'()');
					} //end if
					return null;
				} //end if
				//--
				return $this->run_command((string)$method, (array)$args);
				//--
				break;
			case 'AUTH': // password ; returns OK
			case 'SELECT': // select the DB by the given index (default is 0 .. 15)
				//--
				$this->error('Redis Dissalowed Command', 'Method is Forbidden', 'Method: '.$method); // fatal error
				return null;
				//--
			default:
				//--
				$this->error('Redis Unavailable Command', 'Method is Unavailable', 'Method: '.$method); // fatal error
				return null;
				//--
		} //end switch
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * this is the Run Command
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	private function run_command(string $method, array $args) {
		//--
		if(!$this->socket) {
			$this->error('Redis Connection / Run', 'Connection Failed', 'Method: '.$method); // fatal error
			return null;
		} //end if
		//--
		$method = (string) $method;
		$args = (array) $args;
		//--
		array_unshift($args, $method);
		$cmd = '*'.count($args)."\r\n"; // no. of arguments
		foreach($args as $z => $item) {
			$cmd .= '$'.strlen($item)."\r\n"; // str length
			$cmd .= $item."\r\n"; // key contents
		} //end foreach
		//--
		if((string)$cmd == '') {
			//--
			$this->error('Redis Run Command', 'Empty commands are not allowed ...', ''); // fatal error
			return null;
			//--
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--
		@fwrite($this->socket, $cmd);
		//--
		$response = $this->parse_response($method);
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'redis|total-queries', 1, '+');
			//--
			$time_end = (float) (microtime(true) - (float)$time_start);
			SmartEnvironment::setDebugMsg('db', 'redis|total-time', $time_end, '+');
			//--
			$dtype = 'nosql';
			if(in_array(
				(string) strtoupper((string)$method),
				[
					'TYPE',
					'STRLEN',
					'TTL',
					'GET',
					'KEYS',
					'RANDOMKEY',
				]
			)) {
				$dtype = 'read';
			} elseif(in_array(
				(string) strtoupper((string)$method),
				[
					'EXISTS',
				]
			)) {
				$dtype = 'count';
			} elseif(in_array(
				(string) strtoupper((string)$method),
				[
					'SET',
					'APPEND',
					'DEL',
					'EXPIRE',
					'EXPIREAT',
					'PERSIST',
					'INCR',
					'INCRBY',
					'DECR',
					'DECRBY',
					'RENAME',
				]
			)) {
				$dtype = 'write';
			} elseif(in_array(
				(string) strtoupper((string)$method),
				[
					'MULTI',
					'EXEC',
					'DISCARD',
				]
			)) {
				$dtype = 'transaction';
			} elseif(in_array(
				(string) strtoupper((string)$method),
				[
					'PING',
					'AUTH',
					'SELECT',
					'WATCH',
					'UNWATCH',
				]
			)) {
				$dtype = 'set';
			} elseif(in_array(
				(string) strtoupper((string)$method),
				[
					'FLUSHDB',
					'SAVE',
					'QUIT',
				]
			)) {
				$dtype = 'special';
			} //end if else
			//--
			$datasize = null;
			$isdatalensize = false;
			if(in_array(
				(string) strtoupper((string)$method),
				[
					'SET',
					'APPEND',
					'HSET',
					'HMSET',
					'HSETNX',
					'LINSERT',
					'LSET',
					'SADD',
					'ZADD',
					'ECHO',
				]
			)) {
				$datasize = (int) strlen((string)implode(' ', (array)$args));
				$isdatalensize = true;
			} //end if
			//--
			SmartEnvironment::setDebugMsg('db', 'redis|log', [
				'type' 			=> (string) $dtype,
				'data' 			=> (string) strtoupper($method).' :: '.$this->description,
				'command' 		=> (string) Smart::text_cut_by_limit((string)implode(' ', (array)$args), 1024, true, '[...data-longer-than-1024-bytes-is-not-logged-all-here...]'),
				'time' 			=> (string) Smart::format_number_dec($time_end, 9, '.', ''),
				'rows' 			=> (int)   (($datasize === null) ? (is_array($response) ? count($response) : (int)((bool)strlen((string)$response))) : $datasize),
				'wsize' 		=> (bool) 	$isdatalensize,
				'connection' 	=> (string) $this->socket
			]);
			//--
		} //end if
		//--
		return $response;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function parse_response($method) {
		//--
		$result = null;
		//--
		if(!$this->socket) {
			$this->error('Redis Connection / Response', 'Connection Failed (1)', 'Method: '.$method); // fatal error
			return null;
		} //end if
		//--
		$line = (string) @fgets($this->socket, $this->recvbuf);
		if((string)$line == '') {
			$this->error('Redis Response', 'Empty Response', 'Method: '.$method."\n".'ERR-MSG: `UNKNOWN ERROR`'); // fatal error
			return null;
		} //end if
		//--
		$type = (string) substr((string)$line, 0, 1);
		$result = (string) substr((string)$line, 1, ((int)strlen((string)$line) - 3));
		//--
		if((string)$type == '-') { // error message
			//--
			$this->error('Redis Response', 'Invalid Response', 'Method: '.$method."\n".'ERR-MSG: `'.$line.'`'); // fatal error
			return null;
			//--
		} elseif((string)$type == '$') { // bulk reply
			//--
			if((string)$result == '-1') {
				//--
				$result = null;
				//--
			} else {
				//--
				if(!$this->socket) {
					$this->error('Redis Connection / Response', 'Connection Failed (2)', 'Method: '.$method); // fatal error
					return null;
				} //end if
				//--
				/* Old Buggy Method
				$line = @fread($this->socket, ($result + 2));
				$result = substr($line, 0, (strlen($line) - 2));
				*/
				//### Fix from: Predis\Connection\StreamConnection->read() # case: case '$':
				$size = (int) $result;
				$bytes_left = ($size += 2);
				$result = '';
				do {
					$chunk = @fread($this->socket, min((int)$bytes_left, $this->recvbuf)); // 4096 was instead of $this->recvbuf
					if($chunk === false || $chunk === '') {
						$this->error('Redis Response', 'Error while reading bulk reply from the server', 'Method: '.$method); // fatal error
						return null;
					} //end if
					$result .= (string) $chunk;
					$bytes_left = (int) $size - strlen($result);
				} while($bytes_left > 0);
				$result = (string) substr((string)$result, 0, -2);
				//###
				//--
			} //end if else
			//--
		} elseif((string)$type == '*') { // multi-bulk reply
			//--
			$count = (int) $result;
			//--
			for($i=0, $result=array(); $i<$count; $i++) {
				//--
				$result[] = $this->parse_response($method);
				//--
			} //end for
			//--
		} //end if else
		//--
		return $result;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private function connect() {
		//--
		if(!is_resource($this->socket)) { // try to connect or re-use the connection
			//--
			if(array_key_exists('redis', SmartEnvironment::$Connections) AND array_key_exists((string)$this->server.'@'.$this->db, (array)SmartEnvironment::$Connections['redis']) AND is_resource(SmartEnvironment::$Connections['redis'][(string)$this->server.'@'.$this->db])) {
				//--
				$this->socket = SmartEnvironment::$Connections['redis'][(string)$this->server.'@'.$this->db]; // re-use conection (import)
				//--
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('db', 'redis|log', [
						'type' => 'open-close',
						'data' => 'Redis DB :: Re-Using Connection to: '.$this->server.'@'.$this->db.' :: '.$this->description.' @ Connection-Socket: '.$this->socket
					]);
				} //end if
				//--
				$errno = 0;
				$errstr = 'Trying to reuse the connection: '.$this->socket;
				//--
			} else {
				//--
				// DO NOT DISABLE/RESTORE ERROR LOGS HERE, THIS IS A DB AND ALL ERRORS SHOULD BE LOGGED
				$this->socket = @stream_socket_client($this->server, $errno, $errstr, $this->timeout);
				//--
				if(SmartEnvironment::ifDebug()) {
					//--
					SmartEnvironment::setDebugMsg('db', 'redis|log', [
						'type' => 'metainfo',
						'data' => 'Connection Timeout: '.$this->timeout.' seconds'
					]);
					//--
					SmartEnvironment::setDebugMsg('db', 'redis|log', [
						'type' => 'metainfo',
						'data' => 'Fast Query Reference Time < '.$this->slow_time.' seconds'
					]);
					//--
					SmartEnvironment::setDebugMsg('db', 'redis|log', [
						'type' => 'open-close',
						'data' => 'Redis DB :: Open Connection to: '.$this->server.'@'.$this->db.' :: '.$this->description.' @ Connection-Socket: '.$this->socket
					]);
					//--
				} //end if
				//--
				if(is_resource($this->socket)) {
					//--
					@stream_set_blocking($this->socket, true);
					@stream_set_timeout($this->socket, (int)SMART_FRAMEWORK_NETSOCKET_TIMEOUT);
					//--
					SmartEnvironment::$Connections['redis'][(string)$this->server.'@'.$this->db] = $this->socket; // export connection
					//--
					if((string)$this->password != '') {
						$this->run_command('AUTH', array($this->password)); // authenticate
					} //end if
					//--
					$this->run_command('SELECT', array($this->db)); // select database
					//--
					if(SmartEnvironment::ifDebug()) {
						SmartEnvironment::setDebugMsg('db', 'redis|log', [
							'type' => 'set',
							'data' => 'Selected Redis Database #'.$this->db.' :: `'.$this->description.'`',
							'skip-count' => 'yes'
						]);
					} //end if
					//--
				} //end if else
				//--
			} //end if else
			//--
			if(!is_resource($this->socket)) {
				$this->error('Redis Connect', 'ERROR: #'.$errno.' :: '.$errstr, 'Connection to Redis server: '.$this->server.'@'.$this->db); // non-fatal error, depends on how Redis class is setup
				return null;
			} //end if
			//--
		} //end if
		//--
		return $this->socket;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * this is for disconnect from Redis
	 * normally should not be used
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public function disconnect() {
		//--
		if($this->socket) {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'redis|log', [
					'type' => 'open-close',
					'data' => 'Redis DB :: Close Connection for: '.$this->server.'@'.$this->db.' :: '.$this->description.' @ Connection-Socket: '.$this->socket
				]);
			} //end if
			//--
			@fclose($this->socket); // closing the local connection (the global might remain opened ...)
			//--
			SmartEnvironment::$Connections['redis'][(string)$this->server.'@'.$this->db] = null;
			//--
			$this->socket = null; // reset and clear socket
			$this->err = true; // required, to halt driver, no more allow operations and avoid reconnect, was explicit destroyed
			//--
		} //end if
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Displays the Redis Errors and HALT EXECUTION (This have to be a FATAL ERROR as it occur when a FATAL Redis ERROR happens or when Data Exchange fails)
	 * If Non-Fatal Errors are set per instance will just log them
	 * PRIVATE
	 *
	 * @param STRING $y_area :: The Area
	 * @param STRING $y_error_message :: The Error Message to Display
	 * @param STRING $y_query :: The query
	 * @param STRING $y_warning :: The Warning Title
	 *
	 * @return :: HALT EXECUTION WITH ERROR MESSAGE
	 *
	 */
	private function error($y_area, $y_error_message, $y_query='', $y_warning='') {
		//--
		$this->err = true; // required, to halt driver
		//--
		$is_fatal = (bool) $this->fatal_err;
		//--
		if($is_fatal === false) { // NON-FATAL ERROR
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'redis|log', [
					'type' => 'metainfo',
					'data' => 'Redis (`'.$this->description.'`) :: SILENT WARNING: '.$y_area."\n".'Command: '.$y_query."\n".'Error-Message: '.$y_error_message."\n".'The settings for this Redis instance allow just silent warnings on connection fail.'."\n".'All next method calls to this Redis instance will be discarded silently ...'
				]);
			} //end if
			Smart::log_warning('#REDIS@'.$this->socket.'# (`'.$this->description.'`) :: Q# // Redis Client :: NON-FATAL ERROR :: '.$y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Command:'."\n".$y_query);
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
			$the_query_info = (string) $y_query;
			if((string)$the_query_info == '') {
				$the_query_info = '-'; // query cannot e empty in this case (templating enforcement)
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
				'Redis Client',
				'Redis',
				'MemDB',
				'Server',
				'lib/core/img/db/redis-logo.svg',
				(int)    $width, // width
				(string) $the_area, // area
				(string) $the_error_message, // err msg
				(string) $the_params, // title or params
				(string) $the_query_info // command
			);
		} //end if
		//--
		Smart::raise_error(
			'#REDIS@'.$this->socket.'# (`'.$this->description.'`) :: Q# // Redis Client :: ERROR :: '.$y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Command:'."\n".$y_query,
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

// end of php code
