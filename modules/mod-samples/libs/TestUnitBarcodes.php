<?php
// [LIB - Smart.Framework / Samples / Test (1D & 2D) Barcodes]
// (c) 2006-2021 unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

// Class: \SmartModExtLib\Samples\TestUnitBarcodes
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
 * Test (1D & 2D) Barcodes
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20210526
 *
 */
final class TestUnitBarcodes {

	// ::


	//============================================================
	public static function test2dBarcodeQRMiniCode() {
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) '<span title="'.\Smart::escape_html((string)$str).'">'.(new \SmartQR2DBarcode('M'))->renderAsSVG((string)$str, ['cm'=>'#555555','wq'=>0]).'</span>';
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test2dBarcodeQRCode() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes2D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = 'Smart スマート // Cloud Application Platform クラウドアプリケーションプラットフォーム áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇòóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝßȘŞŠŚŜțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ " <p></p> ? & * ^ $ @ ! ` ~ % () [] {} | \ / + - _ : ; , . #0.97900300';
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes2D::getBarcode($str, 'qrcode', 'html-svg', 2, '#333333', 'M');
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test2dBarcodeAztec() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes2D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = 'Smart スマート // Cloud Application Platform クラウドアプリケーションプラットフォーム áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇòóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝßȘŞŠŚŜțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ " <p></p> ? & * ^ $ @ ! ` ~ % () [] {} | \ / + - _ : ; , . #0.97900300';
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes2D::getBarcode($str, 'aztec', 'html-svg', 2, '#333333');
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test2dBarcodeDataMatrix() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes2D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = 'áâãäåāăąÁÂÃÄÅĀĂĄćĉčçĆĈČÇďĎèéêëēĕėěęÈÉÊËĒĔĖĚĘĝģĜĢĥħĤĦìíîïĩīĭȉȋįÌÍÎÏĨĪĬȈȊĮĳĵĲĴķĶĺļľłĹĻĽŁñńņňÑŃŅŇòóôõöōŏőøœÒÓÔÕÖŌŎŐØŒŕŗřŔŖŘșşšśŝßȘŞŠŚŜțţťȚŢŤùúûüũūŭůűųÙÚÛÜŨŪŬŮŰŲŵŴẏỳŷÿýẎỲŶŸÝźżžŹŻŽ " <p></p> ? & * ^ $ @ ! ` ~ % () [] {} | \ / + - _ : ; , . #0.97900300';
		//--
		$use_cache = 60; // cache for 60 seconds
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes2D::getBarcode($str, 'semacode', 'html-svg', 2, '#333333', '', (int)$use_cache);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test2dBarcodePdf417() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes2D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = '1234567890 abcdefghij klmnopqrst uvwxzy 234DSKJFH23YDFKJHaS AbcdeFghij KlmnopQrsT uvWxZy 234D-SKJFH23YDFKJHaS '.time();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes2D::getBarcode($str, 'pdf417', 'html-png', 1, '#333333', '1');
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcodeEanUpc() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = '123456789012'; // fixed length of 12 digits
		//--
		$use_cache = 60; // cache for 60 seconds
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, 'EANUPC', 'html-png', 1, 20, '#333333', true, (int)$use_cache);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcode128B() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, '128', 'html-svg', 1, 20, '#333333', true);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcode93() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, '93', 'html-svg', 1, 20, '#333333', true);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcode39() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, '39', 'html-svg', 1, 20, '#333333', true);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcodeRms() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, 'RMS', 'html-svg', 2, 20, '#333333', true);
		//--
	} //END FUNCTION
	//============================================================


	//============================================================
	public static function test1dBarcodeKix() {
		//--
		if(!\class_exists('\\SmartModExtLib\\Barcodes\\SmartBarcodes1D')) {
			return 'Barcode Module is missing';
		} //end if
		//--
		$str = (string) self::generateCodeForBarcode1D();
		//--
		return (string) \SmartModExtLib\Barcodes\SmartBarcodes1D::getBarcode($str, 'KIX', 'html-png', 2, 20, '#333333', true);
		//--
	} //END FUNCTION
	//============================================================


	//===== PRIVATES


	//============================================================
	private static function generateCodeForBarcode1D() {
		//--
		return (string) \Smart::uuid_10_str();
		//--
	} //END FUNCTION
	//============================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
