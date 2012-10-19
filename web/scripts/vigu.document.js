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
 * Base object for all Vigu menu operations
 */
Vigu.Document = (function($) {
		return {
			/**
			 * Create the error document
			 *
		     * @param {jQuery} Dom node
			 * @param {Integer} Id of the document to render
			 *
			 * @return {undefined}
			 */
			render : function(node, key) {
				if (key != undefined) {
					$.ajax({
						url : '/api/log/details',
						dataType : 'json',
						data : {
							key : key
						},
						success : function(data) {
							$("[role=document]").remove();
							if (data['error'] === undefined) {
								var data = data['details'];
								var document = $('<div>').attr('role', 'document').addClass('ui-widget ui-widget-content ui-corner-all');
								Vigu.Document.headerSection(document, data);
								Vigu.Document.messageSection(document, data.message);
								Vigu.Document.stacktraceSection(document, data.stacktrace);
								Vigu.Document.contextSection(document, data.context);
								document.appendTo(node);

								$(window).resize(function() {
									Vigu.Document.setDocumentSize();
								}).trigger('resize');
							} else {
								Vigu.notify(data['error']);
							}
						}
					});
				} else {
					$("[role=document]").remove();
				}
			},
			/**
			 * Set the handled status for an error
			 *
			 * @param {boolean}  handled   Has this error been handled
			 * @param {string}   key       The key of the error
			 * @param {function} onSuccess Call this when the request is successful.
			 *
			 * @return undefined
			 */
			setHandled : function(handled, key, onSuccess) {
				var url = handled ? '/api/log/handle' : '/api/log/un_handle';
				$.ajax({
					url : url,
					dataType : 'json',
					data: { key: key },
					error : function() {
						Vigu.notify('Setting the handled status failed');
					},
					success : onSuccess
				});
			},
			/**
			 * Set the size of the document in the interface
			 *
			 * @return {undefined}
			 */
			setDocumentSize : function() {
				var position = $('[role=document]').position();
				var newSize = $(window).height() - position.top -7;
				$('[role=document]').css({'height':newSize});
			},
			/**
			 * Generate the header block
			 *
			 * @param {jQuery} node Node
			 * @param {Object} data Document data
			 *
			 * @return undefined
			 */
			headerSection : function(node, data) {
				var title = data.level;
				var level = data.level.toLowerCase().replace(' error', '_error').replace(' warning', '_warning').replace(' notice', '_notice');
				$('<div>')
					.addClass('ui-widget-header ui-corner-all ui-helper-clearfix messageTitle')
					.append($('<label>')
							.text('Handled').append($('<input type="checkbox">')
								.attr('checked', data.handled == true ? 'checked' : null)
								.change(function(){
									if ($(this).is(':checked')) {
										Vigu.notify('Error marked as handled');
										Vigu.Document.setHandled(true, data.key, Vigu.Grid.reload);
									} else {
										Vigu.notify('Error unmarked as handled');
										Vigu.Document.setHandled(false, data.key, Vigu.Grid.reload);
									}
								})))
					.append($('<span>')
						.text(title)
						.attr('title', title))
					.appendTo(node);
				var left = $('<div>').addClass('icons').appendTo(node);
				var right = $('<div>').addClass('fields').appendTo(node);
				$('<div>').addClass(level).addClass('error_level').appendTo(left);
				$('<div>').addClass('count').text(data.count).appendTo(left);
				var dl = $('<dl>');
				$('<dt>').text('Last (First)').appendTo(dl);
				$('<dd>').text(data.last + ' (' + data.first + ')').attr('title', data.last + '' + data.first + ')').appendTo(dl);
				$('<dt>').text('Frequency').appendTo(dl);
				$('<dd>').text(data.frequency.toFixed(1) + ' per hour').attr('title', data.frequency).appendTo(dl);
				$('<dt>').text('File').appendTo(dl);
				$('<dd>').text(data.file).addClass('file_search').attr('title', data.file).click(function(){
				    Vigu.Grid.parameters.path = data.file;
				    $('input[name="search"]').val(data.file);
					Vigu.Toolbar.updateSearchReset();

				    Vigu.Grid.reload();
				}).appendTo(dl);
				$('<dt>').text('Line').appendTo(dl);
				$('<dd>').text(data.line).appendTo(dl);
				dl.appendTo(right);
			},
			/**
			 * Generate the message block
			 *
			 * @param {jQuery} node    Node
			 * @param {Object} message Massage
			 *
			 * @return undefined
			 */
			messageSection : function(node, message) {
				$('<div>').addClass('ui-widget-header ui-corner-all ui-helper-clearfix messageTitle').append($('<span>').text('Message')).appendTo(node);
				var messageText = $('<div>').addClass('message');
				if (message != undefined) {
					message = message.replace(/(href=\W?)/, 'target="ref" $1http://dk.php.net/manual/en/');
					$('<p>').html(message).appendTo(messageText);
				} else {
					$('<p>').text('No stacktrace available').appendTo(messageText);
				}
				node.append(messageText);
			},
			/**
			 * Generate the stacktrace block
			 *
			 * @param {jQuery} node       Node
			 * @param {Object} stacktrace Stacktrace
			 *
			 * @return undefined
			 */
			stacktraceSection : function(node, stacktrace) {
				$('<div>').addClass('ui-widget-header ui-corner-all ui-helper-clearfix messageTitle').append($('<span>').text('Stacktrace')).appendTo(node);
				var trace = $('<div>').addClass('stacktrace');
				if (stacktrace != undefined && stacktrace.length != 0) {
					for (line in stacktrace) {
						this._stLine(trace, stacktrace[line]);
					}
				} else {
					$('<p>').text('No stacktrace available').appendTo(trace);
				}
				node.append(trace);
			},
			/**
			 * Generate the context block
			 *
			 * @param {jQuery} node    Node
			 * @param {Object} context Context
			 *
			 * @return undefined
			 */
			contextSection : function(node, context) {
				$('<div>').addClass('ui-widget-header ui-corner-all ui-helper-clearfix messageTitle').append($('<span>').text('Context')).appendTo(node);
				var contextSection = $('<div>').addClass('context');
				if (context != undefined && context.length != 0) {
					for (key in context) {
						var varName = $('<span>').addClass('varName').text(key + ' : ').append($('<span>').addClass('varValue').text(context[key]));
						$('<p>').append(varName).appendTo(contextSection);
					}
				} else {
					$('<p>').text('No context available').appendTo(contextSection);
				}
				node.append(contextSection);
			},
			/**
			 * Generate a stacktrace line
			 *
			 * @param {jQuery} node Node
			 * @param {String} line Line
			 *
			 * @return undefined
			 */
			_stLine : function(node, line) {
				var path = line['file'];
				if (path != undefined) {
					var p = $('<p>');
					this._stClass(p, line['class']);
					this._stFunction(p, line['function'], line['type']);
					this._stPath(p, path);
					this._stLineNumber(p, line['line']);
					p.appendTo(node);
				}
			},
			/**
			 * Generate the linenumber element in a stacktrace line
			 *
			 * @param {jQuery} node Node
			 * @param {String} line Line number
			 *
			 * @return undefined
			 */
			_stLineNumber : function(node, line) {
				if (line > 0) {
					$('<span>')
					.text(' on line ')
					.append($('<span>')
							.text(line)
					)
					.appendTo(node);
				}
			},
			/**
			 * Generate the function element in a stacktrace line
			 *
			 * @param {jQuery} node         Node
			 * @param {String} functionName Function name
			 * @param {String} functionType Function type
			 *
			 * @return undefined
			 */
			_stFunction : function(node, functionName, functionType) {
				if (functionName != undefined) {
					$('<span>')
						.text('' + functionType)
						.append($('<span>')
									.text('' + functionName + '()')
						)
						.appendTo(node);
				} else {
					$('<span>')
						.append($('<span>')
						)
						.appendTo(node);
				}
			},
			/**
			 * Generate the class element in a stacktrace line
			 *
			 * @param {jQuery} node      Node
			 * @param {String} className Class name
			 *
			 * @return undefined
			 */
			_stClass : function(node, className) {
				if(className != undefined) {
					$('<span>')
						//.text(' in ')
						.append($('<span>')
									.text(className)
						)
					.appendTo(node);
				} else {
					$('<span>')
						.append($('<span>')
						)
					.appendTo(node);
				}
			},
			/**
			 * Generate the path element in a stacktrace line
			 *
			 * @param {jQuery} node Node
			 * @param {String} path Path
			 *
			 * @return undefined
			 */
			_stPath : function(node, path) {
				if (path !== '') {
					$('<span>')
					.click(function(){
						Vigu.Grid.parameters.path = path;
						$('input[name="search"]').val(path);
						Vigu.Grid.reload();
					})
					.text(' in ')
					.append($('<span>')
							.text(path)
					)
					.appendTo(node);
				}
			}
		};
})(jQuery);
