<?php
// [LIB - Smart.Framework / Samples / Test (Unicode) Strings]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitStrings
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
 * Test (Unicode) Strings
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250214
 *
 */
final class TestUnitStrings {

	// ::

	private static $entities_text = 'Platform クラウドアプリケーションプラットフォーム \'áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇòóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝßȘŞŠŚŜțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ "';
	private static $entities_html = 'Platform &#12463;&#12521;&#12454;&#12489;&#12450;&#12503;&#12522;&#12465;&#12540;&#12471;&#12519;&#12531;&#12503;&#12521;&#12483;&#12488;&#12501;&#12457;&#12540;&#12512; \'&#225;&#226;&#227;&#228;&#229;&#257;&#259;&#261;&#193;&#194;&#195;&#196;&#197;&#256;&#258;&#260;&#263;&#265;&#269;&#231;&#262;&#264;&#268;&#199;&#271;&#270;&#232;&#233;&#234;&#235;&#275;&#277;&#279;&#283;&#281;&#200;&#201;&#202;&#203;&#274;&#276;&#278;&#282;&#280;&#285;&#291;&#284;&#290;&#293;&#295;&#292;&#294;&#236;&#237;&#238;&#239;&#297;&#299;&#301;&#521;&#523;&#303;&#204;&#205;&#206;&#207;&#296;&#298;&#300;&#520;&#522;&#302;&#307;&#309;&#306;&#308;&#311;&#310;&#314;&#316;&#318;&#322;&#313;&#315;&#317;&#321;&#241;&#324;&#326;&#328;&#209;&#323;&#325;&#327;&#242;&#243;&#244;&#245;&#246;&#333;&#335;&#337;&#248;&#339;&#210;&#211;&#212;&#213;&#214;&#332;&#334;&#336;&#216;&#338;&#341;&#343;&#345;&#340;&#342;&#344;&#537;&#351;&#353;&#347;&#349;&#223;&#536;&#350;&#352;&#346;&#348;&#539;&#355;&#357;&#538;&#354;&#356;&#249;&#250;&#251;&#252;&#361;&#363;&#365;&#367;&#369;&#371;&#217;&#218;&#219;&#220;&#360;&#362;&#364;&#366;&#368;&#370;&#373;&#372;&#7823;&#7923;&#375;&#255;&#253;&#7822;&#7922;&#374;&#376;&#221;&#378;&#380;&#382;&#377;&#379;&#381; "';

