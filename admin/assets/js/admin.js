(function($) {
  'use strict';
  $(document).ready(function(){
		$('.type-perioditem-location').click(function(){
			var checkbox = $(this).find( '.cb2-perioditem-selector' );

			if (checkbox.attr('checked')) {
				checkbox.removeAttr('checked');
				$(this).attr( 'class', $(this).attr('class').replace(/cb2-booked/, '') );
			} else {
				checkbox.attr('checked', '1');
				$(this).attr( 'class', $(this).attr('class') + ' cb2-booked' );
			}
		});

		$('form').submit(function(){
			var datepickers = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			datepickers.removeAttr('disabled');
		});

		$('.cmb2-id-recurrence-type input').click(function(){
			var datepickers     = $('.cmb2-id-datetime-part-period-start .cmb2-datepicker, .cmb2-id-datetime-part-period-end .cmb2-datepicker');
			var explanation     = $('.cmb2-id-explanation p');
			var sequence        = $('.cmb2-id-recurrence-sequence');
			var sequence_inputs = sequence.find('input');

			datepickers.removeAttr('disabled', '1');
			sequence.addClass('cb2-disabled');
			sequence_inputs.attr('disabled', '1');

			switch ($(this).val()) {
				case '__Null__': {
					explanation.html('To create separate repeating slots see <b>Recurrence</b> below');
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
	});

})(jQuery);
