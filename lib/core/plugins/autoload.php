<?php
// [LIB - Smart.Framework / Plugins / AutoLoad]
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//-- r.20220210
// #PLUGINS# :: they are loaded via Dependency Injection
//--
/**
 * Function AutoLoad Plugins
 *
 * @access 		private
 * @internal
 *
 */
function autoload__SmartFrameworkPlugins($classname) {
	//--
	if((string)substr((string)$classname, 0, 5) !== 'Smart') { // must start with Smart
		return;
	} //end if
	//--
	switch((string)$classname) {
		//-- idn
		case 'SmartPunycode':
			require_once('lib/core/plugins/lib_idn_punycode.php'); 		// idn punnycode converter
			break;
		//-- detect
		case 'SmartDetectImages':
			require_once('lib/core/plugins/lib_detect_img.php');		// detect img
			break;
		//-- robot
		case 'SmartRobot':
			require_once('lib/core/plugins/lib_robot.php'); 			// smart robot
			break;
		//-- mail
		case 'SmartMailerSend':
		case 'SmartMailerSmtpClient':
			require_once('lib/core/plugins/lib_mail_send.php');			// mail send client (sendmail, smtp)
			break;
		case 'SmartMailerPop3Client':
		case 'SmartMailerImap4Client':
			require_once('lib/core/plugins/lib_mail_get.php'); 			// mail get client (pop3, imap4)
			break;
		case 'SmartMailerMimeDecode':
		case 'SmartMailerMimeExtract':
			require_once('lib/core/plugins/lib_mail_decode.php'); 		// mail message decoder (mime)
			break;
		case 'SmartMailerNotes':
			require_once('lib/core/plugins/lib_mail_notes.php');		// mail notes (mime parsing fixes, decode, encode)
			break;
		case 'SmartMailerMimeParser':
		case 'SmartMailerUtils':
			require_once('lib/core/plugins/lib_mail_utils.php');		// mail utils (send, verify, parse)
			break;
		//-- yaml parser and composer
		case 'SmartYamlConverter':
			require_once('lib/core/plugins/lib_yaml.php');				// yaml converter
			break;
		//-- xml parser and composer
		case 'SmartDomUtils':
		case 'SmartXmlComposer':
		case 'SmartXmlParser':
			require_once('lib/core/plugins/lib_xml.php');				// xml parser and composer
			break;
		//-- html parser
		case 'SmartHtmlParser':
			require_once('lib/core/plugins/lib_html.php');				// html parser
			break;
		//-- markdown
		case 'SmartMarkdownToHTML':
			require_once('lib/core/plugins/lib_markdown.php');			// markdown to html parser, v2
			break;
		//-- db drivers
		case 'SmartRedisDb':
			require_once('lib/core/plugins/lib_db_redis.php');			// redis db connector
			break;
		case 'SmartMongoDb':
			require_once('lib/core/plugins/lib_db_mongodb.php');		// mongodb db connector
			break;
		case 'SmartDbaUtilDb':
		case 'SmartDbaDb':
			require_once('lib/core/plugins/lib_db_dba.php');			// dba db connector
			break;
		case 'SmartSQliteFunctions':
		case 'SmartSQliteUtilDb':
		case 'SmartSQliteDb':
			require_once('lib/core/plugins/lib_db_sqlite.php');			// sqlite3 db connector
			break;
		case 'SmartPgsqlDb':
		case 'SmartPgsqlExtDb':
			require_once('lib/core/plugins/lib_db_pgsql.php');			// postgresql db connector
			break;
		case 'SmartMysqliDb':
		case 'SmartMysqliExtDb':
			require_once('lib/core/plugins/lib_db_mysqli.php'); 		// mysqli db connector
			break;
		//-- persistent cache
		case 'SmartRedisPersistentCache':
			require_once('lib/core/plugins/lib_pcache_redis.php');		// redis persistent cache
			break;
		case 'SmartMongoDbPersistentCache':
			require_once('lib/core/plugins/lib_pcache_mongodb.php');	// mongodb persistent cache
			break;
		case 'SmartDbaPersistentCache':
			require_once('lib/core/plugins/lib_pcache_dba.php'); 		// dba persistent cache
			break;
		case 'SmartSQlitePersistentCache':
			require_once('lib/core/plugins/lib_pcache_sqlite.php'); 	// sqlite3 persistent cache
			break;
		//-- session handler
		case 'SmartAbstractCustomSession':
		case 'SmartSession':
			require_once('lib/core/plugins/lib_session.php');			// session handler
			break;
		//-- im (gd) process
		case 'SmartImageGdProcess':
			require_once('lib/core/plugins/lib_imgd.php');				// img (gd) process
			break;
		//-- captcha
		case 'SmartQR2DBarcode':
			require_once('lib/core/plugins/lib_qrcode.php'); 			// qrcode integration for captcha
			break;
		case 'SmartSVGCaptcha':
			require_once('lib/core/plugins/lib_captcha_svg.php'); 		// captcha svg plugin
			break;
		case 'SmartAsciiCaptcha':
			require_once('lib/core/plugins/lib_captcha_ascii.php'); 	// captcha ascii plugin
			break;
		case 'SmartCaptcha':
			require_once('lib/core/plugins/lib_captcha_form.php'); 		// captcha form manager
			break;
		//-- viewhelpers
		case 'SmartViewHtmlHelpers':
			require_once('lib/core/plugins/lib_viewhelpers.php'); 		// viewhelpers components (html / js)
			break;
		//-- spreadsheet export / import
		case 'SmartSpreadSheetExport':
		case 'SmartSpreadSheetImport':
			require_once('lib/core/plugins/lib_spreadsheet.php');		// spreadsheet export / import
			break;
		//--
		default:
			return; // other classes are not managed here ...
		//--
	} //end switch
	//--
} //END FUNCTION
//--
spl_autoload_register('autoload__SmartFrameworkPlugins', true, true); 	// throw / prepend
//--


// end of php code
