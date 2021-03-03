<?php
// [LIB - Smart.Framework / Webdav / Library Admin DavServer]
// (c) 2006-2020 unix-world.org - all rights reserved
// r.7.2.1 / smart.framework.v.7.2

// Module Lib: \SmartModExtLib\Webdav\DavServer
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
 * Dav Server
 * @ignore
 */
final class DavServer {

	// ::
	// v.20210303

	const DAV_RESOURCE_TYPE_COLLECTION = 'collection';
	const DAV_RESOURCE_TYPE_NONCOLLECTION = 'noncollection';
	const DAV_RESOURCE_TYPE_NOTFOUND = 'notfound';

	private static $httpRequestHeaders = null; // must init to null
	private static $httpRequestBody = null; // must init to null

	private static $tpl_path = 'modules/mod-webdav/libs/templates/'; // trailing slash req.


	public static function getSupportedBrowserIds() {
		//--
		return array('fox', 'crm', 'opr', 'sfr', 'iee', 'iex', 'eph', 'nsf');
		//--
	} //END FUNCTION;


	public static function getTplPath() {
		//--
		return (string) self::$tpl_path;
		//--
	} //END FUNCTION


	public static function safeCheckPathAgainstHtFiles($path) {
		//--
		if(\stripos(\SmartFileSysUtils::get_file_name_from_path($path), '.ht') === 0) { // dissalow ^\.ht files as in apache config to prevent access to .htaccess / .htpassword
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function safePathName($path) { // on WebDAV there is an issue with #
		//--
		$path = (string) \str_replace('#', '-', (string)$path); // {{{SYNC-WEBDAV-#-ISSUE}}}
		$path = (string) \Smart::safe_pathname((string)$path, '-'); // FIX: allow only safe paths :: {{{SYNC-SAFE-FNAME-REPLACEMENT}}}
		//--
		return (string) $path;
		//--
	} //END FUNCTION


	public static function safeFileName($path) { // on WebDAV there is an issue with #
		//--
		$path = (string) \str_replace('#', '-', (string)$path); // {{{SYNC-WEBDAV-#-ISSUE}}}
		$path = (string) \Smart::safe_filename((string)$path, '-'); // FIX: allow only safe paths :: {{{SYNC-SAFE-FNAME-REPLACEMENT}}}
		//--
		return (string) $path;
		//--
	} //END FUNCTION


	// used to extract path from headers like MOVE ...
	public static function extractPathFromCurrentURL($url, $urldecode=false) { // sync with SmartFrameworkRuntime::Parse_Semantic_URL()
		//--
		$base_url = (string) \SmartUtils::get_server_current_url().\SmartUtils::get_server_current_script();
		//--
		if(\strpos((string)$url, (string)$base_url) !== 0) {
			return ''; // URL must start with the current server base URL ; this is important to avoid wrong path extract if /~ occurs before php script !!!
		} //end if
		$url_path = (string) \substr($url, \strlen($base_url));
		//--
		$sem_path_pos = \strpos((string)$url_path, '/~');
		if($sem_path_pos !== false) {
			$path_url = (string) \substr((string)$url_path, ($sem_path_pos + 2));
		} else {
			$path_url = '';
		} //end if
		//--
		if($urldecode === true) {
			$path_url = (string) \rawurldecode($path_url);
		} //end if
		$path_url = (string) \ltrim($path_url, '/');
		//--
		return (string) $path_url;
		//--
	} //END FUNCTION


	public static function answerLocked($dav_prefix, $dav_req_path, $dav_author, $http_status, $lock_depth, $lock_time, $lock_uuid) {
		//--
		$dav_prefix = (string) \trim((string)$dav_prefix);
		if((string)$dav_prefix != '') {
			$dav_prefix = (string) ' '.$dav_prefix;
		} //end if
		//--
		$xml = (string) \SmartMarkersTemplating::render_file_template(
			self::$tpl_path.'answer-locked.mtpl.xml',
			[
				'DAV-XML-PREFIX' 	=> (string) $dav_prefix,
				'DAV-METHOD' 		=> (string) 'LOCK',
				'DAV-REQ-PATH' 		=> (string) $dav_req_path,
				'DAV-AUTHOR' 		=> (string) $dav_author,
				'LOCK-DEPTH' 		=> (string) $lock_depth,
				'LOCK-TIME-SEC' 	=> (int)    $lock_time,
				'LOCK-UUID' 		=> (string) $lock_uuid,
			],
			'yes' // cache
		);
		//--
		if(\headers_sent()) {
			\Smart::raise_error(
				__METHOD__.'() :: Request FAILED # Headers Already Sent'
			);
		} else {
				\http_response_code((int)$http_status);
				\header('Content-type: text/xml; charset="utf-8"');
				\header('Content-length: '.(int)\strlen($xml));
				echo((string)$xml);
		} //end if else
		//--
	} //END FUNCTION


	public static function answerMultiStatus($dav_prefix, $dav_method, $dav_req_path, $is_root_path, $http_status, $dav_req_uri, $arr_items=[], $arr_quota=[]) {
		//--
		$dav_prefix = (string) \trim((string)$dav_prefix);
		if((string)$dav_prefix != '') {
			$dav_prefix = (string) ' '.$dav_prefix;
		} //end if
		//--
		$http_status = (int) $http_status;
		if((int)$http_status != 207) {
			$http_status = 404;
			$arr_items = array();
		} //end if
		//--
		$sett_is_root = (bool) $is_root_path; // import first time
		$arr_items = (array) $arr_items;
		$item_arr = [];
		if(\Smart::array_size($arr_items) > 0) {
			foreach($arr_items as $key => $val) {
				if(\Smart::array_size($val) > 0) { // must check if array is non empty
					if((string)$val['dav-resource-type'] == (string)self::DAV_RESOURCE_TYPE_COLLECTION) {
						$val['dav-resource-type'] = (string) self::DAV_RESOURCE_TYPE_COLLECTION;
						$val['c-xml-restype'] = (string) \trim((string)$val['c-xml-restype']);
						$val['c-xml-data'] = (string) $val['c-xml-data'];
						if((string)\trim((string)$val['c-xml-restype']) == '') {
							$val['c-xml-restype'] = '<d:collection/>'; // default
						} //end if else
					} elseif((string)$val['dav-resource-type'] == (string)self::DAV_RESOURCE_TYPE_NONCOLLECTION) {
						$val['dav-resource-type'] = (string) self::DAV_RESOURCE_TYPE_NONCOLLECTION;
						$val['c-xml-restype'] = ''; // non-collection items does not use this
						$val['c-xml-data'] = (string) $val['c-xml-data'];
					} else {
						$val['dav-resource-type'] = (string) self::DAV_RESOURCE_TYPE_NOTFOUND;
						$val['c-xml-restype'] = ''; // not-found items does not use this
						$val['c-xml-data'] = ''; // not-found items have no file data
					} //end if else
					$item_arr[] = (array) [
						'IS-ROOT' 				=> (string) ($sett_is_root ? 'yes' : 'no'),
						'DAV-RESOURCE-TYPE' 	=> (string) $val['dav-resource-type'],
						'DAV-REQUEST-PATH' 		=> (string) $val['dav-request-path'],
						'DATE-CREATION' 		=> (string) \gmdate('D, d M Y H:i:s O', (int)$val['date-creation-timestamp']),
						'DATE-MODIFIED' 		=> (string) \gmdate('D, d M Y H:i:s O', (int)$val['date-modified-timestamp']),
						'SIZE-BYTES' 			=> (int)    $val['size-bytes'],
						'MIME-TYPE' 			=> (string) $val['mime-type'],
						'E-TAG' 				=> (string) $val['etag-hash'],
						'C-XML-RESOURCE-TYPE' 	=> (string) $val['c-xml-restype'],
						'C-XML-DATA' 			=> (string) $val['c-xml-data']
					];
					$sett_is_root = false; // set to false after first usage
				} //end if
			} //end foreach
		} //end if
		//--
		if(\Smart::array_size($item_arr) <= 0) {
			$http_status = 404;
			$arr_items = array();
		} //end if
		//--
		$xml = (string) \SmartMarkersTemplating::render_file_template(
			self::$tpl_path.'answer-multistatus.mtpl.xml',
			[
				'DAV-XML-PREFIX' 	=> (string) $dav_prefix,
				'DAV-METHOD' 		=> (string) $dav_method,
				'DAV-REQ-PATH' 		=> (string) $dav_req_path,
				'DAV-REQUEST-URI' 	=> (string) $dav_req_uri,
				'HTTP-STATUS' 		=> (int)    $http_status,
				'ITEM' 				=> (array) 	$item_arr,
				'QUOTA-USED' 		=> (int)    $arr_quota['used'],
				'QUOTA-FREE' 		=> (int)    $arr_quota['free']
			],
			'yes' // cache
		);
		//--
		if(\headers_sent()) {
			\Smart::raise_error(
				__METHOD__.'() :: Request FAILED # Headers Already Sent'
			);
		} else {
				\http_response_code((int)$http_status);
				\header('Content-type: text/xml; charset="utf-8"');
				\header('Content-length: '.(int)\strlen($xml));
				echo((string)$xml);
		} //end if else
		//--
	} //END FUNCTION


	/**
	 * Returns all (known) HTTP headers as Array or a specific Header as String if Name is non-empty.
	 *
	 * All headers are converted to lower-case, and additionally all underscores are automatically converted to dashes
	 *
	 * @return array / string
	 */
	public static function getRequestHeaders($name='') {
		//--
		$name = (string) \trim((string)$name);
		//--
		if(self::$httpRequestHeaders === null) {
			//--
			self::$httpRequestHeaders = [];
			//--
			foreach((array)$_SERVER as $key => $value) {
				//--
				switch((string)\strtoupper((string)$key)) {
					case 'CONTENT_LENGTH':
					case 'CONTENT_TYPE':
						self::$httpRequestHeaders[(string)\strtolower((string)\str_replace('_', '-', (string)$key))] = (string) $value;
						break;
					default :
						if(\strpos((string)$key, 'HTTP_') === 0) {
							self::$httpRequestHeaders[(string)\substr((string)\strtolower((string)\str_replace('_', '-', (string)$key)), 5)] = (string) $value;
						} //end if else
				} //end switch
			} //end foreach
			//--
		} //end if
		//--
		if((string)$name != '') {
			if(!\array_key_exists((string)\strtolower((string)\str_replace('_', '-', (string)$name)), self::$httpRequestHeaders)) {
				return '';
			} //end if
			return (string) self::$httpRequestHeaders[(string)\strtolower((string)\str_replace('_', '-', (string)$name))];
		} else {
			return (array) self::$httpRequestHeaders;
		} //end if else
		//--
	} //END FUNCTION


	/**
	 * Returns the HTTP request body as string or stream
	 *
	 * @return string / resource
	 */
	public static function getRequestBody($get_as_stream=false) {
		//--
		if(self::$httpRequestBody === null) {
			if($get_as_stream === true) {
				self::$httpRequestBody = @\fopen('php://input', 'r'); // for large file puts this is essential to avoid memory overflow
			} else {
				self::$httpRequestBody = (string) @\file_get_contents('php://input');
			} //end if else
		} //end if
		//--
		return self::$httpRequestBody; // mixed: string / resource
		//--
	} //END FUNCTION


	public static function parseXMLBody($xml, $xns='', $xkey='') {
		//--
		if(!\function_exists('\\simplexml_load_string')) {
			//--
			\Smart::raise_error('ERROR: The PHP SimpleXML Parser Extension is required for the SmartFramework XML Library');
			//--
			return array();
			//--
		} //end if
		//--
		if((string)\trim((string)$xml) == '') {
			return array();
		} //end if
		//--
		@\libxml_use_internal_errors(true);
		@\libxml_clear_errors();
		//--
		$sxe = @\simplexml_load_string( // object not array !!
			(string) $xml,
			'\\SimpleXMLElement', // this element standard class
			\LIBXML_ERR_WARNING | \LIBXML_NONET | \LIBXML_PARSEHUGE | \LIBXML_BIGLINES | \LIBXML_NOCDATA // {{{SYNC-LIBXML-OPTIONS}}} ; Fix: LIBXML_NOCDATA converts all CDATA to String
		);
		//--
		$errors = (array) @\libxml_get_errors();
		//--
		if(\Smart::array_size($errors) > 0) {
			//--
			if(\SmartFrameworkRuntime::ifDebug()) {
				$notice_log = '';
				foreach($errors as $z => $error) {
					if(\is_object($error)) {
						$notice_log .= 'PARSE-ERROR: ['.$error->code.'] / Level: '.$error->level.' / Line: '.$error->line.' / Column: '.$error->column.' / Message: '.$error->message."\n";
					} //end if
				} //end foreach
				if((string)$notice_log != '') {
					\Smart::log_notice(__METHOD__.' # NOTICE [SimpleXML]:'."\n".$notice_log."\n".'#END'."\n");
				} //end if
				\Smart::log_notice(__METHOD__.' # Debug XML-String:'."\n".$xml."\n".'#END');
			} //end if
			//--
			return array();
			//--
		} //end if
		//--
		$arr = array();
		//--
		if(!\is_object($sxe)) {
			\Smart::log_notice(__METHOD__.' # NOTICE [SimpleXML Object is Empty]');
			return array();
		} //end if
		//--
		$ns = @$sxe->getNamespaces(true);
		if(\is_array($ns)) {
			foreach($ns as $sp => $v) {
				if(((string)$xns == '') OR ((string)\strtolower((string)$xns) == (string)\strtolower((string)$sp))) {
					$child = $sxe->children($ns[(string)$sp]);
					if(\is_object($child)) {
						foreach($child as $k => $out_ns) {
							if(((string)$xkey == '') OR ((string)\strtolower((string)$k) == (string)\strtolower((string)$xkey))) {
							//	$arr[] = (string) $out_ns; // this enforcing to a string was used just for a particular situation, getting href from REPORT links ; below the code extends the xml parsing to get also arrays
								$tmp_val = \Smart::json_decode(
									(string) \Smart::json_encode(
										$out_ns, // mixed type
										false, // no pretty print
										true, // unescaped unicode
										false // html safe
									),
									true // return array
								); // mixed, normalize via json:encode/decode ; accept below only array or string
								if(\is_array($tmp_val)) {
									if(\Smart::array_size($tmp_val) <= 0) {
										$tmp_val = ''; // fix: empty array :: convert to string
									} elseif(\Smart::array_size($tmp_val) == 1) {
										if(\array_key_exists('0', $tmp_val)) {
											$tmp_val = $tmp_val[0]; // mixed ; fix: arrays with only one element [0] :: assign the element zero to the parent ; by example the REPORT href will be in this situation, thus we get it from arra[0] to *string
										} //end if
									} //end if
								} else {
									$tmp_val = (string) $tmp_val;
								} //end if
								$arr[] = $tmp_val; // mixed
								$tmp_val = ''; // reset
							} //end if
						} //end forach
					} //end if
				} //end if
			} //end foreach
		} //end if
		//--
		@\libxml_clear_errors();
		@\libxml_use_internal_errors(false);
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
