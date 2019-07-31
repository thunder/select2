<?php

namespace Drupal\select2\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\select2\Select2Trait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class Select2EntityReferenceWidget extends Select2Widget implements ContainerFactoryPluginInterface {

  use Select2Trait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('entity_type.manager'));
  }

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
      '#title' => $this->t('Autocomplete'),
      '#default_value' => $this->getSetting('autocomplete'),
      '#description' => $this->t('Options will be lazy loaded. This is recommended for lists with a lot of values.'),
    ];
    $element['match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
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
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options) && $this->getSetting('autocomplete')) {
      // Get all currently selected options.
      $selected_options = [];
      foreach ($entity->get($this->fieldDefinition->getName()) as $item) {
        if ($item->{$this->column} !== NULL) {
          $selected_options[] = $item->{$this->column};
        }
      }

      if (!$selected_options) {
        return $this->options = [];
      }

      // Validate that the options are matching the target_type and handler
      // settings.
      $handler_settings = $this->getSelectionSettings() + [
        'target_type' => $this->getFieldSetting('target_type'),
        'handler' => $this->getFieldSetting('handler'),
      ];
      return $this->options = static::getValidReferenceableEntities($selected_options, $handler_settings);
    }
    return parent::getOptions($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $autocomplete = $this->getSetting('autocomplete');
    $operators = $this->getMatchOperatorOptions();
    $summary[] = $this->t('Autocomplete: @autocomplete', ['@autocomplete' => $autocomplete ? $this->t('On') : $this->t('Off')]);
    if ($autocomplete) {
      $summary[] = $this->t('Autocomplete matching: @match_operator', ['@match_operator' => $operators[$this->getSetting('match_operator')]]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#target_type'] = $this->getFieldSetting('target_type');
    $element['#selection_handler'] = $this->getFieldSetting('handler');
    $element['#selection_settings'] = $this->getSelectionSettings();
    $element['#autocomplete'] = $this->getSetting('autocomplete');

    if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
      $entity = $items->getEntity();
      $element['#autocreate'] = [
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
      ];
    }
    // Do not display a 'multiple' select box if there is only one option. But
    // with 'autocreate' or 'autocomplete' we want to ignore that.
    $element['#multiple'] = $this->multiple && (count($this->options) > 1 || isset($element['#autocreate']) || $element['#autocomplete']);

    if ($element['#autocomplete'] && $element['#multiple']) {
      $entity_definition = $this->entityTypeManager->getDefinition($element['#target_type']);
      $message = $this->t("Drag to re-order @entity_types.", ['@entity_types' => $entity_definition->getPluralLabel()]);
      if (!empty($element['#description'])) {
        $element['#description'] = [
          '#theme' => 'item_list',
          '#items' => [$element['#description'], $message],
        ];
      }
      else {
        $element['#description'] = $message;
      }
    }

    return $element;
  }

  /**
   * Build array of selection settings.
   *
   * @return array
   *   Selection settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSelectionSettings() {
    $label_field = $this->entityTypeManager->getDefinition($this->getFieldSetting('target_type'))->getKey('label') ?: '_none';
    return [
      'match_operator' => $this->getSetting('match_operator'),
      'sort' => ['field' => $label_field],
    ] + $this->getFieldSetting('handler_settings');
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareFieldValues(array $values, array $element) {
    if (empty($element['#autocreate'])) {
      return parent::prepareFieldValues($values, $element);
    }

    $handler_settings = $element['#selection_settings'] + [
      'target_type' => $element['#target_type'],
      'handler' => $element['#selection_handler'],
    ];
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($handler_settings);

    $options = static::getValidReferenceableEntities(array_keys(OptGroup::flattenOptions($element['#options'])), $handler_settings);
    $items = [];
    foreach ($values as $value) {
      if (isset($options[$value])) {
        $items[] = [$element['#key_column'] => $value];
      }
      else {
        if ($handler instanceof SelectionWithAutocreateInterface) {
          $label = substr($value, 4);
          // We are not saving created entities, because that's part of
          // Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::preSave().
          $items[] = [
            'entity' => $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $label, $element['#autocreate']['uid']),
          ];
        }
      }
    }
    return $items;
  }

  /**
   * Returns the name of the bundle which will be used for autocreated entities.
   *
   * @uses \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget::getAutocreateBundle().
   *   This is copied from core.
   *
   * @return string
   *   The bundle name.
   */
  protected function getAutocreateBundle() {
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create') && $target_bundles = $this->getSelectionHandlerSetting('target_bundles')) {
      // If there's only one target bundle, use it.
      if (count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // Otherwise use the target bundle stored in selection handler settings.
      elseif (!$bundle = $this->getSelectionHandlerSetting('auto_create_bundle')) {
        // If no bundle has been set as auto create target means that there is
        // an inconsistency in entity reference field settings.
        trigger_error(sprintf(
          "The 'Create referenced entities if they don't already exist' option is enabled but a specific destination bundle is not set. You should re-visit and fix the settings of the '%s' (%s) field.",
          $this->fieldDefinition->getLabel(),
          $this->fieldDefinition->getName()
        ), E_USER_WARNING);
      }
    }

    return $bundle;
  }

  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @uses \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget::getSelectionHandlerSetting().
   *   This is copied from core.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Returns the options for the match operator.
   *
   * @return array
   *   List of options.
   */
  protected function getMatchOperatorOptions() {
    return [
      'STARTS_WITH' => $this->t('Starts with'),
      'CONTAINS' => $this->t('Contains'),
    ];
  }

}
