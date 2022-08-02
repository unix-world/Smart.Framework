<?php
// [LIB - Smart.Framework / Plugins / StaticLoad]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//-- r.20220210
// #PLUGINS# :: they can be loaded always (require) or as dependency injection (require_once)
//--
require_once('lib/core/plugins/lib_idn_punycode.php'); 		// idn punnycode converter
require_once('lib/core/plugins/lib_detect_img.php');		// detect img
require_once('lib/core/plugins/lib_robot.php'); 			// smart robot
//--
require_once('lib/core/plugins/lib_mail_send.php');			// mail send client (sendmail, smtp)
require_once('lib/core/plugins/lib_mail_get.php'); 			// mail get client (pop3, imap4)
require_once('lib/core/plugins/lib_mail_decode.php'); 		// mail message decoder (mime)
require_once('lib/core/plugins/lib_mail_notes.php');		// mail notes (mime parsing fixes, decode, encode)
require_once('lib/core/plugins/lib_mail_utils.php');		// mail utils (verify, parse)
//--
require_once('lib/core/plugins/lib_yaml.php');				// yaml converter
require_once('lib/core/plugins/lib_xml.php');				// xml parser and composer
require_once('lib/core/plugins/lib_html.php');				// html parser
require_once('lib/core/plugins/lib_markdown.php'); 			// markdown syntax parser
//--
require_once('lib/core/plugins/lib_db_redis.php');			// redis db connector
require_once('lib/core/plugins/lib_db_mongodb.php');		// mongodb db connector
require_once('lib/core/plugins/lib_db_dba.php');			// dba db connector
require_once('lib/core/plugins/lib_db_sqlite.php');			// sqlite3 db connector
require_once('lib/core/plugins/lib_db_pgsql.php');			// postgresql db connector
require_once('lib/core/plugins/lib_db_mysqli.php');			// mysqli db connector
//--
require_once('lib/core/plugins/lib_pcache_redis.php');		// redis persistent cache
require_once('lib/core/plugins/lib_pcache_mongodb.php');	// mongodb persistent cache
require_once('lib/core/plugins/lib_pcache_dba.php'); 		// dba persistent cache
require_once('lib/core/plugins/lib_pcache_sqlite.php'); 	// sqlite3 persistent cache
//--
require_once('lib/core/plugins/lib_session.php');			// session storage
//--
require_once('lib/core/plugins/lib_imgd.php');				// img (gd) process
//--
require_once('lib/core/plugins/lib_qrcode.php'); 			// qrcode integration for captcha
require_once('lib/core/plugins/lib_captcha_svg.php'); 		// captcha svg plugin
require_once('lib/core/plugins/lib_captcha_ascii.php'); 	// captcha ascii plugin
require_once('lib/core/plugins/lib_captcha_form.php'); 		// captcha form manager
//--
require_once('lib/core/plugins/lib_viewhelpers.php'); 		// viewhelpers components
//--
require_once('lib/core/plugins/lib_spreadsheet.php');		// spreadsheet export / import
//--


// end of php code
