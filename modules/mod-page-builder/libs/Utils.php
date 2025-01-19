<?php
// Class: \SmartModExtLib\PageBuilder\Utils
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\PageBuilder;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Class: PageBuilder Utils
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250107
 * @package 	PageBuilder
 *
 */
final class Utils {

	// ::

	private const REGEX_XPLACEHOLDERS 	= '/\{\{\:[^\:]*\:\}\}/';
	private const REGEX_PLACEHOLDERS 	= '/\{\{\:[A-Z0-9_\-\.]+\:\}\}/'; // {{{SYNC-PAGEBUILDER-REGEX-MARKERS-INT}}}
	private const REGEX_MARKERS 			= '/\{\{\=\#[A-Z0-9_\-\.]+(\|[a-z0-9]+)*\#\=\}\}/';


	public static function getDbType() {
		//--
		$type = '';
		//--
		if(\defined('\\SMART_PAGEBUILDER_DB_TYPE')) {
			if((string)\SMART_PAGEBUILDER_DB_TYPE == 'sqlite') {
				$type = 'sqlite';
			} elseif(((string)\SMART_PAGEBUILDER_DB_TYPE == 'pgsql') AND (\Smart::array_size(\Smart::get_from_config('pgsql')) > 0)) {
				$type = 'pgsql';
			} //end if
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION


	public static function allowPages() {
		//--
		$allow = true;
		//--
		if(\defined('\\SMART_PAGEBUILDER_DISABLE_PAGES')) {
			if(\SMART_PAGEBUILDER_DISABLE_PAGES === true) {
				$allow = false;
			} //end if
		} //end if
		//--
		return (bool) $allow;
		//--
	} //END FUNCTION


	public static function getAvailableLayouts() {
		//--
		$layouts = [];
		//--
		$layouts[''] = 'DEFAULT';
		//--
		$available_layouts = \Smart::get_from_config('pagebuilder.layouts');
		$cnt_available_layouts = (int) \Smart::array_size($available_layouts);
		if($cnt_available_layouts > 0) {
			if(\Smart::array_type_test($available_layouts) == 1) { // non-associative
				for($i=0; $i<$cnt_available_layouts; $i++) {
					$available_layouts[$i] = (string) \trim((string)$available_layouts[$i]);
					if((string)$available_layouts[$i] != '') {
						if(\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$available_layouts[$i])) {
							$layouts[(string)$available_layouts[$i]] = (string) $available_layouts[$i];
						} //end if
					} //end if
				} //end for
			} //end if
		} //end if
		//--
		return (array) $layouts;
		//--
	} //END FUNCTION


	public static function getFilesFolderRoot() {
		//--
		return (string) \Smart::safe_pathname('wpub/files-pbld/');
		//--
	} //END FUNCTION


	public static function getMediaFolderRoot() {
		//--
		return (string) \Smart::safe_pathname('wpub/media-pbld/');
		//--
	} //END FUNCTION


	public static function getMediaFolderByObjectId($y_id) {
		//-- {{{SYNC-PAGEBUILDER-ID-CONSTRAINTS}}}
		$y_id = (string) \trim((string)$y_id);
		//--
		$prefix = '1'; // page
		if(\strpos((string)$y_id, '#') === 0) {
			$prefix = '2'; // segment
			$y_id = (string) \substr((string)$y_id, 1); // remove the first # for segments
		} //end if
		$prefix = (string) \trim((string)$prefix, '/'); // dissalow path character by mistake, just in case
		if((string)$prefix == '') {
			$prefix = '@';
		} //end if
		//--
		$y_id = (string) \Smart::create_slug((string)$y_id, true, 63); // lowercase, max 63, output: a-z 0-9 _ - ; {{{SYNC-PAGEBUILDER-OBJECTID-FOLDER-CHARS}}}
		if((string)$y_id == '') {
			$y_id = '@';
		} //end if
		//-- 28 x 32000 = 896000 x 2 max objects (896000 pages and 896000 segments) = 1792000 ; if need to store more media than this use the alternative 'files-pbld' where the structure is free ; expand like this due to the constraint on many operating systems having max 32000 sub-folders in a folder
		$suffix = (string) \substr((string)\ltrim((string)$y_id, '_-'), 0, 1); // eliminate from prefix - or _ to get a letter or number ; when object is created is supposed to have the constraint of not containing only - and _
		$suffix = (string) \trim((string)$suffix, '/'); // dissalow path character by mistake, just in case
		if((string)$suffix == '') {
			$suffix = '@';
		} //end if
		//--
		$dir = (string) \Smart::safe_pathname(self::getMediaFolderRoot().\Smart::safe_filename((string)$prefix, '@').'/'.\Smart::safe_filename((string)$suffix, '@').'/'.\Smart::safe_filename((string)$y_id, '@').'/');
		//--
		if(\SmartFileSystem::is_type_dir((string)$dir)) {
			//-- create index files only if dir exists
			$level1 = (string) \Smart::safe_pathname(self::getMediaFolderRoot().\Smart::safe_filename((string)$prefix, '@').'/');
			if(!\SmartFileSystem::is_type_file((string)$level1.'index.html')) {
				\SmartFileSystem::write((string)$level1.'index.html', '');
			} //end if
			//--
			$level2 = (string) \Smart::safe_pathname(self::getMediaFolderRoot().\Smart::safe_filename((string)$prefix, '@').'/'.\Smart::safe_filename((string)$suffix, '@').'/');
			if(!\SmartFileSystem::is_type_file((string)$level2.'index.html')) {
				\SmartFileSystem::write((string)$level2.'index.html', '');
			} //end if
			//--
		} //end if
		//--
		return (string) $dir;
		//--
	} //END FUNCTION


	public static function getMediaFolderContent(?string $y_media_dir) : array {
		//--
		$arr_imgs = array();
		//--
		if(\SmartFileSysUtils::checkIfSafePath((string)$y_media_dir)) {
			if(\SmartFileSystem::is_type_dir((string)$y_media_dir)) {
				$files_n_dirs = (array) (new \SmartGetFileSystem(true))->get_storage((string)$y_media_dir, false, false);
				if((int)\Smart::array_size($files_n_dirs['list-files']) > 0) {
					for($i=0; $i<\Smart::array_size($files_n_dirs['list-files']); $i++) {
						$tmp_ext = (string) \SmartFileSysUtils::extractPathFileExtension((string)$files_n_dirs['list-files'][$i]);
						switch((string)$tmp_ext) {
							case 'svg':
							case 'gif':
							case 'png':
							case 'jpg':
							case 'webp':
								$arr_imgs[] = [
									'img' 	=> (string) $y_media_dir.$files_n_dirs['list-files'][$i],
									'file' 	=> (string) $files_n_dirs['list-files'][$i],
									'type' 	=> (string) $tmp_ext,
									'size' 	=> (string) \SmartUtils::pretty_print_bytes((int)\SmartFileSystem::get_file_size((string)$y_media_dir.$files_n_dirs['list-files'][$i]), 1, '')
								];
								break;
							default:
								// skip
						} //end switch
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return (array) $arr_imgs;
		//--
	} //END FUNCTION


	public static function fixSafeCode($y_html) {
		//--
		$y_html = (string) $y_html;
		//--
		$y_html = \Smart::commentOutPhpCode((string)$y_html); // comment out PHP code
		$y_html = \str_replace([' />', '/>'], ['>', '>'], $y_html); // cleanup XHTML tag style
		//--
		return (string) $y_html;
		//--
	} //END FUNCTION


	public static function extractPageBuilderSyntax(string $text) : array { // extract PageBuilder like syntax, approx., doesn't need to match exactly, it is for preserving the syntax and may be larger but reserved for future extensions
		//--
		$matches = array();
		$pcre = preg_match_all(
			'/(\{\{([\:]{1}|[\=%]{1}|[\=\#]{1}){1}){1}[^\s]*?((\2)\}\}){1}/s', // '/(\{\{([\:]{1}|[\=%]{1}|[\=\#]{1}){1}){1}[^\s]*((\2)\}\}){1}/sU', // will match: {{:.:}} ; {{=%.%=}} ; {{=#.#=}}
			(string) $text,
			$matches,
			\PREG_PATTERN_ORDER,
			0
		);
		if($pcre === false) {
			\Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		//--
		return (array) ((isset($matches[0]) && \is_array($matches[0])) ? $matches[0] : []);
		//--
	} //END FUNCTION


	public static function htmlValidatorOption() : string {
		//--
		if(\defined('\\SMART_PAGEBUILDER_HTML_VALIDATOR')) {
			return (string) \SMART_PAGEBUILDER_HTML_VALIDATOR;
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION


	public static function displayValidationErrors() : bool {
		//--
		$validopts = (string) self::htmlValidatorOption();
		//--
		if((string)$validopts != '') {
			return true;
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION


	public static function markdownRenderingGetOptionHtmlValidate(?string $option_validate_html=null) : ?string {
		//--
		if($option_validate_html === null) {
			$option_validate_html = (string) self::htmlValidatorOption();
		} //end if
		//--
		if((string)$option_validate_html == '') {
			return '';
		} //end if
		//--
		return (string) '<validate:html:'.str_replace(['<','>'], '', (string)$option_validate_html).'>'; // important: do not allow < or > to be able to inject other options ... strict the html validation is expected here
		//--
	} //END FUNCTION


	public static function renderMarkdown(?string $markdown_code, ?string $option_validate_html='', ?string $relative_url_prefix='', bool $log_render_notices=true, bool $y_lazyLoadImgDisabled=false) : string {
		//--
		// The default options are used on frontend rendering
		// as default should use '' not null for $option_validate_html
		// if $option_validate_html is set to null can use the override constant SMART_PAGEBUILDER_HTML_VALIDATOR
		// if $option_validate_html is set to '' will disable validation
		//--
		$option_validate_html = (string) self::markdownRenderingGetOptionHtmlValidate($option_validate_html); // do not cast method param, it may be null which changes the options !
		//--
		$syntax_pagebuiler = (array) self::extractPageBuilderSyntax((string)$markdown_code);
		//-- TODO: add constant to override compatibility mode ; by default is disabled here ...
		return (string) (new \SmartMarkdownToHTML(true, true, (bool)$y_lazyLoadImgDisabled, (string)$option_validate_html, (string)$relative_url_prefix, (bool)$log_render_notices, (array)$syntax_pagebuiler, false))->parse((string)$markdown_code); // C:0
		//--
	} //END FUNCTION


	public static function composePluginClassName($str) {
		//--
		$arr = (array) \explode('-', (string)$str);
		//--
		$class = '';
		//--
		for($i=0; $i<\Smart::array_size($arr); $i++) {
			//--
			$arr[$i] = (string) \trim((string)$arr[$i]);
			//--
			if((string)$arr[$i] != '') {
				//--
				$arr[$i] = (string) \strtolower((string)\Smart::safe_varname((string)$arr[$i])); // from camelcase to lower
				//--
				if((string)$arr[$i] != '') {
					$class .= (string) \ucfirst((string)$arr[$i]);
				} //end if
				//--
			} //end if
			//--
		} //end for
		//--
		return (string) $class;
		//--
	} //END FUNCTION


	public static function comparePlaceholdersAndMarkers($original_str, $transl_str) {
		//--
		$arr_placeholder_diffs 	= (array) self::comparePlaceholders($original_str, $transl_str);
		$arr_marker_diffs 		= (array) self::compareMarkers($original_str, $transl_str);
		//--
		return (array) \array_merge((array)$arr_placeholder_diffs, (array)$arr_marker_diffs);
		//--
	} //END FUNCTION


	public static function comparePlaceholders($original_str, $transl_str) {
		//--
		$original_arr 	= (array) self::extractPlaceholders((string)$original_str);
		$transl_arr 	= (array) self::extractPlaceholders((string)$transl_str);
		//--
		return (array) \array_diff($original_arr, $transl_arr);
		//--
	} //END FUNCTION


	public static function compareMarkers($original_str, $transl_str) {
		//--
		$original_arr 	= (array) self::extractMarkers((string)$original_str);
		$transl_arr 	= (array) self::extractMarkers((string)$transl_str);
		//--
		return (array) \array_diff($original_arr, $transl_arr);
		//--
	} //END FUNCTION


	public static function extractPlaceholders($str, $invalids=false) {
		//--
		if((string)\trim((string)$str) == '') {
			return array();
		} //end if
		//--
		if($invalids === true) {
			$re = (string) self::REGEX_XPLACEHOLDERS;
		} else {
			$re = (string) self::REGEX_PLACEHOLDERS;
		} //end if else
		//--
		$pcre = \preg_match_all((string)$re, (string)$str, $matches);
		if($pcre === false) {
			\Smart::log_warning(__METHOD__.'() # ERROR: '.\SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		$arr = (array) \Smart::array_sort((array)$matches[0], 'natcasesort');
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function extractMarkers($str) {
		//--
		if((string)\trim((string)$str) == '') {
			return array();
		} //end if
		//--
		$re = (string) self::REGEX_MARKERS;
		//--
		$pcre = \preg_match_all((string)$re, (string)$str, $matches);
		if($pcre === false) {
			\Smart::log_warning(__METHOD__.'() # ERROR: '.\SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return array();
		} //end if
		$arr = (array) \Smart::array_sort((array)$matches[0], 'natcasesort');
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function prepareCodeData(?string $str, bool $remove_trailing_spaces, bool $indent_with_tabs=false) {
		//--
		$str = (string) \trim((string)$str);
		if((string)$str == '') {
			return '';
		} //end if
		//--
		$remove_trailing_spaces = (bool) $remove_trailing_spaces;
		//--
		$str = (string) \str_replace(["\r\n", "\r"], "\n", (string)$str); 		// normalize line endings
		$str = (string) \str_replace(["\x0B", "\0", "\f"], ' ', (string)$str); 	// fix weird characters
		if($remove_trailing_spaces !== false) {
			$str = (string) \preg_replace('/([ ]|\t)+[\\n]/', "\n", (string)$str); 	// remove trailing line spaces from inside empty lines because outside empty lines are trimmed (not for YAML code)
		} //end if
		if($indent_with_tabs === true) {
			$str = (string) \preg_replace_callback('/^([ ]|\t)+/m', function($matches) {
				$matches[0] = (string) ($matches[0] ?? '');
				$matches[0] = (string) \str_replace("\t", '    ', (string)$matches[0]); // replace all tabs with a group of 4 spaces, to have all as spaces ...
				$matches[0] = (string) \str_replace('    ', "\t", (string)$matches[0]); // replace each group of 4 spaces with a tab
				return (string) $matches[0];
			}, (string)$str); // replace all indentation spaces (grouped as 4) with a tab for each group
		} //end if
		//--
		return (string) \trim((string)$str);
		//--
	} //END FUNCTION


	public static function getParsedPluginSettingsFromValueOrDefinedConstant($param_sett) : array {
		//--
		// $param_sett is MIXED: can be nScalar or Array but the type counts !
		//--
		$out = [
			'value' => null,
			'error' => '??',
		];
		//--
		if((string)\substr((string)$param_sett, 0, 7) == '!const:') {
			$param_sett = (string) \strtoupper((string)\trim((string)\substr((string)$param_sett, 7)));
			if((string)$param_sett == '') {
				$out['error'] = 'Constant is #EMPTY#';
				return (array) $out;
			} //end if
			if(
				((string)\substr((string)$param_sett, 0, 29) != 'PAGEBUILDER_PLUGIN_SETTINGS__')
				OR
				((string)\substr((string)$param_sett, -1, 1) == '_')
				OR
				(!\preg_match('/^[A-Z0-9_]+$/', (string)$param_sett))
			) {
				$out['error'] = 'Constant Name is Dissalowed: `'.$param_sett.'`';
				return (array) $out;
			} //end if
			if(!\defined('\\'.$param_sett)) {
				$out['error'] = 'Constant is N/A: `'.$param_sett.'`';
				return (array) $out;
			} //end if
			$out['value'] = \constant('\\'.$param_sett); // mixed
		} else {
			$out['value'] = $param_sett; // mixed, as is
		} //end if
		//--
		$out['error'] = ''; // reset
		return (array) $out;
		//--
	} //END FUNCTION


	public static function fixPageBuilderCodeBeforeValidation(?string $html_code) : string {
		//--
		$validation_placeholders = (array) self::extractPlaceholders((string)$html_code);
		foreach($validation_placeholders as $phkey => $phval) {
			$html_code = (string) \strtr((string)$html_code, [ (string)$phval => (string)\SmartHashCrypto::crc32b((string)$phval) ]); // tidy gives error if the url is like {{:PLACEHOLDER:}} thus fake it and replace with a small hash ;-)
		} //end foreach
		$validation_placeholders = null;
		//--
		$validation_markers = (array) self::extractMarkers((string)$html_code);
		foreach($validation_markers as $mkkey => $mkval) {
			$html_code = (string) \strtr((string)$html_code, [ (string)$mkval => (string)\SmartHashCrypto::crc32b((string)$mkval) ]); // tidy gives error if the url is like {{=#MARKER|html#=}} thus fake it and replace with a small hash ;-)
		} //end foreach
		$validation_markers = null;
		//--
		$html_code = (string) \strtr((string)$html_code, [ // {{{SYNC-PAGEBUILDER-FAKE-TIDY}}} tidy does not like these, .. fake tidy, browsers support them !
			'src=""' 			=> 'src="data:,"', // ex: for lazyload images
			'align="left"' 		=> '', // ex: for img
			'align="center"' 	=> '', // ex: for img
			'align="right"' 	=> '', // ex: for img
			'<center>' 			=> '',
			'</center>' 		=> '',
		]);
		//--
		return (string) $html_code;
		//--
	} //END FUNCTION


	public static function getRenderedMarkdownNotices(string $markdown_code) : array {
		//--
		// the constant SMART_PAGEBUILDER_HTML_VALIDATOR must be defined for this method
		//--
		$option_validate_html = (string) self::markdownRenderingGetOptionHtmlValidate(); // here, validator cannot be null
		//--
		if($option_validate_html !== null) {
			if((string)$option_validate_html == '') {
				return (array) [
					'validator' => '',
					'notices' 	=> [],
				];
			} //end if
		} //end if
		//--
	//	$syntax_pagebuiler = (array) self::extractPageBuilderSyntax((string)$markdown_code);
	//	$obj = new \SmartMarkdownToHTML(true, true, true, (string)$option_validate_html, null, false, (array)$syntax_pagebuiler, false); // C:0 ; disable lazyload images, tidy does not like empty src attribute on images
		//--
		$obj = new \SmartMarkdownToHTML(true, true, true, (string)$option_validate_html, null, false, null, false); // C:0 ; disable lazyload images, tidy does not like empty src attribute on images
		$markdown_code = (string) \SmartModExtLib\PageBuilder\Utils::fixPageBuilderCodeBeforeValidation((string)$markdown_code); // is better to fake marker replacement with hashes ... than to keep markers, the values are not known here (as above)
		//--
		$markdown_code = (string) \strtr((string)$markdown_code, [ // {{{SYNC-PAGEBUILDER-FAKE-TIDY}}} tidy does not like these, .. fake tidy, browsers support them !
		//	'src=""' => '',
			'@align=left' 		=> '@data-align=left', // ex: for img
			'@align=center' 	=> '@data-align=center', // ex: for img
			'@align=right' 		=> '@data-align=right', // ex: for img
		]);
		//--
		$obj->parse((string)$markdown_code);
		//--
	//	$syntax_pagebuiler = null;
		//--
		return (array) [
			'validator' => (string) $obj->validator(),
			'notices' 	=> (array)  $obj->notices(),
		];
		//--
	} //END FUNCTION


	//==================================================================
	public static function renderNotices(array $arr_notices, string $title, string $subtitle) : string {
		//--
		$html = '';
		//--
		if(\Smart::array_size($arr_notices) > 0) {
			//--
			$html = '<div style="max-height:70px; overflow:auto;"><ul>'; // {{{SYNC-PAGEBUILDER-NOTIFICATIONS-HEIGHT}}}
			//--
			foreach($arr_notices as $rnKey => $rnVal) {
				$html .= '<li>';
				if(\is_array($rnVal)) {
					$html .= (string) \Smart::escape_html((string)$subtitle).':<br>';
					$html .= '<ul>'."\n";
					foreach($rnVal as $rnXKey => $rnXVal) {
						$html .= (string) '<li>'.\Smart::escape_html((string)$rnXVal).'</li>'."\n";
					} //end foreach
					$html .= '</ul>'."\n";
				} else {
					$html .= (string) \Smart::escape_html((string)$rnVal);
				} //end if else
				$html .= '</li>'."\n";
			} //end foreach
			//--
			$html .= '</ul></div>'."\n";
			//--
			$html = (string) \SmartComponents::operation_notice('NOTICE: '.\Smart::escape_html((string)$title).':<br>'.$html, '92%');
			//--
		} //end if
		//--
		return (string) $html;
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
