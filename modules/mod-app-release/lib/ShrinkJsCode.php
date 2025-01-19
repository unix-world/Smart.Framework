<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Strip JS Code
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION [T]
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// PHP8

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Shrink (Strip) Javascript Code
 * (c) 2021-present unix-world.org
 *
 * based on JavaScript Minifier Class v.1.3.63, license BSD
 * (c) 2012-2021 Matthias Mullie
 *
 * @access 		private
 * @internal
 *
 * @depends classes: ShrinkCode
 *
 */
final class ShrinkJsCode extends ShrinkCode {

	// ->
	// v.20250107


	private const PROPERTIESANDMETHODS = [ // 13 [ 8 + 5 ]
		// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/RegExp#Properties_2
		'constructor',
		'flags',
		'global',
		'ignoreCase',
		'multiline',
		'source',
		'sticky',
		'unicode',
		// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/RegExp#Methods_2
		'compile(',
		'exec(',
		'test(',
		'toSource(',
		'toString(',
	];

	private const KEYWORDS = [ // 9
		'do',
		'in',
		'new',
		'else',
		'throw',
		'yield',
		'delete',
		'return',
		'typeof'
	];

	private const KEYWORDSBEFORE = [ // 26
		'do',
		'in',
		'const',
		'let',
		'var',
		'new',
		'case',
		'else',
		'enum',
		'void',
		'with',
		'class',
		'yield',
		'delete',
		'export',
		'import',
		'public',
		'static',
		'typeof',
		'extends',
		'package',
		'private',
		'function',
		'protected',
		'implements',
		'instanceof',
	];

	private const KEYWORDSAFTER = [ // 7
		'in',
		'public',
		'extends',
		'private',
		'protected',
		'implements',
		'instanceof',
	];

	private const KEYWORDSRESERVED = [ // 63
		'do',
		'if',
		'in',
		'for',
		'let',
		'new',
		'try',
		'var',
		'case',
		'else',
		'enum',
		'eval',
		'null',
		'this',
		'true',
		'void',
		'with',
		'break',
		'catch',
		'class',
		'const',
		'false',
		'super',
		'throw',
		'while',
		'yield',
		'delete',
		'export',
		'import',
		'public',
		'return',
		'static',
		'switch',
		'typeof',
		'default',
		'extends',
		'finally',
		'package',
		'private',
		'continue',
		'debugger',
		'function',
		'arguments',
		'interface',
		'protected',
		'implements',
		'instanceof',
		'abstract',
		'boolean',
		'byte',
		'char',
		'double',
		'final',
		'float',
		'goto',
		'int',
		'long',
		'native',
		'short',
		'synchronized',
		'throws',
		'transient',
		'volatile',
	];

	private const OPERATORSBEFORE = [ // 43
		'+',
		'-',
		'*',
		'/',
		'%',
		'=',
		'+=',
		'-=',
		'*=',
		'/=',
		'%=',
		'<<=',
		'>>=',
		'>>>=',
		'&=',
		'^=',
		'|=',
		'&',
		'|',
		'^',
		'~',
		'<<',
		'>>',
		'>>>',
		'==',
		'===',
		'!=',
		'!==',
		'>',
		'<',
		'>=',
		'<=',
		'&&',
		'||',
		'!',
		'.',
		'[',
		'?',
		':',
		',',
		';',
		'(',
		'{',
	];

	private const OPERATORSAFTER = [ // 43
		'+',
		'-',
		'*',
		'/',
		'%',
		'=',
		'+=',
		'-=',
		'*=',
		'/=',
		'%=',
		'<<=',
		'>>=',
		'>>>=',
		'&=',
		'^=',
		'|=',
		'&',
		'|',
		'^',
		'<<',
		'>>',
		'>>>',
		'==',
		'===',
		'!=',
		'!==',
		'>',
		'<',
		'>=',
		'<=',
		'&&',
		'||',
		'.',
		'[',
		']',
		'?',
		':',
		',',
		';',
		'(',
		')',
		'}',
	];

	private const OPERATORS = [ // 46
		'+',
		'-',
		'*',
		'/',
		'%',
		'=',
		'+=',
		'-=',
		'*=',
		'/=',
		'%=',
		'<<=',
		'>>=',
		'>>>=',
		'&=',
		'^=',
		'|=',
		'&',
		'|',
		'^',
		'~',
		'<<',
		'>>',
		'>>>',
		'==',
		'===',
		'!=',
		'!==',
		'>',
		'<',
		'>=',
		'<=',
		'&&',
		'||',
		'!',
		'.',
		'[',
		']',
		'?',
		':',
		',',
		';',
		'(',
		')',
		'{',
		'}',
	];


