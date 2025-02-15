<?php
// Class: \SmartModExtLib\AuthAdmins\AbstractTaskController
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

\define('SMART_APP_MODULE_DIRECT_OUTPUT', true);

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================

/**
 * Task Controller: Abstract Custom Task
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250207
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
	protected $warn = '';
	protected $notice = ''; // for nothice there is no growl, there is only an extra HTML note ...
	protected $notehtml = '';

	protected $goback = ''; // does not work in modals

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


	final protected function FixMessageMultiNewLines(string $message) {
		//--
		if((string)\trim((string)$message) == '') {
			return '';
		} //end if
		//--
		$message = (string) \str_replace([ "\r\n", "\r" ], "\n", (string)$message);
		//-- {{{SYNC-FIX-EMPTY-MULTI-LINES-WITH-ONE-LINE}}} - with an adjustments
		$message = (string) \preg_replace('/^\n*[\n]{1,}/m', '', (string)$message); // fix: replace multiple consecutive lines that may also contain before optional leading spaces
		$message = (string) \preg_replace('/[^\S\r\n]+$/m', '', (string)$message); // remove trailing spaces on each line
		//--
		return (string) \trim((string)$message);
		//--
	} //END FUNCTION


	final protected function EchoTextMessage(string $message, bool $strong=false, bool $highlight=false) {
		//--
		if((string)\trim((string)$message) == '') {
			echo '<br>'; // for TEXT messages this can be a space / tab / newline, don't enclose in a box as below, simply echo it
			return;
		} //end if
		//--
		$tag_start = '';
		$tag_end = '';
		if($strong === true) {
			$tag_start = '<b>';
			$tag_end = '</b>';
		} //end if
		//--
		$css_class = '';
		if($highlight === true) {
			$css_class = 'task-highlight';
		} //end if
		//--
		echo (string) '<pre class="task-result'.($css_class ? ' '.$css_class : '').'">'.$tag_start.\Smart::escape_html((string)\trim((string)$message)).$tag_end.'</pre>'."\n";
		$this->InstantFlush();
		//--
	} //END FUNCTION


	final protected function EchoHtmlMessage(string $message, bool $highlight=false) {
		//--
		if((string)\trim((string)$message) == '') {
			return; // for HTML messages make non sense !
		} //end if
		//--
		$css_class = '';
		if($highlight === true) {
			$css_class = 'task-highlight';
		} //end if
		//--
		echo (string) '<div class="task-result'.($css_class ? ' '.$css_class : '').'">'.$message.'</div>'."\n"; // do not trim message, new lines must be appended to a non-empty html message, cant echo just new lines !
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
				(!\SmartFileSysUtils::checkIfSafePath((string)$this->app_tpl)) OR
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
		$err 	= (string) \trim((string)$this->err);
		$warn 	= (string) \trim((string)$this->warn);
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
		if((string)$err != '') { // error
			echo (string) \SmartComponents::operation_error((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$err)).$icon);
		} elseif((string)$warn != '') { // warning
			echo (string) \SmartComponents::operation_warn((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$warn)).$icon);
		} elseif((string)$notice != '') { // notice, with extra support for an extra note in HTML format
			echo (string) \SmartComponents::operation_notice((string)\Smart::nl_2_br((string)\Smart::escape_html((string)$notice)).$icon);
			if((string)\trim((string)$this->notehtml) != '') {
				echo "\n";
				echo (string) $this->notehtml;
			} //end if
		} else { // ok
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
				'HAVE-WARNS' 		=> (string) (\strlen((string)$warn) ? 'yes' : 'no'),
				'HAVE_NOTICE' 		=> (string) (\strlen((string)$notice) ? 'yes' : 'no'),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'SELFCLOSE' 		=> (string) (((int)$this->selfclose > 0) ? (int)$this->selfclose : 0),
				'ENDSCROLL' 		=> (string) (($this->endscroll === true) ? 'yes' : 'no'),
				'GO-BACK-URL' 		=> (string) (($this->modal === true) ? '' : (string)$this->goback),
				'MODAL' 			=> (string) (($this->modal === true) ? 'yes' : 'no'),
				'MAIN-URL' 			=> (string) $this->app_main_url,
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
