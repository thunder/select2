/**
 * @file
 * Init select2 widget.
 */

(function ($) {

  'use strict';

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsSelect2Widget = {
    attach: function (context, settings) {
      Drupal.facets.initSelect2(context, settings);
    }
  };

  /**
   * Turns all facet links into a dropdown with options for every link.
   *
   * @param {object} context
   *   Context.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.initSelect2 = function (context, settings) {
    // Find all dropdown facet links and turn them into an option.
    $('.js-facets-select2').once('facets-select2').each(function () {
      var $dropdown = $(this);

      $dropdown.addClass('facets-dropdown');
      $dropdown.addClass('js-facets-dropdown');

      // Go to the selected option when it's clicked.
      $dropdown.on('change.facets', function () {
        var url = $($dropdown).val();
        if (!url) {
          url = $($dropdown).data('drupal-facet-cancel-url');
        }
        window.location.href = url;
      });

      // Replace links with dropdown.
      Drupal.attachBehaviors($dropdown.parent()[0], Drupal.settings);
    });
  };

})(jQuery);
