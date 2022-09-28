<?php
// Controller: AuthAdmins/MetaInfo
// Route: admin.php?page=auth-admins.metainfo.stml
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT S EXECUTION
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN');
define('SMART_APP_MODULE_AUTH', true); 	// if set to TRUE because is shared

// [PHP8]

/**
 * Admin Controller
 * @ignore
 */
class SmartAppAdminController extends SmartAbstractAppController {

	// v.20220915


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
			$this->PageViewSetCfg('error', 'Auth Admins Manager Requires Authentication ! ...');
			return 403;
		} //end if
		//--

		//--
		$metainfo = [
			'Client IP: `'.SmartUtils::get_ip_client().'`',
			'Client Proxy: `'.SmartUtils::get_ip_proxyclient().'`',
			'Server Protocol: `'.SmartUtils::get_server_current_protocol().'`',
			'Server Port: `'.SmartUtils::get_server_current_port().'`',
			'Server Domain: `'.SmartUtils::get_server_current_domain_name().'`',
			'Server Base Domain: `'.SmartUtils::get_server_current_basedomain_name().'`',
			'Server Sub Domain: `'.SmartUtils::get_server_current_subdomain_name().'`',
			'Server IP: `'.SmartUtils::get_server_current_ip().'`',
		];
		//--
		$proxymodeenabled = (bool) (SMART_FRAMEWORK_SRVPROXY_ENABLED === true);
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
		$this->PageViewSetVars([
			'title' => 'MetaInfo',
			'main' 	=> '<h1>Web Server MetaInfo</h1>'.Smart::nl_2_br((string)Smart::escape_html((string)implode("\n", (array)$metainfo)))
		]);
		//--


	} //END FUNCTION


} //END CLASS


//end of php code
