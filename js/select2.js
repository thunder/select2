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
          //TODO: Need to add the default status
          console.log(params);
          var entity = $.trim(params.term);
          return {
            id: "$ID:" + entity,
            text: entity
          }
        };
        config.templateSelection = function (option) {
          var published = true;
          if(option.hasOwnProperty('published')){
            published = option.published;
          } else if (config.items[option.id] && config.items[option.id].hasOwnProperty('published')) {
            published = config.items[option.id].published;
          }

          var classes = published ? 'published' : 'unpublished';
          return $('<span class="' + classes + '">' + option.text + '</span>');
        };

        $(this).css('width', '100%').select2(config);

        var that = $(this);
        $.each(config.items, function(index, data) {
          that.append(new Option(data.text, data.id, data.selected, data.selected));
        });
      })
    }
  };

})(jQuery, drupalSettings);
