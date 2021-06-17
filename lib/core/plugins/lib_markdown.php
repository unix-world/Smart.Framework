<?php
// [LIB - Smart.Framework / Plugins / Markdown to HTML Parser]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Markdown Parser - Output HTML5 Code
// DEPENDS:
//	* Smart::
//	* SmartUnicode::
//	* SmartUtils::
// REQUIRED CSS:
//	* markdown.css
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// This class is based on Parsedown by Emanuil Rusev, License: MIT

// [REGEX-SAFE-OK] ; [PHP8]

/**
 * Class: SmartMarkdownToHTML - Exports Markdown Code to HTML Code.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	Smart, SmartUnicode, SmartUtils, SmartParser
 * @version 	v.20210617
 * @package 	Plugins:ConvertersAndParsers
 *
 * <code>
 * $markdown = new SmartMarkdownToHTML();
 * echo $markdown->text('Hello _SmartMarkdownToHTML_!');
 * // prints: '<p>Hello <i>SmartMarkdownToHTML</i>!</p>'
 * </code>
 *
 */
final class SmartMarkdownToHTML {

	//===================================

	// based on v.1.5.1 with upstream fixes from 1.5.4 -> 1.8.0 + extra customizations and optimizations
	// removed support for HTML markup (was unsafe and could lead to many XSS vulnerabilities ...)
	// other fixes by unixman:
	// 	* fixed multiple security vulnerabilities: character encoding fixes, fixed regex escapings, fixed html escapings, code and regex optimizations
	// 	* extend the syntax to support attributes, added pandoc style block divs

	private const MKDW_VERSION = 'Smart.Markdown.parser@v.1.8.0-r.20210617';

	//===================================

	//--
	private $breaksEnabled = true; 			// add <br> for text on multiple lines
	private $sBreakEnabled = true;			// enable \s and \S
	private $urlsLinked = true; 			// parse URLs from texts
	private $htmlEntitiesDisabled = false; 	// if TRUE will Disable the HTML Entities such as &nbsp; (this is useful and normally should not be disabled)
	private $validateHtml = false; 			// Validate the HTML Code using the SmartHtmlParser with DOM
	private $relative_url_prefix = ''; 		// if an url prefix is given here all relative URLs will be prefixed with this
	private $use_all_unveil = false; 		// if set to TRUE will use unveil for all images
	//--
	private $DefinitionData;
	//--

	private const BlockTypes = [
		'#' => [ 'Header' ],
		'*' => [ 'Rule', 'List' ],
		'+' => [ 'List' ],
		'-' => [ 'SetextHeader', 'Table', 'Rule', 'List' ],
		'0' => [ 'List' ],
		'1' => [ 'List' ],
		'2' => [ 'List' ],
		'3' => [ 'List' ],
		'4' => [ 'List' ],
		'5' => [ 'List' ],
		'6' => [ 'List' ],
		'7' => [ 'List' ],
		'8' => [ 'List' ],
		'9' => [ 'List' ],
		':' => [ 'FencedDiv', 'Table' ], // 'FencedDiv' by unixman
		'<' => [ 'Validate' ],
		'=' => [ 'SetextHeader' ],
		'>' => [ 'Quote' ],
		'[' => [ 'Reference' ],
		'_' => [ 'Rule' ],
		'`' => [ 'FencedCode' ],
		'|' => [ 'Table' ],
		'~' => [ 'FencedPreformat' ], // fix by unixman, use 'FencedPreformat' instead of 'FencedCode'
	];

	private const unmarkedBlockTypes = [
		'Code',
	];

	private const InlineTypes = [ // this one is from v.1.5.4 but is safer than the
		'"'  => [ 'SpecialCharacter'],
		'!'  => [ 'Image'],
		'&'  => [ 'SpecialCharacter' ],
		'*'  => [ 'Emphasis' ],
	//	':'  => [ 'Url' ], // {{{SYNC-DISABLE-INLINE-DIRECT-URL}}} fix by unixman: this is redundant with 'Link' which already solves the URL Link ...
		'<'  => [ 'UrlTag', 'EmailTag', 'SpecialCharacter', 'Validate' ],
		'>'  => [ 'SpecialCharacter' ],
		'['  => [ 'Link' ],
		'_'  => [ 'Emphasis' ],
		'`'  => [ 'Code' ],
		'~'  => [ 'Strikethrough', 'Subscript' ], // '~' => array('Strikethrough') # extended syntax by unixman
		'^'  => [ 'Superscript' ], // extended syntax by unixman
		'\\' => [ 'EscapeSequence' ],
	];

	/* old, from v.1.5.4
	private const inlineMarkerList = '!"*_&[:<>`~\\';
	*/
//	private const inlineMarkerList = '!"*_&[:<>`~^\\'; // extended syntax by unixman
	private const inlineMarkerList = '!"*_&[<>`~^\\'; // extended syntax by unixman  + fix: {{{SYNC-DISABLE-INLINE-DIRECT-URL}}} :: taken out the : required in order to disable: 'Url'

	/* old, from v.1.5.4
	private const specialCharacters = [
		'\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|',
	];
	*/
	private const specialCharacters = [ // fix from 1.8.0, added ~
		'\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~'
	];

	/* old, from v.1.5.4
	private const StrongRegex = [
		'*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
		'_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
	];
	*/
	private const StrongRegex = [ // fix from 1.8.0
		'*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
		'_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
	];

	private const EmRegex = [
		'*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
		'_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
	];

	//-- extra, by unixman: attributes can optional start with a type prefix to know which attributes to assign to nested elements (ex: image in a link, or link in a table cell, or image in a link in a table cell)
	private const regexHdrAttribute = '[ ]*\{(H\:[ ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[ ]*)+)\}'; // Header - optional starts with {H:
	private const regexImgAttribute = '[ ]*\{(I\:[ ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[ ]*)+)\}'; // Images - optional starts with {I:
	private const regexLnkAttribute = '[ ]*\{(L\:[ ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[ ]*)+)\}'; // Links  - optional starts with {L:
	private const regexTblAttribute = '[ ]*\{(T\:[ ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[ ]*)+)\}'; // Tables - optional starts with {T:
	//--

	//===================================


	/**
	 * Class constructor with many options
	 */
	public function __construct(bool $y_breaksEnabled=true, bool $y_sBreakEnabled=true, bool $y_urlsLinked=true, bool $y_htmlEntitiesDisabled=false, $y_validateHtml=false, ?string $y_relative_url_prefix=null, bool $y_use_all_unveil=false) {
		//--
		$this->breaksEnabled 			= (bool) $y_breaksEnabled; // add <br> for text on multiple lines
		$this->sBreakEnabled 			= (bool) $y_sBreakEnabled; // enable ``` ``` \S \s
		$this->urlsLinked 				= (bool) $y_urlsLinked; // parse URLs from texts
		$this->htmlEntitiesDisabled 	= (bool) $y_htmlEntitiesDisabled; // if disabled the Markdown parser will disallow all html entities (ex: &reg; &copy) and will escape them as regular text
		//--
		$this->validateHtml 			= (int)  (Smart::is_nscalar($y_validateHtml) ? $y_validateHtml : false); // validate the HTML via Cleaner, (if Tidy or DOM is available will be used) ; this is slow ... but adds extra safety ; this is normally needed only with untrusted markdown that can come from untrusted users, normally should not be enabled
		//--
		$this->relative_url_prefix 		= (string) trim((string)$y_relative_url_prefix); // if provided use this prefix for all relative urls
		$this->use_all_unveil 			= (bool) $y_use_all_unveil;
		//--
	} //END FUNCTION


