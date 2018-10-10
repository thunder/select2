<?php

namespace Drupal\select2_facets\Plugin\facets\widget;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
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
  public function defaultConfiguration() {
    return [
      'autocomplete' => FALSE,
      'match_operator' => 'CONTAINS',
    ] + parent::defaultConfiguration();
  }

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

    $element = [
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

    if ($this->getConfiguration()['autocomplete']) {
      $element['#autocomplete'] = [
        'process' => [$this, 'processFacetAutocomplete'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form['autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => t('Autocomplete'),
      '#default_value' => $this->getConfiguration()['autocomplete'],
      '#description' => t('Options will be lazy loaded. This is recommended for lists with a lot of values.'),
    ];
    $form['match_operator'] = [
      '#type' => 'radios',
      '#title' => t('Autocomplete matching'),
      '#default_value' => $this->getConfiguration()['match_operator'],
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
      '#states' => [
        'visible' => [
          ':input[name$="widget_config[autocomplete]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Returns the options for the match operator.
   *
   * @return array
   *   List of options.
   */
  protected function getMatchOperatorOptions() {
    return [
      'STARTS_WITH' => t('Starts with'),
      'CONTAINS' => t('Contains'),
    ];
  }

  /**
   * Set the autocomplete route properties.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The render element with autocomplete settings.
   */
  public function processFacetAutocomplete(array $element) {
    $selection_settings = [
      'request' => serialize(\Drupal::request()),
      'match_operator' => $this->getConfiguration()['match_operator'],
    ];

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $data = serialize($selection_settings) . $this->facet->getFacetSourceId() . $this->facet->id();
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'select2_facets.facet_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'facetsource_id' => $this->facet->getFacetSourceId(),
      'facet_id' => $this->facet->id(),
      'selection_settings_key' => $selection_settings_key,
    ];

    return $element;
  }

}
