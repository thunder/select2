<?php

namespace Drupal\select2\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'select2' widget.
 *
 * @FieldWidget(
 *   id = "select2",
 *   label = @Translation("Select2 (simple select)"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class Select2Widget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autocomplete' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    if ($this->fieldDefinition->getType() == 'entity_reference') {
      $element['autocomplete'] = [
        '#type' => 'checkbox',
        '#title' => t('Autocomplete'),
        '#default_value' => $this->getSetting('autocomplete'),
        '#description' => t('Options will be lazy loaded. This is recommended for lists with a lot of values.'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->fieldDefinition->getType() == 'entity_reference') {
      $autocomplete = $this->getSetting('autocomplete');
      $summary[] = t('Autocomplete: @autocomplete', ['@autocomplete' => $autocomplete ? $this->t('On') : $this->t('Off')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#type'] = 'select2';

    if ($this->getSetting('autocomplete')) {
      $element['#autocomplete'] = TRUE;
      $element['#target_type'] = $this->getFieldSetting('target_type');
      $element['#selection_handler'] = $this->getFieldSetting('handler');
      $element['#selection_settings'] = $this->getFieldSetting('handler_settings') + ['match_operator' => 'CONTAINS'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {}

  /**
   * {@inheritdoc}
   *
   * Complete copy of parent class. Only change is '_none' was replaced by ''.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '') {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.
    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = [$element['#value']];
    }

    // Filter out the '' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = [];
    foreach ($values as $value) {
      $items[] = [$element['#key_column'] => $value];
    }
    $form_state->setValueForElement($element, $items);
  }

}
