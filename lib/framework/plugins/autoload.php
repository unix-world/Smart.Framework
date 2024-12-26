<?php
// [LIB - Smart.Framework / Plugins / AutoLoad]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//-- r.20221225
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
		//--
		case 'SmartYamlConverter':
			require_once('lib/framework/plugins/lib_yaml.php');				// yaml converter
			break;
		case 'SmartDomUtils':
		case 'SmartXmlParser':
		case 'SmartXmlComposer':
			require_once('lib/framework/plugins/lib_xml.php');				// xml parser and composer
			break;
		case 'SmartHtmlParser':
			require_once('lib/framework/plugins/lib_html.php');				// html parser
			break;
		case 'SmartMarkdownToHTML':
			require_once('lib/framework/plugins/lib_markdown.php');			// markdown to html parser, v2
			break;
		//--
		case 'SmartPunycode':
			require_once('lib/framework/plugins/lib_idn_punycode.php'); 	// idn punnycode converter
			break;
		case 'SmartDetectImages':
			require_once('lib/framework/plugins/lib_detect_img.php');		// detect img
			break;
		//--
		case 'SmartMailerSmtpClient':
		case 'SmartMailerSend':
			require_once('lib/framework/plugins/lib_mail_send.php');		// mail send client (sendmail, smtp)
			break;
		case 'SmartMailerImap4Client':
		case 'SmartMailerPop3Client':
			require_once('lib/framework/plugins/lib_mail_get.php'); 		// mail get client (pop3, imap4)
			break;
		case 'SmartMailerNotes':
			require_once('lib/framework/plugins/lib_mail_notes.php');		// mail notes (mime parsing fixes, decode, encode)
			break;
		case 'SmartMailerMimeExtract':
		case 'SmartMailerMimeDecode':
			require_once('lib/framework/plugins/lib_mail_decode.php'); 		// mail message decoder (mime)
			break;
		//--
		case 'SmartRedisDb':
			require_once('lib/framework/plugins/lib_db_redis.php');			// redis db connector
			break;
		case 'SmartMongoDb':
			require_once('lib/framework/plugins/lib_db_mongodb.php');		// mongodb db connector
			break;
		case 'SmartPgsqlDb':
		case 'SmartPgsqlExtDb':
			require_once('lib/framework/plugins/lib_db_pgsql.php');			// postgresql db connector
			break;
		case 'SmartMysqliDb':
		case 'SmartMysqliExtDb':
			require_once('lib/framework/plugins/lib_db_mysqli.php'); 		// mysqli db connector
			break;
		//--
		case 'SmartRedisPersistentCache':
			require_once('lib/framework/plugins/lib_pcache_redis.php');		// redis persistent cache
			break;
		case 'SmartMongoDbPersistentCache':
			require_once('lib/framework/plugins/lib_pcache_mongodb.php');	// mongodb persistent cache
			break;
		//--
		case 'SmartSpreadSheetExport':
		case 'SmartSpreadSheetImport':
			require_once('lib/framework/plugins/lib_spreadsheet.php');		// spreadsheet export / import
			break;
		case 'SmartQR2DBarcode':
			require_once('lib/framework/plugins/lib_qrcode.php'); 			// qrcode integration for captcha
			break;
		case 'SmartAsciiCaptcha':
			require_once('lib/framework/plugins/lib_captcha_ascii.php'); 	// captcha ascii plugin
			break;
		case 'SmartImageGdProcess':
			require_once('lib/framework/plugins/lib_imgd.php');				// img (gd) process
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
