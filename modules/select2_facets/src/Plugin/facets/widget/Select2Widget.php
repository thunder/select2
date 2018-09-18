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

    foreach ($facet->getResults() as $result) {
      if (empty($result->getUrl())) {
        $items[] = $this->buildResultItem($result);
      }
      else {
        // When the facet is being build in an AJAX request, and the facetsource
        // is a block, we need to update the url to use the current request url.
        if ($result->getUrl()->isRouted() && $result->getUrl()->getRouteName() === 'facets.block.ajax') {
          $request = \Drupal::request();
          $url_object = \Drupal::service('path.validator')
            ->getUrlIfValid($request->getPathInfo());
          if ($url_object) {
            $url = $result->getUrl();
            $options = $url->getOptions();
            $route_params = $url_object->getRouteParameters();
            $route_name = $url_object->getRouteName();
            $result->setUrl(new Url($route_name, $route_params, $options));
          }
        }

        $items[$result->getUrl()->toString()] = $result->getDisplayValue();
      }
    }


    return [
      '#type' => 'select2',
      '#theme' => 'select',
      '#options' => $items,
      '#required' => FALSE,
      '#attributes' => [
        'data-drupal-selector' => 'facet-' . $facet->id(),
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-facet-alias' => $facet->getUrlAlias(),
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
