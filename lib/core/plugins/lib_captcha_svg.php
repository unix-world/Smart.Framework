<?php
// [LIB - Smart.Framework / Plugins / Captcha SVG Image]
// (c) 2006-present unix-world.org - all rights reserved
// r.8.7 / smart.framework.v.8.7

//----------------------------------------------------- PREVENT SEPARATE EXECUTION WITH VERSION CHECK
if((!defined('SMART_FRAMEWORK_VERSION')) || ((string)SMART_FRAMEWORK_VERSION != 'smart.framework.v.8.7')) {
	@http_response_code(500);
	die('Invalid Framework Version in PHP Script: '.@basename(__FILE__).' ...');
} //end if
//-----------------------------------------------------


//======================================================
// Captcha SVG (Vector) Image
// DEPENDS:
//	* Smart::
//	* SmartMarkersTemplating::
// REQUIRED TEMPLATES:
//	* captcha-plugin-svg-image.inc.htm
//======================================================


//==================================================================
/*
 * SVG Captcha Plugin
 * Generate SVG (vector graphics) captchas.
 * This version contains many changes and optimizations from the original work.
 * @author unixman
 * @copyright (c) 2018-present unix-world.org
 * @license: BSD
 *
 * Original work: SVG Captcha, https://github.com/NikolaiT/SVG-Captcha # head.20191215
 * @copyright (c) 2013, Nikolai Tschacher
 * @license https://github.com/NikolaiT/SVG-Captcha/blob/master/LICENSE # GNU Public License
 *
 * It will draw the captcha text with help of svg shapes emulated font, that consits of Bezier curvatures and straight lines.
 * Then it will apply some mathematical functions on the raw curvature data such that parsing the SVG files and cracking the unerlying pattern becomes difficult (even hard) enough
 * The points that constitute the curves (that constitute the Glyphs) should be sheared/rotated/translated with affine transformations and random parameters.
 * This captcha will use a single path element to draw all glyphs. Thus it becomes almost impossible to differentiate between single glyphs, because all glyphs are drawn with one 'stroke'.
 *
 * The SVG path specification in short:
 * Commands: M = moveto, L = lineto, C = curveto,  Z = closepath
 * Relative versions of commands: Uppercase means absoute, lowercase means relative. All coordinate values are relative to the point at the start of the command.
 * Alternate versions of lineto are available in case of horizontal and vertical lines:
 *    H/h draws a horizontal line from the current point (cpx, cpy) to (x, cpy). Multiple x values can be provided (which doesen't make sense, but it might be a neat idea!)
 *    V/v draws a vertical line fro the current point (cpx, cpy) to (cpx, y). Multiple y values can be provided.
 * Alternate versions of curve are available where some control points on the current segment can be determined from control points of the previous segment:
 *
 * SVG Commands:
 * moveto: M/m, establishes a new current point. A path data segment must begin with a moveto. Subsequent moveto commands represent the start of a new subpath.
 * closepath: Z/z, ends the current subpath and causes an automatic straight line to be drawn from the current point to the initial point of the current subpath.
 * At the end of the command, the new current point is set to the initial point of the current subpath. Z and z have identical effect.
 * lineto: L/l, draws a straight line from the current point to a new point, that becomes the new current point.
 * cubic curveto: C/c, parameters: (x1, y1, x2, y2, x, y)+. Draws a cubic Bezier curve from the current point to x,y using two control points.
 * smooth cubic curveto: S/s, parameters: (x2, y2, x, y)+. Draws a cubic Bezier curve from the current point to x,y. The first control point is assumed to be the reflection
 * of the second control point on the previous command relative to the current point.
 * quadratic curveto: Q/q, parameters: (x1, y1, x, y)+. Draws a quadratic Bezier curve from the current point to (x, y) using (x1, y1) as the control point.
 * smooth quadratic curveto: T/t, parameters: (x, y)+. Draws a quadratic Bezier curve from the current point to (x,y). The control point is assumed to be the reflection of
 * the control point of the previous command relative to the current point.
*/
//==================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class: SVG Captcha - A SVG Image Plugin for SmartCaptcha
 * Create a Form Captcha Validation Vector Image (SVG)
 *
 * @access 		PUBLIC
 * @depends 	classes: Smart, SmartMarkersTemplating, SmartSvgCaptchaPoint ; constants: SMART_FRAMEWORK_SECURITY_KEY
 * @version 	v.20250714
 * @package 	development:Captcha
 */
final class SmartSVGCaptcha {

	//================================================================
	//--
	private $time = 0;
	private $alphabet = [];
	private $numchars = 0;
	private $width = 0;
	private $height = 0;
	//--
	/**
	 * Multidimensional array holding the difficulty settings
	 * The boolean value with key 'apply' indicates whether to use/apply the function.
	 * p indicates the probability 1/p of usages for the function.
	 * ARRAY
	 */
	private $dsettings = [
		// h: This coefficient multiplied with the previous glyph's width determines the minimal advance of the current glyph.
		// v: The fraction of the maximally allowed vertical displacement based on the current glyph height.
		// mh: The minimal vertical offset expressed as the divisor of the current glyph height.
		'stroke_border_color' 		=> '#CCCCCC', 	// element (svg) border color ; default is #CCCCCC
		'stroke_width' 				=> 2, 			// path width 1..5 ; default is 2
		'stroke_opacity' 			=> 0.9, 		// 0.5 .. 1.0 ; default is 0.9
		'stroke_fill' 				=> '', 			// default is empty string ; can be a hexa color as #777777
		'stroke_color' 				=> '#999999', 	// stroke hexa color ; default is #999999
		'stroke_bg_color' 			=> '#FFFFFF', 	// stroke background color ; default is #FFFFFF
		'stroke_bg_pattern_color' 	=> '#BBBBBB', 	// stroke background pattern color ; default is #BBBBBB
		'glyph_offsetting' 			=> [ 'apply' => true,  'h' => 1, 				'v' => 0.5, 'mh' => 8 ], // Needs to be enabled by default
		'glyph_fragments' 			=> [ 'apply' => false, 'r_num_frag' => null, 	'frag_factor' => 2 ],
		'transformations' 			=> [ 'apply' => false, 'rotate' => false, 		'skew' => false, 'scale' => false, 'shear' => false, 'shear_factor' => 0.77, 'translate' => false ],
		'approx_shapes' 			=> [ 'apply' => false, 'p' => 3, 				'r_al_num_lines' => [0,1] ],
		'change_degree' 			=> [ 'apply' => false, 'p' => 5 ],
		'split_curve'				=> [ 'apply' => false, 'p' => 5 ],
		'shapeify' 					=> [ 'apply' => false, 'r_num_shapes' => [1,2], 'r_num_gp' => [0,1] ]
	];
	//--
	/**
	 * The answer to the generated captcha.
	 * ARRAY
	 */
	private $captcha_answer = [];
	//================================================================


