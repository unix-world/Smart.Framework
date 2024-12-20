<?php
// [LIB - Smart.Framework / Samples / Test PostgreSQL Server]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitPgSQL
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
 * Test PostgreSQL Server
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20241220
 *
 */
final class TestUnitPgSQL {

	// ::

	//============================================================
	public static function testPgServer() {

		//--
		if((!\defined('\\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS')) OR (\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS !== true)) {
			//--
			return (string) \SmartComponents::operation_notice('Test Unit for PostgreSQL Server is DISABLED ...');
			//--
		} //end if
		//--

		//--
		$cfg_pgsql = (array) \Smart::get_from_config('pgsql', 'array');
		$cfg_pgsql = (array) \SmartPgsqlDb::conform_config_array((array)$cfg_pgsql);
		//--

		//--
		if(
			(\Smart::array_size($cfg_pgsql) <= 0) OR
			(!isset($cfg_pgsql['server-host'])) OR ((string)$cfg_pgsql['server-host'] == '') OR
			(!isset($cfg_pgsql['server-port'])) OR ((string)$cfg_pgsql['server-port'] == '') OR
			(!isset($cfg_pgsql['dbname'])) OR ((string)$cfg_pgsql['dbname'] == '') OR
			(!isset($cfg_pgsql['username'])) OR ((string)$cfg_pgsql['username'] == '')
		) {
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for PgSQL Server: INVALID PostgreSQL server configuration available in configs ...');
			//--
		} //end if
		//--

		//--
		$time = \microtime(true);
		//--

		//--
		$value = \date('Y-m-d H:i:s');
		$comments = '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド'.'-'.\Smart::random_number(1000,9999)."'".'🚕🚓🚗🚑🚒🚒🚛🚜🚘🚔🚔🚖🚎🏍🛵🚲';
		//--

		//--
		$tests = array();
		$tests[] = '===== PostgreSQL [UTF8] / TESTS: =====';
		//--
		$err = '';
		//--

		//--
		if(\Smart::random_number(1,9) >= 5) {
			$tests[] = 'Random Test Dynamic Connection: Open / Drop Test Table (If Exists)';
			$pgsql = new \SmartPgsqlExtDb((array)$cfg_pgsql);
			$pgsql->write_data('DROP TABLE IF EXISTS "public"."_test_unit_db_server_tests"');
			unset($pgsql);
		} //end if
		//--

		//--
		$tests[] = 'PostgreSQL Server Version: '.\SmartPgsqlDb::check_server_version();
		//--

		//--
		$tests[] = 'Start Transaction';
		\SmartPgsqlDb::write_data('BEGIN');
		//--

		//--
		$tests[] = 'Create a Temporary Table for this Test, after transaction to test DDL';
		if(\SmartPgsqlDb::check_if_table_exists('_test_unit_db_server_tests', 'public') == 1) {
			\SmartPgsqlDb::write_data('DROP TABLE "public"."_test_unit_db_server_tests"');
		} //end if
		\SmartPgsqlDb::write_data('CREATE TABLE "public"."_test_unit_db_server_tests" ( "variable" character varying(100) NOT NULL, "value" character varying(16384) DEFAULT \'\'::character varying, "comments" text DEFAULT \'\'::text NOT NULL, "a_null_column" text DEFAULT NULL, CONSTRAINT _test_unit_db_server_tests__check__variable CHECK ((char_length((variable)::text) >= 1)), CONSTRAINT _test_unit_db_server_tests__uniq__variable UNIQUE(variable) )');
		//--

		//--
		$variable = '"'.'Ș'."'".\substr(\SmartPgsqlDb::new_safe_id('uid15seq', 'variable', '_test_unit_db_server_tests', 'public'), 3, 5).\substr(\SmartPgsqlDb::new_safe_id('uid12seq', 'variable', '_test_unit_db_server_tests', 'public'), 3, 2);
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Check if the Test Table exists [ Positive ; Variable is: '.$variable.' ]';
			$data = \SmartPgsqlDb::check_if_table_exists('_test_unit_db_server_tests', 'public');
			if($data !== 1) {
				$err = 'Table Creation FAILED ... Table does not exists in the `public` schema ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Insert Ignore ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(['variable'=>$variable, 'value'=>$value, 'comments'=>$comments], 'insert'); //.' RETURNING "value", "variable"'; // write_igdata() does not yet support RETURNING as it runs in an anonymous code block that cannot return
			$data = \SmartPgsqlDb::write_igdata($quer_str, 'Test Write Insert');
			if($data[1] !== 1) {
				$err = 'Write / Insert Ignore Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		if((string)$err == '') {
			$tests[] = 'Truncate Table';
			$quer_str = 'TRUNCATE TABLE "public"."_test_unit_db_server_tests"';
			$data = \SmartPgsqlDb::write_data($quer_str, 'Test Write Insert');
			if($data[1] !== 0) {
				$err = 'Truncate Table Test Failed, should return 0 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Insert, w. returning Data ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(['variable'=>$variable, 'value'=>$value, 'comments'=>$comments], 'insert').' RETURNING "variable", "value"';
			$data = \SmartPgsqlDb::write_data($quer_str, 'Test Write Insert');
			if($data[1] !== 1) {
				$err = 'Write / Insert Test Failed, should return 1 but returned: '.$data[1];
			} //end if
			if((string)$err == '') {
				if(\Smart::array_size($data[2]) !== 2) {
					$err = 'Write / Insert Test Failed, returning data is invalid: '.print_r($data[2],1);
				} //end if
			} //end if
			if((string)$err == '') {
				if((string)$data[2][0] != (string)$variable) {
					$err = 'Write / Insert Test Failed, returning data[0] should be: '.$variable.' but is: '.$data[2][0];
				} //end if
			} //end if
			if((string)$err == '') {
				if((string)$data[2][1] != (string)$value) {
					$err = 'Write / Insert Test Failed, returning data[0] should be: '.$value.' but is: '.$data[2][1];
				} //end if
			} //end if
		} //end if
		if((string)$err == '') {
			$tests[] = 'Truncate Table';
			$quer_str = 'TRUNCATE TABLE "public"."_test_unit_db_server_tests"';
			$data = \SmartPgsqlDb::write_data($quer_str, 'Test Write Insert');
			if($data[1] !== 0) {
				$err = 'Truncate Table Test Failed, should return 0 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Insert ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(['variable'=>$variable, 'value'=>$value, 'comments'=>$comments], 'insert'); //.' RETURNING "value", "variable"';
			$data = \SmartPgsqlDb::write_data($quer_str, 'Test Write Insert');
			if($data[1] !== 1) {
				$err = 'Write / Insert Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Insert if not exists, with Insert-SubSelect ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(array('variable'=>$variable, 'value'=>$value, 'comments'=>$comments), 'insert-subselect');
			$data = \SmartPgsqlDb::write_igdata($quer_str);
			if($data[1] !== 0) {
				$err = 'Write / Insert if not exists with insert-subselect Test Failed, should return 0 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Update w. Prepare Statement ]';
			$quer_str = 'UPDATE "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(['variable'=>$variable, 'value'=>$value, 'comments'=>$comments], 'update');
			$data = \SmartPgsqlDb::write_data($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Update Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Update ]';
			$quer_str = 'UPDATE "public"."_test_unit_db_server_tests" SET "comments" = $2 WHERE ("variable" = $1)';
			$data = \SmartPgsqlDb::write_data($quer_str, [ $variable, $comments ]);
			if($data[1] !== 1) {
				$err = 'Write / Update Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write Ignore [ Update w. Params and Title ]';
			$quer_str = 'UPDATE "public"."_test_unit_db_server_tests" SET "comments" = $2 WHERE ("variable" = $1)';
			$data = \SmartPgsqlDb::write_igdata($quer_str, [ '@title' => 'Write Ignore Test with Params and Title', '@params' => [ $variable, $comments ] ]);
			if($data[1] !== 1) {
				$err = 'Write Ignore / Update w. Params and Title Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Update w. Params and Title ]';
			$quer_str = 'UPDATE "public"."_test_unit_db_server_tests" SET "comments" = $2 WHERE ("variable" = $1)';
			$data = \SmartPgsqlDb::write_data($quer_str, [ '@title' => 'Write Test with Params and Title', '@params' => [ $variable, $comments ] ]);
			if($data[1] !== 1) {
				$err = 'Write / Update w. Params and Title Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Count [ One Row ]';
			$quer_str = 'SELECT COUNT(1) FROM "public"."_test_unit_db_server_tests" WHERE (("variable" = $1) AND ("comments" = '.\SmartPgsqlDb::escape_literal($comments).'))';
			$data = \SmartPgsqlDb::count_data($quer_str, [ $variable ]);
			if($data !== 1) {
				$err = 'Count Test Failed, should return 1 but returned: '.$data;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Count [ One Row w. Params and Title ]';
			$quer_str = 'SELECT COUNT(1) FROM "public"."_test_unit_db_server_tests" WHERE (("variable" = $1) AND ("comments" = '.\SmartPgsqlDb::escape_literal($comments).'))';
			$data = \SmartPgsqlDb::count_data($quer_str, [ '@title' => 'Count One Row Test with Params and Title', '@params' => [ $variable ] ]);
			if($data !== 1) {
				$err = 'Count Test w. Params and Title Failed, should return 1 but returned: '.$data;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Read [ LIKE / Literal Escape +% ]';
			$data = \SmartPgsqlDb::read_adata('SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" LIKE '.\SmartPgsqlDb::escape_literal('%'.$variable.'_%', 'likes').')');
			if(\Smart::array_size($data) !== 0) {
				$err = 'Read Like / Literal-Escape +% Test Failed, should return 0 rows but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ LIKE / Literal Escape ]';
			$data = \SmartPgsqlDb::read_adata('SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" LIKE '.\SmartPgsqlDb::escape_literal('%'.$variable.'%').')');
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read Like / Literal-Escape Test Failed, should return 1 row but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ ILIKE / Str Escape Likes ]';
			$data = \SmartPgsqlDb::read_adata('SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" ILIKE \''.'%'.\SmartPgsqlDb::escape_str($variable, 'likes').'%'.'\')');
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read Like / Str-Escape Likes Test Failed, should return 1 rows but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		if((string)$err == '') { // the ~ and ~* fail with double quotes " in a string ; don;t know how to escape, so make this a negative test :-)
			$tests[] = 'Read [ ~* / Str Escape Regex ]';
			$data = \SmartPgsqlDb::read_adata('SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" ~* '.'\'\\y'.\SmartPgsqlDb::escape_str('%'.$variable.'\'\' . \\ + * ? [ ^ ] $ ( ) { } = ! < > | : -', 'regex').'\\y\''.')');
			if(\Smart::array_size($data) !== 0) {
				$err = 'Read ~* / Str-Escape Regex Test Failed, should return 0 rows but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ LIKE / Str Escape ]';
			$data = \SmartPgsqlDb::read_adata('SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" ILIKE \''.\SmartPgsqlDb::escape_str('%'.$variable.'%').'\')');
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read Like / Str-Escape Test Failed, should return 1 row but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ IN (SELECT) ]';
			$quer_str = 'SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" '.\SmartPgsqlDb::prepare_statement(array('\'a', '"b"', '3^$', $variable, '@?%'), 'in-select').') LIMIT 100 OFFSET 0';
			$data = \SmartPgsqlDb::read_adata($quer_str);
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read IN SELECT Test Failed, should return 1 row but returned: '.\Smart::array_size($data).' rows ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ (DATA) ARRAY[] ]';
			$quer_str = 'SELECT * FROM "public"."_test_unit_db_server_tests" WHERE ("variable" = ANY('.\SmartPgsqlDb::prepare_statement(array('\'a', '"b"', '3^$', $variable, '@?%'), 'data-array').')) LIMIT 100 OFFSET 0';
			$data = \SmartPgsqlDb::read_adata($quer_str);
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read (DATA) ARRAY[] Test Failed, should return 1 row but returned: '.\Smart::array_size($data).' rows ...';
			} //end if
		} //end if
		//--

		//--
		$quer_str = 'SELECT "comments", "a_null_column" FROM "public"."_test_unit_db_server_tests" WHERE ("variable" = $1) LIMIT 1 OFFSET 0';
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Non-Associative + Param Query $ ]';
			$data = \SmartPgsqlDb::read_data($quer_str, array($variable));
			if((string)\trim($data[0]) !== (string)$comments) {
				$err = 'Read / Non-Associative Test #1 Failed, should return `'.$comments.'` but returned `'.$data[0].'`';
			} //end if
			if((string)$err == '') {
				if(!\array_key_exists('1', (array)$data)) {
					$err = 'Read / Non-Associative Test #1 Failed by testing null field column';
				} //end if
			} //end if
			if((string)$err == '') {
				if($data[1] !== null) {
					$err = 'Read / Non-Associative Test #1 Failed by testing null field column value';
				} //end if
			} //end if
		} //end if
		if((string)$err == '') {
			$tests[] = 'Read [ Non-Associative + Prepare Param Query $ ]';
			$param_query = \SmartPgsqlDb::prepare_param_query((string)$quer_str, [$variable]);
			$data = \SmartPgsqlDb::read_data((string)$param_query, 'Test Param Query');
			if((string)\trim($data[0]) !== (string)$comments) {
				$err = 'Read / Non-Associative Test #2 Failed, should return `'.$comments.'` but returned `'.$data[0].'`';
			} //end if
		} //end if
		//-- with ? as param is only possible using prepare_param_query()
		if((string)$err == '') {
			$tests[] = 'Read [ Non-Associative + Prepare Param Query ? ]';
			$data = \SmartPgsqlDb::read_data($quer_str, array($variable));
			$param_query = \str_replace('$1', '?', $quer_str); // convert $1 to ?
			$param_query = \SmartPgsqlDb::prepare_param_query((string)$param_query, array($variable));
			$data = \SmartPgsqlDb::read_data($param_query, 'Test Param Query');
			if((string)\trim($data[0]) !== (string)$comments) {
				$err = 'Read / Non-Associative Test #3 Failed, should return `'.$comments.'` but returned `'.$data[0].'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Associative: One-Row ]';
			$data = \SmartPgsqlDb::read_asdata($quer_str, [ $variable ]);
			if((string)\trim($data['comments']) !== (string)$comments) {
				$err = 'Read / Associative / One-Row Test Failed, should return `'.$comments.'` but returned `'.$data['comments'].'`';
			} //end if
			if((string)$err == '') {
				if(!\array_key_exists('a_null_column', (array)$data)) {
					$err = 'Read / Associative / One-Row Test Failed by testing null field column';
				} //end if
			} //end if
			if((string)$err == '') {
				if($data['a_null_column'] !== null) {
					$err = 'Read / Associative / One-Row Test Failed by testing null field column value';
				} //end if
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Associative: One-Row w. Params and Title ]';
			$data = \SmartPgsqlDb::read_asdata($quer_str, [ '@title' => 'Read Associative One-Row with Params and Title', '@params' => [ $variable ] ]);
			if(\Smart::array_size($data) <= 0) {
				$err = 'Read / Associative / One-Row Test Returns No Data';
			} //end if
			if((string)$err == '') {
				$encoding = (string) \SmartUnicode::detect_encoding((string)$data['comments']);
				if((string)$encoding != (string)\SMART_FRAMEWORK_CHARSET) {
					$err = 'Read / Associative / One-Row Test w. Params and Title Failed, encoding is `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`';
				} //end if
			} //end if
			if((string)$err == '') {
				if((string)\trim((string)$data['comments']) !== (string)$comments) {
					$err = 'Read / Associative / One-Row Test w. Params and Title Failed, should return `'.$comments.'` but returned `'.$data['comments'].'`';
				} //end if
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Associative: Multi-Rows ]';
			$data = \SmartPgsqlDb::read_adata($quer_str, [ $variable ]);
			if(!\array_key_exists('0', (array)$data)) {
				$err = 'Read / Associative / Multi-Rows Test Failed by testing 1st line';
			} //end if
			if((string)$err == '') {
				if((string)\trim((string)$data[0]['comments']) !== (string)$comments) {
					$err = 'Read / Associative / Multi-Rows Test Failed, should return `'.$comments.'` but returned `'.$data[0]['comments'].'`';
				} //end if
			} //end if
			if((string)$err == '') {
				if(!\array_key_exists('a_null_column', (array)$data[0])) {
					$err = 'Read / Associative / Multi-Rows Test Failed by testing null field column';
				} //end if
			} //end if
			if((string)$err == '') {
				if($data[0]['a_null_column'] !== null) {
					$err = 'Read / Associative / Multi-Rows Test Failed by testing null field column value';
				} //end if
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Associative: Multi-Rows w. Params and Title ]';
			$data = \SmartPgsqlDb::read_adata($quer_str, [ '@title' => 'Read Associative Multi-Rows with Params and Title', '@params' => [ $variable ] ]);
			if((string)\trim($data[0]['comments']) !== (string)$comments) {
				$err = 'Read / Associative / Multi-Rows Test w. Params and Title Failed, should return `'.$comments.'` but returned `'.$data[0]['comments'].'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Non-Associative: Multi-Rows w. Params and Title ]';
			$data = \SmartPgsqlDb::read_data($quer_str, [ '@title' => 'Read Non-Associative Multi-Rows with Params and Title', '@params' => [ $variable ] ]);
			if((string)\trim($data[0]) !== (string)$comments) {
				$err = 'Read / Non-Associative / Multi-Rows Test w. Params and Title Failed, should return `'.$comments.'` but returned `'.$data[0]['comments'].'`';
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Delete ]';
			$quer_str = 'DELETE FROM "public"."_test_unit_db_server_tests" WHERE ("variable" = \''.\SmartPgsqlDb::escape_str($variable).'\')';
			$data = \SmartPgsqlDb::write_data($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Delete Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write Ignore Duplicates [ Insert Ignore Positive ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(array('variable'=>$variable, 'value'=>null, 'comments'=>$comments), 'insert');
			$data = \SmartPgsqlDb::write_igdata($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Insert Ignore Positive Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write Ignore Duplicates [ Insert Ignore Negative ]';
			$quer_str = 'INSERT INTO "public"."_test_unit_db_server_tests" '.\SmartPgsqlDb::prepare_statement(array('variable'=>$variable, 'value'=>$value, 'comments'=>$comments), 'insert');
			$data = \SmartPgsqlDb::write_igdata($quer_str);
			if($data[1] !== 0) {
				$err = 'Write / Insert Ignore Negative Test Failed, should return 0 but returned: '.$data[1];
			} //end if
		} //end if
		//--

		//--
		$tests[] = 'Commit Transation';
		\SmartPgsqlDb::write_data('COMMIT');
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Check if the Test Table Exists (Param Query ?) after Drop [ Negative ], using a new Constructor (should be able to re-use connection)';
			$pgsql2 = new \SmartPgsqlExtDb((array)$cfg_pgsql);
			$pgsql2->write_data('DROP TABLE IF EXISTS "public"."_test_unit_db_server_tests"');
			$data = $pgsql2->check_if_table_exists('_test_unit_db_server_tests', 'public');
			$pgsql2->prepare_param_query('SELECT ?', array('\'1"'));
			unset($pgsql2);
			if($data == 1) {
				$err = 'Table Drop FAILED ... Table still exists ...';
			} //end if
		} //end if
		//--

		//--
		$time = 'TOTAL TIME was: '.(\microtime(true) - $time);
		//--
		$end_tests = '===== END TESTS ... '.$time.' sec. =====';
		//--
		$img_check = 'lib/core/img/db/postgresql-logo.svg';
		if((string)$err == '') {
			$img_sign = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP PostgreSQL.</span>';
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework PostgreSQL Server Operations <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP PostgreSQL Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing PostgreSQL Server Operations.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'PostgreSQL Server Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework PostgreSQL Server Tests: DONE ...';
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
