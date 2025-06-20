<?php
// PHP Auth Users Auth Plugins for Smart.Framework
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
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: \SmartModExtLib\AuthUsers\AuthPlugins
 * Auth Users Auth Plugins
 *
 * @depends 	\SmartModExtLib\AuthUsers\Utils
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250314
 * @package 	modules:AuthUsers
 *
 */
final class AuthPlugins {

	// ::

	public const AUTH_USERS_PLUGINS_VALID_ID_REGEX  = '/^[a-z0-9]{3,17}$/'; // {{{SYNC-MAX-AUTH-PLUGIN-ID-LENGTH}}}

	private static ?array $plugins = null;


	public static function pluginExists(string $id) : bool {
		//--
		$id = (string) \strtolower((string)\trim((string)$id));
		if((string)$id == '') {
			return false;
		} //end if
		//--
		$plugins = (array) self::getPlugins();
		if((int)\Smart::array_size($plugins) <= 0) {
			return false;
		} //end if
		//--
		$plugin = (array) ($plugins[(string)$id] ?? null);
		if((int)\Smart::array_size($plugin) <= 0) {
			return false;
		} //end if
		//--
		return true;
		//--
	} //END FUNCTION


	public static function getPluginIdentity(string $id) : array {
		//--
		$id = (string) \strtolower((string)\trim((string)$id));
		if((string)$id == '') {
			return [];
		} //end if
		//--
		$plugins = (array) self::getPlugins();
		if((int)\Smart::array_size($plugins) <= 0) {
			return [];
		} //end if
		//--
		$plugin = (array) ($plugins[(string)$id] ?? null);
		if((int)\Smart::array_size($plugin) <= 0) {
			return [];
		} //end if
		//--
		return [
			'id' 		=> (string) $id,
			'name' 		=> (string) $plugin['name'],
			'url' 		=> (string) $plugin['url'],
			'svg' 		=> (string) $plugin['icon:svg'],
		];
		//--
	} //END FUNCTION


	public static function getPluginsForDisplay(string $stateCsrf) : array {
		//--
		$plugins = (array) self::getPlugins();
		if((int)\Smart::array_size($plugins) <= 0) {
			return [];
		} //end if
		//--
		$arr = [];
		foreach($plugins as $key => $val) {
			//--
			$arr[] = [
				'id' 	=> (string) $key,
				'name'	=> (string) $val['name'],
				'url' 	=> (string) \Smart::url_add_params((string)$val['url'], ['state' => (string)$stateCsrf], false), // add public csrf state
				'svg'	=> (string) $val['icon:svg'],
			];
			//--
		} //end foreach
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	public static function getPluginsForAccountSecurity(string $accountAllowed) : array {
		//--
		$plugins = (array) self::getPlugins();
		if((int)\Smart::array_size($plugins) <= 0) {
			return [];
		} //end if
		//--
		$arr = [];
		foreach($plugins as $key => $val) {
			//--
			$enabled = false;
			if(\strpos((string)$accountAllowed, '<'.$key.'>') !== false) {
				$enabled = true;
			} //end if
			//--
			$arr[] = [
				'id' 	=> (string) $key,
				'name'	=> (string) $val['name'],
				'svg'	=> (string) $val['icon:svg'],
				'state' => (string) (($enabled === true) ? 'active' : 'inactive'),
			];
			//--
		} //end foreach
		//--
		return (array) $arr;
		//--
	} //END FUNCTION


	//======== [ PRIVATES ]


	private static function getPlugins() : array {
		//--
		if(\is_array(self::$plugins)) {
			return (array) self::$plugins;
		} //end if
		//--
		$plugins = \Smart::get_from_config('auth:users.plugins:ext', 'array'); // do not cast
		if((int)\Smart::array_size($plugins) <= 0) {
			return [];
		} //end if
		if((int)\Smart::array_size($plugins) > 12) { // {{{SYNC-MAX-AUTH-PLUGIN-ID-LENGTH}}} ; max field size in DB is 255 for <plugin1>,<plugin2> where each id can be max 17 characters
			\Smart::log_warning(__METHOD__.' # Too many Auth Plugins Defined in Config, max is 12');
			return [];
		} //end if
		//--
		if((int)\Smart::array_type_test($plugins) != 2) { // associative
			\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config');
			return [];
		} //end if
		//--
		self::$plugins = [];
		$keys = [ // {{{SYNC-MOD-AUTH-USERS-REQUIRED-KEYS}}}
			'name',
			'url',
			'icon:svg',
		];
		foreach($plugins as $key => $val) {
			//--
			if(!\preg_match((string)self::AUTH_USERS_PLUGINS_VALID_ID_REGEX, (string)$key)) {
				\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key.' ; Invalid ID Value for Key: `'.$keys[$i].'`');
				return [];
			} //end if
			if((int)\Smart::array_size($val) <= 0) {
				\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key);
				return [];
			} //end if
			//--
			for($i=0; $i<count($keys); $i++) {
				if(!\array_key_exists((string)$keys[$i], (array)$val)) {
					\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key.' ; Missing Key: `'.$keys[$i].'`');
					return [];
				} //end if
				if(!\Smart::is_nscalar($val[(string)$keys[$i]] ?? null)) {
					\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key.' ; Non-Scalar Value for Key: `'.$keys[$i].'`');
					return [];
				} //end if
				$val[(string)$keys[$i]] = (string) \trim((string)($val[(string)$keys[$i]] ?? null));
				if((string)\trim((string)($val[(string)$keys[$i]] ?? null)) == '') {
					\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key.' ; Empty Value for Key: `'.$keys[$i].'`');
					return [];
				} //end if
				if((\strpos((string)$keys[$i], 'url:') !== false) AND (\strpos((string)$val[(string)$keys[$i]], 'https://') !== 0)) {
					\Smart::log_warning(__METHOD__.' # Invalid Auth Plugins Definitions in Config at entry: '.$key.' ; Non-HTTPS URL Value for Key: `'.$keys[$i].'`');
					return [];
				} //end if
				$entry[(string)$keys[$i]] = (string) $val[(string)$keys[$i]];
			} //end for
			//--
			self::$plugins[(string)$key] = (array) $val;
			//--
		} //end foreach
		//--
		return (array) self::$plugins;
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