	//================================================================
	/**
	 * Class constructor
	 *
	 * @param INT $numchars The number of glyphs (characters) the captcha will contain
	 * @param INT $width The width of the captcha
	 * @param INT $height The height of the captcha
	 * @param MIXED $difficulty The difficulty of the captcha to generate ; Valid values are: -1 = very easy ; 0 = easy (default) ; 1 = moderate ; 2 = hard ; 3 = very hard ; alternate can be an array of settings
	 */
	public function __construct(?int $numchars, ?int $width, ?int $height, $difficulty=null) {
		//--
		$this->time = (float) microtime(true);
		//--
		$this->initAlphabetGlyphs();
		//--
		$this->numchars = (int) $numchars;
		if((int)$this->numchars < 3) {
			$this->numchars = 3;
		} elseif((int)$this->numchars > 7) {
			$this->numchars = 7;
		} //end if
		//--
		if((int)$this->numchars > (int)Smart::array_size($this->alphabet)) { // safety check
			$this->numchars = (int) Smart::array_size($this->alphabet);
		} //end if
		//--
		$this->width  = (int) $width;
		$this->height = (int) $height;
		//--
		// Set the parameters for the algorithms according to the user supplied difficulty.
		//--
		$this->dsettings['stroke_width'] 					= 2;
		$this->dsettings['stroke_opacity'] 					= 0.55;
		$this->dsettings['glyph_offsetting']['apply'] 		= true;
		$this->dsettings['glyph_offsetting']['h'] 			= 1.32;
		$this->dsettings['glyph_offsetting']['v'] 			= 0.5;
		$this->dsettings['glyph_offsetting']['mh'] 			= 7;
		$this->dsettings['transformations']['apply'] 		= true;
		$this->dsettings['transformations']['rotate'] 		= true;
		$this->dsettings['transformations']['skew'] 		= true;
		$this->dsettings['transformations']['scale'] 		= true;
		$this->dsettings['transformations']['translate'] 	= true;
		$this->dsettings['shapeify']['apply'] 				= true;
		$this->dsettings['shapeify']['r_num_shapes'] 		= [1,2];
		$this->dsettings['shapeify']['r_num_gp'] 			= [0,1];
		$this->dsettings['change_degree']['apply'] 			= true;
		$this->dsettings['change_degree']['p'] 				= 2;
		$this->dsettings['split_curve']['apply'] 			= true;
		//--
		if(Smart::is_nscalar($difficulty)) {
			if((int)$difficulty < 0) { // very easy: -1
				$this->dsettings['stroke_opacity'] 						= 0.57;
				$this->dsettings['stroke_color'] 						= '#888888';
				$this->dsettings['glyph_offsetting']['apply'] 			= true;
				$this->dsettings['transformations']['apply'] 			= true;
				$this->dsettings['transformations']['rotate'] 			= false;
				$this->dsettings['transformations']['skew'] 			= false;
				$this->dsettings['transformations']['scale'] 			= false;
				$this->dsettings['transformations']['shear'] 			= true;
				$this->dsettings['transformations']['shear_factor'] 	= 0.28;
				$this->dsettings['transformations']['translate'] 		= true;
				$this->dsettings['shapeify']['apply'] 					= false;
				$this->dsettings['change_degree']['apply'] 				= false;
				$this->dsettings['split_curve']['apply'] 				= false;
			} elseif((int)$difficulty == 0) { // easy (default)
				$this->dsettings['stroke_opacity'] 						= 0.55;
				$this->dsettings['stroke_color'] 						= '#888888';
				$this->dsettings['glyph_offsetting']['apply'] 			= true;
				$this->dsettings['transformations']['apply'] 			= true;
				$this->dsettings['transformations']['rotate'] 			= true;
				$this->dsettings['transformations']['skew'] 			= false;
				$this->dsettings['transformations']['scale'] 			= false;
				$this->dsettings['transformations']['shear'] 			= true;
				$this->dsettings['transformations']['shear_factor'] 	= 0.42;
				$this->dsettings['transformations']['translate'] 		= true;
				$this->dsettings['shapeify']['apply'] 					= false;
				$this->dsettings['change_degree']['apply'] 				= false;
				$this->dsettings['split_curve']['apply'] 				= true;
			} elseif((int)$difficulty == 1) { // moderate
				$this->dsettings['stroke_opacity'] 						= 0.65;
				$this->dsettings['transformations']['apply'] 			= true;
				$this->dsettings['transformations']['rotate'] 			= true;
				$this->dsettings['transformations']['skew'] 			= true;
				$this->dsettings['transformations']['scale'] 			= false;
				$this->dsettings['shapeify']['apply'] 					= true;
				$this->dsettings['shapeify']['r_num_shapes'] 			= range(0, 2);
				$this->dsettings['shapeify']['r_num_gp'] 				= range(1, 2);
				$this->dsettings['approx_shapes']['apply'] 				= true;
				$this->dsettings['approx_shapes']['p'] 					= 5;
				$this->dsettings['approx_shapes']['r_al_num_lines'] 	= range(2, 10);
				$this->dsettings['change_degree']['apply'] 				= true;
				$this->dsettings['change_degree']['p'] 					= 5;
				$this->dsettings['split_curve']['apply'] 				= true;
			} elseif((int)$difficulty == 2) { // hard
				$this->dsettings['stroke_opacity'] 						= 0.7;
				$this->dsettings['glyph_fragments']['apply'] 			= true;
				$this->dsettings['glyph_fragments']['r_num_frag'] 		= range(1, 2);
				$this->dsettings['glyph_offsetting']['apply'] 			= true;
				$this->dsettings['transformations']['apply'] 			= true;
				$this->dsettings['transformations']['rotate'] 			= true;
				$this->dsettings['transformations']['skew'] 			= true;
				$this->dsettings['transformations']['scale'] 			= false;
				$this->dsettings['shapeify']['apply'] 					= true;
				$this->dsettings['shapeify']['r_num_shapes'] 			= range(0, 2);
				$this->dsettings['shapeify']['r_num_gp'] 				= range(1, 2);
				$this->dsettings['approx_shapes']['apply'] 				= true;
				$this->dsettings['approx_shapes']['p'] 					= 5;
				$this->dsettings['approx_shapes']['r_al_num_lines'] 	= range(2, 10);
				$this->dsettings['change_degree']['apply'] 				= true;
				$this->dsettings['change_degree']['p'] 					= 5;
				$this->dsettings['split_curve']['apply'] 				= true;
			} elseif((int)$difficulty == 3) { // very hard
				$this->dsettings['stroke_width'] 						= 1;
				$this->dsettings['stroke_opacity'] 						= 0.7;
				$this->dsettings['glyph_fragments']['apply'] 			= true;
				$this->dsettings['glyph_fragments']['r_num_frag'] 		= range(1, 2);
				$this->dsettings['glyph_offsetting']['apply'] 			= true;
			//	$this->dsettings['glyph_offsetting']['h'] 				= 0.78;
				$this->dsettings['transformations']['apply'] 			= true;
				$this->dsettings['transformations']['rotate'] 			= true;
				$this->dsettings['transformations']['skew'] 			= true;
				$this->dsettings['transformations']['scale'] 			= false;
				$this->dsettings['shapeify']['apply'] 					= true;
				$this->dsettings['shapeify']['r_num_shapes'] 			= range(0, 4);
				$this->dsettings['shapeify']['r_num_gp'] 				= range(2, 4);
				$this->dsettings['approx_shapes']['apply'] 				= true;
				$this->dsettings['approx_shapes']['p'] 					= 5;
				$this->dsettings['approx_shapes']['r_al_num_lines'] 	= range(1, 5);
				$this->dsettings['change_degree']['apply'] 				= true;
				$this->dsettings['change_degree']['p'] 					= 5;
				$this->dsettings['split_curve']['apply'] 				= true;
			} //end if else
		} elseif(is_array($difficulty) AND (Smart::array_size($difficulty) > 0)) {
			foreach((array)$difficulty as $key => $val) {
				if(array_key_exists($key, $this->dsettings)) {
					$this->dsettings[$key] = $val;
				} //end if
			} //end foreach
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generate the code and the SVG Image
	 * @return STRING The Captcha SVG (Vector) Image
	 */
	public function draw_image() {
		//--
		return (string) $this->generate();
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Get the generated code
	 * Must be call only after draw_image()
	 * @return STRING The Captcha generated code
	 */
	public function get_code() {
		//--
		return (string) strtoupper((string)implode('', (array)$this->captcha_answer));
		//--
	} //END FUNCTION
	//================================================================


	//===== PRIVATES


	//================================================================
	/**
	 * This function generates a SVG d attribute of a path element. The d attribute represents the path data.
	 *
	 * The function takes an parameter $clength as input and modifies the appearance of the $clength randomly chosen
	 * glyphs of the alphabet, such that viewers can still recognize the original glyph but programs fail so (or do very bad compared to humans).
	 *
	 * The following random distortion mechanisms are the backbone of the captchs's security strength:
	 * - All glpyhs are packed into a single d attribute.
	 * - Point representation changes from absolute to relative on a random base.
	 * - Cubic Bezier curves are converted to quadratic splines and vice versa.
	 * - Bezier curves are approximated by lines. Lines are represented as bezier curves.
	 * - Parts of glyphs (That is: Some of their geometrical primitives) are copied and inserted at some random place in the canvas.
	 *   This technique spreads confusion for cracking parsers, since it becomes harder to distinguish between real glyphs and meaningless glyph fragments. Possible drawback:
	 *   Crackers have easier play to gues what glyhps are used, because more 'evidence' of glyphs is present.
	 * - All input points undergo affine transformation matrices (Rotation/Skewing/Translation/Scaling).
	 * - Random 'components', such as holes or misformations (mandelbrot shapes for instance) are randomly injected into the shape definitions.
	 * - The definition of the components (Which consists of geometrical primitives) that constitute each glyph, are arranged randomly.
	 *   More precise: The imaginal pen jumps from glyph to glyph with the Moveto (M/m) command in a unpredictable manner.
	 * - In order to make analyses as hard as possible, we need to connect each glyph in a matther that makes it unfeasible to distinguish
	 *   the glyph entities. For instance: If every glyph was drawn in a separate subpath in the d attribute, it'd be very easy to recognize the single glyphs.
	 *   Furthermore there must be some countermeasures to make out the glyphs by their coordinate values. Hence they need to overlap to a certain degree that makes it
	 *   hard to assign geometrical primitives to a certain glyph entity.
	 *
	 * Note: The majority of the above methods try to hinder cracking attempts that try to match the distorted path
	 *       elements against the original path data (Which of course are public).
	 *       This means that there remains the traditional cracking attempt: Common OCR techniques on a SVG captcha, that is converted to a bitmap format.
	 *       Hence, some more blurring techniques, especially for traditinoal attacks, are applied:
	 * - Especially to prevent OCR techniques, independent random shapes are injected into the d attribute.
	 * - Colorous background noise is not an option (That's just a css defintion in SVG).
	 * @return STRING The captcha svg output
	 */
	private function generate() {
		//-- Start by choosing $clength random glyphs from the alphabet and store them in $selected
		$selected_keys = $this->arr_rand_safe($this->alphabet, $this->numchars, false);
		foreach($selected_keys as $kk => $key) {
			$selected[$key] = $this->alphabet[$key];
		} //end foreach
		//-- Pack all shape types together for every remaining glyph. I am sure there are more elegant ways.
		foreach($selected as $key => $value) {
			$packed[$key]['width'] = $selected[$key]['width'];
			$packed[$key]['height'] = $selected[$key]['height'];
			foreach($value['glyph_data'] as $kk => $shapetype) {
				foreach($shapetype as $kk => $shape) {
					$packed[$key]['glyph_data'][] = $shape;
				} //end foreach
			} //end foreach
			$this->captcha_answer[] = $key;
		} //end foreach
		//-- First of all, the glyphs need to be scaled such that the biggest glyph becomes a fraction of the height of the overall height.
		$packed = $this->_scale_by_largest_glyph($packed);
		//-- By now, each glyph is randomly aligned but still in their predefined geometrical form. It's time to give them some new shape with affine transformations. It is imported to call this function before the glyphs become aligned, in order for the affine transformations to relate to a constant coordinate system.
		if($this->dsettings['transformations']['apply']) {
			$packed = $this->_apply_affine_transformations($packed);
		} //end if
		//-- Now every glyph has a unique size (as defined by their typeface) and they overlap all more or less if we would draw them directly. Therefore we need to align them horizontally/vertically such that the (n+1)-th glyph overlaps not more than to than half of the horizontal width of the n-th glyph. In order to do so, we need to know the widths/heights of the glyphs. It is assumed that this information is held in the alphabet array.
		if($this->dsettings['glyph_offsetting']['apply']) {
			$packed = $this->_align_randomly($packed);
		} //end if
		//-- Replicate glyph fragments and insert them at random positions
		if($this->dsettings['glyph_fragments']['apply']) {
			$packed = $this->_glyph_fragments($packed);
		} //end if
		//-- Finally, we generate a single array of shapes, and then shuffle it. Therefore we cannot longer distinguish which shape belongs to which glyph.
		foreach($packed as $char => $value) {
			foreach($value['glyph_data'] as $kk => $shape) {
				$shapearray[] = $shape;
			} //end foreach
		} //end foreach
		//-- Shuffle it!
		shuffle($shapearray);
		//-- Insert some randomly generated shapes in the shapearray
		if($this->dsettings['shapeify']['apply']) {
			$shapearray = $this->_shapeify($shapearray);
		} //end if
		//-- Here is the part where the rest of the magic happens! Let's modify the shapes. It is perfectly possible that a single shape get's downgraded and afterwards approximated with lines wich somehow neutralizes the downgrade. But this happens rarily. Any of the following methods may change the input array! So they cannot be run in a for loop, because keys will ge messed up.
		if($this->dsettings['change_degree']['apply']) { // Executes an curve downgrade/upgrade from times to times :P
			$shapearray = $this->_maybe_change_curvature_degree($shapearray);
		} //end if
		//-- Maybe split a single curve into two subcurves
		if($this->dsettings['split_curve']['apply']) {
			$shapearray = $this->_maybe_split_curve($shapearray);
		} //end if
		//-- Approximates a curve with lines on a random base or the other way around.
		if($this->dsettings['approx_shapes']['apply']) {
			$shapearray = $this->_maybe_approximate_xor_make_curvaceous($shapearray);
		} //end if
		//-- Shuffle once more
		shuffle($shapearray);
		//-- Now write the SVG file
		$path_str = '';
		$begin_path = true;
		foreach($shapearray as $key => &$shape) {
			//-- Assign 'random' float precision.
			array_map(function($p) {
				$p->x = sprintf('%.'.Smart::random_number(5, 8).'f', $p->x);
				$p->y = sprintf('%.'.Smart::random_number(4, 7).'f', $p->y);
			}, $shape);
			if($begin_path) {
				$path_str .= 'M '.$shape[0]->x.' '.$shape[0]->y.' ';
				$begin_path = false;
			} //end if
			$path_str .= $this->_shape2_cmd($shape, true, true);
		} //end foreach
		//--
		return (string) $this->_write_SVG($path_str);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * This function replaces all parameters in the SVG skeleton with the computed
	 * values and finally returns the SVG string.
	 *
	 * @param STRING $path_str The string holding the path data for the path d attribute
	 * @return STRING The svg output for the captcha image
	 */
	private function _write_SVG($path_str) {
		//--
		$path_str = (string) trim((string)$path_str);
		$key = (string) (defined('SMART_FRAMEWORK_SECURITY_KEY') ? SMART_FRAMEWORK_SECURITY_KEY : '');
		//--
		return (string) SmartMarkersTemplating::render_file_template(
			'lib/core/plugins/templates/captcha-plugin-svg-image.inc.htm',
			[
				'WIDTH' 					=> (int)    $this->width,
				'HEIGHT' 					=> (int)    $this->height,
				'ELEMENT-ID-HASH' 			=> (string) sha1('SVG-Element'.$path_str.$key.time()),
				'ELEMENT-BORDER-COLOR' 		=> (string) $this->dsettings['stroke_border_color'],
				'BACKGROUND-COLOR' 			=> (string) $this->dsettings['stroke_bg_color'],
				'PATTERN-ID-HASH' 			=> (string) sha1('SVG-Pattern'.$path_str.$key.time()),
				'PATTERN-COLOR' 			=> (string) $this->dsettings['stroke_bg_pattern_color'],
				'CAPTCHA-ID-HASH' 			=> (string) sha1('SVG-Path'.$path_str.$key.time()),
				'CAPTCHA-COLOR' 			=> (string) $this->dsettings['stroke_color'],
				'CAPTHA-STROKE-WIDTH' 		=> (int) 	$this->dsettings['stroke_width'],
				'CAPTHA-STROKE-FILL' 		=> (string) ((strpos((string)$this->dsettings['stroke_fill'], '#') === 0) ? $this->dsettings['stroke_fill'] : 'none'),
				'CAPTCHA-OPACITY' 			=> (float)  $this->dsettings['stroke_opacity'],
				'CAPTCHA-PATH' 				=> (string) $path_str,
				'EXECUTION-TIME' 			=> (float)  Smart::format_number_dec((float)(microtime(true) - (float)$this->time), 9, '.', '')
			],
			'yes' // export to cache
		);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Takes the pre-chosen glyphs as input and copies random shapes of some
	 * randomly chosen glyhs and randomly translates them and adds them to the glyhp array.
	 *
	 * @param array $glyphs The glyph array.
	 * @return array The modified glyph array.
	 */
	private function _glyph_fragments($glyphs) {
		//-- How many glyph fragments? If it is bigger than $glyph, just use: Smart::array_size($glyphs) - 1
		if(!empty($this->dsettings['glyph_fragments']['r_num_frag'])) {
			$ngf = (int) max($this->dsettings['glyph_fragments']['r_num_frag']);
			$ngf = (int) ($ngf >= Smart::array_size($glyphs) ? (Smart::array_size($glyphs) - 1) : $ngf);
		} else {
			// If no range is specified in $dsettings
			$ngf = (int) Smart::random_number(0, (int)(Smart::array_size($glyphs) - 1));
		} //end if else
		//-- Choose a random range of glyph fragments.
		$chosen_keys = $this->arr_rand_safe($glyphs, $ngf, true);
		//--
		$glyph_fragments = [];
		foreach($chosen_keys as $kk => $key) {
			//-- Get a key for the fragments
			$ukey = (string) uniqid($prefix = 'gf__');
			//-- Choose maximally half of all shapes that constitute the glyph
			$shape_keys = $this->arr_rand_safe(
				$glyphs[$key]['glyph_data'], Smart::random_number(0, Smart::floor_number(Smart::array_size($glyphs[$key]['glyph_data']) / $this->dsettings['glyph_fragments']['frag_factor']))
			);
			//-- Determine translation and rotation parameters. In which x direction should the fragment be moved (Based on the very first shape in the fragment)
			if((!empty($shape_keys)) && (Smart::array_size($shape_keys) > 0)) {
				$pos = (($rel = $glyphs[$key]['glyph_data'][$shape_keys[0]][0]->x) > $this->width / 2) ? false : true;
				$x_translate = ($pos) ? Smart::random_number((int)abs($rel), (int)$this->width) : - Smart::random_number(0, (int)abs($rel));
				$y_translate = (microtime() & 1) ? (-1 * Smart::random_number(0, Smart::floor_number($this->width / 5))) : Smart::random_number(0, Smart::floor_number($this->width / 5));
				$a = $this->_ra(0.6);
				foreach($shape_keys as $kk => $skey) {
					$copy = $this->arr_copy($glyphs[$key]['glyph_data'][$skey]);
					$this->on_points($copy, [$this, '_translate'], [$x_translate, $y_translate]);
					$this->on_points($copy, [$this, '_rotate'], [$a]);
					$glyph_fragments[$ukey]['glyph_data'][] = $copy;
				} //end foreach
			} //end if
		} //end foreach
		//--
		return (array) array_merge($glyph_fragments, $glyphs);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Insert randomly generated shapes into the shape array(mandelbrot like distortions).
	 *
	 * The idea is to replace certain basic shapes such as curves or lines with a geometrical
	 * figure that distorts the overall picture of the glyph. Such a figure could be generated randomly, the
	 * only constraints are, that the start point and end point of the replaced shape coincide with the
	 * randomly generated substitute.
	 *
	 * A second approach is to add such random shapes without replacing existing ones.
	 *
	 * This function does both of the above. The purposes for this procedure is to make
	 * OCR techniques more cumbersome.
	 *
	 * Note: Currently, only the second construct is implemented due to the likely
	 * difficulty involving the first idea.
	 *
	 *
	 * @param array $shapearray
	 * @return array The shapearray merged with randomly generated shapes.
	 */
	private function _shapeify($shapearray) {
		//--
		$random_shapes = [];
		//-- How many random shapes?
		$ns = Smart::random_number(min($this->dsettings['shapeify']['r_num_shapes']), max($this->dsettings['shapeify']['r_num_shapes']));
		//--
		foreach(range(0, $ns) as $kk => $i) {
			$random_shapes = array_merge($random_shapes, $this->_random_shape());
		} //end foreach
		//--
		return (array) array_merge($shapearray, $random_shapes);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates a randomly placed shape in the coordinate system.
	 *
	 * @return array An array of arrays constituting glyphs.
	 */
	private function _random_shape() {
		//--
		$rshapes = [];
		//-- Bounding points that constrain the maximal shape expansion
		$min = new SmartSvgCaptchaPoint(0, 0);
		$max = new SmartSvgCaptchaPoint($this->width, $this->height);
		//-- Get a start point
		$previous = $startp = new SmartSvgCaptchaPoint(Smart::random_number((int)$min->x, (int)$max->x), Smart::random_number((int)$min->y, (int)$max->y));
		//-- Of how many random geometrical primitives should our random shape consist?
		$ngp = Smart::random_number(min($this->dsettings['shapeify']['r_num_gp']), max($this->dsettings['shapeify']['r_num_gp']));
		//--
		foreach(range(0, $ngp) as $kk => $j) {
			//-- Find a random endpoint for geometrical primitves. If there are only 4 remaining shapes to add, choose a random point that is closer to the endpoint!
			$rp = new SmartSvgCaptchaPoint(Smart::random_number((int)$min->x, (int)$max->x), Smart::random_number((int)$min->y, (int)$max->y));
			if(($ngp - 4) <= $j) {
				$rp = new SmartSvgCaptchaPoint(Smart::random_number((int)$min->x, (int)$max->x), Smart::random_number((int)$min->y, (int)$max->y));
				//-- Make the component closer to the startpoint that is currently wider away. This ensures that the component switches over the iterations (most likely).
				$axis = abs($startp->x - $rp->x) > abs($startp->y - $rp->y) ? 'x' : 'y';
				if($axis === 'x') {
					$rp->x += ($startp->x > $rp->x) ? abs($startp->x - $rp->x) / 4 : abs($startp->x - $rp->x) / -4;
				} else {
					$rp->y += ($startp->y > $rp->y) ? abs($startp->y - $rp->y) / 4 : abs($startp->y - $rp->y) / -4;
				} //end if else
			} //end if
			//--
			if($j == ($ngp - 1)) { // Close the shape. With a line
				$rshapes[] = [ $previous, $startp ];
				break;
			} elseif(Smart::random_number(0, 1) == 1) { // Add a line
				$rshapes[] = [ $previous, $rp ];
			} else { // Add quadratic bezier curve
				$rshapes[] = [ $previous, new SmartSvgCaptchaPoint($previous->x, $rp->y), $rp ];
			} //end if else
			//--
			$previous = $rp;
			//--
		} //end foreach
		//--
		return (array) $rshapes;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Does not change the size/keys of the input array!
	 *
	 * Elevates maybe the curvature degree of a quadratic curve to a cubic curve.
	 *
	 * @param array $shapearray
	 * @return bool Vacously true.
	 */
	private function _maybe_change_curvature_degree($shapearray) {
		//--
		foreach($shapearray as $kk => &$shape) {
			$p = (int) $this->dsettings['change_degree']['p'];
			$do_change = (bool) ((int)Smart::random_number(0, (int)$p) == (int)$p);
			if($do_change && ((int)Smart::array_size($shape) == 3)) {
				 // We only deal with quadratic splines.
				 // Their degree is elevated to a cubic curvature.
				 // We pick '1/3rd start + 2/3rd control' and '2/3rd control + 1/3rd end', and now we have exactly the same curve as before, except represented as a cubic curve, rather than a quadratic curve.
				list($p1, $p2, $p3) = $shape;
				$shape = [
					$p1,
					new SmartSvgCaptchaPoint(1 / 3 * $p1->x + 2 / 3 * $p2->x, 1 / 3 * $p1->y + 2 / 3 * $p2->y),
					new SmartSvgCaptchaPoint(1 / 3 * $p3->x + 2 / 3 * $p2->x, 1 / 3 * $p3->y + 2 / 3 * $p2->y),
					$p3
				];
			} //end if
		} //end foreach
		//--
		return (array) $shapearray;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Split quadratic and cubic bezier curves in two components.
	 *
	 * @param array $shapearray
	 * @return array The updated shapearray.
	 */
	private function _maybe_split_curve($shapearray) {
		//-- Holding a copy preserves messing up the argument array.
		$newshapes = [];
		//--
		foreach($shapearray as $key => $shape) {
			$p = (int) $this->dsettings['split_curve']['p'];
			$do_change = (bool) ((int)Smart::random_number(0, (int)$p) == (int)$p);
			if($do_change && ((int)Smart::array_size($shape) >= 3)) {
				$left = [];
				$right = [];
				$this->_split_curve($shape, $this->_rt(), $left, $right);
				$right = (array) array_reverse($right);
				//-- Now update the shapearray accordingly: Delete the old curve, append the two new ones :P
				if(!empty($left) and !empty($right)) {
					unset($shapearray[$key]);
					$newshapes[] = $left;
					$newshapes[] = $right;
				} //end if
			} //end if
		} //end foreach
		//--
		return (array) array_merge($newshapes, $shapearray);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Approximates maybe a curve with lines or maybe converts lines to quadratic or cubic
	 * bezier splines (With a slight curvaceous shape).
	 *
	 * @param array $shapearray The array holding all shapes.
	 * @return array The udpated shapearray.
	 */
	private function _maybe_approximate_xor_make_curvaceous($shapearray) {
		//-- Holding a an array of keys to delete after the loop
		$dk = [];
		$merge = []; // Accumulating the new shapes
		//--
		foreach($shapearray as $key => $shape) {
			$p = (int) $this->dsettings['approx_shapes']['p'];
			$do_change = (bool) ((int)Smart::random_number(0, (int)$p) == (int)$p);
			if($do_change) {
				if(((Smart::array_size($shape) == 3) || (Smart::array_size($shape) == 4))) {
					$lines = $this->_approximate_bezier($shape);
					$dk[] = $key;
					$merge = (array) array_merge($merge, $lines);
				} elseif(Smart::array_size($shape) == 2) { // This is FUN: Approximate lines with curves! There are no limits for imagination
					$shapearray[$key] = $this->_approximate_line($shape);
				} //end if else
			} //end if
		} //end foreach
		//-- get rid of the duplicate shapes
		return (array) array_merge($merge, array_diff_key($shapearray, array_fill_keys($dk, 0)));
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Transforms an array of points into its according SVG path command. Assumes that the
	 * 'current point' is already existant.
	 *
	 * @param array $shape The array of points to convert.
	 * @param bool $absolute Whether the $path command is absolute or not.
	 * @param bool $explicit_moveto If we should add an explicit moveto before the command.
	 * @return string The genearetd SVG command based on the arguments.
	 */
	private function _shape2_cmd($shape, $absolute = true, $explicit_moveto = false) {
		//--
		if($explicit_moveto) {
			$prefix = 'M '.$shape[0]->x.' '.$shape[0]->y.' ';
		} else {
			$prefix = '';
		} //end if
		if(Smart::array_size($shape) == 2) { // Handle lines
			list($p1, $p2) = $shape;
			$cmd = 'L '.$p2->x.' '.$p2->y.' ';
		} elseif(Smart::array_size($shape) == 3) { // Handle quadratic bezier splines
			list($p1, $p2, $p3) = $shape;
			$cmd = 'Q '.$p2->x.' '.$p2->y.' '.$p3->x.' '.$p3->y.' ';
		} elseif(Smart::array_size($shape) == 4) { // Handle cubic bezier splines
			list($p1, $p2, $p3, $p4) = $shape;
			$cmd = 'C '.$p2->x.' '.$p2->y.' '.$p3->x.' '.$p3->y.' '.$p4->x.' '.$p4->y.' ';
		} //end if else
		if(!$cmd) {
			return false;
		} //end if
		//--
		return (string) $prefix.$cmd;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Scales all the glyphs by the glyph with the biggest height such that
	 * the lagerst glyph is 2/3 of the pictue height.
	 *
	 * @param array $glyphs All the glyphs of the shapearray.
	 */
	private function _scale_by_largest_glyph($glyphs) {
		//-- $this->width = 2 * $my*$what <=> $what = $this->width/2/$my
		$my = max(array_column($glyphs, 'height'));
		$scale_factor = ($this->height / 1.5) / $my;
		$this->on_points(
			$glyphs, [$this, '_scale'], [$scale_factor]
		);
		//-- And change their height/widths attributes manually
		foreach($glyphs as $kk => &$value) {
			$value['width'] *= $scale_factor;
			$value['height'] *= $scale_factor;
		} //end foreach
		//--
		return (array) $glyphs;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Algins the glyphs horizontally and vertically in a random way.
	 *
	 * @param array $glyphs The glyphs to algin.
	 */
	private function _align_randomly($glyphs) {
		//--
		$accumulated_hoffset = 0;
		$lastxo = 0;
		$lastyo = 0;
		$cnt = 0;
		//--
		$overlapf_h = $this->dsettings['glyph_offsetting']['h']; // Successive glyphs overlap previous glyphs at least to overlap * length of the previous glyphs.
		$overlapf_v = $this->dsettings['glyph_offsetting']['v']; // The maximal y-offset based on the current glyph height.
		foreach($glyphs as $kk => &$glyph) {
			//-- Get a random x-offset based on the width of the previous glyph divided by two.
			$accumulated_hoffset += ($cnt == 0) ? ($glyph['width'] / 2) + 5 : Smart::random_number((int)$lastxo, (int)(($glyph['width'] > $lastxo) ? $glyph['width'] : $lastxo));
			//-- Get a random y-offst based on the height of the current glyph.
			$h = round($glyph['height'] * $overlapf_v);
			$svo = $this->height / $this->dsettings['glyph_offsetting']['mh'];
			$yoffset = Smart::random_number((int)($svo > $h ? 0 : $svo), (int)$h);
			// Translate all points by the calculated offset. Except the very firs glyph. It should start left aligned.
			$this->on_points(
				$glyph['glyph_data'], [$this, '_translate'], [$accumulated_hoffset, $yoffset]
			);
			//--
			$lastxo = round($glyph['width'] * $overlapf_h);
			$lastyo = round($glyph['height'] * $overlapf_v);
			$cnt++;
			//--
		} //end foreach
		//--
		// Reevaluate the width of the image by the accumulated offset + the width of the last glyph + a random padding of maximally the last glpyh's half size.
		//$this->width = $accumulated_hoffset + $glyph['width'] + Smart::random_number(Smart::floor_number($glyph['width'] * $overlapf_h), (int)$glyph['width']);
		//--
		return (array) $glyphs;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * This function distorts the coordinate system of every glyph on two levels:
	 * First it chooses a set of affine transformations randomly. Then it distorts the coordinate
	 * system by feeding the transformations random arguments.
	 * @param array The glyphs to apply the affine transformations.
	 */
	private function _apply_affine_transformations($glyphs) {
		//--
		foreach($glyphs as $kk => &$glyph) {
			foreach($this->_get_random_transformations() as $zz => $transformation) {
				$this->on_points($glyph['glyph_data'], $transformation[0], $transformation[1]);
			} //end foreach
		} //end foreach
		//--
		return (array) $glyphs;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Generates random transformations based on the difficulty settings.
	 *
	 * @return array Returns an array of (random) transformations.
	 */
	private function _get_random_transformations() {
		//-- Prepare some transformations with some random arguments.
		$transformations = [];
		if($this->dsettings['transformations']['rotate']) {
			$transformations[] = [ [$this, '_rotate'], [$this->_ra()] ];
		} //end if
		if($this->dsettings['transformations']['skew']) {
			$transformations[] = [ [$this, '_skew'], [$this->_ra()] ];
		} //end if
		if($this->dsettings['transformations']['scale']) {
			$transformations[] = [ [$this, '_scale'], [$this->_rs()] ];
		} //end if
		if($this->dsettings['transformations']['shear']) {
			$transformations[] = [ [$this, '_shear'], [$this->dsettings['transformations']['shear_factor'], 0] ];
		} //end if
		if($this->dsettings['transformations']['translate']) {
			$transformations[] = [ [$this, '_translate'], [0, 0] ];
		} //end if
		if(empty($transformations)) {
			//return (array) null;
			return []; // fix by unixman
		} //end if
		//-- How many random transformations to delete?
		$n = Smart::random_number(0, (int)((int)Smart::array_size($transformations) - 1));
		//--
		$this->_shuffle_assoc($transformations);
		//-- Delete the (random) transformations we don't want
		for($i = 0; $i < $n; $i++) {
			unset($transformations[$i]);
		} //end for
		//--
		return (array) $transformations;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Recursive function.
	 *
	 * Applies the function $callback recursively on every point found in $data.
	 * The $callback function needs to have a point as its first argument.
	 *
	 * @param array $data An array holding point instances.
	 * @param array $callback The function to call for any point.
	 * @param array $args An associative array with parameter names as keys and arguments as values.
	 */
	private function on_points(&$data, $callback, $args) {
		//-- Base step
		if($data instanceof SmartSvgCaptchaPoint) { // Send me a letter for X-Mas!
			if(is_callable($callback)) {
				call_user_func_array($callback, array_merge([$data], $args));
			} //end if
		} //end if
		//-- Recursive step
		if(is_array($data)) {
			foreach($data as $kk => &$value) {
				$this->on_points($value, $callback, $args);
			} //end foreach
			unset($value);
		} //end if
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns a random angle.
	 *
	 * @param int (Optional). Specifies the upper bound in radian.
	 * @return int
	 */
	private function _ra($ub=null) {
		//--
		$n = (float) (Smart::random_number(0, (int)($ub != null ? $ub : 4)) / 10);
		if(Smart::random_number(0, 1) == 1) {
			$n *= -1;
		} //end if
		//--
		return (float) $n;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns a random scale factor.
	 *
	 * @return int
	 */
	private function _rs() {
		//--
		return (float) (Smart::random_number(8, 12) / 10);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Returns a random t parameter between 0-1 and
	 * if $inclusive is true, including zero and 0.
	 *
	 * @param bool $inclusive Description
	 * @return int The value between 0-1
	 */
	private function _rt($inclusive=true) {
		//--
		if($inclusive) {
			$max = 1000;
		} else {
			$max = 999;
		} //end if
		//--
		return (float) (Smart::random_number(0, (int)$max) / 1000);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Applies a rotation matrix on a point:
	 * (x, y) = cos(a)*x - sin(a)*y, sin(a)*x + cos(a)*y
	 *
	 * @param SmartSvgCaptchaPoint $p The point to rotate.
	 * @param float $a The rotation angle.
	 */
	private function _rotate($p, $a) {
		//--
		$x = $p->x;
		$y = $p->y;
		$p->x = cos($a) * $x - sin($a) * $y;
		$p->y = sin($a) * $x + cos($a) * $y;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Applies a skew matrix on a point:
	 * (x, y) = x+sin(a)*y, y
	 *
	 * @param SmartSvgCaptchaPoint $p The point to skew.
	 * @param float $a The skew angle.
	 */
	private function _skew($p, $a) {
		//--
		$x = $p->x;
		$y = $p->y;
		$p->x = $x + sin($a) * $y;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Scales a point with $sx and $sy:
	 * (x, y) = x*sx, y*sy
	 *
	 * @param SmartSvgCaptchaPoint $p The point to scale.
	 * @param float $s The scale factor for the x/y-component.
	 */
	private function _scale($p, $s = 1) {
		//--
		$x = $p->x;
		$y = $p->y;
		$p->x = $x * $s;
		$p->y = $y * $s;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * http://en.wikipedia.org/wiki/Shear_mapping
	 *
	 * Displace every point horizontally by an amount proportionally
	 * to its y(horizontal shear) or x(vertical shear) coordinate.
	 *
	 * Horizontal shear: (x, y) = (x + mh*y, y)
	 * Vertical shear: (x, y) = (x, y + mv*x)
	 *
	 * One shear factor needs always to be zero.
	 *
	 * @param SmartSvgCaptchaPoint $p
	 * @param float $mh The shear factor for horizontal shear.
	 * @param float $mv The shear factor for vertical shear.
	 */
	private function _shear($p, $mh = 1, $mv = 0) {
		//--
		if(($mh * $mv) != 0) {
			return;
		} //end if
		//--
		$x = $p->x;
		$y = $p->y;
		$p->x = $x + $y * $mh;
		$p->y = $y + $x * $mv;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Translates the point by the given $dx and $dy.
	 * (x, y) = x + dx, y + dy
	 * @param SmartSvgCaptchaPoint $p
	 * @param float $dx
	 * @param float $dy
	 */
	private function _translate($p, $dx, $dy) {
		//--
		$x = $p->x;
		$y = $p->y;
		$p->x = $x + $dx;
		$p->y = $y + $dy;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 *
	 * @param array $line
	 * @return array An array of points constituting the approximated line.
	 */
	private function _approximate_line($line) {
		//--
		if((Smart::array_size($line) != 2) || (!isset($line[0])) || !($line[0] instanceof SmartSvgCaptchaPoint) || (!isset($line[1])) || !($line[1] instanceof SmartSvgCaptchaPoint)) {
			return [];
		} //end if else
		//--
		/*
		  There are several ways to make a bezier curve look like a line. We need to have a threshold
		  that determines how big the distance from a particular but arbitrarily chosen control point is
		  from the original line. Naturally, such a distance must be rather small...
		 *
		 * General principle: The points that determine the line must be the same as the at least
		 * two points of the Bezier curve. The remaining points can be anywhere on the imaginable straight line.
		 * This induces that also control points can represent the lines defining points and thus the resulting
		 * bezier line overlaps (The control points become interpolate with the line points).
		 */
		//-- First choose the target curve
		$make_cubic = (intval(time()) & 1) ? true : false; // Who cares? There's enough randomness already ...
		//-- A closure that gets a point somewhere near the line :P Somewhere near depends heavily on the length of the size itself. How do we get line lengths? Yep, I actually DO remember something for once from my maths courses :/
		$d = (float) sqrt(pow(abs($line[0]->x - $line[1]->x), 2) + pow(abs($line[0]->y - $line[1]->y), 2));
		// The control points are allowed to be maximally a 10th of the line width apart from the line distance.
		$md = (float) ($d / Smart::random_number(10, 50));
		//--
		$somewhere_near_the_line = function($line, $md) {
			//-- Such a point must be within the bounding rectangle of the line.
			$maxx = max($line[0]->x, $line[1]->x);
			$maxy = max($line[0]->y, $line[1]->y);
			$minx = min($line[0]->x, $line[1]->x);
			$miny = min($line[0]->y, $line[1]->y);
			//-- Now get a point on the line. Remember: f(x) = mx + d ; But watch out! Lines parallel to the y-axis promise trouble! Just change these a bit :P
			$divisor = ($line[1]->x - $line[0]->x);
			if($divisor == 0) {
				$divisor = 0.001;
				$line[1]->x += 1;
			} //end if
			// Get the coefficient m and the (0, d)-y-intersection.
			//-- # unixman: fix division by zero below
		//	$m = ($line[1]->y - $line[0]->y) / ($line[1]->x - $line[0]->x);
		//	$d = ($line[1]->x * $line[0]->y - $line[0]->x * $line[1]->y) / ($line[1]->x - $line[0]->x);
			$m = ($line[1]->y - $line[0]->y) / $divisor;
			$d = ($line[1]->x * $line[0]->y - $line[0]->x * $line[1]->y) / $divisor;
			if($maxx < 0 || $minx < 0) { // Some strange cases oO
				$ma = max(abs($maxx), abs($minx));
				$mi = min(abs($maxx), abs($minx));
				$x = -1 * Smart::random_number((int)$mi, (int)$ma);
			} else {
				$x = Smart::random_number((int)$minx, (int)$maxx);
			} //end if else
			$y = $m * $x + $d;
			//-- And move it away by $md :P
			return (object) new SmartSvgCaptchaPoint($x + ((Smart::random_number(0, 1) == 1) ? $md : -1*$md), $y + ((Smart::random_number(0, 1) == 1) ? $md : -1*$md));
			//--
		}; //end anonymous function
		//--
		if($make_cubic) {
			$p1 = $somewhere_near_the_line($line, $md);
			$p2 = $somewhere_near_the_line($line, $md);
			$curve = [ $line[0], $p1, $p2, $line[1] ];
		} else {
			$p1 = $somewhere_near_the_line($line, $md);
			$curve = [ $line[0], $p1, $line[1] ];
		} //end if else
		//--
		return (array) $curve;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Approximates a quadratic/cubic Bezier curves by $nlines lines. If $nlines is false or unset, a random $nlines
	 * between 10 and 20 is chosen.
	 *
	 * @param array $curve An array of three or four points representing a quadratic or cubic Bezier curve.
	 * @return array Returns an array of lines (array of two points).
	 */
	private function _approximate_bezier($curve, $nlines = false) {
		//-- Check that we deal with SmartSvgCaptchaPoint arrays only.
		foreach($curve as $kk => $point) {
			if(get_class($point) != 'SmartSvgCaptchaPoint') {
				return [];
			} //end if
		} //end foreach
		if(!$nlines || !isset($nlines)) {
			$nlines = Smart::random_number(min($this->dsettings['approx_shapes']['r_al_num_lines']), max($this->dsettings['approx_shapes']['r_al_num_lines']));
		} //end if
		//--
		$approx_func = null; // because PHP sucks!
		//--
		if(Smart::array_size($curve) == 3) { // Handle quadratic curves.
			$approx_func = function($curve, $nlines) {
				list($p1, $p2, $p3) = $curve;
				$last = $p1;
				$lines = [];
				for($i = 0; $i <= $nlines; $i++) {
					$t = $i / $nlines;
					$t2 = $t * $t;
					$mt = 1 - $t;
					$mt2 = $mt * $mt;
					$x = $p1->x * $mt2 + $p2->x * 2 * $mt * $t + $p3->x * $t2;
					$y = $p1->y * $mt2 + $p2->y * 2 * $mt * $t + $p3->y * $t2;
					$lines[] = [ $last, new SmartSvgCaptchaPoint($x, $y) ];
					$last = new SmartSvgCaptchaPoint($x, $y);
				} //end for
				return (array) $lines;
			}; //end anonymous function
		} elseif(Smart::array_size($curve) == 4) { // Handle cubic curves.
			$approx_func = function($curve, $nlines) {
				list($p1, $p2, $p3, $p4) = $curve;
				$last = $p1;
				$lines = [];
				for($i = 0; $i <= $nlines; $i++) {
					$t = $i / $nlines;
					$t2 = $t * $t;
					$t3 = $t2 * $t;
					$mt = 1 - $t;
					$mt2 = $mt * $mt;
					$mt3 = $mt2 * $mt;
					$x = $p1->x * $mt3 + 3 * $p2->x * $mt2 * $t + 3 * $p3->x * $mt * $t2 + $p4->x * $t3;
					$y = $p1->y * $mt3 + 3 * $p2->y * $mt2 * $t + 3 * $p3->y * $mt * $t2 + $p4->y * $t3;
					$lines[] = [ $last, new SmartSvgCaptchaPoint($x, $y) ];
					$last = new SmartSvgCaptchaPoint($x, $y);
				} //end for
				return (array) $lines;
			}; //end anonymous function
		} else {
			return [];
		} //end if else
		//--
		return (array) $approx_func($curve, $nlines);
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * This functon splits a curve at a given point t and returns two subcurves:
	 * The right and left one. Note: The right array needs to be reversed before useage.
	 *
	 * @param array $curve The curve to split.
	 * @param float $t The parameter t where to split the curve.
	 * @param array $left The left subcurve. Passed by reference.
	 * @param  array The right subcurve. Passed by reference.
	 */
	private function _split_curve($curve, $t, &$left, &$right) {
		//-- Check that we deal with SmartSvgCaptchaPoint arrays only.
		foreach($curve as $kk => $point) {
			if(get_class($point) != 'SmartSvgCaptchaPoint') {
				return;
			} //end if
		} //end foreach
		//--
		if(Smart::array_size($curve) == 1) {
			$left[] = $curve[0];
			$right[] = $curve[0];
		} else {
			$newpoints = [];
			for($i = 0; $i < (int)(Smart::array_size($curve) - 1); $i++) {
				if($i == 0) {
					$left[] = $curve[$i];
				} //end if
				if($i == (int)(Smart::array_size($curve) - 2)) {
					$right[] = $curve[$i + 1];
				} //end if
				$x = (1 - $t) * $curve[$i]->x + $t * $curve[$i + 1]->x;
				$y = (1 - $t) * $curve[$i]->y + $t * $curve[$i + 1]->y;
				$newpoints[] = new SmartSvgCaptchaPoint($x, $y);
			} //end for
			$this->_split_curve($newpoints, $t, $left, $right);
		} //end if else
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	private function initAlphabetGlyphs() { // abefikny EGHQSWX
		//--
		if(is_array($this->alphabet) && (Smart::array_size($this->alphabet) > 0)) {
			return (array) $this->alphabet;
		} //end if
		//--
		$this->alphabet = [
			'a' => [
				'width' => 351,
				'height' => 634,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(195, 0), new SmartSvgCaptchaPoint(163, 0), new SmartSvgCaptchaPoint(133, 9), new SmartSvgCaptchaPoint(107, 27) ],
						[ new SmartSvgCaptchaPoint(107, 27), new SmartSvgCaptchaPoint(71, 53), new SmartSvgCaptchaPoint(60, 162), new SmartSvgCaptchaPoint(60, 162) ],
						[ new SmartSvgCaptchaPoint(60, 162), new SmartSvgCaptchaPoint(73, 177), new SmartSvgCaptchaPoint(95, 212), new SmartSvgCaptchaPoint(95, 212) ],
						[ new SmartSvgCaptchaPoint(95, 212), new SmartSvgCaptchaPoint(95, 212), new SmartSvgCaptchaPoint(104, 99), new SmartSvgCaptchaPoint(129, 72) ],
						[ new SmartSvgCaptchaPoint(95, 212), new SmartSvgCaptchaPoint(95, 212), new SmartSvgCaptchaPoint(104, 99), new SmartSvgCaptchaPoint(129, 72) ],
						[ new SmartSvgCaptchaPoint(129, 72), new SmartSvgCaptchaPoint(161, 31), new SmartSvgCaptchaPoint(207, 26), new SmartSvgCaptchaPoint(262, 74) ],
						[ new SmartSvgCaptchaPoint(262, 74), new SmartSvgCaptchaPoint(300, 130), new SmartSvgCaptchaPoint(277, 185), new SmartSvgCaptchaPoint(245, 228) ],
						[ new SmartSvgCaptchaPoint(245, 228), new SmartSvgCaptchaPoint(209, 277), new SmartSvgCaptchaPoint(151, 274), new SmartSvgCaptchaPoint(105, 287) ],
						[ new SmartSvgCaptchaPoint(245, 228), new SmartSvgCaptchaPoint(209, 277), new SmartSvgCaptchaPoint(151, 274), new SmartSvgCaptchaPoint(105, 287) ],
						[ new SmartSvgCaptchaPoint(105, 287), new SmartSvgCaptchaPoint(9, 326), new SmartSvgCaptchaPoint(0, 444), new SmartSvgCaptchaPoint(23, 521) ],
						[ new SmartSvgCaptchaPoint(23, 521), new SmartSvgCaptchaPoint(32, 562), new SmartSvgCaptchaPoint(53, 590), new SmartSvgCaptchaPoint(90, 601) ],
						[ new SmartSvgCaptchaPoint(90, 601), new SmartSvgCaptchaPoint(150, 618), new SmartSvgCaptchaPoint(193, 601), new SmartSvgCaptchaPoint(225, 563) ],
						[ new SmartSvgCaptchaPoint(90, 601), new SmartSvgCaptchaPoint(150, 618), new SmartSvgCaptchaPoint(193, 601), new SmartSvgCaptchaPoint(225, 563) ],
						[ new SmartSvgCaptchaPoint(225, 563), new SmartSvgCaptchaPoint(231, 590), new SmartSvgCaptchaPoint(232, 620), new SmartSvgCaptchaPoint(258, 626) ],
						[ new SmartSvgCaptchaPoint(258, 626), new SmartSvgCaptchaPoint(298, 634), new SmartSvgCaptchaPoint(351, 628), new SmartSvgCaptchaPoint(312, 589) ],
						[ new SmartSvgCaptchaPoint(312, 589), new SmartSvgCaptchaPoint(273, 551), new SmartSvgCaptchaPoint(283, 535), new SmartSvgCaptchaPoint(281, 510) ],
						[ new SmartSvgCaptchaPoint(312, 589), new SmartSvgCaptchaPoint(273, 551), new SmartSvgCaptchaPoint(283, 535), new SmartSvgCaptchaPoint(281, 510) ],
						[ new SmartSvgCaptchaPoint(335, 71), new SmartSvgCaptchaPoint(339, 43), new SmartSvgCaptchaPoint(291, 17), new SmartSvgCaptchaPoint(240, 5) ],
						[ new SmartSvgCaptchaPoint(240, 5), new SmartSvgCaptchaPoint(224, 1), new SmartSvgCaptchaPoint(209, 0), new SmartSvgCaptchaPoint(195, 0) ],
						[ new SmartSvgCaptchaPoint(252, 283), new SmartSvgCaptchaPoint(270, 367), new SmartSvgCaptchaPoint(251, 535), new SmartSvgCaptchaPoint(152, 571) ],
						[ new SmartSvgCaptchaPoint(252, 283), new SmartSvgCaptchaPoint(270, 367), new SmartSvgCaptchaPoint(251, 535), new SmartSvgCaptchaPoint(152, 571) ],
						[ new SmartSvgCaptchaPoint(152, 571), new SmartSvgCaptchaPoint(54, 608), new SmartSvgCaptchaPoint(35, 434), new SmartSvgCaptchaPoint(72, 384) ],
						[ new SmartSvgCaptchaPoint(72, 384), new SmartSvgCaptchaPoint(124, 313), new SmartSvgCaptchaPoint(178, 279), new SmartSvgCaptchaPoint(252, 283) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(281, 510), new SmartSvgCaptchaPoint(335, 71) ],
						[ new SmartSvgCaptchaPoint(195, 0), new SmartSvgCaptchaPoint(195, 0) ],
						[ new SmartSvgCaptchaPoint(252, 283), new SmartSvgCaptchaPoint(252, 283) ]
					]
				]
			],
			'b' => [
				'width' => 237,
				'height' => 454,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(43, 0), new SmartSvgCaptchaPoint(39, 13), new SmartSvgCaptchaPoint(38, 20), new SmartSvgCaptchaPoint(37, 26) ],
						[ new SmartSvgCaptchaPoint(37, 26), new SmartSvgCaptchaPoint(0, 302), new SmartSvgCaptchaPoint(5, 438), new SmartSvgCaptchaPoint(5, 438) ],
						[ new SmartSvgCaptchaPoint(5, 438), new SmartSvgCaptchaPoint(5, 438), new SmartSvgCaptchaPoint(142, 454), new SmartSvgCaptchaPoint(188, 414) ],
						[ new SmartSvgCaptchaPoint(188, 414), new SmartSvgCaptchaPoint(222, 385), new SmartSvgCaptchaPoint(237, 329), new SmartSvgCaptchaPoint(224, 287) ],
						[ new SmartSvgCaptchaPoint(188, 414), new SmartSvgCaptchaPoint(222, 385), new SmartSvgCaptchaPoint(237, 329), new SmartSvgCaptchaPoint(224, 287) ],
						[ new SmartSvgCaptchaPoint(224, 287), new SmartSvgCaptchaPoint(213, 254), new SmartSvgCaptchaPoint(177, 221), new SmartSvgCaptchaPoint(141, 220) ],
						[ new SmartSvgCaptchaPoint(141, 220), new SmartSvgCaptchaPoint(99, 220), new SmartSvgCaptchaPoint(40, 295), new SmartSvgCaptchaPoint(40, 295) ],
						[ new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(18, 373), new SmartSvgCaptchaPoint(38, 398) ],
						[ new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(18, 373), new SmartSvgCaptchaPoint(38, 398) ],
						[ new SmartSvgCaptchaPoint(38, 398), new SmartSvgCaptchaPoint(64, 431), new SmartSvgCaptchaPoint(131, 416), new SmartSvgCaptchaPoint(161, 388) ],
						[ new SmartSvgCaptchaPoint(161, 388), new SmartSvgCaptchaPoint(186, 366), new SmartSvgCaptchaPoint(189, 321), new SmartSvgCaptchaPoint(178, 289) ],
						[ new SmartSvgCaptchaPoint(178, 289), new SmartSvgCaptchaPoint(172, 272), new SmartSvgCaptchaPoint(156, 253), new SmartSvgCaptchaPoint(138, 253) ],
						[ new SmartSvgCaptchaPoint(178, 289), new SmartSvgCaptchaPoint(172, 272), new SmartSvgCaptchaPoint(156, 253), new SmartSvgCaptchaPoint(138, 253) ],
						[ new SmartSvgCaptchaPoint(138, 253), new SmartSvgCaptchaPoint(109, 251), new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(69, 305) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(40, 295), new SmartSvgCaptchaPoint(85, 4) ],
						[ new SmartSvgCaptchaPoint(85, 4), new SmartSvgCaptchaPoint(43, 0) ],
						[ new SmartSvgCaptchaPoint(69, 305), new SmartSvgCaptchaPoint(69, 305) ]
					]
				]
			],
			'e' => [
				'width' => 480,
				'height' => 615,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(46, 207), new SmartSvgCaptchaPoint(0, 331), new SmartSvgCaptchaPoint(3, 525), new SmartSvgCaptchaPoint(204, 570) ],
						[ new SmartSvgCaptchaPoint(204, 570), new SmartSvgCaptchaPoint(404, 615), new SmartSvgCaptchaPoint(454, 423), new SmartSvgCaptchaPoint(460, 406) ],
						[ new SmartSvgCaptchaPoint(389, 406), new SmartSvgCaptchaPoint(389, 406), new SmartSvgCaptchaPoint(354, 498), new SmartSvgCaptchaPoint(313, 515) ],
						[ new SmartSvgCaptchaPoint(313, 515), new SmartSvgCaptchaPoint(252, 539), new SmartSvgCaptchaPoint(166, 522), new SmartSvgCaptchaPoint(121, 474) ],
						[ new SmartSvgCaptchaPoint(313, 515), new SmartSvgCaptchaPoint(252, 539), new SmartSvgCaptchaPoint(166, 522), new SmartSvgCaptchaPoint(121, 474) ],
						[ new SmartSvgCaptchaPoint(121, 474), new SmartSvgCaptchaPoint(82, 433), new SmartSvgCaptchaPoint(98, 306), new SmartSvgCaptchaPoint(98, 306) ],
						[ new SmartSvgCaptchaPoint(461, 304), new SmartSvgCaptchaPoint(461, 304), new SmartSvgCaptchaPoint(480, 140), new SmartSvgCaptchaPoint(334, 70) ],
						[ new SmartSvgCaptchaPoint(334, 70), new SmartSvgCaptchaPoint(188, 0), new SmartSvgCaptchaPoint(83, 108), new SmartSvgCaptchaPoint(46, 207) ],
						[ new SmartSvgCaptchaPoint(334, 70), new SmartSvgCaptchaPoint(188, 0), new SmartSvgCaptchaPoint(83, 108), new SmartSvgCaptchaPoint(46, 207) ],
						[ new SmartSvgCaptchaPoint(387, 257), new SmartSvgCaptchaPoint(387, 257), new SmartSvgCaptchaPoint(379, 114), new SmartSvgCaptchaPoint(251, 112) ],
						[ new SmartSvgCaptchaPoint(251, 112), new SmartSvgCaptchaPoint(123, 109), new SmartSvgCaptchaPoint(97, 257), new SmartSvgCaptchaPoint(97, 257) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(460, 406), new SmartSvgCaptchaPoint(389, 406) ],
						[ new SmartSvgCaptchaPoint(98, 306), new SmartSvgCaptchaPoint(461, 304) ],
						[ new SmartSvgCaptchaPoint(46, 207), new SmartSvgCaptchaPoint(46, 207) ],
						[ new SmartSvgCaptchaPoint(97, 257), new SmartSvgCaptchaPoint(387, 257) ],
						[ new SmartSvgCaptchaPoint(97, 257), new SmartSvgCaptchaPoint(387, 257) ],
						[ new SmartSvgCaptchaPoint(97, 257), new SmartSvgCaptchaPoint(97, 257) ]
					]
				]
			],
			'f' => [
				'width' => 240,
				'height' => 600,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(240, 0), new SmartSvgCaptchaPoint(240, 0), new SmartSvgCaptchaPoint(167, 0), new SmartSvgCaptchaPoint(138, 11) ],
						[ new SmartSvgCaptchaPoint(138, 11), new SmartSvgCaptchaPoint(106, 24), new SmartSvgCaptchaPoint(84, 48), new SmartSvgCaptchaPoint(70, 80) ],
						[ new SmartSvgCaptchaPoint(70, 80), new SmartSvgCaptchaPoint(57, 108), new SmartSvgCaptchaPoint(60, 170), new SmartSvgCaptchaPoint(60, 170) ],
						[ new SmartSvgCaptchaPoint(90, 170), new SmartSvgCaptchaPoint(90, 170), new SmartSvgCaptchaPoint(87, 116), new SmartSvgCaptchaPoint(97, 91) ],
						[ new SmartSvgCaptchaPoint(90, 170), new SmartSvgCaptchaPoint(90, 170), new SmartSvgCaptchaPoint(87, 116), new SmartSvgCaptchaPoint(97, 91) ],
						[ new SmartSvgCaptchaPoint(97, 91), new SmartSvgCaptchaPoint(106, 68), new SmartSvgCaptchaPoint(146, 48), new SmartSvgCaptchaPoint(170, 40) ],
						[ new SmartSvgCaptchaPoint(170, 40), new SmartSvgCaptchaPoint(197, 31), new SmartSvgCaptchaPoint(240, 50), new SmartSvgCaptchaPoint(240, 50) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(240, 50), new SmartSvgCaptchaPoint(240, 0) ],
						[ new SmartSvgCaptchaPoint(60, 170), new SmartSvgCaptchaPoint(0, 170) ],
						[ new SmartSvgCaptchaPoint(0, 170), new SmartSvgCaptchaPoint(0, 200) ],
						[ new SmartSvgCaptchaPoint(0, 200), new SmartSvgCaptchaPoint(60, 200) ],
						[ new SmartSvgCaptchaPoint(0, 200), new SmartSvgCaptchaPoint(60, 200) ],
						[ new SmartSvgCaptchaPoint(60, 200), new SmartSvgCaptchaPoint(60, 570) ],
						[ new SmartSvgCaptchaPoint(60, 570), new SmartSvgCaptchaPoint(0, 570) ],
						[ new SmartSvgCaptchaPoint(0, 570), new SmartSvgCaptchaPoint(0, 600) ],
						[ new SmartSvgCaptchaPoint(0, 570), new SmartSvgCaptchaPoint(0, 600) ],
						[ new SmartSvgCaptchaPoint(0, 600), new SmartSvgCaptchaPoint(130, 600) ],
						[ new SmartSvgCaptchaPoint(130, 600), new SmartSvgCaptchaPoint(150, 570) ],
						[ new SmartSvgCaptchaPoint(150, 570), new SmartSvgCaptchaPoint(90, 570) ],
						[ new SmartSvgCaptchaPoint(150, 570), new SmartSvgCaptchaPoint(90, 570) ],
						[ new SmartSvgCaptchaPoint(90, 570), new SmartSvgCaptchaPoint(90, 200) ],
						[ new SmartSvgCaptchaPoint(90, 200), new SmartSvgCaptchaPoint(150, 200) ],
						[ new SmartSvgCaptchaPoint(150, 200), new SmartSvgCaptchaPoint(150, 170) ],
						[ new SmartSvgCaptchaPoint(150, 200), new SmartSvgCaptchaPoint(150, 170) ],
						[ new SmartSvgCaptchaPoint(150, 170), new SmartSvgCaptchaPoint(90, 170) ],
						[ new SmartSvgCaptchaPoint(240, 50), new SmartSvgCaptchaPoint(240, 50) ]
					]
				]
			],
			'i' => [
				'width' => 122,
				'height' => 687,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(67, 1), new SmartSvgCaptchaPoint(48, 0), new SmartSvgCaptchaPoint(28, 14), new SmartSvgCaptchaPoint(17, 29) ],
						[ new SmartSvgCaptchaPoint(17, 29), new SmartSvgCaptchaPoint(6, 45), new SmartSvgCaptchaPoint(0, 67), new SmartSvgCaptchaPoint(7, 85) ],
						[ new SmartSvgCaptchaPoint(7, 85), new SmartSvgCaptchaPoint(14, 107), new SmartSvgCaptchaPoint(37, 128), new SmartSvgCaptchaPoint(60, 130) ],
						[ new SmartSvgCaptchaPoint(60, 130), new SmartSvgCaptchaPoint(79, 131), new SmartSvgCaptchaPoint(99, 117), new SmartSvgCaptchaPoint(109, 100) ],
						[ new SmartSvgCaptchaPoint(60, 130), new SmartSvgCaptchaPoint(79, 131), new SmartSvgCaptchaPoint(99, 117), new SmartSvgCaptchaPoint(109, 100) ],
						[ new SmartSvgCaptchaPoint(109, 100), new SmartSvgCaptchaPoint(120, 82), new SmartSvgCaptchaPoint(122, 56), new SmartSvgCaptchaPoint(113, 37) ],
						[ new SmartSvgCaptchaPoint(113, 37), new SmartSvgCaptchaPoint(105, 19), new SmartSvgCaptchaPoint(86, 3), new SmartSvgCaptchaPoint(67, 1) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(23, 214), new SmartSvgCaptchaPoint(23, 687) ],
						[ new SmartSvgCaptchaPoint(23, 687), new SmartSvgCaptchaPoint(96, 687) ],
						[ new SmartSvgCaptchaPoint(96, 687), new SmartSvgCaptchaPoint(96, 214) ],
						[ new SmartSvgCaptchaPoint(96, 214), new SmartSvgCaptchaPoint(23, 214) ],
						[ new SmartSvgCaptchaPoint(96, 214), new SmartSvgCaptchaPoint(23, 214) ],
						[ new SmartSvgCaptchaPoint(67, 1), new SmartSvgCaptchaPoint(67, 1) ]
					]
				]
			],
			'k' => [
				'width' => 420,
				'height' => 680,
				'glyph_data' => [
					'lines' => [
						[ new SmartSvgCaptchaPoint(0, 0), new SmartSvgCaptchaPoint(60, 0) ],
						[ new SmartSvgCaptchaPoint(60, 0), new SmartSvgCaptchaPoint(60, 490) ],
						[ new SmartSvgCaptchaPoint(60, 490), new SmartSvgCaptchaPoint(350, 280) ],
						[ new SmartSvgCaptchaPoint(350, 280), new SmartSvgCaptchaPoint(420, 280) ],
						[ new SmartSvgCaptchaPoint(350, 280), new SmartSvgCaptchaPoint(420, 280) ],
						[ new SmartSvgCaptchaPoint(420, 280), new SmartSvgCaptchaPoint(210, 440) ],
						[ new SmartSvgCaptchaPoint(210, 440), new SmartSvgCaptchaPoint(420, 680) ],
						[ new SmartSvgCaptchaPoint(420, 680), new SmartSvgCaptchaPoint(350, 680) ],
						[ new SmartSvgCaptchaPoint(420, 680), new SmartSvgCaptchaPoint(350, 680) ],
						[ new SmartSvgCaptchaPoint(350, 680), new SmartSvgCaptchaPoint(170, 470) ],
						[ new SmartSvgCaptchaPoint(170, 470), new SmartSvgCaptchaPoint(60, 550) ],
						[ new SmartSvgCaptchaPoint(60, 550), new SmartSvgCaptchaPoint(60, 680) ],
						[ new SmartSvgCaptchaPoint(60, 550), new SmartSvgCaptchaPoint(60, 680) ],
						[ new SmartSvgCaptchaPoint(60, 680), new SmartSvgCaptchaPoint(0, 680) ],
						[ new SmartSvgCaptchaPoint(0, 680), new SmartSvgCaptchaPoint(0, 0) ]
					]
				]
			],
			'n' => [
				'width' => 420,
				'height' => 380,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(111, 50), new SmartSvgCaptchaPoint(111, 50), new SmartSvgCaptchaPoint(146, 38), new SmartSvgCaptchaPoint(206, 39) ],
						[ new SmartSvgCaptchaPoint(206, 39), new SmartSvgCaptchaPoint(267, 41), new SmartSvgCaptchaPoint(287, 53), new SmartSvgCaptchaPoint(304, 67) ],
						[ new SmartSvgCaptchaPoint(304, 67), new SmartSvgCaptchaPoint(318, 79), new SmartSvgCaptchaPoint(340, 110), new SmartSvgCaptchaPoint(340, 110) ],
						[ new SmartSvgCaptchaPoint(370, 110), new SmartSvgCaptchaPoint(370, 110), new SmartSvgCaptchaPoint(361, 71), new SmartSvgCaptchaPoint(340, 50) ],
						[ new SmartSvgCaptchaPoint(370, 110), new SmartSvgCaptchaPoint(370, 110), new SmartSvgCaptchaPoint(361, 71), new SmartSvgCaptchaPoint(340, 50) ],
						[ new SmartSvgCaptchaPoint(340, 50), new SmartSvgCaptchaPoint(310, 20), new SmartSvgCaptchaPoint(288, 15), new SmartSvgCaptchaPoint(220, 10) ],
						[ new SmartSvgCaptchaPoint(220, 10), new SmartSvgCaptchaPoint(151, 5), new SmartSvgCaptchaPoint(110, 20), new SmartSvgCaptchaPoint(110, 20) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(30, 0), new SmartSvgCaptchaPoint(30, 30) ],
						[ new SmartSvgCaptchaPoint(30, 30), new SmartSvgCaptchaPoint(80, 30) ],
						[ new SmartSvgCaptchaPoint(80, 30), new SmartSvgCaptchaPoint(80, 350) ],
						[ new SmartSvgCaptchaPoint(80, 350), new SmartSvgCaptchaPoint(30, 350) ],
						[ new SmartSvgCaptchaPoint(80, 350), new SmartSvgCaptchaPoint(30, 350) ],
						[ new SmartSvgCaptchaPoint(30, 350), new SmartSvgCaptchaPoint(0, 380) ],
						[ new SmartSvgCaptchaPoint(0, 380), new SmartSvgCaptchaPoint(160, 380) ],
						[ new SmartSvgCaptchaPoint(160, 380), new SmartSvgCaptchaPoint(160, 350) ],
						[ new SmartSvgCaptchaPoint(160, 380), new SmartSvgCaptchaPoint(160, 350) ],
						[ new SmartSvgCaptchaPoint(160, 350), new SmartSvgCaptchaPoint(110, 350) ],
						[ new SmartSvgCaptchaPoint(110, 350), new SmartSvgCaptchaPoint(111, 50) ],
						[ new SmartSvgCaptchaPoint(340, 110), new SmartSvgCaptchaPoint(340, 350) ],
						[ new SmartSvgCaptchaPoint(340, 110), new SmartSvgCaptchaPoint(340, 350) ],
						[ new SmartSvgCaptchaPoint(340, 350), new SmartSvgCaptchaPoint(290, 350) ],
						[ new SmartSvgCaptchaPoint(290, 350), new SmartSvgCaptchaPoint(290, 380) ],
						[ new SmartSvgCaptchaPoint(290, 380), new SmartSvgCaptchaPoint(420, 380) ],
						[ new SmartSvgCaptchaPoint(290, 380), new SmartSvgCaptchaPoint(420, 380) ],
						[ new SmartSvgCaptchaPoint(420, 380), new SmartSvgCaptchaPoint(370, 350) ],
						[ new SmartSvgCaptchaPoint(370, 350), new SmartSvgCaptchaPoint(370, 110) ],
						[ new SmartSvgCaptchaPoint(110, 20), new SmartSvgCaptchaPoint(110, 0) ],
						[ new SmartSvgCaptchaPoint(110, 20), new SmartSvgCaptchaPoint(110, 0) ],
						[ new SmartSvgCaptchaPoint(110, 0), new SmartSvgCaptchaPoint(30, 0) ]
					]
				]
			],
			'y' => [
				'width' => 347,
				'height' => 381,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(178, 245), new SmartSvgCaptchaPoint(178, 245), new SmartSvgCaptchaPoint(148, 314), new SmartSvgCaptchaPoint(122, 339) ],
						[ new SmartSvgCaptchaPoint(122, 339), new SmartSvgCaptchaPoint(109, 350), new SmartSvgCaptchaPoint(93, 361), new SmartSvgCaptchaPoint(76, 363) ],
						[ new SmartSvgCaptchaPoint(76, 363), new SmartSvgCaptchaPoint(53, 366), new SmartSvgCaptchaPoint(6, 342), new SmartSvgCaptchaPoint(6, 342) ],
						[ new SmartSvgCaptchaPoint(0, 359), new SmartSvgCaptchaPoint(0, 359), new SmartSvgCaptchaPoint(49, 381), new SmartSvgCaptchaPoint(73, 379) ],
						[ new SmartSvgCaptchaPoint(0, 359), new SmartSvgCaptchaPoint(0, 359), new SmartSvgCaptchaPoint(49, 381), new SmartSvgCaptchaPoint(73, 379) ],
						[ new SmartSvgCaptchaPoint(73, 379), new SmartSvgCaptchaPoint(93, 377), new SmartSvgCaptchaPoint(112, 365), new SmartSvgCaptchaPoint(128, 352) ],
						[ new SmartSvgCaptchaPoint(128, 352), new SmartSvgCaptchaPoint(158, 325), new SmartSvgCaptchaPoint(175, 286), new SmartSvgCaptchaPoint(195, 250) ],
						[ new SmartSvgCaptchaPoint(195, 250), new SmartSvgCaptchaPoint(235, 178), new SmartSvgCaptchaPoint(296, 16), new SmartSvgCaptchaPoint(296, 16) ],
						[ new SmartSvgCaptchaPoint(195, 250), new SmartSvgCaptchaPoint(235, 178), new SmartSvgCaptchaPoint(296, 16), new SmartSvgCaptchaPoint(296, 16) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(30, 19), new SmartSvgCaptchaPoint(65, 19) ],
						[ new SmartSvgCaptchaPoint(65, 19), new SmartSvgCaptchaPoint(178, 245) ],
						[ new SmartSvgCaptchaPoint(6, 342), new SmartSvgCaptchaPoint(0, 359) ],
						[ new SmartSvgCaptchaPoint(296, 16), new SmartSvgCaptchaPoint(347, 16) ],
						[ new SmartSvgCaptchaPoint(296, 16), new SmartSvgCaptchaPoint(347, 16) ],
						[ new SmartSvgCaptchaPoint(347, 16), new SmartSvgCaptchaPoint(347, 0) ],
						[ new SmartSvgCaptchaPoint(347, 0), new SmartSvgCaptchaPoint(239, 0) ],
						[ new SmartSvgCaptchaPoint(239, 0), new SmartSvgCaptchaPoint(239, 16) ],
						[ new SmartSvgCaptchaPoint(239, 0), new SmartSvgCaptchaPoint(239, 16) ],
						[ new SmartSvgCaptchaPoint(239, 16), new SmartSvgCaptchaPoint(278, 16) ],
						[ new SmartSvgCaptchaPoint(278, 16), new SmartSvgCaptchaPoint(189, 229) ],
						[ new SmartSvgCaptchaPoint(189, 229), new SmartSvgCaptchaPoint(81, 19) ],
						[ new SmartSvgCaptchaPoint(189, 229), new SmartSvgCaptchaPoint(81, 19) ],
						[ new SmartSvgCaptchaPoint(81, 19), new SmartSvgCaptchaPoint(135, 18) ],
						[ new SmartSvgCaptchaPoint(135, 18), new SmartSvgCaptchaPoint(135, 0) ],
						[ new SmartSvgCaptchaPoint(135, 0), new SmartSvgCaptchaPoint(30, 0) ],
						[ new SmartSvgCaptchaPoint(135, 0), new SmartSvgCaptchaPoint(30, 0) ],
						[ new SmartSvgCaptchaPoint(30, 0), new SmartSvgCaptchaPoint(30, 19) ]
					]
				]
			],
			'E' => [
				'width' => 370,
				'height' => 680,
				'glyph_data' => [
					'lines' => [
						[ new SmartSvgCaptchaPoint(0, 0), new SmartSvgCaptchaPoint(0, 50) ],
						[ new SmartSvgCaptchaPoint(0, 50), new SmartSvgCaptchaPoint(50, 50) ],
						[ new SmartSvgCaptchaPoint(50, 50), new SmartSvgCaptchaPoint(50, 630) ],
						[ new SmartSvgCaptchaPoint(50, 630), new SmartSvgCaptchaPoint(0, 630) ],
						[ new SmartSvgCaptchaPoint(50, 630), new SmartSvgCaptchaPoint(0, 630) ],
						[ new SmartSvgCaptchaPoint(0, 630), new SmartSvgCaptchaPoint(0, 680) ],
						[ new SmartSvgCaptchaPoint(0, 680), new SmartSvgCaptchaPoint(370, 680) ],
						[ new SmartSvgCaptchaPoint(370, 680), new SmartSvgCaptchaPoint(370, 550) ],
						[ new SmartSvgCaptchaPoint(370, 680), new SmartSvgCaptchaPoint(370, 550) ],
						[ new SmartSvgCaptchaPoint(370, 550), new SmartSvgCaptchaPoint(320, 550) ],
						[ new SmartSvgCaptchaPoint(320, 550), new SmartSvgCaptchaPoint(320, 630) ],
						[ new SmartSvgCaptchaPoint(320, 630), new SmartSvgCaptchaPoint(100, 630) ],
						[ new SmartSvgCaptchaPoint(320, 630), new SmartSvgCaptchaPoint(100, 630) ],
						[ new SmartSvgCaptchaPoint(100, 630), new SmartSvgCaptchaPoint(100, 360) ],
						[ new SmartSvgCaptchaPoint(100, 360), new SmartSvgCaptchaPoint(280, 360) ],
						[ new SmartSvgCaptchaPoint(280, 360), new SmartSvgCaptchaPoint(280, 310) ],
						[ new SmartSvgCaptchaPoint(280, 360), new SmartSvgCaptchaPoint(280, 310) ],
						[ new SmartSvgCaptchaPoint(280, 310), new SmartSvgCaptchaPoint(100, 310) ],
						[ new SmartSvgCaptchaPoint(100, 310), new SmartSvgCaptchaPoint(100, 50) ],
						[ new SmartSvgCaptchaPoint(100, 50), new SmartSvgCaptchaPoint(320, 50) ],
						[ new SmartSvgCaptchaPoint(100, 50), new SmartSvgCaptchaPoint(320, 50) ],
						[ new SmartSvgCaptchaPoint(320, 50), new SmartSvgCaptchaPoint(320, 130) ],
						[ new SmartSvgCaptchaPoint(320, 130), new SmartSvgCaptchaPoint(370, 130) ],
						[ new SmartSvgCaptchaPoint(370, 130), new SmartSvgCaptchaPoint(370, 0) ],
						[ new SmartSvgCaptchaPoint(370, 130), new SmartSvgCaptchaPoint(370, 0) ],
						[ new SmartSvgCaptchaPoint(370, 0), new SmartSvgCaptchaPoint(0, 0) ]
					]
				]
			],
			'G' => [
				'width' => 248,
				'height' => 353,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(248, 60), new SmartSvgCaptchaPoint(248, 60), new SmartSvgCaptchaPoint(211, 28), new SmartSvgCaptchaPoint(189, 17) ],
						[ new SmartSvgCaptchaPoint(189, 17), new SmartSvgCaptchaPoint(169, 7), new SmartSvgCaptchaPoint(146, 0), new SmartSvgCaptchaPoint(124, 4) ],
						[ new SmartSvgCaptchaPoint(124, 4), new SmartSvgCaptchaPoint(98, 8), new SmartSvgCaptchaPoint(74, 25), new SmartSvgCaptchaPoint(56, 45) ],
						[ new SmartSvgCaptchaPoint(56, 45), new SmartSvgCaptchaPoint(33, 70), new SmartSvgCaptchaPoint(20, 103), new SmartSvgCaptchaPoint(12, 135) ],
						[ new SmartSvgCaptchaPoint(56, 45), new SmartSvgCaptchaPoint(33, 70), new SmartSvgCaptchaPoint(20, 103), new SmartSvgCaptchaPoint(12, 135) ],
						[ new SmartSvgCaptchaPoint(12, 135), new SmartSvgCaptchaPoint(3, 175), new SmartSvgCaptchaPoint(0, 218), new SmartSvgCaptchaPoint(12, 257) ],
						[ new SmartSvgCaptchaPoint(12, 257), new SmartSvgCaptchaPoint(21, 287), new SmartSvgCaptchaPoint(39, 315), new SmartSvgCaptchaPoint(64, 333) ],
						[ new SmartSvgCaptchaPoint(64, 333), new SmartSvgCaptchaPoint(83, 347), new SmartSvgCaptchaPoint(108, 352), new SmartSvgCaptchaPoint(132, 352) ],
						[ new SmartSvgCaptchaPoint(64, 333), new SmartSvgCaptchaPoint(83, 347), new SmartSvgCaptchaPoint(108, 352), new SmartSvgCaptchaPoint(132, 352) ],
						[ new SmartSvgCaptchaPoint(132, 352), new SmartSvgCaptchaPoint(167, 353), new SmartSvgCaptchaPoint(236, 344), new SmartSvgCaptchaPoint(236, 344) ],
						[ new SmartSvgCaptchaPoint(207, 297), new SmartSvgCaptchaPoint(208, 326), new SmartSvgCaptchaPoint(181, 324), new SmartSvgCaptchaPoint(158, 321) ],
						[ new SmartSvgCaptchaPoint(158, 321), new SmartSvgCaptchaPoint(130, 317), new SmartSvgCaptchaPoint(74, 301), new SmartSvgCaptchaPoint(58, 278) ],
						[ new SmartSvgCaptchaPoint(158, 321), new SmartSvgCaptchaPoint(130, 317), new SmartSvgCaptchaPoint(74, 301), new SmartSvgCaptchaPoint(58, 278) ],
						[ new SmartSvgCaptchaPoint(58, 278), new SmartSvgCaptchaPoint(38, 248), new SmartSvgCaptchaPoint(39, 208), new SmartSvgCaptchaPoint(43, 174) ],
						[ new SmartSvgCaptchaPoint(43, 174), new SmartSvgCaptchaPoint(46, 136), new SmartSvgCaptchaPoint(56, 95), new SmartSvgCaptchaPoint(80, 65) ],
						[ new SmartSvgCaptchaPoint(80, 65), new SmartSvgCaptchaPoint(96, 47), new SmartSvgCaptchaPoint(119, 34), new SmartSvgCaptchaPoint(144, 36) ],
						[ new SmartSvgCaptchaPoint(80, 65), new SmartSvgCaptchaPoint(96, 47), new SmartSvgCaptchaPoint(119, 34), new SmartSvgCaptchaPoint(144, 36) ],
						[ new SmartSvgCaptchaPoint(144, 36), new SmartSvgCaptchaPoint(177, 38), new SmartSvgCaptchaPoint(224, 84), new SmartSvgCaptchaPoint(224, 84) ],
						[ new SmartSvgCaptchaPoint(224, 84), new SmartSvgCaptchaPoint(224, 84), new SmartSvgCaptchaPoint(218, 92), new SmartSvgCaptchaPoint(248, 60) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(236, 344), new SmartSvgCaptchaPoint(238, 202) ],
						[ new SmartSvgCaptchaPoint(238, 202), new SmartSvgCaptchaPoint(118, 200) ],
						[ new SmartSvgCaptchaPoint(118, 200), new SmartSvgCaptchaPoint(116, 231) ],
						[ new SmartSvgCaptchaPoint(116, 231), new SmartSvgCaptchaPoint(207, 231) ],
						[ new SmartSvgCaptchaPoint(116, 231), new SmartSvgCaptchaPoint(207, 231) ],
						[ new SmartSvgCaptchaPoint(207, 231), new SmartSvgCaptchaPoint(207, 297) ],
						[ new SmartSvgCaptchaPoint(248, 60), new SmartSvgCaptchaPoint(248, 60) ]
					]
				]
			],
			'H' => [
				'width' => 420,
				'height' => 550,
				'glyph_data' => [
					'lines' => [
						[ new SmartSvgCaptchaPoint(0, 0), new SmartSvgCaptchaPoint(0, 35) ],
						[ new SmartSvgCaptchaPoint(0, 35), new SmartSvgCaptchaPoint(55, 35) ],
						[ new SmartSvgCaptchaPoint(55, 35), new SmartSvgCaptchaPoint(55, 520) ],
						[ new SmartSvgCaptchaPoint(55, 520), new SmartSvgCaptchaPoint(0, 520) ],
						[ new SmartSvgCaptchaPoint(55, 520), new SmartSvgCaptchaPoint(0, 520) ],
						[ new SmartSvgCaptchaPoint(0, 520), new SmartSvgCaptchaPoint(0, 550) ],
						[ new SmartSvgCaptchaPoint(0, 550), new SmartSvgCaptchaPoint(150, 550) ],
						[ new SmartSvgCaptchaPoint(150, 550), new SmartSvgCaptchaPoint(150, 520) ],
						[ new SmartSvgCaptchaPoint(150, 550), new SmartSvgCaptchaPoint(150, 520) ],
						[ new SmartSvgCaptchaPoint(150, 520), new SmartSvgCaptchaPoint(95, 520) ],
						[ new SmartSvgCaptchaPoint(95, 520), new SmartSvgCaptchaPoint(95, 270) ],
						[ new SmartSvgCaptchaPoint(95, 270), new SmartSvgCaptchaPoint(325, 270) ],
						[ new SmartSvgCaptchaPoint(95, 270), new SmartSvgCaptchaPoint(325, 270) ],
						[ new SmartSvgCaptchaPoint(325, 270), new SmartSvgCaptchaPoint(325, 520) ],
						[ new SmartSvgCaptchaPoint(325, 520), new SmartSvgCaptchaPoint(265, 520) ],
						[ new SmartSvgCaptchaPoint(265, 520), new SmartSvgCaptchaPoint(265, 550) ],
						[ new SmartSvgCaptchaPoint(265, 520), new SmartSvgCaptchaPoint(265, 550) ],
						[ new SmartSvgCaptchaPoint(265, 550), new SmartSvgCaptchaPoint(420, 550) ],
						[ new SmartSvgCaptchaPoint(420, 550), new SmartSvgCaptchaPoint(420, 520) ],
						[ new SmartSvgCaptchaPoint(420, 520), new SmartSvgCaptchaPoint(370, 520) ],
						[ new SmartSvgCaptchaPoint(420, 520), new SmartSvgCaptchaPoint(370, 520) ],
						[ new SmartSvgCaptchaPoint(370, 520), new SmartSvgCaptchaPoint(370, 35) ],
						[ new SmartSvgCaptchaPoint(370, 35), new SmartSvgCaptchaPoint(420, 35) ],
						[ new SmartSvgCaptchaPoint(420, 35), new SmartSvgCaptchaPoint(420, 0) ],
						[ new SmartSvgCaptchaPoint(420, 35), new SmartSvgCaptchaPoint(420, 0) ],
						[ new SmartSvgCaptchaPoint(420, 0), new SmartSvgCaptchaPoint(265, 0) ],
						[ new SmartSvgCaptchaPoint(265, 0), new SmartSvgCaptchaPoint(265, 35) ],
						[ new SmartSvgCaptchaPoint(265, 35), new SmartSvgCaptchaPoint(325, 35) ],
						[ new SmartSvgCaptchaPoint(265, 35), new SmartSvgCaptchaPoint(325, 35) ],
						[ new SmartSvgCaptchaPoint(325, 35), new SmartSvgCaptchaPoint(325, 230) ],
						[ new SmartSvgCaptchaPoint(325, 230), new SmartSvgCaptchaPoint(95, 230) ],
						[ new SmartSvgCaptchaPoint(95, 230), new SmartSvgCaptchaPoint(95, 35) ],
						[ new SmartSvgCaptchaPoint(95, 230), new SmartSvgCaptchaPoint(95, 35) ],
						[ new SmartSvgCaptchaPoint(95, 35), new SmartSvgCaptchaPoint(150, 35) ],
						[ new SmartSvgCaptchaPoint(150, 35), new SmartSvgCaptchaPoint(150, 0) ],
						[ new SmartSvgCaptchaPoint(150, 0), new SmartSvgCaptchaPoint(0, 0) ],
						[ new SmartSvgCaptchaPoint(150, 0), new SmartSvgCaptchaPoint(0, 0) ]
					]
				]
			],
			'Q' => [
				'width' => 510,
				'height' => 600,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(70, 90), new SmartSvgCaptchaPoint(68, 202), new SmartSvgCaptchaPoint(71, 408), new SmartSvgCaptchaPoint(70, 440) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(0, 450), new SmartSvgCaptchaPoint(70, 540) ],
						[ new SmartSvgCaptchaPoint(70, 540), new SmartSvgCaptchaPoint(360, 540) ],
						[ new SmartSvgCaptchaPoint(360, 540), new SmartSvgCaptchaPoint(410, 600) ],
						[ new SmartSvgCaptchaPoint(410, 600), new SmartSvgCaptchaPoint(500, 600) ],
						[ new SmartSvgCaptchaPoint(410, 600), new SmartSvgCaptchaPoint(500, 600) ],
						[ new SmartSvgCaptchaPoint(500, 600), new SmartSvgCaptchaPoint(440, 530) ],
						[ new SmartSvgCaptchaPoint(440, 530), new SmartSvgCaptchaPoint(510, 460) ],
						[ new SmartSvgCaptchaPoint(510, 460), new SmartSvgCaptchaPoint(510, 70) ],
						[ new SmartSvgCaptchaPoint(510, 460), new SmartSvgCaptchaPoint(510, 70) ],
						[ new SmartSvgCaptchaPoint(510, 70), new SmartSvgCaptchaPoint(431, 2) ],
						[ new SmartSvgCaptchaPoint(431, 2), new SmartSvgCaptchaPoint(70, 0) ],
						[ new SmartSvgCaptchaPoint(70, 0), new SmartSvgCaptchaPoint(0, 70) ],
						[ new SmartSvgCaptchaPoint(70, 0), new SmartSvgCaptchaPoint(0, 70) ],
						[ new SmartSvgCaptchaPoint(0, 70), new SmartSvgCaptchaPoint(0, 450) ],
						[ new SmartSvgCaptchaPoint(70, 440), new SmartSvgCaptchaPoint(110, 480) ],
						[ new SmartSvgCaptchaPoint(110, 480), new SmartSvgCaptchaPoint(310, 480) ],
						[ new SmartSvgCaptchaPoint(110, 480), new SmartSvgCaptchaPoint(310, 480) ],
						[ new SmartSvgCaptchaPoint(310, 480), new SmartSvgCaptchaPoint(270, 420) ],
						[ new SmartSvgCaptchaPoint(270, 420), new SmartSvgCaptchaPoint(360, 420) ],
						[ new SmartSvgCaptchaPoint(360, 420), new SmartSvgCaptchaPoint(400, 480) ],
						[ new SmartSvgCaptchaPoint(360, 420), new SmartSvgCaptchaPoint(400, 480) ],
						[ new SmartSvgCaptchaPoint(400, 480), new SmartSvgCaptchaPoint(440, 430) ],
						[ new SmartSvgCaptchaPoint(440, 430), new SmartSvgCaptchaPoint(440, 90) ],
						[ new SmartSvgCaptchaPoint(440, 90), new SmartSvgCaptchaPoint(390, 50) ],
						[ new SmartSvgCaptchaPoint(440, 90), new SmartSvgCaptchaPoint(390, 50) ],
						[ new SmartSvgCaptchaPoint(390, 50), new SmartSvgCaptchaPoint(120, 50) ],
						[ new SmartSvgCaptchaPoint(120, 50), new SmartSvgCaptchaPoint(70, 90) ]
					]
				]
			],
			'S' => [
				'width' => 354,
				'height' => 745,
				'glyph_data' => [
					'cubic_splines' => [
						[ new SmartSvgCaptchaPoint(287, 366), new SmartSvgCaptchaPoint(250, 289), new SmartSvgCaptchaPoint(141, 264), new SmartSvgCaptchaPoint(99, 189) ],
						[ new SmartSvgCaptchaPoint(99, 189), new SmartSvgCaptchaPoint(88, 168), new SmartSvgCaptchaPoint(78, 141), new SmartSvgCaptchaPoint(85, 118) ],
						[ new SmartSvgCaptchaPoint(85, 118), new SmartSvgCaptchaPoint(92, 96), new SmartSvgCaptchaPoint(116, 82), new SmartSvgCaptchaPoint(135, 71) ],
						[ new SmartSvgCaptchaPoint(135, 71), new SmartSvgCaptchaPoint(157, 58), new SmartSvgCaptchaPoint(182, 50), new SmartSvgCaptchaPoint(207, 48) ],
						[ new SmartSvgCaptchaPoint(135, 71), new SmartSvgCaptchaPoint(157, 58), new SmartSvgCaptchaPoint(182, 50), new SmartSvgCaptchaPoint(207, 48) ],
						[ new SmartSvgCaptchaPoint(207, 48), new SmartSvgCaptchaPoint(244, 44), new SmartSvgCaptchaPoint(288, 42), new SmartSvgCaptchaPoint(319, 63) ],
						[ new SmartSvgCaptchaPoint(319, 63), new SmartSvgCaptchaPoint(335, 73), new SmartSvgCaptchaPoint(348, 128), new SmartSvgCaptchaPoint(347, 110) ],
						[ new SmartSvgCaptchaPoint(346, 44), new SmartSvgCaptchaPoint(345, 21), new SmartSvgCaptchaPoint(354, 16), new SmartSvgCaptchaPoint(293, 15) ],
						[ new SmartSvgCaptchaPoint(346, 44), new SmartSvgCaptchaPoint(345, 21), new SmartSvgCaptchaPoint(354, 16), new SmartSvgCaptchaPoint(293, 15) ],
						[ new SmartSvgCaptchaPoint(293, 15), new SmartSvgCaptchaPoint(293, 15), new SmartSvgCaptchaPoint(161, 0), new SmartSvgCaptchaPoint(88, 58) ],
						[ new SmartSvgCaptchaPoint(88, 58), new SmartSvgCaptchaPoint(16, 116), new SmartSvgCaptchaPoint(27, 169), new SmartSvgCaptchaPoint(39, 196) ],
						[ new SmartSvgCaptchaPoint(39, 196), new SmartSvgCaptchaPoint(74, 277), new SmartSvgCaptchaPoint(183, 304), new SmartSvgCaptchaPoint(233, 377) ],
						[ new SmartSvgCaptchaPoint(39, 196), new SmartSvgCaptchaPoint(74, 277), new SmartSvgCaptchaPoint(183, 304), new SmartSvgCaptchaPoint(233, 377) ],
						[ new SmartSvgCaptchaPoint(233, 377), new SmartSvgCaptchaPoint(248, 400), new SmartSvgCaptchaPoint(256, 427), new SmartSvgCaptchaPoint(262, 454) ],
						[ new SmartSvgCaptchaPoint(262, 454), new SmartSvgCaptchaPoint(270, 493), new SmartSvgCaptchaPoint(272, 533), new SmartSvgCaptchaPoint(267, 572) ],
						[ new SmartSvgCaptchaPoint(267, 572), new SmartSvgCaptchaPoint(261, 609), new SmartSvgCaptchaPoint(265, 640), new SmartSvgCaptchaPoint(228, 679) ],
						[ new SmartSvgCaptchaPoint(267, 572), new SmartSvgCaptchaPoint(261, 609), new SmartSvgCaptchaPoint(265, 640), new SmartSvgCaptchaPoint(228, 679) ],
						[ new SmartSvgCaptchaPoint(228, 679), new SmartSvgCaptchaPoint(182, 727), new SmartSvgCaptchaPoint(84, 731), new SmartSvgCaptchaPoint(28, 695) ],
						[ new SmartSvgCaptchaPoint(28, 695), new SmartSvgCaptchaPoint(6, 681), new SmartSvgCaptchaPoint(0, 604), new SmartSvgCaptchaPoint(1, 623) ],
						[ new SmartSvgCaptchaPoint(3, 691), new SmartSvgCaptchaPoint(6, 745), new SmartSvgCaptchaPoint(67, 742), new SmartSvgCaptchaPoint(94, 742) ],
						[ new SmartSvgCaptchaPoint(3, 691), new SmartSvgCaptchaPoint(6, 745), new SmartSvgCaptchaPoint(67, 742), new SmartSvgCaptchaPoint(94, 742) ],
						[ new SmartSvgCaptchaPoint(94, 742), new SmartSvgCaptchaPoint(124, 741), new SmartSvgCaptchaPoint(153, 741), new SmartSvgCaptchaPoint(182, 741) ],
						[ new SmartSvgCaptchaPoint(182, 741), new SmartSvgCaptchaPoint(235, 740), new SmartSvgCaptchaPoint(287, 664), new SmartSvgCaptchaPoint(307, 605) ],
						[ new SmartSvgCaptchaPoint(307, 605), new SmartSvgCaptchaPoint(333, 530), new SmartSvgCaptchaPoint(322, 438), new SmartSvgCaptchaPoint(287, 366) ],
						[ new SmartSvgCaptchaPoint(307, 605), new SmartSvgCaptchaPoint(333, 530), new SmartSvgCaptchaPoint(322, 438), new SmartSvgCaptchaPoint(287, 366) ]
					],
					'lines' => [
						[ new SmartSvgCaptchaPoint(347, 110), new SmartSvgCaptchaPoint(346, 44) ],
						[ new SmartSvgCaptchaPoint(1, 623), new SmartSvgCaptchaPoint(3, 691) ],
						[ new SmartSvgCaptchaPoint(287, 366), new SmartSvgCaptchaPoint(287, 366) ]
					]
				]
			],
			'W' => [
				'width' => 520,
				'height' => 390,
				'glyph_data' => [
					'lines' => [
						[ new SmartSvgCaptchaPoint(0, 0), new SmartSvgCaptchaPoint(130, 390) ],
						[ new SmartSvgCaptchaPoint(130, 390), new SmartSvgCaptchaPoint(190, 390) ],
						[ new SmartSvgCaptchaPoint(190, 390), new SmartSvgCaptchaPoint(270, 120) ],
						[ new SmartSvgCaptchaPoint(270, 120), new SmartSvgCaptchaPoint(350, 390) ],
						[ new SmartSvgCaptchaPoint(270, 120), new SmartSvgCaptchaPoint(350, 390) ],
						[ new SmartSvgCaptchaPoint(350, 390), new SmartSvgCaptchaPoint(410, 390) ],
						[ new SmartSvgCaptchaPoint(410, 390), new SmartSvgCaptchaPoint(520, 0) ],
						[ new SmartSvgCaptchaPoint(520, 0), new SmartSvgCaptchaPoint(430, 10) ],
						[ new SmartSvgCaptchaPoint(520, 0), new SmartSvgCaptchaPoint(430, 10) ],
						[ new SmartSvgCaptchaPoint(430, 10), new SmartSvgCaptchaPoint(380, 290) ],
						[ new SmartSvgCaptchaPoint(380, 290), new SmartSvgCaptchaPoint(300, 80) ],
						[ new SmartSvgCaptchaPoint(300, 80), new SmartSvgCaptchaPoint(240, 80) ],
						[ new SmartSvgCaptchaPoint(300, 80), new SmartSvgCaptchaPoint(240, 80) ],
						[ new SmartSvgCaptchaPoint(240, 80), new SmartSvgCaptchaPoint(160, 290) ],
						[ new SmartSvgCaptchaPoint(160, 290), new SmartSvgCaptchaPoint(90, 10) ],
						[ new SmartSvgCaptchaPoint(90, 10), new SmartSvgCaptchaPoint(0, 0) ],
						[ new SmartSvgCaptchaPoint(90, 10), new SmartSvgCaptchaPoint(0, 0) ]
					]
				]
			],
			'X' => [
				'width' => 300,
				'height' => 400,
				'glyph_data' => [
					'lines' => [
						[ new SmartSvgCaptchaPoint(10, 0), new SmartSvgCaptchaPoint(130, 200) ],
						[ new SmartSvgCaptchaPoint(130, 200), new SmartSvgCaptchaPoint(0, 390) ],
						[ new SmartSvgCaptchaPoint(0, 390), new SmartSvgCaptchaPoint(40, 390) ],
						[ new SmartSvgCaptchaPoint(40, 390), new SmartSvgCaptchaPoint(150, 220) ],
						[ new SmartSvgCaptchaPoint(40, 390), new SmartSvgCaptchaPoint(150, 220) ],
						[ new SmartSvgCaptchaPoint(150, 220), new SmartSvgCaptchaPoint(260, 400) ],
						[ new SmartSvgCaptchaPoint(260, 400), new SmartSvgCaptchaPoint(300, 400) ],
						[ new SmartSvgCaptchaPoint(300, 400), new SmartSvgCaptchaPoint(170, 190) ],
						[ new SmartSvgCaptchaPoint(300, 400), new SmartSvgCaptchaPoint(170, 190) ],
						[ new SmartSvgCaptchaPoint(170, 190), new SmartSvgCaptchaPoint(296, 0) ],
						[ new SmartSvgCaptchaPoint(296, 0), new SmartSvgCaptchaPoint(260, 0) ],
						[ new SmartSvgCaptchaPoint(260, 0), new SmartSvgCaptchaPoint(150, 170) ],
						[ new SmartSvgCaptchaPoint(260, 0), new SmartSvgCaptchaPoint(150, 170) ],
						[ new SmartSvgCaptchaPoint(150, 170), new SmartSvgCaptchaPoint(50, 0) ],
						[ new SmartSvgCaptchaPoint(50, 0), new SmartSvgCaptchaPoint(10, 0) ]
					]
				]
			],
		];
		//--
		return (array) $this->alphabet;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Shuffle an array while preserving key/value mappings.
	 *
	 * @param array $array The array to shuffle.
	 * @return boolean Whether the action was successful.
	 */
	private function _shuffle_assoc(&$array) {
		//--
		$keys = array_keys($array);
		//--
		shuffle($keys);
		foreach($keys as $kk => $key) {
			$new[$key] = $array[$key];
		} //end foreach
		$array = $new;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Copies arrays while shallow copying their values.
	 * Used just for extra _glyph_fragments
	 *
	 * http://stackoverflow.com/questions/6418903/how-to-clone-an-array-of-objects-in-php
	 *
	 * @param array $arr The array to copy
	 * @return array
	 */
	private function arr_copy($arr) {
		//--
		$newArray = [];
		//--
		foreach($arr as $key => $value) {
			if(is_array($value)) {
				$newArray[$key] = $this->arr_copy($value);
			} elseif(is_object($value)) {
				$newArray[$key] = clone $value;
			} else {
				$newArray[$key] = $value;
			} //end if else
		} //end foreach
		//--
		return (array) $newArray;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Secure replacement for array_rand().
	 *
	 * @param array $input The input array.
	 * @param int $num_el (Optional). How many elements to choose from the input array.
	 * @param bool $allow_duplicates (Optional). Whether to allow choosing random values more than once.
	 * @return array Returns the keys of the picked elements.
	 */
	private function arr_rand_safe($input, $num_el=1, $allow_duplicates=false) {
		//--
		if($num_el > Smart::array_size($input)) {
			$num_el = Smart::array_size($input);
		} //end if
		$keys = (array) array_keys($input);
		$chosen_keys = [];
		if($allow_duplicates) {
			for($i=0; $i<$num_el; $i++) {
				$chosen_keys[] = $keys[Smart::random_number(0, (int)(Smart::array_size($input) - 1))];
			} //end for
		} else {
			$already_used = [];
			for($i=0; $i<$num_el; $i++) {
				$key = $this->_pick_remaining($keys, $already_used);
				$chosen_keys[] = $key;
				$already_used[] = $key;
			} //end for
		} //end if else
		//--
		return (array) $chosen_keys;
		//--
	} //END FUNCTION
	//================================================================


	//================================================================
	/**
	 * Little helper function for arr_rand_safe().
	 *
	 * @return mixed Returns a key that is in $key_pool but no in $already_picked
	 */
	private function _pick_remaining($key_pool, $already_picked) {
		//--
		$remaining = array_values(array_diff($key_pool, $already_picked));
		//--
		return $remaining[Smart::random_number(0, (int)(Smart::array_size($remaining) - 1))]; // mixed
		//--
	} //END FUNCTION
	//================================================================


} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


//=====================================================================================
//===================================================================================== CLASS START
//=====================================================================================


/**
 * Class Smart SVG Captcha Point Object
 *
 * @access 		private
 * @internal
 *
 * @version 	v.20250714
 *
 */
final class SmartSvgCaptchaPoint {
	//--
	public $x;
	public $y;
	//--
	public function __construct($x, $y) {
		//--
		if(!Smart::is_nscalar($x)) {
			$x = null;
		} //end if
		if(!Smart::is_nscalar($y)) {
			$y = null;
		} //end if
		//--
		$this->x = (float) $x;
		$this->y = (float) $y;
		//--
	} //END FUNCTION
	//--
	public function __toString() : string {
		//--
		return (string) 'Point(x='.$this->x.',y='.$this->y.')';
		//--
	} //END FUNCTION
	//--
	public function is_equal($p) : bool {
		//--
		if($p instanceof SmartSvgCaptchaPoint) {
			return (bool) (($this->x == $p->x) && ($this->y == $p->y));
		} //end if
		//--
		return false;
		//--
	} //END FUNCTION
	//--
} //END CLASS


//=====================================================================================
//===================================================================================== CLASS END
//=====================================================================================


// end of php code
