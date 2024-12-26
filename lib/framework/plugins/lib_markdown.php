<?php
// [LIB - Smart.Framework / Plugins / Markdown to HTML Parser]
// (c) 2006-present unix-world.org - all rights reserved
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
//	* SmartHtmlParser::
// REQUIRED CSS:
//	* markdown.css
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// This class is written from the scratch by unixman (c) unix-world.org, License: BSD

// [REGEX-SAFE-OK] ; [PHP8]

/**
 * Class: SmartMarkdownToHTML - Exports Markdown (v2, smart flavour) Code to HTML Code.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: Smart, SmartEnvironment, SmartUnicode, SmartHtmlParser ; optional-constants: SMART_MARKDOWN_LAZYLOAD_DEFAULT_IMG
 * @version 	v.20241218
 * @package 	Plugins:ConvertersAndParsers
 *
 * <code>
 * $markdown = new SmartMarkdownToHTML();
 * $html = $markdown->parse('Hello **SmartMarkdownToHTML** !');
 * // $html would be (default): 				'<div class="mkdw-line">Hello <b>SmartMarkdownToHTML</b> !</div>'
 * // or if set with paragraphs $html would be: '<p>Hello <b>SmartMarkdownToHTML</b> !</p>' <!-- paragraphs cannot be nested like divs, but is just another option ... -->
 * </code>
 *
 */
final class SmartMarkdownToHTML {

	//===================================

	private const MKDW_VERSION = 'smart.markdown:parser@v.2.2.8-r.20241212';

	//===================================

// TODO:
//	[OK] should not fix closing the inline tags if not closed ; maybe intentional, and maybe closed on next line ; and even if not intentional, it can show some errors when rendering !!
//	[OK: MKTPL SYNTAX need to be escaped, at least for 2 equals in IF] avoid the collisions with MarkersTPL Syntax (Ex: IF ==) with italic ... how: extract syntax and put back !?
//	[OK] in line by line loop detect the end line and check if defs are flushed also if end line and not an empty line
//	[OK: answer is NO] the above issue must flush also if another element sudden starts without a new empty line ?!
//	[OK: cannot do, too complicated, but when new block syntax is implemented, would be just ok also without moving] move parse table / parse list in a separate function
//	[OK] use more html entities
//	[OK] add for compatibility with hr: *** and --- and ___ ; check if possible and not collide
//	[OK] add option for support compatibility mode, default false, can be overriden by constant ; in non-compatibility mode disable: PATTERN_INLINE_COMPAT_FIX and old style blockquotes PATTERN_BLOCK_QUOTED are disabled
//	[OK] add links and images ; alternate images
//	[NO: they can be used as images, with attributes] make use of also ::sfi sfi-icon:: for iconic fonts
//		add the rest of missing elements: ascii preformat fix, footnotes, dt/dd (Definition Lists)
//		have options to render with: p instead div ; em instead of i ; strong instead of bold
//		extend Lists to support Task Lists [ ] or [x]
// 	[NO: in pre can use images and links] add Automatic URL Linking in pre !?
//	[OK] by using the multiline blockquote and pre-parsing stanbdard blockquotes and transform in multiline, the BlockQuote can act like div (just put <blockquote> where it starts and </blockquote> where it ends)
//	[OK] add  BlockQuote, both: normal or like gitlab (multiline between <<< and <<<), but only with one level ; it must be extracted first ; pre-parsed ; it can contain code or pre that must be extracted after ; idea: can extract, and replaced with a blockquote like gitlab by mangling left '> '
//		like above idea for indented PRE code blocks ! # perhaps add indent tab with nbsp like here: https://www.markdownguide.org/hacks/#indent-tab ; but for replacing indented pre
//	[NO] hardbreaks: https://docs.gitlab.com/ee/user/asciidoc.html
//	[OK] implement video and audio using media syntax but with {I: %video=video.webm$type=webm$att1=1$att2=2}
//	[OK] finalize options in construct
//	[NO: in the future perhaps, when the framework will support PHP8+ only because it needs a typecast like null|string|array ] make options support also as non-associative array, they are easy to manage ...]
//	[OK] implement LAZYLOAD for media images (unveil)
//	[OK] implement relative URL prefix
//	[OK: will not implement in v2 max-*] implement for tables from table options: ALIGN-HEAD-LEFT ; ALIGN-LEFT ; NO-TABLE-HEAD ; ALIGN-HEAD-CENTER ; ALIGN-AUTO ; max-cells-# max-rows-# ; these are fixes with tables from import ; but should in v2 be used max-* ??
//		table captions to support import from html !? idea: https://forum.obsidian.md/t/captions-for-tables-in-markdown/17240/5 ; or idea, via table DEF
// 	[OK] Inline URL Tags are no more supported in v2: aka <http://#inline.url.tag2>
// 	[OK] Add support for URL encoded data as ?URL@ENC:element%28%29:URL@ENC? for inline code and similar elements contained in links and media
// 	[OK] Support most of elements inside a list

	//--
	private $sBreakEnabled = true;					// enable \ break
	private $mediaExtraEnabled = true; 				// enable extra media: videos, audio ; images are always enabled
	private $useCompatibilityMode = true; 			// use the compatibility mode with v1 and support 99% of v1 syntax, otherwise some old v1 syntax will not be parsed ; v1 syntax is not optimal and takes much overhead ...
	private $lazyLoadImgDisabled = false; 			// if TRUE will Disable the lazy load feature for media images
	private $renderOptions = ''; 					// Render Options: <allow:marker-tpl>,<validate:html>,<validate:html:dom>,<validate:html:tidy>,<validate:html:required>,<validate:html:tidy:required>,<validate:html:dom:required>
	private $optionValidateHtml = false; 			// based on render options
	private $optionAllowMarkerTplSyntax = false; 	// If set to TRUE will not disable the Markers TPL Syntax ; by default does not allow
	private $relative_url_prefix = ''; 				// if an url prefix is given here all relative URLs will be prefixed with this
	private $log_render_notices = false; 			// if set to TRUE will log to the error log all warnings and notices
	private $arr_preserve_extra_syntax = []; 		// if non empty array will preserve all the parts as they are without being rendered (ex: page builder syntax)
	//--

	//-- parse and render helpers
	private $NoticesLog = [];
	private $DefinitionData = [];
	private $documentParsed = false;
	//--

	// regex for attributes like { attr="some value" }: '[a-z0-9\-@%]+\=(")(.+?)(")'
	//-- extra, by unixman: attributes can optional start with a type prefix to know which attributes to assign to nested elements (ex: media in a link, or link in a table cell, or media in a link in a table cell)
	private const regexHeadingAttribute 	= '[\t ]*\{(H\:[\t ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[\t ]*)+)\}'; 	// Header 					- optional, starts with {H:
	private const regexMediaAttribute 		= '[\t ]*\{(I\:[\t ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[\t ]*)+)\}'; 	// Media 					- optional, starts with {I:
	private const regexLinkAttribute 		= '[\t ]*\{(L\:[\t ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[\t ]*)+)\}'; 	// Links 					- optional, starts with {L:
	private const regexTableCellAttribute 	= '[\t ]*\{(T\:[\t ]*)((?:[\#\.@%][_a-zA-Z0-9,%\-\=\$\:;\!\/]+[\t ]*)+)\}'; 	// Table Cell Attributes 	- optional, starts with {T:
	private const regexTableDefinition 		= '[\t ]*(\{\!DEF\!\=([_A-Za-z0-9\.\-\#;]+)\})[\t ]*'; 							// Table Definition 		- optional, first head cell only, starts with: {!DEF!=
	//--

	//-- links: regex
	private const regexLinkStart 			= '\[((?:[^\]\[]++|(?R))*+)\]'; // inspired from parsedown inlineLink part#1 v.1.8.0 with fixes: escapes
	private const regexLinkEnd 				= '[\(]\s*+((?:[^ \t\(\)]++|[\(][^ \t)]+[\)])++)(?:[ \t]+("[^"]*+"|\'[^\']*+\'))?\s*+[\)]'; // inspired from parsedown inlineLink part#2 v.1.8.0 with fixes: escapes
	//-- media: regex
	private const regexMediaStart 			= '[\!]{1}'.self::regexLinkStart; // the same but starts with an exclamation mark: !
	private const regexMediaEnd 			= ''.self::regexLinkEnd; // the same
	//-- links with or without media inside ; media in links: patterns
	private const PATTERN_LINK_AND_MEDIA 	= '/[\!]?'.self::regexLinkStart.''.self::regexLinkEnd.'(('.self::regexMediaAttribute.')|('.self::regexLinkAttribute.'))*/s'; // link or media or link with media inside ; but to parse also media so add the media start exclamation sign (!) ... ; if media inside must be extracted again form the first part as the regexLinkStart includes inside also regexMediaStart if any ;-)
	private const PATTERN_LINK_ONLY 		= '/'.self::regexLinkStart.''.self::regexLinkEnd.'/'; 	// must run after extracting PATTERN_LINK_AND_MEDIA to extract only links, without attributes from the match 1st part
	private const PATTERN_MEDIA_ONLY 		= '/'.self::regexMediaStart.''.self::regexMediaEnd.'/'; 	// must run after extracting PATTERN_LINK_AND_MEDIA to extract only media, without attributes from the match 1st part ; works also for media embedded into links
	//--

	//--
	private const PATTERN_BLOCK_CODE 	= '/\n[`]{3}[\t a-z0-9\-]{0,255}\n([^\n]*?\n)*?[`]{3}\n/s'; 			// Fenced Code Blocks
	//--
	private const PATTERN_INLINE_CODE 	= '/[`]{3}.*?[`]{3}/s'; 												// Inline Code
	//--
	private const PATTERN_BLOCK_PRE 	= '/\n[~]{3}\n([^\n]*?\n)*?[~]{3}\n/s'; 								// Fenced Preformat Blocks
	private const PATTERN_BLOCK_MPRE 	= '/\n[~]{4}\n([^\n]*?\n)*?[~]{4}\n/s'; 								// Fenced Preformat Blocks, Mono
	//--
	private const PATTERN_LIST_UL 		= '/^([\t ]*)[\*\-\+]{1}[\t ]+/'; 										// UL list
	private const PATTERN_LIST_OL 		= '/^([\t ]*)[0-9]+[\.\)]{1}[\t ]+/'; 									// OL List
	//--

	//--
	private const SYNTAX_INLINE_FORMATTING = [
		'**' => 'b', // strong
		'==' => 'i', // em
		'~~' => 's', // strike
		'__' => 'u', // underline
		'--' => 'del',
		'++' => 'ins',
		'!!' => 'sub',
		'^^' => 'sup',
		',,'  => 'q', // inline quote
		'$$' => 'var', // can be used for math
		'??' => 'cite', // inline term def, ; cannot use dt/dd
		'``' => 'mark', // ```inline code``` and block codes are handled elsewhere, there is no risk to collide with them, this is safe
	];
	//--

	//==
	//--
	private const PATTERN_INLINE_COMPAT_FIX 	= [ // the compatibility is ensured for STRONG at least with **bold** even if __bold__ changed in v2 to __underline__
		'``' => '/([`]{1})(?<!\\\\`)(?<!``)([^`]+)(\1)/', 		// CODE compatibility like `code`, now mapped to ``highlight`` because cannot map as ```code``` because ```code``` are pre-extracted and does not need to be escaped !
		'==' => '/([_]{1})(?<!\\\\_)(?<!__)([^_]+)(\1)/', 		// EM  compatibility support as in v2 syntax completely changed from _italic_ or *italic* to ==italic== ; at least will support _italic_
		'!!' => '/([~]{1})(?<!\\\\~)(?<!~~)([^~]+)(\1)/', 		// SUB compatibility support as in v2 syntax completely changed from ~sub~ to !!sub!!
		'^^' => '/([\^]{1})(?<!\\\\\^)(?<!\^\^)([^\^]+)(\1)/', 	// SUP compatibility support as in v2 syntax completely changed from ^sup^ to ^^sup^^
	]; // if \ has to be matched with a regular expression \\, then '\\\\' must be used in PHP code #  https://www.php.net/manual/en/regexp.reference.escape.php
	//--
	private const PATTERN_BLOCK_QUOTED 	= '/(\n[\>]+[^\n]*)+\n/s'; // Compatibility Mode Quoted Block ; must use double \n to separe two blocks !
	//--
	//==

	//==
	//-- special parsing helper characters ; {{{SYNC-SPECIAL-CHARACTER-MKDW-PARSER}}}
	private const SPECIAL_CHAR_ENTRY_MARK 	= "\u{2042}"; // unicode character &#8258; (unicode): ⁂ "\u{2042}"
	private const SPECIAL_CHAR_ENTRY_REPL 	= '&#8258;'; // restore as html entity '&#8258;'
	//-- #end {{{SYNC-SPECIAL-CHARACTER-MKDW-PARSER}}}

	//-- {{{SYNC-SPECIAL-CHARACTER-MKDW-CONVERTER}}}
	private const SPECIAL_CHAR_CONV_REPL 	= '&#8273;'; // the special char used by converter converted to entity ; (unicode) ⁑ ; "\u{2051}" : '&#8273;'
	//-- #end {{{SYNC-SPECIAL-CHARACTER-MKDW-CONVERTER}}}

	//-- {{{SYNC-SPECIAL-CHARACTER-MKDW-TABLE-SEP}}}
	private const SPECIAL_CHAR_TBL_SEP_MARK = '┆'; 			// special character used for tables
	private const SPECIAL_CHAR_TBL_SEP_REPL = '&#9478;'; 	// restore as html entity "&#9478;"
	//-- #end {{{SYNC-SPECIAL-CHARACTER-MKDW-TABLE-SEP}}}

