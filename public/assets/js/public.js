(function($) {
  'use strict';
  $(function() {
    // Write in console log the PHP value passed in enqueue_js_vars in public/class-plugin-name.php
		// console.log(pn_js_vars.alert);

		$(document).ready(function(){
			$('.cb2-template-available > .cb2-details').click(function(e){
				var container      = $(this).parent();
				var checkbox       = $(this).children('.cb2-perioditem-selector');
				var cssClass       = $(this).attr('class').trim();
				var target         = $(e.target);
				var clicked_input  = (target.is(checkbox));
				var is_checked     = checkbox.attr('checked');
				var selection_mode = 'none';
				var selections     = $();

				// See if we have a selection mode
				var selection_container = $(this).closest('.cb2-selection-container');
				if (selection_container.length) {
					var selection_container_class = selection_container.attr('class');
					var selection_mode_matches    = selection_container_class.match(/cb2-selection-mode-([^ ]+)/);
					selections = selection_container.find('.cb2-selected');
					if (selection_mode_matches) selection_mode = selection_mode_matches[1];
				}

				// Selection styles
				switch (selection_mode) {
					case 'range':
						switch (selections.length) {
							case 0:
								break;
							case 1:
								// TODO: Create a continuous link between the selected items
								break;
							default:
								// TODO: move the selections to change the range
						}
						break;
				}

				// The default checkbox event will check the checkbox
				// AFTER this action
				if (clicked_input) is_checked = !is_checked;

				if (is_checked) {
					if (!clicked_input) checkbox.removeAttr('checked');
					container.attr( 'class', cssClass.replace(/cb2-selected/, '') );
				} else {
					if (!clicked_input) checkbox.attr('checked', '1');
					container.attr( 'class', cssClass + ' cb2-selected' );
				}

				// Prevent any container clicks from bubbling
				e.stopPropagation();

				// Prevent any default container <a> working
				if (!clicked_input) e.preventDefault();
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
})(jQuery);
