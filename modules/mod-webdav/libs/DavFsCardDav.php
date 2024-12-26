<?php
// [LIB - Smart.Framework / Webdav / Library Admin CardDav:Fs]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Webdav\DavFsCardDav
// Type: Module Library

namespace SmartModExtLib\Webdav;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

// [PHP8]

/**
 * Dav FileSystem CardDAV
 * @ignore
 */
final class DavFsCardDav {

	// ::
	// v.20240116

	private static $carddav_ns = 'xmlns:card="urn:ietf:params:xml:ns:carddav"';
	private static $carddav_urn = 'urn:ietf:params:xml:ns:carddav';
	private static $carddav_rep_data = ':address-data';
	private static $carddav_max_res_size = 1500000; // 1.5MB


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodOptions() : int { // 200 @ https://tools.ietf.org/html/rfc6352
		//--
		\http_response_code(200);
		//--
		\header('Date: '.\date('D, d M Y H:i:s O'));
		\header('Content-length: 0');
		\header('MS-Author-Via: DAV'); // Microsoft clients are set default to the Frontpage protocol unless we tell them to use DAV
		\header('DAV: 1, 2, addressbook'); // don't support (LOCK / UNLOCK) as seen in sabreDAV 1.5.x
		\header('Allow: OPTIONS, HEAD, GET, PROPFIND, REPORT, PUT, DELETE');
		\header('Accept-Ranges: none');
		\header('Z-Cloud-Service: CardDAV Server (Addressbook vcf / contacts)');
		//--
		return 200;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodHead(?string $dav_vfs_path) : int { // 200 | 404 | 415
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		//--
		if(!\SmartFileSystem::path_exists($dav_vfs_path)) {
			\http_response_code(404);
			return 404;
		} //end if
		//--
		if(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			\http_response_code(200);
			\header('Content-Type: '.self::mimeTypeDir($dav_vfs_path)); // directory
			\header('Content-length: 0');
		} elseif(\SmartFileSystem::is_type_file($dav_vfs_path)) {
			\http_response_code(200);
			\header('Content-Type: '.self::mimeTypeFile($dav_vfs_path));
			\header('Content-Length: '.(int)\SmartFileSystem::get_file_size($dav_vfs_path));
			\header('ETag: "'.(string)\SmartFileSystem::get_file_md5_checksum((string)$dav_vfs_path).'"');
		} else { // unknown media type
			\http_response_code(415);
			return 415;
		} //end if else
		//--
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s', (int)\SmartFileSystem::get_file_mtime($dav_vfs_path)).' GMT');
		return 200;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodPropfind(?string $dav_uri, ?string $dav_request_path, ?string $dav_vfs_path, bool $dav_is_root_path, ?string $dav_vfs_root) : int {
		//--
		$dav_method = 'PROPFIND';
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		\header('Expires: '.\gmdate('D, d M Y', @\strtotime('-1 day')).' '.\date('H:i:s').' GMT'); // HTTP 1.0
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
		//--
		if(\SmartFileSystem::is_type_file($dav_vfs_path)) { // file
			$statuscode = 207;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				(string) self::$carddav_ns,
				(string) $dav_method,
				(string) $dav_request_path,
				(bool)   $dav_is_root_path,
				(int)    $statuscode,
				(string) $dav_uri,
				(array)  self::getItem($dav_uri, $dav_vfs_path)
			);
		} elseif(\SmartFileSystem::is_type_dir($dav_vfs_path)) { // dir
			$statuscode = 207;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				(string) self::$carddav_ns,
				(string) $dav_method,
				(string) $dav_request_path,
				(bool)   $dav_is_root_path,
				(int)    $statuscode,
				(string) $dav_uri,
				(array)  self::getItem($dav_uri, $dav_vfs_path),
				(array)  self::getQuotaAndUsageInfo($dav_vfs_root)
			);
		} else { // not found
			$statuscode = 404;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				(string) self::$carddav_ns,
				(string) $dav_method,
				(string) $dav_request_path,
				(bool)   $dav_is_root_path,
				(int)    $statuscode,
				(string) $dav_uri
			);
		} //end if else
		//--
		return (int) $statuscode;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodPut(?string $dav_vfs_path) : int { // 201 | 400 | 405 | 406 | 409 | 411 | 415 | 423 | 500
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		//--
		if(!\array_key_exists('range', $heads)) {
			$heads['range'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('content-range', $heads)) {
			$heads['content-range'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('content-length', $heads)) {
			$heads['content-length'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('transfer-encoding', $heads)) {
			$heads['transfer-encoding'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('x-expected-entity-length', $heads)) {
			$heads['x-expected-entity-length'] = null; // PHP8 fix
		} //end if
		//--
		if(((string)\trim((string)$heads['range']) != '') OR ((string)\trim((string)$heads['content-range']) != '')) { // (SabreDAV)
			// Content-Range is dangerous for PUT requests:  PUT per definition
			// stores a full resource.  draft-ietf-httpbis-p2-semantics-15 says
			// in section 7.6:
			//   An origin server SHOULD reject any PUT request that contains a
			//   Content-Range header field, since it might be misinterpreted as
			//   partial content (or might be partial content that is being mistakenly
			//   PUT as a full representation).  Partial content updates are possible
			//   by targeting a separately identified resource with state that
			//   overlaps a portion of the larger resource, or by using a different
			//   method that has been specifically defined for partial updates (for
			//   example, the PATCH method defined in [RFC5789]).
			\header('Accept-Ranges: none');
			\http_response_code(400); // unsupported: ranges
			return 400;
		} //end if
		//--
		$head_content_length = (string) \trim((string)$heads['content-length']);
		if((string)$head_content_length == '') {
			\http_response_code(411); // content length required
			return 411;
		} //end if
		$head_content_length = (int) $head_content_length;
	/*	if($head_content_length < 0) { // this check is disabled in order to allow empty files
			\http_response_code(400); // invalid content length
			return 400;
		} //end if */
		if($head_content_length <= 0) {
			\http_response_code(411); // dissalow empty files (0 bytes)
			return 411;
		} //end if
		//-- TODO: MacOS Finder with PUT files > 8.5 MB still fails ... ; !! without the below restriction, the MacOS Finder works with files under the above limit !!
		if(\stripos((string)$heads['transfer-encoding'], 'chunked') !== false) {
			\header('Accept-Encoding: gzip, deflate, identity'); // {{{SYNC-WEBDAV-CHUNKED-RESTRICTION}}} ; chunked content is unsafe to dechunk ... it may fail, thus on webdav is not acceptable
			\http_response_code(406); // not acceptable
			return 406; // this check must be performed after checking the content length header
		} //end if
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		//--
		$the_dname = (string) \trim((string)\SmartFileSysUtils::extractPathDir((string)$dav_vfs_path));
		if(((string)$the_dname == '') OR (!\SmartFileSysUtils::checkIfSafePath((string)$the_dname))) {
			\http_response_code(415); // do not allow: (empty / unsafe dir paths are not allowed)
			return 415;
		} //end if
		$the_dname = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$the_dname);
		if((!\SmartFileSysUtils::checkIfSafePath((string)$the_dname)) OR (!\SmartFileSystem::is_type_dir($the_dname))) {
			\http_response_code(409); // conflict: cannot PUT a resource if all ancestors do not already exist
			return 409;
		} //end if
		//--
		$the_fname = (string) \trim((string)\SmartFileSysUtils::extractPathFileName((string)$dav_vfs_path));
		if(((string)$the_fname == '') OR ((string)\substr($the_fname, 0, 1) == '.') OR (!\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$the_fname))) {
			\http_response_code(415); // unsupported media type (empty / dot / unsafe file names are not allowed)
			return 415;
		} //end if
		//--
		if((string)$the_dname.$the_fname != (string)$dav_vfs_path) {
			\Smart::log_warning(__METHOD__.'() : Unsafe recompose path: '.$the_dname.$the_fname.' # '.$dav_vfs_path);
			\http_response_code(406); // not acceptable: weird path ... failed to decompose
			return 406;
		} //end if
		//--
		$the_ext = (string) \strtolower((string)\trim((string)\SmartFileSysUtils::extractPathFileExtension((string)$dav_vfs_path)));
		if(!\defined('\\SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS')) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		if(\stripos((string)\SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, '<'.$the_ext.'>') !== false) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		if(\defined('\\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS') AND ((string)\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS != '')) {
			if(\stripos((string)\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS, '<'.$the_ext.'>') === false) {
				\http_response_code(415); // unsupported media type
				return 415;
			} //end if
		} //end if
		if((string)$the_ext != 'vcf') {
			\http_response_code(415); // unsupported media type ; allow just .vcf !!!
			return 415;
		} //end if
		//--
		// NOTICE: enforcing lowercase file name fails with Thunderbird/SoGOAddrbook
		//--
		$fp = \SmartModExtLib\Webdav\DavServer::getRequestBody(true); // get as resource stream
		if(!\is_resource($fp)) {
			\http_response_code(500); // internal server error
			return 500;
		} //end if
		//--
		if(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			\http_response_code(405); // the destination exists and is a directory
			return 405;
		} //end if
		//--
		$oversized = false;
		$max_res_size = \Smart::format_number_int(self::$carddav_max_res_size,'+');
		$vcf_data = '';
		while($data = @\fread($fp, 1024*8)) {
			$vcf_data .= $data;
			if((int)\strlen((string)$vcf_data) > (int)$max_res_size) {
				$oversized = true;
				break;
			} //end if
		} //end while
		//--
		@\fclose($fp);
		//--
		if((string)\trim((string)$vcf_data) == '') {
			\http_response_code(423); // locked: could not achieve fopen advisory lock
			return 423;
		} //end if
		if($oversized === true) {
			\http_response_code(507); // not enough space (for oversized)
			return 507;
		} //end if
		//--
		$fsize = (int) \strlen((string)$vcf_data);
		if((int)$fsize != (int)$head_content_length) {
			\http_response_code(408); // request timeout (delivered a smaller size content than expected)
			return 408;
		} //end if
		//--
		if(!\SmartFileSystem::write((string)$dav_vfs_path, (string)$vcf_data)) {
			\Smart::log_warning(__METHOD__.'() : Failed to Write a new File: '.$dav_vfs_path);
			\http_response_code(423); // locked: could not achieve fopen advisory lock
			return 423;
		} //end if
		$vcf_data = ''; // free mem
		//--
	/*	if((int)\trim((string)$heads['x-expected-entity-length']) > 0) { // intercepting the MacOS Finder problem (SabreDAV)
			// Many webservers will not cooperate well with Finder PUT requests, because it uses 'Chunked' transfer encoding for the request body.
			// The symptom of this problem is that Finder sends files to the server, but they arrive as 0-lenght files in PHP.
			// If we don't do anything, the user might think they are uploading files successfully, but they end up empty on the server.
			// Instead, we throw back an error if we detect this.
			// The reason Finder uses Chunked, is because it thinks the files might change as it's being uploaded, and therefore the
			// Content-Length can vary.
			// Instead it sends the X-Expected-Entity-Length header with the size of the file at the very start of the request.
			// If this header is set, but we don't get a request body we will fail the request to protect the end-user.
			if((int)$fsize <= 0) {
				\http_response_code(411); // content length required
				return 411;
			} //end if
		} //end if */
		//--
		\http_response_code(201); // HTTP/1.1 201 Created
		\header('Content-length: 0');
		\header('ETag: "'.(string)\SmartFileSystem::get_file_md5_checksum((string)$dav_vfs_path).'"');
		\header('Z-Cloud-DAV-Put-FileSize: '.$fsize);
		return 201;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodDelete(?string $dav_vfs_path) : int { // 204 | 405 | 415 | 423
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		//--
		if(\SmartFileSystem::path_exists($dav_vfs_path)) {
			if(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
				\SmartFileSystem::dir_delete($dav_vfs_path, true);
			} elseif(\SmartFileSystem::is_type_file($dav_vfs_path)) {
				\SmartFileSystem::delete($dav_vfs_path);
			} else {
				\http_response_code(405); // method not allowed: unknown resource type
				return 405;
			} //end if
		} //end if
		//--
		if(\SmartFileSystem::path_exists($dav_vfs_path)) {
			\http_response_code(423); // locked: could not remove the resource, perhaps locked
			return 423;
		} //end if
		//--
		\http_response_code(204); // HTTP/1.1 204 No Content
		\header('Content-length: 0');
		return 204;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodGet(?string $dav_method, ?string $dav_author, ?string $dav_url, ?string $dav_request_path, ?string $dav_vfs_path, bool $dav_is_root_path, ?string $dav_vfs_root, ?string $dav_request_back_path, ?string $nfo_title, ?string $nfo_signature, ?string $nfo_prefix_crrpath, ?string $nfo_lnk_welcome, ?string $nfo_txt_welcome, ?string $nfo_svg_logo) : int { // 200 | 404 | 405 | 415 | 423
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		//--
		if(!\array_key_exists('range', $heads)) {
			$heads['range'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('content-range', $heads)) {
			$heads['content-range'] = null; // PHP8 fix
		} //end if
		//--
		if(((string)\trim((string)$heads['range']) != '') OR ((string)\trim((string)$heads['content-range']) != '')) {
			\header('Accept-Ranges: none'); // !!! IMPORTANT BUG FIX: without this, if the client ask a partial range content will result in corrupted file content: MacOS Finder with GET files > 8.5 MB) !!!
			\http_response_code(400); // unsupported: ranges
			return 400;
		} //end if
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		$dav_vfs_root = (string) $dav_vfs_root; // safe on .ht* names
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		//--
		if(!\SmartFileSystem::path_exists($dav_vfs_path)) {
			\http_response_code(404); // path does not exist
			echo (string) self::answerPostErr404('The requested URL was Not Found on this server', $dav_url);
			return 404;
		} //end if
		//--
		if(!\SmartFileSystem::is_type_file($dav_vfs_path)) {
			//--
			$nfo_crrpath = (string) $nfo_prefix_crrpath.$dav_request_path;
			//--
			$bwc = (string) \SmartUtils::get_os_browser_ip('bc');
			if(!\in_array((string)$bwc, (array)\SmartModExtLib\Webdav\DavServer::getSupportedBrowserClasses())) {
				if((string)$bwc == (string)\SmartUtils::GENERIC_VALUE_OS_BROWSER_IP) {
					\http_response_code(405); // method not allowed: only files can be GET !
					return 405;
				} else { // it is a recognized browser, give a nice answer
					\http_response_code(403); // browser not allowed: unsupported class
					die((string)\SmartComponents::http_message_403_forbidden('Browser class not supported: `'.$bwc.'`'));
				} //end if else
			} //end if
			//--
			\http_response_code(200);
			$arr_quota = (array) self::getQuotaAndUsageInfo($dav_vfs_root);
			$files_n_dirs = (array) (new \SmartGetFileSystem(true))->get_storage($dav_vfs_path, false, false, '.vcf'); // non-recuring, no dot files, only VCF
			$fixed_vfs_dir = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$dav_vfs_path);
			$fixed_dav_url = (string) \rtrim((string)$dav_url, '/').'/';
			$base_url = (string) \SmartUtils::get_server_current_url();
			$arr_f_dirs = array();
			for($i=0; $i<\Smart::array_size($files_n_dirs['list-dirs']); $i++) {
				$arr_f_dirs[] = [
					'name'  => (string) $files_n_dirs['list-dirs'][$i],
					'type'  => (string) self::mimeTypeDir((string)$fixed_vfs_dir.$files_n_dirs['list-dirs'][$i]),
					'size'  => '-',
					'modif' => (string) \date('Y-m-d H:i:s O', (int)\SmartFileSystem::get_file_mtime($fixed_vfs_dir.$files_n_dirs['list-dirs'][$i])),
					'link'  => (string) $fixed_dav_url.$files_n_dirs['list-dirs'][$i],
					'icon'  => (string) \SmartModExtLib\Webdav\DavUtils::getFolderIcon($files_n_dirs['list-dirs'][$i])
				];
			} //end for
			$arr_f_files = array();
			for($i=0; $i<\Smart::array_size($files_n_dirs['list-files']); $i++) {
				$arr_f_files[] = [
					'name'  => (string) $files_n_dirs['list-files'][$i],
					'type'  => (string) self::mimeTypeFile((string)$files_n_dirs['list-files'][$i]),
					'size'  => (string) \SmartUtils::pretty_print_bytes((int)\SmartFileSystem::get_file_size($fixed_vfs_dir.$files_n_dirs['list-files'][$i]), 2),
					'modif' => (string) \date('Y-m-d H:i:s O', (int)\SmartFileSystem::get_file_mtime($fixed_vfs_dir.$files_n_dirs['list-files'][$i])),
					'link'  => (string) $fixed_dav_url.$files_n_dirs['list-files'][$i],
					'icon'  => (string) \SmartModExtLib\Webdav\DavUtils::getFileIcon($files_n_dirs['list-files'][$i])
				];
			} //end for
			$detect_dav_url_root = (array) \explode('~', (string)$dav_url);
			if((string)\trim((string)$detect_dav_url_root[0]) != '') {
				$detect_dav_url_back = (string) \trim((string)$detect_dav_url_root[0]).'~/'.$dav_request_back_path;
			} else {
				$detect_dav_url_back = '';
			} //end if else
			$info_extensions_list = '';
			if((\defined('\\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS')) AND ((string)\trim((string)\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS) != '')) {
				$info_extensions_list = 'Allowed Extensions List: '.\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS;
			} else {
				$info_extensions_list = 'Disallowed Extensions List: '.\SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS;
			} //end if else
			$info_restr_charset = 'restricted charset as [ _ a-z A-Z 0-9 - . @ ]';
			$html = (string) \SmartMarkersTemplating::render_file_template(
				\SmartModExtLib\Webdav\DavServer::getTplPath().'answer-get-path.mtpl.htm',
				[
					'CHARSET' 			=> (string) \SMART_FRAMEWORK_CHARSET,
					'IMG-SVG-LOGO' 		=> (string) $nfo_svg_logo,
					'TEXT-WELCOME' 		=> (string) $nfo_txt_welcome,
					'LINK-WELCOME' 		=> (string) $nfo_lnk_welcome,
					'INFO-HEADING' 		=> (string) $nfo_title,
					'INFO-SIGNATURE' 	=> (string) $nfo_signature,
					'INFO-ROOT' 		=> (string) '{DAV:'.$dav_vfs_root.'}',
					'INFO-TITLE' 		=> (string) $nfo_signature.' - '.$nfo_title.' / '.$nfo_crrpath.' @ '.\date('Y-m-d H:i:s O'),
					'INFO-AUTHNAME' 	=> (string) $dav_author,
					'INFO-VERSION' 		=> (string) \SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.\SMART_FRAMEWORK_RELEASE_VERSION,
					'CRR-PATH' 			=> (string) $nfo_crrpath,
					'NUM-CRR-DIRS' 		=> (int)    $files_n_dirs['dirs'],
					'NUM-CRR-FILES' 	=> (int)    $files_n_dirs['files'],
					'QUOTA-USED' 		=> (string) \SmartUtils::pretty_print_bytes((int)$arr_quota['used'], 0, ''),
					'QUOTA-FREE' 		=> (string) \SmartUtils::pretty_print_bytes((int)$arr_quota['free'], 0, ''),
					'QUOTA-SPACE' 		=> (string) ((int)$arr_quota['quota'] ? \SmartUtils::pretty_print_bytes((int)$arr_quota['quota'], 0, '') : 'NOLIMIT'),
					'NUM-DIRS' 			=> (int)    $arr_quota['num-dirs'],
					'NUM-FILES' 		=> (int)    $arr_quota['num-files'],
					'LIST-DIRS' 		=> (array)  $arr_f_dirs,
					'LIST-FILES' 		=> (array)  $arr_f_files,
					'BASE-URL' 			=> (string) $base_url,
					'IS-ROOT' 			=> (string) ($dav_is_root_path ? 'yes' : 'no'),
					'BACK-PATH' 		=> (string) $detect_dav_url_back,
					'DISPLAY-QUOTA' 	=> (string) (\defined('\\SMART_WEBDAV_SHOW_USAGE_QUOTA') AND (\SMART_WEBDAV_SHOW_USAGE_QUOTA === true)) ? 'yes' : 'no',
					'DIR-NEW-INFO' 		=> (string) 'INFO: Directory Creation is dissalowed ...', // TODO: add support to create new addressbooks ...
					'MAX-UPLOAD-INFO' 	=> (string) 'INFO: Direct Files Uploads is dissalowed ...', // TODO: add support to upload validated VCFs only
					'SHOW-POST-FORM' 	=> 'no'
				],
				'yes' // cache
			);
			echo (string) $html;
			return 200;
		} elseif((string)$dav_method == 'POST') { // POST to a file is not allowed
			\http_response_code(405); // method not allowed: only dirs can be POST !
			return 405;
		} //end if
		//--
		if(!\SmartFileSystem::have_access_read($dav_vfs_path)) {
			\http_response_code(423); // locked: file is not accessible
			return 423;
		} //end if
		//--
		\http_response_code(200); // HTTP/1.1 200 OK
		\header('Expires: '.\gmdate('D, d M Y', @\strtotime('-1 day')).' '.\date('H:i:s').' GMT'); // HTTP 1.0
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
		\header('Content-length: '.(int)\SmartFileSystem::get_file_size($dav_vfs_path));
		\header('Content-Type: '.(string)self::mimeTypeFile($dav_vfs_path));
		if(\ob_get_level()) {
			\ob_end_flush(); // fix to avoid get out of memory with big files
		} //end if
		@\readfile($dav_vfs_path);
		return 200;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodReport(?string $dav_uri, ?string $dav_request_path, ?string $dav_vfs_path, bool $dav_is_root_path, ?string $dav_vfs_root) : int {
		//--
		$dav_method = 'REPORT';
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		$dav_vfs_root = (string) $dav_vfs_root; // safe on .ht* names
		//--
		// Method REPORT is for serving multiple files in one request
		// It should only be done over a directory that contains some files to serve
		// For CardDAV will report just for: AddressBook/*.vcf
		// The REPORT method is very complex, and cover many situations, but the main purpose is just to serve many files at once ...
		// For a simple implementation of REPORT we validate the request to be as:
		//	1. Have a Valid XML Body
		// 	2. The XML Body should contain CardDAV signature (self::$carddav_urn) and a request for CardDAV Files (self::$carddav_rep_data)
		// 	3. Idea: we don't take care if is addressbook-multiget or addressbook-query because is too complex to handle and we have not the intention to implement real query ...
		// 	4. Because of the above exposed Idea, to make it simple, if the request contain an xml body from which we can parse and extract some links to the requested addressbook vcf files, that OK and we serve back just those files (is important to serve back all requested files to inform if some files are 404 as deleted meanwhile by another client instance !!) ; if the parsed array is empty (no content or parsing errors, we serve back all files from addressbook !!)
		//	5. If the Request path is not a addressbook folder or a file, serve back an error 400
		//--
		if(\SmartFileSystem::is_type_file((string)$dav_vfs_path)) {
			// \Smart::log_notice('CardDAV REPORT Method called for a file type, which is not supported ...');
			\http_response_code(400); // bad request
			return 400;
		} //end if
		if(!\SmartFileSystem::is_type_dir((string)$dav_vfs_path)) {
			// \Smart::log_notice('CardDAV REPORT Method called for a non-existing folder ...');
			\http_response_code(400); // bad request
			return 400;
		} //end if
		if((int)self::testIsAddressbookCollection((string)$dav_vfs_path) !== 1) {
			// \Smart::log_notice('CardDAV REPORT Method called for a non-addressbook folder, which is not supported ...');
			\http_response_code(400); // bad request
			return 400;
		} //end if
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		// \Smart::log_notice(\print_r($heads,1));
		$body = (string) \SmartModExtLib\Webdav\DavServer::getRequestBody();
		// \Smart::log_notice(\print_r($body,1));
		//-- OR (stripos((string)$body, ':addressbook-multiget ') === false)
		$arr = array();
		if((string)\trim((string)$body) != '') { // test only if non-empty body, otherwise suppose it requested for all files
			if((\stripos((string)$body, (string)self::$carddav_urn) === false) OR (\strpos((string)$body, (string)self::$carddav_rep_data) === false)) {
				// \Smart::log_notice('CardDAV REPORT is invalid: '.$body);
				\http_response_code(400); // bad request
				return 400;
			} //end if
			// \Smart::log_notice('CardDAV REPORT ...');
			$arr = (array) \SmartModExtLib\Webdav\DavServer::parseXMLBody((string)$body, '', 'href');
		} //end if
		//--
		$files = [];
		if(\Smart::array_size($arr) > 0) { // if successfuly extracted some links from the body
			for($i=0; $i<\Smart::array_size($arr); $i++) {
				$link = (string) \trim((string)$arr[$i]);
				if(((string)$link != '') AND ((string)\strtolower((string)\substr((string)$link, -4, 4)) == '.vcf')) { // don't test for safe path as this is the server full url path and may not be compliant with this check
					$link = (string) \SmartFileSysUtils::extractPathFileName((string)$link);
					$link = (string) \trim((string)$link);
					if(((string)$link != '') AND ((string)\strtolower((string)\substr((string)$link, -4, 4)) == '.vcf') AND (\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$link) == '1')) { // but the file must be safe compliant ; safe on .ht* names
						$files[] = (string) $link;
					} //end if
				} //end if
			} //end for
		} //end if
		$arr = array();
		//--
		//$dbg_data = 'Parsed-XML for URI: ';
		if(\Smart::array_size($files) <= 0) { // if no vcf files found in request, serve them all
			// \Smart::log_notice('CardDAV REPORT contain no HREFs or could not parse the body: '."\n".$body);
			//$dbg_data = 'Parsed-XML Empty for URI: ';
			$arr_list_vcf = (array) (new \SmartGetFileSystem(true))->get_storage((string)$dav_vfs_path, false, false, '.vcf'); // non-recuring, no dot files, only VCF ; safe on .ht*
			$files = array();
			$files = (array) $arr_list_vcf['list-files'];
			$arr_list_vcf = array();
		} //end if
		// \Smart::log_notice($dbg_data.' <'.$dav_uri.'> ('.$dav_request_path.') ['.$dav_vfs_path.']:'."\n".\print_r($files,1));
		//--
		$arr = array();
	//	$arr[] = (array) self::getItemTypeCollection($dav_uri, $dav_vfs_path); // add the folder tho this request # THIS MUST NOT BE SET IN REPORT !!!
		$arr = self::addSubItem($dav_uri, $dav_vfs_path, $arr, $files, 'files', true); // safe on .ht*
		//--
		$statuscode = 207;
		// \ob_start();
		\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
			(string) self::$carddav_ns,
			(string) $dav_method,
			(string) $dav_request_path,
			(bool)   $dav_is_root_path,
			(int)    $statuscode,
			(string) $dav_uri,
			(array)  $arr,
			(array)  self::getQuotaAndUsageInfo($dav_vfs_root)
		);
		// $tst = \ob_get_contents();
		// \ob_end_clean();
		// echo $tst;
		// \Smart::log_notice('HEADERS:['.http_response_code().']'."\n".\print_r(\headers_list(),1));
		// \Smart::log_notice('REPORT:'."\n".$tst);
		//--
		return (int) $statuscode;
		//--
	} //END FUNCTION


	//=====


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function answerPostErr404(?string $message, ?string $dav_url) : string {
		//--
		return (string) \SmartComponents::http_message_404_notfound((string)$message);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getQuotaAndUsageInfo(?string $dav_vfs_root) : array {
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_root)) {
			return array();
		} //end if
		//--
		$free_space = (int) \floor((float)\disk_free_space((string)$dav_vfs_root));
		//--
		if((!\defined('\\SMART_WEBDAV_SHOW_USAGE_QUOTA')) OR (\SMART_WEBDAV_SHOW_USAGE_QUOTA !== true)) {
			//-- need to report at least the free space ...
			return array( // skip quota info if not express specified
				'root-dir' 		=> (string) $dav_vfs_root, 		// vfs root dir ; safe on .ht* names
				'quota' 		=> (int) 0, 					// total quota (0 is unlimited)
				'used' 			=> (int) 0, 					// don't know, will not calculate
				'free' 			=> (int) $free_space, 			// free space (free) in bytes,
				'num-dirs' 		=> (int) 0, 					// # dirs
				'num-files' 	=> (int) 0 						// # files
			);
			//--
		} //end if
		//--
		$arr_storage = (new \SmartGetFileSystem())->get_storage((string)$dav_vfs_root, true, true, ''); // recuring, with dot files ; safe on .ht* names as it only calculate sizes
		// \Smart::log_notice(\print_r($arr_storage,1));
		$used_space = (int) $arr_storage['size-files']; // 'size'
		//--
		return array(
			'root-dir' 		=> (string) $dav_vfs_root, 		// vfs root dir ; safe on .ht* names
			'quota' 		=> (int) $arr_storage['quota'], // total quota (0 is unlimited)
			'used' 			=> (int) $used_space, 			// used space (total - free) in bytes,
			'free' 			=> (int) $free_space, 			// free space (free) in bytes,
			'num-dirs' 		=> (int) $arr_storage['dirs'], 	// # dirs
			'num-files' 	=> (int) $arr_storage['files'] 	// # files
		);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getItem(?string $dav_request_path, ?string $dav_vfs_path) : array {
		//--
		$dav_request_path = (string) \trim((string)$dav_request_path);
		$dav_vfs_path = (string) \trim((string)$dav_vfs_path); // safe on .ht* names
		//--
		if(((string)$dav_request_path == '') OR ((string)$dav_vfs_path == '')) {
			return array();
		} //end if
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			return array();
		} //end if
		//--
		$arr = array();
		//--
		if(\SmartFileSystem::is_type_file($dav_vfs_path)) {
			$arr[] = (array) self::getItemTypeNonCollection($dav_request_path, $dav_vfs_path); // safe on .ht* names
		} elseif(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			$arr[] = (array) self::getItemTypeCollection($dav_request_path, $dav_vfs_path); // safe on .ht* names
			$files_n_dirs = (array) (new \SmartGetFileSystem(true))->get_storage($dav_vfs_path, false, false, '.vcf'); // non-recuring, no dot files, only VCF ; safe on .ht* names
			// \print_r($files_n_dirs); die();
			// \print_r($arr); die();
			$arr = self::addSubItem($dav_request_path, $dav_vfs_path, $arr, $files_n_dirs['list-dirs'], 'dirs'); // safe on .ht* names
			$arr = self::addSubItem($dav_request_path, $dav_vfs_path, $arr, $files_n_dirs['list-files'], 'files'); // safe on .ht* names
			// \print_r($arr); die();
		} //end if else
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function testIsAddressbookCollection(?string $dav_vfs_path) : int {
		//--
		if(\defined('\\SMART_WEBDAV_CARDDAV_ABOOK_PATH')) {
			if(\strpos((string)$dav_vfs_path, (string)\SMART_WEBDAV_CARDDAV_ABOOK_PATH) === 0) {
				return 1; // addressbook
			} //end if
		} //end if
		//--
		if(\defined('\\SMART_WEBDAV_CARDDAV_ACC_PATH')) {
			if(\strpos((string)$dav_vfs_path, (string)\SMART_WEBDAV_CARDDAV_ACC_PATH) === 0) {
				return 2; // account
			} //end if
		} //end if
		//--
		return 0;
		//--
	} //end if


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function mimeTypeDir(?string $dav_vfs_path) : string {
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		switch((int)self::testIsAddressbookCollection($dav_vfs_path)) {
			case 1:
				$type = 'Collection, Addressbook';
				break;
			case 2:
				$type = 'Collection, Account';
				break;
			default:
				$type = 'Collection';
		} //end if
		//--
		return (string) $type;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function mimeTypeFile(?string $dav_vfs_path) : string {
		//--
		return (string) \SmartFileSysUtils::getMimeType((string)$dav_vfs_path); // safe on .ht* names
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function addSubItem(?string $dav_request_path, ?string $dav_vfs_path, array $arr, array $subitems, ?string $type, bool $add_data_fcontent=false) : array {
		//--
		$arr = (array) $arr;
		$subitems = (array) $subitems;
		//--
		if(\Smart::array_size($subitems) > 0) {
			for($i=0; $i<\Smart::array_size($subitems); $i++) {
				if(\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$subitems[$i])) {
					if(\SmartFileSysUtils::checkIfSafePath((string)$subitems[$i])) { // will dissalow #paths
						if(\SmartModExtLib\Webdav\DavServer::safeCheckPathAgainstHtFiles($subitems[$i])) { // dissalow .ht*
							$tmp_new_req_path = (string) \rtrim((string)$dav_request_path, '/').'/'.$subitems[$i];
							$tmp_new_vfs_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$dav_vfs_path).$subitems[$i];
							if(\SmartFileSysUtils::checkIfSafePath((string)$tmp_new_vfs_path)) {
								if(((string)$type == 'dirs') AND (\SmartFileSystem::is_type_dir($tmp_new_vfs_path))) {
									$tmp_new_arr = (array) self::getItemTypeCollection(
										(string) $tmp_new_req_path,
										(string) $tmp_new_vfs_path
									);
								} elseif(((string)$type == 'files') AND (\SmartFileSystem::is_type_file($tmp_new_vfs_path))) {
									$tmp_new_arr = (array) self::getItemTypeNonCollection(
										(string) $tmp_new_req_path,
										(string) $tmp_new_vfs_path
									);
									if($add_data_fcontent === true) {
										if(\Smart::array_size($tmp_new_arr) > 0) {
											$tmp_new_arr['c-xml-data'] = '<card:address-data>'.\Smart::escape_html(\SmartFileSystem::read($tmp_new_vfs_path)).'</card:address-data>';
										} //end if
									} //end if
								} //end if else
								if(\Smart::array_size($tmp_new_arr) > 0) {
									$arr[] = (array) $tmp_new_arr;
								} //end if
							} //end if
						} //end if
					} //end if
				} //end if
			} //end for
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getItemTypeNonCollection(?string $dav_request_path, ?string $dav_vfs_path) : array {
		//--
		$dav_request_path = (string) \trim((string)$dav_request_path);
		$dav_vfs_path = (string) \trim((string)$dav_vfs_path); // safe on .ht* names
		//--
		if(((string)$dav_request_path == '') OR ((string)$dav_vfs_path == '')) {
			return array();
		} //end if
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			return array();
		} //end if
		if(!\SmartFileSystem::is_type_file($dav_vfs_path)) {
			return array();
		} //end if
		//--
		return array(
			'dav-resource-type' 		=> (string) \SmartModExtLib\Webdav\DavServer::DAV_RESOURCE_TYPE_NONCOLLECTION,
			'dav-request-path' 			=> (string) $dav_request_path,
			'dav-vfs-path' 				=> (string) $dav_vfs_path, // private
			'date-creation-timestamp' 	=> (int) 	0, // \SmartFileSystem::get_file_ctime($dav_vfs_path), // currently is unused
			'date-modified-timestamp' 	=> (int) 	\SmartFileSystem::get_file_mtime($dav_vfs_path),
			'size-bytes' 				=> (int)    \SmartFileSystem::get_file_size($dav_vfs_path),
			'etag-hash' 				=> (string) \SmartFileSystem::get_file_md5_checksum($dav_vfs_path),
			'mime-type' 				=> (string) self::mimeTypeFile($dav_vfs_path)
		);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getItemTypeCollection(?string $dav_request_path, ?string $dav_vfs_path) : array {
		//--
		$dav_request_path = (string) \trim((string)$dav_request_path);
		$dav_vfs_path = (string) \trim((string)$dav_vfs_path); // safe on .ht* names
		//--
		if(((string)$dav_request_path == '') OR ((string)$dav_vfs_path == '')) {
			return array();
		} //end if
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path)) {
			return array();
		} //end if
		if(!\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			return array();
		} //end if
		//--
		$restype = '';
		$ext_prop = '';
		//--
		$restype .= '<d:collection/>';
		//--
		$ext_prop .= '<d:displayname>'.\Smart::escape_html(\SmartFileSysUtils::extractPathFileName((string)$dav_request_path)).'</d:displayname>'; // iOS Fix
		$ext_prop .= '<d:current-user-principal><d:href>'.\Smart::escape_html((string)\SMART_WEBDAV_CARDDAV_ABOOK_ACC).'</d:href></d:current-user-principal>'; // iOS Fix
		$ext_prop .= '<d:principal-collection-set>'.\Smart::escape_html((string)\SMART_WEBDAV_CARDDAV_ABOOK_PPS).'</d:principal-collection-set>'; // iOS Fix
		$ext_prop .= '<d:principal-URL><d:href>'.\Smart::escape_html((string)\SMART_WEBDAV_CARDDAV_ABOOK_ACC).'</d:href></d:principal-URL>'; // iOS Fix
		//--
		switch((int)self::testIsAddressbookCollection($dav_vfs_path)) {
			case 1: // addressbook
				$restype  .= '<card:addressbook/>';
				$ext_prop .= '<card:supported-address-data><card:address-data-type content-type="text/vcard"/></card:supported-address-data>'; // version="3.0"
				$ext_prop .= '<card:max-resource-size>'.\Smart::format_number_int(self::$carddav_max_res_size,'+').'</card:max-resource-size>';
				// no component set specifications
				break;
			case 2: // principal
				$restype  .= '<d:principal/>';
				$ext_prop .= '<card:addressbook-home-set><d:href>'.\Smart::escape_html((string)\SMART_WEBDAV_CARDDAV_ABOOK_HOME).'</d:href></card:addressbook-home-set>';
				break;
			default:
				// nothing to add
		} //end if
		//--
		return array(
			'dav-resource-type' 		=> (string) \SmartModExtLib\Webdav\DavServer::DAV_RESOURCE_TYPE_COLLECTION,
			'dav-request-path' 			=> (string) \rtrim($dav_request_path, '/').'/',
			'dav-vfs-path' 				=> (string) $dav_vfs_path, // private
			'date-creation-timestamp' 	=> (int) 	0, // \SmartFileSystem::get_file_ctime($dav_vfs_path), // currently is unused
			'date-modified-timestamp' 	=> (int) 	\SmartFileSystem::get_file_mtime($dav_vfs_path),
			'size-bytes' 				=> (int)    0,
		//	'etag-hash' 				=> '', // if etag is empty will not show
			'mime-type' 				=> (string) self::mimeTypeDir($dav_vfs_path),
			'c-xml-restype' 			=> (string) $restype,
			'c-xml-data' 				=> (string) $ext_prop
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
