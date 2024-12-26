<?php
// [LIB - Smart.Framework / Plugins / MySQLi (MariaDB) Database Client]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// NOTES ABOUT REUSING CONNECTIONS:
//		* BY DEFAULT the PHP MySQLi driver reuses connections if the same host:port@dbname#username are used
//		* this is not enough since Smart.Framework uses also the concept of settings like UTF8 and transaction mode
//		* thus the Smart.Framework implements a separate mechanism to control the connections re-use, to avoid break transactions while mixing (re)connections

//======================================================
// Smart-Framework - MySQLi Database Client for MariaDB Server / MySQL
// DEPENDS:
//	* Smart::
//	* SmartUnicode::
//	* SmartComponents:: (optional)
// DEPENDS-EXT: PHP MySQLi Extension
//======================================================
// NOTICE: For MySQLi driver all queries are using MYSQLI_STORE_RESULT (buffered queries) which is the best for data safety
// NOTICE: YOU SHOULD NEVER use UNBUFFERED QUERIES (MYSQLI_USE_RESULT) because for unbuffered result sets there is no 100% guarantee all happens as planned !!!
//======================================================
// NOTICE OF POSSIBLE ERRORS WHEN USING THE CLASS FUNCTIONS IN A WRONG WAY:
// 	mysqli_num_rows() will not return the correct number of rows until all the rows in the result have been retrieved
// The below errors will appear when using ::read_*data() instead of ::write_data() in a wrong way:
//		* mysqli_num_rows() expects parameter 1 to be mysqli_result, boolean given
//		* mysqli_num_fields() expects parameter 1 to be mysqli_result, boolean given
// COMMENT: when using ::write_data() it will expect having affected_rows() which is not available on Read or viceversa
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartMysqliDb - provides a Static Client for MariaDB Server / MySQL that can be used just with the DEFAULT connection from configs.
 * This class can be used just with the DEFAULT connection which must be set in etc/config.php: $configs['mysqli'].
 * It connects automatically, when needed (the connection is lazy, and is made just when is needed to avoid permanent connections to MySQL which slower down the app and takes busy the slots).
 *
 * Minimum supported version of MySQL/MariaDB is: 5.0.0
 * Tested and Stable with MariaDB versions: 5.5.x / 10.x / 11.x
 * Tested and Stable on MySQL versions: 5.x / 6.x / 7.x / 8.x / 9.x
 *
 * <code>
 *
 * // The connection to the DEFAULT MariaDB Server / MySQL will be done automatically, when needed, using the config parameters
 * $count = (int) SmartMysqliDb::count_data('SELECT COUNT(`id`) FROM `table` WHERE (`active` = \''.SmartMysqliDb::escape_str('1').'\')');
 * $non_associative_read_multi_records = (array) SmartMysqliDb::read_data('SELECT * FROM `table` WHERE `id` = ?', array(3));
 * $associative_read_multi_records = (array) SmartMysqliDb::read_adata('SELECT * FROM `table` WHERE `id` = ?', array('some-id'));
 * $associative_read_for_just_one_record = (array) SmartMysqliDb::read_asdata('SELECT * FROM `table` WHERE `id` = ? LIMIT 1 OFFSET 0', array(99)); // NOTICE: this function will return just one record, so always use LIMIT 1 OFFSET 0 (or LIMIT 0,1) ; if the query will return more records will raise an error
 * $update = (array) SmartMysqliDb::write_data('UPDATE `table` SET `active` = 1 WHERE `id` = ?', array(55)); // will return an array[ 0 => message, 1 => (integer) affected rows ]
 * $arr_insert = array(
 * 		'id' => 100,
 * 		'active' => 1,
 * 		'name' => 'Test Record'
 * );
 * $insert = (array) SmartMysqliDb::write_data('INSERT INTO `table` '.SmartMysqliDb::prepare_statement($arr_insert, 'insert'));
 * $prepared_sql = $db->prepare_param_query('SELECT * FROM `table` WHERE `id` = ?', [99]);
 *
 * </code>
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	extensions: PHP MySQLi ; classes: Smart, SmartEnvironment, SmartUnicode, SmartComponents (optional) ; constants: SMART_FRAMEWORK_SQL_CHARSET
 * @version 	v.20241220
 * @package 	Plugins:Database:MySQL
 *
 */
final class SmartMysqliDb {

	// ::

	private static $slow_time = 0.0050;
	private static $server_version = [];

	private static $default_connection = null;

	private const minVersionServer = '5.0.0'; // MariaDB / MySQL minimum server version required [5.0.0] or later (DO NOT RUN THIS SOFTWARE ON OLDER MySQL Versions !!!