	//-- supported html entities (the most usual)
	private const HTML_ENTITIES_REPLACEMENTS = [
		//-- non-standard
		'&BREAK;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/BREAK/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // non-standard, will be converted back to <br>
		//-- html
		'&nbsp;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/nbsp/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // non breakable space
		'&amp;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/amp/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // & ampersand
		'&quot;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/quot/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // " double quote
		'&apos;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/apos/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ' html5 apos
		'&#039;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/039/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ' html4 apos
		'&#39;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/39/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ' html4 apos, short version of the above
		'&lt;' 		=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lt/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // < used for blockquotes
		'&gt;' 		=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/gt/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // > used for blockquotes
		//-- specials
		'&sol;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/sol/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // / slash
		'&#047;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/047/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // / slash, alternative, better supported
		'&#47;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/47/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // / slash, short version of the above
		'&bsol;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/bsol/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // \ backslash
		'&#092;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/092/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // \ backslash, alternative, better supported
		'&#92;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/92/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // \ backslash, short version of the above
		//-- syntax
		'&ast;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/ast/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // * used for lists or bold
		'&equals;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/equals/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // = used for italic
		'&tilde;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/tilde/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ~ used for strike or paragraphs
		'&lowbar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lowbar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // _ used for underline
		'&dash;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/dash/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // - used for lists or deletions or table align
		'&plus;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/plus/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // + used for lists or inserts
		'&excl;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/excl/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ! used for subscript or media
		'&quest;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/quest/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ? used for dt
		'&Hat;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/Hat/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ^ used for superscript
		'&comma;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/comma/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // , used for inline quote
		'&dollar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/dollar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // $ // used for var
		'&grave;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/grave/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ` used for code or inline code or highlights
		'&colon;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/colon/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // : used for divs or table align
		'&verbar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/verbar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // | used for tables
		'&num;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/num/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // # used for headings h1..h6
		'&period;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/period/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // . used for numeric lists
		'&rpar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/rpar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ) used for numeric lists
		'&lpar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lpar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (
		'&rbrack;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/rbrack/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ] used for links or media
		'&lbrack;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lbrack/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // [ used for links or media
		'&rbrace;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/rbrace/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // } used for attributes
		'&lbrace;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lbrace/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // { used for attributes
		'&percnt;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/percnt/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // %
		//--
		'&ndash;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/ndash/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // –
		'&mdash;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/mdash/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // —
		'&horbar;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/horbar/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ―
		//--
		'&commat;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/commat/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // @
		'&#064;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/064/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // alternate @ ; this should be supported as numeric too because it may be a trick to write an email address to hide it from some robots
		'&#64;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/64/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // alternative, short version of the above
		'&copy;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/copy/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (c)
		'&#169;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/169/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (c), alternative
		'&reg;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/reg/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (R)
		'&#174;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/174/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (R), alternative
		'&trade;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/trade/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // (TM)
		'&middot;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/middot/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // &middot;
		'&nldr;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/nldr/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ‥
		'&hellip;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/hellip/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // …
		//--
		'&lsaquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/lsaquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ‹
		'&rsaquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/rsaquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ›
		'&laquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/laquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // «
		'&raquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/raquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // »
		'&ldquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/ldquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // “
		'&rdquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/rdquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ”
		'&bdquo;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/bdquo/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // „
		//--
		'&spades;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/spades/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♠
		'&clubs;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/clubs/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♣
		'&hearts;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/hearts/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♥
		'&diams;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/diams/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♦
		//--
		'&sung;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/sung/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♪
		'&flat;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/flat/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♭
		'&natur;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/natur/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♮
		'&sharp;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/sharp/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ♯
		//--
		'&check;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/check/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ✓
		'&cross;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/cross/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ✗
		'&sext;' 	=> self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/sext/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // ✶
		//-- {{{SYNC-SPECIAL-CHARACTER-MKDW-CONVERTER}}}
		self::SPECIAL_CHAR_CONV_REPL => self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/convmkdwsf/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // the special mark used by Html2Markdown convertor: ⁑
		//-- {{{SYNC-SPECIAL-CHARACTER-MKDW-PARSER}}}
		self::SPECIAL_CHAR_ENTRY_REPL => self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/specmkdwsf/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // the special mark itself: ⁂
		//-- {{{SYNC-SPECIAL-CHARACTER-MKDW-TABLE-SEP}}}
		self::SPECIAL_CHAR_TBL_SEP_REPL => self::SPECIAL_CHAR_ENTRY_MARK.'/%/special/tblsepmkdwsf/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%', // the special table separator mark ┆
		//--
	];
	//--
	//==

	//==
	//-- {{{SYNC-MKDW-EXTERNAL-CONVERT-ESCAPINGS}}}
	public const ESCAPINGS_REPLACEMENTS = [
		'\\_' 	=> '_',
		'\\*' 	=> '*',
		'\\-' 	=> '-',
		'\\+' 	=> '+',
		'\\=' 	=> '=',
		'\\`' 	=> '`',
		'\\~' 	=> '~',
		'\\!' 	=> '!',
		'\\?' 	=> '?',
		'\\#' 	=> '#',
		'\\$' 	=> '$',
		'\\@' 	=> '@',
		'\\%' 	=> '%',
		'\\^' 	=> '^',
		'\\(' 	=> '(',
		'\\)' 	=> ')',
		'\\[' 	=> '[',
		'\\]' 	=> ']',
		'\\{' 	=> '{',
		'\\}' 	=> '}',
		'\\.' 	=> '.',
		'\\,' 	=> ',',
		'\\:' 	=> ':',
		'\\;' 	=> ';',
		'\\<\\<\\<' => '<<<', // do not replace just single < or > ; they may collide with html tags
	//	'\\|' 	=> '|', // {{{SYNC-FIX-ESCAPED-|-}}} ; this is done above by using a circular replacement (before vs after rendering ...)
		'\\\\' 	=> '\\', // needs to be last, fix discovered from golang ; in PHP the strtr plays smarter and perhaps it push the order by some logic
	];
	//--
	//==

	//===================================


