/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.select2 = {
    attach: function (context) {
      $('.select2-widget', context).once('select2-init').each(function () {
        var config = $(this).data('select2-config');
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
        $(this).data('select2-config', config);

        // Emit an event, that other modules have the chance to modify the
        // select2 settings. Make sure that other JavaScript code that rely on
        // this event will be loaded first.
        // @see: modules/select2_publish/select2_publish.libraries.yml
        $(this).trigger('select2-init');
        config = $(this).data('select2-config');

        // If config has a dropdownParent property, wrap it a jQuery object.
        if (Object.prototype.hasOwnProperty.call(config, 'dropdownParent')) {
          config.dropdownParent = $(config.dropdownParent);
        }
        $(this).select2(config);

        // Copied from https://github.com/woocommerce/woocommerce/blob/master/assets/js/admin/wc-enhanced-select.js#L118
        if (Object.prototype.hasOwnProperty.call(config, 'ajax')) {
          var $select = $(this);
          var $list = $(this).next('.select2-container').find('ul.select2-selection__rendered');
          $list.sortable({
            placeholder: 'ui-state-highlight select2-selection__choice',
            forcePlaceholderSize: true,
            items: 'li:not(.select2-search__field)',
            tolerance: 'pointer',
            stop: function () {
              $($list.find('.select2-selection__choice').get().reverse()).each(function () {
                var id = $(this).data('data').id;
                var option = $select.find('option[value="' + id + '"]')[0];
                $select.prepend(option);
              });
            }
          });
        }
      });
    }
  };

})(jQuery, drupalSettings);
