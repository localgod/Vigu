/*
 * jQuery Notifications - v1.0
 *
 * Copyright 2011 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)
 *
 * Dual licensed under the MIT or GPL Version 2 licenses
 *
 */
(function ($) {

	$.notification = function (message, settings) {
		
		if (message === undefined || message === null ) return;
		
		// Merge settings with defaults
		settings = $.extend(true, {
			className: 'jquery-notification',
			duration: 2000,
			freezeOnHover: false,
			hideSpeed: 250,
			position: 'center',
			showSpeed: 250,
			zIndex: 99999
		}, settings);

		// Variables
		var width, height, top, left, windowWidth = $(window).width(),
			windowHeight = $(window).height(),
			scrollTop = $(window).scrollTop(),
			scrollLeft = $(window).scrollLeft(),
			timeout, notification = $('<div id="jquery-notification" />');

		// Skip the animation if a notification is already showing
		if ($('#jquery-notification').length > 0) settings.showSpeed = 0;

		// Clear old notifications
		$('#jquery-notification').remove();

		// Create it
		notification.appendTo($('BODY')).addClass(settings.className).text(message).css({
			position: 'absolute',
			display: 'none',
			zIndex: settings.zIndex
		}).mouseover(function () {
			if (settings.freezeOnHover) clearTimeout(timeout);
			$(this).addClass(settings.className + '-hover');
		}).mouseout(function () {
			$(this).removeClass(settings.className + '-hover');
			if (settings.freezeOnHover) {
				timeout = setTimeout(function () {
					notification.trigger('click');
				}, settings.duration);
			}
		}).click(function () {
			clearTimeout(timeout);
			notification.fadeOut(settings.hideSpeed, function () {
				$(this).remove();
			});
		}).wrapInner('<div id="jquery-notification-message" />');

		// Position it
		width = notification.outerWidth();
		height = notification.outerHeight();

		switch (settings.position) {
		case 'top':
			top = 0 + scrollTop;
			left = windowWidth / 2 - width / 2 + scrollLeft;
			break;
		case 'top-left':
			top = 0 + scrollTop;
			left = 0 + scrollLeft;
			break;
		case 'top-right':
			top = 0 + scrollTop;
			left = windowWidth - width + scrollLeft;
			break;
		case 'bottom':
			top = windowHeight - height + scrollTop;
			left = windowWidth / 2 - width / 2 + scrollLeft;
			break;
		case 'bottom-left':
			top = windowHeight - height + scrollTop;
			left = 0 + scrollLeft;
			break;
		case 'bottom-right':
			top = windowHeight - height + scrollTop;
			left = windowWidth - width + scrollLeft;
			break;
		case 'left':
			top = windowHeight / 2 - height / 2 + scrollTop;
			left = 0 + scrollLeft;
			break;
		case 'right':
			top = windowHeight / 2 - height / 2 + scrollTop;
			left = windowWidth - width + scrollLeft;
			break;
		default:
		case 'center':
			top = windowHeight / 2 - height / 2 + scrollTop;
			left = windowWidth / 2 - width / 2 + scrollLeft;
			break;
		}

		// Show it
		notification.css({
			top: top,
			left: left
		}).fadeIn(settings.showSpeed, function () {
			// Hide it
			timeout = setTimeout(function () {
				notification.trigger('click');
			}, settings.duration);
		});

	};

})(jQuery);