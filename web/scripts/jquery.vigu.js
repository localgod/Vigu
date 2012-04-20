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