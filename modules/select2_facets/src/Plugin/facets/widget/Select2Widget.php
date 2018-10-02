<?php

namespace Drupal\select2_facets\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;

/**
 * The select2 widget.
 *
 * @FacetsWidget(
 *   id = "select2",
 *   label = @Translation("Select2"),
 *   description = @Translation("A configurable widget that shows a select2."),
 * )
 */
class Select2Widget extends WidgetPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {

    $this->facet = $facet;

    $items = [];
    $active_items = [];

    foreach ($facet->getResults() as $result) {
      if (empty($result->getUrl())) {
        continue;
      }
      // When the facet is being build in an AJAX request, and the facetsource
      // is a block, we need to update the url to use the current request url.
      if ($result->getUrl()->isRouted() && $result->getUrl()->getRouteName() === 'facets.block.ajax') {
        $request = \Drupal::request();
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($request->getPathInfo());
        if ($url_object) {
          $url = $result->getUrl();
          $result->setUrl(new Url($url_object->getRouteName(), $url_object->getRouteParameters(), $url->getOptions()));
        }
      }

      $items[$result->getUrl()->toString()] = $result->getDisplayValue();
      if ($result->isActive()) {
        $active_items[] = $result->getUrl()->toString();
      }

    }

    return [
      '#type' => 'select2',
      '#options' => $items,
      '#required' => FALSE,
      '#value' => $active_items,
      '#multiple' => !$facet->getShowOnlyOneResult(),
      '#name' => $facet->getName(),
      '#attributes' => [
        'data-drupal-selector' => 'facet-' . $facet->id(),
        'class' => ['js-facets-select2'],
      ],
      '#attached' => [
        'library' => ['select2_facets/drupal.select2_facets.select2-widget'],
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    return $form;
  }

}
