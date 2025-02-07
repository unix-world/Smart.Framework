<?php
// [LIB - Smart.Framework / Plugins / Mail Utils]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart-Framework - Mail Utils
// DEPENDS:
//	* Smart::
//	* SmartUtils::
//	* SmartFileSysUtils::
//	* SmartFileSystem::
//	* SmartMailerSend::
//	* SmartMailerMimeDecode::
//	* SmartMailerNotes::
// 	* SmartComponents::
// REQUIRED CSS:
//	* default.css
//	* email.css
//======================================================

// [REGEX-SAFE-OK]

// To test email decoding, use: {{{SYNC-TEST-EMAIL-FILE}}}

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartMailerUtils - provides various util functions for eMail like: Check/Validate, Send.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartUtils, SmartFileSysUtils, SmartFileSystem, SmartMailerSend
 * @version 	v.20250129
 * @package 	Application:Plugins:Mailer
 *
 */
final class SmartMailerUtils {

	// ::

	//==================================================================
	/**
	 * Check (Validate) eMail Address ; Can use SMTP or just validate format
	 * [PUBLIC]
	 *
	 * @param STRING $email					:: eMail Address
	 * @param BOOL $checkdomain				:: FALSE = only validate if email address is in the form of text@texte.xt | TRUE = check email with MX + SMTP validation
	 * @param STRING $helo					:: SMTP HELO (if check MX + Domain will be used, cannot be empty)
	 * @param NUMBER $smtp_port				:: SMTP Port (normal is '25')
	 * @return ARRAY 						:: ['status'] = ok / notok ; ['message'] = validation message
	 */
	public static function check_email_address(?string $email, bool $checkdomain=false, string $helo='', int $smtp_port=25) : array {

		//--
		$out = 'notok';
		$msg = '';
		//--

		//--
		$email = (string) trim((string)$email);
		//--
		if(
			((string)$email == '')
			OR
			((int)strlen((string)$email) > 255) // {{{SYNC-MAILCHECK-MAXLEN}}}
		) {
			$msg .= 'The e-mail address is empty or too long !'."\n";
		} else {
			$regex = (string) SmartValidator::regex_stringvalidation_expression('email').'i'; // insensitive, without /u modifier as it does not decodes to punnycode and must contain only ISO-8859-1 charset
			if(!preg_match((string)$regex, (string)$email)) { // {{{SYNC-MAILCHECK-FORMAT}}} check if address is valid (match pattern 'email@domain.tld')
				$msg .= 'The e-mail address does NOT match the pattern `email@domain.tld`'."\n";
			} else {
				$out = 'ok';
			} //end if else
		} //end if else
		//--

		//--
		if((string)$out == 'ok') {
			//--
			if($checkdomain === true) {
				//--
				$out = 'notok'; // reset
				//--
				if((string)$helo == '') {
					$helo = 'localhost';
				} //end if else
				//--
				$msg .= "\n".'CHECK if this is a real email address ...'."\n\n";
				$chk = (array) self::validate_mx_email_address((string)$email, (string)$helo, (int)$smtp_port);
				//--
				if((string)$chk['status'] == 'ok') {
					$out = 'ok';
				} //end if
				//--
				$msg .= $chk['message']."\n";
				//--
			} //end if
			//--
		} //end if
		//--

		//--
		return [
			'status' 	=> (string) $out,
			'message' 	=> (string) $msg
		];
		//--

	} //END FUNCTION
	//==================================================================


