<?php
// [LIB - Smart.Framework / Smart Components]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Smart.Framework - Smart Components
//======================================================

// [REGEX-SAFE-OK] ; [PHP8]

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================

//===== pre-read to make this available for errors called from register_shutdown_function() handlers as they cannot handle relative paths ... (Ex: session handler with sqlite)
if(defined('SMART_TPL_COMPONENTS_APP_ERROR_MSG')) {
	@http_response_code(500);
	die('The constant SMART_TPL_COMPONENTS_APP_ERROR_MSG must not be previous defined: '.@basename(__FILE__).' ...');
} //end if
define('SMART_TPL_COMPONENTS_APP_ERROR_MSG', (string)file_get_contents('lib/core/templates/app-error-message.inc.htm', false)); // it must not contain any sub-templates
if((!is_string(SMART_TPL_COMPONENTS_APP_ERROR_MSG)) || ((string)trim((string)SMART_TPL_COMPONENTS_APP_ERROR_MSG) == '')) { // file_get_contents will return FALSE on failure, thus check if is string ; must not be empty string if file exists or read was successful
	@http_response_code(500);
	die('Wrong Definition for SMART_TPL_COMPONENTS_APP_ERROR_MSG: '.@basename(__FILE__).' ...');
} //end if
//=====

/**
 * Class: SmartComponents - provides various components for Smart.Framework
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	css: notifications.css ; classes: Smart, SmartUtils, SmartFileSystem, SmartTextTranslations, SmartMarkersTemplating
 * @version 	v.20241123
 * @package 	Application:ViewComponents
 *
 */
final class SmartComponents {

	// ::
	// {{{SYNC-SMART-HTTP-STATUS-CODES}}}


	// {{{SYNC-INIT-MTPL-DEFVARS}}}
	public const DEFAULT_PAGE_VARS = [
		'template-path',		// current template path ; ex: 'etc/templates/default/' ; relative path, trailing slash is required
		'template-file',		// current template file ; ex: 'template.htm' | 'template-modal.htm', ... whatever template is available
		'semaphore',			// a general purpose page conditional variable ; ex: '<theme:dark>,<skip:unveil-js>'
		'struct-schema',		// structured document schema ; ex: '//schema.org/CreativeWork' ; '<script type="application/ld+json">{}</script>'
		'canonical-url',		// page canonical url ; ex: '//website.test/this-page.html'
		'title',				// page title ; ex: 'The Page Title'
		'meta-description',		// page meta description ; ex: 'This is an example page'
		'meta-keywords',		// page meta keywords ; ex: 'keyword1, keyword2, ..., keywordN'
		'head-meta',			// head extra meta tags section
		'head-css',				// head extra css style tags section
		'head-js',				// head extra javascript tags section
		'header',				// page header section
		'nav',					// page nav section
		'main',					// page main section ; for raw pages this is the only that will display
		'aside',				// page aside section
		'footer',				// page footer section
	];

	private static $default_css = null;


