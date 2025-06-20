<?php
// Class: \SmartModExtLib\AuthAdmins\SmartAdmViewHtmlHelpers
// (c) 2008-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

namespace SmartModExtLib\AuthAdmins;

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
 * Class: SmartAdmViewHtmlHelpers - Easy to use HTML ViewHelper Components.
 *
 * @usage  		static object: Class::method() - This class provides only STATIC methods
 *
 * @version 	v.20250314
 * @package 	development:modules:AuthAdmins
 *
 */
final class SmartAdmViewHtmlHelpers {

	// ::


	//================================================================
	/**
	 * Manage a SINGLE Selection HTML List Element for Edit or Display data
	 *
	 * @param STRING			$y_id					the HTML element ID
	 * @param STRING 			$y_selected_value		selected value of the list ; ex: 'id1'
	 * @param ENUM				$y_mode					'form' = display form | 'list' = display list
	 * @param ARRAY				$_yarr_data				DATASET ROWS AS: ['id' => 'name', 'id2' => 'name2'] OR ['id', 'name', 'id2', 'name2']
	 * @param STRING 			$y_varname				as 'frm[test]'
	 * @param INTEGER			$y_dimensions			dimensions in pixels (width or width / (list) height for '#JS-UI#' or '#JS-UI-FILTER#')
	 * @param CODE				$y_custom_js			custom js code (Ex: onsubmit="" or onchange="")
	 * @param YES/NO			$y_raw					If Yes, the description values will not apply html special chars
	 * @param YES/NO			$y_allowblank			If Yes, a blank value is allowed in list
	 * @param STRING 			$y_blank_name 			The name of the blank value ; If none will use empty (nbsp) space
	 * @param CSS/#JS-UI#		$y_extrastyle			Extra CSS Style | or Extra CSS Class 'class:a-css-class' | Visual UI Mode '#JS-UI#' or '#JS-UI-FILTER#'
	 *
	 * @return STRING 									[HTML Code]
	 */
	public static function html_select_list_single($y_id, $y_selected_value, $y_mode, $_yarr_data, $y_varname='', $y_dimensions='150/0', $y_custom_js='', $y_raw='no', $y_allowblank='yes', $y_blank_name='', $y_extrastyle='') {

		//-- fix associative array
		$arr_type = \Smart::array_type_test($_yarr_data);
		if($arr_type === 2) { // associative array detected
			$arr_save = (array) $_yarr_data;
			$_yarr_data = array();
			foreach((array)$arr_save as $key => $val) {
				$_yarr_data[] = (string) $key;
				$_yarr_data[] = (string) $val;
			} //end foreach
			$arr_save = array();
		} //end if
		//--

		//--
		$tmp_dimens = (array) \explode('/', (string)\trim((string)$y_dimensions));
		//--
		$the_width = 0;
		if(\array_key_exists(0, $tmp_dimens)) {
			$the_width = (int) isset($tmp_dimens[0]) ? $tmp_dimens[0] : 0;
		} //end if
		$the_height = 0;
		if(\array_key_exists(1, $tmp_dimens)) {
			$the_height = (int) isset($tmp_dimens[1]) ? $tmp_dimens[1] : 0;
		} //end if
		//--
		if($the_width < 0) {
			$the_width = 0;
		} //end if
		if($the_width > 0) {
			if($the_width < 50) {
				$the_width = 50;
			} elseif($the_width > 1200) {
				$the_width = 1200;
			} //end if
		} //end if
		//--
		if($the_height < 0) {
			$the_height = 0;
		} //end if
		//--

		//--
		$y_varname = (string) \trim((string)$y_varname);
		$y_custom_js = (string) \trim((string)$y_custom_js);
		$y_blank_name = (string) \trim((string)$y_blank_name);
		//--

		//--
		$element_id = (string) \Smart::escape_html((string)\Smart::create_htmid((string)\trim((string)$y_id)));
		//--

		//--
		$js = '';
		$css_class = '';
		//--
		if(((string)$element_id != '') && (((string)$y_extrastyle == '#JS-UI#') || ((string)$y_extrastyle == '#JS-UI-FILTER#'))) {
			//--
			$tmp_extra_style = (string) $y_extrastyle;
			$y_extrastyle = ''; // reset
			//--
			if((string)$y_mode == 'form') {
				//--
				if($the_width <= 0) {
					$the_width = 150;
				} //end if
				$the_width = $the_width + 20;
				if($the_height > 0) {
					if($the_height < 50) {
						$the_height = 50;
					} //end if
					if($the_height > 200) {
						$the_height = 200;
					} //end if
				} else {
					$the_height = 200; // default
				} //end if else
				//--
				if((string)$tmp_extra_style == '#JS-UI-FILTER#') {
					$have_filter = true;
					$the_width += 25;
				} else {
					$have_filter = false;
				} //end if else
				//--
				$js = (string) \SmartMarkersTemplating::render_file_template(
					'modules/mod-auth-admins/views/js/listselect/templates/ui-list-single.inc.htm',
					[
						'LANG' => (string) \SmartTextTranslations::getLanguage(),
						'ID' => (string) $element_id,
						'WIDTH' => (int) $the_width,
						'HEIGHT' => (int) $the_height,
						'HAVE-FILTER' => (bool) $have_filter
					],
					'yes' // export to cache
				);
				//--
			} //end if else
			//--
		} else {
			//--
			if((string)$y_mode == 'form') {
				$css_class = 'class="ux-field';
				if((string)$y_extrastyle != '') {
					$y_extrastyle = (string) \trim((string)$y_extrastyle);
					if(\stripos($y_extrastyle, 'class:') === 0) {
						$y_extrastyle = (string) \trim((string)\substr((string)$y_extrastyle, (int)\strlen('class:')));
						if((string)$y_extrastyle != '') {
							$css_class .= ' '.\Smart::escape_html((string)$y_extrastyle);
						} //end if
						$y_extrastyle = '';
					} //end if
				} //end if else
				$css_class .= '"';
			} //end if
			//--
		} //end if else
		//--

		//--
		$out = '';
		//--
		if((string)$y_mode == 'form') {
			//--
			$out .= '<select '.($y_varname ? 'name="'.\Smart::escape_html((string)$y_varname).'" ' : '').($element_id ? 'id="'.$element_id.'" ' : '').'size="1" '.$css_class;
			//--
			$style = [];
			if((int)$the_width > 0) {
				$style[] = 'width:'.(int)$the_width.'px;';
			} //end if
			$y_extrastyle = (string) \trim((string)$y_extrastyle);
			if((string)$y_extrastyle != '') {
				$style[] = (string) \Smart::escape_html((string)$y_extrastyle);
			} //end if
			//--
			if(\Smart::array_size($style) > 0) {
				$out .= ' style="'.\implode(' ', $style).'"';
			} //end if
			//--
			if((string)$y_custom_js != '') {
				$out .= ' '.$y_custom_js;
			} //end if
			//--
			$out .= '>'."\n";
			//--
			if((string)$y_allowblank == 'yes') {
				$out .= '<option value="">'.($y_blank_name ? \Smart::escape_html((string)$y_blank_name) : '&nbsp;').'</option>'."\n"; // we need a blank value to avoid wrong display of selected value
			} //end if
			//--
		} //end if
		//--
		$found = 0;
		for($i=0; $i<\Smart::array_size($_yarr_data); $i++) {
			//--
			$i_key = $i;
			$i_val = $i+1;
			$i=$i+1;
			//--
			if((string)$y_mode == 'form') {
				//--
				$tmp_sel = '';
				//--
				if((\strlen($y_selected_value) > 0) AND ((string)$y_selected_value == (string)$_yarr_data[$i_key])) {
					$tmp_sel = ' selected'; // single ID
				} //end if
				//--
				if((string)$y_raw == 'yes') {
					$tmp_desc_val = (string) $_yarr_data[$i_val];
				} else {
					$tmp_desc_val = (string) \SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val]));
				} //end if else
				//--
				if(\strpos((string)$_yarr_data[$i_key], '#OPTGROUP#') === 0) {
					$out .= '<optgroup label="'.$tmp_desc_val.'">'."\n"; // the optgroup
				} else {
					$out .= '<option value="'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_key])).'"'.$tmp_sel.'>'.$tmp_desc_val.'</option>'."\n";
				} //end if else
				//--
			} else {
				//--
				if(((string)$_yarr_data[$i_val] != '') AND ((string)$y_selected_value == (string)$_yarr_data[$i_key])) {
					//-- single ID
					if((string)$y_raw == 'yes') {
						$out .= (string) $_yarr_data[$i_val]."\n";
					} else {
						$out .= (string) \SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val]))."\n";
					} //end if else
					//--
					$found += 1;
					//--
				} //end if
				//--
			} //end if else
			//--
		} //end for
		//--
		if((string)$y_mode == 'form') {
			//--
			$out .= '</select>'."\n";
			//--
			$out .= (string) $js."\n";
			//--
		} else {
			//--
			if($found <= 0) {
				if($y_allowblank != 'yes') {
					$out .= (string) \SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$y_selected_value)).'<sup>?</sup>'."\n";
				} //end if
			} //end if
			//--
		} //end if
		//--

		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate a MULTIPLE (many selections) View/Edit List to manage ID Selections
	 *
	 * @param STRING			$y_id					the HTML element ID
	 * @param STRING 			$y_selected_value		selected value(s) data as ARRAY [ 'id1', 'id2' ] or STRING LIST as: '<id1>,<id2>'
	 * @param ENUM				$y_mode					'form' = display form | checkboxes | 'list' = display list
	 * @param ARRAY				$_yarr_data				DATASET ROWS AS: ['id' => 'name', 'id2' => 'name2'] OR ['id', 'name', 'id2', 'name2']
	 * @param STRING 			$y_varname				as 'frm[test][]'
	 * @param ENUM				$y_draw 				list | checkboxes
	 * @param YES/NO 			$y_sync_values			If Yes, sync select similar values used (curently works only for checkboxes)
	 * @param INTEGER			$y_dimensions			dimensions in pixels (width or width / (list) height for '#JS-UI#' or '#JS-UI-FILTER#')
	 * @param CODE				$y_custom_js			custom js code (Ex: submit on change)
	 * @param CSS/#JS-UI#		$y_extrastyle			Extra CSS Style | 'class:a-css-class' | '#JS-UI#' or '#JS-UI-FILTER#'
	 * @param INTEGER 			$y_msize 				Multi List Size (if applicable) ; Default is 8 ; accept values between 2 and 32
	 *
	 * @return STRING 									[HTML Code]
	 */
	public static function html_select_list_multi($y_id, $y_selected_value, $y_mode, $_yarr_data, $y_varname='', $y_draw='list', $y_sync_values='no', $y_dimensions='300/0', $y_custom_js='', $y_extrastyle='#JS-UI-FILTER#', $y_msize=8) {

		//-- fix associative array
		$arr_type = \Smart::array_type_test($_yarr_data);
		if($arr_type === 2) { // associative array detected
			$arr_save = (array) $_yarr_data;
			$_yarr_data = array();
			foreach((array)$arr_save as $key => $val) {
				$_yarr_data[] = (string) $key;
				$_yarr_data[] = (string) $val;
			} //end foreach
			$arr_save = array();
		} //end if
		//--

		//-- fix (if only one element show single list, will not apply if checkboxes ...)
		$y_msize = (int) $y_msize;
		if($y_msize < 2) {
			$y_msize = 2;
		} elseif($y_msize > 32) {
			$y_msize = 32;
		} //end if else
		if(\Smart::array_size($_yarr_data) > 2) { // to be multi list must have at least 2 values, else make non-sense
			$use_multi_list_ok = true;
			$use_multi_list_htm = 'multiple size="'.(int)$y_msize.'"';
		} else {
			$use_multi_list_ok = false;
			$use_multi_list_htm = 'size="1"';
		} //end if else
		//--

		//--
		$tmp_dimens = (array) \explode('/', (string)\trim((string)$y_dimensions));
		//--
		$the_width = 0;
		if(\array_key_exists(0, $tmp_dimens)) {
			$the_width = (int) isset($tmp_dimens[0]) ? $tmp_dimens[0] : 0;
		} //end if
		$the_height = 0;
		if(\array_key_exists(1, $tmp_dimens)) {
			$the_height = (int) isset($tmp_dimens[1]) ? $tmp_dimens[1] : 0;
		} //end if
		//--
		if($the_width < 0) {
			$the_width = 0;
		} //end if
		if($the_width > 0) {
			if($the_width < 50) {
				$the_width = 50;
			} elseif($the_width > 1200) {
				$the_width = 1200;
			} //end if
		} //end if
		//--
		if($the_height < 0) {
			$the_height = 0;
		} //end if
		//--

		//--
		$y_varname = (string) \trim((string)$y_varname);
		$y_custom_js = (string) \trim((string)$y_custom_js);
		//--

		//--
		$element_id = (string) \Smart::escape_html((string)\Smart::create_htmid((string)\trim((string)$y_id)));
		//--

		//--
		$js = '';
		$css_class = '';
		//--
		if(((string)$element_id != '') && (((string)$y_extrastyle == '#JS-UI#') || ((string)$y_extrastyle == '#JS-UI-FILTER#'))) {
			//--
			$use_blank_value = 'no';
			//--
			$tmp_extra_style = (string) $y_extrastyle;
			$y_extrastyle = ''; // reset
			//--
			if((string)$y_mode == 'form') {
				//--
				if($the_width <= 0) {
					$the_width = 150;
				} //end if
				if($the_height > 0) {
					if($the_height < 50) {
						$the_height = 50;
					} //end if
					if($the_height > 200) {
						$the_height = 200;
					} //end if
				} else {
					$the_height = 90; // default (sync with jQuery Chosen Multi default)
				} //end if else
				//--
				if((string)$tmp_extra_style == '#JS-UI-FILTER#') {
					$have_filter = true;
					$the_width += 25;
				} else {
					$have_filter = false;
				} //end if else
				//--
				if($use_multi_list_ok === false) {
					$use_blank_value = 'yes';
					$have_filter = false; // if multi will be enforced to single because of just 2 rows or less, disable filter !
				} //end if
				//--
				$js = (string) \SmartMarkersTemplating::render_file_template(
					'modules/mod-auth-admins/views/js/listselect/templates/ui-list-multi.inc.htm',
					[
						'LANG' => (string) \SmartTextTranslations::getLanguage(),
						'ID' => (string) $element_id,
						'WIDTH' => (int) $the_width,
						'HEIGHT' => (int) $the_height,
						'IS-MULTI' => (bool) $use_multi_list_ok,
						'HAVE-FILTER' => (bool) $have_filter
					],
					'yes' // export to cache
				);
				//--
			} //end if
			//--
		} else {
			//--
			$use_blank_value = 'no';
			if($use_multi_list_ok === false) {
				$use_blank_value = 'yes';
			} //end if
			//--
			if((string)$y_mode == 'form') {
				$css_class = 'class="ux-field';
				if((string)$y_extrastyle != '') {
					$y_extrastyle = (string) \trim((string)$y_extrastyle);
					if(\stripos($y_extrastyle, 'class:') === 0) {
						$y_extrastyle = (string) \trim((string)\substr($y_extrastyle, (int)\strlen('class:')));
						if((string)$y_extrastyle != '') {
							$css_class .= ' '.\Smart::escape_html((string)$y_extrastyle);
						} //end if
						$y_extrastyle = '';
					} //end if
				} //end if else
				$css_class .= '"';
			} //end if
			//--
		} //end if else
		//--

		//--
		$out = '';
		//--
		if((string)$y_mode == 'form') {
			//--
			if((string)$y_draw == 'checkboxes') { // checkboxes
				//--
				$out .= '<input type="hidden" name="'.\Smart::escape_html((string)$y_varname).'" value="">'."\n"; // we need a hidden value
				//--
			} else { // list
				//--
				$out .= '<select '.($y_varname ? 'name="'.\Smart::escape_html((string)$y_varname).'" ' : '').($element_id ? 'id="'.\Smart::escape_html((string)$element_id).'" ' : '').$css_class;
				//--
				$style = [];
				if((int)$the_width > 0) {
					$style[] = 'width:'.(int)$the_width.'px;';
				} //end if
				$y_extrastyle = (string) \trim((string)$y_extrastyle);
				if((string)$y_extrastyle != '') {
					$style[] = (string) \Smart::escape_html((string)$y_extrastyle);
				} //end if
				//--
				if(\Smart::array_size($style) > 0) {
					$out .= ' style="'.\implode(' ', $style).'"';
				} //end if
				//--
				if((string)$y_custom_js != '') {
					$out .= ' '.$y_custom_js;
				} //end if
				//--
				$out .= ' '.$use_multi_list_htm.'>'."\n";
				//--
				if((string)$use_blank_value == 'yes') {
					$out .= '<option value="">&nbsp;</option>'."\n"; // we need a blank value to unselect
				} //end if
				//--
			} //end if else
			//--
		} //end if
		//--
		for($i=0; $i<\Smart::array_size($_yarr_data); $i++) {
			//--
			$i_key = $i;
			$i_val = $i+1;
			$i=$i+1;
			//--
			if((string)$y_mode == 'form') {
				//--
				$tmp_el_id = 'SmartFrameworkComponents_MultiSelect_ID__'.\sha1((string)$y_varname.$_yarr_data[$i_key]);
				//--
				$tmp_sel = '';
				$tmp_checked = '';
				//--
				if(\is_array($y_selected_value)) {
					//--
					if(\in_array($_yarr_data[$i_key], $y_selected_value)) {
						//--
						$tmp_sel = ' selected';
						$tmp_checked = ' checked';
						//--
					} //end if
					//--
				} else {
					//--
					if(\SmartUnicode::str_icontains($y_selected_value, '<'.$_yarr_data[$i_key].'>')) { // multiple categs as <id1>,<id2>
						//--
						$tmp_sel = ' selected';
						$tmp_checked = ' checked';
						//--
					} //end if
					//--
				} //end if
				//--
				if((string)$y_draw == 'checkboxes') { // checkboxes
					//--
					if((string)$y_sync_values == 'yes') {
						$tmp_onclick = ' onClick="try { smartJ$Browser.CheckAllCheckBoxes(this.form.name, \''.\Smart::escape_html((string)\Smart::escape_js((string)$tmp_el_id)).'\', this.checked); } catch(err){}"';
					} else {
						$tmp_onclick = '';
					} //end if else
					//--
					$out .= '<input type="checkbox" name="'.\Smart::escape_html((string)$y_varname).'" id="'.\Smart::escape_html((string)$tmp_el_id).'" value="'.\SmartMarkersTemplating::prepare_nosyntax_html_template(\Smart::escape_html((string)$_yarr_data[$i_key])).'"'.$tmp_checked.$tmp_onclick.'>';
					$out .= ' &nbsp; '.\SmartMarkersTemplating::prepare_nosyntax_html_template(\Smart::escape_html((string)$_yarr_data[$i_val])).'<br>';
					//--
				} else { // list
					//--
					if(\strpos((string)$_yarr_data[$i_key], '#OPTGROUP#') === 0) {
						$out .= '<optgroup label="'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val])).'">'."\n"; // the optgroup
					} else {
						$out .= '<option value="'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_key])).'"'.$tmp_sel.'>&nbsp;'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val])).'</option>'."\n";
					} //end if else
					//--
				} //end if else
				//--
			} else {
				//--
				if(\is_array($y_selected_value)) {
					//--
					if(\in_array($_yarr_data[$i_key], $y_selected_value)) {
						//--
						$out .= '&middot;&nbsp;'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val])).'<br>'."\n";
						//--
					} //end if
					//--
				} else {
					//--
					if(\SmartUnicode::str_icontains($y_selected_value, '<'.$_yarr_data[$i_key].'>')) {
						//-- multiple categs as <id1>,<id2>
						$out .= '&middot;&nbsp;'.\SmartMarkersTemplating::prepare_nosyntax_html_template((string)\Smart::escape_html((string)$_yarr_data[$i_val])).'<br>'."\n";
						//--
					} // end if
					//--
				} //end if else
				//--
			} //end if else
			//--
		} //end for
		//--
		if((string)$y_mode == 'form') {
			//--
			if((string)$y_draw == 'checkboxes') { // checkboxes
				$out .= '<br>'."\n";
			} else { // list
				$out .= '</select>'."\n";
				$out .= $js."\n";
			} //end if else
			//--
		} //end if
		//--

		//--
		return (string) $out;
		//--

	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init a JS-UI Tabs Element
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_uitabs_init($y_id_of_tabs, $y_selected=0, $y_prevent_reload=true) {
		//--
		$y_selected = \Smart::format_number_int($y_selected, '+');
		//--
		if($y_prevent_reload === true) {
			$prevreload = 'true';
		} else {
			$prevreload = 'false';
		} //end if else
		//--
		return 'try { smartJ$UI.TabsInit(\''.\Smart::escape_js((string)$y_id_of_tabs).'\', '.$y_selected.', '.$prevreload.'); } catch(e) { console.warn(\'Failed to initialize JS-UI Tabs: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Activate/Deactivate JS-UI Tabs Element
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_uitabs_activate($y_id_of_tabs, $y_activate) {
		//--
		if($y_activate === false) {
			$activate = 'false';
		} else {
			$activate = 'true';
		} //end if else
		//--
		return 'try { smartJ$UI.TabsActivate(\''.\Smart::escape_js((string)$y_id_of_tabs).'\', '.$activate.'); } catch(e) { console.log(\'Failed to activate JS-UI Tabs: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init an Input Field with AutoComplete Single
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_init_select_autocomplete_single($y_element_id, $y_script, $y_term_var, $y_min_len=1, $y_js_evcode='') {
		//--
		$y_min_len = \Smart::format_number_int($y_min_len, '+');
		if($y_min_len < 1) {
			$y_min_len = 1;
		} elseif($y_min_len > 255) {
			$y_min_len = 255;
		} //end if
		//--
		$y_js_evcode = (string) \trim((string)$y_js_evcode);
		//--
		return 'try { smartJ$UI.AutoCompleteField(\'single\', \''.\Smart::escape_js((string)$y_element_id).'\', \''.\Smart::escape_js((string)$y_script).'\', \''.\Smart::escape_js((string)$y_term_var).'\', '.(int)$y_min_len.', \''.\Smart::escape_js((string)$y_js_evcode).'\'); } catch(e) { console.log(\'Failed to initialize JS-UI AutoComplete-Single: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns the JS Code to Init an Input Field with AutoComplete Multi
	 * Must be enclosed in a <script>...</script> html tag or can be used for a JS action (ex: onClick="...")
	 *
	 * @access 		private
	 * @internal
	 *
	 */
	public static function js_code_init_select_autocomplete_multi($y_element_id, $y_script, $y_term_var, $y_min_len=1, $y_js_evcode='') {
		//--
		$y_min_len = \Smart::format_number_int($y_min_len, '+');
		if($y_min_len < 1) {
			$y_min_len = 1;
		} elseif($y_min_len > 255) {
			$y_min_len = 255;
		} //end if
		//--
		$y_js_evcode = (string) \trim((string)$y_js_evcode);
		//--
		return 'try { smartJ$UI.AutoCompleteField(\'multilist\', \''.\Smart::escape_js((string)$y_element_id).'\', \''.\Smart::escape_js((string)$y_script).'\', \''.\Smart::escape_js((string)$y_term_var).'\', '.(int)$y_min_len.', \''.\Smart::escape_js((string)$y_js_evcode).'\'); } catch(e) { console.log(\'Failed to initialize JS-UI AutoComplete-Multi: \' + e); }';
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draws a HTML JS-UI Date Selector Field
	 *
	 * @param STRING 	$y_id					[HTML page ID for field (unique) ; used foor JavaScript]
	 * @param STRING 	$y_var					[HTML Variable Name or empty if no necessary]
	 * @param DATE 		$yvalue					[DATE, empty or formated as YYYY-MM-DD]
	 * @param STRING 	$y_text_select			[The text as title: 'Select Date']
	 * @param JS-Date 	$yjs_mindate			[JS Expression, Min Date] :: new Date(1937, 1 - 1, 1) or '-1y -1m -1d'
	 * @param JS-Date 	$yjs_maxdate			[JS Expression, Max Date] :: new Date(2037, 12 - 1, 31) or '1y 1m 1d'
	 * @param ARRAY 	$y_extra_options		[Options Array[width, ...] for for datePicker]
	 * @param JS-Code 	$y_js_evcode			[JS Code to execute on Select(date)]
	 *
	 * @return STRING 							[HTML Code]
	 */
	public static function html_js_date_field($y_id, $y_var, $yvalue, $y_text_select='', $yjs_mindate='', $yjs_maxdate='', array $y_extra_options=[], $y_js_evcode='') {
		//-- v.20200605
		if((string)$yvalue != '') {
			$yvalue = \date('Y-m-d', @\strtotime($yvalue)); // enforce this date format for internals and be sure is valid
		} //end if
		//--
		$y_js_evcode = (string) \trim((string)$y_js_evcode);
		//--
		if((int)\Smart::get_from_config('regional.calendar-week-start') == 1) {
			$the_first_day = 1; // Calendar Start on Monday
		} else {
			$the_first_day = 0; // Calendar Start on Sunday
		} //end if else
		//--
		if(!\is_array($y_extra_options)) {
			$y_extra_options = array();
		} //end if
		//--
		if((!\array_key_exists('format', $y_extra_options)) OR ((string)$y_extra_options['format'] == '')) {
			$the_altdate_format = (string) \SmartTextTranslations::getDateFormatForJs((string)\Smart::get_from_config('regional.calendar-date-format-client'));
		} else {
			$the_altdate_format = (string) \SmartTextTranslations::getDateFormatForJs((string)$y_extra_options['format']);
		} //end if else
		//--
		if((!\array_key_exists('width', $y_extra_options)) OR ((string)$y_extra_options['width'] == '')) {
			$the_option_size = '85';
		} else {
			$the_option_size = (string) $y_extra_options['width'];
		} //end if
		$the_option_size = (float) $the_option_size;
		if($the_option_size >= 1) {
			$the_option_size = ' width:'.((int)$the_option_size).'px;';
		} elseif($the_option_size > 0) {
			$the_option_size = ' width:'.($the_option_size * 100).'%;';
		} else {
			$the_option_size = '';
		} //end if else
		//--
		if((string)$yjs_mindate == '') {
			$yjs_mindate = 'null';
		} //end if
		if((string)$yjs_maxdate == '') {
			$yjs_maxdate = 'null';
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-auth-admins/views/js/datepicker/templates/ui-picker-date.inc.htm',
			[
				'LANG' 				=> (string) \SmartTextTranslations::getLanguage(),
				'THE-ID' 			=> (string) $y_id,
				'THE-VAR' 			=> (string) $y_var,
				'THE-VALUE' 		=> (string) $yvalue,
				'TEXT-SELECT' 		=> (string) $y_text_select,
				'ALT-DATE-FORMAT' 	=> (string) $the_altdate_format,
				'STYLE-SIZE' 		=> (string) $the_option_size,
				'FDOW' 				=> (int)    $the_first_day, // of week
				'DATE-MIN' 			=> (string) $yjs_mindate,
				'DATE-MAX' 			=> (string) $yjs_maxdate,
				'EVAL-JS' 			=> (string) $y_js_evcode
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draws a HTML JS-UI Time Selector Field
	 *
	 * @param STRING 	$y_id					[HTML page ID for field (unique) ; used foor JavaScript]
	 * @param STRING 	$y_var					[HTML Variable Name]
	 * @param HH:ii 	$yvalue					[TIME, pre-definned value, formated as 24h HH:ii]
	 * @param STRING 	$y_text_select			[The text for 'Select Time']
	 * @param 0..22 	$y_h_st					[Starting Time]
	 * @param 1..23 	$y_h_end				[Ending Time]
	 * @param 0..58 	$y_i_st					[Starting Minute]
	 * @param 1..59 	$y_i_end				[Ending Minute]
	 * @param 1..30 	$y_i_step				[Step of Minutes]
	 * @param INTEGER 	$y_rows 				[Default is 2]
	 * @param JS-Code 	$y_extra_options		[Options Array[width, ...] for timePicker]
	 * @param JS-Code 	$y_js_evcode			[JS Code to execute on Select(time)]
	 *
	 * @return STRING 							[HTML Code]
	 */
	public static function html_js_time_field($y_id, $y_var, $yvalue, $y_text_select='', $y_h_st='0', $y_h_end='23', $y_i_st='0', $y_i_end='55', $y_i_step='5', $y_rows='2', array $y_extra_options=[], $y_js_evcode='') {
		//-- v.20200605
		if((string)$yvalue != '') {
			$yvalue = \date('H:i', @\strtotime(date('Y-m-d').' '.$yvalue)); // enforce this time format for internals and be sure is valid
		} //end if
		//--
		$y_js_evcode = (string) \trim((string)$y_js_evcode);
		//--
		$prep_hstart = \Smart::format_number_int($y_h_st, '+');
		$prep_hend = \Smart::format_number_int($y_h_end, '+');
		$prep_istart = \Smart::format_number_int($y_i_st, '+');
		$prep_iend = \Smart::format_number_int($y_i_end, '+');
		$prep_iinterv = \Smart::format_number_int($y_i_step, '+');
		$prep_rows = \Smart::format_number_int($y_rows, '+');
		//--
		if(!\is_array($y_extra_options)) {
			$y_extra_options = array();
		} //end if
		if((!\array_key_exists('width', $y_extra_options)) OR ((string)$y_extra_options['width'] == '')) {
			$the_option_size = '50';
		} else {
			$the_option_size = (string) $y_extra_options['width'];
		} //end if
		$the_option_size = (float) $the_option_size;
		if($the_option_size >= 1) {
			$the_option_size = ' width:'.((int)$the_option_size).'px;';
		} elseif($the_option_size > 0) {
			$the_option_size = ' width:'.($the_option_size * 100).'%;';
		} else {
			$the_option_size = '';
		} //end if else
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-auth-admins/views/js/timepicker/templates/ui-picker-time.inc.htm',
			[
				'LANG' 			=> (string) \SmartTextTranslations::getLanguage(),
				'THE-ID' 		=> (string) $y_id,
				'THE-VAR' 		=> (string) $y_var,
				'THE-VALUE' 	=> (string) $yvalue,
				'TEXT-SELECT' 	=> (string) $y_text_select,
				'STYLE-SIZE' 	=> (string) $the_option_size,
				'H-START' 		=> (int)    $prep_hstart,
				'H-END' 		=> (int)    $prep_hend,
				'MIN-START'		=> (int)    $prep_istart,
				'MIN-END' 		=> (int)    $prep_iend,
				'MIN-INTERVAL' 	=> (int)    $prep_iinterv,
				'DISPLAY-ROWS' 	=> (int)    $prep_rows,
				'EVAL-JS' 		=> (string) $y_js_evcode
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Outputs the HTML Code to init the HTML (wysiwyg) Editor
	 *
	 * @param $y_filebrowser_link 	STRING 		URL to Image Browser (Example: script.php?op=image-gallery&type=images)
	 * @param $y_styles 			ENUM 		Can be '' or 'a/path/to/styles.css'
	 * @param $y_use_absolute_url 	BOOL 		If TRUE will use full URL prefix to load CSS and Javascripts ; Default is FALSE
	 *
	 * @return STRING							[HTML Code]
	 */
	public static function html_jsload_htmlarea($y_filebrowser_link='', $y_stylesheet='', $y_use_absolute_url=false) {
		//--
		if($y_use_absolute_url !== true) {
			$the_abs_url = '';
		} else {
			$the_abs_url = (string) \SmartUtils::get_server_current_url();
		} //end if else
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-auth-admins/views/js/html-editor/templates/html-editor-init.inc.htm',
			[
				'LANG' 						=> (string) \SmartTextTranslations::getLanguage(),
				'HTMED-PREFIX-URL' 			=> (string) $the_abs_url,
				'STYLESHEET' 				=> (string) $y_stylesheet,
				'FILE-BROWSER-CALLBACK-URL' => (string) $y_filebrowser_link
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Draw a TextArea with a built-in javascript HTML (wysiwyg) Editor
	 *
	 * @param STRING $yid					[Unique HTML Page Element ID]
	 * @param STRING $yvarname				[HTML Form Variable Name]
	 * @param STRING $yvalue				[HTML Data]
	 * @param INTEGER+ $ywidth				[Area Width: (Example) 720px or 75%]
	 * @param INTEGER+ $yheight				[Area Height (Example) 480px or 50%]
	 * @param BOOLEAN $y_allow_scripts		[Allow JavaScripts]
	 * @param BOOLEAN $y_allow_script_src	[Allow JavaScript SRC attribute]
	 * @param MIXED $y_cleaner_deftags 		['' or array of HTML Tags to be allowed / dissalowed by the cleaner ... see HTML Cleaner Documentation]
	 * @param ENUM $y_cleaner_mode 			[HTML Cleaner mode for defined tags: ALLOW / DISALLOW]
	 * @param STRING $y_toolbar_ctrls		[Toolbar Controls: ... see CLEditor Documentation]
	 *
	 * @return STRING						[HTML Code]
	 *
	 */
	public static function html_js_htmlarea($yid, $yvarname, $yvalue='', $ywidth='720px', $yheight='480px', $y_allow_scripts=false, $y_allow_script_src=false, $y_cleaner_deftags='', $y_cleaner_mode='', $y_toolbar_ctrls='') {
		//--
		if((string)$y_cleaner_mode != '') {
			if((string)$y_cleaner_mode !== 'DISALLOW') {
				$y_cleaner_mode = 'ALLOW';
			} //end if
		} //end if
		//--
		return (string) \SmartMarkersTemplating::render_file_template(
			'modules/mod-auth-admins/views/js/html-editor/templates/html-editor-draw.inc.htm',
			[
				'TXT-AREA-ID' 					=> (string) $yid, // HTML or JS ID
				'TXT-AREA-VAR-NAME' 			=> (string) $yvarname, // HTML variable name
				'TXT-AREA-WIDTH' 				=> (string) $ywidth, // 100px or 100%
				'TXT-AREA-HEIGHT' 				=> (string) $yheight, // 100px or 100%
				'TXT-AREA-CONTENT' 				=> (string) $yvalue,
				'TXT-AREA-ALLOW-SCRIPTS' 		=> (bool)   $y_allow_scripts, // boolean
				'TXT-AREA-ALLOW-SCRIPT-SRC' 	=> (bool)   $y_allow_script_src, // boolean
				'CLEANER-REMOVE-TAGS' 			=> (string) \Smart::json_encode($y_cleaner_deftags), // mixed, will be json encoded in tpl
				'CLEANER-MODE-TAGS' 			=> (string) $y_cleaner_mode,
				'TXT-AREA-TOOLBAR' 				=> (string) $y_toolbar_ctrls
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns HTML / JS code for CallBack Mapping for HTML (wysiwyg) Editor - FileBrowser Integration
	 *
	 * @param STRING $yurl					The Callback URL
	 * @param BOOLEAN $is_popup 			Set to True if Popup (incl. Modal)
	 *
	 * @return STRING						[JS Code]
	 */
	public static function html_js_htmlarea_fm_callback($yurl, $is_popup=false) {
		//--
		return (string) \str_replace(["\r\n", "\r", "\n", "\t"], [' ', ' ', ' ', ' '], (string)\SmartMarkersTemplating::render_file_template(
			'modules/mod-auth-admins/views/js/html-editor/templates/html-editor-fm-callback.inc.js',
			[
				'IS_POPUP' 	=> (bool)   $is_popup,
				'URL' 		=> (string) $yurl
			],
			'yes' // export to cache
		));
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================

// end of php code
