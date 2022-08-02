<?php
// Class: \SmartModExtLib\AuthAdmins\AbstractTaskController
// (c) 2006-2022 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_DIRECT_OUTPUT', true);

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Task Controller: Abstract Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20220730
 *
 */
abstract class AbstractTaskController extends \SmartAbstractAppController {

	public const MODULE_TPL_PATH = 'modules/mod-auth-admins/templates/';

	protected $title = 'Task (Abstract)';

	protected $name_prefix = 'Abstract';
	protected $name_suffix = 'Task';
	protected $app_tpl = ''; // path/to/some.mtpl.htm
	protected $app_main_url = '';

	protected $sficon = '';
	protected $msg = '';
	protected $err = '';
	protected $notice = '';
	protected $notehtml = '';

	protected $goback = '';

	protected $modal = false;
	protected $workvar = '';
	protected $working = false;
	protected $workstop = false;
	protected $selfclose = 0;
	protected $endscroll = false;


	//-- This method can be extended, must return an associative array, of variables (if any) for the includded $this->app_tpl TPL
	protected function InitTask() {
		//--
		if(!$this->TestDirectOutput()) {
			return false;
		} //end if
		//--
		return array(); // for OK must return null if no app tpl is set or the array of variables for app tpl ; if ERR must return: false or error message as string
		//--
	} //END FUNCTION
	//--


