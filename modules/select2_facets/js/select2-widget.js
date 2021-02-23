/**
 * @file
 * Init select2 widget.
 */

(($, Drupal) => {
  Drupal.facets = Drupal.facets || {};

  /**
   * Add event handler to all select2 widgets.
   */
  Drupal.facets.initSelect2 = () => {
    $('.js-facets-select2.js-facets-widget')
      .once('js-facets-select2-widget-on-selection-change')
      .each(() => {
        const $select2Widget = $(this);

        $select2Widget.on('select2:select select2:unselect', (item) => {
          $select2Widget.trigger('facets_filter', [item.params.data.id]);
        });

        $select2Widget.on('facets_filtering.select2', () => {
          $select2Widget.prop('disabled', true);
        });
      });
  };

  /**
   * Behavior to register select2 widget to be used for facets.
   */
  Drupal.behaviors.facetsSelect2Widget = {
    attach(context, settings) {
      Drupal.facets.initSelect2(context, settings);
    },
  };
})(jQuery, Drupal);
