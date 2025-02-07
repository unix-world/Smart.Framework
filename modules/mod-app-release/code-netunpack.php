<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/CodeNetUnpack
// Route: ?/page/app-release.code-netunpack (?page=app-release.code-netunpack)
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// SMART_APP_MODULE_DIRECT_OUTPUT :: TRUE :: # by parent class

define('SMART_APP_MODULE_AREA', 'TASK');
define('SMART_APP_MODULE_AUTH', true);
define('SMART_APP_MODULE_AUTOLOAD', true);


/**
 * Task Controller: Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AppRelease\AbstractTaskController {

	protected $title = 'Generate the AppCodeUnPack Manager Standalone Script';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';

	protected $goback = '';

	protected $details = false;

	protected $working = true;
	protected $workstop = false;
	protected $endscroll = false;


	public function Run() {

		//--
		$cleanup = (string) $this->RequestVarGet('cleanup', '', 'string');
		//--

		//--
		$appid = (string) $this->getAppId();
		if((string)$appid == '') {
			$this->err = 'App ID is Empty';
			return;
		} //end if
		//--

		//--
		$this->goback = (string) $this->ControllerGetParam('url-script').'?page='.$this->ControllerGetParam('module').'.app-manage&appid='.Smart::escape_url((string)$appid);
		//--

		//--
		if(!defined('APP_DEPLOY_URLS')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_URLS';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_URLS) == '') {
			$this->err = 'A required constant: APP_DEPLOY_URLS is empty';
			return;
		} //end if
		//--
		if(!defined('APP_DEPLOY_AUTH_USERNAME')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_AUTH_USERNAME';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_AUTH_USERNAME) == '') {
			$this->err = 'A required constant: APP_DEPLOY_AUTH_USERNAME is empty';
			return;
		} //end if
		//--
		if(!defined('APP_DEPLOY_AUTH_PASSWORD')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_AUTH_PASSWORD';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_AUTH_PASSWORD) == '') {
			$this->err = 'A required constant: APP_DEPLOY_AUTH_PASSWORD is empty';
			return;
		} //end if
		//--

		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_APP_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_APP_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)TASK_APP_RELEASE_CODEPACK_APP_DIR)) {
			$this->err = 'The release app folder have an invalid path ...';
			return;
		} //end if
		//--
		if(!defined('TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR')) {
			$this->err = 'A required constant is missing: TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR';
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR)) {
			$this->err = 'The optimizations folder have an invalid path ...';
			return;
		} //end if
		//--

		//--
		if($cleanup) {
			if(SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
				if(!SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
					$this->err = 'Cleanup: Invalid path, not a file ...';
					return;
				} //end if
				SmartFileSystem::delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
				if(SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
					$this->err = 'Cleanup: Failed to remove the file on disk ...';
					return;
				} //end if
			} //end if
			$this->sficon = [
				'bin2',
				'codepen',
			];
			$this->msg = 'AppCodeUnPack Manager Standalone Script Cleanup DONE: `appcodeunpack.php`';
			return;
		} //end if
		//--

		//--
		if(!SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log')) {
			$this->err = 'The optimizations folder exists but optimizations may have not been completed ...';
			return;
		} //end if
		if((string)SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'optimization-errors.log') !== '#NULL') {
			$this->err = 'The optimizations error log is not clean: `optimization-errors.log`';
			return;
		} //end if
		//--

		//--
		if(SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
			$this->sficon = 'codepen';
			$this->notice = 'The AppCodeUnPack Manager Standalone Script already generated: `appcodeunpack.php`';
			return;
		} //end if
		//--

		//--
		if(!SmartFileSystem::is_type_dir((string)TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.'lib')) {
			$this->err = 'Cannot generate the AppCodeUnPack Manager Standalone Script, optimizations folder `lib/` is missing !';
			return;
		} //end if
		//--

		//--
		$arr_svgs = [ // supports only 1 per key, cannot combine
			'APPCODEUNPACK_LOGO_SVG' 			=> 'modules/mod-app-release/views/img/appcodeunpack.svg',
			'APPCODEUNPACK_LOADING_SVG' 		=> 'lib/framework/img/loading-cylon.svg',
			'APPCODEUNPACK_LOGO_NETARCH_SVG' 	=> 'lib/framework/img/netarch-logo.svg',
			'APPCODEUNPACK_LOGO_PHP_SVG' 		=> 'lib/framework/img/php-logo.svg',
			'APPCODEUNPACK_LOGO_APACHE_SVG' 	=> 'lib/framework/img/apache-logo.svg',
			'APPCODEUNPACK_LOGO_SF_SVG' 		=> 'lib/framework/img/sf-logo.svg',
		];
		//--

		//--
		$arr_tpls = [
			'APPCODEUNPACK_HTML_WATCH' 				=> 'lib/core/templates/canvas-clock.inc.htm',
			'APPCODEUNPACK_HTML_ERRTPL' 			=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-err.htm',
			'APPCODEUNPACK_HTML_TPL' 				=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-tpl.htm',
			'APPCODEUNPACK_LOCAL_STYLES' 			=> 'modules/mod-app-release/views/partials/app-release-styles.inc.htm',
			'APPCODEUNPACK_HTML_DEPLOY' 			=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-deploy.inc.htm',
			'APPCODEUNPACK_HTML_LIST_DEPLOYS_TPL' 	=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-deploys-list.inc.htm',
			'APPCODEUNPACK_HTML_LIST_LOGS_TPL' 		=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-tpl-logs-list.inc.htm',
		];
		//--
		$arr_css = [
			'APPCODEUNPACK_BASE_STYLES' 		=> 'lib/css/default.css',
			'APPCODEUNPACK_TOOLKIT_STYLES' 		=> [ 'lib/css/toolkit/ux-toolkit.css', 'lib/css/toolkit/ux-toolkit-responsive.css' ],
			'APPCODEUNPACK_NOTIFICATION_STYLES' => 'lib/core/css/notifications.css',
			'APPCODEUNPACK_CSS_ALERTABLE' 		=> 'lib/js/jquery/jquery.alertable.css',
			'APPCODEUNPACK_CSS_GRITTER' 		=> 'modules/mod-auth-admins/views/js/gritter/jquery.gritter.css',
			'APPCODEUNPACK_CSS_LOCAL_FX' 		=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-styles.css',
		];
		//--
		$arr_js = [
			'APPCODEUNPACK_JS_JQUERY' 			=> [ 'lib/js/jquery/jquery.js', 'lib/js/jquery/jquery.smart.compat.js' ],
			'APPCODEUNPACK_JS_SMART_UTILS' 		=> 'lib/js/framework/src/core_utils.js',
			'APPCODEUNPACK_JS_SMART_DATE' 		=> 'lib/js/framework/src/date_utils.js',
			'APPCODEUNPACK_JS_SMART_CRYPTO' 	=> 'lib/js/framework/src/crypt_utils.js',
			'APPCODEUNPACK_JS_ALERTABLE' 		=> 'lib/js/jquery/jquery.alertable.js',
			'APPCODEUNPACK_JS_WATCH' 			=> 'lib/js/jswclock/smart-watch.js',
			'APPCODEUNPACK_JS_GRITTER' 			=> 'modules/mod-auth-admins/views/js/gritter/jquery.gritter.js',
			'APPCODEUNPACK_JS_LOCAL_FX' 		=> 'modules/mod-app-release/appcodeunpack/appcodeunpack-functions.js',
		];
		//--

		//--
		$arr_php = [ // {{{SYNC-SMART-FRAMEWORK-LIBS-ORDER}}}
			//--
			'lib/framework/smart-error-handler.php' 						=> true,
			//--
			'lib/framework/lib_unicode.php'									=> true,
			'lib/framework/lib_security.php'								=> true,
			'lib/framework/lib_smart.php'									=> true,
			'lib/framework/lib_caching.php'									=> true,
			'lib/framework/lib_cryptohs.php'								=> true,
			'lib/framework/lib_cryptoas.php'								=> true,
			'lib/framework/lib_templating.php'								=> true,
			'lib/framework/lib_valid_parse.php'								=> true,
			'lib/framework/lib_http_cli.php'								=> true,
			'lib/framework/lib_auth.php'									=> true,
			//--
			'lib/lib_registry.php'											=> true,
			//--
			'lib/core/lib_filesys.php'										=> true,
			'lib/core/lib_utils.php'										=> true,
			//--
			'modules/mod-app-release/lib/AppNetUnPackager.php'				=> true,
			'modules/mod-app-release/appcodeunpack/appcodeunpack-app.php'	=> false, // do not minify !
			//--
		];
		//--

		//--
		$hash = (string) SmartHashCrypto::crc32b((string)Smart::uuid_32());
		//--
		$appcodeunpack = [];
		$appcodeunpack[] = '<'.'?php';
		$appcodeunpack[] = '// Smart.Framework / Standalone Runtime / Task / AppCodeUnpack :: Release Deploy Manager';
		$appcodeunpack[] = '// (c) 2006-'.Smart::normalize_spaces((string)date('Y')).' unix-world.org - all rights reserved';
		$appcodeunpack[] = '// '.Smart::normalize_spaces((string)(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '').' / '.(defined('SMART_FRAMEWORK_VERSION') ? SMART_FRAMEWORK_VERSION : '').' @ '.(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : ''));
		$appcodeunpack[] = '// #START: appcodeunpack.php ['.Smart::normalize_spaces((string)$hash).']';
		$appcodeunpack[] = '';
		//--
		$appcodeunpack[] = "ini_set('display_errors', '1'); // enable at bootstrap, will be disabled later";
		$appcodeunpack[] = '';
		//--
		$appcodeunpack[] = "const APP_CUSTOM_STANDALONE_DTIME = '".Smart::normalize_spaces((string)date('Y-m-d H:i:s O'))."';";
		$appcodeunpack[] = "const APP_CUSTOM_STANDALONE_RHASH = '".Smart::normalize_spaces((string)$hash)."';";
		$appcodeunpack[] = "const APP_CUSTOM_LOG_PATH = '#APPCODE-UNPACK#/'; // {{{SYNC-APPCODEUNPACK-FOLDER}}}";
		$appcodeunpack[] = "if((!is_file('appcodeunpack-init.php')) OR (!is_readable('appcodeunpack-init.php'))) {";
		$appcodeunpack[] = "\t"."@http_response_code(500); die('AppCodeUnpack Init is N/A !');";
		$appcodeunpack[] = "} //end if";
		$appcodeunpack[] = "require('appcodeunpack-init.php'); // mandatory, init settings, must keep separate";
		$appcodeunpack[] = '';
		//--

		//--
		$key = null;
		$val = null;
		//--

		//-- SVGs
		$tmp_arr_content = null;
		$tmp_content = null;
		$uuid = null;
		foreach((array)$arr_svgs as $key => $val) {
			if(!is_array($val)) {
				$val = [ (string) $val ];
			} //end if
			if((Smart::array_size($val) != 1) || (Smart::array_type_test($val) != 1)) { // here only 1 level is supported, cannot combine many svgs !
				$this->err = 'Invalid svg settings for key ['.$key.']: `'.print_r($val,1).'` ...';
				return;
				break;
			} //end if
			$tmp_arr_content = [];
			$tmp_content = '';
			for($i=0; $i<Smart::array_size($val); $i++) {
				$tmp_path = (string) $val[$i];
				if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
					$this->err = 'Failed to include svg file for key ['.$key.']: `'.$tmp_path.'` ... Path is not safe !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::is_type_file((string)$tmp_path)) {
					$this->err = 'Failed to include svg file for key ['.$key.']: `'.$tmp_path.'` ... NOT file type !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::have_access_read((string)$tmp_path)) {
					$this->err = 'Failed to include svg file for key ['.$key.']: `'.$tmp_path.'` ... NOT readable !';
					return;
					break;
				} //end if
				$tmp_content = (string) SmartFileSystem::read((string)$tmp_path); // read from source, not optimized, they are plain files ; it is because some SVGs are not includded in the list of optimized files
				if((string)trim((string)$tmp_content) == '') {
					$this->err = 'Failed to include svg file for key ['.$key.']: `'.$tmp_path.'` ... Content is EMPTY !';
					return;
					break;
				} //end if
				$tmp_arr_content[] = (string) trim((string)$tmp_content);
				$tmp_content = '';
			} //end for
			$tmp_content = (string) implode("\n\n", (array)$tmp_arr_content);
			$tmp_arr_content = [];
			$uuid = (string) sha1((string)$tmp_content);
			$uuid = 'SVG_'.$uuid.'_XML';
			$appcodeunpack[] = '';
			$appcodeunpack[] = '// SVG(s): '.Smart::normalize_spaces((string)implode(' ; ', (array)$val));
			$appcodeunpack[] = 'const '.$key.' = <<<'."'".Smart::normalize_spaces((string)$uuid)."'";
			$appcodeunpack[] = (string) $tmp_content;
			$appcodeunpack[] = (string) Smart::normalize_spaces((string)$uuid).';';
			$appcodeunpack[] = '';
			$tmp_content = '';
			$uuid = '';
		} //end foreach
		$uuid = null;
		$tmp_arr_content = null;
		$tmp_content = null;
		//--

		//-- TPLs
		$tmp_arr_content = null;
		$tmp_content = null;
		$uuid = null;
		foreach((array)$arr_tpls as $key => $val) {
			if(!is_array($val)) {
				$val = [ (string) $val ];
			} //end if
			if((Smart::array_size($val) <= 0) || (Smart::array_type_test($val) != 1)) {
				$this->err = 'Invalid tpl settings for key ['.$key.']: `'.print_r($val,1).'` ...';
				return;
				break;
			} //end if
			$tmp_arr_content = [];
			$tmp_content = '';
			for($i=0; $i<Smart::array_size($val); $i++) {
				$tmp_path = (string) $val[$i];
				if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
					$this->err = 'Failed to include tpl file for key ['.$key.']: `'.$tmp_path.'` ... Path is not safe !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::is_type_file((string)$tmp_path)) {
					$this->err = 'Failed to include tpl file for key ['.$key.']: `'.$tmp_path.'` ... NOT file type !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::have_access_read((string)$tmp_path)) {
					$this->err = 'Failed to include tpl file for key ['.$key.']: `'.$tmp_path.'` ... NOT readable !';
					return;
					break;
				} //end if
				$tmp_content = (string) SmartFileSystem::read((string)$tmp_path); // read from source, not optimized, they are plain files ; it is because some TPLs are not includded in the list of optimized files
				if((string)trim((string)$tmp_content) == '') {
					$this->err = 'Failed to include tpl file for key ['.$key.']: `'.$tmp_path.'` ... Content is EMPTY !';
					return;
					break;
				} //end if
				$tmp_arr_content[] = (string) trim((string)$tmp_content);
				$tmp_content = '';
			} //end for
			$tmp_content = (string) implode("\n\n", (array)$tmp_arr_content);
			$tmp_arr_content = [];
			$uuid = (string) sha1((string)$tmp_content);
			$uuid = 'TPL_'.$uuid.'_HTML';
			$appcodeunpack[] = '';
			$appcodeunpack[] = '// TPL(s): '.Smart::normalize_spaces((string)implode(' ; ', (array)$val));
			$appcodeunpack[] = 'const '.$key.' = <<<'."'".Smart::normalize_spaces((string)$uuid)."'";
			$appcodeunpack[] = (string) $tmp_content;
			$appcodeunpack[] = (string) Smart::normalize_spaces((string)$uuid).';';
			$appcodeunpack[] = '';
			$tmp_content = '';
			$uuid = '';
		} //end foreach
		$uuid = null;
		$tmp_arr_content = null;
		$tmp_content = null;
		//--

		//-- CSS
		$tmp_arr_content = null;
		$tmp_content = null;
		$uuid = null;
		foreach((array)$arr_css as $key => $val) {
			if(!is_array($val)) {
				$val = [ (string) $val ];
			} //end if
			if((Smart::array_size($val) <= 0) || (Smart::array_type_test($val) != 1)) {
				$this->err = 'Invalid css settings for key ['.$key.']: `'.print_r($val,1).'` ...';
				return;
				break;
			} //end if
			$tmp_arr_content = [];
			$tmp_content = '';
			for($i=0; $i<Smart::array_size($val); $i++) {
				if(strpos((string)$key, 'APPCODEUNPACK_CSS_LOCAL_') === 0) {
					$tmp_path = (string) $val[$i]; // these are local, there is a little chance to be found as minified in normal conditions ...
				} else {
					$tmp_path = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$val[$i]; // should be already minified
				} //end if else
				if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
					$this->err = 'Failed to include css file for key ['.$key.']: `'.$tmp_path.'` ... Path is not safe !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::is_type_file((string)$tmp_path)) {
					$this->err = 'Failed to include css file for key ['.$key.']: `'.$tmp_path.'` ... NOT file type !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::have_access_read((string)$tmp_path)) {
					$this->err = 'Failed to include css file for key ['.$key.']: `'.$tmp_path.'` ... NOT readable !';
					return;
					break;
				} //end if
				if(strpos((string)$key, 'APPCODEUNPACK_CSS_LOCAL_') === 0) {
					$lint_err = (string) CssOptimizer::lint_code((string)Smart::real_path((string)$tmp_path));
					if((string)$lint_err != '') {
						$this->err = 'Lint Failed on css file for key ['.$key.']: `'.$tmp_path.'` with ERRORS: '.$lint_err;
						return;
						break;
					} //end if
					$tmp_content = (string) CssOptimizer::strip_code((string)$tmp_path); // read from source, not optimized, and strip
				} else {
					$tmp_content = (string) SmartFileSystem::read((string)$tmp_path); // read the minified
				} //end if else
				if((string)trim((string)$tmp_content) == '') {
					$this->err = 'Failed to include css file for key ['.$key.']: `'.$tmp_path.'` ... Content is EMPTY !';
					return;
					break;
				} //end if
				if(strpos((string)$key, 'APPCODEUNPACK_CSS_LOCAL_') === 0) {
					$tmp_content = '/* CSS-Stylesheet (CS): '.Smart::normalize_spaces((string)Smart::base_name((string)$val[$i])).' @ '.Smart::normalize_spaces((string)date('Y-m-d H:i:s O')).' */'."\n".trim((string)$tmp_content)."\n".'/* #END */'."\n";
				} //end if
				$tmp_arr_content[] = (string) trim((string)$tmp_content);
				$tmp_content = '';
			} //end for
			$tmp_content = (string) implode("\n\n", (array)$tmp_arr_content);
			$tmp_arr_content = [];
			$uuid = (string) sha1((string)$tmp_content);
			$uuid = 'CSS_'.$uuid.'_HTML';
			$appcodeunpack[] = '';
			$appcodeunpack[] = '// CSS(s): '.Smart::normalize_spaces((string)implode(' ; ', (array)$val));
			$appcodeunpack[] = 'const '.$key.' = <<<'."'".Smart::normalize_spaces((string)$uuid)."'";
			$appcodeunpack[] = '<style>';
			$appcodeunpack[] = (string) $tmp_content;
			$appcodeunpack[] = '</style>';
			$appcodeunpack[] = (string) Smart::normalize_spaces((string)$uuid).';';
			$appcodeunpack[] = '';
			$tmp_content = '';
			$uuid = '';
		} //end foreach
		$uuid = null;
		$tmp_arr_content = null;
		$tmp_content = null;
		//--

		//-- JS
		$tmp_arr_content = null;
		$tmp_content = null;
		$uuid = null;
		foreach((array)$arr_js as $key => $val) {
			if(!is_array($val)) {
				$val = [ (string) $val ];
			} //end if
			if((Smart::array_size($val) <= 0) || (Smart::array_type_test($val) != 1)) {
				$this->err = 'Invalid js settings for key ['.$key.']: `'.print_r($val,1).'` ...';
				return;
				break;
			} //end if
			$tmp_arr_content = [];
			$tmp_content = '';
			for($i=0; $i<Smart::array_size($val); $i++) {
				if(strpos((string)$key, 'APPCODEUNPACK_JS_LOCAL_') === 0) {
					$tmp_path = (string) $val[$i]; // these are local, there is a little chance to be found as minified in normal conditions ...
				} else {
					$tmp_path = (string) TASK_APP_RELEASE_CODEPACK_DESTINATION_DIR.$val[$i]; // should be already minified
				} //end if else
				if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
					$this->err = 'Failed to include js file for key ['.$key.']: `'.$tmp_path.'` ... Path is not safe !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::is_type_file((string)$tmp_path)) {
					$this->err = 'Failed to include js file for key ['.$key.']: `'.$tmp_path.'` ... NOT file type !';
					return;
					break;
				} //end if
				if(!SmartFileSystem::have_access_read((string)$tmp_path)) {
					$this->err = 'Failed to include js file for key ['.$key.']: `'.$tmp_path.'` ... NOT readable !';
					return;
					break;
				} //end if
				if(strpos((string)$key, 'APPCODEUNPACK_JS_LOCAL_') === 0) {
					//--
					$lint_err = (string) JsOptimizer::lint_code((string)Smart::real_path((string)$tmp_path));
					if((string)$lint_err != '') {
						$this->err = 'Lint Failed on css file for key ['.$key.']: `'.$tmp_path.'` with ERRORS: '.$lint_err;
						return;
						break;
					} //end if
					//--
					$jsminmarkup = '';
					if(defined('TASK_APP_RELEASE_CODEPACK_NODEJS_BIN') && ((string)TASK_APP_RELEASE_CODEPACK_NODEJS_BIN != '')) { // {{{SYNC-SIGNATURE-STRIP-NODE-COMMENTS}}} strip code using nodejs (if there is nodejs defined prefer strip using this)
						$jsminmarkup = 'US';
						$tmp_arr = (array) JsOptimizer::minify_code((string)Smart::real_path((string)$tmp_path), 'strip');
						$tmp_content = (string) $tmp_arr['content'];
						if((string)$tmp_arr['error'] != '') {
							$this->err = 'Failed to include js file for key ['.$key.']: `'.$tmp_path.'` ... '.$tmp_arr['error'];
							return;
							break;
						} //end if
						$tmp_arr = null;
					} else {
						$jsminmarkup = 'CS';
						$tmp_content = (string) JsOptimizer::strip_code((string)$tmp_path); // read from source, not optimized, and strip
					} //end if else
					//--
				} else {
					$tmp_content = (string) SmartFileSystem::read((string)$tmp_path); // read the minified
				} //end if else
				if((string)trim((string)$tmp_content) == '') {
					$this->err = 'Failed to include js file for key ['.$key.']: `'.$tmp_path.'` ... Content is EMPTY !';
					return;
					break;
				} //end if
				if(strpos((string)$key, 'APPCODEUNPACK_JS_LOCAL_') === 0) {
					$tmp_content = '// JS-Script ('.$jsminmarkup.'): '.Smart::normalize_spaces((string)Smart::base_name((string)$val[$i])).' @ '.Smart::normalize_spaces((string)date('Y-m-d H:i:s O'))."\n".trim((string)$tmp_content)."\n".'// #END'."\n";
				} //end if
				$tmp_arr_content[] = (string) trim((string)$tmp_content);
				$tmp_content = '';
			} //end for
			$tmp_content = (string) implode("\n\n", (array)$tmp_arr_content);
			$tmp_arr_content = [];
			$uuid = (string) sha1((string)$tmp_content);
			$uuid = 'JS_'.$uuid.'_HTML';
			$appcodeunpack[] = '';
			$appcodeunpack[] = '// JS(s): '.Smart::normalize_spaces((string)implode(' ; ', (array)$val));
			$appcodeunpack[] = 'const '.$key.' = <<<'."'".Smart::normalize_spaces((string)$uuid)."'";
			$appcodeunpack[] = '<script>';
			$appcodeunpack[] = (string) $tmp_content;
			$appcodeunpack[] = '</script>';
			$appcodeunpack[] = (string) Smart::normalize_spaces((string)$uuid).';';
			$appcodeunpack[] = '';
			$tmp_content = '';
			$uuid = '';
		} //end foreach
		$uuid = null;
		$tmp_arr_content = null;
		$tmp_content = null;
		//--

		//-- PHPs
		$tmp_content = null;
		foreach($arr_php as $key => $val) {
			$tmp_path = (string) $key;
			if(!SmartFileSysUtils::checkIfSafePath((string)$tmp_path)) {
				$this->err = 'Failed to include php file: `'.$tmp_path.'` ... Path is not safe !';
				return;
				break;
			} //end if
			if(!SmartFileSystem::is_type_file((string)$tmp_path)) {
				$this->err = 'Failed to include php file: `'.$tmp_path.'` ... NOT file type !';
				return;
				break;
			} //end if
			if(!SmartFileSystem::have_access_read((string)$tmp_path)) {
				$this->err = 'Failed to include php file: `'.$tmp_path.'` ... NOT readable !';
				return;
				break;
			} //end if
			if($val === true) {
				$tmp_content = (string) PhpOptimizer::minify_code((string)$tmp_path); // read from source, not optimized, and minify ; it is because some PHP scripts are not includded in the list of optimized files
			} else {
				$tmp_content = (string) PhpOptimizer::strip_code((string)$tmp_path); // read from source, not optimized, and strip only ; it is because some PHP scripts are not includded in the list of optimized files
			} //end if else
			if((string)trim((string)$tmp_content) == '') {
				$this->err = 'Failed to include php file: `'.$tmp_path.'` ... Content is EMPTY !';
				return;
				break;
			} //end if
			//--
			$tmp_content = (string) PhpOptimizer::check_and_remove_start_end_tags((string)$tmp_content);
			if((string)trim((string)$tmp_content) == '') {
				$this->err = 'Failed to include php file: `'.$tmp_path.'` ... Content is EMPTY after removing start/end php tags !';
				return;
				break;
			} //end if
			//--
			$appcodeunpack[] = (string) "\n".'// PHP-Script ('.(($val === true) ? 'ZM' : 'CS').'): '.$key."\n".$tmp_content."\n".'// #END'."\n"; // add this only after removing the php terminator
			//--
		} //end for
		$tmp_content = null;
		//--

		//--
		$key = null;
		$val = null;
		//--

		//--
		$appcodeunpack[] = '';
		$appcodeunpack[] = '// #END appcodeunpack.php ['.Smart::normalize_spaces((string)$hash).']';
		//--
		$appcodeunpack = (string) trim((string)implode("\n", (array)$appcodeunpack)); // trim here
		if((string)$appcodeunpack == '') {
			$this->err = 'Failed to generate the: `appcodeunpack.php` ... empty content !';
			return;
		} //end if
		$appcodeunpack .= "\n"; // add new line at the end
		//--
		if(!SmartFileSystem::write((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php', (string)$appcodeunpack)) {
			SmartFileSystem::delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
			$this->err = 'Failed to generate the: `appcodeunpack.php` ... disk write error !';
			return;
		} //end if
		//--
		$tmp_content = (string) SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
		if((string)$appcodeunpack !== (string)$tmp_content) {
			SmartFileSystem::delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
			$this->err = 'Failed to generate the: `appcodeunpack.php` ... content on disk is different !';
			return;
		} //end if
		if((string)SmartHashCrypto::sha512((string)$appcodeunpack) !== (string)SmartHashCrypto::sha512((string)$tmp_content)) {
			SmartFileSystem::delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
			$this->err = 'Failed to generate the: `appcodeunpack.php` ... content checksum on disk is different !';
			return;
		} //end if
		$tmp_content = null;
		$appcodeunpack = null;
		//--
		$lint_chk = (string) PhpOptimizer::lint_code((string)Smart::real_path((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php'));
		if($lint_chk) {
			SmartFileSystem::delete((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
			$this->err = 'Failed to generate the: `appcodeunpack.php` ... PHP lint errors: '.$lint_chk;
			return;
		} //end if

		//--
		$this->sficon = 'codepen';
		$this->msg = 'AppCodeUnPack Manager Standalone Script generated OK: `appcodeunpack.php`';
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