	//================================================================
	/**
	 * Return App Default CSS
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function app_default_css() : string {
		//--
		if(self::$default_css !== null) {
			return (string) self::$default_css;
		} //end if
		//--
		self::$default_css = (string) trim((string)SmartFileSystem::read('lib/css/default.css'));
		if((string)self::$default_css == '') {
			Smart::log_warning(__METHOD__.' # The Default CSS `default.css` is not accessible or empty !');
		} //end if
		//--
		return (string) self::$default_css;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Compose an App Error Message
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function app_error_message(?string $y_title, ?string $y_name, ?string $y_mode, ?string $y_type, ?string $y_logo, ?int $y_width, ?string $y_area, ?string $y_errmsg, ?string $y_area_one, ?string $y_area_two) : string {
		//--
		$y_width = (int) $y_width;
		if($y_width < 250) {
			$y_width = 250;
		} elseif($y_width > 750) {
			$y_width = 750;
		} //end if
		//--
		$y_area     = (string) trim((string)$y_area); // if this is empty will simply not be displayed
		$y_area_one = (string) trim((string)$y_area_one); // if this is empty will display: DEBUG OFF
		$y_area_two = (string) trim((string)$y_area_two); // if this is empty will display: View App Log for more details ...
		//--
		return (string) SmartMarkersTemplating::render_template(
			(string) SMART_TPL_COMPONENTS_APP_ERROR_MSG,
			[
				'WIDTH' 	=> (int)    $y_width,
				'TITLE' 	=> (string) $y_title,
				'AREA' 		=> (string) $y_area,
				'LOGO' 		=> (string) $y_logo,
				'NAME' 		=> (string) $y_name,
				'MODE' 		=> (string) $y_mode,
				'TYPE' 		=> (string) $y_type,
				'ERR-MSG' 	=> (string) $y_errmsg,
				'AREA-ONE' 	=> (string) $y_area_one,
				'AREA-TWO' 	=> (string) $y_area_two,
				'CRR-URL' 	=> (string) SmartUtils::get_server_current_url()
			]
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: HTTP Status Message
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function http_status_message(?string $y_title, ?string $y_message='', ?string $y_html_message='', ?string $y_opstyle='') : string {
		//--
		// IMPORTANT: 204 code is not managed, this is: `No Content` used by APIs
		//--
		$y_opstyle = (string) strtolower((string)trim((string)$y_opstyle));
		//--
		$msg_html = (string) Smart::nl_2_br((string)Smart::escape_html((string)$y_message));
		//--
		$tpl = 'lib/core/templates/http-message-status.htm';
		switch((string)$y_opstyle) {
			case 'proxy':
				$tpl = 'lib/core/templates/http-message-proxy-status.htm';
				$msg_html = (string) self::operation_display((string)$msg_html, '100%', false); // without icon
				break;
			case '3xx':
			case 'displayx':
				$msg_html = (string) self::operation_display((string)$msg_html, '100%', false); // without icon
				break;
			case '200':
			case 'display':
				$msg_html = (string) self::operation_display((string)$msg_html, '100%', true); // default, with icon
				break;
			case '208':
			case 'hint':
				$msg_html = (string) self::operation_hint((string)$msg_html, '100%');
				break;
			case '203':
			case 'result':
				$msg_html = (string) self::operation_result((string)$msg_html, '100%');
				break;
			case '202':
			case 'important':
			default:
				$msg_html = (string) self::operation_important((string)$msg_html, '100%');
		} //end switch
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			(string) $tpl,
			[
				'CHARSET' 			=> (string) SmartUtils::get_encoding_charset(),
				'BASE-URL' 			=> (string) SmartUtils::get_server_current_url(),
				'TITLE' 			=> (string) $y_title,
				'SIGNATURE-HTML' 	=> (string) '<b>Smart.Framework :: WebApp</b><br>'.Smart::escape_html((string)SmartUtils::get_server_current_url(false)),
				'MESSAGE-HTML' 		=> (string) (((string)trim((string)$y_message) != '') ? $msg_html : ''),
				'EXTMSG-HTML' 		=> (string) $y_html_message
			]
			// use caching, as default
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: HTTP Error Message
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function http_error_message(?string $y_title, ?string $y_message='', ?string $y_html_message='') : string {
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/templates/http-message-error.htm',
			[
				'CHARSET' 			=> SmartUtils::get_encoding_charset(),
				'BASE-URL' 			=> SmartUtils::get_server_current_url(),
				'TITLE' 			=>(string) $y_title,
				'SIGNATURE-HTML' 	=> '<b>Smart.Framework :: WebApp</b><br>'.Smart::escape_html(SmartUtils::get_server_current_request_method().' '.SmartUtils::get_server_current_protocol().SmartUtils::get_server_current_domain_name().':'.SmartUtils::get_server_current_port().SmartUtils::get_server_current_request_uri()),
				'MESSAGE-HTML' 		=> self::operation_error(Smart::nl_2_br(Smart::escape_html((string)$y_message)), '100%'),
				'EXTMSG-HTML' 		=> (string) $y_html_message
			]
			// use caching, as default
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 400 Bad Request :: The server cannot or will not process the request due to something that is perceived to be a client error (e.g., malformed request syntax, invalid request message framing, or deceptive request routing).
	public static function http_message_400_badrequest(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'400.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'400.php');
				if(function_exists('custom_http_message_400_badrequest')) {
					return (string) custom_http_message_400_badrequest((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('400 Bad Request', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 401 Unauthorized :: Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided. The response must include a WWW-Authenticate header field containing a challenge applicable to the requested resource. See Basic access authentication and Digest access authentication.
	public static function http_message_401_unauthorized(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'401.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'401.php');
				if(function_exists('custom_http_message_401_unauthorized')) {
					return (string) custom_http_message_401_unauthorized((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('401 Unauthorized', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 403 Forbidden :: The request was a valid request, but the server is refusing to respond to it. Unlike a 401 Unauthorized response, authenticating will make no difference.
	public static function http_message_403_forbidden(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'403.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'403.php');
				if(function_exists('custom_http_message_403_forbidden')) {
					return (string) custom_http_message_403_forbidden((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('403 Forbidden', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 404 Not Found :: The requested resource could not be found but may be available again in the future. Subsequent requests by the client are permissible.
	public static function http_message_404_notfound(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'404.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'404.php');
				if(function_exists('custom_http_message_404_notfound')) {
					return (string) custom_http_message_404_notfound((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('404 Not Found', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 405 Method Not Allowed :: The requested method is not allowed on this url.
	public static function http_message_405_methodnotallowed(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'405.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'405.php');
				if(function_exists('custom_http_message_405_methodnotallowed')) {
					return (string) custom_http_message_405_methodnotallowed((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('405 Method Not Allowed', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 410 Gone :: A 410 is more permanent than a 404; it means that the page is gone. To be used for limited-time / promotional services.
	public static function http_message_410_gone(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'410.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'410.php');
				if(function_exists('custom_http_message_410_gone')) {
					return (string) custom_http_message_410_gone((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('410 Gone', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 422 Unprocessable Content (Unprocessable Entity) :: The user has sent a payload that is not valid.
	public static function http_message_422_unprocessablecontent(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'422.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'422.php');
				if(function_exists('custom_http_message_422_unprocessablecontent')) {
					return (string) custom_http_message_422_unprocessablecontent((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('422 Unprocessable Content', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 429 Too Many Requests :: The user has sent too many requests in a given amount of time. Intended for use with rate-limiting schemes.
	public static function http_message_429_toomanyrequests(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'429.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'429.php');
				if(function_exists('custom_http_message_429_toomanyrequests')) {
					return (string) custom_http_message_429_toomanyrequests((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('429 Too Many Requests', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 500 Internal Server Error :: A generic error message, given when an unexpected condition was encountered and no more specific message is suitable.
	public static function http_message_500_internalerror(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'500.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'500.php');
				if(function_exists('custom_http_message_500_internalerror')) {
					return (string) custom_http_message_500_internalerror((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('500 Internal Server Error', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 501 Not Implemented :: A generic error message, given when the HTTP Method is not implemented.
	public static function http_message_501_notimplemented(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'501.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'501.php');
				if(function_exists('custom_http_message_501_notimplemented')) {
					return (string) custom_http_message_501_notimplemented((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('501 Not Implemented', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 502 Bad Gateway :: The server was acting as a gateway or proxy and received an invalid response from the upstream server.
	public static function http_message_502_badgateway(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'502.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'502.php');
				if(function_exists('custom_http_message_502_badgateway')) {
					return (string) custom_http_message_502_badgateway((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('502 Bad Gateway', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 503 Service Unavailable :: The server is currently unavailable (because it is overloaded or down for maintenance). Generally, this is a temporary state.
	public static function http_message_503_serviceunavailable(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'503.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'503.php');
				if(function_exists('custom_http_message_503_serviceunavailable')) {
					return (string) custom_http_message_503_serviceunavailable((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('503 Service Unavailable', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// 504 Gateway Timeout :: The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.
	public static function http_message_504_gatewaytimeout(?string $y_message, ?string $y_html_message='') : string {
		//--
		if(defined('SMART_FRAMEWORK_CUSTOM_ERR_PAGES')) {
			//--
			if(SmartFileSystem::is_type_file(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'504.php')) {
				require_once(SMART_FRAMEWORK_CUSTOM_ERR_PAGES.'504.php');
				if(function_exists('custom_http_message_504_gatewaytimeout')) {
					return (string) custom_http_message_504_gatewaytimeout((string)$y_message, (string)$y_html_message);
				} //end if
			} //end if
			//--
		} //end if
		//--
		return (string) self::http_error_message('504 Gateway Timeout', (string)$y_message, (string)$y_html_message);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_question(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_question', (string)$y_width); // question
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_notice(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_notice', (string)$y_width); // notice
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_ok(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_info', (string)$y_width); // ok
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_warn(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_warn', (string)$y_width); // warn
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_error(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_error', (string)$y_width); // error
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_success(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_success', (string)$y_width); // success
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_important(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_important', (string)$y_width); // important
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_result(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_result', (string)$y_width); // result
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_display(?string $y_html, ?string $y_width='', bool $y_use_icon=true) : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_display', (string)$y_width, (string)(!!$y_use_icon ? 'icon' : '')); // display
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	public static function operation_hint(?string $y_html, ?string $y_width='') : string {
		//--
		return (string) self::notifications_template((string)$y_html, 'operation_hint', (string)$y_width); // display
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: Notifications Message Template
	 */
	private static function notifications_template(?string $y_html, ?string $y_notification_class, ?string $y_width, ?string $y_css_classes='') : string {
		//--
		$style = '';
		if((string)$y_width != '') {
			$style = (string) 'width:'.self::fix_css_elem_dim((string)$y_width).';';
		} //end if else
		//--
		$y_css_classes = (string) trim((string)$y_css_classes);
		if((string)$y_css_classes != '') {
			$y_css_classes = ' '.$y_css_classes;
		} //end if
		//--
		return (string) '<!-- require: notifications.css --><div class="'.Smart::escape_html((string)$y_notification_class.$y_css_classes).'" style="'.Smart::escape_html((string)$style).'">'.$y_html.'</div>';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: Format CSS Dimension for Elements
	 * If no unit is specified then assume px (pixels)
	 * If number is < 0, will assume 1 to avoid hide element
	 * Allowed Units: %, vw, vh, pt, pc, px
	 * Returns STRING the CSS safe formated dimension
	 */
	private static function fix_css_elem_dim(?string $css_w_or_h) : string {
		//--
		$css_w_or_h = (string) Smart::normalize_spaces((string)$css_w_or_h); // $css_w_or_h = str_replace([' ', "\t", "\n", "\r"], '', (string)$css_w_or_h);
		$css_w_or_h = (string) trim((string)$css_w_or_h);
		//--
		$css_w_or_h = (array) explode(';', (string)$css_w_or_h);
		$css_w_or_h = (string) trim((string)($css_w_or_h[0] ?? ''));
		$matches = array();
		$found = preg_match('/^([0-9]+)(%|[a-z]{1,2})?$/', (string)$css_w_or_h, $matches);
		if($found === false) {
			Smart::log_warning(__METHOD__.'() # ERROR: '.SMART_FRAMEWORK_ERR_PCRE_SETTINGS);
		} //end if
		if(!array_key_exists(0, $matches)) {
			$matches[0] = null;
		} //end if
		if(!array_key_exists(1, $matches)) {
			$matches[1] = null;
		} //end if
		if(!array_key_exists(2, $matches)) {
			$matches[2] = null;
		} //end if
		$css_unit = 'px';
		$css_num = (int) $matches[1];
		if($css_num <= 0) {
			$css_num = 1;
		} //end if
		$css_w_or_h = '';
		switch((string)$matches[2]) {
			case '%':
			case 'vw':
			case 'vh':
				$css_unit = (string) $matches[2];
				if($css_num > 100) {
					$css_num = 100;
				} //end if
				break;
			case 'pt':
			case 'pc':
			case 'px':
				$css_unit = (string) $matches[2];
				break;
			default:
				$css_unit = 'px';
		} //end switch
		if($css_num > 3200) {
			$css_num = 3200; // avoid too large values
		} //end if
		$css_w_or_h = (string) $css_num.$css_unit;
		//--
		return (string) $css_w_or_h;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Function: Draw App Powered Info
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function app_powered_info(?string $y_show_versions='no', array $y_plugins=[], bool $exclude_db_plugins=false, bool $display_clock=false, bool $display_app_logo=true) : string {
		//--
		global $configs;
		//--
		$base_url = (string) SmartUtils::get_server_current_url();
		//-- framework
		$software_name = 'Smart.Framework, a PHP / Javascript Web Framework';
		if(!defined('SMART_SOFTWARE_DISABLE_STATUS_POWERED') OR SMART_SOFTWARE_DISABLE_STATUS_POWERED !== true) {
			$software_name .= ' :: '.(defined('SMART_FRAMEWORK_RELEASE_TAGVERSION') ? SMART_FRAMEWORK_RELEASE_TAGVERSION : '').'-'.(defined('SMART_FRAMEWORK_RELEASE_VERSION') ? SMART_FRAMEWORK_RELEASE_VERSION : '').' @ '.(defined('SMART_SOFTWARE_APP_NAME') ? SMART_SOFTWARE_APP_NAME : '');
		} //end if
		$software_logo = $base_url.'lib/framework/img/sf-logo.svg';
		$software_url = (string) (defined('SMART_FRAMEWORK_RELEASE_URL') ? SMART_FRAMEWORK_RELEASE_URL : '');
		//--
		$arr_powered_sside = [];
		//-- os
		$arr_os = (array) self::get_imgdesc_by_os_id((string)SmartUtils::get_server_os());
		$os_pict = (string) $arr_os['img'];
		$os_desc = (string) $arr_os['desc'];
		$arr_powered_sside[] = [
			'name' 	=> (string) $os_desc,
			'logo' 	=> (string) $base_url.$os_pict,
			'url' 	=> (string) ''
		];
		//-- web server
		$tmp_arr_web_server = (array) SmartUtils::get_webserver_version();
		$name_webserver = (string) $tmp_arr_web_server['name'].' Web Server';
		if((string)$y_show_versions == 'yes') { // expose versions (not recommended in web area, except for auth admins)
			$name_webserver .= ' :: '.$tmp_arr_web_server['version'];
		} //end if
		if(stripos((string)$name_webserver, 'apache') !== false) {
			$logo_webserver = 'lib/framework/img/apache-logo.svg';
			$url_webserver = 'https://httpd.apache.org';
		} elseif(stripos((string)$name_webserver, 'nginx') !== false) {
			$logo_webserver = 'lib/framework/img/nginx-logo.svg';
			$url_webserver = 'https://www.nginx.com';
		} else {
			$logo_webserver = 'lib/framework/img/haproxy-logo.svg';
			$url_webserver = '';
		} //end if else
		$arr_powered_sside[] = [
			'name' 	=> (string) $name_webserver,
			'logo' 	=> (string) $base_url.$logo_webserver,
			'url' 	=> (string) $url_webserver
		];
		//-- php
		$php_name = 'PHP Server-Side Scripting Language';
		if((string)$y_show_versions == 'yes') { // expose versions (not recommended in web area, except for auth admins)
			$php_name .= ' :: '.PHP_VERSION;
		} //end if
		$arr_powered_sside[] = [
			'name' 	=> (string) $php_name,
			'logo' 	=> (string) $base_url.'lib/framework/img/php-logo.svg',
			'url' 	=> (string) 'http://www.php.net'
		];
		//-- db plugins
		if($exclude_db_plugins !== true) {
			//-- sqlite
			if(array_key_exists('sqlite', $configs) AND (is_array($configs['sqlite']))) {
				$arr_powered_sside[] = [
					'name' 	=> (string) 'SQLite Embedded Database',
					'logo' 	=> (string) $base_url.'lib/core/img/db/sqlite-logo.svg',
					'url' 	=> (string) 'https://www.sqlite.org'
				];
			} //end if
			//-- dba
			if(array_key_exists('dba', $configs) AND (is_array($configs['dba']))) {
				$arr_powered_sside[] = [
					'name' 	=> (string) 'DBA/GDBM Embedded DataStore ('.$configs['dba']['handler'].')',
					'logo' 	=> (string) $base_url.'lib/core/img/db/dba-logo.svg',
					'url' 	=> (string) 'https://www.gnu.org/software/gdbm'
				];
			} //end if
			//-- redis
			if(array_key_exists('redis', $configs) AND (is_array($configs['redis']))) {
				$arr_powered_sside[] = [
					'name' 	=> (string) 'Redis In-Memory Distributed Key-Value Store (Caching Data Store)',
					'logo' 	=> (string) $base_url.'lib/core/img/db/redis-logo.svg',
					'url' 	=> (string) 'https://redis.io'
				];
			} //end if
			//-- mongodb
			if(array_key_exists('mongodb', $configs) AND (is_array($configs['mongodb']))) {
				$arr_powered_sside[] = [
					'name' 	=> (string) 'MongoDB BigData Server',
					'logo' 	=> (string) $base_url.'lib/core/img/db/mongodb-logo.svg',
					'url' 	=> (string) 'https://docs.mongodb.com'
				];
			} //end if
			//-- pgsql
			if(array_key_exists('pgsql', $configs) AND (is_array($configs['pgsql']))) {
				$arr_powered_sside[] = [
					'name' 	=> (string) 'PostgreSQL Database Server',
					'logo' 	=> (string) $base_url.'lib/core/img/db/postgresql-logo.svg',
					'url' 	=> (string) 'https://www.postgresql.org'
				];
			} //end if
			//-- mysqli
			if(array_key_exists('mysqli', $configs) AND (is_array($configs['mysqli']))) {
				$tmp_name = (string) trim((string)$configs['mysqli']['type']);
				$arr_powered_sside[] = [
					'name' 	=> (string) ucfirst((string)$tmp_name).' Database Server',
					'logo' 	=> (string) $base_url.'lib/core/img/db/mysql-logo.svg',
					'url' 	=> (string) 'https://mariadb.org'
				];
			} //end if
			//--
		} //end if
		//--
		$arr_powered_cside = [];
		//-- html
		$arr_powered_cside[] = [
			'name' 	=> (string) 'HTML Markup Language for World Wide Web',
			'logo' 	=> (string) $base_url.'lib/framework/img/html-logo.svg',
			'url' 	=> (string) 'https://www.w3.org/TR/html/'
		];
		//-- css
		$arr_powered_cside[] = [
			'name' 	=> (string) 'CSS Style Sheet Language for World Wide Web',
			'logo' 	=> (string) $base_url.'lib/framework/img/css-logo.svg',
			'url' 	=> (string) 'https://www.w3.org/TR/CSS/'
		];
		//-- javascript
		$arr_powered_cside[] = [
			'name' 	=> (string) 'Javascript Client-Side Scripting Language for World Wide Web',
			'logo' 	=> (string) $base_url.'lib/framework/img/javascript-logo.svg',
			'url' 	=> (string) 'https://developer.mozilla.org/en-US/docs/Web/JavaScript'
		];
		//-- jquery
		$arr_powered_cside[] = [
			'name' 	=> (string) 'jQuery Javascript Library',
			'logo' 	=> (string) $base_url.'lib/framework/img/jquery-logo.svg',
			'url' 	=> (string) 'https://jquery.com'
		];
		//--
		if(Smart::array_size($y_plugins) > 0) {
			for($i=0; $i<Smart::array_size($y_plugins); $i++) {
				$tmp_arr = [];
				if(is_array($y_plugins[$i])) {
					if(array_key_exists('name', $y_plugins[$i]) AND ((string)$y_plugins[$i]['name'] != '') AND array_key_exists('logo', $y_plugins[$i]) AND ((string)$y_plugins[$i]['logo'] != '')) {
						$tmp_arr = [
							'name' 	=> (string) $y_plugins[$i]['name'],
							'logo' 	=> (string) $y_plugins[$i]['logo'],
							'url' 	=> (string) $y_plugins[$i]['url']
						];
						if((string)$y_plugins[$i]['type'] == 'sside') {
							$arr_powered_sside[] = (array) $tmp_arr;
						} elseif((string)$y_plugins[$i]['type'] == 'cside') {
							$arr_powered_cside[] = (array) $tmp_arr;
						} //end if else
					} else {
						$arr_powered_cside[] = [
							'name' 	=> '',
							'logo' 	=> '',
							'url' 	=> ''
						];
					} //end if
				} //end if
			} //end for
		} //end if
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/templates/app-powered-info.inc.htm',
			[
				'SHOW-APP' 		=> (string) (($display_app_logo === false) ? 'no' : 'yes'),
				'APP-NAME' 		=> (string) $software_name,
				'APP-LOGO' 		=> (string) $software_logo,
				'APP-URL' 		=> (string) $software_url,
				'ARR-SSIDE' 	=> (array)  $arr_powered_sside,
				'ARR-CSIDE' 	=> (array)  $arr_powered_cside,
				'SHOW-CLOCK' 	=> (string) (($display_clock === false) ? 'no' : 'yes'),
			]
			// use caching, as default
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// This conform the var names to lowercase and set the meta vars into a template array context (by default this is used by ::render_app_template() but can be used outside if needed ...
	public static function set_app_template_conform_metavars(array $arr_data=[]) : array {
		//--
		if(!is_array($arr_data)) {
			return array();
		} //end if
		//--
		if(SmartEnvironment::isAdminArea() === true) {
			if(SmartEnvironment::isTaskArea() === true) {
				$the_area = 'task';
				$the_realm = 'TSK';
			} else {
				$the_area = 'admin';
				$the_realm = 'ADM';
			} //end if else
		} else {
			$the_area = 'index';
			$the_realm = 'IDX';
		} //end if else
		$os_bw = (array) SmartUtils::get_os_browser_ip();
		//--
		$arr_data = (array) array_change_key_case((array)$arr_data, CASE_LOWER); // make all keys lower (only 1st level, not nested), to comply with SmartAbstractAppController handling mode
		//--
		$netport = (string) SmartUtils::get_server_current_port();
		$srvport = (string) ((($netport == 80) || ($netport == 443)) ? '' : ':'.$netport);
		$srvproto = (string) SmartUtils::get_server_current_protocol();
		//--
		$charset = (string) (defined('SMART_FRAMEWORK_CHARSET') ? SMART_FRAMEWORK_CHARSET : '');
		$timezone = (string) (defined('SMART_FRAMEWORK_TIMEZONE') ? SMART_FRAMEWORK_TIMEZONE : '');
		$cookiename = (string) (defined('SMART_FRAMEWORK_UUID_COOKIE_NAME') ? SMART_FRAMEWORK_UUID_COOKIE_NAME : '');
		$namespace = (string) (defined('SMART_SOFTWARE_NAMESPACE') ? SMART_SOFTWARE_NAMESPACE : '');
		$timeout_execution = (int) (defined('SMART_FRAMEWORK_EXECUTION_TIMEOUT') ? SMART_FRAMEWORK_EXECUTION_TIMEOUT : 0);
		$timeout_netsocket = (int) (defined('SMART_FRAMEWORK_NETSOCKET_TIMEOUT') ? SMART_FRAMEWORK_NETSOCKET_TIMEOUT : 0);
		//--
		$arr_data['release-hash'] 				= (string) SmartUtils::get_app_release_hash(); 								// the release hash based on app framework version, framework release and modules version
		$arr_data['lang'] 						= (string) SmartTextTranslations::getLanguage(); 							// current language (ex: en)
		$arr_data['charset'] 					= (string) $charset;														// current charset (ex: UTF-8)
		$arr_data['timezone'] 					= (string) $timezone; 														// current timezone (ex: UTC)
		$arr_data['client-ip'] 					= (string) $os_bw['ip']; 													// client browser IP (ex: 127.0.0.1)
		$arr_data['client-os'] 					= (string) $os_bw['os']; 													// client browser OS (ex: bsd)
		$arr_data['client-is-mobile'] 			= (string) $os_bw['mobile']; 												// client browser is Mobile (yes/no)
		$arr_data['client-class'] 				= (string) $os_bw['bc']; 													// client browser Class (ex: gk)
		$arr_data['client-browser'] 			= (string) $os_bw['bw']; 													// client browser (ex: fox)
		$arr_data['client-uid-cookie-name'] 	= (string) $cookiename;														// client browser UID Cookie Name (as defined in etc/init.php) ; it may be required to pass this cookie name to the Javascript ...
		$arr_data['client-uid-cookie-lifetime'] = (int)    SmartUtils::cookie_default_expire(); 							// client browser UID Cookie Default Expire (as defined in etc/init.php) ; it may be required to pass the cookie default lifetime to the Javascript ...
		$arr_data['client-uid-cookie-domain'] 	= (string) SmartUtils::cookie_default_domain(); 							// client browser UID Cookie Default Domain (as defined in etc/init.php) ; it may be required to pass the cookie default domain to the Javascript ...
		$arr_data['client-uid-cookie-samesite'] = (string) SmartUtils::cookie_default_samesite_policy(); 					// client browser UID Cookie Default SameSite Policy (as defined in etc/init.php) ; it may be required to pass the cookie default SameSite policy to the Javascript ...
		$arr_data['app-env'] 					= (string) (SmartEnvironment::ifDevMode() !== true) ? 'prod' : 'dev'; 		// App Environment: dev | prod :: {{{SYNC-APP-ENV-SETT}}}
		$arr_data['app-namespace'] 				= (string) $namespace;														// NameSpace from configs (as defined in etc/init.php)
		$arr_data['app-realm'] 					= (string) $the_realm; 														// IDX (for index.php area) ; ADM (for admin.php area) ; TSK (for task.php area)
		$arr_data['app-domain'] 				= (string) Smart::get_from_config('app.'.$the_area.'-domain', 'string'); 	// the domain set in configs, that may differ by area: $configs['app']['index-domain'] | $configs['app']['admin-domain']
		$arr_data['base-url'] 					= (string) SmartUtils::get_server_current_url(); 							// http(s)://crr-subdomain.crr-domain.ext/ | http(s)://crr-domain.ext/ | http(s)://127.0.0.1/sites/frameworks/smart-framework/
		$arr_data['base-path'] 					= (string) SmartUtils::get_server_current_path(); 							// / | /sites/frameworks/smart-framework/
		$arr_data['base-domain'] 				= (string) SmartUtils::get_server_current_basedomain_name(); 				// crr-domain.ext | IP (ex: 127.0.0.1)
		$arr_data['srv-domain'] 				= (string) SmartUtils::get_server_current_domain_name(); 					// crr-subdomain.crr-domain.ext | crr-domain.ext | IP
		$arr_data['srv-ip-addr'] 				= (string) SmartUtils::get_server_current_ip(); 							// current server IP (ex: 127.0.0.1)
		$arr_data['srv-proto'] 					= (string) $srvproto; 														// http:// | https://
		$arr_data['net-proto'] 					= (string) ((string)$srvproto == 'https://') ? 'https' : 'http'; 			// http | https
		$arr_data['prefix-proto'] 				= (string) ((string)$srvproto == 'https://') ? 'https:' : 'http:'; 			// http: | https: ; required for constructs like '[###PREFIX-PROTO|html###]//some.url/'
		$arr_data['srv-port'] 					= (string) $srvport; 														// '' | ''  | ':8080' ... (the current server port address ; empty for port 80 and 443 ; for the rest of ports will be :portnumber)
		$arr_data['net-port'] 					= (string) $netport; 														// 80 | 443 | 8080 ... (the current server port)
		$arr_data['srv-script'] 				= (string) SmartUtils::get_server_current_script(); 						// index.php | admin.php | task.php
		$arr_data['srv-urlquery'] 				= (string) SmartUtils::get_server_current_queryurl(); 						// ?page=some.page&ofs=...
		$arr_data['srv-requri'] 				= (string) SmartUtils::get_server_current_request_uri(); 					// page.html
		$arr_data['timeout-execution'] 			= (int)    $timeout_execution; 												// execution timeout (req. by qunit)
		$arr_data['timeout-netsocket'] 			= (int)    $timeout_netsocket; 												// netsocket timeout (req. by qunit)
		$arr_data['time-date-start'] 			= (string) date('Y-m-d H:i:s O'); 											// date time start
		$arr_data['time-date-year'] 			= (string) date('Y'); 														// date time Year
		$arr_data['auth-login-ok'] 				= (string) (SmartAuth::check_login() === true ? 'yes' : 'no'); 				// Auth Login OK: yes/no
		$arr_data['auth-login-id'] 				= (string) SmartAuth::get_auth_id(); 										// Auth ID (can be the same as UserName or Different)
		$arr_data['auth-login-username'] 		= (string) SmartAuth::get_auth_username(); 									// Auth UserName
		$arr_data['auth-login-fullname'] 		= (string) SmartAuth::get_user_fullname(); 									// (Auth) User FullName
		$arr_data['auth-login-privileges'] 		= (string) SmartAuth::get_user_privileges(); 								// (Auth) User Privileges
		$arr_data['debug-mode'] 				= (string) (SmartEnvironment::ifDebug() ? 'yes' : 'no'); 					// yes | no
		//-- initialize all missing array keys
		for($i=0; $i<count((array)self::DEFAULT_PAGE_VARS); $i++) { // {{{SYNC-INIT-MTPL-DEFVARS}}}
			if(!array_key_exists((string)self::DEFAULT_PAGE_VARS[$i], (array)$arr_data)) { // avoid rewrite a key from above
				$arr_data[(string)self::DEFAULT_PAGE_VARS[$i]] = ''; // init key
			} else {
				if(!Smart::is_nscalar($arr_data[(string)self::DEFAULT_PAGE_VARS[$i]])) {
					$arr_data[(string)self::DEFAULT_PAGE_VARS[$i]] = ''; // reset key, the value is wrong, must be scalar
					Smart::log_warning(__METHOD__.' # Invalid, non-scalar value for page variable `'.strtoupper((string)self::DEFAULT_PAGE_VARS[$i]));
				} //end if
				$arr_data[(string)self::DEFAULT_PAGE_VARS[$i]] = (string) $arr_data[(string)self::DEFAULT_PAGE_VARS[$i]]; // force string
			} //end if
		} //end for
		//--
		return (array) $arr_data;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	// This renders the App Main Template (should be used only on custom developments ...)
	public static function render_app_template(?string $template_path, ?string $template_file, array $arr_data) : string { // {{{SYNC-ARRAY-MAKE-KEYS-LOWER}}}

		//--
		$template_path = (string) Smart::safe_pathname((string)SmartFileSysUtils::addPathTrailingSlash((string)trim((string)$template_path)));
		if(!SmartFileSysUtils::checkIfSafePath((string)$template_path)) {
			Smart::raise_error(
				'#SMART-FRAMEWORK-RENDER-APP-TEMPLATE#'."\n".'The Template Dir Path is Invalid: '.$template_path,
				'App Template Render ERROR :: (See Error Log for More Details)'
			);
			die();
			return (string) '';
		} //end if
		//--
		$template_file = (string) Smart::safe_filename((string)trim((string)$template_file));
		if(!SmartFileSysUtils::checkIfSafeFileOrDirName((string)$template_file)) {
			Smart::raise_error(
				'#SMART-FRAMEWORK-RENDER-APP-TEMPLATE#'."\n".'The Template File Name is Invalid: '.$template_file,
				'App Template Render ERROR :: (See Error Log for More Details)'
			);
			die();
			return (string) '';
		} //end if
		//--
		if(!SmartFileSysUtils::checkIfSafePath((string)$template_path.$template_file)) {
			Smart::raise_error(
				'#SMART-FRAMEWORK-RENDER-APP-TEMPLATE#'."\n".'The Template File Path is Invalid: '.$template_path.$template_file,
				'App Template Render ERROR :: (See Error Log for More Details)'
			);
			die();
			return (string) '';
		} //end if
		//--

		//-- init or set all standard page vars {{{SYNC-INIT-MTPL-DEFVARS}}}
		$arr_data = (array) self::set_app_template_conform_metavars($arr_data);
		$arr_data['template-path'] 				= (string) $template_path; // overwrite
		$arr_data['template-file'] 				= (string) $template_file; // overwrite
		$arr_data['main'] 						= (string) ($arr_data['main'] ?? null); // mandatory
		//--

		//-- read TPL
		$tpl = (string) trim((string)SmartMarkersTemplating::read_template_file((string)$template_path.$template_file)); // no caching by default, the app template is loaded once
		if((string)$tpl == '') {
			Smart::raise_error(
				'#SMART-FRAMEWORK-RENDER-APP-TEMPLATE#'."\n".'The Template File is either: Empty / Does not Exists / Cannot be Read: '.$template_path.$template_file,
				'App Template Render ERROR :: (See Error Log for More Details)'
			);
			die();
			return (string) '';
		} //end if
		//-- add html performance profiling in TPL
		if(defined('SMART_FRAMEWORK_PROFILING_HTML_PERF') AND (SMART_FRAMEWORK_PROFILING_HTML_PERF === true)) {
			if((stripos((string)$tpl, '</head>') !== false) AND (stripos((string)$tpl, '</body>') !== false)) {
				$tpl = (string) str_ireplace('</head>', "\n".SmartMarkersTemplating::render_file_template('lib/core/templates/perf-html-profiler-header.inc.htm', [ 'MODE' => (SmartEnvironment::ifDevMode() !== true) ? 'prod' : 'dev', 'VERSION' => (STRING) SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION ])."\n".'</head>', (string)$tpl); // use caching, as default
				$tpl = (string) str_ireplace('</body>', "\n".SmartMarkersTemplating::render_file_template('lib/core/templates/perf-html-profiler-footer.inc.htm', [ 'MODE' => (SmartEnvironment::ifDevMode() !== true) ? 'prod' : 'dev', 'VERSION' => (STRING) SMART_FRAMEWORK_RELEASE_TAGVERSION.'-'.SMART_FRAMEWORK_RELEASE_VERSION ])."\n".'</body>', (string)$tpl); // use caching, as default
			} //end if
		} //end if
		//-- add debug support in TPL
		if(SmartEnvironment::ifDebug()) {
			if(class_exists('SmartDebugProfiler')) {
				if((stripos((string)$tpl, '</head>') !== false) AND (stripos((string)$tpl, '</body>') !== false)) {
					$tpl = (string) str_ireplace('</head>', "\n".SmartDebugProfiler::js_headers_debug(SmartUtils::get_server_current_script().'?smartframeworkservice=debug')."\n".'</head>', (string)$tpl);
					$tpl = (string) str_ireplace('</body>', "\n".SmartDebugProfiler::div_main_debug()."\n".'</body>', (string)$tpl);
				} //end if
			} //end if
		} //end if
		//--

		//-- render TPL
		return (string) SmartMarkersTemplating::render_main_template(
			(string) $tpl,				// tpl string
			(array)  $arr_data, 		// tpl vars
			(string) $template_path, 	// tpl base path (for sub-templates, if any)
			'no'						// ignore if empty
			// use caching, as default
		);
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get Browser Image and Description by BW-ID
	 * This is compatible with BW-ID supplied by:
	 * 		cli: SmartUtils::get_os_browser_ip()
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function get_imgdesc_by_bw_id(?string $y_bw) : array {
		//--
		switch((string)strtolower((string)$y_bw)) { // {{{SYNC-CLI-BW-ID}}}
			case '@s#':
				$desc = 'Smart.Framework @Robot';
				$pict = 'browser/@smart-robot';
				break;
			case 'bot':
				$desc = 'Robot / Crawler';
				$pict = 'browser/bot';
				break;
			case 'lyx':
				$desc = 'Lynx Text Browser';
				$pict = 'browser/lyx';
				break;
			case 'fox':
				$desc = 'Mozilla Firefox';
				$pict = 'browser/fox';
				break;
			case 'smk':
				$desc = 'Mozilla Seamonkey';
				$pict = 'browser/smk';
				break;
			case 'moz':
				$desc = 'Mozilla (Derivate)';
				$pict = 'browser/moz';
				break;
			case 'crm':
				$desc = 'Google Chromium / Chrome';
				$pict = 'browser/crm';
				break;
			case 'sfr':
				$desc = 'Apple Safari / Webkit';
				$pict = 'browser/sfr';
				break;
			case 'wkt':
				$desc = 'Webkit (Derivate)';
				$pict = 'browser/wkt';
				break;
			case 'iee':
				$desc = 'Microsoft Edge';
				$pict = 'browser/iee';
				break;
			case 'opr':
				$desc = 'Opera';
				$pict = 'browser/opr';
				break;
			case 'eph':
				$desc = 'Epiphany';
				$pict = 'browser/eph';
				break;
			case 'knq':
				$desc = 'Konqueror';
				$pict = 'browser/knq';
				break;
			case 'nsf':
				$desc = 'NetSurf';
				$pict = 'browser/nsf';
				break;
			default:
				$desc = '[Other]: ('.(string)$y_bw.')';
				$pict = 'browser/xxx';
		} //end switch
		//--
		return array(
			'img'  => (string) 'lib/core/img/'.Smart::safe_pathname($pict).'.svg',
			'desc' => (string) $desc.' :: Web Browser'
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get OS Image and Description by OS-ID
	 * This is compatible with OS-ID supplied by:
	 * 		srv: SmartUtils::get_server_os()
	 * 		cli: SmartUtils::get_os_browser_ip()
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function get_imgdesc_by_os_id(?string $y_os_id) : array {
		//--
		switch((string)strtolower((string)$y_os_id)) { // {{{SYNC-SRV-OS-ID}}} ; {{{SYNC-CLI-OS-ID}}}
			//-
			case 'openbsd':
				$desc = 'OpenBSD';
				$pict = 'os/bsd-openbsd';
				break;
			case 'netbsd':
				$desc = 'NetBSD';
				$pict = 'os/bsd-netbsd';
				break;
			case 'freebsd':
				$desc = 'FreeBSD';
				$pict = 'os/bsd-freebsd';
				break;
			case 'dragonfly':
				$desc = 'DragonFly-BSD';
				$pict = 'os/bsd-dragonfly';
				break;
			case 'bsd-os':
			case 'bsd': // cli
				$desc = 'BSD';
				$pict = 'os/bsd-generic';
				break;
			//-
			case 'debian':
				$desc = 'Debian Linux';
				$pict = 'os/linux-debian';
				break;
			case 'ubuntu':
				$desc = 'Ubuntu Linux';
				$pict = 'os/linux-ubuntu';
				break;
			case 'mint':
				$desc = 'Mint Linux';
				$pict = 'os/linux-mint';
				break;
			case 'redhat':
				$desc = 'RedHat Enterprise Linux';
				$pict = 'os/linux-redhat';
				break;
			case 'centos':
				$desc = 'CentOS Linux';
				$pict = 'os/linux-centos';
				break;
			case 'fedora':
				$desc = 'Fedora Linux';
				$pict = 'os/linux-fedora';
				break;
			case 'suse':
				$desc = 'SuSE Enterprise Linux';
				$pict = 'os/linux-suse-e';
				break;
			case 'opensuse':
				$desc = 'OpenSuSE Linux';
				$pict = 'os/linux-suse';
				break;
			case 'alpine':
				$desc = 'Alpine Linux';
				$pict = 'os/linux-alpine';
				break;
			case 'void':
				$desc = 'Void Linux';
				$pict = 'os/linux-void';
				break;
			case 'arch':
				$desc = 'Arch Linux';
				$pict = 'os/linux-arch';
				break;
			case 'manjaro':
				$desc = 'Manjaro Linux';
				$pict = 'os/linux-manjaro';
				break;
			case 'solus':
				$desc = 'Solus Linux';
				$pict = 'os/linux-solus';
				break;
			case 'linux':
			case 'lnx': // cli
				$desc = 'Linux';
				$pict = 'os/linux-generic';
				break;
			//-
			case 'solaris':
			case 'sun': // cli
				$desc = 'OpenSolaris';
				$pict = 'os/unix-solaris';
				break;
			//-
			case 'macosx':
			case 'macos':
			case 'mac': // cli
				$desc = 'Apple MacOS';
				$pict = 'os/mac-os';
				break;
			//-
			case 'windows':
			case 'winnt':
			case 'win': // cli
				$desc = 'Microsoft Windows';
				$pict = 'os/windows-os';
				break;
			//- cli only
			case 'ios':
				$desc = 'Apple iOS Mobile';
				$pict = 'os/mobile/ios';
				break;
			case 'android':
			case 'and':
				$desc = 'Google Android Mobile';
				$pict = 'os/mobile/android';
				break;
			case 'wmo':
				$desc = 'Microsoft Windows Mobile';
				$pict = 'os/mobile/windows-mobile';
				break;
			case 'lxm':
				$desc = 'Linux Mobile';
				$pict = 'os/mobile/linux-mobile';
				break;
			//-
			case SmartUtils::GENERIC_VALUE_OS_BROWSER_IP:
			default:
				$desc = '[UNKNOWN]: ('.$y_os_id.')';
				$pict = 'os/other-os';
			//-
		} //end switch
		//--
		return (array) [
			'img'  => (string) 'lib/core/img/'.Smart::safe_pathname((string)$pict).'.svg',
			'desc' => (string) $desc.' Operating System'
		];
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