	final protected function TestDirectOutput() {
		//--
		if((!\defined('\\SMART_APP_MODULE_DIRECT_OUTPUT')) OR (\SMART_APP_MODULE_DIRECT_OUTPUT !== true)) {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # Invalid Controller Mode. Must be with: SMART_APP_MODULE_DIRECT_OUTPUT=true !');
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	final protected function PageScrollDown() {
		//--
		echo "\n".'<script>setTimeout(() => { smartJ$Browser.windwScrollDown(self, -1); }, 150);</script>'."\n";
		//--
	} //END FUNCTION


	final protected function EchoTextMessage(string $message, bool $strong=false) {
		//--
		$tag_start = '';
		$tag_end = '';
		if($strong === true) {
			$tag_start = '<b>';
			$tag_end = '</b>';
		} //end if
		//--
		echo (string) $tag_start.\Smart::nl_2_br((string)\Smart::escape_html((string)\trim((string)$message))."\n", false).$tag_end;
		$this->InstantFlush();
		//--
	} //END FUNCTION


	final protected function EchoHtmlMessage(string $message) {
		//--
		echo (string) $message;
		$this->InstantFlush();
		//--
	} //END FUNCTION


	final public function Initialize() {
		//--
		if(!$this->TestDirectOutput()) {
			return false;
		} //end if
		//--
		ob_start();
		$arr_vars = $this->InitTask();
		if($arr_vars === null) {
			$arr_vars = array();
		} elseif($arr_vars === false) {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # InitTask() FAILED: Task was not initialized ...');
			return false;
		} elseif(is_string($arr_vars)) {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # InitTask() FAILED:'."\n".$arr_vars);
		} //end if
		$test = ob_get_contents();
		ob_end_clean();
		if((string)$test != '') {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # InitTask() must not output anything by echo !');
			return false;
		} //end if
		if(!\is_array($arr_vars)) {
			\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # InitTask() must return array() !');
			return false;
		} //end if
		if(\Smart::array_size($arr_vars) > 0) {
			if(\Smart::array_type_test($arr_vars) != 2) {
				\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # InitTask() the returned array() must be associative !');
				return false;
			} //end if
		} //end if
		//--
		if((string)\trim((string)$this->app_tpl) != '') {
			if(
				((int)\strlen((string)$this->app_tpl) < 10) OR
				(\substr((string)$this->app_tpl, -9, 9) != '.mtpl.htm') OR
				(!\SmartFileSysUtils::check_if_safe_path((string)$this->app_tpl)) OR
				(!\SmartFileSystem::is_type_file((string)$this->app_tpl))
			) {
				\SmartFrameworkRuntime::Raise500Error(__METHOD__.' # Invalid App TPL Path: `'.$this->app_tpl.'`. Must be a valid and existing relative path to something like this: `path/to/some.mtpl.htm`');
				return false;
			} //end if
		} else {
			$this->app_tpl = 'modules/mod-auth-admins/views/task.mtpl.htm'; // this can also an empty string ... but is better to display someething in header
			$arr_vars = array(); // make sure is quite empty in this case ...
		} //end if
		//--
		if(\is_string($this->workvar)) {
			if((string)$this->workvar != '') { // conditional by workvar
				if(\SmartFrameworkSecurity::ValidateUrlVariableName((string)$this->workvar)) {
					if(!$this->RequestVarGet((string)$this->workvar)) {
						$this->working = false;
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		$this->app_main_url = (string) \trim((string)$this->app_main_url);
		if((string)$this->app_main_url == '') {
			$this->app_main_url = (string) $this->ControllerGetParam('url-script').'?page=auth-admins.tasks';
		} //end if
		//--
		\SmartFrameworkRuntime::outputHttpHeadersCacheControl();
		echo (string) \SmartMarkersTemplating::render_file_template(
			self::MODULE_TPL_PATH.'template-task-start.mtpl.htm',
			(array) array_merge((array)$arr_vars, (array)\SmartComponents::set_app_template_conform_metavars([
				'@SUB-TEMPLATES@' => [
					'%app-tpl%' => (string) $this->app_tpl,
				],
				'TEMPLATE-PATH' 	=> (string) self::MODULE_TPL_PATH,
				'TEMPLATE-FILE' 	=> (string) 'template-task-start.mtpl.htm',
				'MOD-VIEW-PATH' 	=> (string) $this->ControllerGetParam('module-view-path'),
				'TITLE' 			=> (string) $this->title,
				'NAME' 				=> (string) $this->name_prefix.'.'.$this->name_suffix,
				'NAME-PREFIX' 		=> (string) $this->name_prefix,
				'NAME-SUFFIX' 		=> (string) $this->name_suffix,
				'WORKING' 			=> (string) (($this->working === true) ? 'yes' : 'no'),
				'WORKSTOP' 			=> (string) (($this->workstop === true) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'MAIN-URL' 			=> (string) $this->app_main_url,
			]))
		);
		$this->InstantFlush();
		//--
		return true;
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		$err = (string) \trim((string)$this->err);
		$notice = (string) \trim((string)$this->notice);
		//--
		$icon = '';
		if(\is_array($this->sficon) AND (\Smart::array_size($this->sficon) > 0)) {
			foreach($this->sficon as $key => $val) {
				if(\Smart::is_nscalar($val)) {
					if((string)\trim((string)$val) != '') {
						$icon .= ' &nbsp;&nbsp; <i class="sfi sfi-2x sfi-'.\Smart::escape_html((string)$val).'"></i>';
					} //end if
				} //end if
			} //end foreach
		} elseif((string)trim((string)$this->sficon) != '') {
			$icon = ' &nbsp;&nbsp; <i class="sfi sfi-2x sfi-'.\Smart::escape_html((string)$this->sficon).'"></i>';
		} //end if
		//--
		if((string)$err != '') {
			echo (string) \SmartComponents::operation_error((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$err)).$icon);
		} elseif((string)$notice != '') {
			echo (string) \SmartComponents::operation_notice((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$notice)).$icon);
			echo "\n";
			echo (string) $this->notehtml;
		} else {
			echo (string) \SmartComponents::operation_success('OK: Completed ... '.\Smart::nl_2_br((string)\Smart::escape_html((string)$this->msg)).$icon);
		} //end if
		$this->InstantFlush();
		//--
		echo (string) \SmartMarkersTemplating::render_file_template(
			self::MODULE_TPL_PATH.'template-task-end.mtpl.htm',
			(array) \SmartComponents::set_app_template_conform_metavars([
				'TEMPLATE-PATH' 	=> (string) self::MODULE_TPL_PATH,
				'TEMPLATE-FILE' 	=> (string) 'template-task-end.mtpl.htm',
				'TITLE' 			=> (string) $this->title,
				'YEAR' 				=> (string) \date('Y'),
				'WORKING' 			=> (string) (($this->working === true) ? 'yes' : 'no'),
				'HAVE-ERRORS' 		=> (string) (\strlen((string)$err) ? 'yes' : 'no'),
				'HAVE_NOTICE' 		=> (string) (\strlen((string)$notice) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'SELFCLOSE' 		=> (string) (((int)$this->selfclose > 0) ? (int)$this->selfclose : 0),
				'ENDSCROLL' 		=> (string) (($this->endscroll === true) ? 'yes' : 'no'),
				'GO-BACK-URL' 		=> (string) $this->goback,
			])
		);
		$this->InstantFlush();
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
