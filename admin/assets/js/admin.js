(function($) {
  'use strict';
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

		$('.cb2-calendar-krumo-show').click(function(){
			$(this).parent().find('.cb2-calendar-krumo').show();
		});

		$('form').submit(function(){
			var datepickers = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			datepickers.removeAttr('disabled');
		});

		$('.cmb2-id-recurrence-type input').click(function(){
			var datepickers     = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			var explanation     = $('.cmb2-id-period-explanation p');
			var sequence        = $('.cmb2-id-recurrence-sequence');
			var sequence_inputs = sequence.find('input');

			datepickers.removeAttr('disabled', '1');
			sequence.addClass('cb2-disabled');
			sequence_inputs.attr('disabled', '1');

			switch ($(this).val()) {
				case '__Null__': {
					explanation.html('To create separate repeating slots see <b>Recurrence</b> below.'
						+ 'For example: repeats Mon - Fri 8:00 - 18:00 should use Daily <b>Recurrence Type</b>'
						+ 'and Mon - Fri <b>Sequence</b>.');
					break;
				}
				case 'D': {
					datepickers.attr('disabled', '1');
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
