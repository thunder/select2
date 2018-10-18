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
   * Add event handler to all select2 widgets.
   *
   * @param {object} context
   *   Context.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.initSelect2 = function (context, settings) {
    $('.js-facets-select2').once('facets-select2').each(function () {
      // Go to the selected option when it's clicked.
      $(this).on('select2:select select2:unselect', function (item) {
        window.location.href = item.params.data.id;
      });
    });
  };

})(jQuery);
