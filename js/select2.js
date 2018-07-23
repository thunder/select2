/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2 = {
    attach: function (context) {
      $('.select2-widget', context).once('select2-init').each(function () {
        var config = drupalSettings.select2[$(this).attr('data-drupal-selector')];
        config.createTag = function (params) {
          var term = $.trim(params.term);
          if (term === '') {
            return null;
          }
          return {
            id: '$ID:' + term,
            text: term
          };
        };
        $(this).css('width', '100%').select2(config);
      });
    }
  };

})(jQuery, drupalSettings);
