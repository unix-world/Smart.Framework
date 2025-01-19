<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// App Code Optimizer
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
 * Code Optimizer for Software Releases
 *
 * DEPENDS:
 * Smart::
 * SmartFileSysUtils::
 * SmartFileSystem::
 *
 * PhpOptimizer::
 * JsOptimizer::
 * CssOptimizer::
 *
 * @depends: constants: TASK_APP_RELEASE_CODEPACK_MODE, TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR
 *
 * @access 		private
 * @internal
 *
 */
final class AppCodeOptimizer {

	// ->
	// v.20250107

	private $debug;

	private $log;
	private $err;
	private $counters 	= [];
	private $sfnopack 	= [];
	private $sfnostrip 	= [];
	private $sfdevonly 	= [];


	//====================================================
	public function __construct($y_debug=false) {
		//--
		$this->debug = (bool) $y_debug;
		//--
		$this->log = '';
		$this->err = '';
		$this->counters = [
			'dirs' 				=> 0,
			'dir-nopack' 		=> 0,
			'php' 				=> 0,
			'js' 				=> 0,
			'css' 				=> 0,
			'files-nostrip' 	=> 0,
			'dev-only-files' 	=> 0,
			'other-files' 		=> 0,
			'dot-files' 		=> 0,
		];
		$this->sfnopack  = [];
		$this->sfnostrip = [];
		$this->sfdevonly = [];
		//--
		clearstatcache(true); // do a full clear stat cache at the begining
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// optimizations method for folders
	public function optimize_code_dir(?string $dirsource) {
		//--
		if((string)$this->err != '') {
			return;
		} //end if
		//--
		$dirsource = (string) $dirsource;
		$dirsource = (string) trim((string)$dirsource, '/');
		//--
		$this->optimize_dir((string)$dirsource);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	// optimizations method for files
	public function optimize_code_file(?string $filesource) {
		//--
		if((string)$this->err != '') {
			return;
		} //end if
		//--
		if(
			(AppNetUnPackager::unpack_valid_file_name((string)Smart::base_name((string)$filesource)) !== true)
		) {
			$this->err = 'ERROR: Invalid File: '.$filesource.' !';
			return;
		} //end if
		//--
		$dirdest = (string) trim((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR);
		if(!SmartFileSysUtils::checkIfSafePath((string)$dirdest)) {
			$this->err = 'ERROR: Destination Folder of File: '.$filesource.' IS NOT VALID ! TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR=`'.$dirdest.'`';
			return;
		} //end if
		$dirdest = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirdest);
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirdest); // must be relative path
		//--
		$dir_of_file = (string) SmartFileSysUtils::extractPathDir((string)$filesource);
		if((string)$dir_of_file != '') {
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dir_of_file); // must be relative path
			if(!SmartFileSystem::is_type_dir($dirdest.$dir_of_file)) {
				SmartFileSystem::dir_create((string)$dirdest.$dir_of_file, true);
			} //end if
			if(!SmartFileSystem::is_type_dir($dirdest.$dir_of_file)) {
				$this->err = 'ERROR: Destination Folder of File: '.$filesource.' Cannot be created !';
				return;
			} //end if
		} //end if
		//--
		$destfile = (string) $dirdest.$filesource;
		if(!SmartFileSysUtils::checkIfSafePath((string)$destfile)) {
			$this->err = 'ERROR: Destination File: '.$destfile.' IS NOT VALID !';
			return;
		} //end if
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$destfile); // must be relative path
		//--
		if(SmartFileSystem::is_type_dir($filesource)) {
			$this->err = 'ERROR: The source FILE is A DIRECTORY: '.$filesource;
			return;
		} //end if
		if(!SmartFileSystem::is_type_file($filesource)) {
			$this->err = 'ERROR: The source FILE does NOT EXIST: '.$filesource;
			return;
		} //end if
		//--
		if($this->debug) {
			$this->add_to_log('Source File: '.$filesource);
			$this->add_to_log('Destination File: '.$destfile);
		} //end if
		//--
		echo '<div style="color:#777777;"><b>Sources File: ['.Smart::escape_html($filesource).'] : </b></div>';
		Smart::InstantFlush();
		//--
		$this->err = (string) $this->optimize_file((string)$filesource, (string)$destfile);
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public function have_errors() {
		//--
		return (bool) strlen((string)trim((string)$this->err));
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public function get_errors() {
		//--
		$out = '';
		if($this->have_errors()) {
			$out .= 'LAST FAILURE :: '.$this->err."\n";
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	public function get_log() {
		//--
		$out = '';
		//--
		$tot_f = (int) ($this->counters['php'] + $this->counters['js'] + $this->counters['css'] + $this->counters['files-nostrip'] + $this->counters['dev-only-files'] + $this->counters['other-files'] + $this->counters['dot-files']);
		$total = (int) ($this->counters['dirs'] + $this->counters['dir-nopack'] + $tot_f);
		//--
		$out .= 'PHP Scripts (processed): '.$this->counters['php']."\n";
		$out .= 'JS Scripts (processed): '.$this->counters['js']."\n";
		$out .= 'CSS Stylesheets (processed): '.$this->counters['css']."\n";
		if(count($this->sfnostrip) > 0) {
			$out .= '* No-Strip * PHP / JS / CSS Files (not processed): '.$this->counters['files-nostrip']."\n";
			for($i=0; $i<count($this->sfnostrip); $i++) {
				$out .= "\t".'- '.$this->sfnostrip[$i]."\n";
			} //end for
		} //end if
		if(($this->counters['dev-only-files'] > 0) OR (count($this->sfdevonly) > 0)) {
			$out .= '* Skipped (Development-Only) * PHP / JS / CSS Files (not copied): '.$this->counters['dev-only-files']."\n";
			for($i=0; $i<count($this->sfdevonly); $i++) {
				$out .= "\t".'- '.$this->sfdevonly[$i]."\n";
			} //end for
		} //end if
		$out .= 'Other Files (copied): '.$this->counters['other-files']."\n";
		if($this->counters['dot-files'] > 0) {
			$out .= '* Dot * Files: '.$this->counters['dot-files']."\n";
		} //end if
		$out .= '** Total Files (includded) **: '.$tot_f."\n";
		$out .= '** Total Dirs (includded) **: '.$this->counters['dirs']."\n";
		if(($this->counters['dir-nopack'] > 0) OR (count($this->sfnopack) > 0)) {
			$out .= '* Skipped (No-Pack) * Dirs (not includded): '.$this->counters['dir-nopack']."\n";
			for($i=0; $i<count($this->sfnopack); $i++) {
				$out .= "\t".'- '.$this->sfnopack[$i]."\n";
			} //end for
		} //end if
		$out .= '*** TOTAL Files and Dirs (includded) ***: '.$total."\n";
		//--
		if((string)$this->log != '') {
			$out .= "\n\n".'##### DEBUG LOG #####'."\n".$this->log;
		} //end if
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//====================================================


	//##############################################################


	//====================================================
	private function get_mode() {
		//--
		$mode = (string) strtolower((string)trim((string)TASK_APP_RELEASE_CODEPACK_MODE));
		switch((string)$mode) {
			case 'strip':
			case 'minify':
				// OK
				break;
			default:
				$this->err = 'ERROR: A constant have a wrong value: TASK_APP_RELEASE_CODEPACK_MODE=`'.$mode.'`';
				return '';
		} //end switch
		//--
		return (string) $mode;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	private function optimize_file(?string $path_original_file, ?string $path_destination_file) {
		//--
		$mode = (string) $this->get_mode();
		if((string)$this->err != '') {
			return (string) $this->err;
		} //end if
		if((string)$mode == '') {
			return 'ERROR: Empty Optimization Mode';
		} //end if
		//--
		$err = '';
		//--
		if(SmartFileSystem::is_type_dir((string)$path_original_file)) {
			$err = 'ERROR: The source FILE is A DIRECTORY: '.$path_original_file;
			return (string) $err;
		} //end if
		if(!SmartFileSystem::is_type_file((string)$path_original_file)) {
			$err = 'ERROR: The source FILE does NOT EXIST: '.$path_original_file;
			return (string) $err;
		} //end if
		//-- file or link
		if(SmartFileSystem::path_exists((string)$path_destination_file)) {
			$err = 'ERROR: The destination FILE PATH already exists: '.$path_original_file;
			return (string) $err;
		} //end if
		//--
		$fname = (string) SmartFileSysUtils::extractPathFileName((string)$path_original_file);
		//--
		$tmp_read_file_head = '';
		$tmp_is_a_file_and_is_devonly = false;
		$tmp_is_a_file_and_needs_strip = false;
		if(
			((string)substr((string)$path_original_file, -4, 4) == '.php') OR
			((string)substr((string)$path_original_file, -3, 3) == '.js') OR // .mtpl.js and .inc.js will be tested below
			((string)substr((string)$path_original_file, -4, 4) == '.css') OR
			((string)substr((string)$path_original_file, -4, 4) == '.ini') OR
			((string)substr((string)$path_original_file, -5, 5) == '.yaml')
		) {
			$tmp_read_file_head = (string) SmartFileSystem::read($path_original_file, 1024, 'no', 'no'); // read the first 1024 bytes of file to search for skip strip comment ; don't deny absolute path
			if(stripos((string)$tmp_read_file_head, '[@[#[!SF.DEV-ONLY!]#]@]') !== false) {
				$tmp_is_a_file_and_is_devonly = true;
			} elseif(
				((string)substr((string)$path_original_file, -4, 4) == '.php')
				OR
				(
					((string)substr((string)$path_original_file, -3, 3) == '.js')
					AND
					((string)substr((string)$path_original_file, -8, 8) != '.mtpl.js')
					AND
					((string)substr((string)$path_original_file, -7, 7) != '.inc.js')
				)
				OR
				((string)substr((string)$path_original_file, -4, 4) == '.css')
			) {
				if(stripos((string)$tmp_read_file_head, '[@[#[!NO-STRIP!]#]@]') === false) {
					$tmp_is_a_file_and_needs_strip = true;
				} //end if
			} //end if
			$tmp_read_file_head = '';
		} //end if
		//--
		if($tmp_is_a_file_and_is_devonly === true) {
			//--
			$this->counters['dev-only-files']++;
			$this->sfdevonly[] = (string) $path_original_file;
			//--
			if((string)substr((string)$path_original_file, -4, 4) == '.php') {
				echo '<span title="PHP @ DEV-ONLY: '.Smart::escape_html($path_original_file).'" style="color:#FF3300;cursor:default;">&clubs;</span>'."\n";
			} elseif((string)substr((string)$path_original_file, -3, 3) == '.js') {
				echo '<span title="JS @ DEV-ONLY: '.Smart::escape_html($path_original_file).'" style="color:#FF3300;cursor:default;">&spades;</span>'."\n";
			} elseif((string)substr((string)$path_original_file, -4, 4) == '.css') {
				echo '<span title="CSS @ DEV-ONLY: '.Smart::escape_html($path_original_file).'" style="color:#FF3300;cursor:default;">&hearts;</span>'."\n";
			} else {
				echo '<span title="File @ DEV-ONLY: '.Smart::escape_html($path_original_file).'" style="color:#FF3300;cursor:default;">&diams;</span>'."\n";
			} //end if else
			//--
			Smart::InstantFlush();
			//--
		} else {
			//--
			if(((string)substr((string)$path_original_file, -4, 4) == '.php') AND ($tmp_is_a_file_and_needs_strip === true)) {
				//--
				$this->counters['php']++;
				//--
				echo '<span title="PHP: '.Smart::escape_html($path_original_file).'" style="color:#4F5B93;cursor:default;">&clubs;</span>'."\n";
				Smart::InstantFlush();
				//--
				if($this->debug) {
					$this->add_to_log('PHP Script: '.$path_original_file);
				} //end if
				//--
				if((string)$mode == 'minify') { // minify code
					//--
					$the_php_proc_mode = 'ZM'; // zend minify
					$tmp_content = (string) PhpOptimizer::minify_code($path_original_file);
					//--
				} else { // strip code (remove comments and standardize)
					//--
					$the_php_proc_mode = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
					$tmp_content = (string) PhpOptimizer::strip_code($path_original_file);
					//--
				} //end if else
				//--
				if((string)trim((string)$tmp_content) == '') {
					$err = 'ERROR: EMPTY PHP FILE: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} //end if
				//--
				$tmp_content = (string) PhpOptimizer::check_and_remove_start_end_tags((string)$tmp_content);
				if((string)trim((string)$tmp_content) == '') {
					$err = 'ERROR: EMPTY PHP FILE AFTER REMOVING START/END TAGS: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} //end if
				//--
				$tmp_content = '<'.'?php'."\n".'// PHP-Script ('.$the_php_proc_mode.'): '.$path_original_file.' @ '.date('Y-m-d H:i:s O')."\n".$tmp_content."\n".'// #END'."\n"; // add this only after removing the php terminator, above
				//--
				$out = SmartFileSystem::write(
					(string) $path_destination_file,
					(string) $tmp_content
				);
				$chk = SmartFileSystem::is_type_file($path_destination_file);
				//--
				if(($out != 1) OR (!$chk)) {
					$err = 'ERROR: A PHP FILE failed to be created: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} else { // re-check syntax, it was modified by this script by adding some comments ; test if OK
					$tmp_chksyntax = (string) PhpOptimizer::minify_code($path_destination_file);
					if((string)$tmp_chksyntax == '') {
						$err = 'ERROR: A PHP FILE check syntax FAILED: '.$path_original_file.' @ '.$path_destination_file;
						return (string) $err;
					} //end if
					$tmp_chksyntax = '';
				} //end if else
				//--
			} elseif(
				((string)substr((string)$path_original_file, -3, 3) == '.js') AND
				((string)substr((string)$path_original_file, -8, 8) != '.mtpl.js') AND
				((string)substr((string)$path_original_file, -7, 7) != '.inc.js') AND
				($tmp_is_a_file_and_needs_strip === true)
			) {
				//--
				$this->counters['js']++;
				//--
				echo '<span title="JS: '.Smart::escape_html($path_original_file).' " style="color:#FFCC00;cursor:default;">&spades;</span>'."\n";
				Smart::InstantFlush();
				//--
				if($this->debug) {
					$this->add_to_log('JS-Script: '.$path_original_file);
				} //end if
				//--
				if((string)$mode == 'minify') { // minify code
					//--
					$the_compressor_signature = (string) 'UM'; // node minify
					$tmp_arr = (array) JsOptimizer::minify_code($path_original_file);
					$tmp_content = (string) $tmp_arr['content'];
					$tmp_error = (string) $tmp_arr['error'];
					$tmp_arr = null;
					//--
				} elseif(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN') && ((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN != '')) { // {{{SYNC-SIGNATURE-STRIP-NODE-COMMENTS}}} strip code using nodejs (if there is nodejs defined prefer strip using this)
					//--
					$the_compressor_signature = (string) 'US'; // node strip
					$tmp_arr = (array) JsOptimizer::minify_code($path_original_file, 'strip');
					$tmp_content = (string) $tmp_arr['content'];
					$tmp_error = (string) $tmp_arr['error'];
					$tmp_arr = null;
					//--
				} else { // strip code (remove comments and standardize)
					//--
					$the_compressor_signature = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
					$tmp_content = (string) JsOptimizer::strip_code($path_original_file);
					$tmp_error = '';
					if((string)$tmp_content == '') {
						$tmp_error = 'ERROR: Empty Output from Js: '.$path_original_file;
					} //end if
					//--
				} //end if else
				//--
				if((strlen($tmp_error) > 0) OR ((string)$tmp_content == '')) {
					$err = 'ERROR: Failed to process a JS-Script: '.$path_original_file.' @ ['.$tmp_error.']';
					return (string) $err;
				} //end if
				//--
				$out = SmartFileSystem::write(
					(string) $path_destination_file,
					'// JS-Script ('.$the_compressor_signature.'): '.Smart::base_name($path_original_file).' @ '.date('Y-m-d H:i:s O')."\n".trim((string)$tmp_content)."\n".'// #END'."\n"
				);
				$chk = SmartFileSystem::is_type_file($path_destination_file);
				//--
				if(($out != 1) OR (!$chk)) {
					$err = 'ERROR: A JS-Script failed to be created: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} //end if else
				//--
			} elseif(((string)substr((string)$path_original_file, -4, 4) == '.css') AND ($tmp_is_a_file_and_needs_strip === true)) {
				//--
				$this->counters['css']++;
				//--
				echo '<span title="CSS: '.Smart::escape_html($path_original_file).'" style="color:#98BF21;cursor:default;">&hearts;</span>'."\n";
				Smart::InstantFlush();
				//--
				if($this->debug) {
					$this->add_to_log('Css-Stylesheet: '.$path_original_file);
				} //end if
				//--
				if((string)$mode == 'minify') { // minify code
					//--
					$the_compressor_signature = (string) 'UM';
					$tmp_arr = (array) CssOptimizer::minify_code($path_original_file);
					$tmp_content = (string) $tmp_arr['content'];
					$tmp_error = (string) $tmp_arr['error'];
					$tmp_arr = null;
					//--
				} else { // strip code (remove comments and standardize)
					//--
					$the_compressor_signature = 'CS'; // {{{SYNC-SIGNATURE-STRIP-COMMENTS}}}
					$tmp_content = (string) CssOptimizer::strip_code($path_original_file);
					$tmp_error = '';
					if((string)$tmp_content == '') {
						$tmp_error = 'ERROR: Empty Output from Css: '.$path_original_file;
					} //end if
					//--
				} //end if else
				//--
				if((strlen($tmp_error) > 0) OR ((string)$tmp_content == '')) {
					$err = 'ERROR: Failed to process a CSS-Stylesheet: '.$path_original_file.' @ ['.$tmp_error.']';
					return (string) $err;
				} //end if
				//--
				$out = SmartFileSystem::write(
					(string) $path_destination_file,
					'/* CSS-Stylesheet ('.$the_compressor_signature.'): '.Smart::base_name($path_original_file).' @ '.date('Y-m-d H:i:s O').' */'."\n".trim((string)$tmp_content)."\n".'/* #END */'."\n"
				);
				$chk = SmartFileSystem::is_type_file($path_destination_file);
				//--
				if(($out != 1) OR (!$chk)) {
					$err = 'ERROR: A CSS-Stylesheet failed to be created: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} //end if else
				//--
			} else {
				//--
				if((string)substr((string)$path_original_file, -4, 4) == '.php') { // php skipped scripts
					$this->counters['files-nostrip']++;
					$this->sfnostrip[] = (string) $path_original_file;
					echo '<span title="PHP *No-Strip* : '.Smart::escape_html($path_original_file).'" style="color:#E13DFC;cursor:default;">&clubs;</span>'."\n";
				} elseif(
					((string)substr((string)$path_original_file, -3, 3) == '.js')
					AND
					((string)substr((string)$path_original_file, -8, 8) != '.mtpl.js')
					AND
					((string)substr((string)$path_original_file, -7, 7) != '.inc.js')
				) { // js skipped scripts
					$this->counters['files-nostrip']++;
					$this->sfnostrip[] = (string) $path_original_file;
					echo '<span title="JS *No-Strip* : '.Smart::escape_html($path_original_file).'" style="color:#E13DFC;cursor:default;">&spades;</span>'."\n";
				} elseif((string)substr((string)$path_original_file, -4, 4) == '.css') { // css skipped scripts
					$this->counters['files-nostrip']++;
					$this->sfnostrip[] = (string) $path_original_file;
					echo '<span title="CSS *No-Strip* : '.Smart::escape_html($path_original_file).'" style="color:#E13DFC;cursor:default;">&hearts;</span>'."\n";
				} elseif((string)substr((string)$fname, 0, 1) == '.') { // dot files
					$this->counters['dot-files']++;
					echo '<span title="Dot File: '.Smart::escape_html($path_original_file).'" style="color:#E13DFC;cursor:default;">&diams;</span>'."\n";
				} else { // the rest of files
					$this->counters['other-files']++;
					echo '<span title="File: '.Smart::escape_html($path_original_file).'" style="color:#CCCCCC;cursor:default;">&diams;</span>'."\n";
				} //end if else
				//--
				Smart::InstantFlush();
				//--
				if($this->debug) {
					$this->add_to_log('Other File: '.$path_original_file);
				} //end if
				//--
				$out = SmartFileSystem::copy($path_original_file, $path_destination_file, false, true, 'no'); // don't overwrite destination, check copied content, allow absolute path
				$chk = SmartFileSystem::is_type_file($path_destination_file);
				//--
				if(($out != 1) OR (!$chk)) {
					$err = 'ERROR: A Misc. FILE failed to be copied: '.$path_original_file.' @ '.$path_destination_file;
					return (string) $err;
				} //end if else
				//--
			} //end if else
			//--
			if((string)substr((string)$path_original_file, -4, 4) == '.php') {
				$lint_chk = (string) PhpOptimizer::lint_code(Smart::real_path($path_destination_file));
				if($lint_chk) {
					$err = 'ERROR: A PHP FILE syntax check failed: '.Smart::real_path($path_destination_file).' @ ['.$lint_chk.']';
					return (string) $err;
				} //end if
			} elseif(
				((string)substr((string)$path_original_file, -3, 3) == '.js')
				AND
				((string)substr((string)$path_original_file, -8, 8) != '.mtpl.js')
				AND
				((string)substr((string)$path_original_file, -7, 7) != '.inc.js')
			) { // js skipped scripts
				$lint_chk = (string) JsOptimizer::lint_code(Smart::real_path($path_destination_file));
				if($lint_chk) {
					$err = 'ERROR: A JS-Script syntax check failed: '.Smart::real_path($path_destination_file).' @ ['.$lint_chk.']';
					return (string) $err;
				} //end if
			} elseif((string)substr((string)$path_original_file, -4, 4) == '.css') {
				$lint_chk = (string) CssOptimizer::lint_code(Smart::real_path($path_destination_file));
				if($lint_chk) {
					$err = 'ERROR: A CSS FILE syntax check failed: '.Smart::real_path($path_destination_file).' @ ['.$lint_chk.']';
					return (string) $err;
				} //end if
			} //end if else
			//--
		} //end if else
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//====================================================


	//====================================================
	private function optimize_dir(?string $dirsource, ?string $subdir=null) {

		//--
		if((string)$this->err != '') {
			return;
		} //end if
		//--

		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_MODE')) {
			$this->err = 'ERROR: A constant have not been defined: TASK_APP_RELEASE_CODEPACK_MODE';
			return;
		} //end if
		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR')) {
			$this->err = 'ERROR: A constant have not been defined: TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR';
			return;
		} //end if
		//--

		//-- comments
		$mode = (string) $this->get_mode();
		//--

		//--
		$dirsource = (string) Smart::safe_pathname((string)trim((string)$dirsource));
		$subdir = (string) Smart::safe_pathname((string)trim((string)$subdir));
		//--

		//--
		if((string)$subdir == '') {
			//--
			$this->counters['dirs']++;
			//--
			echo '<div style="color:#777777;"><b>Sources Folder: ['.Smart::escape_html($dirsource).'] : </b></div>';
			echo '<span title="AppCodePack :: START Processing Folder: '.Smart::escape_html($dirsource).'" style="color:#000000;text-weight:bold;cursor:default;"> &laquo;<b>&starf;</b>&raquo; </span>'."\n";
			//--
		} else {
			//--
			echo '<span title="Sources Sub-Folder: '.Smart::escape_html($dirsource.'/'.$subdir).'" style="color:#778899;text-weight:bold;cursor:default;"> &laquo;.&raquo; </span>'."\n";
			//--
		} //end if
		//--
		Smart::InstantFlush();
		//--

		//--
		if((string)$dirsource == '') {
			$this->err = 'ERROR: Empty Source Folder ...';
			return;
		} //end if
		//--

		//--
		$dirsource = (string) $dirsource;
		$dirrealpathsource = (string) Smart::real_path((string)$dirsource);
		//--
		if((string)substr((string)$dirsource, -1, 1) == '/') {
			$this->err = 'ERROR: Source Folder: `'.$dirsource.'` Trailing Slash MUST NOT BE USED !';
			return;
		} //end if
		//--
		if((SmartFileSystem::is_type_file($dirsource)) OR (SmartFileSystem::is_type_file($dirrealpathsource))) {
			$this->err = 'ERROR: Source Folder: `'.$dirsource.'` = `'.$dirrealpathsource.'` is a FILE !';
			return;
		} elseif((!SmartFileSystem::is_type_dir($dirsource)) OR (!SmartFileSystem::is_type_dir($dirrealpathsource))) {
			$this->err = 'ERROR: Source Folder: `'.$dirsource.'` = `'.$dirrealpathsource.'` does NOT EXISTS !';
			return;
		} //end if
		//--

		//--
		$dirdest = (string) trim((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR);
		if(!SmartFileSysUtils::checkIfSafePath((string)$dirdest)) {
			$this->err = 'ERROR: Destination Folder (1): '.$dirdest.' IS NOT VALID ! TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR=`'.$dirdest.'`';
			return;
		} //end if
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirdest); // must be relative path
		//--
		$dirdest = (string) SmartFileSysUtils::addPathTrailingSlash((string)SmartFileSysUtils::addPathTrailingSlash((string)$dirdest).trim((string)$dirsource, '/'));
		if(!SmartFileSysUtils::checkIfSafePath((string)$dirdest)) {
			$this->err = 'ERROR: Destination Folder (2): '.$dirdest.' IS NOT VALID ! TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR=`'.$dirdest.'`';
			return;
		} //end if
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirdest); // must be relative path
		//--

		//--
		$originaldirsource = (string) $dirsource; // preserve this for recurring
		$originalsubdir = '';
		if((string)$subdir != '') {
			$originalsubdir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$subdir); // relative path
			$dirdest = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirdest).$subdir; // relative path
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirdest); // must be relative path
			$dirsource = (string) SmartFileSysUtils::addPathTrailingSlash((string)SmartFileSysUtils::addPathTrailingSlash((string)$dirsource).trim((string)$subdir, '/')); // absolute path
			SmartFileSysUtils::raiseErrorIfUnsafePath((string)$dirsource); // must be relative path
		} //end if
		//--

		//--
		if((string)$subdir == '') {
			if(SmartFileSystem::path_exists($dirdest)) {
				$this->err = 'ERROR: Destination Folder ALREADY EXISTS: ['.Smart::base_name($dirdest).']';
				return;
			} //end if
		} //end if
		//--

		//--
		if($this->debug) {
			$this->add_to_log('Source Folder: '.$dirsource);
			$this->add_to_log('Destination Folder: '.$dirdest);
		} //end if
		//--

		//--
		if(!SmartFileSystem::have_access_read($dirsource)) {
			$this->err = 'ERROR: The Source Folder: ['.$dirsource.'] is not readable !';
			return;
		} //end if
		//--
		if($handle = opendir($dirsource)) {
			//--
			if((string)$subdir == '') {
				if(SmartFileSystem::path_exists($dirdest)) {
					$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] already exists !';
					return;
				} //end if
			} //end if
			//--
			SmartFileSystem::dir_create($dirdest, true);
			//--
			if(!SmartFileSystem::is_type_dir($dirdest)) {
				$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] could not be created !';
				return;
			} //end if
			if(!SmartFileSystem::have_access_write($dirdest)) {
				$this->err = 'ERROR: The Destination Folder: ['.$dirdest.'] is not writable !';
				return;
			} //end if
			//--
			while(false !== ($file = readdir($handle))) {
				//--
				if(
					((string)$file != '') AND ((string)$file != '.') AND ((string)$file != '..') AND ((string)$file != '/') AND
					(AppNetUnPackager::unpack_valid_file_name((string)$file) === true)
				) { // fix empty
					//--
					$tmp_path = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirsource).$file; // absolute path
					$tmp_dest = (string) SmartFileSysUtils::addPathTrailingSlash((string)$dirdest).$file; // relative path
					//--
					SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_path, false); // allow absolute paths
					SmartFileSysUtils::raiseErrorIfUnsafePath((string)$tmp_dest); // must be relative path
					//--
					if(SmartFileSystem::path_exists((string)$tmp_path)) {
						//--
						if(SmartFileSystem::path_exists((string)$tmp_path.'/'.'sf-dev-only.nopack')) { // absolute path
							//--
							$this->counters['dir-nopack']++; // SKIP !!!
							$this->sfnopack[] = (string) $tmp_path;
							//--
							echo '<span title="NO-PACK*DIR: '.Smart::escape_html((string)$tmp_path).'" style="color:#FF3300;cursor:default;">&laquo;&cross;&raquo;</span>'."\n";
							Smart::InstantFlush();
							//--
							if($this->debug) {
								$this->add_to_log('No-Pack Folder: '.$tmp_path);
							} //end if
							//--
						} else {
							//--
							if(!SmartFileSystem::is_type_dir((string)$tmp_path)) { // FILE
								//--
								$this->err = (string) $this->optimize_file((string)$tmp_path, (string)$tmp_dest);
								if((string)$this->err != '') {
									break;
								} //end if
								//--
							} else { // DIR
								//--
								$this->counters['dirs']++;
								//-- dir
								if($this->debug) {
									$this->add_to_log('Sub-Folder: '.$tmp_path);
								} //end if
								//--
								if(SmartFileSystem::is_type_file($tmp_dest)) {
									$this->err = 'ERROR: The destination Sub-Folder is a File: '.$tmp_dest;
									break;
								} //end if
								if(SmartFileSystem::is_type_dir($tmp_dest)) {
									$this->err = 'ERROR: The destination Sub-Folder already Exists: '.$tmp_dest;
									break;
								} //end if
								//--
								$out = SmartFileSystem::dir_create($tmp_dest, true); // recursive
								$chk = (bool) (SmartFileSystem::is_type_dir($tmp_dest) AND SmartFileSystem::have_access_write($tmp_dest));
								//--
								if(($out != 1) OR (!$chk)) {
									$this->err = 'ERROR: A Sub-Folder Failed to be Created: '.$tmp_dest;
									break;
								} //end if
								//--
								$this->optimize_dir((string)$originaldirsource, (string)$originalsubdir.$file);
								if((string)$this->err != '') {
									break; // bug fix !!!
								} //end if
								//--
							} //end if else
							//--
						} //end if else
						//--
					} //end if
					//--
				} //end if
				//--
			} //end while
			//--
			@closedir($handle);
			//--
		} else {
			//--
			$this->err = 'ERROR: The Folder: ['.$dirsource.'] is not accessible !';
			//--
		} //end if
		//--

	} //END FUNCTION
	//====================================================


	//===============================================================
	private function add_to_log(?string $message) {
		//--
		if(!$this->debug) {
			return;
		} //end if
		//--
		$message = str_replace(array("\n", "\r", "\t"), array(' ', ' ', ' '), (string)$message);
		//--
		$this->log .= $message."\n";
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
