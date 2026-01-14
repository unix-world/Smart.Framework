<?php
// [LIB - Smart.Framework / Samples / DhkxTest
// (c) 2006-2023 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\DhkxTest
// Type: Module Library
// Info: this class integrates with the default Smart.Framework modules autoloader so does not need anything else to be setup

namespace SmartModExtLib\Samples;

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
 * Sample DhkxTest Test
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20260114
 *
 */
final class DhkxTest {

	// ::

	// this have to be run (and display) on the main page
	public static function renderViewJsPhpExchange() : string {
		//--
		$initServerData = (array) self::initServerData();
		//--
		return (string) self::initClientData((array)$initServerData);
		//--
	} //END FUNCTION


	// PHP server
	private static function initServerData() : array {
		//--
		$dh  = new \SmartDhKx();
		$bas = (string) $dh->getBaseGen(); // base needs to be passed to client
		$srv = (array)  $dh->getSrvData((string)$bas); // server pub needs to be passed to client
		//--
		return [
			'bas' => (string) $bas,
			'srv' => (array)  $srv,
		];
		//--
	} //END FUNCTION


	// JS Client
	private static function initClientData(array $initServerData) : string {
		//--
		$bas = (string) ($initServerData['bas'] ?? null);
		$srv = (array)  ($initServerData['srv'] ?? null);
		//--
		$prevSrvDhkxPub  = (string) \trim((string)\SmartUtils::get_cookie('testDhkx_SrvPub'));
		$prevSrvDhkxSec  = (string) \trim((string)\SmartUtils::get_cookie('testDhkx_SrvSec'));
		$prevCliDhkxPub  = (string) \trim((string)\SmartUtils::get_cookie('testDhkx_CliPub'));
		$prevCliDhkxShad = (string) \trim((string)\SmartUtils::get_cookie('testDhkx_CliShad'));
		//--
		\SmartUtils::set_cookie('testDhkx_SrvSec', (string)\SmartCipherCrypto::tf_encrypt((string)($srv['sec'] ?? null))); // server must preserve it's secret
		//--
		$prevDhkxSrvShad = '';
		if( // client must return: client pub
			((string)$prevSrvDhkxPub != '')
			AND
			((string)$prevSrvDhkxSec != '')
			AND
			((string)$prevCliDhkxPub != '')
			AND
			((string)$prevCliDhkxShad != '')
		) {
			$prevSrvDhkxSec = (string) \SmartCipherCrypto::tf_decrypt((string)$prevSrvDhkxSec);
			if((string)$prevSrvDhkxSec != '') {
				$prevDhkxSrvShad = (string) (new \SmartDhKx())->getSrvShad((string)$prevSrvDhkxSec, (string)$prevCliDhkxPub);
			} //end if
		} //end if
		if((string)$prevDhkxSrvShad == '') {
			$prevSrvDhkxPub  = '';
			$prevSrvDhkxSec  = '';
			$prevCliDhkxPub  = '';
			$prevCliDhkxShad = '';
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			(string) 'modules/mod-samples/libs/views/dhkx-test.mtpl.htm',
			[
				'SRV-BASE' 		=> (string) $bas,
				'SRV-PUB' 		=> (string) ($srv['pub'] ?? null),
				'SRV-DATA' 		=> (string) \Smart::json_encode((array)$srv),
				'CLI-PREV-PUB' 	=> (string) $prevCliDhkxPub,
				'CLI-PREV-SHAD' => (string) $prevCliDhkxShad,
				'SRV-PREV-SHAD' => (string) $prevDhkxSrvShad,
				'SRV-PREV-SEC' 	=> (string) $prevSrvDhkxSec,
				'SRV-PREV-PUB' 	=> (string) $prevSrvDhkxPub,
			]
		);
		//--
	} //END FUNCTION


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//end of php code
