<?php
// [LIB - Smart.Framework / Webdav / Library Admin Dav:Fs]
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Webdav\DavFileSystem
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
 * Dav FileSystem (WebDAV)
 * @ignore
 */
final class DavFileSystem {

	// ::
	// v.20250714

	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodOptions() : int { // 200
		//--
		\http_response_code(200);
		//--
		\header('Date: '.\date('D, d M Y H:i:s O'));
		\header('Content-length: 0');
		\header('MS-Author-Via: DAV'); // Microsoft clients are set default to the Frontpage protocol unless we tell them to use DAV
		\header('DAV: 1, 2'); // [DAV 2 is supposed to allow LOCK / UNLOCK, but is not necessary because file PUT are using UUIDs to avoid conflicts or temporary overlapings ] ; DAV version 2 is req. for MacOS Finder to enable non-readonly mode
		\header('Allow: OPTIONS, HEAD, GET, PROPFIND, MKCOL, PUT, DELETE, COPY, MOVE'); // [ LOCK, UNLOCK : disabled, since MacOS Finder is buggy and they were only provided for it ] not yet supported: PROPPATCH ; LOCK / UNLOCK are req. for MacOS
		\header('Accept-Ranges: none');
		\header('Z-Cloud-Service: WebDAV Server');
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
			//--
			\http_response_code(200);
			\header('Content-Type: '.self::mimeTypeDir($dav_vfs_path)); // directory
			\header('Content-length: 0');
			//--
		} elseif(\SmartFileSystem::is_type_file($dav_vfs_path)) {
			//--
			\http_response_code(200);
			\header('Content-Type: '.self::mimeTypeFile($dav_vfs_path));
			$fsize_bytes = (int) \SmartFileSystem::get_file_size($dav_vfs_path);
			\header('Content-Length: '.(int)$fsize_bytes);
			//--
			$max_fsize_etag = -2; // {{{SYNC-DEFAULT-PROPFIND-ETAG-MAX-FSIZE}}} by default don't calculate etags, is not mandatory ; !!! etags on PROPFIND / HEAD :: set = -2 to disable etags ; set to -1 to show etags for all files ; if >= 0, will show the etag only if the file size is <= with this limit (etag on PROPFIND / HEAD is not mandatory for WebDAV and may impact performance if there are a large number of files in a directory or big size files ...) ; etags will always show on PUT method
			if(\defined('\\SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE')) {
				$max_fsize_etag = (int) \SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE;
			} //end if
			$display_etag = false;
			if((int)$max_fsize_etag == -1) { // show for all files, no size matter
				$display_etag = true;
			} elseif((int)$max_fsize_etag >= 0) { // show only for files <= with this size
				if((int)$fsize_bytes <= (int)$max_fsize_etag) {
					$display_etag = true;
				} //end if
			} //end if
			if($etags === true) { // if specific ask for etag, include it (no matter what settings are ...)
				$display_etag = true;
			} //end if
			if($display_etag === true) {
				\header('ETag: "'.(string)\SmartFileSystem::get_file_md5_checksum((string)$dav_vfs_path).'"');
			} //end if
			//--
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
	public static function methodLock(?string $dav_request_path, ?string $dav_author) : int {
		//--
		// No need for Real LOCK (the file PUT is safe by using a unique Temp.UUID) ; just Emulate the LOCK - to handle compatibility with MacOS
		//--
		\header('Expires: '.\gmdate('D, d M Y', @\strtotime('-1 day')).' '.\date('H:i:s').' GMT'); // HTTP 1.0
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
		//--
		$statuscode = 200;
		\SmartModExtLib\Webdav\DavServer::answerLocked(
			'', // no dav prefix
			(string) $dav_request_path,
			(string) $dav_author,
			(int) $statuscode,
			(string) 'infinity',
			(int) 3600, // fixed 1h
			(string) '00000000-0000-0000-0000-000000000000' // just a fake UUID to emulate lock ID ...
		);
		//--
		return (int) $statuscode;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodUnlock(?string $dav_request_path, ?string $dav_author) : int {
		//--
		// Because the LOCK is just Emulate, no need for Real UNLOCK
		//--
		\header('Expires: '.\gmdate('D, d M Y', @\strtotime('-1 day')).' '.\date('H:i:s').' GMT'); // HTTP 1.0
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
		//--
		\header('Content-length: 0');
		\http_response_code(204); // no content
		//--
		return 204;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK, no dot dirs allowed ; safe against .ht* names
	public static function methodPostMkd(?string $dav_url, ?string $dav_vfs_path, ?string $dir_name) : int { // browser support to make new dir(s)
		//--
		$dav_vfs_path = (string) \trim((string)$dav_vfs_path);
		$dir_name = (string) \trim((string)$dir_name);
		//--
		if(((string)$dav_vfs_path == '') OR ((string)$dir_name == '') OR ((string)\substr((string)$dir_name, 0, 1) == '.')) { // check if dot name
			echo self::answerPostErr400('Create Directory ERROR: Invalid Directory Name (1)', (string)$dav_url);
			return 400;
		} //end if
		//--
		$dir_name = (string) \SmartUnicode::deaccent_str($dir_name);
		$dir_name = (string) \SmartModExtLib\Webdav\DavServer::safeFileName($dir_name); // {{{SYNC-SAFE-FNAME-REPLACEMENT}}}
		//--
		if(\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_path) != '1') {
			echo self::answerPostErr400('Create Directory ERROR: Invalid Directory Name (2)', (string)$dav_url);
			return 400;
		} //end if
		//--
		if(\SmartFileSysUtils::checkIfSafeFileOrDirName((string)$dir_name) != '1') {
			echo self::answerPostErr400('Create Directory ERROR: Invalid Directory Name (3)', (string)$dav_url);
			return 400;
		} //end if
		//--
		$mkdir_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$dav_vfs_path);
		if(!\SmartFileSystem::path_exists($mkdir_path)) {
			echo self::answerPostErr400('Create Directory ERROR: Invalid Directory Name (4)', (string)$dav_url);
			return 400;
		} //end if
		$mkdir_path .= (string) $dir_name;
		$mkdir_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$mkdir_path);
		if(\SmartFileSysUtils::checkIfSafePath((string)$mkdir_path) != '1') {
			echo self::answerPostErr400('Create Directory ERROR: Invalid Directory Name (5)', (string)$dav_url);
			return 400;
		} //end if
		//--
		if(!\SmartFileSystem::path_exists($mkdir_path)) {
			\SmartFileSystem::dir_create($mkdir_path, false);
		} //end if
		//--
		return 200;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodMkcol(?string $dav_vfs_path) : int { // 201 | 207 | 405 | 409 | 415
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		//Smart::log_notice(\print_r($heads,1));
		$body = (string) \SmartModExtLib\Webdav\DavServer::getRequestBody();
		//--
		if(!\array_key_exists('content-type', $heads)) {
			$heads['content-type'] = null; // PHP8 fix
		} //end if
		//--
		if((string)\trim((string)$body != '')) {
			if(\strpos((string)$heads['content-type'], '/xml') !== false) {
				\http_response_code(415); // unsupported media type (we must throw 415 for unsupport mkcol bodies which are non-standard)
				\header('Content-length: 0');
				return 415;
			} //end if
		} //end if
		//--
		$the_fname = (string) \trim((string)\SmartFileSysUtils::extractPathFileName((string)$dav_vfs_path));
		if(((string)$the_fname == '') OR (\substr($the_fname, 0, 1) == '.')) {
			\http_response_code(415); // unsupported media type (empty or dot dirs not allowed)
			return 415;
		} //end if
		//--
		if(!\SmartFileSystem::path_exists($dav_vfs_path)) {
			\SmartFileSystem::dir_create($dav_vfs_path, false);
			if(!\SmartFileSystem::is_type_dir($dav_vfs_path)) {
				\http_response_code(409); // in case of FAIL use 409: a collection cannot be created until intermediate collections have been created
				\header('Content-length: 0');
				return 409;
			} //end if
		} elseif(!\SmartFileSystem::is_type_dir($dav_vfs_path)) {
	//	} else {
			\http_response_code(405); // the destination exists and is a file but the request required to create a directory :: Method Not Allowed (File or Dir Already Exists)
			\header('Content-length: 0');
			return 405;
		} //end if
		//--
		\http_response_code(201); // HTTP/1.1 201 Created
		\header('Content-length: 0');
		return 201;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodPropfind(?string $dav_uri, ?string $dav_request_path, ?string $dav_vfs_path, bool $dav_is_root_path, ?string $dav_vfs_root) : int {
		//--
		$dav_method = 'PROPFIND';
		//--
		$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		//$body = (string) \SmartModExtLib\Webdav\DavServer::getRequestBody(); // not used ; if ever used this may contain extra XML info about making this request more particular
		//--
		if(!\array_key_exists('z-cloud-webdav-response-include', $heads)) {
			$heads['z-cloud-webdav-response-include'] = null; // PHP8 fix
		} //end if
		//--
		$etags = false;
		if(\stripos((string)$heads['z-cloud-webdav-response-include'], 'etags') !== false) { // If this head is includded in client request [ Z-Cloud-Webdav-Response-Include: etags ] will force include etags for that request, for this method (propfind)
			$etags = true;
		} //end if
		//--
		\header('Expires: '.\gmdate('D, d M Y', @\strtotime('-1 day')).' '.\date('H:i:s').' GMT'); // HTTP 1.0
		\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
		//--
		if(\SmartFileSystem::is_type_file($dav_vfs_path)) { // file
			$statuscode = 207;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				'', // no dav prefix
				(string) $dav_method,
				(string) $dav_request_path,
				(bool)   $dav_is_root_path,
				(int)    $statuscode,
				(string) $dav_uri,
				(array)  self::getItem($dav_uri, $dav_vfs_path, (bool)$etags)
			);
		} elseif(\SmartFileSystem::is_type_dir($dav_vfs_path)) { // dir
			$statuscode = 207;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				'', // no dav prefix
				(string) $dav_method,
				(string) $dav_request_path,
				(bool)   $dav_is_root_path,
				(int)    $statuscode,
				(string) $dav_uri,
				(array)  self::getItem($dav_uri, $dav_vfs_path, (bool)$etags),
				(array)  self::getQuotaAndUsageInfo($dav_vfs_root)
			);
		} else { // not found
			$statuscode = 404;
			\SmartModExtLib\Webdav\DavServer::answerMultiStatus(
				'', // no dav prefix
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


	//-- SECURITY CHECK: OK, no dot files allowed ; safe against .ht* names
	public static function methodPostUpf(?string $dav_url, ?string $dav_vfs_path) : int { // browser support to make new dir(s)
		//--
		// \print_r($_FILES);
		//--
		$result = '';
		for($i=0; $i<10; $i++) {
			// $result is mixed !!!
			$result = \SmartUtils::store_uploaded_file( // dot files and valid names are checked in this function
				(string) $dav_vfs_path, // safe against .ht* names via check in the controller lib
				'file', 		// var name
				(int) $i, 		// var index: zero (first) of multi upload $_FILES array[]
				'versioning' 	// for browser based uploads is safe to use versioning to avoid rewrite something (in webdav is not necessary because will prompt file overwrite)
			);
			if($result) {
				break;
			} //end if
		} //end if
		if($result) {
			\http_response_code(400); // upload failed for some reasons
			echo self::answerPostErr400('File #'.($i+1).' Upload Errors @ '.$result, (string)$dav_url);
			return 400;
		} //end if
		//--
		return 200;
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
		if(\SmartFileSystem::is_type_file($dav_vfs_path)) {
			\SmartFileSystem::delete((string)$dav_vfs_path);
		} //end if
		if(\SmartFileSystem::path_exists($dav_vfs_path)) {
			\http_response_code(405); // the destination exists and could not be replaced
			return 405;
		} //end if
		//--
		$tmp_vfs_path = (string) $the_dname.'.'.$the_fname.'__.TMP@-'.\Smart::uuid_10_seq().'-'.\Smart::uuid_10_num().'-'.\Smart::uuid_10_str(); // temporary file will start with a dot ; supposed that dir name ends with a slash ; !IMPORTANT! because dot files are restricted in this DAV env and thus cannot be overwritten by other processes ; more, the dot files are not listed by GET in this DAV env
		if(!\SmartFileSysUtils::checkIfSafePath((string)$tmp_vfs_path)) {
			\Smart::log_warning(__METHOD__.'() : Unsafe temporary file: '.$tmp_vfs_path);
			\http_response_code(415); // unsupported media type
			return 415;
		} //end if
		if(\SmartFileSystem::is_type_dir($tmp_vfs_path)) {
			\Smart::log_warning(__METHOD__.'() : Temporary file is a dir: '.$tmp_vfs_path);
			\http_response_code(405); // the destination exists and is a directory
			return 405;
		} //end if
		if(\SmartFileSystem::path_exists($tmp_vfs_path)) {
			\Smart::log_warning(__METHOD__.'() : Temporary file already exists: '.$tmp_vfs_path);
			\http_response_code(405); // the destination exists and could not be replaced
			return 405;
		} //end if
		//--
		$fd = @\fopen((string)$tmp_vfs_path, 'wb');
		if(!$fd) {
			\Smart::log_warning(__METHOD__.'() : Failed to Open a new File: '.$tmp_vfs_path);
			\http_response_code(423); // locked: could not achieve fopen advisory lock
			return 423;
		} //end if
		while($data = @\fread($fp, 1024*8)) {
			@\fwrite($fd, $data);
		} //end while
		//--
		@\fclose($fd);
		@\fclose($fp);
		//--
		if(!\SmartFileSystem::is_type_file((string)$tmp_vfs_path)) {
			\Smart::log_warning(__METHOD__.'() : Failed to Write the new File: '.$tmp_vfs_path);
			\http_response_code(423); // locked: could not achieve fopen advisory lock
			return 423;
		} //end if
		if(\SmartFileSystem::path_exists((string)$dav_vfs_path)) {
		//	\http_response_code(405); // the destination exists and could not be replaced
		//	return 405;
			// this may be a fix for buggy dav clients (no return but delete and replace)
			\SmartFileSystem::delete((string)$dav_vfs_path); // delete file to be replaced with new one
		} //end if
		if(!\SmartFileSystem::rename((string)$tmp_vfs_path, (string)$dav_vfs_path, true)) { // rename with atomic rewrite of destination
			\Smart::log_warning(__METHOD__.'() : Failed to rename the temporary file: '.$tmp_vfs_path.' to file: '.$dav_vfs_path);
			if(\SmartFileSystem::is_type_file((string)$tmp_vfs_path)) {
				if(!\SmartFileSystem::delete((string)$tmp_vfs_path)) { // no more valid
					\Smart::log_warning(__METHOD__.'() : Failed to remove temporary file: '.$tmp_vfs_path);
				} //end if
			} //end if
			\http_response_code(423); // locked: could not achieve fopen advisory lock
			return 423;
		} //end if
		//--
		$fsize = (int) \SmartFileSystem::get_file_size((string)$dav_vfs_path);
		if((int)$fsize != (int)$head_content_length) {
			if(!\SmartFileSystem::delete((string)$dav_vfs_path)) {
				\Smart::log_warning(__METHOD__.'() : Failed to remove invalid content length file: '.$dav_vfs_path);
			} //end if
			\http_response_code(408); // request timeout (delivered a smaller size content than expected)
			return 408;
		} //end if
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
	public static function methodGet(?string $dav_method, ?string $dav_author, ?string $dav_url, ?string $dav_request_path, ?string $dav_vfs_path, bool $dav_is_root_path, ?string $dav_vfs_root, ?string $dav_request_back_path, ?string $nfo_title, ?string $nfo_signature, ?string $nfo_prefix_crrpath, ?string $nfo_lnk_welcome, ?string $nfo_txt_welcome, ?string $nfo_svg_logo) : int { // 200 | 400 | 404 | 405 | 415 | 423
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
			$files_n_dirs = (array) (new \SmartGetFileSystem(true))->get_storage($dav_vfs_path, false, false, ''); // non-recuring, no dot files
			$fixed_vfs_dir = (string) \SmartFileSysUtils::addPathTrailingSlash((string)$dav_vfs_path);
			$fixed_dav_url = (string) \rtrim((string)$dav_url, '/').'/';
			$base_url = (string) \SmartUtils::get_server_current_url();
			$arr_f_dirs = array();
			for($i=0; $i<\Smart::array_size($files_n_dirs['list-dirs']); $i++) {
				$arr_f_dirs[] = [
					'name'  => (string) $files_n_dirs['list-dirs'][$i],
					'type'  => (string) self::mimeTypeDir((string)$files_n_dirs['list-dirs'][$i]),
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
					'DIR-NEW-INFO' 		=> (string) 'INFO: For safety the creation of new directories is enforced as this: .dot directories are not allowed and all directory names will be converted using a '.$info_restr_charset.' only.',
					'MAX-UPLOAD-INFO' 	=> (string) 'INFO: Can upload a max. of 10 files at once with a max total size of '.\SmartUtils::pretty_print_bytes((int)\SmartFileSysUtils::maxUploadFileSize(), 0, '').'. For safety the .dot files are not allowed and all file names will be converted using a '.$info_restr_charset.' only. '.$info_extensions_list.'.',
					'SHOW-POST-FORM' 	=> 'yes' // support POST
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
	public static function methodCopy(?string $dav_request_path, ?string $dav_vfs_path, ?string $dav_vfs_root) : int { // 201 | 204 | 400 | 403 | 412 | 502 | 507
		//--
		return (int) self::methodCopyOrMove('COPY', $dav_request_path, $dav_vfs_path, $dav_vfs_root); // safe on .ht* names
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	public static function methodMove(?string $dav_request_path, ?string $dav_vfs_path, ?string $dav_vfs_root) : int { // 201 | 204 | 400 | 403 | 412 | 502
		//--
		return (int) self::methodCopyOrMove('MOVE', $dav_request_path, $dav_vfs_path, $dav_vfs_root); // safe on .ht* names
		//--
	} //END FUNCTION


	//=====


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function answerPostErr400(?string $message, ?string $dav_url) : string {
		//--
		return (string) \SmartComponents::http_message_400_badrequest('ERROR: '.$message);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function answerPostErr404(?string $message, ?string $dav_url) : string {
		//--
		return (string) \SmartComponents::http_message_404_notfound((string)$message);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function methodCopyOrMove(?string $dav_method, ?string $dav_request_path, ?string $dav_vfs_path, ?string $dav_vfs_root) : int {
		//--
		$heads = (array) \SmartModExtLib\Webdav\DavServer::getRequestHeaders();
		//$body = (string) \SmartModExtLib\Webdav\DavServer::getRequestBody(); // not used
		//--
		if(!\array_key_exists('destination', $heads)) {
			$heads['destination'] = null; // PHP8 fix
		} //end if
		if(!\array_key_exists('overwrite', $heads)) {
			$heads['overwrite'] = null; // PHP8 fix
		} //end if
		//--
		if((string)\trim((string)$heads['destination']) == '') {
			\http_response_code(400); // bad request ; destination must be non-empty
			return 400;
		} //end if
		$path_raw_dest = \SmartModExtLib\Webdav\DavServer::extractPathFromCurrentURL((string)$heads['destination'], true);
		if(((string)\trim((string)$path_raw_dest) == '') OR ((string)\substr((string)\trim((string)$path_raw_dest), 0, 1) == '.')) { // safe on .ht* names
			\http_response_code(400); // bad request ; destination must be non-empty, and not start with dot
			return 400;
		} //end if
		$path_raw_dest = (string) \trim((string)$path_raw_dest);
		$path_raw_dest = (string) \SmartUnicode::deaccent_str((string)$path_raw_dest);
		$path_raw_dest = (string) \SmartModExtLib\Webdav\DavServer::safePathName($path_raw_dest);
		//--
		if(((string)$dav_request_path == (string)$path_raw_dest) OR ((string)$path_raw_dest == '')) {
			\http_response_code(405); // not allowed ; destination and source are the same
			return 405;
		} //end if
		//--
		$path_dest = (string) \SmartModExtLib\Webdav\DavServer::safePathName(\rtrim($dav_vfs_root.$path_raw_dest, '/'));
		// \Smart::log_notice($dav_method.' # Src=`'.$dav_vfs_path.'` ; Dest=`'.$path_dest.'` ; OverWr='.(string)$heads['overwrite']);
		//--
		if((string)\strtoupper((string)\trim((string)$heads['overwrite'])) == 'T') {
			$overwrite = true; // this should create a version to avoid rewrite, just on files ; on dirs will not allow
	//	} elseif((string)\strtoupper((string)\trim((string)$heads['overwrite'])) == 'F') {
	//		$overwrite = false;
		} else { // MacOS does not provide it if false
			$overwrite = false;
	//		\http_response_code(400); // bad request ; the overwrite http header should be either T or F
	//		return 400;
		} //end if else
		//--
		if($overwrite !== true) {
			if(\SmartFileSystem::path_exists($path_dest)) {
				\http_response_code(412); // precondition failed (Either the Overwrite header is "F" and the state of the destination resource is not null, or the method was used in a Depth: 0 transaction)
				return 412;
			} //end if
		} //end if
		//--
		if(\SmartFileSystem::path_exists($path_dest)) {
			if(\SmartFileSystem::is_type_dir($path_dest)) {
				\SmartFileSystem::dir_delete($path_dest, true);
			} elseif(\SmartFileSystem::is_type_file($path_dest)) {
				\SmartFileSystem::delete($path_dest);
			} else {
				\http_response_code(502); // bad gateway (unknown type: dest, perhaps located on a remote location which refuses to accept the resource)
				return 502;
			} //end if else
			$ok_answer = 204; // no content (The resource was moved successfully to a pre-existing destination URI)
		} else {
			$ok_answer = 201; // created (The resource was moved successfully and a new resource was created at the specified destination URI)
		} //end if
		//--
		$ok = -1;
		if(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			if((string)$dav_method == 'COPY') {
				$ok = \SmartFileSystem::dir_copy($dav_vfs_path, $path_dest, true);
			} elseif((string)$dav_method == 'MOVE') {
				$ok = \SmartFileSystem::dir_rename($dav_vfs_path, $path_dest);
			} else {
				\http_response_code(500); // internal server error
				return 500;
			} //end if else
		} elseif(\SmartFileSystem::is_type_file($dav_vfs_path)) {
			if((string)$dav_method == 'COPY') {
				$ok = \SmartFileSystem::copy((string)$dav_vfs_path, (string)$path_dest, false, true);
			} elseif((string)$dav_method == 'MOVE') {
				$tmp_fext = (string) \strtolower((string)\SmartFileSysUtils::extractPathFileExtension((string)$path_dest)); // get the extension
				if(!\defined('\\SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS')) { // {{{SYNC-CHK-ALLOWED-DENIED-EXT}}}
					\http_response_code(500); // internal server error
					return 500;
				} //end if
				if(\stripos((string)\SMART_FRAMEWORK_DENY_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') !== false) {
					\http_response_code(412); // precondition failed
					return 412;
				} //end if
				if(\defined('\\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS') AND ((string)\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS != '')) {
					if(\stripos((string)\SMART_FRAMEWORK_ALLOW_UPLOAD_EXTENSIONS, '<'.$tmp_fext.'>') === false) {
						\http_response_code(412); // precondition failed
						return 412;
					} //end if
				} //end if
				$ok = \SmartFileSystem::rename((string)$dav_vfs_path, (string)$path_dest, true); // rename with atomic rewrite of destination
			} else {
				\http_response_code(500); // internal server error
				return 500;
			} //end if else
		} else {
			\http_response_code(502); // bad gateway (unknown type: source, perhaps located on a remote location which refuses to accept the resource)
			return 502;
		} //end if else
		//--
		if($ok != 1) {
			if((string)$dav_method == 'COPY') {
				\http_response_code(507); // not enough space
				return 507;
			} elseif((string)$dav_method == 'MOVE') {
				\http_response_code(502); // bad gateway (something was wrong ... on moving)
				return 502;
			} else {
				\http_response_code(500); // method should be COPY or MOVE (something was wrong ... on moving)
				return 500;
			} //end if else
		} //end if
		//--
		\http_response_code((int)$ok_answer); // HTTP/1.1 201 Created or HTTP/1.1 204 No Content
		\header('Content-length: 0');
		return (int) $ok_answer;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getQuotaAndUsageInfo(?string $dav_vfs_root) : array {
		//--
		if(!\SmartFileSysUtils::checkIfSafePath((string)$dav_vfs_root)) {
			return array();
		} //end if
		//--
		$free_space = (int) \Smart::floor_number((float)\disk_free_space((string)$dav_vfs_root));
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
	private static function getItem(?string $dav_request_path, ?string $dav_vfs_path, bool $etags=false) : array {
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
			$arr[] = (array) self::getItemTypeNonCollection($dav_request_path, $dav_vfs_path, (bool)$etags); // safe on .ht* names
		} elseif(\SmartFileSystem::is_type_dir($dav_vfs_path)) {
			$arr[] = (array) self::getItemTypeCollection($dav_request_path, $dav_vfs_path); // safe on .ht* names
			$files_n_dirs = (array) (new \SmartGetFileSystem(true))->get_storage($dav_vfs_path, false, false, ''); // non-recuring, no dot files ; safe on .ht* names
			// \print_r($files_n_dirs); die();
			// \print_r($arr); die();
			$arr = self::addSubItem($dav_request_path, $dav_vfs_path, $arr, $files_n_dirs['list-dirs'], 'dirs'); // safe on .ht* names
			$arr = self::addSubItem($dav_request_path, $dav_vfs_path, $arr, $files_n_dirs['list-files'], 'files', (bool)$etags); // safe on .ht* names
			// \print_r($arr); die();
		} //end if else
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function mimeTypeDir(?string $dav_vfs_path) : string {
		//--
	//	$dav_vfs_path = (string) $dav_vfs_path; // safe on .ht* names
		//--
		return (string) 'directory'; // 'httpd/unix-directory'
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function mimeTypeFile(?string $dav_vfs_path) : string {
		//--
		return (string) \SmartFileSysUtils::getMimeType((string)$dav_vfs_path); // safe on .ht* names
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function addSubItem(?string $dav_request_path, ?string $dav_vfs_path, array $arr, array $subitems, ?string $type, bool $etags=false) : array {
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
										(string) $tmp_new_vfs_path,
										(bool)   $etags
									);
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
	private static function getItemTypeNonCollection(?string $dav_request_path, ?string $dav_vfs_path, bool $etags=false) : array {
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
		$fsize_bytes = (int) \SmartFileSystem::get_file_size($dav_vfs_path);
		//--
		$etag_file = '';
		$max_fsize_etag = -2; // {{{SYNC-DEFAULT-PROPFIND-ETAG-MAX-FSIZE}}} by default don't calculate etags, is not mandatory ; !!! etags on PROPFIND / HEAD :: set = -2 to disable etags ; set to -1 to show etags for all files ; if >= 0, will show the etag only if the file size is <= with this limit (etag on PROPFIND / HEAD is not mandatory for WebDAV and may impact performance if there are a large number of files in a directory or big size files ...) ; etags will always show on PUT method
		if(\defined('\\SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE')) {
			$max_fsize_etag = (int) \SMART_WEBDAV_PROPFIND_ETAG_MAX_FSIZE;
		} //end if
		$display_etag = false;
		if((int)$max_fsize_etag == -1) { // show for all files, no size matter
			$display_etag = true;
		} elseif((int)$max_fsize_etag >= 0) { // show only for files <= with this size
			if((int)$fsize_bytes <= (int)$max_fsize_etag) {
				$display_etag = true;
			} //end if
		} //end if
		if($etags === true) { // if specific ask for etag, include it (no matter what settings are ...)
			$display_etag = true;
		} //end if
		if($display_etag === true) {
			$etag_file = (string) \SmartFileSystem::get_file_md5_checksum((string)$dav_vfs_path);
		} //end if
		//--
		return array(
			'dav-resource-type' 		=> (string) \SmartModExtLib\Webdav\DavServer::DAV_RESOURCE_TYPE_NONCOLLECTION,
			'dav-request-path' 			=> (string) $dav_request_path,
			'dav-vfs-path' 				=> (string) $dav_vfs_path, // private
			'date-creation-timestamp' 	=> (int) 	0, // \SmartFileSystem::get_file_ctime($dav_vfs_path), // currently is unused
			'date-modified-timestamp' 	=> (int) 	\SmartFileSystem::get_file_mtime($dav_vfs_path),
			'size-bytes' 				=> (int)    $fsize_bytes,
			'etag-hash' 				=> (string) $etag_file,
			'mime-type' 				=> (string) self::mimeTypeFile($dav_vfs_path)
		);
		//--
	} //END FUNCTION


	//-- SECURITY CHECK: OK @ safe against .ht* names
	private static function getItemTypeCollection(?string $dav_request_path, ?string $dav_vfs_path) : array {
		//--
		$dav_request_path = (string) \trim((string)$dav_request_path);
		$dav_vfs_path = (string) \trim((string)$dav_vfs_path); // safe on .ht*
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
		return array(
			'dav-resource-type' 		=> (string) \SmartModExtLib\Webdav\DavServer::DAV_RESOURCE_TYPE_COLLECTION,
			'dav-request-path' 			=> (string) \rtrim($dav_request_path, '/').'/',
			'dav-vfs-path' 				=> (string) $dav_vfs_path, // private
			'date-creation-timestamp' 	=> (int) 	0, // \SmartFileSystem::get_file_ctime($dav_vfs_path), // currently is unused
			'date-modified-timestamp' 	=> (int) 	\SmartFileSystem::get_file_mtime($dav_vfs_path),
			'size-bytes' 				=> (int)    0,
		//	'etag-hash' 				=> '', // if etag is empty will not show
			'mime-type' 				=> (string) self::mimeTypeDir($dav_vfs_path)
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
