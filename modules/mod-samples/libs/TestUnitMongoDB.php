<?php
// [LIB - Smart.Framework / Samples / Test MongoDB Server]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitMongoDB
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\Samples;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Test TestUnitMongoDB Server
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20241220
 *
 */
final class TestUnitMongoDB {

	// ::

	//============================================================
	public static function testMongoServer() {

		//--
		if((!\defined('\\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS')) OR (\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS !== true)) {
			//--
			return (string) \SmartComponents::operation_notice('Test Unit for MongoDB Server is DISABLED ...');
			//--
		} //end if
		//--

		//--
		$cfg_mongo = (array) \Smart::get_from_config('mongodb', 'array');
		//--

		//--
		if(
			(\Smart::array_size($cfg_mongo) <= 0) OR
			(!isset($cfg_mongo['server-host'])) OR ((string)$cfg_mongo['server-host'] == '') OR
			(!isset($cfg_mongo['server-port'])) OR ((string)$cfg_mongo['server-port'] == '') OR
			(!isset($cfg_mongo['dbname'])) OR ((string)$cfg_mongo['dbname'] == '')
		) {
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for Mongo Server: INVALID MongoDB server configuration available in configs ...');
			//--
		} //end if
		//--

		//--
		$is_fatal_err = true; // this is default (for below operations wiwth try/catch the get/set Fatal ERR Mode is used)
		//--
		$mongo = new \SmartMongoDb((array)$cfg_mongo, $is_fatal_err);
		//--

		//--
		$time = \microtime(true);
		//--

		//--
		$dtime = \date('Y-m-d H:i:s');
		$comments = '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド'.'-'.\Smart::random_number(1000,9999)."'".'🚕🚓🚗🚑🚒🚒🚛🚜🚘🚔🚔🚖🚎🏍🛵🚲';
		//--

		//--
		$tests = array();
		$tests[] = '===== MongoDB [UTF8] / TESTS: =====';
		//--
		$err = '';
		//--

		//--
		$tests[] = 'MongoDB Extension Version: '.$mongo->get_ext_version();
		$tests[] = 'MongoDB Server Version: '.$mongo->get_server_version();
		//--

		//--
		if((string)$err == '') {
			$tst = 'Ping MongoDB';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'ping' => 1,
				]
			);
			if(!$mongo->is_command_ok($result)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[0/ok] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Create Mongo ObjectId from String';
			$tests[] = (string) $tst;
			$objStrId = '5f1330d499376783fb741802';
			$objId = $mongo->getObjectId((string)$objStrId);
			if(!\is_object($objId)) {
				$err = 'The Test: '.$tst.' FAILED for: '.$objStrId;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Drop Test Collection if Exists (if not exists just ignore the error)';
			$result = $mongo->igcommand(
				[
					'drop' => (string) 'myTestCollection'
				]
			);
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Create Test Collection';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'create' => (string) 'myTestCollection'
				]
			);
			if($mongo->is_command_ok($result)) {
				$tst = 'Create Collection Indexes (incl. Unique and FTS)';
				$tests[] = (string) $tst;
				$result = $mongo->igcommand(
					[
						'createIndexes' => (string) 'myTestCollection',
						'indexes' 		=> [
							[
								'name' 			=> 'id',
								'key' 			=> [ 'id' => 1 ],
								'unique' 		=> true
							],
							[
								'name' 				=> 'text',
								'sparse' 			=> false, // if true, the index only references documents with the specified field (use less space but behave differently in some situations)
								'key' 				=> [ 'title' => 'text', 'name' => 'text' ],
								'weights' 			=> [ 'title' => 10, 'name' => 5 ],
							//	'default_language' 	=> 'none', // must be a valid language code
								'language_override' => 'dictionary' // override field from document
							],
							[
								'name' 			=> 'cost',
								'key' 			=> [ 'cost' => -1 ]
							]
						]
					]
				);
				if(!$mongo->is_command_ok($result)) {
					$err = 'The Test: '.$tst.' FAILED ! Expected result of array[0/ok] should be 1 but is: '.\print_r($result,1);
				} //end if
			} else {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[0/ok] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Bulk Insert 10 Documents';
			$tests[] = (string) $tst;
			$docs = array();
			for($i=0; $i<10; $i++) {
				$docs[] = [
					'id'  => $mongo->assign_uuid(),
					'name' => 'Test #'.$i,
					'cost' => ($i+1),
					'data' => [
						'unicodeStr' => (string) $comments,
						'dTime' => (string) $dtime,
						'isBulk' => true,
						'rating' => \Smart::random_number(1,9) / 100
					]
				];
			} //end for
			$result = $mongo->bulkinsert('myTestCollection', (array)$docs);
			$docs = array();
			if($result[1] != 10) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 10 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		$uuid = $mongo->assign_uuid();
		if((string)$err == '') {
			$tst = 'Insert Single Document';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['id']  = $uuid;
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 0;
			$result = $mongo->insert('myTestCollection', (array)$doc);
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		if((string)$err == '') {
			$tst = 'Upsert Single Document, existing, with the same UUID as previous';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['id']  = $uuid;
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 0;
			$doc['upsert'] = 'update';
			$modeFatalErr = (bool) $mongo->getFatalErrMode(); // store the current Fatal Mode for MongoDB Class
			try {
				$result = $mongo->upsert(
					'myTestCollection',
					[ 'id' => $uuid ], 		// filter (update only this)
					'$set', 				// increment operation
					(array) $doc			// update array
				);
			} catch(\Exception $err) {
				\Smart::log_warning(__METHOD__.'() # MongoDB Upsert (#1) :: Exception: '.$err->getMessage());
			} //end try catch
			$mongo->setFatalErrMode((bool)$modeFatalErr); // restore the previous Fatal Mode for MongoDB Class
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		$uuid = $mongo->assign_uuid();
		if((string)$err == '') {
			$tst = 'Upsert Single Document, not existing, with a new UUID';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['id']  = $uuid;
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 0;
			$modeFatalErr = (bool) $mongo->getFatalErrMode(); // store the current Fatal Mode for MongoDB Class
			try {
				$result = $mongo->upsert(
					'myTestCollection',
					[ 'id' => $uuid ], 			// filter (update only this)
					[
						'$setOnInsert' => (array)$doc, // insert array
						'$set' => [ 'upsert' => 'insert' ] // update array
					]
				);
			} catch(\Exception $err) {
				\Smart::log_warning(__METHOD__.'() # MongoDB Upsert (#2) :: Exception: '.$err->getMessage());
			} //end try catch
			$mongo->setFatalErrMode((bool)$modeFatalErr); // restore the previous Fatal Mode for MongoDB Class
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Insert Another Single Document';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['id'] = $mongo->assign_uuid();
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 2;
			$result = $mongo->insert('myTestCollection', (array)$doc);
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Insert Another Single Document';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['_id'] = $mongo->assign_uuid();
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 3;
			$result = $mongo->insert('myTestCollection', (array)$doc);
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Update Replace the Last Inserted Single Document';
			$tests[] = (string) $tst;
			$oldId = (string) $doc['_id'];
			unset($doc['_id']); // the _id cannot be replaced and if contained in the doc array will simply fail
			$result = $mongo->update(
				'myTestCollection',
				[ '_id' => (string)$oldId ], // filter
				[ 0 => (array) $doc ] // replace
			);
			$oldId = null;
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Update No Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->update(
				'myTestCollection',
				[ 'name' => $comments ], 	// filter (update only this)
				'$set', 					// increment operation
				[ 							// update array
					'wrongupdate' => true
				]
			);
			if($result[1] !== 0) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 0 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Update Single Document by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->update(
				'myTestCollection',
				[ 'name' => 'Test:'.$comments, 'cost' => 0, 'upsert' => [ '$ne' => 'insert' ] ], // filter (update only this)
				'$inc', 										// increment operation
				[ 												// update array
					'cost' => (float) 1
				]
			);
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Update Many Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->update(
				'myTestCollection',
				[ 'name' => [ '$ne' => 'Test:'.$comments ], 'notexisting' => [ '$exists' => false ] ], 	// filter (update all except these)
				'$set', 										// upd. operation
				[ 												// update array
					'updated' => true
				]
			);
			if($result[1] != 10) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 10 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Delete Single Document by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->delete(
				'myTestCollection',
				[ 'name' => 'Test:'.$comments, 'cost' => 1 ] // filter
			);
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Delete Many Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->delete(
				'myTestCollection',
				[ 'name' => 'Test:'.$comments ] // filter
			);
			if($result[1] != 3) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 3 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Delete No Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->delete(
				'myTestCollection',
				[ 'name' => 'Test:'.$comments ] // filter
			);
			if($result[1] !== 0) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 0 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Insert Another Document';
			$tests[] = (string) $tst;
			$doc = array();
			$doc['id'] = $mongo->assign_uuid();
			$doc['title'] = 'This is a test ...';
			$doc['language'] = 'en';
			$doc['dictionary'] = (string) $mongo->getFtsDictionaryByLang((string)$doc['language']);
			$doc['name'] = 'Test:'.$comments;
			$doc['cost'] = 7;
			$result = $mongo->insert('myTestCollection', (array)$doc);
			$doc = array();
			if($result[1] != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[1] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Count Many Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->count(
				'myTestCollection',
				[ 'name' => [ '$ne' => 'Test:'.$comments ] ] // filter
			);
			if($result != 10) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of integer should be 10 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Count Single Document by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->count(
				'myTestCollection',
				[ 'name' => [ '$eq' => 'Test:'.$comments ] ] // filter
			);
			if($result != 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of integer should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Count No Documents by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->count(
				'myTestCollection',
				[ 'name' => [ '$eq' => 'Test:!' ] ] // filter
			);
			if($result !== 0) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of integer should be 0 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Find One Document by Filter';
			$tests[] = (string) $tst;
			$result = $mongo->findone(
				'myTestCollection',
				[ 'cost' => 7 ], // find filter
				[ // projection
					'id' => 1,
					'name' => 1,
					'cost' => 1
				],
				[
					'limit' => 2 // trying to fake the limit
				]
			);
			if((\Smart::array_size($result) <= 0) OR ((int)$result['cost'] != 7) OR ((string)\trim((string)$result['name']) == '')) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
			if((string)$err == '') {
				$encoding = (string) \SmartUnicode::detect_encoding((string)$result['name']);
				if((string)$encoding != (string)\SMART_FRAMEWORK_CHARSET) {
					$err = 'The Test: '.$tst.' FAILED ! Expected encoding is: `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`';
				} //end if
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Find Single Document by Filter and Limit / Offset';
			$tests[] = (string) $tst;
			$result = $mongo->find(
				'myTestCollection',
				[ 'cost' => 7 ], // filter
				[ // projection
					'id',
					'cost'
				],
				[
					'limit' => 1, // limit
					'skip' => 0 // offset
				]
			);
			if((\Smart::array_size($result) != 1) OR (\Smart::array_size($result[0]) <= 0) OR ($result[0]['cost'] != 7)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Find Single Document by Filter and Limit with No Projection';
			$tests[] = (string) $tst;
			$result = $mongo->find(
				'myTestCollection',
				[ 'cost' => 7 ], // filter
				[], // no projection
				[
					'limit' => 1
				]
			);
			if((\Smart::array_size($result) != 1) OR (\Smart::array_size($result[0]) <= 0) OR ($result[0]['cost'] != 7)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Find Many Documents by Filter with Sort';
			$tests[] = (string) $tst;
			$result = $mongo->find(
				'myTestCollection',
				[ 'cost' => [ '$gt' => 5 ] ], // filter
				[ // projection
					'id' => 1,
					'name' => 1,
					'cost' => [ '$slice' => -1 ]
				],
				[
					'limit' => 2, // trying to fake the limit
					'sort' => [ 'cost' => -1 ], // sort by cost descending
				]
			);
			if((\Smart::array_size($result) != 2) OR (\Smart::array_size($result[0]) <= 0) OR ($result[0]['cost'] != 10) OR (\Smart::array_size($result[1]) <= 0) OR ($result[1]['cost'] != 9)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'ReUse MongoDB Connection';
			$tests[] = (string) $tst;
			$mongo = null;
			$mongo = new \SmartMongoDb((array)$cfg_mongo);
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Aggregate Find / GroupBy with Filter, Sort, Limit and Projection';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'aggregate' => (string) 'myTestCollection',
					'pipeline' => [ // return a pipeline
						[
							'$match' => [ // query
								'id' => [ '$exists' => true ],
								'cost' => [ '$gte' => 0, '$lte' => 10 ]
							]
						],
						[
							'$project' => [ // projection
								'id' => '$id',
								'cost' => '$cost'
							]
						],
						[
							'$group' => [ // group
								'_id' => '$cost',
								'total' => ['$sum' => '$cost']
							]
						],
						[
							'$sort' => [ 'total' => -1 ] // sorting
						],
						[	'$limit' => 4 ], 	// limit results: in the case of aggregate this is limit(3) + skip(1) = 4
						[	'$skip' => 1 ] 		// offset (1)
					],
					'cursor' => [ 'batchSize' => 0 ] // this is required by MongoDB Server 3.6 and later
				]
			);
			if((\Smart::array_size($result) != 3) OR (\Smart::array_size($result[0]) <= 0) OR (\Smart::array_size($result[1]) <= 0) OR (\Smart::array_size($result[2]) <= 0)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Aggregate Count / GroupBy with Filter';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'aggregate' => (string) 'myTestCollection',
					'pipeline' => [ // return a pipeline
						[
							'$match' => [ 'id' => [ '$exists' => true ], 'cost' => 7 ] // query
						],
						[
							'$group' => [
								'_id'   => '$cost',
								'count' => [ '$sum' => 1 ]
							]
						],
						[	'$limit' => 100 ] // max results
					],
					'cursor' => [ 'batchSize' => 0 ] // this is required by MongoDB Server 3.6
				]
			);
			if((\Smart::array_size($result) != 1) OR (\Smart::array_size($result[0]) <= 0) OR ($result[0]['count'] != 2)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Search Distinct with Filter'; // this may not use limit / offset thus use Find / GroupBy (Aggregate) instead
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'distinct' => (string) 'myTestCollection',
					'key' => (string) 'cost',
					'query' => (array) ['cost' => ['$gte' => 6]]
				]
			);
			if((!$mongo->is_command_ok($result)) OR (\Smart::array_size($result[0]) <= 0) OR (\Smart::array_size($result[0]['values']) != 5)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'MapReduce with Limit and Sort';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'mapReduce' => 'myTestCollection',
					'map' => 'function() { emit(this.$cost, 1); }',
					'reduce' => 'function(k, vals) { var sum = 0; for (var i in vals) { sum += vals[i]; } return sum; }',
					'out' => [ 'inline' => 1 ], // required to return results instead to populate a destination collection with the mapReduce results
				//	'final' => '', // optional js function post reduce
					'query' => [ 'cost' => [ '$gte' => 7 ] ],
					'sort' => [ 'cost' => -1 ],
					'limit' => 10
				]
			);
			if((\Smart::array_size($result) != 1) OR (\Smart::array_size($result[0]) <= 0) OR (\Smart::array_size($result[0]['results']) <= 0) OR (\Smart::array_size($result[0]['results'][0]) <= 0) OR ($result[0]['results'][0]['value'] != 5)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Facet Aggregation with FTS (only for MongoDB Server >= 3.4 / MongoDB Extension >= 1.3)';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'aggregate' => (string) 'myTestCollection',
					'pipeline' => [ // return a pipeline
						[
							'$match' => [
								'id' => [ '$exists' => true ], // query
								'$text' => [ '$search' => 'test', '$language' => 'en' ] // FTS query
							]
						],
						[
							'$facet' => [ // $facet only works on MongoDB 3.4 or later !!
								'records@ALL' => [
									[
										'$project' => [
											'id' => true,
											'title' => true,
											'name' => true,
											'cost' => true,
											'language' => true,
											'dictionary' => true,
											'score' => [ '$meta' => 'textScore' ], // only for FTS
										]
									],
									[ '$sort' => [ 'score' => -1 ] ], // order desc by FTS score
									[ '$skip' => 0 ], // offset
									[ '$limit' => 100 ] // limit
								],
								'count@ALL' => [
									[ '$count' => 'total' ]
								],
								'countBy@COST' => [
									[ '$unwind' => '$cost' ],
									[ '$sortByCount' => '$cost' ]
								],
								'count@TITLE:#exists' => [
									[ '$match' => [ 'title' => [ '$exists' => true ] ] ],
									[ '$count' => 'count' ]
								],
								'count@TITLE:#!exists' => [
									[ '$match' => [ 'title' => [ '$exists' => false ] ] ],
									[ '$count' => 'count' ]
								]
							],
						]
					],
					'cursor' => [ 'batchSize' => 0 ] // this is required by MongoDB Server 3.6
				]
			);
			if((\Smart::array_size($result) != 1) OR (\Smart::array_size($result[0]) <= 0) OR (\Smart::array_size($result[0]['records@ALL']) != 11) OR (\Smart::array_size($result[0]['count@TITLE:#exists']) <= 0) OR (\Smart::array_size($result[0]['count@TITLE:#exists'][0]) <= 0) OR ($result[0]['count@TITLE:#exists'][0]['count'] != 1)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of one specific document but is different: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tst = 'Drop Test Collection';
			$tests[] = (string) $tst;
			$result = $mongo->command(
				[
					'drop' => (string) 'myTestCollection'
				]
			);
			if(!$mongo->is_command_ok($result)) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result of array[0/ok] should be 1 but is: '.\print_r($result,1);
			} //end if
		} //end if
		//--

		//--
		$time = 'TOTAL TIME was: '.(\microtime(true) - $time);
		//--
		$end_tests = '===== END TESTS ... '.$time.' sec. =====';
		//--
		$img_check = 'lib/core/img/db/mongodb-logo.svg';
		if((string)$err == '') {
			$img_sign = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP MongoDB.</span>';
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework MongoDB Server Operations <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP MongoDB Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing MongoDB Server Operations.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'MongoDB Server Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework MongoDB Server Tests: DONE ...';
		//--

		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-samples/libs/templates/testunit/partials/test-dialog.inc.htm',
			[
				//--
				'TEST-HEADING' 		=> (string) $test_heading,
				//--
				'DIALOG-WIDTH' 		=> '725',
				'DIALOG-HEIGHT' 	=> '480',
				'IMG-SIGN' 			=> (string) $img_sign,
				'IMG-CHECK' 		=> (string) $img_check,
				'TXT-MAIN-HTML' 	=> (string) $text_main,
				'TXT-INFO-HTML' 	=> (string) $text_info,
				'TEST-INFO' 		=> (string) $test_info
				//--
			]
		);
		//--

	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
