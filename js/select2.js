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
          var term = $.trim(params.term);
          if (term === '') {
            return null;
          }
          return {
            id: "$ID:" + term,
            text: term,
            published: config.autocreate_status
          }
        };
        config.templateSelection = function (option) {
          var published = option.published === true || $(option.element).attr('data-published') === true;
          var classes = published ? 'published' : 'unpublished';
          return $('<span class="' + classes + '">' + option.text + '</span>');
        };

        $(this).css('width', '100%').select2(config);

        // We have to initialize the options on our own, because if ajax is used
        // the data property doesn't work and we need a way to add custom
        // properties.
        var that = $(this);
        $.each(config.items, function(index, data) {
          var option = new Option(data.text, data.id, data.selected, data.selected);
          $(option).attr('data-published', data.published);
          that.append(option);
        });
      })
    }
  };

})(jQuery, drupalSettings);
