<?php
// [LIB - Smart.Framework / Samples / Test Unit Main]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitMain
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so it does not need anything else to be setup

namespace SmartModExtLib\Samples;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Test Unit Main (Misc Tests)
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20231114
 *
 */
final class TestUnitMain {

	// ::


	//============================================================
	public static function mainScreen(?int $tab, ?array $frm, ?array $frx) {

		//--
		if(!\defined('\\SMART_FRAMEWORK_TESTUNIT_BASE_URL')) {
			\SmartFrameworkRuntime::Raise500Error('ERROR: TEST UNIT BASE URL has not been defined ...');
			return;
		} //end if
		//--

		//--
		$tab = (int)   $tab;
		$frm = (array) $frm;
		$frx = (array) $frx;
		//--

		//--
		if(\Smart::array_size($frx) > 0) { // test form data :: because is modal we have to close it in order to refresh the parent
			//--
			return '<table><tr><td><h1>Form Sent (Test) !</h1><hr><pre>'.\Smart::escape_html((string)\print_r($frx,1)).'</pre></td></tr></table><script>smartJ$Browser.RefreshParent();</script><br><br><input class="ux-button" id="myCloseButton" type="button" value="[Close Me]" onClick="smartJ$Browser.CloseModalPopUp(); return false;"><br><br><b>This page will auto-close in 9 seconds [Counting: <span id="mycounter">9</span>]</b><script>smartJ$Browser.CountDown(9, \'mycounter\', \'console.log(counter, elID); smartJ$Browser.CloseDelayedModalPopUp(500);\');</script><br><br><b><i>After closing this window, parent will refresh ...</i></b>';
			//--
		} //end if
		//--

		//-- normal form with modal / popup
		$basic_form_start 	= '<form class="ux-form ux-inline-block" id="form_for_test" action="'.\SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.main&tab=1&winmod=yes" method="post" target="_blank"><input type="hidden" name="testformdata[test]" value="Testing ..."><input type="hidden" name="testformdata[another-test]" value="Testing more ...">';
		$basic_form_end 	= '</form>';
		//--
		$basic_form_send_modal = '<input class="ux-button ux-button-secondary" style="min-width:320px;" type="submit" value="Submit Form with Confirmation / Modal" OnClick="'.\SmartViewHtmlHelpers::js_code_confirm_form_submit('<div align="left"><h3><b>Are you sure you want to submit this form [MODAL] ?</b></h3></div>', 'my_form').'">';
		$basic_form_send_popup = '<input class="ux-button ux-button-regular" style="min-width:320px;" type="submit" value="Submit Form with Confirmation / PopUp" OnClick="'.\SmartViewHtmlHelpers::js_code_confirm_form_submit('<div align="left"><h3><b>Are you sure you want to submit this form [POPUP] ?</b></h3></div>', 'my_form', '780', '420', '1').'">';
		//--

		//-- ajax post form
		$btnop = '<button title="Submit this Test Form by AJAX (with Confirmation)" class="ux-button ux-button-large ux-button-primary" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('test_form_ajax', \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.post-form-by-ajax&tab=2', '<h2>Are you sure you want to submit this form by Ajax !?</h2>', 'jQuery(\'[id^=Smart-Captcha-Plugin] img:first\').trigger(\'click\');').' return false;">Submit this Test Form by AJAX &nbsp; <span class="sfi sfi-compass"></span></button>';
		//-- end

		//-- lists with one element
		$one_single_select 				= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('test-unit-s-list-one', '', 'form', array('one' => 'One'), 'frm[one_single]', '150', '', 'no', 'no', '', '#JS-UI#'); // returns HTML Code
		$one_single_with_blank_select 	= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi('test-unit-lst-m-1', '', 'form', array('one' => 'One'), 'frm[one_multi][]', 'list', 'no', '200', '', '#JS-UI-FILTER#'); // returns HTML Code
		//--
		$test_normal_list_s 			= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('test_normal_s', '', 'form', [1 => 'Val 1', 2 => 'Val 2', 3 => 'Val 3', 4 => 'Val 4', 5 => 'Val 5']);
		$test_normal_list_m 			= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi('test_normal_m', '', 'form', [1 => 'Val 1', 2 => 'Val 2', 3 => 'Val 3', 4 => 'Val 4', 5 => 'Val 5'], '', 'list', 'no', '200/75', '', 'height:65px;');
		//--