	/**
	 * Class constructor with many options
	 */
	public function __construct(bool $y_sBreakEnabled=true, bool $y_mediaExtraEnabled=true, bool $y_lazyLoadImgDisabled=false, ?string $y_renderOptions=null, ?string $y_relative_url_prefix=null, bool $y_log_render_notices=false, ?array $y_arr_preserve_extra_syntax=null, bool $y_useCompatibilityMode=false) {
		//--
		$this->sBreakEnabled 			= (bool) $y_sBreakEnabled; 			// add <br> for a backslash \ followed by an empty line
		$this->mediaExtraEnabled 		= (bool) $y_mediaExtraEnabled; 		// enable extra media: video, audio, iconic font (SFI) ; images are always enabled
		$this->lazyLoadImgDisabled 		= (bool) $y_lazyLoadImgDisabled; 	// if disabled the Markdown parser will not use the lazy load feature for media images
		//--
		$this->renderOptions 			= (array) Smart::list_to_array((string)$y_renderOptions); // render options
		//--
		$this->optionValidateHtml 	  = ''; // like false, do not validate except if required by options
		if(in_array('validate:html:any', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any'; // validate with any of dom or tidy (dom is preffered, newer tidy versions breaks pre-formats), optional
		} elseif(in_array('validate:html:any:prefer:dom', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any:prefer:dom'; // validate, prefer dom, optional
		} elseif(in_array('validate:html:any:prefer:tidy', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any:prefer:tidy'; // validate, prefer tidy, optional
		} elseif(in_array('validate:html:any:required', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any:required'; // validate with any of dom or tidy (no preference), required
		} elseif(in_array('validate:html:any:required:dom', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any:required:dom'; // validate, prefer dom, required
		} elseif(in_array('validate:html:any:required:tidy', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'any:required:tidy'; // validate, prefer tidy, required
		} elseif(in_array('validate:html:dom', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'dom'; // validate, dom only, optional
		} elseif(in_array('validate:html:tidy', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'tidy'; // validate, tidy only, optional
		} elseif(in_array('validate:html:dom:required', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'dom:required'; // validate, dom, required
		} elseif(in_array('validate:html:tidy:required', (array)$this->renderOptions)) {
			$this->optionValidateHtml = 'tidy:required'; // validate, tidy, required
		} //end if
		//--
		$this->optionAllowMarkerTplSyntax = false;
		if(in_array('allow:marker-tpl', (array)$this->renderOptions)) {
			$this->optionAllowMarkerTplSyntax = true; // IMPORTANT: allowing MarkerTPL Syntax may lead to some portions of code to be unescaped if syntax is wrong or the match for a placeholder which is approxymative will not match the fully the allowed syntax ; but this will appear as warnings in logs from the MarkerTPL's side rendering ; this is a situation with all the TPL syntax in any templating system that will not render or escape wrong syntax placeholders !
		} //end if
		//--
		$this->arr_preserve_extra_syntax = [];
		if((int)Smart::array_type_test($y_arr_preserve_extra_syntax) == 1) { // {{{SYNC-MKDW-CHECK-SYNTAX-NON-ASSOC}}}
			$this->arr_preserve_extra_syntax = (array) $y_arr_preserve_extra_syntax;
		} //end if
		//--
		$this->relative_url_prefix 		= (string) trim((string)$y_relative_url_prefix); // if provided use this prefix for all relative urls
		$this->log_render_notices 		= (bool)   $y_log_render_notices;
		//--
		$this->useCompatibilityMode 	= (bool)   $y_useCompatibilityMode; // compatibility mode, enabled by default
		//--
		$this->documentParsed 			= false; // init as false
		//--
	} //END FUNCTION


	/**
	 * Converts Markdown to HTML
	 * @param STRING $text The Markdown to be processed
	 * @return STRING HTML code
	 */
	public function parse(?string $text) : string {
		//-- check: avoid parse twice
		if($this->documentParsed !== false) {
			Smart::log_warning(__METHOD__.' # ERR: re-using the markdown renderer instance is not supported ... use a new instance');
			Smart::log_notice(__METHOD__.' # Trying to re-use the markdown renderer with text: `'."\n".substr((string)$text, 0, 512).'...`');
			return '<!-- Markdown parser re-used, skip parsing -->';
		} //end if
		//-- clear log notices
		$this->NoticesLog = [];
		//-- pre-fix charset, it is mandatory to be converted to UTF-8
		$text = (string) SmartUnicode::fix_charset($text);
		//-- substitute special reserved character as html entity ; this character is reserved (completely dissalowed), will be used for processing purposes only
	//	$text = (string) str_replace((string)self::SPECIAL_CHAR_ENTRY_MARK, (string)self::SPECIAL_CHAR_ENTRY_REPL, $text);
		$text = (string) strtr($text, [
			(string) self::SPECIAL_CHAR_ENTRY_MARK 		=> (string) self::SPECIAL_CHAR_ENTRY_REPL,
			(string) self::SPECIAL_CHAR_TBL_SEP_MARK 	=> (string) self::SPECIAL_CHAR_TBL_SEP_REPL,
		]);
		//-- standardize line breaks
		$text = (string) str_replace(["\r\n", "\r"], "\n", $text);
		//-- remove surrounding line breaks
		$text = (string) trim($text, "\n");
		//-- parse markdown
		$markup = (string) $this->renderDocument($text); // !!!!!!! MAXIMUM ATENTION WHAT CHARACTERS ARE REPLACED BEFORE THIS TO AVOID CHANGE THE CODE BLOCKS WHICH NEED TO BE PRESERVED AS THEY ARE !!!!!!!
		$text = ''; // free mem
		//-- trim line breaks
		$markup = (string) trim($markup, "\n");
		//-- prepare the HTML
		$markup = (string) $this->prepareHTML((string)$markup);
		//-- fix charset
		$markup = (string) SmartUnicode::fix_charset($markup); // fix by unixman (in case that broken UTF-8 characters are detected just try to fix them to avoid break JSON)
		//-- Comment Out PHP tags
		$markup = (string) Smart::commentOutPhpCode((string)$markup, ['tag-start' => '&lt;&quest;', 'tag-end' => '&quest;&gt;']); // fix PHP tags if any remaining ...
		//-- Dissalow Marker TPL Syntax if not specified so
		if($this->optionAllowMarkerTplSyntax !== true) {
			$markup = (string) SmartMarkersTemplating::prepare_nosyntax_html_template((string)$markup);
		} //end if
		//-- Replace backslashes with the equivalent html entity
	//	$markup = (string) str_replace('\\', '&#092;', $markup);
		$markup = (string) strtr($markup, [
			'\\' => '&#092;',
		]);
		//--
		$this->documentParsed = true;
		//--
		if(strpos((string)$markup, (string)self::SPECIAL_CHAR_ENTRY_MARK) !== false) {
			Smart::log_warning(__METHOD__.'() # Markdown Rendering Issues: The special placeholders markup has been found in the rendered code and should not be there ... some placeholder failed to be replaced perhaps ...');
		} //end if
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	/**
	 * @access 		private
	 * @internal
	 */
	public function notices() : array {
		//--
		return (array) $this->NoticesLog;
		//--
	} //END FUNCTION


	/**
	 * @access 		private
	 * @internal
	 */
	public function validator() : string {
		//--
		return (string) $this->optionValidateHtml;
		//--
	} //END FUNCTION


	//===== [PRIVATES]


	private function notice_log(?string $method, ?string $message) : void {
		//--
		$this->NoticesLog[] = (string) $message;
		//--
		if(!SmartEnvironment::ifDevMode()) {
			return; // do not log in production environments
		} //end if
		//--
		if($this->log_render_notices === true) {
			Smart::log_notice($method.' # '.$message);
		} //end if
		//--
	} //END FUNCTION


	private function prepareHTML(?string $markup) : string {
		//--
		if($this->useCompatibilityMode) {
			$info_compat = 'C:1';
		} else {
			$info_compat = 'C:0';
		} //end if else
		//--
		if($this->sBreakEnabled) {
			$info_linebreaks = 'B:1';
		} else {
			$info_linebreaks = 'B:0';
		} //end if else
		if($this->mediaExtraEnabled) {
			$info_sbreaks = 'M:1';
		} else {
			$info_sbreaks = 'M:0';
		} //end if else
		if($this->lazyLoadImgDisabled) {
			$info_entities = 'Z:0';
		} else {
			$info_entities = 'Z:1';
		} //end if else
		//--
		if((string)$this->optionValidateHtml != '') {
			$info_validatehtml = 'V:1';
		} else {
			$info_validatehtml = 'V:0';
		} //end if
		//--
		$markup = (string) strtr($markup, [
			'&BREAK;' => '<br>',
		]);
		//--
		$markup = "\n".'<!-- HTML/Markdown :: ( '.Smart::escape_html($info_compat.' '.$info_linebreaks.' '.$info_sbreaks.' '.$info_entities.' '.$info_validatehtml.' T:'.date('YmdHi')).' ) -->'."\n".'<div id="markdown-'.sha1((string)$markup).'-'.Smart::uuid_10_num().'" class="markdown">'."\n".$markup."\n".'</div>'."\n".'<!-- # HTML/Markdown # '.Smart::escape_html((string)self::MKDW_VERSION).'  -->'."\n"; // if parsed and contain HTML Tags, add div and comments
		//--
		if((string)$this->optionValidateHtml != '') {
			$htmlparser = new SmartHtmlParser((string)$markup, true, (string)$this->optionValidateHtml, false);
			$markup = (string) $htmlparser->get_clean_html();
			$validerrs = (string) $htmlparser->getValidationErrors();
			if((string)$validerrs != '') {
				$this->NoticesLog[] = (array) explode("\n", (string)$validerrs);
				if(SmartEnvironment::ifDevMode()) { // log only in dev environments
					if($this->log_render_notices === true) {
						Smart::log_notice(__METHOD__.' # HTML Validator['.$this->optionValidateHtml.']: '.(string)$validerrs);
					} //end if
				} //end if
			} //end if
			$validerrs = null;
			$htmlparser = null;
		} //end if
		//--
		return (string) $markup;
		//--
	} //END FUNCTION


	//-- # parse


	private function initDefinitionData(bool $clear) : bool {
		//--
		if($clear === true) {
			$this->DefinitionData = [];
		} elseif(!is_array($this->DefinitionData)) {
			$this->DefinitionData = [];
		} //end if
		//--
		if(!array_key_exists('extracted', (array)$this->DefinitionData) OR !is_array($this->DefinitionData['extracted'])) {
			$this->DefinitionData['extracted'] = [];
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	private function fixEscapings(?string $text) : string { // this is an extra feature, inspired from turndown.js ; some character sequences cannot be used without being escaped in markdown ... revert them here (except the code which has a special revert only ...)
		//--
		return (string) strtr((string)$text, (array)self::ESCAPINGS_REPLACEMENTS);
		//--
	} //END FUNCTION


	private function escapeValidHtmlTagName(?string $tag) : string { // escape and validate a html tag ; if invalid tag name will return 'invalid'
		//--
		$tag = (string) strtolower((string)trim((string)$tag));
		//--
		if(((string)$tag == '') OR (!preg_match('/^[a-z]+/', (string)$tag)) OR (!preg_match('/^[a-z0-9]+$/', (string)$tag))) { // must start with a-z ; can contain 0-9 (ex: h1..h6)
			$tag = 'invalidtag'; // {{{SYNC-MKDW-HTML-TAG-INVALID}}}
		} //end if
		//--
		return (string) $tag;
		//--
	} //END FUNCTION


	// some syntax as inline code and similar elements contained in links or media can't be rendered because some characters conflicts ... this is a solution !
	private function fixDecodeUrlEncSyntax(?string $text) : string { // this will postfix special situations with weird characters in links and media
		//--
		return (string) preg_replace_callback('/(\?URL@ENC\:)(.*)(\:URL@ENC\?)/U', function($matches) {
			return (string) Smart::escape_html((string)urldecode((string)($matches[2] ?? null))); // use url decode instead of rawurldecode ; will do the job of rawurldecode + will decode also + as spaces
		}, (string)$text); // replace all ?URL-ENC:...:URL-ENC? syntax
		//--
	} //END FUNCTION


	private function getDataBlockQuoteds(?string $text) : array { // Quoted Blocks
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_BLOCK_QUOTED, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getDataBlockCodes(?string $text) : array { // Fenced Code Blocks
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_BLOCK_CODE, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getDataInlineCodes(?string $text) : array { // Inline Code
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_INLINE_CODE, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getDataInlineLinksAndMedia(?string $text) : array { // Inline Links, Links with Media, Media
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_LINK_AND_MEDIA, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getInlineLink(?string $text) : array { // Inline Links
		//--
		$matches = array();
		$pcre = preg_match((string)self::PATTERN_LINK_ONLY, (string)$text, $matches, PREG_OFFSET_CAPTURE, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) (is_array($matches) ? $matches : []);
		//--
	} //END FUNCTION


	private function getInlineMedia(?string $text) : array { // Inline Media
		//--
		$matches = array();
		$pcre = preg_match((string)self::PATTERN_MEDIA_ONLY, (string)$text, $matches, PREG_OFFSET_CAPTURE, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) (is_array($matches) ? $matches : []);
		//--
	} //END FUNCTION


	private function getDataBlockPreformats(?string $text) : array { // Fenced Preformat Blocks
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_BLOCK_PRE, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getDataBlockMPreformats(?string $text) : array { // Fenced Preformat Blocks, Mono
		//--
		$matches = array();
		$pcre = preg_match_all((string)self::PATTERN_BLOCK_MPRE, (string)$text, $matches, PREG_PATTERN_ORDER, 0);
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return [];
		} //end if
		//--
		return (array) ((isset($matches[0]) && is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	private function getTextAsLinesArr(?string $txt) : array {
		//--
		return (array) explode("\n", (string)trim((string)$txt)); // {{{SYNC-MKDW-TRIM-ELEMENT-PROC}}}
		//--
	} //END FUNCTION


	private function getTextWithPlaceholders(?string $text, ?string $element, ?array $arr) : string {
		//--
		$this->initDefinitionData(false); // init if req., no clear
		//--
		$nl = '';
		switch((string)$element) {
			//--
			case 'syntax-mtpl':
			case 'syntax-extra':
			case 'inline-links-and-media':
			case 'inline-code':
				$nl = ''; // skip newline in this context
				break;
			//-- {{{SYNC-MKDW-SPECIAL-BLOCK-TYPES}}}
			case 'code':
			case 'mpre':
			case 'pre':
			case 'blockquote':
			//-- #end sync
				$nl = "\n"; // use newline in this context
				break;
			//--
			default:
				Smart::log_warning(__METHOD__.' # Invalid element: `'.$element.'`');
				return (string) $text;
		} //end switch
		//--
		if(!array_key_exists((string)$element.':placeholders', (array)$this->DefinitionData['extracted']) OR !is_array($this->DefinitionData['extracted'][(string)$element.':placeholders'])) {
			$this->DefinitionData['extracted'][(string)$element.':placeholders'] = [];
		} //end if
		//--
		$max = (int) Smart::array_size($arr);
		if((int)$max > 0) {
			for($i=0; $i<$max; $i++) {
				$placeholder = (string) self::SPECIAL_CHAR_ENTRY_MARK.'/%/'.$element.'/place/'.$i.'/'.self::SPECIAL_CHAR_ENTRY_MARK.'%.%';
				$text = (string) Smart::str_replace_first((string)$arr[$i], (string)$nl.$placeholder.$nl, (string)$text);
				if((string)$element == 'inline-links-and-media') {
					$arr[$i] = (string) strtr((string)$arr[$i], [ '```' => '``' ]); // fix: links and media cannot contain inline code ; thus if inline code detected will be changed to highlight (mark) ; for links that contain in description 3 backticks sequence ; it is req. because links and media are extracted out before inline code ... it is a very specific situation !
				} //end if
				$this->DefinitionData['extracted'][(string)$element.':placeholders'][(string)$placeholder] = (string) $arr[$i];
			} //end for
		} //end if
		//--
		return (string) $text;
		//--
	} //END FUNCTION


	private function replaceInlineTextFormatting(?string $text) : string {
		//--
		if((string)trim((string)$text) == '') {
			return '';
		} //end if
		//-- backward compatible text formatting syntax (v1) ... as much as it can be supported !
		if($this->useCompatibilityMode) {
			foreach((array)self::PATTERN_INLINE_COMPAT_FIX as $key => $val) {
				$text = (string) preg_replace_callback((string)$val, function($matches) use($key) {
					return (string) $key.($matches[2] ?? '').$key;
				}, (string)$text);
			} //end foreach
		} //end if
		//--
		foreach((array)self::SYNTAX_INLINE_FORMATTING as $key => $val) {
			//--
			if(strpos((string)$text, (string)$key) !== false) {
				$repls = 0;
				$replt = '';
				while(strpos((string)$text, (string)$key) !== false) {
					//--
					$replt = '';
					if(($repls % 2) !== 0) {
						$replt = '</'.self::escapeValidHtmlTagName((string)$val).'>'; // closing tag
					} else {
						$replt = '<'.self::escapeValidHtmlTagName((string)$val).'>'; // opening tag
					} //end if else
					//--
					$text = (string) Smart::str_replace_first((string)$key, (string)$replt, (string)$text);
					//--
					$repls++;
					//--
					if((int)$repls > 8192) { // {{{SYNC-MKDW-LOOP-INLINE-EVEN-TAGS}}} ; also this number must be even: 8192
						if(SmartEnvironment::ifDebug()) {
							self::notice_log((string)__METHOD__, 'Too many replacements in a single line, line is too long ...');
						} //end if
						break;
					} //end if
					//--
				} //end while
				//-- fix: add closing tag if missing, otherwise need to run html validate as too many tags may remain unclosed ; tags on another line are not supported ... it is better this way to avoid running html validator as mandatory for safety
				if(($repls % 2) !== 0) { // {{{SYNC-MKDW-LOOP-INLINE-EVEN-TAGS}}} ; this condition works just if the above stop number is even: 8192
					$text .= '</'.self::escapeValidHtmlTagName((string)$val).'>'; // closing tag if not even ; if while loop breaks before end be sure close last line, also inline tags cannot spread on many lines !
				} //end if
				//-- fix: remove empty tags: if by example the strings ends with ** will replace it with <b> and will fix closing tag after with </b> resulting in string ending with an empty tag as <b></b> ; this also fixes the situation <b>[\t ]*</b> a tag with just spaces ; all need to be removed at the end after applying html escape
				if((strpos((string)$text, '<') !== false) AND (strpos((string)$text, '>') !== false)) {
					$text = (string) preg_replace_callback('/\<([a-z]+)\>([\t ]*)\<\/\1\>/', function($matches) {
						return (string) ($matches[2] ?? '');
					}, (string)$text); // replace all empty tags
				} //end if
				//--
			} //end if
			//--
		} //end foreach
		//--
		return (string) $text;
		//--
	} //END FUNCTION


	private function fixRelativeURL(?string $url) : string {
		//--
		if((string)$this->relative_url_prefix == '') {
			return (string) $url;
		} //end if
		//--
		if(
			(strpos((string)trim((string)$url), '#') === 0) // anchor
			OR
			(strpos((string)trim((string)$url), 'mailto:') === 0) // mail
		) {
			return (string) $url;
		} //end if
		//--
		if(
			(stripos((string)trim((string)$url), 'http://') !== 0)
			AND
			(stripos((string)trim((string)$url), 'https://') !== 0)
			AND
			(strpos((string)trim((string)$url), '//') !== 0)
		) {
			return (string) $this->relative_url_prefix.$url;
		} //end if
		//--
		return (string) $url;
		//--
	} //END FUNCTION


	private function fixRenderCode(?string $text) : string {
		//--
		return (string) str_replace( // {{{SYNC-MKDW-CODE-FIX-SPECIALS}}}
			[
				'\\`\\`\\`',
				'∖`∖`∖`', // '∖' here is the utf-8 #8726 (a special backslash)
			],
			[
				'```',
				'\\`\\`\\`',
			],
			(string) $text
		);
		//--
	} //END FUNCTION


	private function createHtmlInline(?string $text, ?string $type, bool $headings_parsed=false) : string {
		//--
		// Smart.Markdown inline syntax support:
		// 		IMPORTANT:
		// 			- do not use ## @@ %% here, they may collide with Marker Templating Syntax ; or {{: :}} which may collide with PageBuilder Syntax
		// 			- do not use << or >> here, they are already html escaped when the replacements need to occur
		//--
		//	**bold** ; here the __bold__ is no more supported, it is now __underline__, but at least this is compatible with commonmark as **bold**
		//	==italic== ; but support original compatible _italic_ as there is no other way to have compatibility with commonmark ; no support for *italic* because is redundant and if only can support one compatibility for bold will support also just one for italic
		//	~~strikethrough~~
		//	__underline__
		//	--delete--
		//	++insert++
		//	!!subscript!! ; but support original compatible ~subscript~ as there is no other way to have compatibility with commonmark
		//	^^superscript^^ ; but support original compatible ^supperscript^ as there is no other way to have compatibility with commonmark
		//	$$variable$$
		//	,,quote,,
		//	??definition term??
		// ``highlight``
		//--
		if((string)trim((string)$text) == '') {
			return '';
		} //end if
		//--
		$tag_start = '';
		$tag_end = '';
		//--
		$atts = [];
		//--
		$type = (string) strtolower((string)trim((string)$type));
		switch((string)$type) {
			case 'p':
				if(strpos((string)$text, '####### ') === 0) { // skip div and newline for (h7:span) ; add a space before
					$tag_start = ' '; // preserve a space instead newline
					$tag_end = '';
				} elseif(strpos((string)$text, '######## ') === 0) { // skip div and newline for (h8:dfn) ; add a newline before
					$tag_start = "\n";
					$tag_end = '';
				} else { // for all the rest, DEFAULT
					$tag_start = '<div class="mkdw-line">';
					$tag_end = '</div>'."\n";
				} //end if
				break;
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$headings_parsed = true; // avoid re-parse headers in the same line if already there is one
				$attributes = (array) $this->parseElementAttributes((string)$text, (string)$type);
				$text = (string) $attributes['element:text'];
				$atts = (array)  $attributes['element:atts'];
				$attributes = null;
				$tag_start = '<'.self::escapeValidHtmlTagName($type).$this->buildAttributeData((array)$atts).'>';
				$tag_end = '</'.self::escapeValidHtmlTagName($type).'>'."\n";
				break;
			case 'span': // h7
				$headings_parsed = true; // avoid re-parse headers in the same line if already there is one
				$attributes = (array) $this->parseElementAttributes((string)$text, 'span'); // parse as h7
				$text = (string) $attributes['element:text'];
				$atts = (array)  $attributes['element:atts'];
				$attributes = null;
				$tag_start = '<span'.$this->buildAttributeData((array)$atts).'>';
				$tag_end = '</span>'; // no extra new line
				break;
			case 'dfn': // h8
				$headings_parsed = true; // avoid re-parse headers in the same line if already there is one
				$attributes = (array) $this->parseElementAttributes((string)$text, 'dfn'); // parse as h7
				$text = (string) $attributes['element:text'];
				$atts = (array)  $attributes['element:atts'];
				$attributes = null;
				$tag_start = '<dfn'.$this->buildAttributeData((array)$atts).'>';
				$tag_end = '</dfn>'; // no extra new line
				break;
			case 'li':
			case 'td':
				// the tags and attributes are created in the main loop for these ... ; in this case(s) it is just the line content that must be processed inline and escaped, then return elsewhere to create the tags
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid Element Type: '.$type);
				return (string) Smart::escape_html((string)$text);
		} //end switch
		//--
		//== {{{SYNC-MKDW-RENDER-ENTITIES-AND-INLINE-FORMATTING}}}
		//-- replace html entities with placeholders
		$text = (string) strtr((string)$text, (array)self::HTML_ENTITIES_REPLACEMENTS);
		//-- headings: h1..h6 (# style)
		$unparsed = true;
		if($headings_parsed === false) {
			$renderr = (array) $this->renderLineHeadings((string)$text);
			$text = (string) $renderr['crr'];
			$unparsed = (bool) $renderr['unparsed'];
			$renderr = null;
		} //end if
		//-- apply default escaping, if not escaped elsewhere
		if($unparsed === true) {
			$text = (string) Smart::escape_html((string)$text); // line not parsed, escape html here ; if line was parsed, the escapes were made in renderLineHeadings
		} //end if
		//-- render back html entities
		$text = (string) strtr((string)$text, (array)array_flip((array)self::HTML_ENTITIES_REPLACEMENTS));
		//-- text formatting syntax
		$text = (string) $this->replaceInlineTextFormatting((string)$text);
		//--
		//== #end sync
		//--
		return (string) $tag_start.$text.$tag_end;
		//--
	} //END FUNCTION


	private function setBackTextWithPlaceholders(?string $text, ?string $element) : string {
		//--
		$this->initDefinitionData(false); // init if req., no clear
		//--
		switch((string)$element) {
			//--
			case 'syntax-mtpl':
			case 'syntax-extra':
			case 'inline-links-and-media':
			case 'inline-code':
			//-- {{{SYNC-MKDW-SPECIAL-BLOCK-TYPES}}}
			case 'code':
			case 'mpre':
			case 'pre':
			case 'blockquote':
			//-- #end sync
				// ok
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid element: `'.$element.'`');
				return (string) $text;
		} //end switch
		//--
		if(!array_key_exists((string)$element.':placeholders', (array)$this->DefinitionData['extracted']) OR !is_array($this->DefinitionData['extracted'][(string)$element.':placeholders'])) {
			$this->DefinitionData['extracted'][(string)$element.':placeholders'] = [];
		} //end if
		//--
		foreach($this->DefinitionData['extracted'][(string)$element.':placeholders'] as $key => $val) {
			if((string)trim((string)$key) != '') {
				if((string)$element == 'syntax-mtpl') { // mtpl syntax: PRESERVE exactly how it is
					$text = (string) str_replace((string)$key, (string)$val, (string)$text); // ! no new line here, PRESERVE ; replace all occurences here not only first, they may have been replicated, by ex the =@. for links and media
				} elseif((string)$element == 'syntax-extra') { // extra syntax: PRESERVE exactly how it is
					$text = (string) str_replace((string)$key, (string)$val, (string)$text); // ! no new line here, PRESERVE ; replace all occurences here not only first, they may have been replicated, by ex the =@. for links and media
				} elseif((string)$element == 'inline-links-and-media') { // links, links with media, media
					$val = (string) $this->renderLinksAndMedia((string)$val); // it returns html safe escaped code
					$val = (string) $this->fixEscapings((string)$val); // for links and media need to be fixed here ... cannot later !
					$text = (string) Smart::str_replace_first((string)$key, (string)$val, (string)$text); // ! no new line here, it is inline syntax
				} elseif((string)$element == 'inline-code') { // code
					$val = (string) substr((string)$val, 3, -3); // remove 1st ``` and last ```
					$val = (string) $this->fixRenderCode((string)$val); // {{{SYNC-MKDW-CODE-FIX-SPECIALS}}}
					$text = (string) Smart::str_replace_first((string)$key, (string)'<code class="mkdw-inline-code">'.Smart::escape_html((string)$val).'</code>', (string)$text); // ! no new line here, it is inline syntax
				} else {
					$arr = (array) $this->getTextAsLinesArr((string)$val);
					$max = (int) Smart::array_size($arr);
					if((int)$max > 0) {
						for($i=0; $i<$max; $i++) {
							if((string)$element == 'blockquote') { // compat blockquote pre-process
								$arr[$i] = (string) ltrim((string)$arr[$i], '>'); // first ltrim only the > characters
								$tmp_test_char = (string) substr($arr[$i], 0, 1);
								if(((string)$tmp_test_char == ' ') OR ((string)$tmp_test_char == "\t")) {
									$arr[$i] = (string) substr((string)$arr[$i], 1); // eliminate only first space or tab, DO NOT ltrim() all spaces ; otherwise, the code or pre inside will loose the format
								} //end if
								$tmp_test_char = null;
								$arr[$i] .= "\n"; // do not escape !! will be processed later as lines between a blockquote
								if((int)$i <= 0) { // first
									$arr[$i] = '<<<'."\n".$arr[$i];
								} //end if
								if((int)$i === ((int)$max - 1)) { // last
									$arr[$i] .= '<<<'."\n";
								} //end if
							} elseif((string)$element == 'code') { // pre+code
								if((int)$i === 0) {
									$syntax = (string) trim((string)ltrim((string)$arr[$i], '`'));
									if((string)$syntax == '') {
										$syntax = 'plaintext';
									} //end if
									$arr[$i] = '<pre><code class="mkdw-code syntax" data-syntax="'.Smart::escape_html((string)$syntax).'">'; // data syntax must not be parsed inline
								} elseif((int)$i === ((int)$max - 1)) { // last
									$arr[$i] = '</code></pre>'."\n";
								} else {
									$arr[$i] = (string) $this->fixRenderCode((string)$arr[$i]); // {{{SYNC-MKDW-CODE-FIX-SPECIALS}}}
									$arr[$i] = (string) Smart::escape_html((string)$arr[$i])."\n"; // do not parse inline, preserve code
								} //end if else
							} else { // pre
								if((int)$i === 0) {
									if($element == 'mpre') {
										$arr[$i] = '<pre class="mkdw-mono">';
									} else {
										$arr[$i] = '<pre>';
									} //end if else
								} elseif((int)$i === ((int)$max - 1)) { // last
									$arr[$i] = '</pre>'."\n";
								} else {
									$arr[$i] = (string) Smart::escape_html((string)$arr[$i])."\n"; // this should not be parsed inline ! (ex: html comments are tranformed in del tag)
								} //end if else
							} //end if else
						} //end for
					} //end if
					$text = (string) Smart::str_replace_first((string)$key, (string)implode('', (array)$arr), (string)$text);
				} //end if else
			} //end if
		} //end foreach
		//--
		return (string) $text;
		//--
	} //END FUNCTION


	// unixman, extra Attributes ($ is replaced with a space for @atr=)
	// Examples:
	//		[link](http://unix-world.org) {L:.primary9 #link .Upper-Case @data-smart=open.modal$700$300}
	//		![alt text](https://www.gstatic.com/webp/gallery/1.sm.jpg "Logo Title Text 1") {I:@width=100 @style=box-shadow:$10px$10px$5px$#888888; %lazyload=unveil %alternate=https://www.gstatic.com/webp/gallery/1.sm.webp$image/webp}
	//		![Sample Video OGG](https://www.w3schools.com/html/mov_bbb.ogg){I: #video-1 %video=ogg @width=320 @height=176 @controls=none}
	//		![Sample Video Webm/MP4](https://www.w3schools.com/html/mov_bbb.webm$https://www.w3schools.com/html/mov_bbb.mp4){I: #video-2 %video=webm$mp4 @width=320 @height=176 @preload=none @poster=https://www.w3schools.com/images/w3html5.gif}
	//		![Sample Audio OGG/MP3](https://www.w3schools.com/html/horse.ogg$https://www.w3schools.com/html/horse.mp3){I: #audio-1 %audio=ogg$mpeg}
	// 		TABLE / TH / TD {T: @class=bordered}
	private function parseAttributeData(string $eltype, ?string $attributeString) : array {
		//--
		// TODO: use $eltype for a list of allowable attributes
		//--
		$arr = array();
		//--
		$attributes = preg_split('/[ ]+/', (string)$attributeString, -1, PREG_SPLIT_NO_EMPTY);
		//--
		$classes = [];
		if(is_array($attributes)) {
			//--
			foreach($attributes as $z => $attribute) {
				//--
				if($attribute[0] === '@') { // @ html attr
					if(strpos((string)$attribute, '=') !== false) { // ex: @style=box-shadow:$10px$10px$5px$#888888;filter:grayscale!!_80%_!!;
						$tmp_arr = (array) explode('=', $attribute);
						if(!array_key_exists(0, $tmp_arr)) {
							$tmp_arr[0] = null;
						} //end if
						if(!array_key_exists(1, $tmp_arr)) {
							$tmp_arr[1] = null;
						} //end if
					//	$arr[(string)trim((string)substr((string)trim((string)$tmp_arr[0]),1))] = (string) trim((string)str_replace(['$', '!-', '-!'], [' ', '(', ')'], (string)trim((string)$tmp_arr[1])));
						$arr[(string)trim((string)substr((string)trim((string)$tmp_arr[0]),1))] = (string) trim((string)strtr((string)trim((string)$tmp_arr[1]), ['$' => ' ', '!!_' => '(', '_!!' => ')']));
					} else { // ex: @article-div
						$arr['id'] = (string) substr((string)$attribute, 1);
					} //end if else
				} elseif($attribute[0] === '#') { // # html id
					$arr['id'] = (string) substr((string)$attribute, 1);
				} elseif($attribute[0] === '.') { // . html class name
					$classes[] = (string) substr((string)$attribute, 1);
				} elseif($attribute[0] === '%') { // % alternate media (used for images)
					if($eltype === 'a') {
						if((string)$attribute == '%blank') {
							$arr['target'] = '_blank';
						} //end if
					} elseif($eltype === 'media') {
						if(strpos((string)$attribute, '%video=') === 0) {
							$arr['video'] = (string) substr((string)$attribute, 7);
						} elseif(strpos((string)$attribute, '%audio=') === 0) {
							$arr['audio'] = (string) substr((string)$attribute, 7);
						} elseif(strpos((string)$attribute, '%lazyload=') === 0) {
							$arr['lazyload'] = (string) substr((string)$attribute, 10);
						} elseif(strpos((string)$attribute, '%alternate=') === 0) {
							$tmp_attr = (array) explode('$', (string)$attribute);
							$tmp_alternate = (string) substr((string)($tmp_attr[0] ?? null), 11);
							if(
								(strpos((string)$attribute, '$') !== false) AND
								(Smart::array_size($tmp_attr) === 2) AND
								((string)trim((string)$tmp_attr[0]) != '') AND
								((string)trim((string)$tmp_attr[1]) != '') AND
								((string)trim((string)$tmp_alternate) != '')
							) {
								$tmp_attr[0] = (string) $tmp_alternate;
								$arr['alternate'] = (array) $tmp_attr;
							} else {
								if(SmartEnvironment::ifDebug()) {
									self::notice_log((string)__METHOD__, 'Parser Notice: Wrong Attribute (2): `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
								} //end if
							} //end if else
							$tmp_alternate = null;
							$tmp_attr = null;
						} else {
							if(SmartEnvironment::ifDebug()) {
								self::notice_log((string)__METHOD__, 'Parser Notice: Wrong Attribute (1): `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
							} //end if
						} //end if else
					} //end if else
				} else { // invalid attribute
					if(SmartEnvironment::ifDebug()) {
						self::notice_log((string)__METHOD__, 'Parser Notice: Invalid Attribute: `'.$attribute.'` in: `'.$attributeString.'`'); // this can occur with converted markdown ... (Ex: perl docs)
					} //end if
				} //end if else
				//--
			} //end foreach
			//--
		} //end if
		//--
		if(Smart::array_size($classes) > 0) {
			$tmp_classes = (array) array_values((array)array_unique((array)$classes));
			$classes = [];
			foreach($tmp_classes as $clskey => $clsval) {
				$clsval = (string) trim((string)$clsval); // allow camel case
				if((string)$clsval != '') {
					if(stripos((string)$clsval, 'mkdw-') !== 0) { // allowed classes: must not start with 'mkdw-', the prefix is reserved for the main CSS of Markdown
						$classes[] = (string) $clsval;
					} //end if
				} //end if
			} //end foreach
			$arr['class'] = (string) implode(' ', (array)$classes);
			$classes = null;
			$tmp_classes = null;
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	private function buildAttributeData(?array $arr, ?array $exclusions=[]) : string {
		//--
		$catt = [];
		//--
		if((int)Smart::array_size($arr) > 0) {
			//--
			$real_atts = [];
			foreach($arr as $akey => $aval) {
				$akey = (string) strtolower((string)trim((string)$akey)); // make all lower string
				if( // validate the HTML attribute
					((string)$akey != '') // non-empty
					AND
					preg_match('/^[a-z]+/', (string)$akey) // must start with a-z
					AND
					preg_match('/^[a-z0-9\-]+$/', (string)$akey) // may contain only a-z 0-9 -
				) {
					$ok = true;
					if(is_array($exclusions)) {
						if(array_key_exists((string)$akey, (array)$exclusions)) {
							$ok = false;
						} //end if
					} //end if
					if($ok === true) {
						$real_atts[(string)$akey] = $aval; // mixed
					} //end if
				} //end if
			} //end foreach
			$arr = null;
			//--
			foreach($real_atts as $key => $val) {
				$prefix = '';
				if(is_array($val)) {
					$prefix = 'data-mkdw-'; // TODO: use later these kind of attributes for post rendering !
					$val = (string) Smart::json_encode((array)$val);
				} //end if
				$catt[] = (string) Smart::escape_html((string)$prefix.$key).'="'.Smart::escape_html((string)$val).'"'; // attributes must not be parsed inline
			} //end foreach
			//--
		} //end if
		//--
		if((int)Smart::array_size($catt) > 0) {
			return ' '.implode(' ', (array)$catt);
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


	private function parseElementAttributes(?string $text, ?string $type) : array {
		//--
		$arr = [
			'element:text' => (string) $text, // use default, just in case it returns below
			'element:atts' => (array)  [],
		];
		//--
		$regex = '';
		//--
		$type = (string) strtolower((string)trim((string)$type));
		switch((string)$type) {
			case 'media': // media
				$regex = (string) self::regexMediaAttribute;
				break;
			case 'a': // link
				$regex = (string) self::regexLinkAttribute;
				break;
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
			case 'span': // h7
			case 'dfn': // h8
				$regex = (string) self::regexHeadingAttribute;
				break;
			case 'td':
				$regex = (string) self::regexTableCellAttribute;
				break;
			default:
				Smart::log_warning(__METHOD__.' # Invalid Element Type: '.$type);
				return (array) $arr;
		} //end switch
		//--
		if((string)$regex == '') {
			Smart::log_warning(__METHOD__.' # Empty Regex for Element Type: '.$type);
			return (array) $arr;
		} //end if
		//--
		$atts = [];
		//--
		$matches = [];
		if(preg_match('/'.$regex.'/', (string)$text, $matches)) { // no need for preg_quote() here expects a REGEX expr
			$attributeRawString = (string) ($matches[0] ?? '');
			$attributeString = (string) ($matches[2] ?? '');
			if((string)$attributeString != '') {
				$atts = (array) $this->parseAttributeData((string)$type, (string)$attributeString);
			} //end if
			if((string)$attributeRawString != '') {
				$text = (string) Smart::str_replace_first((string)$attributeRawString, '', (string)$text); // {{{SYNC-MKDW-REPL-ATTS-DEF}}}
			} //end if
		} //end if
		//--
		$arr['element:text'] = (string) $text;
		$arr['element:atts'] = (array)  $atts;
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	private function parseTableAttributes(?string $firstTableHeaderCell) : array {
		//--
		$table_defs = [];
		$defs_matches = array();
		if(preg_match('/^'.self::regexTableDefinition.'/', (string)$firstTableHeaderCell, $defs_matches)) {
			if(isset($defs_matches[0]) AND isset($defs_matches[1])) {
				$firstTableHeaderCell = (string) Smart::str_replace_first((string)$defs_matches[0], '', (string)$firstTableHeaderCell); // {{{SYNC-MKDW-REPL-ATTS-DEF}}}
				if(isset($defs_matches[2])) {
					$tmp_table_defs = (array) explode(';', (string)$defs_matches[2]);
					for($i=0; $i<Smart::array_size($tmp_table_defs); $i++) {
						$tmp_table_defs[$i] = (string) trim((string)$tmp_table_defs[$i]);
						if((string)$tmp_table_defs[$i] != '') {
							if(!in_array((string)$tmp_table_defs[$i], (array)$table_defs)) {
								$table_defs[] = (string) $tmp_table_defs[$i];
							} //end if
						} //end if
					} //end for
				} //end if
			} //end if
		} //end if
		$defs_matches = null;
		//--
		return array(
			'hcell:text' => (string) $firstTableHeaderCell,
			'table:defs' => (array)  $table_defs,
		);
		//--
	} //END FUNCTION


	private function getListEntryLevelByLeadingSpaces(?string $leadingSpaces) : int {
		//--
		if((string)$leadingSpaces == '') {
			return 0;
		} //end if
		//--
		if((int)strlen((string)$leadingSpaces) == 1) {
			return 1;
		} //end if
		//--
		$leadingSpaces = (string) str_replace('    ', "\t", (string)$leadingSpaces); // replace 4 spaces with tabs
		$leadingSpaces = (string) str_replace('   ', "\t", (string)$leadingSpaces); // replace 3 spaces with tabs
		$leadingSpaces = (string) str_replace('  ', "\t", (string)$leadingSpaces); // replace 2 spaces with tabs
		$leadingSpaces = (string) str_replace(' ', "\t", (string)$leadingSpaces); // replace 1 spaces with tabs
		//--
		$leadingNumSpaces = (int) strlen((string)$leadingSpaces);
		if((int)$leadingNumSpaces < 0) {
			$leadingNumSpaces = 0;
		} elseif((int)$leadingNumSpaces > 7) { // {{{SYNC-MKDW-LISTS-MAX-LEVELS}}}
			$leadingNumSpaces = 7; // max 8 levels
		} //end if
		//--
		return (int) $leadingNumSpaces;
		//--
	} //END FUNCTION


	private function formatListNodeEntry(?string $value) : string {
		//--
		$value = (string) trim((string)$value);
		if((string)$value != '') {
			$arr = (array) explode(' ', (string)$value, 4);
			$value = (string) str_replace("\t", ' ', (string)trim((string)$arr[0]))."\t".str_replace("\t", ' ', (string)trim((string)$arr[1]))."\t".str_replace("\t", ' ', (string)trim((string)$arr[2]))."\t".str_replace("\t", ' ', (string)trim((string)$arr[3])); // must keep numbers as the keys must be unique !
		} //end if
		//--
		return (string) $value;
		//--
	} //END FUNCTION


	private function decodeListNodeArray(?array $arr) : array {
		//--
		$xarr = array();
		//--
		if((int)Smart::array_size($arr) > 0) {
			//--
			foreach($arr as $key => $value) {
				//--
				if((string)$key != '@') {
					$key = (string) $this->formatListNodeEntry($key);
				} //end if
				//--
				if(is_array($value)) {
					$xarr[(string)$key] = $this->decodeListNodeArray($value);
				} else {
					$xarr[(string)$key] = (string) $this->formatListNodeEntry($value);
				} //end if else
				//--
			} //end foreach
			//--
		} //end if
		//--
		return (array) $xarr;
		//--
	} //END FUNCTION


	private function convertProcessedListArrToHtml(?array $arr, int $level=0) : string {
		//--
		$max = (int) Smart::array_size($arr);
		//--
		if((int)$max <= 0) {
			return '';
		} //end if
		//--
		if((int)$level < 0) {
			$level = 0;
		} //end if
		//--
		$lst_type = '';
		$html = '';
		//--
		$lsize = (int) Smart::array_size($arr);
		foreach($arr as $key => $val) {
			$karr = (array) explode("\t", (string)$key, 4);
			if((string)trim((string)$lst_type) == '') {
				$lst_type = 'ul';
				if((int)$lsize > 1) { // fix: for lists with only one element force them as UL (the case of broken lists ...)
					if(strpos((string)trim((string)$karr[0]), '#') === 0) {
						$lst_type = 'ol';
					} //end if
				} //end if
				if((string)trim((string)$lst_type) != '') {
					$html .= (string) "\n".str_repeat("\t", (int)$level).'<'.self::escapeValidHtmlTagName($lst_type).'>'."\n";
				} //end if
			} //end if
			$arr_li = (array) explode('|', (string)$karr[2]);
			$html .= (string) str_repeat("\t", (int)$karr[1]).'<li>'.$this->createHtmlInline((string)base64_decode((string)$arr_li[0]), 'li');
			$arr_li[1] = (string) trim((string)$arr_li[1]);
			if((string)$arr_li[1] != '') {
				$arr_li[1] = (string) base64_decode((string)$arr_li[1]);
				if((string)trim((string)$arr_li[1]) != '') {
					$html .= (string) $arr_li[1];
				} //end if
			} //end if
			$arr_li = null;
			if((int)Smart::array_size($val) > 0) {
				$html .= (string) $this->convertProcessedListArrToHtml($val, (int)$karr[1]);
			} //end if
			$html .= '</li>'."\n";
		} //end foreach
		//--
		if((string)trim((string)$lst_type) != '') {
			$html .= (string) str_repeat("\t", (int)$level). '</'.self::escapeValidHtmlTagName($lst_type).'>';
		} //end if
		//--
		return (string) $html;
		//--
	} //END FUNCTION


	private function createListNodeKey(array $val, int $iterator) : string {
		//--
		$val['type']  = (string) ($val['type'] ?? null);
		$val['level'] = (int)    ($val['level'] ?? null);
		$val['code']  = (string) ($val['code'] ?? null);
		//--
		$val['extra'] = (array)  ($val['extra'] ?? null);
		if(Smart::array_size($val['extra']) > 0) {
			$val['extra'] = (string) implode("\n", (array)$val['extra']);
		} else {
			$val['extra'] = '';
		} //end if
		//--
		return (string) ($val['type'] === 'ol' ? '#ol:' : '*ul:').' '.(int)$val['level'].' '.base64_encode((string)$val['code']).'|'.base64_encode((string)$val['extra']).' '.(int)$iterator;
		//--
	} //END FUNCTION


	private function parseListNodesArr(array $arr, int $cnt=0, int $level=0) : array {
		//--
		if((int)$cnt < 0) {
			$cnt = 0;
		} //end if
		if((int)$level < 0) {
			$level = 0;
		} //end if
		//--
		$crrNode = [];
		//--
		for($i=(int)$cnt; $i<Smart::array_size($arr); $i++) {
			//--
			if(is_array($arr[$i])) {
				//--
				if(isset($arr[$i+1]) AND is_array($arr[$i+1]) AND ((int)$arr[$i+1]['level'] > (int)$level)) { // > ; level increase
					$key = (string) $this->createListNodeKey((array)$arr[$i], $i);
					$tarr = (array) $this->parseListNodesArr((array)$arr, (int)($i+1), (int)$arr[$i+1]['level']);
					$i = (int) $tarr['iterator'];
					$crrNode[(string)$key] = (array) $tarr['nodes'];
					$tarr = null;
				} else { // = ; same level
					$key = (string) $this->createListNodeKey((array)$arr[$i], $i);
					$crrNode[(string)$key] = null;
				} //end if
				//--
				if(isset($arr[$i+1]) AND is_array($arr[$i+1]) AND ((int)$arr[$i+1]['level'] < (int)$level)) { // < ; level decrease ; at the end ... it can be after any of the above cases, in the case if level increase must get the updated $i ... !
					break;
				} //end if
				//--
			} //end if
			//--
		} //end for
		//--
		return [ 'iterator' => (int)$i, 'nodes' => (array)$crrNode ];
		//--
	} //END FUNCTION


	private function convertListArrToHtml(?array $arr) : string {
		//--
		$tarr = (array) $this->parseListNodesArr($arr);
		$arr = [ '@' => (array)$tarr['nodes'] ];
//print_r($arr); die();
		$tarr = null;
		//--
		$arr = (array) $this->decodeListNodeArray($arr);
		if(!isset($arr['@'])) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice(__METHOD__.' # List Data Conversion Failed: @');
			} //end if
			return (string) '';
		} //end if
		if((int)Smart::array_size($arr['@']) <= 0) {
			if(SmartEnvironment::ifDebug()) {
				Smart::log_notice(__METHOD__.' # List Data Conversion Failed: [..]');
			} //end if
			return (string) '';
		} //end if
		//--
	//	print_r($arr['@']); die(); // DEBUG
		return (string) $this->convertProcessedListArrToHtml((array)$arr['@']);
		//--
	} //END FUNCTION


	private function renderAltOrTitle(?string $txt) : string { // {{{SYNC-SMART-STRIP-TAGS-LOGIC}}}
		//--
		$txt = (string) self::replaceInlineTextFormatting((string)$txt); // render syntax (will be cleared below)
		$txt = (string) strip_tags((string)$txt); // cleanup syntax tags
		//--
		$txt = (string) Smart::decode_html_entities((string)$txt); // restore html entities
		$txt = (string) preg_replace('/&\#?([0-9a-z]+);/i', ' ', (string)$txt); // clean any other remaining html entities
		$txt = (string) preg_replace('/[ \\t]+/', ' ', (string)$txt); // replace multiple tabs or spaces with one space
		//--
		$txt = (string) strtr((string)$txt, [
			"''" 	=> '',
			"' '" 	=> '',
			'""' 	=> '',
			'" "' 	=> '',
		]);
		//--
		return (string) Smart::escape_html((string)$txt);
		//--
	} //END FUNCTION


	private function renderHtmlMediaOnly(?array $extracted_media_only_arr, ?string $link_or_media_md_part) : ?string {
		//--
		if(!is_array($extracted_media_only_arr)) {
			return null;
		} //end if
		if(!isset($extracted_media_only_arr[0]) OR !is_array($extracted_media_only_arr[0])) {
			return null;
		} //end if
		if(!isset($extracted_media_only_arr[1]) OR !is_array($extracted_media_only_arr[1])) {
			return null;
		} //end if
		if(!isset($extracted_media_only_arr[2]) OR !is_array($extracted_media_only_arr[2])) {
			return null;
		} //end if
		if(!isset($extracted_media_only_arr[3]) OR !is_array($extracted_media_only_arr[3])) {
			$extracted_media_only_arr[3] = [ '', -1 ]; // {{{SYNC-MKDW-MEDIA-LINKS-TITLE-CAN-MISS}}} ; if no title text, there are only 3 elements in the array: 0, 1 and 2
		} //end if
		//--
		$media_alt_txt 	= (string) trim((string)($extracted_media_only_arr[1][0] ?? null));
		$media_title 	= (string) trim((string)($extracted_media_only_arr[3][0] ?? null));
		$media_src 		= (string) trim((string)($extracted_media_only_arr[2][0] ?? null));
		//--
		$media_title = (string) trim((string)trim((string)$media_title, '"\'')); // remove trailing quotes
		//--
		$attributes = (array) $this->parseElementAttributes((string)$link_or_media_md_part, 'media'); // TODO: identify media type here perhaps ...
		$atts = (array) $attributes['element:atts'];
		$attributes = null;
		//--
		if((string)$media_src == '') {
			return null; // invalid media ; the media src is empty
		} //end if
		//--
		if((string)$media_src == 'SFI-ICON') {
			//--
			if(!$this->mediaExtraEnabled) {
				return null; // extra media disabled
			} //end if
			//--
			if((string)$media_title != '') {
				//--
				$media_title = (string) str_replace("\t", ' ', (string)$media_title);
				//--
				if(strpos((string)$media_title, 'sfi sfi-') === 0) {
					return '<i class="sfi sfi-'.Smart::escape_html((string)substr((string)$media_title, 8)).'"'.(isset($atts['style']) ? 'style="'.Smart::escape_html((string)$atts['style']).'"' : '').'></i>&nbsp; '.($media_alt_txt ? self::renderAltOrTitle((string)$media_alt_txt) : '');
				} //end if
				//--
			} //end if
			//--
		} //end if
		//--
		$media_id = '';
		if(isset($atts['id'])) {
			$media_id = (string) trim((string)$atts['id']);
			unset($atts['id']);
		} //end if
		//--
		if(isset($atts['video'])) {
			//--
			if(!$this->mediaExtraEnabled) {
				return null; // extra media disabled
			} //end if
			//--
			$media_title = (string) $media_alt_txt; // for video there is no alt attribute; to avoid duplicating, use for the title always the alt
			//--
			$atts['video'] = (string) strtolower((string)trim((string)$atts['video']));
			//--
			$arr_video_srcs  = (array) explode('$', (string)$media_src);
			$arr_video_types = (array) explode('$', (string)$atts['video']);
			//--
			unset($atts['video']);
			//--
			$html_video_source = '';
			for($i=0; $i<Smart::array_size($arr_video_srcs); $i++) {
				$videosrc = (string) trim((string)$arr_video_srcs[$i]);
				if((string)$videosrc != '') {
					$videotype = (string) strtolower((string)trim((string)($arr_video_types[$i] ?? null)));
					switch((string)$videotype) {
						case 'ogg':
						case 'webm':
						case 'mp4':
							$videotype = '/'.$videotype;
							break;
						case '':
						default:
							$videotype = ''; // reset ; unrecognized
					} //end switch
				} //end if
				$html_video_source .= '<source type="video'.Smart::escape_html((string)$videotype).'" src="'.Smart::escape_html((string)$videosrc).'">';
			} //end for
			//--
			if((string)$html_video_source == '') {
				return null; // invalid video
			} //end if
			//--
			if(!isset($atts['preload'])) {
				$atts['preload'] = 'auto';
			} //end if
			//--
			if(!isset($atts['controls'])) {
				$atts['controls'] = 'true';
			} //end if
			if(((string)$atts['controls'] == 'no') OR ((string)$atts['controls'] == 'none') OR ((string)$atts['controls'] == 'false')) {
				unset($atts['controls']);
			} //end if
			//--
			return '<video'.($media_id ? ' id="'.Smart::escape_html((string)$media_id).'"' : '').($media_title ? ' title="'.self::renderAltOrTitle((string)$media_title).'"' : '').$this->buildAttributeData((array)$atts).'>'.$html_video_source.'</video>';
			//--
		} elseif(isset($atts['audio'])) {
			//--
			if(!$this->mediaExtraEnabled) {
				return null; // extra media disabled
			} //end if
			//--
			$media_title = (string) $media_alt_txt; // for audio there is no alt attribute; to avoid duplicating, use for the title always the alt
			//--
			$atts['audio'] = (string) strtolower((string)trim((string)$atts['audio']));
			//--
			$arr_audio_srcs  = (array) explode('$', (string)$media_src);
			$arr_audio_types = (array) explode('$', (string)$atts['audio']);
			//--
			unset($atts['audio']);
			//--
			$html_audio_source = '';
			for($i=0; $i<Smart::array_size($arr_audio_srcs); $i++) {
				$audiosrc = (string) trim((string)$arr_audio_srcs[$i]);
				if((string)$audiosrc != '') {
					$audiotype = (string) strtolower((string)trim((string)($arr_audio_types[$i] ?? null)));
					switch((string)$audiotype) { // https://en.wikipedia.org/wiki/HTML5_audio
						case 'ogg':
						case 'mpeg': // mp3
						case 'mp4':
						case 'webm':
						case 'flac':
						case 'wav':
							$audiotype = '/'.$audiotype;
							break;
						case '':
						default:
							$audiotype = ''; // reset ; unrecognized
					} //end switch
				} //end if
				$html_audio_source .= '<source type="audio'.Smart::escape_html((string)$audiotype).'" src="'.Smart::escape_html((string)$audiosrc).'">';
			} //end for
			//--
			if((string)$html_audio_source == '') {
				return null; // invalid audio
			} //end if
			//--
			if(!isset($atts['preload'])) {
				$atts['preload'] = 'auto';
			} //end if
			//--
			if(!isset($atts['controls'])) {
				$atts['controls'] = 'true';
			} //end if
			if(((string)$atts['controls'] == 'no') OR ((string)$atts['controls'] == 'none') OR ((string)$atts['controls'] == 'false')) {
				unset($atts['controls']);
			} //end if
			//--
			return '<audio'.($media_id ? ' id="'.Smart::escape_html((string)$media_id).'"' : '').($media_title ? ' title="'.self::renderAltOrTitle((string)$media_title).'"' : '').$this->buildAttributeData((array)$atts).'>'.$html_audio_source.'</audio>';
			//--
		} //end if else
		//--
		if((string)$media_title == '=@.') {
			$media_title = (string) $media_alt_txt; // unixman fix: if title is "=@." make the same as alt to avoid duplicating the same text in the markdown code
		} //end if
		//--
		$alternate_img_src = '';
		$alternate_img_type = '';
		if(isset($atts['alternate'])) {
			if(is_array($atts['alternate'])) {
				if(isset($atts['alternate'][0])) {
					$alternate_img_src = (string) trim((string)$atts['alternate'][0]);
					if((string)$alternate_img_src != '') {
						if(isset($atts['alternate'][1])) {
							$alternate_img_type = (string) trim((string)$atts['alternate'][1]);
						} //end if
					} //end if
				} //end if
			} //end if
			unset($atts['alternate']);
		} //end if
		//--
		$use_lazyload = false;
		$lazyload_class = '';
		if(!$this->lazyLoadImgDisabled) {
			if(isset($atts['lazyload'])) {
				$lazyload_class = (string) trim((string)$atts['lazyload']);
				if((string)$lazyload_class != '') {
					$use_lazyload = true;
				} //end if
				unset($atts['lazyload']);
			} //end if
		} else {
			if(isset($atts['lazyload'])) {
				unset($atts['lazyload']); // do not set a lazyload="" attribute on img
			} //end if
		} //end if
		//-- loading
		if(isset($atts['loading'])) {
			$atts['loading'] = (string) strtolower((string)trim((string)$atts['loading']));
			if((string)$atts['loading'] == 'lazy') {
				$use_lazyload = false; // avoid mixing lazyload with loading lazy
			} else {
				unset($atts['loading']); // do not set a loading="" attribute on img
			} //end if else
		} //end if
		//--
		if($use_lazyload) {
			if(!isset($atts['class'])) {
				$atts['class'] = (string) $lazyload_class;
			} else {
				$atts['class'] = (string) trim((string)$lazyload_class.' '.trim((string)$atts['class']));
			} //end if
		} //end if
		//--
		$html = '';
		//--
		$src = '';
		$srcset = '';
		$datasrc = '';
		//--
		if((string)$alternate_img_src != '') {
			$html .= '<picture'.($media_id ? ' id="'.Smart::escape_html((string)$media_id).'"' : '').($media_title ? ' title="'.self::renderAltOrTitle((string)$media_title).'"' : '').$this->buildAttributeData((array)$atts).'>';
			if($use_lazyload) {
				$srcset = '';
				$datasrc = (string) $alternate_img_src;
			} else {
				$srcset = (string) $alternate_img_src;
				$datasrc = '';
			} //end if else
			$html .= '<source'.$this->buildAttributeData((array)$atts).($alternate_img_type ? ' type="'.Smart::escape_html((string)$alternate_img_type).'"' : '').' srcset="'.Smart::escape_html((string)$srcset).'"'.($datasrc ? ' data-src="'.Smart::escape_html((string)$datasrc).'"' : '').'>';
		} //end if else
		//--
		if($use_lazyload) {
			if(defined('SMART_MARKDOWN_LAZYLOAD_DEFAULT_IMG')) {
				$src = (string) trim((string)SMART_MARKDOWN_LAZYLOAD_DEFAULT_IMG);
			} else {
				$src = '';
			} //end if
			$datasrc = (string) $media_src;
		} else {
			$src = (string) $media_src;
			$datasrc = '';
		} //end if else
		$html .= '<img'.(($media_id && ((string)$alternate_img_src == '')) ? ' id="'.Smart::escape_html((string)$media_id).'"' : '').($media_alt_txt ? ' alt="'.self::renderAltOrTitle((string)$media_alt_txt).'"' : '').($media_title ? ' title="'.self::renderAltOrTitle((string)$media_title).'"' : '').$this->buildAttributeData((array)$atts).' src="'.Smart::escape_html((string)$src).'"'.($datasrc ? ' data-src="'.Smart::escape_html((string)$datasrc).'"' : '').'>';
		//--
		if((string)$alternate_img_src != '') {
			$html .= '</picture>';
		} //end if
		//--
		return (string) $html;
		//--
	} //END FUNCTION


	private function renderHtmlLinkOnly(?array $extracted_link_only_arr, ?string $link_or_media_md_part) : ?string {
		//--
		if(!is_array($extracted_link_only_arr)) { // it can contain a sub-media !
			return null;
		} //end if
		if(!isset($extracted_link_only_arr[0]) OR !is_array($extracted_link_only_arr[0])) {
			return null;
		} //end if
		if(!isset($extracted_link_only_arr[1]) OR !is_array($extracted_link_only_arr[1])) {
			return null;
		} //end if
		if(!isset($extracted_link_only_arr[2]) OR !is_array($extracted_link_only_arr[2])) {
			return null;
		} //end if
		if(!isset($extracted_link_only_arr[3]) OR !is_array($extracted_link_only_arr[3])) {
			$extracted_link_only_arr[3] = [ '', -1 ]; // {{{SYNC-MKDW-MEDIA-LINKS-TITLE-CAN-MISS}}} ; if no title text, there are only 3 elements in the array: 0, 1 and 2
		} //end if
		//--
		$link_txt 	= (string) trim((string)($extracted_link_only_arr[1][0] ?? null));
		$link_title = (string) trim((string)($extracted_link_only_arr[3][0] ?? null));
		$link_href 	= (string) trim((string)($extracted_link_only_arr[2][0] ?? null));
		//--
		$link_title = (string) trim((string)trim((string)$link_title, '"\'')); // remove trailing quotes
		//--
		$attributes = (array) $this->parseElementAttributes((string)$link_or_media_md_part, 'a');
		$atts = (array) $attributes['element:atts'];
		$attributes = null;
		//--
		if((string)$link_href == '') {
			return null; // invalid link ; the link href is empty
		} //end if
		//--
		if((string)$link_href == '#') { // anchor
			if((string)$link_txt == '') {
				if(isset($atts['id'])) {
					$link_txt = (string) trim((string)$atts['id']);
				} //end if
			} //end if
			if((string)$link_txt != '') {
				$link_txt = (string) Smart::create_htmid((string)$link_txt);
				if((string)$link_txt != '') {
					return '<a href="#" id="'.Smart::escape_html((string)$link_txt).'" style="visibility:hidden;"></a>';
				} //end if
			} //end if
			return null; // invalid anchor ; the link id is empty
		} //end if
		//--
		if((string)$link_txt == '') {
			return null; // invalid link ; the link href is empty
		} //end if
		//--
		$link_html_txt = '';
		if(strpos((string)$link_txt, '![') === 0) { // {{{SYNC-MKDW-DETECT-MEDIA-START}}}
			$link_html_txt = (string) $this->renderLinksAndMedia((string)$link_txt, true); // circular reference protection ; disable detect links inside links !
		} else {
			$link_html_txt = (string) $link_txt;
			//== {{{SYNC-MKDW-RENDER-ENTITIES-AND-INLINE-FORMATTING}}}
			$link_html_txt = (string) strtr((string)$link_html_txt, (array)self::HTML_ENTITIES_REPLACEMENTS); // replace html entities with placeholders
			//- SKIP render line headings in this context
			$link_html_txt = (string) Smart::escape_html((string)$link_html_txt); // apply default escaping
			$link_html_txt = (string) strtr((string)$link_html_txt, (array)array_flip((array)self::HTML_ENTITIES_REPLACEMENTS)); // render back html entities
			$link_html_txt = (string) self::replaceInlineTextFormatting((string)$link_html_txt); // text formatting syntax
			//== #end sync
		} //end if
		//--
		if((string)$link_title == '=@.') {
			$link_title = (string) $link_txt; // unixman fix: if title is "=@." make the same as alt to avoid duplicating the same text in the markdown code
		} //end if
		//--
		$link_href = (string) $this->fixRelativeURL((string)$link_href);
		//--
		return '<a href="'.Smart::escape_html((string)$link_href).'" title="'.self::renderAltOrTitle((string)$link_title).'"'.$this->buildAttributeData((array)$atts).'>'.$link_html_txt.'</a>';
		//--
	} //END FUNCTION


	private function renderLinksAndMedia(?string $text, bool $nolinks=false) : string {
		//--
		$text = (string) $text; // this expects to be the extracted string part by PATTERN_LINK_AND_MEDIA
		//--
		$trimmed_txt = (string) trim((string)$text);
		if((string)$trimmed_txt == '') {
			return (string) Smart::escape_html((string)$text); // empty string or just spaces ; escape for safety
		} //end if
		//--
		if($nolinks === true) { // circular reference protection
			$is_link = false;
		} else {
			$is_link = (bool) (strpos((string)$trimmed_txt, '[') === 0);  // {{{SYNC-MKDW-DETECT-LINK-START}}} ; expects: [anchor-id](#) ; [](#){L: #anchor-id} ; [](#){L: @id=anchor-id} ; [Text](http://url.link) ; [Text](http://url.link "Title goes here...") {L: .ux-button #the-id} ; [![Alternate Text](wpub/path-to/image.svg.gif.png.jpg.webp "Image Title")](http://url.link) {I:@width=256 @height=256} {L:@data-slimbox=slimbox} ; [![Alternate Text](wpub/path-to/image.svg.gif.png.jpg.webp "Image Title"){I:@width=256 @height=256}](http://url.link){L:@data-slimbox=slimbox}
		} //end if else
		$is_media = (bool) (strpos((string)$trimmed_txt, '![') === 0); // {{{SYNC-MKDW-DETECT-MEDIA-START}}}  ; expects: ![Alternate Text](wpub/path-to/image.svg.gif.png.jpg.webp "Image Title") {I:@width=256 @height=256}
		//--
		$trimmed_txt = null; // free mem
		//--
		$arr = [];
		//--
		if($is_link === true) { // is link
			$arr = (array) $this->getInlineLink((string)$text);
		} elseif($is_media === true) { // is media
			$arr  = (array) $this->getInlineMedia((string)$text);
		} else {
			return (string) Smart::escape_html((string)$text); // not link, not media
		} //end if else
		//--
		if((int)Smart::array_size($arr) < 3) { // {{{SYNC-MKDW-MEDIA-LINKS-TITLE-CAN-MISS}}} ; if no title text, there are only 3 elements in the array: 0, 1 and 2
			return (string) Smart::escape_html((string)$text); // something wrong, regex did not found a valid structure ...
		} //end if
		for($i=0; $i<Smart::array_size($arr); $i++) {
			if(!is_array($arr[$i])) {
				return (string) Smart::escape_html((string)$text); // something wrong, regex did not found a valid structure ...
			} //end if
		} //end for
		//-- # end sync
		if(!array_key_exists(0, (array)$arr[0])) {
			return (string) Smart::escape_html((string)$text); // something wrong, invalid return match array ...
		} //end if
		//--
		$trimmed_txt = (string) trim((string)$arr[0][0]);
		if((string)$trimmed_txt == '') {
			return (string) Smart::escape_html((string)$text); // empty string or just spaces ; escape for safety
		} //end if
		//--
		$is_link  = (bool) (strpos((string)$trimmed_txt, '[') === 0);
		$is_media = (bool) (strpos((string)$trimmed_txt, '![') === 0);
		//--
		$trimmed_txt = null; // free mem
		//--
		if($is_media === true) { // is media ; process first because links can also contain media
			//--
			$rendered_html = $this->renderHtmlMediaOnly((array)$arr, (string)$text); // do not cast ; it is mixed ; can be null if the media was wrong or string if rendered
			//--
			if($rendered_html === null) {
				return (string) Smart::escape_html((string)$text); // invalid media ; could not render the media html code
			} //end if
			//--
			return (string) $rendered_html;
			//--
		} elseif($is_link === true) { // is link ; process second ; links can contain also media
			//--
			$rendered_html = $this->renderHtmlLinkOnly((array)$arr, (string)$text); // do not cast ; it is mixed ; can be null if the media was wrong or string if rendered
			//--
			if($rendered_html === null) {
				return (string) Smart::escape_html((string)$text); // invalid media ; could not render the media html code
			} //end if
			//--
			return (string) $rendered_html;
			//--
		} else {
			return (string) Smart::escape_html((string)$text); // something wrong, the code in this method is missing some verifications above ...
		} //end if
		//--
		return (string) Smart::escape_html((string)$text); // unknown error ; escape for safety
		//--
	} //END FUNCTION


	private function renderLineHeadings(?string $line_crr) : array {
		//--
		$renderr = [
			'crr'  		=> $line_crr, 	// do not cast ; ?string
			'unparsed' 	=> true, 		// boolean ; set to false if $line_crr was modified
		];
		//--
		if(strpos((string)$line_crr, '#') !== 0) { // {{{SYNC-MKDW-HEADERS-LINE-DETECT}}}
			return (array) $renderr; // not a heading line
		} //end if
		//--
		$line_is_unparsed = true;
		//--
		$level = (int) strspn((string)$line_crr, '#', 0, 10); // fix by unixman ; find up to 10 levels of #, we only use 8
		if(((int)$level >= 1) AND ((int)$level <= 6)) { // h1..h6
			if(strpos((string)$line_crr, '# ') === (int)((int)$level-1)) { // h1..h6
				$line_crr = (string) $this->createHtmlInline((string)substr((string)$line_crr, (int)((int)$level+1)), 'h'.(int)$level, true); // avoid circular reference between createHtmlInline and this (renderLineHeadings) as renderLineHeadings is called inside createHtmlInline thus must explicit set last param to true, just in case ... anyway there is a double control !
				$line_is_unparsed = false;
			} //end if
		} elseif((int)$level == 7) { // span
			if(strpos((string)$line_crr, '# ') === (int)((int)$level-1)) { // h7:span
				$line_crr = (string) $this->createHtmlInline((string)substr((string)$line_crr, (int)((int)$level+1)), 'span', true); // avoid circular reference between createHtmlInline and this (renderLineHeadings) as renderLineHeadings is called inside createHtmlInline thus must explicit set last param to true, just in case ... anyway there is a double control !
				$line_is_unparsed = false;
			} //end if
		} elseif((int)$level == 8) { // dfn
			if(strpos((string)$line_crr, '# ') === (int)((int)$level-1)) { // h8:dfn
				$line_crr = (string) $this->createHtmlInline((string)substr((string)$line_crr, (int)((int)$level+1)), 'dfn', true); // avoid circular reference between createHtmlInline and this (renderLineHeadings) as renderLineHeadings is called inside createHtmlInline thus must explicit set last param to true, just in case ... anyway there is a double control !
				$line_is_unparsed = false;
			} //end if
		} //end if
		//--
		$renderr = [
			'crr'  		=> $line_crr, 					// do not cast ; ?string
			'unparsed' 	=> (bool) $line_is_unparsed, 	// boolean ; set to false if $line_crr was modified
		];
		//--
		return (array) $renderr;
		//--
	} //END FUNCTION


	private function renderLineDefault(?string $line_crr, ?string $line_next) : array {
		//--
		$renderr = [
			'crr'  => $line_crr, 	// do not cast ; ?string
			'next' => false, 		// do not cast ; ?string | false ; set to false if not modified
		];
		if((string)trim((string)$line_crr) == '') {
			return (array) $renderr; // empty
		} //end if
		//-- check special markers
		if(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/') === 0) {
			if( // {{{SYNC-MKDW-SPECIAL-BLOCK-TYPES}}}
				(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/code/') === 0)
				OR
				(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/pre/') === 0)
				OR
				(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/blockquote/') === 0)
			) {
				return (array) $renderr; // skip these lines, they are post-render markers: code, pre, blockquote
		//	} elseif(
		//		(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/inline-links-and-media/') === 0)
		//		OR
		//		(strpos((string)$line_crr, (string)self::SPECIAL_CHAR_ENTRY_MARK.'/%/inline-code/') === 0)
		//	) {
		//		$renderr['crr'] = (string) $this->createHtmlInline((string)$line_crr, 'p');
		//		return (array) $renderr; // skip these lines, they need to be rendered here: inline-links-and-media, inline-code
			} //end if else
		} //end if
		//-- OLD Style Headings, alt headers: h1, h2
		if($line_next) {
			if(((strpos((string)$line_next, '======') === 0)) AND ((string)rtrim((string)trim((string)$line_next, '=')) == '')) { // at least 6 chars as =, but only these
				$renderr['crr'] = (string) $this->createHtmlInline((string)$line_crr, 'h1'); // no need for the 3rd param to explicit set to TRUE, here there is no circular reference since these are alt headers
				$renderr['next'] = null; // clear next line
				return (array) $renderr;
			} elseif(((strpos((string)$line_next, '------') === 0)) AND ((string)rtrim((string)trim((string)$line_next, '-')) == '')) { // at least 6 chars as -, but only these
				$renderr['crr'] = (string) $this->createHtmlInline((string)$line_crr, 'h2'); // no need for the 3rd param to explicit set to TRUE, here there is no circular reference since these are alt headers
				$renderr['next'] = null; // clear next line
				return (array) $renderr;
			} //end if else
		} //end if else
		//--
		$renderr['crr'] = (string) $this->createHtmlInline((string)$line_crr, 'p');
		//--
		return (array) $renderr;
		//--
	} //END FUNCTION


	private function renderDocument(?string $text) : string {
		//--
		$this->initDefinitionData(true); // init, clear
		//--
		$text = "\n".$text."\n"; // required for pattern matching and flushing of last line data buffered previous
		//-- pre render syntax mtpl: extract marker tpl syntax, to preserve it for post rendering
		$this->DefinitionData['extracted']['syntax-mtpl'] = []; // init
		$mtpl_syntax = (array) SmartMarkersTemplating::extract_tpl_syntax((string)$text);
		foreach($mtpl_syntax as $key => $val) {
			if(is_array($val)) {
				if((int)Smart::array_type_test($val) == 1) { // {{{SYNC-MKDW-CHECK-SYNTAX-NON-ASSOC}}}
					for($i=0; $i<Smart::array_size($val); $i++) {
						$this->DefinitionData['extracted']['syntax-mtpl'][] = (string) $val[$i];
					} //end for
				} //end if
			} //end if
		} //end foreach
		$mtpl_syntax = null;
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'syntax-mtpl', (array)$this->DefinitionData['extracted']['syntax-mtpl']);
		//-- pre render syntax extra
		$this->DefinitionData['extracted']['syntax-extra'] = []; // init
		if((int)Smart::array_type_test($this->arr_preserve_extra_syntax) == 1) { // {{{SYNC-MKDW-CHECK-SYNTAX-NON-ASSOC}}}
			$this->DefinitionData['extracted']['syntax-extra'] = (array) $this->arr_preserve_extra_syntax;
		} //end if
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'syntax-extra', (array)$this->DefinitionData['extracted']['syntax-extra']);
		//-- 1st extract+convert classic quoted blocks into v2 syntax quoted blocks by only enclosing them between <<<\n<<<
		if($this->useCompatibilityMode) {
			$this->DefinitionData['extracted']['blockquote'] = (array) $this->getDataBlockQuoteds((string)$text);
			$text = (string) $this->getTextWithPlaceholders((string)$text, 'blockquote', (array)$this->DefinitionData['extracted']['blockquote']);
			$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'blockquote');
			$this->DefinitionData['extracted']['blockquote'] = null; // discard
		} //end if
		//-- 2nd extract code blocks to be preserved and replace them with placeholders
		$this->DefinitionData['extracted']['code'] = (array) $this->getDataBlockCodes((string)$text);
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'code', (array)$this->DefinitionData['extracted']['code']);
		//-- 3rd extract links, links with media, media ; after extract+convert code blocks, after extracting code blocks and inline code, but prior to extract pre-formats ; pre-formats may contain media
		// MUST BE BEFORE INLINE CODE to avoid rendering a portion of an media or link title that contains ```code``` as code {{{SYNC-MKDW-INLINE-CODE-VS-LINKS-MEDIA-ORDER}}}
		$this->DefinitionData['extracted']['inline-links-and-media'] = (array) $this->getDataInlineLinksAndMedia((string)$text);
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'inline-links-and-media', (array)$this->DefinitionData['extracted']['inline-links-and-media']);
		//-- 4th extract inline code to be preserved and replace them with placeholders !!! keep before pre, pre may contain code !!!
		// MUST BE AFTER INLINE LINKS AND MEDIA to avoid rendering a portion of an media or link title that contains ```code``` as code {{{SYNC-MKDW-INLINE-CODE-VS-LINKS-MEDIA-ORDER}}}
		$this->DefinitionData['extracted']['inline-code'] = (array) $this->getDataInlineCodes((string)$text);
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'inline-code', (array)$this->DefinitionData['extracted']['inline-code']);
		//-- 5th extract pre blocks to be preserved and replace them with placeholders
		$this->DefinitionData['extracted']['mpre'] = (array) $this->getDataBlockMPreformats((string)$text);
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'mpre', (array)$this->DefinitionData['extracted']['mpre']);
		$this->DefinitionData['extracted']['pre'] = (array) $this->getDataBlockPreformats((string)$text);
		$text = (string) $this->getTextWithPlaceholders((string)$text, 'pre', (array)$this->DefinitionData['extracted']['pre']);
		//-- 6th process line by line
		$arr = (array) explode("\n", (string)$text);
		$text = '';
		//--
		$is_blockquote = false;
		$is_div = false;
		$is_sdiv = false;
		$is_section = false;
		$is_article = false;
		$def_lists = null;
		$def_table = null;
		//--
		for($i=0; $i<Smart::array_size($arr); $i++) {
			//--
			$is_list = false;
			//--
			$line_is_unparsed = true;
			$line_next = null;
			$line_last = false; // not last line
			if(array_key_exists((int)($i+1), $arr)) {
				$line_next = $arr[$i+1]; // ?string
			} else {
				$line_last = true; // it is the last line
			} //end if
			//--
			if($arr[$i] === null) {
				//--
				// skip explicit null lines, they are by default string and can not be null except if set so
				//--
			} elseif((string)trim((string)$arr[$i]) == '\\') {
				//--
				if($this->sBreakEnabled) {
					$arr[$i] = '<br>';
				} else {
					$arr[$i] = '';
				} //end if else
				//--
			} else {
			//======= Empty or Spaces Only Line
				//--
				$match_list_ul_entry = array();
				$match_list_ol_entry = array();
				//--
				if(
					($line_last === true) // if last line
					OR
					((string)trim((string)$arr[$i]) == '') // or an empty line
				) {
					if($def_lists !== null) {
						$arr[$i] = (string) $this->convertListArrToHtml($def_lists)."\n"; // close the list
						$line_is_unparsed = false;
						$def_lists = null; // fix: reset it on each line that is not a list
					} //end if
				} //end if
				//--
				if(strpos((string)$arr[$i], '|') !== 0) { // table ; {{{SYNC-MKWD-CONDITION-TABLE-LINE}}}
					$def_table = null; // fix: reset it on each line that is not a table
				} //end if
				//--
				if($line_is_unparsed !== true) {
					//--
					// skip, already rendered above
					//--
				} elseif((string)trim((string)$arr[$i]) == '') { // {{{SYNC-MKWD-EMPTY-LINE}}} empty or spaces only line ; used to reset some parsing data
					//-- br (must be at the end, it checks if the line is still empty after above flushes
					if(
						((string)trim((string)$line_next) == '')
						AND
						($line_last === false)
						AND
						((string)trim((string)$arr[$i]) == '')
					) {
						$arr[$i] = (string) '<br>'."\n";
						$line_is_unparsed = false;
					} //end if
					//--
			//======= Blockquote
				} elseif(strpos((string)$arr[$i], '<<<') === 0) { // blockquote
					//--
					if($is_blockquote === true) { // close blockquote
						//--
						$is_blockquote = false;
						//--
						$arr[$i] = '</blockquote>'.'<br>'."\n"; // {{{SYNC-MKDW-ENDTAG-BLOCKQUOTE}}}
						$line_is_unparsed = false;
						//--
					} else { // open blockquote
						//--
						$is_blockquote = true;
						//--
						$blockquote_atts = (array) $this->parseAttributeData('blockquote', (string)ltrim((string)$arr[$i], '<'));
						//--
						$arr[$i] = '<blockquote'.(isset($blockquote_atts['id']) ? ' id="'.Smart::escape_html((string)$blockquote_atts['id']).'"' : '').(isset($blockquote_atts['class']) ? ' class="'.Smart::escape_html((string)$blockquote_atts['class']).'"' : '').$this->buildAttributeData((array)$blockquote_atts, [ 'id' => false, 'class' => false ]).'>'; // do not parse inline: id, class
						$line_is_unparsed = false;
						//--
						$blockquote_atts = null;
						//--
					} //end if else
					//--
			//======= Div
				} elseif((strpos((string)$arr[$i], ':::') === 0) AND ((string)substr((string)$arr[$i], 0, 4) != '::::')) { // div
					//--
					if($is_div === true) { // close div
						//--
						$is_div = false;
						//--
						$arr[$i] = '</div>'."\n"; // {{{SYNC-MKDW-ENDTAG-DIV}}}
						$line_is_unparsed = false;
						//--
					} else { // open div
						//--
						$is_div = true;
						//--
						$div_atts = (array) $this->parseAttributeData('div', (string)ltrim((string)$arr[$i], ':'));
						//--
						$arr[$i] = '<div'.(isset($div_atts['id']) ? ' id="'.Smart::escape_html((string)$div_atts['id']).'"' : '').(isset($div_atts['class']) ? ' class="'.Smart::escape_html((string)$div_atts['class']).'"' : '').$this->buildAttributeData((array)$div_atts, [ 'id' => false, 'class' => false ]).'>'; // do not parse inline: id, class
						$line_is_unparsed = false;
						//--
						$div_atts = null;
						//--
					} //end if else
					//--
			//======= Sub-Div (it is needed to allow insert a div in another div because in markdown elements can't be nested if they are the same type)
				} elseif(strpos((string)$arr[$i], '::::') === 0) {
					//--
					if($is_sdiv === true) { // close sub-div
						//--
						$is_sdiv = false;
						//--
						$arr[$i] = '</div><!-- /sdiv -->'."\n"; // {{{SYNC-MKDW-ENDTAG-SUBDIV}}}
						$line_is_unparsed = false;
						//--
					} else { // open sub-div
						//--
						$is_sdiv = true;
						//--
						$sdiv_atts = (array) $this->parseAttributeData('div', (string)ltrim((string)$arr[$i], ':'));
						//--
						$arr[$i] = '<!-- sdiv --><div'.(isset($sdiv_atts['id']) ? ' id="'.Smart::escape_html((string)$sdiv_atts['id']).'"' : '').(isset($sdiv_atts['class']) ? ' class="'.Smart::escape_html((string)$sdiv_atts['class']).'"' : '').$this->buildAttributeData((array)$sdiv_atts, [ 'id' => false, 'class' => false ]).'>'; // do not parse inline: id, class
						$line_is_unparsed = false;
						//--
						$sdiv_atts = null;
						//--
					} //end if else
					//--
			//======= Section
				} elseif((strpos((string)$arr[$i], ';;;') === 0) AND ((string)substr((string)$arr[$i], 0, 4) != ';;;;')) { // section
					//--
					if($is_section === true) { // close section
						//--
						$is_section = false;
						//--
						$arr[$i] = '</section>'."\n"; // {{{SYNC-MKDW-ENDTAG-SECTION}}}
						$line_is_unparsed = false;
						//--
					} else { // open section
						//--
						$is_section = true;
						//--
						$sect_atts = (array) $this->parseAttributeData('section', (string)ltrim((string)$arr[$i], ';'));
						//--
						$arr[$i] = '<section'.(isset($sect_atts['id']) ? ' id="'.Smart::escape_html((string)$sect_atts['id']).'"' : '').(isset($sect_atts['class']) ? ' class="'.Smart::escape_html((string)$sect_atts['class']).'"' : '').$this->buildAttributeData((array)$sect_atts, [ 'id' => false, 'class' => false ]).'>'; // do not parse inline: id, class
						$line_is_unparsed = false;
						//--
						$sect_atts = null;
						//--
					} //end if else
					//--
			//======= Article
				} elseif(strpos((string)$arr[$i], ';;;;') === 0) { // article
					//--
					if($is_article === true) { // close article
						//--
						$is_article = false;
						//--
						$arr[$i] = '</article>'."\n"; // {{{SYNC-MKDW-ENDTAG-ARTICLE}}}
						$line_is_unparsed = false;
						//--
					} else { // open article
						//--
						$is_article = true;
						//--
						$art_atts = (array) $this->parseAttributeData('article', (string)ltrim((string)$arr[$i], ';'));
						//--
						$arr[$i] = '<article'.(isset($art_atts['id']) ? ' id="'.Smart::escape_html((string)$art_atts['id']).'"' : '').(isset($art_atts['class']) ? ' class="'.Smart::escape_html((string)$art_atts['class']).'"' : '').$this->buildAttributeData((array)$art_atts, [ 'id' => false, 'class' => false ]).'>'; // do not parse inline: id, class
						$line_is_unparsed = false;
						//--
						$art_atts = null;
						//--
					} //end if else
					//--
			//======= Horizontal Rule # need to be detected before lists !!
				} elseif(in_array((string)substr((string)$arr[$i], 0, 5), [ '- - -', '* * *' ])) { // hr
					//--
					$arr[$i] = '<hr>'."\n";
					$line_is_unparsed = false;
					//--
				} elseif(in_array((string)substr((string)$arr[$i], 0, 3), [ '---', '***', '___' ])) { // hr ; support v1
					//--
					$arr[$i] = '<hr>'."\n";
					$line_is_unparsed = false;
					//--
			//======= Lists ul / ol
				} elseif(
					preg_match((string)self::PATTERN_LIST_UL, (string)$arr[$i], $match_list_ul_entry)
					OR
					preg_match((string)self::PATTERN_LIST_OL, (string)$arr[$i], $match_list_ol_entry)
				) { // lists: ul / ol ; {{{SYNC-MKWD-CONDITION-LIST-LINE}}}
					//--
					$list_type = '';
					$list_level = -1;
					$list_code = '';
					//-- max 8 levels // {{{SYNC-MKDW-LISTS-MAX-LEVELS}}}
					if((int)Smart::array_size($match_list_ul_entry) > 0) {
						$is_list = true;
						$list_type = 'ul';
						$list_level = (int) $this->getListEntryLevelByLeadingSpaces((string)($match_list_ul_entry[1] ?? ''));
						$list_code = (string) ltrim((string)substr((string)ltrim((string)$arr[$i]), 1));
					} elseif((int)Smart::array_size($match_list_ol_entry) > 0) {
						$is_list = true;
						$list_type = 'ol';
						$list_level = (int) $this->getListEntryLevelByLeadingSpaces((string)($match_list_ol_entry[1] ?? ''));
						$list_code = (string) ltrim((string)preg_replace('/^[0-9]+[\.\)]{1}/', '', (string)ltrim((string)$arr[$i]), 1));
					} //end if
					//--
					if(($is_list === true) AND ((string)$list_type != '') AND ((int)$list_level >= 0)) {
						//--
						$def_lists[] = [
							'level' => (int)    $list_level,
							'type' 	=> (string) $list_type,
							'code' 	=> (string) $list_code,
							'extra' => (array)  [],
						];
						//--
						$arr[$i] = null; // avoid display now, will be done later
						$line_is_unparsed = false;
						//--
					} // end if
					//--
			//======= Table
				} elseif(strpos((string)$arr[$i], '|') === 0) { // table ; {{{SYNC-MKWD-CONDITION-TABLE-LINE}}}
					//--
					$arr[$i] = (string) str_replace('\\|', self::SPECIAL_CHAR_TBL_SEP_MARK, (string)$arr[$i]); // {{{SYNC-MKDW-TABLE-CELL-VBAR-FIX}}} ; fix: if a cell have to contain a vertical bar, make a special replacement
					//--
					$cells = (array) explode('|', (string)$arr[$i]);
					$paligns = [];
					$aligns = [];
					$mcells = (int) ((int)Smart::array_size($cells) - 2); // is is 1st line, use real
					$tbl_line_discarded = false;
					//--
					if($def_table !== null) {
						if((int)$def_table['cells'] < (int)$mcells) {
							$def_table['cells'] = (int) $mcells; // fix back, cells number is larger than previous
						} else {
							$mcells = (int) $def_table['cells']; // use the max cells from defs, 1st line
						} //end if else
					} //end if else
					if((int)$mcells > 0) {
						if($def_table === null) {
							if($line_last === false) { // look ahead for table aligns
								if(strpos((string)$line_next, '|') === 0) { // table align defs
									$aligns = (array) explode('|', (string)$line_next);
									if((int)(Smart::array_size($aligns)-2) >= 0) {
										$pa = 0;
										for($a=1; $a<Smart::array_size($aligns)-1; $a++) {
											$aligns[$a] = (string) trim((string)$aligns[$a]);
											if((string)trim((string)$aligns[$a], ':-') == '') {
												$paligns[$pa] = '';
												if(strpos((string)$aligns[$a], ':-') === 0) {
													if((string)$paligns[$pa] == '') {
														$paligns[$pa] = 'left';
													} else {
														$paligns[$pa] = 'center';
													} //end if else
												} //end if
												if((string)substr((string)$aligns[$a], -2, 2) == '-:') {
													if((string)$paligns[$pa] == '') {
														$paligns[$pa] = 'right';
													} else {
														$paligns[$pa] = 'center';
													} //end if else
												} //end if
											} elseif((string)trim((string)$aligns[$a], '-') == '') {
												$paligns[$pa] = '';
											} else { // error, invalid aligns
												$paligns = [];
												break;
											} //end if else
											$pa++;
										} //end for
									} //end if
								} //end if
								if(Smart::array_size($paligns) > 0) {
									$arr[$i+1] = null; // discard the 2nd table line with aligns ; it must exists, above is tested as should not be the last line
									$line_next = null; // bugfix: if the last table row is the one with aligns because this line was missing in the past it was not closing the table ! it is logic that if the above line is reset also this test line which is the reference of next line should reset as this is tested in a table before the alignements line and will not impact other things !
									$tbl_line_discarded = true; // bugfix: {{{SYNC-MKWD-CONDITION-TABLE-LINE}}}
								} //end if
							} //end if
						} else {
							$paligns = (array) $def_table['aligns'];
							$arr[$i] = "\t".'<tr>'."\n";
							$line_is_unparsed = false;
						} //end if
						$is_tbl_init = (bool) ($def_table !== null);
						$is_tbl_full_width = true;
						$align_tbl_head = '';
						$tbl_head_use_td = false;
						$harr = [];
						for($c=1; $c<Smart::array_size($cells)-1; $c++) {
							$cells[$c] = (string) str_replace(self::SPECIAL_CHAR_TBL_SEP_MARK, '|', (string)$cells[$c]); // {{{SYNC-MKDW-TABLE-CELL-VBAR-FIX}}} ; fix back
							if($def_table === null) {
								if((int)$c == 1) {
									$is_tbl_init = true;
									$harr = (array) $this->parseTableAttributes((string)$cells[$c]);
									$cells[$c] = (string) $harr['hcell:text'];
									$tblclasses = [];
									$tblid = '';
									for($hd=0; $hd<Smart::array_size($harr['table:defs']); $hd++) {
										if(strpos((string)$harr['table:defs'][$hd], '.') === 0) { // table classes
											$tmp_tbl_class = (string) str_replace(['.', '#'], '', (string)$harr['table:defs'][$hd]);
											if(!in_array((string)$tmp_tbl_class, (array)$tblclasses)) {
												$tblclasses[] = (string) $tmp_tbl_class;
											} //end if
										} elseif(strpos((string)$harr['table:defs'][$hd], '#') === 0) { // table id
											$tblid = (string) str_replace(['.', '#'], '', (string)$harr['table:defs'][$hd]);
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'AUTO-WIDTH') {
											$is_tbl_full_width = false;
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'ALIGN-HEAD-LEFT') {
											$align_tbl_head = 'left';
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'ALIGN-HEAD-CENTER') {
											$align_tbl_head = 'center';
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'ALIGN-HEAD-RIGHT') {
											$align_tbl_head = 'right';
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'ALIGN-HEAD-AUTO') { // if numeric, will align to right otherwise to left
											if(is_numeric((string)trim((string)$harr['hcell:text']))) {
												$align_tbl_head = 'right';
											} else {
												$align_tbl_head = 'center';
											} //end if else
										} elseif((string)strtoupper((string)$harr['table:defs'][$hd]) == 'NO-TABLE-HEAD') {
											$tbl_head_use_td = true;
										} //end if else
									} //end for
									if($is_tbl_full_width !== false) { // by default tables are full width
										if(!in_array('full-width-table', (array)$tblclasses)) {
											$tblclasses[] = 'full-width-table';
										} //end if
									} //end if
									$arr[$i] = '<table'.($tblid ? ' id="'.Smart::escape_html((string)$tblid).'"' : '').($tblclasses ? ' class="'.Smart::escape_html((string)implode(' ', $tblclasses)).'"' : '').'>'."\n"."\t".'<tr>'."\n"; // ids and classes must not be parsed inline
									$line_is_unparsed = false;
									$tblid = '';
									$tblclasses = [];
								} //end if
							} //end if
							$carr = (array) $this->parseElementAttributes((string)$cells[$c], 'td');
							$cell_elem = 'td';
							if($def_table === null) {
								if($tbl_head_use_td !== true) {
									$cell_elem = 'th';
								} //end if
							} //end if
							$cell_align = '';
							if((string)$align_tbl_head != '') {
								$cell_align = (string) $align_tbl_head;
							} else {
								$cell_align = (string) ((isset($paligns[$c-1]) && $paligns[$c-1]) ? $paligns[$c-1] : '');
							} //end if else
						//	if((string)$cells[$c] != '') { // bugfix (realm=javascript&key=3) it appears that also with empty cell and colspans the cell must be rendered ...  ; previous assumption was: if cell is empty, that is intentional to solve the issue with collspans, so do not render that cell
								$arr[$i] .= "\t"."\t".'<'.self::escapeValidHtmlTagName($cell_elem).$this->buildAttributeData((array)$carr['element:atts']).($cell_align ? ' style="text-align:'.Smart::escape_html((string)$cell_align).';"' : '').'>'.(trim((string)$carr['element:text']) ? $this->createHtmlInline((string)trim((string)$carr['element:text']), 'td') : '&nbsp;').'</'.self::escapeValidHtmlTagName($cell_elem).'>'."\n"; // do not parse inline attributes
								$line_is_unparsed = false;
						//	} //end if
							$carr = null;
							$cell_align = null;
						} //end for
						$tbl_head_use_td = null;
						$align_tbl_head = null;
						//--
						if($is_tbl_init === true) {
							//--
							$arr[$i] .= "\t".'</tr>'."\n";
							$line_is_unparsed = false;
							//--
							if($def_table === null) { // table can be init but def table null at this point, if first line, thus export settings for next loops
								//--
								$def_table = [ // init here, above next if, and do not unify with an else, must go separately, with a separate condition
									'line' 		=> (int)   $i,
									'cells' 	=> (int)   $mcells,
									'rows' 		=> (int)   1,
									'aligns' 	=> (array) $paligns,
									'defs' 		=> (array) ($harr['table:defs'] ?? []),
								];
								//--
							} //end if
							//--
							$def_table['rows']++;
							//--
							if(
								(($line_last === true))
								OR
								(($tbl_line_discarded === true) AND isset($arr[$i+2]) AND (strpos((string)$arr[$i+2], '|') !== 0)) // {{{SYNC-MKWD-CONDITION-TABLE-LINE}}}
								OR
								(($tbl_line_discarded !== true) AND (strpos((string)$line_next, '|') !== 0)) // {{{SYNC-MKWD-CONDITION-TABLE-LINE}}}
							) {
								$arr[$i] .= '</table>'."\n"; // must close table here if next line is not part of a table to avoid collide with other elements ex: blockquotes
								$line_is_unparsed = false;
								//$arr[$i] .= "\n".'<pre>'.print_r($def_table,1).'</pre>'; // DEBUG: print table data
								$def_table = null; // reset
							} //end if
							//--
						} //end if
						//--
						$harr = null;
						$is_tbl_init = null;
						$is_tbl_full_width = null;
						//--
					} //end if else
					//--
					$cells = null;
					$mcells = 0;
					$paligns = null;
					$aligns = null;
					$tbl_line_discarded = null;
					//--
				} //end if else (end table)
				//-- DEFAULT ; OTHER CASES: special markers: keep as they are ; parse alt headers and reset below line ; for the rest, apply html escape + parse inline
				if($line_is_unparsed === true) {
					$renderr = (array) $this->renderLineDefault($arr[$i], $line_next);
					$arr[$i] = ($renderr['crr'] === null) ? null : (string)$renderr['crr']; // do not cast
					$line_is_unparsed = false;
					if($line_last === false) {
						if($renderr['next'] !== false) { // avoid rewrite next line if not modified
							$arr[$i+1] = ($renderr['next'] === null) ? null : (string)$renderr['next']; // do not cast
						} //end if
					} //end if
					$renderr = null;
					//--
				} //end if
				//--
			} //end if else
			//--
			if($arr[$i] !== null) {
				if($def_lists !== null) {
					if($is_list !== true) { // for the case not a list but considered inside a list until first empty line
						if((string)trim((string)$arr[$i]) != '') {
							$max_def_lists = (int) Smart::array_size($def_lists);
							if((int)$max_def_lists > 0) {
								$max_def_lists -= 1;
								if(!is_array($def_lists[(int)$max_def_lists]['extra'])) {
									$def_lists[(int)$max_def_lists]['extra'] = [];
								} //end if
								$def_lists[(int)$max_def_lists]['extra'][] = (string) $arr[$i];
								$arr[$i] = null; // avoid display now
								$line_is_unparsed = false;
							} //end if
						} //end if else
					} //end if else
					$arr[$i] = null; // avoid display now
					$line_is_unparsed = false;
				} //end if
			} //end if
			//--
			if($arr[$i] !== null) { // must use a separate check for null than above !
				$text .= (string) $arr[$i]; // add only non-null lines
			} //end if
			//--
		} //end for
		//-- close unclosed (by editor's omission) tags
		if($is_blockquote === true) {
			$is_blockquote = false;
			$text .= '</blockquote>'.'<br>'."\n"; // {{{SYNC-MKDW-ENDTAG-BLOCKQUOTE}}}
			self::notice_log((string)__METHOD__, ' # Unclosed tag found: BLOCKQUOTE <<<');
		} //end if
		if($is_div === true) {
			$is_div = false;
			$text .= '</div>'."\n"; // {{{SYNC-MKDW-ENDTAG-DIV}}}
			self::notice_log((string)__METHOD__, ' # Unclosed tag found: DIV :::');
		} //end if
		if($is_sdiv === true) {
			$is_sdiv = false;
			$text .= '</div><!-- /sdiv -->'."\n"; // {{{SYNC-MKDW-ENDTAG-SUBDIV}}}
			self::notice_log((string)__METHOD__, ' # Unclosed tag found: DIV.SUB ::::');
		} //end if
		if($is_section === true) {
			$is_section = false;
			$text .= '</section>'."\n"; // {{{SYNC-MKDW-ENDTAG-SECTION}}}
			self::notice_log((string)__METHOD__, ' # Unclosed tag found: SECTION ;;;');
		} //end if
		if($is_article === true) {
			$is_article = false;
			$text .= '</article>'."\n"; // {{{SYNC-MKDW-ENDTAG-ARTICLE}}}
			self::notice_log((string)__METHOD__, ' # Unclosed tag found: ARTICLE ;;;;');
		} //end if
		//--
		$arr = null;
		//--
//return (string) '<pre>'.Smart::escape_html((string)print_r($this->DefinitionData,1)).'</pre>'; // DEBUG
		//-- 7th fix escapings, before render blocks !
		$text = (string) $this->fixEscapings((string)$text);
		//-- 8th render back blocks
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'mpre');
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'pre');
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'inline-code'); 				// {{{SYNC-MKDW-INLINE-CODE-VS-LINKS-MEDIA-ORDER}}}
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'inline-links-and-media'); 	// {{{SYNC-MKDW-INLINE-CODE-VS-LINKS-MEDIA-ORDER}}}
		$text = (string) $this->fixDecodeUrlEncSyntax((string)$text);
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'code');
		//-- post render
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'syntax-extra');
		$text = (string) $this->setBackTextWithPlaceholders((string)$text, 'syntax-mtpl');
		//--
		if((stripos((string)$text, '<invalidtag') !== false) OR (stripos((string)$text, '</invalidtag') !== false)) { // {{{SYNC-MKDW-HTML-TAG-INVALID}}}
			self::notice_log((string)__METHOD__, ' # Invalid tags found ...');
		} //end if
		//--
		$this->initDefinitionData(true); // init, clear
		//--
//return (string) '<pre>'.Smart::escape_html((string)$text).'</pre>'; // DEBUG
		return (string) $text;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
