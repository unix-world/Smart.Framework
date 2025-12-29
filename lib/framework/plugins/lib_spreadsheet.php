<?php
// [LIB - Smart.Framework / Plugins / SpreadSheet Import/Export]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// SpreadSheet Import/Export: XML (Excel 2003)
// DEPENDS:
//	* Smart::
// DEPENDS-EXT:
//======================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SmartSpreadSheetExport - Exports (structured) Data to XML Spreadsheet (Excel 2003).
 *
 * @usage  		dynamic object: (new Class())->method() - This class provides only DYNAMIC methods
 *
 * @depends 	classes: Smart
 * @version 	v.20251211
 * @package 	Plugins:ExportAndImport
 *
 */
final class SmartSpreadSheetExport {

	//->

	private const VERSION = 'excel.2003.xml.spreadsheet:20251211';

	private $cellWidth = 150;
	private $cellHeight = 15;


	//=====================================================================
	public function __construct(?int $y_cell_width=0, ?int $y_cell_height=0) {
		//--
		if(((int)$y_cell_width > 0) AND ((int)$y_cell_width <= 1000)) {
			$this->cellWidth = (int) $y_cell_width;
		} //end if
		//--
		if(((int)$y_cell_height > 0) AND ((int)$y_cell_height <= 500)) {
			$this->cellHeight = (int) $y_cell_height;
		} //end if
		//--
	} //END FUNCTION
	//=====================================================================


	//=====================================================================
	public function getMimeType() {
		//--
		return (string) 'application/vnd.ms-excel';
		//--
	} //END FUNCTION
	//=====================================================================


	//=====================================================================
	public function getDispositionHeader(?string $y_filename='file.xml') {
		//--
		return (string) 'attachment; filename="'.Smart::safe_filename((string)$y_filename).'"';
		//--
	} //END FUNCTION
	//=====================================================================


	//=====================================================================
	// creates a Spreadsheet from Array
	public function getFileContents(?string $y_sheet_name, array $y_arr_fields, array $y_arr_data) {
		//--
		$y_sheet_name = (string) Smart::text_cut_by_limit((string)trim((string)$y_sheet_name), 31, false, '...');
		if((string)trim((string)$y_sheet_name) == '') {
			$y_sheet_name = 'Spreadsheet';
		} //end if
		//--
		$str = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'."\n";
		$str .= '<'.'?mso-application progid="Excel.Sheet"?'.'>'."\n";
		$str .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:html="https://www.w3.org/TR/html401/">'."\n";
		$str .= "\t".'<Styles>'."\n";
		$str .= "\t\t".'<Style ss:ID="Default" ss:Name="Default"/>'."\n";
		$str .= "\t\t".'<Style ss:ID="Bold" ss:Name="Bold"><Font ss:Bold="1"/></Style>'."\n";
		$str .= "\t".'</Styles>'."\n";
		$str .= "\t".'<Worksheet ss:Name="'.trim((string)$this->escapeStr((string)$y_sheet_name, true)).'">'."\n";
		$str .= "\t\t".'<Table>'."\n";
		//--
		if(Smart::array_size($y_arr_fields) > 0) {
			for($i=0; $i<Smart::array_size($y_arr_fields); $i++) {
				$str .= "\t\t\t".'<Column ss:Index="'.(int)($i+1).'" ss:AutoFitWidth="0" ss:Width="'.(int)$this->cellWidth.'"/>'."\n";
			} //end for
			$str .= "\t\t\t".'<Row ss:StyleID="Bold">'."\n";
			for($i=0; $i<Smart::array_size($y_arr_fields); $i++) {
				$str .= "\t\t\t\t".'<Cell>'."\n";
				$str .= "\t\t\t\t\t".'<Data ss:Type="String">'.$this->escapeStr((string)$y_arr_fields[$i], true).'</Data>'."\n";
				$str .= "\t\t\t\t".'</Cell>'."\n";
			} //end for
			$str .= "\t\t\t".'</Row>'."\n";
		} //end if
		if(Smart::array_size($y_arr_data) > 0) {
			for($i=0; $i<Smart::array_size($y_arr_data); $i++) {
				if(Smart::array_size($y_arr_data[$i] ?? null) > 0) {
					$str .= "\t\t\t".'<Row ss:Height="'.(int)$this->cellHeight.'">'."\n";
					foreach((array)$y_arr_data[$i] as $key => $val) {
						$str .= "\t\t\t\t".'<Cell>'."\n";
						$str .= "\t\t\t\t\t".'<Data ss:Type="'.(preg_match('/^(\-)?[0-9]+(\.[0-9]+)?$/', (string)$val) ? 'Number' : 'String').'">'.$this->escapeStr((string)$val, false).'</Data>'."\n";
						$str .= "\t\t\t\t".'</Cell>'."\n";
					} //end foreach
					$str .= "\t\t\t".'</Row>'."\n";
				} //end if
			} //end for
		} //end if
		//--
		$str .= "\t\t".'</Table>'."\n";
		$str .= "\t\t".'<x:WorksheetOptions/>'."\n";
		$str .= "\t".'</Worksheet>'."\n";
		$str .= '</Workbook>'."\n";
		//--
		$str .= '<!-- # SpreadSheet ('.$this->escapeStr((string)self::VERSION.' / '.SMART_FRAMEWORK_VERSION, true).') # -->'."\n";
		//--
		return (string) $str;
		//--
	} //END FUNCTION
	//=====================================================================