	//======================================================
	/**
	 * Pre-connects manually to the Default Server.
	 * This function is OPTIONAL as the connection on the DEFAULT Server will be done automatically when needed.
	 * Anyway, if there is a need to create an explicit connection to the DEFAULT Server earlier, this function can be used by example in App Bootstrap.
	 *
	 */
	public static function default_connect() {
		//--
		return self::check_connection('DEFAULT', 'DEFAULT-CONNECT');
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Create a Server Custom Connection.
	 * This MUST NOT be used with the default connection ... as that is handled automatically.
	 *
	 * @param STRING $yhost 						:: db host
	 * @param STRING $yport 						:: db port
	 * @param STRING $ydb 							:: db name
	 * @param STRING $yuser							:: db user
	 * @param STRING $ypass							:: db pass
	 * @param INTEGER $ytimeout 					:: connection timeout
	 * @param ENUM $y_transact_mode					:: transactional mode ('READ COMMITTED' | 'REPEATABLE READ' | 'SERIALIZABLE' | '' to leave it as default)
	 * @param FLOAT $y_debug_sql_slowtime			:: debug query slow time
	 * @param ENUM $y_type							:: server type: mariadb (UTF8.MB4) / mysql (UTF8)
	 *
	 * @return RESOURCE								:: the mysql connection object
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function server_connect($yhost, $yport, $ydb, $yuser, $ypass, $ytimeout, $y_transact_mode='', $y_debug_sql_slowtime=0, $y_type='mariadb') {

		//--
		if(defined('SMART_FRAMEWORK_SQL_CHARSET')) {
			if((string)SMART_FRAMEWORK_SQL_CHARSET != 'UTF8') {
				self::error('[PRE-CONNECT]', 'PHP-MySQLi', 'Check App Configuration', 'The SMART_FRAMEWORK_SQL_CHARSET must be set as: UTF8', 'Invalid INI Settings');
				return;
			} //end if
		} else {
			self::error('[PRE-CONNECT]', 'PHP-MySQLi', 'Check App Configuration', 'The SMART_FRAMEWORK_SQL_CHARSET must be set', 'Invalid INI Settings');
			return;
		} //end if else
		//--

		//--
		if(!function_exists('mysqli_init')) {
			self::error('[PRE-CONNECT]', 'PHP-MySQLi', 'Check MySQLi PHP Extension', 'PHP Extension is required to run this software !', 'Cannot find MySQLi PHP Extension');
			return;
		} //end if
		//--

		//--
		$connUnicode   = 'UTF8.MB4';
		$connCharset   = 'utf8mb4';
		$connCollation = 'utf8mb4_bin';
		$y_type = (string) strtolower((string)trim((string)$y_type));
		switch((string)$y_type) {
			case 'mysql': // support for older servers
				$connUnicode   = 'UTF8';
				$connCharset   = 'utf8';
				$connCollation = 'utf8_bin';
				break;
			case 'mariadb': // support for modern servers
			default:
				$y_type = 'mariadb';
		} //end switch
		//--

		//-- connection timeout
		$timeout = (int) $ytimeout;
		//--
		if($timeout < 1) {
			$timeout = 1;
		} //end if
		if($timeout > 60) {
			$timeout = 60;
		} //end if
		//--

		//-- debug settings
		if(SmartEnvironment::ifDebug()) {
			//--
			$y_debug_sql_slowtime = (float) $y_debug_sql_slowtime;
			if($y_debug_sql_slowtime <= 0) {
				$y_debug_sql_slowtime = (float) self::$slow_time;
			} //end if
			//--
			if($y_debug_sql_slowtime < 0.0000001) {
				$y_debug_sql_slowtime = 0.0000001;
			} elseif($y_debug_sql_slowtime > 0.9999999) {
				$y_debug_sql_slowtime = 0.9999999;
			} //end if
			//--
			self::$slow_time = (float) $y_debug_sql_slowtime; // update
			//--
		} //end if
		//--

		//-- debug inits
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'mysqli|slow-time', number_format(self::$slow_time, 7, '.', ''), '=');
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'metainfo',
				'data' => 'Database Server: MySQLi ('.$y_type.') / App Connector Version: '.SMART_FRAMEWORK_VERSION.' / Connection Charset: '.SMART_FRAMEWORK_SQL_CHARSET
			]);
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'metainfo',
				'data' => 'Connection Timeout: '.$timeout.' seconds / Fast Query Reference Time < '.self::$slow_time.' seconds'
			]);
		} //end if
		//--

		//--
		if((string)$ypass != '') {
			$password = (string) base64_decode((string)$ypass);
		} else {
			$password = '';
		} //end if else
		//--

		//--
		if(((string)$yhost == '') OR ((int)$yport <= 0) OR ((string)$ydb == '') OR ((string)$yuser == '')) { // {{{SYNC-MYSQLI-CFG-PARAMS-CHECK}}}
			self::error('[PRE-CONNECT]', 'PHP-MySQLi', 'The Default MySQL Configs are not complete !', 'Some of the configs[mysqli] parameters are missing !', 'Can not connect to Server');
			return;
		} //end if
		//--

		//-- {{{SYNC-CONNECTIONS-IDS}}}
		$the_conn_key = (string)$yhost.':'.$yport.'@'.$ydb.'#'.$yuser;
		//--
		$driver = new mysqli_driver();
		$driver->report_mode = MYSQLI_REPORT_OFF; // PHP 8.1+ ; the MYSQLI_REPORT_ERROR is not required as all reporting is done by the server here ...
		//--
		$connection = @mysqli_init();
		@mysqli_options($connection, MYSQLI_OPT_LOCAL_INFILE, false);
		if(!@mysqli_real_connect($connection, (string)$yhost, (string)$yuser, (string)$password, false, (int)$yport)) {
			// @mysqli_close($y_connection) if object ; but reusing connections policy dissalow disconnects
			self::error($yhost.':'.$yport.'@'.$ydb.'#'.$yuser, 'Connect', 'Connect to Server (1)', 'CONNECTION FAILED !!!', 'Connection Failed to Server !');
			return;
		} //end if
		//--
		if(!is_object($connection)) {
			self::error($yhost.':'.$yport.'@'.$ydb.'#'.$yuser, 'Connection', 'Connect to Server (2)', 'NO CONNECTION !!!', 'Connection Failed to Server !');
			return;
		} //end if
		if((string)$connection->thread_id == '') {
			self::error($yhost.':'.$yport.'@'.$ydb.'#'.$yuser, 'Connection', 'Connect to Server (3)', 'INVALID CONNECTION !!!', 'Connection Failed to Server !');
			return;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'open-close',
				'data' => 'Connected to Server: '.$the_conn_key.' ; Error Reporting: MYSQLI_REPORT_OFF',
				'connection' => (string) self::get_connection_id($connection)
			]);
		} //end if
		//--

		//--
		if((string)SMART_FRAMEWORK_SQL_CHARSET == 'UTF8') {
			//--
			@mysqli_query($connection, "SET CHARACTER SET '{$connCharset}'", MYSQLI_STORE_RESULT);
			if(@mysqli_errno($connection) !== 0) {
				self::error(self::get_connection_id($connection), 'Encoding-Charset', 'Failed to set Encoding on Server', 'Error='.@mysqli_error($connection), 'Set='.$connCharset);
				return;
			} //end if else
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'set',
					'data' => 'SET Character Set to: '.$connCharset,
					'connection' => (string) self::get_connection_id($connection),
					'skip-count' => 'yes'
				]);
			} //end if
			//--
			@mysqli_query($connection, "SET COLLATION_CONNECTION = '{$connCollation}'", MYSQLI_STORE_RESULT);
			if(@mysqli_errno($connection) !== 0) {
				self::error(self::get_connection_id($connection), 'Encoding-Collation', 'Failed to set Collation on Server', 'Error='.@mysqli_error($connection), 'Set='.$connCollation);
				return;
			} //end if else
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'set',
					'data' => 'SET Connection Collation to: '.$connCollation,
					'connection' => (string) self::get_connection_id($connection),
					'skip-count' => 'yes'
				]);
			} //end if
			//--
		} else {
			//--
			self::error(self::get_connection_id($connection), 'Encoding-Charset', 'Wrong Client Encoding for Server', 'Server='.$connUnicode, 'Client='.SMART_FRAMEWORK_SQL_CHARSET);
			return;
			//--
		} //end if
		//--

		//-- under MySQL there is no true Serializable, but anyway will push the server to do it's bests ...
		$transact = strtoupper((string)$y_transact_mode);
		switch((string)$transact) {
			case 'REPEATABLE READ':
			case 'READ COMMITTED':
				@mysqli_query($connection, "SET SESSION TRANSACTION ISOLATION LEVEL {$transact}", MYSQLI_STORE_RESULT);
				if(@mysqli_errno($connection) !== 0) {
					self::error(self::get_connection_id($connection), 'Set-Session-Transaction-Level', 'Failed to Set Session Transaction Level as '.$transact, 'Error='.@mysqli_error($connection), 'DB='.$ydb);
					return;
				} //end if else
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
						'type' => 'set',
						'data' => 'SET Session Transaction Isolation Level to: '.$transact,
						'connection' => (string) self::get_connection_id($connection),
						'skip-count' => 'yes'
					]);
				} //end if
				break;
			default:
				// LEAVE THE SESSION TRANSACTION AS SET IN CFG
		} //end switch
		//--

		//--
		if(!@mysqli_select_db($connection, $ydb)) {
			self::error(self::get_connection_id($connection), 'Select-DB', 'Failed to select the Database', 'ERROR !!!', 'Database Selection ERROR ...');
			return;
		} //end if
		if(@mysqli_errno($connection) !== 0) {
			self::error(self::get_connection_id($connection), 'Select Database', 'Failed to Select the Database', 'Error='.@mysqli_error($connection), 'DB='.$ydb);
			return;
		} //end if else
		//--

		//-- export only at the end (after all settings)
		SmartEnvironment::$Connections['mysqli'][(string)$the_conn_key] = &$connection; // export connection
		//--

		//-- OUTPUT
		return $connection;
		//-- OUTPUT

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Fix a string to be compliant with MySQL LIKE syntax.
	 * It will use special quotes for the LIKE special characters: % _
	 * This function IS NOT INTENDED TO ESCAPE AGAINST SQL INJECTIONS ; USE IT ONLY WITH PREPARED PARAMS OR USE escape_str() with mode 'likes'
	 *
	 * @param STRING $y_string						:: A String or a Number to be Quoted for LIKES
	 */
	public static function quote_likes($y_string) {
		//--
		return (string) str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], (string)$y_string); // escape for LIKE / SIMILAR: extra special escape: \ = \\ ; _ = \_ ; % = \%
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Escape a string to be compliant and Safe (against SQL Injection) with MySQL standards.
	 * This function WILL NOT ADD the SINGLE QUOTES (') arround the string, but just will just escape it to be safe.
	 *
	 * @param STRING $y_string						:: A String or a Number to be Escaped
	 * @param ENUM $y_mode							:: '' = default ; 'likes' = Escape LIKE Syntax (% _)
	 * @param RESOURCE $y_connection 				:: the connection
	 * @return STRING 								:: The Escaped String / Number
	 *
	 */
	public static function escape_str($y_string, $y_mode='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'ESCAPE-STR');
		//==

		//-- Fix
		$y_string = (string) SmartUnicode::fix_charset((string)$y_string);
		$y_mode = (string) trim((string)strtolower((string)$y_mode));
		//--

		//--
		if((string)$y_mode == 'likes') { // escape for LIKE / ILIKE / SIMILAR: extra special escape: \ = \\ ; _ = \_ ; % = \%
			$y_string = (string) self::quote_likes((string)$y_string);
		} //end if
		//--
		$y_string = (string) @mysqli_real_escape_string($y_connection, (string)$y_string);
		//--

		//--
		return $y_string;
		//--

	} // END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Check if a Table Exists in the current Database.
	 *
	 * @param STRING $y_table 						:: The Table Name
	 * @param RESOURCE $y_connection				:: The connection to Server
	 * @return 0/1									:: 1 if exists ; 0 if not
	 *
	 */
	public static function check_if_table_exists($y_table, $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'TABLE-CHECK-IF-EXISTS');
		//==

		//--
		$y_table = (string) str_replace('"', '', (string)$y_table);
		//--

		//--
		$arr_data = self::read_data("SHOW TABLES LIKE '".self::escape_str($y_table, '', $y_connection)."'", 'Check if Table Exists', $y_connection);
		//--
		if(array_key_exists(0, $arr_data) AND ((string)$arr_data[0] == (string)$y_table)) {
			$out = 1;
		} else {
			$out = 0;
		} //end if else
		//--

		//--
		return $out;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * MySQL Query :: Count
	 * This function is intended to be used for count type queries: SELECT COUNT().
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @param RESOURCE $y_connection				:: the connection
	 * @return INTEGER								:: the result of COUNT()
	 */
	public static function count_data($queryval, $params_or_title='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'COUNT-DATA');
		//==

		//-- samples
		// $queryval = $queryval = "SELECT COUNT(*) FROM `tablename` WHERE (`field` = 'x')";
		//--

		//--
		$time_start = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		$use_param_query = false;
		if(is_array($params_or_title)) {
			if(Smart::array_size($params_or_title) > 0) {
				$use_param_query = true;
			} else {
				$params_or_title = '';
			} //end if else
		} //end if
		//--
		if($use_param_query === true) {
			$the_query_title = '';
			$queryval = self::prepare_param_query($queryval, (array)$params_or_title, $y_connection);
		} else {
			$the_query_title = (string) $params_or_title;
		} //end if else
		//--
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		if($chk !== 0) {
			$error = 'Query FAILED: '.$err;
		} //end if else
		if(!is_object($result)) {
			//$error = 'Query is INVALID (Returns No Data) for this context: '.__FUNCTION__.'()';
			return 0;
		} //end if else
		//--

		//--
		$time_end = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_end = (float) (microtime(true) - (float)$time_start);
		} //end if
		//--

		//--
		$mysql_result_count = 0; // store COUNT data
		if((string)$error == '') {
			$record = @mysqli_fetch_row($result);
			$mysql_result_count = Smart::format_number_int((int)($record[0] ?? null));
		} //end if
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-queries', 1, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-time', $time_end, '+');
			//--
			if(is_array($params_or_title)) {
				$dbg_query_params = (array) $params_or_title;
			} else {
				$dbg_query_params = '';
			} //end if else
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'count',
				'data' => 'COUNT :: '.$the_query_title,
				'query' => $queryval,
				'params' => $dbg_query_params,
				'rows' => $mysql_result_count,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'connection' => (string) self::get_connection_id($y_connection)
			]);
			//--
		} //end if
		//--

		//-- init vars
		if((string)$error != '') {
			//--
			self::error(self::get_connection_id($y_connection), 'COUNT-DATA', $error, $queryval, $params_or_title);
			return 0;
			//--
		} //end else
		//--

		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		return Smart::format_number_int($mysql_result_count, '+'); // be sure is 0 or greater
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * MySQL Query :: Read (Non-Associative) one or multiple rows.
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @param RESOURCE $y_connection				:: the connection
	 * @return ARRAY (non-asociative) of results	:: array('column-0-0', 'column-0-1', null, ..., 'column-0-n', 'column-1-0', 'column-1-1', ... 'column-1-n', ..., 'column-m-0', 'column-m-1', ..., 'column-m-n')
	 */
	public static function read_data($queryval, $params_or_title='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'READ-DATA');
		//==

		//-- samples
		// $queryval = "SELECT * FROM `tablename` WHERE (`field` = 'x') ORDER BY `field` ASC LIMIT $limit OFFSET $offset"; // [LIMIT-OFFSET]
		//--

		//--
		$time_start = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		$use_param_query = false;
		if(is_array($params_or_title)) {
			if(Smart::array_size($params_or_title) > 0) {
				$use_param_query = true;
			} else {
				$params_or_title = '';
			} //end if else
		} //end if
		//--
		if($use_param_query === true) {
			$the_query_title = '';
			$queryval = self::prepare_param_query($queryval, (array)$params_or_title, $y_connection);
		} else {
			$the_query_title = (string) $params_or_title;
		} //end if else
		//--
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		if($chk !== 0) {
			$error = 'Query FAILED:'."\n".$err;
		} //end if else
		if(!is_object($result)) {
			//$error = 'Query is INVALID (Returns No Data) for this context: '.__FUNCTION__.'()';
			return array();
		} //end if else
		//--

		//--
		$time_end = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_end = (float) (microtime(true) - (float)$time_start);
		} //end if
		//--

		//--
		$number_of_rows = 0;
		$number_of_fields = 0;
		if((string)$error == '') {
			$number_of_rows = @mysqli_num_rows($result);
			$number_of_fields = @mysqli_num_fields($result);
		} //end if
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-queries', 1, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-time', $time_end, '+');
			//--
			if(is_array($params_or_title)) {
				$dbg_query_params = (array) $params_or_title;
			} else {
				$dbg_query_params = '';
			} //end if else
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'read',
				'data' => 'READ [NON-ASSOCIATIVE] :: '.$the_query_title,
				'query' => $queryval,
				'params' => $dbg_query_params,
				'rows' => $number_of_rows,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'connection' => (string) self::get_connection_id($y_connection)
			]);
			//--
		} //end if
		//--

		//-- init vars
		$mysql_result_arr = array(); // store SELECT data
		//--
		if((string)$error != '') {
			//--
			self::error(self::get_connection_id($y_connection), 'READ-DATA', $error, $queryval, $params_or_title);
			return array();
			//--
		} else {
			//--
			for($i=0; $i<$number_of_rows; $i++) {
				//--
				$record = @mysqli_fetch_row($result);
				//--
				for($ii=0; $ii<$number_of_fields; $ii++) {
					if($record[$ii] === null) {
						$mysql_result_arr[] = null; // preserve null
					} else {
						$mysql_result_arr[] = (string) $record[$ii]; // force string
					} //end if else
				} // end for
				//--
			} //end for
			//--
		} //end else
		//--

		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		return (array) $mysql_result_arr;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * MySQL Query :: Read (Associative) one or multiple rows.
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @param RESOURCE $y_connection				:: the connection
	 * @return ARRAY (asociative) of results		:: array(0 => array('column1' => 'val1', 'column2' => null, ... 'column-n' => 't'), 1 => array('column1' => 'val2', 'column2' => 'val2', ... 'column-n' => 'f'), ..., m => array('column1' => 'valM', 'column2' => 'xyz', ... 'column-n' => 't'))
	 */
	public static function read_adata($queryval, $params_or_title='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'READ-aDATA');
		//==

		//-- samples
		// $queryval = "SELECT * FROM `tablename` WHERE (`field` = 'x') ORDER BY `field` ASC LIMIT $limit OFFSET $offset"; // [LIMIT-OFFSET]
		//--

		//--
		$time_start = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		$use_param_query = false;
		if(is_array($params_or_title)) {
			if(Smart::array_size($params_or_title) > 0) {
				$use_param_query = true;
			} else {
				$params_or_title = '';
			} //end if else
		} //end if
		//--
		if($use_param_query === true) {
			$the_query_title = '';
			$queryval = self::prepare_param_query($queryval, (array)$params_or_title, $y_connection);
		} else {
			$the_query_title = (string) $params_or_title;
		} //end if else
		//--
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		if($chk !== 0) {
			$error = 'Query FAILED:'."\n".$err;
		} //end if else
		if(!is_object($result)) {
			//$error = 'Query is INVALID (Returns No Data) for this context: '.__FUNCTION__.'()';
			return array();
		} //end if else
		//--

		//--
		$time_end = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_end = (float) (microtime(true) - (float)$time_start);
		} //end if
		//--

		//--
		$number_of_rows = 0;
		$number_of_fields = 0;
		if((string)$error == '') {
			$number_of_rows = @mysqli_num_rows($result);
			$number_of_fields = @mysqli_num_fields($result);
		} //end if
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-queries', 1, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-time', $time_end, '+');
			//--
			if(is_array($params_or_title)) {
				$dbg_query_params = (array) $params_or_title;
			} else {
				$dbg_query_params = '';
			} //end if else
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'read',
				'data' => 'aREAD [ASSOCIATIVE] :: '.$the_query_title,
				'query' => $queryval,
				'params' => $dbg_query_params,
				'rows' => $number_of_rows,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'connection' => (string) self::get_connection_id($y_connection)
			]);
			//--
		} //end if
		//--

		//-- init vars
		$mysql_result_arr = array(); // store SELECT data
		//--
		if((string)$error != '') {
			//--
			self::error(self::get_connection_id($y_connection), 'READ-aDATA', $error, $queryval, $params_or_title);
			return array();
			//--
		} else {
			//--
			if($number_of_rows > 0) {
				//--
				for($i=0; $i<$number_of_rows; $i++) {
					//--
					$record = @mysqli_fetch_assoc($result);
					//--
					if(is_array($record)) {
						//--
						$tmp_datarow = array();
						//--
						foreach($record as $key => $val) {
							if($val === null) {
								$tmp_datarow[(string)$key] = null; // preserve null
							} else {
								$tmp_datarow[(string)$key] = (string) $val; // force string
							} //end if
						} //end foreach
						//--
						$mysql_result_arr[] = (array) $tmp_datarow;
						//--
						$tmp_datarow = array();
						//--
					} //end if
					//--
				} //end for
				//--
			} //end if else
			//--
		} //end else
		//--

		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		return (array) $mysql_result_arr;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * MySQL Query :: Read (Associative) - Single Row (just for 1 row, to easy the use of data from queries).
	 * !!! This will raise an error if more than one row(s) are returned !!!
	 * This function does not support multiple rows because the associative data is structured without row iterator.
	 * For queries that return more than one row use: read_adata() or read_data().
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @hints	ALWAYS use a LIMIT 1 OFFSET 0 with all queries using this function to avoid situations that will return more than 1 rows and will raise ERROR with this function.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @param RESOURCE $y_connection				:: the connection
	 * @return ARRAY (asociative) of results		:: Returns just a SINGLE ROW as: array('column1' => 'val1', 'column2' => null, ... 'column-n' => 't')
	 */
	public static function read_asdata($queryval, $params_or_title='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'READ-asDATA');
		//==

		//-- samples
		// $queryval = "SELECT * FROM `tablename` WHERE (`field` = 'x') ORDER BY `field` ASC LIMIT 1 OFFSET $offset"; // [LIMIT-OFFSET]
		//--

		//--
		$time_start = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		$use_param_query = false;
		if(is_array($params_or_title)) {
			if(Smart::array_size($params_or_title) > 0) {
				$use_param_query = true;
			} else {
				$params_or_title = '';
			} //end if else
		} //end if
		//--
		if($use_param_query === true) {
			$the_query_title = '';
			$queryval = self::prepare_param_query($queryval, (array)$params_or_title, $y_connection);
		} else {
			$the_query_title = (string) $params_or_title;
		} //end if else
		//--
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		if($chk !== 0) {
			$error = 'Query FAILED:'."\n".$err;
		} //end if else
		if(!is_object($result)) {
			//$error = 'Query is INVALID (Returns No Data) for this context: '.__FUNCTION__.'()';
			return array();
		} //end if else
		//--

		//--
		$time_end = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_end = (float) (microtime(true) - (float)$time_start);
		} //end if
		//--

		//--
		$number_of_rows = 0;
		$number_of_fields = 0;
		if((string)$error == '') {
			$number_of_rows = @mysqli_num_rows($result);
			$number_of_fields = @mysqli_num_fields($result);
		} //end if
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-queries', 1, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-time', $time_end, '+');
			//--
			if(is_array($params_or_title)) {
				$dbg_query_params = (array) $params_or_title;
			} else {
				$dbg_query_params = '';
			} //end if else
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'read',
				'data' => 'asREAD [SINGLE-ROW-ASSOCIATIVE] :: '.$the_query_title,
				'query' => $queryval,
				'params' => $dbg_query_params,
				'rows' => $number_of_rows,
				'time' => Smart::format_number_dec($time_end, 9, '.', ''),
				'connection' => (string) self::get_connection_id($y_connection)
			]);
			//--
		} //end if
		//--

		//-- init vars
		$mysql_result_arr = array(); // store SELECT data
		//--
		if((string)$error != '') {
			//--
			self::error(self::get_connection_id($y_connection), 'READ-asDATA', $error, $queryval, $params_or_title);
			return array();
			//--
		} else {
			//--
			if($number_of_rows == 1) {
				//--
				$record = @mysqli_fetch_assoc($result);
				//--
				if(is_array($record)) {
					foreach($record as $key => $val) {
						if($val === null) {
							$mysql_result_arr[(string)$key] = null; // preserve null
						} else {
							$mysql_result_arr[(string)$key] = (string) $val; // force string
						} //end if else
					} //end foreach
				} //end if
				//--
			} else {
				//--
				if($number_of_rows > 1) {
					self::error(self::get_connection_id($y_connection), 'READ-asDATA', 'The Result contains more than one row ...', $queryval, $params_or_title);
					return array();
				} //end if
				//--
			} //end if else
			//--
		} //end else
		//--

		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		return (array) $mysql_result_arr;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * MySQL Query :: Write.
	 * This function is intended to be used for write type queries: BEGIN (TRANSACTION) ; COMMIT ; ROLLBACK ; INSERT ; INSERT IGNORE ; REPLACE ; UPDATE ; CREATE SCHEMAS ; CALLING STORED PROCEDURES ...
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @param RESOURCE $y_connection				:: the connection
	 * @return ARRAY 								:: [ 0 => 'control-message', 1 => #affected-rows, 2 => #last-inserted-id(autoincrement)|0|null ]
	 */
	public static function write_data($queryval, $params_or_title='', $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'WRITE-DATA');
		//==

		//-- samples
		// $queryval = 'BEGIN'; // start transaction
		// $queryval = 'UPDATE `tablename` SET `field` = \'value\' WHERE (`id_field` = \'val1\')';
		// $queryval = 'INSERT INTO `tablename` (`desiredfield1`, `desiredfield2`) VALUES (\'val1\', \'val2\')';
		// $queryval = 'DELETE FROM `tablename` WHERE (`id_field` = \'val1\')';
		// $queryval = 'COMMIT'; // commit transaction (on success)
		// $queryval = 'ROLLBACK'; // rollback transaction (on error)
		//--

		//--
		$time_start = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		$use_param_query = false;
		if(is_array($params_or_title)) {
			if(Smart::array_size($params_or_title) > 0) {
				$use_param_query = true;
			} else {
				$params_or_title = '';
			} //end if else
		} //end if
		//--
		if($use_param_query === true) {
			$the_query_title = '';
			$queryval = self::prepare_param_query($queryval, (array)$params_or_title, $y_connection);
		} else {
			$the_query_title = (string) $params_or_title;
		} //end if else
		//--
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		$affected = 0;
		if($chk !== 0) {
			$error = 'Query FAILED:'."\n".$err;
		} else {
			$affected = @mysqli_affected_rows($y_connection);
		} //end if else
		//--

		//--
		$time_end = 0;
		if(SmartEnvironment::ifDebug()) {
			$time_end = (float) (microtime(true) - (float)$time_start);
		} //end if
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-queries', 1, '+');
			//--
			SmartEnvironment::setDebugMsg('db', 'mysqli|total-time', $time_end, '+');
			//--
			if(is_array($params_or_title)) {
				$dbg_query_params = (array) $params_or_title;
			} else {
				$dbg_query_params = '';
			} //end if else
			//--
			if(
				(stripos((string)trim((string)$queryval), 'BEGIN') === 0) OR
				(stripos((string)trim((string)$queryval), 'START TRANSACTION') === 0) OR
				(stripos((string)trim((string)$queryval), 'COMMIT') === 0) OR
				(stripos((string)trim((string)$queryval), 'ROLLBACK') === 0)
			) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'transaction',
					'data' => 'TRANSACTION :: '.$the_query_title,
					'query' => $queryval,
					'params' => '',
					'time' => Smart::format_number_dec($time_end, 9, '.', ''),
					'connection' => (string) self::get_connection_id($y_connection)
				]);
			} elseif(stripos((string)trim((string)$queryval), 'SET ') === 0) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'set',
					'data' => 'SET :: '.$the_query_title,
					'query' => $queryval,
					'params' => $dbg_query_params,
					'time' => Smart::format_number_dec($time_end, 9, '.', ''),
					'connection' => (string) self::get_connection_id($y_connection)
				]);
			} elseif(
				(stripos((string)trim((string)$queryval), 'TRUNCATE ') === 0) OR
				(stripos((string)trim((string)$queryval), 'DROP ') === 0) OR
				(stripos((string)trim((string)$queryval), 'CREATE ') === 0) OR
				(stripos((string)trim((string)$queryval), 'ALTER ') === 0) OR
				(stripos((string)trim((string)$queryval), 'ANALYZE ') === 0) OR
				(stripos((string)trim((string)$queryval), 'OPTIMIZE ') === 0) OR
				(stripos((string)trim((string)$queryval), 'EXPLAIN ') === 0)
			) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'special',
					'data' => 'COMMAND :: '.$the_query_title,
					'query' => $queryval,
					'params' => $dbg_query_params,
					'rows' => $affected,
					'time' => Smart::format_number_dec($time_end, 9, '.', ''),
					'connection' => (string) self::get_connection_id($y_connection)
				]);
			} else {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'write',
					'data' => 'WRITE :: '.$the_query_title,
					'query' => $queryval,
					'params' => $dbg_query_params,
					'rows' => $affected,
					'time' => Smart::format_number_dec($time_end, 9, '.', ''),
					'connection' => (string) self::get_connection_id($y_connection)
				]);
			} //end if else
			//--
		} //end if
		//--

		//--
		$last_insert_id = null;
		//--
		if((string)$error != '') {
			//--
			$message = 'errorsqlwriteoperation: '.$error;
			//--
			self::error(self::get_connection_id($y_connection), 'WRITE-DATA', $error, $queryval, $params_or_title);
			return array($message, 0);
			//--
		} else {
			//--
			$last_insert_id = (string) @mysqli_insert_id($y_connection); // may return int as string if max int overflows
			//--
			$message = 'oksqlwriteoperation'; // this can be extended to detect extra notices
			//--
		} //end else
		//--

		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		return array($message, Smart::format_number_int($affected, '+'), $last_insert_id);
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Create Escaped Write SQL Statements from Data - to be used with MySQL for: INSERT ; UPDATE ; IN-SELECT
	 * Can be used with: write_data() to build INSERT / UPDATE queries from an associative array
	 * or can be used with read_data(), read_adata(), read_asdata(), count_data() to build IN-SELECT queries from a non-associative array
	 *
	 * @param ARRAY $arrdata 						:: associative array: array of form data as $arr=array(); $arr['field1'] = 'a string'; $arr['field2'] = 100; | non-associative array $arr[] = 'some value'; $arr[] = 'other-value', ...
	 * @param ENUM $mode							:: mode: 'insert' | 'update' | 'in-select'
	 * @param RESOURCE $y_connection 				:: the connection to Server
	 * @return STRING								:: The SQL partial Statement
	 */
	public static function prepare_statement($arrdata, $mode, $y_connection='DEFAULT') {

		// version: 170411

		//==
		$y_connection = self::check_connection($y_connection, 'PREPARE-STATEMENT');
		//==

		//--
		$mode = strtolower((string)$mode);
		//--
		switch((string)$mode) {
			//-- associative array
			case 'insert':
				$mode = 'insert';
				break;
			case 'update':
				$mode = 'update';
				break;
			//-- non-associative array
			case 'in-select':
				$mode = 'in-select';
				break;
			//-- invalid
			default:
				self::error(self::get_connection_id($y_connection), 'PREPARE-STATEMENT', 'Invalid Mode', '', $mode);
				return '';
		} //end switch
		//--

		//--
		$tmp_query = '';
		//--
		$tmp_query_x = '';
		$tmp_query_y = '';
		$tmp_query_z = '';
		$tmp_query_w = '';
		//--

		//--
		if(is_array($arrdata)) {
			//--
			foreach($arrdata as $key => $val) {
				//-- check for SQL INJECTION
				$key = (string) trim(str_replace(array('`', "'", '"'), array('', '', ''), (string)$key));
				//-- Except in-select, do not allow invalid keys as they represent the field names ; valid fields must contain only the following chars [A..Z][a..z][0..9][_]
				if((string)$mode == 'in-select') { // in-select
					$key = (int) $key; // force int keys
				} elseif(!self::validate_table_and_fields_names($key)) { // no unicode modifier
					self::error(self::get_connection_id($y_connection), 'PREPARE-STATEMENT', 'Invalid KEY', '', $key);
					return '';
				} //end if
				//--
				$val_x = ''; // reset
				//--
				if(is_array($val)) { // array (this is a special case, and always escape data)
					//--
					$val_x = (string) "'".self::escape_str(Smart::array_to_list($val), '', $y_connection)."'"; // array values will be converted to: <val1>, <val2>, ...
					//--
				} elseif($val === null) { // emulate the SQL: NULL
					//--
					$val_x = 'NULL';
					//--
				} elseif($val === false) { // emulate the SQL: FALSE
					//--
					$val_x = 'FALSE';
					//--
				} elseif($val === true) { // emulate the SQL: TRUE
					//--
					$val_x = 'TRUE';
					//--
				} elseif(SmartValidator::validate_numeric_integer_or_decimal_values($val) === true) { // number ; {{{SYNC-DETECT-PURE-NUMERIC-INT-OR-DECIMAL-VALUES}}}
					//--
					$val_x = (string) trim((string)$val); // not escaped, it is safe: numeric and can contain just 0-9 - .
					//--
				} else { // string or other cases
					//--
					$val_x = (string) "'".self::escape_str($val, '', $y_connection)."'";
					//--
				} //end if else
				//--
				if((string)$mode == 'in-select') { // in-select
					$tmp_query_w .= $val_x.',';
				} elseif((string)$mode == 'update') { // update
					$tmp_query_x .= '`'.$key.'`'.'='.$val_x.',';
				} else { // insert
					$tmp_query_y .= '`'.$key.'`'.',';
					$tmp_query_z .= $val_x.',';
				} //end if else
				//--
			} //end while
			//--
		} else {
			//--
			self::error(self::get_connection_id($y_connection), 'PREPARE-STATEMENT', 'The first argument must be array !', '', '');
			return '';
			//--
		} //end if else
		//--

		//-- eliminate last comma
		if((string)$mode == 'in-select') { // in-select
			$tmp_query_w = rtrim($tmp_query_w, ' ,');
		} elseif((string)$mode == 'update') { // update
			$tmp_query_x = rtrim($tmp_query_x, ' ,');
		} else { // insert
			$tmp_query_y = rtrim($tmp_query_y, ' ,');
			$tmp_query_z = rtrim($tmp_query_z, ' ,');
		} //end if else
		//--

		//--
		if((string)$mode == 'in-select') { // in-select
			$tmp_query = ' IN ('.$tmp_query_w.') ';
		} elseif((string)$mode == 'update') { // update
			$tmp_query = ' SET '.$tmp_query_x.' ';
		} else { // (new) insert
			$tmp_query = ' ('.$tmp_query_y.') VALUES ('.$tmp_query_z.') ';
		} //end if else
		//--

		//--
		return (string) $tmp_query;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Create Escaped SQL Statements from Parameters and Array of Data by replacing ? (question marks)
	 * This can be used for a full SQL statement or just for a part.
	 * The statement must not contain any Single Quotes to prevent SQL injections which are unpredictable if mixing several statements at once !
	 *
	 * @param STRING $query							:: SQL Statement to process like '   WHERE ("id" = ?)'
	 * @param ARRAY $arrdata 						:: The non-associative array as of: $arr=array('a');
	 * @param RESOURCE $y_connection 				:: the connection to Server
	 * @return STRING								:: The SQL processed (partial/full) Statement
	 */
	public static function prepare_param_query($query, $replacements_arr, $y_connection='DEFAULT') { // {{{SYNC-SQL-PARAM-QUERY}}}

		// version: 20210328

		//==
		$y_connection = self::check_connection($y_connection, 'PREPARE-PARAM-QUERY');
		//==

		//--
		if(!is_string($query)) {
			self::error(self::get_connection_id($y_connection), 'PREPARE-PARAM-QUERY', 'Query is not a string !', print_r($query,1), $replacements_arr);
			return ''; // single quote is not allowed
		} //end if
		//--
		if((string)trim((string)$query) == '') {
			self::error(self::get_connection_id($y_connection), 'PREPARE-PARAM-QUERY', 'Query is empty !', (string)$query, $replacements_arr);
			return ''; // empty query not allowed
		} //end if
		//--
		if(!is_array($replacements_arr)) {
			self::error(self::get_connection_id($y_connection), 'PREPARE-PARAM-QUERY', 'Query Replacements is NOT Array !', (string)$query, $replacements_arr);
			return ''; // replacements must be an array
		} //end if
		//--
		if(Smart::array_size($replacements_arr) <= 0) { // this must be a separate check than if is array ; if there are no replacements return the plain / unchanged query (as below) like it would not contain ?
			return (string) $query; // this situation is important for a query like: SELECT * FROM table WHERE (json_field::jsonb ? 1) and if it would have no
		} //end if
		//--
		$out_query = '';
		//--
		if(strpos((string)$query, '?') !== false) {
			//--
			if(strpos($query, "'") !== false) { // do this check only if contains ? ... this must be avoided as below will be exploded by ? thus if a ? is inside '' this is a problem ...
				self::error(self::get_connection_id($y_connection), 'PREPARE-PARAM-QUERY', 'Query used for prepare with params in '.__FUNCTION__.'() cannot contain single quotes to prevent possible SQL injections which can produce unpredictable results !', (string)$query, $replacements_arr);
				return ''; // single quote is not allowed
			} //end if
			//--
			$expr_arr = (array) explode('?', (string)$query);
			$expr_count = count($expr_arr);
			//--
			for($i=0; $i<$expr_count; $i++) {
				//--
				$out_query .= (string) $expr_arr[$i];
				//--
				if($i < ($expr_count - 1)) {
					//--
					if(!array_key_exists((string)$i, $replacements_arr)) {
						self::error(self::get_connection_id($y_connection), 'PREPARE-PARAM-QUERY', 'Invalid Replacements Array size ; Key='.$i, (string)$query, $replacements_arr);
						return ''; // array key does not exists in replacements
						break;
					} //end if
					//--
					if(SmartValidator::validate_numeric_integer_or_decimal_values($replacements_arr[$i]) === true) { // {{{SYNC-DETECT-PURE-NUMERIC-INT-OR-DECIMAL-VALUES}}}
						$out_query .= (string) trim((string)$replacements_arr[$i]); // not escaped, it is safe: numeric and can contain just 0-9 - .
					} else {
						$out_query .= "'".self::escape_str((string)$replacements_arr[$i], '', $y_connection)."'";
					} //end if else
					//--
				} //end if
				//--
			} //end for
			//--
		} else {
			//--
			$out_query = (string) $query; // query contains no ? ... return it unchanged
			//--
		} //end if else
		//--

		//--
		return (string) $out_query;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get A UNIQUE (SAFE) ID for DB Tables
	 *
	 * @param ENUM $y_mode 							:: mode: uid10str | uid10num | uid10seq | uid12seq | uid32 | uid34 | uid36 | uid45
	 * @param STRING $y_field_name 					:: the field name
	 * @param STRING $y_table_name 					:: the table name
	 * @param RESOURCE $y_connection 				:: the connection to Server
	 * @return STRING 								:: the generated Unique ID
	 *
	 */
	public static function new_safe_id($y_mode, $y_id_field, $y_table_name, $y_connection='DEFAULT') {

		//==
		$y_connection = self::check_connection($y_connection, 'NEW-SAFE-ID');
		//==

		//--
		if(!self::validate_table_and_fields_names($y_table_name)) {
			self::error(self::get_connection_id($y_connection), 'NEW-SAFE-ID', 'Get New Safe ID', 'Invalid Table Name', $y_table_name);
			return '';
		} //end if
		if(!self::validate_table_and_fields_names($y_id_field)) {
			self::error(self::get_connection_id($y_connection), 'NEW-SAFE-ID', 'Get New Safe ID', 'Invalid Field Name', $y_id_field.' / [Table='.$y_table_name.']');
			return '';
		} //end if
		//--

		//--
		$tmp_result = 'NO-ID-INIT'; //init (must be not empty)
		$counter = 0; // default is zero
		//--
		while((string)$tmp_result != '') { // while we cannot find an unused ID
			//--
			$counter += 1;
			//--
			if($counter > 7500) { // loop to max 7500
				self::error(self::get_connection_id($y_connection), 'NEW-SAFE-ID', 'Get New Safe ID', 'Could Not Assign a Unique ID', '(timeout / 7500) ... try again !');
				return '';
			} //end if
			//--
			if(($counter % 500) == 0) {
				sleep(1);
			} //end if
			//--
			$new_id = 'NO-ID-ALGO';
			switch((string)$y_mode) {
				// IMPORTANT: MySQL is not always safe for case sensitive UUIDs (depends how collation is set for the ID field) such as base62 ...
				case 'uid45':
					$new_id = (string) Smart::uuid_45((string)Smart::net_server_id()); // will use the server ID.Host as Prefix to ensure it is true unique in a cluster
					break;
				case 'uid36':
					$new_id = (string) Smart::uuid_36((string)Smart::net_server_id()); // will use the server ID.Host as Prefix to ensure it is true unique in a cluster
					break;
				case 'uid34': // for cluster
					$new_id = (string) Smart::uuid_34();
					break;
				case 'uid32':
					$new_id = (string) Smart::uuid_32();
					break;
				case 'uid12seq': // for cluster ; ! sequences are not safe without a second registry allocation table as the chance to generate the same ID in the same time moment is just 1 in 999
					$new_id = (string) Smart::uuid_12_seq();
					break;
				case 'uid10seq': // ! sequences are not safe without a second registry allocation table as the chance to generate the same ID in the same time moment is just 1 in 999
					$new_id = (string) Smart::uuid_10_seq();
					break;
				case 'uid10num':
					$new_id = (string) Smart::uuid_10_num();
					break;
				case 'uid10str':
				default:
					$new_id = (string) Smart::uuid_10_str();
			} //end switch
			//--
			$result_arr = array();
			$result_arr = self::read_data('SELECT `'.$y_id_field.'` FROM `'.$y_table_name.'` WHERE (`'.$y_id_field.'` = \''.self::escape_str($new_id, '', $y_connection).'\') LIMIT 1 OFFSET 0', 'Checking if NEW ID Exists ...', $y_connection);
			$tmp_result = '';
			if(array_key_exists(0, $result_arr)) {
				$tmp_result = (string) trim((string)$result_arr[0]);
			} //end if
			$result_arr = array();
			//--
		} //end while
		//--

		//--
		return (string) $new_id;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Check and Return the Server Version
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function check_server_version($y_connection='DEFAULT', $y_revalidate=false) {

		//==
		$y_connection = self::check_connection($y_connection, 'CHECK-SERVER-VERSION');
		//==

		//--
		if($y_revalidate !== true) {
			if((string)self::$server_version[(string)self::get_connection_id($y_connection)] != '') {
				return (string) self::$server_version[(string)self::get_connection_id($y_connection)];
			} //end if
		} //end if
		//--

		//--
		$queryval = 'SELECT VERSION()';
		$result = @mysqli_query($y_connection, $queryval, MYSQLI_STORE_RESULT);
		$chk = @mysqli_errno($y_connection);
		$err = @mysqli_error($y_connection);
		//--

		//--
		$error = '';
		if($chk !== 0) {
			$error = 'Query FAILED:'."\n".$err;
		} //end if else
		//--

		//--
		if((string)$error != '') {
			//--
			self::error(self::get_connection_id($y_connection), 'CHECK-SERVER-VERSION', $error, $queryval, '');
			return '';
			//--
		} else {
			//--
			$record = @mysqli_fetch_row($result);
			//--
		} //end if else
		//--
		if($result instanceof mysqli_result) {
			@mysqli_free_result($result);
		} //end if
		//--

		//--
		$mysql_num_version = trim((string)$record[0]);
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
				'type' => 'metainfo',
				'data' => 'Server Version: '.$mysql_num_version,
				'connection' => (string) self::get_connection_id($y_connection),
				'skip-count' => 'yes'
			]);
		} //end if
		//--

		//--
		if(version_compare((string)self::major_version(self::minVersionServer), (string)self::major_version($mysql_num_version)) > 0) {
			self::error($y_connection, 'Server-Version', 'Server Version not supported', $mysql_num_version, 'version='.self::major_version(self::minVersionServer).' or later is required to run this software !');
			return '';
		} //end if
		//--

		//--
		self::$server_version[(string)self::get_connection_id($y_connection)] = (string) $mysql_num_version;
		//--

		//--
		return (string) $mysql_num_version;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the MySQLi Connection ID
	 * This is for internal use only !
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function get_connection_id($y_connection) {
		//--
		$out = '?CONNECTION?';
		//--
		if(!is_object($y_connection)) { // if no connection
			//--
			$out = 'NO-CONNECTION';
			//--
		} else {
			//--
			if((string)$y_connection->thread_id == '') {
				$out = 'CONNECTION-LOST';
			} else {
				$out = 'ThreadID:'.$y_connection->thread_id;
			} //end if else
			//--
		} //end if else
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Conform the MySQLi Connection Array (fix for PHP8)
	 *
	 * @param ARRAY 	$cfg			:: The configuration array or an empty array
	 * @return ARRAY
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function conform_config_array($cfg) {
		//--
		if(!is_array($cfg)) {
			$cfg = array();
		} //end if
		//--
		return (array) Smart::array_init_keys(
			(array) $cfg,
			[
				'type',
				'server-host',
				'server-port',
				'dbname',
				'username',
				'password',
				'timeout',
				'slowtime',
				'transact'
			]
		);
		//--
	} //END FUNCTION
	//======================================================


	//======================================================================
	//===== PRIVATES
	//======================================================================


	//======================================================
	/**
	 * Check the connection to MySQL if Active
	 *
	 * @param RESOURCE 	$y_connection 	:: The Connection to Server
	 * @param STRING 	$y_description	:: The Description of Where it is Checked (for having a clue where it fails)
	 * @return HALT EXECUTION IF NO CONNECTION OR CONNECTION BUSY AFTER SEVERAL RETRIES
	 *
	 */
	private static function check_connection($y_connection, $y_description) {
		//--
		$cfg = (array) Smart::get_from_config('mysqli', 'array');
		//--
		if($y_connection === 'DEFAULT') { // just for the default connection !!!
			//--
			if(!self::$default_connection) { // MySQL default connection check to avoid re-connection which can break transactions
				//--
				if(Smart::array_size($cfg) <= 0) {
					self::error('', 'CHECK-DEFAULT-MYSQLI-CONFIGS', 'The Default MySQLi Configs not detected !', 'The configs[mysqli] is not an array !', $y_description);
					return null;
				} //end if
				//--
				$cfg = (array) self::conform_config_array($cfg);
				//--
				if(((string)$cfg['server-host'] == '') OR ((int)$cfg['server-port'] <= 0) OR ((string)$cfg['dbname'] == '') OR ((string)$cfg['username'] == '')) { // {{{SYNC-MYSQLI-CFG-PARAMS-CHECK}}}
					self::error('', 'CHECK-DEFAULT-MYSQLI-CONFIGS', 'The Default MySQLi Configs are not complete !', 'Some of the configs[mysqli] parameters are missing !', $y_description);
					return null;
				} //end if
				//-- {{{SYNC-CONNECTIONS-IDS}}}
				$the_conn_key = (string) $cfg['server-host'].':'.$cfg['server-port'].'@'.$cfg['dbname'].'#'.$cfg['username'];
				if((array_key_exists('mysqli', (array)SmartEnvironment::$Connections)) AND (array_key_exists((string)$the_conn_key, (array)SmartEnvironment::$Connections['mysqli']))) { // if the connection was made before using the SmartMysqliExtDb
					//--
					$y_connection = &SmartEnvironment::$Connections['mysqli'][(string)$the_conn_key];
					//--
					self::$default_connection = (string) $the_conn_key;
					//--
					if(SmartEnvironment::ifDebug()) {
						SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
							'type' => 'open-close',
							'data' => 'Re-Using Connection to Server as DEFAULT: '.$the_conn_key,
							'connection' => (string) self::get_connection_id($y_connection)
						]);
					} //end if
					//--
				} else {
					//--
					$y_connection = self::server_connect( // create a DEFAULT connection using default mysqli connection params from config
						(string) $cfg['server-host'],
						(int)    $cfg['server-port'],
						(string) $cfg['dbname'],
						(string) $cfg['username'],
						(string) $cfg['password'],
						(int)    $cfg['timeout'],
						(string) $cfg['transact'],
						(float)  $cfg['slowtime'],
						(string) $cfg['type']
					);
					//--
					self::$default_connection = (string) $the_conn_key;
					//--
					if(is_object($y_connection)) {
						//--
						if((string)$y_connection->thread_id != '') {
							//--
							define('SMART_FRAMEWORK_DB_VERSION_MySQL', self::check_server_version($y_connection, true)); // re-validate
							//--
						} //end if
						//--
					} //end if
					//--
				} //end if else
				//--
			} else {
				//-- re-use the default connection
				$y_connection = &SmartEnvironment::$Connections['mysqli'][(string)self::$default_connection];
				//--
			} //end if
			//--
		} //end if
		//--
		if(!is_object($y_connection)) { // if no connection
			//--
			self::error(self::get_connection_id($y_connection), 'CHECK-CONNECTION', 'Connection is BROKEN !', 'Connection-ID: '.$y_connection, $y_description);
			return null;
			//--
		} else {
			//--
			if((string)$y_connection->thread_id == '') {
				self::error(self::get_connection_id($y_connection), 'CHECK-CONNECTION', 'Connection LOST !', 'Connection-ID: '.self::get_connection_id($y_connection), $y_description);
				return null;
			} //end if
			//--
		} //end if
		//--
		return $y_connection; // obj / mixed
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	private static function validate_table_and_fields_names($y_table_or_field) {
		//--
		$y_table_or_field = (string) $y_table_or_field;
		//--
		if(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $y_table_or_field)) {
			$is_ok = true;
		} else {
			$is_ok = false;
		} //end if else
		//--
		return $is_ok;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	// returns major version for mysql versions
	private static function major_version($y_version) {
		//--
		$arr = (array) explode('.', (string)trim((string)$y_version));
		if(!array_key_exists(0, $arr)) {
			$arr[0] = null;
		} //end if
		if(!array_key_exists(1, $arr)) {
			$arr[1] = null;
		} //end if
		//--
		return (string) trim((string)$arr[0]).'.'.trim((string)$arr[1]).'.x';
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Displays the MySQL Errors and HALT EXECUTION (This have to be a FATAL ERROR as it occur when a FATAL MySQLi ERROR happens or when a Query Syntax is malformed)
	 * PRIVATE
	 *
	 * @return :: HALT EXECUTION WITH ERROR MESSAGE
	 *
	 */
	private static function error($y_connection_id, $y_area, $y_error_message, $y_query, $y_params_or_title, $y_warning='') {
		//--
		if(defined('SMART_SOFTWARE_SQLDB_FATAL_ERR') AND (SMART_SOFTWARE_SQLDB_FATAL_ERR === false)) {
			throw new Exception('#MYSQLi-DB@'.$y_connection_id.'# :: Q# // MySQLi Client :: EXCEPTION :: '.$y_area."\n".$y_error_message);
			return;
		} //end if
		//--
		$err_log = $y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Params / Title:'."\n".print_r($y_params_or_title,1)."\n".'*** Query:'."\n".$y_query;
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
			if(is_array($y_params_or_title)) {
				$the_params = '*** Params ***'."\n".print_r($y_params_or_title, 1);
			} elseif((string)$y_params_or_title != '') {
				$the_params = '[ Reference Title ]: '.$y_params_or_title;
			} else {
				$the_params = '- No Params or Reference Title -';
			} //end if
			$the_query_info = (string) trim((string)$y_query);
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
				'MySQLi Client',
				'MariaDB / MySQL',
				'SQL/DB',
				'Server',
				'lib/core/img/db/mysql-logo.svg',
				(int)    $width, // width
				(string) $the_area, // area
				(string) $the_error_message, // err msg
				(string) $the_params, // title or params
				(string) $the_query_info // sql statement
			);
		} //end if
		//--
		Smart::raise_error(
			'#MYSQLi-DB@'.$y_connection_id.' :: Q# // MySQLi Client :: ERROR :: '.$err_log, // err to register
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
 * Class: SmartMysqliExtDb - provides a Dynamic (Extended) Client for MariaDB Server / MySQL that can be used with custom made connections.
 * This class is made to be used with custom made MySQLi connections (other servers than default).
 *
 * Minimum supported version of MySQL/MariaDB is: 5.0.0
 * Tested and Stable with MariaDB versions: 5.5.x / 10.x / 11.x
 * Tested and Stable on MySQL versions: 5.x / 6.x / 7.x / 8.x / 9.x
 *
 * <code>
 * // Sample config array for this class constructor:
 * $custom_mysql = array();
 * $custom_mysql['type']         = 'mariadb';               // mariadb (UTF8.MB4) / mysql (UTF8)
 * $custom_mysql['server-host']  = '127.0.0.1';             // database host (default is 127.0.0.1)
 * $custom_mysql['server-port']  = '3306';                  // database port (default is 3306)
 * $custom_mysql['dbname']       = 'smart_framework';       // database name
 * $custom_mysql['username']     = 'root';                  // sql server user name
 * $custom_mysql['password']     = base64_encode('root');   // sql server Base64-Encoded password for that user name B64
 * $custom_mysql['timeout']      = 30;                      // connection timeout (how many seconds to wait for a valid MySQL Connection)
 * $custom_mysql['slowtime']     = 0.0050;                  // 0.0025 .. 0.0090 slow query time (for debugging)
 * $custom_mysql['transact']     = 'REPEATABLE READ';       // Default Transaction Level: 'REPEATABLE READ' | 'READ COMMITTED' | '' to leave it as default
 * // sample usage:
 * $mysql = new SmartMysqliExtDb($custom_mysql);
 * $mysql->read_adata('SELECT * FROM `my_table` LIMIT 100 OFFSET 0');
 * //... for other hints look to the samples of the class: SmartMysqliDb::*
 * </code>
 *
 * @usage 		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints		This class have no catcheable Exception because the ONLY errors will raise are when the server returns an ERROR regarding a malformed SQL Statement, which is not acceptable to be just Exception, so will raise a fatal error !
 *
 * @depends 	extensions: PHP MySQLi ; classes: Smart, SmartEnvironment, SmartUnicode, SmartComponents (optional) ; constants: SMART_FRAMEWORK_SQL_CHARSET
 * @version 	v.20241220
 * @package 	Plugins:Database:MySQL
 *
 */
final class SmartMysqliExtDb {

	// ->

	private $connection;


	//==================================================


	/**
	 * Class Constructor - will initiate also the custom connection for a MariaDB Server / MySQL specified as parameters of this function.
	 *
	 * @param ARRAY $y_configs_arr 					:: The Array of Configuration parameters - the ARRAY STRUCTURE should be identical with the default config.php: $configs['mysqli'].
	 *
	 */
	public function __construct(array $y_configs_arr) {
		//--
		$y_configs_arr = (array) SmartMysqliDb::conform_config_array($y_configs_arr);
		//-- {{{SYNC-CONNECTIONS-IDS}}}
		$the_conn_key = (string) $y_configs_arr['server-host'].':'.$y_configs_arr['server-port'].'@'.$y_configs_arr['dbname'].'#'.$y_configs_arr['username'];
		if(
			( // {{{SYNC-MYSQLI-CFG-PARAMS-CHECK}}}
				((string)$y_configs_arr['server-host'] != '') AND
				((int)$y_configs_arr['server-port'] > 0) AND
				((string)$y_configs_arr['dbname'] != '') AND
				((string)$y_configs_arr['username'] != '')
			) AND
			(array_key_exists('mysqli', (array)SmartEnvironment::$Connections)) AND
			(array_key_exists((string)$the_conn_key, (array)SmartEnvironment::$Connections['mysqli']))
		) {
			//-- try to reuse the connection :: only check if array key exists, not if it is a valid resource ; this should be as so to avoid mismatching connection mixings (if by example will re-use the connection of another server, and connection is broken in the middle of a transaction, it will fail ugly ;) and out of any control !
			$this->connection = &SmartEnvironment::$Connections['mysqli'][(string)$the_conn_key];
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mysqli|log', [
					'type' => 'open-close',
					'data' => 'Re-Using Connection to Server: '.$the_conn_key,
					'connection' => (string) SmartMysqliDb::get_connection_id($this->connection)
				]);
			} //end if
			//--
		} else {
			//-- connect
			$this->connection = SmartMysqliDb::server_connect(
				(string) $y_configs_arr['server-host'],
				(int)    $y_configs_arr['server-port'],
				(string) $y_configs_arr['dbname'],
				(string) $y_configs_arr['username'],
				(string) $y_configs_arr['password'],
				(int)    $y_configs_arr['timeout'],
				(string) $y_configs_arr['transact'],
				(float)  $y_configs_arr['slowtime'],
				(string) $y_configs_arr['type']
			);
			//--
			$this->check_server_version(true); // re-validate
			//--
		} //end if else
		//--
	} //END FUNCTION


	//==================================================


	/**
	 * Returns the connection resource of the current MariaDB Server / MySQL.
	 */
	public function getConnection() {
		//--
		return $this->connection;
		//--
	} //END FUNCTION


	//==================================================


	/**
	 * Fix a string to be compliant with MySQL LIKE / SIMILAR syntax.
	 * It will use special quotes for the LIKE / SIMILAR special characters: % _
	 * This function IS NOT INTENDED TO ESCAPE AGAINST SQL INJECTIONS ; USE IT ONLY WITH PREPARED PARAMS OR USE escape_str() with mode 'likes'
	 *
	 * @param STRING $y_string						:: A String or a Number to be Quoted for LIKES
	 */
	public function quote_likes($y_string) {
		//--
		return (string) SmartMysqliDb::quote_likes($y_string);
		//--
	} //END FUNCTION


	/**
	 * Escape a string to be compliant and Safe (against SQL Injection) with MySQL standards.
	 * This function WILL NOT ADD the SINGLE QUOTES (') arround the string, but just will just escape it to be safe.
	 *
	 * @param STRING $y_string						:: A String or a Number to be Escaped
	 * @param ENUM $y_mode							:: '' = default ; 'likes' = Escape LIKE Syntax (% _)
	 * @return STRING 								:: The Escaped String / Number
	 *
	 */
	public function escape_str($y_string, $y_mode='') {
		//--
		return SmartMysqliDb::escape_str($y_string, $y_mode, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * Check if a Table Exists in the current Database.
	 *
	 * @param STRING $y_table 						:: The Table Name
	 * @return 0/1									:: 1 if exists ; 0 if not
	 *
	 */
	public function check_if_table_exists($y_table) {
		//--
		return SmartMysqliDb::check_if_table_exists($y_table, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * MySQL Query :: Count
	 * This function is intended to be used for count type queries: SELECT COUNT().
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @return INTEGER								:: the result of COUNT()
	 */
	public function count_data($queryval, $params_or_title='') {
		//--
		return SmartMysqliDb::count_data($queryval, $params_or_title, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * MySQL Query :: Read (Non-Associative) one or multiple rows.
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @return ARRAY (non-asociative) of results	:: array('column-0-0', 'column-0-1', null, ..., 'column-0-n', 'column-1-0', 'column-1-1', ... 'column-1-n', ..., 'column-m-0', 'column-m-1', ..., 'column-m-n')
	 */
	public function read_data($queryval, $params_or_title='') {
		//--
		return SmartMysqliDb::read_data($queryval, $params_or_title, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * MySQL Query :: Read (Associative) one or multiple rows.
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @return ARRAY (asociative) of results		:: array(0 => array('column1' => 'val1', 'column2' => null, ... 'column-n' => 't'), 1 => array('column1' => 'val2', 'column2' => 'val2', ... 'column-n' => 'f'), ..., m => array('column1' => 'valM', 'column2' => 'xyz', ... 'column-n' => 't'))
	 */
	public function read_adata($queryval, $params_or_title='') {
		//--
		return SmartMysqliDb::read_adata($queryval, $params_or_title, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * MySQL Query :: Read (Associative) - Single Row (just for 1 row, to easy the use of data from queries).
	 * !!! This will raise an error if more than one row(s) are returned !!!
	 * This function does not support multiple rows because the associative data is structured without row iterator.
	 * For queries that return more than one row use: read_adata() or read_data().
	 * This function is intended to be used for read type queries: SELECT.
	 *
	 * @hints	ALWAYS use a LIMIT 1 OFFSET 0 with all queries using this function to avoid situations that will return more than 1 rows and will raise ERROR with this function.
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @return ARRAY (asociative) of results		:: Returns just a SINGLE ROW as: array('column1' => 'val1', 'column2' => null, ... 'column-n' => 't')
	 */
	public function read_asdata($queryval, $params_or_title='') {
		//--
		return SmartMysqliDb::read_asdata($queryval, $params_or_title, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * MySQL Query :: Write.
	 * This function is intended to be used for write type queries: BEGIN (TRANSACTION) ; COMMIT ; ROLLBACK ; INSERT ; INSERT IGNORE ; REPLACE ; UPDATE ; CREATE SCHEMAS ; CALLING STORED PROCEDURES ...
	 *
	 * @param STRING $queryval						:: the query
	 * @param STRING $params_or_title 				:: *optional* array of parameters or query title for easy debugging
	 * @return ARRAY 								:: [ 0 => 'control-message', 1 => #affected-rows, 2 => #last-inserted-id(autoincrement)|0|null ]
	 */
	public function write_data($queryval, $params_or_title='') {
		//--
		return SmartMysqliDb::write_data($queryval, $params_or_title, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * Create Escaped Write SQL Statements from Data - to be used with MySQL for: INSERT ; UPDATE ; IN-SELECT
	 * Can be used with: write_data() to build INSERT / UPDATE queries from an associative array
	 * or can be used with read_data(), read_adata(), read_asdata(), count_data() to build IN-SELECT queries from a non-associative array
	 *
	 * @param ARRAY $arrdata 						:: associative array: array of form data as $arr=array(); $arr['field1'] = 'a string'; $arr['field2'] = 100; | non-associative array $arr[] = 'some value'; $arr[] = 'other-value', ...
	 * @param ENUM $mode							:: mode: 'insert' | 'update' | 'in-select'
	 * @return STRING								:: The SQL partial Statement
	 */
	public function prepare_statement($arrdata, $mode) {
		//--
		return SmartMysqliDb::prepare_statement($arrdata, $mode, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * Create Escaped SQL Statements from Parameters and Array of Data by replacing ? (question marks)
	 * This can be used for a full SQL statement or just for a part.
	 * The statement must not contain any Single Quotes to prevent SQL injections which are unpredictable if mixing several statements at once !
	 *
	 * @param STRING $query							:: SQL Statement to process like '   WHERE ("id" = ?)'
	 * @param ARRAY $arrdata 						:: The non-associative array as of: $arr=array('a');
	 * @return STRING								:: The SQL processed (partial/full) Statement
	 */
	public function prepare_param_query($query, $arrdata) {
		//--
		return SmartMysqliDb::prepare_param_query($query, $arrdata, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * Get A UNIQUE (SAFE) ID for DB Tables
	 *
	 * @param ENUM $y_mode 							:: mode: uid10str | uid10num | uid10seq | uid12seq | uid32 | uid34 | uid36 | uid45
	 * @param STRING $y_field_name 					:: the field name
	 * @param STRING $y_table_name 					:: the table name
	 * @return STRING 								:: the generated Unique ID
	 *
	 */
	public function new_safe_id($y_mode, $y_id_field, $y_table_name) {
		//--
		return SmartMysqliDb::new_safe_id($y_mode, $y_id_field, $y_table_name, $this->connection);
		//--
	} //END FUNCTION


	/**
	 * Check and Return the Server Version
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public function check_server_version($y_revalidate=false) {
		//--
		return SmartMysqliDb::check_server_version($this->connection, $y_revalidate);
		//--
	} //END FUNCTION


	//==================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
