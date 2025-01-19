<?php
// [LIB - Smart.Framework / Plugins / Mail Mime Decode]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Mail Mime Decoder and Parser
// DEPENDS:
//	* SmartUnicode::
//	* Smart::
//	* SmartFileSysUtils::
//	* SmartDetectImages::
//	* SmartMailerNotes::
//======================================================

// [REGEX-SAFE-OK]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

/**
 * Class: SmartMailerMimeDecode - provides an eMail MIME decoder.
 * This class is for very advanced use.
 *
 * It just implements the mime decoding to a PHP array by decoding Mime Email Messages.
 * To easy parse mime messages and display them on-the-fly use the class: Smart Mailer Mime Parser.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: SmartUnicode, Smart, SmartFileSysUtils, SmartDetectImages
 * @version 	v.20250107
 * @package 	Plugins:Mailer
 *
 */
final class SmartMailerMimeDecode {

	// ->


	//-- export
	/**
	 * @var ARRAY
	 * @ignore
	 */
	public $arr_heads;
	/**
	 * @var ARRAY
	 * @ignore
	 */
	public $arr_parts;
	/**
	 * @var ARRAY
	 * @ignore
	 */
	public $arr_atts;
	//-- temporary
	private $last_charset;
	private $last_fname;
	private $last_cid;
	private $cycle;
	//-- set
	private $local_charset = 'ISO-8859-1';
	//--

	//-- a restricted list with the allowed charsets used for implicit detection (for explicit detection any charset can be used) ; using a restricted list is a safety measure against malformed or broken strings ; ex: avoid a broken UTF-8 string to be detected as GB18030 if contains weird characters
	private const CONVERSION_IMPLICIT_CHARSETS = 'UTF-8, ISO-8859-1, ISO-8859-15, ISO-8859-2, ISO-8859-9, ISO-8859-3, ISO-8859-4, ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-10, ISO-8859-13, ISO-8859-14, ISO-8859-16, ASCII, SJIS, EUC-JP, JIS, ISO-2022-JP, EUC-CN, GB18030, ISO-2022-KR, KOI8-R, KOI8-U'; // Fixes: starting with PHP 7.1 it warns about illegal argument if using: ISO-8859-11 ; starting with PHP 8.1 the UTF-7 should no more be used in the list because it misbehaves: if the (plus) + character is present in a string will always detect string as being UTF-7 instead of UTF-8
	//--


