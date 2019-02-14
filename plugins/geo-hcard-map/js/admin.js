(function($) {
  "use strict";

  $(document).ready(function(){
    var jAjaxForms = $('form.geo-hcard-map-ajax');
    
    jAjaxForms.on('submit', function(e){
      var jForm = $(this);
      jForm.parent().children('.submitting').fadeIn();
      
      $.ajax({ 
        data: jForm.serialize(),
        type: jForm.attr('method'),
        url:  jForm.attr('action'),
        success: function(data) {
          jForm.fadeOut();
          jForm.parent().children('.submitting').fadeOut();
          jForm.parent().children(".success").fadeIn();
        }
      });
      return false;
    });
    jAjaxForms.find('input, select').on('change', function(){
      $(this).closest('form').submit();
    });
    jAjaxForms.find('input[type=submit]').hide();
  });
})(jQuery);