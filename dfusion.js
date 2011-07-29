(function ($) {

Drupal.behaviors.dfusion = {
  attach: function (context) {
    var loginElements = $('.form-item-name, .form-item-pass, li.dfusion-link');
    var dfusionElements = $('.form-item-dfusion-identifier, li.user-link');
    var cookie = $.cookie('Drupal.visitor.dfusion_identifier');

    // This behavior attaches by ID, so is only valid once on a page.
    if (!$('#edit-dfusion-identifier.dfusion-processed').size()) {
      if (cookie) {
        $('#edit-dfusion-identifier').val(cookie);
      }
      if ($('#edit-dfusion-identifier').val()) {
        $('#edit-dfusion-identifier').addClass('dfusion-processed');
        loginElements.hide();
        // Use .css('display', 'block') instead of .show() to  Konqueror friendly.
        dfusionElements.css('display', 'block');
      }
    }

    $('li.dfusion-link:not(.dfusion-processed)', context)
      .addClass('dfusion-processed')
      .click(function () {
         loginElements.hide();
         dfusionElements.css('display', 'block');
        // Remove possible error message.
        $('#edit-name, #edit-pass').removeClass('error');
        $('div.messages.error').hide();
        // Set focus on DFusion Identifier field.
        $('#edit-dfusion-identifier')[0].focus();
        return false;
      });
    $('li.user-link:not(.dfusion-processed)', context)
      .addClass('dfusion-processed')
      .click(function () {
         dfusionElements.hide();
         loginElements.css('display', 'block');
        // Clear DFusion Identifier field and remove possible error message.
        $('#edit-dfusion-identifier').val('').removeClass('error');
        $('div.messages.error').css('display', 'block');
        // Set focus on username field.
        $('#edit-name')[0].focus();
        return false;
      });
  }
};

})(jQuery);
