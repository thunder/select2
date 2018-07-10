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
