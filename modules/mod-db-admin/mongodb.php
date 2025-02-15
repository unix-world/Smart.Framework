<?php
// Controller: DnAdmin.Mongodb
// Route: admin.php?/page/db-admin.mongodb (admin.php?page=db-admin.mongodb)
// (c) 2008-present unix-world.org - all rights reserved

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'ADMIN'); // ADMIN
define('SMART_APP_MODULE_AUTH', true); // requires auth always

/**
 * Admin Area Controller
 * @version 20250207
 * @ignore
 *
 * @requires define('SMART_FRAMEWORK_DB_ADMIN_ALLOW', true); // set in config-admin.php
 */
final class SmartAppAdminController extends SmartAbstractAppController {


	public function Initialize() {
		//--
		if(!SmartAppInfo::TestIfModuleExists('mod-auth-admins')) {
			$this->PageViewSetErrorStatus(500, 'Mod AuthAdmins is missing !');
			return false;
		} //end if
		//--
		$this->PageViewSetCfg('template-path', 'modules/mod-auth-admins/templates/');
		$this->PageViewSetCfg('template-file', 'template.htm');
		//--
		$semaphores = [];
		$semaphores[] = 'styles:dark';
		$this->PageViewSetVar('semaphore', (string)Smart::array_to_list($semaphores));
		//--
		return true;
		//--
	} //END FUNCTION


