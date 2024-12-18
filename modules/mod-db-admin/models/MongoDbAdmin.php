<?php
// Class: \SmartModDataModel\DbAdmin\MongoDbAdmin
// Type: Module Data Model: DbAdmin / MongoDB Admin
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup
// (c) 2008-present unix-world.org - all rights reserved

namespace SmartModDataModel\DbAdmin;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


final class MongoDbAdmin { // v.20220921

	// ::

	private static $mongo = null;
	private static $config = [];


	public static function getExtVersion() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		return (string) $mongo->get_ext_version();
		//--
	} //END FUNCTION


	public static function getServerVersion() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		return (string) $mongo->get_server_version();
		//--
	} //END FUNCTION


	public static function getServerBuildInfo() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$result = (array) $mongo->igcommand(
			[
				'buildInfo' => 1
			]
		);
		//--
		if(!$mongo->is_command_ok($result)) {
			return array();
		} //end if
		//--
		$result = (array) ($result[0] ?? null);
		unset($result['ok']);
		//--
		return (array) $result;
		//--
	} //END FUNCTION


	public static function getDbName() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return '';
		} //end if
		//--
		return (string) self::$config['dbname'];
		//--
	} //END FUNCTION


	public static function getDbHost() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return '';
		} //end if
		//--
		return (string) self::$config['server-host'];
		//--
	} //END FUNCTION


	public static function getDbPort() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return '';
		} //end if
		//--
		return (string) self::$config['server-port'];
		//--
	} //END FUNCTION


	public static function getDbCollections() {
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$result = (array) $mongo->igcommand(
			[
				'listCollections' => 1 // 'listDatabases' => 1 (to get databases list ; req to be connected to `admin` db)
			]
		);
		//--
		return (array) $result;
		//--
	} //END FUNCTION


	public static function getDbCollectionStats(string $collection) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return array();
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$result = (array) $mongo->igcommand(
			[
				'collStats' => (string) $collection
			]
		);
		//--
		if(!$mongo->is_command_ok($result)) {
			return [];
		} //end if
		//--
		return (array) $result;
		//--
	} //END FUNCTION


	public static function getDbCollectionIndexes(string $collection) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return array();
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$result = (array) $mongo->igcommand(
			[
				'listIndexes' => (string) $collection
			]
		);
		//--
		return (array) $result;
		//--
	} //END FUNCTION


	// THROWS
	public static function getRecordsCount(string $collection, array $query=[]) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 0;
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 0;
		} //end if
		//--
		return (int) $mongo->count(
			(string) $collection,
			(array)  $query // filter
		);
		//--
	} //END FUNCTION


	// THROWS
	public static function getRecordsData(string $collection, array $query=[], $offset=0, $limit=10, $sorting=[]) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return array();
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$arrOptions = [
			'limit' => (int) $limit, // limit
			'skip' 	=> (int) $offset // offset
		];
		//--
		if(\Smart::array_type_test($sorting) == 2) {
			foreach($sorting as $key => $val) {
				$key = (string) trim((string)$key);
				$val = (string) strtoupper((string)trim((string)$val));
				if((string)$key != '') {
					if($val === 'DESC') {
						$val = -1;
					} else {
						$val = 1;
					} //end if else
					$arrOptions['sort'][(string)$key] = (int) $val;
				} //end if
			} //end foreach
		} //end if
		//--
		return (array) $mongo->find(
			(string) $collection,
			(array)  $query, // filter
			(array)  [],     // no projection
			(array)  $arrOptions
		);
		//--
	} //END FUNCTION


	public static function getRealMongoId(?string $id) {
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			return '';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return (string) $id;
		} //end if
		//--
		if((\strpos((string)$id, 'ObjectId(') === 0) AND ((string)\substr((string)$id, -1, 1) == ')')) { // try to convert to MongoDB ObjectId
			$objMongoId = $mongo->getObjectId((string)\substr((string)$id, 9, -1)); // return mixed
		} else { // preserve as string
			$objMongoId = (string) $id;
		} //end if
		//--
		return $objMongoId; // MIXED: string or object
		//--
	} //END FUNCTION


	public static function convertQueryToRealMongoId(?array $query, int $level=0) : array { // do not use strong type params
		//--
		$level = (int) $level;
		if((int)$level < 0) {
			return array();
		} //end if
		//--
		if(!\is_array($query)) {
			return array();
		} //end if
		//--
		foreach((array)$query as $key => $val) {
			if(((string)$key == '_id') OR ($level > 0)) {
				if(\is_array($val)) {
					$query[(string)$key] = self::convertQueryToRealMongoId((array)$val, (int)((int)$level + 1)); // array, do not cast !
				} else {
					$query[(string)$key] = self::getRealMongoId((string)$val); // mixed: string or object, do not cast
				} //end if
				break;
			} //end if
		} //end foreach
		//--
		return (array) $query;
		//--
	} //END FUNCTION


	public static function getRecord(string $collection, string $id) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return array();
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		if((string)\trim((string)$id) == '') {
			return array();
		} //end if
		$id = self::getRealMongoId($id); // mixed
		if(!$id) {
			return array();
		} //end if
		//--
		try {
			$result = (array) $mongo->findone(
				(string) $collection,
				[
					'_id' => $id // mixed
				] // filter
			);
		} catch(\Exception $err) {
			// $err->getMessage();
			$result = array();
		} //end try catch
		//--
		return (array) $result;
		//--
	} //END FUNCTION


	public static function insertRecord(string $collection, array $doc, string $name_new_collection='') {
		//--
		$name_new_collection = (string) \trim((string)$name_new_collection);
		if((string)$name_new_collection != '') {
			if(self::validateCollectionName((string)$name_new_collection) !== true) {
				\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Invalid ...');
				return 'Invalid Name for the New Collection ...';
			} //end if
			$collection = (string) $name_new_collection;
		} //end ie
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		if(!\is_array($doc)) {
			return 'Document Data is NOT Array';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		$doc['_id'] = $doc['_id'] ?? null; // do not cast, perhaps not scalar !
		if(!\Smart::is_nscalar($doc['_id'])) {
			$doc['_id'] = '';
		} //end if
		$doc['_id'] = (string) \trim((string)($doc['_id'] ?? null));
		$is_uuid_valid = false;
		if(((int)\strlen((string)$doc['_id']) >= 1) AND ((int)\strlen((string)$doc['_id']) <= 255)) {
			$is_uuid_valid = true;
		} //end if
		if(((string)$doc['_id'] == '') OR ($is_uuid_valid !== true)) {
			$doc['_id'] = (string) \strtolower((string)$mongo->assign_uuid()); // generate new _id if empty or invalid
		} //end if
		//--
		if(\Smart::array_size($doc) <= 0) {
			return 'Empty Document';
		} //end if
		//--
		$result = array();
		try {
			$result = (array) $mongo->insert(
				(string) $collection,
				(array)  $doc
			);
		} catch(\Exception $err) {
			return 'Insert EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if($result[1] != 1) {
			return 'Insert FAILED: ['.$result[1].']';
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function modifyRecord(string $collection, string $id, array $doc) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		if((string)\trim((string)$id) == '') {
			return 'Empty Record UID';
		} //end if
		//--
		if(!\is_array($doc)) {
			return 'Document Data is NOT Array';
		} //end if
		if(\array_key_exists('_id', (array)$doc)) {
			unset($doc['_id']); // the _id must not be updated
		} //end if
		//--
		if(\Smart::array_size($doc) <= 0) {
			return 'Empty Document';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		$id = self::getRealMongoId($id); // mixed
		if(!$id) {
			return 'Invalid Record UID';
		} //end if
		//--
		$result = array();
		try {
			$result = (array) $mongo->update(
				(string) $collection,
				(array) [ '_id' => $id ], // filter
				(array) [ 0 => (array) $doc ] // replace
			);
		} catch(\Exception $err) {
			return 'Update EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if($result[1] != 1) {
			return 'Update FAILED: ['.$result[1].']';
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function deleteRecord(string $collection, string $id) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		if((string)\trim((string)$id) == '') {
			return 'Empty Record UID';
		} //end if
		$id = self::getRealMongoId($id); // mixed
		if(!$id) {
			return 'Invalid Record UID';
		} //end if
		//--
		try {
			$result = (array) $mongo->delete(
				(string) $collection,
				[
					'_id' => $id // mixed
				] // filter
			);
		} catch(\Exception $err) {
			return 'Delete EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if($result[1] != 1) {
			return 'Delete FAILED: ['.$result[1].']';
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function deleteRecords(string $collection, array $filter) { // delete many records by filter
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		if((int)\Smart::array_size($filter) <= 0) {
			return 'Invalid Records Filter Criteria';
		} //end if
		$filter = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::convertQueryToRealMongoId((array)$filter);
		if((int)\Smart::array_size($filter) <= 0) {
			return 'Empty Records Filter Criteria';
		} //end if
		//--
		try {
			$result = (array) $mongo->delete(
				(string) $collection,
				(array) $filter // filter
			);
		} catch(\Exception $err) {
			return 'Delete EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if((int)$result[1] <= 0) {
			return 'Delete Records FAILED: ['.$result[1].']';
		} //end if
		//--
		return 'OK: #'.(int)$result[1].' Record(s) Deleted';
		//--
	} //END FUNCTION


	public static function dropCollection(string $collection) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		if(self::validateCollectionName((string)$collection) !== true) {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Invalid ...');
			return 'Invalid Name for the Selected Collection to Drop ...';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		$result = $mongo->igcommand(
			[
				'drop' => (string) $collection
			]
		);
		//--
		if(!$mongo->is_command_ok($result)) {
			return 'MongoDB Collection Drop FAILED for: '.$collection;
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function dropIndex(string $collection, string $index) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		$index = (string) $index;
		if(self::validateIndexName((string)$index) !== true) {
			return 'No Index Selected';
		} //end if
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		try {
			$result = $mongo->command(
				[
					'dropIndexes' => (string) $collection,
					'index' => (string) $index
				]
			);
		} catch(\Exception $err) {
			return 'dropIndex EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if(!$mongo->is_command_ok($result)) {
			return 'MongoDB Collection Drop Indexes FAILED for: '.$collection;
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function createIndex(string $collection, array $defs) {
		//--
		$collection = (string) \trim((string)$collection);
		if((string)$collection == '') {
			\Smart::log_warning(__METHOD__.'() MongoDB Collection Name is Empty ...');
			return 'No Collection Selected';
		} //end if
		//--
		if(\Smart::array_size($defs) <= 0) {
			return 'Empty Index Definitions';
		} //end if
		if(\Smart::array_type_test($defs) != 2) { // must be associative
			return 'Invalid Index Definitions';
		} //end if
		//--
		if(!\array_key_exists('name', (array)$defs)) {
			return 'Index Definitions are Missing for the Property: name'.\print_r($defs,1);
		} //end if
		if(self::validateIndexName((string)$defs['name']) !== true) {
			return 'Index Definitions are Invalid for the Property: name: `'.$defs['name'].'`';
		} //end if
		//--
		if(!\array_key_exists('key', (array)$defs)) {
			return 'Index Definitions are Missing the Property: key';
		} //end if
		if((\Smart::array_size($defs['key']) <= 0) OR (\Smart::array_type_test($defs['key']) != 2)) { // must be associative
			return 'Index Definitions are Invalid for the Property: [key]: Must Be an Associative Array as [ key1: 1, key2: -1, ..., key3: text ]';
		} //end if
		foreach($defs['key'] as $key => $val) {
			if((string)\trim((string)$key) == '') {
				return 'Index Definitions are Empty for at least one Property of: key[]';
			} //end if
			if((string)\trim((string)$val) == '') {
				return 'Index Definitions are Empty for the Property: key['.$key.']';
			} //end if
		} //end foreach
		//--
		$mongo = self::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return 'MongoDB Instance is N/A';
		} //end if
		//--
		try {
			$result = $mongo->command(
				[
					'createIndexes' => (string) $collection,
					'indexes' => [
						(array) $defs
					]
				]
			);
		} catch(\Exception $err) {
			return 'createIndex EXCEPTION: '.$err->getMessage();
		} //end try catch
		//--
		if(!$mongo->is_command_ok($result)) {
			return 'MongoDB Collection Create Indexes FAILED for: '.$collection;
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	public static function validateCollectionName(string $collection) {
		//--
		if((string)\trim((string)$collection) == '') {
			return false;
		} //end if
		//--
		if(!\preg_match((string)'/^[a-zA-Z0-9_]+$/', (string)$collection)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function validateIndexName(string $index) {
		//--
		if((string)\trim((string)$index) == '') {
			return false;
		} //end if
		//--
		if((string)\trim((string)$index) == '_id_') { // this is reserved for MongoDB internal index as _id !!
			return false;
		} //end if
		//--
		if(!\preg_match((string)'/^[[:graph:]]+$/', (string)$index)) { // [:graph:] = Visible characters (anything except spaces and control characters)
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function getInstance() {
		//--
		$cfg = \Smart::get_from_config('mongodb');
		if(\Smart::array_size($cfg) <= 0) {
			\Smart::raise_error(__METHOD__.'() MongoDB Config is not available ...');
			return null;
		} //end if
		self::$config = (array) $cfg;
		//--
		if(self::$mongo === null) {
			try {
				self::$mongo = new \SmartMongoDb((array)self::$config, false); // non-fatal errors !!
			} catch(\Exception $e) {
				return null;
			}
		} //end if
		//--
		return self::$mongo; // mixed
		//--
	} //END FUNCTION


} //END CLASS


// end of php code
