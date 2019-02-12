/**
 * @file
 * Init select2 widget.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.facets = Drupal.facets || {};

  /**
   * Add event handler to all select2 widgets.
   */
  Drupal.facets.initSelect2 = function () {
    $('.js-facets-select2').once('facets-select2').each(function () {
      // Go to the selected option when it's clicked.
      $(this).on('select2:select select2:unselect', function (item) {
        $(this).trigger('facets_filter', [ item.params.data.id ]);
      });
    });
  };

  /**
   * Behavior to register select2 widget to be used for facets.
   */
  Drupal.behaviors.facetsSelect2Widget = {
    attach: function (context, settings) {
      Drupal.facets.initSelect2(context, settings);
    }
  };

})(jQuery, Drupal);
