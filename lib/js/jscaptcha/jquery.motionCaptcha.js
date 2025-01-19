
/**
 * jQuery Smart MotionCAPTCHA r.20250107
 * (c) 2019-present unix-world.org
 *
 * Based on v0.2 proof of concept of https://github.com/josscrowcroft/MotionCAPTCHA
 * (c) 2011 Joss Crowcroft
 */

jQuery.fn.motionCaptcha || (function($) {

	/**
	 * Main plugin function definition
	 */
	$.fn.motionCaptcha = function(options) {


		// An array of shape names that you want MotionCAPTCHA to use:
		var shapes = ['triangle', 'x', 'rectangle', 'circle', 'check', 'caret', 'zigzag', 'arrow', 'leftbracket', 'rightbracket', 'v', 'delete', 'leftbrace', 'rightbrace', 'star', 'loop'];

		var numTries = 0;


		/**
		 * Act on matched form element:
		 * This could be set up to iterate over multiple elements, but tbh would it ever be useful?
		 */
		return this.each(function() {

			// Build main options before element iteration:
			var opts = $.extend({}, $.fn.motionCaptcha.defaults, options);

			// Ensure option ID params are valid #selectors:
			opts.canvasId = '#' + opts.canvasId.replace(/\#/g, '');


			// Plugin setup:


			// Set up Harmony vars:
			var brush,
				locked = false;

			// Set up MotionCAPTCHA form and jQuery elements:
			var $body = $('body'),
				$canvas = $(String(opts.canvasId));

			// Set up MotionCAPTCHA canvas vars:
			var canvasWidth = $canvas.width(),
				canvasHeight = $canvas.height(),
				borderLeftWidth = 1 * $canvas.css('borderLeftWidth').replace('px', ''),
				borderTopWidth = 1 * $canvas.css('borderTopWidth').replace('px', '');


			// Canvas setup:


			// Set the canvas DOM element's dimensions to match the display width/height (pretty important):
			$canvas[0].width = canvasWidth;
			$canvas[0].height = canvasHeight;

			// Get DOM reference to canvas context:
			var ctx = $canvas[0].getContext('2d');

			// Add canvasWidth and canvasHeight values to context, for Ribbon brush:
			ctx.canvasWidth = canvasWidth;
			ctx.canvasHeight = canvasHeight;

			// Set canvas context font and fillStyle:
			ctx.font = opts.canvasFont;
			ctx.textAlign = 'left';

			var stepForward = function() {
				// Test Tries
				if(numTries >= Number(opts.maxTries)) {
					$canvas.addClass('mc-invalid');
					setTimeout(() => {
						opts.onCompletion($canvas, ctx, 0);
					}, 500);
					return true;
				} //end if
				return false;
			}

			// Set random shape
			$canvas.on('refresh.motionCaptcha', function(){

				if(locked) {
					return false;
				}

				// Lock the canvas
				locked = 1;

				// Incr Tries
				numTries++;

				// Test Tries
				if(stepForward()) {
					return;
				} //end if

				ctx.clearRect(0, 0, canvasWidth, canvasHeight);
				$canvas.removeClass('mc-novalid mc-invalid mc-valid').attr('title', opts.changeMsg);
				$canvas.removeClass(String(shapes.join(' ')));
				$canvas.addClass(shapes[Math.floor(Math.random() * (shapes.length))]); // regen shape

				// Unlock the canvas
				locked = 0;

			}).dblclick(function(){

				$canvas.trigger('refresh');

			});

			$canvas.addClass(shapes[Math.floor(Math.random() * (shapes.length))]); // init shape


			// Set up Dollar Recognizer and drawing vars:
			var _isDown = false,
				_holdStill = false,
				_points = [],
				_r = new DollarRecognizer();


			// Create the Harmony Ribbon brush:
			brush = new Ribbon(ctx);


			// Mousedown event
			// Start Harmony brushstroke and begin recording DR points:
			var touchStartEvent = function(event) {

				if(locked) {
					return false;
				}

				// Prevent default action:
				event.preventDefault();

				// Get mouse position inside the canvas:
				var pos = getPos(event),
					x = pos[0],
					y = pos[1];

				// Internal drawing var
				_isDown = true;

				// Prevent jumpy-touch bug on android, no effect on other platforms:
				_holdStill = true;

				// Disable text selection:
				$('body').addClass('mc-noselect');

				// Clear canvas:
				ctx.clearRect(0, 0, canvasWidth, canvasHeight);

				// Start brushstroke:
				brush.strokeStart(x, y);

				// Remove 'mc-invalid' and 'mc-valid' classes from canvas:
				$canvas.removeClass('mc-novalid mc-invalid mc-valid').attr('title', opts.changeMsg);

				// Add the first point to the points array:
				_points = [new Point(x, y)];

				return false;

			}; // mousedown/touchstart event


			// Mousemove event:
			var touchMoveEvent = function(event) {

				if ( _holdStill ) {
					return _holdStill = 0;
				}

				// If mouse is down and canvas not locked:
				if ( !locked && _isDown ) {

					// Prevent default action:
					event.preventDefault();

					// Get mouse position inside the canvas:
					var pos = getPos(event),
						x = pos[0],
						y = pos[1];

					// Append point to points array:
					_points[_points.length] = new Point(x, y);

					// Do brushstroke:
					brush.stroke(x, y);

				}

				return false;

			}; // mousemove/touchmove event


			// Mouseup event:
			var touchEndEvent = function(event) {

				// If mouse is down and canvas not locked:
				if (!locked && _isDown) {

					_isDown = false;

					// Allow text-selection again:
					$('body').removeClass('mc-noselect');

					// Dollar Recognizer result:
					if (_points.length >= 10) {

						var result = _r.Recognize(_points);
						// Check result:

						if ($canvas.attr('class').match(result.Name) && result.Attainment >= Number(opts.similarity)) { // OK

							// Lock the canvas:
							locked = 1;

							// Destroy the Harmony brush (give it time to finish drawing)
							setTimeout(brush.destroy, 500);

							// Add 'mc-valid' class to canvas:
							$canvas.addClass('mc-valid').attr('title', '');

							// Write success message into canvas:
							ctx.fillStyle = opts.canvasTextColor;
							ctx.fillText(opts.completedMsg, 10, 24);

							// Call the onCompletion function to handle the rest of the business:
							// Pass in the form, the canvas, the canvas context:
							opts.onCompletion($canvas, ctx, result.Attainment);

						} else { // Warn: shape does not match

							// Incr Tries
							numTries++;

							// test Forward
							stepForward();

							// Lock the canvas:
							locked = 1;

							// Add 'mc-invalid' class to canvas:
							$canvas.addClass('mc-novalid');

							// Write error message into canvas:
							ctx.fillStyle = opts.canvasErrTextColor;
							ctx.fillText(opts.warnMsg, 10, 24);
							setTimeout(function(){ ctx.clearRect(0, 0, canvasWidth, canvasHeight); $canvas.removeClass('mc-novalid'); locked = 0; }, 1500);

							// Pass off to the error callback to finish up:
							opts.onError($canvas, ctx);
						}

					} else if (_points.length >= 5) { // ERR: no drawing or too less drawing (less then 5 points)

						// Incr Tries
						numTries++;

						// test Forward
						stepForward();

						// Lock the canvas:
						locked = 1;

						// Add 'mc-invalid' class to canvas:
						$canvas.addClass('mc-invalid');

						// Write error message into canvas:
						ctx.fillStyle = opts.canvasErrTextColor;
						ctx.fillText(opts.errorMsg, 10, 24);
						setTimeout(function(){ ctx.clearRect(0, 0, canvasWidth, canvasHeight); $canvas.removeClass('mc-invalid'); locked = 0; }, 1500);

						// Pass off to the error callback to finish up:
						opts.onError($canvas, ctx);
					}

				}

				return false;

			}; // mouseup/touchend event


			// Bind events to canvas:
			$canvas.bind({
				mousedown:  touchStartEvent,
				mousemove: touchMoveEvent,
				mouseup:  touchEndEvent,
			});


			// Mobile touch events:
			$canvas[0].addEventListener('touchstart', touchStartEvent, false);
			$canvas[0].addEventListener('touchmove', touchMoveEvent, false);
			$canvas[0].addEventListener('touchend', touchEndEvent, false);


			/**
			 * Get X/Y mouse position, relative to (/inside) the canvas
			 *
			 * Handles cross-browser quirks rather nicely, I feel.
			 *
			 * @todo For 1.0, if no way to obtain coordinates, don't activate MotionCAPTCHA.
			 */
			function getPos(event) {
				var x, y;
				// Check for mobile first to avoid android jumpy-touch bug (iOS / Android):
				if ( event.touches && event.touches.length > 0 ) {
					// iOS/android uses event.touches, relative to entire page:
					x = event.touches[0].pageX - $canvas.offset().left + borderLeftWidth;
					y = event.touches[0].pageY - $canvas.offset().top + borderTopWidth;
				} else if ( event.offsetX ) {
					// Chrome/Safari give the event offset relative to the target event:
					x = event.offsetX - borderLeftWidth;
					y = event.offsetY - borderTopWidth;
				} else {
					// Otherwise, subtract page click from canvas offset (Firefox uses this):
					x = event.pageX - $canvas.offset().left - borderLeftWidth;
					y = event.pageY - $canvas.offset().top - borderTopWidth;
				}
				return [x,y];
			}

		}); // this.each

	} // end main plugin function


	/**
	 * Exposed default plugin settings, which can be overridden in plugin call.
	 */
	$.fn.motionCaptcha.defaults = {

		canvasId: '#mc-canvas', // The ID of the MotionCAPTCHA canvas element

		similarity: 0.82, // dont't change this, it is calibrated !
		maxTries: 7, // number of tries before done err

		// Canvas vars:
		canvasFont: 'bold 10px mono',
		canvasTextColor: '#777777',
		canvasErrTextColor: '#EF4756',

		// These messages are displayed inside the canvas after a user finishes drawing:
		errorMsg: 'Invalid draw ...',
		warnMsg: 'Try again ...',
		completedMsg: 'Done ...',
		changeMsg: 'Double-Click to change the figure',

		// Callback function to execute when a user successfully draws the shape
		// Passed in the form, the canvas and the canvas context
		// Scope (this) is active plugin options object (opts)
		// NB: The default onCompletion callback function enables the submit button, and adds the form action attribute:
		onCompletion: function($canvas, ctx, attainment) {
			return;
		},

		// Callback function to execute when a user fails to draw the shape
		// Passed in the form, the canvas and the canvas context
		// Scope (this) is active plugin options object (opts)
		onError: function($canvas, ctx) {
			return;
		}
	};


	//=====


	/**
	 * Harmony - Ribbon Brush class, ribbon.js
	 * https://github.com/mrdoob/harmony
	 * License: GNU General Public License
	 * Version: 08 Apr 2010
	 */

	function Ribbon(ctx) {
		this.init(ctx);
	}

	Ribbon.prototype = {

		ctx: null,
		X: null,
		Y: null,
		painters: null,
		interval: null,

		init: function(ctx) {
			var scope = this,
				brushSize = 2,
				strokeColor = [77, 88, 99];
			this.ctx = ctx;
			this.ctx.globalCompositeOperation = 'source-over';
			this.X = this.ctx.canvasWidth / 2;
			this.Y = this.ctx.canvasHeight / 2;
			this.painters = [];
			// Draw each of the lines:
			for(var i = 0; i < 38; i++) {
				this.painters.push({
					dx: this.ctx.canvasWidth / 2,
					dy: this.ctx.canvasHeight / 2,
					ax: 0,
					ay: 0,
					div: 0.1,
					ease: Math.random() * 0.18 + 0.60
				});
			}
			// Set the ticker:
			this.interval = setInterval(function(){ update(); }, 1000/60);
			var update = function() {
				var i;
				scope.ctx.lineWidth = brushSize;
				scope.ctx.strokeStyle = 'rgba(' + strokeColor[0] + ', ' + strokeColor[1] + ', ' + strokeColor[2] + ', ' + 0.06 + ')';
				for(i = 0; i < scope.painters.length; i++) {
					scope.ctx.beginPath();
					scope.ctx.moveTo(scope.painters[i].dx, scope.painters[i].dy);
					scope.painters[i].dx -= scope.painters[i].ax = (scope.painters[i].ax + (scope.painters[i].dx - scope.X) * scope.painters[i].div) * scope.painters[i].ease;
					scope.painters[i].dy -= scope.painters[i].ay = (scope.painters[i].ay + (scope.painters[i].dy - scope.Y) * scope.painters[i].div) * scope.painters[i].ease;
					scope.ctx.lineTo(scope.painters[i].dx, scope.painters[i].dy);
					scope.ctx.stroke();
				}
			}
		},

		destroy: function() {
			clearInterval(this.interval);
		},

		strokeStart: function( X, Y ) {
			this.X = X;
			this.Y = Y
			for(var i = 0; i < this.painters.length; i++) {
				this.painters[i].dx = X;
				this.painters[i].dy = Y;
			}
			this.shouldDraw = true;
		},

		stroke: function( X, Y ) {
			this.X = X;
			this.Y = Y;
		}

	};


	//=====


	/**
	 * The $1 Unistroke Recognizer (JavaScript version)
	 * http://depts.washington.edu/acelab/proj/dollar/index.html
	 * (c) 2007-2018, Jacob O. Wobbrock, Andrew D. Wilson and Yang Li - all rights reserved.
	 * License: New BSD License
	 * Version: July 14, 2018
	 */


	// Point class
	// constructor
	function Point(x, y) {
		this.X = x;
		this.Y = y;
	}


	// Rectangle class
	// constructor
	function Rectangle(x, y, width, height) {
		this.X = x;
		this.Y = y;
		this.Width = width;
		this.Height = height;
	}


	// Unistroke class: a unistroke template
	// constructor
	function Unistroke(name, points)  {
		this.Name = name;
		this.Points = Resample(points, NumPoints);
		var radians = IndicativeAngle(this.Points);
		this.Points = RotateBy(this.Points, -radians);
		this.Points = ScaleTo(this.Points, SquareSize);
		this.Points = TranslateTo(this.Points, Origin);
		this.Vector = Vectorize(this.Points); // for Protractor
	}


	// Result class
	// constructor
	function Result(name, attainment, ms) {
		this.Name = name;
		this.Attainment = attainment;
		this.Time = ms;
	}


	// DollarRecognizer constants
	var NumUnistrokes = 16;
	var NumPoints = 64;
	var SquareSize = 250.0;
	var Origin = new Point(0,0);
	var Diagonal = Math.sqrt(SquareSize * SquareSize + SquareSize * SquareSize);
	var HalfDiagonal = 0.5 * Diagonal;
	var AngleRange = Deg2Rad(45.0);
	var AnglePrecision = Deg2Rad(2.0);
	var Phi = 0.5 * (-1.0 + Math.sqrt(5.0)); // Golden Ratio


	// DollarRecognizer class
	// constructor
	function DollarRecognizer() {

		//-- one built-in unistroke per gesture type
		this.Unistrokes = new Array(NumUnistrokes);
		this.Unistrokes[0] = new Unistroke('triangle', new Array(new Point(137,139),new Point(135,141),new Point(133,144),new Point(132,146),new Point(130,149),new Point(128,151),new Point(126,155),new Point(123,160),new Point(120,166),new Point(116,171),new Point(112,177),new Point(107,183),new Point(102,188),new Point(100,191),new Point(95,195),new Point(90,199),new Point(86,203),new Point(82,206),new Point(80,209),new Point(75,213),new Point(73,213),new Point(70,216),new Point(67,219),new Point(64,221),new Point(61,223),new Point(60,225),new Point(62,226),new Point(65,225),new Point(67,226),new Point(74,226),new Point(77,227),new Point(85,229),new Point(91,230),new Point(99,231),new Point(108,232),new Point(116,233),new Point(125,233),new Point(134,234),new Point(145,233),new Point(153,232),new Point(160,233),new Point(170,234),new Point(177,235),new Point(179,236),new Point(186,237),new Point(193,238),new Point(198,239),new Point(200,237),new Point(202,239),new Point(204,238),new Point(206,234),new Point(205,230),new Point(202,222),new Point(197,216),new Point(192,207),new Point(186,198),new Point(179,189),new Point(174,183),new Point(170,178),new Point(164,171),new Point(161,168),new Point(154,160),new Point(148,155),new Point(143,150),new Point(138,148),new Point(136,148)));
		this.Unistrokes[1] = new Unistroke('x', new Array(new Point(87,142),new Point(89,145),new Point(91,148),new Point(93,151),new Point(96,155),new Point(98,157),new Point(100,160),new Point(102,162),new Point(106,167),new Point(108,169),new Point(110,171),new Point(115,177),new Point(119,183),new Point(123,189),new Point(127,193),new Point(129,196),new Point(133,200),new Point(137,206),new Point(140,209),new Point(143,212),new Point(146,215),new Point(151,220),new Point(153,222),new Point(155,223),new Point(157,225),new Point(158,223),new Point(157,218),new Point(155,211),new Point(154,208),new Point(152,200),new Point(150,189),new Point(148,179),new Point(147,170),new Point(147,158),new Point(147,148),new Point(147,141),new Point(147,136),new Point(144,135),new Point(142,137),new Point(140,139),new Point(135,145),new Point(131,152),new Point(124,163),new Point(116,177),new Point(108,191),new Point(100,206),new Point(94,217),new Point(91,222),new Point(89,225),new Point(87,226),new Point(87,224)));
		this.Unistrokes[2] = new Unistroke('rectangle', new Array(new Point(78,149),new Point(78,153),new Point(78,157),new Point(78,160),new Point(79,162),new Point(79,164),new Point(79,167),new Point(79,169),new Point(79,173),new Point(79,178),new Point(79,183),new Point(80,189),new Point(80,193),new Point(80,198),new Point(80,202),new Point(81,208),new Point(81,210),new Point(81,216),new Point(82,222),new Point(82,224),new Point(82,227),new Point(83,229),new Point(83,231),new Point(85,230),new Point(88,232),new Point(90,233),new Point(92,232),new Point(94,233),new Point(99,232),new Point(102,233),new Point(106,233),new Point(109,234),new Point(117,235),new Point(123,236),new Point(126,236),new Point(135,237),new Point(142,238),new Point(145,238),new Point(152,238),new Point(154,239),new Point(165,238),new Point(174,237),new Point(179,236),new Point(186,235),new Point(191,235),new Point(195,233),new Point(197,233),new Point(200,233),new Point(201,235),new Point(201,233),new Point(199,231),new Point(198,226),new Point(198,220),new Point(196,207),new Point(195,195),new Point(195,181),new Point(195,173),new Point(195,163),new Point(194,155),new Point(192,145),new Point(192,143),new Point(192,138),new Point(191,135),new Point(191,133),new Point(191,130),new Point(190,128),new Point(188,129),new Point(186,129),new Point(181,132),new Point(173,131),new Point(162,131),new Point(151,132),new Point(149,132),new Point(138,132),new Point(136,132),new Point(122,131),new Point(120,131),new Point(109,130),new Point(107,130),new Point(90,132),new Point(81,133),new Point(76,133)));
		this.Unistrokes[3] = new Unistroke('circle', new Array(new Point(127,141),new Point(124,140),new Point(120,139),new Point(118,139),new Point(116,139),new Point(111,140),new Point(109,141),new Point(104,144),new Point(100,147),new Point(96,152),new Point(93,157),new Point(90,163),new Point(87,169),new Point(85,175),new Point(83,181),new Point(82,190),new Point(82,195),new Point(83,200),new Point(84,205),new Point(88,213),new Point(91,216),new Point(96,219),new Point(103,222),new Point(108,224),new Point(111,224),new Point(120,224),new Point(133,223),new Point(142,222),new Point(152,218),new Point(160,214),new Point(167,210),new Point(173,204),new Point(178,198),new Point(179,196),new Point(182,188),new Point(182,177),new Point(178,167),new Point(170,150),new Point(163,138),new Point(152,130),new Point(143,129),new Point(140,131),new Point(129,136),new Point(126,139)));
		this.Unistrokes[4] = new Unistroke('check', new Array(new Point(91,185),new Point(93,185),new Point(95,185),new Point(97,185),new Point(100,188),new Point(102,189),new Point(104,190),new Point(106,193),new Point(108,195),new Point(110,198),new Point(112,201),new Point(114,204),new Point(115,207),new Point(117,210),new Point(118,212),new Point(120,214),new Point(121,217),new Point(122,219),new Point(123,222),new Point(124,224),new Point(126,226),new Point(127,229),new Point(129,231),new Point(130,233),new Point(129,231),new Point(129,228),new Point(129,226),new Point(129,224),new Point(129,221),new Point(129,218),new Point(129,212),new Point(129,208),new Point(130,198),new Point(132,189),new Point(134,182),new Point(137,173),new Point(143,164),new Point(147,157),new Point(151,151),new Point(155,144),new Point(161,137),new Point(165,131),new Point(171,122),new Point(174,118),new Point(176,114),new Point(177,112),new Point(177,114),new Point(175,116),new Point(173,118)));
		this.Unistrokes[5] = new Unistroke('caret', new Array(new Point(79,245),new Point(79,242),new Point(79,239),new Point(80,237),new Point(80,234),new Point(81,232),new Point(82,230),new Point(84,224),new Point(86,220),new Point(86,218),new Point(87,216),new Point(88,213),new Point(90,207),new Point(91,202),new Point(92,200),new Point(93,194),new Point(94,192),new Point(96,189),new Point(97,186),new Point(100,179),new Point(102,173),new Point(105,165),new Point(107,160),new Point(109,158),new Point(112,151),new Point(115,144),new Point(117,139),new Point(119,136),new Point(119,134),new Point(120,132),new Point(121,129),new Point(122,127),new Point(124,125),new Point(126,124),new Point(129,125),new Point(131,127),new Point(132,130),new Point(136,139),new Point(141,154),new Point(145,166),new Point(151,182),new Point(156,193),new Point(157,196),new Point(161,209),new Point(162,211),new Point(167,223),new Point(169,229),new Point(170,231),new Point(173,237),new Point(176,242),new Point(177,244),new Point(179,250),new Point(181,255),new Point(182,257)));
		this.Unistrokes[6] = new Unistroke('zigzag', new Array(new Point(307,216),new Point(333,186),new Point(356,215),new Point(375,186),new Point(399,216),new Point(418,186)));
		this.Unistrokes[7] = new Unistroke('arrow', new Array(new Point(68,222),new Point(70,220),new Point(73,218),new Point(75,217),new Point(77,215),new Point(80,213),new Point(82,212),new Point(84,210),new Point(87,209),new Point(89,208),new Point(92,206),new Point(95,204),new Point(101,201),new Point(106,198),new Point(112,194),new Point(118,191),new Point(124,187),new Point(127,186),new Point(132,183),new Point(138,181),new Point(141,180),new Point(146,178),new Point(154,173),new Point(159,171),new Point(161,170),new Point(166,167),new Point(168,167),new Point(171,166),new Point(174,164),new Point(177,162),new Point(180,160),new Point(182,158),new Point(183,156),new Point(181,154),new Point(178,153),new Point(171,153),new Point(164,153),new Point(160,153),new Point(150,154),new Point(147,155),new Point(141,157),new Point(137,158),new Point(135,158),new Point(137,158),new Point(140,157),new Point(143,156),new Point(151,154),new Point(160,152),new Point(170,149),new Point(179,147),new Point(185,145),new Point(192,144),new Point(196,144),new Point(198,144),new Point(200,144),new Point(201,147),new Point(199,149),new Point(194,157),new Point(191,160),new Point(186,167),new Point(180,176),new Point(177,179),new Point(171,187),new Point(169,189),new Point(165,194),new Point(164,196)));
		this.Unistrokes[8] = new Unistroke('leftbracket', new Array(new Point(140,124),new Point(138,123),new Point(135,122),new Point(133,123),new Point(130,123),new Point(128,124),new Point(125,125),new Point(122,124),new Point(120,124),new Point(118,124),new Point(116,125),new Point(113,125),new Point(111,125),new Point(108,124),new Point(106,125),new Point(104,125),new Point(102,124),new Point(100,123),new Point(98,123),new Point(95,124),new Point(93,123),new Point(90,124),new Point(88,124),new Point(85,125),new Point(83,126),new Point(81,127),new Point(81,129),new Point(82,131),new Point(82,134),new Point(83,138),new Point(84,141),new Point(84,144),new Point(85,148),new Point(85,151),new Point(86,156),new Point(86,160),new Point(86,164),new Point(86,168),new Point(87,171),new Point(87,175),new Point(87,179),new Point(87,182),new Point(87,186),new Point(88,188),new Point(88,195),new Point(88,198),new Point(88,201),new Point(88,207),new Point(89,211),new Point(89,213),new Point(89,217),new Point(89,222),new Point(88,225),new Point(88,229),new Point(88,231),new Point(88,233),new Point(88,235),new Point(89,237),new Point(89,240),new Point(89,242),new Point(91,241),new Point(94,241),new Point(96,240),new Point(98,239),new Point(105,240),new Point(109,240),new Point(113,239),new Point(116,240),new Point(121,239),new Point(130,240),new Point(136,237),new Point(139,237),new Point(144,238),new Point(151,237),new Point(157,236),new Point(159,237)));
		this.Unistrokes[9] = new Unistroke('rightbracket', new Array(new Point(112,138),new Point(112,136),new Point(115,136),new Point(118,137),new Point(120,136),new Point(123,136),new Point(125,136),new Point(128,136),new Point(131,136),new Point(134,135),new Point(137,135),new Point(140,134),new Point(143,133),new Point(145,132),new Point(147,132),new Point(149,132),new Point(152,132),new Point(153,134),new Point(154,137),new Point(155,141),new Point(156,144),new Point(157,152),new Point(158,161),new Point(160,170),new Point(162,182),new Point(164,192),new Point(166,200),new Point(167,209),new Point(168,214),new Point(168,216),new Point(169,221),new Point(169,223),new Point(169,228),new Point(169,231),new Point(166,233),new Point(164,234),new Point(161,235),new Point(155,236),new Point(147,235),new Point(140,233),new Point(131,233),new Point(124,233),new Point(117,235),new Point(114,238),new Point(112,238)));
		this.Unistrokes[10] = new Unistroke('v', new Array(new Point(89,164),new Point(90,162),new Point(92,162),new Point(94,164),new Point(95,166),new Point(96,169),new Point(97,171),new Point(99,175),new Point(101,178),new Point(103,182),new Point(106,189),new Point(108,194),new Point(111,199),new Point(114,204),new Point(117,209),new Point(119,214),new Point(122,218),new Point(124,222),new Point(126,225),new Point(128,228),new Point(130,229),new Point(133,233),new Point(134,236),new Point(136,239),new Point(138,240),new Point(139,242),new Point(140,244),new Point(142,242),new Point(142,240),new Point(142,237),new Point(143,235),new Point(143,233),new Point(145,229),new Point(146,226),new Point(148,217),new Point(149,208),new Point(149,205),new Point(151,196),new Point(151,193),new Point(153,182),new Point(155,172),new Point(157,165),new Point(159,160),new Point(162,155),new Point(164,150),new Point(165,148),new Point(166,146)));
		this.Unistrokes[11] = new Unistroke('delete', new Array(new Point(123,129),new Point(123,131),new Point(124,133),new Point(125,136),new Point(127,140),new Point(129,142),new Point(133,148),new Point(137,154),new Point(143,158),new Point(145,161),new Point(148,164),new Point(153,170),new Point(158,176),new Point(160,178),new Point(164,183),new Point(168,188),new Point(171,191),new Point(175,196),new Point(178,200),new Point(180,202),new Point(181,205),new Point(184,208),new Point(186,210),new Point(187,213),new Point(188,215),new Point(186,212),new Point(183,211),new Point(177,208),new Point(169,206),new Point(162,205),new Point(154,207),new Point(145,209),new Point(137,210),new Point(129,214),new Point(122,217),new Point(118,218),new Point(111,221),new Point(109,222),new Point(110,219),new Point(112,217),new Point(118,209),new Point(120,207),new Point(128,196),new Point(135,187),new Point(138,183),new Point(148,167),new Point(157,153),new Point(163,145),new Point(165,142),new Point(172,133),new Point(177,127),new Point(179,127),new Point(180,125)));
		this.Unistrokes[12] = new Unistroke('leftbrace', new Array(new Point(150,116),new Point(147,117),new Point(145,116),new Point(142,116),new Point(139,117),new Point(136,117),new Point(133,118),new Point(129,121),new Point(126,122),new Point(123,123),new Point(120,125),new Point(118,127),new Point(115,128),new Point(113,129),new Point(112,131),new Point(113,134),new Point(115,134),new Point(117,135),new Point(120,135),new Point(123,137),new Point(126,138),new Point(129,140),new Point(135,143),new Point(137,144),new Point(139,147),new Point(141,149),new Point(140,152),new Point(139,155),new Point(134,159),new Point(131,161),new Point(124,166),new Point(121,166),new Point(117,166),new Point(114,167),new Point(112,166),new Point(114,164),new Point(116,163),new Point(118,163),new Point(120,162),new Point(122,163),new Point(125,164),new Point(127,165),new Point(129,166),new Point(130,168),new Point(129,171),new Point(127,175),new Point(125,179),new Point(123,184),new Point(121,190),new Point(120,194),new Point(119,199),new Point(120,202),new Point(123,207),new Point(127,211),new Point(133,215),new Point(142,219),new Point(148,220),new Point(151,221)));
		this.Unistrokes[13] = new Unistroke('rightbrace', new Array(new Point(117,132),new Point(115,132),new Point(115,129),new Point(117,129),new Point(119,128),new Point(122,127),new Point(125,127),new Point(127,127),new Point(130,127),new Point(133,129),new Point(136,129),new Point(138,130),new Point(140,131),new Point(143,134),new Point(144,136),new Point(145,139),new Point(145,142),new Point(145,145),new Point(145,147),new Point(145,149),new Point(144,152),new Point(142,157),new Point(141,160),new Point(139,163),new Point(137,166),new Point(135,167),new Point(133,169),new Point(131,172),new Point(128,173),new Point(126,176),new Point(125,178),new Point(125,180),new Point(125,182),new Point(126,184),new Point(128,187),new Point(130,187),new Point(132,188),new Point(135,189),new Point(140,189),new Point(145,189),new Point(150,187),new Point(155,186),new Point(157,185),new Point(159,184),new Point(156,185),new Point(154,185),new Point(149,185),new Point(145,187),new Point(141,188),new Point(136,191),new Point(134,191),new Point(131,192),new Point(129,193),new Point(129,195),new Point(129,197),new Point(131,200),new Point(133,202),new Point(136,206),new Point(139,211),new Point(142,215),new Point(145,220),new Point(147,225),new Point(148,231),new Point(147,239),new Point(144,244),new Point(139,248),new Point(134,250),new Point(126,253),new Point(119,253),new Point(115,253)));
		this.Unistrokes[14] = new Unistroke('star', new Array(new Point(75,250),new Point(75,247),new Point(77,244),new Point(78,242),new Point(79,239),new Point(80,237),new Point(82,234),new Point(82,232),new Point(84,229),new Point(85,225),new Point(87,222),new Point(88,219),new Point(89,216),new Point(91,212),new Point(92,208),new Point(94,204),new Point(95,201),new Point(96,196),new Point(97,194),new Point(98,191),new Point(100,185),new Point(102,178),new Point(104,173),new Point(104,171),new Point(105,164),new Point(106,158),new Point(107,156),new Point(107,152),new Point(108,145),new Point(109,141),new Point(110,139),new Point(112,133),new Point(113,131),new Point(116,127),new Point(117,125),new Point(119,122),new Point(121,121),new Point(123,120),new Point(125,122),new Point(125,125),new Point(127,130),new Point(128,133),new Point(131,143),new Point(136,153),new Point(140,163),new Point(144,172),new Point(145,175),new Point(151,189),new Point(156,201),new Point(161,213),new Point(166,225),new Point(169,233),new Point(171,236),new Point(174,243),new Point(177,247),new Point(178,249),new Point(179,251),new Point(180,253),new Point(180,255),new Point(179,257),new Point(177,257),new Point(174,255),new Point(169,250),new Point(164,247),new Point(160,245),new Point(149,238),new Point(138,230),new Point(127,221),new Point(124,220),new Point(112,212),new Point(110,210),new Point(96,201),new Point(84,195),new Point(74,190),new Point(64,182),new Point(55,175),new Point(51,172),new Point(49,170),new Point(51,169),new Point(56,169),new Point(66,169),new Point(78,168),new Point(92,166),new Point(107,164),new Point(123,161),new Point(140,162),new Point(156,162),new Point(171,160),new Point(173,160),new Point(186,160),new Point(195,160),new Point(198,161),new Point(203,163),new Point(208,163),new Point(206,164),new Point(200,167),new Point(187,172),new Point(174,179),new Point(172,181),new Point(153,192),new Point(137,201),new Point(123,211),new Point(112,220),new Point(99,229),new Point(90,237),new Point(80,244),new Point(73,250),new Point(69,254),new Point(69,252)));
		this.Unistrokes[15] = new Unistroke('loop', new Array(new Point(81,219),new Point(84,218),new Point(86,220),new Point(88,220),new Point(90,220),new Point(92,219),new Point(95,220),new Point(97,219),new Point(99,220),new Point(102,218),new Point(105,217),new Point(107,216),new Point(110,216),new Point(113,214),new Point(116,212),new Point(118,210),new Point(121,208),new Point(124,205),new Point(126,202),new Point(129,199),new Point(132,196),new Point(136,191),new Point(139,187),new Point(142,182),new Point(144,179),new Point(146,174),new Point(148,170),new Point(149,168),new Point(151,162),new Point(152,160),new Point(152,157),new Point(152,155),new Point(152,151),new Point(152,149),new Point(152,146),new Point(149,142),new Point(148,139),new Point(145,137),new Point(141,135),new Point(139,135),new Point(134,136),new Point(130,140),new Point(128,142),new Point(126,145),new Point(122,150),new Point(119,158),new Point(117,163),new Point(115,170),new Point(114,175),new Point(117,184),new Point(120,190),new Point(125,199),new Point(129,203),new Point(133,208),new Point(138,213),new Point(145,215),new Point(155,218),new Point(164,219),new Point(166,219),new Point(177,219),new Point(182,218),new Point(192,216),new Point(196,213),new Point(199,212),new Point(201,211)));
		//--

		//-- The $1 Gesture Recognizer API begins here -- 3 methods: Recognize(), AddGesture(), and DeleteUserGestures()
		this.Recognize = function(points, useProtractor) {
			var t0 = Date.now();
			var candidate = new Unistroke('', points);
			var u = -1;
			var b = +Infinity;
			for(var i = 0; i < this.Unistrokes.length; i++) { // for each unistroke template
				var d;
				if(useProtractor) {
					d = OptimalCosineDistance(this.Unistrokes[i].Vector, candidate.Vector); // Protractor
				} else {
					d = DistanceAtBestAngle(candidate.Points, this.Unistrokes[i], -AngleRange, +AngleRange, AnglePrecision); // Golden Section Search (original $1)
				}
				if(d < b) {
					b = d; // best (least) distance
					u = i; // unistroke index
				}
			}
			var t1 = Date.now();
			return (u == -1) ? new Result('No match.', 0.0, t1-t0) : new Result(this.Unistrokes[u].Name, useProtractor ? (1.0 - b) : (1.0 - b / HalfDiagonal), t1-t0);
		}

		this.AddGesture = function(name, points) {
			this.Unistrokes[this.Unistrokes.length] = new Unistroke(name, points); // append new unistroke
			var num = 0;
			for(var i = 0; i < this.Unistrokes.length; i++) {
				if(this.Unistrokes[i].Name == name) {
					num++;
				}
			}
			return num;
		}

		this.DeleteUserGestures = function() {
			this.Unistrokes.length = NumUnistrokes; // clear any beyond the original set
			return NumUnistrokes;
		}

	}


	// Private helper functions from here on down
	function Resample(points, n) {
		var I = PathLength(points) / (n - 1); // interval length
		var D = 0.0;
		var newpoints = new Array(points[0]);
		for(var i = 1; i < points.length; i++) {
			var d = Distance(points[i-1], points[i]);
			if((D + d) >= I) {
				var qx = points[i-1].X + ((I - D) / d) * (points[i].X - points[i-1].X);
				var qy = points[i-1].Y + ((I - D) / d) * (points[i].Y - points[i-1].Y);
				var q = new Point(qx, qy);
				newpoints[newpoints.length] = q; // append new point 'q'
				points.splice(i, 0, q); // insert 'q' at position i in points s.t. 'q' will be the next i
				D = 0.0;
			} else {
				D += d;
			}
		}
		if(newpoints.length == n - 1) { // somtimes we fall a rounding-error short of adding the last point, so add it if so
			newpoints[newpoints.length] = new Point(points[points.length - 1].X, points[points.length - 1].Y);
		}
		return newpoints;
	}


	function IndicativeAngle(points) {
		var c = Centroid(points);
		return Math.atan2(c.Y - points[0].Y, c.X - points[0].X);
	}


	function RotateBy(points, radians) { // rotates points around centroid
		var c = Centroid(points);
		var cos = Math.cos(radians);
		var sin = Math.sin(radians);
		var newpoints = new Array();
		for(var i = 0; i < points.length; i++) {
			var qx = (points[i].X - c.X) * cos - (points[i].Y - c.Y) * sin + c.X
			var qy = (points[i].X - c.X) * sin + (points[i].Y - c.Y) * cos + c.Y;
			newpoints[newpoints.length] = new Point(qx, qy);
		}
		return newpoints;
	}


	function ScaleTo(points, size) { // non-uniform scale; assumes 2D gestures (i.e., no lines)
		var B = BoundingBox(points);
		var newpoints = new Array();
		for(var i = 0; i < points.length; i++) {
			var qx = points[i].X * (size / B.Width);
			var qy = points[i].Y * (size / B.Height);
			newpoints[newpoints.length] = new Point(qx, qy);
		}
		return newpoints;
	}


	function TranslateTo(points, pt) { // translates points' centroid
		var c = Centroid(points);
		var newpoints = new Array();
		for(var i = 0; i < points.length; i++) {
			var qx = points[i].X + pt.X - c.X;
			var qy = points[i].Y + pt.Y - c.Y;
			newpoints[newpoints.length] = new Point(qx, qy);
		}
		return newpoints;
	}


	function Vectorize(points) { // for Protractor
		var sum = 0.0;
		var vector = new Array();
		for(var i = 0; i < points.length; i++) {
			vector[vector.length] = points[i].X;
			vector[vector.length] = points[i].Y;
			sum += points[i].X * points[i].X + points[i].Y * points[i].Y;
		}
		var magnitude = Math.sqrt(sum);
		for(var i = 0; i < vector.length; i++) {
			vector[i] /= magnitude;
		}
		return vector;
	}


	function OptimalCosineDistance(v1, v2) { // for Protractor
		var a = 0.0;
		var b = 0.0;
		for(var i = 0; i < v1.length; i += 2) {
			a += v1[i] * v2[i] + v1[i+1] * v2[i+1];
			b += v1[i] * v2[i+1] - v1[i+1] * v2[i];
		}
		var angle = Math.atan(b / a);
		return Math.acos(a * Math.cos(angle) + b * Math.sin(angle));
	}


	function DistanceAtBestAngle(points, T, a, b, threshold) {
		var x1 = Phi * a + (1.0 - Phi) * b;
		var f1 = DistanceAtAngle(points, T, x1);
		var x2 = (1.0 - Phi) * a + Phi * b;
		var f2 = DistanceAtAngle(points, T, x2);
		while (Math.abs(b - a) > threshold) {
			if(f1 < f2) {
				b = x2;
				x2 = x1;
				f2 = f1;
				x1 = Phi * a + (1.0 - Phi) * b;
				f1 = DistanceAtAngle(points, T, x1);
			} else {
				a = x1;
				x1 = x2;
				f1 = f2;
				x2 = (1.0 - Phi) * a + Phi * b;
				f2 = DistanceAtAngle(points, T, x2);
			}
		}
		return Math.min(f1, f2);
	}


	function DistanceAtAngle(points, T, radians) {
		var newpoints = RotateBy(points, radians);
		return PathDistance(newpoints, T.Points);
	}


	function Centroid(points) {
		var x = 0.0, y = 0.0;
		for(var i = 0; i < points.length; i++) {
			x += points[i].X;
			y += points[i].Y;
		}
		x /= points.length;
		y /= points.length;
		return new Point(x, y);
	}


	function BoundingBox(points) {
		var minX = +Infinity, maxX = -Infinity, minY = +Infinity, maxY = -Infinity;
		for(var i = 0; i < points.length; i++) {
			minX = Math.min(minX, points[i].X);
			minY = Math.min(minY, points[i].Y);
			maxX = Math.max(maxX, points[i].X);
			maxY = Math.max(maxY, points[i].Y);
		}
		return new Rectangle(minX, minY, maxX - minX, maxY - minY);
	}


	function PathDistance(pts1, pts2) {
		var d = 0.0;
		for(var i = 0; i < pts1.length; i++) { // assumes pts1.length == pts2.length
			d += Distance(pts1[i], pts2[i]);
		}
		return d / pts1.length;
	}


	function PathLength(points) {
		var d = 0.0;
		for(var i = 1; i < points.length; i++) {
			d += Distance(points[i - 1], points[i]);
		}
		return d;
	}


	function Distance(p1, p2) {
		var dx = p2.X - p1.X;
		var dy = p2.Y - p1.Y;
		return Math.sqrt(dx * dx + dy * dy);
	}


	function Deg2Rad(d) {
		return (d * Math.PI / 180.0);
	}


})(jQuery);

// #END
