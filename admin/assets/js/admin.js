(function($) {
  'use strict';
  $(document).ready(function(){
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
			e.stopPropagation();

			// Prevent any default container <a> working
			if (!clicked_input) e.preventDefault();
		});

		$('.cb2-calendar-krumo-show').click(function(){
			$(this).parent().find('.cb2-calendar-krumo').show();
		});

		$('form').submit(function(){
			var datepickers = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			datepickers.show();
		});

		$('.cmb2-id-recurrence-type input').click(function(){
			var datepickers     = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			var explanation     = $('.cmb2-id-period-explanation p');
			var sequence        = $('.cmb2-id-recurrence-sequence');
			var sequence_inputs = sequence.find('input');

			datepickers.show();
			sequence.addClass('cb2-disabled');
			sequence_inputs.attr('disabled', '1');

			switch ($(this).val()) {
				case '__Null__': {
					explanation.html('To create separate repeating slots see <b>Recurrence</b> below. '
						+ 'For example: repeats Mon - Fri 8:00 - 18:00 should use Daily <b>Recurrence Type</b> '
						+ 'and Mon - Fri <b>Sequence</b>.');
					break;
				}
				case 'D': {
					datepickers.hide();
					explanation.html('Only the times are relevant now because the period repeats every day.');
					sequence.removeClass('cb2-disabled');
					sequence_inputs.removeAttr('disabled');
					break;
				}
				case 'W': {
					// TODO: replace with Mon-Sun day picker
					explanation.html('The date indicates the day-of-the-week that repeats.');
					break;
				}
				case 'M': {
					// TODO: replace with 1-31 day picker
					explanation.html('The date indicates the day-of-the-month that repeats. The month and year are now irrelevant');
					break;
				}
				case 'Y': {
					// TODO: replace with month-day picker
					explanation.html('The date indicates the day-of-the-year that repeats. The year is now irrelevant');
					break;
				}
			}
		});
		$('.cmb2-id-recurrence-type input[checked]').click();
	});

})(jQuery);
