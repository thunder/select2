/**
 * @file
 * Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  var passIt = function () {
    function SelectAll() {}

    SelectAll.prototype.render = function (decorated) {
      var $rendered = decorated.call(this);
      var self = this;

      var $selectAll = $(
        '<button type="button">Select All</button>'
      );

      $rendered.find('.select2-dropdown').prepend($selectAll);

      $selectAll.on('click', function (e) {
        var $results = $rendered.find('.select2-results__option[aria-selected=false]');

        // Get all results that aren't selected
        $results.each(function () {
          var $result = $(this);

          // Get the data object for it
          var data = $result.data('data');

          // Trigger the select event
          self.trigger('select', {
            data: data
          });
        });

        self.trigger('close');
      });

      return $rendered;
    }

    return SelectAll;
  }


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
        config.templateSelection = function (option, container) {
          // The placeholder doesn't have value.
          if ('element' in option && 'value' in option.element) {
            // Add option value to selection container for sorting.
            $(container).data('optionValue', option.element.value);
          }
          return option.text;
        };

        $.fn.select2.amd.require([
          'select2/utils',
          'select2/dropdown',
          'select2/dropdown/attachBody'
        ], function (Utils, Dropdown, AttachBody) {
          config.dropdownAdapter = Utils.Decorate(
            Utils.Decorate(
              Dropdown,
              AttachBody
            ),
            passIt()
          );

        });

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
                $select.prepend($select.find('option[value="' + $(this).data('optionValue') + '"]').first());
              });
            }
          });
        }
      });
    }
  };

})(jQuery, drupalSettings);
