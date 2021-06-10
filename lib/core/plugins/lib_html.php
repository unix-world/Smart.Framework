<?php
// [LIB - Smart.Framework / Plugins / HTML Parser]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
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
 * @version 	v.20210609
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
	private $dom_processing 		= true;					// if set to false will dissalow post-processing of html cleanup with Tidy (if available) or DomDocument (if available and Tidy not available) ; if set to true (default) if tidy or DomDocument class is available (by checking in this order) will use any of it it for post-processing of html cleanup
	private $dom_log_errors 		= false; 				// if set to true will log Tidy <- LibXML -> DomDocument errors and warnings
	private $dom_prefer_domdoc 		= false; 				// if set to TRUE will prefer DomDocument instead of tidy for dom processing ; if set to 2 will prefer tidy ; IMPORTANT: requires tidy v5 or later ... do not use tidyp which miss some important options like skip dropping empty tags which may contain iconic fonts
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
	public function __construct(?string $y_html='', bool $y_signature=true, $y_allow_dom_processing=true, bool $y_log_dom_warn_err=false) {
		//--
		if(!Smart::is_nscalar($y_allow_dom_processing)) {
			$y_allow_dom_processing = true;
		} //end if
		//--
		$this->html = (string) $y_html;
		//--
		$this->signature 			= (bool) $y_signature;
		$this->dom_processing 		= (bool) $y_allow_dom_processing;
		$this->dom_log_errors 		= (bool) $y_log_dom_warn_err;
		//--
		if((bool)$y_allow_dom_processing === false) { // if set to zero or FALSE
			$this->dom_prefer_domdoc = 0;
		} else { // can be: true, 1, 2
			$this->dom_prefer_domdoc = (int) $y_allow_dom_processing; // [ (int)TRUE = 1 ] ; by default prefer dom document (default, it is built-in)
			if(defined('SMART_HTML_CLEANER_USE_TIDY')) { // IMPORTANT: the constant SMART_HTML_CLEANER_USE_TIDY should not be defined as general, to be able to define per controller ...
				if(SMART_HTML_CLEANER_USE_TIDY === true) {
					if($y_allow_dom_processing === true) { // rewrite only if set as default to true, not explicit to 1 or 2
						$this->dom_prefer_domdoc = 2; // prefer tidy
					} //end if
				} elseif(SMART_HTML_CLEANER_USE_TIDY === false) {
					$this->dom_prefer_domdoc = 1; // prefer dom document, tidy is disabled
				} elseif((string)SMART_HTML_CLEANER_USE_TIDY == 'tidy') {
					$this->dom_prefer_domdoc = 2; // mandatory tidy
				} //end if else
			} //end if
		} //end if else
		if((int)$this->dom_prefer_domdoc < 0) {
			$this->dom_prefer_domdoc = 0;
		} elseif((int)$this->dom_prefer_domdoc > 2) {
			$this->dom_prefer_domdoc = 1;
		} //end if
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
	public function get_tags(string $tag) {
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
			foreach($this->elements as $key => $code) {
				if((strpos($code, '<') !== false) OR (strpos($code, '>') !== false)) { // if valid tag
					$code = (string) trim((string)str_replace(array("\t", "\n", "\r"), array(' ', ' ', ' '), (string)$code)); // make tabs and new lines as simple space
					$tmp_test = (string) strtolower((string)substr((string)$code, 0, $len));
					if(((string)$tmp_test == (string)$tag.' ') OR ((string)$tmp_test == (string)$tag.'/') OR ((string)$tmp_test == (string)$tag.'>')) {
						$attrib_arr[] = (array) $this->get_attributes((string)$code);
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
	public function get_clean_html(bool $y_comments=true, ?array $y_extra_tags_remove=array(), ?array $y_extra_tags_clean=array(), ?array $y_allowed_tags=array(), bool $y_allow_media_tags=false, bool $y_allow_iframes=false) {

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
		$this->clean_html((bool)$y_comments, (array)$y_extra_tags_remove, (array)$y_extra_tags_clean, (array)$y_allowed_tags, (bool)$y_allow_media_tags, (bool)$y_allow_iframes);
		//--

		//--
		//print_r($this->elements,1); die();
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

		//--
		$this->html = (string) SmartUnicode::fix_charset($this->html);
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function regex_tag(string $tag) {

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
	private function remove_comments(string $html) {

		//--
		if((string)$html == '') {
			return '';
		} //end if
		//--

		//--
		return (string) preg_replace(
			'#\<\s*?\!\-\-(.*?)\-\-\>#si', // {{{SYNC-COMMENTS-REGEX}}} ; just VALID comments
			'',
			(string) $html
		);
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function extract_body_contents_from_html(?string $html) {

		//--
		// THIS IS A FIX for Tidy and DomDocument as some version of these parsers may fail to return just the body ...
		//--

		//--
		// cleanup fix: remove the !doctype, html / body tags, but not their contents ; remove the head tag includdings it's contents
		//--
		$html = (string) $html;
		//--
		if((strpos($html, '<') !== false) OR (strpos($html, '>') !== false)) {
			$arr_tags_list = array( // remove these tags but keep their content
				'!doctype' 	=> false, // does not have a tag end
				'html' 		=> true,
				'body' 		=> true,
			); // for the head tag, it must be removed completely
			$arr_tags_repl = array();
			foreach($arr_tags_list as $key => $val) {
				$tmp_regex_tag = (array) $this->regex_tag((string)$key);
				$arr_tags_repl[] = $tmp_regex_tag['delimiter'].$tmp_regex_tag['tag-start'].$tmp_regex_tag['delimiter'].'si';
				if($val === true) {
					$arr_tags_repl[] = $tmp_regex_tag['delimiter'].$tmp_regex_tag['tag-end'].$tmp_regex_tag['delimiter'].'si';
				} //end if
			} //end if
			//print_r($arr_tags_repl); die();
			$html = (string) preg_replace((array)$arr_tags_repl, '', (string)$html); // remove !doctype, html and body but preserve their contents
			//-- remove head tag and also it's contents
			$tmp_regex_head_tag = (array) $this->regex_tag('head');
			$tmp_regex_expt_head_tag = (string) $tmp_regex_head_tag['delimiter'].'('.$tmp_regex_head_tag['tag-start'].')'.'.*?'.'('.$tmp_regex_head_tag['tag-end'].')'.$tmp_regex_head_tag['delimiter'].'si';
			$html = (string) preg_replace((string)$tmp_regex_expt_head_tag, '', (string)$html); // remove completely head and it's contents
		} //end if
		//--

		//--
		$html = (string) trim((string)$html); // cleanup fix: trim
		//--

		//--
		return (string) $html;
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function compose_html_document(?string $body) {

		//--
		// THIS IS A FIX for Tidy and DomDocument as some version of these parsers fail if only body provided ...
		// Trick: the meta charset have not be supplied because if set to UTF-8 the DomDocument will decode all possible entities, includding &Prime; thus the fixback to &quot; is no more available to force &quot; instead of " when using DomDocument
		//--
		return (string) '<!DOCTYPE html>'."\n".'<html>'."\n".'<head>'."\n".'<title>HTML Document</title>'."\n".'</head>'."\n".'<body>'."\n".$body."\n".'</body>'."\n".'</html>'."\n";
		//--

	} //END FUNCTION
	//=========================================================================


	//=========================================================================
	private function clean_html(string $y_comments, array $y_extra_tags_remove, array $y_extra_tags_clean, array $y_allowed_tags, bool $y_allow_media_tags, bool $y_allow_iframes) {

		//-- CLEANUP DISSALOWED AND FIX INVALID HTML TAGS
		// * it will use code standardize before to fix active PHP tags and weird characters
		// * will convert all UTF-8 characters to the coresponding HTML-ENTITIES
		// * will remove all tags that are unsafe like <script> or <head> and many other dissalowed unsafe tags
		// * if allowed tags are specified they will take precedence and will be filtered via strip_tags by allowing only these tags, at the end of cleanup to be safer !
		// * if tidy or DomDocument is detected and is allowed to be used by current settings will be used finally to do (post-processing) extra cleanup and fixes
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
			'form',
			'applet',
			'param',
			'object',
			'xmp',
			'xml',
			'frameset',
			'frame',
		);
		if(!$y_allow_iframes) {
			$arr_tags_2x_list_bad[] = 'iframe';
		} //end if
		if(!$y_allow_media_tags) {
			$arr_tags_2x_list_bad[] = 'audio';
			$arr_tags_2x_list_bad[] = 'video';
			$arr_tags_2x_list_bad[] = 'canvas';
		} //end if
		$arr_tags_2x_list_bad[] = 'o:p'; // must be at the end, cleanup word formatting
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
				$arr_tags_2x_repl_good[] = (string) '<!-- # -->'; // comment ; must be non-empty to avoid break compatibility with DOMDocument in the case it is used !!
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
			'plaintext',
			'marquee'
		));
		if(Smart::array_size($y_extra_tags_clean) > 0) {
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

		//-- SAFETY CHECK [OK]: the 2nd param: $arr_all_repl_good must not contain any regex back reference such as $1 or \\1 !!!
		//Smart::log_notice(print_r($arr_all_repl_good,1));
		$this->html = (string) preg_replace((array)$arr_all_repl_bad, (array)$arr_all_repl_good, (string)$this->html); // safe
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
				$tmp_parse_attr = (array) $this->get_attributes((string)$code);
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
					//	} elseif((stripos((string)trim((string)$val), 'java') !== false) AND (stripos((string)trim((string)$val), 'script') !== false) AND (strpos((string)trim((string)$val), ':') !== false)) { // this is not safe and may remove unwanted attributes ... too restrictive, disable it !
					//		$tmp_is_valid_attr = false; // remove attributes that contain java + script + :
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

		//-- {{{SYNC-HTML-PARSER-RECOMPOSE}}}
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
		$use_dom = null;
		//--
		if($this->dom_processing !== false) {
			//--
			if(defined('SMART_HTML_CLEANER_USE_TIDY') AND ((string)SMART_HTML_CLEANER_USE_TIDY == 'tidy')) {
				if(((int)$this->dom_prefer_domdoc !== 2) OR (!class_exists('tidy'))) {
					$err_text = 'Smart/HTML.Cleaner ERROR: Tidy is missing but the options require explicit use of Tidy ...'."\n".'The HTML Output was aborted due to security restrictions, HTML not validated by Tidy !';
					Smart::log_warning($err_text."\n".'The original HTML is here:'."\n".'`'.$this->html.'`');
					$this->html = '<div class="operation_error">'.Smart::nl_2_br((string)Smart::escape_html((string)$err_text)).'</div>';
					return;
				} //end if
			} //end if
			//--
			if(((int)$this->dom_prefer_domdoc === 2) AND (class_exists('tidy'))) { // IMPORTANT: do not initialize tidy if html is empty, it is very expensive
				//--
				$use_dom = 'T';
				//--
				if((string)$this->html != '') {
					//--
					$max_err_level = 0; // 0: no errors ; 1: minimal ; 2: more
					$enable_warns = false; // if set to true will output the HTML validation report to logs
					if(SmartFrameworkRegistry::ifDebug()) {
						$max_err_level = 2;
						$enable_warns = true;
					} elseif($this->dom_log_errors === true) {
						$max_err_level = 1;
					} //end if
					//--
					$etidy = (string) strtolower((string)SMART_FRAMEWORK_SQL_CHARSET); // tidy uses utf8 instead of UTF-8
					//--
					$ctidy = [ // config options for tidy v5 or later
						'quiet' => true,
						'show-errors' => (int) $max_err_level, // err level 0..6
						'show-warnings' => (bool) $enable_warns,
						'char-encoding' => (string) $etidy,
						'input-encoding' => (string) $etidy,
						'output-encoding' => (string) $etidy,
						'output-bom' => false,
						'newline' => 'LF',
						'doctype' => 'omit',
						'output-xml' => false,
						'input-xml' => false,
						'output-xhtml' => false,
						'output-html' => true,
						'wrap' => 0,
						'wrap-attributes' => false,
						'wrap-sections' => false,
						'indent' => false, // must be set to FALSE to save space and to keep this compatible with the code generated by DomDocument below
						'indent-attributes' => false,
						'tab-size' => 4,
						'ncr' => true,
						'preserve-entities' => true,
						'numeric-entities' => true,
						'uppercase-tags' => false,
						'uppercase-attributes' => false,
						'quote-nbsp' => true,
						'quote-ampersand' => true,
						'quote-marks' => true,
						'fix-bad-comments' => true,
						'fix-uri' => true,
						'merge-divs' => false,
						'merge-spans' => false,
						'tidy-mark' => false,
						'hide-comments' => ($y_comments === false) ? true : false,
						'omit-optional-tags' => false, // this is the real alias for the deprecated: hide-endtags
						'drop-empty-elements' => false, // N/A in tidyp and this is essential ; without this disabled will drop off empty tags like iconic fonts: <i class="sfi sfi-embed"></i>
						'drop-empty-paras' => false,
					//	'drop-font-tags' => false, // deprecated, only available in tidyp
						'drop-proprietary-attributes' => false,
						'clean' => false, // do not enable this, will replace the inline styles
						'markup' => false, // do not generate a pretty printed version of the markup
						'vertical-space' => false,
					//	'show-body-only' => true, // this option is not used, will extract body later
						'new-blocklevel-tags' => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section summary template track video source picture',
						'new-empty-tags' => 'command embed keygen source track wbr',
						'new-inline-tags' => 'span audio command datalist embed keygen mark meter output progress time video source picture wbr', // deprecated in HTML 5.2: menuitem
						'new-pre-tags' => 'pre code'
					];
					$tidy = new tidy();
					//--
					$tidy->parseString((string)$this->compose_html_document((string)$this->html), (array)$ctidy, (string)$etidy); // fix: in some versions of tidy the first comment dissapear if not enclosed in a body container, so need this function: compose_html_document
					$testClean = $tidy->cleanRepair();
					//--
					if((SmartFrameworkRegistry::ifDebug()) OR ($this->dom_log_errors === true)) { // log errors if set :: OR ((string)$this->html == '')
						$notice_log = $tidy->errorBuffer;
						if((string)$notice_log != '') {
							Smart::log_notice(__CLASS__.' # Tidy [Result='.$testClean.'] Log:'."\n".$notice_log."\n".'#END'."\n");
						} //end if
						if(SmartFrameworkRegistry::ifDebug()) {
							Smart::log_notice(__CLASS__.' # Debug Tidy [Result='.$testClean.'] Clean HTML-String:'."\n".$this->html."\n".'#END');
						} //end if
					} //end if
					//--
				//	$this->html = (string) $tidy; // $tidy tostring() returns only the inside of the body, but have some bugs if trailing comments ... if show-body-only is set to TRUE ; $tidy->html() returns the full document ; if show-body-only option miss from some builds of Tidy may return the full HTML document instead of body only so get rid of this option and apply postfixes to get just the body
				//	$this->html = (string) $tidy->html(); // returns the full HTML documents and later in post-fixes will get just the body contents
					$this->html = (string) $tidy->body(); // returns the body contents, includding the body tags and later in post-fixes will get just the body contents
				//	print_r($this->html); die();
					//--
					$tidy = null;
					$ctidy = null;
					$etidy = null;
					//-- post fixes
					$this->html = (string) $this->extract_body_contents_from_html((string)$this->html);
				//	$this->html = (string) preg_replace('/\<\/code\>\s+\<\/pre\>/', '</code></pre>', $this->html); // fix the newline or spaces between code end tag and pre end tag ; no more required for tidy v5 !
				//	if($y_comments === false) { // on tidy5 using the tidy option: 'hide-comments', much safer ...
				//		$this->html = $this->remove_comments((string)$this->html); // remove comments after tidy cleanup which suppose the bad comments have been fixed, this is much safe
				//	} //end if
					//--
				//	print_r($this->html); die();
					//--
				} //end if
				//--
			} elseif(class_exists('DOMDocument')) { // DOMDocument will be used by default as is generally available if extension loaded if not set explicit to use tidy ; it does not such a good job as tidy ... ; for example will convert &quot; to real " ... which is not so good, but this is fixed below ...
				//--
				$use_dom = 'D';
				//--
				if((string)$this->html != '') { // IMPORTANT: do not initialize DOMDocument if html is empty, it is very expensive ; this also must be checked because the DOMDocument loadHTML() will throw error if $this->html is empty, so test this case ...
					//--
					@libxml_use_internal_errors(true);
					@libxml_clear_errors();
					//--
					$dom = new DOMDocument(5, (string)SMART_FRAMEWORK_CHARSET);
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
					$this->html = (string) str_replace('&quot;', '&Prime;', $this->html); // fix: DomDocument will decode the &quot; to ", thus substitute with &Prime; (&#8243;) which is a unicode verion of it ″ and restore back thereafter ; if there are any &Prime; already converting &Prime; to &quot; later is not a problem ...
					//--
					$testClean = @$dom->loadHTML(
						(string) $this->compose_html_document((string)$this->html), // fix: in some versions of DomDocument or LibXML if not enclosed in a body container there are some strange behaviours when getting back the HTML code, so need this function: compose_html_document
						LIBXML_ERR_WARNING | LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_HTML_NODEFDTD // {{{SYNC-LIBXML-OPTIONS}}} ; important !!! do not use the buggy flag LIBXML_HTML_NOIMPLIED as it will mess up the tags, is not stable enough inside LibXML
					);
				//	$this->html = (string) @$dom->saveHTML(@$dom->getElementsByTagName('body')->item(0)); // get back from DOM, only body ; using this will will decode all possible entities, includding &Prime; thus the fixback to &quot; is no more available to force &quot; instead of " when using DomDocument
					$this->html = (string) @$dom->saveHTML(); // get back from DOM, the full document, will extract later only the body
					//print_r($this->html); die();
					$dom = null; // free mem
					//--
					if((SmartFrameworkRegistry::ifDebug()) OR ($this->dom_log_errors === true)) { // log errors if set :: OR ((string)$this->html == '')
						$errors = (array) @libxml_get_errors();
						if(Smart::array_size($errors) > 0) {
							$notice_log = '';
							$max_err_level = 1;
							if(SmartFrameworkRegistry::ifDebug()) {
								$max_err_level = 2;
							} //end if
							foreach($errors as $z => $error) {
								if((int)$error->level <= (int)$max_err_level) {
									if(is_object($error)) {
										$notice_log .= 'FORMAT-ERROR: ['.$error->code.'] / Level: '.$error->level.' / Line: '.$error->line.' / Column: '.$error->column.' / Message: '.trim((string)$error->message)."\n";
									} //end if
								} //end if
							} //end foreach
							if((string)$notice_log != '') {
								Smart::log_notice(__CLASS__.' # DOMDocument [Result='.$testClean.'] Log:'."\n".$notice_log."\n".'#END'."\n");
							} //end if
							if(SmartFrameworkRegistry::ifDebug()) {
								Smart::log_notice(__CLASS__.' # Debug DomDocument [Result='.$testClean.'] Clean HTML-String:'."\n".$this->html."\n".'#END');
							} //end if
						} //end if
					} //end if
					//--
					@libxml_clear_errors();
					@libxml_use_internal_errors(false);
					//-- post fixes
					$this->html = (string) str_replace('&Prime;', '&quot;', (string)$this->html); // fix back &quot;
					$this->html = (string) $this->extract_body_contents_from_html((string)$this->html);
					if($y_comments === false) {
						$this->html = $this->remove_comments((string)$this->html); // remove comments, for safety this must be done after DOMDocument processing due to the too many new empty lines between tags that breaks html code in DOMDocument (don't now why ...)
					} //end if
					//--
				} //end if
				//--
			} else {
				//--
				if($y_comments === false) {
					$this->html = $this->remove_comments((string)$this->html); // remove comments, no DOM processing available
				} //end if
				//--
			} //end if else
			//--
		} else {
			//--
			if($y_comments === false) {
				$this->html = $this->remove_comments((string)$this->html); // remove comments, DOM processing not enable
			} //end if
			//--
		} //end if
		//--

		//--
		if($this->signature) {
			if($use_dom) {
				$start_signature = '<!-- Smart/HTML.Cleaner ['.Smart::escape_html($use_dom).'] -->';
				$end_signature = '<!-- [/'.Smart::escape_html($use_dom).'] Smart/HTML.Cleaner -->';
			} else {
				$start_signature = '<!-- Smart/HTML.Cleaner [S] -->';
				$end_signature = '<!-- [/S] Smart/HTML.Cleaner -->';
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
		$pcre = preg_match_all('#\<\s*?\!\-\-(.*?)\-\-\>#si', (string)$this->html, $rcomments);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return;
		} //end if
		if($pcre) { // {{{SYNC-COMMENTS-REGEX}}} ; this will get just VALID comments
			if(is_array($rcomments)) {
				$this->comments['comment-keys'] = (array) (isset($rcomments[1]) && is_array($rcomments[1])) ? $rcomments[1] : [];
				$this->comments['comment-tags'] = (array) (isset($rcomments[0]) && is_array($rcomments[0])) ? $rcomments[0] : [];
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
		foreach($raw as $key => $line) {
			//--
			/* {{{SYNC-HTML-CLEAN-UNBREAK-PRE}}}
			 * This was disabled because breaks some important things like the contents of pre / code and other pre-formats like ascii art which should be kept intact (unaltered content, especially on code snippets ...) ; if this is enabled can generate unpredictable things ...
			 * This feature has been designed just for html code prettify but it can be dropped.
			$line = trim($line);
			if((string)$line == '') {
				continue;
			} //end if
			*/
			//--
			$line .= "\n"; // fix: add back the newline of which the lines were exploded by
			//--
			for($charsindex=0; $charsindex<strlen($line); $charsindex++) { // Fix: must be strlen() not SmartUnicode as it will break the parsing
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
	private function get_attributes(?string $html) {

		//--
		$attr_with_dbl_quote = '((['.$this->expr_tag_name.']+)\s*=\s*"([^"]*)")*';
		$attr_with_quote = '((['.$this->expr_tag_name.']+)\s*=\s*\'([^\']*)\')*';
		$attr_without_quote = '((['.$this->expr_tag_name.']+)\s*=([^\s>\/]*))*';
		//--

		//--
		$attr = array();
		$pcre = preg_match_all('/'.$attr_with_dbl_quote.'|'.$attr_with_quote.'|'.$attr_without_quote.'/si', (string)$html, $attr);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
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
