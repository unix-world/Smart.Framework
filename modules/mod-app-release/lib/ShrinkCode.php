<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Strip Code - Abstract Provider
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
 * Shrink (Strip) Code
 * (c) 2021-present unix-world.org
 *
 * based on Minifier Class v.1.3.63, license BSD
 * (c) 2012-2021 Matthias Mullie
 *
 * @access 		private
 * @internal
 *
 */
abstract class ShrinkCode {

	// ->
	// v.20250107

	protected $isClean = true;
	protected $sourceCode = '';

	protected $patterns = [];
	protected $extracted = [];


	//====================================================
	final public function __construct(string $filePath) {
		//--
		if((string)trim((string)$filePath) == '') {
			throw new Exception('Path is empty !');
			return;
		} //end if
		//--
		if(!is_file((string)$filePath)) {
			throw new Exception('Path is not a file !');
			return;
		} //end if
		if(!is_readable((string)$filePath)) {
			throw new Exception('File is not readable !');
			return;
		} //end if
		//--
		$this->sourceCode = (string) trim((string)file_get_contents((string)$filePath));
		if((string)$this->sourceCode == '') {
			$this->sourceCode = '';
			throw new Exception('File content is empty !');
			return;
		} //end if
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	final public function __destruct() {
		// nothing to do here, but this should not be overriden !
	} //END FUNCTION
	//====================================================


	//====================================================
	// css strip
	final public function stripCode() {
		//--
		if($this->isClean !== true) {
			throw new Exception('This class is not re-usable, use a new instance of this class for each separate sourcecode entity ...');
			return '';
		} //end if
		$this->isClean = false;
		//--
		if((string)trim((string)$this->sourceCode) == '') {
			throw new Exception('SourceCode is empty !!');
			return '';
		} //end if
		//--
		return (string) $this->stripCustomCode();
		//--
	} //END FUNCTION
	//====================================================


	//======= [ PROTECTED: SHARED ]


	//====================================================
	abstract protected function stripCustomCode();
	//====================================================


	//====================================================
	protected function extractStrings(string $chars, string $placeholderPrefix='') { // {{{SYNC-CODE-SHRINK-REPLACE}}}
		//-- PHP only supports $this inside anonymous functions since 5.4
		$class = $this;
		$callback = function ($match) use ($class, $placeholderPrefix) {
			// check the second index here, because the first always contains a quote
			if($match[2] === '') {
				/*
				 * Empty strings need no placeholder; they can't be confused for
				 * anything else anyway.
				 * But we still needed to match them, for the extraction routine
				 * to skip over this particular string.
				 */
				return $match[0];
			} //end if
			$count = count($class->extracted); // $this->extracted
			$placeholder = $match[1].$placeholderPrefix.$count.$match[1];
			$class->extracted[$placeholder] = $match[1].$match[2].$match[1]; // $this->extracted
			return $placeholder;
		};
		//--
		/*
		 * The \\ messiness explained:
		 * * Don't count ' or " as end-of-string if it's escaped (has backslash
		 * in front of it)
		 * * Unless... that backslash itself is escaped (another leading slash),
		 * in which case it's no longer escaping the ' or "
		 * * So there can be either no backslash, or an even number
		 * * multiply all of that times 4, to account for the escaping that has
		 * to be done to pass the backslash into the PHP string without it being
		 * considered as escape-char (times 2) and to get it in the regex,
		 * escaped (times 2)
		 */
		$this->registerPattern('/(['.$chars.'])(.*?(?<!\\\\)(\\\\\\\\)*+)\\1/s', $callback);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * It is not possible to "just" run some regular expressions against JavaScript:
	 * it's a complex language. E.g. having an occurrence of // xyz would be a comment,
	 * unless it's used within a string. Of you could have something that looks
	 * like a 'string', but inside a comment.
	 * The only way to accurately replace these pieces is to traverse the JS one
	 * character at a time and try to find whatever starts first.
	 * Also on CSS there are some complex situations
	 *
	 * @param string $content The content to replace patterns in
	 *
	 * @return string The (manipulated) content
	 */
	protected function operateReplacements(string $content) { // {{{SYNC-CODE-OPERATE-REPLACE}}}
		$processed = '';
		$positions = array_fill(0, count($this->patterns), -1);
		$matches = [];
		while($content) {
			// find first match for all patterns
			foreach($this->patterns as $i => $pattern) {
				list($pattern, $replacement) = $pattern;
				// we can safely ignore patterns for positions we've unset earlier,
				// because we know these won't show up anymore
				if(array_key_exists($i, $positions) == false) {
					continue;
				} //end if
				// no need to re-run matches that are still in the part of the
				// content that hasn't been processed
				if($positions[$i] >= 0) {
					continue;
				} //end if
				$match = null;
				if(preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
					$matches[$i] = $match;
					// we'll store the match position as well; that way, we
					// don't have to redo all preg_matches after changing only
					// the first (we'll still know where those others are)
					$positions[$i] = $match[0][1];
				} else {
					// if the pattern couldn't be matched, there's no point in
					// executing it again in later runs on this same content;
					// ignore this one until we reach end of content
					unset($matches[$i], $positions[$i]);
				} //end if else
			} //end foreach
			// no more matches to find: everything's been processed, break out
			if(!$matches) {
				$processed .= $content;
				break;
			} //end if
			// see which of the patterns actually found the first thing (we'll
			// only want to execute that one, since we're unsure if what the
			// other found was not inside what the first found)
			$discardLength = min($positions);
			$firstPattern = array_search($discardLength, $positions);
			$match = $matches[$firstPattern][0][0];
			// execute the pattern that matches earliest in the content string
			list($pattern, $replacement) = $this->patterns[$firstPattern];
			$replacement = $this->replacePattern($pattern, $replacement, $content);
			// figure out which part of the string was unmatched; that's the
			// part we'll execute the patterns on again next
			$content = (string) substr($content, $discardLength);
			$unmatched = (string) substr($content, strpos($content, $match) + strlen($match));
			// move the replaced part to $processed and prepare $content to
			// again match batch of patterns against
			$processed .= (string) substr($replacement, 0, strlen($replacement) - strlen($unmatched));
			$content = $unmatched;
			// first match has been replaced & that content is to be left alone,
			// the next matches will start after this replacement, so we should
			// fix their offsets
			foreach($positions as $i => $position) {
				$positions[$i] -= $discardLength + strlen($match);
			} //end foreach
		} //end while
		//--
		return (string) $processed;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * This method will restore all extracted data (strings, regexes) that were
	 * replaced with placeholder text in extract*(). The original content was
	 * saved in $this->extracted.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function restoreExtractedData(string $content) { // {{{SYNC-CODE-RESTORE-DATA-REPLACE}}}
		//--
		if(count($this->extracted) <= 0) {
			return (string) $content; // nothing was extracted, nothing to restore
		} //end if
		$content = (string) strtr((string)$content, $this->extracted);
		$this->extracted = [];
		//--
		return (string) $content;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	protected function registerPattern(string $pattern, $replacement) { // {{{SYNC-CODE-REGISTER-PATTERN-DATA-REPLACE}}}
		//-- study the pattern, we'll execute it more than once
		$pattern .= 'S';
		$this->patterns[] = [ $pattern, $replacement ]; // $replacement is MIXED: can be string or callable
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	/**
	 * This is where a pattern is matched against $content and the matches
	 * are replaced by their respective value.
	 * This function will be called plenty of times, where $content will always
	 * move up 1 character.
	 *
	 * @param string          $pattern     Pattern to match
	 * @param string|callable $replacement Replacement value
	 * @param string          $content     Content to match pattern against
	 *
	 * @return string
	 */
	protected function replacePattern($pattern, $replacement, $content) { // {{{SYNC-CODE-REPLACE-PATTERN-DATA-REPLACE}}}
		//--
		if(is_callable($replacement)) {
			return preg_replace_callback($pattern, $replacement, $content, 1, $count); // mixed
		} else {
			return preg_replace($pattern, ($replacement ? $replacement : ''), $content, 1, $count); // mixed
		} //end if else
		//--
	} //END FUNCTION
	//====================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
