<?php
// Class: \SmartModExtLib\PageBuilder\AbstractFrontendPageBuilder
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\PageBuilder;

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
 * Class: AbstractFrontendPageBuilder - Abstract Frontend PageBuilder, provides the Abstract Definitions to create PageBuilder (Frontend) Controllers and Plugins.
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 * @hints		needs to be extended as: UniqueClassPluginName
 *
 * @access 		PUBLIC
 *
 * @version 	v.20221219
 * @package 	development:modules:PageBuilder
 *
 */
abstract class AbstractFrontendPageBuilder extends \SmartAbstractAppController {


	//===== $y_ctrl can be: NULL / STRING / ARRAY
	final public function checkIfPageOrSegmentExist(string $y_id, $y_ctrl=null) : bool {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return false;
		} //end if
		//--
		return (bool) \SmartModDataModel\PageBuilder\PageBuilderFrontend::checkIfPageOrSegmentExist((string)$y_id, true, true, $y_ctrl);
		//--
	} //END FUNCTION
	//=====


	//===== $y_ctrl can be: NULL / STRING / ARRAY
	final public function checkIfPageExist(string $y_id, $y_ctrl=null) : bool {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return false;
		} //end if
		//--
		return (bool) \SmartModDataModel\PageBuilder\PageBuilderFrontend::checkIfPageOrSegmentExist((string)$y_id, true, false, $y_ctrl);
		//--
	} //END FUNCTION
	//=====


	//===== $y_ctrl can be: NULL / STRING / ARRAY
	final public function checkIfSegmentExist(string $y_id, $y_ctrl=null) : bool {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return false;
		} //end if
		//--
		return (bool) \SmartModDataModel\PageBuilder\PageBuilderFrontend::checkIfPageOrSegmentExist((string)$y_id, false, true, $y_ctrl);
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getPageById(string $y_id) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('pages', 'id', (string)$y_id);
		//--
	} //END FUNCTION
	//=====

	//=====
	final public function getSegmentById(string $y_id) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'id', (string)$y_id);
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getListOfPagesByCtrl(string $y_ctrl, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('pages', 'ctrl', (string)$y_ctrl, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		//--
	} //END FUNCTION
	//=====
	final public function countListOfPagesByCtrl(string $y_ctrl) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//--
		return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('pages', 'ctrl', (string)$y_ctrl);
		//--
	} //END FUNCTION
	//=====

	//=====
	final public function getListOfSegmentsByCtrl(string $y_ctrl, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'ctrl', (string)$y_ctrl, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		//--
	} //END FUNCTION
	//=====
	final public function countListOfSegmentsByCtrl(string $y_ctrl) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//--
		return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'ctrl', (string)$y_ctrl);
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getListOfPagesByTag(?string $y_ctrl, string $y_tag, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('pages', 'tags:ctrl', [ 'tags' => (string)$y_tag, 'ctrl' => (string)$y_ctrl ], (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} else {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('pages', 'tags', (string)$y_tag, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} //end if else
		//--
	} //END FUNCTION
	//=====
	final public function countListOfPagesByTag(?string $y_ctrl, string $y_tag) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('pages', 'tags:ctrl', [ 'tags' => (string)$y_tag, 'ctrl' => (string)$y_ctrl ]);
		} else {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('pages', 'tags', (string)$y_tag);
		} //end if else
		//--
	} //END FUNCTION
	//=====

	//=====
	final public function getListOfSegmentsByTag(?string $y_ctrl, string $y_tag, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'tags:ctrl', [ 'tags' => (string)$y_tag, 'ctrl' => (string)$y_ctrl ], (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} else {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'tags', (string)$y_tag, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} //end if else
		//--
	} //END FUNCTION
	//=====
	final public function countListOfSegmentsByTag(?string $y_ctrl, string $y_tag) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'tags:ctrl', [ 'tags' => (string)$y_tag, 'ctrl' => (string)$y_ctrl ]);
		} else {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'tags', (string)$y_tag);
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getListOfSegmentsByArea(?string $y_ctrl, string $y_area, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'area:ctrl', [ 'area' => (string)$y_area, 'ctrl' => (string)$y_ctrl ], (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} else {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'area', (string)$y_area, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} //end if else
		//--
	} //END FUNCTION
	//=====
	final public function countListOfSegmentsByArea(?string $y_ctrl, string $y_area) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//--
		if((string)$y_ctrl != '') {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'area:ctrl', [ 'area' => (string)$y_area, 'ctrl' => (string)$y_ctrl ]);
		} else {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'area', (string)$y_area);
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getListOfSegmentsByAreaTag(?string $y_ctrl, array $y_arr_area_tags, string $y_orderby='id', string $y_orderdir='ASC', int $y_limit=0, int $y_ofs=0) : array {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return array();
		} //end if
		//-- $y_arr_area_tags = [ 'area' => 'string', 'tags' => 'string' ]
		if((string)$y_ctrl != '') {
			$y_arr_area_tags['ctrl'] = (string) $y_ctrl;
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'area:tags:ctrl', (array)$y_arr_area_tags, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} else {
			return (array) \SmartModDataModel\PageBuilder\PageBuilderFrontend::getListOfObjectsBy('segments', 'area:tags', (array)$y_arr_area_tags, (string)$y_orderby, (string)$y_orderdir, (int)$y_limit, (int)$y_ofs);
		} //end if else
		//--
	} //END FUNCTION
	//=====
	final public function countListOfSegmentsByAreaTag(?string $y_ctrl, array $y_arr_area_tags) : int {
		//--
		if((string)$this->ControllerGetParam('module-area') != 'index') {
			return 0;
		} //end if
		//-- $y_arr_area_tags = [ 'area' => 'string', 'tags' => 'string' ]
		if((string)$y_ctrl != '') {
			$y_arr_area_tags['ctrl'] = (string) $y_ctrl;
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'area:tags:ctrl', (array)$y_arr_area_tags);
		} else {
			return (int) \SmartModDataModel\PageBuilder\PageBuilderFrontend::countListOfObjectsBy('segments', 'area:tags', (array)$y_arr_area_tags);
		} //end if else
		//--
	} //END FUNCTION
	//=====


	//=====
	final public function getObjectArrExtraData(?string $b64_data) : array {
		//--
		$yaml = (string) \base64_decode((string)$b64_data);
		//--
		if((string)\trim((string)$yaml) != '') {
			$ymp = new \SmartYamlConverter(false); // do not log YAML errors
			$yaml = (array) $ymp->parse((string)$yaml);
			$yerr = (string) $ymp->getError();
			if($yerr) {
				return array();
			} //end if
			$ymp = null;
		} else {
			$yaml = array();
		} //end if
		//--
		if(!\is_array($yaml)) {
			$yaml = [];
		} //end if
		if((!\array_key_exists('EXTRA', $yaml)) OR (!\is_array($yaml['EXTRA']))) {
			$yaml['EXTRA'] = [];
		} //end if
		//--
		return (array) $yaml['EXTRA'];
		//--
	} //END FUNCTION
	//=====


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
