<?php
// [LIB - Smart.Framework / Plugins / XML Parser and Composer]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - XMLToArray / ArrayToXML
// DEPENDS:
//	* Smart::
// DEPENDS-EXT: PHP 7+ (option: LIBXML_BIGLINES) ; PHP XML Extension
//======================================================

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartXmlParser - Create a PHP Array from simple XML structures.
 * XML tag attributes are supported but not parsed.
 *
 * <code>
 *   //-- Sample usage:
 *   $arr = (new SmartXmlParser())->transform('<xml><data>1</data></xml>'); // [OK]
 *   print_r($arr);
 *   //--
 * </code>
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       The XML Parser may handle UTF-8 (default) and ISO-8859-1 encodings
 *
 * @access      PUBLIC
 * @depends     extensions: PHP XML ; classes: Smart
 * @version     v.20250107
 * @package     Plugins:ConvertersAndParsers
 *
 */
final class SmartXmlParser {

	// ->

	//===============================
	private $encoding = 'ISO-8859-1';
	private $mode = 'simple'; // simple | simple+attributes | extended | domxml (requires/prefer: DOMDocument)
	//===============================


	//===============================
	public function __construct(?string $mode='simple', ?string $encoding='') {
		//--
		if((string)$encoding == '') {
			if(defined('SMART_FRAMEWORK_CHARSET')) {
				if((string)SMART_FRAMEWORK_CHARSET != '') {
					$this->encoding = (string) SMART_FRAMEWORK_CHARSET;
				} //end if
			} //end if
		} else {
			$this->encoding = (string) $encoding;
		} //end if
		//--
		$mode = (string) strtolower((string)$mode);
		if((string)$mode === 'domxml') {
			$this->mode = 'domxml';
		} elseif((string)$mode === 'extended') {
			$this->mode = 'extended';
		} elseif((string)$mode === 'simple+attributes') {
			$this->mode = 'simple+attributes';
		} else { // simple
			$this->mode = 'simple';
		} //end if else
		//--
	} //END FUNCTION
	//===============================


	//=============================== Safe Validate and Format XML ; DomXML if used will also apply prettyPrint
	public function format(?string $xml_str, bool $preserve_whitespace=false, bool $log_parse_err_warns=false, bool $use_strict_validation=false, bool $remove_xml_header=false) : string {

		//--
		$xml_str = (string) trim((string)$xml_str);
		if((string)$xml_str == '') {
			return '';
		} //end if
		//--

		//--
		$xml_str = (string) str_replace(["\r\n", "\r", "\x0B", "\0", "\f"], ["\n", "\n", ' ', ' ', ' '], (string)$xml_str); // fixes
		//--

		//--
		$validate_mode = 'simplexml'; // 'simple' | 'extended'
		if((string)$this->mode == 'domxml') {
			if(class_exists('DOMDocument')) {
				$validate_mode = 'domxml'; // 'domxml'
			} elseif($use_strict_validation === true) {
				Smart::log_warning(__METHOD__.' # WARNING [XML-Format('.$this->mode.') / Encoding: '.$this->encoding.']:'."\n".'The PHP DOMDocument class is missing ; Using SimpleXML instead ...'."\n".'#END'."\n");
			} //end if
		} //end if
		//--
		if((string)$validate_mode == 'simplexml') {
			if(!function_exists('simplexml_load_string')) {
				Smart::raise_error(
					__METHOD__.' :: Missing PHP SimpleXML Extension'
				);
				return '';
			} //end if
		} //end if
		//--

		//--
		@libxml_use_internal_errors(true);
		@libxml_clear_errors();
		//--

		//--
		if((string)$validate_mode == 'domxml') {
			//--
			$dom = new DOMDocument('1.0', (string)SMART_FRAMEWORK_CHARSET);
			$dom->encoding = (string) SMART_FRAMEWORK_CHARSET;
			$dom->strictErrorChecking = false; 							// do not throw errors
			$dom->preserveWhiteSpace = (bool) $preserve_whitespace; 	// remove or not redundant white space
			$dom->formatOutput = true; 									// try to format pretty-print the code (will work just partial as the preserve white space is true ...)
			$dom->resolveExternals = false; 							// disable load external entities from a doctype declaration
			$dom->validateOnParse = false; 								// this must be explicit disabled as if set to true it may try to download the DTD and after to validate (insecure ...)
			//--
			@$dom->loadXML(
				(string) $this->FixXmlHeader((string)$xml_str), // need to fix just xml header
				LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOCDATA // {{{SYNC-LIBXML-OPTIONS}}} ; Fix: LIBXML_NOCDATA converts all CDATA to String
			);
		} else { // simpleXML
			//--
			$sxml = new SimpleXMLElement(
				(string) $this->FixXmlHeader((string)$xml_str), // need to fix just xml header
				LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOCDATA // {{{SYNC-LIBXML-OPTIONS}}} ; Fix: LIBXML_NOCDATA converts all CDATA to String
			);
			//--
		} //end if else
		//--

		//-- log errors if any
		if((SmartEnvironment::ifDebug()) OR ($log_parse_err_warns === true)) { // log errors if set
			$errors = (array) @libxml_get_errors();
			if(Smart::array_size($errors) > 0) {
				$notice_log = '';
				foreach($errors as $z => $error) {
					if(is_object($error)) {
						$notice_log .= 'FORMAT-ERROR: ['.$error->code.'] / Level: '.$error->level.' / Line: '.$error->line.' / Column: '.$error->column.' / Message: '.trim((string)$error->message)."\n";
					} //end if
				} //end foreach
				if((string)$notice_log != '') {
					Smart::log_notice(__METHOD__.' # NOTICE [XML-Format('.$this->mode.') / Encoding: '.$this->encoding.']:'."\n".$notice_log."\n".'#END'."\n");
				} //end if
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # DEBUG [XML-Format('.$this->mode.') / Encoding: '.$this->encoding.'] @ XML-String:'."\n".$xml_str."\n".'#END');
				} //end if
			} //end if
		} //end if
		//--

