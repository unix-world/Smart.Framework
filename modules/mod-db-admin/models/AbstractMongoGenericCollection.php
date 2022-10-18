<?php
// Class: \SmartModDataModel\DbAdmin\AbstractMongoGenericCollection
// Type: Module Data Model: DbAdmin / Abstract Generic Collection
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup
// (c) 2006-2022 unix-world.org - all rights reserved

namespace SmartModDataModel\DbAdmin;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


abstract class AbstractMongoGenericCollection { // v.20221011

	// ::

	public const DB_TYPE = 'MongoDB';

	protected static $errfatal = true; // by default use fatal errors
	protected static $collection = 'GenericCollection'; // change it
	protected static $version = '2022-09-12 02:58'; // change it ; the version for the search collection structure ; each time will change it will drop the collection with all data and will re-initialize the collection, recreate indexes ...
	protected static $indexes = [
		[
			'name' 				=> 'dt',
			'key' 				=> [ 'dt' => 1 ]
		],
		[
			'name' 				=> 'id',
			'key' 				=> [ 'id' => 1 ]
		],
		[
			'name' 				=> 'area',
			'key' 				=> [ 'area' => 1 ]
		],
		[
			'name' 				=> 'uniq',
			'key' 				=> [ 'area' => 1, 'id' => 1 ],
			'unique' 			=> true
		],
	//-- ex: extend
	//	[
	//		'name' 				=> 'doc_field1',
	//		'key' 				=> [ 'doc.field1' => 1 ]
	//	],
	//	[
	//		'name' 				=> 'text',
	//		'key' 				=> [ 'doc.name' => 'text', 'doc.desc' => 'text', 'doc.body' => 'text' ],
	//		'weights' 			=> [ 'doc.name' => 100,    'doc.desc' => 5,      'doc.body' => 1 ],
	//		'default_language' 	=> 'en', // must be a valid language code
	//		'language_override' => 'dictionary', // override field from document
	//	],
	//-- #
	];

	private static $mongo = null;

	private const UID_FIELD_SEPARATOR = ':';


