/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2_publish = {
    attach: function (context) {
      $('.select2-widget', context).on('select2:select', function (e) {
        var option = e.params.data;
        console.log(option);
        var published = (option.published === true || $(option.element).attr('data-published') === 'true');

        $(this).addClass(published ? 'published' : 'unpublished');
        $(option.element).trigger({type: 'change'});
      });
    }
  };

})(jQuery, drupalSettings);
