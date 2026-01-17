<?php
// [@[#[!SF.DEV-ONLY!]#]@]
// Controller: Samples/EddsaTest
// Route: ?page=samples.eddsa-test
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT EXECUTION BEFORE RUNTIME READY
if(!defined('SMART_FRAMEWORK_RUNTIME_READY')) { // this must be defined in the first line of the application
	@http_response_code(500);
	die('Invalid Runtime Status in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------

define('SMART_APP_MODULE_AREA', 'SHARED');

/**
 * Abstract Controller
 *
 * @ignore
 *
 */
abstract class SmartAppAbstractController extends SmartAbstractAppController {


	final public function Initialize() {
		//--
		return true;
		//--
	} //END FUNCTION


	final public function ShutDown() {
		//--
		// do nothing
		//--
	} //END FUNCTION


	final public function Run() {

		//-- dissalow run this sample if not test mode enabled
		if(!defined('SMART_FRAMEWORK_TEST_MODE') OR (SMART_FRAMEWORK_TEST_MODE !== true)) {
			$this->PageViewSetErrorStatus(503, 'ERROR: Test mode is disabled ...');
			return;
		} //end if
		//--

		if(SmartCryptoEddsaSodium::isAvailable() !== true) {
			$this->PageViewSetErrorStatus(503, 'WARNING: LibSodium EdDSA is N/A ...');
			return;
		} //end if

		$this->PageViewSetCfg('rawpage', true);
		$this->PageViewSetCfg('rawmime', 'text/plain');
		$this->PageViewSetCfg('rawdisp', 'inline; filename="sample-eddsa.txt"');

		//--
		$main = [];
		$main[] = 'EdDSA Tests: Ed25519';
		//--

		//--
		$secret = (string) SmartHashCrypto::md5('Some Secret');
		$edKeySPair = (array) SmartCryptoEddsaSodium::ed25519NewKeypair((string)$secret);
		if((string)$edKeySPair['err'] != '') {
			$this->PageViewSetCfg('error', 'New Key Pair, with Fixed Secret Error: '.$edKeySPair['err']);
			return 500;
		} //end if
		$main[] = (string) 'New Key Pair, with Fixed Secret `'.$secret.'`: '.SmartUtils::pretty_print_var($edKeySPair);
		//--

		//--
		$edKeyPair = (array) SmartCryptoEddsaSodium::ed25519NewKeypair();
		if((string)$edKeyPair['err'] != '') {
			$this->PageViewSetCfg('error', 'New Key Pair, with Random Secret Error: '.$edKeyPair['err']);
			return 500;
		} //end if
		$main[] = (string) 'New Key Pair, with Random Secret: '.SmartUtils::pretty_print_var($edKeyPair);
		//--

		//--
		$dataMsg = 'A message to Sign'; // {{{SYNC-SIGN-MSG-GO-PHP}}}
		$main[] = 'Data to Sign/Verify: `'.$dataMsg.'`';
		//--

		//--
		$edSignData = (array) SmartCryptoEddsaSodium::ed25519SignData(
			(string) $edKeyPair['privKey'],
			(string) $edKeyPair['pubKey'],
			(string) $dataMsg
		);
		if((string)$edSignData['err'] != '') {
			$this->PageViewSetCfg('error', 'Signed Data Error: '.$edSignData['err']);
			return 500;
		} //end if
		if((string)trim((string)$edSignData['signatureB64']) == '') {
			$this->PageViewSetCfg('error', 'Signed Data is Empty');
			return 500;
		} //end if
		$main[] = (string) 'Signed Data: '.SmartUtils::pretty_print_var($edSignData);
		//--

		//--
		$edVerifyData = (array) SmartCryptoEddsaSodium::ed25519VerifySignedData(
			(string) $edKeyPair['pubKey'],
			(string) $edSignData['signatureB64'],
			(string) $dataMsg
		);
		if(((string)$edVerifyData['err'] != '') OR (($edVerifyData['verifyResult'] ?? null) !== true)) {
			$this->PageViewSetCfg('error', 'Verify Signed Error: '.$edVerifyData['err'].' # '.($edVerifyData['verifyResult'] ?? null));
			return 500;
		} //end if
		$main[] = (string) 'Verify Signed Data: '.SmartUtils::pretty_print_var($edVerifyData);
		//--

		//--
		$this->PageViewSetVar(
			'main',
			(string) implode("\n\n", (array)$main)
		);
		//--

	} //END FUNCTION


} //END CLASS


/**
 * Index Controller
 *
 * @ignore
 *
 */
final class SmartAppIndexController extends SmartAppAbstractController {} //END CLASS


/**
 * Admin Controller
 *
 * @ignore
 *
 */
final class SmartAppAdminController extends SmartAppAbstractController {} //END CLASS


/**
 * Task Controller
 *
 * @ignore
 *
 */
final class SmartAppTaskController extends SmartAppAbstractController {} //END CLASS

// end of php code
