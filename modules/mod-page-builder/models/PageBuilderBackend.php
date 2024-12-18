<?php
// Class: \SmartModDataModel\PageBuilder\PageBuilderBackend
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModDataModel\PageBuilder;

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
 * SQLite/PostgreSQL Model for ModPageBuilder/Backend
 * @ignore
 */
final class PageBuilderBackend {

	// ::
	// v.20231119


	private static $db = null;


	private static function dbType() {
		//--
		if((string)\SmartModExtLib\PageBuilder\Utils::getDbType() == 'sqlite') {
			//--
			if(self::$db === null) {
				//--
				$sqlitedbfile = '#db/page-builder.sqlite';
				//--
				if(!\SmartFileSysUtils::checkIfSafePath((string)$sqlitedbfile, true, true)) { // dissalow absolute ; allow protected
					\Smart::raise_error(
						__CLASS__.': SQLite DB PATH is UNSAFE !',
						'PageBuilder ERROR: UNSAFE DB ACCESS (1)'
					);
					return;
				} //end if
				//--
				// MUST NOT CHECK HERE IF SQLITE FILE EXISTS ... IF DO NOT WILL BE CREATED AND INSTANTIATED !
				//--
				self::$db = new \SmartSQliteDb((string)$sqlitedbfile);
				self::$db->open();
				//--
				if(!\SmartFileSystem::is_type_file((string)$sqlitedbfile)) {
					if(self::$db instanceof \SmartSQliteDb) {
						self::$db->close();
					} //end if
					\Smart::raise_error(
						__CLASS__.': SQLite DB File does NOT Exists !',
						'PageBuilder ERROR: DB NOT FOUND (1)'
					);
					return;
				} //end if
				//--
				if((self::$db->check_if_table_exists('page_builder') != 1) OR (self::$db->check_if_table_exists('page_translations') != 1)) {
					$sql = \SmartFileSystem::read('modules/mod-page-builder/models/sql/sqlite/page-builder-schema.sql');
					if(!$sql) {
						if(self::$db instanceof \SmartSQliteDb) {
							self::$db->close();
						} //end if
						\Smart::raise_error(
							__CLASS__.': SQLite Schema SQL File does NOT Exists or is NOT Readable !',
							'PageBuilder ERROR: DB Schema SQL File does NOT Exists or is NOT Readable (1)'
						);
						return;
					} //end if
					self::$db->write_data((string)$sql);
				} //end if
				//--
			} //end if
			//--
			return 'sqlite';
			//--
		} elseif((string)\SmartModExtLib\PageBuilder\Utils::getDbType() == 'pgsql') {
			//--
			if(\Smart::array_size(\Smart::get_from_config('pgsql')) <= 0) {
				\Smart::raise_error(
					__CLASS__.': PostgreSQL DB CONFIG Not Found !',
					'PageBuilder ERROR: DB CONFIG Not Found (2)'
				);
				return;
			} //end if
			if(\SmartPgsqlDb::check_if_schema_exists('smart_runtime') != 1) {
				$sql = \SmartFileSystem::read('_sql/postgresql/init-smart-framework.sql');
				if(!$sql) {
					\Smart::raise_error(
						__CLASS__.': PostgreSQL Init Schema SQL File does NOT Exists or is NOT Readable !',
						'PageBuilder ERROR: DB Init Schema SQL File does NOT Exists or is NOT Readable (2)'
					);
					return;
				} //end if
				\SmartPgsqlDb::write_data((string)$sql);
			} //end if
			if((\SmartPgsqlDb::check_if_schema_exists('web') != 1) OR (\SmartPgsqlDb::check_if_table_exists('page_builder', 'web') != 1) OR (\SmartPgsqlDb::check_if_table_exists('page_translations', 'web') != 1)) {
				$sql = \SmartFileSystem::read('modules/mod-page-builder/models/sql/postgresql/page-builder-schema.sql');
				if(!$sql) {
					\Smart::raise_error(
						__CLASS__.': PostgreSQL Schema SQL File does NOT Exists or is NOT Readable !',
						'PageBuilder ERROR: DB Schema SQL File does NOT Exists or is NOT Readable (2)'
					);
					return;
				} //end if
				\SmartPgsqlDb::write_data((string)$sql);
			} //end if
			//--
			return 'pgsql';
			//--
		} else {
			//--
			\SmartFrameworkRuntime::Raise503Error('PageBuilder is Unavailable'."\n".'DB Type not set in configs: SMART_PAGEBUILDER_DB_TYPE ! ...');
			die('PageBuilderBackend:NO-DB-TYPE');
			//--
		} //end if else
		//--
	} //END FUNCTION