	//============================================================
	public static function testStr() {
		//--
		return self::$entities_text.' <p></p> ? & * ^ $ @ ! ` ~ % () [] {} | \ / + - _ : ; , . #\'0.51085630 1454529172#'."\r\n\t".'`~@#$%^&*()-_=+[{]}|;:"<>,.?/\\'; // this must be NOT dynamic
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function testUnicode($str_php, $str_js) {

		//--
		$time = \microtime(true);
		//--

		//--
		$lorem_iso_text = 'Lorem Ipsum dolor sit Amet';
		$unicode_text = '"Unicode78źź:ăĂîÎâÂșȘțȚşŞţŢグッド';
		$invalid_string = (string) $unicode_text.pack("H*" ,'c32e').'#';
		//--
		$idn_domain_unicode = 'jösefsson.tßst123.org';
		$idn_domain_iso = 'xn--jsefsson-n4a.xn--tst123-bta.org';
		$idn_email_unicode = 'räksmörgås@jösefsson.tßst123.org';
		$idn_email_iso = 'xn--rksmrgs@jsefsson-vnbx43ag.xn--tst123-bta.org';
		//--

		//--
		$err = '';
		//--

		//--
		$tests[] = '===== Unicode STRING / TESTS: =====';
		//--

		//--
		$regex_positive = '/^[\w"\:\?]+$/';
		$regex_negative = '/[^\w"\:\?]/';
		//--

		//--
		if((string)$err == '') {
			$the_test = 'Unicode Characters Encoding Detection';
			$tests[] = $the_test;
			$teststr = [
				'A * B',
				'A / B',
				'A - B',
				'A + B', // expected to fail in PHP 8.1 / 8.2 may fail if detected as UTF-7 (as in new iconv versions)
				'3 + 4', // expected to fail in PHP 8.1 / 8.2 may fail if detected as UTF-7 (as in new iconv versions)
			];
			foreach($teststr as $kk => $vv) {
				$encoding = \SmartUnicode::detect_encoding((string)$vv);
				if((string)$encoding !== (string)\SMART_FRAMEWORK_CHARSET) {
					$err = 'ERROR: '.$the_test.' FAILED with string: `'.$vv.'` ; Encoding is `'.$encoding.'` instead of `'.\SMART_FRAMEWORK_CHARSET.'`';
					break;
				} //end if
			} //end foreach
		} //end if
		//--

		//--
		if((string)$err == '') {
			$the_test = 'Unicode URL Input Test from PHP (semantic URL vars)';
			$tests[] = $the_test;
			if(((string)$str_php == '') OR ((string)$str_php !== (string)self::testStr().' .')) { // +. must be decoded as space. by urldecode() instead of rawurldecode()
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode URL Input Test from Javascript (URL vars)';
			$tests[] = $the_test;
			if(((string)$str_js == '') OR ((string)$str_js !== (string)self::testStr().' .')) { // +. must be decoded as space. by urldecode() instead of rawurldecode()
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if(\defined('\\SMART_FRAMEWORK_SECURITY_FILTER_INPUT')) {
			if((string)\SMART_FRAMEWORK_SECURITY_FILTER_INPUT != '') {
				if((string)$err == '') {
					$the_test = 'Unicode Input Filter Regex - Smart.Framework Security';
					$tests[] = $the_test;
					if(\preg_match((string)\SMART_FRAMEWORK_SECURITY_FILTER_INPUT, (string)self::testStr())) {
						$err = 'ERROR: '.$the_test.' FAILED ...';
					} //end if
				} //end if
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode String Replace First Occurence Only';
			$tests[] = $the_test;
			if(
				((string)\Smart::str_replace_first((string)$unicode_text, '', (string)$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text) !== (string)'[1]'.$unicode_text.'[2]'.$unicode_text) OR
				((string)\Smart::str_replace_first((string)$unicode_text, '', (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text) !== (string)'[0][1]'.$unicode_text.'[2]'.$unicode_text) OR
				((string)\Smart::str_replace_first((string)$unicode_text, '', (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text.'[3]') !== (string)'[0][1]'.$unicode_text.'[2]'.$unicode_text.'[3]')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode String Replace Last Occurence Only';
			$tests[] = $the_test;
			if(
				((string)\Smart::str_replace_last((string)$unicode_text, '', (string)$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text) !== (string)$unicode_text.'[1]'.$unicode_text.'[2]') OR
				((string)\Smart::str_replace_last((string)$unicode_text, '', (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text) !== (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2]') OR
				((string)\Smart::str_replace_last((string)$unicode_text, '', (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2]'.$unicode_text.'[3]') !== (string)'[0]'.$unicode_text.'[1]'.$unicode_text.'[2][3]')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Json Encode / Decode Test';
			$tests[] = $the_test;
			if((string)\Smart::json_decode(\Smart::json_encode($unicode_text)) !== (string)$unicode_text) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Json Encode / Decode Test with Invalid (out of Unicode Range) String';
			$tests[] = $the_test;
			if((int)\strlen((string)\Smart::json_decode(\Smart::json_encode($invalid_string))) <= 0) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode HTML Entities Encode / Decode Test';
			$tests[] = $the_test;
			if((string)\SmartUnicode::html_entities(self::$entities_text) !== (string)self::$entities_html) {
				$err = 'ERROR: '.$the_test.' FAILED (Encode) ...';
			} elseif((string)\Smart::stripTags((string)self::$entities_html) !== (string)self::$entities_text) {
				$err = 'ERROR: '.$the_test.' FAILED (Decode) ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Regex Test Positive';
			$tests[] = $the_test;
			if(!\preg_match((string)$regex_positive.'u', (string)$unicode_text)) {
				$err = 'ERROR: '.$the_test.' FAILED (1) ...';
			} elseif(\preg_match((string)$regex_positive, (string)$unicode_text)) {
				$err = 'ERROR: '.$the_test.' FAILED (2) ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Regex Test Negative';
			$tests[] = $the_test;
			if(\preg_match((string)$regex_negative.'u', (string)$unicode_text)) {
				$err = 'ERROR: '.$the_test.' FAILED (1) ...';
			} elseif(!\preg_match((string)$regex_negative, (string)$unicode_text)) {
				$err = 'ERROR: '.$the_test.' FAILED (2) ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Deaccented ISO Regex Test Positive';
			$tests[] = $the_test;
			if(!\preg_match((string)$regex_positive, (string)\SmartUnicode::deaccent_str($unicode_text))) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Deaccented ISO Regex Test Negative';
			$tests[] = $the_test;
			if(\preg_match((string)$regex_negative, (string)\SmartUnicode::deaccent_str($unicode_text))) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode Strlen Test';
			$tests[] = $the_test;
			if(\SmartUnicode::str_len($unicode_text) !== 30) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') { // this tests also \SmartUnicode::str_ipos
			$the_test = 'Unicode Find Substring (Case Insensitive), Positive';
			$tests[] = $the_test;
			if(\SmartUnicode::str_icontains($unicode_text, 'șș') !== true) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') { // this tests also \SmartUnicode::str_ipos
			$the_test = 'Unicode Find Substring (Case Insensitive), Negative';
			$tests[] = $the_test;
			if(\SmartUnicode::str_icontains($unicode_text, 'șş') !== false) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') { // this tests also \SmartUnicode::str_pos
			$the_test = 'Unicode Find Substring (Case Sensitive), Positive';
			$tests[] = $the_test;
			if(\SmartUnicode::str_contains($unicode_text, 'țȚ') !== true) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') { // this tests also \SmartUnicode::str_pos
			$the_test = 'Unicode Find Substring (Case Sensitive), Negative';
			$tests[] = $the_test;
			if(\SmartUnicode::str_contains($unicode_text, 'țŢ') !== false) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode Find Substring (Case Insensitive), Reverse';
			$tests[] = $the_test;
			if(\SmartUnicode::str_ripos($unicode_text, 'ţţグ') === false) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Find Substring (Case Sensitive), Reverse';
			$tests[] = $the_test;
			if(\SmartUnicode::str_rpos($unicode_text, 'ţŢグ') === false) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode Return Substring (Case Insensitive)';
			$tests[] = $the_test;
			if(\SmartUnicode::stri_str($unicode_text, 'âȘșȚ') !== 'ÂșȘțȚşŞţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode Return Substring (Case Sensitive)';
			$tests[] = $the_test;
			if(\SmartUnicode::str_str($unicode_text, 'ÂșȘț') !== 'ÂșȘțȚşŞţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode String to LowerCase';
			$tests[] = $the_test;
			if(\SmartUnicode::str_tolower($unicode_text) !== '"unicode78źź:ăăîîââșșțțşşţţグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode String to UpperCase';
			$tests[] = $the_test;
			if(\SmartUnicode::str_toupper($unicode_text) !== '"UNICODE78ŹŹ:ĂĂÎÎÂÂȘȘȚȚŞŞŢŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode SubString function (without last param)';
			$tests[] = $the_test;
			if(\SmartUnicode::sub_str($unicode_text, 25) !== 'ţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'Unicode SubString function (with last param)';
			$tests[] = $the_test;
			if(\SmartUnicode::sub_str($unicode_text, 25, 3) !== 'ţŢグ') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode SubString Count function';
			$tests[] = $the_test;
			if(\SmartUnicode::substr_count($unicode_text, 'ţ') !== 1) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Unicode String Replace with Limit (Case Sensitive)';
			$tests[] = $the_test;
			if(\SmartUnicode::str_limit_replace('ź', '@', $unicode_text, 1) !== '"Unicode78@ź:ăĂîÎâÂșȘțȚşŞţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'String Replace without Limit (Case Sensitive)';
			$tests[] = $the_test;
			if(str_replace('ź', '@', $unicode_text) !== '"Unicode78@@:ăĂîÎâÂșȘțȚşŞţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		if((string)$err == '') { /* This test fails if the replacements accented characters are different case than one find in string (upper/lower) ... */
			$the_test = 'String Replace without Limit (Case Insensitive) *** Only with unaccented replacements !!';
			$tests[] = $the_test;
			if(str_ireplace('E7', '@', $unicode_text) !== '"Unicod@8źź:ăĂîÎâÂșȘțȚşŞţŢグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Deaccent String';
			$tests[] = $the_test;
			if(\SmartUnicode::deaccent_str($unicode_text) !== '"Unicode78zz:aAiIaAsStTsStT???') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Word Wrap';
			$tests[] = $the_test;
			if(\SmartUnicode::word_wrap($unicode_text, 13, "\n", true, '') !== '"Unicode78źź:'."\n".'ăĂîÎâÂșȘțȚşŞţ'."\n".'Ţグッド') {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'YAML Unicode Test: Compose from Array / Parse from YAML';
			$tests[] = $the_test;
			$test_arr = array(
				'@test' => 'Testing weird key characters',
				'line1' => 'Some ISO-8859-1 String: @ # $ % ^ & * (\') _ - + = { [ ] } ; < ,. > / ? \\ |', 'line2' => 'Unicode (long) String: '.$unicode_text.' '.\SmartUnicode::str_toupper($unicode_text).' '.$unicode_text.' '.\SmartUnicode::str_tolower($unicode_text).' '.$unicode_text.' '.\SmartUnicode::deaccent_str($unicode_text).' '.$unicode_text,
				$unicode_text => 'Unicode as Key',
				'line3' => ['A' => 'b', 100, 'Thousand'],
				'line4' => [1, 0.2, 3.0001],
				'line5' => \date('Y-m-d H:i:s')
			);
			$test_yaml = (string) '# start YAML (to test also comments)'."\n".(new \SmartYamlConverter())->compose($test_arr)."\n".'# end YAML';
			$test_parr = (new \SmartYamlConverter())->parse($test_yaml);
			if($test_arr !== $test_parr) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' #ORIGINAL Array ['.\print_r($test_arr,1).']'."\n\n".'#YAML Array (from YAML String): '.\print_r($test_parr,1)."\n\n".'#YAML String (from ORIGINAL Array): '."\n".$test_yaml;
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'XML Unicode Test: Compose ArrayToXML / Format+Validate / XMLToArray :: Simple / Extended';
			if(\class_exists('\\DOMDocument')) {
				$the_test .= '/ DomXML';
			} //end if
			$tests[] = $the_test;
			$test_arr = array(
				'LINE0' => 'Testing weird key characters with case sensitive keys',
				'line1' => 'Some ISO-8859-1 Unsafe Characters: @ # $ % ^ & * (\') _ - + = { [ " ] } ; < ,. > ~` / ! ? \\ |',
				'line2' => '<Unicode> ("long") \'String\': '.$unicode_text.' '.\SmartUnicode::str_toupper($unicode_text).' '.$unicode_text.' '.\SmartUnicode::str_tolower($unicode_text).' '.$unicode_text.' '.\SmartUnicode::deaccent_str($unicode_text).' '.$unicode_text,
				'line3' => ['A' => 'b', 'c' => 'D', 'e' => '', 'F' => ['g' => 'H', 'i' => '']],
				'line4' => '',
				'line5' => \date('Y-m-d H:i:s'),
				'Line6' => \SmartHashCrypto::md5((string)\time()),
				'linE7' => \SmartHashCrypto::sha1((string)\time()),
				'LiNe8' => \SmartHashCrypto::sha256((string)\time()),
				'LiNE9' => \SmartHashCrypto::sha512((string)\time())
			);
			$test_xml = (string) (new \SmartXmlComposer())->transform($test_arr, 'xml'); // array to xml
			$test_xml = (string) (new \SmartXmlParser())->format($test_xml); // simple and extended
			if(\class_exists('\\DOMDocument')) {
				$test_xml = (string) (new \SmartXmlParser('domxml'))->format($test_xml); // domxml
			} //end if
			$test_parr = (new \SmartXmlParser())->transform($test_xml); // simple : xml to array
			if(!\is_array($test_parr)) {
				$test_parr = array();
			} //end if
			if($test_arr !== $test_parr['xml']) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' #ORIGINAL Array ['.\print_r($test_arr,1).']'."\n\n".'#XML Array (from XML String): '.\print_r($test_parr['xml'],1)."\n\n".'#XML String (from ORIGINAL Array): '."\n".$test_xml;
			} //end if
			$test_parr = (new \SmartXmlParser('extended'))->transform($test_xml); // extended : xml to array
			if(!\is_array($test_parr)) {
				$test_parr = array();
			} //end if
			if(
				(!is_array(($test_parr['xml'] ?? null)))
				OR
				(\Smart::array_size(($test_parr['xml'] ?? null)) <= 0)
				OR
				(\Smart::array_size(($test_parr['xml'][0] ?? null)) <= 0)
				OR
				(\Smart::array_size(($test_parr['xml'][0]['line2'] ?? null)) <= 0)
				OR
				((string)($test_parr['xml'][0]['line2'][0] ?? null) != (string)$test_arr['line2'])
			) {
				$err = 'ERROR: '.$the_test.' EXTENDED FAILED ...'.'#XML Array (from XML String): '.\print_r($test_parr['xml'],1)."\n\n".'#XML String (from ORIGINAL Array): '."\n".$test_xml;
			} //end if
			if(\class_exists('\\DOMDocument')) {
				$test_parr = (new \SmartXmlParser('domxml'))->transform($test_xml); // domxml : xml to array
				if(!\is_array($test_parr)) {
					$test_parr = array();
				} //end if
				if(((string)$test_parr['@root'] != 'xml') OR ((string)$test_parr['line2'] != (string)$test_arr['line2'])) {
					$err = 'ERROR: '.$the_test.' EXTENDED FAILED ...'.'#XML Array (from XML String): '.\print_r($test_parr,1)."\n\n".'#XML String (from ORIGINAL Array): '."\n".$test_xml;
				} //end if
			} //end if
		} //end if
		//--
		$the_random_unicode_text = (string) \SmartHashCrypto::sha1((string)$unicode_text.\Smart::random_number(1000,9999)).'-'.$unicode_text." \r\n\t".'-'.\Smart::uuid_10_num().'-'.\Smart::uuid_10_str().'-'.\Smart::uuid_10_seq();
		$arch_text = (string) str_repeat($lorem_iso_text."\t".$unicode_text, 1150)."\n".$the_random_unicode_text; // ~ 65KB
		//--
		if((string)$err == '') {
			$the_test = 'Data: Archive / Unarchive (v3)';
			$tests[] = $the_test;
			$testPhpArchDataV3 = 'HclNDoIwEEDhw3CBoZ3OwFbwJ2lqCBSVJVhKE4kCCxM5vYTdy/vwSZ4FKXICMGVgyXsTkqfdxPYoZdw8JkeKMcp0VRuZ4E1GbTvX5mVHdzcwuUkV37U/Hx76Mlx19wMQufkU4LMmNLYDtZTV/D72QzwGG07o8z8='."\n".'[SFZ.20231031/B64.ZLibRaw.hex]'."\n".'(5uWtdgv2ICZ71xLMEKSYXxPMEL8Yamlx3losdn)';
			if((string)\SmartZLib::dataUnarchive((string)$testPhpArchDataV3) !== (string)$lorem_iso_text) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
			if((string)\SmartZLib::dataUnarchive((string)\SmartZLib::dataArchive((string)$arch_text)) !== (string)$arch_text) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$the_random_unicode_text.']'; // too long to include all, the rest is fixed text ; include just the random part
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Data: Unarchive (v2)';
			$tests[] = $the_test;
			$testPhpArchDataV2 = 'Hcm5DcNADETRYtTAmscMGSs0HBkqgHv1X4IEZR//2cCmwDGlWbJR+TYMG6/J85C0xz+YcNpxfv/XTxzH6qymQ8khMStZKxlhHrLQZ+MuM4rVzmRGN/FUdV+aO2T0Gw=='."\n".'SFZ.20210818/B64.ZLibRaw.hex';
			if((string)\SmartZLib::dataUnarchive((string)$testPhpArchDataV2) !== (string)$lorem_iso_text) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'Data: Unarchive (v1)';
			$tests[] = $the_test;
			$testPhpArchDataV1 = 'HclBDkBAEETRw1hLplupZimDSMRKHMD06Psfgdj9/IfM1ZQ9Z00YLVlnfxNc+Zt+j6Phc+HM3tDkbcn7eR3tuU3SDKGhjwrCUaM4i6dbS7r9qRgEdIsq6i8='."\n".'PHP.SF.151129/B64.ZLibRaw.HEX';
			if((string)\SmartZLib::dataUnarchive((string)$testPhpArchDataV1) !== (string)$lorem_iso_text) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'CacheVar: Compress / Uncompress';
			$tests[] = $the_test;
			if(\SmartPersistentCache::varUncompress(\SmartPersistentCache::varCompress(['Test:Encode/Decode'=>$the_random_unicode_text])) !== (array)['Test:Encode/Decode'=>$the_random_unicode_text]) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$the_random_unicode_text.']';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'CacheVar: Encode / Decode';
			$tests[] = $the_test;
			if(\SmartPersistentCache::varDecode(\SmartPersistentCache::varEncode(['Test:Encode/Decode'=>$the_random_unicode_text])) !== (array)['Test:Encode/Decode'=>$the_random_unicode_text]) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$the_random_unicode_text.']';
			} //end if
		} //end if
		//--

		//--
		if((string)$err == '') {
			$the_test = 'IDN: Domain Punycode Encode UTF-8 to ISO';
			$tests[] = $the_test;
			if((string)(new \SmartPunycode())->encode($idn_domain_unicode) != (string)$idn_domain_iso) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$idn_domain_unicode.' -> '.$idn_domain_iso.']';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'IDN: Domain Punycode Decode ISO to UTF-8';
			$tests[] = $the_test;
			if((string)(new \SmartPunycode())->decode($idn_domain_iso) != (string)$idn_domain_unicode) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$idn_domain_iso.' -> '.$idn_domain_unicode.']';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'IDN: Email Punycode Encode UTF-8 to ISO';
			$tests[] = $the_test;
			if((string)(new \SmartPunycode())->encode($idn_email_unicode) != (string)$idn_email_iso) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$idn_email_unicode.' -> '.$idn_email_iso.']';
			} //end if
		} //end if
		if((string)$err == '') {
			$the_test = 'IDN: Email Punycode Decode ISO to UTF-8';
			$tests[] = $the_test;
			if((string)(new \SmartPunycode())->decode($idn_email_iso) != (string)$idn_email_unicode) {
				$err = 'ERROR: '.$the_test.' FAILED ...'.' ['.$idn_email_iso.' -> '.$idn_email_unicode.']';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			//-- regex positive tests
			$arr_regex = [
				'number-integer' 		=> [ 0, '75', '-101' ],
				'number-decimal' 		=> [ 0, '0.0', '0.1', '75', '75.0', '75.1', '-555', '-555.0', '-555.1' ],
				'number-list-integer' 	=> '1;2;30',
				'number-list-decimal' 	=> '1.0;2;30.44',
				'url' 					=> [ 'https://192.168.1.0', 'http://localhost', 'https://www.dom.ext', 'http://dom.ext/path?a=b&c=d%20#s' ],
				'domain' 				=> [ 'domain.com', 'sdom.domain.org' ],
				'email' 				=> [ 'root@localhost', 'root@localhost.loc', 'sometest-name.extra@dom.ext' ],
				'fax' 					=> [ '~+99-(0)999-123.456.78~' ],
				'macaddr' 				=> [ '00:0A:95:9d:68:16', '00-0a-95-9D-68-16' ],
				'ipv4' 					=> [ '192.168.0.1', '169.254.1.0', '1.0.0.1' ],
				'ipv6' 					=> [ '::1', '0000:0000:0000:0000:0000:0000:0000:0001', '2001:0db8:0000:0000:0000:ff00:0042:8329', '2001:dB8::2:1', '2001:db8::1', '3731:54:65fe:2::a7' ]
			];
			//--
			foreach((array)$arr_regex as $key => $val) {
				//--
				if(\is_array($val)) {
					for($i=0; $i<\Smart::array_size($val); $i++) {
						$the_test = 'Regex Validate Positive (#'.$i.'): '.$key.' ['.$val[$i].']';
						$tests[] = $the_test;
						if(\SmartValidator::validate_string($val[$i], $key) !== true) {
							$err = 'ERROR: '.$the_test.' FAILED ...';
							break;
						} //end if
						if((\stripos((string)$key, 'number-') === 0) AND (\stripos((string)$key, 'number-list-') === false)) {
							$the_test = 'Regex Validate Numeric Positive (#'.$i.'): '.$key.' ['.$val[$i].']';
							$tests[] = $the_test;
							if(\SmartValidator::validate_numeric_integer_or_decimal_values($val[$i], $key) !== true) {
								$err = 'ERROR: '.$the_test.' FAILED ...';
								break;
							} //end if
						} //end if
					} //end for
				} else {
					$the_test = 'Regex Validate Positive: '.$key.' ['.$val.']';
					$tests[] = $the_test;
					if(\SmartValidator::validate_string($val, $key) !== true) {
						$err = 'ERROR: '.$the_test.' FAILED ...';
					} //end if
					if((\stripos((string)$key, 'number-') === 0) AND (\stripos((string)$key, 'number-list-') === false)) {
						$the_test = 'Regex Validate Numeric Positive: '.$key.' ['.$val.']';
						$tests[] = $the_test;
						if(\SmartValidator::validate_numeric_integer_or_decimal_values($val, $key) !== true) {
							$err = 'ERROR: '.$the_test.' FAILED ...';
						} //end if
					} //end if
				} //end if else
				//--
				if((string)$err != '') {
					break;
				} //end if
				//--
			} //end foreach
			//-- regex negative tests
			$arr_regex = [
				'number-integer' 		=> [ '', '.', 'a9', '7B', '-9 ', ' -7' ],
				'number-decimal' 		=> [ '', '.0', '.1', '-.10', ' -7', '-9.0 ' ],
				'number-list-integer' 	=> '1;2.3;30',
				'number-list-decimal' 	=> '1.0;2;30.44a',
				'url' 					=> [ 'http:://192.168.1.0', 'https://local host', 'http:/www.dom.ext', 'https:dom.ext/path?a=b&c=d%20#s' ],
				'domain' 				=> [ 'doMain.com', 's dom.domain.org', '.dom.ext', 'dom..ext', 'localhost', 'loc', 'dom.ext.' ],
				'email' 				=> [ 'rooT@localhost', 'root@local host.loc', 'sometest-name.extra@do_m.ext' ],
				'fax' 					=> [ '~ +99-(0)999-123.456.78 ~' ],
				'macaddr' 				=> [ '00:0A:95:9z:68:16', '00-0Z-95-9D-68-16' ],
				'ipv4' 					=> [ '192.168.0.', '169..1.0', '1.0.1' ],
				'ipv6' 					=> [ '::x', '00z0:0000:0000:0000:0000:0000:0000:0001', '2001:0dx8:0000:0000:0000:ff00:0042:8329', '2001:WB8::2:1', '2001:@db8::1', '3731:54:65Qe:2::a7' ]
			];
			//--
			foreach((array)$arr_regex as $key => $val) {
				//--
				if(\is_array($val)) {
					for($i=0; $i<\Smart::array_size($val); $i++) {
						$the_test = 'Regex Validate Negative (#'.$i.'): '.$key.' ['.$val[$i].']';
						$tests[] = $the_test;
						if(\SmartValidator::validate_string($val[$i], $key) === true) {
							$err = 'ERROR: '.$the_test.' FAILED ...';
							break;
						} //end if
					} //end for
				} else {
					$the_test = 'Regex Validate Negative: '.$key.' ['.$val.']';
					$tests[] = $the_test;
					if(\SmartValidator::validate_string($val, $key) === true) {
						$err = 'ERROR: '.$the_test.' FAILED ...';
					} //end if
				} //end if else
				//--
				if((string)$err != '') {
					break;
				} //end if
				//--
			} //end foreach
			//--
		} //end if
		//--


		//-- Lib Robot Security
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #1';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'http://test.loc/admin.php?abc=def';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #2';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'https://test.loc/admin.php?abc=def';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #3';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'admin.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() === true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #4';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'task.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) (((\SmartEnvironment::isAdminArea() === true) AND (\SmartEnvironment::isTaskArea() === true)) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #5';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'index.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() !== true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #6';
			$tests[] = $the_test;
			$url_trusted_ref = (string) '?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() !== true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #7';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'admin.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() === true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #8';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'task.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) (((\SmartEnvironment::isAdminArea() === true) AND (\SmartEnvironment::isTaskArea() === true)) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #9';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'index.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() !== true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #10';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			$allow_cred_trusted_ref = (string) ((\SmartEnvironment::isAdminArea() !== true) ? 'yes' : 'no');
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != (string)$allow_cred_trusted_ref)
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'yes')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #11';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'test.php';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #12';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'test.php';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #13';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'test.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #14';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'test.php?';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #15';
			$tests[] = $the_test;
			$url_trusted_ref = (string) 'abc/def/';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)\SmartUtils::get_server_current_url().$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//--
		if((string)$err == '') {
			$the_test = 'URL Trust Reference Test #16';
			$tests[] = $the_test;
			$url_trusted_ref = (string) \SmartUtils::get_server_current_url().'abc/def/';
			$arr_trusted_ref = (array) \SmartRobot::get_url_or_path_trust_reference((string)$url_trusted_ref);
			if(
				((string)$arr_trusted_ref['url-or-path-fixed'] != (string)$url_trusted_ref)
				OR
				((string)$arr_trusted_ref['url-or-path-type'] != 'url')
				OR
				((string)$arr_trusted_ref['allow-credentials'] != 'no')
				OR
				((string)$arr_trusted_ref['trust-headers'] != 'no')
			) {
				$err = 'ERROR: '.$the_test.' FAILED ...';
			} //end if
		} //end if
		//-- #end lib robot security

		//--
		$time = 'TOTAL TIME was: '.(\microtime(true) - $time);
		//--
		$end_tests = '===== END TESTS ... '.$time.' sec. =====';
		//--

		//--
		$img_check = 'modules/mod-samples/libs/templates/testunit/img/test-strings.svg';
		if((string)$err == '') {
			$img_sign  = 'lib/framework/img/sign-info.svg';
			$text_main = '<span style="color:#83B953;">Test OK: PHP Unicode Strings.</span>';
			$text_info = '<h2><span style="color:#83B953;">All</span> the SmartFramework Unicode String <span style="color:#83B953;">Tests PASSED on PHP</span><hr></h2><span style="font-size:14px;">'.\Smart::nl_2_br(\Smart::escape_html(\implode("\n".'* ', $tests)."\n".$end_tests)).'</span>';
		} else {
			$img_sign  = 'lib/framework/img/sign-error.svg';
			$text_main = '<span style="color:#FF5500;">An ERROR occured ... PHP Unicode Strings Test FAILED !</span>';
			$text_info = '<h2><span style="color:#FF5500;">A test FAILED</span> when testing Unicode String Tests.<span style="color:#FF5500;"><hr>FAILED Test Details</span>:</h2><br><h5 class="inline">'.\Smart::escape_html($tests[\Smart::array_size($tests)-1]).'</h5><br><span style="font-size:14px;"><pre>'.\Smart::escape_html($err).'</pre></span>';
		} //end if else
		//--
		$test_info = 'Unicode String Test Suite for SmartFramework: PHP';
		//--
		$test_heading = 'SmartFramework Unicode Strings Tests: DONE ...';
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


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
