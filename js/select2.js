/**
 * @file
 * Select2 integration.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.select2 = {
    attach: function (context) {
      $.each(drupalSettings.select2, function(selector, settings) {
        $('[data-drupal-selector=' + selector + ']').select2(settings);
      });
    }
  };

})(jQuery);
