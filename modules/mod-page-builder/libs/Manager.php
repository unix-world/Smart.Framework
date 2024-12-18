<?php
// Class: \SmartModExtLib\PageBuilder\Manager
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\PageBuilder;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* Smart
//	* SmartUnicode
//	* SmartUtils
//	* SmartAuth
//	* SmartComponents
//	* SmartViewHtmlHelpers
//	* SmartTextTranslations

//==================================================================
//-- PRIVILEGES
// <admin>
// OR
// <pagebuilder:create>,<pagebuilder:edit>,<pagebuilder:data-edit>,<pagebuilder:delete>,<pagebuilder:files>
//--
//==================================================================

//define('SMART_PAGEBUILDER_DB_TYPE', 'sqlite'); 		// this must be set in etc/config.php to activate the PageBuilder module ; possible values for the DB Type: 'sqlite' to use with SQLite DB or 'pgsql' to use with PostgreSQL DB
//define('SMART_PAGEBUILDER_DISABLE_PAGES', true); 		// this can be set in etc/config.php to disable the use of pages and allow only segments
//define('SMART_PAGEBUILDER_DISABLE_DELETE', true); 	// this can be set in etc/config-admin.php to disable page deletions in PageBuilder Manager (optional)
//define('SMART_PAGEBUILDER_ALLOW_FULLTREE', true); 	// allow display full tree (this should be enabled just for small projects)
//define('SMART_PAGEBUILDER_THEME_DARK', true); 		// if set to TRUE will enable the dark theme for the page builder editors
//define('SMART_PAGEBUILDER_HTML_VALIDATOR', true); 	// if set will validate the HTML ; just for admin area and mostly for development purposes ; optimal values: 'tidy' or 'tidy:required' ; can be also DOM, but DOM wil not show all errors, DOM is better for rendering ; also Tidy have a bug that loose the pre-format, don't use Tidy for rendering ... just for validation

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Class: PageBuilder Manager
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20231119
 * @package 	PageBuilder
 *
 */
final class Manager {

	// ::

	private const REGEX_MARKER 			= '/^[A-Z0-9_\-\.]+$/'; // {{{SYNC-PAGEBUILDER-REGEX-MARKERS-INT}}}

	private const LIMIT_CODE_SIZE 		= 16777216; // 16 MB
	private const LIMIT_MEDIA_SIZE_MB 	= 2.25; // 2.25 MB
	private const IMG_QUALITY_JPG_WEBP 	= 0.87; // 87%
	private const IMG_MAX_WIDTH 		= 1920; // px
	private const IMG_MAX_HEIGHT 		= 1080; // px

	private const MODULE_PATH 			= 'modules/mod-page-builder/';
	private const ADM_AREA_SCRIPT 		= 'admin.php';
	private const URL_PARAM_PAGE 		= 'page';
	private const URL_VAL_MANAGE_PAGE 	= 'page-builder.manage';
	private const URL_VAL_FMANAGE_PAGE 	= 'page-builder.manage-files';


