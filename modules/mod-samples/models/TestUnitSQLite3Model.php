<?php
// [MODEL - Smart.Framework / Samples / SQLite3 Model]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModDataModel\Samples\TestUnitSQLite3Model
// Type: Module Data Model
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModDataModel\Samples;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Sample Model / SQLite3 (Samples Module)
// DEPENDS:
//	* SmartUnicode::
//	* Smart::
//	* SmartSQliteDb->
//======================================================


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * SQLite3 Sample Model (for Testing)
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260118
 *
 */
final class TestUnitSQLite3Model {

	// ->

	private $connection = null;

	//============================================================
	public function __construct() {

		//--
		$this->connection = new \SmartSQliteDb('tmp/#test-db/testunit.sqlite'); // {{{SYNC-TMP-TEST-DB-PATH}}}
		//--
		$this->connection->open();
		//--

		//-- init (create) the sample tables if they do not exist
		$this->init_table_main_samples();
		$this->init_table_samples_countries();
		//--

		//--
		$this->custom_functions_tests();
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public function __destruct() {

		//--
		if(!$this->connection instanceof \SmartSQliteDb) {
			return;
		} //end if
		//--
		$this->connection->close(); // clean shutdown
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public function getConnection() {
		//--
		return $this->connection;
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public function getListDataAutocomplete($src, $limit) {

		//--
		$where = '';
		if((string)$src != '') {
			$where = ' WHERE name LIKE \''.$this->connection->escape_str((string)$src, 'likes').'%\' ESCAPE \''.$this->connection->likes_escaper().'\'';
		} //end if else
		//--

		//--
		return (array) $this->connection->read_adata('SELECT iso, name FROM sample_countries'.$where.' ORDER BY name ASC LIMIT '.(int)$limit.' OFFSET 0');
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public function getCountDataTable($src) {

		//--
		$where = (string) $this->buildWhereForDataTable($src);
		//--

		//--
		return (int) $this->connection->count_data('SELECT COUNT(1) FROM sample_countries'.$where);
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public function getListDataTable($src, $limit, $ofs, $sortby, $sortdir) {

		//--
		$where = (string) $this->buildWhereForDataTable($src);
		//--

		//--
		if((string)\strtoupper((string)$sortdir) == 'DESC') {
			$syntax_sort_dir = 'DESC';
		} else {
			$syntax_sort_dir = 'ASC';
		} //end if else
		//--
		$syntax_sort_mode = '';
		switch((string)\strtolower((string)$sortby)) {
			case 'iso':
				$syntax_sort_mode = ' ORDER BY `iso` '.$syntax_sort_dir;
				break;
			case 'name':
				$syntax_sort_mode = ' ORDER BY `name` '.$syntax_sort_dir;
				break;
			case 'iso3':
				$syntax_sort_mode = ' ORDER BY `iso3` '.$syntax_sort_dir;
				break;
			case 'numcode':
				$syntax_sort_mode = ' ORDER BY `numcode` '.$syntax_sort_dir;
				break;
			default:
				$syntax_sort_mode = '';
		} //end switch
		//--

		//-- special escaping testing: ` or no escaping is mandatory field ; " is optional field
		return (array) $this->connection->read_adata('SELECT `iso`, name, iso3, numcode, "uuid" FROM `sample_countries`'.$where.$syntax_sort_mode.' LIMIT '.(int)$limit.' OFFSET '.(int)$ofs);
		//--

	} //END FUNCTION
	//============================================================


	//===== PRIVATES


	//============================================================
	private function buildWhereForDataTable($src) {

		//--
		$src = (string) $src;
		//--

		//--
		$where = '';
		//--
		if((string)$src != '') {
			if(\is_numeric($src)) {
				$where = $this->connection->prepare_param_query(' WHERE numcode = ?', array((int)$src));
			} elseif(\strlen((string)$src) == 2) {
				$where = $this->connection->prepare_param_query(' WHERE iso = ?', array(\SmartUnicode::str_toupper($src)));
			} elseif(\strlen((string)$src) == 3) {
				$where = $this->connection->prepare_param_query(' WHERE iso3 = ?', array(\SmartUnicode::str_toupper($src)));
			} else {
				$where = $this->connection->prepare_param_query(' WHERE name LIKE ?', array($src.'%'));
			} //end if else
		} //end if
		//--

		//--
		return (string) $where;
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	private function init_table_main_samples() {

		//-- create table has a built-in check to avoid run if table is already created
		$this->connection->create_table('table_main_sample', "id character varying(10) NOT NULL, name character varying(100) NOT NULL, description text NOT NULL");
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	private function custom_functions_tests() {

		//-- Test encoding
		$test = (array) $this->connection->read_asdata('SELECT ? AS test', ['abc']);
		$encoding = (string) \SmartUnicode::detect_encoding((string)$test['test']);
		if((string)$encoding != (string)\SMART_FRAMEWORK_CHARSET) {
			\Smart::raise_error('Invalid SQLite3 Encoding Test (1): `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`');
		} //end if
		//--

		//--
		if((string)$this->connection->json_encode([]) != '[]') {
			\Smart::raise_error('Invalid SQLite3 Test: JSON-ENCODE []');
		} //end if
		//--

		//--
		$this->connection->register_sql_function('md5', 1, 'mymd5');
		$test = (array) $this->connection->read_asdata('SELECT custom_fx_mymd5(\''.$this->connection->escape_str('123', 'likes').'\') AS test'); // escape likes is for test only
		if((string)$test['test'] != (string)\md5('123')) {
			\Smart::raise_error('Invalid SQLite3 Test: Custom-MD5()');
		} //end if
		//--

		//-- Test Custom Default Registered Functions
		$test = (array) $this->connection->read_asdata('SELECT smart_crc32b(?) AS test', ['abc']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::crc32b('abc')) {
			\Smart::raise_error('Invalid SQLite3 Test: CRC32B()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_md5(?) AS test', ['abc']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::md5('abc')) {
			\Smart::raise_error('Invalid SQLite3 Test: MD5()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_sha1(?) AS test', ['abc']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::sha1('abc')) {
			\Smart::raise_error('Invalid SQLite3 Test: SHA1()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_sha512(?) AS test', ['abc']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::sha512('abc')) {
			\Smart::raise_error('Invalid SQLite3 Test: SHA512()');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_base64_encode(?) AS test', ['șȘțȚăĂîÎâÂ']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::base64_encode('șȘțȚăĂîÎâÂ')) {
			\Smart::raise_error('Invalid SQLite3 Test: BASE64_ENCODE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_base64_decode(?) AS test', ['yJnImMibyJrEg8SCw67DjsOiw4I=']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::base64_decode('yJnImMibyJrEg8SCw67DjsOiw4I=')) {
			\Smart::raise_error('Invalid SQLite3 Test: BASE64_DECODE()');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_bin2hex(?) AS test', ['șȘțȚăĂîÎâÂ']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::bin2hex('șȘțȚăĂîÎâÂ')) {
			\Smart::raise_error('Invalid SQLite3 Test: BIN2HEX()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_hex2bin(?) AS test', ['c899c898c89bc89ac483c482c3aec38ec3a2c382']);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::hex2bin('c899c898c89bc89ac483c482c3aec38ec3a2c382')) {
			\Smart::raise_error('Invalid SQLite3 Test: HEX2BIN()');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_strlen(?) AS test', ['șȘțȚăĂîÎâÂ']);
		if(((int)$test['test'] != (int)\SmartSQliteFunctions::strlen('șȘțȚăĂîÎâÂ')) OR ((int)$test['test'] != 20)) {
			\Smart::raise_error('Invalid SQLite3 Test: STRLEN()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_charlen(?) AS test', ['șȘțȚăĂîÎâÂ']);
		if(((int)$test['test'] != (int)\SmartSQliteFunctions::charlen('șȘțȚăĂîÎâÂ')) OR ((int)$test['test'] != 10)) {
			\Smart::raise_error('Invalid SQLite3 Test: CHARLEN()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_str_wordcount(?) AS test', ['șȘțȚăĂîÎâÂ this is ']);
		if(((int)$test['test'] != (int)\SmartSQliteFunctions::str_wordcount('șȘțȚăĂîÎâÂ this is ')) OR ((int)$test['test'] != 3)) {
			\Smart::raise_error('Invalid SQLite3 Test: STR_WORDCOUNT()');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_time() AS test');
		if((int)$test['test'] <= 0) {
			\Smart::raise_error('Invalid SQLite3 Test: TIME()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_date(\'Y-m-d\') AS test');
		if((string)$test['test'] != (string)\date('Y-m-d')) {
			\Smart::raise_error('Invalid SQLite3 Test: DATE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_date_diff(?, ?) AS test', ['2019-01-07 08:00:00', '2019-01-05 07:30:59']);
		if((int)$test['test'] != 2) {
			\Smart::raise_error('Invalid SQLite3 Test: DATE_DIFF()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_period_diff(?, ?) AS test', ['2019-03-07 08:59:59', '2017-04-07 07:30:59']);
		if((int)$test['test'] != 23) {
			\Smart::raise_error('Invalid SQLite3 Test: PERIOD_DIFF()'.$test['test']);
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_strtotime(?) AS test', [(string)\date('Y-m-d')]);
		if((string)$test['test'] != (string)\SmartSQliteFunctions::strtotime((string)\date('Y-m-d'))) {
			\Smart::raise_error('Invalid SQLite3 Test: STRTOTIME()');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_strip_tags(?) AS test', ['<a><b>abc</b></a>']);
		if((string)$test['test'] != 'abc') {
			\Smart::raise_error('Invalid SQLite3 Test: STRIP_TAGS()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_strip_tags(?, ?) AS test', ['<a><b>abc</b></a>', '<a>']);
		if((string)$test['test'] != '<a>abc</a>') {
			\Smart::raise_error('Invalid SQLite3 Test: STRIP_TAGS(<a>)');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_striptags(?) AS test', ['<a><b>a&apos;b&quot;c&nbsp;&lt;d&gt;</b></a>']);
		if((string)$test['test'] != 'a\'b"c <d>') {
			\Smart::raise_error('Invalid SQLite3 Test: STRIPTAGS()');
		} //end if

		$test = (array) $this->connection->read_asdata('SELECT smart_deaccent_str(?) AS test', ['Querty, șȘțȚăĂîÎâÂ ...']);
		if((string)$test['test'] != 'Querty, sStTaAiIaA ...') {
			\Smart::raise_error('Invalid SQLite3 Test: DEACCENT_STR()');
		} //end if

		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_contains(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), 'b']);
		if((string)$test['test'] != 1) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_ARR_CONTAINS()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_contains(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), 'd']);
		if((string)$test['test'] == 1) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_ARR_CONTAINS()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_contains(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'b', 2]);
		if((string)$test['test'] != 1) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_OBJ_CONTAINS()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_contains(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'b', 3]);
		if((string)$test['test'] == 1) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_OBJ_CONTAINS()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_contains(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'd', 1]);
		if((string)$test['test'] == 1) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_OBJ_CONTAINS()!');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_delete(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), 'b']);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a', 'c'])) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_ARR_DELETE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_delete(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), 'd']);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a', 'b', 'c'])) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_ARR_DELETE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_delete(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'b', 2]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a' => 1, 'c' => 3])) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_OBJ_DELETE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_delete(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'b', 3]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3])) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_OBJ_DELETE()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_delete(?, ?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), 'd', 1]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3])) {
			\Smart::raise_error('Invalid SQLite3 Test: !JSON_OBJ_DELETE()!');
		} //end if
		//--
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_append(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), $this->connection->json_encode('d')]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a', 'b', 'c', 'd'])) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_ARR_APPEND()');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_arr_append(?, ?) AS test', [$this->connection->json_encode(['a', 'b', 'c']), $this->connection->json_encode(['d'])]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a', 'b', 'c', 'd'])) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_ARR_APPEND()[]');
		} //end if
		$test = (array) $this->connection->read_asdata('SELECT smart_json_obj_append(?, ?) AS test', [$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3]), $this->connection->json_encode(['d' => '4'])]);
		if((string)$test['test'] != (string)$this->connection->json_encode(['a' => 1, 'b' => 2, 'c' => 3, 'd' => '4'])) {
			\Smart::raise_error('Invalid SQLite3 Test: JSON_OBJ_APPEND()');
		} //end if
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	private function init_table_samples_countries() {

		//--
		if($this->connection->check_if_table_exists('sample_countries') == 1) {
			return; // prevent execution if the table has been already created
		} //end if
		//--

		//--
		$rows = [
			['iso'=>'AF',
			'name'=>'Afghanistan',
			'iso3'=>'AFG',
			'numcode'=>'4',
			],
			['iso'=>'AL',
			'name'=>'Albania',
			'iso3'=>'ALB',
			'numcode'=>'8',
			],
			['iso'=>'DZ',
			'name'=>'Algeria',
			'iso3'=>'DZA',
			'numcode'=>'12',
			],
			['iso'=>'AS',
			'name'=>'American Samoa',
			'iso3'=>'ASM',
			'numcode'=>'16',
			],
			['iso'=>'AD',
			'name'=>'Andorra',
			'iso3'=>'AND',
			'numcode'=>'20',
			],
			['iso'=>'AO',
			'name'=>'Angola',
			'iso3'=>'AGO',
			'numcode'=>'24',
			],
			['iso'=>'AI',
			'name'=>'Anguilla',
			'iso3'=>'AIA',
			'numcode'=>'660',
			],
			['iso'=>'AQ',
			'name'=>'Antarctica',
			'iso3'=>'ART',
			'numcode'=>'0',
			],
			['iso'=>'AG',
			'name'=>'Antigua and Barbuda',
			'iso3'=>'ATG',
			'numcode'=>'28',
			],
			['iso'=>'AR',
			'name'=>'Argentina',
			'iso3'=>'ARG',
			'numcode'=>'32',
			],
			['iso'=>'AM',
			'name'=>'Armenia',
			'iso3'=>'ARM',
			'numcode'=>'51',
			],
			['iso'=>'AW',
			'name'=>'Aruba',
			'iso3'=>'ABW',
			'numcode'=>'533',
			],
			['iso'=>'AU',
			'name'=>'Australia',
			'iso3'=>'AUS',
			'numcode'=>'36',
			],
			['iso'=>'AT',
			'name'=>'Austria',
			'iso3'=>'AUT',
			'numcode'=>'40',
			],
			['iso'=>'AZ',
			'name'=>'Azerbaijan',
			'iso3'=>'AZE',
			'numcode'=>'31',
			],
			['iso'=>'BS',
			'name'=>'Bahamas',
			'iso3'=>'BHS',
			'numcode'=>'44',
			],
			['iso'=>'BH',
			'name'=>'Bahrain',
			'iso3'=>'BHR',
			'numcode'=>'48',
			],
			['iso'=>'BD',
			'name'=>'Bangladesh',
			'iso3'=>'BGD',
			'numcode'=>'50',
			],
			['iso'=>'BB',
			'name'=>'Barbados',
			'iso3'=>'BRB',
			'numcode'=>'52',
			],
			['iso'=>'BY',
			'name'=>'Belarus',
			'iso3'=>'BLR',
			'numcode'=>'112',
			],
			['iso'=>'BE',
			'name'=>'Belgium',
			'iso3'=>'BEL',
			'numcode'=>'56',
			],
			['iso'=>'BZ',
			'name'=>'Belize',
			'iso3'=>'BLZ',
			'numcode'=>'84',
			],
			['iso'=>'BJ',
			'name'=>'Benin',
			'iso3'=>'BEN',
			'numcode'=>'204',
			],
			['iso'=>'BM',
			'name'=>'Bermuda',
			'iso3'=>'BMU',
			'numcode'=>'60',
			],
			['iso'=>'BT',
			'name'=>'Bhutan',
			'iso3'=>'BTN',
			'numcode'=>'64',
			],
			['iso'=>'BO',
			'name'=>'Bolivia',
			'iso3'=>'BOL',
			'numcode'=>'68',
			],
			['iso'=>'BA',
			'name'=>'Bosnia and Herzegovina',
			'iso3'=>'BIH',
			'numcode'=>'70',
			],
			['iso'=>'BW',
			'name'=>'Botswana',
			'iso3'=>'BWA',
			'numcode'=>'72',
			],
			['iso'=>'BV',
			'name'=>'Bouvet Island',
			'iso3'=>'BVT',
			'numcode'=>'0',
			],
			['iso'=>'BR',
			'name'=>'Brazil',
			'iso3'=>'BRA',
			'numcode'=>'76',
			],
			['iso'=>'IO',
			'name'=>'British Indian Ocean Territory',
			'iso3'=>'BIO',
			'numcode'=>'0',
			],
			['iso'=>'BN',
			'name'=>'Brunei Darussalam',
			'iso3'=>'BRN',
			'numcode'=>'96',
			],
			['iso'=>'BG',
			'name'=>'Bulgaria',
			'iso3'=>'BGR',
			'numcode'=>'100',
			],
			['iso'=>'BF',
			'name'=>'Burkina Faso',
			'iso3'=>'BFA',
			'numcode'=>'854',
			],
			['iso'=>'BI',
			'name'=>'Burundi',
			'iso3'=>'BDI',
			'numcode'=>'108',
			],
			['iso'=>'KH',
			'name'=>'Cambodia',
			'iso3'=>'KHM',
			'numcode'=>'116',
			],
			['iso'=>'CM',
			'name'=>'Cameroon',
			'iso3'=>'CMR',
			'numcode'=>'120',
			],
			['iso'=>'CA',
			'name'=>'Canada',
			'iso3'=>'CAN',
			'numcode'=>'124',
			],
			['iso'=>'CV',
			'name'=>'Cape Verde',
			'iso3'=>'CPV',
			'numcode'=>'132',
			],
			['iso'=>'KY',
			'name'=>'Cayman Islands',
			'iso3'=>'CYM',
			'numcode'=>'136',
			],
			['iso'=>'CF',
			'name'=>'Central African Republic',
			'iso3'=>'CAF',
			'numcode'=>'140',
			],
			['iso'=>'TD',
			'name'=>'Chad',
			'iso3'=>'TCD',
			'numcode'=>'148',
			],
			['iso'=>'CL',
			'name'=>'Chile',
			'iso3'=>'CHL',
			'numcode'=>'152',
			],
			['iso'=>'CN',
			'name'=>'China',
			'iso3'=>'CHN',
			'numcode'=>'156',
			],
			['iso'=>'CX',
			'name'=>'Christmas Island',
			'iso3'=>'CMI',
			'numcode'=>'0',
			],
			['iso'=>'CC',
			'name'=>'Cocos (Keeling] Islands',
			'iso3'=>'CKI',
			'numcode'=>'0',
			],
			['iso'=>'CO',
			'name'=>'Colombia',
			'iso3'=>'COL',
			'numcode'=>'170',
			],
			['iso'=>'KM',
			'name'=>'Comoros',
			'iso3'=>'COM',
			'numcode'=>'174',
			],
			['iso'=>'CG',
			'name'=>'Congo',
			'iso3'=>'COG',
			'numcode'=>'178',
			],
			['iso'=>'CD',
			'name'=>'Congo, the Democratic Republic of the',
			'iso3'=>'COD',
			'numcode'=>'180',
			],
			['iso'=>'CK',
			'name'=>'Cook Islands',
			'iso3'=>'COK',
			'numcode'=>'184',
			],
			['iso'=>'CR',
			'name'=>'Costa Rica',
			'iso3'=>'CRI',
			'numcode'=>'188',
			],
			['iso'=>'CI',
			'name'=>'Cote D\'Ivoire',
			'iso3'=>'CIV',
			'numcode'=>'384',
			],
			['iso'=>'HR',
			'name'=>'Croatia',
			'iso3'=>'HRV',
			'numcode'=>'191',
			],
			['iso'=>'CU',
			'name'=>'Cuba',
			'iso3'=>'CUB',
			'numcode'=>'192',
			],
			['iso'=>'CY',
			'name'=>'Cyprus',
			'iso3'=>'CYP',
			'numcode'=>'196',
			],
			['iso'=>'CZ',
			'name'=>'Czech Republic',
			'iso3'=>'CZE',
			'numcode'=>'203',
			],
			['iso'=>'DK',
			'name'=>'Denmark',
			'iso3'=>'DNK',
			'numcode'=>'208',
			],
			['iso'=>'DJ',
			'name'=>'Djibouti',
			'iso3'=>'DJI',
			'numcode'=>'262',
			],
			['iso'=>'DM',
			'name'=>'Dominica',
			'iso3'=>'DMA',
			'numcode'=>'212',
			],
			['iso'=>'DO',
			'name'=>'Dominican Republic',
			'iso3'=>'DOM',
			'numcode'=>'214',
			],
			['iso'=>'EC',
			'name'=>'Ecuador',
			'iso3'=>'ECU',
			'numcode'=>'218',
			],
			['iso'=>'EG',
			'name'=>'Egypt',
			'iso3'=>'EGY',
			'numcode'=>'818',
			],
			['iso'=>'SV',
			'name'=>'El Salvador',
			'iso3'=>'SLV',
			'numcode'=>'222',
			],
			['iso'=>'GQ',
			'name'=>'Equatorial Guinea',
			'iso3'=>'GNQ',
			'numcode'=>'226',
			],
			['iso'=>'ER',
			'name'=>'Eritrea',
			'iso3'=>'ERI',
			'numcode'=>'232',
			],
			['iso'=>'EE',
			'name'=>'Estonia',
			'iso3'=>'EST',
			'numcode'=>'233',
			],
			['iso'=>'ET',
			'name'=>'Ethiopia',
			'iso3'=>'ETH',
			'numcode'=>'231',
			],
			['iso'=>'FK',
			'name'=>'Falkland Islands (Malvinas]',
			'iso3'=>'FLK',
			'numcode'=>'238',
			],
			['iso'=>'FO',
			'name'=>'Faroe Islands',
			'iso3'=>'FRO',
			'numcode'=>'234',
			],
			['iso'=>'FJ',
			'name'=>'Fiji',
			'iso3'=>'FJI',
			'numcode'=>'242',
			],
			['iso'=>'FI',
			'name'=>'Finland',
			'iso3'=>'FIN',
			'numcode'=>'246',
			],
			['iso'=>'FR',
			'name'=>'France',
			'iso3'=>'FRA',
			'numcode'=>'250',
			],
			['iso'=>'GF',
			'name'=>'French Guiana',
			'iso3'=>'GUF',
			'numcode'=>'254',
			],
			['iso'=>'PF',
			'name'=>'French Polynesia',
			'iso3'=>'PYF',
			'numcode'=>'258',
			],
			['iso'=>'TF',
			'name'=>'French Southern Territories',
			'iso3'=>'FST',
			'numcode'=>'0',
			],
			['iso'=>'GA',
			'name'=>'Gabon',
			'iso3'=>'GAB',
			'numcode'=>'266',
			],
			['iso'=>'GM',
			'name'=>'Gambia',
			'iso3'=>'GMB',
			'numcode'=>'270',
			],
			['iso'=>'GE',
			'name'=>'Georgia',
			'iso3'=>'GEO',
			'numcode'=>'268',
			],
			['iso'=>'DE',
			'name'=>'Germany',
			'iso3'=>'DEU',
			'numcode'=>'276',
			],
			['iso'=>'GH',
			'name'=>'Ghana',
			'iso3'=>'GHA',
			'numcode'=>'288',
			],
			['iso'=>'GI',
			'name'=>'Gibraltar',
			'iso3'=>'GIB',
			'numcode'=>'292',
			],
			['iso'=>'GR',
			'name'=>'Greece',
			'iso3'=>'GRC',
			'numcode'=>'300',
			],
			['iso'=>'GL',
			'name'=>'Greenland',
			'iso3'=>'GRL',
			'numcode'=>'304',
			],
			['iso'=>'GD',
			'name'=>'Grenada',
			'iso3'=>'GRD',
			'numcode'=>'308',
			],
			['iso'=>'GP',
			'name'=>'Guadeloupe',
			'iso3'=>'GLP',
			'numcode'=>'312',
			],
			['iso'=>'GU',
			'name'=>'Guam',
			'iso3'=>'GUM',
			'numcode'=>'316',
			],
			['iso'=>'GT',
			'name'=>'Guatemala',
			'iso3'=>'GTM',
			'numcode'=>'320',
			],
			['iso'=>'GN',
			'name'=>'Guinea',
			'iso3'=>'GIN',
			'numcode'=>'324',
			],
			['iso'=>'GW',
			'name'=>'Guinea-Bissau',
			'iso3'=>'GNB',
			'numcode'=>'624',
			],
			['iso'=>'GY',
			'name'=>'Guyana',
			'iso3'=>'GUY',
			'numcode'=>'328',
			],
			['iso'=>'HT',
			'name'=>'Haiti',
			'iso3'=>'HTI',
			'numcode'=>'332',
			],
			['iso'=>'HM',
			'name'=>'Heard Island and Mcdonald Islands',
			'iso3'=>'HMI',
			'numcode'=>'0',
			],
			['iso'=>'VA',
			'name'=>'Holy See (Vatican City State]',
			'iso3'=>'VAT',
			'numcode'=>'336',
			],
			['iso'=>'HN',
			'name'=>'Honduras',
			'iso3'=>'HND',
			'numcode'=>'340',
			],
			['iso'=>'HK',
			'name'=>'Hong Kong',
			'iso3'=>'HKG',
			'numcode'=>'344',
			],
			['iso'=>'HU',
			'name'=>'Hungary',
			'iso3'=>'HUN',
			'numcode'=>'348',
			],
			['iso'=>'IS',
			'name'=>'Iceland',
			'iso3'=>'ISL',
			'numcode'=>'352',
			],
			['iso'=>'IN',
			'name'=>'India',
			'iso3'=>'IND',
			'numcode'=>'356',
			],
			['iso'=>'ID',
			'name'=>'Indonesia',
			'iso3'=>'IDN',
			'numcode'=>'360',
			],
			['iso'=>'IR',
			'name'=>'Iran, Islamic Republic of',
			'iso3'=>'IRN',
			'numcode'=>'364',
			],
			['iso'=>'IQ',
			'name'=>'Iraq',
			'iso3'=>'IRQ',
			'numcode'=>'368',
			],
			['iso'=>'IE',
			'name'=>'Ireland',
			'iso3'=>'IRL',
			'numcode'=>'372',
			],
			['iso'=>'IL',
			'name'=>'Israel',
			'iso3'=>'ISR',
			'numcode'=>'376',
			],
			['iso'=>'IT',
			'name'=>'Italy',
			'iso3'=>'ITA',
			'numcode'=>'380',
			],
			['iso'=>'JM',
			'name'=>'Jamaica',
			'iso3'=>'JAM',
			'numcode'=>'388',
			],
			['iso'=>'JP',
			'name'=>'Japan',
			'iso3'=>'JPN',
			'numcode'=>'392',
			],
			['iso'=>'JO',
			'name'=>'Jordan',
			'iso3'=>'JOR',
			'numcode'=>'400',
			],
			['iso'=>'KZ',
			'name'=>'Kazakhstan',
			'iso3'=>'KAZ',
			'numcode'=>'398',
			],
			['iso'=>'KE',
			'name'=>'Kenya',
			'iso3'=>'KEN',
			'numcode'=>'404',
			],
			['iso'=>'KI',
			'name'=>'Kiribati',
			'iso3'=>'KIR',
			'numcode'=>'296',
			],
			['iso'=>'KP',
			'name'=>'Korea, Democratic People\'s Republic of',
			'iso3'=>'PRK',
			'numcode'=>'408',
			],
			['iso'=>'KR',
			'name'=>'Korea, Republic of',
			'iso3'=>'KOR',
			'numcode'=>'410',
			],
			['iso'=>'KW',
			'name'=>'Kuwait',
			'iso3'=>'KWT',
			'numcode'=>'414',
			],
			['iso'=>'KG',
			'name'=>'Kyrgyzstan',
			'iso3'=>'KGZ',
			'numcode'=>'417',
			],
			['iso'=>'LA',
			'name'=>'Lao People\'s Democratic Republic',
			'iso3'=>'LAO',
			'numcode'=>'418',
			],
			['iso'=>'LV',
			'name'=>'Latvia',
			'iso3'=>'LVA',
			'numcode'=>'428',
			],
			['iso'=>'LB',
			'name'=>'Lebanon',
			'iso3'=>'LBN',
			'numcode'=>'422',
			],
			['iso'=>'LS',
			'name'=>'Lesotho',
			'iso3'=>'LSO',
			'numcode'=>'426',
			],
			['iso'=>'LR',
			'name'=>'Liberia',
			'iso3'=>'LBR',
			'numcode'=>'430',
			],
			['iso'=>'LY',
			'name'=>'Libyan Arab Jamahiriya',
			'iso3'=>'LBY',
			'numcode'=>'434',
			],
			['iso'=>'LI',
			'name'=>'Liechtenstein',
			'iso3'=>'LIE',
			'numcode'=>'438',
			],
			['iso'=>'LT',
			'name'=>'Lithuania',
			'iso3'=>'LTU',
			'numcode'=>'440',
			],
			['iso'=>'LU',
			'name'=>'Luxembourg',
			'iso3'=>'LUX',
			'numcode'=>'442',
			],
			['iso'=>'MO',
			'name'=>'Macao',
			'iso3'=>'MAC',
			'numcode'=>'446',
			],
			['iso'=>'MK',
			'name'=>'Macedonia, the Former Yugoslav Republic of',
			'iso3'=>'MKD',
			'numcode'=>'807',
			],
			['iso'=>'MG',
			'name'=>'Madagascar',
			'iso3'=>'MDG',
			'numcode'=>'450',
			],
			['iso'=>'MW',
			'name'=>'Malawi',
			'iso3'=>'MWI',
			'numcode'=>'454',
			],
			['iso'=>'MY',
			'name'=>'Malaysia',
			'iso3'=>'MYS',
			'numcode'=>'458',
			],
			['iso'=>'MV',
			'name'=>'Maldives',
			'iso3'=>'MDV',
			'numcode'=>'462',
			],
			['iso'=>'ML',
			'name'=>'Mali',
			'iso3'=>'MLI',
			'numcode'=>'466',
			],
			['iso'=>'MT',
			'name'=>'Malta',
			'iso3'=>'MLT',
			'numcode'=>'470',
			],
			['iso'=>'MH',
			'name'=>'Marshall Islands',
			'iso3'=>'MHL',
			'numcode'=>'584',
			],
			['iso'=>'MQ',
			'name'=>'Martinique',
			'iso3'=>'MTQ',
			'numcode'=>'474',
			],
			['iso'=>'MR',
			'name'=>'Mauritania',
			'iso3'=>'MRT',
			'numcode'=>'478',
			],
			['iso'=>'MU',
			'name'=>'Mauritius',
			'iso3'=>'MUS',
			'numcode'=>'480',
			],
			['iso'=>'YT',
			'name'=>'Mayotte',
			'iso3'=>'MAY',
			'numcode'=>'0',
			],
			['iso'=>'MX',
			'name'=>'Mexico',
			'iso3'=>'MEX',
			'numcode'=>'484',
			],
			['iso'=>'FM',
			'name'=>'Micronesia, Federated States of',
			'iso3'=>'FSM',
			'numcode'=>'583',
			],
			['iso'=>'MD',
			'name'=>'Moldova, Republic of',
			'iso3'=>'MDA',
			'numcode'=>'498',
			],
			['iso'=>'MC',
			'name'=>'Monaco',
			'iso3'=>'MCO',
			'numcode'=>'492',
			],
			['iso'=>'MN',
			'name'=>'Mongolia',
			'iso3'=>'MNG',
			'numcode'=>'496',
			],
			['iso'=>'MS',
			'name'=>'Montserrat',
			'iso3'=>'MSR',
			'numcode'=>'500',
			],
			['iso'=>'MA',
			'name'=>'Morocco',
			'iso3'=>'MAR',
			'numcode'=>'504',
			],
			['iso'=>'MZ',
			'name'=>'Mozambique',
			'iso3'=>'MOZ',
			'numcode'=>'508',
			],
			['iso'=>'MM',
			'name'=>'Myanmar',
			'iso3'=>'MMR',
			'numcode'=>'104',
			],
			['iso'=>'NA',
			'name'=>'Namibia',
			'iso3'=>'NAM',
			'numcode'=>'516',
			],
			['iso'=>'NR',
			'name'=>'Nauru',
			'iso3'=>'NRU',
			'numcode'=>'520',
			],
			['iso'=>'NP',
			'name'=>'Nepal',
			'iso3'=>'NPL',
			'numcode'=>'524',
			],
			['iso'=>'NL',
			'name'=>'Netherlands',
			'iso3'=>'NLD',
			'numcode'=>'528',
			],
			['iso'=>'AN',
			'name'=>'Netherlands Antilles',
			'iso3'=>'ANT',
			'numcode'=>'530',
			],
			['iso'=>'NC',
			'name'=>'New Caledonia',
			'iso3'=>'NCL',
			'numcode'=>'540',
			],
			['iso'=>'NZ',
			'name'=>'New Zealand',
			'iso3'=>'NZL',
			'numcode'=>'554',
			],
			['iso'=>'NI',
			'name'=>'Nicaragua',
			'iso3'=>'NIC',
			'numcode'=>'558',
			],
			['iso'=>'NE',
			'name'=>'Niger',
			'iso3'=>'NER',
			'numcode'=>'562',
			],
			['iso'=>'NG',
			'name'=>'Nigeria',
			'iso3'=>'NGA',
			'numcode'=>'566',
			],
			['iso'=>'NU',
			'name'=>'Niue',
			'iso3'=>'NIU',
			'numcode'=>'570',
			],
			['iso'=>'NF',
			'name'=>'Norfolk Island',
			'iso3'=>'NFK',
			'numcode'=>'574',
			],
			['iso'=>'MP',
			'name'=>'Northern Mariana Islands',
			'iso3'=>'MNP',
			'numcode'=>'580',
			],
			['iso'=>'NO',
			'name'=>'Norway',
			'iso3'=>'NOR',
			'numcode'=>'578',
			],
			['iso'=>'OM',
			'name'=>'Oman',
			'iso3'=>'OMN',
			'numcode'=>'512',
			],
			['iso'=>'PK',
			'name'=>'Pakistan',
			'iso3'=>'PAK',
			'numcode'=>'586',
			],
			['iso'=>'PW',
			'name'=>'Palau',
			'iso3'=>'PLW',
			'numcode'=>'585',
			],
			['iso'=>'PS',
			'name'=>'Palestinian Territory, Occupied',
			'iso3'=>'PTO',
			'numcode'=>'0',
			],
			['iso'=>'PA',
			'name'=>'Panama',
			'iso3'=>'PAN',
			'numcode'=>'591',
			],
			['iso'=>'PG',
			'name'=>'Papua New Guinea',
			'iso3'=>'PNG',
			'numcode'=>'598',
			],
			['iso'=>'PY',
			'name'=>'Paraguay',
			'iso3'=>'PRY',
			'numcode'=>'600',
			],
			['iso'=>'PE',
			'name'=>'Peru',
			'iso3'=>'PER',
			'numcode'=>'604',
			],
			['iso'=>'PH',
			'name'=>'Philippines',
			'iso3'=>'PHL',
			'numcode'=>'608',
			],
			['iso'=>'PN',
			'name'=>'Pitcairn',
			'iso3'=>'PCN',
			'numcode'=>'612',
			],
			['iso'=>'PL',
			'name'=>'Poland',
			'iso3'=>'POL',
			'numcode'=>'616',
			],
			['iso'=>'PT',
			'name'=>'Portugal',
			'iso3'=>'PRT',
			'numcode'=>'620',
			],
			['iso'=>'PR',
			'name'=>'Puerto Rico',
			'iso3'=>'PRI',
			'numcode'=>'630',
			],
			['iso'=>'QA',
			'name'=>'Qatar',
			'iso3'=>'QAT',
			'numcode'=>'634',
			],
			['iso'=>'RE',
			'name'=>'Reunion',
			'iso3'=>'REU',
			'numcode'=>'638',
			],
			['iso'=>'RO',
			'name'=>'Romania',
			'iso3'=>'ROM',
			'numcode'=>'642',
			],
			['iso'=>'RU',
			'name'=>'Russian Federation',
			'iso3'=>'RUS',
			'numcode'=>'643',
			],
			['iso'=>'RW',
			'name'=>'Rwanda',
			'iso3'=>'RWA',
			'numcode'=>'646',
			],
			['iso'=>'SH',
			'name'=>'Saint Helena',
			'iso3'=>'SHN',
			'numcode'=>'654',
			],
			['iso'=>'KN',
			'name'=>'Saint Kitts and Nevis',
			'iso3'=>'KNA',
			'numcode'=>'659',
			],
			['iso'=>'LC',
			'name'=>'Saint Lucia',
			'iso3'=>'LCA',
			'numcode'=>'662',
			],
			['iso'=>'PM',
			'name'=>'Saint Pierre and Miquelon',
			'iso3'=>'SPM',
			'numcode'=>'666',
			],
			['iso'=>'VC',
			'name'=>'Saint Vincent and the Grenadines',
			'iso3'=>'VCT',
			'numcode'=>'670',
			],
			['iso'=>'WS',
			'name'=>'Samoa',
			'iso3'=>'WSM',
			'numcode'=>'882',
			],
			['iso'=>'SM',
			'name'=>'San Marino',
			'iso3'=>'SMR',
			'numcode'=>'674',
			],
			['iso'=>'ST',
			'name'=>'Sao Tome and Principe',
			'iso3'=>'STP',
			'numcode'=>'678',
			],
			['iso'=>'SA',
			'name'=>'Saudi Arabia',
			'iso3'=>'SAU',
			'numcode'=>'682',
			],
			['iso'=>'SN',
			'name'=>'Senegal',
			'iso3'=>'SEN',
			'numcode'=>'686',
			],
			['iso'=>'CS',
			'name'=>'Serbia and Montenegro',
			'iso3'=>'SNM',
			'numcode'=>'0',
			],
			['iso'=>'SC',
			'name'=>'Seychelles',
			'iso3'=>'SYC',
			'numcode'=>'690',
			],
			['iso'=>'SL',
			'name'=>'Sierra Leone',
			'iso3'=>'SLE',
			'numcode'=>'694',
			],
			['iso'=>'SG',
			'name'=>'Singapore',
			'iso3'=>'SGP',
			'numcode'=>'702',
			],
			['iso'=>'SK',
			'name'=>'Slovakia',
			'iso3'=>'SVK',
			'numcode'=>'703',
			],
			['iso'=>'SI',
			'name'=>'Slovenia',
			'iso3'=>'SVN',
			'numcode'=>'705',
			],
			['iso'=>'SB',
			'name'=>'Solomon Islands',
			'iso3'=>'SLB',
			'numcode'=>'90',
			],
			['iso'=>'SO',
			'name'=>'Somalia',
			'iso3'=>'SOM',
			'numcode'=>'706',
			],
			['iso'=>'ZA',
			'name'=>'South Africa',
			'iso3'=>'ZAF',
			'numcode'=>'710',
			],
			['iso'=>'GS',
			'name'=>'South Georgia and the South Sandwich Islands',
			'iso3'=>'SGS',
			'numcode'=>'0',
			],
			['iso'=>'ES',
			'name'=>'Spain',
			'iso3'=>'ESP',
			'numcode'=>'724',
			],
			['iso'=>'LK',
			'name'=>'Sri Lanka',
			'iso3'=>'LKA',
			'numcode'=>'144',
			],
			['iso'=>'SD',
			'name'=>'Sudan',
			'iso3'=>'SDN',
			'numcode'=>'736',
			],
			['iso'=>'SR',
			'name'=>'Suriname',
			'iso3'=>'SUR',
			'numcode'=>'740',
			],
			['iso'=>'SJ',
			'name'=>'Svalbard and Jan Mayen',
			'iso3'=>'SJM',
			'numcode'=>'744',
			],
			['iso'=>'SZ',
			'name'=>'Swaziland',
			'iso3'=>'SWZ',
			'numcode'=>'748',
			],
			['iso'=>'SE',
			'name'=>'Sweden',
			'iso3'=>'SWE',
			'numcode'=>'752',
			],
			['iso'=>'CH',
			'name'=>'Switzerland',
			'iso3'=>'CHE',
			'numcode'=>'756',
			],
			['iso'=>'SY',
			'name'=>'Syrian Arab Republic',
			'iso3'=>'SYR',
			'numcode'=>'760',
			],
			['iso'=>'TW',
			'name'=>'Taiwan, Province of China',
			'iso3'=>'TWN',
			'numcode'=>'158',
			],
			['iso'=>'TJ',
			'name'=>'Tajikistan',
			'iso3'=>'TJK',
			'numcode'=>'762',
			],
			['iso'=>'TZ',
			'name'=>'Tanzania, United Republic of',
			'iso3'=>'TZA',
			'numcode'=>'834',
			],
			['iso'=>'TH',
			'name'=>'Thailand',
			'iso3'=>'THA',
			'numcode'=>'764',
			],
			['iso'=>'TL',
			'name'=>'Timor-Leste',
			'iso3'=>'TIM',
			'numcode'=>'0',
			],
			['iso'=>'TG',
			'name'=>'Togo',
			'iso3'=>'TGO',
			'numcode'=>'768',
			],
			['iso'=>'TK',
			'name'=>'Tokelau',
			'iso3'=>'TKL',
			'numcode'=>'772',
			],
			['iso'=>'TO',
			'name'=>'Tonga',
			'iso3'=>'TON',
			'numcode'=>'776',
			],
			['iso'=>'TT',
			'name'=>'Trinidad and Tobago',
			'iso3'=>'TTO',
			'numcode'=>'780',
			],
			['iso'=>'TN',
			'name'=>'Tunisia',
			'iso3'=>'TUN',
			'numcode'=>'788',
			],
			['iso'=>'TR',
			'name'=>'Turkey',
			'iso3'=>'TUR',
			'numcode'=>'792',
			],
			['iso'=>'TM',
			'name'=>'Turkmenistan',
			'iso3'=>'TKM',
			'numcode'=>'795',
			],
			['iso'=>'TC',
			'name'=>'Turks and Caicos Islands',
			'iso3'=>'TCA',
			'numcode'=>'796',
			],
			['iso'=>'TV',
			'name'=>'Tuvalu',
			'iso3'=>'TUV',
			'numcode'=>'798',
			],
			['iso'=>'UG',
			'name'=>'Uganda',
			'iso3'=>'UGA',
			'numcode'=>'800',
			],
			['iso'=>'UA',
			'name'=>'Ukraine',
			'iso3'=>'UKR',
			'numcode'=>'804',
			],
			['iso'=>'AE',
			'name'=>'United Arab Emirates',
			'iso3'=>'ARE',
			'numcode'=>'784',
			],
			['iso'=>'GB',
			'name'=>'United Kingdom',
			'iso3'=>'GBR',
			'numcode'=>'826',
			],
			['iso'=>'US',
			'name'=>'United States',
			'iso3'=>'USA',
			'numcode'=>'840',
			],
			['iso'=>'UM',
			'name'=>'United States Minor Outlying Islands',
			'iso3'=>'USI',
			'numcode'=>'0',
			],
			['iso'=>'UY',
			'name'=>'Uruguay',
			'iso3'=>'URY',
			'numcode'=>'858',
			],
			['iso'=>'UZ',
			'name'=>'Uzbekistan',
			'iso3'=>'UZB',
			'numcode'=>'860',
			],
			['iso'=>'VU',
			'name'=>'Vanuatu',
			'iso3'=>'VUT',
			'numcode'=>'548',
			],
			['iso'=>'VE',
			'name'=>'Venezuela',
			'iso3'=>'VEN',
			'numcode'=>'862',
			],
			['iso'=>'VN',
			'name'=>'Viet Nam',
			'iso3'=>'VNM',
			'numcode'=>'704',
			],
			['iso'=>'VG',
			'name'=>'Virgin Islands, British',
			'iso3'=>'VGB',
			'numcode'=>'92',
			],
			['iso'=>'VI',
			'name'=>'Virgin Islands, U.s.',
			'iso3'=>'VIR',
			'numcode'=>'850',
			],
			['iso'=>'WF',
			'name'=>'Wallis and Futuna',
			'iso3'=>'WLF',
			'numcode'=>'876',
			],
			['iso'=>'EH',
			'name'=>'Western Sahara',
			'iso3'=>'ESH',
			'numcode'=>'732',
			],
			['iso'=>'YE',
			'name'=>'Yemen',
			'iso3'=>'YEM',
			'numcode'=>'887',
			],
			['iso'=>'ZM',
			'name'=>'Zambia',
			'iso3'=>'ZMB',
			'numcode'=>'894',
			],
			['iso'=>'ZW',
			'name'=>'Zimbabwe',
			'iso3'=>'ZWE',
			'numcode'=>'716',
			]
		];
		//--

		//--
		if(!$this->connection->check_if_table_exists('sample_countries')) { // better check here and make create table in a transaction if does not exists ; if not check here the create_table() will anyway check
			$this->connection->write_data('BEGIN'); // start transaction
			$this->connection->create_table(
				'sample_countries',
				'iso character varying(2) PRIMARY KEY NOT NULL, name character varying(100) NOT NULL, iso3 character varying(3) NOT NULL, numcode integer NOT NULL, uuid character varying(10)',
				[ // indexes
				//	'iso' 		=> 'iso', // not necessary, it is the primary key
					'name' 		=> 'name ASC',
					'iso3' 		=> [ 'mode' => 'unique', 'index' => 'iso3' ],
					'numcode' 	=> 'numcode DESC',
					'uuid' 		=> [ 'mode' => 'unique', 'index' => 'uuid' ]
				]
			);
			$this->connection->write_data('UPDATE `sample_countries` '.$this->connection->prepare_statement($rows[0], 'update').' WHERE (`iso` IS NULL)'); // test
			$iterator = 0;
			foreach($rows as $key => $row) {
				$row['uuid'] = (string) $this->connection->new_safe_id('uid10seq', 'uuid', 'sample_countries');
				$wr = (array) $this->connection->write_data(
					'INSERT INTO `sample_countries` '.$this->connection->prepare_statement($row, 'insert')
				);
				$iterator++;
				if($iterator != $wr[2]) {
					\Smart::log_warning(__METHOD__.' :: Invalid LastInsertID at cycle #'.$iterator.' is: '.$wr[2]);
				} //end if
			} //end foreach
			$this->connection->write_data('COMMIT');
		} //end if
		//--
		$this->connection->read_data('SELECT * FROM sample_countries WHERE (iso '.$this->connection->prepare_statement(array('US', 7, null), 'in-select').')');
		//--

	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
