/**
 * This file is part of the Vigu PHP error aggregation system.
 * @link https://github.com/localgod/Vigu
 *
 * @copyright 2012 Copyright Jens Riisom Schultz, Johannes Skov Frandsen
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
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

				Vigu.Toolbar.setup(this.application, 'Vigu');
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
			},
			/**
			 * Notify the user about something
			 *
			 * @param {string} message Message to user
			 *
			 * @return void
			 */
			notify : function(message) {
				jQuery.notification(message, {
					className : 'jquery-notification',
					duration : 2000,
					freezeOnHover : false,
					hideSpeed : 500,
					position : 'center',
					showSpeed : 500,
					zIndex : 99999
				});
			},
		};
})(jQuery);
