<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: AppRelease/CodeDeploy
// Route: ?/page/app-release.code-deploy (?page=app-release.code-deploy)
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
 * @version 	v.20231107
 *
 */
final class SmartAppTaskController extends \SmartModExtLib\AppRelease\AbstractTaskController {

	protected $title = 'Deploy the Release Package';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';
	protected $notice = '';
	protected $notehtml = '';

	protected $goback = '';

	protected $working = true;
	protected $workvar = 'deploy';

	public function Run() {

		//--
		$appid = (string) $this->getAppId();
		if((string)$appid == '') {
			$this->err = 'App ID is Empty';
			return;
		} //end if
		//--
		$ctrl_url = (string) $this->ControllerGetParam('url-script').'?page='.$this->ControllerGetParam('controller').'&appid='.Smart::escape_url((string)$appid);
		//--

		//--
		if(!defined('APP_DEPLOY_HASH')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_HASH';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_HASH) == '') {
			$this->err = 'A required constant: APP_DEPLOY_HASH is empty';
			return;
		} //end if
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
		if(!defined('APP_DEPLOY_SECRET')) {
			$this->err = 'A required constant is missing: APP_DEPLOY_SECRET';
			return;
		} //end if
		if((string)trim((string)APP_DEPLOY_SECRET) == '') {
			$this->err = 'A required constant: APP_DEPLOY_SECRET is empty';
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
		if(!SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log')) {
			$this->err = 'The release package may have not been completed ...';
			return;
		} //end if
		if((string)SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'package-errors.log') !== '#NULL') {
			$this->err = 'The release package error log is not clean: `package-errors.log`';
			return;
		} //end if
		//--

		//--
		$last_package = '';
		$archives = (array) (new SmartGetFileSystem(true))->get_storage((string)TASK_APP_RELEASE_CODEPACK_APP_DIR, false, false, '.z-netarch');
		if(Smart::array_size($archives['list-files']) <= 0) {
			$this->err = 'Release Package NOT found !';
			return;
		} else {
			if(Smart::array_size($archives['list-files']) == 1) {
				$last_package = (string) $archives['list-files'][0];
			} else {
				$this->err = 'More than one Release Package found !';
				return;
			} //end if else
		} //end if else
		$archives = null;
		//--
		if((string)$last_package == '') {
			$this->err = 'The Release Package is N/A !';
			return;
		} //end if
		//--
		$last_path_package = (string) TASK_APP_RELEASE_CODEPACK_APP_DIR.$last_package;
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$last_path_package)) {
			$this->err = 'The Release Package path is unsafe !';
			return;
		} //end if
		if(!SmartFileSystem::is_type_file((string)$last_path_package)) {
			$this->err = 'The Release Package is not file !';
			return;
		} //end if
		if(!SmartFileSystem::have_access_read((string)$last_path_package)) {
			$this->err = 'The Release Package file is not readable !';
			return;
		} //end if
		$the_pack_size = (int) SmartFileSystem::get_file_size((string)$last_path_package);
		if((int)$the_pack_size < (int)AppNetUnPackager::APP_NET_UNPACKAGER_MIN_PACK_SIZE) {
			$this->err = 'The Package Size on disk is invalid, must have at least '.(int)AppNetUnPackager::APP_NET_UNPACKAGER_MIN_PACK_SIZE.' bytes but have: '.(int)$the_pack_size;
			return;
		} //end if
		//--

		//--
		$arr_urls = (array) explode(' | ', (string)APP_DEPLOY_URLS);
		$arr_valid_urls = [];
		$arr_done_urls = [];
		for($i=0; $i<Smart::array_size($arr_urls); $i++) {
			$arr_urls[$i] = (string) trim((string)$arr_urls[$i]);
			if((string)$arr_urls[$i] != '') {
				if(SmartFileSystem::path_exists((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.Smart::safe_filename('deploy-'.sha1((string)'#'.$appid.'#'.APP_DEPLOY_HASH.'#'.$last_package.'#'.$arr_urls[$i].'#').'.json'))) { // {{{SYNC-APP-DEPLOY-SIGNATURE}}}
					$arr_done_urls[] = (string) $arr_urls[$i];
				} else {
					$arr_valid_urls[(string)$arr_urls[$i]] = (string) $arr_urls[$i];
				} //end if else
			} //end if
		} //end for
		$arr_urls = null;
		if((Smart::array_size($arr_valid_urls) <= 0) AND (Smart::array_size($arr_done_urls) <= 0)) {
			$this->err = 'No Valid Deploy URLs Defined ...';
			return;
		} //end if
		$arr_urls = (array) array_keys((array)$arr_valid_urls);
		//--

		//--
		$url = (string) trim((string)$this->RequestVarGet('url', '', 'string'));
		//--
		if((string)$url == '') {
			$this->notice = 'Select the deploy URL from the list for this Release Package: `'.$last_package.'`';
			if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
				$this->notice .= "\n".'The AppCodeUnpack Manager Standalone Script will be updated with the current generated version ...';
				$this->sficon = [
					'codepen',
					'box-remove',
				];
			} else {
				$this->sficon = 'box-remove';
			} //end if else
			$this->notehtml = (string) SmartMarkersTemplating::render_file_template(
				(string) $this->ControllerGetParam('module-view-path').'app-deploy.mtpl.htm',
				[
					'APP-ID' => (string) $appid,
					'RELEASE-PACKAGE' => (string) $last_package,
					'HTML-LIST-SEL' => (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single(
						'deploy-url-sel',
						'url',
						'form',
						(array) $arr_valid_urls,
						'',
						'0/0',
						'onChange="try { manageButtonSelUrl(); } catch(err) { console.log(\'manageButtonSelUrl ERR:\', err) }" autocomplete="off"',
						'no',
						'yes',
						'--- NO AppCodeUnpack DEPLOY URL Selected ---',
						'class:ux-field-xl customList'
					),
					'CTRL-URL' => (string) $ctrl_url,
					'ARR-COMPLETED' => (array) $arr_done_urls,
				]
			);
			$this->goback = (string) $this->ControllerGetParam('url-script').'?page='.$this->ControllerGetParam('module').'.app-manage&appid='.Smart::escape_url((string)$appid);
			return;
		} //end if
		//--
		$this->goback = (string) $ctrl_url;
		//--
		$is_url_deployed = false;
		if(in_array((string)$url, (array)$arr_done_urls)) {
			$is_url_deployed = true;
		} //end if
		if((!in_array((string)$url, (array)$arr_urls)) AND (!$is_url_deployed)) {
			$this->err = 'The selected URL is not in the list ...';
			return;
		} //end if
		//--
		$deploy_selected_url = (string) $url;
		if((strpos((string)$deploy_selected_url, 'http://') !== 0) AND (strpos((string)$deploy_selected_url, 'https://') !== 0)) {
			$deploy_selected_url = '';
		} //end if
		if((string)trim((string)$deploy_selected_url) == '') {
			$this->err = 'The selected deploy URL is empty or invalid !';
			return;
		} //end if
		//--

		//--
		$signature = (string) sha1((string)'#'.$appid.'#'.APP_DEPLOY_HASH.'#'.$last_package.'#'.$deploy_selected_url.'#'); // {{{SYNC-APP-DEPLOY-SIGNATURE}}}
		//--
		$json_log_fpath = (string) TASK_APP_RELEASE_CODEPACK_APP_DIR.Smart::safe_filename('deploy-'.$signature.'.json');
		if(SmartFileSystem::path_exists((string)$json_log_fpath)) {
			$this->sficon = 'box-remove';
			$this->notice = 'The selected package was already successfuly deployed on the selected URL ...';
			$this->notehtml = (string) SmartComponents::operation_ok('<h1>AppCodeUnpack Package Deploy :: Saved Response Log</h1>'.'<pre>'.Smart::escape_html(SmartUtils::pretty_print_var(Smart::json_decode(SmartFileSystem::read((string)$json_log_fpath)))).'</pre>');
			return;
		} //end if
		if($is_url_deployed) { // safety check !
			$this->err = 'The package was deployed already on this URL !';
			return;
		} //end if
		//--

		//--
		$content_appcodeunpack = '';
		if(SmartFileSystem::is_type_file((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php')) {
			$content_appcodeunpack = (string) SmartFileSystem::read((string)TASK_APP_RELEASE_CODEPACK_APP_DIR.'appcodeunpack.php');
			if((string)trim((string)$content_appcodeunpack) == '') {
				$this->err = 'Failed to read the appcodeunpack.php for Deploy !';
				return;
			} //end if
		} //end if
		//--
		$signature_appcodeunpack = '';
		$signature_eappcodeunpack = '';
		$arr_appcodeunpack = [];
		if((string)$content_appcodeunpack != '') {
			$signature_appcodeunpack = (string) SmartHashCrypto::sh3a512((string)$content_appcodeunpack, true);
			$content_appcodeunpack = (string) SmartCipherCrypto::tf_encrypt((string)$content_appcodeunpack, (string)APP_DEPLOY_SECRET);
			$signature_eappcodeunpack = (string) SmartHashCrypto::sh3a384((string)$content_appcodeunpack, true);
			$signature_hmac = (string) SmartHashCrypto::hmac(
				'sha3-384',
				(string) APP_DEPLOY_SECRET.'#'.((defined('SMART_FRAMEWORK_SECURITY_KEY') && ((string)SMART_FRAMEWORK_SECURITY_KEY != '')) ? SMART_FRAMEWORK_SECURITY_KEY : Smart::uuid_34()),
				(string) $signature_appcodeunpack.chr(0).$signature_eappcodeunpack,
				true
			);
			$arr_appcodeunpack = [ // {{{SYNC-APPCODEUNPACK-SELF-UPDATE}}}
				'#' => (string) $signature_appcodeunpack,
				'=' => (string) $content_appcodeunpack,
				'@' => (string) $signature_eappcodeunpack,
				'!' => (string) $signature_hmac,
			];
		} //end if
		$signature_eappcodeunpack = '';
		$signature_appcodeunpack = '';
		$content_appcodeunpack = '';
		//--

		//--
		$httpcli = new SmartHttpClient(); // HTTP 1.0 to avoid continue method !
		$httpcli->connect_timeout = 20;
		//--
		$httpcli->postfiles = [
			'znetarch' => [
				'filename' => (string) $last_package,
				'content'  => (string) SmartFileSystem::read((string)$last_path_package)
			]
		];
		$packsize = (int) strlen((string)$httpcli->postfiles['znetarch']['content']);
		$packsh3a512b64 = (string) SmartHashCrypto::sh3a512((string)$httpcli->postfiles['znetarch']['content'], true);
		//--
		$httpcli->postvars = [
			'action' => 'deploy',
			'frm' => [
				'client' 			=> (string) 'appcodepack',
				'appid' 			=> (string) $appid,
				'appid-hash' 		=> (string) APP_DEPLOY_HASH,
				'packsh3a512b64' 	=> (string) $packsh3a512b64,
				'packsize' 			=> (int)    $packsize,
				'appcodeunpack' 	=> (array)  $arr_appcodeunpack,
				'metainfo' 			=> [
					(string) SmartUtils::get_ip_client(), // ip
					(string) date('Y-m-d H:i:s O'), // date time
					(int)    time(), // timestamp
					(array) [ // extended metainfo
						'optimizations' => [
							'strategy' 		=> (string) (defined('TASK_APP_RELEASE_CODEPACK_MODE') ? TASK_APP_RELEASE_CODEPACK_MODE : ''),
							'php-version' 	=> (string) phpversion(),
						],
						'packager' => [
							'version' => AppNetPackager::APP_NET_PACKAGER_VERSION,
						],
						'unpackager' => [
							'version' 		=> AppNetUnPackager::APP_NET_UNPACKAGER_VERSION,
							'min-pack-size' => AppNetUnPackager::APP_NET_UNPACKAGER_MIN_PACK_SIZE,
						],
						'deploy-info' => [
							'deploy-files'   => (array) Smart::json_decode((string)APP_DEPLOY_FILES),
							'deploy-folders' => (array) Smart::json_decode((string)APP_DEPLOY_FOLDERS),
						]
					]
				]
			],
		];
		//--
		// {{{SYNC-AUTH-TOKEN-SWT}}}
		//--
		$result = (array) $httpcli->browse_url(
			(string) $deploy_selected_url,
			'POST',
			'', // leave as set by SMART_FRAMEWORK_SSL_MODE
			(string)APP_DEPLOY_AUTH_USERNAME, (string)APP_DEPLOY_AUTH_PASSWORD
		);
		//--
		$httpcli = null; // free mem
		//--
		if(((string)$result['result'] != '1') OR ((string)$result['code'] != '202')) {
			$this->err = 'Package Deploy ERRORS: Invalid operation result or wrong status code ...'."\n".'Expected Operation Result is `1`'."\n".'Operation Result is: `'.$result['result'].'`'."\n".'Expected HTTP Status is: `202` / '.SmartFrameworkRuntime::GetStatusMessageByStatusCode((int)202)."\n".'Response HTTP Status is: `'.$result['code'].'` / '.SmartFrameworkRuntime::GetStatusMessageByStatusCode((int)$result['code'])."\n";
			return;
		} //end if
		//--
		$result['content'] = (string) trim((string)$result['content']);
		if((string)$result['content'] == '') {
			$this->err = 'Package Deploy ERRORS: Content Body is empty !';
			return;
		} //end if
		//--
		$json_arr = Smart::json_decode((string)$result['content']); // mixed !
		if(Smart::array_size($json_arr) <= 0) {
			$this->err = 'Package Deploy ERRORS: Content Body invalid: `'.(string)$result['content'].'`';
			return;
		} //end if
		//--
		if(
			(!array_key_exists('completed', $json_arr)) OR (!Smart::is_nscalar($json_arr['completed']))
			OR
			(!array_key_exists('status', $json_arr))    OR (!Smart::is_nscalar($json_arr['status']))
			OR
			(!array_key_exists('title', $json_arr))     OR (!Smart::is_nscalar($json_arr['title']))
			OR
			(!array_key_exists('message', $json_arr))   OR (!Smart::is_nscalar($json_arr['message']))
		) {
			$this->err = 'Package Deploy ERRORS: Content Body invalid JSON: `'.(string)$result['content'].'`';
			return;
		} //end if
		//--
		$result = null; // free mem
		//--

		//--
		$display_msg = '['.$json_arr['completed'].'/'.$json_arr['status'].']'."\n".$json_arr['title'].': '.$json_arr['message'];
		//--
		if(strpos((string)$json_arr['message'], "\n".'Deploy-URL: `'.$deploy_selected_url.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Deploy-URL Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'AppID: `'.$appid.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: AppID Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'AppID-Hash: `'.APP_DEPLOY_HASH.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: AppID-Hash Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'FileName: `'.$last_package.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Package FileName Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'FileSize-Bytes: `'.$packsize.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Package FileSize-Bytes Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'FileContent-Checksum: `'.$packsh3a512b64.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Package FileContent-Checksum Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'Signature: `'.$signature.'`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Signature Mismatch: '.$display_msg;
			return;
		} //end if
		if(strpos((string)$json_arr['message'], "\n".'Safety-Checks: `yes`'."\n") === false) {
			$this->err = 'Package Deploy WARNING: Safety-Checks Mismatch: '.$display_msg;
			return;
		} //end if
		//--
		$icon_appcodeunpack_upd = !! (strpos((string)$json_arr['message'], "\n".'AppCodeUnpack-Update: `OK`'."\n") !== false);
		//--
		if(
			((string)$json_arr['completed'] !== 'DONE') OR
			((string)$json_arr['status'] !== 'OK') OR
			((string)$json_arr['title'] !== 'OK')
		) {
			$this->err = 'Package Deploy ERRORS: '.$display_msg;
			return;
		} //end if
		//--
		SmartFileSysUtils::raiseErrorIfUnsafePath((string)TASK_APP_RELEASE_CODEPACK_APP_DIR);
		SmartFileSystem::write(
			(string) $json_log_fpath,
			(string) Smart::json_encode([
				'date-time' 	=> (string) date('Y-m-d H:i:s O'),
				'deploy-url' 	=> (string) $deploy_selected_url,
				'app-id' 		=> (string) $appid,
				'app-id-hash' 	=> (string) APP_DEPLOY_HASH,
				'package' 		=> (string) $last_package,
				'fsize' 		=> (string) $packsize,
				'checksum' 		=> (string) $packsh3a512b64,
				'signature' 	=> (string) $signature,
				'response' 		=> (array)  $json_arr,
			])
		);
		//--

		//--
		if($icon_appcodeunpack_upd === true) {
			$this->sficon = [
				'box-remove',
				'codepen',
			];
		} else {
			$this->sficon = 'box-remove';
		} //end if else
		$this->msg = 'Package Deploy SUCCESSFUL: '.$display_msg;
		//--

	} //END FUNCTION


} //END CLASS

// end of php code
