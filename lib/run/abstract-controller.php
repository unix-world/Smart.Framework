<?php
// Smart.Framework / Abstract Controller
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartAbstractAppController - Abstract Application Controller, provides the Abstract Definitions to create controllers in modules.
 *
 * <code>
 *
 * // Usage example: Create a new Index Controller (modules/my-module/my-controller.php)
 * // Will be accessible via: index.php?/page/my-module.my-controller (index.php?page=my-module.my-controller)
 * // or you can use short type as just: ?/page/my-module.my-controller (?page=my-module.my-controller)
 *
 * define('SMART_APP_MODULE_AREA', 'INDEX'); // this controller will run ONLY in index.php
 *
 * class SmartAppIndexController extends SmartAbstractAppController { // or it can extend from any of SmartAppAdminController or SmartAppTaskController if define('SMART_APP_MODULE_AREA', 'SHARED');
 *
 *     public function Run() {
 *
 *         $op = $this->RequestVarGet('op', '', 'string'); // get variable 'op' from Request GET/POST
 *
 *         $this->PageViewSetCfg('template-path', 'my-template'); 		// will be using the template in the folder: etc/templates/my-template/
 *         $this->PageViewSetCfg('template-file', 'template-one.htm');	// will be using the template file: template-one.htm (located in: etc/templates/my-template/)
 *         //$this->PageViewSetCfg('template-file', 'template-modal.htm'); // or using the modal template
 *
 *         // the template 'template-one.htm' contains several markers as): 'title', 'left-column', 'main', 'right-column', so we set them as:
 *         $this->PageViewSetVars([
 *             'title' => 'Hello World', // this marker is like <title>[###TITLE|html###]</title>
 *             'left-column' => 'Some content in the left column', // the marker will be put anywhere in the template html as: [###LEFT-COLUMN###]
 *             'main' => '<h1>Some content in the main area</h1>', // the 'main' area must always be defined in a template as: [###MAIN###] ; when no template this variable will be redirected to the main output in the case of RAW pages (see the below example).
 *             'right-column' => 'Some content in the <b>right area</b>. Current Operation is: '.Smart::escape_html($op) // the marker will be put anywhere in the template html as: [###RIGHT-COLUMN###]
 *         ]);
 *
 *         // HINT - Escaping HTML:
 *         // is better to use: Smart::escape_html($var);
 *         // than htmlspecialchars($var, ENT_HTML401 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8', true);
 *         // if using htmlspecialchars($var); with no extra parameters is not safe for unicode environments
 *
 *         // HINT - Escaping JS (Safe exchanging variables between PHP and Javascript in HTML templates):
 *         // when you have to pass a javascript variable, in a marker like <script>my_js_var = '[###JS-VAR|js###]';</script>
 *         // use: Smart::escape_js('a value exchanged between PHP and Javascript in a safe mode');
 *
 *     } //END FUNCTION
 *
 * } //END CLASS
 *
 * //========================================================================================================
 *
 * // Another usage example: Create a new Admin Controller (modules/my-module/my-other-controller.php)
 * // Will be accessible via: admin.php?/page/my-module.my-other-controller (admin.php?page=my-module.my-other-controller)
 *
 * define('SMART_APP_MODULE_AREA', 'ADMIN'); // this controller will run ONLY in admin.php
 *
 * class SmartAppAdminController extends SmartAbstractAppController { // or it can extend from any of SmartAppIndexController or SmartAppTaskController if define('SMART_APP_MODULE_AREA', 'SHARED');
 *
 *     public function Run() {
 *
 *         $this->PageViewSetCfg('rawpage', true); // do a raw output, no templates are loaded (this example does a json output / an image or other non-html content ; can be used also for output of an image: jpg/gif/jpeg with the appropriate content headers)
 *
 *         $this->PageViewSetCfg('rawmime', 'text/javascript'); // set the content (mime) type ; this can also be for this example: 'application/json'
 *         //$this->PageViewSetCfg('rawdisp', 'inline'); // (optional, set the content disposition ; for pdf mime type you maybe would set this to 'attachment' instead on 'inline'
 *
 *         $this->PageViewSetVar(
 *             'main' => Smart::json_encode('Hello World, this is my json string') // this case have no marker template, but there is always a 'main' output variable even when no template is used
 *         );
 *
 *     } //END FUNCTION
 *
 *     public function ShutDown() {
 *
 *         // This function is OPTIONAL in controllers and must be used only when needed as a destructor for any of: SmartAppTaskController, SmartAppAdminController or SmartAppIndexController.
 *         // NOTICE: The PHP class destructor __destruct() have some bugs, is not always 100% safe.
 *         // See the PHP Bug #31570 for example (which is very old and not yet fixed ...).
 *         // thus, use always ShutDown() instead of __destruct() in all controllers when you need a destructor
 *
 *     } //END FUNCTION
 *
 * } //END CLASS
 *
 * //========================================================================================================
 *
 * // Another usage example: Create a new Task Controller (modules/my-module/my-task-controller.php)
 * // Will be accessible via: task.php?/page/my-module.my-task-controller (task.php?page=my-module.my-task-controller)
 * // This example shows a special example with direct output enabled ; by default Smart.Framework disabled the direct output in controllers ... it is very inefficient, but it can be enabled in any of index / admin / task just like this example ...
 *
 * define('SMART_APP_MODULE_AREA', 'TASK'); // this controller will run ONLY in task.php
 * define('SMART_APP_MODULE_DIRECT_OUTPUT', true); // enable direct output
 *
 * class SmartAppTaskController extends SmartAbstractAppController { // or it can extend from any of SmartAppIndexController or SmartAppAdminController if define('SMART_APP_MODULE_AREA', 'SHARED');
 *
 *     public function Run() {
 *
 *         echo 'Hello world, I am a task';
 *         $this->InstantFlush();
 *
 *     } //END FUNCTION
 *
 * } //END CLASS
 *
 * </code>
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints		needs to be extended as: SmartAppIndexController (as a controller of index.php) or SmartAppAdminController (as a controller of admin.php) or SmartAppTaskController (as a controller of task.php)
 *
 * @access 		PUBLIC
 * @depends 	-
 * @version 	v.20250805
 * @package 	development:Application
 *
 */
abstract class SmartAbstractAppController { // {{{SYNC-ARRAY-MAKE-KEYS-LOWER}}}

	// -> ABSTRACT

	// It must NOT contain STATIC functions / Properties to avoid late state binding (self:: vs static::)

	//--
	private $directoutput;
	//--
	private $appenv;
	private $releasehash;
	private $modulearea;
	private $modulepath;
	private $modulename;
	private $module;
	private $action;
	private $controller;
	private $urlproto;
	private $urldomain;
	private $urlbasedomain;
	private $urlport;
	private $urlscript;
	private $urlpath;
	private $urladdr;
	private $urlpage;
	private $urlquery;
	private $urluri;
	private $uripath;
	private $lang;
	private $charset;
	private $timezone;
	//--
	private $pageheaders;
	private $pagesettings; 					// will allow keys just from $availsettings
	private $pageview; 						// will allow any key since they are templating markers
	private $availsettings = [ 				// list of allowed values for page settings ; used to validate the pagesettings keys by a fixed list: look in middlewares to see complete list
		'error', 'errhtml', 'redirect-url', // 		error message for return non 2xx/3xx codes ; optional error HTML message for return non 2xx/3xx codes ; redirect url for return 3xx codes
		'expires', 'modified', 'c-control',	// 		expires (int) in seconds from now ; last modification of the contents in seconds (int) timestamp: > 0 <= now ; cache control (private | public)
		'template-path', 'template-file',	// 		template path (@ for self module path or a relative path) ; template filename (ex: template.htm)
		'rawpage', 'rawmime', 'rawdisp',	// 		raw page (yes/no) ; raw mime (any valid mime type, ex: image/png) ; raw disposition (ex: inline / attachment / attachment; filename="somefile.pdf")
		'download-packet', 'download-key', 	// 		download packet ; download key
		'status-code'						// 		HTTP Status Code
	];
	//--