	//====================================================
	// js strip
	protected function stripCustomCode() {
		//--
		$js = (string) $this->sourceCode;
		//-- prepare
		$this->extractStrings('\'"`'); // js
		$this->prepareStripJsComments(); // js
		$this->extractJsRegex(); // js
		//-- operate
		$js = (string) $this->operateReplacements((string)$js); // process
		$js = (string) $this->stripJsWhitespace((string)$js); // strip the whitespace, js
		//-- finalize
		$js = (string) $this->restoreExtractedData((string)$js); // restore extracted data by extract strings and regex
		//--
		return (string) trim((string)$js);
		//--
	} //END FUNCTION
	//====================================================


	//======= [ PRIVATES ]


	//====================================================
	private function prepareStripJsComments() {
		//-- multi-line comments
		$this->registerPattern('/\/\*.*?\*\//s', '');
		//-- single-line comments
	//	$this->registerPattern('/\/\/.*$/m', ''); // fails with weird characters after double slash comments ; ex: jquery.keyboard.extension-altkeyspopup.js
		$this->registerPattern('/\/\/.*$/mu', ''); // bug fix for above situation
	//	$this->registerPattern('/\s*(?:\/\/).*?$/mu', ''); // alternative
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * Strip JS whitespace
	 *
	 * We won't strip *all* whitespace, but as much as possible. The thing that
	 * we'll preserve are newlines we're unsure about.
	 * JavaScript doesn't require statements to be terminated with a semicolon.
	 * It will automatically fix missing semicolons with ASI (automatic semi-
	 * colon insertion) at the end of line causing errors (without semicolon.)
	 *
	 * Because it's sometimes hard to tell if a newline is part of a statement
	 * that should be terminated or not, we'll just leave some of them alone.
	 *
	 * @param string $content The content to strip the whitespace for
	 *
	 * @return string
	 */
	private function stripJsWhitespace(string $content) {
		// uniform line endings, make them all line feed
		$content = str_replace(["\r\n", "\r"], "\n", $content);
		// collapse all non-line feed whitespace into a single space
		$content = preg_replace('/[^\S\n]+/', ' ', $content);
		// strip leading & trailing whitespace
		$content = str_replace([" \n", "\n "], "\n", $content);
		// collapse consecutive line feeds into just 1
		$content = preg_replace('/\n+/', "\n", $content);
		//--
		$operatorsBefore = $this->getJsOperatorsForRegex((array)self::OPERATORSBEFORE, '/');
		$operatorsAfter = $this->getJsOperatorsForRegex((array)self::OPERATORSAFTER, '/');
		$operators = $this->getJsOperatorsForRegex((array)self::OPERATORS, '/');
		$keywordsBefore = $this->getJsKeywordsForRegex((array)self::KEYWORDSBEFORE, '/');
		$keywordsAfter = $this->getJsKeywordsForRegex((array)self::KEYWORDSAFTER, '/');
		//--
		// strip whitespace that ends in (or next line begin with) an operator
		// that allows statements to be broken up over multiple lines
		unset($operatorsBefore['+'], $operatorsBefore['-'], $operatorsAfter['+'], $operatorsAfter['-']);
		$content = preg_replace(
			[
				'/('.implode('|', $operatorsBefore).')\s+/',
				'/\s+('.implode('|', $operatorsAfter).')/',
			],
			'\\1',
			$content
		);
		// make sure + and - can't be mistaken for, or joined into ++ and --
		$content = preg_replace(
			[
				'/(?<![\+\-])\s*([\+\-])(?![\+\-])/',
				'/(?<![\+\-])([\+\-])\s*(?![\+\-])/',
			],
			'\\1',
			$content
		);
		// collapse whitespace around reserved words into single space
		$content = preg_replace('/(^|[;\}\s])\K('.implode('|', $keywordsBefore).')\s+/', '\\2 ', $content);
		$content = preg_replace('/\s+('.implode('|', $keywordsAfter).')(?=([;\{\s]|$))/', ' \\1', $content);
		/*
		 * We didn't strip whitespace after a couple of operators because they
		 * could be used in different contexts and we can't be sure it's ok to
		 * strip the newlines. However, we can safely strip any non-line feed
		 * whitespace that follows them.
		 */
		$operatorsDiffBefore = array_diff($operators, $operatorsBefore);
		$operatorsDiffAfter = array_diff($operators, $operatorsAfter);
		$content = preg_replace('/('.implode('|', $operatorsDiffBefore).')[^\S\n]+/', '\\1', $content);
		$content = preg_replace('/[^\S\n]+('.implode('|', $operatorsDiffAfter).')/', '\\1', $content);
		/*
		 * Whitespace after `return` can be omitted in a few occasions
		 * (such as when followed by a string or regex)
		 * Same for whitespace in between `)` and `{`, or between `{` and some
		 * keywords.
		 */
		$content = preg_replace('/\breturn\s+(["\'\/\+\-])/', 'return$1', $content);
		$content = preg_replace('/\)\s+\{/', '){', $content);
		/*
		 * Get rid of double semicolons, except where they can be used like:
		 * "for(v=1,_=b;;)", "for(v=1;;v++)" or "for(;;ja||(ja=true))".
		 * I'll safeguard these double semicolons inside for-loops by
		 * temporarily replacing them with an invalid condition: they won't have
		 * a double semicolon and will be easy to spot to restore afterwards.
		 */
		$content = preg_replace('/\bfor\(([^;]*);;([^;]*)\)/', 'for(\\1;-;\\2)', $content);
		$content = preg_replace('/;+/', ';', $content);
		$content = preg_replace('/\bfor\(([^;]*);-;([^;]*)\)/', 'for(\\1;;\\2)', $content);
		/*
		 * Next, we'll be removing all semicolons where ASI kicks in.
		 * for-loops however, can have an empty body (ending in only a
		 * semicolon), like: `for(i=1;i<3;i++);`, of `for(i in list);`
		 * Here, nothing happens during the loop; it's just used to keep
		 * increasing `i`. With that ; omitted, the next line would be expected
		 * to be the for-loop's body... Same goes for while loops.
		 * I'm going to double that semicolon (if any) so after the next line,
		 * which strips semicolons here & there, we're still left with this one.
		 */
		$content = preg_replace('/(for\([^;\{]*;[^;\{]*;[^;\{]*\));(\}|$)/s', '\\1;;\\2', $content);
		$content = preg_replace('/(for\([^;\{]+\s+in\s+[^;\{]+\));(\}|$)/s', '\\1;;\\2', $content);
		/*
		 * Below will also keep `;` after a `do{}while();` along with `while();`
		 * While these could be stripped after do-while, detecting this
		 * distinction is cumbersome, so I'll play it safe and make sure `;`
		 * after any kind of `while` is kept.
		 */
		$content = preg_replace('/(while\([^;\{]+\));(\}|$)/s', '\\1;;\\2', $content);
		/*
		 * We also can't strip empty else-statements. Even though they're
		 * useless and probably shouldn't be in the code in the first place, we
		 * shouldn't be stripping the `;` that follows it as it breaks the code.
		 * We can just remove those useless else-statements completely.
		 *
		 * @see https://github.com/matthiasmullie/minify/issues/91
		 */
		$content = preg_replace('/else;/s', '', $content);
		/*
		 * We also don't really want to terminate statements followed by closing
		 * curly braces (which we've ignored completely up until now) or end-of-
		 * script: ASI will kick in here & we're all about minifying.
		 * Semicolons at beginning of the file don't make any sense either.
		 */
		$content = preg_replace('/;(\}|$)/s', '\\1', $content);
		$content = (string) ltrim((string)$content, ';');
		//--
		return (string) trim($content); // get rid of remaining whitespace af beginning/end
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * Strip the whitespace around certain operators with regular expressions.
	 * This will prepare the given array by escaping all characters.
	 *
	 * @param string[] $operators
	 * @param string   $delimiter
	 *
	 * @return string[]
	 */
	private function getJsOperatorsForRegex(array $operators, string $delimiter='/') {
		// escape operators for use in regex
		$delimiters = array_fill(0, count($operators), $delimiter);
		$escaped = array_map('preg_quote', $operators, $delimiters);
		$operators = array_combine($operators, $escaped);
		// ignore + & - for now, they'll get special treatment
		unset($operators['+'], $operators['-']);
		// dot can not just immediately follow a number; it can be confused for
		// decimal point, or calling a method on it, e.g. 42 .toString()
		$operators['.'] = '(?<![0-9]\s)\.';
		// don't confuse = with other assignment shortcuts (e.g. +=)
		$chars = preg_quote('+-*\=<>%&|', $delimiter);
		$operators['='] = '(?<!['.$chars.'])\=';
		//--
		return $operators;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * Strip the whitespace around certain keywords with regular expressions.
	 * This will prepare the given array by escaping all characters.
	 *
	 * @param string[] $keywords
	 * @param string   $delimiter
	 *
	 * @return string[]
	 */
	private function getJsKeywordsForRegex(array $keywords, string $delimiter='/') {
		//--
		// escape keywords for use in regex
		$delimiter = array_fill(0, count($keywords), $delimiter);
		$escaped = array_map('preg_quote', $keywords, $delimiter);
		// add word boundaries
		array_walk($keywords, function ($value) {
			return '\b'.$value.'\b';
		});
		$keywords = array_combine($keywords, $escaped);
		//--
		return $keywords;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * JS can have /-delimited regular expressions, like: /ab+c/.match(string).
	 *
	 * The content inside the regex can contain characters that may be confused
	 * for JS code: e.g. it could contain whitespace it needs to match & we
	 * don't want to strip whitespace in there.
	 *
	 * The regex can be pretty simple: we don't have to care about comments,
	 * (which also use slashes) because prepareStripComments() will have stripped those
	 * already.
	 *
	 * This method will replace all string content with simple REGEX#
	 * placeholder text, so we've rid all regular expressions from characters
	 * that may be misinterpreted. Original regex content will be saved in
	 * $this->extracted and after doing all other minifying, we can restore the
	 * original content via restoreRegex()
	 */
	private function extractJsRegex() {
		// PHP only supports $this inside anonymous functions since 5.4
		$class = $this;
		$callback = function ($match) use ($class) {
			$count = count($class->extracted); // $this->extracted
			$placeholder = '"'.$count.'"';
			$class->extracted[$placeholder] = $match[0]; // $this->extracted
			return $placeholder;
		};
		// match all chars except `/` and `\`
		// `\` is allowed though, along with whatever char follows (which is the
		// one being escaped)
		// this should allow all chars, except for an unescaped `/` (= the one
		// closing the regex)
		// then also ignore bare `/` inside `[]`, where they don't need to be
		// escaped: anything inside `[]` can be ignored safely
		$pattern = '\\/(?!\*)(?:[^\\[\\/\\\\\n\r]++|(?:\\\\.)++|(?:\\[(?:[^\\]\\\\\n\r]++|(?:\\\\.)++)++\\])++)++\\/[gimuy]*';
		// a regular expression can only be followed by a few operators or some
		// of the RegExp methods (a `\` followed by a variable or value is
		// likely part of a division, not a regex)
		$before = '(^|[=:,;\+\-\*\/\}\(\{\[&\|!]|'.implode('|', (array)self::KEYWORDS).')\s*';
		$propertiesAndMethods = (array) self::PROPERTIESANDMETHODS;
		$delimiters = array_fill(0, count($propertiesAndMethods), '/');
		$propertiesAndMethods = array_map('preg_quote', $propertiesAndMethods, $delimiters);
		$after = '(?=\s*([\.,;\)\}&\|+]|\/\/|$|\.('.implode('|', $propertiesAndMethods).')))';
		$this->registerPattern('/'.$before.'\K'.$pattern.$after.'/', $callback);
		// regular expressions following a `)` are rather annoying to detect...
		// quite often, `/` after `)` is a division operator & if it happens to
		// be followed by another one (or a comment), it is likely to be
		// confused for a regular expression
		// however, it's perfectly possible for a regex to follow a `)`: after
		// a single-line `if()`, `while()`, ... statement, for example
		// since, when they occur like that, they're always the start of a
		// statement, there's only a limited amount of ways they can be useful:
		// by calling the regex methods directly
		// if a regex following `)` is not followed by `.<property or method>`,
		// it's quite likely not a regex
		$before = '\)\s*';
		$after = '(?=\s*\.('.implode('|', $propertiesAndMethods).'))';
		$this->registerPattern('/'.$before.'\K'.$pattern.$after.'/', $callback);
		// 1 more edge case: a regex can be followed by a lot more operators or
		// keywords if there's a newline (ASI) in between, where the operator
		// actually starts a new statement
		// (https://github.com/matthiasmullie/minify/issues/56)
		$operators = $this->getJsOperatorsForRegex((array)self::OPERATORSBEFORE, '/');
		$operators += $this->getJsOperatorsForRegex((array)self::KEYWORDSRESERVED, '/');
		$after = '(?=\s*\n\s*('.implode('|', $operators).'))';
		$this->registerPattern('/'.$pattern.$after.'/', $callback);
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
