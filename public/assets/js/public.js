(function($) {
  'use strict';
  $(function() {
    // Write in console log the PHP value passed in enqueue_js_vars in public/class-plugin-name.php
		// console.log(pn_js_vars.alert);

		$(document).ready(function(){
			$('.cb2-template-available').click(function(e){
				var checkbox      = $(this).children('.cb2-perioditem-selector');
				var cssClass      = $(this).attr('class').trim();
				var target        = $(e.target);
				var clicked_input = (target.is(checkbox));
				var is_checked    = checkbox.attr('checked');

				// The default checkbox event will check the checkbox
				// AFTER this action
				if (clicked_input) is_checked = !is_checked;

				if (is_checked) {
					if (!clicked_input) checkbox.removeAttr('checked');
					$(this).attr( 'class', cssClass.replace(/cb2-booked/, '') );
				} else {
					if (!clicked_input) checkbox.attr('checked', '1');
					$(this).attr( 'class', cssClass + ' cb2-booked' );
				}
			});

			window.cb2 = {}; // global commons booking object

			cb2.calendarStyles = function() { // manage style of calendar by calendar size, not window width

				if ($('.cb2-calendar-grouped').length < 1) {
					return;
				}

				if ($('.cb2-calendar-grouped').outerWidth() >= 450) {
					$('.cb2-calendar-grouped').addClass('cb2-calendar-grouped-large');
				} else {
					$('.cb2-calendar-grouped').removeClass('cb2-calendar-grouped-large');
				}

			};

			cb2.calendarTooltips = function() {

				if ($('.cb2-calendar-grouped').length < 1) {
					return;
				}

				$('.cb2-slot[data-state="allow-booking"] ').parents('li.cb2-date').each(function(i, elem) {
					var template = document.createElement('div');
					template.id = $(elem).attr('id');
					var html = '<div><ul>';

					$(elem).find('[data-state="allow-booking"]').each(function(j, slot) {
						html += '<li>';
						if ($(slot).attr('data-item-thumbnail')) {
							html += '<img src="' + $(slot).attr('data-item-thumbnail') + '">';
						}
						html += '<a href="' + $(slot).attr('data-item-thumbnail') + '">';
						html += $(slot).attr('data-item-title');
						html += '</a></li>';
					});

					html += '</ul></div>';

					template.innerHTML = html;

					tippy('#' + template.id, {
						appendTo : document.querySelector('.cb2-calendar-grouped'),
						arrow : true,
						html: template,
						interactive : true,
						theme: 'cb2-calendar',
						trigger: 'click'
					}); // need to polyfill MutationObserver for IE10 if planning to use dynamicTitle

				});



			};

			cb2.init = function() {
				cb2.calendarStyles();
				cb2.calendarTooltips();
			};

			cb2.resize = function() {
				cb2.calendarStyles();
			};

			cb2.init();

			$(window).on('resize',cb2.resize);


		});

  });
// Place your public-facing JavaScript here
})(jQuery);
