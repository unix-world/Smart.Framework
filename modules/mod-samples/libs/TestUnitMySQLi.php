<?php
// [LIB - Smart.Framework / Samples / Test MariaDB Server / MySQL]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitMySQLi
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
 * Test MariaDB Server / MySQL
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20241220
 *
 */
final class TestUnitMySQLi {

	// ::

	//============================================================
	public static function testMyServer() {

		//--
		if((!\defined('\\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS')) OR (\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS !== true)) {
			//--
			return (string) \SmartComponents::operation_notice('Test Unit for MariaDB Server / MySQL is DISABLED ...');
			//--
		} //end if
		//--

		//--
		$cfg_mysqli = (array) \Smart::get_from_config('mysqli', 'array');
		$cfg_mysqli = (array) \SmartMysqliDb::conform_config_array((array)$cfg_mysqli);
		//--

		//--
		if(
			(\Smart::array_size($cfg_mysqli) <= 0) OR
			(!isset($cfg_mysqli['server-host'])) OR ((string)$cfg_mysqli['server-host'] == '') OR
			(!isset($cfg_mysqli['server-port'])) OR ((string)$cfg_mysqli['server-port'] == '') OR
			(!isset($cfg_mysqli['dbname'])) OR ((string)$cfg_mysqli['dbname'] == '') OR
			(!isset($cfg_mysqli['username'])) OR ((string)$cfg_mysqli['username'] == '')
		) {
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for MySQLi: INVALID MariaDB Server / MySQL configuration available in configs ...');
			//--
		} //end if
		//--
		$cfg_mysqli['type'] = (string) \strtolower((string)\trim((string)($cfg_mysqli['type'] ?? null)));
		//--

		//--
		$connUnicode   = 'UTF8.MB4';
		$connCharset   = 'utf8mb4';
		$connCollation = 'utf8mb4_bin';
		switch((string)$cfg_mysqli['type']) {
			case 'mysql': // support for older servers
				$connUnicode   = 'UTF8';
				$connCharset   = 'utf8';
				$connCollation = 'utf8_bin';
				break;
			case 'mariadb': // support for modern servers
			default:
				$cfg_mysqli['type'] = 'mariadb';
		} //end switch

		//--
		$time = \microtime(true);
		//--

		//--
		$value = \date('Y-m-d H:i:s');
		$comments = '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド'.'-'.\Smart::random_number(1000,9999)."'";
		if((string)$cfg_mysqli['type'] == 'mariadb') {
			$comments .= '🚕🚓🚗🚑🚒🚒🚛🚜🚘🚔🚔🚖🚎🏍🛵🚲'; // UTF8.MB4 icons
		} //end if
		//--

		//--
		$tests = array();
		$tests[] = '===== '.ucfirst((string)$cfg_mysqli['type']).' Server ['.$connUnicode.'] / TESTS: =====';
		//--
		$err = '';
		//--

		//--
		if(\Smart::random_number(1,9) >= 5) {
			$tests[] = 'Random Test Dynamic Connection: Open / Drop Test Table (If Exists)';
			$mysqli = new \SmartMysqliExtDb((array)$cfg_mysqli);
			$mysqli->write_data('DROP TABLE IF EXISTS `_test_unit_db_server_tests`');
			unset($mysqli);
		} //end if
		//--

		//--
		$tests[] = 'Server Version: '.\SmartMysqliDb::check_server_version();
		//--

		//--
		$tests[] = 'Create a Temporary Table for this Test, before transaction (MySQL have No DDL support in transactions)';
		if(\SmartMysqliDb::check_if_table_exists('_test_unit_db_server_tests') == 1) {
			\SmartMysqliDb::write_data('DROP TABLE `_test_unit_db_server_tests`');
		} //end if
		\SmartMysqliDb::write_data('CREATE TABLE `_test_unit_db_server_tests` ( `id` int AUTO_INCREMENT, `variable` varchar(100) COLLATE '.$connCollation.' NOT NULL, `value` text CHARACTER SET '.$connCharset.' DEFAULT NULL, `comments` mediumtext CHARACTER SET '.$connCharset.' NOT NULL DEFAULT \'\', `a_null_column` text NULL, PRIMARY KEY (`id`), UNIQUE (`variable`) ) ENGINE=InnoDB DEFAULT CHARSET='.$connCharset.' COLLATE='.$connCollation.';');
		//--

		//--
		$tests[] = 'Start Transaction';
		\SmartMysqliDb::write_data('BEGIN'); // MySQL does not support DDLs in Transactions thus transaction here must be after table creation
		//--

		//--
		$variable = '"'.'Ș'."'".\substr(\SmartMysqliDb::new_safe_id('uid10seq', 'variable', '_test_unit_db_server_tests'), 3, 7);
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Check if the Test Table exists [ Positive ; Variable is: '.$variable.' ]';
			$data = \SmartMysqliDb::check_if_table_exists('_test_unit_db_server_tests');
			if($data !== 1) {
				$err = 'Table Creation FAILED ... Table does not exists ...';
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Insert, w. check on LastInsertID ]';
			$quer_str = 'INSERT INTO `_test_unit_db_server_tests` '.\SmartMysqliDb::prepare_statement(['variable'=>$variable, 'value'=>$time, 'comments'=>$comments], 'insert');
			$data = \SmartMysqliDb::write_data($quer_str, 'Test Write Insert');
			if($data[1] !== 1) {
				$err = 'Write / Insert Test Failed, should return 1 but returned: '.$data[1];
			} //end if
			if((string)$err == '') {
				if($data[2] != 1) { // test last inserted ID (as string)
					$err = 'Write / Insert Test Failed on getting LastInsertId, should return 1 but returned: '.$data[2];
				} //end if
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Update ]';
			$quer_str = 'UPDATE `_test_unit_db_server_tests` SET `value` = ?, `comments` = ? WHERE (`variable` = ?)';
			$data = \SmartMysqliDb::write_data($quer_str, [ $value, $comments, $variable ]);
			if($data[1] !== 1) {
				$err = 'Write / Update Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Update, w. Prepare Statement ]';
			$quer_str = 'UPDATE `_test_unit_db_server_tests` '.\SmartMysqliDb::prepare_statement(['variable'=>$variable, 'value'=>$time, 'comments'=>$comments], 'update');
			$data = \SmartMysqliDb::write_data($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Update Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Count [ One Row ]';
			$quer_str = 'SELECT COUNT(1) FROM `_test_unit_db_server_tests` WHERE ((`variable` = \''.\SmartMysqliDb::escape_str($variable).'\') AND (`comments` = \''.\SmartMysqliDb::escape_str($comments).'\'))';
			$data = \SmartMysqliDb::count_data($quer_str);
			if($data !== 1) {
				$err = 'Count Test Failed, should return 1 but returned: '.$data;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Read [ LIKE / Str Escape Likes ]';
			$data = \SmartMysqliDb::read_adata('SELECT * FROM `_test_unit_db_server_tests` WHERE (`variable` LIKE \''.'%'.\SmartMysqliDb::escape_str($variable, 'likes').'%'.'\')');
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read Like / Str-Escape Likes Test Failed, should return 1 rows but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		if((string)$err == '') {
			$tests[] = 'Read [ RLIKE / Str Escape ]';
			$data = \SmartMysqliDb::read_adata('SELECT * FROM `_test_unit_db_server_tests` WHERE (`variable` RLIKE \''.'%'.\SmartMysqliDb::escape_str('%'.$variable.'\'\' . \\ + * ? [ ^ ] $ ( ) { } = ! < > | : -').'%'.'\')');
			if(\Smart::array_size($data) !== 0) {
				$err = 'Read ~* / Str-Escape Test Failed, should return 0 rows but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ LIKE / Str Escape ]';
			$data = \SmartMysqliDb::read_adata('SELECT * FROM `_test_unit_db_server_tests` WHERE (`variable` LIKE \''.\SmartMysqliDb::escape_str('%'.$variable.'%').'\')');
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read Like / Str-Escape Test Failed, should return 1 row but returned: '.\Smart::array_size($data);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ IN (SELECT) ]';
			$quer_str = 'SELECT * FROM `_test_unit_db_server_tests` WHERE (`variable` '.\SmartMysqliDb::prepare_statement(array('\'a', '"b"', '3^$', $variable, '@?%'), 'in-select').') LIMIT 100 OFFSET 0';
			$data = \SmartMysqliDb::read_adata($quer_str);
			if(\Smart::array_size($data) !== 1) {
				$err = 'Read IN SELECT Test Failed, should return 1 row but returned: '.\Smart::array_size($data).' rows ...';
			} //end if
		} //end if
		//--

		//--
		$quer_str = 'SELECT `comments`, `a_null_column` FROM `_test_unit_db_server_tests` WHERE (`variable` = ?) LIMIT 1 OFFSET 0';
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Non-Associative + Param Query ? ]';
			$data = \SmartMysqliDb::read_data($quer_str, array($variable));
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
			$param_query = \SmartMysqliDb::prepare_param_query((string)$quer_str, [$variable]);
			$data = \SmartMysqliDb::read_data((string)$param_query, 'Test Param Query');
			if((string)\trim($data[0]) !== (string)$comments) {
				$err = 'Read / Non-Associative Test #2 Failed, should return `'.$comments.'` but returned `'.$data[0].'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Read [ Associative: One-Row ]';
			$data = \SmartMysqliDb::read_asdata($quer_str, [ $variable ]);

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
					$err = 'Read / Associative / One-Row Test Failed, should return `'.$comments.'` but returned `'.$data['comments'].'`';
				} //end if
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
			$tests[] = 'Read [ Associative: Multi-Rows ]';
			$data = \SmartMysqliDb::read_adata($quer_str, [ $variable ]);
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

		//--
		if((string)$err == '') {
			$tests[] = 'Write [ Delete ]';
			$quer_str = 'DELETE FROM `_test_unit_db_server_tests` WHERE (`variable` = \''.\SmartMysqliDb::escape_str($variable).'\')';
			$data = \SmartMysqliDb::write_data($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Delete Test Failed, should return 1 but returned: '.$data[1];
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write Ignore Duplicates [ Insert Ignore Positive, w. check on LastInsertID ]';
			$quer_str = 'INSERT IGNORE INTO `_test_unit_db_server_tests` '.\SmartMysqliDb::prepare_statement(array('variable'=>$variable, 'value'=>null, 'comments'=>$comments), 'insert');
			$data = \SmartMysqliDb::write_data($quer_str);
			if($data[1] !== 1) {
				$err = 'Write / Insert Ignore Positive Test Failed, should return 1 but returned: '.$data[1];
			} //end if
			if((string)$err == '') {
				if($data[2] != 2) { // test last inserted ID (as string)
					$err = 'Write / Insert Ignore Positive Test Failed on getting LastInsertId, should return 2 but returned: '.$data[2];
				} //end if
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Write Ignore Duplicates [ Insert Ignore Negative ]';
			$quer_str = 'INSERT IGNORE INTO `_test_unit_db_server_tests` '.\SmartMysqliDb::prepare_statement(array('variable'=>$variable, 'value'=>$value, 'comments'=>$comments), 'insert');
			$data = \SmartMysqliDb::write_data($quer_str);
			if($data[1] !== 0) {
				$err = 'Write / Insert Ignore Negative Test Failed, should return 0 but returned: '.$data[1];
			} //end if
		} //end if
		//--

		//--
		$tests[] = 'Commit Transation';
		\SmartMysqliDb::write_data('COMMIT');
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Check if the Test Table Exists (Param Query ?) after Drop [ Negative ], using a new Constructor (should be able to re-use connection)';
			$mysqli2 = new \SmartMysqliExtDb((array)$cfg_mysqli);
			$mysqli2->write_data('DROP TABLE IF EXISTS `_test_unit_db_server_tests`');
			$data = $mysqli2->check_if_table_exists('_test_unit_db_server_tests');
			$mysqli2->prepare_param_query('SELECT ?', array('\'1"'));
			unset($mysqli2);
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
		$img_check = 'lib/core/img/db/mysql-logo.svg';
		if((string)$err == '') {
			$img_sign = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP MySQLi.</span>';
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework MariaDB / MySQL Server Operations <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP MySQLi Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing MariaDB / MySQL Server Operations.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'MariaDB / MySQL Server Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework MariaDB / MySQL Server Tests: DONE ...';
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
