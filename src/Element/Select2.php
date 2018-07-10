<?php

namespace Drupal\select2\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a form element for a simple select2 select box.
 *
 * @FormElement("select2")
 */
class Select2 extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = [];
    $info['#autocomplete'] = FALSE;
    $info['#pre_render'][] = [$class, 'preRenderAutocomplete'];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#multiple']) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    else {
      $empty_option = ['' => ''];
      $element['#options'] = $empty_option + $element['#options'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSelect($element) {
    $element = parent::preRenderSelect($element);
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    $multiple = $element['#multiple'];

    if ($element['#autocomplete'] && $element['#target_type']) {
      // Set only the default values to the options.
      $element['#options'] = !empty($element['#default_value']) ? array_intersect_key($element['#options'], array_flip($element['#default_value'])) : [];

      $order = $element['#default_value'];
      // Bring options in order of default values.
      uksort($element['#options'], function ($a, $b) use ($order) {
        return array_search($a, $order) > array_search($b, $order) ? 1 : -1;
      });

      if (!$multiple) {
        $empty_option = ['' => ''];
        $element['#options'] = $empty_option + $element['#options'];
      }
    }

    // Defining the select2 configuration.
    $settings = [
      'multiple' => $multiple,
      'placeholder' => $required ? new TranslatableMarkup('- Select -') : new TranslatableMarkup('- None -'),
      'allowClear' => !$multiple && !$required ? TRUE : FALSE,
      'dir' => \Drupal::languageManager()->getCurrentLanguage()->getDirection(),
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
    ];

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attributes']['class'][] = 'select2-widget';
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAutocomplete($element) {
    if (!$element['#autocomplete']) {
      return $element;
    }

    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = isset($element['#selection_settings']) ? $element['#selection_settings'] : [];
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'select2.entity_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    ];

    /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
    $access_manager = \Drupal::service('access_manager');
    $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $element['#autocomplete_route_parameters'], \Drupal::currentUser(), TRUE);

    if ($access && $access->isAllowed()) {
      $url = Url::fromRoute($element['#autocomplete_route_name'], $element['#autocomplete_route_parameters'])
        ->toString(TRUE);

      // Provide a data attribute for the JavaScript behavior to bind to.
      $selector = $element['#attributes']['data-drupal-selector'];
      $element['#attached']['drupalSettings']['select2'][$selector] += [
        'minimumInputLength' => 1,
        'ajax' => [
          'url' => $url->getGeneratedUrl(),
        ],
      ];
    }
    return $element;
  }

}
