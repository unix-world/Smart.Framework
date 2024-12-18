<?php
// [LIB - Smart.Framework / Plugins / YAML Parser]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - YAML Parser
// DEPENDS:
//	* Smart::
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


//-- Smart YAML Parser
//
// [Simple PHP YAML Class.]
// This class can be used to read a YAML file (or string) and convert its contents into a PHP array.
// It currently supports a very simple (limited) subsection of the YAML spec.
//
// Based on Spyc v.0.5 with fixes from 0.5.1
// (c) 2005-2006 Chris Wanstrath, 2006-2011 Vlad Andersen under the MIT License
//
// (c) 2014-2022 unix-world.org, fixes and modifications by unixman (iradu@unix-world.org)
// includes many fixes and modification to be unicode compliant and some bug fixes under BSD License
// [REGEX-SAFE-OK]
//--

/**
 * Class: SmartYamlConverter - provides a YAML Converter (Parser and Composer).
 * It will parse YAML to a PHP array, and also will compose YAML from a PHP array.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20220912
 * @package 	Plugins:ConvertersAndParsers
 *
 */
final class SmartYamlConverter {

	// ->

	// TODO:
	//		* {{{BUG-YAML-COMMENTS}}} ; until this bug will be fixed DO NOT USE single ' or double " quotes after YAML comments starting with a #

	//================================================================
	//--
	private $yaml_dump_indent;
	private $yaml_dump_force_quotes = false; // setting this to true will force YAML Dump to enclose any string value in quotes
	private $yaml_contains_group_anchor = false;
	private $yaml_contains_group_alias = false;
	private $path;
	private $result;
	private $yaml_arr_saved_groups = [];
	private $indent;
	//--
	private $delayedPath = array(); // array :: Path modifier that should be applied after adding current element.
	//--
	private $logerr = true;
	private $err = '';
	//--
	private const const_REMPTY = "\0\0\0\0\0"; // sequence of null bytes ; leave it with double quotes !
	//--
	private const const_YML_SAFETY_KEY = 'YamL78lMy'; // use a very special and unique prefix for special keys
	private const const_YML_SAFETY_HASH = 'f3b2840d7eb8c2cc8b221828e0cd1fc56511fa6ed3650340ef3be489c724f749b87da1965d502110f73330e230a719e6a9c518951046c00d2edffa3dd7dd1f23'; // sha512 hex of self::const_YML_SAFETY_KEY."\t".self::const_REMPTY
	//--
	private const const_YML_SAFETY_PREFIX = self::const_YML_SAFETY_KEY.self::const_YML_SAFETY_HASH; // use only this prefix for special keys
	private const const_YML_LITERAL_PLACEHOLDER = '___'.self::const_YML_SAFETY_PREFIX.'_Literal_Block___';
	//--
	//================================================================