	//================================================================
	/**
	 * Class constructor
	 */
	public function __construct($encoding='') {
		//--
		if((string)$encoding == '') {
			if(defined('SMART_FRAMEWORK_CHARSET')) {
				if((string)SMART_FRAMEWORK_CHARSET != '') {
					$this->local_charset = (string) SMART_FRAMEWORK_CHARSET;
				} //end if
			} //end if
		} else {
			$this->local_charset = (string) $encoding;
		} //end if
		//--
		$this->reset();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the local decoder encoding (Ex: UTF-8)
	 * @return STRING the current decoder CharSet
	 */
	public function get_working_charset() {
		//--
		return (string) $this->local_charset;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Reset this class after a parsing to avoid re-create a new clean object
	 */
	public function reset() {
		//--
		$this->arr_heads = array();
		$this->arr_parts = array();
		$this->arr_atts = array();
		//--
		$this->last_charset = '';
		$this->last_fname = 'attachment.file';
		$this->last_cid = '';
		$this->cycle = 0;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the parsed Bodies and Attachments
	 * @return ARRAY parsed Bodies and Attachments as [ 'texts' => [ 0..n array of text bodies ], 'attachments' => [ 0..n array of attachments ] ]
	 */
	public function get_bodies($message, $part_id) {
		//-- decode params
		$params 					= array();
		$params['decode_headers'] 	= true;		// Whether to decode headers
		$params['include_bodies'] 	= true;		// Whether to include the body in the returned object.
		$params['decode_bodies'] 	= true; 	// Whether to decode the bodies of the parts. (Transfer encoding)
		//-- call private decode
		$obj = new SmartMailerMimeExtract((string)$message, $this->local_charset); // [OK]
		$message = ''; // free memory
		$structure = $obj->decode($params);
		//-- get decode arrays
		$this->reset();
		$this->expand_structure($structure, $part_id);
		//-- free memory
		$structure = null;
		$obj = null;
		$params = null;
		//-- what to return
		return array('texts'=>$this->arr_parts, 'attachments'=>$this->arr_atts);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Utility: separe email from name as: 'Name <email@address>'
	 * @return ARRAY [ STRING $name, STRING $email ]
	 */
	public function separe_email_from_name($y_address) {
		//--
		if(SmartUnicode::str_contains($y_address, '<')) {
			//--
			$tmp_expl = array();
			$tmp_expl = (array) explode('<', (string)$y_address);
			$tmp_name = (string) trim((string)(isset($tmp_expl[0]) ? $tmp_expl[0] : ''));
			$tmp_name = (string) trim((string)str_replace(array("'", '"', '`'), array('', '', ''), $tmp_name));
			$tmp_expl = (array) explode('>', (string)(isset($tmp_expl[1]) ? $tmp_expl[1] : ''));
			$tmp_email = (string) trim((string)(isset($tmp_expl[0]) ? $tmp_expl[0] : ''));
			$tmp_expl = array();
			//--
		} else {
			//--
			$tmp_name = '';
			$tmp_email = (string) trim((string)$y_address);
			//--
		} //end if
		//--
		return array($tmp_email, $tmp_name);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the parsed Header as array
	 * @return ARRAY [ key => value, ... ]
	 */
	public function get_header($message) {

		//== [INITS]
		//--
		$export_from_addr = '';
		$export_from_name = '';
		$export_to_addr = '';
		$export_to_name = '';
		$export_cc_addr = '';
		$export_cc_name = '';
		$export_subject = '';
		$export_date = '';
		$export_msguid = '';
		$export_msgid = '';
		$export_inreplyto = '';
		$export_replyto = '';
		$export_priority = '';
		$export_attachments = '';
		//--
		$headers = array();
		//--
		//==

		//== [ATTACHMENTS]
		//--
		$export_attachments = 0; // attachments not detected
		//--
		if(preg_match('/^content-disposition:(\s)attachment(.*);/mi', (string)$message)) { // insensitive
			$export_attachments = 1; // attachments were detected
		} //end if
		//--
		//==

		//== DECODING
		//--
		$params = array();
		$params['decode_headers'] = true;	// Whether to decode headers
		$params['include_bodies'] = false;	// Whether to include the body in the returned object.
		$params['decode_bodies'] = false; 	// Whether to decode the bodies of the parts. (Transfer encoding)
		//--
		$obj = new SmartMailerMimeExtract((string)$message, $this->local_charset); // [OK]
		$message = null;
		$structure = $obj->decode($params);
		//--
		$this->reset();
		$this->expand_structure($structure, ''); // this will be free after trying to guess atatchments
		//-- free memory
		$structure = null;
		$obj = null;
		$params = null;
		//-- some process of data
		$headers = (array) $this->arr_heads[0]; // get first header
		//--
		$this->reset();
		//--
		//==

		//== [FROM]
		$from = '';
		//--
		$headers['from'] = $headers['from'] ?? null;
		if(is_array($headers['from'])) {
			$from = (string) trim((string)$headers['from'][0]);
		} else {
			$from = (string) trim((string)$headers['from']);
		} //end if else
		//--
		if((string)$from == '') { // if from is not specified we use return path
			if(is_array($headers['return-path'])) {
				$from = (string) trim((string)$headers['return-path'][0]);
			} else {
				$from = (string) trim((string)$headers['return-path']);
			} //end if else
		} //end if
		//--
		$tmp_arr = array();
		$tmp_arr = $this->separe_email_from_name($from);
		//--
		$export_from_addr = (string) trim((string)$tmp_arr[0]);
		$export_from_name = (string) trim((string)$tmp_arr[1]);
		//--
		$tmp_arr = array();
		$from = '';
		//--
		$tmp_arr = array();
		$from = '';
		//--
		//==

		//== [TO]
		$to = '';
		//--
		$headers['to'] = $headers['to'] ?? null;
		if(is_array($headers['to'])) {
			$to = (string) trim((string)$headers['to'][0]);
		} else {
			$to = (string) trim((string)$headers['to']);
		} //end if else
		//--
		$tmp_arr = array();
		$tmp_arr = $this->separe_email_from_name($to);
		//--
		$export_to_addr = (string) trim((string)$tmp_arr[0]);
		$export_to_name = (string) trim((string)$tmp_arr[1]);
		//--
		if(SmartUnicode::str_contains($to, '[::@::]')) { // fix for netoffice :: Multi-Message
			$export_to_addr = '[@]';
			$export_to_name = '';
		} elseif(SmartUnicode::str_contains($to, '[::#::]')) { // fix for netoffice :: Fax
			$export_to_addr = '[#]';
			$export_to_name = '';
		} elseif(SmartUnicode::str_contains($to, '[::!::]')) { // Not Sent
			$export_to_addr = '[!]';
			$export_to_name = '';
		} //end if
		//--
		$tmp_arr = array();
		$to = '';
		//--
		//==

		//== [CC]
		$cc = '';
		//--
		$export_cc_addr = array();
		$export_cc_name = array();
		//--
		$headers['cc'] = $headers['cc'] ?? null;
		if(is_array($headers['cc'])) {
			$cc = (string) trim((string)$headers['cc'][0]);
		} else {
			$cc = (string) trim((string)$headers['cc']);
		} //end if else
		//--
		$arr_cc = array();
		//--
		if(SmartUnicode::str_contains($cc, ',')) {
			$arr_cc = (array) explode(',', (string)$cc);
		} else {
			$arr_cc[] = (string) $cc;
		} //end if else
		//--
		for($z=0; $z<Smart::array_size($arr_cc); $z++) {
			//--
			$tmp_arr = array();
			$tmp_arr = $this->separe_email_from_name($arr_cc[$z]);
			//--
			$export_cc_addr[] = (string) trim((string)($tmp_arr[0] ?? null));
			$export_cc_name[] = (string) trim((string)($tmp_arr[1] ?? null));
			//--
			$tmp_arr = array();
			//--
		} //end for
		//--
		$export_cc_addr = (string) implode(', ', (array) $export_cc_addr);
		$export_cc_name = (string) implode(', ', (array) $export_cc_name);
		//--
		$cc = '';
		//--
		//==

		//== [BCC]
		$bcc = '';
		//--
		$headers['bcc'] = $headers['bcc'] ?? null;
		if(is_array($headers['bcc'])) {
			$bcc = (string) trim((string)$headers['bcc'][0]);
		} else {
			$bcc = (string) trim((string)$headers['bcc']);
		} //end if else
		//--
		$tmp_arr = array();
		$tmp_arr = $this->separe_email_from_name($bcc);
		//--
		$export_bcc_addr = (string) trim((string)$tmp_arr[0]);
		$export_bcc_name = (string) trim((string)$tmp_arr[1]);
		//--
		$tmp_arr = array();
		//--
		$bcc = '';
		//--
		//==

		//== [SUBJECT]
		$subj = '';
		//--
		$headers['subject'] = $headers['subject'] ?? null;
		if(is_array($headers['subject'])) {
			$subj = (string) trim((string)$headers['subject'][0]);
		} else {
			$subj = (string) trim((string)$headers['subject']);
		} //end if else
		//--
		$export_subject = (string) trim((string)$subj);
		//--
		if((string)$export_subject == '') {
			$export_subject = '[?]';
		} //end if
		//--
		$subj = '';
		//--
		//==

		//== [DATE]
		$date = '';
		//--
		$headers['date'] = $headers['date'] ?? null;
		if(is_array($headers['date'])) {
			$date = (string) trim((string)$headers['date'][0]);
		} else {
			$date = (string) trim((string)$headers['date']);
		} //end if else
		//--
		$export_date = (string) trim((string)preg_replace('/[^0-9a-zA-Z,\+\:\-]/', ' ', (string)$date)); // fix: remove invalid characters in date
		//--
		if((string)$export_date == '') {
			$export_date = '(?)';
		} //end if
		//--
		$date = '';
		//--
		//==

		//== [MESSAGE-UID]
		$msguid = '';
		//--
		$headers['x-universally-unique-identifier'] = $headers['x-universally-unique-identifier'] ?? null;
		if(is_array($headers['x-universally-unique-identifier'])) {
			$msguid = (string) trim((string)$headers['x-universally-unique-identifier'][0]);
		} else {
			$msguid = (string) trim((string)$headers['x-universally-unique-identifier']);
		} //end if else
		//--
		$msguid = (string) trim((string)str_replace(array('<', '>'), array('', ''), (string)$msguid));
		//--
		$export_msguid = (string) trim((string)$msguid);
		//--
		$msguid = '';
		//--
		//==

		//== [MESSAGE-ID]
		$msgid = '';
		//--
		$headers['message-id'] = $headers['message-id'] ?? null;
		if(is_array($headers['message-id'])) {
			$msgid = (string) trim((string)$headers['message-id'][0]);
		} else {
			$msgid = (string) trim((string)$headers['message-id']);
		} //end if else
		//--
		$msgid = (string) trim((string)str_replace(array('<', '>'), array('', ''), (string)$msgid));
		//--
		$export_msgid = (string) trim((string)$msgid);
		//--
		$msgid = '';
		//--
		//==

		//== [IN-REPLY-TO]
		$inreplyto = '';
		//--
		$headers['in-reply-to'] = $headers['in-reply-to'] ?? null;
		if(is_array($headers['in-reply-to'])) {
			$inreplyto = (string) trim((string)$headers['in-reply-to'][0]);
		} else {
			$inreplyto = (string) trim((string)$headers['in-reply-to']);
		} //end if else
		//--
		$inreplyto = (string) trim((string)str_replace(array('<', '>'), array('', ''), (string)$inreplyto));
		//--
		$export_inreplyto = (string) trim((string)$inreplyto);
		//--
		$inreplyto = '';
		//--
		//==

		//== [REPLY-TO] :: if reply to is an email address reply should respond to this address (this is a standard) !
		$replyto = '';
		//--
		$headers['reply-to'] = $headers['reply-to'] ?? null;
		if(is_array($headers['reply-to'])) {
			$replyto = (string) trim((string)$headers['reply-to'][0]);
		} else {
			$replyto = (string) trim((string)$headers['reply-to']);
		} //end if else
		//--
		$tmp_arr = array();
		$tmp_arr = $this->separe_email_from_name($replyto);
		//--
		$export_replyto = (string) trim((string)$tmp_arr[0]);
		//--
		$tmp_arr = array();
		//--
		$replyto = '';
		//--
		//==

		//== [PRIORITY] :: ( 1=high, 3=normal, 5=low )
		$priority = '';
		//--
		$headers['x-priority'] = $headers['x-priority'] ?? null;
		if(is_array($headers['x-priority'])) {
			$priority = (string) trim((string)$headers['x-priority'][0]);
		} else {
			$priority = (string) trim((string)$headers['x-priority']);
		} //end if else
		//--
		switch((string)strtolower((string)$priority)) {
			case 'high':
			case '0':
			case '1':
			case '2':
				$export_priority = '1'; //high
				break;
			case 'low':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
			case '10':
				$export_priority = '5'; //low
				break;
			case 'normal':
			case 'medium':
			case '3':
			case '4':
			default:
				$export_priority = '3'; //medium (normal)
		} //end switch
		//--
		$priority = '';
		//--
		//==

		//== [CLEANUP]
		//--
		$headers = null;
		//--
		//==

		//== [EXPORT DATA AS ARRAY]
		return array(
			'from_addr' 	=> (string) $export_from_addr,
			'from_name' 	=> (string) $export_from_name,
			'to_addr' 		=> (string) $export_to_addr,
			'to_name' 		=> (string) $export_to_name,
			'cc_addr' 		=> (string) $export_cc_addr,
			'cc_name' 		=> (string) $export_cc_name,
			'bcc_addr'		=> (string) $export_bcc_addr,
			'bcc_name'		=> (string) $export_bcc_name,
			'subject' 		=> (string) $export_subject,
			'date' 			=> (string) $export_date,
			'message-uid' 	=> (string) $export_msguid,
			'message-id' 	=> (string) $export_msgid,
			'in-reply-to' 	=> (string) $export_inreplyto,
			'reply-to' 		=> (string) $export_replyto,
			'priority' 		=> (string) $export_priority,
			'attachments' 	=> (string) $export_attachments
		);
		//==

	} //END FUNCTION
	//================================================================


	//================================================================
	// PRIVATE
	private function expand_structure($data, $part_id) {

		//--
		$this->cycle += 1;
		//--

		//--
		$vxf_mail_part_type = '';
		$vxf_mail_part_stype = '';
		//--

		//--
		if(is_object($data)) {
			$data = (array) get_object_vars($data);
		} //end if
		//--

		//--
		if(is_array($data)) {
			//--
			foreach($data as $key => $value) {
				//--
				if(is_object($value)) {
					//--
					$this->expand_structure($value, (string)$part_id);
					//--
				} elseif(is_array($value)) {
					//-- get params from pre-body-arrays
					if((string)$key === 'ctype_parameters') {
						//--
						$value['charset'] = (string) ($value['charset'] ?? null);
						//--
						if((string)trim($value['charset']) != '') {
							//--
							$tmp_charset = (string) trim((string)SmartUnicode::str_tolower((string)($value['charset'] ?? null)));
							//--
							if(((string)$tmp_charset == '') OR ((string)$tmp_charset == 'us-ascii')) {
								$tmp_charset = 'iso-8859-1'; // correction :: {{{SYNC-CHARSET-FIX}}}
							} //end if
							//--
							$this->last_charset = (string) $tmp_charset;
							//--
						} //end if
						//--
					} elseif((string)$key === 'headers') {
						//--
						$this->arr_heads[] = $value;
						//--
						$value['content-id'] = (string) ($value['content-id'] ?? null);
						//--
						if((string)trim($value['content-id']) != '') {
							$this->last_cid = (string) str_replace([' ', '<', '>'], ['', '', ''], (string)$value['content-id']);
						} //end if
						//--
					} //end if
					//--
					$this->expand_structure($value, (string)$part_id); //recursive array
					//--
				} else {
					//--
					if($key === 'ctype_primary') {
						$vxf_mail_part_type = SmartUnicode::str_tolower((string)$value);
					} elseif($key === 'ctype_secondary') {
						$vxf_mail_part_stype = SmartUnicode::str_tolower((string)$value);
					} elseif(($key === 'name') OR ($key === 'filename')){
						$this->last_fname = (string) str_replace(' ', '_', (string)$value); // fix invalid spaces in file names
					} elseif($key === 'disposition') {
						if(SmartUnicode::str_tolower((string)$value) === 'attachment') {
							$vxf_mail_part_type = 'attachment';
						} //end if
					} elseif($key === 'body') {
						//-- calculate part id
						if(
							((string)$vxf_mail_part_type == 'text') OR
							(((string)$vxf_mail_part_type == 'message') AND ((string)$vxf_mail_part_stype == 'delivery-status')) OR
							(((string)$vxf_mail_part_type == 'application') AND ((string)$vxf_mail_part_stype == 'pgp-encrypted')) // {{{SYNC-MIMETXTPART}}}
						) {
							//--
							$tmp_part_id = 'txt_'.md5((string)trim((string)$value)); // text parts are not very long
							//--
						} else {
							//--
							$tmp_part_len = (int) strlen((string)$value); // this is the file size in bytes
							//--
							if((string)$this->last_cid == '') {
								$tmp_part_id = 'att_'.sha1((string)$this->last_fname.$tmp_part_len.SmartUnicode::sub_str((string)$value, 0, 8192).SmartUnicode::sub_str((string)$value, -8192, 8192)); // try to be unique
								$tmp_att_mod = 'normal';
							} else {
								$tmp_img_lfname_ext = '';
								$tmp_img_bycontent_ext = '';
								if((string)$this->last_fname != '') {
									$tmp_img_lfname_ext = (string) SmartFileSysUtils::extractPathFileExtension((string)$this->last_fname);
									$tmp_img_bycontent_ext = (string) SmartDetectImages::guess_image_extension_by_img_content((string)$value, false); // do not use GD here, is too expensive ...
									$tmp_img_bycontent_ext = (string) trim((string)trim((string)$tmp_img_bycontent_ext, '.'));
									// FIXES BY DOING A QUICK IMG DETECTION HERE:
									// * apple mail adds PDF as inline CID but browsers cannot display as this
									// * some cids don't have a file name so need to detect by content
									switch((string)$tmp_img_lfname_ext) {
										// {{{SYNC-MAIL-CID-IMGS}}} @ Get :: allow cids only for several types of images that can be displayed inline
										case 'svg':
										case 'gif':
										case 'png':
										case 'jpg':
										//-- + allow several other types ...
										case 'jpe':
										case 'jpeg':
											if((string)$tmp_img_bycontent_ext != '') {
												$tmp_part_id = 'cid_'.$this->last_cid; // we have an ID from cid ...
												$tmp_att_mod = 'cid';
											} else {
												$tmp_part_id = 'att_cid_'.$tmp_img_lfname_ext.'_'.$this->last_cid; // we have an ID from cid ...
												$tmp_att_mod = 'normal';
											} //end if else
											break;
										case 'webp':
												$tmp_part_id = 'cid_'.$this->last_cid; // we have an ID from cid ...
												$tmp_att_mod = 'cid';
											break;
										default:
											if((string)$tmp_img_bycontent_ext != '') {
												$tmp_part_id = 'cid_'.$this->last_cid; // we have an ID from cid ...
												$tmp_att_mod = 'cid';
											} else {
												$tmp_part_id = 'att_cid_'.$this->last_cid; // we have an ID from cid ...
												$tmp_att_mod = 'normal';
											} //end if
									} //end switch
								} //end if
							} //end if else
							//--
						} //end if else
						//--
						$tmp_part_id = (string) strtolower((string)$tmp_part_id);
						//--
						if(((string)$tmp_part_id != '') AND (((string)$part_id == '') OR (((string)trim((string)strtolower((string)$part_id)) == (string)$tmp_part_id) OR ((string)trim((string)strtolower((string)str_replace(' ', '', (string)$part_id))) == (string)$tmp_part_id)))) {
							// DEFAULT
							if(
								((string)$vxf_mail_part_type == 'text') OR
								(((string)$vxf_mail_part_type == 'message') AND ((string)$vxf_mail_part_stype == 'delivery-status')) OR
								(((string)$vxf_mail_part_type == 'application') AND ((string)$vxf_mail_part_stype == 'pgp-encrypted')) // {{{SYNC-MIMETXTPART}}}
							) {
								//--
								// TEXT / HTML PART
								//--
								if((string)trim((string)$this->last_charset) == '') {
									$this->last_charset = (string) strtolower((string)SmartUnicode::detect_encoding((string)$value, (string)self::CONVERSION_IMPLICIT_CHARSETS, true)); // use an extended list than default used on SmartUnicode as this can really match a large variety ; fallback on UTF-7 when detecting !
								} //end if
								if((string)trim((string)$this->last_charset) == '') {
									$this->last_charset = (string) SMART_FRAMEWORK_CHARSET; // don't leave empty to re-detect as SmartUnicode::CONVERSION_IMPLICIT_CHARSETS is just a subset of self::CONVERSION_IMPLICIT_CHARSETS
								} //end if
								$value = (string) SmartUnicode::convert_charset((string)$value, (string)$this->last_charset, (string)$this->local_charset); // {{{SYNC-CHARSET-CONVERT}}}
								//--
								if(((string)$vxf_mail_part_type == 'application') AND ((string)$vxf_mail_part_stype == 'pgp-encrypted')) { // {{{SYNC-EMAIL-DECODE-SMIME}}}
									$value = '----- S/MIME: '.$vxf_mail_part_type.'/'.$vxf_mail_part_stype.' -----'."\n".$value;
								} //end if
								//--
								if((string)trim((string)$value) != '') { // avoid empty text parts
									$this->arr_parts[(string)$tmp_part_id] = array(
										'type'			=> (string) 'text',
										'mode'			=> (string) $vxf_mail_part_type.'/'.$vxf_mail_part_stype,
										'charset'		=> (string) $this->last_charset,
										'description'	=> (string) 'Text Part: '.$vxf_mail_part_type.'/'.$vxf_mail_part_stype,
										'content'		=> (string) trim((string)$value),
										'@smart-log' 	=> (((string)$this->last_fname == 'smart-framework-email-send.log') ? (string)$this->last_fname : '') // the smart send log is the last part in a mime
									);
								} //end if
								//--
							} else {
								//--
								// ATTACHMENT / CID PART
								//--
								if((string)$vxf_mail_part_type == 'message') {
									$this->last_fname = 'message-part-'.sha1((string)$value).'.txt';
								} //end if
								//--
								$this->arr_atts[(string)$tmp_part_id] = array(
									'type'		=> (string) 'attachment',
									'mode'		=> (string) $tmp_att_mod,
									'filename'	=> (string) $this->last_fname,
									'filesize'	=> (int)    $tmp_part_len
								);
								if((string)$part_id == '') { // avoid include bodies for attachments except when they are express required
									$this->arr_atts[(string)$tmp_part_id]['description'] = 'Attachment: not includded (by default...)';
									$this->arr_atts[(string)$tmp_part_id]['content'] = '';
								} else {
									$this->arr_atts[(string)$tmp_part_id]['description'] = 'Attachment: includded';
									$this->arr_atts[(string)$tmp_part_id]['content'] = $value;
								} //end if else
								//--
							} //end else
							//--
						} //end if else
						//--
						//-- the body is always last in one cycle ; at the end of one cycle we reset types
						//--
						$value = ''; // free memory
						//--
						$this->last_charset = '';
						$this->last_fname = 'attachment.file';
						$this->last_cid = '';
						//--
						$vxf_mail_part_type = '';
						$vxf_mail_part_stype = '';
						//--
					} else {
						// don't know how to handle this ...
					} //end if else
					//--
				} //end else
				//--
			} //end foreach
			//--
		} //end if
		//--

	} //END FUNCTION
	//================================================================


} //END CLASS


//--------------------------------------------------------------
// Returns an array as :: Array(arr_texts, arr_atts)
//-- Usage:
//	$eml = new SmartMailerMimeDecode();
//	$head = $eml->get_header(SmartUnicode::sub_str($message, 0, 8192));
//	$msg = $eml->get_bodies($message, $part_id); // if $part_id is empty, all message will be displayed
//	$eml = null;
//--
//--------------------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// This class will parse a raw mime email and return the structure.
// Returned structure is similar to that returned by imap_fetchstructure().

// Based on PHP Pear Mime Decode Class with Modifications, Fixes and Enhancements
// Copyright (c) unix-world.org
// LICENSE: This LICENSE is in the BSD license style.
// Copyright (c) 2002-2003, Richard Heyes <richard@phpguru.org>
// Copyright (c) 2003-2006, PEAR <pear-group@php.net>
// Other Authors: George Schlossnagle <george@omniti.com>, Cipriano Groenendal <cipri@php.net>, Sean Coates <sean@php.net>

/**
 * Class Smart Mailer Mime Extract
 *
 * @access 		private
 * @internal
 *
 * @depends 	classes: Smart, SmartUnicode, SmartMailerNotes
 * @version 	v.20250107
 *
 */
final class SmartMailerMimeExtract {

	// ->


	//================================================================
		//--
		private $charset = 'ISO-8859-1';	// The charset
		//--
		private $_header;					// The header part of the input 				:: @var string
		private $_body;						// The body part of the input 					:: @var string
		private $_error; 					// Store last error								:: @var string
		private $_include_bodies;			// whether to include bodies in returned object :: @var boolean
		private $_decode_bodies;			// Flag to determine whether to decode bodies 	:: @var boolean
		private $_decode_headers;			// Flag to determine whether to decode headers 	:: @var boolean
		//--
		private $errors;					// errors log
		//--
		private const MIME_ERR_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8.893 1.5c-.183-.31-.52-.5-.887-.5s-.703.19-.886.5L.138 13.499a.98.98 0 0 0 0 1.001c.193.31.53.501.886.501h13.964c.367 0 .704-.19.877-.5a1.03 1.03 0 0 0 .01-1.002L8.893 1.5zm.133 11.497H6.987v-2.003h2.039v2.003zm0-3.004H6.987V5.987h2.039v4.006z"/></svg>';
		//--
	//================================================================


	//================================================================
	// Constructor.
	// Sets up the object, initialise the variables, and splits and stores the header and body of the input.
	// @param string (The input to decode)
	// @access public
	public function __construct($input, $encoding='') {
		//--
		if((string)$encoding == '') {
			if(defined('SMART_FRAMEWORK_CHARSET')) {
				if((string)SMART_FRAMEWORK_CHARSET != '') {
					$this->charset = (string) SMART_FRAMEWORK_CHARSET;
				} //end if
			} //end if
		} else {
			$this->charset = (string) $encoding;
		} //end if
		//--
		list($header, $body) = $this->_splitBodyHeader($input);
		//--
		$this->_header 			= $header;
		$this->_body 			= $body;
		$this->_include_bodies 	= true;
		$this->_decode_bodies  	= false;
		$this->_decode_headers 	= false;
		//--
		$this->errors 			= [];
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public function get_errors() {
		//--
		return (array) $this->errors;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public function get_working_charset() {
		//--
		return (string) $this->charset;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Begins the decoding process. (no more accepts to be called statically)
	// @param array An array of various parameters that determine various things:
	// :: include_bodies - Whether to include the body in the returned object.
	// :: decode_bodies  - Whether to decode the bodies of the parts. (Transfer encoding)
	// :: decode_headers - Whether to decode headers input (If called statically, this will be treated as the input)
	// @return object Decoded results
	// @access public
	public function decode($params = null) {

		//-- Called via an object
		$this->_include_bodies = isset($params['include_bodies']) ? $params['include_bodies']  : false;
		$this->_decode_bodies  = isset($params['decode_bodies'])  ? $params['decode_bodies']   : false;
		$this->_decode_headers = isset($params['decode_headers']) ? $params['decode_headers']  : false;
		//--
		$structure = $this->_decode($this->_header, $this->_body);
		//--
		if($structure === false) {
			$structure = $this->registerError($this->_error);
		} //end if
		//--

		//--
		return $structure;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	private function _re_encode_part_as_b64($part) {
		//--
		return (string) trim((string)chunk_split((string)Smart::b64_enc((string)$part), 76, "\r\n"));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Performs the decoding. Decodes the body string passed to it.
	// If it finds certain content-types it will call itself in a recursive fashion.
	// @param string Header section
	// @param string Body section
	// @return object Results of decoding process
	// @access private
	private function _decode($headers, $body, $default_ctype = 'text/plain') {

		//--
		$return = new stdClass;
		//--
		$headers = $this->_parseHeaders($headers);
		//--

		//--
		foreach($headers as $u => $value) {
			//--
			if(isset($return->headers[SmartUnicode::str_tolower($value['name'])]) AND !is_array($return->headers[SmartUnicode::str_tolower($value['name'])])) {
				$return->headers[SmartUnicode::str_tolower($value['name'])]   = array($return->headers[SmartUnicode::str_tolower($value['name'])]);
				$return->headers[SmartUnicode::str_tolower($value['name'])][] = $value['value'];
			} elseif(isset($return->headers[SmartUnicode::str_tolower($value['name'])])) {
				$return->headers[SmartUnicode::str_tolower($value['name'])][] = $value['value'];
			} else {
				$return->headers[SmartUnicode::str_tolower($value['name'])] = $value['value'];
			} //end if else
			//--
		} //end foreach
		//--

		//--
		reset($headers);
		//--
		foreach($headers as $key => $value) {
			//--
			$headers[(string)$key]['name'] = (string) strtolower($headers[(string)$key]['name']);
			//--
			switch((string)$headers[(string)$key]['name']) {
				case 'content-type':
					$content_type = $this->_parseHeaderValue($headers[(string)$key]['value']);
					$regs = array();
					if(preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', (string)$content_type['value'], $regs)) {
						$return->ctype_primary   = $regs[1];
						$return->ctype_secondary = $regs[2];
					} //end if
					$content_type['other'] = $content_type['other'] ?? null;
					//if(isset($content_type['other'])) {
					if(is_array($content_type['other'])) {
						foreach($content_type['other'] as $p_name => $p_value) {
							//--
							if((string)$p_name == 'charset') {
								$content_charset = $p_value ; // charset
							} //end if
							//--
							$return->ctype_parameters[$p_name] = $p_value;
							//--
						} //end while
					} //end if
					break;
				case 'content-disposition';
					$content_disposition = $this->_parseHeaderValue($headers[(string)$key]['value']);
					$content_disposition['other'] = $content_disposition['other'] ?? null;
					$return->disposition = $content_disposition['value'];
					//if(isset($content_disposition['other'])) {
					if(is_array($content_disposition['other'])) {
						foreach($content_disposition['other'] as $p_name => $p_value) {
							$return->d_parameters[$p_name] = $p_value;
						} //end while
					} //end if
					break;
				case 'content-transfer-encoding':
					$content_transfer_encoding = $this->_parseHeaderValue($headers[(string)$key]['value']);
					break;
			} //end switch
			//--
		} //end while
		//--

		//--
		if($this->_include_bodies !== true) {
			return $return; // fix by unixman: stop here if not include bodies
		} //end if
		//--

		//--
		if(isset($content_type)) {
			//--
			switch((string)strtolower((string)$content_type['value'])) {
				case 'text/plain':
				case 'message/delivery-status':
					//--
					$encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
					$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
					//--
					break;
				case 'text/html':
					//--
					$encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
					$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
					//--
					break;
				case 'multipart/parallel':
				case 'multipart/report': // RFC1892
				case 'multipart/signed': // PGP
				case 'multipart/encrypted': // GPG
				case 'multipart/digest':
				case 'multipart/alternative':
				case 'multipart/related':
				case 'multipart/mixed':
					//--
					if(!isset($content_type['other']['boundary'])){
						$this->_error = 'No boundary found for ' . $content_type['value'] . ' part';
						return false;
					} //end if
					//-- the default part is text/plain, except for message/digest where is message/rfc822
					$default_ctype = ((string)strtolower((string)$content_type['value']) === 'multipart/digest') ? 'message/rfc822' : 'text/plain';
					//--
					$parts = (array) $this->_boundarySplit($body, $content_type['other']['boundary']);
					//--
					for($i=0; $i<Smart::array_size($parts); $i++) {
						//--
						list($part_header, $part_body) = $this->_splitBodyHeader($parts[$i]);
						$part = $this->_decode($part_header, $part_body, $default_ctype);
						//--
						if($part === false) {
							$part = $this->registerError($this->_error);
						} //end if
						//--
						$return->parts[] = $part;
						//--
					} //end for
					//--
					break;
				case 'message/smart-framework-msg-notes-bfenc-by-acc':
					//--
					$len_body = (int) strlen((string)$body);
					if((string)trim((string)strtolower((string)$content_transfer_encoding['value'])) == 'base64') {
						$body = (string) SmartMailerNotes::decrypt_apple_notes_as_eml_message($body, true); // decrypt and get back b64 encoded
					} else {
						$body = '[ERROR] Content Transfer Encoding for Notes must be Base64'; // invalid body encoding, expects base64
					} //end if
					//--
					if(((string)trim((string)$body) == '') OR (strpos((string)$body, (string)SmartMailerNotes::encrypted_eml_message_as_apple_notes_signature()."\r\n") !== 0)) { // on error, display an error message (if failed to decode)
						//-- if error, re-compose a mime message to display this error because at this stage requires the part to be message/rfc822, as it is expected below
						$newboundary = '_=====-_MimePart._0000000000_.-0000000000-_-=====_';
						$errbody = (string) SmartUnicode::sub_str($body, 0, 2048); // {{{SYNC-INVALID-APPLENOTE-MAXLEN-BODY-ERR}}} ; must be 1024 + something, but not too much
						$body = ''; // reset body and re-compose below
						$body .= 'Mime-Version: 1.0'."\r\n";
						$body .= 'From: <mail.decode@smart.framework>'."\r\n";
						$body .= 'To: <mail.display@smart.framework>'."\r\n";
						$body .= 'Subject: Decrypt Errors for mime part type ('.Smart::normalize_spaces($content_type['value'].' / '.$content_transfer_encoding['value']).')'."\r\n";
						$body .= 'Date: '.Smart::normalize_spaces(date('D, d M Y H:i:s O'))."\r\n";
						$body .= 'Content-Type: multipart/mixed; boundary="'.$newboundary.'"'."\r\n";
						$body .= "\r\n";
						$body .= 'This is a Smart.Framework re-encrypted multi-part apple/note in MIME format to display decrypt errors.'."\r\n";
						$body .= "\r\n";
						$body .= '--'.$newboundary."\r\n";
						$body .= 'Content-Type: text/html'."\r\n";
						$body .= 'Content-Transfer-Encoding: base64'."\r\n";
						$body .= 'Content-Disposition: inline; filename="mime-decoding-errors.html"'."\r\n";
						$body .= "\r\n";
						$body .= (string) $this->_re_encode_part_as_b64('<br><img alt="Mime Decoding Error" title="Mime Decoding Error" align="right" width="96" height="96" src="data:image/svg+xml;base64,'.Smart::escape_html((string)Smart::b64_enc((string)self::MIME_ERR_SVG)).'"><div title="'.Smart::escape_html((string)Smart::normalize_spaces((string)$content_type['value'])).'" style="text-align:left; background:#FCFCFC; border: 1px solid #ECECEC; border-radius:3px;"><h1 style="color:#FF3300;">Smart.Framework :: MIME DECODE ERROR :: Encrypted Apple/Note</h1><h2>Failed to decrypt the main part of this note</h2><h3>Technical Info: [&nbsp;Bytes='.(int)$len_body.'&nbsp;/&nbsp;Encoding='.Smart::escape_html((string)Smart::normalize_spaces((string)$content_transfer_encoding['value'])).'&nbsp;]</h3><pre style="white-space: pre-wrap;">'.Smart::escape_html((string)$errbody).'</pre>')."\r\n";
						$body .= "\r\n";
						$body .= '--'.$newboundary.'--'."\r\n";
						$errbody = '';
						$newboundary = '';
						$content_transfer_encoding['value'] = 'base64';
						//--
					} //end if
					//--
					$body = (string) $this->_re_encode_part_as_b64($body); // after decrypt or error re-compose need to encode back to base64 as it is expected below
					//--
					$len_body = 0;
					$content_type['value'] = 'message/rfc822';
					$obj = new SmartMailerMimeExtract($this->_decodeBody($body, $content_transfer_encoding['value']), $this->charset); // [OK]
					$return->parts[] = $obj->decode(array('include_bodies' => $this->_include_bodies, 'decode_bodies' => $this->_decode_bodies));
					//--
					$obj = null;
					//--
					break;
				case 'message/rfc822':
				case 'message/partial':
				case 'partial/message': // fake type to avoid Google and Yahoo to show the Un-Encoded part
					//--
					$obj = new SmartMailerMimeExtract($this->_decodeBody($body, $content_transfer_encoding['value']), $this->charset); // [OK]
					$return->parts[] = $obj->decode(array('include_bodies' => $this->_include_bodies, 'decode_bodies' => $this->_decode_bodies));
					//--
					$obj = null;
					//--
					break;
				default:
					//--
					if(!isset($content_transfer_encoding['value'])) {
						$content_transfer_encoding['value'] = '7bit';
					} //end if
					//--
					$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $content_transfer_encoding['value']) : $body) : null;
					//--
					break;
			} //end switch
			//--
		} else {
			//--
			$ctype = (array) explode('/', (string)$default_ctype);
			$return->ctype_primary   = (string) trim((string)(isset($ctype[0]) ? $ctype[0] : ''));
			$return->ctype_secondary = (string) trim((string)(isset($ctype[1]) ? $ctype[1] : ''));
			$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body) : $body) : null;
			//--
		} //end if else
		//--

		//--
		return $return;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	// Given a string containing a header and body
	// section, this function will split them (at the first
	// blank line) and return them.
	// @param string Input to split apart
	// @return array Contains header and body section
	// @access private
	private function _splitBodyHeader($input) {
		//--
		$match = array();
		if(preg_match("/^(.*?)\r?\n\r?\n(.*)/s", (string)$input, $match)) {
			return array((string)$match[1], (string)$match[2]);
		} //end if
		//--
		$this->_error = 'Could not split header and body';
		//--
		//return false;
		return array((string)$input, ''); // bug fix: in the case the header is not separed by body we consider the message is only a header with empty body
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Parse headers given in $input and return
	// as assoc array.
	// @param string Headers to parse
	// @return array Contains parsed headers
	// @access private
	private function _parseHeaders($input) {
		//--
		$return = array();
		//--
		if((string)$input !== '') {
			//-- Unfold the input
			$input   = (string) preg_replace("/\r?\n/", "\r\n", (string)$input);
			$input   = (string) preg_replace("/\r\n(\t| )+/", ' ', (string)$input);
			$headers = (array)  explode("\r\n", (string)trim((string)$input));
			//--
			foreach($headers as $u => $value) {
				//--
				$pos = SmartUnicode::str_pos($value, ':'); // return mixed: INTEGER or FALSE
				//--
				if($pos) { // if not false and not zero
					//--
					$hdr_name  = (string) SmartUnicode::sub_str((string)$value, 0, $pos);
					$hdr_value = (string) SmartUnicode::sub_str((string)$value, $pos+1);
					//--
					/*
					if((string)$hdr_value[0] == ' ') {
						$hdr_value = (string) SmartUnicode::sub_str($hdr_value, 1);
					} //end if
					*/
					$hdr_name  = (string) trim((string)$hdr_name);
					$hdr_value = (string) trim((string)$hdr_value, ' '); // trim spaces on both sides ; this is a bug fix of the above commented code
					//--
					$return[] = [
						'name'  => (string) $hdr_name,
						'value' => (string) ($this->_decode_headers ? (string)$this->_decodeHeader($hdr_value) : (string)$hdr_value)
					];
					//--
				} //end if
				//--
			} //end foreach
			//--
		} else {
			//--
			$return = array();
			//--
		} //end if else
		//--
		return (array) $return;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Function to parse a header value,
	// extract first part, and any secondary
	// parts (after ;) This function is not as
	// robust as it could be. Eg. header comments
	// in the wrong place will probably break it.
	// @param string Header value to parse
	// @return array Contains parsed result
	// @access private
	private function _parseHeaderValue($input) {
		//--
		$return = [];
		//--
		$pos = SmartUnicode::str_pos($input, ';'); // mixed: INTEGER or FALSE
		//--
		if($pos !== false) {
			//--
			$return['value'] = (string) trim((string)SmartUnicode::sub_str($input, 0, $pos));
			$input = (string) trim((string)SmartUnicode::sub_str($input, $pos+1));
			//--
			if((string)$input != '') {
				//-- This splits on a semi-colon, if there's no preceeding backslash. Can't handle if it's in double quotes however. (Of course anyone sending that needs a good slap).
				$parameters = (array) preg_split('/\s*(?<!\\\\);\s*/i', (string)$input);
				//--
				for($i=0; $i<Smart::array_size($parameters); $i++) {
					//--
					$pos = SmartUnicode::str_pos($parameters[$i], '='); // mixed: INTEGER or FALSE
					//--
					if($pos !== false) {
						//--
						$param_name  = (string) trim((string)SmartUnicode::sub_str((string)$parameters[$i], 0, $pos)); // added TRIM to fix invalid ' = ' case
						$param_value = (string) trim((string)SmartUnicode::sub_str((string)$parameters[$i], $pos + 1)); // added TRIM to fix invalid ' = ' case
						//--
						/*
						if((string)$param_value[0] == '"') {
							$param_value = (string) SmartUnicode::sub_str((string)$param_value, 1, -1);
						} //end if
						*/
						$param_value = (string) trim((string)$param_value, '"'); // trim quotes on both sides ; this is a bug fix of the above commented code
						//--
						$return['other'] = $return['other'] ?? null;
						if(!is_array($return['other'])) {
							$return['other'] = [];
						} //end if
						$return['other'][(string)$param_name] = (string) $param_value;
						$return['other'][(string)SmartUnicode::str_tolower((string)$param_name)] = (string) $param_value;
						//--
					} //end if
					//--
				} //end for
				//--
			} //end if
			//--
		} else {
			//--
			$return['value'] = (string) trim((string)$input);
			//--
		} //end if else
		//--
		return (array) $return;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// UNIXW :: (FIXED)
	// This function splits the input based on the given boundary
	// @param string Input to parse
	// @return array Contains array of resulting mime parts
	// @access private
	private function _boundarySplit($input, $boundary) {
		//--
		$tmp = (array) explode((string)'--'.$boundary, (string)$input);
		//--
		$parts = [];
		//--
		for($i=1; $i<Smart::array_size($tmp); $i++) {
			$parts[] = (string) $tmp[$i];
		} //end for
		//--
		return (array) $parts;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// UNIXW :: (FIXED)
	// Given a header, this function will decode it
	// according to RFC2047. Probably not *exactly*
	// conformant, but it does pass all the given
	// examples (in RFC2047).
	// @param string Input header value to decode
	// @return string Decoded header value
	// @access private
	private function _decodeHeader($input) {
		//-- Remove white space between encoded-words
		$input = (string) preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', (string)$input); // insensitive
		//-- For each encoded-word...
		$matches = array();
		while(preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', (string)$input, $matches)) { // insensitive
			//--
			$encoded  = (string) ($matches[1] ?? null);
			$charset  = (string) ($matches[2] ?? null);
			$encoding = (string) ($matches[3] ?? null);
			$text     = (string) ($matches[4] ?? null);
			//--
			if(((string)$charset == '') OR ((string)strtolower((string)$charset) == 'us-ascii')) {
				$charset = 'iso-8859-1'; // correction :: {{{SYNC-CHARSET-FIX}}}
			} //end if
			//--
			switch((string)strtoupper((string)$encoding)) {
				case 'B':
					$text = (string) Smart::b64_dec((string)$text);
					$text = (string) SmartUnicode::convert_charset((string)$text, (string)$charset, (string)$this->charset); // {{{SYNC-CHARSET-CONVERT}}}
					break;
				case 'Q':
					$text = (string) str_replace('_', ' ', (string)$text); // {{{SYNC-QUOTED-PRINTABLE-FIX}}} Fix: for google mail subjects ; normally on QP the _ must be encoded as =5F ; because google mail use the _ instead of space in all emails subject, it is considered a major enforcement to support this replacement
					$text = (string) quoted_printable_decode((string)$text);
					$text = (string) SmartUnicode::convert_charset((string)$text, (string)$charset, (string)$this->charset); // {{{SYNC-CHARSET-CONVERT}}}
					break;
				default:
					// as is
			} //end switch
			//--
			$input = (string) str_replace((string)$encoded, (string)$text, (string)$input);
			//--
		} //end while
		//--
		return (string) $input;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// Given a body string and an encoding type,
	// this function will decode and return it.
	// @param  string Input body to decode
	// @param  string Encoding type to use.
	// @return string Decoded body
	// @access private
	private function _decodeBody($input, $encoding='') {
		//--
		switch((string)strtolower((string)$encoding)) {
			case 'base64':
				$input = (string) Smart::b64_dec((string)$input);
				break;
			case 'quoted-printable':
				$input = (string) quoted_printable_decode((string)$input);
				break;
			case 'x-uuencode':
				$input = (string) convert_uudecode((string)$input);
				break;
			case '8bit':
			case '7bit':
			default:
				// leave as is
		} //end switch
		//--
		// {{{SYNC-CHARSET-CONVERT}}} :: only text bodies will be converted using SmartUnicode::convert_charset(), but later as we do not know yet what they are really are
		//--
		return $input;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// error handler
	// return the error
	// @access private
	private function registerError($error) {
		//--
		$error = (string) trim((string)$error);
		//--
		if((string)$error == '') {
			$this->errors[] = (string) $error;
		} //end if
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//======================================================
// USAGE: (assume $input is your raw email)
// $decode = new SmartMailerMimeExtract($input, $charset); // [OK]
// $structure = $decode->decode(...see params[arr]...);
// print_r($structure);
//======================================================


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
