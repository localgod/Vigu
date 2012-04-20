if (typeof Vigu === 'undefined') {
	var Vigu = {};
}
/**
 * Base object for all Vigu menu operations
 */
Vigu.Toolbar = (function($) {
	return {
		/**
		 * Create the toolbar
		 * 
		 * @param {jQuery} Dom node
		 * @param {String} Title in the menu
		 * 
		 * @return undefined
		 */
		setup : function(node , title) {
			var toolbar = $('<div>').attr('role', 'toolbar')
					.addClass('ui-widget-header ui-corner-all')
					.append($('<h1>').text(title));
			toolbar.appendTo(node);
			this.addSearch(toolbar);
		},
		/**
		 * Render the toolbar
		 * 
		 * @return undefined
		 */
		render : function () {
		},
		/**
		 * Get search field
		 * 
		 * @param {jQuery} node Node
		 * 
		 * @return {undefiend}
		 */
		addSearch : function(node) {
			$('<input type="text">')
				.attr('name', 'search')
				.addClass('ui-corner-all')
				.keypress(function(event) {
				  if ( event.which == 13 ) {
					     event.preventDefault();
					     Vigu.Grid.parameters.path = $('input[name="search"]').val();
					     Vigu.Grid.reload();
					   }
					})
				.click(function(){
					 this.select();
				})
				.focus(function(){
					 this.select();
				})
				.appendTo(node);
			$('<button>')
			.text('Reset')
			.click(function(){
				Vigu.Grid.parameters.path = '';
				$('input[name="search"]').val('');
				Vigu.Grid.reload();
			}) 
			.appendTo(node).button();
			$('<button>')
				.text('Reload')
				.click(function(){
					Vigu.Grid.reload();
				}) 
				.appendTo(node).button();
			$('<button>')
				.text('Auto Reload')
				.click(function(){
					Vigu.Grid.autoRefresh();
				}) 
				.appendTo(node).button();
		}
	};
})(jQuery);
