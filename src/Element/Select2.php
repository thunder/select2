<?php

namespace Drupal\select2\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
      'placeholder' => $required ? new TranslatableMarkup('- Select -') : new TranslatableMarkup('- None -'),
      'allowClear' => !$multiple && !$required ? TRUE : FALSE,
      'dir' => \Drupal::languageManager()->getCurrentLanguage()->getDirection(),
      'language' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'width' => $multiple ? 'element' : 'style',
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

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSelect($element) {
    $element = parent::preRenderSelect($element);

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';
    return $element;
  }

}
