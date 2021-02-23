/**
 * @file
 * Select2 integration.
 */
(($, drupalSettings, Sortable) => {
  Drupal.behaviors.select2 = {
    attach(context) {
      $('.select2-widget', context)
        .once('select2-init')
        .each(() => {
          let config = $(this).data('select2-config');
          config.createTag = (params) => {
            const term = $.trim(params.term);
            if (term === '') {
              return null;
            }
            return {
              id: `$ID:${term}`,
              text: term,
            };
          };
          config.templateSelection = (option, container) => {
            // The placeholder doesn't have value.
            if ('element' in option && 'value' in option.element) {
              // Add option value to selection container for sorting.
              $(container).data('optionValue', option.element.value);
            }
            return option.text;
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
          if (
            Object.prototype.hasOwnProperty.call(config, 'ajax') &&
            config.multiple
          ) {
            const $select = $(this);
            const $list = $select
              .next('.select2-container')
              .find('ul.select2-selection__rendered');
            Sortable.create($list[0], {
              draggable: 'li:not(.select2-search)',
              forceFallback: true,
              onEnd() {
                $(
                  $list.find('.select2-selection__choice').get().reverse(),
                ).each(() => {
                  $select.prepend(
                    $select
                      .find(`option[value="${$(this).data('optionValue')}"]`)
                      .first(),
                  );
                });
              },
            });
          }
        });
    },
  };
})(jQuery, drupalSettings, Sortable);
