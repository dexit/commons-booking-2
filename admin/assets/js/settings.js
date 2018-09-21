(function($) {
	'use strict';
	/** allow jumping to admin tabs via url hash  */
  $(function() {
		$('#tabs').tabs({
			beforeActivate: function (event, ui) {
				var hash = ui.newTab.children("li a").attr("href");
				window.location.hash = hash;
			}
		});
  });
})(jQuery);
