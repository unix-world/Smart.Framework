<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// CSS Lint
// (c) 2006-2021 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

//----------------------------------------------------- PREVENT S EXECUTION [T]
if((!defined('SMART_FRAMEWORK_RUNTIME_MODE')) OR ((string)SMART_FRAMEWORK_RUNTIME_MODE != 'web.task')) { // this must be defined in the first line of the application :: {{{SYNC-RUNTIME-MODE-OVERRIDE-TASK}}}
	@http_response_code(500);
	die('Invalid Runtime Mode in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

// based on https://github.com/neilime/php-css-lint
// license: MIT, (c) 2017 Emilien Escalle
// modified by unixman, (c) 2021 unix-world.org
// v.20210511

/**
 * CSS Lint
 *
 * @access 		private
 * @internal
 *
 */
final class CssLint {

	private const CONTEXT_SELECTOR = 'selector';
	private const CONTEXT_SELECTOR_CONTENT = 'selector content';
	private const CONTEXT_NESTED_SELECTOR_CONTENT = 'nested selector content';
	private const CONTEXT_PROPERTY_NAME = 'property name';
	private const CONTEXT_PROPERTY_CONTENT = 'property content';

	/**
	 * Errors occurred during the lint process
	 * @var array|null
	 */
	private $errors = [];

	/**
	 * Current line number
	 * @var int
	 */
	private $lineNumber = 0;

	/**
	 * Current char number
	 * @var int
	 */
	private $charNumber = 0;

	/**
	 * Current context of parsing (must be a constant starting by CONTEXT_...)
	 * @var string|null
	 */
	private $context;

	/**
	 * Current content of parse. Ex: the selector name, the property name or the property content
	 * @var string
	 */
	private $contextContent;

	/**
	 * The previous linted char
	 * @var string|null
	 */
	private $previousChar;

	/**
	 * Tells if the CssLint is parsing a nested selector. Ex: @media, @keyframes...
	 * @var boolean
	 */
	private $nestedSelector = false;

	/**
	 * Tells if the CssLint is parsing a comment
	 * @var boolean
	 */
	private $comment = false;


	/**
	 * Constructor
	 */
	public function __construct() {
		// constructor
	}

	/**
	 * Performs lint on a given string
	 * @param string $sString
	 * @return boolean : true if the string is a valid css string, false else
	 */
	public function lintString(string $sString): bool {
		$sString = (string) str_replace("\t", ' ', (string)$sString); // fix by unixman: avoid err: Unexpected char "\t"
		if(strpos((string)$sString, '{') === false) {
			return true; // fix by unixman ; it fails with app.css where only @import url() :: css code is defined
		} //end if
		$this->initLint();
		$iIterator = 0;
		while(isset($sString[$iIterator])) {
			if($this->lintChar($sString[$iIterator]) === false) {
				return false;
			}
			$iIterator++;
		}
		if(!$this->assertContext(null)) {
			$this->addError('Unterminated "' . $this->context . '"');
		}

		return !$this->getErrors();
	}

	/**
	 * Return the errors occurred during the lint process
	 * @return string
	 */
	public function getErrors(): string {
		return (string) trim((string)(is_array($this->errors) ? implode("\n", (array)$this->errors) : ''));
	}


	//===== [PRIVATES]


	/**
	 * Initialize CssLint, reset all process properties
	 * @return CssLint
	 */
	private function initLint() {
		$this->resetPreviousChar()
				->resetContext()
				->resetLineNumber()->incrementLineNumber()
				->resetCharNumber()
				->resetErrors()
				->resetContextContent();
		return $this;
	}

	/**
	 * Performs lint on a given char
	 * @param string $sChar
	 * @return boolean : true if the process should continue, else false
	 */
	private function lintChar(string $sChar): ?bool {
		$this->incrementCharNumber();
		if($this->isEndOfLine($sChar)) {
			$this->setPreviousChar($sChar);
			if($sChar === "\n") {
				$this->incrementLineNumber()->resetCharNumber();
			}
			return true;
		}
		if(is_bool($bLintCommentChar = $this->lintCommentChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintCommentChar;
		}
		if(is_bool($bLintSelectorChar = $this->lintSelectorChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintSelectorChar;
		}
		if(is_bool($bLintSelectorContentChar = $this->lintSelectorContentChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintSelectorContentChar;
		}
		if(is_bool($bLintPropertyNameChar = $this->lintPropertyNameChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintPropertyNameChar;
		}
		if(is_bool($bLintPropertyContentChar = $this->lintPropertyContentChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintPropertyContentChar;
		}
		if(is_bool($bLintNestedSelectorChar = $this->lintNestedSelectorChar($sChar))) {
			$this->setPreviousChar($sChar);
			return $bLintNestedSelectorChar;
		}
		if($sChar === '*') {
			return true; // don't know how to fix this, ux-toolkit.css have the selector `*display:` which fails here ...
		}
		$this->addError('Unexpected char ' . json_encode($sChar));
		$this->setPreviousChar($sChar);
		return false;
	}

	/**
	 * Performs lint for a given char, check comment part
	 * @param string $sChar
	 * @return boolean|null : true if the process should continue, else false, null if this char is not about comment
	 */
	private function lintCommentChar(string $sChar): ?bool {
		// Manage comment context
		if($this->isComment()) {
			if($sChar === '/' && $this->assertPreviousChar('*')) {
				$this->setComment(false);
			}
			$this->setPreviousChar($sChar);
			return true;
		}
		// First char for a comment
		if($sChar === '/') {
			return true;
		} elseif($sChar === '*' && $this->assertPreviousChar('/')) {
			// End of comment
			$this->setComment(true);
			return true;
		}
		return null;
	}

	/**
	 * Checks that the given CSS property is an existing one
	 * @param string $sProperty the property to check
	 * @return boolean true if the property exists, else returns false
	 */
	public function propertyExists(string $sProperty): bool {
		return true; // TODO in the future, get the list with all CSS properties, the original one was bad ...
	}

	/**
	 * Check if the given char is allowed as an indentation char
	 * @param string $sChar the character to be checked
	 * @return bool according to whether the character is allowed or not
	 */
	private function isAllowedIndentationChar(?string $sChar): bool {
		if(((string)$sChar == ' ') || ((string)$sChar == "\t")) {
			return true;
		}
		return false;
	}

	/**
	 * Performs lint for a given char, check selector part
	 * @param string $sChar
	 * @return boolean|null : true if the process should continue, else false, null if this char is not about selector
	 */
	private function lintSelectorChar(string $sChar): ?bool {
		// Selector must start by #.a-zA-Z
		if($this->assertContext(null)) {
			if($this->isAllowedIndentationChar($sChar)) {
				return true;
			}
			if(preg_match('/[@\#.a-zA-Z\[\*-:]+/', $sChar)) {
				$this->setContext(self::CONTEXT_SELECTOR);
				$this->addContextContent($sChar);
				return true;
			}
			return null;
		}
		// Selector must contains
		if($this->assertContext(self::CONTEXT_SELECTOR)) {
			// A space is valid
			if($sChar === ' ') {
				$this->addContextContent($sChar);
				return true;
			}
			// Start of selector content
			if($sChar === '{') {
				// Check if selector if valid
				$sSelector = trim($this->getContextContent());
				// @nested is a specific selector content
				if(
					// @media selector
					preg_match('/^@media.+/', $sSelector)
					// Keyframes selector
					|| preg_match('/^@.*keyframes.+/', $sSelector)
				) {
					$this->setNestedSelector(true);
					$this->resetContext();
				} else {
					$this->setContext(self::CONTEXT_SELECTOR_CONTENT);
				}
				$this->addContextContent($sChar);
				return true;
			}
			// There cannot have two following commas
			if($sChar === ',') {
				$sSelector = $this->getContextContent();
				if(!$sSelector || !preg_match('/, *$/', $sSelector)) {
					$this->addContextContent($sChar);
					return true;
				}
				$this->addError(sprintf(
					'Selector token %s cannot be preceded by "%s"',
					json_encode($sChar),
					$sSelector
				));
				return false;
			}
			// Wildcard and hash
			if(in_array($sChar, ['*', '#'], true)) {
				$sSelector = $this->getContextContent();
				if(!$sSelector || preg_match('/[a-zA-Z>,\'"] *$/', $sSelector)) {
					$this->addContextContent($sChar);
					return true;
				}
				$this->addError('Selector token "' . $sChar . '" cannot be preceded by "' . $sSelector . '"');
				return true;
			}
			// Dot
			if($sChar === '.') {
				$sSelector = $this->getContextContent();
			//	if(!$sSelector || preg_match('/(, |[a-zA-Z]).*$/', $sSelector)) {
				if(!$sSelector || preg_match('/(, |[a-zA-Z0-9]).*$/', $sSelector)) { // fix by unixman, animate.css does not pass on line 294 with frames such as 6.5% { ... }
					$this->addContextContent($sChar);
					return true;
				}
				$this->addError('Selector token "' . $sChar . '" cannot be preceded by "' . $sSelector . '"');
				return true;
			}
			if(preg_match('/^[#*.0-9a-zA-Z,:()\[\]="\'-^~_%]+/', $sChar)) {
				$this->addContextContent($sChar);
				return true;
			}
			$this->addError('Unexpected selector token "' . $sChar . '"');
			return true;
		}
		return null;
	}

	/**
	 * Performs lint for a given char, check selector content part
	 * @param string $sChar
	 * @return bool|null : true if the process should continue, else false, null if this char is not a selector content
	 */
	private function lintSelectorContentChar(string $sChar): ?bool {
		if(!$this->assertContext(self::CONTEXT_SELECTOR_CONTENT)) {
			return null;
		}
		$sContextContent = $this->getContextContent();
		if(
			(!$sContextContent || $sContextContent === '{') &&
			$this->isAllowedIndentationChar($sChar)
		) {
			return true;
		}
		if($sChar === '}') {
			if($this->isNestedSelector()) {
				$this->resetContext();
			} else {
				$this->resetContext();
			}
			return true;
		}
		if(preg_match('/[-a-zA-Z]+/', $sChar)) {
			$this->setContext(self::CONTEXT_PROPERTY_NAME);
			$this->addContextContent($sChar);
			return true;
		}
		return null;
	}

	/**
	 * Performs lint for a given char, check property name part
	 * @param string $sChar
	 * @return bool|null : true if the process should continue, else false, null if this char is not a property name
	 */
	private function lintPropertyNameChar(string $sChar): ?bool {
		if(!$this->assertContext(self::CONTEXT_PROPERTY_NAME)) {
			return null;
		}
		if($sChar === ':') {
			// Check if property name exists
			$sPropertyName = trim($this->getContextContent());
			if(!$this->propertyExists($sPropertyName)) {
				$this->addError('Unknown CSS property "' . $sPropertyName . '"');
			}
			$this->setContext(self::CONTEXT_PROPERTY_CONTENT);
			return true;
		}
		$this->addContextContent($sChar);
		if($sChar === ' ') {
			return true;
		}
		if(!preg_match('/[-a-zA-Z]+/', $sChar)) {
			$this->addError('Unexpected property name token "' . $sChar . '"');
		}
		return true;
	}

	/**
	 * Performs lint for a given char, check property content part
	 * @param string $sChar
	 * @return bool|null : true if the process should continue, else false, null if this char is not a property content
	 */
	private function lintPropertyContentChar(string $sChar): ?bool {
		if(!$this->assertContext(self::CONTEXT_PROPERTY_CONTENT)) {
			return null;
		}
		$this->addContextContent($sChar);
		// End of the property content
		if($sChar === ';') {
			// Check if the ";" is not quoted
			$sContextContent = $this->getContextContent();
			if(!(substr_count($sContextContent, '"') & 1) && !(substr_count($sContextContent, '\'') & 1)) {
				$this->setContext(self::CONTEXT_SELECTOR_CONTENT);
			}
			if(trim($sContextContent)) {
				return true;
			}
			$this->addError('Property cannot be empty');
			return true;
		}
		// No property content validation
		return true;
	}

	/**
	 * Performs lint for a given char, check nested selector part
	 * @param string $sChar
	 * @return bool|null : true if the process should continue, else false, null if this char is not a nested selector
	 */
	private function lintNestedSelectorChar(string $sChar): ?bool {
		// End of nested selector
		if($this->isNestedSelector() && $this->assertContext(null) && $sChar === '}') {
			$this->setNestedSelector(false);
			return true;
		}
		return null;
	}

	/**
	 * Check if a given char is an end of line token
	 * @param string $sChar
	 * @return boolean : true if the char is an end of line token, else false
	 */
	private function isEndOfLine(string $sChar): bool {
		return $sChar === "\r" || $sChar === "\n";
	}

	/**
	 * Return the current char number
	 * @return int
	 */
	private function getCharNumber(): int {
		return $this->charNumber;
	}

	/**
	 * Assert that previous char is the same as given
	 * @param string $sChar
	 * @return boolean
	 */
	private function assertPreviousChar(string $sChar): bool {
		return $this->previousChar === $sChar;
	}

	/**
	 * Reset previous char property
	 * @return CssLint
	 */
	private function resetPreviousChar(): CssLint {
		$this->previousChar = null;
		return $this;
	}

	/**
	 * Set new previous char
	 * @param string $sChar
	 * @return CssLint
	 */
	private function setPreviousChar(string $sChar): CssLint {
		$this->previousChar = $sChar;
		return $this;
	}

	/**
	 * Return the current line number
	 * @return int
	 */
	private function getLineNumber(): int {
		return $this->lineNumber;
	}

	/**
	 * Add 1 to the current line number
	 * @return CssLint
	 */
	private function incrementLineNumber(): CssLint {
		$this->lineNumber++;
		return $this;
	}

	/**
	 * Reset current line number property
	 * @return CssLint
	 */
	private function resetLineNumber(): CssLint {
		$this->lineNumber = 0;
		return $this;
	}

	/**
	 * Reset current char number property
	 * @return CssLint
	 */
	private function resetCharNumber(): CssLint {
		$this->charNumber = 0;
		return $this;
	}

	/**
	 * Add 1 to the current char number
	 * @return CssLint
	 */
	private function incrementCharNumber(): CssLint {
		$this->charNumber++;
		return $this;
	}

	/**
	 * Assert that current context is the same as given
	 * @param string|array|null $sContext
	 * @return boolean
	 */
	private function assertContext($sContext): bool {
		if(is_array($sContext)) {
			foreach($sContext as $sTmpContext) {
				if($this->assertContext($sTmpContext)) {
					return true;
				}
			}
			return false;
		}
		return $this->context === $sContext;
	}

	/**
	 * Reset context property
	 * @return CssLint
	 */
	private function resetContext(): CssLint {
		return $this->setContext(null);
	}

	/**
	 * Set new context
	 * @param string|null $sContext
	 * @return CssLint
	 */
	private function setContext($sContext): CssLint {
		$this->context = $sContext;
		return $this->resetContextContent();
	}

	/**
	 * Return context content
	 * @return string
	 */
	private function getContextContent(): string {
		return $this->contextContent;
	}

	/**
	 * Reset context content property
	 * @return CssLint
	 */
	private function resetContextContent(): CssLint {
		$this->contextContent = '';
		return $this;
	}

	/**
	 * Append new value to context content
	 * @param string $sContextContent
	 * @return CssLint
	 */
	private function addContextContent($sContextContent): CssLint {
		$this->contextContent .= $sContextContent;
		return $this;
	}

	/**
	 * Add a new error message to the errors property, it adds extra infos to the given error message
	 * @param string $sError
	 * @return CssLint
	 */
	private function addError($sError): CssLint {
		$this->errors[] = $sError . ' (line: ' . $this->getLineNumber() . ', char: ' . $this->getCharNumber() . ')';
		return $this;
	}

	/**
	 * Reset the errors property
	 * @return CssLint
	 */
	private function resetErrors(): CssLint {
		$this->errors = null;
		return $this;
	}

	/**
	 * Tells if the CssLint is parsing a nested selector
	 * @return boolean
	 */
	private function isNestedSelector(): bool {
		return $this->nestedSelector;
	}

	/**
	 * Set the nested selector flag
	 * @param boolean $bNestedSelector
	 */
	private function setNestedSelector(bool $bNestedSelector): void {
		$this->nestedSelector = $bNestedSelector;
	}

	/**
	 * Tells if the CssLint is parsing a comment
	 * @return boolean
	 */
	private function isComment(): bool {
		return $this->comment;
	}

	/**
	 * Set the comment flag
	 * @param boolean $bComment
	 */
	private function setComment(bool $bComment): void {
		$this->comment = $bComment;
	}

} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