	//=====
	/**
	 * Class constructor.
	 * This is used to construct a middleware controller service
	 *
	 * @param STRING $y_module_path 	:: The Relative Path (followed by a trailing slash) to the current module containing the current Controller to be served by the Middleware Service ; Ex: 'modules/mod-mytest/'
	 * @param STRING $y_controller 		:: The Controller to serve the action for the Middleware Service ; Ex: 'mytest.some-controller', referring the file 'modules/mod-mytest/some-controller.php' as the real controller to be served
	 * @param STRING $y_url_page 		:: The URL Parameter 'page' as it comes from URL ; Ex: 'mytest.some-controller' | 'some-controller' (if 'mytest' is set as default module) | 'mytest.some-controller.html' or anything that can refere by URL to the current controller when using Apache Rewrite
	 * @param STRING $y_hardcoded_area 	:: *OPTIONAL* If this is provided will supply a hard-coded Area for the Middleware Service, otherwise is detected from current script.php
	 *
	 */
	final public function __construct(?string $y_module_path, ?string $y_controller, ?string $y_url_page, ?string $y_hardcoded_area='') {
		//--
		$y_module_path 		= (string) $y_module_path;
		$y_controller 		= (string) $y_controller;
		$y_url_page 		= (string) $y_url_page;
		$y_hardcoded_area 	= (string) trim((string)$y_hardcoded_area);
		//--
		$param_url_script 	= (string) SmartUtils::get_server_current_script();
		$param_area 		= (string) trim((string)Smart::base_name((string)$param_url_script, '.php'));
		$param_url_path 	= (string) SmartUtils::get_server_current_path();
		$param_url_addr 	= (string) SmartUtils::get_server_current_url();
		//--
		if(defined('SMART_APP_MODULE_DIRECT_OUTPUT') AND (SMART_APP_MODULE_DIRECT_OUTPUT === true)) {
			$this->directoutput = true;
		} else {
			$this->directoutput = false;
		} //end if else
		//--
		if(((string)$param_area == '') OR (!preg_match('/^[a-z0-9_\-]+$/', (string)$param_area))) {
			Smart::raise_error(
				__METHOD__.'() :: Empty or Invalid Parameter: Area: '.$param_area
			);
			return;
		} //end if
		if((string)$y_hardcoded_area != '') {
			if((string)$y_hardcoded_area !== (string)$param_area) {
				Smart::raise_error(
					__METHOD__.'() :: Invalid Parameter: Area: '.$param_area.' instead of hard-coded: '.$y_hardcoded_area
				);
				return;
			} //end if
		} //end if
		//--
		if((string)$y_module_path == '') {
			Smart::raise_error(
				__METHOD__.'() :: Empty Parameter: Module Path'
			);
			return;
		} //end if
		if(strpos((string)$y_module_path, 'modules/') !== 0) {
			Smart::raise_error(
				__METHOD__.'() :: Invalid Parameter: Module Path: '.$y_module_path
			);
			return;
		} //end if
		if(!SmartFileSysUtils::checkIfSafePath((string)$y_module_path)) {
			Smart::raise_error(
				__METHOD__.'() :: Unsafe Parameter: Module Path: '.$y_module_path
			);
			return;
		} //end if
		if(!SmartFileSystem::is_type_dir((string)$y_module_path)) {
			Smart::raise_error(
				__METHOD__.'() :: Wrong Parameter: Module Path does not exists: '.$y_module_path
			);
			return;
		} //end if
		//--
		if((string)$param_url_script != (string)$param_area.'.php') {
			Smart::raise_error(
				__METHOD__.'() :: Invalid Parameter: URL-Script: '.$param_url_script
			);
			return;
		} //end switch
		if((string)$param_url_path == '') {
			Smart::raise_error(
				__METHOD__.'() :: Empty Parameter: URL-Path: '.$param_url_path
			);
			return;
		} //end switch
		if((string)$param_url_addr == '') {
			Smart::raise_error(
				__METHOD__.'() :: Empty Parameter: URL-Addr: '.$param_url_addr
			);
			return;
		} //end switch
		if((string)$y_url_page == '') {
			Smart::raise_error(
				__METHOD__.'() :: Empty Parameter: URL-Page: '.$y_url_page
			);
			return;
		} //end switch
		if((string)$y_controller == '') {
			Smart::raise_error(
				__METHOD__.'() :: Empty Parameter: Controller: '.$y_controller
			);
			return;
		} //end switch
		//--
		$pretty_url_query = (string) SmartUtils::get_server_current_queryurl(true); // 2nd param set to TRUE ; if empty query url do not return just '?' ...
		//--
		$ctrl_arr = (array) explode('.', (string)$y_controller);
		//--
		$this->appenv 			= (string) (SmartEnvironment::ifDevMode() !== true) ? 'prod' : 'dev'; 				// app environment: dev | prod :: {{{SYNC-APP-ENV-SETT}}}
		$this->releasehash 		= (string) SmartUtils::get_app_release_hash(); 										// the release hash based on app framework version, framework release and modules version
		$this->modulearea 		= (string) $param_area; 															// index | admin | task
		$this->modulepath 		= (string) $y_module_path; 															// modules/mod-something/
		$this->modulename 		= (string) Smart::base_name($y_module_path); 										// mod-something
		$this->module 			= (string) ($ctrl_arr[0] ?? ''); 													// something (module name part of the controller)
		$this->action 			= (string) ($ctrl_arr[1] ?? ''); 													// someaction (controller name part of the controller)
		$this->controller 		= (string) $y_controller; 															// something.someaction (the controller)
		$this->urlproto 		= (string) SmartUtils::get_server_current_protocol(); 								// http:// | https://
		$this->urlbasedomain 	= (string) SmartUtils::get_server_current_basedomain_name();						// 127.0.0.1|localhost|dom.ext
		$this->urldomain 		= (string) SmartUtils::get_server_current_domain_name(); 							// 127.0.0.1|localhost|sdom.dom.ext
		$this->urlport 			= (string) SmartUtils::get_server_current_port(); 									// 80 | 443 | ...
		$this->urlscript 		= (string) $param_url_script; 														// index.php | admin.php | task.php
		$this->urlpath 			= (string) $param_url_path; 														// /frameworks/smart-framework/
		$this->urladdr 			= (string) $param_url_addr; 														// http(s)://127.0.0.1|localhost:8008/frameworks/smart-framework/
		$this->urlpage 			= (string) $y_url_page; 															// this may vary depending on semantic URL rule but can be equal with: something.someaction | someaction | something
		$this->urlquery 		= (string) $pretty_url_query; 														// the filtered, safe URL query
		$this->urluri 			= (string) SmartUtils::get_server_current_request_uri(); 							// the REQUEST_URI
		$this->uripath 			= (string) SmartUtils::get_server_current_request_path(); 							// the PATH_INFO
		$this->lang 			= (string) SmartTextTranslations::getLanguage(); 									// current language (ex: en)
		$this->charset 			= (string) (defined('SMART_FRAMEWORK_CHARSET') ? SMART_FRAMEWORK_CHARSET : ''); 	// current charset (ex: UTF-8)
		$this->timezone 		= (string) (defined('SMART_FRAMEWORK_TIMEZONE') ? SMART_FRAMEWORK_TIMEZONE : ''); 	// current timezone (ex: UTC)
		//--
		$this->pageheaders 		= array(); 																			// Page Current Headers
		$this->pageview 		= array(); 																			// Page Current View
		//--
		$this->availsettings 	= (array) $this->availsettings; 													// Page Available Settings
		$this->pagesettings 	= (array) Smart::array_init_keys([], (array)$this->availsettings); 					// Page Current Settings ; init keys is fix for PHP8
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Class Destructor.
	 * This is used to destruct a middleware controller service
	 *
	 * NOTICE: Use the ShutDown() function as custom destructor, it will be called after Run() safely prior to destruct this class.
	 *
	 * The class destructors are not safe in controller instances.
	 * See the comments from ShutDown() function in this class !
	 */
	final public function __destruct() {
		// This is not safe so we define it as final to avoid re-define later, see function ShutDown() below !!!
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if Raw Page in ON
	 *
	 * @return 	BOOLEAN					:: TRUE if 'rawpage' is 'yes' or true ; FALSE otherwise
	 */
	final public function IsRawPage() : bool {
		//--
		$is_raw = false;
		if((string)strtolower((string)$this->pagesettings['rawpage']) == 'yes') {
			$is_raw = true;
		} //end if
		//--
		return (bool) $is_raw;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if Development Environment is On
	 *
	 * @return 	BOOLEAN					:: TRUE if 'dev' environment is ON, FALSE if not (FALSE if 'prod')
	 */
	final public function IfDevMode() : bool {
		//--
		return (bool) SmartEnvironment::ifDevMode();
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if Debug is On
	 *
	 * @return 	BOOLEAN					:: TRUE if DEBUG is ON, FALSE if not
	 */
	final public function IfDebug() : bool {
		//--
		return (bool) SmartEnvironment::ifDebug();
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Force Debug View Over Raw Output or DownloadPacket Output Pages
	 * The output of this pages is not displayed by default in the Debug Profiler because is not a common HTML Page
	 * To debug this type of pages this function should be called (ONLY IN DEBUG MODE) at the end (or before return) of the controller Run() method
	 * This is not available for direct Direct Output pages (which can be integrated in Debug Profiler only if called as sub-request in an Ajax call from any HTML page)
	 *
	 * @param BOOLEAN $show_output 		:: *Optional* ; Default is FALSE ; If set to TRUE will try to output the contents as HTML ; BEWARE, if the content is binary it should not ...
	 * @return 	BOOLEAN					:: TRUE if Debug is ON, FALSE if not
	 */
	final public function forceRawDebug(bool $show_output=false) : bool {
		//--
		if($this->IfDebug() !== true) {
			Smart::log_warning('ERROR: Page Controller: '.$this->controller.' # '.__FUNCTION__.'(): Method should be called only when Debug Mode is ON ...');
			return false;
		} //end if
		//--
		if($this->directoutput === true) {
			$this->PageViewSetErrorStatus(500, 'ERROR: Page Controller: '.$this->controller.' # '.__FUNCTION__.'(): Method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(((string)strtolower((string)$this->pagesettings['rawpage']) !== 'yes') AND ((string)$this->pagesettings['download-packet'] == '')) {
			$this->PageViewSetErrorStatus(500, 'ERROR: Page Controller: '.$this->controller.' # '.__FUNCTION__.'(): Method is available only for Raw or DownloadPacket Output Mode ...');
			return false;
		} //end if
		//--
		$info = [
			'RawPage' 			=> 'YES',
			'MimeType' 			=> (string) $this->pagesettings['rawmime'],
			'MimeDisposition' 	=> (string) $this->pagesettings['rawdisp']
		];
		//--
		$this->pagesettings['rawpage'] = ''; // clear
		$this->pagesettings['rawmime'] = ''; // clear
		$this->pagesettings['rawdisp'] = ''; // clear
		//--
		$part = '[Output will not be displayed]';
		if((string)$this->pagesettings['download-packet'] != '') {
			$size = (int) strlen((string)$this->pagesettings['download-packet']);
			$type = 'Download Packet Output';
			if($show_output === true) {
				$part = (string) substr((string)$this->pagesettings['download-packet'], 0, 65535);
				if($size > 65535) {
					$part .= '...';
				} //end if
			} //end if
		} else {
			$size = (int) strlen((string)($this->pageview['main'] ?? null));
			$type = 'Raw Output';
			if($show_output === true) {
				$part = (string) substr((string)($this->pageview['main'] ?? null), 0, 65535);
				if($size > 65535) {
					$part .= '...';
				} //end if
			} //end if
		} //end if else
		$output = '<h1>[DEBUG: '.$type.' ; OutputSize = '.SmartUtils::pretty_print_bytes((int)$size, 2).']</h1>'.'<br><pre>'.Smart::escape_html((string)SmartUtils::pretty_print_var($info)).'</pre><hr><pre>'.Smart::escape_html((string)$part).'</pre>';
		//--
		$this->pagesettings['download-packet'] = '';
		$this->pageview['main'] = '';
		//--
		$this->pageview['main'] = (string) $output;
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set Custom Debug Data
	 *
	 * If Debug is turned on, this area of Debug messages will be displayed in Modules section
	 *
	 * @param 	STRING 	$title 			:: A title for the debug message.
	 * @param 	MIXED 	$debug_msg 		:: The data for the debug message. Ex: STRING / ARRAY
	 *
	 * @return 	BOOLEAN					:: TRUE if successful, FALSE if not
	 */
	final public function SetDebugData(?string $title, $debug_msg) : bool {
		//--
		if(!$this->IfDebug()) {
			Smart::log_notice('Page Controller: '.$this->controller.' # NOTICE: Modules/SetDebugData must be set in a Controller only if Modules/IfDebug() is TRUE ... else will slow down the execution. Consider to Add SetDebugData() in a context as if($this->IfDebug()){ $this->SetDebugData(\'Debug title\', \'A debug message ...\'); } ...');
			return false;
		} //end if
		//--
		if(is_object($debug_msg)) {
			$debug_msg = (string) print_r($debug_msg,1);
		} else {
			$debug_msg = (string) SmartUtils::pretty_print_var($debug_msg);
		} //end if
		//--
		SmartEnvironment::setDebugMsg('modules', (string)$this->modulename, [
			'title' => (string) $title,
			'data' => (string) $debug_msg
		]);
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the value for a Controller parameter
	 *
	 * @param 	ENUM 		$param 		:: The selected parameter
	 * The valid param values are:
	 * 		app-env 					:: 		ex: dev | prod ; based on: SmartEnvironment::ifDevMode()
	 * 		app-namespace 				:: 		ex: smartframework.default (the app namespace as defined in etc/init.php)
	 * 		app-domain 					:: 		ex: 127.0.0.1|localhost|sdom.dom.ext|dom.ext (the domain set in configs, that may differ by area: $configs['app']['index-domain'] | $configs['app']['admin-domain'])
	 * 		release-hash 				:: 		ex: 29bp3w (the release hash based on app framework version, framework release and modules version)
	 * 		module-area 				:: 		ex: index / admin / task
	 * 		module-name 				:: 		ex: mod-samples
	 * 		module-path 				:: 		ex: modules/mod-samples/
	 * 		module-view-path 			:: 		ex: modules/mod-samples/views/
	 * 		module-model-path 			:: 		ex: modules/mod-samples/models/
	 * 		module-lib-path 			:: 		ex: modules/mod-samples/libs/
	 * 		module-tpl-path 			:: 		ex: modules/mod-samples/templates/
	 * 		module-plugins-path 		:: 		ex: modules/mod-samples/plugins/
	 * 		module-translations-path 	:: 		ex: modules/mod-samples/translations/
	 * 		module 						:: 		ex: samples (1st part from controller, before .)
	 * 		action 						:: 		ex: test (2nd part from controller, after .)
	 * 		controller 					:: 		ex: samples.test
	 * 		url-proto 					:: 		ex: http | https (the current server protocol)
	 * 		url-proto-addr 				:: 		ex: http:// | https:// (the current server protocol address)
	 * 		url-basedomain 				:: 		ex: 127.0.0.1|localhost|dom.ext (the curent server base domain, or IP)
	 * 		url-domain 					:: 		ex: 127.0.0.1|localhost|sdom.dom.ext (the curent server domain, or IP)
	 * 		url-port 					:: 		ex: 80 | 443 | 8080 ... (the current server port)
	 * 		url-port-addr 				:: 		ex: '' | ''  | ':8080' ... (the current server port address ; empty for port 80 and 443 ; for the rest of ports will be :portnumber)
	 * 		url-script 					:: 		ex: index.php | admin.php | task.php
	 * 		url-path 					:: 		ex: /sites/smart-framework/
	 * 		url-addr 					:: 		ex: http(s)://127.0.0.1|localhost/sites/smart-framework/
	 * 		url-page 					:: 		ex: samples.test | test  (if samples is the default module) ; this is returning the URL page variable as is in the URL (it can be the same as 'controller' or if rewrite is used inside framework can vary
	 * 		url-query 					:: 		ex: ?page=test&ofs=10
	 * 		url-uri 					:: 		ex: /sites/smart-framework/index|admin.php{/some/path/}?page=test&ofs=10
	 * 		uri-path 					:: 		ex: {/some/path/}
	 *		lang 						:: 		ex: en
	 *		charset 					:: 		ex: UTF-8
	 * 		timezone 					:: 		ex: UTC
	 *
	 * @return 	STRING					:: The value for the selected parameter
	 */
	final public function ControllerGetParam(?string $param) : string {
		//--
		$param = (string) strtolower((string)$param);
		//--
		$out = '';
		//--
		switch((string)$param) {
			case 'app-env':
				$out = $this->appenv;
				break;
			case 'app-domain':
				$out = $this->ConfigParamGet('app.'.$this->modulearea.'-domain', 'string');
				break;
			case 'app-namespace':
				$out = SMART_SOFTWARE_NAMESPACE;
				break;
			case 'app-realm':
				$out = (($this->modulearea === 'task') ? 'TSK' : (($this->modulearea === 'admin') ? 'ADM' : 'IDX'));
				break;
			case 'release-hash':
				$out = $this->releasehash;
				break;
			case 'module-area':
				$out = $this->modulearea;
				break;
			case 'module-name':
				$out = $this->modulename;
				break;
			case 'module-path':
				$out = $this->modulepath;
				break;
			case 'module-view-path':
				$out = $this->modulepath.'views/';
				break;
			case 'module-model-path':
				$out = $this->modulepath.'models/';
				break;
			case 'module-lib-path':
				$out = $this->modulepath.'libs/';
				break;
			case 'module-tpl-path':
				$out = $this->modulepath.'templates/';
				break;
			case 'module-plugins-path':
				$out = $this->modulepath.'plugins/';
				break;
			case 'module-translations-path':
				$out = $this->modulepath.'translations/';
				break;
			case 'module':
				$out = $this->module;
				break;
			case 'action':
				$out = $this->action;
				break;
			case 'controller':
				$out = $this->controller;
				break;
			case 'url-proto':
				$out = ((string)$this->urlproto == 'https://') ? 'https' : 'http';
				break;
			case 'url-proto-addr':
				$out = $this->urlproto;
				break;
			case 'url-basedomain':
				$out = $this->urlbasedomain;
				break;
			case 'url-domain':
				$out = $this->urldomain;
				break;
			case 'url-port':
				$out = $this->urlport;
				break;
			case 'url-port-addr':
				$out = ((($this->urlport == 80) || ($this->urlport == 443)) ? '' : ':'.$this->urlport);
				break;
			case 'url-script':
				$out = $this->urlscript;
				break;
			case 'url-path':
				$out = $this->urlpath;
				break;
			case 'url-addr':
				$out = $this->urladdr;
				break;
			case 'url-page':
				$out = $this->urlpage;
				break;
			case 'url-query':
				$out = $this->urlquery;
				break;
			case 'url-uri':
				$out = $this->urluri;
				break;
			case 'uri-path':
				$out = $this->uripath;
				break;
			case 'cookie-default-expire':
				$out = SmartUtils::cookie_default_expire();
				break;
			case 'cookie-default-domain':
				$out = SmartUtils::cookie_default_domain();
				break;
			case 'cookie-default-samesite-policy':
				$out = SmartUtils::cookie_default_samesite_policy();
				break;
			case 'lang':
				$out = $this->lang;
				break;
			case 'charset':
				$out = $this->charset;
				break;
			case 'timezone':
				$out = $this->timezone;
				break;
			default:
				Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / ControllerGetParam: Invalid Parameter: '.$param);
		} //end switch
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the value for a Config parameter from the app $configs array
	 *
	 * @param 	ENUM 		$param 		:: The selected configuration parameter ; Examples: 'app.info-url' will get value from $configs['app']['info-url'] ; 'regional.decimal-separator' will get the value (string) from $configs['regional']['decimal-separator'] ; 'regional' will get the value (array) from $configs['regional']
	 * @param 	ENUM 		$type 			:: The type to pre-format the value: 'array', 'string', 'boolean', 'integer', 'numeric' OR '' to leave the value as is (raw)
	 *
	 * @return 	MIXED					:: The value for the selected parameter. If the Config parameter does not exists, will return an empty string
	 */
	final public function ConfigParamGet(?string $param, ?string $type='') {
		//--
		return Smart::get_from_config((string)$param, (string)$type); // mixed
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the HTTP Request Method (REQUEST_METHOD) in a controller
	 *
	 * @return 	STRING					:: The value of the REQUEST_METHOD HTTP Variable (from server-side)
	 */
	final public function RequestMethodGet() : string {
		//--
		return (string) SmartUtils::get_server_current_request_method(); // string
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the Path Request Variable (PATH_INFO) in a controller
	 *
	 * @return 	STRING					:: The value of the PATH_INFO Request if Set or Empty String
	 */
	final public function RequestPathGet() : string {
		//--
		return (string) SmartFrameworkRegistry::getRequestPath(); // string
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get all the Request Variables from (GET/POST) in a controller
	 *
	 * @return 	ARRAY					:: Associative Array of Request Variables
	 */
	final public function RequestVarsGet() : array {
		//--
		return (array) SmartFrameworkRegistry::getRequestVars(); // array
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get a Request Variable (GET/POST) in a controller
	 *
	 * @param 	STRING 		$key		:: The name (key) of the GET or POST variable (if the variable is set in both GET and POST, the GPC as set in PHP.INI sequence will overwrite the GET with POST, thus the POST value will be get).
	 * @param	MIXED		$defval		:: The default value (if a type is set must be the same type) of that variable in the case was not set in the Request (GET/POST). By default it is set to null.
	 * @param	ENUM		$type		:: The type of the variable ; Default is '' (no enforcing). This can be used to enforce a type for the variable as: ['enum', 'list', 'of', 'allowed', 'values'], 'array', 'string', 'boolean', 'integer', 'integer+', 'integer-', 'decimal1', 'decimal2', 'decimal3', 'decimal4', 'numeric'.
	 *
	 * @return 	MIXED					:: The value of the choosen Request (GET/POST) variable
	 */
	final public function RequestVarGet(?string $key, $defval=null, $type='') { // {{{SYNC-REQUEST-DEF-PARAMS}}}
		//--
		return SmartFrameworkRegistry::getRequestVar((string)$key, $defval, $type); // mixed
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Return TRUE if the Request was made via AJAX
	 *
	 * @return 	BOOLEAN					:: TRUE if HTTP_X_REQUESTED_WITH is xmlhttprequest, FALSE otherwise
	 */
	final public function IsAjaxRequest() : bool {
		//--
		return (bool) SmartUtils::is_ajax_request();
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get a Cookie Variable (COOKIES) in a controller
	 *
	 * @param 	STRING 		$name		:: The cookie name from COOKIES variable.
	 *
	 * @return 	MIXED					:: The value of the choosen Cookie variable or null if not set
	 */
	final public function CookieVarGet(?string $name) {
		//--
		return SmartUtils::get_cookie((string)$name); // mixed: null / string
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a Cookie Variable (COOKIES) in a controller
	 *
	 * @param 	STRING 		$name		:: The cookie name
	 * @param 	STRING 		$data		:: The cookie data
	 * @param 	STRING 		$expire		:: The cookie expire time in seconds since now (zero for session cookies) ; default is zero to set as session cookie (expires with browser session, will be unset after browser is closed)
	 * @param 	STRING 		$path		:: The cookie path ; default is /
	 * @param 	STRING 		$domain		:: The cookie domain ; default is @ (will get as it is set in SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN if defined or '') ; can be explicit set
	 * @param 	ENUM 		$samesite 	:: The cookie SameSite policy ; default is @ (will get as it is set in SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE if defined or '') ; valid values: '', 'Lax', 'Strict', 'None' ; if '' will use the old behaviour ; if 'None' will enforce $secure=true as the new browsers are requiring and will work only over https secure connections
	 * @param 	BOOL 		$secure 	:: The cookie secure policy ; if set to TRUE will send cookies only via https secure connections ; default is FALSE ; if the SameSite is set to 'None' this parameter is enforced to be TRUE
	 * @param 	BOOL 		$httponly 	:: The cookie httponly policy ; if set to TRUE this cookies will not be available to Javascript (or any other client-side access) but only to server-side scripts
	 *
	 * @return 	BOOLEAN					:: TRUE if Set, FALSE if Not
	 */
	final public function CookieVarSet(?string $name, ?string $data, ?int $expire=0, ?string $path='/', ?string $domain='@', ?string $samesite='@', bool $secure=false, bool $httponly=false) : bool {
		//--
		return (bool) SmartUtils::set_cookie((string)$name, (string)$data, (int)$expire, (string)$path, (string)$domain, (string)$samesite, (bool)$secure, (bool)$httponly);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Unset a Cookie Variable (COOKIES) in a controller
	 *
	 * @param 	STRING 		$name		:: The cookie name
	 * @param 	STRING 		$path		:: The cookie path ; default is /
	 * @param 	STRING 		$domain		:: The cookie domain ; default is @ (will get as it is set in SMART_FRAMEWORK_COOKIES_DEFAULT_DOMAIN if defined or '') ; can be explicit set
	 * @param 	ENUM 		$samesite 	:: The cookie SameSite policy ; default is @ (will get as it is set in SMART_FRAMEWORK_COOKIES_DEFAULT_SAMESITE if defined or '') ; valid values: '', 'Lax', 'Strict', 'None' ; if '' will use the old behaviour ; if 'None' will enforce $secure=true as the new browsers are requiring and will work only over https secure connections
	 * @param 	BOOL 		$secure 	:: The cookie secure policy ; if set to TRUE will send cookies only via https secure connections ; default is FALSE ; if the SameSite is set to 'None' this parameter is enforced to be TRUE
	 * @param 	BOOL 		$httponly 	:: The cookie httponly policy ; if set to TRUE this cookies will not be available to Javascript (or any other client-side access) but only to server-side scripts
	 *
	 * @return 	BOOLEAN					:: TRUE if Set, FALSE if Not
	 */
	final public function CookieVarUnset(?string $name, ?string $path='/', ?string $domain='@', ?string $samesite='@', bool $secure=false, bool $httponly=false) : bool {
		//--
		return (bool) SmartUtils::unset_cookie((string)$name, (string)$path, (string)$domain, (string)$samesite, (bool)$secure, (bool)$httponly);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get all the current controller PageView Data: (Raw) Headers, Cfgs and Vars
	 * The general purpose of this function is to get all Page Data at once to export into persistent cache.
	 *
	 * @return 	ARRAY					:: an associative array as: [ heads => PageViewGetRawHeaders() ; cfgs => PageViewGetCfgs() ; vars => PageViewGetVars() ]
	 */
	final public function PageViewGetData() : array {
		//--
		return (array) [
			'heads' 	=> (array) $this->PageViewGetRawHeaders(),
			'cfgs' 		=> (array) $this->PageViewGetCfgs(),
			'vars' 		=> (array) $this->PageViewGetVars()
		];
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set all the current controller PageView Data: (Raw) Headers, Cfgs and Vars
	 * The general purpose of this function is to set all Page Data at once when imported from persistent cache.
	 *
	 * @param 	ARRAY 		$data		:: an associative array as: [ heads => PageViewGetRawHeaders() ; cfgs => PageViewGetCfgs() ; vars => PageViewGetVars() ]
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetData($data) : bool { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO WORK ALSO NON-ARRAY VARS !!!
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(!is_array($data)) {
			return false; // $data must be array
		} //end if
		//--
		$data = (array) array_change_key_case((array)$data, CASE_LOWER); // make all keys lower
		//--
		if(!is_array($data['heads'])) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Invalid Heads');
			return false;
		} //end if
		if(!is_array($data['cfgs'])) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Invalid Cfgs');
			return false;
		} //end if
		if(!is_array($data['vars'])) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Invalid Vars');
			return false;
		} //end if
		//--
		if($this->PageViewSetRawHeaders($data['heads']) !== true) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Failed to Set Heads');
			return false;
		} //end if
		if($this->PageViewSetCfgs($data['cfgs']) !== true) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Failed to Set Cfgs');
			return false;
		} //end if
		if($this->PageViewSetVars($data['vars']) !== true) {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetData: Failed to Set Vars');
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get all the current controller PageView Headers (Raw) that will be mapped to Raw HTTP Header(s)
	 *
	 * @return 	ARRAY					:: an associative array with all controller Page View (Raw) Headers currently set
	 */
	final public function PageViewGetRawHeaders() : array {
		//--
		return (array) $this->pageheaders;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a list with multiple values for RawHeaders into the current controller as PageView Headers (Raw) that will be mapped to Raw HTTP Header(s)
	 *
	 * @param 	ARRAY 		$entries	:: an associative array to be set with elements for each raw header entry [ 'Header Key 1' => 'Header Entry One', 'Header Key 2' => 'Header Entry Two', ..., 'Header Key n' => 'Header Entry N' ]
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetRawHeaders($entries) : bool { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO WORK ALSO NON-ARRAY VARS !!!
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(!is_array($entries)) {
			return false; // $entries must be array
		} //end if
		//--
		$ok = true;
		//--
		foreach($entries as $key => $val) {
			$test = $this->PageViewSetRawHeader($key, $val);
			if($test !== true) {
				$ok = false;
			} //end if
		} //end foreach
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a single value for settings into the current controller as PageView Headers (Raw) that will be mapped to Raw HTTP Header(s)
	 *
	 * @param 	STRING 		$param		:: the header key 		(Ex: 'X-XSS-Protection')
	 * @param 	STRING 		$value		:: the header value 	(Ex: '1; mode=block')
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetRawHeader(?string $param, ?string $value) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		if(!Smart::is_nscalar($value)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Value: '.$param);
			return false;
		} //end if
		//--
		$param = (string) Smart::normalize_spaces((string)$param); // safety
		$param = (string) preg_replace('/[^0-9a-zA-Z\-]/', '', (string)$param); // allow just A-Z a-z 0-9 -
		$param = (string) str_replace(' ', '', (string)$param); // remove any remaining spaces ... (to be sure)
		$param = (string) trim((string)$param); // trim
		if((string)$param == '') {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetRawHeader: Invalid Parameter: '.$param);
		} //end if
		//--
		$value = (string) Smart::normalize_spaces((string)$value); // safety
		$value = (string) trim((string)$value); // trim
		//--
		if((string)$param == '') {
			Smart::log_notice('Page Controller: '.$this->controller.' # SmartAbstractAppController / PageViewSetRawHeader: Empty Key / Parameter');
			return false;
		} //end if
		//--
		$this->pageheaders[(string)$param] = (string) $value; // IMPORTANT: Value can be empty (Ex: 'Expect:')
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Reset all variables for the current controller into PageView Headers (Raw) that will be mapped to Raw HTTP Header(s)
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewResetRawHeaders() : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		$this->pageheaders = array();
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get a specific value of the current controller from PageView Settings (Cfgs)
	 *
	 * @param 	STRING 		$param		:: the parameter to be get
	 *
	 * @return 	STRING					:: The value currently set in CFGs if any or an empty string
	 */
	final public function PageViewGetCfg($param) : string {
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return '';
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		if(!in_array((string)$param, (array)$this->availsettings)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter: '.$param);
			return '';
		} //end if
		//--
		return (string) $this->pagesettings[(string)$param];
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get all the current controller PageView Settings (Cfgs)
	 *
	 * @return 	ARRAY					:: an associative array with all controller Page View Cfgs. (Settings) currently set
	 */
	final public function PageViewGetCfgs() : array {
		//--
		return (array) $this->pagesettings;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a list with multiple values for settings into the current controller as PageView Settings (Cfgs)
	 *
	 * @param 	ARRAY 		$params		:: an associative array to be set as [ 'param1' => 'value1', ..., 'param-n' => 'val-n' ]
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetCfgs($params) : bool { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO WORK ALSO NON-ARRAY VARS !!!
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(!is_array($params)) {
			return false; // $params must be array
		} //end if
		//--
		$params = (array) array_change_key_case((array)$params, CASE_LOWER); // make all keys lower
		//--
		$ok = true;
		//--
		foreach($params as $key => $val) {
			$test = $this->PageViewSetCfg($key, $val);
			if($test !== true) {
				$ok = false;
			} //end if
		} //end foreach
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a single value for settings into the current controller as PageView Settings (Cfgs)
	 *
	 * @param 	STRING 		$param		:: the parameter to be set
	 * @param 	STRING 		$value		:: the value
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetCfg($param, $value) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		if(!Smart::is_nscalar($value)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Value: '.$param);
			return false;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		if(is_bool($value)) { // fix for bool
			if($value === true) {
				$value = 'yes'; // true
			} elseif($value === false) {
				$value = ''; // false
			} //end if else
		} //end if
		if(in_array((string)$param, (array)$this->availsettings)) {
			$this->pagesettings[(string)$param] = (string)$value;
		} else {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter: '.$param);
			return false;
		} //end if else
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Reset all variables for the current controller into PageView Settings (Cfgs)
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewResetCfgs() : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		$this->pagesettings = array();
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get ERROR Message for a controller page
	 *
	 * @param 	BOOL 		$errhtml	:: *Optional* ; Default is FALSE ; If TRUE will return the HTML Error Message instead of default plain HTML Message
	 *
	 * @return 	STRING 					:: the ERROR Message if Any
	 */
	final public function PageViewGetErrorMessage(bool $errhtml=false) : string {
		//--
		$code = (int) $this->PageViewGetStatusCode();
		//--
		$err = '';
		if((int)$code >= 400) {
			$err = (string) $this->pagesettings['error'];
			if($errhtml === true) { // if req. for specific errhtml serve this
				if((string)trim((string)$this->pagesettings['errhtml']) != '') { // if have errhtml, use this
					$err = (string) $this->pagesettings['errhtml'];
				} else { // otherwise convert the error (txt) message if any to errhtml
					$err = (string) Smart::escape_html($err);
				} //end if
			} //end if
		} //end if
		//--
		return (string) $err;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get STATUS Code for a controller page
	 *
	 * @return 	ENUM 					:: the HTTP Status Code: 2xx, 3xx, 4xx, 5xx, ... (consult middleware documentation to see what is supported) ; if an invalid error status code is used then 200 will be used instead
	 */
	final public function PageViewGetStatusCode() : int {
		//--
		$code = 200; // default
		//--
		if((int)$this->pagesettings['status-code'] > (int)$code) {
			$code = (int) $this->pagesettings['status-code'];
		} //end if
		//--
		return (int) $code;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set OK (2xx) STATUS Code for a controller page
	 *
	 * @param 	ENUM 		$code		:: the HTTP OK Status Code: 200, 202, 203, 208, ... (consult middleware documentation to see what is supported) ; if an invalid error status code is used then 200 will be used instead
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetOkStatus(int $code) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		$code = (int) $code;
		if(!in_array((int)$code, (array)SmartFrameworkRuntime::getHttpStatusCodesOK())) { // in the case that the http status code is n/a, use 200 instead
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid OK Status Code ('.$code.'), reset it to 200');
			$code = 200;
		} //end if
		//--
		return (bool) $this->PageViewSetCfg('status-code', (int)$code);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set Error Status and Optional Message for a controller page
	 * The Controller should stop the execution after calling this function using 'return;' or ending the 'Run()' main function
	 *
	 * @param 	ENUM 			$code		:: the HTTP Error Status Code: 400, 403, 404, 500, 503, ... (consult middleware documentation to see what is supported) ; if an invalid error status code is used then 500 will be used instead
	 * @param 	STRING/ARRAY 	$msg 		:: The detailed message that will be displayed public near the status code ; can be string or array [ 0 => message ; 1 => htmlmessage ]
	 * @param 	ENUM 			$logtype 	:: *Optional* ; Default is '' ; available values: '' | 'WARN' | 'NOTICE'
	 *
	 * @return 	BOOL						:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetErrorStatus(?int $code, $msg=null, ?string $logtype='') : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(is_array($msg)) {
			$message = (string) ($msg[0] ?? '');
			$htmlmsg = (string) ($msg[1] ?? '');
		} else {
			$message = (string) $msg;
			$htmlmsg = '';
		} //end if else
		//--
		$code = (int) $code;
		if(!in_array((int)$code, (array)SmartFrameworkRuntime::getHttpStatusCodesERR())) { // in the case that the error status code is n/a, use 500 instead
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid HTTP Error Status Code ('.$code.'), reset it to 500');
			$code = 500;
		} //end if else
		//--
		$out = (bool) $this->PageViewSetCfgs([
			'status-code' 	=> (int) $code,
			'error' 		=> (string) $message,
			'errhtml' 		=> (string) $htmlmsg
		]);
		//--
		switch((string)strtoupper((string)trim((string)$logtype))) {
			case 'NOTICE':
				Smart::log_notice('Page Controller Log NOTICE # ('.$this->controller.'): [Status-Code:'.(int)$code.'] '.(string)$message);
				break;
			case 'WARN':
				Smart::log_warning('Page Controller Log WARNING # ('.$this->controller.'): [Status-Code:'.(int)$code.'] '.(string)$message);
				break;
			default:
				// no log
		} //end switch
		//--
		return (bool) $out;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set Redirect URL for a controller page
	 * The Controller should stop the execution after calling this function using 'return;' or ending the 'Run()' main function
	 *
	 * @param 	STRING 		$url 		:: The absolute URL to redirect the page to (Ex: http://some-domain.ext/some-page.html)
	 * @param 	ENUM 		$code		:: the HTTP Error Status Code: 301, 302, ... (consult middleware documentation to see what is supported) ; if an invalid error status code is used then 302 will be used instead
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetRedirectUrl(?string $url, ?int $code) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		$url = (string) trim((string)$url);
		if((string)$url == '') {
			return false;
		} //end if
		//--
		$code = (int) $code;
		if(!in_array((int)$code, (array)SmartFrameworkRuntime::getHttpStatusCodesRDR())) { // in the case that the redirect status code is n/a, use 302 instead
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Redirect Status Code ('.$code.'), reset it to 302');
			$code = 302;
		} //end if
		//--
		return (bool) $this->PageViewSetCfgs([
			'status-code' 	=> (int) $code,
			'redirect-url' 	=> (string) $url
		]);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Create a Page View Semaphores List from an Array
	 * This creates a semaphores list for templating, that can be used with a special variable 'semaphore' to signal templates different semaphore conditions (ex: if a semaphore contains a certain <value> load or not a portion of tpl code)
	 *
	 * @param 	ARRAY 		$semaphores		:: Ex: [ 'semaphore1', 'semaphore2' ]
	 *
	 * @return 	STRING						:: The list of Semaphores: ex: '<semaphore1>,<semaphore2>'
	 */
	final public function PageViewCreateSemaphores(array $semaphores) : string {
		//--
		return (string) Smart::array_to_list((array)$semaphores);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get the current controller PageView specific Var
	 *
	 * @param 	STRING 		$param		:: the variable to be get
	 *
	 * @return 	MIXED					:: the content of the specific PageView variable currently set
	 */
	final public function PageViewGetVar($param) {
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return null;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		return (array_key_exists((string)$param, $this->pageview) ? $this->pageview[(string)$param] : null); // mixed ; do not test, it may be an out of bound parameter set in cache
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get all the current controller PageView Vars
	 *
	 * @return 	ARRAY					:: an associative array with all the controller Page View variables currently set
	 */
	final public function PageViewGetVars() : array {
		//--
		return (array) $this->pageview;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a list with multiple values for variables into the current controller into PageView Vars
	 *
	 * @param 	ARRAY 		$params		:: an associative array to be set as [ 'variable1' => 'value1', ..., 'variable-n' => 'val-n' ]
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetVars($params) : bool { // !!! DO NOT FORCE ARRAY TYPE ON METHOD PARAMETER AS IT HAVE TO WORK ALSO NON-ARRAY VARS !!!
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if(!is_array($params)) {
			return false; // $params must be array
		} //end if
		//--
		$params = (array) array_change_key_case((array)$params, CASE_LOWER); // make all keys lower
		//--
		$ok = true;
		//--
		foreach($params as $key => $val) {
			$test = $this->PageViewSetVar($key, $val);
			if($test !== true) {
				$ok = false;
			} //end if
		} //end foreach
		//--
		return (bool) $ok;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a single value for the current controller into PageView Vars, with Optional OverWrite (if not empty) parameter
	 *
	 * @param 	STRING 		$param		:: the variable to be set
	 * @param 	STRING 		$value		:: the value
	 * @param 	BOOL 		$overwrite 	:: overwrite (default is TRUE) ; set to FALSE to set only if value is empty
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewSetVar($param, $value, bool $overwrite=true) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		if(!Smart::is_nscalar($value)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Value: '.$param);
			return false;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		if($overwrite === false) {
			if(array_key_exists((string)$param, $this->pageview) AND ((string)$this->pageview[(string)$param] != '')) {
				return true;
			} //end if
		} //end if
		//--
		$this->pageview[(string)$param] = (string)$value; // set
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Prepend a single value to a variable for the current controller into PageView Vars
	 *
	 * @param 	STRING 		$param		:: the variable to prepend value to
	 * @param 	STRING 		$value		:: the value
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewPrependVar($param, $value) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		if(!Smart::is_nscalar($value)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Value: '.$param);
			return false;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		$value = (string) $value;
		//--
		if(!array_key_exists((string)$param, $this->pageview)) {
			$this->pageview[(string)$param] = ''; // init
		} //end if
		$this->pageview[(string)$param] = (string) $value.$this->pageview[(string)$param]; // prepend
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Append a single value to a variable for the current controller into PageView Vars
	 *
	 * @param 	STRING 		$param		:: the variable to append value to
	 * @param 	STRING 		$value		:: the value
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewAppendVar($param, $value) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		if(!Smart::is_nscalar($value)) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Value: '.$param);
			return false;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		if(!array_key_exists((string)$param, $this->pageview)) {
			$this->pageview[(string)$param] = ''; // init
		} //end if
		$this->pageview[(string)$param] .= (string) $value; // append
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Reset all variables for the current controller into PageView Vars and unset all keys
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewResetVars() : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		$this->pageview = array();
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Reset a single variable value for the current controller into PageView Vars and unset the key
	 *
	 * @param 	STRING 		$param		:: the variable to be reset (unset)
	 *
	 * @return 	BOOL					:: TRUE if OK, FALSE if not
	 */
	final public function PageViewResetVar($param) : bool {
		//--
		if($this->directoutput === true) {
			Smart::log_warning('Page Controller: '.$this->controller.' # '.__METHOD__.'(): This method is not available for Direct Output Mode ...');
			return false;
		} //end if
		//--
		if((!Smart::is_nscalar($param)) OR ((string)trim((string)$param) == '')) {
			Smart::log_notice('Page Controller: '.$this->controller.' # '.__METHOD__.'(): Invalid Parameter Type: '.$param);
			return false;
		} //end if
		//--
		$param = (string) strtolower((string)$param);
		//--
		if((string)$param != '') {
			$this->pageview[(string)$param] = '';
			unset($this->pageview[(string)$param]);
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Test if the Page Cache (system) is active or not
	 * This is based on PersistentCache
	 *
	 * @return 	BOOL					:: TRUE if Active, FALSE if not
	 */
	final public function PageCacheisActive() : bool {
		//--
		return (bool) SmartPersistentCache::isActive();
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Prepare a Page Cache SAFE Key or Realm
	 * This is based on PersistentCache
	 *
	 * @return 	STRING					:: The safe prepared Key or Realm
	 */
	final public function PageCacheSafeKey(?string $y_key_or_realm) : string {
		//--
		return (string) SmartPersistentCache::safeKey((string)$y_key_or_realm);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Get a Page from the (Persistent) Cache
	 *
	 * @param 	STRING 		$storage_namespace		:: the cache storage namespace, used to group keys
	 * @param 	STRING 		$unique_key				:: the unique cache key
	 *
	 * @return 	MIXED								:: If the PersistentCache is active and value was set will return a single (STRING) or multiple (ARRAY) Page Settings / Page Values ; otherwise will return a NULL value.
	 */
	final public function PageGetFromCache(?string $storage_namespace, ?string $unique_key) {
		//--
		if(!SmartPersistentCache::isActive()) {
			return null;
		} //end if
		//--
		$cache = SmartPersistentCache::getKey(
			(string) $storage_namespace,
			(string) $unique_key
		);
		//--
		if(($cache === null) OR ((string)$cache == '')) {
			return null;
		} //end if
		//--
		return SmartPersistentCache::varUncompress($cache); // mixed (number / string / array)
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Set a Page into the (Persistent) Cache
	 *
	 * @param 	STRING 		$storage_namespace		:: the cache storage namespace, used to group keys
	 * @param 	STRING 		$unique_key				:: the unique cache key
	 * @param 	MIXED 		$content 				:: the cache content as a STRING or an ARRAY with Page Value(s) / Page Setting(s)
	 * @param 	INTEGER 	$expiration 			:: The page cache expiration in seconds ; 0 will not expire
	 *
	 * @return 	BOOL								:: TRUE if the PersistentCache is active and value was set ; FALSE in the rest of the cases
	 */
	final public function PageSetInCache(?string $storage_namespace, ?string $unique_key, $content, int $expiration) : bool {
		//--
		if($content === null) { // must allow empty array ; dissalow null as null is the returned result by get key if key not found
			return false;
		} //end if
		//--
		if(!SmartPersistentCache::isActive()) {
			return false;
		} //end if
		//--
		$cache = (string) SmartPersistentCache::varCompress($content); // mixed (number / string / array)
		if((string)$cache == '') {
			return false;
		} //end if
		//--
		return (bool) SmartPersistentCache::setKey(
			(string) $storage_namespace,
			(string) $unique_key,
			(string) $cache,
			(int)    $expiration // expiration time in seconds
		);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Unset a Page from the (Persistent) Cache
	 *
	 * @param 	STRING 		$storage_namespace		:: the cache storage namespace, used to group keys
	 * @param 	STRING 		$unique_key				:: the unique cache key
	 *
	 * @return 	BOOL								:: TRUE if the PersistentCache is active and value was unset ; FALSE in the rest of the cases
	 */
	final public function PageUnsetFromCache(?string $storage_namespace, ?string $unique_key) : bool {
		//--
		return (bool) SmartPersistentCache::unsetKey(
			(string) $storage_namespace,
			(string) $unique_key
		);
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * Force Instant Flush Output, ONLY for controllers that do a direct output (Ex: SMART_APP_MODULE_DIRECT_OUTPUT must be defined and set to TRUE).
	 * This will do instant flush and also ob_flush() if necessary and detected (for example when the output_buffering is enabled in PHP.INI).
	 * NOTICE: be careful using this function to avoid break intermediary output bufferings !!
	 *
	 * @access 		private
	 * @internal
	 *
	 * @return VOID	:: This function does not return anything
	 */
	final public function InstantFlush() : void {
		//--
		if($this->directoutput === true) { // OK
			//--
			Smart::InstantFlush();
			//--
		} else { // WARNING: N/A
			//--
			Smart::log_warning('Page Controller: '.$this->controller.' # Using the InstantFlush() in controllers that are not using direct output is not allowed as will break the middleware output chain ...');
			//--
		} //end if
		//--
		return;
		//--
	} //END FUNCTION
	//=====


	//=====
	/**
	 * This is a pre Run() function
	 * This function will be called before Run()
	 *
	 * @return MIXED 					:: *OPTIONAL* If an ERROR occurs during initialize it can return FALSE or any valid and supported HTTP ERR Status Codes as 4xx / 5xx to avoid continue and dissalow calling the Run() method ; otherwise it can return TRUE or VOID ; or it can return any SUCCESS or REDIRECT HTTP Status Codes 2xx / 3xx and this will also prevent executing Run() method (but in this situation an output, at least 'main' or 'redirect-url' must be set)
	 */
	public function Initialize() {
		// *** optional*** can be redefined in a controller (as a pre-run init, if required) but is not mandatory ...
	} //END FUNCTION
	//=====


	//=====
	/**
	 * The Controller Runtime - This function is required to be re-defined in all controllers
	 *
	 * @return 	MIXED					:: *OPTIONAL* The HTTP Status Code: by default it does not return or it must returns 200 which is optional ; other supported HTTP Status Codes are: 201/202/203/204/208, 301/302, 400/401/402/403/404/405/406/408/409/410/415/422/423/424/429, 500/501/502/503/504/507 ; 304 (Not Modified) is reserved, cannt be used here ; if the HTTP status code is in the range of 4xx - 5xx, an extra notification message can be set as: ##EXAMPLE: $this->PageViewSetErrorStatus(400, 'Some parameters are missing'); return; / ##EXAMPLE: $this->PageViewSetCfg('error', 'Access to this page is restricted'); return 403; ## - to have also a detailed error message to be shown near the HTTP status code) ; It also can return VOID or TRUE as HTTP Status 200 or return FALSE as HTTP Status 500
	 */
	abstract public function Run(); //END FUNCTION
	//=====


	//=====
	/**
	 * This is the post Initialize() / post Run() function
	 * This function will be called after Initialize() and Run() wether or not Run() is executed (Initialize() may prevent Run() to be executed but in any case Shutdown() will be executed ...)
	 *
	 * This function is the (real) Controller Destructor - This function is optional and can be re-defined in controllers where a destructor is required.
	 * It will replace the class destructor __destruct() which is NOT SAFE in all cases (see php bug #31570).
	 * NOTICE:
	 * Sometimes __destruct() for classes is not 100% safe ; example: the PHP Bug #31570 (not fixed since a very long time).
	 * Instead of __destruct() use ShutDown() method for controllers in framework modules (which is always executed after Run() and is 100% safe).
	 * WARNING:
	 * Modifications for Page Settings or Page Variables are not allowed in this function, after Run() has been completed !
	 * If controller variables are modified after Run() has completed it can produce unexpected results ...
	 * EXAMPLE / SCENARIO:
	 * This function (by replacing __destruct()) can be used if you have to cleanup a temporary folder (tmp/) after Run().
	 * Because of the PHP Bug #31570, the __destruct() can't operate on relative paths and will produce wrong and unexpected results !!!
	 *
	 * @returns VOID :: This function does not return anything and if it sets any page settings, configs, status codes, headers or anything else will be ignored ; the purpose of this function is just to be used as a safe destructor !
	 */
	public function ShutDown() {
		// *** optional*** can be redefined in a controller (as a post-run init, as safe destructor, if required) but is not mandatory ...
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=========================
//==
/**
 * Function AutoLoad Modules (Libs / Models) via Dependency Injection
 *
 * @access 		private
 * @internal
 * @version		v.20210526
 *
 */
function autoload__SmartFrameworkModClasses($classname) {
	//--
	$classname = (string) $classname;
	//--
	if((strpos($classname, '\\') === false) OR (!preg_match('/^[a-zA-Z0-9_\\\]+$/', $classname))) { // if have no namespace or not valid character set
		return;
	} //end if
	//--
	if((strpos($classname, 'SmartModExtLib\\') === false) AND (strpos($classname, 'SmartModDataModel\\') === false)) { // must start with this namespaces only
		return;
	} //end if
	//--
	$parts = (array) explode('\\', $classname);
	if(count($parts) != 3) { // need for [0], [1] and [2]
		return;
	} //end if
	if((string)trim((string)$parts[0]) == '') { // type
		return; // no module detected
	} //end if
	if((string)trim((string)$parts[1]) == '') { // mod suffix
		return; // no module detected
	} //end if
	if((string)trim((string)$parts[2]) == '') { // class file
		return; // invalid
	} //end if
	//--
	$dir = 'modules/mod';
	$dir .= (string) strtolower((string)implode('-', preg_split('/(?=[A-Z])/', (string)$parts[1])));
	if((string)$parts[0] == 'SmartModExtLib') {
		$dir .= '/libs/';
	} elseif((string)$parts[0] == 'SmartModDataModel') {
		$dir .= '/models/';
	} else {
		return; // other namespaces are not managed here
	} //end if else
	$dir = (string) $dir;
	$file = (string) $parts[2];
	$path = (string) $dir.$file;
	$path = (string) trim((string)str_replace(['\\', "\0"], '', (string)$path)); // filter out null byte and backslash
	//--
	if(((string)$path == '') OR (!preg_match('/^[_a-zA-Z0-9\-\/]+$/', (string)$path))) {
		return; // invalid path characters in file
	} //end if
	//--
	if(!is_file((string)$path.'.php')) { // here must be used is_file() because is autoloader ...
		return; // file does not exists
	} //end if
	//--
	require_once((string)$path.'.php');
	//--
} //END FUNCTION
//==
spl_autoload_register('autoload__SmartFrameworkModClasses', true, false); // throw / append
//==
//=========================


// end of php code