	final public static function isActive() : bool {
		//--
		$mongo = static::getInstance(true);
		if(!$mongo) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	final public static function getDbVersion() : string {
		//--
		$mongo = static::getInstance(true);
		if(!$mongo) {
			return '?';
		} //end if
		//--
		return (string) 'Server: v.'.$mongo->get_server_version().' ; Client v.'.$mongo->get_ext_version();
		//--
	} //END FUNCTION


	final public static function getAreas() : array {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$result = $mongo->command(
			[
				'distinct' 	=> (string) static::getCollection(),
				'key' 		=> 'area',
				'query' 	=> null
			]
		);
		$arr = [];
		if(($mongo->is_command_ok($result)) AND isset($result[0]) AND \is_array($result[0]) AND ((int)\Smart::array_size($result[0]) > 0) AND isset($result[0]['values']) AND is_array($result[0]['values'])) {
			$arr = (array) $result[0]['values'];
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	final public static function getRecordsCount(string $area='') : int {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		//--
		$filter = [];
		if((string)$area != '') {
			$filter = [
				'area' => (string) $area,
			];
		} //end if
		//--
		$cnt = (int) $mongo->count(
			(string) static::getCollection(),
			(array) $filter // filter
		);
		//--
		return (int) $cnt;
		//--
	} //END FUNCTION


	final public static function getRecordsList(string $area='', int $limit=0, int $offset=0, string $sortby='', string $sortdir='', array $extra_sort=[], array $extra_filter=[]) : array {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		//--
		$filter = [];
		if((string)$area != '') {
			$filter['area'] = (string) $area;
		} //end if
		if((int)\Smart::array_size($extra_filter) > 0) { // {{{SYNC-DB-ADMIN-MONGO-APPLY-EXTRA-FILTER}}}
			foreach($extra_filter as $key => $val) {
				$key = (string) \trim((string)$key);
				$val = \Smart::json_decode(\Smart::json_encode($val)); // force discard objects, resources and keep just nScalar and Array
				if((string)$key != '') {
					if((string)$key != 'area') {
						$filter[(string)$key] = $val;
					} //end if
				} //end if
			} //end foreach
		} //end if
		//--
		$sortby = (string) \trim((string)$sortby);
		if((string)$sortby == '') {
			$sortby = '_id';
		} //end if
		$sortdir = (string) \strtoupper((string)\trim((string)$sortdir));
		if((string)$sortdir == 'DESC') {
			$sortdir = -1;
		} else { // ASC
			$sortdir = 1;
		} //end if
		$sort_arr = [ (string)$sortby => (int)$sortdir ];
		if((int)\Smart::array_size($extra_sort) > 0) {
			foreach($extra_sort as $key => $val) {
				$key = (string) \trim((string)$key);
				$val = (string) \strtoupper((string)\trim((string)$val));
				if((string)$val == 'DESC') {
					$val = -1;
				} else { // ASC
					$val = 1;
				} //end if
				if((string)$key != '') {
					if(!\array_key_exists((string)$key, (array)$sort_arr)) {
						$sort_arr[(string)$key] = (int) $val;
					} //end if
				} //end if
			} //end foreach
		} //end if
		$options = [ 'sort' => (array)$sort_arr ];
		if((int)$limit > 0) {
			$options['limit'] = (int) $limit;  // limit
			$options['skip']  = (int) $offset; // offset
		} //end if
		//--
		return (array) $mongo->find(
			(string) static::getCollection(),
			(array) $filter, // filter
			[
				// projection: all
			],
			(array) $options // options
		);
		//--
	} //END FUNCTION


	// with the default indexes the combination of area/id is unique and single area or single id are not ...
	// for the default scenario to get a unique field both: area and id must be provided and non-empty
	// {{{SYNC-GenericCollection-FILTER-ONE-RECORD}}}
	final public static function getRecord(?string $area, ?string $id, array $extra_filter=[]) : array {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		$id = (string) \trim((string)$id);
		//--
		$filter = [];
		if((string)$area != '') { // at insert empty area is not possible thus searching for an empty area also is not possible
			$filter['area'] = (string) $area; // by default area is not unique ... but can be when redefine indexes
		} //end if
		if((string)$id != '') { // at insert empty id is not possible thus searching for an empty id also is not possible
			$filter['id'] = (string) $id; // by default id is not unique ... but can be when redefine indexes
		} //end if
		if((int)\Smart::array_size($extra_filter) > 0) { // {{{SYNC-DB-ADMIN-MONGO-APPLY-EXTRA-FILTER}}}
			foreach($extra_filter as $key => $val) {
				$key = (string) \trim((string)$key);
				$val = \Smart::json_decode(\Smart::json_encode($val)); // force discard objects, resources and keep just nScalar and Array
				if((string)$key != '') {
					if(((string)$key != 'area') AND ((string)$key != 'id')) {
						$filter[(string)$key] = $val;
					} //end if
				} //end if
			} //end foreach
		} //end if
		//--
		if((int)\Smart::array_size($filter) <= 0) {
			return []; // unsupported
		} //end if
		//--
		return (array) $mongo->findone(
			(string) static::getCollection(),
			(array) $filter, // filter
			(array) [], // projection: all fields
			[
				'limit' => (int) 1,  // limit
				'skip' 	=> (int) 0 // offset
			]
		);
		//--
	} //END FUNCTION


	final public static function insertRecord(string $area, string $id, array $doc, bool $overwrite=false) : array {
		//--
		// $doc must NOT contain the following keys: _id, id, area, date
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		if((string)$area == '') {
			\Smart::log_warning(__METHOD__.'() # MongoDB Insert Record: Area is Empty for ID: '.$id);
			return array();
		} //end if
		if(\strpos((string)$area, (string)self::UID_FIELD_SEPARATOR) !== false) {
			\Smart::log_warning(__METHOD__.'() # MongoDB Insert Record: Area `'.$area.'` Contains Invalid Characters like `'.(string)self::UID_FIELD_SEPARATOR.'` ; ID: '.$id);
			return array();
		} //end if
		//--
		$id = (string) \trim((string)$id);
		if((string)$id == '') {
			\Smart::log_warning(__METHOD__.'() # MongoDB Insert Record: ID is Empty for Area: '.$area);
			return array();
		} //end if
		if(\strpos((string)$id, (string)self::UID_FIELD_SEPARATOR) !== false) {
			\Smart::log_warning(__METHOD__.'() # MongoDB Insert Record: ID `'.$id.'` Contains Invalid Characters like `'.(string)self::UID_FIELD_SEPARATOR.'` ; ID: '.$id);
			return array();
		} //end if
		//--
		if((int)\Smart::array_size((array)$doc) <= 0) {
			\Smart::log_notice(__METHOD__.'() # MongoDB Insert Record: Document is Empty for: '.$area.' / '.$id);
			return array();
		} //end if
		//--
		$idoc = [
			'_id' 			=> (string) $area.self::UID_FIELD_SEPARATOR.$id, // ensure the same uuid to avoid 2 different uuids are upserted in the same time and generate duplicate error on high concurrency
			'id' 			=> (string) $id, // unique id per area
			'area' 			=> (string) $area, // ex: `area-one`
			'dt' 			=> (string) \date('Y-m-d H:i:s'),
		];
		if((int)\Smart::array_size($doc) > 0) {
			if((int)\Smart::array_type_test($doc) == 2) { // associative
				foreach($doc as $key => $val) {
					$key = (string) \trim((string)$key);
					if((string)$key != '') {
						$val = \Smart::json_decode(\Smart::json_encode($val)); // trick: discard anything else except nScalar and Arrays (dissalow resources, objects, ...)
						if(!\array_key_exists((string)$key, (array)$idoc)) {
							$idoc[(string)$key] = $val; // mixed
						} //end if
					} //end if
				} //end foreach
			} //end if
		} //end if
		//--
		$arr = [];
		try {
			$filter = [ // filter by Unique
				'area'		=> (string) $area,
				'id' 		=> (string) $id,
			];
			if($overwrite === true) {
				$arr = (array) $mongo->upsert(
					(string) static::getCollection(),
					(array) $filter, // filter
					'$set', 		// operation
					(array) $idoc 	// update array
				);
			} else {
				$arr = (array) $mongo->insert(
					(string) static::getCollection(),
					(array) $idoc
				);
			} //end if else
		} catch(\Exception $err) {
			// only log notice, not warning ... as in production this may occur on high concurrency over big data, there is no DB lock, just record lock !!
			\Smart::log_warning(__METHOD__.'() # MongoDB Insert Record :: Upsert Exception: '.$err->getMessage());
			$arr = array();
		} //end try catch
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	final public static function deleteRecord(string $area, string $id) : array {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		$id  = (string) \trim((string)$id);
		//--
		return (array) $mongo->delete(
			(string) static::getCollection(),
			[ // {{{SYNC-GenericCollection-FILTER-ONE-RECORD}}}
				'area' 	=> (string) $area,
				'id' 	=> (string) $id
			] // filter
		);
		//--
	} //END FUNCTION


	final public static function deleteRecords(string $area='') : array {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return array();
		} //end if
		//--
		$area = (string) \trim((string)$area);
		//--
		$filter = [];
		if((string)$area != '') {
			$filter = [
				'area' => (string) $area,
			];
		} //end if
		//--
		return (array) $mongo->delete(
			(string) static::getCollection(),
			(array)  $filter, // filter
		);
		//--
	} //END FUNCTION


	//===== PRIVATES


	final protected static function getInstance(bool $only_test=false) : ?object {
		//--
		$cfg = (array) \Smart::get_from_config('mongodb', 'array');
		if(\Smart::array_size($cfg) <= 0) {
			if($only_test !== true) {
				\Smart::log_warning(__METHOD__.'() MongoDB Config is not available ...');
			} //end if
			return null;
		} //end if
		//--
		if(self::$mongo === null) {
			if(static::$errfatal === false) {
				try {
					self::$mongo = new \SmartMongoDb((array)$cfg, false); // non-fatal errors !!
				} catch(\Exception $e) {
					return null;
				} //end try catch
			} else {
				self::$mongo = new \SmartMongoDb((array)$cfg); // fatal errors (as default)
			} //end if else
		} //end if
		//--
		return self::$mongo; // mixed
		//--
	} //END FUNCTION


	final protected static function getCollection() : string {
		//--
		$mongo = static::getInstance();
		if(!$mongo) {
			\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
			return (string) static::$collection;
		} //end if
		//--
		$mongo->igcommand(
			[
				'create' => 'SmartFrameworkMetaInfo'
			]
		);
		$test = (array) $mongo->findone(
			'SmartFrameworkMetaInfo',
			[ 'CollectionName' => (string) static::$collection ]
		);
		$test['CollectionVersion'] = ($test['CollectionVersion'] ?? null);
		$result = null;
		if((string)$test['CollectionVersion'] != (string)static::$version) {
			$mongo->delete(
				'SmartFrameworkMetaInfo',
				[
					'CollectionName' => (string) static::$collection
				] // filter
			);
			$mongo->insert(
				'SmartFrameworkMetaInfo',
				[
					'CollectionName' 		=> (string) static::$collection,
					'CollectionVersion' 	=> (string) static::$version,
					'CollectionCreatedOn' 	=> (string) \date('Y-m-d H:i:s O'),
				]
			);
			$mongo->igcommand(
				[
					'drop' => (string) static::$collection
				]
			);
		} //end if
		//--
		$test = (array) $mongo->findone(
			'SmartFrameworkMetaInfo',
			[ 'CollectionName' => (string) static::$collection ]
		);
		$test['CollectionVersion'] = ($test['CollectionVersion'] ?? null);
		if((string)$test['CollectionVersion'] != (string)static::$version) {
			\Smart::raise_error(__METHOD__.'() Invalid MongoDB Collection Version: `'.$test['CollectionVersion'].'` != `'.static::$version.'` # for collection: `'.static::$collection.'`');
			return (string) static::$collection;
		} //end if
		//--
		$result = $mongo->igcommand(
			[
				'create' => (string) static::$collection
			]
		);
		if($mongo->is_command_ok($result)) { // cmd is OK just when creates
			//--
			$result = $mongo->igcommand(
				[
					'createIndexes' => (string) static::$collection,
					'indexes' 		=> (array)  static::$indexes,
				]
			);
			//--
			if(!$mongo->is_command_ok($result)) {
				\Smart::raise_error(__METHOD__.'() MongoDB Failed to Create Collection Indexes: '.\print_r($result,1));
				return (string) static::$collection;
			} //end if
			//--
		} //end if
		//--
		return (string) static::$collection;
		//--
	} //END FUNCTION


} //END CLASS


// #end of php code
