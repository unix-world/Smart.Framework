<?php
// [LIB - Smart.Framework / Samples / Test SQLite3]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitSQLite3
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

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
 * Test SQLite3
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210526
 *
 */
final class TestUnitSQLite3 {

	// ::

	//============================================================
	public static function testJsonAutocomplete($src) {

		//--
		$rd = (array) (new \SmartModDataModel\Samples\TestUnitSQLite3Model())->getListDataAutocomplete($src, 25);
		//--

		//-- build the required data structure for autocomplete
		$arr = array();
		for($i=0; $i<\Smart::array_size($rd); $i++) { // id is optional for display only
			$arr[] = [
				'id' => '',
				'value' => (string)$rd[$i]['iso'],
				'label' => $rd[$i]['name'] // don't cast
			];
		} //end for
		//--

		//--
		return \Smart::json_encode((array)$arr);
		//--

	} //END FUNCTION
	//============================================================


	//============================================================
	public static function testJsonSmartgrid($ofs, $sortby, $sortdir, $sorttype, $src='') {

		//--
		$data = [
			'status'  			=> 'OK',
			'crrOffset' 		=> (int) $ofs,
			'itemsPerPage' 		=> 25,
			'sortBy' 			=> (string) $sortby,
			'sortDir' 			=> (string) $sortdir,
			'sortType' 			=> (string) $sorttype,
			'filter' 			=> [
				'src' => (string) $src
			]
		];
		//--

		//--
		$model = new \SmartModDataModel\Samples\TestUnitSQLite3Model(); // open connection / initialize
		//--
		$data['totalRows'] 	= (int)   $model->getCountDataTable($src);
		$data['rowsList'] 	= (array) $model->getListDataTable($src, $data['itemsPerPage'], $data['crrOffset'], $data['sortBy'], $data['sortDir']);
		//--
		unset($model); // close connection
		//--

		//--
		return \Smart::json_encode((array)$data);
		//--

	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
