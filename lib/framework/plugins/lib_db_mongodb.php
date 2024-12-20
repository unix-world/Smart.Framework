<?php
// [LIB - Smart.Framework / Plugins / MongoDB Database Client]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//======================================================
// Smart-Framework - MongoDB Client
// DEPENDS:
//	* Smart::
//	* SmartEnvironment
//	* SmartComponents:: (optional)
// DEPENDS-EXT: PHP MongoDB / PECL (v.1.1.0 or later)
//======================================================

// [PHP8]


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class Smart MongoDB Client (for PHP MongoDB extension v.1.1.0 or later)
 * Tested and Stable on MongoDB Server versions: 4.4 / 5.0 / 5.1 / 5.2 / 6.0 / 7.0 / 8.0
 *
 * <code>
 *
 * // sample mongo config
 * $cfg_mongo = array();
 * $cfg_mongo['type'] 		= 'mongo-standalone'; 				// mongodb server(s) type: 'mongo-standalone' | 'mongo-cluster' (sharding) | 'mongo-replica-set:My-Replica' (replica set)
 * $cfg_mongo['server-host']	= '127.0.0.1';							// mongodb host or comma separed list of multiple hosts
 * $cfg_mongo['server-port']	= '27017';									// mongodb port
 * $cfg_mongo['dbname']		= 'smart_framework';			// mongodb database
 * $cfg_mongo['username'] 		= '';											// mongodb username
 * $cfg_mongo['password'] 		= '';											// mongodb Base64-Encoded password
 * $cfg_mongo['timeout']		= 5;												// mongodb connect timeout in seconds
 * $cfg_mongo['slowtime']		= 0.0035;									// 0.0025 .. 0.0090 slow query time (for debugging)
 *
 * // sample mongo connect
 * $mongo = new SmartMongoDb($cfg_mongo);
 *
 * </code>
 *
 * @usage 		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints 		Important: MongoDB database specifies that max BSON document size is 16 megabytes and supports no more than 100 levels of nesting, thus this limit cannot be exceeded from PHP side when creating new mongodb documents: https://docs.mongodb.com/manual/reference/limits/ ; To store documents larger than the maximum size, MongoDB provides the GridFS API
 *
 * @access 		PUBLIC
 * @depends 	extensions: PHP MongoDB ; classes: Smart, SmartEnvironment, SmartComponents (optional)
 * @version 	v.20241220
 * @package 	Plugins:Database:MongoDB
 *
 * @throws 		Exception : Depending how this class it is constructed it may throw Exception or Raise Fatal Error
 *
 */
final class SmartMongoDb { // !!! Use no paranthesis after magic methods doc to avoid break the comments !!!

	// ->


	/** @var string */
	private $server;
	private $srvver;
	private $extver;

	/** @var string */
	private $db;

	/** @var integer+ */
	private $timeout;

	/** @var resource */
	private $mongodbclient;

	/** @var object */
	private $mongodb;

	/** @var object */
	private $collection;

	/** @var float */
	private $slow_time = 0.0035;

	/** @var boolean */
	private $fatal_err = true;

	/** @var string */
	private $connex_typ = '';

	/** @var string */
	private $connex_key = '';

	/** @var boolean */
	private $connected = false;

	private const minVersionServer    = '4.4.16'; // min mongodb server version supported ; prev supported version was 3.4.22 (that supports `upsert` and `facet aggregation with fts`) but was too old
	private const minVersionExtension = '1.5.5';  // prev supported version was 1.3.0 that supports `facet aggregation with fts` is 1.3


