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
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    $multiple = $element['#multiple'];

    // Defining the select2 configuration.
    $settings = [
      'multiple' => $multiple,
      'placeholder' => $required ? t('- Select -') : t('- None -'),
      'allowClear' => !$multiple && !$required ? TRUE : FALSE,
    ];

    if ($multiple) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    // A non-#multiple select needs special handling to prevent user agents from
    // preselecting the first option without intention. #multiple select lists
    // do not get an empty option, as it would not make sense, user
    // interface-wise.
    else {
      if (!isset($element['#default_value'])) {
        // The empty option is prepended to #options and purposively not merged
        // to prevent another option in #options mistakenly using the same value
        // as #empty_value.
        $empty_option = ['' => ''];
        $element['#options'] = $empty_option + $element['#options'];
      }
    }

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;
    return $element;
  }

}