		//-- misc purpose data array
		$array_of_values = array();
		$array_of_values['#OPTGROUP#1'] = 'Labels';
		for($i=1; $i<=500; $i++) {
			$array_of_values['id'.$i] = 'Label '.$i;
		} //end for
		//--
		$array_of_values['#OPTGROUP#2'] = 'HTML Safety Test';
		//--
		$weird = 'a&"/><i>Italic</i></body>';
		$array_of_values['safety-test:'.$weird] = (string) $weird;
		//--

		//-- single-select
		$selected_value 	= 'id2';
		$elem_single_select = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('test-unit-s-list-two', $selected_value, 'form', $array_of_values, 'frm[list_single]', '150', 'onChange="alert(\''.\Smart::escape_js('Getting value from the "SingleList": ').'\' + $(\'#test-unit-s-list-two\').val());"', 'no', 'yes', '', '#JS-UI-FILTER#'); // returns HTML Code
		//--

		//-- draw a multi-select (classic)
		$selected_values 	= [ 'id1', 'id3' ];
		$elem_multi_select 	= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi('test-unit-m-list-2', $selected_values, 'form', $array_of_values, 'frm[list_multi_one][]', 'list', 'no', '250', 'onBlur="alert(\''.\Smart::escape_js('Getting value from the:'."\n".' "MultiList": ').'\' + $(\'#test-unit-m-list-2\').val());"', '#JS-UI-FILTER#'); // returns HTML Code
		//--

