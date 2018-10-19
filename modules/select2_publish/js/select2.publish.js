/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2_publish = {
    attach: function (context) {
      $('.select2-widget', context).on('select2-init', function (e) {
        var config = $(e.target).data('select2-config');
        config.templateSelection = config.templateResult = function (option, item) {
          if (item) {
            var published = (option.published === true || $(option.element).attr('data-published') === 'true');
            $(item).addClass(published ? 'published' : 'unpublished');
          }
          return option.text;
        };
        $(e.target).data('select2-config', config)
      });
    }
  };

})(jQuery, drupalSettings);
