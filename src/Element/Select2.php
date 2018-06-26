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
      'dir' => \Drupal::languageManager()->getCurrentLanguage()->getDirection(),
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'width' => 'style',
    ];

    if ($multiple) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
    }
    else {
      // Adding an empty option in order make the placeholder working.
      $empty_option = ['' => ''];
      $element['#options'] = $empty_option + $element['#options'];
    }

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;
    return $element;
  }

}