		//-- multi-select (checkboxes)
		$array_of_values 	= array('id1' => 'Label 1', 'id2' => 'Label 2', 'id3' => 'Label 3');
		$selected_values 	= array('id2', 'id3');
		$elem_multi_boxes 	= (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_multi('test-unit-m-list-3', $selected_values, 'form', $array_of_values, 'frm[list_multi_two][]', 'checkboxes', 'yes'); // returns HTML Code
		//--

		//--
		if(\SmartEnvironment::isAdminArea() === true) {
			if(\SmartEnvironment::isTaskArea() === true) {
				$info_adm = '[ Task Area (Private) ]';
				$info_pfx = 'tsk';
			} else {
				$info_adm = '[ Admin Area (Private) ]';
				$info_pfx = 'adm';
			} //end if else
		} else {
			$info_adm = '[ Index Area (Public) ]';
			$info_pfx = 'idx';
		} //end if else
		//--

		//--
		$demo_mod_ext_toolkits = '';
		$demo_mod_ext_components = '';
		if(\SmartAppInfo::TestIfModuleExists('mod-ui-bootstrap')) {
			$demo_mod_ext_toolkits .= (string) \SmartFileSystem::read('modules/mod-ui-bootstrap/testunit/templates/tab-ui-components.inc.htm');
		} //end if
		if(\SmartAppInfo::TestIfModuleExists('mod-ui-jqueryui')) {
			$demo_mod_ext_toolkits .= (string) \SmartFileSystem::read('modules/mod-ui-jqueryui/testunit/templates/tab-ui-components.inc.htm');
		} //end if
		if(\SmartAppInfo::TestIfModuleExists('mod-js-components')) {
			$demo_mod_ext_components .= (string) \SmartFileSystem::read('modules/mod-js-components/testunit/templates/tab-ui-components.inc.htm');
		} //end if
		if(\SmartAppInfo::TestIfModuleExists('mod-wflow-components')) {
			$demo_mod_ext_components .= (string) \SmartFileSystem::read('modules/mod-wflow-components/testunit/templates/tab-ui-components.inc.htm');
		} //end if
		//--
		$demo_mod_ui_components = (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-samples/libs/templates/testunit/test-unit-tab-components.inc.htm',
			[
				'TESTUNIT_BASE_URL' 		=> (string) \SMART_FRAMEWORK_TESTUNIT_BASE_URL,
				'EXTERNAL-TOOLKITS' 		=> (string) $demo_mod_ext_toolkits,
				'EXTERNAL-COMPONENTS' 		=> (string) $demo_mod_ext_components
			]
		);
		//--

		//--
		$test_tabs_activate = '<script>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::js_code_uitabs_activate('tabs_draw', false).'</script>'; // unused, just test method output
		//--
		$arr_bw = (array) \SmartComponents::get_imgdesc_by_bw_id((string)\SmartUtils::get_os_browser_ip('bw'));
		$tpl_path = 'modules/mod-samples/libs/templates/testunit';
		//--
		$tpl_alt_tpl = (bool) \SmartAppInfo::TestIfModuleExists('mod-tpl');
		$tpl_alt_twist = (bool) \SmartAppInfo::TestIfModuleExists('mod-tpl-twist');
		$tpl_alt_twig = (bool) \SmartAppInfo::TestIfModuleExists('mod-tpl-twig');
		$tpl_alt_typo3fluid = (bool) \SmartAppInfo::TestIfModuleExists('mod-tpl-typo3-fluid');
		$tpl_alt_avail = (bool) ($tpl_alt_tpl && ($tpl_alt_twist || $tpl_alt_twig || $tpl_alt_typo3fluid));
		//--
		$dlgType = 'auto';
		if(rand(0,10) > 5) {
			$dlgType = 'alertable';
		} //end if
		//--
		return \SmartMarkersTemplating::render_file_template( // rendering a complex template with hardcoded sub templates
			'modules/mod-samples/libs/templates/testunit/test-unit.mtpl.htm',
			[
				'@SUB-TEMPLATES@' => [
					'test-unit-tab-tests.inc.htm' 			=> (string) \SmartFileSysUtils::addPathTrailingSlash((string)$tpl_path), 	// directory with trailing slash
					'test-unit-tab-interractions.inc.htm' 	=> (string) $tpl_path, 													// directory without trailing slash
					'test-unit-tab-forms.inc.htm' 			=> '@', 																// @ (self) path, assumes the same dir
					'%test-unit-tab-templating%'			=> '@/test-unit-tab-templating.inc.htm'									// variable, with full path, using self @/sub-dir/ instead of $tpl_path/test-unit-tab-misc.htm
				],
				'MOD-BARCODES-AVAILABLE' 					=> (string) (\SmartAppInfo::TestIfModuleExists('mod-barcodes') ? 'yes' : 'no'),
				'TEST-DHKX-EXCHANGE-HTML' 					=> (string) \SmartModExtLib\Samples\DhkxTest::renderViewJsPhpExchange(),
				'TEST-URL-UNICODE-STR' 						=> (string) \SmartModExtLib\Samples\TestUnitStrings::testStr(),
				'TEST-UNIT-AREA' 							=> (string) $info_pfx,
				'TESTUNIT-TPL-PATH' 						=> (string) \SmartFileSysUtils::addPathTrailingSlash((string)$tpl_path), 	// this MUST be with trailing slash
				'TESTUNIT_BASE_URL' 						=> (string) \SMART_FRAMEWORK_TESTUNIT_BASE_URL,
				'NO-CACHE-TIME' 							=> (string) \time(),
				'CURRENT-DATE-TIME' 						=> (string) \date('Y-m-d H:i:s O'),
				'TEST-JS_SCRIPTS_Init-Tabs' 				=> '<script>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::js_code_uitabs_init('tabs_draw', \Smart::format_number_int($tab,'+')).'</script>',
				'Test-Buttons_AJAX-POST' 					=> (string) $btnop,
				'TEST-VAR'  								=> '<div style="background-color: #ECECEC; padding: 10px;"><b>Smart.Framework</b> :: PHP/Javascript web framework :: Test and Demo Suite @ '.$info_adm.'</div>',
				'TEST-ELEMENTS_DIALOG' 						=> '<a class="ux-button ux-button-primary" style="min-width:320px;" href="#" onClick="'.\SmartViewHtmlHelpers::js_code_ui_confirm_dialog('<h1>Do you like this framework ?</h1><div>Option: <select id="test-dlg-select-el-sf" class="ux-field"><option value="Yes">Yes</option><option value="No">No</option></select></div>', 'smartJ$Browser.AlertDialog(smartJ$Utils.escape_html(\'Well ... then you selected the value: [\' + $(\'#test-dlg-select-el-sf\').val() + \']\\n ... \\\' " <tag> !\'))', '', '', '', (string)$dlgType).' return false;">Test JS-UI Dialog &nbsp; <i class="sfi sfi-share"></i></a>',
				'TEST-ELEMENTS_ALERT' 						=> '<a class="ux-button ux-button-info" style="min-width:320px;" href="#" onClick="'.\SmartViewHtmlHelpers::js_code_ui_alert_dialog('<h2>You can press now OK !</h2><div>Option: <select id="test-dlg-select-el-sf" class="ux-field"><option value="One">One</option><option value="Two">Two</option></select></div>', 'smartJ$Browser.AlertDialog(smartJ$Utils.escape_html(\'Good ... you selected the value: [\' + $(\'#test-dlg-select-el-sf\').val() + \']\\n ... \\\' " <tag> !\'))', '', '', '', (string)$dlgType).' return false;">Test JS-UI Alert  &nbsp; <i class="sfi sfi-share"></i></a>',
				'TEST-ELEMENTS_SEND-CONFIRM-MODAL' 			=> (string) $basic_form_start.$basic_form_send_modal.$basic_form_end,
				'TEST-ELEMENTS_SEND-CONFIRM-POPUP' 			=> (string) $basic_form_start.$basic_form_send_popup.$basic_form_end,
				'TEST-ELEMENTS-WND-INTERRACTIONS-MODAL' 	=> (string) \SmartModExtLib\Samples\TestUnitBrowserWinInterractions::bttnModalTestInit(),
				'TEST-ELEMENTS-WND-INTERRACTIONS-POPUP' 	=> (string) \SmartModExtLib\Samples\TestUnitBrowserWinInterractions::bttnPopupTestInit(),
				'TEST-ELEMENTS_SINGLE-SELECT' 				=> 'SingleSelect DropDown List without Blank: '.$one_single_select,
				'TEST-ELEMENTS_SINGLE-BLANK-SELECT' 		=> 'SingleSelect DropDown List (from Multi): '.$one_single_with_blank_select,
				'TEST-ELEMENTS_SINGLE-SEARCH-SELECT' 		=> 'SingleSelect DropDown List with Search: '.$elem_single_select,
				'TEST-ELEMENTS_MULTI-SELECT' 				=> '<span>MultiSelect DropDown List: </span>'.$elem_multi_select,
				'TEST-ELEMENTS_MULTIBOX-SELECT' 			=> 'MultiSelect CheckBoxes (Sync):<br>'.$elem_multi_boxes,
				'TEST-ELEMENTS_NORMAL-LIST-S' 				=> (string) $test_normal_list_s,
				'TEST-ELEMENTS_NORMAL-LIST-M' 				=> (string) $test_normal_list_m,
				'TEST-ELEMENTS_CALENDAR' 					=> 'Calendar Selector: '.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_js_date_field('frm_calendar_id', 'frm[date]', \Smart::escape_html(isset($frm['date']) ? $frm['date'] : ''), 'Select Date', "'0d'", "'1y'", [], 'alert(\'You selected the date: \' + date);'),
				'TEST-ELEMENTS_TIMEPICKER' 					=> 'TimePicker Selector: '.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_js_time_field('frm_timepicker_id', 'frm[time]', \Smart::escape_html(isset($frm['time']) ? $frm['time'] : ''), 'Select Time', '9', '19', '0', '55', '5', '3', [], 'alert(\'You selected the time: \' + time);'),
				'TEST-ELEMENTS-YES_NO' 						=> \SmartViewHtmlHelpers::html_selector_yes_no('yes_or_no', 'y'),
				'TEST-ELEMENTS-TRUE_FALSE' 					=> \SmartViewHtmlHelpers::html_selector_true_false('true_or_false', '0'),
				'TEST-ELEMENTS_AUTOCOMPLETE-SINGLE' 		=> 'AutoComplete Single: '.'<input id="auto-complete-fld" type="text" name="frm[autocomplete]" style="width:75px;"><script>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::js_code_init_select_autocomplete_single('auto-complete-fld', \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.autocomplete', 'src', 1, 'alert(\'You selected: \' + value);').'</script>',
				'TEST-ELEMENTS_AUTOCOMPLETE-MULTI'			=> 'Autocomplete Multi: '.'<input id="auto-complete-mfld" type="text" name="frm[mautocomplete]" style="width:125px;"><script>'.\SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::js_code_init_select_autocomplete_multi('auto-complete-mfld', \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.autocomplete', 'src', 1, 'alert(\'You selected: \' + value);').'</script>',
				'TEST-elements_Captcha' 					=> (string) \SmartCaptcha::drawCaptchaForm(self::captchaFormName(), self::captchaFormPluginUrl(), (string)self::captchaMode()),
				'test-elements_limited-area' 				=> '<div>Limited TextArea:</div>'.\SmartViewHtmlHelpers::html_js_limited_text_area('', 'frm[text_area_1]', '', 300, '400px', '90px'),
				'POWERED-INFO' 								=> (string) \SmartComponents::app_powered_info(
																	'no',
																	[
																		[],
																		[
																			'type' => 'cside',
																			'name' => (string) $arr_bw['desc'],
																			'logo' => (string) \SmartUtils::get_server_current_url().$arr_bw['img'],
																			'url' => ''
																		]
																	],
																	false, // don't exclude db plugins
																	true, // display watch
																	true // display logo
																),
				'STR-NUM' 									=> '1abc', // this will be converted to num !!
				'NUM-NUM' 									=> '0.123456789',
				'IFTEST' 									=> \Smart::random_number(1,2),
				'IF2TEST' 									=> \Smart::random_number(0,9),
				'LOOPTEST-VAR1' => (array) [
						[
							'd1' => 'Column 1.x (HTML Escape)',
							'd2' => 'Column 2.x (JS Escape)',
							'd3' => 'Column 3.x (URL Escape)'
						]
				],
				'LOOPTEST-VAR2' => (array) [
						[
							'c1' => '<Column 1.1>',
							'c2' => 'Column 1.2'."\n",
							'c3' => 'Column 1.3'."\t"
						],
						[
							'c1' => '<Column 2.1>',
							'c2' => 'Column 2.2'."\n",
							'c3' => 'Column 2.3'."\t"
						],
						[
							'c1' => '<Column 3.1>',
							'c2' => 'Column 3.2'."\n",
							'c3' => 'Column 3.3'."\t"
						],
						[
							'c1' => \Smart::random_number(0,1),
							'c2' => 'a',
							'c3' => 'A'
						]
				],
				'HTML-SYNTAX-DESCR' 		=> (string) \SmartMarkersTemplating::prepare_nosyntax_html_template(\SmartFileSystem::read('modules/mod-samples/libs/templates/testunit/partials/test-tpl-syntax-desc.nosyntax.inc.htm'), true),
				'TEST-UI-COMPONENTS' 		=> (string) $demo_mod_ui_components,
				'TPL-ALT-AVAIL' 			=> (string) ($tpl_alt_avail ? 'yes' : 'no'),
				'TPL-TWIST-AVAIL' 			=> (string) ($tpl_alt_twist ? 'yes' : 'no'),
				'TPL-TWIG-AVAIL' 			=> (string) ($tpl_alt_twig ? 'yes' : 'no'),
				'TPL-TYPO3FLUID-AVAIL' 		=> (string) ($tpl_alt_typo3fluid ? 'yes' : 'no'),
			]
		);
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public static function formReplyJson($tab, $frm) {

		//--
		$tab = (int) $tab;
		$frm = (array) $frm;
		//--

		//--
		$tmp_data = '<br><br><hr><pre>'.'GET:'.'<br>'.\Smart::escape_html(\print_r(\SmartFrameworkSecurity::FilterRequestVar($_GET),1)).'<hr>'.'POST:'.'<br>'.\Smart::escape_html(\print_r(\SmartFrameworkSecurity::FilterRequestVar($_POST),1)).'</pre>';
		//--

		//--
		$captcha_ok = (bool) \SmartCaptcha::verifyCaptcha(
			self::captchaFormName(),
			false, // do not clear, will clear below
			self::captchaMode()
		);
		//--

		//--
		$code = '?';
		$title = '!';
		$desc = '...';
		$evcode = '';
		$redir = '';
		$div_id = '';
		$div_htm = '';
		$hide_form_on_success = false;
		//--
		if((string)$frm['date'] != '') {
			//--
			if($captcha_ok !== true) {
				//--
				$code = 'ERROR';
				$title = 'CAPTCHA verification FAILED ...';
				$desc = 'Please enter a valid captcha value:'.$tmp_data;
				//--
			} else {
				//--
				$code = 'OK';
				$title = 'Captcha validation OK ... The page or just the Captcha will be refreshed depending if TextArea is filled or not ...';
				$desc = 'Form sent successful:'.$tmp_data;
				//--
				if((string)$frm['text_area_1'] == '') {
					$evcode = 'alert(\'The page will be redirected shortly (because the request answer set it - custom action) ...\');';
					$redir = \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.main&time='.\time().'&tab='.\Smart::escape_url($tab);
					$div_id = '';
					$div_htm = '';
				} else {
					$redir = '';
					$div_id = 'answer_ajax';
					$div_htm = '<table border="0" bgcolor="#DDEEFF" width="100%"><tr><td><h1>OK, form sent on: '.\date('Y-m-d H:i:s').'</h1></td></tr><tr><td><div align="left"><img width="64" src="lib/framework/img/sign-ok.svg"></div><div><a data-smart="open.modal" href="'.\SMART_FRAMEWORK_TESTUNIT_BASE_URL.'test.markdown" target="testunit-json-test" title="Modal">Test Link 1 (modal link)</a><br><a href="'.\SMART_FRAMEWORK_TESTUNIT_BASE_URL.'test.json" target="_blank">Test Link 2 (default link)</a><br><a data-slimbox="slimbox" title="Image 3" href="?page=samples.test-image"><img src="?page=samples.test-image" alt="Click to Test Image Gallery" title="Click to Test Image Gallery"></a></div></td></tr><tr><td><hr><b>Here is the content of the text area:</b><br><pre>'.\Smart::escape_html($frm['text_area_1']).'</pre></td></tr></table>';
					$hide_form_on_success = true;
				} //end if else
				//--
				\SmartCaptcha::clearCaptcha(self::captchaFormName(), self::captchaMode()); // everything OK, so clear captcha
				//--
			} //end if else
			//--
		} else {
			//--
			$code = 'ERROR';
			$title = 'CAPTCHA NOT Checked yet <a> ...';
			$desc = 'Please fill the <b>Date field</b> ...'.$tmp_data;
			//--
			if((string)$frm['text_area_1'] != '') {
				$redir = (string) \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.main&time='.\time().'&tab='.\Smart::escape_url($tab); // test redir if error ! must not work !
			} //end if
			//--
			$div_id = '';
			$div_htm = '';
			//--
		} //end if else
		//--

		//--
		return \SmartViewHtmlHelpers::js_ajax_replyto_html_form($code, $title, $desc, $redir, $div_id, $div_htm, $evcode, $hide_form_on_success); // mixed output (json)
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public static function captchaImg() {

		//--
		$rand = \Smart::random_number(0,2);
		//--

		//--
		if(\SmartAppInfo::TestIfModuleExists('mod-captcha') && ($rand > 0)) {
			//--
			if($rand == 1) {
				$mode = 'hashed';
			} else {
				$mode = 'dotted';
			} //end if else
			//--
			$captcha = new \SmartModExtLib\Captcha\SmartImageCaptcha();
			$captcha->format = 'svg';
			$captcha->mode = (string) $mode;
			$captcha->pool = (string) '23579ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$captcha->width = 175;
			$captcha->height = 50;
			$captcha->chars = 5;
			$captcha->charfont = 'modules/mod-captcha/fonts/barrio.ttf';
			$captcha->charttfsize = 24;
			$captcha->charspace = 20;
			$captcha->charxvar = 15;
			$captcha->charyvar = 7;
			$captcha->noise = 500;
			$captcha->colors_chars = [0x111111, 0x333333, 0x778899, 0x666699, 0x003366, 0x669966, 0x006600, 0xFF3300];
			$captcha->colors_noise = [0x888888, 0x999999, 0xAAAAAA, 0xBBBBBB, 0xCCCCCC, 0xDDDDDD, 0xEEEEEE, 0x8080C0];
			//--
		} else {
			//--
			$captcha = new \SmartSVGCaptcha(5, 175, 50, 0);
			//--
		} //end if else
		//--

		//--
		$image = (string) $captcha->draw_image();
		$code = (string) strtoupper((string)$captcha->get_code());
		//--
		$captcha = null; // free mem
		//--

		//-- initialize captha form with the generated code
		if(!\SmartCaptcha::initCaptchaPlugin((string)self::captchaFormName(), (string)$code, (string)self::captchaMode())) {
			\Smart::log_warning(__METHOD__.' # Failed to init Captcha Form with code ...');
		} //end if
		//--

		//-- return the captcha image as string
		return (string) $image;
		//--

	} //END FUNCTION
	//============================================================


	//===== PRIVATES


	//============================================================
	private static function captchaMode() {
		//--
		if((string)\SMART_FRAMEWORK_TESTUNIT_CAPTCHA_MODE == 'session') {
			return 'session';
		} else {
			return 'cookie';
		} //end if else
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	private static function captchaFormName() {
		//--
		return ' Test_Unit-Ajax-Form-forCaptcha_'.\date('Y').' '; // test value with all allowed characters and some spaces (that spaces are presumed to be trimmed ...)
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	private static function captchaFormPluginUrl() {
		//--
		if(\SmartEnvironment::isAdminArea() !== true) {
			return ''; // on index display only the Smart.Captcha
		} //end if
		//--
		return (string) \SMART_FRAMEWORK_TESTUNIT_BASE_URL.'testunit.captcha';
		//--
	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
