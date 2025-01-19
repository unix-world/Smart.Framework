<?php
// Controller: AuthAdmins/MetaInfo
// Route: admin.php?page=auth-admins.metainfo.stml
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN');
define('SMART_APP_MODULE_AUTH', true);
define('SMART_APP_MODULE_REALM_AUTH', 'SMART-ADMINS-AREA'); // if set will check the login realm

// [PHP8]

/**
 * Admin Controller
 * @ignore
 */
class SmartAppAdminController extends SmartAbstractAppController {

	// v.20250112


	public function Initialize() {
		//--
		$this->PageViewSetCfg('template-path', '@'); // set template path to this module
		$this->PageViewSetCfg('template-file', 'template.htm');
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() { // (OUTPUTS: HTML)

		//--
		if(SmartAuth::check_login() !== true) {
			$this->PageViewSetCfg('error', 'Auth Admins MetaInfo Requires Authentication ! ...');
			return 403;
		} //end if
		//--

		//--
		$metainfo = [
			'Client MetaInfo: `'.SmartUtils::pretty_print_var((array)SmartUtils::get_os_browser_ip()).'`',
			'Client User-Agent: `'.SmartUtils::get_visitor_useragent().'`',
			'Client Signature: `'.SmartUtils::get_visitor_signature().'`',
			'Client Unique ID (Internal): `'.SmartUtils::get_visitor_tracking_uid().'`', // this can be exposed public, it is a derivation of SMART_APP_VISITOR_COOKIE that can be used where needs a different UID related with SMART_APP_VISITOR_COOKIE
			'Client Visitor ID (Cookie): `'.(defined('SMART_APP_VISITOR_COOKIE') ? SMART_APP_VISITOR_COOKIE : '').'`',
			'',
			'Client IP: `'.SmartUtils::get_ip_client().'`',
			'Client Proxy: `'.SmartUtils::get_ip_proxyclient().'`',
			'Client Request Method: `'.SmartUtils::get_server_current_request_method().'`',
			'',
			'Server Protocol: `'.SmartUtils::get_server_current_protocol().'`',
			'Server Port: `'.SmartUtils::get_server_current_port().'`',
			'Server Domain: `'.SmartUtils::get_server_current_domain_name().'`',
			'Server Base Domain: `'.SmartUtils::get_server_current_basedomain_name().'`',
			'Server Sub Domain: `'.SmartUtils::get_server_current_subdomain_name().'`',
			'Server IP: `'.SmartUtils::get_server_current_ip().'`',
		];
		//--

		//--
		$proxymodeenabled = (bool) (SMART_FRAMEWORK_SRVPROXY_ENABLED === true);
		//--
		$metainfo[] = '';
		$metainfo[] = 'PROXY Mode Enabled: '.'`'.(($proxymodeenabled === true) ? 'true' : 'false').'`';
		if($proxymodeenabled === true) {
			$metainfo[] = 'Proxy Mode detect Client IP: `'.SMART_FRAMEWORK_SRVPROXY_CLIENT_IP.'`';
			$metainfo[] = 'Proxy Mode detect Client Proxy IP: `'.SMART_FRAMEWORK_SRVPROXY_CLIENT_PROXY_IP.'`';
			$metainfo[] = 'Proxy Mode detect Server Protocol: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_PROTO.'`';
			$metainfo[] = 'Proxy Mode detect Server IP: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_IP.'`';
			$metainfo[] = 'Proxy Mode detect Server Domain: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_DOMAIN.'`';
			$metainfo[] = 'Proxy Mode detect Server Port: `'.SMART_FRAMEWORK_SRVPROXY_SERVER_PORT.'`';
		} //end if
		//--

		//--
		$metainfo[] = '';
		$metainfo[] = 'Server Execution Path: `'.SmartUtils::get_server_current_path().'`';
		$metainfo[] = 'Server Full Script: `'.SmartUtils::get_server_current_full_script().'`';
		$metainfo[] = 'Server Script File: `'.SmartUtils::get_server_current_script().'`';
		$metainfo[] = '';
		$metainfo[] = 'Server Request URI: `'.SmartUtils::get_server_current_request_uri().'`';
		$metainfo[] = 'Server Request Query URL (default): `'.SmartUtils::get_server_current_queryurl(false).'`';
		$metainfo[] = 'Server Request Query URL (blank, if empty): `'.SmartUtils::get_server_current_queryurl(true).'`';
		$metainfo[] = 'Server Request Path: `'.SmartUtils::get_server_current_request_path().'`';
		//--
		$metainfo[] = '';
		$metainfo[] = 'Server Request URL (Skip Port if default): `'.SmartUtils::get_server_current_url(true).'`';
		$metainfo[] = 'Server Request URL (Always with Port): `'.SmartUtils::get_server_current_url(false).'`';
		//--

		//--
		$metainfo[] = '';
		$metainfo[] = 'Server Software: `'.SmartUtils::pretty_print_var((array)SmartUtils::get_webserver_version()).'`';
		$metainfo[] = 'Server OS: `'.SmartUtils::get_server_os().'`';
		//--

		//--
		$metainfo[] = '';
		$metainfo[] = 'Auth Data: `'.SmartUtils::pretty_print_var((array)SmartAuth::get_login_data(true)).'`';
		//--

		//--
		$metainfo[] = '';
		$metainfo[] = '#END';
		//--

		//--
		$this->PageViewSetVars([
			'title' => 'MetaInfo',
			'main' 	=> '<h1>Web Server MetaInfo</h1><pre>'.Smart::escape_html((string)implode("\n", (array)$metainfo)).'</pre>'
		]);
		//--


	} //END FUNCTION


} //END CLASS


//end of php code
