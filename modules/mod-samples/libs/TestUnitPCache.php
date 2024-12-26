<?php
// [LIB - Smart.Framework / Samples / Test Persistent Cache]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitPCache
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
 * Test Persistent Cache
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20231029
 *
 */
final class TestUnitPCache {

	// ::

	//============================================================
	public static function testPersistentCache() {

		//--
		if((!\defined('\\SMART_FRAMEWORK_TESTUNIT_ALLOW_PCACHE_TESTS')) OR (\SMART_FRAMEWORK_TESTUNIT_ALLOW_PCACHE_TESTS !== true)) {
			//--
			return \SmartComponents::operation_notice('Test Unit for Persistent Cache is DISABLED ...');
			//--
		} //end if
		//--

		//--
		if(!\SmartPersistentCache::isActive()) {
			//--
			return (string) \SmartComponents::operation_warn('Test Unit for Persistent Cache: NO Active Persistent Cache configuration available in configs ...');
			//--
		} //end if
		//--
		$thePcacheVersionInfo = (string) \SmartPersistentCache::getVersionInfo();
		//--

		//--
		$the_test_realm = 'persistent@cache/test';
		//--
		$pcache_big_content = self::packTestArchive(); // CREATE THE Test Archive (time not counted)
		//--
		$pcache_mode = [];
		if(\SmartPersistentCache::isMemoryBased() === true) {
			$pcache_mode[] = 'Memory-Based';
		} //end if
		if(\SmartPersistentCache::isFileSystemBased() === true) {
			$pcache_mode[] = 'FileSystem-Based';
		} //end if
		if(\SmartPersistentCache::isDbBased() === true) {
			$pcache_mode[] = 'DB-Based';
		} //end if
		$pcache_mode = (string) implode(' + ', (array)$pcache_mode);
		//--
		if(\SmartPersistentCache::isMemoryBased() !== true) {
			$expire_wait_seconds = 7; // it is slower and seconds begins before writing which may take a second or so ...
		//	$pcache_big_content = (string) substr($pcache_big_content, 0, 65535); // avoid test with very big loads on non-memory based PCache Handlers
		} else {
			$expire_wait_seconds = 6; // should be enough for memory based
		} //end if
		//--
		$pcache_test_key = 'pcache-test-key_'.\SmartPersistentCache::safeKey(\Smart::uuid_10_num().'-'.\Smart::uuid_36((string)\Smart::net_server_id()).'-'.\Smart::uuid_45((string)\Smart::net_server_id()));
		$pcache_test_value = array(
			'unicode-test' => '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド'.'🚕🚓🚗🚑🚒🚒🚛🚜🚘🚔🚔🚖🚎🏍🛵🚲', // unicode value
			'big-key-test' => (string) $pcache_big_content, // a big key
			'random-key' => \Smart::uuid_10_str().'.'. \Smart::uuid_10_seq().'.'.\Smart::random_number(1000,9999) // a very random key
		);
		$pcache_test_checkum = \SmartHashCrypto::sha1(\implode("\n", (array)$pcache_test_value));
		$pcache_test_arch_content = \SmartPersistentCache::varCompress($pcache_test_value);
		$pcache_test_arch_checksum = \SmartHashCrypto::sha1($pcache_test_arch_content);
		//--

		//--
		if((stripos((string)$thePcacheVersionInfo, 'redis:') === 0) OR (stripos((string)$thePcacheVersionInfo, 'mongodb:') === 0) OR (stripos((string)$thePcacheVersionInfo, 'dba:') === 0)) {
			$test_scale = 'LARGE';
			$num_keys = 1000;
			$key_middle = 500;
			$key_last = 999;
		} else { // sqlite and the rest, only test 100 keys to avoid run out of resources ...
			$test_scale = 'SMALL';
			$num_keys = 100;
			$key_middle = 50;
			$key_last = 99;
		} //end if else
		//--

		//--
		$tests = array();
		$tests[] = '***** Persistent Cache Backend: ['.$thePcacheVersionInfo.'] *****';
		$tests[] = '*** Persistent Cache Storage: '.$pcache_mode.' # '.$test_scale.' SCALE Test ('.(int)$num_keys.') keys ***';
		$tests[] = '===== Persistent Cache / TESTS with a huge size Variable (String/Json) Key-Size of 2x'.\SmartUtils::pretty_print_bytes((int)\strlen($pcache_test_arch_content), 2).' : =====';
		//--
		if(stripos((string)$thePcacheVersionInfo, 'redis:') === 0) {
			$tests[] = '=== Redis Re-Use the connection Test (see the debug log ... it must have reuse it !) ===';
			$redis = new \SmartRedisDb( // TEST FOR REDIS TO REUSE THE CONNECTION (for the rest they are in their test libs, but for Redis implement it here ...)
				(string) \get_called_class(), 	// desc (late state binding to get this class or class that extends this)
				(bool)   false 					// fatal err
			); // use the connection values from configs
			$redis->get('fake-test-just-for-reuse-the-connection');
			$redis = null;
		} //end if
		//--
		$err = '';
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Building a Test Archive file for Persistent Cache Tests (time not counted)'; // archive was previous created, only test here
			if((string)$pcache_big_content == '') {
				$err = 'Failed to build the Test Archive file for the Persistent Cache Test (see the error log for more details) ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$cachePathPrefix = (string) \SmartPersistentCache::cachePathPrefix(3, $the_test_realm);
			$cacheTestPrefix = (string) \str_replace('/', '', (string)$cachePathPrefix);
			$tests[] = 'Get the Cache Prefix, must be exactly 3 alphanum chars containing 0..9 a..z separed by slash: `'.$cacheTestPrefix.'` (`'.$cachePathPrefix.'`)';
			if((\strlen((string)\trim((string)$cacheTestPrefix)) != 3) OR (!\preg_match('/^[a-z0-9]{3}$/', (string)$cacheTestPrefix))) {
				$err = 'The Cache Prefix `'.$cacheTestPrefix.'` must have only 3 alphanum chars as 0-9 a-z, separed by slashes (`'.$cachePathPrefix.'`)';
			} //end if
		} //end if
		//--

		//--
		if(\Smart::random_number(0,1) == 1) {
			$the_test_realm = '';
			$tests[] = 'The Testing Realm is EMPTY';
		} else {
			$tests[] = 'The Testing Realm is NON-EMPTY: `'.$the_test_realm.'`';
		} //end if
		//--

		//--
		$time = \microtime(true);
		//--
		$tests[] = '++ START Counter ...';
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Clearing All Data in Persistent Cache Store(s)';
			if(\SmartPersistentCache::clearData() !== true) {
				$err = 'Persistent Cache FAILED to Clear All Data in Persistent Cache Store(s)';
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Building the Cache Archive';
			if((string)$pcache_test_arch_content == '') {
				$err = 'Failed to build the Cache Variable(s) Archive file for the Persistent Cache Test (see the error log for more details) ...';
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Set a short Persistent Cache Key (auto-expire in 5 seconds)';
			$pcache_set_key = \SmartPersistentCache::setKey(
				$the_test_realm,
				$pcache_test_key,
				(string) $pcache_test_value['unicode-test'],
				5 // expire it after 5 seconds
			);
			if($pcache_set_key !== true) {
				$err = 'Persistent Cache SetKey (short) returned a non-true result: '."\n".$pcache_test_key;
			} //end if
			if((string)$err == '') {
				$tests[] = 'Get the TTL of the short Persistent Cache Key (auto-expire in 5 seconds)';
				$pcache_get_ttl = \SmartPersistentCache::getTtl(
					$the_test_realm,
					$pcache_test_key
				);
				if(((int)$pcache_get_ttl < 1) OR ((int)$pcache_get_ttl > 5)) {
					$err = 'Persistent Cache getTtl (short) returned an invalid result: '."\n".$pcache_get_ttl;
				} //end if
			} //end if
			if((string)$err == '') {
				$tests[] = 'Wait '.(int)$expire_wait_seconds.' seconds for Persistent Cache Key to expire, then check again if exists (time not counted)';
				sleep((int)$expire_wait_seconds); // wait the Persistent Cache Key to Expire
				$time = (float) ((float)$time + (int)$expire_wait_seconds); // ignore those wait seconds (waiting time) to fix counter
				$tests[] = '-- FIX Counter (substract the the waiting time: '.(int)$expire_wait_seconds.' seconds) ...';
				if(\SmartPersistentCache::keyExists($the_test_realm, $pcache_test_key)) {
					$err = 'Persistent Cache (short) Key does still exists (but should be expired after '.(int)$expire_wait_seconds.' seconds) and is not: '."\n".$pcache_test_key;
				} //end if
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Set a long Persistent Cache Key (will not expire)';
			$pcache_set_key = \SmartPersistentCache::setKey(
				$the_test_realm,
				$pcache_test_key,
				$pcache_test_arch_content
			);
			if($pcache_set_key !== true) {
				$err = 'Persistent Cache SetKey (long) returned a non-true result: '."\n".$pcache_test_key;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Check if Persistent Cache Key exists (after set)';
			if(!\SmartPersistentCache::keyExists($the_test_realm, $pcache_test_key)) {
				$err = 'Persistent Cache Key does not exists: '."\n".$pcache_test_key;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Set a Persistent Cache Key with Empty Realm (will expire after 30 seconds)';
			$pcache_set_rxkey = \SmartPersistentCache::setKey(
				'',
				'No-Realm-'.$pcache_test_key,
				\date('Y-m-d H:i:s'),
				30
			);
			if($pcache_set_rxkey !== true) {
				$err = 'Persistent Cache SetKey with Empty Realm returned a non-true result: '."\n".'No-Realm-'.$pcache_test_key;
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Get Persistent Cache Key';
			$getval = (string) \SmartPersistentCache::getKey($the_test_realm, $pcache_test_key);
			$encoding = (string) \SmartUnicode::detect_encoding((string)$getval);
			if((string)$encoding != (string)\SMART_FRAMEWORK_CHARSET) {
				$err = 'Persistent Cache Key Encoding Check FAILED (1) ! Expected encoding is: `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`';
			} else {
				$pcache_cached_value = \SmartPersistentCache::varUncompress((string)$getval);
				if(\Smart::array_size($pcache_cached_value) > 0) {
					$tests[] = 'Check if Persistent Cache Key is valid (array-keys)';
					$encoding = (string) \SmartUnicode::detect_encoding((string)$pcache_cached_value['unicode-test']);
					if((string)$encoding != (string)\SMART_FRAMEWORK_CHARSET) {
						$err = 'Persistent Cache Key Encoding Check FAILED (2) ! Expected encoding is: `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`';
					} else {
						if(((string)$pcache_cached_value['unicode-test'] != '') AND ((string)$pcache_cached_value['big-key-test'] != '')) {
							$tests[] = 'Check if Persistent Cache Key is valid (checksum)';
							if((string)\SmartHashCrypto::sha1((string)\implode("\n", (array)$pcache_cached_value)) == (string)$pcache_test_checkum) {
								if($pcache_test_value === $pcache_cached_value) {
									$tests[] = 'Unset Persistent Cache Key';
									$pcache_unset_key = \SmartPersistentCache::unsetKey($the_test_realm, $pcache_test_key);
									if($pcache_unset_key === true) {
										$tests[] = 'Check if Persistent Cache Key exists (after unset)';
										if(\SmartPersistentCache::keyExists($the_test_realm, $pcache_test_key)) {
											$err = 'Persistent Cache Key does exists (after unset) and should not: '."\n".$pcache_test_key;
										} else {
											// OK
										} //end if
									} else {
										$err = 'Persistent Cache UnSetKey returned a non-true result: '."\n".$pcache_test_key;
									} //end if else
								} else {
									$err = 'Persistent Cache Cached Value is broken: comparing stored value with original value failed on key: '."\n".$pcache_test_key;
								} //end if else
							} else {
								$err = 'Persistent Cache Cached Value is broken: checksum failed on key: '."\n".$pcache_test_key;
							} //end if else
						} else {
							$err = 'Persistent Cache Cached Value is broken: array-key is missing after Cache-Variable-Unarchive on key: '."\n".$pcache_test_key;
						} //end if
					} //end if else
				} else {
					$err = 'Persistent Cache Cached Value is broken: non-array value was returned after Cache-Variable-Unarchive on key: '."\n".$pcache_test_key;
				} //end if
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$tests[] = 'Set '.(int)$num_keys.' Persistent Cache Keys with Realm';
			for($i=0; $i<(int)$num_keys; $i++) {
				$pcache_set_multi = \SmartPersistentCache::setKey(
					$the_test_realm.'__MKeys',
					'Multi-'.$pcache_test_key.'#'.$i,
					\date('Y-m-d H:i:s')
				);
				if($pcache_set_multi !== true) {
					$err = 'Persistent Cache SetKey['.($i+1).'] with Realm returned a non-true result';
					break;
				} //end if
			} //end for
		} //end if
		if((string)$err == '') {
			$tests[] = 'Rewrite Set first 10 keys (each 5 times) Persistent Cache Keys with Realm';
			for($i=0; $i<10; $i++) {
				for($j=0; $j<5; $j++) {
					$pcache_set_multi = \SmartPersistentCache::setKey(
						$the_test_realm.'__MKeys',
						'Multi-'.$pcache_test_key.'#'.$i,
						\date('Y-m-d H:i:s')
					);
					if($pcache_set_multi !== true) {
						$err = 'Persistent Cache Rewrite SetKey['.($i+1).'] with Realm returned a non-true result';
						break;
					} //end if
				} //end for
			} //end for
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if FIRST Persistent Cache Key (from '.(int)$num_keys.') exists, before unset with wildcard (*)';
			if(!\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#0')) {
				$err = 'Persistent Cache Key does not exists and should: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#0';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if MIDDLE Persistent Cache Key (from '.(int)$num_keys.') exists, before unset with wildcard (*)';
			if(!\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_middle)) {
				$err = 'Persistent Cache Key does not exists and should: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#'.$key_middle;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Get the TTL of the MIDDLE Persistent Cache Key (from '.(int)$num_keys.') exists, before unset with wildcard (*)';
			$pcache_get_ttl = \SmartPersistentCache::getTtl($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_middle);
			if((int)$pcache_get_ttl != -1) { // does not expire
				$err = 'Persistent Cache Ttl returned an invalid result: '."\n".$pcache_get_ttl;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if LAST Persistent Cache Key (from '.(int)$num_keys.') exists, before unset with wildcard (*)';
			if(!\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_last)) {
				$err = 'Persistent Cache Key does not exists and should: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#'.$key_last;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Unset all '.(int)$num_keys.' Persistent Cache Keys with Realm, using wildcard (*)';
			$pcache_unset_multi = \SmartPersistentCache::unsetKey($the_test_realm.'__MKeys', '*');
			if($pcache_unset_multi !== true) {
				$err = 'Persistent Cache UnsetKeys[1..'.(int)$num_keys.'] using wildcard (*), with Realm returned a non-true result';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if FIRST Persistent Cache Key (from '.(int)$num_keys.') exists, after unset with wildcard (*)';
			if(\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#0')) {
				$err = 'Persistent Cache Key does exists and should not: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#0';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if MIDDLE Persistent Cache Key (from '.(int)$num_keys.') exists, after unset with wildcard (*)';
			if(\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_middle)) {
				$err = 'Persistent Cache Key does exists and should not: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#'.$key_middle;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Get the TTL of the MIDDLE Persistent Cache Key (from '.(int)$num_keys.') exists, after unset with wildcard (*)';
			$pcache_get_ttl = \SmartPersistentCache::getTtl($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_middle);
			if((int)$pcache_get_ttl != -2) { // does not exists
				$err = 'Persistent Cache Ttl returned an invalid result: '."\n".$pcache_get_ttl;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$tests[] = 'Check if LAST Persistent Cache Key (from '.(int)$num_keys.') exists, after unset with wildcard (*)';
			if(\SmartPersistentCache::keyExists($the_test_realm.'__MKeys', 'Multi-'.$pcache_test_key.'#'.$key_last)) {
				$err = 'Persistent Cache Key does exists and should not: '."\n".$the_test_realm.'__MKeys'.':'.'Multi-'.$pcache_test_key.'#'.$key_last;
			} //end if
		} //end if
		//--

		//--
		$time = 'TOTAL TIME (Except building the test archive, Except wait expire) was: '.\Smart::format_number_dec((\microtime(true) - $time), 11, '.', '');
		//--
		$end_tests = '===== END TESTS ... ====='."\n".'=== '.$time.' sec. ===';
		//--
		if(stripos((string)$thePcacheVersionInfo, 'redis:') === 0) {
			$img_check = 'lib/core/img/db/redis-logo.svg';
		} elseif(stripos((string)$thePcacheVersionInfo, 'mongodb:') === 0) {
			$img_check = 'lib/core/img/db/mongodb-logo.svg';
		} elseif(stripos((string)$thePcacheVersionInfo, 'dba:') === 0) {
			$img_check = 'lib/core/img/db/dba-logo.svg';
		} elseif(stripos((string)$thePcacheVersionInfo, 'sqlite:') === 0) {
			$img_check = 'lib/core/img/db/sqlite-logo.svg';
		} else {
			$img_check = 'lib/core/img/app/server.svg';
		} //end if else
		if((string)$err == '') {
			$img_sign = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP PersistentCache.</span>';
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework PersistentCache Server Operations <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP PersistentCache Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing PersistentCache Server Operations.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'Persistent Cache Server Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework Persistent Cache Server Tests: DONE ...';
		//--

		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-samples/libs/templates/testunit/partials/test-dialog.inc.htm',
			[
				//--
				'TEST-HEADING' 		=> (string) $test_heading,
				//--
				'DIALOG-WIDTH' 		=> '725',
				'DIALOG-HEIGHT' 	=> '425',
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


	//============================================================
	private static function packTestArchive($y_exclusions_arr='') {
		//--
		$the_test_file = 'modules/mod-samples/libs/TestUnitPCache.php';
		//--
		$testsrcfile = (string) \SmartFileSystem::read((string)$the_test_file);
		$out = '';
		if((string)$testsrcfile != '') {
			//--
			$testsrcfile = (string) \base64_encode((string)$testsrcfile);
			$vlen = \Smart::random_number(100000,900000);
			//--
			while(\strlen((string)$out) < (8388608 + $vlen)) {
				$randomizer = (string) '#'.\Smart::random_number().'#'."\n";
				$testfile = \SmartUtils::data_archive((string)$randomizer.$testsrcfile);
				if(\SmartHashCrypto::sha1((string)\SmartUtils::data_unarchive((string)$testfile)) !== \SmartHashCrypto::sha1((string)$randomizer.$testsrcfile)) {
					\Smart::log_warning('Data Unarchive Failed for Pack Test Archive ...');
					return 'Data Unarchive Failed for Pack Test Archive !';
				} //end if
				$out .= (string) $testfile;
			} //end if
			//--
		} else {
			//--
			\Smart::log_warning('Failed to read the test file: '.$the_test_file);
			return 'ERROR: Cannot Get File Read for this test !';
			//--
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
