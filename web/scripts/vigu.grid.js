if (typeof Vigu === 'undefined') {
	var Vigu = {};
}
/**
 * Base object for all Vigu entry operations
 */
Vigu.Grid = (function($) {
	return {
		/**
		 * Should the grid autorefresh
		 *
		 * @type {Boolean}
		 */
		autorefresh : false,
		/**
		 * Timer
		 *
		 * @type {string}
		 */
		timer : '',
		/**
		 * Paramters used in query string
		 *
		 * @type {String}
		 */
		parameters : {
			/**
			 * Module to limit search by
			 * @type {String}
			 */
			module : '',
			/**
			 * Host to limit search by
			 * @type {String}
			 */
			host : '',
			/**
			 * Error level to limit search by
			 * @type {String}
			 */
			level : '',
			/**
			 * File path to limit search by
			 * @type {String}
			 */
			path : '',
			/**
			 * Error message to limit search by
			 * @type {String}
			 */
			search : ''
		},
		/**
		 * Setup the tags for the grid
		 *
		 * @param {jQuery} Dom node
		 *
		 * @return undefined
		 */
		setup : function(node) {
			$('<table>').attr('role','grid').attr('id', 'grid').appendTo(node);
			$('<div>').attr('id', 'pager').appendTo(node);
		},
		/**
		 * reload the grid with updated query
		 *
		 * @return undefined
		 */
		reload : function() {
			$("#grid").jqGrid().setGridParam({page : 1});
			$("#grid").jqGrid().setGridParam({url : '/api/log/grid' + Vigu.Grid.queryString()}).trigger("reloadGrid");
		},
		/**
		 * Auto refresh grid
		 *
		 * @return undefined
		 */
		autoRefresh : function() {
			Vigu.Grid.autorefresh ? Vigu.Grid.autorefresh = false : Vigu.Grid.autorefresh = true;
			if (Vigu.Grid.autorefresh) {
				Vigu.Grid.timer = setInterval("Vigu.Grid.reload()", 5000);
			} else {
				clearInterval(Vigu.Grid.timer);
			}
		},
		/**
		 * Render the grid
		 *
		 * @return undefined
		 */
		render : function() {
			var gridHeight = $(window).height() - 130;
			$("[role='grid']").jqGrid(
					{
						url : '/api/log/grid' + Vigu.Grid.queryString(),
						datatype : "json",
						colNames : [ 'Level', 'Host', 'Message', 'Last', 'Count'],
						colModel : [
						             {name : 'level',     index : 'level',     resizable : false, sortable : false, width : 120,  align: 'center', fixed : true, title : false, formatter : Vigu.Grid.levelFormatter},
						             {name : 'host',      index : 'host',      resizable : false, sortable : false, width : 150,  align: 'center', fixed : true, title : false},
						             {name : 'message',   index : 'message',   classes : 'messageGrid', sortable : false, formatter : Vigu.Grid.messageFormatter},
						             {name : 'timestamp', index : 'timestamp', resizable : false, width : 140, align: 'center', fixed : true, title : false, formatter : Vigu.Grid.agoFormatter},
						             {name : 'count',     index : 'count',     resizable : false, width : 60,  align: 'center', fixed : true, title : false}
						           ],
			            jsonReader : {
			        	      root: "rows",
			        	      page: "page",
			        	      total: "total",
			        	      records: "records",
			        	      repeatitems: true,
			        	      cell: "cell",
			        	      id: "key",
			        	      userdata: "userdata",
			        	      subgrid: {
			        	         root:"rows",
			        	         repeatitems: true,
			        	         cell:"cell"
			        	      }
			        	   },
						loadtext: 'Loading...',
						rowNum : 100,
						rowList : [100, 200, 300],
						pager : '#pager',
						sortname : 'timestamp',
						viewrecords : true,
						sortorder : "desc",
						autowidth: true,
						viewrecords: true,
						gridview: true,
						hidegrid: false,
						height: gridHeight,
						caption : "Errors",
					    onSelectRow: function(id) {
					    	if (id != '') {
					    		Vigu.Document.render(Vigu.rightColumn, id);
					    	}
						},
						gridComplete: function() {
							var firstIdOnPage = $("[role='grid']").getDataIDs()[0];
							$('.ui-grid-ico-sort.ui-icon-desc.ui-sort-ltr').hide();
							if (firstIdOnPage != '') {
								Vigu.Document.render(Vigu.rightColumn, firstIdOnPage);
							}
						}
					});

			$(window).bind('resize', function() {
				$("#grid").setGridWidth(($("[role='application']").width() - 2) / 2, true);
			}).trigger('resize');

		},
		/**
		 * Formats the message
		 *
		 * @param {String} cellvalue The value to be formatted
		 * @param {Object} options   Containing the row id and column id
		 * @param {Object} rowObject Is a row data represented in the format determined from datatype option
		 *
		 * @return {String}
		 * @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:custom_formatter
		 */
		messageFormatter : function(cellvalue, options, rowObject) {
			var newValue = cellvalue.replace(/(href=\W?)/, 'target="ref" $1http://dk.php.net/manual/en/');
			return newValue;
		},
		/**
		 * Formats the level
		 *
		 * @param {String} cellvalue The value to be formatted
		 * @param {Object} options   Containing the row id and column id
		 * @param {Object} rowObject Is a row data represented in the format determined from datatype option
		 *
		 * @return {String}
		 * @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:custom_formatter
		 */
		levelFormatter : function(cellvalue, options, rowObject) {
			var lower = cellvalue.toLowerCase();
			var className = 'errorlevel_'+ lower.replace(' error', '_error')
												.replace(' warning', '_warning')
												.replace(' notice', '_notice')
												.replace(' deprecated', '_deprecated');
			return '<span class="'+ className +'">' + lower.charAt(0).toUpperCase() + lower.slice(1) + '</span>';
		},
		/**
		 * Formats the date
		 *
		 * @param {String} cellvalue The value to be formatted
		 * @param {Object} options   Containing the row id adn column id
		 * @param {Object} rowObject Is a row data represented in the format determined from datatype option
		 *
		 * @return {String}
		 * @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:custom_formatter
		 */
		agoFormatter : function(cellvalue, options, rowObject) {
			var date = new Date((cellvalue || "").replace(/-/g,"/").replace(/[TZ]/g," "));
			var diff = (((new Date()).getTime() - date.getTime()) / 1000),
			day_diff = Math.floor(diff / 86400);

			if (isNaN(day_diff) || day_diff < 0 || day_diff >= 31 ) {
				return cellvalue;
			}

			return day_diff == 0 && (
					diff < 60 && "just now" ||
					diff < 120 && "1 minute ago" ||
					diff < 3600 && Math.floor( diff / 60 ) + " minutes ago" ||
					diff < 7200 && "1 hour ago" ||
					diff < 86400 && Math.floor( diff / 3600 ) + " hours ago") ||
				day_diff == 1 && "Yesterday" ||
				day_diff < 7 && day_diff + " days ago" ||
				day_diff < 31 && Math.ceil( day_diff / 7 ) + " weeks ago";
		},
		/**
		 * Construct the query string
		 *
		 * @return {String}
		 */
		queryString : function() {
			var params = [];
			$.each(Vigu.Grid.parameters, function(key, value) {
				if (value) {
					params.push(key + '=' + value);
				}
			});

			return (params.length > 0 ? '?' : '') + params.join('&');
		}
	};
})(jQuery);
