<?php
// [LIB - Smart.Framework / Plugins / HTML Parser]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.7.2')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - HTML 5 Parser
// DEPENDS:
//	* Smart::
//======================================================

// [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartHtmlParser - provides a HTML Parser and Cleaner that will sanitize and parse the HTML code.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20210308
 * @package 	Plugins:ConvertersAndParsers
 *
 */
final class SmartHtmlParser {

	// ->


	//-- parsing registers
	private $html 					= '';					// the html string
	private $elements 				= array();				// html elements array
	private $comments 				= array();				// html comments array
	//-- parsing flags
	private $el_parsed 				= false;				// init: false ; will be set to true after html elements are parsed to avoid re-parsing of elements
	private $cm_parsed 				= false;				// init: false ; will be set to true after html comments are parsed to avoid re-parsing of comments
	private $is_std 				= false;				// init: false ; will be set to true after html is standardized to avoid re-standardize
	private $is_clean 				= false;				// init: false ; will be set to true after html is cleaned to avoid re-clean
	//-- extra settings
	private $signature 				= true;					// if set to true will add the signature for the cleanup code as html comments
	private $dom_processing 		= true;					// if set to false will dissalow post-processing of html cleanup with DomDocument ; if set to true (default) if DomDocument class is available will use it for post-processing of html cleanup
	private $dom_log_errors 		= false; 				// if set to true will log LibXML -> DomDocument errors and warnings
	//-- regex expressions
	private $expr_tag_name 			= ''; 					// regex expr: the allowed characters in tag names (just for open tags ... the end tags will add / and space
	private $expr_tag_start 		= ''; 					// regex expr: tag start
	private $expr_tag_end_start 	= ''; 					// regex expr: end tag start
	private $expr_tag_simple_end 	= ''; 					// regex expr: tag end without attributes
	private $expr_tag_complex_end 	= ''; 					// regex expr: tag end with attributes or / (it needs at least one space after tag name)
	//-- regex syntax
	private $regex_tag_name 		= ''; 					// regex syntax: this is just generic as a sample, will be rewritten on constructor
	//--


