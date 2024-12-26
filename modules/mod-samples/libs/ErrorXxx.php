<?php
// [LIB - Smart.Framework / Samples / ErrorXxx - a sample helper for custom 4xx and 5xx status pages]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\ErrorXxx
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\Samples;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Sample Helper to implement custom Error Handlers for HTTP Status Errors (4xx, 5xx)
 *
 * IMPORTANT:
 * implementing custom handlers needs to avoid internal infinite loops (Ex: a 404 page may require a css that is not available and will call inside another 404 ...)
 * To avoid infinite loops, apache2 have a core directive as: LimitInternalRecursion
 * Example: `LimitInternalRecursion 10` is a good value
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20221220
 *
 */
abstract class ErrorXxx extends \SmartAbstractAppController {

	protected $errcode = 501;
	protected $errtext = '???';

	private $errmsg = '';
	private $tpldir = 'modules/mod-samples/libs/views/';

	final public function Run() {

		//--
		$the_errmsg = (string) \Smart::normalize_spaces((string)\trim((string)$this->errmsg));
		//--

		//-- detect page extension
		$uri = (string) \SmartUtils::get_server_current_request_uri();
		$uri = (string) \ltrim($uri, '/');
		$uri = (array)  \explode('?', (string)$uri);
		$uri = (string) \trim((string)(isset($uri[0]) ? $uri[0] : ''));
		if(((string)$uri != '') AND ((string)\substr((string)$uri, -1, 1) != '/')) {
			$ext = (string) \SmartFileSysUtils::extractPathFileExtension((string)$uri);
			$lext = (string) \strtolower((string)$ext);
		} else {
			$ext = (string) $this->RequestVarGet('page', '', 'string');
			$lext = '';
			if(\strpos((string)$ext, '.') !== false) { // if at least module.controller
				$ext = (array) \explode('.', (string)$ext);
				if(\Smart::array_size($ext) == 3) { // module.controller.ext
					$ext = (string) (isset($ext[2]) ? $ext[2] : '');
				} elseif(\Smart::array_size($ext) == 4) { // module.controller.seo.ext
					$ext = (string) (isset($ext[3]) ? $ext[3] : '');
				} else {
					$ext = ''; // n/a
				} //end if else
				$lext = (string) \strtolower((string)$ext);
			} //end if
		} //end if else
		//-- remap some extensions
		if((string)$lext == 'markdown') {
			$lext = 'md';
		} elseif((string)$lext == 'less') {
			$lext = 'css';
		} elseif((string)$lext == 'scss') {
			$lext = 'css';
		} elseif((string)$lext == 'sass') {
			$lext = 'css';
		} elseif((string)$lext == 'jpeg') {
			$lext = 'jpg';
		} elseif((string)$lext == 'jpe') {
			$lext = 'jpg';
		} //end if
		//-- special handler for several well known non-HTML extension types
		switch((string)$lext) {
			case 'jpg':
			case 'gif':
			case 'png':
			case 'webp':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'image/'.$lext);
				$this->PageViewSetCfg('rawdisp', 'inline; filename="'.(int)$this->errcode.'.'.$lext.'"');
				$this->PageViewSetVar('main', (string)\SmartFileSystem::read($this->tpldir.'img/error-xxx.'.$lext));
				return;
				break;
			case 'svg':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'image/svg+xml');
				$this->PageViewSetCfg('rawdisp', 'inline; filename="'.(int)$this->errcode.'.'.$lext.'"');
				$this->PageViewSetVar('main', (string)\SmartFileSystem::read($this->tpldir.'img/error-xxx.'.$lext));
				return;
				break;
			case 'json':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/json');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar('main',
					(string)\Smart::json_encode([
						'status' => 'ERROR',
						'message' => (string) ((int)$this->errcode.' '.$this->errtext),
						'details' => (string) 'JSON / Page Error: '.$the_errmsg
					])
				);
				return;
				break;
			case 'wsdl':
			case 'xml':
			case 'rdf':
			case 'rss':
			case 'atom':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'application/xml');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar('main', '<error'.(int)$this->errcode.'>XML '.\Smart::escape_html($this->errtext.': '.$the_errmsg).'</error'.(int)$this->errcode.'>');
				return;
				break;
			case 'txt':
			case 'log':
			case 'sql':
			case 'md':
			case 'eml':
			case 'ics':
			case 'vcf':
			case 'vcs':
			case 'ldif':
			case 'pem':
			case 'asc':
			case 'sig':
			case 'csv':
			case 'tab':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/plain');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar('main', '-- # ERROR '.(int)$this->errcode.': '.strtoupper((string)$lext).' '.$this->errtext.' # '.$the_errmsg.' --');
				return;
				break;
			case 'js':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'application/javascript');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$the_errmsg = (string) str_replace(['/*', '*/'], ' ', (string)$the_errmsg);
				$this->PageViewSetVar('main', '/* # ERROR '.(int)$this->errcode.': JS '.$this->errtext.' # '.$the_errmsg.' */');
				return;
				break;
			case 'css':
				$this->PageViewResetVars();
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/css');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$the_errmsg = (string) str_replace(['/*', '*/'], ' ', (string)$the_errmsg);
				$this->PageViewSetVar('main', '/* # ERROR '.(int)$this->errcode.': CSS '.$this->errtext.' # '.$the_errmsg.' */');
				return;
				break;
			default:
				// nothing
		} //end if
		//--

		//--
		$this->PageViewSetVars([
			'title'				=> (string) (int)$this->errcode.' '.(string)$this->errtext,
			'main' 				=> (string) \SmartMarkersTemplating::render_file_template(
				$this->tpldir.'error-xxx.mtpl.htm',
				[
					'CRR-URL' 		=> (string) \SmartUtils::get_server_current_url(),
					'STATUS-CODE' 	=> (int) $this->errcode,
					'STATUS-MSG' 	=> (string) $this->errtext
				]
			)
		]);
		//--

	} //END FUNCTION


	final public function outputErrorPage($y_message, $y_html_message) {
		//--
		$this->errmsg = (string) $y_message; // must be before initialize
		//--
		$this->Initialize(); // in this context don't check for exit code here
		$this->Run(); // in this context don't check for exit code here
		$this->ShutDown(); // always ...
		//--
		$cfgs = (array) $this->getRenderCfgs();
		$vars = (array) $this->getRenderVars();
		//--
		if(!\headers_sent()) {
			\SmartFrameworkRuntime::outputHttpHeadersCacheControl();
			if($this->IsRawPage()) {
				\SmartFrameworkRuntime::outputHttpSafeHeader('Content-Type: '.$cfgs['rawmime']);
				\SmartFrameworkRuntime::outputHttpSafeHeader('Content-Disposition: '.$cfgs['rawdisp']);
				return (string) $vars['main'];
			} //end if
		} //end if
		//--
		$template_path = (string) $this->PageViewGetCfg('template-path');
		$template_file = (string) $this->PageViewGetCfg('template-file');
		if(((string)$template_path == '') OR ((string)$template_file == '')) {
			$template_path = (string) \Smart::get_from_config('app.index-template-path');
			$template_file = (string) \Smart::get_from_config('app.index-template-file');
		} //end if
		//--
		if((string)$template_path == '@') {
			$template_path = (string) $this->ControllerGetParam('module-tpl-path');
		} else {
			$template_path = (string) \SmartFileSysUtils::addPathTrailingSlash((string)\SMART_APP_TEMPLATES_DIR.$template_path);
		} //end if
		//--
		if($this->IfDevMode() !== true) { // avoid display details on prod env
			$y_message = '';
			$y_html_message = '';
		} //end if
		//--
		$vars['FOOTER'] = (string) \SmartMarkersTemplating::render_file_template(
			$this->tpldir.'error-xxx-footer.mtpl.htm',
			[
				'CRR-URL' 		=> (string) \SmartUtils::get_server_current_url(),
				'ERR-MESSAGE' 	=> (string) $y_message
			]
		);
		//--
		return (string) \SmartComponents::render_app_template(
			(string) $template_path,
			(string) $template_file,
			(array)  $vars
		);
		//--
	} //END FUNCTION


	private function getRenderVars() {
		//--
		return (array) $this->PageViewGetVars();
		//--
	} //END FUNCTION


	private function getRenderCfgs() {
		//--
		return (array) $this->PageViewGetCfgs();
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