	/**
	 * Converts Markdown to HTML
	 * @param STRING $text The Markdown to be processed
	 * @return STRING HTML code
	 */
	public function text($text) {
		//-- check
		if(!Smart::is_nscalar($text)) {
			Smart::log_notice(__METHOD__.' # Text is not nScalar: '.print_r($text,1));
			return '<!-- Markdown Parser Failed ... Text is not nScalar ... -->';
		} //end if
		//-- make sure no definitions are set
		$this->DefinitionData = array(); // init
		//-- pre trim
		$text = (string) trim((string)$text)."\n"; // ensure the last new line if having a backslash
		//-- Fix broking curly quotes: ‘ = &lsquo; [0145] ; ’ = &rsquo; [0146] ; “ = &ldquo; [0147] ; ” = &rdquo; [0148]
		$text = (string) str_replace(['‘', '’', '“', '”', '″', '″'], ['\'', '\'', '"', '"', '"', '"'], $text); // bug fix (special apostrophes will break the UTF-8 markdown ... don't know why !? but need fixing ; perhaps they are interpreted different in UTF-16 context !!!)
		//-- standardize line breaks
		$text = (string) str_replace(["\r\n", "\r"], "\n", $text);
		//-- special breaks ; use `\` + `\n` as a new line enforcer
		if($this->sBreakEnabled) {
			$text = (string) str_replace(['\\'."\n"], "\n".' '."\n", $text); // don;t use &nbsp;, can occur in code tags and is rendered as html escaped
		} else { // IMPORTANT: \ must be enclosed by newlines, otherwise may behave unpredictable on replace ...
			$text = (string) str_replace(['\\'."\n"], "\n", $text);
		} //end if else
		//-- remove surrounding line breaks
		$text = (string) trim($text, "\n");
		//-- fix for tables, escaped | must not be converted (by unixman) {{{SYNC-FIX-ESCAPED-|-}}}
		$text = (string) str_replace( // save existing '┆' as the html entity '┆' to preserve originals ; replace all escaped | with ┆, they will be converted back below
			[
				'\\|',
				'\\`'
			],
			[
				'┆',
				'‛' // this is utf-8 8219, a special backtick
			],
			(string) $text
		);
		//-- split text into lines
		$lines = (array) explode("\n", $text);
		$text = ''; // free mem
		//-- iterate through lines to identify blocks
		$markup = (string) $this->lines($lines);
		$lines = null; // free mem
		//-- trim line breaks
		$markup = (string) trim($markup, "\n");
		//-- fix back for tables (by unixman), must be before prepare HTML because it may convert ┆
		$markup = (string) str_replace(
			[
				'┆',
				'‛', // this is utf-8 8219, a special backtick
				'∖`', // this is utf-8, 8726, a special backslash
				'∖' // this is utf-8, 8726, a special backslash ; if need a backslash character use this
			],
			[
				'|',
				'`',
				'`',
				'\\'
			],
			(string) $markup
		);
		//-- prepare the HTML
		$markup = (string) $this->prepareHTML((string)$markup);
		//-- fix charset
		$markup = (string) SmartUnicode::fix_charset($markup); // fix by unixman (in case that broken UTF-8 characters are detected just try to fix them to avoid break JSON)
		//-- Comment Out PHP tags
		$markup = (string) SmartUtils::comment_php_code((string)$markup, ['tag-start' => '&lt;&quest;', 'tag-end' => '&quest;&gt;']); // fix PHP tags if any remaining ...
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	//===== [PRIVATES]


	//-- # prepare HTML


	private function prepareHTML(string $markup) {
		//--
		if($this->breaksEnabled) {
			$info_linebreaks = 'B:1';
		} else {
			$info_linebreaks = 'B:0';
		} //end if else
		if($this->sBreakEnabled) {
			$info_sbreaks = 'S:1';
		} else {
			$info_sbreaks = 'S:0';
		} //end if else
		if($this->urlsLinked) {
			$info_urls = 'L:1';
		} else {
			$info_urls = 'L:0';
		} //end if else
		if($this->htmlEntitiesDisabled) {
			$info_entities = 'E:0';
		} else {
			$info_entities = 'E:1';
		} //end if else
		if($this->validateHtml) {
			$info_validatehtml = 'V:1';
		} else {
			$info_validatehtml = 'V:0';
		} //end if
		//--
		$markup = "\n".'<!--  HTML/Markdown :: ( '.Smart::escape_html($info_linebreaks.' '.$info_sbreaks.' '.$info_urls.' '.$info_entities.' '.$info_validatehtml.' T:'.date('YmdHi')).' ) -->'."\n".'<div id="markdown-'.sha1((string)$markup).'-'.Smart::uuid_10_num().'" class="markdown">'."\n".$markup."\n".'</div>'."\n".'<!--  # HTML/Markdown # '.Smart::escape_html((string)self::MKDW_VERSION).'  -->'."\n"; // if parsed and contain HTML Tags, add div and comments
		//--
		if($this->validateHtml) {
			$htmlparser = new SmartHtmlParser((string)$markup, true, (int)$this->validateHtml, false);
			$markup = (string) $htmlparser->get_clean_html();
			$htmlparser = null;
		} //end if
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	//-- # unmarked Text


	private function unmarkedText($text) {
		//--
		if(!Smart::is_nscalar($text)) {
			return '';
		} //end if
		//--
		if($this->breaksEnabled) {
			//--
			$text = (string) preg_replace('/[ ]*\n/', "<br>\n", (string)$text);
			//--
		} else {
			//--
			$text = (string) preg_replace('/(?:[ ][ ]+|[ ]*\\\\)\n/', "<br>\n", (string)$text);
			$text = (string) str_replace(" \n", "\n", (string)$text);
			//--
		} //end if else
		//--
		return (string) $text;
		//--
	} //END FUNCTION


	//-- # Lines, Paragraph


	private function line($text) {
		//--
		if(!Smart::is_nscalar($text)) {
			return '';
		} //end if
		//--
		$markup = '';
		//--
		while($excerpt = strpbrk($text, self::inlineMarkerList)) { // $excerpt is based on the first occurrence of a marker
			//--
			$marker = $excerpt[0];
			//--
			$markerPosition = strpos($text, $marker); // mixed
			//--
			$Excerpt = array('text' => $excerpt, 'context' => $text);
			//--
			foreach(self::InlineTypes[$marker] as $z => $inlineType) {
				//--
				$Inline = $this->{'inline'.$inlineType}($Excerpt);
				//--
				if(!isset($Inline)) {
					continue;
				} //end if
				//-- makes sure that the inline belongs to "our" marker
				if(isset($Inline['position']) AND ($Inline['position'] > $markerPosition)) {
					continue;
				} //end if
				//-- sets a default inline position
				if(!isset($Inline['position'])) {
					$Inline['position'] = $markerPosition;
				} //end if
				//-- the text that comes before the inline
				$unmarkedText = substr($text, 0, $Inline['position']);
				//-- compile the unmarked text
				$markup .= $this->unmarkedText($unmarkedText);
				//-- compile the inline
				$markup .= isset($Inline['markup']) ? $Inline['markup'] : $this->element($Inline['element']);
				//-- remove the examined text
				$text = substr($text, $Inline['position'] + $Inline['extent']);
				//--
				continue 2;
				//--
			} //end foreach
			//-- the marker does not belong to an inline
			$unmarkedText = substr($text, 0, $markerPosition + 1);
			//--
			$markup .= $this->unmarkedText($unmarkedText);
			//--
			$text = substr($text, $markerPosition + 1);
			//--
		} //end while
		//--
		$markup .= $this->unmarkedText($text);
		//--
		$markup = (string) $this->fixEscapings((string)$markup); // unixman: this fixes the escapings for inline text ... if break anything else this line must be disabled !
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	private function lines($lines, $render_mode='p') {
		//--
		if(!is_array($lines)) {
			return '';
		} //end if
		//--
		$CurrentBlock = null;
		//--
		$crrLine = -1;
		$emptyLines = 0;
		//--
		foreach($lines as $z => $line) {
			//--
			$crrLine++;
			//--
			if(trim($line) === '') {
				$emptyLines++; // count plus
			} else {
				$emptyLines = 0; // restart counting
			} //end if
			//--
			if(rtrim($line) === '') {
				//--
			//	if(isset($CurrentBlock)) {
				if(is_array($CurrentBlock)) { // fix by unixman
					$CurrentBlock['interrupted'] = true;
				} //end if
				//--
				continue;
				//--
			} //end if
			//--
			if(strpos($line, "\t") !== false) {
				//--
				$parts = (array) explode("\t", $line);
				//--
				$line = (string) (isset($parts[0]) ? $parts[0] : '');
				unset($parts[0]);
				//--
				foreach($parts as $z => $part) {
					//--
				//	$shortage = 4 - mb_strlen($line, 'utf-8') % 4;
					$shortage = 4 - SmartUnicode::str_len($line) % 4; // Unicode compliant Fix by Unixman
					//--
					$line .= str_repeat(' ', $shortage);
					$line .= $part;
					//--
				} //end foreach
				//--
			} //end if
			//--
			$indent = 0;
			//--
			while(isset($line[$indent]) AND $line[$indent] === ' ') {
				$indent ++;
			} //end while
			//--
			$text = $indent > 0 ? substr($line, $indent) : $line;
			//--
			$Line = array('body' => $line, 'indent' => $indent, 'text' => $text);
			//--
			if(isset($CurrentBlock['continuable'])) {
				//--
				$Block = $this->{'block'.$CurrentBlock['type'].'Continue'}($Line, $CurrentBlock);
				//--
				if(isset($Block)) {
					//--
					$CurrentBlock = $Block;
					//--
					continue;
					//--
				} else {
					//--
					if(method_exists($this, 'block'.$CurrentBlock['type'].'Complete')) {
						//--
						$CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
						//--
					} //end if
					//--
				} //end if else
				//--
			} //end if
			//--
			$marker = $text[0];
			//--
			$blockTypes = self::unmarkedBlockTypes;
			//--
			if(isset(self::BlockTypes[$marker])) {
				foreach(self::BlockTypes[$marker] as $z => $blockType) {
					$blockTypes[] = $blockType;
				} //end foreach
			} //end if
			//--
			foreach($blockTypes as $z => $blockType) {
				//--
				$Block = $this->{'block'.$blockType}($Line, $CurrentBlock);
				//--
				if(isset($Block)) {
					//--
					$Block['type'] = $blockType;
					//--
					if(!isset($Block['identified'])) {
						$Blocks[] = $CurrentBlock;
						$Block['identified'] = true;
					} //end if
					//--
					if(method_exists($this, 'block'.$blockType.'Continue')) {
						$Block['continuable'] = true;
					} //end if
					//--
					$CurrentBlock = $Block;
					//--
					continue 2;
					//--
				} //end if
				//--
			} //end foreach
			//--
			if(isset($CurrentBlock) AND !isset($CurrentBlock['type']) AND !isset($CurrentBlock['interrupted'])) {
				//--
				$CurrentBlock['element']['text'] .= "\n".$text;
				//--
			} else {
				//--
				$Blocks[] = $CurrentBlock;
				//--
				switch((string)$render_mode) {
					case 'span':
						$CurrentBlock = $this->span($Line);
						break;
					case 'div':
						$CurrentBlock = $this->div($Line);
						break;
					case 'p':
					default:
						$CurrentBlock = $this->paragraph($Line);
				} //end switch
				$CurrentBlock['identified'] = true;
				//--
			} //end if else
			//--
		} //end foreach
		//--
		if(isset($CurrentBlock['continuable']) AND method_exists($this, 'block'.$CurrentBlock['type'].'Complete')) {
			//--
			$CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
			//--
		} //end if
		//--
		$Blocks[] = $CurrentBlock;
		//--
		unset($Blocks[0]);
		//--
		$markup = '';
		//--
		foreach($Blocks as $z => $Block) {
			//--
			if(isset($Block['hidden'])) {
				continue;
			} //end if
			//--
			$markup .= "\n";
			$markup .= isset($Block['markup']) ? $Block['markup'] : $this->element($Block['element']);
			//--
		} //end foreach
		//--
		$markup .= "\n";
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	private function paragraph($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		return array( // Block
			'element' => array(
				'name' => 'p',
				'text' => $Line['text'],
				'handler' => 'line',
			),
		);
		//--
	} //END FUNCTION


	private function span($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		return array( // Block
			'element' => array(
				'name' => 'span',
				'text' => $Line['text'],
				'handler' => 'line',
			),
		);
		//--
	} //END FUNCTION


	private function div($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		return array( // Block
			'element' => array(
				'name' => 'div',
				'text' => $Line['text'],
				'handler' => 'line',
			),
		);
		//--
	} //END FUNCTION


	//-- # Code


	private function inlineCode($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		$marker = (string) $Excerpt['text'][0];
		//--
	//	if(preg_match('/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', (string)$Excerpt['text'], $matches)) {
		if(preg_match('/^('.preg_quote((string)$marker).'+)[ ]*(.+?)[ ]*(?<!'.preg_quote((string)$marker).')\1(?!'.preg_quote((string)$marker).')/s', (string)$Excerpt['text'], $matches)) { // fix by unixman, keep original + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$text = (string) (isset($matches[2]) ? $matches[2] : '');
			$text = (string) preg_replace("/[ ]*\n/", ' ', $text);
			//--
			return array(
				'extent' => strlen($matches[0]),
				'element' => array(
					'attributes' => [
						'class' => 'mkdw-i-code',
					],
					'name' => 'code',
					'text' => $text,
				),
			);
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockCode($Line, $Block=null) {
		//--
		if(
			!is_array($Line) AND
			isset($Block) AND // specific check for isset
			!isset($Block['type']) AND
			!isset($Block['interrupted'])
		) {
			return;
		} //end if
		//--
		if($Line['indent'] >= 4) {
			//--
			$text = (string) substr((string)$Line['body'], 4);
			//--
			$Block = array(
				'element' => array(
					'name' => 'div', // pre
					'handler' => 'element',
					'text' => array(
						'name' => 'pre', // code
						'text' => $text,
					),
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockCodeContinue($Line, $Block=null) {
		//--
		if($Line['indent'] >= 4) {
			//--
			if(isset($Block['interrupted'])) {
				//--
				$Block['element']['text']['text'] .= "\n";
				//--
				unset($Block['interrupted']);
				//--
			} //end if
			//--
			$Block['element']['text']['text'] .= "\n";
			//--
			$text = (string) substr((string)$Line['body'], 4);
			//--
			$Block['element']['text']['text'] .= $text;
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockCodeComplete($Block=null) {
		//--
		// all escapings are centralized now ; test ok
		//--
		return $Block;
		//--
	} //END FUNCTION


	//-- # Fenced Div

	// no need for inlineFencedDiv


	private function blockFencedDiv($Line) { // by unixman
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		if(preg_match('/^['.preg_quote((string)$Line['text'][0]).']{3}[ ]*([_a-zA-Z0-9\-\.\#@ ]+)?[ ]*$/', $Line['text'], $matches)) {
			//--
			$class = '';
			$id = '';
			$eltype = 'div';
			$handler = 'divs_span'; // by default render div with spans ; if using 'lines' will render div with p (paragraphs)
			if(isset($matches[1])) { // fix from 1.5.4
				//--
				$classes = (array) explode(' ', (string)trim((string)$matches[1]));
				for($i=0; $i<count($classes); $i++) {
					$classes[$i] = (string) trim((string)$classes[$i]);
					if((string)$classes[$i] != '') {
						if(strpos((string)$classes[$i], '.') === 0) {
							$class .= (string) ltrim((string)$classes[$i], '.').' ';
						} elseif(strpos((string)$classes[$i], '#') === 0) {
							$id = (string) ltrim((string)$classes[$i], '#');
						} elseif(strpos((string)$classes[$i], '@') === 0) {
							switch((string)strtolower((string)trim((string)substr((string)$classes[$i], 1)))) {
								//--
								case 'section-div':
									$eltype = 'section';
									$handler = 'divs_div';
									break;
								case 'article-div':
									$eltype = 'article';
									$handler = 'divs_div';
									break;
								case 'header-div':
									$eltype = 'header';
									$handler = 'divs_div';
									break;
								case 'footer-div':
									$eltype = 'footer';
									$handler = 'divs_div';
									break;
								case 'main-div':
									$eltype = 'main';
									$handler = 'divs_div';
									break;
								case 'aside-div':
									$eltype = 'aside';
									$handler = 'divs_div';
									break;
								case 'nav-div':
									$eltype = 'nav';
									$handler = 'divs_div';
									break;
								case 'summary-div':
									$eltype = 'summary';
									$handler = 'divs_div';
									break;
								case 'details-div':
									$eltype = 'details';
									$handler = 'divs_div';
									break;
								//--
								case 'div-divs':
									$handler = 'divs_div';
									break;
								case 'div-paragraphs':
									$handler = 'lines';
									break;
								//--
									default:
										// as default :-)
							} //end switch
						} //end if
					} //end if
				} //end for
				//--
			} //end if
			//--
			$Block = array(
				'char' => $Line['text'][0],
				'element' => array(
					'attributes' => [
						'class' => (string) trim((string)$class),
						'id' 	=> (string) trim((string)$id),
					],
					'name' => (string) $eltype,
					'handler' => (string) $handler,
					'text' => [],
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockFencedDivContinue($Line, $Block=null) { // by unixman
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(!isset($Block['char']) OR ((string)$Block['char'] !== ':')) {
			return;
		} //end if
		//--
		if(isset($Block['complete'])) {
			return;
		} //end if
		//--
		if(strpos((string)$Line['text'], ':::') === 0) {
			$Block['complete'] = true;
			return $Block;
		} //end if
		//--
		$Block['element']['text'][]= $Line['text'];
		//--
		return $Block;
		//--
	} //END FUNCTION


	private function blockFencedDivComplete($Block) { // by unixman
		//--
		// all escapings are centralized now ; test ok
		//--
		return $Block;
		//--
	} //END FUNCTION


	//-- # Fenced Code


	// no need for inlineFencedCode


	private function blockFencedCode($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		//if(preg_match('/^(['.$Line['text'][0].']{3,})[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) {
	//	if(preg_match('/^['.$Line['text'][0].']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) { // fix from 1.5.4
		if(preg_match('/^['.preg_quote((string)$Line['text'][0]).']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) { // fix by unixman, keep v.1.5.4 + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$Element = array(
				'name' => 'code',
				'text' => '',
			);
			//--
			//if(isset($matches[2])) {
			if(isset($matches[1])) { // fix from 1.5.4
				//--
				//$class = 'language-'.$matches[2];
				//$class = 'language-'.$matches[1]; // fix from 1.5.4
				$class = (string) $matches[1]; // fix from 1.5.4 :: modified by unixman to be compliant with highlight.js
				//--
			} else {
				//--
				$class = 'plaintext'; // fix by unixman to be compliant with highlight.js (plaintext)
				//--
			} //end if
			//--
			$Element['attributes'] = array(
				'class' => (string) 'mkdw-code syntax', // req. prefix: mkdw-code (to differentiate from others for main css)
				'data-syntax' => (string) trim((string)$class), // req. prefix: mkdw-code (to differentiate from others for main css)
			);
			//--
			$Block = array(
				'char' => $Line['text'][0],
				'element' => array(
					'name' => 'pre',
					'handler' => 'element',
					'text' => $Element,
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockFencedCodeContinue($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(isset($Block['complete'])) {
			return;
		} //end if
		//--
		if(isset($Block['interrupted'])) {
			//--
			$Block['element']['text']['text'] .= "\n";
			//--
			unset($Block['interrupted']);
			//--
		} //end if
		//--
		if(preg_match('/^'.preg_quote((string)$Block['char']).'{3,}[ ]*$/', $Line['text'])) { // fix by unixman, keep original version + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);
			$Block['complete'] = true;
			//--
			return $Block;
			//--
		} //end if
		//--
		$Block['element']['text']['text'] .= "\n".$Line['body'];
		//--
		return $Block;
		//--
	} //END FUNCTION


	private function blockFencedCodeComplete($Block) {
		//--
		// all escapings are centralized now ; test ok
		//--
		return $Block;
		//--
	} //END FUNCTION


	//-- # Fenced (Code) Preformat :: by unixman (derived from FencedCode) to separate handle 'pre' vs 'code' html tags


	// no need for inlineFencedPreformat


	private function blockFencedPreformat($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
	//	if(preg_match('/^(['.$Line['text'][0].']{3,})[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) {
	//	if(preg_match('/^['.$Line['text'][0].']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) { // fix from 1.5.4
		if(preg_match('/^['.preg_quote((string)$Line['text'][0]).']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches)) { // fix by unixman, keep v.1.5.4 + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$Element = array(
				'name' => 'pre',
				'text' => '',
			);
			//--
			$Block = array(
				'char' => $Line['text'][0],
				'element' => array(
					'name' => 'div',
					'handler' => 'element',
					'text' => $Element,
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockFencedPreformatContinue($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(isset($Block['complete'])) {
			return;
		} //end if
		//--
		if(isset($Block['interrupted'])) {
			//--
			$Block['element']['text']['text'] .= "\n";
			//--
			unset($Block['interrupted']);
			//--
		} //end if
		//--
	//	if(preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text'])) {
		if(preg_match('/^'.preg_quote((string)$Block['char']).'{3,}[ ]*$/', $Line['text'])) { // fix by unixman, keep original + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);
			$Block['complete'] = true;
			//--
			return $Block;
			//--
		} //end if
		//--
		$Block['element']['text']['text'] .= "\n".$Line['body'];
		//--
		return $Block;
		//--
	} //END FUNCTION


	private function blockFencedPreformatComplete($Block) {
		//--
		// all escapings are centralized now ; test ok
		//--
		return $Block;
		//--
	} //END FUNCTION


	//-- # Header


	//-- no need for inlineHeader


	private function blockHeader($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		if(isset($Line['text'][1])) {
			//--
			/*
			$level = 1;
			while(isset($Line['text'][$level]) AND $Line['text'][$level] === '#') {
				$level ++;
			} //end while
			*/
			$level = (int) strspn((string)$Line['text'], '#', 0, 7); // fix by unixman
			//--
		//	if($level > 6) {
			if(($level <= 0) OR ($level > 6)) { // fix by unixman
				return;
			} //end if
			//--
		//	$text = trim($Line['text'], '# ');
			$text = ltrim($Line['text'], '# '); // fix by unixman
			//--
			$attributes = array();
			if(preg_match('/'.self::regexHdrAttribute.'/', $text, $matches)) { // no need for preg_quote() here, self::regexTblAttribute is a REGEX expr
				$attributes = $this->parseAttributeData((string)(isset($matches[2]) ? $matches[2] : ''));
				$text = (string) trim((string)substr((string)$text, 0, (strlen((string)$text) - strlen((string)(isset($matches[0]) ? $matches[0] : '')))));
			} //end if
			//--
			$Block = array(
				'element' => array(
					'name' => 'h'.min(6, $level),
					'text' => $text,
					'handler' => 'line',
				),
			);
			//--
			if(Smart::array_size($attributes) > 0) {
				$Block['element']['attributes'] = (array) $attributes;
			} //end if
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # List


	// no need for inlineList


	private function blockList($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		list($name, $pattern) = (array) (($Line['text'][0] <= '-') ? ['ul', '[*+-]'] : ['ol', '[0-9]+[.]']); // {{{MARKDOWN-PATTERN-UL-OL}}}
		//--
		if(preg_match('/^('.$pattern.'[ ]+)(.*)/', $Line['text'], $matches)) { // no need for preg_quote() here, the $pattern is a regex that come from above {{{MARKDOWN-PATTERN-UL-OL}}}
			//--
			$Block = array(
				'indent' => $Line['indent'],
				'pattern' => (string) $pattern,
				'element' => array(
					'name' => (string) $name,
					'handler' => 'elements',
				),
			);
			//--
			$Block['li'] = array(
				'name' => 'li',
				'handler' => 'li',
				'text' => array(
					$matches[2],
				),
			);
			//--
			$Block['element']['text'][] =& $Block['li']; // pass by reference to reflect later changes
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockListContinue($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if($Block['indent'] === $Line['indent'] AND preg_match('/^'.$Block['pattern'].'(?:[ ]+(.*)|$)/', $Line['text'], $matches)) { // no need for preg_quote() here, the $Block['pattern'] is a regex that come from above {{{MARKDOWN-PATTERN-UL-OL}}}
			//--
			if(isset($Block['interrupted'])) {
				//--
				$Block['li']['text'][]= '';
				//--
				unset($Block['interrupted']);
				//--
			} //end if
			//--
			unset($Block['li']);
			//--
			$text = isset($matches[1]) ? $matches[1] : '';
			//--
			$Block['li'] = array(
				'name' => 'li',
				'handler' => 'li',
				'text' => array(
					$text,
				),
			);
			//--
			$Block['element']['text'][]= & $Block['li'];
			//--
			return $Block;
			//--
		} //end if
		//--
		if($Line['text'][0] === '[' AND $this->blockReference($Line)) {
			return $Block;
		} //end if
		//--
		if(!isset($Block['interrupted'])) {
			//--
			$text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);
			//--
			$Block['li']['text'][]= $text;
			//--
			return $Block;
			//--
		} //end if
		//--
		if($Line['indent'] > 0) {
			//--
			$Block['li']['text'][]= '';
			//--
			$text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);
			//--
			$Block['li']['text'][]= $text;
			//--
			unset($Block['interrupted']);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Quote


	// no need for inlineQuote


	private function blockQuote($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		if(preg_match('/^>[ ]?(.*)/', $Line['text'], $matches)) {
			//--
			$Block = array(
				'element' => array(
					'name' => 'blockquote',
					'handler' => 'lines',
					'text' => (array) $matches[1],
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockQuoteContinue($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if($Line['text'][0] === '>' AND preg_match('/^>[ ]?(.*)/', $Line['text'], $matches)) {
			//--
			if(isset($Block['interrupted'])) {
				//--
				$Block['element']['text'][]= '';
				//--
				unset($Block['interrupted']);
				//--
			} //end if
			//--
			$Block['element']['text'][]= $matches[1];
			//--
			return $Block;
			//--
		} //end if
		//--
		if(!isset($Block['interrupted'])) {
			//--
			$Block['element']['text'][]= $Line['text'];
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Rule


	// no need for inlineRule


	private function blockRule($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
	//	if(preg_match('/^(['.$Line['text'][0].'])([ ]*\1){2,}[ ]*$/', $Line['text'])) {
		if(preg_match('/^(['.preg_quote((string)$Line['text'][0]).'])([ ]*\1){2,}[ ]*$/', $Line['text'])) { // fix by unixman, keep original + add preg_quote() otherwise is totally unsafe and can also crash the PHP code execution
			//--
			$Block = array(
				'element' => array(
					'name' => 'hr'
				),
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Setext Header


	// no need for inlineSetextHeader


	private function blockSetextHeader($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(isset($Block['type']) OR isset($Block['interrupted'])) {
			return;
		} //end if
		//--
		if(rtrim($Line['text'], $Line['text'][0]) === '') {
			//--
			$Block['element']['name'] = (string) (($Line['text'][0] === '=') ? 'h1' : 'h2');
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Reference


	// no need for inlineReference


	private function blockReference($Line) {
		//--
		if(!is_array($Line)) {
			return;
		} //end if
		//--
		if(preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $Line['text'], $matches)) {
			//--
			$id = (string) strtolower((string)$matches[1]);
			//--
			$Data = array(
				'url' => $matches[2],
				'title' => null,
			);
			//--
			if(isset($matches[3])) {
				$Data['title'] = $matches[3];
			} //end if
			//--
			$this->DefinitionData['Reference'][$id] = $Data;
			//--
			$Block = array(
				'hidden' => true,
			);
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Table


	// no need for inlineTable


	private function blockTable($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(isset($Block['type']) OR isset($Block['interrupted'])) {
			return;
		} //end if
		//--
		$table_defs = [];
		//--
		if((strpos((string)$Block['element']['text'], '|') !== false) AND ((string)rtrim((string)$Line['text'], ' -:|') === '')) {
			//-- unixman
			if($Block['element']['text'][0] === '|') {
				//--
				$defs_matches = array();
				if(preg_match('/^\|(\{\!DEF\!\=([_A-Za-z0-9\.\-\#]+;?)*\}( )){1}/', (string)$Block['element']['text'], $defs_matches)) {
					if(isset($defs_matches[0]) AND isset($defs_matches[1])) {
						$Block['element']['text'] = (string) '|'.substr((string)$Block['element']['text'], (int)strlen((string)rtrim((string)$defs_matches[0])));
						$defs_matches[1] = (string) trim((string)$defs_matches[1]);
						$defs_matches[1] = (string) trim((string)$defs_matches[1], '{}');
						$defs_matches[1] = (string) trim((string)$defs_matches[1]);
						$defs_matches[1] = (string) substr((string)$defs_matches[1], 6); // take out prefix: `!DEF!=`
						$table_defs = (array) explode(';', $defs_matches[1]);
					} //end if
				} //end if
				$defs_matches = null;
				//--
				$is_full_width = true;
				if(in_array('AUTO-WIDTH', (array)$table_defs)) {
					$is_full_width = false;
				} //end if else
				//--
			} else {
				$is_full_width = false;
			} //end if
			//-- #unixman
			$alignments = array();
			//--
			$divider = (string) $Line['text'];
			$divider = (string) trim($divider);
			$divider = (string) trim($divider, '|');
			//--
			$dividerCells = (array) explode('|', $divider);
			//--
			foreach($dividerCells as $z => $dividerCell) {
				//--
				$dividerCell = (string) trim((string)$dividerCell);
				//--
				if($dividerCell === '') {
					continue;
				} //end if
				//--
				$alignment = null;
				//--
				if(isset($dividerCell[0]) AND ((string)$dividerCell[0] === ':')) {
					$alignment = 'left';
				} //end if
				if((string)substr((string)$dividerCell, - 1) === ':') {
					$alignment = $alignment === 'left' ? 'center' : 'right';
				} //end if
				//--
				$alignments[] = $alignment;
				//--
			} //end foreach
			//--
			$HeaderElements = array();
			//--
			$header = (string) $Block['element']['text'];
			$header = (string) trim($header);
			$header = (string) trim($header, '|');
			//--
			$headerCells = (array) explode('|', $header);
			//--
			foreach($headerCells as $index => $headerCell) {
				//--
				$headerCell = (string) trim((string)$headerCell);
				//--
				$HeaderElement = array(
					'name' => (string) (in_array('NO-TABLE-HEAD', (array)$table_defs) ? 'td' : 'th'),
					'handler' => 'line',
				);
				//-- unixman
				$matches = array();
				if(preg_match('/'.self::regexTblAttribute.'/', $headerCell, $matches)) { // no need for preg_quote() here, self::regexTblAttribute is a REGEX expr
					if((!array_key_exists('attributes', $HeaderElement)) OR (!is_array($HeaderElement['attributes']))) {
						$HeaderElement['attributes'] = array();
					} //end if
					$HeaderElement['attributes'] += $this->parseAttributeData((string)(isset($matches[2]) ? $matches[2] : ''));
					$headerCell = (string) trim((string)substr((string)$headerCell, 0, (strlen((string)$headerCell) - strlen((string)(isset($matches[0]) ? $matches[0] : '')))));
				} //end if
				//-- # end unixman
				$HeaderElement['text'] = (string) $headerCell;
				//--
				$alignment = (string) (isset($alignments[$index]) ? $alignments[$index] : '');
				if(
					(in_array('ALIGN-HEAD-LEFT', (array)$table_defs)) OR
					(in_array('ALIGN-HEAD-CENTER', (array)$table_defs)) OR
					(in_array('ALIGN-HEAD-RIGHT', (array)$table_defs)) OR
					(in_array('ALIGN-HEAD-AUTO', (array)$table_defs))
				) {
					if(in_array('ALIGN-HEAD-LEFT', (array)$table_defs)) {
						$alignment = 'left';
					} elseif(in_array('ALIGN-HEAD-CENTER', (array)$table_defs)) {
						$alignment = 'center';
					} elseif(in_array('ALIGN-HEAD-RIGHT', (array)$table_defs)) {
						$alignment = 'right';
					} elseif(in_array('ALIGN-HEAD-AUTO', (array)$table_defs)) {
						if(is_numeric((string)trim((string)$HeaderElement['text']))) {
							$alignment = 'right';
						} else {
							$alignment = 'center';
						} //end if else
					} //end if else
				} else {
					if(in_array('ALIGN-LEFT', (array)$table_defs)) {
						$alignment = 'left';
					} elseif(in_array('ALIGN-CENTER', (array)$table_defs)) {
						$alignment = 'center';
					} elseif(in_array('ALIGN-RIGHT', (array)$table_defs)) {
						$alignment = 'right';
					} elseif(in_array('ALIGN-AUTO', (array)$table_defs)) {
						if(is_numeric((string)trim((string)$HeaderElement['text']))) {
							$alignment = 'right';
						} else {
							$alignment = 'center';
						} //end if else
					} //end if else
				} //end if else
				//--
				if((!array_key_exists('attributes', $HeaderElement)) OR (!is_array($HeaderElement['attributes']))) {
					$HeaderElement['attributes'] = array();
				} //end if
				if((string)$alignment != '') {
					$HeaderElement['attributes']['style'] = 'text-align: '.$alignment.';';
				} //end if
				//--
				$HeaderElements[] = (array) $HeaderElement;
				//--
			} //end foreach
			//--
			$table_id = '';
			$table_classes = [];
			if($is_full_width) {
				$table_classes['full-width-table'] = true;
			} //end if
			for($i=0; $i<Smart::array_size($table_defs); $i++) {
				if(strpos((string)$table_defs[$i], '#') === 0) {
					if((string)trim((string)$table_id) == '') { // find the first table ID if any
						$table_id = (string) trim((string)str_replace(['#', '.'], '', (string)$table_defs[$i]));
					} //end if
				} elseif(strpos((string)$table_defs[$i], '.') === 0) {
					$table_classes[(string)trim((string)str_replace(['#', '.'], '', (string)$table_defs[$i]))] = true;
				} //end if
			} //end for
			$table_classes = (string) trim((string)implode(' ', array_keys((array)$table_classes)));
			//--
			$Block = array(
				'@table-defs@' 	=> (array) $table_defs,
				'alignments' 	=> (array) $alignments,
				'identified' 	=> true,
				'element' 		=> [
					'name' 			=> 'table',
					'handler' 		=> 'elements',
					'attributes' 	=> [],
				],
			);
			if((string)trim((string)$table_classes) != '') {
				$Block['element']['attributes']['class'] = (string) $table_classes;
			} //end if
			if((string)trim((string)$table_id) != '') {
				$Block['element']['attributes']['id'] = (string) $table_id;
			} //end if
			//--
			$Block['element']['text'][]= array(
				'name' => 'thead',
				'handler' => 'elements'
			);
			//--
			$Block['element']['text'][]= array(
				'name' => 'tbody',
				'handler' => 'elements',
				'text' => array()
			);
			//--
			$valign = 'top';
			if(
				(in_array('VALIGN-HEAD-CENTER', (array)$table_defs)) OR
				(in_array('VALIGN-HEAD-MIDDLE', (array)$table_defs)) OR
				(in_array('VALIGN-CENTER', (array)$table_defs)) OR
				(in_array('VALIGN-MIDDLE', (array)$table_defs))
			) {
				$valign = 'middle';
			} elseif (
				(in_array('VALIGN-HEAD-BOTTOM', (array)$table_defs)) OR
				(in_array('VALIGN-HEAD-DOWN', (array)$table_defs)) OR
				(in_array('VALIGN-BOTTOM', (array)$table_defs)) OR
				(in_array('VALIGN-DOWN', (array)$table_defs))
			) {
				$valign = 'bottom';
			} //end if else
			//--
			$Block['element']['text'][0]['text'][]= array(
				'name' => 'tr',
				'attributes' => [
					'valign' => (string) $valign,
				],
				'handler' => 'elements',
				'text' => $HeaderElements
			);
			//--
			return (array) $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	private function blockTableContinue($Line, $Block=null) {
		//--
		if((!is_array($Line)) OR (!is_array($Block))) {
			return;
		} //end if
		//--
		if(isset($Block['interrupted'])) {
			return;
		} //end if
		//--
		if(($Line['text'][0] === '|') OR (strpos($Line['text'], '|'))) { // here strpos must not check with true/false, because the first character is already checked and must not be checked again
			//--
			$Elements = array();
			//--
			$row = (string) $Line['text'];
			$row = (string) trim($row);
			$row = (string) trim($row, '|');
			//--
			$pcre = preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', $row, $matches);
			if($pcre === false) {
				Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
				return $Block;
			} //end if
			//--
			$table_defs = (array) $Block['@table-defs@'];
			//--
			if(is_array($matches[0])) {
				foreach($matches[0] as $index => $cell) {
					//--
					$cell = (string) trim((string)$cell);
					//--
					$Element = array(
						'name' => 'td',
						'handler' => 'line',
					);
					//-- unixman
					$matches = array();
					if(preg_match('/'.self::regexTblAttribute.'/', $cell, $matches)) { // no need for preg_quote() here, self::regexTblAttribute is a REGEX expr
						if((!array_key_exists('attributes', $Element)) OR (!is_array($Element['attributes']))) {
							$Element['attributes'] = array();
						} //end if
						$Element['attributes'] += $this->parseAttributeData((string)(isset($matches[2]) ? $matches[2] : ''));
						$cell = trim((string)substr($cell, 0, (strlen((string)$cell) - strlen((string)(isset($matches[0]) ? $matches[0] : '')))));
					} //end if
					//-- # end unixman
					$Element['text'] = $cell;
					//--
					if((!isset($Element['attributes'])) OR (!is_array($Element['attributes']))) {
						$Element['attributes'] = array();
					} //end if
					//--
					$alignment = (string) (isset($Block['alignments'][$index]) ? $Block['alignments'][$index] : '');
					if(in_array('ALIGN-LEFT', (array)$table_defs)) {
						$alignment = 'left';
					} elseif(in_array('ALIGN-CENTER', (array)$table_defs)) {
						$alignment = 'center';
					} elseif(in_array('ALIGN-RIGHT', (array)$table_defs)) {
						$alignment = 'right';
					} elseif(in_array('ALIGN-AUTO', (array)$table_defs)) {
						if(is_numeric((string)trim((string)$Element['text']))) {
							$alignment = 'right';
						} else {
							$alignment = 'left';
						} //end if else
					} //end if else
					//--
					$Element['attributes']['style'] = ($alignment ? 'text-align: '.$alignment.'; ' : '').(isset($Element['attributes']['style']) ? $Element['attributes']['style'] : ''); // fix by unixman
					//--
					$Elements[] = $Element;
					//--
				} //end foreach
			} //end if
			//--
			$valign = 'top';
			if(
				(in_array('VALIGN-CENTER', (array)$table_defs)) OR
				(in_array('VALIGN-MIDDLE', (array)$table_defs))
			) {
				$valign = 'middle';
			} elseif (
				(in_array('VALIGN-BOTTOM', (array)$table_defs)) OR
				(in_array('VALIGN-DOWN', (array)$table_defs))
			) {
				$valign = 'bottom';
			} //end if else
			//--
			$Element = array(
				'name' => 'tr',
				'attributes' => [
					'valign' => (string) $valign,
				],
				'handler' => 'elements',
				'text' => $Elements,
			);
			//--
			$Block['element']['text'][1]['text'][]= $Element;
			//--
			return $Block;
			//--
		} //end if
		//--
	} //END FUNCTION


	//-- # Validate


	private function inlineValidate($Excerpt) {
		//--
		return;
		//--
	} //END FUNCTION


	private function blockValidate($Line) {
		//--
		return;
		//--
	} //END FUNCTION


	//-- # Email Tag


	private function inlineEmailTag($Excerpt) {
		//--
		if($this->urlsLinked !== true) {
			return; // fix by unixman
		} //end if
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if((strpos((string)$Excerpt['text'], '>') !== false) AND preg_match('/^<((mailto:)?\S+?@\S+?)>/i', (string)$Excerpt['text'], $matches)) {
			//-- the email can be as <mailto:addr@eml> or <[addr at eml](mailto:addr@eml)> or can be just like <addr@eml>
			$email = (string) ($matches[1] ?? '');
			$emails = (array) SmartParser::get_arr_emails((string)$email);
			$emails = (array) array_values((array)array_unique((array)$emails));
			$email = (string) trim((string)implode(', ', (array)$emails));
			$emails = null;
			$name = (string) trim((string)($matches[1] ?? ''));
			if((strpos($name, '[') === 0) AND (strpos($name, ']') !== false)) {
				$name = (array) explode(']', (string)substr((string)$name, 1), 2);
				$name = (string) trim((string)($name[0] ?? ''));
				if((string)$name == '') {
					$name = (string) $email;
				} //end if
			} else {
				$name = (string) $email;
			} //end if else
			//--
		//	if(!isset($matches[2])) {
			$url = '';
			if((string)$email != '') {
				$url = (string) 'mailto:'.$email;
			} //end if
			//--
			return array(
				'extent' => (int) strlen(isset($matches[0]) ? $matches[0] : 0),
				'element' => [
					'name' => 'a',
					'text' => (string) $name,
					'attributes' => [
						'href' => (string) $url, // email urls should not be relative fixed !
					],
				],
			);
			//--
		} //end if
		//--
	} //END FUNCTION


	// no need for blockEmailTag


	//-- # Emphasis


	private function inlineEmphasis($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(!isset($Excerpt['text'][1])) {
			return;
		} //end if
		//--
		$marker = (string) $Excerpt['text'][0];
		//--
	//	if($Excerpt['text'][1] === $marker AND preg_match(self::StrongRegex[$marker], $Excerpt['text'], $matches)) {
		if(((string)$Excerpt['text'][1] === (string)$marker) AND isset(self::StrongRegex[$marker]) AND preg_match(self::StrongRegex[$marker], $Excerpt['text'], $matches)) { // fix by unixman ; no need for preg_quote() here, self::StrongRegex[key] if isset() is a REGEX expr
			$emphasis = 'b'; // 'strong';
	//	} elseif(preg_match(self::EmRegex[$marker], $Excerpt['text'], $matches)) {
		} elseif(isset(self::EmRegex[$marker]) AND preg_match(self::EmRegex[$marker], $Excerpt['text'], $matches)) { // fix by unixman ; no need for preg_quote() here, self::EmRegex[key] if isset() is a REGEX expr
			$emphasis = 'i'; // 'em';
		} else {
			return;
		} //end if else
		//--
		return array(
			'extent' => strlen($matches[0]),
			'element' => array(
				'name' => $emphasis,
				'handler' => 'line',
				'text' => $matches[1],
			),
		);
		//--
	} //END FUNCTION


	// no need for blockEmphasis


	//-- # EscapeSequence


	private function inlineEscapeSequence($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(isset($Excerpt['text'][1]) AND in_array($Excerpt['text'][1], self::specialCharacters)) {
			return array(
				'markup' => $Excerpt['text'][1],
				'extent' => 2,
			);
		} //end if
		//--
	} //END FUNCTION


	// no need for blockEmphasis


	//-- # Image


	private function inlineImage($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(!isset($Excerpt['text'][1]) OR $Excerpt['text'][1] !== '[') {
			return;
		} //end if
		//--
		$Excerpt['text'] = substr($Excerpt['text'], 1);
		//--
		$Link = $this->inlineLink($Excerpt, true);
		//--
		if($Link === null) {
			return;
		} //end if
		//--
		if( // unixman: support for SFI Icons
			((string)$Link['element']['attributes']['href'] == 'SFI-ICON') AND
			((string)$Link['element']['text'] == 'ICON')
		) {
			return array(
				'extent' => $Link['extent'] + 1,
				'markup' => (string) ' &nbsp;<i class="'.Smart::escape_html((string)trim((string)$Link['element']['attributes']['title'])).'"></i>&nbsp; ',
			);
		} //end if
		//--
		$Inline = array(
			'extent' => $Link['extent'] + 1,
			'element' => array(
				'name' => 'img',
				'attributes' => array(
					'src' => $Link['element']['attributes']['href'],
					'alt' => $Link['element']['text'],
				),
			),
		);
		//--
		$Inline['element']['attributes'] += $Link['element']['attributes'];
		//--
		if(array_key_exists('href', $Inline['element']['attributes'])) {
			unset($Inline['element']['attributes']['href']);
		} //end if
		//--
		if($this->use_all_unveil === true) {
			$Inline['element']['attributes']['unveil'] = true;
		} //end if
		//--
		if(
			(array_key_exists('alternate', $Inline['element']['attributes'])) AND
			(is_array($Inline['element']['attributes']['alternate'])) AND
			(count($Inline['element']['attributes']['alternate']) === 2)
		) {
			//--
			if(isset($Inline['element']['attributes']['unveil']) AND ($Inline['element']['attributes']['unveil'] === true)) {
				//-- {{{SYNC-MARKDOWN-UNVEIL-CLASS-FIX}}}
				if(!isset($Inline['element']['attributes']['class'])) {
					$Inline['element']['attributes']['class'] = '';
				} //end if
				$tmp_classes = (array) explode(' ', (string)$Inline['element']['attributes']['class']);
				if(!in_array('unveil', $tmp_classes)) {
					$tmp_classes[] = 'unveil'; // if .unveil class is missing add it
				} //end if
				$Inline['element']['attributes']['class'] = (string) implode(' ', (array)$tmp_classes);
				$tmp_classes = null;
				//-- #sync
			} //end if
			//--
			$markup = '';
			//--
			$markup .= '<picture';
			$markup .= (isset($Inline['element']['attributes']['id']) ? ' id="'.Smart::escape_html((string)$Inline['element']['attributes']['id']).'"' : '');
			$markup .= (isset($Inline['element']['attributes']['title']) ? ' title="'.Smart::escape_html((string)$Inline['element']['attributes']['title']).'"' : '');
			$markup .= '>'."\n";
			//--
			$markup .= "\t".'<source';
			if(is_array($Inline['element']['attributes'])) {
				foreach($Inline['element']['attributes'] as $key => $val) {
					if((string)$key == 'unveil') {
						// skip, this is a processing property to integrate with jQuery.unveil
					} elseif(((string)$key == 'alternate') AND is_array($val)) {
						if(isset($Inline['element']['attributes']['unveil']) AND ($Inline['element']['attributes']['unveil'] === true)) {
							$markup .= ' srcset="" data-src="'.Smart::escape_html((string)$val[0]).'" type="'.Smart::escape_html((string)$val[1]).'"';
						} else {
							$markup .= ' srcset="'.Smart::escape_html((string)$val[0]).'" type="'.Smart::escape_html((string)$val[1]).'"';
						} //end if else
					} elseif((string)$key == 'id') {
						// skip, is set on <picture> tag
					} elseif((string)$key == 'title') {
						// skip, is set on <picture> tag
					} elseif((string)$key == 'alt') {
						// skip, is set on <img> tag
					} elseif((string)$key == 'src') {
						// skip, here will use srcset
					} elseif(Smart::is_nscalar($val)) {
						if(preg_match('/['.SmartValidator::regex_stringvalidation_segment('tag-name').']/i', (string)$key)) {
							$markup .= ' '.strtolower((string)$key).'="'.Smart::escape_html((string)$val).'"';
						} //end if
					} //end if
				} //end foreach
			} //end if
			$markup .= '>'."\n";
			//--
			$markup .= "\t".'<img';
			if(is_array($Inline['element']['attributes'])) {
				foreach($Inline['element']['attributes'] as $key => $val) {
					if((string)$key == 'unveil') {
						// skip, this is a processing property to integrate with jQuery.unveil
					} elseif(((string)$key == 'alternate') AND is_array($val)) {
						// skip, will be set on <source> tag
					} elseif((string)$key == 'id') {
						// skip, is set on <picture> tag
					} elseif((string)$key == 'title') {
						// skip, is set on <picture> tag
					} elseif((string)$key == 'alt') {
							$markup .= ' alt="'.Smart::escape_html((string)$val).'"';
					} elseif((string)$key == 'src') {
						if(isset($Inline['element']['attributes']['unveil']) AND ($Inline['element']['attributes']['unveil'] === true)) {
							$markup .= ' src="" data-src="'.Smart::escape_html((string)$val).'"';
						} else {
							$markup .= ' src="'.Smart::escape_html((string)$val).'"';
						} //end if else
					} elseif(Smart::is_nscalar($val)) {
						if(preg_match('/['.SmartValidator::regex_stringvalidation_segment('tag-name').']/i', (string)$key)) {
							$markup .= ' '.strtolower((string)$key).'="'.Smart::escape_html((string)$val).'"';
						} //end if
					} //end if
				} //end foreach
			} //end if
			$markup .= '>'."\n";
			//--
			$markup .= '</picture>';
			//--
			$alternate_img = array(
				'extent' => $Inline['extent'],
				'markup' => (string) $markup
			);
			//--
			$markup = null;
			//--
			$Inline = (array) $alternate_img;
			$alternate_img = null;
			//--
		} elseif(array_key_exists('alternate', $Inline['element']['attributes'])) {
			unset($Inline['element']['attributes']['alternate']); // invalid !
		} //end if
		//--
		if(array_key_exists('element', $Inline)) {
			if(array_key_exists('attributes', $Inline['element'])) {
				if(array_key_exists('unveil', $Inline['element']['attributes'])) {
					//-- {{{SYNC-MARKDOWN-UNVEIL-CLASS-FIX}}}
					if(!isset($Inline['element']['attributes']['class'])) {
						$Inline['element']['attributes']['class'] = '';
					} //end if
					$tmp_classes = (array) explode(' ', (string)$Inline['element']['attributes']['class']);
					if(!in_array('unveil', $tmp_classes)) {
						$tmp_classes[] = 'unveil'; // if .unveil class is missing add it
					} //end if
					$Inline['element']['attributes']['class'] = (string) implode(' ', (array)$tmp_classes);
					$tmp_classes = null;
					//-- #sync
					if(isset($Inline['element']['attributes']['src'])) {
						$Inline['element']['attributes']['data-src'] = $Inline['element']['attributes']['src'];
					} //end if
					//--
					$Inline['element']['attributes']['src'] = ''; // will use data-src as src
					unset($Inline['element']['attributes']['unveil']); // if not markup this key have to be unset
					//--
				} //end if
			} //end if
		} //end if
		//--
		return $Inline;
		//--
	} //END FUNCTION


	// no need for blockImage


	//-- # Link


	private function inlineLink($Excerpt, $isImage=false) { // updated to fixes from v.1.8.0
		//-- unixman
		if($this->urlsLinked !== true) {
			return;
		} //end if
		//-- #end unixman
		$Element = array(
			'name' => 'a',
			'handler' => 'line',
			'text' => null,
			'attributes' => array(
				'href' => null,
				'title' => null,
			),
		);
		//--
		$extent = 0;
		//--
		$remainder = $Excerpt['text'];
		//--
	//	if(preg_match('/\[((?:[^][]|(?R))*)\]/', $remainder, $matches)) {
		if(preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) { // fix from 1.8.0
			//--
			$Element['text'] = $matches[1];
			//--
			$extent += strlen($matches[0]);
			//--
			$remainder = substr($remainder, $extent);
			//--
		} else {
			//--
			return;
			//--
		} //end if else
		//--
	//	if(preg_match('/^[(]((?:[^ ()]|[(][^ )]+[)])+)(?:[ ]+("[^"]*"|\'[^\']*\'))?[)]/', $remainder, $matches)) { // fix from 1.5.4
		if(preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches)) { // fix from 1.8.0
			//--
			$Element['attributes']['href'] = $matches[1];
			//--
			if(isset($matches[2])) {
				$Element['attributes']['title'] = substr($matches[2], 1, -1);
			} //end if
			//--
			$extent += strlen($matches[0]);
			//--
		} else {
			//--
			if(preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
				//--
			//	$definition = $matches[1] ? $matches[1] : $Element['text'];
				$definition = strlen($matches[1]) ? $matches[1] : $Element['text']; // fix from 1.5.4
				$definition = strtolower($definition);
				//--
				$extent += strlen($matches[0]);
				//--
			} else {
				//--
				$definition = strtolower($Element['text']);
				//--
			} //end if else
			//--
			if(!isset($this->DefinitionData['Reference'][$definition])) {
				return;
			} //end if
			//--
			$Definition = $this->DefinitionData['Reference'][$definition];
			//--
			$Element['attributes']['href'] = $Definition['url'];
			$Element['attributes']['title'] = $Definition['title'];
			//--
		} //end if else
		//--
		$Element['attributes']['href'] = (string) str_replace(['&', '<'], ['&amp;', '&lt;'], (string)$Element['attributes']['href']);
		//-- unixman (extra)
		$remainder = (string) substr((string)$Excerpt['text'], (isset($Element['extent']) ? $Element['extent'] : 0));
		$matches = array();
		if($isImage === true) {
			$theRegex = (string) self::regexImgAttribute;
			// do not escape image urls !!!
		} else {
			$theRegex = (string) self::regexLnkAttribute;
			$Element['attributes']['href'] = $this->fixRelativeURL((string)$Element['attributes']['href']);
		} //end if else
		if(preg_match('/'.$theRegex.'/', $remainder, $matches)) { // no need for preg_quote() here, the $theRegex is always a REGEX expr
			$Element['attributes'] += $this->parseAttributeData((string)(isset($matches[2]) ? $matches[2] : ''));
			$extent += strlen($matches[0]);
		} //end if
		//-- #end unixman
		return array(
			'extent' => $extent,
			'element' => $Element,
		);
		//--
	} //END FUNCTION


	// no need for blockLink


	//-- # Special Character


	private function inlineSpecialCharacter($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
	//	if($Excerpt['text'][0] === '&' AND !preg_match('/^&#?\w+;/', $Excerpt['text'])) {
	//	if(substr((string)$Excerpt['text'], 0, 1) === '&' AND !preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'])) { // fix adapted from v.1.8.0
		if(((string)substr((string)$Excerpt['text'], 0, 1) === '&') AND (!preg_match('/^&([a-zA-Z]+|\#[0-9]+);/', $Excerpt['text']))) { // fix by unixman
			return array(
				'markup' => '&amp;',
				'extent' => 1,
			);
		} //end if
		//--
		$SpecialCharacter = [
			'<' => '&lt;',
			'>' => '&gt;',
			'"' => '&quot;'
		];
		//-- #unixman fix
		if($this->htmlEntitiesDisabled) {
			$SpecialCharacter['&'] = '&amp;';
		} //end if
		//-- #end unixman
		if(isset($SpecialCharacter[$Excerpt['text'][0]])) {
			return array(
				'markup' => (string) $SpecialCharacter[$Excerpt['text'][0]],
				'extent' => 1,
			);
		} //end if
		//--
	} //END FUNCTION


	// no need for blockSpecialCharacter


	//-- # Superscript


	// added by unixman to extend syntax: ^Superscript^
	private function inlineSuperscript($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(!isset($Excerpt['text'][0])) {
			return;
		} //end if
		//--
		if($Excerpt['text'][0] === '^' AND preg_match('/^\^(?=\S)(.+?)(?<=\S)\^/', $Excerpt['text'], $matches)) {
			return array(
				'extent' => (int) strlen((isset($matches[0]) ? $matches[0] : 0)),
				'element' => [
					'name' => 'sup',
					'text' => (string) (isset($matches[1]) ? $matches[1] : ''),
					'handler' => 'line',
				],
			);
		} //end if
		//--
	} //END FUNCTION


	// no need for blockSuperscript


	//-- # Subscript


	// added by unixman to extend syntax: ~Subscript~
	private function inlineSubscript($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(!isset($Excerpt['text'][0])) {
			return;
		} //end if
		//--
		$txt1 = (string) ($Excerpt['text'][1] ?? null);
		if($Excerpt['text'][0] === '~' AND $txt1 !== '~' AND preg_match('/^~(?=\S)(.+?)(?<=\S)~/', $Excerpt['text'], $matches)) {
			return array(
				'extent' => (int) strlen((isset($matches[0]) ? $matches[0] : 0)),
				'element' => [
					'name' => 'sub',
					'text' => (string) (isset($matches[1]) ? $matches[1] : null),
					'handler' => 'line',
				],
			);
		} //end if
		//--
	} //END FUNCTION


	// no need for blockSubscript


	//-- # Strikethrough


	// syntax: ~~Strikethrough~~
	private function inlineStrikethrough($Excerpt) {
		//--
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//--
		if(!isset($Excerpt['text'][1])) {
			return;
		} //end if
		//--
		if($Excerpt['text'][1] === '~' AND preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches)) {
			return array(
				'extent' => (int) strlen(isset($matches[0]) ? $matches[0] : 0),
				'element' => [
					'name' => 'del',
					'text' => (string) (isset($matches[1]) ? $matches[1] : ''),
					'handler' => 'line',
				],
			);
		} //end if
		//--
	} //END FUNCTION


	// no need for blockStrikethrough


//	//-- # Url # {{{SYNC-DISABLE-INLINE-DIRECT-URL}}} see the comments above
//
//
//	private function inlineUrl($Excerpt) {
//		//--
//		if($this->urlsLinked !== true) {
//			return;
//		} //end if
//		if(!is_array($Excerpt)) {
//			return;
//		} //end if
//		if((!isset($Excerpt['text'][2])) OR ($Excerpt['text'][2] !== '/')) {
//			return;
//		} //end if
//		//--
//	//	if(preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)) {
//		if((strpos($Excerpt['context'], 'http') !== false) AND (preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE))) { // fix from 1.8.0
//			//--
//			if(!array_key_exists(0, $matches)) {
//				$matches[0] = array();
//			} //end if
//			//--
//			$Inline = array(
//				'extent' => (int) strlen((string)($matches[0][0] ?? '')),
//				'position' => ($matches[0][1] ?? null), // here must be null if not isset()
//				'element' => [
//					'name' => 'a',
//					'text' => (string) ($matches[0][0] ?? ''),
//					'attributes' => [
//						'href' => (string) $this->fixRelativeURL((string)($matches[0][0] ?? '')),
//					],
//				],
//			);
//			//--
//			return $Inline;
//			//--
//		} //end if
//		//--
//	} //END FUNCTION


	// no need for blockUrl


	//-- # Url Tag


	private function inlineUrlTag($Excerpt) {
		//-- unixman
		if($this->urlsLinked !== true) {
			return;
		} //end if
		if(!is_array($Excerpt)) {
			return;
		} //end if
		//-- #end unixman
	//	if((strpos($Excerpt['text'], '>') !== false) AND preg_match('/^<(\w+:\/{2}[^ >]+)>/i', $Excerpt['text'], $matches)) {
		if((strpos($Excerpt['text'], '>') !== false) AND preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches)) { // fix from 1.8.0
			//--
			$url = (string) (isset($matches[1]) ? $matches[1] : ''); // fix from 1.8.0, no need to escape here, also in inlineUrl() the href is not escaped, will be escaped later
			//--
			return array(
				'extent' => (int) strlen(isset($matches[0]) ? $matches[0] : 0),
				'element' => [
					'name' => 'a',
					'text' => (string) $url,
					'attributes' => [
						'href' => (string) $this->fixRelativeURL((string)$url),
					],
				],
			);
			//--
		} //end if
		//--
	} //END FUNCTION


	// no need for blocUrlTag


	//-- # fixCodeEscapings (by unixman)


	private function fixCodeEscapings($code) {
		//--
		return (string) preg_replace(
			'/^\\\\```/m', // will replace just \``` and if starts a line ... but actually the string internally in PHP is \\``` ... regex tested !
			'```',
			(string) $code
		);
		//--
	} //END FUNCTION


	//-- # fixEscapings (by unixman)


	private function fixEscapings($text) { // this is an extra feature, inspired from turndown.js ; some character sequences cannot be used without being escaped in markdown ... revert them here (except the code which has a special revert only ...)
		//--
		return (string) strtr((string)$text, [
			'\\\\' 	=> '\\',
			'\\*' 	=> '*',
			'\\-' 	=> '-',
			'\\+ ' 	=> '+ ', // with a space !
			'\\+' 	=> '+',
			'\\=' 	=> '=',
			'\\#' 	=> '#',
			'\\`' 	=> '`',
			'\\~' 	=> '~',
			'\\(' 	=> '(',
			'\\)' 	=> ')',
			'\\[' 	=> '[',
			'\\]' 	=> ']',
			'\\{' 	=> '{',
			'\\}' 	=> '}',
			'\\>' 	=> '>',
			'\\<' 	=> '<', // extra
			'\\_' 	=> '_',
			'\\. ' 	=> '. ', // with a space
			'\\:' 	=> ':',
		//	'\\|' 	=> '|', // {{{SYNC-FIX-ESCAPED-|-}}} ; this is done above by using a circular replacement (before vs after rendering ...)
		]);
		//--
	} //END FUNCTION


	//-- # fixRelativeURL (by unixman)


	private function fixRelativeURL($url) {
		//--
		if((string)$this->relative_url_prefix == '') {
			return (string) $url;
		} //end if
		//--
		if(
			(strpos((string)trim((string)$url), '#') === 0) OR
			(strpos((string)trim((string)$url), 'mailto:') === 0) OR
			((string)$url === 'SFI-ICON')
		) {
			return (string) $url;
		} //end if
		//--
		if(
			(stripos((string)trim((string)$url), 'http://') !== 0) AND
			(stripos((string)trim((string)$url), 'https://') !== 0) AND
			(stripos((string)trim((string)$url), '//') !== 0)
		) {
			$url = (string) $this->relative_url_prefix.$url;
		} //end if
		//--
		return (string) $url;
		//--
	} //END FUNCTION


	//-- # Handlers


	private function element($Element) {
		//--
		if(!is_array($Element)) {
			return '';
		} //end if
		//--
		$regex_tag_name = (string) '/^['.SmartValidator::regex_stringvalidation_segment('tag-name').']+$/i'; // {{{SYNC-HTML-TAGS-REGEX}}}
		$el_name = (string) strtolower((string)trim((string)$Element['name']));
		//--
		if(((string)$el_name == '') OR (!preg_match((string)$regex_tag_name, (string)$el_name))) {
			Smart::log_notice(__METHOD__.' # Markdown Invalid HTML Element (skip): '.print_r($Element,1));
			return '<!-- NOTICE: Markdown Invalid HTML Element: skip -->';
		} //end if
		//--
		$markup = '<'.Smart::escape_html($el_name);
		//--
	//	if(isset($Element['attributes'])) {
		if((array_key_exists('attributes', $Element)) AND (is_array($Element['attributes']))) {
			//--
			foreach($Element['attributes'] as $name => $value) {
				//--
			//	if($value === null) {
				$value = (string) trim((string)$value);
				if((string)$value == '') {
					continue;
				} //end if
				//--
				$markup .= ' '.Smart::escape_html((string)$name).'="'.Smart::escape_html((string)$value).'"'; // fix by unixman: all escapings of attributes are centralized now HERE !!
				//--
			} //end foreach
			//--
		} //end if
		//--
	//	if(isset($Element['text'])) {
		if(array_key_exists('text', $Element)) {
			//--
			$markup .= '>';
			//--
			if(isset($Element['handler'])) {
				$markup .= (string) $this->{$Element['handler']}($Element['text']);
			} else { // fix by unixman: fix back all escapings ; code should be treated differently !!
				$markup .= (string) Smart::escape_html((string)(
					((string)$el_name == 'code')
						?
						(string) $this->fixCodeEscapings((string)$Element['text']) // code should not be altered, turndown does not escape it which is just perfect ; but adding just a revert of \``` if starting a new line as ``` cannot be used to start a line inside code fence without this hack ...
						:
						(string) $this->fixEscapings((string)$Element['text'])) // for all the rest of elements, except code ...
					); // fix by unixman: all escapings for texts are centralized now HERE !!
			} //end if else
			//--
			$markup .= '</'.Smart::escape_html($el_name).'>';
			//--
		} else {
			//--
			$markup .= '>';
			//--
		} //end if else
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	private function elements($Elements) {
		//--
		if(!is_array($Elements)) {
			return '';
		} //end if
		//--
		$markup = '';
		//--
		foreach($Elements as $z => $Element) {
			$markup .= "\n".$this->element($Element);
		} //end foreach
		//--
		$markup .= "\n";
		//--
		return $markup;
		//--
	} //END FUNCTION


	private function divs_span($lines) {
		//--
		if(!is_array($lines)) {
			return '';
		} //end if
		//--
		return (string) $this->lines($lines, 'span'); // render with span not paragraphs !
		//--
	} //END FUNCTION


	private function divs_div($lines) {
		//--
		if(!is_array($lines)) {
			return '';
		} //end if
		//--
		return (string) $this->lines($lines, 'div'); // render with div not paragraphs !
		//--
	} //END FUNCTION


	private function li($lines) { // Fixed: No Unicode String Functions here !!!
		//--
		if(!is_array($lines)) {
			return '';
		} //end if
		//--
		$markup = (string) $this->lines($lines, 'span'); // render with span not paragraphs !
		//--
		/*
		if( // a fix for the above code if rendered with paragraphs, by unixman, if p tag have attributes, the above code will not match (but this one fixes)
			(
				(
					(strpos((string)$markup, '<p>') !== false) OR
					(strpos((string)$markup, '</p>') !== false)
				)
			)
		) {
			//--
			// the original code was to replace only first <p> and last </p>, so the below regex does so
			$markup = (string) preg_replace('#<p[^>]*?'.'>#si', '', (string)$markup, 1); // replace first ocurence only
			$markup = (string) preg_replace('#</p[^>]*?'.'>(?!.*</p[^>]*?'.'>)#mi', '', (string)$markup, 1); // replace last ocurence only
			// but the conclusion is that is better to replace all occurences of <p> and </p> in ul/ol li ... the lists will display better
			//-- this was tested to replace ALL <p> and </p>
		//	$markup = (string) preg_replace('#<p[^>]*?'.'>#si', '', (string)$markup); // replace all ocurences
		//	$markup = (string) preg_replace('#</p[^>]*?'.'>#si', '', (string)$markup); // replace all ocurences
			//--
		} //end if
		*/
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	// unixman, extra Attributes ($ is replaced with a space for @atr=)
	// Examples:
	//		[link](http://parsedown.org) {L:.primary9 #link .Upper-Case @data-smart=open.modal$700$300}
	//		![alt text](https://www.gstatic.com/webp/gallery/1.sm.jpg "Logo Title Text 1") {I:@width=100 @style=box-shadow:$10px$10px$5px$#888888; %unveil %alternate=https://www.gstatic.com/webp/gallery/1.sm.webp$image/webp}
	// 		TABLE / TH / TD {T: @class=bordered}
	private function parseAttributeData(string $attributeString) {
		//--
		$Data = array();
		//--
		$attributes = preg_split('/[ ]+/', $attributeString, - 1, PREG_SPLIT_NO_EMPTY);
		//--
		$classes = array();
		if(is_array($attributes)) {
			foreach($attributes as $z => $attribute) {
				//--
				if($attribute[0] === '@') { // @ html attr
					$tmp_arr = (array) explode('=', $attribute);
					if(!array_key_exists(0, $tmp_arr)) {
						$tmp_arr[0] = null;
					} //end if
					if(!array_key_exists(1, $tmp_arr)) {
						$tmp_arr[1] = null;
					} //end if
					$Data[(string)trim((string)substr((string)trim((string)$tmp_arr[0]),1))] = (string) trim((string)str_replace(['$'], [' '], (string)trim((string)$tmp_arr[1])));
				} elseif($attribute[0] === '#') { // # html id
					$Data['id'] = (string) substr((string)$attribute, 1);
				} elseif($attribute[0] === '.') { // . html class name
					$classes[] = (string) substr((string)$attribute, 1);
				} elseif($attribute[0] === '%') { // % alternate image
					if((string)$attribute == '%unveil') {
						$Data['unveil'] = true;
					} elseif(strpos((string)$attribute, '%alternate=') === 0) {
						$tmp_attr = (array) explode('$', (string)$attribute);
						$tmp_altimg = (string) substr((string)$tmp_attr[0], 11);
						if(
							(strpos((string)$attribute, '%alternate=') === 0) AND
							(strpos((string)$attribute, '$') !== false) AND
							is_array($tmp_attr) AND
							(count($tmp_attr) === 2) AND
							((string)trim((string)$tmp_attr[0]) != '') AND
							((string)trim((string)$tmp_attr[1]) != '') AND
							((string)trim((string)$tmp_altimg) != '')
						) {
							$tmp_attr[0] = (string) $tmp_altimg;
							$Data['alternate'] = (array) $tmp_attr;
						} else {
						//	Smart::log_notice(__CLASS__.' # Parser Notice: Wrong Attribute (2): `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
						} //end if else
						$tmp_altimg = null;
						$tmp_attr = null;
					} else {
					//	Smart::log_notice(__CLASS__.' # Parser Notice: Wrong Attribute (1): `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
					} //end if else
				} else { // invalid attribute
				//	Smart::log_notice(__CLASS__.' # Parser Notice: Invalid Attribute: `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
				} //end if else
				//--
			} //end foreach
		} //end if
		//--
		if(Smart::array_size($classes) > 0) {
			$Data['class'] = (string) implode(' ', (array)$classes);
		} //end if
		//--
		return (array) $Data;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