	//================================================================== Do MX Check
	/**
	 * Does the MX Check of eMail / Domain
	 * [PRIVATE]
	 *
	 * @param STRING $helo					:: SMTP HELO
	 * @param STRING $email					:: eMail Address
	 * @param NUMBER $smtp_port				:: SMTP Port (normal is '25')
	 * @return STRING
	 */
	private static function validate_mx_email_address(string $email, string $helo, int $smtp_port) : array {

		//--
		// will check all available MX servers from DNS
		//--

		//------------
		$out = 'notok';
		$msg = '';
		//------------

		//--
		$email = (string) trim((string)$email);
		if((string)$email == '') {
			return [
				'status' 	=> (string) $out,
				'message' 	=> (string) 'Email address is empty',
			];
		} //end if
		//--
		if((int)strlen((string)$email) > 255) { // {{{SYNC-MAILCHECK-MAXLEN}}}
			return [
				'status' 	=> (string) $out,
				'message' 	=> (string) 'Email address is too long',
			];
		} //end if
		//--
		$regex = (string) SmartValidator::regex_stringvalidation_expression('email').'i'; // insensitive, without /u modifier as it does not decodes to punnycode and must contain only ISO-8859-1 charset
		if(!preg_match((string)$regex, (string)$email)) { // {{{SYNC-MAILCHECK-FORMAT}}} check if address is valid (match pattern 'email@domain.tld')
			return [
				'status' 	=> (string) $out,
				'message' 	=> (string) 'Email address format is wrong, expects something similar with: `email@domain.tld` but have: `'.$email.'`',
			];
		} //end if
		//--

		//--
		if(
			((int)$smtp_port <= 0)
			OR
			((int)$smtp_port > 65535)
		) {
			return [
				'status' 	=> (string) $out,
				'message' 	=> (string) 'Invalid IP Port: '.(int)$smtp_port,
			];
		} //end if
		//--

		//------------
		$tmp_arr = array();
		$tmp_arr = (array)  explode('@', (string)$email);
		$domain  = (string) trim((string)($tmp_arr[1] ?? null));
		$safedom = (string) Smart::safe_validname((string)$domain);
		$tmp_arr = array();
		//------------
		if(((string)$domain == '') OR (strpos((string)$domain, '.') === false)) {
			return [
				'status' 	=> (string) $out,
				'message' 	=> (string) 'Invalid Domain for this email address ... local domains are not supported, must contain at least a dot',
			];
		} //end if
		//------------
		if(function_exists('getmxrr')) {
			if((string)$safedom != '') {
				@getmxrr((string)$safedom, $tmp_arr); // getmxrr is available also on Windows platforms since PHP 5.3
			} else {
				$msg .= 'WARNING: Empty Safe Domain Name (after-conversion) for: '.$domain;
			} //end if
		} else {
			$msg .= 'WARNING: PHP getmxrr is not available ...';
		} //end if
		//------------
		if((int)Smart::array_size($tmp_arr) <= 0) { // ERR
			$msg .= 'WARNING: No MX Records found for Domain \''.$safedom.'\''."\n";
		} else {
			$msg .= 'List of available MX Servers for Domain \''.$safedom.'\':'."\n";
			for($m=0; $m<Smart::array_size($tmp_arr); $m++) {
				$msg .= ' -> '.$tmp_arr[$m]."\n";
			} //end for
			$msg .= "\n";
		} //end if else
		//------------
		$msg .= '[Checking mail address: \''.$email.'\']'."\n";
		//------------
		for($i=0; $i<Smart::array_size($tmp_arr); $i++) {
			//--
			$domain    = (string) trim((string)$tmp_arr[$i]);
			$domain_ip = (string) @gethostbyname((string)$domain);
			//--
			$msg .= 'Start MX checking for domain: \''.$domain.'\' :: \''.$domain_ip.'\' ... '."\n";
			//--
			$smtp = new SmartMailerSmtpClient();
			$smtp->timeout = 10;
			$smtp->debug = true;
			$smtp->dbglevel = 1;
			$smtp->connect((string)$helo, (string)$domain_ip, (int)$smtp_port);
			$vfy = $smtp->mail((string)$email);
			if($vfy) {
				$vfy = $smtp->recipient($email);
			} //end if
			$smtp->quit();
			//--
			if((string)$vfy == '1') {
				//--
				$out = 'ok';
				$msg .= '[done]'."\n".'LOG: '."\n".$smtp->log."\n";
				//--
				break; //stop
				//--
			} else {
				//--
				$msg .= '[failed]'."\n".'LOG: '."\n".$smtp->log."\n";
				//--
			} //end if else
			//--
			if($i >= 3) {
				break; // do not check more than 3 servers
			} //end if
			//--
		} //end for
		//------------

		//--
		return [
			'status' 	=> (string) $out,
			'message' 	=> (string) $msg,
		];
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Send Email Mime Message from Smart.Framework to a destination with optional log of sent messages to a specific directory
	 * It will use the default server settings from configs: $configs['sendmail'][]
	 *
	 * @param STRING 		$logsend_dir 		A Directory relative path where to store send log messages OR Empty (no store): '' | 'tmp/my-email-send-log-dir'
	 * @param STRING/ARRAY 	$to					To: to@addr | [ 'to1@addr', 'to2@addr', ... ]
	 * @param STRING/ARRAY 	$cc					Cc: '' | cc@addr | [ 'cc1@addr', 'cc2@addr', ... ]
	 * @param STRING 		$bcc				Bcc: '' | bcc@addr
	 * @param STRING 		$subj				Subject: Your Subject
	 * @param STRING 		$message			Message: The body of the message
	 * @param TRUE/FALSE 	$is_html			Format: FALSE = Text/Plain ; TRUE = HTML
	 * @param ARRAY 		$attachments		* Attachments array: [] | ['file1.txt'=>'This is the file 1 content', ...] :: default is []
	 * @param STRING 		$replytoaddr 		* Reply To Addr: '' | reply-to@addr :: default is ''
	 * @param STRING 		$inreplyto			* In Reply To: '' | the ID of message that is replying to :: default is ''
	 * @param ENUM			$priority			* Priority: 1=High ; 3=Normal ; 5=Low :: default is 3
	 * @param ENUM			$charset			* charset :: default is UTF-8
	 * @return TRUE/FALSE	OPERATION RESULT [1 = OK ; 0 = send error ; -1 = error, empty config ]
	 */
	public static function send_email(?string $logsend_dir, $to, $cc, ?string $bcc, ?string $subj, ?string $message, bool $is_html, array $attachments=[], ?string $replytoaddr='', ?string $inreplyto='', ?int $priority=3, ?string $charset='UTF-8') : int {

		//-- Get Default SMTP from configs
		$def_mail_cfg = Smart::get_from_config('sendmail'); // do not cast
		if(Smart::array_size($def_mail_cfg) <= 0) {
			return -1; // warning: the default config is empty
		} //end if
		//--

		//--
		if(Smart::is_arr_or_nscalar($to) !== true) {
			$to = ''; // dissalow object or resource
		} //end if
		//--
		if(Smart::is_arr_or_nscalar($cc) !== true) {
			$cc = ''; // dissalow object or resource
		} //end if
		//--

		//-- fix: detect encrypted password and decrypt
		if(isset($def_mail_cfg['auth-password'])) {
			if(is_array($def_mail_cfg['auth-password'])) {
				//--
				if(isset($def_mail_cfg['auth-password']['oauth2'])) {
					if((int)Smart::array_size($def_mail_cfg['auth-password']['oauth2']) <= 0) {
						return -3; // invalid oauth2 settings
					} //end if
					$def_mail_cfg['auth-password'] = [
						'callable'  => [ 'SmartMailerOauth2', 'getTokenPass' ],
						'params' 	=> (array) $def_mail_cfg['auth-password']['oauth2'],
					];
				} //end if
				//--
				if(
					isset($def_mail_cfg['auth-password']['encrypted'])
					AND
					is_string($def_mail_cfg['auth-password']['encrypted'])
					AND
					(
						((string)$def_mail_cfg['auth-password']['encrypted'] == 'bf:enc')
						OR
						((string)$def_mail_cfg['auth-password']['encrypted'] == 'tf:enc')
					)
					AND
					isset($def_mail_cfg['auth-password']['data'])
					AND
					is_string($def_mail_cfg['auth-password']['data'])
					AND
					((string)trim((string)$def_mail_cfg['auth-password']['data']) != '')
				) {
					$def_mail_cfg['auth-password'] = (string) SmartCipherCrypto::tf_decrypt((string)$def_mail_cfg['auth-password']['data'], '', true); // use default key ; BF fallback ...
				} elseif(
					isset($def_mail_cfg['auth-password']['callable'])
					AND
					is_array($def_mail_cfg['auth-password']['callable'])
					AND
					((int)Smart::array_size($def_mail_cfg['auth-password']['callable']) == 2)
					AND
					(Smart::array_type_test($def_mail_cfg['auth-password']['callable']) == 1) // non-associative
					AND
					is_callable((array)$def_mail_cfg['auth-password']['callable'])
					AND
					isset($def_mail_cfg['auth-password']['params'])
					AND
					is_array($def_mail_cfg['auth-password']['params'])
				) {
					$def_mail_cfg['auth-password'] = (string) call_user_func_array((array)$def_mail_cfg['auth-password']['callable'], (array)[ 'params' => $def_mail_cfg['auth-password']['params'] ]);
					if((string)trim((string)$def_mail_cfg['auth-password']) == '') {
						return -2; // could not get password
					} //end if
				} else {
					$def_mail_cfg['auth-password'] = ''; // INVALID
					Smart::log_warning(__METHOD__.' # Invalid definition for config value: sendmail.auth-password !');
					return -1;
				} //end if
				//--
			} //end if
		} //end if
		//--

		//--
		 $arr_send_result = (array) self::send_custom_email(
			(array) $def_mail_cfg,
			$logsend_dir,
			$to,
			$cc,
			$bcc,
			$subj,
			$message,
			$is_html,
			$attachments,
			$replytoaddr,
			$inreplyto,
			$priority,
			$charset
		);
		//--

		//--
		return (int) $arr_send_result['result']; // only return the result as 0 for error and 1 for success
		//--

	} //END FUNCTION
	//==================================================================



	//==================================================================
	/**
	 * Send Email Mime Message from Smart.Framework to a destination with optional log of sent messages to a specific directory
	 * It will use custom server settings as 1st parameter: $mail_config
	 *
	 * @param ARRAY 		$mail_config 		send config array (keys that start with * are optional): [ server-mx-domain, server-host, server-port, server-ssl, *server-cafile, *auth-user, *auth-password, *auth-mode, *from-return, from-address, from-name, *use-qp-encoding, *use-min-enc-subj, *use-antispam-rules ]
	 * @param STRING 		$logsend_dir 		A Directory relative path where to store send log messages OR Empty (no store): '' | 'tmp/my-email-send-log-dir'
	 * @param STRING/ARRAY 	$to					To: to@addr | [ 'to1@addr', 'to2@addr', ... ]
	 * @param STRING/ARRAY 	$cc					Cc: '' | cc@addr | [ 'cc1@addr', 'cc2@addr', ... ]
	 * @param STRING 		$bcc				Bcc: '' | bcc@addr
	 * @param STRING 		$subj				Subject: Your Subject
	 * @param STRING 		$message			Message: The body of the message
	 * @param TRUE/FALSE 	$is_html			Format: FALSE = Text/Plain ; TRUE = HTML
	 * @param ARRAY 		$attachments		* Attachments array: [] | ['file1.txt'=>'This is the file 1 content', ...] :: default is []
	 * @param STRING 		$replytoaddr 		* Reply To Addr: '' | reply-to@addr :: default is ''
	 * @param STRING 		$inreplyto			* In Reply To: '' | the ID of message that is replying to :: default is ''
	 * @param ENUM			$priority			* Priority: 1=High ; 3=Normal ; 5=Low :: default is 3
	 * @param ENUM			$charset			* charset :: default is UTF-8
	 * @return ARRAY							[ 'result' => 'Operation RESULT', 'error' => 'ERROR Message if any', 'log' => 'Send LOG', 'message' => 'The Mime MESSAGE' ]
	 */
	public static function send_custom_email(?array $mail_config, ?string $logsend_dir, $to, $cc, ?string $bcc, ?string $subj, ?string $message, bool $is_html, array $attachments=[], ?string $replytoaddr='', ?string $inreplyto='', ?int $priority=3, ?string $charset='UTF-8') : array {

		//--
		if(Smart::is_arr_or_nscalar($to) !== true) {
			$to = ''; // dissalow object or resource
		} //end if
		//--
		if(Smart::is_arr_or_nscalar($cc) !== true) {
			$cc = ''; // dissalow object or resource
		} //end if
		//--

		//--
		$mail_config = (array) Smart::array_init_keys(
			$mail_config,
			[
				'server-mx-domain',
				'server-host',
				'server-port',
				'server-ssl',
				'server-cafile',
				'server-secure',
				'auth-user',
				'auth-password',
				'auth-mode',
				'from-return',
				'from-address',
				'from-name',
				'use-qp-encoding',
				'use-min-enc-subj',
				'use-antispam-rules',
			]
		);
		//--

		//-- SMTP connection vars
		$server_settings = [
			'smtp_mxdomain' 		=> (string) $mail_config['server-mx-domain'],
			'server_name' 			=> (string) $mail_config['server-host'],
			'server_port' 			=> (string) $mail_config['server-port'],
			'server_sslmode' 		=> (string) $mail_config['server-ssl'],
			'server_cafile' 		=> (string) $mail_config['server-cafile'],
			'server_secure' 		=> (bool)   $mail_config['server-secure'], // optional, just for SMTP
			'server_auth_user' 		=> (string) $mail_config['auth-user'], // optional, just for SMTP
			'server_auth_pass' 		=> (string) $mail_config['auth-password'], // optional, just for SMTP
			'server_auth_mode' 		=> (string) $mail_config['auth-mode'], // optional, just for SMTP (auth mode)
			'send_from_return' 		=> (string) $mail_config['from-return'], // optional (if empty, will use send_from_addr)
			'send_from_addr' 		=> (string) $mail_config['from-address'],
			'send_from_name' 		=> (string) $mail_config['from-name'],
			'use_qp_encoding' 		=> (bool)   $mail_config['use-qp-encoding'], // optional
			'use_min_enc_subj'		=> (bool)   $mail_config['use-min-enc-subj'], // optional
			'use_antispam_rules'	=> (bool)   $mail_config['use-antispam-rules'] // optional
		];
		//--

		//--
		$stmp_y = (string) date('Y');
		$stmp_m = (string) date('m');
		$stmp_d = (string) date('d');
		$stmp_time = (string) date('His');
		//--
		if((string)$mail_config['log-messages'] != 'yes') { // no
			$logsend_dir = '';
		} else { // yes
			$logsend_dir = (string) trim((string)$logsend_dir);
		} //end if else
		//--
		if((string)$logsend_dir != '') {
			//--
			$logsend_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$logsend_dir); // if the last / if not present
			$logsend_dir .= (string) $stmp_y.'/'.$stmp_y.'-'.$stmp_m.'/'.$stmp_y.'-'.$stmp_m.'-'.$stmp_d; // add the time stamps
			$logsend_dir = (string) SmartFileSysUtils::addPathTrailingSlash((string)$logsend_dir); // add the last slash finally
			//--
			SmartFileSystem::dir_create($logsend_dir, true); // recursive
			//--
			$tmp_send_mode = 'send-return';
			//--
		} else {
			//--
			$tmp_send_mode = 'send';
			//--
		} //end if else
		//--
		$arr_send_result = (array) self::send_extended_email(
			(array) $server_settings, 	// arr server settings
			(string) $tmp_send_mode, 	// send mode
			$to, // to@addr : MIXED(STRING / ARRAY)
			$cc, // cc@addr : MIXED(STRING / ARRAY)
			(string) $bcc, // bcc@addr
			(string) $subj, // subject
			(string) $message, // message
			(bool)   $is_html, // format: is-html ? TRUE : FALSE
			(array)  $attachments, // array of attachments
			(string) $replytoaddr, // reply-to@addr
			(string) $inreplyto, // in reply to Msg-Id
			(int)    $priority, // msg priority: 1 / 3 / 5
			(string) $charset // msg charset: UTF-8 | ISO-8859-1 | ...
		);
		//--
		if((string)$logsend_dir != '') {
			//--
			if(SmartFileSystem::is_type_dir($logsend_dir)) {
				//--
				if(is_array($to)) {
					$mark_to = '@multi@';
				} else {
					$mark_to = (string) $to;
				} //end if else
				//--
				SmartFileSystem::write((string)$logsend_dir.$stmp_y.$stmp_m.$stmp_d.'_'.$stmp_time.'__'.Smart::safe_validname((string)$mark_to).'__'.SmartHashCrypto::md5((string)print_r($to,1)."\v".print_r($cc,1)."\t".$subj."\n\r".$message).'.eml', (string)$arr_send_result['message']);
				//--
			} //end if
			//--
		} //end if
		//--

		//--
		return (array) $arr_send_result; // check the ['result'] as 0 for error and 1 for success
		//--

	} // END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Send Email Mime Message from Smart.Framework to a destination with many options that can be customized
	 * This is for very advanced use only.
	 *
	 * @param ARRAY			$y_server_settings	send config array (keys that start with * are optional): [ smtp_mxdomain, server_name, server_port, server_sslmode, *server_cafile, *server_auth_user, *server_auth_pass, *server_auth_mode, *send_from_return, send_from_addr, send_from_name, *use_qp_encoding, *use_min_enc_subj, *use_antispam_rules ]
	 * @param ENUM			$y_mode				mode: 'send' = do send | 'send-return' = do send + return | 'return' = no send, just return mime formated mail
	 * @param STRING/ARRAY 	$to					To: to@addr | [ 'to1@addr', 'to2@addr', ... ]
	 * @param STRING/ARRAY 	$cc					Cc: '' | cc@addr | [ 'cc1@addr', 'cc2@addr', ... ]
	 * @param STRING 		$bcc				Bcc: '' | bcc@addr
	 * @param STRING 		$subj				Subject: Your Subject
	 * @param STRING 		$message			Message: The body of the message
	 * @param TRUE/FALSE 	$is_html			Format: FALSE = Text/Plain ; TRUE = HTML
	 * @param ARRAY 		$attachments		* Attachments array: [] | ['file1.txt'=>'This is the file 1 content', ...] :: default is []
	 * @param STRING 		$replytoaddr 		* Reply To Addr: '' | reply-to@addr :: default is ''
	 * @param STRING 		$inreplyto			* In Reply To: '' | the ID of message that is replying to :: default is ''
	 * @param ENUM			$priority			* Priority: 1=High ; 3=Normal ; 5=Low :: default is 3
	 * @param ENUM			$charset			* charset :: default is UTF-8
	 * @return ARRAY							[ 'result' => 'Operation RESULT', 'error' => 'ERROR Message if any', 'log' => 'Send LOG', 'message' => 'The Mime MESSAGE' ]
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function send_extended_email(?array $y_server_settings, ?string $y_mode, $to, $cc, ?string $bcc, ?string $subj, ?string $message, bool $is_html, array $attachments=[], ?string $replytoaddr='', ?string $inreplyto='', ?int $priority=3, ?string $charset='UTF-8') {

		//--
		if(Smart::is_arr_or_nscalar($to) !== true) {
			$to = ''; // dissalow object or resource
		} //end if
		//--
		if(Smart::is_arr_or_nscalar($cc) !== true) {
			$cc = ''; // dissalow object or resource
		} //end if
		//--

		//--
		$y_server_settings = (array) Smart::array_init_keys(
			$y_server_settings,
			[
				'smtp_mxdomain',
				'server_name',
				'server_port',
				'server_sslmode',
				'server_cafile',
				'server_secure',
				'server_auth_user',
				'server_auth_pass',
				'server_auth_mode',
				'send_from_return',
				'send_from_addr',
				'send_from_name',
				'use_qp_encoding',
				'use_min_enc_subj',
				'use_antispam_rules',
			]
		);
		//--

		//-- SMTP HELO
		$server_helo 	= (string) trim((string)$y_server_settings['smtp_mxdomain']);
		if((string)$server_helo == '') {
			$server_helo = (string) SmartUtils::get_ip_client();
		} //end if
		//-- SMTP CONNECTION VARS
		$server_name 		= (string) trim((string)$y_server_settings['server_name']);
		$server_port 		= (string) trim((string)$y_server_settings['server_port']);
		$server_sslmode 	= (string) trim((string)$y_server_settings['server_sslmode']);
		$server_cafile 		= (string) trim((string)$y_server_settings['server_cafile']);
		$server_secure 		= (bool)   $y_server_settings['server_secure'];
		$server_user 		= (string) trim((string)$y_server_settings['server_auth_user']);
		$server_pass 		= (string) trim((string)$y_server_settings['server_auth_pass']);
		$server_modeauth 	= (string) trim((string)$y_server_settings['server_auth_mode']);
		//-- SEND FROM
		$send_from_addr 	= (string) trim((string)$y_server_settings['send_from_addr']);
		$send_from_return 	= (string) trim((string)$y_server_settings['send_from_return']);
		if((string)$send_from_return == '') {
			$send_from_return = (string) $send_from_addr;
		} //end if
		$send_from_name 	= (string) trim((string)$y_server_settings['send_from_name']);
		//-- MIME COMPOSE SETTINGS
		$usealways_b64 		= (bool)   ($y_server_settings['use_qp_encoding'] === true ? false : true);
		$use_min_enc_subj 	= (bool)   ($y_server_settings['use_min_enc_subj'] === false ? false : true);
		$use_antispam_rules = (bool)   ($y_server_settings['use_antispam_rules'] === false ? false : true);
		//--

		//-- mail send class init
		$mail = new SmartMailerSend();
		$mail->smtp_securemode = (bool) $server_secure;
		$mail->usealways_b64 = (bool) $usealways_b64;
		$mail->use_min_enc_subj = (bool) $use_min_enc_subj;
		$mail->use_antispam_rules = (bool) $use_antispam_rules;
		//--
		if((string)$server_name == '@mail') {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('mail', 'SEND', 'Send eMail Method Selected: [MAIL]');
			} //end if
			//-- mail method
			$mail->method = 'mail';
			//--
		} elseif((string)$server_name != '') {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('mail', 'SEND', 'Send eMail Method Selected: [SMTP]');
			} //end if
			//-- debug
			if(SmartEnvironment::ifDebug()) {
				$mail->debuglevel = 1; // default is 1
			} else {
				$mail->debuglevel = 0; // no debug
			} //end if else
			//-- smtp server method
			$mail->method = 'smtp';
			$mail->smtp_timeout = '30';
			$mail->smtp_helo = (string) $server_helo;
			$mail->smtp_server = (string) $server_name;
			$mail->smtp_port = (int) $server_port;
			$mail->smtp_ssl = (string) $server_sslmode;
			$mail->smtp_cafile = (string) $server_cafile;
			//--
			if(((string)$server_user == '') OR ((string)$server_pass == '')) {
				$mail->smtp_login = false;
			} else {
				$mail->smtp_login = true;
				$mail->smtp_user = (string) $server_user;
				$mail->smtp_password = (string) $server_pass;
				$mail->smtp_modeauth = (string) $server_modeauth;
			} //end if
			//--
		} else {
			//--
			if(SmartEnvironment::ifDebug()) {
				SmartEnvironment::setDebugMsg('mail', 'SEND', 'Send eMail Method Selected: [NONE] !!!');
			} //end if
			//--
			$mail->method = 'skip';
			//--
		} //end if else
		//--

		//-- charset
		if((string)$charset == '') {
			$charset = 'UTF-8'; // default
		} //end if
		//--
		$mail->charset = (string) $charset;
		//--

		//--
		if((string)$mail->charset != 'UTF-8') { // in this case (ISO-88591 / ISO-8859-2) we deaccent the things for maximum compatibility
			$send_from_name = (string) SmartUnicode::deaccent_str((string)$send_from_name);
			$subj 			= (string) SmartUnicode::deaccent_str((string)$subj);
			$message 		= (string) SmartUnicode::deaccent_str((string)$message);
		} //end if
		//--

		//-- Extra Mail Headers
		$mail->headers = '';
		//-- Errors Reporting Header
		$mail->headers .= 'Errors-To: '.$mail->safe_header_str((string)$send_from_addr)."\r\n";
		//-- In-Reply-To Header
		if((string)$inreplyto != '') {
			$mail->headers .= 'In-Reply-To: '.$mail->safe_header_str((string)$inreplyto)."\r\n";
		} //end if else
		//-- Reply-To Header
		if((string)$replytoaddr != '') {
			$mail->headers .= 'Reply-To: '.$mail->safe_header_str((string)$replytoaddr)."\r\n";
		} //end if
		//--

		//--
		$mail->priority = (int) Smart::format_number_int($priority, '+'); // high=1 | low=5 | normal=3
		//--

		//-- from
		$mail->from_return 	= (string) $send_from_addr;
		$mail->from 		= (string) $send_from_addr;
		$mail->namefrom 	= (string) $send_from_name;
		//--

		//-- subject
		$mail->subject = (string) $subj;
		//--

		//-- if message is html, include CID imgs as attachments (except if mode is 'return' which needs original data !!)
		if(((string)$y_mode != 'return') AND ($is_html === true)) {
			//-- init
			$arr_links = array();
			//-- embed all images
			$htmlparser = new SmartHtmlParser((string)$message);
			$htmlparser->get_clean_html(); // clean html before ; don't care of html comments
			$arr_links = (array) $htmlparser->get_tags('img'); // {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}}
			$htmlparser = null;
			//--
			$chk_duplicates_arr = [];
			$uniq_id = 0;
			//--
			for($i=0; $i<Smart::array_size($arr_links); $i++) {
				//--
				$tmp_original_img_link = (string) trim((string)$arr_links[$i]['src']); // trim any possible spaces
				//-- reverse the &amp; back to & (generated from JavaScript) ...
				$tmp_imglink = (string) str_replace('&amp;', '&', (string)$tmp_original_img_link);
				//--
				$tmp_cid = 'img_'.SmartHashCrypto::sha256('Smart.Framework eMail-Utils // CID Embed // '.'@'.$tmp_imglink.'#'); // this should not vary by $i or others because if duplicate images are detected only the first is attached
				//--
				if((!isset($chk_duplicates_arr[(string)$tmp_cid])) OR (!$chk_duplicates_arr[(string)$tmp_cid])) { // avoid browse twice the same image
					//--
					$tmp_fcontent = '';
					$tmp_fake_fname = '';
					$tmp_img_ext = ''; // extension
					//--
					$tmp_getimg_arr = (array) SmartRobot::load_url_img_content((string)$tmp_imglink, 'auto'); // {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}}
					if($tmp_getimg_arr['result'] == 1) {
						$tmp_fcontent = (string) $tmp_getimg_arr['content'];
						$tmp_fake_fname = (string) $tmp_getimg_arr['filename'];
						$tmp_img_ext = (string) $tmp_getimg_arr['extension'];
					} //end if
					$tmp_getimg_arr = null;
					//-- {{{SYNC-MAIL-CID-IMGS}}} @ Send
					if(((string)$tmp_fcontent != '') AND ((string)$tmp_fake_fname != '') AND (((string)$tmp_img_ext == '.svg') OR ((string)$tmp_img_ext == '.png') OR ((string)$tmp_img_ext == '.gif') OR ((string)$tmp_img_ext == '.jpg') OR ((string)$tmp_img_ext == '.webp'))) {
						//--
						$tmp_arr_fmime = array();
						$tmp_arr_fmime = (array) SmartFileSysUtils::getArrMimeType((string)$tmp_fake_fname);
						$tmp_fmime = (string) $tmp_arr_fmime[0];
						if(strpos((string)$tmp_fmime, 'image/') !== 0) {
							$tmp_fmime = 'image'; // in the case of CIDS we already pre-validated the images
						} //end if
						$tmp_fname = (string) 'cid_'.$uniq_id.'__'.$tmp_cid.$tmp_img_ext;
						$mail->add_attachment($tmp_fcontent, $tmp_fname, $tmp_fmime, 'inline', $tmp_cid.$tmp_img_ext); // attachment
						$message = str_replace('src="'.$tmp_original_img_link.'"', 'src="cid:'.$tmp_cid.$tmp_img_ext.'"', $message);
						//--
						$uniq_id += 1;
						//--
					} //end if
					//--
					$chk_duplicates_arr[(string)$tmp_cid] = true;
					//--
					$guess_arr = array();
					$tmp_browse_arr = array();
					//--
				} //end if
				//--
			} //end for
			//-- clean
			$chk_duplicates_arr = array();
			$uniq_id = 0;
			$tmp_original_img_link = '';
			$tmp_imglink = '';
			$tmp_cid = '';
			$tmp_browse_arr = array();
			$tmp_fcontent = '';
			$tmp_arr_fmime = array();
			$tmp_fmime = '';
			$tmp_fname = '';
			//--
		} //end if
		//--

		//-- message body
		$mail->is_html = ($is_html === true) ? true : false; // false | true
		$mail->body = (string) $message;
		//--
		$message = null;
		//--

		//-- attachments
		if(Smart::array_size($attachments) > 0) {
			foreach($attachments as $key => $val) {
				//--
				$tmp_arr_fmime = (array) SmartFileSysUtils::getArrMimeType((string)$key);
				//--
				$mail->add_attachment((string)$val, (string)$key, (string)$tmp_arr_fmime[0], 'attachment', '', 'yes'); // embed as attachment
				//--
				$tmp_arr_fmime = array();
				//--
			} //end while
		} //end if
		//--

		//--
		switch((string)$y_mode) {
			case 'return':
				//--
				$mail->to = '[::!::]';
				$mail->cc = '';
				//-- only return mime formated message
				$mail->send('no');
				return array('result' => 1, 'error' => '', 'log' => '', 'message' => $mail->mime_message);
				//--
				break;
			case 'send-return':
			case 'send':
			default:
				//--
				$out = 0;
				//--
				$arr_to = array();
				if(!is_array($to)) {
					$arr_to[] = (string) $to;
					$tmp_send_to = (string) $to;
				} else {
					$arr_to = (array) $to;
					if(Smart::array_size($arr_to) > 1) {
						$tmp_send_to = '[::@::]'; // multi message
					} else {
						$tmp_send_to = (string) $arr_to[0];
					} //end if else
				} //end if else
				//--
				$tmp_send_log = '';
				$tmp_send_log .= str_repeat('-', 100)."\n";
				$tmp_send_log .= 'Smart.Framework / eMail Send Log :: '.$send_from_addr.' ['.$send_from_name.']'."\n";
				$tmp_send_log .= $server_sslmode.'://'.$server_name.':'.$server_port.' # '.$server_user.' :: '.$server_helo."\n";
				$tmp_send_log .= str_repeat('-', 100)."\n";
				//--
				$counter_sent = 0;
				for($i=0; $i<Smart::array_size($arr_to); $i++) {
					//--
					$arr_to[$i] = trim($arr_to[$i]);
					//--
					if((string)$arr_to[$i] != '') {
						//--
						$mail->to = (string) $arr_to[$i];
						//--
						$mail->cc = $cc; // can be string or array
						//--
						$mail->bcc = (string) $bcc;
						//--
						$tmp_send_log .= '#'.($i+1).'. To: \''.$arr_to[$i].'\' :: '.date('Y-m-d H:i:s O');
						//-- real send
						if(((string)$mail->method == 'mail') OR ((string)$mail->method == 'smtp')) {
							$err = $mail->send('yes');
							if(SmartEnvironment::ifDebug()) {
								SmartEnvironment::setDebugMsg('mail', 'SEND', '[----- Send eMail Log #'.($i+1).': '.date('Y-m-d H:i:s').' -----]');
							} //end if
						} else {
							$err = 'WARNING: SMTP Server or Mail Method IS NOT SET in CONFIG. Send eMail - Operation ABORTED !';
						} //end if else
						//--
						if(SmartEnvironment::ifDebug()) {
							SmartEnvironment::setDebugMsg('mail', 'SEND', '========== SEND TO: '.$arr_to[$i].' =========='."\n".'ERRORS: '.$err."\n".'=========='."\n".$mail->log."\n".'========== # ==========');
						} //end if
						//--
						if((string)$err != '') {
							$tmp_send_log .= ' :: ERROR:'."\n".$arr_to[$i]."\n".$err."\n";
						} else {
							$counter_sent += 1;
							$tmp_send_log .= ' :: OK'."\n";
						} //end if else
						//--
						if($i > 10000) {
							break; // hard limit
						} //end if
						//--
					} //end if
					//--
				} //end for
				//--
				if($counter_sent > 0) {
					$out = 1;
				} //end if
				//--
				$tmp_send_log .= str_repeat('-', 100)."\n\n";
				if(SmartEnvironment::ifDebug()) {
					SmartEnvironment::setDebugMsg('mail', 'SEND', 'Send eMail Operations Log: '.$tmp_send_log);
				} //end if
				//--
				if((string)$y_mode == 'send-return') {
					//--
					$mail->to = $tmp_send_to;
					if(is_array($cc)) {
						$mail->cc = (string) implode(', ', $cc);
					} elseif((string)$cc != '') {
						$mail->cc = (string) $cc;
					} //end if else
					$mail->add_attachment($tmp_send_log, 'smart-framework-email-send.log', 'text/plain', 'inline', '', 'yes');
					$mail->send('no');
					return array('result' => (int)$out, 'error' => (string)$err, 'log' => (string)$tmp_send_log, 'message' => (string)$mail->mime_message);
					//--
				} else {
					//--
					return array('result' => (int)$out, 'error' => (string)$err, 'log' => (string)$tmp_send_log, 'message' => ''); // skip returning the message
					//--
				} //end if else
				//--
		} //end switch
		//--

	} // END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Get the IMAP Real Message UID as stored on server
	 *
	 * @param STRING			$uid			The client-side IMAP UID
	 * @return STRING							The server-side IMAP UID
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function get_imap_message_real_uid(?string $uid) : string {
		//--
		// on IMAP4 when using the IMAP client library the UID will be as 'IMAP4-UIV-@num@-UID-@uid@'
		// this function will parse this and will return the real server-side UID as stored on IMAP server
		//--
		$uid = (string) trim((string)$uid);
		if((string)$uid == '') {
			return '';
		} //end if
		//--
		if(strpos((string)$uid, 'IMAP4-UIV-') !== 0) {
			return '';
		} //end if
		//--
		$uid = (string) trim((string)ltrim((string)$uid, 'IMAP4-UIV-'));
		if((string)$uid == '') {
			return '';
		} //end if
		//--
		if(strpos((string)$uid, '-UID-') === false) {
			return '';
		} //end if
		//--
		$uid = (array) explode('-UID-', (string)$uid);
		$uid = (string) trim((string)(isset($uid[1]) ? $uid[1] : ''));
		if((string)$uid == '') {
			return '';
		} //end if
		//--
		return (string) $uid;
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartMailerMimeParser - provides an easy to use eMail MIME Parser.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartHashCrypto, SmartUtils, SmartFileSysUtils, SmartFileSystem, SmartMailerMimeDecode, SmartMailerNotes
 * @version 	v.20250129
 * @package 	Application:Plugins:Mailer
 *
 */
final class SmartMailerMimeParser {

	// ::

	//--
	// TODO: unify the encode_mime_fileurl/decode_mime_fileurl with SmartFrameworkRuntime::Create_Download_Link() ; see: {{{TODO-DOWNLOADS-HANDLER-REFACTORING}}}
	//--


	private const EMAIL_TOKEN_COOKIE_NAME = 'SfMailParse_Csrf'; // mai decode CSRF cookie
	private const EMAIL_CRYPTO_PREFIX_KEY = 'Smart.Framework//MailMimeLink';

	private const ERR_MSG_TEXT_DECODE = 'ERROR: MIME Parser // Mesage File Decode :: See error log for details ...';

	//==================================================================
	/**
	 * Encode and Encrypts a Mime File URL using the crypto algo defined in SMART_FRAMEWORK_SECURITY_CRYPTO or 'hash/sha3-384' as a fallback
	 * It takes in account if the User is Authenticated or not
	 * This make safe using Mail Message Parts URL links sent by URL for specific and private user access
	 * @param STRING $y_msg_file The relative path to the .eml message file
	 * @param STRING $y_ctrl_key The encryption private key
	 * @return STRING the encoded and encrypted url segment
	 */
	public static function encode_mime_fileurl(?string $y_msg_file, ?string $y_ctrl_key) : string {
		//--
		$y_msg_file = (string) trim((string)$y_msg_file);
		if((string)$y_msg_file == '') {
			Smart::log_warning(__METHOD__.' # Encode Mime File URL: Empty Message File Path has been provided. This means the URL link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_msg_file)) {
			Smart::log_warning(__METHOD__.' # Encode Mime File URL: Invalid Message File Path has been provided. This means the URL link will be unavaliable (empty) to assure security protection. Message File: '.$y_msg_file);
			return '';
		} //end if
		//--
		$y_ctrl_key = (string) trim((string)$y_ctrl_key);
		if((string)$y_ctrl_key == '') {
			Smart::log_warning(__METHOD__.' # Encode Mime File URL: Empty Controller Key has been provided. This means the URL link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		if(!defined('SMART_ERROR_AREA')) {
			Smart::log_warning(__METHOD__.' # Encode Mime File URL: Missing SMART_ERROR_AREA. This means the URL link will be unavaliable (empty) to assure security protection.');
			return '';
		} //end if
		$y_ctrl_key = (string) SMART_ERROR_AREA.'/'.$y_ctrl_key; // {{{SYNC-ENCMIMEURL-CTRL-PREFIX}}}
		//--
		if((string)trim((string)SMART_APP_VISITOR_COOKIE) == '') { // {{{SYNC-SMART-UNIQUE-COOKIE}}}
			Smart::log_warning(__METHOD__.' # Empty Visitor ID (cookie). Parts decoding feature are not available without it ...');
			return '';
		} //end if
		$access_token = (string) SmartHashCrypto::checksum('eMime#'.SmartUtils::get_visitor_tracking_uid()); // visitor tracking UID is using: SMART_APP_VISITOR_COOKIE, SMART_SOFTWARE_NAMESPACE, SMART_FRAMEWORK_SECURITY_KEY and SmartUtils::client_ident_private_key()
		SmartUtils::set_cookie(
			(string) self::EMAIL_TOKEN_COOKIE_NAME,
			(string) $access_token,
			0, // session expire
			'/', // path
			'@', // domain
			'None' // {{{SYNC-COOKIE-POLICY-NONE}}} ; same site policy: None # Safety: OK, the token cookie is bind to visitor ID, incl. IP address, thus is not important if revealed to 3rd party ... # this is a fix (required for iFrame srcdoc, will not send cookies if Lax or Strict on Firefox impl.)
		);
		//--
		$crrtime = (int) time();
		//-- {{{SYNC-MAIL-UTILS-ENC/DEC-KEYS}}}
		$access_key     = (string) SmartHashCrypto::checksum('MimeLink:'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_token.':'.$y_msg_file.'>'.$y_ctrl_key);
		$unique_key     = (string) SmartHashCrypto::checksum('Time='.$crrtime.'#'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_key.'-'.SmartUtils::unique_auth_client_private_key().':'.$y_msg_file.'>'.$y_ctrl_key);
		$self_robot_key = (string) SmartHashCrypto::checksum('Time='.$crrtime.'#'.SmartAuth::get_auth_id().'*'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.SmartUtils::get_selfrobot_useragent_name().'$'.$access_key.':'.$y_msg_file.'>'.$y_ctrl_key);
		//-- {{{SYNC-MIME-ENCRYPT-ARR}}}
		$safe_link = (string) SmartCipherCrypto::encrypt(
			(string) trim((string)$crrtime)."\n". 											// current time stamp
			(string) trim((string)$y_msg_file)."\n". 										// file
			(string) trim((string)$access_key)."\n". 										// access key based on UniqueID cookie
			(string) trim((string)$unique_key)."\n". 										// unique key based on: AuthUserID, User-Agent and IP
			(string) trim((string)$self_robot_key)."\n". 									// self robot browser UserAgentName/ID key
			(string) SmartHashCrypto::sh3a224((string)trim((string)$access_token))."\n", 	// control: hash of current token
			(string) self::EMAIL_CRYPTO_PREFIX_KEY."\t".SMART_FRAMEWORK_SECURITY_KEY
		);
		//-- {{{SYNC-ENCRYPTED-URL-LINK}}}
		return (string) trim((string)$safe_link); // DO NOT ESCAPE URL here ... it must be done in controllers ; if escaped here and passed directly, not via URL will encode also ; and ! ... will not work
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Decode and Decrypts a Mime File URL encoded/encrypted with encode_mime_fileurl()
	 * It takes in account if the User is Authenticated or not
	 * @param STRING $y_enc_msg_file The encoded/encrypted path to the .eml message file
	 * @param STRING $y_ctrl_key The decryption private key
	 * @return ARRAY with the decoded and decrypted url segment containing all required information to validate the email message path
	 */
	public static function decode_mime_fileurl(?string $y_enc_msg_file, ?string $y_ctrl_key) : array {
		//--
		$arr = array(); // {{{SYNC-MIME-ENCRYPT-ARR}}}
		$arr['error'] = ''; // by default, no error
		//--
		$y_enc_msg_file = (string) trim((string)$y_enc_msg_file);
		if((string)$y_enc_msg_file == '') {
			$arr = array();
			$arr['error'] = 'Empty Message File Path has been provided. This means the URL link will be unavaliable (empty) to assure security protection.';
			return (array) $arr;
		} //end if
		//--
		$y_ctrl_key = (string) trim((string)$y_ctrl_key);
		if((string)$y_ctrl_key == '') {
			$arr = array();
			$arr['error'] = 'Empty Controller Key has been provided. This means the URL link will be unavaliable (empty) to assure security protection.';
			return (array) $arr;
		} //end if
		if(!defined('SMART_ERROR_AREA')) {
			$arr = array();
			$arr['error'] = 'Missing SMART_ERROR_AREA. This means the URL link will be unavaliable (empty) to assure security protection.';
			return (array) $arr;
		} //end if
		$y_ctrl_key = (string) SMART_ERROR_AREA.'/'.$y_ctrl_key; // {{{SYNC-ENCMIMEURL-CTRL-PREFIX}}}
		//--
		$the_sep_arr = (array) self::mime_separe_part_link((string)$y_enc_msg_file);
		$y_enc_msg_file = (string) trim((string)$the_sep_arr['msg']);
		$the_msg_part 	= (string) trim((string)$the_sep_arr['part']);
		$the_sep_arr = null;
		//--
		$access_token = (string) trim((string)SmartUtils::get_cookie((string)self::EMAIL_TOKEN_COOKIE_NAME));
		if((string)$access_token == '') {
			$arr = array();
			$arr['error'] = 'WARNING: Access Forbidden ... Empty Access Token ...!';
			return (array) $arr;
		} //end if
		//--
		if((string)$the_msg_part != '') {
			$the_msg_part = (string) strtolower((string)trim((string)SmartUtils::url_obfs_decode((string)$the_msg_part)));
		} //end if
		//--
		$decoded_link = (string) trim((string)SmartCipherCrypto::decrypt(
			(string)$y_enc_msg_file,
			(string) self::EMAIL_CRYPTO_PREFIX_KEY."\t".SMART_FRAMEWORK_SECURITY_KEY
		));
		if((string)$decoded_link == '') {
			$arr = array();
			$arr['error'] = 'WARNING: Access Forbidden ... Empty MimeURL Data ...!';
			return (array) $arr;
		} //end if
		$dec_arr = (array) explode("\n", trim((string)$decoded_link));
		//print_r($dec_arr);
		//--
		$arr['creation-time'] 	= (string) trim((string)($dec_arr[0] ?? ''));
		$arr['message-file'] 	= (string) trim((string)($dec_arr[1] ?? ''));
		$arr['message-part'] 	= (string) trim((string)$the_msg_part);
		$arr['access-key'] 		= (string) trim((string)($dec_arr[2] ?? ''));
		$arr['bw-unique-key'] 	= (string) trim((string)($dec_arr[3] ?? ''));
		$arr['sf-robot-key']	= (string) trim((string)($dec_arr[4] ?? ''));
		$arr['hash-token'] 		= (string) trim((string)($dec_arr[5] ?? ''));
		//-- check if file path is valid
		if((string)trim((string)($arr['message-file'] ?? null)) == '') {
			$arr = array();
			$arr['error'] = 'ERROR: Empty Message Path ...';
			return (array) $arr;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$arr['message-file'])) {
			$arr = array();
			$arr['error'] = 'ERROR: Unsafe Message Path Access ...';
			return (array) $arr;
		} //end if
		//--
		if((string)$arr['hash-token'] != (string)SmartHashCrypto::sh3a224((string)$access_token)) {
			$arr = array();
			$arr['error'] = 'ERROR: Invalid Access Token (cookie) ; This issue can be if the SandBox iFrame is not receiving the cookies from parent window. With default browser settings regarding cookie policy it should be receiving the access token cookie ...';
			return (array) $arr;
		} //end if
		//--
		$browser_os_ip_identification = (array) SmartUtils::get_os_browser_ip(); // get browser and os identification
		//-- re-compose the access key
		$crrtime = (int) $arr['creation-time'];
		//-- {{{SYNC-MAIL-UTILS-ENC/DEC-KEYS}}}
		$access_key     = (string) SmartHashCrypto::checksum('MimeLink:'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_token.':'.$arr['message-file'].'>'.$y_ctrl_key);
		$uniq_key       = (string) SmartHashCrypto::checksum('Time='.$crrtime.'#'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.$access_key.'-'.SmartUtils::unique_auth_client_private_key().':'.$arr['message-file'].'>'.$y_ctrl_key);
		$self_robot_key = (string) SmartHashCrypto::checksum('Time='.$crrtime.'#'.SmartAuth::get_auth_id().'*'.SMART_SOFTWARE_NAMESPACE.'-'.SMART_FRAMEWORK_SECURITY_KEY.'-'.trim((string)$browser_os_ip_identification['signature']).'$'.$access_key.':'.$arr['message-file'].'>'.$y_ctrl_key);
		//-- check access key
		if((string)$arr['error'] == '') {
			if((string)$access_key != (string)$arr['access-key']) {
				$arr = array();
				$arr['error'] = 'ERROR: Access Forbidden ... Invalid ACCESS KEY ...';
			} //end if
		} //end if
		//-- check the client key
		if((string)$arr['error'] == '') {
			//--
			$ok_client_key = false;
			//--
			if(((string)$the_msg_part == '') AND ((string)$arr['bw-unique-key'] == (string)$uniq_key)) { // no message part, allow only client browser
				$ok_client_key = true;
			} elseif(((string)$the_msg_part != '') AND (((string)$arr['bw-unique-key'] == (string)$uniq_key) OR (((string)$browser_os_ip_identification['bw'] == '@s#') AND ((string)$arr['sf-robot-key'] == (string)$self_robot_key)))) {
				$ok_client_key = true;
			} else {
				$ok_client_key = false;
			} //end if else
			//--
			if($ok_client_key != true) {
				$arr = array();
				$arr['error'] = 'ERROR: Access Forbidden ... Invalid CLIENT KEY ...';
			} //end if
			//--
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Get an Email Message (.eml) as HTML
	 * @return STRING the HTML view of the message linked with all sub-parts in a safe way by making use of encode_mime_fileurl() and decode_mime_fileurl()
	 */
	public static function display_message(?string $y_msg_type, ?string $y_enc_msg_file, ?string $y_ctrl_key, ?string $y_link, ?string $y_target='', ?string $y_title='', ?string $y_process_mode='', ?string $y_show_headers='') : string {
		//--
		if((string)$y_process_mode != 'print') {
			$y_process_mode = 'default';
		} //end if
		if((string)$y_show_headers != 'subject') {
			$y_show_headers = 'default';
		} //end if
		if((string)$y_target == '') {
			$y_target = '_blank';
		} //end if
		//--
		return (string) self::read_mime_message((string)$y_msg_type, (string)$y_enc_msg_file, (string)$y_ctrl_key, (string)$y_process_mode, (string)$y_show_headers, (string)$y_title, (string)$y_link, (string)$y_target);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Get an Email Message (.eml) as ARRAY
	 * This can be used to re-compose a Mime Message for Reply or Forward
	 * @return ARRAY with the full message structure as parts and all sub-parts in a safe way by making use of encode_mime_fileurl() and decode_mime_fileurl()
	 */
	public static function get_message_data_structure(?string $y_msg_type, ?string $y_enc_msg_file, ?string $y_ctrl_key, ?string $y_process_mode, ?string $y_link='', ?string $y_target='') : array {
		//--
		if((string)$y_process_mode != 'data-reply') {
			$y_process_mode = 'data-full';
		} //end if
		//--
		return (array) self::read_mime_message((string)$y_msg_type, (string)$y_enc_msg_file, (string)$y_ctrl_key, (string)$y_process_mode, '', '', (string)$y_link, (string)$y_target);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// the link can be empty as '' just for 'reply' process mode when forwards
	// for the rest of cases the link is something like: yourscript?page=your.action&your_url_param_message={{{MESSAGE}}}&your_url_param_rawmode={{{RAWMODE}}}&your_url_param_mime={{{MIME}}}&your_url_param_disp={{{DISP}}}&&your_url_param_mode={{{MODE}}}
	// [PRIVATE]
	private static function read_mime_message(?string $y_msg_type, ?string $y_enc_msg_file, ?string $y_ctrl_key, ?string $y_process_mode, ?string $y_show_headers, ?string $y_title, ?string $y_link,  ?string $y_target) { // : mixed ( string | array )

		// $y_msg_type     : 'message' | 'apple-note'
		// $y_process_mode : 'default' | 'print' | 'data-full' | 'data-reply'
		// $y_show_headers : 'default' | 'subject' (just for mode: 'default' | 'print')

		//--
		$msg_decode_arr = (array) self::decode_mime_fileurl((string)$y_enc_msg_file, (string)$y_ctrl_key);
		//--
		if((string)$msg_decode_arr['error'] != '') {
			return (string) SmartComponents::operation_error('MIME Parser // ERROR: '.$msg_decode_arr['error']);
		} //end if
		//--

		//--
		$the_message_eml = (string) trim((string)$msg_decode_arr['message-file']);
		$the_part_id = (string) trim((string)$msg_decode_arr['message-part']);
		//--
		//$the_message_eml = 'tmp/test-emails/test_uxm_multi_mimes.eml'; // EMAIL TEST FILE ... (uncomment for tests) ; {{{SYNC-TEST-EMAIL-FILE}}}
		//--

		//--
		if(((string)$the_message_eml == '') OR (!SmartFileSystem::is_type_file((string)$the_message_eml))) {
			Smart::raise_error(
				(string) __METHOD__.' # ERROR: Message File EMPTY or NOT FOUND: `'.$the_message_eml.'`',
				(string) self::ERR_MSG_TEXT_DECODE
			);
			return '';
		} //end if
		//--
		if((string)substr((string)$the_message_eml, -4, 4) != '.eml') {
			Smart::raise_error(
				(string) __METHOD__.' # ERROR: Message File Extension is not .eml: `'.$the_message_eml.'`',
				(string) self::ERR_MSG_TEXT_DECODE
			);
			return '';
		} //end if
		//--

		//--
		$out = ''; // init
		//--
		$reply_text 				= array(); // init
		$reply_text['atts_num'] 	= '';
		$reply_text['atts_lst'] 	= '';
		$reply_text['filepath'] 	= '';
		$reply_text['reply-to'] 	= '';
		$reply_text['from'] 		= '';
		$reply_text['from-name'] 	= '';
		$reply_text['to'] 			= '';
		$reply_text['cc'] 			= '';
		$reply_text['date'] 		= '';
		$reply_text['subject'] 		= '';
		$reply_text['in-reply-to'] 	= '';
		$reply_text['message-id'] 	= '';
		$reply_text['message-type'] = '';
		$reply_text['message'] 		= '';
		//--

		//==
		//--
		$content = (string) SmartFileSystem::read((string)$the_message_eml);
		$eml = new SmartMailerMimeDecode();
		$head = $eml->get_header(SmartUnicode::sub_str((string)$content, 0, 65535)); // some messages fail with 8192 to decode ; a faster compromise would be 16384, but here we can use a higher value since is done once (text 65535)
		$msg = $eml->get_bodies((string)$content, (string)$the_part_id);
		$eml = null;
		$content = null;
		//--
		//==

		//--
		$reg_atts_num = 0;
		$reg_atts_list = ''; // list separed by \n
		//--
		if((string)$the_part_id == '') {
			//-- display whole message
			$reg_is_part = 'no';
			$skip_part_processing = 'no';
			$skip_part_linking = 'no';
			//--
		} else {
			//-- display only a part of the message
			$reg_is_part = 'yes';
			$skip_part_processing = 'no';
			$skip_part_linking = 'yes';
			//--
			if((string)substr($the_part_id, 0, 4) == 'txt_') {
				//-- text part
				$tmp_part = (array) $msg['texts'][(string)$the_part_id];
				$msg = array();
				$msg['texts'][(string)$the_part_id] = (array) $tmp_part;
				$tmp_part = null;
				//--
			} else {
				//-- att / cid part
				$skip_part_processing = 'yes';
				//--
				if(!is_array($msg['attachments'][$the_part_id])) { // try to normalize name
					$the_part_id = (string) trim((string)str_replace(' ', '', (string)$the_part_id));
				} //end if
				//--
				$out = (string) $msg['attachments'][$the_part_id]['content']; // DO NO MORE ADD ANYTHING TO $out ... downloading, there are no risk of code injection
				//--
			} //end if else
			//--
		} //end if else
		//--

		//--
		if((string)$y_process_mode == 'print') {
			$skip_part_linking = 'yes'; // skip links to other sub-parts like texts / attachments but not cids !
		} elseif((string)$y_process_mode == 'data-reply') {
			$skip_part_linking = 'yes';
		} //end if
		//--

		//--
		if((string)$skip_part_processing != 'yes') {
			//--
			if((string)$y_title != '') {
				$out .= (string) $y_title; // expects '' or valid HTML
			} //end if
			//--
			$out .= '<!-- Smart.Framework // MIME MESSAGE HTML --><div align="left"><div id="mime_msg_box">';
			//--
			if((string)$the_part_id == '') {
				//--
				$tmp_ittl = '';
				$priority_img = '';
				if((string)$y_msg_type == 'apple-note') {
					$tmp_ittl = (string) ucwords((string)str_replace('-', ' ', $y_msg_type)).' / UUID: '.$head['message-uid'];
					$priority_img = '<img src="lib/core/plugins/img/email/note.svg" align="left" alt="'.Smart::escape_html((string)$tmp_ittl).'" title="'.Smart::escape_html((string)$tmp_ittl).'">';
				} else {
					$tmp_ittl = ' / Message-ID: '.$head['message-id'];
					switch((string)$head['priority']) {
						case '1': // high
							$priority_img = '<img src="lib/core/plugins/img/email/priority-high.svg" align="left" alt="High Priority'.Smart::escape_html((string)$tmp_ittl).'" title="High Priority'.Smart::escape_html((string)$tmp_ittl).'">';
							break;
						case '5': // low
							$priority_img = '<img src="lib/core/plugins/img/email/priority-low.svg" align="left" alt="Low Priority'.Smart::escape_html((string)$tmp_ittl).'" title="Low Priority'.Smart::escape_html((string)$tmp_ittl).'">';
							break;
						case '3': // medium
						default:
							//$priority_img = '';
							$priority_img = '<img src="lib/core/plugins/img/email/priority-normal.svg" align="left" alt="Normal Priority'.Smart::escape_html((string)$tmp_ittl).'" title="Normal Priority'.Smart::escape_html((string)$tmp_ittl).'">';
					} //end switch
				} //end if
				$tmp_ittl = '';
				//--
				if((string)$skip_part_linking != 'yes') { // avoid display the print link when only a part is displayed ; print view is HTML so need no mimetype
					$out .= '<a href="'.self::mime_link((string)$y_ctrl_key, (string)$the_message_eml, (string)$the_part_id, (string)$y_link, '', '', 'print').'" target="'.Smart::escape_html((string)$y_target).'__mimepart" data-smart="open.popup">'.'<img align="right" src="lib/core/plugins/img/email/print-view.svg" title="Print View" alt="Print View">'.'</a>';
				} //end if
				//--
				switch((string)$y_show_headers) {
					case 'subject':
						//--
						if((string)$head['subject'] != '[?]') {
							$out .= '<h1 style="display:inline-block!important; line-height:16px;"><span style="font-size:1.25rem;">'.Smart::escape_html((string)$head['subject']).'</span></h1><br>';
						} //end if
						//--
						break;
					case 'default':
					default:
						//--
						if((string)$head['subject'] != '[?]') {
							$out .= '<h1 style="display:inline-block!important; line-height:16px;">'.$priority_img.'<span style="font-size:1.25rem;">&nbsp;&nbsp;'.Smart::escape_html((string)$head['subject']).'</span></h1><hr>';
						} //end if
						//--
						if((string)$head['date'] != '(?)') {
							$out .= '<span style="font-size:1.125rem;"><b>Date:</b> '.Smart::escape_html(date('Y-m-d H:i:s O', @strtotime($head['date']))).'</span><br>';
						} //end if
						//--
						if((string)$y_msg_type == 'apple-note') {
							//--
							$out .= '<span style="font-size:1rem;"><b>Notes.Author:</b> '.Smart::escape_html((string)$head['from_addr']).((((string)$head['from_addr'] != (string)$head['from_name']) && ((string)trim((string)$head['from_name']) != '')) ? ' &nbsp; <i>'.Smart::escape_html((string)$head['from_name']).'</i>' : '').'</span><br>';
							//--
						} else {
							//--
							$out .= '<span style="font-size:1rem;"><b>From:</b> '.Smart::escape_html((string)$head['from_addr']).' &nbsp; <i>'.Smart::escape_html((string)$head['from_name']).'</i>'.'</span><br>';
							$out .= '<span style="font-size:1rem;"><b>To:</b> '.Smart::escape_html((string)$head['to_addr']).' &nbsp; <i>'.Smart::escape_html((string)$head['to_name']).'</i>'.'</span><br>';
							//--
							if((string)$head['cc_addr'] != '') {
								$out .= '<span style="font-size:1rem;"><b>Cc:</b> ';
								if(SmartUnicode::str_contains($head['cc_addr'], ',')) {
									$arr_cc_addr = (array) explode(',', (string)$head['cc_addr']);
									$arr_cc_name = (array) explode(',', (string)$head['cc_name']);
									$out .= '[@]';
									for($z=0; $z<Smart::array_size($arr_cc_addr); $z++) {
										$out .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.Smart::escape_html((string)trim((string)$arr_cc_addr[$z])).' &nbsp; <i>'.Smart::escape_html((string)trim((string)(isset($arr_cc_name[$z]) ? $arr_cc_name[$z] : ''))).'</i>';
									} //end for
								} else {
									$out .= Smart::escape_html((string)$head['cc_addr']).' &nbsp; <i>'.Smart::escape_html((string)$head['cc_name']).'</i>';
								} //end if else
								$out .= '</span><br>';
							} //end if
							//--
							if((string)$head['bcc_addr'] != '') {
								$out .= '<span style="font-size:1rem;"><b>Bcc:</b> ';
								$out .= Smart::escape_html((string)$head['bcc_addr']).' &nbsp; <i>'.Smart::escape_html((string)$head['bcc_name']).'</i>';
								$out .= '</span><br>';
							} //end if
							//--
						} //end if else
						//--
				} //end switch
				//-- print attachments
				if(is_array($msg['attachments'])) {
					//--
					$cnt=0;
					//--
					$atts = ''; // atts with link
					$xatts = ''; // atts without link
					//--
					$tmp_att_img = '<img src="lib/core/plugins/img/email/attachment.svg" alt="Attachment" title="Attachment">';
					//--
					foreach($msg['attachments'] as $key => $val) {
						//--
						$key = (string) $key;
						//--
						$tmp_arr = array();
						$tmp_arr = (array) $val;
						//--
						if((string)$tmp_arr['mode'] == 'normal') {
							//--
							$cnt += 1;
							//--
							$eval_arr = (array) SmartFileSysUtils::getArrMimeType((string)$tmp_arr['filename']);
							//--
							$tmp_att_name = (string) Smart::escape_html((string)$tmp_arr['filename']);
							$tmp_att_size = (string) Smart::escape_html((string)SmartUtils::pretty_print_bytes((int)$tmp_arr['filesize'], 1));
							//--
							$reg_atts_num += 1;
							$reg_atts_list .= str_replace(array("\r", "\n", "\t"), array('', '', ''), (string)$tmp_arr['filename'])."\n";
							//--
							$atts .= '<div align="left"><table border="0" cellpadding="2" cellspacing="0" title="Attachment #'.$cnt.'"><tr><td>'.$tmp_att_img.'</td><td>&nbsp;</td><td><a href="'.self::mime_link((string)$y_ctrl_key, (string)$the_message_eml, (string)$key, (string)$y_link, (string)$eval_arr[0], (string)$eval_arr[1]).'" target="'.$y_target.'__mimepart" data-smart="open.popup"><span style="font-size:0.875rem;"><b>'.$tmp_att_name.'</b></span></a></td><td><span style="font-size:0.875rem;"> &nbsp;<b><i>'.$tmp_att_size.'</i></b></span></td></tr></table></div>';
							$xatts .= '<div align="left">'.$tmp_att_img.'&nbsp;&nbsp;<span style="font-size:0.875rem;">'.$tmp_att_name.'&nbsp;&nbsp;<i>'.$tmp_att_size.'</i></span></div>';
							//--
							$eval_arr = array();
							//--
						} //end if
						//--
					} //end foreach
					//--
					if($cnt > 0) {
						if((string)$skip_part_linking == 'yes') { // avoid displaying attachments links when only a part is displayed
							$out .= '<hr><div align="left">'.$xatts.'</div>';
						} else {
							$out .= '<hr><div align="left">'.$atts.'</div>';
						} //end if
					} //end if
					//--
					$tmp_att_name = '';
					$tmp_att_size = '';
					//--
					$atts = '';
					$xatts = '';
					//--
				} //end if
				//--
			} else {
				//--
				$out .= '<div style="text-align:right; color:#999999; font-size:0.625rem;">'.Smart::escape_html((string)$head['subject']).' // '.'MIME Part ID : <i>'.Smart::escape_html((string)$the_part_id).'</i></div>';
				//--
			} //end if
			//-- print text bodies
			$markup_multipart = 'This is a multi-part message in MIME format.';
			if(is_array($msg['texts'])) {
				//-- check similarity and prepare the HTML parts
				$buff = '';
				$buff_id = '';
				$xbuff = '';
				$xbuff_id = '';
				$skips = array();
				$numparts = 0;
				//--
				$primary_body_part_detected = false;
				//--
				foreach($msg['texts'] as $key => $val) {
					//--
					$key = (string) $key;
					$val = (array) $val;
					//--
					$numparts += 1;
					//--
					if((string)$val['type'] == 'text') { // assure we don't print other things
						//--
						if((string)$val['mode'] == 'text/x-watch-html') { // Apple watch Text: skip
							//--
							$val['skip'] = true;
							$msg['texts'][$key]['skip'] = true; // write back
							//--
						} elseif((string)$val['mode'] == 'text/html') { // HTML Parts :: check similarity
							//--
							$val['content'] = '<!-- MIMEREAD:PART:HTML -->'.preg_replace("'".'<\?xml'.".*?".'>'."'si", " ", (string)$val['content']); // remove always fake "< ?" as "< ?xml" (fixed with /u modifier for unicode strings)
							//--
							if((SmartUnicode::str_contains($val['content'], '<'.'?')) OR (SmartUnicode::str_contains($val['content'], '?'.'>')) OR (SmartUnicode::str_contains($val['content'], '<'.'%')) OR (SmartUnicode::str_contains($val['content'], '%'.'>'))) {
								//--
								$val['content'] = (string) highlight_string((string)$val['content'], true); // highlight the PHP* code & sanitize the parts
								//--
							} else {
								//-- sanitize this html part
								if((string)$y_msg_type == 'apple-note') {
									$val['content'] = (string) SmartMailerNotes::mime_fix_apple_notes_objects_in_html($val['content']); // must be done before cleanup
								} //end if
								$val['content'] = (string) self::mime_fix_clean_html((string)$val['content']);
								//-- {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}} :: fix back unsafe images replaced by mime_fix_clean_html() if default or print mode (not for 'data-reply' or 'data-full' or other modes)
								if(((string)$y_process_mode == 'default') OR ((string)$y_process_mode == 'print')) {
									$val['content'] = (string) str_replace('data-title="WebMail :: Disabled UNSAFE Image" src="#smart-framework-webmail-unsafe-image"', 'title="Smart.Framework.WebMail :: Disabled UNSAFE Image @ '.date('Y-m-d H:i:s O').'" src="lib/core/plugins/img/email/unsafe-image.svg"', (string)$val['content']);
								} //end if
								//-- replace cid images
								$val['content'] = (string) self::mime_fix_cids((string)$the_message_eml, (string)$val['content'], (string)$y_ctrl_key, (string)$y_link);
								//--
							} //end if else
							//--
							$msg['texts'][$key]['content'] = (string) $val['content']; // rewrite back
							//--
							$xbuff = (string) SmartUnicode::sub_str((string)Smart::stripTags((string)$val['content']), 0, 16384);
							$xbuff_id = (string) $key;
							//--
							$percent_similar = 0;
							if((string)$the_part_id == '') {
							//	@similar_text($buff, $xbuff, $percent_similar);
								$percent_similar = 99; // {{{SYNC-FIX-EML-HIDE-ALTERNATE-PARTS}}}
								if($percent_similar >= 15) { // 15% at least similarity
									$skips[$buff_id] = $percent_similar; // skip this alternate text part ...
								} //end if
							} //end if
							//-- clean buffer
							$buff = '';
							$buff_id = '';
							//--
						} else { // SECURITY NOTICE: for all the rest of cases fall back to text to avoid injections of non-html code as html
					//	} elseif(((string)$val['mode'] == 'text/plain') OR ((string)$val['mode'] == 'application/pgp-encrypted')) { // Plain TEXT ; {{{SYNC-EMAIL-DECODE-SMIME}}}
							//-- sanitize text
							$val['content'] = '<!-- MIMEREAD:PART:TEXT -->'.Smart::escape_html((string)$val['content']);
							$val['content'] = (string) str_replace(["\r\n", "\r", "\n"], ["\n", "\n", '<br>'], (string)$val['content']);
							$val['content'] = (string) SmartParser::text_urls((string)$val['content']);
							//--
							if((string)$val['mode'] == 'application/pgp-encrypted') { // {{{SYNC-EMAIL-DECODE-SMIME}}}
								$val['content'] = '<img src="lib/core/plugins/img/email/mime-encrypted.svg" align="right" alt="S.MIME" title="S.MIME">'.$val['content'];
							} //end if
							//--
							$msg['texts'][$key]['content'] = (string) $val['content']; // rewrite back
							//-- assign buffer
							$buff = (string) SmartUnicode::sub_str($val['content'], 0, 16384);
							$buff_id = (string) $key;
							//--
							$percent_similar = 0;
							if(((string)$the_part_id == '') AND ($primary_body_part_detected === true)) {
							//	@similar_text($buff, $markup_multipart, $percent_similar);
								$percent_similar = 99; // {{{SYNC-FIX-EML-HIDE-ALTERNATE-PARTS}}}
								if($percent_similar >= 25) { // 25% at least similarity
									$skips[(string)$buff_id] = (int) ceil((float)$percent_similar); // skip this alternate html part ...
								} //end if
							} //end if
							//--
							// clean buffer
							$xbuff = '';
							$xbuff_id = '';
							//--
						} //end if
						//--
						$primary_body_part_detected = true;
						//--
					} //end if
					//--
				} //end foreach
				//--
				if($numparts <= 1) {
					$skips = array(); // disallow skips if only one part
				} //end if
				//-- print bodies except the skipped by similarity
				$out .= '<hr>';
				//--
				$cnt=0;
				foreach($msg['texts'] as $key => $val) {
					//--
					$key = (string) $key;
					$val = (array) $val;
					//--
					$val['type'] = $val['type'] ?? null;
					$val['skip'] = $val['skip'] ?? null;
					if(((string)$val['type'] == 'text') AND ($val['skip'] !== true)) { // assure we don't print other things
						//--
						$cnt += 1;
						//--
						$eval_arr = (array) SmartFileSysUtils::getArrMimeType('part_'.$cnt.'.html', 'inline');
						//--
						$tmp_link_pre = '<span title="Mime Part #'.$cnt.' ( '.Smart::escape_html(strtolower($val['mode']).' : '.strtoupper($val['charset'])).' )"><a href="'.self::mime_link((string)$y_ctrl_key, (string)$the_message_eml, (string)$key, (string)$y_link, (string)$eval_arr[0], (string)$eval_arr[1], 'partial').'" target="'.$y_target.'__mimepart" data-smart="open.popup">';
						//--
						$eval_arr = array();
						//--
						$tmp_link_pst = '</a></span>';
						//--
						if((!isset($skips[$key])) OR ((string)$skips[$key] == '')) { // print part if not skipped by similarity ...
							//--
							if((string)$skip_part_linking == 'yes') { // avoid display sub-text part links when only a part is displayed
								$tmp_pict_img = '';
							} else {
								$tmp_pict_img = '<div align="right">'.$tmp_link_pre.'<img src="lib/core/plugins/img/email/mime-part.svg" alt="Mime Part" title="Mime Part">'.$tmp_link_pst.'</div>';
							} //end if
							//--
							if((string)$y_process_mode == 'data-reply') {
								if((string)$reply_text['message'] == '') {
									$reply_text['message-type'] = (string) $val['mode'];
									$reply_text['message'] = (string) $val['content'];
								} //end if
							} else {
								$htmid = (string) trim((string)preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key));
								$out .= $tmp_pict_img;
								if(((string)$val['mode'] == 'text/plain') OR ((string)$y_process_mode == 'print')) {
									$out .= $val['content'];
								} else { // for non text/plain parts, implements a sandboxed iframe for the non-print mode ; for print mode the iframe sandbox must be manually set as sandbox="allow-same-origin" to ensure safety !
									$out .= '<div title="Mime Message HTML Safe SandBox / iFrame" style="position:relative;"><img height="16" src="lib/core/plugins/img/email/safe.svg" style="cursor:help; position:absolute; top:5px; left:49vw; opacity:0.25;"><iframe name="'.Smart::escape_html((string)$htmid).'" id="'.Smart::escape_html((string)$htmid).'" width="100%" scrolling="auto" marginwidth="5" marginheight="5" hspace="0" vspace="0" frameborder="0" style="min-height:25vh; height:max-content; border:1px solid #ECECEC;" sandbox="allow-same-origin" srcdoc="'.Smart::escape_html('<!DOCTYPE html><html><head><title>Mime Message</title><meta charset="'.Smart::escape_html((string)SMART_FRAMEWORK_CHARSET).'">'.'<style>'."\n".SmartComponents::app_default_css()."\n".'</style>'.'</head><body>'.$val['content'].'<script>alert(\'If you can see this alert the Mime Message iFrame Sandbox is unsafe ...\');</script></body></html>').'" onload="setTimeout(() => { const $ifrm = jQuery(\'#'.Smart::escape_html((string)$htmid).'\'); const ifrmH = $ifrm.contents().height(); const iHeight = ifrmH + 15 + \'px\'; $ifrm.height(iHeight); }, 100);"></iframe></div>';
								} //end if else
								$out .= '<br><hr><br>';
							} //end if
							//--
						} else {
							//--
							if((string)$skip_part_linking != 'yes') { // for replies, avoid display sub-text part links when only a part is displayed
								if((string)$y_process_mode == 'data-reply') {
									// display nothing
								} else {
									if((string)$val['@smart-log'] != '') {
										$out .= '<div align="right">'.'<span title="'.Smart::escape_html((string)$val['@smart-log']).'">&nbsp;</span>'.$tmp_link_pre.'<img src="lib/core/plugins/img/email/mime-log-part.svg" alt="Message Send Log" title="Message Send Log">'.$tmp_link_pst.'</div>';
									} else {
										$out .= '<div align="right">'.'<span title="'.'~'.Smart::escape_html(Smart::format_number_dec($skips[$key], 0, '.', ',').'%').'">&nbsp;</span>'.$tmp_link_pre.'<img src="lib/core/plugins/img/email/mime-alt-part.svg" alt="Alternative Mime Part" title="Alternative Mime Part">'.$tmp_link_pst.'</div>';
									} //end if else
								} //end if else
							} //end if
							//--
						} //end if else
						//--
					} //end if
					//--
				} //end foreach
				//--
			} //end if
			//--
			$out .= '</div></div><!-- END MIME MESSAGE HTML -->';
			//--
		} //end if else
		//--

		//--
		if((string)$y_process_mode == 'data-full') { // output an array with message and all header info as data structure
			//--
			return array(
				'reply-to' 		=> (string) $head['reply-to'],
				'from' 			=> (string) $head['from_addr'],
				'from-name' 	=> (string) $head['from_name'],
				'to' 			=> (string) $head['to_addr'],
				'cc' 			=> (string) $head['cc_addr'],
				'date' 			=> (string) $head['date'],
				'atts_num' 		=> (int)    $reg_atts_num,
				'atts_lst' 		=> (string) $reg_atts_list,
				'filepath' 		=> (string) $the_message_eml,
				'subject' 		=> (string) $head['subject'],
				'is_part' 		=> (string) $reg_is_part, // yes/no
				'in-reply-to' 	=> (string) $head['in-reply-to'],
				'message-id' 	=> (string) $head['message-id'],
				'message' 		=> (string) $out
			);
			//--
		} elseif((string)$y_process_mode == 'data-reply') { // output a special array for replies only
			//--
			$reply_text['reply-to'] 	= (string) $head['reply-to'];
			$reply_text['from'] 		= (string) $head['from_addr'];
			$reply_text['from-name'] 	= (string) $head['from_name'];
			$reply_text['to'] 			= (string) $head['to_addr'];
			$reply_text['cc'] 			= (string) $head['cc_addr'];
			$reply_text['date'] 		= (string) $head['date'];
			$reply_text['atts_num'] 	= (int)    $reg_atts_num;
			$reply_text['atts_lst'] 	= (string) $reg_atts_list;
			$reply_text['filepath'] 	= (string) $the_message_eml;
			$reply_text['subject'] 		= (string) $head['subject'];
			$reply_text['in-reply-to'] 	= (string) $head['in-reply-to'];
			$reply_text['message-id'] 	= (string) $head['message-id'];
			$reply_text['message-type'] = (string) $reply_text['message-type'];
			$reply_text['message'] 		= (string) $reply_text['message']; // this comes from above
			//--
			return (array) $reply_text;
			//--
		} else { // 'default' or 'print' :: message as html view
			//--
			return (string) $out;
			//--
		} //end if
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	// [PRIVATE]
	private static function mime_fix_clean_html(?string $y_mime_part) : string {
		//--
		// 1. clean HTML and strip comments
		// 2. extract all image tags to be checked and deactivate unsafe img links, since robot re-composes a message and only embed img tags {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}}
		// 3. images with unsafe img src links that point to index.php / admin.php will be deactivated to prevent robot to be fooled by inserting back links that point to unwanted areas when the robot re-compose back a mime message on reply by example and to avoid embedd unwanted things
		//--
		$htmlparser = new SmartHtmlParser((string)$y_mime_part);
		$y_mime_part = (string) $htmlparser->get_clean_html(false); // clean, without html comments
		$arr_links = (array) $htmlparser->get_tags('img'); // {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}}
		$htmlparser = null;
		//--
		for($i=0; $i<Smart::array_size($arr_links); $i++) {
			//--
			$tmp_link = (string) trim((string)$arr_links[$i]['src']); // trim any possible spaces
			//--
			if((stripos((string)$tmp_link, 'data:') === 0) OR (stripos((string)$tmp_link, 'cid:') === 0)) {
				//--
				// data: images are embedded, they are safe
				// cid: images are replaced after this step, they are safe
				//--
			} else {
				//--
				// for any other images test if they match a trusted robot reference
				// if they are trusted by robot, they are UNSAFE because they can fool the robot to replace them back as a CID attachment when an email message is re-composed (ex: reply to a message, can embedd an unwanted image or other unwanted things ...)
				// to avoid any hack that can fool the robot, simply disable below any image link that can be trusted by robot !
				//--
				$arr_robot_test = (array) SmartRobot::get_url_or_path_trust_reference((string)$tmp_link); // {{{SYNC-CHECK-ROBOT-TRUST-IMG-LINKS}}}
				//--
				if(((string)$arr_robot_test['allow-credentials'] == 'yes') OR ((string)$arr_robot_test['trust-headers'] == 'yes')) {
					//-- replace robot trusted img links
					$y_mime_part = (string) str_ireplace('src="'.$tmp_link.'"', 'alt="UNSAFE Image Disabled: `'.$tmp_link.'`" data-title="WebMail :: Disabled UNSAFE Image" src="#smart-framework-webmail-unsafe-image"', (string)$y_mime_part);
					//--
				} //end if
				//--
			} //end if
			//--
		} //end for
		//--
		return (string) $y_mime_part;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// [PRIVATE]
	private static function mime_fix_cids(?string $y_msg_file, ?string $y_mime_part, ?string $y_ctrl_key, ?string $y_link) : string {
		//--
		$matches = array(); // init
		//--
		$pcre = preg_match_all('/<img[^>]+src=[\'"]?(cid\:)([^\'"]*)[\'"]?[^>]*>/si', (string)$y_mime_part, $matches, PREG_SET_ORDER, 0); // fix: previous was just i (not si) ; modified on 20200331
		if($pcre === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
			return (string) $y_mime_part;
		} //end if
		//--
		// $matches[i][0] : the full link
		// $matches[i][1] : 'cid:'
		// $matches[i][2] : cid part id
		//--
		for($i=0; $i<Smart::array_size($matches); $i++) {
			$tmp_replace_cid_link = (string) str_replace(
				["\r\n", "\n", "\r", "\t"],
				' ',
				(string) ($matches[$i][0] ?? null)
			);
			$tmp_replace_cid_link = (string) str_replace(
				(string) ($matches[$i][1] ?? null).($matches[$i][2] ?? null),
				(string) self::mime_link((string)$y_ctrl_key, (string)$y_msg_file, (string)'cid_'.($matches[$i][2] ?? null), (string)$y_link, 'image', 'inline'), // for cids send a generic type as image and later before servibg try to detect the real mime type {{{SYNC-BETTER-CID-IMGS-DETECTION-OF-MIMETYPE}}} ; why need fixing ? SVGs don't function with mime type 'image', they need 'image/svg+xml'
				(string) $tmp_replace_cid_link
			);
			$y_mime_part = (string) str_replace(
				(string) ($matches[$i][0] ?? null),
				(string) $tmp_replace_cid_link,
				(string) $y_mime_part
			);
		} //end for
		//--
		$matches = null; // free mem
		//--
		return (string) $y_mime_part;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// [PRIVATE]
	private static function mime_separe_part_link(?string $y_msg_file) : array {
		//--
		$out = array('msg' => '', 'part' => '');
		//--
		if(strpos((string)$y_msg_file, '@') !== false) {
			$tmp_arr = (array) explode('@', (string)$y_msg_file);
			$out['msg']  = (string) trim((string)(isset($tmp_arr[0]) ? $tmp_arr[0] : ''));
			$out['part'] = (string) trim((string)(isset($tmp_arr[1]) ? $tmp_arr[1] : ''));
		} else {
			$out['msg'] = (string) trim((string)$y_msg_file);
		} //end if else
		//--
		return (array) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// [PRIVATE]
	private static function mime_link(?string $y_ctrl_key, ?string $y_msg_file, ?string $y_part, ?string $y_link, ?string $y_rawmime, ?string $y_rawdisp, ?string $y_display='') : string {
		//--
		$y_msg_file = (string) $y_msg_file;
		$y_part = (string) $y_part;
		$y_link = (string) $y_link;
		$y_rawmime = (string) $y_rawmime;
		$y_rawdisp = (string) $y_rawdisp;
		$y_display = (string) $y_display; // print | partial
		//--
		$the_url_param_msg = '';
		$the_url_param_raw = '';
		$the_url_param_mime = '';
		$the_url_param_disp = '';
		//--
		if(((string)$y_link != '') AND ((string)$y_msg_file != '')) {
			//--
			$the_url_param_msg = (string) self::encode_mime_fileurl((string)$y_msg_file, (string)$y_ctrl_key); // {{{SYNC-MIME-ENCRYPT-ARR}}}
			if((string)$y_part != '') {
				$the_url_param_msg .= '@'.SmartUtils::url_obfs_encode((string)$y_part); // have part
			} //end if
			//--
			if((string)$y_rawmime != '') {
				$the_url_param_raw = 'raw';
				$the_url_param_mime = (string) SmartUtils::url_obfs_encode((string)$y_rawmime);
			} //end if
			if((string)$y_rawdisp != '') {
				$the_url_param_raw = 'raw';
				$the_url_param_disp = (string) SmartUtils::url_obfs_encode((string)$y_rawdisp);
			} //end if
			//--
			$the_url_param_mode = '';
			if((string)$y_display == 'print') { // printable display mode
				$the_url_param_mode = 'print';
			} elseif((string)$y_display == 'partial') { // partial display mode
				$the_url_param_mode = 'partial';
			} //end if else
			//--
			$y_link = (string) str_replace(
				[
					'{{{MESSAGE}}}',
					'{{{RAWMODE}}}',
					'{{{MIME}}}',
					'{{{DISP}}}',
					'{{{MODE}}}',
				],
				[
					(string) Smart::escape_url($the_url_param_msg),
					(string) Smart::escape_url($the_url_param_raw),
					(string) Smart::escape_url($the_url_param_mime),
					(string) Smart::escape_url($the_url_param_disp),
					(string) Smart::escape_url($the_url_param_mode),
				],
				(string) $y_link
			);
			//--
		} //end if
		//--
		return (string) $y_link;
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartMailerOauth2 - provides OAuth2 Mailer password provider
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartHashCrypto, SmartCipherCrypto, SmartHttpUtils, SmartHttpClient, SmartAuth, SmartUtils
 * @version 	v.20250129
 * @package 	Application:Plugins:Mailer
 *
 */
final class SmartMailerOauth2 {

	// ::

	private static $cache = []; // use cache if there are more calls under the same request ...


	//==================================================================
	/**
	 * Get Mailer Token Pass from a HTTP Based OAuth2 Service
	 * @param ARRAY $params
	 * @return STRING the password (OAuth2 Token)
	 */
	public static function getTokenPass(array $params) : string {
		//--
		$req = [
			'url',
			'url:post:arr',
			'auth',
			'user',
			'pass',
		];
		//--
		$params = (array) Smart::array_init_keys((array)$params, (array)$req);
		//--
		for($i=0; $i<Smart::array_size($req); $i++) {
			$key = (string) $req[$i];
			if((string)$key == 'url:post:arr') { // post arr can be empty
				if(!is_array($params[(string)$key])) {
					return '';
				} //end if
			} else {
				if(!isset($params[(string)$key])) {
					return '';
				} //end if
				if(!is_string($params[(string)$key])) {
					return '';
				} //end if
				$params[(string)$key] = (string) trim((string)$params[(string)$key]);
				if((string)$params[(string)$key] == '') {
					return '';
				} //end if
			} //end if
		} //end for
		//--
		if(!is_array(self::$cache)) {
			self::$cache = [];
		} //end if
		$hash = (string) SmartHashCrypto::sh3a512((string)trim((string)Smart::json_encode((array)$params)));
		if(isset(self::$cache[(string)$hash])) {
			return (string) self::$cache[(string)$hash];
		} //end if
		//--
		$bw = new SmartHttpClient();
		$bw->connect_timeout = 10;
		$method = 'GET';
		if((int)Smart::array_size($params['url:post:arr']) > 0) {
			$method = 'POST';
			$bw->postvars = (array) $params['url:post:arr'];
		} //end if
		$authUser = '';
		$authPass = '';
		$response = [];
		switch((string)strtolower((string)$params['auth'])) { // TODO: allow also Token !
			case 'swt': // pass: passhash,b64
				$url = (string) $params['url'];
				if(strpos((string)$url, 'admin.php?') === 0) {
					$url = (string) SmartUtils::get_server_current_url().$url;
				} elseif((strpos((string)$url, 'https://') !== 0) OR (strpos((string)$url, '/admin.php?') === false)) { // security ! allow only https on S.F. !
					return '';
				} //end if
				$params['pass'] = (string) trim((string)Smart::b64_dec((string)$params['pass'], true)); // it is only a pass hash, it is ok to store as B64 only
				if((string)$params['pass'] == '') {
					return '';
				} //end if
				$swt_token = (array) SmartAuth::swt_token_create(
					'A', // bind to adm/tsk area only !
					(string) $params['user'], // auth user name ; this should be the username not the ID ; on admin area the username is used for auth !
					(string) $params['pass'],  // password hash
					(int)    60, // ~30 sec but the clock may be unsync a bit ... let it be 60 sec ; connect time is 10 sec ; token refresh timeout is 15 ; total is 25 + extra 5 sec
					(array)  [], // better use wildcard, this can be cross-servers, make sure it works // [ (string)SmartUtils::get_server_current_ip() ], // server's own IP Address only, in this List ; currently just one
					(array)  [ 'oauth2' ] // only oauth2 privilege
				);
				if($swt_token['error'] !== '') {
					Smart::log_warning(__METHOD__.' # SWT ERR: '.$swt_token['error']);
					return '';
				} //end if
				$authUser = (string) SmartHttpUtils::AUTH_USER_BEARER;
				$authPass = (string) $swt_token['token'];
				break;
			case 'token':
				if(strpos((string)$url, 'https://') !== 0) { // allow just on HTTPS ; tokens cannot be exposed on http, it is unsafe
					return '';
				} //end if
				$authUser = (string) strtoupper((string)$params['user']);
				switch((string)$authUser) {
					case SmartHttpUtils::AUTH_USER_BEARER:
					case SmartHttpUtils::AUTH_USER_APIKEY:
					case SmartHttpUtils::AUTH_USER_TOKEN:
					case SmartHttpUtils::AUTH_USER_RAW:
						break;
					default:
						Smart::log_warning(__METHOD__.' # Token Invalid UserName: '.$authUser);
						return '';
				} //end switch
				$authPass = (string) trim((string)SmartCipherCrypto::tf_decrypt((string)$params['pass'], '', true)); // TF or BF with fallback ; it is tokem can be trimmed
				if((string)$authPass == '') {
					return '';
				} //end if
			case 'basic':
				if(strpos((string)$url, 'https://') !== 0) { // allow just on HTTPS ; tokens cannot be exposed on http, it is unsafe
					return '';
				} //end if
				$authUser = (string) $params['user'];
				$authPass = (string) SmartCipherCrypto::tf_decrypt((string)$params['pass'], '', true); // TF or BF with fallback ; do not trim
				if((string)trim((string)$authPass) == '') {
					return '';
				} //end if
			default:
				return '';
		} //end switch
		$response = (array) $bw->browse_url((string)$url, (string)$method, '', (string)$authUser, (string)$authPass, 0); // no redirects
		//--
		if(Smart::array_size($response) <= 0) {
			return '';
		} //end if
		if($response['code'] != 200) {
			return '';
		} //end
		$response['content'] = (string) trim((string)$response['content']);
		if((string)$response['content'] == '') {
			return '';
		} //end if
		if((string)trim((string)$response['c-type']) == 'text/plain') {
			self::$cache[(string)$hash] = $response['content'];
			return (string) self::$cache[(string)$hash];
		} elseif(((string)trim((string)$response['c-type']) == 'application/json') || ((string)trim((string)$response['c-type']) == 'text/json')) {
			$jsonArr = Smart::json_decode((string)$response['content']); // do not cast, test array below !
			if(Smart::array_size($jsonArr) < 1) {
				return '';
			} //end if
			if((!isset($jsonArr['access_token'])) OR (!is_string($jsonArr['access_token']))) {
				return '';
			} //end if
			self::$cache[(string)$hash] = trim((string)$jsonArr['access_token']);
			return (string) self::$cache[(string)$hash];
		} //end if
		//--
		return '';
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
