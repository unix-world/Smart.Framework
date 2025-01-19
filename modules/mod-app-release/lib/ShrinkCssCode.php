<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Strip CSS Code
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
 * Shrink (Strip) CSS Code
 * (c) 2021-present unix-world.org
 *
 * based on CSS Minifier Class v.1.3.63, license BSD
 * (c) 2012-2021 Matthias Mullie
 *
 * @access 		private
 * @internal
 *
 * @depends classes: ShrinkCode
 *
 */
final class ShrinkCssCode extends ShrinkCode {

	// ->
	// v.20250107


	//====================================================
	// css strip
	protected function stripCustomCode() {
		//--
		$css = (string) $this->sourceCode;
		//--
		$this->extractStrings('\'"'); // css
		$this->prepareStripCssComments(); // css
		//-- operate
		$css = (string) $this->operateReplacements((string)$css); // process
		$css = (string) $this->stripCssWhitespace((string)$css); // strip the whitespace, css
		$css = (string) $this->stripCssEmptyTags((string)$css); // strip css empty tags, they are useless
		//-- finalize
		$css = (string) $this->restoreExtractedData((string)$css); // restore extracted data by extract strings
		//--
		return (string) trim((string)$css);
		//--
	} //END FUNCTION
	//====================================================


	//======= [ PRIVATES ]


	//====================================================
	private function prepareStripCssComments() {
		//-- multi-line comments
		$this->registerPattern('/\/\*.*?\*\//s', '');
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * Strip CSS whitespace
	 *
	 * @param string $content The CSS content to strip the whitespace for
	 *
	 * @return string
	 */
	private function stripCssWhitespace(string $content) {
		// uniform line endings, make them all line feed
		$content = str_replace(["\r\n", "\r"], "\n", $content);
		// remove leading & trailing whitespace
		$content = preg_replace('/^\s*/m', '', $content);
		$content = preg_replace('/\s*$/m', '', $content);
		// replace newlines with a single space
		$content = preg_replace('/\s+/', ' ', $content);
		// remove whitespace around meta characters ; inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
		$content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
		$content = preg_replace('/([\[(:>\+])\s+/', '$1', $content);
		$content = preg_replace('/\s+([\]\)>\+])/', '$1', $content);
		$content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);
		// remove semicolon/whitespace followed by closing bracket
		$content = str_replace(';}', '}', $content);
		//--
		return (string) trim((string)$content);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * Strip empty tags from source code.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function stripCssEmptyTags(string $content) {
		//--
		$content = preg_replace('/(?<=^)[^\{\};]+\{\s*\}/', '', $content);
		$content = preg_replace('/(?<=(\}|;))[^\{\};]+\{\s*\}/', '', $content);
		//--
		return (string) trim((string)$content);
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