	//======================================================
	/**
	 * Class constructor
	 *
	 * @throws 	Exception 	Depending how this class it is constructed it may throw Exception or Raise Fatal Error
	 *
	 * @param 	ARRAY 		$y_configs_arr 		:: *Optional* ; The Array of Configuration parameters ; Default is Empty Array, case which will get the configuration from config.php ; if custom, the ARRAY STRUCTURE should be identical with the default config.php: $configs['mongodb'].
	 * @param 	BOOLEAN 	$y_fatal_err 		:: *Optional* ; Set if Errors handling mode ; Default is TRUE ; if set to FALSE will throw Exception instead of Raise a Fatal Error
	 *
	 */
	public function __construct(array $y_configs_arr=[], bool $y_fatal_err=true) {

		//--
		$this->fatal_err = (bool) $y_fatal_err;
		//--

		//--
		$this->extver = (string) phpversion('mongodb');
		//--
		if(version_compare((string)$this->extver, (string)self::minVersionExtension) < 0) {
			$this->error('[INIT]', 'PHP MongoDB Extension', 'CHECK PHP MongoDB Version', 'This version of MongoDB Client Library needs MongoDB PHP Extension v.'.self::minVersionExtension.' or later. The current version is: '.$this->extver);
			return;
		} //end if
		//--

		//--
		$y_configs_arr = (array) $y_configs_arr;
		//--
		if(Smart::array_size($y_configs_arr) <= 0) { // if not from constructor, try to use the default
			$y_configs_arr = (array) Smart::get_from_config('mongodb', 'array');
		} //end if
		//--
		$type 		= '';
		$db 		= '';
		$host 		= '';
		$port 		= '';
		$timeout 	= '';
		$username 	= '';
		$password 	= '';
		$authmet 	= '';
		$timeslow 	= 0;
	//	$transact 	= '';
		//--
		if(Smart::array_size($y_configs_arr) > 0) {
			$type 		= (string) ($y_configs_arr['type']        ?? null);
			$db 		= (string) ($y_configs_arr['dbname']      ?? null);
			$host 		= (string) ($y_configs_arr['server-host'] ?? null);
			$port 		= (string) ($y_configs_arr['server-port'] ?? null);
			$timeout 	= (string) ($y_configs_arr['timeout']     ?? null);
			$username 	= (string) ($y_configs_arr['username']    ?? null);
			$password 	= (string) ($y_configs_arr['password']    ?? null);
			$authmet 	= (string) ($y_configs_arr['authmet']     ?? null);
			$timeslow 	= (float)  ($y_configs_arr['slowtime']    ?? null);
		//	$transact 	= (string) ($y_configs_arr['transact']    ?? null); // reserved for future usage (only MongoDB v.4+ supports transactions ...)
		} else {
			$this->error('[CHECK-CONFIGS]', 'MongoDB Configuration Init', 'CHECK Connection Config', 'Empty Configuration');
			return;
		} //end if
		//--
		$this->connex_typ = (string) $type;
		//--
		if((string)$password != '') {
			$password = (string) base64_decode((string)$password);
		} //end if
		//--
		if(((string)$host == '') OR ((string)$port == '') OR ((string)$db == '') OR ((string)$timeout == '')) {
			$this->error('[CHECK-CONFIGS]', 'MongoDB Configuration Init', 'CHECK Connection Params: '.$host.':'.$port.'@'.$db, 'Some Required Parameters are Empty');
			return;
		} //end if
		//--
		$this->srvver = '';
		//--
		if(strpos((string)$host, ',') !== false) {
			$tmp_arr_hosts = (array) explode(',', (string)$host);
			$arr_hosts = [];
			$host = '';
			for($i=0; $i<Smart::array_size($tmp_arr_hosts); $i++) {
				$tmp_arr_hosts[$i] = (string) trim((string)$tmp_arr_hosts[$i]);
				if((string)$tmp_arr_hosts[$i] != '') {
					$arr_hosts[(string)$tmp_arr_hosts[$i]] = (string) $tmp_arr_hosts[$i];
				} //end if
			} //end if
			$tmp_arr_hosts = array();
			$arr_hosts = (array) array_values((array)$arr_hosts);
			if(Smart::array_size($arr_hosts) <= 0) {
				$this->error('[CHECK-CONFIGS]', 'MongoDB Configuration Init', 'CHECK Connection Param Multi-Hosts: '.$host.':'.$port.'@'.$db, 'Invalid Multi-Hosts Parameter');
				return;
			} //end if
			$host = (string) implode(',', (array)$arr_hosts);
			for($i=0; $i<Smart::array_size($arr_hosts); $i++) {
				$arr_hosts[$i] = (string) $arr_hosts[$i].':'.(int)$port;
			} //end if
			$this->server = (string) implode(',', (array)$arr_hosts);
		} else {
			$this->server = (string) $host.':'.(int)$port;
		} //end if else
		//--
		$this->db = (string) $db;
		//--
		$this->timeout = Smart::format_number_int($timeout, '+');
		if($this->timeout < 1) {
			$this->timeout = 1;
		} //end if
		if($this->timeout > 60) {
			$this->timeout = 60;
		} //end if
		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			if($this->fatal_err === true) {
				$txt_conn = 'FATAL ERRORS';
			} else {
				$txt_conn = 'IGNORED BUT LOGGED AS WARNINGS';
			} //end if else
			//--
			SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
				'type' => 'metainfo',
				'data' => 'MongoDB App Connector Version: '.SMART_FRAMEWORK_VERSION.' // Connection Errors are: '.$txt_conn
			]);
			//--
			if((float)$timeslow > 0) {
				$this->slow_time = (float) $timeslow;
			} else {
				$this->slow_time = 0.0035; // default slow time for mongodb
			} //end if
			if($this->slow_time < 0.0000001) {
				$this->slow_time = 0.0000001;
			} elseif($this->slow_time > 0.9999999) {
				$this->slow_time = 0.9999999;
			} //end if
			//--
			SmartEnvironment::setDebugMsg('db', 'mongodb|slow-time', number_format($this->slow_time, 7, '.', ''), '=');
			SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
				'type' => 'metainfo',
				'data' => 'Fast Query Reference Time < '.number_format($this->slow_time, 7, '.', '').' seconds'
			]);
			//--
		} //end if
		//--

		//--
		$this->connex_key = (string) $this->server.'@'.$this->db.'#'.$username;
		//--

		//--
		if(!class_exists('\\MongoDB\\Driver\\Manager')) {
			$this->error((string)$this->connex_key, 'MongoDB Driver', 'PHP MongoDB Driver Manager is not available', '');
			return;
		} //end if
		//--
		if(!class_exists('\\MongoDB\\Driver\\Command')) {
			$this->error((string)$this->connex_key, 'MongoDB Driver', 'PHP MongoDB Driver Command is not available', '');
			return;
		} //end if
		//--
		if(!class_exists('\\MongoDB\\Driver\\Query')) {
			$this->error((string)$this->connex_key, 'MongoDB Driver', 'PHP MongoDB Driver Query is not available', '');
			return;
		} //end if
		//--
		if(!class_exists('\\MongoDB\\Driver\\BulkWrite')) {
			$this->error((string)$this->connex_key, 'MongoDB Driver', 'PHP MongoDB Driver BulkWrite is not available', '');
			return;
		} //end if
		//--
		if(!class_exists('\\MongoDB\\Driver\\WriteResult')) {
			$this->error((string)$this->connex_key, 'MongoDB Driver', 'PHP MongoDB Driver WriteResult is not available', '');
			return;
		} //end if
		//--

		//--
		$this->connect((string)$type, (string)$username, (string)$password, (string)$authmet);
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * A replacement for the default MongoDB object ID, will generate a 32 characters very unique UUID
	 *
	 * @return 	STRING						:: UUID (base36)
	 */
	public function assign_uuid() : string {

		//--
		if((string)$this->connex_typ == 'mongo-cluster') { // {{{SYNC-MONGODB-CONN-CLUSTER}}}
			$uuid = (string) Smart::uuid_34();
		} else {
			$uuid = (string) Smart::uuid_32();
		} //end if else
		//--
		return (string) $uuid;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the MongoDB Extension version
	 *
	 * @return 	STRING						:: MongoDB extension version
	 */
	public function get_ext_version() : string {

		//--
		return (string) $this->extver;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the MongoDB server version
	 *
	 * @return 	STRING						:: MongoDB version
	 */
	public function get_server_version() : string {

		//--
		if((string)$this->srvver == '') {
			//--
			$arr_build_info = $this->igcommand(['buildinfo' => true]); // fix: ignore command, not command, no need to try/catch here
			//--
			if(is_array($arr_build_info)) {
				if(is_array(($arr_build_info[0] ?? null))) {
					$this->srvver = (string) trim((string)($arr_build_info[0]['version'] ?? null));
				} //end if
			} //end if
			//--
			$arr_build_info = null;
			//--
		} //end if
		//--
		if((string)$this->srvver == '') {
			$this->srvver = '0.0'; // avoid requery
		} //end if
		//--

		//--
		return (string) $this->srvver;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Set Fatal Error Mode to TRUE/FALSE
	 *
	 * @return VOID
	 */
	public function setFatalErrMode(bool $is_fatal) : void {
		//--
		$this->fatal_err = (bool) $is_fatal;
		//--
		return;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Laminas/DB: Get Fatal Error TRUE/FALSE
	 *
	 * @return TRUE/FALSE
	 */
	public function getFatalErrMode() : bool {
		//--
		return (bool) $this->fatal_err;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the MongoDB ObjectId by Id
	 *
	 * @return 	MIXED						:: return a MongoDB ObjectId as OBJECT or STRING if invalid Id
	 */
	public function getObjectId(string $id) { // : MIXED
		//--
		$id = (string) trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$objMongoId = (string) $id;
		$err = '';
		//--
		if(class_exists('\\MongoDB\\BSON\\ObjectId')) {
			try {
				$objMongoId = new \MongoDB\BSON\ObjectId((string)$id);
			} catch(\Exception $e) {
				$err = (string) $e->getMessage(); // this must be non-fatal as if malformed string is sent this class will throw
			} //end if else
		} else {
			$err = 'MongoDB ObjectId Class not found ...';
		} //end if else
		if((string)$err != '') {
			Smart::log_notice('#MongoDB# :: Get ObjectID: '.$err);
		} //end if
		//--
		return $objMongoId; // mixed: Object or String
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Get the MongoDB FTS (Full Text Search) Dictionary by Two Letter language code (ISO 639-1)
	 *
	 * @return 	STRING						:: dictionary name (ex: 'english' - if available or 'none' - if n/a)
	 */
	public function getFtsDictionaryByLang(string $lang) : string {
		//--
		$dictionary = '';
		//--
		$lang = (string) strtolower((string)$lang);
		//--
		switch((string)$lang) { // https://docs.mongodb.com/manual/reference/text-search-languages/
			case 'en':
				$dictionary = 'english';
				break;
			case 'de':
				$dictionary = 'german';
				break;
			case 'fr':
				$dictionary = 'french';
				break;
			case 'es':
				$dictionary = 'spanish';
				break;
			case 'pt':
				$dictionary = 'portuguese';
				break;
			case 'ro':
				$dictionary = 'romanian';
				break;
			case 'it':
				$dictionary = 'italian';
				break;
			case 'nl':
				$dictionary = 'dutch';
				break;
			case 'da':
				$dictionary = 'danish';
				break;
			case 'nb':
				$dictionary = 'norwegian';
				break;
			case 'fi':
				$dictionary = 'finnish';
				break;
			case 'sv':
				$dictionary = 'swedish';
				break;
			case 'ru':
				$dictionary = 'russian';
				break;
			case 'hu':
				$dictionary = 'hungarian';
				break;
			case 'tr':
				$dictionary = 'turkish';
				break;
			default:
				$dictionary = 'none'; // text search uses simple tokenization with no list of stop words and no stemming
		} //end switch
		//--
		return (string) $dictionary;
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * custom usage, test if the search expression is a FTS search phrase
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public function isFtsSearchPhrase(?string $text) : bool {
		//--
		$text = (string) trim((string)$text);
		//--
		return (bool) ((strpos((string)$text, '"') === 0) && ((string)substr((string)$text, -1, 1) == '"'));
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * custom usage, prepare the FTS keywords from a FTS search phrase
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public function prepareFtsSearchKeywords(?string $text) : string {

		//--
		$is_phrase = (bool) $this->isFtsSearchPhrase((string)$text);
		//--
		$text = (string) $this->escapeFtsText((string)$text);
		$text = (string) trim((string)$text); // need another trim after the above method ...
		//--

		//--
		if($is_phrase !== true) {
			//--
			$arr = (array) explode(' ', (string)$text);
			$text = [];
			for($i=0; $i<count($arr); $i++) {
				$arr[$i] = (string) trim((string)$arr[$i]);
				if((string)$arr[$i] != '') {
					$text[] = '"'.$arr[$i].'"';
				} //end if
			} //end for
			$arr = null;
			//--
			if((int)Smart::array_size($text) > 0) {
				$text = (string) implode(' ', (array)$text);
			} else {
				$text = '';
			} //end if
			//--
		} else {
			//--
			if((string)$text != '') {
				//--
				$text = '"'.$text.'"';
			} else {
				$text = '';
			} //end if else
			//--
		} //end if else
		//--

		//--
		return (string) trim((string)$text);
		//--

 	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Escape FTS text, to be used in a safe mode with mongo Full Text Search
	 *
	 * @return 	STRING						:: FTS escaped
	 */
	public function escapeFtsText(?string $text) : string {

		//--
		$text = (string) SmartUnicode::fix_charset((string)$text); // fix
		//--

		//--
		return (string) strtr((string)$text, [ '"' => '', '-' => ' ' ]); // " and - are special FTS patterns ; " encloses a phrase ; - negates term ; - can also can separe words # https://docs.mongodb.com/manual/reference/operator/query/text/
		//--

 	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Escape Regex text, to be used in a safe mode with mongo $regex => 'text...'
	 *
	 * @return 	STRING						:: Regex escaped
	 */
	public function escapeRegexText(?string $text) : string {

		//--
		$text = (string) SmartUnicode::fix_charset((string)$text); // fix
		//--

		//--
		return (string) preg_quote((string)$text, '/');
		//--

 	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Test if a command output is OK compliant (will check if result[0][ok] == 1)
	 * Notice: not all MongoDB commands will return this standard answer, but majority
	 *
	 * @param MIXED result							:: result output from a mongodb command
	 *
	 * @return BOOLEAN								:: TRUE / FALSE
	 */
	public function is_command_ok($result) : bool {

		//--
		$is_ok = false;
		//--
		if(is_array($result)) {
			if(array_key_exists(0, $result)) {
				if(is_array($result[0])) {
					if((int)($result[0]['ok'] ?? null) == 1) {
						$is_ok = true;
					} //end if
				} //end if
			} //end if
		} //end if
		//--

		//--
		return (bool) $is_ok;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * This is the Magic Method (Call) that maps the PHP class extra methods to MongoDB methods.
	 * It have variadic parameters mapped to MongoDB sub-calls.
	 *
	 * @throws 	Exception 	Depending how this class it is constructed it may throw Exception or Raise Fatal Error
	 *
	 * @magic
	 *
	 * @method MIXED		count(STRING $strCollection, ARRAY $arrQuery)												:: count documents in a collection
	 * @method MIXED		find(STRING $strCollection, ARRAY $arrQuery, ARRAY $arrProjFields, ARRAY *$arrOptions)		:: find single or multiple documents in a collection with optional filter criteria / limit
	 * @method MIXED		findone(STRING $strCollection, ARRAY $arrQuery, ARRAY $arrProjFields, ARRAY *$arrOptions)	:: find single document in a collection with optional filter criteria / limit
	 * @method MIXED		bulkinsert(STRING $strCollection, ARRAY $arrMultiDocs)										:: add multiple documents to a collection
	 * @method MIXED		insert(STRING $strCollection, ARRAY $arrDoc)												:: add single document to a collection
	 * @method MIXED		upsert(STRING $strCollection, ARRAY $arrFilter, MIXED $strUpdOpOrArrUpd, ARRAY *$arrUpd)	:: insert single or modify single or multi documents in a collection that are matching the filter criteria
	 * @method MIXED		update(STRING $strCollection, ARRAY $arrFilter, MIXED $strUpdOpOrArrUpd, ARRAY *$arrUpd)	:: modify single or many documents in a collection that are matching the filter criteria
	 * @method MIXED		delete(STRING $strCollection, ARRAY $arrFilter)												:: delete single or many documents from a collection that are matching the filter criteria
	 * @method MIXED		command($arrCmd)																			:: run a command over database like: aggregate, distinct, mapReduce, create Collection, drop Collection, ...
	 * @method MIXED		igcommand($arrCmd)																			:: run a command over database and ignore if error ; in the case of throw error will ignore it and will not stop execution ; will return the errors instead of result like: create Collection which may throw errors if collection already exists, drop Collection, similar if does not exists
	 *
	 * <code>
	 * // Sample Count Records
	 * $count = $mongo->count(
	 * 		'myTestCollection',
	 * 		[ 'name' => [ '$eq' => 'Test:!' ] ] // filter
	 * );
	 * var_dump($count);
	 * </code>
	 *
	 * <code>
	 * // Sample Find One
	 * $find = $mongo->findone(
	 * 		'myTestCollection',
	 * 		[ 'cost' => 7 ] // filter
	 * );
	 * var_dump($find);
	 * </code>
	 *
	 * <code>
	 * // Sample Find Many
	 * $mfind = $mongo->find(
	 * 		'myTestCollection',
	 * 		[ 'name' => 'John' ], // filter
	 * 		[ // projection
	 * 			'name',
	 * 			'description'
	 * 		],
	 * 		[
	 * 			'limit' => 10, // limit
	 * 			'skip' => 0 // offset
	 * 		]
	 * );
	 * var_dump($mfind);
	 * </code>
	 *
	 * <code>
	 * // Sample Insert
	 * $doc = [];
	 * $doc['id']  = $mongo->assign_uuid();
	 * $doc['name'] = 'My Name';
	 * $doc['description'] = 'Some description goes here ...';
	 * $insert = $mongo->insert('myTestCollection', (array)$doc);
	 * $doc = [];
	 * var_dump($insert);
	 * </code>
	 *
	 * <code>
	 * // Sample Bulk Insert (10 Documents)
	 * $docs = array();
	 * for($i=0; $i<10; $i++) {
	 * 	$docs[] = [
	 * 		'id'  => $mongo->assign_uuid(),
	 * 		'name' => 'Document #'.$i,
	 * 		'cost' => ($i+1),
	 * 		'data' => [
	 * 			'description' => 'This is the document #'.$i,
	 * 			'date_time' => (string) date('Y-m-d H:i:s'),
	 * 			'rating' => Smart::random_number(1,9) / 100
	 * 		]
	 * 	];
	 * } //end for
	 * $insert = $mongo->bulkinsert('myTestCollection', (array)$docs);
	 * $docs = array();
	 * var_dump($insert);
	 * </code>
	 *
	 * <code>
	 * // Sample Complete Update (Replace) ; it will completely replace the document with a new one, except the _id UID key, by _id ; works only with one document ; if more documents match the criteria, only the first one will be updated
	 * $doc = [];
	 * $doc['name'] = 'My New Name';
	 * $doc['description'] = 'Some description goes here ...';
	 * $update = $mongo->update(
	 * 		'myTestCollection',
	 * 		[ '_id' => 'XXXXXXXXXX-XXXXXXXXXX-XXXXXXXXXX' ], 			// filter (update only this)
	 * 		[ 0 => (array) $doc ]										// replace array
	 * 	);
	 * $doc = [];
	 * var_dump($update);
	 * </code>
	 *
	 * <code>
	 * // Sample Partial Update, will update one or many: name and description ; if other keys exist in the stored document they remain unchanged
	 * $doc = [];
	 * $doc['name'] = 'My New Name';
	 * $doc['description'] = 'Some description goes here ...';
	 * $update = $mongo->update(
	 * 		'myTestCollection',
	 * 		[ 'id' => 'XXXXXXXXXX-XXXXXXXXXX-XXXXXXXXXX' ], 			// filter (update only this)
	 * 		'$set', 													// increment operation
	 * 		(array) $doc												// update array
	 * 	);
	 * $doc = [];
	 * var_dump($update);
	 * </code>
	 *
	 * <code>
	 * // Sample Upsert
	 * $doc = [];
	 * $docID  = 'XXXXXXXXXX-XXXXXXXXXX-XXXXXXXXXX'; // comes from $mongo->assign_uuid();
	 * $doc['name'] = 'My Newest Name';
	 * $doc['description'] = 'Some description goes here ...';
	 * try {
	 * 		$upsert = $mongo->upsert(
	 * 			'myTestCollection',
	 * 			[ 'id' => $docID ], 								// filter (update only this)
	 * 			[ // also the $mongo->update() can use this style of associative array
	 * 				'$setOnInsert' 	=> (array) [ 'id' => $docID ], 						// just on insert
	 * 				'$set' 			=> (array) $doc, 									// update array
	 * 				'$addToSet' 	=> (array) [ 'updated' => date('Y-m-d H:i:s') ] 	// update array #2 using $addToSet (can be also: $push or $inc, ...)
	 * 			]
	 * 		);
	 * } catch(Exception $err) {
	 * 		// if upsert goes wrong ...
	 * }
	 * $doc = [];
	 * var_dump($upsert);
	 * </code>
	 *
	 * <code>
	 * // Sample Delete
	 * $delete = $mongo->delete(
	 * 		'myTestCollection',
	 * 		[ 'name' => 'An Item', 'cost' => 8 ] // filter
	 * );
	 * var_dump($delete);
	 * </code>
	 *
	 * <code>
	 * // Search Distinct with Filter
	 * $filter = $mongo->command(
	 * 		[
	 * 			'distinct' => 'myTestCollection',
	 * 			'key' => 'cost',
	 * 			'query' => [ 'cost' => [ '$gte' => 5 ] ] // find distinct where cost > 5
	 * 		]
	 * );
	 * if(!$mongo->is_command_ok($filter) {
	 * 		// command failed ...
	 * }
	 * var_dump($filter);
	 * </code>
	 *
	 * <code>
	 * // ... see more usage samples: modules/mod-samples/libs/TestUnitMongoDB.php
	 * </code>
	 *
	 */
	public function __call(string $method, array $args) {

		//--
		$this->collection = ''; // initialize and clear
		//--

		//--
		$method = (string) $method;
		$args = (array) $args;
		//--
		if(SmartEnvironment::ifDebug()) {
			$time_start = microtime(true);
		} //end if
		//--

		//--
		if(!is_object($this->mongodbclient)) {
			$this->error((string)$this->connex_key, 'MongoDB Initialize', 'MongoDB->INIT-MANAGER() :: '.$this->connex_key.'/'.$this->collection, 'ERROR: MongoDB Manager Object is null ...');
			return null;
		} //end if
		//--

		//--
		$obj = null;
		$qry = array();
		$opts = array();
		$drows = 0;
		$dcmd = 'nosql';
		$dmethod = (string) $method;
		$skipdbg = false;
		//--
		switch((string)$method) {
			//-- collection methods
			case 'count': // ARGS [ strCollection, arrQuery ]
				//--
				$dcmd = 'count';
				//--
				$this->collection = (string) trim((string)($args[0] ?? null)); // strCollection
				if((string)trim((string)$this->collection) == '') {
					$this->error((string)$this->connex_key, 'MongoDB Count', 'MongoDB->'.$method.'()', 'ERROR: Empty Collection name ...', $args);
					return 0;
				} //end if
				//--
				$qry = (array) ($args[1] ?? null); // arrQuery
				//--
				if(Smart::array_size($qry) <= 0) { // fix for: BSON field 'count.query' is the wrong type 'array', expected type 'object' when query array is empty
					$command = new \MongoDB\Driver\Command([
						'count' => (string) $this->collection
					]);
				} else {
					$command = new \MongoDB\Driver\Command([
						'count' => (string) $this->collection,
						'query' => (array) $qry
					]);
				} //end if else
				if(!is_object($command)) {
					$this->error((string)$this->connex_key, 'MongoDB Count', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Command Object is null ...', $args);
					return 0;
				} //end if
				//--
				try {
					$cursor = $this->mongodbclient->executeCommand($this->db, $command);
				} catch(Exception $err) {
					$this->error((string)$this->connex_key, 'MongoDB Count Execute', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: '.$err->getMessage(), $args);
					return 0;
				} //end try
				if(!is_object($cursor)) {
					$this->error((string)$this->connex_key, 'MongoDB Count Cursor', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Cursor Object is null ...', $args);
					return 0;
				} //end if
				$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
				//print_r($cursor->toArray()); die();
				$obj = 0;
				if(is_object($cursor)) {
					$tmp_obj = (array) $cursor->toArray();
					if(is_array($tmp_obj[0])) {
						$tmp_obj = (array) $tmp_obj[0];
						if(array_key_exists('n', (array)$tmp_obj)) {
							if((int)($tmp_obj['ok'] ?? null) == 1) {
								$obj = (int) $tmp_obj['n'];
								$drows = (int) $obj;
							} //end if
						} //end if
					} //end if
					$tmp_obj = array(); // free mem
				} //end if object
				//--
				unset($cursor);
				unset($command);
				//--
				break;
			//--
			case 'find': 	// ARGS [ strCollection, arrQuery, arrProjFields, arrOptions ]
			case 'findone': // ARGS [ strCollection, arrQuery, arrProjFields, arrOptions ]
				//--
				$dcmd = 'read';
				//--
				$this->collection = (string) trim((string)($args[0] ?? null)); // strCollection
				if((string)trim((string)$this->collection) == '') {
					$this->error((string)$this->connex_key, 'MongoDB Read', 'MongoDB->'.$method.'()', 'ERROR: Empty Collection name ...', $args);
					return array();
				} //end if
				//--
				$qry = (array) ($args[1] ?? null); // arrQuery
				//--
				if(array_key_exists(3, $args) AND (is_array($args[3]))) {
					$opts = (array) $args[3]; // arrOptions
				} //end if
				//-- fix: find one must have limit 1, offset 0
				if((string)$method == 'findone') {
					$opts['limit'] = 1; // limit
					$opts['skip'] = 0; // offset
				} //end if
				//-- fix: select just particular fields
				$opts['projection'] = array(); // arrProjFields
				if(array_key_exists(2, $args) AND (Smart::array_size($args[2]) > 0)) {
					if(Smart::array_type_test($args[2]) === 2) { // associative
						foreach((array)$args[2] as $key => $val) {
							$key = (string) trim((string)$key);
							if((string)$key != '') {
								if(\is_array($val)) {
									$opts['projection'][(string)$key] = (array) $val;
								} else {
									$opts['projection'][(string)$key] = 1; // must be 1 here, as of MongoDB 5.0
								} //end if else
							} //end if
						} //end foreach
					} elseif(Smart::array_type_test($args[2]) === 1) { // non-associative
						for($i=0; $i<Smart::array_size($args[2]); $i++) {
							$key = (string) trim((string)$args[2][$i]);
							if((string)$key != '') {
								$opts['projection'][(string)$key] = 1; // must be 1 here
							} //end if
						} //end for
					} //end if else
				} //end if
				//print_r($opts); die();
				//--
				$query = new \MongoDB\Driver\Query( // max 2 parameters
					(array) $qry, // query (empty: select all)
					(array) $opts // options
				);
				//print_r($query); die();
				if(!is_object($query)) {
					$this->error((string)$this->connex_key, 'MongoDB Read', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Query Object is null ...', $args);
					return array();
				} //end if
				//--
				try {
					$cursor = $this->mongodbclient->executeQuery($this->db.'.'.$this->collection, $query);
				} catch(Exception $err) {
					$this->error((string)$this->connex_key, 'MongoDB Read Execute', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: '.$err->getMessage(), $args);
					return array();
				} //end try
				if(!is_object($cursor)) {
					$this->error((string)$this->connex_key, 'MongoDB Read Cursor', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Cursor Object is null ...', $args);
					return array();
				} //end if
				$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
				//print_r($cursor->toArray()); die();
				$obj = array();
				if(is_object($cursor)) {
					$obj = Smart::json_decode(
						(string) Smart::json_encode(
							(array)$cursor->toArray(),
							false, // no pretty print
							true, // unescaped unicode
							false // html safe
						),
						true // return array
					); // mixed, normalize via json:encode/decode
					if(!is_array($obj)) {
						$obj = array();
					} //end if
					$drows = (int) Smart::array_size($obj);
					if((string)$method == 'findone') {
						if(array_key_exists(0, $obj) AND is_array($obj[0])) {
							$obj = (array) $obj[0];
							$drows = 1;
						} else {
							$obj = array();
							$drows = 0;
						} //end if
					} //end if
				} //end if object
				//--
				unset($cursor);
				unset($query);
				//--
				//print_r($obj); die();
				//--
				break;
			//--
			case 'bulkinsert': 	// ARGS [ strCollection, arrMultiDocs ] ; can do multiple inserts
			case 'insert': 		// ARGS [ strCollection, arrDoc ] ; can do just single insert
			case 'upsert': 		// ARGS [ strCollection, arrFilter, strUpsOp/arrUps, arrUps* ] ; can do just single insert or single/multi update
			case 'update': 		// ARGS [ strCollection, arrFilter, strUpdOp/arrUpd, arrUpd* ] ; can do single or multi update
				//--
				$dcmd = 'write';
				//--
				$this->collection = (string) trim((string)($args[0] ?? null)); // strCollection
				if((string)trim((string)$this->collection) == '') {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'()', 'ERROR: Empty Collection name ...', $args);
					return array();
				} //end if
				//--
				$write = new \MongoDB\Driver\BulkWrite();
				if(!is_object($write)) {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Write Object is null ...', $args);
					return array();
				} //end if
				//--
				if(!array_key_exists(1, $args)) {
					$args[1] = [];
				} //end if
				//--
				$num_docs = 0;
				//--
				if((string)$method == 'bulkinsert') {
					if(Smart::array_type_test($args[1]) === 1) { // 1: non-associative array of multi docs
						$qry = 'bulkinsert['.Smart::array_size($args[1]).']';
						$opts = [];
						for($i=0; $i<Smart::array_size($args[1]); $i++) {
							if(Smart::array_size($args[1][$i]) > 0) {
								$write->insert(
									(array) $args[1][$i] // doc
								);
								$num_docs++;
							} else {
								$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Multi-Document #'.$i.' is empty or not array ...', $args);
								return array();
								break;
							} //end if
						} //end for
					} else {
						$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Invalid Multi-Document structure ...', $args);
						return array();
					} //end if else
				} elseif((string)$method == 'insert') {
					$qry = 'insert';
					$opts = [];
					if(Smart::array_size($args[1]) > 0) {
						$write->insert(
							(array) $args[1] // doc
						);
						$num_docs++;
					} else {
						$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Document is empty or not array ...', $args);
						return array();
						break;
					} //end if
				} elseif(((string)$method == 'update') OR ((string)$method == 'upsert')) {
					if(!is_array($args[1])) {
						$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Invalid Filter provided ...', $args);
						return array();
					} //end if
					$qry = (string) $method.':'.(is_array($args[2]) ? (string)implode(',', array_keys($args[2])) : (string)$args[2]);
					if((string)$method == 'upsert') {
						$opts = [ // update options for upsert
							'multi' 	=> true, // update all the matching documents
							'upsert' 	=> true // if filter does not match an existing document, do insert a single document
						];
					} else { // update
						$opts = [ // update options
							'multi' 	=> true, // update all the matching documents
							'upsert' 	=> false // if filter does not match an existing document, do not insert a single document and do no update
						];
					} //end if else
					if((Smart::array_size($args[2]) == 1) AND (Smart::array_type_test($args[2]) == 1) AND (Smart::array_size($args[2][0]) > 0) AND (Smart::array_type_test($args[2][0]) == 2)) { // expects non-associative array with first key [0] as an associative array and the 3rd param as empty
						$opts['multi'] = false; // this is a requirement for this case to avoid Exception: `Replacement document conflicts with true "multi" option`
						if(!empty($args[3])) {
							$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Too many parameters for Update Replace ...', $args);
							return array();
							break;
						} //end if
						if(array_key_exists('_id', (array)$args[2][0])) {
							$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: The Update Replace document cannot contain the special UID key: `_id` ...', $args);
							return array();
							break;
						} //end if
						$write->update( // completely replaces the $doc, except the _id
							(array) $args[1], 									// filter
							(array) $args[2][0], 								// must be in format: (array)$doc as $args[2] is expected to be: [ 0 => (array)$doc ]
							(array) $opts										// options
						);
						$num_docs++;
					} elseif((Smart::array_size($args[2]) > 0) AND (Smart::array_type_test($args[2]) == 2)) { // expects associative array and the 3rd param as empty
						if(!empty($args[3])) {
							$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Document must be combined with Operation if the associative array is passed as operation ...', $args);
							return array();
							break;
						} //end if
						$write->update( // only update fields from $doc
							(array) $args[1], 									// filter
							(array) $args[2], 									// must be in format: [ '$set|$inc|$mul|...' => (array)$doc ]
							(array) $opts										// options
						);
						$num_docs++;
					} elseif(Smart::array_size($args[3]) > 0) { // non-associative array mapped to $args[2] operation
						$write->update( // only update fields from $doc
							(array) $args[1], 									// filter
							(array) [ (string)$args[2] => (array)$args[3] ], 	// must be in format: [ '$set|$inc|$mul|...' => (array)$doc ]
							(array) $opts										// options
						);
						$num_docs++;
					} else {
						$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Document is empty or not array or invalid format ...', $args);
						return array();
						break;
					} //end if
				} //end if else
				//--
				if($num_docs <= 0) {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: No valid document(s) found ...', $args);
					return array();
				} //end if
				//--
				try {
					$result = $this->mongodbclient->executeBulkWrite($this->db.'.'.$this->collection, $write);
				} catch(Exception $err) {
					if((string)$method == 'upsert') {
						if(stripos((string)trim((string)$err->getMessage()), 'E11000 duplicate key error') === 0) { // avoid log upsert duplicate key errors which may occur in high concurrency when multiple processes try to update the same record exactly in the same time ; if upsert we assume that any process can do insert or update (order can be random)
							if(SmartEnvironment::ifDebug()) {
								Smart::log_notice('#MongoDB# :: Ignoring Upsert Duplicate Key: '.$err->getMessage());
							} //end if
							return array();
						} //end if
					} //end if else
					$this->error((string)$this->connex_key, 'MongoDB Write Execute', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: '.$err->getMessage(), $args);
					return array();
				} //end try
				if(!is_object($result)) {
					$this->error((string)$this->connex_key, 'MongoDB Write Result', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Result Object is null ...', $args);
					return array();
				} //end if
				$obj = array();
				if($result instanceof \MongoDB\Driver\WriteResult) {
					$msg = (string) implode("\n", (array)$result->getWriteErrors());
					$msg = (string) trim((string)$msg);
					if((string)$msg == '') {
						$msg = 'oknosqlwriteoperation';
					} //end if
					$obj[0] = (string) $msg; // ok / error message
					$obj[1] = 0; // affected
					if(((string)$method == 'insert') OR ((string)$method == 'bulkinsert')) {
						$obj[1] = (int) $result->getInsertedCount();
					} elseif(((string)$method == 'upsert') OR ((string)$method == 'update')) {
						$obj[1] = (int) ((int)$result->getUpsertedCount() + (int)$result->getModifiedCount());
					} //end if else
					$obj[2] = (string) $qry; // query
					$obj[3] = []; // return extra messages
					if((string)$method == 'upsert') {
						$obj[3] = [
							'upserted-ids' => (array) $result->getUpsertedIds() // this is returned only on INSERT
						];
					} //end if
					$msg = '';
					$drows = (int) $obj[1];
				} else {
					$this->error((string)$this->connex_key, 'MongoDB Write Result Type', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Result Object is not instance of WriteResult ...', $args);
					return array();
				} //end if
				//--
				//print_r($result); die();
				//--
				unset($result);
				unset($write);
				//--
				//print_r($obj); die();
				//--
				break;
			//--
			case 'delete': // ARGS [ strCollection, arrFilter ]
				//--
				$dcmd = 'write';
				//--
				$this->collection = (string) trim((string)($args[0] ?? null)); // strCollection
				if((string)trim((string)$this->collection) == '') {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'()', 'ERROR: Empty Collection name ...', $args);
					return array();
				} //end if
				//--
				$write = new \MongoDB\Driver\BulkWrite();
				if(!is_object($write)) {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Write Object is null ...', $args);
					return array();
				} //end if
				//--
				if(!is_array(($args[1] ?? null))) {
					$this->error((string)$this->connex_key, 'MongoDB Write', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Invalid Filter provided ...', $args);
					return array();
				} //end if
				$qry = 'delete';
				$opts = [ // delete options
					'limit' => false // delete all matching documents
				];
				$write->delete(
					(array) $args[1], 									// filter
					(array) $opts										// options
				);
				try {
					$result = $this->mongodbclient->executeBulkWrite($this->db.'.'.$this->collection, $write);
				} catch(Exception $err) {
					$this->error((string)$this->connex_key, 'MongoDB Write Execute', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: '.$err->getMessage(), $args);
					return array();
				} //end try
				if(!is_object($result)) {
					$this->error((string)$this->connex_key, 'MongoDB Write Result', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Result Object is null ...', $args);
					return array();
				} //end if
				$obj = array();
				if($result instanceof \MongoDB\Driver\WriteResult) {
					$msg = (string) implode("\n", (array)$result->getWriteErrors());
					$msg = (string) trim((string)$msg);
					if((string)$msg == '') {
						$msg = 'oknosqlwriteoperation';
					} //end if
					$obj[0] = (string) $msg;
					$obj[1] = (int) $result->getDeletedCount();
					$obj[2] = (string) $qry;
					$obj[3] = []; // return extra messages
					$msg = '';
					$drows = (int) $obj[1];
				} else {
					$this->error((string)$this->connex_key, 'MongoDB Write Result Type', 'MongoDB->'.$method.'() :: '.$this->collection, 'ERROR: Result Object is not instance of WriteResult ...', $args);
					return array();
				} //end if
				//--
				//print_r($result); die();
				//--
				unset($result);
				unset($write);
				//--
				//print_r($obj); die();
				//--
				break;
			//--
			case 'command': 	// ARGS [ arrCmd ]
			case 'igcommand': 	// ARGS [ arrCmd ]
				//-- dbg types: 'count', 'read', 'write', 'special', 'transaction', 'set', 'metainfo'
				$qry = (array) ($args[0] ?? null); // arrQuery
				foreach($qry as $kk => $vv) {
					if((string)strtolower((string)$kk) == 'buildinfo') {
						$dcmd = (string) 'metainfo';
					} elseif((string)strtolower((string)$kk) == 'count') {
						$dcmd = (string) 'count';
					} elseif(in_array((string)strtolower((string)$kk), ['find', 'findone', 'aggregate', 'distinct', 'mapreduce', 'geosearch'])) {
						$dcmd = (string) 'read';
					} elseif(in_array((string)strtolower((string)$kk), ['delete', 'insert', 'update', 'upsert', 'bulkinsert'])) {
						$dcmd = (string) 'write';
					} elseif(in_array((string)strtolower((string)$kk), ['create', 'createindexes', 'drop', 'dropindexes'])) {
						$dcmd = (string) 'special';
					} elseif((string)strtolower((string)$kk) == 'ping') {
						$dcmd = (string) 'set';
					} //end if
					$dmethod = (string) str_replace(':', '-', (string)$kk).'::'.$method; // subname
					break;
				} //end if
				//--
				$command = new \MongoDB\Driver\Command((array)$qry);
				if(!is_object($command)) {
					$this->error((string)$this->connex_key, 'MongoDB Command', 'MongoDB->'.$dmethod.'()', 'ERROR: Command Object is null ...', $args);
					return array();
				} //end if
				//--
				$igerr = false;
				try {
					$cursor = $this->mongodbclient->executeCommand($this->db, $command);
				} catch(Exception $err) {
					if((string)$method == 'igcommand') {
						$igerr = (string) $err->getMessage(); // must be type string
					} else {
						$this->error((string)$this->connex_key, 'MongoDB Command Execute', 'MongoDB->'.$dmethod.'()', 'ERROR: '.$err->getMessage(), $args);
						return array();
					} //end if else
				} //end try
				$obj = array();
				if(((string)$method == 'command') OR ($igerr === false)) {
					if(!is_object($cursor)) {
						$this->error((string)$this->connex_key, 'MongoDB Command Cursor', 'MongoDB->'.$dmethod.'()', 'ERROR: Cursor Object is null ...', $args);
						return array();
					} //end if
					$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
					//print_r($cursor->toArray()); die();
					if(is_object($cursor)) {
						$obj = Smart::json_decode(
							(string) Smart::json_encode(
								(array)$cursor->toArray(),
								false, // no pretty print
								true, // unescaped unicode
								false // html safe
							),
							true // return array
						); // mixed, normalize via json:encode/decode
						if(!is_array($obj)) {
							$obj = array();
						} //end if
						$drows = (int) Smart::array_size($obj);
					} //end if object
				} else {
					$obj = array(
						'ERRORS' => [
							'err-msg' 	=> (string) $igerr,
							'type' 		=> 'catcheable PHP Exception / MongoDB Manager: executeCommand',
							'class' 	=> (string) __CLASS__,
							'function' 	=> (string) __FUNCTION__,
							'method' 	=> (string) $dmethod
						]
					);
				} //end if else
				//--
				unset($cursor);
				unset($command);
				//print_r($obj); die();
				//--
				break;
			//--
			default:
				//--
				$this->error((string)$this->connex_key, 'MongoDB Method', 'MongoDB->'.$method.'()', 'ERROR: The selected method ['.$method.'] is NOT implemented ...', $args);
				return null;
				//--
		} //end switch
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			if($skipdbg !== true) {
				if($this->connected === true) { // avoid register pre-connect commands like version)
					//--
					SmartEnvironment::setDebugMsg('db', 'mongodb|total-queries', 1, '+');
					//--
					$time_end = (float) (microtime(true) - (float)$time_start);
					//--
					SmartEnvironment::setDebugMsg('db', 'mongodb|total-time', $time_end, '+');
					//--
					$dbg_arr_cmd = [];
					if($this->collection) {
						$dbg_arr_cmd['Collection'] = (string) $this->collection;
					} //end if
					$dbg_arr_cmd['Query'] = (array) $qry;
					if($opts) {
						$dbg_arr_cmd['Options'] = (array) $opts;
					} //end if
					//--
					SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
						'type' 			=> (string) $dcmd,
						'data' 			=> (string) strtoupper((string)$dmethod),
						'command' 		=> (array)  $dbg_arr_cmd,
						'time' 			=> (string) Smart::format_number_dec($time_end, 9, '.', ''),
						'rows' 			=> (int)    $drows,
						'connection' 	=> (string) $this->connex_key
					]);
					//--
					$dbg_arr_cmd = null; // free mem
					//--
				} //end if
			} //end if
		} //end if
		//--

		//--
		return $obj; // mixed
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * this is the internal connector (will connect just when needed)
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	private function connect(string $type, string $username, string $password, string $authmet) : bool {

		//--
		$replica = false;
		if((string)$type == 'mongo-cluster') { // cluster (sharding) ; {{{SYNC-MONGODB-CONN-CLUSTER}}}
			$concern_rd = 'majority'; // requires the servers to be started with --enableMajorityReadConcern
			$concern_wr = 'majority'; // make sense if with a sharding cluster
		} elseif(strpos((string)$type, 'mongo-replica-set:') !== false) { // replica set
			$replica = (array) explode('mongo-replica-set:', (string)$type);
			$replica = (string) trim((string)($replica[1] ?? ''));
			$concern_rd = 'available';
			$concern_wr = 'majority';
		} else { // mongo-standalone
			$concern_rd = 'local';
			$concern_wr = 1;
		} //end if else
		//--

		//--
		$options = array(
			'connect' 					=> false, // lazy
			'connectTimeoutMS' 			=> (int) ($this->timeout * 1000),
			'socketTimeoutMS' 			=> (int) (SMART_FRAMEWORK_NETSOCKET_TIMEOUT * 1000),
			'serverSelectionTimeoutMS' 	=> (int) ($this->timeout * 1000),
			'serverSelectionTryOnce' 	=> false, // searches for a server up to the serverSelectionTimeoutMS value
			'readConcernLevel' 			=> $concern_rd, // rd concern
			'w' 						=> $concern_wr, // wr concern
			'wTimeoutMS' 				=> (int) (SMART_FRAMEWORK_NETSOCKET_TIMEOUT * 1000) // if this is 0 (no timeout) the write operation will block indefinitely
		);
		if($replica !== false) {
			$options['replicaSet'] = (string) $replica;
			$nfo_replica = ' @ [replicaSet='.$replica.']';
		} else {
			$nfo_replica = '';
		} //end if
		//--
		if((string)$username != '') {
			$options['username'] = (string) $username;
			if((string)$password != '') {
				$options['password'] = (string) $password;
				$options['authMechanism'] = (string) $authmet; // default is 'MONGODB-CR'
			} //end if
		} //end if
		//--

		//--
		if(is_array(SmartEnvironment::$Connections) AND array_key_exists('mongodb', SmartEnvironment::$Connections) AND is_array(SmartEnvironment::$Connections['mongodb']) AND is_object(SmartEnvironment::$Connections['mongodb'][(string)$this->connex_key])) {
			//--
			$this->mongodbclient = &SmartEnvironment::$Connections['mongodb'][(string)$this->connex_key];
			$this->connected = true;
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
					'type' => 'open-close',
					'data' => 'Re-Using MongoDB Manager Instance :: ServerType ['.$type.']: '.$this->connex_key.$nfo_replica
				]);
			} //end if
			//--
		} else {
			//--
			try {
				$this->mongodbclient = new \MongoDB\Driver\Manager(
					(string) 'mongodb://'.$this->server.'/'.$this->db,
					(array) $options
				);
			} catch(Exception $err) {
				$this->mongodbclient = null;
				$this->error((string)$this->connex_key, 'MongoDB Manager', 'Failed to Initialize Object: '.$this->db.' on '.$this->server, 'ERROR: '.$err->getMessage());
				return false;
			} //end try catch
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
					'type' => 'open-close',
					'data' => 'Creating MongoDB Manager Instance :: ServerType ['.$type.']: '.$this->connex_key.$nfo_replica
				]);
			} //end if
			//--
			$this->get_server_version(); // this will register the $this->srvver if req.
			//--
			if(((string)$this->srvver == '') OR (version_compare((string)self::minVersionServer, (string)$this->srvver) > 0)) {
				$this->mongodbclient = null;
				$this->error((string)$this->connex_key, 'MongoDB Manager', 'Invalid MongoDB Server Version on '.$this->server, 'ERROR: Minimum MongoDB supported Server version is: '.self::minVersionServer.' but this Server version is: '.$this->srvver);
				return false;
			} //end if
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
					'type' => 'metainfo',
					'data' => 'MongoDB Extension Version: '.$this->extver
				]);
				SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
					'type' => 'metainfo',
					'data' => 'MongoDB Server Version: '.$this->srvver
				]);
			} //end if
			//--
			SmartEnvironment::$Connections['mongodb'][(string)$this->connex_key] = &$this->mongodbclient; // export connection
			//--
			$this->connected = true;
			//--
		} //end if else
		//--

		//--
		if(SmartEnvironment::ifDebug()) {
			//--
			SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
				'type' => 'set',
				'data' => 'Using Database: '.$this->db,
				'connection' => (string) $this->server,
				'skip-count' => 'yes'
			]);
			//--
		} //end if
		//--

		//--
		return true;
		//--

	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * this is for disconnect from MongoDB
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public function disconnect() : void {
		//--
		SmartEnvironment::$Connections['mongodb'][(string)$this->connex_key] = null; // close connection
		//--
		if(SmartEnvironment::ifDebug()) {
			SmartEnvironment::setDebugMsg('db', 'mongodb|log', [
				'type' => 'open-close',
				'data' => 'Destroying MongoDB Manager Instance: '.$this->connex_key
			]);
		} //end if
		//--
	} //END FUNCTION
	//======================================================


	//======================================================
	/**
	 * Displays the MongoDB Errors and HALT EXECUTION (This have to be a FATAL ERROR as it occur when a FATAL MongoDB ERROR happens or when a Data Query fails)
	 * PRIVATE
	 *
	 * @param STRING $y_conhash :: Connection Hash or a message key if not connected
	 * @param STRING $y_area :: The Area
	 * @param STRING $y_info :: The Extra Info
	 * @param STRING $y_error_message :: The Error Message to Display
	 * @param STRING $y_query :: The query
	 * @param STRING $y_warning :: The Warning Title
	 *
	 * @return :: void ; HALT THE EXECUTION WITH ERROR MESSAGE
	 *
	 */
	private function error($y_conhash, $y_area, $y_info, $y_error_message, $y_query='', $y_warning='', $y_is_fatal=null) : void {
		//--
		if(($y_is_fatal === true) OR ($y_is_fatal === false)) { // depends on how is set, conform
			$y_is_fatal = (bool) $y_is_fatal;
		} else { // NULL :: default, depend on how $this->fatal_err is
			if($this->fatal_err === false) {
				$y_is_fatal = false;
			} else {
				$y_is_fatal = true;
			} //end if else
		} //end if else
		//--
		if($y_is_fatal === false) {
			throw new Exception('#MONGO-DB@'.$y_conhash.'# :: Q# // MongoDB Client :: EXCEPTION :: '.$y_area."\n".$y_info.': '.$y_error_message);
			return;
		} //end if
		//--
		$def_warn = 'Execution Halted !';
		$y_warning = (string) trim((string)$y_warning);
		if(is_array($y_query)) { // can be also an empty array
			$y_query = (string) print_r($y_query,1);
		} //end if
		$the_params = '- '.'MongoDB Manager v.'.$this->extver.' -';
		if(SmartEnvironment::ifDebug()) {
			$width = 750;
			$the_area = (string) $y_area;
			if((string)$y_warning == '') {
				$y_warning = (string) $def_warn;
			} //end if
			$the_error_message = 'Operation FAILED: '.$def_warn."\n".$y_error_message;
			$the_query_info = (string) trim((string)$y_query);
			$y_query = ' '.trim((string)$the_params."\n".$y_info."\n".$y_query);
		} else {
			$width = 550;
			$the_area = '';
			$the_error_message = 'Operation FAILED: '.$def_warn;
			$y_query = ' '.trim((string)$the_params."\n".$y_info."\n".$y_query);
			$the_query_info = ''; // do not display query if not in debug mode ... this a security issue if displayed to public ;)
			$the_params = '';
		} //end if else
		//--
		$out = '';
		if(class_exists('SmartComponents')) {
			$out = (string) SmartComponents::app_error_message(
				'MongoDB Manager',
				'MongoDB',
				'NoSQL/DB',
				'Server',
				'lib/core/img/db/mongodb-logo.svg',
				(int)    $width, // width
				(string) $the_area, // area
				(string) $the_error_message, // err msg
				(string) $the_params, // title or params
				(string) $the_query_info // command
			);
		} //end if
		//--
		Smart::raise_error(
			'#MONGO-DB@'.$y_conhash.' :: Q# // MongoDB Client :: ERROR :: '.$y_area."\n".'*** Error-Message: '.$y_error_message."\n".'*** Statement:'.$y_query,
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
