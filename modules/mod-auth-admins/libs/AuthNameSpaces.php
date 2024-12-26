<?php
// Class: \SmartModExtLib\AuthAdmins\AuthNameSpaces
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

//----------------------------------------------------- PREVENT DIRECT EXECUTION (Namespace)
if(!\defined('\\SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@\http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@\basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

//# Depends on:
//	* SmartUtils
//	* SmartFrameworkSecurity

// [PHP8]

//=====================================================================================
//===================================================================================== CLASS START [OK: NAMESPACE]
//=====================================================================================


/**
 * Auth NameSpaces Provider
 * This class provides the methods for auth namespaces
 *
 * @ignore
 *
 * @version 	v.20231020
 * @package 	development:modules:AuthAdmins
 *
 */
final class AuthNameSpaces {

	// ::

	//================================================================
	public static function GetNameSpaces() {
		//--
		$areas = [];
		//--
		if(\SmartEnvironment::isAdminArea() === true) {
			//--
			$available_areas = array();
			//--
			if(\SmartEnvironment::isTaskArea() === true) {
				$available_areas = (array) \Smart::get_from_config('app-auth.tsk-namespaces', 'array');
			} else {
				$available_areas = (array) \Smart::get_from_config('app-auth.adm-namespaces', 'array');
			} //end if else
			//--
			if((\Smart::array_size($available_areas) > 0) AND (\Smart::array_type_test($available_areas) == 2)) {
				//--
				foreach((array)$available_areas as $key => $val) {
					$key = (string) \trim((string)$key);
					if((string)$key != '') {
						if(\Smart::is_nscalar($val)) {
							$val = (string) \trim((string)$val);
							if((string)$val != '') {
								$areas[(string)$key] = (string) $val;
							} //end if
						} //end if
					} //end if
				} //end foreach
				//--
			} //end if
			//--
			if(\Smart::array_size($areas) <= 0) {
				if(\SmartEnvironment::isTaskArea() === true) {
					$areas = [
						'Tasks' => 'task.php?page=auth-admins.tasks'
					];
				} else {
					$areas = [
						'Admins Manager' => 'admin.php?page=auth-admins.manager'
					];
				} //end if else
			} //end if
			//--
		} //end if
		//--
		return (array) $areas;
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
