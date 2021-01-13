/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings, Sortable) {
  'use strict';

  $.fn.select2.amd.define("CustomDropdownAdapter", [
      'select2/utils',
      'select2/dropdown',
      'select2/dropdown/attachBody'
    ],
    function (Utils, Dropdown, AttachBody) {

      let dropdownWithButton = Utils.Decorate(Dropdown, AttachBody);

      dropdownWithButton.prototype.render = function () {
        var $rendered = Dropdown.prototype.render.call(this);

        var $selectAll = $(
          '<button type="button">Select All</button>'
        );

        var self = this;

        $selectAll.on('click', function (e) {
          // Get all results that aren't selected.
          let $results = $rendered.find('.select2-results__option[aria-selected=false]');

          $results.each(function (foo, bar) {
            // Trigger the select event.
            self.trigger('select', {
              data: Utils.GetData(bar, 'data')
            });
          });
          self.trigger('close');
        });

        $rendered.prepend($selectAll);

        return $rendered;
      };

      return Utils.Decorate(dropdownWithButton, AttachBody);
    });

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
        config.dropdownAdapter = $.fn.select2.amd.require("CustomDropdownAdapter")

        config.templateSelection = function (option, container) {
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
        if (Object.prototype.hasOwnProperty.call(config, 'ajax') && config.multiple) {
          var $select = $(this);
          var $list = $select.next('.select2-container').find('ul.select2-selection__rendered');
          Sortable.create($list[0], {
            draggable: 'li:not(.select2-search)',
            forceFallback: true,
            onEnd: function () {
              $($list.find('.select2-selection__choice').get().reverse()).each(function () {
                $select.prepend($select.find('option[value="' + $(this).data('optionValue') + '"]').first());
              });
            }
          });
        }
      });
    }
  };

})(jQuery, drupalSettings, Sortable);