		//--
		if((string)$validate_mode == 'domxml') {
			if(is_object($dom)) {
				$xml_str = (string) @$dom->saveXML();
				$xml_str = (string) trim((string)$xml_str);
				if((string)$xml_str != '') {
					$xml_str .= "\n".'<!-- SafeFilter: SmartFramework.XML.Format(Validate:DomXML) -->';
				} //end if
			} else {
				$xml_str = ''; // document is not valid, return empty string
			} //end if else
			$dom = null; // free mem
		} else { // simpleXML
			if(is_object($sxml)) {
				$xml_str = (string) @$sxml->asXML();
				$xml_str = (string) trim((string)$xml_str);
				if((string)$xml_str != '') {
					$xml_str .= "\n".'<!-- SafeFilter: SmartFramework.XML.Format(Validate:SimpleXML) -->';
				} //end if
			} else {
				$xml_str = ''; // document is not valid, return empty string
			} //end if else
			$sxml = null; // free mem
		} //end if else
		//--

		//--
		@libxml_clear_errors();
		@libxml_use_internal_errors(false);
		//--

		//--
		if($remove_xml_header === true) {
			$xml_str = (string) $this->RemoveXmlHeader((string)$xml_str);
		} //end if
		//--

		//--
		return (string) $xml_str;
		//--

	} //END FUNCTION
	//===============================


	//===============================
	public function transform(?string $xml_str, bool $log_parse_err_warns=false) : array {

		//--
		$xml_str = (string) trim((string)$xml_str);
		if((string)$xml_str == '') {
			return array();
		} //end if
		//--

		//--
		@libxml_use_internal_errors(true);
		@libxml_clear_errors();
		//--

		//-- convert
		if((string)$this->mode == 'domxml') {
			//--
			$arr = (array) $this->DomXML2Array((string)$xml_str);
			//--
		} else { // simple | simple+attributes | extended
			//--
			$arr = (array) $this->SimpleXML2Array((string)$xml_str);
			//--
		} //end if else
		//-- FIX: json encode / decode forces to sanitize and convert any remaining xml type objects into sub-arrays (especially on SimpleXML, but also may appear on DomXML) !
		$arr = (array) Smart::json_decode(
			(string)Smart::json_encode(
				(array) $arr,
				false, // no pretty print
				true, // unescaped unicode
				false // html safe
			),
			true // return array
		);
		//--

		//-- log errors if any
		if((SmartEnvironment::ifDebug()) OR ($log_parse_err_warns === true)) { // log errors if set
			$errors = (array) @libxml_get_errors();
			if(Smart::array_size($errors) > 0) {
				$notice_log = '';
				foreach($errors as $z => $error) {
					if(is_object($error)) {
						$notice_log .= 'PARSE-ERROR: ['.$error->code.'] / Level: '.$error->level.' / Line: '.$error->line.' / Column: '.$error->column.' / Message: '.$error->message."\n";
					} //end if
				} //end foreach
				if((string)$notice_log != '') {
					Smart::log_notice(__METHOD__.' # NOTICE [XML-Process('.$this->mode.') / Encoding: '.$this->encoding.']:'."\n".$notice_log."\n".'#END'."\n");
				} //end if
				if(SmartEnvironment::ifDebug()) {
					Smart::log_notice(__METHOD__.' # DEBUG [XML-Process('.$this->mode.') / Encoding: '.$this->encoding.'] @ XML-String:'."\n".$xml_str."\n".'#END');
				} //end if
			} //end if
		} //end if
		//--

		//--
		@libxml_clear_errors();
		@libxml_use_internal_errors(false);
		//--

		//--
		if(Smart::array_size($arr) <= 0) {
			$arr = array('XML@PARSER:ERROR' => __CLASS__.' # No XML Data or Invalid Data'); // in case of error, return this
		} //end if
		//--

		//--
		return (array) $arr;
		//--

	} //END FUNCTION
	//===============================


	##### PRIVATES


	//===============================
	private function SimpleXML2Array(string $xml_str) : array {
		//--
		if(!function_exists('simplexml_load_string')) {
			Smart::raise_error(
				__METHOD__.' :: Missing PHP SimpleXML Extension'
			);
			return array();
		} //end if
		//--
		return (array) $this->SimpleXMLNode2Array(
			@simplexml_load_string( // object not array !!
				$this->FixSimpleXmlRoot((string)$xml_str), // simplexml needs an xml root to give the same array back ; also fixes the xml header, too
				'SimpleXMLElement', // this element class is referenced and check in SimpleXMLNode2Array
				LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOCDATA // {{{SYNC-LIBXML-OPTIONS}}} ; Fix: LIBXML_NOCDATA converts all CDATA to String
			)
		);
		//--
	} //END FUNCTION
	//===============================


	//===============================
	private function SimpleXMLNode2Array($sxml) : array {
		//-- $sxml is MIXED type !
		if(!is_object($sxml)) {
			return array();
		} //end if
		if((string)get_class($sxml) !== 'SimpleXMLElement') {
			return array();
		} //end if
		//--
		if(((string)$this->mode === 'simple') OR ((string)$this->mode === 'simple+attributes')) { // fix: ex: array 0,1,2 is parsed in a wrong way: 20231207
			//--
			$sxml = Smart::json_decode(Smart::json_encode($sxml, false, true, false)); // $sxml is MIXED type !
			if(!is_array($sxml)) {
				$sxml = [];
			} //end if
			//--
			$sxml = (array) $this->FixEmptyArraysToEmptyString((array)$sxml);
			//--
			return (array) $sxml;
			//--
		} elseif($this->mode !== 'extended') {
			//--
			return array();
			//--
		} //end if #end fix
		//--
		$arr = array();
		//--
		foreach($sxml->children() as $r) {
			//--
			$t = (string) $r->getName();
			//--
			if($this->mode === 'extended') { // this is just for extended mode
				//--
				$tmp_atts = (array) $r->attributes();
				if(array_key_exists('@attributes', $tmp_atts)) {
					$arr[$t.'|@attributes'][] = (array) $tmp_atts['@attributes'];
				} else {
					$arr[$t.'|@attributes'][] = array();
				} //end if else
				$tmp_atts = null;
				if((int)$r->count() <= 0) {
					$arr[$t][] = (string) $r; // array ; force add as toString
				} else {
					$arr[$t][] = (array) $this->SimpleXMLNode2Array($r); // array ; force add as array ; $r is MIXED !
				} //end if else
				//--
			} //end if else
			//--
		} //end foreach
		//--
		return (array) $arr; // return array
		//--
	} //END FUNCTION
	//===============================


	//=============================== # fix: ex: array 0,1,2 is parsed in a wrong way: 20231207
	private function FixEmptyArraysToEmptyString(array $arr) : array {
		//-- only for: simple or simple+attributes ; it does convert empty sub-arrays as empty strings to easy operations ; for simple mode also removes the @attributes
		foreach($arr as $key => $val) {
			if(is_array($val)) {
				if(((string)$this->mode === 'simple') AND ((string)$key == '@attributes')) {
					unset($arr[$key]); // not for: 'simple+attributes' ; unset just for 'simple' mode ...
				} else {
					if(count($val) <= 0) {
						$arr[$key] = '';
					} else {
						$arr[$key] = (array) $this->FixEmptyArraysToEmptyString((array)$arr[$key]);
					} //end if else
				} //end if else
			} //end if
		} //end foreach
		//--
		if(!is_array($arr)) {
			$arr = [];
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//===============================


	//===============================
	// convert xml string to php array - useful to get a serializable value
	// original author: Adrien aka Gaarf & contributors # http://gaarf.info/2009/08/13/xml-string-to-php-array/
	private function DomXML2Array(string $xmlstr) : array {
		//--
		if(!class_exists('DOMDocument')) {
			Smart::log_warning(__METHOD__.' # WARNING [XML-Process('.$this->mode.') / Encoding: '.$this->encoding.']:'."\n".'The PHP DOMDocument() class is missing ...'."\n".'#END'."\n");
			return array();
		} //end if
		//--
		$dom = new DOMDocument('1.0', (string)SMART_FRAMEWORK_CHARSET);
		//--
		$dom->encoding = (string) SMART_FRAMEWORK_CHARSET;
		$dom->strictErrorChecking = false; 	// do not throw errors
		$dom->preserveWhiteSpace = true; 	// do not remove redundant white space
		$dom->formatOutput = false; 		// do not try to format pretty-print the code
		$dom->resolveExternals = false; 	// disable load external entities from a doctype declaration
		$dom->validateOnParse = false; 		// this must be explicit disabled as if set to true it may try to download the DTD and after to validate (insecure ...)
		//--
		@$dom->loadXML(
			(string) $this->FixXmlHeader((string)$xmlstr), // need to fix just xml header
			LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOCDATA // {{{SYNC-LIBXML-OPTIONS}}} ; Fix: LIBXML_NOCDATA converts all CDATA to String
		);
		//--
		$root = null;
		if(is_object($dom)) {
			$root = $dom->documentElement;
		} //end if
		$dom = null; // free mem
		//--
		$output = array();
		if(is_object($root)) {
			$output = SmartDomUtils::DomNode2Array($root); // mixed
		} //end if
		if(!is_array($output)) {
			$output = array();
		} //end if
		if($root->tagName) {
			$output['@root'] = (string) $root->tagName;
		} //end if
		//--
		return (array) $output;
		//--
	} //END FUNCTION
	//===============================


	//===============================
	private function RemoveXmlHeader(string $xml_str) : string {
		//--
		return (string) trim((string)preg_replace('#<\?xml (.*?)>#si', '', (string)$xml_str)); // remove the xml markup tag ; extra: str_replace(['<'.'?', '?'.'>'], ['<!-- ', ' -->'], $xml_str); // comment out any markup tags
		//--
	} //END FUNCTION
	//===============================


	//===============================
	private function AddXmlHeader(string $xml_str) : string {
		//--
		$xml_str = '<'.'?'.'xml version="1.0" encoding="'.Smart::escape_html((string)strtoupper((string)$this->encoding)).'"'.'?'.'>'."\n".$xml_str;
		//--
		return (string) $xml_str;
		//--
	} //END FUNCTION
	//===============================


	//===============================
	private function FixXmlHeader(string $xml_str) : string {
		//--
		$xml_str = (string) trim((string)$xml_str);
		if((string)$xml_str == '') {
			return '';
		} //end if
		//--
		$xml_str = (string) $this->RemoveXmlHeader((string)$xml_str);
		//--
		if(!SmartValidator::validate_html_or_xml_code((string)$xml_str)) { // fix parser bug if empty data passed
			return ''; // invalid xml
		} //end if
		//--
		return (string) $this->AddXmlHeader((string)$xml_str);
		//--
	} //END FUNCTION
	//===============================


	//=============================== fix for simpleXML parser to array ; needs an external xml root to the supplied xml
	private function FixSimpleXmlRoot(string $xml_str) : string {
		//--
		$xml_str = (string) $this->RemoveXmlHeader((string)$xml_str);
		//--
		if(!SmartValidator::validate_html_or_xml_code((string)$xml_str)) { // fix parser bug if empty data passed
			Smart::log_notice(__METHOD__.' # GetXMLTree: Invalid XML Detected (555)'."\n".'Encoding: '.$this->encoding.' // Xml-String:'."\n".$xml_str."\n".'#END');
			$xml_str = ''; // clear invalid xml
		} //end if
		//--
		return (string) $this->AddXmlHeader('<smart_framework_xml_data_parser_fix_tag>'."\n".trim((string)$xml_str)."\n".'</smart_framework_xml_data_parser_fix_tag>');
		//--
	} //END FUNCTION
	//===============================


} //END CLASS


/*** Test Extended XML String
<ab></ab>
<cd stt="2"></cd>
<ef>x</ef>
<gh att="3">y</gh>
<meal>
	<test></test>
	<type active="yes">Lunch</type>
	<time>12:30</time>
	<menu>
	 <name></name>
	 <xname att="7"></xname>
	  <entree>salad</entree>
	  <xentree att="t">cabbage</xentree>
	  <maincourse>
	  </maincourse>
	  <maincourse att="xxl">
	  </maincourse>
	  <maincourse>
		  <part></part>
	  </maincourse>
	  <maincourse>
		  <part att="one"></part>
	  </maincourse>
	  <maincourse>
		  <part>blu</part>
	  </maincourse>
	  <maincourse>
		  <part>ships</part>
		  <part>steak</part>
	  </maincourse>
	  <maincourse att="z">
		  <part att="f">fisch</part>
		  <part att="d">rice</part>
	  </maincourse>
	  <maincourse>
		  <part>wine</part>
		  <part>cheese</part>
		  <part>eggs</part>
	  </maincourse>
	</menu>
</meal>
***/


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================



//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartXmlComposer - Create simple XML structure from a PHP Array.
 * XML tag attributes are not supported.
 *
 * <code>
 *   //-- Sample use:
 *   $array = array(
 *   	'id' => '15',
 *   	'name' => 'Test',
 *   	'data' => array(
 *   		'key1' => '12345',
 *   		'key2' => '67890',
 *   		'key3' => 'ABCDEF'
 *   	),
 *   	'date' => '2016-02-05 09:30:05'
 *   );
 *   $xml = (new SmartXmlComposer())->transform($array, 'myxml');
 *   echo $xml;
 *   //-- will have something like:
 *   <myxml>
 *     <id>15</id>
 *     <name>Test</name>
 *     <data>
 *       <key1>12345</key1>
 *       <key2>67890</key2>
 *       <key3>ABCDEF</key3>
 *     </data>
 *     <date>2016-02-05 09:30:05</date>
 *   </myxml>
 *   //--
 * </code>
 *
 * @usage       dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints       -
 *
 * @access      PUBLIC
 * @depends     classes: Smart
 * @version     v.20250107
 * @package     Plugins:ConvertersAndParsers
 *
 */
final class SmartXmlComposer {

	// ->

	//===============================
	private $encoding = 'ISO-8859-1';
	//===============================


	//===============================
	public function __construct(?string $encoding='') {
		//--
		if((string)$encoding == '') {
			if(defined('SMART_FRAMEWORK_CHARSET')) {
				if((string)SMART_FRAMEWORK_CHARSET != '') {
					$this->encoding = (string) SMART_FRAMEWORK_CHARSET;
				} //end if
			} //end if
		} else {
			$this->encoding = (string) $encoding;
		} //end if
		//--
	} //END FUNCTION
	//===============================


	//===============================
	public function transform(array $y_array, ?string $xmlroot='xml') : string {
		//--
		$xmlroot = (string) trim((string)$xmlroot);
		if((string)$xmlroot == '') {
			$xml_tag_start = '';
			$xml_tag_end = '';
		} else {
			$xml_tag_start = "\n".'<'.Smart::escape_html((string)$xmlroot).'>';
			$xml_tag_end = "\n".'</'.Smart::escape_html((string)$xmlroot).'>';
		} //end if
		//--
		return '<'.'?xml version="1.0" encoding="'.Smart::escape_html((string)$this->encoding).'"?'.'>'.$xml_tag_start."\n".trim((string)$this->CreateFromArr((array)$y_array)).$xml_tag_end;
		//--
	} //END FUNCTION
	//===============================


	##### PRIVATES


	//===============================
	private function CreateFromArr(array $y_array) : string {

		//--
		if(!is_array($y_array)) {
			Smart::log_warning(__METHOD__.' # expects an Array as parameter ...');
			return '<error>XML Writer requires an Array as parameter</error>';
		} //end if
		//--

		//--
		$out = '';
		//--
		$arrtype = Smart::array_type_test($y_array); // 0: not an array ; 1: non-associative ; 2: associative
		//--
		foreach($y_array as $key => $val) {
			//--
			if($arrtype === 2) { // fix keys for associative array
				if((is_bool($key)) OR (is_numeric($key)) OR ((string)$key == '')) {
					$key = (string) '_'.$key; // boolean, numeric or empty keys are not xml compliant, will be converted to string and prefixed with an underscore: _
				} //end if
			} //end if
			//--
			if(is_array($val)) {
				if(is_numeric($key)) { // this can happen only if non-associative array as for associative arrays the numeric key is fixed above as _#
					$out .= (string) $this->CreateFromArr((array)$val);
				} else {
					$out .= '<'.Smart::escape_html((string)$key).'>'."\n".$this->CreateFromArr((array)$val).'</'.Smart::escape_html((string)$key).'>'."\n";
				} //end if else
			} elseif(Smart::is_nscalar($val)) {
				if((string)trim((string)$val) != '') {
					$out .= '<'.Smart::escape_html((string)$key).'>'.Smart::escape_html((string)$val).'</'.Smart::escape_html((string)$key).'>'."\n";
				} else {
					$out .= '<'.Smart::escape_html((string)$key).' />'."\n";
				} //end if else
			} else { // invalid
				Smart::log_warning(__METHOD__.' # Non-Scalar Value detected in Array: '.print_r($val,1));
			} //end if else
			//--
		} //end foreach
		//--

		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//===============================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class Smart DOM (Document) Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250107
 *
 */
final class SmartDomUtils {

	// ::

	//===============================
	public static function DomHTML2Array(?string $source, ?string $mode='grouped') {
		//--
		if(!class_exists('DOMDocument')) {
			Smart::log_warning(__METHOD__.' # The PHP DOMDocument() class is missing ...');
			return array();
		} //end if
		//--
		if((string)trim((string)$source) == '') { // the html source
			return array();
		} //end if
		//--
		@libxml_use_internal_errors(true);
		@libxml_clear_errors();
		//-- {{{SYNC-DOM-HTML-OPTIONS}}}
		$dom = new DOMDocument('5', (string)SMART_FRAMEWORK_CHARSET);
		//--
		$dom->encoding = (string) SMART_FRAMEWORK_CHARSET;
		$dom->strictErrorChecking = false; 	// do not throw errors
		$dom->preserveWhiteSpace = false; 	// set this to false in order to real format HTML ...
		$dom->formatOutput = false; 		// try to format pretty-print the code (will work just partial as the preserve white space is true ...)
		$dom->resolveExternals = false; 	// disable load external entities from a doctype declaration
		$dom->validateOnParse = false; 		// this must be explicit disabled as if set to true it may try to download the DTD and after to validate (insecure ...)
		$dom->recover = true; // trying to parse non-well formed documents, for HTML make sense but not for XML
	//	$dom->substituteEntities = false; 	// this attribute ir proprietary for LibXML, it does not make any difference ... still buggy with replacing &quot; with " (it's decoded value)
		//-- pre fixes
		$source = (string) str_replace('&quot;', '&Prime;', (string)$source); // fix: DomDocument will decode the &quot; to ", thus substitute with &Prime; (&#8243;) which is a unicode verion of it ″ and restore back thereafter ; if there are any &Prime; already converting &Prime; to &quot; later is not a problem ...
		//--
		@$dom->loadHTML(
			(string) $source, // fix: in some versions of DomDocument or LibXML if not enclosed in a body container there are some strange behaviours when getting back the HTML code, so need this function: compose_html_document
			LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_HTML_NODEFDTD // {{{SYNC-LIBXML-OPTIONS}}} ; important !!! do not use the buggy flag LIBXML_HTML_NOIMPLIED as it will mess up the tags, is not stable enough inside LibXML
		);
		//--
		@libxml_clear_errors();
		@libxml_use_internal_errors(false);
		//--
		$root = null;
		if(is_object($dom)) {
			$root = $dom->documentElement;
		} //end if
		$dom = null; // free mem
		//--
		$output = array();
		if(is_object($root)) {
			$output = SmartDomUtils::DomNode2Array($root, (string)$mode); // mixed
		} //end if
		if(!is_array($output)) {
			$output = array();
		} //end if
		if((string)$mode != 'ordered') {
			if($root->tagName) {
				$output['@root'] = (string) $root->tagName;
			} //end if
		} //end if
		//--
		return (array) $output;
		//--
	} //END FUNCTION
	//===============================


	//===============================
	// EX: $root = $dom->documentElement; print_r(SmartDomUtils::DomNode2Array($root));
	public static function DomNode2Array($node, ?string $mode='grouped') {
		//--
		if((string)$mode == 'ordered') {
			return self::DomNode2OrderedArray($node); // mixed
		} else {
			return self::DomNode2GroupedArray($node); // mixed
		} //end if else
		//--
	} //END FUNCTION
	//===============================


	//===============================
	// build an associative array grouped by tags
	private static function DomNode2GroupedArray($node) {
		//--
		if(!is_object($node)) {
			return array();
		} //end if
		//--
		$output = array();
		//--
		switch($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				//--
				$output = '';
				if((string)trim((string)$node->textContent) != '') {
					$output = (string) $node->textContent; // FIX: to preserve text node exact as it is ...
				} //end if
				//--
				break;
			case XML_ELEMENT_NODE:
				//--
				if(is_object($node->childNodes)) {
					for($i=0, $m=(int)$node->childNodes->length; $i<$m; $i++) {
						$child = $node->childNodes->item($i);
						$v = self::DomNode2GroupedArray($child);
						if(is_array($output) && isset($child->tagName)) {
							$t = (string) $child->tagName;
							if(!isset($output[$t])) {
								$output[$t] = array();
							} //end if
							$output[$t][] = $v;
						} elseif($v || $v === '0') {
							//-- # bug fix r.20210614
						//	$output = (string) $v;
							$output = is_array($v) ? (array) $v : (string) $v;
							//--
						} //end if
					} //end for
					//--
					if(is_object($node->attributes) && $node->attributes->length && !is_array($output)) { // has attributes but isn't an array
						$output = array('@content' => $output); // change output into an array.
					} //end if
					//--
					if(is_array($output)) {
						if(is_object($node->attributes) && $node->attributes->length) {
							$a = array();
							foreach($node->attributes as $attrName => $attrNode) {
								$a[(string)$attrName] = (string) $attrNode->value;
							} //end foreach
							$output['@attributes'] = $a;
						} //end if
						foreach($output as $t => $v) {
							if(is_array($v) && (count($v) == 1) && ((string)$t != '@attributes')) {
								$output[(string)$t] = ($v[0] ?? null);
							} //end if
						} //end foreach
					} //end if
					//--
				} //end if
				//--
				break;
			default:
				// nothing to do
		} //end switch
		//--
		return $output; // mixed
		//--
	} //END FUNCTION
	//===============================


	//===============================
	// build an associative array with all tags in order
	private static function DomNode2OrderedArray($node) {
		//--
		if(!is_object($node)) {
			return array();
		} //end if
		//--
		$output = array();
		//--
		switch($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				//--
				$output = '';
				if((string)trim((string)$node->textContent) != '') {
					$output = (string) $node->textContent; // FIX: to preserve text node exact as it is ...
				} //end if
				//--
				break;
			case XML_ELEMENT_NODE:
				//--
				if(is_object($node->childNodes)) {
					for($i=0, $m=(int)$node->childNodes->length; $i<$m; $i++) {
						$child = $node->childNodes->item($i);
						$v = self::DomNode2OrderedArray($child);
						if(is_array($output) && isset($child->tagName)) {
							$t = (string) $child->tagName;
							$output[] = [
								(string) $t => $v
							];
						} elseif($v || $v === '0') {
							//--
							$output[] = is_array($v) ? (array) $v : (string) $v;
							//--
						} //end if
					} //end for
					//--
					if(is_array($output)) {
						if(is_object($node->attributes) && $node->attributes->length) {
							$a = array();
							foreach($node->attributes as $attrName => $attrNode) {
								$a[(string)$attrName] = (string) $attrNode->value;
							} //end foreach
							$output['@attributes'] = $a;
						} //end if
						foreach($output as $t => $v) {
							if(is_array($v) && (count($v) == 1) && ((string)$t != '@attributes')) {
								$output[] = [
									(string) $t => ($v[0] ?? null)
								];
							} //end if
						} //end foreach
					} //end if
					//--
				} //end if
				//--
				break;
			default:
				// nothing to do
		} //end switch
		//--
		return $output; // mixed
		//--
	} //END FUNCTION
	//===============================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
