<?php
// Controller: PageBuilder/Manage
// Route: ?/page/page-builder.manage
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

if(!defined('SMART_PAGEBUILDER_HTML_VALIDATOR')) {
	define('SMART_PAGEBUILDER_HTML_VALIDATOR', 'any:prefer:tidy'); // if available, use one ... ; {{{SYNC-PAGEBUILDER-FALLBACK-HTML-VALIDATOR}}}
} //end if

/**
 * PageBuilder Manage
 *
 * @ignore
 *
 */
final class SmartAppAdminController extends SmartAbstractAppController {

	// r.20250124

	public function Run() {

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetCfg('error', 'PageBuilder Manage requires Authentication ! ...');
			return 403;
		} //end if
		//--
		if((string)\SmartModExtLib\PageBuilder\Utils::getDbType() == '') {
			$this->PageViewSetCfg('error', 'PageBuilder DB Type not set in configs: SMART_PAGEBUILDER_DB_TYPE ! ...');
			return 503;
		} //end if
		//--

		//--
		$this->PageViewSetVar('title', 'Web / PageBuilder :: Manage');
		//--

		//--
		$op = $this->RequestVarGet('op', 'records-list', 'string');
		//--
		switch((string)$op) {
			case 'records-list': // HTML: list view
				$tpl = $this->RequestVarGet('tpl', '', 'string');
				if((string)$tpl != 'custom') {
					$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
					$this->PageViewSetCfg('template-file', 'template.htm');
				} //end if
				$this->PageViewSetVars([
					'main' => (string) \SmartModExtLib\PageBuilder\Manager::ViewDisplayListTable($tpl)
				]);
				break;
			case 'records-list-json': // JSON: for list
				$ofs = $this->RequestVarGet('ofs', 0, 'integer+');
				$sortby = $this->RequestVarGet('sortby', 'name', 'string');
				$sortdir = $this->RequestVarGet('sortdir', 'ASC', 'string');
				$srcby = $this->RequestVarGet('srcby', '', 'string');
				$src = $this->RequestVarGet('src', '', 'string');
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/json');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayListJson($ofs, $sortby, $sortdir, $srcby, $src)
				);
				break;
			case 'records-tree': // HTML: tree view
				$srcby = $this->RequestVarGet('srcby', '', 'string');
				$src = $this->RequestVarGet('src', '', 'string');
				$ctrl = $this->RequestVarGet('ctrl', '', 'string');
				$tpl = $this->RequestVarGet('tpl', '', 'string');
				if((string)$tpl != 'custom') {
					$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
					$this->PageViewSetCfg('template-file', 'template.htm');
				} //end if
				$this->PageViewSetVars([
					'main' => (string) \SmartModExtLib\PageBuilder\Manager::ViewDisplayTree($tpl, $srcby, $src, $ctrl)
				]);
				break;
			case 'record-add-form':
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormAdd()
				);
				break;
			case 'record-add-do': // JSON
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/json');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormsSubmit('add', $frm)
				);
				break;
			case 'record-view':
				$id = $this->RequestVarGet('id', '', 'string');
				$sop = $this->RequestVarGet('sop', '', 'string');
				$translate = $this->RequestVarGet('translate', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayRecord($id, $sop, $translate)
				);
				break;
			case 'record-view-tab-props': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormProps($id, 'view')
				);
				break;
			case 'record-edit-tab-props': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormProps($id, 'form')
				);
				break;
			case 'record-preview-tab-code': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$translate = $this->RequestVarGet('translate', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormMarkupCode($id, 'view', $translate)
				);
				break;
			case 'record-view-tab-code': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$mode = $this->RequestVarGet('mode', 'codeview', ['codeview','codesrcview']);
				$translate = $this->RequestVarGet('translate', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormMarkupCode($id, $mode, $translate)
				);
				break;
			case 'record-edit-tab-code': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$translate = $this->RequestVarGet('translate', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormMarkupCode($id, 'form', $translate)
				);
				break;
			case 'record-view-tab-data': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormYamlData($id, 'view')
				);
				break;
			case 'record-preview-tab-data': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormYamlData($id, 'preview')
				);
				break;
			case 'record-edit-tab-data': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormYamlData($id, 'form')
				);
				break;
			case 'record-view-tab-info': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormInfo($id, 'view')
				);
				break;
			case 'record-view-tab-media': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormMedia($id, 'view')
				);
				break;
			case 'record-edit-tab-media': // HTML
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormMedia($id, 'form')
				);
				break;
			case 'record-upload-media': // JSON
				$id = $this->RequestVarGet('id', '', 'string');
				$type = $this->RequestVarGet('type', '', 'string');
				$name = $this->RequestVarGet('name', '', 'string');
				$content = $this->RequestVarGet('content', '', 'string');
				$cksum = $this->RequestVarGet('cksum', '', 'string');
				$as = $this->RequestVarGet('as', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::UploadMedia($id, $type, $name, $content, $cksum, $as)
				);
				break;
			case 'record-delete-media': // JSON
				$id = $this->RequestVarGet('id', '', 'string');
				$fname = $this->RequestVarGet('fname', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::DeleteMedia($id, $fname)
				);
				break;
			case 'record-view-highlight-code': // HTML: preview code
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayHighlightCode($id)
				);
				break;
			case 'record-view-highlight-data': // HTML: preview data
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayHighlightData($id)
				);
				break;
			case 'record-view-media': // HTML: preview media
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayMedia($id)
				);
				break;
			case 'record-edit-do': // JSON
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormsSubmit('edit', $frm, $id)
				);
				break;
			case 'record-delete':
				$id = $this->RequestVarGet('id', '', 'string');
				$delete = $this->RequestVarGet('delete', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormDelete($id, $delete)
				);
				break;
			case 'record-clone':
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormClone($id)
				);
				break;
			case 'record-clone-do': // JSON
				$frm = $this->RequestVarGet('frm', array(), 'array');
				$id = $this->RequestVarGet('id', '', 'string');
				$this->PageViewSetCfg('rawpage', 'yes');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewFormsSubmit('clone', $frm, $id)
				);
				break;
			case 'reset-counter': // JSON
				$back = $this->RequestVarGet('back', '', 'string');
				$this->PageViewSetCfg('rawpage', true);
				$this->PageViewSetCfg('rawmime', 'text/json');
				$this->PageViewSetCfg('rawdisp', 'inline');
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayResetCounter($back)
				);
				break;
			case 'export-translations': // HTML
				$tpl = $this->RequestVarGet('tpl', '', 'string');
				if((string)$tpl != 'custom') {
					$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
					$this->PageViewSetCfg('template-file', 'template.htm');
				} //end if
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayExportData($tpl)
				);
				break;
			case 'export-translations-spreadsheet': // SpreadSheet
				$exportlang = $this->RequestVarGet('exportlang', '', 'string');
				if(SmartTextTranslations::validateLanguage($exportlang) !== true) {
					$this->PageViewSetErrorStatus(400, 'ERROR: Invalid PageBuilder Export Language: '.$exportlang);
					return;
				} //end if
				$mode = $this->RequestVarGet('mode', 'all', ['all','missing']);
				if(((string)$mode != 'all') AND ((string)$mode != 'missing')) {
					$this->PageViewSetErrorStatus(400, 'ERROR: Invalid PageBuilder Export Mode: '.$mode);
					return;
				} //end if
				$this->PageViewSetCfg('rawpage', true);
				$spreadsheet = new SmartSpreadSheetExport(500, 50);
				$this->PageViewSetCfg('rawmime', (string)$spreadsheet->getMimeType());
				if((string)$exportlang == (string)SmartTextTranslations::getDefaultLanguage()) {
					$extralang = '@';
					$arrsheets = [
						'[lang_'.SmartTextTranslations::getDefaultLanguage().']'
					];
				} else {
					$extralang = (string) substr((string)trim((string)$exportlang), 0, 2);
					$arrsheets = [
						'[lang_'.SmartTextTranslations::getDefaultLanguage().']',
						'[lang_'.$exportlang.']'
					];
				} //end if else
				$this->PageViewSetCfg('rawdisp', (string)$spreadsheet->getDispositionHeader('translations-pgbld-'.SmartTextTranslations::getDefaultLanguage().'_'.$extralang.'-'.substr((string)$mode,0,3).'-'.date('Ymd_His').'.xml', 'attachment'));
				$this->PageViewSetVar(
					'main',
					(string) $spreadsheet->getFileContents(
						'PageBuilder Transl. - '.$mode,
						(array) $arrsheets,
						(array) \SmartModExtLib\PageBuilder\Manager::ViewDisplayExportSpreadsheetData((string)$mode, (string)$exportlang, 'associative')
					)
				);
				$spreadsheet = null;
				break;
			case 'import-translations': // HTML
				$tpl = $this->RequestVarGet('tpl', '', 'string');
				if((string)$tpl != 'custom') {
					$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
					$this->PageViewSetCfg('template-file', 'template.htm');
				} //end if
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayImportData($tpl)
				);
				break;
			case 'import-translations-do': // HTML
				$tpl = $this->RequestVarGet('tpl', '', 'string');
				if((string)$tpl != 'custom') {
					$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
					$this->PageViewSetCfg('template-file', 'template.htm');
				} //end if
				$this->PageViewSetVar(
					'main',
					\SmartModExtLib\PageBuilder\Manager::ViewDisplayImportDoData($tpl)
				);
				break;
			default:
				$this->PageViewSetCfg('error', 'ERROR: Invalid PageBuilder Manager Operation: '.$op);
				return 400;
		} //end switch
		//--

	} //END FUNCTION

} //END CLASS

// end of php code
