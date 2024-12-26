<?php
// [LIB - Smart.Framework / Samples / Test DBA DB]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitDbaDB
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
 * Test TestUnitDbaDB
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210526
 *
 */
final class TestUnitDbaDB {

	// ::

	//============================================================
	public static function testDbaDb() {

		//--
		if((!\defined('\\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS')) OR (\SMART_FRAMEWORK_TESTUNIT_ALLOW_DATABASE_TESTS !== true)) {
			//--
			return (string) \SmartComponents::operation_notice('Test Unit for DBA DB is DISABLED ...');
			//--
		} //end if
		//--

		//--
		$cfg_dba = (array) \Smart::get_from_config('dba');
		//--

		//--
		if(\Smart::array_size($cfg_dba) <= 0) {
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for Dba DB: EMPTY DBA config ...');
			//--
		} //end if
		//--

		//--
		if(\SmartDbaUtilDb::isDbaAndHandlerAvailable() !== true) { // test with the handler from cfg
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for Dba DB: INVALID DBA handler ['.$cfg_dba['handler'].'] in configs or PHP DBA Extension is missing ...');
			//--
		} //end if
		//--
		$theHandler = (string) \SmartDbaUtilDb::getDbaHandler();
		//--

		//--
		$dba = new \SmartDbaDb('tmp/testunit.dba', (string)__CLASS__);
		//--
		$dbPath = (string) $dba->getDbRealPath();
		//--

		//--
		$time = \microtime(true);
		//--

		//--
		$dtime = \date('Y-m-d H:i:s');
		$comments = '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド'.'-'.\Smart::random_number(1000,9999)."'".'🚕🚓🚗🚑🚒🚒🚛🚜🚘🚔🚔🚖🚎🏍🛵🚲';
		$fcontents = \SmartFileSystem::read('README.md');
		//--

		//--
		$tests = array();
		$tests[] = '===== Dba DB / TESTS: =====';
		//--
		$err = '';
		//--

		//--
		$tests[] = 'DBA Handler: `'.$theHandler.'`';
		//--
		$testKey = (string) 'A Key: '.$dtime;
		//--
		if((string)$err == '') {
			$tst = 'Get the Read DB Path: '.$dbPath;
			$tests[] = (string) $tst;
			$result = (string) $dbPath;
			if((string)$result == '') {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be Non-Empty STRING';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Truncate DB (Delete all records, if any, and re-init)';
			$tests[] = (string) $tst;
			$result = $dba->truncateDb(true);
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Get Key TTL for a Key that does not exists';
			$tests[] = (string) $tst;
			$result = $dba->getTtl($testKey);
			if($result !== -2) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be -2 but have: '.$result;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Check if a Key Exists (should not)';
			$tests[] = (string) $tst;
			$result = $dba->keyExists($testKey);
			if($result !== false) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be FALSE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Insert a Key 15 times in DB (not expire)';
			$tests[] = (string) $tst;
			for($i=0; $i<15; $i++) {
				if($i <= 0) {
					$result = $dba->setKey($testKey, $fcontents, 0); // no expire
				} elseif($i > 0 && $i <= 10) {
					$result = $dba->setKey($testKey.'#'.($i+1), $fcontents.'@'.$i, 0); // #2..#11, no expire
				} else {
					$result = $dba->setKey($testKey.'#'.($i+1), $fcontents.'@'.$i, 1); // #12..#16, expire in 1 sec
				} //end if else
				if($result !== true) {
					$err = 'The Test #'.($i+1).': '.$tst.' FAILED ! Expected result should be TRUE';
					break;
				} //end if
			} //end for
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Check if the Inserted Key Exists';
			$tests[] = (string) $tst;
			$result = $dba->keyExists($testKey);
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Get Key TTL for the inserted Key (which does not expire)';
			$tests[] = (string) $tst;
			$result = $dba->getTtl($testKey);
			if($result !== -1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be -1 but have: '.$result;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Update the same Key in DB (not expire)';
			$tests[] = (string) $tst;
			$result = $dba->setKey($testKey, $comments.'#', 0);
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Read the updated Key from DB (and compare with the written value)';
			$tests[] = (string) $tst;
			$result = $dba->getKey($testKey);
			if((string)$result !== (string)$comments.'#') {
				$err = 'The Test: '.$tst.' FAILED ! Expected result: `'.$comments.'#'.'` but have: `'.$result.'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Get Key TTL for the updated Key (which does not expire)';
			$tests[] = (string) $tst;
			$result = $dba->getTtl($testKey);
			if($result !== -1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be -1 but have: '.$result;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Update the same Key in DB (with an expiration value of 2s)';
			$tests[] = (string) $tst;
			$result = $dba->setKey($testKey, $comments.'@exp=2', 2);
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Get Key TTL for the updated Key (which does expire)';
			$tests[] = (string) $tst;
			$result = $dba->getTtl($testKey);
			if($result < 1) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be at least time now but have: '.$result;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Read the same Key from DB (and compare with the written value, should not be expired)';
			$tests[] = (string) $tst;
			$result = $dba->getKey($testKey);
			if((string)$result !== (string)$comments.'@exp=2') {
				$err = 'The Test: '.$tst.' FAILED ! Expected result: `'.$comments.'#'.'` but have: `'.$result.'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			sleep(2);
			$time = (float) $time + 2; // ignore those 2 seconds (waiting time) to fix counter
			$tst = 'Read the same Key from DB after waiting 2s (now should be expired)';
			$tests[] = (string) $tst;
			$result = $dba->getKey($testKey);
			if($result !== false) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result: Key Expired but have: `'.$result.'`';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Unset a Key in DB';
			$tests[] = (string) $tst;
			$result = $dba->unsetKey($testKey.'#2');
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Unset another Key in DB';
			$tests[] = (string) $tst;
			$result = $dba->unsetKey($testKey.'#3');
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Optimize DB';
			$tests[] = (string) $tst;
			$result = $dba->optimizeDb();
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Check if the Key Exists after Unset';
			$tests[] = (string) $tst;
			$result = $dba->keyExists($testKey);
			if($result === true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be FALSE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Clear All Expired Keys, limit to 5';
			$tests[] = (string) $tst;
			$result = $dba->clearExpiredKeys(5); // the rest will be cleared by next test
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Get a List will All Not Expired Keys'; // will also clear
			$tests[] = (string) $tst;
			$result = $dba->getKeysList();
			if(\Smart::array_size($result) != 8) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be ARRAY with SIZE 8 but is: '.\Smart::array_size($result);
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tst = 'Truncate DB (Delete all records and re-init)';
			$tests[] = (string) $tst;
			$result = $dba->truncateDb(true);
			if($result !== true) {
				$err = 'The Test: '.$tst.' FAILED ! Expected result should be TRUE';
			} //end if
		} //end if
		//--

		//--
		$time = 'TOTAL TIME (except wait expire) was: '.\Smart::format_number_dec((\microtime(true) - $time), 11, '.', '');
		//--
		$end_tests = '===== END TESTS ... '.$time.' sec. =====';
		//--
		$img_check = 'lib/core/img/db/dba-logo.svg';
		if((string)$err == '') {
			$img_sign = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP Dba DB ['.\Smart::escape_html(\SmartDbaUtilDb::getDbaHandler()).'].</span>'; // must show what is in config
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework Dba DB Operations <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP Dba DB Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing Dba DB Operations.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'Dba DB Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework Dba DB Tests: DONE ...';
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
