if (typeof Vigu === 'undefined') {
	var Vigu = {};
}
/**
 * Base object for all Vigu operations
 */
Vigu = (function($) {
		return {
			/**
			 * The main application
			 * 
			 * @type jQuery node
			 */
			application : undefined,
			/**
			 * The left column
			 * 
			 * @type jQuery node
			 */
			leftColumn : undefined,
			/**
			 * The right column
			 * 
			 * @type jQuery node
			 */
			rightColumn : undefined,
			/**
			 * Create the vigu application
			 * 
			 * @return undefined
			 */
			setup : function() {
				this.application = $('<div>').attr('role', 'application');
				this.leftColumn  = $('<div>').attr('role', 'region');
				this.rightColumn = $('<div>').attr('role', 'region');
				
				Vigu.Toolbar.setup(this.application, 'Vigu - You did this!');
				this.application.append(this.leftColumn);
				this.application.append(this.rightColumn);
				Vigu.Grid.setup(this.leftColumn);
				this.application.appendTo('body');
			},
			/**
			 * Render the UI
			 * 
			 * This needs to be done after the elements have been added to the DOM
			 * 
			 * @return undefined
			 */
			render : function() {
				Vigu.Toolbar.render();
				Vigu.Grid.render();
			}
		};
})(jQuery);
