(function($) {
  'use strict';
  $(function() {
    // Write in console log the PHP value passed in enqueue_js_vars in public/class-plugin-name.php
		// console.log(pn_js_vars.alert);

		$(document).ready(function(){
			$('.cb2-selectable > .cb2-details').click(function(e){
				var selection_container = $(this).closest('.cb2-selection-container');
				var container      = $(this).parent();
				var checkbox       = $(this).children('.cb2-periodinst-selector');
				var target         = $(e.target);
				var clicked_input  = (target.is(checkbox));
				var is_checked     = checkbox.attr('checked');
				var selection_mode = 'none';
				var selections     = $();
				var selectables    = $();
				var settings       = {};
				var earliest, latest, range;

				// The default checkbox event will check the checkbox
				// AFTER this action
				if (clicked_input) is_checked = !is_checked;
				if (is_checked) {
					if (!clicked_input) checkbox.removeAttr('checked');
					container.removeClass( 'cb2-selected' );
				} else {
					if (!clicked_input) checkbox.attr('checked', '1');
					container.addClass( 'cb2-selected' );
				}

				// See if we have a selection mode
				if (selection_container.length) {
					var selection_container_class = selection_container.attr('class');
					var selection_mode_matches    = selection_container_class.match(/cb2-selection-mode-([^ ]+)/);
					var selection_container_js_m  = selection_container_class.match(/cb2_settings_([^ ]+)_([^ ]+)/);
					var selection_container_js;
					if ( selection_container_js_m ) {
						selection_container_js = selection_container_js_m[0];
						settings = ( window[selection_container_js] ? window[selection_container_js] : {} );
						if (window.console) console.log(settings);
					}
					selectables = selection_container.find('.cb2-selectable, .cb2-not-includable');
					selections  = selection_container.find('.cb2-selected');
					earliest    = selections.first();
					latest      = selections.last();
					if (selection_mode_matches) selection_mode = selection_mode_matches[1];
				}

				// Selection styles
				switch (selection_mode) {
					case 'range':
						switch (selections.length) {
							case 0:
								selectables.removeClass('cb2-range-selected');
								break;
							case 1:
								selectables.removeClass('cb2-range-selected');
								break;
							case 3:
								if (container.hasClass('cb2-range-selected')) {
									// Start again from 1 select
									selectables
										.removeClass('cb2-range-selected')
										.removeClass('cb2-selected')
										.find(':input').removeAttr('checked');
									if (!clicked_input) checkbox.attr('checked', '1');
									container.addClass('cb2-selected')
									break;
								}
							case 2:
								// TODO: Should we go full OO on this and create a JS class for each PHP class?
								var at_earliest = false, at_latest = false, inbetween = false,
									bcontinue = true, range_selected = 0;
								// We loop here because the cb2-selectable are NOT siblings
								selectables.removeClass('cb2-range-selected');
								selectables.each(function(){
									var jThis     = $(this);
									if (jThis.is(earliest))
										at_earliest = true;
									if (jThis.is(latest)) {
										at_latest = true;
										inbetween = false;
									}
									var inrange = (at_earliest || inbetween || at_latest);

									if (inbetween) {
										// Others that are checked in the middle
										jThis
											.removeClass('cb2-selected')
											.find(':input').removeAttr('checked');
									}

									if (inrange) {
										if (settings.selection_periods_max && range_selected >= settings.selection_periods_max) {
											selectables
												.removeClass('cb2-range-selected')
												.removeClass('cb2-selected')
												.find(':input').removeAttr('checked');
											// TODO: Some better translateable warning
											alert('cannot select more than ' + settings.selection_periods_max + ' periods');
											bcontinue = false;
										}
										else if (jThis.hasClass('cb2-not-includable')) {
											selectables
												.removeClass('cb2-range-selected')
												.removeClass('cb2-selected')
												.find(':input').removeAttr('checked');
											// TODO: Some better translateable warning
											alert('cannot select accross non-inclusions');
											bcontinue = false;
										}
										else {
											jThis.addClass('cb2-range-selected');
											range_selected++;
										}
									}

									if (at_earliest) inbetween = true;
									return bcontinue && !at_latest;
								});
								break;
							default:
								if (window.console) console.error('too many selections: ' + selections.length);
						}
						break;
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
