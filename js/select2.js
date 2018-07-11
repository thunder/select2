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
          var entity = $.trim(params.term);
          return {
            id: "$ID:" + entity,
            text: entity
          }
        };
        config.templateSelection = function (state) {
          var published = true;
          if(state.hasOwnProperty('published')){
            published = state.published;
          } else if (config.items[state.id] && config.items[state.id].hasOwnProperty('published')) {
            published = config.items[state.id].published;
          }

          var classes = published ? 'published' : 'unpublished';
          return $('<span class="' + classes + '">' + state.text + '</span>');
        };

        $(this).css('width', '100%').select2(config);

        var that = $(this);
        $.each(config.items, function(index, data) {
          var newOption = new Option(data.text, data.id, data.selected, data.selected);
          // Append it to the select
          that.append(newOption).trigger('change');
        });
      })
    }
  };

})(jQuery, drupalSettings);
