<?php

namespace Drupal\select2\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'select2' widget.
 *
 * @FieldWidget(
 *   id = "select2_entity_reference",
 *   label = @Translation("Select2 (simple select)"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class Select2EntityReference extends Select2Widget implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Select2EntityReference object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $autocomplete = $this->getSetting('autocomplete');
    $summary[] = t('Autocomplete: @autocomplete', ['@autocomplete' => $autocomplete ? $this->t('On') : $this->t('Off')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#target_type'] = $this->getFieldSetting('target_type');
    $element['#selection_handler'] = $this->getFieldSetting('handler');
    $element['#selection_settings'] = $this->getFieldSetting('handler_settings') + ['match_operator' => 'CONTAINS'];
    if ($this->getFieldSetting('handler_settings')['auto_create']) {
      $status = TRUE;
      $definition = $this->entityTypeManager->getDefinition($element['#target_type']);
      if ($definition->entityClassImplements(EntityPublishedInterface::class)) {
        $bundle = reset($element['#selection_settings']['target_bundles']);
        /** @var \Drupal\Core\Entity\EntityPublishedInterface $entity */
        $entity = $this->entityTypeManager->getStorage($element['#target_type'])->create([$definition->getKey('bundle') => $bundle]);
        $status = $entity->isPublished();
      }

      $element['#autocreate'] = [
        '#uid' => $this->currentUser->id(),
        '#status' => $status,
      ];

      $entities = $this->entityTypeManager->getStorage($element['#target_type'])->loadMultiple(array_keys($this->getOptions($items->getEntity())));
      foreach ($entities as $id => $entity) {
        $element['#additional_properties'][$id] = ['published' => ($entity instanceof EntityPublishedInterface ? $entity->isPublished() : TRUE)];
      }
    }

    if ($this->getSetting('autocomplete')) {
      $element['#autocomplete'] = TRUE;
    }

    return $element;
  }

}