	public function Run() {

		//--
		if(!defined('SMART_FRAMEWORK_DB_ADMIN_ALLOW') OR (SMART_FRAMEWORK_DB_ADMIN_ALLOW !== true)) {
			$this->PageViewSetErrorStatus(403, 'INFO: The access to Mod DB Admin is disabled. Read the module documentation how to enable it ...');
			return;
		} //end if
		//--

		//--
		if(SmartAuth::is_authenticated() !== true) {
			$this->PageViewSetCfg('error', 'MongoDB Admin Requires Authentication ...');
			return 403;
		} //end if
		//--
		if(!SmartEnvironment::isAdminArea()) { // allow: adm/tsk ; but this controller does not extends in a task controller
			$this->PageViewSetCfg('error', 'MongoDB Admin is allowed to run under `Admin` area only ...');
			return 403;
		} //end if
		//--
		if(SmartAuth::test_login_privilege('super-admin') !== true) { // PRIVILEGES
			$this->PageViewSetCfg('error', 'MongoDB Admin requires the following privileges: `super-admin` ...');
			return 403;
		} //end if
		//--
		if(SmartAuth::test_login_privilege('db-admin:mongo') !== true) { // PRIVILEGES
			$this->PageViewSetCfg('error', 'MongoDB Admin requires the following privileges: `db-admin-mongo` ...');
			return 403;
		} //end if
		//--

		//--
		if(Smart::array_size(Smart::get_from_config('mongodb')) <= 0) {
			$this->PageViewSetErrorStatus(500, 'MongoDB Server: Not Configured ...');
			return;
		} //end if
		//--

		//--
		$the_base_url = 'admin.php?page='.$this->ControllerGetParam('controller');
		$the_cookiename_collection = 'SmartDbAdminMongoCollection';
		//--

		//--
		$the_collection = (string) trim((string)$this->CookieVarGet((string)$the_cookiename_collection));
		//--
		$tmp_arr_collections = [];
		$tmp_arr_list_collections = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbCollections();
		for($i=0; $i<Smart::array_size($tmp_arr_list_collections); $i++) {
			if(is_array($tmp_arr_list_collections[$i])) {
				$tmp_arr_list_collections[$i]['name'] = (string) trim((string)($tmp_arr_list_collections[$i]['name'] ?? null));
				if((string)$tmp_arr_list_collections[$i]['name'] != '') {
					$tmp_arr_collections[(string)$tmp_arr_list_collections[$i]['name']] = (int) $i;
				} //end if
			} //end if
		} //end for
		$collections_list = [];
		$collections_arr = [];
		$collection_selected = false;
		ksort($tmp_arr_collections);
		foreach($tmp_arr_collections as $ckey => $cval) { // rebuild sorted collections, only need name ; this can't be done simpler because need to preserve collection index
			$collections_list[] = (array) $tmp_arr_list_collections[(int)$cval];
			$collections_arr[] = (string) $ckey;
		} //end foreach
		foreach($tmp_arr_collections as $ckey => $cval) {
			if((string)$the_collection != '') {
				if((string)$ckey === (string)$the_collection) {
					$collection_selected = true;
					break;
				} //end if
			} //end foreach
		} //end if
		$tmp_arr_collections = null; // clear
		//--

		//--
		$id_ = (string) trim((string)$this->RequestVarGet('id_', '', 'string'));
		//--
		$mode = $this->RequestVarGet('mode', 'raw', ['raw','visual']);
		//--
		$action = $this->RequestVarGet('action', '', 'string');
		//--
		switch((string)$action) {

			case 'close-modal': // Closes the Modal and Refresh the Parent (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$rdr = (string) trim((string)$this->RequestVarGet('rdr', '', 'string'));
				if((string)$rdr != '') {
					$final_js = 'smartJ$Browser.RedirectDelayedToURL(\''.Smart::escape_js((string)$rdr).'\', 1000)';
				} else {
					$final_js = 'smartJ$Browser.CloseDelayedModalPopUp();';
				} //end if
				//--
				$this->PageViewSetVars([
					'title' => 'Wait ...',
					'main' => '<br><div><center><img src="lib/framework/img/loading-bars.svg" width="64" height="64"></center></div>'.
					'<script>smartJ$Browser.RefreshParent();</script>'.
					'<script>'.$final_js.'</script>'
				]);
				//--
				break;

			case 'new-form': // Form for Add new Record (OUTPUTS: HTML)
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$param_collection = (string) trim((string)$this->RequestVarGet('collection', '', 'string'));
				//--
				if((string)$param_collection == '@NEW@') {
					$title = 'Create New Collection & Add New Record';
					$txt_action = 'Create Collection & Insert Record';
				} else {
					$title = 'Add New Record';
					$txt_action = 'Insert Record';
				} //end if else
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-record-form.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $the_base_url.'&action=new-add',
							'ACTION-TXT' 		=> (string) $txt_action,
							'ACTION-METHOD' 	=> (string) 'new',
							'TXT-COLLECTION' 	=> (string) 'Collection Name',
							'THE-TITLE' 		=> (string) $title,
							'HOST' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 		=> (string) (((string)$param_collection == '@NEW@') ? $param_collection : $the_collection),
							'DATA-JSON' 		=> '{'."\n\n".'}',
							'CHECKSUM-HASH' 	=> (string) sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.(((string)$param_collection == '@NEW@') ? $param_collection : $the_collection)),
							'RECORD-ID' 		=> (string) '',
							'QMODE' 			=> (string) $mode // raw | visual
						]
					)
				]);
				//--
				break;

			case 'edit-form': // Form for Edit Record (OUTPUTS: HTML)
				//--
				if((string)$the_collection == '') {
					$this->PageViewSetCfg('error', 'No Collection Selected');
					return 400;
				} //end if
				//--
				$data = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecord((string)$the_collection, (string)$id_);
				if(Smart::array_size($data) <= 0) {
					$this->PageViewSetCfg('error', 'Invalid Record UID: `'.$id_.'`');
					return 400;
				} //end if
				//-- {{{SYNC-DB-MONGO-ADMIN-UNSET-_ID}}}
			//	if(is_array($data)) {
			//		if(array_key_exists('_id', (array)$data)) {
			//			unset($data['_id']); // the UID must NOT be edited / changed
			//		} //end if
			//	} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Edit Record';
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-record-form.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $the_base_url.'&action=edit-record&id_='.Smart::escape_url((string)$id_),
							'ACTION-TXT' 		=> (string) 'Save Record',
							'ACTION-METHOD' 	=> (string) 'edit',
							'THE-TITLE' 		=> (string) $title,
							'HOST' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 		=> (string) $the_collection,
							'DATA-JSON' 		=> (string) Smart::json_encode((array)$data, true, true, false),
							'CHECKSUM-HASH' 	=> (string) sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection.'#'.$id_),
							'RECORD-ID' 		=> (string) $id_,
							'QMODE' 			=> (string) $mode // raw | visual
						]
					)
				]);
				//--
				break;

			case 'delete-confirm': // Confirm for Delete Record (OUTPUTS: HTML)
				//--
				if((string)$the_collection == '') {
					$this->PageViewSetCfg('error', 'No Collection Selected');
					return 400;
				} //end if
				//--
				$data = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecord((string)$the_collection, (string)$id_);
				if(Smart::array_size($data) <= 0) {
					$this->PageViewSetCfg('error', 'Invalid Record UID: `'.$id_.'`');
					return 400;
				} //end if
				//-- {{{SYNC-DB-MONGO-ADMIN-UNSET-_ID}}}
			//	if(is_array($data)) {
			//		if(array_key_exists('_id', (array)$data)) {
			//			unset($data['_id']); // the UID must NOT be edited / changed
			//		} //end if
			//	} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'View / Delete Record';
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-record-delete.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $the_base_url.'&action=delete-record&id_='.Smart::escape_url((string)$id_),
							'ACTION-TXT' 		=> (string) 'Delete Record',
							'THE-TITLE' 		=> (string) $title,
							'HOST' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 		=> (string) $the_collection,
							'DATA-JSON' 		=> (string) Smart::json_encode((array)$data, true, true, false),
							'CHECKSUM-HASH' 	=> (string) sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection.'#'.$id_),
							'RECORD-ID' 		=> (string) $id_,
						]
					)
				]);
				//--
				break;

			case 'drop-collection-confirm': // Confirm for Drop Collection (OUTPUTS: HTML)
				//--
				if((string)$the_collection == '') {
					$this->PageViewSetCfg('error', 'No Collection Selected');
					return 400;
				} //end if
				//--
				if($collection_selected !== true) {
					$this->PageViewSetCfg('error', 'Invalid Collection Selected: `'.$the_collection.'`');
					return 400;
				} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Drop Collection';
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-collection-drop-confirm.mtpl.htm',
						[
							'ACTIONS-URL' 			=> (string) $the_base_url.'&action=drop-a-collection&collection='.Smart::escape_url((string)$the_collection),
							'ACTION-TXT' 			=> (string) 'Drop the collection: `'.(string)$the_collection.'`',
							'THE-TITLE' 			=> (string) $title,
							'HOST' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 			=> (string) $the_collection,
							'TOTAL-ALL-RECORDS' 	=> (int)    \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecordsCount((string)$the_collection, [])
						]
					)
				]);
				//--
				break;

			case 'drop-index-form': // display the Drop Index form (OUTPUTS: HTML)
				//--
				if((string)$the_collection == '') {
					$this->PageViewSetCfg('error', 'No Collection Selected');
					return 400;
				} //end if
				//--
				if($collection_selected !== true) {
					$this->PageViewSetCfg('error', 'Invalid Collection Selected: `'.$the_collection.'`');
					return 400;
				} //end if
				//--
				$collection_indexes = (array) $this->getCollectionIndexes((string)$the_collection, (bool)$collection_selected);
				if(array_key_exists('_id_', (array)$collection_indexes)) {
					unset($collection_indexes['_id_']); // special idx
				} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Drop an Index from Collection';
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-index-delete-form.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $the_base_url.'&action=delete-index-do',
							'ACTION-TXT' 		=> (string) 'Drop Selected Index',
							'THE-TITLE' 		=> (string) $title,
							'HOST' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 		=> (string) $the_collection,
							'ARR-INDEXES' 		=> (array)  $collection_indexes,
							'COLLINDEXES' 		=> (string) ((Smart::array_size($collection_indexes) > 0) ? Smart::json_encode($collection_indexes, true, true, false) : '{}'),
							'CHECKSUM-HASH' 	=> (string) sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection),
						]
					)
				]);
				//--
				break;

			case 'add-index-form': // display the Add Index form (OUTPUTS: HTML)
				//--
				if((string)$the_collection == '') {
					$this->PageViewSetCfg('error', 'No Collection Selected');
					return 400;
				} //end if
				//--
				$this->PageViewSetCfg('template-file', 'template-modal.htm');
				//--
				$title = 'Create a New Index for Collection';
				$this->PageViewSetVars([
					'title' => (string) $title,
					'main' => SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-index-add-form.mtpl.htm',
						[
							'ACTIONS-URL' 		=> (string) $the_base_url.'&action=add-index-do',
							'ACTION-TXT' 		=> (string) 'Create New Index',
							'THE-TITLE' 		=> (string) $title,
							'HOST' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'COLLECTION' 		=> (string) $the_collection,
							'DATA-JSON' 		=> (string) '{'."\n".'"name": "",'."\n".'"key": { "": 1, "": -1 },'."\n".'"unique": false'."\n".'}',
							'CHECKSUM-HASH' 	=> (string) sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection)
						]
					)
				]);
				//--
				break;

			case 'delete-index-do':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm = $this->RequestVarGet('frm', [], 'array');
				if(!is_array($frm)) {
					$frm = array();
				} //end if
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					$frm = $this->validateIndexDropFormData((string)$the_collection, (array)$frm);
					if(!is_array($frm)) {
						$message = 'ERR: '.$frm;
					} else {
						$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::dropIndex((string)$the_collection, (string)$frm['drop-index']);
						if((string)$message === 'OK') {
							$status = 'OK';
							$message = 'Index Drop SUCCESSFUL';
							$redirect = (string) $the_base_url.'&action=close-modal';
						} //end if
					} //end if
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'add-index-do':
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm = $this->RequestVarGet('frm', [], 'array');
				if(!is_array($frm)) {
					$frm = array();
				} //end if
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					$frm = $this->validateIndexAddFormData((string)$the_collection, (array)$frm);
					if(!is_array($frm)) {
						$message = 'ERR: '.$frm;
					} else {
						$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::createIndex((string)$the_collection, (array)$frm);
						if((string)$message === 'OK') {
							$status = 'OK';
							$message = 'Index Created SUCCESSFUL';
							$redirect = (string) $the_base_url.'&action=close-modal';
						} //end if
					} //end if
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'drop-a-collection': // Drop a Collection (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$param_collection = (string) trim((string)$this->RequestVarGet('collection', '', 'string'));
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if(((string)$param_collection == '') OR (!\SmartModDataModel\DbAdmin\MongoDbAdmin::validateCollectionName((string)$param_collection))) {
					$message = 'Empty or Invalid Collection selected';
				} elseif(!in_array((string)$param_collection, (array)$collections_arr)) {
					$message = 'The selected Collection does not exists: `'.$param_collection.'`';
				} else {
					$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::dropCollection((string)$the_collection);
					if((string)$message === 'OK') {
						$this->CookieVarSet((string)$the_cookiename_collection, ''); // reset collection cookie
						$status = 'OK';
						$message = 'Collection DROP was SUCCESSFUL';
						$redirect = (string) $the_base_url.'&action=close-modal';
					} //end if
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'new-add': // Do Add new Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$param_collection = (string) trim((string)$this->RequestVarGet('collection', '', 'string'));
				$frm = $this->RequestVarGet('frm', [], 'array');
				if(!is_array($frm)) {
					$frm = array();
				} //end if
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				$name_new_collection = '';
				$nerr = '';
				$nmsg = '';
				if((string)$param_collection == '@NEW@') {
					$the_collection = (string) $param_collection;
					$collection_selected = true; // fake it to easy logic here
					$name_new_collection = (string) trim((string)$this->RequestVarGet('newcollectionname', '', 'string'));
					$nmsg = 'Collection: `'.$name_new_collection.'` created SUCCESSFUL and ';
					if(((string)$name_new_collection == '') OR (!\SmartModDataModel\DbAdmin\MongoDbAdmin::validateCollectionName((string)$name_new_collection))) {
						$nerr = 'Empty or Invalid Name for the New Collection !';
					} //end if else
					if(in_array((string)$name_new_collection, (array)$collections_arr)) {
						$nerr = 'The Collection already exists !';
					} //end if
				} //end if
				//--
				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					if($nerr) {
						$message = 'ERR: '.$nerr;
					} else {
						$frm = $this->validateInsertFormData((string)$the_collection, (array)$frm);
						if(!is_array($frm)) {
							$message = 'ERR: '.$frm;
						} else {
							$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::insertRecord((string)$the_collection, (array)$frm, (string)$name_new_collection);
							if((string)$message === 'OK') {
								$status = 'OK';
								$message = $nmsg.'Record Insert SUCCESSFUL';
								$redirect = (string) $the_base_url.'&action=close-modal';
							} //end if
						} //end if
					} //end if else
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'edit-record': // Do Modify Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm = $this->RequestVarGet('frm', [], 'array');
				if(!is_array($frm)) {
					$frm = array();
				} //end if
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					$frm = $this->validateEditFormData((string)$the_collection, (string)$id_, (array)$frm);
					if(!is_array($frm)) {
						$message = 'ERR: '.$frm;
					} else {
						$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::modifyRecord((string)$the_collection, (string)$id_, (array)$frm);
						if((string)$message === 'OK') {
							$status = 'OK';
							$message = 'Record Edit SUCCESSFUL';
							$redirect = (string) $the_base_url.'&action=close-modal&rdr='.Smart::escape_url((string)$the_base_url.'&action=edit-form&id_='.Smart::escape_url((string)$id_).'&mode='.Smart::escape_url((string)$mode));
						} //end if
					} //end if
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'delete-record': // Do delete Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$frm = $this->RequestVarGet('frm', [], 'array');
				if(!is_array($frm)) {
					$frm = array();
				} //end if
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				$status = 'INVALID';
				$redirect = '';
				$jsevcode = '';
				//--
				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					$test_chk = $this->validateDeleteFormData((string)$the_collection, (string)$id_, (array)$frm);
					if((string)$test_chk !== 'OK') {
						$message = 'ERR: '.$test_chk;
					} else {
						$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::deleteRecord((string)$the_collection, (string)$id_);
						if((string)$message === 'OK') {
							$status = 'OK';
							$message = 'Record Delete SUCCESSFUL';
							$redirect = (string) $the_base_url.'&action=close-modal';
						} //end if
					} //end if
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message),
						$redirect,
						'',
						'',
						$jsevcode
					)
				);
				//--
				break;

			case 'delete-selected-records': // Do delete ALL Selected Record (OUTPUTS: JSON)
				//--
				$this->PageViewSetCfg('rawpage', true);
				//--
				$title = 'MongoDB '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().' :: '.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().' @ '.$the_collection;
				//--
				$status = 'INVALID';
				$message = ''; // {{{SYNC-MOD-AUTH-VALIDATIONS}}}
				//--

				if((string)trim((string)$the_collection) == '') {
					$message = 'No Collection selected';
				} elseif($collection_selected !== true) {
					$message = 'Collection does not exists: `'.$the_collection.'`';
				} else {
					$qdeljson = (string) trim((string)$this->RequestVarGet('query_', '', 'string'));
					if((string)$qdeljson == '') {
						$message = 'Filtering query was N/A ... Delete many records must have a filter query !';
					} else {
						$qdeljson = Smart::json_decode((string)$qdeljson);
						if((int)Smart::array_size($qdeljson) <= 0) {
							$message = 'Filtering query was empty ... Delete many records must have a filter query !';
						} else {
							$message = (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::deleteRecords((string)$the_collection, (array)$qdeljson);
							if(strpos((string)$message, 'OK:') === 0) {
								$status = 'OK';
								$message = (string) $message.' - SUCCESSFUL';
							} //end if
						} //end if else
					} //end if else
				} //end if else
				//--
				$this->PageViewSetVar(
					'main',
					SmartViewHtmlHelpers::js_ajax_replyto_html_form(
						$status,
						$title,
						Smart::escape_html((string)$message)
					)
				);
				//--
				break;

			default: // display the main UI (OUTPUTS: HTML)

				//--
				if((string)$the_collection == '') {
					$build_info = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getServerBuildInfo();
					if(Smart::array_size($build_info) <= 0) {
						$this->PageViewSetErrorStatus(500, 'MongoDB Server: Cannot Get Build Info ...');
						return;
					} //end if
					$this->PageViewSetVars([
						'title' => 'DB Admin :: MongoDB',
						'main'  => (string) SmartMarkersTemplating::render_file_template(
							$this->ControllerGetParam('module-view-path').'mongodb-buildinfo.mtpl.htm',
							[
								'CLI-VERSION' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getExtVersion(),
								'SRV-VERSION' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getServerVersion(),
								'DATABASE' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
								'HOST' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
								'PORT' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
								'COLLECTION' 			=> (string) '', // this must be empty here !
								'COLLECTIONS' 			=> (array)  $collections_list,
								'BUILD-INFO' 			=> (string) SmartUtils::pretty_print_var($build_info),
								'PAGE-LIST-URL' 		=> (string) $the_base_url,
								'COOKIENAME-COLLECTION' => (string) $the_cookiename_collection,
								'URL-NEW-COLLECTION' 	=> $the_base_url.'&action=new-form'.'&collection='.Smart::escape_url('@NEW@'),
							]
						)
					]);
					return;
				} //end if
				//--

				//--
				$the_qf_mode = (string) strtolower((string)trim((string)$this->RequestVarGet('qf', '', 'string')));
				$is_command = false;
				if((string)$the_qf_mode == 'cmd') {
					$is_command = true;
				} //end if
				//--

				//--
				$ofs = (int) $this->RequestVarGet('ofs', 0, 'integer+');
				//--
				$limit = 10;
				//--
				$qjson = (string) trim((string)$this->RequestVarGet('query_', '', 'string'));
				$query_ = Smart::json_decode((string)$qjson);
				//--
				$query = [];
				if($is_command === true) {
					$id_ = '';
				} //end if
				if((string)$id_ != '') {
					$query = [ '_id' => (string) $id_ ];
					$qjson = (string) Smart::json_encode((array)$query);
					$query_ = [];
				} elseif(Smart::array_size($query_) > 0) {
					$query = (array) $query_;
				} else {
					$query_ = [];
				} //end if else
				//--

				//--
				if($is_command === true) {
					$sorting = [];
				} else {
					$sorting = $this->RequestVarGet('sorting', [], 'array');
					if(Smart::array_type_test($sorting) != 2) {
						$sorting = [];
					} //end if
					if(Smart::array_size($sorting) <= 0) {
						$sorting = [
							'_id' => 'ASC'
						];
					} //end if
				} //end if
				//--

				//--
				if($is_command !== true) {
					$query = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::convertQueryToRealMongoId((array)$query); // {{{SYNC-MONGODB-CONVERT-$OID-TO-OBJECTID}}}
				} //end if
				//--

				//--

				//--
				$collection_metainfo = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbCollectionStats((string)$the_collection);
				//--
				$all_meta_count = Smart::array_get_by_key_path((array)$collection_metainfo, '0.count', '.');
				if(!Smart::is_nscalar($all_meta_count)) {
					$all_meta_count = 0;
				} //end if
				$all_meta_count = (int) $all_meta_count;
				if((int)$all_meta_count < 0) {
					$all_meta_count = 0;
				} //end if
				//--
				$error = []; // init errors
				//--
				/*
				if((int)$all_meta_count <= 0) { // an optional FALLBACK, currently unused to get the total records .. it is more costly !
					try {
						$all_meta_count = (int) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecordsCount((string)$the_collection, []);
					} catch(Exception $e) {
						$error[] = (string) $e->getMessage();
						$all_meta_count = 0;
					} //end try catch
				} //end if
				*/
				//--

				//--
				$collection_indexes = (array) $this->getCollectionIndexes((string)$the_collection, (bool)$collection_selected);
				//--

				//--
				$total_collection_size = Smart::array_get_by_key_path((array)$collection_metainfo, '0.totalSize', '.');
				if(!Smart::is_nscalar($total_collection_size)) {
					$total_collection_size = 0;
				} //end if
				$total_collection_size = (int) $total_collection_size;
				if((int)$total_collection_size < 0) {
					$total_collection_size = 0;
				} //end if
				//--
				$total_collection_pretty_size = (string) SmartUtils::pretty_print_bytes((int)$total_collection_size, 2, ' ', 1024); // for mongodbvalue is 1024 NOT 1000 !
				//--

				//--
				$all_count = (int) $all_meta_count;
				//--
				$is_empty_collection = false;
				if((int)$all_meta_count <= 0) {
					$is_empty_collection = true;
				} //end if
				//--
				$time = 0;
				$count = 0;
				$data = [];
				$records = [];
				//--
				$html_sorting = [];
				$ascdesc = [ 'ASC' => 'ASC', 'DESC' => 'DESC' ];
				//--
				if($is_command === true) {
					//-- this does not depend on $qjson which on empty query is '{}' ; for command it requires a non-empty array
					if((int)Smart::array_size($query) <= 0) {
						$error[] = 'WARNING: No Command supplied ... Running `buildinfo` command as default !...';
						$query = [
							'buildinfo' => 1,
						];
						$query_ = (array) $query;
						$qjson = (string) Smart::json_encode((array)$query);
					} //end if
					$time = microtime(true);
					$mongo = \SmartModDataModel\DbAdmin\MongoDbAdmin::getInstance();
					if(!$mongo) {
						\Smart::log_warning(__METHOD__.'() MongoDB Instance is not available ...');
						$error[] = 'ERROR: MongoDB Instance is N/A';
					} //end if
					try {
						$data = $mongo->command((array)$query);
					} catch(Exception $e) {
						$error[] = (string) $e->getMessage();
						$query = [];
						$data = [];
					} //end try catch
					$time = microtime(true) - $time;
					if(!is_array($data)) {
						$data = [ 'command:result' => (string)$data ];
					} elseif((int)Smart::array_size($data) > 0) {
						$records[] = [
							'_id' 	=> 'MongoDB-Command:'.sha1((string)Smart::json_encode((array)$data)).'-'.sha1((string)microtime(true)),
							'-id' 	=> (string) 'MongoDB Command run at: `'.date('Y-m-d H:i:s').'`',
							'-num' 	=> 1,
							'-json' => (string) Smart::json_encode((array)$data),
						];
						$count = 1;
					} else { // no data provided by command
						$count = 0;
					} //end if else
					$all_count = (int) $count; // fix
					//--
				} else {
					//--
					if((string)$qjson != '') {
						try {
							$count = (int) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecordsCount((string)$the_collection, (array)$query);
						} catch(Exception $e) {
							$error[] = (string) $e->getMessage();
							$query = [];
							$count = 0;
						} //end try catch
					} //end if
					//--
					if(((string)$qjson == '') OR (Smart::array_size($query) > 0) OR (Smart::array_size($error) > 0)) {
						// there is no filter
					} else {
						$all_count = (int) $count; // filtered ... update to reflect the reality
					} //end if else
					//--
					if(Smart::array_size($error) > 0) {
						$data = [];
					} else {
						if((string)$qjson != '') {
							$time = microtime(true);
							try {
								$data = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecordsData((string)$the_collection, (array)$query, (int)$ofs, (int)$limit, (array)$sorting);
							} catch(Exception $e) {
								$error[] = (string) $e->getMessage();
								$query = [];
								$data = [];
							} //end try catch
							$time = microtime(true) - $time;
						} //end if
					} //end if else
					//--
					for($i=0; $i<Smart::array_size($data); $i++) {
						//--
						if(is_array($data[$i]['_id'])) {
							$data[$i]['_id'] = (string) 'ObjectId('.($data[$i]['_id']['$oid'] ?? '!Undefined!').')'; // {{{SYNC-MONGODB-CONVERT-$OID-TO-OBJECTID}}}
						} //end if
						//--
						if((string)$data[$i]['_id'] != '') {
							$tmp_arr = (array) $data[$i];
						//	unset($tmp_arr['_id']); // {{{SYNC-DB-MONGO-ADMIN-UNSET-_ID}}}
							$records[] = [
								'_id' 	=> (string) ($data[$i]['_id'] ?? null),
								'-id' 	=> (string) ($data[$i]['id'] ?? null),
								'-num' 	=> (int) ((int)$i + 1 + (int)$ofs),
								'-json' => (string) Smart::json_encode((array)$tmp_arr)
							];
							$tmp_arr = null;
						} //end if
						//--
					} //end if
					//--
					if((int)Smart::array_size($sorting) > 0) {
						$iterator = 0;
						foreach($sorting as $key => $val) {
							$key = (string) trim((string)$key);
							$val = (string) strtoupper((string)trim((string)$val));
							if(!in_array($val, array_values($ascdesc))) {
								$val = 'ASC';
							} //end if
							if((string)$key != '') {
								$html_sorting[] = [
									'id-field' => (string) $key,
									'html-field' => (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('sort-m'.(int)$iterator, (string)$val, 'form', (array)$ascdesc, 'sort[m'.(int)$iterator.']', '70/0', '', 'no', 'no', '', 'class:filter-direction')
								];
								$iterator++;
							} //end if
						} //end foreach
						$iterator = null;
					} //end if
					//--
				} //end if else
				//--

				//--
				$sort_size = (int) Smart::array_size($html_sorting);
				$sort_max = 7;
				for($i=$sort_size; $i<$sort_max; $i++) {
					$html_sorting[] = [
						'id-field' => '',
						'html-field' => (string) \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers::html_select_list_single('sort-m'.(int)$i, 'ASC', 'form', (array)$ascdesc, 'sort[m'.(int)$i.']', '70/0', '', 'no', 'no', '', 'class:filter-direction')
					];
				} //end for
				//--

				//--
				$arr_url_params = (array) $this->RequestVarsGet();
				$arr_url_ok_params = [];
				foreach($arr_url_params as $key => $val) {
					switch(strtolower((string)trim((string)$key))) {
						case '':
						case 'page':
						case 'ofs':
							// skip
							break;
						default:
							$arr_url_ok_params[(string)$key] = $val;
					} //end if
				} //end if
				$arr_url_params = [];
				$arr_url_ok_params['ofs'] = '{{{offset}}}';
				$navbox_url = (string) Smart::url_add_params((string)$the_base_url, (array)$arr_url_ok_params);
				$arr_url_ok_params = [];
				//--
				$num_pages = (int) ceil((int)$count / (int)$limit);
				if($num_pages <= 0) {
					$num_pages = 1;
				} //end if
				//--

				//--
				$is_query = (bool) ((Smart::array_size($query_) > 0) || ((string)$id_ != ''));
				//--
				$this->PageViewSetVars([
					'title' => 'DB Admin :: MongoDB',
					'main'  => (string) SmartMarkersTemplating::render_file_template(
						$this->ControllerGetParam('module-view-path').'mongodb-list.mtpl.htm',
						[
							'QFORMAT' 				=> (string) $the_qf_mode,
							'QMODE' 				=> (string) $mode, // raw | visual
							'PAGE-URL' 				=> (string) $the_base_url,
							'URL-DROP-COLLECTION' 	=> (string) $the_base_url.'&action=drop-collection-confirm',
							'URL-NEW-RECORD' 		=> (string) $the_base_url.'&action=new-form'.'&mode='.Smart::escape_url((string)$mode),
							'URL-EDIT-RECORD' 		=> (string) $the_base_url.'&action=edit-form'.'&mode='.Smart::escape_url((string)$mode).'&id_=',
							'URL-DELETE-RECORD' 	=> (string) $the_base_url.'&action=delete-confirm'.'&id_=',
							'URL-DELETE-SLRECORDS' 	=> (string) $the_base_url.'&action=delete-selected-records',
							'URL-ADD-INDEX' 		=> (string) $the_base_url.'&action=add-index-form',
							'URL-DROP-INDEX' 		=> (string) $the_base_url.'&action=drop-index-form',
							'HOST' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost(),
							'PORT' 					=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort(),
							'DATABASE' 				=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName(),
							'CLI-VERSION' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getExtVersion(),
							'SRV-VERSION' 			=> (string) \SmartModDataModel\DbAdmin\MongoDbAdmin::getServerVersion(),
							'COOKIENAME-COLLECTION' => (string) $the_cookiename_collection,
							'COLLECTIONS' 			=> (array)  $collections_list,
							'COLLECTION' 			=> (string) $the_collection,
							'COLLINDEXES' 			=> (string) ((Smart::array_size($collection_indexes) > 0) ? Smart::json_encode($collection_indexes) : '{}'),
							'EXECUTION-TIME' 		=> (string) Smart::format_number_dec($time, 10, '.', ''),
							'ERROR' 				=> (string) implode("\n", (array)$error),
							'QUERY' 				=> (string) (((string)$id_ != '')  ? (string)Smart::json_encode(['_id' => (string)$id_], true, true, false) : ((Smart::array_size($query_) > 0) ? Smart::json_encode((array)$query_, true, true, false) : '{'."\n"."\n".'}')),
							'IS-QUERY' 				=> (string) $is_query ? 'yes' : 'no',
							'SORT-MAX' 				=> (int)    $sort_max,
							'LIMIT-PER-PAGE' 		=> (int)    $limit,
							'OFFSET' 				=> (int)    (ceil((int)$ofs / (int)$limit) + 1),
							'PAGES' 				=> (int)    $num_pages,
							'TOTAL-RECORDS' 		=> (int)    $count,
							'TOTAL-ALL-RECORDS' 	=> (int)    $all_count,
							'IS-EMPTY-COLLECTION' 	=> (int)    $is_empty_collection,
							'FILTER-ID_' 			=> (string) $id_,
							'SORTING' 				=> (array)  $html_sorting,
							'COLL-TOT-SIZE-BYTES'	=> (int)    $total_collection_size,
							'COLL-TOT-SIZE-PRETTY'	=> (string) $total_collection_pretty_size,
							'NAV-PAGER-HTML' 		=> (string) (((int)Smart::array_size($records) > 0) ? SmartViewHtmlHelpers::html_navpager(
								(string) $navbox_url,
								(int) $count,
								(int) $limit,
								(int) $ofs,
								false,
								25,
								[
									'show-first' => true,
									'show-last' => true
								]
							) : ''),
							'NUM-RECORDS' 			=> (int)   Smart::array_size($records),
							'RECORDS' 				=> (array) $records
						]
					)
				]);
				//--

		} // end switch
		//--

	} //END FUNCTION


	private function getCollectionIndexes(string $the_collection, bool $collection_selected) {
		//--
		if((string)$the_collection == '') {
			return array();
		} //end if
		//--
		$collection_indexes = [];
		if($collection_selected === true) {
			$tmp_indexes = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getDbCollectionIndexes((string)$the_collection);
			for($i=0; $i<Smart::array_size($tmp_indexes); $i++) {
				if(Smart::array_size($tmp_indexes[$i]) > 0) {
					$tmp_idx_data = (array) $tmp_indexes[$i];
					$tmp_idx_name = (string) trim((string)$tmp_idx_data['name']);
					if((string)$tmp_idx_name == '') {
						$tmp_idx_name = (string) '@'.sha1((string)print_r($tmp_idx_data, 1));
					} //end if
					unset($tmp_idx_data['name']); // name is used as key
					unset($tmp_idx_data['ns']); // unset index namespace (Ex: dbName.CollactionName)
				//	unset($tmp_idx_data['v']); // unset version
					$collection_indexes[(string)$tmp_idx_name] = (array) $tmp_idx_data;
					$tmp_idx_name = '';
					$tmp_idx_data = array();
				} //end if
			} //end for
			$tmp_indexes = array();
		} //end if
		//--
		return (array) $collection_indexes;
		//--
	} //END FUNCTION


	private function validateInsertFormData(string $the_collection, array $frm) {
		//--
		if((string)$the_collection == '') {
			return 'No Collection Selected !';
		} //end if
		//--
		if(Smart::array_size($frm) <= 0) {
			return 'Form Data is Empty !';
		} //end if
		//--
		if((string)$frm['chk'] !== (string)sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection)) {
			return 'Form Checksum is Invalid !';
		} //end if
		//--
		$frm['json'] = (string) trim((string)$frm['json']);
		if((string)$frm['json'] == '') {
			return 'Record JSON is Empty !';
		} //end if
		//--
		$frm['json'] = Smart::json_decode((string)$frm['json']); // mixed
		if(!is_array($frm['json'])) {
			return 'Record JSON Structure is Invalid !';
		} //end if
		//--
		if(Smart::array_size($frm['json']) <= 0) {
			return 'Record JSON Structure is Empty !';
		} //end if
		//--
		return (array) $frm['json'];
		//--
	} //END FUNCTION


	private function validateEditFormData(string $the_collection, string $id_, array $frm) {
		//--
		if((string)$the_collection == '') {
			return 'No Collection Selected !';
		} //end if
		//--
		if((string)trim((string)$id_) == '') {
			return 'Empty Record UID';
		} //end if
		$data = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecord((string)$the_collection, (string)$id_);
		if(Smart::array_size($data) <= 0) {
			return 'Invalid Record UID: `'.$id_.'`';
		} //end if
		//--
		if(Smart::array_size($frm) <= 0) {
			return 'Form Data is Empty !';
		} //end if
		//--
		if((string)$frm['chk'] !== (string)sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection.'#'.$id_)) {
			return 'Form Checksum is Invalid !';
		} //end if
		//--
		$frm['json'] = (string) trim((string)$frm['json']);
		if((string)$frm['json'] == '') {
			return 'Record JSON is Empty !';
		} //end if
		//--
		$frm['json'] = Smart::json_decode((string)$frm['json']); // mixed
		if(!is_array($frm['json'])) {
			return 'Record JSON Structure is Invalid !';
		} //end if
		//--
		if(array_key_exists('_id', (array)$frm['json'])) {
			unset($frm['json']['_id']); // the UID must NOT be edited / changed
		} //end if
		if(Smart::array_size($frm['json']) <= 0) {
			return 'Record JSON Structure is Empty !';
		} //end if
		//--
		return (array) $frm['json'];
		//--
	} //END FUNCTION


	private function validateDeleteFormData(string $the_collection, string $id_, array $frm) {
		//--
		if((string)$the_collection == '') {
			return 'No Collection Selected !';
		} //end if
		//--
		if((string)trim((string)$id_) == '') {
			return 'Empty Record UID';
		} //end if
		$data = (array) \SmartModDataModel\DbAdmin\MongoDbAdmin::getRecord((string)$the_collection, (string)$id_);
		if(Smart::array_size($data) <= 0) {
			return 'Invalid Record UID: `'.$id_.'`';
		} //end if
		//--
		if(Smart::array_size($frm) <= 0) {
			return 'Form Data is Empty !';
		} //end if
		//--
		if((string)$frm['chk'] !== (string)sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection.'#'.$id_)) {
			return 'Form Checksum is Invalid !';
		} //end if
		//--
		return 'OK';
		//--
	} //END FUNCTION


	private function validateIndexAddFormData(string $the_collection, array $frm) {
		//--
		if((string)$the_collection == '') {
			return 'No Collection Selected !';
		} //end if
		//--
		if(Smart::array_size($frm) <= 0) {
			return 'Index Data is Empty !';
		} //end if
		//--
		if((string)$frm['chk'] !== (string)sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection)) {
			return 'Form Checksum is Invalid !';
		} //end if
		//--
		$frm['json'] = (string) trim((string)$frm['json']);
		if((string)$frm['json'] == '') {
			return 'Index JSON is Empty !';
		} //end if
		//--
		$frm['json'] = Smart::json_decode((string)$frm['json']); // mixed
		if(!is_array($frm['json'])) {
			return 'Index JSON Structure is Invalid !';
		} //end if
		if(Smart::array_size($frm['json']) <= 0) {
			return 'Index Data is Empty !';
		} //end if
		//--
		return (array) $frm['json'];
		//--
	} //END FUNCTION


	private function validateIndexDropFormData(string $the_collection, array $frm) {
		//--
		if((string)$the_collection == '') {
			return 'No Collection Selected !';
		} //end if
		//--
		if(Smart::array_size($frm) <= 0) {
			return 'Index Data is Empty !';
		} //end if
		//--
		if((string)$frm['chk'] !== (string)sha1((string)\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbHost().':'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbPort().'/'.\SmartModDataModel\DbAdmin\MongoDbAdmin::getDbName().'@'.$the_collection)) {
			return 'Form Checksum is Invalid !';
		} //end if
		//--
		$frm['drop-index'] = (string) trim((string)$frm['drop-index']);
		if((string)$frm['drop-index'] == '') {
			return 'Empty Drop Index Selected !';
		} //end if
		//--
		return (array) $frm;
		//--
	} //END FUNCTION


} //END CLASS


// end of php code
