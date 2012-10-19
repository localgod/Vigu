/**
 * This file is part of the Vigu PHP error aggregation system.
 * @link https://github.com/localgod/Vigu
 *
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
(function($) {
	$.fn.vigu = function() {
		var scope = function($this) {
			var f = {
				/**
				 * The "constructor"
				 */
				init : function() {
					f.setup();
				},

				/**
				 * Sample function - Sets the background color of $this.
				 * 
				 * @param {String}
				 *            color The color to set.
				 */
				setup : function(color) {
					Vigu.setup();
					Vigu.render();
				},

				events : {
				}
			};
			f.init();
		};

		return this.each(function() {
			scope($(this));
		});
	};
}(jQuery));