<?php

namespace Drupal\select2\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'select2' widget.
 *
 * @FieldWidget(
 *   id = "select2_entity_reference",
 *   label = @Translation("Select2"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class Select2EntityReferenceWidget extends Select2Widget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autocomplete' => FALSE,
      'match_operator' => 'CONTAINS',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => t('Autocomplete'),
      '#default_value' => $this->getSetting('autocomplete'),
      '#description' => t('Options will be lazy loaded. This is recommended for lists with a lot of values.'),
    ];
    $element['match_operator'] = [
      '#type' => 'radios',
      '#title' => t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][autocomplete]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $autocomplete = $this->getSetting('autocomplete');
    $operators = $this->getMatchOperatorOptions();
    $summary[] = t('Autocomplete: @autocomplete', ['@autocomplete' => $autocomplete ? $this->t('On') : $this->t('Off')]);
    $summary[] = t('Autocomplete matching: @match_operator', ['@match_operator' => $operators[$this->getSetting('match_operator')]]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#target_type'] = $this->getFieldSetting('target_type');
    $element['#selection_handler'] = $this->getFieldSetting('handler');
    $element['#selection_settings'] = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];
    $element['#autocreate'] = isset($this->getFieldSetting('handler_settings')['auto_create']) ? $this->getFieldSetting('handler_settings')['auto_create'] : FALSE;
    $element['#autocomplete'] = $this->getSetting('autocomplete');
    $element['#multiple'] = $this->multiple && (count($this->options) > 1 || $element['#autocreate']);

    return $element;
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

}