	public static function startTransaction() {
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data('BEGIN');
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data('BEGIN');
		} //end if else
		//--
	} //END FUNCTION


	public static function rollbackTransaction() {
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data('ROLLBACK');
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data('ROLLBACK');
		} //end if else
		//--
	} //END FUNCTION


	public static function commitTransaction() {
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data('COMMIT');
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data('COMMIT');
		} //end if else
		//--
	} //END FUNCTION


	public static function getExprContextUsageCount($expr) {
		//--
		return (int) (self::listCountRecords('code', (string)$expr) + self::listCountRecords('data', (string)$expr));
		//--
	} //END FUNCTION


	public static function getRecordsUniqueControllers($mode, $filter='') {
		//--
		switch((string)$mode) {
			case 'list-all': // list all
			case 'filter': // filter (show all or filtered by ctrl)
				break;
			default:
				return array();
		} //end switch
		//--
		if((string)$mode == 'list-all') {
			//--
			if((string)self::dbType() == 'pgsql') {
				return (array) \SmartPgsqlDb::read_adata(
					'SELECT "ctrl", COUNT(1) AS "objects" FROM "web"."page_builder" WHERE ("ref" = $1) GROUP BY "ctrl" ORDER BY "ctrl" ASC',
					[
						(string) '[]'
					]
				);
			} elseif((string)self::dbType() == 'sqlite') {
				return (array) self::$db->read_adata(
					'SELECT `ctrl`, COUNT(1) AS `objects` FROM `page_builder` WHERE (`ref` = ?) GROUP BY `ctrl` ORDER BY `ctrl` ASC',
					[
						(string) '[]'
					]
				);
			} else {
				return array();
			} //end if else
			//--
		} else { // filter
			//--
			$filter = (string) \trim((string)$filter);
			//--
			if((string)$filter != '') {
				if((string)self::dbType() == 'pgsql') {
					return (array) \SmartPgsqlDb::read_data(
						'SELECT "ctrl" FROM "web"."page_builder" WHERE (("ref" = $1) AND ("ctrl" = $2)) GROUP BY "ctrl" ORDER BY "ctrl" ASC LIMIT 1 OFFSET 0',
						[
							(string) '[]',
							(string) $filter
						]
					);
				} elseif((string)self::dbType() == 'sqlite') {
					return (array) self::$db->read_data(
						'SELECT `ctrl` FROM `page_builder` WHERE ((`ref` = ?) AND (`ctrl` = ?)) GROUP BY `ctrl` ORDER BY `ctrl` ASC LIMIT 1 OFFSET 0',
						[
							(string) '[]',
							(string) $filter
						]
					);
				} else {
					return array();
				} //end if else
			} else {
				if((string)self::dbType() == 'pgsql') {
					return (array) \SmartPgsqlDb::read_data(
						'SELECT "ctrl" FROM "web"."page_builder" WHERE ("ref" = $1) GROUP BY "ctrl" ORDER BY "ctrl" ASC',
						[
							(string) '[]'
						]
					);
				} elseif((string)self::dbType() == 'sqlite') {
					return (array) self::$db->read_data(
						'SELECT `ctrl` FROM `page_builder` WHERE (`ref` = ?) GROUP BY `ctrl` ORDER BY `ctrl` ASC',
						[
							(string) '[]'
						]
					);
				} else {
					return array();
				} //end if else
			} //end if else
			//--
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordsByCtrl($y_ctrl) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_adata(
				'SELECT "id", "active", "auth", "special", "name", "mode", "translations", "counter" FROM "web"."page_builder" WHERE (("ctrl" = $1) AND ("ref" = $2)) ORDER BY "special" ASC, "name" ASC, "id" ASC',
				[
					(string) $y_ctrl,
					(string) '[]'
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_adata(
				'SELECT `id`, `active`, `auth`, `special`, `name`, `mode`, `translations`, `counter` FROM `page_builder` WHERE ((`ctrl` = ?) AND (`ref` = ?)) ORDER BY `special` ASC, `name` ASC, `id` ASC',
				[
					(string) $y_ctrl,
					(string) '[]'
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordsByRef($y_ref) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_adata(
				'SELECT "id", "active", "auth", "special", "name", "mode", "translations", "counter" FROM "web"."page_builder" WHERE ("ref" ? $1) ORDER BY "special" ASC, "name" ASC, "id" ASC',
				[
					(string) $y_ref
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_adata(
				'SELECT `id`, `active`, `auth`, `special`, `name`, `mode`, `translations`, `counter` FROM `page_builder` WHERE (smart_json_arr_contains(`ref`, ?) = 1) ORDER BY `special` ASC, `name` ASC, `id` ASC',
				[
					(string) $y_ref
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT * FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT * FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordIdsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "name", "ref" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `name`, `ref` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordDetailsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "ref", "special", "name", "mode" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `ref`, `special`, `name`, `mode` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordCodeById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "ref", "special", "mode", "data", "code", "translations" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `ref`, `special`, `mode`, `data`, `code`, `translations` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getTranslationCodeById($y_id, $y_lang) {
		//--
		if((string)self::dbType() == 'pgsql') {
			$tarr = (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "lang", "code" FROM "web"."page_translations" WHERE (("id" = $1) AND ("lang" = $2)) LIMIT 1 OFFSET 0',
				[
					(string) $y_id,
					(string) $y_lang
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$tarr = (array) self::$db->read_asdata(
				'SELECT `id`, `lang`, `code` FROM `page_translations` WHERE ((`id` = ?) AND (`lang` = ?)) LIMIT 1 OFFSET 0',
				[
					(string) $y_id,
					(string) $y_lang
				]
			);
		} else {
			return array();
		} //end if else
		//--
		$arr = (array) self::getRecordCodeById($y_id);
		//--
		if(\Smart::array_size($arr) > 0) {
			$arr['code'] = (string) (isset($tarr['code']) ? $tarr['code'] : '');
			$arr['lang'] = (string) (isset($tarr['lang']) ? $tarr['lang'] : '');
		} //end if
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function getRecordsTranslationsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_data(
				'SELECT "lang" FROM "web"."page_translations" WHERE ("id" = $1)',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_data(
				'SELECT `lang` FROM `page_translations` WHERE (`id` = ?)',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function resetRecordTranslationsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data(
				'DELETE FROM "web"."page_translations" WHERE ("id" = $1)',
				[
					(string) $y_id
				]
			);
			$out = true;
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data(
				'DELETE FROM `page_translations` WHERE (`id` = ?)',
				[
					(string) $y_id
				]
			);
			$out = true;
		} else {
			$out = false;
		} //end if else
		//--
		return (bool) $out;
		//--
	} //END FUNCTION


	public static function getRecordDataById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "ref", "special", "mode", "data", "code" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `ref`, `special`, `mode`, `data`, `code` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordPropsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "ref", "special", "mode", "name", "ctrl", "active", "auth", "translations", "layout", OCTET_LENGTH("code") AS "len_code", OCTET_LENGTH("data") AS "len_data", "checksum", md5("id" || "data" || "code") AS "calc_checksum", "tags" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `ref`, `special`, `mode`, `name`, `ctrl`, `active`, `auth`, `translations`, `layout`, smart_strlen(`code`) AS `len_code`, smart_strlen(`data`) AS `len_data`, `checksum`, smart_md5(`id` || `data` || `code`) AS `calc_checksum`, `tags` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function getRecordInfById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_asdata(
				'SELECT "id", "ref", "special", "mode", "published", "admin", "modified", "checksum", "counter", "name" FROM "web"."page_builder" WHERE ("id" = $1) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_asdata(
				'SELECT `id`, `ref`, `special`, `mode`, `published`, `admin`, `modified`, `checksum`, `counter`, `name` FROM `page_builder` WHERE (`id` = ?) LIMIT 1 OFFSET 0',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function insertRecord($y_arr_data, $y_use_external_transaction=false) {
		//--
		$y_arr_data = (array) $y_arr_data;
		//--
		$y_arr_data['id'] = (string) \trim((string)$y_arr_data['id']);
		//-- {{{SYNC-PAGEBUILDER-SLUG-LEN-CONSTRAINTS}}}
		$minlen = 1; // {{{SYNC-PAGEBUILDER-SLUG-LEN-CONSTRAINTS}}} ; ex: 'go' or 'c' are valid slugs
		if(\strpos((string)$y_arr_data['id'], '#') === 0) { // if segment, it was prefixed with a # (min length must be +1)
			$minlen = 2;
		} //end if
		if(\strlen((string)$y_arr_data['id']) < (int)$minlen) {
			return -1; // data must contain the ID and must be non-empty, at least 1 char (constraint)
		} //end if
		if(\strlen((string)$y_arr_data['id']) > 63) {
			return -2; // max 63 chars (constraint, in case it is used with wildcard subdomains)
		} //end if
		//--
		if($y_use_external_transaction !== true) {
			self::startTransaction();
		} //end if
		//--
		if((string)self::dbType() == 'pgsql') {
			$wr = (array) \SmartPgsqlDb::write_data(
				'INSERT INTO "web"."page_builder" '.
				\SmartPgsqlDb::prepare_statement((array)$y_arr_data, 'insert')
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$wr = (array) self::$db->write_data(
				'INSERT INTO `page_builder` '.
				self::$db->prepare_statement((array)$y_arr_data, 'insert')
			);
		} else {
			$wr = array();
		} //end if else
		//--
		if($wr[1] != 1) {
			if($y_use_external_transaction !== true) {
				self::rollbackTransaction();
			} //end if
			return (int) $wr[1]; // insert failed
		} //end if
		//--
		$wr = (array) self::updateChecksumRecordById((string)$y_arr_data['id']);
		if($wr[1] != 1) {
			if($y_use_external_transaction !== true) {
				self::rollbackTransaction();
			} //end if
			return -2; // checksum failed
		} //end if
		//--
		if($y_use_external_transaction !== true) {
			self::commitTransaction();
		} //end if
		//--
		return 1; // all ok
		//--
	} //END FUNCTION


	public static function clearRecordRefsById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."page_builder" SET "ref" = smart_jsonb_arr_delete("ref", $1)',
				[
					(string) $y_id // ref del: string
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->write_data(
				'UPDATE `page_builder` SET `ref` = smart_json_arr_delete(`ref`, ?)',
				[
					(string) $y_id // ref del: string
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function updateRecordById($y_id, $y_arr_data, $y_upd_checksum) {
		//--
		$y_id = (string) \trim((string)$y_id);
		$y_arr_data = (array) $y_arr_data;
		$y_upd_checksum = (bool) $y_upd_checksum;
		//--
		if((string)$y_id == '') {
			return -1; // empty ID
		} //end if
		if(\Smart::array_size($y_arr_data) <= 0) {
			return -2; // empty data
		} //end if
		if(\array_key_exists('id', $y_arr_data)) {
			return -3; // data must not contain the ID which cannot be changed on edit
		} //end if
		//--
		self::clearObjectFromPCache((string)$y_id);
		//--
		self::startTransaction();
		//--
		$rd = (array) self::getRecordIdsById((string)$y_id);
		if((string)$rd['id'] == '') {
			self::rollbackTransaction();
			return -4;
		} //end if
		//--
		if(\array_key_exists('tags', $y_arr_data)) {
			if(\Smart::array_type_test($y_arr_data['tags']) != '1') {
				$y_arr_data['tags'] = [];
			} //end if
			$y_arr_data['tags'] = (string) \Smart::json_encode($y_arr_data['tags']);
		} //end if
		//--
		$tmp_refs = \Smart::json_decode((string)$rd['ref']);
		if(\Smart::array_size($tmp_refs) > 0) {
			$y_arr_data['ctrl'] = ''; // fix: do not allow to set controller on sub-segments
		} //end if
		$tmp_refs = null;
		//--
		if((string)self::dbType() == 'pgsql') {
			$wr = (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."page_builder" '.
				\SmartPgsqlDb::prepare_statement((array)$y_arr_data, 'update').
				' WHERE ("id" = \''.\SmartPgsqlDb::escape_str((string)$y_id).'\')'
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$wr = (array) self::$db->write_data(
				'UPDATE `page_builder` '.
				self::$db->prepare_statement((array)$y_arr_data, 'update').
				' WHERE (`id` = \''.self::$db->escape_str((string)$y_id).'\')'
			);
		} else {
			$wr = array();
		} //end if else
		//--
		if($wr[1] != 1) {
			self::rollbackTransaction();
			return (int) $wr[1]; // update failed
		} //end if
		//--
		if($y_upd_checksum === true) {
			$wr = (array) self::updateChecksumRecordById((string)$y_id);
			if($wr[1] != 1) {
				self::rollbackTransaction();
				return -5; // checksum failed
			} //end if
		} //end if
		//--
		if(\array_key_exists('data', $y_arr_data)) { // DO THIS JUST JUST ON UPDATES THAT CONTAIN THE 'data' KEY
			//-- delete ref from all objects
			self::clearRecordRefsById($y_id);
			//-- rebuild reference from YAML (if new YAML segments entered will be created automatically)
			$tmp_yaml = (string) \trim((string)\base64_decode((string)$y_arr_data['data']));
			if((string)$tmp_yaml != '') {
				$tmp_ymp = new \SmartYamlConverter(false); // do not log YAML parse errors
				$tmp_yaml = (array) $tmp_ymp->parse((string)$tmp_yaml);
				$tmp_yerr = (string) $tmp_ymp->getError();
				if($tmp_yerr) {
					self::rollbackTransaction();
					return -6; // yaml have errors, prevent parse it
				} //end if
				$tmp_ymp = null;
				if(\Smart::array_size($tmp_yaml) > 0) {
					if(isset($tmp_yaml['RENDER']) AND (\Smart::array_size($tmp_yaml['RENDER']) > 0)) {
						$test_create_sub_segments = (int) self::updateCreateChilds((string)$y_id, (string)$rd['name'], (array)$y_arr_data, (array)$tmp_yaml['RENDER']);
						if((int)$test_create_sub_segments !== 1) {
							self::rollbackTransaction();
							return (int) $test_create_sub_segments;
						} //end if
						foreach($tmp_yaml['RENDER'] as $key => $val) {
							$key = (string) \trim((string)$key);
							if((string)$key != '') {
								if(\Smart::array_size($val) > 0) {
									foreach($val as $k => $v) {
										if(((string)\trim((string)$k) != '') AND (\Smart::array_size($val[(string)$k]) > 0) AND (\Smart::array_size($v) > 0) AND isset($v['type']) AND \Smart::is_nscalar($v['type']) AND ((string)$v['type'] == 'segment')) {
											if(isset($v['render']) AND (\Smart::array_size($v['render']) > 0)) {
												$test_create_sub_segments = (int) self::updateCreateChilds((string)$y_id, (string)$rd['name'].': ['.$key.']', (array)$y_arr_data, (array)$v['render']);
												if((int)$test_create_sub_segments !== 1) {
													self::rollbackTransaction();
													return (int) $test_create_sub_segments;
												} //end if
											} //end if
										} //end if
									} //end foreach
								} //end if
							} //end if
						} //end foreach
					} //end if
				} //end if
			} //end if
			//--
		} //end if
		//--
		self::commitTransaction();
		//--
		return 1; // all ok
		//--
	} //END FUNCTION


	public static function updateTranslationById($y_id, $y_lang, $y_arr_data) {
		//--
		$y_id = (string) \trim((string)$y_id);
		$y_lang = (string) \trim((string)$y_lang);
		$y_arr_data = (array) $y_arr_data;
		//--
		if((string)$y_id == '') {
			return -1; // empty ID
		} //end if
		if(\Smart::array_size($y_arr_data) <= 0) {
			return -2; // empty data
		} //end if
		if(((string)$y_lang == '') OR (\strlen((string)$y_lang) != 2) OR (\SmartTextTranslations::validateLanguage((string)$y_lang) !== true)) {
			return -3; // invalid language
		} //end if
		if(\array_key_exists('id', $y_arr_data)) {
			return -4; // data must not contain the ID which cannot be changed on edit
		} //end if
		//--
		$y_arr_data['id'] = (string) $y_id;
		$y_arr_data['lang'] = (string) $y_lang;
		//--
		self::startTransaction();
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data(
				'DELETE FROM "web"."page_translations" WHERE (("id" = $1) AND ("lang" = $2))',
				[
					(string) $y_id,
					(string) $y_lang
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data(
				'DELETE FROM `page_translations` WHERE ((`id` = ?) AND (`lang` = ?))',
				[
					(string) $y_id,
					(string) $y_lang
				]
			);
		} //end if else
		//--
		if((string)\trim((string)$y_arr_data['code']) != '') { // avoid to insert empty translation
			//--
			if((string)self::dbType() == 'pgsql') {
				$wr = (array) \SmartPgsqlDb::write_data(
					'INSERT INTO "web"."page_translations" '.
					\SmartPgsqlDb::prepare_statement((array)$y_arr_data, 'insert')
				);
			} elseif((string)self::dbType() == 'sqlite') {
				$wr = (array) self::$db->write_data(
					'INSERT INTO `page_translations` '.
					self::$db->prepare_statement((array)$y_arr_data, 'insert')
				);
			} else {
				$wr = array();
			} //end if else
			//--
			if($wr[1] != 1) {
				self::rollbackTransaction();
				return (int) $wr[1]; // insert failed
			} //end if
			//--
		} //end if
		//--
		self::commitTransaction();
		//--
		return 1; // all ok
		//--
	} //END FUNCTION


	public static function deleteRecordById($y_id) {
		//--
		$y_id = (string) \trim((string)$y_id);
		//--
		if((string)$y_id == '') {
			return -1; // empty ID
		} //end if
		//--
		self::clearObjectFromPCache((string)$y_id);
		//--
		self::startTransaction();
		//--
		$chk_ref = (array) self::getRecordDetailsById((string)$y_id);
		$is_related = false;
		if(\Smart::array_size($chk_ref) > 0) {
			for($i=0; $i<\Smart::array_size($chk_ref); $i++) {
				if($is_related === true) {
					break;
				} //end if
				$tmp_arr_refs = \Smart::json_decode((string)$chk_ref['ref']);
				if(\Smart::array_size($tmp_arr_refs) > 0) {
					if(\Smart::array_type_test($tmp_arr_refs) == 1) { // non-associative
						for($j=0; $j<\Smart::array_size($tmp_arr_refs); $j++) {
							$tmp_arr_refs[$j] = (string) \trim((string)$tmp_arr_refs[$j]);
							if((string)$tmp_arr_refs[$j] != '') {
								$tmp_arr_chk = (array) self::getRecordDetailsById((string)$tmp_arr_refs[$j]);
								if(\Smart::array_size($tmp_arr_chk) > 0) {
									$is_related = true;
									break;
								} //end if
							} //end if
						} //end for
					} //end if
				} //end if
			} //end for
			if($is_related === true) {
				self::rollbackTransaction();
				return -2; // have refs that exist
			} //end if
		} //end if
		//--
		if((string)self::dbType() == 'pgsql') {
			$wr = (array) \SmartPgsqlDb::write_data(
				'DELETE FROM "web"."page_builder" WHERE ("id" = $1)',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$wr = (array) self::$db->write_data(
				'DELETE FROM `page_builder` WHERE (`id` = ?)',
				[
					(string) $y_id
				]
			);
		} else {
			$wr = array();
		} //end if else
		//--
		self::clearRecordRefsById($y_id);
		//--
		if((string)self::dbType() == 'pgsql') {
			\SmartPgsqlDb::write_data(
				'DELETE FROM "web"."page_translations" WHERE ("id" = $1)',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data(
				'DELETE FROM `page_translations` WHERE (`id` = ?)',
				[
					(string) $y_id
				]
			);
		} //end if else
		//--
		self::commitTransaction();
		//--
		return (int) $wr[1];
		//--
	} //END FUNCTION


	public static function resetCounterOnAllRecords() {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."page_builder" SET "counter" = 0'
			);
		} elseif((string)self::dbType() == 'sqlite') {
			self::$db->write_data('VACUUM');
			return (array) self::$db->write_data(
				'UPDATE `page_builder` SET `counter` = 0'
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function listGetRecords($y_xsrc, $y_src, $y_limit, $y_ofs, $y_xsort, $y_sort) {
		//--
		// {{{SYNC-PAGE-BUILDER-DO-NOT-TRIM-SRC}}} do not trim here: $y_src ; will be trimmed later if needed
		//--
		$y_limit = \Smart::format_number_int($y_limit, '+');
		if($y_limit < 1) {
			$y_limit = 1;
		} //end if else
		//--
		$y_ofs = \Smart::format_number_int($y_ofs, '+');
		if($y_ofs > 0) {
			$y_ofs = (int) (\floor($y_ofs / $y_limit) * $y_limit); // fix offset to be multiple of limit
		} //end if
		//--
		switch((string)\strtoupper((string)$y_xsort)){
			case 'ASC':
				$xsort = 'ASC';
				break;
			default:
				$xsort = 'DESC';
		} //end switch
		//--
		switch((string)\strtolower((string)$y_sort)) {
			case 'id':
			case 'ref':
			case 'name':
			case 'ctrl':
			case 'layout':
			case 'tags':
			case 'modified':
			case 'counter':
			case 'translations':
				if((string)self::dbType() == 'pgsql') {
					$sort = 'ORDER BY a.'.\SmartPgsqlDb::escape_identifier((string)$y_sort).' '.$xsort;
				} elseif((string)self::dbType() == 'sqlite') {
					$sort = 'ORDER BY a.`'.$y_sort.'` '.$xsort;
				} //end if else
				break;
			case 'special':
			case 'active':
			case 'auth':
				if((string)self::dbType() == 'pgsql') {
					$sort = 'ORDER BY a.'.\SmartPgsqlDb::escape_identifier((string)$y_sort).' '.$xsort.', a."id" '.$xsort;
				} elseif((string)self::dbType() == 'sqlite') {
					$sort = 'ORDER BY a.`'.$y_sort.'` '.$xsort.', a.`id` '.$xsort;
				} //end if else
				break;
			case 'mode':
				if((string)self::dbType() == 'pgsql') {
					$sort = 'ORDER BY a."mode" '.$xsort.', a."id" DESC';
				} elseif((string)self::dbType() == 'sqlite') {
					$sort = 'ORDER BY a.`mode` '.$xsort.', a.`id` DESC';
				} //end if else
				break;
			case '@data':
				if((string)self::dbType() == 'pgsql') {
					$sort = 'ORDER BY (char_length(a."data") + char_length(a."code")) '.$xsort;
				} elseif((string)self::dbType() == 'sqlite') {
					$sort = 'ORDER BY (smart_charlen(a.`data`) + smart_charlen(a.`code`)) '.$xsort;
				} //end if else
				break;
			case 'published':
			default:
				if((string)self::dbType() == 'pgsql') {
					$sort = 'ORDER BY a."published" DESC';
				} elseif((string)self::dbType() == 'sqlite') {
					$sort = 'ORDER BY a.`published` DESC';
				} //end if else
		} //end switch
		//--
		$where = (string) self::buildListWhereCondition($y_xsrc, $y_src);
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::read_adata(
				'SELECT a."id", a."name", a."mode", a."ref", a."ctrl", a."tags", a."layout", a."active", a."auth", a."special", a."modified", a."counter", a."translations", (char_length(a."data") + char_length(a."code")) AS "total_size" FROM "web"."page_builder" a '.$where.' '.$sort.' LIMIT '.(int)$y_limit.' OFFSET '.(int)$y_ofs
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->read_adata(
				'SELECT a.`id`, a.`name`, a.`mode`, a.`ref`, a.`ctrl`, a.`tags`, a.`layout`, a.`active`, a.`auth`, a.`special`, a.`modified`, a.`counter`, a.`translations`, (smart_charlen(a.`data`) + smart_charlen(a.`code`)) AS `total_size` FROM `page_builder` a '.$where.' '.$sort.' LIMIT '.(int)$y_limit.' OFFSET '.(int)$y_ofs
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	public static function listCountRecords($y_xsrc, $y_src) {
		//--
		// {{{SYNC-PAGE-BUILDER-DO-NOT-TRIM-SRC}}} do not trim here: $y_src ; will be trimmed later if needed
		//--
		$where = (string) self::buildListWhereCondition($y_xsrc, $y_src);
		//--
		if((string)self::dbType() == 'pgsql') {
			return (int) \SmartPgsqlDb::count_data(
				'SELECT COUNT(1) FROM "web"."page_builder" a '.$where
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (int) self::$db->count_data(
				'SELECT COUNT(1) FROM `page_builder` a '.$where
			);
		} else {
			return 0;
		} //end if else
		//--
	} //END FUNCTION


	//===== PRIVATES


	private static function clearObjectFromPCache($y_id) {
		//--
		$y_id = (string) \trim((string)$y_id);
		if((string)$y_id == '') {
			return false;
		} //end if
		//--
		if(\SmartPersistentCache::isActive() !== true) {
			return true;
		} //end if
		//-- {{{SYNC-PAGEBUILDER-PCACHE-ID}}}
		$the_pcache_key = (string) $y_id.'@'.\SmartTextTranslations::getLanguage();
		//--
		return (bool) \SmartPersistentCache::unsetKey(
			(string) 'smart-pg-builder',
			(string) \SmartPersistentCache::safeKey((string)$the_pcache_key)
		);
		//--
	} //END FUNCTION


	private static function buildListWhereCondition($y_xsrc, $y_src) {
		//--
		// {{{SYNC-PAGE-BUILDER-DO-NOT-TRIM-SRC}}} do not trim here: $y_src ; will be trimmed down later if needed
		//--
		$where = '';
		if((string)\trim((string)$y_src) != '') {
			switch((string)$y_xsrc) {
				case 'id':
					$y_src = (string) \trim((string)$y_src);
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE (a."id" LIKE \''.\SmartPgsqlDb::escape_str((string)$y_src).'\')';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE (a.`id` LIKE \''.self::$db->escape_str((string)$y_src).'\')';
					} //end if else
					break;
				case 'id-ref':
					$y_src = (string) \trim((string)$y_src);
					if((string)$y_src == '[]') { // empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."ref" = \'[]\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`ref` = \'[]\')';
						} //end if else
					} elseif((string)$y_src == '![]') { // non empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."ref" != \'[]\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`ref` != \'[]\')';
						} //end if else
					} else {
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE ((a."id" = \''.\SmartPgsqlDb::escape_str((string)$y_src).'\') OR (a."ref" ? \''.\SmartPgsqlDb::escape_str((string)$y_src).'\'))';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE ((a.`id` = \''.self::$db->escape_str((string)$y_src).'\') OR (smart_json_arr_contains(a.`ref`, \''.self::$db->escape_str((string)$y_src).'\') = 1))';
						} //end if else
					} //end if else
					break;
				case 'name':
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE (a."name" ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\')';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE (a.`name` LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\')';
					} //end if else
					break;
				case 'ctrl':
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE (a."ctrl" ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\')';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE (a.`ctrl` LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\')';
					} //end if else
					break;
				case 'template':
					$y_src = (string) \trim((string)$y_src);
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE ((a."layout" ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\') AND (SUBSTR("id",1,1) != \'#\'))';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE ((a.`layout` LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\') AND (substr(`id`,1,1) != \'#\'))';
					} //end if else
					break;
				case 'area':
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE ((a."layout" ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\') AND (SUBSTR("id",1,1) = \'#\'))';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE ((a.`layout` LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\') AND (substr(`id`,1,1) = \'#\'))';
					} //end if else
					break;
				case 'code':
					if((string)$y_src == '[]') { // empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."code" = \'\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`code` = \'\')';
						} //end if else
					} elseif((string)$y_src == '![]') { // non empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."code" != \'\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`code` != \'\')';
						} //end if else
					} else {
						if(\strpos((string)$y_src, '</>') === 0) { // strip tags
							$y_src = (string) \trim((string)\substr((string)$y_src, 3));
							if((string)self::dbType() == 'pgsql') {
								$where = 'WHERE (smart_str_striptags(convert_from(decode(a."code", \'base64\'), \'UTF8\')) ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\')';
							} elseif((string)self::dbType() == 'sqlite') {
								$where = 'WHERE (smart_strip_tags(smart_base64_decode(a.`code`)) LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\')';
							} //end if else
						} else { // default search
							if((string)self::dbType() == 'pgsql') {
								$where = 'WHERE (convert_from(decode(a."code", \'base64\'), \'UTF8\') ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\')';
							} elseif((string)self::dbType() == 'sqlite') {
								$where = 'WHERE (smart_base64_decode(a.`code`) LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\')';
							} //end if else
						} //end if else
					} //end if
					break;
				case 'data':
					if((string)$y_src == '[]') { // empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."data" = \'\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`data` = \'\')';
						} //end if else
					} elseif((string)$y_src == '![]') { // non empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."data" != \'\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`data` != \'\')';
						} //end if else
					} else {
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (convert_from(decode(a."data", \'base64\'), \'UTF8\') ILIKE \'%'.\SmartPgsqlDb::escape_str((string)$y_src, 'likes').'%\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (smart_base64_decode(a.`data`) LIKE \'%'.self::$db->escape_str((string)$y_src, 'likes').'%\' ESCAPE \''.self::$db->likes_escaper().'\')';
						} //end if else
					} //end if
					break;
				case 'tags':
					$y_src = (string) \trim((string)$y_src);
					if((string)$y_src == '[]') { // empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."tags" = \'[]\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`tags` = \'[]\')';
						} //end if else
					} elseif((string)$y_src == '![]') { // non empty
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."tags" != \'[]\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`tags` != \'[]\')';
						} //end if else
					} else {
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."tags" ? \''.\SmartPgsqlDb::escape_str((string)$y_src).'\')';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (smart_json_arr_contains(a.`tags`, \''.self::$db->escape_str((string)$y_src).'\') = 1)';
						} //end if else
					} //end if else
					break;
				case 'translations':
					$y_src = (string) \trim((string)$y_src);
					$is_positive = false;
					if(\strpos((string)$y_src, '!') === 0) { // negation search: !ro
						$y_src = (string) \ltrim((string)$y_src, '!');
						$is_negative = true;
					} else { // positive search: ro
						$is_negative = false;
						if(\strpos((string)$y_src, '"') === 0) {
							$is_positive = true;
						} //end if
					} //end if else
					if((\strlen((string)$y_src) == 2) AND (\preg_match('/^[a-z]+$/', (string)$y_src))) {
						$arr_raw_langs = (array) \SmartTextTranslations::getListOfLanguages();
						$flang = '';
						foreach($arr_raw_langs as $key => $val) {
							$flang = (string) $key;
							break;
						} //end foreach
						if((string)$flang == (string)$y_src) { // default language
							if($is_negative) {
								if((string)self::dbType() == 'pgsql') {
									$where = 'WHERE FALSE';
								} elseif((string)self::dbType() == 'sqlite') {
									$where = 'WHERE 0';
								} //end if else
							} //end if
						} else {
							if($is_negative) {
								$is_negative = ' ';
							} else {
								$is_negative = ' NOT ';
							} //end if else
							if((string)self::dbType() == 'pgsql') {
								$where = 'LEFT OUTER JOIN "web"."page_translations" b ON a."id" = b."id" AND a."translations" = 1 AND b."lang" = \''.\SmartPgsqlDb::escape_str((string)$y_src).'\' WHERE ((a."translations" = 1) AND (b."lang" IS'.$is_negative.'NULL))';
							} elseif((string)self::dbType() == 'sqlite') {
								$where = 'LEFT OUTER JOIN `page_translations` b ON a.`id` = b.`id` AND a.`translations` = 1 AND b.`lang` = \''.self::$db->escape_str((string)$y_src).'\' WHERE ((a.`translations` = 1) AND (b.`lang` IS'.$is_negative.'NULL))';
							} //end if else
						} //end if else
					} elseif($is_positive) {
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."translations" = 1)';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`translations` = 1)';
						} //end if else
					} elseif($is_negative) {
						if((string)self::dbType() == 'pgsql') {
							$where = 'WHERE (a."translations" != 1)';
						} elseif((string)self::dbType() == 'sqlite') {
							$where = 'WHERE (a.`translations` != 1)';
						} //end if else
					} //end if
					break;
				default: // invalid search
					if((string)self::dbType() == 'pgsql') {
						$where = 'WHERE FALSE';
					} elseif((string)self::dbType() == 'sqlite') {
						$where = 'WHERE 0';
					} //end if else
			} // end switch
		} //end if
		//--
		return (string) $where;
		//--
	} //END FUNCTION


	private static function updateChecksumRecordById($y_id) {
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."page_builder" SET "checksum" = md5("id" || "data" || "code") WHERE ("id" = $1)',
				[
					(string) $y_id
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->write_data(
				'UPDATE `page_builder` SET `checksum` = smart_md5(`id` || `data` || `code`) WHERE (`id` = ?)',
				[
					(string) $y_id
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	private static function updateCreateChilds(string $y_id, string $y_name, array $y_arr_data, array $y_render_yaml) {
		//--
		if(\Smart::array_size($y_render_yaml) <= 0) {
			return 1; // OK
		} //end if
		//--
		foreach($y_render_yaml as $key => $val) {
			$key = (string) \trim((string)$key);
			if((string)$key != '') {
				if(\Smart::array_size($val) > 0) {
					foreach($val as $k => $v) {
						if(((string)\trim((string)$k) != '') AND (\Smart::array_size($val[(string)$k]) > 0) AND (\Smart::array_size($v) > 0) AND isset($v['type']) AND \Smart::is_nscalar($v['type'])) {
							if((string)$v['type'] == 'plugin') {
								$v['id'] = (string) \trim((string)($v['id'] ?? ''));
								if((string)$v['id'] != '') {
									if(isset($v['config']) AND (\Smart::is_nscalar($v['config']))) { // test for config segment
										$v['config'] = (string) \trim((string)$v['config']);
										if((\strlen((string)$v['config']) >= 2) AND (\strlen((string)$v['config']) <= 63)) {
											if((string)$v['config'] != '') {
												$v['config'] = (string) '#'.$v['config']; // ensure is segment
												if((\strlen((string)$v['config']) >= 2) AND (\strlen((string)$v['config']) <= 63)) { // db id constraint
													$test_exists = (array) self::getRecordIdsById((string)$v['config']);
													$tmp_arr_refs = [ (string)$y_id ];
													if((int)\Smart::array_size($test_exists) <= 0) { // settings segment does not exists
														$tmp_new_arr = [
															'id' 		=> (string) $v['config'],
															'ref' 		=> (string) \Smart::json_encode((array)$tmp_arr_refs),
															'name' 		=> (string) \SmartUnicode::sub_str($y_name.': Settings ['.$key.']', 0, 255),
															'mode' 		=> 'settings', // default to settings segment
															'admin' 	=> (string) $y_arr_data['admin'],
															'modified' 	=> (string) $y_arr_data['modified'],
															'ctrl' 		=> (string) '#settings',
														];
														$wr = (int) self::insertRecord((array)$tmp_new_arr, true); // insert with external transaction
														if($wr != 1) {
															return -18; // insert sub-segment failed
														} //end if
													} else {
														$wr = (array) self::updateRecordRefsById(
															(string) $v['config'],
															(array)  $tmp_arr_refs // array of IDs
														);
														if($wr[1] != 1) {
															return -17; // update sub-segment failed
														} //end if
													} //end if else
												} //end if
											} //end if
										} //end if
									} //end if
								} //end if
							} elseif((string)$v['type'] == 'segment') {
								$v['id'] = (string) \trim((string)($v['id'] ?? ''));
								if((\strlen((string)$v['id']) >= 2) AND (\strlen((string)$v['id']) <= 63)) {
									$v['id'] = (string) \Smart::safe_validname((string)$v['id']); // allow: [a-z0-9] _ - . @
									if((string)$v['id'] != '') {
										$v['id'] = (string) '#'.$v['id']; // ensure is segment
										if((\strlen((string)$v['id']) >= 2) AND (\strlen((string)$v['id']) <= 63)) { // db id constraint
											$test_exists = (array) self::getRecordIdsById((string)$v['id']);
											$tmp_arr_refs = [ (string)$y_id ];
											if((int)\Smart::array_size($test_exists) <= 0) { // segment does not exists
												$tmp_new_arr = [
													'id' 		=> (string) $v['id'],
													'ref' 		=> (string) \Smart::json_encode((array)$tmp_arr_refs),
													'name' 		=> (string) \SmartUnicode::sub_str($y_name.': ['.$key.']', 0, 255),
													'mode' 		=> 'text', // default to text segment
													'admin' 	=> (string) $y_arr_data['admin'],
													'modified' 	=> (string) $y_arr_data['modified'],
												];
												$wr = (int) self::insertRecord((array)$tmp_new_arr, true); // insert with external transaction
												if($wr != 1) {
													return -16; // insert sub-segment failed
												} //end if
											} else {
												$wr = (array) self::updateRecordRefsById(
													(string) $v['id'],
													(array)  $tmp_arr_refs // array of IDs
												);
												if($wr[1] != 1) {
													return -15; // update sub-segment failed
												} //end if
											} //end if
										} else {
											return -14; // invalid render val content id (3)
										} //end if
									} else {
										return -13; // invalid render val content id (2)
									} //end if
								} else {
									return -12; // invalid render val content id (1)
								} //end if
							} //end if else
						} //end if
					} //end foreach
				} else {
					return -11; // invalid render val
				} //end if
			} else {
				return -10; // invalid render key
			} //end if
		} //end foreach
		//--
		return 1; // OK
		//--
	} //END FUNCTION


	private static function updateRecordRefsById($y_id, $y_refs_arr) {
		//--
		if(\Smart::array_size($y_refs_arr) <= 0) {
			return -1;
		} //end if
		if(\Smart::array_type_test($y_refs_arr) !== 1) { // must be array non-associative
			return -2;
		} //end if
		//--
		$arr_upd = [];
		foreach($y_refs_arr as $key => $val) {
			if((\strlen((string)$val) < 2) OR (\strlen((string)$val) > 63) OR (((string)$val != (string)\Smart::safe_validname((string)$val)) AND ((string)$val != (string)'#'.\Smart::safe_validname((string)$val)))) { // allow: [a-z0-9] _ - . @
				return -3;
			} //end if
			$arr_upd[] = (string) $val;
		} //end foreach
		//--
		if(\Smart::array_size($arr_upd) <= 0) {
			return -4;
		} //end if
		//--
		if((string)self::dbType() == 'pgsql') {
			return (array) \SmartPgsqlDb::write_data(
				'UPDATE "web"."page_builder" SET "ctrl" = $1, "ref" = smart_jsonb_arr_append("ref", $2) WHERE ("id" = $3)',
				[
					(string) '',
					(string) \SmartPgsqlDb::json_encode((array)$arr_upd), // ref add: json arr data
					(string) $y_id // ID
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			return (array) self::$db->write_data(
				'UPDATE `page_builder` SET `ctrl` = ?, `ref` = smart_json_arr_append(`ref`, ?) WHERE (`id` = ?)',
				[
					(string) '',
					(string) self::$db->json_encode((array)$arr_upd), // ref add: json arr data
					(string) $y_id // ID
				]
			);
		} else {
			return array();
		} //end if else
		//--
	} //END FUNCTION


	//===== SPECIALS: IMPORT / EXPORT


	public static function updateTranslationByText($text_deflang, $lang, $text_lang, $admin) {
		//--
		$text_lang = (string) \SmartModExtLib\PageBuilder\Utils::prepareCodeData((string)$text_lang, true);
		if((string)\trim((string)$text_lang) == '') {
			return -1;
		} //end if
		//--
		if((string)\trim((string)$text_deflang) == '') {
			return -2;
		} //end if
		if(((string)$lang == (string)\SmartTextTranslations::getDefaultLanguage()) OR ((string)$lang == '') OR (\strlen($lang) != 2) OR (\SmartTextTranslations::validateLanguage($lang) !== true)) {
			return -3;
		} //end if
		//--
		self::startTransaction();
		//--
		if((string)self::dbType() == 'pgsql') {
			$arr = (array) \SmartPgsqlDb::read_adata(
				'SELECT "id", "code" FROM "web"."page_builder" WHERE (("code" = $1) AND ("translations" = 1))',
				[
					(string) \base64_encode((string)$text_deflang)
				]
			);
		} elseif((string)self::dbType() == 'sqlite') {
			$arr = (array) self::$db->read_adata(
				'SELECT `id`, `code` FROM `page_builder` WHERE ((`code` = ?) AND (`translations` = 1))',
				[
					(string) \base64_encode((string)$text_deflang)
				]
			);
		} else {
			$arr = array();
		} //end if else
		//--
		$upd = 0;
		//--
		if(\Smart::array_size($arr) > 0) {
			//--
			for($i=0; $i<\Smart::array_size($arr); $i++) {
				//--
				if((string)self::dbType() == 'pgsql') {
					\SmartPgsqlDb::write_data(
						'DELETE FROM "web"."page_translations" WHERE (("id" = $1) AND ("lang" = $2))',
						[
							(string) $arr[$i]['id'],
							(string) $lang
						]
					);
				} elseif((string)self::dbType() == 'sqlite') {
					self::$db->write_data(
						'DELETE FROM `page_translations` WHERE ((`id` = ?) AND (`lang` = ?))',
						[
							(string) $arr[$i]['id'],
							(string) $lang
						]
					);
				} //end if else
				//--
				if((string)self::dbType() == 'pgsql') {
					$wr = (array) \SmartPgsqlDb::write_data(
						'INSERT INTO "web"."page_translations" '.\SmartPgsqlDb::prepare_statement(
							[
								'id' 		=> (string) $arr[$i]['id'],
								'lang' 		=> (string) $lang,
								'code' 		=> (string) \base64_encode((string)$text_lang),
								'admin' 	=> (string) $admin,
								'modified' 	=> (string) \date('Y-m-d H:i:s')
							],
							'insert'
						)
					);
				} elseif((string)self::dbType() == 'sqlite') {
					$wr = (array) self::$db->write_data(
						'INSERT INTO `page_translations` '.self::$db->prepare_statement(
							[
								'id' 		=> (string) $arr[$i]['id'],
								'lang' 		=> (string) $lang,
								'code' 		=> (string) \base64_encode((string)$text_lang),
								'admin' 	=> (string) $admin,
								'modified' 	=> (string) \date('Y-m-d H:i:s')
							],
							'insert'
						)
					);
				} else {
					$wr = array();
				} //end if else
				//--
				$upd += (int) $wr[1];
				//--
			} //end for
			//--
		} //end if
		//--
		self::commitTransaction();
		//--
		return (int) $upd;
		//--
	} //END FUNCTION


	public static function exportTranslationsByLang($lang, $mode='all', $arrmode='non-associative') {
		//--
		$lang = (string) \trim((string)$lang);
		//--
		if(((string)$lang == '') OR (\strlen($lang) != 2) OR (\SmartTextTranslations::validateLanguage($lang) !== true)) {
			return array(); // invalid language
		} //end if
		//--
		if((string)$lang == (string)\SmartTextTranslations::getDefaultLanguage()) {
			//--
			if((string)self::dbType() == 'pgsql') {
				$query = 'SELECT DISTINCT convert_from(decode("code", \'base64\'), \'UTF8\') AS '.\SmartPgsqlDb::escape_identifier((string)'lang_'.\SmartTextTranslations::getDefaultLanguage()).' FROM "web"."page_builder" WHERE "translations" = 1';
			} elseif((string)self::dbType() == 'sqlite') {
				$query = 'SELECT DISTINCT smart_base64_decode(`code`) AS `'.'lang_'.\SmartTextTranslations::getDefaultLanguage().'` FROM `page_builder` WHERE `translations` = 1';
			} //end if else
			//--
			if((string)$mode == 'missing') {
				if((string)self::dbType() == 'pgsql') {
					$query .= ' AND "code" = \'\'';
				} elseif((string)self::dbType() == 'sqlite') {
					$query .= ' AND `code` = \'\'';
				} //end if else
			} //end if
			//--
		} else {
			//--
			if((string)self::dbType() == 'pgsql') {
				$query = '
					SELECT DISTINCT
					convert_from(decode("a"."code", \'base64\'), \'UTF8\') AS '.\SmartPgsqlDb::escape_identifier((string)'lang_'.\SmartTextTranslations::getDefaultLanguage()).', COALESCE(convert_from(decode("b"."code", \'base64\'), \'UTF8\'), \'\') AS '.\SmartPgsqlDb::escape_identifier((string)'lang_'.$lang).'
					FROM "web"."page_builder" "a"
					LEFT OUTER JOIN "web"."page_translations" "b" ON
						"a"."id" = "b"."id" AND
						"b"."lang" = \''.\SmartPgsqlDb::escape_str((string)$lang).'\'
					WHERE
						"a"."translations" = 1
				';
			} elseif((string)self::dbType() == 'sqlite') {
				$query = '
					SELECT DISTINCT
					smart_base64_decode(`a`.`code`) AS `'.'lang_'.\SmartTextTranslations::getDefaultLanguage().'`, COALESCE(smart_base64_decode(`b`.`code`), \'\') AS `'.'lang_'.$lang.'`
					FROM `page_builder` `a`
					LEFT OUTER JOIN `page_translations` `b` ON
						`a`.`id` = `b`.`id` AND
						`b`.`lang` = \''.self::$db->escape_str((string)$lang).'\'
					WHERE
						`a`.`translations` = 1
				';
			} //end if else
			//--
			if((string)$mode == 'missing') {
				if((string)self::dbType() == 'pgsql') {
					$query .= ' AND "b"."lang" IS NULL';
				} elseif((string)self::dbType() == 'sqlite') {
					$query .= ' AND `b`.`lang` IS NULL';
				} //end if else
			} //end if
			//--
		} //end if
		//--
		if((string)$arrmode == 'associative') {
			if((string)self::dbType() == 'pgsql') {
				return (array) \SmartPgsqlDb::read_adata((string)$query);
			} elseif((string)self::dbType() == 'sqlite') {
				return (array) self::$db->read_adata((string)$query);
			} else {
				return array();
			} //end if else
		} else {
			if((string)self::dbType() == 'pgsql') {
				return (array) \SmartPgsqlDb::read_data((string)$query);
			} elseif((string)self::dbType() == 'sqlite') {
				return (array) self::$db->read_data((string)$query);
			} else {
				return array();
			} //end if else
		} //end if else
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