	//=========================================================================
	public function __construct($y_html='', $y_signature=true, $y_allow_dom_processing=true, $y_log_dom_warn_err=false) {
		//--
		$this->html = (string) $y_html;
		$this->signature = (bool) $y_signature;
		$this->dom_processing = (bool) $y_allow_dom_processing;
		$this->dom_log_errors = (bool) $y_log_dom_warn_err;
		//--
		$this->expr_tag_name 		= SmartValidator::regex_stringvalidation_segment('tag-name');
		$this->expr_tag_start 		= SmartValidator::regex_stringvalidation_segment('tag-start');
		$this->expr_tag_end_start 	= SmartValidator::regex_stringvalidation_segment('tag-end-start');
		$this->expr_tag_simple_end 	= SmartValidator::regex_stringvalidation_segment('tag-simple-end');
		$this->expr_tag_complex_end = SmartValidator::regex_stringvalidation_segment('tag-complex-end');
		//-- {{{SYNC-HTML-TAGS-REGEX}}}
		$this->regex_tag_name 		= '/^['.$this->expr_tag_name.']+$/si';
		//--
	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	public function get_comments() {
		//--
		$this->standardize_html();
		$this->parse_comments();
		//--
		return (array) $this->comments;
		//--
	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	public function get_all_tags() {
		//--
		$this->standardize_html();
		$this->parse_elements();
		//--
		return (array) $this->elements;
		//--
	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	// use no SmartUnicode, tag names are always non-unicode
	public function get_tags($tag) {
		//--
		$this->standardize_html();
		$this->parse_elements();
		//--
		if((string)trim((string)$tag) == '') {
			return array();
		} //end if
		//--
		$tag = (string) strtolower('<'.$tag);
		$len = (int) (strlen((string)$tag) + 1); // will add ' ' or \t or or / '>' at the end for testing
		$attrib_arr = array();
		if(is_array($this->elements)) {
			//while(list($key, $code) = @each($this->elements)) { // Fix: this is deprecated as of PHP 7.2
			foreach($this->elements as $key => $code) {
				if((strpos($code, '<') !== false) OR (strpos($code, '>') !== false)) { // if valid tag
					$code = (string) trim((string)str_replace(array("\t", "\n", "\r"), array(' ', ' ', ' '), (string)$code)); // make tabs and new lines as simple space
					$tmp_test = (string) strtolower((string)substr((string)$code, 0, $len));
					if(((string)$tmp_test == (string)$tag.' ') OR ((string)$tmp_test == (string)$tag.'/') OR ((string)$tmp_test == (string)$tag.'>')) {
						$attrib_arr[] = (array) $this->get_attributes($code);
					} //end if
				} //end if
			} //end while
		} //end if
		//--
		return (array) $attrib_arr;
		//--
	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	public function get_std_html() {

		//--
		$this->standardize_html();
		//--

		//--
		return (string) $this->html;
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	// TODO: add allowed tags attributes
	public function get_clean_html($y_comments=true, $y_extra_tags_remove=array(), $y_extra_tags_clean=array(), $y_allowed_tags=array()) {

		//--
		if(!SmartValidator::validate_html_or_xml_code((string)$this->html)) {
			return (string) $this->html;
		} //end if
		//--

		//--
		if(!is_array($y_extra_tags_remove)) {
			$y_extra_tags_remove = array();
		} //end if
		//--
		if(!is_array($y_extra_tags_clean)) {
			$y_extra_tags_clean = array();
		} //end if
		//--
		if(!is_array($y_allowed_tags)) {
			$y_allowed_tags = array();
		} //end if
		//--

		//--
		$this->clean_html((bool)$y_comments, (array)$y_extra_tags_remove, (array)$y_extra_tags_clean, (array)$y_allowed_tags);
		//--

		//--
		//return (string) (print_r($this->elements,1));
		return (string) $this->html;
		//--

	} //END FUNCTION
	//=========================================================================


	## PRIVATES


	//=========================================================================
	private function standardize_html() {

		//-- STANDARDIZE THE HTML CODE
		// * protect against client-side scripting and html denied tags ::  the < ? ? > or < % % > tag(s) will be detected and if present, will be replaced with dummy tags to prevent code injection
		// * remove all weird / unsafe characters (ex: non-utf8)
		// * replace multiple spaces with just one space
		//--

		//--
		if($this->is_std != false) {
			return; // avoid to re-parse
		} //end if
		//--
		$this->is_std = true;
		//--

		//-- remove all non utf8 characters
		$this->html = (string) preg_replace((string)Smart::lower_unsafe_characters(), '', (string)$this->html);
		//-- standardize new lines, tabs and line ends
		$this->html = (string) str_replace(array("\0", "\r\n", "\r", ' />', '/>'), array('', "\n", "\n", '>', '>'), (string)$this->html);
		//-- protect against server-side tags
		$this->html = (string) str_replace(array('<'.'?', '?'.'>', '<'.'%', '%'.'>'), array('<tag-question:start', 'tag-question:end>', '<tag-percent:start', 'tag-percent:end>'), (string)$this->html);
		//--

		//-- standardize spaces and new lines
		$arr_spaces_cleanup = array(
			//shorten multiple tabs and spaces
			'/([\t ])+/si' => ' ',
			//remove leading and trailing spaces and tabs
			'/^([\t ])+/mi' => '',
			'/([\t ])+$/mi' => '',
			//remove empty lines (sequence of line-end and white-space characters)
			'/[\r\n]+([\t ]?[\r\n]+)+/si' => "\n"
		);
		//--
		$this->html = (string) preg_replace((array)array_keys((array)$arr_spaces_cleanup), (array)array_values((array)$arr_spaces_cleanup), (string)$this->html);
		$this->html = (string) SmartUnicode::fix_charset($this->html);
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function regex_tag($tag) {

		//--
		$tag = (string) trim((string)$tag);
		//--

		//-- {{{SYNC-HTML-TAGS-REGEX}}}
		return array(
			'delimiter' => '#', // these regex must be used with # delimiter
			'tag-start' => $this->expr_tag_start.preg_quote((string)$tag, '#').$this->expr_tag_simple_end.'|'.$this->expr_tag_start.preg_quote((string)$tag, '#').$this->expr_tag_complex_end,
			'tag-end' 	=> $this->expr_tag_end_start.preg_quote((string)$tag, '#').$this->expr_tag_simple_end.'|'.$this->expr_tag_end_start.preg_quote((string)$tag, '#').$this->expr_tag_complex_end
		);
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function clean_html($y_comments, $y_extra_tags_remove=array(), $y_extra_tags_clean=array(), $y_allowed_tags=array()) {

		//-- CLEANUP DISSALOWED AND FIX INVALID HTML TAGS
		// * it will use code standardize before to fix active PHP tags and weird characters
		// * will convert all UTF-8 characters to the coresponding HTML-ENTITIES
		// * will remove all tags that are unsafe like <script> or <head> and many other dissalowed unsafe tags
		// * if allowed tags are specified they will take precedence and will be filtered via strip_tags by allowing only these tags, at the end of cleanup to be safer !
		// * if DomDocument is detected and is allowed to be used by current settings will be used finally to do (post-processing) extra cleanup and fixes
		//--

		//--
		if($this->is_clean != false) {
			return; // avoid to re-parse
		} //end if
		//--
		$this->is_clean = true;
		//--

		//--
		$this->standardize_html(); // first, standardize the HTML Code
		//--

		//--
		$arr_tags_0x_list_comments = array( // {{{SYNC-COMMENTS-REGEX}}}
			'#\<\s*?\!\-?\-?(.*?)\-?\-?\>#si' // comments (incl. invalid comments ...)
		);
		//--
		$arr_tags_2x_list_bad = array( // remove tags and their content
			'head',
			'style',
			'script',
			'noscript',
			'frameset',
			'frame',
			'iframe',
			'canvas',
			'audio',
			'video',
			'applet',
			'param',
			'object',
			'form',
			'xml',
			'xmp',
			'o:p'
		);
		if(Smart::array_size($y_extra_tags_remove) > 0) { // add extra entries such as: img, p, div, ...
			for($i=0; $i<count($y_extra_tags_remove); $i++) {
				if(preg_match((string)$this->regex_tag_name, (string)$y_extra_tags_remove[$i])) {
					if(!in_array((string)$y_extra_tags_remove[$i], $arr_tags_2x_list_bad)) {
						$arr_tags_2x_list_bad[] = (string) $y_extra_tags_remove[$i];
					} //end if
				} //end if
			} //end for
		} //end if
		$arr_tags_2x_repl_bad = array();
		$arr_tags_2x_repl_good = array();
		if($y_comments === false) {
			for($i=0; $i<count($arr_tags_0x_list_comments); $i++) {
				$arr_tags_2x_repl_bad[] = (string) $arr_tags_0x_list_comments[$i];
				$arr_tags_2x_repl_good[] = (string) '<!-- # -->'; // comment ; must be non-empty to avoid break compatibility with DOMDocument !!
			} //end for
		} //end if
		for($i=0; $i<count($arr_tags_2x_list_bad); $i++) {
			$tmp_regex_tag = (array) $this->regex_tag((string)$arr_tags_2x_list_bad[$i]);
			// currently if nested tags some content between those tags may remain not removed ... but that is ok as long as the tag is replaced ; possible fix: match with siU instead of si but will go ungreedy and will match all content until very last end tag ... which may remove too many content
			$arr_tags_2x_repl_bad[] = (string) $tmp_regex_tag['delimiter'].'('.$tmp_regex_tag['tag-start'].')'.'.*?'.'('.$tmp_regex_tag['tag-end'].')'.$tmp_regex_tag['delimiter'].'si'; // fix: paranthesis are required to correct match in this case (balanced regex)
			$arr_tags_2x_repl_good[] = (string) '<!-- '.Smart::escape_html((string)$arr_tags_2x_list_bad[$i]).'/ -->';
		} //end if
		//--

		//--
		$arr_tags_1x_list_bad = (array) array_merge((array)$arr_tags_2x_list_bad, array( // remove these tags but keep their content
			'!doctype',
			'html',
			'body',
			'base',
			'meta',
			'link',
			'track',
			'source',
			'plaintext',
			'marquee'
		));
		if(Smart::array_size($y_extra_tags_clean) > 0) { // add extra entries such as: img, p, div, ...
			for($i=0; $i<count($y_extra_tags_clean); $i++) {
				if(preg_match((string)$this->regex_tag_name, (string)$y_extra_tags_clean[$i])) {
					if(!in_array((string)$y_extra_tags_clean[$i], $arr_tags_1x_list_bad)) {
						$arr_tags_1x_list_bad[] = (string) $y_extra_tags_clean[$i];
					} //end if
				} //end if
			} //end for
		} //end if
		$arr_tags_1x_repl_bad = array(
		);
		$arr_tags_1x_repl_good = array(
		);
		for($i=0; $i<count($arr_tags_1x_list_bad); $i++) {
			$tmp_regex_tag = (array) $this->regex_tag((string)$arr_tags_1x_list_bad[$i]);
			$arr_tags_1x_repl_bad[] = $tmp_regex_tag['delimiter'].$tmp_regex_tag['tag-start'].$tmp_regex_tag['delimiter'].'si';
			$arr_tags_1x_repl_bad[] = $tmp_regex_tag['delimiter'].$tmp_regex_tag['tag-end'].$tmp_regex_tag['delimiter'].'si';
			$arr_tags_1x_repl_good[] = '<!-- '.Smart::escape_html((string)$arr_tags_1x_list_bad[$i]).' -->';
			$arr_tags_1x_repl_good[] = '<!-- /'.Smart::escape_html((string)$arr_tags_1x_list_bad[$i]).' -->';
		} //end if
		//--

		//--
		$arr_all_repl_bad  = (array) array_merge((array)$arr_tags_2x_repl_bad,  (array)$arr_tags_1x_repl_bad);
		$arr_all_repl_good = (array) array_merge((array)$arr_tags_2x_repl_good, (array)$arr_tags_1x_repl_good);
		//--
		//print_r($arr_tags_2x_repl_bad);
		//print_r($arr_tags_2x_repl_good);
		//print_r($arr_tags_1x_repl_bad);
		//print_r($arr_tags_1x_repl_good);
		//print_r($arr_all_repl_bad);
		//print_r($arr_all_repl_good);
		//die('');
		//--

		//--
		$this->html = (string) preg_replace((array)$arr_all_repl_bad, (array)$arr_all_repl_good, (string)$this->html);
		//--

		//--
		$this->parse_elements();
		//--

		//--
		for($i=0; $i<Smart::array_size($this->elements); $i++) {
			//--
			$code = (string) $this->elements[$i];
			if((substr($code, 0, 4) != '<!--') AND ((strpos($code, '<') !== false) OR (strpos($code, '>') !== false))) { // if valid tag and not a comment
				//--
				$tag_have_endline = false;
				if(substr($code, -1, 1) === "\n") {
					$tag_have_endline = true;
				} //end if
				//--
				$code = (string) trim((string)str_replace(["\t", "\n", "\r"], ' ', (string)$code)); // make tabs and new lines as simple space
				$tmp_parse_attr = (array) $this->get_attributes($code);
				//--
				if((strpos($code, ' ') !== false) AND (Smart::array_size($tmp_parse_attr) > 0)) { // tag have attributes
					//--
					$tmp_arr = (array) explode(' ', $code); // get tag parts
					$this->elements[$i] = (string) strtolower((string)(isset($tmp_arr[0]) ? $tmp_arr[0] : '')); // recompose the tags
					foreach($tmp_parse_attr as $key => $val) {
						$tmp_is_valid_attr = true;
						if(!preg_match((string)$this->regex_tag_name, (string)$key)) {
							$tmp_is_valid_attr = false; // remove invalid attributes
						} elseif(substr((string)trim((string)$key), 0, 2) == 'on') {
							$tmp_is_valid_attr = false; // remove attributes starting with 'on' (all JS Events)
						} elseif(substr((string)trim((string)$key), 0, 10) == 'formaction') {
							$tmp_is_valid_attr = false; // remove attributes starting with 'formaction'
						} elseif(substr((string)trim((string)$val), 0, 2) == '&{') {
							$tmp_is_valid_attr = false; // remove attributes of which value are old Netscape JS ; Ex: border="&{getBorderWidth( )};"
						} elseif(substr((string)trim((string)$val), 0, 11) == 'javascript:') {
							$tmp_is_valid_attr = false; // remove attributes that contain javascript:
						} elseif((stripos((string)trim((string)$val), 'java') !== false) AND (stripos((string)trim((string)$val), 'script') !== false) AND (strpos((string)trim((string)$val), ':') !== false)) {
							$tmp_is_valid_attr = false; // remove attributes that contain java + script + :
						} //end for
						if($tmp_is_valid_attr) {
							$this->elements[$i] .= ' '.strtolower($key).'='.'"'.str_replace(array('"', '<', '>'), array('&quot;', '&lt;', '&gt;'), (string)$val).'"';
						} //end if
					} //end foreach
					$this->elements[$i] .= '>';
					if($tag_have_endline) {
						$this->elements[$i] .= "\n";
					} //end if
					$tmp_arr = array();
					//--
				} elseif(preg_match('/^[<'.$this->expr_tag_name.'\/ >]+$/si', (string)$code)) { // simple tags (includding tags like <br />) ; needs extra / and space
					//--
					$this->elements[$i] = strtolower((string)$code);
					if($tag_have_endline) {
						$this->elements[$i] .= "\n";
					} //end if
					//--
				} else {
					//--
					$this->elements[$i] = ''; // invalid tags, clear
					//--
				} //end if
			} //end if
			//--
		} //end for
		//--

		//-- {{{SYNC-HTMP-PARSER-RECOMPOSE}}}
		$this->html = (string) SmartUnicode::convert_charset((string)implode('', (array)$this->elements), 'UTF-8', 'HTML-ENTITIES');
		//--

		//--
		if(Smart::array_size($y_allowed_tags) > 0) {
			$arr_striptags_allow = array();
			for($i=0; $i<count($y_allowed_tags); $i++) {
				if(preg_match((string)$this->regex_tag_name, (string)$y_allowed_tags[$i])) {
					if(!in_array((string)$y_allowed_tags[$i], (array)$arr_striptags_allow)) { // despite if a tag is specified as unallowed, if allowed here will take precedence
						$arr_striptags_allow[] = '<'.$y_allowed_tags[$i].'>';
					} //end if
				} //end if
			} //end for
			if(Smart::array_size($arr_striptags_allow) > 0) {
				//print_r($arr_striptags_allow);
				$str_striptags_allow = (string) implode(',', (array)$arr_striptags_allow);
				//echo $str_striptags_allow;
				$this->html = (string) strip_tags((string)$this->html, (string)$str_striptags_allow);
			} //end if
		} //end if
		//--

		//--
		$this->html = (string) trim((string)$this->html);
		//--

		//--
		$use_dom = false;
		//--
		if(($this->dom_processing !== false) AND (class_exists('DOMDocument'))) {
			//--
			$use_dom = true;
			//--
			if((string)$this->html != '') { // IMPORTANT: DOMDocument loadHTML() will throw error if $this->html is empty, so test this case ...
				//--
				@libxml_use_internal_errors(true);
				@libxml_clear_errors();
				//--
				$dom = new DOMDocument(5, (string)SMART_FRAMEWORK_CHARSET);
				//--
				$dom->encoding = (string) SMART_FRAMEWORK_CHARSET;
				$dom->strictErrorChecking = false; 	// do not throw errors
				$dom->preserveWhiteSpace = false; 	// set this to false in order to real format HTML ...
				$dom->formatOutput = true; 			// try to format pretty-print the code (will work just partial as the preserve white space is true ...)
				$dom->resolveExternals = false; 	// disable load external entities from a doctype declaration
				$dom->validateOnParse = false; 		// this must be explicit disabled as if set to true it may try to download the DTD and after to validate (insecure ...)
				//--
				@$dom->loadHTML(
					(string) $this->html,
					LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_HTML_NODEFDTD // {{{SYNC-LIBXML-OPTIONS}}} ; important !!! do not use the buggy flag LIBXML_HTML_NOIMPLIED as it will mess up the tags, is not stable enough inside LibXML
				);
				$this->html = (string) @$dom->saveHTML(); // get back from DOM ; using @$dom->saveHTML(@$dom->getElementsByTagName('body')->item(0)); is buggy as will not encode entities as #xxxx;
				//print_r($this->html);
				$dom = null; // free mem
				$this->html = (string) trim((string)preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', (string)$this->html)); // cleanup fix: remove the doctype, html / head / body tags
				//--
				if((SmartFrameworkRuntime::ifDebug()) OR ($this->dom_log_errors === true)) { // log errors if set :: OR ((string)$this->html == '')
					$errors = (array) @libxml_get_errors();
					if(Smart::array_size($errors) > 0) {
						$notice_log = '';
						foreach($errors as $z => $error) {
							if(is_object($error)) {
								$notice_log .= 'FORMAT-ERROR: ['.$error->code.'] / Level: '.$error->level.' / Line: '.$error->line.' / Column: '.$error->column.' / Message: '.trim((string)$error->message)."\n";
							} //end if
						} //end foreach
						if((string)$notice_log != '') {
							Smart::log_notice('SmartHtmlParser NOTICE [DOMDocument]:'."\n".$notice_log."\n".'#END'."\n");
						} //end if
						if(SmartFrameworkRuntime::ifDebug()) {
							Smart::log_notice('SmartHtmlParser / Debug HTML-String:'."\n".$this->html."\n".'#END');
						} //end if
					} //end if
				} //end if
				//--
				@libxml_clear_errors();
				@libxml_use_internal_errors(false);
				//--
			} //end if
			//--
		} //end if
		//--

		//-- this must be done after DOMDocument processing due to the too many new empty lines between tags that breaks html code in DOMDocument (don't now why ...)
		if($y_comments === false) {
			$this->html = (string) preg_replace(
				'#\<\s*?\!\-\-(.*?)\-\-\>#si', // {{{SYNC-COMMENTS-REGEX}}} ; just VALID comments
				'',
				(string) $this->html
			);
		} //end if
		//--

		//--
		if($this->signature) {
			if($use_dom) {
				$start_signature = '<!-- Smart/HTML.Cleaner [@] -->';
				$end_signature = '<!-- [/@] Smart/HTML.Cleaner -->';
			} else {
				$start_signature = '<!-- Smart/HTML.Cleaner [#] -->';
				$end_signature = '<!-- [/#] Smart/HTML.Cleaner -->';
			} //end if else
		} else {
			$start_signature = '';
			$end_signature = '';
		} //end if else
		//--

		//--
		$this->html = (string) $start_signature.$this->html.$end_signature;
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function parse_comments() {

		//--
		if($this->cm_parsed != false) {
			return; // avoid to re-parse
		} //end if
		$this->cm_parsed = true;
		//--

		//--
		$this->comments = array(); // init
		//--
		if((string)$this->html == '') {
			return;
		} //end if
		//--
		$rcomments = array();
		if(preg_match_all('#\<\s*?\!\-\-(.*?)\-\-\>#si', (string)$this->html, $rcomments)) { // {{{SYNC-COMMENTS-REGEX}}} ; this will get just VALID comments
			if(is_array($rcomments)) {
				$this->comments['comment-keys'] = (array) $rcomments[1];
				$this->comments['comment-tags'] = (array) $rcomments[0];
			} //end if
		} //end if
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	// use no SmartUnicode, tag names are always non-unicode
	private function parse_elements() {

		//--
		if($this->el_parsed != false) {
			return; // avoid to re-parse
		} //end if
		$this->el_parsed = true;
		//--

		//--
		$this->elements = array(); // init
		//--
		if((string)$this->html == '') {
			return;
		} //end if
		//--

		//--
		$ignorechar = false;
		$intag = false;
		$tagdepth = 0;
		$line = '';
		$text = '';
		$tag = '';
		//--
		$raw = (array) explode("\n", (string)$this->html);
		//--
		//while(list($key, $line) = @each($raw)) { // Fix: this is deprecated as of PHP 7.2
		foreach($raw as $key => $line) {
			//--
			$line = trim($line);
			if((string)$line == '') {
				continue;
			} //end if
			$line .= "\n"; // fix: if tag is on multiple lines
			//--
			for($charsindex=0; $charsindex<strlen($line); $charsindex++) { // Fix: must be strlen() not SmartUnicode as it will break the parsing (Fix: 160203)
				if($ignorechar == true) {
					$ignorechar = false;
				} //end if
				if(((string)$line[$charsindex] == '<') AND (!$intag)) {
					if((string)$text != '') {
						// text found
						$this->elements[] = $text;
						$text = '';
					} //end if
					$intag = true;
				} else {
					if(((string)$line[$charsindex] == '>') AND ($intag)) {
						$tag .= '>';
						// tag found
						$this->elements[] = $tag;
						$ignorechar = true;
						$intag = false;
						$tag = '';
					} //end if
				} //end if else
				if((!$ignorechar) AND (!$intag)) {
					$text .= $line[$charsindex];
				} else {
					if((!$ignorechar) AND ($intag)) {
						$tag .= $line[$charsindex];
					} //end if
				} //end if else
			} //end for
		} //end while
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function get_attributes($html) {

		//--
		$attr_with_dbl_quote = '((['.$this->expr_tag_name.']+)\s*=\s*"([^"]*)")*';
		$attr_with_quote = '((['.$this->expr_tag_name.']+)\s*=\s*\'([^\']*)\')*';
		$attr_without_quote = '((['.$this->expr_tag_name.']+)\s*=([^\s>\/]*))*';
		//--

		//--
		$attr = array();
		preg_match_all('/'.$attr_with_dbl_quote.'|'.$attr_with_quote.'|'.$attr_without_quote.'/si', (string)$html, $attr);
		//--
		$res = array();
		//--
		if(is_array($attr)) {
			foreach($attr as $count => $attr_arrx) {
				if(is_array($attr_arrx)) {
					foreach($attr_arrx as $i => $a) {
						if(((string)$a != '') AND ($count == 2)) {
							$res[$a] = (string) $attr[3][$i];
						} //end if
						if(((string)$a != '') AND ($count == 5)) {
							$res[$a] = (string) $attr[6][$i];
						} //end if
						if(((string)$a != '') AND ($count == 8)) {
							$res[$a] = (string) $attr[9][$i];
						} //end if
					} //end foreach
				} //end if
			} //end foreach
		} //end if
		//--

		//--
		return (array) $res;
		//--

	} //END FUNCTION
	//=========================================================================


} //END CLASS


//=========================================================================
/*******************
// SAMPLE USAGE:
$html = <<<HTML_CODE
<a href="#anchor"><img src="some/image.jpg" width="32" height="64"></a>
<a href="#anchor"><img src="some/image2.jpg" width="33" height="65"></a>
HTML_CODE;
$obj = new SmartHtmlParser($html);
print_r($obj->get_tags("img"));
echo $obj->get_clean_html();
********************/
//=========================================================================


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