	//================================================================
	// Constructor
	public function __construct($log_errors=true, $dump_force_quotes=false) {
		//--
		$this->logerr = (bool) $log_errors;
		$this->yaml_dump_force_quotes = (bool) $dump_force_quotes;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * @return STRING the Error Message if Any
	 */
	public function getError() {
		//--
		return (string) $this->err;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	* Create a PHP array from YAML string
	*
	* @param string $input
	* @return array
	*/
	public function parse($input) {
		//--
		$this->err = '';
		//--
		return (array) $this->loadWithSource((array)$this->loadFromString((string)$input));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Create YAML from PHP array
	 *
	 * The dump method, when supplied with an array, will do its best
	 * to convert the array into friendly YAML.  Pretty simple.  Feel free to
	 * save the returned string as tasteful.yaml and pass it around.
	 *
	 * Oh, and you can decide how big the indent is and what the wordwrap
	 * for folding is.  Pretty cool -- just pass in 'false' for either if
	 * you want to use the default.
	 *
	 * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
	 * you can turn off wordwrap by passing in 0.
	 *
	 * @access public

	 * @param array $array PHP array
	 * @param int $indent Pass in false to use the default, which is 2
	 * @return string YAML
	 */
	public function compose(array $array, $indent=2) {
		//--
		// Dumps to some very clean YAML.  We'll have to add some more features
		// and options soon.  And better support for folding.
		//--
		$this->err = '';
		//-- New features and options.
		if(!is_int($indent)) {
			$this->yaml_dump_indent = 2;
		} else {
			$this->yaml_dump_indent = $indent;
		} //end if
		//-- New YAML document
		//$string = "---\n";
		$string = '';
		//-- Start at the base of the array and move through it.
		if($array) {
			$previous_key = -1;
			foreach((array)$array as $key => $value) {
				if(!isset($first_key)) {
					$first_key = $key;
				} //end if
				$string .= (string) $this->_yamlize($key, $value, 0, $previous_key, $first_key, $array);
				$previous_key = $key;
			} //end foreach
		} //end if
		//--
		return (string) $string;
		//--
	} //END FUNCTION
	//================================================================


	### PRIVATES


	//================================================================
	/**
	 * Attempts to convert a key / value array item to YAML
	 * @access private
	 * @return string
	 * @param $key The name of the key
	 * @param $value The value of the item
	 * @param $indent The indent of the current node
	 */
	private function _yamlize($key, $value, $indent, $previous_key=-1, $first_key=0, $source_array=null) {
		//--
		if(is_array($value)) {
			//--
			if(empty ($value)) {
				return $this->_dumpNode($key, array(), $indent, $previous_key, $first_key, $source_array);
			} //end if
			//-- It has children.  What to do? Make it the right kind of item
			$string = $this->_dumpNode($key, self::const_REMPTY, $indent, $previous_key, $first_key, $source_array);
			//-- Add the indent
			$indent += $this->yaml_dump_indent;
			//-- Yamlize the array
			$string .= $this->_yamlizeArray($value, $indent);
		} elseif (!is_array($value)) {
			//-- It doesn't have children.
			$string = $this->_dumpNode($key, $value, $indent, $previous_key, $first_key, $source_array);
			//--
		} //end if else
		//--
		return $string;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Attempts to convert an array to YAML
	 * @access private
	 * @return string
	 * @param $array The array you want to convert
	 * @param $indent The indent of the current level
	 */
	private function _yamlizeArray($array, $indent) {
		//--
		if(is_array($array)) {
			//--
			$string = '';
			$previous_key = -1;
			//--
			foreach($array as $key => $value) {
				//--
				if(!isset($first_key)) {
					$first_key = $key;
				} //end if
				//--
				$string .= $this->_yamlize($key, $value, $indent, $previous_key, $first_key, $array);
				$previous_key = $key;
				//--
			} //end foreach
			//--
			return $string;
			//--
		} else {
			//--
			return false;
			//--
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns YAML from a key and a value
	 * @access private
	 * @return string
	 * @param $key The name of the key
	 * @param $value The value of the item
	 * @param $indent The indent of the current node
	 */
	private function _dumpNode($key, $value, $indent, $previous_key=-1, $first_key=0, $source_array=null) {
		//-- do some folding here, for blocks
		if(
			is_string($value) &&
			(
				(
					strpos((string)$value, "\n") !== false ||
					strpos((string)$value, ": ") !== false ||
					strpos((string)$value, "- ") !== false ||
					strpos((string)$value, "*") !== false  ||
					strpos((string)$value, "#") !== false  ||
					strpos((string)$value, "<") !== false  ||
					strpos((string)$value, ">") !== false  ||
					strpos((string)$value, '  ') !== false ||
					strpos((string)$value, "[") !== false  ||
					strpos((string)$value, "]") !== false  ||
					strpos((string)$value, "{") !== false  ||
					strpos((string)$value, "}") !== false
				) ||
				strpos((string)$value, "&") !== false  ||
				strpos((string)$value, "'") !== false  ||
				strpos((string)$value, "!") === 0      ||
				substr((string)$value, -1, 1) == ':'
			)
		) {
			$value = $this->_doLiteralBlock($value, $indent);
		} else {
			$value  = $this->_doFolding($value, $indent);
		} //end if else
		//--
		if($value === array()) {
			$value = '[ ]';
		} //end if
		if(in_array($value, array('true', 'TRUE', 'false', 'FALSE', 'y', 'Y', 'n', 'N', 'null', 'NULL'), true)) {
			$value = $this->_doLiteralBlock($value, $indent);
		} //end if
		if((string)trim((string)$value) != (string)$value) {
			$value = $this->_doLiteralBlock($value, $indent);
		} //end if
		if(is_bool($value)) {
			$value = ($value) ? "true" : "false";
		} //end if
		//--
		if($value === null) {
			$value = 'null';
		} //end if
		if($value === "'".self::const_REMPTY."'") {
			$value = null;
		} //end if
		//--
		$spaces = str_repeat(' ', $indent);
		//--
		//if(is_int($key) && $key - 1 == $previous_key && $first_key===0) {
		if(is_array($source_array) && array_keys($source_array) === range(0, Smart::array_size($source_array) - 1)) {
			// It's a sequence
			$string = $spaces.'- '.$value."\n";
		} else {
			//--
			//if($first_key===0) { @http_response_code(500); die('YAML // Keys are all screwy.  The first one was zero, now it\'s "'. $key .'"'); }
			//-- It's mapped
			if(strpos($key, ":") !== false || strpos($key, "#") !== false) {
				$key = '"'.$key.'"';
			} //end if
			//--
			$string = rtrim($spaces.$key.': '.$value)."\n";
			//--
		} //end if else
		//--
		return $string;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Creates a literal block for dumping
	 * @access private
	 * @return string
	 * @param $value
	 * @param $indent int The value of the indent
	 */
	private function _doLiteralBlock($value, $indent) {
		//--
		if($value === "\n") {
			return '\n';
		} //end if
		//--
		if(strpos((string)$value, "\n") === false && strpos((string)$value, "'") === false) {
			return sprintf("'%s'", $value);
		} //end if
		if(strpos((string)$value, "\n") === false && strpos((string)$value, '"') === false) {
			return sprintf('"%s"', $value);
		} //end if
		//--
		$exploded = (array) explode("\n", (string)$value);
		$newValue = '|';
		$indent += $this->yaml_dump_indent;
		$spaces = str_repeat(' ', $indent);
		//--
		foreach($exploded as $key => $line) {
			$newValue .= "\n".$spaces.($line);
		} //end foreach
		//--
		return (string) $newValue;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Folds a string of text, if necessary
	 * @access private
	 * @return string
	 * @param $value The string you wish to fold
	 */
	private function _doFolding($value, $indent) {
		//--
		if($this->yaml_dump_force_quotes && is_string($value) && $value !== self::const_REMPTY) {
			//$value = '"'.$value.'"';
			$value = "'".str_replace("'", "\\'", $value)."'"; // fix by unixman
		} //end if
		//--
		return $value;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function loadWithSource($Source) {
		//--
		if(empty($Source)) {
			return array();
		} //end if
		//--
		$this->path = array();
		$this->result = array();
		//--
		$cnt = Smart::array_size($Source);
		for($i = 0; $i < $cnt; $i++) {
			$line = $Source[$i];
			$this->indent = strlen($line) - strlen(ltrim($line));
			$tempPath = $this->getParentPathByIndent($this->indent);
			$line = $this->stripIndent($line, $this->indent);
			if($this->isComment($line)) {
				continue;
			} //end if
			if($this->isEmpty($line)) {
				continue;
			} //end if
			$this->path = $tempPath;
			$literalBlockStyle = $this->startsLiteralBlock($line);
			if($literalBlockStyle) {
				$line = rtrim($line, $literalBlockStyle." \n");
				$literalBlock = '';
				$line .= self::const_YML_LITERAL_PLACEHOLDER;
				$literal_block_indent = strlen($Source[$i+1]) - strlen(ltrim($Source[$i+1]));
				while(++$i < $cnt && $this->literalBlockContinues($Source[$i], $this->indent)) {
				  $literalBlock = $this->addLiteralLine($literalBlock, $Source[$i], $literalBlockStyle, $literal_block_indent);
				} //end while
				$i--;
			} //end if
			//-- Strip out comments fix #8 from v.0.5.1 # {{{BUG-YAML-COMMENTS}}} # TODO: the below fix will not remove comments after # if enclosed in single or double quotes ; example: "value" # "comment" or "value" # 'comment'
			if(strpos($line, '#') !== false) {
				$line = preg_replace('/\s*#([^"\']+)$/', '', $line);
			} //end if
			//-- fix from #5 from v.0.5.1 (moved here from above strip comments)
			while(++$i < $cnt && $this->greedilyNeedNextLine($line)) {
				$line = rtrim($line, " \n\t\r").' '.ltrim($Source[$i], " \t");
			} //end while
			$i--;
			//--
			$lineArray = $this->_parseLine($line);
			if($literalBlockStyle) {
				$lineArray = $this->revertLiteralYamlPlaceHolder($lineArray, $literalBlock);
			} //end if
			$this->addArray($lineArray, $this->indent);
			foreach($this->delayedPath as $indent => $delayedPath) {
				$this->path[$indent] = $delayedPath;
			} //end foreach
			$this->delayedPath = array();
		} //end for
		//--
		return $this->result;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function loadFromString($input) {
		//-- dissalow special prefix
		$safety_prefix = (string) ucfirst((string)strrev((string)strtolower((string)self::const_YML_SAFETY_PREFIX)));
		$input = (string) str_replace((string)self::const_YML_SAFETY_PREFIX, (string)$safety_prefix, (string)$input); // replace all possible interferences with safety keys
		//--
		$lines = (array) explode("\n", (string)$input);
		//--
		foreach($lines as $k => $v) {
			$lines[$k] = (string) rtrim((string)$v, "\r");
		} //end foreach
		//--
		return (array) $lines;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Parses YAML code and returns an array for a node
	 * @access private
	 * @return array
	 * @param string $line A line from the YAML file
	 */
	private function _parseLine($line) {
		//--
		if(!$line) {
			return array();
		} //end if
		//--
		$line = trim($line);
		//--
		if(!$line) {
			return array();
		} //end if
		//--
		$array = array();
		//--
		$group = $this->nodeContainsGroup($line);
		if($group) {
			$this->addGroup($line, $group);
			$line = $this->stripGroup ($line, $group);
		} //end if
		//--
		if($this->startsMappedSequence($line)) {
			return $this->returnMappedSequence($line);
		} //end if
		if($this->startsMappedValue($line)) {
			return $this->returnMappedValue($line);
		} //end if
		if($this->isArrayElement($line)) {
			return $this->returnArrayElement($line);
		} //end if
		if($this->isPlainArray($line)) {
			return $this->returnPlainArray($line);
		} //end if
		//--
		return $this->returnKeyValuePair($line);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Finds the type of the passed value, returns the value as the new type.
	 * @access private
	 * @param string $value
	 * @return mixed
	 */
	private function _toType($value) {
		//--
		if($value === '') {
			return null;
		} //end if
		//--
	//	$first_character = $value[0];
		$first_character = (string) substr((string)$value,  0, 1);
		$last_character  = (string) substr((string)$value, -1, 1);
		//--
		$is_quoted = false;
		do {
		//	if(!$value) {
			if((string)$value == '') {
				break;
			} //end if
			if(((string)$first_character != '"') && ((string)$first_character != "'")) {
				break;
			} //end if
			if(((string)$last_character != '"') && ((string)$last_character != "'")) {
				break;
			} //end if
			$is_quoted = true;
		} while(0);
		//--
		if($is_quoted) {
			return strtr((string)substr((string)$value, 1, -1), array ('\\"' => '"', '\'\'' => '\'', '\\\'' => '\''));
		} //end if
		//--
		if(strpos((string)$value, ' #') !== false && !$is_quoted) {
			$value = (string) preg_replace('/\s+#(.+)$/', '', (string)$value);
		} //end if
		//--
		if(!$is_quoted) {
			$value = (string) str_replace('\n', "\n", (string)$value);
		} //end if
		//--
		if(((string)$first_character == '[') && ((string)$last_character == ']')) {
			//-- Take out strings sequences and mappings
			$innerValue = (string) trim((string)substr((string)$value, 1, -1));
			if($innerValue === '') {
				return array();
			} //end if
			$explode = $this->_inlineEscape($innerValue);
			//-- Propagate value array
			$value  = array();
			foreach($explode as $z => $v) {
				$value[] = $this->_toType($v);
			} //end foreach
			//-- return
			return $value;
			//--
		} //end if
		//--
		if((strpos((string)$value, ': ') !== false) && ((string)$first_character != '{')) {
			$array = (array) explode(': ', $value);
			$key   = (string) trim((string)(isset($array[0]) ? $array[0] : ''));
			array_shift($array);
			$value = (string) trim((string)implode(': ', $array));
			$value = $this->_toType($value);
			return array($key => $value);
		} //end ifexplode
		//--
		if(((string)$first_character == '{') && ((string)$last_character == '}')) {
			$innerValue = (string) trim((string)substr((string)$value, 1, -1));
			if($innerValue === '') {
				return array();
			} //end if
			// Inline Mapping
			// Take out strings sequences and mappings
			$explode = $this->_inlineEscape($innerValue);
			// Propagate value array
			$array = array();
			foreach($explode as $z => $v) {
				$SubArr = $this->_toType($v);
				if(empty($SubArr)) {
					continue;
				} //end if
				if(is_array($SubArr)) {
					$array[key($SubArr)] = $SubArr[key($SubArr)];
					continue;
				} //end if
				$array[] = $SubArr;
			} //end foreach
			return $array;
		} //end if
		//--
		if((string)$value == '') {
			return '';
		} //end if
		//--
		if(((string)strtolower((string)$value) == 'null') || ((string)$value == '~')) {
			return null;
		} //end if
		//--
		if(is_numeric($value) && preg_match('/^(-|)[1-9]+[0-9]*$/', (string)$value)){
			$intvalue = (int) $value;
			if($intvalue != PHP_INT_MAX) {
				$value = $intvalue;
			} //end if
			return $value;
		} //end if
		//--
		/* this was added in v.0.5.1 but is unsafe !!
		if(is_numeric($value) && preg_match('/^0[xX][0-9a-fA-F]+$/', $value)) {
			// Hexadecimal value.
			return hexdec($value);
		} //end if
		*/
		//--
		if(in_array((string)strtolower((string)$value), array('true', 'on', '+', 'yes', 'y'))) {
			return true;
		} //end if
		//--
		if(in_array((string)strtolower((string)$value), array('false', 'off', '-', 'no', 'n'))) {
			return false;
		} //end if
		//--
		if(is_numeric($value)) {
			if((string)$value == '0') {
				return 0;
			} //end if
			if((string)rtrim((string)$value, 0) === (string)$value) {
				$value = (float) $value;
			} //end if
			return $value;
		} //end if
		//--
		return $value;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Used in inlines to check for more inlines or quoted strings
	 * @access private
	 * @return array
	 */
	private function _inlineEscape($inline) {
		//--
		// There's gotta be a cleaner way to do this...
		// While pure sequences seem to be nesting just fine,
		// pure mappings and mappings with sequences inside can't go very
		// deep.  This needs to be fixed.
		//--
		$seqs = array();
		$maps = array();
		$saved_strings = array();
		$saved_empties = array();
		//-- Check for empty strings fix from v.0.5.1
		$regex = '/("")|(\'\')/';
		$pcre = preg_match_all($regex, $inline, $strings);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR (1): '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return $inline;
		} //end if
		if($pcre) {
			$saved_empties = $strings[0];
			$inline = preg_replace($regex, self::const_YML_SAFETY_PREFIX.'__Empty', $inline);
		} //end if
		unset($regex);
		//-- Check for strings
		$regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
		$pcre = preg_match_all($regex, $inline, $strings);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR (2): '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return $inline;
		} //end if
		if($pcre) {
			$saved_strings = $strings[0];
			$inline = preg_replace($regex, self::const_YML_SAFETY_PREFIX.'__String', $inline);
		} //end if
		unset($regex);
		//--
		$i = 0;
		$regex_seq = '/\[([^{}\[\]]+)\]/U';
		$regex_map = '/{([^\[\]{}]+)}/U';
		do {
			// Check for sequences
			while(preg_match($regex_seq, $inline, $matchseqs)) {
				$seqs[] = $matchseqs[0];
				$inline = preg_replace($regex_seq, (self::const_YML_SAFETY_PREFIX.'__Seq'.(Smart::array_size($seqs) - 1).'s'), $inline, 1); // safe
			} //end while
			// Check for mappings
			while(preg_match($regex_map, $inline, $matchmaps)) {
				$maps[] = $matchmaps[0];
				$inline = preg_replace($regex_map, (self::const_YML_SAFETY_PREFIX.'__Map'.(Smart::array_size($maps) - 1).'s'), $inline, 1); // safe
			} //end while
			if($i++ >= 10) {
				break;
			} //end if
		} while(strpos($inline, '[') !== false || strpos($inline, '{') !== false);
		unset($regex_seq);
		unset($regex_map);
		//--
		$explode = (array) explode(', ', $inline);
		$stringi = 0;
		$i = 0;
		//--
		while(1) {
			//-- Re-add the sequences
			if(!empty($seqs)) {
				foreach($explode as $key => $value) {
					if(strpos($value, self::const_YML_SAFETY_PREFIX.'__Seq') !== false) {
						foreach($seqs as $seqk => $seq) {
							$explode[$key] = str_replace((self::const_YML_SAFETY_PREFIX.'__Seq'.$seqk.'s'), $seq, $value);
							$value = $explode[$key];
						} //end foreach
					} //end if
				} //end foreach
			} //end if
			//-- Re-add the mappings
			if(!empty($maps)) {
				foreach($explode as $key => $value) {
					if(strpos($value, self::const_YML_SAFETY_PREFIX.'__Map') !== false) {
						foreach($maps as $mapk => $map) {
							$explode[$key] = str_replace((self::const_YML_SAFETY_PREFIX.'__Map'.$mapk.'s'), $map, $value);
							$value = $explode[$key];
						} //end foreach
					} //end if
				} //end foreach
			} //end if
			//-- Re-add the strings
			if(!empty($saved_strings)) {
				foreach ($explode as $key => $value) {
					while(strpos($value, self::const_YML_SAFETY_PREFIX.'__String') !== false) {
						//-- fix by unixman (security issue, unsafe preg_replace may lead to unpredictable results if a string inside YAML syntax like [ 'a', 'b', '$1', '\\1', "xYz", ... ] contains a regex backtrace like in the example ...)
					//	$explode[$key] = (string) preg_replace('/'.self::const_YML_SAFETY_PREFIX.'__String/', $saved_strings[$stringi], $value, 1); // unsafe, if the $saved_strings[$stringi] contains any regex back reference such as $1 or \\1 ... the results are unpredictable !
						$explode[$key] = (string) preg_replace_callback( // this is safe !
							'/'.self::const_YML_SAFETY_PREFIX.'__String/',
							function($matches) use ($saved_strings, $stringi) {
								return (string) ($saved_strings[$stringi] ?? '');
							},
							(string) $value,
							1
						);
						//-- #end fix by unixman
						unset($saved_strings[$stringi]);
						++$stringi;
						$value = $explode[$key];
					} //end while
				} //end foreach
			} //end if
			//-- Re-add the empty strings fix from v.0.5.1
			if(!empty($saved_empties)) {
				foreach($explode as $key => $value) {
					while(strpos($value, self::const_YML_SAFETY_PREFIX.'__Empty') !== false) {
						$explode[$key] = preg_replace('/'.self::const_YML_SAFETY_PREFIX.'__Empty/', '', $value, 1);
						$value = $explode[$key];
					} //end while
				} //end foreach
			} //end if
			//--
			$finished = true;
			foreach($explode as $key => $value) {
				if(strpos($value, self::const_YML_SAFETY_PREFIX.'__Seq') !== false) {
					$finished = false; break;
				} //end if
				if(strpos($value, self::const_YML_SAFETY_PREFIX.'__Map') !== false) {
					$finished = false; break;
				} //end if
				if(strpos($value, self::const_YML_SAFETY_PREFIX.'__String') !== false) {
					$finished = false; break;
				} //end if
				if(strpos($value, self::const_YML_SAFETY_PREFIX.'__Empty') !== false) { // fix from v.0.5.1
					$finished = false; break;
				} //end if
			} //end foreach
			if($finished) {
				break;
			} //end if
			$i++;
			if($i > 10) {
				break; // Prevent infinite loops.
			} //end if
			//--
		} //end while
		//--
		return $explode;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function literalBlockContinues($line, $lineIndent) {
		//--
		if(!trim($line)) {
			return true;
		} //end if
		//--
		if(strlen($line) - strlen(ltrim($line)) > $lineIndent) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function referenceContentsByAlias($alias) {
		//--
		do {
			//-
			if(!isset($this->yaml_arr_saved_groups[$alias])) {
				$this->err = 'Bad group name: '.$alias;
				if($this->logerr !== false) {
					Smart::log_warning('YAML // '.$this->err);
				} //end if
				break; // just in case
			} //end if
			//--
			$groupPath = $this->yaml_arr_saved_groups[$alias];
			$value = $this->result;
			//--
			foreach($groupPath as $z => $k) {
				$value = $value[$k];
			} //end foreach
			//--
		} while(false);
		//--
		return $value;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function addArrayInline($array, $indent) {
		//--
		$CommonGroupPath = $this->path;
		//--
		if(empty($array)) {
			return false;
		} //end if
		//--
		foreach($array as $k => $v) {
			$this->addArray(array($k => $v), $indent);
			$this->path = $CommonGroupPath;
		} //end foreach
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function addArray($incoming_data, $incoming_indent) {
		//--
		// print_r ($incoming_data);
		//--
		if(Smart::array_size($incoming_data) > 1) {
			return $this->addArrayInline ($incoming_data, $incoming_indent);
		} //end if
		//--
		$key = key($incoming_data);
		$value = isset($incoming_data[$key]) ? $incoming_data[$key] : null;
		if($key === '__!'.self::const_YML_SAFETY_PREFIX.'__Zero') {
			$key = '0';
		} //end if
		//--
		if($incoming_indent == 0 && !$this->yaml_contains_group_alias && !$this->yaml_contains_group_anchor) { // Shortcut for root-level values.
			if($key || $key === '' || $key === '0') {
				$this->result[$key] = $value;
			} else {
				$this->result[] = $value;
				end($this->result);
				$key = key($this->result);
			} //end if else
			$this->path[$incoming_indent] = $key;
			return;
		} //end if
		//--
		$history = array();
		//-- Unfolding inner array tree.
		$history[] = $tmp_arr = $this->result;
		foreach($this->path as $z => $k) {
			$history[] = $tmp_arr = $tmp_arr[$k];
		} //end foreach
		//--
		if($this->yaml_contains_group_alias) {
			$value = $this->referenceContentsByAlias($this->yaml_contains_group_alias);
			$this->yaml_contains_group_alias = false;
		} //end if
		//-- Adding string or numeric key to the innermost level or $this->arr.
		if(is_string($key) && $key == '<<') {
			if(!is_array ($tmp_arr)) {
				$tmp_arr = array ();
			} //end if
			$tmp_arr = array_merge($tmp_arr, $value);
		} elseif($key || $key === '' || $key === '0') {
			if (!is_array ($tmp_arr)) {
				$tmp_arr = array ($key=>$value);
			} else {
				$tmp_arr[$key] = $value;
			} //end if else
		} else {
			if(!is_array ($tmp_arr)) {
				$tmp_arr = array ($value); $key = 0;
			} else {
				$tmp_arr[] = $value;
				end($tmp_arr);
				$key = key($tmp_arr);
			} //end if else
		} //end if else
		//--
		$reverse_path = array_reverse($this->path);
		$reverse_history = array_reverse($history);
		$reverse_history[0] = $tmp_arr;
		$cnt = Smart::array_size($reverse_history) - 1;
		for($i=0; $i<$cnt; $i++) {
			$reverse_history[$i+1][$reverse_path[$i]] = $reverse_history[$i];
		} //end for
		$this->result = $reverse_history[$cnt];
		$this->path[$incoming_indent] = $key;
		//--
		if($this->yaml_contains_group_anchor) {
			$this->yaml_arr_saved_groups[$this->yaml_contains_group_anchor] = $this->path;
			if(is_array ($value)) {
				$k = key($value);
				if(!is_int ($k)) {
					$this->yaml_arr_saved_groups[$this->yaml_contains_group_anchor][$incoming_indent + 2] = $k;
				} //end if
			} //end if
			$this->yaml_contains_group_anchor = false;
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function startsLiteralBlock($line) {
		//--
		$lastChar = (string) substr((string)trim((string)$line), -1);
		//--
		if($lastChar != '>' && $lastChar != '|') {
			return false;
		} //end if
		//--
		if((string)$lastChar == '|') {
			return $lastChar;
		} //end if
		//-- HTML tags should not be counted as literal blocks.
		if(preg_match('#<.*'.'?'.'>$#', $line)) {
			return false;
		} //end if
		//--
		return $lastChar;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function greedilyNeedNextLine($line) {
		//--
		$line = (string) trim($line);
		//--
		if(!strlen($line)) {
			return false;
		} //end if
		//--
		if((string)substr($line, -1, 1) == ']') {
			return false;
		} //end if
		if($line[0] == '[') {
			return true;
		} //end if
		if(preg_match('#^[^:]+?:\s*\[#', $line)) {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function addLiteralLine ($literalBlock, $line, $literalBlockStyle, $indent = -1) {
		//--
		$line = $this->stripIndent($line, $indent);
		//--
		if($literalBlockStyle !== '|') {
			$line = $this->stripIndent($line);
		} //end if
		//--
		$line = (string) rtrim($line, "\r\n\t ")."\n";
		//--
		if($literalBlockStyle == '|') {
			return $literalBlock . $line;
		} //end if
		//--
		if(strlen($line) <= 0) {
			return rtrim($literalBlock, ' ')."\n";
		} //end if
		//--
		if($line == "\n" && $literalBlockStyle == '>') {
			return rtrim ($literalBlock, " \t")."\n";
		} //end if
		//--
		if($line != "\n") {
			$line = trim ($line, "\r\n ") . " ";
		} //end if
		//--
		return $literalBlock.$line;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function revertLiteralYamlPlaceHolder($lineArray, $literalBlock) {
		//--
		foreach($lineArray as $k => $v) {
			if(is_array($v)) {
				$lineArray[$k] = $this->revertLiteralYamlPlaceHolder($v, $literalBlock);
			} elseif((string)substr($v, -1 * strlen(self::const_YML_LITERAL_PLACEHOLDER)) == (string)self::const_YML_LITERAL_PLACEHOLDER) {
				$lineArray[$k] = rtrim($literalBlock, " \r\n");
			} //end if else
		} //end foreach
		//--
		return $lineArray;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function stripIndent($line, $indent = -1) {
		//--
		if($indent == -1) {
			$indent = strlen($line) - strlen(ltrim($line));
		} //end if
		//--
		return substr($line, $indent);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function getParentPathByIndent($indent) {
		//--
		if($indent == 0) {
			return array();
		} //end if
		//--
		$linePath = $this->path;
		//--
		do {
			end($linePath);
			$lastIndentInParentPath = key($linePath);
			if($indent <= $lastIndentInParentPath) {
				array_pop($linePath);
			} //end if
		} while($indent <= $lastIndentInParentPath);
		//--
		return $linePath;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function clearBiggerPathValues($indent) {
		//--
		if($indent == 0) {
			$this->path = array();
		} //end if
		//--
		if(empty($this->path)) {
			return true;
		} //end if
		//--
		foreach($this->path as $k => $v) {
			if($k > $indent) {
				unset($this->path[$k]);
			} //end if
		} //end foreach
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isComment($line) {
		//--
		if(!$line) {
			return false;
		} //end if
		//--
		if($line[0] == '#') {
			return true;
		} //end if
		//--
		if(trim($line, " \r\n\t") == '---') {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isEmpty($line) {
		//--
		return (trim($line) === '');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isArrayElement($line) {
		//--
		if(!$line) {
			return false;
		} //end if
		//--
		if($line[0] != '-') {
			return false;
		} //end if
		//--
		if(strlen($line) > 3) {
			if(substr($line, 0, 3) == '---') {
				return false;
			} //end if
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isHashElement($line) {
		//--
		return strpos($line, ':');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isLiteral($line) {
		//--
		if($this->isArrayElement($line)) {
			return false;
		} //end if
		//--
		if($this->isHashElement($line)) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function unquote($value) {
		//--
		if(!$value) {
			return $value;
		} //end if
		if(!is_string($value)) {
			return $value;
		} //end if
		if($value[0] == '\'') {
			return trim($value, '\'');
		} //end if
		if($value[0] == '"') {
			return trim($value, '"');
		} //end if
		//--
		return $value; // mixed
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function startsMappedSequence($line) {
		//--
		return (($line[0] == '-') && (substr($line, -1, 1) == ':'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function returnMappedSequence($line) {
		//--
		$array = array();
		$key = $this->unquote(trim(substr($line, 1, -1)));
		$array[$key] = array();
		$this->delayedPath = array(strpos($line, $key) + $this->indent => $key);
		//--
		return array($array);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function returnMappedValue($line) {
		//--
		$array = array();
		$key = $this->unquote(trim(substr($line, 0, -1)));
		$array[$key] = '';
		//--
		return $array;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function startsMappedValue($line) {
		//--
		return (substr($line, -1, 1) == ':');
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function isPlainArray($line) {
		//--
		return (($line[0] == '[') && (substr($line, -1, 1) == ']'));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function returnPlainArray($line) {
		//--
		return $this->_toType($line);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function returnKeyValuePair($line) {
		//--
		$array = array();
		$key = '';
		//--
		if(strpos($line, ':') !== false) {
			// It's a key/value pair most likely
			// If the key is in double quotes pull it out
			if(($line[0] == '"' || $line[0] == "'") && preg_match('/^(["\'](.*)["\'](\s)*:)/', $line, $matches)) {
				$value = trim(str_replace($matches[1], '', $line));
				$key   = $matches[2];
			} else {
				// Do some guesswork as to the key and the value
				$explode = (array) explode(':', $line);
				$key     = (string) trim((string)(isset($explode[0]) ? $explode[0] : ''));
				array_shift($explode);
				$value   = trim(implode(':', $explode));
			} //end if else
			// Set the type of the value.  Int, string, etc
			$value = $this->_toType($value);
			if($key === '0') {
				$key = '__!'.self::const_YML_SAFETY_PREFIX.'__Zero';
			} //end if
			$array[$key] = $value;
		} else {
			$array = array ($line);
		} //end if else
		//--
		return $array;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function returnArrayElement($line) {
		//--
		if(strlen($line) <= 1) {
			return array(array()); // weird ...
		} //end if
		//--
		$array = array();
		$value   = trim(substr($line, 1));
		$value   = $this->_toType($value);
		$array[] = $value;
		//--
		return $array;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function nodeContainsGroup($line) {
		//--
		$symbolsForReference = 'A-z0-9_\-';
		//--
		if(strpos($line, '&') === false && strpos($line, '*') === false) {
			return false; // fast stop
		} //end if
		if($line[0] == '&' && preg_match('/^(&['.$symbolsForReference.']+)/', $line, $matches)) {
			return $matches[1];
		} //end if
		if($line[0] == '*' && preg_match('/^(\*['.$symbolsForReference.']+)/', $line, $matches)) {
			return $matches[1];
		} //end if
		if(preg_match('/(&['.$symbolsForReference.']+)$/', $line, $matches)) {
			return $matches[1];
		} //end if
		if(preg_match('/(\*['.$symbolsForReference.']+$)/', $line, $matches)) {
			return $matches[1];
		} //end if
		if(preg_match('#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $matches)) {
			return $matches[1];
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function addGroup($line, $group) {
		//--
		if($group[0] == '&') {
			$this->yaml_contains_group_anchor = substr($group, 1);
		} //end if
		if($group[0] == '*') {
			$this->yaml_contains_group_alias = substr($group, 1);
		} //end if
		//print_r($this->path);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function stripGroup($line, $group) {
		//--
		$line = trim(str_replace($group, '', $line));
		//--
		return $line;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


/*
// Usage YAML -> PHP:
$yaml  = new SmartYamlConverter();
$yaml_string = file_get_contents('file.yaml');
$array = $yaml->parse($yaml_string);
print_r($array);
// Usage PHP -> YAML:
echo $yaml->compose($array);
$err = $yaml->getError(); // returns empty or err msg
*/

/* Sample YAML Code
YAML Data:
---
String: "Anyone's name, really.: Me"
Int: 13
True: true
False: false
Zero: 0
Null: null
NotNull: 'null'
NotTrue: 'y'
NotBoolTrue: 'true'
NotInt: 5
Float: 5.34
Negative: -90
SmallFloat: 0.7
NewLine: \n
0: PHP Class
1: Basic YAML Loader
2: Very Basic YAML Dumper
3:
  - YAML is so easy to learn.
  - >
	Your config files will never be the
	same.
4:
  cpu: 1.5ghz
  ram: 1 gig
  os: os x 10.4.1
domains:
  - yaml.org
  - php.net
5:
  program: Adium
  platform: OS X
  type: Chat Client
no time: |
  There isn't any time for your tricks!
  Do you understand?
some time: |
  There is nothing but time
  for your tricks.
databases:
  -
	name: spartan
	notes:
	  - Needs to be backed up
	  - Needs to be normalized
	type: pgsql
"if: you'd": like
6:
  - One
  - Two
  - Three
  - Four
7:
  - One
  -
	- Two
	- And
	- Three
  - Four
  - Five
8:
  - This
  -
	- Is
	- Getting
	-
	  - Ridiculous
	  - Guys
  - Seriously
  -
	- Show
	- That
9:
  name: John
  age: Doe
  brand: Dunhill Grey
*/


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

//end of php code
