<?php

namespace Drupal\select2\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Provides a form element for a simple select2 select box.
 *
 * @FormElement("select2")
 */
class Select2 extends Select {

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processSelect($element, $form_state, $complete_form);

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';

    // Defining the select2 configuration.
    $settings = [
      'multiple' => $element['#multiple'],
    ];

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;
    return $element;
  }

}
