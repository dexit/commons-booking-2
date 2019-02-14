function cb2_process(){
	var $ = jQuery;
	var WP_DEBUG = $('body.cb2-WP_DEBUG-on').length;

	$('.cb2-javascript-form input[type=button]').click(function(){
		var sExisting;
		var sRedirect = document.location;
		var sQuery    = unescape(document.location.search.replace(/^\?/, ''));
		var aQuery    = sQuery.split('&');
		var jForm     = $(this).closest('.cb2-javascript-form');
		var jInputs   = jForm.find(":input");

		jInputs.each(function(){
			// Attribute switching so that the form inputs can exist inside the outer form
			var sJSName =  $(this).attr('js-name');
			if (sJSName)   $(this).attr('name', sJSName);
			else sJSName = $(this).attr('name');

			// Remove existing parameters
			// so that double submits do not aggregate
			if (sJSName) {
				sJSName = sJSName.replace(/\[\d+\]/, '[]');
				var i = aQuery.length;
				while (i > 0) {
					i--;
					sExisting = aQuery[i].replace(/=.*/, '').replace(/\[[0-9]+\]/, '[]');
					if (sExisting == sJSName)
						aQuery.splice(i, 1);
				}
			}
		});

		sQuery  = aQuery.join('&');
		sQuery += '&';
		sQuery += jInputs.serialize()
		sQuery += '&redirect=' + escape(sRedirect);

		document.location = document.location.pathname + '?' + sQuery;
	});

	$('.cb2-template-available > .cb2-details').click(function(e){
		var container      = $(this).parent();
		var checkbox       = $(this).children('.cb2-perioditem-selector');
		var cssClass       = $(this).attr('class').trim();
		var target         = $(e.target);
		var clicked_input  = (target.is(checkbox));
		var is_checked     = checkbox.attr('checked');

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
		//e.stopPropagation();

		// Prevent any default container <a> working
		if (!clicked_input) e.preventDefault();
	});

	$('.cb2-nexts').each(function(){
		var nexts  = $(this).find('li');
		var ids    = '';
		var hrefs  = nexts.find('a').each(function() {
			ids += (ids?',':'') + $(this).attr('href');
		});
		var panels = $(ids);
		panels.hide();

		$(this).closest('.cb2-popup,body').addClass('cb2-with-nexts');
		$(this).closest('.cb2-popup,#post-body').removeClass('columns-2');

		nexts.click(function(e) {
			var next  = $(this);
			var href  = next.find('a').attr( 'href' );
			var panel = $(href);

			// Close other nexts
			nexts.removeClass('selected');
			panels.hide();

			// Open target next
			next.addClass('selected');
			panel.focus();
			panel.show();

			if (next.hasClass('cb2-last')) {
				$('.cb2-popup-form-next').hide();
				$('.cb2-popup-form-save').show();
			}
			else {
				$('.cb2-popup-form-next').show();
				$('.cb2-popup-form-save').hide();
			}

			e.preventDefault();
		});

		// Open first next
		if (nexts.length) {
			var next  = nexts.filter('.cb2-selected');
			if (!next.length) next = nexts.eq(0);
			var href  = next.find('a').attr( 'href' );
			var panel = $(href);
			next.addClass('selected');
			panel.show();
		}
	});

	$('.cb2-popup-form-next').click(function(){
		//TODO: cb2-popup-form-next
	});

	$('.cb2-tabs').each(function(){
		var tabs   = $(this).find('li');
		var ids    = '';
		var hrefs  = tabs.find('a').each(function() {
			ids += (ids?',':'') + $(this).attr('href');
		});
		var panels = $(ids);
		panels.hide();

		$(this).closest('.cb2-popup,body').addClass('cb2-with-tabs');
		$(this).closest('.cb2-popup,#post-body').removeClass('columns-2');
		$(this).addClass('cb2-processed');

		tabs.click(function(e) {
			var tab   = $(this);
			var href  = tab.find('a').attr( 'href' );
			var panel = $(href);

			// Close other tabs
			tabs.removeClass('selected');
			panels.hide();
			tabs.each(function(){
				var other_href = $(this).find('a').attr( 'href' );
				other_href = other_href.replace(/^#/, '');
				$(this).closest('.cb2-popup,body').removeClass('cb2-tabs-' + other_href + '-selected');
			});
			href = href.replace(/^#/, '');
			tab.closest('.cb2-popup,body').addClass('cb2-tabs-' + href + '-selected');

			// Open target tab
			tab.addClass('selected');
			panel.focus();
			panel.show();

			e.preventDefault();
		});

		// Open first tab
		if (tabs.length) {
			var tab   = tabs.filter('.cb2-selected');
			if (!tab.length) tab = tabs.eq(0);
			var href  = tab.find('a').attr( 'href' );
			var panel = $(href);
			tab.addClass('selected');
			href = href.replace(/^#/, '');
			tab.closest('.cb2-popup,body').addClass('cb2-tabs-' + href + '-selected');
			panel.show();
		}
	});

	$('.cb2-popup .cb2-advanced').click(function(e){
		$(this).closest('.cb2-popup, .cb2-ajax-edit-form').toggleClass('cb2-with-template-post');
		e.preventDefault();
	});

	$('.cb2-save-visible-ajax-form').click(function() {
		// TODO: Save all the forms, or just the visible one?
		var self   = this;
		var form   = $(self).closest('.cb2-ajax-edit-form');
		var data   = form.find(':input').serialize();
		var action = form.attr('action');

		$(self).attr('disabled', '1');
		$(self).parents('.cb2-popup, body').addClass('cb2-saving');
		$.post({
			url: action,
			data: data,
			success: function(){
				$(self).removeAttr('disabled');
				$(self).parents('.cb2-popup, body').removeClass('cb2-saving');
				if (!WP_DEBUG) {
					// TODO: callback based refresh => calendar ajax refresh
					document.location = document.location;
					$(self).parents('.cb2-popup, body').addClass('cb2-refreshing');
				}
			},
			error: function() {
				$(self).addClass('cb2-ajax-failed');
				$(self).parents('.cb2-popup, body').addClass('cb2-ajax-failed');
			}
		});
	});

	$('.cb2-removable-item')
		.append('<input type="button" value="x"/>')
		.children('input').click(function(){
			$(this).parent().remove();
		});

	$('.cb2-calendar-krumo-show').click(function(){
		$(this).parent().find('.cb2-calendar-krumo').show();
	});

	$('form').submit(function(){
		var datepickers = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
		datepickers.show();
	});

	var datepickers       = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
	var explanation       = $('.cmb2-id-period-explanation p');
	var recurrence_boxes  = $('.cmb2-id-recurrence-sequence, .cmb2-id-datetime-from, .cmb2-id-datetime-to');
	var sequence          = $('.cmb2-id-recurrence-sequence');
	var sequence_checks   = sequence.find('.cmb2-checkbox-list');
	var daily_html        = sequence.find('.cmb2-checkbox-list').html();
	var recurrence_inputs = recurrence_boxes.find('input');

	$('.cmb2-id-recurrence-type input').click(function(){
		datepickers.hide(); // Hide so that values still work!
		recurrence_inputs.removeAttr('disabled');
		recurrence_boxes.removeClass('cb2-disabled');

		switch ($(this).val()) {
			case '__Null__': {
				explanation.html('To create separate repeating slots see <b>Recurrence</b> below. '
					+ 'For example: repeats Mon - Fri 8:00 - 18:00 should use Daily <b>Recurrence Type</b> '
					+ 'and Mon - Fri <b>Sequence</b>.');
				datepickers.show();
				recurrence_boxes.addClass('cb2-disabled');
				recurrence_inputs.attr('disabled', '1');
				break;
			}
			case 'H': {
				explanation.html('Only the times are relevant now because the period repeats hourly.');
				var options = '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">6-7</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence2" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">7-8</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence3" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">8-9</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence4" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">9-10</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence5" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">10-11</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence6" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">11-12</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence7" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">12-13</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence8" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">13-14</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence9" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">14-15</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence10" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">15-16</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence11" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">16-17</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence12" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">17-18</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence13" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">18-19</label></li>';
				options += '<li><a href="#">...</label></li>';
				sequence_checks.html(options);
				break;
			}
			case 'D': {
				explanation.html('Only the times are relevant now because the period repeats every day.');
				sequence_checks.html(daily_html);
				break;
			}
			case 'W': {
				// TODO: replace with Mon-Sun day picker
				explanation.html('The date indicates the day-of-the-week that repeats.');
				var options = '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">1</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence2" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence3" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">3</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence4" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">4</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence5" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">5</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence6" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">6</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence7" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">7</label></li>';
				options += '<li><a href="#">...</label></li>';
				datepickers.show();
				sequence_checks.html(options);
				break;
			}
			case 'M': {
				// TODO: replace with 1-31 day picker
				explanation.html('The date indicates the day-of-the-month that repeats. The month and year are now irrelevant');
				var options = '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Jan</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Feb</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Mar</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Apr</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">May</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Jun</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Jul</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Aug</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Sep</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Oct</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Nov</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">Dec</label></li>';
				datepickers.show();
				sequence_checks.html(options);
				break;
			}
			case 'Y': {
				// TODO: replace with month-day picker
				explanation.html('The date indicates the day-of-the-year that repeats. The year is now irrelevant');
				var options = '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2019</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2020</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2021</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2022</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2023</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2024</label></li>';
				options += '<li><input type="checkbox" class="cmb2-option" name="recurrence_sequence[]" id="recurrence_sequence1" value="1" data-hash="jna7s5t53nc0" disabled="disabled"><label for="recurrence_sequence1">2025</label></li>';
				options += '<li><a href="#">...</label></li>';
				datepickers.show();
				sequence_checks.html(options);
				break;
			}
		}
	});
	$('.cmb2-id-recurrence-type input[checked]').click();
}

(function($) {
  'use strict';
  $(document).ready(cb2_process);
	$(window).on('cb2-popup-appeared', cb2_process);
})(jQuery);