	//==================================================================
	public static function text($ykey, $y_escape_html=true) {

		//--
		$text = [];
		//--

		//-- ttls
		$text['ttl_list'] 			= 'PageBuilder Objects';
		$text['ttl_records'] 		= 'List';
		$text['ttl_trecords'] 		= 'TreeList';
		$text['ttl_add'] 			= 'New Object';
		$text['ttl_edt'] 			= 'Edit Object Properties';
		$text['ttl_edtc'] 			= 'Edit Object Code';
		$text['ttl_edtac'] 			= 'Edit Object Data';
		$text['ttl_del'] 			= 'Delete this Object';
		$text['ttl_clone'] 			= 'Clone this Object';
		$text['ttl_ch_list'] 		= 'PageBuilder Objects - Change List Mode';
		$text['ttl_webdav'] 		= 'PageBuilder Files - WebDAV';
		$text['ttl_transl_export'] 	= 'PageBuilder Translations Export';
		$text['ttl_transl_import'] 	= 'PageBuilder Translations Import';
		$text['ttl_reset_hits'] 	= 'Reset Hit Counter on All PageBuilder Objects';
		//-- buttons
		$text['search']				= 'Filter';
		$text['reset']				= 'Reset';
		$text['cancel']				= 'Cancel';
		$text['close']				= 'Close';
		$text['save']				= 'Save';
		$text['yes'] 				= 'Yes';
		$text['no']		   			= 'No';
		$text['segment_page'] 		= 'Segment';
		//-- page data mode
		$text['record_runtime'] 	= 'Data';
		$text['record_data'] 		= 'YAML';
		$text['record_syntax'] 		= 'Syntax';
		$text['record_code'] 		= 'Code';
		$text['record_source'] 		= 'Source';
		$text['record_json_data'] 	= 'Parsed Data';
		$text['record_sytx_html'] 	= 'HTML';
		$text['record_sytx_mkdw'] 	= 'MARKDOWN';
		$text['record_sytx_text'] 	= 'TEXT';
		$text['record_sytx_raw'] 	= 'RAW';
		//-- tab nav
		$text['tab_props'] 			= 'Properties';
		$text['tab_code'] 			= 'Code';
		$text['tab_data'] 			= 'Data';
		$text['tab_info'] 			= 'Info';
		$text['tab_media'] 			= 'Media';
		//-- list data
		$text['records'] 			= 'Records';
		$text['cnp']				= 'Create A New Object';
		$text['vep']				= 'View/Edit Object';
		$text['dp']					= 'Delete Object';
		//-- fields
		$text['ctrl_unassigned'] 	= '*** (UNASSIGNED CONTROLLER) ***';
		$text['search_by']			= 'Filter by';
		$text['keyword']			= 'Keyword';
		$text['op_compl']			= 'Operation completed';
		$text['op_ncompl'] 			= 'Operation NOT completed';
		//-- errors
		$text['err_0'] 				= 'Invalid Object Syntax Type';
		$text['err_1']				= 'ERROR: Invalid Object ID !';
		$text['err_2'] 				= 'Invalid manage operation !';
		$text['err_3'] 				= 'ID already in use !';
		$text['err_4'] 				= 'Invalid ID';
		$text['err_5'] 				= 'An error occured. Please try again !';
		$text['err_6'] 				= 'Invalid Name for Object';
		$text['err_7'] 				= 'Some Edit Fields are not allowed here !';
		//-- messages
		$text['msg_confirm_del'] 	= 'Please confirm you want to delete this object';
		$text['msg_unsaved'] 	  	= 'NOTICE: Any unsaved change will be lost.';
		$text['msg_object_exists'] 	= 'An Object with the Same ID Already Exists';
		$text['msg_no_priv_add']  	= 'WARNING: You have not enough privileges to Create New Objects !';
		$text['msg_no_priv_read'] 	= 'WARNING: You have not enough privileges to READ this Object !';
		$text['msg_no_priv_edit'] 	= 'WARNING: You have not enough privileges to EDIT this Object !';
		$text['msg_no_priv_del']  	= 'WARNING: You have not enough privileges to DELETE this Object !';
		$text['msg_specprivs_req'] 	= 'WARNING: Special Privileges are required to operate this change.';
		$text['msg_invalid_cksum'] 	= 'NOTICE: Invalid Object CHECKSUM ! Edit and Save again the Object Code or Object Data to (Re)Validate it !';
		//--
		$text['id'] 				= 'ID';
		$text['clone'] 				= 'Create a Clone of this Object';
		$text['ref'] 				= 'Ref.';
		$text['refs'] 				= 'Related Objects';
		$text['ctrl'] 				= 'Controller';
		$text['template'] 			= 'Page Template';
		$text['area'] 				= 'Segment Area';
		$text['layout'] 			= 'Area / Template';
		$text['tags'] 				= 'Tags';
		$text['name'] 				= 'Name';
		$text['active']				= 'Active';
		$text['special'] 			= 'Class';
		$text['login'] 				= 'Login Restricted';
		$text['modified']			= 'Modified';
		$text['size'] 				= 'Size';
		$text['free_acc'] 			= 'Public Access';
		$text['login_acc'] 			= 'Access by Login';
		$text['restr_acc'] 			= 'Restricted Access';
		$text['activate']			= 'Activate';
		$text['deactivate'] 		= 'Deactivate';
		$text['content'] 			= 'Content';
		$text['acontent'] 			= 'ActiveContent';
		$text['admin'] 				= 'Author';
		$text['published'] 			= 'Published';
		$text['auth'] 				= 'Auth';
		$text['translatable'] 		= 'Translatable';
		$text['translations'] 		= 'Translations';
		$text['warn_translations'] 	= 'WARNING: This PageBuilder Object is marked as Not Translatable but some Translations are detected';
		$text['counter'] 			= 'Hits';
		$text['pw_code'] 			= 'View Code';
		$text['pw_data'] 			= 'View Data';
		$text['pw_media'] 			= 'View Media';
		$text['preview'] 			= 'Preview';
		//--
		$text['hint_0'] 			= 'Select a filtering criteria from below';
		$text['hint_1'] 			= 'Hints: `[]` for Empty ; `![]` for Non-Empty ; `%expr%` for containing expression';
		$text['hint_2'] 			= 'Hints: `ro` for records having this language code Translation ; `!ro` for records NOT having this language code Translation ; `!` for NON-Translatable records ; `"` for Translatable records';
		$text['hint_3'] 			= 'Fill the filtering expression';
		$text['hint_4'] 			= 'Hints: `[]` for Empty ; `![]` for Non-Empty ; `%expr%` for containing expression ; `</> expr` for strip-tags containing expression';
		$text['hint_5'] 			= 'Hints: `%expr%` for containing expression';
		$text['hint_6'] 			= 'Hints: `[]` for Empty ; `![]` for Non-Empty ; `value` for containing the value';
		//--

		//--
		$outText = (string) $text[(string)$ykey];
		//--
		if((string)\trim((string)$outText) == '') {
			$outText = '[MISSING-TEXT@'.__CLASS__.']:'.(string)$ykey;
			\Smart::log_warning('Invalid Text Key: ['.$ykey.'] in: '.__METHOD__.'()');
		} //end if else
		//--
		if($y_escape_html !== false) {
			$outText = (string) \Smart::escape_html($outText);
		} //end if
		//--
		return (string) $outText;
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayHighlightCode($y_id) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordById($y_id);
		//--
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_warn(self::text('err_4'));
		} //end if
		//--
		if((string)$query['mode'] == 'text') {
			$type = 'plaintext'; // fix for text
		} else {
			$type = (string) $query['mode'];
		} //end if else
		//--
		$out = \SmartViewHtmlHelpers::html_jsload_editarea();
		$out .= \SmartViewHtmlHelpers::html_jsload_hilitecodesyntax('body', 'light');
		$out .= '<div style="text-align:left;">';
		$out .= '<h3>Code Preview: '.\Smart::escape_html($query['name']).' :: '.\Smart::escape_html($query['id']).'</h3>';
		$out .= '<pre><code class="syntax" data-syntax="'.\Smart::escape_html($type).'" id="code-view-area">'.\Smart::escape_html((string)\base64_decode((string)$query['code'])).'</code></pre>';
		$out .= '</div>';
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayHighlightData($y_id) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordById($y_id);
		//--
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_warn(self::text('err_4'));
		} //end if
		//--
		$type = 'yaml';
		//--
		$out = \SmartViewHtmlHelpers::html_jsload_editarea();
		$out .= \SmartViewHtmlHelpers::html_jsload_hilitecodesyntax('body', 'dark');
		$out .= '<div style="text-align:left;">';
		$out .= '<h3>Data Preview: '.\Smart::escape_html($query['name']).' :: '.\Smart::escape_html($query['id']).'</h3>';
		$out .= '<pre><code class="syntax" data-syntax="'.\Smart::escape_html($type).'" id="data-view-area">'.\Smart::escape_html((string)\base64_decode((string)$query['data'])).'</code></pre>';
		$out .= '</div>';
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayRecord($y_id, $y_disp, $y_lang='') {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDetailsById($y_id);
		//--
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_warn(self::text('err_4'));
		} //end if
		//--
		$action_code = 'record-view-tab-code';
		//--
		$xtra_args = '';
		switch((string)$y_disp) {
			case 'code':
				$selected_tab = '1';
				if((string)$y_lang != '') {
					$xtra_args = '&translate='.\Smart::escape_url((string)$y_lang);
				} //end if
				break;
			case 'yaml':
				$selected_tab = '2';
				break;
			case 'info':
				$selected_tab = '3';
				break;
			case 'media':
				$selected_tab = '4';
				break;
			case 'props':
			default:
				$selected_tab = '0';
		} //end switch
		//--
		if(self::testIsSegmentPage($query['id'])) {
			$draw_name = '<span style="color:#4D5774">'.\Smart::escape_html($query['name']).'<span>';
		} else {
			$draw_name = \Smart::escape_html($query['name']);
		} //end if else
		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$out = '';
	//	$out .= \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_jsload_htmlarea(''); // {{{SYNC-PAGEBUILDER-HTML-WYSIWYG}}}
		$out .= '<link href="lib/js/jquery/jsonview/jquery.json-viewer.css" type="text/css" rel="stylesheet">';
		$out .= '<script src="lib/js/jquery/jsonview/jquery.json-viewer.js"></script>';
		$out .= \SmartViewHtmlHelpers::html_jsload_editarea();
		$out .= '<script>'.\SmartViewHtmlHelpers::js_code_init_away_page('The changes will be lost !').'</script>';
		$out .= \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-record.mtpl.htm',
			[
				'RECORD-ID'			=> (string) \Smart::escape_html($query['id']),
				'RECORD-NAME' 		=> (string) $draw_name,
				'RECORD-TYPE' 		=> (string) $query['mode'],
				'BUTTONS-CLOSE' 	=> (string) '<input type="button" value="'.\Smart::escape_html($translator_window->text('button_close')).'" class="ux-button ux-button-dark" onClick="smartJ$Browser.CloseModalPopUp(); return false;">',
				'TAB-TXT-PROPS'		=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/props.svg'.'" alt="'.self::text('tab_props').'" title="'.self::text('tab_props').'">'.'&nbsp;'.self::text('tab_props'),
				'TAB-LNK-PROPS'		=> (string) self::composeUrl('op=record-view-tab-props&id='.\Smart::escape_url($query['id'])),
				'TAB-TXT-CODE'		=> (string) self::getImgForCodeType($query['id'], $query['mode'], 'tabs').'&nbsp;'.self::text('tab_code'),
				'TAB-LNK-CODE'		=> (string) self::composeUrl('op='.$action_code.'&id='.\Smart::escape_url($query['id']).$xtra_args),
				'TAB-TXT-DATA'		=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-data.svg'.'" alt="'.self::text('record_data').' '.self::text('record_runtime').'" title="'.self::text('record_data').' '.self::text('record_runtime').'">'.'&nbsp;'.self::text('tab_data'),
				'TAB-LNK-DATA'		=> (string) self::composeUrl('op=record-view-tab-data&id='.\Smart::escape_url($query['id'])),
				'TAB-TXT-INFO'		=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/info.svg'.'" alt="'.self::text('tab_info').'" title="'.self::text('tab_info').'">'.'&nbsp;'.self::text('tab_info'),
				'TAB-LNK-INFO'		=> (string) self::composeUrl('op=record-view-tab-info&id='.\Smart::escape_url($query['id'])),
				'TAB-TXT-MEDIA'		=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/media.svg'.'" alt="'.self::text('tab_media').'" title="'.self::text('tab_media').'">'.'&nbsp;'.self::text('tab_media'),
				'TAB-LNK-MEDIA'		=> (string) self::composeUrl('op=record-view-tab-media&id='.\Smart::escape_url($query['id'])),
				'JS-TABS'			=> (string) '<script>smartJ$UI.TabsInit(\'tabs\', '.(int)$selected_tab.', false);</script>'
			]
		);
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// view or display form entry for PROPS
	// $y_mode :: 'list' | 'form'
	public static function ViewFormProps($y_id, $y_mode) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordPropsById($y_id);
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_error('FormView Props // Invalid ID');
		} //end if
		//--
		if(self::testIsSegmentPage($query['id'])) {
			$arr_pmodes = array('html' => 'HTML Code', 'markdown' => 'Markdown Code', 'text' => 'Text / Plain', 'settings' => 'Data / Settings');
		} else {
			$arr_pmodes = array('html' => 'HTML Code', 'markdown' => 'Markdown Code', 'text' => 'Text / Plain', 'raw' => 'Raw Code');
		} //end if else
		//--
		$arr_refs = array();
		$q_refs = \Smart::json_decode((string)$query['ref']);
		if(!\is_array($q_refs)) {
			$q_refs = array();
		} //end if
		foreach($q_refs as $key => $val) {
			if(!\is_array($val)) {
				$val = (string) \trim((string)$val);
				if((string)$val != '') {
					if(!\in_array((string)$val, $arr_refs)) {
						$arr_refs[] = (string) $val;
					} //end if
				} //end if
			} //end if
		} //end if
		$q_refs = null;
		$arr_refs = (array) \Smart::array_sort((array)$arr_refs, 'natsort');
		//--
		$is_subsegment = false;
		if(\Smart::array_size($arr_refs) > 0) {
			$is_subsegment = true;
		} //end if
		//--
		$arr_xrefs = [];
		$q_refs = \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsByRef($y_id);
		for($i=0; $i<\Smart::array_size($q_refs); $i++) {
			if((string)$q_refs[$i]['id'] != '') {
				if(!\in_array((string)$q_refs[$i]['id'], $arr_xrefs)) {
					$arr_xrefs[] = (string) $q_refs[$i]['id'];
				} //end if
			} //end if
		} //end if
		$q_refs = null;
		$have_childs = false;
		if(\Smart::array_size($arr_xrefs) > 0) {
			$have_childs = true;
		} //end if
		//--
		$bttns = '';
		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$arr_tags = \Smart::json_decode((string)$query['tags']);
		if(\Smart::array_type_test($arr_tags) != '1') {
			$arr_tags = []; // must be array non-associative
		} //end if
		//--
		if((string)$y_mode == 'form') {
			//--
			$chk_reset_transl = '<input type="checkbox" name="frm[reset-translations]" value="all" onchange="if(jQuery(this).is(\':checked\')) { smartJ$Browser.AlertDialog(\'<span style=&quot;font-weight:bold; color:#FF5500;&quot;>If this checkbox is checked will reset (erase) all the PageBuilder Translations for this Object when you save it. Cannot be Undone.</span>\', function(){ jQuery(\'#warn-lang-reset\').empty().text(\'NOTICE: All Translations for this PageBuilder Object will be erased on Save.\'); }, \'Reset All Translations for this PageBuilder Object\', 550, 175); } else { jQuery(\'#warn-lang-reset\').empty(); }">';
			//--
			$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-save.svg'.'" alt="'.self::text('save').'" title="'.self::text('save').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('page_form_props', self::composeUrl('op=record-edit-do&id='.\Smart::escape_url($query['id']))).'">';
			$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-back.svg'.'" alt="'.self::text('cancel').'" title="'.self::text('cancel').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_code_ui_confirm_dialog('<h3>'.self::text('msg_unsaved').'</h3>'.'<br>'.'<b>'.\Smart::escape_html($translator_window->text('confirm_action')).'</b>', 'smartJ$Browser.LoadElementContentByAjax('."jQuery('#adm-page-props').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-props&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');").'">';
			//--
			$fld_name = '<input type="text" name="frm[name]" value="'.\Smart::escape_html($query['name']).'" size="70" maxlength="150" autocomplete="off" placeholder="'.self::text('name').'" required>';
			//--
			if(((string)$query['mode'] == 'raw') OR ((string)$query['mode'] == 'settings')) { // raw or settings cannot be changed to other modes !
				unset($arr_pmodes['html']);
				unset($arr_pmodes['markdown']);
				unset($arr_pmodes['text']);
				$fld_pmode = \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('pmode', $query['mode'], 'form', $arr_pmodes, 'frm[mode]', '150/0', '', 'no', 'no');
			} else {
				unset($arr_pmodes['raw']);
				unset($arr_pmodes['settings']);
				$fld_pmode = \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('pmode', $query['mode'], 'form', $arr_pmodes, 'frm[mode]', '150/0', '', 'no', 'no');
			} //end if else
			//--
			$fld_ctrl = self::drawFieldCtrl($query['ctrl'], $is_subsegment, 'form', 'frm[ctrl]');
			$fld_active = \SmartViewHtmlHelpers::html_selector_true_false('frm[active]', $query['active']);
			$fld_auth = \SmartViewHtmlHelpers::html_selector_true_false('frm[auth]', $query['auth']);
			$fld_trans = \SmartViewHtmlHelpers::html_selector_true_false('frm[translations]', $query['translations']);
			//--
			$fld_special = '<input type="text" name="frm[special]" value="'.\Smart::escape_html((int)$query['special']).'" size="10" placeholder="0..999999999">';
			$fld_tags = '<input id="the-tags" type="text" name="frm[tags]" value="" size="70" placeholder="'.self::text('tags').'">';
			//--
			if(self::testIsSegmentPage($query['id'])) {
				$fld_area = '<input type="text" name="frm[layout]" value="'.\Smart::escape_html($query['layout']).'" size="35" maxlength="75" autocomplete="off">';
				$fld_template = '';
			} else {
				$fld_area = '';
				$fld_template = (string) self::drawFieldLayoutPages($query['mode'], 'form', $query['layout'], 'frm[layout]');
			} //end if else
			//--
			$extra_form_start = '<form class="ux-form" name="page_form_props" id="page_form_props" method="post" action="#" onsubmit="return false;"><input type="hidden" name="frm[form_mode]" value="props">';
			$extra_form_end = '</form>';
			$extra_scripts = '<script>smartJ$Browser.setFlag(\'PageAway\', false);</script>';
			$extra_scripts .= '<script>smartJ$UI.TabsActivate(\'tabs\', false);</script>';
			$extra_scripts .= '<script>smartJ$Browser.RefreshParent();</script>';
			//--
		} else {
			//--
			$chk_reset_transl = '';
			//--
			if(!\defined('\\SMART_PAGEBUILDER_DISABLE_DELETE')) {
				$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-delete.svg'.'" alt="'.self::text('ttl_del').'" title="'.self::text('ttl_del').'" style="cursor:pointer;" onClick="self.location=\''.\Smart::escape_js(self::composeUrl('op=record-delete&id='.\Smart::escape_url($query['id']))).'\';">';
				$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			} //end if
			$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-edit.svg'.'" alt="'.self::text('ttl_edt').'" title="'.self::text('ttl_edt').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#adm-page-props').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-edit-tab-props&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');".'">';
			$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-clone.svg'.'" alt="'.self::text('ttl_clone').'" title="'.self::text('ttl_clone').'" style="cursor:pointer;" onClick="self.location=\''.\Smart::escape_js(self::composeUrl('op=record-clone&id='.\Smart::escape_url($query['id']))).'\';">';
			if((string)$query['checksum'] != (string)$query['calc_checksum']) {
				$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$bttns .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$bttns .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/no-hash.svg'.'" alt="'.self::text('msg_invalid_cksum').'" title="'.self::text('msg_invalid_cksum').'" style="cursor:help;">';
			} //end if
			//--
			$fld_name = (string) \Smart::escape_html($query['name']);
			$fld_pmode = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('pmode', $query['mode'], 'list', $arr_pmodes);
			$fld_ctrl = (string) self::drawFieldCtrl($query['ctrl'], $is_subsegment, 'list');
			$fld_special = (string) \Smart::escape_html((int)$query['special']);
			$fld_active = (string) \SmartViewHtmlHelpers::html_selector_true_false('', $query['active']);
			$fld_auth = (string) \SmartViewHtmlHelpers::html_selector_true_false('', $query['auth']);
			$fld_trans = (string) \SmartViewHtmlHelpers::html_selector_true_false('', $query['translations']);
			//--
			$fld_tags = (string) '<span id="the-tags"></span>';
			//--
			if(self::testIsSegmentPage($query['id'])) {
				$fld_area = (string) \Smart::escape_html($query['layout']);
				$fld_template = '';
			} else {
				$fld_area = '';
				$fld_template = (string) self::drawFieldLayoutPages($query['mode'], 'list', $query['layout']);
			} //end if else
			//--
			$extra_form_start = '';
			$extra_form_end = '';
			$extra_scripts = '<script>smartJ$Browser.setFlag(\'PageAway\', true);</script>';
			$extra_scripts .= '<script>smartJ$UI.TabsActivate(\'tabs\', true);</script>';
			//--
		} //end if else
		//--
		$codetype = array();
		if($query['len_code'] > 0) {
			$codetype[] = self::text('record_code').'&nbsp;['.\Smart::escape_html(\SmartUtils::pretty_print_bytes((int)$query['len_code'],2)).']';
		} //end if
		if($query['len_data'] > 0) {
			$codetype[] = self::text('record_runtime').'&nbsp;['.\Smart::escape_html(\SmartUtils::pretty_print_bytes((int)$query['len_data'],2)).']';
		} //end if
		if(\Smart::array_size($codetype) > 0) {
			$codetype = (string) \str_replace(' ', '&nbsp;', (string)\implode('&nbsp;&nbsp;/&nbsp;&nbsp;', (array)$codetype));
		} else {
			$codetype = '';
		} //end if
		//--
		$arr_raw_langs = (array) \SmartTextTranslations::getListOfLanguages();
		$transl_arr = array();
		$show_translations = false;
		if(\Smart::array_size($arr_raw_langs) > 1) {
			$show_translations = true;
			$transl_arr = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsTranslationsById($y_id);
		} //end if
		if(\Smart::array_size($transl_arr) > 0) {
			for($i=0; $i<\Smart::array_size($transl_arr); $i++) {
				$transl_arr[$i] = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('', (string)$transl_arr[$i], 'list', (array)$arr_raw_langs);
			} //end if
		} //end if
		if((string)$query['mode'] == 'settings') {
			$show_translations = false;
		} //end if
		//--
		$transl_cnt = (int) \Smart::array_size($transl_arr);
		//--
		$the_template = self::MODULE_PATH.'libs/views/manager/view-record-frm-props.mtpl.htm';
		//--
		$out = \SmartMarkersTemplating::render_file_template(
			(string) $the_template,
			[
				'MODE' 						=> (string) $y_mode,
				'RECORD-ID' 				=> (string) $query['id'],
				'IS-SEGMENT' 				=> (string) self::testIsSegmentPage($query['id']),
				'IS-SUBSEGMENT' 			=> (string) $is_subsegment ? 1 : 0,
				'BUTTONS'					=> (string) $bttns,
				'CODE-TYPE'					=> (string) $codetype,
				'TEXT-NAME'					=> (string) self::text('name'),
				'FIELD-NAME' 				=> (string) $fld_name,
				'TEXT-CTRL'					=> (string) self::text('ctrl'),
				'FIELD-CTRL' 				=> (string) $fld_ctrl,
				'TEXT-PMODE'				=> (string) self::text('record_syntax'),
				'FIELD-PMODE' 				=> (string) $fld_pmode,
				'TEXT-SPECIAL'				=> (string) self::text('special'),
				'FIELD-SPECIAL'				=> (string) $fld_special,
				'TEXT-ACTIVE'				=> (string) self::text('active'),
				'FIELD-ACTIVE'				=> (string) $fld_active,
				'TEXT-AUTH'					=> (string) self::text('login'),
				'FIELD-AUTH'				=> (string) $fld_auth,
				'TEXT-TRANS'				=> (string) self::text('translatable'),
				'FIELD-TRANS'				=> (string) $fld_trans,
				'MODULE-PATH' 				=> (string) self::MODULE_PATH,
				'TEXT-TRANSLATIONS' 		=> (string) self::text('translations'),
				'SHOW-TRANSLATIONS' 		=> (int)    $show_translations,
				'COUNT-TRANSLATIONS' 		=> (int)    $transl_cnt,
				'ARR-TRANSLATIONS' 			=> (array)  $transl_arr,
				'IS-TRANSLATABLE' 			=> (int)    $query['translations'],
				'TEXT-RESET-TRANSL' 		=> (string) self::text('reset'),
				'FIELD-RESET-TRANSL' 		=> (string) $chk_reset_transl,
				'WARN-TRANSLATABLE' 		=> (string) self::text('warn_translations'),
				'TEXT-TEMPLATE'				=> (string) self::text('template'),
				'FIELD-TEMPLATE'			=> (string) $fld_template,
				'TEXT-AREA'					=> (string) self::text('area'),
				'FIELD-AREA'				=> (string) $fld_area,
				'TEXT-TAGS'					=> (string) self::text('tags'),
				'JSON-TAGS' 				=> (string) \Smart::json_encode((array)$arr_tags, false, false, true),
				'FIELD-TAGS'				=> (string) $fld_tags,
				'MODE-PAGETYPE' 			=> (string) $query['mode'],
				'TEXT-REFS' 				=> (string) self::text('refs'),
				'ARR-REFS' 					=> (array)  $arr_refs,
				'ARR-XREFS' 				=> (array)  $arr_xrefs,
				'NUM-REFS' 					=> (int) 	(\Smart::array_size($arr_refs) + \Smart::array_size($arr_xrefs)),
				'URL-REF' 					=> (string) self::composeUrl('op=record-view&id='),
			]
		);
		//--
		return (string) '<div id="adm-page-props" align="left">'.$extra_form_start.$out.$extra_form_end.'</div>'.$extra_scripts;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// view or display form entry for Markup Code
	// $y_mode :: 'list' | 'form'
	public static function ViewFormMarkupCode($y_id, $y_mode, $y_lang='') {
		//--
		if(((string)$y_lang == '') OR (\strlen($y_lang) != 2) OR (\SmartTextTranslations::validateLanguage($y_lang) !== true)) {
			$y_lang = '';
		} //end if
		//--
		if((string)$y_lang != '') {
			$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getTranslationCodeById($y_id, $y_lang);
		} else {
			$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordCodeById($y_id);
		} //end if else
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_error('FormView Code // Invalid ID');
		} //end if
		//--
		$arr_raw_langs = (array) \SmartTextTranslations::getListOfLanguages();
		$arr_langs = [];
		$first_lang = true;
		foreach($arr_raw_langs as $key => $val) {
			if($first_lang) {
				$key = ''; // make empty key for the first language as this will be the default
				$first_lang = false;
			} //end if
			$arr_langs[(string)$key] = (string) $val;
		} //end foreach
		//--
		$tselect = '';
		if((string)$y_mode == 'form') {
			$tselmode = '';
		} else {
			$tselmode = 'form';
		} //end if else
		if(\Smart::array_size($arr_langs) > 1) {
			$tselect = (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single(
				'language-select',
				(string) $y_lang,
				(string) $tselmode,
				(array) $arr_langs,
				'translate',
				'150/0',
				'onChange="var theSelLang = String(jQuery(this).val()); self.location = \''.\Smart::escape_js(self::composeUrl('op=record-view&sop=code&id='.\Smart::escape_url($query['id']))).'\' + \'&translate=\' + smartJ$Utils.escape_url(theSelLang);"', // $y_custom_js
				'no',
				'no',
				'',
				'#JS-UI#'
			);
		} //end if
		//--
		if($query['translations'] != 1) {
			$tselect = ''; // not translatable page
		} //end if
		//--
		$query['code'] = (string) \base64_decode((string)$query['code']);
		$query['data'] = (string) \base64_decode((string)$query['data']);
		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$query['code'] = (string) $query['code'];
		//--
		if(\defined('\\SMART_PAGEBUILDER_THEME_DARK') AND (\SMART_PAGEBUILDER_THEME_DARK === true)) {
			$theme_readonly = 'oceanic-next';
			$theme_editable = 'zenburn';
			$theme_mkdw_htmlsrc = 'uxm';
		} else {
			$theme_readonly = 'uxm';
			$theme_editable = 'uxw';
			$theme_mkdw_htmlsrc = 'oceanic-next';
		} //end if
		//--
		if(
			(\SmartAuth::test_login_privilege('admin') === true)
			OR
			(\SmartAuth::test_login_privilege('pagebuilder:edit') === true)
		) {
			//--
			if((string)$y_mode == 'form') {
				//--
				$out = '';
				//--
				if((string)$query['mode'] == 'settings') {
					//--
					$out .= '<center><div title="'.\Smart::escape_html($query['code']).'"><img src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-settings.svg" width="256" height="256" alt="Data / Settings Segment" title="Data / Settings Segment" style="opacity:0.7"></div></center>';
					//--
				} else {
					//-- EDITOR
					$out .= '<form name="page_form_html" id="page_form_html" method="post" action="#" onsubmit="return false;">';
					$out .= '<div id="code-editor" align="left">';
					if((string)$query['mode'] == 'raw') {
						$out .= '<span style="font-size:1.125rem; color:#FF7700"><b>&lt;<i>raw</i>&gt;</b>'.' - '.self::text('ttl_edtc').'</span>';
					} elseif((string)$query['mode'] == 'text') {
						$out .= '<span style="font-size:1.125rem; color:#007700"><b>&lt;<i>text</i>&gt;</b>'.' - '.self::text('ttl_edtc').'</span>';
					} elseif((string)$query['mode'] == 'markdown') {
						$out .= '<span style="font-size:1.125rem; color:#4D5774"><b>&lt;<i>markdown</i>&gt;</b>'.' - '.self::text('ttl_edtc').'</span>';
					} else { // html
						$out .= '<span style="font-size:1.125rem; color:#666699"><b>&lt;<i>html5</i>&gt;</b>'.' - '.self::text('ttl_edtc').'</span>';
					} //end if else
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= (string) $tselect;
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-save.svg'.'" alt="'.self::text('save').'" title="'.self::text('save').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('page_form_html', self::composeUrl('op=record-edit-do&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang))).'">';
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-back.svg'.'" alt="'.self::text('cancel').'" title="'.self::text('cancel').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_code_ui_confirm_dialog('<h3>'.self::text('msg_unsaved').'</h3>'.'<br>'.'<b>'.\Smart::escape_html($translator_window->text('confirm_action')).'</b>', 'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-editor').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');").'">';
					$out .= (string) self::getPreviewButtons((string)$query['id']);
					$out .= '</div>'."\n";
					$out .= '<input type="hidden" name="frm[form_mode]" value="code">';
					if((string)$y_lang != '') {
						$out .= '<input type="hidden" name="frm[language]" value="'.\Smart::escape_html((string)$y_lang).'">';
					} //end if
					if((string)$query['mode'] == 'raw') {
						$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', 'frm[code]', $query['code'], 'text', true, '90vw', '70vh', true, (string)$theme_editable);
					} elseif((string)$query['mode'] == 'text') {
						$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', 'frm[code]', $query['code'], 'text', true, '90vw', '70vh', true, (string)$theme_editable);
					} elseif((string)$query['mode'] == 'markdown') {
						$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', 'frm[code]', $query['code'], 'markdown', true, '90vw', '70vh', true, (string)$theme_editable);
					} else {
					//	$out .= \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_js_htmlarea('pbld_code_htmleditor', 'frm[code]', $query['code'], '90vw', '70vh', true); // {{{SYNC-PAGEBUILDER-HTML-WYSIWYG}}}
						$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', 'frm[code]', $query['code'], 'html', true, '90vw', '70vh', true, (string)$theme_editable);
					} //end if else
					$out .= '<div align="left">';
					if((string)$query['mode'] == 'raw') {
						$out .= '<span style="font-size:1.125rem; color:#FF7700"><b>&lt;/<i>raw</i>&gt;</b></span>';
					} elseif((string)$query['mode'] == 'text') {
						$out .= '<span style="font-size:1.125rem; color:#007700"><b>&lt;/<i>text</i>&gt;</b></span>';
					} elseif((string)$query['mode'] == 'markdown') {
						$out .= '<span style="font-size:1.125rem; color:#4D5774"><b>&lt;/<i>markdown</i>&gt;</b></span>';
					} else { // html
						$out .= '<span style="font-size:1.125rem; color:#666699"><b>&lt;/<i>html5</i>&gt;</b></span>';
					} //end if else
					$out .= '</div>'."\n";
					$out .= "\n".'</form>'."\n";
					$out .= '<script>smartJ$Browser.setFlag(\'PageAway\', false);</script>';
					$out .= '<script>smartJ$UI.TabsActivate(\'tabs\', false);</script>';
					$out .= '<script>smartJ$Browser.RefreshParent();</script>'; // not necessary
					//--
				} //end if else
				//--
			} else {
				//-- CODE VIEW
				$out = '';
				//--
				if((string)$query['mode'] == 'settings') {
					//--
					$out .= '<center><div title="'.\Smart::escape_html($query['code']).'"><img src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-settings.svg" width="256" height="256" alt="Data / Settings Segment" title="Data / Settings Segment" style="opacity:0.7"></div></center>';
					//--
				} else {
					//-- {{{SYNC-PAGEBUILDER-COMPARE-CODE-WITH-DATA-PLACEHOLDERS}}}
					$arr_extr_placeholders = array();
					if((string)\trim((string)$query['code']) != '') {
						$arr_extr_placeholders = (array) \SmartModExtLib\PageBuilder\Utils::extractPlaceholders((string)$query['code'], true);
					} //end if
					$arr_data_code_yaml = (array) (new \SmartYamlConverter(false))->parse((string)$query['data']); // do not log YAML errors
					$arr_placehold_orphans = [];
					if((!isset($arr_data_code_yaml['RENDER'])) OR (\Smart::array_size($arr_data_code_yaml['RENDER']) <= 0)) {
						$arr_data_code_yaml['RENDER'] = array();
					} //end if
					foreach($arr_extr_placeholders as $placehold_key => $placehold_val) {
						$placehold_val = (string) \trim((string)$placehold_val);
						$placehold_val = (string) \substr((string)$placehold_val, 3, -3);
						if(\preg_match((string)self::REGEX_MARKER, (string)$placehold_val)) {
							if(!\array_key_exists((string)$placehold_val, (array)$arr_data_code_yaml['RENDER'])) {
								$arr_placehold_orphans[(string)$placehold_val] = 1;
							} //end if
						} elseif(\stripos((string)$placehold_val, 'TEMPLATE@') === 0) { // exclude special keys: `@` and `TEMPLATE@*`
							$arr_placehold_orphans[(string)$placehold_val] = 2;
						} elseif((string)$placehold_val == '@') { // exclude special keys: `@` and `TEMPLATE@*`
							$arr_placehold_orphans[(string)$placehold_val] = 3;
						} else {
							$arr_placehold_orphans[(string)$placehold_val] = 0;
						} //end if else
					} //end foreach
					$placehold_key = null; // free mem
					$placehold_val = null; // free mem
					$arr_data_code_yaml = null; // free mem
					$arr_extr_placeholders = null; // free mem
					$warn_placeholders = '';
					if(\Smart::array_size($arr_placehold_orphans) > 0) {
						$warn_placeholders .= '<ul>';
						foreach($arr_placehold_orphans as $orphans_key => $orphans_val) {
							$warn_placeholders .= '<li>{{:'.\Smart::escape_html((string)$orphans_key).':}}';
							switch((int)$orphans_val) {
								case 1:
									$warn_placeholders .= ' - UNDEFINED Placeholder (Missing in Data context)';
									break;
								case 2:
								case 3:
									$warn_placeholders .= ' - INVALID Placeholder (Reserved Syntax)';
									break;
								case 0:
								default:
									$warn_placeholders .= ' - INVALID Placeholder (WRONG Syntax)';
							} //end switch
							$warn_placeholders .= '</li>';
						} //end foreach
						$orphans_key = null;
						$orphans_val = null;
						$warn_placeholders .= '</ul>';
						$out .= (string) \SmartComponents::operation_warn('WARNING: Undefined or Invalid Placeholders detected:'.'<div style="max-height:70px; overflow:auto;">'.$warn_placeholders.'</div>', '92%'); // {{{SYNC-PAGEBUILDER-NOTIFICATIONS-HEIGHT}}}
					} //end if
					$warn_placeholders = null; // free mem
					$arr_placehold_orphans = null; // free mem
					//--
					$out .= '<div id="code-viewer" align="left" style="margin-bottom:5px;">';
					if((string)$query['mode'] == 'raw') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;raw&gt;</b></span>';
					} elseif((string)$query['mode'] == 'text') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;text&gt;</b></span>';
					} elseif((string)$query['mode'] == 'markdown') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;markdown&gt;</b></span>';
					} else {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;html5&gt;</b></span>';
					} //end if else
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= (string) $tselect;
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-edit.svg'.'" alt="'.self::text('ttl_edtc').'" title="'.self::text('ttl_edtc').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-edit-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
					//--
					if(((string)$y_mode == 'codeview') OR ((string)$y_mode == 'codesrcview')) {
						//--
						if((string)$query['mode'] == 'raw') {
							//--
							$out .= '</div>'."\n";
							$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', '', $query['code'], 'text', false, '90vw', '70vh', true, (string)$theme_readonly);
							//--
						} elseif((string)$query['mode'] == 'text') {
							//--
							$out .= '</div>'."\n";
							$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', '', $query['code'], 'text', false, '90vw', '70vh', true, (string)$theme_readonly);
							//--
						} elseif((string)$query['mode'] == 'markdown') {
							//--
							$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview.svg'.'" alt="'.self::text('preview').'" title="'.self::text('preview').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-preview-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
							//--
							$codemode = 'markdown';
							//--
							if((string)$y_mode == 'codesrcview') {
								//--
								$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
								$out .= '<img alt="'.self::text('record_sytx_mkdw').' '.self::text('record_source').'" title="'.self::text('record_sytx_mkdw').' '.self::text('record_source').'" src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-markdown.svg'.'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
								//--
							} //end if
							//--
							if(\SmartModExtLib\PageBuilder\Utils::displayValidationErrors() === true) {
								//--
								$arr_mkdw_render_notices = (array) \SmartModExtLib\PageBuilder\Utils::getRenderedMarkdownNotices((string)$query['code']);
								if((string)$arr_mkdw_render_notices['validator'] != '') {
									$out .= '<br><div style="float:left; cursor:help;" title="Markdown/HTML Code Validation: '.\Smart::escape_html((string)$arr_mkdw_render_notices['validator'].' # Warnings/Errors: '.(((int)\Smart::array_size((array)$arr_mkdw_render_notices['notices']) > 0) ? 'YES' : 'NO')).'"><i class="sfi sfi-html-five"></i></div>';
									$out .= (string) \SmartModExtLib\PageBuilder\Utils::renderNotices((array)$arr_mkdw_render_notices['notices'], 'Markdown Html Rendering', 'Html Validation Warnings/Errors');
								} //end if
								$arr_mkdw_render_notices = null;
								//--
							} //end if
							//--
							if((string)$y_mode == 'codesrcview') {
								//--
								$theme_readonly = (string) $theme_mkdw_htmlsrc;
								$codemode = 'html';
								//--
								$query['code'] = \SmartModExtLib\PageBuilder\Utils::renderMarkdown((string)$query['code'], '', '', false); // render on the fly ; use NULL for options to dissalow override by SMART_PAGEBUILDER_HTML_VALIDATOR ; no need for validation here ; do not log notices
								//--
							} //end if
							$out .= '</div>'."\n";
							$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', '', $query['code'], (string)$codemode, false, '90vw', '70vh', true, (string)$theme_readonly);
							//--
						} else { // html
							//--
							$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview.svg'.'" alt="'.self::text('preview').'" title="'.self::text('preview').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-preview-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
							//--
							if(\SmartModExtLib\PageBuilder\Utils::displayValidationErrors() === true) {
								//--
								$validation_html = (string) \SmartModExtLib\PageBuilder\Utils::fixPageBuilderCodeBeforeValidation((string)$query['code']);
								$htmlparser = new \SmartHtmlParser((string)$validation_html, true, (string)\SmartModExtLib\PageBuilder\Utils::htmlValidatorOption(), false);
								$validation_html = null;
								$htmlparser->get_clean_html();
								$validerrs = (string) trim((string)$htmlparser->getValidationErrors());
								$out .= '<br><div style="float:left; cursor:help;" title="HTML Code Validation: '.\Smart::escape_html((string)\SmartModExtLib\PageBuilder\Utils::htmlValidatorOption().' # Warnings/Errors: '.(((string)$validerrs != '') ? 'YES' : 'NO')).'"><i class="sfi sfi-html-five"></i></div>';
								if((string)$validerrs != '') {
									$out .= (string) \SmartModExtLib\PageBuilder\Utils::renderNotices([ 0 => (array)explode("\n", (string)$validerrs) ], 'Html Code', 'Html Validation Warnings/Errors');
								} //end if
								$validerrs = null;
								$htmlparser = null;
								//--
							} //end if
							//--
							$out .= '</div>'."\n";
							$out .= \SmartViewHtmlHelpers::html_js_editarea('pbld_code_editor', '', $query['code'], 'html', false, '90vw', '70vh', true, (string)$theme_readonly);
							//--
						} //end if else
						//--
					} else { // view
						//--
						if(((string)$query['mode'] == 'raw') OR ((string)$query['mode'] == 'text')) {
							$out .= '</div>'."\n";
							$out .= \SmartComponents::operation_notice('FormView HTML Source // Raw or Text Pages does not have this feature ...', '100%');
						} else { // markdown / html
							$img_view_html_code = 'syntax-html.svg';
							$txt_view_html_code = self::text('record_source');
							$txt_view_mkdw_code = self::text('record_source');
							if((string)$query['mode'] == 'markdown') {
								$img_view_html_code = 'op-view-code.svg';
								$txt_view_html_code = self::text('record_code');
								$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
								$out .= '<img alt="'.self::text('record_sytx_mkdw').' '.$txt_view_mkdw_code.'" title="'.self::text('record_sytx_mkdw').' '.$txt_view_mkdw_code.'" src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-markdown.svg'.'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-code&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
							} //end if
							$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/'.$img_view_html_code.'" alt="'.self::text('record_sytx_html').' '.$txt_view_html_code.'" title="'.self::text('record_sytx_html').' '.$txt_view_html_code.'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#code-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-code&mode=codesrcview&id='.\Smart::escape_url($query['id']).'&translate='.\Smart::escape_url($y_lang)))."', 'GET', 'html');".'">';
							$out .= '</div>'."\n";
							$the_editor_styles = '';
							if((string)$query['mode'] == 'markdown') {
							//	$the_editor_styles = '<link rel="stylesheet" type="text/css" href="lib/css/plugins/markdown.css">'; // includded in lib/core/plugins/css/app{.pak}.css
								$query['code'] = \SmartModExtLib\PageBuilder\Utils::renderMarkdown((string)$query['code'], '', '', false, true); // render on the fly ; use NULL for options to dissalow override by SMART_PAGEBUILDER_HTML_VALIDATOR ; no need for validation here ; do not log notices
							} else {
							//	$the_editor_styles = '<link rel="stylesheet" type="text/css" href="modules/mod-auth-admins/views/js/html-editor/cleditor/jquery.cleditor.smartframeworkcomponents.css">'; // {{{SYNC-PAGEBUILDER-HTML-WYSIWYG}}}
								$query['code'] = (string) \SmartModExtLib\PageBuilder\Utils::fixSafeCode((string)$query['code']); // {{{SYNC-PAGEBUILDER-HTML-SAFETY}}} avoid PHP code + cleanup XHTML tag style
							} //end if else
							$the_website_styles = "\n".'<style>'."\n".\SmartComponents::app_default_css()."\n".'</style>'."\n";
							$the_website_styles .= '<link rel="stylesheet" type="text/css" href="lib/css/toolkit/sf-icons.css">';
							$the_website_styles .= '<link rel="stylesheet" type="text/css" href="lib/css/app.pak.css">';
							$the_website_styles .= '<link rel="stylesheet" type="text/css" href="lib/core/css/app.pak.css">';
							$the_website_styles .= '<link rel="stylesheet" type="text/css" href="lib/core/plugins/css/app.pak.css">'; // includes lib/css/plugins/markdown.css
							$out .= \SmartViewHtmlHelpers::html_js_preview_iframe('pbld_code_editor', '<!DOCTYPE html><html><head>'.$the_website_styles.$the_editor_styles.'</head><body style="background:#FFFFFF;">'.$query['code'].'</body></html></html>', $y_width='90vw', $y_height='70vh');
						} //end if else
						//--
					} //end if else
					//--
					$out .= '<div align="left">';
					if((string)$query['mode'] == 'raw') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;/raw&gt;</b></span>';
					} elseif((string)$query['mode'] == 'text') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;/text&gt;</b></span>';
					} elseif((string)$query['mode'] == 'markdown') {
						$out .= '<span style="font-size:1.125rem;"><b>&lt;/markdown&gt;</b></span>';
					} else { // html
						$out .= '<span style="font-size:1.125rem;"><b>&lt;/html5&gt;</b></span>';
					} //end if else
					$out .= '</div>'."\n";
					$out .= '<script>smartJ$Browser.setFlag(\'PageAway\', true); smartJ$UI.TabsActivate(\'tabs\', true);</script>';
					//--
				} //end if else
				//--
			} //end if else
			//--
		} else {
			//--
			if((string)$y_mode == 'form') {
				$msg = self::text('msg_no_priv_edit');
			} else {
				$msg = self::text('msg_no_priv_read');
			} //end if else
			//--
			$out = \SmartComponents::operation_notice($msg);
			//--
		} //end if else
		//--
		return $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// view or display form entry for YAML Code
	// $y_mode :: 'list' | 'form'
	public static function ViewFormYamlData($y_id, $y_mode) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDataById($y_id);
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_error('FormView YAML Data // Invalid ID');
		} //end if
		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$query['code'] = (string) \base64_decode((string)$query['code']);
		$query['data'] = (string) \base64_decode((string)$query['data']);
		//--
		if(\defined('\\SMART_PAGEBUILDER_THEME_DARK') AND (\SMART_PAGEBUILDER_THEME_DARK === true)) {
			$theme_readonly = 'oceanic-next';
			$theme_editable = 'zenburn';
		} else {
			$theme_readonly = 'uxm';
			$theme_editable = 'uxw';
		} //end if
		//--
		if(
			(\SmartAuth::test_login_privilege('admin') === true)
			OR
			(
				(\SmartAuth::test_login_privilege('pagebuilder:edit') === true)
				AND
				(\SmartAuth::test_login_privilege('pagebuilder:data-edit') === true)
			)
		) {
			//--
			if((string)$y_mode == 'form') {
				//-- CODE EDITOR
				$out = '';
				$out .= '<form class="ux-form" name="page_form_yaml" id="page_form_yaml" method="post" action="#" onsubmit="return false;">';
				$out .= '<div align="left" id="yaml-editor"><span style="font-size:1.125rem; color:#4D5774"><b>&lt;<i>yaml</i>&gt;</b>'.' - '.self::text('ttl_edtac').'</span>';
				$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-save.svg'.'" alt="'.self::text('save').'" title="'.self::text('save').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('page_form_yaml', self::composeUrl('op=record-edit-do&id='.\Smart::escape_url($query['id']))).'">';
				$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-back.svg'.'" alt="'.self::text('cancel').'" title="'.self::text('cancel').'" style="cursor:pointer;" onClick="'.\SmartViewHtmlHelpers::js_code_ui_confirm_dialog('<h3>'.self::text('msg_unsaved').'</h3>'.'<br>'.'<b>'.\Smart::escape_html($translator_window->text('confirm_action')).'</b>', 'smartJ$Browser.LoadElementContentByAjax('."jQuery('#yaml-editor').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-data&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');").'">';
				$out .= (string) self::getPreviewButtons((string)$query['id']);
				$out .= '</div>'."\n";
				$out .= '<input type="hidden" name="frm[form_mode]" value="yaml">';
				$out .= \SmartViewHtmlHelpers::html_js_editarea('record_sytx_yaml', 'frm[data]', $query['data'], 'yaml', true, '90vw', '70vh', true, (string)$theme_editable); // OK.new
				$out .= '<div align="left"><span style="font-size:1.125rem; color:#4D5774"><b>&lt;/<i>yaml</i>&gt;</b></span></div>'."\n";
				$out .= "\n".'</form>'."\n";
				$out .= '<script>smartJ$Browser.setFlag(\'PageAway\', false);</script>';
				$out .= '<script>smartJ$UI.TabsActivate(\'tabs\', false);</script>';
				$out .= '<script>smartJ$Browser.RefreshParent();</script>'; // not necessary
				//--
			} else {
				//-- CODE VIEW
				$ymp = new \SmartYamlConverter(false); // do not log YAML errors
				$yaml = (array) $ymp->parse((string)$query['data']);
				$yerr = (string) $ymp->getError();
				$ymp = null;
				//--
				$out = '';
				if($yerr) {
					//--
					$out .= (string) \SmartComponents::operation_error('YAML Parse ERROR: '.\Smart::escape_html($yerr), '92%');
					//--
				} else {
					//--
					if((string)$query['mode'] == 'settings') {
						//--
						if(\Smart::array_size($yaml) <= 0) {
							$out .= (string) \SmartComponents::operation_warn('YAML Structure WARNING: Empty definition for settings', '92%');
						} elseif(\Smart::array_size($yaml['SETTINGS']) <= 0) {
							$out .= (string) \SmartComponents::operation_warn('YAML Structure WARNING: Invalid `SETTINGS` definition', '92%');
						} //end if else
						//--
					} else {
						//-- {{{SYNC-PAGEBUILDER-COMPARE-CODE-WITH-DATA-PLACEHOLDERS}}}
						$arr_extr_placeholders = array();
						if((string)\trim((string)$query['code']) != '') {
							$arr_extr_placeholders = (array) \SmartModExtLib\PageBuilder\Utils::extractPlaceholders((string)$query['code']);
						} //end if
						$arr_data_code_yaml = (array) (new \SmartYamlConverter(false))->parse((string)$query['data']); // do not log YAML errors
						$arr_placehold_orphans = [];
						if(isset($arr_data_code_yaml['RENDER']) AND (\Smart::array_size($arr_data_code_yaml['RENDER']) > 0)) {
							foreach($arr_data_code_yaml['RENDER'] as $datactx_key => $datactx_val) {
								$datactx_key = (string) \trim((string)$datactx_key);
								if((string)$datactx_key != '') {
									if(\preg_match((string)self::REGEX_MARKER, (string)$datactx_key)) {
										if(!\in_array((string)'{{:'.$datactx_key.':}}', (array)$arr_extr_placeholders)) {
											$arr_placehold_orphans[(string)$datactx_key] = 1;
										} //end if
									} elseif(\stripos((string)$datactx_key, 'TEMPLATE@') === 0) { // exclude special keys: `@` and `TEMPLATE@*`
										if((string)\substr((string)$query['id'], 0, 1) == '#') {
											$arr_placehold_orphans[(string)$datactx_key] = 2;
										} //end if
									} elseif((string)$datactx_key == '@') { // exclude special keys: `@` and `TEMPLATE@*`
										// OK
									} else {
										$arr_placehold_orphans[(string)$datactx_key] = 0;
									} //end if else
								} //end if
							} //end foreach
						} //end if
						$datactx_key = null; // free mem
						$datactx_val = null; // free mem
						$arr_data_code_yaml = null; // free mem
						$arr_extr_placeholders = null; // free mem
						$warn_placeholders = '';
						if(\Smart::array_size($arr_placehold_orphans) > 0) {
							$warn_placeholders .= '<ul>';
							foreach($arr_placehold_orphans as $orphans_key => $orphans_val) {
								$warn_placeholders .= '<li>`'.\Smart::escape_html((string)$orphans_key).'`';
								switch((int)$orphans_val) {
									case 1:
										$warn_placeholders .= ' - UNUSED Key (Missing in Code context)';
										break;
									case 2:
										$warn_placeholders .= ' - INVALID Key (Reserved just for Pages)';
										break;
									case 0:
									default:
										$warn_placeholders .= ' - INVALID Key (WRONG Syntax)';
								} //end switch
								$warn_placeholders .= '</li>';
							} //end foreach
							$orphans_key = null;
							$orphans_val = null;
							$warn_placeholders .= '</ul>';
							$out .= (string) \SmartComponents::operation_warn('WARNING: Unused or Invalid Keys detected:'.'<div style="max-height:70px; overflow:auto;">'.$warn_placeholders.'</div>', '92%'); // {{{SYNC-PAGEBUILDER-NOTIFICATIONS-HEIGHT}}}
						} //end if
						$warn_placeholders = null; // free mem
						$arr_placehold_orphans = null; // free mem
						//--
						if(\Smart::array_size($yaml) > 0) {
							if((string)$query['mode'] == 'raw') {
								if(\Smart::array_size($yaml['PROPS']) <= 0) {
									$out .= (string) \SmartComponents::operation_warn('YAML Structure WARNING: Invalid `PROPS` definition', '92%');
								} //end if
							} else {
								if(\Smart::array_size($yaml['RENDER']) <= 0) {
									$out .= (string) \SmartComponents::operation_warn('YAML Structure WARNING: Invalid `RENDER` definition', '92%');
								} //end if
							} //end if else
						} //end if
						//--
					} //end if else
					//--
				} //end if
				//--
				$out .= '<div align="left" id="yaml-viewer" style="margin-bottom:5px;"><span style="font-size:1.125rem;"><b>&lt;yaml&gt;</b></span>';
				$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-edit.svg'.'" alt="'.self::text('ttl_edtac').'" title="'.self::text('ttl_edtac').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#yaml-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-edit-tab-data&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');".'">';
				$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				if((string)$y_mode == 'preview') {
					$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-data.svg'.'" alt="'.self::text('record_runtime').' '.self::text('record_source').'" title="'.self::text('record_runtime').' '.self::text('record_source').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#yaml-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-view-tab-data&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');".'">';
				} else {
					$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview.svg'.'" alt="'.self::text('preview').'" title="'.self::text('preview').'" style="cursor:pointer;" onClick="'.'smartJ$Browser.LoadElementContentByAjax('."jQuery('#yaml-viewer').parent().prop('id'), 'lib/framework/img/loading-bars.svg', '".\Smart::escape_js(self::composeUrl('op=record-preview-tab-data&id='.\Smart::escape_url($query['id'])))."', 'GET', 'html');".'">';
				} //end if else
				$out .= '</div>'."\n";
				if((string)$y_mode == 'preview') {
					$out .= '<style> #yaml-json-renderer, #yaml-json-renderer * { font-size: 0.8125rem !important; } </style><div id="yaml-json-renderer" style="width:88vw; height: 70vh; border: 1px solid #ECECEC; padding: 0.5em 1.5em; overflow:auto;"></div><script>(function(){ var yamlData = \''.\Smart::escape_js(\Smart::json_encode($yaml, false, false, true)).'\'; var yamlJsonData = null; try { yamlJsonData = JSON.parse(yamlData); } catch(err){ jQuery(\'#yaml-json-renderer\').html(\'<div id="operation_error">\' + \'ERROR Parsing YAML to JSON Data: \' + err + \'</div>\'); return; } jQuery(\'#yaml-json-renderer\').css({\'white-space\':\'pre\'}).jsonViewer(yamlJsonData, {collapsed:false, withQuotes:false}); })();</script>';
				} else { // view
					$out .= \SmartViewHtmlHelpers::html_js_editarea('record_sytx_yaml', '', $query['data'], 'yaml', false, '90vw', '70vh', true, (string)$theme_readonly); // OK.new
				} //end if else
				$out .= '<div align="left"><span style="font-size:1.125rem;"><b>&lt;/yaml&gt;</b></span></div>'."\n";
				$out .= '<script>smartJ$Browser.setFlag(\'PageAway\', true); smartJ$UI.TabsActivate(\'tabs\', true);</script>';
				//--
			} //end if else
			//--
		} else {
			//--
			if((string)$y_mode == 'form') {
				$msg = self::text('msg_no_priv_edit');
			} else {
				$msg = self::text('msg_no_priv_read');
			} //end if else
			//--
			$out = \SmartComponents::operation_notice($msg);
			//--
		} //end if else
		//--
		return $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// view or display form entry for INFO
	// $y_mode :: 'list'
	public static function ViewFormInfo($y_id, $y_mode) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordInfById($y_id);
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_error('FormView Info // Invalid ID');
		} //end if
		//--
		$the_template = self::MODULE_PATH.'libs/views/manager/view-record-info.mtpl.htm';
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) $the_template,
			[
				'TEXT-MODIFIED'			=> (string) self::text('modified'),
				'FIELD-MODIFIED' 		=> (string) \Smart::escape_html($query['modified']),
				'TEXT-ADMIN'			=> (string) self::text('admin'),
				'FIELD-ADMIN' 			=> (string) \Smart::escape_html($query['admin']),
				'TEXT-PUBLISHED'		=> (string) self::text('published'),
				'FIELD-PUBLISHED' 		=> (string) \Smart::escape_html(\date('Y-m-d H:i:s', $query['published'])),
				'TEXT-COUNTER'			=> (string) self::text('counter'),
				'FIELD-COUNTER' 		=> (string) \Smart::escape_html($query['counter'])
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ListMediaForObjectId($y_id) {
		//--
		$y_id = (string) \trim((string)$y_id);
		if((string)$y_id == '') {
			return array();
		} //end if
		//--
		$fdir = (string) \SmartModExtLib\PageBuilder\Utils::getMediaFolderByObjectId((string)$y_id);
		//--
		$arr_imgs = array();
		if(\SmartFileSystem::is_type_dir($fdir)) {
			$arr_imgs = (array) \SmartModExtLib\PageBuilder\Utils::getMediaFolderContent($fdir);
			if(\Smart::array_size($arr_imgs) > 0) {
				for($i=0; $i<\Smart::array_size($arr_imgs); $i++) {
					$tmp_is_used = (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::getExprContextUsageCount($fdir.$arr_imgs[$i]['file']); // {{{SYNC-PAGEBUILDER-MEDIA-USAGE-CHECK}}}
					$arr_imgs[$i]['used'] = (int) $tmp_is_used;
					$tmp_is_used = null;
				} //end if
			} //end if
		} //end if
		//--
		return (array) $arr_imgs;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayMedia($y_id) {
		//--
		return (string) self::ViewFormMedia($y_id, 'list', true);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	// view or display form entry for MEDIA
	// $y_mode :: 'list' | 'form'
	public static function ViewFormMedia($y_id, $y_mode, $y_is_preview_only=false) {
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordInfById($y_id);
		if((string)$query['id'] == '') {
			return \SmartComponents::operation_error('FormView Media // Invalid ID');
		} //end if
		//--
	//	if((string)$query['mode'] == 'raw') {
	//		return '<br><center><div>'.'<img src="'.self::MODULE_PATH.'libs/views/manager/img/syntax-raw.svg" width="256" height="256" alt="N/A" title="N/A" style="opacity:0.2">'.'</div></center>';
	//	} //end if
		//--
		$the_template = self::MODULE_PATH.'libs/views/manager/view-record-media.mtpl.htm';
		//--
		$arr_imgs = (array) self::ListMediaForObjectId((string)$query['id']);
		//--
		if($y_is_preview_only === true) {
			$is_preview_only = 'yes';
			$priv_edit = 'no';
			$priv_delete = 'no';
		} else {
			$is_preview_only = 'no';
			$priv_edit = ((\SmartAuth::test_login_privilege('admin') === true) OR (\SmartAuth::test_login_privilege('pagebuilder:edit') === true)) ? 'yes' : 'no';
			$priv_delete = ((\SmartAuth::test_login_privilege('admin') === true) OR (\SmartAuth::test_login_privilege('pagebuilder:delete') === true)) ? 'yes' : 'no';
		} //end if else
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) $the_template,
			[
				'MODULE-PATH' 			=> (string) self::MODULE_PATH,
				'IS-PREVIEW' 			=> (string) $is_preview_only,
				'PRIV-EDIT' 			=> (string) $priv_edit,
				'PRIV-DELETE' 			=> (string) $priv_delete,
				'RECORD-ID'				=> (string) \Smart::escape_html($query['id']),
				'RECORD-NAME' 			=> (string) \Smart::escape_html($query['name']),
				'JPEG-QUALITY' 			=> (string) \Smart::format_number_dec(self::IMG_QUALITY_JPG_WEBP, 2),
				'MAX-SIZE-B64-MEDIA' 	=> (string) \Smart::format_number_dec(self::LIMIT_MEDIA_SIZE_MB * 1.1, 2), // allow 10% more size
				'MAX-WIDTH-MEDIA' 		=> (string) \Smart::format_number_int(self::IMG_MAX_WIDTH, '+'),
				'MAX-HEIGHT-MEDIA' 		=> (string) \Smart::format_number_int(self::IMG_MAX_HEIGHT, '+'),
				'CNT-MEDIA-FILES' 		=> (int)    \Smart::array_size($arr_imgs),
				'ARR-MEDIA-IMGS' 		=> (array)  $arr_imgs
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function UploadMedia($y_id, $y_type, $y_name, $y_content, $y_cksum, $y_as) {
		//--
		$err = '';
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordInfById($y_id);
		if((string)$query['id'] == '') {
			$err = 'Invalid ID';
		} //end if
		//--
		if(!$err) {
			$priv_edit = ((\SmartAuth::test_login_privilege('admin') === true) OR (\SmartAuth::test_login_privilege('pagebuilder:edit') === true)) ? 'yes' : 'no';
			if((string)$priv_edit != 'yes') {
				$err = 'Not Enough Privileges';
			} //end if
		} //end if
		//--
		if(!$err) {
			$y_content = (string) \trim((string)$y_content);
			if((string)$y_content == '') {
				$err = 'Empty Content';
			} //end if
		} //end if
		if(!$err) {
			if((string)\sha1((string)$y_content) != (string)$y_cksum) {
				$err = 'Invalid Content Checksum';
			} //end if
		} //end if
		if(!$err) {
			if(((string)\strtolower((string)\substr((string)$y_content, 0, 11)) != 'data:image/') OR (\stripos((string)$y_content, ';base64,') === false)) {
				$err = 'Invalid Content Format';
			} //end if
		} //end if
		if(!$err) {
			$y_content = (array) \explode(';base64,', (string)$y_content);
			$y_content = (string) @\base64_decode((string)\trim((string)(isset($y_content[1]) ? $y_content[1] : '')));
			if((string)$y_content == '') {
				$err = 'Invalid SVG Content';
			} //end if
		} //end if
		//--
		$img_ext = '';
		//--
		if(!$err) {
			switch((string)$y_type) {
				case 'image/svg+xml': // here SVGs can be only base64 encoded as canvas will send them !
					if((\stripos((string)$y_content, '<svg') !== false) AND (\stripos((string)$y_content, '</svg>') !== false)) { // {{{SYNC VALIDATE SVG}}}
						$y_content = (new \SmartXmlParser())->format((string)$y_content, false, false, false, true); // avoid injection of other content than XML, remove the XML header
					} else {
						$y_content = ''; // not a SVG !
					} //end if else
					if((string)$y_content == '') {
						$err = 'Invalid SVG Content';
					} else {
						$img_ext = 'svg';
					} //end if
					break;
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
				case 'image/webp':
					$imgd = new \SmartImageGdProcess((string)$y_content);
					$img_ext = (string) $imgd->getImageType();
					$img_as = '';
					if((string)$y_type == 'image/webp') { // fix back: {{{SYNC-JS-CANVAS-CANNOT-HANDLE-WEBP}}}
						$img_ext = 'webp';
						$img_as = 'webp';
					} //end if
					$is_converted = false;
					$skip_filter_imgd = false;
					switch((string)$y_as) {
						case 'gif':
						case 'png':
						case 'jpg':
						case 'webp':
							$is_converted = true;
							$img_ext = (string) $y_as;
							$img_as = (string) $y_as;
							break;
						default:
							// n/a
					} //end if
					if(((string)$img_ext == 'gif') || ((string)$img_ext == 'png')) {
						if($is_converted === false) { // skip original GIfs and PNGs (but not those converted)
							$skip_filter_imgd = true; // preserve original gifs to keep animations ; they will be only validated for errors via IMGD (if there is a real image to avoid injection of other content)
						} //end if
					} //end if
					$resize = $imgd->resizeImage(1600, 1280, false, 2, [255, 255, 255]); // create resample with: preserve if lower + relative dimensions
					if(!$resize) {
						$err = 'Invalid Image Content: '.$imgd->getLastMessage();
					} //end if
					if(!$err) {
						if($imgd->getStatusOk() === true) {
							if($skip_filter_imgd === false) { // skip original GIfs and PNGs (but not those converted)
								$y_content = (string) $imgd->getImageData((string)$img_as, (self::IMG_QUALITY_JPG_WEBP * 100), 9);
							} //end if
						} else {
							$y_content = '';
						} //end if
					} else {
						$y_content = '';
					} //end if else
					if(!$err) {
						if((string)$y_content == '') {
							$err = 'Invalid Image Content';
						} elseif(\strlen($y_content) > 1024 * 1024 * self::LIMIT_MEDIA_SIZE_MB) {
							$err = 'Oversized Image Content';
						} //end if
					} //end if
					break;
				default:
					$err = 'Invalid Type: '.$y_type;
					$y_type = 'unknown';
			} //end switch
		} //end if
		//--
		if(!$err) {
			$fdir = (string) \SmartModExtLib\PageBuilder\Utils::getMediaFolderByObjectId((string)$query['id']);
			if(!\SmartFileSystem::is_type_dir($fdir)) {
				\SmartFileSystem::dir_create($fdir, true);
				if(!\SmartFileSystem::is_type_dir($fdir)) {
					$err = 'Failed to Create Storage Folder';
				} //end if
			} //end if
		} //end if
		if(!$err) {
			if(!\SmartFileSystem::is_type_file($fdir.'index.html')) {
				\SmartFileSystem::write($fdir.'index.html', '');
			} //end if
		} //end if
		if(!$err) {
			$y_name = (string) \trim((string)$y_name);
			if((string)$y_name != '') {
				$y_name = (string) \Smart::safe_filename((string)$y_name);
				$y_name = (string) \SmartFileSysUtils::extractPathFileNoExtName((string)$y_name);
				$y_name = (string) \trim((string)\substr((string)\Smart::safe_filename((string)$y_name), 0, 70), '.'); // try to cut the filename at a given length as 70 ; the extension can be no more than 5 characters as of: .svg .gif .png .jpg .webp
				$y_name = (string) \Smart::safe_filename((string)$y_name.'.'.$img_ext);
			} //end if
			if(((string)$y_name != '') AND (\strlen((string)$y_name) <= 75)) {
				$file = (string) $y_name;
			} else {
				$file = (string) \Smart::safe_filename('img-'.\strtolower(\Smart::uuid_10_seq()).'.'.$img_ext);
			} //end if
			if(!\SmartFileSystem::write((string)$fdir.$file, (string)$y_content)) {
				$err = 'Failed to Create Storage File';
			} //end if
		} //end if
		//--
		if(!$err) {
			$rdir = (string) \SmartModExtLib\PageBuilder\Utils::getMediaFolderRoot();
			if(\SmartFileSystem::is_type_dir($rdir)) {
				if(!\SmartFileSystem::is_type_file($rdir.'index.html')) {
					\SmartFileSystem::write($rdir.'index.html', '');
				} //end if
			} //end if
		} //end if
		//--
		if(!$err) {
			$status = 'OK';
			$message = 'Operation Completed: '.$img_ext;
		} else {
			$status = 'ERROR';
			$message = (string) $err;
		} //end if else
		//--
		$title = 'Upload Media: '.$y_type;
		//--
		return (string) \SmartViewHtmlHelpers::js_ajax_replyto_html_form(
			(string) $status,
			(string) $title,
			(string) $message
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function DeleteMedia($y_id, $y_filename) {
		//--
		$err = '';
		//--
		$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordInfById($y_id);
		if((string)$query['id'] == '') {
			$err = 'Invalid ID';
		} //end if
		//--
		if(!$err) {
			$y_filename = (string) \trim((string)$y_filename);
			if((string)$y_filename == '') {
				$err = 'Empty File Name';
			} elseif((string)\substr((string)$y_filename, -5, 5) == '.webp') {
				// ok
			} else {
				switch((string)\substr((string)$y_filename, -4, 4)) {
					case '.svg':
					case '.gif':
					case '.png':
					case '.jpg':
						break;
					default:
						$err = 'Invalid File Type';
				} //end switch
			} //end if
		} //end if
		//--
		if(!$err) {
			$priv_delete = ((\SmartAuth::test_login_privilege('admin') === true) OR (\SmartAuth::test_login_privilege('pagebuilder:delete') === true)) ? 'yes' : 'no';
			if((string)$priv_delete != 'yes') {
				$err = 'Not Enough Privileges';
			} //end if
		} //end if
		//--
		if(!$err) {
			$fdir = (string) \SmartModExtLib\PageBuilder\Utils::getMediaFolderByObjectId((string)$query['id']);
			if(!\SmartFileSystem::is_type_dir($fdir)) {
				$err = 'Invalid Media Container';
			} else {
				$is_used = (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::getExprContextUsageCount($fdir.$y_filename); // {{{SYNC-PAGEBUILDER-MEDIA-USAGE-CHECK}}}
				if($is_used > 0) {
					$err = 'File is Used in [#'.$is_used.'] Objects';
				} //end if
			} //end if
		} //end if
		if(!$err) {
			if(!\SmartFileSystem::is_type_file($fdir.$y_filename)) {
				$err = 'Invalid File Name';
			} //end if
		} //end if
		if(!$err) {
			if(!\SmartFileSystem::delete($fdir.$y_filename)) {
				$err = 'ERROR Deleting the File';
			} //end if
		} //end if
		if(!$err) {
			if(\SmartFileSystem::is_type_file($fdir.$y_filename)) {
				$err = 'FAILED to Delete the File';
			} //end if
		} //end if
		//--
		// getMediaFolderContent
		if(!$err) {
			if(\SmartFileSystem::is_type_dir($fdir)) {
				$remaining_imgs_arr = (array) \SmartModExtLib\PageBuilder\Utils::getMediaFolderContent($fdir);
				if(\Smart::array_size($remaining_imgs_arr) <= 0) {
					if(!\SmartFileSystem::dir_delete($fdir)) {
						$err = 'FAILED to Delete the (Empty) Media Container for this Object';
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		if(!$err) {
			$status = 'OK';
			$message = 'Operation Completed';
		} else {
			$status = 'ERROR';
			$message = (string) $err;
		} //end if else
		//--
		$title = 'Delete Media: '.$y_filename;
		//--
		return (string) \SmartViewHtmlHelpers::js_ajax_replyto_html_form(
			(string) $status,
			(string) $title,
			(string) $message
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewFormAdd() {
		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$out = '';
		//-- SMART_PAGEBUILDER_DISABLE_PAGES
		$arr_objects_segments = [
			'#OPTGROUP#Segments' => 'Segments',
				'html-segment' 		=> 'Segment Page - HTML Syntax',
				'markdown-segment' 	=> 'Segment Page - Markdown Syntax',
				'text-segment' 		=> 'Segment Page - Text Syntax',
				'settings-segment' 	=> 'Segment Page - Data / Settings'
		];
		$arr_objects_pages = [
			'#OPTGROUP#Pages' => 'Pages',
				'html-page' 		=> 'Page - HTML Syntax',
				'markdown-page' 	=> 'Page - Markdown Syntax',
				'text-page' 		=> 'Page - Text Syntax',
				'raw-page' 			=> 'Page - Raw Code'
		];
		if(\SmartModExtLib\PageBuilder\Utils::allowPages() === true) {
			$arr_objects = (array) \array_merge((array)$arr_objects_pages, (array)$arr_objects_segments);
		} else {
			$arr_objects = (array) $arr_objects_segments;
		} //end if else
		//--
		$out .= '<script>'.\SmartViewHtmlHelpers::js_code_init_away_page('The changes will be lost !').'</script>';
		$out .= \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-record-frm-add.mtpl.htm',
			[
				'BUTTONS-CLOSE' 	=> (string) '<input type="button" value="'.\Smart::escape_html($translator_window->text('button_close')).'" class="ux-button ux-button-secondary" onClick="smartJ$Browser.CloseModalPopUp(); return false;">',
				'THE-TTL' 			=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/op-add.svg'.'" alt="'.self::text('ttl_add').'" title="'.self::text('ttl_add').'">'.'&nbsp;'.self::text('ttl_add'),
				'REFRESH-PARENT' 	=> (string) '<script>smartJ$Browser.RefreshParent();</script>',
				'FORM-NAME' 		=> (string) 'page_form_add',
				'LABELS-TYPE'		=> (string) self::text('record_syntax'),
				'CONTROLS-TYPE' 	=> (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('ptype', '', 'form', (array)$arr_objects, 'frm[ptype]', '275/0', '', 'no', 'yes'),
				'LABELS-ID'			=> (string) self::text('id'),
				'LABELS-NAME'		=> (string) self::text('name'),
				'LABELS-CTRL' 		=> (string) self::text('ctrl'),
				'BUTTONS-SUBMIT' 	=> (string) '<button class="ux-button ux-button-regular" type="button" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('page_form_add', self::composeUrl('op=record-add-do')).' return false;">'.' &nbsp; '.'<i class="sfi sfi-floppy-disk"></i>'.' &nbsp; '.self::text('save').'</button>'
			],
			'no'
		);
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewFormsSubmit($y_mode, $y_frm, $y_id='', $y_redir=true) {
		//--
		$y_frm = (array) $y_frm;
		$y_id = (string) \trim((string)$y_id);
		//--
		$data = array();
		$error = '';
		$redirect = '';
		$rdr_sufx = '';
		//--
		$proc_write_ok = false; 	// only if true will run the insert or update query
		$proc_id = ''; 				// '' for insert | 'the-uid' for update
		$proc_mode = ''; 			// insert | update
		$proc_upd_cksum = false;	// if true will update the page checksum: id+data
		//--
		switch((string)$y_mode) {
			//--
			case 'add': // OK
				//--
				$proc_mode = 'insert';
				//--
				if(
					(\SmartAuth::test_login_privilege('admin') === true)
					OR
					(\SmartAuth::test_login_privilege('pagebuilder:create') === true)
				) {
					//-- {{{SYNC-PAGEBUILDER-ID-CONSTRAINTS}}}
					$y_frm['id'] = (string) \trim((string)$y_frm['id']);
					$y_frm['id'] = (string) \Smart::create_slug((string)$y_frm['id'], true, 63); // lowercase, max 63, output: a-z 0-9 _ -
					//--
					if(
						((int)\strlen((string)$y_frm['id']) >= 1) // {{{SYNC-PAGEBUILDER-SLUG-LEN-CONSTRAINTS}}} ; ex: 'go' or 'c' are valid slugs
						AND
						((int)\strlen((string)$y_frm['id']) <= 63)
						AND
						((string)\trim((string)$y_frm['id'], '_-') != '') // must not contain only - or _
					) {
						//--
						$data = array();
						//--
						$data['id'] = (string) $y_frm['id'];
						//--
						switch((string)$y_frm['ptype']) {
							case 'settings-segment':
								$data['id'] = '#'.$data['id']; // segment page
								$data['mode'] = 'settings';
								break;
							case 'text-segment':
								$data['id'] = '#'.$data['id']; // segment page
								$data['mode'] = 'text';
								break;
							case 'markdown-segment':
								$data['id'] = '#'.$data['id']; // segment page
								$data['mode'] = 'markdown';
								break;
							case 'html-segment':
								$data['id'] = '#'.$data['id']; // segment page
								$data['mode'] = 'html';
								break;
							case 'raw-page':
								$data['mode'] = 'raw';
								break;
							case 'text-page':
								$data['mode'] = 'text';
								break;
							case 'markdown-page':
								$data['mode'] = 'markdown';
								break;
							case 'html-page':
								$data['mode'] = 'html';
								break;
							default:
								$error = self::text('err_0')."\n"; // invalid object type
						} //end switch
						//--
						$redirect = self::composeUrl('op=record-view&id='.\Smart::escape_url($data['id']));
						//--
						$data['ref'] = '[]'; // reference parent, by default is empty json array []
						$data['name'] = (string) \trim((string)$y_frm['name']);
						$data['active'] = '0'; // the page will be inactive at creation time
						$data['ctrl'] = '';
						$data['published'] = (string) \time();
						//--
						if((string)$error == '') {
							if(((string)$data['id'] == '') OR ((string)$data['id'] == '#')) {
								$error = self::text('err_4')."\n"; // invalid (empty) ID
							} //end if
						} //end if
						if((string)$error == '') {
							$chk_id = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordIdsById($data['id']);
							if(\Smart::array_size($chk_id) > 0) {
								if(\strlen($chk_id['id']) > 0) {
									$error = self::text('err_3')."\n"; // duplicate ID
								} //end if
							} //end if
						} //end if
						if((string)$error == '') {
							if((string)$data['name'] == '') {
								$error = self::text('err_6')."\n"; // invalid (empty) Title
							} //end if
						} //end if
						//--
						if((string)$error == '') {
							$proc_write_ok = true;
						} // end if else
						//--
					} else {
						//--
						$error = self::text('err_4')."\n";
						//--
					} // end if else
					//--
				} else {
					//--
					$error = self::text('msg_no_priv_add')."\n";
					//--
				} // end if else
				//--
				break;
			//--
			case 'edit':
				//--
				$proc_mode = 'update';
				//--
				$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDetailsById($y_id);
				//--
				if(
					((string)\trim((string)$y_id) != '')
					AND
					((int)\Smart::array_size($query) > 0)
					AND
					((string)$y_id == (string)$query['id'])
					AND
					(
						(\SmartAuth::test_login_privilege('admin') === true)
						OR
						(\SmartAuth::test_login_privilege('pagebuilder:edit') === true)
						OR
						(\SmartAuth::test_login_privilege('pagebuilder:data-edit') === true)
					)
				) {
					//--
					$proc_id = (string) $query['id'];
					//--
					if((string)$y_frm['form_mode'] == 'props') { // PROPS
						//--
						$redirect = self::composeUrl('op=record-view&id='.\Smart::escape_url($query['id']));
						//--
						$data = array();
						//--
						$data['name'] = (string) \SmartUnicode::sub_str((string)\trim((string)($y_frm['name'] ?? null)), 0, 255);
						if((string)$error == '') {
							if((string)$data['name'] == '') {
								$error = self::text('err_6')."\n"; // invalid (empty) Title
							} //end if
						} //end if
						//--
						$data['ctrl'] = (string) \SmartUnicode::sub_str((string)\trim((string)($y_frm['ctrl'] ?? null)), 0, 128);
						//--
						$data['tags'] = [];
						$arr_tmp_tags = (array) \explode(',', (string)\trim((string)($y_frm['tags'] ?? null)));
						for($g=0; $g<\Smart::array_size($arr_tmp_tags); $g++) {
							$arr_tmp_tags[$g] = (string) \trim((string)$arr_tmp_tags[$g]);
							if((\strlen((string)$arr_tmp_tags[$g]) >= 1) AND (\strlen((string)$arr_tmp_tags[$g]) <= 25)) {
								if(preg_match('/^[a-z0-9\-]+$/', (string)$arr_tmp_tags[$g])) {
									if(\Smart::array_size($data['tags']) < 25) {
										if(!\in_array((string)$arr_tmp_tags[$g], (array)$data['tags'])) {
											$data['tags'][] = (string) $arr_tmp_tags[$g];
										} //end if
									} //end if
								} //end if
							} //end if
						} //end for
						//--
						$data['translations'] = (int) ($y_frm['translations'] ?? null);
						if($data['translations'] != 1) {
							$data['translations'] = 0;
						} //end if
						//--
						$data['special'] = (int) ($y_frm['special'] ?? null);
						if($data['special'] < 0) {
							$data['special'] = 0;
						} elseif($data['special'] > 999999999) {
							$data['special'] = 999999999;
						} //end if
						//--
						if(!self::testIsSegmentPage($query['id'])) {
							//--
							$data['active'] = \Smart::format_number_int(($y_frm['active'] ?? null), '+');
							if(((string)$data['active'] != '0') AND ((string)$data['active'] != '1')) {
								$data['active'] = '0';
							} //end if
							//--
							$data['auth'] = \Smart::format_number_int(($y_frm['auth'] ?? null), '+');
							if(((string)$data['auth'] != '0') AND ((string)$data['auth'] != '1')) {
								$data['auth'] = '0';
							} //end if
							//--
							$data['mode'] = (string) \strtolower((string)\trim((string)($y_frm['mode'] ?? null)));
							switch((string)$data['mode']) {
								case 'raw':
									$data['mode'] = 'raw';
									break;
								case 'text':
									$data['mode'] = 'text';
									break;
								case 'markdown':
									$data['mode'] = 'markdown';
									break;
								case 'html':
								default:
									$data['mode'] = 'html';
							} //end switch
							//--
							$data['layout'] = (string) \trim((string)($y_frm['layout'] ?? null));
							$data['layout'] = (string) \Smart::safe_filename((string)$data['layout']);
							$data['layout'] = (string) \strtolower((string)$data['layout']);
							if(\strlen((string)$data['layout']) > 75) {
								$data['layout'] = ''; // fix to avoid DB overflow
							} //end if
							if((string)$data['mode'] == 'raw') {
								$data['layout'] = ''; // force for raw pages
							} //end if
							//--
						} else {
							//--
							$data['active'] = 0;
							$data['auth'] = 0;
							//--
							$data['mode'] = (string) \strtolower((string)\trim((string)$y_frm['mode']));
							switch((string)$data['mode']) {
								case 'settings':
									$data['mode'] = 'settings';
									$data['code'] = '';
									break;
								case 'text':
									$data['mode'] = 'text';
									break;
								case 'markdown':
									$data['mode'] = 'markdown';
									break;
								case 'html':
								default:
									$data['mode'] = 'html';
							} //end switch
							//--
							$data['layout'] = (string) \trim((string)(isset($y_frm['layout']) ? $y_frm['layout'] : ''));
							$data['layout'] = (string) \Smart::safe_filename((string)$data['layout']);
							$data['layout'] = (string) \strtolower((string)$data['layout']);
							if(\strlen((string)$data['layout']) > 75) {
								$data['layout'] = ''; // fix to avoid DB overflow
							} //end if
							if((string)$data['mode'] == 'settings') {
								$data['layout'] = ''; // force for settings segments
							} //end if
							//--
						} //end if
						//--
						if(isset($y_frm['reset-translations']) AND ((string)$y_frm['reset-translations'] == 'all')) {
							if(
								(\SmartAuth::test_login_privilege('admin') === true)
								OR
								(\SmartAuth::test_login_privilege('pagebuilder:edit') === true)
							) {
								if((string)$error == '') {
									\SmartModDataModel\PageBuilder\PageBuilderBackend::resetRecordTranslationsById($query['id']);
								} //end if
							} else {
								$error = self::text('msg_specprivs_req')."\n";
							} //end if
						} //end if
						//--
						if((string)$error == '') {
							$proc_write_ok = true;
						} //end if
						//--
					} elseif((string)$y_frm['form_mode'] == 'code') { // CODE
						//--
						$proc_upd_cksum = true;
						//--
						if(!\array_key_exists('data', $y_frm)) { // frm[data] must not be set here
							//--
							$redirect = self::composeUrl('op=record-view&id='.\Smart::escape_url($query['id']).'&sop=code');
							//--
							$data = array();
							//--
							$data['code'] = (string) \trim((string)$y_frm['code']);
							if((string)$data['code'] != '') {
								//--
								/*
								if(((string)$query['mode'] == 'markdown') OR ((string)$query['mode'] == 'html')) {
									// {{{SYNC-PAGEBUILDER-HTML-SAFETY}}} :: fixSafeCode is managed later on display
								} elseif((string)$query['mode'] == 'raw') {
									// {{{SYNC-PAGEBUILDER-RAWPAGE-SAFETY}}} :: managed later on display, depends on mime type
								} //end if
								*/
								//--
								$data['code'] = (string) \SmartModExtLib\PageBuilder\Utils::prepareCodeData((string)$data['code'], true);
								//--
								$data['code'] = (string) \base64_encode((string)$data['code']);
								//--
							} //end if
							//--
							$y_frm['code'] = ''; // free mem
							//--
							if((int)\strlen((string)$data['code']) > (int)self::LIMIT_CODE_SIZE) {
								$error = 'Page Code is OVERSIZED !'."\n";
							} //end if
							//--
							if((string)$error == '') {
								$proc_write_ok = true;
							} //end if
							//--
						} else {
							//--
							$error = self::text('err_7').' (2)'."\n";
							//--
						} //end if else
						//--
					} elseif((string)$y_frm['form_mode'] == 'yaml') { // YAML
						//--
						$proc_upd_cksum = true;
						//--
						if(!\array_key_exists('code', $y_frm)) { // frm[code] must not be set here
							//--
							if(
								(\SmartAuth::test_login_privilege('admin') === true)
								OR
								(\SmartAuth::test_login_privilege('pagebuilder:data-edit') === true)
							) {
								//--
								$redirect = (string) self::composeUrl('op=record-view&id='.\Smart::escape_url($query['id']).'&sop=yaml');
								//--
								$data = [];
								//--
								$data['data'] = (string) \trim((string)$y_frm['data']);
								if((string)$data['data'] != '') {
									//--
									$data['data'] = (string) \SmartModExtLib\PageBuilder\Utils::prepareCodeData((string)$data['data'], false, true); // for yaml re-indent spaces as tabs ...
									//--
									$data['data'] = (string) \base64_encode((string)$data['data']); // encode data b64 (encode must be here because will be transmitted later as B64 encode and must cover all error situations)
									//--
								} //end if
								$y_frm['data'] = '';
								//--
								if((int)\strlen($data['data']) > (int)(self::LIMIT_CODE_SIZE/10)) {
									$error = 'Page Data is OVERSIZED !'."\n";
								} //end if
								//--
								if((string)$error == '') {
									$proc_write_ok = true;
								} //end if
								//--
							} else {
								//--
								$error = self::text('msg_no_priv_edit')."\n";
								//--
							} //end if else
							//--
						} else {
							//--
							$error = self::text('err_7').' (3)'."\n";
							//--
						} //end if else
						//--
					} else {
						//--
						$error = 'Invalid Operation !';
						//--
					} //end if else
					//--
				} else {
					//--
					$error = self::text('msg_no_priv_edit')."\n";
					//--
				} //end if else
				//--
				break;
			//--
			case 'clone':
				//--
				$proc_mode = 'insert';
				//--
				$query = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordById((string)$y_id); // get full object
				//--
				if(
					((string)\trim((string)$y_id) != '')
					AND
					((string)$y_id == (string)$query['id'])
					AND
					(
						(\SmartAuth::test_login_privilege('admin') === true)
						OR
						(\SmartAuth::test_login_privilege('pagebuilder:create') === true)
					)
				) {
					//-- {{{SYNC-PAGEBUILDER-ID-CONSTRAINTS}}}
					$y_frm['id'] = (string) \trim((string)$y_frm['id']);
					$y_frm['id'] = (string) \Smart::create_slug((string)$y_frm['id'], true, 63); // lowercase, max 63, output: a-z 0-9 _ -
					//--
					if(
						((int)\strlen((string)$y_frm['id']) >= 1) // {{{SYNC-PAGEBUILDER-SLUG-LEN-CONSTRAINTS}}} ; ex: 'go' or 'c' are valid slugs
						AND
						((int)\strlen((string)$y_frm['id']) <= 63)
						AND
						((string)\trim((string)$y_frm['id'], '_-') != '') // must not contain only - or _
					) {
						//--
						if((string)\substr((string)$y_id, 0, 1) == '#') {
							$y_frm['id'] = '#'.$y_frm['id']; // if cloned is segment then make this also a segment
						} //end if
						//--
						$test_clone = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDetailsById((string)$y_frm['id']);
						//--
						if(\Smart::array_size($test_clone) <= 0) {
							//--
							$proc_id = (string) $y_frm['id'];
							$redirect = self::composeUrl('op=record-view&id='.\Smart::escape_url($y_frm['id']));
							//--
							$data = array();
							$data = (array) $query;
							$data['id'] = (string) $y_frm['id'];
							$data['ref'] = '[]'; // reference parent, by default is empty json array [] ; reset refs, it is not yet in use ...
							$data['name'] = (string) $y_frm['name'];
							$data['checksum'] = '';
							$data['special'] = 0;
							$data['active'] = 0;
							$data['counter'] = 0;
							$data['published'] = (string) \time();
							//--
							if((string)$error == '') {
								$proc_write_ok = true;
							} // end if else
							//--
						} else {
							//--
							$error = self::text('msg_object_exists')."\n";
							//--
						} //end if else
						//--
					} else {
						//--
						$error = self::text('err_4')."\n";
						//--
					} //end if else
					//--
				} else {
					//--
					$error = self::text('msg_no_priv_add')."\n";
					//--
				} // end if else
				//--
				break;
			//--
			default: // OK
				//--
				$error = self::text('err_2')."\n";
				//--
		} // end switch
		//--
		if((string)$error == '') {
			//--
			if($proc_write_ok) {
				//--
				if(\Smart::array_size($data) > 0) {
					//--
					$data['admin'] = \SmartAuth::get_auth_id();
					$data['modified'] = \date('Y-m-d H:i:s');
					//--
					if((string)$proc_mode == 'insert') {
						$wr = \SmartModDataModel\PageBuilder\PageBuilderBackend::insertRecord($data);
					} elseif((string)$proc_mode == 'update') {
						if(isset($y_frm['language']) AND ((string)$y_frm['language'] != '')) {
							$rdr_sufx = '&translate='.\Smart::escape_url((string)$y_frm['language']);
							$wr = \SmartModDataModel\PageBuilder\PageBuilderBackend::updateTranslationById($proc_id, $y_frm['language'], $data);
						} else {
							$wr = \SmartModDataModel\PageBuilder\PageBuilderBackend::updateRecordById($proc_id, $data, $proc_upd_cksum);
						} //end if
					} else {
						$wr = -100; // invalid op mode
					} //end if else
					//--
					if($wr !== 1) {
						$error = self::text('err_5').' @ '.$wr."\n";
					} // end if else
					//--
				} else {
					//--
					$error = 'Internal ERROR ... (Data is Empty)';
					//--
				} //end if else
				//--
			} else {
				//--
				$error = 'Internal ERROR ... (Write Operation Failed)';
				//--
			} //end if
			//--
		} // end if
		//--
		if((string)$error == '') {
			//--
			$result = 'OK';
			$title = '*';
			$message = '<span style="font-size:1rem;"><b>'.self::text('op_compl').'</b></span>';
			if($y_redir !== true) {
				$redirect = (string) $y_redir;
			} //end if
			if((string)$redirect != '') {
				$redirect .= $rdr_sufx;
			} //end if
			//--
		} else {
			//--
			$result = 'ERROR';
			$title = self::text('op_ncompl');
			$message = '<span style="font-size:1rem;"><b>'.$error.'</b></span>';
			$redirect = ''; // avoid redirect if error
			//--
		} //end if
		//--
		return (string) \SmartViewHtmlHelpers::js_ajax_replyto_html_form(
			(string) $result,
			(string) $title,
			(string) $message,
			(string) $redirect,
			'',
			'',
			'',
			true // hide form on success
		);
		//--
	} // END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Clone an Object by ID
	 *
	 * @param string $y_id
	 * @return string
	 */
	public static function ViewFormClone($y_id) {

		//--
		$tmp_rd_arr = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDetailsById($y_id);
		//--
		if((string)$tmp_rd_arr['id'] == '') {
			return \SmartComponents::operation_error(self::text('err_4'));
		} //end if
		//--

		//--
		$translator_window = \SmartTextTranslations::getTranslator('@core', 'window');
		//--
		$out = \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-record-frm-clone.mtpl.htm',
			[
				'BUTTONS-CLOSE' 	=> (string) '<input type="button" value="'.\Smart::escape_html($translator_window->text('button_close')).'" class="ux-button ux-button-dark" onClick="smartJ$Browser.CloseModalPopUp(); return false;">',
				'THE-TTL' 			=> (string) '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/op-clone.svg'.'" alt="'.self::text('ttl_clone').'" title="'.self::text('ttl_clone').'">'.'&nbsp;'.self::text('ttl_clone'),
				'REFRESH-PARENT' 	=> (string) '<script>smartJ$Browser.RefreshParent();</script>',
				'FORM-NAME' 		=> (string) 'page_form_clone',
				'CLONED-ID' 		=> (string) \Smart::escape_html((string)$y_id),
				'LABELS-CLONE' 		=> (string) self::text('clone'),
				'LABELS-ID'			=> (string) self::text('id'),
				'LABELS-NAME'		=> (string) self::text('name'),
				'LABELS-CTRL' 		=> (string) self::text('ctrl'),
				'BUTTONS-SUBMIT' 	=> (string) '<button class="ux-button ux-button-highlight" type="button" onClick="'.\SmartViewHtmlHelpers::js_ajax_submit_html_form('page_form_clone', self::composeUrl('op=record-clone-do')).' return false;">'.' &nbsp; '.'<i class="sfi sfi-floppy-disk"></i>'.' &nbsp; '.self::text('save').'</button>'
			],
			'no'
		);
		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//==================================================================


	//==================================================================
	/**
	 * Delete an Object
	 *
	 * @param string $y_id
	 * @param string $y_delete
	 * @return string
	 */
	public static function ViewFormDelete($y_id, $y_delete) {

		//--
		$tmp_rd_arr = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordDetailsById($y_id);
		//--
		if((string)$tmp_rd_arr['id'] == '') {
			return \SmartComponents::operation_error(self::text('err_4'));
		} //end if
		//--

		//--
		$out = '';
		//--
		if((string)$y_delete == 'yes') {
			//--
			if(
				(\SmartAuth::test_login_privilege('admin') === true)
				OR
				(\SmartAuth::test_login_privilege('pagebuilder:delete') === true)
			) {
				//--
				$rdw = '<script>'.\SmartViewHtmlHelpers::js_code_wnd_redirect(self::composeUrl('op=record-view&id='.\Smart::escape_url($tmp_rd_arr['id'])), 3500).'</script>';
				//--
				$out .= '<script>'.\SmartViewHtmlHelpers::js_code_wnd_refresh_parent().'</script>';
				//--
				$chk_is_used = 0;
				$fdir = (string) \SmartModExtLib\PageBuilder\Utils::getMediaFolderByObjectId((string)$tmp_rd_arr['id']);
				if(\SmartFileSystem::is_type_dir($fdir)) {
					$arr_imgs = (array) \SmartModExtLib\PageBuilder\Utils::getMediaFolderContent($fdir);
					if(\Smart::array_size($arr_imgs) > 0) {
						for($i=0; $i<\Smart::array_size($arr_imgs); $i++) {
							$chk_is_used += (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::getExprContextUsageCount($fdir.$arr_imgs[$i]['file']); // {{{SYNC-PAGEBUILDER-MEDIA-USAGE-CHECK}}}
							if($chk_is_used > 0) {
								break;
							} //end if
						} //end if
					} //end if
				} //end if
				//--
				if($chk_is_used > 0) {
					//--
					$out .= '<br>'.\SmartComponents::operation_notice('Delete Canceled: The selected Object have attached Media Files which are in use by this Object or other Objects. Media Files usage must be cleared first !');
					$out .= $rdw;
					//--
				} else {
					//--
					$chk_del = (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::deleteRecordById($tmp_rd_arr['id']);
					//--
					if($chk_del == 1) {
						if(\SmartFileSystem::is_type_dir($fdir)) {
							\SmartFileSystem::dir_delete($fdir, true);
							if(\SmartFileSystem::path_exists($fdir)) {
								\Smart::log_warning(__METHOD__.' # Failed to Remove the Media Folder for ObjectID: '.$tmp_rd_arr['id']);
							} //end if
						} //end if
						$out .= '<br>'.\SmartComponents::operation_ok(self::text('op_compl'));
						$out .= '<script>'.\SmartViewHtmlHelpers::js_code_wnd_close_modal_popup().'</script>'; // ok
					} elseif($chk_del == -1) {
						$out .= '<br>'.\SmartComponents::operation_warn('Delete Failed: Empty ID');
						$out .= $rdw;
					} elseif($chk_del == -2) {
						$out .= '<br>'.\SmartComponents::operation_notice('Delete Canceled: The selected Object is in use in other Objects. Relations must be cleared first !');
						$out .= $rdw;
					} else {
						$out .= '<br>'.\SmartComponents::operation_error('Something goes really wrong ... Delete returned an invalid number rows: '.$chk_del);
						$out .= $rdw;
					} //end if else
					//--
				} //end if else
				//--
			} else {
				//--
				$out .= '<br>'.\SmartComponents::operation_error(self::text('msg_no_priv_del'));
				$out .= '<script>'.\SmartViewHtmlHelpers::js_code_wnd_refresh_parent().'</script>';
				$out .= '<script>'.\SmartViewHtmlHelpers::js_code_wnd_close_modal_popup(1500).'</script>'; // ok
				//--
			} //end if else
			//--
		} else {
			//--
			$out .= \SmartComponents::operation_question(self::text('ttl_del').' ?<div style="display:inline-block; margin-left:100px; min-width:200px;"><a class="ux-button ux-button-special" onClick="'.\Smart::escape_html(\SmartViewHtmlHelpers::js_code_ui_confirm_dialog('<h1>'.self::text('msg_confirm_del').' !</h1>', 'self.location=\''.self::composeUrl('op=record-delete&delete=yes&id='.\Smart::escape_url($y_id)).'\';', '550', '250', self::text('dp').' ?')).'; return false;" href="#">DELETE</a><a class="ux-button" href="'.\Smart::escape_html(self::composeUrl('op=record-view&id='.\Smart::escape_url($y_id))).'">Cancel</a></div>', '720');
			$out .= self::ViewDisplayRecord((string)$y_id, 0);
			//--
		} //end if else
		//--

		//--
		return (string) $out;
		//--

	} // END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayTree($y_tpl, $srcby, $src, $y_ctrl) {
		//--
		$flimit = 100000; // filter limit
		//--
		$unassigned_ctrl = false;
		if((string)$y_ctrl == ' ') {
			$unassigned_ctrl = true;
		} //end if
		$y_ctrl = (string) \trim((string)$y_ctrl);
		//-- {{{SYNC-PAGE-BUILDER-DO-NOT-TRIM-SRC}}} do not trim $src here, will be trimmed in model if needed
		if((string)\trim((string)$src) == '') {
			$srcby = '';
		} elseif((string)\trim((string)$srcby) == '') {
			$src = '';
		} //end if
		//--
		$cookie_display_fulltree = 'pageBuilder_Display_FullTree';
		$cookie_value_fulltree = 'display:full-tree';
		$display_fulltree = (string) \SmartUtils::get_cookie((string)$cookie_display_fulltree); // VALUE-FULLTREE
		//--
		$cookie_display_datasets = 'pageBuilder_Display_DataSets';
		$cookie_value_datasets = 'hide:datasets';
	//	$display_datasets = (string) \SmartUtils::get_cookie((string)$cookie_display_datasets); // VALUE-DATASETS
		$display_datasets = ''; // feature disabled
		$have_fulltree = false;
		//--
		$lst_src = (string) $src;
		$lst_srcby = (string) $srcby;
		//--
		$collapse = 'collapsed';
		$fcollapse = '';
		//--
		if((string)$srcby == 'ctrl') {
		//	if((string)$y_ctrl == '') {
		//		$y_ctrl = (string) $src;
		//	} //end if
			$srcby = '';
			$src = '';
			$lst_srcby = '';
			$lst_src = '';
		} //end if
		//--
		if($unassigned_ctrl === true) {
			$scollapse = '';
			$arr_controllers = [ '', ' ' ];
		} elseif((string)\trim((string)$y_ctrl) != '') {
			$scollapse = '';
			$arr_controllers = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsUniqueControllers('filter', (string)$y_ctrl);
		} else {
			if(\defined('\\SMART_PAGEBUILDER_ALLOW_FULLTREE')) {
				if(\SMART_PAGEBUILDER_ALLOW_FULLTREE === true) {
					$have_fulltree = true;
				} //end if
			} //end if
			$scollapse = (string) $collapse;
			if(($have_fulltree === true) AND ((string)$display_fulltree == (string)$cookie_value_fulltree)) {
				$arr_controllers = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsUniqueControllers('filter');
			} else {
				$arr_controllers = array();
			} //end if else
		} //end if else
		//--
		$arr_all_controllers = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsUniqueControllers('list-all');
		//--
		$filter = array();
		if(((string)\trim((string)$src) != '') AND ((string)\trim((string)$srcby) != '')) {
			$tmp_filter = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::listGetRecords($srcby, $src, (int)$flimit, 0, 'DESC', 'id');
			for($i=0; $i<\Smart::array_size($tmp_filter); $i++) {
				$filter[] = [ 'id' => (string)$tmp_filter[$i]['id'], 'hash-id' => (string)\sha1((string)$tmp_filter[$i]['id']) ];
			} //end for
			$tmp_filter = array();
		} //end if
		if(\Smart::array_size($filter) > 0) {
			$fcollapse = (string) $collapse;
		} //end if
		//--
		$total = [];
		//--
		$css_cls_a = 'simpletree-item-active';
		$css_cls_i = 'simpletree-item-inactive';
		//--
		$arr_pages_data = array();
		$have_datasets = 0;
		for($i=0; $i<\Smart::array_size($arr_controllers); $i++) {
			if(strpos($arr_controllers[$i], '{') === 0) {
				$have_datasets++;
			} //end if
			if(((strpos($arr_controllers[$i], '{') === 0) AND ((string)$display_datasets != (string)$cookie_value_datasets)) OR ((strpos($arr_controllers[$i], '{') !== 0) OR (strpos($arr_controllers[$i], '{') === false))) {
				$tmp_arr_lvl1 = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsByCtrl((string)$arr_controllers[$i]);
				for($j=0; $j<\Smart::array_size($tmp_arr_lvl1); $j++) {
					if(\Smart::array_size($tmp_arr_lvl1[$j]) > 0) {
						$tmp_arr_lvl2 = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsByRef((string)$tmp_arr_lvl1[$j]['id']);
						$tmp_arr_lvl1[$j]['hash-id'] = (string) \sha1((string)$tmp_arr_lvl1[$j]['id']);
						$tmp_arr_lvl1[$j]['is-segment'] = (int) self::testIsSegmentPage((string)$tmp_arr_lvl1[$j]['id']);
						if(((string)$tmp_arr_lvl1[$j]['active'] == 1) OR ($tmp_arr_lvl1[$j]['is-segment'] == 1)) {
							$tmp_arr_lvl1[$j]['style-class'] = (string) $css_cls_a;
						} else {
							$tmp_arr_lvl1[$j]['style-class'] = (string) $css_cls_i;
						} //end if else
						$tmp_arr_lvl1[$j]['icon-type'] = (string) self::getImgForPageType((string)$tmp_arr_lvl1[$j]['id']);
						$tmp_arr_lvl1[$j]['img-type-html'] = (string) self::getImgForCodeType((string)$tmp_arr_lvl1[$j]['id'], (string)$tmp_arr_lvl1[$j]['mode']);
						$tmp_arr_lvl1[$j]['ref-childs'] = array();
						if(\Smart::array_size($tmp_arr_lvl2) > 0) {
							for($k=0; $k<\Smart::array_size($tmp_arr_lvl2); $k++) {
								if(\Smart::array_size($tmp_arr_lvl2[$k]) > 0) {
									$tmp_arr_lvl3 = (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::getRecordsByRef((string)$tmp_arr_lvl2[$k]['id']);
									$tmp_arr_lvl2[$k]['hash-id'] = (string) \sha1((string)$tmp_arr_lvl2[$k]['id']);
									$tmp_arr_lvl2[$k]['is-segment'] = (int) self::testIsSegmentPage((string)$tmp_arr_lvl2[$k]['id']);
									if(((string)$tmp_arr_lvl2[$k]['active'] == 1) OR ($tmp_arr_lvl2[$k]['is-segment'] == 1)) {
										$tmp_arr_lvl2[$k]['style-class'] = (string) $css_cls_a;
									} else {
										$tmp_arr_lvl2[$k]['style-class'] = (string) $css_cls_i;
									} //end if else
									$tmp_arr_lvl2[$k]['icon-type'] = (string) self::getImgForPageType((string)$tmp_arr_lvl2[$k]['id']);
									$tmp_arr_lvl2[$k]['img-type-html'] = (string) self::getImgForCodeType((string)$tmp_arr_lvl2[$k]['id'], (string)$tmp_arr_lvl2[$k]['mode']);
									$tmp_arr_lvl2[$k]['ref-childs'] = array();
									if(\Smart::array_size($tmp_arr_lvl3) > 0) {
										for($z=0; $z<\Smart::array_size($tmp_arr_lvl3); $z++) {
											$tmp_arr_lvl3[$z]['hash-id'] = (string) \sha1((string)$tmp_arr_lvl3[$z]['id']);
											$tmp_arr_lvl3[$z]['is-segment'] = (int) self::testIsSegmentPage((string)$tmp_arr_lvl3[$z]['id']);
											if(((string)$tmp_arr_lvl3[$z]['active'] == 1) OR ($tmp_arr_lvl3[$z]['is-segment'] == 1)) {
												$tmp_arr_lvl3[$z]['style-class'] = (string) $css_cls_a;
											} else {
												$tmp_arr_lvl3[$z]['style-class'] = (string) $css_cls_i;
											} //end if else
											$tmp_arr_lvl3[$z]['icon-type'] = (string) self::getImgForPageType((string)$tmp_arr_lvl3[$z]['id']);
											$tmp_arr_lvl3[$z]['img-type-html'] = (string) self::getImgForCodeType((string)$tmp_arr_lvl3[$z]['id'], (string)$tmp_arr_lvl3[$z]['mode']);
										} //end for
										$tmp_arr_lvl2[$k]['ref-childs'] = (array) $tmp_arr_lvl3;
										if(!\array_key_exists((string)$z, $tmp_arr_lvl3)) {
											$tmp_arr_lvl3[$z] = [];
										} //end if
										if(!\array_key_exists('id', $tmp_arr_lvl3[$z])) {
											$tmp_arr_lvl3[$z]['id'] = null;
										} //end if
										if((string)$tmp_arr_lvl3[$z]['id'] != '') {
											if(!\array_key_exists((string)$tmp_arr_lvl3[$z]['id'], $total)) {
												$total[(string)$tmp_arr_lvl3[$z]['id']] = 0;
											} //end if
											$total[(string)$tmp_arr_lvl3[$z]['id']] += 1;
										} //end if else
									} //end if
									$tmp_arr_lvl3 = array();
									if(!\array_key_exists((string)$tmp_arr_lvl2[$k]['id'], $total)) {
										$total[(string)$tmp_arr_lvl2[$k]['id']] = 0;
									} //end if
									$total[(string)$tmp_arr_lvl2[$k]['id']] += 1;
								} //end if
							} //end for
							$tmp_arr_lvl1[$j]['ref-childs'] = (array) $tmp_arr_lvl2;
						} //end if
						$tmp_arr_lvl2 = array();
						$arr_pages_data[(string)$arr_controllers[$i]][] = (array) $tmp_arr_lvl1[$j];
						if(!\array_key_exists((string)$tmp_arr_lvl1[$j]['id'], $total)) {
							$total[(string)$tmp_arr_lvl1[$j]['id']] = 0;
						} //end if
						$total[(string)$tmp_arr_lvl1[$j]['id']] += 1;
					} //end if
				} //end for
			} //end if
			$tmp_arr_lvl1 = array();
		} //end if
		if(((string)$display_datasets == (string)$cookie_value_datasets)) {
			$fcollapse = (string) $collapse;
		} //end if
		//--
		// \print_r($total); die();
		// \print_r($arr_pages_data); die();
		//--
		$the_link_list = (string) self::composeUrl('op=records-tree&tpl='.\Smart::escape_url($y_tpl));
		$the_alt_link_list = (string) self::composeUrl('tpl='.\Smart::escape_url($y_tpl).'#!'.'&srcby='.\Smart::escape_url($lst_srcby).'&src='.\Smart::escape_url($lst_src));
		//-- {{{SYNC-PAGEBUILDER-MANAGER-DEF-LINKS}}}
		$the_link_add = (string) self::composeUrl('op=record-add-form');
		$the_link_view = (string) self::composeUrl('op=record-view&id=');
		$the_link_delete = '';
		if(!\defined('\\SMART_PAGEBUILDER_DISABLE_DELETE')) {
			$the_link_delete = (string) self::composeUrl('op=record-delete&id=');
		} //end if
		//--
		if(\Smart::array_size((array)\SmartTextTranslations::getListOfLanguages()) > 1) {
			$show_translations = 'yes';
		} else {
			$show_translations = 'no';
		} //end if else
		//--
		if(\SmartModExtLib\PageBuilder\Utils::allowPages() === true) {
			$allow_pages = 'yes';
		} else {
			$allow_pages = 'no';
		} //end if else
		//-- #{{{SYNC-PAGEBUILDER-MANAGER-DEF-LINKS}}}
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-list-tree.mtpl.htm',
			[
				'RELEASE-HASH' 		=> (string) \SmartUtils::get_app_release_hash(),
				'IS-DEV-MODE' 		=> (string) ((\SmartEnvironment::ifDevMode() === true) ? 'yes' : 'no'),
				'COOKIE-DATASETS' 	=> (string) $cookie_display_datasets,
				'VALUE-DATASETS' 	=> (string) $cookie_value_datasets,
				'DISPLAY-DATASETS' 	=> (string) $display_datasets,
				'HAVE-DATASETS' 	=> (string) $have_datasets ? 'yes' : 'no',
				'COOKIE-FULLTREE' 	=> (string) $cookie_display_fulltree,
				'VALUE-FULLTREE' 	=> (string) $cookie_value_fulltree,
				'DISPLAY-FULLTREE' 	=> (string) $display_fulltree,
				'HAVE-FULLTREE' 	=> (string) $have_fulltree ? 'yes' : 'no',
				'SHOW-FILTER-CTRL' 	=> 'no',
				'SHOW-TRANSLATIONS' => (string) $show_translations,
				'ALLOW-PAGES' 		=> (string) $allow_pages,
				'LIST-FORM-URL' 	=> (string) self::ADM_AREA_SCRIPT,
				'LIST-FORM-METHOD' 	=> 'GET',
				'LIST-FORM-VARS' 	=> (array) [
					[ 'name' => 'page', 'value' => (string) self::URL_VAL_MANAGE_PAGE ],
					[ 'name' => 'op',   'value' => 'records-tree' ],
					[ 'name' => 'tpl',  'value' => (string) $y_tpl ]
				],
				'LIST-VAL-SRC' 		=> (string) $lst_src,
				'LIST-VAL-SRCBY' 	=> (string) $lst_srcby,
				'LIST-BTN-RESET' 	=> (string) $the_link_list,
				'LIST-NEW-URL' 		=> (string) $the_link_add,
				'LIST-RECORD-URL' 	=> (string) $the_link_view,
				'LIST-DELETE-URL' 	=> (string) $the_link_delete,
				'LIST-ALT-COOKIE' 	=> (string) '',
				'LIST-CRR-LINK' 	=> (string) $the_link_list,
				'LIST-ALT-LINK' 	=> (string) $the_alt_link_list,
				'TXT-ALT-LINK' 		=> (string) self::text('ttl_ch_list', false),
				'TXT-EXPT-LINK' 	=> (string) self::text('ttl_transl_export', false),
				'LIST-EXPT-LINK' 	=> (string) self::composeUrl('op=export-translations'),
				'TXT-IMPT-LINK' 	=> (string) self::text('ttl_transl_import', false),
				'LIST-IMPT-LINK' 	=> (string) self::composeUrl('op=import-translations'),
				'TXT-WEBDAV-LINK' 	=> (string) self::text('ttl_webdav', false),
				'LIST-WEBDAV-LINK' 	=> (string) self::composeWebdavUrl(),
				'TXT-RESET-COUNTER' => (string) self::text('ttl_reset_hits', false),
				'TXT-CTRL-NONE' 	=> (string) self::text('ctrl_unassigned', false),
				'COLLAPSE' 			=> (string) $collapse,
				'SPECIAL-COLLAPSE' 	=> (string) $scollapse,
				'FILTER-COLLAPSE' 	=> (string) $fcollapse,
				'FILTER' 			=> (array)  $filter,
				'CTRLS' 			=> (array)  $arr_all_controllers,
				'CTRL-SEL' 			=> (string) ($unassigned_ctrl === true) ? ' ' : (string)$y_ctrl,
				'DATA' 				=> (array)  $arr_pages_data,
				'PATH-MODULE' 		=> (string) self::MODULE_PATH,
				'LIST-TTL' 			=> (string) self::text('ttl_list', false),
				'LIST-RECORDS' 		=> (string) self::text('ttl_trecords', false),
				'TXT-RECORDS' 		=> (string) self::text('records', false),
				'TXT-SEARCH-BY' 	=> (string) self::text('search_by', false),
				'TXT-FILTER' 		=> (string) self::text('search', false),
				'TXT-RESET' 		=> (string) self::text('reset', false),
				'TXT-ADD-NEW' 		=> (string) self::text('ttl_add', false),
				'TXT-COL-ID' 		=> (string) self::text('id', false),
				'TXT-COL-REFID' 	=> (string) self::text('ref', false),
				'TXT-COL-NAME' 		=> (string) self::text('name', false),
				'TXT-COL-CTRL' 		=> (string) self::text('ctrl', false),
				'TXT-COL-TEMPLATE' 	=> (string) self::text('template', false),
				'TXT-COL-AREA' 		=> (string) self::text('area', false),
				'TXT-COL-CODE' 		=> (string) self::text('record_code', false),
				'TXT-COL-RUNTIME' 	=> (string) self::text('record_runtime', false),
				'TXT-COL-TAGS' 		=> (string) self::text('tags', false),
				'TXT-COL-SYNTAX' 	=> (string) self::text('record_syntax', false),
				'TXT-COL-LAYOUT' 	=> (string) self::text('layout', false),
				'TXT-COL-SPECIAL' 	=> (string) self::text('special', false),
				'TXT-COL-ACTIVE' 	=> (string) self::text('active', false),
				'TXT-COL-AUTH' 		=> (string) self::text('auth', false),
				'TXT-COL-TRANSL' 	=> (string) self::text('translations', false),
				'TXT-COL-COUNTER' 	=> (string) self::text('counter', false),
				'HINT-0' 			=> (string) self::text('hint_0', false),
				'HINT-1' 			=> (string) self::text('hint_1', false),
				'HINT-2' 			=> (string) self::text('hint_2', false),
				'HINT-3' 			=> (string) self::text('hint_3', false),
				'HINT-4' 			=> (string) self::text('hint_4', false),
				'HINT-5' 			=> (string) self::text('hint_5', false),
				'HINT-6' 			=> (string) self::text('hint_6', false),
				'FMT-LIST' 			=> (string) \Smart::array_size($filter).' / '.\Smart::array_size($total),
				'DB-TYPE' 			=> (string) \SmartModExtLib\PageBuilder\Utils::getDbType()
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayListTable($y_tpl) {
		//--
		$the_link_list = (string) self::composeUrl('op=records-list-json&');
		$the_back_link_list = (string) self::composeUrl('op=records-list&tpl='.\Smart::escape_url($y_tpl)); // \SmartFrameworkRegistry::getCookieVar('PageBuilder_Smart_Slickgrid_List_URL')
		$the_alt_link_list = (string) self::composeUrl('op=records-tree&tpl='.\Smart::escape_url($y_tpl)); // \SmartFrameworkRegistry::getCookieVar('PageBuilder_Smart_Slickgrid_List_URL')
		//-- {{{SYNC-PAGEBUILDER-MANAGER-DEF-LINKS}}}
		$the_link_add = (string) self::composeUrl('op=record-add-form');
		$the_link_view = (string) self::composeUrl('op=record-view&id=');
		$the_link_delete = '';
		if(!\defined('\\SMART_PAGEBUILDER_DISABLE_DELETE')) {
			$the_link_delete = (string) self::composeUrl('op=record-delete&id=');
		} //end if
		//--
		if(\Smart::array_size((array)\SmartTextTranslations::getListOfLanguages()) > 1) {
			$show_translations = 'yes';
		} else {
			$show_translations = 'no';
		} //end if else
		//--
		if(\SmartModExtLib\PageBuilder\Utils::allowPages() === true) {
			$allow_pages = 'yes';
		} else {
			$allow_pages = 'no';
		} //end if else
		//-- #{{{SYNC-PAGEBUILDER-MANAGER-DEF-LINKS}}}
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-list.mtpl.htm',
			[
				'RELEASE-HASH' 		=> (string) \SmartUtils::get_app_release_hash(),
				'IS-DEV-MODE' 		=> (string) ((\SmartEnvironment::ifDevMode() === true) ? 'yes' : 'no'),
				'SHOW-FILTER-CTRL' 	=> 'yes',
				'SHOW-TRANSLATIONS' => (string) $show_translations,
				'ALLOW-PAGES' 		=> (string) $allow_pages,
				'LIST-FORM-URL' 	=> '#',
				'LIST-FORM-METHOD' 	=> 'POST',
				'LIST-FORM-VARS' 	=> (array) [],
				'LIST-JSON-URL' 	=> (string) $the_link_list,
				'LIST-NEW-URL' 		=> (string) $the_link_add,
				'LIST-RECORD-URL' 	=> (string) $the_link_view,
				'LIST-DELETE-URL' 	=> (string) $the_link_delete,
				'LIST-ALT-COOKIE' 	=> (string) 'PageBuilder_Smart_Slickgrid_List_URL',
				'LIST-CRR-LINK' 	=> (string) $the_back_link_list,
				'LIST-ALT-LINK' 	=> (string) $the_alt_link_list,
				'TXT-ALT-LINK' 		=> (string) self::text('ttl_ch_list', false),
				'TXT-EXPT-LINK' 	=> (string) self::text('ttl_transl_export', false),
				'LIST-EXPT-LINK' 	=> (string) self::composeUrl('op=export-translations'),
				'TXT-IMPT-LINK' 	=> (string) self::text('ttl_transl_import', false),
				'LIST-IMPT-LINK' 	=> (string) self::composeUrl('op=import-translations'),
				'TXT-WEBDAV-LINK' 	=> (string) self::text('ttl_webdav', false),
				'LIST-WEBDAV-LINK' 	=> (string) self::composeWebdavUrl(),
				'TXT-RESET-COUNTER' => (string) self::text('ttl_reset_hits', false),
				'TXT-CTRL-NONE' 	=> (string) self::text('ctrl_unassigned', false),
				'CTRLS' 			=> (array)  [],
				'CTRL-SEL' 			=> (string) '',
				'PATH-MODULE' 		=> (string) self::MODULE_PATH,
				'LIST-TTL' 			=> (string) self::text('ttl_list', false),
				'LIST-RECORDS' 		=> (string) self::text('ttl_records', false),
				'TXT-RECORDS' 		=> (string) self::text('records', false),
				'TXT-SEARCH-BY' 	=> (string) self::text('search_by', false),
				'TXT-FILTER' 		=> (string) self::text('search', false),
				'TXT-RESET' 		=> (string) self::text('reset', false),
				'TXT-ADD-NEW' 		=> (string) self::text('ttl_add', false),
				'TXT-COL-ID' 		=> (string) self::text('id', false),
				'TXT-COL-REFID' 	=> (string) self::text('ref', false),
				'TXT-COL-NAME' 		=> (string) self::text('name', false),
				'TXT-COL-CTRL' 		=> (string) self::text('ctrl', false),
				'TXT-COL-TEMPLATE' 	=> (string) self::text('template', false),
				'TXT-COL-AREA' 		=> (string) self::text('area', false),
				'TXT-COL-CODE' 		=> (string) self::text('record_code', false),
				'TXT-COL-RUNTIME' 	=> (string) self::text('record_runtime', false),
				'TXT-COL-TAGS' 		=> (string) self::text('tags', false),
				'TXT-COL-SYNTAX' 	=> (string) self::text('record_syntax', false),
				'TXT-COL-LAYOUT' 	=> (string) self::text('layout', false),
				'TXT-COL-SPECIAL' 	=> (string) self::text('special', false),
				'TXT-COL-ACTIVE' 	=> (string) self::text('active', false),
				'TXT-COL-AUTH' 		=> (string) self::text('auth', false),
				'TXT-COL-TRANSL' 	=> (string) self::text('translations', false),
				'TXT-COL-COUNTER' 	=> (string) self::text('counter', false),
				'HINT-0' 			=> (string) self::text('hint_0', false),
				'HINT-1' 			=> (string) self::text('hint_1', false),
				'HINT-2' 			=> (string) self::text('hint_2', false),
				'HINT-3' 			=> (string) self::text('hint_3', false),
				'HINT-4' 			=> (string) self::text('hint_4', false),
				'HINT-5' 			=> (string) self::text('hint_5', false),
				'HINT-6' 			=> (string) self::text('hint_6', false),
				'FMT-LIST' 			=> '# / # @',
				'DB-TYPE' 			=> (string) \SmartModExtLib\PageBuilder\Utils::getDbType()
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayListJson($ofs, $sortby, $sortdir, $srcby, $src) {
		//--
		$ofs = (int) \Smart::format_number_int($ofs, '+');
		//--
		$sortdir = (string) \strtoupper((string)$sortdir);
		if((string)$sortdir != 'ASC') {
			$sortdir = 'DESC';
		} //end if
		//--
		$limit = 25;
		//-- {{{SYNC-PAGE-BUILDER-DO-NOT-TRIM-SRC}}} do not trim $src here, will be trimmed in model if needed
		if((string)\trim((string)$src) == '') {
			$srcby = '';
		} elseif((string)\trim((string)$srcby) == '') {
			$src = '';
		} //end if
		//--
		$data = [
			'status'  			=> 'OK',
			'crrOffset' 		=> (int)    $ofs,
			'itemsPerPage' 		=> (int)    $limit,
			'sortBy' 			=> (string) $sortby,
			'sortDir' 			=> (string) $sortdir,
			'sortType' 			=> (string) '', // applies only with clientSort (not used for Server-Side sort)
			'filter' 			=> [
				'srcby' => (string) $srcby,
				'src' => (string) $src
			]
		];
		//--
		$data['totalRows'] 	= (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::listCountRecords((string)$srcby, (string)$src);
		$data['rowsList'] 	= (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::listGetRecords((string)$srcby, (string)$src, (int)$limit, (int)$ofs, (string)$sortdir, (string)$sortby);
		//--
		return (string) \Smart::json_encode((array)$data);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayResetCounter($y_redir_url='') {
		//--
		$wr = \SmartModDataModel\PageBuilder\PageBuilderBackend::resetCounterOnAllRecords();
		if($wr[1] >= 0) { // there can be no records, thus can be also zero
			$status = 'OK';
			$message = 'Hit Counter was reset on all PageBuilder Objects';
		} else {
			$status = 'ERROR';
			$message = 'There was an error trying to reset the Hit Counter on all PageBuilder Objects';
		} //end if else
		//--
		return (string) \SmartViewHtmlHelpers::js_ajax_replyto_html_form(
			(string) $status,
			'Reset Hit Counter on All PageBuilder Objects',
			(string) $message,
			(string) $y_redir_url
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayExportData($y_tpl) {
		//--
		$y_tpl = (string) $y_tpl;
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-export.mtpl.htm',
			[
				'URL-FORM-ACTION' 	=> (string) self::composeUrl('op=export-translations-spreadsheet'),
				'LANGUAGE-DEFAULT' 	=> (string) \SmartTextTranslations::getDefaultLanguage(),
				'LANGUAGES-ARR' 	=> (array)  \SmartTextTranslations::getListOfLanguages()
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayExportSpreadsheetData($mode, $lang, $arrmode) {
		//--
		if(((string)$mode != 'all') AND ((string)$mode != 'missing')) {
			return array();
		} //end if else
		if(\SmartTextTranslations::validateLanguage($lang) !== true) {
			return array();
		} //end if
		//--
		return (array) \SmartModDataModel\PageBuilder\PageBuilderBackend::exportTranslationsByLang((string)$lang, (string)$mode, (string)$arrmode);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayImportData($y_tpl, $y_appname='PageBuilder', $y_action='') {
		//--
		$y_tpl = (string) $y_tpl;
		$y_appname = (string) $y_appname;
		$y_action = (string) $y_action;
		//--
		if((string)$y_action == '') {
			$y_action = (string) self::composeUrl('op=import-translations-do');
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-import-form.mtpl.htm',
			[
				'TPL-VAR' 			=> (string) $y_tpl,
				'APP-NAME' 			=> (string) $y_appname,
				'URL-FORM-ACTION' 	=> (string) $y_action
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	public static function ViewDisplayImportDoData($y_tpl, $y_appname='PageBuilder', $y_modelclass='') {
		//--
		$y_tpl = (string) $y_tpl;
		$y_appname = (string) $y_appname;
		$y_modelclass = (string) $y_modelclass;
		//--
		if(!$_FILES['import_file']['tmp_name']) {
			return (string) \SmartComponents::operation_notice('NO File to Import (.xml)');
		} //end if
		if(\substr((string)$_FILES['import_file']['name'], -4, 4) != '.xml') {
			return (string) \SmartComponents::operation_warn('Invalid File to Import (.xml)');
		} //end if
		//--
		$input_str = (string) \SmartFileSystem::read_uploaded((string)$_FILES['import_file']['tmp_name']);
		$input_str = (array) \SmartSpreadSheetImport::parseContentAsArr((string)$input_str);
		if(\Smart::array_size($input_str) <= 0) {
			return \SmartComponents::operation_error('Invalid XL03/Xml File Format to Import');
		} //end if
		if(\Smart::array_size($input_str['header']) < 2) {
			return \SmartComponents::operation_error('Invalid XL03/Xml Table Format to Import');
		} //end if
		$hdr_arr = array();
		for($i=0; $i<\Smart::array_size($input_str['header']); $i++) {
			$tmp_head_val_orig = (string) \trim((string)$input_str['header'][$i]);
			$tmp_head_val_lang = (string) \substr((string)$tmp_head_val_orig, 6, 2);
			if((\strlen((string)$tmp_head_val_lang) == 2) AND ((string)$tmp_head_val_orig == '[lang_'.$tmp_head_val_lang.']')) {
				$hdr_arr[] = (string) $tmp_head_val_lang;
			} //end if
		} //end for
		if(\Smart::array_size($hdr_arr) != 2) {
			return \SmartComponents::operation_error('Invalid XL03/Xml Table Header to Import');
		} //end if
		$data_arr = array();
		for($i=0; $i<\Smart::array_size($input_str['data']); $i++) {
			$data_arr[(string)$hdr_arr[$i % 2]][] = (string) $input_str['data'][$i];
			$data_arr[(string)$hdr_arr[($i+1) % 2]][] = (string) $input_str['data'][$i+1];
			$i+=1;
		} //end for
		$input_str = null; // free mem
		if(\Smart::array_size($data_arr[(string)$hdr_arr[0]]) != \Smart::array_size($data_arr[(string)$hdr_arr[1]])) {
			return \SmartComponents::operation_error('Invalid XL03/Xml Table Data to Import');
		} //end if
	//	\print_r($hdr_arr); \print_r($data_arr); die();
		//--
		$def_lang = (string) \SmartTextTranslations::getDefaultLanguage();
		//--
		$arr_conform = [ 'is_imported' => 'no' ];
		$arr_conform = (array) \Smart::array_init_keys(
			(array) $arr_conform,
			[
				'default',
				'is_transl_empty',
				'is_base_empty',
				'is_base_diff_transl',
				'is_imported',
				'status',
				'diffs',
				'translate',
			]
		);
		//--
		$out_total = 0;
		$real_imported = 0;
		$arr_xdata = [];
		foreach($data_arr as $lang => $val) {
			//--
			$x_iterator = 0;
			//--
			if(((string)$lang != (string)$def_lang) AND (\SmartTextTranslations::validateLanguage((string)$lang))) {
				//--
				if(\is_array($val)) {
					//--
					for($i=0; $i<\Smart::array_size($val); $i++) {
						//--
						$x_is_all_empty = false;
						$x_is_empty = true;
						$x_is_tempty = true;
						$x_is_diff = true;
						$x_is_not_imported = true;
						$diffs_arr_rows = [];
						//--
						if((string)\trim((string)$data_arr[(string)$def_lang][$i]) != '') {
							//--
							$x_is_empty = false;
							//--
							if((string)\trim((string)$val[$i]) != '') {
								//--
								$x_is_tempty = false;
								//--
								$arr_placeholder_and_marker_diffs = (array) \SmartModExtLib\PageBuilder\Utils::comparePlaceholdersAndMarkers((string)$data_arr[(string)$def_lang][$i], (string)$val[$i]);
								//--
								if(\Smart::array_size($arr_placeholder_and_marker_diffs) <= 0) {
									//--
									$x_is_diff = false;
									//--
									if((string)$y_modelclass != '') {
										$upd = (int) $y_modelclass::updateTranslationByText((string)$data_arr[(string)$def_lang][$i], (string)$lang, (string)$val[$i], (string)\SmartAuth::get_auth_id());
									} else {
										$upd = (int) \SmartModDataModel\PageBuilder\PageBuilderBackend::updateTranslationByText((string)$data_arr[(string)$def_lang][$i], (string)$lang, (string)$val[$i], (string)\SmartAuth::get_auth_id());
									} //end if else
									//--
									if($upd > 0) {
										$real_imported++;
										$x_is_not_imported = false;
									} //end if
									if((string)$dbg == 'yes') {
										if($upd < -1) {
											\SmartEnvironment::setDebugMsg('extra', 'IMPORT-TRANSLATIONS', [
												'title' => '[Import Translations: '.$y_appname.']',
												'data' => 'ERROR('.$upd.'): Could not Find for Update PageBuilder Translations for text: `'.(string)$data_arr[(string)$def_lang][$i].'`'
											]);
										} elseif($upd == -1) {
											// no translation
										} elseif($upd == 0) {
											\SmartEnvironment::setDebugMsg('extra', 'IMPORT-TRANSLATIONS', [
												'title' => '[Import Translations: '.$y_appname.']',
												'data' => 'WARN: Could not Update PageBuilder Translations for text: `'.(string)$data_arr[(string)$def_lang][$i].'`'
											]);
										} //end if else
									} //end if
									//--
								} else {
									//--
									$diffs_arr_rows = (array) $arr_placeholder_and_marker_diffs;
									//--
								} //end if else
								//--
								$arr_placeholder_and_marker_diffs = array();
								//--
							} //end if
							//--
						} elseif((string)\trim((string)$val[$i]) == '') { // skip if both empty
							//--
							$x_is_all_empty = true;
							//--
						} //end if else
						//--
						if($x_is_all_empty === false) {
							//--
							if(!\is_array($arr_xdata[(int)$x_iterator])) {
								$arr_xdata[(int)$x_iterator] = (array) $arr_conform;
							} //end if
							$status = 'ok';
							if($x_is_empty || $x_is_tempty) {
								$x_is_diff = false; // FIX
							} //end if
							if($x_is_empty || $x_is_tempty || $x_is_diff || $x_is_not_imported) {
								$status = 'warn';
								if(!$x_is_tempty) {
									$status = 'warn-crit';
								} //end if
							} //end if
							$arr_xdata[(int)$x_iterator]['is_transl_empty'] = (string) ($x_is_tempty ? 'yes' : 'no');
							$arr_xdata[(int)$x_iterator]['is_base_empty'] = (string) ($x_is_empty ? 'yes' : 'no');
							$arr_xdata[(int)$x_iterator]['is_base_diff_transl'] = (string) ($x_is_diff ? 'yes' : 'no');
							$arr_xdata[(int)$x_iterator]['is_imported'] = (string) (!$x_is_not_imported ? 'yes' : 'no');
							$arr_xdata[(int)$x_iterator]['status'] = (string) $status;
							$arr_xdata[(int)$x_iterator]['diffs'] = (string) \implode(', ', (array)$diffs_arr_rows);
							$arr_xdata[(int)$x_iterator]['translate'] = (string) $val[$i];
							$x_iterator++;
							//--
						} //end if
						//--
					} //end for
					//--
				} //end if
				//--
			} elseif((string)$lang == (string)$def_lang) {
				//--
				if(\is_array($val)) {
					//--
					for($i=0; $i<\Smart::array_size($val); $i++) {
						//--
						if(((string)\trim((string)$data_arr[(string)$def_lang][$i]) != '') AND ((string)\trim((string)$val[$i]) != '')) { // skip all empty records
							//--
							if(!\is_array($arr_xdata[(int)$x_iterator])) {
								$arr_xdata[(int)$x_iterator] = (array) $arr_conform;
							} //end if
							//--
							$arr_xdata[(int)$x_iterator]['default'] = (string) $val[$i];
							$x_iterator++;
							//--
							$out_total++;
							//--
						} //end if
						//--
					} //end for
					//--
				} //end if
				//--
			} else { // INVALID LANGUAGE CASE
				//--
				if(\is_array($val)) {
					//--
					for($i=0; $i<\Smart::array_size($val); $i++) {
						//--
						if(((string)\trim((string)$data_arr[(string)$def_lang][$i]) != '') AND ((string)\trim((string)$val[$i]) != '')) { // skip all empty records
							//--
							if(!\is_array($arr_xdata[(int)$x_iterator])) {
								$arr_xdata[(int)$x_iterator] = (array) $arr_conform;
							} //end if
							//--
							$arr_xdata[(int)$x_iterator]['is_transl_empty'] = 'no';
							$arr_xdata[(int)$x_iterator]['is_base_empty'] = 'no';
							$arr_xdata[(int)$x_iterator]['is_base_diff_transl'] = 'yes';
							$arr_xdata[(int)$x_iterator]['is_imported'] = 'no';
							$arr_xdata[(int)$x_iterator]['status'] = 'warn-crit';
							$arr_xdata[(int)$x_iterator]['diffs'] = '! INVALID LANGUAGE !';
							$arr_xdata[(int)$x_iterator]['translate'] = (string) $val[$i];
							$x_iterator++;
							//--
						} //end if
						//--
					} //end for
					//--
				} //end if
				//--
			} //end if
			//--
		} //end foreach
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) self::MODULE_PATH.'libs/views/manager/view-import-result.mtpl.htm',
			[
				'TPL-VAR' 			=> (string) $y_tpl,
				'APP-NAME' 			=> (string) $y_appname,
				'TOTAL-RECORDS' 	=> (int)    $out_total,
				'TOTAL-IMPORTED' 	=> (int)    $real_imported,
				'TOTAL-ERRORS' 		=> (int)    ($out_total - $real_imported),
				'HEAD-ARR' 			=> (array)  $hdr_arr,
				'DATA-ARR' 			=> (array)  $arr_xdata
			]
		);
		//--
	} //END FUNCTION
	//==================================================================


	//=== PRIVATES ===


	//==================================================================
	private static function composeUrl($y_suffix) {
		//--
		return (string) \Smart::url_add_suffix(
			(string) self::ADM_AREA_SCRIPT.'?/'.\Smart::escape_url(self::URL_PARAM_PAGE).'/'.\Smart::escape_url(self::URL_VAL_MANAGE_PAGE),
			(string) $y_suffix
		);
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function composeWebdavUrl() {
		//--
		return (string) self::ADM_AREA_SCRIPT.'/'.\Smart::escape_url(self::URL_PARAM_PAGE).'/'.\Smart::escape_url(self::URL_VAL_FMANAGE_PAGE).'/~';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function testIsSegmentPage($y_id) {
		//--
		$out = 0;
		//--
		if((string)\substr((string)$y_id, 0, 1) == '#') {
			$out = 1;
		} //endd if
		//--
		return (int) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getPreviewButtons($id) {
		//--
		$out = '';
		//--
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		//--
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview-code.svg'.'" alt="'.self::text('pw_code').'" title="'.self::text('pw_code').'" style="cursor:pointer;" onClick="smartJ$Browser.PopUpLink(\''.\Smart::escape_js(self::composeUrl('op=record-view-highlight-code&id='.\Smart::escape_url($id))).'\', \'page-builder-pw\', null, null, 1); return false;">';
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview-data.svg'.'" alt="'.self::text('pw_data').'" title="'.self::text('pw_data').'" style="cursor:pointer;" onClick="smartJ$Browser.PopUpLink(\''.\Smart::escape_js(self::composeUrl('op=record-view-highlight-data&id='.\Smart::escape_url($id))).'\', \'page-builder-pw\', null, null, 1); return false;">';
		$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$out .= '<img src="'.self::MODULE_PATH.'libs/views/manager/img/op-preview-media.svg'.'" alt="'.self::text('pw_media').'" title="'.self::text('pw_media').'" style="cursor:pointer;" onClick="smartJ$Browser.PopUpLink(\''.\Smart::escape_js(self::composeUrl('op=record-view-media&id='.\Smart::escape_url($id))).'\', \'page-builder-pw\', null, null, 1); return false;">';
		//--
		return (string) $out;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getImgForPageType($y_id) {
		//--
		if(self::testIsSegmentPage($y_id)) { // segment
			$img = self::MODULE_PATH.'libs/views/manager/img/type-segment.svg';
		} else { // page
			$img = self::MODULE_PATH.'libs/views/manager/img/type-page.svg';
		} //end if else
		//--
		return (string) $img;
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getImgForRef($y_ref) {
		//--
		$y_ref = (string) \trim((string)$y_ref);
		//--
		if((string)$y_ref == '') {
			return '';
		} //end if
		//--
		if((string)$y_ref == '-') {
			return '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/ref-n-a.svg'.'" alt="-" title="-">'; // for pages that cannot be assigned with a ref (ex: website menu)
		} //end if
		//--
		return '<img height="16" src="'.self::MODULE_PATH.'libs/views/manager/img/ref-parent.svg'.'" alt="'.\Smart::escape_html($y_ref).'" title="'.\Smart::escape_html($y_ref).'">';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getImgForCodeType($y_id, $y_type, $y_scope='list') {
		//--
		$kind = 'Type';
		$ttl = '[Unknown]';
		$img = self::MODULE_PATH.'libs/views/manager/img/syntax-unknown.svg';
		//--
		if(self::testIsSegmentPage($y_id)) {
			$kind = 'Segment';
			switch((string)$y_type) {
				case 'settings':
					$ttl = 'SETTINGS';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-settings.svg';
					break;
				case 'text':
					$ttl = 'TEXT';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-text.svg';
					break;
				case 'markdown':
					$ttl = 'MARKDOWN';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-markdown.svg';
					break;
				case 'html':
					$ttl = 'HTML';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-html.svg';
					break;
				default:
					// unknown
			} //end switch
		} else {
			$kind = 'Page';
			switch((string)$y_type) {
				case 'raw':
					$ttl = 'RAW';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-raw.svg';
					break;
				case 'text':
					$ttl = 'TEXT';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-text.svg';
					break;
				case 'markdown':
					$ttl = 'MARKDOWN';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-markdown.svg';
					break;
				case 'html':
					$ttl = 'HTML';
					$img = self::MODULE_PATH.'libs/views/manager/img/syntax-html.svg';
				default:
					// unknown
			} //end switch
		} //end if else
		//--
		$title = (string) $ttl.' '.$kind;
		if((string)$y_scope == 'tabs') {
			$title = (string) $ttl.' '.self::text('record_code');
		} //end if
		//--
		return '<img height="16" src="'.$img.'" alt="'.$title.'" title="'.$title.'">';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getImgForRestrictionsStatus($y_id, $y_status) {
		//--
		if(self::testIsSegmentPage($y_id)) {
			$img = self::MODULE_PATH.'libs/views/manager/img/restr-private.svg';
			$ttl = self::text('restr_acc');
		} elseif($y_status == 1) {
			$img = self::MODULE_PATH.'libs/views/manager/img/restr-login.svg';
			$ttl = self::text('login_acc');
		} else {
			$img = self::MODULE_PATH.'libs/views/manager/img/restr-public.svg';
			$ttl = self::text('free_acc');
		} //end if else
		//--
		return '<img height="16" src="'.$img.'" alt="'.$ttl.'" title="'.$ttl.'">';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function getImgForActiveStatus($y_id, $y_status) {
		//--
		if(self::testIsSegmentPage($y_id)) {
			return '';
		} else {
			switch((string)$y_status) {
				case '1':
					$img = self::MODULE_PATH.'libs/views/manager/img/status-active.svg';
					$ttl = self::text('yes');
					break;
				case '0':
				default:
					$img = self::MODULE_PATH.'libs/views/manager/img/status-inactive.svg';
					$ttl = self::text('no');
			} //end switch
		} //end if else
		//--
		return '<img src="'.$img.'" alt="'.$ttl.'" title="'.$ttl.'">';
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function drawFieldCtrl($y_id, $y_issubsegment, $y_mode, $y_var='', $y_width='65') {
		//--
		if((string)$y_mode == 'form') {
			if($y_issubsegment === true) {
				$prop_placeholder = 'Controller Name (N/A)';
				$prop_readonly = ' readonly disabled';
			} else {
				$prop_placeholder = 'Controller Name';
				$prop_readonly = '';
			} //end if else
			return (string) '<input type="text" name="'.\Smart::escape_html((string)$y_var).'" value="'.\Smart::escape_html((string)$y_id).'" size="'.\Smart::format_number_int($y_width,'+').'" maxlength="128" autocomplete="off" placeholder="'.\Smart::escape_html($prop_placeholder).'"'.$prop_readonly.'>';
		} else {
			return (string) \Smart::escape_html($y_id);
		} //end if else
		//--
	} //END FUNCTION
	//==================================================================


	//==================================================================
	private static function drawFieldLayoutPages($y_mode, $y_listmode, $y_value, $y_htmlvar='') {
		//--
		return (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('', $y_value, $y_listmode, (array)\SmartModExtLib\PageBuilder\Utils::getAvailableLayouts(), $y_htmlvar, '250', '', 'no', 'no');
		//--
	} //END FUNCTION
	//==================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
