// Chart.js - Stacked Area
// https://github.com/tannerlinsley/Chart.StackedArea.js
// r.1.0.1

// (c) 2016-2019 unix-world.org
// License: BSD
// version: 20190221
// unixman:
//		* fixes: changed window context to support all type of constructors
//		* fix default scale start at zero

(function() {
	"use strict";

	var root = this,
		Chart = root.Chart,
		helpers = Chart.helpers;

	//-- fix by unixman
	var defaultConfig = {

		//Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
		scaleBeginAtZero : true,

	};
	//-- #end fix

	Chart.types.Line.extend({

		name: "StackedArea",

		defaults : defaultConfig, // fix by unixman

		initialize: function(data) {

			var options = this.options;

			this.ScaleClass = Chart.Scale.extend({
				calculateStackY: function(datasets, dsIndex, pointIndex, value) {
					var offset = 0, sum = 0;
					var i = 0; // fixed by unixman
					for(i = 0; i < datasets.length; i++) {
						sum += datasets[i].points[pointIndex].value;
					}
					for(i = dsIndex; i < datasets.length; i++) {
						if(i === dsIndex && value) {
							offset += value;
						} else {
							offset = (+offset) + (+datasets[i].points[pointIndex].value);
						}
					}
					if(options.relativePoints) {
						offset = offset / sum * 100;
					}
					return this.calculateY(offset);
				},
			});

			//Declare the extension of the default point, to cater for the options passed in to the constructor
			this.PointClass = Chart.Point.extend({
				strokeWidth: this.options.pointDotStrokeWidth,
				radius: this.options.pointDotRadius,
				display: this.options.pointDot,
				hitDetectionRadius: this.options.pointHitDetectionRadius,
				ctx: this.chart.ctx,
				inRange: function(mouseX) {
					return (Math.pow(mouseX - this.x, 2) < Math.pow(this.radius + this.hitDetectionRadius, 2));
				}
			});

			this.datasets = [];

			//Set up tooltip events on the chart
			if(this.options.showTooltips) {
				helpers.bindEvents(this, this.options.tooltipEvents, function(evt) {
					var activePoints = (evt.type !== 'mouseout') ? this.getPointsAtEvent(evt) : [];
					this.eachPoints(function(point) {
						point.restore(['fillColor', 'strokeColor']);
					});
					helpers.each(activePoints, function(activePoint) {
						activePoint.fillColor = activePoint.highlightFill;
						activePoint.strokeColor = activePoint.highlightStroke;
					});
					this.showTooltip(activePoints);
				});
			}

			//Iterate through each of the datasets, and build this into a property of the chart
			helpers.each(data.datasets, function(dataset) {
				var datasetObject = {
					label: dataset.label || null,
					fillColor: dataset.fillColor,
					strokeColor: dataset.strokeColor,
					pointColor: dataset.pointColor,
					pointStrokeColor: dataset.pointStrokeColor,
					points: []
				};
				this.datasets.push(datasetObject);
				helpers.each(dataset.data, function(dataPoint, index) {
					//Add a new point for each piece of data, passing any required data to draw.
					datasetObject.points.push(new this.PointClass({
						value: dataPoint,
						label: data.labels[index],
						datasetLabel: dataset.label,
						strokeColor: dataset.pointStrokeColor,
						fillColor: dataset.pointColor,
						highlightFill: dataset.pointHighlightFill || dataset.pointColor,
						highlightStroke: dataset.pointHighlightStroke || dataset.pointStrokeColor
					}));
				}, this);
			}, this);

			this.buildScale(data.labels);

			this.eachPoints(function(point, index) {
				helpers.extend(point, {
					x: this.scale.calculateX(index),
					y: this.scale.endPoint
				});
				point.save();
			}, this);

			this.render();

		},

		buildScale: function(labels) {

			var self = this;

			var dataTotal = function() {
				var values = [];
				helpers.each(self.datasets, function(dataset) {
					helpers.each(dataset.points, function(point, pointIndex) {
						if(!values[pointIndex]) values[pointIndex] = 0;
						if(self.options.relativePoints) {
							values[pointIndex] = 100;
						} else {
							values[pointIndex] = values[pointIndex] + point.value;
						}
					});
				});

				if(self.options.relativePoints) {
					values.push(0);
				} else {
					helpers.each(self.datasets[0].points, function(point, pointIndex) {
						values.push(point.value);
					});
				}

				return values;

			};

			var scaleOptions = {
				templateString: this.options.scaleLabel,
				height: this.chart.height,
				width: this.chart.width,
				ctx: this.chart.ctx,
				textColor: this.options.scaleFontColor,
				fontSize: this.options.scaleFontSize,
				fontStyle: this.options.scaleFontStyle,
				fontFamily: this.options.scaleFontFamily,
				valuesCount: labels.length,
				beginAtZero: this.options.scaleBeginAtZero,
				integersOnly: this.options.scaleIntegersOnly,
				calculateYRange: function(currentHeight) {
					var updatedRanges = helpers.calculateScaleRange(
						dataTotal(),
						currentHeight,
						this.fontSize,
						this.beginAtZero,
						this.integersOnly
					);
					helpers.extend(this, updatedRanges);
				},
				xLabels: labels,
				font: helpers.fontString(this.options.scaleFontSize, this.options.scaleFontStyle, this.options.scaleFontFamily),
				lineWidth: this.options.scaleLineWidth,
				lineColor: this.options.scaleLineColor,
				showHorizontalLines: this.options.scaleShowHorizontalLines,
				showVerticalLines: this.options.scaleShowVerticalLines,
				gridLineWidth: (this.options.scaleShowGridLines) ? this.options.scaleGridLineWidth : 0,
				gridLineColor: (this.options.scaleShowGridLines) ? this.options.scaleGridLineColor : "rgba(0,0,0,0)",
				padding: (this.options.showScale) ? 0 : this.options.pointDotRadius + this.options.pointDotStrokeWidth,
				showLabels: this.options.scaleShowLabels,
				display: this.options.showScale
			};

			if(this.options.scaleOverride) {
				helpers.extend(scaleOptions, {
					calculateYRange: helpers.noop,
					steps: this.options.scaleSteps,
					stepValue: this.options.scaleStepWidth,
					min: this.options.scaleStartValue,
					max: this.options.scaleStartValue + (this.options.scaleSteps * this.options.scaleStepWidth)
				});
			}

			this.scale = new this.ScaleClass(scaleOptions);

		},

		draw: function(ease) {

			var easingDecimal = ease || 1;
			this.clear();

			var ctx = this.chart.ctx;

			// Some helper methods for getting the next/prev points
			var hasValue = function(item) {
					return item.value !== null;
				},
				nextPoint = function(point, collection, index) {
					return helpers.findNextWhere(collection, hasValue, index) || point;
				},
				previousPoint = function(point, collection, index) {
					return helpers.findPreviousWhere(collection, hasValue, index) || point;
				};

			this.scale.draw(easingDecimal);

			helpers.each(this.datasets, function(dataset, dsindex) {
				var pointsWithValues = helpers.where(dataset.points, hasValue);

				//Transition each point first so that the line and point drawing isn't out of sync
				//We can use this extra loop to calculate the control points of this dataset also in this loop

				helpers.each(dataset.points, function(point, index) {
					if(point.hasValue()) {
						point.transition({
							y: this.scale.calculateStackY(this.datasets, dsindex, index, point.value),
							//y: this.scale.calculateY(point.value),
							x: this.scale.calculateX(index)
						}, easingDecimal);
					}
				}, this);

				// Control points need to be calculated in a seperate loop, because we need to know the current x/y of the point
				// This would cause issues when there is no animation, because the y of the next point would be 0, so beziers would be skewed
				if(this.options.bezierCurve) {
					helpers.each(pointsWithValues, function(point, index) {
						var tension = (index > 0 && index < pointsWithValues.length - 1) ? this.options.bezierCurveTension : 0;
						point.controlPoints = helpers.splineCurve(
							previousPoint(point, pointsWithValues, index),
							point,
							nextPoint(point, pointsWithValues, index),
							tension
						);

						// Prevent the bezier going outside of the bounds of the graph

						// Cap puter bezier handles to the upper/lower scale bounds
						if(point.controlPoints.outer.y > this.scale.endPoint) {
							point.controlPoints.outer.y = this.scale.endPoint;
						} else if(point.controlPoints.outer.y < this.scale.startPoint) {
							point.controlPoints.outer.y = this.scale.startPoint;
						}

						// Cap inner bezier handles to the upper/lower scale bounds
						if(point.controlPoints.inner.y > this.scale.endPoint) {
							point.controlPoints.inner.y = this.scale.endPoint;
						} else if(point.controlPoints.inner.y < this.scale.startPoint) {
							point.controlPoints.inner.y = this.scale.startPoint;
						}
					}, this);
				}

				//Draw the line between all the points
				ctx.lineWidth = this.options.datasetStrokeWidth;
				ctx.strokeStyle = dataset.strokeColor;
				ctx.beginPath();

				helpers.each(pointsWithValues, function(point, index) {
					if(index === 0) {
						ctx.moveTo(point.x, point.y);
					} else {
						if(this.options.bezierCurve) {
							var previous = previousPoint(point, pointsWithValues, index);
							ctx.bezierCurveTo(
								previous.controlPoints.outer.x,
								previous.controlPoints.outer.y,
								point.controlPoints.inner.x,
								point.controlPoints.inner.y,
								point.x,
								point.y
							);
						} else {
							ctx.lineTo(point.x, point.y);
						}
					}
				}, this);

				ctx.stroke();

				if(this.options.datasetFill && pointsWithValues.length > 0) {
					//Round off the line by going to the base of the chart, back to the start, then fill.
					ctx.lineTo(pointsWithValues[pointsWithValues.length - 1].x, this.scale.endPoint);
					ctx.lineTo(pointsWithValues[0].x, this.scale.endPoint);
					ctx.fillStyle = dataset.fillColor;
					ctx.closePath();
					ctx.fill();
				}

				//Now draw the points over the line
				//A little inefficient double looping, but better than the line
				//lagging behind the point positions
				helpers.each(pointsWithValues, function(point) {
					point.draw();
				});
			}, this);

		}

	});

//}).call(this, window); // fixed by unixman
}).call(this);

//#END
