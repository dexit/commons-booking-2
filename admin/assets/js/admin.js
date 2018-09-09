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
	});
})(jQuery);
