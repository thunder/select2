/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2 = {
    attach: function (context) {
      $('.select2-widget', context).once('select2-init').each(function () {
        $(this).select2(drupalSettings.select2[$(this).attr('data-drupal-selector')]);
      })
    }
  };

})(jQuery, drupalSettings);