	//#####


	//=====================================================================
	private function escapeStr(?string $y_str, bool $is_head) {
		//--
		$y_str = (string) str_replace(["\r\n", "\r"], "\n", (string)$y_str);
		$y_str = (string) Smart::escape_html((string)$y_str);
		if($is_head === true) {
			$y_str = (string) str_replace(["\n", "\t"], ' ', (string)$y_str);
		} else {
			$y_str = (string) str_replace("\n", '&#10;', (string)$y_str); // safe new lines (must be done after encoding HTML entities)
		} //end if else
		//--
		return (string) $y_str;
		//--
	} //END FUNCTION
	//=====================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================



/**
 * Class: SmartSpreadSheetImport - Imports XML Spreadsheet (Excel 2003) to (structured) Data.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @depends 	classes: Smart, SmartXmlParser
 * @version 	v.20250107
 * @package 	Plugins:ExportAndImport
 *
 */
final class SmartSpreadSheetImport {

	//::


	//=====================================================================
	// parse Spreadsheet to Array
	public static function parseContentAsArr(?string $input_str, bool $first_line_is_header=true) {
		//--
		$input_str = (string) trim((string)$input_str);
		if((string)$input_str == '') {
			return array();
		} //end if
		//--
		if(stripos($input_str, '<'.'?xml ') !== 0) {
			return array();
		} //end if
		//--
		$input_str = (string) str_replace('&#10;', "\n", $input_str); // FIX: Line Break
		//--
		$csv_arr = (new SmartXmlParser('domxml'))->transform($input_str);
		$input_str = ''; // free mem
		//print_r($csv_arr); die();
		if(Smart::array_size($csv_arr) <= 0) {
			return array();
		} //end if
		if(Smart::array_size($csv_arr['Worksheet'] ?? null) > 0) {
			$csv_arr = (array) $csv_arr['Worksheet']; // standard
		} elseif(Smart::array_size($csv_arr['ss:Worksheet'] ?? null) > 0) {
			$csv_arr = (array) $csv_arr['ss:Worksheet']; // bugfix for OOffice
		} else {
			return array();
		} //end if else
		if(Smart::array_size($csv_arr['Table'] ?? null) <= 0) {
			return array();
		} //end if
		$csv_arr = (array) $csv_arr['Table'];
		if(Smart::array_size($csv_arr['Row'] ?? null) <= 0) {
			return array();
		} //end if
		$csv_arr = (array) $csv_arr['Row'];
		//--
		$hdr_arr = array();
		$data_arr = array();
		//--
		if($first_line_is_header === true) {
			$index_data = 1;
			if(is_array($csv_arr[0] ?? null)) {
				if(Smart::array_size($csv_arr[0]['Cell'] ?? null) > 0) {
					for($j=0; $j<Smart::array_size($csv_arr[0]['Cell']); $j++) {
						if(Smart::array_size($csv_arr[0]['Cell'][$j]) > 0) {
							if(Smart::array_size($csv_arr[0]['Cell'][$j]['Data'] ?? null) > 0) {
								if(array_key_exists('@content', (array)$csv_arr[0]['Cell'][$j]['Data'])) {
									$hdr_arr[] = (string) $csv_arr[0]['Cell'][$j]['Data']['@content'];
								} //end if
							} //end if
						} //end if
					} //end for
				} //end if
			} //end if
		} else {
			$index_data = 0;
		} //end if else
		//--
		if((int)Smart::array_size($csv_arr) > (int)$index_data) {
			for($i=$index_data; $i<Smart::array_size($csv_arr); $i++) {
				if(is_array($csv_arr[$i])) {
					if(Smart::array_size($csv_arr[$i]['Cell'] ?? null) > 0) {
						for($j=0; $j<Smart::array_size($csv_arr[$i]['Cell']); $j++) {
							if(Smart::array_size($csv_arr[$i]['Cell'][$j] ?? null) > 0) {
								if(Smart::array_size($csv_arr[$i]['Cell'][$j]['Data'] ?? null) > 0) {
									if(array_key_exists('@content', (array)$csv_arr[$i]['Cell'][$j]['Data'])) {
										$data_arr[] = (string) $csv_arr[$i]['Cell'][$j]['Data']['@content'];
									} else {
										$data_arr[] = ''; // preserve empty cells in all defined columns
									} //end if
								} //end if
							} //end if
						} //end for
					} //end if
				} //end if
			} //end if
		} //end if
		//--
		return array(
			'header' 	=> (array) $hdr_arr, // 1st line
			'data' 		=> (array) $data_arr // the rest of lines
		);
		//--
	} //END FUNCTION
	//=====================================================================


} //END CLASS

//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


// end of php code
