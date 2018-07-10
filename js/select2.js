/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2 = {
    attach: function (context) {
      $('.select2-widget', context).once('select2-init').each(function () {
        $(this)
          .css('width', '100%')
          .select2(drupalSettings.select2[$(this).attr('data-drupal-selector')])
          // Move option to the end after selecting it.
          // @see {@link https://github.com/select2/select2/issues/3106#issuecomment-339594053|In multi-select, selections do not appear in the order in which they were selected}
          .on('select2:select', function(e){
            var id = e.params.data.id;
            var option = $(e.target).children('[value='+id+']');
            option.detach();
            $(e.target).append(option).change();
        });
      })
    }
  };

})(jQuery, drupalSettings);
