<?php
// PHP Auth Users Apps for Smart.Framework
// Module Library
// (c) 2008-present unix-world.org - all rights reserved

// this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\AuthUsers;

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
 * Class: \SmartModExtLib\AuthUsers\Apps
 * Auth Users Apps
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20251202
 * @package 	modules:AuthUsers
 *
 */
final class Apps {

	// ::

	private const APPS = [
		[
			'label' => 'OAuth2 Manager',
			'icon'  => 'modules/mod-oauth2/views/img/logo-oauth.svg',
			'link'  => '?page=oauth2.manager',
		],
	];


	public static function getApps() : array {
		//--
		$apps = [];
		//--
		if((\defined('\\SMART_AUTHUSERS_APPS')) AND ((int)\Smart::array_size(\SMART_AUTHUSERS_APPS) > 0)) {
			foreach(\SMART_AUTHUSERS_APPS as $key => $val) {
				if(\is_array($val)) {
					$label = (string) \trim((string)($val['label'] ?? null));
					$lang  = (string) \SmartTextTranslations::getLanguage();
					if(isset($val['label:'.$lang])) {
						$llabel = (string) \trim((string)($val['label:'.$lang] ?? null));
						if((string)$llabel != '') {
							$label = (string) $llabel;
						} //end if
					} //end if
					$icon  = (string) \trim((string)($val['icon']  ?? null));
					$link  = (string) \trim((string)($val['link']  ?? null));
					if(((string)$label != '') AND ((string)$label != '') AND ((string)$label != '')) {
						$apps[] = [
							'label' => (string) $label,
							'icon'  => (string) $icon,
							'link'  => (string) $link,
						];
					} else {
						\Smart::log_warning(__METHOD__.' # Invalid App definition at key: '.$key);
					} //end if else
				} else {
					\Smart::log_warning(__METHOD__.' # Empty App definition at key: '.$key);
				} //end if else
			} //end foreach
		} //end if
		//--
		if((int)\Smart::array_size($apps) <= 0) {
			$apps = (array) self::APPS;
		} //end if
		//--
		return (array) $apps;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
